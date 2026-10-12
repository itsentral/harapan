<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pricebook extends Admin_Controller
{
    // Permission
    protected $viewPermission   = 'Pricebook.View';
    protected $addPermission    = 'Pricebook.Add';
    protected $managePermission = 'Pricebook.Manage';
    protected $deletePermission = 'Pricebook.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'pricebook/pricebook_model',
        ));

        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $this->template->page_icon('fa fa-money');
        $this->template->title('Pricebook');

        $this->template->render('index');
    }

    /**
     * Server-side DataTables endpoint.
     * Harga sekarang -> warehouse_stock
     * Harga lalu     -> warehouse_stock_per_days (filter by tgl_backup)
     */
    public function data_side_pricebook()
    {
        $this->pricebook_model->get_json_pricebook();
    }

    /**
     * Total nilai inventory keseluruhan (JSON).
     */
    public function total_inventory()
    {
        $tanggal = $this->input->post('tanggal', true);
        $total   = $this->pricebook_model->get_total_inventory($tanggal);

        header('Content-Type: application/json');
        echo json_encode([
            'total'    => $total,
            'totalFmt' => number_format($total, 0, ',', '.'),
        ]);
    }

    /**
     * Export report pricebook ke Excel.
     */
    public function export_excel()
    {
        $this->auth->restrict($this->viewPermission);

        $tanggal = $this->input->get('tanggal', true);
        $rows    = $this->pricebook_model->get_data_export($tanggal);

        if (empty($rows)) {
            echo "<script>alert('Data tidak ditemukan'); window.history.back();</script>";
            return;
        }

        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $this->load->library('PHPExcel');

        $xls   = new PHPExcel();
        $sheet = $xls->getActiveSheet();

        $periode = ($tanggal) ? 'Harga Tanggal ' . $tanggal : 'Harga Sekarang';
        $sheet->setCellValue('A1', 'REPORT PRICEBOOK - ' . $periode);
        $sheet->mergeCells('A1:F2');

        $headers = [
            'A' => '#',
            'B' => 'Id Material',
            'C' => 'Product',
            'D' => 'Gudang',
            'E' => 'Qty',
            'F' => 'Costbook',
            'G' => 'Total Nilai',
        ];
        $rowHeader = 4;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $r = $rowHeader + 1;
        $no = 1;
        $grandTotal = 0;
        foreach ($rows as $row) {
            $qty        = (float) $row->qty;
            $costbook   = (float) $row->costbook;
            $totalNilai = (float) $row->total_nilai;
            $grandTotal += $totalNilai;

            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValueExplicit('B' . $r, (string)$row->id_material, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $r, (string)$row->nm_product, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $r, (string)$row->kd_gudang, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $r, $qty, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('F' . $r, $costbook, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('G' . $r, $totalNilai, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $r++;
        }

        // Baris grand total
        $sheet->setCellValue('A' . $r, 'GRAND TOTAL');
        $sheet->mergeCells('A' . $r . ':F' . $r);
        $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValueExplicit('G' . $r, (float)$grandTotal, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        $sheet->getStyle('A' . $rowHeader . ':G' . $r)->getFont()->setBold(false);
        $sheet->getStyle('A' . $r . ':G' . $r)->getFont()->setBold(true);

        $sheet->setTitle('Pricebook');
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Pricebook_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}
