<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Report_margin_achievement_model extends BF_Model
{

    public function __construct()
    {
        parent::__construct();

        $this->ENABLE_ADD     = has_permission('Report_Margin_Achievement.Add');
        $this->ENABLE_MANAGE  = has_permission('Report_Margin_Achievement.Manage');
        $this->ENABLE_VIEW    = has_permission('Report_Margin_Achievement.View');
        $this->ENABLE_DELETE  = has_permission('Report_Margin_Achievement.Delete');
    }

    /**
     * Ambil data Margin Achievement per Sales untuk 1 periode (bulan+tahun).
     *
     * Sumber data:
     * - Target Omset       : tabel target_penjualan (kolom dinamis sesuai bulan, mis. jan/feb/...)
     * - Realisasi Omset    : tr_invoice_sales join master_customers, filter bulan & tahun (delivery_date)
     * - Target Margin (%)  : tabel master_margin (id_sales, tahun, bulan)
     * - Realisasi Margin   : Revenue - HPP (Harga Pokok Penjualan / COGS), dihitung per baris invoice
     *   dari tr_invoice_sales_detail.subtotal dikurangi (qty x sales_order_detail.harga_beli),
     *   di-join lewat surat_jalan_detail. Sesuai rumus resmi dari BA:
     *   Margin (Gross Profit) = Revenue (Omset Penjualan) - HPP (Harga Pokok Penjualan/COGS)
     *
     * @param int $bulan_no 1-12
     * @param int $tahun
     * @return array ['rows' => [...], 'totals' => [...]]
     */
    public function get_data($bulan_no, $tahun)
    {
        // =========================
        // A) Semua sales aktif (employee, department = 2).
        // Aktif = deleted_date belum terisi (belum di-soft-delete lewat
        // Master_employee::delete()). Kolom 'status' Active/Non-Active TIDAK
        // dipakai di sini karena hanya menandai status kepegawaian, bukan
        // apakah record karyawan sudah dihapus.
        // =========================
        $allSales = $this->db->select('id, nm_karyawan')
            ->from('employee')
            ->where('department', '2')
            ->where('deleted_date', NULL)
            ->order_by('nm_karyawan', 'asc')
            ->get()
            ->result_array();

        // =========================
        // B) Cari kolom bulan (bulan_id) di tabel cr_bulan, mis. bulan_no=1 -> 'jan'
        // =========================
        $bln_row = $this->db->where('bulan_no', $bulan_no)->get('cr_bulan')->row_array();
        $bulan_id = $bln_row['bulan_id'] ?? null;

        // =========================
        // C) Target Omset per sales (dari target_penjualan, kolom dinamis sesuai bulan)
        // =========================
        $targetOmsetMap = [];
        if (!empty($bulan_id)) {
            $targetRows = $this->db->select("id_karyawan, `{$bulan_id}` as target_omset", false)
                ->from('target_penjualan')
                ->get()
                ->result_array();

            foreach ($targetRows as $t) {
                $targetOmsetMap[$t['id_karyawan']] = (float) $t['target_omset'];
            }
        }

        // =========================
        // D) Realisasi Omset per sales (dari tr_invoice_sales, filter bulan & tahun)
        // =========================
        $this->db->select('c.id_karyawan, SUM(i.grand_total) as realisasi_omset', false);
        $this->db->from('tr_invoice_sales i');
        $this->db->join('master_customers c', 'c.id_customer = i.id_customer', 'left');
        $this->db->where('YEAR(i.delivery_date)', $tahun);
        $this->db->where('MONTH(i.delivery_date)', $bulan_no);
        $this->db->where('IFNULL(i.is_cancel,0) =', 0, false);
        $this->db->where('c.id_karyawan >', 0);
        $this->db->group_by('c.id_karyawan');
        $realisasiRows = $this->db->get()->result_array();

        $realisasiOmsetMap = [];
        foreach ($realisasiRows as $r) {
            $realisasiOmsetMap[$r['id_karyawan']] = (float) $r['realisasi_omset'];
        }

        // =========================
        // D2) Realisasi Margin (Rp) riil per sales = Revenue - HPP (COGS)
        // Dihitung per baris invoice detail, HPP diambil dari sales_order_detail.harga_beli
        // (cost snapshot saat SO dibuat), disambungkan lewat surat_jalan_detail.
        // Pola JOIN ini sudah dipakai di modul retur_credit_note untuk jurnal HPP retur.
        // =========================
        $sqlMargin = "
            SELECT
                c.id_karyawan,
                SUM(dt.subtotal) AS revenue,
                SUM(dt.qty * IFNULL(sod.harga_beli, 0)) AS hpp,
                SUM(dt.subtotal - (dt.qty * IFNULL(sod.harga_beli, 0))) AS realisasi_margin_rp
            FROM tr_invoice_sales_detail dt
            INNER JOIN tr_invoice_sales i
                ON i.id_invoice = dt.id_invoice
            LEFT JOIN master_customers c
                ON c.id_customer = i.id_customer
            LEFT JOIN surat_jalan_detail sjd
                ON sjd.no_surat_jalan = dt.id_delivery
               AND sjd.id_product    = dt.id_produk
            LEFT JOIN sales_order_detail sod
                ON sod.id = sjd.id_so_det
            WHERE YEAR(i.delivery_date) = ?
              AND MONTH(i.delivery_date) = ?
              AND IFNULL(i.is_cancel, 0) = 0
              AND c.id_karyawan > 0
            GROUP BY c.id_karyawan
        ";
        $marginRealisasiRows = $this->db->query($sqlMargin, [$tahun, $bulan_no])->result_array();

        $realisasiMarginRpMap = [];
        foreach ($marginRealisasiRows as $m) {
            $realisasiMarginRpMap[$m['id_karyawan']] = (float) $m['realisasi_margin_rp'];
        }

        // =========================
        // E) Target Margin (%) per sales (dari master_margin)
        // =========================
        $marginRows = $this->db->select('id_sales, target_margin', false)
            ->from('master_margin')
            ->where('tahun', $tahun)
            ->where('bulan', $bulan_no)
            ->get()
            ->result_array();

        $targetMarginPctMap = [];
        foreach ($marginRows as $m) {
            $targetMarginPctMap[$m['id_sales']] = (float) $m['target_margin'];
        }

        // =========================
        // F) Gabungkan semua sales + hitung metrik turunan
        // =========================
        $rows = [];

        $totalTargetOmset      = 0;
        $totalRealisasiOmset   = 0;
        $totalTargetMarginRp   = 0;
        $totalRealisasiMarginRp = 0;

        foreach ($allSales as $s) {
            $id = $s['id'];

            $targetOmset     = $targetOmsetMap[$id] ?? 0;
            $realisasiOmset  = $realisasiOmsetMap[$id] ?? 0;
            $targetMarginPct = $targetMarginPctMap[$id] ?? 0;

            $pctAchOmset = $targetOmset > 0 ? ($realisasiOmset / $targetOmset) : 0;

            // DPP (Dasar Pengenaan Pajak) = Realisasi Omset (Rp) / 1,11 (menghilangkan PPN 11%)
            $dpp = $realisasiOmset / 1.11;

            $targetMarginRp    = $targetOmset * ($targetMarginPct / 100);
            $realisasiMarginRp = $realisasiMarginRpMap[$id] ?? 0;

            $marginPctThdOmset = $realisasiOmset > 0 ? ($realisasiMarginRp / $realisasiOmset) : 0;

            // % Ach Margin = Margin % thd Omset (Realisasi) / Target Margin %
            // (murni membandingkan rate margin aktual vs rate target, terlepas dari pencapaian omset)
            $pctAchMargin = $targetMarginPct > 0 ? ($marginPctThdOmset / ($targetMarginPct / 100)) : 0;

            if ($pctAchMargin >= 1) {
                $status = 'Tercapai';
            } elseif ($pctAchMargin >= 0.9) {
                $status = 'Mendekati Target';
            } else {
                $status = 'Belum Tercapai';
            }

            $rows[] = [
                'id_sales'             => $id,
                'nama_sales'           => strtoupper($s['nm_karyawan']),
                'target_omset'         => $targetOmset,
                'realisasi_omset'      => $realisasiOmset,
                'pct_ach_omset'        => $pctAchOmset,
                'dpp'                  => $dpp,
                'target_margin_rp'     => $targetMarginRp,
                'realisasi_margin_rp'  => $realisasiMarginRp,
                'pct_ach_margin'       => $pctAchMargin,
                'margin_pct_thd_omset' => $marginPctThdOmset,
                'target_margin_pct'    => $targetMarginPct,
                'status'               => $status,
            ];

            $totalTargetOmset       += $targetOmset;
            $totalRealisasiOmset    += $realisasiOmset;
            $totalTargetMarginRp    += $targetMarginRp;
            $totalRealisasiMarginRp += $realisasiMarginRp;
        }

        // Target Margin % gabungan (weighted average) = Total Target Margin Rp / Total Target Omset
        $totalTargetMarginPct = $totalTargetOmset > 0 ? ($totalTargetMarginRp / $totalTargetOmset) : 0;
        $totalMarginPctThdOmset = $totalRealisasiOmset > 0 ? ($totalRealisasiMarginRp / $totalRealisasiOmset) : 0;

        $totals = [
            'target_omset'         => $totalTargetOmset,
            'realisasi_omset'      => $totalRealisasiOmset,
            'pct_ach_omset'        => $totalTargetOmset > 0 ? ($totalRealisasiOmset / $totalTargetOmset) : 0,
            'dpp'                  => $totalRealisasiOmset / 1.11,
            'target_margin_rp'     => $totalTargetMarginRp,
            'realisasi_margin_rp'  => $totalRealisasiMarginRp,
            // % Ach Margin = Margin % thd Omset (Realisasi) / Target Margin % (weighted average)
            'pct_ach_margin'       => $totalTargetMarginPct > 0 ? ($totalMarginPctThdOmset / $totalTargetMarginPct) : 0,
            'margin_pct_thd_omset' => $totalMarginPctThdOmset,
        ];

        return [
            'rows'   => $rows,
            'totals' => $totals,
        ];
    }
}
