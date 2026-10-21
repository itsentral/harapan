<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Report_hpp_detail extends Admin_Controller
{
    //Permission
    protected $viewPermission   = 'Report_HPP_Detail.View';
    protected $addPermission    = 'Report_HPP_Detail.Add';
    protected $managePermission = 'Report_HPP_Detail.Manage';
    protected $deletePermission = 'Report_HPP_Detail.Delete';

    public function __construct()
    {
        parent::__construct();

        $this->load->model(array(
            'Report_hpp_detail/report_hpp_detail_model'
        ));
        date_default_timezone_set('Asia/Bangkok');
    }

    /**
     * Report HPP Detail (tampilan layar).
     * Menampilkan HPP (costbook) tiap produk per invoice.
     * Filter: periode tanggal (dari & sampai) dengan basis Tgl Dibuat atau Tgl Kirim.
     */
    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');
        $date_basis = $this->input->get('date_basis');
        if ($date_basis !== 'delivery') {
            $date_basis = 'created';
        }

        $rows = $this->report_hpp_detail_model->get_report_hpp_detail(
            $start_date,
            $end_date,
            $date_basis
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'date_basis' => $date_basis,
        ];

        history("View report HPP detail");
        $this->template->title('Report HPP Detail');
        $this->template->page_icon('fa fa-cubes');
        $this->template->render('report_hpp_detail', $data);
    }

    /**
     * Download Excel Report HPP Detail.
     * Memakai query & kolom yang identik dengan tampilan layar (index).
     */
    public function download_excel()
    {
        $this->auth->restrict($this->viewPermission);

        $start_date = $this->input->get('start_date');
        $end_date   = $this->input->get('end_date');
        $date_basis = $this->input->get('date_basis');
        if ($date_basis !== 'delivery') {
            $date_basis = 'created';
        }

        $rows = $this->report_hpp_detail_model->get_report_hpp_detail(
            $start_date,
            $end_date,
            $date_basis
        );

        $data = [
            'rows'       => $rows,
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'date_basis' => $date_basis,
        ];

        history("Download excel report HPP detail");
        // View ini hanya me-render tabel + header Excel, tanpa template.
        $this->load->view('report_hpp_detail_excel', $data);
    }
}
