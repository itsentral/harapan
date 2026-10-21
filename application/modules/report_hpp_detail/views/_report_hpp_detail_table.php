<?php

/**
 * Partial tabel Report HPP Detail.
 * Dipakai BERSAMA oleh tampilan layar (report_hpp_detail.php) dan download Excel
 * (report_hpp_detail_excel.php) agar kolom & isi selalu identik.
 *
 * Dua mode:
 *  - $for_excel = true  : render untuk Excel. Sub Total per invoice & Grand Total
 *                         ikut dirender di server (karena Excel tidak menjalankan JS).
 *  - $for_excel = false : render untuk layar + DataTables. Body HANYA berisi baris
 *                         data produk (tiap baris punya data-invoice & data-total),
 *                         lalu baris Sub Total disisipkan oleh JS (drawCallback) dan
 *                         Grand Total ditampilkan di footer. Lihat report_hpp_detail.php.
 *
 * Variabel yang dibutuhkan: $rows (array of object)
 * Variabel opsional: $for_excel (bool)
 */
$for_excel = isset($for_excel) ? $for_excel : false;

/**
 * Format tanggal aman (tanpa jam) untuk kolom tanggal.
 */
if (!function_exists('hpp_detail_fmt_date')) {
    function hpp_detail_fmt_date($val)
    {
        if (empty($val) || $val === '0000-00-00' || $val === '0000-00-00 00:00:00') {
            return '-';
        }
        $ts = strtotime($val);
        return $ts ? date('Y-m-d', $ts) : '-';
    }
}

// Hitung Grand Total (dipakai untuk mode Excel).
$grand_total = 0;
if (!empty($rows)) {
    foreach ($rows as $r) {
        $grand_total += (float) $r->total_hpp;
    }
}
?>
<table border="1" class="table table-bordered table-striped" id="tbl-hpp-detail" style="width:100%;">
    <thead>
        <tr>
            <th class="text-center">No. Invoice</th>
            <th class="text-center">Tgl Dibuat</th>
            <th class="text-center">Tgl Kirim</th>
            <th class="text-center">No. Surat Jalan</th>
            <th class="text-center">No. SO</th>
            <th class="text-center">ID Produk</th>
            <th class="text-center">Nama Produk</th>
            <th class="text-center">Qty</th>
            <th class="text-center">Costbook</th>
            <th class="text-center">Total</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($rows)) : ?>
            <?php
            $for_excel_invoice = null;   // untuk melacak pergantian invoice di mode Excel
            $for_excel_subtotal = 0;
            ?>
            <?php foreach ($rows as $i => $r) : ?>
                <?php if ($for_excel && $for_excel_invoice !== null && $r->id_invoice !== $for_excel_invoice) : ?>
                    <tr>
                        <th colspan="9" style="text-align:left;">Sub Total</th>
                        <th style="text-align:right;"><?= number_format($for_excel_subtotal, 2, ',', '.'); ?></th>
                    </tr>
                    <?php $for_excel_subtotal = 0; ?>
                <?php endif; ?>
                <tr<?= $for_excel ? '' : ' data-invoice="' . htmlspecialchars($r->id_invoice, ENT_QUOTES) . '" data-total="' . (float) $r->total_hpp . '"'; ?>>
                    <td><?= htmlspecialchars($r->id_invoice); ?></td>
                    <td><?= hpp_detail_fmt_date($r->created_on); ?></td>
                    <td><?= hpp_detail_fmt_date($r->delivery_date); ?></td>
                    <td><?= htmlspecialchars($r->id_delivery); ?></td>
                    <td><?= htmlspecialchars($r->id_so); ?></td>
                    <td><?= htmlspecialchars($r->id_produk); ?></td>
                    <td><?= strtoupper(htmlspecialchars($r->nm_produk)); ?></td>
                    <td align="right"><?= number_format((float) $r->qty, 0, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->harga_beli, 2, ',', '.'); ?></td>
                    <td align="right"><?= number_format((float) $r->total_hpp, 2, ',', '.'); ?></td>
                    </tr>
                    <?php
                    if ($for_excel) {
                        $for_excel_subtotal += (float) $r->total_hpp;
                        $for_excel_invoice = $r->id_invoice;
                    }
                    ?>
                <?php endforeach; ?>

                <?php if ($for_excel) : ?>
                    <?php // Sub Total untuk invoice terakhir 
                    ?>
                    <tr>
                        <th colspan="9" style="text-align:left;">Sub Total</th>
                        <th style="text-align:right;"><?= number_format($for_excel_subtotal, 2, ',', '.'); ?></th>
                    </tr>
                    <tr>
                        <th colspan="9" style="text-align:left;">Grand Total</th>
                        <th style="text-align:right;"><?= number_format($grand_total, 2, ',', '.'); ?></th>
                    </tr>
                <?php endif; ?>
            <?php elseif ($for_excel) : ?>
                <tr>
                    <td colspan="10" align="center">Tidak ada data.</td>
                </tr>
            <?php endif; ?>
    </tbody>
    <?php if (!$for_excel) : ?>
        <tfoot>
            <tr>
                <th colspan="9" style="text-align:left;">Grand Total</th>
                <th id="hpp-grand-total" style="text-align:right;">0,00</th>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>