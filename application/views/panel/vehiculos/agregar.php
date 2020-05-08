		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/vehiculos/'); ?>">Vehiculos</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar vehiculo</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/vehiculos/agregar'); ?>" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<div class="control-group">
								  <label class="control-label" for="fplacas">Placas </label>
								  <div class="controls">
										<input type="text" name="fplacas" id="fplacas" class="span6" maxlength="15"
										value="<?php echo set_value('fplacas'); ?>" required autofocus placeholder="JHS2312, MJF332J">
								  </div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="fmodelo">Modelo </label>
								  <div class="controls">
										<input type="text" name="fmodelo" id="fmodelo" class="span6" value="<?php echo set_value('fmodelo'); ?>"
											maxlength="15" placeholder="1990, 2000">
								  </div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="fmarca">Marca </label>
								  <div class="controls">
										<input type="text" name="fmarca" id="fmarca" class="span6" value="<?php echo set_value('fmarca'); ?>"
											maxlength="15" placeholder="Chevrolet, Toyota">
								  </div>
								</div>

                <div class="control-group">
                  <label class="control-label" for="fcolor">Color </label>
                  <div class="controls">
                    <input type="text" name="fcolor" id="fcolor" class="span6" value="<?php echo set_value('fcolor'); ?>"
                      maxlength="15" placeholder="Amarillo, Azul, Negro">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="funidad">Unidad de medida </label>
                  <div class="controls">
                    <select name="funidad" id="funidad">
                      <option value="km" <?php echo set_select('funidad', 'km') ?>>Km</option>
                      <option value="mi" <?php echo set_select('funidad', 'mi') ?>>Millas</option>
                    </select>
                  </div>
                </div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/vehiculos/'); ?>" class="btn">Cancelar</a>
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

