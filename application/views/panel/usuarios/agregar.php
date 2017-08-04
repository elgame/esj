		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/usuarios/'); ?>">Usuarios</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-edit"></i> Agregar usuario</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/usuarios/agregar'); ?>" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<div class="span7">
									<div class="control-group">
									  <label class="control-label" for="fnombre">Nombre </label>
									  <div class="controls">
											<input type="text" name="fnombre" id="fnombre" class="span6" value="<?php echo set_value('fnombre'); ?>" maxlength="90" placeholder="Nombre" autofocus required>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fapellido_paterno">Apellido paterno </label>
									  <div class="controls">
											<input type="text" name="fapellido_paterno" id="fapellido_paterno" class="span6" value="<?php echo set_value('fapellido_paterno'); ?>" maxlength="25" placeholder="Apellido paterno" >
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fapellido_materno">Apellido materno </label>
									  <div class="controls">
											<input type="text" name="fapellido_materno" id="fapellido_materno" class="span6" value="<?php echo set_value('fapellido_materno'); ?>" maxlength="25" placeholder="Apellido materno" >
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fusuario">Usuario </label>
									  <div class="controls">
											<input type="text" name="fusuario" id="fusuario" class="span6" value="<?php echo set_value('fusuario'); ?>" maxlength="30" placeholder="admin">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fpass">Password </label>
									  <div class="controls">
									  	<input type="password" name="fpass" id="fpass" class="span6" value="<?php echo set_value('fpass'); ?>" maxlength="32" placeholder="********">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="festa_asegurado">Esta asegurado? </label>
									  <div class="controls">
									  	<input type="checkbox" name="festa_asegurado" id="festa_asegurado" value="t" data-uniform="false" <?php echo set_checkbox('festa_asegurado', 't'); ?>>
									  </div>
									</div>

									<fieldset>
										<legend>Domicilio</legend>
										<div class="control-group">
										  <label class="control-label" for="fcalle">Calle </label>
										  <div class="controls">
												<input type="text" name="fcalle" id="fcalle" class="span6" value="<?php echo set_value('fcalle'); ?>" maxlength="60" placeholder="calle">
										  </div>
										</div>

										<div class="control-group">
										  <label class="control-label" for="fnumero">Numero </label>
										  <div class="controls">
												<input type="text" name="fnumero" id="fnumero" class="span6" value="<?php echo set_value('fnumero'); ?>" maxlength="7" placeholder="numero">
										  </div>
										</div>

										<div class="control-group">
										  <label class="control-label" for="fcolonia">Colonia </label>
										  <div class="controls">
												<input type="text" name="fcolonia" id="fcolonia" class="span6" value="<?php echo set_value('fcolonia'); ?>" maxlength="60" placeholder="colonia">
										  </div>
										</div>

										<div class="control-group">
										  <label class="control-label" for="fmunicipio">Municipio </label>
										  <div class="controls">
												<input type="text" name="fmunicipio" id="fmunicipio" class="span6" value="<?php echo set_value('fmunicipio'); ?>" maxlength="45" placeholder="municipio">
										  </div>
										</div>

										<div class="control-group">
										  <label class="control-label" for="festado">Estado </label>
										  <div class="controls">
												<input type="text" name="festado" id="festado" class="span6" value="<?php echo set_value('festado'); ?>" maxlength="45" placeholder="estado">
										  </div>
										</div>

										<div class="control-group">
										  <label class="control-label" for="fcp">Codigo Postal </label>
										  <div class="controls">
												<input type="text" name="fcp" id="fcp" class="span6" value="<?php echo set_value('fcp'); ?>" maxlength="12" placeholder="codigo postal">
										  </div>
										</div>
									</fieldset>

									<fieldset>
										<legend>Otros</legend>

										<div class="control-group">
										  <label class="control-label" for="ffecha_nacimiento">Fecha de nacimiento </label>
										  <div class="controls">
												<input type="date" name="ffecha_nacimiento" id="ffecha_nacimiento" class="span6" value="<?php echo set_value('ffecha_nacimiento'); ?>" maxlength="25" placeholder="Fecha de nacimiento">
										  </div>
										</div>

										<div class="control-group">
										  <label class="control-label" for="ffecha_entrada">Fecha de entrada </label>
										  <div class="controls">
												<input type="date" name="ffecha_entrada" id="ffecha_entrada" class="span6" value="<?php echo set_value('ffecha_entrada', date('Y-m-d')); ?>" maxlength="25" placeholder="Fecha de entrada">
										  </div>
										</div>

                    <div class="control-group">
                      <label class="control-label" for="ffecha_imss">Fecha Alta IMSS </label>
                      <div class="controls">
                        <input type="date" name="ffecha_imss" id="ffecha_imss" class="span6" value="<?php echo set_value('ffecha_imss', date('Y-m-d')); ?>" maxlength="25" placeholder="Fecha IMSS">
                      </div>
                    </div>

										<div class="control-group">
										  <label class="control-label" for="ffecha_salida">Fecha de salida </label>
										  <div class="controls">
												<input type="date" name="ffecha_salida" id="ffecha_salida" class="span6" value="<?php echo set_value('ffecha_salida'); ?>" maxlength="25" placeholder="Fecha de salida">
										  </div>
										</div>

										<div class="control-group">
										  <label class="control-label" for="fnacionalidad">Nacionalidad </label>
										  <div class="controls">
												<input type="text" name="fnacionalidad" id="fnacionalidad" class="span6" value="<?php echo set_value('fnacionalidad', 'Mexicana'); ?>" maxlength="20" placeholder="Nacionalidad">
										  </div>
										</div>

										<div class="control-group">
										  <label class="control-label" for="festado_civil">Estado civil </label>
										  <div class="controls">
										  	<select name="festado_civil" id="festado_civil">
													<option value="Soltero" <?php echo set_select('festado_civil', 'Soltero', false, $this->input->post('festado_civil')); ?>>Soltero</option>
													<option value="Casado" <?php echo set_select('festado_civil', 'Casado', false, $this->input->post('festado_civil')); ?>>Casado</option>
													<option value="Divorciado" <?php echo set_select('festado_civil', 'Divorciado', false, $this->input->post('festado_civil')); ?>>Divorciado</option>
													<option value="Viudo" <?php echo set_select('festado_civil', 'Viudo', false, $this->input->post('festado_civil')); ?>>Viudo</option>
													<option value="Union libre" <?php echo set_select('festado_civil', 'Union libre', false, $this->input->post('festado_civil')); ?>>Union libre</option>
												</select>
										  </div>
										</div>

										<div class="control-group tipo3">
										  <label class="control-label" for="fsexo">Sexo </label>
										  <div class="controls">
												<select name="fsexo" id="fsexo">
													<option value="h" <?php echo set_select('fsexo', 'h', false, $this->input->post('fsexo')); ?>>Masculino</option>
													<option value="m" <?php echo set_select('fsexo', 'm', false, $this->input->post('fsexo')); ?>>Femenino</option>
												</select>
										  </div>
										</div>

										<div class="control-group">
										  <label class="control-label" for="femail">Email </label>
										  <div class="controls">
												<input type="text" name="femail" id="femail" class="span6" value="<?php echo set_value('femail'); ?>" maxlength="70" placeholder="correo@gmail.com">
										  </div>
										</div>

										<div class="control-group">
										  <label class="control-label" for="fcuenta_cpi">Cuenta contpaq </label>
										  <div class="controls">
												<input type="text" name="fcuenta_cpi" id="fcuenta_cpi" class="span6" value="<?php echo set_value('fcuenta_cpi'); ?>" maxlength="12" placeholder="Cuenta contpaq">
										  </div>
										</div>

									</fieldset>

								</div> <!--/span-->

								<div class="span4">
									<div class="control-group">
									  <label class="control-label" style="width: 100px;">Privilegios </label>
									  <div class="controls" style="margin-left: 120px;">
                      <select name="id_empresa">
                      <?php foreach ($empresas['empresas'] as $key => $empresa) { ?>
                        <option value="<?php echo $empresa->id_empresa ?>" <?php echo set_select('id_empresa', $empresa->id_empresa, false, $this->input->post('id_empresa')); ?>><?php echo $empresa->nombre_fiscal ?></option>
                      <?php } ?>
                      </select>
									  	<div id="list_privilegios" style="height: 500px; overflow-y: auto; border:1px #ddd solid;">
									  		<?php
												if($this->usuarios_model->tienePrivilegioDe('', 'privilegios/index/')){
													echo $this->usuarios_model->getFrmPrivilegios(0, true);
												}
											?>
									    </div>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="frfc">RFC </label>
									  <div class="controls">
											<input type="text" name="frfc" id="frfc" class="span12" value="<?php echo set_value('frfc'); ?>" pattern=".{12,13}" title="12 o 13 caracteres" placeholder="RFC">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fcurp">CURP </label>
									  <div class="controls">
											<input type="text" name="fcurp" id="fcurp" class="span12" value="<?php echo set_value('fcurp'); ?>" maxlength="30" placeholder="CURP">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fempresa">Empresa </label>
									  <div class="controls">
										<input type="text" name="fempresa" id="fempresa" class="span12" value="<?php echo set_value('fempresa'); ?>" placeholder="Nombre" required>
										<input type="hidden" name="did_empresa" value="<?php echo set_value('did_empresa'); ?>" id="did_empresa" required>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fpuesto">Puesto </label>
									  <div class="controls">
										<select name="fpuesto" id="fpuesto">
										<?php foreach ($puestos['puestos'] as $key => $value)
										{
										?>
											<option value="<?php echo $value->id_puesto ?>" <?php echo set_select('fpuesto', $value->id_puesto, false, $this->input->post('fpuesto')); ?>><?php echo $value->nombre." ({$value->abreviatura})" ?></option>
										<?php
										} ?>
										</select>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fsalario_diario">Salario diario </label>
									  <div class="controls">
											<input type="text" name="fsalario_diario" id="fsalario_diario" class="span12 vpositive" value="<?php echo set_value('fsalario_diario'); ?>" maxlength="12" placeholder="Salario de nomina fiscal">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fsalario_diario_real">Salario diario real </label>
									  <div class="controls">
											<input type="text" name="fsalario_diario_real" id="fsalario_diario_real" class="span12 vpositive" value="<?php echo set_value('fsalario_diario_real'); ?>" maxlength="12" placeholder="Salario de nomina real">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="finfonavit">Infonavit </label>
									  <div class="controls">
											<input type="text" name="finfonavit" id="finfonavit" class="span12 vpositive" value="<?php echo set_value('finfonavit'); ?>" maxlength="12" placeholder="Infonavit">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fregimen_contratacion">Regimen contratacion </label>
									  <div class="controls">
										<select name="fregimen_contratacion" id="fregimen_contratacion">
											<option value="2" <?php echo set_select('fregimen_contratacion', '2', false, $this->input->post('fregimen_contratacion')); ?>>Sueldos y salarios</option>
											<option value="3" <?php echo set_select('fregimen_contratacion', '3', false, $this->input->post('fregimen_contratacion')); ?>>Jubilados</option>
											<option value="4" <?php echo set_select('fregimen_contratacion', '4', false, $this->input->post('fregimen_contratacion')); ?>>Pensionados</option>
										</select>
									  </div>
									</div>

								</div> <!--/span-->

	              <div class="clearfix"></div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/usuarios/'); ?>" class="btn">Cancelar</a>
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

