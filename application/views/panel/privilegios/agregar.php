		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/privilegios'); ?>">Privilegios</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-edit"></i> Agregar Privilegio</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/privilegios/agregar'); ?>" method="post" class="form-horizontal" id="form">
						  <fieldset>
								<legend></legend>

								<div class="span7">
	                <div class="control-group">
	                  <label class="control-label" for="dnombre">*Nombre </label>
	                  <div class="controls">
											<input type="text" name="dnombre" id="dnombre" value="<?php echo set_value('dnombre'); ?>" class="input-xlarge" autofocus>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="durl_accion">*Url accion </label>
	                  <div class="controls">
	                    <input type="text" name="durl_accion" id="durl_accion" value="<?php echo set_value('durl_accion'); ?>" class="input-xlarge" >
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="durl_icono">*icono </label>
	                  <div class="controls">
	                    <input type="text" name="durl_icono" id="durl_icono" class="input-large" value="<?php echo set_value('durl_icono'); ?>">
	                    <p class="help-block">Iconos de Bootstrap</p>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dmostrar_menu">Mostrar menu </label>
	                  <div class="controls">
	                    <input type="checkbox" name="dmostrar_menu" id="dmostrar_menu" value="si" <?php echo set_checkbox('dmostrar_menu', 'si'); ?>>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dtarget_blank">Target blank</label>
	                  <div class="controls">
	                    <input type="checkbox" name="dtarget_blank" id="dtarget_blank" value="si" <?php echo set_checkbox('dtarget_blank', 'si'); ?>>
	                  </div>
	                </div>
								</div> <!--/span -->

								<div class="span4">
	                <div class="control-group">
	                  <label class="control-label" style="width: 100px;">Privilegios </label>
	                  <div class="controls" style="margin-left: 120px;">
	                  	<div style="height: 300px; overflow-y: auto; border:1px #ddd solid;">
	                  		<?php echo $this->usuarios_model->getFrmPrivilegios(0, true, 'radio', true); ?>
	                    </div>
	                  </div>
	                </div>
	              </div> <!--/span-->

	              <div class="clearfix"></div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/privilegios/'); ?>" class="btn">Cancelar</a>
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

