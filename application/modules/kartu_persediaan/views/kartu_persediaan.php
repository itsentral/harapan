<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">
<style>
    #table-rekap th,
    #table-detail th {
        text-align: center;
        vertical-align: middle !important;
    }

    /* Pertegas border tabel (default tema terlalu tipis/pudar). */
    #table-rekap,
    #table-detail {
        border-collapse: collapse;
    }

    #table-rekap>thead>tr>th,
    #table-rekap>tbody>tr>td,
    #table-detail>thead>tr>th,
    #table-detail>tbody>tr>td {
        border: 1px solid #9aa0a6 !important;
    }

    /* Header lebih tegas. */
    #table-rekap>thead>tr>th,
    #table-detail>thead>tr>th {
        border-color: #6c757d !important;
    }

    .kp-detail-box {
        display: none;
    }

    .kp-total-row td {
        font-weight: bold;
        background: #f5f5f5;
        border-top: 2px solid #6c757d !important;
    }

    .kp-saldo-awal td {
        font-style: italic;
        background: #fcfce8;
    }

    /* Garis pemisah TEBAL antar grup kolom (DEBET | KREDIT | SALDO),
       supaya pengelompokan jelas seperti di Excel. Posisi kolom berbeda
       antara mode admin dan non-admin, jadi di-generate oleh PHP. */
    <?php if (!empty($is_admin)): ?>

    /* REKAP admin: KREDIT mulai kolom 6, SALDO mulai kolom 8, Aksi kolom 11 */
    #table-rekap>tbody>tr>td:nth-child(6),
    #table-rekap>tbody>tr>td:nth-child(8),
    #table-rekap>tbody>tr>td:nth-child(11) {
        border-left: 2px solid #495057 !important;
    }

    /* DETAIL admin: KREDIT mulai kolom 7, SALDO mulai kolom 10 */
    #table-detail>tbody>tr>td:nth-child(7),
    #table-detail>tbody>tr>td:nth-child(10) {
        border-left: 2px solid #495057 !important;
    }

    <?php else: ?>

    /* REKAP non-admin: KREDIT mulai kolom 5, SALDO mulai kolom 6, Aksi kolom 7 */
    #table-rekap>tbody>tr>td:nth-child(5),
    #table-rekap>tbody>tr>td:nth-child(6),
    #table-rekap>tbody>tr>td:nth-child(7) {
        border-left: 2px solid #495057 !important;
    }

    /* DETAIL non-admin: KREDIT mulai kolom 5, SALDO mulai kolom 6 */
    #table-detail>tbody>tr>td:nth-child(5),
    #table-detail>tbody>tr>td:nth-child(6) {
        border-left: 2px solid #495057 !important;
    }

    <?php endif; ?>

    /* Pemisah grup pada header (baris pertama: label grup). */
    #table-rekap>thead>tr:first-child>th,
    #table-detail>thead>tr:first-child>th {
        border: 2px solid #495057 !important;
    }
</style>

<!-- ===================== FILTER + REKAP ===================== -->
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-book"></i> Kartu Persediaan (Rekap)</h3>
    </div>
    <div class="box-header">
        <table>
            <tr>
                <td><label class="form-label">Saldo Awal Sejak</label></td>
                <td style="padding-left:8px;"><input type="date" id="anchor_date" class="form-control input-sm" title="Tanggal awal pembukuan. Kosongkan untuk hitung dari awal data."></td>
                <td style="padding: 0 16px;"></td>
                <td><label class="form-label">Periode Transaksi</label></td>
                <td style="padding-left:8px;"><input type="date" id="start_date" class="form-control input-sm"></td>
                <td style="padding: 0 8px;"><i class="fa fa-arrow-right"></i></td>
                <td><input type="date" id="end_date" class="form-control input-sm"></td>
                <td style="padding-left: 8px;">
                    <button id="btnFilter" class="btn bg-purple btn-sm"><i class="fa fa-filter"></i> Filter</button>
                    <button id="btnReset" class="btn btn-default btn-sm">Reset</button>
                    <a id="btnExportRekap" href="javascript:void(0)" class="btn btn-success btn-sm">
                        <i class="fa fa-file-excel-o"></i> Export Rekap
                    </a>
                </td>
            </tr>
        </table>
        <p class="text-muted" style="margin-top:6px;">
            <small>
                <b>Saldo Awal Sejak</b>: batas awal akumulasi saldo awal (mis. 1 Jan 2026). Kosongkan = hitung dari awal data.
                <b>Periode Transaksi</b>: rentang transaksi yang ditampilkan di kartu. Klik <b>Detail</b> untuk kartu per barang.
            </small>
        </p>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table id="table-rekap" class="table table-bordered table-striped">
                <thead class="bg-blue">
                    <tr>
                        <th rowspan="2">#</th>
                        <th rowspan="2">Kode Barang</th>
                        <th rowspan="2">Nama Barang</th>
                        <th colspan="<?= !empty($is_admin) ? 2 : 1 ?>">DEBET</th>
                        <th colspan="<?= !empty($is_admin) ? 2 : 1 ?>">KREDIT</th>
                        <th colspan="<?= !empty($is_admin) ? 3 : 1 ?>">SALDO</th>
                        <th rowspan="2">Aksi</th>
                    </tr>
                    <tr>
                        <th>Qty</th>
                        <?php if (!empty($is_admin)): ?><th>Total</th><?php endif; ?>
                        <th>Qty</th>
                        <?php if (!empty($is_admin)): ?><th>Total</th><?php endif; ?>
                        <th>Qty</th>
                        <?php if (!empty($is_admin)): ?><th>Harga</th>
                            <th>Total</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== DETAIL PER BARANG ===================== -->
