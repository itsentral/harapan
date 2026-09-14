<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Kpi extends Admin_Controller
{
  //Permission
  protected $viewPermission   = 'KPI.View';
  protected $addPermission    = 'KPI.Add';
  protected $managePermission = 'KPI.Manage';
  protected $deletePermission = 'KPI.Delete';

  protected $id_user;
  protected $datetime;

  public function __construct()
  {
    parent::__construct();
    $this->load->model(array(
      'Kpi/Kpi_model'
    ));
    $this->template->title('Manage Kpi');
    $this->template->page_icon('fa fa-building-o');

    date_default_timezone_set('Asia/Bangkok');

    $this->id_user  = $this->auth->user_id();
    $this->datetime = date('Y-m-d H:i:s');
  }

  public function index()
  {
    $this->auth->restrict($this->viewPermission);

    $this->template->title('Manage KPI');
    $this->template->page_icon('fa fa-cogs');
    $this->template->render('index');
  }

  public function get_list()
  {
    $this->auth->restrict($this->viewPermission);
    $kpi_headers = $this->Kpi_model->get_all();

    $data['kpi_headers'] = $kpi_headers;
    $this->template->render('table/list_kpi', $data);
  }

  public function save_realisasi()
  {
    $this->auth->restrict($this->managePermission);

    $post = $this->input->post();

    if (empty($post['header_id']) || empty($post['realisasi'])) {
      echo json_encode([
        'status' => 'error',
        'message' => 'Data tidak lengkap'
      ]);
      return;
    }

    $header_id = intval($post['header_id']);
    $realisasi_data = $post['realisasi'];

    $header = $this->db->get_where('kpi_headers', ['id' => $header_id])->row();
    if (!$header) {
      echo json_encode([
        'status' => 'error',
        'message' => 'KPI Header tidak ditemukan'
      ]);
      return;
    }

    $current_year = $header->periode;

    $this->db->trans_start();

    $saved_count = 0;
    $updated_count = 0;
    $deleted_count = 0;

    $month_map = [
      'Jan' => '01',
      'Feb' => '02',
      'Mar' => '03',
      'Apr' => '04',
      'Mei' => '05',
      'Jun' => '06',
      'Jul' => '07',
      'Agu' => '08',
      'Sep' => '09',
      'Okt' => '10',
      'Nov' => '11',
      'Des' => '12'
    ];

    $total_filled_months = 0;
    $total_expected_months = 0;
    $items_calculation = [];

    foreach ($realisasi_data as $kpi_item_id => $months) {
      $item = $this->db->get_where('kpi_items', ['id' => $kpi_item_id])->row();

      if (!$item) {
        continue;
      }

      $monthly_values = [];
      $filled_months_count = 0;

      foreach ($months as $month_name => $value) {
        $month_num = isset($month_map[$month_name]) ? $month_map[$month_name] : '01';
        $periode = $current_year . '-' . $month_num . '-01';

        $is_empty = ($value === '' || $value === null);

        $existing = $this->db->get_where('kpi_realisations', [
          'kpi_item_id' => $kpi_item_id,
          'periode' => $periode
        ])->row();

        if ($is_empty) {
          if ($existing) {
            $this->db->where('id', $existing->id);
            $this->db->delete('kpi_realisations');
            $deleted_count++;
          }
        } else {
          $value = floatval($value);
          $monthly_values[] = $value;
          $filled_months_count++;

          $status_result = $this->calculate_status($kpi_item_id, $value);

          $data = [
            'kpi_item_id' => $kpi_item_id,
            'periode'     => $periode,
            'value'       => $value,
            'status_code' => $status_result['status_code']
          ];

          if ($existing) {
            $this->db->where('id', $existing->id);
            $this->db->update('kpi_realisations', $data);
            $updated_count++;
          } else {
            $this->db->insert('kpi_realisations', $data);
            $saved_count++;
          }
        }
      }

      $skor_realisasi = 0;
      if (!empty($monthly_values)) {
        $skor_realisasi = $this->calculate_final_score($monthly_values, $item->sistem_penilaian);
      }

      $skor_pencapaian = 0;
      if ($skor_realisasi > 0 && $item->target > 0) {
        $adjusted_target = $item->target;

        if ($item->sistem_penilaian == 1 && $filled_months_count > 0) {
          $adjusted_target = $item->target * $filled_months_count;
        }

        $skor_pencapaian = ($skor_realisasi / $adjusted_target) * 100;
      }

      $skor_akhir = 0;
      if ($skor_pencapaian > 0 && $item->bobot > 0) {
        $skor_akhir = ($skor_pencapaian * $item->bobot) / 100;
      }

      $this->db->where('id', $kpi_item_id);
      $this->db->update('kpi_items', [
        'skor_realisasi' => $skor_realisasi,
        'skor_pencapaian' => $skor_pencapaian,
        'skor_akhir' => $skor_akhir
      ]);

      $items_calculation[] = [
        'skor_pencapaian' => $skor_pencapaian,
        'bobot' => $item->bobot
      ];

      $total_filled_months += $filled_months_count;
      $total_expected_months += 12;
    }

    $skor_kpi = 0;
    $total_items_with_score = 0;

    if (!empty($items_calculation)) {
      $is_bobot = $header->is_bobot;

      if ($is_bobot == 1) {
        foreach ($items_calculation as $calc) {
          if ($calc['skor_pencapaian'] > 0) {
            $skor_kpi += ($calc['skor_pencapaian'] * $calc['bobot']) / 100;
          }
        }
      } else {
        $total_skor_pencapaian = 0;
        foreach ($items_calculation as $calc) {
          if ($calc['skor_pencapaian'] > 0) {
            $total_skor_pencapaian += $calc['skor_pencapaian'];
            $total_items_with_score++;
          }
        }

        if ($total_items_with_score > 0) {
          $skor_kpi = $total_skor_pencapaian / $total_items_with_score;
        }
      }
    }

    $new_is_realisasi = 0;

    if ($total_filled_months == 0) {
      // Tidak ada data realisasi sama sekali
      $new_is_realisasi = 0;
    } else if ($total_filled_months == $total_expected_months) {
      // Semua data lengkap (semua item sudah terisi 12 bulan)
      $new_is_realisasi = 2;
    } else {
      // Ada data tapi belum lengkap
      $new_is_realisasi = 1;
    }

    $this->db->where('id', $header_id);
    $this->db->update('kpi_headers', [
      'is_realisasi' => $new_is_realisasi,
      'skor_kpi' => $skor_kpi
    ]);

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE) {
      echo json_encode([
        'status' => 'error',
        'message' => 'Gagal menyimpan data realisasi'
      ]);
      return;
    }

    $total = $saved_count + $updated_count;
    $message = "Berhasil menyimpan data realisasi";

    $details = [];
    if ($saved_count > 0) $details[] = "{$saved_count} data baru";
    if ($updated_count > 0) $details[] = "{$updated_count} data diupdate";
    if ($deleted_count > 0) $details[] = "{$deleted_count} data dihapus";

    if (!empty($details)) {
      $message .= " (" . implode(", ", $details) . ")";
    }

    $proses_status = '';
    if ($new_is_realisasi == 0) {
      $proses_status = "Belum ada realisasi";
    } elseif ($new_is_realisasi == 1) {
      $proses_status = "Berhasil proses realisasi ({$total_filled_months}/{$total_expected_months} bulan terisi) - Skor KPI: " . number_format($skor_kpi, 2);
    } elseif ($new_is_realisasi == 2) {
      $proses_status = "Proses Realisasi lengkap (12 bulan penuh) - Skor KPI: " . number_format($skor_kpi, 2);
    }

    echo json_encode([
      'status' => 'success',
      'message' => $message,
      'status_info' => $proses_status,
      'data' => [
        'saved' => $saved_count,
        'updated' => $updated_count,
        'deleted' => $deleted_count,
        'total' => $total,
        'is_realisasi' => $new_is_realisasi,
        'filled_months' => $total_filled_months,
        'expected_months' => $total_expected_months,
        'skor_kpi' => $skor_kpi
      ]
    ]);
  }

  private function calculate_final_score($values, $sistem_penilaian)
  {
    if (empty($values)) {
      return 0;
    }

    switch ($sistem_penilaian) {
      case 0: // Rata-rata
        return array_sum($values) / count($values);

      case 1: // Akumulatif
        return array_sum($values);

      case 2: // Angka terakhir
        return end($values);

      default:
        return 0;
    }
  }

  private function calculate_status($kpi_item_id, $value)
  {
    $thresholds = $this->db->get_where('kpi_thresholds', [
      'kpi_item_id' => $kpi_item_id
    ])->result();

    if (empty($thresholds)) {
      return [
        'status_code' => null,
        'status_label' => 'Tidak Ada Threshold'
      ];
    }

    foreach ($thresholds as $threshold) {
      $min = floatval($threshold->min_value);
      $max = $threshold->max_value !== null ? floatval($threshold->max_value) : PHP_FLOAT_MAX;

      if ($value >= $min && $value <= $max) {
        $status_labels = [
          0 => 'Merah',
          1 => 'Kuning',
          2 => 'Hijau'
        ];

        return [
          'status_code' => $threshold->status_code,
          'status_label' => $status_labels[$threshold->status_code] ?? 'Unknown'
        ];
      }
    }

    return [
      'status_code' => null,
      'status_label' => 'Di Luar Range'
    ];
  }

  public function add()
  {
    $this->auth->restrict($this->addPermission);
    $data = [
      'mode'      => 'add',
      'divisions' => $this->Kpi_model->get_divisions(),
      'employees' => $this->Kpi_model->get_active_employees(),
    ];

    $this->template->page_icon('fa fa-bar-chart');
    $this->template->title('Add New KPI');
    $this->template->render('form', $data);
  }

  public function save()
  {
    $this->auth->restrict($this->addPermission);

    $post = $this->input->post();

    if (empty($post['divisi'])) {
      echo json_encode(['status' => 'error', 'message' => 'Divisi harus dipilih']);
      return;
    }

    if (empty($post['item']) || count($post['item']) === 0) {
      echo json_encode(['status' => 'error', 'message' => 'Minimal harus ada 1 item KPI']);
      return;
    }

    if ($post['bobot_enabled'] == 1 && !empty($post['bobot'])) {
      $total_bobot = 0;
      foreach ($post['bobot'] as $bobot) {
        if (!empty($bobot)) {
          $total_bobot += floatval($bobot);
        }
      }

      if (abs($total_bobot - 100) > 0.01) {
        echo json_encode([
          'status' => 'error',
          'message' => "Total bobot harus 100%. Saat ini: " . number_format($total_bobot, 2) . "%"
        ]);
        return;
      }
    }

    $this->db->trans_start();

    $divisi_id = $post['divisi'];
    $divisi_name = !empty($post['divisi_name']) ? $post['divisi_name'] : '';

    if (empty($divisi_name)) {
      $divisi = $this->db->select('name')->get_where('divisions', ['id' => $divisi_id])->row();
      $divisi_name = $divisi ? $divisi->name : '';
    }

    $header_data = [
      'divisi_id'   => $divisi_id,
      'divisi_name' => $divisi_name,
      'is_bobot'    => $post['bobot_enabled'],
      'periode'     => date('Y'),
      'create_date' => date('Y-m-d H:i:s'),
      'create_by'   => $this->auth->nama(),
    ];
    $this->db->insert('kpi_headers', $header_data);
    $header_id = $this->db->insert_id();

    if (!empty($post['item'])) {
      foreach ($post['item'] as $i => $item) {
        if (empty($item)) continue;

        $pic_id = $post['pic_id'][$i];
        $pic = $this->db->select('nm_karyawan')->get_where('employee', ['id' => $pic_id])->row();
        $pic_name = $pic ? $pic->nm_karyawan : '';

        $bobot_value = 0;
        if ($post['bobot_enabled'] == 1 && isset($post['bobot'][$i])) {
          $bobot_value = floatval($post['bobot'][$i]);
        }

        $item_data = [
          'header_id'           => $header_id,
          'item'                => $post['item'][$i],
          'indikator'           => $post['indikator'][$i],
          'pic_id'              => $pic_id,
          'pic_name'            => $pic_name,
          'target'              => floatval($post['target'][$i]),
          'satuan'              => $post['satuan'][$i],
          'formula_description' => $post['formula'][$i],
          'sistem_penilaian'    => intval($post['sistem_penilaian'][$i]),
          'bobot'               => $bobot_value,
          'status'              => intval($post['threshold_type'][$i])
        ];
        $this->db->insert('kpi_items', $item_data);
        $item_id = $this->db->insert_id();

        $merah  = isset($post['merah'][$i]) && $post['merah'][$i] !== '' ? floatval($post['merah'][$i]) : null;
        $kuning = isset($post['kuning'][$i]) && $post['kuning'][$i] !== '' ? floatval($post['kuning'][$i]) : null;
        $hijau  = isset($post['hijau'][$i]) && $post['hijau'][$i] !== '' ? floatval($post['hijau'][$i]) : null;
        $threshold_type = isset($post['threshold_type'][$i]) ? $post['threshold_type'][$i] : '1';

        if ($threshold_type === '1') {
          if ($merah !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => 0,
              'max_value'   => $merah,
              'status_code' => 0
            ]);
          }

          if ($merah !== null && $kuning !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => $merah,
              'max_value'   => $kuning,
              'status_code' => 1
            ]);
          }

          if ($kuning !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => $kuning,
              'max_value'   => null,
              'status_code' => 2
            ]);
          }
        } else {
          if ($hijau !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => 0,
              'max_value'   => $hijau,
              'status_code' => 2
            ]);
          }

          if ($hijau !== null && $kuning !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => $hijau,
              'max_value'   => $kuning,
              'status_code' => 1
            ]);
          }

          if ($kuning !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => $kuning,
              'max_value'   => null,
              'status_code' => 0
            ]);
          }
        }
      }
    }

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE) {
      echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data KPI']);
    } else {
      echo json_encode(['status' => 'success', 'message' => 'KPI berhasil disimpan']);
    }
  }

  public function edit($id)
  {
    $this->auth->restrict($this->managePermission);

    $data = $this->prepareKpiData($id);
    if (!$data) {
      $this->session->set_flashdata('error', 'Data KPI tidak ditemukan');
      redirect('kpi');
    }

    $data['mode'] = 'edit';
    $this->template->title('Edit KPI');
    history("Edit KPI ID: $id");
    $this->template->render('form', $data);
  }

  private function prepareKpiData($id)
  {
    $header = $this->Kpi_model->get_header($id);
    if (!$header) return false;

    $items = $this->Kpi_model->get_items_with_thresholds($id);

    foreach ($items as &$item) {
      $threshold_data = $this->parse_thresholds($item->thresholds);

      $item->threshold_type = $item->status;
      $item->merah_value   = $threshold_data['merah'];
      $item->kuning_value  = $threshold_data['kuning'];
      $item->hijau_value   = $threshold_data['hijau'];
    }

    return [
      'header'    => $header,
      'items'     => $items,
      'divisions' => $this->Kpi_model->get_divisions(),
      'employees' => $this->Kpi_model->get_active_employees(),
    ];
  }

  public function view($id)
  {
    $this->auth->restrict($this->viewPermission);

    $data = $this->prepareKpiData($id);
    if (!$data) {
      $this->session->set_flashdata('error', 'Data KPI tidak ditemukan');
      redirect('kpi');
    }

    $data['mode'] = 'view';
    $this->template->title('View KPI');
    $this->template->render('form', $data);
  }

  private function parse_thresholds($thresholds)
  {
    $result = [
      'merah' => 0,
      'kuning' => 0,
      'hijau' => 0
    ];

    if (empty($thresholds)) {
      return $result;
    }

    foreach ($thresholds as $t) {
      if ($t->status_code == 0 && $t->max_value !== null) {
        $result['merah'] = $t->max_value;
      }
      if ($t->status_code == 1 && $t->max_value !== null) {
        $result['kuning'] = $t->max_value;
      }
      if ($t->status_code == 2 && $t->min_value !== null) {
        $result['hijau'] = $t->min_value;
      }
    }

    return $result;
  }

  public function update()
  {
    $this->auth->restrict($this->managePermission);

    $post = $this->input->post();
    $header_id = intval($post['header_id']);

    if (empty($post['divisi'])) {
      echo json_encode(['status' => 'error', 'message' => 'Divisi harus dipilih']);
      return;
    }

    if (empty($post['item']) || count($post['item']) === 0) {
      echo json_encode(['status' => 'error', 'message' => 'Minimal harus ada 1 item KPI']);
      return;
    }

    if ($post['bobot_enabled'] == 1 && !empty($post['bobot'])) {
      $total_bobot = 0;
      foreach ($post['bobot'] as $bobot) {
        if (!empty($bobot)) {
          $total_bobot += floatval($bobot);
        }
      }

      if (abs($total_bobot - 100) > 0.01) {
        echo json_encode([
          'status' => 'error',
          'message' => "Total bobot harus 100%. Saat ini: " . number_format($total_bobot, 2) . "%"
        ]);
        return;
      }
    }

    $this->db->trans_start();

    $divisi_id = $post['divisi'];
    $divisi_name = !empty($post['divisi_name']) ? $post['divisi_name'] : '';

    if (empty($divisi_name)) {
      $divisi = $this->db->select('name')->get_where('divisions', ['id' => $divisi_id])->row();
      $divisi_name = $divisi ? $divisi->name : '';
    }

    $header_data = [
      'divisi_id'   => $divisi_id,
      'divisi_name' => $divisi_name,
      'is_bobot'    => $post['bobot_enabled'],
      'periode'     => date('Y'),
      'update_date' => date('Y-m-d H:i:s'),
      'update_by'   => $this->auth->nama(),
    ];
    $this->db->where('id', $header_id);
    $this->db->update('kpi_headers', $header_data);

    $old_items = $this->db->select('id')->get_where('kpi_items', ['header_id' => $header_id])->result();
    foreach ($old_items as $old_item) {
      $this->db->delete('kpi_thresholds', ['kpi_item_id' => $old_item->id]);
    }
    $this->db->delete('kpi_items', ['header_id' => $header_id]);

    if (!empty($post['item'])) {
      foreach ($post['item'] as $i => $item) {
        if (empty($item)) continue;

        $pic_id = $post['pic_id'][$i];
        $pic = $this->db->select('nm_karyawan')->get_where('employee', ['id' => $pic_id])->row();
        $pic_name = $pic ? $pic->nm_karyawan : '';

        $bobot_value = 0;
        if ($post['bobot_enabled'] == 1 && isset($post['bobot'][$i])) {
          $bobot_value = floatval($post['bobot'][$i]);
        }

        $item_data = [
          'header_id'           => $header_id,
          'item'                => $post['item'][$i],
          'indikator'           => $post['indikator'][$i],
          'pic_id'              => $pic_id,
          'pic_name'            => $pic_name,
          'target'              => floatval($post['target'][$i]),
          'satuan'              => $post['satuan'][$i],
          'formula_description' => $post['formula'][$i],
          'sistem_penilaian'    => intval($post['sistem_penilaian'][$i]),
          'bobot'               => $bobot_value,
          'status'              => intval($post['threshold_type'][$i])
        ];
        $this->db->insert('kpi_items', $item_data);
        $item_id = $this->db->insert_id();

        $merah  = isset($post['merah'][$i]) && $post['merah'][$i] !== '' ? floatval($post['merah'][$i]) : null;
        $kuning = isset($post['kuning'][$i]) && $post['kuning'][$i] !== '' ? floatval($post['kuning'][$i]) : null;
        $hijau  = isset($post['hijau'][$i]) && $post['hijau'][$i] !== '' ? floatval($post['hijau'][$i]) : null;
        $threshold_type = isset($post['threshold_type'][$i]) ? $post['threshold_type'][$i] : '1';

        if ($threshold_type === '1') {
          if ($merah !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => 0,
              'max_value'   => $merah,
              'status_code' => 0
            ]);
          }

          if ($merah !== null && $kuning !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => $merah,
              'max_value'   => $kuning,
              'status_code' => 1
            ]);
          }

          if ($kuning !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => $kuning,
              'max_value'   => null,
              'status_code' => 2
            ]);
          }
        } else {
          if ($hijau !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => 0,
              'max_value'   => $hijau,
              'status_code' => 2
            ]);
          }

          if ($hijau !== null && $kuning !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => $hijau,
              'max_value'   => $kuning,
              'status_code' => 1
            ]);
          }

          if ($kuning !== null) {
            $this->db->insert('kpi_thresholds', [
              'kpi_item_id' => $item_id,
              'min_value'   => $kuning,
              'max_value'   => null,
              'status_code' => 0
            ]);
          }
        }
      }
    }

    $this->db->trans_complete();

    if ($this->db->trans_status() === FALSE) {
      echo json_encode(['status' => 'error', 'message' => 'Gagal mengupdate data KPI']);
    } else {
      echo json_encode(['status' => 'success', 'message' => 'KPI berhasil diupdate']);
    }
  }

  public function delete($id = null)
  {
    $this->auth->restrict($this->deletePermission);

    $response = ['status' => 'error', 'message' => 'Terjadi kesalahan.'];

    if (!$id || !is_numeric($id)) {
      $response['message'] = 'ID tidak valid.';
      echo json_encode($response);
      exit;
    }

    if ($this->Kpi_model->delete_header($id)) {
      $response = [
        'status'  => 'success',
        'message' => 'KPI berhasil dihapus!',
        'submessage' => 'Data telah dihapus.'
      ];
    } else {
      $response['message'] = 'Gagal menghapus data KPI.';
    }

    echo json_encode($response);
    exit;
  }

  public function realisasi($header_id)
  {
    $this->auth->restrict($this->managePermission);

    $header = $this->Kpi_model->get_header_by_id($header_id);
    if (!$header) {
      show_404();
    }

    $is_closed = ($header->is_close == 1);
    $items = $this->Kpi_model->get_items_by_header($header_id);

    $item_ids = array_column($items, 'id');
    $thresholds_raw = [];
    if (!empty($item_ids)) {
      $this->db->select('kpi_item_id, min_value, max_value, status_code');
      $this->db->where_in('kpi_item_id', $item_ids);
      $this->db->order_by('status_code', 'ASC');
      $thresholds_raw = $this->db->get('kpi_thresholds')->result();
    }

    $thresholds = [];
    foreach ($thresholds_raw as $t) {
      if (!isset($thresholds[$t->kpi_item_id])) {
        $thresholds[$t->kpi_item_id] = [];
      }

      $status_name = '';
      if ($t->status_code == 0) $status_name = 'merah';
      elseif ($t->status_code == 1) $status_name = 'kuning';
      elseif ($t->status_code == 2) $status_name = 'hijau';

      if ($status_name) {
        $thresholds[$t->kpi_item_id][$status_name] = [
          'min' => $t->min_value !== null ? (float)$t->min_value : 0,
          'max' => $t->max_value !== null ? (float)$t->max_value : null
        ];
      }
    }

    $periode_year = $header->periode;
    $realisations = [];
    foreach ($items as $item) {
      $realisations[$item->id] = $this->Kpi_model->get_realisations_by_item($item->id);
    }

    foreach ($items as $item) {
      $item->target_display = $this->format_value($item->target, $item->satuan);
      $item->threshold_json = json_encode(
        isset($thresholds[$item->id]) ? $thresholds[$item->id] : [
          'merah' => ['min' => 0, 'max' => null],
          'kuning' => ['min' => 0, 'max' => null],
          'hijau' => ['min' => 0, 'max' => null]
        ]
      );
    }

    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $months_with_year = [];
    foreach ($months as $month) {
      $months_with_year[] = $month . ' ' . $periode_year;
    }

    $data = [
      'header'           => $header,
      'items'            => $items,
      'realisations'     => $realisations,
      'thresholds'       => $thresholds,
      'months'           => $months,
      'months_display'   => $months_with_year,
      'periode_year'     => $periode_year,
      'is_closed'        => $is_closed
    ];

    $this->template->render('kpi/realisasi_form', $data);
  }

  private function format_value($val, $satuan)
  {
    if ($satuan === 'nominal') {
      return 'Rp ' . number_format($val, 0, ',', '.');
    } elseif ($satuan === 'persen') {
      return number_format($val, 2, ',', '.') . '%';
    } else {
      return number_format($val, 0, ',', '.');
    }
  }

  public function close_period()
  {
    $this->auth->restrict($this->managePermission);

    $header_id = $this->input->post('header_id');

    if (!$header_id || !is_numeric($header_id)) {
      echo json_encode(['status' => 'error', 'message' => 'ID header tidak valid.']);
      return;
    }

    $header = $this->Kpi_model->get_header($header_id);
    if (!$header) {
      echo json_encode(['status' => 'error', 'message' => 'Data KPI tidak ditemukan.']);
      return;
    }

    if ($header->is_close == 1) {
      echo json_encode(['status' => 'error', 'message' => 'Periode KPI ini sudah ditutup sebelumnya.']);
      return;
    }

    $update = $this->db->where('id', $header_id)
      ->update('kpi_headers', [
        'is_close'   => 1,
        'close_by'   => $this->auth->nama(),
        'close_date' => date('Y-m-d H:i:s')
      ]);


    if ($update) {
      echo json_encode([
        'status'  => 'success',
        'message' => 'Periode KPI tahun ' . $header->periode . ' untuk divisi ' . $header->divisi_name . ' berhasil ditutup.'
      ]);
    } else {
      echo json_encode(['status' => 'error', 'message' => 'Gagal menutup periode. Silakan coba lagi.']);
    }
  }
}
