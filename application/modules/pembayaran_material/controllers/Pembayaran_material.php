<?php
defined('BASEPATH') or exit('No direct script access allowed');
$data_status = array();
class Pembayaran_material extends Admin_Controller
{
	protected $viewPermission   = 'Incoming_Stok.View';
	protected $addPermission    = 'Incoming_Stok.Add';
	protected $managePermission = 'Incoming_Stok.Manage';
	protected $deletePermission = 'Incoming_Stok.Delete';

	protected $data_status;

	public function __construct()
	{
		parent::__construct();
		$this->load->library(array('upload', 'Image_lib'));
		$this->load->model(array(
			'pembayaran_material/master_model',
			'pembayaran_material/Pembayaran_material_model',
			'all/All_model',
			'pembayaran_material/Jurnal_model'
		));
		$this->data_status = array('0' => 'Pengajuan', '1' => 'Approve', '2' => 'Selesai');
	}
	//==================================================================================================================
	//==================================================REQUEST PEMBAYARAN==============================================
	//==================================================================================================================
	function index()
	{
		$data_Group			= $this->master_model->getArray('groups', array(), 'id', 'name');
		$data_list 			= $this->pembayaran_material_model->get_data_json_request_payment();
		$data_listnm 			= $this->pembayaran_material_model->get_data_json_request_payment_nm();
		$data = array(
			'title'			=> 'Indeks Of Request Payment',
			'action'		=> 'index',
			'row_group'		=> $data_Group,
			'results'		=> $data_list,
			'resultsnm'		=> $data_listnm,
			'data_status'	=> $this->data_status
		);
		history('View Request Payment');
		$this->load->view('Pembayaran_material/index_request_payment', $data);
	}


	public function request_payment_save()
	{
		$id_req = $this->input->post("id_req");
		$request_date = $this->input->post("request_date");
		$no_po = $this->input->post("no_po");
		$id_supplier = $this->input->post("id_supplier");
		$nilai_ppn = $this->input->post("nilai_ppn");
		$curs_header = $this->input->post("curs_header");
		$nilai_total = $this->input->post("nilai_total");
		$total_bayar = $this->input->post("total_bayar");
		$po_belum_dibayar = $this->input->post("po_belum_dibayar");
		$sisa_dp = $this->input->post("sisa_dp");
		$no_request = $this->input->post("no_request");
		$no_invoice = $this->input->post("no_invoice");
		$nilai_invoice = $this->input->post("nilai_invoice");
		$keterangan = $this->input->post("keterangan");
		$potongan_dp = $this->input->post("potongan_dp");
		$potongan_claim = $this->input->post("potongan_claim");
		$keterangan_potongan = $this->input->post("keterangan_potongan");
		$request_payment = $this->input->post("request_payment");
		$invoice_ppn = $this->input->post("invoice_ppn");
		$nilai_pph_invoice = $this->input->post("nilai_pph_invoice");
		$nilai_po_invoice = $this->input->post("nilai_po_invoice");
		$tipe = $this->input->post("tipe");
		$tipetrans = $this->input->post("tipetrans");
		$payfor = $this->input->post("payfor");
		$coa_pph = $this->input->post("coa_pph");
		$bank_transfer = $this->input->post("bank_transfer");

		$data_session	= $this->session->userdata;
		$Username 		= $this->session->userdata['ORI_User']['username'];
		$dateTime		= date('Y-m-d H:i:s');

		$this->db->trans_begin();
		$dataheader =  array(
			'nilai_po_invoice' => $nilai_po_invoice,
			'nilai_pph_invoice' => $nilai_pph_invoice,
			'request_payment' => $request_payment,
			'request_date' => $request_date,
			'no_invoice' => $no_invoice,
			'nilai_invoice' => $nilai_invoice,
			'keterangan' => $keterangan,
			'tipe' => $tipe,
			'invoice_ppn' => $invoice_ppn,
			'potongan_dp' => $potongan_dp,
			'potongan_claim' => $potongan_claim,
			'keterangan_potongan' => $keterangan_potongan,
			'coa_pph' => $coa_pph,
			'bank_transfer' => $bank_transfer,
			'modified_on' => date('Y-m-d H:i:s'),
			'modified_by' => $Username
		);
		if ($tipetrans == "2") {
			$this->All_model->DataUpdate('purchase_order_request_payment_nm', $dataheader, array('id' => $id_req));
			$this->All_model->DataUpdate('tran_po_detail', array('status_pay' => ''), array('status_pay' => $no_request));
			if (!empty($payfor)) {
				foreach ($payfor as $val) {
					if ($val != "") $this->All_model->DataUpdate('tran_po_detail', array('status_pay' => $no_request), array('id' => $val));
				}
			}
		} else {
			$this->All_model->DataUpdate('purchase_order_request_payment', $dataheader, array('id' => $id_req));
			$this->All_model->DataUpdate('tran_material_po_detail', array('status_pay' => ''), array('status_pay' => $no_request));
			if (!empty($payfor)) {
				foreach ($payfor as $val) {
					if ($val != "") $this->All_model->DataUpdate('tran_material_po_detail', array('status_pay' => $no_request), array('id' => $val));
				}
			}
		}
		$this->db->trans_complete();
		if ($this->db->trans_status()) {
			$this->db->trans_commit();
			$keterangan     = "SUKSES, simpan data ";
			$result         = TRUE;
		} else {
			$this->db->trans_rollback();
			$keterangan     = "GAGAL, simpan data ";
			$result = FALSE;
			history('Save Edit Request Payment, No ' . $id_req);
		}
		$param = array(
			'save' => $result
		);
		echo json_encode($param);
	}



	//==================================================================================================================
	//===============================================PEMBAYARAN=========================================================
	//==================================================================================================================
	public function payment_list()
	{
		// $results = $this->pembayaran_material_model->get_data_json_request_payment_header("status>0 and tipe='material'");
		// $results = $this->db->get_where('payment_approve', ['status' => 2])->result();
		$results = $this->db
			->select('a.*, c.invoice_no')
			->from('payment_approve a')
			->join('tr_expense b', 'b.no_doc = a.no_doc')
			->join('tr_invoice_po c', 'c.id = a.no_doc')
			->where('a.status', 2)
			->where('b.exp_inv_po', 1)
			->where('a.id_payment <>', null)
			->where('a.id_payment <>', '')
			->group_by('a.id_payment')
			->order_by('a.created_on', 'DESC')
			->get()
			->result();

		$results2 = $this->db->query("SELECT a.* FROM payment_approve a LEFT JOIN tr_expense b ON b.no_doc = a.no_doc WHERE a.status = 2 AND a.no_doc NOT LIKE '%INV-%' AND a.no_doc NOT LIKE '%PI-%' AND (a.id_payment IS NOT NULL AND a.id_payment <> '') GROUP BY a.id_payment ORDER BY a.created_on DESC")->result();
		// ->select('a.*')
		// ->from('payment_approve a')
		// ->join('tr_expense b', 'b.no_doc = a.no_doc', 'left')
		// ->where('a.status', 2)
		// ->where('(SELECT COUNT(aa.id) FROM tr_expense aa WHERE aa.no_doc = a.no_doc AND (aa.exp_inv_po IS NULL OR aa.exp_inv_po = "")) <=', 0)
		// ->group_by('a.id')
		// ->order_by('a.created_on', 'DESC')
		// ->get()
		// ->result();


		// $results2 = $this->pembayaran_material_model->get_data_json_request_payment_header("status>0 and tipe='nonmaterial'");
		// $data_Group			= $this->master_model->getArray('groups', array(), 'id', 'name');
		$data = array(
			'title'			=> 'Payment List',
			'action'		=> 'index',
			'data_status'	=> $this->data_status,
			'results'		=> $results,
			'results2' => $results2
		);
		// history('List Payment');
		$this->template->set($data);
		$this->template->render('index_payment_new');
	}

