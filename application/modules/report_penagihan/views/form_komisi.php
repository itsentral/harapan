<form id="form-komisi-penagihan" autocomplete="off">
    <input type="hidden" name="id" value="<?= isset($komisi->id) ? $komisi->id : '' ?>">
    <input type="hidden" name="id_karyawan" value="<?= $id_sales ?>">
    <input type="hidden" name="nm_karyawan" value="<?= $nama_sales ?>">
    <input type="hidden" name="bulan_id" value="<?= $bulan_id ?>">
    <input type="hidden" name="bulan" value="<?= $nama_bulan ?>">

    <div class="form-group row">
        <label class="col-md-2 control-label"><b>Sales</b></label>
        <div class="col-md-4">
            <input type="text" class="form-control input-sm" value="<?= ucwords($nama_sales) ?>" readonly>
        </div>
        <label class="col-md-2 control-label"><b>Bulan</b></label>
        <div class="col-md-4">
            <input type="text" class="form-control input-sm" value="<?= ucfirst($nama_bulan) ?> <?= $tahun ?>" readonly>
        </div>
    </div>

    <hr>

    <table class="table table-sm table-bordered">
        <thead>
            <tr class="bg-primary">
                <th width="18%">Item</th>
                <th class="text-center" width="14%">Target</th>
                <th class="text-center" width="14%">Pencapaian</th>
                <th class="text-center" width="16%">Persentase Kinerja</th>
                <th class="text-center" width="16%">Koefisien</th>
                <th class="text-center" width="14%">Nilai Komisi</th>
            </tr>
        </thead>
        <tbody>
            <!-- Pencapaian Tagihan On Time -->
            <tr>
                <td>Pencapaian Tagihan On Time</td>
                <td><input type="text" name="target_ontime" id="k_target_ontime" class="form-control input-sm komisiMoney" value="<?= isset($komisi->target_ontime) ? number_format($komisi->target_ontime, 0) : number_format($target_ontime, 0) ?>"></td>
                <td><input type="text" name="realisasi_ontime" id="k_realisasi_ontime" class="form-control input-sm komisiMoney" value="<?= isset($komisi->realisasi_ontime) ? number_format($komisi->realisasi_ontime, 0) : number_format($realisasi_ontime, 0) ?>"></td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" name="persentase_ontime" id="k_persentase_ontime" class="form-control" readonly value="<?= isset($komisi->persentase_ontime) ? number_format($komisi->persentase_ontime, 2) : '' ?>">
                        <span class="input-group-addon">%</span>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" name="koefisien_ontime" id="k_koefisien_ontime" class="form-control" readonly value="<?= isset($komisi->koefisien_ontime) ? number_format($komisi->koefisien_ontime, 2) : '' ?>">
                        <span class="input-group-addon">%</span>
                    </div>
                </td>
                <td><input type="hidden" name="nilai_komisi_ontime" id="k_nilai_komisi_ontime" value="<?= isset($komisi->nilai_komisi_ontime) ? number_format($komisi->nilai_komisi_ontime, 0) : '' ?>"></td>
            </tr>

            <!-- Pencapaian Pembayaran Tunggakan -->
            <tr>
                <td>Pencapaian Pembayaran Tunggakan</td>
                <td><input type="text" name="target_tunggakan" id="k_target_tunggakan" class="form-control input-sm komisiMoney" value="<?= isset($komisi->target_tunggakan) ? number_format($komisi->target_tunggakan, 0) : number_format($target_tunggakan, 0) ?>"></td>
                <td><input type="text" name="realisasi_tunggakan" id="k_realisasi_tunggakan" class="form-control input-sm komisiMoney" value="<?= isset($komisi->realisasi_tunggakan) ? number_format($komisi->realisasi_tunggakan, 0) : number_format($realisasi_tunggakan, 0) ?>"></td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" name="persentase_tunggakan" id="k_persentase_tunggakan" class="form-control" readonly value="<?= isset($komisi->persentase_tunggakan) ? number_format($komisi->persentase_tunggakan, 2) : '' ?>">
                        <span class="input-group-addon">%</span>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" name="koefisien_tunggakan" id="k_koefisien_tunggakan" class="form-control" readonly value="<?= isset($komisi->koefisien_tunggakan) ? number_format($komisi->koefisien_tunggakan, 2) : '' ?>">
                        <span class="input-group-addon">%</span>
                    </div>
                </td>
                <td><input type="hidden" name="nilai_komisi_tunggakan" id="k_nilai_komisi_tunggakan" value="<?= isset($komisi->nilai_komisi_tunggakan) ? number_format($komisi->nilai_komisi_tunggakan, 0) : '' ?>"></td>
            </tr>

            <!-- Baris Total disembunyikan dari tampilan, namun field tetap dipertahankan
                 (hidden) karena nilainya dipakai untuk menghitung komisi penjualan dan
                 disimpan saat Save. -->
            <tr style="display:none;">
                <td colspan="6"><input type="hidden" name="total_ontime_tunggakan" id="k_total_ontime_tunggakan" value="<?= isset($komisi->total_ontime_tunggakan) ? number_format($komisi->total_ontime_tunggakan, 0, '.', '') : '' ?>"></td>
            </tr>

            <!-- Pencapaian Penjualan -->
            <tr>
                <td>Pencapaian Penjualan</td>
                <td><input type="text" name="target_penjualan" id="k_target_penjualan" class="form-control input-sm komisiMoney" value="<?= isset($komisi->target_penjualan) ? number_format($komisi->target_penjualan, 0) : number_format($target_penjualan_val, 0) ?>"></td>
                <td><input type="text" name="realisasi_penjualan" id="k_realisasi_penjualan" class="form-control input-sm komisiMoney" readonly value="<?= isset($komisi->realisasi_penjualan) ? number_format($komisi->realisasi_penjualan, 0) : number_format($realisasi_penjualan_val, 0) ?>"></td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" name="persentase_penjualan" id="k_persentase_penjualan" class="form-control" readonly value="<?= isset($komisi->persentase_penjualan) ? number_format($komisi->persentase_penjualan, 2) : '' ?>">
                        <span class="input-group-addon">%</span>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="text" name="koefisien_penjualan" id="k_koefisien_penjualan" class="form-control" readonly value="<?= isset($komisi->koefisien_penjualan) ? number_format($komisi->koefisien_penjualan, 2) : '' ?>">
                        <span class="input-group-addon">%</span>
                    </div>
                </td>
                <td><input type="text" name="nilai_komisi_penjualan" id="k_nilai_komisi_penjualan" class="form-control input-sm komisiMoney" readonly value="<?= isset($komisi->nilai_komisi_penjualan) ? number_format($komisi->nilai_komisi_penjualan, 0) : '' ?>"></td>
            </tr>

            <!-- Baris Grand Total disembunyikan dari tampilan, namun field tetap
                 dipertahankan (hidden) agar tetap tersimpan saat Save. -->
            <tr style="display:none;">
                <td colspan="6"><input type="hidden" name="grand_total" id="k_grand_total" value="<?= isset($komisi->grand_total) ? number_format($komisi->grand_total, 0, '.', '') : '' ?>"></td>
            </tr>
        </tbody>
    </table>

    <div class="text-right">
        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save</button>
    </div>
