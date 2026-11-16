<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_warehouse_invoiced_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ambil data report Warehouse Invoiced (barang yang sudah di-invoice).
     * Sumber: tr_invoice_sales (header) + tr_invoice_sales_detail (detail),
     * KHUSUS invoice tipe 'delivery' (berasal dari surat jalan).
     *
     * Dipakai bersama oleh tampilan layar (index) dan download Excel,
     * sehingga kolom & isinya selalu identik.
     *
     * Nilai (cost) diambil dari detail.harga_beli (HPP/costbook saat invoice dibuat).
     * Total Nilai = harga_beli * qty.
     *
     * Filter opsional: periode tanggal invoice (header.created_on).
     *
     * @param string $start_date format Y-m-d (opsional)
     * @param string $end_date   format Y-m-d (opsional)
     */
    public function get_report($start_date = '', $end_date = '')
    {
        $this->db->select('
            d.id,
            h.created_on AS tgl_invoice,
            h.id_invoice,
            d.id_delivery AS no_surat_jalan,
            h.nm_customer,
            d.id_produk AS id_product,
            d.nm_produk AS product,
            d.qty,
            d.harga_beli AS costbook,
            (d.harga_beli * d.qty) AS total_harga
        ', false);
        $this->db->from('tr_invoice_sales_detail d');
        $this->db->join('tr_invoice_sales h', 'h.id_invoice = d.id_invoice', 'left');
        $this->db->where('h.tipe_billing', 'delivery');

        // Hanya invoice aktif (yang dibatalkan / is_cancel = 1 tidak ditampilkan).
        $this->db->group_start();
        $this->db->where('h.is_cancel', null);
        $this->db->or_where('h.is_cancel', 0);
        $this->db->group_end();

        if (!empty($start_date)) {
            $this->db->where('DATE(h.created_on) >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('DATE(h.created_on) <=', $end_date);
        }

        $this->db->order_by('h.created_on', 'desc');
        $this->db->order_by('d.id', 'desc');

        return $this->db->get()->result();
    }
}
