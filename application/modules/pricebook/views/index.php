<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">

<div class="box box-primary">
    <div class="box-header clearfix">
        <span class="pull-left">
            <small class="text-muted">
                Harga sekarang diambil dari <b>warehouse_stock</b>, harga lalu dari <b>warehouse_stock_per_days</b> (filter tanggal backup).
            </small>
        </span>
        <span class="pull-right" style="max-width:250px">
            <div class="input-group">
                <span class="input-group-addon">Tgl Harga Lalu</span>
                <input type="text" name="tanggal" id="filterTanggal" class="form-control datepicker tanggal">
            </div>
        </span>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="tablePricebook">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Id Material</th>
                        <th>Product</th>
                        <th>Costbook Lalu</th>
                        <th>Costbook Sekarang</th>
                        <th>Selisih</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
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

        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
        });

        $('#filterTanggal').on('change', function() {
            DataTables();
        });
    });

    function DataTables() {
        $('#tablePricebook').DataTable({
            processing: true,
            serverSide: true,
            stateSave: true,
            autoWidth: false,
            destroy: true,
            searching: true,
            responsive: true,
            aaSorting: [
                [2, "asc"]
            ],
            columnDefs: [{
                targets: 'no-sort',
                orderable: false
            }],
            sPaginationType: "simple_numbers",
            iDisplayLength: 10,
            aLengthMenu: [
                [10, 20, 50, 100, 150],
                [10, 20, 50, 100, 150]
            ],
            ajax: {
                url: siteurl + active_controller + 'data_side_pricebook',
                type: "post",
                data: function(d) {
                    d.tanggal = $('#filterTanggal').val();
                },
                cache: false
            }
        });
    }
</script>