</form>

<script>
    $(function() {
        // Format number helper
        function toNum(str) {
            return parseFloat(String(str).replace(/[^0-9.-]+/g, '')) || 0;
        }

        function formatNum(n) {
            // Gunakan koma sebagai pemisah ribuan agar konsisten dengan format
            // number_format() PHP dan field lain di form ini (mis. "126,464,100").
            // Locale 'id-ID' memakai titik sebagai pemisah ribuan, yang salah
            // dibaca ulang oleh toNum() karena titik dianggap desimal.
            return n.toLocaleString('en-US', {
                maximumFractionDigits: 0
            });
        }

        // Hitung komisi per item
        function hitungKomisi(key) {
            var target = toNum($('#k_target_' + key).val());
            var realisasi = toNum($('#k_realisasi_' + key).val());
            var persentase = (target > 0) ? (realisasi / target) * 100 : 0;
            $('#k_persentase_' + key).val(persentase.toFixed(2));

            // Ambil koefisien dari server
            $.ajax({
                url: siteurl + 'master_komisi/get_koefisien',
                method: 'POST',
                dataType: 'json',
                data: {
                    komisi_type: key,
                    persen: persentase
                },
                success: function(res) {
                    var koef = res.success ? parseFloat(res.koefisien) : 0;
                    $('#k_koefisien_' + key).val(koef.toFixed(2));

                    var nilai = realisasi * (koef / 100);
                    $('#k_nilai_komisi_' + key).val(formatNum(nilai));

                    hitungTotal();
                }
            });
        }

        function hitungPenjualan() {
            var target = toNum($('#k_target_penjualan').val());
            var realisasi = toNum($('#k_realisasi_penjualan').val());
            var total_ot = toNum($('#k_total_ontime_tunggakan').val());
            var persentase = (target > 0) ? (realisasi / target) * 100 : 0;
            $('#k_persentase_penjualan').val(persentase.toFixed(2));

            $.ajax({
                url: siteurl + 'master_komisi/get_koefisien',
                method: 'POST',
                dataType: 'json',
                data: {
                    komisi_type: 'penjualan',
                    persen: persentase
                },
                success: function(res) {
                    var koef = res.success ? parseFloat(res.koefisien) : 0;
                    $('#k_koefisien_penjualan').val(koef.toFixed(2));

                    var nilai = total_ot * (koef / 100);
                    $('#k_nilai_komisi_penjualan').val(formatNum(nilai));

                    hitungGrandTotal();
                }
            });
        }

        function hitungTotal() {
            var n_ontime = toNum($('#k_nilai_komisi_ontime').val());
            var n_tunggakan = toNum($('#k_nilai_komisi_tunggakan').val());
            var total = n_ontime + n_tunggakan;
            $('#k_total_ontime_tunggakan').val(formatNum(total));

            // Nilai komisi penjualan bergantung pada total ini, jadi hitung ulang
            // setelah total_ontime_tunggakan ter-update (mengatasi race condition
            // karena hitungKomisi('ontime'/'tunggakan') bersifat async).
            hitungPenjualan();
        }

        function hitungGrandTotal() {
            var total_ot = toNum($('#k_total_ontime_tunggakan').val());
            var n_penjualan = toNum($('#k_nilai_komisi_penjualan').val());
            $('#k_grand_total').val(formatNum(total_ot + n_penjualan));
        }

        // Event listeners
        $('#k_realisasi_ontime, #k_target_ontime').on('change keyup', function() {
            hitungKomisi('ontime');
        });
        $('#k_realisasi_tunggakan, #k_target_tunggakan').on('change keyup', function() {
            hitungKomisi('tunggakan');
        });
        $('#k_realisasi_penjualan, #k_target_penjualan').on('change keyup', function() {
            hitungPenjualan();
        });

        // Trigger hitung awal jika ada data
        if (toNum($('#k_target_ontime').val()) > 0) {
            hitungKomisi('ontime');
        }
        if (toNum($('#k_target_tunggakan').val()) > 0) {
            hitungKomisi('tunggakan');
        }
        if (toNum($('#k_target_penjualan').val()) > 0) {
            hitungPenjualan();
        }
    });
</script>