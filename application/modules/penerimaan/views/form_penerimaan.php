<div class="box box-primary">
    <div class="box-body">
        <form id="data-form" method="POST">
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-6">
                        <!-- Tanggal Penerimaan -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label>Tanggal Pembayaran <span class="text-red">*</span></label>
                            </div>
                            <div class="col-md-8">
                                <input type="date" class="form-control" name="tgl_pembayaran" id="tgl_pembayaran" min="2025-09-01">
                            </div>
                        </div>

                        <!-- Customer -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label>Customer <span class="text-red">*</span></label>
                            </div>
                            <div class="col-md-8">
                                <select name="id_customer" id="id_customer" class="form-control select2">
                                    <option value="">-- Pilih ---</option>
                                    <?php foreach ($customers as $cs): ?>
                                        <option value="<?= $cs->id_customer ?>">
                                            <?= $cs->name_customer ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Customer -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label>Pilih Bank <span class="text-red">*</span></label>
                            </div>
                            <div class="col-md-8">
                                <select name="bank" id="bank" class="form-control select2" <?= $disabled ?>>
                                    <option value="">-- Pilih ---</option>
                                    <?php foreach ($bank as $b): ?>
                                        <option value="<?= $b->no_perkiraan; ?>" data-nama="<?= $b->nama; ?>">
                                            <?= $b->nama . " (" . $b->no_perkiraan . ")" ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <input type="hidden" id="bank_name" name="bank_name" value="">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <!-- Keterangan Pembayaran -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label>Keterangan Pembayaran</label>
                            </div>
                            <div class="col-md-8">
                                <textarea name="ket_bayar" class="form-control"></textarea>
                            </div>
                        </div>

                        <!-- Pembayaran Bank -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label>Penerimaan Bank <span class="text-red">*</span></label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" name="total_bank" class="form-control total-bank moneyFormat text-right" id="totalBank" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="col-md-12">
                        <a href="javascript:void(0);" class="btn btn-sm btn-success" id="selectInv"><i class="fa fa-plus"></i> Add Invoice</a>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" id="tableInv">
                                <thead class="bg-blue">
                                    <tr>
                                        <th style="min-width: 20px;" class="text-nowrap">No</th>
                                        <th style="min-width: 250px;" class="text-nowrap">No Invoice</th>
                                        <th style="min-width: 100px;" class="text-nowrap">Nominal Invoice</th>
                                        <th style="min-width: 100px;" class="text-nowrap">Sisa Invoice</th>
                                        <th style="min-width: 100px;" class="text-nowrap">Nominal Bayar</th>
                                        <th style="min-width: 20px;" class="text-nowrap"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr class="bg-info">
                                        <th colspan="4" class="text-right">Total Bayar Invoice</th>
                                        <th>
                                            <input type="text" name="total_terima" class="form-control input-sm moneyFormat text-right" id="totalBayarInvoice" readonly>
                                        </th>
                                        <th colspan="4"></th>
                                    </tr>
                                    <tr class="bg-info">
                                        <th colspan="4" class="text-right">Total Tagihan Invoice</th>
                                        <th>
                                            <input type="text" name="total_invoice" class="form-control input-sm moneyFormat text-right" id="totalInvoice" readonly>
                                        </th>
                                        <th colspan="4"></th>
                                    </tr>
                                    <tr class="bg-info">
                                        <th colspan="4" class="text-right">Selisih</th>
                                        <th>
                                            <input type="text" name="selisih" class="form-control input-sm moneyFormat text-right" id="selisih" readonly>
                                        </th>
                                        <th colspan="4"></th>
                                    </tr>
                                    <tr class="bg-warning" id="rowSelisihKurang" hidden>
                                        <th colspan="4" class="text-right">Selisih Kurang</th>
                                        <th>
                                            <input type="text" name="selisih_kurang" class="form-control input-sm moneyFormat text-right" id="selisihKurang" readonly>
                                        </th>
                                        <th colspan="4" class="text-left">
                                            <label class="text-nowrap" style="font-weight:normal;" id="labelBulatkanKurang" hidden>
                                                <input type="checkbox" id="bulatkanKurang"> Bulatkan kekurangan (maks 1.000)
                                            </label>
                                            <input type="hidden" name="pembulatan_kurang" id="pembulatanKurang" value="0">
                                        </th>
                                    </tr>
                                    <tr class="bg-info" hidden>
                                        <th colspan="4" class="text-right">Biaya Administrasi</th>
                                        <th>
                                            <input type="text" name="biaya_adm" class="form-control input-sm moneyFormat text-right" id="biayaAdm">
                                        </th>
                                        <th colspan="4"></th>
                                    </tr>
                                    <tr class="bg-info" hidden>
                                        <th colspan="4" class="text-right">Lebih Bayar</th>
                                        <th>
                                            <input type="text" name="lebih_bayar" class="form-control input-sm moneyFormat text-right" id="lebihBayar">
                                        </th>
                                        <th colspan="4"></th>
                                    </tr>
                                    <tr class="bg-info">
                                        <th colspan="4" class="text-right">Pembulatan</th>
                                        <th>
                                            <input type="text" name="pembulatan" class="form-control input-sm moneyFormat text-right" id="pembulatan" value="0" placeholder="0">
                                        </th>
                                        <th colspan="4"></th>
                                    </tr>
                                    <tr class="bg-info" hidden>
                                        <th colspan="4" class="text-right">Kontrol</th>
                                        <th>
                                            <input type="text" name="kontrol" class="form-control input-sm moneyFormat text-right" id="kontrol" readonly>
                                        </th>
                                        <th colspan="4"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="col-md-12">
                        <hr>
                        <label>Informasi Jurnal</label>
                        <div class="table-responsive">
                            <table id="tableJurnal" class="table table-bordered table-hover">
                                <thead>
                                    <tr bgcolor='#9acfea'>
                                        <th class="text-center">Tanggal</th>
                                        <th class="text-center">Tipe</th>
                                        <th class="text-center">No. COA</th>
                                        <th class="text-center">Nama COA</th>
                                        <th class="text-center">Keterangan</th>
                                        <th class="text-center">Debit</th>
                                        <th class="text-center">Kredit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- KOSONG -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" align="right"><b>TOTAL</b></td>
                                        <td><input type="text" id="totalDebit" name="total_debit" class="form-control text-right" readonly></td>
                                        <td><input type="text" id="totalKredit" name="total_kredit" class="form-control text-right" readonly></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-success" id="btnSave"><i class="fa fa-save"></i> Save</button>
                        <a class="btn btn-default" onclick="window.history.back(); return false;">
                            <i class="fa fa-reply"></i> Batal
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Daftar Invoice -->
<div class="modal fade" id="ModalInv" tabindex="-1" role="dialog" aria-labelledby="modalDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel"><span class="fa fa-archive"></span>&nbsp;Daftar Invoice</h4>
            </div>
            <div class="modal-body">
                <table class="table table-bordered" id="tableModalInv" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal Invoice</th>
                            <th>No Invoice</th>
                            <th>Tanggal SO</th>
                            <th>No SO</th>
                            <th>Total Invoice</th>
                            <th class="text-center">Pilih</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" id="btnPilihInv">Pilih</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal History Credit Note -->
