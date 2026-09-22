<div class="box box-primary">
    <div class="box-body">
        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            Isi <strong>qty retur aktual</strong> yang diterima kembali dari customer. Nomor SJ Retur akan otomatis dibuat:
            <strong><?= $retur['no_sj_asal'] ?>R</strong>
        </div>

        <form id="form-sjr">
            <input type="hidden" name="no_retur" value="<?= $retur['no_retur'] ?>">
            <input type="hidden" name="no_sj_asal" value="<?= $retur['no_sj_asal'] ?>">
            <input type="hidden" name="no_invoice" value="<?= $retur['id_invoice'] ?>">
            <input type="hidden" name="no_so" value="<?= $retur['no_so'] ?>">
            <input type="hidden" name="id_customer" value="<?= $retur['id_customer'] ?>">
            <input type="hidden" name="nm_customer" value="<?= $retur['nm_customer'] ?>">

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
                        <label>No. SJ Asal</label>
                        <input type="text" class="form-control" value="<?= $retur['no_sj_asal'] ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Customer</label>
                        <input type="text" class="form-control" value="<?= $retur['nm_customer'] ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Tanggal SJ Retur <span class="text-red">*</span></label>
                        <input type="date" class="form-control" name="tgl_sjr" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control" rows="2"><?= $retur['alasan'] ?></textarea>
                    </div>
                </div>
            </div>

            <hr>
            <h4>Detail Item — Isi Qty Retur Aktual</h4>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-blue">
                        <tr>
                            <th>No</th>
                            <th>ID Produk</th>
                            <th>Nama Produk</th>
                            <th class="text-center">Qty Invoice</th>
                            <th class="text-center">Qty Retur Aktual <span class="text-red">*</span></th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Total Retur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 0;
                        foreach ($detail as $dt): $no++; ?>
                            <tr>
                                <td class="text-center"><?= $no ?></td>
                                <td>
                                    <input type="hidden" name="detail[<?= $no ?>][id_product]" value="<?= $dt['id_product'] ?>">
                                    <input type="hidden" name="detail[<?= $no ?>][nm_product]" value="<?= $dt['nm_product'] ?>">
                                    <input type="hidden" name="detail[<?= $no ?>][id_so_det]" value="<?= $dt['id_so_det'] ?>">
                                    <input type="hidden" name="detail[<?= $no ?>][harga_raw]" value="<?= $dt['harga'] ?>">
                                    <input type="hidden" name="detail[<?= $no ?>][harga_beli]" value="<?= $dt['harga_beli'] ?>">
                                    <?= $dt['id_product'] ?>
                                </td>
                                <td><?= $dt['nm_product'] ?></td>
                                <td class="text-center">
                                    <span class="qty_invoice"><?= $dt['qty_retur'] ?></span>
                                </td>
                                <td style="width:100px;">
                                    <input type="number" name="detail[<?= $no ?>][qty_retur]"
                                        class="form-control input-sm text-center qty_retur_input"
                                        value="<?= $dt['qty_retur'] ?>"
                                        min="0" max="<?= $dt['qty_retur'] ?>" step="1" required>
                                </td>
                                <td class="text-right"><?= number_format($dt['harga'], 0, ',', '.') ?></td>
                                <td class="text-right">
                                    <span class="total_item"><?= number_format($dt['qty_retur'] * $dt['harga'], 0, ',', '.') ?></span>
                                    <input type="hidden" class="total_item_raw" value="<?= $dt['qty_retur'] * $dt['harga'] ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray">
                        <tr>
                            <th colspan="6" class="text-right">Total Nilai Retur</th>
                            <th class="text-right" id="grand_total_sjr">
                                <?= number_format(array_sum(array_column($detail, 'total')), 0, ',', '.') ?>
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
                            <th>Nama COA</th>
                            <th class="text-center">Debit</th>
                            <th class="text-center">Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Baris 1: Debit Persediaan Barang Warehouse (barang fisik balik ke gudang) -->
                        <tr bgcolor="#DCDCDC">
                            <td>
                                <input type="date" name="tgl_jurnal[]" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                            </td>
                            <td>
                                <input type="text" name="type[]" class="form-control" value="JV" readonly>
                            </td>
                            <td>
                                <input type="text" name="no_coa[]" class="form-control" value="1104-01-01" readonly>
                            </td>
                            <td>
                                <input type="text" name="nama_coa[]" class="form-control" value="Persediaan Barang Warehouse" readonly>
                            </td>
                            <td>
                                <input type="hidden" name="debet[]" id="debet_row1" value="0">
                                <input type="text" class="form-control text-right" id="debet_row1_display" value="0" readonly>
                            </td>
                            <td>
                                <input type="hidden" name="kredit[]" id="kredit_row1" value="0">
                                <input type="text" class="form-control text-right" id="kredit_row1_display" value="0" readonly>
                            </td>
                        </tr>
                        <!-- Baris 2: Kredit HPP (membalik beban HPP yang batal) -->
                        <tr bgcolor="#DCDCDC">
                            <td>
                                <input type="date" name="tgl_jurnal[]" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                            </td>
                            <td>
                                <input type="text" name="type[]" class="form-control" value="JV" readonly>
                            </td>
                            <td>
                                <input type="text" name="no_coa[]" class="form-control" value="5101-01-01" readonly>
                            </td>
                            <td>
                                <input type="text" name="nama_coa[]" class="form-control" value="HPP" readonly>
                            </td>
                            <td>
                                <input type="hidden" name="debet[]" id="debet_row2" value="0">
                                <input type="text" class="form-control text-right" id="debet_row2_display" value="0" readonly>
                            </td>
                            <td>
                                <input type="hidden" name="kredit[]" id="kredit_row2" value="0">
                                <input type="text" class="form-control text-right" id="kredit_row2_display" value="0" readonly>
                            </td>
                        </tr>
                        <!-- Baris Total -->
                        <tr bgcolor="#DCDCDC">
                            <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                            <td class="text-right">
                                <input type="text" class="form-control text-right" id="total_debet_display" value="0" readonly>
                            </td>
                            <td class="text-right">
                                <input type="text" class="form-control text-right" id="total_kredit_display" value="0" readonly>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Surat Jalan Retur</button>
                <a class="btn btn-default" href="<?= site_url('retur_credit_note') ?>"><i class="fa fa-reply"></i> Batal</a>
            </div>
        </form>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Hitung ulang total saat qty berubah
        $(document).on('input', '.qty_retur_input', function() {
            var $row = $(this).closest('tr');
            var qty = parseFloat($(this).val()) || 0;
            var maxQty = parseFloat($(this).attr('max')) || 0;
            if (qty > maxQty) {
                qty = maxQty;
                $(this).val(qty);
            }
            if (qty < 0) {
                qty = 0;
                $(this).val(0);
            }

            var harga = parseFloat($row.find('input[name$="[harga_raw]"]').val()) || 0;
            var total = qty * harga;
            $row.find('.total_item').text(total.toLocaleString('id-ID'));
            $row.find('.total_item_raw').val(total);
            hitungGrandTotal();
            hitungJurnal();
        });

        function hitungGrandTotal() {
            var total = 0;
            $('.total_item_raw').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#grand_total_sjr').text(total.toLocaleString('id-ID'));
        }

        // Hitung total nilai jurnal = SUM(qty_retur * harga_beli) per baris
        function hitungJurnal() {
            var totalHargaBeli = 0;
            $('tbody tr').each(function() {
                var $row = $(this);
                var qtyInput = $row.find('.qty_retur_input');
                if (qtyInput.length === 0) return; // skip baris non-detail
                var qty = parseFloat(qtyInput.val()) || 0;
                var hargaBeli = parseFloat($row.find('input[name$="[harga_beli]"]').val()) || 0;
                totalHargaBeli += qty * hargaBeli;
            });

            var formatted = totalHargaBeli.toLocaleString('id-ID');

            // Baris 1: 1104-01-01 Persediaan Barang Warehouse → Debit (barang fisik balik ke gudang)
            $('#debet_row1').val(totalHargaBeli);
            $('#debet_row1_display').val(formatted);
            $('#kredit_row1').val(0);
            $('#kredit_row1_display').val('0');

            // Baris 2: 5101-01-01 HPP → Kredit (membalik beban HPP yang batal)
            $('#debet_row2').val(0);
            $('#debet_row2_display').val('0');
            $('#kredit_row2').val(totalHargaBeli);
            $('#kredit_row2_display').val(formatted);

            // Total
            $('#total_debet_display').val(formatted);
            $('#total_kredit_display').val(formatted);
        }

        // Hitung jurnal saat halaman pertama kali load
        hitungJurnal();

        $('#form-sjr').submit(function(e) {
            e.preventDefault();
            swal({
                title: "Simpan Surat Jalan Retur?",
                text: "Data tidak dapat diubah setelah disimpan.",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, Simpan",
                confirmButtonColor: "#3c8dbc"
            }, function(confirm) {
                if (!confirm) return;
                var formData = new FormData($('#form-sjr')[0]);
                $.ajax({
                    url: siteurl + 'retur_credit_note/save_sjr',
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
                                timer: 4000
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