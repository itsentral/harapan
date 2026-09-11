<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_penagihan extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Penagihan.View';
    protected $addPermission    = 'Report_Penagihan.Add';
    protected $managePermission = 'Report_Penagihan.Manage';
    protected $deletePermission = 'Report_Penagihan.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->library(array('upload', 'Image_lib'));
        $this->load->model(array(
            'Report_penagihan/Report_penagihan_model',
        ));

        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->page_icon('fa fa-credit-card');
        $this->template->title('Report Target Penagihan vs Realisasi Tagihan');

        $tahun = $this->input->get('tahun') ?? date('Y');
        $bulan_sekarang = (int) date('n'); // bulan saat ini (1-12)
        $tahun_sekarang = (int) date('Y');

        // 1. Ambil Data Sales & Bulan
        $sales = $this->db->where('department', '2')->get('employee')->result_array();
        $bulan = $this->db->order_by('bulan_no', 'asc')->get('cr_bulan')->result_array();

        // 2. Target Tagihan (Target Penagihan) - BEKU / historis
        // Dihitung efisien via bulk-load + agregasi PHP (lihat hitung_rekap_target()).
        $rekap_target = $this->hitung_rekap_target($tahun, $tahun_sekarang, $bulan_sekarang);

        // 3. Query Realisasi Tagihan (pembayaran yang diterima pada bulan tersebut, berdasarkan tanggal pembayaran)
        $this->db->select("
        c.id as id_sales,
        MONTH(p.tgl_pembayaran) as bulan,
        SUM(pd.total_bayar_idr) as total_realisasi
        ");
        $this->db->from('tr_invoice_payment_detail pd');
        $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = pd.kd_pembayaran');
        $this->db->join('tr_invoice_sales a', 'a.id_invoice = pd.no_invoice');
        $this->db->join('master_customers b', 'a.id_customer = b.id_customer');
        $this->db->join('employee c', 'b.id_karyawan = c.id');
        $this->db->where('YEAR(p.tgl_pembayaran)', $tahun);
        $this->db->group_by('c.id, MONTH(p.tgl_pembayaran)');
        $query_data = $this->db->get()->result_array();

        $rekap_realisasi = [];
        foreach ($query_data as $row) {
            $rekap_realisasi[$row['id_sales']][$row['bulan']] = (float)$row['total_realisasi'];
        }

        $data = [
            'sales' => $sales,
            'bulan' => $bulan,
            'rekap_target' => $rekap_target,
            'rekap_realisasi' => $rekap_realisasi,
            'tahun_pilih' => $tahun,
            'bulan_sekarang' => $bulan_sekarang,
            'tahun_sekarang' => $tahun_sekarang,
        ];

        $this->template->render('index', $data);
    }

    public function export_excel()
    {
        $tahun = $this->input->get('tahun') ?? date('Y');
        $bulan_sekarang = (int) date('n');
        $tahun_sekarang = (int) date('Y');

        // 1. Ambil Data Sales & Bulan
        $sales = $this->db->where('department', '2')->get('employee')->result_array();
        $bulan = $this->db->order_by('bulan_no', 'asc')->get('cr_bulan')->result_array();

        // 2. Target Tagihan (Target Penagihan) - BEKU / historis, via helper efisien
        $rekap_target = $this->hitung_rekap_target($tahun, $tahun_sekarang, $bulan_sekarang);

        // 3. Query Realisasi Tagihan (pembayaran yang diterima pada bulan tersebut, berdasarkan tanggal pembayaran)
        $this->db->select("
        c.id as id_sales,
        MONTH(p.tgl_pembayaran) as bulan,
        SUM(pd.total_bayar_idr) as total_realisasi
        ");
        $this->db->from('tr_invoice_payment_detail pd');
        $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = pd.kd_pembayaran');
        $this->db->join('tr_invoice_sales a', 'a.id_invoice = pd.no_invoice');
        $this->db->join('master_customers b', 'a.id_customer = b.id_customer');
        $this->db->join('employee c', 'b.id_karyawan = c.id');
        $this->db->where('YEAR(p.tgl_pembayaran)', $tahun);
        $this->db->group_by('c.id, MONTH(p.tgl_pembayaran)');
        $query_data = $this->db->get()->result_array();

        $rekap_realisasi = [];
        foreach ($query_data as $row) {
            $rekap_realisasi[$row['id_sales']][$row['bulan']] = (float)$row['total_realisasi'];
        }

        // 4. Setup PHPExcel
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        // Redam notice/warning/deprecated dari PHPExcel (library lama) supaya tidak bocor
        // ke output binary .xls dan tidak memicu "headers already sent"
        error_reporting(E_ERROR | E_PARSE);
        $this->load->library('PHPExcel');
        PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();

        $sheet->setCellValue('A1', 'REPORT TARGET PENAGIHAN VS REALISASI TAGIHAN - TAHUN ' . $tahun);
        $sheet->mergeCells('A1:O2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // 5. Header Tabel
        $headers = ['A' => 'Nama Sales', 'B' => 'Keterangan'];
        $col = 'C';
        foreach ($bulan as $b) {
            $headers[$col] = substr($b['bulan'], 0, 3);
            $col++;
        }
        $headers['O'] = 'T Score';

        $rowHeader = 4;
        foreach ($headers as $c => $label) {
            $sheet->setCellValue($c . $rowHeader, $label);
            $sheet->getColumnDimension($c)->setAutoSize(true);
            $sheet->getStyle($c . $rowHeader)->getFont()->setBold(true);
            $sheet->getStyle($c . $rowHeader)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }

        // 6. Isi Data Sales
        $r = $rowHeader + 1;
        $grand_total_target = array_fill(1, 12, 0);
        $grand_total_realisasi = array_fill(1, 12, 0);

        foreach ($sales as $s) {
            // Merge Nama Sales
            $sheet->setCellValue('A' . $r, strtoupper($s['nm_karyawan']));
            $sheet->mergeCells('A' . $r . ':A' . ($r + 1));
            $sheet->getStyle('A' . $r)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

            // Baris Target Penagihan (Target)
            $sheet->setCellValue('B' . $r, 'Target Penagihan');
            $row_t_target = 0;
            $c = 'C';
            foreach ($bulan as $b) {
                $bln_no = (int)$b['bulan_no'];
                // Kosongkan bulan yang belum terjadi
                if ($tahun == $tahun_sekarang && $bln_no > $bulan_sekarang) {
                    $sheet->setCellValue($c . $r, '-');
                    $sheet->getStyle($c . $r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                } else {
                    $val = (float)($rekap_target[$s['id']][$bln_no] ?? 0);
                    $sheet->setCellValueExplicit($c . $r, $val, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $sheet->getStyle($c . $r)->getNumberFormat()->setFormatCode('#,##0');
                    $row_t_target += $val;
                    $grand_total_target[$bln_no] += $val;
                }
                $c++;
            }
            $sheet->setCellValueExplicit('O' . $r, $row_t_target, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('O' . $r)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('O' . $r)->getFont()->setBold(true);

            // Baris Realisasi Tagihan
            $r++;
            $sheet->setCellValue('B' . $r, 'Realisasi Tagihan');
            $row_t_realisasi = 0;
            $c = 'C';
            foreach ($bulan as $b) {
                $bln_no = (int)$b['bulan_no'];
                if ($tahun == $tahun_sekarang && $bln_no > $bulan_sekarang) {
                    $sheet->setCellValue($c . $r, '-');
                    $sheet->getStyle($c . $r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                } else {
                    $val = (float)($rekap_realisasi[$s['id']][$bln_no] ?? 0);
                    $sheet->setCellValueExplicit($c . $r, $val, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $sheet->getStyle($c . $r)->getNumberFormat()->setFormatCode('#,##0');
                    $row_t_realisasi += $val;
                    $grand_total_realisasi[$bln_no] += $val;
                }
                $c++;
            }
            $sheet->setCellValueExplicit('O' . $r, $row_t_realisasi, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('O' . $r)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('O' . $r)->getFont()->setBold(true);

            $r++;
        }

        // 7. Baris Target Cabang (Grand Total)
        $sheet->setCellValue('A' . $r, 'Target Cabang');
        $sheet->mergeCells('A' . $r . ':A' . ($r + 1));
        $sheet->getStyle('A' . $r . ':O' . ($r + 1))->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');

        $sheet->setCellValue('B' . $r, 'Target Penagihan');
        $c = 'C';
        $total_cabang_t = 0;
        foreach ($bulan as $b) {
            $bln_no = (int)$b['bulan_no'];
            if ($tahun == $tahun_sekarang && $bln_no > $bulan_sekarang) {
                $sheet->setCellValue($c . $r, '-');
                $sheet->getStyle($c . $r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            } else {
                $gt = $grand_total_target[$bln_no];
                $sheet->setCellValueExplicit($c . $r, $gt, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle($c . $r)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle($c . $r)->getFont()->setBold(true);
                $total_cabang_t += $gt;
            }
            $c++;
        }
        $sheet->setCellValueExplicit('O' . $r, $total_cabang_t, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('O' . $r)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('O' . $r)->getFont()->setBold(true);

        $r++;
        $sheet->setCellValue('B' . $r, 'Realisasi Tagihan');
        $c = 'C';
        $total_cabang_r = 0;
        foreach ($bulan as $b) {
            $bln_no = (int)$b['bulan_no'];
            if ($tahun == $tahun_sekarang && $bln_no > $bulan_sekarang) {
                $sheet->setCellValue($c . $r, '-');
                $sheet->getStyle($c . $r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            } else {
                $gp = $grand_total_realisasi[$bln_no];
                $sheet->setCellValueExplicit($c . $r, $gp, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle($c . $r)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle($c . $r)->getFont()->setBold(true);
                $total_cabang_r += $gp;
            }
            $c++;
        }
        $sheet->setCellValueExplicit('O' . $r, $total_cabang_r, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('O' . $r)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('O' . $r)->getFont()->setBold(true);

        // 8. Styling Akhir
        $sheet->getStyle('A4:O' . $r)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

        // 9. Output
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Report_Target_Penagihan_' . $tahun . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /**
     * Hitung rekap Target Penagihan (BEKU / historis) untuk semua sales x 12 bulan.
     *
     * Definisi target bulan M untuk seorang sales:
     *   Jumlah sisa piutang dari setiap invoice miliknya yang jatuh tempo <= akhir bulan M,
     *   dihitung PER KONDISI CUTOFF (akhir bulan M-1) = grand_total dikurangi total pembayaran
     *   yang diterima s/d akhir bulan M-1. Hanya invoice dengan sisa > 0 pada cutoff yang dihitung.
     *
     * Angka ini BEKU: tidak berubah walau invoice sudah lunas hari ini, karena tidak
     * bergantung pada kolom a.piutang yang live.
     *
     * Implementasi: bulk-load semua invoice + pembayaran (dikelompok per bulan) dalam 2 query,
     * lalu agregasi di PHP. Jauh lebih cepat daripada correlated subquery per sales per bulan.
     *
     * @param  int|string $tahun          Tahun laporan
     * @param  int        $tahun_sekarang Tahun berjalan
     * @param  int        $bulan_sekarang Bulan berjalan (1-12)
     * @return array      rekap_target[id_sales][bulan] = (float) target
     */
    private function hitung_rekap_target($tahun, $tahun_sekarang, $bulan_sekarang)
    {
        // 1) Bulk-load semua invoice milik sales (department 2)
        $this->db->select('a.id_invoice, c.id as id_sales, a.grand_total, a.jatuh_tempo', false);
        $this->db->from('tr_invoice_sales a');
        $this->db->join('master_customers b', 'a.id_customer = b.id_customer');
        $this->db->join('employee c', 'b.id_karyawan = c.id');
        $this->db->where('c.department', '2');
        $invoices = $this->db->get()->result_array();

        // 2) Bulk-load total pembayaran per invoice per bulan (YYYY-MM)
        $this->db->select("pd.no_invoice, DATE_FORMAT(p.tgl_pembayaran, '%Y-%m') as ym, SUM(pd.total_bayar_idr) as amt", false);
        $this->db->from('tr_invoice_payment_detail pd');
        $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = pd.kd_pembayaran');
        $this->db->group_by("pd.no_invoice, DATE_FORMAT(p.tgl_pembayaran, '%Y-%m')");
        $pay_rows = $this->db->get()->result_array();

        // Susun map pembayaran: pay[id_invoice][ym] = jumlah
        $pay = [];
        foreach ($pay_rows as $pr) {
            $pay[$pr['no_invoice']][$pr['ym']] = (float)$pr['amt'];
        }

        // 3) Agregasi target per sales per bulan di PHP
        $rekap_target = [];
        for ($m = 1; $m <= 12; $m++) {
            // Lewati bulan yang belum terjadi (untuk tahun berjalan)
            if ($tahun == $tahun_sekarang && $m > $bulan_sekarang) {
                continue;
            }

            $akhir_bulan = date('Y-m-t', strtotime("$tahun-$m-01"));
            // Cutoff = pembayaran diperhitungkan sampai akhir bulan sebelumnya (ym <= cutoff_ym)
            $cutoff_ym   = date('Y-m', strtotime("$tahun-$m-01 -1 month"));

            foreach ($invoices as $inv) {
                // Invoice harus jatuh tempo <= akhir bulan M
                if (empty($inv['jatuh_tempo']) || $inv['jatuh_tempo'] > $akhir_bulan) {
                    continue;
                }

                $id  = $inv['id_invoice'];
                $sid = $inv['id_sales'];

                // Total pembayaran yang diterima sampai cutoff
                $bayar_cutoff = 0.0;
                if (!empty($pay[$id])) {
                    foreach ($pay[$id] as $ym => $amt) {
                        if ($ym <= $cutoff_ym) {
                            $bayar_cutoff += $amt;
                        }
                    }
                }

                $sisa = (float)$inv['grand_total'] - $bayar_cutoff;
                if ($sisa > 0) {
                    if (!isset($rekap_target[$sid][$m])) {
                        $rekap_target[$sid][$m] = 0.0;
                    }
                    $rekap_target[$sid][$m] += $sisa;
                }
            }
        }

        return $rekap_target;
    }

    /**
     * Form Perhitungan Komisi (AJAX - load di modal)
     * Parameter POST: id_sales, nama_sales, bulan, tahun
     */
    public function form_komisi()
    {
        $id_sales   = $this->input->post('id_sales');
        $nama_sales = $this->input->post('nama_sales');
        $bulan      = (int)$this->input->post('bulan');
        $tahun      = $this->input->post('tahun') ?? date('Y');

        // Ambil nama bulan
        $bln_row = $this->db->where('bulan_no', $bulan)->get('cr_bulan')->row_array();
        $nama_bulan = $bln_row ? $bln_row['bulan'] : 'Bulan ' . $bulan;
        $bulan_id = $bln_row ? $bln_row['bulan_id'] : '';

        // Cek apakah sudah ada data komisi tersimpan
        $komisi = $this->db->get_where('komisi_realisasi', [
            'id_karyawan' => $id_sales,
            'bulan_id'    => $bulan_id,
            'tahun'       => $tahun
        ])->row();

        // Hitung target & pencapaian dari data report
        $akhir_bulan = date('Y-m-t', strtotime("$tahun-$bulan-01"));
        $awal_bulan = "$tahun-" . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-01";
        $today = date('Y-m-d');

        // 1. Target Tagihan Ontime = piutang yang jatuh tempo masih di bulan ini ATAU sudah lewat <= 15 hari dari hari ini
        // Sama dengan logika ringkasan: invoice yang statusnya "On Time"
        $this->db->select("SUM(a.piutang) as total", false);
        $this->db->from('tr_invoice_sales a');
        $this->db->join('master_customers b', 'a.id_customer = b.id_customer');
        $this->db->join('employee c', 'b.id_karyawan = c.id');
        $this->db->where('c.id', $id_sales);
        $this->db->where('a.piutang >', 0);
        $this->db->where('a.jatuh_tempo <=', $akhir_bulan);
        $this->db->where("(
            (YEAR(a.jatuh_tempo) = YEAR('$today') AND MONTH(a.jatuh_tempo) = MONTH('$today'))
            OR (a.jatuh_tempo >= '$today')
            OR (DATEDIFF('$today', a.jatuh_tempo) <= 15)
        )", null, false);
        $result = $this->db->get()->row_array();
        $target_ontime = (float)($result['total'] ?? 0);

        // 2. Target Tagihan Tunggakan = piutang yang sudah lewat > 15 hari dari hari ini
        $this->db->select("SUM(a.piutang) as total", false);
        $this->db->from('tr_invoice_sales a');
        $this->db->join('master_customers b', 'a.id_customer = b.id_customer');
        $this->db->join('employee c', 'b.id_karyawan = c.id');
        $this->db->where('c.id', $id_sales);
        $this->db->where('a.piutang >', 0);
        $this->db->where('a.jatuh_tempo <=', $akhir_bulan);
        $this->db->where("a.jatuh_tempo < '$today'", null, false);
        $this->db->where("DATEDIFF('$today', a.jatuh_tempo) > 15", null, false);
        $result = $this->db->get()->row_array();
        $target_tunggakan = (float)($result['total'] ?? 0);

        // 3. Pencapaian Tagihan Ontime = total bayar untuk invoice yang jatuh tempo bulan ini, tanggal bayar <= jatuh tempo
        $this->db->select("SUM(pd.total_bayar_idr) as total", false);
        $this->db->from('tr_invoice_payment_detail pd');
        $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = pd.kd_pembayaran');
        $this->db->join('tr_invoice_sales a', 'a.id_invoice = pd.no_invoice');
        $this->db->join('master_customers b', 'a.id_customer = b.id_customer');
        $this->db->join('employee c', 'b.id_karyawan = c.id');
        $this->db->where('c.id', $id_sales);
        $this->db->where("YEAR(a.jatuh_tempo) = " . (int)$tahun, null, false);
        $this->db->where("MONTH(a.jatuh_tempo) = " . (int)$bulan, null, false);
        $this->db->where('a.total_bayar >', 0);
        $this->db->where("p.tgl_pembayaran <= a.jatuh_tempo", null, false);
        $result = $this->db->get()->row_array();
        $realisasi_ontime = (float)($result['total'] ?? 0);

        // 4. Pencapaian Tagihan Tunggakan = total bayar untuk invoice yang jatuh tempo bulan ini, tanggal bayar > jatuh tempo
        $this->db->select("SUM(pd.total_bayar_idr) as total", false);
        $this->db->from('tr_invoice_payment_detail pd');
        $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = pd.kd_pembayaran');
        $this->db->join('tr_invoice_sales a', 'a.id_invoice = pd.no_invoice');
        $this->db->join('master_customers b', 'a.id_customer = b.id_customer');
        $this->db->join('employee c', 'b.id_karyawan = c.id');
        $this->db->where('c.id', $id_sales);
        $this->db->where("YEAR(a.jatuh_tempo) = " . (int)$tahun, null, false);
        $this->db->where("MONTH(a.jatuh_tempo) = " . (int)$bulan, null, false);
        $this->db->where('a.total_bayar >', 0);
        $this->db->where("p.tgl_pembayaran > a.jatuh_tempo", null, false);
        $result = $this->db->get()->row_array();
        $realisasi_tunggakan = (float)($result['total'] ?? 0);

        // 5. Target Penjualan dari tabel target_penjualan berdasarkan id_karyawan dan bulan
        $target_penjualan_val = 0;
        $target_row = $this->db->get_where('target_penjualan', ['id_karyawan' => $id_sales])->row();
        if ($target_row && !empty($bulan_id) && isset($target_row->{$bulan_id})) {
            $target_penjualan_val = (float)$target_row->{$bulan_id};
        }

        // 6. Realisasi Penjualan = actual penjualan berdasarkan invoice (delivery_date), sama seperti Report Penjualan per Sales
        $this->db->select("SUM(i.grand_total) as total", false);
        $this->db->from('tr_invoice_sales i');
        $this->db->join('master_customers c', 'c.id_customer = i.id_customer', 'left');
        $this->db->where('c.id_karyawan', $id_sales);
        $this->db->where("YEAR(i.delivery_date) = " . (int)$tahun, null, false);
        $this->db->where("MONTH(i.delivery_date) = " . (int)$bulan, null, false);
        $this->db->where('IFNULL(i.is_cancel,0)', 0);
        $result = $this->db->get()->row_array();
        $realisasi_penjualan_val = (float)($result['total'] ?? 0);

        $data = [
            'id_sales'                => $id_sales,
            'nama_sales'              => $nama_sales,
            'bulan'                   => $bulan,
            'bulan_id'                => $bulan_id,
            'nama_bulan'              => $nama_bulan,
            'tahun'                   => $tahun,
            'komisi'                  => $komisi,
            'target_ontime'           => $target_ontime,
            'realisasi_ontime'        => $realisasi_ontime,
            'target_tunggakan'        => $target_tunggakan,
            'realisasi_tunggakan'     => $realisasi_tunggakan,
            'target_penjualan_val'    => $target_penjualan_val,
            'realisasi_penjualan_val' => $realisasi_penjualan_val,
        ];

        $this->load->view('form_komisi', $data);
    }

    /**
     * Export Detail Excel per Sales per Bulan
     * Untuk keperluan rekonsiliasi data Rencana Tagihan vs Realisasi Tagihan
     * 
     * Parameter GET:
     * - tahun: tahun data
     * - bulan: nomor bulan (1-12)
     * - id_sales: ID karyawan/sales
     * - tipe: 'target' (rencana penagihan) atau 'realisasi' (realisasi tagihan)
     */
    public function export_detail()
    {
        $tahun    = $this->input->get('tahun') ?? date('Y');
        $bulan    = (int)($this->input->get('bulan') ?? date('n'));
        $id_sales = $this->input->get('id_sales');
        $tipe     = $this->input->get('tipe') ?? 'target'; // target atau realisasi

        // Validasi
        if (empty($id_sales) || $bulan < 1 || $bulan > 12) {
            show_error('Parameter tidak valid.', 400);
            return;
        }

        // Ambil nama sales
        $sales = $this->db->where('id', $id_sales)->get('employee')->row_array();
        $nama_sales = $sales ? ucwords($sales['nm_karyawan']) : 'Unknown';

        // Ambil nama bulan
        $bln_row = $this->db->where('bulan_no', $bulan)->get('cr_bulan')->row_array();
        $nama_bulan = $bln_row ? $bln_row['bulan'] : 'Bulan ' . $bulan;

        // Hitung cutoff date (akhir bulan sebelumnya)
        // Cutoff untuk perhitungan sisa piutang: sampai akhir bulan sebelum bulan laporan
        $cutoff_date = date('Y-m-t', strtotime("$tahun-$bulan-01 -1 month"));

        // Query data detail berdasarkan tipe
        if ($tipe == 'target') {
            // Target Penagihan: invoice yang jatuh tempo <= akhir bulan dan masih punya sisa piutang pada cutoff
            $akhir_bulan = date('Y-m-t', strtotime("$tahun-$bulan-01"));

            // Tambahkan subquery untuk hitung sisa piutang dengan cutoff
            $this->db->select("
                a.id_invoice as no_invoice, 
                a.nm_customer, 
                a.created_on as tgl_invoice, 
                a.jatuh_tempo, 
                a.grand_total as total_invoice, 
                pd.total_bayar_idr as total_bayar, 
                a.piutang, 
                p.tgl_pembayaran as tanggal_bayar, 
                pd.kd_pembayaran as no_penerimaan,
                (
                    a.grand_total - COALESCE((
                        SELECT SUM(pd2.total_bayar_idr)
                        FROM tr_invoice_payment_detail pd2
                        JOIN tr_invoice_payment p2 ON p2.kd_pembayaran = pd2.kd_pembayaran
                        WHERE pd2.no_invoice = a.id_invoice
                        AND p2.tgl_pembayaran <= '$cutoff_date'
                    ), 0)
                ) as sisa_piutang_cutoff
            ", false);
            $this->db->from('tr_invoice_sales a');
            $this->db->join('master_customers b', 'a.id_customer = b.id_customer', 'left');
            $this->db->join('tr_invoice_payment_detail pd', 'pd.no_invoice = a.id_invoice', 'left');
            $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = pd.kd_pembayaran', 'left');
            $this->db->where('b.id_karyawan', $id_sales);
            $this->db->where('a.jatuh_tempo <=', $akhir_bulan);
            // Target Penagihan (BEKU): saring berdasarkan sisa piutang pada kondisi cutoff,
            // bukan kolom a.piutang yang live, agar konsisten dengan angka di report.
            $this->db->having('sisa_piutang_cutoff >', 0);
            $this->db->order_by('a.id_invoice', 'ASC');
            $this->db->order_by('p.tgl_pembayaran', 'ASC');
            $query = $this->db->get();
            $data_detail = $query ? $query->result_array() : [];

            $judul = 'Detail Target Penagihan';
            $filename = 'Detail_Target_Penagihan_' . str_replace(' ', '_', $nama_sales) . '_' . $nama_bulan . '_' . $tahun . '.xls';
        } else {
            // Realisasi Tagihan: pembayaran yang diterima pada bulan tersebut, berdasarkan tanggal pembayaran
            $this->db->select("
                a.id_invoice as no_invoice, 
                a.nm_customer, 
                a.created_on as tgl_invoice, 
                a.jatuh_tempo, 
                a.grand_total as total_invoice, 
                pd.total_bayar_idr as total_bayar, 
                a.piutang, 
                p.tgl_pembayaran as tanggal_bayar, 
                pd.kd_pembayaran as no_penerimaan,
                (
                    a.grand_total - COALESCE((
                        SELECT SUM(pd2.total_bayar_idr)
                        FROM tr_invoice_payment_detail pd2
                        JOIN tr_invoice_payment p2 ON p2.kd_pembayaran = pd2.kd_pembayaran
                        WHERE pd2.no_invoice = a.id_invoice
                        AND p2.tgl_pembayaran <= '$cutoff_date'
                    ), 0)
                ) as sisa_piutang_cutoff
            ", false);
            $this->db->from('tr_invoice_sales a');
            $this->db->join('master_customers b', 'a.id_customer = b.id_customer', 'left');
            $this->db->join('tr_invoice_payment_detail pd', 'pd.no_invoice = a.id_invoice', 'left');
            $this->db->join('tr_invoice_payment p', 'p.kd_pembayaran = pd.kd_pembayaran', 'left');
            $this->db->where('b.id_karyawan', $id_sales);
            $this->db->where("YEAR(p.tgl_pembayaran) = " . (int)$tahun, null, false);
            $this->db->where("MONTH(p.tgl_pembayaran) = " . (int)$bulan, null, false);
            $this->db->where('pd.total_bayar_idr >', 0);
            $this->db->order_by('a.id_invoice', 'ASC');
            $this->db->order_by('p.tgl_pembayaran', 'ASC');
            $query = $this->db->get();
            $data_detail = $query ? $query->result_array() : [];

            $judul = 'Detail Realisasi Tagihan';
            $filename = 'Detail_Realisasi_Tagihan_' . str_replace(' ', '_', $nama_sales) . '_' . $nama_bulan . '_' . $tahun . '.xls';
        }

        // Setup PHPExcel
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        // Redam notice/warning/deprecated dari PHPExcel (library lama) supaya tidak bocor
        // ke output binary .xls dan tidak memicu "headers already sent"
        error_reporting(E_ERROR | E_PARSE);
        $this->load->library('PHPExcel');
        PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', strtoupper($judul));
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Info
        $sheet->setCellValue('A2', 'Sales: ' . $nama_sales);
        $sheet->setCellValue('A3', 'Periode: ' . $nama_bulan . ' ' . $tahun);
        $sheet->getStyle('A2:A3')->getFont()->setBold(true);

        // Keterangan tambahan
        $sheet->setCellValue('J2', 'tgl bayar ambil dari tanggal terima uang');
        $sheet->getStyle('J2')->getFont()->setItalic(true)->setSize(9);

        // Header tabel
        $headers = ['A' => 'No', 'B' => 'No Invoice', 'C' => 'Customer', 'D' => 'Tgl Invoice', 'E' => 'Jatuh Tempo', 'F' => 'Total Invoice', 'G' => 'Tanggal Bayar', 'H' => 'No Penerimaan', 'I' => 'Total Bayar', 'J' => 'Sisa Piutang', 'K' => 'Selisih tanggal jatuh tempo dengan tanggal bayar', 'L' => 'On time / Tunggakan'];
        $rowHeader = 5;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $sheet->getStyle($col . $rowHeader)->getFont()->setBold(true);
            $sheet->getStyle($col . $rowHeader)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle($col . $rowHeader)->getAlignment()->setWrapText(true);
            $sheet->getStyle($col . $rowHeader)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('4472C4');
            $sheet->getStyle($col . $rowHeader)->getFont()->getColor()->setRGB('FFFFFF');
        }

        // Isi data
        $r = $rowHeader + 1;
        $no = 1;
        $total_invoice_sum = 0;
        $total_bayar_sum = 0;
        $total_piutang_sum = 0;
        $total_ontime_sum = 0;
        $total_nunggakan_sum = 0;
        $total_bayar_ontime = 0;
        $total_bayar_nunggakan = 0;
        $prev_invoice = ''; // Track invoice sebelumnya untuk sisa piutang

        foreach ($data_detail as $row) {
            $sheet->setCellValue('A' . $r, $no);
            $sheet->setCellValue('B' . $r, $row['no_invoice']);
            $sheet->setCellValue('C' . $r, $row['nm_customer']);
            $sheet->setCellValue('D' . $r, $row['tgl_invoice']);
            $sheet->setCellValue('E' . $r, $row['jatuh_tempo']);

            $total_inv = (float)$row['total_invoice'];
            $total_bay = (float)$row['total_bayar'];
            // Gunakan sisa_piutang_cutoff yang sudah dihitung dengan cutoff date
            $piutang_cutoff = (float)$row['sisa_piutang_cutoff'];

            $sheet->setCellValueExplicit('F' . $r, $total_inv, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('#,##0');

            // Tanggal Bayar - format hanya tanggal (tanpa waktu)
            $tanggal_bayar = '';
            if (!empty($row['tanggal_bayar'])) {
                $tanggal_bayar = date('Y-m-d', strtotime($row['tanggal_bayar']));
            }
            $sheet->setCellValue('G' . $r, $tanggal_bayar);

            // No Penerimaan
            $no_penerimaan = !empty($row['no_penerimaan']) ? $row['no_penerimaan'] : '';
            $sheet->setCellValue('H' . $r, $no_penerimaan);

            // Total Bayar
            $sheet->setCellValueExplicit('I' . $r, $total_bay, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('I' . $r)->getNumberFormat()->setFormatCode('#,##0');

            // Sisa Piutang - hanya tampilkan di baris pertama per invoice (dengan cutoff)
            $piutang_tracked = false;
            $is_first_row = false;
            if ($row['no_invoice'] !== $prev_invoice) {
                $sheet->setCellValueExplicit('J' . $r, $piutang_cutoff, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle('J' . $r)->getNumberFormat()->setFormatCode('#,##0');
                $total_piutang_sum += $piutang_cutoff;
                $piutang_tracked = true;
                $is_first_row = true;
            } else {
                $sheet->setCellValue('J' . $r, '');
            }
            $prev_invoice = $row['no_invoice'];

            // Selisih & status On Time / Nunggakan
            $selisih_hari = '';
            $status_ontime = '';

            if ($tipe == 'target') {
                // Untuk Target Penagihan: bandingkan jatuh tempo vs cutoff date
                if (!empty($row['jatuh_tempo'])) {
                    $date_jatuh_tempo = new DateTime($row['jatuh_tempo']);
                    $date_cutoff = new DateTime($cutoff_date);

                    // Jika jatuh tempo masih di bulan cutoff → On Time
                    if ($date_jatuh_tempo->format('Y-m') == $date_cutoff->format('Y-m')) {
                        $selisih_hari = 0;
                        $status_ontime = 'On Time';
                    } elseif ($date_jatuh_tempo < $date_cutoff) {
                        // Jatuh tempo sudah lewat dari cutoff date
                        $diff = $date_cutoff->diff($date_jatuh_tempo);
                        $selisih_hari = $diff->days;

                        if ($selisih_hari <= 15) {
                            $status_ontime = 'On Time';
                        } else {
                            $status_ontime = 'Nunggakan';
                        }
                    } else {
                        // Jatuh tempo belum lewat (setelah cutoff)
                        $selisih_hari = 0;
                        $status_ontime = 'On Time';
                    }
                }
            } else {
                // Untuk Realisasi Tagihan: bandingkan tanggal bayar vs jatuh tempo
                if (!empty($tanggal_bayar) && !empty($row['jatuh_tempo'])) {
                    $date_jatuh_tempo = new DateTime($row['jatuh_tempo']);
                    $date_bayar = new DateTime($tanggal_bayar);
                    $diff = $date_jatuh_tempo->diff($date_bayar);
                    $selisih_hari = ($date_bayar > $date_jatuh_tempo) ? $diff->days : -$diff->days;

                    if ($date_bayar <= $date_jatuh_tempo) {
                        $status_ontime = 'On Time';
                    } else {
                        $status_ontime = 'Nunggakan';
                    }
                }
            }

            // Kolom K: Selisih Hari (kosongkan sesuai format BA)
            $sheet->setCellValue('K' . $r, '');
            $sheet->getStyle('K' . $r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            // Kolom L: On time / Tunggakan - hanya tampilkan di baris pertama per invoice
            if ($is_first_row) {
                $sheet->setCellValue('L' . $r, $status_ontime);
                $sheet->getStyle('L' . $r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                // Highlight kuning untuk row yang nunggakan
                if ($status_ontime == 'Nunggakan') {
                    $sheet->getStyle('L' . $r)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
                }

                // Track on time / nunggakan berdasarkan piutang (hanya baris pertama per invoice)
                if ($status_ontime == 'On Time') {
                    $total_ontime_sum += $piutang_cutoff;
                } elseif ($status_ontime == 'Nunggakan') {
                    $total_nunggakan_sum += $piutang_cutoff;
                }
            } else {
                $sheet->setCellValue('L' . $r, '');
            }

            // Track total bayar (setiap baris pembayaran)
            $total_bayar_sum += $total_bay;

            $no++;
            $r++;
        }

        // Baris total
        $sheet->setCellValue('A' . $r, '');
        $sheet->setCellValue('B' . $r, '');
        $sheet->setCellValue('C' . $r, '');
        $sheet->setCellValue('D' . $r, '');
        $sheet->setCellValue('E' . $r, 'TOTAL');
        $sheet->getStyle('E' . $r)->getFont()->setBold(true);

        $sheet->setCellValue('F' . $r, '');

        $sheet->setCellValue('G' . $r, '');
        $sheet->setCellValue('H' . $r, '');

        $sheet->setCellValueExplicit('I' . $r, $total_bayar_sum, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('I' . $r)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('I' . $r)->getFont()->setBold(true);

        $sheet->setCellValueExplicit('J' . $r, $total_piutang_sum, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('J' . $r)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('J' . $r)->getFont()->setBold(true);

        $sheet->setCellValue('K' . $r, '');
        $sheet->setCellValue('L' . $r, '');

        // Border
        $sheet->getStyle('A' . $rowHeader . ':L' . $r)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

        // Ringkasan On Time / Tagihan
        $r += 2;
        $ringkasan_title = ($tipe == 'target') ? 'Target Tagihan' : 'Realisasi Tagihan';
        $sheet->setCellValue('E' . $r, $ringkasan_title);
        $sheet->getStyle('E' . $r . ':F' . $r)->getFont()->setBold(true);
        $sheet->getStyle('E' . $r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->mergeCells('E' . $r . ':F' . $r);

        $r++;
        $sheet->setCellValue('E' . $r, 'RINGKASAN ON TIME / TAGIHAN');
        $sheet->getStyle('E' . $r)->getFont()->setBold(true);

        $r++;
        $sheet->setCellValue('E' . $r, 'Sisa Piutang (Rp)');
        $sheet->setCellValueExplicit('F' . $r, $total_piutang_sum, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('#,##0');

        $r++;
        $sheet->setCellValue('E' . $r, 'Total Target On Time (Rp)');
        $sheet->setCellValueExplicit('F' . $r, $total_ontime_sum, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('#,##0');

        $r++;
        $sheet->setCellValue('E' . $r, 'Total Target Nunggak (Rp)');
        $sheet->setCellValueExplicit('F' . $r, $total_nunggakan_sum, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('#,##0');

        $r++;
        $sheet->setCellValue('E' . $r, '% On Time (Rp)');
        $pct_ontime = ($total_piutang_sum > 0) ? ($total_ontime_sum / $total_piutang_sum) * 100 : 0;
        $sheet->setCellValue('F' . $r, number_format($pct_ontime, 1) . '%');
        $sheet->getStyle('F' . $r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        // Border ringkasan
        $ringkasan_start = $r - 5;
        $sheet->getStyle('E' . $ringkasan_start . ':F' . $r)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

        // Output
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
