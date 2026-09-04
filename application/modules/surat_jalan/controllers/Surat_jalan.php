<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Surat_jalan extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Surat_Jalan.View';
    protected $addPermission    = 'Surat_Jalan.Add';
    protected $managePermission = 'Surat_Jalan.Manage';
    protected $deletePermission = 'Surat_Jalan.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->library(array('upload', 'Image_lib'));
        $this->load->model(array(
            'Surat_jalan/surat_jalan_model',
            'jurnal_nomor/Jurnal_model'
        ));

        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->title('Surat Jalan');
        $this->template->page_icon('fa fa-envelope');
        $this->template->render('index');
    }

    public function index_retur()
    {
        $this->template->title('List Produk Retur');
        $this->template->page_icon('fa fa-rotate-left');
        $this->template->render('index_retur');
    }

    public function index_hilang()
    {
        $this->template->title('List Produk Hilang');
        $this->template->page_icon('fa fa-eye-slash');
        $this->template->render('index_hilang');
    }

    public function data_side_surat_jalan()
    {
        $this->surat_jalan_model->data_side_surat_jalan();
    }

    public function data_side_retur()
    {
        $this->surat_jalan_model->data_side_retur();
    }

    public function data_side_hilang()
    {
        $this->surat_jalan_model->data_side_hilang();
    }

    public function add()
    {
        $sql = "
                SELECT l.no_loading, l.nopol, l.tanggal_muat
                FROM loading_delivery l
                WHERE l.status = 3
                AND l.pengiriman = 'Gudang'
                AND EXISTS (
                    SELECT 1
                    FROM loading_delivery_detail ld
                    WHERE ld.no_loading = l.no_loading
                    AND NOT EXISTS (
                        SELECT 1
                        FROM surat_jalan sj
                        WHERE sj.no_loading  = ld.no_loading
                        AND sj.no_so       = ld.no_so
                        AND sj.no_delivery = ld.no_delivery
                    )
                )
                GROUP BY l.no_loading, l.nopol, l.tanggal_muat
                ORDER BY l.tanggal_muat DESC
                ";
        $loading = $this->db->query($sql)->result_array();

        $data = [
            'loading' => $loading
        ];

        $this->template->title('Add Surat Jalan');
        $this->template->page_icon('fa fa-envelope');
        $this->template->render('form', $data);
    }

    public function get_spk()
    {
        $no_loading = $this->input->get('no_loading', TRUE);

        $header = $this->db->get_where('loading_delivery', ['no_loading' => $no_loading])->row_array();
        $detail = $this->db
            ->select('
                    ldd.*,
                    sd.no_so,
                    sd.pengiriman,
                    sd.tanggal_kirim,
                    sd.delivery_address AS alamat,
                    sdd.id_so_det,        
                    p.weight,
                    (ldd.qty_muat * COALESCE(MAX(w.harga_beli),0)) AS costbook,
                ')
            ->from('loading_delivery_detail ldd')
            ->join('spk_delivery sd', 'ldd.no_delivery = sd.no_delivery', 'left')
            ->join('spk_delivery_detail sdd', 'sdd.no_delivery = ldd.no_delivery AND sdd.id_product = ldd.id_product', 'left')
            ->join('new_inventory_4 p', 'ldd.id_product = p.code_lv4', 'left')
            ->join('warehouse_stock w', 'p.code_lv4 = w.id_material', 'left')
            ->where('ldd.no_loading', $no_loading)
            ->where("CONCAT(ldd.no_so, '|', ldd.no_delivery) NOT IN (
                    SELECT CONCAT(no_so, '|', no_delivery)
                    FROM surat_jalan
                    WHERE no_loading = '$no_loading')")
            ->group_by('ldd.no_loading, ldd.no_delivery, ldd.id_product')
            ->get()
            ->result_array();

        echo json_encode([
            'header' => $header,
            'detail' => $detail
        ]);
    }

    public function save()
    {
        $post = $this->input->post();

        $detail = $post['detail'];

        $is_update = isset($post['id']) && !empty($post['id']);
        $tanggal_sekarang = date('Y-m-d H:i:s');

        if ($is_update) {
            // MODE UPDATE
            $id_sj = $post['id'];
            $no_surat_jalan = $post['no_surat_jalan'];

            $ArrHeader = [
                'no_loading'       => $post['no_loading'],
                'no_so'            => $post['no_so'],
                'no_delivery'      => $post['no_delivery'],
                'pengiriman'       => $post['pengiriman'],
                'driver_name'      => $post['driver_name'],
                'delivery_address' => $post['delivery_address'],
                'delivery_date'    => date('Y-m-d', strtotime($post['delivery_date'])),
                'updated_by'       => $this->auth->user_id(),
                'updated_at'       => $tanggal_sekarang,
            ];
        } else {
            // MODE INSERT
            $Ym = date('ym');
            $SQL = "SELECT MAX(no_surat_jalan) as maxM FROM surat_jalan WHERE no_surat_jalan LIKE 'SJ/G/{$Ym}/%'";
            $result = $this->db->query($SQL)->result_array();
            $angkaUrut = $result[0]['maxM'];

            if ($angkaUrut) {
                $parts = explode('/', $angkaUrut);
                $urutan = isset($parts[3]) ? (int)$parts[3] : 0;
            } else {
                $urutan = 0;
            }

            $urutan++;
            $formatUrut = sprintf('%04s', $urutan);
            $no_surat_jalan = "SJ/G/{$Ym}/{$formatUrut}";

            $ArrHeader = [
                'no_surat_jalan'   => $no_surat_jalan,
                'no_loading'       => $post['no_loading'],
                'no_so'            => $post['no_so'],
                'no_delivery'      => $post['no_delivery'],
                'pengiriman'       => $post['pengiriman'],
                'driver_name'      => $post['driver_name'],
                'delivery_address' => $post['delivery_address'],
                'delivery_date'    => date('Y-m-d', strtotime($post['delivery_date'])),
                'created_by'       => $this->auth->user_id(),
                'created_at'       => $tanggal_sekarang,
            ];
        }

        // Prepare Detail
        $ArrDetail = [];
        foreach ($detail as $key => $value) {
            $id_product     = $value['id_product'];
            $id_so_det      = $value['id_so_det'];
            $id_spk_det     = $value['id_spk_det'];
            $qty            = $value['qty'];

            $ArrDetail[$key] = [
                'no_surat_jalan'  => $no_surat_jalan,
                'id_product'      => $id_product,
                'product'         => $value['product'],
                'qty'             => $qty,
                'weight'          => $value['weight'],
                'total_berat'     => $value['total_berat'],
                'id_so_det'       => $id_so_det,
                'id_spk_det'      => $id_spk_det,
            ];
        }

        // Simpan ke DB — semua operasi di dalam satu transaksi
        $this->db->trans_start();

        if ($is_update) {
            // Saat UPDATE: kembalikan dulu qty_delivery dari detail SJ lama sebelum diganti
            $old_sj_detail = $this->db->get_where('surat_jalan_detail', ['id_sj' => $id_sj])->result_array();
            foreach ($old_sj_detail as $old) {
                $this->db->set('qty_delivery', 'qty_delivery - ' . (int)$old['qty'], FALSE);
                $this->db->set('status_kirim', '0');
                $this->db->where('id', $old['id_so_det']);
                $this->db->update('sales_order_detail');

                // Kembalikan stok yang sudah diturunkan saat SJ lama dibuat
                $this->db->set('qty_stock',   'qty_stock + '   . (int)$old['qty'], FALSE);
                $this->db->set('qty_booking', 'qty_booking + ' . (int)$old['qty'], FALSE);
                $this->db->set('qty_free',    'qty_stock - qty_booking', FALSE);
                $this->db->where('id_material', $old['id_product']);
                $this->db->where('id_gudang', 1);
                $this->db->where('kd_gudang', 'PUS');
                $this->db->update('warehouse_stock');

                // Guard: kalau baris warehouse_stock tidak ditemukan, UPDATE di atas
                // diam-diam tidak mengubah apapun (affected_rows = 0) tapi proses tetap
                // lanjut jika tidak dicek. Batalkan transaksi supaya tidak terjadi
                // selisih stok seperti pada kasus SJ/G/2608/0072.
                if ((int)$old['qty'] > 0 && $this->db->affected_rows() == 0) {
                    $this->db->trans_rollback();
                    echo json_encode(['status' => 0, 'pesan' => "Gagal mengembalikan stok lama untuk produk {$old['id_product']}: baris warehouse_stock tidak ditemukan."]);
                    return;
                }
            }

            $this->db->update('surat_jalan', $ArrHeader, ['id' => $id_sj]);
            $this->db->delete('surat_jalan_detail', ['id_sj' => $id_sj]);

            foreach ($ArrDetail as &$row) {
                $row['id_sj'] = $id_sj;
            }
            $this->db->insert_batch('surat_jalan_detail', $ArrDetail);

            // Update SPK, SO detail, dan warehouse_stock dengan nilai baru
            foreach ($detail as $value) {
                $qty        = (int)$value['qty'];
                $id_product = $value['id_product'];
                $id_so_det  = $value['id_so_det'];

                $this->db->update('spk_delivery', ['status' => 'ON DELIVER', 'no_surat_jalan' => $no_surat_jalan], ['no_delivery' => $post['no_delivery']]);

                $this->db->set('qty_delivery', 'qty_delivery + ' . $qty, FALSE);
                $this->db->set('status_kirim', '1');
                $this->db->set('tgl_delivery', date('Y-m-d H:i:s', strtotime($post['delivery_date'])));
                $this->db->where('id', $id_so_det);
                $this->db->update('sales_order_detail');

                // Turunkan stok saat SJ dibuat — barang sudah keluar gudang fisik
                $this->db->set('qty_stock',   'qty_stock - '   . $qty, FALSE);
                $this->db->set('qty_booking', 'GREATEST(qty_booking - ' . $qty . ', 0)', FALSE);
                $this->db->set('qty_free',    '(qty_stock - ' . $qty . ') - GREATEST(qty_booking - ' . $qty . ', 0)', FALSE);
                $this->db->where('id_material', $id_product);
                $this->db->where('id_gudang', 1);
                $this->db->where('kd_gudang', 'PUS');
                $this->db->where('qty_stock >=', $qty); // guard anti-minus
                $this->db->update('warehouse_stock');

                // Guard: kalau qty_stock riil < qty, WHERE di atas tidak match baris
                // manapun dan UPDATE ini diam-diam gagal (affected_rows = 0) tanpa
                // error. Tanpa cek ini, proses tetap lanjut seolah stok sudah
                // berkurang padahal warehouse_stock tidak berubah sama sekali.
                if ($qty > 0 && $this->db->affected_rows() == 0) {
                    $this->db->trans_rollback();
                    echo json_encode(['status' => 0, 'pesan' => "Stok tidak cukup untuk produk {$value['product']} ({$id_product}). Tidak dapat memperbarui Surat Jalan."]);
                    return;
                }
            }
        } else {
            $this->db->insert('surat_jalan', $ArrHeader);
            $id_sj = $this->db->insert_id();

            foreach ($ArrDetail as &$row) {
                $row['no_surat_jalan']  = $no_surat_jalan;
                $row['id_sj']  = $id_sj;
            }
            $this->db->insert_batch('surat_jalan_detail', $ArrDetail);

            // Preload stok untuk kartu stok — baca dengan FOR UPDATE agar tidak race condition
            $productIds = array_unique(array_column($detail, 'id_product'));
            $ids_escaped = array_map(function ($id) {
                return $this->db->escape($id);
            }, $productIds);
            $ids_str = implode(',', $ids_escaped);
            $stockRows = $this->db->query(
                "SELECT * FROM warehouse_stock WHERE id_material IN ({$ids_str}) AND id_gudang = 1 AND kd_gudang = 'PUS' FOR UPDATE"
            )->result_array();
            $stockMap   = [];
            foreach ($stockRows as $s) {
                $stockMap[$s['id_material']] = $s;
            }

            $arr_kartu_stok_sj = [];

            // Update SPK, SO detail, warehouse_stock, dan kartu stok
            foreach ($detail as $value) {
                $qty        = (int)$value['qty'];
                $id_product = $value['id_product'];
                $id_so_det  = $value['id_so_det'];

                $this->db->update('spk_delivery', ['status' => 'ON DELIVER', 'no_surat_jalan' => $no_surat_jalan], ['no_delivery' => $post['no_delivery']]);

                $this->db->set('qty_delivery', 'qty_delivery + ' . $qty, FALSE);
                $this->db->set('status_kirim', '1');
                $this->db->set('tgl_delivery', date('Y-m-d H:i:s', strtotime($post['delivery_date'])));
                $this->db->where('id', $id_so_det);
                $this->db->update('sales_order_detail');

                // Turunkan stok saat SJ dibuat — barang sudah keluar gudang fisik
                $stok = $stockMap[$id_product] ?? null;

                // Guard: produk tidak ditemukan di warehouse_stock (gudang 1 / PUS).
                // Sebelumnya kondisi ini tidak dicek dan diam-diam dianggap stok = 0,
                // sehingga kartu_stok tetap mencatat pengurangan padahal warehouse_stock
                // sama sekali tidak tersentuh.
                if ($stok === null) {
                    $this->db->trans_rollback();
                    echo json_encode(['status' => 0, 'pesan' => "Stok warehouse tidak ditemukan untuk produk {$value['product']} ({$id_product})."]);
                    return;
                }

                $old_stock   = (float)$stok['qty_stock'];
                $old_booking = (float)$stok['qty_booking'];
                $old_free    = (float)$stok['qty_free'];

                // Kalau qty = 0, tidak ada barang yang keluar untuk baris ini —
                // warehouse_stock tidak perlu (dan tidak boleh) disentuh sama sekali.
                // Sebelumnya UPDATE tetap dijalankan dengan nilai yang identik dengan
                // nilai lama, sehingga MySQL melaporkan affected_rows = 0 (karena tidak
                // ada baris yang benar-benar berubah nilainya) dan guard di bawah salah
                // mengartikannya sebagai kegagalan (lihat kasus "Save Failed" pada qty=0).
                if ($qty > 0) {
                    // Guard: stok riil tidak cukup. Ini adalah root cause bug di kasus
                    // SJ/G/2608/0072 — WHERE qty_stock >= $qty di bawah gagal match,
                    // UPDATE warehouse_stock diam-diam ber-affected_rows 0, tapi tanpa
                    // cek ini proses tetap lanjut dan kartu_stok tetap mencatat qty_akhir
                    // yang seolah-olah berhasil dikurangi (bahkan bisa jadi minus).
                    if ($old_stock < $qty) {
                        $this->db->trans_rollback();
                        echo json_encode(['status' => 0, 'pesan' => "Stok tidak cukup untuk produk {$value['product']} ({$id_product}). Stok tersedia: {$old_stock}, dibutuhkan: {$qty}."]);
                        return;
                    }

                    $new_stock   = $old_stock - $qty;
                    $new_booking = max($old_booking - $qty, 0);
                    $new_free    = $new_stock - $new_booking;

                    $this->db->set('qty_stock',   $new_stock,   FALSE);
                    $this->db->set('qty_booking', $new_booking, FALSE);
                    $this->db->set('qty_free',    $new_free,    FALSE);
                    $this->db->where('id_material', $id_product);
                    $this->db->where('id_gudang', 1);
                    $this->db->where('kd_gudang', 'PUS');
                    $this->db->where('qty_stock >=', $qty); // guard anti-minus
                    $this->db->update('warehouse_stock');

                    // Guard tambahan: pastikan UPDATE benar-benar mengubah baris
                    // (jaga-jaga race condition di luar lock FOR UPDATE di atas).
                    if ($this->db->affected_rows() == 0) {
                        $this->db->trans_rollback();
                        echo json_encode(['status' => 0, 'pesan' => "Gagal memperbarui stok untuk produk {$value['product']} ({$id_product}). Kemungkinan stok berubah bersamaan proses lain, silakan coba lagi."]);
                        return;
                    }

                    // Update map lokal agar kartu stok berikutnya akurat
                    if (isset($stockMap[$id_product])) {
                        $stockMap[$id_product]['qty_stock']   = $new_stock;
                        $stockMap[$id_product]['qty_booking'] = $new_booking;
                        $stockMap[$id_product]['qty_free']    = $new_free;
                    }
                } else {
                    // qty = 0: tidak ada perubahan stok, kartu stok tetap dicatat
                    // dengan qty_transaksi = 0 (konsisten dengan baris non-stok lain
                    // seperti "Sales Order" yang juga bisa qty_transaksi = 0).
                    $new_stock   = $old_stock;
                    $new_booking = $old_booking;
                    $new_free    = $old_free;
                }

                // Kartu stok: barang keluar gudang saat SJ dibuat
                $arr_kartu_stok_sj[] = [
                    'no_transaksi'   => $no_surat_jalan,
                    'transaksi'      => 'Surat Jalan',
                    'tgl_transaksi'  => $post['delivery_date'],
                    'code_lv4'       => $id_product,
                    'nm_product'     => $value['product'],
                    'qty'            => $old_stock,
                    'qty_book'       => $old_booking,
                    'qty_free'       => $old_free,
                    'qty_transaksi'  => $qty * -1,
                    'qty_akhir'      => $new_stock,
                    'qty_book_akhir' => $new_booking,
                    'qty_free_akhir' => $new_free,
                    'harga_stok'     => $stok ? (float)$stok['harga_beli'] : 0,
                ];
            }

            if (!empty($arr_kartu_stok_sj)) {
                $this->db->insert_batch('kartu_stok', $arr_kartu_stok_sj);
            }

            //SYAMSUDIN 16-09-2025 JURNAL
            $tgl_inv  = date('Y-m-d');
            $keterangan  = "Surat Jalan" . $no_surat_jalan;
            $type        = $no_surat_jalan;
            $reff        = $no_surat_jalan;
            $no_req      = $no_surat_jalan;
            $no_po       = $no_surat_jalan;
            $total       = str_replace(",", "", $this->input->post('debet[0]'));
            $jenis       = $this->input->post('jenis');
            $tipe_jurnal       = $this->input->post('tipe');
            $jenis_jurnal       = $this->input->post('jenis_jurnal');

            $total_po           = str_replace(",", "", $this->input->post('debet[0]'));
            $Nomor_JV                = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', $tgl_inv);


            $Bln             = substr($tgl_inv, 5, 2);
            $Thn             = substr($tgl_inv, 0, 4);


            $dataJVhead = array(
                'nomor'             => $Nomor_JV,
                'tgl'                 => $tgl_inv,
                'jml'                => $total,
                'koreksi_no'        => '-',
                'kdcab'                => '101',
                'jenis'                => 'JV',
                'keterangan'         => $keterangan,
                'bulan'                => $Bln,
                'tahun'                => $Thn,
                'user_id'            => $this->auth->user_id(),
                'memo'                => '',
                'tgl_jvkoreksi'        => $tgl_inv,
                'ho_valid'            => ''
            );

            $this->db->insert(DBACC . '.javh', $dataJVhead);

            for ($i = 0; $i < count($this->input->post('type')); $i++) {
                $tipe = $this->input->post('type')[$i];
                $perkiraan = $this->input->post('no_coa')[$i];
                $noreff = $no_po;

                $datadetail = array(
                    'tipe'            => $this->input->post('type')[$i],
                    'nomor'           => $Nomor_JV,
                    'tanggal'         => $this->input->post('tgl_jurnal')[$i],
                    'no_perkiraan'    => $this->input->post('no_coa')[$i],
                    'keterangan'      =>  $keterangan,
                    'no_reff'        => $no_po,
                    'debet'          => str_replace(",", "", $this->input->post('debet')[$i]),
                    'kredit'         => str_replace(",", "", $this->input->post('kredit')[$i]),
                    'created_by'      => $this->auth->user_id(),
                    'created_on'      => date('Y-m-d H:i:s')
                );
                $this->db->insert(DBACC . '.jurnal', $datadetail);
            }

            $Qry_Update_Cabang_acc     = "UPDATE " . DBACC . ".pastibisa_tb_cabang SET nomorJC=nomorJC + 1 WHERE nocab='101'";
            $this->db->query($Qry_Update_Cabang_acc);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $res = ['status' => 0, 'pesan' => 'Gagal menyimpan data.'];
        } else {
            $this->db->trans_commit();
            $res = ['status' => 1, 'pesan' => 'Data berhasil disimpan.'];
            history(($is_update ? 'Update' : 'Create') . " Surat Jalan : " . $no_surat_jalan);
        }

        echo json_encode($res);
    }

    public function confirm_sj($id)
    {
        // Header SJ
        $sj = $this->db
            ->select('sj.*, so.nama_sales, ld.nopol, p.id_penawaran, c.name_customer')
            ->from('surat_jalan sj')
            ->join('loading_delivery ld', 'sj.no_loading = ld.no_loading', 'left')
            ->join('sales_order so', 'sj.no_so = so.no_so', 'left')
            ->join('penawaran p', 'so.id_penawaran = p.id_penawaran', 'left')
            ->join('master_customers c', 'so.id_customer = c.id_customer', 'left')
            ->where('sj.id', $id)
            ->get()
            ->row_array();

        if (!$sj) {
            show_404();
        }

        $sdd_sub = "
        (
            SELECT
                id_so_det,
                no_delivery,
                SUM(COALESCE(qty_so, 0))  AS qty_so,
                SUM(COALESCE(qty_spk, 0)) AS qty_spk
            FROM spk_delivery_detail
            GROUP BY id_so_det, no_delivery
        ) sdd
    ";


        $wh_sub = "
        (
            SELECT
                id_material,
                MAX(harga_beli) AS costbook,
                MAX(id_unit)    AS id_unit
            FROM warehouse_stock
            GROUP BY id_material
        ) wh
    ";

        $detail = $this->db
            ->select('
            d.*,
            s.code,
            COALESCE(sdd.qty_so, 0)  AS qty_so,
            COALESCE(sdd.qty_spk, 0) AS qty_spk,
            COALESCE(wh.costbook, 0) AS costbook
        ')
            ->from('surat_jalan_detail d')
            ->join('surat_jalan sj', 'sj.id = d.id_sj') // untuk akses sj.no_delivery di join sdd
            ->join($sdd_sub, 'sdd.id_so_det = d.id_so_det AND sdd.no_delivery = sj.no_delivery', 'left')
            ->join($wh_sub, 'wh.id_material = d.id_product', 'left')
            ->join('ms_satuan s', 's.id = wh.id_unit', 'left')
            ->where('d.id_sj', $id)
            ->get()
            ->result_array();

        $data = [
            'sj'     => $sj,
            'detail' => $detail,
        ];

        $this->template->page_icon('fa fa-check');
        $this->template->title('Confirm Delivery');
        $this->template->render('confirm', $data);
    }

    public function view_confirm($id)
    {
        // Header SJ — sama seperti confirm_sj, ditambah info hasil konfirmasi
        $sj = $this->db
            ->select('sj.*, so.nama_sales, ld.nopol, p.id_penawaran, c.name_customer')
            ->from('surat_jalan sj')
            ->join('loading_delivery ld', 'sj.no_loading = ld.no_loading', 'left')
            ->join('sales_order so', 'sj.no_so = so.no_so', 'left')
            ->join('penawaran p', 'so.id_penawaran = p.id_penawaran', 'left')
            ->join('master_customers c', 'so.id_customer = c.id_customer', 'left')
            ->where('sj.id', $id)
            ->get()
            ->row_array();

        if (!$sj) {
            show_404();
        }

        // Hanya bisa dilihat kalau sudah pernah dikonfirmasi (ada qty_terkirim)
        if (!in_array($sj['status'], ['CONFIRM', 'RETUR', 'HILANG'])) {
            show_404();
        }

        $sdd_sub = "
        (
            SELECT
                id_so_det,
                no_delivery,
                SUM(COALESCE(qty_so, 0))  AS qty_so,
                SUM(COALESCE(qty_spk, 0)) AS qty_spk
            FROM spk_delivery_detail
            GROUP BY id_so_det, no_delivery
        ) sdd
    ";

        $wh_sub = "
        (
            SELECT
                id_material,
                MAX(harga_beli) AS costbook,
                MAX(id_unit)    AS id_unit
            FROM warehouse_stock
            GROUP BY id_material
        ) wh
    ";

        // Detail sudah menyimpan qty_terkirim/qty_retur/qty_hilang/qty_lebih
        // hasil proses confirm() — tinggal ditampilkan read-only di sini.
        $detail = $this->db
            ->select('
            d.*,
            s.code,
            COALESCE(sdd.qty_so, 0)  AS qty_so,
            COALESCE(sdd.qty_spk, 0) AS qty_spk,
            COALESCE(wh.costbook, 0) AS costbook
        ')
            ->from('surat_jalan_detail d')
            ->join('surat_jalan sj', 'sj.id = d.id_sj')
            ->join($sdd_sub, 'sdd.id_so_det = d.id_so_det AND sdd.no_delivery = sj.no_delivery', 'left')
            ->join($wh_sub, 'wh.id_material = d.id_product', 'left')
            ->join('ms_satuan s', 's.id = wh.id_unit', 'left')
            ->where('d.id_sj', $id)
            ->get()
            ->result_array();

        $data = [
            'sj'     => $sj,
            'detail' => $detail,
        ];

        $this->template->page_icon('fa fa-eye');
        $this->template->title('Detail Konfirmasi Surat Jalan');
        $this->template->render('view_confirm', $data);
    }


    public function confirm()
    {
        $post   = $this->input->post();
        $detail = $post['detail'] ?? [];

        if (empty($detail) || !is_array($detail)) {
            echo json_encode(['status' => 0, 'pesan' => 'Detail tidak valid.']);
            return;
        }

        // ===== Validasi field wajib =====
        $id_sj = isset($post['id']) ? (int)$post['id'] : 0;
        if (!$id_sj) {
            echo json_encode(['status' => 0, 'pesan' => 'ID Surat Jalan tidak valid.']);
            return;
        }

        $tgl_diterima   = isset($post['tgl_diterima'])   ? trim($post['tgl_diterima'])   : '';
        $penerima       = isset($post['penerima'])       ? trim($post['penerima'])       : '';
        $no_surat_jalan = isset($post['no_surat_jalan']) ? trim($post['no_surat_jalan']) : '';
        $no_delivery    = isset($post['no_delivery'])    ? trim($post['no_delivery'])    : '';

        if (!$tgl_diterima || !$no_surat_jalan || !$no_delivery) {
            echo json_encode(['status' => 0, 'pesan' => 'Field tgl_diterima, no_surat_jalan, dan no_delivery wajib diisi.']);
            return;
        }

        // ===== Guard: cegah double-confirm =====
        $existing = $this->db
            ->select('status')
            ->where('id', $id_sj)
            ->get('surat_jalan')
            ->row_array();

        if (!$existing) {
            echo json_encode(['status' => 0, 'pesan' => 'Surat Jalan tidak ditemukan.']);
            return;
        }

        if (in_array($existing['status'], ['CONFIRM', 'HILANG', 'RETUR'])) {
            echo json_encode(['status' => 0, 'pesan' => 'Surat Jalan ini sudah pernah dikonfirmasi, tidak bisa diproses ulang.']);
            return;
        }

        $sanitized_sj = str_replace(['/', '\\'], '_', $no_surat_jalan);

        // status final (prioritas: HILANG > RETUR > CONFIRM)
        $flag_hilang = false;
        $flag_retur  = false;

        $ArrUpdate = [
            'tgl_diterima' => $tgl_diterima,
            'penerima'     => $penerima,
            'updated_by'   => $this->auth->user_id(),
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        // ✅ Upload file dokumen jika ada (di luar transaksi DB)
        // Catatan: file sudah terupload sebelum transaksi DB dimulai.
        // Jika DB gagal, file perlu dihapus manual (orphan risk).
        if (!empty($_FILES['file_dokumen']['name'])) {
            $config = [
                'upload_path'   => './uploads/confirm_sj/',
                'allowed_types' => 'jpg|jpeg|png|gif|webp|pdf|doc|docx|xls|xlsx',
                'max_size'      => 5120,
                // 'detect_mime'   => FALSE,
                'file_name'     => 'bukti_confirm_sj_gudang_' . $sanitized_sj
            ];

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('file_dokumen')) {
                echo json_encode(['status' => 0, 'pesan' => $this->upload->display_errors()]);
                return;
            }

            $uploadData = $this->upload->data();
            $ArrUpdate['file_dokumen'] = $uploadData['file_name'];
        }

        // ====== Preload stok agar hemat query ======
        // Validasi juga struktur tiap item detail sebelum masuk transaksi
        $productIds = [];
        foreach ($detail as $key => $value) {
            if (empty($value['id_detail']) || empty($value['id_product']) || empty($value['id_so_det'])) {
                echo json_encode(['status' => 0, 'pesan' => "Data detail ke-{$key} tidak lengkap (id_detail/id_product/id_so_det)."]);
                return;
            }
            $productIds[] = $value['id_product'];
        }
        $productIds = array_values(array_unique($productIds));

        $stockMap = [];
        // Stok akan dibaca di dalam transaksi dengan FOR UPDATE (lihat di bawah)

        // ====== Siapkan batch update surat_jalan_detail & kartu_stok ======
        $ArrDetailBatch = [];
        $arr_kartu_stok = [];

        // Helper rollback cepat
        $fail = function ($msg) {
            $this->db->trans_rollback();
            echo json_encode(['status' => 0, 'pesan' => $msg]);
        };

        // ====== MULAI TRANSAKSI (semua update DB di dalam ini) ======
        $this->db->trans_begin();

        // Baca stok dengan FOR UPDATE di dalam transaksi agar tidak race condition
        if (!empty($productIds)) {
            $ids_escaped = array_map(function ($id) {
                return $this->db->escape($id);
            }, $productIds);
            $ids_str = implode(',', $ids_escaped);
            $stocks = $this->db->query(
                "SELECT * FROM warehouse_stock WHERE id_material IN ({$ids_str}) FOR UPDATE"
            )->result_array();
            foreach ($stocks as $s) {
                $stockMap[$s['id_material']] = $s;
            }
        }

        // update surat_jalan header
        // status diset setelah loop (berdasarkan flag)
        $this->db->update('surat_jalan', $ArrUpdate, ['id' => $id_sj]);

        foreach ($detail as $key => $value) {

            $qty_delivery = (int) ($value['qty_delivery'] ?? 0); // yang dikirim (keluar gudang)
            $qty_terkirim = (int) ($value['qty_terkirim'] ?? 0); // diterima customer (fulfilled)
            $qty_retur    = (int) ($value['qty_retur'] ?? 0);    // kembali gudang
            $qty_hilang   = (int) ($value['qty_hilang'] ?? 0);   // hilang (tetap bagian dari yang dikirim)
            $qty_lebih    = (int) ($value['qty_lebih'] ?? 0);

            $id_detail  = $value['id_detail'];
            $id_product = $value['id_product'];
            $id_so_det  = $value['id_so_det'];

            $total = $qty_terkirim + $qty_retur + $qty_hilang;

            // Validasi basic
            if ($qty_delivery < 0 || $qty_terkirim < 0 || $qty_retur < 0 || $qty_hilang < 0 || $qty_lebih < 0) {
                $fail('Qty tidak boleh negatif.');
                return;
            }
            if ($total > $qty_delivery) {
                $fail("Total (terkirim+retur+hilang) melebihi qty_delivery untuk produk {$id_product}.");
                return;
            }

            // Flag status
            if ($qty_hilang > 0) $flag_hilang = true;
            if ($qty_retur > 0 || $qty_lebih > 0 || $total !== $qty_delivery) $flag_retur = true;

            // ===== Upload bukti retur/hilang per item (tetap bisa, tapi file system tidak ikut rollback) =====
            $reason    = null;
            $fileBukti = null;

            if (($qty_retur > 0 || $qty_hilang > 0) && !empty($value['reason'])) {
                $reason = $value['reason'];
            }

            if (($qty_retur > 0 || $qty_hilang > 0) && !empty($_FILES['detail']['name'][$key]['file_bukti'])) {
                $_FILES['file_temp'] = [
                    'name'     => $_FILES['detail']['name'][$key]['file_bukti'],
                    'type'     => $_FILES['detail']['type'][$key]['file_bukti'],
                    'tmp_name' => $_FILES['detail']['tmp_name'][$key]['file_bukti'],
                    'error'    => $_FILES['detail']['error'][$key]['file_bukti'],
                    'size'     => $_FILES['detail']['size'][$key]['file_bukti'],
                ];

                $config_retur = [
                    'upload_path'   => './uploads/confirm_sj/',
                    'allowed_types' => 'jpg|jpeg|png|gif|webp|pdf|doc|docx|xls|xlsx',
                    'max_size'      => 5120,
                    // 'detect_mime'   => FALSE,
                    'file_name'     => 'retur_' . $sanitized_sj . '_' . $key
                ];

                $this->upload->initialize($config_retur);

                if ($this->upload->do_upload('file_temp')) {
                    $upload_data = $this->upload->data();
                    $fileBukti = $upload_data['file_name'];
                } else {
                    $fail('Upload file retur gagal: ' . $this->upload->display_errors());
                    return;
                }
            }

            // ===== Update surat_jalan_detail (batch) =====
            $ArrDetailBatch[] = [
                'id'          => $id_detail,
                'qty_terkirim' => $qty_terkirim,
                'qty_retur'    => $qty_retur,
                'qty_hilang'   => $qty_hilang,
                'qty_lebih'    => $qty_lebih,
                'reason'       => $reason,
                'file_bukti'   => $fileBukti,
            ];

            // ===== Update spk_delivery_detail (tetap seperti kamu) =====
            $this->db->where([
                'no_delivery' => $no_delivery,
                'id_so_det'   => $id_so_det
            ])->update('spk_delivery_detail', [
                'qty_delivery' => $qty_terkirim
            ]);

            // ===== Update sales_order_detail qty_terkirim (OPTIMASI: tanpa SELECT) =====
            if ($qty_terkirim > 0) {
                $this->db->set('qty_terkirim', 'qty_terkirim + ' . (int)$qty_terkirim, FALSE);
                $this->db->where('id', $id_so_det);
                $this->db->where("qty_terkirim + {$qty_terkirim} <= qty_delivery", null, FALSE);
                $this->db->update('sales_order_detail');

                if ($this->db->affected_rows() == 0) {
                    $fail("Qty terkirim melebihi qty_delivery pada SO detail: {$id_so_det}.");
                    return;
                }
            }

            // ===== Ambil stok dari map (hemat query) =====
            if (empty($stockMap[$id_product])) {
                $fail("Stok warehouse tidak ditemukan untuk produk: {$id_product}.");
                return;
            }

            $stok = $stockMap[$id_product];

            $old_stock   = (float)$stok['qty_stock'];
            $old_booking = (float)$stok['qty_booking'];
            $old_free    = (float)$stok['qty_free'];

            // ===== Update warehouse_stock (INTI) =====
            //
            // Filosofi Solusi 1:
            //   Stok sudah turun saat Save SJ (barang keluar gudang fisik).
            //   Confirm hanya menangani barang yang KEMBALI ke gudang (retur/lebih).
            //
            //   qty_stock   : ditambah qty_retur + qty_lebih (barang balik ke gudang)
            //   qty_booking : tidak berubah (sudah dilepas saat Save SJ)
            //   qty_free    : naik sesuai barang yang balik
            //
            // Kalau normal (tidak ada retur/hilang): tidak ada perubahan stok sama sekali.
            // Kalau hilang: tidak ada perubahan stok (barang memang tidak kembali).
            //
            $balik = $qty_retur + $qty_lebih;

            if ($balik > 0) {
                $new_stock   = $old_stock + $balik;
                $new_booking = $old_booking; // booking tidak berubah
                $new_free    = $new_stock - $new_booking;

                $this->db->set('qty_stock', 'qty_stock + ' . $balik, FALSE);
                $this->db->set('qty_free',  'qty_free + '  . $balik, FALSE);
                $this->db->where('id_material', $id_product);
                $this->db->where('id_gudang', 1);
                $this->db->where('kd_gudang', 'PUS');
                $this->db->update('warehouse_stock');

                // Update map lokal
                $stockMap[$id_product]['qty_stock'] = $new_stock;
                $stockMap[$id_product]['qty_free']  = $new_free;

                // Kartu stok: barang retur masuk kembali ke gudang
                $arr_kartu_stok[] = [
                    'no_transaksi'   => $no_surat_jalan,
                    'transaksi'      => 'Retur/lebih',
                    'tgl_transaksi'  => $tgl_diterima,
                    'code_lv4'       => $id_product,
                    'nm_product'     => $stok['nm_product'],
                    'qty'            => $old_stock,
                    'qty_book'       => $old_booking,
                    'qty_free'       => $old_free,
                    'qty_transaksi'  => $balik,
                    'qty_akhir'      => $new_stock,
                    'qty_book_akhir' => $new_booking,
                    'qty_free_akhir' => $new_free,
                    'harga_stok'     => (float)$stok['harga_beli'],
                ];
            }
        }

        // Tentukan status final
        $status = 'CONFIRM';
        if ($flag_hilang) {
            $status = 'HILANG';
        } elseif ($flag_retur) {
            $status = 'RETUR';
        }

        // update status header surat_jalan
        $this->db->update('surat_jalan', ['status' => $status], ['id' => $id_sj]);

        // batch update detail SJ
        if (!empty($ArrDetailBatch)) {
            $this->db->update_batch('surat_jalan_detail', $ArrDetailBatch, 'id');
        }

        // insert kartu stok
        if (!empty($arr_kartu_stok)) {
            $this->db->insert_batch('kartu_stok', $arr_kartu_stok);
        }

        // ===== JURNAL =====
        $tgl_inv    = date('Y-m-d');
        $keterangan = 'Confirm Surat Jalan ' . $no_surat_jalan;
        $no_po      = $no_surat_jalan;

        // Fix: $this->input->post('debet[0]') tidak bekerja di CI — harus ambil array dulu
        $debet_arr = $this->input->post('debet');
        $total     = is_array($debet_arr) ? round(str_replace(',', '', $debet_arr[0])) : 0;

        $Nomor_JV = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', $tgl_inv);

        $Bln = substr($tgl_inv, 5, 2);
        $Thn = substr($tgl_inv, 0, 4);

        $dataJVhead = [
            'nomor'        => $Nomor_JV,
            'tgl'          => $tgl_inv,
            'jml'          => $total,
            'koreksi_no'   => '-',
            'kdcab'        => '101',
            'jenis'        => 'JV',
            'keterangan'   => $keterangan,
            'bulan'        => $Bln,
            'tahun'        => $Thn,
            'user_id'      => $this->auth->user_id(),
            'memo'         => '',
            'tgl_jvkoreksi' => $tgl_inv,
            'ho_valid'     => ''
        ];

        $this->db->insert(DBACC . '.javh', $dataJVhead);

        $types = $this->input->post('type');
        if (is_array($types)) {
            for ($i = 0; $i < count($types); $i++) {
                $datadetail = [
                    'tipe'         => $this->input->post('type')[$i],
                    'nomor'        => $Nomor_JV,
                    'tanggal'      => $this->input->post('tgl_jurnal')[$i],
                    'no_perkiraan' => $this->input->post('no_coa')[$i],
                    'keterangan'   => $keterangan,
                    'no_reff'      => $no_po,
                    'debet'        => str_replace(",", "", $this->input->post('debet')[$i]),
                    'kredit'       => str_replace(",", "", $this->input->post('kredit')[$i]),
                    'created_by'   => $this->auth->user_id(),
                    'created_on'   => date('Y-m-d H:i:s')
                ];
                $this->db->insert(DBACC . '.jurnal', $datadetail);
            }
        }

        $this->db->query("UPDATE " . DBACC . ".pastibisa_tb_cabang SET nomorJC=nomorJC + 1 WHERE nocab='101'");

        // ===== COMMIT / ROLLBACK =====
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            echo json_encode(['status' => 0, 'pesan' => 'Gagal menyimpan konfirmasi.']);
            return;
        }

        $this->db->trans_commit();

        history("Confirm Surat Jalan : ID #{$id_sj} Status: {$status}");
        echo json_encode(['status' => 1, 'pesan' => 'Konfirmasi berhasil disimpan.']);
    }



    public function print_sj($id)
    {
        // Ambil data header surat jalan + join ke sales_order dan master_customers
        $sj = $this->db
            ->select('sj.*, so.nama_sales, ld.nopol, c.name_customer')
            ->from('surat_jalan sj')
            ->join('loading_delivery ld', 'sj.no_loading = ld.no_loading', 'left')
            ->join('sales_order so', 'sj.no_so = so.no_so', 'left')
            ->join('master_customers c', 'so.id_customer = c.id_customer', 'left')
            ->where('sj.id', $id)
            ->get()
            ->row_array();

        if (!$sj) {
            show_404();
        }


        // Ambil data detail + join ke inventory dan satuan
        $detail = $this->db
            ->select('
            d.*,
            s.code,
        ')
            ->from('surat_jalan_detail d')
            ->join('new_inventory_4 inv', 'd.id_product = inv.code_lv4', 'left')
            ->join('ms_satuan s', 'inv.id_unit = s.id', 'left')
            ->where('d.id_sj', $id)
            ->get()
            ->result_array();

        $data = [
            'sj' => $sj,
            'detail' => $detail,
        ];

        $this->load->view('print_sj', $data);
    }

    public function export_excel()
    {
        $start = $this->input->get('start_date', true);
        $end   = $this->input->get('end_date', true);

        $this->db->select('sj.no_surat_jalan, sj.no_loading, sj.no_so, sj.delivery_date, sj.status, c.name_customer');
        $this->db->from('surat_jalan sj');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');
        $this->db->where('sj.pengiriman', 'Gudang');
        if (!empty($start)) $this->db->where('sj.delivery_date >=', $start);
        if (!empty($end))   $this->db->where('sj.delivery_date <=', $end);
        $this->db->order_by('sj.delivery_date', 'DESC');

        $rows = $this->db->get()->result();

        if (empty($rows)) {
            echo "<script>alert('Data tidak ditemukan'); window.history.back();</script>";
            return;
        }

        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $this->load->library('PHPExcel');

        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();

        $periode = ($start && $end) ? $start . ' s/d ' . $end : 'Semua Data';
        $sheet->setCellValue('A1', 'REPORT SURAT JALAN - ' . $periode);
        $sheet->mergeCells('A1:G2');

        $headers = ['A' => '#', 'B' => 'No. Surat Jalan', 'C' => 'No. Muat Kendaraan', 'D' => 'No. Sales Order', 'E' => 'Customer', 'F' => 'Tanggal Kirim', 'G' => 'Status'];
        $rowHeader = 4;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $r = $rowHeader + 1;
        $no = 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValueExplicit('B' . $r, (string)$row->no_surat_jalan, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $r, (string)$row->no_loading, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $r, (string)$row->no_so, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $r, (string)$row->name_customer, PHPExcel_Cell_DataType::TYPE_STRING);
            if (!empty($row->delivery_date)) {
                // Ambil komponen tanggal langsung dari string (hindari konversi lewat strtotime()
                // + PHPToExcel() yang memaksa timezone UTC, karena itu bisa membuat tanggal
                // mundur 1 hari saat timezone aplikasi (Asia/Bangkok, UTC+7) berbeda dari UTC).
                $dateOnly = substr($row->delivery_date, 0, 10); // 'Y-m-d'
                list($y, $m, $d) = array_map('intval', explode('-', $dateOnly));
                $tgl = (float)PHPExcel_Shared_Date::FormattedPHPToExcel($y, $m, $d);
                $sheet->setCellValueExplicit('F' . $r, $tgl, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            }
            $sheet->setCellValueExplicit('G' . $r, (string)$row->status, PHPExcel_Cell_DataType::TYPE_STRING);
            $r++;
        }

        $sheet->setTitle('Surat Jalan');
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Surat_Jalan_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}

// Trash

//  public function edit($id)
//     {
//         $sj = $this->db
//             ->from('surat_jalan')
//             ->where('id', $id)
//             ->get()
//             ->row_array();

//         if (!$sj) show_404();

//         $detail = $this->db
//             ->from('surat_jalan_detail')
//             ->where('id_sj', $id)
//             ->get()
//             ->result_array();

//         $loading = $this->db->get('loading_delivery')->result_array();

//         // Ambil daftar SO berdasarkan no_loading
//         $sales_order = $this->db
//             ->where('no_loading', $sj['no_loading'])
//             ->group_by('no_so')
//             ->get('loading_delivery_detail')
//             ->result_array();

//         // Ambil daftar SPK berdasarkan no_so
//         $spk_list = $this->db
//             ->where('no_so', $sj['no_so'])
//             ->group_by('no_delivery')
//             ->get('spk_delivery')
//             ->result_array();

//         $data = [
//             'sj'          => $sj,
//             'detail'      => $detail,
//             'loading'     => $loading,
//             'sales_order' => $sales_order,
//             'spk_list'    => $spk_list
//         ];

//         $this->template->title('Add Surat Jalan');
//         $this->template->page_icon('fa fa-envelope');
//         $this->template->render('form', $data);
//     }

//     public function get_so()
//     {
//         $no_loading = $this->input->get('no_loading', TRUE);

//         $data = $this->db
//             ->select('so.no_so, c.*')
//             ->from('loading_delivery_detail ld')
//             ->join('sales_order so', 'ld.no_so = so.no_so', 'left')
//             ->join('master_customers c', 'so.id_customer = c.id_customer', 'left')
//             ->where('ld.no_loading', $no_loading)
//             ->group_by('so.no_so')
//             ->get()
//             ->result();

//         echo "<option value=''>-- Pilih --</option>";
//         foreach ($data as $so) {
//             echo "<option data-alamat='$so->address_office' value='$so->no_so'>$so->no_so - $so->name_customer</option>";
//         }
//     }

//  public function get_detail()
//     {
//         $no_delivery = $this->input->get('no_delivery', TRUE);

//         $data = $this->db
//             ->select('ldd.*, sod.id AS id_so_det') // ambil kolom id dari sales_order_detail
//             ->from('loading_delivery_detail ldd')
//             ->join('sales_order_detail sod', 'ldd.no_so = sod.no_so AND ldd.id_product = sod.id_product', 'left')
//             ->where('ldd.no_delivery', $no_delivery)
//             ->get()
//             ->result();

//         $html = '';
//         $no = 1;

//         foreach ($data as $i => $row) {
//             $html .= "<tr>
//                     <td class='text-center'>{$no}</td>
//                     <td>{$row->id_product}</td>
//                     <td>{$row->product}</td>
//                     <td class='text-center'>{$row->qty_spk}</td>

//                     <!-- Hidden fields for POST -->
//                     <input type='hidden' name='detail[{$i}][id_product]' value='{$row->id_product}'>
//                     <input type='hidden' name='detail[{$i}][product]' value=\"{$row->product}\">
//                     <input type='hidden' name='detail[{$i}][qty]' value='{$row->qty_spk}'>
//                     <input type='hidden' name='detail[{$i}][id_so_det]' value='{$row->id_so_det}'>
//                   </tr>";
//             $no++;
//         }

//         echo $html;
//     }
