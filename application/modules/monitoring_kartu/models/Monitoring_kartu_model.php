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
            2 => 'no_perkiraan',
            3 => 'no_reff',
            4 => 'jenis_trans',
            5 => $nama_col,
            6 => 'keterangan',
            7 => 'debet',
            8 => 'kredit',
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
        $this->db->select('id, tanggal, nomor, no_perkiraan, no_reff, jenis_trans, keterangan, debet, kredit, nocust, nama_supplier');
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
            $nestedData[] = "<div class='text-center'>" . htmlspecialchars($row['no_perkiraan']) . "</div>";
            $nestedData[] = htmlspecialchars($row['no_reff']);
            $nestedData[] = htmlspecialchars($row['jenis_trans']);
            $nestedData[] = htmlspecialchars($nama);
            $nestedData[] = htmlspecialchars($row['keterangan'] . '');
            $nestedData[] = "<div class='text-right'>" . number_format((float)$row['debet'], 0, ',', '.') . "</div>";
            $nestedData[] = "<div class='text-right'>" . number_format((float)$row['kredit'], 0, ',', '.') . "</div>";

            $btn_hapus = "<button type='button' class='btn btn-xs btn-danger btn-hapus' "
                . "data-id='" . (int)$row['id'] . "' title='Hapus'><i class='fa fa-trash'></i></button>";
            $nestedData[] = "<div class='text-center'>{$btn_hapus}</div>";

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
     * Pindahkan satu baris kartu ke tabel _deleted lalu hapus dari tabel asli.
     * Dijalankan dalam transaksi agar konsisten.
     *
     * @param  string $jenis  'hutang' | 'piutang'
     * @param  int    $id
     * @return array  ['status' => bool, 'message' => string]
     */
    public function arsip_hapus($jenis, $id)
    {
        $jenis = strtolower(trim($jenis));
        if (!in_array($jenis, ['hutang', 'piutang'], true)) {
            return ['status' => false, 'message' => 'Jenis kartu tidak valid.'];
        }

        $id = (int)$id;
        if ($id <= 0) {
            return ['status' => false, 'message' => 'ID tidak valid.'];
        }

        $table         = ($jenis === 'hutang') ? 'tr_kartu_hutang' : 'tr_kartu_piutang';
        $table_deleted = $table . '_deleted';

        // Ambil data yang akan dihapus
        $row = $this->db->where('id', $id)->get($table)->row_array();
        if (empty($row)) {
            return ['status' => false, 'message' => 'Data tidak ditemukan atau sudah dihapus.'];
        }

        // Metadata penghapusan (kolom opsional; hanya diisi bila ada di tabel _deleted)
        $meta = [
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $this->_current_user(),
        ];
        foreach ($meta as $col => $val) {
            if ($this->db->field_exists($col, $table_deleted)) {
                $row[$col] = $val;
            }
        }

        $this->db->trans_start();

        $this->db->insert($table_deleted, $row);
        $this->db->where('id', $id)->delete($table);

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return ['status' => false, 'message' => 'Gagal memindahkan data ke tabel deleted. Perubahan dibatalkan.'];
        }

        return ['status' => true, 'message' => 'Data berhasil dihapus dan dipindahkan ke arsip.'];
    }

    private function _current_user()
    {
        $ci = get_instance();
        if (isset($ci->auth) && method_exists($ci->auth, 'userdata')) {
            $ud = $ci->auth->userdata();
            if (is_object($ud) && isset($ud->username)) {
                return $ud->username;
            }
            if (is_array($ud) && isset($ud['username'])) {
                return $ud['username'];
            }
        }
        return null;
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

        $this->db->select('tanggal, nomor, no_perkiraan, no_reff, jenis_trans, keterangan, debet, kredit, nocust, nama_supplier');
        $this->db->from($table);
        $this->_apply_date($tgl_awal, $tgl_akhir);
        $this->_apply_search($jenis, $search);
        $this->db->order_by('tanggal', 'asc');
        $this->db->order_by('id', 'asc');
        $q = $this->db->get();

        $rows = [];
        foreach (($q ? $q->result_array() : []) as $r) {
            $rows[] = [
                'tanggal'      => $r['tanggal'],
                'nomor'        => $r['nomor'],
                'no_perkiraan' => $r['no_perkiraan'],
                'no_reff'      => $r['no_reff'],
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
        $this->db->or_like('no_perkiraan', $search);
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
