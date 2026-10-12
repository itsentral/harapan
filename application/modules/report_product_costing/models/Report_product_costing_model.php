<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_product_costing_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Ambil data report Product Costing.
     * Dipakai bersama oleh tampilan layar (index) dan download Excel,
     * sehingga kolom & isinya selalu identik.
     *
     * @param string $start_date  format Y-m-d (opsional)
     * @param string $end_date    format Y-m-d (opsional)
     * @param string $status      A | WA | R | '' (semua)
     * @param string $date_field  created_at | modified_at (basis filter periode)
     */
    public function get_report_product_costing($start_date = '', $end_date = '', $status = '', $date_field = 'created_at')
    {
        // Amankan pilihan kolom tanggal agar tidak bisa diinjeksi
        $date_field = ($date_field === 'modified_at') ? 'modified_at' : 'created_at';

        $this->db->select('
            pc.id,
            pc.code_lv4,
            pc.product_name,
            pc.harga_beli,
            pc.status,
            pc.revisi,
            pc.created_at,
            pc.modified_at,
            ni1.nama AS product_type,
            ni4.nama AS variant_name,
            uc.nm_lengkap AS created_by_name,
            um.nm_lengkap AS modified_by_name
        ');
        $this->db->from('product_costing pc');
        $this->db->join('new_inventory_1 ni1', 'pc.code_lv1 = ni1.code_lv1', 'left');
        $this->db->join('new_inventory_4 ni4', 'pc.code_lv4 = ni4.code_lv4', 'left');
        $this->db->join('users uc', 'uc.id_user = pc.created_by', 'left');
        $this->db->join('users um', 'um.id_user = pc.modified_by', 'left');

        if (!empty($status)) {
            $this->db->where('pc.status', $status);
        }
        if (!empty($start_date)) {
            $this->db->where('DATE(pc.' . $date_field . ') >=', $start_date);
        }
        if (!empty($end_date)) {
            $this->db->where('DATE(pc.' . $date_field . ') <=', $end_date);
        }

        $this->db->order_by('pc.id', 'asc');

        return $this->db->get()->result();
    }
}
