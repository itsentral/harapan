<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Surat_jalan_model extends BF_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->ENABLE_ADD     = has_permission('Surat_Jalan.Add');
        $this->ENABLE_MANAGE  = has_permission('Surat_Jalan.Manage');
        $this->ENABLE_VIEW    = has_permission('Surat_Jalan.View');
        $this->ENABLE_DELETE  = has_permission('Surat_Jalan.Delete');
    }

    public function data_side_surat_jalan()
    {
        $requestData = $_REQUEST;

        $start_date = $this->input->post('start_date');
        $end_date   = $this->input->post('end_date');

        $fetch = $this->get_query_json_surat_jalan(
            $requestData['search']['value'],
            $requestData['order'][0]['column'],
            $requestData['order'][0]['dir'],
            $requestData['start'],
            $requestData['length'],
            $start_date,
            $end_date
        );

        $totalData     = $fetch['totalData'];
        $totalFiltered = $fetch['totalFiltered'];
        $query         = $fetch['query'];

        $data  = [];
        $urut  = 1;

        foreach ($query->result_array() as $row) {
            $nestedData = [];
            $status = '';

            // $viewBtn  = "<a href='javascript:void(0);' data-id='" . $row['no_surat_jalan'] . "' class='btn btn-sm btn-info view-sj'><i class='fa fa-eye'></i></a>";
            // $editBtn  = "<a href='" . site_url('surat_jalan/edit/' . $row['id']) . "' class='btn btn-sm btn-warning'><i class='fa fa-edit'></i></a>";
            $printBtn = "<a href='" . site_url('surat_jalan/print_sj/' . $row['id']) . "' target='_blank' class='btn btn-sm btn-warning'><i class='fa fa-print'></i></a>";
            $confimDo = "<a href='" . site_url('surat_jalan/confirm_sj/' . $row['id']) . "' class='btn btn-sm btn-info' title='Confirm Delivery'><i class='fa fa-check'></i></a>";
            $viewDo   = "<a href='" . site_url('surat_jalan/view_confirm/' . $row['id']) . "' class='btn btn-sm btn-default' title='Lihat Qty Terkirim'><i class='fa fa-eye'></i></a>";

            $action =  ($row['status'] == 'CONFIRM') || ($row['status'] == 'RETUR') || ($row['status'] == 'HILANG') ? $printBtn . ' ' . $viewDo : $printBtn . ' ' . $confimDo;

            if ($row['status'] == 'ON DELIVER') {
                $status = " <span class='badge bg-yellow'>ON DELIVERY</span>";
            } else if ($row['status'] == 'CONFIRM') {
                $status = " <span class='badge bg-green'>CONFIRM DELIVERY</span>";
            } else {
                $status = " <span class='badge bg-gray'>RETUR</span>";
            }


            $nestedData[] = "<div class='text-center'>{$urut}</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['no_surat_jalan']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['no_loading']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['no_so']) . "</div>";
            $nestedData[] = "<div>" . strtoupper($row['name_customer']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . date('d/M/Y', strtotime($row['delivery_date'])) . "</div>";
            $nestedData[] = "<div class='text-center'>" . $status . "</div>";
            $nestedData[] = "<div class='text-center'>" . $action . "</div>";

            $data[] = $nestedData;
            $urut++;
        }


        $json_data = [
            "draw"            => intval($requestData['draw']),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ];

        echo json_encode($json_data);
    }


    public function get_query_json_surat_jalan($like_value = NULL, $column_order = NULL, $column_dir = NULL, $limit_start = NULL, $limit_length = NULL, $start_date = NULL, $end_date = NULL)
    {
        $columns_order_by = [
            0 => 'sj.no_surat_jalan',
            1 => 'sj.no_surat_jalan',
            2 => 'sj.no_surat_jalan',
            3 => 'c.name_customer',
            4 => 'sj.delivery_date',
            5 => 'sj.status'
        ];

        // =============================
        // 1. Hitung totalData
        // =============================
        $this->db->from('surat_jalan sj');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');
        $this->db->where('sj.pengiriman', 'Gudang');
        $totalData = $this->db->count_all_results();

        // =============================
        // 2. Hitung totalFiltered
        // =============================
        $this->db->from('surat_jalan sj');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');
        $this->db->where('sj.pengiriman', 'Gudang');

        if (!empty($start_date)) $this->db->where('sj.delivery_date >=', $start_date);
        if (!empty($end_date)) $this->db->where('sj.delivery_date <=', $end_date);

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('sj.no_surat_jalan', $like_value);
            $this->db->or_like('c.name_customer', $like_value);
            $this->db->group_end();
        }

        $totalFiltered = $this->db->count_all_results();

        // =============================
        // 3. Ambil data paginasi
        // =============================
        $this->db->select('sj.id, sj.no_surat_jalan, sj.no_loading, sj.no_so, sj.delivery_date, sj.status, c.name_customer');
        $this->db->from('surat_jalan sj');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');
        $this->db->where('sj.pengiriman', 'Gudang');

        if (!empty($start_date)) $this->db->where('sj.delivery_date >=', $start_date);
        if (!empty($end_date)) $this->db->where('sj.delivery_date <=', $end_date);

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('sj.no_surat_jalan', $like_value);
            $this->db->or_like('sj.no_loading', $like_value);
            $this->db->or_like('sj.no_so', $like_value);
            $this->db->or_like('c.name_customer', $like_value);
            $this->db->group_end();
        }

        if (isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('sj.created_at', 'desc');
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

    public function data_side_retur()
    {
        $requestData = $_REQUEST;

        $fetch = $this->get_query_json_retur(
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
        $urut  = 1;

        foreach ($query->result_array() as $row) {
            $nestedData = [];
            $status = '';

            // $viewBtn  = "<a href='javascript:void(0);' data-id='" . $row['no_surat_jalan'] . "' class='btn btn-sm btn-info view-sj'><i class='fa fa-eye'></i></a>";
            // $editBtn  = "<a href='" . site_url('surat_jalan/edit/' . $row['id']) . "' class='btn btn-sm btn-warning'><i class='fa fa-edit'></i></a>";
            $createSpk = "<a href='" . site_url('surat_jalan/confirm_sj/' . $row['id']) . "' class='btn btn-sm btn-success' title='Confirm Delivery'><i class='fa fa-check-square-o'></i></a>";

            $action =  ($row['status'] == 'RETUR') ? $createSpk : '';

            if ($row['status'] == 'ON DELIVER') {
                $status = " <span class='badge bg-yellow'>ON DELIVERY</span>";
            } else if ($row['status'] == 'CONFIRM') {
                $status = " <span class='badge bg-green'>CONFIRM DELIVERY</span>";
            } else {
                $status = " <span class='badge bg-gray'>RETUR</span>";
            }


            $nestedData[] = "<div class='text-center'>{$urut}</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['no_surat_jalan']) . "</div>";
            $nestedData[] = "<div>" . strtoupper($row['name_customer']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . date('d/M/Y', strtotime($row['delivery_date'])) . "</div>";
            $nestedData[] = "<div class='text-center'>" . $row['qty_retur'] . "</div>";
            // 🔽 Tambahkan kolom file bukti
            if (!empty($row['file_bukti'])) {
                $url = base_url('uploads/confirm_sj/' . $row['file_bukti']);
                $fileLink = "<a href='{$url}' target='_blank' class='btn btn-xs btn-info'><i class='fa fa-file'></i> Lihat File</a>";
            } else {
                $fileLink = "<span class='text-muted'>-</span>";
            }
            $nestedData[] = "<div class='text-center'>{$fileLink}</div>";
            $nestedData[] = "<div class='text-center'>" . $action . "</div>";

            $data[] = $nestedData;
            $urut++;
        }


        $json_data = [
            "draw"            => intval($requestData['draw']),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ];

        echo json_encode($json_data);
    }


    public function get_query_json_retur($like_value = NULL, $column_order = NULL, $column_dir = NULL, $limit_start = NULL, $limit_length = NULL)
    {
        $columns_order_by = [
            0 => 'sj.no_surat_jalan',
            1 => 'c.name_customer',
            2 => 'sj.delivery_date',
            3 => 'sjd.product',
            4 => 'sjd.qty_retur'
        ];

        // =============================
        // 1. Hitung totalData
        // =============================
        $this->db->from('surat_jalan_detail sjd');
        $this->db->join('surat_jalan sj', 'sjd.id_sj = sj.id', 'left');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');
        $this->db->where('sj.pengiriman', 'Gudang');
        $this->db->where('sjd.qty_retur >', 0);
        $totalData = $this->db->count_all_results();

        // =============================
        // 2. Hitung totalFiltered
        // =============================
        $this->db->from('surat_jalan_detail sjd');
        $this->db->join('surat_jalan sj', 'sjd.id_sj = sj.id', 'left');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');
        $this->db->where('sj.pengiriman', 'Gudang');
        $this->db->where('sjd.qty_retur >', 0);

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('sj.no_surat_jalan', $like_value);
            $this->db->or_like('c.name_customer', $like_value);
            $this->db->or_like('sjd.product', $like_value);
            $this->db->group_end();
        }

        $totalFiltered = $this->db->count_all_results();

        // =============================
        // 3. Ambil data paginasi
        // =============================
        $this->db->select('
        sjd.id,
        sj.no_surat_jalan,
        sj.delivery_date,
        sj.status,
        c.name_customer,
        sjd.product,
        sjd.qty_retur,
        sjd.file_bukti,
    ');
        $this->db->from('surat_jalan_detail sjd');
        $this->db->join('surat_jalan sj', 'sjd.id_sj = sj.id', 'left');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');
        $this->db->where('sj.pengiriman', 'Gudang');
        $this->db->where('sjd.qty_retur >', 0);

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('sj.no_surat_jalan', $like_value);
            $this->db->or_like('c.name_customer', $like_value);
            $this->db->or_like('sjd.product', $like_value);
            $this->db->group_end();
        }

        if (isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('sj.created_at', 'desc');
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

    public function data_side_hilang()
    {
        $requestData = $_REQUEST;

        $fetch = $this->get_query_json_hilang(
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
        $urut  = 1;

        foreach ($query->result_array() as $row) {
            $nestedData = [];
            $status = '';

            // $viewBtn  = "<a href='javascript:void(0);' data-id='" . $row['no_surat_jalan'] . "' class='btn btn-sm btn-info view-sj'><i class='fa fa-eye'></i></a>";
            // $editBtn  = "<a href='" . site_url('surat_jalan/edit/' . $row['id']) . "' class='btn btn-sm btn-warning'><i class='fa fa-edit'></i></a>";
            $createSpk = "<a href='" . site_url('surat_jalan/confirm_sj/' . $row['id']) . "' class='btn btn-sm btn-success' title='Confirm Delivery'><i class='fa fa-check-square-o'></i></a>";

            $action =  ($row['status'] == 'RETUR') ? $createSpk : '';

            if ($row['status'] == 'ON DELIVER') {
                $status = " <span class='badge bg-yellow'>ON DELIVERY</span>";
            } else if ($row['status'] == 'CONFIRM') {
                $status = " <span class='badge bg-green'>CONFIRM DELIVERY</span>";
            } else {
                $status = " <span class='badge bg-gray'>RETUR</span>";
            }


            $nestedData[] = "<div class='text-center'>{$urut}</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['no_surat_jalan']) . "</div>";
            $nestedData[] = "<div>" . strtoupper($row['name_customer']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . date('d/M/Y', strtotime($row['delivery_date'])) . "</div>";
            $nestedData[] = "<div class='text-center'>" . $row['qty_retur'] . "</div>";
            // 🔽 Tambahkan kolom file bukti
            // if (!empty($row['file_bukti'])) {
            //     $url = base_url('uploads/confirm_sj/' . $row['file_bukti']);
            //     $fileLink = "<a href='{$url}' target='_blank' class='btn btn-xs btn-info'><i class='fa fa-file'></i> Lihat File</a>";
            // } else {
            //     $fileLink = "<span class='text-muted'>-</span>";
            // }
            // $nestedData[] = "<div class='text-center'>{$fileLink}</div>";
            $nestedData[] = "<div class='text-center'>" . $action . "</div>";

            $data[] = $nestedData;
            $urut++;
        }


        $json_data = [
            "draw"            => intval($requestData['draw']),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ];

        echo json_encode($json_data);
    }


    public function get_query_json_hilang($like_value = NULL, $column_order = NULL, $column_dir = NULL, $limit_start = NULL, $limit_length = NULL)
    {
        $columns_order_by = [
            0 => 'sj.no_surat_jalan',
            1 => 'c.name_customer',
            2 => 'sj.delivery_date',
            3 => 'sjd.product',
            4 => 'sjd.qty_hilang'
        ];

        // =============================
        // 1. Hitung totalData
        // =============================
        $this->db->from('surat_jalan_detail sjd');
        $this->db->join('surat_jalan sj', 'sjd.id_sj = sj.id', 'left');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');
        $this->db->where('sj.pengiriman', 'Gudang');
        $this->db->where('sjd.qty_hilang >', 0);
        $totalData = $this->db->count_all_results();

        // =============================
        // 2. Hitung totalFiltered
        // =============================
        $this->db->from('surat_jalan_detail sjd');
        $this->db->join('surat_jalan sj', 'sjd.id_sj = sj.id', 'left');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');
        $this->db->where('sj.pengiriman', 'Gudang');
        $this->db->where('sjd.qty_hilang >', 0);

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('sj.no_surat_jalan', $like_value);
            $this->db->or_like('c.name_customer', $like_value);
            $this->db->or_like('sjd.product', $like_value);
            $this->db->group_end();
        }

        $totalFiltered = $this->db->count_all_results();

        // =============================
        // 3. Ambil data paginasi
        // =============================
        $this->db->select('
        sjd.id,
        sj.no_surat_jalan,
        sj.delivery_date,
        sj.status,
        c.name_customer,
        sjd.product,
        sjd.qty_hilang,
        sjd.file_bukti,
    ');
        $this->db->from('surat_jalan_detail sjd');
        $this->db->join('surat_jalan sj', 'sjd.id_sj = sj.id', 'left');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');
        $this->db->where('sj.pengiriman', 'Gudang');
        $this->db->where('sjd.qty_hilang >', 0);

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('sj.no_surat_jalan', $like_value);
            $this->db->or_like('c.name_customer', $like_value);
            $this->db->or_like('sjd.product', $like_value);
            $this->db->group_end();
        }

        if (isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('sj.created_at', 'desc');
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
