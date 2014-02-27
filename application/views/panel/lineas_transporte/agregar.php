		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/lineas_transporte/'); ?>">Lineas transporte</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar linea</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/lineas_transporte/agregar'); ?>" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<div class="control-group">
								  <label class="control-label" for="fnombre">Nombre </label>
								  <div class="controls">
										<input type="text" name="fnombre" id="fnombre" class="span6" maxlength="100"
										value="<?php echo set_value('fnombre'); ?>" required autofocus placeholder="Linea del Sur">
								  </div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="ftelefonos">Telefonos </label>
								  <div class="controls">
										<input type="text" name="ftelefonos" id="ftelefonos" class="span6" value="<?php echo set_value('ftelefonos'); ?>"
											maxlength="70" placeholder="3122356, 31356256">
								  </div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="fid">ID </label>
								  <div class="controls">
										<input type="text" name="fid" id="fid" class="span6" value="<?php echo set_value('fid'); ?>"
											maxlength="30" placeholder="323*66556*65">
								  </div>
								</div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/lineas_transporte/'); ?>" class="btn">Cancelar</a>
								</div>
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
	});
</script>
<?php }
}?>
<!-- Bloque de alertas -->

