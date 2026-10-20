<?php

if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/*
 * @author Syamsudin
 * @copyright Copyright (c) 2022, Syamsudin
 *
 * This is controller for Purchase Order Payment
 */

class Purchase_order_payment extends Admin_Controller
{
	//Permission
	protected $viewPermission 	= 'Purchase_Order.View';
	protected $addPermission  	= 'Purchase_Order.Add';
	protected $managePermission = 'Purchase_Order.Manage';
	protected $deletePermission = 'Purchase_Order.Delete';

	public function __construct()
	{
		parent::__construct();
		$this->load->library(array('upload', 'Image_lib'));
		$this->load->model(array(
			'Purchase_order_payment/Pr_model',
			'Purchase_order_payment/Jurnal_model',
		));
		$this->template->title('Receive Invoice');
		$this->template->page_icon('fa fa-building-o');

		date_default_timezone_set('Asia/Bangkok');
	}
	public function index()
	{
		$this->auth->restrict($this->viewPermission);
		$session = $this->session->userdata('app_session');
		$this->template->page_icon('fa fa-users');

		$this->db->select('a.*, b.nm_lengkap');
		$this->db->from('tr_purchase_order a');
		$this->db->join('users b', 'b.id_user = a.created_by', 'left');
		$this->db->where('a.status', '2');
		$get_list_po = $this->db->get()->result_array();

		$this->template->set('list_po', $get_list_po);
		$this->template->title('Receive Invoice');
		$this->template->render('index_incoming');
	}

	public function checkbx()
	{
		$post = $this->input->post();

		if ($post['checkbx'] == 'dp') {
			$this->db->select('a.*, b.nm_lengkap, c.nama as nm_supplier, d.id as id_invoice, IF(SUM(d.persen_dp) IS NULL, 0, SUM(d.persen_dp)) as ttl_persen_dp, e.id as id_top, e.progress, e.nilai as nilai_top, e.keterangan as keterangan_top');
			$this->db->from('tr_purchase_order a');
			$this->db->join('users b', 'b.id_user = a.created_by', 'left');
			$this->db->join('new_supplier c', 'c.kode_supplier = a.id_suplier', 'left');
			$this->db->join('tr_top_po e', 'e.no_po = a.no_po');
			$this->db->join('tr_invoice_po d', 'd.no_po = a.no_surat AND d.id_top = e.id', 'left');
			$this->db->where('e.group_top', 76);
			$this->db->where('a.status', '2');
			$this->db->group_by('e.id');
			$this->db->order_by('a.created_on', 'desc');
			$get_list_po = $this->db->get()->result_array();



			$get_supplier = $this->db->get('new_supplier')->result();

			$this->template->set('list_po', $get_list_po);
			$this->template->set('list_supplier', $get_supplier);
			$this->template->render('list_dp');
		} else if ($post['checkbx'] == 'pro') {
			$this->db->select('a.*, b.nm_lengkap, c.nama as nm_supplier, d.id as id_invoice, IF(SUM(d.persen_dp) IS NULL, 0, SUM(d.persen_dp)) as ttl_persen_dp, e.id as id_top, e.progress, e.nilai as nilai_top, e.keterangan as keterangan_top');
			$this->db->from('tr_purchase_order a');
			$this->db->join('users b', 'b.id_user = a.created_by', 'left');
			$this->db->join('new_supplier c', 'c.kode_supplier = a.id_suplier', 'left');
			$this->db->join('tr_top_po e', 'e.no_po = a.no_po');
			$this->db->join('tr_invoice_po d', 'd.no_po = a.no_surat AND d.id_top = e.id', 'left');
			$this->db->where('e.group_top', 77);
			$this->db->where('a.status', '2');
			$this->db->group_by('e.id');
			$this->db->order_by('a.created_on', 'desc');
			$get_list_po = $this->db->get()->result_array();

			$get_supplier = $this->db->get('new_supplier')->result();

			$this->template->set('list_po', $get_list_po);
			$this->template->set('list_supplier', $get_supplier);
			$this->template->render('list_pro');
		} else if ($post['checkbx'] == 'ret') {
			$this->db->select('a.*, b.nm_lengkap, c.nama as nm_supplier, d.id as id_invoice , IF(SUM(d.persen_dp) IS NULL, 0, SUM(d.persen_dp)) as ttl_persen_dp, e.id as id_top, e.progress, e.nilai as nilai_top, e.keterangan as keterangan_top');
			$this->db->from('tr_purchase_order a');
			$this->db->join('users b', 'b.id_user = a.created_by', 'left');
			$this->db->join('new_supplier c', 'c.kode_supplier = a.id_suplier', 'left');
			$this->db->join('tr_top_po e', 'e.no_po = a.no_po');
			$this->db->join('tr_invoice_po d', 'd.no_po = a.no_surat AND d.id_top = e.id', 'left');
			$this->db->where('e.group_top', 78);
			$this->db->where('a.status', '2');
			$this->db->group_by('e.id');
			$this->db->order_by('a.created_on', 'desc');
			$get_list_po = $this->db->get()->result_array();

			$get_supplier = $this->db->get('new_supplier')->result();

			$this->template->set('list_po', $get_list_po);
			$this->template->set('list_supplier', $get_supplier);
			$this->template->render('list_ret');
		} else {
			$this->db->select('a.*');
			$this->db->from('tr_invoice_po a');
			//$this->db->like('a.no_po', 'TR');
			$this->db->order_by('a.created_date', 'desc');
			$get_list_inc = $this->db->get()->result_array();

			$get_supplier = $this->db->get('new_supplier')->result();

			$this->template->set('list_inc', $get_list_inc);
			$this->template->set('list_supplier', $get_supplier);
			$this->template->render('list_inc');
		}
	}

	public function req_app()
	{
		$no_surat = $this->input->post('no_po');
		$id_top = $this->input->post('id_top');
		$tipe = $this->input->post('tipe');


		$get_po = $this->db->get_where('tr_purchase_order', ['no_surat' => $no_surat])->row_array();
		$get_currency = $this->db->get('mata_uang')->result_array();
		$get_supplier = $this->db->get_where('new_supplier', ['kode_supplier' => $get_po['id_suplier']])->row_array();

		$get_total_po = $this->db->select('hargatotal as ttl_po')->get_where('tr_purchase_order', ['no_po' => $get_po['no_po']])->row_array();

		$get_top = $this->db->get_where('tr_top_po', ['id' => $id_top])->row();

		$progress = $get_top->progress;
		$nilai_disc = ($get_po['nilai_disc']);
		$nilai_ppn = (($get_po['total_ppn']) * $progress / 100);

		$this->template->set('data_po', $get_po);
		$this->template->set('list_currency', $get_currency);
		$this->template->set('get_total_po', $get_total_po);
		$this->template->set('get_supplier', $get_supplier);
		$this->template->set('get_top', $get_top);
		$this->template->set('id_top', $id_top);
		$this->template->set('nilai_ppn', $nilai_ppn);
		$this->template->set('nilai_disc', $nilai_disc);
		$this->template->set('progress', $progress);

		if ($tipe == 'dp') {
			$this->template->render('add');
		}
		if ($tipe == 'pro') {
			$this->template->render('add_pro');
		}
		if ($tipe == 'ret') {
			$this->template->render('add_ret');
		}
	}

