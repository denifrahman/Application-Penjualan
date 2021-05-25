<?php echo form_open_multipart('barang/edit/' . $barang->id_barang, array('id' => 'submit')); ?>
<div class="form-horizontal">
	<div class="form-group">
		<label class="col-sm-3 control-label">Kode Barang</label>
		<div class="col-sm-8">
			<?php
			echo form_input(array(
				'name' => 'kode_barang',
				'class' => 'form-control',
				'value' => $barang->kode_barang
			));
			echo form_hidden('kode_barang_old', $barang->kode_barang);
			?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label">Nama Barang</label>
		<div class="col-sm-8">
			<?php
			echo form_input(array(
				'name' => 'nama_barang',
				'class' => 'form-control',
				'value' => $barang->nama_barang
			));
			?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label">Kategori</label>
		<div class="col-sm-8">
			<select name='id_kategori_barang' class='form-control'>
				<option value=''></option>
				<?php
				foreach ($kategori->result() as $k) {
					$selected = '';
					if ($barang->id_kategori_barang == $k->id_kategori_barang) {
						$selected = 'selected';
					}

					echo "<option value='" . $k->id_kategori_barang . "' " . $selected . ">" . $k->kategori . "</option>";
				}
				?>
			</select>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label">Merek</label>
		<div class="col-sm-8">
			<select name='id_merk_barang' class='form-control'>
				<option value=''></option>
				<?php
				foreach ($merek->result() as $m) {
					$selected = '';
					if ($barang->id_merk_barang == $m->id_merk_barang) {
						$selected = 'selected';
					}

					echo "<option value='" . $m->id_merk_barang . "' " . $selected . ">" . $m->merk . "</option>";
				}
				?>
			</select>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label">Stok</label>
		<div class="col-sm-8">
			<?php
			echo form_input(array(
				'name' => 'total_stok',
				'class' => 'form-control',
				'value' => $barang->total_stok
			));
			?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label">Harga</label>
		<div class="col-sm-8">
			<?php
			echo form_input(array(
				'name' => 'harga',
				'class' => 'form-control',
				'value' => $barang->harga
			));
			?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label">Sampul</label>
		<div class="col-sm-8">
			<input type="file" name='foto_sampul' id="foto_sampul" size="20" />
			<?= form_hidden('foto_sampul', $barang->foto_sampul); ?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label">Foto 1</label>
		<div class="col-sm-8">
			<input type="file" name='foto_1' id="foto_1" size="20"   />
			<?= form_hidden('foto_1', $barang->foto_1); ?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label">Foto 2</label>
		<div class="col-sm-8">
			<input type="file" name='foto_2' size="20" id="foto_2" />
			<?= form_hidden('foto_2', $barang->foto_2); ?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label">Foto 3</label>
		<div class="col-sm-8">
			<input type="file" name='foto_3' id="foto_3" size="20" />
			<?= form_hidden('foto_3', $barang->foto_3); ?>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-3 control-label">Keterangan</label>
		<div class="col-sm-8">
			<textarea name='keterangan' class='form-control' rows='3' style='resize:vertical;'><?php echo $barang->keterangan; ?></textarea>
		</div>
	</div>
</div>
<div id='ResponseInput'></div>
<button type='submit' class='btn btn-primary' id='submit'>Update Data</button>
<button type='button' class='btn btn-default' data-dismiss='modal'>Tutup</button>
<?php echo form_close(); ?>

<script>
	$(document).ready(function() {
		$('#submit').submit(function(e) {
			// ("#foto_3").val('');
			e.preventDefault();
			$.ajax({
				url: 'barang/edit/' + '<?= $barang->id_barang ?> ',
				type: "POST",
				data: new FormData(this),
				processData: false,
				contentType: false,
				cache: false,
				async: false,
				dataType: 'json',
				success: function(json) {
					if (json.status == 1) {
						$('#ResponseInput').html(json.pesan);
						setTimeout(function() {
							$('#ResponseInput').html('');
						}, 3000);
						$('#my-grid').DataTable().ajax.reload(null, false);
					} else {
						$('#ResponseInput').html(json.pesan);
					}
				}
			});
		});
	});

	// function getFileNameWithExt(event, param) {
	// 	var timestamp = new Date().getTime();
	// 	if (!event || !event.target || !event.target.files || event.target.files.length === 0) {
	// 		return;
	// 	}
	// 	const name = event.target.files[0].name;
	// 	const lastDot = name.lastIndexOf('.');
	// 	const fileName = name.substring(0, lastDot);
	// 	const ext = name.substring(lastDot + 1);
	// 	// alert(param + '_' + timestamp + '.' + ext);
	// 	// $("#"+param).val(param + '_' + timestamp + '.' + ext);
	// 	document.getElementById("foto_sampul").value = "Johnny Bravo";
	// }
</script>