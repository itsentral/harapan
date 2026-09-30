<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_piutang_kasir extends Admin_Controller
{
    protected $viewPermission   = 'Report_Piutang_Kasir.View';
    protected $addPermission    = 'Report_Piutang_Kasir.Add';
    protected $managePermission = 'Report_Piutang_Kasir.Manage';
    protected $deletePermission = 'Report_Piutang_Kasir.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Report_piutang_kasir/Report_piutang_kasir_model');
        date_default_timezone_set('Asia/Bangkok');
    }

    // ─────────────────────────────────────────────────────────────
    // Halaman Utama: Report Piutang Kasir
    // ─────────────────────────────────────────────────────────────
    public function index()
    {
        $bulan = $this->input->get('bulan', true); // format: YYYY-MM

        $data = ['bulan' => $bulan];

        if (!empty($bulan)) {
            $rows = $this->Report_piutang_kasir_model->get_rows($bulan);

            // Total setoran sales = SUM semua baris kasir di bulan tsb
            $total_setoran_sales = array_sum(array_column($rows, 'setoran_sales'));

            // Total setoran bank = SUM unik per id_setor_bank
            // (satu id_setor_bank bisa muncul di banyak baris kasir,
            //  jadi kita deduplikasi dulu)
            $seen_bank = [];
            $total_setoran_bank = 0;
            foreach ($rows as $r) {
                if (!empty($r['id_setor_bank']) && !isset($seen_bank[$r['id_setor_bank']])) {
                    $seen_bank[$r['id_setor_bank']] = true;
                    $total_setoran_bank += $r['total_bank'];
                }
            }

            $data['rows']                = $rows;
            $data['total_setoran_sales'] = $total_setoran_sales;
            $data['total_setoran_bank']  = $total_setoran_bank;
            $data['piutang_kasir']       = $total_setoran_sales - $total_setoran_bank;
        } else {
            $data['rows']                = [];
            $data['total_setoran_sales'] = 0;
            $data['total_setoran_bank']  = 0;
            $data['piutang_kasir']       = 0;
        }

        $this->template->page_icon('fa fa-money');
        $this->template->title('Report Piutang Kasir');
        $this->template->render('index', $data);
    }

    // ─────────────────────────────────────────────────────────────
    // Export Excel
    // ─────────────────────────────────────────────────────────────
    public function export_excel()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $bulan = $this->input->get('bulan', true);
        if (empty($bulan)) redirect('report_piutang_kasir');

        $rows = $this->Report_piutang_kasir_model->get_rows($bulan);

        $total_setoran_sales = array_sum(array_column($rows, 'setoran_sales'));
        $seen_bank = [];
        $total_setoran_bank = 0;
        foreach ($rows as $r) {
            if (!empty($r['id_setor_bank']) && !isset($seen_bank[$r['id_setor_bank']])) {
                $seen_bank[$r['id_setor_bank']] = true;
                $total_setoran_bank += $r['total_bank'];
            }
        }
        $piutang_kasir = $total_setoran_sales - $total_setoran_bank;

        $bulan_parts = explode('-', $bulan);
        $nama_bulan  = $this->_nama_bulan((int)$bulan_parts[1]) . ' ' . $bulan_parts[0];

        $this->load->library('PHPExcel');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setTitle('Report Piutang Kasir ' . $nama_bulan);
        $sheet = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Piutang Kasir');

        $styleHeader = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => '2E75B6']],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];
        $styleData = [
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];
        $styleTotal = [
            'font'    => ['bold' => true],
            'fill'    => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => 'BDD7EE']],
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];
        $styleSummary = [
            'font'    => ['bold' => true],
            'fill'    => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => ['rgb' => 'D9D9D9']],
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];

        // Judul
        $sheet->setCellValue('A1', 'REPORT PIUTANG KASIR - ' . strtoupper($nama_bulan));
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Summary
        $sheet->setCellValue('A3', 'Setoran Sales');
        $sheet->setCellValue('B3', $total_setoran_sales);
        $sheet->setCellValue('A4', 'Setoran Bank');
        $sheet->setCellValue('B4', $total_setoran_bank);
        $sheet->setCellValue('A5', 'Piutang Kasir');
        $sheet->setCellValue('B5', $piutang_kasir);
        $sheet->getStyle('A3:B5')->applyFromArray($styleSummary);
        $sheet->getStyle('B3:B5')->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('B3:B5')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        // Header tabel
        $row = 7;
        $headers = ['Tanggal', 'Kode Trans Kasir', 'Setoran Sales ke Kasir', 'Sales', 'Kode Trans Bank', 'Tanggal Bank', 'Setor Kasir Penjualan'];
        $cols    = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . $row, $h);
        }
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($styleHeader);

        // Pre-process: hitung jumlah baris per grup bank & tandai baris pertama,
        // supaya kolom bank (Kode Trans Bank, Tanggal Bank, Setor Kasir) di-merge
        // dan hanya tampil sekali per grup — sama seperti tampilan (rowspan).
        $bank_rowspan = [];
        $bank_first   = [];
        foreach ($rows as $i => $r) {
            $bid = $r['id_setor_bank'] ?? null;
            if (!empty($bid)) {
                if (!isset($bank_rowspan[$bid])) {
                    $bank_rowspan[$bid] = 0;
                    $bank_first[$bid]   = $i;
                }
                $bank_rowspan[$bid]++;
            }
        }

        $row++;
        foreach ($rows as $i => $r) {
            $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($r['tgl_kasir'])));
            $sheet->setCellValue('B' . $row, $r['id_kasir']);
            $sheet->setCellValue('C' . $row, $r['setoran_sales']);
            $sheet->setCellValue('D' . $row, $r['sales']);

            $bid = $r['id_setor_bank'] ?? null;
            if (!empty($bid) && isset($bank_first[$bid]) && $bank_first[$bid] === $i) {
                // Baris pertama grup bank -> isi nilai & merge vertikal sebanyak rowspan
                $rowspan = $bank_rowspan[$bid];
                $sheet->setCellValue('E' . $row, $r['id_setor_bank']);
                $sheet->setCellValue('F' . $row, date('d/m/Y', strtotime($r['tgl_bank'])));
                $sheet->setCellValue('G' . $row, $r['total_bank']);

                if ($rowspan > 1) {
                    $rowEnd = $row + $rowspan - 1;
                    $sheet->mergeCells('E' . $row . ':E' . $rowEnd);
                    $sheet->mergeCells('F' . $row . ':F' . $rowEnd);
                    $sheet->mergeCells('G' . $row . ':G' . $rowEnd);
                    // vertical center biar rapi
                    $sheet->getStyle('E' . $row . ':G' . $rowEnd)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                }
            }

            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($styleData);
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            $row++;
        }

        // Total row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->setCellValue('C' . $row, $total_setoran_sales);
        $sheet->setCellValue('G' . $row, $total_setoran_bank);
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->mergeCells('D' . $row . ':F' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($styleTotal);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Piutang kasir row
        $row++;
        $sheet->setCellValue('A' . $row, 'PIUTANG KASIR');
        $sheet->setCellValue('C' . $row, $piutang_kasir);
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($styleTotal);
        $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row)->getFont()->setSize(12);

        // Column widths
        foreach (['A' => 14, 'B' => 20, 'C' => 24, 'D' => 16, 'E' => 20, 'F' => 14, 'G' => 24] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $filename = 'Report_Piutang_Kasir_' . str_replace('-', '_', $bulan) . '.xlsx';
        ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    // Helper: nama bulan Indonesia
    // ─────────────────────────────────────────────────────────────
    private function _nama_bulan($m)
    {
        $bulan = [
            1  => 'Januari',
            2  => 'Februari',
            3  => 'Maret',
            4  => 'April',
            5  => 'Mei',
            6  => 'Juni',
            7  => 'Juli',
            8  => 'Agustus',
            9  => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
        return $bulan[$m] ?? '';
    }
}
