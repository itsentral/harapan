<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_penjualan_hpp extends Admin_Controller
{
    // Permission
    protected $viewPermission   = 'Report_Penjualan_HPP.View';
    protected $addPermission    = 'Report_Penjualan_HPP.Add';
    protected $managePermission = 'Report_Penjualan_HPP.Manage';
    protected $deletePermission = 'Report_Penjualan_HPP.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->library(array('upload', 'Image_lib'));
        $this->load->model(array(
            'Report_penjualan_hpp/Report_penjualan_hpp_model',
        ));

        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->page_icon('fa fa-bar-chart');
        $this->template->title('Report Penjualan vs HPP');
        $this->template->render('index');
    }

    public function data_side_report()
    {
        $this->Report_penjualan_hpp_model->data_side_report();
    }

    public function export_excel_report()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $tgl_dari   = $this->input->get('tgl_dari', true);
        $tgl_sampai = $this->input->get('tgl_sampai', true);
        $search     = $this->input->get('search', true);

        // Ambil data (tanpa paging)
        $rows = $this->Report_penjualan_hpp_model->get_export_report($search, $tgl_dari, $tgl_sampai);

        $this->load->library("PHPExcel");
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Penjualan vs HPP');

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

        $styleSubTitle = [
            'font' => ['bold' => true, 'size' => 10],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
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
        // Row 1: NAMA TABEL
        // =========================
        $sheet->setCellValue('A1', 'NAMA TABEL');
        $sheet->getStyle('A1')->applyFromArray($styleSubTitle);
        $sheet->setCellValue('B1', 'tr_invoice_sales_detail');
        $sheet->getStyle('B1')->getFont()->setBold(true);

        // Row 2: PERIODE
        $sheet->setCellValue('A2', 'PERIODE');
        $sheet->getStyle('A2')->applyFromArray($styleSubTitle);

        $periodeText = '';
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $periodeText = date('d/m/Y', strtotime($tgl_dari)) . '  s/d  ' . date('d/m/Y', strtotime($tgl_sampai));
        } elseif (!empty($tgl_dari)) {
            $periodeText = 'Mulai ' . date('d/m/Y', strtotime($tgl_dari));
        } elseif (!empty($tgl_sampai)) {
            $periodeText = 'Sampai ' . date('d/m/Y', strtotime($tgl_sampai));
        } else {
            $periodeText = 'Semua';
        }
        $sheet->setCellValue('B2', $periodeText);

        // Row 3: kosong
        // =========================
        // Row 4: Header (JUDUL)
        // Row 5: Header (FIELD / KOLOM)
        // =========================
        $rowH1 = 4;
        $rowH2 = 5;

        $headers = [
            'A' => ['judul' => 'No',               'field' => ''],
            'B' => ['judul' => 'TGL INVOICE',       'field' => 'created_on'],
            'C' => ['judul' => 'NO INVOICE',         'field' => 'id_invoice'],
            'D' => ['judul' => 'NOMOR SO',           'field' => 'id_so'],
            'E' => ['judul' => 'No Penawaran',       'field' => 'id_penawaran'],
            'F' => ['judul' => 'Nomor PO',           'field' => ''],
            'G' => ['judul' => 'NOMOR SJ',           'field' => 'id_delivery'],
            'H' => ['judul' => 'ID BARANG',          'field' => 'id_produk'],
            'I' => ['judul' => 'NAMA BARANG',        'field' => 'nama_produk'],
            'J' => ['judul' => 'QTY',                'field' => 'qty'],
            'K' => ['judul' => 'COSTBOOK SO',        'field' => 'sod.harga_beli'],
            'L' => ['judul' => 'COSTBOOK INVOICE',   'field' => 'dt.harga_beli'],
            'M' => ['judul' => 'PENJUALAN + PPN',    'field' => 'subtotal'],
            'N' => ['judul' => 'PENDAPATAN',         'field' => 'subtotal / 1,11'],
            'O' => ['judul' => 'HPP',                'field' => 'harga_beli * qty'],
            'P' => ['judul' => 'PERSEN HPP',         'field' => 'hpp / pendapatan'],
            'Q' => ['judul' => 'LABA/RUGI KOTOR',    'field' => 'pendapatan - hpp'],
            'R' => ['judul' => 'PERSEN LABA/RUGI',   'field' => 'laba / pendapatan'],
        ];

        // Row judul
        $sheet->setCellValue("A{$rowH1}", 'JUDUL');
        $sheet->getStyle("A{$rowH1}")->getFont()->setBold(true);
        // Row field
        $sheet->setCellValue("A{$rowH2}", 'FIELD / KOLOM');
        $sheet->getStyle("A{$rowH2}")->getFont()->setBold(true);

        foreach ($headers as $col => $h) {
            $sheet->setCellValue("{$col}{$rowH1}", $h['judul']);
            $sheet->setCellValue("{$col}{$rowH2}", $h['field']);
        }
        $sheet->getStyle("A{$rowH1}:R{$rowH1}")->applyFromArray($tableHeader);
        $sheet->getStyle("A{$rowH2}:R{$rowH2}")->applyFromArray($tableHeader);
        $sheet->getStyle("A{$rowH2}:R{$rowH2}")->getFill()->getStartColor()->setRGB('F2F2F2');

        // Column widths
        $colWidths = [
            'A' => 5, 'B' => 18, 'C' => 22, 'D' => 16, 'E' => 16,
            'F' => 14, 'G' => 20, 'H' => 16, 'I' => 35, 'J' => 8,
            'K' => 16, 'L' => 18, 'M' => 18, 'N' => 18, 'O' => 18,
            'P' => 12, 'Q' => 18, 'R' => 14,
        ];
        foreach ($colWidths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // Freeze pane
        $sheet->freezePane('A6');

        // =========================
        // DATA
        // =========================
        $rowNum = 6;
        $no = 1;

        $totalPenjualanPPN = 0;
        $totalPendapatan   = 0;
        $totalHPP          = 0;
        $totalLaba         = 0;

        foreach ($rows as $row) {
            $subtotal         = (float) $row->subtotal;
            $costbook_so      = (float) $row->costbook_so;
            $costbook_invoice = (float) $row->costbook_invoice;
            $qty              = (float) $row->qty;

            $penjualan_ppn = $subtotal;                          // PENJUALAN + PPN
            $pendapatan    = round($subtotal / 1.11, 2);         // PENDAPATAN (DPP)
            // Fallback: jika costbook invoice kosong/0, pakai costbook SO (data lama)
            $harga_hpp     = ($costbook_invoice > 0) ? $costbook_invoice : $costbook_so;
            $hpp           = $harga_hpp * $qty;                   // HPP
            $laba          = $pendapatan - $hpp;                  // LABA/RUGI KOTOR
            $persen_hpp    = $pendapatan > 0 ? ($hpp / $pendapatan) : 0;
            $persen_laba   = $pendapatan > 0 ? ($laba / $pendapatan) : 0;

            $totalPenjualanPPN += $penjualan_ppn;
            $totalPendapatan   += $pendapatan;
            $totalHPP          += $hpp;
            $totalLaba         += $laba;

            $sheet->setCellValueExplicit("A{$rowNum}", $no++, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("B{$rowNum}", (!empty($row->created_on) ? date('d/m/Y H:i', strtotime($row->created_on)) : ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowNum}", strtoupper($row->id_invoice), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", strtoupper($row->id_so ?? ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$rowNum}", strtoupper($row->id_penawaran ?? ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$rowNum}", '', PHPExcel_Cell_DataType::TYPE_STRING); // Nomor PO belum ada di database
            $sheet->setCellValueExplicit("G{$rowNum}", strtoupper($row->id_delivery ?? ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$rowNum}", strtoupper($row->id_produk ?? ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("I{$rowNum}", $row->nm_produk ?? '', PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("J{$rowNum}", $qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("K{$rowNum}", $costbook_so, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("L{$rowNum}", $costbook_invoice, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("M{$rowNum}", $penjualan_ppn, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("N{$rowNum}", $pendapatan, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("O{$rowNum}", $hpp, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("P{$rowNum}", $persen_hpp, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("Q{$rowNum}", $laba, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("R{$rowNum}", $persen_laba, PHPExcel_Cell_DataType::TYPE_NUMERIC);

            // Styling
            $sheet->getStyle("A{$rowNum}:R{$rowNum}")->applyFromArray($tableBody);

            // Number formats
            $sheet->getStyle("J{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("K{$rowNum}:O{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("P{$rowNum}")->getNumberFormat()->setFormatCode('0%');
            $sheet->getStyle("Q{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("R{$rowNum}")->getNumberFormat()->setFormatCode('0%');

            // Alignment
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowNum}:C{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$rowNum}:G{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("J{$rowNum}:O{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("P{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("Q{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("R{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $rowNum++;
        }

        // =========================
        // TOTAL ROW
        // =========================
        $totalPersenHpp  = $totalPendapatan > 0 ? ($totalHPP / $totalPendapatan) : 0;
        $totalPersenLaba = $totalPendapatan > 0 ? ($totalLaba / $totalPendapatan) : 0;

        $sheet->setCellValue("A{$rowNum}", '');
        $sheet->mergeCells("A{$rowNum}:L{$rowNum}");
        $sheet->setCellValue("A{$rowNum}", 'TOTAL');
        $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValueExplicit("M{$rowNum}", $totalPenjualanPPN, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("N{$rowNum}", $totalPendapatan, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("O{$rowNum}", $totalHPP, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("P{$rowNum}", $totalPersenHpp, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("Q{$rowNum}", $totalLaba, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("R{$rowNum}", $totalPersenLaba, PHPExcel_Cell_DataType::TYPE_NUMERIC);

        $sheet->getStyle("A{$rowNum}:R{$rowNum}")->applyFromArray($styleTotalRow);
        $sheet->getStyle("M{$rowNum}:O{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("P{$rowNum}")->getNumberFormat()->setFormatCode('0%');
        $sheet->getStyle("Q{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("R{$rowNum}")->getNumberFormat()->setFormatCode('0%');
        $sheet->getStyle("M{$rowNum}:O{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("P{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("Q{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("R{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

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

        $filename = "Report_Penjualan_vs_HPP_{$filePeriode}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}
