		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/proveedores/'); ?>">Proveedores</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar proveedor</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/proveedores/agregar'); ?>" method="post" class="form-horizontal" id="formprovee" enctype="multipart/form-data">
						  <fieldset>
								<legend></legend>

								<div class="span6">
									<div class="control-group">
									  <label class="control-label" for="fnombre_fiscal">Nombre fiscal </label>
									  <div class="controls">
											<input type="text" name="fnombre_fiscal" id="fnombre_fiscal" class="span10" maxlength="140" 
											value="<?php echo set_value('fnombre_fiscal'); ?>" required autofocus placeholder="GAS MENGUC SA DE CV, MORA NARANJO ALFREDO">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="frfc">RFC </label>
									  <div class="controls">
											<input type="text" name="frfc" id="frfc" class="span12" value="<?php echo set_value('frfc'); ?>" 
												maxlength="13" placeholder="MPE050528A58, SFM00061515A">
									  </div>
									</div>

									<div class="control-group tipo3">
									  <label class="control-label" for="ftipo_proveedor">Tipo de proveedor </label>
									  <div class="controls">
											<select name="ftipo_proveedor" id="ftipo_proveedor">
												<option value="in" <?php echo set_select('ftipo_proveedor', 'in', false, $this->input->post('ftipo_proveedor')); ?>>Insumos</option>
												<option value="fr" <?php echo set_select('ftipo_proveedor', 'fr', false, $this->input->post('ftipo_proveedor')); ?>>Fruta</option>
												<option value="ot" <?php echo set_select('ftipo_proveedor', 'ot', false, $this->input->post('ftipo_proveedor')); ?>>Otros</option>
											</select>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fcalle">Calle </label>
									  <div class="controls">
											<input type="text" name="fcalle" id="fcalle" class="span10" value="<?php echo set_value('fcalle'); ?>" 
												maxlength="60" placeholder="PRIVADA SAN MARINO, 5 DE MAYO">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fno_exterior">No. exterior </label>
									  <div class="controls">
											<input type="text" name="fno_exterior" id="fno_exterior" class="span10" value="<?php echo set_value('fno_exterior'); ?>" 
												maxlength="7" placeholder="102, S/N">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fno_interior">No. interior </label>
									  <div class="controls">
											<input type="text" name="fno_interior" id="fno_interior" class="span10" value="<?php echo set_value('fno_interior'); ?>" 
												maxlength="7" placeholder="102, S/N">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fcolonia">Colonia </label>
									  <div class="controls">
											<input type="text" name="fcolonia" id="fcolonia" class="span10" value="<?php echo set_value('fcolonia'); ?>" 
												maxlength="60" placeholder="Juan Jose Rios, 3ra Cocoteros">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="flocalidad">Localidad </label>
									  <div class="controls">
											<input type="text" name="flocalidad" id="flocalidad" class="span10" value="<?php echo set_value('flocalidad'); ?>" 
												maxlength="45" placeholder="Cerro de ortega, Ranchito">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fmunicipio">Municipio </label>
									  <div class="controls">
											<input type="text" name="fmunicipio" id="fmunicipio" class="span10" value="<?php echo set_value('fmunicipio'); ?>" 
												maxlength="45" placeholder="Tecoman, Armeria">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="festado">Estado </label>
									  <div class="controls">
											<input type="text" name="festado" id="festado" class="span10" value="<?php echo set_value('festado'); ?>" 
												maxlength="45" placeholder="Colima, Jalisco">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fcp">CP </label>
									  <div class="controls">
											<input type="text" name="fcp" id="fcp" class="span10" value="<?php echo set_value('fcp'); ?>" 
												maxlength="10" placeholder="28084, 28000">
									  </div>
									</div>
								</div> <!--/span-->

								<div class="span5">
									<div class="control-group">
										<label class="control-label" for="dregimen_fiscal">Régimen fiscal:</label>
										<div class="controls">
											<input type="text" name="dregimen_fiscal" id="dregimen_fiscal" class="span12" value="<?php echo set_value('dregimen_fiscal'); ?>" maxlength="100">
										</div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fcurp">CURP </label>
									  <div class="controls">
											<input type="text" name="fcurp" id="fcurp" class="span12" value="<?php echo set_value('fcurp'); ?>" 
												maxlength="10" placeholder="IIML781216MCMXNS02, MONA731117HMNRRL05">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="ftelefono">Telefono </label>
									  <div class="controls">
											<input type="text" name="ftelefono" id="ftelefono" class="span12" value="<?php echo set_value('ftelefono'); ?>" 
												maxlength="15" placeholder="3189212, 312 308 7691">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fcelular">Celular </label>
									  <div class="controls">
											<input type="text" name="fcelular" id="fcelular" class="span12" value="<?php echo set_value('fcelular'); ?>" 
												maxlength="20" placeholder="044 312 1379827, 313 552 1232">
									  </div>
									</div>
	                
									<div class="control-group">
									  <label class="control-label" for="femail">Email </label>
									  <div class="controls">
											<input type="text" name="femail" id="femail" class="span12" value="<?php echo set_value('femail'); ?>" 
												maxlength="70" placeholder="correo@gmail.com">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fcuenta_cpi" style="font-weight: bold;">Cuenta ContpaqI </label>
									  <div class="controls">
											<input type="text" name="fcuenta_cpi" id="fcuenta_cpi" class="span12" value="<?php echo set_value('fcuenta_cpi'); ?>" 
												maxlength="12" placeholder="12312, 312322">
									  </div>
									</div>

									<div class="control-group">
										<label class="control-label" for="dcer_org">Certificado .CER:</label>
										<div class="controls">
											<input type="file" name="dcer_org" id="dcer_org" class="span12">
										</div>
									</div>

									<div class="control-group">
										<label class="control-label" for="dkey_path">Llave .KEY:</label>
										<div class="controls">
											<input type="file" name="dkey_path" id="dkey_path" class="span12">
										</div>
									</div>

									<div class="control-group">
										<label class="control-label" for="dpass">Clave:</label>
										<div class="controls">
											<input type="text" name="dpass" id="dpass" class="span12" 
												value="<?php echo set_value('dpass'); ?>" maxlength="20">
										</div>
									</div>

									<div class="control-group">
										<label class="control-label" for="dcfdi_version">Version CFDI:</label>
										<div class="controls">
											<input type="text" name="dcfdi_version" id="dcfdi_version" class="span12" 
												value="<?php echo set_value('dcfdi_version'); ?>" maxlength="6">
										</div>
									</div>

									<div class="control-group">
										<label class="control-label" for="condicionPago">Condición de Pago:</label>
										<div class="controls">
											<select name="condicionPago" class="span9" id="condicionPago">
												<option value="co" <?php echo set_select('condicionPago', 'co'); ?>>Contado</option>
												<option value="cr" <?php echo set_select('condicionPago', 'cr'); ?>>Credito</option>
											</select>
										</div>
									</div>

									<div class="control-group">
										<label class="control-label" for="plazoCredito">Plazo de Crédito:</label>
										<div class="controls">
											<input type="text" name="plazoCredito" class="span9 vpos-int" id="plazoCredito" value="<?php echo set_value('plazoCredito', '0'); ?>">
										</div>
									</div>

	              				</div> <!--/span-->

	              				<div class="span11">
	              					<table class="table table-striped table-bordered table-hover table-condensed">
										<thead>
										  <tr>
										    <th>Banamex</th>
										    <th>BANCO</th>
										    <th>ALIAS</th>
										    <th>SUCURSAL</th>
										    <th>CUENTA/CLABE</th>
										    <th>OPC</th>
										  </tr>
										</thead>
										<tbody id="tableCuentas">
										<?php if (is_array($this->input->post('cuentas_alias')))
										{
										  foreach ($this->input->post('cuentas_alias') as $key => $value)
										  {
										?>
											<tr>
											    <td><input type="checkbox" class="chk_banamex" value="si" <?php echo ($_POST['cuentas_banamex'][$key]=='true'? 'checked': ''); ?> data-uniform="false">
											    	<input type="hidden" name="cuentas_banamex[]" value="<?php echo $_POST['cuentas_banamex'][$key]; ?>" class="cuentas_banamex">
											    	<input type="hidden" name="cuentas_id[]" value="<?php echo $_POST['cuentas_id'][$key]; ?>" class="cuentas_id">
											    </td>
											    <td>
						                            <select name="fbanco[]" class="fbanco">
						                            <?php  foreach ($bancos['bancos'] as $keyb => $valueb) {
						                            ?>
						                                <option value="<?php echo $valueb->id_banco ?>" <?php echo set_select('fbanco', $valueb->id_banco); ?>><?php echo $valueb->nombre; ?></option>
						                            <?php
						                            }?>
						                            </select>
						                        </td>
											    <td><input type="text" name="cuentas_alias[]" value="<?php echo $_POST['cuentas_alias'][$key]; ?>" class="cuentas_alias"></td>
											    <td><input type="text" name="cuentas_sucursal[]" value="<?php echo $_POST['cuentas_sucursal'][$key]; ?>" class="cuentas_sucursal vpos-int" <?php echo ($_POST['cuentas_banamex'][$key]=='true'? '': 'readonly'); ?>></td>
											    <td><input type="text" name="cuentas_cuenta[]" value="<?php echo $_POST['cuentas_cuenta'][$key]; ?>" class="cuentas_cuenta vpos-int"></td>
											    <td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>
											</tr>
										<?php
										  }
										} ?>
									        <tr>
											    <td><input type="checkbox" class="chk_banamex" value="si" checked data-uniform="false">
											    	<input type="hidden" name="cuentas_banamex[]" value="true" class="cuentas_banamex">
											    	<input type="hidden" name="cuentas_id[]" value="" class="cuentas_id">
											    </td>
											    <td>
									                <select name="fbanco[]" class="fbanco">
									                <?php  foreach ($bancos['bancos'] as $keyb => $valueb) {
									                ?>
									                    <option value="<?php echo $valueb->id_banco ?>"><?php echo $valueb->nombre; ?></option>
									                <?php
									                }?>
									                </select>
									            </td>
											    <td><input type="text" name="cuentas_alias[]" value="" class="cuentas_alias"></td>
											    <td><input type="text" name="cuentas_sucursal[]" value="" class="cuentas_sucursal vpos-int"></td>
											    <td><input type="text" name="cuentas_cuenta[]" value="" class="cuentas_cuenta vpos-int"></td>
											    <td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>
											</tr>

										</tbody>
									</table>
	              				</div>

	              				<div class="clearfix"></div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/proveedores/'); ?>" class="btn">Cancelar</a>
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

