<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('po_merge_detail_rows')) {
	/**
	 * Gabungkan baris detail PO (dt_trans_po) untuk TAMPILAN saja.
	 *
	 * Barang produksi yang sama dalam 1 PO (biasanya berasal dari beberapa PR berbeda)
	 * disimpan sebagai beberapa baris dt_trans_po (1 baris per PR). Fungsi ini menyatukannya
	 * jadi 1 baris supaya tampilan view / edit / print / approval sama seperti saat create PO.
	 *
	 * Aturan:
	 * - Hanya baris PR produksi (tipe kosong) yang punya idmaterial yang digabung.
	 *   PR departemen / asset tidak disentuh.
	 * - Kunci gabung: no_po + idmaterial + harga satuan + harga include + persen diskon.
	 *   Baris dengan harga/diskon berbeda tetap terpisah supaya nilai tidak salah.
	 * - Kolom angka dijumlahkan, kolom lain diambil dari baris pertama (id terkecil).
	 * - Properti tambahan:
	 *     id_list      : "443,444"           (id dt_trans_po yang tergabung, urut id)
	 *     id_pr_list   : "443:521,444:522"   (pasangan id dt_trans_po : idpr)
	 *     merged_count : jumlah baris yang tergabung
	 *
	 * Data di database TIDAK diubah. Penyimpanan tetap per baris / per PR.
	 *
	 * @param array $rows Hasil ->result() (array of object)
	 * @return array
	 */
	function po_merge_detail_rows($rows)
	{
		if (empty($rows) || !is_array($rows)) {
			return $rows;
		}

		$sum_fields = ['qty', 'jumlahharga', 'harga_total', 'ppn', 'nilai_disc', 'propose_purchase', 'totalwidth', 'qty_in'];

		// Kelompokkan dengan mempertahankan urutan asli dari query, supaya PO yang tidak
		// punya barang kembar tampil persis sama seperti sebelumnya.
		$groups = [];
		$index = [];

		foreach ($rows as $row) {
			$tipe = isset($row->tipe_pr) ? $row->tipe_pr : (isset($row->tipe) ? $row->tipe : '');
			$idmaterial = isset($row->idmaterial) ? $row->idmaterial : '';

			if (($tipe !== '' && $tipe !== null) || $idmaterial === '' || $idmaterial === null) {
				$groups[] = [$row];
				continue;
			}

			$key = implode('|', [
				$row->no_po ?? '',
				$idmaterial,
				(float) ($row->hargasatuan ?? 0),
				(float) ($row->hargasatuan_include ?? 0),
				(float) ($row->persen_disc ?? 0),
			]);

			if (!isset($index[$key])) {
				$index[$key] = count($groups);
				$groups[] = [$row];
			} else {
				$groups[$index[$key]][] = $row;
			}
		}

		$result = [];
		foreach ($groups as $members) {
			// Di dalam grup, urutkan per id: urutan ini dipakai saat qty dipecah lagi (SaveEditPO)
			usort($members, function ($a, $b) {
				return (int) ($a->id ?? 0) - (int) ($b->id ?? 0);
			});

			$base = clone $members[0];
			$ids = [];
			$pairs = [];
			foreach ($members as $i => $m) {
				$ids[] = (int) ($m->id ?? 0);
				$pairs[] = (int) ($m->id ?? 0) . ':' . (int) ($m->idpr ?? 0);

				if ($i > 0) {
					foreach ($sum_fields as $f) {
						if (property_exists($base, $f) || property_exists($m, $f)) {
							$base->$f = (float) ($base->$f ?? 0) + (float) ($m->$f ?? 0);
						}
					}
				}
			}

			$base->id_list = implode(',', $ids);
			$base->id_pr_list = implode(',', $pairs);
			$base->merged_count = count($members);
			$result[] = $base;
		}

		return $result;
	}
}
