<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model Monitoring Kartu Hutang & Piutang
 *
 * Sumber data:
 *   - tr_kartu_hutang   (jenis = hutang)   -> pihak: supplier (nama_supplier)
 *   - tr_kartu_piutang  (jenis = piutang)  -> pihak: customer (nocust)
 *
 * Struktur kolom kedua tabel identik:
 *   id, tipe, nomor, tanggal, no_perkiraan, keterangan, jenis_trans, no_reff,
 *   debet, kredit, nocust, valid, waktu_valid, stspos, jenis_jurnal,
 *   id_supplier, nama_supplier, no_request, debet_usd, kredit_usd
 */
class Monitoring_kartu_model extends BF_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * DataTables server-side untuk mutasi kartu hutang/piutang.
     * Membaca parameter DataTables dari $_REQUEST dan meng-echo JSON.
     */
    public function data_serverside()
    {
        $requestData = $_REQUEST;
        $search    = isset($requestData['search']['value']) ? $requestData['search']['value'] : '';
        $col_order = isset($requestData['order'][0]['column']) ? $requestData['order'][0]['column'] : 0;
        $col_dir   = isset($requestData['order'][0]['dir']) ? $requestData['order'][0]['dir'] : 'asc';
        $start     = isset($requestData['start']) ? $requestData['start'] : 0;
        $length    = isset($requestData['length']) ? $requestData['length'] : 10;

        // Parameter filter tambahan (dikirim dari DataTables ajax.data)
        $jenis     = isset($requestData['jenis']) ? strtolower(trim($requestData['jenis'])) : 'hutang';
        $tgl_awal  = isset($requestData['tgl_awal']) ? trim($requestData['tgl_awal']) : '';
        $tgl_akhir = isset($requestData['tgl_akhir']) ? trim($requestData['tgl_akhir']) : '';

        if (!in_array($jenis, ['hutang', 'piutang'], true)) {
            $jenis = 'hutang';
        }
        $table    = ($jenis === 'hutang') ? 'tr_kartu_hutang' : 'tr_kartu_piutang';
        $nama_col = ($jenis === 'hutang') ? 'nama_supplier' : 'nocust';

        $columns_order = [
            0 => 'tanggal',
            1 => 'nomor',
            2 => 'no_reff',
            3 => 'jenis_trans',
            4 => $nama_col,
            5 => 'keterangan',
            6 => 'debet',
            7 => 'kredit',
        ];

        // ---- Total data (tanpa search, dengan filter tanggal) ----
        $this->db->from($table);
        $this->_apply_date($tgl_awal, $tgl_akhir);
        $totalData = $this->db->count_all_results();

        // ---- Total filtered ----
        $this->db->from($table);
        $this->_apply_date($tgl_awal, $tgl_akhir);
        $this->_apply_search($jenis, $search);
        $totalFiltered = $this->db->count_all_results();

        // ---- Data ----
        $this->db->select('tanggal, nomor, no_reff, jenis_trans, keterangan, debet, kredit, nocust, nama_supplier');
        $this->db->from($table);
        $this->_apply_date($tgl_awal, $tgl_akhir);
        $this->_apply_search($jenis, $search);

        if (isset($columns_order[$col_order])) {
            $this->db->order_by($columns_order[$col_order], $col_dir);
        } else {
            $this->db->order_by('tanggal', 'asc');
        }
        $this->db->order_by('id', 'asc');

        if ($length != -1) {
            $this->db->limit($length, $start);
        }
        $query = $this->db->get();

        $data = [];
        $urut = $start + 1;
        foreach (($query ? $query->result_array() : []) as $row) {
            $nama = ($jenis === 'hutang')
                ? trim($row['nama_supplier'] . '')
                : trim($row['nocust'] . '');

            $tgl = !empty($row['tanggal']) ? date('d/m/Y', strtotime($row['tanggal'])) : '';

            $nestedData   = [];
            $nestedData[] = "<div class='text-center'>{$urut}</div>";
            $nestedData[] = "<div class='text-center'>{$tgl}</div>";
            $nestedData[] = htmlspecialchars($row['nomor']);
            $nestedData[] = htmlspecialchars($row['no_reff']);
            $nestedData[] = htmlspecialchars($row['jenis_trans']);
            $nestedData[] = htmlspecialchars($nama);
            $nestedData[] = htmlspecialchars($row['keterangan'] . '');
            $nestedData[] = "<div class='text-right'>" . number_format((float)$row['debet'], 0, ',', '.') . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format((float)$row['kredit'], 0, ',', '.') . "</div>";

            $data[] = $nestedData;
            $urut++;
        }

        // ---- Total debet & kredit (seluruh data terfilter) ----
        $totals = $this->_get_totals($table, $jenis, $tgl_awal, $tgl_akhir, $search);

        $json_data = [
            "draw"            => intval(isset($requestData['draw']) ? $requestData['draw'] : 0),
            "recordsTotal"    => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data,
            "total_debet"     => (float)$totals['debet'],
            "total_kredit"    => (float)$totals['kredit'],
        ];

        echo json_encode($json_data);
    }

    /**
     * Total debet & kredit untuk seluruh data terfilter (dipakai footer).
     */
    public function get_totals($jenis, $tgl_awal, $tgl_akhir, $search = '')
    {
        $table = ($jenis === 'hutang') ? 'tr_kartu_hutang' : 'tr_kartu_piutang';
        return $this->_get_totals($table, $jenis, $tgl_awal, $tgl_akhir, $search);
    }

    private function _get_totals($table, $jenis, $tgl_awal, $tgl_akhir, $search = '')
    {
        $this->db->select('COALESCE(SUM(debet),0) AS debet, COALESCE(SUM(kredit),0) AS kredit');
        $this->db->from($table);
        $this->_apply_date($tgl_awal, $tgl_akhir);
        $this->_apply_search($jenis, $search);
        $q = $this->db->get();

        if (!$q) {
            return ['debet' => 0, 'kredit' => 0];
        }
        $r = $q->row_array();
        return ['debet' => (float)$r['debet'], 'kredit' => (float)$r['kredit']];
    }

    /**
     * Ambil semua baris untuk print / export (tanpa paging).
     */
    public function get_all($jenis, $tgl_awal, $tgl_akhir, $search = '')
    {
        $table = ($jenis === 'hutang') ? 'tr_kartu_hutang' : 'tr_kartu_piutang';

        $this->db->select('tanggal, nomor, no_reff, jenis_trans, keterangan, debet, kredit, nocust, nama_supplier');
        $this->db->from($table);
        $this->_apply_date($tgl_awal, $tgl_akhir);
        $this->_apply_search($jenis, $search);
        $this->db->order_by('tanggal', 'asc');
        $this->db->order_by('id', 'asc');
        $q = $this->db->get();

        $rows = [];
        foreach (($q ? $q->result_array() : []) as $r) {
            $rows[] = [
                'tanggal'     => $r['tanggal'],
                'nomor'       => $r['nomor'],
                'no_reff'     => $r['no_reff'],
                'jenis_trans' => $r['jenis_trans'],
                'keterangan'  => $r['keterangan'],
                'nama'        => ($jenis === 'hutang') ? trim($r['nama_supplier'] . '') : trim($r['nocust'] . ''),
                'debet'       => (float)$r['debet'],
                'kredit'      => (float)$r['kredit'],
            ];
        }
        return $rows;
    }

    private function _apply_date($tgl_awal, $tgl_akhir)
    {
        if (!empty($tgl_awal)) {
            $this->db->where('DATE(tanggal) >=', $tgl_awal);
        }
        if (!empty($tgl_akhir)) {
            $this->db->where('DATE(tanggal) <=', $tgl_akhir);
        }
    }

    private function _apply_search($jenis, $search)
    {
        if ($search === '' || $search === null) {
            return;
        }
        $this->db->group_start();
        $this->db->like('nomor', $search);
        $this->db->or_like('no_reff', $search);
        $this->db->or_like('jenis_trans', $search);
        $this->db->or_like('keterangan', $search);
        if ($jenis === 'hutang') {
            $this->db->or_like('nama_supplier', $search);
        } else {
            $this->db->or_like('nocust', $search);
        }
        $this->db->group_end();
    }
}