	public function cancel_payment()
	{
		// Hanya admin yang bisa membatalkan payment
		if (!$this->auth->is_admin()) {
			echo json_encode(['status' => false, 'message' => 'Anda tidak memiliki akses untuk membatalkan payment.']);
			return;
		}

		$id_payment = $this->input->post('id_payment');

		if (empty($id_payment)) {
			echo json_encode(['status' => false, 'message' => 'ID Payment tidak valid.']);
			return;
		}

		$Username = $this->auth->user_name();
		if (empty($Username)) {
			$Username = 'admin';
		}

		// Cek apakah payment exists
		$payment_header = $this->db->get_where('tr_payment_paid', ['id' => $id_payment])->row();
		if (!$payment_header) {
			echo json_encode(['status' => false, 'message' => 'Data payment tidak ditemukan.']);
			return;
		}

		// ============================================================
		// STEP A: Reverse jurnal ke DBACC (accounting DB) - DILAKUKAN DULUAN
		// ============================================================
		$dbacc_reversed = false;

		// Cari di DBACC.japh dulu
		$japh_entry = $this->db->query("SELECT * FROM " . DBACC . ".japh WHERE no_reff = '" . $this->db->escape_str($id_payment) . "' LIMIT 1")->row();

		if ($japh_entry) {
			$Nomor_JV_Rev = $this->Jurnal_model->get_no_buk('101');
			$total_rev = 0;

			$jurnal_acc_entries = $this->db->query("SELECT * FROM " . DBACC . ".jurnal WHERE nomor = '" . $this->db->escape_str($japh_entry->nomor) . "'")->result();

			foreach ($jurnal_acc_entries as $jacc) {
				$this->db->insert(DBACC . '.jurnal', [
					'tipe'              => 'BUK',
					'nomor'             => $Nomor_JV_Rev,
					'tanggal'           => date('Y-m-d'),
					'no_perkiraan'      => $jacc->no_perkiraan,
					'keterangan'        => 'BATAL - ' . $jacc->keterangan,
					'no_reff'           => $jacc->no_reff,
					'debet'             => $jacc->kredit,
					'kredit'            => $jacc->debet,
					'nilai_valas_debet' => isset($jacc->nilai_valas_kredit) ? $jacc->nilai_valas_kredit : 0,
					'nilai_valas_kredit' => isset($jacc->nilai_valas_debet) ? $jacc->nilai_valas_debet : 0,
				]);
				$total_rev += $jacc->kredit;
			}

			$this->db->insert(DBACC . '.japh', [
				'nomor'         => $Nomor_JV_Rev,
				'tgl'           => date('Y-m-d'),
				'jml'           => $total_rev,
				'jenis_ap'      => 'V',
				'bayar_kepada'  => $japh_entry->bayar_kepada,
				'kdcab'         => '101',
				'jenis_reff'    => 'BUK',
				'no_reff'       => $id_payment,
				'note'          => 'BATAL - ' . $japh_entry->note,
				'user_id'       => $Username,
				'ho_valid'      => '',
			]);
			$this->db->query("UPDATE " . DBACC . ".pastibisa_tb_cabang SET nobuk=nobuk + 1 WHERE nocab='101'");
			$dbacc_reversed = true;
		}

		// Jika japh tidak ditemukan, cari langsung dari DBACC.jurnal berdasarkan no_reff
		if (!$dbacc_reversed) {
			$jurnal_acc_entries = $this->db->query("SELECT * FROM " . DBACC . ".jurnal WHERE no_reff = '" . $this->db->escape_str($id_payment) . "'")->result();

			if (!empty($jurnal_acc_entries)) {
				$Nomor_JV_Rev = $this->Jurnal_model->get_no_buk('101');
				$total_rev = 0;

				foreach ($jurnal_acc_entries as $jacc) {
					$this->db->insert(DBACC . '.jurnal', [
						'tipe'              => 'BUK',
						'nomor'             => $Nomor_JV_Rev,
						'tanggal'           => date('Y-m-d'),
						'no_perkiraan'      => $jacc->no_perkiraan,
						'keterangan'        => 'BATAL - ' . $jacc->keterangan,
						'no_reff'           => $jacc->no_reff,
						'debet'             => $jacc->kredit,
						'kredit'            => $jacc->debet,
						'nilai_valas_debet' => isset($jacc->nilai_valas_kredit) ? $jacc->nilai_valas_kredit : 0,
						'nilai_valas_kredit' => isset($jacc->nilai_valas_debet) ? $jacc->nilai_valas_debet : 0,
					]);
					$total_rev += $jacc->kredit;
				}

				$nm_supplier = isset($payment_header->nm_supplier) ? $payment_header->nm_supplier : '';
				$this->db->insert(DBACC . '.japh', [
					'nomor'         => $Nomor_JV_Rev,
					'tgl'           => date('Y-m-d'),
					'jml'           => $total_rev,
					'jenis_ap'      => 'V',
					'bayar_kepada'  => $nm_supplier,
					'kdcab'         => '101',
					'jenis_reff'    => 'BUK',
					'no_reff'       => $id_payment,
					'note'          => 'BATAL Pembayaran ' . $id_payment,
					'user_id'       => $Username,
					'ho_valid'      => '',
				]);
				$this->db->query("UPDATE " . DBACC . ".pastibisa_tb_cabang SET nobuk=nobuk + 1 WHERE nocab='101'");
				$dbacc_reversed = true;
			}
		}

		// ============================================================
		// STEP B: Reverse jurnal lokal dan update status (dalam transaksi)
		// ============================================================
		$this->db->trans_begin();

		// 1) Reverse tr_jurnal
		$jurnal_entries = $this->db->get_where('tr_jurnal', [
			'no_transaksi' => $id_payment,
			'jenis_transaksi' => 'Payment'
		])->result();

		if (!empty($jurnal_entries)) {
			$arr_jurnal_balik = [];
			$no_jurnal = 1;
			foreach ($jurnal_entries as $jrn) {
				$id_jurnal = $this->Pembayaran_material_model->generate_id_invoice_jurnal($no_jurnal);
				$arr_jurnal_balik[] = [
					'no_jurnal'       => $id_jurnal,
					'tgl_jurnal'      => date('Y-m-d'),
					'tipe'            => $jrn->tipe,
					'coa'             => $jrn->coa,
					'nm_coa'          => $jrn->nm_coa,
					'debit'           => $jrn->kredit,
					'kredit'          => $jrn->debit,
					'keterangan'      => 'BATAL - ' . $jrn->keterangan,
					'no_transaksi'    => $id_payment,
					'jenis_transaksi' => 'Payment Batal',
					'created_by'      => $this->auth->user_id(),
					'created_date'    => date('Y-m-d'),
				];
				$no_jurnal++;
			}
			$this->db->insert_batch('tr_jurnal', $arr_jurnal_balik);
		}

		// 2) Reverse jurnaltras
		$jurnaltras_entries = $this->db->get_where('jurnaltras', [
			'no_reff' => $id_payment
		])->result();

		if (!empty($jurnaltras_entries)) {
			$arr_jurnaltras_balik = [];
			foreach ($jurnaltras_entries as $jrn) {
				$arr_jurnaltras_balik[] = [
					'nomor'              => $jrn->nomor . '-REV',
					'tanggal'            => date('Y-m-d'),
					'tipe'               => $jrn->tipe,
					'no_perkiraan'       => $jrn->no_perkiraan,
					'keterangan'         => 'BATAL - ' . $jrn->keterangan,
					'no_request'         => $jrn->no_request,
					'kredit'             => $jrn->debet,
					'debet'              => $jrn->kredit,
					'nilai_valas_debet'  => $jrn->nilai_valas_kredit,
					'nilai_valas_kredit' => $jrn->nilai_valas_debet,
					'no_reff'            => $jrn->no_reff,
					'jenis_jurnal'       => $jrn->jenis_jurnal,
					'nocust'             => $jrn->nocust,
					'stspos'             => '1'
				];
			}
			$this->db->insert_batch('jurnaltras', $arr_jurnaltras_balik);
		}

		// 3) Reverse kartu hutang
		$kartu_hutang_entries = $this->db->get_where('tr_kartu_hutang', [
			'no_request' => $id_payment
		])->result();

		if (!empty($kartu_hutang_entries)) {
			foreach ($kartu_hutang_entries as $kh) {
				$this->db->insert('tr_kartu_hutang', [
					'tipe'          => $kh->tipe,
					'nomor'         => $kh->nomor . '-REV',
					'tanggal'       => date('Y-m-d'),
					'no_perkiraan'  => $kh->no_perkiraan,
					'keterangan'    => 'BATAL - ' . $kh->keterangan,
					'no_reff'       => $kh->no_reff,
					'debet'         => $kh->kredit,
					'kredit'        => $kh->debet,
					'id_supplier'   => $kh->id_supplier,
					'nama_supplier' => $kh->nama_supplier,
					'no_request'    => $kh->no_request,
					'debet_usd'     => isset($kh->kredit_usd) ? $kh->kredit_usd : 0,
					'kredit_usd'    => isset($kh->debet_usd) ? $kh->debet_usd : 0,
				]);
			}
		}

		// 4) Update status payment_approve
		$this->db->where('id_payment', $id_payment);
		$this->db->update('payment_approve', [
			'status'                => 1,
			'id_payment'            => null,
			'tgl_bayar'             => null,
			'payment_bank'          => null,
			'total_payment'         => null,
			'keterangan_pembayaran' => null,
			'link_doc'              => null,
			'coa_bank'              => null,
			'nm_coa_bank'           => null,
			'mata_uang'             => null,
			'kurs_payment'          => null,
			'selisih'               => null
		]);

		// 5) Tandai batal di tr_payment_paid
		$this->db->where('id', $id_payment);
		$this->db->update('tr_payment_paid', [
			'keterangan_pembayaran' => 'DIBATALKAN oleh ' . $Username . ' pada ' . date('Y-m-d H:i:s')
		]);

		$this->db->trans_complete();

		if ($this->db->trans_status()) {
			$this->db->trans_commit();
			history('Cancel Payment: ' . $id_payment . ' by ' . $Username);
			echo json_encode(['status' => true, 'message' => 'Payment ' . $id_payment . ' berhasil dibatalkan beserta jurnal baliknya.']);
		} else {
			$this->db->trans_rollback();
			echo json_encode(['status' => false, 'message' => 'Gagal membatalkan payment. Error: ' . json_encode($this->db->error())]);
		}
	}


