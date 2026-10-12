<?php
$filename = 'report_product_costing_' . date('Ymd_His') . '.xls';
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=" . $filename);
header("Pragma: no-cache");
header("Expires: 0");

$status_label = [
    'A'  => 'Approved',
    'WA' => 'Waiting Approval',
    'R'  => 'Rejected',
];
$periode_label = ($date_field == 'modified_at') ? 'Modified At' : 'Created At';
?>
<table border="1">
    <tr>
        <th colspan="12" style="font-size:16px; text-align:center;">REPORT PRODUCT COSTING</th>
    </tr>
    <tr>
        <td colspan="12">
            Basis Tanggal: <?= $periode_label ?> |
            Periode: <?= $start_date ? htmlspecialchars($start_date) : 'Awal' ?> s/d <?= $end_date ? htmlspecialchars($end_date) : 'Akhir' ?> |
            Status: <?= $status ? (isset($status_label[$status]) ? $status_label[$status] : $status) : 'Semua' ?>
        </td>
    </tr>
    <tr><td colspan="12"></td></tr>
</table>

<?php
// Pakai partial tabel yang sama dengan tampilan layar agar kolom & isi identik.
$this->load->view('_report_product_costing_table', ['rows' => $rows, 'for_excel' => true]);
