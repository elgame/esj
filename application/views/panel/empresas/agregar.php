		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/empresas/'); ?>">Empresas</a> <span class="divider">/</span>
					</li>
					<li>Agregar empresa</li>
				</ul>
			</div>

			<form action="<?php echo base_url('panel/empresas/agregar'); ?>" method="post" class="form-horizontal" enctype="multipart/form-data">
				<div class="row-fluid">
					<div class="box span12">
						<div class="box-header well" data-original-title>
							<h2><i class="icon-list-alt"></i> Información</h2>
							<div class="box-icon">
								<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
							</div>
						</div>
						<div class="box-content">
							  <fieldset>
									<legend></legend>

									<div class="span6 mquit">
										<div class="control-group">
											<label class="control-label" for="dnombre_fiscal">Nombre Fiscal:</label>
											<div class="controls">
												<input type="text" name="dnombre_fiscal" id="dnombre_fiscal" class="span12" value="<?php echo set_value('dnombre_fiscal'); ?>" maxlength="130" required autofocus>
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="drfc">RFC:</label>
											<div class="controls">
												<input type="text" name="drfc" id="drfc" class="span12" value="<?php echo set_value('drfc'); ?>" maxlength="13">
											</div>
										</div>

                    <div class="control-group">
                      <label class="control-label" for="dsucursal">Sucursal:</label>
                      <div class="controls">
                        <input type="text" name="dsucursal" id="dsucursal" class="span12" value="<?php echo set_value('dsucursal'); ?>" maxlength="130">
                      </div>
                    </div>

										<div class="control-group">
											<label class="control-label" for="dcalle">Calle:</label>
											<div class="controls">
												<input type="text" name="dcalle" id="dcalle" class="span12" value="<?php echo set_value('dcalle'); ?>" maxlength="60">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dno_exterior">No exterior:</label>
											<div class="controls">
												<input type="text" name="dno_exterior" id="dno_exterior" class="span12" value="<?php echo set_value('dno_exterior'); ?>" maxlength="7">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dno_interior">No interior:</label>
											<div class="controls">
												<input type="text" name="dno_interior" id="dno_interior" class="span12" value="<?php echo set_value('dno_interior'); ?>" maxlength="7">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dpais">País:</label>
											<div class="controls">
												<input type="text" name="dpais" id="dpais" class="span12" value="<?php echo set_value('dpais'); ?>" maxlength="60" required>
												<span class="dpais help-block nomarg" style="color:#bd362f"></span>
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="destado">Estado:</label>
											<div class="controls">
												<input type="text" name="destado" id="destado" class="span12" value="<?php echo set_value('destado'); ?>" maxlength="45">
												<span class="destado help-block nomarg" style="color:#bd362f"></span>
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dmunicipio">Municipio / Delegación:</label>
											<div class="controls">
												<input type="text" name="dmunicipio" id="dmunicipio" class="span12" value="<?php echo set_value('dmunicipio'); ?>" maxlength="45">
												<span class="dmunicipio help-block nomarg" style="color:#bd362f"></span>
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dlocalidad">Localidad:</label>
											<div class="controls">
												<input type="text" name="dlocalidad" id="dlocalidad" class="span12" value="<?php echo set_value('dlocalidad'); ?>" maxlength="45">
												<span class="dlocalidad help-block nomarg" style="color:#bd362f"></span>
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dcp">CP:</label>
											<div class="controls">
												<input type="text" name="dcp" id="dcp" class="span12" value="<?php echo set_value('dcp'); ?>" maxlength="10">
												<span class="dcp help-block nomarg" style="color:#bd362f"></span>
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dcolonia">Colonia:</label>
											<div class="controls">
												<input type="text" name="dcolonia" id="dcolonia" class="span12" value="<?php echo set_value('dcolonia'); ?>" maxlength="60">
												<span class="dcolonia help-block nomarg" style="color:#bd362f"></span>
											</div>
										</div>

									</div> <!--/span-->

									<div class="span6 mquit">
										<!-- <div class="control-group">
											<label class="control-label" for="dregimen_fiscal">Régimen fiscal:</label>
											<div class="controls">
												<input type="text" name="dregimen_fiscal" id="dregimen_fiscal" class="span12" value="<?php echo set_value('dregimen_fiscal'); ?>" maxlength="200">
											</div>
										</div> -->
                    <div class="control-group">
                      <label class="control-label" for="dregimen_fiscal">Régimen fiscal </label>
                      <div class="controls">
                      <select name="dregimen_fiscal" id="dregimen_fiscal" class="span12">
                      <?php foreach ($regimen_fiscales as $key => $value)
                      {
                      ?>
                        <option value="<?php echo $value->c_RegimenFiscal ?>"
                          <?php echo set_select('dregimen_fiscal', $value->c_RegimenFiscal, false, $this->input->post('dregimen_fiscal')); ?>><?php echo $value->label ?></option>
                      <?php
                      } ?>
                      </select>
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="dcurp">CURP:</label>
                      <div class="controls">
                        <input type="text" name="dcurp" id="dcurp" class="span12" value="<?php echo set_value('dcurp'); ?>" maxlength="15">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="dregistro_patronal">Registro patronal:</label>
                      <div class="controls">
                        <input type="text" name="dregistro_patronal" id="dregistro_patronal" class="span12" value="<?php echo set_value('dregistro_patronal'); ?>" maxlength="15">
                      </div>
                    </div>

										<div class="control-group">
											<label class="control-label" for="dtelefono">Teléfono:</label>
											<div class="controls">
												<input type="text" name="dtelefono" id="dtelefono" class="span12" value="<?php echo set_value('dtelefono'); ?>" maxlength="15">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="demail">Email:</label>
											<div class="controls">
												<input type="text" name="demail" id="demail" class="span12" value="<?php echo set_value('demail'); ?>" maxlength="70">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dpag_web">Pag Web:</label>
											<div class="controls">
												<input type="text" name="dpag_web" id="dpag_web" class="span12" value="<?php echo set_value('dpag_web'); ?>" maxlength="80">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dlogo">Logo:</label>
											<div class="controls">
												<input type="file" name="dlogo" id="dlogo" class="span12">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dcer_org">Certificado .CER:</label>
											<div class="controls">
												<input type="file" name="dcer_org" id="dcer_org" class="span12">
											</div>
										</div>

										<!-- <div class="control-group">
											<label class="control-label" for="dcer">Certificado .PEM:</label>
											<div class="controls">
												<input type="file" name="dcer" id="dcer" class="span12">
											</div>
										</div> -->

										<div class="control-group">
											<label class="control-label" for="dkey_path">Llave .KEY:</label>
											<div class="controls">
												<input type="file" name="dkey_path" id="dkey_path" class="span12">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dpass">Clave:</label>
											<div class="controls">
												<input type="password" name="dpass" id="dpass" class="span12"
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

		              </div> <!--/span-->

							  </fieldset>

						</div>
					</div><!--/box span-->

				</div><!--/row-->

				<div class="form-actions">
				  <button type="submit" class="btn btn-primary">Guardar</button>
				  <button type="reset" class="btn">Cancelar</button>
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
