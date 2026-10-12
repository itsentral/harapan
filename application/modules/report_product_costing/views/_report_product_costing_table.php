<?php

/**
 * Partial tabel Report Product Costing.
 * Dipakai BERSAMA oleh tampilan layar (report_product_costing.php) dan
 * download Excel (report_product_costing_excel.php) agar kolom & isi selalu identik.
 *
 * Variabel yang dibutuhkan: $rows (array of object)
 * Variabel opsional: $for_excel (bool) - true saat dipakai untuk export Excel.
 */
$status_map = [
    'A'  => 'Approved',
    'WA' => 'Waiting Approval',
    'R'  => 'Rejected',
];
// Di tampilan layar, biarkan DataTables yang menampilkan pesan kosong-nya
// sendiri (baris colspan akan membuat DataTables error). Baris "Tidak ada data"
// hanya dirender untuk Excel.
$for_excel = isset($for_excel) ? $for_excel : false;
?>
<table border="1" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>ID</th>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Product Type</th>
            <th class="text-right">Harga Beli<br>(IDR)</th>
            <th>Status</th>
            <th class="text-right">Revisi</th>
            <th>Created By</th>
            <th>Modified By</th>
            <th>Created At</th>
            <th>Modified At</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($rows)) : $no = 1; ?>
            <?php foreach ($rows as $r) : ?>
                <tr>
                    <td align="center"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($r->id); ?></td>
                    <td><?= htmlspecialchars($r->code_lv4); ?></td>
                    <td><?= strtoupper(htmlspecialchars($r->product_name)); ?></td>
                    <td><?= strtoupper(htmlspecialchars($r->product_type)); ?></td>
                    <td align="right"><?= number_format((float) $r->harga_beli, 2, ',', '.'); ?></td>
                    <td><?= isset($status_map[$r->status]) ? $status_map[$r->status] : htmlspecialchars($r->status); ?></td>
                    <td align="right"><?= (int) $r->revisi; ?></td>
                    <td><?= htmlspecialchars($r->created_by_name); ?></td>
                    <td><?= htmlspecialchars($r->modified_by_name); ?></td>
                    <td><?= !empty($r->created_at) ? date('d-M-Y H:i:s', strtotime($r->created_at)) : '-'; ?></td>
                    <td><?= !empty($r->modified_at) ? date('d-M-Y H:i:s', strtotime($r->modified_at)) : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        <?php elseif ($for_excel) : ?>
            <tr>
                <td colspan="12" align="center">Tidak ada data.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
