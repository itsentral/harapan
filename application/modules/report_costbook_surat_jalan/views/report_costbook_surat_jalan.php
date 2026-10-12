<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">
<style type="text/css">
    thead input {
        width: 100%;
    }
</style>

<?php
// Siapkan query string untuk link download agar filternya ikut.
$qs = http_build_query([
    'start_date' => $start_date,
    'end_date'   => $end_date,
]);
?>

<div class="box">
    <div class="box-header">
        <div class="callout callout-info" style="margin-bottom:10px;">
            <p style="margin:0;">
                <i class="fa fa-info-circle"></i>
                Report ini menampilkan <strong>costbook surat jalan yang sudah dibuat tetapi belum di-confirm</strong>
                (status <strong>ON DELIVERY</strong>).
            </p>
        </div>
        <form method="get" action="<?= base_url('report_costbook_surat_jalan') ?>" class="form-inline">
            <div class="form-group">
                <label>Created At Dari</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="form-group">
                <label>Sampai</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Filter</button>
            <a href="<?= base_url('report_costbook_surat_jalan') ?>" class="btn btn-default btn-sm">Reset</a>

            <span class="pull-right">
                <a class="btn btn-success btn-sm" href="<?= base_url('report_costbook_surat_jalan/download_excel') . '?' . $qs ?>" title="Download Excel">
                    <i class="fa fa-file-excel-o"></i> Download Excel
                </a>
            </span>
        </form>
    </div>
    <!-- /.box-header -->

    <div class="box-body">
        <div class="table-responsive">
            <?php $this->load->view('_report_costbook_surat_jalan_table', ['rows' => $rows]); ?>
        </div>
    </div>
    <!-- /.box-body -->
</div>

<!-- DataTables -->
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('table.table-bordered').DataTable({
            "autoWidth": false,
            "responsive": true,
            "aaSorting": [],
            "sPaginationType": "simple_numbers",
            "iDisplayLength": 10,
            "aLengthMenu": [
                [10, 25, 50, 100, 150],
                [10, 25, 50, 100, 150]
            ],
            "language": {
                "emptyTable": "Tidak ada data."
            }
        });
    });
</script>