<?php
$filename = 'report_costbook_surat_jalan_' . date('Ymd_His') . '.xls';
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=" . $filename);
header("Pragma: no-cache");
header("Expires: 0");
?>
<table border="1">
    <tr>
        <th colspan="16" style="font-size:16px; text-align:center;">REPORT COSTBOOK SURAT JALAN</th>
    </tr>
    <tr>
        <td colspan="16">
            Costbook surat jalan yang sudah dibuat tetapi belum di-confirm (status ON DELIVERY).
        </td>
    </tr>
    <tr>
        <td colspan="16">
            Periode Created At: <?= $start_date ? htmlspecialchars($start_date) : 'Awal' ?> s/d <?= $end_date ? htmlspecialchars($end_date) : 'Akhir' ?>
        </td>
    </tr>
    <tr>
        <td colspan="16"></td>
    </tr>
</table>

<?php
// Pakai partial tabel yang sama dengan tampilan layar agar kolom & isi identik.
$this->load->view('_report_costbook_surat_jalan_table', ['rows' => $rows, 'for_excel' => true]);
