<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Umur Hutang</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
        }

        .page-wrapper { padding: 20px 30px; }

        .report-header { text-align: center; margin-bottom: 16px; }
        .report-header h2 {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .report-header p { font-size: 11px; margin-top: 4px; }

        .info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #f0f0f0;
            border: 1px solid #ccc;
        }
        .info-bar .label-val { font-size: 11px; }
        .info-bar .label-val span { font-weight: bold; }
        .info-bar .total-box {
            font-size: 12px;
            font-weight: bold;
            color: #1a5276;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        thead tr th {
            background: #1a5276;
            color: #fff;
            padding: 6px 8px;
            text-align: center;
            border: 1px solid #999;
            font-size: 10px;
        }
        tbody tr td {
            padding: 5px 8px;
            border: 1px solid #ccc;
            vertical-align: middle;
            font-size: 10px;
        }
        tbody tr:nth-child(even) td { background: #f9f9f9; }
        tfoot tr td {
            padding: 6px 8px;
            border: 1px solid #999;
            font-weight: bold;
            font-size: 11px;
            background: #eaf2fb;
        }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .text-left   { text-align: left; }

        .print-footer {
            margin-top: 20px;
            font-size: 10px;
            color: #555;
            text-align: right;
        }

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
        <h2>Report Umur Hutang (Aging)</h2>
        <p>Per Tanggal: <strong><?= date('d F Y', strtotime($tanggal)) ?></strong></p>
    </div>

    <?php
    function fmt_num($n) {
        return number_format((float)$n, 0, ',', '.');
    }
    function fmt_tgl($d) {
        if (empty($d)) return '-';
        $months_id = [
            1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
            7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'
        ];
        $ts = strtotime($d);
        return date('d', $ts) . ' ' . $months_id[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    }
    ?>

    <div class="info-bar">
        <div class="label-val">
            Tanggal Cetak: <span><?= date('d/m/Y H:i') ?></span>
        </div>
        <div class="total-box">
            Total Sisa Hutang: Rp <?= fmt_num($total_sisa_hutang) ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:18%;">Supplier</th>
                <th style="width:9%;">Tgl Invoice</th>
                <th style="width:12%;">No PO</th>
                <th style="width:13%;">No Invoice</th>
                <th style="width:12%;">Sisa Hutang</th>
                <th style="width:10%;">Tgl Terakhir Bayar</th>
                <th style="width:8%;">&lt; 30 hari</th>
                <th style="width:8%;">&lt; 60 hari</th>
                <th style="width:8%;">&lt; 90 hari</th>
                <th style="width:8%;">&lt; 120 hari</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($data_report)): ?>
            <tr>
                <td colspan="10" class="text-center">Tidak ada data hutang.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($data_report as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['nm_supplier']) ?></td>
                <td class="text-center"><?= fmt_tgl($row['tgl_invoice']) ?></td>
                <td class="text-center"><?= htmlspecialchars($row['no_po']) ?></td>
                <td class="text-center"><?= htmlspecialchars($row['id_invoice']) ?></td>
                <td class="text-right"><?= fmt_num($row['sisa_hutang']) ?></td>
                <td class="text-center"><?= fmt_tgl($row['tgl_terakhir_bayar']) ?></td>
                <td class="text-right"><?= fmt_num($row['b_30']) ?></td>
                <td class="text-right"><?= fmt_num($row['b_60']) ?></td>
                <td class="text-right"><?= fmt_num($row['b_90']) ?></td>
                <td class="text-right"><?= fmt_num($row['b_120']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Total</td>
                <td class="text-right"><?= fmt_num($total_sisa_hutang) ?></td>
                <td></td>
                <td class="text-right"><?= fmt_num($bucket_totals['<30']) ?></td>
                <td class="text-right"><?= fmt_num($bucket_totals['<60']) ?></td>
                <td class="text-right"><?= fmt_num($bucket_totals['<90']) ?></td>
                <td class="text-right"><?= fmt_num($bucket_totals['<120']) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="print-footer">
        Dicetak pada: <?= date('d/m/Y H:i:s') ?>
    </div>

</div>
</body>
</html>
