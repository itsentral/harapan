<?php

/**
 * Partial tabel Report Costbook Surat Jalan.
 * Dipakai BERSAMA oleh tampilan layar (report_costbook_surat_jalan.php) dan
 * download Excel (report_costbook_surat_jalan_excel.php) agar kolom & isi selalu identik.
 *
 * Variabel yang dibutuhkan: $rows (array of object)
 * Variabel opsional: $for_excel (bool) - true saat dipakai untuk export Excel.
 */
$for_excel = isset($for_excel) ? $for_excel : false;

// Hitung subtotal
$sum_qty          = 0;
$sum_total_berat  = 0;
$sum_qty_terkirim = 0;
$sum_qty_retur    = 0;
$sum_qty_hilang   = 0;
$sum_qty_lebih    = 0;
if (!empty($rows)) {
    foreach ($rows as $r) {
        $sum_qty          += (float) $r->qty;
        $sum_total_berat  += (float) $r->total_berat;
        $sum_qty_terkirim += (float) $r->qty_terkirim;
        $sum_qty_retur    += (float) $r->qty_retur;
        $sum_qty_hilang   += (float) $r->qty_hilang;
        $sum_qty_lebih    += (float) $r->qty_lebih;
    }
}
?>
<table border="1" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>No Surat Jalan</th>
            <th>No SO</th>
            <th>Tgl Kirim</th>
            <th>Created At</th>
            <th>Customer</th>
            <th>Product ID</th>
            <th>Product Name</th>
            <th class="text-right">Qty</th>
            <th class="text-right">Weight</th>
            <th class="text-right">Total Berat</th>
            <th class="text-right">Qty Terkirim</th>
            <th class="text-right">Qty Retur</th>
            <th class="text-right">Qty Hilang</th>
            <th class="text-right">Qty Lebih</th>
            <th class="text-right">Costbook<br>(IDR)</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($rows)) : $no = 1; ?>
            <?php foreach ($rows as $r) : ?>
                <tr>
                    <td align="center"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($r->no_surat_jalan); ?></td>
                    <td><?= htmlspecialchars($r->no_so); ?></td>
                    <td><?= !empty($r->delivery_date) ? date('d-M-Y', strtotime($r->delivery_date)) : '-'; ?></td>
                    <td><?= !empty($r->created_at) ? date('d-M-Y H:i:s', strtotime($r->created_at)) : '-'; ?></td>
                    <td><?= strtoupper(htmlspecialchars($r->name_customer)); ?></td>
                    <td><?= htmlspecialchars($r->id_product); ?></td>
                    <td><?= strtoupper(htmlspecialchars($r->product)); ?></td>
                    <td align="right"><?= number_format((float) $r->qty, 0, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->weight, 2, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->total_berat, 2, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->qty_terkirim, 0, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->qty_retur, 0, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->qty_hilang, 0, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->qty_lebih, 0, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->costbook, 2, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php elseif ($for_excel) : ?>
            <tr>
                <td colspan="16" align="center">Tidak ada data.</td>
            </tr>
        <?php endif; ?>
    </tbody>
    <?php if (!empty($rows)) : ?>
        <tfoot>
            <tr>
                <th colspan="8" style="text-align:right;">TOTAL</th>
                <th style="text-align:right;"><?= number_format($sum_qty, 0, ',', '.'); ?></th>
                <th></th>
                <th style="text-align:right;"><?= number_format($sum_total_berat, 2, ',', '.'); ?></th>
                <th style="text-align:right;"><?= number_format($sum_qty_terkirim, 0, ',', '.'); ?></th>
                <th style="text-align:right;"><?= number_format($sum_qty_retur, 0, ',', '.'); ?></th>
                <th style="text-align:right;"><?= number_format($sum_qty_hilang, 0, ',', '.'); ?></th>
                <th style="text-align:right;"><?= number_format($sum_qty_lebih, 0, ',', '.'); ?></th>
                <th></th>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>