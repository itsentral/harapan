<div class="box box-primary">
    <div class="box-body">
        <div class="row" style="margin-bottom: 10px;">
            <div class="col-md-2">
                <label>Pilih Bulan</label>
                <select id="filter_bulan" class="form-control input-sm">
                    <?php foreach ($bulan_list as $b): ?>
                        <option value="<?= $b['bulan_no'] ?>" <?= ($bulan == $b['bulan_no']) ? 'selected' : '' ?>><?= $b['bulan'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>Pilih Tahun</label>
                <select id="filter_tahun" class="form-control input-sm">
                    <?php
                    $thn_skrg = date('Y');
                    for ($i = $thn_skrg - 1; $i <= $thn_skrg + 1; $i++):
                    ?>
                        <option value="<?= $i ?>" <?= ($tahun == $i) ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-8 text-right" style="padding-top: 25px;">
                <a target="_blank" href="<?= base_url('report_margin_achievement/export_excel?bulan=' . $bulan . '&tahun=' . $tahun) ?>" class="btn btn-success btn-sm">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-condensed">
                <thead>
                    <tr class="bg-primary">
                        <th class="text-center" style="vertical-align:middle;">No</th>
                        <th class="text-center" style="vertical-align:middle; min-width: 150px;">Nama Sales</th>
                        <th class="text-center" style="vertical-align:middle; min-width: 130px;">Target Omset (Rp)</th>
                        <th class="text-center" style="vertical-align:middle; min-width: 130px;">Realisasi Omset (Rp)</th>
                        <th class="text-center" style="vertical-align:middle;">% Ach Omset</th>
                        <th class="text-center" style="vertical-align:middle; min-width: 120px;">DPP</th>
                        <th class="text-center" style="vertical-align:middle; min-width: 130px;">Realisasi Margin (Rp)</th>
                        <th class="text-center" style="vertical-align:middle;">% Ach Margin</th>
                        <th class="text-center" style="vertical-align:middle; min-width: 110px;">Actual Margin (%)</th>
                        <th class="text-center" style="vertical-align:middle;">Target Margin %</th>
                        <th class="text-center" style="vertical-align:middle; min-width: 130px;">Status Achievement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1;
                    foreach ($rows as $row): ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td><?= $row['nama_sales'] ?></td>
                            <td class="text-right"><?= number_format($row['target_omset']) ?></td>
                            <td class="text-right"><?= number_format($row['realisasi_omset']) ?></td>
                            <td class="text-center"><?= number_format($row['pct_ach_omset'] * 100, 1) ?>%</td>
                            <td class="text-right"><?= number_format($row['dpp']) ?></td>
                            <td class="text-right"><?= number_format($row['realisasi_margin_rp']) ?></td>
                            <td class="text-center"><?= number_format($row['pct_ach_margin'] * 100, 1) ?>%</td>
                            <td class="text-center"><?= number_format($row['margin_pct_thd_omset'] * 100, 1) ?>%</td>
                            <td class="text-center"><?= number_format($row['target_margin_pct'], 1) ?>%</td>
                            <td class="text-center">
                                <?php
                                $badge = 'label-danger';
                                if ($row['status'] == 'Tercapai') $badge = 'label-success';
                                elseif ($row['status'] == 'Mendekati Target') $badge = 'label-warning';
                                ?>
                                <span class="label <?= $badge ?>"><?= $row['status'] ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="11" class="text-center">Tidak ada data sales.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="bg-gray">
                        <th colspan="2" class="text-right">TOTAL</th>
                        <th class="text-right"><?= number_format($totals['target_omset']) ?></th>
                        <th class="text-right"><?= number_format($totals['realisasi_omset']) ?></th>
                        <th class="text-center"><?= number_format($totals['pct_ach_omset'] * 100, 1) ?>%</th>
                        <th class="text-right"><?= number_format($totals['dpp']) ?></th>
                        <th class="text-right"><?= number_format($totals['realisasi_margin_rp']) ?></th>
                        <th class="text-center"><?= number_format($totals['pct_ach_margin'] * 100, 1) ?>%</th>
                        <th class="text-center"><?= number_format($totals['margin_pct_thd_omset'] * 100, 1) ?>%</th>
                        <th></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div style="margin-top: 15px; font-size: 12px; color: #666;">
            <strong>Keterangan:</strong>
            <ol style="padding-left: 18px;">
                <li>Target Omset dan Realisasi Omset diambil dari Report Penjualan per Sales.</li>
                <li>% Ach Omset = Realisasi Omset / Target Omset.</li>
                <li>DPP = Realisasi Omset (Rp) / 1,11.</li>
                <li>Target Margin % diambil dari Master Target Margin per Sales.</li>
                <li>Actual Margin (%) = (Realisasi Omset (Revenue) - HPP (Harga Pokok Penjualan/COGS)) aktual per baris invoice / Realisasi Omset.</li>
                <li>Realisasi Margin (Rp) = DPP x Actual Margin (%).</li>
                <li>% Ach Margin = Actual Margin (%) / Target Margin %.</li>
                <li>Status: &gt;=100% Tercapai, 90-99,9% Mendekati Target, &lt;90% Belum Tercapai.</li>
            </ol>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        function reload() {
            var bln = $('#filter_bulan').val();
            var thn = $('#filter_tahun').val();
            window.location.href = siteurl + active_controller + '?bulan=' + bln + '&tahun=' + thn;
        }
        $('#filter_bulan, #filter_tahun').on('change', reload);
    });
</script>