<!-- <div class="modal fade" id="ModalCNHistory" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-orange">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-history"></i> History Credit Note</h4>
            </div>
            <div class="modal-body">
                <p id="cn-invoice-label" class="text-bold"></p>
                <table class="table table-bordered table-striped" id="tableCNHistory">
                    <thead class="bg-orange">
                        <tr>
                            <th>No. Retur</th>
                            <th>Tgl. Retur</th>
                            <th class="text-right">Nilai Inv. Asal</th>
                            <th class="text-right">Nilai Retur</th>
                            <th class="text-right">Nilai Inv. Baru</th>
                            <th>Alasan</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="bg-yellow">
                            <th colspan="3" class="text-right">Total Nilai Retur</th>
                            <th class="text-right" id="cn-total-retur">0</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div> -->


<script src="<?= base_url('assets/plugins/jquery-inputmask/jquery.inputmask.js') ?>"></script>
<script src="<?= base_url('assets/plugins/select2/select2.full.min.js') ?>"></script>

<script>
    $(document).ready(function() {
        let selectedInvoiceIds = [];
        let selectedCnIds = []; // array no_retur CN yang dipilih
        let selectedCnData = {}; // map no_retur => {invoice, nilai}

        $('.select2').select2({
            width: '100%'
        });

        $('.moneyFormat').each(function() {
            let val = parseFloat($(this).val().replace(/,/g, '')) || 0;
            $(this).val(number_format(val, 2));
        });

        $('#bank').on('change', function() {
            updateInvoiceTotals();
            generateJurnal();
        });

        $(document).on('click', '.btn-remove', function() {
            const $row = $(this).closest('tr');
            const id_invoice = $row.find('input[name*="[id_invoice]"]').val();

            // Hapus dari array selected invoice
            selectedInvoiceIds = selectedInvoiceIds.filter(id => id !== id_invoice);

            // Hapus CN yang terkait invoice ini dari selectedCnIds
            $row.find('input[name*="[cn]"][name*="[no_retur]"]').each(function() {
                const no_retur = $(this).val();
                selectedCnIds = selectedCnIds.filter(id => id !== no_retur);
                delete selectedCnData[no_retur];
            });

            // Hapus baris
            $row.remove();

            // Re-number baris
            $('#tableInv tbody tr').each(function(i, row) {
                $(row).find('td:first').text(i + 1);
            });
            updateInvoiceTotals();
            generateJurnal();
        });

        // Tombol Pilih Inv
        $('#selectInv').on('click', function() {
            const tgl_pembayaran = $('#tgl_pembayaran').val();
            const id_customer = $('#id_customer').val();

            if (!tgl_pembayaran) {
                swal({
                    title: "Error Message !",
                    text: 'Silahkan Pilih Tanggal Pembayaran terlebih dahulu..',
                    type: "warning",
                    timer: 7000,
                    showCancelButton: false,
                    showConfirmButton: true,
                    allowOutsideClick: trigger_error
                });
                return;
            }

            if (!id_customer) {
                swal({
                    title: "Error Message !",
                    text: 'Silahkan Pilih Customer terlebih dahulu..',
                    type: "warning",
                    timer: 7000,
                    showCancelButton: false,
                    showConfirmButton: true,
                    allowOutsideClick: true
                });
                return;
            }

            // Ambil data invoice dari server
            $.ajax({
                url: siteurl + 'penerimaan/get_inv',
                type: 'GET',
                data: {
                    id_customer
                },
                success: function(res) {
                    const data = JSON.parse(res);
                    let html = '';
                    let no = 1;
                    let currentCustomer = '';

                    if (data.length === 0) {
                        html = `<tr><td colspan="5" class="text-center">Tidak ada data invoice</td></tr>`;
                    } else {
                        data.forEach((item) => {
                            if (item.name_customer !== currentCustomer) {
                                html += `
                                            <tr style="background-color:#f0f0f0; font-weight:bold;">
                                                <td colspan="8">Customer: ${item.name_customer}</td>
                                            </tr>
                                        `;
                                currentCustomer = item.name_customer;
                            }

                            // Badge credit note jika ada
                            let cnBadge = '';
                            if (parseInt(item.jumlah_cn) > 0) {
                                cnBadge = `<span class="badge bg-orange" title="Ada credit note">CN</span>`;
                            }

                            const invChecked = selectedInvoiceIds.includes(item.id_invoice) ? 'checked' : '';

                            html += `
                        	<tr class="inv-row" data-invoice="${item.id_invoice}">
                                <td class="text-center">${no++}</td>
                                <td>${item.tgl_inv}</td>
                                <td>${item.id_invoice} ${cnBadge}</td>
                                <td>${item.tgl_so ?? '-'}</td>
                                <td>${item.id_so ?? '-'}</td>
                                <td class="text-right inv-sisa-tagihan" data-original="${parseFloat(item.sisa_tagihan)}">${parseFloat(item.sisa_tagihan).toLocaleString('id-ID')}</td>
                                <td class="text-center">
                                  <input type="checkbox" class="select-inv" data-inv='${JSON.stringify(item)}' ${invChecked}>
                                </td>
                            </tr>
                    `;

                            // Baris CN di bawah invoice (jika ada)
                            if (item.cn_rows && item.cn_rows.length > 0) {
                                item.cn_rows.forEach(function(cn) {
                                    const cnVal = parseFloat(cn.nilai_retur || 0);
                                    const cnChecked = selectedCnIds.includes(cn.no_retur) ? 'checked' : '';
                                    html += `
                                    <tr class="cn-row" data-invoice="${item.id_invoice}" data-no-retur="${cn.no_retur}" style="background-color:#fff8e1;">
                                        <td></td>
                                        <td colspan="1" class="text-muted" style="padding-left:30px;">
                                            <i class="fa fa-level-down text-orange"></i>
                                            <small class="text-orange"><b>Credit Note</b></small>
                                        </td>
                                        <td class="text-orange"><small>${cn.no_retur}</small></td>
                                        <td colspan="2"><small class="text-muted">${cn.tgl_retur || '-'}</small></td>
                                        <td class="text-right text-orange"><small><b>- ${cnVal.toLocaleString('id-ID')}</b></small></td>
                                        <td class="text-center">
                                            <input type="checkbox" class="select-cn" 
                                                data-invoice="${item.id_invoice}"
                                                data-no-retur="${cn.no_retur}"
                                                data-nilai="${cnVal}"
                                                ${cnChecked}
                                                ${selectedInvoiceIds.includes(item.id_invoice) ? '' : 'disabled'}>
                                        </td>
                                    </tr>`;
                                });
                            }
                        });
                    }

                    $('#tableModalInv tbody').html(html);
                    // Cekbox chaining logic
                    const checkboxes = $('#tableModalInv .select-inv');
                    checkboxes.prop('disabled', false); //sementara buat aktifin semua checkbox

                    // Handler checkbox CN: update tampilan sisa tagihan invoice di modal
                    $('#tableModalInv').off('change', '.select-cn').on('change', '.select-cn', function() {
                        const id_invoice = $(this).data('invoice');
                        const $invRow = $('#tableModalInv tr.inv-row[data-invoice="' + id_invoice + '"]');
                        const $sisaCell = $invRow.find('.inv-sisa-tagihan');
                        const originalVal = parseFloat($sisaCell.data('original')) || 0;

                        // Hitung total CN yang dicentang untuk invoice ini
                        let totalCnChecked = 0;
                        $('#tableModalInv tr.cn-row[data-invoice="' + id_invoice + '"] .select-cn:checked').each(function() {
                            totalCnChecked += parseFloat($(this).data('nilai')) || 0;
                        });

                        const sisaBaru = originalVal - totalCnChecked;
                        $sisaCell.text(sisaBaru.toLocaleString('id-ID'));
                    });

                    // Handler checkbox invoice: enable/disable CN di bawahnya
                    $('#tableModalInv').off('change', '.select-inv').on('change', '.select-inv', function() {
                        const invData = $(this).data('inv');
                        const id_invoice = invData.id_invoice;
                        const isChecked = $(this).is(':checked');
                        const $cnRows = $('#tableModalInv tr.cn-row[data-invoice="' + id_invoice + '"]');

                        if (isChecked) {
                            // Invoice dicentang → enable CN
                            $cnRows.find('.select-cn').prop('disabled', false);
                        } else {
                            // Invoice di-uncheck → uncheck dan disable semua CN-nya
                            $cnRows.find('.select-cn').prop('checked', false).prop('disabled', true);

                            // Reset tampilan sisa tagihan ke nilai original
                            const $invRow = $('#tableModalInv tr.inv-row[data-invoice="' + id_invoice + '"]');
                            const $sisaCell = $invRow.find('.inv-sisa-tagihan');
                            const originalVal = parseFloat($sisaCell.data('original')) || 0;
                            $sisaCell.text(originalVal.toLocaleString('id-ID'));
                        }
                    });

                    $('#ModalInv').modal('show');
                },
                error: function() {
                    swal("Error", "Gagal mengambil data invoice.", "error");
                }
            });
        });

        // Tombol Select Inv
        $('#btnPilihInv').on('click', function() {
            const selectedInvoices = [];

            $('.select-inv:checked').each(function() {
                const data = $(this).data('inv');
                if (data) selectedInvoices.push(data);
            });

            if (selectedInvoices.length === 0) {
                swal("Warning", "Silakan pilih minimal satu invoice.", "warning");
                return;
            }

            // Kumpulkan CN yang dipilih: map id_invoice => total nilai CN
            const cnByInvoice = {};
            const cnDetailByInvoice = {};
            $('.select-cn:checked').each(function() {
                const id_invoice = $(this).data('invoice');
                const no_retur = $(this).data('no-retur');
                const nilai = parseFloat($(this).data('nilai')) || 0;

                if (!cnByInvoice[id_invoice]) {
                    cnByInvoice[id_invoice] = 0;
                    cnDetailByInvoice[id_invoice] = [];
                }
                cnByInvoice[id_invoice] += nilai;
                cnDetailByInvoice[id_invoice].push({
                    no_retur,
                    nilai
                });

                // Simpan ke selectedCnIds agar tetap tercentang saat modal dibuka ulang
                if (!selectedCnIds.includes(no_retur)) {
                    selectedCnIds.push(no_retur);
                    selectedCnData[no_retur] = {
                        id_invoice,
                        nilai
                    };
                }
            });

            let rowIndex = $('#tableInv tbody tr').length;

            selectedInvoices.forEach((inv) => {
                if (selectedInvoiceIds.includes(inv.id_invoice)) return;

                selectedInvoiceIds.push(inv.id_invoice);
                rowIndex++;

                const totalCn = cnByInvoice[inv.id_invoice] || 0;
                const cnDetails = cnDetailByInvoice[inv.id_invoice] || [];
                const sisaTagihan = parseFloat(inv.sisa_tagihan || inv.grand_total || 0);
                const nominal = sisaTagihan - totalCn; // sisa tagihan dikurangi CN

                // Hidden inputs untuk CN yang dipilih pada invoice ini
                let cnHiddenInputs = '';
                cnDetails.forEach(function(cn, ci) {
                    cnHiddenInputs += `
                        <input type="hidden" name="detail[${rowIndex}][cn][${ci}][no_retur]" value="${cn.no_retur}">
                        <input type="hidden" name="detail[${rowIndex}][cn][${ci}][nilai]" value="${cn.nilai}">
                    `;
                });

                // Label CN jika ada
                let cnLabel = '';
                if (cnDetails.length > 0) {
                    const cnList = cnDetails.map(cn => `${cn.no_retur} (-${cn.nilai.toLocaleString('id-ID')})`).join(', ');
                    cnLabel = `<br><small class="text-orange">↳ Credit Note: ${cnList}</small>`;
                }

                $('#tableInv tbody').append(`
                    <tr>
                        <td class="text-center">${rowIndex}</td>
                        <td>${inv.id_invoice}${cnLabel}</td>
                        <td>
                            <input type="text" name="detail[${rowIndex}][tagihan]" class="form-control input-sm text-right tagihan moneyFormat" value="${sisaTagihan}" readonly />
                        </td>
                        <td>
                            <input type="text" name="detail[${rowIndex}][sisa_invoice]" class="form-control input-sm text-right sisa_invoice moneyFormat" value="${nominal}" readonly/>
                        </td>
                        <td>
                            <input type="text" name="detail[${rowIndex}][total_bayar]" class="form-control input-sm text-right total_bayar moneyFormat" value="${nominal}" readonly/>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-danger btn-sm btn-remove"><i class="fa fa-trash"></i></button>
                            <input type="hidden" name="detail[${rowIndex}][id_invoice]" value="${inv.id_invoice}">
                            <input type="hidden" name="detail[${rowIndex}][id_so]" value="${inv.id_so}">
                            <input type="hidden" name="detail[${rowIndex}][total_cn]" value="${totalCn}">
                            ${cnHiddenInputs}
                        </td>
                    </tr>
                `);
            });

            $('#ModalInv').modal('hide');
            moneyFormat('.moneyFormat');
            updateInvoiceTotals();
            generateJurnal();
        });

        // Proses simpan
        $(document).on('submit', '#data-form', function(e) {
            e.preventDefault();

            // Hapus inputmask sebelum serialize agar value bersih
            $('#data-form .moneyFormat').inputmask('remove');

            const form = document.getElementById('data-form');
            const formData = new FormData(form);

            // Re-apply inputmask setelah serialize (untuk display)
            moneyFormat('.moneyFormat');

            swal({
                title: "Warning!",
                text: "Yakin simpan data?",
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, Bayar",
                confirmButtonColor: "#00a65a",
                cancelButtonColor: "#c9302c"
            }, function(confirm) {
                if (confirm) {
                    $.ajax({
                        type: 'POST',
                        url: siteurl + active_controller + 'save',
                        data: formData,
                        contentType: false,
                        processData: false,
                        dataType: 'json',
                        success: function(result) {
                            if (result.status == '1') {
                                swal('Success', result.message, 'success')
                                window.location.href = siteurl + active_controller
                            } else {
                                swal('Failed!', result.message, 'warning');
                            }
                        },
                        error: function() {
                            swal('Error!', 'Please try again later!', 'error');
                        }
                    });
                }
            });
        });

        $('#totalBank').on('input', function() {
            updateInvoiceTotals();
            generateJurnal();
        });

        $('#biayaAdm, #lebihBayar').on('input', function() {
            const totalBank = parseFloat($('#totalBank').val().replace(/,/g, '')) || 0;
            const totalInvoice = parseFloat($('#totalInvoice').val().replace(/,/g, '')) || 0;
            calculateSelisihDanKontrol(totalBank, totalInvoice);
        });

        // Pembulatan: update kontrol & jurnal saat diisi
        $('#pembulatan').on('input', function() {
            const totalBank = parseFloat($('#totalBank').val().replace(/,/g, '')) || 0;
            const totalBayarInvoice = parseFloat($('#totalBayarInvoice').val().replace(/,/g, '')) || 0;
            calculateSelisihDanKontrol(totalBank, totalBayarInvoice);
            generateJurnal();
        });

        // Bulatkan kekurangan: hitung ulang alokasi & jurnal
        $(document).on('change', '#bulatkanKurang', function() {
            updateInvoiceTotals();
        });

        // Handler tombol lihat history credit note
        $(document).on('click', '.btn-lihat-cn', function() {
            const id_invoice = $(this).data('invoice');
            $('#cn-invoice-label').text('Invoice: ' + id_invoice);
            $('#tableCNHistory tbody').html('<tr><td colspan="6" class="text-center"><i class="fa fa-spinner fa-spin"></i> Memuat...</td></tr>');
            $('#ModalCNHistory').modal('show');

            $.ajax({
                url: siteurl + 'retur_credit_note/get_cn_history',
                type: 'GET',
                data: {
                    id_invoice: id_invoice
                },
                dataType: 'json',
                success: function(res) {
                    let html = '';
                    let totalRetur = 0;
                    const rows = res.rows || [];
                    const nilaiInvAsal = parseFloat(res.nilai_inv_asal || 0);

                    if (!rows || rows.length === 0) {
                        html = '<tr><td colspan="6" class="text-center">Tidak ada data credit note.</td></tr>';
                    } else {
                        // Hitung nilai inv asal per baris: mundur dari asal ke bawah
                        let nilaiAwalBaris = nilaiInvAsal;
                        rows.forEach(function(cn) {
                            const nilaiRetur = parseFloat(cn.nilai_retur || 0);
                            const nilaiInvBaru = parseFloat(cn.nilai_inv_baru || 0);
                            totalRetur += nilaiRetur;
                            html += `<tr>
                                <td>${cn.no_retur}</td>
                                <td>${cn.tgl_retur}</td>
                                <td class="text-right">${nilaiAwalBaris.toLocaleString('id-ID')}</td>
                                <td class="text-right">${nilaiRetur.toLocaleString('id-ID')}</td>
                                <td class="text-right">${nilaiInvBaru.toLocaleString('id-ID')}</td>
                                <td>${cn.alasan || '-'}</td>
                            </tr>`;
                            nilaiAwalBaris = nilaiInvBaru; // inv asal baris berikutnya = inv baru baris ini
                        });
                    }
                    $('#tableCNHistory tbody').html(html);
                    $('#cn-total-retur').text(totalRetur.toLocaleString('id-ID'));
                },
                error: function() {
                    $('#tableCNHistory tbody').html('<tr><td colspan="6" class="text-center text-red">Gagal memuat data.</td></tr>');
                }
            });
        });
    })

    function generateJurnal() {

        let jurnalHTML = '';
        let totalDebit = 0;
        let totalKredit = 0;

        const today = $('#tgl_pembayaran').val() || '';
        const customerName = $('#id_customer option:selected').text().trim();

        const noPerkiraan = $('#bank').val() || '';
        const namaBank = $('#bank option:selected').data('nama') || '';

        // let totalBayar = parseFloat($('#totalBank').val());
        let totalBayar = parseFloat($('#totalBank').val().replace(/,/g, '')) || 0;


        // =========================
        // PIUTANG + CN
        // =========================
        $('#tableInv tbody tr').each(function() {
            const $row = $(this);
            // Ambil nama invoice dari td ke-2 (teks sebelum label CN)
            const invoiceRaw = $row.find('td:eq(1)').clone();
            invoiceRaw.find('small, br').remove();
            const invoice = invoiceRaw.text().trim();

            const bayar = parseFloat($row.find('.total_bayar').val().replace(/,/g, '')) || 0;
            const totalCn = parseFloat($row.find('input[name*="[total_cn]"]').val()) || 0;
            const totalKreditInv = bayar + totalCn; // total pengurangan piutang = bayar bank + CN

            if (totalKreditInv > 0) {
                jurnalHTML += `
            <tr>
                <td><input type="date" name="tgl_jurnal[]" value="${today}" class="form-control" readonly></td>
                <td><input type="text" name="type[]" value="BUM" class="form-control" readonly></td>
                <td><input type="text" name="no_coa[]" value="1102-01-01" class="form-control" readonly></td>
                <td><input type="text" name="nama_coa[]" value="Piutang Dagang" class="form-control" readonly></td>
                <td><textarea name="keterangan[]" class="form-control" readonly>Pembayaran Invoice ${invoice} A/n ${customerName}</textarea></td>

                <td><input type="hidden" name="debet[]" value="0">
                <input type="text" value="0" class="form-control text-right" readonly></td>

                <td><input type="hidden" name="kredit[]" value="${totalKreditInv}">
                <input type="text" value="${number_format(totalKreditInv,2)}" class="form-control text-right" readonly></td>
            </tr>
            `;
                totalKredit += totalKreditInv;
            }

            // Baris CN: Debit Retur Penjualan (offset agar jurnal balance)
            if (totalCn > 0) {
                jurnalHTML += `
            <tr>
                <td><input type="date" name="tgl_jurnal[]" value="${today}" class="form-control" readonly></td>
                <td><input type="text" name="type[]" value="BUM" class="form-control" readonly></td>
                <td><input type="text" name="no_coa[]" value="4102-01-01" class="form-control" readonly></td>
                <td><input type="text" name="nama_coa[]" value="Retur Penjualan" class="form-control" readonly></td>
                <td><textarea name="keterangan[]" class="form-control" readonly>Penggunaan Credit Note Invoice ${invoice} A/n ${customerName}</textarea></td>

                <td><input type="hidden" name="debet[]" value="${totalCn}">
                <input type="text" value="${number_format(totalCn,2)}" class="form-control text-right" readonly></td>

                <td><input type="hidden" name="kredit[]" value="0">
                <input type="text" value="0" class="form-control text-right" readonly></td>
            </tr>
            `;
                totalDebit += totalCn;
            }
        });

        // =========================
        // BANK (DEBIT)
        // =========================
        if (totalBayar > 0) {
            jurnalHTML += `
        <tr>
            <td><input type="date" name="tgl_jurnal[]" value="${today}" class="form-control" readonly></td>
            <td style="min-width: 10px;"><input type="text" name="type[]" value="BUM" class="form-control" readonly></td>
            <td><input type="text" name="no_coa[]" value="${noPerkiraan}" class="form-control" readonly></td>
            <td><input type="text" name="nama_coa[]" value="${namaBank}" class="form-control" readonly></td>
            <td><textarea name="keterangan[]" class="form-control" readonly>Penerimaan uang via ${namaBank}</textarea></td>

            <td>
                <input type="hidden" name="debet[]" value="${totalBayar}">
                <input type="text" value="${number_format(totalBayar,2)}" class="form-control text-right" readonly>
            </td>

            <td>
                <input type="hidden" name="kredit[]" value="0">
                <input type="text" value="0" class="form-control text-right" readonly>
            </td>
        </tr>
        `;
            totalDebit += totalBayar;
        }


        // =========================
        // BIAYA ADMIN
        // =========================
        let biayaAdmin = parseFloat($('#biaya_admin').val());

        if (biayaAdmin > 0) {
            jurnalHTML += `
        <tr>
            <td><input type="date" name="tgl_jurnal[]" value="${today}" class="form-control" readonly></td>
            <td><input type="text" name="type[]" value="BUM" class="form-control" readonly></td>
            <td><input type="text" name="no_coa[]" value="7201-01-02" class="form-control" readonly></td>
            <td><input type="text" name="nama_coa[]" value="Biaya Adm Bank & Buku Cek/Giro" class="form-control" readonly></td>
            <td><textarea name="keterangan[]" class="form-control" readonly>Pembayaran Biaya Admin Bank</textarea></td>

            <td><input type="hidden" name="debet[]" value="${biayaAdmin}">
            <input type="text" value="${number_format(biayaAdmin,2)}" class="form-control text-right" readonly></td>

            <td><input type="hidden" name="kredit[]" value="0">
            <input type="text" value="0" class="form-control text-right" readonly></td>
        </tr>
        `;
            totalDebit += biayaAdmin;
        }

        // =========================
        // BIAYA PEMBULATAN
        // =========================
        let pembulatan = parseFloat($('#pembulatan').val().replace(/,/g, '')) || 0;

        if (pembulatan > 0) {
            // Kumpulkan no invoice dari semua baris
            let invoiceList = [];
            $('#tableInv tbody tr').each(function() {
                const $row = $(this);
                const invoiceRaw = $row.find('td:eq(1)').clone();
                invoiceRaw.find('small, br').remove();
                const inv = invoiceRaw.text().trim();
                if (inv) invoiceList.push(inv);
            });
            const keteranganPembulatan = invoiceList.length > 0 ?
                'Biaya Pembulatan untuk pembayaran Invoice ' + invoiceList.join(', ') :
                'Biaya Pembulatan';

            jurnalHTML += `
        <tr>
            <td><input type="date" name="tgl_jurnal[]" value="${today}" class="form-control" readonly></td>
            <td><input type="text" name="type[]" value="BUM" class="form-control" readonly></td>
            <td><input type="text" name="no_coa[]" value="7201-01-06" class="form-control" readonly></td>
            <td><input type="text" name="nama_coa[]" value="Biaya Pembulatan" class="form-control" readonly></td>
            <td><textarea name="keterangan[]" class="form-control" readonly>${keteranganPembulatan}</textarea></td>

            <td><input type="hidden" name="debet[]" value="0">
            <input type="text" value="0" class="form-control text-right" readonly></td>

            <td><input type="hidden" name="kredit[]" value="${pembulatan}">
            <input type="text" value="${number_format(pembulatan,2)}" class="form-control text-right" readonly></td>
        </tr>
        `;
            totalKredit += pembulatan;
        }

        // =========================
        // BIAYA PEMBULATAN (KEKURANGAN BAYAR)
        // =========================
        // Saat kurang bayar dan user memilih dibulatkan, kekurangan dibebankan
        // sebagai Biaya Pembulatan di sisi DEBET agar jurnal tetap balance.
        let pembulatanKurang = parseFloat($('#pembulatanKurang').val()) || 0;

        if (pembulatanKurang > 0) {
            let invoiceListK = [];
            $('#tableInv tbody tr').each(function() {
                const $row = $(this);
                const invoiceRaw = $row.find('td:eq(1)').clone();
                invoiceRaw.find('small, br').remove();
                const inv = invoiceRaw.text().trim();
                if (inv) invoiceListK.push(inv);
            });
            const keteranganPembulatanK = invoiceListK.length > 0 ?
                'Biaya Pembulatan (kurang bayar) Invoice ' + invoiceListK.join(', ') :
                'Biaya Pembulatan (kurang bayar)';

            jurnalHTML += `
        <tr>
            <td><input type="date" name="tgl_jurnal[]" value="${today}" class="form-control" readonly></td>
            <td><input type="text" name="type[]" value="BUM" class="form-control" readonly></td>
            <td><input type="text" name="no_coa[]" value="7201-01-06" class="form-control" readonly></td>
            <td><input type="text" name="nama_coa[]" value="Biaya Pembulatan" class="form-control" readonly></td>
            <td><textarea name="keterangan[]" class="form-control" readonly>${keteranganPembulatanK}</textarea></td>

            <td><input type="hidden" name="debet[]" value="${pembulatanKurang}">
            <input type="text" value="${number_format(pembulatanKurang,2)}" class="form-control text-right" readonly></td>

            <td><input type="hidden" name="kredit[]" value="0">
            <input type="text" value="0" class="form-control text-right" readonly></td>
        </tr>
        `;
            totalDebit += pembulatanKurang;
        }

        $('#tableJurnal tbody').html(jurnalHTML);

        $('#totalDebit').val(number_format(totalDebit, 2));
        $('#totalKredit').val(number_format(totalKredit, 2));

        if (totalDebit !== totalKredit) {
            $('#totalDebit').addClass('bg-red');
            $('#totalKredit').addClass('bg-red');
            $('#btnSave').prop('disabled', true)
        } else {
            $('#totalDebit').removeClass('bg-red');
            $('#totalKredit').removeClass('bg-red');
            $('#btnSave').prop('disabled', false);
        }
    }

    // Batas maksimal nilai yang boleh dibulatkan saat kurang bayar
    const MAX_PEMBULATAN_KURANG = 1000;

    function updateInvoiceTotals() {
        let totalInvoice = 0;
        let totalBayarInvoice = 0;
        let totalBank = parseFloat($('#totalBank').val().replace(/,/g, '')) || 0;
        let sisaBank = totalBank;
        let bank = $('#bank').val();

        // Simpan referensi baris terakhir yang masih kurang bayar (sisa > 0)
        let $lastUnderpaidRow = null;
        let lastUnderpaidSisa = 0;
        let lastUnderpaidBayar = 0;

        // Reset penanda pembulatan per baris terlebih dahulu
        $('#tableInv tbody tr').find('input[name*="[pembulatan]"]').val(0);

        // Loop per baris invoice
        $('#tableInv tbody tr').each(function() {
            const $row = $(this);
            const tagihan = parseFloat($row.find('.tagihan').val().replace(/,/g, '')) || 0;
            const totalCn = parseFloat($row.find('input[name*="[total_cn]"]').val()) || 0;
            const tagihanSetelahCn = tagihan - totalCn; // tagihan efektif setelah dikurangi CN

            totalInvoice += tagihanSetelahCn;

            let bayar = 0;
            if (sisaBank >= tagihanSetelahCn) {
                bayar = tagihanSetelahCn;
                sisaBank -= tagihanSetelahCn;
            } else {
                bayar = sisaBank;
                sisaBank = 0;
            }

            const sisa = tagihanSetelahCn - bayar;
            totalBayarInvoice += bayar

            // Set Total Bayar
            $row.find('.total_bayar').val(number_format(bayar, 2));
            // Set Sisa Invoice
            $row.find('.sisa_invoice').val(number_format(sisa, 2));

            // Catat baris terakhir yang masih kurang (dipakai untuk pembulatan kekurangan)
            if (sisa > 0.001) {
                $lastUnderpaidRow = $row;
                lastUnderpaidSisa = sisa;
                lastUnderpaidBayar = bayar;
            }
        });

        // =========================
        // PEMBULATAN KEKURANGAN
        // =========================
        // Selisih kurang = total tagihan - total yang terbayar dari bank
        const selisihKurang = totalInvoice - totalBayarInvoice;
        let pembulatanKurang = 0;

        // Tampilkan baris "Selisih Kurang" hanya saat memang kurang bayar
        if (selisihKurang > 0.001) {
            $('#rowSelisihKurang').removeAttr('hidden');
            $('#selisihKurang').val(number_format(selisihKurang, 2));

            // Checkbox hanya boleh dipakai jika kekurangan <= batas maksimal
            if (selisihKurang <= MAX_PEMBULATAN_KURANG + 0.001) {
                $('#labelBulatkanKurang').removeAttr('hidden');
                $('#bulatkanKurang').prop('disabled', false);
            } else {
                $('#labelBulatkanKurang').attr('hidden', true);
                $('#bulatkanKurang').prop('checked', false).prop('disabled', true);
            }
        } else {
            $('#rowSelisihKurang').attr('hidden', true);
            $('#labelBulatkanKurang').attr('hidden', true);
            $('#selisihKurang').val(number_format(0, 2));
            $('#bulatkanKurang').prop('checked', false);
        }

        // Jika user memilih bulatkan kekurangan dan masih dalam batas,
        // bebankan kekurangan ke faktur TERAKHIR yang masih kurang bayar.
        if (
            $('#bulatkanKurang').is(':checked') &&
            selisihKurang > 0.001 &&
            selisihKurang <= MAX_PEMBULATAN_KURANG + 0.001 &&
            $lastUnderpaidRow
        ) {
            pembulatanKurang = selisihKurang;

            // Faktur terakhir dianggap lunas: total bayar ditambah kekurangan, sisa jadi 0
            const bayarBaru = lastUnderpaidBayar + pembulatanKurang;
            $lastUnderpaidRow.find('.total_bayar').val(number_format(bayarBaru, 2));
            $lastUnderpaidRow.find('.sisa_invoice').val(number_format(0, 2));

            // Simpan nilai pembulatan pada baris tersebut (untuk server-side)
            let $pembInput = $lastUnderpaidRow.find('input[name*="[pembulatan]"]');
            if ($pembInput.length === 0) {
                // Buat hidden input pembulatan bila belum ada, gunakan index dari input id_invoice
                const idInvName = $lastUnderpaidRow.find('input[name*="[id_invoice]"]').attr('name') || '';
                const m = idInvName.match(/detail\[(\d+)\]/);
                const idx = m ? m[1] : 0;
                $lastUnderpaidRow.find('td:last').append(
                    '<input type="hidden" name="detail[' + idx + '][pembulatan]" value="' + pembulatanKurang + '">'
                );
            } else {
                $pembInput.val(pembulatanKurang);
            }

            // totalBayarInvoice tidak diubah: bank tetap segitu, kekurangan ditutup pembulatan
        }

        $('#pembulatanKurang').val(pembulatanKurang);

        $('#totalInvoice').val(number_format(totalInvoice, 2));
        $('#totalBayarInvoice').val(number_format(totalBayarInvoice, 2));

        $('#no_coa1').val(bank)

        calculateSelisihDanKontrol(totalBank, totalBayarInvoice);
        generateJurnal()
    }


    function calculateSelisihDanKontrol(totalBank, totalBayarInvoice) {
        const biayaAdm = parseFloat($('#biayaAdm').val().replace(/,/g, '')) || 0;
        const lebihBayar = parseFloat($('#lebihBayar').val().replace(/,/g, '')) || 0;
        const pembulatan = parseFloat($('#pembulatan').val().replace(/,/g, '')) || 0;
        const pembulatanKurang = parseFloat($('#pembulatanKurang').val()) || 0;

        const selisih = totalBank - totalBayarInvoice;
        // pembulatanKurang menutup kekurangan, jadi ditambahkan agar kontrol kembali 0
        const kontrol = selisih + biayaAdm - lebihBayar - pembulatan + pembulatanKurang;

        $('#selisih').val(number_format(selisih, 2));
        $('#kontrol').val(number_format(kontrol, 2));

        // Enable/Disable tombol Save berdasarkan nilai kontrol
        if (Math.abs(kontrol) < 0.01) {
            $('#btnSave').prop('disabled', false);
        } else {
            $('#btnSave').prop('disabled', true);
        }
    }


    function moneyFormat(e) {
        $(e).inputmask({
            alias: "decimal",
            digits: 2,
            radixPoint: ".",
            autoGroup: true,
            placeholder: "0",
            rightAlign: false,
            allowMinus: true,
            integerDigits: 13,
            groupSeparator: ",",
            digitsOptional: false,
            showMaskOnHover: true,
        })
    }

    function number_format(number, decimals = 2, dec_point = '.', thousands_sep = ',') {
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number;
        var prec = !isFinite(+decimals) ? 0 : Math.abs(decimals);
        var sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep;
        var dec = (typeof dec_point === 'undefined') ? '.' : dec_point;
        var s = '';

        var toFixedFix = function(n, prec) {
            var k = Math.pow(10, prec);
            return '' + Math.round(n * k) / k;
        };

        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }
</script>