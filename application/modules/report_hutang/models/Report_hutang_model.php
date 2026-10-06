<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_hutang_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ambil semua invoice PO yang masih ada sisa hutang (belum lunas)
     * per tanggal yang dipilih, beserta rincian pembayarannya.
     *
     * @param  string $tanggal  Format Y-m-d
     * @return array  ['rows' => [...], 'total_hutang' => float]
     */
    public function get_hutang_per_invoice($tanggal)
    {
        $rows = $this->_build_report_rows($tanggal);

        // Hitung total hutang: ambil sisa_hutang dari baris terakhir tiap invoice
        $total_hutang = 0;
        $last_invoice = null;
        $last_sisa = 0;

        foreach ($rows as $r) {
            if ($r['is_first_row'] && $last_invoice !== null) {
                $total_hutang += $last_sisa;
            }
            $last_invoice = $r['id_invoice'];
            $last_sisa = (float)$r['sisa_hutang'];
        }
        if ($last_invoice !== null) {
            $total_hutang += $last_sisa;
        }

        return [
            'rows'         => $rows,
            'total_hutang' => $total_hutang,
        ];
    }

    /**
     * Build report rows
     *
     * Tabel & relasi:
     *   - tr_invoice_po          : data invoice (supplier, tgl, no invoice, no po, nilai)
     *   - payment_approve        : link invoice <-> pembayaran
     *       no_doc     = tr_invoice_po.id
     *       id_payment = tr_payment_paid.id  (kosong/null = belum bayar)
     *   - tr_payment_paid        : data pembayaran (tgl_bayar, payment_bank)
     *       id = payment_approve.id_payment
     */
    private function _build_report_rows($tanggal)
    {
        // 1. Ambil semua invoice PO s/d tanggal, yang invoice_no tidak kosong/null
        $this->db->select('id, invoice_no, invoice_date, nm_supplier, no_po, total_invoice');
        $this->db->from('tr_invoice_po');
        $this->db->where('DATE(invoice_date) <=', $tanggal);
        $this->db->where('invoice_no IS NOT NULL');
        $this->db->where('invoice_no <>', '');
        $this->db->order_by('nm_supplier ASC, invoice_date ASC, id ASC');
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
            // 2. Cek pembayaran: payment_approve yang id_payment-nya TIDAK kosong/null
            //    dan tr_payment_paid.tgl_bayar <= tanggal
            $this->db->select('COALESCE(SUM(pa.payment_bank), 0) AS total_bayar');
            $this->db->from('payment_approve pa');
            $this->db->join('tr_payment_paid pp', 'pp.id = pa.id_payment', 'inner');
            $this->db->where('pa.no_doc', $inv['id']);
            $this->db->where('pa.id_payment IS NOT NULL');
            $this->db->where('pa.id_payment <>', '');
            $this->db->where('pp.tgl_bayar <=', $tanggal);
            $bayar_result = $this->db->get();

            $total_bayar_sd_tgl = 0;
            if ($bayar_result) {
                $bayar_row = $bayar_result->row_array();
                $total_bayar_sd_tgl = $bayar_row ? (float)$bayar_row['total_bayar'] : 0;
            }

            // Skip invoice yang sudah lunas (sisa hutang = 0)
            $sisa_akhir = (float)$inv['total_invoice'] - $total_bayar_sd_tgl;
            if ($sisa_akhir <= 0) {
                continue;
            }

            // 3. Ambil detail pembayaran
            //    kode bayar   = payment_approve.id_payment
            //    tgl bayar    = tr_payment_paid.tgl_bayar
            //    nilai bayar  = payment_approve.payment_bank
            $this->db->select('pa.id_payment AS kd_pembayaran, pp.tgl_bayar, pa.payment_bank AS nilai_bayar');
            $this->db->from('payment_approve pa');
            $this->db->join('tr_payment_paid pp', 'pp.id = pa.id_payment', 'inner');
            $this->db->where('pa.no_doc', $inv['id']);
            $this->db->where('pa.id_payment IS NOT NULL');
            $this->db->where('pa.id_payment <>', '');
            $this->db->where('pp.tgl_bayar <=', $tanggal);
            $this->db->order_by('pp.tgl_bayar ASC, pa.id ASC');
            $pay_query = $this->db->get();

            $payments = $pay_query ? $pay_query->result_array() : [];

            if (empty($payments)) {
                // Belum ada pembayaran (id_payment kosong di payment_approve)
                $rows[] = [
                    'nm_supplier'    => $inv['nm_supplier'],
                    'tgl_invoice'    => $inv['invoice_date'],
                    'no_po'          => $inv['no_po'],
                    'id_invoice'     => $inv['invoice_no'],
                    'nilai_invoice'  => $inv['total_invoice'],
                    'kd_pembayaran'  => '',
                    'tgl_bayar'      => '',
                    'nilai_bayar'    => '',
                    'total_bayar'    => '',
                    'sisa_hutang'    => $inv['total_invoice'],
                    'is_first_row'   => true,
                    'rowspan'        => 1,
                ];
            } else {
                $running_total = 0;
                $rowspan = count($payments);

                foreach ($payments as $idx => $pay) {
                    $running_total += $pay['nilai_bayar'];
                    $sisa = $inv['total_invoice'] - $running_total;

                    $rows[] = [
                        'nm_supplier'    => $inv['nm_supplier'],
                        'tgl_invoice'    => $inv['invoice_date'],
                        'no_po'          => $inv['no_po'],
                        'id_invoice'     => $inv['invoice_no'],
                        'nilai_invoice'  => $inv['total_invoice'],
                        'kd_pembayaran'  => $pay['kd_pembayaran'],
                        'tgl_bayar'      => $pay['tgl_bayar'],
                        'nilai_bayar'    => $pay['nilai_bayar'],
                        'total_bayar'    => $running_total,
                        'sisa_hutang'    => $sisa,
                        'is_first_row'   => ($idx === 0),
                        'rowspan'        => $rowspan,
                    ];
                }
            }
        }

        return $rows;
    }
}
