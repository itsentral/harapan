<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_warehouse_mutasi_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ambil data report Mutasi Warehouse Stock dari ledger warehouse_stock_mutasi.
     * Dipakai bersama oleh tampilan layar (index) dan download Excel,
     * sehingga kolom & isinya selalu identik.
     *
     * Filter opsional:
     *   - periode tgl_event (dari & sampai)
     *   - event (CREATE / UPDATE / CONFIRM / INVOICE)
     *
     * @param string $start_date format Y-m-d (opsional)
     * @param string $end_date   format Y-m-d (opsional)
     * @param string $event      salah satu kode event (opsional)
     */
    public function get_report($start_date = '', $end_date = '', $event = '')
    {
        $this->db->select('
            t.id,
            t.tgl_event,
            t.event,
            t.dari_tahap,
            t.ke_tahap,
            t.no_so,
            t.no_surat_jalan,
            t.id_product,
            t.product,
            t.qty,
            t.costbook,
            t.total_harga,
            t.ref_no,
            c.name_customer
        ', false);
        $this->db->from('warehouse_stock_mutasi t');
        $this->db->join('sales_order so', 't.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');

        if (!empty($start_date)) {
            $this->db->where('DATE(t.tgl_event) >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('DATE(t.tgl_event) <=', $end_date);
        }
        if (!empty($event)) {
            $this->db->where('t.event', $event);
        }

        $this->db->order_by('t.tgl_event', 'desc');
        $this->db->order_by('t.id', 'desc');

        return $this->db->get()->result();
    }
}
