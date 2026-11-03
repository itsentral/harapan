<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_warehouse_intransit_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ambil data report Warehouse Stock In-Transit.
     * Dipakai bersama oleh tampilan layar (index) dan download Excel,
     * sehingga kolom & isinya selalu identik.
     *
     * Sumber data: tabel warehouse_stock_intransit, di-join ke master_customers
     * (lewat surat_jalan -> sales_order) untuk menampilkan nama customer.
     *
     * Filter opsional: periode tgl_masuk (dari & sampai).
     *
     * @param string $start_date format Y-m-d (opsional)
     * @param string $end_date   format Y-m-d (opsional)
     */
    public function get_report($start_date = '', $end_date = '')
    {
        $this->db->select('
            t.id,
            t.tgl_masuk,
            t.no_so,
            t.no_surat_jalan,
            t.id_product,
            t.product,
            t.qty,
            t.costbook,
            t.total_harga,
            t.created_at,
            c.name_customer
        ', false);
        $this->db->from('warehouse_stock_intransit t');
        $this->db->join('sales_order so', 't.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');

        if (!empty($start_date)) {
            $this->db->where('t.tgl_masuk >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('t.tgl_masuk <=', $end_date);
        }

        $this->db->order_by('t.tgl_masuk', 'desc');
        $this->db->order_by('t.id', 'desc');

        return $this->db->get()->result();
    }
}
