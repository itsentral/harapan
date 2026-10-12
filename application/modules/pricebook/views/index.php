<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">

<div class="row">
    <div class="col-md-12">
        <div class="box box-solid">
            <div class="box-body">
                <h4 style="margin-top:0;margin-bottom:10px"><b>Total Inventory</b></h4>
                <div id="totalInventoryBox"
                     style="background:#d9edf7;border:1px solid #bce8f1;border-radius:4px;padding:18px 20px;font-size:22px;font-weight:bold;color:#31708f">
                    0
                </div>
            </div>
        </div>
    </div>
</div>

<div class="box box-primary">
    <div class="box-header clearfix">
        <span class="pull-left">
            <small class="text-muted">
                Kosongkan tanggal untuk harga sekarang. Pilih tanggal untuk harga lalu.
            </small>
        </span>
        <span class="pull-right">
            <div class="input-group" style="max-width:250px;display:inline-table;vertical-align:middle">
                <span class="input-group-addon">Tanggal</span>
                <input type="text" name="tanggal" id="filterTanggal" class="form-control datepicker tanggal">
            </div>
            <button type="button" id="btnExportExcel" class="btn btn-success" style="margin-left:5px">
                <i class="fa fa-file-excel-o"></i> Download Excel
            </button>
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
                        <th>Gudang</th>
                        <th>Qty</th>
                        <th>Costbook</th>
                        <th>Total Nilai</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" style="text-align:right">Grand Total</th>
                        <th id="grandTotalPricebook" style="text-align:right">0</th>
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
        DataTables();
        loadTotalInventory();

        $('.datepicker').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true,
        });

        $('#filterTanggal').on('change', function() {
            DataTables();
            loadTotalInventory();
        });

        $('#btnExportExcel').on('click', function() {
            var tanggal = $('#filterTanggal').val() || '';
            window.location.href = siteurl + active_controller + 'export_excel?tanggal=' + encodeURIComponent(tanggal);
        });
    });

    function loadTotalInventory() {
        $('#totalInventoryBox').text('...');
        $.ajax({
            url: siteurl + active_controller + 'total_inventory',
            type: 'post',
            dataType: 'json',
            data: {
                tanggal: $('#filterTanggal').val()
            },
            success: function(res) {
                $('#totalInventoryBox').text((res && res.totalFmt !== undefined) ? res.totalFmt : '0');
            },
            error: function() {
                $('#totalInventoryBox').text('0');
            }
        });
    }

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
                dataSrc: function(json) {
                    var gt = (json && json.grandTotalFmt !== undefined) ? json.grandTotalFmt : '0';
                    $('#grandTotalPricebook').text(gt);
                    return json.data;
                },
                cache: false
            }
        });
    }
</script>
