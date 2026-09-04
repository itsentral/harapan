<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penawaran_dropship extends Admin_Controller
{
    //Permission
    protected $viewPermission       = 'Penawaran.View';
    protected $addPermission        = 'Penawaran.Add';
    protected $managePermission     = 'Penawaran.Manage';
    protected $deletePermission     = 'Penawaran.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Penawaran_dropship/penawaran_dropship_model',
            'Price_list/price_list_model',
            'Product_costing/product_costing_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->page_icon('fa fa-shopping-cart');
        $this->template->title('Penawaran Dropship');
        $this->template->render('index');
    }

    public function add()
    {
        $data['customers'] = $this->db
            ->where('deleted', 0)
            ->where('deleted_by', null)
            ->get('master_customers')
            ->result_array();

        $data['products'] = $this->db
            ->select('
                    pc.id,
                    pc.product_name,
                    pc.propose_price,
                    pc.harga_beli,
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

        $payment_terms = $this->db
            ->where('group_by', 'top invoice')
            ->where('sts', 'Y')
            ->order_by('id', 'asc')
            ->get('list_help')
            ->result_array();
        $data['payment_terms'] = $payment_terms;
        $data['mode'] = "add";

        $this->template->render('form', $data);
    }

    public function edit($id_penawaran)
    {
        // Cek apakah data ada
        $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $id_penawaran])->row_array();

        if (!$penawaran) {
            show_404(); // Jika tidak ada, tampilkan error 404
        }

        // Ambil data detail produk terkait
        $penawaran_detail = $this->db->get_where('penawaran_detail', ['id_penawaran' => $id_penawaran])->result_array();

        // Data customer dan produk (jika diperlukan untuk select)
        $data['customers'] = $this->db->get('master_customers')->result_array();
        $data['products'] = $this->db
            ->select('
                    pc.id,
                    pc.product_name,
                    pc.propose_price,
                    pc.harga_beli,
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

        $data['payment_terms'] = $this->db->where('group_by', 'top invoice')->where('sts', 'Y')->get('list_help')->result_array();

        // Kirim data ke view
        $data['penawaran'] = $penawaran;
        $data['penawaran_detail'] = $penawaran_detail;
        $data['mode'] = "edit";

        // View form edit
        $this->template->render('form', $data);
    }

    public function view($id_penawaran)
    {
        // Cek apakah data ada
        $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $id_penawaran])->row_array();

        if (!$penawaran) {
            show_404();
        }

        // Ambil data detail produk terkait
        $penawaran_detail = $this->db->get_where('penawaran_detail', ['id_penawaran' => $id_penawaran])->result_array();

        // Data customer dan produk
        $data['customers'] = $this->db
            ->where('deleted', 0)
            ->where('deleted_by', null)
            ->get('master_customers')
            ->result_array();

        $data['products'] = $this->db
            ->select('
                    pc.id,
                    pc.product_name,
                    pc.propose_price,
                    pc.harga_beli,
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

        $data['payment_terms'] = $this->db->where('group_by', 'top invoice')->where('sts', 'Y')->get('list_help')->result_array();

        // Kirim data ke view
        $data['penawaran'] = $penawaran;
        $data['penawaran_detail'] = $penawaran_detail;
        $data['mode'] = "view";

        // View form (read-only)
        $this->template->title('Detail Penawaran Dropship');
        $this->template->page_icon('fa fa-shopping-cart');
        $this->template->render('form', $data);
    }

    public function save()
    {
        $data = $this->input->post();

        $id = $data['id_penawaran'];

        $is_update = !empty($id);
        $id_penawaran = $is_update ? $id : $this->penawaran_dropship_model->generate_id();

        $header = [
            'id_penawaran'              => $id_penawaran,
            'id_customer'               => $data['id_customer'],
            // 'price_mode'                => $data['price_mode'],
            'sales'                     => $data['sales'],
            'email'                     => $data['email'],
            'payment_term'              => $data['payment_term'],
            'quotation_date'            => date('Y-m-d H:i:s', strtotime($data['quotation_date'])),
            'tipe_bayar'                => $data['tipe_bayar'],
            'freight'                   => str_replace(',', '', $data['freight']),
            'total_penawaran'           => str_replace(',', '', $data['total_penawaran']),
            'total_price_list'          => str_replace(',', '', $data['total_price_list']),
            'total_diskon_persen'       => $data['total_diskon_persen'],
            'total_harga_freight'       => str_replace(',', '', $data['total_harga_freight']),
            'total_harga_freight_exppn' => str_replace(',', '', $data['total_harga_freight_exppn']),
            'dpp'                       => str_replace(',', '', $data['dpp']),
            'ppn'                       => str_replace(',', '', $data['ppn']),
            'grand_total'               => str_replace(',', '', $data['grand_total']),
            'due_date_credit'           => date('Y-m-d H:i:s', strtotime($data['due_date_credit'])),
            'credit_limit'              => str_replace(',', '', $data['credit_limit']),
            'outstanding'               => str_replace(',', '', $data['outstanding']),
            'over_limit'                => str_replace(',', '', $data['over_limit']),
            'status_credit_limit'       => $data['status_credit_limit'],
            'tipe_penawaran'            => "Dropship",
        ];

        // Buat nentuin status dan level approval
        $level_approval = 'M';
        $status = 'WA';
        $surplus_only = true;

        if (isset($_POST['product']) && is_array($_POST['product'])) {
            foreach ($_POST['product'] as $pro) {
                $diskon = floatval($pro['diskon']);

                if ($diskon < -2) {
                    // Diskon minus terlalu besar, butuh approval direksi
                    $level_approval = 'D';
                    $status = 'WA';

                    $surplus_only = false;
                    break; // langsung berhenti
                }

                if ($diskon >= -2 && $diskon <= 0) {
                    // Masih dalam range toleransi → butuh approval manager
                    $surplus_only = false;
                }
            }

            // Kalau semua diskon > 0% (surplus semua), langsung approve
            if ($surplus_only) {
                $status = 'A'; // auto approve
            }
        }
        $header['level_approval'] = $level_approval;
        $header['status'] = $status;


        if ($is_update) {
            // Ambil revisi terakhir dari database
            $prev = $this->db->select('revisi')
                ->from('penawaran')
                ->where('id_penawaran', $id)
                ->get()
                ->row_array();

            $header['revisi'] = isset($prev['revisi']) ? $prev['revisi'] + 1 : 1;
            $header['modified_by'] = $this->auth->user_id();
            $header['modified_at'] = date('Y-m-d H:i:s');

            $header['approved_by_manager'] = null;
            $header['approved_at_manager'] = null;

            $header['approved_by_direksi'] = null;
            $header['approved_at_direksi'] = null;
        } else {
            $header['revisi'] = 0; // pertama kali dibuat
            $header['created_by'] = $this->auth->user_id();
            $header['created_at'] = date('Y-m-d H:i:s');
        }

        $this->db->trans_start();
        if ($is_update) {
            $this->db->where('id_penawaran', $id);
            $this->db->update('penawaran', $header);
            $id_penawaran = $id;
        } else {
            $this->db->insert('penawaran', $header);
            $id_penawaran = $header['id_penawaran']; // pakai ID yang baru dibuat
        }
        // Hapus dan simpan ulang product
        if ($is_update) {
            $this->db->delete('penawaran_detail', ['id_penawaran' => $id_penawaran]);
        }
        if (isset($_POST['product']) && is_array($_POST['product'])) {
            $product_data = [];
            foreach ($_POST['product'] as $pro) {
                $product_data[] = [
                    'id_penawaran'      => $id_penawaran,
                    'id_product'        => $pro['id_product'],
                    'product_name'      => $pro['product_name'],
                    'harga_beli'        => str_replace(',', '', $pro['harga_beli']),
                    'qty'               => $pro['qty'],
                    'price_list'        => str_replace(',', '', $pro['price_list']),
                    'harga_penawaran'   => str_replace(',', '', $pro['harga_penawaran']),
                    'diskon'            => $pro['diskon'],
                    'total'             => str_replace(',', '', $pro['total']),
                    'total_pl'          => str_replace(',', '', $pro['total_pl']),
                ];
            }

            if (!empty($product_data)) {
                $this->db->insert_batch('penawaran_detail', $product_data);
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

    public function index_loss()
    {
        $this->template->title('Loss Penawaran');
        $this->template->render('index_loss');
    }

    public function loss()
    {
        $post = $this->input->post();
        $id_penawaran = $post['id_penawaran'];

        // Cek apakah data penawaran tersedia
        $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $id_penawaran])->row_array();

        if (!$penawaran) {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Data penawaran tidak ditemukan.'
            ]);
            return;
        }

        // Siapkan data untuk update loss
        $update = [
            'deleted_by' => $this->auth->user_id(),
            'deleted_at' => date('Y-m-d H:i:s'),
            'status'     => 'L'
        ];

        // Lakukan update ke tabel penawaran
        $this->db->where('id_penawaran', $id_penawaran);
        $this->db->update('penawaran', $update);

        echo json_encode([
            'status' => 1,
            'pesan'  => 'Penawaran berhasil di-mark sebagai Loss.'
        ]);
    }

    public function request_approval()
    {
        $post = $this->input->post();
        $id_penawaran = $post['id_penawaran'];

        // Cek apakah data penawaran tersedia
        $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $id_penawaran])->row_array();

        if (!$penawaran) {
            echo json_encode([
                'status' => 0,
                'pesan'  => 'Data penawaran tidak ditemukan.'
            ]);
            return;
        }

        // Siapkan data untuk update loss
        $update = [
            'status_draft' => 1
        ];

        // Lakukan update ke tabel penawaran
        $this->db->where('id_penawaran', $id_penawaran);
        $this->db->update('penawaran', $update);

        echo json_encode([
            'status' => 1,
            'pesan'  => 'Penawaran berhasil direquest Approval.'
        ]);
    }

    // Bagian Print out
    public function print_penawaran($id_penawaran)
    {
        $this->template->page_icon('fa fa-list');

        // Ambil data penawaran utama dari tabel 'penawaran' + join 'master_customers'
        $get_penawaran = $this->db
            ->select('p.*, c.*, 
                    e1.nm_karyawan AS created_by,
                    e2.nm_karyawan AS approved_by_manager,
                    e3.nm_karyawan AS approved_by_direksi')
            ->from('penawaran p')
            ->join('master_customers c', 'p.id_customer = c.id_customer', 'left')
            ->join('employee e1', 'e1.id = p.created_by', 'left')
            ->join('employee e2', 'e2.id = p.approved_by_manager', 'left')
            ->join('employee e3', 'e3.id = p.approved_by_direksi', 'left')
            ->where('p.id_penawaran', $id_penawaran)
            ->get()
            ->row();

        // Ambil detail item penawaran (tabel penawaran_detail dan join terkait bisa disesuaikan)
        $get_penawaran_detail = $this->db->select('d.*')
            ->from('penawaran_detail d')
            ->where('d.id_penawaran', $id_penawaran)
            ->order_by('d.id', 'ASC')
            ->get()
            ->result();

        // Bangun data yang akan dikirim ke view
        $data = [
            'data_penawaran' => $get_penawaran,
            'data_penawaran_detail' => $get_penawaran_detail,
        ];

        // Kirim ke view
        $this->load->view('print_penawaran', ['results' => $data]);
    }

    // Bagian Approval 
    public function approval_manager()
    {
        $this->template->render('list_approval_manager');
    }

    public function approve_manager($id_penawaran)
    {
        $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $id_penawaran])->row_array();

        if (!$penawaran) {
            show_404();
        }

        $penawaran_detail = $this->db->get_where('penawaran_detail', ['id_penawaran' => $id_penawaran])->result_array();

        $data['customers'] = $this->db->get('master_customers')->result_array();
        $data['products'] = $this->db->get('product_costing')->result_array();
        $data['payment_terms'] = $this->db->where('group_by', 'top invoice')->where('sts', 'Y')->get('list_help')->result_array();

        // Kirim data ke view
        $data['penawaran'] = $penawaran;
        $data['penawaran_detail'] = $penawaran_detail;
        $data['mode'] = 'approval_manager';

        // View form edit
        $this->template->render('form', $data);
    }

    public function save_approval_manager()
    {
        $post = $this->input->post();
        $id_penawaran = $post['id_penawaran'];

        $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $id_penawaran])->row_array();

        if (!$penawaran) {
            echo json_encode(['status' => 0, 'pesan' => 'Data penawaran tidak ditemukan']);
            return;
        }

        // Siapkan data header update
        $update = [
            'approved_by_manager' => $this->auth->user_id(),
            'approved_at_manager' => date('Y-m-d H:i:s')
        ];


        // Cek apakah level approval butuh direksi
        if ($penawaran['level_approval'] == 'D') {
            $update['status'] = 'WA'; // Tunggu approval Direksi
        } else {
            $update['status'] = 'A'; // Final approval dari Manager
        }

        // Simpan update ke penawaran
        $this->db->where('id_penawaran', $id_penawaran);
        $this->db->update('penawaran', $update);

        // Proses revisi data produk (penawaran_detail)
        if (isset($post['product']) && is_array($post['product'])) {
            $product_data = [];

            foreach ($post['product'] as $pro) {
                $product_data[] = [
                    'id_penawaran'      => $id_penawaran,
                    'id_product'        => $pro['id_product'],
                    'product_name'      => $pro['product_name'],
                    'harga_beli'        => str_replace(',', '', $pro['harga_beli']),
                    'qty'               => $pro['qty'],
                    'price_list'        => str_replace(',', '', $pro['price_list']),
                    'harga_penawaran'   => str_replace(',', '', $pro['harga_penawaran']),
                    'diskon'            => $pro['diskon'],
                    'total'             => str_replace(',', '', $pro['total']),
                    'total_pl'          => str_replace(',', '', $pro['total_pl']),
                ];
            }

            if (!empty($product_data)) {
                $this->db->where('id_penawaran', $id_penawaran)->delete('penawaran_detail');

                $this->db->insert_batch('penawaran_detail', $product_data);
            }
        }

        echo json_encode([
            'status' => 1,
            'pesan' => 'Penawaran berhasil diapprove oleh Manager.'
        ]);
    }

    public function approval_direksi()
    {
        $this->template->render('list_approval_direksi');
    }

    public function approve_direksi($id_penawaran)
    {
        // Cek apakah data ada
        $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $id_penawaran])->row_array();

        if (!$penawaran) {
            show_404(); // Jika tidak ada, tampilkan error 404
        }

        // Ambil data detail produk terkait
        $penawaran_detail = $this->db->get_where('penawaran_detail', ['id_penawaran' => $id_penawaran])->result_array();

        // Data customer dan produk (jika diperlukan untuk select)
        $data['customers'] = $this->db->get('master_customers')->result_array();
        $data['products'] = $this->db->get('product_costing')->result_array();
        $data['payment_terms'] = $this->db->where('group_by', 'top invoice')->where('sts', 'Y')->get('list_help')->result_array();

        // Kirim data ke view
        $data['penawaran'] = $penawaran;
        $data['penawaran_detail'] = $penawaran_detail;
        $data['mode'] = 'approval_direksi';

        // View form edit
        $this->template->render('form', $data);
    }

    public function save_approval_direksi()
    {
        $post = $this->input->post();
        $id_penawaran = $post['id_penawaran'];

        if (empty($id_penawaran)) {
            echo json_encode(['status' => 0, 'pesan' => 'ID penawaran tidak ditemukan']);
            return;
        }

        $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $id_penawaran])->row_array();

        if (!$penawaran) {
            echo json_encode(['status' => 0, 'pesan' => 'Data penawaran tidak ditemukan']);
            return;
        }

        $this->db->where('id_penawaran', $id_penawaran);
        $this->db->update('penawaran', [
            'status' => 'A', // FINAL Approved
            'approved_by_direksi' => $this->auth->user_id(),
            'approved_at_direksi' => date('Y-m-d H:i:s')
        ]);

        echo json_encode([
            'status' => 1,
            'pesan' => 'Approval direksi berhasil diproses.'
        ]);
    }

    // reject 
    public function reject($id = null)
    {
        if (!$id) {
            echo json_encode(['save' => 0, 'message' => 'ID tidak ditemukan']);
            return;
        }

        $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $id])->row();
        if (!$penawaran) {
            echo json_encode(['save' => 0, 'message' => 'Data tidak ditemukan']);
            return;
        }

        $reason = $this->input->post('reason');
        if (!$reason) {
            echo json_encode(['save' => 0, 'message' => 'Alasan harus diisi']);
            return;
        }

        $data = [
            'status' => "R",
            'status_draft' => 0,
            'reject_reason' => $reason,
            'modified_by' => $this->auth->user_id(),
            'modified_at' => date('Y-m-d H:i:s')
        ];

        if ($penawaran->level_approval == "D" && $penawaran->approved_by_manager !== null) {
            $data['status'] = "WA";
            $data['approved_by_manager'] = null;
            $data['approved_at_manager'] = null;
        }

        $this->db->where('id_penawaran', $id);
        $update = $this->db->update('penawaran', $data);

        if ($update) {
            echo json_encode(['save' => 1]);
        } else {
            echo json_encode(['save' => 0, 'message' => 'Gagal menyimpan alasan penolakan']);
        }
    }

    // FUNGSI BUAT AJAX SERVERSIDE
    public function pilih_harga_ajax()
    {
        $kategori_toko = $this->input->post('kategori_toko');
        $tipe_bayar = $this->input->post('tipe_bayar');
        $id_product = $this->input->post('id_product');

        // Ambil dari tabel kalkulasi
        $row = $this->db->get_where('master_kalkulasi_price_list', [
            'id_product' => $id_product,
            'toko' => $kategori_toko
        ])->row_array();

        if (!$row) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['error' => true, 'message' => 'Harga tidak ditemukan untuk toko yang dipilih.']));
        }

        $harga = ($tipe_bayar === 'cash') ? $row['cash'] : $row['tempo'];

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'error' => false,
                'harga' => intval($harga)
            ]));
    }

    public function get_nama_sales()
    {
        $id_karyawan = $this->input->post('id_karyawan');

        $karyawan = $this->db->get_where('employee', ['id' => $id_karyawan])->row_array();

        if ($karyawan) {
            echo json_encode([
                'error' => false,
                'nama_sales' => ucfirst($karyawan['nm_karyawan'])
            ]);
        } else {
            echo json_encode([
                'error' => true,
                'message' => 'Sales tidak ditemukan'
            ]);
        }
    }

    public function get_free_stok()
    {
        $code_lv4 = $this->input->post('code_lv4');

        $stock = $this->db->get_where('warehouse_stock', ['code_lv4' => $code_lv4])->row_array();

        if ($stock) {
            echo json_encode([
                'error' => false,
                'qty_free' => number_format($stock['qty_free'])
            ]);
        } else {
            echo json_encode([
                'error' => true,
                'message' => 'Free Stok tidak ditemukan'
            ]);
        }
    }

    public function get_credit_limit()
    {
        $id_customer = $this->input->post('id_customer', true);

        if (empty($id_customer)) {
            echo json_encode(['error' => true, 'message' => 'id_customer kosong']);
            return;
        }

        // Ambil kredit limit: join child_customer_rate (alias r) ke kelas (alias k)
        // Ambil 1 baris terbaru jika ada duplikasi
        $row = $this->db->select('k.kredit_limit')
            ->from('child_customer_rate r')
            ->join('kelas k', 'k.kelas = r.kelas')   // pastikan casing nilai 'kelas' sama
            ->where('r.id_customer', $id_customer)
            ->order_by('r.id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$row) {
            // fallback jika belum ada kelas atau tidak match
            echo json_encode(['error' => false, 'kredit_limit' => 0, 'kredit_limit_formatted' => number_format(0, 0, ',', '.')]);
            return;
        }

        $limit = (float)$row['kredit_limit'];
        echo json_encode([
            'error' => false,
            'kredit_limit' => $limit,                               // angka mentah
            'kredit_limit_formatted' => number_format($limit, 0, ',', '.') // string rupiah
        ]);
    }

    public function get_info_kredit_limit()
    {
        $id_customer = $this->input->post('id_customer');

        if (empty($id_customer)) {
            echo json_encode(['status' => 0, 'msg' => 'id_customer kosong']);
            return;
        }

        // A) Outstanding Piutang
        $row_piutang = $this->db
            ->select('SUM(i.piutang) AS outstanding_piutang', false)
            ->from('tr_invoice_sales i')
            ->where('i.id_customer', $id_customer)
            ->where('i.piutang <>', 0)
            ->get()
            ->row_array();

        $outstanding = (!empty($row_piutang['outstanding_piutang'])) ? (float)$row_piutang['outstanding_piutang'] : 0;

        // B) SO Baru (status A)
        $row_so = $this->db
            ->select('SUM(s.grand_total) AS so_baru', false)
            ->from('sales_order s')
            ->where('s.id_customer', $id_customer)
            ->where('s.status', 'A')
            ->where("(s.status_spk IS NULL OR s.status_spk != 'Belum SPK')", null, false)
            ->get()
            ->row_array();

        $so_baru = (!empty($row_so['so_baru'])) ? (float)$row_so['so_baru'] : 0;

        // C) Kredit Limit terakhir
        $row_limit = $this->db
            ->select('k.kredit_limit', false)
            ->from('child_customer_rate r')
            ->join('kelas k', 'k.kelas = r.kelas', 'inner')
            ->where('r.id_customer', $id_customer)
            ->order_by('r.id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        $kredit_limit = (!empty($row_limit['kredit_limit'])) ? (float)$row_limit['kredit_limit'] : 0;

        // D) Total
        $total = $outstanding + $so_baru;

        echo json_encode([
            'status' => 1,
            'msg' => 'OK',
            'data' => [
                'outstanding_piutang' => $outstanding,
                'so_baru'             => $so_baru,
                'total'               => $total,
                'kredit_limit'        => $kredit_limit,
            ]
        ]);
    }


    public function get_histori_pembayaran()
    {
        $id_customer = $this->input->post('id_customer');

        if (empty($id_customer)) {
            echo json_encode(['status' => 0, 'msg' => 'id_customer kosong', 'data' => []]);
            return;
        }

        $sql = "
                SELECT
                i.id_invoice AS no_invoice,
                DATE_FORMAT(i.jatuh_tempo, '%d/%m/%Y') AS due_date,
                DATE_FORMAT(MAX(p.tgl_pembayaran), '%d/%m/%Y') AS tanggal_pelunasan,
                DATEDIFF(MAX(p.tgl_pembayaran), i.jatuh_tempo) AS gap_days
                FROM tr_invoice_sales i
                JOIN tr_invoice_payment_detail pd ON pd.no_invoice = i.id_invoice
                JOIN tr_invoice_payment p ON p.kd_pembayaran = pd.kd_pembayaran
                WHERE
                i.id_customer = ?
                AND pd.sisa_invoice_idr = 0
                GROUP BY
                i.id_invoice, i.jatuh_tempo
                ORDER BY
                MAX(p.tgl_pembayaran) DESC
                LIMIT 5
            ";

        $rows = $this->db->query($sql, [$id_customer])->result_array();

        echo json_encode([
            'status' => 1,
            'msg' => 'OK',
            'data' => $rows
        ]);
    }

    public function get_jatuh_tempo()
    {
        $id_customer = $this->input->post('id_customer');

        if (empty($id_customer)) {
            echo json_encode(['status' => 0, 'msg' => 'id_customer kosong', 'data' => []]);
            return;
        }

        $sql = "
        SELECT
          i.id_invoice,
          i.id_so,
          i.id_penawaran,
          i.id_billing,
          i.grand_total,
          i.total_bayar,
          i.piutang,
          DATE_FORMAT(i.jatuh_tempo, '%d/%m/%Y') AS jatuh_tempo,
          i.id_customer
        FROM tr_invoice_sales i
        WHERE i.id_customer = ?
          AND i.piutang != 0
        ORDER BY i.jatuh_tempo ASC
        LIMIT 3
    ";

        $rows = $this->db->query($sql, [$id_customer])->result_array();

        echo json_encode([
            'status' => 1,
            'msg' => 'OK',
            'data' => $rows
        ]);
    }

    public function data_side_penawaran()
    {
        $this->penawaran_dropship_model->get_json_penawaran();
    }

    public function data_side_approval_manager()
    {
        $this->penawaran_dropship_model->get_json_approval_manager();
    }

    public function data_side_approval_direksi()
    {
        $this->penawaran_dropship_model->get_json_approval_direksi();
    }

    public function data_side_loss_penawaran()
    {
        $this->penawaran_dropship_model->get_json_loss_penawaran();
    }

    // BUAT TEST TEST
    public function getHargaPenawaran($id_product, $nama_toko, $tipe_bayar)
    {
        // Ambil produk
        $product = $this->db->get_where('product_costing', ['id' => $id_product])->row_array();

        if (!$product) {
            return [
                'error' => true,
                'message' => 'Produk tidak ditemukan.'
            ];
        }

        $harga_awal = $product['propose_price'];

        // Ambil daftar toko & urutkan berdasarkan urutan kalkulasi
        $tokoList = $this->db->order_by('urutan', 'asc')->get('master_persentase')->result_array();

        if (empty($tokoList)) {
            return [
                'error' => true,
                'message' => 'Data persentase toko kosong.'
            ];
        }

        // Inisialisasi
        $current_cash = $harga_awal;
        $current_tempo = 0;
        $harga_terpilih = null;

        // Hitung harga berantai dan ambil yang cocok
        foreach ($tokoList as $index => $toko) {
            $cash_percent = floatval($toko['cash']) / 100;
            $tempo_percent = floatval($toko['tempo']) / 100;

            // Jika bukan toko pertama, cash dihitung dari tempo sebelumnya
            if ($index > 0) {
                $current_cash = $current_tempo + ($current_tempo * $cash_percent);
            }

            $current_tempo = $current_cash + ($current_cash * $tempo_percent);

            if (strtolower($toko['nama']) === strtolower($nama_toko)) {
                $harga_terpilih = ($tipe_bayar === 'cash') ? $current_cash : $current_tempo;
                break;
            }
        }

        if ($harga_terpilih === null) {
            return [
                'error' => true,
                'message' => 'Toko tidak ditemukan dalam skema kalkulasi.'
            ];
        }

        return [
            'error' => false,
            'id_product' => $id_product,
            'nama_produk' => $product['product_name'],
            'toko' => $nama_toko,
            'tipe_bayar' => $tipe_bayar,
            'harga' => $harga_terpilih,
        ];
    }

    public function test()
    {
        // $data = $this->getHargaPenawaran('PC2500001', 'Toko 2', 'tempo');
        $data = $this->getHargaSemuaToko('PC2500001');

        echo '<pre>';
        print_r($data);
        echo '</pre>';
        die();
    }

    public function getHargaSemuaToko($id_product)
    {
        // Ambil produk
        $product = $this->db->get_where('product_costing', ['id' => $id_product])->row_array();

        if (!$product) {
            return [
                'error' => true,
                'message' => 'Produk tidak ditemukan.'
            ];
        }

        $harga_awal = $product['propose_price'];

        // Ambil data toko
        $tokoList = $this->db->order_by('urutan', 'asc')->get('master_persentase')->result_array();

        if (empty($tokoList)) {
            return [
                'error' => true,
                'message' => 'Data master persentase kosong.'
            ];
        }

        // Inisialisasi
        $current_cash = $harga_awal;
        $current_tempo = 0;
        $result = [];

        foreach ($tokoList as $index => $toko) {
            $cash_percent = floatval($toko['cash']) / 100;
            $tempo_percent = floatval($toko['tempo']) / 100;

            if ($index > 0) {
                $current_cash = $current_tempo + ($current_tempo * $cash_percent);
            }

            $current_tempo = $current_cash + ($current_cash * $tempo_percent);

            $result[] = [
                'toko' => $toko['nama'],
                'cash' => intval($current_cash),
                'tempo' => intval($current_tempo),
            ];
        }

        return [
            'error' => false,
            'produk' => $product['product_name'],
            'harga' => $result
        ];
    }

    public function export_excel()
    {
        $start = $this->input->get('start_date', true);
        $end   = $this->input->get('end_date', true);

        $id_karyawan_login = (int) $this->auth->user_id();
        $is_admin = in_array($id_karyawan_login, [7, 94, 95], true);

        $this->db->select('p.id_penawaran, p.quotation_date, p.total_penawaran, p.status, p.revisi, c.name_customer, p.sales');
        $this->db->from('penawaran p');
        $this->db->join('master_customers c', 'p.id_customer = c.id_customer', 'left');
        $this->db->join('users u', 'c.id_karyawan = u.employee_id', 'left');
        $this->db->where('p.status !=', 'L');
        $this->db->where('p.tipe_penawaran', 'Dropship');

        if (!$is_admin) {
            $this->db->where('u.id_user', $id_karyawan_login);
        }
        if (!empty($start)) $this->db->where('p.quotation_date >=', $start);
        if (!empty($end))   $this->db->where('p.quotation_date <=', $end);
        $this->db->order_by('p.quotation_date', 'DESC');

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
        $sheet->setCellValue('A1', 'REPORT PENAWARAN DROPSHIP - ' . $periode);
        $sheet->mergeCells('A1:G2');

        $headers = ['A' => '#', 'B' => 'No. Penawaran', 'C' => 'Tanggal Penawaran', 'D' => 'Customer', 'E' => 'Sales', 'F' => 'Nilai Penawaran', 'G' => 'Status'];
        $rowHeader = 4;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $statusMap = ['WA' => 'Waiting Approval', 'A' => 'Approved', 'R' => 'Rejected', 'L' => 'Loss'];
        $r = $rowHeader + 1;
        $no = 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValueExplicit('B' . $r, (string)$row->id_penawaran, PHPExcel_Cell_DataType::TYPE_STRING);
            if (!empty($row->quotation_date)) {
                // Ambil komponen tanggal langsung dari string (hindari konversi lewat strtotime()
                // + PHPToExcel() yang memaksa timezone UTC, karena itu bisa membuat tanggal
                // mundur 1 hari saat timezone aplikasi (Asia/Bangkok, UTC+7) berbeda dari UTC).
                $dateOnly = substr($row->quotation_date, 0, 10); // 'Y-m-d'
                list($y, $m, $d) = array_map('intval', explode('-', $dateOnly));
                $tgl = (float)PHPExcel_Shared_Date::FormattedPHPToExcel($y, $m, $d);
                $sheet->setCellValueExplicit('C' . $r, $tgl, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle('C' . $r)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            }
            $sheet->setCellValueExplicit('D' . $r, (string)$row->name_customer, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $r, (string)$row->sales, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $r, (float)$row->total_penawaran, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValueExplicit('G' . $r, isset($statusMap[$row->status]) ? $statusMap[$row->status] : $row->status, PHPExcel_Cell_DataType::TYPE_STRING);
            $r++;
        }

        $sheet->setTitle('Penawaran Dropship');
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Penawaran_Dropship_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
