<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_inventory_model extends BF_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->ENABLE_ADD     = has_permission('Report_Inventory.Add');
        $this->ENABLE_MANAGE  = has_permission('Report_Inventory.Manage');
        $this->ENABLE_VIEW    = has_permission('Report_Inventory.View');
        $this->ENABLE_DELETE  = has_permission('Report_Inventory.Delete');
    }

    public function get_json_stock()
    {
        $requestData = $_REQUEST;
        $tanggal = isset($requestData['tanggal']) ? trim($requestData['tanggal']) : null;

        $fetch = $this->get_query_json_stock(
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
            $asc_desc = $requestData['order'][0]['dir'];
            $nomor = ($asc_desc == 'asc')
                ? ($total_data - $start_dari) - $urut2
                : $urut1 + $start_dari;

            $nestedData = [];
            $nestedData[] = "<div align='center'>{$nomor}</div>";
            $nestedData[] = "<div align='center'>{$row['id_material']}</div>";
            $nestedData[] = "<div align='center'>{$row['code_product']}</div>";
            $nestedData[] = "<div align='left'>{$row['nm_product']}</div>";
            $nestedData[] = "<div align='right'>" . number_format($row['qty_stock'], 0, ',', '.') . "</div>";
            $nestedData[] = "<div align='right'>" . number_format($row['qty_booking'], 0, ',', '.') . "</div>";
            $nestedData[] = "<div align='right'>" . number_format($row['qty_free'], 0, ',', '.') . "</div>";
            $nestedData[] = "<div align='center'>{$row['nm_gudang']}</div>";
            $nestedData[] = "<div align='center'>" . date('d/m/Y', strtotime($row['tgl_backup'])) . "</div>";
            $nestedData[] = "<div align='right'>" . number_format($row['harga_beli'], 0, ',', '.') . "</div>";
            $nestedData[] = "<div align='right'>" . number_format($row['total_nilai'], 0, ',', '.') . "</div>";

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

    public function get_query_json_stock(
        $like_value = null,
        $column_order = null,
        $column_dir = null,
        $limit_start = null,
        $limit_length = null,
        $tanggal = null
    ) {
        $columns_order_by = [
            0 => 's.id',
            1 => 's.code_product',
            2 => 's.nm_product',
            3 => 's.qty_stock',
            4 => 's.qty_booking',
            5 => 's.qty_free',
            6 => 'w.nm_gudang',
            7 => 's.tgl_backup',
            8 => 's.harga_beli',
            9 => 's.total_nilai',
        ];

        // ---- total data
        $this->db->from('warehouse_stock_per_days s');
        $this->db->join('warehouse w', 's.id_gudang = w.id', 'left');
        if ($tanggal !== null && $tanggal !== '') {
            $this->db->like('s.tgl_backup', $tanggal);
        } else {
            $this->db->where('0=1', null, false); // <<— paksa kosong ketika tanggal kosong
        }
        $totalData = $this->db->count_all_results();

        // ---- total filtered
        $this->db->from('warehouse_stock_per_days s');
        $this->db->join('warehouse w', 's.id_gudang = w.id', 'left');
        if ($tanggal !== null && $tanggal !== '') {
            $this->db->like('s.tgl_backup', $tanggal);
        } else {
            $this->db->where('0=1', null, false);
        }
        if ($like_value) {
            $this->db->group_start();
            $this->db->like('s.code_product', $like_value);
            $this->db->or_like('s.nm_product', $like_value);
            $this->db->or_like('w.nm_gudang', $like_value);
            $this->db->group_end();
        }
        $totalFiltered = $this->db->count_all_results();

        // ---- main query
        $this->db->select('s.id, s.id_material, s.code_product, s.nm_product, s.qty_stock, s.qty_booking,
                       s.qty_free, w.nm_gudang, s.tgl_backup, s.harga_beli, s.total_nilai');
        $this->db->from('warehouse_stock_per_days s');
        $this->db->join('warehouse w', 's.id_gudang = w.id', 'left');
        if ($tanggal !== null && $tanggal !== '') {
            $this->db->like('s.tgl_backup', $tanggal);
        } else {
            $this->db->where('0=1', null, false);
        }
        if ($like_value) {
            $this->db->group_start();
            $this->db->like('s.code_product', $like_value);
            $this->db->or_like('s.nm_product', $like_value);
            $this->db->or_like('w.nm_gudang', $like_value);
            $this->db->group_end();
        }
        if ($column_order !== null && isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('s.tgl_backup', 'desc');
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

    public function get_data_stock_for_excel($tanggal)
    {
        if (empty($tanggal)) return [];

        $this->db->select('
            s.id_material,
            s.code_product,
            s.nm_product,
            s.qty_stock,
            s.qty_booking,
            s.qty_free,
            w.nm_gudang,
            s.tgl_backup,
            s.harga_beli,
            s.total_nilai
        ');
        $this->db->from('warehouse_stock_per_days s');
        $this->db->join('warehouse w', 's.id_gudang = w.id', 'left');
        $this->db->like('s.tgl_backup', $tanggal);
        // Samakan urutan dengan tampilan (DataTables default: kolom Code Product desc)
        $this->db->order_by('s.code_product', 'desc');
        return $this->db->get()->result_array();
    }
}
