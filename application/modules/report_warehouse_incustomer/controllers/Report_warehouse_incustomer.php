<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_warehouse_incustomer extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Warehouse_Incustomer.View';
    protected $addPermission    = 'Report_Warehouse_Incustomer.Add';
    protected $managePermission = 'Report_Warehouse_Incustomer.Manage';
    protected $deletePermission = 'Report_Warehouse_Incustomer.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Report_warehouse_incustomer/report_warehouse_incustomer_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    /**
     * Report Warehouse Stock In-Customer (tampilan layar).
     * Menampilkan barang yang Surat Jalannya sudah di-confirm (diterima customer)
     * tetapi belum di-invoice (masih berada di warehouse_stock_incustomer).
     * Filter: periode tgl_masuk.
     */
    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');

        $rows = $this->report_warehouse_incustomer_model->get_report(
            $start_date,
            $end_date
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ];

        history("View report warehouse in-customer");
        $this->template->title('Report Warehouse In-Customer');
        $this->template->page_icon('fa fa-user');
        $this->template->render('report_warehouse_incustomer', $data);
    }

    /**
     * Download Excel report Warehouse In-Customer.
     * Memakai query & kolom yang identik dengan tampilan layar (index).
     */
    public function download_excel()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');

        $rows = $this->report_warehouse_incustomer_model->get_report(
            $start_date,
            $end_date
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ];

        history("Download excel report warehouse in-customer");
        $this->load->view('report_warehouse_incustomer_excel', $data);
    }
}
