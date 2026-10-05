<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_piutang_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Rekap piutang PER CUSTOMER s/d tanggal cut-off.
     * Dibuat agar bisa dibandingkan (compare) dengan laporan Kartu Piutang
     * yang menampilkan Saldo Akhir per customer.
     *
     * Untuk tiap customer:
     * - total_invoice : SUM grand_total semua invoice s/d tanggal (is_cancel NULL)
     * - total_bayar   : SUM pelunasan (bank + CN + pembulatan) s/d tanggal
     * - sisa_piutang  : total_invoice - total_bayar  (= Saldo Akhir)
     *
     * @param  string $tanggal Format Y-m-d
     * @return array
     */
    public function get_piutang_per_customer($tanggal)
    {
        // 1. Total invoice per customer s/d tanggal
        $this->db->select('id_customer, nm_customer');
        $this->db->select('COALESCE(SUM(grand_total), 0) AS total_invoice', false);
        $this->db->from('tr_invoice_sales');
        $this->db->where('DATE(created_on) <=', $tanggal);
        $this->db->where('is_cancel', null);
        $this->db->group_by('id_customer, nm_customer');
        $inv_q = $this->db->get();
        $invoices = $inv_q ? $inv_q->result_array() : [];

        if (empty($invoices)) {
            return ['rows' => [], 'total_piutang' => 0];
        }

        // 2. Total pelunasan per customer s/d tanggal.
        //    Join detail -> payment (header) -> invoice (ambil id_customer).
        $this->db->select('i.id_customer', false);
        $this->db->select('COALESCE(SUM(d.total_bayar_idr), 0) AS total_bayar', false);
        $this->db->select('COALESCE(SUM(d.total_cn_idr), 0) AS total_cn', false);
        $this->db->select('COALESCE(SUM(d.pembulatan_idr), 0) AS total_pembulatan', false);
        $this->db->from('tr_invoice_payment_detail d');
        $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = d.kd_pembayaran', 'inner');
        $this->db->join('tr_invoice_sales i', 'i.id_invoice = d.no_invoice', 'inner');
        $this->db->where('p.tgl_pembayaran <=', $tanggal);
        $this->db->where('i.is_cancel', null);
        $this->db->group_by('i.id_customer');
        $pay_q = $this->db->get();

        $bayar_map = [];
        if ($pay_q) {
            foreach ($pay_q->result_array() as $r) {
                $bayar_map[$r['id_customer']] = (float)$r['total_bayar']
                    + (float)$r['total_cn']
                    + (float)$r['total_pembulatan'];
            }
        }

        // 3. Gabungkan: sisa = total_invoice - total_bayar
        $rows = [];
        $total_piutang = 0;
        foreach ($invoices as $inv) {
            $total_invoice = (float)$inv['total_invoice'];
            $total_bayar   = isset($bayar_map[$inv['id_customer']]) ? $bayar_map[$inv['id_customer']] : 0;
            $sisa          = $total_invoice - $total_bayar;

            $rows[] = [
                'id_customer'   => $inv['id_customer'],
                'name_customer' => $inv['nm_customer'],
                'total_invoice' => $total_invoice,
                'total_bayar'   => $total_bayar,
                'sisa_piutang'  => $sisa,
            ];

            $total_piutang += $sisa;
        }

        // Urutkan by customer
        usort($rows, function ($a, $b) {
            return strcasecmp($a['name_customer'], $b['name_customer']);
        });

        return [
            'rows'          => $rows,
            'total_piutang' => $total_piutang,
        ];
    }

    /**
     * Ringkasan (summary) piutang PER INVOICE: 1 baris per invoice.
     * Kolom: Customer, Tanggal Invoice, No Invoice, Nilai Invoice,
     *        Total Bayar, Sisa Piutang.
     *
     * Memakai logika yang sama dengan get_piutang_per_invoice(), lalu
     * mengambil hanya baris terakhir tiap invoice (sisa_piutang terkini).
     *
     * @param  string $tanggal Format Y-m-d
     * @return array
     */
    public function get_piutang_per_invoice_summary($tanggal)
    {
        $rows = $this->_build_report_rows($tanggal);

        $summary = [];
        $by_invoice = [];

        // Kelompokkan baris per invoice (urutan sudah terjaga dari _build_report_rows)
        foreach ($rows as $r) {
            $id = $r['id_invoice'];
            if (!isset($by_invoice[$id])) {
                $by_invoice[$id] = [
                    'name_customer' => $r['name_customer'],
                    'tgl_invoice'   => $r['tgl_invoice'],
                    'id_invoice'    => $r['id_invoice'],
                    'nilai_invoice' => (float)$r['nilai_invoice'],
                    'total_bayar'   => 0,
                    'sisa_piutang'  => (float)$r['nilai_invoice'],
                ];
            }
            // Total bayar = running total terakhir; sisa = sisa baris terakhir
            $by_invoice[$id]['total_bayar']  = ($r['total_bayar'] !== '' ? (float)$r['total_bayar'] : 0);
            $by_invoice[$id]['sisa_piutang'] = (float)$r['sisa_piutang'];
        }

        $total_piutang = 0;
        foreach ($by_invoice as $inv) {
            $summary[] = $inv;
            $total_piutang += (float)$inv['sisa_piutang'];
        }

        return [
            'rows'          => $summary,
            'total_piutang' => $total_piutang,
        ];
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
        $total_saldo_piutang = 0;
        $last_invoice = null;
        $last_sisa = 0;
        $last_saldo = 0;

        foreach ($rows as $r) {
            if ($r['is_first_row'] && $last_invoice !== null) {
                $total_piutang += $last_sisa;
                $total_saldo_piutang += $last_saldo;
            }
            $last_invoice = $r['id_invoice'];
            $last_sisa = (float)$r['sisa_piutang'];
            $last_saldo = (float)$r['saldo_piutang'];
        }
        // Tambahkan invoice terakhir
        if ($last_invoice !== null) {
            $total_piutang += $last_sisa;
            $total_saldo_piutang += $last_saldo;
        }

        return [
            'rows'                => $rows,
            'total_piutang'       => $total_piutang,
            'total_saldo_piutang' => $total_saldo_piutang,
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
            $this->db->select('p.id, p.kd_pembayaran, p.tgl_pembayaran, p.created_on AS tgl_dibuat, d.total_bayar_idr AS nilai_bayar, d.total_cn_idr AS nilai_cn, d.pembulatan_idr AS nilai_pembulatan, d.sisa_invoice_idr AS sisa');
            $this->db->from('tr_invoice_payment_detail d');
            $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = d.kd_pembayaran', 'inner');
            $this->db->where('d.no_invoice', $inv['id_invoice']);
            $this->db->where('p.tgl_pembayaran <=', $tanggal);
            // Urut berdasarkan tanggal dibuat (created_on), lalu id (auto increment)
            // sebagai tie-breaker agar urutan pembayaran yang tgl-nya sama tidak terbolak-balik.
            $this->db->order_by('p.created_on ASC, p.id ASC');
            $pay_query = $this->db->get();

            $payments = $pay_query ? $pay_query->result_array() : [];

            // Saldo cut off: ambil sisa invoice dari pembayaran dengan
            // tgl_pembayaran TERBESAR yang <= tanggal cut off (tie-breaker id
            // terbesar). Ini independen dari urutan tampilan (sort by created_on),
            // sehingga saldo tetap mengikuti tanggal pembayaran, bukan urutan baris.
            $saldo_cutoff = (float)$inv['nilai_invoice'];
            if (!empty($payments)) {
                $saldo_pay = null;
                foreach ($payments as $pp) {
                    if ($saldo_pay === null) {
                        $saldo_pay = $pp;
                        continue;
                    }
                    $cur_ts  = strtotime($pp['tgl_pembayaran']);
                    $best_ts = strtotime($saldo_pay['tgl_pembayaran']);
                    if ($cur_ts > $best_ts
                        || ($cur_ts === $best_ts && (int)$pp['id'] > (int)$saldo_pay['id'])) {
                        $saldo_pay = $pp;
                    }
                }
                if ($saldo_pay !== null) {
                    $saldo_cutoff = (float)$saldo_pay['sisa'];
                }
            }

            if (empty($payments)) {
                // Invoice belum ada pembayaran sama sekali
                $rows[] = [
                    'name_customer'  => $inv['nm_customer'],
                    'tgl_invoice'    => $inv['tgl_invoice'],
                    'id_invoice'     => $inv['id_invoice'],
                    'nilai_invoice'  => $inv['nilai_invoice'],
                    'kd_pembayaran'  => '',
                    'tgl_bayar'      => '',
                    'tgl_dibuat'     => '',
                    'nilai_bayar'    => '',
                    'total_bayar'    => '',
                    'sisa_piutang'   => $inv['nilai_invoice'],
                    'saldo_piutang'  => $inv['nilai_invoice'],
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

                    // Saldo piutang = saldo cut off invoice ini, yaitu sisa
                    // invoice pada pembayaran dengan tgl_pembayaran terbesar
                    // (<= tanggal cut off). Nilai sama di semua baris invoice.
                    $saldo_piutang = $saldo_cutoff;

                    $rows[] = [
                        'name_customer'  => $inv['nm_customer'],
                        'tgl_invoice'    => $inv['tgl_invoice'],
                        'id_invoice'     => $inv['id_invoice'],
                        'nilai_invoice'  => $inv['nilai_invoice'],
                        'kd_pembayaran'  => $pay['kd_pembayaran'],
                        'tgl_bayar'      => $pay['tgl_pembayaran'],
                        'tgl_dibuat'     => $pay['tgl_dibuat'],
                        'nilai_bayar'    => $pay['nilai_bayar'],
                        'total_bayar'    => $running_total,
                        'sisa_piutang'   => $sisa,
                        'saldo_piutang'  => $saldo_piutang,
                        'is_first_row'   => ($idx === 0),
                        'rowspan'        => $rowspan,
                    ];
                }
            }
        }

        return $rows;
    }
}
