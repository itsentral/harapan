<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_pembelian extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Pembelian.View';
    protected $addPermission    = 'Report_Pembelian.Add';
    protected $managePermission = 'Report_Pembelian.Manage';
    protected $deletePermission = 'Report_Pembelian.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->library(array('upload', 'Image_lib'));
        $this->load->model(array(
            'Report_pembelian/Report_pembelian_model',
        ));

        date_default_timezone_set('Asia/Bangkok');
    }

    // =============================
    // Report Seluruh Pembelian
    // =============================
    public function index()
    {
        $this->template->page_icon('fa fa-clipboard');
        $this->template->title('Daftar Faktur Pembelian');
        $this->template->render('index');
    }

    public function data_side_report()
    {
        $this->Report_pembelian_model->data_side_report();
    }

    // =============================
    // Export Daftar Faktur Pembelian
    // =============================
    public function export_faktur_report()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $tgl_dari   = $this->input->get('tgl_dari', true);
        $tgl_sampai = $this->input->get('tgl_sampai', true);
        $search     = $this->input->get('search', true);

        // Ambil data faktur pembelian (mirror list Daftar Faktur Pembelian)
        $rows = $this->Report_pembelian_model->get_export_report($search, $tgl_dari, $tgl_sampai);

        $this->load->library("PHPExcel");
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Daftar Faktur Pembelian');

        // STYLE
        $styleTitle = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '2C3E50']],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ]
        ];
        $tableHeader = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
            'fill' => [
                'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => 'D9EAD3']
            ]
        ];
        $tableBody = [
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
            'alignment' => ['vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER]
        ];

        // Judul & Periode
        $sheet->setCellValue('A1', 'DAFTAR FAKTUR PEMBELIAN');
        $sheet->mergeCells('A1:E1');
        $sheet->getStyle('A1:E1')->applyFromArray($styleTitle);

        $periodeText = 'Periode Faktur: ';
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $periodeText .= date('d/m/Y', strtotime($tgl_dari)) . ' s/d ' . date('d/m/Y', strtotime($tgl_sampai));
        } else {
            $periodeText .= 'Semua';
        }
        $sheet->setCellValue('A2', $periodeText);
        $sheet->mergeCells('A2:E2');

        // Header Kolom (Baris 4)
        $headers = [
            'A' => 'No',
            'B' => 'Nomor Invoice',
            'C' => 'Tanggal',
            'D' => 'Supplier',
            'E' => 'Total'
        ];
        $rowHeader = 4;
        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . $rowHeader, $header);
            $sheet->getStyle($col . $rowHeader)->applyFromArray($tableHeader);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->freezePane('A5');

        // Isi Data
        $rowNum = 5;
        $no = 1;
        $grandTotal = 0;

        foreach ($rows as $row) {
            $total = (float) ($row->total_invoice ?? 0);
            $grandTotal += $total;

            $sheet->setCellValueExplicit("A{$rowNum}", (string)$no++, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$rowNum}", strtoupper((string)($row->id ?? '-')), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("C{$rowNum}", (string)(!empty($row->invoice_date) ? date('d/M/Y', strtotime($row->invoice_date)) : '-'), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", strtoupper((string)($row->nm_supplier ?? '-')), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue("E{$rowNum}", $total);
            $sheet->getStyle("E{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');

            $sheet->getStyle("A{$rowNum}:E{$rowNum}")->applyFromArray($tableBody);
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

            $rowNum++;
        }

        // Baris Total
        $sheet->setCellValue("D{$rowNum}", 'TOTAL');
        $sheet->setCellValue("E{$rowNum}", $grandTotal);
        $sheet->getStyle("E{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("A{$rowNum}:E{$rowNum}")->applyFromArray($tableHeader);
        $sheet->getStyle("D{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        // Filename & Output
        $filePeriode = (!empty($tgl_dari)) ? date('Ymd', strtotime($tgl_dari)) : 'all';
        $filename = "Daftar_Faktur_Pembelian_{$filePeriode}.xls";

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        if (ob_get_level() > 0) ob_end_clean();

        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');

        $objWriter->save("php://output");
        exit;
    }

    // =============================
    // Histori Pembelian PR, PO, Inc, Receiv Inv, Payment
    // =============================

    public function history_pembelian()
    {
        $this->template->page_icon('fa fa-clipboard');
        $this->template->title('Histori Proses Pembelian');
        $this->template->render('history_pembelian');
    }

    public function data_side_history_pembelian()
    {
        $this->Report_pembelian_model->get_json_history_pembelian();
    }

    public function export_excel_report()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $tgl_dari   = $this->input->get('tgl_dari', true);
        $tgl_sampai = $this->input->get('tgl_sampai', true);
        $search     = $this->input->get('search', true);

        // Ambil data menggunakan model pengadaan/pembelian
        $rows = $this->Report_pembelian_model->get_export_history($search, $tgl_dari, $tgl_sampai);

        $this->load->library("PHPExcel");
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Histori Pembelian');

        // =========================
        // STYLE
        // =========================
        $styleTitle = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '2C3E50']],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ]
        ];

        $tableHeader = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            ],
            'fill' => [
                'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => 'D9EAD3'] // Hijau muda (khas purchasing)
            ]
        ];

        $tableBody = [
            'borders' => [
                'allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            ],
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ]
        ];

        // =========================
        // Judul & Periode
        // =========================
        $sheet->setCellValue('A1', 'LAPORAN HISTORI PROSES PEMBELIAN');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1:H1')->applyFromArray($styleTitle);

        $periodeText = 'Periode PO: ';
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $periodeText .= date('d/m/Y', strtotime($tgl_dari)) . ' s/d ' . date('d/m/Y', strtotime($tgl_sampai));
        } else {
            $periodeText .= 'Semua';
        }
        $sheet->setCellValue('A2', $periodeText);
        $sheet->mergeCells('A2:H2');

        // =========================
        // Header Kolom (Baris 4)
        // =========================
        $headers = [
            'A' => 'No',
            'B' => 'Permintaan Barang (PR)',
            'C' => 'Pesanan Pembelian (PO)',
            'D' => 'Penerimaan Barang',
            'E' => 'Faktur Pembelian',
            'F' => 'Pembayaran Pembelian',
            'G' => 'Term (Hari)',
            'H' => 'Jatuh Tempo'
        ];

        $rowHeader = 4;
        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . $rowHeader, $header);
            $sheet->getStyle($col . $rowHeader)->applyFromArray($tableHeader);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze pane
        $sheet->freezePane('A5');

        // =========================
        // Isi Data
        // =========================
        $rowNum = 5;
        $no = 1;

        foreach ($rows as $row) {
            // Kita gabungkan Nomor PR dengan Tipenya agar informatif
            // Contoh: PR2026001 (Produksi)
            $pr_display = $row->permintaan_barang;
            if (!empty($row->tipe_pr)) {
                $pr_display .= " (" . $row->tipe_pr . ")";
            }

            $sheet->setCellValueExplicit("A{$rowNum}", (string)$no++, PHPExcel_Cell_DataType::TYPE_STRING);

            // Menggunakan data PR yang sudah ada keterangan tipenya
            $sheet->setCellValueExplicit("B{$rowNum}", (string)($pr_display ?? '-'), PHPExcel_Cell_DataType::TYPE_STRING);

            $sheet->setCellValueExplicit("C{$rowNum}", (string)($row->pesanan_pembelian ?? '-'), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", (string)($row->penerimaan_barang ?? '-'), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$rowNum}", (string)($row->faktur_pembelian ?? '-'), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("F{$rowNum}", (string)($row->pembayaran_pembelian ?? '-'), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("G{$rowNum}", (string)(!empty($row->term) ? $row->term . ' Hari' : '-'), PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("H{$rowNum}", (string)(!empty($row->jatuh_tempo) ? date('d-m-Y', strtotime($row->jatuh_tempo)) : '-'), PHPExcel_Cell_DataType::TYPE_STRING);

            // Apply style body
            $sheet->getStyle("A{$rowNum}:H{$rowNum}")->applyFromArray($tableBody);

            // Alignment center untuk nomor dan kolom dokumen
            $sheet->getStyle("A{$rowNum}:H{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

            $rowNum++;
        }

        // =========================
        // Filename & Output
        // =========================
        $filePeriode = (!empty($tgl_dari)) ? date('Ymd', strtotime($tgl_dari)) : 'all';
        $filename = "Histori_Pembelian_{$filePeriode}.xls";

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        if (ob_get_level() > 0) ob_end_clean();

        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');

        $objWriter->save("php://output");
        exit;
    }

    // =============================
    // Pembelian per produk
    // =============================
    public function pembelian_per_barang()
    {
        $this->template->page_icon('fa fa-clipboard');
        $this->template->title('Pembelian per Barang');
        $this->template->render('pembelian_product');
    }

    public function data_side_pembelian_per_barang()
    {
        $this->Report_pembelian_model->get_json_pembelian_per_barang();
    }

    public function export_pembelian_per_barang()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $tgl_dari   = $this->input->get('tgl_dari', true);
        $tgl_sampai = $this->input->get('tgl_sampai', true);
        $search     = $this->input->get('search', true);

        // Ambil data menggunakan model
        $rows = $this->Report_pembelian_model->get_export_pembelian_per_barang($search, $tgl_dari, $tgl_sampai);

        $this->load->library("PHPExcel");
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Pembelian per Barang');

        // =========================
        // STYLE (Standar Laporan Purchasing)
        // =========================
        $styleTitle = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '2C3E50']],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ]
        ];

        $tableHeader = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            ],
            'fill' => [
                'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => 'D9EAD3'] // Hijau muda standar
            ]
        ];

        $tableBody = [
            'borders' => [
                'allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            ],
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ]
        ];

        // =========================
        // Judul & Periode
        // =========================
        $sheet->setCellValue('A1', 'LAPORAN PEMBELIAN PER BARANG');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1:D1')->applyFromArray($styleTitle);

        $periodeText = 'Periode: ';
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $periodeText .= date('d/m/Y', strtotime($tgl_dari)) . ' s/d ' . date('d/m/Y', strtotime($tgl_sampai));
        } else {
            $periodeText .= 'Semua Periode';
        }
        $sheet->setCellValue('A2', $periodeText);
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2:D2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // =========================
        // Header Kolom (Baris 4)
        // =========================
        $headers = [
            'A' => 'No',
            'B' => 'Nama Barang',
            'C' => 'Kts (Unit#1)',
            'D' => 'Total Pembelian'
        ];

        $rowHeader = 4;
        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . $rowHeader, $header);
            $sheet->getStyle($col . $rowHeader)->applyFromArray($tableHeader);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze pane
        $sheet->freezePane('A5');

        // =========================
        // Isi Data
        // =========================
        $rowNum = 5;
        $no = 1;

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $nama_barang = isset($row->nama_barang) ? (string)$row->nama_barang : '-';
                $qty         = isset($row->total_qty) ? (float)$row->total_qty : 0;
                $nominal     = isset($row->total_nominal) ? (float)$row->total_nominal : 0;

                // Set Values
                $sheet->setCellValueExplicit("A{$rowNum}", (string)$no++, PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("B{$rowNum}", $nama_barang, PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$rowNum}", $qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit("D{$rowNum}", $nominal, PHPExcel_Cell_DataType::TYPE_NUMERIC);

                // Format Angka
                $sheet->getStyle("C{$rowNum}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("D{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');

                // Apply style body
                $sheet->getStyle("A{$rowNum}:D{$rowNum}")->applyFromArray($tableBody);
                $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("C{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $rowNum++;
            }
        } else {
            $sheet->setCellValue("A{$rowNum}", 'Data tidak ditemukan');
            $sheet->mergeCells("A{$rowNum}:D{$rowNum}");
            $sheet->getStyle("A{$rowNum}:D{$rowNum}")->applyFromArray($tableBody);
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }

        // =========================
        // Filename & Output
        // =========================
        $filePeriode = (!empty($tgl_dari)) ? date('Ymd', strtotime($tgl_dari)) : 'all';
        $filename = "Laporan_Pembelian_per_Barang_{$filePeriode}.xls";

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        // Bersihkan buffer
        if (ob_get_level() > 0) ob_end_clean();

        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');

        $objWriter->save("php://output");
        exit;
    }

    // =============================
    // Pembelian per pemasok/vendor
    // =============================
    public function pembelian_per_vendor()
    {
        $this->template->page_icon('fa fa-clipboard');
        $this->template->title('Pembelian per Pemasok');
        $this->template->render('pembelian_vendor');
    }

    public function data_side_pembelian_per_vendor()
    {
        $this->Report_pembelian_model->get_json_pembelian_per_vendor();
    }

    public function export_pembelian_per_vendor()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $tgl_dari   = $this->input->get('tgl_dari', true);
        $tgl_sampai = $this->input->get('tgl_sampai', true);
        $search     = $this->input->get('search', true);

        // Ambil data menggunakan model
        $rows = $this->Report_pembelian_model->get_export_pembelian_per_vendor($search, $tgl_dari, $tgl_sampai);

        $this->load->library("PHPExcel");
        $objPHPExcel = new PHPExcel();
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Pembelian per Pemasok');

        // =========================
        // STYLE (Mengikuti format Histori Pembelian)
        // =========================
        $styleTitle = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '2C3E50']],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ]
        ];

        $tableHeader = [
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            ],
            'fill' => [
                'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                'color' => ['rgb' => 'D9EAD3'] // Hijau muda khas purchasing
            ]
        ];

        $tableBody = [
            'borders' => [
                'allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]
            ],
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER
            ]
        ];

        // =========================
        // Judul & Periode
        // =========================
        $sheet->setCellValue('A1', 'LAPORAN PEMBELIAN PER PEMASOK');
        $sheet->mergeCells('A1:C1');
        $sheet->getStyle('A1:C1')->applyFromArray($styleTitle);

        $periodeText = 'Periode: ';
        if (!empty($tgl_dari) && !empty($tgl_sampai)) {
            $periodeText .= date('d/m/Y', strtotime($tgl_dari)) . ' s/d ' . date('d/m/Y', strtotime($tgl_sampai));
        } else {
            $periodeText .= 'Semua Periode';
        }
        $sheet->setCellValue('A2', $periodeText);
        $sheet->mergeCells('A2:C2');
        $sheet->getStyle('A2:C2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // =========================
        // Header Kolom (Baris 4)
        // =========================
        $headers = [
            'A' => 'No',
            'B' => 'Pemasok',
            'C' => 'Total Pembelian'
        ];

        $rowHeader = 4;
        foreach ($headers as $col => $header) {
            $sheet->setCellValue($col . $rowHeader, $header);
            $sheet->getStyle($col . $rowHeader)->applyFromArray($tableHeader);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Freeze pane agar header tetap terlihat saat scroll
        $sheet->freezePane('A5');

        // =========================
        // Isi Data
        // =========================
        $rowNum = 5;
        $no = 1;

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $pemasok = isset($row->pemasok) ? (string)$row->pemasok : '-';
                $nominal = isset($row->total_nominal) ? (float)$row->total_nominal : 0;

                // Kolom A: No
                $sheet->setCellValueExplicit("A{$rowNum}", (string)$no++, PHPExcel_Cell_DataType::TYPE_STRING);

                // Kolom B: Pemasok
                $sheet->setCellValueExplicit("B{$rowNum}", $pemasok, PHPExcel_Cell_DataType::TYPE_STRING);

                // Kolom C: Nominal (Numeric dengan format ribuan)
                $sheet->setCellValueExplicit("C{$rowNum}", $nominal, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle("C{$rowNum}")->getNumberFormat()->setFormatCode('#,##0.00');

                // Apply style body dan alignment
                $sheet->getStyle("A{$rowNum}:C{$rowNum}")->applyFromArray($tableBody);
                $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

                $rowNum++;
            }
        } else {
            // Jika data kosong
            $sheet->setCellValue("A{$rowNum}", 'Data tidak ditemukan');
            $sheet->mergeCells("A{$rowNum}:C{$rowNum}");
            $sheet->getStyle("A{$rowNum}:C{$rowNum}")->applyFromArray($tableBody);
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }

        // =========================
        // Filename & Output
        // =========================
        $filePeriode = (!empty($tgl_dari)) ? date('Ymd', strtotime($tgl_dari)) : 'all';
        $filename = "Laporan_Pembelian_per_Vendor_{$filePeriode}.xls";

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');

        // Bersihkan buffer
        if (ob_get_level() > 0) ob_end_clean();

        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');

        $objWriter->save("php://output");
        exit;
    }
}
