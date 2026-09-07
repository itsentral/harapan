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
}
