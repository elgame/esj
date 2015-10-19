		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/catalogos_sft/cat_codigos'); ?>">Catalogo codigos</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-edit"></i> Agregar</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/catalogos_sft/agregar_codigos'); ?>" method="post" class="form-horizontal" id="form">
						  <fieldset>
								<legend></legend>

								<div class="span7">
	                <div class="control-group">
	                  <label class="control-label" for="dnombre">*Nombre </label>
	                  <div class="controls">
											<input type="text" name="dnombre" id="dnombre" value="<?php echo set_value('dnombre'); ?>" class="input-xlarge" autofocus required>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dcodigo">Codigo </label>
	                  <div class="controls">
	                    <input type="text" name="dcodigo" id="dcodigo" value="<?php echo set_value('dcodigo'); ?>" class="input-xlarge">
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="ddescripcion">Descripcion </label>
	                  <div class="controls">
	                    <input type="text" name="ddescripcion" id="ddescripcion" value="<?php echo set_value('ddescripcion'); ?>" class="input-xlarge">
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dubicacion">Ubicacion </label>
	                  <div class="controls">
	                    <input type="text" name="dubicacion" id="dubicacion" value="<?php echo set_value('dubicacion'); ?>" class="input-xlarge">
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dotro_dato">Otro dato </label>
	                  <div class="controls">
	                    <input type="text" name="dotro_dato" id="dotro_dato" value="<?php echo set_value('dotro_dato'); ?>" class="input-xlarge">
	                  </div>
	                </div>

								</div> <!--/span -->

								<div class="span4">
	                <div class="control-group">
	                  <label class="control-label" style="width: 100px;">Catalogo </label>
	                  <div class="controls" style="margin-left: 120px;">
	                  	<div style="height: 300px; overflow-y: auto; border:1px #ddd solid;">
	                  		<?php echo $this->catalogos_sft_model->getFrmCatCodigos(0, true, 'radio', true); ?>
	                    </div>
	                  </div>
	                </div>
	              </div> <!--/span-->

	              <div class="clearfix"></div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/catalogos_sft/cat_codigos/'); ?>" class="btn">Cancelar</a>
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

