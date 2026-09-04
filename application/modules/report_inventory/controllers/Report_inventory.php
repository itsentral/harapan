<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_inventory extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Inventory.View';
    protected $addPermission    = 'Report_Inventory.Add';
    protected $managePermission = 'Report_Inventory.Manage';
    protected $deletePermission = 'Report_Inventory.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->library(array('upload', 'Image_lib'));
        $this->load->model(array(
            'report_inventory/report_inventory_model',
        ));

        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->page_icon('fa fa-list');
        $this->template->title('Report Nilai Inventory');

        $this->template->render('index');
    }

    public function data_side_stock()
    {
        $this->report_inventory_model->get_json_stock();
    }

    public function export_excel()
    {
        // 1) Ambil parameter & data
        $tanggal = $this->input->get('tanggal', true);            // yyyy-mm-dd
        $rows    = $this->report_inventory_model->get_data_stock_for_excel($tanggal);

        if (empty($tanggal) || empty($rows)) {
            $this->session->set_flashdata('alert', 'Data tidak ada untuk tanggal tersebut.');
            redirect('report_inventory');
            return;
        }

        // 2) Siapkan PHPExcel + binder aman
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $this->load->library('PHPExcel');
        PHPExcel_Cell::setValueBinder(new PHPExcel_Cell_AdvancedValueBinder());

        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();

        // (opsional) style helper-mu
        $whiteCenterBold = function_exists('whiteCenterBold') ? whiteCenterBold() : [];
        $mainTitle       = function_exists('mainTitle') ? mainTitle() : [];
        $tableBodyLeft   = function_exists('tableBodyLeft') ? tableBodyLeft() : [];
        $tableBodyRight  = function_exists('tableBodyRight') ? tableBodyRight() : [];

        // 3) Judul
        $sheet->setCellValue('A1', 'REPORT NILAI INVENTORY - ' . $tanggal);
        $sheet->mergeCells('A1:K2');
        if ($mainTitle) $sheet->getStyle('A1:K2')->applyFromArray($mainTitle);

        // 4) Header kolom (persis seperti tabel)
        $headers = [
            'A' => '#',
            'B' => 'Id Product',
            'C' => 'Code Product',
            'D' => 'Product',
            'E' => 'Qty Stock',
            'F' => 'Qty Booking',
            'G' => 'Qty Free',
            'H' => 'Gudang',
            'I' => 'Tanggal Stock',
            'J' => 'Costbook',
            'K' => 'Total Nilai',
        ];
        $rowHeader = 4;
        foreach ($headers as $col => $label) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $sheet->setCellValue($col . $rowHeader, $label);
            if ($whiteCenterBold) $sheet->getStyle($col . $rowHeader)->applyFromArray($whiteCenterBold);
        }

        // 5) Isi data (pakai explicit type supaya aman)
        $r = $rowHeader + 1;
        $no = 1;
        foreach ($rows as $row) {
            $sheet->setCellValueExplicit('A' . $r, $no++, PHPExcel_Cell_DataType::TYPE_NUMERIC);

            // string: jaga leading zero
            $sheet->setCellValueExplicit('B' . $r, (string)$row['id_material'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $r, (string)$row['code_product'], PHPExcel_Cell_DataType::TYPE_STRING);

            $sheet->setCellValue('D' . $r, $row['nm_product']);

            // numeric
            $sheet->setCellValueExplicit('E' . $r, (float)$row['qty_stock'],   PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('F' . $r, (float)$row['qty_booking'], PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('G' . $r, (float)$row['qty_free'],    PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $sheet->setCellValue('H' . $r, $row['nm_gudang']);

            // tanggal → serial excel
            // Ambil komponen tanggal langsung dari string (hindari konversi lewat strtotime()
            // + PHPToExcel() yang memaksa timezone UTC, karena itu bisa membuat tanggal
            // mundur 1 hari saat timezone aplikasi (Asia/Bangkok, UTC+7) berbeda dari UTC).
            $dateOnly = substr($row['tgl_backup'], 0, 10); // 'Y-m-d'
            list($y, $m, $d) = array_map('intval', explode('-', $dateOnly));
            $excelDate = PHPExcel_Shared_Date::FormattedPHPToExcel($y, $m, $d);
            $sheet->setCellValueExplicit('I' . $r, $excelDate, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->getStyle('I' . $r)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

            // numeric (uang)
            $sheet->setCellValueExplicit('J' . $r, (float)$row['harga_beli'],  PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('K' . $r, (float)$row['total_nilai'], PHPExcel_Cell_DataType::TYPE_NUMERIC);

            // apply style kiri/kanan kalau kamu pakai helper
            if ($tableBodyLeft) {
                $sheet->getStyle('B' . $r)->applyFromArray($tableBodyLeft);
                $sheet->getStyle('C' . $r)->applyFromArray($tableBodyLeft);
                $sheet->getStyle('D' . $r)->applyFromArray($tableBodyLeft);
                $sheet->getStyle('H' . $r)->applyFromArray($tableBodyLeft);
            }
            if ($tableBodyRight) {
                $sheet->getStyle('E' . $r . ':G' . $r)->applyFromArray($tableBodyRight);
                $sheet->getStyle('J' . $r . ':K' . $r)->applyFromArray($tableBodyRight);
            }

            $r++;
        }

        $sheet->setTitle('Nilai Inventory');

        // 6) Output sebagai XLS (Excel5)
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="report_nilai_inventory_' . $tanggal . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
