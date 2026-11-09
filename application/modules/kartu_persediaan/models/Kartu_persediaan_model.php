<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * Model Kartu Persediaan (metode Moving Average)
 *
 * Sumber data : tabel `kartu_stok` (ledger stok yang diisi modul incoming_check,
 *               surat_jalan, retur, adjustment, dll).
 *
 * Aturan masuk/keluar  : ditentukan dari TANDA `qty_transaksi`.
 *                        qty_transaksi > 0  => DEBET (barang masuk)
 *                        qty_transaksi < 0  => KREDIT (barang keluar)
 *
 * Saldo awal (Opsi 1)  : akumulasi moving-average seluruh transaksi SEBELUM
 *                        tanggal "dari" (start_date). Tidak memakai tabel tutup
 *                        buku terpisah — konsisten dengan sumber `kartu_stok`.
 *
 * Harga keluar         : lihat konstanta METODE_HARGA_KELUAR di bawah.
 *                        'moving_average' (Opsi A, default) => qty_keluar x harga rata-rata berjalan.
 *                        'harga_stok'     (Opsi B)          => pakai kolom harga_stok apa adanya.
 */

class Kartu_persediaan_model extends BF_Model
{
    /**
     * Metode penilaian harga barang KELUAR.
     *   'harga_stok'     = Opsi B (AKTIF)  : harga keluar = kolom harga_stok dari kartu_stok
     *                                        (costbook yang sudah moving-average oleh sistem saat transaksi).
     *   'moving_average' = Opsi A          : harga keluar = saldo_total / saldo_qty (rata-rata berjalan,
     *                                        dihitung ulang). Catatan: bisa menghasilkan 0 bila saldo
     *                                        berjalan <= 0 karena data historis tidak lengkap.
     * Ganti satu nilai ini untuk beralih antar opsi.
     */
    const METODE_HARGA_KELUAR = 'harga_stok';

    /** Jumlah desimal pembulatan harga rata-rata. */
    const DESIMAL_HARGA = 2;

    /**
     * Daftar jenis transaksi yang DIKECUALIKAN dari kartu persediaan.
     * Ini transaksi yang sifatnya booking/penawaran, bukan pergerakan stok fisik.
     *
     * Catatan: 'Sales Order', 'Delivery Pabrik', dan 'Cancel SPK' punya
     * qty_transaksi = 0 sehingga sudah otomatis terbuang oleh filter
     * `qty_transaksi <> 0` (lihat _apply_filter_transaksi). Yang WAJIB
     * ada di daftar ini adalah transaksi yang ber-nilai (qty != 0) tetapi
     * secara bisnis bukan persediaan — mis. 'Batal SO'.
     */
    private $exclude_transaksi = [
        'Batal SO',
    ];

    public function __construct()
    {
        parent::__construct();

        $this->ENABLE_ADD    = has_permission('Warehouse.Add');
        $this->ENABLE_MANAGE = has_permission('Warehouse.Manage');
        $this->ENABLE_VIEW   = has_permission('Warehouse.View');
        $this->ENABLE_DELETE = has_permission('Warehouse.Delete');
    }

    /**
     * Terapkan filter standar kartu persediaan ke query builder aktif:
     *   - hanya baris dengan qty_transaksi != 0 (abaikan booking/komitmen),
     *   - kecualikan jenis transaksi di $exclude_transaksi (mis. Batal SO).
     * Dipanggil konsisten di semua query (saldo awal, rekap, detail).
     */
    private function _apply_filter_transaksi()
    {
        $this->db->where('ks.qty_transaksi !=', 0);
        if (!empty($this->exclude_transaksi)) {
            $this->db->where_not_in('ks.transaksi', $this->exclude_transaksi);
        }
    }

    // =====================================================================
    // HELPER PERHITUNGAN MOVING AVERAGE
    // =====================================================================

