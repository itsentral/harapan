<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">

<style>
    #tblPenjualanHpp thead th {
        white-space: nowrap;
        font-size: 12px;
    }
    #tblPenjualanHpp tbody td {
        font-size: 12px;
        vertical-align: middle;
    }
    .footer-total td {
        font-weight: bold;
        background-color: #ffffcc !important;
    }
</style>

<div class="box box-primary">
    <div class="box-body">
        <!-- FILTER -->
        <div class="row" style="margin-bottom:10px;">
            <div class="col-md-2">
                <label>Tgl Awal</label>
                <input type="date" id="tgl_dari" class="form-control input-sm">
            </div>
            <div class="col-md-2">
                <label>Tgl Akhir</label>
                <input type="date" id="tgl_sampai" class="form-control input-sm">
            </div>
            <div class="col-md-4" style="padding-top:25px;">
                <button class="btn btn-primary btn-sm" id="btnFilter">
                    <i class="fa fa-search"></i> Filter
                </button>
                <button class="btn btn-default btn-sm" id="btnReset">
                    <i class="fa fa-refresh"></i> Reset
                </button>
                <button class="btn btn-success btn-sm" id="btnExportExcel">
                    <i class="fa fa-file-excel-o"></i> Export Excel
                </button>
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="tblPenjualanHpp" style="width:100%">
                <thead>
                    <tr>
                        <th class="text-center" style="width:35px">No</th>
                        <th class="text-center">NOMOR SO</th>
                        <th class="text-center">Nama Customer</th>
                        <th class="text-center">Nama Sales</th>
                        <th class="text-center">TGL INVOICE</th>
                        <th class="text-center">NO INVOICE</th>
                        <th class="text-center">ID BARANG</th>
                        <th class="text-center">NAMA BARANG</th>
                        <th class="text-center">QTY</th>
                        <th class="text-center">Hrga jual satuan</th>
                        <th class="text-center">COSTBOOK HPP</th>
                        <th class="text-center">HARGA JUAL</th>
                        <th class="text-center">HPP</th>
                        <th class="text-center">LABA/RUGI KOTOR</th>
                        <th class="text-center">PERSEN LABA/RUGI</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr class="footer-total">
                        <td colspan="11" class="text-center"><strong>TOTAL</strong></td>
                        <td class="text-right" id="footPenjualanPPN">0</td>
                        <td class="text-right" id="footHPP">0</td>
                        <td class="text-right" id="footLaba">0</td>
                        <td class="text-center" id="footPersenLaba">0%</td>
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
$(document).ready(function () {
    initDataTable();

    $('#btnFilter').on('click', function () {
        $('#tblPenjualanHpp').DataTable().ajax.reload();
    });

    $('#btnReset').on('click', function () {
        $('#tgl_dari').val('');
        $('#tgl_sampai').val('');
        $('#tblPenjualanHpp').DataTable().ajax.reload();
    });

    $('#btnExportExcel').on('click', function () {
        var tgl_dari  = $('#tgl_dari').val();
        var tgl_sampai = $('#tgl_sampai').val();
        var searchVal  = $('#tblPenjualanHpp_filter input').val();

        var url = siteurl + active_controller + 'export_excel_report'
            + '?tgl_dari='   + encodeURIComponent(tgl_dari || '')
            + '&tgl_sampai=' + encodeURIComponent(tgl_sampai || '')
            + '&search='     + encodeURIComponent(searchVal || '');

        window.location = url;
    });
});

function formatNumber(num) {
    if (!num) return '0';
    return Number(num).toLocaleString('id-ID');
}

function initDataTable() {
    $('#tblPenjualanHpp').DataTable({
        processing: true,
        serverSide: true,
        stateSave: true,
        autoWidth: false,
        destroy: true,
        searching: true,
        responsive: false,
        scrollX: true,
        aaSorting: [[4, "desc"]],
        columnDefs: [
            { targets: 'no-sort', orderable: false }
        ],
        sPaginationType: "simple_numbers",
        iDisplayLength: 10,
        aLengthMenu: [
            [10, 20, 50, 100, 150],
            [10, 20, 50, 100, 150]
        ],
        ajax: {
            url: siteurl + active_controller + 'data_side_report',
            type: "post",
            data: function (d) {
                d.tgl_dari   = $('#tgl_dari').val();
                d.tgl_sampai = $('#tgl_sampai').val();
            },
            dataSrc: function (json) {
                // Update footer totals dari response
                var sumPenjualanPPN = json.sumPenjualanPPN || 0;
                var sumPendapatan   = json.sumPendapatan || 0;
                var sumHPP          = json.sumHPP || 0;
                var sumLaba         = json.sumLaba || 0;
                var pLaba = sumPendapatan > 0 ? Math.round((sumLaba / sumPendapatan) * 100) : 0;

                $('#footPenjualanPPN').html('<strong>' + formatNumber(sumPenjualanPPN) + '</strong>');
                $('#footHPP').html('<strong>' + formatNumber(sumHPP) + '</strong>');
                $('#footLaba').html('<strong>' + formatNumber(sumLaba) + '</strong>');
                $('#footPersenLaba').html('<strong>' + pLaba + '%</strong>');

                return json.data;
            },
            cache: false,
            error: function () {
                $("#tblPenjualanHpp tbody").html(
                    '<tr><th colspan="15" class="text-center">No data found in the server</th></tr>'
                );
            }
        }
    });
}
</script>
