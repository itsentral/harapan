<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_warehouse_intransit extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Warehouse_Intransit.View';
    protected $addPermission    = 'Report_Warehouse_Intransit.Add';
    protected $managePermission = 'Report_Warehouse_Intransit.Manage';
    protected $deletePermission = 'Report_Warehouse_Intransit.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Report_warehouse_intransit/report_warehouse_intransit_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    /**
     * Report Warehouse Stock In-Transit (tampilan layar).
     * Menampilkan barang yang sudah dibuat Surat Jalan tetapi belum di-confirm
     * (masih berada di warehouse_stock_intransit). Filter: periode tgl_masuk.
     */
    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');

        $rows = $this->report_warehouse_intransit_model->get_report(
            $start_date,
            $end_date
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ];

        history("View report warehouse in-transit");
        $this->template->title('Report Warehouse In-Transit');
        $this->template->page_icon('fa fa-truck');
        $this->template->render('report_warehouse_intransit', $data);
    }

    /**
     * Download Excel report Warehouse In-Transit.
     * Memakai query & kolom yang identik dengan tampilan layar (index),
     * digenerate dengan PHPExcel (file .xls biner) — gaya sama dengan report_penjualan.
     */
    public function download_excel()
    {
        $this->auth->restrict($this->viewPermission);

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');

        $rows = $this->report_warehouse_intransit_model->get_report($start_date, $end_date);

        $this->load->library('PHPExcel');
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Warehouse In-Transit');

        $styleTitle = [
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'B30000']],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER],
        ];
        $styleSubTitle = [
            'font' => ['bold' => false, 'size' => 11],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER],
        ];
        $tableHeader = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER, 'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER],
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
            'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'D9EDF7']],
        ];
        $tableBody = [
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
            'alignment' => ['vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER],
        ];
        $rowTotal = [
            'font' => ['bold' => true],
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
            'alignment' => ['vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER],
            'fill' => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'D9EDF7']],
        ];

        // Judul & periode (A..I = 9 kolom)
        $sheet->setCellValue('A1', 'Report Warehouse In-Transit');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1:I1')->applyFromArray($styleTitle);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $periodeText = 'Periode Tgl Surat Jalan: ';
        $periodeText .= !empty($start_date) ? date('d M Y', strtotime($start_date)) : 'Awal';
        $periodeText .= ' s/d ';
        $periodeText .= !empty($end_date) ? date('d M Y', strtotime($end_date)) : 'Akhir';
        $sheet->setCellValue('A2', $periodeText);
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2:I2')->applyFromArray($styleSubTitle);

        // Header tabel
        $rowHeader = 4;
        $headers = [
            'A' => 'No',
            'B' => 'Tgl Surat Jalan',
            'C' => 'No Surat Jalan',
            'D' => 'Customer',
            'E' => 'Product ID',
            'F' => 'Product Name',
            'G' => 'Qty',
            'H' => 'Cost Book (IDR)',
            'I' => 'Total Nilai (IDR)',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
        }
        $sheet->getStyle("A{$rowHeader}:I{$rowHeader}")->applyFromArray($tableHeader);
        $sheet->getRowDimension($rowHeader)->setRowHeight(20);

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(16);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(35);
        $sheet->getColumnDimension('E')->setWidth(14);
        $sheet->getColumnDimension('F')->setWidth(40);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(18);
        $sheet->freezePane('A5');

        $rowNum = 5;
        $no = 1;
        $sum_qty = 0;
        $sum_total = 0;
        foreach ($rows as $r) {
            $qty   = (float) $r->qty;
            $cost  = (float) $r->costbook;
            $total = (float) $r->total_harga;

            $sheet->setCellValueExplicit("A{$rowNum}", (string)$no++, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$rowNum}", (!empty($r->tgl_surat_jalan) ? date('d/M/Y', strtotime($r->tgl_surat_jalan)) : '-'), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowNum}", (string)$r->no_surat_jalan, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", strtoupper((string)$r->name_customer), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$rowNum}", (string)$r->id_product, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$rowNum}", strtoupper((string)$r->product), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$rowNum}", $qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("H{$rowNum}", $cost, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("I{$rowNum}", $total, PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $sheet->getStyle("A{$rowNum}:I{$rowNum}")->applyFromArray($tableBody);
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowNum}:C{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowNum}:F{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("G{$rowNum}:I{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("G{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("H{$rowNum}:I{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');

            $sum_qty   += $qty;
            $sum_total += $total;
            $rowNum++;
        }

        // Total
        $sheet->setCellValue("A{$rowNum}", 'TOTAL');
        $sheet->mergeCells("A{$rowNum}:F{$rowNum}");
        $sheet->setCellValueExplicit("G{$rowNum}", (float)$sum_qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("I{$rowNum}", (float)$sum_total, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle("A{$rowNum}:I{$rowNum}")->applyFromArray($rowTotal);
        $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("G{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("I{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("G{$rowNum}:I{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$rowNum}:I{$rowNum}")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

        history("Download excel report warehouse in-transit");

        $filename = 'Report_Warehouse_Intransit_' . date('Ymd_His') . '.xls';
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
        exit;
    }
}
