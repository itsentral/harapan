<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Surat_jalan_pabrik extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Surat_Jalan_Pabrik.View';
    protected $addPermission    = 'Surat_Jalan_Pabrik.Add';
    protected $managePermission = 'Surat_Jalan_Pabrik.Manage';
    protected $deletePermission = 'Surat_Jalan_Pabrik.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->library(array('upload', 'Image_lib'));
        $this->load->model(array(
            'Surat_jalan_pabrik/surat_jalan_pabrik_model',
            'jurnal_nomor/Jurnal_model',
        ));

        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->title('Surat Jalan Pabrik');
        $this->template->page_icon('fa fa-envelope');
        $this->template->render('index');
    }

    public function data_side_surat_jalan_pabrik()
    {
        $this->surat_jalan_pabrik_model->data_side_surat_jalan_pabrik();
    }

    public function add()
    {
        $spk_delivery = $this->db
            ->select('sd.no_delivery, c.name_customer AS customer, sd.tanggal_spk, sd.delivery_address')
            ->from('spk_delivery sd')
            ->join('sales_order so', 'sd.no_so = so.no_so', 'left')
            ->join('master_customers c', 'so.id_customer = c.id_customer', 'left')
            ->where('sd.pengiriman', 'Pabrik')
            ->where("sd.no_delivery NOT IN (SELECT no_delivery FROM surat_jalan)")
            ->get()
            ->result_array();

        $data = [
            'spk_delivery' => $spk_delivery,
        ];

        $this->template->title('Add Surat Jalan Pabrik');
        $this->template->page_icon('fa fa-envelope');
        $this->template->render('form', $data);
    }

    public function get_spk_detail()
    {
        $no_delivery = $this->input->get('no_delivery', TRUE);

        // Ambil data header SPK Delivery
        $header = $this->db->get_where('spk_delivery', ['no_delivery' => $no_delivery])->row_array();

        // Ambil detail yang belum pernah dimuat ke surat jalan
        $detail = $this->db
            ->select('
            sdd.*,
            so.no_so,
            sod.id AS id_so_det,
            c.name_customer AS customer,
            c.address_office AS alamat,
            p.nama AS product,
            p.weight,
            (sdd.qty_spk * p.weight) AS total_berat,
            COALESCE(w.harga_beli, 0) AS costbook
        ')
            ->from('spk_delivery_detail sdd')
            ->join('sales_order so', 'sdd.no_so = so.no_so', 'left')
            ->join('sales_order_detail sod', 'sod.no_so = sdd.no_so AND sod.id_product = sdd.id_product', 'left')
            ->join('master_customers c', 'so.id_customer = c.id_customer', 'left')
            ->join('new_inventory_4 p', 'sdd.id_product = p.code_lv4', 'left')
            ->join('warehouse_stock w', 'w.id_material = sdd.id_product', 'left')
            ->where('sdd.no_delivery', $no_delivery)
            ->where("CONCAT(sdd.no_so, '|', sdd.no_delivery) NOT IN (
                    SELECT CONCAT(no_so, '|', no_delivery)
                    FROM surat_jalan
                    WHERE no_delivery = '$no_delivery'
                )")
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
                // 'no_loading'       => $post['no_loading'],
                'no_so'            => $post['no_so'],
                'pengiriman'       => $post['pengiriman'],
                'no_delivery'      => $post['no_delivery'],
                'driver_name'      => $post['driver_name'],
                'delivery_address' => $post['delivery_address'],
                'delivery_date'    => date('Y-m-d', strtotime($post['delivery_date'])),
                'updated_by'       => $this->auth->user_id(),
                'updated_at'       => $tanggal_sekarang,
            ];
        } else {
            // MODE INSERT
            $Ym = date('ym');
            $prefix = "SJ/P/{$Ym}/";

            $SQL = "SELECT MAX(no_surat_jalan) AS maxM
                    FROM surat_jalan
                    WHERE no_surat_jalan LIKE ?";
            $result = $this->db->query($SQL, [$prefix . '%'])->row_array();

            $angkaUrut = $result && $result['maxM'] ? $result['maxM'] : null;
            $urutan = 0;
            if ($angkaUrut) {
                $parts = explode('/', $angkaUrut);
                $urutan = isset($parts[3]) ? (int)$parts[3] : 0;
            }
            $urutan++;
            $no_surat_jalan = $prefix . sprintf('%04d', $urutan);

            $ArrHeader = [
                'no_surat_jalan'   => $no_surat_jalan,
                // 'no_loading'       => $post['no_loading'],
                'no_so'            => $post['no_so'],
                'pengiriman'       => $post['pengiriman'],
                'no_delivery'      => $post['no_delivery'],
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
            $id_product = $value['id_product'];
            $id_so_det  = $value['id_so_det'];
            $qty        = $value['qty'];

            $ArrDetail[$key] = [
                'no_surat_jalan'  => $no_surat_jalan,
                'id_product'      => $id_product,
                'product'         => $value['product'],
                'qty'             => $qty,
                'weight'          => $value['weight'],
                'total_berat'     => $value['weight'],
                'id_so_det'       => $id_so_det,
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

                $this->db->update('spk_delivery', ['status' => 'ON DELIVER'], ['no_delivery' => $post['no_delivery']]);

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
                $this->db->where('qty_stock >=', $qty);
                $this->db->update('warehouse_stock');
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

                $this->db->update('spk_delivery', ['status' => 'ON DELIVER'], ['no_delivery' => $post['no_delivery']]);

                $this->db->set('qty_delivery', 'qty_delivery + ' . $qty, FALSE);
                $this->db->set('status_kirim', '1');
                $this->db->set('tgl_delivery', date('Y-m-d H:i:s', strtotime($post['delivery_date'])));
                $this->db->where('id', $id_so_det);
                $this->db->update('sales_order_detail');

                // Turunkan stok saat SJ dibuat — barang sudah keluar gudang fisik
                $stok        = $stockMap[$id_product] ?? null;
                $old_stock   = $stok ? (float)$stok['qty_stock']   : 0;
                $old_booking = $stok ? (float)$stok['qty_booking'] : 0;
                $old_free    = $stok ? (float)$stok['qty_free']    : 0;

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

                // Update map lokal agar kartu stok berikutnya akurat
                if (isset($stockMap[$id_product])) {
                    $stockMap[$id_product]['qty_stock']   = $new_stock;
                    $stockMap[$id_product]['qty_booking'] = $new_booking;
                    $stockMap[$id_product]['qty_free']    = $new_free;
                }

                // Kartu stok: barang keluar gudang saat SJ dibuat
                $arr_kartu_stok_sj[] = [
                    'no_transaksi'   => $no_surat_jalan,
                    'transaksi'      => 'Surat Jalan Pabrik',
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
        }

        // ===== JURNAL =====
        $tgl_inv     = date('Y-m-d');
        $keterangan  = 'Surat Jalan Pabrik ' . $no_surat_jalan;
        $no_po       = $no_surat_jalan;
        $total       = str_replace(',', '', $this->input->post('debet')[0]);

        $Nomor_JV = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', $tgl_inv);
        $Bln      = substr($tgl_inv, 5, 2);
        $Thn      = substr($tgl_inv, 0, 4);

        $dataJVhead = [
            'nomor'         => $Nomor_JV,
            'tgl'           => $tgl_inv,
            'jml'           => $total,
            'koreksi_no'    => '-',
            'kdcab'         => '101',
            'jenis'         => 'JV',
            'keterangan'    => $keterangan,
            'bulan'         => $Bln,
            'tahun'         => $Thn,
            'user_id'       => $this->auth->user_id(),
            'memo'          => '',
            'tgl_jvkoreksi' => $tgl_inv,
            'ho_valid'      => '',
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
                    'debet'        => str_replace(',', '', $this->input->post('debet')[$i]),
                    'kredit'       => str_replace(',', '', $this->input->post('kredit')[$i]),
                    'created_by'   => $this->auth->user_id(),
                    'created_on'   => date('Y-m-d H:i:s'),
                ];
                $this->db->insert(DBACC . '.jurnal', $datadetail);
            }
        }

        $this->db->query("UPDATE " . DBACC . ".pastibisa_tb_cabang SET nomorJC=nomorJC + 1 WHERE nocab='101'");

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
            ->join('penawaran p', 'so.id_penawaran = p.id_penawaran')
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

        // ===== Validasi struktur detail =====
        foreach ($detail as $key => $value) {
            if (empty($value['id_detail']) || empty($value['id_product']) || empty($value['id_so_det'])) {
                echo json_encode(['status' => 0, 'pesan' => "Data detail ke-{$key} tidak lengkap (id_detail/id_product/id_so_det)."]);
                return;
            }
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
        if (!empty($_FILES['file_dokumen']['name'])) {
            $config = [
                'upload_path'   => './uploads/confirm_sj/',
                'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx|xls|xlsx',
                'max_size'      => 2048,
                'file_name'     => 'bukti_confirm_sj_pabrik_' . $sanitized_sj,
            ];

            $this->upload->initialize($config);

            if (!$this->upload->do_upload('file_dokumen')) {
                echo json_encode(['status' => 0, 'pesan' => $this->upload->display_errors()]);
                return;
            }

            $uploadData = $this->upload->data();
            $ArrUpdate['file_dokumen'] = $uploadData['file_name'];
        }

        $ArrDetailBatch = [];
        $arr_kartu_stok = [];

        // Helper rollback
        $fail = function ($msg) {
            $this->db->trans_rollback();
            echo json_encode(['status' => 0, 'pesan' => $msg]);
        };

        // ====== MULAI TRANSAKSI ======
        $this->db->trans_begin();

        // Update header SJ (status diset setelah loop)
        $this->db->update('surat_jalan', $ArrUpdate, ['id' => $id_sj]);

        foreach ($detail as $key => $value) {
            $qty_delivery = (int)($value['qty_delivery'] ?? 0);
            $qty_terkirim = (int)($value['qty_terkirim'] ?? 0);
            $qty_retur    = (int)($value['qty_retur']    ?? 0);
            $qty_hilang   = (int)($value['qty_hilang']   ?? 0);
            $qty_lebih    = (int)($value['qty_lebih']    ?? 0);
            $id_detail    = $value['id_detail'];
            $id_product   = $value['id_product'];
            $id_so_det    = $value['id_so_det'];

            $total = $qty_terkirim + $qty_retur + $qty_hilang;

            // Validasi qty
            if ($qty_delivery < 0 || $qty_terkirim < 0 || $qty_retur < 0 || $qty_hilang < 0 || $qty_lebih < 0) {
                $fail('Qty tidak boleh negatif.');
                return;
            }
            if ($total > $qty_delivery) {
                $fail("Total (terkirim+retur+hilang) melebihi qty_delivery untuk produk {$id_product}.");
                return;
            }

            // Flag status (prioritas: HILANG > RETUR > CONFIRM)
            if ($qty_hilang > 0) $flag_hilang = true;
            if ($qty_retur > 0 || $qty_lebih > 0 || $total !== $qty_delivery) $flag_retur = true;

            // ===== Upload bukti retur/hilang per item =====
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
                    'allowed_types' => 'jpg|jpeg|png|pdf|doc|docx|xls|xlsx',
                    'max_size'      => 2048,
                    'file_name'     => 'retur_' . $sanitized_sj . '_' . $key,
                ];

                $this->upload->initialize($config_retur);

                if ($this->upload->do_upload('file_temp')) {
                    $upload_data = $this->upload->data();
                    $fileBukti   = $upload_data['file_name'];
                } else {
                    $fail('Upload file retur gagal: ' . $this->upload->display_errors());
                    return;
                }
            }

            // ===== Batch detail SJ =====
            $ArrDetailBatch[] = [
                'id'           => $id_detail,
                'qty_terkirim' => $qty_terkirim,
                'qty_retur'    => $qty_retur,
                'qty_hilang'   => $qty_hilang,
                'qty_lebih'    => $qty_lebih,
                'reason'       => $reason,
                'file_bukti'   => $fileBukti,
            ];

            // ===== Update spk_delivery_detail =====
            $this->db->where([
                'no_delivery' => $no_delivery,
                'id_so_det'   => $id_so_det,
            ])->update('spk_delivery_detail', ['qty_delivery' => $qty_terkirim]);

            // ===== Update sales_order_detail qty_terkirim (guard overflow) =====
            if ($qty_terkirim > 0) {
                $this->db->set('qty_terkirim', 'qty_terkirim + ' . $qty_terkirim, FALSE);
                $this->db->where('id', $id_so_det);
                $this->db->where("qty_terkirim + {$qty_terkirim} <= qty_delivery", null, FALSE);
                $this->db->update('sales_order_detail');

                if ($this->db->affected_rows() == 0) {
                    $fail("Qty terkirim melebihi qty_delivery pada SO detail: {$id_so_det}.");
                    return;
                }
            }

            // ===== Update warehouse_stock =====
            // Catatan: ini dropship dari pabrik — stok gudang TIDAK dikurangi saat delivery
            // (barang tidak lewat gudang). Hanya qty_lebih yang masuk kembali ke gudang.
            // qty_retur dari pabrik juga masuk ke gudang (barang fisik kembali ke gudang kita).
            $balik_ke_gudang = $qty_retur + $qty_lebih;

            // Ambil stok saat ini untuk keperluan log kartu stok — FOR UPDATE agar tidak race condition
            $stok = $this->db->query(
                "SELECT * FROM warehouse_stock WHERE id_material = ? FOR UPDATE",
                [$id_product]
            )->row_array();

            if ($balik_ke_gudang > 0) {
                if (empty($stok)) {
                    $fail("Data warehouse tidak ditemukan untuk produk: {$id_product}.");
                    return;
                }

                $old_stock   = (float)$stok['qty_stock'];
                $old_booking = (float)$stok['qty_booking'];
                $old_free    = (float)$stok['qty_free'];
                $new_stock   = $old_stock + $balik_ke_gudang;
                $new_free    = $new_stock - $old_booking;

                // qty_stock naik, qty_booking tidak berubah (tidak pernah di-booking dari pabrik),
                // qty_free naik sesuai barang yang masuk
                $this->db->set('qty_stock', "qty_stock + {$balik_ke_gudang}", FALSE);
                $this->db->set('qty_free',  "qty_free  + {$balik_ke_gudang}", FALSE);
                $this->db->where('id_material', $id_product);
                $this->db->where('id_gudang', 1);
                $this->db->where('kd_gudang', 'PUS');
                $this->db->update('warehouse_stock');

                $arr_kartu_stok[] = [
                    'no_transaksi'   => $no_surat_jalan,
                    'transaksi'      => 'Retur/lebih Pabrik',
                    'tgl_transaksi'  => $tgl_diterima,
                    'code_lv4'       => $id_product,
                    'nm_product'     => $stok['nm_product'],
                    'qty'            => $old_stock,
                    'qty_book'       => $old_booking,
                    'qty_free'       => $old_free,
                    'qty_transaksi'  => $balik_ke_gudang,
                    'qty_akhir'      => $new_stock,
                    'qty_book_akhir' => $old_booking,
                    'qty_free_akhir' => $new_free,
                    'harga_stok'     => (float)($stok['harga_beli'] ?? 0),
                ];
            }

            // Log kartu stok untuk delivery (informatif, stok tidak berubah karena dropship)
            if ($qty_terkirim > 0 && !empty($stok)) {
                $old_stock   = (float)$stok['qty_stock'];
                $old_booking = (float)$stok['qty_booking'];
                $old_free    = (float)$stok['qty_free'];

                $arr_kartu_stok[] = [
                    'no_transaksi'   => $no_surat_jalan,
                    'transaksi'      => 'Delivery Pabrik',
                    'tgl_transaksi'  => $tgl_diterima,
                    'code_lv4'       => $id_product,
                    'nm_product'     => $stok['nm_product'],
                    'qty'            => $old_stock,
                    'qty_book'       => $old_booking,
                    'qty_free'       => $old_free,
                    'qty_transaksi'  => 0, // stok gudang tidak bergerak untuk dropship
                    'qty_akhir'      => $old_stock,
                    'qty_book_akhir' => $old_booking,
                    'qty_free_akhir' => $old_free,
                    'harga_stok'     => (float)($stok['harga_beli'] ?? 0),
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

        // Update status header
        $this->db->update('surat_jalan', ['status' => $status], ['id' => $id_sj]);

        // Batch update detail SJ
        if (!empty($ArrDetailBatch)) {
            $this->db->update_batch('surat_jalan_detail', $ArrDetailBatch, 'id');
        }

        // Insert kartu stok
        if (!empty($arr_kartu_stok)) {
            $this->db->insert_batch('kartu_stok', $arr_kartu_stok);
        }

        // ===== JURNAL =====
        $tgl_inv    = date('Y-m-d');
        $keterangan = 'Confirm Surat Jalan Pabrik ' . $no_surat_jalan;
        $no_po      = $no_surat_jalan;

        // Fix: $this->input->post('debet[0]') tidak bekerja di CI — harus ambil array dulu
        $debet_arr = $this->input->post('debet');
        $total     = is_array($debet_arr) ? round(str_replace(',', '', $debet_arr[0])) : 0;

        $Nomor_JV = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', $tgl_inv);
        $Bln      = substr($tgl_inv, 5, 2);
        $Thn      = substr($tgl_inv, 0, 4);

        $dataJVhead = [
            'nomor'         => $Nomor_JV,
            'tgl'           => $tgl_inv,
            'jml'           => $total,
            'koreksi_no'    => '-',
            'kdcab'         => '101',
            'jenis'         => 'JV',
            'keterangan'    => $keterangan,
            'bulan'         => $Bln,
            'tahun'         => $Thn,
            'user_id'       => $this->auth->user_id(),
            'memo'          => '',
            'tgl_jvkoreksi' => $tgl_inv,
            'ho_valid'      => '',
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
                    'debet'        => str_replace(',', '', $this->input->post('debet')[$i]),
                    'kredit'       => str_replace(',', '', $this->input->post('kredit')[$i]),
                    'created_by'   => $this->auth->user_id(),
                    'created_on'   => date('Y-m-d H:i:s'),
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

        history("Confirm Surat Jalan Pabrik : ID #{$id_sj} Status: {$status}");
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

        $this->db->select('sj.no_surat_jalan, sj.delivery_date, sj.status, c.name_customer');
        $this->db->from('surat_jalan sj');
        $this->db->join('sales_order so', 'sj.no_so = so.no_so', 'left');
        $this->db->join('master_customers c', 'so.id_customer = c.id_customer', 'left');
        $this->db->where('sj.pengiriman', 'Pabrik');
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
        $sheet->setCellValue('A1', 'REPORT SURAT JALAN PABRIK - ' . $periode);
        $sheet->mergeCells('A1:E2');

        $headers = ['A' => '#', 'B' => 'No. Surat Jalan', 'C' => 'Customer', 'D' => 'Tanggal Kirim', 'E' => 'Status'];
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
            $sheet->setCellValueExplicit('C' . $r, (string)$row->name_customer, PHPExcel_Cell_DataType::TYPE_STRING);
            if (!empty($row->delivery_date)) {
                // Ambil komponen tanggal langsung dari string (hindari konversi lewat strtotime()
                // + PHPToExcel() yang memaksa timezone UTC, karena itu bisa membuat tanggal
                // mundur 1 hari saat timezone aplikasi (Asia/Bangkok, UTC+7) berbeda dari UTC).
                $dateOnly = substr($row->delivery_date, 0, 10); // 'Y-m-d'
                list($y, $m, $d) = array_map('intval', explode('-', $dateOnly));
                $tgl = (float)PHPExcel_Shared_Date::FormattedPHPToExcel($y, $m, $d);
                $sheet->setCellValueExplicit('D' . $r, $tgl, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle('D' . $r)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            }
            $sheet->setCellValueExplicit('E' . $r, (string)$row->status, PHPExcel_Cell_DataType::TYPE_STRING);
            $r++;
        }

        $sheet->setTitle('Surat Jalan Pabrik');
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Surat_Jalan_Pabrik_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
