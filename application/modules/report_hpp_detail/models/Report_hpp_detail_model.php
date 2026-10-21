<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_hpp_detail_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ambil data Report HPP Detail.
     *
     * Dipakai bersama oleh tampilan layar (index) dan download Excel,
     * sehingga kolom & isinya selalu identik.
     *
     * Tujuan report: mengetahui HPP (costbook) tiap produk per invoice.
     *   - Kolom selain Tgl Dibuat & Tgl Kirim diambil dari tr_invoice_sales_detail.
     *   - Tgl Dibuat (created_on) & Tgl Kirim (delivery_date) diambil dari tr_invoice_sales.
     *   - Total = Qty x Costbook (harga_beli) = nilai HPP per baris.
     *
     * Invoice yang dibatalkan dikecualikan: is_cancel 1 (full cancel) maupun 2/3
     * (cancel sebagian) tidak dihitung; hanya is_cancel = 0 atau NULL yang masuk.
     * Hanya baris dengan qty > 0 yang ditampilkan.
     *
     * @param string $start_date  format Y-m-d (opsional)
     * @param string $end_date    format Y-m-d (opsional)
     * @param string $date_basis  'created' (Tgl Dibuat) atau 'delivery' (Tgl Kirim)
     */
    public function get_report_hpp_detail($start_date = '', $end_date = '', $date_basis = 'created')
    {
        // Tentukan kolom tanggal yang dipakai untuk filter periode.
        $date_col = ($date_basis === 'delivery') ? 'i.delivery_date' : 'i.created_on';

        $this->db->select('
            dt.id_invoice,
            i.created_on,
            i.delivery_date,
            dt.id_delivery,
            dt.id_so,
            dt.id_produk,
            dt.nm_produk,
            dt.qty,
            dt.harga_beli,
            (dt.qty * IFNULL(dt.harga_beli, 0)) AS total_hpp
        ', false);
        $this->db->from('tr_invoice_sales_detail dt');
        $this->db->join('tr_invoice_sales i', 'i.id_invoice = dt.id_invoice', 'inner');

        // Hanya invoice yang benar-benar tidak dibatalkan: is_cancel = 0 atau NULL.
        // Nilai 1 = full cancel (credit note), 2/3 = cancel sebagian — semuanya
        // dikecualikan agar HPP yang dihitung hanya atas penjualan yang sah.
        $this->db->where('IFNULL(i.is_cancel, 0) =', 0, false);
        $this->db->where('IFNULL(dt.qty, 0) >', 0, false);

        if (!empty($start_date)) {
            $this->db->where("DATE($date_col) >=", $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where("DATE($date_col) <=", $end_date);
        }

        // Urutkan agar baris satu invoice berdekatan (untuk grouping Sub Total).
        $this->db->order_by("$date_col", 'asc');
        $this->db->order_by('dt.id_invoice', 'asc');
        $this->db->order_by('dt.id', 'asc');

        return $this->db->get()->result();
    }
}
