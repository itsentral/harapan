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
                        <th style="width: 10px;">No</th>
                        <th>Customer</th>
                        <th>Total Invoice</th>
                        <th>Revenue (exclude PPn)</th>
                        <th>Total Bayar</th>
                        <th>Total Piutang</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
                <tfoot>
                    <tr style="font-weight:bold; background:#f5f5f5;">
                        <th colspan="2" class="text-center">Total</th>
                        <th class="text-right" id="footTotalInvoice">0</th>
                        <th class="text-right" id="footTotalInvoiceDpp">0</th>
                        <th class="text-right" id="footTotalBayar">0</th>
                        <th class="text-right" id="footTotalPiutang">0</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>


<div class="modal fade" id="modalDet" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">Detail Piutang</h4>
            </div>
            <div class="modal-body">
                <div id="isi-modal-detail"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
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

            var url = siteurl + active_controller + 'export_excel_customer' +
                '?tgl_dari=' + encodeURIComponent(tgl_dari || '') +
                '&tgl_sampai=' + encodeURIComponent(tgl_sampai || '') +
                '&id_sales=' + encodeURIComponent(id_sales || '') +
                '&search=' + encodeURIComponent(searchVal || '');

            window.location = url;
        });

        $(document).on('click', '.view-detail', function() {
            var id_cust = $(this).data('customer');
            var nm_cust = $(this).data('name');

            $('.modal-title').text('Detail Piutang ' + nm_cust);

            $('#isi-modal-detail').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading Data...</div>');

            $('#modalDet').modal('show');

            $.ajax({
                url: '<?php echo base_url("report_penjualan/get_detail_piutang") ?>',
                type: 'POST',
                data: {
                    id_customer: id_cust
                },
                success: function(response) {
                    $('#isi-modal-detail').html(response);
                },
                error: function() {
                    $('#isi-modal-detail').html('<p class="text-danger text-center">Gagal mengambil data. Cek koneksi atau query.</p>');
                }
            });
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
                    "targets": [0, 6],
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
                url: siteurl + active_controller + 'data_side_customer',
                type: "post",
                data: function(d) {
                    d.tgl_dari = $('#tgl_dari').val();
                    d.tgl_sampai = $('#tgl_sampai').val();
                    d.id_sales = $('#id_sales').val();
                },
                dataSrc: function(json) {
                    // isi footer grand total dari response
                    if (json && json.grandTotal) {
                        $('#footTotalInvoice').text(json.grandTotal.total_invoice);
                        $('#footTotalInvoiceDpp').text(json.grandTotal.total_invoice_dpp);
                        $('#footTotalBayar').text(json.grandTotal.total_bayar);
                        $('#footTotalPiutang').text(json.grandTotal.total_piutang);
                    } else {
                        $('#footTotalInvoice').text('0');
                        $('#footTotalInvoiceDpp').text('0');
                        $('#footTotalBayar').text('0');
                        $('#footTotalPiutang').text('0');
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