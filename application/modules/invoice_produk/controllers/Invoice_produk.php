<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Invoice_produk extends Admin_Controller
{

	//Permission

	protected $viewPermission   = "Invoice_Produk.View";
	protected $addPermission    = "Invoice_Produk.Add";
	protected $managePermission = "Invoice_Produk.Manage";
	protected $deletePermission = "Invoice_Produk.Delete";

	public function __construct()
	{
		parent::__construct();
		$this->load->library(array('upload', 'Image_lib'));
		$this->load->model('Invoice_produk/Invoice_produk_model');
		$this->load->model('jurnal_nomor/Jurnal_model');
		$this->template->title('Invoice Produk');
		$this->template->page_icon('fa fa-money');
		date_default_timezone_set('Asia/Bangkok');
	}

	public function index()
	{
		$this->auth->restrict($this->viewPermission);

		$get_list_invoice_dp = $this->db->get_where('tr_billing_plan', ['tipe_billing_plan' => 1, 'tipe_so' => 1])->result();

		$data = [
			'list_invoice_dp' => $get_list_invoice_dp
		];
		$this->template->set($data);
		$this->template->render('index');
	}

	public function create_invoice_modal()
	{
		$no_so = $this->input->post('no_so');
		$id = $this->input->post('id');
		$tipe_billing = $this->input->post('tipe_billing');

		// if ($tipe_billing == 'dp') {
		// 	$get_so_details = $this->db->query("
		// 		SELECT
		// 			a.nama_produk as nama_produk,
		// 			a.qty as qty,
		// 			b.harga_satuan as harga_satuan,
		// 			b.diskon_persen as diskon_persen,
		// 			b.diskon_nilai as diskon_nilai,
		// 			b.total_harga as total_harga,
		// 			d.code as uom
		// 		FROM
		// 			tr_sales_order_detail a
		// 			LEFT JOIN tr_penawaran_detail b ON b.id_penawaran_detail = a.id_penawaran_detail
		// 			LEFT JOIN new_inventory_4 c ON c.code_lv4 = b.id_category3
		// 			LEFT JOIN ms_satuan d ON d.id = c.id_unit
		// 		WHERE
		// 			a.no_so = '" . $no_so . "'

		// 		UNION ALL

		// 		SELECT
		// 			a.nm_other as nama_produk,
		// 			a.qty as qty,
		// 			a.harga as harga_satuan,
		// 			0 as diskon_persen,
		// 			0 as diskon_nilai,
		// 			a.total as total_harga,
		// 			d.code as uom
		// 		FROM
		// 			tr_penawaran_other_item a
		// 			LEFT JOIN tr_sales_order b ON b.no_penawaran = a.id_penawaran
		// 			LEFT JOIN new_inventory_4 c ON c.code_lv4 = a.id_other
		// 			LEFT JOIN ms_satuan d ON d.id = c.id_unit
		// 		WHERE
		// 			b.no_so = '" . $no_so . "'
		// 	")->result();
		// 	// print_r($this->db->error($get_so_details));
		// 	// exit;

		// 	$get_so = $this->db->get_where('tr_sales_order', ['no_so' => $no_so])->row();
		// 	$get_penawaran = $this->db->get_where('tr_penawaran', ['no_penawaran' => $get_so->no_penawaran])->row();
		// 	$get_billing_details = $this->db->get_where('tr_billing_plan', ['id' => $id])->row();

		// 	$data = [
		// 		'detail' => $get_so_details,
		// 		'tipe_billing' => $tipe_billing,
		// 		'id_billing' => $id,
		// 		'no_so' => $no_so,
		// 		'billing_details' => $get_billing_details,
		// 		'data_so' => $get_so,
		// 		'data_penawaran' => $get_penawaran
		// 	];
		// 	$this->template->set('results', $data);
		// 	$this->template->render('modal_billing_plan');
		// }

		if ($tipe_billing == 'delivery') {
			// $get_other_cost = $this->db
			// 	->select('b.*')
			// 	->from('sales_order a')
			// 	->join('tr_penawaran_other_cost b', 'b.id_penawaran = a.no_penawaran', 'left')
			// 	->where('a.no_so', $no_so)
			// 	->get()
			// 	->result();

			$sql = "
					SELECT
					sod.product,
					sod.qty_order,
					sjd.qty_terkirim AS qty_delivery,
					sod.harga_penawaran,
					sod.harga_beli,
					sod.price_list,
					sod.diskon_persen,
					sod.diskon_nilai
					FROM surat_jalan_detail sjd
					LEFT JOIN sales_order_detail sod
						ON sod.id = sjd.id_so_det
					WHERE sjd.no_surat_jalan = ?";

			$get_so_detail = $this->db->query($sql, [$id])->result();

			// $persen_dp = 0;
			// $get_persen_dp = $this->db->select('a.persen_billing_plan')->get_where('tr_billing_plan a', ['a.no_so' => $no_so, 'a.tipe_billing_plan' => 1])->result();
			// if (!empty($get_persen_dp)) {
			// 	foreach ($get_persen_dp as $item_persen_dp) {
			// 		$persen_dp += $item_persen_dp->persen_billing_plan;
			// 	}
			// }

			// $persen_retensi = 0;

			// $get_persen_retensi = $this->db
			// 	->select('a.persen_billing_plan')
			// 	->from('tr_billing_plan a')
			// 	->where('a.no_so', $no_so)
			// 	->where('a.tipe_billing_plan', 2)
			// 	->where('(SELECT COUNT(aa.id_invoice) FROM tr_invoice_sales aa WHERE aa.id_billing = a.id) <=', 0)
			// 	->get()
			// 	->result();
			// if (!empty($get_persen_retensi)) {
			// 	foreach ($get_persen_retensi as $item_persen_retensi) {
			// 		$persen_retensi += $item_persen_retensi->persen_billing_plan;
			// 	}
			// }

			// $persen_jaminan = 0;
			// $get_persen_jaminan = $this->db
			// 	->select('a.persen_billing_plan')
			// 	->from('tr_billing_plan a')
			// 	->where('a.no_so', $no_so)
			// 	->where('a.tipe_billing_plan', 3)
			// 	->where('(SELECT COUNT(aa.id_invoice) FROM tr_invoice_sales aa WHERE aa.id_billing = a.id) <=', 0)
			// 	->get()
			// 	->result();
			// if (!empty($get_persen_jaminan)) {
			// 	foreach ($get_persen_jaminan as $item_persen_jaminan) {
			// 		$persen_jaminan += $item_persen_jaminan->persen_billing_plan;
			// 	}
			// }

			$get_penawaran = $this->db
				->select('b.*, c.name_customer')
				->from('sales_order a')
				->join('penawaran b', 'b.id_penawaran = a.id_penawaran', 'left')
				->join('master_customers c', 'c.id_customer = b.id_customer', 'left')
				->where('a.no_so', $no_so)
				->get()
				->row();

			$get_so = $this->db->get_where('sales_order', ['no_so' => $no_so])->row();
			// $get_penawaran = $this->db->get_where('tr_penawaran', ['no_penawaran' => $get_so->no_penawaran])->row();

			$get_sj_pertama = $this->db
				->select('no_surat_jalan')
				->from('surat_jalan')
				->where('no_so', $no_so)
				->order_by('delivery_date', 'ASC')   // pakai tanggal kirim
				->order_by('id', 'ASC')              // fallback kalau tanggal sama
				->limit(1)
				->get()
				->row();

			$is_sj_pertama = ($get_sj_pertama && $get_sj_pertama->no_surat_jalan === $id);

			$data = [
				'tipe_billing' => $tipe_billing,
				'id_billing' => $id,
				'no_so' => $no_so,
				// 'list_other_cost' => $get_other_cost,
				'list_so_detail' => $get_so_detail,
				// 'persen_dp' => $persen_dp,
				// 'persen_retensi' => $persen_retensi,
				// 'persen_jaminan' => $persen_jaminan,
				// 'currency' => $get_penawaran->currency,
				// 'persen_ppn' => $get_penawaran->ppn,
				'data_so' => $get_so,
				'data_penawaran' => $get_penawaran,
				'is_sj_pertama' => $is_sj_pertama,
			];

			$this->template->set('results', $data);
			$this->template->render('modal_billing_delivery');
		}

		// if ($tipe_billing == 'retensi') {
		// 	$get_spk_delivery = $this->db->get_where('spk_delivery', ['no_so' => $no_so])->result();

		// 	$get_billing_plan = $this->db->get_where('tr_billing_plan', ['id' => $id])->row();

		// 	$get_so = $this->db->get_where('tr_sales_order', ['no_so' => $no_so])->row();

		// 	$get_penawaran = $this->db
		// 		->select('b.*')
		// 		->from('tr_sales_order a')
		// 		->join('tr_penawaran b', 'b.no_penawaran = a.no_penawaran', 'left')
		// 		->where('a.no_so', $no_so)
		// 		->get()
		// 		->row();

		// 	$data = [
		// 		'list_spk_delivery' => $get_spk_delivery,
		// 		'billing_plan' => $get_billing_plan,
		// 		'no_so' => $no_so,
		// 		'id_billing' => $id,
		// 		'tipe_billing' => $tipe_billing,
		// 		'data_so' => $get_so,
		// 		'data_penawaran' => $get_penawaran
		// 	];
		// 	$this->template->set('results', $data);
		// 	$this->template->render('modal_billing_retensi');
		// }

		// if ($tipe_billing == 'jaminan') {
		// 	$get_spk_delivery = $this->db->get_where('spk_delivery', ['no_so' => $no_so])->result();

		// 	$get_billing_plan = $this->db->get_where('tr_billing_plan', ['id' => $id])->row();

		// 	$get_so = $this->db->get_where('tr_sales_order', ['no_so' => $no_so])->row();

		// 	$get_dp = $this->db->get_where('tr_invoice_sales', ['id_so' => $no_so, 'tipe_billing' => 'dp'])->row();

		// 	$get_penawaran = $this->db
		// 		->select('b.*')
		// 		->from('tr_sales_order a')
		// 		->join('tr_penawaran b', 'b.no_penawaran = a.no_penawaran', 'left')
		// 		->where('a.no_so', $no_so)
		// 		->get()
		// 		->row();

		// 	$data = [
		// 		'list_spk_delivery' => $get_spk_delivery,
		// 		'billing_plan' => $get_billing_plan,
		// 		'no_so' => $no_so,
		// 		'id_billing' => $id,
		// 		'tipe_billing' => $tipe_billing,
		// 		'data_so' => $get_so,
		// 		'data_penawaran' => $get_penawaran,
		// 		'data_dp' => $get_dp
		// 	];
		// 	$this->template->set('results', $data);
		// 	$this->template->render('modal_billing_jaminan');
		// }
	}

	public function view_invoice_modal()
	{
		$no_so = $this->input->post('no_so');
		$id = $this->input->post('id');
		$tipe_billing = $this->input->post('tipe_billing');
		$id_invoice = $this->input->post('id_invoice');


		// if ($tipe_billing == 'dp') {
		// 	$get_so_details = $this->db->query("
		// 		SELECT
		// 			a.nama_produk as nama_produk,
		// 			a.qty as qty,
		// 			b.harga_satuan as harga_satuan,
		// 			b.diskon_persen as diskon_persen,
		// 			b.diskon_nilai as diskon_nilai,
		// 			b.total_harga as total_harga,
		// 			d.code as uom
		// 		FROM
		// 			tr_sales_order_detail a
		// 			LEFT JOIN tr_penawaran_detail b ON b.id_penawaran_detail = a.id_penawaran_detail
		// 			LEFT JOIN new_inventory_4 c ON c.code_lv4 = b.id_category3
		// 			LEFT JOIN ms_satuan d ON d.id = c.id_unit
		// 		WHERE
		// 			a.no_so = '" . $no_so . "'

		// 		UNION ALL

		// 		SELECT
		// 			a.nm_other as nama_produk,
		// 			a.qty as qty,
		// 			a.harga as harga_satuan,
		// 			0 as diskon_persen,
		// 			0 as diskon_nilai,
		// 			a.total as total_harga,
		// 			d.code as uom
		// 		FROM
		// 			tr_penawaran_other_item a
		// 			LEFT JOIN tr_sales_order b ON b.no_penawaran = a.id_penawaran
		// 			LEFT JOIN new_inventory_4 c ON c.code_lv4 = a.id_other
		// 			LEFT JOIN ms_satuan d ON d.id = c.id_unit
		// 		WHERE
		// 			b.no_so = '" . $no_so . "'
		// 	")->result();
		// 	// print_r($this->db->error($get_so_details));
		// 	// exit;

		// 	$get_so = $this->db->get_where('tr_sales_order', ['no_so' => $no_so])->row();
		// 	$get_penawaran = $this->db->get_where('tr_penawaran', ['no_penawaran' => $get_so->no_penawaran])->row();
		// 	$get_billing_details = $this->db->get_where('tr_billing_plan', ['id' => $id])->row();

		// 	$data = [
		// 		'detail' => $get_so_details,
		// 		'tipe_billing' => $tipe_billing,
		// 		'id_billing' => $id,
		// 		'no_so' => $no_so,
		// 		'billing_details' => $get_billing_details,
		// 		'data_so' => $get_so,
		// 		'data_penawaran' => $get_penawaran
		// 	];
		// 	$this->template->set('results', $data);
		// 	$this->template->render('modal_billing_plan');
		// }

		if ($tipe_billing == 'delivery') {
			// if ($id_invoice !== '') {
			// 	$get_other_cost = $this->db
			// 		->select('b.*')
			// 		->from('tr_sales_order a')
			// 		->join('tr_penawaran_other_cost b', 'b.id_penawaran = a.no_penawaran', 'left')
			// 		->join('tr_used_invoice_sales_other_cost c', 'c.id_other_cost = b.id')
			// 		->where('a.no_so', $no_so)
			// 		->where('c.id_invoice', $id_invoice)
			// 		->get()
			// 		->result();
			// } else {
			// 	$get_other_cost = $this->db
			// 		->select('b.*')
			// 		->from('tr_sales_order a')
			// 		->join('tr_penawaran_other_cost b', 'b.id_penawaran = a.no_penawaran', 'left')
			// 		->where('a.no_so', $no_so)
			// 		->get()
			// 		->result();
			// }

			$get_so_detail = $this->db
				->select('
						COALESCE(s.product, b.product) AS product,
						b.qty_order,
						s.qty_terkirim AS qty_delivery,
						b.harga_beli,
						b.price_list,
						b.harga_penawaran,
						b.diskon_persen,
						b.diskon_nilai
					')
				->from('surat_jalan_detail s')
				->join('sales_order_detail b', 'b.id = s.id_so_det', 'left')
				->where('b.no_so', $no_so)
				->where('s.no_surat_jalan', $id)
				->where('s.qty_terkirim !=', 0)
				->get()
				->result();

			// $persen_dp = 0;
			// $get_persen_dp = $this->db->select('a.persen_billing_plan')->get_where('tr_billing_plan a', ['a.no_so' => $no_so, 'a.tipe_billing_plan' => 1])->row();
			// if (!empty($get_persen_dp)) {
			// 	$persen_dp = $get_persen_dp->persen_billing_plan;
			// }

			// $persen_retensi = 0;
			// $get_persen_retensi = $this->db->select('a.persen_billing_plan')->get_where('tr_billing_plan a', ['a.no_so' => $no_so, 'a.tipe_billing_plan' => 2])->row();
			// if (!empty($get_persen_retensi)) {
			// 	$persen_retensi = $get_persen_retensi->persen_billing_plan;
			// }

			// $persen_jaminan = 0;
			// $get_persen_jaminan = $this->db->select('a.persen_billing_plan')->get_where('tr_billing_plan a', ['a.no_so' => $no_so, 'a.tipe_billing_plan' => 3])->row();
			// if (!empty($get_persen_jaminan)) {
			// 	$persen_jaminan = $get_persen_jaminan->persen_billing_plan;
			// }

			$get_penawaran = $this->db
				->select('b.*, c.name_customer')
				->from('sales_order a')
				->join('penawaran b', 'b.id_penawaran = a.id_penawaran', 'left')
				->join('master_customers c', 'c.id_customer = b.id_customer', 'left')
				->where('a.no_so', $no_so)
				->get()
				->row();

			$get_so = $this->db->get_where('sales_order', ['no_so' => $no_so])->row();
			// $get_penawaran = $this->db->get_where('penawaran', ['no_penawaran' => $get_so->no_penawaran])->row();

			$get_sj_pertama = $this->db
				->select('no_surat_jalan')
				->from('surat_jalan')
				->where('no_so', $no_so)
				->order_by('delivery_date', 'ASC')   // pakai tanggal kirim
				->order_by('id', 'ASC')              // fallback kalau tanggal sama
				->limit(1)
				->get()
				->row();

			$is_sj_pertama = ($get_sj_pertama && $get_sj_pertama->no_surat_jalan === $id);

			$data = [
				'tipe_billing' => $tipe_billing,
				'id_billing' => $id,
				'id_invoice' => $id_invoice,
				'no_so' => $no_so,
				'list_so_detail' => $get_so_detail,
				'persen_ppn' => $get_penawaran->ppn,
				'data_so' => $get_so,
				'data_penawaran' => $get_penawaran,
				'view' => 1,
				'is_sj_pertama' => $is_sj_pertama,
			];

			$this->template->set('results', $data);
			$this->template->render('modal_billing_delivery');
		}

		// if ($tipe_billing == 'retensi') {
		// 	$get_spk_delivery = $this->db->get_where('spk_delivery', ['no_so' => $no_so])->result();

		// 	$get_billing_plan = $this->db->get_where('tr_billing_plan', ['id' => $id])->row();

		// 	$get_so = $this->db->get_where('tr_sales_order', ['no_so' => $no_so])->row();

		// 	$get_penawaran = $this->db
		// 		->select('b.*')
		// 		->from('tr_sales_order a')
		// 		->join('tr_penawaran b', 'b.no_penawaran = a.no_penawaran', 'left')
		// 		->where('a.no_so', $no_so)
		// 		->get()
		// 		->row();

		// 	$data = [
		// 		'list_spk_delivery' => $get_spk_delivery,
		// 		'billing_plan' => $get_billing_plan,
		// 		'no_so' => $no_so,
		// 		'id_billing' => $id,
		// 		'tipe_billing' => $tipe_billing,
		// 		'data_so' => $get_so,
		// 		'data_penawaran' => $get_penawaran
		// 	];
		// 	$this->template->set('results', $data);
		// 	$this->template->render('modal_billing_retensi');
		// }

		// if ($tipe_billing == 'jaminan') {
		// 	$get_spk_delivery = $this->db->get_where('spk_delivery', ['no_so' => $no_so])->result();

		// 	$get_billing_plan = $this->db->get_where('tr_billing_plan', ['id' => $id])->row();

		// 	$get_so = $this->db->get_where('tr_sales_order', ['no_so' => $no_so])->row();

		// 	$get_dp = $this->db->get_where('tr_invoice_sales', ['id_so' => $no_so, 'tipe_billing' => 'dp'])->row();

		// 	$get_penawaran = $this->db
		// 		->select('b.*')
		// 		->from('tr_sales_order a')
		// 		->join('tr_penawaran b', 'b.no_penawaran = a.no_penawaran', 'left')
		// 		->where('a.no_so', $no_so)
		// 		->get()
		// 		->row();

		// 	$data = [
		// 		'list_spk_delivery' => $get_spk_delivery,
		// 		'billing_plan' => $get_billing_plan,
		// 		'no_so' => $no_so,
		// 		'id_billing' => $id,
		// 		'tipe_billing' => $tipe_billing,
		// 		'data_so' => $get_so,
		// 		'data_penawaran' => $get_penawaran,
		// 		'data_dp' => $get_dp
		// 	];
		// 	$this->template->set('results', $data);
		// 	$this->template->render('modal_billing_jaminan');
		// }
	}

	public function create_invoice()
	{
		$post = $this->input->post();
		$id_so = $post['no_so'];

		$tgl_so = $this->db->select('tgl_so')->where('no_so', $id_so)->limit(1)->get('sales_order')->row('tgl_so');
		$get_top = $this->db
			->select('a.payment_term, b.name as top_name, b.data1 as jumlah_top')
			->from('sales_order a')
			->join('list_help b', 'b.id = a.payment_term', 'left')
			->where('a.no_so', $id_so)
			->get()
			->row();

		$id_invoice = $this->Invoice_produk_model->generate_id_invoice();

		$this->db->trans_begin();

		if ($post['tipe_billing'] == 'delivery') {
			// Guard: tolak jika semua barang di SJ ini sudah retur (qty_terkirim = 0 semua)
			$total_terkirim = $this->db
				->select_sum('qty_terkirim', 'total_terkirim')
				->where('no_surat_jalan', $post['id_billing'])
				->get('surat_jalan_detail')
				->row('total_terkirim');

			if ((float)$total_terkirim <= 0) {
				echo json_encode(['status' => false, 'message' => 'Invoice tidak dapat dibuat karena semua barang pada surat jalan ini telah diretur.']);
				return;
			}

			$delivery_date = $this->db->select('delivery_date')
				->where('no_surat_jalan', $post['id_billing'])
				->limit(1)->get('surat_jalan')->row('delivery_date');

			// normalisasi jumlah_top -> integer hari
			$topDays = 0;
			if (!empty($get_top->jumlah_top)) {
				// buang karakter non-digit jika ada (mis "30 HARI")
				$topDays = (int) preg_replace('/\D/', '', $get_top->jumlah_top);
			}

			$jatuh_tempo = null;
			if (!empty($delivery_date)) {
				$jatuh_tempo = date('Y-m-d', strtotime($delivery_date . " +{$topDays} days"));
			}

			$data_insert = [
				'id_invoice' => $id_invoice,
				'id_so' => $id_so,
				'tipe_so' => $post['tipe_so'],
				'tgl_so' => $tgl_so,
				'id_penawaran' => $post['id_penawaran'],
				'id_customer' => $post['id_customer'],
				'nm_customer' => $post['nm_customer'],
				'id_billing' => $post['id_billing'],
				'total_harga_beli' => $post['total_harga_beli'],
				'tipe_billing' => $post['tipe_billing'],
				'nilai_dpp' => $post['nilai_dpp'],
				'nilai_asli' => $post['nilai_asli'],
				'nilai_invoice' => $post['nilai_invoice'],
				'diskon_khusus'	=> $post['diskon_khusus'],
				// 'ppn' => $post['ppn'],
				'nilai_ppn' => $post['nilai_ppn'],
				'grand_total' => $post['grand_total'],
				'piutang'	=> $post['grand_total'],
				'sts' => 1,
				// 'tax_invoice_no' => $post['tax_invoice_no'],
				'created_by' => $this->auth->user_id(),
				'created_on' => date('Y-m-d'),
				'delivery_date' => $delivery_date,
				'jatuh_tempo' => $jatuh_tempo
			];


			$data_insert_detail = [];
			$get_delivery_details = $this->db
				->select('
						s.id_product,
						COALESCE(s.product, b.product) AS product,
						s.qty_terkirim AS qty_delivery,  
						d.code AS uom,
						b.price_list,
						b.harga_penawaran,
						b.diskon_persen
					')
				->from('surat_jalan_detail s')
				->join('sales_order_detail b', 'b.id = s.id_so_det', 'left')
				->join('new_inventory_4 c', 'c.code_lv4 = s.id_product', 'left')
				->join('ms_satuan d', 'd.id = c.id_unit', 'left')
				->where('s.no_surat_jalan', $post['id_billing'])
				->get()
				->result();


			foreach ($get_delivery_details as $item_details) {
				$nilai_disc = (float) $item_details->diskon_persen;
				$subtotal = $item_details->harga_penawaran * $item_details->qty_delivery;

				$data_insert_detail[] = [
					'id_invoice' => $id_invoice,
					'id_so' => $post['no_so'],
					'tipe_so' => $post['tipe_so'],
					'id_penawaran' => $post['id_penawaran'],
					'id_delivery' => $post['id_billing'],
					'id_produk' => $item_details->id_product,
					'nm_produk' => $item_details->product,
					'qty' => $item_details->qty_delivery,
					'uom' => $item_details->uom,
					'harga' => $item_details->harga_penawaran,
					'disc' => $nilai_disc,
					'subtotal' => $subtotal,
					'created_by' => $this->auth->user_id(),
					'created_on' => date('Y-m-d')
				];
			}

			$insert_invoice = $this->db->insert('tr_invoice_sales', $data_insert);
			$insert_invoice_details = $this->db->insert_batch('tr_invoice_sales_detail', $data_insert_detail);

			//SYAMSUDIN 29-08-2025 JURNAL

			$tgl_inv  		= $this->input->post('tgl_jurnal[0]');
			$keterangan  	= "Penjualan atas invoice nomor " . $id_invoice;
			$type        	= $this->input->post('type[0]');
			$reff        	= $id_invoice;
			$no_req      	= $this->input->post('no_request[0]');
			$total       	= str_replace(",", "", $this->input->post('total'));
			$jenis       	= $this->input->post('jenis');
			$tipe_jurnal    = $this->input->post('tipe');
			$jenis_jurnal   = $this->input->post('jenis_jurnal');

			// Hitung nilai piutang dagang (COA 1102-01-01) dari detail jurnal,
			// bukan total invoice keseluruhan.
			$piutang_dagang = 0;
			$arr_no_coa = $this->input->post('no_coa');
			$arr_debet  = $this->input->post('debet');
			if (is_array($arr_no_coa)) {
				for ($p = 0; $p < count($arr_no_coa); $p++) {
					if ($arr_no_coa[$p] == '1102-01-01') {
						$piutang_dagang += (float) str_replace(",", "", $arr_debet[$p]);
					}
				}
			}

			$total_po       = $this->input->post('total_po');
			$id_vendor      = $this->input->post('vendor_id');
			$nama_vendor    = $this->input->post('vendor_nm'); //SYAMSUDIN 29-08-2025

			$Nomor_JV       = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', $tgl_inv);


			$Bln            = substr($tgl_inv, 5, 2);
			$Thn            = substr($tgl_inv, 0, 4);


			$dataJVhead = array(
				'nomor'             => $Nomor_JV,
				'tgl'               => $tgl_inv,
				'jml'               => $total,
				'koreksi_no'        => '-',
				'kdcab'             => '101',
				'jenis'             => 'JV',
				'keterangan'        => $keterangan,
				'bulan'             => $Bln,
				'tahun'             => $Thn,
				'user_id'    	    => $this->auth->user_id(),
				'memo'              => '',
				'tgl_jvkoreksi'     => $tgl_inv,
				'ho_valid'      	=> ''
			);

			$this->db->insert(DBACC . '.javh', $dataJVhead);

			for ($i = 0; $i < count($this->input->post('type')); $i++) {
				$tipe = $this->input->post('type')[$i];
				$perkiraan = $this->input->post('no_coa')[$i];
				$noreff = $id_invoice;

				$datadetail = array(
					'tipe'            => $this->input->post('type')[$i],
					'nomor'           => $Nomor_JV,
					'tanggal'         => $this->input->post('tgl_jurnal')[$i],
					'no_perkiraan'    => $this->input->post('no_coa')[$i],
					'keterangan'      =>  $keterangan,
					'no_reff'        => $id_invoice,
					'debet'          => round($this->input->post('debet')[$i]),
					'kredit'         => round($this->input->post('kredit')[$i]),
					'created_by' 	 => $this->auth->user_id(),
					'created_on' 	 => date('Y-m-d H:i:s')
				);
				$this->db->insert(DBACC . '.jurnal', $datadetail);
			}

			$Qry_Update_Cabang_acc     = "UPDATE " . DBACC . ".pastibisa_tb_cabang SET nomorJC=nomorJC + 1 WHERE nocab='101'";
			$this->db->query($Qry_Update_Cabang_acc);

			$id_cust  = $post['id_customer'];
			$nama     = $post['nm_customer'];
			$No_Inv   = $id_invoice;

			$datapiutang = array(
				'tipe'            => 'JV',
				'nomor'            => $Nomor_JV,
				'tanggal'        => $tgl_inv,
				'no_perkiraan'  => '1102-01-01',
				'keterangan'    => $keterangan,
				'no_reff'       => $No_Inv,
				'debet'         => $piutang_dagang,
				'kredit'         =>  0,
				'id_supplier'     => $id_cust,
				'nama_supplier'   => $nama,

			);
			$this->db->insert('tr_kartu_piutang', $datapiutang);

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
				$noreff = $id_invoice;

				$datadetail = array(
					'tipe'            => $this->input->post('type')[$i],
					'nomor'           => $Nomor_JV,
					'tanggal'         => $this->input->post('tgl_jurnal')[$i],
					'no_perkiraan'    => $this->input->post('no_coa')[$i],
					'keterangan'      =>  $keterangan,
					'no_reff'         => $id_invoice,
					'debet'           => str_replace(",", "", $this->input->post('debet')[$i]),
					'kredit'          => str_replace(",", "", $this->input->post('kredit')[$i]),
					'created_by' 	  => $this->auth->user_id(),
					'created_on' 	  => date('Y-m-d H:i:s')
				);
				$this->db->insert(DBACC . '.jurnal', $datadetail);
			}

			$Qry_Update_Cabang_acc     = "UPDATE " . DBACC . ".pastibisa_tb_cabang SET nomorJC=nomorJC + 1 WHERE nocab='101'";
			$this->db->query($Qry_Update_Cabang_acc);

			$id_cust  = $post['id_customer'];
			$nama     = $post['nm_customer'];
			$No_Inv   = $id_invoice;

			$datapiutang = array(
				'tipe'          => 'JV',
				'nomor'         => $Nomor_JV,
				'tanggal'       => $tgl_inv,
				'no_perkiraan'  => '1102-01-01',
				'keterangan'    => $keterangan,
				'no_reff'       => $No_Inv,
				'debet'         => $piutang_dagang,
				'kredit'        =>  0,
				'id_supplier'   => $id_cust,
				'nama_supplier' => $nama,

			);
			$this->db->insert('tr_kartu_piutang', $datapiutang);
		}

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			echo json_encode(['status' => false, 'message' => 'Gagal menyimpan data Invoice.']);
		} else {
			$this->db->trans_commit();
			echo json_encode([
				'status'       => true,
				'message'      => 'Data Invoice berhasil disimpan.',
				'redirect_url' => base_url("invoice_produk/print_invoice_delivery/{$id_invoice}")
			]);
		}
	}

	public function cancel_invoice()
	{
		$post = $this->input->post();

		$id_invoice = $post['id_invoice'];

		$ArrHeader = [
			'updated_by' 	=> $this->auth->user_id(),
			'updated_on' 	=> date('Y-m-d H:i:s'),
			'is_cancel'		=> 1
		];

		$this->db->trans_begin();

		$this->db->update('tr_invoice_sales', $ArrHeader, ['id_invoice' => $id_invoice]);

		if ($this->db->trans_status() === FALSE) {
			$this->db->trans_rollback();
			echo json_encode(['status' => false, 'message' => 'Gagal membatalkan Invoice.']);
		} else {
			$this->db->trans_commit();
			echo json_encode(['status' => true, 'message' => 'Invoice berhasil dibatalkan.']);
		}
	}

	/**
	 * Endpoint server-side DataTables untuk tab "Invoice Delivery".
	 * Menggantikan pola lama (fetch semua baris + N+1 query di change_tab())
	 * dengan paging di level SQL supaya hanya baris yang ditampilkan yang diproses.
	 */
	public function data_side_delivery()
	{
		$this->auth->restrict($this->viewPermission);
		$this->Invoice_produk_model->data_side_delivery();
	}

	public function change_tab()
	{
		$tipe = $this->input->post('tipe');

		$hasil = '';

		$tipe  = $this->input->post('tipe', TRUE);
		$start = $this->input->post('start_date', TRUE);
		$end   = $this->input->post('end_date', TRUE);

		// normalisasi simple ke YYYY-MM-DD
		$norm = function ($v) {
			if (!$v) return null;
			return preg_match('#^\d{4}-\d{2}-\d{2}$#', $v) ? $v : null;
		};
		$start = $norm($start);
		$end   = $norm($end);

		if ($tipe == 'dp') {
			$hasil .= '<table id="tblInvoice" class="table table-bordered">
				<thead>
					<tr>
						<th class="text-center">No. SO</th>
						<th class="text-center">No. Invoice</th>
						<th class="text-center">Customer Name</th>
						<th class="text-center">SO</th>
						<th class="text-center">Invoiced</th>
						<th class="text-center">Outstanding Invoice</th>
						<th class="text-center">Billing Plan Date</th>
						<th class="text-center">Action</th>
					</tr>
				</thead>
				<tbody>
			';

			$get_list_invoice_dp = $this->db->get_where('tr_billing_plan', ['tipe_billing_plan' => 1, 'tipe_so' => 1])->result();
			foreach ($get_list_invoice_dp as $item) {

				$this->db->select('b.nilai_ppn');
				$this->db->from('tr_sales_order a');
				$this->db->join('tr_penawaran b', 'b.no_penawaran = a.no_penawaran');
				$this->db->where('a.no_so', $item->no_so);
				$get_penawaran = $this->db->get()->row();
				$nilai_ppn = 0;
				if (!empty($get_penawaran)) {
					$nilai_ppn = $get_penawaran->nilai_ppn;
				}

				$total_so = ($item->total_so + $nilai_ppn);

				$invoiced_value = 0;
				$get_invoiced_value = $this->db->select('IF(a.nilai_invoice IS NULL, 0,  SUM(a.nilai_invoice)) AS invoiced_value')->get_where('tr_invoice_sales a', ['id_so' => $item->no_so])->row();
				if (!empty($get_invoiced_value)) {
					$invoiced_value = $get_invoiced_value->invoiced_value;
				}

				$hasil .= '<tr>';
				$hasil .= '<td class="text-center">' . $item->no_so . '</td>';
				$hasil .= '<td class="text-center">' . $item->id . '</td>';
				$hasil .= '<td class="text-center">' . $item->nm_customer . '</td>';
				$hasil .= '<td class="text-right">' . number_format($total_so, 2) . '</td>';
				$hasil .= '<td class="text-right">' . number_format($invoiced_value, 2) . '</td>';
				$hasil .= '<td class="text-right">' . number_format($total_so - $invoiced_value, 2) . '</td>';
				$hasil .= '<td class="text-center">' . date('d F Y', strtotime($item->billing_plan_date)) . '</td>';

				$id_invoice = '';
				$get_id_invoice = $this->db->get_where('tr_invoice_sales', ['id_billing' => $item->id])->row();
				if (!empty($get_id_invoice)) {
					$id_invoice = $get_id_invoice->id_invoice;
				}

				$edit = '<button type="button" class="btn btn-sm btn-success create_invoice_modal" data-no_so="' . $item->no_so . '" data-id="' . $item->id . '" data-tipe_billing="dp" title="Create"><i class="fa fa-check"></i></button>';

				$view = '<button type="button" class="btn btn-sm btn-info view_invoice_modal" data-no_so="' . $item->no_so . '" data-id="' . $item->id . '" data-tipe_billing="dp"><i class="fa fa-eye"></i></button>';

				$print = '<a href="invoice_produk/print_invoice_dp/' . $id_invoice . '" class="btn btn-sm btn-primary print_invoice_dp" target="_blank" data-id_invoice="' . $id_invoice . '" title="Print Invoice"><i class="fa fa-print"></i></a>';

				$check_invoice_dp = $this->db->get_where('tr_invoice_sales', ['id_billing' => $item->id, 'tipe_billing' => 'dp'])->num_rows();
				if ($check_invoice_dp > 0) {
					$button = $view . ' ' . $print;
				} else {
					$button = $edit;
				}

				$hasil .=	 '<td class="text-center">
								' . $button . '
							</td>';
				$hasil .=	 '</tr>';
			}

			$hasil .= '</tbody>';
		}

		if ($tipe == 'delivery') {
			// PERFORMANCE NOTE:
			// Sebelumnya blok ini melakukan query utama TANPA limit (bisa >1.700 baris)
			// lalu untuk SETIAP baris menjalankan 3-6 query tambahan (N+1 query problem),
			// sehingga total ribuan query dijalankan setiap kali tab ini dibuka.
			// Sekarang blok ini hanya merender skeleton tabel (tbody kosong).
			// Data baris diambil oleh DataTables secara server-side (paging di level SQL)
			// lewat endpoint `invoice_produk/data_side_delivery`, lihat
			// Invoice_produk_model::data_side_delivery().
			$hasil .= '
					<div class="row" style="margin:0 0 10px 0; align-items:center;">
						<div class="col-sm-2" style="display:flex; align-items:center;">
							<label class="form-label" style="margin:0;">Pilih Tanggal Invoice</label>
						</div>
						<div class="col-sm-2">
							<input type="date" id="start_date_delivery" class="form-control input-sm"
								value="' . htmlspecialchars($start ?? '', ENT_QUOTES, 'UTF-8') . '">
						</div>
						<div class="col-sm-1 text-center" style="font-size:16px; display:flex; align-items:center; justify-content:center;">
							<i class="fa fa-arrow-right"></i>
						</div>
						<div class="col-sm-2">
							<input type="date" id="end_date_delivery" class="form-control input-sm"
								value="' . htmlspecialchars($end ?? '', ENT_QUOTES, 'UTF-8') . '">
						</div>
						<div class="col-sm-3">
							<button id="btnFilterDelivery" class="btn bg-purple btn-sm">
								<i class="fa fa-filter"></i> Filter
							</button>
							<button id="btnResetDelivery" class="btn btn-default btn-sm">
								Reset
							</button>
							<button id="btnExportDelivery" class="btn btn-success btn-sm">
								<i class="fa fa-file-excel-o"></i> Export Excel
							</button>
						</div>
					</div>
					<table id="tblInvoice" class="table table-bordered">
					<thead class="bg-blue">
						<tr>
						<th class="text-center">No. DO</th>
						<th class="text-center">No. SO</th>
						<th class="text-center">Tgl. Kirim</th>
						<th class="text-center">No. Invoice</th>
						<th class="text-center">Tgl. Invoice</th>
						<th class="text-center">Nama Customer</th>
						<th class="text-center">Nominal Invoice</th>
						<th class="text-center">Action</th>
						</tr>
					</thead>
					<tbody></tbody>
				';
		}

		if ($tipe == 'retensi') {
			$hasil .= '<table id="tblInvoice" class="table table-bordered">
				<thead>
					<tr>
						<th class="text-center">No. SO</th>
						<th class="text-center">No. Invoice</th>
						<th class="text-center">Nama Customer</th>
						<th class="text-center">Action</th>
					</tr>
				</thead>
				<tbody>
			';

			$get_retensi = $this->db
				->select('a.*')
				->from('tr_billing_plan a')
				->where('a.tipe_billing_plan', 2)
				->where('a.tipe_so', 1)
				->get()
				->result();

			foreach ($get_retensi as $item_retensi) {

				$check_invoice_retensi = $this->db->get_where('tr_invoice_sales', ['id_so' => $item_retensi->no_so, 'id_billing' => $item_retensi->id])->num_rows();

				$edit = '<button type="button" class="btn btn-sm btn-success create_invoice_modal" data-no_so="' . $item_retensi->no_so . '" data-id="' . $item_retensi->id . '" data-tipe_billing="retensi" title="Create"><i class="fa fa-check"></i></button>';

				$view = '<button type="button" class="btn btn-sm btn-info view_invoice_modal"  data-no_so="' . $item_retensi->no_so . '" data-id="' . $item_retensi->id . '" data-tipe_billing="retensi"><i class="fa fa-eye"></i></button>';

				$id_invoice = '';
				$get_id_invoice = $this->db->get_where('tr_invoice_sales', ['id_billing' => $item_retensi->id])->row();
				if (!empty($get_id_invoice)) {
					$id_invoice = $get_id_invoice->id_invoice;
				}

				$print = '<a href="invoice_produk/print_invoice_retensi/' . $id_invoice . '" target="_blank" class="btn btn-sm btn-primary print_invoice_retensi" data-id_invoice="' . $id_invoice . '" title="Print Invoice"><i class="fa fa-print"></i></a>';

				if ($check_invoice_retensi > 0) {
					$button = $view . ' ' . $print;
				} else {
					$button = $edit;
				}

				$hasil .= '<tr>
					<td class="text-center">' . $item_retensi->no_so . '</td>
					<td class="text-center">' . $item_retensi->id . '</td>
					<td class="text-left">' . $item_retensi->nm_customer . '</td>
					<td class="text-center">' . $button . '</td>
				</tr>';
			}
			$hasil .= '</tbody>';
		}

		if ($tipe == 'jaminan') {
			$hasil .= '<table id="tblInvoice" class="table table-bordered">
				<thead>
					<tr>
						<th class="text-center">No. SO</th>
						<th class="text-center">No. Invoice</th>
						<th class="text-center">Nama Customer</th>
						<th class="text-center">Action</th>
					</tr>
				</thead>
				<tbody>
			';

			$get_jaminan = $this->db
				->select('a.*')
				->from('tr_billing_plan a')
				->where('a.tipe_billing_plan', 3)
				->where('a.tipe_so', 1)
				->get()
				->result();

			foreach ($get_jaminan as $item_jaminan) {

				$check_invoice_jaminan = $this->db->get_where('tr_invoice_sales', ['id_so' => $item_jaminan->no_so, 'id_billing' => $item_jaminan->id])->num_rows();

				$edit = '<button type="button" class="btn btn-sm btn-success create_invoice_modal" data-no_so="' . $item_jaminan->no_so . '" data-id="' . $item_jaminan->id . '" data-tipe_billing="jaminan" title="Create"><i class="fa fa-check"></i></button>';

				$view = '<button type="button" class="btn btn-sm btn-info view_invoice_modal" data-no_so="' . $item_jaminan->no_so . '" data-id="' . $item_jaminan->id . '" data-tipe_billing="jaminan"><i class="fa fa-eye"></i></button>';

				$id_invoice = '';
				$get_id_invoice = $this->db->get_where('tr_invoice_sales', ['id_billing' => $item_jaminan->id])->row();
				if (!empty($get_id_invoice)) {
					$id_invoice = $get_id_invoice->id_invoice;
				}

				$print = '<a href="invoic_produk/print_invoice_jaminan/' . $id_invoice . '" target="_blank" class="btn btn-sm btn-primary print_invoice_delivery" data-id_invoice="' . $id_invoice . '" title="Print Invoice"><i class="fa fa-print"></i></a>';

				if ($check_invoice_jaminan > 0) {
					$button = $view . ' ' . $print;
				} else {
					$button = $edit;
				}

				$hasil .= '<tr>
						<td class="text-center">' . $item_jaminan->no_so . '</td>
						<td class="text-center">' . $item_jaminan->id . '</td>
						<td class="text-left">' . $item_jaminan->nm_customer . '</td>
						<td class="text-center">' . $button . '</td>
					</tr>';
			}
			$hasil .= '</tbody>';
		}

		$hasil .= '</table>';

		echo json_encode([
			'hasil' => $hasil
		]);
	}

	public function hitung_delivery_w_other_cost()
	{
		$post = $this->input->post();
		$id_billing = $post['id_billing'];
		$no_so = $post['no_so'];
		$tipe_billing = $post['tipe_billing'];
		$nilai_other_cost = $post['nilai_other_cost'];

		$get_other_cost = $this->db
			->select('b.*')
			->from('tr_sales_order a')
			->join('tr_penawaran_other_cost b', 'b.id_penawaran = a.no_penawaran', 'left')
			->where('a.no_so', $no_so)
			->get()
			->result();

		$get_so_detail = $this->db
			->query("
					SELECT
						b.nama_produk,
						b.qty,
						a.qty_delivery,
						b.harga_satuan,
						b.diskon_persen,
						b.diskon_nilai,
						b.nama_produk
					FROM
						spk_delivery_detail a
						LEFT JOIN tr_sales_order_detail b ON b.no_so = a.no_so AND b.id_category3 = a.code_lv4
					WHERE
						a.no_so = '" . $no_so . "' AND
						a.no_surat_jalan = '" . $id_billing . "'
					GROUP BY a.id
				")
			->result();

		$persen_dp = 0;
		$get_persen_dp = $this->db->select('a.persen_billing_plan')->get_where('tr_billing_plan a', ['a.no_so' => $no_so, 'a.tipe_billing_plan' => 1])->row();
		if (!empty($get_persen_dp)) {
			$persen_dp = $get_persen_dp->persen_billing_plan;
		}

		$persen_retensi = 0;
		$get_persen_retensi = $this->db->select('a.persen_billing_plan')->get_where('tr_billing_plan a', ['a.no_so' => $no_so, 'a.tipe_billing_plan' => 2])->row();
		if (!empty($get_persen_retensi)) {
			$persen_retensi = $get_persen_retensi->persen_billing_plan;
		}

		$persen_jaminan = 0;
		$get_persen_jaminan = $this->db->select('a.persen_billing_plan')->get_where('tr_billing_plan a', ['a.no_so' => $no_so, 'a.tipe_billing_plan' => 3])->row();
		if (!empty($get_persen_jaminan)) {
			$persen_jaminan = $get_persen_jaminan->persen_billing_plan;
		}

		$get_penawaran = $this->db
			->select('b.*')
			->from('tr_sales_order a')
			->join('tr_penawaran b', 'b.no_penawaran = a.no_penawaran', 'left')
			->where('a.no_so', $no_so)
			->get()
			->row();
		$persen_ppn = $get_penawaran->ppn;

		$subtotal = 0;
		foreach ($get_so_detail as $item_detail) {
			$nilai_disc = (float) ($item_detail->harga_satuan * $item_detail->disc_persen / 100);
			$total_harga = (($item_detail->harga_satuan - $nilai_disc) * $item_detail->qty_delivery);

			$subtotal += $total_harga;
		}

		$dp_proporsional = ($subtotal * $persen_dp / 100);
		$retensi_proporsional = ($subtotal * $persen_retensi / 100);
		$jaminan_proporsional = ($subtotal * $persen_jaminan / 100);

		$dpp = ($subtotal - $dp_proporsional - $retensi_proporsional);
		$nilai_ppn = (($dpp + $nilai_other_cost) * $persen_ppn / 100);
		$total_all = ($dpp + $nilai_ppn);
		$total_tagihan = ($total_all + $jaminan_proporsional);

		$nilai_invoice = ($dpp + $nilai_other_cost);

		$hasil = '
			<tr>
				<td class="text-right" colspan="6">Total</td>
				<td class="text-right">(' . $get_penawaran->currency . ') ' . number_format($subtotal, 2) . '</td>
			</tr>
			<tr>
				<td class="text-right" colspan="6">DP Proporsional (' . number_format($persen_dp, 2) . '%)</td>
				<td class="text-right">(' . $get_penawaran->currency . ')  ' . number_format($dp_proporsional, 2) . '</td>
			</tr>
			<tr>
				<td class="text-right" colspan="6">Retensi Proporsional (' . number_format($persen_retensi, 2) . '%)</td>
				<td class="text-right">(' . $get_penawaran->currency . ') ' . number_format($retensi_proporsional, 2) . '</td>
			</tr>
			<tr>
				<td class="text-right" colspan="6">DPP</td>
				<td class="text-right">(' . $get_penawaran->currency . ') ' . number_format($dpp, 2) . '</td>
			</tr>
			<tr>
				<td class="text-right" colspan="6">Other Cost</td>
				<td class="text-right">(' . $get_penawaran->currency . ') ' . number_format($nilai_other_cost, 2) . '</td>
			</tr>
			<tr>
				<td class="text-right" colspan="6">PPn (' . number_format($persen_ppn, 2) . '%)</td>
				<td class="text-right">(' . $get_penawaran->currency . ') ' . number_format($nilai_ppn, 2) . '</td>
			</tr>
			<tr>
				<td class="text-right" colspan="6">Total</td>
				<td class="text-right">(' . $get_penawaran->currency . ') ' . number_format($total_all, 2) . '</td>
			</tr>
			<tr>
				<td class="text-right" colspan="6">Jaminan</td>
				<td class="text-right">(' . $get_penawaran->currency . ') ' . number_format($jaminan_proporsional, 2) . '</td>
			</tr>
			<tr>
				<td class="text-right" colspan="6">Total Tagihan</td>
				<td class="text-right">(' . $get_penawaran->currency . ') ' . number_format($total_tagihan, 2) . '</td>
			</tr>
		';

		echo json_encode([
			'hasil' => $hasil,
			'total_tagihan' => $total_tagihan,
			'nilai_ppn' => $nilai_ppn,
			'nilai_invoice' => $nilai_invoice
		]);
	}

	public function print_invoice_dp()
	{
		ob_clean();
		ob_start();

		$id_invoice = $this->uri->segment(3);

		$get_invoice = $this->db->get_where('tr_invoice_sales', ['id_invoice' => $id_invoice])->row();
		$get_invoice_detail = $this->db->get_where('tr_invoice_sales_detail', ['id_invoice' => $id_invoice])->result();
		$get_so = $this->db->get_where('tr_sales_order', ['no_so' => $get_invoice->id_so])->row();
		$get_customer = $this->db->get_where('customer', ['id_customer' => $get_so->id_customer])->row();
		$get_penawaran = $this->db->get_where('tr_penawaran', ['no_penawaran' => $get_invoice->id_penawaran])->row();

		$get_payment_term = $this->db->get_where('list_help', ['id' => $get_penawaran->top])->row();



		$data = [
			'id_invoice' => $id_invoice,
			'data_invoice' => $get_invoice,
			'data_invoice_detail' => $get_invoice_detail,
			'data_so' => $get_so,
			'data_customer' => $get_customer,
			'data_penawaran' => $get_penawaran,
			'data_payment_term' => $get_payment_term
		];

		$this->load->view('print_invoice_dp', $data);
		$html = ob_get_contents();

		require_once('./assets/html2pdf/html2pdf/html2pdf.class.php');
		$html2pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(10, 5, 10, 5));
		$html2pdf->pdf->SetDisplayMode('fullpage');
		$html = ob_get_contents();

		$html2pdf->WriteHTML($html);

		ob_end_clean();
		$html2pdf->Output('Invoice DP.pdf', 'I');
	}

	public function print_invoice_delivery($id_invoice)
	{
		$this->template->page_icon('fa fa-list');

		// Ambil data invoice utama
		$get_invoice = $this->db->get_where('tr_invoice_sales', ['id_invoice' => $id_invoice])->row();

		// Ambil detail item invoice
		$get_invoice_detail = $this->db
			->select('a.*, b.id_product, b.product, b.price_list, b.diskon_persen, b.harga_penawaran')
			->from('tr_invoice_sales_detail a')
			->join('sales_order_detail b', 'b.no_so = a.id_so AND b.id_product = a.id_produk', 'left')
			->where('a.id_invoice', $id_invoice)
			->get()
			->result();

		// Ambil data sales order
		$get_so = $this->db->get_where('sales_order', ['no_so' => $get_invoice->id_so])->row();

		// Ambil data customer
		$get_customer = $this->db->get_where('master_customers', ['id_customer' => $get_so->id_customer])->row();

		// Ambil data surat jalan
		$get_delivery = $this->db->get_where('surat_jalan', ['no_surat_jalan' => $get_invoice->id_billing])->row();

		// Ambil data penawaran + term of payment (TOP)
		$get_penawaran = $this->db
			->select('a.*, b.name as top_name, b.data1 as jumlah_top')
			->from('penawaran a')
			->join('list_help b', 'b.id = a.payment_term', 'left')
			->where('a.id_penawaran', $get_invoice->id_penawaran)
			->get()
			->row();

		// $spk_pertama = $this->db
		// 	->order_by('no_delivery', 'ASC')
		// 	->get_where('spk_delivery', ['no_so' => $no_so])
		// 	->row();

		// $is_spk_pertama = ($spk_pertama && $spk_pertama->no_surat_jalan == $id);

		// echo '<pre>';
		// print_r($get_invoice_detail);
		// echo '</pre>';
		// die();

		// Susun data untuk dikirim ke view
		$data = [
			'id_invoice'           => $id_invoice,
			'data_invoice'         => $get_invoice,
			'data_invoice_detail'  => $get_invoice_detail,
			'data_penawaran'       => $get_penawaran,
			'data_delivery'        => $get_delivery,
			'data_so'              => $get_so,
			'data_customer'        => $get_customer,
			// 'is_spk_pertama' 	=> $is_spk_pertama,
		];

		// Load view dan generate PDF
		ob_clean();
		ob_start();
		$this->load->view('print_invoice_delivery', $data);
		$html = ob_get_clean();

		// require_once('./assets/html2pdf/html2pdf/html2pdf.class.php');
		// $html2pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(10, 5, 10, 5));
		// $html2pdf->pdf->SetDisplayMode('fullpage');
		// $html2pdf->WriteHTML($html);
		// $html2pdf->Output('Invoice Delivery.pdf', 'I');

		// require_once('./assets/tcpdf/tcpdf.php');
		// $pdf = new TCPDF();
		// $pdf->AddPage();
		// $pdf->writeHTML($html, true, false, true, false, '');
		// $pdf->Output('Invoice Delivery.pdf', 'I');

		$this->load->view('print_invoice_delivery', $data);
	}


	public function print_invoice_retensi()
	{
		ob_clean();
		ob_start();

		$id_invoice = $this->uri->segment(3);

		$get_invoice = $this->db->get_where('tr_invoice_sales', ['id_invoice' => $id_invoice])->row();
		$get_invoice_detail = $this->db->get_where('tr_invoice_sales_detail', ['id_invoice' => $id_invoice])->result();
		$get_so = $this->db->get_where('tr_sales_order', ['no_so' => $get_invoice->id_so])->row();
		$get_customer = $this->db->get_where('customer', ['id_customer' => $get_so->id_customer])->row();



		$data = [
			'id_invoice' => $id_invoice,
			'data_invoice' => $get_invoice,
			'data_invoice_detail' => $get_invoice_detail,
			'data_so' => $get_so,
			'data_customer' => $get_customer
		];

		$this->load->view('print_invoice_retensi', $data);
		$html = ob_get_contents();

		require_once('./assets/html2pdf/html2pdf/html2pdf.class.php');
		$html2pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(10, 5, 10, 5));
		$html2pdf->pdf->SetDisplayMode('fullpage');
		$html = ob_get_contents();

		$html2pdf->WriteHTML($html);

		ob_end_clean();
		$html2pdf->Output('Invoice Retensi.pdf', 'I');
	}

	public function print_invoice_jaminan()
	{
		ob_clean();
		ob_start();

		$id_invoice = $this->uri->segment(3);

		$get_invoice = $this->db->get_where('tr_invoice_sales', ['id_invoice' => $id_invoice])->row();
		$get_invoice_detail = $this->db->get_where('tr_invoice_sales_detail', ['id_invoice' => $id_invoice])->result();
		$get_so = $this->db->get_where('tr_sales_order', ['no_so' => $get_invoice->id_so])->row();
		$get_customer = $this->db->get_where('customer', ['id_customer' => $get_so->id_customer])->row();



		$data = [
			'id_invoice' => $id_invoice,
			'data_invoice' => $get_invoice,
			'data_invoice_detail' => $get_invoice_detail,
			'data_so' => $get_so,
			'data_customer' => $get_customer
		];

		$this->load->view('print_invoice_jaminan', $data);
		$html = ob_get_contents();

		require_once('./assets/html2pdf/html2pdf/html2pdf.class.php');
		$html2pdf = new HTML2PDF('P', 'A4', 'en', true, 'UTF-8', array(10, 5, 10, 5));
		$html2pdf->pdf->SetDisplayMode('fullpage');
		$html = ob_get_contents();

		$html2pdf->WriteHTML($html);

		ob_end_clean();
		$html2pdf->Output('Invoice Jaminan.pdf', 'I');
	}

	public function export_excel_delivery()
	{
		// 1) Ambil parameter filter
		$start = $this->input->get('start', true);
		$end   = $this->input->get('end', true);

		// 2) Query Data (Sama dengan logika tabel Anda)
		$this->db
			->select('sj.no_surat_jalan, sj.delivery_date, sj.no_delivery, sj.no_so, c.name_customer, i.id_invoice, i.created_on, i.is_cancel, sj.created_at')
			->from('surat_jalan sj')
			->join('tr_invoice_sales i', 'sj.no_surat_jalan = i.id_billing AND i.tipe_billing="delivery"', 'left')
			->join('spk_delivery a', 'a.no_delivery = sj.no_delivery', 'left')
			->join('sales_order b', 'b.no_so = sj.no_so', 'left')
			->join('master_customers c', 'c.id_customer = b.id_customer', 'left')
			->where('sj.status !=', 'ON DELIVER')
			->where('sj.status IS NOT NULL')
			->order_by('sj.created_at', 'DESC');

		if ($start && $end) {
			$this->db->where("DATE(i.created_on) >=", $start);
			$this->db->where("DATE(i.created_on) <=", $end);
		} elseif ($start) {
			$this->db->where("DATE(i.created_on) >=", $start);
		} elseif ($end) {
			$this->db->where("DATE(i.created_on) <=", $end);
		}

		$rows = $this->db->get()->result();

		if (empty($rows)) {
			echo "<script>alert('Data tidak ditemukan'); window.history.back();</script>";
			return;
		}

		// 3) Setup PHPExcel
		set_time_limit(0);
		ini_set('memory_limit', '1024M');

		$this->load->library('PHPExcel');
		// PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

		$xls   = new PHPExcel();
		$sheet = $xls->getActiveSheet();

		// Judul
		$periode = ($start && $end) ? $start . ' s/d ' . $end : 'Semua Data';
		$sheet->setCellValue('A1', 'REPORT INVOICE DELIVERY - ' . $periode);
		$sheet->mergeCells('A1:F2');

		// Header Kolom
		$headers = [
			'A' => 'No. DO',
			'B' => 'No. SO',
			'C' => 'Tgl. Kirim',
			'D' => 'No. Invoice',
			'E' => 'Tgl. Invoice',
			'F' => 'Nama Customer',
			'G' => 'Nominal Invoice',
		];

		$rowHeader = 4;
		foreach ($headers as $col => $label) {
			$sheet->setCellValue($col . $rowHeader, $label);
			$sheet->getColumnDimension($col)->setAutoSize(true);
		}

		// 4) Isi Data
		$r = $rowHeader + 1;
		foreach ($rows as $item) {

			// --- LOGIKA HITUNG NOMINAL (Sama dengan di view Anda) ---
			$get_hitung = $this->db->query("
            SELECT sjd.qty_terkirim AS qty, sod.harga_penawaran
            FROM surat_jalan_detail sjd
            LEFT JOIN sales_order_detail sod ON sod.id = sjd.id_so_det
            WHERE sjd.no_surat_jalan = '" . $item->no_surat_jalan . "'
        ")->result();

			$subtotal = 0;
			foreach ($get_hitung as $h) {
				$subtotal += round(($h->harga_penawaran * $h->qty), -2);
			}

			// Cek SJ Pertama untuk Freight
			$get_sj_pertama = $this->db->select('no_surat_jalan')->from('surat_jalan')
				->where('no_so', $item->no_so)->order_by('delivery_date', 'ASC')->order_by('id', 'ASC')->limit(1)->get()->row();

			$freight = 0;
			$diskon_khusus = 0;
			if ($get_sj_pertama && $get_sj_pertama->no_surat_jalan === $item->no_surat_jalan) {
				$f_data = $this->db->select('b.freight, b.diskon_khusus')->from('sales_order a')
					->join('penawaran b', 'b.id_penawaran = a.id_penawaran')->where('a.no_so', $item->no_so)->get()->row();
				$freight = $f_data ? $f_data->freight : 0;
				$diskon_khusus = $f_data ? $f_data->diskon_khusus : 0;
			}

			$includeppn = $subtotal - $diskon_khusus;
			$excludeppn = ($includeppn + $freight) / 1.11;
			$dpp = $excludeppn * 11 / 12;
			$ppn = $dpp * 12 / 100;
			$nominal_invoice = ($excludeppn + $ppn);
			// -------------------------------------------------------

			// Tulis ke Excel
			$sheet->setCellValueExplicit('A' . $r, (string)$item->no_surat_jalan, PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('B' . $r, (string)$item->no_so, PHPExcel_Cell_DataType::TYPE_STRING);

			// Format Tanggal (numeric excel serial)
			if (!empty($item->delivery_date)) {
				// Ambil komponen tanggal langsung dari string (hindari konversi lewat strtotime()
				// + PHPToExcel() yang memaksa timezone UTC, karena itu bisa membuat tanggal
				// mundur 1 hari saat timezone aplikasi (Asia/Bangkok, UTC+7) berbeda dari UTC).
				$dateOnlyKirim = substr($item->delivery_date, 0, 10); // 'Y-m-d'
				list($yk, $mk, $dk) = array_map('intval', explode('-', $dateOnlyKirim));
				$tgl_kirim = (float)PHPExcel_Shared_Date::FormattedPHPToExcel($yk, $mk, $dk);
				$sheet->setCellValueExplicit('C' . $r, $tgl_kirim, PHPExcel_Cell_DataType::TYPE_NUMERIC);
				$sheet->getStyle('C' . $r)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
			} else {
				$sheet->setCellValueExplicit('C' . $r, '', PHPExcel_Cell_DataType::TYPE_STRING);
			}

			$sheet->setCellValueExplicit('D' . $r, (string)$item->id_invoice, PHPExcel_Cell_DataType::TYPE_STRING);

			if (!empty($item->created_on)) {
				// Ambil komponen tanggal langsung dari string (hindari konversi lewat strtotime()
				// + PHPToExcel() yang memaksa timezone UTC, karena itu bisa membuat tanggal
				// mundur 1 hari saat timezone aplikasi (Asia/Bangkok, UTC+7) berbeda dari UTC).
				$dateOnlyInv = substr($item->created_on, 0, 10); // 'Y-m-d'
				list($yi, $mi, $di) = array_map('intval', explode('-', $dateOnlyInv));
				$tgl_inv = (float)PHPExcel_Shared_Date::FormattedPHPToExcel($yi, $mi, $di);
				$sheet->setCellValueExplicit('E' . $r, $tgl_inv, PHPExcel_Cell_DataType::TYPE_NUMERIC);
				$sheet->getStyle('E' . $r)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
			} else {
				$sheet->setCellValueExplicit('E' . $r, '', PHPExcel_Cell_DataType::TYPE_STRING);
			}

			$sheet->setCellValueExplicit('F' . $r, (string)$item->name_customer, PHPExcel_Cell_DataType::TYPE_STRING);

			$sheet->setCellValueExplicit('G' . $r, (float)$nominal_invoice, PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$sheet->getStyle('G' . $r)->getNumberFormat()->setFormatCode('#,##0.00');

			$r++;
		}

		$sheet->setTitle('Invoice Delivery');

		// 5) Output
		$writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
		ob_end_clean();
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="Invoice_Delivery_' . date('Ymd_His') . '.xls"');
		header('Cache-Control: max-age=0');
		$writer->save('php://output');
		exit;
	}
}