	public function form_payment_new()
	{

		$id_payment = explode(';', $_GET['id_payment']);

		$check_transpoty_driver = $this->Pembayaran_material_model->check_transport_payment($id_payment);

		$jurnal_refill_petty_cash = '';
		if ($check_transpoty_driver > 0) {
			$jurnal_refill_petty_cash = $this->Pembayaran_material_model->jurnal_refill_petty_cash($id_payment);
		}

		$get_payment = $this->db
			->select('a.*')
			->from('payment_approve a')
			->where_in('a.id', $id_payment)
			->get()
			->result();
		$get_supplier = $this->db->get('new_supplier')->result();
		$get_mata_uang = $this->db->get_where('mata_uang', ['deleted_by' => 0, 'activation' => 'active'])->result();

		$this->db->from(DBACC . '.coa_master a')
			->where('a.no_perkiraan LIKE', '%1101-02%')
			->where('a.level', 5);
		$get_bank = $this->db->get()->result();

		$data = [
			'id_payment' => implode(',', $id_payment),
			'result_payment' => $get_payment,
			'list_supplier' => $get_supplier,
			'list_bank' => $get_bank,
			'list_mata_uang' => $get_mata_uang,
			'jurnal_refill_petty_cash' => $jurnal_refill_petty_cash
		];

		$this->template->set('results', $data);
		$this->template->render('form_payment_new');
	}

