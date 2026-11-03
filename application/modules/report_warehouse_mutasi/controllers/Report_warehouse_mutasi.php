<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_warehouse_mutasi extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_Warehouse_Mutasi.View';
    protected $addPermission    = 'Report_Warehouse_Mutasi.Add';
    protected $managePermission = 'Report_Warehouse_Mutasi.Manage';
    protected $deletePermission = 'Report_Warehouse_Mutasi.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Report_warehouse_mutasi/report_warehouse_mutasi_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    /**
     * Report Mutasi Warehouse Stock (ledger perpindahan antar tahap).
     * Menampilkan jejak perpindahan barang: CREATE -> CONFIRM -> INVOICE.
     * Filter: periode tgl_event + event (opsional).
     */
    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');
        $event      = $this->input->get('event');

        $rows = $this->report_warehouse_mutasi_model->get_report(
            $start_date,
            $end_date,
            $event
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'event'      => $event,
        ];

        history("View report mutasi warehouse stock");
        $this->template->title('Report Mutasi Warehouse Stock');
        $this->template->page_icon('fa fa-exchange');
        $this->template->render('report_warehouse_mutasi', $data);
    }

    /**
     * Download Excel report Mutasi Warehouse Stock.
     * Memakai query & kolom yang identik dengan tampilan layar (index).
     */
    public function download_excel()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');
        $event      = $this->input->get('event');

        $rows = $this->report_warehouse_mutasi_model->get_report(
            $start_date,
            $end_date,
            $event
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'event'      => $event,
        ];

        history("Download excel report mutasi warehouse stock");
        $this->load->view('report_warehouse_mutasi_excel', $data);
    }
}
