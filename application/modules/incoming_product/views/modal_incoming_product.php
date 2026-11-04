<?php

// cek currency

?>
<form action="#" method="POST" id="form_adjustment" enctype="multipart/form-data" autocomplete='off'>
    <div class="box-body">
        <table style="width: 50%;">
            <tr>
                <td>No PO</td>
                <td>:</td>
                <td><?= $no_surat ?></td>
            </tr>
            <tr>
                <td>No PR</td>
                <td>:</td>
                <td><?= $no_pr ?></td>
            </tr>
            <tr>
                <td>Incoming Date</td>
                <td>:</td>
                <td>
                    <input type="date" name="incoming_date" id="" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                </td>
            </tr>
        </table>
        <br>
        <input type="hidden" name='no_po' id='no_po' value='<?= $no_po; ?>'>
        <input type="hidden" name="no_surat" value="<?= $no_surat; ?>">
        <?php
        $total_freight = 0;
        $kurs = 1;
        if (!empty($no_ros)) {
            $dataros = $this->db->query("SELECT * FROM report_of_shipment WHERE id = '" . $this->db->escape_str($no_ros) . "'")->row();

            $total_freight = !empty($dataros) ? $dataros->fc_cost : 0;
            $kurs = !empty($dataros) ? $dataros->freight_curs : 0;
        }
        ?>
        <input type="hidden" name='total_freight' id='total_freight' value='<?= $total_freight; ?>'>

        <?php
        $Noo = 0;
        foreach ($result_header as $header_item) {
        ?>

            <table class="table table-striped table-bordered table-hover table-condensed" width="100%" style="margin-top: 1.5vh;">
                <thead id='head_table'>
                    <tr class='bg-blue'>
                        <th class="text-center" style='vertical-align:middle;' width='5%'>No</th>
                        <th class="text-center" style='vertical-align:middle;'>Material ID</th>
                        <th class="text-center" style='vertical-align:middle;'>Material Name</th>
                        <th class="text-center" style='vertical-align:middle;' width='10%'>Qty Order</th>
                        <th class="text-center" style='vertical-align:middle;' width='10%'>UoM Order</th>
                        <!-- <th class="text-center" style='vertical-align:middle;' width='10%'>Qty belum dikirim</th> -->
                        <th class="text-center" style='vertical-align:middle;' width='13%'>Qty belum dikirim</th>
                        <th class="text-center" style='vertical-align:middle;' width='13%'>Qty NG</th>
                        <th class="text-center" style='vertical-align:middle;' width='7%'>Qty Diterima</th>
                        <th class="text-center" style='vertical-align:middle;' width='17%'>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $kode_trans = !empty($kode_trans) ? $kode_trans : '';
                    $this->db->select('a.*, b.code as code_product, b.konversi, c.code as unit_measure, d.code as unit_packing, e.qty_order');
                    $this->db->from('dt_trans_po a');
                    $this->db->join('new_inventory_4 b', 'b.code_lv4 = a.idmaterial', 'left');
                    $this->db->join('ms_satuan c', 'c.id = b.id_unit', 'left');
                    $this->db->join('ms_satuan d', 'd.id = b.id_unit_packing', 'left');
                    $this->db->join('tr_incoming_check_detail e', 'e.id_po_detail = a.id AND e.kode_trans = "' . $kode_trans . '"', 'left');
                    $this->db->where('a.no_po', $header_item->no_po);
                    // Satu baris per baris dt_trans_po (bukan per material), supaya barang yang sama
                    // dari beberapa PR dalam 1 PO tampil lengkap dan bisa diterima per baris.
                    $this->db->group_by('a.id');
                    $this->db->order_by('a.id', 'ASC');
                    $result = $this->db->get()->result_array();

                    if (!empty($result)) {
                        $Total1 = 0;
                        $Total2 = 0;
                        $No = 0;
                        $grand_ttl_qty_ng = 0;
                        foreach ($result as $val => $valx) {
                            if ($valx['qty'] > 0) {
                                $No++;
                                $Noo++;
                                $Total2 += $valx['qty'];

                                $totIn = $valx['qty_order'];

                                // Hasil QC dihitung per baris dt_trans_po (lewat id_detail), supaya tidak dobel
                                // kalau material yang sama ada di lebih dari satu baris dalam 1 PO.
                                $get_qty_ng_oke = $this->db->select('IF(SUM(qty_ng) IS NULL, 0, SUM(qty_ng)) as ttl_qty_ng, IF(SUM(qty_oke) IS NULL, 0, SUM(qty_oke)) as ttl_qty_oke')
                                    ->where('id_detail IN (SELECT id FROM tr_incoming_check_detail WHERE id_po_detail = ' . (int) $valx['id'] . ')', null, false)
                                    ->get('tr_checked_incoming_detail')->row_array();
                                $ttl_qty_ng = $get_qty_ng_oke['ttl_qty_ng'];
                                $ttl_qty_oke = $get_qty_ng_oke['ttl_qty_oke'];

                                // Cek total qty yang sudah pernah di-incoming berdasarkan id_po_detail
                                // agar cocok dengan cara simpan di process_in_product (per id_po_detail).
                                // PENTING: basis sisa qty HARUS dari tr_incoming_check_detail (semua incoming
                                // yang sudah tercatat, apapun status QC-nya), BUKAN dari hasil QC
                                // (tr_checked_incoming_detail). QC bisa baru menyelesaikan sebagian incoming,
                                // sehingga memakai basis QC akan membuat sisa yang ditampilkan lebih besar
                                // dari kenyataan dan berpotensi menyebabkan input incoming dobel/over-qty.
                                $get_qty_incoming_check = $this->db->select('IF(SUM(qty_order) IS NULL, 0, SUM(qty_order)) AS ttl_qty_incoming_check')->get_where('tr_incoming_check_detail', ['id_po_detail' => $valx['id']])->row_array();

                                $ttl_qty_incoming_check = $get_qty_incoming_check['ttl_qty_incoming_check'];

                                if (($valx['qty'] - $ttl_qty_incoming_check) > 0) {
                                    echo "<tr>";
                                    echo "<td align='center'>" . $No . "
                                        <input type='hidden' name='addInMat[$Noo][no_po]' value='" . $valx['no_po'] . "'>
                                        <input type='hidden' name='addInMat[$Noo][id]' value='" . $valx['id'] . "'>
                                        <input type='hidden' name='addInMat[$Noo][qty_order]' value='" . $valx['qty'] . "'>
                                        <input type='hidden' name='addInMat[$Noo][qty_rusak]' data-no='$No' class='form-control input-sm text-right maskM'>
                                        <input type='hidden' name='addInMat[$Noo][expired]' data-no='$No' class='form-control input-sm text-left tanggal' readonly placeholder='Expired Date'>
                                        <input type='hidden' name='addInMat[$Noo][harga]' value=''>
                                        <input type='hidden' name='addInMat[$Noo][qty_sisa]' value='" . ($valx['qty'] - $ttl_qty_incoming_check) . "'>
                                    </td>";
                                    echo "<td>" . $valx['code_product'] . "</td>";
                                    echo "<td>" . $valx['namamaterial'] . "</td>";
                                    echo "<td align='right'>" . number_format($valx['qty'], 2) . "</td>";
                                    echo "<td align='center'>" . $valx['unit_measure'] . "</td>";
                                    echo "<td align='right' class='belumDiterima'>" . number_format(($valx['qty'] - $ttl_qty_incoming_check), 2) . "</td>";
                                    echo "<td align='right' class='belumDiterima'>" . number_format($ttl_qty_ng, 2) . "</td>";
                                    echo "<td align='center'><input type='text' name='addInMat[$Noo][qty_in]' data-no='$No' class='form-control input-sm text-right maskM qtyDiterima'></td>";
                                    echo "<td align='center'><input type='text' name='addInMat[$Noo][keterangan]' data-no='$No' class='form-control input-sm text-left'></td>";
                                    echo "</tr>";

                                    $grand_ttl_qty_ng += $ttl_qty_ng;

                                    $Total1 += ($valx['qty'] - $ttl_qty_incoming_check);
                                }
                            }
                        }
                    ?>
                        <tr>
                            <td><b></b></td>
                            <td colspan='2'><b>SUM TOTAL</b></td>
                            <td align='right'><b><?= number_format($Total2, 2); ?></b></td>
                            <td><b></b></td>
                            <td align='right'><b><?= number_format($Total1, 2); ?></b></td>
                            <td align='right'><b><?= number_format($grand_ttl_qty_ng, 2); ?></b></td>
                            <td colspan='2'><b></b></td>
                        </tr>
                    <?php
                    } else {
                        echo "<tr>";
                        echo "<td colspan='3'>Data aktual belum di update, silahkan update data terlebih dahulu.</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>

        <?php
        }
        ?>

        <div class="row">
            <div class="col-md-3" style="margin-top: 25px;">
                <div class="form-group">
                    <input type="file" name="file_incoming_material[]" id="" class="form-control form-control-sm" multiple>
                </div>
            </div>
        </div>
    </div>
</form>
<style>
    .tanggal {
        cursor: pointer;
    }
</style>
<script>
    $(document).ready(function() {
        swal.close();
        $('.maskM').autoNumeric('init', {
            mDec: '4',
            aPad: false
        });
        $('.tanggal').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });

        $(document).on('change', '.qtyDiterima', function() {
            var ttl_qty_terima = 0;
            $('.qtyDiterima').each(function(index, val) {
                var val = $(this).val();
                var val = val.split(',').join('');
                var val = parseFloat(val);
                // alert(val);

                ttl_qty_terima += val;
            });


        });
    });
</script>