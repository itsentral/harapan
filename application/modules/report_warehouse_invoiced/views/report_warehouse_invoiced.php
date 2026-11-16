<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">
<style type="text/css">
    thead input {
        width: 100%;
    }
</style>

<?php
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
                Report ini menampilkan <strong>barang yang sudah di-invoice</strong> (tahap akhir setelah in-customer).
            </p>
        </div>
        <form method="get" action="<?= base_url('report_warehouse_invoiced') ?>" class="form-inline">
            <div class="form-group">
                <label>Tgl Invoice Dari</label>
                <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars((string) $start_date) ?>">
            </div>
            <div class="form-group">
                <label>Sampai</label>
                <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars((string) $end_date) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Filter</button>
            <a href="<?= base_url('report_warehouse_invoiced') ?>" class="btn btn-default btn-sm">Reset</a>

            <span class="pull-right">
                <a class="btn btn-success btn-sm" href="<?= base_url('report_warehouse_invoiced/download_excel') . '?' . $qs ?>" title="Download Excel">
                    <i class="fa fa-file-excel-o"></i> Download Excel
                </a>
            </span>
        </form>
    </div>
    <!-- /.box-header -->

    <div class="box-body">
        <?php if (empty($has_filter)) : ?>
            <div class="callout callout-warning" style="margin:0;">
                <p style="margin:0;">
                    <i class="fa fa-calendar"></i> Silakan pilih <strong>periode tanggal invoice</strong> lalu klik
                    <strong>Filter</strong> untuk menampilkan data.
                </p>
            </div>
        <?php else : ?>
            <div class="table-responsive">
                <?php $this->load->view('_report_warehouse_invoiced_table', ['rows' => $rows]); ?>
            </div>
        <?php endif; ?>
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