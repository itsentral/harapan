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
                        <th width="90">Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr class="active">
                        <th colspan="8" class="text-right">Total</th>
                        <th class="text-right" id="tfoot-debet"></th>
                        <th class="text-right" id="tfoot-kredit"></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

    </div>
</div>

<!-- Modal Edit Debet/Kredit -->
<div class="modal fade" id="modal-edit" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="form-edit">
                <div class="modal-header bg-warning">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-pencil"></i> Edit Debet / Kredit</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit-id" name="id">
                    <div class="form-group">
                        <label>Nomor</label>
                        <input type="text" id="edit-nomor" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <input type="text" id="edit-keterangan" class="form-control" readonly>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Debet</label>
                                <input type="text" id="edit-debet" name="debet" class="form-control text-right money" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kredit</label>
                                <input type="text" id="edit-kredit" name="kredit" class="form-control text-right money" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-simpan-edit">
                        <i class="fa fa-save"></i> Simpan
                    </button>
                </div>
            </form>
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
                { targets: [8, 9], className: 'text-right' },
                { targets: 10, orderable: false, searchable: false, className: 'text-center', width: '90px' }
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

    // Format ribuan untuk input uang
    function unformatMoney(v) {
        if (v === null || v === undefined) return '';
        return ('' + v).replace(/\./g, '').replace(/,/g, '.');
    }

    $('#modal-edit').on('input', '.money', function () {
        var raw = $(this).val().replace(/[^\d]/g, '');
        if (raw === '') { $(this).val(''); return; }
        $(this).val(parseInt(raw, 10).toLocaleString('id-ID'));
    });

    // Edit baris: buka modal & isi form
    $('#tbl-kartu').on('click', '.btn-edit', function () {
        var id     = $(this).data('id');
        var jenis  = $('#jenis').val();

        $.ajax({
            url: base_url + active_controller + '/get_detail',
            type: 'POST',
            dataType: 'json',
            data: { id: id, jenis: jenis },
            success: function (res) {
                if (!res.status) {
                    swal({ title: 'Gagal', text: res.message, type: 'error' });
                    return;
                }
                var d = res.data;
                $('#edit-id').val(d.id);
                $('#edit-nomor').val(d.nomor);
                $('#edit-keterangan').val(d.keterangan);
                $('#edit-debet').val(formatNumber(d.debet));
                $('#edit-kredit').val(formatNumber(d.kredit));
                $('#modal-edit').modal('show');
            },
            error: function () {
                swal({ title: 'Error', text: 'Gagal mengambil data.', type: 'error' });
            }
        });
    });

    // Simpan perubahan debet/kredit
    $('#form-edit').on('submit', function (e) {
        e.preventDefault();

        var id     = $('#edit-id').val();
        var jenis  = $('#jenis').val();
        var debet  = unformatMoney($('#edit-debet').val()) || 0;
        var kredit = unformatMoney($('#edit-kredit').val()) || 0;

        var $btn = $('#btn-simpan-edit');
        $btn.prop('disabled', true);

        $.ajax({
            url: base_url + active_controller + '/update',
            type: 'POST',
            dataType: 'json',
            data: { id: id, jenis: jenis, debet: debet, kredit: kredit },
            success: function (res) {
                if (res.status) {
                    $('#modal-edit').modal('hide');
                    swal({ title: 'Berhasil', text: res.message, type: 'success' });
                    if (dtKartu) dtKartu.ajax.reload(null, false);
                } else {
                    swal({ title: 'Gagal', text: res.message, type: 'error' });
                }
            },
            error: function () {
                swal({ title: 'Error', text: 'Koneksi gagal, coba lagi.', type: 'error' });
            },
            complete: function () {
                $btn.prop('disabled', false);
            }
        });
    });

    // Hapus baris (dipindahkan ke tabel _deleted)
    $('#tbl-kartu').on('click', '.btn-hapus', function () {
        var id    = $(this).data('id');
        var jenis = $('#jenis').val();

        swal({
            title: 'Hapus data ini?',
            text: 'Data akan dipindahkan ke tabel arsip (' +
                  (jenis === 'piutang' ? 'tr_kartu_piutang_deleted' : 'tr_kartu_hutang_deleted') + ').',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dd4b39',
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }, function (isConfirm) {
            if (!isConfirm) return;

            $.ajax({
                url: base_url + active_controller + '/delete',
                type: 'POST',
                dataType: 'json',
                data: { id: id, jenis: jenis },
                success: function (res) {
                    if (res.status) {
                        swal({ title: 'Berhasil', text: res.message, type: 'success' });
                        if (dtKartu) dtKartu.ajax.reload(null, false);
                    } else {
                        swal({ title: 'Gagal', text: res.message, type: 'error' });
                    }
                },
                error: function () {
                    swal({ title: 'Error', text: 'Koneksi gagal, coba lagi.', type: 'error' });
                }
            });
        });
    });
});
</script>
