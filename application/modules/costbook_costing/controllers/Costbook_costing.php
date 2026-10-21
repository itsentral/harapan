<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Costbook_costing extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Costbook_Costing.View';
    protected $addPermission    = 'Costbook_Costing.Add';
    protected $managePermission = 'Costbook_Costing.Manage';
    protected $deletePermission = 'Costbook_Costing.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->load->model(array('Costbook_costing/Costbook_costing_model'));
        $this->template->title('Costbook Costing');
        $this->template->page_icon('fa fa-calculator');
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->template->title('Costbook Costing');
        $this->template->page_icon('fa fa-calculator');
        $this->template->render('index');
    }

    // SERVER SIDE
    public function data_side_costbook_costing()
    {
        $this->Costbook_costing_model->get_json_costbook_costing();
    }

    public function export_excel()
    {
        $this->db->select('
            pc.id,
            pc.code_lv4,
            pc.product_name,
            pc.harga_beli,
            pc.status,
            pc.modified_at
        ');
        $this->db->from('product_costing pc');
        $this->db->order_by('pc.id', 'asc');

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

        $sheet->setCellValue('A1', 'REPORT COSTBOOK COSTING');
        $sheet->mergeCells('A1:G2');

        $headers = [
            'A' => '#',
            'B' => 'Kode Costing',
            'C' => 'Kode Product',
            'D' => 'Nama Product',
            'E' => 'Costbook',
            'F' => 'Status',
            'G' => 'Modified At',
        ];
        $rowHeader = 4;
        foreach ($headers as $col => $label) {
            $sheet->setCellValue($col . $rowHeader, $label);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $status_map = ['WA' => 'Waiting Approval', 'A' => 'Approved', 'R' => 'Rejected'];

        $r = $rowHeader + 1;
        $no = 1;
        foreach ($rows as $row) {
            $status_label = isset($status_map[$row->status]) ? $status_map[$row->status] : (string)$row->status;

            $modified_at = (!empty($row->modified_at) && $row->modified_at !== '0000-00-00 00:00:00')
                ? date('d/M/Y H:i', strtotime($row->modified_at))
                : '-';

            $sheet->setCellValue('A' . $r, $no++);
            $sheet->setCellValueExplicit('B' . $r, (string)$row->id, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $r, (string)$row->code_lv4, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $r, (string)$row->product_name, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('E' . $r, (float)$row->harga_beli, PHPExcel_Cell_DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('F' . $r, $status_label, PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G' . $r, $modified_at, PHPExcel_Cell_DataType::TYPE_STRING);
            $r++;
        }

        $sheet->setTitle('Costbook Costing');
        $writer = PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="Costbook_Costing_' . date('Ymd_His') . '.xls"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }
}

/* End of file Costbook_costing.php */
