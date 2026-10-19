<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_piutang_aging_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Report UMUR PIUTANG (aging) per invoice.
     *
     * Konsep sama dengan Report Piutang, tetapi fokus pada umur piutang:
     * - 1 baris per invoice (tanpa rincian penerimaan).
     * - Hanya menampilkan invoice yang MASIH ADA sisa piutang (belum lunas).
     * - Umur piutang = selisih hari antara tanggal invoice (created_on)
     *   dengan tanggal hari ini.
     * - Sisa piutang dimasukkan ke salah satu kolom bucket umur:
     *   < 30 hari, < 60 hari, < 90 hari, < 120 hari.
     *
     * @return array ['rows' => [...], 'total_sisa_piutang' => float, 'bucket_totals' => [...]]
     */
    public function get_aging_per_invoice()
    {
        $today_ts = strtotime(date('Y-m-d'));

        // 1. Ambil semua invoice aktif (belum di-cancel)
        $this->db->select('id_invoice, id_customer, nm_customer, created_on AS tgl_invoice, grand_total AS nilai_invoice');
        $this->db->from('tr_invoice_sales');
        $this->db->where('is_cancel', null);
        $this->db->order_by('nm_customer ASC, created_on ASC, id_invoice ASC');
        $query = $this->db->get();

        if (!$query) {
            return ['rows' => [], 'total_sisa_piutang' => 0, 'bucket_totals' => $this->_empty_buckets()];
        }

        $all_invoices = $query->result_array();
        if (empty($all_invoices)) {
            return ['rows' => [], 'total_sisa_piutang' => 0, 'bucket_totals' => $this->_empty_buckets()];
        }

        $rows               = [];
        $total_sisa_piutang = 0;
        $bucket_totals      = $this->_empty_buckets();

        foreach ($all_invoices as $inv) {
            // Total pelunasan untuk invoice ini (bank + CN + pembulatan),
            // konsisten dengan modul Report Piutang.
            $this->db->select('COALESCE(SUM(d.total_bayar_idr), 0) AS total_bayar');
            $this->db->select('COALESCE(SUM(d.total_cn_idr), 0) AS total_cn');
            $this->db->select('COALESCE(SUM(d.pembulatan_idr), 0) AS total_pembulatan');
            $this->db->select('MAX(p.tgl_pembayaran) AS tgl_terakhir_bayar', false);
            $this->db->from('tr_invoice_payment_detail d');
            $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = d.kd_pembayaran', 'inner');
            $this->db->where('d.no_invoice', $inv['id_invoice']);
            $bayar_result = $this->db->get();

            $total_pelunasan    = 0;
            $tgl_terakhir_bayar = '';
            if ($bayar_result) {
                $brow = $bayar_result->row_array();
                if ($brow) {
                    $total_pelunasan = (float)$brow['total_bayar']
                        + (float)$brow['total_cn']
                        + (float)$brow['total_pembulatan'];
                    $tgl_terakhir_bayar = $brow['tgl_terakhir_bayar'] ?: '';
                }
            }

            $nilai_invoice = (float)$inv['nilai_invoice'];
            $sisa_piutang  = $nilai_invoice - $total_pelunasan;

            // Skip invoice yang sudah lunas / tidak ada sisa piutang
            if ($sisa_piutang <= 0) {
                continue;
            }

            // Umur piutang = selisih hari tanggal invoice vs hari ini
            $umur_hari = 0;
            if (!empty($inv['tgl_invoice'])) {
                $inv_ts = strtotime(date('Y-m-d', strtotime($inv['tgl_invoice'])));
                $umur_hari = (int) floor(($today_ts - $inv_ts) / 86400);
                if ($umur_hari < 0) {
                    $umur_hari = 0;
                }
            }

            $bucket = $this->_bucket_of($umur_hari);

            // Nilai per kolom bucket: sisa piutang hanya mengisi 1 kolom
            $bk = ['<30' => 0, '<60' => 0, '<90' => 0, '<120' => 0];
            $bk[$bucket] = $sisa_piutang;

            $rows[] = [
                'name_customer'      => $inv['nm_customer'],
                'tgl_invoice'        => $inv['tgl_invoice'],
                'id_invoice'         => $inv['id_invoice'],
                'sisa_piutang'       => $sisa_piutang,
                'tgl_terakhir_bayar' => $tgl_terakhir_bayar,
                'umur_hari'          => $umur_hari,
                'bucket'             => $bucket,
                'b_30'               => $bk['<30'],
                'b_60'               => $bk['<60'],
                'b_90'               => $bk['<90'],
                'b_120'              => $bk['<120'],
            ];

            $total_sisa_piutang     += $sisa_piutang;
            $bucket_totals[$bucket] += $sisa_piutang;
        }

        return [
            'rows'               => $rows,
            'total_sisa_piutang' => $total_sisa_piutang,
            'bucket_totals'      => $bucket_totals,
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
