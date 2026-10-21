<?php
$filename = 'report_hpp_detail_' . date('Ymd_His') . '.xls';
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=" . $filename);
header("Pragma: no-cache");
header("Expires: 0");

$basis_label = (isset($date_basis) && $date_basis === 'delivery') ? 'Tgl Kirim' : 'Tgl Dibuat';
?>
<table border="1">
    <tr>
        <th colspan="10" style="font-size:16px; text-align:center;">Report HPP Detail</th>
    </tr>
    <tr>
        <td colspan="10">
            HPP (costbook) tiap produk per invoice. Total = Qty x Costbook.
        </td>
    </tr>
    <tr>
        <td colspan="10">
            Filter Berdasarkan: <?= htmlspecialchars($basis_label) ?> &nbsp;|&nbsp;
            Periode: <?= $start_date ? htmlspecialchars($start_date) : 'Awal' ?> s/d <?= $end_date ? htmlspecialchars($end_date) : 'Akhir' ?>
        </td>
    </tr>
    <tr>
        <td colspan="10"></td>
    </tr>
</table>

<?php
// Pakai partial tabel yang sama dengan tampilan layar agar kolom & isi identik.
$this->load->view('_report_hpp_detail_table', ['rows' => $rows, 'for_excel' => true]);
