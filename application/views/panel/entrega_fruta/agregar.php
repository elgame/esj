		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/entrega_fruta/'); ?>">Entrega de fruta</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Crear formatos</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/entrega_fruta/agregar'); ?>" method="post" class="form-horizontal" id="formprovee">
						  <fieldset>
								<legend></legend>

								<div class="span3">
									<div class="control-group">
									  <label class="control-label" for="farea">Area </label>
									  <div class="controls">
											<select name="farea" class="span12" id="farea" required>
			                  <option value=""></option>
			                  <?php foreach ($areas['areas'] as $area){ ?>
			                    <option value="<?php echo $area->id_area ?>"
			                      <?php echo set_select('farea', $area->id_area, false, $this->input->get('farea')) ?>><?php echo $area->nombre ?></option>
			                  <?php } ?>
			                </select>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fno_formatos">No. hojas </label>
									  <div class="controls">
											<input type="number" name="fno_formatos" id="fno_formatos" class="span12" value="<?php echo set_value('fno_formatos'); ?>"
												maxlength="7" placeholder="102" required>
											<p class="help-block"></p>
									  </div>
									</div>

									<div class="clearfix"></div>

									<div class="form-actions">
									  <button type="submit" class="btn btn-primary">Guardar</button>
									  <a href="<?php echo base_url('panel/entrega_fruta/'); ?>" class="btn">Cancelar</a>
									</div>

								</div> <!--/span-->

								<div class="span8">
									<iframe src="" id="frame_reporte" name="frame_reporte" style="width: 100%;height: 510px;"></iframe>
								</div> <!--/span-->


						  </fieldset>
						</form>

					</div>
				</div><!--/span-->

			</div><!--/row-->


					<!-- content ends -->
		</div><!--/#content.span10-->



<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
	if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
	$(document).ready(function(){
		noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});

		<?php if (isset($hojas)) {
		?>
			$("#frame_reporte").attr('src', "<?php echo base_url('panel/entrega_fruta/printEntrada?hojas='.$hojas); ?>");
		<?php } ?>
	});
</script>
<?php }
}?>
<!-- Bloque de alertas -->

