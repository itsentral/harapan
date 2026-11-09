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
            t.no_surat_jalan,
            t.id_product,
            t.product,
            t.qty,
            t.costbook,
            t.total_harga,
            t.created_at,
            sj.created_at AS tgl_surat_jalan,
            c.name_customer
        ', false);
        $this->db->from('warehouse_stock_intransit t');
        $this->db->join('surat_jalan sj', 't.id_sj = sj.id', 'left');
        $this->db->join('sales_order so', 't.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');

        // Filter periode berdasarkan tanggal surat jalan dibuat (created_at).
        if (!empty($start_date)) {
            $this->db->where('DATE(sj.created_at) >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('DATE(sj.created_at) <=', $end_date);
        }

        $this->db->order_by('sj.created_at', 'desc');
        $this->db->order_by('t.id', 'desc');

        return $this->db->get()->result();
    }
}
