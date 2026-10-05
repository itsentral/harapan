<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Piutang Per Invoice</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #000;
            background: #fff;
        }

        .page-wrapper {
            padding: 20px 30px;
        }

        /* Header */
        .report-header {
            text-align: center;
            margin-bottom: 16px;
        }
        .report-header h2 {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .report-header p {
            font-size: 11px;
            margin-top: 4px;
        }

        /* Info bar */
        .info-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding: 8px 12px;
            background: #f0f0f0;
            border: 1px solid #ccc;
        }
        .info-bar .label-val {
            font-size: 11px;
        }
        .info-bar .label-val span {
            font-weight: bold;
        }
        .info-bar .total-box {
            font-size: 12px;
            font-weight: bold;
            color: #1a5276;
        }

        /* Table */
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
        tbody tr:nth-child(even) td {
            background: #f9f9f9;
        }
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

        /* Footer */
        .print-footer {
            margin-top: 20px;
            font-size: 10px;
            color: #555;
            text-align: right;
        }

        /* Print controls */
        .no-print {
            margin-bottom: 12px;
        }
        @media print {
            .no-print { display: none !important; }
            body { font-size: 10px; }
            .page-wrapper { padding: 10px 15px; }
        }
    </style>
</head>
<body>
<div class="page-wrapper">

    <!-- Print / Download buttons (hidden on print) -->
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

    <!-- Report Header -->
    <div class="report-header">
        <h2>Report Piutang Per Invoice</h2>
        <p>Per Tanggal: <strong><?= date('d F Y', strtotime($tanggal)) ?></strong></p>
    </div>

    <!-- Info Bar -->
    <div class="info-bar">
        <div class="label-val">
            Tanggal Cetak: <span><?= date('d/m/Y H:i') ?></span>
        </div>
        <div class="total-box">
            Total Piutang: Rp <?= number_format($total_piutang, 0, ',', '.') ?>
        </div>
    </div>

    <!-- Data Table -->
    <table>
        <thead>
            <tr>
                <th style="width:16%;">Customer</th>
                <th style="width:9%;">Tgl Invoice</th>
                <th style="width:12%;">No Invoice</th>
                <th style="width:10%;">Nilai Invoice</th>
                <th style="width:12%;">Kode Penerimaan</th>
                <th style="width:9%;">Tgl Bayar</th>
                <th style="width:9%;">Tgl Dibuat</th>
                <th style="width:10%;">Nilai Bayar</th>
                <th style="width:10%;">Total Bayar</th>
                <th style="width:10%;">Sisa Piutang</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($data_report)): ?>
            <tr>
                <td colspan="10" class="text-center">Tidak ada data piutang.</td>
            </tr>
        <?php else: ?>
            <?php
            $months_id = [
                1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',5=>'Mei',6=>'Jun',
                7=>'Jul',8=>'Agu',9=>'Sep',10=>'Okt',11=>'Nov',12=>'Des'
            ];
            function fmt_tgl($d, $months_id) {
                if (empty($d)) return '';
                $ts = strtotime($d);
                return date('d', $ts) . ' ' . $months_id[(int)date('n', $ts)] . ' ' . date('Y', $ts);
            }
            function fmt_num($n) {
                if ($n === '' || $n === null) return '';
                return number_format((float)$n, 0, ',', '.');
            }
            ?>
            <?php foreach ($data_report as $row): ?>
            <tr>
                <?php if ($row['is_first_row']): ?>
                <td rowspan="<?= $row['rowspan'] ?>"><?= htmlspecialchars($row['name_customer']) ?></td>
                <td rowspan="<?= $row['rowspan'] ?>" class="text-center"><?= fmt_tgl($row['tgl_invoice'], $months_id) ?></td>
                <td rowspan="<?= $row['rowspan'] ?>" class="text-center"><?= htmlspecialchars($row['id_invoice']) ?></td>
                <td rowspan="<?= $row['rowspan'] ?>" class="text-right"><?= fmt_num($row['nilai_invoice']) ?></td>
                <?php endif; ?>
                <td class="text-center"><?= htmlspecialchars($row['kd_pembayaran']) ?></td>
                <td class="text-center"><?= fmt_tgl($row['tgl_bayar'], $months_id) ?></td>
                <td class="text-center"><?= fmt_tgl($row['tgl_dibuat'], $months_id) ?></td>
                <td class="text-right"><?= fmt_num($row['nilai_bayar']) ?></td>
                <td class="text-right"><?= fmt_num($row['total_bayar']) ?></td>
                <td class="text-right"><?= fmt_num($row['sisa_piutang']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="9" class="text-right">Total Piutang</td>
                <td class="text-right"><?= number_format($total_piutang, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="print-footer">
        Dicetak pada: <?= date('d/m/Y H:i:s') ?>
    </div>

</div>
</body>
</html>