	public function req_inc_app()
	{
		$no_surat = $this->input->post('no_po');
		$tipe_incoming = $this->input->post('tipe_incoming');


		$get_currency = $this->db->get('mata_uang')->result_array();

		if ($tipe_incoming == 'incoming material') {
			$get_inc = $this->db->get_where('tr_incoming_check', ['kode_trans' => $no_surat])->row_array();
			$get_total_po = $this->db->query('
				SELECT SUM(a.jumlahharga) as ttl_po
				FROM
					dt_trans_po a 
				WHERE
					a.id IN (SELECT aa.id_po_detail FROM tr_incoming_check_detail aa WHERE aa.kode_trans = "' . $no_surat . '")
			')->row_array();

			$get_po = $this->db->get_where('tr_purchase_order', ['no_po' => $get_inc['no_ipp']])->row();

			$this->db->select('a.nama as nm_supplier');
			$this->db->from('new_supplier a');
			$this->db->join('tr_purchase_order b', 'b.id_suplier = a.kode_supplier', 'left');
			$this->db->where('b.no_po', $get_inc['no_ipp']);
			$get_supplier = $this->db->get()->row();

			// $get_list_inc = $this->db->get_where('tr_incoming_check_detail', ['kode_trans' => $no_surat])->result_array();

			$this->db->select('a.*, b.hargasatuan, b.qty as qty_po, c.no_surat, (d.qty_ng + d.qty_oke) as qty_incoming');
			$this->db->from('tr_incoming_check_detail a');
			$this->db->join('dt_trans_po b', 'b.id = a.id_po_detail', 'left');
			$this->db->join('tr_purchase_order c', 'c.no_po = b.no_po', 'left');
			$this->db->join('tr_checked_incoming_detail d', 'd.id_detail = a.id', 'left');
			$this->db->where('a.kode_trans', $no_surat);
			// $this->db->group_by('a.id');
			$get_list_inc = $this->db->get()->result_array();

			$no_surat = $get_list_inc[0]['no_surat'];

			$get_invoice = $this->db->get_where('tr_invoice_po', ['no_po' => $no_surat])->row_array();

			$total_dp = 0;
			$get_total_dp = $this->db->get_where('tr_invoice_po', ['no_po' => $no_surat])->row_array();
			if (!empty($get_total_dp)) {
				$total_dp = $get_total_dp['value_dp'];
			}

			$total_incoming = 0;
			foreach ($get_list_inc as $item) {
				$total_incoming += ($item['qty_incoming'] * $item['hargasatuan']);
			}
		} else {
			$get_inc = $this->db->get_where('warehouse_adjustment', ['kode_trans' => $no_surat])->row_array();
			$get_total_po = $this->db->query('
				SELECT SUM(a.jumlahharga) as ttl_po
				FROM
					dt_trans_po a 
				WHERE
					a.id IN (SELECT aa.no_ipp FROM warehouse_adjustment_detail aa WHERE aa.kode_trans = "' . $no_surat . '")
			')->row_array();

			// $get_list_inc = $this->db->get_where('tr_incoming_check_detail', ['kode_trans' => $no_surat])->result_array();

			$get_po = $this->db->get_where('tr_purchase_order', ['no_po' => $get_inc['no_ipp']])->row();

			$this->db->select('a.nama as nm_supplier');
			$this->db->from('new_supplier a');
			$this->db->join('tr_purchase_order b', 'b.id_suplier = a.kode_supplier', 'left');
			$this->db->where('b.no_po', $get_inc['no_ipp']);
			$get_supplier = $this->db->get()->row();

			$get_list_inc = $this->db->query("
				SELECT
					a.*, b.hargasatuan, b.qty as qty_po, c.no_surat, (a.qty_oke + a.qty_rusak) as qty_incoming
				FROM
					warehouse_adjustment_detail a
					LEFT JOIN dt_trans_po b ON b.id = a.no_ipp
					LEFT JOIN tr_purchase_order c ON c.no_po = b.no_po
				WHERE
					a.kode_trans = '" . $no_surat . "'
			")->result_array();

			$no_surat = $get_list_inc[0]['no_surat'];

			$get_invoice = $this->db->get_where('tr_invoice_po', ['no_po' => $no_surat])->row_array();

			$total_dp = 0;
			$get_total_dp = $this->db->get_where('tr_invoice_po', ['no_po' => $no_surat])->row_array();
			if (!empty($get_total_dp)) {
				$total_dp = $get_total_dp['value_dp'];
			}

			$total_incoming = 0;
			foreach ($get_list_inc as $item) {
				$total_incoming += ($item['qty_incoming'] * $item['hargasatuan']);
			}
		}

		$this->template->set('data_inc', $get_inc);
		$this->template->set('list_currency', $get_currency);
		$this->template->set('get_total_po', $get_total_po);
		$this->template->set('list_inc', $get_list_inc);
		$this->template->set('total_dp', $total_dp);
		$this->template->set('total_incoming', $total_incoming);
		$this->template->set('tipe_incoming', $tipe_incoming);
		$this->template->set('get_supplier', $get_supplier);
		$this->template->set('data_po', $get_po);
		$this->template->render('add_inc');
	}

	public function view()
	{
		$id = $this->input->post('id');
		$tipe = $this->input->post('tipe');

		$get_invoice = $this->db->get_where('tr_invoice_po', ['id' => $id])->row_array();
		$id_top = $get_invoice['id_top'];

		$get_po = $this->db->get_where('tr_purchase_order', ['no_surat' => $get_invoice['no_po']])->row();
		$get_top = $this->db->get_where('tr_top_po', ['id' => $id_top])->row();

		$this->template->set('data_invoice', $get_invoice);
		$this->template->set('nilai_ppn', $get_invoice['nilai_ppn']);
		$this->template->set('nilai_disc', $get_invoice['nilai_disc']);
		$this->template->set('nilai_top', $get_top->nilai);
		if ($tipe == 'dp') {
			$this->template->render('view');
		}
		if ($tipe == 'pro') {
			$this->template->render('view_pro');
		}
		if ($tipe == 'ret') {
			$this->template->render('view_ret');
		}
	}

	public function view_inc()
	{
		$id = $this->input->post('id');

		$get_invoice = $this->db->get_where('tr_invoice_po', ['id' => $id])->row_array();
		$kode_trans = str_replace(', ', ',', $get_invoice['no_incoming']);
		$no_incoming = explode(',', $kode_trans);

		// Hitung ulang Total Incoming dari data detail (sama seperti rec_invoice_btn)
		$in_clause = "'" . implode("','", $no_incoming) . "'";

		$total_invoice = 0;
		$query_ttl = "
			SELECT SUM(b.qty_oke * c.hargasatuan) as subtotal
			FROM tr_incoming_check_detail a
			JOIN tr_checked_incoming_detail b ON b.id_detail = a.id
			JOIN dt_trans_po c ON c.id = a.id_po_detail
			WHERE a.kode_trans IN ($in_clause)
			UNION ALL

			SELECT (a.qty_oke * b.hargasatuan) FROM warehouse_adjustment_detail a 
			JOIN dt_trans_po b ON b.id = a.no_ipp 
			LEFT JOIN warehouse_adjustment c ON c.kode_trans = a.kode_trans
			WHERE a.kode_trans IN ($in_clause) AND c.category = 'incoming stok'
			UNION ALL

			SELECT (a.qty_oke * b.harga) FROM warehouse_adjustment_detail a 
			JOIN tr_pr_detail_kasbon b ON b.id_detail = a.id_po_detail AND b.id_kasbon = a.no_ipp 
			LEFT JOIN warehouse_adjustment c ON c.kode_trans = a.kode_trans
			WHERE a.kode_trans IN ($in_clause) AND c.category = 'incoming non rutin'
			UNION ALL

			SELECT (a.qty_oke * d.hargasatuan) FROM warehouse_adjustment_detail a 
			JOIN tr_purchase_order b ON b.no_surat = a.no_ipp 
			JOIN dt_trans_po d ON d.no_po = b.no_po AND a.nm_material = d.namamaterial 
			LEFT JOIN warehouse_adjustment c ON c.kode_trans = a.kode_trans
			WHERE a.kode_trans IN ($in_clause) AND c.category = 'incoming asset'
		";
		$res_ttl = $this->db->query($query_ttl)->result();
		foreach ($res_ttl as $row) $total_invoice += $row->subtotal;

		// Override total_pembelian dan nilai_ppn dengan perhitungan ulang
		$kurs = (!empty($get_invoice['kurs']) && $get_invoice['kurs'] > 0) ? $get_invoice['kurs'] : 1;
		$total_invoice_final = $total_invoice * $kurs;
		$nilai_ppn = $total_invoice_final * 11 / 100;

		$get_invoice['total_pembelian'] = $total_invoice;
		$get_invoice['nilai_ppn'] = $nilai_ppn;

		$this->template->set('data_invoice', $get_invoice);
		$this->template->set('no_incoming', $no_incoming);

		$this->template->render('view_inc');
	}

	/**
	 * Normalisasi string angka berformat (dari field autoNumeric / number_format)
	 * menjadi nilai numeric murni yang aman disimpan ke DB.
	 *
	 * Menangani kasus di mana pemisah ribuan bisa berupa koma (default autoNumeric)
	 * ATAU titik (locale Indonesia), sehingga "709.000" tidak lagi terbaca menjadi 709.
	 *
	 * Aturan:
	 *  - Jika ada koma DAN titik: separator terakhir dianggap desimal, sisanya ribuan.
	 *  - Jika hanya koma: koma dianggap pemisah ribuan (bawaan autoNumeric) -> dibuang.
	 *  - Jika hanya titik:
	 *      * tepat 3 digit setelah titik tunggal -> pemisah ribuan -> dibuang (709.000 => 709000)
	 *      * selain itu (1, 2, atau >=4 digit) -> dianggap desimal.
	 *      * lebih dari satu titik -> semua dianggap pemisah ribuan.
	 */
	private function clean_number($value)
	{
		if ($value === null) {
			return 0;
		}

		$s = trim((string) $value);
		if ($s === '') {
			return 0;
		}

		// Tangani tanda negatif
		$neg = (strpos($s, '-') === 0) ? '-' : '';

		// Sisakan hanya digit, koma, dan titik
		$s = preg_replace('/[^0-9.,]/', '', $s);
		if ($s === '') {
			return 0;
		}

		$hasComma = strpos($s, ',') !== false;
		$hasDot   = strpos($s, '.') !== false;

		if ($hasComma && $hasDot) {
			// Separator yang muncul paling akhir = pemisah desimal
			if (strrpos($s, ',') > strrpos($s, '.')) {
				// koma sebagai desimal, titik sebagai ribuan
				$s = str_replace('.', '', $s);
				$s = str_replace(',', '.', $s);
			} else {
				// titik sebagai desimal, koma sebagai ribuan
				$s = str_replace(',', '', $s);
			}
		} elseif ($hasComma) {
			// Hanya koma -> pemisah ribuan (bawaan autoNumeric)
			$s = str_replace(',', '', $s);
		} elseif ($hasDot) {
			$dotCount = substr_count($s, '.');
			if ($dotCount > 1) {
				// Beberapa titik pasti pemisah ribuan
				$s = str_replace('.', '', $s);
			} else {
				$decimals = strlen(substr(strrchr($s, '.'), 1));
				if ($decimals === 3) {
					// Pola ribuan ala Indonesia: 709.000 -> 709000
					$s = str_replace('.', '', $s);
				}
				// selain itu: biarkan titik sebagai desimal
			}
		}

		return (float) ($neg . $s);
	}

	public function save_invoice()
	{
		$post = $this->input->post();

		$config['upload_path'] = './uploads/invoice'; //path folder
		$config['allowed_types'] = '*'; //type yang dapat diakses bisa anda sesuaikan
		$config['max_size'] = 100000000; // Maximum file size in kilobytes (2MB).
		$config['encrypt_name'] = TRUE; // Encrypt the uploaded file's name.
		$config['remove_spaces'] = FALSE; // Remove spaces from the file name.

		$this->load->library('upload', $config);
		$this->upload->initialize($config);

		$this->db->trans_begin();

		$link_doc = '';
		if ($this->upload->do_upload('upload_invoice')) {
			$data_upload_po = $this->upload->data();
			$link_doc = 'uploads/invoice/' . $data_upload_po['file_name'];
		}

		$no_po = $post['no_po'];
		$no_po1 = $post['nomor_po'];
		$kurs = $this->clean_number($post['kurs']);

		$no_invoice = $this->Pr_model->generate_no_invoice();

		if ($post['tipe_req'] == 'dp') {
			$get_po = $this->db->get_where('tr_purchase_order', ['no_surat' => $post['nomor_po']])->row();
			$get_supplier = $this->db->get_where('new_supplier', ['kode_supplier' => $get_po->id_suplier])->row();

			$insert_invoice = $this->db->insert('tr_invoice_po', [
				'id' => $no_invoice,
				'no_po' => $post['no_po'],
				'curr' => $post['currency'],
				'invoice_date' => $post['invoice_date'],
				'value_dp' => $this->clean_number($post['value_dp']),
				'invoice_no' => $post['nomor_invoice'],
				'total_pembelian' => $this->clean_number($post['total_pembelian']),
				'no_faktur_pajak' => $post['nomor_faktur_pajak'],
				'persen_dp' => $post['persen_dp'],
				'link_doc' => $link_doc,
				'invoice_date_real' => $post['invoice_date_real'],
				'tanggal_faktur_pajak' => $post['tanggal_faktur_pajak'],
				'id_supplier' => $get_supplier->kode_supplier,
				'nm_supplier' => $get_supplier->nama,
				'id_top' => $post['id_top'],
				'bank' => $post['bank'],
				'no_bank' => $post['no_bank'],
				'nm_acc_bank' => $post['nm_acc_bank'],
				'nilai_disc' => $this->clean_number($post['nilai_disc']),
				'nilai_ppn' => $this->clean_number($post['nilai_ppn']),
				'total_invoice' => $this->clean_number($post['nilai_ppn']) + $this->clean_number($post['total_pembelian']),
				'kurs' => $this->clean_number($post['kurs']),
				'created_by' => $this->auth->user_id(),
				'created_date' => date('Y-m-d H:i:s')
			]);
			if (!$insert_invoice) {
				print_r($this->db->error($insert_invoice));
			}
		} else {
			$arr_id_suplier = [];
			$get_id_suplier = $this->db->query("SELECT a.id_suplier FROM tr_purchase_order a WHERE a.no_surat IN ('" . str_replace(",", "','", $post['nomor_po']) . "') GROUP BY a.id_suplier")->result();
			foreach ($get_id_suplier as $item_id_suplier) {
				$arr_id_suplier[] = $item_id_suplier->id_suplier;
			}

			$arr_nm_supplier = [];
			$get_nm_supplier = $this->db->query("SELECT a.nama FROM new_supplier a WHERE a.kode_supplier IN ('" . str_replace(",", "','", implode(',', $arr_id_suplier)) . "')")->result();
			foreach ($arr_nm_supplier as $item_nm_supplier) {
				$arr_nm_supplier[] = $item_nm_supplier->nama;
			}

			$insert_invoice = $this->db->insert('tr_invoice_po', [
				'id' => $no_invoice,
				'no_po' => $post['no_po'],
				'no_incoming' => $post['nomor_po'],
				'curr' => $post['currency'],
				'invoice_date' => $post['invoice_date'],
				'value_dp' => $this->clean_number($post['value_dp']),
				'invoice_no' => $post['nomor_invoice'],
				'total_pembelian' => $this->clean_number($post['total_pembelian']),
				'no_faktur_pajak' => $post['nomor_faktur_pajak'],
				'link_doc' => $link_doc,
				'req_payment_po' => $this->clean_number($post['req_payment_po']),
				'total_invoice' => $this->clean_number($post['total_invoice']),
				'notes' => $post['notes'],
				'invoice_date_real' => $post['invoice_date_real'],
				'tanggal_faktur_pajak' => $post['tanggal_faktur_pajak'],
				'id_supplier' => $post['kode_supplier'],
				'nm_supplier' => $post['nama_supplier'],
				'nilai_ppn' => $this->clean_number($post['nilai_ppn']),
				'nilai_disc' => $this->clean_number($post['nilai_disc']),
				'bank' => $post['bank'],
				'no_bank' => $post['no_bank'],
				'nm_acc_bank' => $post['nm_acc_bank'],
				'kurs' => $this->clean_number($post['kurs']),
				'nilai_retur' => $this->clean_number(isset($post['nilai_retur']) ? $post['nilai_retur'] : '0'),
				'total_tagihan' => $this->clean_number(isset($post['total_tagihan']) ? $post['total_tagihan'] : '0'),
				'id_retur_pembelian' => isset($post['id_retur_pembelian']) ? $post['id_retur_pembelian'] : null,
				'created_by' => $this->auth->user_id(),
				'created_date' => date('Y-m-d H:i:s')
			]);
			if (!$insert_invoice) {
				print_r($this->db->error($insert_invoice));
			}

			// Update settlement di tabel retur pembelian jika ada retur yang dipilih
			$nilai_retur_settle = (float) $this->clean_number(isset($post['nilai_retur']) ? $post['nilai_retur'] : '0');
			$ids_retur_pembelian = isset($post['id_retur_pembelian']) ? $post['id_retur_pembelian'] : '';
			if ($nilai_retur_settle > 0 && !empty($ids_retur_pembelian)) {
				$arr_ids = array_filter(explode(',', $ids_retur_pembelian));
				$sisa_settle = $nilai_retur_settle;

				foreach ($arr_ids as $id_retur) {
					if ($sisa_settle <= 0) break;

					$get_retur = $this->db->get_where('tr_retur_pembelian', ['id' => $id_retur])->row();
					if (!$get_retur || $get_retur->sisa_retur <= 0) continue;

					// Settle sebesar sisa_retur atau sisa yang perlu di-settle (mana yang lebih kecil)
					$settle_amount = min($sisa_settle, $get_retur->sisa_retur);
					$new_settlement = $get_retur->settlement + $settle_amount;
					$new_sisa_retur = $get_retur->total_retur - $new_settlement;
					if ($new_sisa_retur < 0) $new_sisa_retur = 0;

					$update_status = ($new_sisa_retur <= 0) ? 3 : $get_retur->status;

					$this->db->update('tr_retur_pembelian', [
						'settlement' => $new_settlement,
						'sisa_retur' => $new_sisa_retur,
						'status' => $update_status,
						'updated_by' => $this->auth->user_id(),
						'updated_date' => date('Y-m-d H:i:s'),
					], ['id' => $id_retur]);

					$sisa_settle -= $settle_amount;
				}
			}
		}

		$get_users = $this->db->get_where('users', ['id_user' => $this->auth->user_id()])->row_array();

		if ($post['tipe_req'] == 'dp') {
			$get_po = $this->db->get_where('tr_purchase_order', ['no_surat' => $post['nomor_po']])->row();
			$get_supplier = $this->db->get_where('new_supplier', ['kode_supplier' => $get_po->id_suplier])->row();

			$get_top = $this->db->get_where('tr_top_po', ['id' => $post['id_top']])->row();
			if ($get_top->group_top == 76) {
				$insert_expense = $this->db->insert('tr_expense', [
					'no_doc' => $no_invoice,
					'tgl_doc' => $post['invoice_date'],
					'nama' => $get_users['nm_lengkap'],
					'approval' => $get_users['nm_lengkap'],
					'status' => 1,
					'created_by' => $get_users['nm_lengkap'],
					'created_on' => date('Y-m-d H:i:s'),
					'approved_by' => $get_users['nm_lengkap'],
					'approved_on' => date('Y-m-d H:i:s'),
					'jumlah' => $this->clean_number($post['value_dp']),
					'informasi' => 'Pembayaran DP : ' . $no_po . ' (' . $get_supplier->nama . ')',
					'exp_inv_po' => 1,
					'bank_id' => $post['bank'],
					'accnumber' => $post['no_bank'],
					'accname' => $post['nm_acc_bank'],
					'id_po' => $post['nomor_po']
				]);
				if (!$insert_expense) {
					print_r($this->db->error($insert_expense));
					exit;
				}

				$insert_expense_detail = $this->db->insert('tr_expense_detail', [
					'tanggal' => $post['invoice_date'],
					'no_doc' => $no_invoice,
					'deskripsi' => 'Pembayaran DP : ' . $no_po . ' (' . $get_supplier->nama . ')',
					'qty' => 1,
					'harga' => $this->clean_number($post['value_dp']),
					'total_harga' => $this->clean_number($post['value_dp']),
					'status' => 0,
					'keterangan' => 'Pembayaran DP : ' . $no_po . ' (' . $get_supplier->nama . ')',
					'expense' => $this->clean_number($post['value_dp']),
					'created_by' => $get_users['nm_lengkap'],
					'created_on' => date('Y-m-d H:i:s')
				]);
				if (!$insert_expense_detail) {
					print_r($this->db->error($insert_expense_detail));
					exit;
				}

				if ($post['currency'] == 'IDR') {
					$kurs  = 1;
				} else {
					$kurs  = $this->clean_number($post['kurs']);
				}

				$dpp_dp_idr = ($this->clean_number($post['total_pembelian']) * $kurs);
				$dpp_dp = ($this->clean_number($post['total_pembelian']));

				$update_uang_muka = $this->db->update('tr_purchase_order', ['uang_muka_idr' => $dpp_dp_idr], ['no_surat' => $no_po1]);
				$update_uang_muka1 = $this->db->update('tr_purchase_order', ['uang_muka' => $dpp_dp], ['no_surat' => $no_po1]);
				$update_kurs       = $this->db->update('tr_purchase_order', ['kurs_terima_invoice' => $kurs], ['no_surat' => $no_po1]);
			}
			if ($get_top->group_top == 77) {
				$insert_expense = $this->db->insert('tr_expense', [
					'no_doc' => $no_invoice,
					'tgl_doc' => $post['invoice_date'],
					'nama' => $get_users['nm_lengkap'],
					'approval' => $get_users['nm_lengkap'],
					'status' => 1,
					'created_by' => $get_users['nm_lengkap'],
					'created_on' => date('Y-m-d H:i:s'),
					'approved_by' => $get_users['nm_lengkap'],
					'approved_on' => date('Y-m-d H:i:s'),
					'jumlah' => $this->clean_number($post['value_dp']),
					'informasi' => 'Pembayaran Progress : ' . $no_po . ' (' . $get_supplier->nama . ')',
					'exp_inv_po' => 1,
					'bank_id' => $post['bank'],
					'accnumber' => $post['no_bank'],
					'accname' => $post['nm_acc_bank']
				]);
				if (!$insert_expense) {
					print_r($this->db->error($insert_expense));
					exit;
				}

				$insert_expense_detail = $this->db->insert('tr_expense_detail', [
					'tanggal' => $post['invoice_date'],
					'no_doc' => $no_invoice,
					'deskripsi' => 'Pembayaran Progress : ' . $no_po . ' (' . $get_supplier->nama . ')',
					'qty' => 1,
					'harga' => $this->clean_number($post['value_dp']),
					'total_harga' => $this->clean_number($post['value_dp']),
					'status' => 0,
					'keterangan' => 'Pembayaran Progress : ' . $no_po . ' (' . $get_supplier->nama . ')',
					'expense' => $this->clean_number($post['value_dp']),
					'created_by' => $get_users['nm_lengkap'],
					'created_on' => date('Y-m-d H:i:s')
				]);
				if (!$insert_expense_detail) {
					print_r($this->db->error($insert_expense_detail));
					exit;
				}

				if ($post['currency'] == 'IDR') {
					$kurs  = 1;
				} else {
					$kurs  = $this->clean_number($post['kurs']);
				}

				$dpp_dp_idr = ($this->clean_number($post['total_pembelian']) * $kurs);
				$dpp_dp = ($this->clean_number($post['total_pembelian']));

				$update_uang_muka = $this->db->update('tr_purchase_order', ['uang_muka_idr' => $dpp_dp_idr], ['no_surat' => $no_po1]);
				$update_uang_muka1 = $this->db->update('tr_purchase_order', ['uang_muka' => $dpp_dp], ['no_surat' => $no_po1]);
				$update_kurs       = $this->db->update('tr_purchase_order', ['kurs_terima_invoice' => $kurs], ['no_surat' => $no_po1]);
			}
			if ($get_top->group_top == 78) {
				$insert_expense = $this->db->insert('tr_expense', [
					'no_doc' => $no_invoice,
					'tgl_doc' => $post['invoice_date'],
					'nama' => $get_users['nm_lengkap'],
					'approval' => $get_users['nm_lengkap'],
					'status' => 1,
					'created_by' => $get_users['nm_lengkap'],
					'created_on' => date('Y-m-d H:i:s'),
					'approved_by' => $get_users['nm_lengkap'],
					'approved_on' => date('Y-m-d H:i:s'),
					'jumlah' => $this->clean_number($post['value_dp']),
					'informasi' => 'Pembayaran Retensi : ' . $no_po1 . ' (' . $get_supplier->nama . ')',
					'exp_inv_po' => 1,
					'bank_id' => $post['bank'],
					'accnumber' => $post['no_bank'],
					'accname' => $post['nm_acc_bank']
				]);
				if (!$insert_expense) {
					print_r($this->db->error($insert_expense));
					exit;
				}

				$insert_expense_detail = $this->db->insert('tr_expense_detail', [
					'tanggal' => $post['invoice_date'],
					'no_doc' => $no_invoice,
					'deskripsi' => 'Pembayaran Retensi : ' . $no_po1 . ' (' . $get_supplier->nama . ')',
					'qty' => 1,
					'harga' => $this->clean_number($post['value_dp']),
					'total_harga' => $this->clean_number($post['value_dp']),
					'status' => 0,
					'keterangan' => 'Pembayaran Retensi : ' . $no_po1 . ' (' . $get_supplier->nama . ')',
					'expense' => $this->clean_number($post['value_dp']),
					'created_by' => $get_users['nm_lengkap'],
					'created_on' => date('Y-m-d H:i:s')
				]);
				if (!$insert_expense) {
					print_r($this->db->error($insert_expense));
					exit;
				}
			}
		} else {
			$arr_id_suplier = [];
			$get_id_suplier = $this->db->query("SELECT a.id_suplier FROM tr_purchase_order a WHERE a.no_surat IN ('" . str_replace(",", "','", $post['no_po']) . "') GROUP BY a.id_suplier")->result();
			foreach ($get_id_suplier as $item_id_suplier) {
				$arr_id_suplier[] = $item_id_suplier->id_suplier;
			}

			// print_r(str_replace(",", "','", $post['nomor_po']));
			// exit;

			$arr_nm_supplier = [];
			if (!empty($arr_id_suplier)) {
				$get_nm_supplier = $this->db->select('nama')->from('new_supplier')->where_in('kode_supplier', $arr_id_suplier)->get()->result();
				foreach ($get_nm_supplier as $item_nm_supplier) {
					$arr_nm_supplier[] = $item_nm_supplier->nama;
				}
			}

			$check_po = $this->db->get_where('tr_purchase_order', ['no_surat' => $no_po])->result();
			if (count($check_po) < 1) {
				$update_kurs       = $this->db->update('rutin_non_planning_header', ['kurs_terima_invoice_progress' => $kurs], ['no_pr' => $no_po]);
			} else {
				$update_kurs       = $this->db->update('tr_purchase_order', ['kurs_terima_invoice_progress' => $kurs], ['no_surat' => $no_po]);
			}


			$insert_expense = $this->db->insert('tr_expense', [
				'no_doc' => $no_invoice,
				'tgl_doc' => $post['invoice_date'],
				'nama' => $get_users['nm_lengkap'],
				'approval' => $get_users['nm_lengkap'],
				'status' => 1,
				'created_by' => $get_users['nm_lengkap'],
				'created_on' => date('Y-m-d H:i:s'),
				'approved_by' => $get_users['nm_lengkap'],
				'approved_on' => date('Y-m-d H:i:s'),
				'jumlah' => $this->clean_number($post['req_payment_po']),
				'informasi' => 'Pembayaran PO : ' . $no_po . ' (' . implode(', ', $arr_nm_supplier) . ')',
				'bank_id' => $post['bank'],
				'accnumber' => $post['no_bank'],
				'accname' => $post['nm_acc_bank'],
				'id_po' => $post['no_po'],
				'exp_inv_po' => 1
			]);
			if (!$insert_expense) {
				print_r($this->db->error($insert_expense));
				exit;
			}

			$insert_expense_detail = $this->db->insert('tr_expense_detail', [
				'tanggal' => $post['invoice_date'],
				'no_doc' => $no_invoice,
				'deskripsi' => 'Pembayaran PO : ' . $no_po . ' (' . implode(', ', $arr_nm_supplier) . ')',
				'qty' => 1,
				'harga' => $this->clean_number($post['req_payment_po']),
				'total_harga' => $this->clean_number($post['req_payment_po']),
				'status' => 0,
				'keterangan' => 'Pembayaran PO : ' . $no_po . ' (' . implode(', ', $arr_nm_supplier) . ')',
				'expense' => $this->clean_number($post['req_payment_po']),
				'created_by' => $get_users['nm_lengkap'],
				'created_on' => date('Y-m-d H:i:s')
			]);
			if (!$insert_expense_detail) {
				print_r($this->db->error($insert_expense_detail));
				exit;
			}
		}

		if ($post['tipe_req'] == 'dp') {
			$update_po = $this->db->update('tr_purchase_order', ['po_inv_create' => 1], ['no_surat' => $post['nomor_po']]);
			if (!$update_po) {
				print_r($this->db->error($update_po));
				exit;
			}
		} else {
			$clean_no_po = str_replace(', ', ',', $post['nomor_po']);

			$update_incoming = $this->db->where_in('kode_trans', explode(',', $clean_no_po));
			$update_incoming = $this->db->update('tr_incoming_check', ['inc_inv_create' => 1]);
			if (!$update_incoming) {
				print_r($this->db->error($update_incoming));
				exit;
			}

			$update_warehouse = $this->db->where_in('kode_trans', explode(',', $clean_no_po));
			$update_warehouse = $this->db->update('warehouse_adjustment', ['inc_inv_create' => 1]);
			if (!$update_warehouse) {
				print_r($this->db->error($update_warehouse));
				exit;
			}

			$update_invoice = $this->db->where_in('kode_trans', explode(',', $clean_no_po));
			$update_invoice = $this->db->delete('tr_check_invoice');
			if (!$update_invoice) {
				print_r($this->db->error($update_invoice));
				exit;
			}
		}

		//tambahan syam 16/07/2024
		//edit febry 08/04/2026

		$no_inc = $post['nomor_po'];


		$totalunbill = 0;
		$totalap = 0;
		$coaunbill = '';
		$coaap = '';


		if ($post['tipe_req'] == 'dp') {
			$get_supplier = $this->db->get_where('new_supplier', ['kode_supplier' => $get_po->id_suplier])->row();
			if ($post['currency'] == 'IDR') {
				$kurs  = 1;
				$jenis_jurnal = 'JV001';
			} else {
				$kurs  = $this->clean_number($post['kurs']);
				$jenis_jurnal = 'JV004';
			}

			$total_pembelian = $this->clean_number($post['total_pembelian']) * $kurs;
			$nilai_ppn = $this->clean_number($post['nilai_ppn']) * $kurs;
			$kode_supplier = $get_supplier->kode_supplier;
			$nama = $get_supplier->nama;
		} else {

			if ($post['currency'] == 'IDR') {
				$kurs  = 1;
				$jenis_jurnal = 'JV003';
			} else {
				$kurs  = $this->clean_number($post['kurs']);
				$jenis_jurnal = 'JV006';
			}

			$total_pembelian = $this->clean_number($post['total_pembelian']) * $kurs;
			$nilai_ppn = $this->clean_number($post['nilai_ppn']) * $kurs;
			$kode_supplier = implode(', ', $arr_id_suplier);
			$nama = implode(', ', $arr_nm_supplier);
		}

		// print_r($jenis_jurnal);
		// exit;

		$datajurnal1 = $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' order by parameter_no")->result();

		$nilai_incoming = $total_pembelian;

		$kode_trans = $clean_no_po;
		$data_incoming = $this->db->select('
        g.no_po,
        h.no_surat,
        SUM(b.total_harga) as total_harga
    	')
			->from('tr_incoming_check_detail a')
			->join('tr_checked_incoming_detail b', 'b.id_detail = a.id', 'left')
			->join('dt_trans_po g', 'g.id = a.id_po_detail', 'left')
			->join('tr_purchase_order h', 'h.no_po = g.no_po', 'left')
			->where('a.kode_trans', $kode_trans)
			->group_by('g.no_po')
			->get()
			->result();

		$nomor_jurnal = $jenis_jurnal . $no_po . rand(100, 999);
		$payment_date = $post['invoice_date']; //date("Y-m-d");
		$det_Jurnaltes1 = array();
		if ($post['tipe_req'] == 'dp') {
			if ($total_pembelian > 0) {
				foreach ($datajurnal1 as $rec) {
					if ($rec->parameter_no == "1") {

						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'JV',
							'no_perkiraan' => $rec->no_perkiraan,
							'keterangan' => 'PO ' . $post['nomor_po'] . ', FP:' . $post['nomor_faktur_pajak'] . ', Sup:' . $nama,
							'no_reff' => $post['nomor_invoice'],
							'debet' => $total_pembelian,
							'kredit' => 0,
							'no_request' => $post['nomor_po'],
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $kode_supplier,
							'stspos' => '1'
						);
						$totalunbill = $total_pembelian;
						$coaunbill = $rec->no_perkiraan;
					}
					if ($rec->parameter_no == "2") {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'JV',
							'no_perkiraan' => $rec->no_perkiraan,
							'keterangan' => 'PO ' . $post['nomor_po'] . ', FP:' . $post['nomor_faktur_pajak'] . ', Sup:' . $nama,
							'no_reff' => $post['nomor_invoice'],
							'debet' => 0,
							'kredit' => $total_pembelian + $nilai_ppn,
							'no_request' => $post['nomor_po'],
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $kode_supplier,
							'stspos' => '1'
						);
						$totalap = $total_pembelian + $nilai_ppn;
						$coaap = $rec->no_perkiraan;
					}
					if ($rec->parameter_no == "3") {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'JV',
							'no_perkiraan' => $rec->no_perkiraan,
							'keterangan' => 'PO ' . $post['nomor_po'] . ', FP:' . $post['nomor_faktur_pajak'] . ', Sup:' . $nama,
							'no_reff' => $post['nomor_invoice'],
							'debet' => $nilai_ppn,
							'kredit' => 0,
							'no_request' => $post['nomor_po'],
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $kode_supplier,
							'stspos' => '1'
						);
					}
				}
			}
		} else {
			if ($total_pembelian > 0) {
				foreach ($datajurnal1 as $rec) {
					if ($rec->parameter_no == "1") {

						foreach ($data_incoming as $row) {

							if ($row->total_harga <= 0) continue;

							$det_Jurnaltes1[] = array(
								'nomor'        => $nomor_jurnal,
								'tanggal'      => $payment_date,
								'tipe'         => 'JV',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan'   => 'Receive Invoice PO ' . $row->no_surat . ' dari Incoming ' . $kode_trans . ', Sup:' . $nama,
								'no_reff'      => $row->no_surat,
								'debet'        => $row->total_harga,
								'kredit'       => 0,
								'no_request'   => $row->no_po,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust'       => $kode_supplier,
								'stspos'       => '1'
							);
							$coaunbill = $rec->no_perkiraan;
							$totalunbill += $row->total_harga;
						}
					}
					if ($rec->parameter_no == "2") {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'JV',
							'no_perkiraan' => $rec->no_perkiraan,
							'keterangan' => 'Receive Invoice Incoming ' . $post['nomor_po'] . ', FP:' . $post['nomor_faktur_pajak'] . ', Sup:' . $nama,
							'no_reff' => $post['nomor_invoice'],
							'debet' => $nilai_ppn,
							'kredit' => 0,
							'no_request' => $post['nomor_po'],
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $kode_supplier,
							'stspos' => '1'
						);
					}
					if ($rec->parameter_no == "3") {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'JV',
							'no_perkiraan' => $rec->no_perkiraan,
							'keterangan' => 'Receive Invoice Incoming ' . $post['nomor_po'] . ', FP:' . $post['nomor_faktur_pajak'] . ', Sup:' . $nama,
							'no_reff' => $post['nomor_invoice'],
							'debet' => 0,
							'kredit' => $totalunbill + $nilai_ppn,
							'no_request' => $post['nomor_po'],
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $kode_supplier,
							'stspos' => '1'
						);
						$totalap = $totalunbill + $nilai_ppn;
						$coaap = $rec->no_perkiraan;
					}

					// //gadipake 
					// if ($rec->parameter_no == "4") {
					// 	$det_Jurnaltes1[] = array(
					// 		'nomor' => $nomor_jurnal,
					// 		'tanggal' => $payment_date,
					// 		'tipe' => 'JV',
					// 		'no_perkiraan' => $rec->no_perkiraan,
					// 		'keterangan' => 'PO ' . $post['nomor_po'] . ', FP:' . $post['nomor_faktur_pajak'] . ', Sup:' . $nama,
					// 		'no_reff' => $post['nomor_invoice'],
					// 		'debet' => 0,
					// 		'kredit' => $umidr,
					// 		'no_request' => $post['nomor_po'],
					// 		'jenis_jurnal' => $jenis_jurnal,
					// 		'nocust' => $kode_supplier,
					// 		'stspos' => '1'
					// 	);
					// }
					// if ($rec->parameter_no == "5") {
					// 	$det_Jurnaltes1[] = array(
					// 		'nomor' => $nomor_jurnal,
					// 		'tanggal' => $payment_date,
					// 		'tipe' => 'JV',
					// 		'no_perkiraan' => $rec->no_perkiraan,
					// 		'keterangan' => 'PO ' . $post['nomor_po'] . ', FP:' . $post['nomor_faktur_pajak'] . ', Sup:' . $nama,
					// 		'no_reff' => $post['nomor_invoice'],
					// 		'debet' => $selisihdebet,
					// 		'kredit' => $selisihkredit,
					// 		'no_request' => $post['nomor_po'],
					// 		'jenis_jurnal' => $jenis_jurnal,
					// 		'nocust' => $kode_supplier,
					// 		'stspos' => '1'
					// 	);
					// }
				}
			}
		}
		$insert_jurnaltras = $this->db->insert_batch('jurnaltras', $det_Jurnaltes1);
		if (!$insert_jurnaltras) {
			print_r($this->db->error($insert_jurnaltras));
			exit;
		}