    /**
     * Terapkan satu baris transaksi kartu_stok ke saldo berjalan.
     *
     * @param array $saldo  ['qty' => float, 'total' => float] saldo sebelum transaksi (by-ref).
     * @param array $row    satu baris kartu_stok (minimal: qty_transaksi, harga_stok).
     * @return array        rincian baris: qty_in, nilai_in, qty_out, harga_out, nilai_out,
     *                       saldo_qty, saldo_harga, saldo_total.
     */
    public function hitung_baris(array &$saldo, array $row)
    {
        $qty_transaksi = (float) $row['qty_transaksi'];
        $harga_stok    = isset($row['harga_stok']) ? (float) $row['harga_stok'] : 0.0;

        $qty_in   = 0.0;
        $nilai_in = 0.0;
        $qty_out  = 0.0;
        $harga_out = 0.0;
        $nilai_out = 0.0;

        if ($qty_transaksi > 0) {
            // ===== BARANG MASUK (DEBET) =====
            $qty_in        = $qty_transaksi;
            $nilai_in      = $qty_in * $harga_stok;        // harga beli real dari kartu_stok
            $saldo['qty']   += $qty_in;
            $saldo['total'] += $nilai_in;
        } elseif ($qty_transaksi < 0) {
            // ===== BARANG KELUAR (KREDIT) =====
            $qty_out = abs($qty_transaksi);

            if (self::METODE_HARGA_KELUAR === 'harga_stok') {
                // Opsi B: pakai harga_stok apa adanya.
                $harga_out = $harga_stok;
            } else {
                // Opsi A (default): rata-rata berjalan saat ini.
                $harga_out = ($saldo['qty'] > 0)
                    ? round($saldo['total'] / $saldo['qty'], self::DESIMAL_HARGA)
                    : 0.0;
            }

            $nilai_out      = $qty_out * $harga_out;
            $saldo['qty']   -= $qty_out;
            $saldo['total'] -= $nilai_out;
        }

        // Harga rata-rata saldo setelah transaksi (2 desimal).
        $saldo_harga = ($saldo['qty'] != 0)
            ? round($saldo['total'] / $saldo['qty'], self::DESIMAL_HARGA)
            : 0.0;

        return [
            'qty_in'      => $qty_in,
            'nilai_in'    => $nilai_in,
            'qty_out'     => $qty_out,
            'harga_out'   => $harga_out,
            'nilai_out'   => $nilai_out,
            'saldo_qty'   => $saldo['qty'],
            'saldo_harga' => $saldo_harga,
            'saldo_total' => $saldo['total'],
        ];
    }

    /**
     * Hitung SALDO AWAL (qty + nilai) per produk: akumulasi transaksi kartu_stok
     * SEBELUM $start_date. Jika $start_date kosong, saldo awal = 0.
     *
     * $anchor_date (tanggal awal pembukuan) membatasi BATAS BAWAH akumulasi:
     * hanya transaksi dengan tgl >= $anchor_date yang ikut dihitung. Jika kosong,
     * akumulasi dari awal data (tanpa batas bawah).
     *
     * @param string|null $code_lv4    batasi ke satu produk (opsional).
     * @param string|null $start_date  'Y-m-d' — batas atas (eksklusif) saldo awal.
     * @param string|null $anchor_date 'Y-m-d' — batas bawah (inklusif) akumulasi.
     * @return array  [code_lv4 => ['qty'=>float,'total'=>float,'nm_product'=>string]]
     */
    public function hitung_saldo_awal($code_lv4 = null, $start_date = null, $anchor_date = null)
    {
        $hasil = [];
        if (empty($start_date)) {
            return $hasil;
        }

        $this->db->select('ks.code_lv4, ks.nm_product, ks.qty_transaksi, ks.harga_stok');
        $this->db->from('kartu_stok ks');
        $this->db->where('ks.deleted', null);
        $this->db->where('DATE(ks.tgl_transaksi) <', $start_date);
        if (!empty($anchor_date)) {
            $this->db->where('DATE(ks.tgl_transaksi) >=', $anchor_date);
        }
        $this->_apply_filter_transaksi();
        if (!empty($code_lv4)) {
            $this->db->where('ks.code_lv4', $code_lv4);
        }
        $this->db->order_by('ks.tgl_transaksi', 'asc');
        $this->db->order_by('ks.id', 'asc');
        $rows = $this->db->get()->result_array();

        $saldo_map = [];
        foreach ($rows as $row) {
            $kode = $row['code_lv4'];
            if (!isset($saldo_map[$kode])) {
                $saldo_map[$kode] = ['qty' => 0.0, 'total' => 0.0, 'nm_product' => $row['nm_product']];
            }
            $saldo = ['qty' => $saldo_map[$kode]['qty'], 'total' => $saldo_map[$kode]['total']];
            $this->hitung_baris($saldo, $row);
            $saldo_map[$kode]['qty']        = $saldo['qty'];
            $saldo_map[$kode]['total']      = $saldo['total'];
            $saldo_map[$kode]['nm_product'] = $row['nm_product'];
        }

        return $saldo_map;
    }

