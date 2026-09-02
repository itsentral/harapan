<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">

<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="fa fa-credit-card"></i> Monitoring Kartu Hutang &amp; Piutang</h3>
    </div>
    <div class="box-body">

        <!-- Filter -->
        <div class="row">
            <div class="col-md-2">
                <div class="form-group">
                    <label>Jenis Kartu</label>
                    <select id="jenis" class="form-control">
                        <option value="hutang">Kartu Hutang</option>
                        <option value="piutang">Kartu Piutang</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Tanggal Awal</label>
                    <input type="text" id="tgl_awal" class="form-control datepicker"
                           placeholder="dd/mm/yyyy" autocomplete="off" readonly>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>Tanggal Akhir</label>
                    <input type="text" id="tgl_akhir" class="form-control datepicker"
                           placeholder="dd/mm/yyyy" autocomplete="off" readonly>
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
                    <i class="fa fa-file-excel-o"></i> Excel
                </button>
            </div>
        </div>

        <!-- Tabel Hasil -->
        <div class="table-responsive" style="margin-top:10px;">
            <table class="table table-bordered table-striped" id="tbl-kartu" style="width:100%;">
                <thead>
                    <tr class="bg-blue">
                        <th width="30">No</th>
                        <th>Tanggal</th>
                        <th>Nomor</th>
                        <th>No Perkiraan</th>
                        <th>No Reff</th>
                        <th>Jenis Trans</th>
                        <th id="th-nama">Supplier</th>
                        <th>Keterangan</th>
                        <th>Debet</th>
                        <th>Kredit</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr class="active">
                        <th colspan="8" class="text-right">Total</th>
                        <th class="text-right" id="tfoot-debet"></th>
                        <th class="text-right" id="tfoot-kredit"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</div>

<link rel="stylesheet" href="<?= base_url('assets/plugins/datepicker/datepicker3.css') ?>">
<script src="<?= base_url('assets/plugins/datepicker/bootstrap-datepicker.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script>

<script>
var base_url = '<?= base_url() ?>';
var active_controller = '<?= $this->uri->segment(1) ?>';
var dtKartu = null;

$(document).ready(function () {

    $('.datepicker').datepicker({
        format: 'dd/mm/yyyy',
        autoclose: true,
        todayHighlight: true
    });

    // Ubah label kolom sesuai jenis
    $('#jenis').on('change', function () {
        $('#th-nama').text($(this).val() === 'piutang' ? 'Customer' : 'Supplier');
    });

    function toServerDate(display) {
        if (!display) return '';
        var p = display.split('/');
        return p[2] + '-' + p[1] + '-' + p[0];
    }

    function currentFilter() {
        return {
            jenis: $('#jenis').val(),
            tgl_awal: toServerDate($('#tgl_awal').val()),
            tgl_akhir: toServerDate($('#tgl_akhir').val())
        };
    }

    function formatNumber(n) {
        if (n === '' || n === null || n === undefined) return '0';
        return parseFloat(n).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    function loadTable() {
        var f = currentFilter();

        if (!f.tgl_awal || !f.tgl_akhir) {
            swal({ title: 'Perhatian', text: 'Isi tanggal awal dan akhir.', type: 'warning' });
            return;
        }
        if (f.tgl_awal > f.tgl_akhir) {
            swal({ title: 'Perhatian', text: 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.', type: 'warning' });
            return;
        }

        $('#th-nama').text(f.jenis === 'piutang' ? 'Customer' : 'Supplier');

        if (dtKartu) {
            dtKartu.ajax.reload();
            $('#btn-print, #btn-excel').show();
            return;
        }

        dtKartu = $('#tbl-kartu').DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            responsive: true,
            aaSorting: [[1, 'asc']],
            iDisplayLength: 25,
            aLengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            columnDefs: [
                { targets: 0, orderable: false, width: '30px' },
                { targets: [8, 9], className: 'text-right' }
            ],
            ajax: {
                url: base_url + active_controller + '/data',
                type: 'POST',
                data: function (d) {
                    var cf = currentFilter();
                    d.jenis     = cf.jenis;
                    d.tgl_awal  = cf.tgl_awal;
                    d.tgl_akhir = cf.tgl_akhir;
                },
                dataSrc: function (json) {
                    $('#tfoot-debet').text(formatNumber(json.total_debet));
                    $('#tfoot-kredit').text(formatNumber(json.total_kredit));
                    return json.data;
                },
                error: function () {
                    swal({ title: 'Error', text: 'Gagal memuat data.', type: 'error' });
                }
            }
        });

        $('#btn-print, #btn-excel').show();
    }

    $('#btn-cari').on('click', loadTable);

    function buildQuery() {
        var f = currentFilter();
        return 'jenis=' + encodeURIComponent(f.jenis) +
               '&tgl_awal=' + encodeURIComponent(f.tgl_awal) +
               '&tgl_akhir=' + encodeURIComponent(f.tgl_akhir) +
               '&keyword=' + encodeURIComponent(dtKartu ? dtKartu.search() : '');
    }

    $('#btn-print').on('click', function () {
        window.open(base_url + active_controller + '/print_report?' + buildQuery(), '_blank');
    });

    $('#btn-excel').on('click', function () {
        window.location.href = base_url + active_controller + '/export_excel?' + buildQuery();
    });
});
</script>
