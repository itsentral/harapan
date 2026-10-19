<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_hutang_aging_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Report UMUR HUTANG (aging) per invoice PO.
     *
     * Konsep sama dengan Report Hutang, tetapi fokus pada umur hutang:
     * - 1 baris per invoice (tanpa rincian pembayaran).
     * - Hanya menampilkan invoice yang MASIH ADA sisa hutang (belum lunas).
     * - Umur hutang = selisih hari antara tanggal invoice (invoice_date)
     *   dengan tanggal hari ini.
     * - Sisa hutang dimasukkan ke salah satu kolom bucket umur:
     *   < 30 hari, < 60 hari, < 90 hari, < 120 hari.
     *
     * Tabel & relasi:
     *   - tr_invoice_po    : data invoice (supplier, tgl, no invoice, no po, nilai)
     *   - payment_approve  : link invoice <-> pembayaran (no_doc = tr_invoice_po.id,
     *                        id_payment = tr_payment_paid.id)
     *   - tr_payment_paid  : data pembayaran (tgl_bayar)
     *
     * @return array ['rows' => [...], 'total_sisa_hutang' => float, 'bucket_totals' => [...]]
     */
    public function get_aging_per_invoice()
    {
        $today_ts = strtotime(date('Y-m-d'));

        // 1. Ambil semua invoice PO yang invoice_no tidak kosong/null
        $this->db->select('id, invoice_no, invoice_date, nm_supplier, no_po, total_invoice');
        $this->db->from('tr_invoice_po');
        $this->db->where('invoice_no IS NOT NULL');
        $this->db->where('invoice_no <>', '');
        $this->db->order_by('nm_supplier ASC, invoice_date ASC, id ASC');
        $query = $this->db->get();

        if (!$query) {
            return ['rows' => [], 'total_sisa_hutang' => 0, 'bucket_totals' => $this->_empty_buckets()];
        }

        $all_invoices = $query->result_array();
        if (empty($all_invoices)) {
            return ['rows' => [], 'total_sisa_hutang' => 0, 'bucket_totals' => $this->_empty_buckets()];
        }

        $rows             = [];
        $total_sisa_hutang = 0;
        $bucket_totals     = $this->_empty_buckets();

        foreach ($all_invoices as $inv) {
            // Total pembayaran untuk invoice ini + tanggal bayar terakhir
            $this->db->select('COALESCE(SUM(pa.payment_bank), 0) AS total_bayar');
            $this->db->select('MAX(pp.tgl_bayar) AS tgl_terakhir_bayar', false);
            $this->db->from('payment_approve pa');
            $this->db->join('tr_payment_paid pp', 'pp.id = pa.id_payment', 'inner');
            $this->db->where('pa.no_doc', $inv['id']);
            $this->db->where('pa.id_payment IS NOT NULL');
            $this->db->where('pa.id_payment <>', '');
            $bayar_result = $this->db->get();

            $total_bayar        = 0;
            $tgl_terakhir_bayar = '';
            if ($bayar_result) {
                $brow = $bayar_result->row_array();
                if ($brow) {
                    $total_bayar        = (float)$brow['total_bayar'];
                    $tgl_terakhir_bayar = $brow['tgl_terakhir_bayar'] ?: '';
                }
            }

            $nilai_invoice = (float)$inv['total_invoice'];
            $sisa_hutang   = $nilai_invoice - $total_bayar;

            // Skip invoice yang sudah lunas / tidak ada sisa hutang
            if ($sisa_hutang <= 0) {
                continue;
            }

            // Umur hutang = selisih hari tanggal invoice vs hari ini
            $umur_hari = 0;
            if (!empty($inv['invoice_date'])) {
                $inv_ts = strtotime(date('Y-m-d', strtotime($inv['invoice_date'])));
                $umur_hari = (int) floor(($today_ts - $inv_ts) / 86400);
                if ($umur_hari < 0) {
                    $umur_hari = 0;
                }
            }

            $bucket = $this->_bucket_of($umur_hari);

            // Nilai per kolom bucket: sisa hutang hanya mengisi 1 kolom
            $bk = ['<30' => 0, '<60' => 0, '<90' => 0, '<120' => 0];
            $bk[$bucket] = $sisa_hutang;

            $rows[] = [
                'nm_supplier'        => $inv['nm_supplier'],
                'tgl_invoice'        => $inv['invoice_date'],
                'no_po'              => $inv['no_po'],
                'id_invoice'         => $inv['invoice_no'],
                'sisa_hutang'        => $sisa_hutang,
                'tgl_terakhir_bayar' => $tgl_terakhir_bayar,
                'umur_hari'          => $umur_hari,
                'bucket'             => $bucket,
                'b_30'               => $bk['<30'],
                'b_60'               => $bk['<60'],
                'b_90'               => $bk['<90'],
                'b_120'              => $bk['<120'],
            ];

            $total_sisa_hutang      += $sisa_hutang;
            $bucket_totals[$bucket] += $sisa_hutang;
        }

        return [
            'rows'              => $rows,
            'total_sisa_hutang' => $total_sisa_hutang,
            'bucket_totals'     => $bucket_totals,
        ];
    }

    /**
     * Tentukan bucket aging berdasarkan umur (hari).
     * <30  : 0 - 30 hari
     * <60  : 31 - 60 hari
     * <90  : 61 - 90 hari
     * <120 : > 90 hari (termasuk 91 hari ke atas)
     */
    private function _bucket_of($umur_hari)
    {
        if ($umur_hari <= 30) return '<30';
        if ($umur_hari <= 60) return '<60';
        if ($umur_hari <= 90) return '<90';
        return '<120';
    }

    /**
     * Struktur bucket kosong.
     */
    private function _empty_buckets()
    {
        return [
            '<30'  => 0,
            '<60'  => 0,
            '<90'  => 0,
            '<120' => 0,
        ];
    }
}
