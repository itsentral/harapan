<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_margin_achievement extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Margin_Achievement.View';
    protected $addPermission    = 'Report_Margin_Achievement.Add';
    protected $managePermission = 'Report_Margin_Achievement.Manage';
    protected $deletePermission = 'Report_Margin_Achievement.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->load->model(['Report_margin_achievement/Report_margin_achievement_model']);
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $this->template->page_icon('fa fa-line-chart');
        $this->template->title('Laporan Margin Achievement per Sales');

        $bulan = (int) ($this->input->get('bulan') ?? date('n'));
        $tahun = (int) ($this->input->get('tahun') ?? date('Y'));

        $result = $this->Report_margin_achievement_model->get_data($bulan, $tahun);

        $data['bulan_list'] = $this->db->order_by('bulan_no', 'asc')->get('cr_bulan')->result_array();
        $data['bulan']  = $bulan;
        $data['tahun']  = $tahun;
        $data['rows']   = $result['rows'];
        $data['totals'] = $result['totals'];

        $this->template->render('index', $data);
    }

    public function export_excel()
    {
        $this->auth->restrict($this->viewPermission);

        $bulan = (int) ($this->input->get('bulan') ?? date('n'));
        $tahun = (int) ($this->input->get('tahun') ?? date('Y'));

        $result = $this->Report_margin_achievement_model->get_data($bulan, $tahun);
        $rows   = $result['rows'];
        $totals = $result['totals'];

        $bln_row    = $this->db->where('bulan_no', $bulan)->get('cr_bulan')->row_array();
        $nama_bulan = $bln_row ? $bln_row['bulan'] : 'Bulan ' . $bulan;

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $this->load->library('PHPExcel');
        PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('Margin Achievement');

        // ===== STYLE =====
        $styleTitle = [
            'font'      => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER],
        ];

        $tableHeader = [
            'font'      => ['bold' => true],
            'alignment' => [
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER,
            ],
            'borders'   => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
            'fill'      => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'D9EDF7']],
        ];

        $tableBody = [
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];

        $rowTotalStyle = [
            'font'    => ['bold' => true],
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
            'fill'    => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'D9D9D9']],
        ];

        // ===== JUDUL =====
        $sheet->setCellValue('A1', 'LAPORAN MARGIN ACHIEVEMENT PER SALES');
        $sheet->mergeCells('A1:K1');
        $sheet->getStyle('A1:K1')->applyFromArray($styleTitle);

        $sheet->setCellValue('A2', 'Periode: ' . $nama_bulan . ' ' . $tahun);
        $sheet->mergeCells('A2:K2');
        $sheet->getStyle('A2:K2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // ===== HEADER KOLOM =====
        $headers = [
            'A' => 'No',
            'B' => 'Nama Sales',
            'C' => 'Target Omset (Rp)',
            'D' => 'Realisasi Omset (Rp)',
            'E' => '% Ach Omset',
            'F' => 'DPP',
            'G' => 'Realisasi Margin (Rp)',
            'H' => '% Ach Margin',
            'I' => 'Actual Margin (%)',
            'J' => 'Target Margin %',
            'K' => 'Status Achievement',
        ];

        $rowHeader = 4;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('A' . $rowHeader . ':K' . $rowHeader)->applyFromArray($tableHeader);

        // Kolom "No" tidak perlu auto-size (cukup lebar untuk 2-3 digit angka),
        // supaya tidak melebar akibat konten lain di kolom A (mis. baris Keterangan).
        $sheet->getColumnDimension('A')->setAutoSize(false);
        $sheet->getColumnDimension('A')->setWidth(6);

        // ===== ISI DATA =====
        $r = $rowHeader + 1;
        $no = 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $r, $no);
            $sheet->setCellValue('B' . $r, $row['nama_sales']);

            $sheet->setCellValueExplicit('C' . $r, $row['target_omset'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('C' . $r)->getNumberFormat()->setFormatCode('#,##0');

            $sheet->setCellValueExplicit('D' . $r, $row['realisasi_omset'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('D' . $r)->getNumberFormat()->setFormatCode('#,##0');

            $sheet->setCellValueExplicit('E' . $r, $row['pct_ach_omset'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('E' . $r)->getNumberFormat()->setFormatCode('0.0%');

            $sheet->setCellValueExplicit('F' . $r, $row['dpp'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('#,##0');

            $sheet->setCellValueExplicit('G' . $r, $row['realisasi_margin_rp'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('G' . $r)->getNumberFormat()->setFormatCode('#,##0');

            $sheet->setCellValueExplicit('H' . $r, $row['pct_ach_margin'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('H' . $r)->getNumberFormat()->setFormatCode('0.0%');

            $sheet->setCellValueExplicit('I' . $r, $row['margin_pct_thd_omset'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('I' . $r)->getNumberFormat()->setFormatCode('0.0%');

            $sheet->setCellValueExplicit('J' . $r, $row['target_margin_pct'] / 100, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('J' . $r)->getNumberFormat()->setFormatCode('0.0%');

            $sheet->setCellValue('K' . $r, $row['status']);

            $sheet->getStyle('A' . $r . ':K' . $r)->applyFromArray($tableBody);

            // Highlight status
            if ($row['status'] == 'Tercapai') {
                $sheet->getStyle('K' . $r)->getFont()->getColor()->setRGB('008000');
            } elseif ($row['status'] == 'Mendekati Target') {
                $sheet->getStyle('K' . $r)->getFont()->getColor()->setRGB('B8860B');
            } else {
                $sheet->getStyle('K' . $r)->getFont()->getColor()->setRGB('FF0000');
            }

            $no++;
            $r++;
        }

        // ===== BARIS TOTAL =====
        $sheet->setCellValue('A' . $r, '');
        $sheet->mergeCells('A' . $r . ':B' . $r);
        $sheet->setCellValue('A' . $r, 'TOTAL');
        $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValueExplicit('C' . $r, $totals['target_omset'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('C' . $r)->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValueExplicit('D' . $r, $totals['realisasi_omset'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('D' . $r)->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValueExplicit('E' . $r, $totals['pct_ach_omset'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('E' . $r)->getNumberFormat()->setFormatCode('0.0%');

        $sheet->setCellValueExplicit('F' . $r, $totals['dpp'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('F' . $r)->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValueExplicit('G' . $r, $totals['realisasi_margin_rp'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('G' . $r)->getNumberFormat()->setFormatCode('#,##0');

        $sheet->setCellValueExplicit('H' . $r, $totals['pct_ach_margin'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('H' . $r)->getNumberFormat()->setFormatCode('0.0%');

        $sheet->setCellValueExplicit('I' . $r, $totals['margin_pct_thd_omset'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('I' . $r)->getNumberFormat()->setFormatCode('0.0%');

        $sheet->setCellValue('J' . $r, '');
        $sheet->setCellValue('K' . $r, '');

        $sheet->getStyle('A' . $r . ':K' . $r)->applyFromArray($rowTotalStyle);

        // ===== KETERANGAN =====
        $r += 2;
        // Catatan: setiap baris keterangan di-merge A:K agar tidak ikut dihitung
        // oleh auto-size kolom A (kalau tidak di-merge, teks panjang ini akan
        // membuat kolom "No" ikut melebar mengikuti panjang kalimat).
        $keteranganLines = [
            'Keterangan:',
            '1. Target Omset dan Realisasi Omset diambil dari Report Penjualan per Sales.',
            '2. % Ach Omset = Realisasi Omset / Target Omset.',
            '3. DPP = Realisasi Omset (Rp) / 1,11.',
            '4. Target Margin % diambil dari Master Target Margin per Sales.',
            '5. Actual Margin (%) = (Realisasi Omset (Revenue) - HPP (Harga Pokok Penjualan/COGS)) aktual per baris invoice / Realisasi Omset.',
            '6. Realisasi Margin (Rp) = DPP x Actual Margin (%).',
            '7. % Ach Margin = Actual Margin (%) / Target Margin %.',
            '8. Status: >=100% Tercapai, 90-99,9% Mendekati Target, <90% Belum Tercapai.',
        ];

        foreach ($keteranganLines as $i => $line) {
            $sheet->setCellValue('A' . $r, $line);
            $sheet->mergeCells('A' . $r . ':K' . $r);
            if ($i === 0) {
                $sheet->getStyle('A' . $r)->getFont()->setBold(true);
            }
            $r++;
        }

        // Output
        // NOTE: pakai Excel5 (.xls) bukan Excel2007 (.xlsx) karena writer Excel2007
        // butuh ekstensi PHP ZipArchive yang tidak terpasang di server ini.
        // Konsisten dengan mayoritas report lain di codebase (report_penjualan,
        // report_debt, report_pembelian, report_penagihan, report_inventory).
        $filename = 'Margin_Achievement_' . $nama_bulan . '_' . $tahun . '.xls';
        $writer   = PHPExcel_IOFactory::createWriter($xls, 'Excel5');

        if (ob_get_length()) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
