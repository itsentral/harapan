<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">

<div class="box box-primary">
    <div class="box-header">
        <a id="btnExport" href="javascript:void(0)" class="btn btn-success btn-sm">
            <i class="fa fa-file-excel-o"></i> Export Excel
        </a>
    </div>
    <!-- /.box-header -->
    <div class="box-body">
        <div class="table-responsive">
            <table id="table-costbook-costing" class="table table-bordered table-striped">
                <thead class="bg-blue">
                    <th>No</th>
                    <th>Kode Costing</th>
                    <th>Kode Product</th>
                    <th>Nama Product</th>
                    <th>Costbook</th>
                    <th>Status</th>
                    <th>Modified At</th>
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

        $('#btnExport').on('click', function(e) {
            e.preventDefault();
            window.location.href = base_url + active_controller + 'export_excel';
        });
    });

    function DataTables(status = null) {
        var dataTable = $('#table-costbook-costing').DataTable({
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
                url: base_url + active_controller + 'data_side_costbook_costing',
                type: "post",
                data: function(d) {
                    d.status = status
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