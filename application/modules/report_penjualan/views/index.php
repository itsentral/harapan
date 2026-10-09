<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">

<div class="box box-primary">
    <div class="box-body">
        <div class="row" style="margin-bottom:10px;">
            <div class="col-md-2">
                <label>Dari</label>
                <input type="date" id="tgl_dari" class="form-control input-sm">
            </div>
            <div class="col-md-2">
                <label>Sampai</label>
                <input type="date" id="tgl_sampai" class="form-control input-sm">
            </div>
            <div class="col-md-2">
                <label>Sales</label>
                <select name="id_sales" class="form-control input-sm" id="id_sales" required <?= isset($komisi->id_karyawan) ? 'disabled' : '' ?>>
                    <option value="">-- Pilih Sales --</option>
                    <?php foreach ($sales as $s): ?>
                        <option
                            value="<?= $s['id'] ?>"
                            data-nama="<?= $s['nm_karyawan'] ?>"
                            <?= (isset($komisi->id_karyawan) && $komisi->id_karyawan == $s['id']) ? 'selected' : '' ?>>
                            <?= ucfirst($s['nm_karyawan']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3" style="padding-top:25px;">
                <button class="btn btn-primary btn-sm" id="btnFilter">Filter</button>
                <button class="btn btn-default btn-sm" id="btnReset">Reset</button>
                <button class="btn btn-success btn-sm" id="btnExportExcel">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered" id="example1">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nomor Invoice</th>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th>Total Invoice</th>
                        <th>Revenue (exclude PPn)</th>
                        <th>Total Bayar</th>
                        <th>Piutang</th>
                        <th>Umur (hr)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- DataTables -->
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script>

<script>
    $(document).ready(function() {
        DataTables()

        $('#btnFilter').on('click', function() {
            $('#example1').DataTable().ajax.reload();
        });

        $('#btnReset').on('click', function() {
            $('#tgl_dari').val('');
            $('#tgl_sampai').val('');
            $('#id_sales').val('');
            $('#example1').DataTable().ajax.reload();
        });

        $('#btnExportExcel').on('click', function() {
            var tgl_dari = $('#tgl_dari').val();
            var tgl_sampai = $('#tgl_sampai').val();
            var id_sales = $('#id_sales').val();
            var searchVal = $('#example1_filter input').val();

            var url = siteurl + active_controller + 'export_excel_report' +
                '?tgl_dari=' + encodeURIComponent(tgl_dari || '') +
                '&tgl_sampai=' + encodeURIComponent(tgl_sampai || '') +
                '&id_sales=' + encodeURIComponent(id_sales || '') +
                '&search=' + encodeURIComponent(searchVal || '');

            window.location = url;
        });
    });

    function DataTables() {
        var dataTable = $('#example1').DataTable({
            "processing": true,
            "serverSide": true,
            "stateSave": true,
            "autoWidth": false,
            "destroy": true,
            "searching": true,
            "responsive": true,
            "aaSorting": [
                [2, "desc"]
            ],
            "columnDefs": [{
                    "targets": [0, 9],
                    "orderable": false
                },
                {
                    "targets": 'no-sort',
                    "orderable": false
                }
            ],
            "sPaginationType": "simple_numbers",
            "iDisplayLength": 10,
            "aLengthMenu": [
                [10, 20, 50, 100, 150],
                [10, 20, 50, 100, 150]
            ],
            "ajax": {
                url: siteurl + active_controller + 'data_side_report',
                type: "post",
                data: function(d) {
                    d.tgl_dari = $('#tgl_dari').val();
                    d.tgl_sampai = $('#tgl_sampai').val();
                    d.id_sales = $('#id_sales').val();
                },
                cache: false,
                error: function() {
                    $(".my-grid-error").html("");
                    $("#my-grid").append('<tbody class="my-grid-error"><tr><th colspan="3">No data found in the server</th></tr></tbody>');
                    $("#my-grid_processing").css("display", "none");
                }
            }
        });
    }
</script>