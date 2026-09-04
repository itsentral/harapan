<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Monitoring Kartu Hutang & Piutang
 *
 * Monitoring transaksi pada tabel tr_kartu_hutang dan tr_kartu_piutang.
 * Menampilkan mutasi debet/kredit per rentang tanggal menggunakan
 * DataTables server-side, dengan opsi filter jenis kartu & pencarian.
 */
class Monitoring_kartu extends Admin_Controller
{
    protected $viewPermission   = 'Monitoring_Kartu.View';
    protected $editPermission   = 'Monitoring_Kartu.Edit';
    protected $deletePermission = 'Monitoring_Kartu.Delete';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Monitoring_kartu/Monitoring_kartu_model');
        $this->template->title('Monitoring Kartu Hutang & Piutang');
        $this->template->page_icon('fa fa-credit-card');
        date_default_timezone_set('Asia/Bangkok');
    }

    public function index()
    {
        $this->auth->restrict($this->viewPermission);
        $this->template->render('index');
    }

    /**
     * Endpoint DataTables server-side.
     * Menerima parameter DataTables + jenis, tgl_awal, tgl_akhir via POST.
     */
    public function data()
    {
        $this->auth->restrict($this->viewPermission);
        $this->Monitoring_kartu_model->data_serverside();
    }

    /**
     * AJAX: hapus satu baris kartu (dipindahkan ke tabel _deleted).
     * POST: jenis (hutang|piutang), id
     */
    public function delete()
    {
        $this->auth->restrict($this->deletePermission);

        if (!$this->input->is_ajax_request()) {
            show_error('Akses tidak diizinkan.');
        }

        $jenis = strtolower(trim($this->input->post('jenis')));
        $id    = (int)$this->input->post('id');

        $result = $this->Monitoring_kartu_model->arsip_hapus($jenis, $id);

        echo json_encode($result);
    }

    /**
     * AJAX: ambil detail satu baris kartu untuk form edit.
     * POST: jenis (hutang|piutang), id
     */
    public function get_detail()
    {
        $this->auth->restrict($this->editPermission);

        if (!$this->input->is_ajax_request()) {
            show_error('Akses tidak diizinkan.');
        }

        $jenis = strtolower(trim($this->input->post('jenis')));
        $id    = (int)$this->input->post('id');

        $row = $this->Monitoring_kartu_model->get_row($jenis, $id);

        if (empty($row)) {
            echo json_encode(['status' => false, 'message' => 'Data tidak ditemukan.']);
            return;
        }

        echo json_encode([
            'status' => true,
            'data'   => [
                'id'         => (int)$row['id'],
                'nomor'      => $row['nomor'],
                'tanggal'    => $row['tanggal'],
                'keterangan' => $row['keterangan'],
                'debet'      => (float)$row['debet'],
                'kredit'     => (float)$row['kredit'],
            ],
        ]);
    }

    /**
     * AJAX: update nilai debet & kredit satu baris kartu.
     * POST: jenis (hutang|piutang), id, debet, kredit
     */
    public function update()
    {
        $this->auth->restrict($this->editPermission);

        if (!$this->input->is_ajax_request()) {
            show_error('Akses tidak diizinkan.');
        }

        $jenis  = strtolower(trim($this->input->post('jenis')));
        $id     = (int)$this->input->post('id');
        $debet  = (float)str_replace(['.', ','], ['', '.'], $this->input->post('debet'));
        $kredit = (float)str_replace(['.', ','], ['', '.'], $this->input->post('kredit'));

        $result = $this->Monitoring_kartu_model->update_nilai($jenis, $id, $debet, $kredit);

        echo json_encode($result);
    }

    /**
     * Halaman print / cetak monitoring.
     * GET: jenis, tgl_awal, tgl_akhir, keyword (opsional) via query string.
     */
    public function print_report()
    {
        $this->auth->restrict($this->viewPermission);

        $filter = $this->_get_filter();
        if ($filter === false) {
            show_error('Parameter filter tidak lengkap.');
        }

        $rows   = $this->Monitoring_kartu_model->get_all($filter['jenis'], $filter['tgl_awal'], $filter['tgl_akhir'], $filter['keyword']);
        $totals = $this->Monitoring_kartu_model->get_totals($filter['jenis'], $filter['tgl_awal'], $filter['tgl_akhir'], $filter['keyword']);

        $data = [
            'jenis'        => $filter['jenis'],
            'keyword'      => $filter['keyword'],
            'tgl_awal'     => $filter['tgl_awal'],
            'tgl_akhir'    => $filter['tgl_akhir'],
            'data_report'  => $rows,
            'total_debet'  => $totals['debet'],
            'total_kredit' => $totals['kredit'],
        ];

        $this->load->view('print_report', $data);
    }

    /**
     * Export Excel monitoring.
     * GET: jenis, tgl_awal, tgl_akhir, keyword (opsional) via query string.
     */
    public function export_excel()
    {
        $this->auth->restrict($this->viewPermission);

        $filter = $this->_get_filter();
        if ($filter === false) {
            show_error('Parameter filter tidak lengkap.');
        }

        $jenis       = $filter['jenis'];
        $data_report = $this->Monitoring_kartu_model->get_all($jenis, $filter['tgl_awal'], $filter['tgl_akhir'], $filter['keyword']);
        $totals      = $this->Monitoring_kartu_model->get_totals($jenis, $filter['tgl_awal'], $filter['tgl_akhir'], $filter['keyword']);
        $label       = ($jenis === 'hutang') ? 'HUTANG' : 'PIUTANG';

        $this->load->library('PHPExcel');

        $objPHPExcel = new PHPExcel();
        $sheet       = $objPHPExcel->getActiveSheet();
        $sheet->setTitle('Kartu ' . ucfirst($jenis));

        $style_header = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => '1A5276']],
            'alignment' => ['horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                            'vertical'   => PHPExcel_Style_Alignment::VERTICAL_CENTER],
            'borders'   => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];
        $style_data = [
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];
        $style_total = [
            'font'    => ['bold' => true],
            'fill'    => ['type' => PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'EAF2FB']],
            'borders' => ['allborders' => ['style' => PHPExcel_Style_Border::BORDER_THIN]],
        ];

        // Title
        $sheet->setCellValue('A1', 'MONITORING KARTU ' . $label);
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Periode: ' . date('d F Y', strtotime($filter['tgl_awal'])) . ' s/d ' . date('d F Y', strtotime($filter['tgl_akhir'])));
        $sheet->mergeCells('A2:I2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        // Header kolom
        $headers = ['Tanggal', 'Nomor', 'No Perkiraan', 'No Reff', 'Jenis Trans',
                    ($jenis === 'hutang' ? 'Supplier' : 'Customer'), 'Keterangan', 'Debet', 'Kredit'];
        $cols    = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

        foreach ($headers as $i => $h) {
            $sheet->setCellValue($cols[$i] . '4', $h);
            $sheet->getStyle($cols[$i] . '4')->applyFromArray($style_header);
            $sheet->getColumnDimension($cols[$i])->setAutoSize(true);
        }

        $row = 5;
        foreach ($data_report as $d) {
            $sheet->setCellValue('A' . $row, !empty($d['tanggal']) ? date('d/m/Y', strtotime($d['tanggal'])) : '');
            $sheet->setCellValueExplicit('B' . $row, $d['nomor'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $row, $d['no_perkiraan'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D' . $row, $d['no_reff'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $row, $d['jenis_trans']);
            $sheet->setCellValue('F' . $row, $d['nama']);
            $sheet->setCellValue('G' . $row, $d['keterangan']);
            $sheet->setCellValue('H' . $row, (float)$d['debet']);
            $sheet->setCellValue('I' . $row, (float)$d['kredit']);

            $sheet->getStyle('H' . $row . ':I' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($style_data);
            $row++;
        }

        // Total row
        $sheet->setCellValue('A' . $row, 'TOTAL');
        $sheet->mergeCells('A' . $row . ':G' . $row);
        $sheet->setCellValue('H' . $row, (float)$totals['debet']);
        $sheet->setCellValue('I' . $row, (float)$totals['kredit']);
        $sheet->getStyle('H' . $row . ':I' . $row)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($style_total);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);

        $filename = 'Monitoring_Kartu_' . ucfirst($jenis) . '_' . $filter['tgl_awal'] . '_sd_' . $filter['tgl_akhir'] . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $writer->save('php://output');
        exit;
    }

    /**
     * Validasi & normalisasi parameter filter dari query string (GET).
     *
     * @return array|false
     */
    private function _get_filter()
    {
        $jenis     = strtolower(trim($this->input->get('jenis')));
        $tgl_awal  = trim($this->input->get('tgl_awal'));
        $tgl_akhir = trim($this->input->get('tgl_akhir'));
        $keyword   = trim($this->input->get('keyword'));

        if (!in_array($jenis, ['hutang', 'piutang'], true)) {
            return false;
        }
        if (empty($tgl_awal) || empty($tgl_akhir)) {
            return false;
        }
        if ($tgl_awal > $tgl_akhir) {
            return false;
        }

        return [
            'jenis'     => $jenis,
            'tgl_awal'  => $tgl_awal,
            'tgl_akhir' => $tgl_akhir,
            'keyword'   => $keyword,
        ];
    }
}
