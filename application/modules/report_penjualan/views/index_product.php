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
                        <th style="width: 10px;">No</th>
                        <th>Produk</th>
                        <th>Satuan</th>
                        <th>Kuantitas</th>
                        <th>Penjualan</th>
                        <th>Revenue (exclude PPn)</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr style="font-weight:bold; background:#f5f5f5;">
                        <th colspan="3" class="text-center">Total Nama Barang</th>
                        <th class="text-right" id="footQty">0</th>
                        <th class="text-right" id="footSales">0</th>
                        <th class="text-right" id="footSalesDpp">0</th>
                    </tr>
                </tfoot>
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

            var url = siteurl + active_controller + 'export_excel_product' +
                '?tgl_dari=' + encodeURIComponent(tgl_dari || '') +
                '&tgl_sampai=' + encodeURIComponent(tgl_sampai || '') +
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
                [1, "asc"]
            ],
            "columnDefs": [{
                    "targets": [0],
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
                url: siteurl + active_controller + 'data_side_product',
                type: "post",
                data: function(d) {
                    d.tgl_dari = $('#tgl_dari').val();
                    d.tgl_sampai = $('#tgl_sampai').val();
                },
                dataSrc: function(json) {
                    // isi footer grand total dari response
                    if (json && json.grandTotal) {
                        $('#footQty').text(json.grandTotal.qty_total);
                        $('#footSales').text(json.grandTotal.penjualan_total);
                        $('#footSalesDpp').text(json.grandTotal.penjualan_total_dpp);
                    } else {
                        $('#footQty').text('0');
                        $('#footSales').text('0');
                        $('#footSalesDpp').text('0');
                    }
                    return json.data;
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