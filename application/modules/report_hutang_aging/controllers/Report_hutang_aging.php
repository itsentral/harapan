<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_hutang_aging extends Admin_Controller
{
    protected $viewPermission = 'Report_Hutang_Aging.View';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Report_hutang_aging/Report_hutang_aging_model');
        $this->template->title('Report Umur Hutang');
        $this->template->page_icon('fa fa-clock-o');
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->render('index');
    }

    /**
     * AJAX: ambil data umur hutang per invoice (per hari ini).
     */
    public function get_data()
    {
        $result = $this->Report_hutang_aging_model->get_aging_per_invoice();

        echo json_encode([
            'status'            => true,
            'tanggal'           => date('Y-m-d'),
            'data'              => $result['rows'],
            'total_sisa_hutang' => $result['total_sisa_hutang'],
            'bucket_totals'     => $result['bucket_totals'],
        ]);
    }

    /**
     * Halaman print / cetak report umur hutang.
     */
    public function print_report()
    {
        $result = $this->Report_hutang_aging_model->get_aging_per_invoice();

        $data = [
            'tanggal'           => date('Y-m-d'),
            'data_report'       => $result['rows'],
            'total_sisa_hutang' => $result['total_sisa_hutang'],
            'bucket_totals'     => $result['bucket_totals'],
        ];

        $this->load->view('print_report', $data);
    }

    /**
     * Export Excel report umur hutang.
     */
    public function export_excel()
    {
        $result            = $this->Report_hutang_aging_model->get_aging_per_invoice();
        $data_report       = $result['rows'];
        $total_sisa_hutang = $result['total_sisa_hutang'];
        $bucket_totals     = $result['bucket_totals'];
        $tanggal           = date('Y-m-d');

        $this->load->library('PHPExcel');

        $objPHPExcel = new PHPExcel();
        $sheet       = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Umur Hutang');

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
        $sheet->setCellValue('A1', 'REPORT UMUR HUTANG (AGING)');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Per Tanggal: ' . date('d F Y', strtotime($tanggal)));
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Header kolom
        $headers = [
            'Supplier',
            'Tanggal Invoice',
            'No PO',
            'No Invoice',
            'Sisa Hutang',
            'Tgl Terakhir Bayar',
            '< 30 hari',
            '< 60 hari',
            '< 90 hari',
            '< 120 hari',
        ];
        $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . '4', $h);
            $sheet->getStyle($cols[$i] . '4')->applyFromArray($style_header);
            $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
        }

        $row = 5;
        foreach ($data_report as $d) {
            $sheet->setCellValue('A' . $row, $d['nm_supplier']);

            if (!empty($d['tgl_invoice'])) {
                $excel_tgl = $this->_to_excel_date($d['tgl_invoice']);
                $sheet->setCellValue('B' . $row, $excel_tgl);
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('dd mmm yyyy');
            }

            $sheet->setCellValueExplicit('C' . $row, $d['no_po'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $row, $d['id_invoice'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $row, (float)$d['sisa_hutang']);

            if (!empty($d['tgl_terakhir_bayar'])) {
                $excel_tgl_bayar = $this->_to_excel_date($d['tgl_terakhir_bayar']);
                $sheet->setCellValue('F' . $row, $excel_tgl_bayar);
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('dd mmm yyyy');
            }

            $sheet->setCellValue('G' . $row, (float)$d['b_30']);
            $sheet->setCellValue('H' . $row, (float)$d['b_60']);
            $sheet->setCellValue('I' . $row, (float)$d['b_90']);
            $sheet->setCellValue('J' . $row, (float)$d['b_120']);

            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G' . $row . ':J' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($style_data);
            $row++;
        }

        // Total row
        $sheet->setCellValue('A' . $row, 'Total');
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->setCellValue('E' . $row, (float)$total_sisa_hutang);
        $sheet->setCellValue('G' . $row, (float)$bucket_totals['<30']);
        $sheet->setCellValue('H' . $row, (float)$bucket_totals['<60']);
        $sheet->setCellValue('I' . $row, (float)$bucket_totals['<90']);
        $sheet->setCellValue('J' . $row, (float)$bucket_totals['<120']);
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('G' . $row . ':J' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row . ':J' . $row)->applyFromArray($style_total);

        $filename = 'Report_Umur_Hutang_' . $tanggal . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $writer->save('php://output');
        exit;
    }

    /**
     * Konversi string tanggal DB menjadi serial tanggal Excel tanpa pergeseran
     * timezone (lihat penjelasan di modul Report Piutang).
     */
    private function _to_excel_date($dateStr)
    {
        if (empty($dateStr)) {
            return null;
        }
        try {
            $dt = new DateTime($dateStr);
        } catch (Exception $e) {
            return null;
        }
        return PHPExcel_Shared_Date::PHPToExcel($dt);
    }
}
