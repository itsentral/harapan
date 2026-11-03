<?php
$filename = 'report_warehouse_intransit_' . date('Ymd_His') . '.xls';
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=" . $filename);
header("Pragma: no-cache");
header("Expires: 0");
?>
<table border="1">
    <tr>
        <th colspan="10" style="font-size:16px; text-align:center;">REPORT WAREHOUSE IN-TRANSIT</th>
    </tr>
    <tr>
        <td colspan="10">
            Stok barang in-transit — sudah dibuat Surat Jalan tetapi belum di-confirm.
        </td>
    </tr>
    <tr>
        <td colspan="10">
            Periode Tgl Masuk: <?= $start_date ? htmlspecialchars((string) $start_date) : 'Awal' ?> s/d <?= $end_date ? htmlspecialchars((string) $end_date) : 'Akhir' ?>
        </td>
    </tr>
    <tr>
        <td colspan="10"></td>
    </tr>
</table>

<?php
// Pakai partial tabel yang sama dengan tampilan layar agar kolom & isi identik.
$this->load->view('_report_warehouse_intransit_table', ['rows' => $rows, 'for_excel' => true]);
