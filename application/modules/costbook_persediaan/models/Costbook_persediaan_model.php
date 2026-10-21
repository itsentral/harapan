<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Costbook_persediaan_model extends BF_Model
{
    public function get_json_costbook()
    {
        $requestData = $_REQUEST;

        $fetch = $this->get_query_json_costbook(
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
            $nomor    = $urut1 + $requestData['start'];
            $costbook = isset($row['harga_beli']) ? floatval($row['harga_beli']) : 0;

            $updated_at = (!empty($row['update_date']) && $row['update_date'] !== '0000-00-00 00:00:00')
                ? date('d/M/Y H:i', strtotime($row['update_date']))
                : '-';

            $nestedData   = [];
            $nestedData[] = "<div align='center'>{$nomor}</div>";
            $nestedData[] = "<div align='left'>{$row['code_product']}</div>";
            $nestedData[] = "<div align='left'>{$row['nm_product']}</div>";
            $nestedData[] = "<div align='center'>{$row['kd_gudang']}</div>";
            $nestedData[] = "<div align='center'>{$row['unit_packing']}</div>";
            $nestedData[] = "<div align='right'>" . number_format($costbook, 2) . "</div>";
            $nestedData[] = "<div align='center'>{$updated_at}</div>";

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

    public function get_query_json_costbook($like_value = null, $column_order = null, $column_dir = null, $limit_start = null, $limit_length = null)
    {
        $columns_order_by = [
            0 => 'ws.id',
            1 => 'ws.code_product',
            2 => 'ws.nm_product',
            3 => 'ws.kd_gudang',
            4 => 'sm.nama',
            5 => 'ws.harga_beli',
            6 => 'ws.update_date'
        ];

        // Total Data
        $this->db->select('ws.id');
        $this->db->from('warehouse_stock ws');
        $this->db->join('ms_satuan sm', 'ws.id_unit = sm.id', 'left');
        $this->db->join('new_inventory_4 ni', 'ni.code_lv4 = ws.code_lv4', 'inner');
        $this->db->where('ni.deleted_date IS NULL', null, false);
        $this->db->where('ni.deleted_by IS NULL',  null, false);
        $totalData = $this->db->count_all_results();

        // Total Filtered
        $this->db->select('ws.id');
        $this->db->from('warehouse_stock ws');
        $this->db->join('ms_satuan sm', 'ws.id_unit = sm.id', 'left');
        $this->db->join('new_inventory_4 ni', 'ni.code_lv4 = ws.code_lv4', 'inner');
        $this->db->where('ni.deleted_date IS NULL', null, false);
        $this->db->where('ni.deleted_by IS NULL',  null, false);

        if ($like_value) {
            $this->db->group_start();
            $this->db->like('ws.code_product', $like_value);
            $this->db->or_like('ws.nm_product', $like_value);
            $this->db->or_like('ws.kd_gudang', $like_value);
            $this->db->or_like('sm.nama', $like_value);
            $this->db->group_end();
        }

        $totalFiltered = $this->db->count_all_results();

        // Data utama
        $this->db->select('
                            ws.*,
                            sm.nama AS unit_packing
                        ');
        $this->db->from('warehouse_stock ws');
        $this->db->join('ms_satuan sm', 'ws.id_unit = sm.id', 'left');
        $this->db->join('new_inventory_4 ni', 'ni.code_lv4 = ws.code_lv4', 'inner');
        $this->db->where('ni.deleted_date IS NULL', null, false);
        $this->db->where('ni.deleted_by IS NULL',  null, false);

        if ($like_value) {
            $this->db->group_start();
            $this->db->like('ws.code_product', $like_value);
            $this->db->or_like('ws.nm_product', $like_value);
            $this->db->or_like('ws.kd_gudang', $like_value);
            $this->db->or_like('sm.nama', $like_value);
            $this->db->group_end();
        }

        if ($column_order !== null && isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('ws.code_product', 'asc');
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

/* End of file Costbook_persediaan_model.php */
