		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/control_acceso/'); ?>">Control de acceso</a> <span class="divider">/</span>
					</li>
					<li>Registro</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Registro</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
					  <fieldset>
							<legend>
								<form action="<?php echo base_url('panel/control_acceso/entrada_salida'); ?>" method="get" class="form-search">
									<label for="placas">Placas</label>
									<input type="text" name="placas" id="placas" value="<?php echo set_value_get('placas'); ?>"
										class="input-large search-query" autofocus>
								</form>
							</legend>

							<form action="<?php echo base_url('panel/control_acceso/entrada_salida'); ?>" method="post" class="form-horizontal">
								<div class="span6">
									<input type="hidden" name="tipo" value="<?php echo $tipo ?>">
									<input type="hidden" name="id_control" value="<?php echo isset($data->id_control)?$data->id_control: ''; ?>">

									<div class="control-group">
									  <label class="control-label" for="nombre">Nombre </label>
									  <div class="controls">
											<input type="text" name="nombre" id="nombre" class="span10" maxlength="200"
												value="<?php echo isset($data->nombre)?$data->nombre: ''; ?>" required <?php echo $readonly ?>>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="asunto">Asunto </label>
									  <div class="controls">
											<input type="text" name="asunto" id="asunto" class="span10" maxlength="200"
												value="<?php echo isset($data->asunto)?$data->asunto: ''; ?>" required <?php echo $readonly ?>>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="departamento">Departamento </label>
									  <div class="controls">
											<input type="text" name="departamento" id="departamento" class="span10" maxlength="60"
												value="<?php echo isset($data->departamento)?$data->departamento: ''; ?>" required <?php echo $readonly ?>>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="placas">Placas </label>
									  <div class="controls">
											<input type="text" name="placas" id="placas" class="span10" maxlength="15" required
												value="<?php echo isset($data->placas)?$data->placas: ''; ?>" <?php echo $readonly ?>>
									  </div>
									</div>

								<?php if (isset($data->id_control)) {
								?>
									<div class="control-group">
									  <label class="control-label" for="id_vale_salida">Vale de salida </label>
									  <div class="controls">
											<input type="text" name="id_vale_salida" id="id_vale_salida" class="span12" maxlength="15"
												value="<?php echo isset($data->id_vale_salida)?$data->id_vale_salida: ''; ?>" <?php echo $readonly_v ?>>
									  </div>
									</div>
								<?php
								} ?>

								</div> <!--/span-->

								<div class="span5">
									<div class="control-group">
									  <label class="control-label" for="id_usaurio_ent">Registro entrada </label>
									  <div class="controls">
									  	<?php echo isset($data->usuario_entrada)? $data->usuario_entrada: $this->session->userdata('usuario'); ?>
											<input type="hidden" name="id_usaurio_ent" id="id_usaurio_ent"
											value="<?php echo isset($data->id_usaurio_ent)?$data->id_usaurio_ent: $this->session->userdata('id_usuario'); ?>" required>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fecha_entrada">Fecha de entrada </label>
									  <div class="controls">
											<input type="datetime" name="fecha_entrada" id="fecha_entrada" readonly
											value="<?php echo isset($data->fecha_entrada)?$data->fecha_entrada: date("Y-m-d H:i"); ?>" required>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="id_usuario_sal">Registro salida</label>
									  <div class="controls">
									  	<?php
									  		$usuario_salida = '';
									  		if (isset($data->id_control)) {
									  			echo isset($data->usuario_salida)? $data->usuario_salida: $this->session->userdata('usuario');
									  			$usuario_salida = $this->session->userdata('id_usuario');
									  		}
									  	?>
											<input type="hidden" name="id_usuario_sal" id="id_usuario_sal"
											value="<?php echo isset($data->id_usuario_sal)?$data->id_usuario_sal: $usuario_salida; ?>" required>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fecha_salida">Fecha de entrada </label>
									  <div class="controls">
									  <?php if (isset($data->id_control)) { ?>
											<input type="datetime" name="fecha_salida" id="fecha_salida" readonly
											value="<?php echo isset($data->fecha_salida)?$data->fecha_salida: date("Y-m-d H:i"); ?>" required>
										<?php } ?>
									  </div>
									</div>

	              </div> <!--/span-->

	              <div class="clearfix"></div>


								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/control_acceso/entrada_salida/'); ?>" class="btn btn-info">Nueva</a>
								  <a href="<?php echo base_url('panel/control_acceso/'); ?>" class="btn">Cancelar</a>
								</div>
							</form>
					  </fieldset>

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

