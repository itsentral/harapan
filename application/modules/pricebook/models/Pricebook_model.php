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

            $harga_sekarang = isset($row['harga_sekarang']) ? $row['harga_sekarang'] : 0;
            $harga_lalu     = isset($row['harga_lalu']) ? $row['harga_lalu'] : 0;
            $selisih        = $harga_sekarang - $harga_lalu;

            $nestedData = [];
            $nestedData[] = "<div align='center'>{$nomor}</div>";
            $nestedData[] = "<div align='center'>{$row['id_material']}</div>";
            $nestedData[] = "<div align='left'>{$row['nm_product']}</div>";
            $nestedData[] = "<div align='right'>" . number_format($harga_lalu, 0, ',', '.') . "</div>";
            $nestedData[] = "<div align='right'>" . number_format($harga_sekarang, 0, ',', '.') . "</div>";
            $nestedData[] = "<div align='right'>" . number_format($selisih, 0, ',', '.') . "</div>";

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
     * Base table   : warehouse_stock (ws)  -> harga sekarang
     * Joined table : warehouse_stock_per_days (wp) filter by tgl_backup -> harga lalu
     */
    public function get_query_json_pricebook(
        $like_value = null,
        $column_order = null,
        $column_dir = null,
        $limit_start = null,
        $limit_length = null,
        $tanggal = null
    ) {
        $columns_order_by = [
            0 => 'ws.id_material',
            1 => 'ws.id_material',
            2 => 'ws.nm_product',
            3 => 'harga_lalu',
            4 => 'harga_sekarang',
        ];

        // Subquery harga lalu berdasarkan tgl_backup (ambil per id_material)
        $tgl_backup = ($tanggal !== null && $tanggal !== '') ? $this->db->escape_like_str($tanggal) : '';
        $join_lalu = '(SELECT p.id_material, MAX(p.harga_beli) AS harga_lalu
                       FROM warehouse_stock_per_days p
                       WHERE p.tgl_backup LIKE "%' . $tgl_backup . '%"
                       GROUP BY p.id_material) wp';

        // ---- total data
        $this->db->select('ws.id_material');
        $this->db->from('warehouse_stock ws');
        $this->db->group_by('ws.id_material');
        $totalData = $this->db->count_all_results();

        // ---- total filtered
        $this->db->select('ws.id_material');
        $this->db->from('warehouse_stock ws');
        if ($like_value) {
            $this->db->group_start();
            $this->db->like('ws.id_material', $like_value);
            $this->db->or_like('ws.nm_product', $like_value);
            $this->db->group_end();
        }
        $this->db->group_by('ws.id_material');
        $totalFiltered = count($this->db->get()->result_array());

        // ---- main query
        $this->db->select('ws.id_material, ws.nm_product,
                           MAX(ws.harga_beli) AS harga_sekarang,
                           MAX(wp.harga_lalu) AS harga_lalu');
        $this->db->from('warehouse_stock ws');
        $this->db->join($join_lalu, 'wp.id_material = ws.id_material', 'left');
        if ($like_value) {
            $this->db->group_start();
            $this->db->like('ws.id_material', $like_value);
            $this->db->or_like('ws.nm_product', $like_value);
            $this->db->group_end();
        }
        $this->db->group_by('ws.id_material, ws.nm_product');

        if ($column_order !== null && isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('ws.nm_product', 'asc');
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
