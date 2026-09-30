<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_piutang_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ambil semua invoice yang masih ada sisa piutang (belum lunas)
     * per tanggal yang dipilih, beserta rincian pembayarannya.
     *
     * @param  string $tanggal  Format Y-m-d
     * @return array
     */
    public function get_piutang_per_invoice($tanggal)
    {
        $rows = $this->_build_report_rows($tanggal);

        // Hitung total piutang: ambil sisa_piutang dari baris terakhir tiap invoice
        $total_piutang = 0;
        $last_invoice = null;
        $last_sisa = 0;

        foreach ($rows as $r) {
            if ($r['is_first_row'] && $last_invoice !== null) {
                $total_piutang += $last_sisa;
            }
            $last_invoice = $r['id_invoice'];
            $last_sisa = (float)$r['sisa_piutang'];
        }
        // Tambahkan invoice terakhir
        if ($last_invoice !== null) {
            $total_piutang += $last_sisa;
        }

        return [
            'rows'          => $rows,
            'total_piutang' => $total_piutang,
        ];
    }

    /**
     * Build report rows: per invoice tampilkan semua baris pembayaran,
     * dengan running total bayar dan sisa piutang di setiap baris.
     */
    private function _build_report_rows($tanggal)
    {
        // 1. Ambil semua invoice s/d tanggal (nm_customer sudah ada di tr_invoice_sales)
        $this->db->select('id_invoice, id_customer, nm_customer, created_on AS tgl_invoice, grand_total AS nilai_invoice');
        $this->db->from('tr_invoice_sales');
        $this->db->where('DATE(created_on) <=', $tanggal);
        $this->db->where('is_cancel', null);
        $this->db->order_by('nm_customer ASC, created_on ASC, id_invoice ASC');
        $all_invoices = $this->db->get();

        if (!$all_invoices) {
            return [];
        }

        $all_invoices = $all_invoices->result_array();

        if (empty($all_invoices)) {
            return [];
        }

        $rows = [];

        foreach ($all_invoices as $inv) {
            // Hitung total pelunasan untuk invoice ini s/d tanggal.
            // Pelunasan = uang bank riil (total_bayar_idr) + credit note (total_cn_idr)
            // + pembulatan kekurangan (pembulatan_idr), konsisten dengan cara modul
            // Penerimaan menutup sisa invoice menjadi lunas.
            $this->db->select('COALESCE(SUM(d.total_bayar_idr), 0) AS total_bayar');
            $this->db->select('COALESCE(SUM(d.total_cn_idr), 0) AS total_cn');
            $this->db->select('COALESCE(SUM(d.pembulatan_idr), 0) AS total_pembulatan');
            $this->db->from('tr_invoice_payment_detail d');
            $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = d.kd_pembayaran', 'inner');
            $this->db->where('d.no_invoice', $inv['id_invoice']);
            $this->db->where('p.tgl_pembayaran <=', $tanggal);
            $bayar_result = $this->db->get();

            $total_bayar_sd_tgl = 0;
            $total_pelunasan_sd_tgl = 0;
            if ($bayar_result) {
                $bayar_row = $bayar_result->row_array();
                if ($bayar_row) {
                    $total_bayar_sd_tgl = (float)$bayar_row['total_bayar'];
                    $total_pelunasan_sd_tgl = (float)$bayar_row['total_bayar']
                        + (float)$bayar_row['total_cn']
                        + (float)$bayar_row['total_pembulatan'];
                }
            }

            // Skip invoice yang sudah lunas (pelunasan mencakup bayar + CN + pembulatan)
            if ((float)$inv['nilai_invoice'] <= $total_pelunasan_sd_tgl) {
                continue;
            }

            // Ambil semua baris pembayaran untuk invoice ini s/d tanggal
            $this->db->select('p.kd_pembayaran, p.tgl_pembayaran, d.total_bayar_idr AS nilai_bayar, d.sisa_invoice_idr AS sisa');
            $this->db->from('tr_invoice_payment_detail d');
            $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = d.kd_pembayaran', 'inner');
            $this->db->where('d.no_invoice', $inv['id_invoice']);
            $this->db->where('p.tgl_pembayaran <=', $tanggal);
            $this->db->order_by('p.tgl_pembayaran ASC, p.kd_pembayaran ASC');
            $pay_query = $this->db->get();

            $payments = $pay_query ? $pay_query->result_array() : [];

            if (empty($payments)) {
                // Invoice belum ada pembayaran sama sekali
                $rows[] = [
                    'name_customer'  => $inv['nm_customer'],
                    'tgl_invoice'    => $inv['tgl_invoice'],
                    'id_invoice'     => $inv['id_invoice'],
                    'nilai_invoice'  => $inv['nilai_invoice'],
                    'kd_pembayaran'  => '',
                    'tgl_bayar'      => '',
                    'nilai_bayar'    => '',
                    'total_bayar'    => '',
                    'sisa_piutang'   => $inv['nilai_invoice'],
                    'is_first_row'   => true,
                    'rowspan'        => 1,
                ];
            } else {
                $running_total = 0;
                $rowspan = count($payments);

                foreach ($payments as $idx => $pay) {
                    $running_total += $pay['nilai_bayar'];
                    // $sisa = $inv['nilai_invoice'] - $running_total;
                    $sisa = $pay['sisa'];

                    $rows[] = [
                        'name_customer'  => $inv['nm_customer'],
                        'tgl_invoice'    => $inv['tgl_invoice'],
                        'id_invoice'     => $inv['id_invoice'],
                        'nilai_invoice'  => $inv['nilai_invoice'],
                        'kd_pembayaran'  => $pay['kd_pembayaran'],
                        'tgl_bayar'      => $pay['tgl_pembayaran'],
                        'nilai_bayar'    => $pay['nilai_bayar'],
                        'total_bayar'    => $running_total,
                        'sisa_piutang'   => $sisa,
                        'is_first_row'   => ($idx === 0),
                        'rowspan'        => $rowspan,
                    ];
                }
            }
        }

        return $rows;
    }
}
