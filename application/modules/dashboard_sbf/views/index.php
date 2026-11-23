<style>
    .sbf-dash {
        --ink: #1B2A41;
        --ink-soft: #2B3A42;
        --line: #CBD0BE;
        --card: #F8F9F4;
        --amber: #E8952F;
        --amber-soft: #FBE8CC;
        --red: #C23B3B;
        --red-soft: #F6D9D6;
        --green: #3F7D58;
        --muted: #5F6672;
    }

    .sbf-dash .mono {
        font-family: Consolas, Menlo, "Courier New", monospace;
    }

    .sbf-dash .filter-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: flex-end;
        margin-bottom: 15px;
    }

    .sbf-dash .filter-bar .meta {
        margin-left: auto;
        text-align: right;
        font-size: 12px;
        color: var(--muted);
    }

    /* KPI */
    .sbf-dash .kpi {
        background: var(--card);
        border: 1px solid var(--line);
        padding: 12px 12px 10px 15px;
        position: relative;
        margin-bottom: 15px;
        min-height: 98px;
    }

    .sbf-dash .kpi::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: var(--k-accent, var(--ink));
    }

    .sbf-dash .kpi.good {
        --k-accent: var(--green);
    }

    .sbf-dash .kpi.warn {
        --k-accent: var(--amber);
    }

    .sbf-dash .kpi.bad {
        --k-accent: var(--red);
    }

    .sbf-dash .kpi .label {
        font-size: 11px;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--muted);
        font-weight: 600;
        margin-bottom: 6px;
    }

    .sbf-dash .kpi .value {
        font-size: 20px;
        font-weight: 700;
        color: var(--ink);
        line-height: 1.1;
    }

    .sbf-dash .kpi .sub {
        font-size: 12px;
        margin-top: 6px;
        color: var(--muted);
    }

    .sbf-dash .kpi .sub.up {
        color: var(--green);
    }

    .sbf-dash .kpi .sub.down {
        color: var(--red);
    }

    /* Panel */
    .sbf-dash .box-title {
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 600;
    }

    .sbf-dash .chart-wrap {
        position: relative;
        height: 240px;
    }

    .sbf-dash .tag {
        font-size: 11px;
        color: var(--muted);
        text-transform: uppercase;
    }

    /* Top sales */
    .sbf-dash .p-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 0;
        border-bottom: 1px solid #E3E5D8;
    }

    .sbf-dash .p-row:last-child {
        border-bottom: none;
    }

    .sbf-dash .p-rank {
        font-weight: 700;
        color: var(--muted);
        width: 22px;
    }

    .sbf-dash .p-name {
        flex: 1;
        min-width: 0;
    }

    .sbf-dash .p-name .n {
        font-weight: 600;
        color: var(--ink);
    }

    .sbf-dash .p-name .s {
        font-size: 12px;
        color: var(--muted);
    }

    .sbf-dash .p-bar-bg {
        width: 90px;
        height: 6px;
        background: #E3E5D8;
        border-radius: 3px;
        overflow: hidden;
    }

    .sbf-dash .p-bar {
        height: 100%;
        background: var(--ink);
    }

    .sbf-dash .p-val {
        font-weight: 600;
        width: 100px;
        text-align: right;
    }

    /* Tabel */
    .sbf-dash table thead th {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: var(--muted);
        white-space: nowrap;
    }

    .sbf-dash td.num {
        text-align: right;
        white-space: nowrap;
    }

    .sbf-dash .days-badge {
        display: inline-block;
        min-width: 36px;
        padding: 2px 6px;
        font-weight: 700;
        font-size: 12px;
        text-align: center;
    }

    .sbf-dash .days-badge.mid {
        background: var(--amber-soft);
        color: #8A5A11;
    }

    .sbf-dash .days-badge.high {
        background: var(--red-soft);
        color: #8A2222;
    }

    .sbf-dash .stamp {
        display: inline-block;
        font-weight: 700;
        font-size: 11px;
        letter-spacing: .06em;
        text-transform: uppercase;
        padding: 2px 8px;
        border: 1.5px dashed currentColor;
        border-radius: 3px;
        white-space: nowrap;
    }

    .sbf-dash .stamp.jt {
        color: #A8650A;
    }

    .sbf-dash .stamp.macet {
        color: var(--red);
    }

    .sbf-dash .section-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 10px 0 10px;
    }

    .sbf-dash .section-title h3 {
        margin: 0;
        font-size: 18px;
        text-transform: uppercase;
        font-weight: 600;
        color: var(--ink);
    }

    .sbf-dash .section-title .rule {
        flex: 1;
        height: 1px;
        background: var(--line);
    }

    .sbf-dash .section-title .count {
        background: var(--ink);
        color: #fff;
        font-size: 12px;
        padding: 2px 8px;
    }

    .sbf-dash .callout-bad {
        background: var(--red-soft);
        border: 1px solid #E3AFAF;
        padding: 10px 14px;
        margin-top: 10px;
        color: #7A2323;
        font-size: 13px;
    }

    .sbf-dash .legend-row {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        margin-top: 8px;
        font-size: 12px;
        color: var(--muted);
    }

    .sbf-dash .legend-dot {
        display: inline-block;
        width: 9px;
        height: 9px;
        margin-right: 5px;
    }

    .sbf-dash .def-list {
        margin: 0;
        padding-left: 18px;
        font-size: 13px;
        color: var(--ink-soft);
    }

    .sbf-dash .def-list li {
        margin-bottom: 6px;
    }

    .sbf-dash .empty {
        padding: 18px;
        text-align: center;
        color: var(--muted);
    }
