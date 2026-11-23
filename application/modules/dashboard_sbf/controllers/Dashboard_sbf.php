<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Dashboard Penjualan & Piutang.
 * Akses: permission 'Dashboard_Sbf.View' (admin otomatis lolos).
 */
class Dashboard_sbf extends Admin_Controller
{
    protected $viewPermission = 'Dashboard_Sbf.View';

    private $nama_bulan = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'Mei',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Agu',
        9 => 'Sep',
        10 => 'Okt',
        11 => 'Nov',
        12 => 'Des',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Dashboard_sbf/Dashboard_sbf_model');
        $this->template->title('Dashboard Penjualan & Piutang');
        $this->template->page_icon('fa fa-line-chart');
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);

        $this->template->set('bulan_list', $this->nama_bulan);
        $this->template->set('tahun_sekarang', (int) date('Y'));
        $this->template->set('bulan_sekarang', (int) date('n'));
        $this->template->render('index');
    }

    /**
     * AJAX: seluruh data dashboard untuk bulan & tahun terpilih.
     * Data piutang selalu posisi per hari ini; data penjualan mengikuti periode.
     */
    public function get_data()
    {
        $this->auth->restrict($this->viewPermission);

        $bulan = (int) $this->input->post('bulan');
        $tahun = (int) $this->input->post('tahun');
        if ($bulan < 1 || $bulan > 12) {
            $bulan = (int) date('n');
        }
        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) date('Y');
        }

        $today = date('Y-m-d');
        $m     = $this->Dashboard_sbf_model;

        // Rentang 6 bulan berakhir di bulan terpilih
        $sel        = new DateTime(sprintf('%04d-%02d-01', $tahun, $bulan));
        $start      = (clone $sel)->modify('-5 months')->format('Y-m-d');
        $end_excl   = (clone $sel)->modify('+1 month')->format('Y-m-d');
        $key_sel    = $sel->format('Y-m');
        $key_prev   = (clone $sel)->modify('-1 month')->format('Y-m');

        $penjualan = $m->get_penjualan_per_bulan($start, $end_excl);
        $tertagih  = $m->get_tertagih_per_bulan($start, $end_excl);
        $orders    = $m->get_order_per_bulan($start, $end_excl);

        $labels = [];
        $trend_penjualan = [];
        $trend_tertagih  = [];
        for ($i = 5; $i >= 0; $i--) {
            $d        = (clone $sel)->modify("-{$i} months");
            $key      = $d->format('Y-m');
            $labels[] = $this->nama_bulan[(int) $d->format('n')] . ' ' . $d->format('y');
            $trend_penjualan[] = $penjualan[$key] ?? 0;
            $trend_tertagih[]  = $tertagih[$key] ?? 0;
        }

        $jual_now   = $penjualan[$key_sel] ?? 0;
        $jual_prev  = $penjualan[$key_prev] ?? 0;
        $order_now  = $orders[$key_sel] ?? 0;
        $order_prev = $orders[$key_prev] ?? 0;
        $tagih_now  = $tertagih[$key_sel] ?? 0;

        $piutang = $m->get_piutang_summary($today, 10);

        echo json_encode([
            'status'  => true,
            'periode' => $this->nama_bulan[$bulan] . ' ' . $tahun,
            'tanggal' => $today,
            'kpi'     => [
                'penjualan'        => $jual_now,
                'penjualan_prev'   => $jual_prev,
                'order'            => $order_now,
                'order_prev'       => $order_prev,
                'tertagih'         => $tagih_now,
                'collection_rate'  => $jual_now > 0 ? round($tagih_now / $jual_now * 100, 1) : null,
                'total_piutang'    => $piutang['total_piutang'],
                'total_invoice'    => $piutang['total_invoice'],
                'jt_total'         => $piutang['jt_total'],
                'jt_invoice'       => $piutang['jt_invoice'],
                'jt_customer'      => $piutang['jt_customer'],
                'macet_total'      => $piutang['macet_total'],
                'macet_invoice'    => $piutang['macet_invoice'],
            ],
            'trend'   => [
                'labels'    => $labels,
                'penjualan' => $trend_penjualan,
                'tertagih'  => $trend_tertagih,
            ],
            'kategori'   => $m->get_penjualan_kategori($bulan, $tahun, 5),
            'aging'      => $piutang['buckets'],
            'top_sales'  => $m->get_top_sales($bulan, $tahun, 5),
            'jatuh_tempo' => $piutang['jt_rows'],
            'macet'       => $piutang['macet_rows'],
        ]);
    }
}
