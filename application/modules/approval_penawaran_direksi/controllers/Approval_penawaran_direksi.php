<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Approval_penawaran_direksi extends Admin_Controller
{
    //Permission
    protected $viewPermission       = 'Approval_Penawaran_Direksi.Add';
    protected $addPermission        = 'Approval_Penawaran_Direksi.Manage';
    protected $managePermission     = 'Approval_Penawaran_Direksi.View';
    protected $deletePermission     = 'Approval_Penawaran_Direksi.Delete';


    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Approval_penawaran_direksi/approval_penawaran_direksi_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    public function data_side_approval_direksi()
    {
        $this->approval_penawaran_direksi_model->get_json_approval_direksi();
    }

    public function index()
    {
        $this->template->page_icon('fa fa-check-square-o');
        $this->template->title('Approval Penawaran Direksi');
        $this->template->render('index');
    }

    public function approval($id_penawaran)
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
        $data['mode'] = 'approval_direksi';

        // View form edit
        $this->template->title("Approval Penawaran Direksi");
        $this->template->page_icon("fa fa-check-square-o");
        $this->template->render('form', $data);
    }

    public function save()
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

        $update = [
            'status' => 'A', // FINAL Approved
            'approved_by_direksi' => $this->auth->user_id(),
            'approved_at_direksi' => date('Y-m-d H:i:s')
        ];

        // Proses revisi data produk (penawaran_detail).
        // Direksi boleh tambah/hapus produk saat approval, jadi total header
        // wajib dihitung ulang dari detail agar header = jumlah detail.
        $product_data = [];
        $total_penawaran  = 0;
        $total_price_list = 0;

        if (isset($post['product']) && is_array($post['product'])) {
            foreach ($post['product'] as $pro) {
                if (empty($pro['id_product'])) {
                    continue; // baris kosong (produk belum dipilih) tidak disimpan
                }

                $qty             = (float) str_replace(',', '', $pro['qty']);
                $price_list      = (float) str_replace(',', '', $pro['price_list']);
                $harga_penawaran = (float) str_replace(',', '', $pro['harga_penawaran']);

                // Rumus sama dengan hitungTotal() di view
                $diskon       = ($harga_penawaran && $price_list) ? (($harga_penawaran - $price_list) / $price_list) * 100 : 0;
                $diskon_nilai = ($price_list * $diskon) / 100;
                $total        = $qty * $harga_penawaran;
                $total_pl     = $qty * $price_list;

                $total_penawaran  += $total;
                $total_price_list += $total_pl;

                $product_data[] = [
                    'id_penawaran'      => $id_penawaran,
                    'id_product'        => $pro['id_product'],
                    'product_name'      => $pro['product_name'],
                    'harga_beli'        => str_replace(',', '', $pro['harga_beli']),
                    'qty'               => $qty,
                    'price_list'        => $price_list,
                    'harga_penawaran'   => $harga_penawaran,
                    'diskon'            => round($diskon, 2),
                    'diskon_nilai'      => round($diskon_nilai, 2),
                    'total'             => $total,
                    'total_pl'          => $total_pl,
                ];
            }
        }

        if (empty($product_data)) {
            echo json_encode(['status' => 0, 'pesan' => 'Produk penawaran tidak boleh kosong']);
            return;
        }

        // Hitung ulang total header, rumus sama dengan hitungAllTotal() di view.
        // Freight & diskon khusus diambil dari data penawaran (tidak diubah saat approval).
        $diskon_khusus = (float) $penawaran['diskon_khusus'];
        if ($diskon_khusus > $total_penawaran) {
            $diskon_khusus = $total_penawaran; // cegah minus
        }
        $freight             = (float) $penawaran['freight'];
        $total_harga_freight = ($total_penawaran - $diskon_khusus) + $freight;
        $exclude_ppn         = $total_harga_freight / 1.11;
        $dpp                 = ($exclude_ppn * 11) / 12;
        $ppn                 = (12 * $dpp) / 100;

        $update['total_penawaran']           = $total_penawaran;
        $update['total_price_list']          = $total_price_list;
        $update['total_diskon_persen']       = $total_price_list ? round((($total_penawaran - $total_price_list) / $total_price_list) * 100, 2) : 0;
        $update['total_harga_freight']       = $total_harga_freight;
        $update['total_harga_freight_exppn'] = round($exclude_ppn, 2);
        $update['dpp']                       = round($dpp, 2);
        $update['ppn']                       = round($ppn, 2);
        $update['grand_total']               = round($exclude_ppn + $ppn, 2);

        // Hitung ulang status credit limit dari grand total baru,
        // rumus sama dengan updateCreditStatus() di view.
        $credit_limit = (float) $penawaran['credit_limit'];
        $selisih      = ($update['grand_total'] + (float) $penawaran['outstanding']) - $credit_limit;
        if ($credit_limit > 0 && $selisih > 0) {
            $update['over_limit']          = round($selisih, 2);
            $update['status_credit_limit'] = 'Overlimit';
        } else {
            $update['over_limit']          = 0;
            $update['status_credit_limit'] = 'Tidak Overlimit';
        }

        $this->db->trans_start();

        $this->db->where('id_penawaran', $id_penawaran);
        $this->db->update('penawaran', $update);

        $this->db->where('id_penawaran', $id_penawaran)->delete('penawaran_detail');
        $this->db->insert_batch('penawaran_detail', $product_data);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            echo json_encode(['status' => 0, 'pesan' => 'Gagal menyimpan approval penawaran']);
            return;
        }

        echo json_encode([
            'status' => 1,
            'pesan' => 'Approval direksi berhasil diproses.'
        ]);
    }

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
}