	public function save_payment_new()
	{
		$id_req = $this->input->post("id_req");
		$payment_date = $this->input->post("payment_date");
		$bank_coa = $this->input->post("bank_coa");
		$bank_nilai = $this->input->post("bank_nilai");
		$curs = $this->input->post("curs");
		$id_supplier = $this->input->post("id_supplier");
		$curs_header = $this->input->post("curs_header");

		$biaya_admin_forex = $this->input->post("biaya_admin_forex");
		$biaya_admin = $this->input->post("biaya_admin");
		$curs_admin = $this->input->post("curs_admin");

		$biaya_admin_forex2 = $this->input->post("biaya_admin_forex2");
		$biaya_admin2 = $this->input->post("biaya_admin2");
		$curs_admin2 = $this->input->post("curs_admin2");
		$bank_coa_admin = $this->input->post("bank_coa_admin");

		$nilai_bayar_bank = $this->input->post("nilai_bayar_bank");

		$data_session	= $this->session->userdata;
		$Username 		= $this->session->userdata['ORI_User']['username'];
		$dateTime		= date('Y-m-d H:i:s');
		$alokasi_dp = $this->input->post("alokasi_dp");
		$alokasi_hutang = $this->input->post("alokasi_hutang");
		$tipetrans = $this->input->post("tipetrans");
		$selisih_kurs1 = 0;
		$this->db->trans_begin();
		$jenis_jurnal = 'BUK20';
		if ($curs_header != 'IDR') $jenis_jurnal = 'BUK21';
		try {

			$no_payment = $this->All_model->GetAutoGenerate('format_payment');
			$nomor_jurnal = $jenis_jurnal . $no_payment . rand(100, 999);

			$dataheader =  array(
				'no_payment' => $no_payment,
				'id_supplier' => $id_supplier,
				'curs_header' => $curs_header,
				'payment_date' => $payment_date,
				'bank_coa' => $bank_coa,
				'nilai_bayar_bank' => $nilai_bayar_bank,
				'curs' => $curs,
				'bank_nilai' => $bank_nilai,
				'modul' => 'PO',
				'biaya_admin_forex' => $biaya_admin_forex,
				'biaya_admin' => $biaya_admin,
				'curs_admin' => $curs_admin,
				'biaya_admin_forex2' => $biaya_admin_forex2,
				'biaya_admin2' => $biaya_admin2,
				'curs_admin2' => $curs_admin2,
				'bank_coa_admin' => $bank_coa_admin,
				'status' => '1',
				'created_on' => date('Y-m-d H:i:s'),
				'created_by' => $Username
			);

			$this->All_model->dataSave('purchase_order_request_payment_header', $dataheader);
			$datajurnal1 = $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no in ('1','4','8') order by parameter_no")->result();
			$det_Jurnaltes1 = array();
			$total = 0;
			foreach ($datajurnal1 as $rec) {
				// CASH BANK
				if ($rec->parameter_no == "1") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => ($bank_nilai),
						'debet' => 0,
						'nilai_valas_debet' => 0,
						'nilai_valas_kredit' => $nilai_bayar_bank,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
				// ADMIN BANK EXPENSE
				if ($rec->parameter_no == "4") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => 0,
						'debet' => $biaya_admin,
						'nilai_valas_debet' => 0,
						'nilai_valas_kredit' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => 0,
						'debet' => $biaya_admin2,
						'nilai_valas_debet' => 0,
						'nilai_valas_kredit' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
				// ADMIN BANK
				if ($rec->parameter_no == "8") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => $biaya_admin,
						'debet' => 0,
						'nilai_valas_debet' => 0,
						'nilai_valas_kredit' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => $biaya_admin2,
						'debet' => 0,
						'nilai_valas_debet' => 0,
						'nilai_valas_kredit' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
			}
			$tanggal = $payment_date;
			$Bln	= substr($tanggal, 5, 2);
			$Thn	= substr($tanggal, 0, 4);
			$Nomor_JV = $this->Jurnal_model->get_no_buk('101', $tanggal);
			foreach ($id_req as $keys) {
				$this->All_model->DataUpdate('purchase_order_request_payment', array('status' => '2', 'payment_date' => $payment_date, 'no_payment' => $no_payment), array('id' => $keys));
				$data = $this->db->query("select * from purchase_order_request_payment where id='" . $keys . "'")->row();
				$selisih_kurs = 0;
				$nilai_terima_barang_idr = 0;
				$datapoheader = $this->db->query("select * from tran_material_po_header where no_po='" . $data->no_po . "'")->row();
				if ($datapoheader->terima_barang_idr != 0) {
					$kurs_hutang = $datapoheader->kurs_terima;
					$selisih_kurs = (($data->nilai_po_invoice * $curs) - $data->nilai_po_invoice * $datapoheader->kurs_terima);

					if ($selisih_kurs < 0) {
						$selisih_kurs1 = $selisih_kurs * (-1);
					} else {
						$selisih_kurs1 = $selisih_kurs;
					}
				} else {

					$kurs_hutang = $data->kurs_receive_invoice;

					$selisih_kurs = (($data->nilai_po_invoice * $curs) - $data->nilai_po_invoice * $data->kurs_receive_invoice);

					if ($selisih_kurs < 0) {
						$selisih_kurs1 = $selisih_kurs * (-1);
					} else {
						$selisih_kurs1 = $selisih_kurs;
					}
				}


				// update PO
				$nilai_dp_kurs = 0;
				$addsql = "";
				if ($data->tipe == 'TR-01') {
					$nilai_dp_kurs = ($data->nilai_po_invoice * $curs);
					$addsql = ", nilai_dp_kurs=" . $nilai_dp_kurs . "";
				}

				if ($data->tipe == 'TR-01') {
					$this->db->query("update tran_material_po_header set terima_barang_kurs=0, terima_barang_idr=0
				" . $addsql . ", total_bayar=(total_bayar+" . ($data->nilai_po_invoice) . "),
				total_bayar_rupiah=(total_bayar_rupiah+" . ($data->nilai_po_invoice * $curs) . "),
				bayar_kurs=(bayar_kurs+" . ($data->nilai_po_invoice) . "),
				bayar_idr=(bayar_idr+" . ($data->nilai_po_invoice * $curs) . ")
				" .
						($data->tipe == 'TR-01' ?
							",nilai_dp=(nilai_dp+" . $data->nilai_po_invoice . "), sisa_dp=(sisa_dp+" . $data->nilai_po_invoice . ")" :
							", nilai_dp=(nilai_dp-" . $data->potongan_dp . "), sisa_dp=(sisa_dp-" . $data->potongan_dp . ")") .
						" where no_po='" . $data->no_po . "'");
				}

				if ($data->tipe == 'TR-02') {
					$this->db->query("update tran_material_po_header set terima_barang_kurs=0, terima_barang_idr=0
				" . $addsql . ", total_bayar=(total_bayar+" . ($data->nilai_po_invoice) . "),
				total_bayar_rupiah=(total_bayar_rupiah+" . ($data->nilai_po_invoice * $curs) . "),
				bayar_kurs=(bayar_kurs+" . ($data->nilai_po_invoice) . "),
				bayar_idr=(bayar_idr+" . ($data->nilai_po_invoice * $curs) . "),
				sisa_hutang_kurs=(sisa_hutang_kurs-" . ($data->nilai_po_invoice) . "),
				sisa_hutang_idr=(sisa_hutang_idr-" . ($data->nilai_po_invoice * $curs) . ")				
				" .
						($data->tipe == 'TR-01' ?
							",nilai_dp=(nilai_dp+" . $data->nilai_po_invoice . "), sisa_dp=(sisa_dp+" . $data->nilai_po_invoice . ")" :
							", nilai_dp=(nilai_dp-" . $data->potongan_dp . "), sisa_dp=(sisa_dp-" . $data->potongan_dp . ")") .
						" where no_po='" . $data->no_po . "'");
				}

				$keterangan		= 'Pembayaran ' . $no_payment;
				$data_coa 	= $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no='3'")->row();
				$data_supplier 	= $this->db->query("select * from supplier where id_supplier='" . $data->id_supplier . "'")->row();
				$data_coa2 	= $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no='2'")->row();

				if ($data->curs_header == 'IDR') {
					$coahutang = '2101-01-01';
				} else {
					$coahutang = '2101-01-04';
				}

				if ($data->tipe == 'TR-01') {
					$datahutang = array(
						'tipe'       	 => 'BUK',
						'nomor'       	 => $Nomor_JV,
						'tanggal'        => $tanggal,
						'no_perkiraan'   => $coahutang,
						'keterangan'     => $keterangan,
						'no_reff'     	 => $data->no_po,
						'debet'      	 => (($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
						'kredit'         => 0,
						'id_supplier'    => $data->id_supplier,
						'nama_supplier'  => $data_supplier->nm_supplier,
						'no_request'     => $no_payment,
						'debet_usd'		 => (($curs_header != 'IDR') ? ($data->nilai_po_invoice + $data->invoice_ppn) : 0),
						'kredit_usd'	=> 0,

					);
					$this->db->insert('tr_kartu_hutang', $datahutang);
				}


				if ($data->tipe == 'TR-02') {
					$datahutang = array(
						'tipe'       	 => 'BUK',
						'nomor'       	 => $Nomor_JV,
						'tanggal'        => $tanggal,
						'no_perkiraan'   => $coahutang,
						'keterangan'     => $keterangan,
						'no_reff'     	 => $data->no_po,
						'debet'      	 => (($data->nilai_po_invoice + $data->invoice_ppn) * $kurs_hutang),
						'kredit'         => 0,
						'id_supplier'    => $data->id_supplier,
						'nama_supplier'  => $data_supplier->nm_supplier,
						'no_request'     => $no_payment,
						'debet_usd'		 => (($curs_header != 'IDR') ? ($data->nilai_po_invoice + $data->invoice_ppn) : 0),
						'kredit_usd'	=> 0,

					);
					$this->db->insert('tr_kartu_hutang', $datahutang);
				}

				$datajurnal1 = $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no in ('2','3','5','6','7','9') order by parameter_no")->result();
				foreach ($datajurnal1 as $rec) {
					if ($data->modul == 'PO') {
						// UANG MUKA
						if ($rec->parameter_no == "2") {
							if ($data->tipe == 'TR-01') {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $coahutang,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => round(($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
									'nilai_valas_debet' => ($data->nilai_po_invoice + $data->invoice_ppn),
									'nilai_valas_kredit' => 0,
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							} else {
								if ($data->potongan_dp > 0) {
									$det_Jurnaltes1[] = array(
										'nomor' => $nomor_jurnal,
										'tanggal' => $payment_date,
										'tipe' => 'BUK',
										'no_perkiraan' => $rec->no_perkiraan,
										'keterangan' => $data->keterangan,
										'no_request' => $data->no_po,
										'debet' => 0,
										'kredit' => 0,
										'nilai_valas_debet' => 0,
										'nilai_valas_kredit' => 0,
										'no_reff' => $no_payment,
										'jenis_jurnal' => $jenis_jurnal,
										'nocust' => $data->id_supplier,
										'stspos' => '1'
									);
								}
							}
						}
						// HUTANG
						if ($rec->parameter_no == "3") {
							if ($data->tipe == 'TR-02') {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $coahutang,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => round((($data->nilai_po_invoice + $data->invoice_ppn) * $kurs_hutang)),
									'nilai_valas_debet' => ($data->nilai_po_invoice + $data->invoice_ppn),
									'nilai_valas_kredit' => 0,
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							} else {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $coahutang,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => 0,
									'nilai_valas_debet' => 0,
									'nilai_valas_kredit' => 0,
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							}
						}
					}

					if ($data->modul == 'FORWARDER') {
						// HUTANG FORWARDER
						if ($rec->parameter_no == "5") {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => 'FORWARDER ',
								'no_request' => $data->no_po,
								'kredit' => 0,
								'debet' => round(($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
								'nilai_valas_debet' => 0,
								'nilai_valas_kredit' => 0,
								'no_reff' => $no_payment,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						}
					}
					// PPN
					if ($rec->parameter_no == "6") {
						/*
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal, 'tanggal' => $payment_date, 'tipe' => 'BUK', 'no_perkiraan' => $rec->no_perkiraan, 'keterangan' => $data->keterangan, 'no_request' => $data->no_po, 'kredit' => 0, 'debet' => ($data->invoice_ppn*$curs), 'no_reff' => $no_payment, 'jenis_jurnal'=>$jenis_jurnal, 'nocust'=>$data->id_supplier
					);
*/
					}
					// PPH
					if ($rec->parameter_no == "7") {
						if ($data->nilai_pph_invoice <> 0) {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $data->coa_pph,
								'keterangan' => $data->keterangan,
								'no_request' => $data->no_po,
								'kredit' => round($data->nilai_pph_invoice * $curs),
								'debet' => 0,
								'nilai_valas_debet' => 0,
								'nilai_valas_kredit' => 0,
								'no_reff' => $no_payment,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						}
					}
					// SELISIH KURS
					if ($rec->parameter_no == "9") {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'BUK',
							'no_perkiraan' => $rec->no_perkiraan,
							'keterangan' => $data->keterangan,
							'no_request' => $data->no_po,
							'kredit' => round($selisih_kurs < 0 ? ($selisih_kurs * -1) : 0),
							'debet' => round($selisih_kurs >= 0 ? $selisih_kurs : 0),
							'nilai_valas_debet' => 0,
							'nilai_valas_kredit' => 0,
							'no_reff' => $no_payment,
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $data->id_supplier,
							'stspos' => '1'
						);
					}
				}
			}
			$this->db->insert_batch('jurnaltras', $det_Jurnaltes1);

			//auto jurnal

			foreach ($det_Jurnaltes1 as $vals) {
				$datadetail = array(
					'tipe'			=> 'BUK',
					'nomor'			=> $Nomor_JV,
					'tanggal'		=> $tanggal,
					'no_perkiraan'	=> $vals['no_perkiraan'],
					'keterangan'	=> $vals['keterangan'],
					'no_reff'		=> $vals['no_reff'],
					'debet'			=> $vals['debet'],
					'kredit'		=> $vals['kredit'],
					'nilai_valas_debet'			=> $vals['nilai_valas_debet'],
					'nilai_valas_kredit'		=> $vals['nilai_valas_kredit'],
				);
				$total = ($total + $vals['debet']);
				$this->db->insert(DBACC . '.jurnal', $datadetail);
			}

			$keterangan		= 'Pembayaran ' . $no_payment;
			$dataJVhead = array(
				'nomor' 	    	=> $Nomor_JV,
				'tgl'	         	=> $tanggal,
				'jml'	            => $total,
				'jenis_ap'	        => 'V',
				'bayar_kepada'		=> $data_supplier->nm_supplier,
				'kdcab'				=> '101',
				'jenis_reff' 		=> 'BUK',
				'no_reff' 			=> $no_payment,
				'note'				=> $keterangan,
				'user_id'			=> $Username,
				'ho_valid'			=> '',
			);

			$this->db->insert(DBACC . '.japh', $dataJVhead);
			$Qry_Update_Cabang_acc	 = "UPDATE " . DBACC . ".pastibisa_tb_cabang SET nobuk=nobuk + 1 WHERE nocab='101'";
			$this->db->query($Qry_Update_Cabang_acc);


			//end auto jurnal

			$this->db->trans_complete();
			if ($this->db->trans_status()) {
				$this->db->trans_commit();
				$result         = TRUE;
				history('Save Payment');
			} else {
				$this->db->trans_rollback();
				$result = FALSE;
			}
		} catch (Exception $e) {
			$this->db->trans_rollback();
			$result = FALSE;
		}

		$param = array(
			'save' => $result
		);
		echo json_encode($param);
	}
	public function view_payment_new($id)
	{
		$list_id_payment = [];
		$get_id_payment = $this->db->select('a.id')->get_where('payment_approve a', ['a.id_payment' => $id])->result();
		foreach ($get_id_payment as $item_id_payment) {
			$list_id_payment[] = $item_id_payment->id;
		}
		$list_id_payment = implode(';', $list_id_payment);

		$id_payment = explode(';', $list_id_payment);

		// $dataid = implode("','", $request_id);
		// $results = $this->pembayaran_material_model->get_data_json_request_payment("id in ('" . $dataid . "')");
		// $data_Group	= $this->master_model->getArray('groups', array(), 'id', 'name');
		// $datacoa	= $this->All_model->GetCoaCombo('5', " a.no_perkiraan like '1101%'");
		// $data = array(
		// 	'title'			=> 'Form Payment',
		// 	'action'		=> 'index',
		// 	'datacoa'		=> $datacoa,
		// 	'row_group'		=> $data_Group,
		// 	'akses_menu'	=> $Arr_Akses,
		// 	'results'		=> $results,
		// );
		// history('Form Payment');
		// $this->load->view('Pembayaran_material/form_payment_new.php', $data);

		$get_payment = $this->db
			->select('a.*')
			->from('payment_approve a')
			->where_in('a.id', $id_payment)
			->get()
			->result();
		$get_supplier = $this->db->get('new_supplier')->result();
		$this->db->from(DBACC . '.coa_master a')
			->where('a.no_perkiraan LIKE', '%1101-02%')
			->where('a.level', 5);
		$get_bank = $this->db->get()->result();

		$get_mata_uang = $this->db->get_where('mata_uang', ['deleted_by' => 0, 'activation' => 'active'])->result();

		$get_payment_header = $this->db
			->select('a.*')
			->from('payment_approve a')
			->where_in('a.id', $id_payment)
			->group_by('a.id_payment')
			->get()
			->row();

		$bank_charge = 0;
		$get_bank_charge = $this->db->get_where('tr_payment_paid a', ['a.id' => $id])->row();
		if (!empty($get_bank_charge)) {
			$bank_charge = $get_bank_charge->bank_charge;
		}

		$data = [
			'id_payment' => implode(',', $id_payment),
			'result_header' => $get_payment_header,
			'result_payment' => $get_payment,
			'list_supplier' => $get_supplier,
			'list_bank' => $get_bank,
			'list_mata_uang' => $get_mata_uang,
			'bank_charge' => $bank_charge
		];
		$this->template->set('results', $data);
		$this->template->render('view_payment_new');
	}



	//==================================================================================================================
	//================================================== PAYMENT JURNAL ================================================
	//==================================================================================================================



	public function save_payment_new_nonmaterial()
	{
		$id_req = $this->input->post("id_req");
		$payment_date = $this->input->post("payment_date");
		$bank_coa = $this->input->post("bank_coa");
		$bank_nilai = $this->input->post("bank_nilai");
		$curs = $this->input->post("curs");
		$id_supplier = $this->input->post("id_supplier");
		$curs_header = $this->input->post("curs_header");

		$biaya_admin_forex = $this->input->post("biaya_admin_forex");
		$biaya_admin = $this->input->post("biaya_admin");
		$curs_admin = $this->input->post("curs_admin");

		$biaya_admin_forex2 = $this->input->post("biaya_admin_forex2");
		$biaya_admin2 = $this->input->post("biaya_admin2");
		$curs_admin2 = $this->input->post("curs_admin2");
		$bank_coa_admin = $this->input->post("bank_coa_admin");

		$nilai_bayar_bank = $this->input->post("nilai_bayar_bank");

		$data_session	= $this->session->userdata;
		$Username 		= $this->session->userdata['ORI_User']['username'];
		$dateTime		= date('Y-m-d H:i:s');
		$alokasi_dp = $this->input->post("alokasi_dp");
		$alokasi_hutang = $this->input->post("alokasi_hutang");
		$tipetrans = $this->input->post("tipetrans");
		$this->db->trans_begin();
		$jenis_jurnal = 'BUK20';
		if ($curs_header != 'IDR') $jenis_jurnal = 'BUK21';
		try {

			$no_payment = $this->All_model->GetAutoGenerate('format_payment');
			$nomor_jurnal = $jenis_jurnal . $no_payment . rand(100, 999);

			$tanggal = $payment_date;
			$Bln	= substr($tanggal, 5, 2);
			$Thn	= substr($tanggal, 0, 4);
			$Nomor_JV = $this->Jurnal_model->get_no_buk('101', $tanggal);

			$dataheader =  array(
				'no_payment' => $no_payment,
				'id_supplier' => $id_supplier,
				'curs_header' => $curs_header,
				'payment_date' => $payment_date,
				'bank_coa' => $bank_coa,
				'nilai_bayar_bank' => $nilai_bayar_bank,
				'curs' => $curs,
				'bank_nilai' => $bank_nilai,
				'modul' => 'PO',
				'tipe' => 'nonmaterial',
				'biaya_admin_forex' => $biaya_admin_forex,
				'biaya_admin' => $biaya_admin,
				'curs_admin' => $curs_admin,
				'biaya_admin_forex2' => $biaya_admin_forex2,
				'biaya_admin2' => $biaya_admin2,
				'curs_admin2' => $curs_admin2,
				'bank_coa_admin' => $bank_coa_admin,
				'status' => '1',
				'created_on' => date('Y-m-d H:i:s'),
				'created_by' => $Username
			);

			$this->All_model->dataSave('purchase_order_request_payment_header', $dataheader);
			$datajurnal1 = $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no in ('1','4','8') order by parameter_no")->result();
			$det_Jurnaltes1 = array();
			foreach ($datajurnal1 as $rec) {
				// CASH BANK
				if ($rec->parameter_no == "1") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => ($bank_nilai),
						'debet' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
				// ADMIN BANK EXPENSE
				if ($rec->parameter_no == "4") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => 0,
						'debet' => $biaya_admin,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $rec->no_perkiraan,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => 0,
						'debet' => $biaya_admin2,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
				// ADMIN BANK
				if ($rec->parameter_no == "8") {
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => $biaya_admin,
						'debet' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal,
						'tanggal' => $payment_date,
						'tipe' => 'BUK',
						'no_perkiraan' => $bank_coa,
						'keterangan' => $rec->keterangan,
						'no_request' => $no_payment,
						'kredit' => $biaya_admin2,
						'debet' => 0,
						'no_reff' => $no_payment,
						'jenis_jurnal' => $jenis_jurnal,
						'nocust' => $id_supplier,
						'stspos' => '1'
					);
				}
			}
			foreach ($id_req as $keys) {
				$this->All_model->DataUpdate('purchase_order_request_payment_nm', array('status' => '2', 'payment_date' => $payment_date, 'no_payment' => $no_payment), array('id' => $keys));
				$data = $this->db->query("select * from purchase_order_request_payment_nm where id='" . $keys . "'")->row();
				$selisih_kurs = 0;
				$nilai_terima_barang_idr = 0;
				$datapoheader = $this->db->query("select * from tran_po_header where no_po='" . $data->no_po . "'")->row();
				if ($datapoheader->terima_barang_idr != 0) {
					$selisih_kurs = (($data->nilai_po_invoice * $curs) - $datapoheader->terima_barang_idr);
				}
				// update PO
				$nilai_dp_kurs = 0;
				$addsql = "";
				if ($data->tipe == 'TR-01') {
					$nilai_dp_kurs = ($data->nilai_po_invoice * $curs);
					$addsql = ", nilai_dp_kurs=" . $nilai_dp_kurs . "";
				}
				$this->db->query("update tran_po_header set terima_barang_kurs=0, terima_barang_idr=0
				" . $addsql . ", total_bayar=(total_bayar+" . ($data->nilai_po_invoice) . "),
				total_bayar_rupiah=(total_bayar_rupiah+" . ($data->nilai_po_invoice * $curs) . ")
				" .
					($data->tipe == 'TR-01' ?
						",nilai_dp=(nilai_dp+" . $data->nilai_po_invoice . "), sisa_dp=(sisa_dp+" . $data->nilai_po_invoice . ")" :
						", nilai_dp=(nilai_dp-" . $data->potongan_dp . "), sisa_dp=(sisa_dp-" . $data->potongan_dp . ")") .
					" where no_po='" . $data->no_po . "'");

				$keterangan		= 'Pembayaran ' . $no_payment;
				$data_coa 	= $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no='3'")->row();
				$data_supplier 	= $this->db->query("select * from supplier where id_supplier='" . $data->id_supplier . "'")->row();
				$datahutang = array(
					'tipe'       	 => 'BUK',
					'nomor'       	 => $Nomor_JV,
					'tanggal'        => $tanggal,
					'no_perkiraan'   => $data_coa->no_perkiraan,
					'keterangan'     => $keterangan,
					'no_reff'     	 => $data->no_po,
					'debet'      	 => (($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
					'kredit'         => 0,
					'id_supplier'    => $data->id_supplier,
					'nama_supplier'  => $data_supplier->nm_supplier,
					'no_request'     => $no_payment,
					'debet_usd'		 => (($curs_header != 'IDR') ? ($data->nilai_po_invoice + $data->invoice_ppn) : 0),
					'kredit_usd'	=> 0,
				);
				$this->db->insert('tr_kartu_hutang', $datahutang);


				if ($data->curs_header == 'IDR') {
					$coahutang = '2101-01-01';
				} else {
					$coahutang = '2101-01-04';
				}


				$datajurnal1 = $this->db->query("select * from " . DBACC . ".master_oto_jurnal_detail where kode_master_jurnal='" . $jenis_jurnal . "' and parameter_no in ('2','3','5','6','7','9') order by parameter_no")->result();
				foreach ($datajurnal1 as $rec) {
					if ($data->modul == 'PO') {
						// UANG MUKA
						if ($rec->parameter_no == "2") {
							if ($data->tipe == 'TR-01') {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $coahutang,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => (($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							} else {
								if ($data->potongan_dp > 0) {
									$det_Jurnaltes1[] = array(
										'nomor' => $nomor_jurnal,
										'tanggal' => $payment_date,
										'tipe' => 'BUK',
										'no_perkiraan' => $coahutang,
										'keterangan' => $data->keterangan,
										'no_request' => $data->no_po,
										'debet' => 0,
										'kredit' => 0,
										'no_reff' => $no_payment,
										'jenis_jurnal' => $jenis_jurnal,
										'nocust' => $data->id_supplier,
										'stspos' => '1'
									);
								}
							}
						}
						// HUTANG
						if ($rec->parameter_no == "3") {
							if ($data->tipe == 'TR-02') {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $rec->no_perkiraan,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => (($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							} else {
								$det_Jurnaltes1[] = array(
									'nomor' => $nomor_jurnal,
									'tanggal' => $payment_date,
									'tipe' => 'BUK',
									'no_perkiraan' => $rec->no_perkiraan,
									'keterangan' => $data->keterangan,
									'no_request' => $data->no_po,
									'kredit' => 0,
									'debet' => 0,
									'no_reff' => $no_payment,
									'jenis_jurnal' => $jenis_jurnal,
									'nocust' => $data->id_supplier,
									'stspos' => '1'
								);
							}
						}
					}

					if ($data->modul == 'FORWARDER') {
						// HUTANG FORWARDER
						if ($rec->parameter_no == "5") {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => 'FORWARDER ',
								'no_request' => $data->no_po,
								'kredit' => 0,
								'debet' => (($data->nilai_po_invoice + $data->invoice_ppn) * $curs),
								'no_reff' => $no_payment,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						}
					}
					// PPN
					if ($rec->parameter_no == "6") {
						/*
					$det_Jurnaltes1[] = array(
						'nomor' => $nomor_jurnal, 'tanggal' => $payment_date, 'tipe' => 'BUK', 'no_perkiraan' => $rec->no_perkiraan, 'keterangan' => $data->keterangan, 'no_request' => $data->no_po, 'kredit' => 0, 'debet' => ($data->invoice_ppn*$curs), 'no_reff' => $no_payment, 'jenis_jurnal'=>$jenis_jurnal, 'nocust'=>$data->id_supplier
					);
*/
					}
					// PPH
					if ($rec->parameter_no == "7") {
						if ($data->nilai_pph_invoice <> 0) {
							$det_Jurnaltes1[] = array(
								'nomor' => $nomor_jurnal,
								'tanggal' => $payment_date,
								'tipe' => 'BUK',
								'no_perkiraan' => $rec->no_perkiraan,
								'keterangan' => $data->keterangan,
								'no_request' => $data->no_po,
								'kredit' => ($data->nilai_pph_invoice * $curs),
								'debet' => 0,
								'no_reff' => $no_payment,
								'jenis_jurnal' => $jenis_jurnal,
								'nocust' => $data->id_supplier,
								'stspos' => '1'
							);
						}
					}
					// SELISIH KURS
					if ($rec->parameter_no == "9") {
						$det_Jurnaltes1[] = array(
							'nomor' => $nomor_jurnal,
							'tanggal' => $payment_date,
							'tipe' => 'BUK',
							'no_perkiraan' => $rec->no_perkiraan,
							'keterangan' => $data->keterangan,
							'no_request' => $data->no_po,
							'kredit' => ($selisih_kurs < 0 ? ($selisih_kurs * -1) : 0),
							'debet' => ($selisih_kurs >= 0 ? $selisih_kurs : 0),
							'no_reff' => $no_payment,
							'jenis_jurnal' => $jenis_jurnal,
							'nocust' => $data->id_supplier,
							'stspos' => '1'
						);
					}
				}
			}
			$this->db->insert_batch('jurnaltras', $det_Jurnaltes1);


			//auto jurnal
			$tanggal = $payment_date;
			$Bln	= substr($tanggal, 5, 2);
			$Thn	= substr($tanggal, 0, 4);
			$Nomor_JV = $this->Jurnal_model->get_no_buk('101', $tanggal);
			$total = 0;
			foreach ($det_Jurnaltes1 as $vals) {
				$datadetail = array(
					'tipe'			=> 'BUK',
					'nomor'			=> $Nomor_JV,
					'tanggal'		=> $tanggal,
					'no_perkiraan'	=> $vals['no_perkiraan'],
					'keterangan'	=> $vals['keterangan'],
					'no_reff'		=> $vals['no_reff'],
					'debet'			=> $vals['debet'],
					'kredit'		=> $vals['kredit'],
				);
				$total = ($total + $vals['debet']);
				$this->db->insert(DBACC . '.jurnal', $datadetail);
			}

			$dataJVhead = array(
				'nomor' 	    	=> $Nomor_JV,
				'tgl'	         	=> $tanggal,
				'jml'	            => $total,
				'jenis_ap'	        => 'V',
				'bayar_kepada'		=> $data_supplier->nm_supplier,
				'kdcab'				=> '101',
				'jenis_reff' 		=> 'BUK',
				'no_reff' 			=> $no_payment,
				'note'				=> $keterangan,
				'user_id'			=> $Username,
				'ho_valid'			=> '',
			);

			$this->db->insert(DBACC . '.japh', $dataJVhead);
			$Qry_Update_Cabang_acc	 = "UPDATE " . DBACC . ".pastibisa_tb_cabang SET nobuk=nobuk + 1 WHERE nocab='101'";
			$this->db->query($Qry_Update_Cabang_acc);

			//end auto jurnal

			$this->db->trans_complete();
			if ($this->db->trans_status()) {
				$this->db->trans_commit();
				$result         = TRUE;
				history('Save Payment');
			} else {
				$this->db->trans_rollback();
				$result = FALSE;
			}
		} catch (Exception $e) {
			$this->db->trans_rollback();
			$result = FALSE;
		}

		$param = array(
			'save' => $result
		);
		echo json_encode($param);
	}

	public function list_request_payment($jenis_payment)
	{
		$this->template->set('jenis_payment', $jenis_payment);
		$this->template->title('List Request Payment');
		$this->template->render('list_request_payment');
	}

	public function check_payment()
	{
		$id = $this->input->post('id');
		$checked = $this->input->post('checked');

		$this->db->trans_start();

		if ($checked == 1) {
			$this->db->insert('tr_choosed_payment', [
				'id_user' => $this->auth->user_id(),
				'id_payment' => $id
			]);
		} else {
			$this->db->delete('tr_choosed_payment', ['id_user' => $this->auth->user_id(), 'id_payment' => $id]);
		}

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
		} else {
			$this->db->trans_commit();
		}
	}

	public function clear_choosed_payment()
	{
		$id_user = $this->auth->user_id();

		$this->db->trans_start();

		$this->db->delete('tr_choosed_payment', ['id_user' => $id_user]);

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

	public function proses_payment()
	{
		$id_user = $this->auth->user_id();

		$arr_choosed_payment = [];
		$get_choosed_payment = $this->db->query("SELECT * FROM tr_choosed_payment WHERE id_user = '" . $id_user . "'")->result();
		foreach ($get_choosed_payment as $item) {
			$arr_choosed_payment[] = $item->id_payment;
		}

		echo json_encode([
			'count_choosed_payment' => count($get_choosed_payment),
			'arr_choosed_payment' => implode(';', $arr_choosed_payment)
		]);
	}

	// public function save_payment()
	// {
	// 	$post = $this->input->post();

	// 	$payment_bank = str_replace(',', '', $post['payment_bank']);

	// 	$this->db->trans_start();

	// 	$get_coa_bank = $this->db->get_where(DBACC . '.coa_master', ['no_perkiraan' => $post['bank']])->row();

	// 	$nm_coa_bank = '';
	// 	$kode_bank = '';
	// 	if (!empty($get_coa_bank)) {
	// 		$nm_coa_bank = $get_coa_bank->nama;
	// 		$kode_bank = $get_coa_bank->no_perkiraan;
	// 	}

	// 	$id_payment_paid = $this->Pembayaran_material_model->generate_id_payment_paid($kode_bank, $post['tgl_bayar']);

	// 	$config['upload_path'] = 'uploads/expense/';
	// 	$config['allowed_types'] = '*';
	// 	$config['remove_spaces'] = TRUE;
	// 	$config['encrypt_name'] = TRUE;
	// 	$filenames = '';

	// 	if (!empty($_FILES['upload_doc']['name'])) {
	// 		$_FILES['file']['name'] = $_FILES['upload_doc']['name'];
	// 		$_FILES['file']['type'] = $_FILES['upload_doc']['type'];
	// 		$_FILES['file']['tmp_name'] = $_FILES['upload_doc']['tmp_name'];
	// 		$_FILES['file']['error'] = $_FILES['upload_doc']['error'];
	// 		$_FILES['file']['size'] = $_FILES['upload_doc']['size'];
	// 		// $this->load->library('upload', $config);
	// 		$this->upload->initialize($config);
	// 		if ($this->upload->do_upload('file')) {
	// 			$uploadData = $this->upload->data();
	// 			$filenames = $uploadData['file_name'];
	// 		}
	// 	}

	// 	$insert_payment_paid = $this->db->insert('tr_payment_paid', [
	// 		'id' => $id_payment_paid,
	// 		'bank_charge' => str_replace(',', '', $post['bank_charge']),
	// 		'created_by' => $this->auth->user_id(),
	// 		'created_on' => date('Y-m-d H:i:s')
	// 	]);
	// 	if (!$insert_payment_paid) {
	// 		print_r($this->db->error($insert_payment_paid));
	// 		exit;
	// 	}

	// 	$this->db->where_in('id', explode(',', $post['id_payment']));
	// 	$update_payment1 = $this->db->update('payment_approve', [
	// 		'id_payment' => $id_payment_paid,
	// 		'tgl_bayar' => $post['tgl_bayar'],
	// 		'supplier' => $post['supplier_input'],
	// 		'keterangan_pembayaran' => $post['keterangan_pembayaran'],
	// 		'coa_bank' => $post['bank'],
	// 		'nm_coa_bank' => $nm_coa_bank,
	// 		'mata_uang' => $post['mata_uang'],
	// 		'payment_bank' => str_replace(',', '', $post['payment_bank']),
	// 		'total_payment' => $post['total_payment'],
	// 		'selisih' => ($post['total_payment'] - $payment_bank),
	// 		'status' => 2,
	// 		'link_doc' => $filenames,
	// 		'id_supplier' => $post['supplier_input'],
	// 		'nm_supplier' => $post['nm_supplier_input'],
	// 		'kurs_payment' => str_replace(',', '', $post['kurs_payment'])
	// 	]);
	// 	if (!$update_payment1) {
	// 		print_r($this->db->error($update_payment1));
	// 		exit;
	// 	}

	// 	if (!empty($post['dt'])) {
	// 		foreach ($post['dt'] as $detail) {
	// 			$tipe_pph = isset($detail['tipe_pph']) ? $detail['tipe_pph'] : null;

	// 			$this->db->where('id', $detail['id_payment']);
	// 			$update_payment_detail = $this->db->update('payment_approve', [
	// 				'total_ppn' => str_replace(',', '', $detail['nilai_ppn']),
	// 				'total_pph' => str_replace(',', '', $detail['nilai_pph']),
	// 				'tipe_pph' => $tipe_pph
	// 			]);

	// 			$kurs_invoice = $detail['kurs_invoice'];
	// 			if (!$update_payment_detail) {
	// 				print_r($this->db->error($update_payment_detail));
	// 				exit;
	// 			}
	// 		}
	// 	}

	// 	$arr_jurnal = [];
	// 	if (isset($post['jurnal_ls'])) {
	// 		$no_jurnal = 1;
	// 		foreach ($post['jurnal_ls'] as $item_jurnal) {
	// 			$id_jurnal = $this->Pembayaran_material_model->generate_id_invoice_jurnal($no_jurnal);

	// 			$arr_jurnal[] = [
	// 				'no_jurnal' => $id_jurnal,
	// 				'tgl_jurnal' => date('Y-m-d'),
	// 				'tipe' => $item_jurnal['tipe'],
	// 				'coa' => $item_jurnal['coa'],
	// 				'nm_coa' => $item_jurnal['nm_coa'],
	// 				'debit' => $item_jurnal['debit'],
	// 				'kredit' => $item_jurnal['kredit'],
	// 				'keterangan' => $item_jurnal['keterangan'],
	// 				'no_transaksi' => $id_payment_paid,
	// 				'jenis_transaksi' => 'Payment',
	// 				'created_by' => $this->auth->user_id(),
	// 				'created_date' => date('Y-m-d')
	// 			];
	// 			$no_jurnal++;
	// 		}
	// 	}

	// 	if (!empty($arr_jurnal)) {
	// 		$insert_jurnal = $this->db->insert_batch('tr_jurnal', $arr_jurnal);
	// 		if (!$insert_jurnal) {
	// 			print_r($this->db->error($insert_jurnal));
	// 			exit;
	// 		}
	// 	}

	// 	if ($this->db->trans_status() === false) {
	// 		$this->db->trans_rollback();
	// 		$valid = 0;
	// 		$pesan = 'Maaf, data gagal dibayar !';
	// 	} else {
	// 		$this->db->trans_commit();
	// 		$valid = 1;
	// 		$pesan = 'Selamat, data telah berhasil dibayar !';
	// 	}

	// 	echo json_encode([
	// 		'status' => $valid,
	// 		'pesan' => $pesan
	// 	]);
	// }

	public function save_payment()
	{
		$post = $this->input->post();

		// -------- helper parsing uang (format: 880,888.00) --------
		$money = function ($v) {
			if ($v === null || $v === '') return 0.0;
			// kasus umum kamu: ribuan pakai koma, desimal titik
			$v = str_replace(',', '', (string)$v);
			return (float)$v;
		};

		// -------- parse input --------
		$ids = array_values(array_filter(array_map('trim', explode(',', ($post['id_payment'] ?? '')))));
		if (empty($ids)) {
			echo json_encode(['status' => 0, 'pesan' => 'ID payment kosong.']);
			return;
		}

		// $tgl_bayar            = $post['tgl_bayar'] ?? date('Y-m-d');
		$bank_coa             = $post['bank'] ?? '';
		$payment_bank         = $money($post['payment_bank'] ?? 0);          // transfer utama
		$payment_bank_charge  = $money($post['payment_bank_charge'] ?? 0);   // transfer admin
		$bank_charge          = $money($post['bank_charge'] ?? 0);           // biaya admin (debit biaya)
		$kurs_payment         = $money($post['kurs_payment'] ?? 0);
		$mata_uang            = $post['mata_uang'] ?? null;
		$keterangan_pembayaran = $post['keterangan_pembayaran'] ?? null;

		$supplier_id   = $post['supplier_input'] ?? null;
		$supplier_name = $post['nm_supplier_input'] ?? null;

		if ($bank_coa === '') {
			echo json_encode(['status' => 0, 'pesan' => 'COA bank wajib diisi.']);
			return;
		}

		// -------- ambil COA bank --------
		$get_coa_bank = $this->db->get_where(DBACC . '.coa_master', ['no_perkiraan' => $bank_coa])->row();
		$nm_coa_bank  = $get_coa_bank->nama ?? '';
		$kode_bank    = $get_coa_bank->no_perkiraan ?? $bank_coa;

		// -------- ambil baris detail yang dibayar (2 dokumen / dst) --------
		$this->db->select('id, tipe, no_doc, jumlah, tgl_bayar');
		$this->db->from('payment_approve');
		$this->db->where_in('id', $ids);
		$rows = $this->db->get()->result();

		if (empty($rows)) {
			echo json_encode(['status' => 0, 'pesan' => 'Data payment_approve tidak ditemukan.']);
			return;
		}

		if (!empty($post['tgl_bayar'])) {
			$tgl_bayar = $post['tgl_bayar'];
		} else {
			$tgl_bayar = (!empty($rows[0]->tgl_bayar)) ? $rows[0]->tgl_bayar : date('Y-m-d');
		}

		$total_doc = 0;
		foreach ($rows as $r) $total_doc += (float)$r->jumlah;

		// OPTIONAL: validasi agar user tidak salah input payment_bank
		if (abs($total_doc - $payment_bank) > 0.5) {
			echo json_encode([
				'status' => 0,
				'pesan'  => 'Nilai Payment Bank tidak sama dengan total dokumen. Total dokumen: ' . number_format($total_doc, 0, ',', '.') .
					' | Payment Bank: ' . number_format($payment_bank, 0, ',', '.')
			]);
			return;
		}

		// kontrol batch (debit = total_doc + bank_charge ; kredit = payment_bank + payment_bank_charge)
		$selisih_total = ($total_doc + $bank_charge) - ($payment_bank + $payment_bank_charge);

		// -------- upload --------
		$config['upload_path']   = 'uploads/expense/';
		$config['allowed_types'] = '*';
		$config['remove_spaces'] = TRUE;
		$config['encrypt_name']  = TRUE;

		$filenames = '';
		if (!empty($_FILES['upload_doc']['name'])) {
			$_FILES['file']['name']     = $_FILES['upload_doc']['name'];
			$_FILES['file']['type']     = $_FILES['upload_doc']['type'];
			$_FILES['file']['tmp_name'] = $_FILES['upload_doc']['tmp_name'];
			$_FILES['file']['error']    = $_FILES['upload_doc']['error'];
			$_FILES['file']['size']     = $_FILES['upload_doc']['size'];

			$this->upload->initialize($config);
			if ($this->upload->do_upload('file')) {
				$uploadData = $this->upload->data();
				$filenames = $uploadData['file_name'];
			}
		}

		// -------- transaksi --------
		$this->db->trans_begin();

		try {
			// 1) buat ID header batch
			$id_payment_paid = $this->Pembayaran_material_model->generate_id_payment_paid($kode_bank, $tgl_bayar);

			// 2) insert HEADER (1x)
			$ok = $this->db->insert('tr_payment_paid', [
				'id'                  => $id_payment_paid,
				'tgl_bayar'           => $tgl_bayar,
				'coa_bank'            => $bank_coa,
				'nm_coa_bank'         => $nm_coa_bank,

				'mata_uang'           => $mata_uang,
				'kurs_payment'        => $kurs_payment,

				'payment_bank'        => $payment_bank,
				'payment_bank_charge' => $payment_bank_charge,
				'bank_charge'         => $bank_charge,

				'total_doc'           => $total_doc,
				'selisih_total'       => $selisih_total,

				'keterangan_pembayaran' => $keterangan_pembayaran,
				'supplier'            => $supplier_id,
				'nm_supplier'         => $supplier_name,
				'link_doc'            => $filenames,

				'created_by'          => $this->auth->user_id(),
				'created_on'          => date('Y-m-d H:i:s'),
			]);
			if (!$ok) throw new Exception('Insert tr_payment_paid gagal: ' . json_encode($this->db->error()));

			// 3) update DETAIL batch metadata (banyak baris)
			$update = [
				'id_payment'            => $id_payment_paid,
				'tgl_bayar'             => $tgl_bayar,
				'keterangan_pembayaran' => $keterangan_pembayaran,
				'coa_bank'              => $bank_coa,
				'nm_coa_bank'           => $nm_coa_bank,
				'mata_uang'             => $mata_uang,
				'kurs_payment'          => $kurs_payment,
				'status'                => 2,
				'link_doc'              => $filenames,
			];

			// jangan overwrite supplier kalau memang kosong di form (karena expense bisa tanpa supplier)
			if (!empty($supplier_id)) {
				$update['supplier']    = $supplier_id;
				$update['id_supplier'] = $supplier_id;
				$update['nm_supplier'] = $supplier_name;
			}

			$this->db->where_in('id', $ids);
			$ok = $this->db->update('payment_approve', $update);
			if (!$ok) throw new Exception('Update payment_approve batch gagal: ' . json_encode($this->db->error()));

			// 4) set nilai PER BARIS (ini yang mencegah dobel di report)
			foreach ($rows as $r) {
				$this->db->where('id', $r->id);
				$ok = $this->db->update('payment_approve', [
					'total_payment' => (float)$r->jumlah,
					'payment_bank'  => (float)$r->jumlah,
					'selisih'       => 0,
				]);
				if (!$ok) throw new Exception('Update payment_approve line gagal: ' . json_encode($this->db->error()));
			}

			// 5) update pajak per baris (dt)
			if (!empty($post['dt']) && is_array($post['dt'])) {
				foreach ($post['dt'] as $detail) {
					$id_line = $detail['id_payment'] ?? null;
					if (!$id_line) continue;

					$tipe_pph = $detail['tipe_pph'] ?? null;

					$this->db->where('id', $id_line);
					$ok = $this->db->update('payment_approve', [
						'total_ppn' => $money($detail['nilai_ppn'] ?? 0),
						'total_pph' => $money($detail['nilai_pph'] ?? 0),
						'tipe_pph'  => $tipe_pph
					]);
					if (!$ok) throw new Exception('Update tax gagal: ' . json_encode($this->db->error()));
				}
			}

			// 6) insert jurnal batch (1x) => no_transaksi = id_payment_paid
			//    Sekaligus POSTING ke GL (japh + jurnal) dan tr_kartu_hutang,
			//    agar tidak perlu lagi langkah "Submit Jurnal" terpisah.
			if (!empty($post['jurnal_ls']) && is_array($post['jurnal_ls'])) {
				$arr_jurnal = [];
				$no_jurnal  = 1;

				// Nomor Bukti Jurnal (JV) GL untuk header japh + detail jurnal.
				// Dibentuk INLINE dengan logika IDENTIK get_no_buk() milik modul
				// jurnal_nomor (yang dipakai proses "Simpan Payment Jurnal"), supaya
				// formatnya konsisten 101BK<yy><nobuk> (bukan BKA). Tidak memakai
				// $this->Jurnal_model karena nama kelas Jurnal_model bentrok antar
				// modul di HMVC (definisi kelas yang ter-include duluan yang menang),
				// sehingga hasilnya bisa berubah-ubah tergantung urutan load.
				$Nomor_JV = null;
				$row_cab = $this->db->query("SELECT nocab, subcab, nobuk FROM " . DBACC . ".pastibisa_tb_cabang WHERE nocab = '101' ORDER BY id")->row();
				if ($row_cab) {
					$kd_cab = $row_cab->nocab;
					$kd_buk = (int) $row_cab->nobuk + 1;
					if (strlen((string) $kd_buk) == 1) {
						$kdadd = "0000";
					} else if (strlen((string) $kd_buk) == 2) {
						$kdadd = "000";
					} else if (strlen((string) $kd_buk) == 3) {
						$kdadd = "00";
					} else if (strlen((string) $kd_buk) == 4) {
						$kdadd = "0";
					} else {
						$kdadd = "0";
					}
					$Nomor_JV = $kd_cab . "B" . "K" . date("y") . $kdadd . $kd_buk;
				}
				if (!$Nomor_JV) throw new Exception('Gagal membuat Nomor JV: data cabang 101 tidak ditemukan.');

				// COA yang dianggap Hutang Dagang -> ikut tercatat di tr_kartu_hutang
				$coa_hutang_dagang = array('2101-01-01', '2101-01-02', '2101-01-04');

				$total_debit_jurnal = 0;

				foreach ($post['jurnal_ls'] as $item) {
					$id_jurnal   = $this->Pembayaran_material_model->generate_id_invoice_jurnal($no_jurnal);
					$coa_line    = $item['coa'];
					$ket_line    = $item['keterangan'];
					$tgl_line    = $item['tanggal_jurnal'] ?? $tgl_bayar;
					$tipe_line   = $item['tipe'];
					$debit_line  = $money($item['debit'] ?? 0);
					$kredit_line = $money($item['kredit'] ?? 0);

					// 6a) draft jurnal pembayaran (tr_jurnal) -> langsung sts=1 (sudah terposting)
					$arr_jurnal[] = [
						'no_jurnal'       => $id_jurnal,
						'tgl_jurnal'      => $tgl_line, // pakai dari post
						'tipe'            => $tipe_line,
						'coa'             => $coa_line,
						'nm_coa'          => $item['nm_coa'],
						'debit'           => $debit_line,
						'kredit'          => $kredit_line,
						'keterangan'      => $ket_line,
						'no_transaksi'    => $id_payment_paid,
						'jenis_transaksi' => 'Payment',
						'sts'             => '1',
						'created_by'      => $this->auth->user_id(),
						'created_date'    => date('Y-m-d'),
					];

					$total_debit_jurnal += $debit_line;

					// 6b) detail jurnal GL
					$ok = $this->db->insert(DBACC . '.jurnal', [
						'tipe'         => $tipe_line,
						'nomor'        => $Nomor_JV,
						'tanggal'      => $tgl_line,
						'no_reff'      => $id_payment_paid,
						'no_perkiraan' => $coa_line,
						'keterangan'   => $ket_line,
						'debet'        => $debit_line,
						'kredit'       => $kredit_line,
						'created_on'   => date('Y-m-d H:i:s'),
						'created_by'   => $this->auth->user_id(),
					]);
					if (!$ok) throw new Exception('Insert jurnal GL gagal: ' . json_encode($this->db->error()));

					// 6c) jika COA Hutang Dagang, catat ke tr_kartu_hutang.
					//     Pada pembayaran hutang berkurang => nilai masuk sebagai debet.
					if (in_array($coa_line, $coa_hutang_dagang) && ($debit_line > 0 || $kredit_line > 0)) {
						$ok = $this->db->insert('tr_kartu_hutang', [
							'tipe'          => $tipe_line,
							'nomor'         => $Nomor_JV,
							'tanggal'       => $tgl_line,
							'no_perkiraan'  => $coa_line,
							'keterangan'    => $ket_line,
							'no_reff'       => $id_payment_paid,
							'debet'         => $debit_line,
							'kredit'        => $kredit_line,
							'id_supplier'   => $supplier_id,
							'nama_supplier' => $supplier_name,
							'no_request'    => $id_payment_paid,
						]);
						if (!$ok) throw new Exception('Insert tr_kartu_hutang gagal: ' . json_encode($this->db->error()));
					}

					$no_jurnal++;
				}

				$ok = $this->db->insert_batch('tr_jurnal', $arr_jurnal);
				if (!$ok) throw new Exception('Insert jurnal gagal: ' . json_encode($this->db->error()));

				// 6d) header jurnal GL (japh) 1x
				$ok = $this->db->insert(DBACC . '.japh', [
					'nomor'      => $Nomor_JV,
					'tgl'        => $tgl_bayar,
					'jml'        => $total_debit_jurnal,
					'kdcab'      => '101',
					'jenis_reff' => 'BUK',
					'no_reff'    => $id_payment_paid,
					'jenis_ap'   => 'V',
					'note'       => 'Payment ' . $id_payment_paid,
					'user_id'    => $this->auth->user_name(),
					'ho_valid'   => '',
					'batal'      => '0',
				]);
				if (!$ok) throw new Exception('Insert japh GL gagal: ' . json_encode($this->db->error()));

				// 6e) naikkan counter nomor bukti cabang
				$this->db->query("UPDATE " . DBACC . ".pastibisa_tb_cabang SET nobuk = nobuk + 1 WHERE nocab = '101'");
			}

			if ($this->db->trans_status() === FALSE) {
				throw new Exception('Trans status false');
			}

			$this->db->trans_commit();
			echo json_encode([
				'status' => 1,
				'pesan'  => 'Selamat, data telah berhasil dibayar !',
				'id_payment_paid' => $id_payment_paid,
				'total_doc' => $total_doc,
				'selisih_total' => $selisih_total
			]);
			return;
		} catch (Exception $e) {
			$this->db->trans_rollback();
			echo json_encode([
				'status' => 0,
				'pesan'  => 'Maaf, data gagal dibayar ! ' . $e->getMessage()
			]);
			return;
		}
	}


	public function used_choosed_payment()
	{
		$this->db->trans_start();

		$this->db->delete('tr_choosed_payment', ['id_user' => $this->auth->user_id()]);

		if ($this->db->trans_status() === false) {
			$this->db->trans_rollback();
		} else {
			$this->db->trans_commit();
		}
	}

	public function get_list_req_payment()
	{
		$this->Pembayaran_material_model->get_list_req_payment();
	}

	public function set_jurnal()
	{
		$this->Pembayaran_material_model->set_jurnal();
	}

	public function set_jurnal_refill()
	{
		$this->Pembayaran_material_model->set_jurnal_refill();
	}
}
