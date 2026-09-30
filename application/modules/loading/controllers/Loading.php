<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Loading extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Loading.View';
    protected $addPermission    = 'Loading.Add';
    protected $managePermission = 'Loading.Manage';
    protected $deletePermission = 'Loading.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->library(array('upload', 'Image_lib'));
        $this->load->model(array(
            'Loading/loading_model'
        ));

        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->title('Muat Kendaraan');
        $this->template->page_icon('fa fa-truck');
        $this->template->render('index');
    }

    public function index_approval()
    {
        $this->auth->restrict('Approval_Loading.View');
        $this->template->title('Approval Muat Kendaraan');
        $this->template->page_icon('fa fa-check');
        $this->template->render('index_approval');
    }

    public function data_side_loading()
    {
        $this->loading_model->data_side_loading();
    }

    public function data_side_approval_loading()
    {
        $this->loading_model->data_side_approval_loading();
    }

    public function get_detail_loading()
    {
        $no_loading = $this->input->get('no_loading', TRUE);

        if (!$no_loading) {
            show_404();
        }

        $data = $this->db
            ->where('no_loading', $no_loading)
            ->order_by('no_delivery')
            ->get('loading_delivery_detail')
            ->result();

        echo json_encode($data);
    }

    public function add()
    {
        $this->template->title('Atur Muatan');
        $this->template->page_icon('fa fa-clipboard');

        $data = [
            'kendaraan' => $this->db->get('master_kendaraan')->result(),
        ];

        $this->template->render('form', $data);
    }

    public function edit($id)
    {
        // Cek apakah data ada
        $loading = $this->db->get_where('loading_delivery', ['id' => $id])->row_array();

        if (!$loading) {
            show_404(); // Jika tidak ada, tampilkan error 404
        }

        $detail = $this->db
            ->select('
        ldd.*, 
        so.nama_sales
        ')
            ->from('loading_delivery_detail ldd')
            ->join('sales_order so', 'so.no_so = ldd.no_so', 'left')
            ->where('ldd.no_loading', $loading['no_loading'])
            ->get()
            ->result_array();

        //data nya sudah dibuat ke surat jalan belom?
        $usedPairs = $this->db
            ->select('no_so, no_delivery')
            ->from('surat_jalan')
            ->where('no_loading', $loading['no_loading'])
            ->get()
            ->result_array();

        $usedKeys = array_map(function ($row) {
            return $row['no_so'] . '|' . $row['no_delivery'];
        }, $usedPairs);

        // Kirim data ke view
        $data = [
            'kendaraan' => $this->db->get('master_kendaraan')->result(),
            'loading'   => $loading,
            'detail'    => $detail,
            'usedKeys'  => $usedKeys
        ];
        // View form edit
        $this->template->render('form', $data);
    }

    public function confirm_qty($id)
    {
        // Cek apakah data ada
        $loading = $this->db->get_where('loading_delivery', ['id' => $id])->row_array();

        if (!$loading) {
            show_404(); // Jika tidak ada, tampilkan error 404
        }

        $detail = $this->db
            ->select('
        ldd.*, 
        so.nama_sales
        ')
            ->from('loading_delivery_detail ldd')
            ->join('sales_order so', 'so.no_so = ldd.no_so', 'left')
            ->where('ldd.no_loading', $loading['no_loading'])
            ->get()
            ->result_array();

        // Kumpulkan id produk (ganti field sesuai tabelmu: id_product / product_id / kode_barang)
        $productIds = array_unique(array_map(function ($d) {
            return $d['id_product']; // <-- sesuaikan nama kolom
        }, $detail));

        $stockMap = [];
        if (!empty($productIds)) {
            // kalau ada kolom gudang, filter juga: ->where('warehouse_id', $loading['id_gudang'])
            $rows = $this->db->select('code_lv4, qty_free')   // <-- sesuaikan kolom qty
                ->from('warehouse_stock')
                ->where_in('code_lv4', $productIds)
                ->get()->result_array();

            foreach ($rows as $r) {
                $stockMap[$r['code_lv4']] = (float)$r['qty_free'];
            }
        }

        // sisipkan stok ke tiap baris detail
        foreach ($detail as &$d) {
            $pid = $d['id_product'];
            $d['stock_aktual'] = isset($stockMap[$pid]) ? $stockMap[$pid] : 0;
        }
        unset($d);


        //data nya sudah dibuat ke surat jalan belom?
        $usedPairs = $this->db
            ->select('no_so, no_delivery')
            ->from('surat_jalan')
            ->where('no_loading', $loading['no_loading'])
            ->get()
            ->result_array();

        $usedKeys = array_map(function ($row) {
            return $row['no_so'] . '|' . $row['no_delivery'];
        }, $usedPairs);

        // Kirim data ke view
        $data = [
            'kendaraan' => $this->db->get('master_kendaraan')->result(),
            'loading'   => $loading,
            'detail'    => $detail,
            'usedKeys'  => $usedKeys,
            'mode'      => 'confirm_qty'
        ];

        // View form edit
        $this->template->page_icon('fa fa-check-square-o');
        $this->template->title('Confirm QTY');
        $this->template->render('form', $data);
    }

    public function confirm_berat($id)
    {
        // Cek apakah data ada
        $loading = $this->db->get_where('loading_delivery', ['id' => $id])->row_array();

        if (!$loading) {
            show_404(); // Jika tidak ada, tampilkan error 404
        }

        $detail = $this->db
            ->select('
        ldd.*, 
        so.nama_sales
        ')
            ->from('loading_delivery_detail ldd')
            ->join('sales_order so', 'so.no_so = ldd.no_so', 'left')
            ->where('ldd.no_loading', $loading['no_loading'])
            ->get()
            ->result_array();

        //data nya sudah dibuat ke surat jalan belom?
        $usedPairs = $this->db
            ->select('no_so, no_delivery')
            ->from('surat_jalan')
            ->where('no_loading', $loading['no_loading'])
            ->get()
            ->result_array();

        $usedKeys = array_map(function ($row) {
            return $row['no_so'] . '|' . $row['no_delivery'];
        }, $usedPairs);

        // Kirim data ke view
        $data = [
            'kendaraan' => $this->db->get('master_kendaraan')->result(),
            'loading'   => $loading,
            'detail'    => $detail,
            'usedKeys'  => $usedKeys,
            'mode'      => 'confirm_berat'
        ];

        // View form edit
        $this->template->page_icon('fa fa-check-square-o');
        $this->template->title('Confirm Berat');
        $this->template->render('form', $data);
    }

    public function approval($id)
    {
        // Cek apakah data ada
        $loading = $this->db->get_where('loading_delivery', ['id' => $id])->row_array();

        if (!$loading) {
            show_404(); // Jika tidak ada, tampilkan error 404
        }

        $detail = $this->db
            ->select('
        ldd.*, 
        so.nama_sales
        ')
            ->from('loading_delivery_detail ldd')
            ->join('sales_order so', 'so.no_so = ldd.no_so', 'left')
            ->where('ldd.no_loading', $loading['no_loading'])
            ->get()
            ->result_array();

        //data nya sudah dibuat ke surat jalan belom?
        $usedPairs = $this->db
            ->select('no_so, no_delivery')
            ->from('surat_jalan')
            ->where('no_loading', $loading['no_loading'])
            ->get()
            ->result_array();

        $usedKeys = array_map(function ($row) {
            return $row['no_so'] . '|' . $row['no_delivery'];
        }, $usedPairs);

        // Kirim data ke view
        $data = [
            'kendaraan' => $this->db->get('master_kendaraan')->result(),
            'loading'   => $loading,
            'detail'    => $detail,
            'usedKeys'  => $usedKeys,
            'mode'      => 'approval'
        ];

        // View form edit
        $this->template->render('form', $data);
    }

    // reject 
    public function reject($id = null)
    {
        if (!$id) {
            echo json_encode(['save' => 0, 'message' => 'ID tidak ditemukan']);
            return;
        }

        $loading = $this->db->get_where('loading_delivery', ['id' => $id])->row();
        if (!$loading) {
            echo json_encode(['save' => 0, 'message' => 'Data tidak ditemukan']);
            return;
        }

        $reason = $this->input->post('reason');
        if (!$reason) {
            echo json_encode(['save' => 0, 'message' => 'Alasan harus diisi']);
            return;
        }

        $data = [
            'status' => 0,
            'reject_reason' => $reason,
        ];

        $this->db->where('id', $id);
        $update = $this->db->update('loading_delivery', $data);

        if ($update) {
            echo json_encode(['save' => 1]);
        } else {
            echo json_encode(['save' => 0, 'message' => 'Gagal menyimpan alasan penolakan']);
        }
    }

    public function print($id)
    {
        // Cek apakah data ada
        $loading = $this->db->get_where('loading_delivery', ['id' => $id])->row_array();

        if (!$loading) {
            show_404(); // Jika tidak ada, tampilkan error 404
        }

        $detail = $this->db->get_where('loading_delivery_detail', ['no_loading' => $loading['no_loading']])->result_array();

        //data nya sudah dibuat ke surat jalan belom?
        $usedPairs = $this->db
            ->select('no_so, no_delivery')
            ->from('surat_jalan')
            ->where('no_loading', $loading['no_loading'])
            ->get()
            ->result_array();

        $usedKeys = array_map(function ($row) {
            return $row['no_so'] . '|' . $row['no_delivery'];
        }, $usedPairs);

        $nopol = $loading['nopol'];

        $kendaraan = $this->db
            ->get_where('master_kendaraan', ['nopol' => $nopol])
            ->row_array();

        // Kirim data ke view
        $data = [
            'kendaraan' => $kendaraan,
            'loading'   => $loading,
            'detail'    => $detail,
            'usedKeys'  => $usedKeys,
        ];

        // View form edit
        $this->load->view('print', ['results' => $data]);
    }

    public function get_spk()
    {
        $data = $this->db
            ->select('
            s.no_delivery,
            s.no_so,
            s.pengiriman,
            s.notes,
            so.nama_sales,
            DATE_FORMAT(s.tanggal_spk, "%d %M %Y") AS tanggal_spk,
            c.name_customer,
            d.id,
            d.id_product,
            p.nama,
            p.weight,
            d.qty_spk,
            d.qty_belum_muat,
            (p.weight * d.qty_belum_muat) AS jumlah_berat
        ')
            ->from('spk_delivery_detail d')
            ->join('spk_delivery s', 's.no_delivery = d.no_delivery', 'left')
            ->join('sales_order so', 'so.no_so = s.no_so', 'left')
            ->join('master_customers c', 'c.id_customer = s.id_customer', 'left')
            ->join('new_inventory_4 p', 'p.code_lv4 = d.id_product', 'left')
            // ->join('loading_delivery_detail l', 'l.id_spk_detail = d.id', 'left') // per item, bukan per delivery
            ->where('s.pengiriman', "Gudang")
            ->where('d.qty_belum_muat >', 0)
            ->order_by('s.no_delivery')
            ->get()
            ->result();

        echo json_encode($data);
    }


    public function save()
    {
        $post = $this->input->post();
        $detail = $post['detail'];

        $is_edit = isset($post['id_loading']) && !empty($post['id_loading']);
        $no_loading = $is_edit ? $post['id_loading'] : $this->_generateNoLoading();

        $ArrHeader = [
            'no_loading'    => $no_loading,
            'pengiriman'    => "Gudang",
            'nopol'         => $post['kendaraan'],
            'kapasitas'     => str_replace(',', '', $post['kapasitas']),
            'total_berat'   => str_replace(',', '', $post['total_berat']),
            'tanggal_muat'  => date('Y-m-d H:i:s', strtotime($post['tanggal_muat'])),
        ];

        if ($is_edit) {
            $ArrHeader['updated_by'] = $this->auth->user_id();
            $ArrHeader['updated_at'] = date('Y-m-d H:i:s');
        } else {
            $ArrHeader['created_by'] = $this->auth->user_id();
            $ArrHeader['created_at'] = date('Y-m-d H:i:s');
        }

        $ArrDetail = [];

        foreach ($detail as $key => $value) {
            $no_delivery    = $value['no_delivery'];

            $ArrDetail[$key]['no_loading']      = $no_loading;
            $ArrDetail[$key]['no_delivery']     = $no_delivery;
            $ArrDetail[$key]['id_spk_detail']   = $value['id_spk_detail'];
            $ArrDetail[$key]['no_so']           = $value['no_so'];
            $ArrDetail[$key]['customer']        = $value['customer'];
            $ArrDetail[$key]['id_product']      = $value['id_product'];
            $ArrDetail[$key]['product']         = $value['product'];
            $ArrDetail[$key]['qty_muat']        = $value['qty_muat'];
            $ArrDetail[$key]['jumlah_berat']    = $value['jumlah_berat'];

            // Update status SPK
            $this->db->update('spk_delivery', ['status' => 'LOADING'], ['no_delivery' => $no_delivery]);
        }

        $this->db->trans_start();

        if ($is_edit) {
            $this->db->update('loading_delivery', $ArrHeader, ['no_loading' => $no_loading]);

            // Restore qty_belum_muat dari detail lama sebelum dihapus
            $old_details = $this->db
                ->get_where('loading_delivery_detail', ['no_loading' => $no_loading])
                ->result_array();

            foreach ($old_details as $old) {
                $spk_old = $this->db->get_where('spk_delivery_detail', ['id' => $old['id_spk_detail']])->row_array();
                if ($spk_old) {
                    $restored_belum_muat = (int)$spk_old['qty_belum_muat'] + (int)$old['qty_muat'];
                    $restored_qty_muat   = max(0, (int)$spk_old['qty_muat'] - (int)$old['qty_muat']);
                    $this->db->update(
                        'spk_delivery_detail',
                        ['qty_belum_muat' => $restored_belum_muat, 'qty_muat' => $restored_qty_muat],
                        ['id' => $old['id_spk_detail']]
                    );
                }
            }

            // Hapus detail lama, insert ulang
            $this->db->delete('loading_delivery_detail', ['no_loading' => $no_loading]);
            if (!empty($ArrDetail)) {
                $this->db->insert_batch('loading_delivery_detail', $ArrDetail);
            }
        } else {
            $this->db->insert('loading_delivery', $ArrHeader);
            $insert_id = $this->db->insert_id();
            if (!empty($ArrDetail)) {
                $this->db->insert_batch('loading_delivery_detail', $ArrDetail);
            }
        }

        // Kurangi qty_belum_muat di spk_delivery_detail sesuai qty_muat yang direncanakan
        foreach ($ArrDetail as $d) {
            $id_spk_detail = (int)$d['id_spk_detail'];
            $qty_muat      = (int)$d['qty_muat'];

            $spk = $this->db->get_where('spk_delivery_detail', ['id' => $id_spk_detail])->row_array();
            if ($spk) {
                $sisa_baru   = max(0, (int)$spk['qty_belum_muat'] - $qty_muat);
                $sudah_muat  = (int)$spk['qty_spk'] - $sisa_baru;
                $this->db->update(
                    'spk_delivery_detail',
                    ['qty_belum_muat' => $sisa_baru, 'qty_muat' => $sudah_muat],
                    ['id' => $id_spk_detail]
                );
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $Arr_Data  = ['pesan' => 'Save gagal disimpan ...', 'status' => 0];
        } else {
            $this->db->trans_commit();
            $Arr_Data  = ['pesan' => 'Save berhasil disimpan. Thanks ...', 'status' => 1,  'id_loading' => $is_edit ? $post['id_loading'] : $insert_id];
            history(($is_edit ? "Update" : "Create") . " Muat Kendaraan : " . $no_loading);
        }

        echo json_encode($Arr_Data);
    }

    /**
     * Validasi tiap baris detail punya value numerik untuk $field.
     * Kosong/tidak ada ditolak; "0" tetap dianggap valid.
     * Return array label baris yang gagal (kosong berarti semua valid).
     */
    private function get_invalid_detail_rows($detail, $field)
    {
        $invalid = [];
        foreach ($detail as $value) {
            $val = isset($value[$field]) ? trim((string)$value[$field]) : '';
            if ($val === '' || !is_numeric($val)) {
                $invalid[] = trim(
                    (isset($value['no_delivery']) ? $value['no_delivery'] : '-') . ' / ' .
                        (isset($value['no_so']) ? $value['no_so'] : '-') . ' - ' .
                        (isset($value['product']) ? $value['product'] : '-')
                );
            }
        }
        return $invalid;
    }

    public function save_confirm_qty()
    {
        $post      = $this->input->post();
        $detail    = $post['detail'];
        $no_loading = $post['id_loading'];

        $invalid_rows = $this->get_invalid_detail_rows($detail, 'qty_aktual');
        if (!empty($invalid_rows)) {
            echo json_encode([
                'pesan'  => 'Qty Aktual kosong / tidak valid pada baris: ' . implode('; ', $invalid_rows) . '. Isi terlebih dulu sebelum konfirmasi.',
                'status' => 0
            ]);
            return;
        }

        $ArrHeader = [
            'no_loading'    => $no_loading,
            'pengiriman'    => "Gudang",
            'nopol'         => $post['kendaraan'],
            'kapasitas'     => str_replace(',', '', $post['kapasitas']),
            'total_berat'   => str_replace(',', '', $post['total_berat']),
            'tanggal_muat'  => date('Y-m-d H:i:s', strtotime($post['tanggal_muat'])),
            'updated_by'    => $this->auth->user_id(),
            'updated_at'    => date('Y-m-d H:i:s'),
            'status'        => 1,
        ];

        $ArrDetail = [];
        $headerStatus = []; // kumpulkan status per no_delivery

        $this->db->trans_begin();

        // ── STEP 1: Restore qty_belum_muat dari detail loading yang lama ──────
        // Supaya tidak double-deduct jika confirm dilakukan ulang
        $old_details = $this->db
            ->get_where('loading_delivery_detail', ['no_loading' => $no_loading])
            ->result_array();

        foreach ($old_details as $old) {
            $spk_old = $this->db->get_where('spk_delivery_detail', ['id' => $old['id_spk_detail']])->row_array();
            if ($spk_old) {
                $restored_belum_muat = (int)$spk_old['qty_belum_muat'] + (int)$old['qty_muat'];
                $restored_qty_muat   = max(0, (int)$spk_old['qty_muat'] - (int)$old['qty_muat']);
                // Pastikan tidak melebihi qty_spk
                $restored_belum_muat = min($restored_belum_muat, (int)$spk_old['qty_spk']);
                $this->db->update(
                    'spk_delivery_detail',
                    ['qty_belum_muat' => $restored_belum_muat, 'qty_muat' => $restored_qty_muat],
                    ['id' => $old['id_spk_detail']]
                );
            }
        }

        // ── STEP 2: Hitung dan update qty_belum_muat berdasarkan qty_aktual ───
        foreach ($detail as $key => $value) {
            $no_delivery    = $value['no_delivery'];
            $id_spk_detail  = (int)$value['id_spk_detail'];
            $qty_aktual     = (int)$value['qty_aktual']; // benar-benar dimuat

            // simpan histori muatan (detail loading)
            $ArrDetail[$key]['no_loading']    = $no_loading;
            $ArrDetail[$key]['no_delivery']   = $no_delivery;
            $ArrDetail[$key]['id_spk_detail'] = $id_spk_detail;
            $ArrDetail[$key]['no_so']         = $value['no_so'];
            $ArrDetail[$key]['customer']      = $value['customer'];
            $ArrDetail[$key]['id_product']    = $value['id_product'];
            $ArrDetail[$key]['product']       = $value['product'];
            $ArrDetail[$key]['qty_muat']      = $qty_aktual;
            $ArrDetail[$key]['jumlah_berat']  = $value['jumlah_berat'];
            $ArrDetail[$key]['keterangan']    = $value['keterangan'];

            // Baca nilai terbaru dari DB (sudah di-restore di STEP 1)
            $spk = $this->db->get_where('spk_delivery_detail', ['id' => $id_spk_detail])->row_array();
            if (!$spk) {
                $this->db->trans_rollback();
                show_404();
            }

            $rencana   = (int)$spk['qty_spk'];
            $sisa_lama = (int)$spk['qty_belum_muat']; // sudah di-restore, ini nilai bersih

            // Batasi qty_aktual agar tidak melebihi sisa yang tersedia
            $muat      = max(0, min($qty_aktual, $sisa_lama));
            $sisa_baru = max(0, $sisa_lama - $muat);
            $sudah_muat_baru = $rencana - $sisa_baru;

            $this->db->update(
                'spk_delivery_detail',
                [
                    'qty_belum_muat' => $sisa_baru,
                    'qty_muat'       => $sudah_muat_baru
                ],
                ['id' => $id_spk_detail]
            );

            // Catat status per no_delivery: tetap LOADING
            if (!isset($headerStatus[$no_delivery])) {
                $headerStatus[$no_delivery] = 'LOADING';
            }
        }

        // ── STEP 3: Update header + replace detail loading ────────────────────
        $this->db->update('loading_delivery', $ArrHeader, ['no_loading' => $no_loading]);
        $this->db->delete('loading_delivery_detail', ['no_loading' => $no_loading]);
        if (!empty($ArrDetail)) {
            $this->db->insert_batch('loading_delivery_detail', $ArrDetail);
        }

        // ── STEP 4: Update status header SPK per no_delivery ─────────────────
        foreach ($headerStatus as $nd => $st) {
            $this->db->update('spk_delivery', ['status' => $st], ['no_delivery' => $nd]);
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $Arr_Data  = ['pesan' => 'Save gagal disimpan ...', 'status' => 0];
        } else {
            $this->db->trans_commit();
            $Arr_Data  = ['pesan' => 'Save berhasil disimpan. Thanks ...', 'status' => 1];
            history("Update Muat Kendaraan : " . $no_loading);
        }

        echo json_encode($Arr_Data);
    }


    public function save_confirm_berat()
    {
        $post = $this->input->post();
        $detail = $post['detail'];
        $no_loading =  $post['id_loading'];

        // Catatan: yang benar-benar disimpan ke DB adalah jumlah_berat (di-sync
        // otomatis oleh input .berat-aktual di JS), bukan berat_aktual itu sendiri.
        $invalid_rows = $this->get_invalid_detail_rows($detail, 'jumlah_berat');
        if (!empty($invalid_rows)) {
            echo json_encode([
                'pesan'  => 'Berat Aktual kosong / tidak valid pada baris: ' . implode('; ', $invalid_rows) . '. Isi terlebih dulu sebelum konfirmasi.',
                'status' => 0
            ]);
            return;
        }

        $ArrHeader = [
            'no_loading'    => $no_loading,
            'pengiriman'    => "Gudang",
            'nopol'         => $post['kendaraan'],
            'kapasitas'     => str_replace(',', '', $post['kapasitas']),
            'total_berat'   => str_replace(',', '', $post['total_berat']),
            'tanggal_muat'  => date('Y-m-d H:i:s', strtotime($post['tanggal_muat'])),
            'updated_by'    => $this->auth->user_id(),
            'updated_at'    => date('Y-m-d H:i:s'),
            'status'        => 2,
        ];

        $ArrDetail = [];

        foreach ($detail as $key => $value) {
            $ArrDetail[$key]['no_loading']      = $no_loading;
            $ArrDetail[$key]['no_delivery']     = $value['no_delivery'];
            $ArrDetail[$key]['id_spk_detail']   = $value['id_spk_detail'];
            $ArrDetail[$key]['no_so']           = $value['no_so'];
            $ArrDetail[$key]['customer']        = $value['customer'];
            $ArrDetail[$key]['id_product']      = $value['id_product'];
            $ArrDetail[$key]['product']         = $value['product'];
            $ArrDetail[$key]['qty_muat']        = $value['qty_muat'];
            $ArrDetail[$key]['jumlah_berat']    = $value['jumlah_berat'];
            $ArrDetail[$key]['keterangan']      = $value['keterangan'];
        }

        $this->db->trans_start();

        $this->db->update('loading_delivery', $ArrHeader, ['no_loading' => $no_loading]);
        $this->db->delete('loading_delivery_detail', ['no_loading' => $no_loading]);

        if (!empty($ArrDetail)) {
            $this->db->insert_batch('loading_delivery_detail', $ArrDetail);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $Arr_Data  = ['pesan' => 'Save gagal disimpan ...', 'status' => 0];
        } else {
            $this->db->trans_commit();
            history("Update Muat Kendaraan : " . $no_loading);

            $manager_number = '6285158115205'; // sudah diformat internasional +62
            $approval_link = base_url("loading/index_approval/");
            $wa_message = "Info, Muatan No. *$no_loading* butuh approval, silahkan akses link berikut:\n$approval_link";

            $wa_response = $this->send_wa($manager_number, $wa_message);

            $Arr_Data  = [
                'pesan' => 'Save berhasil disimpan. Thanks ...',
                'status' => 1,
                'wa_response' => $wa_response
            ];
        }

        echo json_encode($Arr_Data);
    }

    public function approve()
    {
        $post = $this->input->post();
        $detail = $post['detail'];

        $no_loading =  $post['id_loading'];

        $ArrHeader = [
            'no_loading'    => $no_loading,
            // 'pengiriman'    => $post['pengiriman'],
            // 'nopol'         => $post['kendaraan'],
            'kapasitas'     => str_replace(',', '', $post['kapasitas']),
            'total_berat'   => str_replace(',', '', $post['total_berat']),
            'tanggal_muat'  => date('Y-m-d H:i:s', strtotime($post['tanggal_muat'])),
            'updated_by'    => $this->auth->user_id(),
            'updated_at'    => date('Y-m-d H:i:s'),
            'status'        => 3,
        ];

        $ArrDetail = [];

        foreach ($detail as $key => $value) {
            $no_delivery = $value['no_delivery'];

            $ArrDetail[$key]['no_loading']      = $no_loading;
            $ArrDetail[$key]['no_delivery']     = $no_delivery;
            $ArrDetail[$key]['id_spk_detail']   = $value['id_spk_detail'];
            $ArrDetail[$key]['no_so']           = $value['no_so'];
            $ArrDetail[$key]['customer']        = $value['customer'];
            $ArrDetail[$key]['id_product']      = $value['id_product'];
            $ArrDetail[$key]['product']         = $value['product'];
            $ArrDetail[$key]['qty_muat']        = $value['qty_muat'];
            $ArrDetail[$key]['jumlah_berat']    = $value['jumlah_berat'];
            // $ArrDetail[$key]['keterangan']      = $value['keterangan'];
        }

        $this->db->trans_start();


        $this->db->update('loading_delivery', $ArrHeader, ['no_loading' => $no_loading]);

        // Hapus detail lama, insert ulang
        $this->db->delete('loading_delivery_detail', ['no_loading' => $no_loading]);
        if (!empty($ArrDetail)) {
            $this->db->insert_batch('loading_delivery_detail', $ArrDetail);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $Arr_Data  = ['pesan' => 'Save gagal disimpan ...', 'status' => 0];
        } else {
            $this->db->trans_commit();
            $Arr_Data  = ['pesan' => 'Save berhasil disimpan. Thanks ...', 'status' => 1];
            history("Approve Muat Kendaraan : " . $no_loading);
        }

        echo json_encode($Arr_Data);
    }

    public function delete_detail()
    {
        $id = $this->input->post('id', TRUE);

        if ($id) {
            $this->db->where('id', $id);
            $this->db->delete('loading_delivery_detail');
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'failed', 'message' => 'ID not provided']);
        }
    }

    public function get_view()
    {
        $no_loading = $this->input->get('no_loading', TRUE);

        $header = $this->db->get_where('loading_delivery', ['no_loading' => $no_loading])->row_array();
        $detail = $this->db->get_where('loading_delivery_detail', ['no_loading' => $no_loading])->result_array();

        echo json_encode([
            'header' => $header,
            'detail' => $detail
        ]);
    }


    // Private Function Section 

    private function _generateNoLoading()
    {
        $Ym = date('ym');
        $SQL = "SELECT MAX(no_loading) as maxM FROM loading_delivery WHERE no_loading LIKE 'MK" . $Ym . "%'";
        $result = $this->db->query($SQL)->result_array();
        $angkaUrut = $result[0]['maxM'];
        $urutan = (int)substr($angkaUrut, 6, 4);
        $urutan++;
        return "MK" . $Ym . sprintf('%04s', $urutan);
    }

    private function updateStatusSPKByNoSO($no_so)
    {
        $summary = $this->db
            ->select('SUM(qty_belum_spk) as total_belum_spk')
            ->from('spk_delivery_detail')
            ->where('no_so', $no_so)
            ->get()
            ->row_array();

        $status_spk = ($summary['total_belum_spk'] > 0) ? 'SPK Sebagian' : 'SPK Lengkap';

        $this->db->update('sales_order', ['status_spk' => $status_spk], ['no_so' => $no_so]);
    }

    public function cancel_loading()
    {
        $this->auth->restrict($this->deletePermission);

        $id = $this->input->post('id');

        if (!$id) {
            echo json_encode(['status' => 0, 'pesan' => 'ID tidak ditemukan']);
            return;
        }

        // Ambil data loading
        $loading = $this->db->get_where('loading_delivery', ['id' => $id])->row_array();

        if (!$loading) {
            echo json_encode(['status' => 0, 'pesan' => 'Data loading tidak ditemukan']);
            return;
        }

        // Validasi: Hanya bisa cancel jika status = Draft (0)
        if ($loading['status'] != 0) {
            echo json_encode(['status' => 0, 'pesan' => 'Loading tidak bisa di-cancel. Status bukan Draft.']);
            return;
        }

        $no_loading = $loading['no_loading'];

        // Validasi: Cek apakah sudah ada surat jalan
        $cek_sj = $this->db->get_where('surat_jalan', ['no_loading' => $no_loading])->num_rows();
        if ($cek_sj > 0) {
            echo json_encode(['status' => 0, 'pesan' => 'Loading tidak bisa di-cancel. Sudah ada Surat Jalan terkait.']);
            return;
        }

        // Ambil semua detail loading
        $details = $this->db->get_where('loading_delivery_detail', ['no_loading' => $no_loading])->result_array();

        if (empty($details)) {
            echo json_encode(['status' => 0, 'pesan' => 'Detail loading kosong']);
            return;
        }

        // Simpan list no_delivery untuk audit trail
        $no_deliveries = array_unique(array_column($details, 'no_delivery'));
        $cancelled_spk_list = implode(', ', $no_deliveries);

        $this->db->trans_begin();

        // Restore qty_belum_muat dan qty_muat untuk setiap item
        foreach ($details as $detail) {
            $id_spk_detail = $detail['id_spk_detail'];
            $qty_muat      = (int)$detail['qty_muat'];

            // Ambil data SPK detail
            $spk_detail = $this->db->get_where('spk_delivery_detail', ['id' => $id_spk_detail])->row_array();

            if ($spk_detail) {
                // Restore qty
                $qty_belum_muat_baru = (int)$spk_detail['qty_belum_muat'] + $qty_muat;
                $qty_muat_baru       = max(0, (int)$spk_detail['qty_muat'] - $qty_muat);

                // Pastikan tidak melebihi qty_spk
                $qty_belum_muat_baru = min($qty_belum_muat_baru, (int)$spk_detail['qty_spk']);

                $this->db->update(
                    'spk_delivery_detail',
                    [
                        'qty_belum_muat' => $qty_belum_muat_baru,
                        'qty_muat'       => $qty_muat_baru
                    ],
                    ['id' => $id_spk_detail]
                );
            }
        }

        // Update status SPK header untuk setiap no_delivery yang terkait
        $no_deliveries = array_unique(array_column($details, 'no_delivery'));

        foreach ($no_deliveries as $no_delivery) {
            // Cek apakah ada loading lain yang masih menggunakan no_delivery ini
            $cek_loading_lain = $this->db
                ->where('no_delivery', $no_delivery)
                ->where('no_loading !=', $no_loading)
                ->get('loading_delivery_detail')
                ->num_rows();

            if ($cek_loading_lain == 0) {
                // Tidak ada loading lain, kembalikan status ke NOT YET DELIVER
                $this->db->update(
                    'spk_delivery',
                    ['status' => 'NOT YET DELIVER'],
                    ['no_delivery' => $no_delivery]
                );
            } else {
                // Masih ada loading lain, tetap gunakan status LOADING
                // (tidak ada status PARTIAL LOADING di SPK Delivery)
                $this->db->update(
                    'spk_delivery',
                    ['status' => 'LOADING'],
                    ['no_delivery' => $no_delivery]
                );
            }
        }

        // Hapus detail loading
        $this->db->delete('loading_delivery_detail', ['no_loading' => $no_loading]);

        // Update loading header - set status cancelled dengan audit trail
        $this->db->update(
            'loading_delivery',
            [
                'status'              => -1,
                'cancelled_spk_list'  => $cancelled_spk_list,
                'cancelled_by'        => $this->auth->user_id(),
                'cancelled_at'        => date('Y-m-d H:i:s'),
                'updated_by'          => $this->auth->user_id(),
                'updated_at'          => date('Y-m-d H:i:s')
            ],
            ['no_loading' => $no_loading]
        );

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            echo json_encode(['status' => 0, 'pesan' => 'Gagal cancel loading']);
        } else {
            $this->db->trans_commit();
            history("Cancel Loading: " . $no_loading);
            echo json_encode(['status' => 1, 'pesan' => 'Loading berhasil di-cancel. SPK dikembalikan ke status Waiting Loading.']);
        }
    }

    private function send_wa($number, $message)
    {
        $url = 'https://app.whacenter.com/api/send';

        $data = [
            //'device_id' => 'ea118812b9454dc34a477ae1c053f0fc', //device yang lama
            'device_id' => 'c7c1b12f27cffcc9f49d92533a274c4d',
            'number'    => $number,
            'message'   => $message
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function export_excel()
    {
        $start = $this->input->get('start_date', true);
        $end   = $this->input->get('end_date', true);

        $sub_list_spk = "
            SELECT d.no_loading, GROUP_CONCAT(DISTINCT d.no_delivery ORDER BY d.no_delivery SEPARATOR ', ') AS list_spk
            FROM loading_delivery_detail d GROUP BY d.no_loading
        ";

        $where = " WHERE 1=1 ";
        $params = [];
        if (!empty($start)) {
            $where .= " AND l.tanggal_muat >= ? ";
            $params[] = $start;
        }
        if (!empty($end)) {
            $where .= " AND l.tanggal_muat <= ? ";
            $params[] = $end;
        }

        $sql = "SELECT l.no_loading, l.nopol, l.pengiriman, l.total_berat, l.kapasitas, l.tanggal_muat, l.status, COALESCE(s.list_spk,'') AS list_spk
                FROM loading_delivery l LEFT JOIN ($sub_list_spk) s ON s.no_loading = l.no_loading
                $where ORDER BY l.tanggal_muat DESC";

        $rows = $this->db->query($sql, $params)->result();

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
        $sheet->setCellValue('A1', 'REPORT MUAT KENDARAAN - ' . $periode);
        $sheet->mergeCells('A1:H2');

        $headers = ['A' => '#', 'B' => 'No. Muat Kendaraan', 'C' => 'No. SPK Delivery', 'D' => 'Nopol Kendaraan', 'E' => 'Pengiriman', 'F' => 'Total Berat (Kg)', 'G' => 'Tanggal Muat', 'H' => 'Status'];
        $rowHeader = 4;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $statusMap = [-1 => 'Cancelled', 0 => 'Draft', 1 => 'Confirm QTY', 2 => 'Confirm Berat', 3 => 'Approved'];
        $r = $rowHeader + 1;
        $no = 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValueExplicit('B' . $r, (string)$row->no_loading, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $r, (string)$row->list_spk, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $r, (string)$row->nopol, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $r, (string)$row->pengiriman, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $r, (float)$row->total_berat, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
            if (!empty($row->tanggal_muat)) {
                // Ambil komponen tanggal langsung dari string (hindari konversi lewat strtotime()
                // + PHPToExcel() yang memaksa timezone UTC, karena itu bisa membuat tanggal
                // mundur 1 hari saat timezone aplikasi (Asia/Bangkok, UTC+7) berbeda dari UTC).
                $dateOnly = substr($row->tanggal_muat, 0, 10); // 'Y-m-d'
                list($y, $m, $d) = array_map('intval', explode('-', $dateOnly));
                $tgl = (float)PHPExcel_Shared_Date::FormattedPHPToExcel($y, $m, $d);
                $sheet->setCellValueExplicit('G' . $r, $tgl, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle('G' . $r)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            }
            $sheet->setCellValueExplicit('H' . $r, isset($statusMap[$row->status]) ? $statusMap[$row->status] : (string)$row->status, PHPExcel_Cell_DataType::TYPE_STRING);
            $r++;
        }

        $sheet->setTitle('Muat Kendaraan');
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        if (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Muat_Kendaraan_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
