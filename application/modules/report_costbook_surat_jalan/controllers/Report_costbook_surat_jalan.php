<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_costbook_surat_jalan extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Costbook_Surat_Jalan.View';
    protected $addPermission    = 'Report_Costbook_Surat_Jalan.Add';
    protected $managePermission = 'Report_Costbook_Surat_Jalan.Manage';
    protected $deletePermission = 'Report_Costbook_Surat_Jalan.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Report_costbook_surat_jalan/report_costbook_surat_jalan_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    /**
     * Report Costbook Surat Jalan (tampilan layar).
     * Hanya menampilkan SJ yang sudah dibuat tapi belum confirm
     * (status = 'ON DELIVER'). Filter: periode delivery_date (dari & sampai).
     */
    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');

        $rows = $this->report_costbook_surat_jalan_model->get_report_costbook_surat_jalan(
            $start_date,
            $end_date
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ];

        history("View report costbook surat jalan");
        $this->template->title('Report Costbook Surat Jalan');
        $this->template->page_icon('fa fa-truck');
        $this->template->render('report_costbook_surat_jalan', $data);
    }

    /**
     * Download Excel report Costbook Surat Jalan.
     * Memakai query & kolom yang identik dengan tampilan layar (index).
     */
    public function download_excel()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');

        $rows = $this->report_costbook_surat_jalan_model->get_report_costbook_surat_jalan(
            $start_date,
            $end_date
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ];

        history("Download excel report costbook surat jalan");
        // View ini hanya me-render tabel + header Excel, tanpa template.
        $this->load->view('report_costbook_surat_jalan_excel', $data);
    }
}
