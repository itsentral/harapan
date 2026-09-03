<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Dompdf\Dompdf;

class Penerimaan_cash extends Admin_Controller
{

	protected $viewPermission   = 'Penerimaan_Uang_Cash.View';
	protected $addPermission    = 'Penerimaan_Uang_Cash.Add';
	protected $managePermission = 'Penerimaan_Uang_Cash.Manage';
	protected $deletePermission = 'Penerimaan_Uang_Cash.Delete';

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array(
			'Penerimaan_cash/master_model',
			'Penerimaan_cash/penerimaan_cash_model',
			'Penerimaan_cash/All_model',
			'Penerimaan_cash/Jurnal_model',
			'Penerimaan_cash/Acc_model'
		));

		date_default_timezone_set('Asia/Bangkok');
	}

	public function index()
	{
		$this->template->page_icon('fa fa-money');
		$this->template->title('Penerimaan Uang Cash');
		$this->template->render('list_payment_cash');
	}

	public function data_side_penerimaan_cash()
	{
		$this->penerimaan_cash_model->get_data_json_payment_cash();
	}

	public function add()
	{
		$user_id = $this->auth->user_id();

		// Ambil daftar customer dari invoice yang masih aktif
		$this->db->select('c.id_customer, c.name_customer, c.npwp, c.telephone, c.fax, c.address_office, a.id_so, c.id_karyawan as id_sales');
		$this->db->from('tr_invoice_sales a');
		// $this->db->join('sales_order b', 'b.no_so = a.id_so', 'left');
		$this->db->join('master_customers c', 'c.id_customer = a.id_customer', 'left');
		$this->db->join('users u', 'u.employee_id = c.id_karyawan', 'left');
		$this->db->where('c.deleted_by IS NULL');
		$this->db->where('a.sts', 1);
		if ($user_id != 7 && $user_id != 114) {
			$this->db->where('u.id_user', $user_id);
		}
		$this->db->group_by('c.id_customer');
		$customers = $this->db->get()->result();

		$data = [
			'customers' => $customers
		];

		$this->template->title('Add Penerimaan Uang Cash');
		$this->template->page_icon('fa fa-money');
		$this->template->render('form_penerimaan_cash', $data);
	}

	public function get_inv()
	{
		$id_customer = $this->input->get('id_customer', TRUE);

		$rows = $this->db
			->select('
            i.id_invoice,
            i.id_so,
            i.tipe_so,
            i.id_penawaran,
            i.id_billing,
            i.tipe_billing,
            i.nilai_dpp,
            i.nilai_asli,
            i.nilai_invoice,
            i.persen_invoice,
            i.ppn,
            i.nilai_ppn,
            i.grand_total,
			(i.grand_total - IFNULL(bayar.total_bayar, 0)) as sisa_tagihan,
            DATE_FORMAT(i.created_on, "%d/%b/%Y") as tgl_inv,
            DATE_FORMAT(i.tgl_so, "%d/%b/%Y") as tgl_so,
            c.name_customer,
            IFNULL(cn.jumlah_cn, 0) as jumlah_cn,
            IFNULL(cn.total_nilai_cn, 0) as total_nilai_cn
        ')
			->from('tr_invoice_sales i')
			->join('master_customers c', 'c.id_customer = i.id_customer', 'left')
			->where('i.id_customer', $id_customer)
			->where('i.sts', 1)
			->where('(i.is_cancel IS NULL OR i.is_cancel = 2)', null, false)
			->join('(SELECT no_invoice, SUM(total_bayar_idr) as total_bayar 
         FROM tr_invoice_payment_detail 
         GROUP BY no_invoice) bayar', 'bayar.no_invoice = i.id_invoice', 'left')
			->join('(SELECT id_invoice, COUNT(*) as jumlah_cn, SUM(total_harga) as total_nilai_cn
         FROM tr_retur
         GROUP BY id_invoice) cn', 'cn.id_invoice = i.id_invoice', 'left')
			->where('(i.grand_total > IFNULL(bayar.total_bayar, 0))', null, false)
			->order_by('i.created_on', 'ASC')
			->get()
			->result_array();

		// Sertakan detail CN per invoice agar bisa ditampilkan inline
		foreach ($rows as &$inv) {
			if ((int)$inv['jumlah_cn'] > 0) {
				$inv['cn_rows'] = $this->db
					->select('no_retur, tgl_retur, total_harga as nilai_retur, nilai_inv_baru, alasan')
					->from('tr_retur')
					->where('id_invoice', $inv['id_invoice'])
					->where('status', 2)
					->where('used_in_invoice IS NULL', null, false)  // hanya CN yang belum dipakai
					->order_by('tgl_retur', 'ASC')
					->get()->result_array();
			} else {
				$inv['cn_rows'] = [];
			}
		}
		unset($inv);

		echo json_encode($rows);
	}

	// public function save_tanpa_otp()
	// {
	// 	$post = $this->input->post();

	// 	$tgl_pembayaran = $post['tgl_pembayaran'];
	// 	$id_customer = $post['id_customer'];
	// 	$detail = $post['detail'];
	// 	$total_invoice = str_replace(",", "", $post['total_invoice']);
	// 	$total_terima = str_replace(",", "", $post['total_terima']);
	// 	$id_invoices = array_column($detail, 'id_invoice');
	// 	$invoice_string = implode(', ', $id_invoices);

	// 	$kd_pembayaran = $this->penerimaan_cash_model->generate_nopn($tgl_pembayaran);
	// 	$customer = $this->db->get_where('master_customers', ['id_customer' => $id_customer])->row();

	// 	// Simpan header langsung (tanpa OTP)
	// 	$header = [
	// 		'kd_pembayaran' => $kd_pembayaran,
	// 		'tgl_pembayaran' => $tgl_pembayaran,
	// 		'no_invoice' => $invoice_string,
	// 		'id_customer' => $id_customer,
	// 		'nm_customer' => $customer->name_customer,
	// 		'jumlah_piutang_idr' => $total_invoice,
	// 		'jumlah_pembayaran_idr' => $total_terima,
	// 		'keterangan' => $post['ket_bayar'],
	// 		'created_by' => $this->auth->user_id(),
	// 		'created_on' => date('Y-m-d H:i:s'),
	// 		'tipe_bayar' => "CASH"
	// 	];

	// 	$this->db->insert('tr_invoice_payment', $header);

	// 	// Simpan detail
	// 	foreach ($detail as $row) {
	// 		$invoice = $this->db->get_where('tr_invoice_sales', ['id_invoice' => $row['id_invoice']])->result();
	// 		$total_bayar = floatval(str_replace(',', '', $row['total_bayar']));
	// 		$tagihan = floatval(str_replace(',', '', $row['tagihan']));
	// 		$sisa_invoice = floatval(str_replace(',', '', $row['sisa_invoice']));

	// 		foreach ($invoice as $inv) {
	// 			$data_detail = [
	// 				'kd_pembayaran' => $kd_pembayaran,
	// 				'no_invoice' => $row['id_invoice'],
	// 				'no_ipp' => $row['id_so'],
	// 				'so_number' => $row['id_so'],
	// 				'tgl_invoice' => date('Y-m-d', strtotime($inv->created_on)),
	// 				'total_ppn_idr' => $inv->nilai_ppn,
	// 				'total_invoice_idr' => $tagihan,
	// 				'total_bayar_idr' => $total_bayar,
	// 				'sisa_invoice_idr' => $sisa_invoice,
	// 				'id_customer' => $header['id_customer'],
	// 				'nm_customer' => $header['nm_customer'],
	// 				'created_by' => $this->auth->user_id(),
	// 				'created_on' => date('Y-m-d H:i:s'),
	// 				'tipe_bayar' => "CASH"
	// 			];

	// 			$this->db->insert('tr_invoice_payment_detail', $data_detail);

	// 			// Rekap ulang total_bayar dari detail
	// 			$sum = $this->db->select('COALESCE(SUM(total_bayar_idr),0) AS total', false)
	// 				->from('tr_invoice_payment_detail')
	// 				->where('no_invoice', $row['id_invoice'])
	// 				->get()->row()->total;

	// 			// Update header: total_bayar, piutang, dan status
	// 			$this->db->set('total_bayar', $sum, false);
	// 			$this->db->set('piutang', "GREATEST(COALESCE(piutang,0) - {$sum}, 0)", false);
	// 			$this->db->set('sts', "CASE WHEN {$sum} >= COALESCE(piutang,0) THEN 0 ELSE 1 END", false);
	// 			$this->db->where('id_invoice', $row['id_invoice'])->update('tr_invoice_sales');
	// 		}
	// 	}

	// 	$kd_bayar  = $kd_pembayaran;
	// 	$this->appr_jurnal($kd_bayar);

	// 	echo json_encode([
	// 		'status' => 1,
	// 		'message' => 'Pembayaran berhasil disimpan.',
	// 		'redirect_url' => base_url("penerimaan_cash/print_struk/$kd_pembayaran")
	// 	]);
	// }

	public function save()
	{
		$post = $this->input->post();
		// echo '<pre>';
		// print_r($post);
		// echo '</pre>';
		// die();

		$tgl_pembayaran = $post['tgl_pembayaran'];
		$id_customer    = $post['id_customer'];
		$detail         = $post['detail'];

		$total_invoice  = str_replace(",", "", $post['total_invoice']);
		$total_terima   = floatval(str_replace(",", "", $post['total_terima']));

		if ($total_terima <= 0) {
			echo json_encode([
				'status' => 0,
				'message' => 'Total penerimaan tidak boleh 0. Silahkan isi nominal pembayaran.'
			]);
			return;
		}

		$id_invoices    = array_column($detail, 'id_invoice');
		$invoice_string = implode(', ', $id_invoices);

		$kd_pembayaran  = $this->penerimaan_cash_model->generate_nopn($tgl_pembayaran);
		$customer       = $this->db->get_where('master_customers', ['id_customer' => $id_customer])->row();

		// OTP
		$otp_code   = rand(100000, 999999);
		$otp_expiry = date('Y-m-d H:i:s', strtotime('+3 minutes'));

		// 🔥 SIMPAN SEMUA TERMASUK JURNAL
		$otp_data = [
			'kd_pembayaran' => $kd_pembayaran,
			'id_customer'   => $id_customer,
			'otp_code'      => $otp_code,
			'expired_at'    => $otp_expiry,
			'data_json'     => json_encode([
				'header' => [
					'tgl_pembayaran'        => $tgl_pembayaran,
					'no_invoice'            => $invoice_string,
					'id_customer'           => $id_customer,
					'nm_customer'           => $customer->name_customer,
					'jumlah_piutang_idr'    => $total_invoice,
					'jumlah_pembayaran_idr' => $total_terima,
					'keterangan'            => $post['ket_bayar'],
					'created_by'            => $this->auth->user_id(),
					'created_on'            => date('Y-m-d H:i:s'),
					'tipe_bayar'            => "CASH"
				],
				'detail' => $detail,

				// 🔥 INI KUNCI
				'jurnal' => [
					'tgl_jurnal' => $post['tgl_jurnal'],
					'type'       => $post['type'],
					'no_coa'     => $post['no_coa'],
					'nama_coa'   => $post['nama_coa'],
					'keterangan' => $post['keterangan'],
					'debet'      => $post['debet'],
					'kredit'     => $post['kredit'],
				]
			])
		];

		$this->db->insert('tr_invoice_payment_otp', $otp_data);

		// Kirim OTP via WhatsApp API Gateway
		$wa_number = preg_replace('/^0/', '62', $customer->telephone); // convert 08xxx → 628xxx
		$otp_message = "Terimakasih telah melakukan pembayaran sejumlah Rp. *$total_terima* \n\nKode OTP untuk verifikasi pembayaran Anda adalah: *$otp_code*\n\nKode ini berlaku hingga " . date('H:i', strtotime($otp_expiry)) . " WIB.\n\nJangan bagikan kode ini ke siapa pun.";

		$response = $this->send_wa($wa_number, $otp_message);

		echo json_encode([
			'status' => 1,
			'message' => 'OTP dikirim',
			'kd_pembayaran' => $kd_pembayaran,
			'response' => $response
		]);
	}

	function send_wa($number, $message)
	{
		$url = 'https://app.whacenter.com/api/send';

		$data = [
			//'device_id' => '34ee66bd167985a7317a49f60498db55', //punya harapan
			//'device_id' => '532c60ddc0f2c1184b396488c804413e', //punya harapan 8001
			'device_id' => 'fa9df5e220c9436b7ecac99c737bd4e6', //harapan 998
			'number' => $number, // format: 628xxx
			'message' => $message
		];

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	public function verify_otp()
	{
		$post = $this->input->post();
		$kd   = $post['kd_pembayaran'];
		$otp  = $post['otp_code'];

		$otp_data = $this->db->get_where('tr_invoice_payment_otp', [
			'kd_pembayaran' => $kd,
			'otp_code'      => $otp
		])->row();

		if (!$otp_data || strtotime($otp_data->expired_at) < time()) {
			echo json_encode(['status' => 0, 'message' => 'OTP salah / expired']);
			return;
		}

		$decoded = json_decode($otp_data->data_json, true); // WAJIB TRUE
		$header  = $decoded['header'];
		$detail  = $decoded['detail'];
		$jurnal  = $decoded['jurnal'];

		$this->db->trans_begin();

		try {

			// =========================
			// INSERT HEADER
			// =========================
			$header['kd_pembayaran'] = $kd;
			$this->db->insert('tr_invoice_payment', $header);

			// =========================
			// INSERT DETAIL + UPDATE INV
			// =========================
			foreach ($detail as $row) {

				$inv = $this->db
					->get_where('tr_invoice_sales', ['id_invoice' => $row['id_invoice']])
					->row();

				if (!$inv) {
					throw new Exception("Invoice tidak ditemukan: " . $row['id_invoice']);
				}

				$total_bayar  = floatval(str_replace(',', '', $row['total_bayar']));
				$tagihan      = floatval(str_replace(',', '', $row['tagihan']));
				$sisa_invoice = floatval(str_replace(',', '', $row['sisa_invoice']));
				$total_cn     = isset($row['total_cn']) ? (float)$row['total_cn'] : 0;

				$this->db->insert('tr_invoice_payment_detail', [
					'kd_pembayaran'      => $kd,
					'no_invoice'         => $row['id_invoice'],
					'no_ipp'             => $row['id_so'],
					'so_number'          => $row['id_so'],
					'tgl_invoice'        => date('Y-m-d', strtotime($inv->created_on)),
					'total_ppn_idr'      => $inv->nilai_ppn,
					'total_invoice_idr'  => $tagihan,
					'total_bayar_idr'    => $total_bayar,
					'sisa_invoice_idr'   => $sisa_invoice,
					'total_cn_idr'       => $total_cn,
					'id_customer'        => $header['id_customer'],
					'nm_customer'        => $header['nm_customer'],
					'created_by'         => $this->auth->user_id(),
					'created_on'         => date('Y-m-d H:i:s'),
					'tipe_bayar'         => "CASH"
				]);

				// Tandai CN yang digunakan
				if (!empty($row['cn']) && is_array($row['cn'])) {
					foreach ($row['cn'] as $cn_item) {
						if (!empty($cn_item['no_retur'])) {
							$this->db->update('tr_retur', [
								'used_in_invoice' => $kd,
								'used_date'       => date('Y-m-d'),
							], ['no_retur' => $cn_item['no_retur']]);
						}
					}
				}

				// update invoice — sisa piutang = grand_total - total bayar - total CN
				$sum_bayar = $this->db->select('COALESCE(SUM(total_bayar_idr),0) AS total', false)
					->from('tr_invoice_payment_detail')
					->where('no_invoice', $row['id_invoice'])
					->get()->row()->total;

				$sum_cn = $this->db->select('COALESCE(SUM(total_cn_idr),0) AS total', false)
					->from('tr_invoice_payment_detail')
					->where('no_invoice', $row['id_invoice'])
					->get()->row()->total;

				$sisa_piutang = (float)$inv->grand_total - (float)$sum_bayar - (float)$sum_cn;
				if ($sisa_piutang < 0) $sisa_piutang = 0;

				$this->db->set('total_bayar', $sum_bayar, false);
				$this->db->set('piutang', $sisa_piutang, false);
				$this->db->set('sts', "CASE WHEN {$sisa_piutang} <= 0 THEN 0 ELSE 1 END", false);
				$this->db->where('id_invoice', $row['id_invoice']);
				$this->db->update('tr_invoice_sales');
			}
			// =========================
			// JURNAL 🔥
			// =========================
			$this->appr_jurnal($kd, $jurnal, $header, $detail);

			// hapus OTP
			$this->db->delete('tr_invoice_payment_otp', ['kd_pembayaran' => $kd]);

			if ($this->db->trans_status() === FALSE) {
				throw new Exception("DB Error");
			}

			$this->db->trans_commit();

			echo json_encode([
				'status' => 1,
				'message' => 'Verifikasi berhasil. Pembayaran disimpan.',
				'redirect_url' => base_url("penerimaan_cash/print_struk/$kd")
			]);
		} catch (Exception $e) {

			$this->db->trans_rollback();

			echo json_encode([
				'status' => 0,
				'message' => $e->getMessage()
			]);
		}
	}

	function appr_jurnal($kd_bayar, $jurnal, $header, $detail)
	{
		$session = $this->session->userdata('app_session');

		$customer = $this->db
			->select('c.name_customer, c.id_karyawan, e.nm_karyawan')
			->from('master_customers c')
			->join('employee e', 'e.id = c.id_karyawan', 'left')
			->where('c.id_customer', $header['id_customer'])
			->get()
			->row();
		//AMBIL NO INVOICE DARI DETAIL
		$arrInv = array_column($detail, 'id_invoice');
		$invString = implode(', ', $arrInv);
		$note = 'PEMBAYARAN PIUTANG INVOICE ' . $invString . ' A/N ' . $customer->name_customer;

		$Nomor_JV       = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', $header['tgl_pembayaran']);


		// VALIDASI BALANCE
		if (array_sum($jurnal['debet']) != array_sum($jurnal['kredit'])) {
			throw new Exception("Jurnal tidak balance");
		}

		// HEADER
		$this->db->insert(DBACC . '.jarh', [
			'nomor'         => $Nomor_JV,
			'kd_pembayaran' => $kd_bayar,
			'tgl'           => $header['tgl_pembayaran'],
			'jml'           => array_sum($jurnal['debet']),
			'kdcab'         => '101',
			'jenis_reff'    => $kd_bayar,
			'no_reff'       => $kd_bayar,
			'customer'      => $header['nm_customer'],
			'note'          => $note,
			'jenis_ar'      => 'V',
			'terima_dari'   => '-',
			'valid'         => $session['id_user'],
			'tgl_valid'     => $header['tgl_pembayaran'],
			'user_id'       => $session['id_user'],
			'tgl_invoice'   => $header['tgl_pembayaran'],
			'batal'         => 0,
		]);

		// DETAIL
		$arrJurnal = [];

		for ($i = 0; $i < count($jurnal['no_coa']); $i++) {
			$arrJurnal[] = [
				'nomor'         => $Nomor_JV,
				'tanggal'       => $jurnal['tgl_jurnal'][$i],
				'tipe'          => $jurnal['type'][$i],
				'no_perkiraan'  => $jurnal['no_coa'][$i],
				'keterangan'    => $jurnal['keterangan'][$i] . " A/n " . $customer->name_customer,
				'no_reff'       => $kd_bayar,
				'debet'         => floatval($jurnal['debet'][$i]),
				'kredit'        => floatval($jurnal['kredit'][$i]),
				'created_by'    => $this->auth->user_id(),
				'created_on'    => date('Y-m-d H:i:s'),
			];
		}
		$this->db->insert_batch(DBACC . '.jurnal', $arrJurnal);

		// INSERT KARTU PIUTANG
		foreach ($detail as $row) {

			$total_bayar = floatval(str_replace(',', '', $row['total_bayar']));
			$total_cn    = isset($row['total_cn']) ? (float)$row['total_cn'] : 0;

			// Kredit piutang dari pembayaran cash
			if ($total_bayar > 0) {
				$this->db->insert('tr_kartu_piutang', [
					'tipe'          => 'JV',
					'nomor'         => $Nomor_JV,
					'tanggal'       => $header['tgl_pembayaran'],
					'no_perkiraan'  => '1102-01-01',
					'keterangan'    => 'PEMBAYARAN PIUTANG INV ' . $row['id_invoice'] . ' A/n ' . $customer->name_customer,
					'no_reff'       => $row['id_invoice'],
					'debet'         => 0,
					'kredit'        => $total_bayar,
					'id_supplier'   => $header['id_customer'],
					'nama_supplier' => $customer->name_customer,
				]);

				$this->db->insert('tr_kartu_piutang_sales', [
					'tipe'          => 'JV',
					'nomor'         => $Nomor_JV,
					'tanggal'       => $header['tgl_pembayaran'],
					'no_perkiraan'  => '1102-01-04',
					'keterangan'    => 'PENERIMAAN PIUTANG INV ' . $row['id_invoice'] . ' A/n ' . $customer->name_customer,
					'no_reff'       => $row['id_invoice'],
					'debet'         => $total_bayar,
					'kredit'        => 0,
					'id_sales'      => $customer->id_karyawan,
					'nama_sales'    => $customer->nm_karyawan,
				]);
			}

			// Kredit piutang dari CN
			if ($total_cn > 0) {
				$cn_nos = [];
				if (!empty($row['cn']) && is_array($row['cn'])) {
					foreach ($row['cn'] as $cn_item) {
						$cn_nos[] = $cn_item['no_retur'];
					}
				}
				$ket_cn = 'PENGGUNAAN CREDIT NOTE ' . implode(', ', $cn_nos) . ' INV ' . $row['id_invoice'] . ' A/n ' . $customer->name_customer;

				$this->db->insert('tr_kartu_piutang', [
					'tipe'          => 'JV',
					'nomor'         => $Nomor_JV,
					'tanggal'       => $header['tgl_pembayaran'],
					'no_perkiraan'  => '1102-01-01',
					'keterangan'    => $ket_cn,
					'no_reff'       => $row['id_invoice'],
					'debet'         => 0,
					'kredit'        => $total_cn,
					'id_supplier'   => $header['id_customer'],
					'nama_supplier' => $customer->name_customer,
				]);
			}
		}

		// COUNTER
		$this->db->query("UPDATE " . DBACC . ".pastibisa_tb_cabang SET nobum=nobum+1 WHERE nocab='101'");
	}

	public function resend_otp()
	{
		$kd = $this->input->post('kd_pembayaran');

		$otp_data = $this->db->get_where('tr_invoice_payment_otp', ['kd_pembayaran' => $kd])->row();
		if (!$otp_data) {
			echo json_encode(['status' => 0, 'message' => 'Data OTP tidak ditemukan']);
			return;
		}

		$otp_code = rand(100000, 999999);
		$expired_at = date('Y-m-d H:i:s', strtotime('+3 minutes'));

		// Update
		$this->db->update('tr_invoice_payment_otp', [
			'otp_code' => $otp_code,
			'expired_at' => $expired_at
		], ['kd_pembayaran' => $kd]);

		// Ambil customer
		$cust = $this->db->query("
        SELECT c.name_customer, c.telephone 
        FROM tr_invoice_payment_otp t
        JOIN master_customers c ON c.id_customer = t.id_customer
        WHERE t.kd_pembayaran = ?
    ", [$kd])->row();

		if (!$cust) {
			echo json_encode(['status' => 0, 'message' => 'Customer tidak ditemukan']);
			return;
		}

		$nohp = preg_replace('/[^0-9]/', '', $cust->telephone);
		$wa = (substr($nohp, 0, 1) == '0') ? '62' . substr($nohp, 1) : $nohp;
		$msg = "Kode OTP baru Anda: *$otp_code*\n\nBerlaku sampai " . date('H:i', strtotime($expired_at)) . " WIB.";

		$response = $this->send_wa($wa, $msg);

		echo json_encode([
			'status' => 1,
			'message' => 'OTP dikirim',
			'kd_pembayaran' => $kd,
			'response' => $response
		]);
	}

	public function print_struk($kd_pembayaran)
	{
		$header = $this->db
			->select('
						a.*, 
						b.id_invoice, 
						b.grand_total, 
						b.total_bayar, 
						b.piutang, 
						b.freight, 
						b.sts
					')
			->from('tr_invoice_payment a')
			->join('tr_invoice_sales b', 'a.no_invoice = b.id_invoice', 'left')
			->where('a.kd_pembayaran', $kd_pembayaran)
			->get()
			->row();

		$details = $this->db
			->where('kd_pembayaran', $kd_pembayaran)
			->get('tr_invoice_payment_detail')->result();

		$subtotal = 0;
		$freight = 0;
		$total_pembayaran = 0;
		$total_kurang_pembayaran = 0;

		$total_pembayaran_sebelumnya = 0;

		foreach ($details as $d) {
			$pembayaran_sebelumnya = $this->db
				->select_sum('b.total_bayar_idr')
				->from('tr_invoice_payment a')
				->join('tr_invoice_payment_detail b', 'a.kd_pembayaran = b.kd_pembayaran')
				->where('b.no_invoice', $d->no_invoice)
				->where('a.created_on <', $header->created_on)
				->get()
				->row()
				->total_bayar_idr ?? 0;

			$total_pembayaran_sebelumnya += $pembayaran_sebelumnya;
		}

		foreach ($details as $item) {
			$invoice = $this->db
				->select('a.*')
				->from('tr_invoice_sales a')
				->where('a.id_invoice', $item->no_invoice)
				->get()
				->result();

			$total_pembayaran += $item->total_bayar_idr;
			$total_kurang_pembayaran += $item->sisa_invoice_idr;

			foreach ($invoice as $inv) {
				$delivery = $this->db
					->select('a.no_surat_jalan, a.no_delivery, a.no_so')
					->from('spk_delivery a')
					->where('a.no_surat_jalan', $inv->id_billing)
					->get()
					->row();

				if (!$delivery) continue;

				$items = $this->db
					->select('c.price_list, c.harga_penawaran, c.diskon, c.diskon_nilai, a.qty_delivery as qty, c.total_pl, d.ppn')
					->from('spk_delivery_detail a')
					->join('sales_order_detail b', 'b.id = a.id_so_det')
					->join('penawaran_detail c', 'c.id_penawaran = b.id_penawaran AND c.id_product = b.id_product')
					->join('penawaran d', 'd.id_penawaran = c.id_penawaran')
					->where('a.no_delivery', $delivery->no_delivery)
					->get()->result();

				$get_spk_pertama = $this->db
					->order_by('no_delivery', 'ASC')
					->get_where('spk_delivery', ['no_so' => $delivery->no_so])
					->row();

				$is_spk_pertama = ($get_spk_pertama && $get_spk_pertama->no_surat_jalan == $delivery->no_surat_jalan);

				// Jika SPK pertama, ambil freight
				if ($is_spk_pertama) {
					$freight_data = $this->db
						->select('b.freight')
						->from('sales_order a')
						->join('penawaran b', 'b.id_penawaran = a.id_penawaran')
						->where('a.no_so', $delivery->no_so)
						->get()
						->row();

					$freight += $freight_data ? $freight_data->freight : 0;
				}

				foreach ($items as $row) {
					$disc = (float)$row->diskon;
					$total_item = round(($row->price_list * $row->qty) * (1 + ($disc / 100)), -2); // diskon dikurang
					$subtotal += $total_item;
				}
			}
		}

		// Hitung DPP & PPN (jika PPN sudah termasuk)
		$exclude_ppn = ($subtotal + $freight) / 1.11;
		$dpp = ($exclude_ppn * 11) / 12;
		$ppn = ($dpp * 12) / 100;
		$grand_total = $exclude_ppn + $ppn;


		// Kirim ke view
		$html = $this->load->view('struk_penerimaan_cash', [
			'header' => $header,
			'details' => $details,
			'subtotal' => $subtotal,
			'exclude_ppn' => $exclude_ppn,
			'freight' => $freight,
			// 'dpp' => $dpp,
			// 'ppn' => $ppn,
			'total_pembayaran' => $total_pembayaran,
			'total_pembayaran_sebelumnya' => $total_pembayaran_sebelumnya,
			'total_kurang_pembayaran' => $total_kurang_pembayaran,
			// 'grand_total' => $grand_total,
		], true);

		$dompdf = new Dompdf();
		$dompdf->loadHtml($html);
		$dompdf->setPaper([0, 0, 165, 500], 'portrait'); // thermal 58mm
		$dompdf->render();
		$dompdf->stream("STRUK_$kd_pembayaran.pdf", ["Attachment" => false]);
	}

	/**
	 * Batalkan Penerimaan Cash (ADMIN ONLY)
	 * - Pindahkan header dari tr_invoice_payment → tr_invoice_payment_delete
	 * - Pindahkan detail dari tr_invoice_payment_detail → tr_invoice_payment_detail_delete
	 * - Jurnal dibalik (debet jadi kredit, kredit jadi debet)
	 * - Update saldo piutang invoice
	 * - Reset CN yang dipakai
	 */
	public function batalkan_penerimaan()
	{
		// Hanya admin (user_id = 7) yang boleh
		$user_id = $this->auth->user_id();
		if ($user_id != 7) {
			echo json_encode(['status' => 0, 'message' => 'Anda tidak memiliki akses untuk membatalkan penerimaan.']);
			return;
		}

		$kd_pembayaran = $this->input->post('kd_pembayaran');
		if (empty($kd_pembayaran)) {
			echo json_encode(['status' => 0, 'message' => 'Kode pembayaran tidak ditemukan.']);
			return;
		}

		// Ambil header
		$header = $this->db->get_where('tr_invoice_payment', ['kd_pembayaran' => $kd_pembayaran])->row_array();
		if (!$header) {
			echo json_encode(['status' => 0, 'message' => 'Data penerimaan tidak ditemukan.']);
			return;
		}

		// Ambil detail
		$details = $this->db->get_where('tr_invoice_payment_detail', ['kd_pembayaran' => $kd_pembayaran])->result_array();

		// Helper: ambil list kolom tabel target, filter data sesuai kolom yang ada
		$filter_columns = function ($data, $table) {
			$fields = $this->db->list_fields($table);
			$filtered = [];
			foreach ($data as $key => $val) {
				if (in_array($key, $fields)) {
					$filtered[$key] = $val;
				}
			}
			return $filtered;
		};

		$this->db->trans_start();

		// ============================
		// 1. Pindahkan header ke tabel delete
		// ============================
		$header_insert = $header;
		unset($header_insert['id']);
		$header_insert['deleted_by'] = $user_id;
		$header_insert['deleted_on'] = date('Y-m-d H:i:s');
		$header_insert = $filter_columns($header_insert, 'tr_invoice_payment_delete');
		$this->db->insert('tr_invoice_payment_delete', $header_insert);

		// ============================
		// 2. Pindahkan detail ke tabel delete
		// ============================
		foreach ($details as $det) {
			$det_insert = $det;
			unset($det_insert['id']);
			$det_insert['deleted_by'] = $user_id;
			$det_insert['deleted_on'] = date('Y-m-d H:i:s');
			$det_insert = $filter_columns($det_insert, 'tr_invoice_payment_detail_delete');
			$this->db->insert('tr_invoice_payment_detail_delete', $det_insert);
		}

		// ============================
		// 3. Jurnal Balik (Reversal) - langsung insert tanpa searching jurnal lama
		// ============================
		$Nomor_JV_Reversal = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', date('Y-m-d'));

		// Ambil customer info untuk keterangan
		$customer = $this->db
			->select('c.name_customer, c.id_karyawan, e.nm_karyawan')
			->from('master_customers c')
			->join('employee e', 'e.id = c.id_karyawan', 'left')
			->where('c.id_customer', $header['id_customer'])
			->get()
			->row();
		$nm_cust = isset($customer->name_customer) ? $customer->name_customer : $header['nm_customer'];

		// Hitung total dari detail
		$total_amount = 0;
		foreach ($details as $det) {
			$total_amount += floatval($det['total_bayar_idr']);
		}

		// Insert header jarh
		$this->db->insert(DBACC . '.jarh', [
			'nomor'         => $Nomor_JV_Reversal,
			'kd_pembayaran' => $kd_pembayaran,
			'tgl'           => date('Y-m-d'),
			'jml'           => $total_amount,
			'kdcab'         => '101',
			'jenis_reff'    => $kd_pembayaran,
			'no_reff'       => $kd_pembayaran,
			'customer'      => $nm_cust,
			'note'          => 'BATAL PENERIMAAN ' . $kd_pembayaran . ' A/N ' . $nm_cust,
			'jenis_ar'      => 'V',
			'terima_dari'   => '-',
			'valid'         => $user_id,
			'tgl_valid'     => date('Y-m-d'),
			'user_id'       => $user_id,
			'tgl_invoice'   => date('Y-m-d'),
			'batal'         => 1,
		]);

		// Insert detail jurnal — BALIK: yang tadinya debet (kas) jadi kredit, yang tadinya kredit (piutang) jadi debet
		$arrJurnalReversal = [];
		foreach ($details as $det) {
			$total_bayar = floatval($det['total_bayar_idr']);
			if ($total_bayar <= 0) continue;

			// Kredit kas (balik dari debet kas)
			$arrJurnalReversal[] = [
				'nomor'         => $Nomor_JV_Reversal,
				'tanggal'       => date('Y-m-d'),
				'tipe'          => 'JV',
				'no_perkiraan'  => '1101-01-01',
				'keterangan'    => 'BATAL PENERIMAAN ' . $kd_pembayaran . ' INV ' . $det['no_invoice'] . ' A/n ' . $nm_cust,
				'no_reff'       => $kd_pembayaran,
				'debet'         => 0,
				'kredit'        => $total_bayar,
				'created_by'    => $user_id,
				'created_on'    => date('Y-m-d H:i:s'),
			];

			// Debet piutang (balik dari kredit piutang)
			$arrJurnalReversal[] = [
				'nomor'         => $Nomor_JV_Reversal,
				'tanggal'       => date('Y-m-d'),
				'tipe'          => 'JV',
				'no_perkiraan'  => '1102-01-01',
				'keterangan'    => 'BATAL PENERIMAAN ' . $kd_pembayaran . ' INV ' . $det['no_invoice'] . ' A/n ' . $nm_cust,
				'no_reff'       => $kd_pembayaran,
				'debet'         => $total_bayar,
				'kredit'        => 0,
				'created_by'    => $user_id,
				'created_on'    => date('Y-m-d H:i:s'),
			];
		}
		if (!empty($arrJurnalReversal)) {
			$this->db->insert_batch(DBACC . '.jurnal', $arrJurnalReversal);
		}

		// ============================
		// 4. Balik Kartu Piutang
		// ============================
		foreach ($details as $det) {
			$total_bayar = floatval($det['total_bayar_idr']);
			$total_cn    = isset($det['total_cn_idr']) ? floatval($det['total_cn_idr']) : 0;

			if ($total_bayar > 0) {
				$this->db->insert('tr_kartu_piutang', [
					'tipe'          => 'JV',
					'nomor'         => $Nomor_JV_Reversal,
					'tanggal'       => date('Y-m-d'),
					'no_perkiraan'  => '1102-01-01',
					'keterangan'    => 'BATAL PENERIMAAN ' . $kd_pembayaran . ' INV ' . $det['no_invoice'] . ' A/n ' . $nm_cust,
					'no_reff'       => $det['no_invoice'],
					'debet'         => $total_bayar,
					'kredit'        => 0,
					'id_supplier'   => $header['id_customer'],
					'nama_supplier' => $nm_cust,
				]);

				$this->db->insert('tr_kartu_piutang_sales', [
					'tipe'          => 'JV',
					'nomor'         => $Nomor_JV_Reversal,
					'tanggal'       => date('Y-m-d'),
					'no_perkiraan'  => '1102-01-04',
					'keterangan'    => 'BATAL PENERIMAAN ' . $kd_pembayaran . ' INV ' . $det['no_invoice'] . ' A/n ' . $nm_cust,
					'no_reff'       => $det['no_invoice'],
					'debet'         => 0,
					'kredit'        => $total_bayar,
					'id_sales'      => isset($customer->id_karyawan) ? $customer->id_karyawan : null,
					'nama_sales'    => isset($customer->nm_karyawan) ? $customer->nm_karyawan : null,
				]);
			}

			if ($total_cn > 0) {
				$this->db->insert('tr_kartu_piutang', [
					'tipe'          => 'JV',
					'nomor'         => $Nomor_JV_Reversal,
					'tanggal'       => date('Y-m-d'),
					'no_perkiraan'  => '1102-01-01',
					'keterangan'    => 'BATAL PENERIMAAN ' . $kd_pembayaran . ' CN INV ' . $det['no_invoice'] . ' A/n ' . $nm_cust,
					'no_reff'       => $det['no_invoice'],
					'debet'         => $total_cn,
					'kredit'        => 0,
					'id_supplier'   => $header['id_customer'],
					'nama_supplier' => $nm_cust,
				]);
			}

			// ============================
			// 5. Update saldo invoice (kembalikan piutang)
			// ============================
			$inv = $this->db->get_where('tr_invoice_sales', ['id_invoice' => $det['no_invoice']])->row();
			if ($inv) {
				$sum_bayar = $this->db->select('COALESCE(SUM(total_bayar_idr),0) AS total', false)
					->from('tr_invoice_payment_detail')
					->where('no_invoice', $det['no_invoice'])
					->where('kd_pembayaran !=', $kd_pembayaran)
					->get()->row()->total;

				$sum_cn = $this->db->select('COALESCE(SUM(total_cn_idr),0) AS total', false)
					->from('tr_invoice_payment_detail')
					->where('no_invoice', $det['no_invoice'])
					->where('kd_pembayaran !=', $kd_pembayaran)
					->get()->row()->total;

				$sisa_piutang = (float)$inv->grand_total - (float)$sum_bayar - (float)$sum_cn;
				if ($sisa_piutang < 0) $sisa_piutang = 0;

				$this->db->set('total_bayar', $sum_bayar, false);
				$this->db->set('piutang', $sisa_piutang, false);
				$this->db->set('sts', "CASE WHEN {$sisa_piutang} <= 0 THEN 0 ELSE 1 END", false);
				$this->db->where('id_invoice', $det['no_invoice']);
				$this->db->update('tr_invoice_sales');
			}
		}

		// ============================
		// 6. Reset CN yang dipakai
		// ============================
		$this->db->update('tr_retur', [
			'used_in_invoice' => null,
			'used_date'       => null,
		], ['used_in_invoice' => $kd_pembayaran]);

		// ============================
		// 7. Batalkan Setor Kasir (jika ada)
		// ============================
		$setor_kasir_details = $this->db->get_where('tr_setor_kasir_detail', ['kd_pembayaran' => $kd_pembayaran])->result_array();
		if (!empty($setor_kasir_details)) {
			foreach ($setor_kasir_details as $skd) {
				$id_setor_kasir = $skd['id_setor_kasir'];

				// Pindah detail ke tabel delete
				$skd_insert = $skd;
				unset($skd_insert['id']);
				$skd_insert['deleted_by'] = $user_id;
				$skd_insert['deleted_on'] = date('Y-m-d H:i:s');
				$skd_insert = $filter_columns($skd_insert, 'tr_setor_kasir_detail_delete');
				$this->db->insert('tr_setor_kasir_detail_delete', $skd_insert);

				// Hapus detail
				$this->db->delete('tr_setor_kasir_detail', ['id' => $skd['id']]);

				// Cek apakah header setor kasir masih punya detail lain
				$sisa_detail_kasir = $this->db->where('id_setor_kasir', $id_setor_kasir)
					->count_all_results('tr_setor_kasir_detail');

				if ($sisa_detail_kasir == 0) {
					// Pindah header ke delete
					$sk_header = $this->db->get_where('tr_setor_kasir', ['id' => $id_setor_kasir])->row_array();
					if ($sk_header) {
						$sk_header_insert = $sk_header;
						unset($sk_header_insert['id']);
						$sk_header_insert['deleted_by'] = $user_id;
						$sk_header_insert['deleted_on'] = date('Y-m-d H:i:s');
						$sk_header_insert = $filter_columns($sk_header_insert, 'tr_setor_kasir_delete');
						$this->db->insert('tr_setor_kasir_delete', $sk_header_insert);
						$this->db->delete('tr_setor_kasir', ['id' => $id_setor_kasir]);
					}

					// Jurnal balik setor kasir
					$Nomor_JV_Kasir = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', date('Y-m-d'));
					$total_setor_kasir = isset($sk_header['total_setoran']) ? floatval($sk_header['total_setoran']) : 0;

					if ($total_setor_kasir > 0) {
						$this->db->insert(DBACC . '.jarh', [
							'nomor'         => $Nomor_JV_Kasir,
							'kd_pembayaran' => $id_setor_kasir,
							'tgl'           => date('Y-m-d'),
							'jml'           => $total_setor_kasir,
							'kdcab'         => '101',
							'jenis_reff'    => $id_setor_kasir,
							'no_reff'       => $id_setor_kasir,
							'customer'      => $nm_cust,
							'note'          => 'BATAL SETOR KASIR ' . $id_setor_kasir . ' KARENA BATAL PENERIMAAN ' . $kd_pembayaran,
							'jenis_ar'      => 'V',
							'terima_dari'   => '-',
							'valid'         => $user_id,
							'tgl_valid'     => date('Y-m-d'),
							'user_id'       => $user_id,
							'tgl_invoice'   => date('Y-m-d'),
							'batal'         => 1,
						]);

						// Balik: Kas Penjualan di-debet, Kas Sales di-kredit
						$this->db->insert_batch(DBACC . '.jurnal', [
							[
								'nomor'         => $Nomor_JV_Kasir,
								'tanggal'       => date('Y-m-d'),
								'tipe'          => 'BUM',
								'no_perkiraan'  => '1101-01-02',
								'keterangan'    => 'BATAL SETOR KASIR ' . $id_setor_kasir . ' - ' . $kd_pembayaran,
								'no_reff'       => $id_setor_kasir,
								'debet'         => $total_setor_kasir,
								'kredit'        => 0,
								'created_by'    => $user_id,
								'created_on'    => date('Y-m-d H:i:s'),
							],
							[
								'nomor'         => $Nomor_JV_Kasir,
								'tanggal'       => date('Y-m-d'),
								'tipe'          => 'BUM',
								'no_perkiraan'  => '1101-01-01',
								'keterangan'    => 'BATAL SETOR KASIR ' . $id_setor_kasir . ' - ' . $kd_pembayaran,
								'no_reff'       => $id_setor_kasir,
								'debet'         => 0,
								'kredit'        => $total_setor_kasir,
								'created_by'    => $user_id,
								'created_on'    => date('Y-m-d H:i:s'),
							],
						]);
					}
				}
			}
		}

		// ============================
		// 8. Batalkan Setor Bank (jika ada)
		// ============================
		$setor_bank_details = $this->db->get_where('tr_setor_bank_detail', ['kd_pembayaran' => $kd_pembayaran])->result_array();
		if (!empty($setor_bank_details)) {
			foreach ($setor_bank_details as $sbd) {
				$id_setor_bank = $sbd['id_setor_bank'];

				// Pindah detail ke tabel delete
				$sbd_insert = $sbd;
				unset($sbd_insert['id']);
				$sbd_insert['deleted_by'] = $user_id;
				$sbd_insert['deleted_on'] = date('Y-m-d H:i:s');
				$sbd_insert = $filter_columns($sbd_insert, 'tr_setor_bank_detail_delete');
				$this->db->insert('tr_setor_bank_detail_delete', $sbd_insert);

				// Hapus detail
				$this->db->delete('tr_setor_bank_detail', ['id' => $sbd['id']]);

				// Cek apakah header setor bank masih punya detail lain
				$sisa_detail_bank = $this->db->where('id_setor_bank', $id_setor_bank)
					->count_all_results('tr_setor_bank_detail');

				if ($sisa_detail_bank == 0) {
					// Pindah header ke delete
					$sb_header = $this->db->get_where('tr_setor_bank', ['id' => $id_setor_bank])->row_array();
					if ($sb_header) {
						$sb_header_insert = $sb_header;
						unset($sb_header_insert['id']);
						$sb_header_insert['deleted_by'] = $user_id;
						$sb_header_insert['deleted_on'] = date('Y-m-d H:i:s');
						$sb_header_insert = $filter_columns($sb_header_insert, 'tr_setor_bank_delete');
						$this->db->insert('tr_setor_bank_delete', $sb_header_insert);
						$this->db->delete('tr_setor_bank', ['id' => $id_setor_bank]);
					}

					// Jurnal balik setor bank
					$Nomor_JV_Bank = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', date('Y-m-d'));
					$total_setor_bank = isset($sb_header['total_setoran']) ? floatval($sb_header['total_setoran']) : 0;

					if ($total_setor_bank > 0) {
						$this->db->insert(DBACC . '.jarh', [
							'nomor'         => $Nomor_JV_Bank,
							'kd_pembayaran' => $id_setor_bank,
							'tgl'           => date('Y-m-d'),
							'jml'           => $total_setor_bank,
							'kdcab'         => '101',
							'jenis_reff'    => $id_setor_bank,
							'no_reff'       => $id_setor_bank,
							'customer'      => $nm_cust,
							'note'          => 'BATAL SETOR BANK ' . $id_setor_bank . ' KARENA BATAL PENERIMAAN ' . $kd_pembayaran,
							'jenis_ar'      => 'V',
							'terima_dari'   => '-',
							'valid'         => $user_id,
							'tgl_valid'     => date('Y-m-d'),
							'user_id'       => $user_id,
							'tgl_invoice'   => date('Y-m-d'),
							'batal'         => 1,
						]);

						// Balik: Kas Penjualan di-debet, Bank di-kredit
						$this->db->insert_batch(DBACC . '.jurnal', [
							[
								'nomor'         => $Nomor_JV_Bank,
								'tanggal'       => date('Y-m-d'),
								'tipe'          => 'BUM',
								'no_perkiraan'  => '1101-01-01',
								'keterangan'    => 'BATAL SETOR BANK ' . $id_setor_bank . ' - ' . $kd_pembayaran,
								'no_reff'       => $id_setor_bank,
								'debet'         => $total_setor_bank,
								'kredit'        => 0,
								'created_by'    => $user_id,
								'created_on'    => date('Y-m-d H:i:s'),
							],
							[
								'nomor'         => $Nomor_JV_Bank,
								'tanggal'       => date('Y-m-d'),
								'tipe'          => 'BUM',
								'no_perkiraan'  => '1101-02-01',
								'keterangan'    => 'BATAL SETOR BANK ' . $id_setor_bank . ' - ' . $kd_pembayaran,
								'no_reff'       => $id_setor_bank,
								'debet'         => 0,
								'kredit'        => $total_setor_bank,
								'created_by'    => $user_id,
								'created_on'    => date('Y-m-d H:i:s'),
							],
						]);
					}
				}
			}
		}

		// ============================
		// 9. Hapus dari tabel utama
		// ============================
		$this->db->delete('tr_invoice_payment_detail', ['kd_pembayaran' => $kd_pembayaran]);
		$this->db->delete('tr_invoice_payment', ['kd_pembayaran' => $kd_pembayaran]);

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			echo json_encode([
				'status'  => 0,
				'message' => 'Gagal membatalkan penerimaan. Silahkan cek log database.'
			]);
		} else {
			echo json_encode([
				'status'  => 1,
				'message' => 'Penerimaan ' . $kd_pembayaran . ' berhasil dibatalkan.'
			]);
		}
	}

	public function export_excel()
	{
		$start = $this->input->get('start_date', true);
		$end   = $this->input->get('end_date', true);

		$user_id = $this->auth->user_id();
		$department_id = $this->auth->department_id();

		$sub_join = "(SELECT kd_pembayaran, GROUP_CONCAT(no_invoice SEPARATOR ',') AS invoiced, SUM(total_invoice_idr) AS total_invoice FROM tr_invoice_payment_detail GROUP BY kd_pembayaran) c";

		$this->db->select('a.kd_pembayaran, a.tgl_pembayaran, a.nm_customer, a.keterangan, a.jumlah_pembayaran_idr, c.invoiced, c.total_invoice');
		$this->db->from('tr_invoice_payment a');
		$this->db->where('a.tipe_bayar', 'CASH');
		$this->db->join($sub_join, 'a.kd_pembayaran = c.kd_pembayaran', 'left');
		if ($user_id != 7 && $department_id != 3) {
			$this->db->where('a.created_by', $user_id);
		}
		if (!empty($start)) $this->db->where('a.tgl_pembayaran >=', $start);
		if (!empty($end))   $this->db->where('a.tgl_pembayaran <=', $end);
		$this->db->order_by('a.tgl_pembayaran', 'DESC');

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
		$sheet->setCellValue('A1', 'REPORT PENERIMAAN CASH - ' . $periode);
		$sheet->mergeCells('A1:H2');

		$headers = ['A' => '#', 'B' => 'Tgl Penerimaan', 'C' => 'Kode Penerimaan', 'D' => 'Nama Customer', 'E' => 'Keterangan', 'F' => 'No Invoice', 'G' => 'Total Invoice', 'H' => 'Total Penerimaan (IDR)'];
		$rowHeader = 4;
		foreach ($headers as $col => $label) {
			$sheet->setCellValue($col . $rowHeader, $label);
			$sheet->getColumnDimension($col)->setAutoSize(true);
		}

		$r = $rowHeader + 1;
		$no = 1;
		foreach ($rows as $row) {
			$sheet->setCellValue('A' . $r, $no++);
			if (!empty($row->tgl_pembayaran)) {
				$tgl = (float)PHPExcel_Shared_Date::PHPToExcel(strtotime($row->tgl_pembayaran));
				$sheet->setCellValueExplicit('B' . $r, $tgl, PHPExcel_Cell_DataType::TYPE_NUMERIC);
				$sheet->getStyle('B' . $r)->getNumberFormat()->setFormatCode('dd/mmm/yyyy');
			}
			$sheet->setCellValueExplicit('C' . $r, (string)$row->kd_pembayaran, PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('D' . $r, (string)$row->nm_customer, PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('E' . $r, (string)$row->keterangan, PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('F' . $r, (string)$row->invoiced, PHPExcel_Cell_DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('G' . $r, (float)$row->total_invoice, PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$sheet->getStyle('G' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
			$sheet->setCellValueExplicit('H' . $r, (float)$row->jumlah_pembayaran_idr, PHPExcel_Cell_DataType::TYPE_NUMERIC);
			$sheet->getStyle('H' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
			$r++;
		}

		$sheet->setTitle('Penerimaan Cash');
		$writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
		ob_end_clean();
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="Penerimaan_Cash_' . date('Ymd_His') . '.xls"');
		header('Cache-Control: max-age=0');
		$writer->save('php://output');
		exit;
	}
}


// trash


// public function print_struk2($kd_pembayaran)
// 	{
// 		$header = $this->db
// 			->where('kd_pembayaran', $kd_pembayaran)
// 			->get('tr_invoice_payment')->row();

// 		$details = $this->db
// 			->where('kd_pembayaran', $kd_pembayaran)
// 			->get('tr_invoice_payment_detail')->result();

// 		$subtotal = 0;
// 		$freight = 0;
// 		$total_pembayaran = 0;
// 		$total_kurang_pembayaran = 0;

// 		$no_invoices = array_map(function ($d) {
// 			return $d->no_invoice;
// 		}, $details);

// 		$total_pembayaran_sebelumnya = $this->db
// 			->select_sum('total_bayar_idr')
// 			->from('tr_invoice_payment_detail')
// 			->where_in('no_invoice', $no_invoices)
// 			->where('kd_pembayaran !=', $kd_pembayaran)
// 			->get()
// 			->row()
// 			->total_bayar_idr ?? 0;

// 		foreach ($details as $item) {
// 			$invoice = $this->db
// 				->select('a.*')
// 				->from('tr_invoice_sales a')
// 				->where('a.id_invoice', $item->no_invoice)
// 				->get()
// 				->result();

// 			$total_pembayaran += $item->total_bayar_idr;
// 			$total_kurang_pembayaran += $item->sisa_invoice_idr;

// 			foreach ($invoice as $inv) {
// 				$delivery = $this->db
// 					->select('a.no_surat_jalan, a.no_delivery, a.no_so')
// 					->from('spk_delivery a')
// 					->where('a.no_surat_jalan', $inv->id_billing)
// 					->get()
// 					->row();

// 				if (!$delivery) continue;

// 				$items = $this->db
// 					->select('c.price_list, c.harga_penawaran, c.diskon, c.diskon_nilai, a.qty_delivery as qty, c.total_pl, d.ppn')
// 					->from('spk_delivery_detail a')
// 					->join('sales_order_detail b', 'b.id = a.id_so_det')
// 					->join('penawaran_detail c', 'c.id_penawaran = b.id_penawaran AND c.id_product = b.id_product')
// 					->join('penawaran d', 'd.id_penawaran = c.id_penawaran')
// 					->where('a.no_delivery', $delivery->no_delivery)
// 					->get()->result();

// 				$get_spk_pertama = $this->db
// 					->order_by('no_delivery', 'ASC')
// 					->get_where('spk_delivery', ['no_so' => $delivery->no_so])
// 					->row();

// 				$is_spk_pertama = ($get_spk_pertama && $get_spk_pertama->no_surat_jalan == $delivery->no_surat_jalan);

// 				// Jika SPK pertama, ambil freight
// 				if ($is_spk_pertama) {
// 					$freight_data = $this->db
// 						->select('b.freight')
// 						->from('sales_order a')
// 						->join('penawaran b', 'b.id_penawaran = a.id_penawaran')
// 						->where('a.no_so', $delivery->no_so)
// 						->get()
// 						->row();

// 					$freight += $freight_data ? $freight_data->freight : 0;
// 				}

// 				foreach ($items as $row) {
// 					$disc = (float)$row->diskon;
// 					$total_item = round(($row->price_list * $row->qty) * (1 + ($disc / 100)), -2); // diskon dikurang
// 					$subtotal += $total_item;
// 				}
// 			}
// 		}

// 		// Hitung DPP & PPN (jika PPN sudah termasuk)
// 		$exclude_ppn = ($subtotal + $freight) / 1.11;
// 		$dpp = ($exclude_ppn * 11) / 12;
// 		$ppn = ($dpp * 12) / 100;
// 		$grand_total = $exclude_ppn + $ppn;

// 		// Kirim ke view
// 		$html = $this->load->view('struk_penerimaan_cash', [
// 			'header' => $header,
// 			'details' => $details,
// 			'subtotal' => $subtotal,
// 			'exclude_ppn' => $exclude_ppn,
// 			'freight' => $freight,
// 			'dpp' => $dpp,
// 			'ppn' => $ppn,
// 			'total_pembayaran' => $total_pembayaran,
// 			'total_pembayaran_sebelumnya' => $total_pembayaran_sebelumnya,
// 			'total_kurang_pembayaran' => $total_kurang_pembayaran,
// 			'grand_total' => $grand_total,
// 		], true);

// 		$dompdf = new Dompdf();
// 		$dompdf->loadHtml($html);
// 		$dompdf->setPaper([0, 0, 165, 500], 'portrait'); // thermal 58mm
// 		$dompdf->render();
// 		$dompdf->stream("STRUK_$kd_pembayaran.pdf", ["Attachment" => false]);
// 	}
// }
