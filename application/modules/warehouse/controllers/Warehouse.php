<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * @author Harboens
 * @copyright Copyright (c) 2020
 *
 * This is controller for Master Warehouse
 */
$status = array();
class Warehouse extends Admin_Controller
{
    //Permission
    protected $viewPermission       = 'Warehouse.View';
    protected $addPermission        = 'Warehouse.Add';
    protected $managePermission     = 'Warehouse.Manage';
    protected $deletePermission     = 'Warehouse.Delete';
    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Warehouse/Warehouse_model', 'All/All_model'));
        $this->template->title('Gudang');
        $this->template->page_icon('fa fa-dollar');
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->title('Stock Product');
        $this->template->page_icon('fa fa-cubes');
        $this->template->render('index');
    }

    public function kartu_stok()
    {
        $this->template->title('Kartu Stok');
        $this->template->page_icon('fa fa-file');
        $this->template->render('kartu_stok');
    }

    // SERVER SIDE
    public function data_side_warehouse_stock()
    {
        $this->Warehouse_model->get_json_warehouse_stock();
    }

    public function data_side_kartu_stok()
    {
        $this->Warehouse_model->get_json_kartu_stok();
    }

    public function export_excel()
    {
        $this->db->select('
            ws.code_product,
            ws.nm_product,
            ws.kd_gudang,
            sp.nama AS unit_packing,
            sm.nama AS unit,
            ws.qty_stock,
            ws.qty_booking
        ');
        $this->db->from('warehouse_stock ws');
        $this->db->join('ms_satuan sm', 'ws.id_unit = sm.id', 'left');
        $this->db->join('ms_satuan sp', 'ws.id_unit_packing = sp.id', 'left');
        $this->db->join('new_inventory_4 ni', 'ni.code_lv4 = ws.code_lv4', 'inner');
        $this->db->where('ni.deleted_date IS NULL', null, false);
        $this->db->where('ni.deleted_by IS NULL', null, false);
        $this->db->order_by('ws.code_product', 'asc');

        $rows = $this->db->get()->result();

        if (empty($rows)) {
            echo "<script>alert('Data tidak ditemukan'); window.history.back();</script>";
            return;
        }

        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $this->load->library('PHPExcel');

        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();

        $sheet->setCellValue('A1', 'REPORT STOCK PRODUCT');
        $sheet->mergeCells('A1:I2');

        $headers = ['A' => '#', 'B' => 'Kode Product', 'C' => 'Nama Product', 'D' => 'Kode Gudang', 'E' => 'Unit Packing', 'F' => 'Unit Measurement', 'G' => 'Jumlah Stok', 'H' => 'Stok Booking', 'I' => 'Stok Available'];
        $rowHeader = 4;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $r = $rowHeader + 1;
        $no = 1;
        foreach ($rows as $row) {
            $qty_stock   = (float) $row->qty_stock;
            $qty_booking = (float) $row->qty_booking;
            $available   = $qty_stock - $qty_booking;

            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValueExplicit('B' . $r, (string)$row->code_product, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $r, (string)$row->nm_product, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $r, (string)$row->kd_gudang, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $r, (string)$row->unit_packing, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $r, (string)$row->unit, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G' . $r, $qty_stock, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('H' . $r, $qty_booking, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('I' . $r, $available, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $r++;
        }

        $sheet->setTitle('Stock Product');
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Stock_Product_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function export_excel_kartu_stok()
    {
        $start = $this->input->get('start_date', true);
        $end   = $this->input->get('end_date', true);

        $this->db->select('ks.*');
        $this->db->from('kartu_stok ks');
        $this->db->where('ks.deleted', null);
        if (!empty($start)) $this->db->where('DATE(ks.tgl_transaksi) >=', $start);
        if (!empty($end))   $this->db->where('DATE(ks.tgl_transaksi) <=', $end);
        $this->db->order_by('ks.id', 'asc');

        $rows = $this->db->get()->result();

        if (empty($rows)) {
            echo "<script>alert('Data tidak ditemukan'); window.history.back();</script>";
            return;
        }

        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $this->load->library('PHPExcel');

        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();

        $periode = ($start && $end) ? $start . ' s/d ' . $end : 'Semua Data';
        $sheet->setCellValue('A1', 'REPORT KARTU STOK - ' . $periode);
        $sheet->mergeCells('A1:N2');

        $headers = [
            'A' => '#',
            'B' => 'Tgl Transaksi',
            'C' => 'No. Transaksi',
            'D' => 'Jenis Transaksi',
            'E' => 'Id Produk',
            'F' => 'Produk',
            'G' => 'Stock Awal',
            'H' => 'Booking Awal',
            'I' => 'Free Stock Awal',
            'J' => 'In/Out',
            'K' => 'Booking Transaksi',
            'L' => 'Stock Akhir',
            'M' => 'Booking Akhir',
            'N' => 'Free Stock Akhir',
        ];
        $rowHeader = 4;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $r = $rowHeader + 1;
        $no = 1;
        foreach ($rows as $row) {
            $sheet->setCellValue('A' . $r, $no++);
            if (!empty($row->tgl_transaksi)) {
                // Ambil komponen tanggal langsung dari string (hindari konversi lewat strtotime()
                // + PHPToExcel() yang memaksa timezone UTC, karena itu bisa membuat tanggal
                // mundur 1 hari saat timezone aplikasi (Asia/Bangkok, UTC+7) berbeda dari UTC).
                $dateOnly = substr($row->tgl_transaksi, 0, 10); // 'Y-m-d'
                list($y, $m, $d) = array_map('intval', explode('-', $dateOnly));
                $tgl = (float)PHPExcel_Shared_Date::FormattedPHPToExcel($y, $m, $d);
                $sheet->setCellValueExplicit('B' . $r, $tgl, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                $sheet->getStyle('B' . $r)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
            }
            $sheet->setCellValueExplicit('C' . $r, (string)$row->no_transaksi, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $r, (string)$row->transaksi, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $r, (string)$row->code_lv4, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $r, (string)$row->nm_product, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G' . $r, (float)$row->qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('H' . $r, (float)$row->qty_book, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('I' . $r, (float)$row->qty_free, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('J' . $r, (float)$row->qty_transaksi, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('K' . $r, (float)($row->qty_book_akhir - $row->qty_book), PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('L' . $r, (float)$row->qty_akhir, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('M' . $r, (float)$row->qty_book_akhir, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('N' . $r, (float)$row->qty_free_akhir, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $r++;
        }

        $sheet->setTitle('Kartu Stok');
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Kartu_Stok_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
