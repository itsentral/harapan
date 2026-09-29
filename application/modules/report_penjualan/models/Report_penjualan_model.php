<?php

use Mpdf\Tag\P;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_penjualan_model extends BF_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->ENABLE_ADD     = has_permission('Report_Penjualan.Add');
        $this->ENABLE_MANAGE  = has_permission('Report_Penjualan.Manage');
        $this->ENABLE_VIEW    = has_permission('Report_Penjualan.View');
        $this->ENABLE_DELETE  = has_permission('Report_Penjualan.Delete');
    }

    // =============================
    // Report Seluruh Penjualan
    // =============================
    public function data_side_report()
    {
        $requestData = $_REQUEST;

        $tgl_dari   = $requestData['tgl_dari'] ?? null;   // format: YYYY-MM-DD
        $tgl_sampai = $requestData['tgl_sampai'] ?? null;
        $id_sales   = $requestData['id_sales'] ?? null;

        $fetch = $this->get_query_json_report(
            $requestData['search']['value'],
            $requestData['order'][0]['column'],
            $requestData['order'][0]['dir'],
            $requestData['start'],
            $requestData['length'],
            $tgl_dari,
            $tgl_sampai,
            $id_sales,
        );

        $totalData     = $fetch['totalData'];
        $totalFiltered = $fetch['totalFiltered'];
        $query         = $fetch['query'];

        $data  = [];
        $urut = intval($requestData['start']) + 1;

        foreach ($query->result_array() as $row) {
            $nestedData = [];
            $status = '';

            if ($row['is_cancel'] == 1) {
                $status = "<span class='badge bg-red'>Credit Note</span>";
            } else {
                if ($row['sts'] == 1) {
                    $status = "<span class='badge bg-yellow'>Belum Lunas</span>";
                } else {
                    $status = " <span class='badge bg-green'>Lunas</span>";
                }
            }

            $nestedData[] = "<div class='text-center'>{$urut}</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['id_invoice']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . (($row['created_on'] != null) ? date('d/M/Y', strtotime($row['created_on'])) : '') . "</div>";
            $nestedData[] = "<div>" . strtoupper($row['nm_customer']) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($row['total']) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($row['total_bayar']) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($row['piutang']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . number_format($row['umur']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . $status . "</div>";

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

    public function get_query_json_report($like_value = NULL, $column_order = NULL, $column_dir = NULL, $limit_start = NULL, $limit_length = NULL, $tgl_dari = NULL, $tgl_sampai = NULL, $id_sales = NULL)
    {
        $columns_order_by = [
            0 => 'i.id_invoice',
            1 => 'i.created_on',
            2 => 'i.nm_customer',
        ];

        // Helper filter (Tanggal & Sales)
        $apply_filters = function () use ($tgl_dari, $tgl_sampai, $id_sales) {
            // Filter Tanggal
            if (!empty($tgl_dari) && !empty($tgl_sampai)) {
                $this->db->where('DATE(i.created_on) >=', $tgl_dari);
                $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
            }

            // Filter Sales (Join ke master customer)
            if (!empty($id_sales)) {
                $this->db->join('master_customers c', 'i.id_customer = c.id_customer');
                $this->db->where('c.id_karyawan', $id_sales);
            }
        };

        $select = 'i.id_invoice, i.created_on, i.grand_total as total, i.total_bayar, i.piutang, i.jatuh_tempo, i.id_customer, i.nm_customer, i.sts, i.is_cancel,
               DATEDIFF(i.jatuh_tempo, DATE(i.created_on)) AS umur';

        // 1) totalData (opsional: mau dihitung setelah filter tanggal atau tidak)
        $this->db->select($select)->from('tr_invoice_sales i');
        $apply_filters();                 // <-- kalau totalData ikut filter periode
        $this->db->group_by('i.id_invoice');
        $totalData = $this->db->count_all_results();

        // 2) totalFiltered
        $this->db->select($select)->from('tr_invoice_sales i');
        $apply_filters();                 // <-- penting
        $this->db->group_by('i.id_invoice');

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('i.id_invoice', $like_value);
            $this->db->or_like('i.nm_customer', $like_value);
            $this->db->group_end();
        }
        $totalFiltered = $this->db->count_all_results();

        // 3) data
        $this->db->select($select)->from('tr_invoice_sales i');
        $apply_filters();                 // <-- penting
        $this->db->group_by('i.id_invoice');

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('i.id_invoice', $like_value);
            $this->db->or_like('i.nm_customer', $like_value);
            $this->db->group_end();
        }

        if (isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('i.created_on', 'desc');
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

    public function get_export_report($like_value = NULL, $tgl_dari = NULL, $tgl_sampai = NULL, $id_sales = NULL)
    {
        $this->db->select('
        i.id_invoice,
        i.created_on,
        i.grand_total as total,
        i.total_bayar,
        i.piutang,
        i.jatuh_tempo,
        i.nm_customer,
        i.sts,
        i.is_cancel,
        DATEDIFF(i.jatuh_tempo, DATE(i.created_on)) AS umur
    ');
        $this->db->from('tr_invoice_sales i');

        // Filter Sales (Join ke master customer)
        if (!empty($id_sales)) {
            $this->db->join('master_customers c', 'i.id_customer = c.id_customer');
            $this->db->where('c.id_karyawan', $id_sales);
        }

        // Filter tanggal (created_on)
        if (!empty($tgl_dari)) {
            $this->db->where('DATE(i.created_on) >=', $tgl_dari);
        }
        if (!empty($tgl_sampai)) {
            $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
        }

        // Search
        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('i.id_invoice', $like_value);
            $this->db->or_like('i.nm_customer', $like_value);
            $this->db->group_end();
        }

        $this->db->group_by('i.id_invoice');
        $this->db->order_by('i.created_on', 'desc');

        return $this->db->get()->result();
    }

    // =============================
    // Report Penjualan Per Customer
    // =============================
    public function data_side_customer()
    {
        $requestData = $_REQUEST;

        $tgl_dari   = $requestData['tgl_dari'] ?? null;   // format: YYYY-MM-DD
        $tgl_sampai = $requestData['tgl_sampai'] ?? null; // format: YYYY-MM-DD
        $id_sales = $requestData['id_sales'] ?? null; // format: YYYY-MM-DD

        $fetch = $this->get_query_json_customer(
            $requestData['search']['value'],
            $requestData['order'][0]['column'],
            $requestData['order'][0]['dir'],
            $requestData['start'],
            $requestData['length'],
            $tgl_dari,
            $tgl_sampai,
            $id_sales
        );

        $totalData     = $fetch['totalData'];
        $totalFiltered = $fetch['totalFiltered'];
        $query         = $fetch['query'];

        $data  = [];
        $urut = intval($requestData['start']) + 1;

        foreach ($query->result_array() as $row) {
            $nestedData = [];
            $id_customer = $row['id_customer'];
            $nm_customer = $row['nm_customer'];

            $nestedData[] = "<div class='text-center'>{$urut}</div>";
            $nestedData[] = "<div>" . strtoupper($row['nm_customer']) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($row['total_invoice']) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($row['total_bayar']) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($row['total_piutang']) . "</div>";
            $nestedData[] = "<a href='javascript:void(0)' class='btn btn-sm btn-warning view-detail' data-name='{$nm_customer}' data-customer='{$id_customer}'><i class='fa fa-eye'></i> View</a>";

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

    public function get_query_json_customer($like_value = NULL, $column_order = NULL, $column_dir = NULL, $limit_start = NULL, $limit_length = NULL, $tgl_dari = NULL, $tgl_sampai = NULL, $id_sales = NULL)
    {
        $columns_order_by = [
            0 => 'i.nm_customer',
            1 => 'total_invoice',
        ];

        // Helper filter (Tanggal & Sales)
        $apply_filters = function () use ($tgl_dari, $tgl_sampai, $id_sales) {
            // Filter Tanggal
            if (!empty($tgl_dari) && !empty($tgl_sampai)) {
                $this->db->where('DATE(i.created_on) >=', $tgl_dari);
                $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
            }

            // Filter Sales (Join ke master customer)
            if (!empty($id_sales)) {
                $this->db->join('master_customers c', 'i.id_customer = c.id_customer');
                $this->db->where('c.id_karyawan', $id_sales);
            }
        };

        $select = '
        i.id_invoice,
        i.id_customer,
        i.nm_customer,
        SUM(i.grand_total) AS total_invoice,
        SUM(i.total_bayar) AS total_bayar,
        SUM(i.piutang) AS total_piutang,
        i.created_on
    ';

        // =============================
        // 1) totalData (jumlah customer yang muncul)
        // =============================
        $this->db->select('i.id_customer');
        $this->db->from('tr_invoice_sales i');
        $this->db->where('i.is_cancel', null);
        $apply_filters();
        $this->db->group_by('i.id_customer');
        $totalData = $this->db->count_all_results();

        // =============================
        // 2) totalFiltered
        // =============================
        $this->db->select('i.id_customer');
        $this->db->from('tr_invoice_sales i');
        $this->db->where('i.is_cancel', null);
        $apply_filters();

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('i.nm_customer', $like_value);
            $this->db->group_end();
        }

        $this->db->group_by('i.id_customer');
        $totalFiltered = $this->db->count_all_results();

        // =============================
        // 3) data paginasi
        // =============================
        $this->db->select($select);
        $this->db->from('tr_invoice_sales i');
        $this->db->where('i.is_cancel', null);
        $apply_filters();

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('i.nm_customer', $like_value);
            $this->db->group_end();
        }

        $this->db->group_by('i.id_customer, i.nm_customer');

        if (isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('i.nm_customer', 'asc');
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

    public function get_detail_inv_by_customer($id_customer)
    {
        $sql = "SELECT 
                inv.id_invoice,
                inv.created_on AS tgl_invoice,
                inv.grand_total,

                pay.kd_pembayaran,
                p.tgl_pembayaran AS tgl_bayar,
                pay.total_bayar_idr AS nilai_bayar,

                pay.sisa_invoice_idr AS sisa_piutang

            FROM tr_invoice_sales inv

            LEFT JOIN tr_invoice_payment_detail pay 
                ON pay.no_invoice = inv.id_invoice

            LEFT JOIN tr_invoice_payment p 
                ON p.kd_pembayaran = pay.kd_pembayaran

            WHERE inv.id_customer = ?

            ORDER BY inv.id_invoice, p.tgl_pembayaran ASC, pay.id_payment_detail ASC";

        return $this->db->query($sql, [$id_customer])->result();
    }

    public function get_export_customer($like_value = NULL, $tgl_dari = NULL, $tgl_sampai = NULL, $id_sales = NULL)
    {
        $this->db->select('
        i.id_customer,
        i.nm_customer,
        SUM(i.grand_total) AS total_invoice,
        i.created_on
    ');
        $this->db->from('tr_invoice_sales i');
        $this->db->where('i.is_cancel', null);
        $this->db->group_by('i.id_customer');

        // Filter Sales (Join ke master customer)
        if (!empty($id_sales)) {
            $this->db->join('master_customers c', 'i.id_customer = c.id_customer');
            $this->db->where('c.id_karyawan', $id_sales);
        }

        // filter tanggal (created_on)
        if (!empty($tgl_dari)) {
            $this->db->where('DATE(i.created_on) >=', $tgl_dari);
        }
        if (!empty($tgl_sampai)) {
            $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
        }

        // search
        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('i.id_invoice', $like_value);
            $this->db->or_like('i.nm_customer', $like_value);
            $this->db->group_end();
        }

        $this->db->order_by('i.created_on', 'desc');
        return $this->db->get()->result();
    }

    // =============================
    // Report Penjualan Per Produk
    // =============================
    public function data_side_product()
    {
        $requestData = $_REQUEST;

        $tgl_dari   = $requestData['tgl_dari'] ?? null;   // format: YYYY-MM-DD
        $tgl_sampai = $requestData['tgl_sampai'] ?? null; // format: YYYY-MM-DD

        $fetch = $this->get_query_json_product(
            $requestData['search']['value'],
            $requestData['order'][0]['column'],
            $requestData['order'][0]['dir'],
            $requestData['start'],
            $requestData['length'],
            $tgl_dari,
            $tgl_sampai
        );

        $totalData     = $fetch['totalData'];
        $totalFiltered = $fetch['totalFiltered'];
        $query         = $fetch['query'];

        $data  = [];
        $urut = intval($requestData['start']) + 1;

        foreach ($query->result_array() as $row) {
            $nestedData = [];

            $nestedData[] = "<div class='text-center'>{$urut}</div>";
            $nestedData[] = "<div>" . $row['nama_barang'] . "</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['satuan']) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($row['qty_total']) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($row['penjualan_total']) . "</div>";

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

    public function get_query_json_product($like_value = NULL, $column_order = NULL, $column_dir = NULL, $limit_start = NULL, $limit_length = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        // Kolom urutan DataTables: sesuaikan dengan tabel kamu (No, Nama Barang, Satuan, Kuantitas, Penjualan)
        $columns_order_by = [
            0 => 'd.nm_produk',
            1 => 'd.uom',
            2 => 'qty_total',
            3 => 'penjualan_total',
        ];

        $apply_date_filter = function () use ($tgl_dari, $tgl_sampai) {
            if (!empty($tgl_dari) && !empty($tgl_sampai)) {
                $this->db->where('DATE(i.created_on) >=', $tgl_dari);
                $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
            } elseif (!empty($tgl_dari)) {
                $this->db->where('DATE(i.created_on) >=', $tgl_dari);
            } elseif (!empty($tgl_sampai)) {
                $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
            }
        };

        $apply_search_filter = function () use ($like_value) {
            if (!empty($like_value)) {
                $this->db->group_start();
                $this->db->like('d.nm_produk', $like_value);
                $this->db->or_like('d.uom', $like_value);
                $this->db->group_end();
            }
        };

        // =============================
        // 1) totalData (jumlah group produk+satuan)
        // =============================
        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner'); // <-- sesuaikan kalau key beda
        $this->db->where('i.is_cancel', NULL); // IS NULL
        $apply_date_filter();

        // count group unik produk+satuan (pakai id_produk jika ada, fallback nm_produk)
        $this->db->select("COUNT(DISTINCT CONCAT(IFNULL(d.id_produk,''),'|',IFNULL(d.uom,''))) AS total", false);
        $totalData = (int) $this->db->get()->row()->total;

        // =============================
        // 2) totalFiltered
        // =============================
        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner');
        $this->db->where('i.is_cancel', NULL);
        $apply_date_filter();
        $apply_search_filter();

        $this->db->select("COUNT(DISTINCT CONCAT(IFNULL(d.id_produk,''),'|',IFNULL(d.uom,''))) AS total", false);
        $totalFiltered = (int) $this->db->get()->row()->total;

        // =============================
        // 3) data paginasi (group by produk+satuan)
        // =============================
        $this->db->select("
        d.nm_produk AS nama_barang,
        d.uom AS satuan,
        SUM(d.qty) AS qty_total,
        SUM(d.subtotal) AS penjualan_total
    ", false);

        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner');
        $this->db->where('i.is_cancel', NULL);
        $apply_date_filter();
        $apply_search_filter();

        $this->db->group_by('d.nm_produk, d.uom');

        if (isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('d.nm_produk', 'asc');
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

    public function get_export_product($like_value = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        // NOTE: rumus & grouping harus identik dengan get_query_json_product()
        // supaya angka di grid (index) dan di export Excel selalu sama.
        $this->db->select("
        d.nm_produk AS nama_barang,
        d.uom AS satuan,
        SUM(d.qty) AS qty_total,
        SUM(d.subtotal) AS penjualan_total
    ", false);
        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner');
        $this->db->where('i.is_cancel', null);

        // filter tanggal (created_on)
        if (!empty($tgl_dari)) {
            $this->db->where('DATE(i.created_on) >=', $tgl_dari);
        }
        if (!empty($tgl_sampai)) {
            $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
        }

        // search
        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('d.nm_produk', $like_value);
            $this->db->or_like('d.uom', $like_value);
            $this->db->group_end();
        }

        $this->db->group_by('d.nm_produk, d.uom');
        $this->db->order_by('d.nm_produk', 'asc');

        return $this->db->get()->result();
    }

    // =============================
    // Report Penjualan Produk per Customer
    // =============================
    public function data_side_barang_per_pelanggan()
    {
        $requestData = $_REQUEST;

        $tgl_dari   = $requestData['tgl_dari'] ?? null;
        $tgl_sampai = $requestData['tgl_sampai'] ?? null;

        $like_value  = $requestData['search']['value'] ?? '';
        $order_col   = $requestData['order'][0]['column'] ?? 0;
        $order_dir   = $requestData['order'][0]['dir'] ?? 'asc';
        $limit_start = $requestData['start'] ?? 0;
        $limit_len   = $requestData['length'] ?? -1;

        $fetch = $this->get_query_json_barang_per_pelanggan(
            $like_value,
            $order_col,
            $order_dir,
            $limit_start,
            $limit_len,
            $tgl_dari,
            $tgl_sampai
        );

        $totalData     = $fetch['totalData'];
        $totalFiltered = $fetch['totalFiltered'];
        $rows          = $fetch['query']->result_array();

        // =========================
        // Build data + subtotal/grand total
        // =========================
        $data = [];

        $currentBarang = null;
        $subQty = 0;
        $subSales = 0;

        $grandQty = 0;
        $grandSales = 0;

        foreach ($rows as $idx => $r) {
            $barang   = $r['nama_barang'];
            $pelanggan = $r['pelanggan'];
            $qty      = (float)$r['qty_total'];
            $sales    = (float)$r['penjualan_total'];

            // kalau barang berganti -> sisipkan subtotal barang sebelumnya
            if ($currentBarang !== null && $barang !== $currentBarang) {
                $data[] = [
                    'nama_barang' => '',
                    'pelanggan'   => '<b>Total Pelanggan</b>',
                    'kuantitas'   => "<div class='text-right'><b>" . number_format($subQty, 0, ',', '.') . "</b></div>",
                    'penjualan'   => "<div class='text-right'><b>" . number_format($subSales, 0, ',', '.') . "</b></div>",
                    'DT_RowClass' => 'row-subtotal'
                ];
                $subQty = 0;
                $subSales = 0;
            }

            $isFirstRowBarang = ($barang !== $currentBarang);
            $currentBarang = $barang;

            $subQty   += $qty;
            $subSales += $sales;

            $grandQty   += $qty;
            $grandSales += $sales;

            $data[] = [
                'nama_barang' => $isFirstRowBarang
                    ? "<div class='cell-produk'><b>{$barang}<b></div>"
                    : "<div class='cell-produk cell-produk-empty'></div>",
                'pelanggan' => "<div>{$pelanggan}</div>",
                'kuantitas' => "<div class='text-right'>" . number_format($qty, 0, ',', '.') . "</div>",
                'penjualan' => "<div class='text-right'>" . number_format($sales, 0, ',', '.') . "</div>",
                'DT_RowClass' => 'row-detail'
            ];
        }

        // subtotal terakhir
        if ($currentBarang !== null) {
            $data[] = [
                'nama_barang' => '',
                'pelanggan'   => '<b>Total Pelanggan</b>',
                'kuantitas'   => "<div class='text-right'><b>" . number_format($subQty, 0, ',', '.') . "</b></div>",
                'penjualan'   => "<div class='text-right'><b>" . number_format($subSales, 0, ',', '.') . "</b></div>",
                'DT_RowClass' => 'row-subtotal'
            ];

            // grand total
            $data[] = [
                'nama_barang' => '<b>Total Nama Barang</b>',
                'pelanggan'   => '',
                'kuantitas'   => "<div class='text-right'><b>" . number_format($grandQty, 0, ',', '.') . "</b></div>",
                'penjualan'   => "<div class='text-right'><b>" . number_format($grandSales, 0, ',', '.') . "</b></div>",
                'DT_RowClass' => 'row-grandtotal'
            ];
        }

        // NOTE: recordsTotal/Filtered tetap pakai hasil asli (tanpa subtotal row)
        // supaya tidak mengacau serverSide paging. Kita matikan paging & info di JS.
        $json_data = [
            "draw"            => intval($requestData['draw'] ?? 1),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ];

        echo json_encode($json_data);
    }

    public function get_query_json_barang_per_pelanggan($like_value = null, $column_order = null, $column_dir = null, $limit_start = null, $limit_length = null, $tgl_dari = null, $tgl_sampai = null)
    {
        $columns_order_by = [
            0 => 'd.nm_produk',
            1 => 'pelanggan',
            2 => 'qty_total',
            3 => 'penjualan_total',
        ];

        $apply_date_filter = function () use ($tgl_dari, $tgl_sampai) {
            if (!empty($tgl_dari) && !empty($tgl_sampai)) {
                $this->db->where('DATE(i.created_on) >=', $tgl_dari);
                $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
            } elseif (!empty($tgl_dari)) {
                $this->db->where('DATE(i.created_on) >=', $tgl_dari);
            } elseif (!empty($tgl_sampai)) {
                $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
            }
        };

        $apply_search_filter = function () use ($like_value) {
            if (!empty($like_value)) {
                $this->db->group_start();
                $this->db->like('d.nm_produk', $like_value);
                // pilih salah satu sesuai kolom kamu:
                $this->db->or_like('i.nm_customer', $like_value); // HAPUS kalau kolom ini tidak ada
                // $this->db->or_like('c.nm_customer', $like_value); // pakai ini kalau join customer master
                $this->db->group_end();
            }
        };

        // =============================
        // 1) totalData (jumlah kombinasi barang+pelanggan)
        // =============================
        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner');
        // $this->db->join('ms_customer c', 'c.id_customer = i.id_customer', 'left'); // kalau perlu
        $this->db->where('i.is_cancel', null);
        $apply_date_filter();

        $this->db->select("COUNT(DISTINCT CONCAT(IFNULL(d.id_produk,''),'|',IFNULL(i.id_customer,''))) AS total", false);
        // kalau tidak ada i.id_customer, ganti jadi customer name:
        // $this->db->select("COUNT(DISTINCT CONCAT(IFNULL(d.id_produk,''),'|',IFNULL(i.nm_customer,''))) AS total", false);

        $totalData = (int)$this->db->get()->row()->total;

        // =============================
        // 2) totalFiltered
        // =============================
        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner');
        // $this->db->join('ms_customer c', 'c.id_customer = i.id_customer', 'left');
        $this->db->where('i.is_cancel', null);
        $apply_date_filter();
        $apply_search_filter();

        $this->db->select("COUNT(DISTINCT CONCAT(IFNULL(d.id_produk,''),'|',IFNULL(i.id_customer,''))) AS total", false);
        // alternatif:
        // $this->db->select("COUNT(DISTINCT CONCAT(IFNULL(d.id_produk,''),'|',IFNULL(i.nm_customer,''))) AS total", false);

        $totalFiltered = (int)$this->db->get()->row()->total;

        // =============================
        // 3) data
        // =============================
        $this->db->select("
        d.id_produk,
        d.nm_produk AS nama_barang,
        IFNULL(i.nm_customer,'-') AS pelanggan,
        SUM(d.qty) AS qty_total,
        SUM(d.subtotal) AS penjualan_total
    ", false);

        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner');
        // $this->db->join('ms_customer c', 'c.id_customer = i.id_customer', 'left'); // kalau dipakai, ubah pelanggan jadi IFNULL(c.nm_customer,i.nm_customer)
        $this->db->where('i.is_cancel', null);
        $apply_date_filter();
        $apply_search_filter();

        $this->db->group_by('d.id_produk, d.nm_produk, pelanggan');

        // penting: urutkan by barang dulu biar grouping rapih
        $this->db->order_by('d.nm_produk', 'asc');
        $this->db->order_by('pelanggan', 'asc');

        // Kalau kamu mau tetap dukung order dari DataTables:
        /*
    if (isset($columns_order_by[$column_order])) {
        $this->db->order_by($columns_order_by[$column_order], $column_dir);
    }
    */

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

    public function get_export_barang_per_pelanggan($like_value = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        $this->db->select("
        d.id_produk,
        d.nm_produk AS nama_barang,
        IFNULL(i.nm_customer,'-') AS pelanggan,
        SUM(d.qty) AS qty_total,
        SUM(d.subtotal) AS penjualan_total
    ", false);

        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner');
        $this->db->where('i.is_cancel', null);

        // filter tanggal (created_on)
        if (!empty($tgl_dari)) {
            $this->db->where('DATE(i.created_on) >=', $tgl_dari);
        }
        if (!empty($tgl_sampai)) {
            $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
        }

        // search (produk atau pelanggan)
        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('d.nm_produk', $like_value);
            $this->db->or_like('i.nm_customer', $like_value);
            $this->db->group_end();
        }

        // group sesuai report: per barang per pelanggan
        $this->db->group_by('d.id_produk, d.nm_produk, pelanggan');

        // penting untuk grouping export: urutkan barang dulu, lalu pelanggan
        $this->db->order_by('d.nm_produk', 'asc');
        $this->db->order_by('pelanggan', 'asc');

        return $this->db->get()->result();
    }

    // =============================
    // Report Penjualan Customer per Product
    // =============================
    public function data_side_customer_per_barang()
    {
        $requestData = $_REQUEST;

        $tgl_dari   = $requestData['tgl_dari'] ?? null;
        $tgl_sampai = $requestData['tgl_sampai'] ?? null;
        $id_sales = $requestData['id_sales'] ?? null;

        $fetch = $this->get_query_json_customer_per_barang(
            $requestData['search']['value'] ?? '',
            $requestData['order'][0]['column'] ?? 0,
            $requestData['order'][0]['dir'] ?? 'asc',
            $requestData['start'] ?? 0,
            $requestData['length'] ?? -1,
            $tgl_dari,
            $tgl_sampai,
            $id_sales
        );

        $totalData     = $fetch['totalData'];
        $totalFiltered = $fetch['totalFiltered'];
        $query         = $fetch['query'];

        $data = [];

        $currentCust = null;
        $subQty = 0;
        $subSales = 0;

        $grandQty = 0;
        $grandSales = 0;

        foreach ($query->result_array() as $row) {
            $cust   = $row['pelanggan'];
            $produk = $row['nama_barang'];
            $satuan = $row['satuan'];
            $qty    = (float)$row['qty_total'];
            $sales  = (float)$row['penjualan_total'];

            // saat customer berganti -> subtotal customer sebelumnya
            if ($currentCust !== null && $cust !== $currentCust) {
                $data[] = [
                    "", // Pelanggan
                    "<b>Total Nama Barang</b>",
                    "",
                    "<div class='text-right'><b>" . number_format($subQty, 0, ',', '.') . "</b></div>",
                    "<div class='text-right'><b>" . number_format($subSales, 0, ',', '.') . "</b></div>",
                ];
                $subQty = 0;
                $subSales = 0;
            }

            $isFirst = ($cust !== $currentCust);
            $currentCust = $cust;

            $subQty += $qty;
            $subSales += $sales;
            $grandQty += $qty;
            $grandSales += $sales;

            $data[] = [
                $isFirst ? "<div class='cell-cust'>{$cust}</div>" : "<div class='cell-cust cell-cust-empty'></div>",
                "<div>{$produk}</div>",
                "<div class='text-center'>" . strtoupper($satuan) . "</div>",
                "<div class='text-right'>" . number_format($qty, 0, ',', '.') . "</div>",
                "<div class='text-right'>" . number_format($sales, 0, ',', '.') . "</div>",
            ];
        }

        // subtotal terakhir + grand total
        if ($currentCust !== null) {
            $data[] = [
                "",
                "<b>Total Nama Barang</b>",
                "",
                "<div class='text-right'><b>" . number_format($subQty, 0, ',', '.') . "</b></div>",
                "<div class='text-right'><b>" . number_format($subSales, 0, ',', '.') . "</b></div>",
            ];

            $data[] = [
                "<b>Total Pelanggan</b>",
                "",
                "",
                "<div class='text-right'><b>" . number_format($grandQty, 0, ',', '.') . "</b></div>",
                "<div class='text-right'><b>" . number_format($grandSales, 0, ',', '.') . "</b></div>",
            ];
        }

        echo json_encode([
            "draw"            => intval($requestData['draw'] ?? 1),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        ]);
    }

    public function get_query_json_customer_per_barang($like_value = NULL, $column_order = NULL, $column_dir = NULL, $limit_start = NULL, $limit_length = NULL, $tgl_dari = NULL, $tgl_sampai = NULL, $id_sales = NULL)
    {

        // Helper filter (Tanggal & Sales)
        $apply_filters = function () use ($tgl_dari, $tgl_sampai, $id_sales) {
            // Filter Tanggal
            if (!empty($tgl_dari) && !empty($tgl_sampai)) {
                $this->db->where('DATE(i.created_on) >=', $tgl_dari);
                $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
            }

            // Filter Sales (Join ke master customer)
            if (!empty($id_sales)) {
                $this->db->join('master_customers c', 'i.id_customer = c.id_customer');
                $this->db->where('c.id_karyawan', $id_sales);
            }
        };

        $apply_search_filter = function () use ($like_value) {
            if (!empty($like_value)) {
                $this->db->group_start();
                $this->db->like('i.nm_customer', $like_value);
                $this->db->or_like('d.nm_produk', $like_value);
                $this->db->or_like('d.uom', $like_value);
                $this->db->group_end();
            }
        };

        // ===== totalData (jumlah kombinasi customer+produk+uom) =====
        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner');
        $this->db->where('i.is_cancel', NULL);
        $apply_filters();

        $this->db->select("COUNT(DISTINCT CONCAT(IFNULL(i.id_customer,''),'|',IFNULL(d.id_produk,''),'|',IFNULL(d.uom,''))) AS total", false);
        // kalau tidak ada i.id_customer, ganti:
        // $this->db->select("COUNT(DISTINCT CONCAT(IFNULL(i.nm_customer,''),'|',IFNULL(d.id_produk,''),'|',IFNULL(d.uom,''))) AS total", false);

        $totalData = (int)$this->db->get()->row()->total;

        // ===== totalFiltered =====
        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner');
        $this->db->where('i.is_cancel', NULL);
        $apply_filters();
        $apply_search_filter();

        $this->db->select("COUNT(DISTINCT CONCAT(IFNULL(i.id_customer,''),'|',IFNULL(d.id_produk,''),'|',IFNULL(d.uom,''))) AS total", false);
        // alternatif:
        // $this->db->select("COUNT(DISTINCT CONCAT(IFNULL(i.nm_customer,''),'|',IFNULL(d.id_produk,''),'|',IFNULL(d.uom,''))) AS total", false);

        $totalFiltered = (int)$this->db->get()->row()->total;

        // ===== data =====
        $this->db->select("
        IFNULL(i.nm_customer,'-') AS pelanggan,
        d.nm_produk AS nama_barang,
        d.uom AS satuan,
        SUM(d.qty) AS qty_total,
        SUM(d.subtotal) AS penjualan_total
    ", false);

        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner');
        $this->db->where('i.is_cancel', NULL);
        $apply_filters();
        $apply_search_filter();

        $this->db->group_by('pelanggan, d.nm_produk, d.uom');
        $this->db->order_by('pelanggan', 'asc');
        $this->db->order_by('d.nm_produk', 'asc');
        $this->db->order_by('d.uom', 'asc');

        // rekomendasi: report ini tanpa limit, biar subtotal akurat
        // kalau mau tetap pakai limit, boleh, tapi subtotal jadi per halaman.
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

    public function get_export_customer_per_barang($like_value = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        $this->db->select("
        IFNULL(i.nm_customer,'-') AS pelanggan,
        d.nm_produk AS nama_barang,
        d.uom AS satuan,
        SUM(d.qty) AS qty_total,
        SUM(d.subtotal) AS penjualan_total
    ", false);

        $this->db->from('tr_invoice_sales i');
        $this->db->join('tr_invoice_sales_detail d', 'd.id_invoice = i.id_invoice', 'inner');
        $this->db->where('i.is_cancel', NULL);

        if (!empty($tgl_dari))   $this->db->where('DATE(i.created_on) >=', $tgl_dari);
        if (!empty($tgl_sampai)) $this->db->where('DATE(i.created_on) <=', $tgl_sampai);

        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('i.nm_customer', $like_value);
            $this->db->or_like('d.nm_produk', $like_value);
            $this->db->or_like('d.uom', $like_value);
            $this->db->group_end();
        }

        $this->db->group_by('pelanggan, d.nm_produk, d.uom');
        $this->db->order_by('pelanggan', 'asc');
        $this->db->order_by('d.nm_produk', 'asc');
        $this->db->order_by('d.uom', 'asc');

        return $this->db->get()->result();
    }

    // =============================
    // Report Target Sales per Bulan
    // =============================
    public function data_side_report_sales_bulanan()
    {
        $requestData = $_REQUEST;

        $tahun = $requestData['tahun'] ?? date('Y');

        $result = $this->get_query_report_sales_bulanan($tahun);

        echo json_encode([
            "status" => true,
            "data" => $result
        ]);
    }

    public function get_query_report_sales_bulanan($tahun)
    {
        // =========================
        // A) Ambil SEMUA sales dari tabel employee (department = 2)
        // =========================
        $allSales = $this->db->select("id, nm_karyawan")
            ->from("employee")
            ->where("department", 2)
            ->order_by("nm_karyawan", "asc")
            ->get()
            ->result_array();

        // =========================
        // B) TARGET dari target_penjualan
        // =========================
        $targetRows = $this->db->select("
            tp.id_karyawan,
            tp.nm_karyawan,
            tp.jan, tp.feb, tp.mar, tp.apr, tp.mei, tp.jun,
            tp.jul, tp.agu, tp.sep, tp.okt, tp.nov, tp.des
        ", false)
            ->from("target_penjualan tp")
            ->get()
            ->result_array();

        // jadikan map supaya gampang merge
        $targetMap = [];
        foreach ($targetRows as $t) {
            $targetMap[$t['id_karyawan']] = $t;
        }

        // =========================
        // C) ACTUAL dari tr_invoice_sales (pivot per bulan)
        // =========================
        $this->db->select("
        c.id_karyawan,
        SUM(CASE WHEN MONTH(i.delivery_date)=1  THEN i.grand_total ELSE 0 END) AS jan,
        SUM(CASE WHEN MONTH(i.delivery_date)=2  THEN i.grand_total ELSE 0 END) AS feb,
        SUM(CASE WHEN MONTH(i.delivery_date)=3  THEN i.grand_total ELSE 0 END) AS mar,
        SUM(CASE WHEN MONTH(i.delivery_date)=4  THEN i.grand_total ELSE 0 END) AS apr,
        SUM(CASE WHEN MONTH(i.delivery_date)=5  THEN i.grand_total ELSE 0 END) AS mei,
        SUM(CASE WHEN MONTH(i.delivery_date)=6  THEN i.grand_total ELSE 0 END) AS jun,
        SUM(CASE WHEN MONTH(i.delivery_date)=7  THEN i.grand_total ELSE 0 END) AS jul,
        SUM(CASE WHEN MONTH(i.delivery_date)=8  THEN i.grand_total ELSE 0 END) AS agu,
        SUM(CASE WHEN MONTH(i.delivery_date)=9  THEN i.grand_total ELSE 0 END) AS sep,
        SUM(CASE WHEN MONTH(i.delivery_date)=10 THEN i.grand_total ELSE 0 END) AS okt,
        SUM(CASE WHEN MONTH(i.delivery_date)=11 THEN i.grand_total ELSE 0 END) AS nov,
        SUM(CASE WHEN MONTH(i.delivery_date)=12 THEN i.grand_total ELSE 0 END) AS des
    ", false);

        $this->db->from("tr_invoice_sales i");
        $this->db->join("master_customers c", "c.id_customer = i.id_customer", "left");

        // filter tahun / tanggal
        $this->db->where("YEAR(i.delivery_date)", $tahun);

        if (!empty($tgl_dari)) {
            $this->db->where("DATE(i.delivery_date) >=", $tgl_dari);
        }
        if (!empty($tgl_sampai)) {
            $this->db->where("DATE(i.delivery_date) <=", $tgl_sampai);
        }

        // optional: invoice cancel tidak dihitung
        $this->db->where("IFNULL(i.is_cancel,0) =", 0);

        $this->db->group_by("c.id_karyawan");

        $actualRows = $this->db->get()->result_array();

        $actualMap = [];
        foreach ($actualRows as $a) {
            $actualMap[$a['id_karyawan']] = $a;
        }

        // =========================
        // D) MERGE + bentuk 2 baris per sales (berdasarkan SEMUA sales)
        // =========================
        $months = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'sep', 'okt', 'nov', 'des'];

        $resultRows = [];

        // total cabang (target + actual)
        $totalTarget = array_fill_keys($months, 0);
        $totalActual = array_fill_keys($months, 0);

        foreach ($allSales as $sales) {
            $id_karyawan = $sales['id'];
            $nama_sales  = strtoupper($sales['nm_karyawan']);

            // ambil target kalau ada, kalau tidak 0 semua
            $t = $targetMap[$id_karyawan] ?? array_merge(['id_karyawan' => $id_karyawan, 'nm_karyawan' => $sales['nm_karyawan']], array_fill_keys($months, 0));

            // ambil actual kalau ada, kalau tidak 0 semua
            $a = $actualMap[$id_karyawan] ?? array_merge(['id_karyawan' => $id_karyawan], array_fill_keys($months, 0));

            // --- HITUNG TSCORE
            $t_score_target = 0;
            $t_score_actual = 0;

            foreach ($months as $m) {
                $t_score_target += (float)$t[$m];
                $t_score_actual += (float)$a[$m];

                $totalTarget[$m] += (float)$t[$m];
                $totalActual[$m] += (float)$a[$m];
            }

            // BARIS 1: TARGET
            $resultRows[] = [
                'nama_sales' => $nama_sales,
                'tipe' => 'Target',
                'jan' => (float)$t['jan'],
                'feb' => (float)$t['feb'],
                'mar' => (float)$t['mar'],
                'apr' => (float)$t['apr'],
                'mei' => (float)$t['mei'],
                'jun' => (float)$t['jun'],
                'jul' => (float)$t['jul'],
                'agu' => (float)$t['agu'],
                'sep' => (float)$t['sep'],
                'okt' => (float)$t['okt'],
                'nov' => (float)$t['nov'],
                'des' => (float)$t['des'],
                't_score' => (float)$t_score_target
            ];

            // BARIS 2: ACTUAL
            $resultRows[] = [
                'nama_sales' => '', // biar tampil kayak excel (nama cuma sekali)
                'tipe' => 'Actual (based on invoice)',
                'jan' => (float)$a['jan'],
                'feb' => (float)$a['feb'],
                'mar' => (float)$a['mar'],
                'apr' => (float)$a['apr'],
                'mei' => (float)$a['mei'],
                'jun' => (float)$a['jun'],
                'jul' => (float)$a['jul'],
                'agu' => (float)$a['agu'],
                'sep' => (float)$a['sep'],
                'okt' => (float)$a['okt'],
                'nov' => (float)$a['nov'],
                'des' => (float)$a['des'],
                't_score' => (float)$t_score_actual
            ];
        }

        // =========================
        // D) TOTAL CABANG (baris bawah)
        // =========================
        $totalTargetScore = array_sum($totalTarget);
        $totalActualScore = array_sum($totalActual);

        $resultRows[] = [
            'nama_sales' => 'Target Cabang',
            'tipe' => 'Target',
            'jan' => $totalTarget['jan'],
            'feb' => $totalTarget['feb'],
            'mar' => $totalTarget['mar'],
            'apr' => $totalTarget['apr'],
            'mei' => $totalTarget['mei'],
            'jun' => $totalTarget['jun'],
            'jul' => $totalTarget['jul'],
            'agu' => $totalTarget['agu'],
            'sep' => $totalTarget['sep'],
            'okt' => $totalTarget['okt'],
            'nov' => $totalTarget['nov'],
            'des' => $totalTarget['des'],
            't_score' => $totalTargetScore
        ];

        $resultRows[] = [
            'nama_sales' => '',
            'tipe' => 'Actual (based on invoice)',
            'jan' => $totalActual['jan'],
            'feb' => $totalActual['feb'],
            'mar' => $totalActual['mar'],
            'apr' => $totalActual['apr'],
            'mei' => $totalActual['mei'],
            'jun' => $totalActual['jun'],
            'jul' => $totalActual['jul'],
            'agu' => $totalActual['agu'],
            'sep' => $totalActual['sep'],
            'okt' => $totalActual['okt'],
            'nov' => $totalActual['nov'],
            'des' => $totalActual['des'],
            't_score' => $totalActualScore
        ];

        return $resultRows;
    }

    public function get_export_sales_bulanan($tahun = 2025)
    {
        // =========================
        // A) Ambil SEMUA sales dari tabel employee (department = 2)
        // =========================
        $allSales = $this->db->select("id, nm_karyawan")
            ->from("employee")
            ->where("department", 2)
            ->order_by("nm_karyawan", "asc")
            ->get()
            ->result_array();

        // =========================
        // B) TARGET dari target_penjualan
        // =========================
        $targetRows = $this->db->select("
        tp.id_karyawan,
        tp.nm_karyawan,
        tp.jan, tp.feb, tp.mar, tp.apr, tp.mei, tp.jun,
        tp.jul, tp.agu, tp.sep, tp.okt, tp.nov, tp.des
    ", false)
            ->from("target_penjualan tp")
            ->order_by("tp.nm_karyawan", "asc")
            ->get()
            ->result_array();

        $targetMap = [];
        foreach ($targetRows as $t) {
            $targetMap[$t['id_karyawan']] = $t;
        }

        // =========================
        // C) ACTUAL dari tr_invoice_sales (per sales per bulan)
        // =========================
        $this->db->select("
        c.id_karyawan,
        SUM(CASE WHEN MONTH(i.delivery_date)=1  THEN i.grand_total ELSE 0 END) AS jan,
        SUM(CASE WHEN MONTH(i.delivery_date)=2  THEN i.grand_total ELSE 0 END) AS feb,
        SUM(CASE WHEN MONTH(i.delivery_date)=3  THEN i.grand_total ELSE 0 END) AS mar,
        SUM(CASE WHEN MONTH(i.delivery_date)=4  THEN i.grand_total ELSE 0 END) AS apr,
        SUM(CASE WHEN MONTH(i.delivery_date)=5  THEN i.grand_total ELSE 0 END) AS mei,
        SUM(CASE WHEN MONTH(i.delivery_date)=6  THEN i.grand_total ELSE 0 END) AS jun,
        SUM(CASE WHEN MONTH(i.delivery_date)=7  THEN i.grand_total ELSE 0 END) AS jul,
        SUM(CASE WHEN MONTH(i.delivery_date)=8  THEN i.grand_total ELSE 0 END) AS agu,
        SUM(CASE WHEN MONTH(i.delivery_date)=9  THEN i.grand_total ELSE 0 END) AS sep,
        SUM(CASE WHEN MONTH(i.delivery_date)=10 THEN i.grand_total ELSE 0 END) AS okt,
        SUM(CASE WHEN MONTH(i.delivery_date)=11 THEN i.grand_total ELSE 0 END) AS nov,
        SUM(CASE WHEN MONTH(i.delivery_date)=12 THEN i.grand_total ELSE 0 END) AS des
    ", false);

        $this->db->from("tr_invoice_sales i");
        $this->db->join("master_customers c", "c.id_customer = i.id_customer", "left");
        $this->db->where("YEAR(i.delivery_date)", $tahun);
        $this->db->where("IFNULL(i.is_cancel,0) =", 0);

        // hanya customer yg punya sales
        $this->db->where("c.id_karyawan >", 0);

        $this->db->group_by("c.id_karyawan");

        $actualRows = $this->db->get()->result_array();

        $actualMap = [];
        foreach ($actualRows as $a) {
            $actualMap[$a['id_karyawan']] = $a;
        }

        // =========================
        // D) Build output 2 baris per sales + total cabang (berdasarkan SEMUA sales)
        // =========================
        $months = ['jan', 'feb', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'sep', 'okt', 'nov', 'des'];

        $resultRows = [];

        $totalTarget = array_fill_keys($months, 0);
        $totalActual = array_fill_keys($months, 0);

        foreach ($allSales as $sales) {
            $id_karyawan = $sales['id'];
            $nama_sales  = $sales['nm_karyawan'];

            // ambil target kalau ada, kalau tidak 0 semua
            $t = $targetMap[$id_karyawan] ?? array_merge(['id_karyawan' => $id_karyawan, 'nm_karyawan' => $nama_sales], array_fill_keys($months, 0));

            // ambil actual kalau ada, kalau tidak 0 semua
            $a = $actualMap[$id_karyawan] ?? array_merge(['id_karyawan' => $id_karyawan], array_fill_keys($months, 0));

            $t_score_target = 0;
            $t_score_actual = 0;

            foreach ($months as $m) {
                $t_score_target += (float)$t[$m];
                $t_score_actual += (float)$a[$m];

                $totalTarget[$m] += (float)$t[$m];
                $totalActual[$m] += (float)$a[$m];
            }

            // TARGET row
            $resultRows[] = (object)[
                'nama_sales' => $nama_sales,
                'tipe' => 'Target',
                'jan' => (float)$t['jan'],
                'feb' => (float)$t['feb'],
                'mar' => (float)$t['mar'],
                'apr' => (float)$t['apr'],
                'mei' => (float)$t['mei'],
                'jun' => (float)$t['jun'],
                'jul' => (float)$t['jul'],
                'agu' => (float)$t['agu'],
                'sep' => (float)$t['sep'],
                'okt' => (float)$t['okt'],
                'nov' => (float)$t['nov'],
                'des' => (float)$t['des'],
                't_score' => (float)$t_score_target
            ];

            // ACTUAL row
            $resultRows[] = (object)[
                'nama_sales' => '',
                'tipe' => 'Actual (based on invoice)',
                'jan' => (float)$a['jan'],
                'feb' => (float)$a['feb'],
                'mar' => (float)$a['mar'],
                'apr' => (float)$a['apr'],
                'mei' => (float)$a['mei'],
                'jun' => (float)$a['jun'],
                'jul' => (float)$a['jul'],
                'agu' => (float)$a['agu'],
                'sep' => (float)$a['sep'],
                'okt' => (float)$a['okt'],
                'nov' => (float)$a['nov'],
                'des' => (float)$a['des'],
                't_score' => (float)$t_score_actual
            ];
        }

        // TOTAL CABANG
        $totalTargetScore = array_sum($totalTarget);
        $totalActualScore = array_sum($totalActual);

        $resultRows[] = (object)[
            'nama_sales' => 'Target Cabang',
            'tipe' => 'Target',
            'jan' => $totalTarget['jan'],
            'feb' => $totalTarget['feb'],
            'mar' => $totalTarget['mar'],
            'apr' => $totalTarget['apr'],
            'mei' => $totalTarget['mei'],
            'jun' => $totalTarget['jun'],
            'jul' => $totalTarget['jul'],
            'agu' => $totalTarget['agu'],
            'sep' => $totalTarget['sep'],
            'okt' => $totalTarget['okt'],
            'nov' => $totalTarget['nov'],
            'des' => $totalTarget['des'],
            't_score' => $totalTargetScore
        ];

        $resultRows[] = (object)[
            'nama_sales' => '',
            'tipe' => 'Actual (based on invoice)',
            'jan' => $totalActual['jan'],
            'feb' => $totalActual['feb'],
            'mar' => $totalActual['mar'],
            'apr' => $totalActual['apr'],
            'mei' => $totalActual['mei'],
            'jun' => $totalActual['jun'],
            'jul' => $totalActual['jul'],
            'agu' => $totalActual['agu'],
            'sep' => $totalActual['sep'],
            'okt' => $totalActual['okt'],
            'nov' => $totalActual['nov'],
            'des' => $totalActual['des'],
            't_score' => $totalActualScore
        ];

        return $resultRows;
    }
}
