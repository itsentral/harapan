<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_warehouse_invoiced extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Warehouse_Invoiced.View';
    protected $addPermission    = 'Report_Warehouse_Invoiced.Add';
    protected $managePermission = 'Report_Warehouse_Invoiced.Manage';
    protected $deletePermission = 'Report_Warehouse_Invoiced.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Report_warehouse_invoiced/report_warehouse_invoiced_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    /**
     * Report Warehouse Invoiced (tampilan layar).
     * Menampilkan barang yang sudah di-invoice (tahap akhir setelah in-customer).
     * Sumber: tr_invoice_sales + detail, khusus tipe 'delivery'.
     * Filter: periode tanggal invoice.
     */
    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');

        // Data invoice sangat banyak -> jangan tarik semua saat load pertama.
        // Baru query setelah user menentukan periode (klik Filter).
        $has_filter = (!empty($start_date) || !empty($end_date));
        $rows = $has_filter
            ? $this->report_warehouse_invoiced_model->get_report($start_date, $end_date)
            : [];

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'has_filter' => $has_filter,
        ];

        history("View report warehouse invoiced");
        $this->template->title('Report Warehouse Invoiced');
        $this->template->page_icon('fa fa-file-text-o');
        $this->template->render('report_warehouse_invoiced', $data);
    }

    /**
     * Download Excel report Warehouse Invoiced.
     * Query & kolom identik dengan tampilan layar, digenerate PHPExcel (.xls biner).
     */
    public function download_excel()
    {
        $this->auth->restrict($this->viewPermission);

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');

        $rows = $this->report_warehouse_invoiced_model->get_report($start_date, $end_date);

        $this->load->library('PHPExcel');
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Warehouse Invoiced');

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

        // Judul & periode (A..J = 10 kolom)
        $sheet->setCellValue('A1', 'Report Warehouse Invoiced');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1:J1')->applyFromArray($styleTitle);
        $sheet->getRowDimension(1)->setRowHeight(24);

        $periodeText = 'Periode Tgl Invoice: ';
        $periodeText .= !empty($start_date) ? date('d M Y', strtotime($start_date)) : 'Awal';
        $periodeText .= ' s/d ';
        $periodeText .= !empty($end_date) ? date('d M Y', strtotime($end_date)) : 'Akhir';
        $sheet->setCellValue('A2', $periodeText);
        $sheet->mergeCells('A2:J2');
        $sheet->getStyle('A2:J2')->applyFromArray($styleSubTitle);

        // Header tabel
        $rowHeader = 4;
        $headers = [
            'A' => 'No',
            'B' => 'Tgl Invoice',
            'C' => 'No Invoice',
            'D' => 'No Surat Jalan',
            'E' => 'Customer',
            'F' => 'Product ID',
            'G' => 'Product Name',
            'H' => 'Qty',
            'I' => 'Cost Book (IDR)',
            'J' => 'Total Nilai (IDR)',
        ];
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
        }
        $sheet->getStyle("A{$rowHeader}:J{$rowHeader}")->applyFromArray($tableHeader);
        $sheet->getRowDimension($rowHeader)->setRowHeight(20);

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(20);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(35);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(40);
        $sheet->getColumnDimension('H')->setWidth(12);
        $sheet->getColumnDimension('I')->setWidth(16);
        $sheet->getColumnDimension('J')->setWidth(18);
        $sheet->freezePane('A5');

        $rowNum = 5;
        $no = 1;
        $sum_qty = 0;
        $sum_total = 0;
        foreach ($rows as $r) {
            $qty    = (float) $r->qty;
            $cost   = (float) $r->costbook;
            $total  = (float) $r->total_harga;

            $sheet->setCellValueExplicit("A{$rowNum}", (string)$no++, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$rowNum}", (!empty($r->tgl_invoice) ? date('d/M/Y', strtotime($r->tgl_invoice)) : '-'), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowNum}", (string)$r->id_invoice, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", (string)$r->no_surat_jalan, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$rowNum}", strtoupper((string)$r->nm_customer), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$rowNum}", (string)$r->id_product, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$rowNum}", strtoupper((string)$r->product), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$rowNum}", $qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("I{$rowNum}", $cost, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("J{$rowNum}", $total, PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $sheet->getStyle("A{$rowNum}:J{$rowNum}")->applyFromArray($tableBody);
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowNum}:D{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowNum}:G{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("H{$rowNum}:J{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("H{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle("I{$rowNum}:J{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');

            $sum_qty   += $qty;
            $sum_total += $total;
            $rowNum++;
        }

        // Total
        $sheet->setCellValue("A{$rowNum}", 'TOTAL');
        $sheet->mergeCells("A{$rowNum}:G{$rowNum}");
        $sheet->setCellValueExplicit("H{$rowNum}", (float)$sum_qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("J{$rowNum}", (float)$sum_total, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle("A{$rowNum}:J{$rowNum}")->applyFromArray($rowTotal);
        $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("J{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle("H{$rowNum}:J{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("A{$rowNum}:J{$rowNum}")->getBorders()->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

        history("Download excel report warehouse invoiced");

        $filename = 'Report_Warehouse_Invoiced_' . date('Ymd_His') . '.xls';
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
        exit;
    }
}
