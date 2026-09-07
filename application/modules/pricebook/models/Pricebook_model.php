<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Pricebook_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ENABLE_ADD    = has_permission('Pricebook.Add');
        $this->ENABLE_MANAGE = has_permission('Pricebook.Manage');
        $this->ENABLE_VIEW   = has_permission('Pricebook.View');
        $this->ENABLE_DELETE = has_permission('Pricebook.Delete');
    }

    /**
     * Build the JSON response for DataTables.
     */
    public function get_json_pricebook()
    {
        $requestData = $_REQUEST;
        $tanggal = isset($requestData['tanggal']) ? trim($requestData['tanggal']) : null;

        $fetch = $this->get_query_json_pricebook(
            $requestData['search']['value'],
            $requestData['order'][0]['column'],
            $requestData['order'][0]['dir'],
            $requestData['start'],
            $requestData['length'],
            $tanggal
        );

        $totalData     = $fetch['totalData'];
        $totalFiltered = $fetch['totalFiltered'];
        $query         = $fetch['query'];
        $result_data   = $query->result_array();

        $data = [];
        $urut1 = 1;
        $urut2 = 0;

        foreach ($result_data as $row) {
            $total_data = $totalData;
            $start_dari = $requestData['start'];
            $asc_desc   = $requestData['order'][0]['dir'];
            $nomor = ($asc_desc == 'asc')
                ? ($total_data - $start_dari) - $urut2
                : $urut1 + $start_dari;

            $costbook = isset($row['costbook']) ? $row['costbook'] : 0;

            $nestedData = [];
            $nestedData[] = "<div align='center'>{$nomor}</div>";
            $nestedData[] = "<div align='center'>{$row['id_material']}</div>";
            $nestedData[] = "<div align='left'>{$row['nm_product']}</div>";
            $nestedData[] = "<div align='right'>" . number_format($costbook, 0, ',', '.') . "</div>";

            $data[] = $nestedData;
            $urut1++;
            $urut2++;
        }

        $json_data = [
            "draw"            => intval($requestData['draw']),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ];

        echo json_encode($json_data);
    }

    /**
     * Query pricebook.
     *
     * Sumber costbook tergantung filter tanggal:
     * - Tanggal kosong  -> harga sekarang dari warehouse_stock
     * - Tanggal terisi  -> harga lalu dari warehouse_stock_per_days (filter tgl_backup)
     */
    public function get_query_json_pricebook(
        $like_value = null,
        $column_order = null,
        $column_dir = null,
        $limit_start = null,
        $limit_length = null,
        $tanggal = null
    ) {
        $has_tanggal = ($tanggal !== null && $tanggal !== '');

        if ($has_tanggal) {
            // Harga lalu dari warehouse_stock_per_days
            $table   = 'warehouse_stock_per_days t';
            $tgl_esc = $this->db->escape_like_str($tanggal);
        } else {
            // Harga sekarang dari warehouse_stock
            $table = 'warehouse_stock t';
        }

        $columns_order_by = [
            0 => 't.id_material',
            1 => 't.id_material',
            2 => 't.nm_product',
            3 => 'costbook',
        ];

        // ---- total data
        $this->db->select('t.id_material');
        $this->db->from($table);
        if ($has_tanggal) {
            $this->db->like('t.tgl_backup', $tanggal);
        }
        $this->db->group_by('t.id_material');
        $totalData = count($this->db->get()->result_array());

        // ---- total filtered
        $this->db->select('t.id_material');
        $this->db->from($table);
        if ($has_tanggal) {
            $this->db->like('t.tgl_backup', $tanggal);
        }
        if ($like_value) {
            $this->db->group_start();
            $this->db->like('t.id_material', $like_value);
            $this->db->or_like('t.nm_product', $like_value);
            $this->db->group_end();
        }
        $this->db->group_by('t.id_material');
        $totalFiltered = count($this->db->get()->result_array());

        // ---- main query
        $this->db->select('t.id_material, t.nm_product, MAX(t.harga_beli) AS costbook');
        $this->db->from($table);
        if ($has_tanggal) {
            $this->db->like('t.tgl_backup', $tanggal);
        }
        if ($like_value) {
            $this->db->group_start();
            $this->db->like('t.id_material', $like_value);
            $this->db->or_like('t.nm_product', $like_value);
            $this->db->group_end();
        }
        $this->db->group_by('t.id_material, t.nm_product');

        if ($column_order !== null && isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('t.nm_product', 'asc');
        }
        if ($limit_length != -1) {
            $this->db->limit($limit_length, $limit_start);
        }
        $query = $this->db->get();

        return [
            'totalData'     => $totalData,
            'totalFiltered' => $totalFiltered,
            'query'         => $query
        ];
    }
}
