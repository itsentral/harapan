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
                <label>Sampai</label>
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
            <div class="col-md-4" style="padding-top:25px;">
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
                        <th>Pelanggan</th>
                        <th>Nama Barang</th>
                        <th>Satuan</th>
                        <th>Kuantitas</th>
                        <th>Penjualan</th>
                        <th>Revenue (exclude PPn)</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

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

            var url = siteurl + active_controller + 'export_excel_customer_per_barang' +
                '?tgl_dari=' + encodeURIComponent(tgl_dari || '') +
                '&tgl_sampai=' + encodeURIComponent(tgl_sampai || '') +
                '&id_sales=' + encodeURIComponent(id_sales || '') +
                '&search=' + encodeURIComponent(searchVal || '');

            window.location = url;
        });
    });

    function DataTables() {
        $('#example1').DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            searching: true,
            ordering: false, // biar grouping subtotal tidak kacau
            paging: false, // penting: subtotal = total sebenarnya (bukan per halaman)
            info: false,
            autoWidth: false,
            responsive: false,

            ajax: {
                url: siteurl + active_controller + 'data_side_customer_per_barang',
                type: "post",
                data: function(d) {
                    d.tgl_dari = $('#tgl_dari').val();
                    d.tgl_sampai = $('#tgl_sampai').val();
                    d.id_sales = $('#id_sales').val();
                },
                cache: false
            },
            createdRow: function(row, data) {
                // data = array [pelanggan, nama_barang, satuan, qty, penjualan]
                var col0 = $('<div>').html(data[0]).text().trim();
                var col1 = $('<div>').html(data[1]).text().trim();

                if (col1.toUpperCase() === 'TOTAL NAMA BARANG') $(row).addClass('row-subtotal');
                if (col0.toUpperCase() === 'TOTAL PELANGGAN') $(row).addClass('row-grandtotal');
            }
        });
    }
</script>