    // =====================================================================
    // REKAP (blok kiri Excel): satu baris per produk
    // =====================================================================

    /**
     * Ambil data rekap per produk dalam rentang [start_date, end_date].
     * Mengembalikan daftar produk dengan total DEBET, KREDIT, dan SALDO AKHIR.
     *
     * @return array daftar baris rekap (sudah dihitung), siap dipakai DataTables/export.
     */
    public function get_rekap($start_date = null, $end_date = null, $like_value = null, $anchor_date = null)
    {
        // 1) Saldo awal per produk (akumulasi anchor_date .. < start_date).
        $saldo_awal = $this->hitung_saldo_awal(null, $start_date, $anchor_date);

        // 2) Ambil transaksi dalam periode, urut per produk lalu waktu.
        $this->db->select('ks.code_lv4, ks.nm_product, ks.qty_transaksi, ks.harga_stok, ks.tgl_transaksi, ks.id');
        $this->db->from('kartu_stok ks');
        $this->db->where('ks.deleted', null);
        $this->_apply_filter_transaksi();
        if (!empty($start_date)) $this->db->where('DATE(ks.tgl_transaksi) >=', $start_date);
        if (!empty($end_date))   $this->db->where('DATE(ks.tgl_transaksi) <=', $end_date);
        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('ks.code_lv4', $like_value);
            $this->db->or_like('ks.nm_product', $like_value);
            $this->db->group_end();
        }
        $this->db->order_by('ks.code_lv4', 'asc');
        $this->db->order_by('ks.tgl_transaksi', 'asc');
        $this->db->order_by('ks.id', 'asc');
        $rows = $this->db->get()->result_array();

        // 3) Rekap per produk.
        $rekap = []; // code_lv4 => [...]
        foreach ($rows as $row) {
            $kode = $row['code_lv4'];
            if (!isset($rekap[$kode])) {
                $awal = isset($saldo_awal[$kode]) ? $saldo_awal[$kode] : ['qty' => 0.0, 'total' => 0.0];
                $rekap[$kode] = [
                    'code_lv4'     => $kode,
                    'nm_product'   => $row['nm_product'],
                    'saldo_awal_qty'   => $awal['qty'],
                    'saldo_awal_total' => $awal['total'],
                    'debet_qty'    => 0.0,
                    'debet_total'  => 0.0,
                    'kredit_qty'   => 0.0,
                    'kredit_total' => 0.0,
                    '_saldo'       => ['qty' => $awal['qty'], 'total' => $awal['total']],
                ];
            }

            $detail = $this->hitung_baris($rekap[$kode]['_saldo'], $row);
            $rekap[$kode]['debet_qty']    += $detail['qty_in'];
            $rekap[$kode]['debet_total']  += $detail['nilai_in'];
            $rekap[$kode]['kredit_qty']   += $detail['qty_out'];
            $rekap[$kode]['kredit_total'] += $detail['nilai_out'];
            $rekap[$kode]['nm_product']    = $row['nm_product'];
        }

