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
            $costbook_so      = (float) $row['costbook_so'];
            $costbook_invoice = (float) $row['costbook_invoice'];
            $qty              = (float) $row['qty'];

            // Fallback: jika costbook invoice kosong/0, pakai costbook SO (data lama)
            $harga_hpp     = ($costbook_invoice > 0) ? $costbook_invoice : $costbook_so;

            $penjualan_ppn = $subtotal;                          // PENJUALAN + PPN
            $pendapatan    = round($subtotal / 1.11, 2);         // PENDAPATAN (DPP)
            $hpp           = $harga_hpp * $qty;                   // HPP (costbook invoice, fallback SO)
            $laba          = $pendapatan - $hpp;                  // LABA/RUGI KOTOR
            $persen_hpp    = $pendapatan > 0 ? round(($hpp / $pendapatan) * 100) : 0;
            $persen_laba   = $pendapatan > 0 ? round(($laba / $pendapatan) * 100) : 0;

            $sumPenjualanPPN += $penjualan_ppn;
            $sumPendapatan   += $pendapatan;
            $sumHPP          += $hpp;
            $sumLaba         += $laba;

            $nestedData = [];
            $nestedData[] = "<div class='text-center'>{$urut}</div>";
            $nestedData[] = "<div class='text-center'>" . ((!empty($row['created_on'])) ? date('d/m/Y H:i', strtotime($row['created_on'])) : '') . "</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['id_invoice']) . "</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['id_so'] ?? '') . "</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['id_penawaran'] ?? '') . "</div>";
            $nestedData[] = "<div class='text-center'>-</div>"; // Nomor PO belum ada di database
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['id_delivery'] ?? '') . "</div>";
            $nestedData[] = "<div class='text-center'>" . strtoupper($row['id_produk'] ?? '') . "</div>";
            $nestedData[] = "<div>" . ($row['nm_produk'] ?? '') . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($qty) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($costbook_so) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($costbook_invoice) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($penjualan_ppn) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($pendapatan) . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format($hpp) . "</div>";
            $nestedData[] = "<div class='text-center'>{$persen_hpp}%</div>";
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
            1  => 'i.created_on',
            2  => 'dt.id_invoice',
            3  => 'dt.id_so',
            4  => 'dt.id_penawaran',
            5  => 'dt.id_invoice',   // Nomor PO placeholder
            6  => 'dt.id_delivery',
            7  => 'dt.id_produk',
            8  => 'dt.nm_produk',
            9  => 'dt.qty',
            10 => 'sod.harga_beli',
            11 => 'dt.harga_beli',   // Costbook Invoice
            12 => 'dt.subtotal',     // Penjualan + PPN
            13 => 'dt.subtotal',     // Pendapatan (sortir pakai subtotal, hitungan /1.11 di PHP)
        ];

        $select = "
            dt.id,
            i.created_on,
            dt.id_invoice,
            dt.id_so,
            dt.id_penawaran,
            dt.id_delivery,
            dt.id_produk,
            dt.nm_produk,
            ROUND(dt.qty) AS qty,
            IFNULL(sod.harga_beli, 0) AS costbook_so,
            IFNULL(dt.harga_beli, 0)  AS costbook_invoice,
            dt.subtotal
        ";

        // Closure: apply joins
        $apply_joins = function () {
            $this->db->join('tr_invoice_sales i', 'i.id_invoice = dt.id_invoice', 'inner');
            $this->db->join('surat_jalan_detail sjd', 'sjd.no_surat_jalan = dt.id_delivery AND sjd.id_product = dt.id_produk', 'left');
            $this->db->join('sales_order_detail sod', 'sod.id = sjd.id_so_det', 'left');
        };

        // Closure: apply filters
        $apply_filters = function () use ($tgl_dari, $tgl_sampai) {
            $this->db->where('IFNULL(i.is_cancel, 0) =', 0, false);

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
                dt.id_invoice,
                dt.id_so,
                dt.id_penawaran,
                dt.id_delivery,
                dt.id_produk,
                dt.nm_produk,
                ROUND(dt.qty) AS qty,
                IFNULL(sod.harga_beli, 0) AS costbook_so,
                IFNULL(dt.harga_beli, 0)  AS costbook_invoice,
                dt.subtotal
            FROM tr_invoice_sales_detail dt
            INNER JOIN tr_invoice_sales i
                ON i.id_invoice = dt.id_invoice
            LEFT JOIN surat_jalan_detail sjd
                ON sjd.no_surat_jalan = dt.id_delivery
               AND sjd.id_product    = dt.id_produk
            LEFT JOIN sales_order_detail sod
                ON sod.id = sjd.id_so_det
            WHERE IFNULL(i.is_cancel, 0) = 0
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
