<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Model bersama untuk pencatatan mutasi stok in-transit / in-customer.
 *
 * Tabel posisi (warehouse_stock_intransit / warehouse_stock_incustomer) TETAP
 * memakai hard delete pada tiap perpindahan tahap. Model ini hanya menambahkan
 * baris ke ledger append-only `warehouse_stock_mutasi` agar jejak perpindahan
 * (CREATE -> CONFIRM -> INVOICE) tersimpan untuk audit & perhitungan lead time.
 *
 * Dipakai bersama oleh modul:
 *   - surat_jalan (gudang)        : event CREATE / UPDATE / CONFIRM
 *   - surat_jalan_pabrik          : event CREATE / UPDATE / CONFIRM
 *   - invoice_produk              : event INVOICE
 *
 * PENTING: panggil method ini DI DALAM transaksi DB controller pemanggil,
 * supaya ikut rollback bila proses utama gagal.
 */
class Warehouse_stock_model extends BF_Model
{
    protected $table_name = 'warehouse_stock_mutasi';
    protected $key        = 'id';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Catat satu batch peristiwa perpindahan stok ke ledger.
     *
     * @param string $event   CREATE | UPDATE | CONFIRM | INVOICE
     * @param string|null $dari_tahap  NULL | INTRANSIT | INCUSTOMER
     * @param string|null $ke_tahap    INTRANSIT | INCUSTOMER | INVOICED
     * @param array  $rows    Array baris. Tiap baris array asosiatif berisi:
     *                        no_so, no_surat_jalan, id_sj, id_sj_detail,
     *                        id_product, product, qty, costbook
     *                        (total_harga dihitung otomatis costbook*qty bila tak diisi)
     * @param int|null $user_id  ID user pemicu (default: auth user id bila tersedia)
     * @param string|null $ref_no  Nomor dokumen pemicu (mis. id_invoice saat INVOICE)
     * @param string|null $keterangan  Catatan opsional
     * @return int Jumlah baris yang dicatat
     */
    public function log_mutasi($event, $dari_tahap, $ke_tahap, array $rows, $user_id = null, $ref_no = null, $keterangan = null)
    {
        if (empty($rows)) {
            return 0;
        }

        if ($user_id === null && isset($this->auth) && is_object($this->auth)) {
            $user_id = $this->auth->user_id();
        }

        $now = date('Y-m-d H:i:s');

        $batch = [];
        foreach ($rows as $r) {
            $qty      = (float) ($r['qty'] ?? 0);
            $costbook = (float) ($r['costbook'] ?? 0);
            $total    = array_key_exists('total_harga', $r)
                ? (float) $r['total_harga']
                : ($costbook * $qty);

            $batch[] = [
                'tgl_event'      => $now,
                'event'          => $event,
                'dari_tahap'     => $dari_tahap,
                'ke_tahap'       => $ke_tahap,
                'no_so'          => $r['no_so'] ?? null,
                'no_surat_jalan' => $r['no_surat_jalan'] ?? null,
                'id_sj'          => $r['id_sj'] ?? null,
                'id_sj_detail'   => $r['id_sj_detail'] ?? null,
                'id_product'     => $r['id_product'] ?? null,
                'product'        => $r['product'] ?? null,
                'qty'            => $qty,
                'costbook'       => $costbook,
                'total_harga'    => $total,
                'ref_no'         => $ref_no,
                'keterangan'     => $keterangan,
                'created_by'     => $user_id,
                'created_at'     => $now,
            ];
        }

        $this->db->insert_batch('warehouse_stock_mutasi', $batch);
        return count($batch);
    }
}
