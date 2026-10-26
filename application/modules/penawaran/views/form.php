<?php
$readonly = (isset($mode) && ($mode == 'approval_manager' || $mode == 'approval_direksi' || $mode == 'view') ? 'readonly' : '');
$disabled = (isset($mode) && ($mode == 'approval_manager' || $mode == 'approval_direksi' || $mode == 'view') ? 'disabled' : '');

?>

<?php if (isset($mode) && $mode == 'view'): ?>
    <style>
        .btn-danger[onclick^="DelProduct"],
        #add-product {
            display: none !important;
        }
    </style>
<?php endif; ?>

<div class="box box-primary">
    <div class="box-body">
        <form id="data-form" autocomplete="off">
            <input type="hidden" name="id_penawaran" id="id_penawaran" value="<?= isset($penawaran['id_penawaran']) ? $penawaran['id_penawaran'] : '' ?>">
            <div class="row">
                <div class="col-md-12">
                    <div class="col-sm-6">

                        <!-- No Penawaran -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="no_penawaran">No Penawaran</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="no_penawaran" id="no_penawaran"
                                    value="<?= isset($penawaran['id_penawaran']) ? $penawaran['id_penawaran'] : 'Automatic' ?>"
                                    placeholder="Automatic" readonly>
                            </div>
                        </div>

                        <!-- Quotation Date -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="quotation_date">Quotation Date <span class="text-red">*</span></label>
                            </div>
                            <div class="col-md-8">
                                <input type="date" class="form-control" name="quotation_date" id="quotation_date" max="<?= date('Y-m-d') ?>" <?= $readonly ?>
                                    value="<?= isset($penawaran['quotation_date']) ? date('Y-m-d', strtotime($penawaran['quotation_date'])) : '' ?>" required>
                            </div>
                        </div>

                        <!-- Dropship / Toko -->
                        <!-- <div class="form-group row">
                            <div class="col-md-4">
                                <label for="price_mode">Price Mode</label>
                            </div>
                            <div class="col-md-8">
                                <select name="price_mode" id="price_mode" class="form-control select2" <?= $disabled ?>>
                                    <option value="" selected>-- Pilih --</option>
                                    <option value="toko" <?= (isset($penawaran['price_mode']) && $penawaran['price_mode'] == 'toko') ? 'selected' : '' ?>>Toko</option>
                                    <option value="dropship" <?= (isset($penawaran['price_mode']) && $penawaran['price_mode'] == 'dropship') ? 'selected' : '' ?>>Dropship</option>
                                </select>
                                <?php if ($mode == 'approval_manager' || $mode == 'approval_direksi'): ?>
                                    <input type="hidden" name="price_mode" value="<?= isset($penawaran['price_mode']) ? $penawaran['price_mode'] : '' ?>">
                                <?php endif; ?>
                            </div>
                        </div> -->

                        <!-- Customer -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="id_customer">Customer <span class="text-red">*</span></label>
                            </div>
                            <div class="col-md-8">
                                <select name="id_customer" id="id_customer" class="form-control select2" <?= $disabled ?>>
                                    <option value="">-- Pilih ---</option>
                                    <?php foreach ($customers as $ctm): ?>
                                        <option
                                            value="<?= $ctm['id_customer']; ?>"
                                            data-sales="<?= $ctm['id_karyawan'] ?>"
                                            data-email="<?= $ctm['email'] ?>"
                                            data-toko="<?= $ctm['kategori_toko']; ?>"
                                            data-terms="<?= $ctm['payment_term'] ?>"
                                            <?= isset($penawaran['id_customer']) && $penawaran['id_customer'] == $ctm['id_customer'] ? 'selected' : '' ?>>
                                            <?= $ctm['name_customer']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Tipe Bayar -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="tipe_bayar">Tipe Bayar</label>
                            </div>
                            <div class="col-md-8">
                                <select name="tipe_bayar" id="tipe_bayar" class="form-control select2" <?= $disabled ?>>
                                    <option value="">-- Pilih --</option>
                                    <option value="cash" <?= isset($penawaran['tipe_bayar']) && $penawaran['tipe_bayar'] == 'cash' ? 'selected' : '' ?>>Cash</option>
                                    <option value="tempo" <?= isset($penawaran['tipe_bayar']) && $penawaran['tipe_bayar'] == 'tempo' ? 'selected' : '' ?>>Tempo</option>
                                </select>
                            </div>
                        </div>

                    </div>
                    <div class="col-sm-6">

                        <!-- Email -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="email">Email</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="email" id="email" <?= $readonly ?>
                                    value="<?= isset($penawaran['email']) ? $penawaran['email'] : '' ?>">
                            </div>
                        </div>

                        <!-- Sales -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="sales">Sales</label>
                            </div>
                            <div class="col-md-8">
                                <input type="hidden" name="id_karyawan" id="id_karyawan">
                                <input type="text" class="form-control" name="sales" id="sales" <?= $readonly ?>
                                    value="<?= isset($penawaran['sales']) ? $penawaran['sales'] : '' ?>">
                            </div>
                        </div>

                        <!-- Term of Payment -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="payment_term">Term Of Payment</label>
                            </div>
                            <div class="col-md-8">
                                <select id="payment_term" name="payment_term" class="form-control select2" required <?= $disabled ?>>
                                    <option value="">-- Pilih --</option>
                                    <?php foreach ($payment_terms as $term): ?>
                                        <option value="<?= htmlspecialchars($term['id']) ?>"
                                            <?= isset($penawaran['payment_term']) && $penawaran['payment_term'] == $term['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($term['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Freight -->
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="freight">Freight Cost</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control moneyFormat" name="freight" id="freight" <?= $readonly ?>
                                    value="<?= isset($penawaran['freight']) ? $penawaran['freight'] : '' ?>">
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <hr>
            <div class="form-group row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class='table table-bordered table-striped'>
                            <thead>
                                <tr class="bg-blue">
                                    <th class="text-center" style="min-width: 200px;">Nama Produk</th>
                                    <th class="text-center" style="min-width: 100px;" class="text-nowrap">Qty</th>
                                    <th class="text-center" style="min-width: 100px;" class="text-nowrap">Free Stok</th>
                                    <th class="text-center" style="min-width: 150px;" class="text-nowrap">Price List</th>
                                    <th class="text-center" style="min-width: 150px;" class="text-nowrap">Harga Penawaran</th>
                                    <th class="text-center" style="min-width: 100px;" class="text-nowrap">% Discount</th>
                                    <th class="text-center" style="min-width: 160px;">Total Harga Penawaran</th>
                                    <th class="text-center" style="width: 50px;">
                                        <?php if (!isset($mode) || $mode != 'view') :
                                            echo form_button(array('type' => 'button', 'class' => 'btn btn-sm btn-success', 'value' => 'back', 'content' => 'Add', 'id' => 'add-product'));
                                        endif; ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="list_product">
                                <?php
                                $loop = 0;
                                if (!empty($penawaran_detail)) {
                                    // UNTUK AMBIL FREE STOK DARI WAREHOUSE
                                    // 1. Ambil semua id_product dari penawaran_detail
                                    $id_products = array_column($penawaran_detail, 'id_product');
                                    $id_products = array_filter(array_unique($id_products)); // hapus duplikat dan kosong

                                    // 2. Ambil qty_free dari warehouse_stock berdasarkan code_lv4
                                    $stock_by_product = [];

                                    if (!empty($id_products)) {
                                        $this->db->where_in('code_lv4', $id_products);
                                        $stocks = $this->db->select('code_lv4, qty_free')->get('warehouse_stock')->result_array();

                                        foreach ($stocks as $stock) {
                                            $stock_by_product[$stock['code_lv4']] = $stock['qty_free'];
                                        }
                                    }

                                    foreach ($penawaran_detail as $dp) {
                                        $loop++;
                                        $qty_free = isset($stock_by_product[$dp['id_product']]) ? $stock_by_product[$dp['id_product']] : 0;
                                ?>
                                        <tr id="tr_<?= $loop ?>">
                                            <td>
                                                <select name="product[<?= $loop ?>][id_product]" class="form-control product-select select2" data-loop="<?= $loop ?>" <?= $disabled ?>>
                                                    <option value="">-- Pilih Produk --</option>
                                                    <?php foreach ($products as $item): ?>
                                                        <option value="<?= $item['code_lv4'] ?>"
                                                            data-price="<?= $item['propose_price'] ?>"
                                                            data-harga-beli="<?= $item['harga_beli'] ?>"
                                                            data-code="<?= $item['code_lv4'] ?>"
                                                            data-product="<?= $item['product_name'] ?>"
                                                            data-dropship-price="<?= $item['dropship_price'] ?>"
                                                            data-dropship-tempo="<?= $item['dropship_tempo'] ?>"
                                                            <?= $item['code_lv4'] == $dp['id_product'] ? 'selected' : '' ?>>
                                                            <?= $item['product_name'] ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </td>
                                            <td hidden>
                                                <?php if ($mode == 'approval_manager'): ?>
                                                    <input type="hidden" name="product[<?= $loop ?>][id_product]" value="<?= $dp['id_product'] ?>">
                                                <?php endif; ?>
                                                <input type="hidden" name="product[<?= $loop ?>][product_name]" id="product_name_<?= $loop ?>" value="<?= $dp['product_name'] ?>">
                                                <input type="hidden" name="product[<?= $loop ?>][harga_beli]" id="harga_beli_<?= $loop ?>" value="<?= $dp['harga_beli'] ?>">
                                            </td>
                                            <td><input type="number" class="form-control qty-input text-center" name="product[<?= $loop ?>][qty]" id="qty_<?= $loop ?>" value="<?= $dp['qty'] ?>" <?= $readonly ?>></td>
                                            <td><input type="text" class="form-control text-center" name="product[<?= $loop ?>][qty_free]" id="qty_free_<?= $loop ?>" value="<?= $qty_free ?>" readonly></td>
                                            <td><input type="text" class="form-control moneyFormat price-list" name="product[<?= $loop ?>][price_list]" id="price_<?= $loop ?>" value="<?= $dp['price_list'] ?>" readonly></td>
                                            <td>
                                                <input type="text" class="form-control penawaran moneyFormat" name="product[<?= $loop ?>][harga_penawaran]" id="penawaran_<?= $loop ?>" value="<?= $dp['harga_penawaran'] ?>"
                                                    <?= ($mode == 'edit') ? '' : 'readonly' ?>>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control diskon" name="product[<?= $loop ?>][diskon]" id="diskon_<?= $loop ?>" value="<?= $dp['diskon'] ?>" readonly>
                                                <input type="hidden" class="form-control diskon_nilai" name="product[<?= $loop ?>][diskon_nilai]" id="diskon_nilai_<?= $loop ?>" value="<?= $dp['diskon_nilai'] ?>" readonly>
                                            </td>
                                            <td><input type="text" class="form-control moneyFormat total-harga" name="product[<?= $loop ?>][total]" id="total_<?= $loop ?>" value="<?= $dp['total'] ?>" readonly></td>
                                            <td hidden><input type="text" class="form-control moneyFormat total-price-list" name="product[<?= $loop ?>][total_pl]" id="total_pl_<?= $loop ?>" value="<?= $dp['total_pl'] ?>" readonly></td>
                                            <td align="center">
                                                <button type="button" class="btn btn-sm btn-danger" onclick="DelProduct(<?= $loop ?>)"><i class="fa fa-trash-o"></i></button>
                                            </td>
                                        </tr>
                                    <?php
                                    }
                                } else {
                                    // default 1 baris kosong
                                    $loop = 1;
                                    ?>
                                    <tr id="tr_1">
                                        <td>
                                            <select name="product[1][id_product]" class="form-control product-select select2" data-loop="1">
                                                <option value="">-- Pilih Produk --</option>
                                                <?php foreach ($products as $item): ?>
                                                    <option value="<?= $item['code_lv4'] ?>" data-price="<?= $item['propose_price'] ?>"
                                                        data-harga-beli="<?= $item['harga_beli'] ?>"
                                                        data-code="<?= $item['code_lv4'] ?>"
                                                        data-product="<?= $item['product_name'] ?>"
                                                        data-dropship-price="<?= $item['dropship_price'] ?>"
                                                        data-dropship-tempo="<?= $item['dropship_tempo'] ?>">
                                                        <?= $item['product_name'] ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td hidden>
                                            <input type="hidden" name="product[1][product_name]" id="product_name_1">
                                            <input type="hidden" name="product[1][harga_beli]" id="harga_beli_1">
                                        </td>
                                        <td><input type="number" class="form-control qty-input" name="product[1][qty]" id="qty_1"></td>
                                        <td><input type="text" class="form-control" name="product[1][qty_free]" id="qty_free_1" readonly></td>
                                        <td><input type="text" class="form-control moneyFormat price-list" name="product[1][price_list]" id="price_1" readonly></td>
                                        <td><input type="text" class="form-control penawaran moneyFormat" name="product[1][harga_penawaran]" id="penawaran_1"></td>
                                        <td>
                                            <input type="text" class="form-control diskon" name="product[1][diskon]" id="diskon_1" readonly>
                                            <input type="hidden" class="form-control diskon_nilai" name="product[1][diskon_nilai]" id="diskon_nilai_1" readonly>
                                        </td>
                                        <td><input type="text" class="form-control moneyFormat total-harga" name="product[1][total]" id="total_1" readonly></td>
                                        <td hidden><input type="text" class="form-control moneyFormat total-price-list" name="product[1][total_pl]" id="total_pl_1" readonly></td>
                                        <td align="center">
                                            <button type="button" class="btn btn-sm btn-danger" onclick="DelProduct(1)"><i class="fa fa-trash-o"></i></button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>Total Harga Penawaran</strong></td>
                                    <td colspan="2"><input type="text" class="form-control moneyFormat" name="total_penawaran" id="total_penawaran" value="<?= isset($penawaran['total_penawaran']) ? $penawaran['total_penawaran'] : '' ?>" readonly></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>Total Harga Price List</strong></td>
                                    <td colspan="2"><input type="text" class="form-control moneyFormat" name="total_price_list" id="total_price_list" value="<?= isset($penawaran['total_price_list']) ? $penawaran['total_price_list'] : '' ?>" readonly></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>Discount Khusus</strong></td>
                                    <td colspan="2"><input type="text" class="form-control moneyFormat" name="diskon_khusus" id="diskon_khusus" value="<?= isset($penawaran['diskon_khusus']) ? $penawaran['diskon_khusus'] : '' ?>"></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>Total % Discount</strong></td>
                                    <td colspan="2"><input type="text" class="form-control" name="total_diskon_persen" id="total_diskon_persen" value="<?= isset($penawaran['total_diskon_persen']) ? $penawaran['total_diskon_persen'] : '' ?>" readonly></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>Total Harga + Freight</strong></td>
                                    <td colspan="2"><input type="text" class="form-control moneyFormat" name="total_harga_freight" id="total_harga_freight" value="<?= isset($penawaran['total_harga_freight']) ? $penawaran['total_harga_freight'] : '' ?>" readonly></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>Total Harga + Freight (Exclude PPN)</strong></td>
                                    <td colspan="2"><input type="text" class="form-control moneyFormat" name="total_harga_freight_exppn" id="total_harga_freight_exppn" value="<?= isset($penawaran['total_harga_freight_exppn']) ? $penawaran['total_harga_freight_exppn'] : '' ?>" readonly></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>DPP</strong></td>
                                    <td colspan="2"><input type="text" class="form-control moneyFormat" name="dpp" id="dpp" value="<?= isset($penawaran['dpp']) ? $penawaran['dpp'] : '' ?>" readonly></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>PPn</strong></td>
                                    <td colspan="2"><input type="text" class="form-control moneyFormat" name="ppn" id="ppn" value="<?= isset($penawaran['ppn']) ? $penawaran['ppn'] : '' ?>" readonly></td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-right"><strong>Grand Total</strong></td>
                                    <td colspan="2"><input type="text" class="form-control moneyFormat" name="grand_total" id="grand_total" value="<?= isset($penawaran['grand_total']) ? $penawaran['grand_total'] : '' ?>" readonly></td>
                                </tr>
                                <?php if ($mode == 'approval_manager' || $mode == 'edit' && $penawaran['level_approval'] == 'D' && $penawaran['reject_reason'] != null): ?>
                                    <tr>
                                        <td colspan="8">Revisi : <span class="text-red"><?= isset($penawaran['reject_reason']) ? $penawaran['reject_reason'] : '' ?></span></td>
                                    </tr>
                                <?php endif; ?>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- BUAT STATUS OVERLIMIT -->
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-6">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="">Due Date Credit</label>
                            </div>
                            <div class="col-md-8">
                                <input type="date" class="form-control" name="due_date_credit" id="due_date_credit"
                                    value="<?= isset($penawaran['due_date_credit']) ? date('Y-m-d', strtotime($penawaran['due_date_credit'])) : '' ?>" <?= (isset($mode) && $mode == 'approval_manager' || $mode == 'approval_direksi') ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="">Credit Limit</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control moneyFormat" name="credit_limit" id="credit_limit"
                                    value="<?= isset($penawaran['credit_limit']) ? $penawaran['credit_limit'] : '' ?>" <?= (isset($mode) && $mode == 'approval_manager' || $mode == 'approval_direksi') ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="">Outstanding Piutang</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control moneyFormat" name="outstanding_piutang" id="outstanding_piutang"
                                    <?= (isset($mode) && $mode == 'approval_manager' || $mode == 'approval_direksi') ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="">SO Baru</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control moneyFormat" name="so_baru" id="so_baru"
                                    <?= (isset($mode) && $mode == 'approval_manager' || $mode == 'approval_direksi') ? 'readonly' : '' ?>>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="">Total Penawaran</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control moneyFormat" name="total_so" id="total_so"
                                    value="<?= isset($penawaran['grand_total']) ? $penawaran['grand_total'] : '' ?>" readonly>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="">Total Outstanding</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control moneyFormat" name="outstanding" id="outstanding"
                                    value="<?= isset($penawaran['outstanding']) ? $penawaran['outstanding'] : '' ?>" <?= (isset($mode) && $mode == 'approval_manager' || $mode == 'approval_direksi') ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="">Over Limit</label>
                            </div>
                            <div class="col-md-8">
                                <input type="text" class="form-control moneyFormat" name="over_limit" id="over_limit"
                                    value="<?= isset($penawaran['over_limit']) ? $penawaran['over_limit'] : '' ?>" <?= (isset($mode) && $mode == 'approval_manager' || $mode == 'approval_direksi') ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-4">
                                <label for="">Status Credit Limit</label>
                            </div>
                            <div class="col-md-8">
                                <label id="status_credit_limit" class="form-control <?= (isset($penawaran['status_credit_limit']) && $penawaran['status_credit_limit'] == 'Overlimit') ? "text-red" : "text-green" ?>" style="border: none; padding-top: 7px;"><?= isset($penawaran['status_credit_limit']) ? $penawaran['status_credit_limit'] : '' ?></label>
                                <input type="hidden" name="status_credit_limit">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- INFORMASI JATUH TEMPO -->
            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-6">
                        <label>Informasi Jatuh Tempo</label>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No Invoice</th>
                                        <th>Total Invoice</th>
                                        <th>Piutang</th>
                                        <th>Jatuh Tempo</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_jatuh_tempo">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label>Histori Pembayaran</label>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>No Invoice</th>
                                        <th>Duedate</th>
                                        <th>Tanggal Pelunasan</th>
                                        <th>Histori GAP (days)</th>
                                    </tr>
                                </thead>
                                <tbody id="tbody_histori">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group row">
                <div class="col-md-12 text-center">
                    <?php if ($mode == 'add') : ?>
                        <button type="submit" class="btn btn-primary" name="save" id="save">
                            <i class="fa fa-save"></i> Save
                        </button>
                    <?php elseif ($mode == 'edit'): ?>
                        <?php if (isset($penawaran['status']) && $penawaran['status'] != 'R') : ?>
                            <a href="javascript:void(0)" data-id="<?= isset($penawaran['id_penawaran']) ? $penawaran['id_penawaran'] : '' ?>" class="btn btn-info btn-request"><i class="fa fa-check"></i> Request Approval</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary" name="save" id="save">
                            <i class="fa fa-save"></i> Save
                        </button>
                    <?php elseif ($mode == 'approval_manager' || $mode == 'approval_direksi'): ?>
                        <button type="submit" class="btn btn-success" name="approve" id="approve" data-role="<?= $mode ?>">
                            <i class="fa fa-check"></i> Approve
                        </button>
                        <a class="btn btn-danger reject" name="reject" id="reject" onclick="Reject()"><i class="fa fa-ban"></i> Reject</a>
                    <?php endif; ?>
                    <a class="btn btn-default" onclick="window.history.back(); return false;">
                        <i class="fa fa-reply"></i> Kembali
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="reject-modal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form-reject">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title" id="rejectModalLabel">Alasan Penolakan</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reject_id">
                    <div class="form-group">
                        <label for="reason">Alasan:</label>
                        <textarea id="reason" name="reason" class="form-control" rows="4" required placeholder="Masukkan alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Tolak</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/plugins/jquery-inputmask/jquery.inputmask.js') ?>"></script>
<script src="<?= base_url('assets/plugins/select2/select2.full.min.js') ?>"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });
        moneyFormat('.moneyFormat')

        // TAMBAH LIST PRODUCT
        let products = <?= json_encode($products) ?>; // kirim dari PHP
        let loop = $('#list_product tr').length; // inisialisasi dari jumlah baris awal
        $('#add-product').click(function() {
            loop++;

            let options = '<option value="">-- Pilih Produk --</option>';
            products.forEach(item => {
                options += `<option value="${item.code_lv4}" data-harga-beli="${item.harga_beli}" data-price="${item.propose_price}" data-product="${item.product_name}" data-dropship-price="${item.dropship_price}" data-code="${item.code_lv4}">${item.product_name}</option>`;
            });

            let row = `
                <tr id="tr_${loop}">
                    <td>
                        <select name="product[${loop}][id_product]" class="form-control product-select select2" data-loop="${loop}">
                            ${options}
                        </select>
                    </td>
                    <td hidden><input type="hidden" name="product[${loop}][product_name]" id="product_name_${loop}"></td>
                    <td hidden><input type="hidden" name="product[${loop}][harga_beli]" id="harga_beli_${loop}"></td>
                    <td><input type="number" class="form-control qty-input" name="product[${loop}][qty]" id="qty_${loop}"></td>
                    <td><input type="text" class="form-control" name="product[${loop}][qty_free]" id="qty_free_${loop}" readonly></td>
                    <td><input type="text" class="form-control moneyFormat price-list" name="product[${loop}][price_list]" id="price_${loop}" readonly></td>
                    <td><input type="text" class="form-control penawaran moneyFormat" name="product[${loop}][harga_penawaran]" id="penawaran_${loop}"></td>
                    <td>
                        <input type="text" class="form-control diskon" name="product[${loop}][diskon]" id="diskon_${loop}" readonly>
                        <input type="hidden" class="form-control diskon_nilai" name="product[${loop}][diskon_nilai]" id="diskon_nilai_${loop}" readonly>
                    </td>
                    <td><input type="text" class="form-control moneyFormat total-harga" name="product[${loop}][total]" id="total_${loop}" readonly></td>
                    <td hidden><input type="text" class="form-control moneyFormat total-price-list" name="product[${loop}][total_pl]" id="total_pl_${loop}" readonly></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger" onclick="DelProduct(${loop})"><i class="fa fa-trash-o"></i></button>
                    </td>
                </tr>`;
            $('#list_product').append(row);
            $(`#tr_${loop} .select2`).select2({
                width: '100%'
            });
            moneyFormat('.moneyFormat')
        });

        // saat produk dipilih ambil harga dan stok 
        $(document).on('change', '.product-select', function() {
            const loop = $(this).data('loop');
            const selected = $(this).find(':selected');
            const price = selected.data('price') || 0;
            const stock = selected.data('stock') || 0;
            const product = selected.data('product');
            const code = selected.data('code');
            const harga_beli = selected.data('harga-beli');

            $(`#price_${loop}`).val(price);
            $(`#stok_${loop}`).val(stock);
            $(`#product_name_${loop}`).val(product);
            $(`#harga_beli_${loop}`).val(harga_beli);

            hitungTotal(loop);

            if (code) {
                $.ajax({
                    url: '<?= base_url('penawaran/get_free_stok') ?>',
                    type: 'POST',
                    data: {
                        code_lv4: code
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.error) {
                            alert(res.message);
                        } else {
                            $(`#qty_free_${loop}`).val(res.qty_free); // ✅ Set qty_free
                        }
                    },
                    error: function() {
                        alert('Gagal mengambil free stock.');
                    }
                });
            } else {
                $(`#qty_free_${loop}`).val(''); // kosongkan jika tidak ada code
            }
        });

        // Trigger hitung diskon, total, dan seluruh total
        $(document).on('input', '.penawaran, .qty-input', function() {
            const loop = $(this).closest('tr').attr('id').split('_')[1];
            hitungTotal(loop);
            hitungAllTotal();
        });

        $(document).on('input', '#diskon_khusus, #freight', hitungAllTotal);

        // Trigger untuk mengambil data dari select customer
        $('#id_customer').change(function() {
            const $selected = $(this).find(':selected');
            const idCustomer = $(this).val();
            const idKaryawan = $selected.data('sales');
            const email = $selected.data('email');
            const kategoriToko = $selected.data('toko');
            const selectedPaymentTerm = $selected.data('terms');

            // Jika Tipe Bayar masih CASH, Term Of Payment tetap "Cash Before Delivery",
            // jangan ditimpa payment_term default customer.
            if ($('#tipe_bayar').val() === 'cash') {
                const cbdValue = getCashBeforeDeliveryValue();
                $('#payment_term').val(cbdValue !== '' ? cbdValue : selectedPaymentTerm).trigger('change');
            } else {
                $('#payment_term').val(selectedPaymentTerm).trigger('change');
            }

            // Update harga berdasarkan kategori toko
            $('.product-select').each(function() {
                const loopIndex = $(this).data('loop');
                if (kategoriToko && kategoriToko.toLowerCase().includes('dropship')) {
                    setDropshipPrice(loopIndex);
                } else {
                    hitungHarga(loopIndex);
                }
            });

            // Ambil nama sales via AJAX
            if (idKaryawan) {
                $.ajax({
                    url: '<?= base_url('penawaran/get_nama_sales') ?>',
                    type: 'POST',
                    data: {
                        id_karyawan: idKaryawan
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.error) {
                            $('#sales').val('');
                            alert(res.message);
                        } else {
                            $('#sales').val(res.nama_sales);
                            $('#id_karyawan').val(idKaryawan);
                            $('#email').val(email);
                        }
                    },
                    error: function() {
                        alert('Gagal mengambil nama sales.');
                    }
                });
            } else {
                $('#sales').val('');
                $('#id_karyawan').val('');
                $('#email').val('');
            }

            // --- AMBIL CREDIT LIMIT BERDASARKAN id_customer ---
            if (idCustomer) {
                $.ajax({
                    url: '<?= base_url('penawaran/get_info_kredit_limit') ?>',
                    type: 'POST',
                    data: {
                        id_customer: idCustomer
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status != 1) {
                            $('#outstanding_piutang,#so_baru,#total_kredit,#kredit_limit').val('');
                            alert(res.msg || 'Gagal mengambil info kredit limit');
                            return;
                        }

                        const d = res.data;

                        // tampilkan format rupiah
                        $('#outstanding_piutang').val((d.outstanding_piutang));
                        $('#so_baru').val((d.so_baru));
                        $('#credit_limit').val((d.kredit_limit)).trigger('input');
                        $('#outstanding').val((d.total)).trigger('input')

                        // Optional: warning kalau total > limit
                        if (parseFloat(d.total) > parseFloat(d.kredit_limit)) {
                            // contoh notif
                            // swal("Warning", "Total melebihi kredit limit!", "warning");
                            console.warn('Total melebihi kredit limit');
                        }
                    },
                    error: function() {
                        alert('Gagal mengambil info kredit limit.');
                    }
                });

                $.ajax({
                    url: '<?= base_url('penawaran/get_jatuh_tempo') ?>',
                    type: 'POST',
                    data: {
                        id_customer: idCustomer
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status != 1) {
                            $('#tbody_jatuh_tempo').html(`<tr><td colspan="4" class="text-center text-danger">${res.msg || 'Gagal mengambil info jatuh tempo'}</td></tr>`);
                            return;
                        }

                        const rows = res.data || []; // array
                        if (!rows.length) {
                            $('#tbody_jatuh_tempo').html(`<tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>`);
                            return;
                        }
                        const fmtIDR = new Intl.NumberFormat('id-ID', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        });

                        let html = '';
                        rows.forEach(r => {
                            const grandTotal = fmtIDR.format(toNumber(r.grand_total));
                            const piutang = fmtIDR.format(toNumber(r.piutang));
                            html += `
                                <tr>
                                <td>${r.id_invoice || ''}</td>
                                <td class='text-right'>${grandTotal}</td>
                                <td class='text-right'>${piutang}</td>
                                <td class='text-center'>${r.jatuh_tempo || ''}</td>
                                </tr>
                            `;
                        });

                        $('#tbody_jatuh_tempo').html(html);
                    },
                    error: function() {
                        alert('Gagal mengambil info jatuh tempo.');
                    }
                });


                $.ajax({
                    url: '<?= base_url('penawaran/get_histori_pembayaran') ?>',
                    type: 'POST',
                    data: {
                        id_customer: idCustomer
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status != 1) {
                            $('#tbody_histori').html(`<tr><td colspan="4" class="text-center text-danger">${res.msg || 'Gagal mengambil histori pembayaran'}</td></tr>`);
                            return;
                        }

                        const rows = res.data || []; // array
                        if (!rows.length) {
                            $('#tbody_histori').html(`<tr><td colspan="4" class="text-center text-muted">Tidak ada histori.</td></tr>`);
                            return;
                        }

                        let html = '';
                        rows.forEach(r => {
                            html += `
                                    <tr>
                                    <td>${r.no_invoice || ''}</td>
                                    <td class='text-center'>${r.due_date || ''}</td>
                                    <td class='text-center'>${r.tanggal_pelunasan || ''}</td>
                                    <td class='text-center'>${r.gap_days ?? ''}</td>
                                    </tr>
                                `;
                        });

                        $('#tbody_histori').html(html);
                    },
                    error: function() {
                        alert('Gagal mengambil histori pembayaran.');
                    }
                });

            } else {
                $('#credit_limit').val('');
            }


        });

        // Trigger penetuan credit limit
        $('#credit_limit, #grand_total, #outstanding').on('input', function() {
            updateCreditStatus();
        });

        // Atur Term Of Payment otomatis mengikuti Tipe Bayar
        // - CASH  : paksa ke "Cash Before Delivery"
        // - Tempo : kembalikan ke payment_term default customer (data-terms)
        $('#tipe_bayar').change(function() {
            const tipeBayar = $(this).val();

            if (tipeBayar === 'cash') {
                // Paksa Term Of Payment ke "Cash Before Delivery"
                const cbdValue = getCashBeforeDeliveryValue();
                if (cbdValue !== '') {
                    $('#payment_term').val(cbdValue).trigger('change');
                }
            } else if (tipeBayar === 'tempo') {
                // Kembalikan ke payment_term default customer yang dipilih
                const defaultTerm = $('#id_customer').find(':selected').data('terms');
                $('#payment_term').val(defaultTerm ? defaultTerm : '').trigger('change');
            }
        });

        // Re-cek harga produk saat customer berubah atau tipe bayar berubah
        $('#id_customer, #tipe_bayar').change(function() {
            const kategoriToko = $('#id_customer').find(':selected').data('toko');

            $('.product-select').each(function() {
                const loopIndex = $(this).data('loop');
                if (kategoriToko && kategoriToko.toLowerCase().includes('dropship')) {
                    setDropshipPrice(loopIndex);
                } else {
                    hitungHarga(loopIndex);
                }
            });
        });

        // Jika produk diganti, hitung ulang harganya sesuai kategori toko customer
        $(document).on('change', '.product-select', function() {
            const loopIndex = $(this).data('loop');
            const kategoriToko = $('#id_customer').find(':selected').data('toko');

            if (kategoriToko && kategoriToko.toLowerCase().includes('dropship')) {
                setDropshipPrice(loopIndex);
            } else {
                hitungHarga(loopIndex);
            }
        });

        // SAVE PENAWARAN
        $('#data-form').submit(function(e) {
            e.preventDefault();

            var isEmpty = false;
            $('.product-select').each(function() {
                if ($(this).val() === "") {
                    isEmpty = true;
                    return false; // Keluar dari each loop
                }
            });

            // Jika ada yang kosong, tampilkan notifikasi error
            if (isEmpty) {
                swal({
                    type: 'warning',
                    title: 'Warning !',
                    text: 'Please make sure all product has been selected !',
                    timer: 3000,
                    allowOutsideClick: false,
                    showConfirmButton: false
                });

                return false;
            }

            const requiredFields = [{
                    id: '#id_customer',
                    message: 'Customer empty, select first ...'
                },
                {
                    id: '#quotation_date',
                    message: 'Date empty, complete it first ...'
                }
            ];

            for (let field of requiredFields) {
                if ($(field.id).val() === '') {
                    swal({
                        title: "Error Message!",
                        text: field.message,
                        type: "warning"
                    });

                    $('#save').prop('disabled', false);
                    return false;
                }
            }

            swal({
                    title: "Are you sure?",
                    text: "You will not be able to process again this data!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes, Process it!",
                    cancelButtonText: "No, cancel process!",
                    closeOnConfirm: true,
                    closeOnCancel: false
                },
                function(isConfirm) {
                    if (isConfirm) {
                        var formData = new FormData($('#data-form')[0]);
                        var baseurl = base_url + active_controller + '/save'
                        $.ajax({
                            url: baseurl,
                            type: "POST",
                            data: formData,
                            cache: false,
                            dataType: 'json',
                            processData: false,
                            contentType: false,
                            success: function(data) {
                                if (data.status == 1) {
                                    swal({
                                        title: "Save Success!",
                                        text: data.pesan,
                                        type: "success",
                                        timer: 3000,
                                        showCancelButton: false,
                                        showConfirmButton: false,
                                        allowOutsideClick: false
                                    });
                                    window.location.href = base_url + active_controller;
                                } else {

                                    if (data.status == 2) {
                                        swal({
                                            title: "Save Failed!",
                                            text: data.pesan,
                                            type: "warning",
                                            timer: 3000,
                                            showCancelButton: false,
                                            showConfirmButton: false,
                                            allowOutsideClick: false
                                        });
                                    } else {
                                        swal({
                                            title: "Save Failed!",
                                            text: data.pesan,
                                            type: "warning",
                                            timer: 3000,
                                            showCancelButton: false,
                                            showConfirmButton: false,
                                            allowOutsideClick: false
                                        });
                                    }

                                }
                            },
                            error: function() {
                                swal({
                                    title: "Error Message !",
                                    text: 'An Error Occured During Process. Please try again..',
                                    type: "warning",
                                    timer: 7000,
                                    showCancelButton: false,
                                    showConfirmButton: false,
                                    allowOutsideClick: false
                                });
                            }
                        });
                    } else {
                        swal("Cancelled", "Data can be process again :)", "error");
                        return false;
                    }
                });
        });

        // APPROVE DIGABUNG
        $('#approve').click(function(e) {
            e.preventDefault();

            var role = $(this).data('role');
            var actionUrl = base_url + active_controller + 'save_' + (role === 'approval_direksi' ? 'approval_direksi' : 'approval_manager');
            console.log(actionUrl)
            swal({
                    title: "Are you sure?",
                    text: "You will not be able to process again this data!",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonClass: "btn-danger",
                    confirmButtonText: "Yes, Process it!",
                    cancelButtonText: "No, cancel process!",
                    closeOnConfirm: true,
                    closeOnCancel: false
                },
                function(isConfirm) {
                    if (isConfirm) {
                        var formData = new FormData($('#data-form')[0]);
                        formData.append('id_penawaran', $('#id_penawaran').val());
                        $.ajax({
                            url: actionUrl,
                            type: "POST",
                            data: formData,
                            cache: false,
                            dataType: 'json',
                            processData: false,
                            contentType: false,
                            success: function(data) {
                                if (data.status == 1) {
                                    swal({
                                        title: "Save Success!",
                                        text: data.pesan,
                                        type: "success",
                                        timer: 3000,
                                        showCancelButton: false,
                                        showConfirmButton: false,
                                        allowOutsideClick: false
                                    });
                                    window.location.href = base_url + active_controller + (role === 'approval_direksi' ? 'approval_direksi' : 'approval_manager');
                                } else {

                                    if (data.status == 2) {
                                        swal({
                                            title: "Save Failed!",
                                            text: data.pesan,
                                            type: "warning",
                                            timer: 3000,
                                            showCancelButton: false,
                                            showConfirmButton: false,
                                            allowOutsideClick: false
                                        });
                                    } else {
                                        swal({
                                            title: "Save Failed!",
                                            text: data.pesan,
                                            type: "warning",
                                            timer: 3000,
                                            showCancelButton: false,
                                            showConfirmButton: false,
                                            allowOutsideClick: false
                                        });
                                    }

                                }
                            },
                            error: function() {
                                swal({
                                    title: "Error Message !",
                                    text: 'An Error Occured During Process. Please try again..',
                                    type: "warning",
                                    timer: 7000,
                                    showCancelButton: false,
                                    showConfirmButton: false,
                                    allowOutsideClick: false
                                });
                            }
                        });
                    } else {
                        swal("Cancelled", "Data can be process again :)", "error");
                        return false;
                    }
                });
        });

        // Saat form submit
        $('#form-reject').submit(function(e) {
            e.preventDefault();

            const id = $('#reject_id').val();
            const reason = $('#reason').val().trim();

            if (reason === '') {
                alert('Alasan penolakan harus diisi.');
                return;
            }

            // Konfirmasi kedua pakai SweetAlert
            swal({
                title: "Konfirmasi Penolakan",
                text: "Yakin ingin menolak data dengan alasan berikut?\n\n" + reason,
                type: "warning",
                showCancelButton: true,
                confirmButtonText: "Ya, Tolak",
                cancelButtonText: "Batal",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (!isConfirm) return;

                // Sembunyikan modal input
                $('#reject-modal').modal('hide');

                // Kirim AJAX ke backend
                $.ajax({
                    url: base_url + active_controller + 'reject/' + id,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        reason
                    },
                    success: function(res) {
                        if (res.save == 1) {
                            swal({
                                title: "Ditolak!",
                                text: "Data berhasil ditolak.",
                                type: "success",
                                timer: 1500,
                                showConfirmButton: false
                            });
                            setTimeout(() => {
                                window.location.href = base_url + active_controller;
                            }, 1500);
                        } else {
                            swal("Gagal", res.message || "Penolakan gagal disimpan.", "error");
                        }
                    },
                    error: function() {
                        swal("Error", "Terjadi kesalahan saat memproses data.", "error");
                    }
                });
            });
        });

        // REQUEST APPROVAL
        $(document).on('click', '.btn-request', function(e) {
            e.preventDefault();

            var id_penawaran = $(this).data('id');
            console.log(id_penawaran)

            swal({
                title: "Are you sure?",
                text: "Request Approval Penawaran!",
                type: "warning",
                showCancelButton: true,
                confirmButtonClass: "btn-danger",
                confirmButtonText: "Yes, Request Approval!",
                cancelButtonText: "Cancel",
                closeOnConfirm: false
            }, function(isConfirm) {
                if (isConfirm) {
                    $.ajax({
                        url: base_url + 'penawaran/request_approval',
                        type: 'POST',
                        data: {
                            id_penawaran: id_penawaran,
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 1) {
                                swal("Success!", response.pesan, "success");
                                window.location.href = base_url + active_controller;
                            } else {
                                swal("Failed", response.pesan, "warning");
                            }
                        },
                        error: function() {
                            swal("Error", "Something went wrong.", "error");
                        }
                    });
                }
            });
        });
    });

    //fungsi reject
    function Reject() {
        const id = $('#id_penawaran').val(); // ambil id dari input hidden
        $('#reject_id').val(id); // simpan ke form modal
        $('#reason').val(''); // reset alasan
        $('#reject-modal').modal('show'); // tampilkan modal
    }

    //fungsi hapus baris
    function DelProduct(id) {
        $('#list_product #tr_' + id).remove();
        hitungAllTotal();
    }

    //fungsi hitung seluruh total 
    function hitungAllTotal() {
        let totalPenawaran = 0;
        let totalPriceList = 0;

        $('.total-harga').each(function() {
            const val = parseFloat($(this).val().replaceAll(',', '')) || 0;
            totalPenawaran += val;
        });

        $('.total-price-list').each(function() {
            const val = parseFloat($(this).val().replaceAll(',', '')) || 0;
            totalPriceList += val
        });

        // --- diskon khusus (nominal) ---
        let diskonKhusus = parseFloat($('#diskon_khusus').val().replaceAll(',', '')) || 0;
        if (diskonKhusus > totalPenawaran) diskonKhusus = totalPenawaran; // cegah minus

        const totalPenawaranAfterDisc = totalPenawaran - diskonKhusus

        const totalDiskon = ((totalPenawaran - totalPriceList) / totalPriceList) * 100;

        const freight = parseFloat($('#freight').val().replaceAll(',', '')) || 0;

        const totalHargaFreight = totalPenawaranAfterDisc + freight;
        const excludePPn = totalHargaFreight / 1.11;

        const dpp = (excludePPn * 11) / 12;
        const ppn = (12 * dpp) / 100;
        const grand_total = excludePPn + ppn;

        $('#total_penawaran').val(totalPenawaran);
        $('#total_price_list').val(totalPriceList);
        $('#total_diskon_persen').val(totalDiskon.toFixed(2));
        $('#total_harga_freight').val(totalHargaFreight);
        $('#total_harga_freight_exppn').val(excludePPn);
        $('#dpp').val(dpp);
        $('#ppn').val(ppn);
        $('#grand_total').val(grand_total).trigger('input');
        $('#total_so').val(grand_total);
    }

    //fungsi hitung total perbaris
    function hitungTotal(loop) {
        const qty = parseFloat($(`#qty_${loop}`).val()) || 0;
        const price = parseFloat($(`#price_${loop}`).val().replace(/,/g, '')) || 0;
        const offer = parseFloat($(`#penawaran_${loop}`).val().replace(/,/g, '')) || 0;

        const diskon = offer ? ((offer - price) / price) * 100 : 0;
        const diskon_nilai = (price * diskon) / 100;
        const total = qty * offer;
        const total_pl = qty * price;

        $(`#diskon_${loop}`).val(diskon.toFixed(2));
        $(`#total_${loop}`).val(total);
        $(`#total_pl_${loop}`).val(total_pl);
        $(`#diskon_nilai_${loop}`).val(diskon_nilai.toFixed(2));
    }

    //fungsi hitung harga berantai berdasarkan toko
    function hitungHarga(loopIndex) {
        const productSelect = $(`.product-select[data-loop="${loopIndex}"]`);
        const idProduct = productSelect.val();

        const idCustomer = $('#id_customer').val();
        const kategoriToko = $('#id_customer option:selected').data('toko');
        const tipeBayar = $('#tipe_bayar').val();

        if (idProduct && kategoriToko && tipeBayar) {
            $.ajax({
                url: base_url + 'penawaran/pilih_harga_ajax',
                type: 'POST',
                data: {
                    id_product: idProduct,
                    kategori_toko: kategoriToko,
                    tipe_bayar: tipeBayar
                },
                dataType: 'json',
                success: function(res) {
                    if (res.error) {
                        Swal.fire('Gagal', res.message, 'warning');
                    } else {
                        $(`#price_${loopIndex}`).val(res.harga);
                    }
                },
                error: function() {
                    Swal.fire('Gagal', 'Terjadi kesalahan saat mengambil data harga.', 'error');
                }
            });
        }
    }

    // SET HARGA UNTUK DROPSHIP
    function setDropshipPrice(loopIndex) {
        const tipeBayar = $('#tipe_bayar').val();
        const productSelect = $(`.product-select[data-loop="${loopIndex}"]`);
        const dropshipPrice = parseFloat(productSelect.find('option:selected').data('dropship-price')) || 0;
        const dropshipTempo = parseFloat(productSelect.find('option:selected').data('dropship-tempo')) || 0;

        if (tipeBayar === 'cash') {
            $(`#price_${loopIndex}`).val(dropshipPrice);
        } else if (tipeBayar === 'tempo') {
            $(`#price_${loopIndex}`).val(dropshipTempo);
        } else {
            $(`#price_${loopIndex}`).val(''); // fallback jika tidak ada yang sesuai
        }
    }

    function updateCreditStatus() {
        const creditLimit = toNumber($('#credit_limit').val());
        const grandTotal = toNumber($('#grand_total').val());
        const so_baru = toNumber($('#so_baru').val());
        const outstanding_piutang = toNumber($('#outstanding_piutang').val());

        const selisih = (grandTotal + so_baru + outstanding_piutang) - creditLimit

        $('#outstanding').val(grandTotal + so_baru);

        let status = "";

        if (creditLimit === 0) {
            status = "Tidak Overlimit";
            $('#status_credit_limit').text(status).removeClass('text-red').addClass('text-green');
            $('#over_limit').val("0");
        } else if (selisih > 0) {
            status = "Overlimit";
            $('#status_credit_limit').text(status).removeClass('text-green').addClass('text-red');
            $('#over_limit').val(selisih.toFixed(2));
        } else {
            status = "Tidak Overlimit";
            $('#status_credit_limit').text(status).removeClass('text-red').addClass('text-green');
            $('#over_limit').val("0");
        }

        // ✅ Simpan ke input hidden agar ikut terkirim
        $('input[name="status_credit_limit"]').val(status);
    }

    function moneyFormat(e) {
        $(e).inputmask({
            alias: "decimal",
            digits: 2,
            radixPoint: ".",
            autoGroup: true,
            placeholder: "0",
            rightAlign: false,
            allowMinus: false,
            integerDigits: 13,
            groupSeparator: ",",
            digitsOptional: false,
            showMaskOnHover: true,
        })
    }

    function toNumber(val) {
        return parseFloat((val || "0").replace(/[^0-9.-]+/g, '')) || 0;
    }

    // Cari value option Term Of Payment yang teksnya "Cash Before Delivery"
    // (tidak hardcode id, agar tetap benar walau id di DB berbeda)
    function getCashBeforeDeliveryValue() {
        let cbdValue = '';
        $('#payment_term option').each(function() {
            if ($(this).text().trim().toLowerCase() === 'cash before delivery') {
                cbdValue = $(this).val();
                return false;
            }
        });
        return cbdValue;
    }
</script>

<!-- Trash  -->

<!-- // Triger untuk merubah price mode
        $('#price_mode').change(function() {
             $('.product-select').each(function() {
                 const loopIndex = $(this).data('loop');
                 const mode = $('#price_mode').val();

                 if (mode === 'dropship') {
                     setDropshipPrice(loopIndex);
                 } else {
                     hitungHarga(loopIndex);
                 }
             });
         }); -->