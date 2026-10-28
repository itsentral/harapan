<style type="text/css">
    thead input {
        width: 100%;
    }
</style>
<div id='alert_edit' class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>
<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">

<div class="box">
    <div class="box-header">
        <span class="pull-left">
            <button type='button' class='btn btn-primary add-costing'>Add Costing</button>

            <!-- <span class='text-bold text-red'><?= $last_update; ?></span> -->
        </span>
        <span class="pull-right">
            <a class="btn btn-success btn-sm" style='float:right;' href="<?= base_url('product_costing/download_excel') ?>" title="Download">Download</a>
        </span>
    </div>

    <!-- /.box-header -->
    <div class="box-body">
        <!-- <div class="form-group row">
            <div class="col-md-10">

            </div>
            <div class="col-md-2">
                <select name="status" id="status" class='form-control select2'>
                    <option value="0">ALL STATUS</option>
                    <option value="N">Waiting Submission</option>
                    <option value="WA">Waiting Approval</option>
                    <option value="A">Approved</option>
                    <option value="R">Rejected</option>
                </select>
            </div>
        </div> -->
        <div class="table-responsive">
            <table id="example1" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Type</th>
                        <th>Product Name</th>
                        <th class='text-right'>Price List<br>(IDR)</th>
                        <th class='text-right'>Price Diajukan<br>(IDR)</th>
                        <th>Status Price List</th>
                        <th width='10%'>Reason</th>
                        <th width='7%'>Action</th>
                    </tr>
                </thead>

                <tbody></tbody>
            </table>
        </div>
    </div>
    <!-- /.box-body -->
</div>

<div class="modal modal-default fade" id="dialog-popup" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel"><span class="fa fa-users"></span>&nbsp;Product Costing</h4>
            </div>
            <div class="modal-body" id="ModalView">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    <span class="glyphicon glyphicon-remove"></span> Close</button>
            </div>
        </div>
    </div>
</div>

<!-- DataTables -->
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/select2/select2.full.min.js') ?>"></script>


<!-- page script -->
<script type="text/javascript">
    $(document).ready(function() {
        $('.select').select2({
            width: '100%'
        });

        $(document).on('change', '#status', function() {
            var status = $('#status').val()
            DataTables(status);
        })

        var status = $('#status').val()
        DataTables(status);

        $(document).on('click', '#btnUpdate', function(e) {
            e.preventDefault()
            swal({
                    title: "Anda Yakin?",
                    text: "Menarik Price Ref & BOM Terbaru !",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-info",
                    confirmButtonText: "Ya, Update!",
                    cancelButtonText: "Batal",
                    closeOnConfirm: false
                },
                function() {
                    $.ajax({
                        type: 'POST',
                        url: base_url + active_controller + '/update_product_price',
                        dataType: "json",
                        //   data:{'id':id},
                        success: function(result) {
                            if (result.status == '1') {
                                swal({
                                        title: "Sukses",
                                        text: "Data berhasil diupdate.",
                                        type: "success"
                                    },
                                    function() {
                                        window.location.reload(true);
                                    })
                            } else {
                                swal({
                                    title: "Error",
                                    text: "Data error. Gagal diupdate",
                                    type: "error"
                                })

                            }
                        },
                        error: function() {
                            swal({
                                title: "Error",
                                text: "Data error. Gagal request Ajax",
                                type: "error"
                            })
                        }
                    })
                });

        });

        // SHOW MODAL FOR ADD
        $(document).on('click', '.add-costing', function() {
            $("#head_title").html("<i class='fa fa-list-alt'></i><b>Tambah Data</b>");
            $.ajax({
                type: 'POST',
                url: siteurl + 'product_costing/add',
                success: function(data) {
                    $("#dialog-popup").modal();
                    $("#ModalView").html(data);
                }
            })
        });

        // SHOW MODAL FOR EDIT
        $(document).on('click', '.edit', function() {
            const id = $(this).data('id');

            $("#head_title").html("<i class='fa fa-edit'></i><b>Edit Data</b>");
            $.ajax({
                type: 'POST',
                url: siteurl + 'product_costing/edit',
                data: {
                    id: id
                }, // Kirim sebagai POST
                success: function(data) {
                    $("#dialog-popup").modal();
                    $("#ModalView").html(data);
                }
            });
        });
    });

    function DataTables(status = null) {
        var dataTable = $('#example1').DataTable({
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
                url: base_url + active_controller + 'data_side_product_costing',
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