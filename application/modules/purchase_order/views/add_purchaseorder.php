<?php
$ENABLE_ADD     = has_permission('Purchase_Request.Add');
$ENABLE_MANAGE  = has_permission('Purchase_Request.Manage');
$ENABLE_VIEW    = has_permission('Purchase_Request.View');
$ENABLE_DELETE  = has_permission('Purchase_Request.Delete');
?>
<style>
	/* Container toggle */
	.switch {
		position: relative;
		display: inline-block;
		width: 50px;
		height: 24px;
	}

	/* Sembunyikan checkbox asli */
	.switch input {
		opacity: 0;
		width: 0;
		height: 0;
	}

	/* Slider */
	.slider {
		position: absolute;
		cursor: pointer;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: #ccc;
		transition: .4s;
	}

	.slider:before {
		position: absolute;
		content: "";
		height: 16px;
		width: 16px;
		left: 4px;
		bottom: 4px;
		background-color: white;
		transition: .4s;
	}

	input:checked+.slider {
		background-color: #2196F3;
	}

	input:checked+.slider:before {
		transform: translateX(26px);
	}

	/* Rounded sliders */
	.slider.round {
		border-radius: 34px;
	}

	.slider.round:before {
		border-radius: 50%;
	}
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.min.css" integrity="sha512-yVvxUQV0QESBt1SyZbNJMAwyKvFTLMyXSyBHDO4BG5t7k/Lw34tyqlSDlKIrIENIzCl+RVUNjmCPG+V/GMesRw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<div class="box box-primary">
	<div class="box-body">
		<form id="data-form" method="post">
			<input type="hidden" name="so_number" value="<?= implode(',', $results['param']) ?>">
			<div class="col-sm-12">
				<div class="input_fields_wrap2">
					<div class="row">
						<center><label for="customer">
								<h3>Purchase Order</h3>
							</label></center>
						<div class="col-sm-12">
							<!-- <div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="id_customer">Supplier</label>
									</div>
									<div class="col-md-8">
										<select id="id_suplier" name="id_suplier" class='form-control input-md chosen-select' onchange="get_lokasi()" required>
											<option value="">--Pilih--</option>
											<?php foreach ($results['supplier'] as $supplier) { ?>
												<option value="<?= $supplier->id_suplier ?>"><?= strtoupper(strtolower($supplier->name_suplier)) ?></option>
											<?php } ?>
										</select>
									</div>
								</div>
							</div> -->
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="id_customer">Local / Import</label>
									</div>
									<div class="col-md-8" id="ubahloi">
										<select id="loi" name="loi" class="form-control select" onchange="get_kurs()" required>
											<option value="">--Pilih--</option>
											<option value="Import">Import</option>
											<option value="Lokal">Lokal</option>
										</select>
									</div>
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="id_customer">Department</label>
									</div>
									<div class="col-md-8" id="ubahloi">
										<select id="select_department" name="dept[]" class="form-control " multiple required>
											<?php
											foreach ($results['list_department'] as $item) {
												echo '<option value="' . $item->id . '">' . strtoupper($item->nama) . '</option>';
											}
											?>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-12">
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="customer">NO.PO</label>
									</div>
									<div class="col-md-8">
										<input type="text" class="form-control" id="no_po" required name="no_po" readonly placeholder="ID PO">
										<input type="hidden" class="form-control" id="no_surat" required name="no_surat" readonly placeholder="No.PO">
									</div>
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="customer">Delivery Date</label>
									</div>
									<div class="col-md-8">
										<input type="text" name="delivery_date" id="" class="form-control datepicker delivery_date">
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-12">
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="customer">Tanggal PO</label>
									</div>
									<div class="col-md-8">
										<input type="text" class="form-control" id="tanggal" value="" onkeyup required name="tanggal">
									</div>
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="id_customer">Currency</label>
									</div>
									<div class="col-md-8">
										<select id="select_curr" name="matauang" class="form-control" required>
											<option value="">- Currency -</option>
											<?php foreach ($results['mata_uang'] as $mata_uang) {
												$selected = '';
											?>
												<option value="<?= $mata_uang->kode ?>" <?= $selected; ?>><?= strtoupper(strtolower($mata_uang->kode)) ?></option>
											<?php } ?>
										</select>
									</div>
								</div>
							</div>
						</div>
						<!-- <div class="col-sm-12">
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="customer">Expect Date</label>
									</div>
									<div class="col-md-8">
										<input type="date" class="form-control" id="expect_tanggal" required name="expect_tanggal"  >
									</div>
								</div>
							</div>
						</div> -->
						<div class="col-sm-12">
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="customer">Payment Term</label>
									</div>
									<div class="col-md-8">
										<select id="term" name="term" class="form-control select" required>
											<option value="">-- Pilih --</option>
											<?php foreach ($results['term'] as $term): ?>
												<option value="<?= htmlspecialchars($term->id) ?>">
													<?= htmlspecialchars($term->name) ?>
												</option>
											<?php endforeach; ?>
										</select>
										<!-- <input type="text" class="form-control" id="term" onkeyup required name="term"> -->
									</div>
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="id_customer">Supplier</label>
									</div>
									<div class="col-md-8">
										<select id="supplier" name="supplier" class="form-control" required>
											<option value="">- Supplier -</option>
											<?php foreach ($results['list_supplier'] as $supplier) {
												$selected = '';
											?>
												<option value="<?= $supplier->kode_supplier ?>" <?= $selected; ?>><?= strtoupper(strtolower($supplier->nama)) ?></option>
											<?php } ?>
										</select>
									</div>
								</div>
							</div>
							<div class="col-sm-6" hidden>
								<div class="form-group row">
									<div class="col-md-4">
										<label for="id_customer">PR</label>
									</div>
									<div class="col-md-8">
										<select id="no_pr" name="no_pr" class='form-control input-md chosen-select' required>
											<option value="0">List Empty</option>
										</select>
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-12" hidden>
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="id_customer">Price Method</label>
									</div>
									<div class="col-md-8">
										<select id="cif" name="cif" class="form-control select" required>
											<option value="">--Pilih--</option>
											<option value="CIF">CIF</option>
											<option value="FOB">FOB</option>
											<option value="LOCO">LOCO</option>
											<option value="DDU">DDU</option>
											<option value="FRANCO">FRANCO</option>
										</select>
									</div>
								</div>
							</div>
						</div>

						<div class="col-md-12">
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="id_customer">Keterangan</label>
									</div>
									<div class="col-md-8">
										<textarea name="keterangan" id="" class="form-control"></textarea>
									</div>
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group row">
									<div class="col-md-4">
										<label for="id_customer">Alamat</label>
									</div>
									<div class="col-md-8">
										<textarea name="delivery_address" id="" class="form-control"></textarea>
									</div>
								</div>
							</div>
						</div>

						<input type="hidden" class="count_all_prod" value="<?= count($results['getitemso']) ?>">
					</div>

					<div class="col-sm-12" style="margin-bottom: 10px;">
						<!-- <div class="text-right">
							<label class="switch">
								<input type="checkbox" id="toggle_pajak">
								<span class="slider round"></span>
							</label>
							<label><b>Gunakan Pajak (PPn/DPP)</b></label>
						</div> -->
						<div class="table-responsive">
							<table class='table table-bordered table-striped'>
								<thead>
									<tr class='bg-blue'>
										<th width='5%' class="text-center">
											<input type="checkbox" id="select_all" checked>
										</th>
										<th style="min-width: 200px;">Item</th>
										<th style="min-width: 150px;">Kode Produk</th>
										<th style="min-width: 100px;" hidden>Width</th>
										<th style="min-width: 100px;" hidden>Length</th>
										<th style="min-width: 75px;">Qty PR</th>
										<th style="min-width: 75px;">PO Qty</th>
										<th style="min-width: 100px;">Unit Measurement</th>
										<th style="min-width: 75px;">Unit Packing</th>
										<th style="min-width: 100px;" hidden>Rate LME</th>
										<th style="min-width: 100px;" hidden>Alloy Price</th>
										<th style="min-width: 100px;" hidden>Fab Cost</th>
										<th style="min-width: 150px;">Harga Satuan (Include PPn)</th>
										<th style="min-width: 150px;">Harga Satuan</th>
										<th style="min-width: 100px;" hidden>Disc %</th>
										<th style="min-width: 100px;" hidden>Biaya Kirim</th>
										<th style="min-width: 150px;">Total Harga</th>
										<th style="min-width: 150px;">Nilai Discount</th>
										<!-- <th style="min-width: 100px;">Nilai PPN</th> -->
										<th style="min-width: 150px;">Sub Total</th>
										<th style="min-width: 100px;">Note</th>
									</tr>
								</thead>
								<tbody>
									<?php
									// if ($results['getitemso']) {
									$n = 1;
									// print_r($results['getitemso']);
									// exit;
									$key = 0;

									// Gabungkan tampilan barang produksi yang sama dari beberapa PR jadi 1 baris.
									// Penyimpanan tetap per-PR (dipecah lagi di SaveNew lewat idpr_list).
									$rows = [];
									$grouped = [];
									foreach ($results['getitemso'] as $r) {
										$sudah_po = $this->db->query("SELECT IF(SUM(a.qty) IS NOT NULL, SUM(a.qty), 0) AS qty_all_po FROM dt_trans_po a WHERE a.idpr = '" . $r->id . "'")->row()->qty_all_po;
										$sisa = $r->propose_purchase - $sudah_po;

										if ($r->tipe_pr === '' && !empty($r->id_material)) {
											if (!isset($grouped[$r->id_material])) {
												$r->qty_sisa = 0;
												$r->pr_list = [];
												$grouped[$r->id_material] = $r;
												$rows[] = $r;
											}
											$grouped[$r->id_material]->qty_sisa += $sisa;
											$grouped[$r->id_material]->pr_list[] = ['id' => $r->id, 'sisa' => $sisa, 'so' => $r->so_number];
										} else {
											$r->qty_sisa = $sisa;
											$r->pr_list = [];
											$rows[] = $r;
										}
									}

									foreach ($rows as $value) {

										$idpr_list = '';
										$info_pr = '';
										if (count($value->pr_list) > 1) {
											$idpr_items = [];
											$so_items = [];
											foreach ($value->pr_list as $pl) {
												$idpr_items[] = (int) $pl['id'] . ':' . (float) $pl['sisa'];
												$so_items[] = $pl['so'];
											}
											$idpr_list = implode(',', $idpr_items);
											$info_pr = "<br><small class='text-muted'>" . htmlspecialchars(implode(', ', $so_items)) . "</small>";
										}

										// echo '<tr><td>' . $value->nm_material . '</td></tr>';

										$no = $n++;
										$key++;
										// $disabled = ($loi == 'Import') ? '' : 'readonly';
										// $disabled2 = ($loi == 'Import') ? 'readonly' : '';
										// $idmat = $value->idmaterial;
										// $harga 	= $this->db->query("SELECT * FROM ms_product_pricelist WHERE id_category3 = '$idmat'")->row();

										// $stock = $this->db->query("SELECT * FROM stock_material WHERE id_category3 = '$idmat'")->row();

										// $avl 	 =	$stock->qty_free;
										$disabled = '';
										$po    = (!empty($value->qty)) ? $value->qty : 0;

										$harga_beli = (!empty($harga->harga_beli)) ? $harga->harga_beli : 0;

										$total = $harga_beli * $po;


										// if ($value->status_app !== 'Y') {

										// $get_po = $this->db->get_where('dt_trans_po', ['idpr' => $value->id])->row();
										// // if ($get_po->qty == null || $get_po->qty > $value->propose_purchase) {
										// $status = "<div class='badge bg-green'>Done PO</div>";
										// if ($get_po->qty == null || $get_po->qty > $value->propose_purchase) {
										// 	$status = "<div class='badge bg-red'>Outstanding PO</div>";
										// }
										$nm_material1 = (!empty($value->nm_material1)) ? $value->nm_material1 : '';

										$no_pr = (!empty($value->no_pr)) ? $value->no_pr : '';
										$id_material = (!empty($value->id_material)) ? $value->id_material : '';
										$width = (!empty($value->width)) ? $value->width : 0;
										$length = (!empty($value->length)) ? $value->length : 0;
										$total_weight = (!empty($value->totalweight)) ? $value->totalweight : 0;

										echo "
												<tr>
													<td class='text-center'>
														<input type='checkbox' name='dt[" . $key . "][checked_point]' class='checked_point' data-no='" . $key . "' value='" . $key . "'  checked>
													</td>
													
													<td>  
													" . $value->nm_material . $nm_material1 . $info_pr . "
														<input type='hidden' class='form-control input-sm' id='dt_idpr_" . $key . "' name='dt[" . $key . "][idpr]' value='" . $value->id . "'>
														<input type='hidden' id='dt_idpr_list_" . $key . "' name='dt[" . $key . "][idpr_list]' value='" . $idpr_list . "'>
														<input type='hidden' id='dt_tipe_pr_" . $key . "' name='dt[" . $key . "][tipe_pr]' value='" . $value->tipe_pr . "'>
														<input type='hidden' class='form-control input-sm' id='dt_no_pr_" . $key . "' name='dt[" . $key . "][no_pr]' value='" . $no_pr . "'>
														<input type='hidden' class='form-control input-sm' id='dt_idmaterial_" . $key . "' name='dt[" . $key . "][idmaterial]' value='" . $id_material . "'>
														<input type='hidden' class='form-control input-sm' id='dt_namamaterial_" . $key . "' name='dt[" . $key . "][namamaterial]' value='" . $value->nm_material . $nm_material1 . "'>
														<input type='hidden' class='form-control input-sm' id='dt_panjang_" . $key . "' name='dt[" . $key . "][panjang]'>
														<input type='hidden' class='form-control input-sm' id='dt_lebar_" . $key . "' name='dt[" . $key . "][lebar]'>
														<input type='hidden' class='form-control input-sm ch_diskon' id='dt_ch_diskon_" . $key . "'>
														<input type='hidden' class='form-control input-sm ch_pajak' id='dt_ch_pajak_" . $key . "'>
														<input type='hidden' class='form-control input-sm ch_jumlah' id='dt_ch_jumlah_" . $key . "'>
														<input type='hidden' class='form-control input-sm ch_ppn' id='dt_ch_ppn_" . $key . "'>
													</td>

													<td hidden>
														<select class='form-control input-sm' id='dt_ppn_" . $key . "' name='dt[" . $key . "][ppn]' onchange='CariPPN(" . $key . ")'>
															<option value=''>SELECT</option>
															<option value='Y'>Y</option>
															<option value='N'>N</option>
														</select>
													</td>
												  
												  	<td><input type='text' class='form-control input-sm' name='dt[" . $key . "][kode_barang]' id='dt_kode_barang_" . $key . "' value='" . $value->code . $value->code1 . "' readonly></td>
												  	<td><input type='text' class='form-control input-sm text-center' id='dt_pr_" . $key . "' name='dt[" . $key . "][pr]' value='" . $value->qty_sisa . "' readonly ></td>
														
													<td hidden><input type='text' class='form-control input-sm' name='dt[" . $key . "][description]' id='dt_description_" . $key . "' value=''></td>
												  	<td hidden><input type='hidden' class='form-control input-sm autoNumeric' name='dt[" . $key . "][width]' id='dt_width_" . $key . "'  value='" . $width . "'></td>
													<td hidden><input type='hidden' class='form-control input-sm autoNumeric' name='dt[" . $key . "][length]' id='dt_length_" . $key . "'  value='" . $length . "'></td>
													
													<td>
														<input type='hidden' class='form-control input-sm autoNumeric' name='dt[" . $key . "][totalweight]' id='dt_totalweight_" . $key . "' value='" . $total_weight . "'  onkeyup='HitAmmount(" . $key . ")'>
														<input type='text' class='form-control input-sm text-center' id='dt_qty_" . $key . "' name='dt[" . $key . "][qty]' value='" . $value->qty_sisa . "' onkeyup='HitAmmount(" . $key . ")'>
													</td>
													<td class='text-center'>" . ucfirst($value->unit_measure) . "</td>
													<td class='text-center'>" . ucfirst($value->packing_unit) . ucfirst($value->packing_unit2) . "</td>
													
													<td hidden>
														<select class='form-control input-sm' id='dt_ratelme_" . $key . "' name='dt[" . $key . "][ratelme]' onchange='CariPrice(" . $key . ")'>
															<option value=''>-Pilih-</option>
															<option value='Hari Ini'>Hari ini</option>
															<option value='H-10'>H-10</option>
															<option value='H-30'>H-30</option>
														</select>
													</td>
													<td hidden><input type='text' class='form-control input-sm autoNumeric3' id='dt_alloyprice_" . $key . "' " . $disabled . " data-decimal='.' data-thousand='' data-precision='0' data-allow-zero='' name='dt[" . $key . "][alloyprice]' onkeyup='HitAmmount(" . $key . ")'></td>
													<td hidden><input type='text' class='form-control input-sm autoNumeric3' id='dt_fabcost_" . $key . "' " . $disabled . " name='dt[" . $key . "][fabcost]' onkeyup='HitAmmount(" . $key . ")'></td>
													
													<td><input type='text' class='form-control input-sm text-right auto_num' id='dt_hargasatuan_include_" . $key . "' name='dt[" . $key . "][hargasatuan_include]' onkeyup='HitAmmount(" . $key . ", \"include\")' value=''></td>
													<td><input type='text' class='form-control input-sm text-right auto_num' id='dt_hargasatuan_" . $key . "' name='dt[" . $key . "][hargasatuan]' onkeyup='HitAmmount(" . $key . ", \"exclude\")' value=''></td>
													
													<td hidden><input type='text' class='form-control input-sm autoNumeric pajak' id='dt_pajak_" . $key . "' name='dt[" . $key . "][pajak]' onkeyup='HitAmmount(" . $key . ")'></td>
													<td hidden><input type='text' class='form-control input-sm autoNumeric3' id='dt_diskon_" . $key . "' " . $disabled . " name='dt[" . $key . "][diskon]' onkeyup='HitAmmount(" . $key . ")'></td>
												
												  	<td><input type='text' class='form-control input-sm text-right ch_jumlah_ex' id='dt_jumlahharga_" . $key . "' readonly name='dt[" . $key . "][jumlahharga]' value='" . $total . "'></td>
													<td>
														<div class='input-group input-group-sm' style='margin-bottom:6px;'>
															<input type='text' name='dt[" . $key . "][disc_persen]' class='form-control input-sm auto_num disc_persen'
																id='disc_persen_" . $key . "' data-key='" . $key . "' placeholder='Persen Disc (%)' onkeyup='HitAmmount(" . $key . ", \"disc_persen\")'>
															<span class='input-group-addon'>%</span>
														</div>
														<div class='input-group input-group-sm'>
															<span class='input-group-addon'>Rp</span>
															<input type='text' name='dt[" . $key . "][disc_num]' class='form-control input-sm auto_num disc_num'
																id='disc_num_" . $key . "' data-key='" . $key . "' placeholder='Nilai Disc (Rp)' onkeyup='HitAmmount(" . $key . ", \"disc_num\")'>
														</div>
													</td>

													<td hidden>
														<input type='text' class='form-control auto_num input-sm ch_ppn cng_nilai_ppn' id='dt_nilai_ppn_" . $key . "' name='dt[" . $key . "][nilai_ppn]' data-key='" . $key . "' placeholder='Nilai PPN' readonly>
														<input type='text' class='form-control input-sm ch_per_ppn cng_persen_ppn' id='dt_persen_ppn_" . $key . "' name='dt[" . $key . "][persen_ppn]' data-key='" . $key . "' placeholder='Persen PPN' readonly>
													</td>
													
													<td><input type='text' class='form-control input-sm text-right ch_jumlah_ex2' id='dt_totalharga_" . $key . "' readonly name='dt[" . $key . "][totalharga]' value='" . $total . "'></td>
													<td><input type='text' class='form-control input-sm' id='dt_note_" . $key . "' name='dt[" . $key . "][note]'></td>
											 	</tr>
													";
									}
									?>
								</tbody>
								<tfoot>
									<tr>
										<td class="text-right" colspan="10"><b>Total</b></th>
										<td colspan="2">
											<input readonly type="text" class="form-control text-right" id="subtotal" onkeyup required name="subtotal">
										</td>
									</tr>
									<tr hidden>
										<td class="text-right" colspan="10"><b>Diskon Khusus</b></th>
										<td colspan="2">
											<input type="text" class="form-control text-right auto_num" id="diskonkhusus" onblur="updateGlobal()" name="diskonkhusus">
											<input type="hidden" id="diskontotal" name="diskontotal">
										</td>
									</tr>
									<tr>
										<td class="text-right" colspan="10"><b>Total (Exclude PPn)</b></td>
										<td colspan="2">
											<input readonly type="text" class="form-control text-right" id="totalexppn" name="totalexppn">
											<input type="hidden" id="totalinppn" name="totalinppn">
										</td>
									</tr>
									<tr>
										<td class="text-right" colspan="10"><b>DPP</b></td>
										<td colspan="2"><input readonly type="text" class="form-control text-right" id="dpp" name="dpp"></td>
									</tr>
									<tr>
										<td class="text-right" colspan="10"><b>PPn</b></td>
										<td colspan="2"><input readonly type="text" class="form-control text-right" id="ppn" name="ppn"></td>
									</tr>
									<tr hidden>
										<td class="text-right" colspan="10"><b>Biaya Kirim</b></td>
										<td colspan="2">
											<input type="hidden" class="form-control" id="taxtotal" onkeyup required name="taxtotal">
											<input type="text" class="form-control auto_num text-right" id="kirim" onblur="cariTotal()" required name="kirim">
										</td>
									</tr>
									<tr>
										<td class="text-right" colspan="10"><b>Total Order</b></td>
										<td colspan="2">
											<input readonly type="text" class="form-control text-right" id="grand_total" onkeyup required name="grand_total">
										</td>
									</tr>
								</tfoot>
							</table>
						</div>
					</div>

					<div class="col-sm-12">
						<div class="row">
							<div class="col-sm-12">
								<input type="hidden" name="num_top" class="num_top">
								<button type="button" class="btn btn-sm btn-primary add_top">
									<i class="fa fa-plus"></i> Add TOP
								</button>
								<table class="table table-bordered">
									<thead class="bg-blue">
										<tr>
											<th class="text-center">Group TOP</th>
											<th class="text-center">Progress (%)</th>
											<th class="text-center">Value</th>
											<th class="text-center">Keterangan</th>
											<th class="text-center">Action</th>
										</tr>
									</thead>
									<tbody class="list_tbody_top">

									</tbody>
								</table>
							</div>
						</div>
					</div>
					<center>
						<button type="submit" class="btn btn-success btn-sm" name="save" id="simpan-com"><i class="fa fa-save"></i> Simpan</button>
					</center>
				</div>
			</div>
		</form>
	</div>
