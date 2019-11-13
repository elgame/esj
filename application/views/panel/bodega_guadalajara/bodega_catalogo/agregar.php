		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/bodega_catalogo'); ?>">Catalogo bodega guadalajara</a> <span class="divider">/</span>
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
						<form action="<?php echo base_url('panel/bodega_catalogo/agregar'); ?>" method="post" class="form-horizontal" id="form">
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
	                  <label class="control-label" for="dcodigo">*Codigo </label>
	                  <div class="controls">
	                    <input type="text" name="dcodigo" id="dcodigo" value="<?php echo set_value('dcodigo'); ?>" class="input-xlarge" required>
	                  </div>
	                </div>

	                <!-- <div class="control-group">
	                  <label class="control-label" for="did_tipo">Tipo </label>
	                  <div class="controls">
	                    <select name="did_tipo" id="did_tipo" required>
	                   <?php foreach ($t_areas as $key => $value)
	                   { ?>
	                    	<option value="<?php echo $value->id_tipo ?>" <?php echo set_select('did_tipo', $value->id_tipo); ?>><?php echo $value->nombre ?></option>
	                   <?php
	                   } ?>
	                    </select>
	                  </div>
	                </div> -->

								</div> <!--/span -->

								<div class="span4">
	                <div class="control-group">
	                  <label class="control-label" style="width: 100px;">Catalogo </label>
	                  <div class="controls" style="margin-left: 120px;">
	                  	<div style="height: 300px; overflow-y: auto; border:1px #ddd solid;">
	                  		<?php echo $this->bodega_catalogo_model->getFrmAreas(0, true, 'radio', true); ?>
	                    </div>
	                  </div>
	                </div>
	              </div> <!--/span-->

	              <div class="clearfix"></div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/bodega_catalogo/'); ?>" class="btn">Cancelar</a>
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

