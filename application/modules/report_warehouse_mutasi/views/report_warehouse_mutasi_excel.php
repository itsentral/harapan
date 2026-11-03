<?php
$filename = 'report_warehouse_mutasi_' . date('Ymd_His') . '.xls';
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=" . $filename);
header("Pragma: no-cache");
header("Expires: 0");
?>
<table border="1">
    <tr>
        <th colspan="14" style="font-size:16px; text-align:center;">REPORT MUTASI WAREHOUSE STOCK</th>
    </tr>
    <tr>
        <td colspan="14">
            Ledger perpindahan stok antar tahap: CREATE (&rarr; In-Transit), CONFIRM (&rarr; In-Customer), INVOICE (&rarr; Invoiced).
        </td>
    </tr>
    <tr>
        <td colspan="14">
            Periode Tgl Event: <?= $start_date ? htmlspecialchars((string) $start_date) : 'Awal' ?> s/d <?= $end_date ? htmlspecialchars((string) $end_date) : 'Akhir' ?>
            <?= !empty($event) ? ' | Event: ' . htmlspecialchars((string) $event) : '' ?>
        </td>
    </tr>
    <tr>
        <td colspan="14"></td>
    </tr>
</table>

<?php
// Pakai partial tabel yang sama dengan tampilan layar agar kolom & isi identik.
$this->load->view('_report_warehouse_mutasi_table', ['rows' => $rows, 'for_excel' => true]);
