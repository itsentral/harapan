<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_penjualan_hpp_summary_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->ENABLE_ADD     = has_permission('Report_Penjualan_HPP.Add');
        $this->ENABLE_MANAGE  = has_permission('Report_Penjualan_HPP.Manage');
        $this->ENABLE_VIEW    = has_permission('Report_Penjualan_HPP.View');
        $this->ENABLE_DELETE  = has_permission('Report_Penjualan_HPP.Delete');
    }

    /**
     * Server-side DataTables: Ringkasan Penjualan vs HPP di-GROUP per invoice.
     *
     * Tujuan: menjumlahkan HARGA JUAL dan HPP per invoice sehingga bisa
     * dibandingkan dengan ledger/jurnal (yang penjualan & HPP-nya tergabung).
     *
     * Per baris detail:
     *   HARGA JUAL (DPP) = dt.subtotal / 1.11
     *   HPP              = dt.harga_beli * dt.qty
     * Lalu di-SUM per id_invoice.
     */
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

        $data = [];
        $urut = intval($requestData['start']) + 1;

        $sumHargaJual = 0;
        $sumHPP       = 0;
        $sumLaba      = 0;

        foreach ($query->result_array() as $row) {
            $harga_jual = (float) $row['harga_jual'];
            $hpp        = (float) $row['hpp'];
            $laba       = $harga_jual - $hpp;
            $persen_laba = $harga_jual > 0 ? round(($laba / $harga_jual) * 100) : 0;

            $sumHargaJual += $harga_jual;
            $sumHPP       += $hpp;
            $sumLaba      += $laba;

            $nestedData = [];
            $nestedData[] = "<div class='text-center'>{$urut}</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['id_invoice']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['id_so'] ?? '') . "</div>";
            $nestedData[] = "<div>" . ($row['nm_customer'] ?? '') . "</div>";
            $nestedData[] = "<div>" . ($row['created_by'] ?? '') . "</div>";
            $nestedData[] = "<div class='text-center'>" . ((!empty($row['created_on'])) ? date('d/m/Y H:i', strtotime($row['created_on'])) : '') . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($row['jml_item']) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($harga_jual) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($hpp) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($laba) . "</div>";
            $nestedData[] = "<div class='text-center'>{$persen_laba}%</div>";

            $data[] = $nestedData;
            $urut++;
        }

        $json_data = [
            "draw"            => intval($requestData['draw']),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "sumHargaJual"    => $sumHargaJual,
            "sumHPP"          => $sumHPP,
            "sumLaba"         => $sumLaba,
        ];

        echo json_encode($json_data);
    }

    /**
     * Query builder untuk server-side DataTables (grouped per invoice).
     */
    public function get_query_json_report(
        $like_value = null,
        $column_order = null,
        $column_dir = null,
        $limit_start = null,
        $limit_length = null,
        $tgl_dari = null,
        $tgl_sampai = null
    ) {
        $columns_order_by = [
            0  => 'dt.id_invoice',
            1  => 'dt.id_invoice',
            2  => 'dt.id_so',
            3  => 'i.nm_customer',
            4  => 'i.created_by',
            5  => 'i.created_on',
            6  => 'jml_item',
            7  => 'harga_jual',
            8  => 'hpp',
            9  => 'harga_jual',   // laba (sortir pakai harga_jual)
        ];

        $select = "
            dt.id_invoice,
            MAX(dt.id_so)          AS id_so,
            MAX(i.nm_customer)     AS nm_customer,
            MAX(i.created_by)      AS created_by,
            MAX(i.created_on)      AS created_on,
            COUNT(*)               AS jml_item,
            SUM(dt.subtotal / 1.11)                AS harga_jual,
            SUM(IFNULL(dt.harga_beli, 0) * dt.qty) AS hpp
        ";

        // Closure: apply joins
        $apply_joins = function () {
            $this->db->join('tr_invoice_sales i', 'i.id_invoice = dt.id_invoice', 'inner');
        };

        // Closure: apply filters
        $apply_filters = function () use ($tgl_dari, $tgl_sampai) {
            $this->db->where('IFNULL(i.is_cancel, 0) =', 0, false);
            $this->db->where('IFNULL(dt.qty, 0) >', 0, false);

            if (!empty($tgl_dari) && !empty($tgl_sampai)) {
                $this->db->where('DATE(i.created_on) >=', $tgl_dari);
                $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
            } elseif (!empty($tgl_dari)) {
                $this->db->where('DATE(i.created_on) >=', $tgl_dari);
            } elseif (!empty($tgl_sampai)) {
                $this->db->where('DATE(i.created_on) <=', $tgl_sampai);
            }
        };

        // Closure: apply search
        $apply_search = function () use ($like_value) {
            if (!empty($like_value)) {
                $this->db->group_start();
                $this->db->like('dt.id_invoice', $like_value);
                $this->db->or_like('dt.id_so', $like_value);
                $this->db->or_like('i.nm_customer', $like_value);
                $this->db->group_end();
            }
        };

        // 1) totalData (jumlah invoice unik)
        $this->db->select('COUNT(DISTINCT dt.id_invoice) AS total', false);
        $this->db->from('tr_invoice_sales_detail dt');
        $apply_joins();
        $apply_filters();
        $totalData = (int) $this->db->get()->row()->total;

        // 2) totalFiltered
        $this->db->select('COUNT(DISTINCT dt.id_invoice) AS total', false);
        $this->db->from('tr_invoice_sales_detail dt');
        $apply_joins();
        $apply_filters();
        $apply_search();
        $totalFiltered = (int) $this->db->get()->row()->total;

        // 3) Data (grouped per invoice)
        $this->db->select($select, false);
        $this->db->from('tr_invoice_sales_detail dt');
        $apply_joins();
        $apply_filters();
        $apply_search();
        $this->db->group_by('dt.id_invoice');

        if (isset($columns_order_by[$column_order])) {
            $this->db->order_by($columns_order_by[$column_order], $column_dir);
        } else {
            $this->db->order_by('created_on', 'asc');
        }

        if ($limit_length != -1) {
            $this->db->limit($limit_length, $limit_start);
        }

        $query = $this->db->get();

        return [
            'totalData'     => $totalData,
            'totalFiltered' => $totalFiltered,
            'query'         => $query,
        ];
    }

    /**
     * Ambil semua data (grouped per invoice) tanpa paging untuk export Excel.
     */
    public function get_export_report($like_value = null, $tgl_dari = null, $tgl_sampai = null)
    {
        $sql = "
            SELECT
                dt.id_invoice,
                MAX(dt.id_so)          AS id_so,
                MAX(i.nm_customer)     AS nm_customer,
                MAX(i.created_by)      AS created_by,
                MAX(i.created_on)      AS created_on,
                COUNT(*)               AS jml_item,
                SUM(dt.subtotal / 1.11)                AS harga_jual,
                SUM(IFNULL(dt.harga_beli, 0) * dt.qty) AS hpp
            FROM tr_invoice_sales_detail dt
            INNER JOIN tr_invoice_sales i
                ON i.id_invoice = dt.id_invoice
            WHERE IFNULL(i.is_cancel, 0) = 0
              AND IFNULL(dt.qty, 0) > 0
        ";

        $binds = [];

        if (!empty($tgl_dari)) {
            $sql .= " AND DATE(i.created_on) >= ?";
            $binds[] = $tgl_dari;
        }
        if (!empty($tgl_sampai)) {
            $sql .= " AND DATE(i.created_on) <= ?";
            $binds[] = $tgl_sampai;
        }
        if (!empty($like_value)) {
            $sql .= " AND (dt.id_invoice LIKE ? OR dt.id_so LIKE ? OR i.nm_customer LIKE ?)";
            $binds[] = "%{$like_value}%";
            $binds[] = "%{$like_value}%";
            $binds[] = "%{$like_value}%";
        }

        $sql .= " GROUP BY dt.id_invoice ORDER BY MAX(i.created_on) ASC, dt.id_invoice ASC";

        return $this->db->query($sql, $binds)->result();
    }
}
