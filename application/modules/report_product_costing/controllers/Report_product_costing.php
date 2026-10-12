<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_product_costing extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Product_Costing.View';
    protected $addPermission    = 'Report_Product_Costing.Add';
    protected $managePermission = 'Report_Product_Costing.Manage';
    protected $deletePermission = 'Report_Product_Costing.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Report_product_costing/report_product_costing_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    /**
     * Report Product Costing (tampilan layar).
     * Filter: periode tanggal (basis created_at / modified_at) + status.
     */
    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');
        $status     = $this->input->get('status');
        $date_field = $this->input->get('date_field');
        $date_field = ($date_field === 'modified_at') ? 'modified_at' : 'created_at';

        $rows = $this->report_product_costing_model->get_report_product_costing(
            $start_date,
            $end_date,
            $status,
            $date_field
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'status'     => $status,
            'date_field' => $date_field,
        ];

        history("View report product costing");
        $this->template->title('Report Product Costing');
        $this->template->page_icon('fa fa-money');
        $this->template->render('report_product_costing', $data);
    }

    /**
     * Download Excel report Product Costing.
     * Memakai query & kolom yang identik dengan tampilan layar (index).
     */
    public function download_excel()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');
        $status     = $this->input->get('status');
        $date_field = $this->input->get('date_field');
        $date_field = ($date_field === 'modified_at') ? 'modified_at' : 'created_at';

        $rows = $this->report_product_costing_model->get_report_product_costing(
            $start_date,
            $end_date,
            $status,
            $date_field
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'status'     => $status,
            'date_field' => $date_field,
        ];

        history("Download excel report product costing");
        // View ini hanya me-render tabel + header Excel, tanpa template.
        $this->load->view('report_product_costing_excel', $data);
    }
}
