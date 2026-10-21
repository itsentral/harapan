<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">
<style type="text/css">
    #tbl-hpp-detail thead input {
        width: 100%;
    }

    tr.hpp-subtotal-row th {
        background: #f5f5f5;
        font-weight: bold;
    }
</style>

<?php
// Siapkan query string untuk link download agar filternya ikut.
$qs = http_build_query([
    'start_date' => $start_date,
    'end_date'   => $end_date,
    'date_basis' => $date_basis,
]);
?>

<div class="box">
    <div class="box-header">
        <div class="callout callout-info" style="margin-bottom:10px;">
            <p style="margin:0;">
                <i class="fa fa-info-circle"></i>
                Report ini menampilkan <strong>HPP (costbook) tiap produk per invoice</strong>.
                <strong>Total = Qty &times; Costbook</strong>. Sub Total dihitung per No. Invoice.
            </p>
        </div>
        <form method="get" action="<?= base_url('report_hpp_detail') ?>" class="form-inline">
            <div class="form-group">
                <label>Filter Berdasarkan</label>
                <select name="date_basis" class="form-control">
                    <option value="created" <?= ($date_basis !== 'delivery') ? 'selected' : '' ?>>Tgl Dibuat</option>
                    <option value="delivery" <?= ($date_basis === 'delivery') ? 'selected' : '' ?>>Tgl Kirim</option>
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
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter"></i> Filter</button>
            <a href="<?= base_url('report_hpp_detail') ?>" class="btn btn-default btn-sm">Reset</a>

            <span class="pull-right">
                <a class="btn btn-success btn-sm" href="<?= base_url('report_hpp_detail/download_excel') . '?' . $qs ?>" title="Download Excel">
                    <i class="fa fa-file-excel-o"></i> Download Excel
                </a>
            </span>
        </form>
    </div>
    <!-- /.box-header -->

    <div class="box-body">
        <div class="table-responsive">
            <?php $this->load->view('_report_hpp_detail_table', ['rows' => $rows]); ?>
        </div>
    </div>
    <!-- /.box-body -->
</div>

<!-- DataTables -->
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script>
<script type="text/javascript">
    $(document).ready(function() {
        // Format angka ke format Indonesia (ribuan '.', desimal ',', 2 desimal).
        function formatRupiah(num) {
            var n = (parseFloat(num) || 0).toFixed(2); // "1234567.89"
            var parts = n.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return parts[0] + ',' + parts[1];
        }

        var table = $('#tbl-hpp-detail').DataTable({
            "autoWidth": false,
            "responsive": true,
            // Kunci urutan per No. Invoice (kolom 0) agar baris satu invoice
            // selalu berdekatan -> Sub Total per invoice tetap valid.
            "order": [
                [0, "asc"]
            ],
            "ordering": false, // nonaktifkan sort manual oleh user (jaga grouping)
            "sPaginationType": "simple_numbers",
            "iDisplayLength": 25,
            "aLengthMenu": [
                [10, 25, 50, 100, 150],
                [10, 25, 50, 100, 150]
            ],
            "language": {
                "emptyTable": "Tidak ada data.",
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ baris",
                "info": "Menampilkan _START_ s/d _END_ dari _TOTAL_ baris",
                "infoEmpty": "Tidak ada data",
                "infoFiltered": "(disaring dari _MAX_ total baris)",
                "paginate": {
                    "first": "Awal",
                    "last": "Akhir",
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                },
                "zeroRecords": "Tidak ada data yang cocok."
            },
            // Sisipkan baris Sub Total per invoice pada halaman yang sedang tampil.
            "drawCallback": function(settings) {
                var api = this.api();
                var rows = api.rows({
                    page: 'current'
                }).nodes();
                var last = null;
                var subtotal = 0;

                // Hapus baris subtotal lama (kalau ada) sebelum menyisipkan ulang.
                $(rows).filter('.hpp-subtotal-row').remove();

                api.rows({
                    page: 'current'
                }).every(function(rowIdx, tableLoop, rowLoop) {
                    var node = this.node();
                    var inv = $(node).data('invoice');
                    var total = parseFloat($(node).data('total')) || 0;

                    if (last !== null && inv !== last) {
                        $(subtotalRow(subtotal)).insertBefore(node);
                        subtotal = 0;
                    }
                    subtotal += total;
                    last = inv;
                });

                // Subtotal untuk invoice terakhir di halaman ini.
                if (last !== null) {
                    var lastNode = $(rows).filter('[data-invoice]').last();
                    if (lastNode.length) {
                        $(subtotalRow(subtotal)).insertAfter(lastNode);
                    }
                }

                // Grand Total = jumlah seluruh baris hasil filter (semua halaman).
                var grand = 0;
                api.rows({
                    search: 'applied'
                }).every(function() {
                    grand += parseFloat($(this.node()).data('total')) || 0;
                });
                $('#hpp-grand-total').text(formatRupiah(grand));
            }
        });

        // Bangun HTML baris Sub Total.
        function subtotalRow(subtotal) {
            return '<tr class="hpp-subtotal-row">' +
                '<th colspan="9" style="text-align:left;">Sub Total</th>' +
                '<th style="text-align:right;">' + formatRupiah(subtotal) + '</th>' +
                '</tr>';
        }
    });
</script>