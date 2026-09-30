<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">

<div class="box box-primary">
    <div class="box-body">
        <div class="row" style="margin-bottom:10px;">
            <div class="col-md-2">
                <label>Tahun</label>
                <select id="tahun" class="form-control input-sm">
                    <?php for ($t = date('Y') - 3; $t <= date('Y') + 1; $t++): ?>
                        <option value="<?= $t ?>" <?= ($t == date('Y')) ? 'selected' : '' ?>>
                            <?= $t ?>
                        </option>
                    <?php endfor; ?>
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
            <table class="table table-bordered" id="tblSales">
                <thead>
                    <tr style="background:#f5f5f5;">
                        <th style="width: 180px;">Nama Sales</th>
                        <th style="width: 150px;">Tipe</th>
                        <th class="text-right">Jan</th>
                        <th class="text-right">Feb</th>
                        <th class="text-right">Mar</th>
                        <th class="text-right">Apr</th>
                        <th class="text-right">Mei</th>
                        <th class="text-right">Jun</th>
                        <th class="text-right">Jul</th>
                        <th class="text-right">Agu</th>
                        <th class="text-right">Sep</th>
                        <th class="text-right">Okt</th>
                        <th class="text-right">Nov</th>
                        <th class="text-right">Des</th>
                        <th class="text-right">T Score</th>
                    </tr>
                </thead>
                <tbody id="bodySales"></tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        loadReportSales();

        $('#btnFilter').on('click', function() {
            loadReportSales();
        });

        $('#btnReset').on('click', function() {
            $('#tahun').val(new Date().getFullYear());
            loadReportSales();
        });

        $('#btnExportExcel').on('click', function() {
            var tahun = $('#tahun').val();
            var url = siteurl + active_controller + 'export_excel_sales_bulanan?tahun=' + encodeURIComponent(tahun);
            window.location = url;
        });
    });

    function loadReportSales() {
        $("#bodySales").html("<tr><td colspan='15' class='text-center'>Loading...</td></tr>");

        $.ajax({
            url: siteurl + active_controller + "data_side_report_sales_bulanan",
            type: "POST",
            dataType: "json",
            data: {
                tahun: $("#tahun").val()
            },
            success: function(res) {
                if (!res || !res.data || res.data.length == 0) {
                    $("#bodySales").html("<tr><td colspan='15' class='text-center'>No data</td></tr>");
                    return;
                }

                var html = "";
                res.data.forEach(function(r) {

                    // styling biar mirip excel
                    var isTotalCabang = (r.nama_sales == "TARGET CABANG");
                    var rowStyle = "";

                    if (isTotalCabang) rowStyle = "style='background:#fff4cc;font-weight:bold;'";
                    if (r.tipe == "Actual (based on invoice)" && isTotalCabang) rowStyle = "style='background:#fff4cc;font-weight:bold;border-bottom:2px solid #333;'";
                    if (r.tipe == "Target" && r.nama_sales != "" && r.nama_sales != "TARGET CABANG") rowStyle = "style='border-top:2px solid #333; font-weight:bold;'";

                    html += "<tr " + rowStyle + ">";
                    html += "<td>" + (r.nama_sales || "") + "</td>";
                    html += "<td>" + (r.tipe || "") + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.jan) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.feb) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.mar) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.apr) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.mei) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.jun) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.jul) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.agu) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.sep) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.okt) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.nov) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.des) + "</td>";
                    html += "<td class='text-right'>" + formatNumber(r.t_score) + "</td>";
                    html += "</tr>";
                });

                $("#bodySales").html(html);
            },
            error: function() {
                $("#bodySales").html("<tr><td colspan='15' class='text-center'>Error load data</td></tr>");
            }
        });
    }

    function formatNumber(num) {
        num = parseFloat(num || 0);
        return num.toLocaleString('id-ID');
    }
</script>