<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model Dashboard Penjualan & Piutang.
 *
 * Definisi (disamakan dengan modul lain di aplikasi):
 * - Penjualan   : SUM(tr_invoice_sales.grand_total) INCLUDE PPN, filter created_on (tanggal invoice),
 *                 invoice aktif = IFNULL(is_cancel,0) = 0 (sama dengan Report_margin_achievement).
 * - Sisa piutang: grand_total - (total_bayar_idr + total_cn_idr + pembulatan_idr) dari
 *                 tr_invoice_payment_detail (sama dengan Report_piutang / Report_piutang_aging).
 *                 Pembayaran yang dibatalkan (tr_invoice_payment.is_cancel = 'YES') tidak dihitung.
 * - Jatuh tempo : tr_invoice_sales.jatuh_tempo. Jika NULL: tanggal kirim (atau tanggal invoice)
 *                 + TOP hari dari sales_order.payment_term -> list_help.data1.
 * - Umur        : hari lewat jatuh tempo (hari ini - jatuh tempo), dipakai untuk chart aging.
 * - Late Debt   : umur 15-30 hari, Bad Debt: umur > 30 hari. Kartu & tabel "Jatuh Tempo"/"Macet"
 *                 memakai rumus Report_debt (lihat get_open_invoices).
 * - Tertagih    : SUM(total_bayar_idr) berdasarkan tgl_pembayaran pada bulan tersebut.
 * - Collection rate: tertagih bulan terpilih / penjualan bulan terpilih.
 */
class Dashboard_sbf_model extends BF_Model
{
    const MACET_HARI = 90;      // batas bucket aging chart
    const LATE_HARI_MIN = 15;   // Late Debt: 15-30 hari
    const BAD_HARI_MIN  = 31;   // Bad Debt: > 30 hari

    public function __construct()
    {
        parent::__construct();
    }

    /* ------------------------------------------------------------------
     * Piutang (posisi per hari ini)
     * ------------------------------------------------------------------ */

    /**
     * Semua invoice yang masih punya sisa piutang beserta hari lewat jatuh tempo.
     * Rumus disamakan dengan modul Report_debt (Report Late & Bad Debt per Sales):
     * - umur = hari ini - tr_invoice_sales.jatuh_tempo (tanpa jatuh tempo = belum jatuh tempo)
     * - sisa = grand_total - bayar - CN - pembulatan - CN retur (tr_retur jenis 2 status 2), min 0
     * - invoice aktif = is_cancel NULL atau 2
     * - hanya invoice milik customer yang punya sales (join employee)
     */
    public function get_open_invoices($today)
    {
        $sql = "
            SELECT
                a.id_invoice,
                a.id_customer,
                a.nm_customer,
                a.jatuh_tempo,
                e.nm_karyawan,
                bayar.tgl_terakhir_bayar,
                GREATEST(a.grand_total - COALESCE(bayar.total_bayar, 0) - COALESCE(bayar.total_cn, 0) - COALESCE(bayar.total_pembulatan, 0) - COALESCE(cn.total_cn_retur, 0), 0) AS sisa_piutang
            FROM tr_invoice_sales a
            INNER JOIN master_customers b ON a.id_customer = b.id_customer
            INNER JOIN employee e ON b.id_karyawan = e.id
            LEFT JOIN (
                SELECT pd.no_invoice,
                       SUM(pd.total_bayar_idr) AS total_bayar,
                       SUM(pd.total_cn_idr) AS total_cn,
                       SUM(pd.pembulatan_idr) AS total_pembulatan,
                       MAX(p.tgl_pembayaran) AS tgl_terakhir_bayar
                FROM tr_invoice_payment_detail pd
                INNER JOIN tr_invoice_payment p ON p.kd_pembayaran = pd.kd_pembayaran
                WHERE p.tgl_pembayaran <= ?
                GROUP BY pd.no_invoice
            ) bayar ON bayar.no_invoice = a.id_invoice
            LEFT JOIN (
                SELECT id_invoice, SUM(total_harga) AS total_cn_retur
                FROM tr_retur
                WHERE jenis_retur = 2 AND status = 2 AND tgl_retur <= ?
                GROUP BY id_invoice
            ) cn ON cn.id_invoice = a.id_invoice
            WHERE a.created_on <= ?
              AND (a.is_cancel IS NULL OR a.is_cancel = 2)
            HAVING sisa_piutang > 0
        ";

        $rows = $this->db->query($sql, [$today, $today, $today . ' 23:59:59'])->result_array();

        $today_ts = strtotime($today);
        foreach ($rows as &$r) {
            $r['sisa_piutang'] = (float) $r['sisa_piutang'];
            $r['hari_telat']   = 0;
            if (!empty($r['jatuh_tempo']) && strtotime($r['jatuh_tempo']) !== false) {
                $r['hari_telat'] = (int) floor(($today_ts - strtotime($r['jatuh_tempo'])) / 86400);
            }
        }
        unset($r);

        return $rows;
    }

