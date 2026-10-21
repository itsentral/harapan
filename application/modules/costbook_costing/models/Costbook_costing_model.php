<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Costbook_costing_model extends BF_Model
{
    protected $status_map = [
        'WA' => 'Waiting Approval',
        'A'  => 'Approved',
        'R'  => 'Rejected',
    ];

    public function get_json_costbook_costing()
    {
        $requestData = $_REQUEST;

        $fetch = $this->get_query_json_costbook_costing(
            $requestData['search']['value'],
            $requestData['order'][0]['column'],
            $requestData['order'][0]['dir'],
            $requestData['start'],
            $requestData['length']
        );

        $totalData     = $fetch['totalData'];
        $totalFiltered = $fetch['totalFiltered'];
        $query         = $fetch['query'];

        $data  = [];
        $urut1 = 1;

        foreach ($query->result_array() as $row) {
            $nomor = $urut1 + $requestData['start'];

            $status_label = isset($this->status_map[$row['status']])
                ? $this->status_map[$row['status']]
                : $row['status'];

            $modified_at = (!empty($row['modified_at']) && $row['modified_at'] !== '0000-00-00 00:00:00')
                ? date('d/M/Y H:i', strtotime($row['modified_at']))
                : '-';

            $nestedData   = [];
            $nestedData[] = "<div align='center'>{$nomor}</div>";
            $nestedData[] = "<div align='left'>{$row['id']}</div>";
            $nestedData[] = "<div align='left'>{$row['code_lv4']}</div>";
            $nestedData[] = "<div align='left'>{$row['product_name']}</div>";
            $nestedData[] = "<div align='right'>" . number_format((float)$row['harga_beli'], 2) . "</div>";
            $nestedData[] = "<div align='center'>{$status_label}</div>";
            $nestedData[] = "<div align='center'>{$modified_at}</div>";

            $data[] = $nestedData;
            $urut1++;
        }

        $json_data = [
            "draw"            => intval($requestData['draw']),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ];

        echo json_encode($json_data);
    }

    public function get_query_json_costbook_costing($like_value = null, $column_order = null, $column_dir = null, $limit_start = null, $limit_length = null)
    {
        $columns_order_by = [
            0 => 'pc.id',
            1 => 'pc.id',
            2 => 'pc.code_lv4',
            3 => 'pc.product_name',
            4 => 'pc.harga_beli',
            5 => 'pc.status',
            6 => 'pc.modified_at',
        ];

        // Total Data
        $this->db->from('product_costing pc');
        $totalData = $this->db->count_all_results();

        // Total Filtered
        $this->db->from('product_costing pc');
        if ($like_value) {
            $this->db->group_start();
            $this->db->like('pc.id', $like_value);
            $this->db->or_like('pc.code_lv4', $like_value);
            $this->db->or_like('pc.product_name', $like_value);
            $this->db->group_end();
        }
        $totalFiltered = $this->db->count_all_results();

        // Data utama
        $this->db->select('
            pc.id,
            pc.code_lv4,
            pc.product_name,
            pc.harga_beli,
            pc.status,
            pc.modified_at
        ');
        $this->db->from('product_costing pc');

        if ($like_value) {
            $this->db->group_start();
            $this->db->like('pc.id', $like_value);
            $this->db->or_like('pc.code_lv4', $like_value);
            $this->db->or_like('pc.product_name', $like_value);
            $this->db->group_end();
        }

        if ($column_order !== null && isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('pc.id', 'asc');
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

/* End of file Costbook_costing_model.php */