		//auto jurnal

		$tanggal = $post['invoice_date'];
		$Bln	= substr($tanggal, 5, 2);
		$Thn	= substr($tanggal, 0, 4);
		$total	= 0;
		$Nomor_JV = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', $tanggal);
		foreach ($det_Jurnaltes1 as $vals) {
			$datadetail = array(
				'tipe'			=> 'JV',
				'nomor'			=> $Nomor_JV,
				'tanggal'		=> $tanggal,
				'no_perkiraan'	=> $vals['no_perkiraan'],
				'keterangan'	=> $vals['keterangan'],
				'no_reff'		=> $vals['no_reff'],
				'debet'			=> $vals['debet'],
				'kredit'		=> $vals['kredit'],
				'created_by'   	=> $this->auth->user_id(),
				'created_on'   	=> date('Y-m-d H:i:s')
			);
			$total = ($total + $vals['debet']);
			$insert_jurnal = $this->db->insert(DBACC . '.jurnal', $datadetail);
			if (!$insert_jurnal) {
				print_r($this->db->error($insert_jurnal));
				exit;
			}
		}
		$keterangan		= 'Receive Invoice ' . $no_invoice;
		$dataJVhead = array(
			'nomor' 	    	=> $Nomor_JV,
			'tgl'	         	=> $tanggal,
			'jml'	            => $total,
			'bulan'	            => $Bln,
			'tahun'	            => $Thn,
			'kdcab'				=> '101',
			'jenis'			    => 'JV',
			'keterangan'		=> $keterangan,
			'user_id'			=> $this->auth->user_id(),
			'ho_valid'			=> '',
		);
		$insert_javh = $this->db->insert(DBACC . '.javh', $dataJVhead);
		if (!$insert_javh) {
			print_r($this->db->error($insert_javh));
			exit;
		}

