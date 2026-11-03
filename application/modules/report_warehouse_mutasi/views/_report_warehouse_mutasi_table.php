<?php

/**
 * Partial tabel Report Mutasi Warehouse Stock.
 * Dipakai BERSAMA oleh tampilan layar (report_warehouse_mutasi.php) dan
 * download Excel (report_warehouse_mutasi_excel.php) agar kolom & isi selalu identik.
 *
 * Variabel yang dibutuhkan: $rows (array of object)
 * Variabel opsional: $for_excel (bool) - true saat dipakai untuk export Excel.
 */
$for_excel = isset($for_excel) ? $for_excel : false;

// Label tahap agar NULL tampil rapi.
$tahap_label = function ($v) {
    $v = trim((string) $v);
    return $v === '' ? '-' : $v;
};

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
            <th>Tgl Event</th>
            <th>Event</th>
            <th>Dari</th>
            <th>Ke</th>
            <th>No SO</th>
            <th>No Surat Jalan</th>
            <th>Customer</th>
            <th>Product ID</th>
            <th>Product Name</th>
            <th class="text-right">Qty</th>
            <th class="text-right">Cost Book<br>(IDR)</th>
            <th class="text-right">Total Harga<br>(IDR)</th>
            <th>Ref</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($rows)) : $no = 1; ?>
            <?php foreach ($rows as $r) : ?>
                <tr>
                    <td align="center"><?= $no++; ?></td>
                    <td><?= !empty($r->tgl_event) ? date('d-M-Y H:i', strtotime($r->tgl_event)) : '-'; ?></td>
                    <td><?= htmlspecialchars((string) $r->event); ?></td>
                    <td><?= $tahap_label($r->dari_tahap); ?></td>
                    <td><?= $tahap_label($r->ke_tahap); ?></td>
                    <td><?= htmlspecialchars((string) $r->no_so); ?></td>
                    <td><?= htmlspecialchars((string) $r->no_surat_jalan); ?></td>
                    <td><?= strtoupper(htmlspecialchars((string) $r->name_customer)); ?></td>
                    <td><?= htmlspecialchars((string) $r->id_product); ?></td>
                    <td><?= strtoupper(htmlspecialchars((string) $r->product)); ?></td>
                    <td align="right"><?= number_format((float) $r->qty, 2, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->costbook, 2, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->total_harga, 2, ',', '.'); ?></td>
                    <td><?= htmlspecialchars((string) $r->ref_no); ?></td>
                </tr>
            <?php endforeach; ?>
        <?php elseif ($for_excel) : ?>
            <tr>
                <td colspan="14" align="center">Tidak ada data.</td>
            </tr>
        <?php endif; ?>
    </tbody>
    <?php if (!empty($rows)) : ?>
        <tfoot>
            <tr>
                <th colspan="10" style="text-align:right;">TOTAL</th>
                <th style="text-align:right;"><?= number_format($sum_qty, 2, ',', '.'); ?></th>
                <th></th>
                <th style="text-align:right;"><?= number_format($sum_total, 2, ',', '.'); ?></th>
                <th></th>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>