        // 4) Tambahkan produk yang HANYA punya saldo awal (tanpa transaksi di periode).
        foreach ($saldo_awal as $kode => $awal) {
            if (isset($rekap[$kode])) continue;
            if (!empty($like_value)) {
                $hay = strtolower($kode . ' ' . $awal['nm_product']);
                if (strpos($hay, strtolower($like_value)) === false) continue;
            }
            $rekap[$kode] = [
                'code_lv4'     => $kode,
                'nm_product'   => $awal['nm_product'],
                'saldo_awal_qty'   => $awal['qty'],
                'saldo_awal_total' => $awal['total'],
                'debet_qty'    => 0.0,
                'debet_total'  => 0.0,
                'kredit_qty'   => 0.0,
                'kredit_total' => 0.0,
                '_saldo'       => ['qty' => $awal['qty'], 'total' => $awal['total']],
            ];
        }

        // 5) Finalisasi saldo akhir + harga rata-rata.
        $out = [];
        foreach ($rekap as $kode => $r) {
            $saldo_qty   = $r['_saldo']['qty'];
            $saldo_total = $r['_saldo']['total'];
            $out[] = [
                'code_lv4'         => $r['code_lv4'],
                'nm_product'       => $r['nm_product'],
                'saldo_awal_qty'   => $r['saldo_awal_qty'],
                'saldo_awal_total' => round($r['saldo_awal_total'], self::DESIMAL_HARGA),
                'debet_qty'        => $r['debet_qty'],
                'debet_total'      => round($r['debet_total'], self::DESIMAL_HARGA),
                'kredit_qty'       => $r['kredit_qty'],
                'kredit_total'     => round($r['kredit_total'], self::DESIMAL_HARGA),
                'saldo_qty'        => $saldo_qty,
                'saldo_harga'      => ($saldo_qty != 0) ? round($saldo_total / $saldo_qty, self::DESIMAL_HARGA) : 0.0,
                'saldo_total'      => round($saldo_total, self::DESIMAL_HARGA),
            ];
        }

        // Urut berdasarkan nama produk.
        usort($out, function ($a, $b) {
            return strcmp($a['nm_product'], $b['nm_product']);
        });

