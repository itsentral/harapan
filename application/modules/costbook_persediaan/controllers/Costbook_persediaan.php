<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Costbook_persediaan extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Costbook_Persediaan.View';
    protected $addPermission    = 'Costbook_Persediaan.Add';
    protected $managePermission = 'Costbook_Persediaan.Manage';
    protected $deletePermission = 'Costbook_Persediaan.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Costbook_persediaan/Costbook_persediaan_model'));
        $this->template->title('Costbook Persediaan');
        $this->template->page_icon('fa fa-dollar');
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->title('Costbook Persediaan (Costbook Stock)');
        $this->template->page_icon('fa fa-cubes');
        $this->template->render('index');
    }

    // SERVER SIDE
    public function data_side_costbook()
    {
        $this->Costbook_persediaan_model->get_json_costbook();
    }

    public function export_excel()
    {
        $this->db->select('
            ws.code_product,
            ws.nm_product,
            ws.kd_gudang,
            sm.nama AS unit_packing,
            ws.harga_beli,
            ws.update_date
        ');
        $this->db->from('warehouse_stock ws');
        $this->db->join('ms_satuan sm', 'ws.id_unit = sm.id', 'left');
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

        $sheet->setCellValue('A1', 'REPORT COSTBOOK PERSEDIAAN');
        $sheet->mergeCells('A1:G2');

        $headers = ['A' => '#', 'B' => 'Kode Product', 'C' => 'Nama Product', 'D' => 'Kode Gudang', 'E' => 'Unit Packing', 'F' => 'Costbook', 'G' => 'Updated At'];
        $rowHeader = 4;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $r = $rowHeader + 1;
        $no = 1;
        foreach ($rows as $row) {
            $costbook = (float) $row->harga_beli;

            $updated_at = (!empty($row->update_date) && $row->update_date !== '0000-00-00 00:00:00')
                ? date('d/M/Y H:i', strtotime($row->update_date))
                : '-';

            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValueExplicit('B' . $r, (string)$row->code_product, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $r, (string)$row->nm_product, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $r, (string)$row->kd_gudang, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $r, (string)$row->unit_packing, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F' . $r, $costbook, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('G' . $r, $updated_at, PHPExcel_Cell_DataType::TYPE_STRING);
            $r++;
        }

        $sheet->setTitle('Costbook Persediaan');
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Costbook_Persediaan_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}

/* End of file Costbook_persediaan.php */
