<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">
<style>
    th {
        text-align: center;
        vertical-align: middle !important;
    }
</style>

<div class="box box-primary">
    <div class="box-header">
        <div class="row">
            <div class="col-md-10">
                <table>
                    <tr>
                        <td><label class="form-label">Pilih Tanggal Transaksi</label></td>
                        <td><input type="date" id="start_date" class="form-control input-sm"></td>
                        <td style="padding: 0 8px;"><i class="fa fa-arrow-right"></i></td>
                        <td><input type="date" id="end_date" class="form-control input-sm"></td>
                        <td style="padding-left: 8px;">
                            <button id="btnFilter" class="btn bg-purple btn-sm">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                            <button id="btnReset" class="btn btn-default btn-sm">
                                Reset
                            </button>
                            <a id="btnExport" href="javascript:void(0)" class="btn btn-success btn-sm">
                                <i class="fa fa-file-excel-o"></i> Export Excel
                            </a>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <div class="table-responsive">
            <table id="table-kartu-stok" class="table table-bordered table-striped">
                <thead class="bg-blue">
                    <tr>
                        <th rowspan='2'>#</th>
                        <th rowspan='2'>Tgl Transaksi</th>
                        <th rowspan='2'>No.Transaksi</th>
                        <th rowspan='2'>Jenis Transaksi</th>
                        <th rowspan='2'>Id Produk</th>
                        <th rowspan='2'>Produk</th>
                        <th colspan='3'>AWAL</th>
                        <th colspan='2'>TRANSAKSI</th>
                        <th colspan='3'>AKHIR</th>
                        <?php if (!empty($is_admin)): ?>
                            <th rowspan='2'>Harga Beli</th>
                        <?php endif; ?>

                    </tr>
                    <tr>
                        <th>Stock</th>
                        <th>Booking</th>
                        <th>Free Stock</th>
                        <th>In/Out</th>
                        <th>Booking</th>
                        <th>Stock</th>
                        <th>Booking</th>
                        <th>Free Stock</th>
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
        DataTables();

        $('#btnFilter').on('click', function(e) {
            e.preventDefault();
            if ($.fn.dataTable.isDataTable('#table-kartu-stok')) {
                $('#table-kartu-stok').DataTable().ajax.reload(null, true);
            }
        });

        $('#btnReset').on('click', function(e) {
            e.preventDefault();
            $('#start_date, #end_date').val('');
            if ($.fn.dataTable.isDataTable('#table-kartu-stok')) {
                $('#table-kartu-stok').DataTable().ajax.reload(null, true);
            }
        });

        $('#btnExport').on('click', function(e) {
            e.preventDefault();
            var start = $('#start_date').val();
            var end = $('#end_date').val();
            window.location.href = base_url + active_controller + 'export_excel_kartu_stok?start_date=' + start + '&end_date=' + end;
        });
    });

    function DataTables(status = null) {
        var dataTable = $('#table-kartu-stok').DataTable({
            "processing": true,
            "serverSide": true,
            "stateSave": true,
            "autoWidth": false,
            "destroy": true,
            "responsive": true,
            "aaSorting": [
                [1, "asc"]
            ],
            "columnDefs": [{
                "targets": 'no-sort',
                "orderable": false,
            }],
            "sPaginationType": "simple_numbers",
            "iDisplayLength": 10,
            "aLengthMenu": [
                [10, 20, 50, 100, 150],
                [10, 20, 50, 100, 150]
            ],
            "ajax": {
                url: base_url + active_controller + 'data_side_kartu_stok',
                type: "post",
                data: function(d) {
                    d.status = status;
                    d.start_date = $('#start_date').val();
                    d.end_date = $('#end_date').val();
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