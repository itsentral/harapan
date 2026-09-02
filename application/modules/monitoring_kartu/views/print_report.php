<?php
$label   = ($jenis === 'hutang') ? 'Hutang' : 'Piutang';
$namaCol = ($jenis === 'hutang') ? 'Supplier' : 'Customer';

$months_id = [
    1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
    7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'
];
if (!function_exists('mk_fmt_tgl')) {
    function mk_fmt_tgl($d, $months_id) {
        if (empty($d)) return '';
        $ts = strtotime($d);
        return date('d', $ts) . ' ' . $months_id[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }
}
if (!function_exists('mk_fmt_num')) {
    function mk_fmt_num($n) {
        if ($n === '' || $n === null) return '0';
        return number_format((float)$n, 0, ',', '.');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Kartu <?= $label ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background: #fff; }
        .page-wrapper { padding: 20px 30px; }
        .report-header { text-align: center; margin-bottom: 16px; }
        .report-header h2 { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .report-header p { font-size: 11px; margin-top: 4px; }
        .info-bar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 12px; padding: 8px 12px; background: #f0f0f0; border: 1px solid #ccc;
        }
        .info-bar .label-val { font-size: 11px; }
        .info-bar .label-val span { font-weight: bold; }
        .info-bar .total-box { font-size: 12px; font-weight: bold; color: #1a5276; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        thead tr th {
            background: #1a5276; color: #fff; padding: 6px 8px; text-align: center;
            border: 1px solid #999; font-size: 10px;
        }
        tbody tr td { padding: 5px 8px; border: 1px solid #ccc; vertical-align: middle; font-size: 10px; }
        tbody tr:nth-child(even) td { background: #f9f9f9; }
        tfoot tr td { padding: 6px 8px; border: 1px solid #999; font-weight: bold; font-size: 11px; background: #eaf2fb; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .text-left   { text-align: left; }
        .print-footer { margin-top: 20px; font-size: 10px; color: #555; text-align: right; }
        .no-print { margin-bottom: 12px; }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 10px; }
            .page-wrapper { padding: 10px 15px; }
        }
    </style>
</head>
<body>
<div class="page-wrapper">

    <div class="no-print" style="text-align:right;">
        <button onclick="window.print()"
                style="padding:6px 14px; background:#1a5276; color:#fff; border:none; border-radius:3px; cursor:pointer; font-size:11px; margin-right:6px;">
            &#128438; Print
        </button>
        <button onclick="window.close()"
                style="padding:6px 14px; background:#888; color:#fff; border:none; border-radius:3px; cursor:pointer; font-size:11px;">
            &#10005; Tutup
        </button>
    </div>

    <div class="report-header">
        <h2>Monitoring Kartu <?= $label ?></h2>
        <p>Periode: <strong><?= mk_fmt_tgl($tgl_awal, $months_id) ?></strong> s/d <strong><?= mk_fmt_tgl($tgl_akhir, $months_id) ?></strong></p>
        <?php if (!empty($keyword)): ?>
            <p>Filter: <strong><?= htmlspecialchars($keyword) ?></strong></p>
        <?php endif; ?>
    </div>

    <div class="info-bar">
        <div class="label-val">
            Tanggal Cetak: <span><?= date('d/m/Y H:i') ?></span>
        </div>
        <div class="total-box">
            Total Debet: Rp <?= mk_fmt_num($total_debet) ?> &nbsp;|&nbsp; Total Kredit: Rp <?= mk_fmt_num($total_kredit) ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:4%;">No</th>
                <th style="width:9%;">Tanggal</th>
                <th style="width:12%;">Nomor</th>
                <th style="width:9%;">No Perkiraan</th>
                <th style="width:11%;">No Reff</th>
                <th style="width:10%;">Jenis Trans</th>
                <th style="width:15%;"><?= $namaCol ?></th>
                <th style="width:16%;">Keterangan</th>
                <th style="width:7%;">Debet</th>
                <th style="width:7%;">Kredit</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($data_report)): ?>
            <tr>
                <td colspan="10" class="text-center">Tidak ada mutasi pada periode ini.</td>
            </tr>
        <?php else: ?>
            <?php $no = 1; foreach ($data_report as $row): ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= mk_fmt_tgl($row['tanggal'], $months_id) ?></td>
                <td><?= htmlspecialchars($row['nomor']) ?></td>
                <td class="text-center"><?= htmlspecialchars($row['no_perkiraan']) ?></td>
                <td><?= htmlspecialchars($row['no_reff']) ?></td>
                <td><?= htmlspecialchars($row['jenis_trans']) ?></td>
                <td><?= htmlspecialchars($row['nama']) ?></td>
                <td><?= htmlspecialchars($row['keterangan']) ?></td>
                <td class="text-right"><?= mk_fmt_num($row['debet']) ?></td>
                <td class="text-right"><?= mk_fmt_num($row['kredit']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" class="text-right">Total</td>
                <td class="text-right"><?= mk_fmt_num($total_debet) ?></td>
                <td class="text-right"><?= mk_fmt_num($total_kredit) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="print-footer">
        Dicetak pada: <?= date('d/m/Y H:i:s') ?>
    </div>

</div>
</body>
</html>
