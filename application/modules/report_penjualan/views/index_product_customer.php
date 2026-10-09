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
            <div class="col-md-4" style="padding-top:25px;">
                <button class="btn btn-primary btn-sm" id="btnFilter">Filter</button>
                <button class="btn btn-default btn-sm" id="btnReset">Reset</button>
                <button class="btn btn-success btn-sm" id="btnExportExcel">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered" id="example1" style="width:100%;">
                <thead>
                    <tr>
                        <th style="width:40%;">Nama Barang</th>
                        <th>Pelanggan</th>
                        <th style="width:12%;">Kuantitas</th>
                        <th style="width:15%;">Penjualan</th>
                        <th style="width:15%;">Revenue (exclude PPn)</th>
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
            $('#example1').DataTable().ajax.reload();
        });

        $('#btnExportExcel').on('click', function() {
            var tgl_dari = $('#tgl_dari').val();
            var tgl_sampai = $('#tgl_sampai').val();
            var searchVal = $('#example1_filter input').val();

            var url = siteurl + active_controller + 'export_excel_barang_per_pelanggan' +
                '?tgl_dari=' + encodeURIComponent(tgl_dari || '') +
                '&tgl_sampai=' + encodeURIComponent(tgl_sampai || '') +
                '&search=' + encodeURIComponent(searchVal || '');

            window.location = url;
        });
    });

    function DataTables() {
        $('#example1').DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            searching: true,
            ordering: false, // biar grouping subtotal tidak kacau
            paging: false, // penting: subtotal = total sebenarnya (bukan per halaman)
            info: false,
            autoWidth: false,
            responsive: false,

            ajax: {
                url: siteurl + active_controller + 'data_side_barang_per_pelanggan',
                type: "post",
                data: function(d) {
                    d.tgl_dari = $('#tgl_dari').val();
                    d.tgl_sampai = $('#tgl_sampai').val();
                },
                cache: false
            },

            columns: [{
                    data: 'nama_barang'
                },
                {
                    data: 'pelanggan'
                },
                {
                    data: 'kuantitas',
                    className: 'text-right'
                },
                {
                    data: 'penjualan',
                    className: 'text-right'
                },
                {
                    data: 'penjualan_dpp',
                    className: 'text-right'
                }
            ],

            createdRow: function(row, data) {
                if (data.DT_RowClass) $(row).addClass(data.DT_RowClass);
            }
        });
    }
</script>