</style>

<div class="sbf-dash">

    <!-- Filter -->
    <div class="filter-bar">
        <div>
            <label for="f-bulan" style="display:block; margin-bottom:2px;">Bulan</label>
            <select id="f-bulan" class="form-control input-sm" style="width:120px;">
                <?php foreach ($bulan_list as $no => $nm) : ?>
                    <option value="<?= $no ?>" <?= $no == $bulan_sekarang ? 'selected' : '' ?>><?= $nm ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="f-tahun" style="display:block; margin-bottom:2px;">Tahun</label>
            <select id="f-tahun" class="form-control input-sm" style="width:90px;">
                <?php for ($y = $tahun_sekarang; $y >= $tahun_sekarang - 4; $y--) : ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <button type="button" id="btn-tampil" class="btn btn-primary btn-sm">
                <i class="fa fa-refresh"></i> Tampilkan
            </button>
        </div>
        <div class="meta">
            <strong id="meta-periode">-</strong><br>
            Posisi piutang per <span id="meta-tanggal">-</span>
        </div>
    </div>

    <!-- KPI -->
    <div class="row">
        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="kpi good">
                <div class="label">Penjualan Bulan Ini</div>
                <div class="value mono" id="kpi-penjualan">-</div>
                <div class="sub" id="kpi-penjualan-sub">&nbsp;</div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="kpi">
                <div class="label">Order Baru</div>
                <div class="value mono" id="kpi-order">-</div>
                <div class="sub" id="kpi-order-sub">&nbsp;</div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="kpi">
                <div class="label">Total Piutang</div>
                <div class="value mono" id="kpi-piutang">-</div>
                <div class="sub" id="kpi-piutang-sub">&nbsp;</div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="kpi warn">
                <div class="label">Jatuh Tempo (1-90 hr)</div>
                <div class="value mono" id="kpi-jt">-</div>
                <div class="sub" id="kpi-jt-sub">&nbsp;</div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="kpi bad">
                <div class="label">Macet (&gt;90 hr)</div>
                <div class="value mono" id="kpi-macet">-</div>
                <div class="sub" id="kpi-macet-sub">&nbsp;</div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-xs-6">
            <div class="kpi good" id="kpi-cr-box">
                <div class="label">Collection Rate</div>
                <div class="value mono" id="kpi-cr">-</div>
                <div class="sub" id="kpi-cr-sub">&nbsp;</div>
            </div>
        </div>
    </div>

    <!-- Tren + Aging -->
    <div class="row">
        <div class="col-md-7">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Tren Penjualan &amp; Penagihan</h3>
                    <span class="tag pull-right">6 bulan terakhir</span>
                </div>
                <div class="box-body">
                    <div class="chart-wrap"><canvas id="chartTrend"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Penjualan per Kategori</h3>
                    <span class="tag pull-right" id="kategori-periode">-</span>
                </div>
                <div class="box-body">
                    <div class="chart-wrap"><canvas id="chartKategori"></canvas></div>
                    <div class="empty" id="kategori-empty" style="display:none;">Belum ada penjualan pada periode ini.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Aging + Top sales -->
    <div class="row">
        <div class="col-md-5">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Aging Piutang</h3>
                    <span class="tag pull-right">Hari lewat jatuh tempo</span>
                </div>
                <div class="box-body">
                    <div class="chart-wrap"><canvas id="chartAging"></canvas></div>
                    <div class="legend-row">
                        <span><span class="legend-dot" style="background:#3F7D58"></span>Belum jatuh tempo</span>
                        <span><span class="legend-dot" style="background:#E8952F"></span>Jatuh tempo (1-90 hr)</span>
                        <span><span class="legend-dot" style="background:#C23B3B"></span>Macet (&gt;90 hr)</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="box box-default">
                <div class="box-header with-border">
                    <h3 class="box-title">Top Sales - Pencapaian Target</h3>
                    <span class="tag pull-right" id="top-periode">-</span>
                </div>
                <div class="box-body" id="top-sales"></div>
            </div>
        </div>
    </div>

    <!-- Definisi -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default collapsed-box">
                <div class="box-header with-border">
                    <h3 class="box-title">Catatan Definisi</h3>
                    <div class="box-tools pull-right">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse" title="Buka/tutup"><i class="fa fa-plus"></i></button>
                    </div>
                </div>
                <div class="box-body">
                    <ul class="def-list">
                        <li>Penjualan dihitung dari <b>tanggal kirim</b> invoice, <b>sudah termasuk PPN</b>, tanpa invoice yang dibatalkan.</li>
                        <li>Tertagih = pembayaran yang diterima pada bulan tersebut. Collection rate = tertagih bulan ini / penjualan bulan ini, sehingga bisa melebihi 100% bila ada pelunasan invoice bulan lalu.</li>
                        <li>Piutang dan aging adalah posisi <b>per hari ini</b> (tidak ikut filter bulan). Umur dihitung dari hari lewat jatuh tempo.</li>
                        <li>Jatuh tempo diambil dari invoice. Bila kosong, dihitung dari tanggal kirim + TOP sales order.</li>
                        <li>Target sales dari master target penjualan, sama dengan Report Margin Achievement.</li>
                        <li>Kategori produk = Product Category (level 2 inventory). Nilai kategori dihitung dari detail invoice (include PPN, sebelum diskon khusus), jadi totalnya bisa sedikit berbeda dari angka Penjualan di atas. Lima kategori terbesar ditampilkan, sisanya digabung ke "Lainnya".</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Piutang jatuh tempo -->
    <div class="section-title">
        <h3>Piutang Jatuh Tempo</h3>
        <div class="rule"></div>
        <span class="count" id="cnt-jt">0 invoice</span>
    </div>
    <div class="box box-default">
        <div class="box-body table-responsive no-padding">
            <table class="table table-hover" id="tbl-jt">
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>No. Invoice</th>
                        <th>Tgl Jatuh Tempo</th>
                        <th class="text-center">Telat (hari)</th>
                        <th class="text-right">Sisa Tagihan</th>
                        <th>Sales PIC</th>
                        <th>Bayar Terakhir</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="box-footer text-muted" style="font-size:12px;">Menampilkan 10 invoice dengan sisa tagihan terbesar. Rincian lengkap ada di Report Umur Piutang.</div>
    </div>

    <!-- Piutang macet -->
    <div class="section-title">
        <h3>Piutang Macet</h3>
        <div class="rule"></div>
        <span class="count" id="cnt-macet">0 invoice</span>
    </div>
    <div class="box box-default">
        <div class="box-body table-responsive no-padding">
            <table class="table table-hover" id="tbl-macet">
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>No. Invoice</th>
                        <th>Tgl Jatuh Tempo</th>
                        <th class="text-center">Telat (hari)</th>
                        <th class="text-right">Sisa Tagihan</th>
                        <th>Sales PIC</th>
                        <th>Bayar Terakhir</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="box-footer" id="macet-callout" style="display:none; border-top:none; padding-top:0;">
            <div class="callout-bad" id="macet-callout-text"></div>
        </div>
        <div class="box-footer text-muted" style="font-size:12px;">Menampilkan 10 invoice dengan umur terlama.</div>
    </div>

