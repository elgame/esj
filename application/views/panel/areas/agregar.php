		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/areas/'); ?>">Areas</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<form action="<?php echo base_url('panel/areas/agregar'); ?>" method="post" class="form-horizontal">
				<div class="row-fluid">

					<div class="box span12">
						<div class="box-header well" data-original-title>
							<h2><i class="icon-plus"></i> Datos area</h2>
							<div class="box-icon">
								<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
							</div>
						</div>
						<div class="box-content">

							<div class="control-group">
							  <label class="control-label" for="fnombre">Nombre </label>
							  <div class="controls">
									<input type="text" name="fnombre" id="fnombre" class="span6" maxlength="140" 
									value="<?php echo set_value('fnombre'); ?>" required autofocus placeholder="Limon, Piña, Insumo">
							  </div>
							</div>

							<div class="control-group tipo3">
							  <label class="control-label" for="ftipo">Tipo de proveedor </label>
							  <div class="controls">
									<select name="ftipo" id="ftipo">
										<option value="fr" <?php echo set_select('ftipo', 'fr', false, $this->input->post('ftipo')); ?>>Fruta</option>
										<option value="in" <?php echo set_select('ftipo', 'in', false, $this->input->post('ftipo')); ?>>Insumos</option>
									</select>
							  </div>
							</div>

						</div>
					</div><!--/span-->

				</div><!--/row-->

				<div class="row-fluid">

					<div class="box span12">
						<div class="box-header well" data-original-title>
							<h2><i class="icon-leaf"></i> Calidades / Clasificaciones</h2>
							<div class="box-icon">
								<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
							</div>
						</div>
						<div class="box-content">
							<fieldset class="span6">
								<legend>Calidades</legend>

								<div class="row-fluid">
									<p class="span5">
										<input type="text" name="acal_nombre" id="acal_nombre" class="span11" maxlength="40" placeholder="Nombre de la calidad">
									</p>
									<p class="span5">
										<input type="text" name="acal_precio" id="acal_precio" class="span11 vpositive" maxlength="11" placeholder="Precio de compra">
									</p>
									<button type="button" id="acal_agregar" class="btn">Agregar</button>
								</div>

								<table class="table table-striped table-bordered bootstrap-datatable">
								  <thead>
									  <tr>
									  	<th>Nombre</th>
											<th>Precio</th>
											<th>Opc</th>
									  </tr>
								  </thead>
								  <tbody id="acal_body">
								 <?php 
								 if (is_array($this->input->post('cal_nombre'))) {
								 	foreach ($this->input->post('cal_nombre') as $key => $value) {
								  ?>
								  	<tr id="acal_r<?php echo $key; ?>">
									  	<td><input type="text" class="span12" name="cal_nombre[]" value="<?php echo $value; ?>" maxlength="40" required></td>
											<td><input type="text" class="span8 vpositive" name="cal_precio[]" value="<?php echo $_POST['cal_precio'][$key]; ?>" maxlength="11" required></td>
											<td><button type="button" class="btn btn-danger cal_remove" data-row="<?php echo $key; ?>"><i class="icon-remove"></i></button></td>
									  </tr>
								 <?php 
								 	}
								 } ?>
								  </tbody>
								</table>
							</fieldset>

							<fieldset class="span6">
								<legend>Clasificaciones</legend>

								<div class="row-fluid">
									<p class="span4">
										<input type="text" name="acla_nombre" id="acla_nombre" class="span11" maxlength="40" placeholder="Nombre de la calidad">
									</p>
									<p class="span3">
										<input type="text" name="acla_precio" id="acla_precio" class="span11 vpositive" maxlength="11" placeholder="Precio de venta">
									</p>
									<p class="span3">
										<input type="text" name="acla_cuenta" id="acla_cuenta" class="span11 vpositive" maxlength="12" placeholder="Cuenta contpaqi">
									</p>
									<button type="button" id="acla_agregar" class="btn">Agregar</button>
								</div>

								<table class="table table-striped table-bordered bootstrap-datatable">
								  <thead>
									  <tr>
									  	<th>Nombre</th>
											<th>Precio</th>
											<th>Cuenta</th>
											<th>Opc</th>
									  </tr>
								  </thead>
								  <tbody id="acla_body">
								 <?php 
								 if (is_array($this->input->post('cla_nombre'))) {
								 	foreach ($this->input->post('cla_nombre') as $key => $value) {
								  ?>
								  	<tr id="acla_r<?php echo $key; ?>">
									  	<td><input type="text" class="span12" name="cla_nombre[]" value="<?php echo $value; ?>" maxlength="40" required></td>
											<td><input type="text" class="span8 vpositive" name="cla_precio[]" value="<?php echo $_POST['cla_precio'][$key]; ?>" maxlength="11" required></td>
											<td><input type="text" class="span8 vpositive" name="cla_cuenta[]" value="<?php echo $_POST['cla_cuenta'][$key]; ?>" maxlength="12"></td>
											<td><button type="button" class="btn btn-danger cla_remove" data-row="<?php echo $key; ?>"><i class="icon-remove"></i></button></td>
									  </tr>
								 <?php 
								 	}
								 } ?>
								  </tbody>
								</table>
							</fieldset>

						</div>
					</div><!--/span-->

				</div><!--/row-->

				<div class="form-actions">
				  <button type="submit" class="btn btn-primary">Guardar</button>
				  <a href="<?php echo base_url('panel/areas/'); ?>" class="btn">Cancelar</a>
				</div>
			</form>


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