<div class="box box-success kp-detail-box" id="detailBox">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-file-text-o"></i> Detail Kartu: <span id="detailNama">-</span></h3>
        <div class="box-tools pull-right">
            <a id="btnExportDetail" href="javascript:void(0)" class="btn btn-success btn-xs">
                <i class="fa fa-file-excel-o"></i> Export Detail
            </a>
            <button type="button" class="btn btn-default btn-xs" id="btnCloseDetail"><i class="fa fa-times"></i> Tutup</button>
        </div>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table id="table-detail" class="table table-bordered table-striped">
                <thead class="bg-green">
                    <tr>
                        <th rowspan="2">TGL</th>
                        <th rowspan="2">NO BUKTI</th>
                        <th rowspan="2">Keterangan</th>
                        <th colspan="<?= !empty($is_admin) ? 3 : 1 ?>">DEBET</th>
                        <th colspan="<?= !empty($is_admin) ? 3 : 1 ?>">KREDIT</th>
                        <th colspan="<?= !empty($is_admin) ? 3 : 1 ?>">SALDO</th>
                    </tr>
                    <tr>
                        <th>Qty</th>
                        <?php if (!empty($is_admin)): ?><th>Harga</th>
                            <th>Total</th><?php endif; ?>
                        <th>Qty</th>
                        <?php if (!empty($is_admin)): ?><th>Harga</th>
                            <th>Total</th><?php endif; ?>
                        <th>Qty</th>
                        <?php if (!empty($is_admin)): ?><th>Harga</th>
                            <th>Total</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="detailBody"></tbody>
            </table>
        </div>
    </div>
</div>

<!-- DataTables -->
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script>

