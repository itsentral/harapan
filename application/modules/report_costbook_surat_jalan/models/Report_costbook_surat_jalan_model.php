<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_costbook_surat_jalan_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ambil data report Costbook Surat Jalan dari surat_jalan_detail.
     * Dipakai bersama oleh tampilan layar (index) dan download Excel,
     * sehingga kolom & isinya selalu identik.
     *
     * Report ini KHUSUS menampilkan surat jalan yang sudah dibuat tetapi
     * BELUM di-confirm, yaitu status = 'ON DELIVER'.
     * Filter opsional: periode created_at (dari & sampai).
     *
     * @param string $start_date format Y-m-d (opsional)
     * @param string $end_date   format Y-m-d (opsional)
     */
    public function get_report_costbook_surat_jalan($start_date = '', $end_date = '')
    {
        $this->db->select('
            sjd.id,
            sj.no_surat_jalan,
            sj.no_so,
            sj.delivery_date,
            sj.created_at,
            sj.status,
            c.name_customer,
            sjd.id_product,
            sjd.product,
            sjd.qty,
            sjd.weight,
            sjd.total_berat,
            sjd.qty_terkirim,
            sjd.qty_retur,
            sjd.qty_hilang,
            sjd.qty_lebih,
            sjd.costbook
        ', false);
        $this->db->from('surat_jalan_detail sjd');
        $this->db->join('surat_jalan sj', 'sjd.id_sj = sj.id', 'left');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');

        // Hanya SJ yang sudah dibuat tapi belum confirm.
        $this->db->where('sj.status', 'ON DELIVER');

        if (!empty($start_date)) {
            $this->db->where('DATE(sj.created_at) >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('DATE(sj.created_at) <=', $end_date);
        }

        $this->db->order_by('sj.created_at', 'desc');

        return $this->db->get()->result();
    }
}
