<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_warehouse_mutasi extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Warehouse_Mutasi.View';
    protected $addPermission    = 'Report_Warehouse_Mutasi.Add';
    protected $managePermission = 'Report_Warehouse_Mutasi.Manage';
    protected $deletePermission = 'Report_Warehouse_Mutasi.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Report_warehouse_mutasi/report_warehouse_mutasi_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    /**
     * Report Mutasi Warehouse Stock (ledger perpindahan antar tahap).
     * Menampilkan jejak perpindahan barang: CREATE -> CONFIRM -> INVOICE.
     * Filter: periode tgl_event + event (opsional).
     */
    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');
        $event      = $this->input->get('event');

        $rows = $this->report_warehouse_mutasi_model->get_report(
            $start_date,
            $end_date,
            $event
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'event'      => $event,
        ];

        history("View report mutasi warehouse stock");
        $this->template->title('Report Mutasi Warehouse Stock');
        $this->template->page_icon('fa fa-exchange');
        $this->template->render('report_warehouse_mutasi', $data);
    }

    /**
     * Download Excel report Mutasi Warehouse Stock.
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
        $event      = $this->input->get('event');

        $rows = $this->report_warehouse_mutasi_model->get_report($start_date, $end_date, $event);

        $this->load->library('PHPExcel');
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Mutasi Warehouse');

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

        // Judul & periode (A..M = 13 kolom)
        $sheet->setCellValue('A1', 'Report Mutasi Warehouse Stock');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1:M1')->applyFromArray($styleTitle);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $periodeText = 'Periode Tgl Event: ';
        $periodeText .= !empty($start_date) ? date('d M Y', strtotime($start_date)) : 'Awal';
        $periodeText .= ' s/d ';
        $periodeText .= !empty($end_date) ? date('d M Y', strtotime($end_date)) : 'Akhir';
        if (!empty($event)) $periodeText .= ' | Event: ' . $event;
        $sheet->setCellValue('A2', $periodeText);
        $sheet->mergeCells('A2:M2');
        $sheet->getStyle('A2:M2')->applyFromArray($styleSubTitle);

        // Header tabel
        $rowHeader = 4;
        $headers = [
            'A' => 'No',
            'B' => 'Tgl Event',
            'C' => 'Event',
            'D' => 'Dari',
            'E' => 'Ke',
            'F' => 'No Surat Jalan',
            'G' => 'Customer',
            'H' => 'Product ID',
            'I' => 'Product Name',
            'J' => 'Qty',
            'K' => 'Cost Book (IDR)',
            'L' => 'Total Nilai (IDR)',
            'M' => 'Ref',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
        }
        $sheet->getStyle("A{$rowHeader}:M{$rowHeader}")->applyFromArray($tableHeader);
        $sheet->getRowDimension($rowHeader)->setRowHeight(20);

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(35);
        $sheet->getColumnDimension('H')->setWidth(14);
        $sheet->getColumnDimension('I')->setWidth(40);
        $sheet->getColumnDimension('J')->setWidth(12);
        $sheet->getColumnDimension('K')->setWidth(16);
        $sheet->getColumnDimension('L')->setWidth(18);
        $sheet->getColumnDimension('M')->setWidth(20);
        $sheet->freezePane('A5');

        $tahap_label = function ($v) {
            $v = trim((string) $v);
            return $v === '' ? '-' : $v;
        };

        $rowNum = 5;
        $no = 1;
        $sum_qty = 0;
        $sum_total = 0;
        foreach ($rows as $r) {
            $qty   = (float) $r->qty;
            $cost  = (float) $r->costbook;
            $total = (float) $r->total_harga;

            $sheet->setCellValueExplicit("A{$rowNum}", (string)$no++, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$rowNum}", (!empty($r->tgl_event) ? date('d/M/Y H:i', strtotime($r->tgl_event)) : '-'), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowNum}", (string)$r->event, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", $tahap_label($r->dari_tahap), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$rowNum}", $tahap_label($r->ke_tahap), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$rowNum}", (string)$r->no_surat_jalan, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$rowNum}", strtoupper((string)$r->name_customer), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$rowNum}", (string)$r->id_product, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("I{$rowNum}", strtoupper((string)$r->product), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("J{$rowNum}", $qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("K{$rowNum}", $cost, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("L{$rowNum}", $total, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("M{$rowNum}", (string)$r->ref_no, PHPExcel_Cell_DataType::TYPE_STRING);

            $sheet->getStyle("A{$rowNum}:M{$rowNum}")->applyFromArray($tableBody);
            $sheet->getStyle("A{$rowNum}:F{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$rowNum}:I{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("J{$rowNum}:L{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("K{$rowNum}:L{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');

            $sum_qty   += $qty;
            $sum_total += $total;
            $rowNum++;
        }

        // Total
        $sheet->setCellValue("A{$rowNum}", 'TOTAL');
        $sheet->mergeCells("A{$rowNum}:I{$rowNum}");
        $sheet->setCellValueExplicit("J{$rowNum}", (float)$sum_qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("L{$rowNum}", (float)$sum_total, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle("A{$rowNum}:M{$rowNum}")->applyFromArray($rowTotal);
        $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("J{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("L{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("J{$rowNum}:L{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$rowNum}:M{$rowNum}")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

        history("Download excel report mutasi warehouse stock");

        $filename = 'Report_Warehouse_Mutasi_' . date('Ymd_His') . '.xls';
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
        exit;
    }
}
