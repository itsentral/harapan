<style>
    /* Mengatur area scroll tabel */
    .table-scrollable {
        max-height: 500px;
        /* Tentukan tinggi maksimal tabel sebelum muncul scroll */
        overflow-y: auto;
        /* Munculkan scroll vertikal jika data melebihi max-height */
        border: 1px solid #ccc;
    }

    /* Membuat Header Tabel tetap di atas saat di-scroll (Sticky Header) */
    #tbl-piutang thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #337ab7;
        /* Warna biru sesuai class bg-blue AdminLTE */
        color: #fff;
        box-shadow: inset 0 1px 0 #fff, inset 0 -1px 0 #fff;
    }

    /* Memastikan footer juga tetap terlihat di bawah jika diinginkan */
    #tbl-piutang tfoot td {
        position: sticky;
        bottom: 0;
        background-color: #f9f9f9;
        z-index: 10;
        border-top: 2px solid #ccc;
    }
</style>
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-file-text-o"></i> Report Piutang Per Invoice</h3>
    </div>
    <div class="box-body">

        <!-- Filter -->
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="text" id="tanggal" name="tanggal"
                        class="form-control datepicker"
                        placeholder="Pilih tanggal..."
                        autocomplete="off" readonly>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Total Piutang</label>
                    <input type="text" id="total_piutang_display"
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
                <!-- Tombol Download Summary disembunyikan untuk sementara -->
                <button type="button" id="btn-summary" class="btn btn-info btn-sm" style="display:none;" hidden>
                    <i class="fa fa-file-text-o"></i> Download Summary
                </button>
            </div>
        </div>

        <!-- Tabel Hasil -->
        <div id="result-area" style="display:none; margin-top:10px;">
            <div class="table-responsive table-scrollable">
                <table class="table table-bordered table-striped table-condensed" id="tbl-piutang">
                    <thead>
                        <tr class="bg-blue" style="color:#fff;">
                            <th class="text-center" style="vertical-align:middle;">Customer</th>
                            <th class="text-center" style="vertical-align:middle;">Tanggal Invoice</th>
                            <th class="text-center" style="vertical-align:middle;">No Invoice</th>
                            <th class="text-center" style="vertical-align:middle;">Nilai Invoice</th>
                            <th class="text-center" style="vertical-align:middle;">Kode Penerimaan</th>
                            <th class="text-center" style="vertical-align:middle;">Tanggal Bayar</th>
                            <th class="text-center" style="vertical-align:middle;">Tanggal Dibuat</th>
                            <th class="text-center" style="vertical-align:middle;">Nilai Bayar</th>
                            <th class="text-center" style="vertical-align:middle;">Total Bayar</th>
                            <th class="text-center" style="vertical-align:middle;">Sisa Piutang</th>
                            <th class="text-center" style="vertical-align:middle;">Saldo Piutang</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-piutang">
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="9" class="text-right"><strong>Total Piutang</strong></td>
                            <td class="text-right" id="tfoot-total"><strong></strong></td>
                            <td class="text-right" id="tfoot-total-saldo"><strong></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div id="no-data-area" style="display:none;">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> Tidak ada data piutang per tanggal yang dipilih.
            </div>
        </div>

    </div><!-- /.box-body -->
</div><!-- /.box -->

<!-- Datepicker CSS (jika belum di-load global) -->
<link rel="stylesheet" href="<?= base_url('assets/plugins/datepicker/datepicker3.css') ?>">

<script src="<?= base_url('assets/plugins/datepicker/bootstrap-datepicker.js') ?>"></script>

