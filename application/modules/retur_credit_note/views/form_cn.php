<?php
$grand_total_retur = 0;
foreach ($detail as $dt) {
    $grand_total_retur += $dt['total'];
}
$nilai_inv_baru = $grand_total_inv - $grand_total_retur;
?>

<div class="box box-danger">
    <div class="box-body">

        <?php if ($total_sudah_bayar > 0): ?>
            <div class="alert alert-warning">
                <strong><i class="fa fa-exclamation-triangle"></i> Perhatian!</strong>
                Invoice ini sudah pernah menerima pembayaran sebesar
                <strong>Rp <?= number_format($total_sudah_bayar, 0, ',', '.') ?></strong>.
                Piutang baru setelah CN: <strong>Rp <?= number_format(max(0, $nilai_inv_baru - $total_sudah_bayar), 0, ',', '.') ?></strong>.
            </div>
        <?php endif; ?>

        <form id="form-cn">
            <input type="hidden" name="no_retur" value="<?= $retur['no_retur'] ?>">
            <input type="hidden" name="id_invoice" value="<?= $retur['id_invoice'] ?>">
            <input type="hidden" name="grand_total_asli" id="grand_total_asli" value="<?= $grand_total_inv ?>">
            <input type="hidden" name="total_sudah_bayar" value="<?= $total_sudah_bayar ?>">
            <input type="hidden" id="subtotal_invoice" value="<?= $subtotal_invoice ?>">
            <input type="hidden" id="pendapatan_invoice" value="<?= $pendapatan_invoice ?>">
            <input type="hidden" id="ppn_invoice" value="<?= $ppn_invoice ?>">

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>No. Retur</label>
                        <input type="text" class="form-control" value="<?= $retur['no_retur'] ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>No. Invoice</label>
                        <input type="text" class="form-control" value="<?= $retur['id_invoice'] ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>No. SJ Retur</label>
                        <input type="text" class="form-control" value="<?= $retur['no_sjr'] ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Customer</label>
                        <input type="text" class="form-control" value="<?= $retur['nm_customer'] ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Tanggal Credit Note <span class="text-red">*</span></label>
                        <input type="date" class="form-control" name="tgl_retur" required value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
            </div>

            <hr>
            <h4>Detail Item Retur (dari Surat Jalan Retur)</h4>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-red">
                        <tr>
                            <th>No</th>
                            <th>ID Produk</th>
                            <th>Nama Produk</th>
                            <th class="text-center">Qty Retur</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Total Retur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 0;
                        foreach ($detail as $dt): $no++; ?>
                            <tr>
                                <td class="text-center"><?= $no ?></td>
                                <td><?= $dt['id_product'] ?></td>
                                <td><?= $dt['nm_product'] ?></td>
                                <td class="text-center"><?= $dt['qty_retur'] ?></td>
                                <td class="text-right"><?= number_format($dt['harga'], 0, ',', '.') ?></td>
                                <td class="text-right total_item_raw_display">
                                    <?= number_format($dt['total'], 0, ',', '.') ?>
                                    <input type="hidden" class="harga_beli_raw" value="<?= $dt['harga_beli'] ?? 0 ?>">
                                    <input type="hidden" class="qty_retur_raw" value="<?= $dt['qty_retur'] ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray">
                        <tr>
                            <th colspan="5" class="text-right">Total Nilai Retur</th>
                            <th class="text-right">
                                <input type="text" class="form-control input-sm text-right bg-red" id="grand_total_display"
                                    readonly value="<?= number_format($grand_total_retur, 0, ',', '.') ?>">
                                <input type="hidden" name="grand_total" id="grand_total" value="<?= $grand_total_retur ?>">
                            </th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-right">Nilai Invoice Asal</th>
                            <th class="text-right">
                                <input type="text" class="form-control input-sm text-right" readonly
                                    value="<?= number_format($grand_total_inv, 0, ',', '.') ?>">
                            </th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-right">Sudah Dibayar</th>
                            <th class="text-right">
                                <input type="text" class="form-control input-sm text-right" readonly
                                    value="<?= number_format($total_sudah_bayar, 0, ',', '.') ?>">
                            </th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-right">Nilai Invoice Baru (Sisa)</th>
                            <th class="text-right">
                                <input type="text" class="form-control input-sm text-right" id="nilai_inv_baru_display"
                                    readonly value="<?= number_format($nilai_inv_baru, 0, ',', '.') ?>">
                                <input type="hidden" name="nilai_inv_baru" id="nilai_inv_baru" value="<?= $nilai_inv_baru ?>">
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <hr>
            <h4>Informasi Jurnal</h4>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr bgcolor="#9acfea">
                            <th class="text-center">Tanggal</th>
                            <th class="text-center">Tipe</th>
                            <th class="text-center">No. COA</th>
                            <th class="text-center">Nama COA</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Baris 1: Kredit Piutang Dagang (piutang berkurang) -->
                        <tr bgcolor="#DCDCDC">
                            <td><input type="date" class="form-control" value="<?= date('Y-m-d') ?>" readonly></td>
                            <td><input type="text" class="form-control" value="JV" readonly></td>
                            <td><input type="text" class="form-control" value="1102-01-01" readonly></td>
                            <td><input type="text" class="form-control" value="Piutang Dagang" readonly></td>
                            <td><input type="text" class="form-control text-right" value="0" readonly></td>
                            <td><input type="text" class="form-control text-right" id="jrn_piutang_display" value="0" readonly></td>
                        </tr>
                        <!-- Baris 2: Debit Retur Penjualan (contra-revenue) -->
                        <tr bgcolor="#DCDCDC">
                            <td><input type="date" class="form-control" value="<?= date('Y-m-d') ?>" readonly></td>
                            <td><input type="text" class="form-control" value="JV" readonly></td>
                            <td><input type="text" class="form-control" value="4102-01-01" readonly></td>
                            <td><input type="text" class="form-control" value="Retur Penjualan" readonly></td>
                            <td><input type="text" class="form-control text-right" id="jrn_retur_display" value="0" readonly></td>
                            <td><input type="text" class="form-control text-right" value="0" readonly></td>
                        </tr>
                        <!-- Baris 3: Debit PPN Keluaran (kewajiban PPN berkurang) -->
                        <tr bgcolor="#DCDCDC">
                            <td><input type="date" class="form-control" value="<?= date('Y-m-d') ?>" readonly></td>
                            <td><input type="text" class="form-control" value="JV" readonly></td>
                            <td><input type="text" class="form-control" value="2103-01-01" readonly></td>
                            <td><input type="text" class="form-control" value="PPN Keluaran" readonly></td>
                            <td><input type="text" class="form-control text-right" id="jrn_ppn_display" value="0" readonly></td>
                            <td><input type="text" class="form-control text-right" value="0" readonly></td>
                        </tr>
                        <!-- Total -->
                        <tr bgcolor="#DCDCDC">
                            <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                            <td><input type="text" class="form-control text-right" id="jrn_total_debet_display" value="0" readonly></td>
                            <td><input type="text" class="form-control text-right" id="jrn_total_kredit_display" value="0" readonly></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-danger"><i class="fa fa-check"></i> Proses Credit Note</button>
                <a class="btn btn-default" href="<?= site_url('retur_credit_note') ?>"><i class="fa fa-reply"></i> Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {

        // Hitung jurnal saat halaman load
        hitungJurnalCN();

        function hitungJurnalCN() {
            // Total Retur = grand_total (qty * harga include PPN) → Piutang Dagang KREDIT
            var totalRetur = parseFloat($('#grand_total').val()) || 0;

            // Retur Penjualan (4102-01-01) & PPN Keluaran (2103-01-01) TIDAK dihitung ulang
            // dengan rumus 11/12 — itu bisa mismatch dengan nilai asli invoice karena rounding
            // (rumus invoice membagi 1.11 dari subtotal SEBELUM PPN, sedangkan totalRetur di
            // sini sudah setara subtotal itu sendiri). Sebagai gantinya, nilai Pendapatan
            // Penjualan & PPN Keluaran yang SUDAH dijurnal saat invoice dibuat diproporsikan
            // sesuai rasio nilai retur terhadap subtotal invoice — identik dengan yang dihitung
            // di save_cn()/_buat_jurnal_credit_note() pada controller.
            var subtotalInvoice = parseFloat($('#subtotal_invoice').val()) || 0;
            var pendapatanInvoice = parseFloat($('#pendapatan_invoice').val()) || 0;
            var ppnInvoice = parseFloat($('#ppn_invoice').val()) || 0;

            var returPenjualan = 0;
            var ppnKeluaran = 0;
            if (subtotalInvoice > 0 && pendapatanInvoice > 0) {
                var rasio = totalRetur / subtotalInvoice;
                returPenjualan = pendapatanInvoice * rasio;
                ppnKeluaran = ppnInvoice * rasio;
            } else {
                // Fallback: invoice lama yang jurnalnya tidak ditemukan
                var excludeppn = totalRetur / 1.11;
                var dpp = (excludeppn * 11) / 12;
                ppnKeluaran = (dpp * 12) / 100;
                returPenjualan = excludeppn;
            }

            var fmt = function(n) {
                return n.toLocaleString('id-ID');
            };

            $('#jrn_piutang_display').val(fmt(totalRetur));
            $('#jrn_retur_display').val(fmt(returPenjualan));
            $('#jrn_ppn_display').val(fmt(ppnKeluaran));
            $('#jrn_total_debet_display').val(fmt(returPenjualan + ppnKeluaran));
            $('#jrn_total_kredit_display').val(fmt(totalRetur));
        }

        $('#form-cn').submit(function(e) {
            e.preventDefault();
            swal({
                title: "Proses Credit Note?",
                text: "Tindakan ini akan mencatat Credit Note dan membuat jurnal koreksi. Nilai piutang invoice tidak akan berubah. Tidak dapat dibatalkan.",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, Proses",
                confirmButtonColor: "#dd4b39"
            }, function(confirm) {
                if (!confirm) return;
                var formData = new FormData($('#form-cn')[0]);
                $.ajax({
                    url: siteurl + 'retur_credit_note/save_cn',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(res) {
                        if (res.status == 1) {
                            swal({
                                title: 'Berhasil!',
                                text: res.pesan,
                                type: 'success',
                                timer: 5000
                            });
                            setTimeout(function() {
                                window.location.href = siteurl + 'retur_credit_note';
                            }, 1500);
                        } else {
                            swal('Gagal', res.pesan, 'warning');
                        }
                    },
                    error: function() {
                        swal('Error', 'Terjadi kesalahan.', 'error');
                    }
                });
            });
        });
    });
</script>