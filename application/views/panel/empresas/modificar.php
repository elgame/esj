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
					<li>Modificar empresa</li>
				</ul>
			</div>

			<form action="<?php echo base_url('panel/empresas/modificar/?'.String::getVarsLink(array('msg'))); ?>" method="post" 
				class="form-horizontal" enctype="multipart/form-data">
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
											<label class="control-label" for="dnombre_fiscal">*Nombre Fiscal:</label>
											<div class="controls">
												<input type="text" name="dnombre_fiscal" id="dnombre_fiscal" class="span12" 
													value="<?php echo (isset($info['info']->nombre_fiscal)? $info['info']->nombre_fiscal: ''); ?>" maxlength="130" autofocus>
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="drfc">RFC:</label>
											<div class="controls">
												<input type="text" name="drfc" id="drfc" class="span12" 
													value="<?php echo (isset($info['info']->rfc)? $info['info']->rfc: ''); ?>" maxlength="13">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dcalle">Calle:</label>
											<div class="controls">
												<input type="text" name="dcalle" id="dcalle" class="span12" 
													value="<?php echo (isset($info['info']->calle)? $info['info']->calle: ''); ?>" maxlength="60">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dno_exterior">No exterior:</label>
											<div class="controls">
												<input type="text" name="dno_exterior" id="dno_exterior" class="span12" 
													value="<?php echo (isset($info['info']->no_exterior)? $info['info']->no_exterior: ''); ?>" maxlength="7">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dno_interior">No interior:</label>
											<div class="controls">
												<input type="text" name="dno_interior" id="dno_interior" class="span12" 
													value="<?php echo (isset($info['info']->no_interior)? $info['info']->no_interior: ''); ?>" maxlength="7">
											</div>
										</div>
										
										<div class="control-group">
											<label class="control-label" for="dcolonia">Colonia:</label>
											<div class="controls">
												<input type="text" name="dcolonia" id="dcolonia" class="span12" 
													value="<?php echo (isset($info['info']->colonia)? $info['info']->colonia: ''); ?>" maxlength="60">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dlocalidad">Localidad:</label>
											<div class="controls">
												<input type="text" name="dlocalidad" id="dlocalidad" class="span12" 
													value="<?php echo (isset($info['info']->localidad)? $info['info']->localidad: ''); ?>" maxlength="45">
											</div>
										</div>
										
										<div class="control-group">
											<label class="control-label" for="dmunicipio">Municipio / Delegación:</label>
											<div class="controls">
												<input type="text" name="dmunicipio" id="dmunicipio" class="span12" 
													value="<?php echo (isset($info['info']->municipio)? $info['info']->municipio: ''); ?>" maxlength="45">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="destado">Estado:</label>
											<div class="controls">
												<input type="text" name="destado" id="destado" class="span12" 
													value="<?php echo (isset($info['info']->estado)? $info['info']->estado: ''); ?>" maxlength="45">
											</div>
										</div>
										
										<div class="control-group">
											<label class="control-label" for="dcp">CP:</label>
											<div class="controls">
												<input type="text" name="dcp" id="dcp" class="span12" 
													value="<?php echo (isset($info['info']->cp)? $info['info']->cp: ''); ?>" maxlength="10">
											</div>
										</div>

									</div> <!--/span-->

									<div class="span6 mquit">
										<div class="control-group">
											<label class="control-label" for="dregimen_fiscal">Régimen fiscal:</label>
											<div class="controls">
												<input type="text" name="dregimen_fiscal" id="dregimen_fiscal" class="span12" 
													value="<?php echo (isset($info['info']->regimen_fiscal)? $info['info']->regimen_fiscal: ''); ?>" maxlength="200">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dtelefono">Teléfono:</label>
											<div class="controls">
												<input type="text" name="dtelefono" id="dtelefono" class="span12" 
													value="<?php echo (isset($info['info']->telefono)? $info['info']->telefono: ''); ?>" maxlength="15">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dcelular">Celular:</label>
											<div class="controls">
												<input type="text" name="dcelular" id="dcelular" class="span12" 
													value="<?php echo (isset($info['info']->celular)? $info['info']->celular: ''); ?>" maxlength="20">
											</div>
										</div>
										
										<div class="control-group">
											<label class="control-label" for="demail">Email:</label>
											<div class="controls">
												<input type="text" name="demail" id="demail" class="span12" 
													value="<?php echo (isset($info['info']->email)? $info['info']->email: ''); ?>" maxlength="70">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dpag_web">Pag Web:</label>
											<div class="controls">
												<input type="text" name="dpag_web" id="dpag_web" class="span12" 
													value="<?php echo (isset($info['info']->pag_web)? $info['info']->pag_web: ''); ?>" maxlength="80">
											</div>
										</div>

										<div class="control-group">
											<label class="control-label" for="dlogo">Logo:</label>
											<div class="controls">
												<input type="file" name="dlogo" id="dlogo" class="span12">
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



