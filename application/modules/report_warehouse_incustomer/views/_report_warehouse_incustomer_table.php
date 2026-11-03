<?php

/**
 * Partial tabel Report Warehouse In-Customer.
 * Dipakai BERSAMA oleh tampilan layar (report_warehouse_incustomer.php) dan
 * download Excel (report_warehouse_incustomer_excel.php) agar kolom & isi selalu identik.
 *
 * Variabel yang dibutuhkan: $rows (array of object)
 * Variabel opsional: $for_excel (bool) - true saat dipakai untuk export Excel.
 */
$for_excel = isset($for_excel) ? $for_excel : false;

$sum_qty   = 0;
$sum_total = 0;
if (!empty($rows)) {
    foreach ($rows as $r) {
        $sum_qty   += (float) $r->qty;
        $sum_total += (float) $r->total_harga;
    }
}
?>
<table border="1" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Tgl Surat Jalan</th>
            <th>No Surat Jalan</th>
            <th>Customer</th>
            <th>Product ID</th>
            <th>Product Name</th>
            <th class="text-right">Qty</th>
            <th class="text-right">Cost Book<br>(IDR)</th>
            <th class="text-right">Total Nilai<br>(IDR)</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($rows)) : $no = 1; ?>
            <?php foreach ($rows as $r) : ?>
                <tr>
                    <td align="center"><?= $no++; ?></td>
                    <td><?= !empty($r->tgl_surat_jalan) ? date('d-M-Y', strtotime($r->tgl_surat_jalan)) : '-'; ?></td>
                    <td><?= htmlspecialchars($r->no_surat_jalan); ?></td>
                    <td><?= strtoupper(htmlspecialchars((string) $r->name_customer)); ?></td>
                    <td><?= htmlspecialchars((string) $r->id_product); ?></td>
                    <td><?= strtoupper(htmlspecialchars((string) $r->product)); ?></td>
                    <td align="right"><?= number_format((float) $r->qty, 2, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->costbook, 2, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->total_harga, 2, ',', '.'); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php elseif ($for_excel) : ?>
            <tr>
                <td colspan="9" align="center">Tidak ada data.</td>
            </tr>
        <?php endif; ?>
    </tbody>
    <?php if (!empty($rows)) : ?>
        <tfoot>
            <tr>
                <th colspan="6" style="text-align:right;">TOTAL</th>
                <th style="text-align:right;"><?= number_format($sum_qty, 2, ',', '.'); ?></th>
                <th></th>
                <th style="text-align:right;"><?= number_format($sum_total, 2, ',', '.'); ?></th>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>