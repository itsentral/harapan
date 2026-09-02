<div class="box box-primary">
    <div class="box-body">
        <div class="row">
            <div class="col-md-12">
                <div class="col-md-6">
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Nomor Surat Jalan</label>
                        </div>
                        <div class="col-auto">
                            <p>:&emsp;<?= $sj['no_surat_jalan'] ?></p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Nomor SPK Delivery</label>
                        </div>
                        <div class="col-auto">
                            <p>:&emsp;<?= $sj['no_delivery'] ?></p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Nomor Sales Order</label>
                        </div>
                        <div class="col-auto">
                            <p>:&emsp;<?= $sj['no_so'] ?></p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Nomor Penawaran</label>
                        </div>
                        <div class="col-sm-auto">
                            <p>:&emsp;<?= $sj['id_penawaran'] ?></p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Customer</label>
                        </div>
                        <div class="col-sm-auto">
                            <p>:&emsp;<?= $sj['name_customer'] ?></p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Alamat Pengiriman</label>
                        </div>
                        <div class="col-sm-auto">
                            <p>:&emsp;<?= $sj['delivery_address'] ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Status</label>
                        </div>
                        <div class="col-sm-auto">
                            <?php
                                $badge = 'bg-gray';
                                if ($sj['status'] === 'CONFIRM') $badge = 'bg-green';
                                elseif ($sj['status'] === 'HILANG') $badge = 'bg-red';
                                elseif ($sj['status'] === 'RETUR') $badge = 'bg-yellow';
                            ?>
                            <p>:&emsp;<span class="badge <?= $badge ?>"><?= $sj['status'] ?></span></p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Tanggal Pengiriman</label>
                        </div>
                        <div class="col-sm-8">
                            <input type="date" class="form-control" readonly value="<?= date('Y-m-d', strtotime($sj['delivery_date'])) ?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Tanggal Diterima</label>
                        </div>
                        <div class="col-sm-8">
                            <input type="date" class="form-control" readonly value="<?= !empty($sj['tgl_diterima']) ? date('Y-m-d', strtotime($sj['tgl_diterima'])) : '' ?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Diterima Oleh</label>
                        </div>
                        <div class="col-sm-8">
                            <input type="text" class="form-control" readonly value="<?= $sj['penerima'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Dokumen Bukti</label>
                        </div>
                        <div class="col-sm-8">
                            <?php if (!empty($sj['file_dokumen'])): ?>
                                <a href="<?= base_url('uploads/confirm_sj/' . $sj['file_dokumen']) ?>" target="_blank" class="btn btn-xs btn-info">
                                    <i class="fa fa-file"></i> Lihat File
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="col-sm-12">
                    <hr>
                    <h4>List Product</h4>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="bg-blue">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th class="text-center" style="min-width: 100px;">Qty Order</th>
                                    <th class="text-center" style="min-width: 100px;">Qty SPK</th>
                                    <th class="text-center" style="min-width: 100px;">Qty Delivery</th>
                                    <th class="text-center" style="min-width: 100px;">Qty Terkirim</th>
                                    <th class="text-center" style="min-width: 100px;">Qty Retur</th>
                                    <th class="text-center" style="min-width: 100px;">Qty Hilang</th>
                                    <th class="text-center" style="min-width: 100px;">Qty Lebih</th>
                                    <th class="text-center" style="min-width: 200px;">Keterangan Retur/Hilang</th>
                                    <th class="text-center" style="min-width: 150px;">Bukti Retur/Hilang</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail as $i => $row): ?>
                                    <tr>
                                        <td align="center"><?= $i + 1; ?></td>
                                        <td style="min-width: 500px;"><?= $row['product']; ?></td>
                                        <td align="center">
                                            <input type="number" class="form-control text-center" value="<?= $row['qty_so']; ?>" readonly>
                                        </td>
                                        <td align="center">
                                            <input type="number" class="form-control text-center" value="<?= $row['qty_spk']; ?>" readonly>
                                        </td>
                                        <td align="center">
                                            <input type="number" class="form-control text-center" value="<?= $row['qty']; ?>" readonly>
                                        </td>
                                        <td align="center">
                                            <input type="number" class="form-control text-center" value="<?= $row['qty_terkirim']; ?>" readonly>
                                        </td>
                                        <td align="center">
                                            <input type="number" class="form-control text-center" value="<?= $row['qty_retur']; ?>" readonly>
                                        </td>
                                        <td align="center">
                                            <input type="number" class="form-control text-center" value="<?= $row['qty_hilang']; ?>" readonly>
                                        </td>
                                        <td align="center">
                                            <input type="number" class="form-control text-center" value="<?= $row['qty_lebih']; ?>" readonly>
                                        </td>
                                        <td>
                                            <textarea class="form-control" readonly><?= $row['reason'] ?? '' ?></textarea>
                                        </td>
                                        <td align="center">
                                            <?php if (!empty($row['file_bukti'])): ?>
                                                <a href="<?= base_url('uploads/confirm_sj/' . $row['file_bukti']) ?>" target="_blank" class="btn btn-xs btn-info">
                                                    <i class="fa fa-file"></i> Lihat File
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-12 text-center">
                    <a class="btn btn-default" onclick="window.history.back(); return false;">
                        <i class="fa fa-reply"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
