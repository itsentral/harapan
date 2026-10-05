<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_piutang extends Admin_Controller
{
    protected $viewPermission   = 'Report_Piutang.View';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Report_piutang/Report_piutang_model');
        $this->template->title('Report Piutang Per Invoice');
        $this->template->page_icon('fa fa-file-text-o');
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->render('index');
    }

    /**
     * Halaman rekap piutang PER CUSTOMER (untuk dibandingkan dengan Kartu Piutang).
     */
    public function per_customer()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->title('Report Piutang Per Customer');
        $this->template->render('per_customer');
    }

    /**
     * AJAX: ambil rekap piutang per customer s/d tanggal.
     * POST: tanggal (Y-m-d)
     */
    public function get_data_customer()
    {
        $tanggal = $this->input->post('tanggal');

        if (empty($tanggal)) {
            echo json_encode(['status' => false, 'message' => 'Tanggal tidak boleh kosong.']);
            return;
        }

        $result = $this->Report_piutang_model->get_piutang_per_customer($tanggal);

        echo json_encode([
            'status'        => true,
            'data'          => $result['rows'],
            'total_piutang' => $result['total_piutang'],
        ]);
    }

    /**
     * Export Excel rekap piutang per customer.
     * GET: tanggal via URI segment (format Y-m-d)
     */
    public function export_excel_customer($tanggal = null)
    {
        if (empty($tanggal)) {
            show_error('Tanggal tidak ditemukan.');
        }

        $result        = $this->Report_piutang_model->get_piutang_per_customer($tanggal);
        $data_report   = $result['rows'];
        $total_piutang = $result['total_piutang'];

        $this->load->library('PHPExcel');

        $objPHPExcel = new PHPExcel();
        $sheet       = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Piutang Per Customer');

        $style_header = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '1A5276']],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
            'borders'   => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];
        $style_data = [
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];
        $style_total = [
            'font'      => ['bold' => true],
            'fill'      => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'EAF2FB']],
            'borders'   => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT],
        ];

        $sheet->setCellValue('A1', 'REPORT PIUTANG PER CUSTOMER');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Per Tanggal: ' . date('d F Y', strtotime($tanggal)));
        $sheet->mergeCells('A2:E2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $headers = ['No', 'Customer', 'Total Invoice', 'Total Bayar', 'Sisa Piutang'];
        $cols    = ['A', 'B', 'C', 'D', 'E'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . '4', $h);
            $sheet->getStyle($cols[$i] . '4')->applyFromArray($style_header);
            $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
        }

        $row = 5;
        $no  = 1;
        foreach ($data_report as $d) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $d['name_customer']);
            $sheet->setCellValue('C' . $row, (float)$d['total_invoice']);
            $sheet->setCellValue('D' . $row, (float)$d['total_bayar']);
            $sheet->setCellValue('E' . $row, (float)$d['sisa_piutang']);

            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($style_data);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Total Piutang');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->setCellValue('E' . $row, (float)$total_piutang);
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($style_total);

        $filename = 'Report_Piutang_Per_Customer_' . $tanggal . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $writer->save('php://output');
        exit;
    }

    /**
     * AJAX: ambil data piutang per invoice s/d tanggal yang dipilih
     * POST: tanggal (Y-m-d)
     */
    public function get_data()
    {
        $tanggal = $this->input->post('tanggal');

        if (empty($tanggal)) {
            echo json_encode(['status' => false, 'message' => 'Tanggal tidak boleh kosong.']);
            return;
        }

        $result = $this->Report_piutang_model->get_piutang_per_invoice($tanggal);
        $data = $result['rows'];
        $total_piutang = $result['total_piutang'];

        echo json_encode([
            'status'        => true,
            'data'          => $data,
            'total_piutang' => $total_piutang,
        ]);
    }

    /**
     * Halaman print / cetak report piutang
     * GET: tanggal via URI segment (format Y-m-d)
     */
    public function print_report($tanggal = null)
    {
        if (empty($tanggal)) {
            show_error('Tanggal tidak ditemukan.');
        }

        $result        = $this->Report_piutang_model->get_piutang_per_invoice($tanggal);
        $data_report   = $result['rows'];
        $total_piutang = $result['total_piutang'];

        $data = [
            'tanggal'       => $tanggal,
            'data_report'   => $data_report,
            'total_piutang' => $total_piutang,
        ];

        $this->load->view('print_report', $data);
    }

    /**
     * Export Excel SUMMARY report piutang (1 baris per invoice).
     * Kolom: Customer, Tanggal Invoice, No Invoice, Nilai Invoice,
     *        Total Bayar, Sisa Piutang.
     * GET: tanggal via URI segment (format Y-m-d)
     */
    public function export_summary($tanggal = null)
    {
        if (empty($tanggal)) {
            show_error('Tanggal tidak ditemukan.');
        }

        $result        = $this->Report_piutang_model->get_piutang_per_invoice_summary($tanggal);
        $data_report   = $result['rows'];
        $total_piutang = $result['total_piutang'];

        $this->load->library('PHPExcel');

        $objPHPExcel = new PHPExcel();
        $sheet       = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Summary Piutang');

        $style_header = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '1A5276']],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
            'borders'   => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];
        $style_data = [
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];
        $style_total = [
            'font'      => ['bold' => true],
            'fill'      => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'EAF2FB']],
            'borders'   => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT],
        ];

        $sheet->setCellValue('A1', 'SUMMARY PIUTANG PER INVOICE');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Per Tanggal: ' . date('d F Y', strtotime($tanggal)));
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $headers = ['Customer', 'Tanggal Invoice', 'No Invoice', 'Nilai Invoice', 'Total Bayar', 'Sisa Piutang'];
        $cols    = ['A', 'B', 'C', 'D', 'E', 'F'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . '4', $h);
            $sheet->getStyle($cols[$i] . '4')->applyFromArray($style_header);
            $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
        }

        $months_id = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
            7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $row = 5;
        foreach ($data_report as $d) {
            $tgl_inv = !empty($d['tgl_invoice'])
                ? date('d', strtotime($d['tgl_invoice'])) . ' ' . $months_id[(int)date('n', strtotime($d['tgl_invoice']))] . ' ' . date('Y', strtotime($d['tgl_invoice']))
                : '';

            $sheet->setCellValue('A' . $row, $d['name_customer']);
            $sheet->setCellValue('B' . $row, $tgl_inv);
            $sheet->setCellValueExplicit('C' . $row, $d['id_invoice'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $row, (float)$d['nilai_invoice']);
            $sheet->setCellValue('E' . $row, (float)$d['total_bayar']);
            $sheet->setCellValue('F' . $row, (float)$d['sisa_piutang']);

            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($style_data);
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'Total Piutang');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->setCellValue('F' . $row, (float)$total_piutang);
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row . ':F' . $row)->applyFromArray($style_total);

        $filename = 'Summary_Piutang_' . $tanggal . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $writer->save('php://output');
        exit;
    }

    /**
     * Export Excel report piutang
     * GET: tanggal via URI segment (format Y-m-d)
     */
    public function export_excel($tanggal = null)
    {
        if (empty($tanggal)) {
            show_error('Tanggal tidak ditemukan.');
        }

        $result        = $this->Report_piutang_model->get_piutang_per_invoice($tanggal);
        $data_report   = $result['rows'];
        $total_piutang = $result['total_piutang'];

        $this->load->library('PHPExcel');

        $objPHPExcel = new PHPExcel();
        $sheet       = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Piutang Per Invoice');

        $style_header = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '1A5276']],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
            'borders'   => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];
        $style_data = [
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];
        $style_total = [
            'font'      => ['bold' => true],
            'fill'      => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'EAF2FB']],
            'borders'   => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT],
        ];

        // Title
        $sheet->setCellValue('A1', 'REPORT PIUTANG PER INVOICE');
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Per Tanggal: ' . date('d F Y', strtotime($tanggal)));
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Header kolom
        $headers = [
            'Customer',
            'Tanggal Invoice',
            'No Invoice',
            'Nilai Invoice',
            'Kode Penerimaan',
            'Tanggal Bayar',
            'Tanggal Dibuat',
            'Nilai Bayar',
            'Total Bayar',
            'Sisa Piutang',
            'Saldo Piutang'
        ];
        $cols    = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];

        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . '4', $h);
            $sheet->getStyle($cols[$i] . '4')->applyFromArray($style_header);
            $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
        }

        // Data rows
        $row = 5;
        $months_id = [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'Mei',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Agu',
            9 => 'Sep',
            10 => 'Okt',
            11 => 'Nov',
            12 => 'Des'
        ];

        foreach ($data_report as $d) {
            if ($d['is_first_row']) {
                $sheet->setCellValue('A' . $row, $d['name_customer']);
                // Tulis sebagai nilai tanggal Excel asli agar bisa di-sort kronologis
                if (!empty($d['tgl_invoice'])) {
                    $excel_tgl_inv = PHPExcel_Shared_Date::PHPToExcel(strtotime($d['tgl_invoice']));
                    $sheet->setCellValue('B' . $row, $excel_tgl_inv);
                    $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('dd mmm yyyy');
                }
                $sheet->setCellValueExplicit('C' . $row, $d['id_invoice'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue('D' . $row, (float)$d['nilai_invoice']);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            }

            $sheet->setCellValue('E' . $row, $d['kd_pembayaran']);
            // Tanggal bayar sebagai nilai tanggal Excel asli (bisa di-sort)
            if (!empty($d['tgl_bayar'])) {
                $excel_tgl_bayar = PHPExcel_Shared_Date::PHPToExcel(strtotime($d['tgl_bayar']));
                $sheet->setCellValue('F' . $row, $excel_tgl_bayar);
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('dd mmm yyyy');
            }
            // Tanggal dibuat (created_on) sebagai nilai tanggal Excel asli (bisa di-sort)
            if (!empty($d['tgl_dibuat'])) {
                $excel_tgl_dibuat = PHPExcel_Shared_Date::PHPToExcel(strtotime($d['tgl_dibuat']));
                $sheet->setCellValue('G' . $row, $excel_tgl_dibuat);
                $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('dd mmm yyyy');
            }
            $sheet->setCellValue('H' . $row, $d['nilai_bayar'] !== '' ? (float)$d['nilai_bayar'] : null);
            $sheet->setCellValue('I' . $row, $d['total_bayar'] !== '' ? (float)$d['total_bayar'] : null);
            $sheet->setCellValue('J' . $row, (float)$d['sisa_piutang']);
            $sheet->setCellValue('K' . $row, isset($d['saldo_piutang']) && $d['saldo_piutang'] !== '' ? (float)$d['saldo_piutang'] : null);

            $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0');

            $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($style_data);
            $row++;
        }

        // Total row
        $sheet->setCellValue('A' . $row, 'Total Piutang');
        $sheet->mergeCells('A' . $row . ':I' . $row);
        $sheet->setCellValue('J' . $row, (float)$total_piutang);
        $sheet->getStyle('J' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row . ':K' . $row)->applyFromArray($style_total);

        // Output
        $filename = 'Report_Piutang_' . $tanggal . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $writer->save('php://output');
        exit;
    }
}
