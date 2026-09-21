<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_penjualan_hpp_model extends BF_Model
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
     * Server-side DataTables untuk Report Penjualan vs HPP.
     *
     * Sumber data:
     *   - tr_invoice_sales_detail (dt) → detail invoice (subtotal, harga_beli)
     *   - tr_invoice_sales (i)         → header invoice (created_on)
     *   - surat_jalan_detail (sjd)     → penghubung ke SO detail
     *   - sales_order_detail (sod)     → harga_beli (costbook SO)
     *
     * Kolom:
     *   COSTBOOK SO       = sod.harga_beli (harga beli dari SO)
     *   COSTBOOK INVOICE  = dt.harga_beli  (harga beli beku saat invoice dibuat)
     *   PENJUALAN + PPN   = dt.subtotal    (nilai termasuk PPN)
     *   PENDAPATAN        = dt.subtotal / 1.11 (DPP, nilai tanpa PPN)
     *   HPP               = dt.harga_beli * dt.qty
     *   Laba/Rugi         = PENDAPATAN - HPP
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

        // Accumulators untuk total footer
        $sumPenjualanPPN = 0;
        $sumPendapatan   = 0;
        $sumHPP          = 0;
        $sumLaba         = 0;

        foreach ($query->result_array() as $row) {
            $subtotal         = (float) $row['subtotal'];
            $costbook_invoice = (float) $row['costbook_invoice'];
            $qty              = (float) $row['qty'];

            $harga_hpp     = $costbook_invoice;                      // Costbook HPP dari dt.harga_beli

            $harga_jual        = round($subtotal / 1.11, 2);         // Harga jual sesuai invoice (tanpa PPN)
            $harga_jual_satuan = $qty > 0 ? ($harga_jual / $qty) : 0; // Harga jual per unit (tanpa PPN)
            $costbook_hpp      = $harga_hpp;                          // Costbook HPP per unit
            $pendapatan        = $harga_jual;                        // Pendapatan (DPP)
            $hpp               = $harga_hpp * $qty;                   // HPP (harga_beli * qty)
            $laba              = $pendapatan - $hpp;                  // LABA/RUGI KOTOR
            $persen_laba       = $pendapatan > 0 ? round(($laba / $pendapatan) * 100) : 0;

            $sumPenjualanPPN += $harga_jual;
            $sumPendapatan   += $pendapatan;
            $sumHPP          += $hpp;
            $sumLaba         += $laba;

            $nestedData = [];
            $nestedData[] = "<div class='text-center'>{$urut}</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['id_so'] ?? '') . "</div>";
            $nestedData[] = "<div>" . ($row['nm_customer'] ?? '') . "</div>";
            $nestedData[] = "<div>" . ($row['created_by'] ?? '') . "</div>";
            $nestedData[] = "<div class='text-center'>" . ((!empty($row['created_on'])) ? date('d/m/Y H:i', strtotime($row['created_on'])) : '') . "</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['id_invoice']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['id_produk'] ?? '') . "</div>";
            $nestedData[] = "<div>" . ($row['nm_produk'] ?? '') . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($qty) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($harga_jual_satuan) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($costbook_hpp) . "</div>";
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
            "sumPenjualanPPN" => $sumPenjualanPPN,
            "sumPendapatan"   => $sumPendapatan,
            "sumHPP"          => $sumHPP,
            "sumLaba"         => $sumLaba,
        ];

        echo json_encode($json_data);
    }

    /**
     * Query builder untuk server-side DataTables.
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
            0  => 'dt.id',
            1  => 'dt.id_so',
            2  => 'i.nm_customer',
            3  => 'i.created_by',
            4  => 'i.created_on',
            5  => 'dt.id_invoice',
            6  => 'dt.id_produk',
            7  => 'dt.nm_produk',
            8  => 'dt.qty',
            9  => 'dt.subtotal',     // Harga jual satuan (sortir pakai subtotal)
            10 => 'dt.harga_beli',   // Costbook HPP
            11 => 'dt.subtotal',     // Harga jual
            12 => 'dt.harga_beli',   // HPP
            13 => 'dt.subtotal',     // Laba/Rugi (sortir pakai subtotal, hitungan di PHP)
        ];

        $select = "
            dt.id,
            i.created_on,
            i.nm_customer,
            i.created_by,
            dt.id_invoice,
            dt.id_so,
            dt.id_penawaran,
            dt.id_delivery,
            dt.id_produk,
            dt.nm_produk,
            ROUND(dt.qty) AS qty,
            IFNULL(dt.harga_beli, 0)  AS costbook_invoice,
            dt.subtotal
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
                $this->db->or_like('dt.id_penawaran', $like_value);
                $this->db->or_like('dt.id_delivery', $like_value);
                $this->db->or_like('dt.id_produk', $like_value);
                $this->db->or_like('dt.nm_produk', $like_value);
                $this->db->group_end();
            }
        };

        // 1) totalData
        $this->db->select('COUNT(*) AS total', false);
        $this->db->from('tr_invoice_sales_detail dt');
        $apply_joins();
        $apply_filters();
        $totalData = (int) $this->db->get()->row()->total;

        // 2) totalFiltered
        $this->db->select('COUNT(*) AS total', false);
        $this->db->from('tr_invoice_sales_detail dt');
        $apply_joins();
        $apply_filters();
        $apply_search();
        $totalFiltered = (int) $this->db->get()->row()->total;

        // 3) Data
        $this->db->select($select, false);
        $this->db->from('tr_invoice_sales_detail dt');
        $apply_joins();
        $apply_filters();
        $apply_search();

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
            'totalData'     => $totalData,
            'totalFiltered' => $totalFiltered,
            'query'         => $query,
        ];
    }

    /**
     * Ambil semua data tanpa paging untuk export Excel.
     */
    public function get_export_report($like_value = null, $tgl_dari = null, $tgl_sampai = null)
    {
        $sql = "
            SELECT
                i.created_on,
                i.nm_customer,
                i.created_by,
                dt.id_invoice,
                dt.id_so,
                dt.id_penawaran,
                dt.id_delivery,
                dt.id_produk,
                dt.nm_produk,
                ROUND(dt.qty) AS qty,
                IFNULL(dt.harga_beli, 0)  AS costbook_invoice,
                dt.subtotal
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
            $sql .= " AND (dt.id_invoice LIKE ? OR dt.id_so LIKE ? OR dt.nm_produk LIKE ?)";
            $binds[] = "%{$like_value}%";
            $binds[] = "%{$like_value}%";
            $binds[] = "%{$like_value}%";
        }

        $sql .= " ORDER BY i.created_on DESC, dt.id_invoice ASC";

        return $this->db->query($sql, $binds)->result();
    }
}
