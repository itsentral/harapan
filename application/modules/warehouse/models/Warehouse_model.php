<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is model class for table "Warehouse"
 */

class Warehouse_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();

        $this->ENABLE_ADD     = has_permission('Warehouse.Add');
        $this->ENABLE_MANAGE  = has_permission('Warehouse.Manage');
        $this->ENABLE_VIEW    = has_permission('Warehouse.View');
        $this->ENABLE_DELETE  = has_permission('Warehouse.Delete');
    }

    // list data
    public function GetListWarehouse()
    {
        $this->db->select('a.*');
        $this->db->from($this->table_name . ' a');
        $this->db->order_by('a.id', 'asc');
        $query = $this->db->get();
        if ($query->num_rows() != 0) {
            return $query->result();
        } else {
            return false;
        }
    }

    // get data
    public function GetDataWarehouse($id)
    {
        $this->db->select('a.*');
        $this->db->from($this->table_name . ' a');
        $this->db->where('a.id', $id);
        $query = $this->db->get();
        if ($query->num_rows() != 0) {
            return $query->row();
        } else {
            return false;
        }
    }

    //server side
    public function get_json_warehouse_stock()
    {
        $requestData = $_REQUEST;

        $fetch = $this->get_query_json_warehouse_stock(
            $requestData['search']['value'],
            $requestData['order'][0]['column'],
            $requestData['order'][0]['dir'],
            $requestData['start'],
            $requestData['length']
        );

        $totalData = $fetch['totalData'];
        $totalFiltered = $fetch['totalFiltered'];
        $query = $fetch['query'];

        $data = [];
        $urut1 = 1;

        foreach ($query->result_array() as $row) {
            $nomor = $urut1 + $requestData['start'];
            $qty_stock = isset($row['qty_stock']) ? floatval($row['qty_stock']) : 0;
            $qty_booking = isset($row['qty_booking']) ? floatval($row['qty_booking']) : 0;
            $available = $qty_stock - $qty_booking;

            $nestedData = [];
            $nestedData[] = "<div align='center'>{$nomor}</div>";
            $nestedData[] = "<div align='left'>{$row['code_product']}</div>";
            $nestedData[] = "<div align='left'>{$row['nm_product']}</div>";
            $nestedData[] = "<div align='center'>{$row['kd_gudang']}</div>";
            $nestedData[] = "<div align='center'>{$row['unit']}</div>";
            $nestedData[] = "<div align='center'>{$row['unit_packing']}</div>";
            $nestedData[] = "<div align='right'>" . number_format($qty_stock) . "</div>";
            $nestedData[] = "<div align='right'>" . number_format($qty_booking) . "</div>";
            $nestedData[] = "<div align='right'>" . number_format($available) . "</div>";

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

    public function get_query_json_warehouse_stock($like_value = null, $column_order = null, $column_dir = null, $limit_start = null, $limit_length = null)
    {
        $columns_order_by = [
            0 => 'ws.id',
            1 => 'ws.code_product',
            2 => 'ws.nm_product',
            3 => 'ws.kd_gudang',
            4 => 'sp.nama',
            5 => 'sm.nama',
            6 => 'ws.qty_stock',
            7 => 'ws.qty_booking'
        ];

        // Total Data
        $this->db->select('ws.id');
        $this->db->from('warehouse_stock ws');
        $this->db->join('ms_satuan sm', 'ws.id_unit = sm.id', 'left');
        $this->db->join('ms_satuan sp', 'ws.id_unit_packing = sp.id', 'left');
        $this->db->join('new_inventory_4 ni', 'ni.code_lv4 = ws.code_lv4', 'inner');
        $this->db->where('ni.deleted_date IS NULL', null, false);
        $this->db->where('ni.deleted_by IS NULL',  null, false);
        $totalData = $this->db->count_all_results();

        // Total Filtered
        $this->db->select('ws.id');
        $this->db->from('warehouse_stock ws');
        $this->db->join('ms_satuan sm', 'ws.id_unit = sm.id', 'left');
        $this->db->join('ms_satuan sp', 'ws.id_unit_packing = sp.id', 'left');
        $this->db->join('new_inventory_4 ni', 'ni.code_lv4 = ws.code_lv4', 'inner');
        $this->db->where('ni.deleted_date IS NULL', null, false);
        $this->db->where('ni.deleted_by IS NULL',  null, false);

        if ($like_value) {
            $this->db->group_start();
            $this->db->like('ws.code_product', $like_value);
            $this->db->or_like('ws.nm_product', $like_value);
            $this->db->or_like('ws.kd_gudang', $like_value);
            $this->db->or_like('sm.nama', $like_value);
            $this->db->or_like('sp.nama', $like_value);
            $this->db->group_end();
        }

        $totalFiltered = $this->db->count_all_results();

        // Data utama
        $this->db->select('
                            ws.*,
                            sm.nama AS unit,
                            sp.nama AS unit_packing
                        ');
        $this->db->from('warehouse_stock ws');
        $this->db->join('ms_satuan sm', 'ws.id_unit = sm.id', 'left');
        $this->db->join('ms_satuan sp', 'ws.id_unit_packing = sp.id', 'left');
        $this->db->join('new_inventory_4 ni', 'ni.code_lv4 = ws.code_lv4', 'inner');
        $this->db->where('ni.deleted_date IS NULL', null, false);
        $this->db->where('ni.deleted_by IS NULL',  null, false);

        if ($like_value) {
            $this->db->group_start();
            $this->db->like('ws.code_product', $like_value);
            $this->db->or_like('ws.nm_product', $like_value);
            $this->db->or_like('ws.kd_gudang', $like_value);
            $this->db->or_like('sp.nama', $like_value);
            $this->db->or_like('sm.nama', $like_value);
            $this->db->group_end();
        }

        if ($column_order !== null && isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('ws.id', 'desc');
        }

        if ($limit_length != -1) {
            $this->db->limit($limit_length, $limit_start);
        }

        $query = $this->db->get();

        return [
            'totalData' => $totalData,
            'totalFiltered' => $totalFiltered,
            'query' => $query
        ];
    }

    public function get_json_kartu_stok()
    {
        $requestData = $_REQUEST;

        $fetch = $this->get_query_json_kartu_stok(
            $requestData['search']['value'],
            $requestData['order'][0]['column'],
            $requestData['order'][0]['dir'],
            $requestData['start'],
            $requestData['length'],
            isset($requestData['start_date']) ? $requestData['start_date'] : null,
            isset($requestData['end_date']) ? $requestData['end_date'] : null
        );

        $totalData = $fetch['totalData'];
        $totalFiltered = $fetch['totalFiltered'];
        $query = $fetch['query'];

        $is_admin = $this->auth->is_admin();

        $data = [];
        $urut1 = 1;

        foreach ($query->result_array() as $row) {
            $nomor = $urut1 + $requestData['start'];
            $nestedData = [];

            $nestedData[] = "<div align='center'>{$nomor}</div>";
            $nestedData[] = date('d/M/Y', strtotime($row['tgl_transaksi']));
            $nestedData[] = $row['no_transaksi'];
            $nestedData[] = $row['transaksi'];
            $nestedData[] = $row['code_lv4'];
            $nestedData[] = $row['nm_product'];
            $nestedData[] = number_format($row['qty']);                  // AWAL: stock
            $nestedData[] = number_format($row['qty_book']);             // AWAL: booking
            $nestedData[] = number_format($row['qty_free']);             // AWAL: free stock
            $nestedData[] = number_format($row['qty_transaksi']);        // TRANSAKSI: in/out
            $nestedData[] = number_format($row['qty_book_akhir'] - $row['qty_book']); // TRANSAKSI: delta booking
            $nestedData[] = number_format($row['qty_akhir']);            // AKHIR: stock
            $nestedData[] = number_format($row['qty_book_akhir']);       // AKHIR: booking
            $nestedData[] = number_format($row['qty_free_akhir']);       // AKHIR: free stock

            // Harga Beli hanya untuk admin
            if ($is_admin) {
                $harga_stok = isset($row['harga_stok']) ? floatval($row['harga_stok']) : 0;
                $nestedData[] = "<div align='right'>" . number_format($harga_stok) . "</div>";
            }

            $data[] = $nestedData;
            $urut1++;
        }

        $json_data = [
            "draw" => intval($requestData['draw']),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data
        ];

        echo json_encode($json_data);
    }


    public function get_query_json_kartu_stok($like_value = null, $column_order = null, $column_dir = null, $limit_start = null, $limit_length = null, $start_date = null, $end_date = null)
    {
        $columns_order_by = [
            0 => 'ks.id',
            1 => 'ks.tgl_transaksi',
            2 => 'ks.no_transaksi',
            3 => 'ks.transaksi',
            4 => 'ks.code_lv4',
            5 => 'ks.nm_product',
            6 => 'ks.qty',
            7 => 'ks.qty_book',
            8 => 'ks.qty_free',
            9 => 'ks.qty_transaksi',
            10 => 'ks.qty_book_akhir',
            11 => 'ks.qty_akhir',
            12 => 'ks.qty_book_akhir',
            13 => 'ks.qty_free_akhir'
        ];

        $this->db->select('ks.id');
        $this->db->from('kartu_stok ks');
        $this->db->where('ks.deleted', null);
        if (!empty($start_date)) $this->db->where('DATE(ks.tgl_transaksi) >=', $start_date);
        if (!empty($end_date))   $this->db->where('DATE(ks.tgl_transaksi) <=', $end_date);
        $this->db->order_by('ks.id', 'desc');
        $totalData = $this->db->count_all_results();

        $this->db->select('ks.id');
        $this->db->from('kartu_stok ks');
        $this->db->where('ks.deleted', null);
        if (!empty($start_date)) $this->db->where('DATE(ks.tgl_transaksi) >=', $start_date);
        if (!empty($end_date))   $this->db->where('DATE(ks.tgl_transaksi) <=', $end_date);
        $this->db->order_by('ks.id', 'desc');

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('ks.code_lv4', $like_value);
            $this->db->or_like('ks.nm_product', $like_value);
            $this->db->or_like('ks.no_transaksi', $like_value);
            $this->db->or_like('ks.transaksi', $like_value);
            $this->db->group_end();
        }

        $totalFiltered = $this->db->count_all_results();

        $this->db->select('
        ks.*
    ');
        $this->db->from('kartu_stok ks');
        $this->db->where('ks.deleted', null);
        if (!empty($start_date)) $this->db->where('DATE(ks.tgl_transaksi) >=', $start_date);
        if (!empty($end_date))   $this->db->where('DATE(ks.tgl_transaksi) <=', $end_date);
        $this->db->order_by('ks.id', 'desc');

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('ks.code_lv4', $like_value);
            $this->db->or_like('ks.nm_product', $like_value);
            $this->db->or_like('ks.no_transaksi', $like_value);
            $this->db->or_like('ks.transaksi', $like_value);
            $this->db->group_end();
        }

        if ($column_order !== null && isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('ks.id', 'desc');
        }

        if ($limit_length != -1) {
            $this->db->limit($limit_length, $limit_start);
        }

        $query = $this->db->get();

        return [
            'totalData' => $totalData,
            'totalFiltered' => $totalFiltered,
            'query' => $query
        ];
    }
}
