<style>
    .table-scrollable {
        max-height: 500px;
        overflow-y: auto;
        border: 1px solid #ccc;
    }

    #tbl-aging thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #337ab7;
        color: #fff;
        box-shadow: inset 0 1px 0 #fff, inset 0 -1px 0 #fff;
    }

    #tbl-aging tfoot td {
        position: sticky;
        bottom: 0;
        background-color: #f9f9f9;
        z-index: 10;
        border-top: 2px solid #ccc;
        font-weight: bold;
    }
</style>

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-clock-o"></i> Report Umur Hutang (Aging)</h3>
    </div>
    <div class="box-body">

        <!-- Filter / Aksi -->
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Per Tanggal</label>
                    <input type="text" id="tanggal_hari_ini" class="form-control" readonly
                        style="background:#eee; font-weight:bold;">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Total Sisa Hutang</label>
                    <input type="text" id="total_hutang_display"
                        class="form-control text-right"
                        readonly
                        style="background:#d9edf7; font-weight:bold; color:#31708f;">
                </div>
            </div>
            <div class="col-md-6" style="padding-top:25px;">
                <button type="button" id="btn-cari" class="btn btn-primary btn-sm">
                    <i class="fa fa-search"></i> Tampilkan
                </button>
                <button type="button" id="btn-print" class="btn btn-warning btn-sm" style="display:none;">
                    <i class="fa fa-print"></i> Print
                </button>
                <button type="button" id="btn-excel" class="btn btn-success btn-sm" style="display:none;">
                    <i class="fa fa-file-excel-o"></i> Download Excel
                </button>
            </div>
        </div>

        <!-- Tabel Hasil -->
        <div id="result-area" style="display:none; margin-top:10px;">
            <div class="table-responsive table-scrollable">
                <table class="table table-bordered table-striped table-condensed" id="tbl-aging">
                    <thead>
                        <tr class="bg-blue" style="color:#fff;">
                            <th class="text-center" style="vertical-align:middle;">Supplier</th>
                            <th class="text-center" style="vertical-align:middle;">Tanggal Invoice</th>
                            <th class="text-center" style="vertical-align:middle;">No PO</th>
                            <th class="text-center" style="vertical-align:middle;">No Invoice</th>
                            <th class="text-center" style="vertical-align:middle;">Sisa Hutang</th>
                            <th class="text-center" style="vertical-align:middle;">Tgl Terakhir Bayar</th>
                            <th class="text-center" style="vertical-align:middle;">&lt; 30 hari</th>
                            <th class="text-center" style="vertical-align:middle;">&lt; 60 hari</th>
                            <th class="text-center" style="vertical-align:middle;">&lt; 90 hari</th>
                            <th class="text-center" style="vertical-align:middle;">&lt; 120 hari</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-aging"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right">Total</td>
                            <td class="text-right" id="tfoot-sisa"></td>
                            <td></td>
                            <td class="text-right" id="tfoot-30"></td>
                            <td class="text-right" id="tfoot-60"></td>
                            <td class="text-right" id="tfoot-90"></td>
                            <td class="text-right" id="tfoot-120"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div id="no-data-area" style="display:none;">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> Tidak ada data hutang.
            </div>
        </div>

    </div><!-- /.box-body -->
</div><!-- /.box -->

<script>
    var base_url = '<?= base_url() ?>';
    var active_controller = '<?= $this->uri->segment(1) ?>';

    $(document).ready(function() {

        // Tampilkan tanggal hari ini
        (function() {
            $('#tanggal_hari_ini').val(formatDate(new Date()));
        })();

        $('#btn-cari').on('click', function() {
            $.ajax({
                url: base_url + active_controller + '/get_data',
                type: 'POST',
                dataType: 'json',
                beforeSend: function() {
                    $('#btn-cari').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
                },
                success: function(res) {
                    $('#btn-cari').prop('disabled', false).html('<i class="fa fa-search"></i> Tampilkan');

                    if (!res.status) {
                        swal({ title: 'Error', text: res.message || 'Gagal memuat data.', type: 'error' });
                        return;
                    }

                    if (res.data.length === 0) {
                        $('#result-area').hide();
                        $('#no-data-area').show();
                        $('#btn-print, #btn-excel').hide();
                        $('#total_hutang_display').val('');
                        return;
                    }

                    $('#no-data-area').hide();
                    renderTable(res.data, res.total_sisa_hutang, res.bucket_totals);
                    $('#total_hutang_display').val(formatNumber(res.total_sisa_hutang));
                    $('#result-area').show();
                    $('#btn-print, #btn-excel').show();
                },
                error: function() {
                    $('#btn-cari').prop('disabled', false).html('<i class="fa fa-search"></i> Tampilkan');
                    swal({ title: 'Error', text: 'Koneksi gagal, coba lagi.', type: 'error' });
                }
            });
        });

        $('#btn-print').on('click', function() {
            window.open(base_url + active_controller + '/print_report', '_blank');
        });

        $('#btn-excel').on('click', function() {
            window.location.href = base_url + active_controller + '/export_excel';
        });

        function renderTable(data, total, b) {
            var tbody = '';
            $.each(data, function(i, row) {
                tbody += '<tr>';
                tbody += '<td>' + escHtml(row.nm_supplier) + '</td>';
                tbody += '<td class="text-center">' + formatDate(row.tgl_invoice) + '</td>';
                tbody += '<td class="text-center">' + escHtml(row.no_po) + '</td>';
                tbody += '<td class="text-center">' + escHtml(row.id_invoice) + '</td>';
                tbody += '<td class="text-right">' + formatNumber(row.sisa_hutang) + '</td>';
                tbody += '<td class="text-center">' + (row.tgl_terakhir_bayar ? formatDate(row.tgl_terakhir_bayar) : '-') + '</td>';
                tbody += '<td class="text-right">' + formatNumber(row.b_30) + '</td>';
                tbody += '<td class="text-right">' + formatNumber(row.b_60) + '</td>';
                tbody += '<td class="text-right">' + formatNumber(row.b_90) + '</td>';
                tbody += '<td class="text-right">' + formatNumber(row.b_120) + '</td>';
                tbody += '</tr>';
            });
            $('#tbody-aging').html(tbody);

            $('#tfoot-sisa').text(formatNumber(total));
            $('#tfoot-30').text(formatNumber(b['<30']));
            $('#tfoot-60').text(formatNumber(b['<60']));
            $('#tfoot-90').text(formatNumber(b['<90']));
            $('#tfoot-120').text(formatNumber(b['<120']));
        }

        function formatNumber(n) {
            if (n === '' || n === null || n === undefined) return '0';
            return parseFloat(n).toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        function formatDate(d) {
            if (!d) return '';
            var dt = new Date(d);
            if (isNaN(dt)) return d;
            var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return dt.getDate() + ' ' + months[dt.getMonth()] + ' ' + dt.getFullYear();
        }

        function escHtml(str) {
            if (!str) return '';
            return $('<div>').text(str).html();
        }
    });
</script>
