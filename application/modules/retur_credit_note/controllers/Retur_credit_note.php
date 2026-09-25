<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Retur_credit_note extends Admin_Controller
{
    protected $viewPermission   = 'Retur_credit_note.View';
    protected $addPermission    = 'Retur_credit_note.Add';
    protected $managePermission = 'Retur_credit_note.Manage';
    protected $deletePermission = 'Retur_credit_note.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->load->library(array('upload', 'Image_lib'));
        $this->load->model(array('Retur_credit_note/Retur_credit_note_model'));
        date_default_timezone_set('Asia/Bangkok');
    }

    // =========================================================
    // INDEX
    // =========================================================
    public function index()
    {
        $this->template->title('Retur Credit Note');
        $this->template->page_icon('fa fa-clipboard');
        $this->template->render('index');
    }

    public function data_side_inv()
    {
        $this->Retur_credit_note_model->data_side_inv();
    }

    // =========================================================
    // STEP 1: Request Retur (dari tombol di invoice_produk)
    // Hanya simpan request ke tr_retur, belum cancel invoice
    // =========================================================
    public function add($id_invoice)
    {
        // Cek apakah sudah ada request retur untuk invoice ini
        $existing = $this->db->get_where('tr_retur', ['id_invoice' => $id_invoice])->row();
        if ($existing) {
            // Sudah ada, redirect ke index dengan pesan
            $this->session->set_flashdata('warning', 'Invoice ini sudah memiliki request retur (No. ' . $existing->no_retur . ').');
            redirect('retur_credit_note');
        }

        $sql = "
            SELECT i.id_invoice, i.id_billing, i.id_so, sj.pengiriman,
                   i.id_customer, i.nm_customer
            FROM tr_invoice_sales i
            JOIN surat_jalan sj ON i.id_billing = sj.no_surat_jalan
            WHERE i.id_invoice = ?
        ";
        $inv = $this->db->query($sql, [$id_invoice])->row_array();

        // harga_beli diambil dari tr_invoice_sales_detail.harga_beli (dt.harga_beli), BUKAN dari
        // sales_order_detail.harga_beli. Nilai dt.harga_beli sudah dihitung saat invoice dibuat
        // via COALESCE(surat_jalan_detail.costbook, sales_order_detail.harga_beli) — costbook
        // adalah harga beli yang DIBEKUKAN saat SJ di-Save, dan nilai inilah yang benar-benar
        // dijurnal sebagai HPP di invoice. Retur harus membalikkan HPP yang SAMA persis dengan
        // yang tercatat di invoice, bukan menghitung ulang dari sales_order_detail (bisa berbeda
        // karena harga_beli di sana adalah snapshot lama saat SO dibuat, sebelum costbook beku).
        $sql2 = "
            SELECT dt.id_so, sjd.id_so_det, dt.id_penawaran, dt.id_delivery,
                   dt.id_produk, dt.nm_produk,
                   round(dt.qty) as qty,
                   dt.harga,
                   dt.harga_beli,
                   round(dt.qty * dt.harga) as total
            FROM tr_invoice_sales_detail dt
            JOIN surat_jalan_detail sjd
                ON dt.id_delivery = sjd.no_surat_jalan
                AND dt.id_produk  = sjd.id_product
            WHERE dt.id_invoice = ?
            ORDER BY dt.id_invoice
        ";
        $detail = $this->db->query($sql2, [$id_invoice])->result_array();

        $data = ['inv' => $inv, 'detail' => $detail];

        $this->template->title('Request Retur');
        $this->template->page_icon('fa fa-clipboard');
        $this->template->render('form_request', $data);
    }

    // =========================================================
    // STEP 1 SAVE: Simpan request retur (status=0)
    // =========================================================
    public function save_request()
    {
        $post        = $this->input->post();
        $id_invoice  = $post['id_invoice'];
        $tipe        = $post['pengiriman'];

        // Generate nomor retur
        $Ym = date('ym');
        $prefix = ($tipe == 'Pabrik') ? "CN/P/{$Ym}" : "CN/G/{$Ym}";
        $SQL    = "SELECT MAX(no_retur) as maxM FROM tr_retur WHERE no_retur LIKE '{$prefix}/%'";
        $result = $this->db->query($SQL)->row_array();
        $urutan = 0;
        if ($result['maxM']) {
            $parts  = explode('/', $result['maxM']);
            $urutan = isset($parts[3]) ? (int)$parts[3] : 0;
        }
        $urutan++;
        $no_retur = $prefix . '/' . sprintf('%04d', $urutan);

        $ArrHeader = [
            'no_retur'       => $no_retur,
            'no_surat_jalan' => $post['id_billing'],
            'no_so'          => $post['id_so'],
            'id_invoice'     => $id_invoice,
            'id_customer'    => $post['id_customer'],
            'nm_customer'    => $post['nm_customer'],
            'alasan'         => $post['alasan'],
            'tipe'           => $tipe,
            'total_harga'    => 0,
            'tgl_retur'      => date('Y-m-d', strtotime($post['tgl_retur'])),
            'created_by'     => $this->auth->user_id(),
            'created_date'   => date('Y-m-d H:i:s'),
            'status'         => 0,   // 0 = request masuk, belum ada SJ retur
            'jenis_retur'    => 2
        ];

        // Simpan detail item (qty masih dari invoice, belum final)
        $ArrDetail = [];
        foreach ($post['detail'] as $key => $value) {
            $ArrDetail[] = [
                'no_retur'       => $no_retur,
                'no_surat_jalan' => $post['id_billing'],
                'id_so_det'      => $value['id_so_det'],
                'id_product'     => $value['id_produk'],
                'nm_product'     => $value['nm_produk'],
                'qty_retur'      => (float)$value['qty'],
                'harga'          => (float)str_replace(',', '', $value['harga_raw']),
                'harga_beli'     => (float)str_replace(',', '', $value['harga_beli']),
                'total'          => (float)str_replace(',', '', $value['total_raw']),
                'created_by'     => $this->auth->user_id(),
                'created_date'   => date('Y-m-d H:i:s'),
            ];
        }

        $this->db->trans_begin();
        $this->db->insert('tr_retur', $ArrHeader);
        if (!empty($ArrDetail)) {
            $this->db->insert_batch('tr_retur_detail', $ArrDetail);
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            echo json_encode(['status' => 0, 'pesan' => 'Gagal menyimpan request retur.']);
        } else {
            $this->db->trans_commit();
            history("Request Retur: " . $no_retur);
            echo json_encode(['status' => 1, 'pesan' => 'Request retur berhasil disimpan. No. Retur: ' . $no_retur]);
        }
    }

    // =========================================================
    // STEP 2: Form Buat Surat Jalan Retur (Gudang, dept=2)
    // =========================================================
    public function form_sjr($id_retur)
    {
        // Cek department user — hanya gudang (dept_id=2), admin (user_id=7), atau yang punya permission Retur_credit_note.BuatSJR
        $user_dept = $this->_get_user_dept();
        $user_id   = $this->auth->user_id();
        if ($user_dept != 2 && $user_id != 7 && !has_permission('Retur_credit_note.BuatSJR')) {
            show_error('Akses ditolak. Hanya departemen Gudang yang dapat membuat Surat Jalan Retur.', 403);
        }

        $retur = $this->db
            ->select('r.*, i.id_billing as no_sj_asal')
            ->from('tr_retur r')
            ->join('tr_invoice_sales i', 'i.id_invoice = r.id_invoice', 'left')
            ->where('r.id', $id_retur)
            ->get()->row_array();

        if (!$retur || $retur['status'] != 0) {
            show_error('Data tidak ditemukan atau sudah diproses.', 404);
        }

        $detail = $this->db
            ->get_where('tr_retur_detail', ['no_retur' => $retur['no_retur']])
            ->result_array();

        $data = ['retur' => $retur, 'detail' => $detail];

        $this->template->title('Buat Surat Jalan Retur');
        $this->template->page_icon('fa fa-truck');
        $this->template->render('form_sjr', $data);
    }

    // =========================================================
    // STEP 2 SAVE: Simpan Surat Jalan Retur (status tr_retur → 1)
    // =========================================================
    public function save_sjr()
    {
        $user_dept = $this->_get_user_dept();
        $user_id   = $this->auth->user_id();
        if ($user_dept != 2 && $user_id != 7 && !has_permission('Retur_credit_note.BuatSJR')) {
            echo json_encode(['status' => 0, 'pesan' => 'Akses ditolak.']);
            return;
        }

        $post       = $this->input->post();
        $no_retur   = $post['no_retur'];
        $no_sj_asal = $post['no_sj_asal'];
        $no_sjr     = $no_sj_asal . 'R';
        $no_invoice = $post['no_invoice'];

        // Cek duplikat no_sjr
        $cek = $this->db->get_where('surat_jalan_retur', ['no_sjr' => $no_sjr])->num_rows();
        if ($cek > 0) {
            // Tambah suffix angka jika sudah ada
            $cek2 = $this->db->like('no_sjr', $no_sj_asal . 'R', 'after')->count_all_results('surat_jalan_retur');
            $no_sjr = $no_sj_asal . 'R' . ($cek2 + 1);
        }

        $ArrHeader = [
            'no_sjr'       => $no_sjr,
            'no_retur'     => $no_retur,
            'no_sj_asal'   => $no_sj_asal,
            'no_invoice'   => $no_invoice,
            'no_so'        => $post['no_so'],
            'id_customer'  => $post['id_customer'],
            'nm_customer'  => $post['nm_customer'],
            'tgl_sjr'      => date('Y-m-d', strtotime($post['tgl_sjr'])),
            'keterangan'   => $post['keterangan'],
            'created_by'   => $this->auth->user_id(),
            'created_date' => date('Y-m-d H:i:s'),
        ];

        $ArrDetail = [];
        // harga_beli per id_product disimpan di sini SAJA untuk keperluan jurnal & kartu_stok —
        // kolom ini tidak ada di tabel surat_jalan_retur_detail sehingga tidak ikut di-insert.
        // Nilainya berasal dari tr_retur_detail.harga_beli, yang sudah konsisten dengan
        // tr_invoice_sales_detail.harga_beli (HPP yang dijurnal saat invoice dibuat) — lihat
        // catatan di method add(). Dengan ini, jurnal SJR dan kartu_stok "Retur/lebih" memakai
        // cost yang SAMA persis dengan yang dibalik dari invoice, bukan harga_beli warehouse_stock
        // saat ini yang bisa sudah berubah karena revisi manual di Product Costing.
        $hargaBeliMap = [];
        $total_harga_beli = 0;
        foreach ($post['detail'] as $value) {
            $qty_retur = (float)$value['qty_retur'];
            if ($qty_retur <= 0) continue;
            $harga      = (float)str_replace(',', '', $value['harga_raw']);
            $harga_beli = (float)str_replace(',', '', $value['harga_beli']);
            $ArrDetail[] = [
                'no_sjr'       => $no_sjr,
                'no_retur'     => $no_retur,
                'id_product'   => $value['id_product'],
                'nm_product'   => $value['nm_product'],
                'qty_retur'    => $qty_retur,
                'harga'        => $harga,
                'total'        => $qty_retur * $harga,
                'id_so_det'    => $value['id_so_det'],
                'created_by'   => $this->auth->user_id(),
                'created_date' => date('Y-m-d H:i:s'),
            ];
            $hargaBeliMap[$value['id_product']] = $harga_beli;
            $total_harga_beli += $qty_retur * $harga_beli;
        }

        if (empty($ArrDetail)) {
            echo json_encode(['status' => 0, 'pesan' => 'Tidak ada item dengan qty retur > 0.']);
            return;
        }

        $tgl_sjr_stok = date('Y-m-d', strtotime($post['tgl_sjr']));

        $this->db->trans_begin();

        // =========================================================
        // Preload warehouse_stock (FOR UPDATE) untuk produk yang diretur,
        // supaya update qty_stock/qty_free di bawah tidak race condition.
        // =========================================================
        $productIds = array_values(array_unique(array_column($ArrDetail, 'id_product')));
        $stockMap = [];
        if (!empty($productIds)) {
            $ids_escaped = array_map(function ($id) {
                return $this->db->escape($id);
            }, $productIds);
            $ids_str = implode(',', $ids_escaped);
            $stocks = $this->db->query(
                "SELECT * FROM warehouse_stock WHERE id_material IN ({$ids_str}) AND id_gudang = 1 AND kd_gudang = 'PUS' FOR UPDATE"
            )->result_array();
            foreach ($stocks as $s) {
                $stockMap[$s['id_material']] = $s;
            }
        }

        $this->db->insert('surat_jalan_retur', $ArrHeader);
        $this->db->insert_batch('surat_jalan_retur_detail', $ArrDetail);
        // Update status tr_retur → 1 (SJ Retur sudah dibuat), simpan no_sjr
        $this->db->update('tr_retur', ['status' => 1, 'no_sjr' => $no_sjr], ['no_retur' => $no_retur]);

        // =========================================================
        // STOK: Barang retur kembali secara fisik ke gudang saat SJ Retur
        // dibuat (gudang sudah konfirmasi qty yang benar-benar diterima).
        // Mengikuti filosofi "Retur/lebih" pada Confirm Surat Jalan:
        //   qty_stock   : ditambah qty_retur (barang balik ke gudang)
        //   qty_booking : tidak berubah (sudah dilepas saat SJ asal dibuat)
        //   qty_free    : naik sesuai barang yang balik
        // =========================================================
        $arr_kartu_stok_sjr = [];
        foreach ($ArrDetail as $item) {
            $qty_retur  = (float)$item['qty_retur'];
            $id_product = $item['id_product'];

            if (empty($stockMap[$id_product])) {
                $this->db->trans_rollback();
                echo json_encode(['status' => 0, 'pesan' => "Stok warehouse tidak ditemukan untuk produk {$item['nm_product']} ({$id_product})."]);
                return;
            }

            $stok        = $stockMap[$id_product];
            $old_stock   = (float)$stok['qty_stock'];
            $old_booking = (float)$stok['qty_booking'];
            $old_free    = (float)$stok['qty_free'];

            $new_stock   = $old_stock + $qty_retur;
            $new_booking = $old_booking; // booking tidak berubah
            $new_free    = $new_stock - $new_booking;

            $this->db->set('qty_stock', 'qty_stock + ' . $qty_retur, FALSE);
            $this->db->set('qty_free',  'qty_free + '  . $qty_retur, FALSE);
            $this->db->where('id_material', $id_product);
            $this->db->where('id_gudang', 1);
            $this->db->where('kd_gudang', 'PUS');
            $this->db->update('warehouse_stock');

            // Guard: kalau UPDATE di atas tidak mengubah baris apapun, jangan diam-diam
            // lanjut — batalkan transaksi supaya tidak terjadi selisih stok.
            if ($this->db->affected_rows() == 0) {
                $this->db->trans_rollback();
                echo json_encode(['status' => 0, 'pesan' => "Gagal mengembalikan stok untuk produk {$item['nm_product']} ({$id_product})."]);
                return;
            }

            // Update map lokal agar akurat jika ada produk yang sama di baris lain
            $stockMap[$id_product]['qty_stock'] = $new_stock;
            $stockMap[$id_product]['qty_free']  = $new_free;

            $arr_kartu_stok_sjr[] = [
                'no_transaksi'   => $no_sjr,
                'transaksi'      => 'Retur/lebih',
                'tgl_transaksi'  => $tgl_sjr_stok,
                'code_lv4'       => $id_product,
                'nm_product'     => $item['nm_product'],
                'qty'            => $old_stock,
                'qty_book'       => $old_booking,
                'qty_free'       => $old_free,
                'qty_transaksi'  => $qty_retur,
                'qty_akhir'      => $new_stock,
                'qty_book_akhir' => $new_booking,
                'qty_free_akhir' => $new_free,
                // harga_stok pakai harga_beli dari invoice (konsisten dengan jurnal), BUKAN
                // warehouse_stock.harga_beli saat ini (bisa berbeda karena revisi Product Costing).
                'harga_stok'     => $hargaBeliMap[$id_product] ?? 0,
            ];
        }

        if (!empty($arr_kartu_stok_sjr)) {
            $this->db->insert_batch('kartu_stok', $arr_kartu_stok_sjr);
        }

        // =========================================================
        // JURNAL: Saat buat Surat Jalan Retur
        // Barang retur balik ke gudang secara fisik — ini kebalikan dari jurnal saat
        // invoice dibuat (Debit HPP / Kredit Persediaan atas barang yang terjual).
        // Nilai qty * harga_beli sama persis dengan rumus & sumber di invoice
        // (tr_invoice_sales_detail.harga_beli, lihat catatan di method add()).
        // Debit  : 1104-01-01 Persediaan Barang Warehouse (barang fisik bertambah)
        // Kredit : 5101-01-01 HPP                         (membalik beban HPP yang batal)
        // =========================================================
        if ($total_harga_beli > 0) {
            $this->load->model('jurnal_nomor/Jurnal_model');
            $tgl_sjr    = date('Y-m-d', strtotime($post['tgl_sjr']));
            $Nomor_JV   = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', $tgl_sjr);
            $keterangan = "SJ Retur {$no_sjr} asal SJ {$no_sj_asal} atas INV {$no_invoice}";

            $this->db->insert(DBACC . '.javh', [
                'nomor'         => $Nomor_JV,
                'tgl'           => $tgl_sjr,
                'jml'           => $total_harga_beli,
                'koreksi_no'    => '-',
                'kdcab'         => '101',
                'jenis'         => 'JV',
                'keterangan'    => $keterangan,
                'bulan'         => date('m', strtotime($tgl_sjr)),
                'tahun'         => date('Y', strtotime($tgl_sjr)),
                'user_id'       => $this->auth->user_id(),
                'memo'          => '',
                'tgl_jvkoreksi' => $tgl_sjr,
                'ho_valid'      => ''
            ]);

            $this->db->insert_batch(DBACC . '.jurnal', [
                [
                    'tipe'         => 'JV',
                    'nomor'        => $Nomor_JV,
                    'tanggal'      => $tgl_sjr,
                    'no_perkiraan' => '1104-01-01',
                    'keterangan'   => $keterangan,
                    'no_reff'      => $no_sjr,
                    'debet'        => $total_harga_beli,
                    'kredit'       => 0,
                    'created_by'   => $this->auth->user_id(),
                    'created_on'   => date('Y-m-d H:i:s'),
                ],
                [
                    'tipe'         => 'JV',
                    'nomor'        => $Nomor_JV,
                    'tanggal'      => $tgl_sjr,
                    'no_perkiraan' => '5101-01-01',
                    'keterangan'   => $keterangan,
                    'no_reff'      => $no_sjr,
                    'debet'        => 0,
                    'kredit'       => $total_harga_beli,
                    'created_by'   => $this->auth->user_id(),
                    'created_on'   => date('Y-m-d H:i:s'),
                ],
            ]);

            $this->db->query("UPDATE " . DBACC . ".pastibisa_tb_cabang SET nomorJC=nomorJC+1 WHERE nocab='101'");
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            echo json_encode(['status' => 0, 'pesan' => 'Gagal menyimpan Surat Jalan Retur.']);
        } else {
            $this->db->trans_commit();
            history("Buat SJ Retur: " . $no_sjr);
            echo json_encode(['status' => 1, 'pesan' => 'Surat Jalan Retur ' . $no_sjr . ' berhasil disimpan.']);
        }
    }

    // =========================================================
    // STEP 3: Form Credit Note (Finance, dept=3)
    // Qty diambil dari SJ Retur yang sudah dibuat gudang
    // =========================================================
    public function form_cn($id_retur)
    {
        $user_dept = $this->_get_user_dept();
        $user_id   = $this->auth->user_id();
        if ($user_dept != 3 && $user_id != 7 && !has_permission('Retur_credit_note.BuatCN')) {
            show_error('Akses ditolak. Hanya departemen Finance yang dapat membuat Credit Note.', 403);
        }

        $retur = $this->db
            ->select('r.*')
            ->from('tr_retur r')
            ->where('r.id', $id_retur)
            ->get()->row_array();

        if (!$retur || $retur['status'] != 1) {
            show_error('Data tidak ditemukan atau belum ada Surat Jalan Retur.', 404);
        }

        // Ambil detail dari SJ Retur (qty sudah final dari gudang)
        // Join ke tr_retur_detail via no_retur yang kini tersimpan di surat_jalan_retur_detail
        // COLLATE diperlukan karena kedua tabel beda collation (utf8mb4_general_ci vs utf8mb4_0900_ai_ci)
        $detail_query = $this->db->query("
            SELECT sjrd.*, trd.harga_beli
            FROM surat_jalan_retur_detail sjrd
            LEFT JOIN tr_retur_detail trd
                ON trd.no_retur COLLATE utf8mb4_general_ci = sjrd.no_retur
                AND trd.id_product COLLATE utf8mb4_general_ci = sjrd.id_product
            WHERE sjrd.no_sjr = ?
        ", [$retur['no_sjr']]);
        $detail = $detail_query ? $detail_query->result_array() : [];

        // Ambil data invoice untuk hitung total sudah bayar
        $inv = $this->db->get_where('tr_invoice_sales', ['id_invoice' => $retur['id_invoice']])->row_array();

        $bayar_query = $this->db
            ->select('COALESCE(SUM(total_bayar_idr), 0) AS total', false)
            ->from('tr_invoice_payment_detail')
            ->where('no_invoice', $retur['id_invoice'])
            ->get();
        $total_sudah_bayar = ($bayar_query && $bayar_query->row()) ? (float)$bayar_query->row()->total : 0.0;

        $grand_total_inv = (float)($inv['grand_total'] ?? 0);

        // Preview nilai jurnal Pendapatan Penjualan (4102-01-01) & PPN Keluaran (2103-01-01)
        // — dihitung dengan cara yang SAMA PERSIS dengan save_cn()/_buat_jurnal_credit_note(),
        // supaya preview yang ditampilkan di form konsisten dengan yang benar-benar disimpan.
        // Lihat catatan lengkap di _buat_jurnal_credit_note() soal kenapa proporsi dari jurnal
        // invoice asli dipakai, bukan re-derivasi rumus 11/12.
        $subtotal_invoice = (float) $this->db
            ->select_sum('subtotal')
            ->from('tr_invoice_sales_detail')
            ->where('id_invoice', $retur['id_invoice'])
            ->get()->row()->subtotal;

        $jurnal_invoice = $this->db
            ->select('no_perkiraan, SUM(kredit) as total_kredit')
            ->from(DBACC . '.jurnal')
            ->where('no_reff', $retur['id_invoice'])
            ->where_in('no_perkiraan', ['4101-01-01', '2103-01-01'])
            ->group_by('no_perkiraan')
            ->get()->result_array();

        $pendapatan_invoice = 0;
        $ppn_invoice        = 0;
        foreach ($jurnal_invoice as $j) {
            if ($j['no_perkiraan'] === '4101-01-01') $pendapatan_invoice = (float) $j['total_kredit'];
            if ($j['no_perkiraan'] === '2103-01-01') $ppn_invoice        = (float) $j['total_kredit'];
        }

        $data = [
            'retur'              => $retur,
            'inv'                => $inv,
            'detail'             => $detail,
            'total_sudah_bayar'  => $total_sudah_bayar,
            'grand_total_inv'    => $grand_total_inv,
            'subtotal_invoice'   => $subtotal_invoice,
            'pendapatan_invoice' => $pendapatan_invoice,
            'ppn_invoice'        => $ppn_invoice,
        ];

        $this->template->title('Buat Credit Note');
        $this->template->page_icon('fa fa-clipboard');
        $this->template->render('form_cn', $data);
    }

    // =========================================================
    // STEP 3 SAVE: Simpan Credit Note (status tr_retur → 2)
    // =========================================================
    public function save_cn()
    {
        $user_dept = $this->_get_user_dept();
        $user_id   = $this->auth->user_id();
        if ($user_dept != 3 && $user_id != 7 && !has_permission('Retur_credit_note.BuatCN')) {
            echo json_encode(['status' => 0, 'pesan' => 'Akses ditolak.']);
            return;
        }

        $post              = $this->input->post();
        $no_retur          = $post['no_retur'];
        $id_invoice_lama   = $post['id_invoice'];
        $grand_total_retur = (float)str_replace(',', '', $post['grand_total']);

        $inv_lama = $this->db->get_where('tr_invoice_sales', ['id_invoice' => $id_invoice_lama])->row();
        if (!$inv_lama) {
            echo json_encode(['status' => 0, 'pesan' => 'Invoice tidak ditemukan.']);
            return;
        }

        // Ambil subtotal invoice (SUM qty*harga semua item, basis SEBELUM PPN ditambahkan)
        // sebagai pembagi rasio retur — dipakai untuk memproporsikan nilai Pendapatan
        // Penjualan & PPN Keluaran yang SUDAH dijurnal saat invoice dibuat, bukan menghitung
        // ulang dari rumus 11/12 (rawan mismatch rounding dengan nilai asli invoice, karena
        // grand_total_retur = qty_retur * harga TIDAK identik dengan subtotal invoice per-unit
        // yang dipakai rumus itu — lihat catatan panjang di _buat_jurnal_credit_note()).
        $subtotal_invoice = (float) $this->db
            ->select_sum('subtotal')
            ->from('tr_invoice_sales_detail')
            ->where('id_invoice', $id_invoice_lama)
            ->get()->row()->subtotal;

        // Ambil nilai Pendapatan Penjualan (4101-01-01) & PPN Keluaran (2103-01-01) yang
        // BENAR-BENAR dijurnal saat invoice dibuat — sumber paling akurat, tidak menghitung
        // ulang dari rumus PPN yang bisa berbeda hasil rounding-nya.
        $jurnal_invoice = $this->db
            ->select('no_perkiraan, SUM(kredit) as total_kredit')
            ->from(DBACC . '.jurnal')
            ->where('no_reff', $id_invoice_lama)
            ->where_in('no_perkiraan', ['4101-01-01', '2103-01-01'])
            ->group_by('no_perkiraan')
            ->get()->result_array();

        $pendapatan_invoice = 0;
        $ppn_invoice        = 0;
        foreach ($jurnal_invoice as $j) {
            if ($j['no_perkiraan'] === '4101-01-01') $pendapatan_invoice = (float) $j['total_kredit'];
            if ($j['no_perkiraan'] === '2103-01-01') $ppn_invoice        = (float) $j['total_kredit'];
        }

        $this->db->trans_begin();

        try {
            // Update tr_retur: simpan total_harga dan status=2
            // Nilai invoice TIDAK diubah — piutang tetap sama, CN hanya dicatat
            $this->db->update('tr_retur', [
                'total_harga' => $grand_total_retur,
                'tgl_retur'   => date('Y-m-d', strtotime($post['tgl_retur'])),
                'status'      => 2,
            ], ['no_retur' => $no_retur]);

            // Tandai invoice bahwa ada credit note (is_cancel=2), tanpa mengubah grand_total/piutang/sts
            $this->db->update('tr_invoice_sales', [
                'is_cancel'  => 2,
                'updated_by' => $this->auth->user_id(),
                'updated_on' => date('Y-m-d H:i:s'),
            ], ['id_invoice' => $id_invoice_lama]);

            // Jurnal koreksi
            $this->_buat_jurnal_credit_note(
                $no_retur,
                $post['tgl_retur'],
                $id_invoice_lama,
                $inv_lama->id_customer,
                $inv_lama->nm_customer,
                $grand_total_retur,
                $subtotal_invoice,
                $pendapatan_invoice,
                $ppn_invoice
            );

            if ($this->db->trans_status() === FALSE) {
                throw new Exception('DB Error.');
            }

            $this->db->trans_commit();
            history("Credit Note: " . $no_retur);

            echo json_encode(['status' => 1, 'pesan' => 'Credit Note ' . $no_retur . ' berhasil disimpan. Nilai piutang invoice tidak berubah.']);
        } catch (Exception $e) {
            $this->db->trans_rollback();
            echo json_encode(['status' => 0, 'pesan' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    // =========================================================
    // VIEW detail retur
    // =========================================================
    public function view($id)
    {
        $inv = $this->db->query("SELECT * FROM tr_retur WHERE id = ?", [$id])->row_array();
        $detail = $this->db->query("SELECT * FROM tr_retur_detail WHERE no_retur = ?", [$inv['no_retur']])->result_array();

        // Ambil detail SJ Retur jika sudah ada
        $detail_sjr = [];
        if (!empty($inv['no_sjr'])) {
            $detail_sjr = $this->db->get_where('surat_jalan_retur_detail', ['no_sjr' => $inv['no_sjr']])->result_array();
        }

        $data = ['inv' => $inv, 'detail' => $detail, 'detail_sjr' => $detail_sjr];

        $this->template->title('View Credit Note');
        $this->template->page_icon('fa fa-clipboard');
        $this->template->render('view', $data);
    }

    // =========================================================
    // AJAX: History CN untuk penerimaan
    // =========================================================
    public function get_cn_history()
    {
        $id_invoice = $this->input->get('id_invoice', TRUE);
        if (!$id_invoice) {
            echo json_encode([]);
            return;
        }

        $data = $this->db
            ->select('r.no_retur, r.tgl_retur, r.total_harga as nilai_retur, r.nilai_inv_baru, r.nm_customer, r.alasan, r.status')
            ->from('tr_retur r')
            ->where('r.id_invoice', $id_invoice)
            ->order_by('r.tgl_retur', 'ASC')
            ->get()->result_array();

        // Ambil nilai invoice asal dari kolom nilai_asli
        $inv = $this->db
            ->select('nilai_asli')
            ->from('tr_invoice_sales')
            ->where('id_invoice', $id_invoice)
            ->get()->row_array();

        echo json_encode([
            'nilai_inv_asal' => (float)($inv['nilai_asli'] ?? 0),
            'rows'           => $data,
        ]);
    }

    // =========================================================
    // PRIVATE: Jurnal koreksi credit note
    // Retur mengurangi piutang & pendapatan yang sudah dicatat saat invoice — arah jurnal
    // adalah KEBALIKAN dari invoice normal (Debit Piutang / Kredit Penjualan / Kredit PPN
    // Keluaran), bukan diulang dengan arah yang sama. Dikonfirmasi dari pola CN yang sudah
    // benar di Penerimaan_cash.php ("Kredit piutang dari CN") dan tr_kartu_piutang di bawah
    // method ini yang sudah mengkredit 1102-01-01.
    //
    // PENTING: nilai 4102-01-01 (Retur Penjualan) BUKAN qty*harga_beli — itu adalah HPP,
    // sudah dijurnal terpisah di save_sjr() (akun 5101-01-01/1104-01-01).
    //
    // Nilai 4102-01-01 & 2103-01-01 TIDAK dihitung ulang dengan rumus 11/12 dari
    // grand_total_retur — sempat dicoba tapi hasilnya MISMATCH dengan nilai asli di jurnal
    // invoice (rumus invoice membagi 1.11 dari SUBTOTAL sebelum PPN ditambahkan, sedangkan
    // grand_total_retur dari tr_retur_detail/surat_jalan_retur_detail sudah setara subtotal
    // itu sendiri — membaginya lagi dengan 1.11 menghasilkan angka yang sedikit berbeda akibat
    // rounding, contoh kasus CN/G/2609/0004: rumus 11/12 hasilkan 1.117.117 sedangkan nilai
    // asli invoice 1.117.568).
    //
    // Pendekatan yang benar: ambil LANGSUNG nilai Pendapatan Penjualan (4101-01-01) & PPN
    // Keluaran (2103-01-01) yang SUDAH dijurnal saat invoice dibuat, lalu proporsikan sesuai
    // rasio nilai retur (subtotal qty*harga) terhadap subtotal invoice — supaya retur benar-
    // benar membalik persis nilai yang sudah tercatat, bukan re-derivasi yang bisa meleset.
    // Kredit : 1102-01-01 Piutang Dagang     = grand_total_retur (qty * harga include PPN) — piutang berkurang
    // Debit  : 4102-01-01 Retur Penjualan    = pendapatan_invoice * rasio                   — contra-revenue
    // Debit  : 2103-01-01 PPN Keluaran       = ppn_invoice * rasio                          — kewajiban PPN berkurang
    // =========================================================
    private function _buat_jurnal_credit_note($no_retur, $tgl_retur, $id_invoice, $id_customer, $nm_customer, $nilai_retur, $subtotal_invoice = 0, $pendapatan_invoice = 0, $ppn_invoice = 0)
    {
        $this->load->model('jurnal_nomor/Jurnal_model');
        $tgl        = date('Y-m-d', strtotime($tgl_retur));
        $Nomor_JV   = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', $tgl);
        $keterangan = "Credit Note {$no_retur} atas Invoice {$id_invoice} A/n {$nm_customer}";

        // Hitung komponen jurnal
        $nilai_piutang = $nilai_retur; // Kredit Piutang Dagang (piutang berkurang)

        if ($subtotal_invoice > 0 && $pendapatan_invoice > 0) {
            // Jalur utama: proporsikan dari nilai yang sudah dijurnal di invoice
            $rasio              = $nilai_retur / $subtotal_invoice;
            $nilai_retur_penj   = $pendapatan_invoice * $rasio;   // Debit Retur Penjualan
            $nilai_ppn_keluaran = $ppn_invoice * $rasio;          // Debit PPN Keluaran
        } else {
            // Fallback: invoice lama yang jurnalnya tidak ditemukan (misal data sebelum
            // fitur ini ada) — pakai rumus 11/12 sebagai pendekatan, meski bisa sedikit
            // berbeda dari nilai asli invoice akibat rounding.
            $excludeppn_retur   = $nilai_retur / 1.11;
            $dpp_retur          = $excludeppn_retur * 11 / 12;
            $nilai_ppn_keluaran = $dpp_retur * 12 / 100;
            $nilai_retur_penj   = $excludeppn_retur;
        }

        $this->db->insert(DBACC . '.javh', [
            'nomor'         => $Nomor_JV,
            'tgl'           => $tgl,
            'jml'           => $nilai_piutang,
            'koreksi_no'    => '-',
            'kdcab'         => '101',
            'jenis'         => 'JV',
            'keterangan'    => $keterangan,
            'bulan'         => date('m', strtotime($tgl)),
            'tahun'         => date('Y', strtotime($tgl)),
            'user_id'       => $this->auth->user_id(),
            'memo'          => '',
            'tgl_jvkoreksi' => $tgl,
            'ho_valid'      => ''
        ]);

        $this->db->insert_batch(DBACC . '.jurnal', [
            // Kredit: Piutang Dagang (piutang customer berkurang)
            [
                'tipe'         => 'JV',
                'nomor'        => $Nomor_JV,
                'tanggal'      => $tgl,
                'no_perkiraan' => '1102-01-01',
                'keterangan'   => $keterangan,
                'no_reff'      => $no_retur,
                'debet'        => 0,
                'kredit'       => $nilai_piutang,
                'created_by'   => $this->auth->user_id(),
                'created_on'   => date('Y-m-d H:i:s'),
            ],
            // Debit: Retur Penjualan (contra-revenue, mengurangi penjualan)
            [
                'tipe'         => 'JV',
                'nomor'        => $Nomor_JV,
                'tanggal'      => $tgl,
                'no_perkiraan' => '4102-01-01',
                'keterangan'   => $keterangan,
                'no_reff'      => $no_retur,
                'debet'        => $nilai_retur_penj,
                'kredit'       => 0,
                'created_by'   => $this->auth->user_id(),
                'created_on'   => date('Y-m-d H:i:s'),
            ],
            // Debit: PPN Keluaran (kewajiban PPN berkurang)
            [
                'tipe'         => 'JV',
                'nomor'        => $Nomor_JV,
                'tanggal'      => $tgl,
                'no_perkiraan' => '2103-01-01',
                'keterangan'   => $keterangan,
                'no_reff'      => $no_retur,
                'debet'        => $nilai_ppn_keluaran,
                'kredit'       => 0,
                'created_by'   => $this->auth->user_id(),
                'created_on'   => date('Y-m-d H:i:s'),
            ],
        ]);

        $this->db->insert('tr_kartu_piutang', [
            'tipe'          => 'JV',
            'nomor'         => $Nomor_JV,
            'tanggal'       => $tgl,
            'no_perkiraan'  => '1102-01-01',
            'keterangan'    => $keterangan,
            'no_reff'       => $id_invoice,
            'debet'         => 0,
            'kredit'        => $nilai_piutang,
            'id_supplier'   => $id_customer,
            'nama_supplier' => $nm_customer,
        ]);

        $this->db->query("UPDATE " . DBACC . ".pastibisa_tb_cabang SET nomorJC=nomorJC+1 WHERE nocab='101'");
    }

    // =========================================================
    // PRIVATE: Ambil department_id user yang login
    // =========================================================
    private function _get_user_dept()
    {
        $user_id = $this->auth->user_id();
        $row = $this->db
            ->select('e.department')
            ->from('users u')
            ->join('employee e', 'e.id = u.employee_id', 'left')
            ->where('u.id_user', $user_id)
            ->get()->row();
        return $row ? (int)$row->department : 0;
    }
}
