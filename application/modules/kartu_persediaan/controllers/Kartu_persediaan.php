<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * Controller Kartu Persediaan (metode Moving Average)
 *
 * Menyajikan dua tampilan dalam satu halaman (master-detail):
 *   - REKAP  : satu baris per produk (DEBET / KREDIT / SALDO) — blok kiri Excel.
 *   - DETAIL : kartu transaksi per produk — blok kanan Excel, muncul saat baris rekap diklik.
 *
 * Sumber data : tabel kartu_stok (lihat Kartu_persediaan_model).
 * Permission harga mengikuti pola Warehouse.ViewHarga (seragam dgn Kartu Stok).
 */
class Kartu_persediaan extends Admin_Controller
{
    // Permission
    protected $viewPermission   = 'Kartu_Persediaan.View';
    protected $addPermission    = 'Kartu_Persediaan.Add';
    protected $managePermission = 'Kartu_Persediaan.Manage';
    protected $deletePermission = 'Kartu_Persediaan.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Kartu_persediaan/Kartu_persediaan_model');
        $this->template->title('Kartu Persediaan');
        $this->template->page_icon('fa fa-book');
        date_default_timezone_set('Asia/Bangkok');
    }

    private function _is_admin()
    {
        return $this->auth->is_admin() || has_permission('Warehouse.ViewHarga');
    }

    // ================= HALAMAN UTAMA =================
    public function index()
    {
        $this->template->set('is_admin', $this->_is_admin());
        $this->template->render('kartu_persediaan');
    }

    // ================= REKAP (DataTables server-side) =================
    public function data_side_rekap()
    {
        $requestData = $_REQUEST;

        $start_date  = isset($requestData['start_date'])  ? $requestData['start_date']  : null;
        $end_date    = isset($requestData['end_date'])    ? $requestData['end_date']    : null;
        $anchor_date = isset($requestData['anchor_date']) ? $requestData['anchor_date'] : null;
        $search      = isset($requestData['search']['value']) ? $requestData['search']['value'] : null;
        $start       = isset($requestData['start'])  ? (int) $requestData['start']  : 0;
        $length      = isset($requestData['length']) ? (int) $requestData['length'] : 10;

        $is_admin = $this->_is_admin();

        // Hitung seluruh rekap (sudah moving-average), lalu paginasi di PHP.
        $all = $this->Kartu_persediaan_model->get_rekap($start_date, $end_date, $search, $anchor_date);

        $recordsTotal = count($all);
        $slice = ($length == -1) ? $all : array_slice($all, $start, $length);

        $data = [];
        $no = $start + 1;
        foreach ($slice as $r) {
            $row = [];
            $row[] = "<div align='center'>" . ($no++) . "</div>";
            $row[] = $r['code_lv4'];
            $row[] = $r['nm_product'];
            // DEBET
            $row[] = "<div align='right'>" . number_format($r['debet_qty']) . "</div>";
            if ($is_admin) $row[] = "<div align='right'>" . number_format($r['debet_total'], 2) . "</div>";
            // KREDIT
            $row[] = "<div align='right'>" . number_format($r['kredit_qty']) . "</div>";
            if ($is_admin) $row[] = "<div align='right'>" . number_format($r['kredit_total'], 2) . "</div>";
            // SALDO
            $row[] = "<div align='right'>" . number_format($r['saldo_qty']) . "</div>";
            if ($is_admin) $row[] = "<div align='right'>" . number_format($r['saldo_harga'], 2) . "</div>";
            if ($is_admin) $row[] = "<div align='right'>" . number_format($r['saldo_total'], 2) . "</div>";
            // tombol detail (data attribute dipakai JS)
            $row[] = "<div align='center'><button type='button' class='btn btn-xs bg-purple btn-detail' "
                . "data-code='" . htmlspecialchars($r['code_lv4'], ENT_QUOTES) . "' "
                . "data-nama='" . htmlspecialchars($r['nm_product'], ENT_QUOTES) . "'>"
                . "<i class='fa fa-eye'></i> Detail</button></div>";
            $data[] = $row;
        }

        echo json_encode([
            'draw'            => isset($requestData['draw']) ? intval($requestData['draw']) : 0,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal, // filter (search) sudah diterapkan di model
            'data'            => $data,
        ]);
    }

    // ================= DETAIL (JSON untuk panel bawah) =================
    public function data_detail()
    {
        $code_lv4    = $this->input->get('code', true);
        $start_date  = $this->input->get('start_date', true);
        $end_date    = $this->input->get('end_date', true);
        $anchor_date = $this->input->get('anchor_date', true);
        $is_admin    = $this->_is_admin();

        $detail = $this->Kartu_persediaan_model->get_detail($code_lv4, $start_date, $end_date, $anchor_date);

        echo json_encode([
            'is_admin'   => $is_admin,
            'code_lv4'   => $detail['code_lv4'],
            'nm_product' => $detail['nm_product'],
            'rows'       => $detail['rows'],
            'total'      => $detail['total'],
        ]);
    }

    // ================= EXPORT REKAP =================
    public function export_rekap()
    {
        $start_date  = $this->input->get('start_date', true);
        $end_date    = $this->input->get('end_date', true);
        $anchor_date = $this->input->get('anchor_date', true);
        $is_admin    = $this->_is_admin();

        $rows = $this->Kartu_persediaan_model->get_rekap($start_date, $end_date, null, $anchor_date);

        if (empty($rows)) {
            echo "<script>alert('Data tidak ditemukan'); window.history.back();</script>";
            return;
        }

        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $this->load->library('PHPExcel');

        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();

        $lastCol = $is_admin ? 'I' : 'F';

        $periode = ($start_date && $end_date) ? $start_date . ' s/d ' . $end_date : 'Semua Data';
        $sheet->setCellValue('A1', 'KARTU PERSEDIAAN - REKAP (' . $periode . ')');
        $sheet->mergeCells('A1:' . $lastCol . '2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Header 2 baris (mirip Excel: DEBET / KREDIT / SALDO).
        $r = 4;
        $sheet->setCellValue('A' . $r, '#');
        $sheet->setCellValue('B' . $r, 'Kode Barang');
        $sheet->setCellValue('C' . $r, 'Nama Barang');
        $sheet->setCellValue('D' . $r, 'DEBET');
        if ($is_admin) {
            $sheet->setCellValue('F' . $r, 'KREDIT');
            $sheet->setCellValue('H' . $r, 'SALDO');
            // Merge label grup + kolom tunggal.
            $sheet->mergeCells('A4:A5');
            $sheet->mergeCells('B4:B5');
            $sheet->mergeCells('C4:C5');
            $sheet->mergeCells('D4:E4'); // DEBET
            $sheet->mergeCells('F4:G4'); // KREDIT
            $sheet->mergeCells('H4:I4'); // SALDO
        } else {
            $sheet->setCellValue('E' . $r, 'KREDIT');
            $sheet->setCellValue('F' . $r, 'SALDO');
            $sheet->mergeCells('A4:A5');
            $sheet->mergeCells('B4:B5');
            $sheet->mergeCells('C4:C5');
            $sheet->mergeCells('D4:D5');
            $sheet->mergeCells('E4:E5');
            $sheet->mergeCells('F4:F5');
        }
        $r2 = 5;
        if ($is_admin) {
            $sheet->setCellValue('D' . $r2, 'Qty');
            $sheet->setCellValue('E' . $r2, 'Total');
            $sheet->setCellValue('F' . $r2, 'Qty');
            $sheet->setCellValue('G' . $r2, 'Total');
            $sheet->setCellValue('H' . $r2, 'Qty');
            $sheet->setCellValue('I' . $r2, 'Total');
        }
        // Non-admin: label grup sudah mewakili kolom tunggal (merge 4:5), tak perlu sub-header.

        $rr = 6;
        $no = 1;
        foreach ($rows as $row) {
            $sheet->setCellValueExplicit('A' . $rr, $no++, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('B' . $rr, (string) $row['code_lv4'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $rr, (string) $row['nm_product'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $rr, (float) $row['debet_qty'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            if ($is_admin) {
                $sheet->setCellValueExplicit('E' . $rr, (float) $row['debet_total'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit('F' . $rr, (float) $row['kredit_qty'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit('G' . $rr, (float) $row['kredit_total'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit('H' . $rr, (float) $row['saldo_qty'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit('I' . $rr, (float) $row['saldo_total'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            } else {
                $sheet->setCellValueExplicit('E' . $rr, (float) $row['kredit_qty'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->setCellValueExplicit('F' . $rr, (float) $row['saldo_qty'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            }
            $rr++;
        }

        // Border + styling grid: header baris 4-5, data 6..($rr-1).
        $this->_style_excel_grid($sheet, 4, 5, $rr - 1, $lastCol);

        foreach (range('A', $lastCol) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setTitle('Rekap Persediaan');
        $this->_download_excel($xls, 'Kartu_Persediaan_Rekap_' . date('Ymd_His'));
    }

    // ================= EXPORT DETAIL per barang =================
    public function export_detail()
    {
        $code_lv4    = $this->input->get('code', true);
        $start_date  = $this->input->get('start_date', true);
        $end_date    = $this->input->get('end_date', true);
        $anchor_date = $this->input->get('anchor_date', true);
        $is_admin    = $this->_is_admin();

        $detail = $this->Kartu_persediaan_model->get_detail($code_lv4, $start_date, $end_date, $anchor_date);

        if (empty($detail['rows'])) {
            echo "<script>alert('Data tidak ditemukan'); window.history.back();</script>";
            return;
        }

        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $this->load->library('PHPExcel');

        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();

        $periode = ($start_date && $end_date) ? $start_date . ' s/d ' . $end_date : 'Semua Data';
        $sheet->setCellValue('A1', 'KARTU PERSEDIAAN - ' . $detail['nm_product'] . ' (' . $periode . ')');
        $sheet->mergeCells('A1:L2');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Header 2 baris (TGL / NO BUKTI / DEBET qhp / KREDIT qhp / SALDO qhp).
        $sheet->setCellValue('A4', 'TGL');
        $sheet->setCellValue('B4', 'NO BUKTI');
        $sheet->setCellValue('C4', 'Keterangan');
        $sheet->setCellValue('D4', 'DEBET');
        $sheet->setCellValue('G4', 'KREDIT');
        $sheet->setCellValue('J4', 'SALDO');
        // Merge label grup.
        $sheet->mergeCells('A4:A5');
        $sheet->mergeCells('B4:B5');
        $sheet->mergeCells('C4:C5');
        $sheet->mergeCells('D4:F4'); // DEBET
        $sheet->mergeCells('G4:I4'); // KREDIT
        $sheet->mergeCells('J4:L4'); // SALDO
        $sheet->setCellValue('D5', 'Qty');
        $sheet->setCellValue('E5', 'Harga');
        $sheet->setCellValue('F5', 'Total');
        $sheet->setCellValue('G5', 'Qty');
        $sheet->setCellValue('H5', 'Harga');
        $sheet->setCellValue('I5', 'Total');
        $sheet->setCellValue('J5', 'Qty');
        $sheet->setCellValue('K5', 'Harga');
        $sheet->setCellValue('L5', 'Total');

        $rr = 6;
        foreach ($detail['rows'] as $row) {
            $tgl = !empty($row['tgl_transaksi']) ? substr($row['tgl_transaksi'], 0, 10) : '';
            $sheet->setCellValueExplicit('A' . $rr, (string) $tgl, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $rr, (string) $row['no_transaksi'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $rr, (string) $row['transaksi'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $rr, (float) $row['debet_qty'],   PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('E' . $rr, (float) $row['debet_harga'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('F' . $rr, (float) $row['debet_total'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('G' . $rr, (float) $row['kredit_qty'],   PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('H' . $rr, (float) $row['kredit_harga'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('I' . $rr, (float) $row['kredit_total'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('J' . $rr, (float) $row['saldo_qty'],   PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('K' . $rr, (float) $row['saldo_harga'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('L' . $rr, (float) $row['saldo_total'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $rr++;
        }

        // Baris TOTAL.
        $t = $detail['total'];
        $sheet->setCellValue('C' . $rr, 'TOTAL');
        $sheet->setCellValueExplicit('D' . $rr, (float) $t['debet_qty'],    PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('F' . $rr, (float) $t['debet_total'],  PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('G' . $rr, (float) $t['kredit_qty'],   PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('I' . $rr, (float) $t['kredit_total'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('J' . $rr, (float) $t['saldo_qty'],    PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('K' . $rr, (float) $t['saldo_harga'],  PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->setCellValueExplicit('L' . $rr, (float) $t['saldo_total'],  PHPExcel_Cell_DataType::TYPE_NUMERIC);

        // Border + styling grid: header baris 4-5, data 6..$rr (termasuk baris TOTAL).
        $this->_style_excel_grid($sheet, 4, 5, $rr, 'L');
        // Baris TOTAL tebal + latar abu.
        $sheet->getStyle('A' . $rr . ':L' . $rr)->getFont()->setBold(true);
        $sheet->getStyle('A' . $rr . ':L' . $rr)->getFill()
            ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F0F0F0');

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setTitle('Detail Persediaan');
        $this->_download_excel($xls, 'Kartu_Persediaan_Detail_' . date('Ymd_His'));
    }

    /**
     * Terapkan border + styling grid pada sheet export.
     *   - Border tipis di seluruh range header..data.
     *   - Border tebal di sekeliling + garis bawah header.
     *   - Header (baris $headerStart..$headerEnd) bold, rata tengah, latar abu.
     *
     * @param object $sheet       PHPExcel_Worksheet aktif.
     * @param int    $headerStart baris awal header (mis. 4).
     * @param int    $headerEnd   baris akhir header (mis. 5).
     * @param int    $lastRow     baris terakhir data.
     * @param string $lastCol     kolom terakhir (mis. 'I' / 'L').
     */
    private function _style_excel_grid($sheet, $headerStart, $headerEnd, $lastRow, $lastCol)
    {
        $full   = 'A' . $headerStart . ':' . $lastCol . $lastRow;
        $header = 'A' . $headerStart . ':' . $lastCol . $headerEnd;

        // Border tipis seluruh sel.
        $sheet->getStyle($full)->getBorders()->getAllBorders()
            ->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

        // Border tebal mengelilingi seluruh tabel.
        $sheet->getStyle($full)->getBorders()->getOutline()
            ->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);

        // Header: bold, rata tengah (horizontal+vertikal), latar abu muda, garis bawah tebal.
        $sheet->getStyle($header)->getFont()->setBold(true);
        $sheet->getStyle($header)->getAlignment()
            ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
            ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $sheet->getStyle($header)->getFill()
            ->setFillType(PHPExcel_Style_Fill::FILL_SOLID)
            ->getStartColor()->setRGB('DCE6F1');
        $sheet->getStyle($header)->getBorders()->getBottom()
            ->setBorderStyle(PHPExcel_Style_Border::BORDER_MEDIUM);
    }

    // Helper download (Excel5 / .xls) — konsisten dengan modul warehouse.
    private function _download_excel($xls, $filename)
    {
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
/* End of file Kartu_persediaan.php */
