<style>
    .otp-input-container .otp-input {
        width: 48px;
        height: 56px;
        text-align: center;
        font-size: 24px;
        border: 2px solid #ccc;
        border-radius: 8px;
        outline: none;
        transition: border-color 0.3s;
    }

    .otp-input:focus {
        border-color: #28a745;
        box-shadow: 0 0 5px rgba(40, 167, 69, 0.5);
    }
</style>
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
                                <input type="date" class="form-control" name="tgl_pembayaran" id="tgl_pembayaran"
                                    min="2025-09-01">
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
                    </div>
                    <div class="col-md-6">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label>Keterangan </label>
                            </div>
                            <div class="col-md-8">
                                <textarea name="ket_bayar" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="col-md-12">
                        <a href="javascript:void(0);" class="btn btn-sm btn-success" id="selectInv"><i class="fa fa-plus"></i> Add Invoice</a>
                        <hr>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="tableInv">
                                <thead class="bg-blue">
                                    <tr>
                                        <th style="min-width: 20px;" class="text-nowrap">No</th>
                                        <th style="min-width: 100px;" class="text-nowrap">No Invoice</th>
                                        <th style="min-width: 100px;" class="text-nowrap">Total Invoice</th>
                                        <th style="min-width: 100px;" class="text-nowrap">Sisa Invoice</th>
                                        <th style="min-width: 100px;" class="text-nowrap">Total Bayar</th>
                                        <th style="min-width: 20px;" class="text-nowrap"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr class="bg-info">
                                        <th colspan="2" class="text-right">TOTAL</th>
                                        <th>
                                            <input type="text" name="total_invoice" class="form-control auto_num text-right" id="totalInvoice" readonly>
                                        </th>
                                        <th></th>
                                        <th>
                                            <input type="text" name="total_terima" class="form-control auto_num text-right" onchange="updateInvoiceTotals()" id="totalTerima">
                                        </th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

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

            <div class="form-group row">
                <div class="col-md-12 text-center">
                    <button type="submit" id="btnSave" class="btn btn-success"><i class="fa fa-save"></i> Save</button>
                    <a class="btn btn-default" onclick="window.history.back(); return false;">
                        <i class="fa fa-reply"></i> Batal
                    </a>
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
<div class="modal fade" id="ModalCNHistory" tabindex="-1" role="dialog" aria-hidden="true">
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
</div>
<!-- Modal OTP -->
<div class="modal fade" id="modal-otp" tabindex="-1" role="dialog" aria-labelledby="modalOtpLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="otp-form">
                <div class="modal-header">
                    <button type="button" class="close d-none" id="btn-close-otp" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title" id="modalOtpLabel">Verifikasi OTP</h4>
                </div>
                <div class="modal-body text-center">
                    <p id="otp-message">Kode OTP telah dikirim ke WhatsApp Anda.</p>

                    <div class="d-flex justify-content-center gap-2 otp-input-container mb-3">
                        <input type="text" class="otp-input" maxlength="1" />
                        <input type="text" class="otp-input" maxlength="1" />
                        <input type="text" class="otp-input" maxlength="1" />
                        <input type="text" class="otp-input" maxlength="1" />
                        <input type="text" class="otp-input" maxlength="1" />
                        <input type="text" class="otp-input" maxlength="1" />
                    </div>

                    <input type="hidden" name="otp_code" id="otp_code_combined">
                    <input type="hidden" name="kd_pembayaran" id="otp-kd-pembayaran">

                    <div id="countdown-text">Kirim ulang dalam <span id="otp-timer">180</span> detik</div>
                    <button type="button" id="resend-otp-btn" class="btn btn-link" style="display:none;">Kirim ulang OTP</button>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success btn-block">Verifikasi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/plugins/select2/select2.full.min.js') ?>"></script>
<script src="<?= base_url('assets/js/autoNumeric.js') ?>"></script>