    /**
     * Ringkasan piutang: total, per bucket aging, dan daftar tabel.
     * Kartu & tabel "jatuh tempo" = Late Debt (15-30 hr), "macet" = Bad Debt (>30 hr),
     * diambil dari rumus Report_debt.
     */
    public function get_piutang_summary($today, $limit = 10)
    {
        $rows = $this->get_open_invoices($today);

        $bucket_keys = ['belum_jt', 'b_1_30', 'b_31_60', 'b_61_90', 'b_90_plus'];
        $buckets     = array_fill_keys($bucket_keys, 0);

        $total        = 0;
        $jt_total     = 0;
        $jt_invoice   = 0;
        $jt_customers = [];
        $macet_total  = 0;
        $macet_inv    = 0;
        $jt_rows      = [];
        $macet_rows   = [];

        foreach ($rows as $r) {
            $h = $r['hari_telat'];
            $s = $r['sisa_piutang'];
            $total += $s;

            if ($h <= 0) {
                $buckets['belum_jt'] += $s;
                continue;
            }

            if ($h <= 30) {
                $buckets['b_1_30'] += $s;
            } elseif ($h <= 60) {
                $buckets['b_31_60'] += $s;
            } elseif ($h <= self::MACET_HARI) {
                $buckets['b_61_90'] += $s;
            } else {
                $buckets['b_90_plus'] += $s;
            }
        }

        // Late Debt (15-30 hr) & Bad Debt (>30 hr) dari rumus Report_debt
        foreach ($rows as $r) {
            $h = $r['hari_telat'];
            $s = $r['sisa_piutang'];

            if ($h < self::LATE_HARI_MIN) {
                continue;
            }

            $item = [
                'nm_customer'        => $r['nm_customer'],
                'id_invoice'         => $r['id_invoice'],
                'jatuh_tempo'        => $r['jatuh_tempo'],
                'hari_telat'         => $h,
                'sisa_piutang'       => $s,
                'nm_sales'           => $r['nm_karyawan'],
                'tgl_terakhir_bayar' => $r['tgl_terakhir_bayar'],
            ];

            if ($h >= self::BAD_HARI_MIN) {
                $macet_total += $s;
                $macet_inv++;
                $macet_rows[] = $item;
            } else {
                $jt_total += $s;
                $jt_invoice++;
                $jt_customers[$r['id_customer']] = true;
                $jt_rows[] = $item;
            }
        }

        // Jatuh tempo: nilai terbesar dulu. Macet: paling lama dulu.
        usort($jt_rows, function ($a, $b) {
            return $b['sisa_piutang'] <=> $a['sisa_piutang'];
        });
        usort($macet_rows, function ($a, $b) {
            return $b['hari_telat'] <=> $a['hari_telat'];
        });

        return [
            'total_piutang'     => $total,
            'total_invoice'     => count($rows),
            'jt_total'          => $jt_total,
            'jt_invoice'        => $jt_invoice,
            'jt_customer'       => count($jt_customers),
            'macet_total'       => $macet_total,
            'macet_invoice'     => $macet_inv,
            'buckets'           => $buckets,
            'jt_rows'           => array_slice($jt_rows, 0, $limit),
            'macet_rows'        => array_slice($macet_rows, 0, $limit),
        ];
    }

    /* ------------------------------------------------------------------
     * Penjualan & penagihan (per bulan)
     * ------------------------------------------------------------------ */

    /**
     * Penjualan (include PPN) per bulan dalam rentang [start, endExclusive).
     * @return array ['YYYY-MM' => float]
     */
    public function get_penjualan_per_bulan($start, $end_exclusive)
    {
        $sql = "
            SELECT DATE_FORMAT(created_on, '%Y-%m') AS ym, COALESCE(SUM(grand_total), 0) AS total
            FROM tr_invoice_sales
            WHERE created_on >= ? AND created_on < ?
              AND IFNULL(is_cancel, 0) = 0
            GROUP BY ym
        ";
        $map = [];
        foreach ($this->db->query($sql, [$start, $end_exclusive])->result_array() as $r) {
            $map[$r['ym']] = (float) $r['total'];
        }
        return $map;
    }

    /**
     * Penerimaan pembayaran (total_bayar_idr) per bulan dalam rentang [start, endExclusive).
     * @return array ['YYYY-MM' => float]
     */
    public function get_tertagih_per_bulan($start, $end_exclusive)
    {
        $sql = "
            SELECT DATE_FORMAT(p.tgl_pembayaran, '%Y-%m') AS ym, COALESCE(SUM(d.total_bayar_idr), 0) AS total
            FROM tr_invoice_payment_detail d
            INNER JOIN tr_invoice_payment p ON p.kd_pembayaran = d.kd_pembayaran
            WHERE p.tgl_pembayaran >= ? AND p.tgl_pembayaran < ?
              AND (p.is_cancel IS NULL OR p.is_cancel != 'YES')
            GROUP BY ym
        ";
        $map = [];
        foreach ($this->db->query($sql, [$start, $end_exclusive])->result_array() as $r) {
            $map[$r['ym']] = (float) $r['total'];
        }
        return $map;
    }

