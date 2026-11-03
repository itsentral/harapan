<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_warehouse_intransit extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Warehouse_Intransit.View';
    protected $addPermission    = 'Report_Warehouse_Intransit.Add';
    protected $managePermission = 'Report_Warehouse_Intransit.Manage';
    protected $deletePermission = 'Report_Warehouse_Intransit.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Report_warehouse_intransit/report_warehouse_intransit_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    /**
     * Report Warehouse Stock In-Transit (tampilan layar).
     * Menampilkan barang yang sudah dibuat Surat Jalan tetapi belum di-confirm
     * (masih berada di warehouse_stock_intransit). Filter: periode tgl_masuk.
     */
    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');

        $rows = $this->report_warehouse_intransit_model->get_report(
            $start_date,
            $end_date
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ];

        history("View report warehouse in-transit");
        $this->template->title('Report Warehouse In-Transit');
        $this->template->page_icon('fa fa-truck');
        $this->template->render('report_warehouse_intransit', $data);
    }

    /**
     * Download Excel report Warehouse In-Transit.
     * Memakai query & kolom yang identik dengan tampilan layar (index).
     */
    public function download_excel()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');

        $rows = $this->report_warehouse_intransit_model->get_report(
            $start_date,
            $end_date
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ];

        history("Download excel report warehouse in-transit");
        $this->load->view('report_warehouse_intransit_excel', $data);
    }
}