<script>
    var base_url = '<?= base_url() ?>';
    var active_controller = '<?= $this->uri->segment(1) ?>';

    $(document).ready(function() {

        // Init datepicker
        $('#tanggal').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });

        // Tombol Tampilkan
        $('#btn-cari').on('click', function() {
            var tgl_display = $('#tanggal').val();
            if (!tgl_display) {
                swal({
                    title: 'Perhatian',
                    text: 'Pilih tanggal terlebih dahulu.',
                    type: 'warning'
                });
                return;
            }

            // Konversi dd/mm/yyyy -> yyyy-mm-dd untuk dikirim ke server
            var parts = tgl_display.split('/');
            var tgl_server = parts[2] + '-' + parts[1] + '-' + parts[0];

            $.ajax({
                url: base_url + active_controller + '/get_data',
                type: 'POST',
                dataType: 'json',
                data: {
                    tanggal: tgl_server
                },
                beforeSend: function() {
                    $('#btn-cari').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');
                },
                success: function(res) {
                    $('#btn-cari').prop('disabled', false).html('<i class="fa fa-search"></i> Tampilkan');

                    if (!res.status) {
                        swal({
                            title: 'Error',
                            text: res.message,
                            type: 'error'
                        });
                        return;
                    }

                    if (res.data.length === 0) {
                        $('#result-area').hide();
                        $('#no-data-area').show();
                        $('#btn-print, #btn-excel').hide();
                        $('#total_piutang_display').val('');
                        return;
                    }

                    $('#no-data-area').hide();
                    renderTable(res.data, res.total_piutang, res.total_saldo_piutang);
                    $('#total_piutang_display').val(formatNumber(res.total_piutang));
                    $('#result-area').show();
                    $('#btn-print, #btn-excel').show().data('tanggal', tgl_server);
                },
                error: function() {
                    $('#btn-cari').prop('disabled', false).html('<i class="fa fa-search"></i> Tampilkan');
                    swal({
                        title: 'Error',
                        text: 'Koneksi gagal, coba lagi.',
                        type: 'error'
                    });
                }
            });
        });

        // Tombol Print
        $('#btn-print').on('click', function() {
            var tgl = $(this).data('tanggal');
            window.open(base_url + active_controller + '/print_report/' + tgl, '_blank');
        });

        // Tombol Excel
        $('#btn-excel').on('click', function() {
            var tgl = $(this).data('tanggal');
            window.location.href = base_url + active_controller + '/export_excel/' + tgl;
        });

        // Tombol Download Summary (1 baris per invoice)
        $('#btn-summary').on('click', function() {
            var tgl = $(this).data('tanggal');
            window.location.href = base_url + active_controller + '/export_summary/' + tgl;
        });

        function renderTable(data, total, totalSaldo) {
            var tbody = '';
            var prevInvoice = null;

            $.each(data, function(i, row) {
                tbody += '<tr>';

                if (row.is_first_row) {
                    tbody += '<td rowspan="' + row.rowspan + '">' + escHtml(row.name_customer) + '</td>';
                    tbody += '<td rowspan="' + row.rowspan + '" class="text-center">' + formatDate(row.tgl_invoice) + '</td>';
                    tbody += '<td rowspan="' + row.rowspan + '" class="text-center">' + escHtml(row.id_invoice) + '</td>';
                    tbody += '<td rowspan="' + row.rowspan + '" class="text-right">' + formatNumber(row.nilai_invoice) + '</td>';
                }

                tbody += '<td class="text-center">' + (row.kd_pembayaran ? escHtml(row.kd_pembayaran) : '') + '</td>';
                tbody += '<td class="text-center">' + (row.tgl_bayar ? formatDate(row.tgl_bayar) : '') + '</td>';
                tbody += '<td class="text-center">' + (row.tgl_dibuat ? formatDate(row.tgl_dibuat) : '') + '</td>';
                tbody += '<td class="text-right">' + (row.nilai_bayar !== '' ? formatNumber(row.nilai_bayar) : '') + '</td>';
                tbody += '<td class="text-right">' + (row.total_bayar !== '' ? formatNumber(row.total_bayar) : '') + '</td>';
                tbody += '<td class="text-right">' + formatNumber(row.sisa_piutang) + '</td>';
                tbody += '<td class="text-right">' + (row.saldo_piutang !== '' ? formatNumber(row.saldo_piutang) : '') + '</td>';
                tbody += '</tr>';
            });

            $('#tbody-piutang').html(tbody);
            $('#tfoot-total strong').text(formatNumber(total));
            $('#tfoot-total-saldo strong').text(formatNumber(totalSaldo));
        }

        function formatNumber(n) {
            if (n === '' || n === null || n === undefined) return '';
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