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
    'event'      => $event,
]);

$events = ['CREATE', 'UPDATE', 'CONFIRM', 'INVOICE'];
?>

<div class="box">
    <div class="box-header">
        <div class="callout callout-info" style="margin-bottom:10px;">
            <p style="margin:0;">
                <i class="fa fa-info-circle"></i>
                Report ini menampilkan <strong>ledger perpindahan stok</strong> antar tahap:
                <strong>CREATE</strong> (SJ dibuat &rarr; In-Transit),
                <strong>CONFIRM</strong> (In-Transit &rarr; In-Customer),
                <strong>INVOICE</strong> (In-Customer &rarr; Invoiced).
            </p>
        </div>
        <form method="get" action="<?= base_url('report_warehouse_mutasi') ?>" class="form-inline">
            <div class="form-group">
                <label>Tgl Event Dari</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars((string) $start_date) ?>">
            </div>
            <div class="form-group">
                <label>Sampai</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars((string) $end_date) ?>">
            </div>
            <div class="form-group">
                <label>Event</label>
                <select name="event" class="form-control">
                    <option value="">-- Semua --</option>
                    <?php foreach ($events as $ev) : ?>
                        <option value="<?= $ev ?>" <?= ($event === $ev) ? 'selected' : '' ?>><?= $ev ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Filter</button>
            <a href="<?= base_url('report_warehouse_mutasi') ?>" class="btn btn-default btn-sm">Reset</a>

            <span class="pull-right">
                <a class="btn btn-success btn-sm" href="<?= base_url('report_warehouse_mutasi/download_excel') . '?' . $qs ?>" title="Download Excel">
                    <i class="fa fa-file-excel-o"></i> Download Excel
                </a>
            </span>
        </form>
    </div>
    <!-- /.box-header -->

    <div class="box-body">
        <div class="table-responsive">
            <?php $this->load->view('_report_warehouse_mutasi_table', ['rows' => $rows]); ?>
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
