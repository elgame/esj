		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/ranchos/'); ?>">Áreas</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar Área</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/ranchos/'.(isset($method)? $method: 'agregar') ); ?>" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<div class="span12">
                  <div class="control-group">
                    <label class="control-label" for="fempresa">Empresa </label>
                    <div class="controls">
                    <input type="text" name="fempresa" id="fempresa" class="span10" value="<?php echo set_value('fempresa', $empresa->nombre_fiscal); ?>" placeholder="Nombre" autofocus>
                    <input type="hidden" name="did_empresa" value="<?php echo set_value('did_empresa', $empresa->id_empresa); ?>" id="did_empresa">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="farea">Cultivo </label>
                    <div class="controls">
                    <input type="text" name="farea" id="farea" class="span10" value="<?php echo set_value('farea'); ?>" placeholder="Limon, Piña">
                    <input type="hidden" name="did_area" value="<?php echo set_value('did_area'); ?>" id="did_area">
                    </div>
                  </div>

									<div class="control-group">
									  <label class="control-label" for="nombre">Nombre </label>
									  <div class="controls">
											<input type="text" name="nombre" id="nombre" class="span10" maxlength="140"
											value="<?php echo set_value('nombre'); ?>" required placeholder="Rancho">
									  </div>
									</div>

								</div> <!--/span-->

	              <div class="clearfix"></div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/ranchos/'); ?>" class="btn">Cancelar</a>
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