</div>

<script src="<?= base_url('assets/plugins/chartjs/Chart.js'); ?>"></script>
<script>
    $(function() {
        var url_data = siteurl + active_controller + 'get_data';
        var chartTrend = null;
        var chartAging = null;
        var chartKategori = null;

        function esc(s) {
            return $('<div>').text(s === null || s === undefined ? '' : s).html();
        }

        function num(n) {
            return (parseFloat(n) || 0).toLocaleString('id-ID', {
                maximumFractionDigits: 0
            });
        }

        function rp(n) {
            return 'Rp ' + num(n);
        }

        // Format ringkas: Rp 4,82 M / Rp 612 Jt
        function rpShort(n) {
            n = parseFloat(n) || 0;
            var abs = Math.abs(n);
            if (abs >= 1e9) return 'Rp ' + (n / 1e9).toLocaleString('id-ID', {
                maximumFractionDigits: 2
            }) + ' M';
            if (abs >= 1e6) return 'Rp ' + (n / 1e6).toLocaleString('id-ID', {
                maximumFractionDigits: 1
            }) + ' Jt';
            return 'Rp ' + num(n);
        }

        var BLN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        function tgl(d) {
            if (!d) return '-';
            var p = String(d).substr(0, 10).split('-');
            if (p.length !== 3) return esc(d);
            return parseInt(p[2], 10) + ' ' + BLN[parseInt(p[1], 10) - 1] + ' ' + p[0];
        }

        function delta(now, prev, textPrev) {
            now = parseFloat(now) || 0;
            prev = parseFloat(prev) || 0;
            if (prev <= 0) return {
                cls: '',
                text: 'Tidak ada pembanding ' + textPrev
            };
            var pct = (now - prev) / prev * 100;
            var up = pct >= 0;
            return {
                cls: up ? 'up' : 'down',
                text: (up ? '\u25B2 ' : '\u25BC ') + Math.abs(pct).toLocaleString('id-ID', {
                    maximumFractionDigits: 1
                }) + '% vs ' + textPrev
            };
        }

        function setSub(sel, d) {
            $(sel).removeClass('up down').addClass(d.cls).text(d.text);
        }

        function renderKpi(k) {
            $('#kpi-penjualan').text(rp(k.penjualan)).attr('title', rp(k.penjualan));
            setSub('#kpi-penjualan-sub', delta(k.penjualan, k.penjualan_prev, 'bulan lalu'));

            $('#kpi-order').text(num(k.order));
            setSub('#kpi-order-sub', delta(k.order, k.order_prev, 'bulan lalu'));

            $('#kpi-piutang').text(rp(k.total_piutang)).attr('title', rp(k.total_piutang));
            $('#kpi-piutang-sub').text(num(k.total_invoice) + ' invoice belum lunas');

            $('#kpi-jt').text(rp(k.jt_total)).attr('title', rp(k.jt_total));
            $('#kpi-jt-sub').addClass('down').text(num(k.jt_invoice) + ' invoice / ' + num(k.jt_customer) + ' pelanggan');

            $('#kpi-macet').text(rp(k.macet_total)).attr('title', rp(k.macet_total));
            $('#kpi-macet-sub').addClass('down').text(num(k.macet_invoice) + ' invoice' + (k.macet_invoice > 0 ? ' - perlu tindakan' : ''));

            if (k.collection_rate === null) {
                $('#kpi-cr').text('-');
                $('#kpi-cr-sub').text('Belum ada penjualan');
            } else {
                $('#kpi-cr').text(k.collection_rate.toLocaleString('id-ID', {
                    maximumFractionDigits: 1
                }) + '%');
                $('#kpi-cr-sub').text('Tertagih ' + rp(k.tertagih));
                $('#kpi-cr-box').removeClass('good warn bad').addClass(k.collection_rate >= 90 ? 'good' : (k.collection_rate >= 70 ? 'warn' : 'bad'));
            }
        }

        function renderTrend(t) {
            var toJt = function(a) {
                return a.map(function(v) {
                    return Math.round(v / 1e6);
                });
            };
            if (chartTrend) chartTrend.destroy();
            chartTrend = new Chart(document.getElementById('chartTrend').getContext('2d'), {
                type: 'line',
                data: {
                    labels: t.labels,
                    datasets: [{
                        label: 'Penjualan',
                        data: toJt(t.penjualan),
                        borderColor: '#1B2A41',
                        backgroundColor: 'rgba(27,42,65,0.08)',
                        borderWidth: 2.5,
                        lineTension: 0.3,
                        fill: true,
                        pointRadius: 3
                    }, {
                        label: 'Tertagih',
                        data: toJt(t.tertagih),
                        borderColor: '#3F7D58',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [4, 3],
                        lineTension: 0.3,
                        fill: false,
                        pointRadius: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        position: 'bottom'
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(v) {
                                    return num(v) + ' Jt';
                                }
                            }
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function(ti, data) {
                                return data.datasets[ti.datasetIndex].label + ': Rp ' + num(ti.yLabel) + ' Jt';
                            }
                        }
                    }
                }
            });
        }

        function renderAging(a) {
            var vals = [a.belum_jt, a.b_1_30, a.b_31_60, a.b_61_90, a.b_90_plus].map(function(v) {
                return Math.round(v / 1e6);
            });
            if (chartAging) chartAging.destroy();
            chartAging = new Chart(document.getElementById('chartAging').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Belum JT', '1-30 hr', '31-60 hr', '61-90 hr', '>90 hr'],
                    datasets: [{
                        label: 'Piutang',
                        data: vals,
                        backgroundColor: ['#3F7D58', '#E8952F', '#E8952F', '#E8952F', '#C23B3B']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: {
                        display: false
                    },
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(v) {
                                    return num(v) + ' Jt';
                                }
                            }
                        }],
                        xAxes: [{
                            gridLines: {
                                display: false
                            },
                            barPercentage: 0.7
                        }]
                    },
                    tooltips: {
                        callbacks: {
                            label: function(ti) {
                                return 'Rp ' + num(ti.yLabel) + ' Jt';
                            }
                        }
                    }
                }
            });
        }

        function renderKategori(list) {
            if (chartKategori) {
                chartKategori.destroy();
                chartKategori = null;
            }
            if (!list.length) {
                $('#chartKategori').parent().hide();
                $('#kategori-empty').show();
                return;
            }
            $('#kategori-empty').hide();
            $('#chartKategori').parent().show();

            var total = 0;
            $.each(list, function(i, r) {
                total += r.total;
            });
            var palette = ['#1B2A41', '#E8952F', '#3F7D58', '#5C6B8A', '#C23B3B', '#C9CDBE'];

            chartKategori = new Chart(document.getElementById('chartKategori').getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: list.map(function(r) {
                        return r.kategori;
                    }),
                    datasets: [{
                        data: list.map(function(r) {
                            return r.total;
                        }),
                        backgroundColor: list.map(function(r, i) {
                            return palette[i % palette.length];
                        }),
                        borderWidth: 2,
                        borderColor: '#F8F9F4'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutoutPercentage: 62,
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            padding: 10
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function(ti, data) {
                                var v = data.datasets[0].data[ti.index];
                                var pct = total > 0 ? (v / total * 100) : 0;
                                return data.labels[ti.index] + ': ' + rpShort(v) + ' (' +
                                    pct.toLocaleString('id-ID', {
                                        maximumFractionDigits: 1
                                    }) + '%)';
                            }
                        }
                    }
                }
            });
        }

        function renderTopSales(list) {
            var html = '';
            if (!list.length) {
                html = '<div class="empty">Belum ada penjualan pada periode ini.</div>';
            }
            $.each(list, function(i, r) {
                var persen = r.persen === null ? 0 : Math.min(r.persen, 100);
                var sub = r.target > 0 ?
                    'Target ' + rpShort(r.target) + ' (' + r.persen.toLocaleString('id-ID', {
                        maximumFractionDigits: 1
                    }) + '%)' :
                    'Target belum diisi';
                html += '<div class="p-row">' +
                    '<div class="p-rank">' + ('0' + (i + 1)).slice(-2) + '</div>' +
                    '<div class="p-name"><div class="n">' + esc(r.nm_sales) + '</div><div class="s">' + sub + '</div></div>' +
                    '<div class="p-bar-bg"><div class="p-bar" style="width:' + persen + '%"></div></div>' +
                    '<div class="p-val mono" title="' + rp(r.realisasi) + '">' + rpShort(r.realisasi) + '</div>' +
                    '</div>';
            });
            $('#top-sales').html(html);
        }

        function renderTable(sel, rows, macet) {
            var html = '';
            if (!rows.length) {
                html = '<tr><td colspan="8" class="empty">Tidak ada data.</td></tr>';
            }
            $.each(rows, function(i, r) {
                var badge = (macet || r.hari_telat > 60) ? 'high' : 'mid';
                html += '<tr>' +
                    '<td><b>' + esc(r.nm_customer) + '</b></td>' +
                    '<td class="mono">' + esc(r.id_invoice) + '</td>' +
                    '<td class="mono">' + tgl(r.jatuh_tempo) + '</td>' +
                    '<td class="text-center"><span class="days-badge mono ' + badge + '">' + num(r.hari_telat) + '</span></td>' +
                    '<td class="num mono">' + rp(r.sisa_piutang) + '</td>' +
                    '<td>' + esc(r.nm_sales || '-') + '</td>' +
                    '<td class="mono">' + tgl(r.tgl_terakhir_bayar) + '</td>' +
                    '<td><span class="stamp ' + (macet ? 'macet' : 'jt') + '">' + (macet ? 'Macet' : 'Jatuh Tempo') + '</span></td>' +
                    '</tr>';
            });
            $(sel + ' tbody').html(html);
        }

        function load() {
            $.ajax({
                url: url_data,
                type: 'POST',
                dataType: 'json',
                data: {
                    bulan: $('#f-bulan').val(),
                    tahun: $('#f-tahun').val()
                },
                beforeSend: function() {
                    $('#btn-tampil').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Memuat...');
                },
                success: function(res) {
                    if (!res || !res.status) {
                        swal({
                            title: 'Error',
                            text: (res && res.message) || 'Gagal memuat data.',
                            type: 'error'
                        });
                        return;
                    }
                    $('#meta-periode').text('Periode ' + res.periode);
                    $('#meta-tanggal').text(tgl(res.tanggal));
                    $('#top-periode').text(res.periode);

                    renderKpi(res.kpi);
                    renderTrend(res.trend);
                    renderKategori(res.kategori);
                    $('#kategori-periode').text(res.periode);
                    renderAging(res.aging);
                    renderTopSales(res.top_sales);
                    renderTable('#tbl-jt', res.jatuh_tempo, false);
                    renderTable('#tbl-macet', res.macet, true);

                    $('#cnt-jt').text(num(res.kpi.jt_invoice) + ' invoice');
                    $('#cnt-macet').text(num(res.kpi.macet_invoice) + ' invoice');

                    if (res.kpi.macet_invoice > 0) {
                        $('#macet-callout-text').html('<b>Perhatian penagihan:</b> ' + rp(res.kpi.macet_total) +
                            ' dari ' + num(res.kpi.macet_invoice) + ' invoice sudah lewat jatuh tempo lebih dari 90 hari.');
                        $('#macet-callout').show();
                    } else {
                        $('#macet-callout').hide();
                    }
                },
                error: function() {
                    swal({
                        title: 'Error',
                        text: 'Koneksi gagal, coba lagi.',
                        type: 'error'
                    });
                },
                complete: function() {
                    $('#btn-tampil').prop('disabled', false).html('<i class="fa fa-refresh"></i> Tampilkan');
                }
            });
        }

        $('#btn-tampil').on('click', load);
        load();
    });
</script>