<style>
    .table-scrollable {
        max-height: 500px;
        overflow-y: auto;
        border: 1px solid #ccc;
    }

    #tbl-piutang-cust thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #337ab7;
        color: #fff;
        box-shadow: inset 0 1px 0 #fff, inset 0 -1px 0 #fff;
    }

    #tbl-piutang-cust tfoot td {
        position: sticky;
        bottom: 0;
        background-color: #f9f9f9;
        z-index: 10;
        border-top: 2px solid #ccc;
    }
</style>
<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-users"></i> Report Piutang Per Customer</h3>
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
                <button type="button" id="btn-excel" class="btn btn-success btn-sm" style="display:none;">
                    <i class="fa fa-file-excel-o"></i> Download Excel
                </button>
            </div>
        </div>

        <!-- Tabel Hasil -->
        <div id="result-area" style="display:none; margin-top:10px;">
            <div class="table-responsive table-scrollable">
                <table class="table table-bordered table-striped table-condensed" id="tbl-piutang-cust">
                    <thead>
                        <tr class="bg-blue" style="color:#fff;">
                            <th class="text-center" style="vertical-align:middle; width:50px;">No</th>
                            <th class="text-center" style="vertical-align:middle;">Customer</th>
                            <th class="text-center" style="vertical-align:middle;">Total Invoice</th>
                            <th class="text-center" style="vertical-align:middle;">Total Bayar</th>
                            <th class="text-center" style="vertical-align:middle;">Sisa Piutang</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-piutang"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right"><strong>Total Piutang</strong></td>
                            <td class="text-right" id="tfoot-total"><strong></strong></td>
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

<link rel="stylesheet" href="<?= base_url('assets/plugins/datepicker/datepicker3.css') ?>">
<script src="<?= base_url('assets/plugins/datepicker/bootstrap-datepicker.js') ?>"></script>

<script>
    var base_url = '<?= base_url() ?>';
    var active_controller = '<?= $this->uri->segment(1) ?>';

    $(document).ready(function() {

        $('#tanggal').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            todayHighlight: true
        });

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

            var parts = tgl_display.split('/');
            var tgl_server = parts[2] + '-' + parts[1] + '-' + parts[0];

            $.ajax({
                url: base_url + active_controller + '/get_data_customer',
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
                        $('#btn-excel').hide();
                        $('#total_piutang_display').val('');
                        return;
                    }

                    $('#no-data-area').hide();
                    renderTable(res.data, res.total_piutang);
                    $('#total_piutang_display').val(formatNumber(res.total_piutang));
                    $('#result-area').show();
                    $('#btn-excel').show().data('tanggal', tgl_server);
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

        $('#btn-excel').on('click', function() {
            var tgl = $(this).data('tanggal');
            window.location.href = base_url + active_controller + '/export_excel_customer/' + tgl;
        });

        function renderTable(data, total) {
            var tbody = '';
            $.each(data, function(i, row) {
                tbody += '<tr>';
                tbody += '<td class="text-center">' + (i + 1) + '</td>';
                tbody += '<td>' + escHtml(row.name_customer) + '</td>';
                tbody += '<td class="text-right">' + formatNumber(row.total_invoice) + '</td>';
                tbody += '<td class="text-right">' + formatNumber(row.total_bayar) + '</td>';
                tbody += '<td class="text-right">' + formatNumber(row.sisa_piutang) + '</td>';
                tbody += '</tr>';
            });
            $('#tbody-piutang').html(tbody);
            $('#tfoot-total strong').text(formatNumber(total));
        }

        function formatNumber(n) {
            if (n === '' || n === null || n === undefined) return '';
            return parseFloat(n).toLocaleString('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });
        }

        function escHtml(str) {
            if (!str) return '';
            return $('<div>').text(str).html();
        }
    });
</script>
