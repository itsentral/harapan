<?php
$ENABLE_ADD     = has_permission('Expense.Add');
$ENABLE_MANAGE  = has_permission('Expense.Manage');
$ENABLE_VIEW    = has_permission('Expense.View');
$ENABLE_DELETE  = has_permission('Expense.Delete');
?>
<link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>">

<style>
	#mytabledata_expense thead th {
		background-color: #3c8dbc;
		color: #ffffff;
		text-align: center;
		vertical-align: middle;
		border: 1px solid #367fa9;
	}

	#mytabledata_expense tbody td {
		vertical-align: middle;
	}

	.badge {
		font-size: 11px;
		padding: 4px 8px;
		font-weight: 600;
	}

	.box-header-flex {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}
</style>

<div id="alert_edit" class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>

<div class="box box-primary">
	<div class="box-header with-border box-header-flex">
		<h3 class="box-title"><i class="fa fa-list"></i> Daftar Expense Report</h3>
		<div>
			<?php if ($ENABLE_ADD) : ?>
				<button class="btn btn-success btn-sm" type="button" onclick="data_add()">
					<i class="fa fa-plus">&nbsp;</i> Tambah Expense
				</button>
			<?php endif; ?>
			<?php if ($ENABLE_VIEW) : ?>
				<a class="btn btn-success btn-sm" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);" href="<?= base_url(isset($export_url) ? $export_url : 'expense/download_excel') ?>">
					<i class="fa fa-file-excel-o">&nbsp;</i> Export Excel
				</a>
			<?php endif; ?>
		</div>
	</div>
	<!-- /.box-header -->
	<div class="box-body">
		<div class="table-responsive">
			<table id="mytabledata_expense" class="table table-bordered table-striped table-hover" width="100%">
				<thead>
					<tr>
						<th width="4%">#</th>
						<th width="13%">No Dokumen</th>
						<th width="9%">Tanggal</th>
						<th width="12%">Nama</th>
						<th width="8%">Jenis</th>
						<th width="12%">Approval</th>
						<th width="10%">Approval Date</th>
						<th width="15%">Keterangan</th>
						<th width="8%">Status</th>
						<th width="9%">Action</th>
					</tr>
				</thead>
				<tbody>
					<?php
					if (!empty($results)) {
						$numb = 0;
						foreach ($results as $record) {
							$numb++;
							$tgl_doc = (!empty($record->tgl_doc) && $record->tgl_doc != '0000-00-00') ? date('d M Y', strtotime($record->tgl_doc)) : '-';
							$app_date = (!empty($record->approved_on) && $record->approved_on != '0000-00-00 00:00:00') ? date('d M Y H:i', strtotime($record->approved_on)) : '-';
					?>
							<tr>
								<td class="text-center"><?= $numb; ?></td>
								<td class="text-center"><b><?= $record->no_doc ?></b></td>
								<td class="text-center"><?= $tgl_doc ?></td>
								<td><?= $record->nmuser ?: $record->nama ?></td>
								<td class="text-center">
									<?= (($record->pettycash != null) ? '<span class="badge bg-blue">Pettycash</span>' : '<span class="badge bg-purple">Expense</span>') ?>
								</td>
								<td><?= $record->nmapproval ?: '-' ?></td>
								<td class="text-center"><?= $app_date ?></td>
								<td><?= $record->informasi ?></td>
								<td class="text-center">
									<?php
									$st_label = isset($status[$record->status]) ? $status[$record->status] : $record->status;
									if ($record->status == 0) {
										echo '<span class="badge bg-yellow">' . $st_label . '</span>';
									} elseif ($record->status == 1) {
										echo '<span class="badge bg-green">' . $st_label . '</span>';
									} elseif ($record->status == 2) {
										echo '<span class="badge bg-blue">' . $st_label . '</span>';
									} elseif ($record->status == 9) {
										echo '<span class="badge bg-red">' . $st_label . '</span>';
									} else {
										echo '<span class="badge bg-gray">' . $st_label . '</span>';
									}
									?>
								</td>
								<td class="text-center" style="white-space: nowrap;">
									<?php if ($ENABLE_VIEW) : ?>
										<a class="btn btn-default btn-xs" href="<?= base_url('expense/expense_print/' . $record->id) ?>" target="expense_print" title="Print"><i class="fa fa-print"></i></a>
										<a class="btn btn-info btn-xs" href="javascript:void(0)" data-jenis="<?= $record->pettycash ?>"
											onclick="setExpenseUrls(this); data_view('<?= $record->id ?>')" title="Lihat"><i class="fa fa-eye"></i></a>
										<?php endif;
									if ($ENABLE_MANAGE) :
										if ($record->status == 0 || $record->status == 9) { ?>
											<a class="btn btn-warning btn-xs" href="javascript:void(0)" data-jenis="<?= $record->pettycash ?>"
												onclick="setExpenseUrls(this); data_edit('<?= $record->id ?>')" title="Edit"><i class="fa fa-edit"></i></a>
										<?php }
									endif;
									if ($ENABLE_DELETE) :
										if ($record->status == 0 || $record->status == 9) { ?>
											<a class="btn btn-danger btn-xs" href="javascript:void(0)" title="Hapus" onclick="data_delete('<?= $record->id ?>')"><i class="fa fa-trash"></i></a>
									<?php }
									endif; ?>
								</td>
							</tr>
					<?php
						}
					}  ?>
				</tbody>
			</table>
		</div>
	</div>
	<!-- /.box-body -->
</div>
<div id="form-data"></div>

<div class="modal fade" id="modalKasbon" tabindex="-1" role="dialog" aria-labelledby="modalKasbonLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #3c8dbc; color: #fff;">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color:#fff; opacity:0.9;">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title" id="modalKasbonLabel"><i class="fa fa-money"></i> Pilih Data Kasbon</h4>
			</div>
			<div class="modal-body">
				<table class="table table-bordered table-striped" id="tableKasbon" width="100%">
					<thead>
						<tr style="background-color: #f4f4f4;">
							<th>#</th>
							<th>No Dokumen</th>
							<th>Tanggal</th>
							<th>Keperluan</th>
							<th>Keterangan</th>
							<th>Jumlah</th>
							<th>Aksi</th>
						</tr>
					</thead>
					<tbody>
						<!-- Data kasbon akan dimuat secara dinamis -->
					</tbody>
				</table>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
			</div>
		</div>
	</div>
</div>

<!-- DataTables -->
<script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>

<!-- page script -->
<script type="text/javascript">
	var url_add = siteurl + 'expense/create/';
	var url_delete = siteurl + 'expense/delete/';

	var url_edit = siteurl + 'expense/edit/';
	var url_view = siteurl + 'expense/view/';

	function setExpenseUrls(el) {
		var jenis = $(el).data('jenis'); // baca jenis dari tombol yang diklik

		if (jenis && jenis !== "") {
			url_edit = siteurl + 'expense/edit_pc/';
			url_view = siteurl + 'expense/view_pc/';
		} else {
			url_edit = siteurl + 'expense/edit/';
			url_view = siteurl + 'expense/view/';
		}
	}

	$(document).ready(function() {
		$("#mytabledata_expense").DataTable({
			"responsive": true,
			"order": [
				[1, "desc"]
			]
		});
	});
</script>