<script>
    $(document).ready(function() {
        $('.auto_num').autoNumeric('init');

        let selectedIds = [];
        let selectedInvoices = [];
        let selectedInvoiceIds = [];
        let selectedCnIds = []; // array no_retur CN yang dipilih
        let selectedCnData = {}; // map no_retur => {id_invoice, nilai}

        $('.select2').select2({
            width: '100%'
        });

        $(document).on('input', 'input[name="total_bayar[]"]', function() {
            updateInvoiceTotals();
        });

        $(document).on('click', '.btn-remove', function() {
            const $row = $(this).closest('tr');
            const id_invoice = $row.find('input[name*="[id_invoice]"]').val();

            // Hapus dari array selected invoice
            selectedInvoiceIds = selectedInvoiceIds.filter(id => id !== id_invoice);

            // Hapus CN terkait dari state
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
                url: siteurl + 'penerimaan_cash/get_inv',
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
                    checkboxes.prop('disabled', false); //sementara biar aktip semua 

                    // Handler checkbox CN: update tampilan sisa tagihan invoice di modal
                    $('#tableModalInv').off('change', '.select-cn').on('change', '.select-cn', function() {
                        const id_invoice = $(this).data('invoice');
                        const $invRow = $('#tableModalInv tr.inv-row[data-invoice="' + id_invoice + '"]');
                        const $sisaCell = $invRow.find('.inv-sisa-tagihan');
                        const originalVal = parseFloat($sisaCell.data('original')) || 0;

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
                            $cnRows.find('.select-cn').prop('disabled', false);
                        } else {
                            $cnRows.find('.select-cn').prop('checked', false).prop('disabled', true);
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

            // Kumpulkan CN yang dipilih per invoice
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
                const nominal = sisaTagihan - totalCn;

                // Hidden inputs CN
                let cnHiddenInputs = '';
                cnDetails.forEach(function(cn, ci) {
                    cnHiddenInputs += `
                        <input type="hidden" name="detail[${rowIndex}][cn][${ci}][no_retur]" value="${cn.no_retur}">
                        <input type="hidden" name="detail[${rowIndex}][cn][${ci}][nilai]" value="${cn.nilai}">
                    `;
                });

                // Label CN
                let cnLabel = '';
                if (cnDetails.length > 0) {
                    const cnList = cnDetails.map(cn => `${cn.no_retur} (-${cn.nilai.toLocaleString('id-ID')})`).join(', ');
                    cnLabel = `<br><small class="text-orange"><i class="fa fa-minus-circle"></i> Credit Note: ${cnList}</small>`;
                }

                $('#tableInv tbody').append(`
                    <tr>
                        <td class="text-center">${rowIndex}</td>
                        <td>${inv.id_invoice}${cnLabel}</td>
                        <td>
                            <input type="text" name="detail[${rowIndex}][tagihan]" class="form-control text-right tagihan auto_num" value="${sisaTagihan}" readonly />
                        </td>
                        <td>
                            <input type="text" name="detail[${rowIndex}][sisa_invoice]" class="form-control text-right sisa_invoice auto_num" value="${nominal}" readonly/>
                        </td>
                        <td>
                            <input type="text" name="detail[${rowIndex}][total_bayar]" class="form-control text-right total_bayar auto_num" value="${nominal}" readonly/>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-danger btn-sm btn-remove"><i class="fa fa-trash"></i></button>
                        </td>
                        <input type="hidden" name="detail[${rowIndex}][id_invoice]" value="${inv.id_invoice}">
                        <input type="hidden" name="detail[${rowIndex}][id_so]" value="${inv.id_so}">
                        <input type="hidden" name="detail[${rowIndex}][total_cn]" value="${totalCn}">
                        ${cnHiddenInputs}
                    </tr>
                `);
            });
            $('.auto_num').autoNumeric('init');
            $('#ModalInv').modal('hide');
            updateInvoiceTotals();
        });

        // Proses simpan
        $(document).on('submit', '#data-form', function(e) {
            e.preventDefault();

            const totalTerima = parseFloat(($('#totalTerima').val() || '0').replace(/,/g, '')) || 0;
            if (totalTerima <= 0) {
                swal('Peringatan', 'Total penerimaan tidak boleh 0. Silahkan isi nominal pembayaran.', 'warning');
                return;
            }

            const form = document.getElementById('data-form');
            const formData = new FormData(form);

            swal({
                title: "Warning!",
                text: "Yakin lakukan pembayaran?",
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
                                // swal({
                                //     title: 'Berhasil!',
                                //     text: result.message,
                                //     type: 'success'
                                // }, function() {
                                //     // Redirect atau cetak struk langsung
                                //     window.location.href = siteurl + active_controller
                                //     // window.location.href = result.redirect_url;
                                // });

                                // Setelah OTP dikirim, tampilkan modal input
                                $('#modal-otp').modal('show');
                                startOtpTimer();
                                $('#otp-kd-pembayaran').val(result.kd_pembayaran);
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

        //Proses submit otp
        $(document).on('submit', '#otp-form', function(e) {
            e.preventDefault();
            $.ajax({
                url: siteurl + active_controller + 'verify_otp',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(result) {
                    if (result.status == 1) {
                        swal('Sukses', result.message, 'success');
                        $('#modal-otp').modal('hide');
                        window.open(result.redirect_url, '_blank');
                        setTimeout(() => {
                            window.location.href = siteurl + active_controller + 'index';
                        }, 1000);
                    } else {
                        swal('Gagal', result.message, 'error');
                    }
                }
            });
        });

        // Otomatis isi dan pindah
        $(document).on('input', '.otp-input', function() {
            const $inputs = $('.otp-input');
            const current = $inputs.index(this);
            if (this.value.length === 1 && current < $inputs.length - 1) {
                $inputs.eq(current + 1).focus();
            }

            let combined = '';
            $inputs.each(function() {
                combined += this.value;
            });
            $('#otp_code_combined').val(combined);
        });
        $(document).on('keydown', '.otp-input', function(e) {
            const $inputs = $('.otp-input');
            const current = $inputs.index(this);

            if (e.key === 'Backspace' && this.value === '' && current > 0) {
                $inputs.eq(current - 1).focus();
            }
        });

        // Resend OTP klik
        $('#resend-otp-btn').on('click', function() {
            const kd = $('#otp-kd-pembayaran').val();
            $.ajax({
                url: siteurl + active_controller + 'resend_otp',
                type: 'POST',
                data: {
                    kd_pembayaran: kd
                },
                success: function(res) {
                    otpSeconds = 180;
                    startOtpTimer();
                    swal('Berhasil', 'OTP baru telah dikirim ke WhatsApp', 'success');
                },
                error: function() {
                    swal('Gagal', 'Gagal mengirim ulang OTP', 'error');
                }
            });
        });

        // update total invoice ketika input total terima
        $('#totalTerima').on('input', function() {
            console.log('input terima');
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
                            nilaiAwalBaris = nilaiInvBaru;
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
    });

    let otpCountdown;
    let otpSeconds = 180;

    function startOtpTimer() {
        $('#otp-timer').text(otpSeconds);
        $('#resend-otp-btn').hide();
        $('#countdown-text').show();

        // Pastikan modal tidak bisa ditutup manual selama countdown
        $('#modal-otp').modal({
            backdrop: 'static',
            keyboard: false
        });

        otpCountdown = setInterval(() => {
            otpSeconds--;
            $('#otp-timer').text(otpSeconds);

            if (otpSeconds <= 0) {
                clearInterval(otpCountdown);

                // 1. Enable ESC key and backdrop to close
                $('#modal-otp').data('bs.modal').options.backdrop = true;
                $('#modal-otp').data('bs.modal').options.keyboard = true;

                // 2. Tampilkan tombol close (jika disembunyikan)
                $('#btn-close-otp').removeClass('d-none');

                $('#countdown-text').hide();
                $('#resend-otp-btn').show();
            }
        }, 1000);
    }

    function generateJurnal() {

        let jurnalHTML = '';
        let totalDebit = 0;
        let totalKredit = 0;

        const today = $('#tgl_pembayaran').val() || '';
        const customerName = $('#id_customer option:selected').text().trim();

        let totalBayar = parseFloat($('#totalTerima').val().replace(/,/g, '')) || 0;

        // =========================
        // PIUTANG + CN
        // =========================
        $('#tableInv tbody tr').each(function() {
            const $row = $(this);
            const invoiceRaw = $row.find('td:eq(1)').clone();
            invoiceRaw.find('small, br').remove();
            const invoice = invoiceRaw.text().trim();

            const bayar = parseFloat($row.find('.total_bayar').val().replace(/,/g, '')) || 0;
            const totalCn = parseFloat($row.find('input[name*="[total_cn]"]').val()) || 0;
            const totalKreditInv = bayar + totalCn;

            if (totalKreditInv > 0) {
                jurnalHTML += `
            <tr>
                <td><input type="date" name="tgl_jurnal[]" value="${today}" class="form-control" readonly></td>
                <td><input type="text" name="type[]" value="JV" class="form-control" readonly></td>
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
                <td><input type="text" name="type[]" value="JV" class="form-control" readonly></td>
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
        // PENERIMAAN (DEBIT)
        // =========================
        if (totalBayar > 0) {
            jurnalHTML += `
        <tr>
            <td><input type="date" name="tgl_jurnal[]" value="${today}" class="form-control" readonly></td>
            <td style="min-width: 10px;"><input type="text" name="type[]" value="JV" class="form-control" readonly></td>
            <td><input type="text" name="no_coa[]" value="1102-01-04" class="form-control" readonly></td>
            <td><input type="text" name="nama_coa[]" value="Piutang Sales" class="form-control" readonly></td>
            <td><textarea name="keterangan[]" class="form-control" readonly>Penerimaan uang via Sales</textarea></td>

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

    function updateInvoiceTotals() {
        let totalInvoice = 0;
        let sisaBayar = getNum($('#totalTerima').val().split(',').join(''));

        $('#tableInv tbody tr').each(function() {
            const $tr = $(this);

            const tagihan = getNum($tr.find('.tagihan').val().split(',').join(''));
            const totalCn = parseFloat($tr.find('input[name*="[total_cn]"]').val()) || 0;
            const tagihanEfektif = tagihan - totalCn; // tagihan setelah dikurangi CN

            totalInvoice += tagihanEfektif;

            let bayar = 0;
            if (sisaBayar >= tagihanEfektif) {
                bayar = tagihanEfektif;
                sisaBayar -= tagihanEfektif;
            } else if (sisaBayar > 0) {
                bayar = sisaBayar;
                sisaBayar = 0;
            }

            const sisa = Math.max(0, tagihanEfektif - bayar);

            $tr.find('.total_bayar').val(number_format(bayar, 2));
            $tr.find('.sisa_invoice').val(number_format(sisa, 2));
        });

        $('#totalInvoice').val(number_format(totalInvoice, 2));

        generateJurnal()
    }

    function number_format(number, decimals, dec_point, thousands_sep) {
        // Strip all characters but numerical ones.
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
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

    function getNum(val) {
        if (isNaN(val) || val == '') {
            return 0;
        }
        return parseFloat(val);
    }
</script>