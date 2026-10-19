<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Product_costing extends Admin_Controller
{
    //Permission
    protected $viewPermission     = 'Product_Costing.View';
    protected $addPermission      = 'Product_Costing.Add';
    protected $managePermission   = 'Product_Costing.Manage';
    protected $deletePermission   = 'Product_Costing.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Product_costing/product_costing_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $session = $this->session->userdata('app_session');

        $product_price   = $this->db->select('MAX(update_date) AS updated_date')->get('product_price')->result();
        $last_update     = "Last Update: " . date('d-M-Y H:i:s', strtotime($product_price[0]->updated_date));
        $data = [
            'product_lv1' => array(),
            'last_update' => $last_update
        ];

        history("View index product costing");
        $this->template->title('Product Costing');
        $this->template->page_icon('fa fa-cubes');
        $this->template->render('index', $data);
    }

    public function add()
    {
        $product = $this->db
            ->select('new_inventory_4.*')
            ->from('new_inventory_4')
            ->join('product_costing', 'product_costing.code_lv4 = new_inventory_4.code_lv4', 'left')
            ->where('new_inventory_4.price_ref IS NOT NULL')
            ->where('new_inventory_4.deleted_date IS NULL')
            ->where('product_costing.code_lv4 IS NULL') // belum terdaftar
            ->get()
            ->result_array();

        $costing = $this->db->get_where('costing_rate', array('deleted_date' => NULL))->result_array();

        $data = [
            'product'   => $product,
            'costing'   => $costing,
        ];
        $this->template->title('Add Costing');
        $this->template->page_icon('fa fa-edit');
        $this->template->render('add', $data);
    }

    public function edit()
    {
        $id = $this->input->post('id');
        if (!$id) {
            show_error("ID tidak ditemukan", 400);
        }
        $procost = $this->db->get_where('product_costing', ['id' => $id])->row();
        $kompetitor = $this->db->get_where('product_costing_kompetitor', ['id_product_costing' => $procost->id])->result();

        $product = $this->db->get_where('new_inventory_4', array('price_ref !=' => NULL))->result_array();
        $costing = $this->db->get_where('costing_rate', array('deleted_date' => NULL))->result_array();

        $data = [
            'product'       => $product,
            'costing'       => $costing,
            'procost'       => $procost,
            'kompetitor'    => $kompetitor,
        ];
        $this->template->title('Edit Costing');
        $this->template->page_icon('fa fa-edit');
        $this->template->render('add', $data);
    }

    public function save()
    {
        $data = $this->input->post();
        $id = $data['id']; // <-- id untuk update, bisa null/kosong saat insert

        // Ambil data inventory
        $id_product = $data['product_id'];
        $inven = $this->db->get_where('new_inventory_4', ['code_lv4' => $id_product])->row();
        if (!$inven) {
            show_error('Produk tidak ditemukan.');
        }

        $is_update = !empty($id);
        $id_product_costing = $is_update ? $id : $this->product_costing_model->generate_id();

        $header = [
            'id'                => $id_product_costing,
            'code_lv1'          => $inven->code_lv1,
            'code_lv2'          => $inven->code_lv2,
            'code_lv3'          => $inven->code_lv3,
            'code_lv4'          => $inven->code_lv4,
            'product_name'      => $inven->nama,
            'harga_beli'        => str_replace(',', '', $data['harga_beli']),
            'biaya_import'      => str_replace(',', '', $data['biaya_import']),
            'biaya_cabang'      => str_replace(',', '', $data['biaya_cabang']),
            'biaya_logistik'    => str_replace(',', '', $data['biaya_logistik']),
            'biaya_ho'          => str_replace(',', '', $data['biaya_ho']),
            'biaya_marketing'   => str_replace(',', '', $data['biaya_marketing']),
            'biaya_interest'    => str_replace(',', '', $data['biaya_interest']),
            'biaya_profit'      => str_replace(',', '', $data['biaya_profit']),
            'price'             => str_replace(',', '', $data['price']),
            'dropship_price'    => str_replace(',', '', $data['dropship_price']),
            'dropship_tempo'    => str_replace(',', '', $data['dropship_tempo']),
            'propose_price'     => str_replace(',', '', $data['propose_price']),
            'status'            => "WA",
        ];

        if ($is_update) {
            // Ambil revisi terakhir
            $last = $this->db->select('revisi')->get_where('product_costing', ['id' => $id])->row();
            $header['revisi'] = $last ? intval($last->revisi) + 1 : 1;
            $header['modified_by'] = $this->auth->user_id();
            $header['modified_at'] = date('Y-m-d H:i:s');
        } else {
            $header['created_by'] = $this->auth->user_id();
            $header['created_at'] = date('Y-m-d H:i:s');
            $header['revisi'] = 0;
        }

        // Insert/update product_costing
        $this->db->trans_start();
        if ($is_update) {
            $this->db->where('id', $id);
            $this->db->update('product_costing', $header);
            $id_product_costing = $id;
        } else {
            $this->db->insert('product_costing', $header);
            $id_product_costing = $header['id']; // pakai ID yang baru dibuat
        }

        // ❌ DINONAKTIFKAN (2026-10-14)
        // Blok di bawah dulu menimpa warehouse_stock.harga_beli dengan harga dari Product Costing
        // (yang bersumber dari new_inventory_4.price_ref / harga referensi jual). Ini KELIRU karena
        // warehouse_stock.harga_beli adalah HARGA POKOK PERSEDIAAN (moving average dari incoming),
        // bukan harga referensi. Overwrite ini menyebabkan costbook Surat Jalan & HPP Invoice ikut
        // salah (lihat kasus SJ/G/2609/0072, produk P425000233: 11.497,79 tertimpa jadi 13.200).
        //
        // Harga pokok persediaan hanya boleh diubah oleh Incoming Check (moving average) dan
        // Adjustment Stok. Product Costing cukup menyimpan harga jual/referensi di tabel
        // product_costing saja.
        //
        // if (!empty($header['code_lv4']) && isset($header['harga_beli'])) {
        //     $this->db->where('code_lv4', $header['code_lv4']);
        //     $this->db->update('warehouse_stock', [
        //         'harga_beli' => $header['harga_beli'],
        //         'update_by' => $this->auth->user_id(),
        //         'update_date' => date('Y-m-d H:i:s')
        //     ]);
        // }

        // Hapus dan simpan ulang kompetitor
        if ($is_update) {
            $this->db->delete('product_costing_kompetitor', ['id_product_costing' => $id_product_costing]);
        }
        if (isset($_POST['kompetitor']) && is_array($_POST['kompetitor'])) {
            $kompetitor_data = [];
            foreach ($_POST['kompetitor'] as $komp) {
                $kompetitor_data[] = [
                    'id_product_costing' => $id_product_costing,
                    'nama'               => $komp['nama'],
                    'harga'              => str_replace(',', '', $komp['harga']),
                ];
            }
            if (!empty($kompetitor_data)) {
                $this->db->insert_batch('product_costing_kompetitor', $kompetitor_data);
            }
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $status    = array(
                'pesan'        => 'Gagal Save. Try Again Later ...',
                'status'    => 0
            );
        } else {
            $this->db->trans_commit();
            $status    = array(
                'pesan'        => 'Success Save. Thanks ...',
                'status'    => 1
            );
        }

        echo json_encode($status);
    }

    public function generate_price_list_ajax()
    {
        if ($this->generate_price_list()) {
            echo json_encode([
                'error' => false,
                'message' => 'Kalkulasi berhasil diperbarui.',
                'last_update' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode([
                'error' => true,
                'message' => 'Produk atau toko kosong.'
            ]);
        }
    }


    public function generate_price_list()
    {
        $products = $this->db->get_where('product_costing', ['status' => 'A'])->result_array();
        $tokoList = $this->db->order_by('urutan', 'asc')->get('master_persentase')->result_array();

        if (empty($products) || empty($tokoList)) {
            return false;
        }

        $this->db->truncate('master_kalkulasi_price_list');

        foreach ($products as $product) {
            $harga_awal = $product['propose_price'];
            $current_cash = $harga_awal;

            foreach ($tokoList as $index => $toko) {
                $cash_percent = floatval($toko['cash']) / 100;
                $tempo_percent = floatval($toko['tempo']) / 100;

                $current_cash = $current_cash + ($current_cash * $cash_percent);
                $current_tempo = $current_cash + ($current_cash * $tempo_percent);

                $data = [
                    'id_product' => $product['code_lv4'],
                    'product_name' => $product['product_name'],
                    'toko' => $toko['nama'],
                    'cash' => ceil($current_cash / 100) * 100,
                    'tempo' => ceil($current_tempo / 100) * 100,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                $this->db->insert('master_kalkulasi_price_list', $data);

                $current_cash = $current_tempo;
            }
        }

        return true;
    }

    public function list_price_list()
    {
        $this->template->title('Price List');
        $this->template->page_icon('fa fa-list-alt');
        // Ambil semua code_lv2 yang digunakan di product list
        $usedCodeLv2 = $this->db
            ->distinct()
            ->select('code_lv2')
            ->get('new_inventory_4')
            ->result_array();

        // Ambil array code_lv2 saja
        $codeLv2List = array_column($usedCodeLv2, 'code_lv2');

        // Ambil kategori berdasarkan code_lv2 yang digunakan
        $kategoriList = [];
        if (!empty($codeLv2List)) {
            $kategoriList = $this->db
                ->select('code_lv2, nama')
                ->where('category', 'product')
                ->where('deleted_date', null)
                ->where_in('code_lv2', $codeLv2List)
                ->order_by('nama', 'asc')
                ->get('new_inventory_2')
                ->result_array();
        }

        // Ambil semua toko dengan urutan (untuk header)
        $tokoList = $this->db->order_by('urutan', 'asc')->get('master_persentase')->result_array();

        // Ambil semua kalkulasi dari DB
        $rows = $this->db
            ->select('
                        mkp.*,          
                        ni4.code_lv4
                    ')
            ->from('master_kalkulasi_price_list mkp')
            ->join('new_inventory_4 ni4', 'ni4.code_lv4 = mkp.id_product', 'left')
            ->where('ni4.deleted_date', null)
            ->where('ni4.deleted_by', null)
            ->get()
            ->result_array();

        // Ambil data dropship dari tabel product_costing
        $costing = $this->db
            ->select('
                    pc.id,
                    pc.product_name,
                    pc.dropship_price,
                    pc.dropship_tempo,
                    ni4.code_lv4
                    ')
            ->from('product_costing pc')
            ->join('new_inventory_4 ni4', 'ni4.code_lv4 = pc.code_lv4', 'left')
            ->where('pc.status', 'A')
            ->where('ni4.deleted_date', null)
            ->where('ni4.deleted_by', null)
            ->get()
            ->result_array();

        // Buat mapping dropship berdasarkan product_name
        $dropshipMap = [];
        foreach ($costing as $item) {
            $dropshipMap[$item['product_name']] = [
                'dropship_price' => $item['dropship_price'],
                'dropship_tempo' => $item['dropship_tempo']
            ];
        }

        // Kelompokkan berdasarkan produk + tambah dropship
        $groupedData = [];
        foreach ($rows as $row) {
            $product = $row['product_name'];
            $toko = $row['toko'];

            // Inisialisasi array produk jika belum ada
            if (!isset($groupedData[$product])) {
                $groupedData[$product] = [];
            }

            $groupedData[$product][$toko] = [
                'cash' => $row['cash'],
                'tempo' => $row['tempo']
            ];

            // Tambahkan dropship jika tersedia
            $groupedData[$product]['dropship_price'] = $dropshipMap[$product]['dropship_price'];
            $groupedData[$product]['dropship_tempo'] = $dropshipMap[$product]['dropship_tempo'];
        }

        $this->template->render('kalkulasi_price_list', [
            'tokoList' => $tokoList,
            'groupedData' => $groupedData,
            'kategoriList' => $kategoriList
        ]);
    }


    public function master_persentase()
    {
        $data['persentase'] = $this->db->order_by('urutan', 'asc')->get('master_persentase')->result_array();
        $this->template->render('master_persentase', $data);
    }

    public function save_persentase()
    {
        $data = $this->input->post('data');

        // Bersihkan semua dulu
        $this->db->truncate('master_persentase');

        foreach ($data as $item) {
            if (!isset($item['nama']) || trim($item['nama']) === '') continue;

            $this->db->insert('master_persentase', [
                'nama' => $item['nama'],
                'urutan' => $item['urutan'],
                'cash' => $item['cash'],
                'tempo' => $item['tempo']
            ]);
        }

        $this->generate_price_list();

        echo json_encode([
            'status' => 1,
            'message' => 'Data berhasil diperbarui.'
        ]);
    }


    public function data_side_product_costing()
    {
        $this->product_costing_model->get_json_product_costing();
    }
}
