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

        $headers = [
            'A' => ['judul' => 'No',                 'field' => ''],
            'B' => ['judul' => 'NOMOR SO',           'field' => 'id_so'],
            'C' => ['judul' => 'Nama Customer',      'field' => 'nm_customer'],
            'D' => ['judul' => 'Nama Sales',         'field' => 'created_by'],
            'E' => ['judul' => 'TGL INVOICE',        'field' => 'created_on'],
            'F' => ['judul' => 'NO INVOICE',         'field' => 'id_invoice'],
            'G' => ['judul' => 'ID BARANG',          'field' => 'id_produk'],
            'H' => ['judul' => 'NAMA BARANG',        'field' => 'nama_produk'],
            'I' => ['judul' => 'QTY',                'field' => 'qty'],
            'J' => ['judul' => 'Hrga jual satuan',   'field' => 'per unit'],
            'K' => ['judul' => 'COSTBOOK HPP',       'field' => 'costbook per unit'],
            'L' => ['judul' => 'HARGA JUAL',         'field' => 'sesuai invoice'],
            'M' => ['judul' => 'HPP',                'field' => 'harga_beli * qty'],
            'N' => ['judul' => 'LABA/RUGI KOTOR',    'field' => 'pendapatan - hpp'],
            'O' => ['judul' => 'PERSEN LABA/RUGI',   'field' => 'laba / pendapatan'],
        ];

        // Row judul
        foreach ($headers as $col => $h) {
            $sheet->setCellValue("{$col}{$rowH1}", $h['judul']);
        }
        $sheet->getStyle("A{$rowH1}:O{$rowH1}")->applyFromArray($tableHeader);

        // Column widths
        $colWidths = [
            'A' => 5, 'B' => 16, 'C' => 28, 'D' => 18, 'E' => 18,
            'F' => 22, 'G' => 16, 'H' => 35, 'I' => 8, 'J' => 16,
            'K' => 16, 'L' => 18, 'M' => 18, 'N' => 18, 'O' => 14,
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

        $totalPenjualanPPN = 0;
        $totalPendapatan   = 0;
        $totalHPP          = 0;
        $totalLaba         = 0;

        foreach ($rows as $row) {
            $subtotal         = (float) $row->subtotal;
            $costbook_invoice = (float) $row->costbook_invoice;
            $qty              = (float) $row->qty;

            $harga_hpp         = $costbook_invoice;                  // Costbook HPP dari dt.harga_beli
            $harga_jual        = round($subtotal / 1.11, 2);         // Harga jual sesuai invoice (tanpa PPN)
            $harga_jual_satuan = $qty > 0 ? ($harga_jual / $qty) : 0; // Harga jual per unit (tanpa PPN)
            $costbook_hpp      = $harga_hpp;                          // Costbook HPP per unit
            $pendapatan        = $harga_jual;                        // Pendapatan (DPP)
            $hpp               = $harga_hpp * $qty;                   // HPP
            $laba              = $pendapatan - $hpp;                  // LABA/RUGI KOTOR
            $persen_laba       = $pendapatan > 0 ? ($laba / $pendapatan) : 0;

            $totalPenjualanPPN += $harga_jual;
            $totalPendapatan   += $pendapatan;
            $totalHPP          += $hpp;
            $totalLaba         += $laba;

            $sheet->setCellValueExplicit("A{$rowNum}", $no++, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("B{$rowNum}", strtoupper($row->id_so ?? ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowNum}", $row->nm_customer ?? '', PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", $row->created_by ?? '', PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$rowNum}", (!empty($row->created_on) ? date('d/m/Y H:i', strtotime($row->created_on)) : ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$rowNum}", strtoupper($row->id_invoice), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$rowNum}", strtoupper($row->id_produk ?? ''), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$rowNum}", $row->nm_produk ?? '', PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("I{$rowNum}", $qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("J{$rowNum}", $harga_jual_satuan, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("K{$rowNum}", $costbook_hpp, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("L{$rowNum}", $harga_jual, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("M{$rowNum}", $hpp, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("N{$rowNum}", $laba, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit("O{$rowNum}", $persen_laba, PHPExcel_Cell_DataType::TYPE_NUMERIC);

            // Styling
            $sheet->getStyle("A{$rowNum}:O{$rowNum}")->applyFromArray($tableBody);

            // Number formats
            $sheet->getStyle("I{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J{$rowNum}:N{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("O{$rowNum}")->getNumberFormat()->setFormatCode('0%');

            // Alignment
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowNum}:D{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("E{$rowNum}:G{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("H{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("I{$rowNum}:N{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("O{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $rowNum++;
        }

        // =========================
        // TOTAL ROW
        // =========================
        $totalPersenLaba = $totalPendapatan > 0 ? ($totalLaba / $totalPendapatan) : 0;

        $sheet->setCellValue("A{$rowNum}", '');
        $sheet->mergeCells("A{$rowNum}:K{$rowNum}");
        $sheet->setCellValue("A{$rowNum}", 'TOTAL');
        $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValueExplicit("L{$rowNum}", $totalPenjualanPPN, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("M{$rowNum}", $totalHPP, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("N{$rowNum}", $totalLaba, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit("O{$rowNum}", $totalPersenLaba, PHPExcel_Cell_DataType::TYPE_NUMERIC);

        $sheet->getStyle("A{$rowNum}:O{$rowNum}")->applyFromArray($styleTotalRow);
        $sheet->getStyle("L{$rowNum}:N{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("O{$rowNum}")->getNumberFormat()->setFormatCode('0%');
        $sheet->getStyle("L{$rowNum}:N{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("O{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

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