    /**
     * Jumlah sales order baru per bulan dalam rentang [start, endExclusive).
     * @return array ['YYYY-MM' => int]
     */
    public function get_order_per_bulan($start, $end_exclusive)
    {
        $sql = "
            SELECT DATE_FORMAT(tgl_so, '%Y-%m') AS ym, COUNT(DISTINCT no_so) AS jml
            FROM sales_order
            WHERE tgl_so >= ? AND tgl_so < ?
              AND IFNULL(status_so, '') != 'CLOSED'
            GROUP BY ym
        ";
        $map = [];
        foreach ($this->db->query($sql, [$start, $end_exclusive])->result_array() as $r) {
            $map[$r['ym']] = (int) $r['jml'];
        }
        return $map;
    }

    /**
     * Penjualan per kategori produk untuk 1 bulan.
     * Kategori = new_inventory_2 (Product Category), dihubungkan lewat
     * tr_invoice_sales_detail.id_produk -> new_inventory_4.code_lv4 -> code_lv2.
     * Nilai = SUM(detail.subtotal) (include PPN, sebelum diskon khusus header).
     * Kategori di luar top N digabung menjadi "Lainnya".
     */
    public function get_penjualan_kategori($bulan_no, $tahun, $limit = 5)
    {
        $start = sprintf('%04d-%02d-01', (int) $tahun, (int) $bulan_no);
        $end   = date('Y-m-d', strtotime($start . ' +1 month'));

        $sql = "
            SELECT COALESCE(n2.nama, 'Tanpa Kategori') AS kategori,
                   COALESCE(SUM(dt.subtotal), 0) AS total
            FROM tr_invoice_sales_detail dt
            INNER JOIN tr_invoice_sales i ON i.id_invoice = dt.id_invoice
            LEFT JOIN new_inventory_4 n4 ON n4.code_lv4 = dt.id_produk
            LEFT JOIN (
                SELECT code_lv2, MAX(nama) AS nama
                FROM new_inventory_2
                GROUP BY code_lv2
            ) n2 ON n2.code_lv2 = n4.code_lv2
            WHERE i.created_on >= ? AND i.created_on < ?
              AND IFNULL(i.is_cancel, 0) = 0
            GROUP BY kategori
            HAVING total > 0
            ORDER BY total DESC
        ";

        $rows = $this->db->query($sql, [$start, $end])->result_array();

        $out    = [];
        $lainnya = 0;
        foreach ($rows as $idx => $r) {
            if ($idx < $limit) {
                $out[] = ['kategori' => $r['kategori'], 'total' => (float) $r['total']];
            } else {
                $lainnya += (float) $r['total'];
            }
        }
        if ($lainnya > 0) {
            $out[] = ['kategori' => 'Lainnya', 'total' => $lainnya];
        }
        return $out;
    }

    /* ------------------------------------------------------------------
     * Top sales vs target (sama dengan Report_margin_achievement)
     * ------------------------------------------------------------------ */

    public function get_top_sales($bulan_no, $tahun, $limit = 5)
    {
        $bln_row  = $this->db->where('bulan_no', (int) $bulan_no)->get('cr_bulan')->row_array();
        $bulan_id = $bln_row['bulan_id'] ?? null;

        // Nama kolom dinamis: hanya huruf kecil, sisanya ditolak.
        $target_map = [];
        if (!empty($bulan_id) && preg_match('/^[a-z]+$/', $bulan_id)) {
            $rows = $this->db->select("id_karyawan, `{$bulan_id}` AS target_omset", false)
                ->from('target_penjualan')
                ->get()
                ->result_array();
            foreach ($rows as $t) {
                $target_map[$t['id_karyawan']] = (float) $t['target_omset'];
            }
        }

        $sql = "
            SELECT c.id_karyawan, e.nm_karyawan, SUM(i.grand_total) AS realisasi
            FROM tr_invoice_sales i
            INNER JOIN master_customers c ON c.id_customer = i.id_customer
            INNER JOIN employee e ON e.id = c.id_karyawan
            WHERE YEAR(i.created_on) = ?
              AND MONTH(i.created_on) = ?
              AND IFNULL(i.is_cancel, 0) = 0
              AND c.id_karyawan > 0
              AND e.deleted_date IS NULL
            GROUP BY c.id_karyawan, e.nm_karyawan
            ORDER BY realisasi DESC
            LIMIT " . (int) $limit;

        $out = [];
        foreach ($this->db->query($sql, [(int) $tahun, (int) $bulan_no])->result_array() as $r) {
            $target = $target_map[$r['id_karyawan']] ?? 0;
            $real   = (float) $r['realisasi'];
            $out[]  = [
                'nm_sales'  => $r['nm_karyawan'],
                'target'    => $target,
                'realisasi' => $real,
                'persen'    => $target > 0 ? round($real / $target * 100, 1) : null,
            ];
        }
        return $out;
    }
}