		//KARTU HUTANG
		//A.UNBILL PO
		foreach ($data_incoming as $row) {
			if ($row->total_harga <= 0) continue;
			$dataunbill = array(
				'tipe'       	 => 'JV',
				'nomor'       	 => $Nomor_JV,
				'tanggal'        => $tanggal,
				'no_perkiraan'   => $coaunbill,
				'keterangan'     => 'Unbil PO ' . $row->no_surat . '; ' . $keterangan . ' dari Incoming ' . $kode_trans,
				'no_reff'     	 => $row->no_surat,
				'debet'          => $row->total_harga,
				'kredit'      	 => 0,
				'id_supplier'    => $kode_supplier,
				'nama_supplier'  => $nama,
				'no_request'     => $post['nomor_invoice'],
			);
			$insert_kartu_hutang = $this->db->insert('tr_kartu_hutang', $dataunbill);
			if (!$insert_kartu_hutang) {
				print_r($this->db->error($insert_kartu_hutang));
				exit;
			}
		}
		//B. TOTAL HUTANG
		$datahutang = array(
			'tipe'       	 => 'JV',
			'nomor'       	 => $Nomor_JV,
			'tanggal'        => $tanggal,
			'no_perkiraan'   => $coaap,
			'keterangan'     => $keterangan,
			'no_reff'     	 => $kode_trans,
			'debet'          => 0,
			'kredit'      	 => $totalap,
			'id_supplier'    => $kode_supplier,
			'nama_supplier'  => $nama,
			'no_request'     => $post['nomor_invoice'],
		);
		$insert_kartu_hutang = $this->db->insert('tr_kartu_hutang', $datahutang);
		if (!$insert_kartu_hutang) {
			print_r($this->db->error($insert_kartu_hutang));
			exit;
		}
		//end auto jurnal

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			$valid = 0;
		} else {
			$this->db->trans_commit();
			$valid = 1;
		}

		echo json_encode([
			'status' => $valid
		]);
	}

	public function search_inc()
	{
		$kode_supplier = $this->input->post('kode_supplier');

		$get_supplier = $this->db->get_where('new_supplier', ['kode_supplier' => $kode_supplier])->row();

		$this->db->select('a.*');
		$this->db->from('tr_invoice_po a');
		$this->db->like('a.no_po', 'TR');
		$get_list_inc = $this->db->get()->result_array();

		$hasil = '
			<table class="table table-bordered table_req_pay_inc">
            <thead class="bg-blue">
                <tr>
                    <th class="text-center">No.</th>
                    <th class="text-center">No. Invoice</th>
                    <th class="text-center">Tanggal Invoice</th>
                    <th class="text-center">Supplier</th>
                    <th class="text-center">Nilai Retur</th>
                    <th class="text-center">Status</th>
					<th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
			';

		$no_po = [];
		foreach ($get_list_inc as $item) {
			$get_no_po = $this->db->query("SELECT a.no_surat FROM tr_purchase_order a WHERE a.no_po IN ('" . str_replace(",", "','", $item['no_ipp']) . "')")->result();
			if (!empty($get_no_po)) {
				$list_no_po = [];
				foreach ($get_no_po as $item_no_po) {
					$list_no_po[] = $item_no_po->no_surat;
				}

				if (!empty($list_no_po)) {
					$list_no_po = implode(', ', $list_no_po);

					$no_po[$item['kode_trans']] = $list_no_po;
				} else {
					$no_po[$item['kode_trans']] = '';
				}
			} else {
				$no_po[$item['kode_trans']] = '';
			}
		}

		$total_invoice = [];
		foreach ($get_list_inc as $item) {
			$get_total_invoice = $this->db->select('total_invoice')->get_where('tr_invoice_po', ['no_po' => $item['kode_trans']])->row();
			if (!empty($get_total_invoice)) {
				$total_invoice[$item['kode_trans']] = $get_total_invoice->total_invoice;
			} else {
				$total_invoice[$item['kode_trans']] = 0;
			}
		}

		$no = 1;
		foreach ($get_list_inc as $item) {

			$exp_no_po = explode(',', $item['no_po']);

			$nm_supplier = [];

			$no_ipp = [];
			$this->db->select('a.no_ipp');
			$this->db->from('tr_incoming_check a');
			$this->db->where_in('a.kode_trans', $exp_no_po);
			$get_no_ipp = $this->db->get()->result();
			foreach ($get_no_ipp as $item_ipp) {
				$no_ipp[] = $item_ipp->no_ipp;
			}

			$this->db->select('a.no_ipp');
			$this->db->from('warehouse_adjustment a');
			$this->db->where_in('a.kode_trans', $exp_no_po);
			$get_no_ipp_ware = $this->db->get()->result();
			foreach ($get_no_ipp_ware as $item_ipp_ware) {
				$no_ipp[] = $item_ipp_ware->no_ipp;
			}
			if (count($no_ipp) > 0) {
				$no_ipp = implode(',', $no_ipp);
			} else {
				$no_ipp = '';
			}

			$this->db->select('b.nama as nm_supplier');
			$this->db->from('tr_purchase_order a');
			$this->db->join('new_supplier b', 'b.kode_supplier = a.id_suplier', 'left');
			$this->db->where_in('a.no_po', explode(',', $no_ipp));
			$this->db->group_by('b.nama');
			$get_nm_supplier = $this->db->get()->result();
			foreach ($get_nm_supplier as $item_nm_supplier) {
				$nm_supplier[] = $item_nm_supplier->nm_supplier;
			}

			if (count($nm_supplier) > 0) {
				$nm_supplier = implode(', ', $nm_supplier);
			} else {
				$nm_supplier = '';
			}

			$status = '<div class="badge bg-yellow">Waiting</div>';
			// if($id_rec_invoice !== ''){
			$get_invoice_payment = $this->db->get_where('payment_approve', ['no_doc' => $item['id'], 'status' => 2])->result();
			if (count($get_invoice_payment) > 0) {
				$complete = 1;
				$status = '<div class="badge bg-green">Complete</div>';
			}
			// }

			$view = '<button type="button" class="btn btn-sm btn-info view_inc" data-id="' . $item['id'] . '"><i class="fa fa-eye"></i></button>';
			if ($kode_supplier !== '') {
				if (strpos($nm_supplier, $get_supplier->nama) !== false) {
					$hasil .= '<tr>';
					$hasil .= '<td style="text-align: center;">' . $no . '</td>';
					$hasil .= '<td style="text-align: center;">' . $item['id'] . '</td>';
					$hasil .= '<td style="text-align: center;">' . date('d F Y', strtotime($item['invoice_date'])) . '</td>';
					$hasil .= '<td>' . $nm_supplier . '</td>';
					$hasil .= '<td style="text-align: right;">' . number_format((isset($item['nilai_retur']) ? $item['nilai_retur'] : 0), 2) . '</td>';
					$hasil .= '<td style="text-align: center;">' . $status . '</td>';
					$hasil .= '<td style="text-align: center;">' . $view . '</td>';
					$hasil .= '</tr>';
					$no++;
				}
			} else {
				$hasil .= '<tr>';
				$hasil .= '<td style="text-align: center;">' . $no . '</td>';
				$hasil .= '<td style="text-align: center;">' . $item['id'] . '</td>';
				$hasil .= '<td style="text-align: center;">' . date('d F Y', strtotime($item['invoice_date'])) . '</td>';
				$hasil .= '<td>' . $nm_supplier . '</td>';
				$hasil .= '<td style="text-align: right;">' . number_format((isset($item['nilai_retur']) ? $item['nilai_retur'] : 0), 2) . '</td>';
				$hasil .= '<td style="text-align: center;">' . $status . '</td>';
				$hasil .= '<td style="text-align: center;">' . $view . '</td>';
				$hasil .= '</tr>';
				$no++;
			}
		}
		$hasil .= '
            </tbody>
        </table>
		';

		echo $hasil;
	}

	public function search_dp()
	{
		$kode_supplier = $this->input->post('kode_supplier');

		$this->db->select('a.*, b.nm_lengkap, c.nama as nm_supplier, IF(SUM(d.persen_dp) IS NULL, 0, SUM(d.persen_dp)) as ttl_persen_dp, e.id as id_top, e.progress, e.nilai as nilai_top, e.keterangan as keterangan_top');
		$this->db->from('tr_purchase_order a');
		$this->db->join('users b', 'b.id_user = a.created_by', 'left');
		$this->db->join('new_supplier c', 'c.kode_supplier = a.id_suplier', 'left');
		$this->db->join('tr_top_po e', 'e.no_po = a.no_po');
		$this->db->join('tr_invoice_po d', 'd.no_po = a.no_surat AND d.id_top = e.id', 'left');
		$this->db->where('e.group_top', 76);
		$this->db->where('a.status', '2');
		$this->db->order_by('a.created_on', 'desc');
		if ($kode_supplier !== '') {
			$this->db->where('a.id_suplier', $kode_supplier);
		}
		$this->db->group_by('e.id');
		$get_list_po = $this->db->get()->result_array();

		$hasil = '
			<table class="table table-bordered table_req_pay_dp">
            <thead class="bg-green">
                <tr>
					<th class="text-center">No</th>
					<th class="text-center">No. PO</th>
					<th class="text-center">No. Purchase Invoice</th>
					<th class="text-center">No. Invoice</th>
					<th class="text-center">Nama Supplier</th>
					<th class="text-center">Tanggal PO</th>
					<th class="text-center">Keterangan</th>
					<th class="text-center">Created By</th>
					<th class="text-center">Status</th>
					<th class="text-center">Action</th>
				</tr>
            </thead>
            <tbody>
			';

		// $no_po = [];
		// foreach ($get_list_po as $item) {
		// 	$get_no_po = $this->db->query("SELECT a.no_surat FROM tr_purchase_order a WHERE a.no_po IN ('" . str_replace(",", "','", $item['no_ipp']) . "')")->result();
		// 	if (!empty($get_no_po)) {
		// 		$list_no_po = [];
		// 		foreach ($get_no_po as $item_no_po) {
		// 			$list_no_po[] = $item_no_po->no_surat;
		// 		}

		// 		if (!empty($list_no_po)) {
		// 			$list_no_po = implode(', ', $list_no_po);

		// 			$no_po[$item['kode_trans']] = $list_no_po;
		// 		} else {
		// 			$no_po[$item['kode_trans']] = '';
		// 		}
		// 	} else {
		// 		$no_po[$item['kode_trans']] = '';
		// 	}
		// }

		// $total_invoice = [];
		// foreach ($get_list_po as $item) {
		// 	$get_total_invoice = $this->db->select('total_invoice')->get_where('tr_invoice_po', ['no_po' => $item['kode_trans']])->row();
		// 	if (!empty($get_total_invoice)) {
		// 		$total_invoice[$item['kode_trans']] = $get_total_invoice->total_invoice;
		// 	} else {
		// 		$total_invoice[$item['kode_trans']] = 0;
		// 	}
		// }

		$no = 1;
		foreach ($get_list_po as $item) {

			$sts = '<div class="badge bg-blue">Waiting</div>';
			$close = 0;
			if ($item['ttl_persen_dp'] == $item['progress']) {
				$sts = '<div class="badge bg-green">Complete</div>';
				$close = 1;
			} else {
				if ($item['ttl_persen_dp'] > 0 && $item['ttl_persen_dp'] < 100) {
					$sts = '<div class="badge bg-yellow">Partial</div>';
				}
			}

			$get_incoming = $this->db->get_where('tr_incoming_check', ['no_ipp' => $item['no_po']])->result();
			$arr_id_incoming = [];

			foreach ($get_incoming as $item_incoming) {
				$arr_id_incoming[] = $item_incoming->kode_trans;
			}

			if (!empty($arr_id_incoming)) {
				$this->db->select('count(a.no_po) as num_po');
				$this->db->from('tr_invoice_po a');
				$this->db->where_in('a.no_po', $arr_id_incoming);
				$num_invoice = $this->db->get()->row();

				if ($num_invoice->num_po > 0) {
					$sts = '<div class="badge bg-green">Complete</div>';
					$close = 1;
				}
			}



			$view_btn = '';
			$req_pay_btn = '<button type="button" class="btn btn-sm btn-primary req_app" style="margin-left: 0.5rem" title="Request Payment" data-no_po="' . $item['no_surat'] . '" data-id_top="' . $item['id_top'] . '" data-tipe="dp"><i class="fa fa-arrow-up"></i></button>';
			if ($close == 1) {
				$get_invoice = $this->db->select('id')->get_where('tr_invoice_po', ['no_po' => $item['no_surat'], 'id_top' => $item['id_top']])->row_array();

				$view_btn = '<button type="button" class="btn btn-sm btn-info view" data-id="' . $get_invoice['id'] . '" data-id_top="' . $get_invoice['id_top'] . '" data-tipe="dp" title="view"><i class="fa fa-eye"></i></button>';
				$req_pay_btn = '';
			}

			$list_dp_btn = '';
			// if($item['ttl_persen_dp'] > 0) {
			//     $list_dp_btn = '<button type="button" class="btn btn-sm btn-warning list_dp" data-no_po="'.$item['no_po'].'" style="margin-left: 0.5rem"><i class="fa fa-list"></i></button>';
			// }

			$no_purchase_invoice = [];
			$no_invoice = [];

			$get_invoice = $this->db->select('a.*')
				->from('tr_invoice_po a')
				->where('a.id_top', $item['id_top'])
				->like('a.no_po', $item['no_surat'])
				->get()
				->result();

			foreach ($get_invoice as $item_invoice) {
				$no_purchase_invoice[] = str_replace(',', '', $item_invoice->id);
				$no_invoice[] = str_replace(',', '', $item_invoice->invoice_no);
			}

			if (!empty($no_purchase_invoice)) {
				$no_purchase_invoice = implode(', ', $no_purchase_invoice);
			} else {
				$no_purchase_invoice = '';
			}

			if (!empty($no_invoice)) {
				$no_invoice = implode(', ', $no_invoice);
			} else {
				$no_invoice = '';
			}

			$hasil .= '<tr>';
			$hasil .= '<td class="text-center">' . $no . '</td>';
			$hasil .= '<td class="text-center">' . $item['no_surat'] . '</td>';
			$hasil .= '<td class="text-center">' . $no_purchase_invoice . '</td>';
			$hasil .= '<td class="text-center">' . $no_invoice . '</td>';
			$hasil .= '<td class="text-center">' . $item['nm_supplier'] . '</td>';
			$hasil .= '<td class="text-center">' . date('d F Y', strtotime($item['tanggal'])) . '</td>';
			$hasil .= '<td class="text-center">' . $item['keterangan_top'] . '</td>';
			$hasil .= '<td class="text-center">' . $item['nm_lengkap'] . '</td>';
			$hasil .= '<td class="text-center">' . $sts . '</td>';
			$hasil .= '<td style="text-align: center;">' . $view_btn . $req_pay_btn . $list_dp_btn . '</td>';
			$hasil .= '</tr>';

			$no++;
		}
		$hasil .= '
            </tbody>
        </table>
		';

		echo $hasil;
	}

	public function search_pro()
	{
		$kode_supplier = $this->input->post('kode_supplier');

		$this->db->select('a.*, b.nm_lengkap, c.nama as nm_supplier, IF(SUM(d.persen_dp) IS NULL, 0, SUM(d.persen_dp)) as ttl_persen_dp, e.id as id_top, e.progress, e.nilai as nilai_top, e.keterangan as keterangan_top');
		$this->db->from('tr_purchase_order a');
		$this->db->join('users b', 'b.id_user = a.created_by', 'left');
		$this->db->join('new_supplier c', 'c.kode_supplier = a.id_suplier', 'left');
		$this->db->join('tr_top_po e', 'e.no_po = a.no_po');
		$this->db->join('tr_invoice_po d', 'd.no_po = a.no_surat AND d.id_top = e.id', 'left');
		$this->db->where('e.group_top', 77);
		$this->db->where('a.status', '2');
		$this->db->order_by('a.created_on', 'desc');
		if ($kode_supplier !== '') {
			$this->db->where('a.id_suplier', $kode_supplier);
		}
		$this->db->group_by('e.id');
		$get_list_po = $this->db->get()->result_array();

		$hasil = '
			<table class="table table-bordered table_req_pay_pro">
            <thead class="bg-yellow">
                <tr>
					<th class="text-center">No</th>
					<th class="text-center">No. PO</th>
					<th class="text-center">No. Purchase Invoice</th>
					<th class="text-center">No. Invoice</th>
					<th class="text-center">Nama Supplier</th>
					<th class="text-center">Tanggal PO</th>
					<th class="text-center">Keterangan</th>
					<th class="text-center">Created By</th>
					<th class="text-center">Status</th>
					<th class="text-center">Action</th>
				</tr>
            </thead>
            <tbody>
			';

		// $no_po = [];
		// foreach ($get_list_po as $item) {
		// 	$get_no_po = $this->db->query("SELECT a.no_surat FROM tr_purchase_order a WHERE a.no_po IN ('" . str_replace(",", "','", $item['no_ipp']) . "')")->result();
		// 	if (!empty($get_no_po)) {
		// 		$list_no_po = [];
		// 		foreach ($get_no_po as $item_no_po) {
		// 			$list_no_po[] = $item_no_po->no_surat;
		// 		}

		// 		if (!empty($list_no_po)) {
		// 			$list_no_po = implode(', ', $list_no_po);

		// 			$no_po[$item['kode_trans']] = $list_no_po;
		// 		} else {
		// 			$no_po[$item['kode_trans']] = '';
		// 		}
		// 	} else {
		// 		$no_po[$item['kode_trans']] = '';
		// 	}
		// }

		// $total_invoice = [];
		// foreach ($get_list_po as $item) {
		// 	$get_total_invoice = $this->db->select('total_invoice')->get_where('tr_invoice_po', ['no_po' => $item['kode_trans']])->row();
		// 	if (!empty($get_total_invoice)) {
		// 		$total_invoice[$item['kode_trans']] = $get_total_invoice->total_invoice;
		// 	} else {
		// 		$total_invoice[$item['kode_trans']] = 0;
		// 	}
		// }

		$no = 1;
		foreach ($get_list_po as $item) {

			$sts = '<div class="badge bg-blue">Waiting</div>';
			$close = 0;
			if ($item['ttl_persen_dp'] == $item['progress']) {
				$sts = '<div class="badge bg-green">Complete</div>';
				$close = 1;
			} else {
				if ($item['ttl_persen_dp'] > 0 && $item['ttl_persen_dp'] < 100) {
					$sts = '<div class="badge bg-yellow">Partial</div>';
				}
			}

			$get_incoming = $this->db->get_where('tr_incoming_check', ['no_ipp' => $item['no_po']])->result();
			$arr_id_incoming = [];

			foreach ($get_incoming as $item_incoming) {
				$arr_id_incoming[] = $item_incoming->kode_trans;
			}

			if (!empty($arr_id_incoming)) {
				$this->db->select('count(a.no_po) as num_po');
				$this->db->from('tr_invoice_po a');
				$this->db->where_in('a.no_po', $arr_id_incoming);
				$num_invoice = $this->db->get()->row();

				if ($num_invoice->num_po > 0) {
					$sts = '<div class="badge bg-green">Complete</div>';
					$close = 1;
				}
			}



			$view_btn = '';
			$req_pay_btn = '<button type="button" class="btn btn-sm btn-primary req_app" style="margin-left: 0.5rem" title="Request Payment" data-no_po="' . $item['no_surat'] . '" data-id_top="' . $item['id_top'] . '" data-tipe="dp"><i class="fa fa-arrow-up"></i></button>';
			if ($close == 1) {
				$get_invoice = $this->db->select('id')->get_where('tr_invoice_po', ['no_po' => $item['no_surat'], 'id_top' => $item['id_top']])->row_array();

				$view_btn = '<button type="button" class="btn btn-sm btn-info view" data-id="' . $get_invoice['id'] . '" data-id_top="' . $get_invoice['id_top'] . '" data-tipe="dp" title="view"><i class="fa fa-eye"></i></button>';
				$req_pay_btn = '';
			}

			$list_dp_btn = '';
			// if($item['ttl_persen_dp'] > 0) {
			//     $list_dp_btn = '<button type="button" class="btn btn-sm btn-warning list_dp" data-no_po="'.$item['no_po'].'" style="margin-left: 0.5rem"><i class="fa fa-list"></i></button>';
			// }
			$no_purchase_invoice = [];
			$no_invoice = [];

			$get_invoice = $this->db->select('a.*')
				->from('tr_invoice_po a')
				->where('a.id_top', $item['id_top'])
				->like('a.no_po', $item['no_surat'])
				->get()
				->result();

			foreach ($get_invoice as $item_invoice) {
				$no_purchase_invoice[] = str_replace(',', '', $item_invoice->id);
				$no_invoice[] = str_replace(',', '', $item_invoice->invoice_no);
			}

			if (!empty($no_purchase_invoice)) {
				$no_purchase_invoice = implode(', ', $no_purchase_invoice);
			} else {
				$no_purchase_invoice = '';
			}

			if (!empty($no_invoice)) {
				$no_invoice = implode(', ', $no_invoice);
			} else {
				$no_invoice = '';
			}

			$hasil .= '<tr>';
			$hasil .= '<td class="text-center">' . $no . '</td>';
			$hasil .= '<td class="text-center">' . $item['no_surat'] . '</td>';
			$hasil .= '<td class="text-center">' . $no_purchase_invoice . '</td>';
			$hasil .= '<td class="text-center">' . $no_invoice . '</td>';
			$hasil .= '<td class="text-center">' . $item['nm_supplier'] . '</td>';
			$hasil .= '<td class="text-center">' . date('d F Y', strtotime($item['tanggal'])) . '</td>';
			$hasil .= '<td class="text-center">' . $item['keterangan_top'] . '</td>';
			$hasil .= '<td class="text-center">' . $item['nm_lengkap'] . '</td>';
			$hasil .= '<td class="text-center">' . $sts . '</td>';
			$hasil .= '<td style="text-align: center;">' . $view_btn . $req_pay_btn . $list_dp_btn . '</td>';
			$hasil .= '</tr>';

			$no++;
		}
		$hasil .= '
            </tbody>
        </table>
		';

		echo $hasil;
	}

	public function search_ret()
	{
		$kode_supplier = $this->input->post('kode_supplier');

		$this->db->select('a.*, b.nm_lengkap, c.nama as nm_supplier, IF(SUM(d.persen_dp) IS NULL, 0, SUM(d.persen_dp)) as ttl_persen_dp, e.id as id_top, e.progress, e.nilai as nilai_top, e.keterangan as keterangan_top');
		$this->db->from('tr_purchase_order a');
		$this->db->join('users b', 'b.id_user = a.created_by', 'left');
		$this->db->join('new_supplier c', 'c.kode_supplier = a.id_suplier', 'left');
		$this->db->join('tr_top_po e', 'e.no_po = a.no_po');
		$this->db->join('tr_invoice_po d', 'd.no_po = a.no_surat AND d.id_top = e.id', 'left');
		$this->db->where('e.group_top', 78);
		$this->db->where('a.status', '2');
		$this->db->order_by('a.created_on', 'desc');
		if ($kode_supplier !== '') {
			$this->db->where('a.id_suplier', $kode_supplier);
		}
		$this->db->group_by('e.id');
		$get_list_po = $this->db->get()->result_array();

		$hasil = '
			<table class="table table-bordered table_req_pay_ret">
            <thead class="bg-red">
                <tr>
					<th class="text-center">No</th>
					<th class="text-center">No. PO</th>
					<th class="text-center">No. Purchase Invoice</th>
					<th class="text-center">No. Invoice</th>
					<th class="text-center">Nama Supplier</th>
					<th class="text-center">Tanggal PO</th>
					<th class="text-center">Keterangan</th>
					<th class="text-center">Created By</th>
					<th class="text-center">Status</th>
					<th class="text-center">Action</th>
				</tr>
            </thead>
            <tbody>
			';

		// $no_po = [];
		// foreach ($get_list_po as $item) {
		// 	$get_no_po = $this->db->query("SELECT a.no_surat FROM tr_purchase_order a WHERE a.no_po IN ('" . str_replace(",", "','", $item['no_ipp']) . "')")->result();
		// 	if (!empty($get_no_po)) {
		// 		$list_no_po = [];
		// 		foreach ($get_no_po as $item_no_po) {
		// 			$list_no_po[] = $item_no_po->no_surat;
		// 		}

		// 		if (!empty($list_no_po)) {
		// 			$list_no_po = implode(', ', $list_no_po);

		// 			$no_po[$item['kode_trans']] = $list_no_po;
		// 		} else {
		// 			$no_po[$item['kode_trans']] = '';
		// 		}
		// 	} else {
		// 		$no_po[$item['kode_trans']] = '';
		// 	}
		// }

		// $total_invoice = [];
		// foreach ($get_list_po as $item) {
		// 	$get_total_invoice = $this->db->select('total_invoice')->get_where('tr_invoice_po', ['no_po' => $item['kode_trans']])->row();
		// 	if (!empty($get_total_invoice)) {
		// 		$total_invoice[$item['kode_trans']] = $get_total_invoice->total_invoice;
		// 	} else {
		// 		$total_invoice[$item['kode_trans']] = 0;
		// 	}
		// }

		$no = 1;
		foreach ($get_list_po as $item) {

			$sts = '<div class="badge bg-blue">Waiting</div>';
			$close = 0;
			if ($item['ttl_persen_dp'] == $item['progress']) {
				$sts = '<div class="badge bg-green">Complete</div>';
				$close = 1;
			} else {
				if ($item['ttl_persen_dp'] > 0 && $item['ttl_persen_dp'] < 100) {
					$sts = '<div class="badge bg-yellow">Partial</div>';
				}
			}

			$get_incoming = $this->db->get_where('tr_incoming_check', ['no_ipp' => $item['no_po']])->result();
			$arr_id_incoming = [];

			foreach ($get_incoming as $item_incoming) {
				$arr_id_incoming[] = $item_incoming->kode_trans;
			}

			if (!empty($arr_id_incoming)) {
				$this->db->select('count(a.no_po) as num_po');
				$this->db->from('tr_invoice_po a');
				$this->db->where_in('a.no_po', $arr_id_incoming);
				$num_invoice = $this->db->get()->row();

				if ($num_invoice->num_po > 0) {
					$sts = '<div class="badge bg-green">Complete</div>';
					$close = 1;
				}
			}



			$view_btn = '';
			$req_pay_btn = '<button type="button" class="btn btn-sm btn-primary req_app" style="margin-left: 0.5rem" title="Request Payment" data-no_po="' . $item['no_surat'] . '" data-id_top="' . $item['id_top'] . '" data-tipe="dp"><i class="fa fa-arrow-up"></i></button>';
			if ($close == 1) {
				$get_invoice = $this->db->select('id')->get_where('tr_invoice_po', ['no_po' => $item['no_surat'], 'id_top' => $item['id_top']])->row_array();

				$view_btn = '<button type="button" class="btn btn-sm btn-info view" data-id="' . $get_invoice['id'] . '" data-id_top="' . $get_invoice['id_top'] . '" data-tipe="dp" title="view"><i class="fa fa-eye"></i></button>';
				$req_pay_btn = '';
			}

			$list_dp_btn = '';
			// if($item['ttl_persen_dp'] > 0) {
			//     $list_dp_btn = '<button type="button" class="btn btn-sm btn-warning list_dp" data-no_po="'.$item['no_po'].'" style="margin-left: 0.5rem"><i class="fa fa-list"></i></button>';
			// }
			$no_purchase_invoice = [];
			$no_invoice = [];

			$get_invoice = $this->db->select('a.*')
				->from('tr_invoice_po a')
				->where('a.id_top', $item['id_top'])
				->like('a.no_po', $item['no_surat'])
				->get()
				->result();

			foreach ($get_invoice as $item_invoice) {
				$no_purchase_invoice[] = str_replace(',', '', $item_invoice->id);
				$no_invoice[] = str_replace(',', '', $item_invoice->invoice_no);
			}

			if (!empty($no_purchase_invoice)) {
				$no_purchase_invoice = implode(', ', $no_purchase_invoice);
			} else {
				$no_purchase_invoice = '';
			}

			if (!empty($no_invoice)) {
				$no_invoice = implode(', ', $no_invoice);
			} else {
				$no_invoice = '';
			}

			$hasil .= '<tr>';
			$hasil .= '<td class="text-center">' . $no . '</td>';
			$hasil .= '<td class="text-center">' . $item['no_surat'] . '</td>';
			$hasil .= '<td class="text-center">' . $no_purchase_invoice . '</td>';
			$hasil .= '<td class="text-center">' . $no_invoice . '</td>';
			$hasil .= '<td class="text-center">' . $item['nm_supplier'] . '</td>';
			$hasil .= '<td class="text-center">' . date('d F Y', strtotime($item['tanggal'])) . '</td>';
			$hasil .= '<td class="text-center">' . $item['keterangan_top'] . '</td>';
			$hasil .= '<td class="text-center">' . $item['nm_lengkap'] . '</td>';
			$hasil .= '<td class="text-center">' . $sts . '</td>';
			$hasil .= '<td style="text-align: center;">' . $view_btn . $req_pay_btn . $list_dp_btn . '</td>';
			$hasil .= '</tr>';

			$no++;
		}
		$hasil .= '
            </tbody>
        </table>
		';

		echo $hasil;
	}

	public function check_list_inc()
	{
		$get_list_inc = $this->db->query('
				SELECT
					a.kode_trans as kode_trans,
					a.no_ipp as no_ipp,
					a.inc_inv_create as inc_inv_create,
					a.tanggal as tanggal,
					"incoming product" as tipe_incoming
				FROM
					tr_incoming_check a
				WHERE
					a.checked = "Y"
					AND a.inc_inv_create IS NULL

				UNION ALL

				SELECT 
					a.kode_trans as kode_trans,
					a.no_ipp as no_ipp,
					a.inc_inv_create as inc_inv_create,
					a.tanggal as tanggal,
					a.category as tipe_incoming
				FROM
					warehouse_adjustment a
				WHERE
					a.category = "incoming stok" OR a.category = "incoming non rutin" OR a.category = "incoming asset"

				ORDER BY tanggal DESC
			')->result_array();

		$no_po = [];
		foreach ($get_list_inc as $item) {
			$get_no_po = $this->db->query("SELECT a.no_surat FROM tr_purchase_order a WHERE a.no_po IN ('" . str_replace(",", "','", $item['no_ipp']) . "') OR a.no_surat IN ('" . str_replace(",", "','", $item['no_ipp']) . "')")->result();
			if (!empty($get_no_po)) {
				$list_no_po = [];
				foreach ($get_no_po as $item_no_po) {
					$list_no_po[] = $item_no_po->no_surat;
				}

				if (!empty($list_no_po)) {
					$list_no_po = implode(', ', $list_no_po);

					$no_po[$item['kode_trans']] = $list_no_po;
				} else {
					$no_po[$item['kode_trans']] = '';
				}
			} else {
				$no_po[$item['kode_trans']] = '';
			}
		}

		$total_invoice = [];
		foreach ($get_list_inc as $item) {
			$get_total_invoice = $this->db->select('total_invoice')->get_where('tr_invoice_po', ['no_po' => $item['kode_trans']])->row();
			if (!empty($get_total_invoice)) {
				$total_invoice[$item['kode_trans']] = $get_total_invoice->total_invoice;
			} else {
				if ($item['tipe_incoming'] == 'incoming non rutin') {
					$this->db->select('SUM(a.total_harga) as ttl_harga');
					$this->db->from('tr_pr_detail_kasbon a');
					$this->db->where('a.id_kasbon', $item['no_ipp']);
					$get_total = $this->db->get()->row();

					$total_invoice[$item['kode_trans']] = $get_total->ttl_harga;
				} else if ($item['tipe_incoming'] == 'incoming asset') {
					$this->db->select('SUM(a.harga_total) as ttl_harga');
					$this->db->from('dt_trans_po a');
					$this->db->join('tr_purchase_order b', 'b.no_po = a.no_po');
					$this->db->where('b.no_surat', $item['no_ipp']);
					$get_total = $this->db->get()->row();

					$total_invoice[$item['kode_trans']] = $get_total->ttl_harga;
				} else {
					$total_invoice[$item['kode_trans']] = 0;
				}
			}
		}

		$get_supplier = $this->db->get('new_supplier')->result();

		$this->template->set('list_inc', $get_list_inc);
		$this->template->set('no_po', $no_po);
		$this->template->set('total_invoice', $total_invoice);
		$this->template->set('list_supplier', $get_supplier);
		$this->template->render('check_list_inc');
	}

	public function check_invoice()
	{
		$kode_trans = $this->input->post('kode_trans');
		$tipe_incoming = $this->input->post('tipe_incoming');
		$tipe = $this->input->post('tipe');

		$this->db->trans_start();
		if ($tipe == 1) {
			$checked_invoice = $this->db->get_where('tr_check_invoice', ['kode_trans' => $kode_trans, 'id_user' => $this->auth->user_id()])->result();
			if (count($checked_invoice) < 1) {
				$insert_check_invoice = $this->db->insert('tr_check_invoice', [
					'kode_trans' => $kode_trans,
					'id_user' => $this->auth->user_id()
				]);
				if (!$insert_check_invoice) {
					print_r($this->db->error($insert_check_invoice));
					exit;
				}
			}
		} else {
			$this->db->delete('tr_check_invoice', ['kode_trans' => $kode_trans, 'id_user' => $this->auth->user_id()]);
		}

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			$valid = 0;
		} else {
			$this->db->trans_commit();
			$valid = 1;
		}

		echo json_encode([
			'status' => $valid
		]);
	}

	public function clear_checked_invoice()
	{
		$this->db->trans_start();

		$this->db->delete('tr_check_invoice', ['id_user' => $this->auth->user_id()]);
		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
			$valid = 0;
		} else {
			$this->db->trans_commit();
			$valid = 1;
		}

		echo json_encode([
			'status' => $valid
		]);
	}

	public function checkCheckedInv()
	{
		$get_checked_invoice = $this->db->get_where('tr_check_invoice', ['id_user' => $this->auth->user_id()])->result();

		echo count($get_checked_invoice);
	}

	public function rec_invoice_btn()
	{
		$userId = $this->auth->user_id();

		// 1. Ambil invoice yang dicek
		$get_checked_invoice = $this->db->select('kode_trans')
			->get_where('tr_check_invoice', ['id_user' => $userId])
			->result();

		$no_incoming = array_column($get_checked_invoice, 'kode_trans');
		$incoming_no = !empty($no_incoming) ? implode(', ', $no_incoming) : '';

		if (empty($no_incoming)) {
			// Handle jika data kosong agar query tidak error
			echo "No data selected";
			die();
		}

		// Helper untuk string IN clause (untuk query manual)
		$in_clause = "'" . implode("','", $no_incoming) . "'";

		// 2. Ambil data PO, PPN, dan Kurs
		$arr_no_po = [];
		$ppn_asli = 0;
		$uang_muka_idr = 0;
		$kurs_terima_barang = 1;

		$query_po = "
        SELECT b.no_surat, b.total_ppn, b.uang_muka, b.uang_muka_idr, b.kurs_terima_barang, b.matauang
        FROM dt_trans_po a
        JOIN tr_purchase_order b ON b.no_po = a.no_po
        WHERE a.id IN (SELECT aa.id_po_detail FROM tr_incoming_check_detail aa WHERE aa.kode_trans IN ($in_clause))
        GROUP BY b.no_surat
        UNION ALL
        SELECT b.no_surat, b.total_ppn, b.uang_muka, b.uang_muka_idr, b.kurs_terima_barang, b.matauang
        FROM dt_trans_po a
        JOIN tr_purchase_order b ON b.no_po = a.no_po
        WHERE a.id IN (SELECT aa.no_ipp FROM warehouse_adjustment_detail aa WHERE aa.kode_trans IN ($in_clause))
        GROUP BY b.no_surat
        UNION ALL
        SELECT a.no_pr as no_surat, 0 as total_ppn, b.uang_muka, b.uang_muka_idr, b.kurs_terima_barang, 'IDR' as matauang
        FROM rutin_non_planning_detail a
        JOIN rutin_non_planning_header b ON b.no_pengajuan = a.no_pengajuan
        WHERE a.id IN (SELECT aa.id_po_detail FROM warehouse_adjustment_detail aa WHERE aa.kode_trans IN ($in_clause))
        GROUP BY a.no_pr
        UNION ALL
        SELECT b.no_surat, b.total_ppn, b.uang_muka, b.uang_muka_idr, b.kurs_terima_barang, b.matauang
        FROM dt_trans_po a
        JOIN tr_purchase_order b ON b.no_po = a.no_po
        WHERE b.no_surat IN (SELECT aa.no_ipp FROM warehouse_adjustment_detail aa WHERE aa.kode_trans IN ($in_clause)) AND b.tipe = 'pr asset'
        GROUP BY b.no_surat
    ";

		$get_no_po = $this->db->query($query_po)->result();

		foreach ($get_no_po as $item) {
			$arr_no_po[] = $item->no_surat;
			$ppn_asli += $item->total_ppn;
			$uang_muka_idr = $item->uang_muka_idr;
			$kurs_terima_barang = ($item->matauang == 'IDR') ? 1 : $item->kurs_terima_barang;
		}

		// 3. Ambil Data Supplier
		$query_supplier = "
        SELECT c.nama, c.kode_supplier FROM dt_trans_po a 
        JOIN tr_purchase_order b ON b.no_po = a.no_po 
        LEFT JOIN new_supplier c ON c.kode_supplier = b.id_suplier
        WHERE a.id IN (SELECT aa.id_po_detail FROM tr_incoming_check_detail aa WHERE aa.kode_trans IN ($in_clause))
        GROUP BY c.nama
        UNION ALL
        SELECT c.nama, c.kode_supplier FROM dt_trans_po a 
        JOIN tr_purchase_order b ON b.no_po = a.no_po 
        LEFT JOIN new_supplier c ON c.kode_supplier = b.id_suplier
        WHERE a.id IN (SELECT aa.no_ipp FROM warehouse_adjustment_detail aa WHERE aa.kode_trans IN ($in_clause))
        GROUP BY c.nama
        UNION ALL
        SELECT c.nama, c.kode_supplier FROM dt_trans_po a 
        JOIN tr_purchase_order b ON b.no_po = a.no_po 
        LEFT JOIN new_supplier c ON c.kode_supplier = b.id_suplier
        WHERE b.no_surat IN (SELECT aa.no_ipp FROM warehouse_adjustment_detail aa WHERE aa.kode_trans IN ($in_clause))
        GROUP BY c.nama
    ";
		$res_supplier = $this->db->query($query_supplier)->result();
		$nm_supplier = implode(', ', array_unique(array_column($res_supplier, 'nama')));
		$kode_supplier = implode(',', array_unique(array_column($res_supplier, 'kode_supplier')));

		// 4. Ambil Currency
		$query_curr = "
        SELECT b.matauang FROM dt_trans_po a JOIN tr_purchase_order b ON b.no_po = a.no_po 
        WHERE a.id IN (SELECT aa.id_po_detail FROM tr_incoming_check_detail aa WHERE aa.kode_trans IN ($in_clause))
        GROUP BY b.matauang
        UNION ALL
        SELECT b.matauang FROM dt_trans_po a JOIN tr_purchase_order b ON b.no_po = a.no_po 
        WHERE a.id IN (SELECT aa.no_ipp FROM warehouse_adjustment_detail aa WHERE aa.kode_trans IN ($in_clause))
        GROUP BY b.matauang
        UNION ALL
        SELECT b.matauang FROM dt_trans_po a JOIN tr_purchase_order b ON b.no_po = a.no_po 
        WHERE b.no_surat IN (SELECT aa.no_ipp FROM warehouse_adjustment_detail aa WHERE aa.kode_trans IN ($in_clause))
        GROUP BY b.matauang
    ";
		$res_curr = $this->db->query($query_curr)->result();
		$currency = implode(', ', array_filter(array_unique(array_column($res_curr, 'matauang'))));

		// 5. Hitung DP (Ditambahkan filter ID = 76 pada tr_top_po)
		$value_dp = 0;
		$query_dp = "
        SELECT c.nilai as nilai_dp 
        FROM dt_trans_po a 
        JOIN tr_purchase_order b ON b.no_po = a.no_po 
        LEFT JOIN tr_top_po c ON c.no_po = b.no_po
        WHERE a.id IN (SELECT aa.id_po_detail FROM tr_incoming_check_detail aa WHERE aa.kode_trans IN ($in_clause)) 
          AND c.id = 76 
        GROUP BY c.id

        UNION ALL

        SELECT c.nilai as nilai_dp 
        FROM dt_trans_po a 
        JOIN tr_purchase_order b ON b.no_po = a.no_po 
        LEFT JOIN tr_top_po c ON c.no_po = b.no_po
        WHERE a.id IN (SELECT aa.no_ipp FROM warehouse_adjustment_detail aa WHERE aa.kode_trans IN ($in_clause)) 
          AND c.id = 76 
        GROUP BY c.id

        UNION ALL

        SELECT c.nilai as nilai_dp 
        FROM dt_trans_po a 
        JOIN tr_purchase_order b ON b.no_po = a.no_po 
        LEFT JOIN tr_top_po c ON c.no_po = b.no_po
        WHERE b.no_surat IN (SELECT aa.no_ipp FROM warehouse_adjustment_detail aa WHERE aa.kode_trans IN ($in_clause)) 
          AND c.id = 76 
        GROUP BY c.id
    ";

		$res_dp = $this->db->query($query_dp)->result();
		foreach ($res_dp as $dp) {
			$value_dp += $dp->nilai_dp;
		}

		// 6. Hitung Total Invoice
		// Dihitung dari qty_oke * hargasatuan agar konsisten dengan Grand Total di detail view
		$total_invoice = 0;
		$query_ttl = "
        SELECT SUM(b.qty_oke * c.hargasatuan) as subtotal
        FROM tr_incoming_check_detail a
        JOIN tr_checked_incoming_detail b ON b.id_detail = a.id
        JOIN dt_trans_po c ON c.id = a.id_po_detail
        WHERE a.kode_trans IN ($in_clause)
        UNION ALL

        SELECT (a.qty_oke * b.hargasatuan) FROM warehouse_adjustment_detail a 
        JOIN dt_trans_po b ON b.id = a.no_ipp 
        LEFT JOIN warehouse_adjustment c ON c.kode_trans = a.kode_trans
        WHERE a.kode_trans IN ($in_clause) AND c.category = 'incoming stok'
        UNION ALL

        SELECT (a.qty_oke * b.harga) FROM warehouse_adjustment_detail a 
        JOIN tr_pr_detail_kasbon b ON b.id_detail = a.id_po_detail AND b.id_kasbon = a.no_ipp 
        LEFT JOIN warehouse_adjustment c ON c.kode_trans = a.kode_trans
        WHERE a.kode_trans IN ($in_clause) AND c.category = 'incoming non rutin'
        UNION ALL

        SELECT (a.qty_oke * d.hargasatuan) FROM warehouse_adjustment_detail a 
        JOIN tr_purchase_order b ON b.no_surat = a.no_ipp 
        JOIN dt_trans_po d ON d.no_po = b.no_po AND a.nm_material = d.namamaterial 
        LEFT JOIN warehouse_adjustment c ON c.kode_trans = a.kode_trans
        WHERE a.kode_trans IN ($in_clause) AND c.category = 'incoming asset'
    ";
		$res_ttl = $this->db->query($query_ttl)->result();
		foreach ($res_ttl as $row) $total_invoice += $row->subtotal;

		// 7. Hitung Diskon
		$nilai_disc = 0;
		// Diskon Material & Stok (Disederhanakan logikanya)
		$query_disc = "
        SELECT d.qty_oke, b.qty, b.hargasatuan, b.hargasatuan_disc, b.harga_total, b.persen_disc as p_item, c.persen_disc as p_po
        FROM tr_incoming_check_detail a
        JOIN dt_trans_po b ON b.id = a.id_po_detail
        JOIN tr_purchase_order c ON c.no_po = b.no_po
        JOIN tr_checked_incoming_detail d ON d.kode_trans = a.kode_trans AND d.id_detail = a.id
        WHERE a.kode_trans IN ($in_clause)
        UNION ALL
		
        SELECT a.qty_oke, b.qty, b.hargasatuan, b.hargasatuan_disc, b.harga_total, b.persen_disc, c.persen_disc
        FROM warehouse_adjustment_detail a
        JOIN dt_trans_po b ON b.id = a.no_ipp
        JOIN tr_purchase_order c ON c.no_po = b.no_po
        JOIN tr_checked_incoming_detail d ON d.kode_trans = a.kode_trans AND d.id_material = a.id_material
        WHERE a.kode_trans IN ($in_clause)
    ";
		$res_disc = $this->db->query($query_disc)->result();
		foreach ($res_disc as $row) {
			$p = ($row->p_item > 0) ? $row->p_item : $row->p_po;
			$nilai_disc += ($row->hargasatuan * $row->qty_oke * $p / 100);
		}

		// Jika diskon masih 0, cek diskon Asset
		if ($nilai_disc <= 0) {
			$res_disc_asset = $this->db->select('a.qty_oke, c.hargasatuan, c.persen_disc as p_item, b.persen_disc as p_po')
				->from('warehouse_adjustment_detail a')
				->join('tr_purchase_order b', 'b.no_surat = a.no_ipp')
				->join('dt_trans_po c', 'c.no_po = b.no_po AND c.namamaterial = a.nm_material', 'left')
				->where_in('a.kode_trans', $no_incoming)
				->get()->result();

			foreach ($res_disc_asset as $row) {
				$p = ($row->p_item > 0) ? $row->p_item : $row->p_po;
				$nilai_disc += ($row->hargasatuan * $row->qty_oke * $p / 100);
			}
		}

		// 8. Final Calculation
		$total_invoice_final = $total_invoice * $kurs_terima_barang;
		// PPN dihitung 11% dari total invoice final
		$nilai_ppn = $total_invoice_final * 11 / 100;

		$nilai_req_payment = (($total_invoice_final + $nilai_ppn) - $uang_muka_idr);

		// 9. Ambil total sisa retur berdasarkan supplier
		$arr_supplier = array_map('trim', explode(',', $kode_supplier));

		// Cari id supplier dari tabel new_supplier berdasarkan kode_supplier
		$this->db->select('id');
		$this->db->from('new_supplier');
		$this->db->where_in('kode_supplier', $arr_supplier);
		$res_id_supplier = $this->db->get()->result();
		$arr_id_supplier = array_column($res_id_supplier, 'id');

		$total_sisa_retur = 0;
		$ids_retur = [];
		if (!empty($arr_id_supplier)) {
			$this->db->select('id, no_retur, sisa_retur');
			$this->db->from('tr_retur_pembelian');
			$this->db->where('sisa_retur >', 0);
			$this->db->where('status', 2);
			$this->db->where_in('id_supplier', $arr_id_supplier);
			$res_retur = $this->db->get()->result();

			foreach ($res_retur as $r) {
				$total_sisa_retur += $r->sisa_retur;
				$ids_retur[] = $r->id;
			}
		}

		// Total Tagihan = Total Invoice (nilai_req_payment) - Total Sisa Retur
		$total_tagihan = $nilai_req_payment - $total_sisa_retur;
		if ($total_tagihan < 0) $total_tagihan = 0;

		$data = [
			'no_incoming'       => $no_incoming,
			'incoming_no'       => $incoming_no,
			'nm_supplier'       => $nm_supplier,
			'kode_supplier'     => $kode_supplier,
			'currency'          => $currency,
			'value_dp'          => $uang_muka_idr,
			'total_incoming'    => $total_invoice,
			'total_invoice'     => $total_invoice_final,
			'nilai_disc'        => $nilai_disc,
			'nilai_ppn'         => $nilai_ppn,
			'nilai_req_payment' => $nilai_req_payment,
			'total_sisa_retur'  => $total_sisa_retur,
			'total_tagihan'     => $total_tagihan,
			'ids_retur_pembelian' => implode(',', $ids_retur),
			'no_po'             => array_unique($arr_no_po)
		];

		// Debugging (Hapus baris ini jika sudah OK)
		// echo '<pre>';
		// print_r($data);
		// echo '</pre>';
		// die();

		$this->template->set('results', $data);
		$this->template->render('add_inc');
	}
}
