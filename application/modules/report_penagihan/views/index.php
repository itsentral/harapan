<div class="box box-primary">
    <div class="box-body">
        <div class="row" style="margin-bottom: 15px;">
            <form method="GET" action="<?= site_url('report_penagihan') ?>">
                <div class="col-md-2">
                    <label>Pilih Tahun</label>
                    <select name="tahun" class="form-control input-sm" onchange="this.form.submit()">
                        <?php for ($i = date('Y') - 2; $i <= date('Y') + 1; $i++): ?>
                            <option value="<?= $i ?>" <?= ($tahun_pilih == $i) ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2" style="margin-top: 25px;">
                    <a target="_blank" href="<?= base_url($this->uri->segment(1) . '/export_excel?tahun=' . $tahun_pilih) ?>" class="btn btn-success btn-sm">
                        <i class="fa fa-file-excel-o"></i> Export Excel
                    </a>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="bg-blue">
                        <th class="text-center" style="vertical-align:middle; min-width: 150px;">Nama Sales</th>
                        <th class="text-center" style="vertical-align:middle; min-width: 180px;">Keterangan</th>
                        <?php foreach ($bulan as $b): ?>
                            <th class="text-center" style="min-width: 100px;"><?= substr($b['bulan'], 0, 3) ?></th>
                        <?php endforeach; ?>
                        <th class="text-center">T Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grand_total_target = array_fill(1, 12, 0);
                    $grand_total_realisasi = array_fill(1, 12, 0);

                    foreach ($sales as $s):
                        $row_t_target = 0;
                        $row_t_realisasi = 0;
                    ?>
                        <tr>
                            <td rowspan="2" style="vertical-align:middle; font-weight:bold;"><?= ucwords($s['nm_karyawan']) ?></td>
                            <td>Target Penagihan</td>
                            <?php foreach ($bulan as $b):
                                $bln_no = (int)$b['bulan_no'];
                                if ($tahun_pilih == $tahun_sekarang && $bln_no > $bulan_sekarang):
                            ?>
                                    <td class="text-center text-muted">-</td>
                                <?php else:
                                    $val = $rekap_target[$s['id']][$bln_no] ?? 0;
                                    $row_t_target += $val;
                                    $grand_total_target[$bln_no] += $val;
                                ?>
                                    <td class="text-right">
                                        <a href="<?= site_url('report_penagihan/export_detail?tahun=' . $tahun_pilih . '&bulan=' . $bln_no . '&id_sales=' . $s['id'] . '&tipe=target') ?>" title="Download detail Target Penagihan" style="color:inherit; text-decoration:underline; cursor:pointer;">
                                            <?= number_format($val) ?>
                                        </a>
                                    </td>
                            <?php endif;
                            endforeach; ?>
                            <td class="text-right"><b><?= number_format($row_t_target) ?></b></td>
                        </tr>
                        <tr>
                            <td>Realisasi Tagihan</td>
                            <?php foreach ($bulan as $b):
                                $bln_no = (int)$b['bulan_no'];
                                if ($tahun_pilih == $tahun_sekarang && $bln_no > $bulan_sekarang):
                            ?>
                                    <td class="text-center text-muted">-</td>
                                <?php else:
                                    $val = $rekap_realisasi[$s['id']][$bln_no] ?? 0;
                                    $row_t_realisasi += $val;
                                    $grand_total_realisasi[$bln_no] += $val;
                                ?>
                                    <td class="text-right">
                                        <a href="<?= site_url('report_penagihan/export_detail?tahun=' . $tahun_pilih . '&bulan=' . $bln_no . '&id_sales=' . $s['id'] . '&tipe=realisasi') ?>" title="Download detail Realisasi Tagihan" style="color:inherit; text-decoration:underline; cursor:pointer;">
                                            <?= number_format($val) ?>
                                        </a>
                                        <br>
                                        <button type="button" class="btn btn-xs btn-info btn-komisi" data-id-sales="<?= $s['id'] ?>" data-nama-sales="<?= $s['nm_karyawan'] ?>" data-bulan="<?= $bln_no ?>" data-tahun="<?= $tahun_pilih ?>" title="Hitung Komisi">
                                            <i class="fa fa-calculator"></i> Komisi
                                        </button>
                                    </td>
                            <?php endif;
                            endforeach; ?>
                            <td class="text-right"><b><?= number_format($row_t_realisasi) ?></b></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-info">
                        <td rowspan="2" style="vertical-align:middle; font-weight:bold;">Target Cabang</td>
                        <td>Target Penagihan</td>
                        <?php $total_cabang_t = 0;
                        foreach ($bulan as $b):
                            $bln_no = (int)$b['bulan_no'];
                            if ($tahun_pilih == $tahun_sekarang && $bln_no > $bulan_sekarang):
                        ?>
                                <td class="text-center text-muted">-</td>
                            <?php else:
                                $gt = $grand_total_target[$bln_no];
                                $total_cabang_t += $gt;
                            ?>
                                <td class="text-right"><b><?= number_format($gt) ?></b></td>
                        <?php endif;
                        endforeach; ?>
                        <td class="text-right"><b><?= number_format($total_cabang_t) ?></b></td>
                    </tr>
                    <tr class="bg-info">
                        <td>Realisasi Tagihan</td>
                        <?php $total_cabang_r = 0;
                        foreach ($bulan as $b):
                            $bln_no = (int)$b['bulan_no'];
                            if ($tahun_pilih == $tahun_sekarang && $bln_no > $bulan_sekarang):
                        ?>
                                <td class="text-center text-muted">-</td>
                            <?php else:
                                $gr = $grand_total_realisasi[$bln_no];
                                $total_cabang_r += $gr;
                            ?>
                                <td class="text-right"><b><?= number_format($gr) ?></b></td>
                        <?php endif;
                        endforeach; ?>
                        <td class="text-right"><b><?= number_format($total_cabang_r) ?></b></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Modal Komisi -->
<div class="modal fade" id="modal-komisi" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Form Perhitungan Komisi</h4>
            </div>
            <div class="modal-body" id="modal-komisi-body">
                <p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $(document).on('click', '.btn-komisi', function() {
            var idSales = $(this).data('id-sales');
            var namaSales = $(this).data('nama-sales');
            var bulan = $(this).data('bulan');
            var tahun = $(this).data('tahun');

            $('#modal-komisi-body').html('<p class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</p>');
            $('#modal-komisi').modal('show');

            $.ajax({
                type: 'POST',
                url: siteurl + 'report_penagihan/form_komisi',
                data: {
                    id_sales: idSales,
                    nama_sales: namaSales,
                    bulan: bulan,
                    tahun: tahun
                },
                success: function(data) {
                    $('#modal-komisi-body').html(data);
                },
                error: function() {
                    $('#modal-komisi-body').html('<p class="text-center text-danger">Gagal memuat form.</p>');
                }
            });
        });

        // Save komisi via AJAX
        $(document).on('submit', '#form-komisi-penagihan', function(e) {
            e.preventDefault();
            var formData = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: siteurl + 'master_komisi/save_komisi',
                data: formData,
                dataType: 'json',
                success: function(res) {
                    if (res.status == 1) {
                        swal({
                            title: "Berhasil!",
                            text: res.pesan,
                            type: "success"
                        }, function() {
                            $('#modal-komisi').modal('hide');
                        });
                    } else {
                        swal("Gagal!", res.pesan, "error");
                    }
                },
                error: function() {
                    swal("Error!", "Terjadi kesalahan.", "error");
                }
            });
        });
    });
</script>