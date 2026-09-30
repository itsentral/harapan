<?php

use Mpdf\Tag\P;

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_pembelian_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ENABLE_ADD     = has_permission('Report_Pembelian.Add');
        $this->ENABLE_MANAGE  = has_permission('Report_Pembelian.Manage');
        $this->ENABLE_VIEW    = has_permission('Report_Pembelian.View');
        $this->ENABLE_DELETE  = has_permission('Report_Pembelian.Delete');
    }

    // =============================
    // Report Seluruh Pembelian
    // =============================
    public function data_side_report()
    {
        $requestData = $_REQUEST;

        $tgl_dari   = $requestData['tgl_dari'] ?? null;
        $tgl_sampai = $requestData['tgl_sampai'] ?? null;

        $fetch = $this->get_query_json_report(
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
            $nestedData[] = "<div class='text-left'>" . strtoupper($row['id']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . (($row['invoice_date'] != null) ? date('d/M/Y', strtotime($row['invoice_date'])) : '') . "</div>";
            $nestedData[] = "<div>" . strtoupper($row['nm_supplier']) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($row['total_invoice']) . "</div>";

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

    public function get_query_json_report($like_value = NULL, $column_order = NULL, $column_dir = NULL, $limit_start = NULL, $limit_length = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        // Sesuaikan mapping column order dengan index di table HTML
        $columns_order_by = [
            1 => 'i.id',
            2 => 'i.invoice_date',
            3 => 'i.nm_supplier',
            4 => 'i.total_invoice',
        ];

        $apply_date_filter = function () use ($tgl_dari, $tgl_sampai) {
            if (!empty($tgl_dari) && !empty($tgl_sampai)) {
                $this->db->where('i.invoice_date >=', $tgl_dari);
                $this->db->where('i.invoice_date <=', $tgl_sampai);
            }
        };

        // Sesuaikan select dengan kolom tr_invoice_po
        $select = 'i.id, i.id, i.invoice_date, i.nm_supplier, i.total_invoice';

        // 1) totalData
        $this->db->from('tr_invoice_po i');
        $apply_date_filter();
        $totalData = $this->db->count_all_results();

        // 2) totalFiltered
        $this->db->from('tr_invoice_po i');
        $apply_date_filter();
        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('i.id', $like_value);
            $this->db->or_like('i.nm_supplier', $like_value);
            $this->db->group_end();
        }
        $totalFiltered = $this->db->count_all_results();

        // 3) Data Fetch
        $this->db->select($select)->from('tr_invoice_po i');
        $apply_date_filter();
        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('i.id', $like_value);
            $this->db->or_like('i.nm_supplier', $like_value);
            $this->db->group_end();
        }

        if (isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('i.invoice_date', 'desc');
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

    // =============================
    // Export Daftar Faktur Pembelian (mirror get_query_json_report)
    // =============================
    public function get_export_report($like_value = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        // Gunakan filter yang PERSIS sama dengan get_query_json_report()
        // agar hasil Excel identik dengan tampilan list Daftar Faktur Pembelian.
        $this->db->select('i.id, i.invoice_date, i.nm_supplier, i.total_invoice');
        $this->db->from('tr_invoice_po i');

        // Filter tanggal: hanya aktif bila KEDUA tanggal terisi (sama seperti list)
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $this->db->where('i.invoice_date >=', $tgl_dari);
            $this->db->where('i.invoice_date <=', $tgl_sampai);
        }

        // Search: kolom sama dengan list (i.id, i.nm_supplier)
        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('i.id', $like_value);
            $this->db->or_like('i.nm_supplier', $like_value);
            $this->db->group_end();
        }

        $this->db->order_by('i.invoice_date', 'desc');

        $query = $this->db->get();

        return ($query) ? $query->result() : [];
    }

    // =============================
    // Histori Pembelian PR, PO, Inc, Receiv Inv, Payment
    // =============================
    public function get_json_history_pembelian()
    {
        $requestData = $_REQUEST;

        $tgl_dari   = $requestData['tgl_dari'] ?? null;
        $tgl_sampai = $requestData['tgl_sampai'] ?? null;

        $fetch = $this->get_query_history_pemebelian(
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

            // Tambahkan Badge Tipe PR agar informatif
            $tipe = $row['tipe_pr'];
            $badge = ($tipe == 'Product') ? 'bg-blue' : (($tipe == 'Asset') ? 'bg-purple' : 'bg-green');

            $nestedData[] = "<div>" . $row['permintaan_barang'] . " <br><small class='label {$badge}'>{$tipe}</small></div>";
            $nestedData[] = "<b>" . ($row['pesanan_pembelian'] ?? '-') . "</b>";
            $nestedData[] = $row['penerimaan_barang'] ?? '-';
            $nestedData[] = $row['faktur_pembelian'] ?? '-';
            $nestedData[] = $row['pembayaran_pembelian'] ?? '-';
            $nestedData[] = !empty($row['term']) ? $row['term'] . ' Hari' : '-';
            $nestedData[] = !empty($row['jatuh_tempo']) ? date('d-m-Y', strtotime($row['jatuh_tempo'])) : '-';

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

    public function get_query_history_pemebelian($like_value = NULL, $column_order = NULL, $column_dir = NULL, $limit_start = NULL, $limit_length = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        $columns_order_by = [
            1 => 'pr_combined.no_pr',
            2 => 'po.no_po',
            3 => 'ic.kode_trans',
            4 => 'inv.id',
            5 => 'pa.id',
            6 => 'po.term',
            7 => 'jatuh_tempo'
        ];

        // Bangun WHERE conditions secara manual agar bisa dipakai di COUNT dan data query
        $where_parts = [];
        $binds       = [];

        // Hanya tampilkan PR yang punya PO (buang baris PR tanpa PO).
        // Menjaga total selalu konsisten antara list & export.
        $where_parts[] = 'po.no_po IS NOT NULL';

        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $where_parts[] = 'po.tanggal >= ?';
            $binds[]       = $tgl_dari;
            $where_parts[] = 'po.tanggal <= ?';
            $binds[]       = $tgl_sampai;
        }

        if (!empty($like_value)) {
            $like_escaped  = '%' . $this->db->escape_like_str($like_value) . '%';
            $where_parts[] = "(pr_combined.no_pr LIKE ? OR po.no_po LIKE ? OR inv.id LIKE ?)";
            $binds[]       = $like_escaped;
            $binds[]       = $like_escaped;
            $binds[]       = $like_escaped;
        }

        $where_sql = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

        $base_from = "FROM (
            SELECT no_pr, po_number AS no_po, 'Product' AS tipe_pr FROM material_planning_base_on_produksi
            UNION ALL
            SELECT no_pengajuan AS no_pr, no_po, 'Rutin' AS tipe_pr FROM rutin_non_planning_header
        ) AS pr_combined
        LEFT JOIN tr_purchase_order po  ON po.no_po   = pr_combined.no_po
        LEFT JOIN tr_incoming_check ic  ON ic.no_ipp  = po.no_po
        LEFT JOIN tr_invoice_po inv     ON inv.no_po  = po.no_surat
        LEFT JOIN payment_approve pa    ON pa.no_doc  = inv.id";

        // Hitung total dengan raw query — aman untuk subquery UNION
        $count_sql    = "SELECT COUNT(*) AS total {$base_from} {$where_sql}";
        $count_result = $this->db->query($count_sql, $binds);
        $totalData    = ($count_result) ? (int) $count_result->row()->total : 0;
        $totalFiltered = $totalData;

        // Tentukan ORDER BY
        if (isset($columns_order_by[$column_order])) {
            $order_col = $columns_order_by[$column_order];
            $order_dir = (strtolower($column_dir) === 'asc') ? 'ASC' : 'DESC';
        } else {
            $order_col = 'po.tanggal';
            $order_dir = 'DESC';
        }

        $order_sql = "ORDER BY {$order_col} {$order_dir}";

        // LIMIT
        $limit_sql = '';
        if ($limit_length != -1) {
            $limit_start  = (int) $limit_start;
            $limit_length = (int) $limit_length;
            $limit_sql    = "LIMIT {$limit_start}, {$limit_length}";
        }

        // Data query
        $data_sql = "SELECT
            pr_combined.no_pr  AS permintaan_barang,
            pr_combined.tipe_pr,
            po.no_surat        AS pesanan_pembelian,
            ic.kode_trans      AS penerimaan_barang,
            inv.id             AS faktur_pembelian,
            pa.id              AS pembayaran_pembelian,
            po.tanggal         AS tgl_po,
            po.term            AS term,
            DATE_ADD(ic.tanggal, INTERVAL po.term DAY) AS jatuh_tempo
        {$base_from}
        {$where_sql}
        {$order_sql}
        {$limit_sql}";

        $query = $this->db->query($data_sql, $binds);

        return [
            'totalData'     => $totalData,
            'totalFiltered' => $totalFiltered,
            'query'         => $query
        ];
    }

    public function get_export_history($like_value = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        // Gunakan raw query (bukan Query Builder) karena Query Builder tidak dapat
        // menangani derived-table subquery UNION dengan benar -> $this->db->get()
        // mengembalikan FALSE sehingga ->result() error.
        // Pola ini konsisten dengan get_query_history_pemebelian().

        $where_parts = [];
        $binds       = [];

        // Hanya tampilkan PR yang punya PO (buang baris PR tanpa PO).
        // Menjaga total selalu konsisten antara list & export.
        $where_parts[] = 'po.no_po IS NOT NULL';

        // Filter Tanggal (Berdasarkan tanggal PO)
        // Samakan persis dengan list/datatable: filter tanggal HANYA aktif
        // jika kedua tanggal terisi.
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $where_parts[] = 'po.tanggal >= ?';
            $binds[]       = $tgl_dari;
            $where_parts[] = 'po.tanggal <= ?';
            $binds[]       = $tgl_sampai;
        }

        // Search filter — kolom & urutan disamakan dengan list/datatable
        if (!empty($like_value)) {
            $like_escaped  = '%' . $this->db->escape_like_str($like_value) . '%';
            $where_parts[] = "(pr_combined.no_pr LIKE ? OR po.no_po LIKE ? OR inv.id LIKE ?)";
            $binds[]       = $like_escaped;
            $binds[]       = $like_escaped;
            $binds[]       = $like_escaped;
        }

        $where_sql = !empty($where_parts) ? 'WHERE ' . implode(' AND ', $where_parts) : '';

        $base_from = "FROM (
            SELECT no_pr, po_number AS no_po, 'Product' AS tipe_pr FROM material_planning_base_on_produksi
            UNION ALL
            SELECT no_pengajuan AS no_pr, no_po, 'Rutin' AS tipe_pr FROM rutin_non_planning_header
        ) AS pr_combined
        LEFT JOIN tr_purchase_order po  ON po.no_po   = pr_combined.no_po
        LEFT JOIN tr_incoming_check ic  ON ic.no_ipp  = po.no_po
        LEFT JOIN tr_invoice_po inv     ON inv.no_po  = po.no_surat
        LEFT JOIN payment_approve pa    ON pa.no_doc  = inv.id";

        $data_sql = "SELECT
            pr_combined.no_pr  AS permintaan_barang,
            pr_combined.tipe_pr,
            po.no_surat        AS pesanan_pembelian,
            ic.kode_trans      AS penerimaan_barang,
            inv.id             AS faktur_pembelian,
            pa.id              AS pembayaran_pembelian,
            po.tanggal         AS tgl_po,
            po.term            AS term,
            DATE_ADD(ic.tanggal, INTERVAL po.term DAY) AS jatuh_tempo
        {$base_from}
        {$where_sql}
        ORDER BY po.tanggal DESC";

        $query = $this->db->query($data_sql, $binds);

        return ($query) ? $query->result() : [];
    }

    // =============================
    // Histori Pembelian PR, PO, Inc, Receiv Inv, Payment per Barang
    // =============================
    public function get_json_pembelian_per_barang()
    {
        $requestData = $_REQUEST;

        $tgl_dari   = $requestData['tgl_dari'] ?? null;
        $tgl_sampai = $requestData['tgl_sampai'] ?? null;

        $fetch = $this->get_query_pembelian_per_barang(
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
            $nestedData[] = "<b>" . $row['nama_barang'] . "</b>";
            $nestedData[] = "<div class='text-right'>" . number_format($row['total_qty'], 0) . "</div>";
            $nestedData[] = "<div class='text-right'>Rp " . number_format($row['total_nominal'], 2) . "</div>";

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

    public function get_query_pembelian_per_barang($like_value = NULL, $column_order = NULL, $column_dir = NULL, $limit_start = NULL, $limit_length = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        $columns_order_by = [
            1 => 'dt.namamaterial',
            2 => 'total_qty',
            3 => 'total_nominal'
        ];

        $this->db->select('
        dt.idmaterial,
        dt.namamaterial as nama_barang,
        SUM(ic.qty_oke) as total_qty,
        SUM(ic.harga * ic.qty_oke) as total_nominal
    ');
        $this->db->from('tr_invoice_po inv');
        $this->db->join('tr_purchase_order po', 'po.no_surat = inv.no_po', 'left');
        $this->db->join('dt_trans_po dt', 'dt.no_po = po.no_po', 'left');
        $this->db->join('tr_incoming_check_detail i', 'i.id_po_detail = dt.id', 'left');
        $this->db->join('tr_checked_incoming_detail ic', 'ic.id_detail = i.id', 'left');

        // Filter Periode Tanggal Invoice
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $this->db->where('inv.invoice_date >=', $tgl_dari);
            $this->db->where('inv.invoice_date <=', $tgl_sampai);
        }

        // Filter Search
        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('dt.namamaterial', $like_value);
            $this->db->or_like('dt.idmaterial', $like_value);
            $this->db->group_end();
        }

        $this->db->group_by('dt.namamaterial');

        $temp_db = clone $this->db;
        $query_total = $temp_db->get();
        $totalData = $query_total->num_rows();
        $totalFiltered = $totalData;

        if (isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('total_nominal', 'desc');
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

    public function get_export_pembelian_per_barang($like_value = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        $this->db->select('
        dt.idmaterial,
        dt.namamaterial as nama_barang,
        SUM(ic.qty_oke) as total_qty,
        SUM(ic.harga * ic.qty_oke) as total_nominal
    ');
        $this->db->from('tr_invoice_po inv');
        $this->db->join('tr_purchase_order po', 'po.no_surat = inv.no_po', 'left');
        $this->db->join('dt_trans_po dt', 'dt.no_po = po.no_po', 'left');
        $this->db->join('tr_incoming_check_detail i', 'i.id_po_detail = dt.id', 'left');
        $this->db->join('tr_checked_incoming_detail ic', 'ic.id_detail = i.id', 'left');

        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $this->db->where('inv.invoice_date >=', $tgl_dari);
            $this->db->where('inv.invoice_date <=', $tgl_sampai);
        }

        if (!empty($like_value)) {
            $this->db->like('dt.namamaterial', $like_value);
        }

        $this->db->group_by('dt.namamaterial');
        $this->db->order_by('total_nominal', 'desc');

        return $this->db->get()->result();
    }

    // =============================
    // Histori Pembelian PR, PO, Inc, Receiv Inv, Payment per Vendor
    // =============================
    public function get_json_pembelian_per_vendor()
    {
        $requestData = $_REQUEST;

        $tgl_dari   = $requestData['tgl_dari'] ?? null;
        $tgl_sampai = $requestData['tgl_sampai'] ?? null;

        $fetch = $this->get_query_pembelian_per_vendor(
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
            $nestedData[] = "<b>" . ($row['nm_supplier'] ?? 'TANPA NAMA') . "</b>";
            // Menggunakan total_pembelian sesuai kolom di tr_invoice_po
            $nestedData[] = "<div class='text-right'>Rp " . number_format($row['total_nominal'], 2) . "</div>";

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

    public function get_query_pembelian_per_vendor($like_value = NULL, $column_order = NULL, $column_dir = NULL, $limit_start = NULL, $limit_length = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        $columns_order_by = [
            1 => 'inv.nm_supplier',
            2 => 'total_nominal'
        ];

        $this->db->select('
        inv.id_supplier,
        inv.nm_supplier,
        SUM(inv.total_pembelian) as total_nominal
    ');
        $this->db->from('tr_invoice_po inv');

        // Filter Periode Tanggal Invoice
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $this->db->where('inv.invoice_date >=', $tgl_dari);
            $this->db->where('inv.invoice_date <=', $tgl_sampai);
        }

        // Filter Search
        if (!empty($like_value)) {
            $this->db->group_start();
            $this->db->like('inv.nm_supplier', $like_value);
            $this->db->or_like('inv.id_supplier', $like_value);
            $this->db->group_end();
        }

        $this->db->group_by('inv.nm_supplier');

        // Hitung Total Data
        $temp_db = clone $this->db;
        $query_total = $temp_db->get();
        $totalData = $query_total->num_rows();
        $totalFiltered = $totalData;

        // Sorting
        if (isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('total_nominal', 'desc');
        }

        // Limit
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

    public function get_export_pembelian_per_vendor($like_value = NULL, $tgl_dari = NULL, $tgl_sampai = NULL)
    {
        $this->db->select('
        inv.nm_supplier as pemasok,
        SUM(inv.total_pembelian) as total_nominal
    ');
        $this->db->from('tr_invoice_po inv');

        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $this->db->where('inv.invoice_date >=', $tgl_dari);
            $this->db->where('inv.invoice_date <=', $tgl_sampai);
        }

        if (!empty($like_value)) {
            $this->db->like('inv.nm_supplier', $like_value);
        }

        $this->db->group_by('inv.nm_supplier');
        $this->db->order_by('total_nominal', 'desc');

        return $this->db->get()->result();
    }
}
