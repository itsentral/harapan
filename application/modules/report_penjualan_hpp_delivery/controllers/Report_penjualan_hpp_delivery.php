<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_penjualan_hpp_delivery extends Admin_Controller
{
    // Permission (pakai permission yang sama dengan report detail)
    protected $viewPermission   = 'Report_Penjualan_HPP.View';
    protected $addPermission    = 'Report_Penjualan_HPP.Add';
    protected $managePermission = 'Report_Penjualan_HPP.Manage';
    protected $deletePermission = 'Report_Penjualan_HPP.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->library(array('upload', 'Image_lib'));
        $this->load->model(array(
            'Report_penjualan_hpp_delivery/Report_penjualan_hpp_delivery_model',
        ));

        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->page_icon('fa fa-bar-chart');
        $this->template->title('Report Penjualan vs HPP (Per Delivery)');
        $this->template->render('index');
    }

    public function data_side_report()
    {
        $this->Report_penjualan_hpp_delivery_model->data_side_report();
    }

    public function export_excel_report()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $tgl_dari   = $this->input->get('tgl_dari', true);
        $tgl_sampai = $this->input->get('tgl_sampai', true);
        $search     = $this->input->get('search', true);

        $rows = $this->Report_penjualan_hpp_delivery_model->get_export_report($search, $tgl_dari, $tgl_sampai);

        $this->load->library("PHPExcel");
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Penjualan vs HPP (Delivery)');

        // =========================
        // STYLE
        // =========================
        $styleTitle = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'B30000']],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ]
        ];

        $tableHeader = [
            'font' => ['bold' => true, 'size' => 9],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrapText'   => true
            ],
            'borders' => [
                'allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            ],
            'fill' => [
                'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => 'D9EDF7']
            ]
        ];

        $tableBody = [
            'borders' => [
                'allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            ],
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
            'font' => ['size' => 9]
        ];

        $styleTotalRow = [
            'font' => ['bold' => true, 'size' => 9],
            'borders' => [
                'allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            ],
            'fill' => [
                'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => 'FFFFCC']
            ],
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ]
        ];

        // =========================
        // Row 1: JUDUL REPORT
        // =========================
        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'Report Penjualan vs HPP (Per Delivery)');
        $sheet->getStyle('A1')->applyFromArray($styleTitle);

        // Row 2: PERIODE (judul)
        $periodeText = '';
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $periodeText = 'Periode ' . date('d/m/Y', strtotime($tgl_dari)) . ' s/d ' . date('d/m/Y', strtotime($tgl_sampai));
        } elseif (!empty($tgl_dari)) {
            $periodeText = 'Periode Mulai ' . date('d/m/Y', strtotime($tgl_dari));
        } elseif (!empty($tgl_sampai)) {
            $periodeText = 'Periode Sampai ' . date('d/m/Y', strtotime($tgl_sampai));
        } else {
            $periodeText = 'Periode: Semua';
        }
        $sheet->mergeCells('A2:L2');
        $sheet->setCellValue('A2', $periodeText);
        $sheet->getStyle('A2')->applyFromArray($styleTitle);
        $sheet->getStyle('A2')->getFont()->setSize(11)->getColor()->setRGB('000000');

        // =========================
        // Row 4: Header
        // =========================
        $rowH1 = 4;

        $headers = [
            'A' => 'No',
            'B' => 'NOMOR DELIVERY',
            'C' => 'NO INVOICE',
            'D' => 'NOMOR SO',
            'E' => 'Nama Customer',
            'F' => 'Nama Sales',
            'G' => 'TGL INVOICE',
            'H' => 'JML ITEM',
            'I' => 'HARGA JUAL',
            'J' => 'HPP',
            'K' => 'LABA/RUGI KOTOR',
            'L' => 'PERSEN LABA/RUGI',
        ];

        foreach ($headers as $col => $judul) {
            $sheet->setCellValue("{$col}{$rowH1}", $judul);
        }
        $sheet->getStyle("A{$rowH1}:L{$rowH1}")->applyFromArray($tableHeader);

        // Column widths
        $colWidths = [
            'A' => 5, 'B' => 22, 'C' => 22, 'D' => 16, 'E' => 28, 'F' => 18,
            'G' => 18, 'H' => 10, 'I' => 18, 'J' => 18, 'K' => 18, 'L' => 14,
        ];
        foreach ($colWidths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Freeze pane
        $sheet->freezePane('A5');

        // =========================
        // DATA
        // =========================
        $rowNum = 5;
        $no = 1;

        $totalHargaJual = 0;
        $totalHPP       = 0;
        $totalLaba      = 0;

        foreach ($rows as $row) {
            $harga_jual  = (float) $row->harga_jual;
            $hpp         = (float) $row->hpp;
            $laba        = $harga_jual - $hpp;
            $persen_laba = $harga_jual > 0 ? ($laba / $harga_jual) : 0;

            $totalHargaJual += $harga_jual;
            $totalHPP       += $hpp;
            $totalLaba      += $laba;

            $sheet->setCellValueExplicit("A{$rowNum}", $no++, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("B{$rowNum}", strtoupper($row->id_delivery ?? ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowNum}", strtoupper($row->id_invoice ?? ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", strtoupper($row->id_so ?? ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$rowNum}", $row->nm_customer ?? '', PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$rowNum}", $row->created_by ?? '', PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$rowNum}", (!empty($row->created_on) ? date('d/m/Y H:i', strtotime($row->created_on)) : ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$rowNum}", (float) $row->jml_item, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("I{$rowNum}", $harga_jual, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("J{$rowNum}", $hpp, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("K{$rowNum}", $laba, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("L{$rowNum}", $persen_laba, PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $sheet->getStyle("A{$rowNum}:L{$rowNum}")->applyFromArray($tableBody);

            $sheet->getStyle("H{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("I{$rowNum}:K{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("L{$rowNum}")->getNumberFormat()->setFormatCode('0%');

            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowNum}:D{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowNum}:F{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("G{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rowNum}:K{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("L{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $rowNum++;
        }

        // =========================
        // TOTAL ROW
        // =========================
        $totalPersenLaba = $totalHargaJual > 0 ? ($totalLaba / $totalHargaJual) : 0;

        $sheet->mergeCells("A{$rowNum}:H{$rowNum}");
        $sheet->setCellValue("A{$rowNum}", 'TOTAL');
        $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValueExplicit("I{$rowNum}", $totalHargaJual, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("J{$rowNum}", $totalHPP, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("K{$rowNum}", $totalLaba, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("L{$rowNum}", $totalPersenLaba, PHPExcel_Cell_DataType::TYPE_NUMERIC);

        $sheet->getStyle("A{$rowNum}:L{$rowNum}")->applyFromArray($styleTotalRow);
        $sheet->getStyle("I{$rowNum}:K{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("L{$rowNum}")->getNumberFormat()->setFormatCode('0%');
        $sheet->getStyle("I{$rowNum}:K{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("L{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // =========================
        // Filename & Output
        // =========================
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $filePeriode = date('Ymd', strtotime($tgl_dari)) . '_' . date('Ymd', strtotime($tgl_sampai));
        } elseif (!empty($tgl_dari)) {
            $filePeriode = 'mulai_' . date('Ymd', strtotime($tgl_dari));
        } elseif (!empty($tgl_sampai)) {
            $filePeriode = 'sd_' . date('Ymd', strtotime($tgl_sampai));
        } else {
            $filePeriode = 'all';
        }

        $filename = "Report_Penjualan_vs_HPP_PerDelivery_{$filePeriode}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}