</div>


<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.7/chosen.jquery.min.js" integrity="sha512-rMGGF4wg1R73ehtnxXBt5mbUfN9JUJwbk21KMlnLZDJh7BkPmeovBuddZCENJddHYYMkCh9hPFnPmS9sspki8g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script type="text/javascript">
	//$('#input-kendaraan').hide();
	var base_url = '<?php echo base_url(); ?>';
	var active_controller = '<?php echo ($this->uri->segment(1)); ?>';
	var num_top = getNum($('.num_top').val());
	$(document).ready(function() {
		// TotalSemua()

		$('.chosen-select').chosen({
			width: "100%"
		});

		$('.auto_num').autoNumeric('init');

		var max_fields2 = 10; //maximum input boxes allowed
		var wrapper2 = $(".input_fields_wrap2"); //Fields wrapper
		var add_button2 = $(".add_field_button2"); //Add button ID	

		$('.datepicker').datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
		});

		$(document).on('click', '.checked_point', function() {
			var no = $(this).data('no');

			if ($(this).prop('checked') === true) {
				$('input[name="dt[' + no + '][description]"]').prop('readonly', false);
				$('input[name="dt[' + no + '][description]"]').val('');

				$('input[name="dt[' + no + '][qty]"]').prop('readonly', false);
				$('input[name="dt[' + no + '][qty]"]').val('');

				$('input[name="dt[' + no + '][hargasatuan]"]').prop('readonly', false);
				$('input[name="dt[' + no + '][hargasatuan]"]').val('');

				$('input[name="dt[' + no + '][ppn]"]').prop('disabled', false);
				$('input[name="dt[' + no + '][ppn]"]').val('');

				$('input[name="dt[' + no + '][jumlahharga]"]').val('0');

				$('input[name="dt[' + no + '][disc_persen]"]').prop('readonly', false);
				$('input[name="dt[' + no + '][disc_persen]"]').val('0');

				$('input[name="dt[' + no + '][disc_num]"]').prop('readonly', false);
				$('input[name="dt[' + no + '][disc_num]"]').val('0');

				$('input[name="dt[' + no + '][nilai_ppn]"]').val('0');

				$('input[name="dt[' + no + '][persen_ppn]"]').val('0');

				$('input[name="dt[' + no + '][totalharga]"]').val('0');

				$('input[name="dt[' + no + '][note]"]').prop('readonly', false);
				$('input[name="dt[' + no + '][note]"]').val('');
			} else {
				$('input[name="dt[' + no + '][description]"]').prop('readonly', true);
				$('input[name="dt[' + no + '][description]"]').val('');

				$('input[name="dt[' + no + '][qty]"]').prop('readonly', true);
				$('input[name="dt[' + no + '][qty]"]').val('');

				$('input[name="dt[' + no + '][hargasatuan]"]').prop('readonly', true);
				$('input[name="dt[' + no + '][hargasatuan]"]').val('');

				$('input[name="dt[' + no + '][ppn]"]').prop('disabled', true);
				$('input[name="dt[' + no + '][ppn]"]').val('');

				$('input[name="dt[' + no + '][jumlahharga]"]').val('0');

				$('input[name="dt[' + no + '][disc_persen]"]').prop('readonly', true);
				$('input[name="dt[' + no + '][disc_persen]"]').val('0');

				$('input[name="dt[' + no + '][disc_num]"]').prop('readonly', true);
				$('input[name="dt[' + no + '][disc_num]"]').val('0');

				$('input[name="dt[' + no + '][nilai_ppn]"]').val('0');

				$('input[name="dt[' + no + '][persen_ppn]"]').val('0');

				$('input[name="dt[' + no + '][totalharga]"]').val('0');

				$('input[name="dt[' + no + '][note]"]').prop('readonly', true);
				$('input[name="dt[' + no + '][note]"]').val('');
			}

			// TotalSemua()
		});

		$('#select_all').change(function() {
			var count_all_prod = $('.count_all_prod').val();
			// If the checkbox being changed is checked
			if ($(this).prop('checked')) {
				// Set all checkboxes to checked
				$('input[type="checkbox"]').prop('checked', true);
				for (var i = 1; i <= count_all_prod; i++) {
					$('input[name="dt[' + i + '][description]"]').prop('readonly', false);
					$('input[name="dt[' + i + '][description]"]').val('');

					$('input[name="dt[' + i + '][qty]"]').prop('readonly', false);
					$('input[name="dt[' + i + '][qty]"]').val('');

					$('input[name="dt[' + i + '][hargasatuan]"]').prop('readonly', false);
					$('input[name="dt[' + i + '][hargasatuan]"]').val('');

					$('input[name="dt[' + i + '][ppn]"]').prop('disabled', false);
					$('input[name="dt[' + i + '][ppn]"]').val('');

					$('input[name="dt[' + i + '][jumlahharga]"]').val('0');

					$('input[name="dt[' + i + '][disc_persen]"]').prop('readonly', false);
					$('input[name="dt[' + i + '][disc_persen]"]').val('0');

					$('input[name="dt[' + i + '][disc_num]"]').prop('readonly', false);
					$('input[name="dt[' + i + '][disc_num]"]').val('0');

					$('input[name="dt[' + i + '][nilai_ppn]"]').val('0');

					$('input[name="dt[' + i + '][persen_ppn]"]').val('0');

					$('input[name="dt[' + i + '][totalharga]"]').val('0');

					$('input[name="dt[' + i + '][note]"]').prop('readonly', false);
					$('input[name="dt[' + i + '][note]"]').val('');
				}
			} else {
				// If the checkbox being changed is unchecked, uncheck all checkboxes
				$('input[type="checkbox"]').prop('checked', false);
				for (var i = 1; i <= count_all_prod; i++) {
					$('input[name="dt[' + i + '][description]"]').prop('readonly', true);
					$('input[name="dt[' + i + '][description]"]').val('');

					$('input[name="dt[' + i + '][qty]"]').prop('readonly', true);
					$('input[name="dt[' + i + '][qty]"]').val('');

					$('input[name="dt[' + i + '][hargasatuan]"]').prop('readonly', true);
					$('input[name="dt[' + i + '][hargasatuan]"]').val('');

					$('input[name="dt[' + i + '][ppn]"]').prop('disabled', true);
					$('input[name="dt[' + i + '][ppn]"]').val('');

					$('input[name="dt[' + i + '][jumlahharga]"]').val('0');

					$('input[name="dt[' + i + '][disc_persen]"]').prop('readonly', true);
					$('input[name="dt[' + i + '][disc_persen]"]').val('0');

					$('input[name="dt[' + i + '][disc_num]"]').prop('readonly', true);
					$('input[name="dt[' + i + '][disc_num]"]').val('0');

					$('input[name="dt[' + i + '][nilai_ppn]"]').val('0');

					$('input[name="dt[' + i + '][persen_ppn]"]').val('0');

					$('input[name="dt[' + i + '][totalharga]"]').val('0');

					$('input[name="dt[' + i + '][note]"]').prop('readonly', true);
					$('input[name="dt[' + i + '][note]"]').val('');
				}
			}
		});

		$(document).on('change', '#id_suplier', function() {
			let id_suplier = $('#id_suplier').val();
			$.ajax({
				type: "POST",
				url: siteurl + 'purchase_order/getPR',
				data: {
					'id_suplier': id_suplier
				},
				cache: false,
				dataType: 'json',
				success: function(data) {
					$('#no_pr').html(data.option).trigger("chosen:updated");
				}
			});
		});

		$(document).on('change', '#no_pr', function() {
			let loi = $('#loi').val();
			let no_pr = $(this).val();
			$.ajax({
				type: "POST",
				url: siteurl + 'purchase_order/AddMaterial_Direct',
				data: {
					'loi': loi,
					'no_pr': no_pr
				},
				cache: false,
				dataType: 'json',
				success: function(data) {
					$('#data_request').html(data.list_mat);
					$(".bilangan-desimal").maskMoney();
					$('.autoNumeric3').autoNumeric('init', {
						vMin: 0
					});
					$('.autoNumeric').autoNumeric();
					$('#expect_tanggal').val(data.min_date);
				}
			});
		});

		$(document).on('click', '.hapus_baris', function() {
			$(this).parent().parent().remove();
			SumDel();
		});

		$('#simpan-com').click(function(e) {
			e.preventDefault();
			var deskripsi = $('#deskripsi').val();
			var tanggal = $('#tanggal').val();
			var loi = $('#loi').val();
			var term = $('#term').val();
			var cif = $('#cif').val();
			var pajak = $('.pajak').val();
			var supplier = $('#supplier').val();
			var department = $('#select_department').val();
			var delivery_date = $('.delivery_date').val()
			var currency = $('#select_curr').val()

			var ttl_persen_top = 0;
			$('.input_progress').each(function() {
				var progress = $(this).val();
				if (progress !== '') {
					progress = progress.split(',').join('');
					progress = parseFloat(progress);

					ttl_persen_top += progress;
				}
			});


			var data, xhr;
			if (loi == '' || loi == null) {
				swal("Warning", "Form Tidak Boleh Kosong :)", "error");
				return false;
			} else if (tanggal == '' || tanggal == null) {
				swal("Warning", "Tanggal Tidak Boleh Kosong :)", "error");
				return false;
			} else if (supplier == '' || supplier == null) {
				swal("Warning", "Supplier tidak boleh kosong  :)", "error");
				return false;
			} else if (select_department == '' || select_department == null) {
				swal("Warning", "Department tidak boleh kosong  :)", "error");
				return false;
			} else if (delivery_date == '' || delivery_date == null) {
				swal("Warning", "Delivery Date tidak boleh kosong  :)", "error");
				return false;
			} else if (currency == '' || currency == null) {
				swal("Warning", "Currency tidak boleh kosong  :)", "error");
				return false;
			} else if (ttl_persen_top > 100) {
				swal("Warning", "Total TOP tidak boleh lebih dari 100%", "error");
				return false;
			} else {
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
							var baseurl = siteurl + 'purchase_order/SaveNew';
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
											timer: 5000,
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
												timer: 5000,
												showCancelButton: false,
												showConfirmButton: false,
												allowOutsideClick: false
											});
										} else {
											swal({
												title: "Save Failed!",
												text: data.pesan,
												type: "warning",
												timer: 5000,
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
										timer: 5000,
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
			}
		});

	});

	$(document).on('change', '.cng_nilai_ppn', function() {
		var key = $(this).data('key');
		var nilai = $("#dt_nilai_ppn_" + key).val();
		var nilai = nilai.split(',').join('');
		var nilai = parseFloat(nilai);
		var nilai = nilai.toFixed(2);

		var hargasatuan = $("#dt_hargasatuan_" + key).val();
		var hargasatuan = hargasatuan.split(',').join('');
		var hargasatuan = parseFloat(hargasatuan);

		var qty = $("#dt_qty_" + key).val();
		var qty = qty.split(',').join('');
		var qty = parseFloat(qty);

		if (qty <= 0 || hargasatuan <= 0) {
			var nilai_persen = 0;
		} else {
			var nilai_persen = parseFloat(nilai / (hargasatuan * qty) * 100);
		}

		$("#dt_persen_ppn_" + key).val(nilai_persen);

		HitAmmount(key);
	});

	$(document).on('change', '.cng_persen_ppn', function() {
		var key = $(this).data('key');

		var persen = $("#dt_persen_ppn_" + key).val();
		persen = persen.split(',').join('');
		persen = parseFloat(persen);

		var hargasatuan = $("#dt_hargasatuan_" + key).val();
		hargasatuan = hargasatuan.split(',').join('');
		hargasatuan = parseFloat(hargasatuan);

		var qty = $("#dt_qty_" + key).val();
		qty = qty.split(',').join('');
		qty = parseFloat(qty);

		var nilai_ppn = parseFloat((hargasatuan * qty) * persen / 100);

		$("#dt_nilai_ppn_" + key).autoNumeric('set', nilai_ppn.toFixed(2));


		HitAmmount(key);
	});

	$(document).on('keyup', '#totaldisc', function() {
		var total = getNum($("#hargatotal").val().split(",").join(""));
		var disc = getNum($("#totaldisc").val().split(",").join(""));

		var persen_disc = (disc / total * 100);
		$("#persendisc").val(number_format(persen_disc, 2));

		cariTotal();
	});

	$(document).on('click', '.add_top', function() {
		$.ajax({
			type: "POST",
			url: siteurl + active_controller + '/add_top_po',
			cache: false,
			dataType: 'JSON',
			success: function(result) {
				num_top++;
				var Rows = '<tr class="top_' + num_top + '">';

				Rows += '<td class="">';
				Rows += '<select class="form-control" name="group_top_' + num_top + '">';
				Rows += result.list_top_group;
				Rows += '</select>';
				Rows += '</td>';

				Rows += '<td class="">';
				Rows += '<input type="text" class="form-control form-control-sm input_progress progress_' + num_top + ' auto_num" name="progress_' + num_top + '" data-no="' + num_top + '">';
				Rows += '</select>';
				Rows += '</td>';

				Rows += '<td class="text-right">';
				Rows += '<input type="text" class="form-control form-control-sm nilai_top nilai_top_' + num_top + ' auto_num" name="nilai_top_' + num_top + '" data-no="' + num_top + '">';
				Rows += '</td>';

				Rows += '<td class="">';
				Rows += '<textarea name="keterangan_top_' + num_top + '" class="form-control form-control-sm"></textarea>';
				Rows += '</td>';

				Rows += '<td class="text-center">';
				Rows += '<button type="button" class="btn btn-sm btn-danger del_top" data-top_no="' + num_top + '"><i class="fa fa-trash"></i></button>';
				Rows += '</td>';

				Rows += '</tr>';


				$('.num_top').val(num_top);
				$('.list_tbody_top').append(Rows);
			},
			error: function(result) {
				swal({
					title: 'Error !',
					text: 'Please try again later !',
					type: 'error'
				});
			}
		});
	});

	$(document).on('change', '.input_progress', function() {
		var no = $(this).data('no');
		var subtotal = $('#grand_total').val();
		if (subtotal == '' || subtotal == null) {
			subtotal = 0;
		} else {
			subtotal = subtotal.split(',').join('');
			subtotal = parseFloat(subtotal);
		}

		var progress = $(this).val();
		if (progress == '' || progress == null) {
			progress = 0;
		} else {
			progress = progress.split(',').join('');
			progress = parseFloat(progress);
		}

		var nilai_top = (subtotal * progress / 100);

		$('.nilai_top_' + no).val(nilai_top.toLocaleString('en-US', {
			maximumFractionDigits: 2
		}));
	});

	$(document).on('change', '.nilai_top', function() {
		var no = $(this).data('no');

		var subtotal = $('#grand_total').val();
		if (subtotal == '' || subtotal == null) {
			subtotal = 0;
		} else {
			subtotal = subtotal.split(',').join('');
			subtotal = parseFloat(subtotal);
		}

		var nilai_top = $(this).val();
		if (nilai_top == '' || nilai_top == null) {
			nilai_top = 0;
		} else {
			nilai_top = nilai_top.split(',').join('');
			nilai_top = parseFloat(nilai_top);
		}

		var progress = (nilai_top / subtotal * 100);

		$(this).val(nilai_top.toLocaleString('en-US', {
			maximumFractionDigits: 2
		}));
		$('.progress_' + no).val(progress.toLocaleString('en-US', {
			maximumFractionDigits: 2
		}));
	});

	$(document).on('click', '.del_top', function() {
		var top_no = $(this).data('top_no');

		$('.top_' + top_no).remove();
	});


	function addmaterial() {
		var jumlah = $('#data_request').find('tr').length;
		var id_suplier = $("#id_suplier").val();
		var loi = $("#loi").val();
		var angka = jumlah + 1;
		if (id_suplier == '' || id_suplier == null || loi == '' || loi == null) {
			swal("Warning", "Silahkan Pilih Supplier Terlebih Dahulu :)", "error");
			return false;
		} else {
			$.ajax({
				type: "GET",
				url: siteurl + 'purchase_order/AddMaterial',
				data: "jumlah=" + jumlah + "&id_suplier=" + id_suplier + "&loi=" + loi,
				success: function(html) {
					$("#data_request").append(html);
					$(".bilangan-desimal").maskMoney();
					$(".chosen-select").chosen();
					$('.autoNumeric3').autoNumeric('init', {
						vMin: 0
					});
				}
			});
			$.ajax({
				type: "GET",
				url: siteurl + 'purchase_order/UbahImport',
				data: "loi=" + loi,
				success: function(html) {
					$("ubahloi").html(html);
				}
			});
		}
	}

	function HitungHarga(id) {
		var dt_qty = $("#dt_qty_" + id).val();
		var dt_width = $("#dt_width_" + id).val();
		var dt_hargasatuan = $("#dt_hargasatuan_" + id).val();
		// $.ajax({
		// type:"GET",
		// url:siteurl+'purchase_order/HitungHarga',
		// data:"dt_hargasatuan="+dt_hargasatuan+"&dt_qty="+dt_qty+"&id="+id,
		// success:function(html){
		// $("#jumlahharga_"+id).html(html);
		// }
		// });
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/TotalWeight',
			data: "dt_width=" + dt_width + "&dt_qty=" + dt_qty + "&id=" + id,
			success: function(html) {
				$("#totalwidth_" + id).html(html);
			}
		});
		// TotalSemua()
	}

	function CariPrice(id) {
		var dt_ratelme = $("#dt_ratelme_" + id).val();
		var dt_idmaterial = $("#dt_idmaterial_" + id).val();
		if (dt_idmaterial == '' || dt_idmaterial == null) {
			swal("Warning", "Silahkan Pilih Material Terlebih Dahulu :)", "error");
			return false;
		} else {
			$.ajax({
				type: "GET",
				url: siteurl + 'purchase_order/CariPrice',
				data: "dt_ratelme=" + dt_ratelme + "&dt_idmaterial=" + dt_idmaterial + "&id=" + id,
				success: function(html) {
					$("#dt_alloyprice_" + id).val(html);
				}
			});
		}
	}

	function CariPPN(id) {
		var ppn = $("#dt_ppn_" + id).val();

		// console.log(ppn)

		var harga = getNum($("#dt_jumlahharga_" + id).val().split(",").join(""));
		var disc_persen = getNum($("#disc_persen_" + id).val().split(",").join(""));
		var disc_num = getNum($("#disc_num_" + id).val().split(",").join(""));
		if (disc_num == '' || disc_num == 0) {
			disc_num = (harga * disc_persen / 100);
		}

		harga = (harga - disc_num);
		var dt_idmaterial = $("#dt_idmaterial_" + id).val();


		// if (dt_idmaterial == '' || dt_idmaterial == null) {
		// 	swal("Warning", "Silahkan Pilih Material Terlebih Dahulu :)", "error");
		// 	return false;
		// } else {


		// }
		if (ppn == '' || ppn == 'N') {
			$("#dt_nilai_ppn_" + id).attr('readonly', true);
			$("#dt_persen_ppn_" + id).attr('readonly', true);

			$("#dt_nilai_ppn_" + id).val('0');
			$("#dt_persen_ppn_" + id).val('0');
			HitAmmount(id);
		} else {
			$.ajax({
				type: "GET",
				url: siteurl + 'purchase_order/CariPPN',
				data: "harga=" + harga + "&id=" + id,
				success: function(html) {
					$("#dt_nilai_ppn_" + id).attr('readonly', false);
					$("#dt_persen_ppn_" + id).attr('readonly', false);

					$("#dt_nilai_ppn_" + id).val(html);
					$("#dt_persen_ppn_" + id).val(11);
					HitAmmount(id);
				}
			});
		}
	}

	function get_kurs() {
		// var loi = $("#loi").val();
		// $.ajax({
		// 	type: "GET",
		// 	url: siteurl + 'purchase_order/FormInputKurs',
		// 	data: "loi=" + loi,
		// 	success: function(html) {
		// 		$("#input_kurs").html(html);
		// 	}
		// });
	}

	function HitungUP(id) {
		var alloyprice = $("#dt_alloyprice_" + id).val();
		var fabcost = $("#dt_fabcost_" + id).val();
		var diskon = $("#dt_diskon_" + id).val();
		var pajak = $("#dt_pajak_" + id).val();
		var qty = $("#dt_qty_" + id).val();
		var hargasatuan = $("#dt_hargasatuan_" + id).val();
		var dt_width = $("#dt_totalwidth_" + id).val();

		var loi = $("#loi").val();
		// console.log(dt_width)
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/HitungUP',
			data: "fabcost=" + fabcost + "&alloyprice=" + alloyprice + "&hargasatuan=" + hargasatuan + "&loi=" + loi,
			success: function(html) {
				// $("#dt_hargasatuan_"+id).val(html); 
				HitAmmount(id)
			}
		});
		// $.ajax({
		// type:"GET",
		// url:siteurl+'purchase_order/Hitjumlah',
		// data:"fabcost="+fabcost+"&alloyprice="+alloyprice+"&pajak="+pajak+"&diskon="+diskon+"&qty="+qty+"&hargasatuan="+hargasatuan+"&loi="+loi+"&dt_width="+dt_width,
		// success:function(html){
		// $("#dt_jumlahharga_"+id).val(html); 
		// }
		// });		
	}

	function HitungUPIm(id) {
		var alloyprice = $("#dt_alloyprice_" + id).val();
		var fabcost = $("#dt_fabcost_" + id).val();
		var diskon = $("#dt_diskon_" + id).val();
		var pajak = $("#dt_pajak_" + id).val();
		var qty = $("#dt_qty_" + id).val();
		var hargasatuan = $("#dt_hargasatuan_" + id).val();
		var dt_width = $("#dt_totalwidth_" + id).val();

		var loi = $("#loi").val();
		// console.log(dt_width)
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/HitungUP',
			data: "fabcost=" + fabcost + "&alloyprice=" + alloyprice + "&hargasatuan=" + hargasatuan + "&loi=" + loi,
			success: function(html) {
				// $("#dt_hargasatuan_"+id).val(html); 
				$('.autoNumeric3').autoNumeric('init', {
					vMin: 0
				});
				HitAmmount(id)
			}
		});
		// $.ajax({
		// type:"GET",
		// url:siteurl+'purchase_order/Hitjumlah',
		// data:"fabcost="+fabcost+"&alloyprice="+alloyprice+"&pajak="+pajak+"&diskon="+diskon+"&qty="+qty+"&hargasatuan="+hargasatuan+"&loi="+loi+"&dt_width="+dt_width,
		// success:function(html){
		// $("#dt_jumlahharga_"+id).val(html); 
		// }
		// });		
	}

	function CariProperties(id) {
		var idpr = $("#dt_idpr_" + id).val();
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariIdMaterial',
			data: "idpr=" + idpr + "&id=" + id,
			success: function(html) {
				$("#idmaterial_" + id).html(html);
			}
		});
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariNamaMaterial',
			data: "idpr=" + idpr + "&id=" + id,
			success: function(html) {
				$("#namaterial_" + id).html(html);
			}
		});
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariPanjangMaterial',
			data: "idpr=" + idpr + "&id=" + id,
			success: function(html) {
				$("#panjang_" + id).html(html);
			}
		});
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariLebarMaterial',
			data: "idpr=" + idpr + "&id=" + id,
			success: function(html) {
				$("#lebar_" + id).html(html);
			}
		});
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariDescripitionMaterial',
			data: "idpr=" + idpr + "&id=" + id,
			success: function(html) {
				$("#description_" + id).html(html);
			}
		});
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariQtyMaterial',
			data: "idpr=" + idpr + "&id=" + id,
			success: function(html) {
				$("#qty_" + id).html(html);
			}
		});
		// $.ajax({
		// type:"GET",
		// url:siteurl+'purchase_order/CariweightMaterial',
		// data:"idpr="+idpr+"&id="+id,
		// success:function(html){
		// $("#width_"+id).html(html);
		// }
		// });
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariTweightMaterial',
			data: "idpr=" + idpr + "&id=" + id,
			success: function(html) {
				$("#totalwidth_" + id).html(html);
			}
		});

		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariWidthMaterial',
			data: "idpr=" + idpr + "&id=" + id,
			success: function(html) {
				$("#width_" + id).html(html);
			}
		});

		var a;
		var ArrList = [];
		for (a = 1; a <= 100; a++) {
			var dataid = $('#dt_idpr_' + a).val();
			ArrList.push(dataid);
		}
		$.ajax({
			type: "POST",
			url: siteurl + 'purchase_order/getDateExp',
			data: {
				'id_pr': ArrList
			},
			dataType: 'json',
			success: function(data) {
				$('#expect_tanggal').val(data.minimal)
			}
		});
	}

	function LockMaterial(id) {
		var idpr = $("#dt_idpr_" + id).val();
		var idmaterial = $("#dt_idmaterial_" + id).val();
		var namaterial = $("#dt_namamaterial_" + id).val();
		var description = $("#dt_description_" + id).val();
		var qty = $("#dt_qty_" + id).val();
		var width = $("#dt_width_" + id).val();
		var totalwidth = $("#dt_totalweight_" + id).val();
		var hargasatuan = $("#dt_hargasatuan_" + id).val();
		var diskon = $("#dt_diskon_" + id).val();
		var pajak = $("#dt_pajak_" + id).val();
		var ratelme = $("#dt_ratelme_" + id).val();
		var alloyprice = $("#dt_alloyprice_" + id).val();
		var fabcost = $("#dt_fabcost_" + id).val();
		var panjang = $("#dt_panjang_" + id).val();
		var lebar = $("#dt_lebar_" + id).val();
		var jumlahharga = $("#dt_jumlahharga_" + id).val();
		var note = $("#dt_note_" + id).val();
		var subtotal = $("#grand_total").val();
		var hargatotal = $("#hargatotal").val();
		var diskontotal = $("#diskontotal").val();
		var taxtotal = $("#taxtotal").val();
		if (qty == '' || qty == null || hargasatuan == '' || hargasatuan == null) {
			swal("Warning", "Form Tidak Boleh Kosong :)", "error");
			return false;
		} else {
			$.ajax({
				type: "GET",
				url: siteurl + 'purchase_order/LockMatrial',
				data: "idpr=" + idpr + "&id=" + id + "&idmaterial=" + idmaterial + "&width=" + width + "&ratelme=" + ratelme + "&alloyprice=" + alloyprice + "&fabcost=" + fabcost + "&panjang=" + panjang + "&lebar=" + lebar + "&totalwidth=" + totalwidth + "&namaterial=" + namaterial + "&description=" + description + "&qty=" + qty + "&hargasatuan=" + hargasatuan + "&diskon=" + diskon + "&pajak=" + pajak + "&jumlahharga=" + jumlahharga + "&note=" + note,
				success: function(html) {
					$("#trmaterial_" + id).html(html);
				}
			});
			$.ajax({
				type: "GET",
				url: siteurl + 'purchase_order/CariTHarga',
				data: "idpr=" + idpr + "&id=" + id + "&hargatotal=" + hargatotal + "&idmaterial=" + idmaterial + "&namaterial=" + namaterial + "&description=" + description + "&qty=" + qty + "&hargasatuan=" + hargasatuan + "&diskon=" + diskon + "&pajak=" + pajak + "&jumlahharga=" + jumlahharga + "&note=" + note,
				success: function(html) {
					$("#ForHarga").html(html);
				}
			});
			$.ajax({
				type: "GET",
				url: siteurl + 'purchase_order/CariTDiskon',
				data: "idpr=" + idpr + "&id=" + id + "&diskontotal=" + diskontotal + "&idmaterial=" + idmaterial + "&namaterial=" + namaterial + "&description=" + description + "&qty=" + qty + "&hargasatuan=" + hargasatuan + "&diskon=" + diskon + "&pajak=" + pajak + "&jumlahharga=" + jumlahharga + "&note=" + note,
				success: function(html) {
					$("#ForDiskon").html(html);
				}
			});
			$.ajax({
				type: "GET",
				url: siteurl + 'purchase_order/CariTPajak',
				data: "idpr=" + idpr + "&id=" + id + "&taxtotal=" + taxtotal + "&idmaterial=" + idmaterial + "&namaterial=" + namaterial + "&description=" + description + "&qty=" + qty + "&hargasatuan=" + hargasatuan + "&diskon=" + diskon + "&pajak=" + pajak + "&jumlahharga=" + jumlahharga + "&note=" + note,
				success: function(html) {
					$("#ForTax").html(html);
				}
			});
			$.ajax({
				type: "GET",
				url: siteurl + 'purchase_order/CariTSum',
				data: "idpr=" + idpr + "&id=" + id + "&hargatotal=" + hargatotal + "&diskontotal=" + diskontotal + "&taxtotal=" + taxtotal + "&idmaterial=" + idmaterial + "&namaterial=" + namaterial + "&description=" + description + "&qty=" + qty + "&hargasatuan=" + hargasatuan + "&diskon=" + diskon + "&pajak=" + pajak + "&jumlahharga=" + jumlahharga + "&note=" + note,
				success: function(html) {
					$("#ForSum").html(html);
				}
			});
		}
	}

	function CancelItem(id) {
		var idpr = $("#dt_idpr_" + id).val();
		var idmaterial = $("#dt_idmaterial_" + id).val();
		var namaterial = $("#dt_namamaterial_" + id).val();
		var description = $("#dt_description_" + id).val();
		var qty = $("#dt_qty_" + id).val();
		var hargasatuan = $("#dt_hargasatuan_" + id).val();
		var diskon = $("#dt_diskon_" + id).val();
		var pajak = $("#dt_pajak_" + id).val();
		var jumlahharga = $("#dt_jumlahharga_" + id).val();
		var note = $("#dt_note_" + id).val();
		var subtotal = $("#grand_total").val();
		var hargatotal = $("#hargatotal").val();
		var diskontotal = $("#diskontotal").val();
		var taxtotal = $("#taxtotal").val();
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariMinHarga',
			data: "idpr=" + idpr + "&id=" + id + "&hargatotal=" + hargatotal + "&idmaterial=" + idmaterial + "&namaterial=" + namaterial + "&description=" + description + "&qty=" + qty + "&hargasatuan=" + hargasatuan + "&diskon=" + diskon + "&pajak=" + pajak + "&jumlahharga=" + jumlahharga + "&note=" + note,
			success: function(html) {
				$("#ForHarga").html(html);
			}
		});
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariMinDiskon',
			data: "idpr=" + idpr + "&id=" + id + "&diskontotal=" + diskontotal + "&idmaterial=" + idmaterial + "&namaterial=" + namaterial + "&description=" + description + "&qty=" + qty + "&hargasatuan=" + hargasatuan + "&diskon=" + diskon + "&pajak=" + pajak + "&jumlahharga=" + jumlahharga + "&note=" + note,
			success: function(html) {
				$("#ForDiskon").html(html);
			}
		});
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariMinPajak',
			data: "idpr=" + idpr + "&id=" + id + "&taxtotal=" + taxtotal + "&idmaterial=" + idmaterial + "&namaterial=" + namaterial + "&description=" + description + "&qty=" + qty + "&hargasatuan=" + hargasatuan + "&diskon=" + diskon + "&pajak=" + pajak + "&jumlahharga=" + jumlahharga + "&note=" + note,
			success: function(html) {
				$("#ForTax").html(html);
			}
		});
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariMinSum',
			data: "idpr=" + idpr + "&id=" + id + "&hargatotal=" + hargatotal + "&diskontotal=" + diskontotal + "&taxtotal=" + taxtotal + "&idmaterial=" + idmaterial + "&namaterial=" + namaterial + "&description=" + description + "&qty=" + qty + "&hargasatuan=" + hargasatuan + "&diskon=" + diskon + "&pajak=" + pajak + "&jumlahharga=" + jumlahharga + "&note=" + note,
			success: function(html) {
				$("#ForSum").html(html);
			}
		});
		$('#data_request #trmaterial_' + id).remove();
	}

	function HapusItem(id) {}

	function HitungHarga2(id) {
		var dt_qty = $("#dt_qty_" + id).val();
		var dt_width = $("#dt_totalwidth_" + id).val();
		var dt_hargasatuan = $("#dt_hargasatuan_" + id).val();
		console.log(dt_width);
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/HitungHarga',
			data: "dt_hargasatuan=" + dt_hargasatuan + "&dt_qty=" + dt_qty + "&id=" + id + "&dt_width=" + dt_width,
			success: function(html) {
				$("#jumlahharga_" + id).html(html);
			}
		});

	}

	function get_lokasi() {
		var supplier = $("#id_suplier").val();
		$.ajax({
			type: "GET",
			url: siteurl + 'purchase_order/CariLokasi',
			data: "supplier=" + supplier,
			success: function(html) {
				$("#loi").html(html);
				get_kurs();
			}

		});


	}

	function HitAmmount(id, source = null) {
		let qty = getNum($("#dt_qty_" + id).val().replace(/,/g, ""));
		let harga = getNum($("#dt_hargasatuan_" + id).val().replace(/,/g, ""));
		let harga_inc = getNum($("#dt_hargasatuan_include_" + id).val().replace(/,/g, ""));
		let disc_persen = getNum($("#disc_persen_" + id).val().replace(/,/g, ""));
		let disc_num = getNum($("#disc_num_" + id).val().replace(/,/g, ""));

		// =========================
		// AUTO DETECT
		// =========================
		if (!source) {
			source = (harga_inc > 0 && harga === 0) ? 'include' : 'exclude';
		}

		// =========================
		// SYNC INCLUDE / EXCLUDE
		// =========================
		if (source === 'include' && harga_inc > 0) {
			harga = harga_inc / 1.11;
			$("#dt_hargasatuan_" + id).val(number_format(harga, 2));
		}

		if (source === 'exclude' && harga > 0) {
			harga_inc = Math.round(harga * 1.11);
			$("#dt_hargasatuan_include_" + id).val(number_format(harga_inc, 2));
		}

		let jumlah = harga * qty;

		// =========================
		// SYNC DISCOUNT (PENTING)
		// =========================
		if (source === 'disc_persen') {
			disc_num = jumlah * disc_persen / 100;
			$("#disc_num_" + id).val(number_format(disc_num, 2));
		}

		if (source === 'disc_num') {
			disc_persen = jumlah > 0 ? (disc_num / jumlah * 100) : 0;
			$("#disc_persen_" + id).val(disc_persen.toFixed(2));
		}

		// fallback kalau tidak ada source discount
		if (!source || (source !== 'disc_persen' && source !== 'disc_num')) {
			disc_num = jumlah * disc_persen / 100;
		}

		// =========================
		// CALCULATION
		// =========================
		let jumlah_w_disc = jumlah - disc_num;

		let dpp = jumlah_w_disc * (11 / 12);
		let ppn = dpp * (12 / 100);
		let total = jumlah_w_disc + ppn;

		// =========================
		// SET VALUE
		// =========================
		$("#dt_jumlahharga_" + id).val(number_format(jumlah, 2));
		$("#dt_nilai_ppn_" + id).val(number_format(ppn, 2));
		$("#dt_totalharga_" + id).val(number_format(jumlah_w_disc, 2));

		updateGlobal();
	}

	function SumDel() {
		var SUM_JML = 0
		var SUM_DIS = 0
		var SUM_PJK = 0
		var SUM_JMX = 0

		$(".ch_diskon").each(function() {
			SUM_DIS += Number($(this).val());
		});

		$(".ch_pajak").each(function() {
			SUM_PJK += Number($(this).val());
		});

		$(".ch_jumlah").each(function() {
			SUM_JML += Number($(this).val());
		});

		$(".ch_jumlah_ex").each(function() {
			SUM_JMX += Number($(this).val().split(",").join(""));
		});

		$("#hargatotal").val(number_format(SUM_JMX, 2));
		$("#diskontotal").val(number_format(SUM_DIS, 2));
		$("#taxtotal").val(number_format(SUM_PJK, 2));
		$("#grand_total").val(number_format(SUM_JML, 0));

	}

	function updateGlobal() {
		let total_ex = 0;
		let total_ex_w_disc_sat = 0;
		let diskon_total = 0;
		let diskonkhusus = getNum($("#diskonkhusus").val().replace(/,/g, ""));

		$(".ch_jumlah_ex").each(function() {
			total_ex += getNum($(this).val().replace(/,/g, ""));
		});

		$(".ch_jumlah_ex2").each(function() {
			total_ex_w_disc_sat += getNum($(this).val().replace(/,/g, ""));
		});

		$(".disc_num").each(function() {
			diskon_total += getNum($(this).val().replace(/,/g, ""));
		});

		let total_ex_w_disc_khus = total_ex_w_disc_sat - diskonkhusus
		let dpp = (11 / 12) * total_ex_w_disc_khus
		let total_ppn = dpp * (12 / 100)
		let total_inc = total_ex + total_ppn;

		$("#subtotal").val(number_format(total_ex_w_disc_sat, 2));
		$("#diskontotal").val(number_format(diskon_total, 2));
		$("#totalexppn").val(number_format(total_ex_w_disc_khus, 2));
		$("#totalinppn").val(number_format(total_inc, 2));
		$("#dpp").val(number_format(dpp, 2));
		$("#ppn").val(number_format(total_ppn, 2));

		cariTotal();
	}

	function cariTotal() {
		let kirim = getNum($("#kirim").val().replace(/,/g, ""));
		let total_ex = getNum($("#totalexppn").val().replace(/,/g, ""));
		let total_ppn = getNum($("#ppn").val().replace(/,/g, ""));

		let grandtotal = total_ex + total_ppn + kirim

		$("#grand_total").val(number_format(grandtotal, 0));
		$("#kirim").val(number_format(kirim, 2));
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
<script src="<?= base_url('assets/js/jquery.maskMoney.js') ?>"></script>
<script src="<?= base_url('assets/js/autoNumeric.js') ?>"></script>
<script>
	$(function() {
		$('#select_department').chosen();
		$('#select_curr').chosen();
		$('#supplier').chosen();
		$('.select').chosen();
		$('#tanggal').datepicker({
			dateFormat: 'yy-mm-dd',
			changeMonth: true,
			changeYear: true,
		});
	});
</script>

<!-- html trash -->