        return $out;
    }

    // =====================================================================
    // DETAIL per barang (blok kanan Excel): satu baris per transaksi
    // =====================================================================

    /**
     * Ambil kartu detail satu produk dalam rentang [start_date, end_date].
     * Termasuk baris "Saldo Awal" di posisi teratas.
     *
     * @return array ['nm_product'=>..., 'rows'=>[...], 'total'=>[...]]
     */
    public function get_detail($code_lv4, $start_date = null, $end_date = null, $anchor_date = null)
    {
        $out = ['code_lv4' => $code_lv4, 'nm_product' => '', 'rows' => [], 'total' => []];

        if (empty($code_lv4)) {
            return $out;
        }

        // Saldo awal produk ini (dibatasi anchor_date jika ada).
        $saldo_awal_map = $this->hitung_saldo_awal($code_lv4, $start_date, $anchor_date);
        $awal = isset($saldo_awal_map[$code_lv4])
            ? $saldo_awal_map[$code_lv4]
            : ['qty' => 0.0, 'total' => 0.0, 'nm_product' => ''];

        $saldo = ['qty' => $awal['qty'], 'total' => $awal['total']];

        // Baris Saldo Awal.
        $saldo_awal_harga = ($saldo['qty'] != 0) ? round($saldo['total'] / $saldo['qty'], self::DESIMAL_HARGA) : 0.0;
        $out['rows'][] = [
            'tgl_transaksi' => $start_date,
            'no_transaksi'  => 'Saldo Awal',
            'transaksi'     => 'Saldo Awal',
            'debet_qty'     => 0.0,
            'debet_harga'   => 0.0,
            'debet_total'   => 0.0,
            'kredit_qty'    => 0.0,
            'kredit_harga'  => 0.0,
            'kredit_total'  => 0.0,
            'saldo_qty'     => $saldo['qty'],
            'saldo_harga'   => $saldo_awal_harga,
            'saldo_total'   => round($saldo['total'], self::DESIMAL_HARGA),
            'is_saldo_awal' => true,
        ];

        // Transaksi dalam periode.
        $this->db->select('ks.id, ks.tgl_transaksi, ks.no_transaksi, ks.transaksi, ks.nm_product, ks.qty_transaksi, ks.harga_stok');
        $this->db->from('kartu_stok ks');
        $this->db->where('ks.deleted', null);
        $this->db->where('ks.code_lv4', $code_lv4);
        $this->_apply_filter_transaksi();
        if (!empty($start_date)) $this->db->where('DATE(ks.tgl_transaksi) >=', $start_date);
        if (!empty($end_date))   $this->db->where('DATE(ks.tgl_transaksi) <=', $end_date);
        $this->db->order_by('ks.tgl_transaksi', 'asc');
        $this->db->order_by('ks.id', 'asc');
        $rows = $this->db->get()->result_array();

        $tot_debet_qty = 0.0;
        $tot_debet_total = 0.0;
        $tot_kredit_qty = 0.0;
        $tot_kredit_total = 0.0;

        foreach ($rows as $row) {
            $out['nm_product'] = $row['nm_product'];
            $detail = $this->hitung_baris($saldo, $row);

            $tot_debet_qty    += $detail['qty_in'];
            $tot_debet_total  += $detail['nilai_in'];
            $tot_kredit_qty   += $detail['qty_out'];
            $tot_kredit_total += $detail['nilai_out'];

            $out['rows'][] = [
                'tgl_transaksi' => $row['tgl_transaksi'],
                'no_transaksi'  => $row['no_transaksi'],
                'transaksi'     => $row['transaksi'],
                'debet_qty'     => $detail['qty_in'],
                'debet_harga'   => ($detail['qty_in'] > 0) ? round($detail['nilai_in'] / $detail['qty_in'], self::DESIMAL_HARGA) : 0.0,
                'debet_total'   => round($detail['nilai_in'], self::DESIMAL_HARGA),
                'kredit_qty'    => $detail['qty_out'],
                'kredit_harga'  => round($detail['harga_out'], self::DESIMAL_HARGA),
                'kredit_total'  => round($detail['nilai_out'], self::DESIMAL_HARGA),
                'saldo_qty'     => $detail['saldo_qty'],
                'saldo_harga'   => $detail['saldo_harga'],
                'saldo_total'   => round($detail['saldo_total'], self::DESIMAL_HARGA),
                'is_saldo_awal' => false,
            ];
        }

        if (empty($out['nm_product'])) {
            $out['nm_product'] = $awal['nm_product'];
        }

        $out['total'] = [
            'debet_qty'    => $tot_debet_qty,
            'debet_total'  => round($tot_debet_total, self::DESIMAL_HARGA),
            'kredit_qty'   => $tot_kredit_qty,
            'kredit_total' => round($tot_kredit_total, self::DESIMAL_HARGA),
            'saldo_qty'    => $saldo['qty'],
            'saldo_harga'  => ($saldo['qty'] != 0) ? round($saldo['total'] / $saldo['qty'], self::DESIMAL_HARGA) : 0.0,
            'saldo_total'  => round($saldo['total'], self::DESIMAL_HARGA),
        ];

        return $out;
    }
}
/* End of file Kartu_persediaan_model.php */
