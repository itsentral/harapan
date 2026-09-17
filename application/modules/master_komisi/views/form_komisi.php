<div class="box box-primary">
    <div class="box-body box-primary">
        <form id="data_form" autocomplete="off">

            <!-- Hidden ID jika mode edit -->
            <?php if (!empty($komisi->id)): ?>
                <input type="hidden" name="id" value="<?= $komisi->id ?>">
                <input type="hidden" name="id_karyawan" value="<?= $komisi->id_karyawan ?>">
                <input type="hidden" name="bulan_id" value="<?= $komisi->bulan_id ?>">
            <?php endif; ?>

            <!-- Pilih Sales -->
            <div class="form-group row">
                <label class="col-md-3 control-label">Sales</label>
                <div class="col-md-9">
                    <select name="id_karyawan" class="form-control select2" id="sales-select" required <?= isset($komisi->id_karyawan) ? 'disabled' : '' ?>>
                        <option value="">-- Pilih Sales --</option>
                        <?php foreach ($sales as $s): ?>
                            <option
                                value="<?= $s['id'] ?>"
                                data-nama="<?= $s['nm_karyawan'] ?>"
                                <?= (isset($komisi->id_karyawan) && $komisi->id_karyawan == $s['id']) ? 'selected' : '' ?>>
                                <?= ucfirst($s['nm_karyawan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Hidden untuk nm_karyawan -->
                    <input type="hidden" name="nm_karyawan" id="nm_karyawan"
                        value="<?= isset($komisi->nm_karyawan) ? $komisi->nm_karyawan : '' ?>">
                </div>
            </div>

            <!-- Pilih Bulan -->
            <div class="form-group row">
                <label class="col-md-3 control-label">Bulan</label>
                <div class="col-md-9">
                    <select name="bulan_id" class="form-control select2" id="bulan-select" required <?= isset($komisi->bulan_id) ? 'disabled' : '' ?>>
                        <option value="">-- Pilih Bulan --</option>
                        <?php foreach ($bulan as $b): ?>
                            <option
                                value="<?= $b['bulan_id'] ?>"
                                data-bulan="<?= $b['bulan'] ?>"
                                <?= (isset($komisi->bulan_id) && $komisi->bulan_id == $b['bulan_id']) ? 'selected' : '' ?>>
                                <?= ucfirst($b['bulan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Hidden untuk nama bulan -->
                    <input type="hidden" name="bulan" id="bulan_id"
                        value="<?= isset($komisi->bulan) ? $komisi->bulan : '' ?>">
                </div>
            </div>

            <hr>

            <!-- Perhitungan Komisi -->
            <div class="form-group row">
                <div class="col-md-12">
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr class="bg-blue">
                                <td width="15%">Item</td>
                                <td class="text-center">Target</td>
                                <td class="text-center">Pencapaian</td>
                                <td class="text-center">Persentase Kinerja</td>
                                <td class="text-center">Koefisien</td>
                                <td class="text-center">Nilai Komisi</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $items = [
                                'ontime' => 'Pencapaian Tagihan On Time',
                                'tunggakan' => 'Pencapaian Pembayaran Tunggakan',
                            ];
                            foreach ($items as $key => $label):
                            ?>
                                <tr>
                                    <td><?= $label ?></td>
                                    <td><input type="text" name="target_<?= $key ?>" id="target_<?= $key ?>" class="form-control moneyFormat" value="<?= isset($komisi) ? number_format($komisi->{'target_' . $key}, 2) : '' ?>"></td>
                                    <td><input type="text" name="realisasi_<?= $key ?>" id="realisasi_<?= $key ?>" class="form-control moneyFormat" value="<?= isset($komisi) ? number_format($komisi->{'realisasi_' . $key}, 2) : '' ?>"></td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" name="persentase_<?= $key ?>" id="persentase_<?= $key ?>" class="form-control" readonly value="<?= isset($komisi) ? number_format($komisi->{'persentase_' . $key}, 2) : '' ?>">
                                            <span class="input-group-addon"><i class="fa fa-percent"></i></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" name="koefisien_<?= $key ?>" id="koefisien_<?= $key ?>" class="form-control" readonly value="<?= isset($komisi) ? number_format($komisi->{'koefisien_' . $key}, 2) : '' ?>">
                                            <span class="input-group-addon"><i class="fa fa-percent"></i></span>
                                        </div>
                                    </td>
                                    <td><input type="hidden" name="nilai_komisi_<?= $key ?>" id="nilai_komisi_<?= $key ?>" value="<?= isset($komisi) ? number_format($komisi->{'nilai_komisi_' . $key}, 2, '.', '') : '' ?>"></td>
                                </tr>
                            <?php endforeach; ?>

                            <!-- Baris Total disembunyikan dari tampilan, namun field tetap
                                 dipertahankan (hidden) karena nilainya dipakai untuk menghitung
                                 komisi penjualan dan disimpan saat Save. -->
                            <tr style="display:none;">
                                <td colspan="6"><input type="hidden" name="total_ontime_tunggakan" id="total_ontime_tunggakan" value="<?= isset($komisi) ? number_format($komisi->total_ontime_tunggakan, 2, '.', '') : '' ?>"></td>
                            </tr>

                            <tr>
                                <td>Pencapaian Penjualan</td>
                                <td><input type="text" name="target_penjualan" id="target_penjualan" class="form-control moneyFormat" value="<?= isset($komisi) ? number_format($komisi->target_penjualan, 2) : '' ?>"></td>
                                <td><input type="text" name="realisasi_penjualan" id="realisasi_penjualan" class="form-control moneyFormat" value="<?= isset($komisi) ? number_format($komisi->realisasi_penjualan, 2) : '' ?>"></td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" name="persentase_penjualan" id="persentase_penjualan" class="form-control" readonly value="<?= isset($komisi) ? number_format($komisi->persentase_penjualan, 2) : '' ?>">
                                        <span class="input-group-addon"><i class="fa fa-percent"></i></span>
                                    </div>
                                </td>
                                <td>
                                    <div class="input-group">
                                        <input type="text" name="koefisien_penjualan" id="koefisien_penjualan" class="form-control" readonly value="<?= isset($komisi) ? number_format($komisi->koefisien_penjualan, 2) : '' ?>">
                                        <span class="input-group-addon"><i class="fa fa-percent"></i></span>
                                    </div>
                                </td>
                                <td><input type="text" name="nilai_komisi_penjualan" id="nilai_komisi_penjualan" class="form-control moneyFormat" readonly value="<?= isset($komisi) ? number_format($komisi->nilai_komisi_penjualan, 2) : '' ?>"></td>
                            </tr>

                            <!-- Baris Grand Total disembunyikan dari tampilan, namun field tetap
                                 dipertahankan (hidden) agar tetap tersimpan saat Save. -->
                            <tr style="display:none;">
                                <td colspan="6"><input type="hidden" name="grand_total" id="grand_total" value="<?= isset($komisi) ? number_format($komisi->grand_total, 2, '.', '') : '' ?>"></td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>

            <div class=" form-group row">
                <div class="col-md-3"></div>
                <div class="col-md-9 text-right">
                    <button type="submit" class="btn btn-primary" name="save" id="save"><i class="fa fa-save"></i> Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="<?= base_url('assets/plugins/select2/select2.full.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/jquery-inputmask/jquery.inputmask.js') ?>"></script>

<script>
    $(document).ready(function() {
        $(document).on('change', '#sales-select', function() {
            const selectedNama = $(this).find('option:selected').data('nama') || '';
            $('#nm_karyawan').val(selectedNama);
        });

        $(document).on('change', '#bulan-select', function() {
            const namaBulan = $(this).find('option:selected').data('bulan') || '';
            $('#bulan_id').val(namaBulan);
        });

        $('.select2').select2({
            width: '100%',
        });
        moneyFormat('.moneyFormat')

        const keys = ['ontime', 'tunggakan'];
        keys.forEach(key => {
            $(`#realisasi_${key}`).on('input', function() {
                hitungTotalKomisi(key);
            });
        });

        const penjualan = 'penjualan'
        $('#realisasi_penjualan').on('input', function() {
            hitungTotalKomisiPenjualan(penjualan);
        });
    });

    function toFloat(str) {
        return parseFloat(String(str).replace(/[^0-9.-]+/g, '')) || 0;
    }

    function hitungTotalKomisi(key) {
        const target = toFloat($(`#target_${key}`).val());
        const realisasi = toFloat($(`#realisasi_${key}`).val());

        let persentase = (target > 0) ? (realisasi / target) * 100 : 0;
        $(`#persentase_${key}`).val(persentase.toFixed(2));

        getKoefisien(key, persentase, function(koef) {
            const koefNum = parseFloat(koef) || 0;

            const nilai = realisasi * (koefNum / 100);

            $(`#koefisien_${key}`).val(koefNum.toFixed(2));
            $(`#nilai_komisi_${key}`).val(nilai.toFixed(2));

            hitungTotalSemua();
        });

    }

    function hitungTotalKomisiPenjualan(penjualan) {
        const target = toFloat($(`#target_${penjualan}`).val());
        const realisasi = toFloat($(`#realisasi_${penjualan}`).val());
        const total_ontime_tunggakan = toFloat($('#total_ontime_tunggakan').val());

        let persentase = (target > 0) ? (realisasi / target) * 100 : 0;
        $(`#persentase_${penjualan}`).val(persentase.toFixed(2));

        getKoefisien(penjualan, persentase, function(koef) {

            const koefNum = parseFloat(koef) || 0;

            const nilai = total_ontime_tunggakan * (koefNum / 100);

            $(`#koefisien_${penjualan}`).val(koefNum.toFixed(2));
            $(`#nilai_komisi_${penjualan}`).val(nilai.toFixed(2));

            hitungGrandTotal();
        });
    }

    function hitungTotalSemua() {
        const keys = ['ontime', 'tunggakan'];
        let total = 0;

        keys.forEach(key => {
            const nilai = toFloat($(`#nilai_komisi_${key}`).val());
            total += nilai;
        });

        $('#total_ontime_tunggakan').val(total.toFixed(2));
    }

    function hitungGrandTotal() {
        const total_ontime_tunggakan = toFloat($('#total_ontime_tunggakan').val());
        const nilai_komisi_penjualan = toFloat($('#nilai_komisi_penjualan').val());

        const grand_total = total_ontime_tunggakan + nilai_komisi_penjualan;

        $('#grand_total').val(grand_total.toFixed(2));
    }

    function getKoefisien(jenis, persen, callback) {
        $.ajax({
            url: "<?= site_url('master_komisi/get_koefisien') ?>",
            method: "POST",
            dataType: "json",
            data: {
                komisi_type: jenis,
                persen: persen
            },
            success: function(res) {
                if (res.success) {
                    callback(res.koefisien);
                } else {
                    console.warn("❌ Koefisien tidak ditemukan di DB!");
                    callback(0);
                }
            },
            error: function(xhr, status, error) {
                console.error("❌ AJAX gagal:", error);
            }
        });
    }

    function moneyFormat(e) {
        $(e).inputmask({
            alias: "decimal",
            digits: 2,
            radixPoint: ".",
            autoGroup: true,
            placeholder: "0",
            rightAlign: false,
            allowMinus: false,
            integerDigits: 13,
            groupSeparator: ",",
            digitsOptional: false,
            showMaskOnHover: true,
        })
    }
</script>