<script>
    var IS_ADMIN = <?= !empty($is_admin) ? 'true' : 'false' ?>;

    function nf(x) {
        x = parseFloat(x) || 0;
        return x.toLocaleString('en-US');
    }

    function nf2(x) {
        x = parseFloat(x) || 0;
        return x.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    $(document).ready(function() {
        buildRekap();

        $('#btnFilter').on('click', function(e) {
            e.preventDefault();
            if ($.fn.dataTable.isDataTable('#table-rekap')) {
                $('#table-rekap').DataTable().ajax.reload(null, true);
            }
            $('#detailBox').hide();
        });

        $('#btnReset').on('click', function(e) {
            e.preventDefault();
            $('#start_date, #end_date, #anchor_date').val('');
            if ($.fn.dataTable.isDataTable('#table-rekap')) {
                $('#table-rekap').DataTable().ajax.reload(null, true);
            }
            $('#detailBox').hide();
        });

        $('#btnExportRekap').on('click', function(e) {
            e.preventDefault();
            var start = $('#start_date').val();
            var end = $('#end_date').val();
            var anchor = $('#anchor_date').val();
            window.location.href = base_url + active_controller + 'export_rekap?start_date=' + start + '&end_date=' + end + '&anchor_date=' + anchor;
        });

        // Klik tombol detail (delegasi karena baris dibuat dinamis).
        $('#table-rekap tbody').on('click', '.btn-detail', function() {
            var code = $(this).data('code');
            var nama = $(this).data('nama');
            loadDetail(code, nama);
        });

        $('#btnCloseDetail').on('click', function() {
            $('#detailBox').hide();
        });

        $('#btnExportDetail').on('click', function(e) {
            e.preventDefault();
            var code = $('#detailBox').data('code');
            if (!code) return;
            var start = $('#start_date').val();
            var end = $('#end_date').val();
            var anchor = $('#anchor_date').val();
            window.location.href = base_url + active_controller + 'export_detail?code=' + encodeURIComponent(code) +
                '&start_date=' + start + '&end_date=' + end + '&anchor_date=' + anchor;
        });
    });

    function buildRekap() {
        $('#table-rekap').DataTable({
            "processing": true,
            "serverSide": true,
            "stateSave": false,
            "autoWidth": false,
            "destroy": true,
            "responsive": true,
            "ordering": false,
            "sPaginationType": "simple_numbers",
            "iDisplayLength": 10,
            "aLengthMenu": [
                [10, 20, 50, 100],
                [10, 20, 50, 100]
            ],
            "ajax": {
                url: base_url + active_controller + 'data_side_rekap',
                type: "post",
                data: function(d) {
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
                    d.anchor_date = $('#anchor_date').val();
                },
                cache: false,
                error: function() {
                    alert('Gagal memuat data rekap.');
                }
            }
        });
    }

    function loadDetail(code, nama) {
        $('#detailNama').text(nama + ' (' + code + ')');
        $('#detailBox').data('code', code).show();
        $('#detailBody').html('<tr><td colspan="12" align="center"><i class="fa fa-spinner fa-spin"></i> Memuat...</td></tr>');

        $('html, body').animate({
            scrollTop: $('#detailBox').offset().top - 60
        }, 300);

        $.ajax({
            url: base_url + active_controller + 'data_detail',
            type: 'get',
            dataType: 'json',
            data: {
                code: code,
                start_date: $('#start_date').val(),
                end_date: $('#end_date').val(),
                anchor_date: $('#anchor_date').val()
            },
            success: function(res) {
                renderDetail(res);
            },
            error: function() {
                $('#detailBody').html('<tr><td colspan="12" align="center" class="text-red">Gagal memuat detail.</td></tr>');
            }
        });
    }

    function renderDetail(res) {
        var colTotal = IS_ADMIN ? 12 : 6;
        var html = '';

        if (!res.rows || res.rows.length === 0) {
            $('#detailBody').html('<tr><td colspan="' + colTotal + '" align="center">Tidak ada data.</td></tr>');
            return;
        }

        res.rows.forEach(function(row) {
            var cls = row.is_saldo_awal ? ' class="kp-saldo-awal"' : '';
            var tgl = row.tgl_transaksi ? String(row.tgl_transaksi).substr(0, 10) : '';
            html += '<tr' + cls + '>';
            html += '<td>' + tgl + '</td>';
            html += '<td>' + (row.no_transaksi || '') + '</td>';
            html += '<td>' + (row.transaksi || '') + '</td>';
            // DEBET
            html += '<td align="right">' + (row.debet_qty ? nf(row.debet_qty) : '') + '</td>';
            if (IS_ADMIN) {
                html += '<td align="right">' + (row.debet_qty ? nf2(row.debet_harga) : '') + '</td>';
                html += '<td align="right">' + (row.debet_qty ? nf2(row.debet_total) : '') + '</td>';
            }
            // KREDIT
            html += '<td align="right">' + (row.kredit_qty ? nf(row.kredit_qty) : '') + '</td>';
            if (IS_ADMIN) {
                html += '<td align="right">' + (row.kredit_qty ? nf2(row.kredit_harga) : '') + '</td>';
                html += '<td align="right">' + (row.kredit_qty ? nf2(row.kredit_total) : '') + '</td>';
            }
            // SALDO
            html += '<td align="right">' + nf(row.saldo_qty) + '</td>';
            if (IS_ADMIN) {
                html += '<td align="right">' + nf2(row.saldo_harga) + '</td>';
                html += '<td align="right">' + nf2(row.saldo_total) + '</td>';
            }
            html += '</tr>';
        });

        // Baris TOTAL
        var t = res.total || {};
        html += '<tr class="kp-total-row">';
        html += '<td colspan="3" align="center">TOTAL</td>';
        html += '<td align="right">' + nf(t.debet_qty) + '</td>';
        if (IS_ADMIN) {
            html += '<td></td>';
            html += '<td align="right">' + nf2(t.debet_total) + '</td>';
        }
        html += '<td align="right">' + nf(t.kredit_qty) + '</td>';
        if (IS_ADMIN) {
            html += '<td></td>';
            html += '<td align="right">' + nf2(t.kredit_total) + '</td>';
        }
        html += '<td align="right">' + nf(t.saldo_qty) + '</td>';
        if (IS_ADMIN) {
            html += '<td align="right">' + nf2(t.saldo_harga) + '</td>';
            html += '<td align="right">' + nf2(t.saldo_total) + '</td>';
        }
        html += '</tr>';

        $('#detailBody').html(html);
    }
</script>