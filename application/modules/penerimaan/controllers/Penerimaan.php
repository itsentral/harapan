<?php
defined('BASEPATH') or exit('No direct script access allowed');

use Dompdf\Dompdf;

class Penerimaan extends Admin_Controller
{

    protected $viewPermission   = 'Penerimaan_Uang.View';
    protected $addPermission    = 'Penerimaan_Uang.Add';
    protected $managePermission = 'Penerimaan_Uang.Manage';
    protected $deletePermission = 'Penerimaan_Uang.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array(
            'Penerimaan/master_model',
            'Penerimaan/penerimaan_model',
            'Penerimaan/All_model',
            'Penerimaan/Jurnal_model',
            'Penerimaan/Acc_model'
        ));

        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->page_icon('fa fa-credit-card');
        $this->template->title('Penerimaan Uang');
        $this->template->render('list_payment');
    }

    public function data_side_penerimaan()
    {
        $this->penerimaan_model->get_data_json_payment();
    }

    public function add()
    {
        // Ambil daftar customer dari invoice yang masih aktif
        $this->db->select('c.id_customer, c.name_customer, c.npwp, c.telephone, c.fax, c.address_office, a.id_so');
        $this->db->from('tr_invoice_sales a');
        // $this->db->join('sales_order b', 'b.no_so = a.id_so', 'left');
        $this->db->join('master_customers c', 'c.id_customer = a.id_customer', 'left');
        $this->db->where('c.deleted_by IS NULL');
        $this->db->where('a.sts', 1);
        $this->db->group_by('c.id_customer');
        $customers = $this->db->get()->result();

        // Ambil data bank dari GL
        $this->db->from(DBACC . '.coa_master a')
            ->where('a.no_perkiraan LIKE', '%1101-02%')
            ->where('a.level', 5);
        $data_bank = $this->db->get()->result();

        $data = [
            'customers' => $customers,
            'bank'      => $data_bank,
        ];

        $this->template->title('Add Penerimaan Uang');
        $this->template->page_icon('fa fa-credit-card');
        $this->template->render('form_penerimaan', $data);
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
            // ->join('sales_order so', 'so.no_so = i.id_so', 'left')
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

    public function save()
    {
        $post = $this->input->post();

        $this->db->trans_begin();

        try {

            // =========================
            // HEADER
            // =========================
            $tgl_pembayaran = $post['tgl_pembayaran'];
            $id_customer    = $post['id_customer'];
            $detail         = $post['detail'];

            $total_invoice  = str_replace(',', '', ($post['total_invoice']));
            $total_terima   = str_replace(',', '', ($post['total_terima']));
            $total_bank     = str_replace(',', '', ($post['total_bank']));

            $kd_bank        = $post['bank'];
            $keterangan     = $post['ket_bayar'];

            $biaya_admin    = str_replace(',', '', ($post['biaya_adm']));
            $lebih_bayar    = str_replace(',', '', ($post['lebih_bayar']));

            $kd_pembayaran  = $this->penerimaan_model->generate_nopn($tgl_pembayaran);

            $customer = $this->db
                ->get_where('master_customers', ['id_customer' => $id_customer])
                ->row();

            $invoice_ids = array_column($detail, 'id_invoice');

            $header = [
                'kd_pembayaran'         => $kd_pembayaran,
                'tgl_pembayaran'        => $tgl_pembayaran,
                'no_invoice'            => implode(', ', $invoice_ids),
                'nm_customer'           => $customer->name_customer,
                'id_customer'           => $id_customer,
                'kd_bank'               => $kd_bank,
                'jumlah_piutang_idr'    => $total_invoice,
                'jumlah_bank_idr'       => $total_bank,
                'jumlah_pembayaran_idr' => $total_terima,
                'biaya_admin_idr'       => $biaya_admin,
                'lebih_bayar'           => $lebih_bayar,
                'keterangan'            => $keterangan,
                'created_by'            => $this->auth->user_id(),
                'created_on'            => date('Y-m-d H:i:s'),
                'tipe_bayar'            => "BANK"
            ];

            $this->db->insert('tr_invoice_payment', $header);

            // =========================
            // DETAIL + UPDATE INVOICE
            // =========================
            foreach ($detail as $idx => $row) {

                if (empty($row['id_invoice'])) {
                    continue; // skip baris yang tidak punya invoice
                }

                $invoice = $this->db
                    ->get_where('tr_invoice_sales', ['id_invoice' => $row['id_invoice']])
                    ->row();

                if (!$invoice) {
                    throw new Exception("Invoice {$row['id_invoice']} tidak ditemukan");
                }

                $total_bayar  = str_replace(',', '', ($row['total_bayar']));
                $tagihan      = str_replace(',', '', ($row['tagihan']));
                $sisa_invoice = str_replace(',', '', ($row['sisa_invoice']));
                $total_cn     = isset($row['total_cn']) ? (float)$row['total_cn'] : 0;

                $data_detail = [
                    'kd_pembayaran'      => $kd_pembayaran,
                    'nm_customer'        => $customer->name_customer,
                    'id_customer'        => $id_customer,
                    'no_invoice'         => $row['id_invoice'],
                    'no_ipp'             => $row['id_so'],
                    'so_number'          => $row['id_so'],
                    'tgl_invoice'        => date('Y-m-d', strtotime($invoice->created_on)),
                    'total_ppn_idr'      => $invoice->nilai_ppn,
                    'total_invoice_idr'  => $tagihan,
                    'total_bayar_idr'    => $total_bayar,
                    'sisa_invoice_idr'   => $sisa_invoice,
                    'total_cn_idr'       => $total_cn,
                    'created_by'         => $this->auth->user_id(),
                    'created_on'         => date('Y-m-d H:i:s'),
                    'tipe_bayar'         => "BANK"
                ];

                $this->db->insert('tr_invoice_payment_detail', $data_detail);

                if ($this->db->affected_rows() == 0) {
                    throw new Exception("Gagal insert detail untuk invoice {$row['id_invoice']}");
                }

                // Tandai CN yang digunakan pada penerimaan ini
                if (!empty($row['cn']) && is_array($row['cn'])) {
                    foreach ($row['cn'] as $cn_item) {
                        $no_retur_cn = $cn_item['no_retur'];
                        $nilai_cn    = (float)$cn_item['nilai'];
                        if (!empty($no_retur_cn)) {
                            $this->db->update('tr_retur', [
                                'used_in_invoice' => $kd_pembayaran,
                                'used_date'       => date('Y-m-d'),
                            ], ['no_retur' => $no_retur_cn]);
                        }
                    }
                }

                // UPDATE INVOICE
                $sum = $this->db->select('COALESCE(SUM(total_bayar_idr),0) AS total', false)
                    ->from('tr_invoice_payment_detail')
                    ->where('no_invoice', $row['id_invoice'])
                    ->get()->row()->total;

                // Sisa piutang = tagihan asal - total bayar - total CN yang sudah dipakai
                $total_cn_used = $this->db->select('COALESCE(SUM(total_cn_idr),0) AS total', false)
                    ->from('tr_invoice_payment_detail')
                    ->where('no_invoice', $row['id_invoice'])
                    ->get()->row()->total;

                $sisa_piutang = (float)$invoice->grand_total - (float)$sum - (float)$total_cn_used;
                if ($sisa_piutang < 0) $sisa_piutang = 0;

                $this->db->set('total_bayar', $sum, false);
                $this->db->set('piutang', $sisa_piutang, false);
                $this->db->set('sts', "CASE WHEN {$sisa_piutang} <= 0 THEN 0 ELSE 1 END", false);
                $this->db->where('id_invoice', $row['id_invoice'])
                    ->update('tr_invoice_sales');
            }

            // =========================
            // JURNAL (DARI POST 🔥)
            // =========================
            $this->saveJurnal($kd_pembayaran, $post);

            // =========================
            // COMMIT
            // =========================
            if ($this->db->trans_status() === FALSE) {
                throw new Exception("DB Error");
            }

            $this->db->trans_commit();

            echo json_encode([
                'status' => 1,
                'message' => 'Pembayaran berhasil disimpan'
            ]);
        } catch (Exception $e) {

            $this->db->trans_rollback();

            echo json_encode([
                'status' => 0,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function saveJurnal($kd_bayar, $post)
    {
        $session = $this->session->userdata('app_session');
        $detail  = $post['detail'];

        $Nomor_BUM = $this->Jurnal_model
            ->get_Nomor_Jurnal_BUM('101', $post['tgl_pembayaran']);

        $customer = $this->db
            ->get_where('master_customers', ['id_customer' => $post['id_customer']])
            ->row();

        //AMBIL NO INVOICE DARI DETAIL
        $arrInv = array_column($detail, 'id_invoice');
        $invString = implode(', ', $arrInv);
        $note = 'PEMBAYARAN PIUTANG INVOICE ' . $invString . ' A/N ' . $customer->name_customer;

        // =========================
        // VALIDASI BALANCE
        // =========================
        $totalDebit  = array_sum($post['debet']);
        $totalKredit = array_sum($post['kredit']);

        if ($totalDebit != $totalKredit) {
            throw new Exception("Jurnal tidak balance");
        }

        // =========================
        // HEADER JURNAL
        // =========================
        $dataJARH = [
            'nomor'         => $Nomor_BUM,
            'kd_pembayaran' => $kd_bayar,
            'tgl'           => $post['tgl_pembayaran'],
            'jml'           => $totalDebit,
            'kdcab'         => '101',
            'jenis_reff'    => $kd_bayar,
            'no_reff'       => $kd_bayar,
            'customer'      => $customer->name_customer,
            'note'          => $note,
            'jenis_ar'      => 'V',
            'terima_dari'   => '-',
            'valid'         => $session['id_user'],
            'tgl_valid'     => $post['tgl_pembayaran'],
            'user_id'       => $session['id_user'],
            'tgl_invoice'   => $post['tgl_pembayaran'],
            'batal'         => 0
        ];

        $this->db->insert(DBACC . '.jarh', $dataJARH);

        // =========================
        // DETAIL JURNAL
        // =========================
        $det_Jurnal = [];

        for ($i = 0; $i < count($post['no_coa']); $i++) {

            $det_Jurnal[] = [
                'nomor'         => $Nomor_BUM,
                'tanggal'       => $post['tgl_jurnal'][$i],
                'tipe'          => $post['type'][$i],
                'no_perkiraan'  => $post['no_coa'][$i],
                'keterangan'    => $post['keterangan'][$i] . " A/n " . $customer->name_customer,
                'no_reff'       => $kd_bayar,
                'debet'         => str_replace(',', '', ($post['debet'][$i])),
                'kredit'        => str_replace(',', '', ($post['kredit'][$i])),
                'created_by'    => $this->auth->user_id(),
                'created_on'    => date('Y-m-d H:i:s'),
            ];
        }

        $this->db->insert_batch(DBACC . '.jurnal', $det_Jurnal);

        // INSERT KARTU PIUTANG
        foreach ($detail as $row) {
            $total_bayar  = str_replace(',', '', ($row['total_bayar']));
            $total_cn     = isset($row['total_cn']) ? (float)$row['total_cn'] : 0;

            // Kredit piutang dari pembayaran bank
            if ($total_bayar > 0) {
                $ket = 'PEMBAYARAN PIUTANG INV ' . $row['id_invoice'] . ' A/N ' . $customer->name_customer;

                $this->db->insert('tr_kartu_piutang', [
                    'tipe'          => 'BUM',
                    'nomor'         => $Nomor_BUM,
                    'tanggal'       => $post['tgl_pembayaran'],
                    'no_perkiraan'  => '1102-01-01',
                    'keterangan'    => $ket,
                    'no_reff'       => $row['id_invoice'],
                    'debet'         => 0,
                    'kredit'        => $total_bayar,
                    'id_supplier'   => $post['id_customer'],
                    'nama_supplier' => $customer->name_customer,
                ]);
            }

            // Kredit piutang dari CN (penggunaan credit note)
            if ($total_cn > 0) {
                $cn_nos = [];
                if (!empty($row['cn']) && is_array($row['cn'])) {
                    foreach ($row['cn'] as $cn_item) {
                        $cn_nos[] = $cn_item['no_retur'];
                    }
                }
                $ket_cn = 'PENGGUNAAN CREDIT NOTE ' . implode(', ', $cn_nos) . ' INV ' . $row['id_invoice'] . ' A/N ' . $customer->name_customer;

                $this->db->insert('tr_kartu_piutang', [
                    'tipe'          => 'BUM',
                    'nomor'         => $Nomor_BUM,
                    'tanggal'       => $post['tgl_pembayaran'],
                    'no_perkiraan'  => '1102-01-01',
                    'keterangan'    => $ket_cn,
                    'no_reff'       => $row['id_invoice'],
                    'debet'         => 0,
                    'kredit'        => $total_cn,
                    'id_supplier'   => $post['id_customer'],
                    'nama_supplier' => $customer->name_customer,
                ]);
            }
        }

        // UPDATE COUNTER   
        $this->db->query("UPDATE " . DBACC . ".pastibisa_tb_cabang SET nobum=nobum+1 WHERE nocab='101'");
    }

    public function print($kd_bayar)
    {
        $data = array(
            'kodebayar' => $kd_bayar,
        );
        $this->load->view('print_penerimaan', $data);
    }

    public function export_excel()
    {
        $start = $this->input->get('start_date', true);
        $end   = $this->input->get('end_date', true);

        $sub_join = "(SELECT kd_pembayaran, GROUP_CONCAT(no_invoice SEPARATOR ',') AS invoiced, SUM(total_invoice_idr) AS total_invoice FROM tr_invoice_payment_detail GROUP BY kd_pembayaran) c";

        $this->db->select('a.kd_pembayaran, a.tgl_pembayaran, a.nm_customer, a.keterangan, a.jumlah_pembayaran_idr, a.biaya_admin_idr, c.invoiced, c.total_invoice');
        $this->db->from('tr_invoice_payment a');
        $this->db->where('a.tipe_bayar', 'BANK');
        $this->db->join($sub_join, 'a.kd_pembayaran = c.kd_pembayaran', 'left');
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
        $sheet->setCellValue('A1', 'REPORT PENERIMAAN (BANK) - ' . $periode);
        $sheet->mergeCells('A1:I2');

        $headers = ['A' => '#', 'B' => 'Tgl Penerimaan', 'C' => 'Kode Penerimaan', 'D' => 'Nama Customer', 'E' => 'Keterangan', 'F' => 'No Invoice', 'G' => 'Total Invoice', 'H' => 'Biaya Admin', 'I' => 'Total Penerimaan (IDR)'];
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
                // Ambil komponen tanggal langsung dari string (hindari konversi lewat strtotime()
                // + PHPToExcel() yang memaksa timezone UTC, karena itu bisa membuat tanggal
                // mundur 1 hari saat timezone aplikasi (Asia/Bangkok, UTC+7) berbeda dari UTC).
                $dateOnly = substr($row->tgl_pembayaran, 0, 10); // 'Y-m-d'
                list($y, $m, $d) = array_map('intval', explode('-', $dateOnly));
                $tgl = (float)PHPExcel_Shared_Date::FormattedPHPToExcel($y, $m, $d);
                $sheet->setCellValueExplicit('B' . $r, $tgl, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle('B' . $r)->getNumberFormat()->setFormatCode('dd/mmm/yyyy');
            }
            $sheet->setCellValueExplicit('C' . $r, (string)$row->kd_pembayaran, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $r, (string)$row->nm_customer, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $r, (string)$row->keterangan, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $r, (string)$row->invoiced, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G' . $r, (float)$row->total_invoice, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('G' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValueExplicit('H' . $r, (float)$row->biaya_admin_idr, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('H' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->setCellValueExplicit('I' . $r, (float)$row->jumlah_pembayaran_idr, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('I' . $r)->getNumberFormat()->setFormatCode('#,##0.00');
            $r++;
        }

        $sheet->setTitle('Penerimaan Bank');
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Penerimaan_Bank_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /**
     * Batalkan Penerimaan (ADMIN ONLY)
     * - Pindahkan header dari tr_invoice_payment → tr_invoice_payment_delete
     * - Pindahkan detail dari tr_invoice_payment_detail → tr_invoice_payment_detail_delete
     * - Jurnal dibalik (debet jadi kredit, kredit jadi debet)
     * - Batalkan juga setor kasir & setor bank jika ada
     */
    public function batalkan_penerimaan()
    {
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

        $header = $this->db->get_where('tr_invoice_payment', ['kd_pembayaran' => $kd_pembayaran])->row_array();
        if (!$header) {
            echo json_encode(['status' => 0, 'message' => 'Data penerimaan tidak ditemukan.']);
            return;
        }

        $details = $this->db->get_where('tr_invoice_payment_detail', ['kd_pembayaran' => $kd_pembayaran])->result_array();

        // Helper: filter kolom sesuai tabel target
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

        // 1. Pindahkan header ke tabel delete
        $header_insert = $header;
        unset($header_insert['id']);
        $header_insert['deleted_by'] = $user_id;
        $header_insert['deleted_on'] = date('Y-m-d H:i:s');
        $header_insert = $filter_columns($header_insert, 'tr_invoice_payment_delete');
        $this->db->insert('tr_invoice_payment_delete', $header_insert);

        // 2. Pindahkan detail ke tabel delete
        foreach ($details as $det) {
            $det_insert = $det;
            unset($det_insert['id']);
            $det_insert['deleted_by'] = $user_id;
            $det_insert['deleted_on'] = date('Y-m-d H:i:s');
            $det_insert = $filter_columns($det_insert, 'tr_invoice_payment_detail_delete');
            $this->db->insert('tr_invoice_payment_detail_delete', $det_insert);
        }

        // 3. Jurnal Balik - langsung insert
        $Nomor_JV_Reversal = $this->Jurnal_model->get_Nomor_Jurnal_Sales('101', date('Y-m-d'));

        $customer = $this->db
            ->select('c.name_customer, c.id_karyawan, e.nm_karyawan')
            ->from('master_customers c')
            ->join('employee e', 'e.id = c.id_karyawan', 'left')
            ->where('c.id_customer', $header['id_customer'])
            ->get()
            ->row();
        $nm_cust = isset($customer->name_customer) ? $customer->name_customer : $header['nm_customer'];

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

        // Insert detail jurnal — debet kredit dibalik
        $arrJurnalReversal = [];
        foreach ($details as $det) {
            $total_bayar = floatval($det['total_bayar_idr']);
            if ($total_bayar <= 0) continue;

            // Kredit bank (balik dari debet bank)
            $arrJurnalReversal[] = [
                'nomor'         => $Nomor_JV_Reversal,
                'tanggal'       => date('Y-m-d'),
                'tipe'          => 'JV',
                'no_perkiraan'  => '1101-02-01',
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

        // 4. Balik Kartu Piutang
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

            // 5. Update saldo invoice
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

        // 6. Reset CN
        $this->db->update('tr_retur', [
            'used_in_invoice' => null,
            'used_date'       => null,
        ], ['used_in_invoice' => $kd_pembayaran]);

        // 7. Batalkan Setor Kasir (jika ada)
        $setor_kasir_details = $this->db->get_where('tr_setor_kasir_detail', ['kd_pembayaran' => $kd_pembayaran])->result_array();
        if (!empty($setor_kasir_details)) {
            foreach ($setor_kasir_details as $skd) {
                $id_setor_kasir = $skd['id_setor_kasir'];

                $skd_insert = $skd;
                unset($skd_insert['id']);
                $skd_insert['deleted_by'] = $user_id;
                $skd_insert['deleted_on'] = date('Y-m-d H:i:s');
                $skd_insert = $filter_columns($skd_insert, 'tr_setor_kasir_detail_delete');
                $this->db->insert('tr_setor_kasir_detail_delete', $skd_insert);
                $this->db->delete('tr_setor_kasir_detail', ['id' => $skd['id']]);

                $sisa_detail_kasir = $this->db->where('id_setor_kasir', $id_setor_kasir)
                    ->count_all_results('tr_setor_kasir_detail');

                if ($sisa_detail_kasir == 0) {
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

        // 8. Batalkan Setor Bank (jika ada)
        $setor_bank_details = $this->db->get_where('tr_setor_bank_detail', ['kd_pembayaran' => $kd_pembayaran])->result_array();
        if (!empty($setor_bank_details)) {
            foreach ($setor_bank_details as $sbd) {
                $id_setor_bank = $sbd['id_setor_bank'];

                $sbd_insert = $sbd;
                unset($sbd_insert['id']);
                $sbd_insert['deleted_by'] = $user_id;
                $sbd_insert['deleted_on'] = date('Y-m-d H:i:s');
                $sbd_insert = $filter_columns($sbd_insert, 'tr_setor_bank_detail_delete');
                $this->db->insert('tr_setor_bank_detail_delete', $sbd_insert);
                $this->db->delete('tr_setor_bank_detail', ['id' => $sbd['id']]);

                $sisa_detail_bank = $this->db->where('id_setor_bank', $id_setor_bank)
                    ->count_all_results('tr_setor_bank_detail');

                if ($sisa_detail_bank == 0) {
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

        // 9. Hapus dari tabel utama
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
}
