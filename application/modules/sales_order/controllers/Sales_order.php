<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 *
 */
class Sales_order extends Admin_Controller
{
  //Permission
  protected $viewPermission   = 'Sales_Order.View';
  protected $addPermission    = 'Sales_Order.Add';
  protected $managePermission = 'Sales_Order.Manage';
  protected $deletePermission = 'Sales_Order.Delete';

  public function __construct()
  {
    parent::__construct();

    // $this->load->library(array( 'upload', 'Image_lib'));
    $this->load->model(array(
      'Sales_order/Sales_order_model',
    ));
    date_default_timezone_set('Asia/Bangkok');
  }

  public function index()
  {
    $this->auth->restrict($this->viewPermission);
    $session = $this->session->userdata('app_session');

    $this->template->page_icon('fa fa-shopping-cart');
    $this->template->render('index');
  }

  public function add($id_penawaran)
  {
    $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $id_penawaran])->row_array();
    if (!$penawaran) {
      show_404();
    }
    $penawaran_detail = $this->db->get_where('penawaran_detail', ['id_penawaran' => $id_penawaran])->result_array();

    $products = $this->db
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

    // Kirim data ke view
    $data = [
      'penawaran'         => $penawaran,
      'penawaran_detail'  => $penawaran_detail,
      'customers'         => $this->db->get('master_customers')->result_array(),
      'products'          => $products,
      'payment_terms'     => $this->db->where('group_by', 'top invoice')->where('sts', 'Y')->get('list_help')->result_array(),
      'mode'              => 'add',
    ];

    $this->template->title('Create Sales Order');
    $this->template->page_icon('fa fa-shopping-cart');
    $this->template->render('form', $data);
  }

  public function cancel()
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
      'approved_at_manager'   => null,
      'approved_by_manager'   => null,
      'status'                => 'WA',
      'status_draft'          => 0
    ];

    // Lakukan update ke tabel penawaran
    $this->db->where('id_penawaran', $id_penawaran);
    $this->db->update('penawaran', $update);

    echo json_encode([
      'status' => 1,
      'pesan'  => 'Sales Order dibatalakan.'
    ]);
  }

  /**
   * Cancel SO - Membatalkan sisa qty SO yang belum ter-SPK
   * 
   * Contoh: SO = 100, SPK = 70, Sisa 30 di-cancel.
   * Stock booking akan dikembalikan, muncul transaksi "Batal SO" di kartu stok.
   */
  public function cancel_so()
  {
    $this->auth->restrict($this->managePermission);

    $no_so  = $this->input->post('no_so');
    $reason = $this->input->post('reason');

    if (empty($no_so)) {
      echo json_encode(['status' => 0, 'pesan' => 'No SO tidak valid.']);
      return;
    }

    $so = $this->db->get_where('sales_order', ['no_so' => $no_so])->row_array();
    if (!$so) {
      echo json_encode(['status' => 0, 'pesan' => 'Data Sales Order tidak ditemukan.']);
      return;
    }

    $so_details = $this->db->get_where('sales_order_detail', ['no_so' => $no_so])->result_array();

    $this->db->trans_begin();
    $total_cancelled = 0;

    foreach ($so_details as $det) {
      $qty_order = floatval($det['qty_order']);
      $qty_spk   = floatval($det['qty_spk']);
      $sisa      = $qty_order - $qty_spk;

      if ($sisa <= 0) continue;

      $total_cancelled += $sisa;
      $code_lv4 = $det['id_product'];

      // Update sales_order_detail
      $this->db->update('sales_order_detail', [
        'qty_order'       => $qty_spk,
        'qty_belum_spk'   => 0,
        'qty_cancelled'   => $sisa,
        'status_planning' => 1,
      ], ['id' => $det['id']]);

      // Kembalikan booking warehouse
      $stok_before = $this->db->get_where('warehouse_stock', ['code_lv4' => $code_lv4, 'id_gudang' => 1, 'kd_gudang' => 'PUS'])->row_array();
      if ($stok_before) {
        $qty_booking_before  = floatval($stok_before['qty_booking']);
        $qty_free_before     = floatval($stok_before['qty_free']);
        $use_qty_free_before = floatval($stok_before['use_qty_free']);

        $qty_booking_after = max(0, $qty_booking_before - $sisa);
        $qty_free_after    = $qty_free_before + $sisa;

        $this->db->where(['code_lv4' => $code_lv4, 'id_gudang' => 1, 'kd_gudang' => 'PUS'])->update('warehouse_stock', [
          'qty_booking'  => $qty_booking_after,
          'qty_free'     => $qty_free_after,
          'use_qty_free' => max(0, $use_qty_free_before - $sisa),
        ]);

        // Catat di kartu_stok
        $this->db->insert('kartu_stok', [
          'no_transaksi'   => $no_so,
          'transaksi'      => 'Batal SO',
          'tgl_transaksi'  => date('Y-m-d H:i:s'),
          'code_lv4'       => $code_lv4,
          'nm_product'     => $det['product'],
          'qty'            => floatval($stok_before['qty_stock']),
          'qty_book'       => $qty_booking_before,
          'qty_free'       => $qty_free_before,
          'qty_transaksi'  => $sisa,
          'qty_akhir'      => floatval($stok_before['qty_stock']),
          'qty_book_akhir' => $qty_booking_after,
          'qty_free_akhir' => $qty_free_after,
          'harga_stok'     => isset($det['harga_beli']) ? floatval($det['harga_beli']) : 0,
        ]);
      }
    }

    // Update header SO
    $this->db->update('sales_order', [
      'status_so'      => 'CLOSED',
      'cancel_reason'  => $reason,
      'cancel_qty'     => $total_cancelled,
      'cancelled_by'   => $this->auth->user_id(),
      'cancelled_at'   => date('Y-m-d H:i:s'),
    ], ['no_so' => $no_so]);

    if ($this->db->trans_status() === FALSE) {
      $this->db->trans_rollback();
      echo json_encode(['status' => 0, 'pesan' => 'Gagal membatalkan sisa SO!']);
      return;
    }

    $this->db->trans_commit();
    history("Cancel SO (sisa): {$no_so} | Qty dibatalkan: {$total_cancelled}");

    echo json_encode([
      'status' => 1,
      'pesan'  => "Sisa SO berhasil dibatalkan. Total qty cancel: {$total_cancelled}. Stock booking dikembalikan."
    ]);
  }

  public function edit($id_so)
  {
    $so = $this->db->get_where('sales_order', ['no_so' => $id_so])->row_array();
    $so_detail = $this->db->get_where('sales_order_detail', ['no_so' => $id_so])->result_array();
    $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $so['id_penawaran']])->row_array();
    // $penawaran_detail = $this->db->get_where('penawaran_detail', ['id_penawaran' => $so['id_penawaran']])->result_array();

    $products = $this->db
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


    // Kirim data ke view
    $data = [
      'so'                => $so,
      'so_detail'         => $so_detail,
      'penawaran'         => $penawaran,
      // 'penawaran_detail'  => $penawaran_detail,
      'customers'         => $this->db->get('master_customers')->result_array(),
      'products'          => $products,
      'payment_terms'     => $this->db->where('group_by', 'top invoice')->where('sts', 'Y')->get('list_help')->result_array(),
      'mode'              => 'edit',
    ];

    $this->template->title('Edit Sales Order');
    $this->template->page_icon('fa fa-shopping-cart');
    $this->template->render('form', $data);
  }

  public function deal($id_so)
  {
    $so = $this->db->get_where('sales_order', ['no_so' => $id_so])->row_array();
    $so_detail = $this->db->get_where('sales_order_detail', ['no_so' => $id_so])->result_array();
    $penawaran = $this->db->get_where('penawaran', ['id_penawaran' => $so['id_penawaran']])->row_array();
    // $penawaran_detail = $this->db->get_where('penawaran_detail', ['id_penawaran' => $so['id_penawaran']])->result_array();

    // Kirim data ke view
    $data = [
      'so'                => $so,
      'so_detail'         => $so_detail,
      'penawaran'         => $penawaran,
      // 'penawaran_detail'  => $penawaran_detail,
      'customers'         => $this->db->get('master_customers')->result_array(),
      'products'          => $this->db->get('product_costing')->result_array(),
      'payment_terms'     => $this->db->where('group_by', 'top invoice')->where('sts', 'Y')->get('list_help')->result_array(),
      'mode'              => 'deal',
    ];

    $this->template->render('form', $data);
  }

  public function save()
  {
    $data = $this->input->post();

    $id = $data['no_so'];
    $is_update = !empty($id);
    $no_so = $is_update ? $id : $this->Sales_order_model->generate_id();

    $header = [
      'no_so'                       => $no_so,
      'id_penawaran'                => $data['id_penawaran'],
      'id_customer'                 => $data['id_customer'],
      'nama_sales'                  => $data['sales'],
      'email_customer'              => $data['email'],
      'payment_term'                => $data['payment_term'],
      'freight'                     => str_replace(',', '', $data['freight']),
      'tgl_so'                      => date('Y-m-d H:i:s', strtotime($data['tgl_so'])),
      'nilai_so'                    => str_replace(',', '', $data['total_penawaran']),
      'total_diskon_persen'         => $data['total_diskon_persen'],
      'diskon_khusus'               => str_replace(',', '', $data['diskon_khusus']),
      'total_harga_freight'         => str_replace(',', '', $data['total_harga_freight']),
      'total_harga_freight_exppn'   => str_replace(',', '', $data['total_harga_freight_exppn']),
      'dpp'                         => str_replace(',', '', $data['dpp']),
      'ppn'                         => str_replace(',', '', $data['ppn']),
      'grand_total'                 => str_replace(',', '', $data['grand_total']),
      'status'                      => 'A', // langsung set A (Approved/Deal)
      'approved_by'                 => $this->auth->user_id(),
      'approved_at'                 => date('Y-m-d H:i:s'),
    ];

    if ($is_update) {
      $header['modified_by'] = $this->auth->user_id();
      $header['modified_at'] = date('Y-m-d H:i:s');
    } else {
      $header['created_by'] = $this->auth->user_id();
      $header['created_at'] = date('Y-m-d H:i:s');
    }

    $this->db->trans_start();

    if ($is_update) {
      $this->db->where('no_so', $id);
      $this->db->update('sales_order', $header);
      $no_so = $id;
    } else {
      $this->db->insert('sales_order', $header);
      $no_so = $header['no_so'];
    }

    // Hapus dan simpan ulang product
    // Saat UPDATE: kembalikan dulu booking lama ke warehouse_stock sebelum dihapus
    if ($is_update) {
      $old_details = $this->db->get_where('sales_order_detail', ['no_so' => $no_so])->result_array();
      foreach ($old_details as $old) {
        $old_code = $old['id_product'];
        $old_use_qty_free = floatval($old['use_qty_free']);
        $old_qty_order    = floatval($old['qty_order']);
        // Kembalikan booking dan free ke kondisi sebelum SO ini
        $this->db->query("
          UPDATE warehouse_stock
          SET qty_booking  = GREATEST(qty_booking - ?, 0),
              use_qty_free = GREATEST(use_qty_free - ?, 0),
              qty_free     = qty_free + ?
          WHERE code_lv4 = ? AND id_gudang = 1 AND kd_gudang = 'PUS'
        ", [$old_qty_order, $old_use_qty_free, $old_use_qty_free, $old_code]);
      }
      $this->db->delete('sales_order_detail', ['no_so' => $no_so]);
    }

    $arr_kartu_stok = [];

    if (isset($_POST['product']) && is_array($_POST['product'])) {
      $detail = [];
      foreach ($_POST['product'] as $pro) {
        $code_lv4 = $pro['code_lv4'];
        // Baca stok SETELAH rollback (untuk update) atau stok saat ini (untuk insert)
        $stok = $this->db->get_where('warehouse_stock', ['code_lv4' => $code_lv4, 'id_gudang' => 1, 'kd_gudang' => 'PUS'])->row_array();

        if ($stok) {
          $qty_booking_lama  = floatval($stok['qty_booking']);
          $qty_free_lama     = floatval($stok['qty_free']);
          $use_qty_free_lama = floatval($stok['use_qty_free']);

          $use_qty_free_baru = floatval($pro['use_qty_free']);
          $qty_order_baru    = floatval($pro['qty']);

          // Booking selalu akumulasi dari kondisi saat ini (sudah di-rollback untuk update)
          $qty_booking_baru = $qty_booking_lama + $qty_order_baru;
          $qty_free_baru    = $qty_free_lama - $use_qty_free_baru;

          // update warehouse
          $this->db->where(['code_lv4' => $code_lv4, 'id_gudang' => 1, 'kd_gudang' => 'PUS'])->update('warehouse_stock', [
            'qty_booking'  => $qty_booking_baru,
            'use_qty_free' => $use_qty_free_lama + $use_qty_free_baru,
            'qty_free'     => $qty_free_baru
          ]);

          // insert sales_order_detail
          $detail[] = [
            'no_so'             => $no_so,
            'id_penawaran'      => $pro['id_penawaran'],
            'id_product'        => $pro['id_product'],
            'product'           => $pro['product_name'],
            'qty_order'         => $qty_order_baru,
            'qty_free'          => $qty_free_baru,
            'use_qty_free'      => $use_qty_free_baru,
            'qty_propose'       => $pro['pr'],
            'harga_beli'        => str_replace(',', '', $pro['harga_beli']),
            'price_list'        => str_replace(',', '', $pro['price_list']),
            'harga_penawaran'   => str_replace(',', '', $pro['harga_penawaran']),
            'diskon_persen'     => $pro['diskon'],
            'diskon_nilai'      => $pro['diskon_nilai'],
            'pengiriman'        => $pro['pengiriman'],
            'total_harga'       => str_replace(',', '', $pro['total']),
            'total_pl'          => str_replace(',', '', $pro['total_pl']),
            'created_by'        => $this->auth->user_id(),
            'created_at'        => date('Y-m-d H:i:s'),
          ];

          // insert kartu_stok
          // qty/qty_book/qty_free = kondisi SEBELUM transaksi ini
          // qty_transaksi = 0 karena stok fisik tidak berubah, hanya booking berubah
          $arr_kartu_stok[] = [
            'no_transaksi'      => $no_so,
            'transaksi'         => "Sales Order",
            'tgl_transaksi'     => $header['tgl_so'],
            'code_lv4'          => $code_lv4,
            'nm_product'        => $pro['product_name'],
            'qty'               => floatval($stok['qty_stock']),
            'qty_book'          => $qty_booking_lama,
            'qty_free'          => $qty_free_lama,
            'qty_transaksi'     => 0,
            'qty_akhir'         => floatval($stok['qty_stock']),
            'qty_book_akhir'    => $qty_booking_baru,
            'qty_free_akhir'    => $qty_free_baru,
            'harga_stok'        => $pro['harga_beli']
          ];
        }
      }

      if (!empty($detail)) {
        $this->db->insert_batch('sales_order_detail', $detail);
      }
      if (!empty($arr_kartu_stok)) {
        $this->db->insert_batch('kartu_stok', $arr_kartu_stok);
      }
    }

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE) {
      $this->db->trans_rollback();
      $status = [
        'status' => 0,
        'pesan' => 'Gagal Save. Try Again Later ...'
      ];
    } else {
      $this->db->trans_commit();
      $this->send_wa_so($no_so);
      $status = [
        'status' => 1,
        'pesan' => 'Sales Order berhasil disimpan dan langsung DEAL.',
        'no_so' => $no_so // ← penting!
      ];
    }

    echo json_encode($status);
  }

  //Send Wa
  private function send_wa_so($no_so)
  {
    $so = $this->db
      ->select('so.*, c.name_customer, c.telephone, c.telephone_2, c.address_office')
      ->from('sales_order so')
      ->join('master_customers c', 'so.id_customer = c.id_customer', 'left')
      ->where('so.no_so', $no_so)
      ->get()->row_array();

    $produk = $this->db
      ->select('product')
      ->from('sales_order_detail')
      ->where('no_so', $no_so)
      ->get()->result_array();

    $raw_phone = preg_replace('/[^0-9]/', '', $so['telephone']);
    if (substr($raw_phone, 0, 1) === '0') {
      $wa_number = '62' . substr($raw_phone, 1);
    } elseif (substr($raw_phone, 0, 2) === '62') {
      $wa_number = $raw_phone;
    } else {
      $wa_number = ltrim($raw_phone, '+');
    }

    $pesan = "Terimakasih kepada CV/TB {$so['name_customer']} yang telah melakukan pemesanan dengan Nomor Sales Order *{$so['no_so']}* melalui sales kami bapak/ibu *{$so['nama_sales']}*, dengan isi pesanan:\n\n";
    $no = 1;
    foreach ($produk as $p) {
      $pesan .= "{$no}. {$p['product']}\n";
      $no++;
    }
    $pesan .= "\nTotal nilai pesanan sebesar *Rp " . number_format($so['nilai_so'], 0, ',', '.') . "*\n";
    $pesan .= "Hubungi no pelayanan pelanggan kami di *+6282130728009* jika ada kesalahan.\n\nHormat kami,\n\n*PT Surya Bangun Fajar*";

    // Panggil API WA (silent)
    $this->send($wa_number, $pesan);
  }

  private function send($number, $message)
  {
    $url = 'https://app.whacenter.com/api/send';
    $data = [
      //'device_id' => 'ea118812b9454dc34a477ae1c053f0fc', //device yang lama
      'device_id' => 'c7c1b12f27cffcc9f49d92533a274c4d',
      'number' => $number,
      'message' => $message
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
  }


  // PRINTOUT
  public function print_so($no_so)
  {
    $this->template->page_icon('fa fa-list');

    // Ambil data sales order utama + penawaran + customer
    $get_so = $this->db
      ->select('so.*, c.*, p.quotation_date, p.total_penawaran, p.tipe_bayar,
                  e1.nm_karyawan AS created_by,
                  e2.nm_karyawan AS approved_by,
                  lh.name AS payment_term_name')
      ->from('sales_order so')
      ->join('penawaran p', 'p.id_penawaran = so.id_penawaran', 'left')
      ->join('master_customers c', 'so.id_customer = c.id_customer', 'left')
      ->join('employee e1', 'e1.id = so.created_by', 'left')
      ->join('employee e2', 'e2.id = so.approved_by', 'left')
      ->join('list_help lh', "lh.id = so.payment_term AND lh.group_by = 'top invoice'", 'left')
      ->where('so.no_so', $no_so)
      ->get()
      ->row();

    // Ambil detail item SO dan unit dari ms_satuan
    $get_so_detail = $this->db
      ->select('d.*, i.id_unit, s.code AS unit')
      ->from('sales_order_detail d')
      ->join('product_costing p', 'p.id = d.id_product', 'left')
      ->join('new_inventory_4 i', 'i.code_lv4 = p.code_lv4', 'left')
      ->join('ms_satuan s', 's.id = i.id_unit', 'left')
      ->where('d.no_so', $no_so)
      ->order_by('d.id', 'ASC')
      ->get()
      ->result();

    $data = [
      'data_so' => $get_so,
      'data_so_detail' => $get_so_detail
    ];

    $this->load->view('print_so', ['results' => $data]);
  }

  // SERVERSIDE
  public function data_side_sales_order()
  {
    $this->Sales_order_model->get_json_sales_order();
  }

  public function get_free_stok()
  {
    $code_lv4 = $this->input->post('code_lv4');

    $stock = $this->db->get_where('warehouse_stock', ['code_lv4' => $code_lv4, 'id_gudang' => 1, 'kd_gudang' => 'PUS'])->row_array();

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

  public function export_excel()
  {
    $start = $this->input->get('start_date', true);
    $end   = $this->input->get('end_date', true);

    $this->db->select('so.no_so, so.tgl_so, so.nilai_so, so.status, p.id_penawaran, p.total_penawaran, p.tipe_penawaran, p.sales, c.name_customer');
    $this->db->from('penawaran p');
    $this->db->join('sales_order so', 'so.id_penawaran = p.id_penawaran', 'left');
    $this->db->join('master_customers c', 'p.id_customer = c.id_customer', 'left');
    $this->db->where('p.status', 'A');
    // Exclude SO yang SPK Lengkap (sudah masuk SO Complete)
    $this->db->group_start();
    $this->db->where('so.status_spk !=', 'SPK Lengkap');
    $this->db->or_where('so.status_spk IS NULL');
    $this->db->group_end();
    if (!empty($start)) $this->db->where('so.tgl_so >=', $start);
    if (!empty($end))   $this->db->where('so.tgl_so <=', $end);
    $this->db->order_by('so.tgl_so', 'DESC');

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
    $sheet->setCellValue('A1', 'REPORT SALES ORDER - ' . $periode);
    $sheet->mergeCells('A1:H2');

    $headers = ['A' => '#', 'B' => 'No. SO', 'C' => 'No. Penawaran', 'D' => 'Tanggal SO', 'E' => 'Customer', 'F' => 'Sales', 'G' => 'Nilai Penawaran', 'H' => 'Nilai SO', 'I' => 'Tipe', 'J' => 'Status'];
    $rowHeader = 4;
    foreach ($headers as $col => $label) {
      $sheet->setCellValue($col . $rowHeader, $label);
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $r = $rowHeader + 1;
    $no = 1;
    foreach ($rows as $row) {
      $sheet->setCellValue('A' . $r, $no++);
      $sheet->setCellValueExplicit('B' . $r, (string)$row->no_so, PHPExcel_Cell_DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('C' . $r, (string)$row->id_penawaran, PHPExcel_Cell_DataType::TYPE_STRING);
      if (!empty($row->tgl_so)) {
        $date = new DateTime($row->tgl_so);
        $tgl = (float)PHPExcel_Shared_Date::FormattedPHPToExcel(
          (int)$date->format('Y'),
          (int)$date->format('m'),
          (int)$date->format('d')
        );
        $sheet->setCellValueExplicit('D' . $r, $tgl, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('D' . $r)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
      }
      $sheet->setCellValueExplicit('E' . $r, (string)$row->name_customer, PHPExcel_Cell_DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('F' . $r, (string)$row->sales, PHPExcel_Cell_DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('G' . $r, (float)$row->total_penawaran, PHPExcel_Cell_DataType::TYPE_NUMERIC);
      $sheet->getStyle('G' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
      $sheet->setCellValueExplicit('H' . $r, (float)$row->nilai_so, PHPExcel_Cell_DataType::TYPE_NUMERIC);
      $sheet->getStyle('H' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
      $sheet->setCellValueExplicit('I' . $r, (string)$row->tipe_penawaran, PHPExcel_Cell_DataType::TYPE_STRING);
      $sheet->setCellValueExplicit('J' . $r, $row->status === 'A' ? 'Deal' : 'Draft', PHPExcel_Cell_DataType::TYPE_STRING);
      $r++;
    }

    $sheet->setTitle('Sales Order');
    $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
    ob_end_clean();
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="Sales_Order_' . date('Ymd_His') . '.xls"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
  }
}


// TRASH

// buat proses DEAL SO
  // public function deal_so()
  // {
  //   $post = $this->input->post();

  //   $no_so = $post['no_so'];
  //   $tgl_so = $post['tgl_so'];

  //   if (empty($no_so)) {
  //     echo json_encode(['status' => 0, 'pesan' => 'Nomor SO tidak ditemukan']);
  //     return;
  //   }

  //   $so = $this->db->get_where('sales_order', ['no_so' => $no_so])->row_array();
  //   if (!$so) {
  //     echo json_encode(['status' => 0, 'pesan' => 'Data Sales Order tidak ditemukan']);
  //     return;
  //   }

  //   $this->db->where('no_so', $no_so);
  //   $this->db->update('sales_order', [
  //     'status'        => 'A', //Deal Sales Order
  //     'approved_by'   => $this->auth->user_id(),
  //     'approved_at'   => date('Y-m-d H:i:s'),
  //   ]);

  //   if (isset($_POST['product']) && is_array($_POST['product'])) {
  //     $arr = [];
  //     foreach ($_POST['product'] as $pro) {
  //       // ambil data warehouse buat stok awal
  //       $code_lv4 = $pro['code_lv4'];
  //       $stok = $this->db->get_where('warehouse_stock', ['code_lv4' => $code_lv4])->row_array();

  //       if ($stok) {
  //         $qty_stock      = floatval($stok['qty_stock']);
  //         $qty_booking    = floatval($stok['qty_booking']);
  //         $qty_free       = floatval($stok['qty_free']);

  //         $qty_post       = floatval($pro['qty']);
  //         $booking_post   = floatval($pro['use_qty_free']);
  //         $free_post      = floatval($pro['qty_free']);

  //         // Kalkulasi untuk mengembalikan stok semula
  //         $stok_awal      = $qty_stock;
  //         $booking_awal   = $qty_booking - $booking_post;
  //         $free_awal      = $qty_free + $booking_post;

  //         $stok_akhir      = $qty_stock;
  //         $booking_akhir   = $qty_booking;
  //         $free_akhir      = $qty_free;
  //       }

  //       // insert ke kartu stok
  //       $arr[] = [
  //         'no_transaksi'      => $no_so,
  //         'transaksi'         => "Sales Order",
  //         'tgl_transaksi'     => $tgl_so,
  //         'code_lv4'          => $pro['code_lv4'],
  //         'nm_product'        => $pro['product_name'],
  //         'qty'               => floatval($stok_awal),
  //         'qty_book'          => floatval($booking_awal),
  //         'qty_free'          => floatval($free_awal),
  //         'qty_transaksi'     => 0,
  //         'qty_akhir'         => $stok_akhir,
  //         'qty_book_akhir'    => $booking_akhir,
  //         'qty_free_akhir'    => $free_akhir,
  //         'harga_stok'        => $pro['harga_beli']
  //       ];
  //     }
  //   }

  //   if (!empty($arr)) {
  //     $this->db->insert_batch('kartu_stok', $arr);
  //   }

  //   echo json_encode([
  //     'status' => 1,
  //     'pesan' => 'Sales Order Deal!!.'
  //   ]);
  // }
