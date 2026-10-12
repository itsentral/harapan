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
    'status'     => $status,
    'date_field' => $date_field,
]);
?>

<div class="box">
    <div class="box-header">
        <form method="get" action="<?= base_url('report_product_costing') ?>" class="form-inline">
            <div class="form-group">
                <label>Basis Tanggal</label>
                <select name="date_field" class="form-control">
                    <option value="created_at" <?= ($date_field == 'created_at') ? 'selected' : '' ?>>Created At</option>
                    <option value="modified_at" <?= ($date_field == 'modified_at') ? 'selected' : '' ?>>Modified At</option>
                </select>
            </div>
            <div class="form-group">
                <label>Dari</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="form-group">
                <label>Sampai</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="">ALL STATUS</option>
                    <option value="WA" <?= ($status == 'WA') ? 'selected' : '' ?>>Waiting Approval</option>
                    <option value="A" <?= ($status == 'A') ? 'selected' : '' ?>>Approved</option>
                    <option value="R" <?= ($status == 'R') ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Filter</button>
            <a href="<?= base_url('report_product_costing') ?>" class="btn btn-default btn-sm">Reset</a>

            <span class="pull-right">
                <a class="btn btn-success btn-sm" href="<?= base_url('report_product_costing/download_excel') . '?' . $qs ?>" title="Download Excel">
                    <i class="fa fa-file-excel-o"></i> Download Excel
                </a>
            </span>
        </form>
    </div>
    <!-- /.box-header -->

    <div class="box-body">
        <div class="table-responsive">
            <?php $this->load->view('_report_product_costing_table', ['rows' => $rows]); ?>
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
