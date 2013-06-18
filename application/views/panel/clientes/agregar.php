		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/clientes/'); ?>">Clientes</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar cliente</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/clientes/agregar'); ?>" method="post" class="form-horizontal">
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
								</div> <!--/span-->

								<div class="span5">

									<div class="control-group">
									  <label class="control-label" for="frfc">RFC </label>
									  <div class="controls">
											<input type="text" name="frfc" id="frfc" class="span12" value="<?php echo set_value('frfc'); ?>"
												maxlength="10" placeholder="MPE050528A58, SFM00061515A">
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
									  <label class="control-label" for="fcp">CP </label>
									  <div class="controls">
											<input type="text" name="fcp" id="fcp" class="span12" value="<?php echo set_value('fcp'); ?>"
												maxlength="10" placeholder="28084, 28000">
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

	              </div> <!--/span-->

	              <div class="clearfix"></div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/clientes/'); ?>" class="btn">Cancelar</a>
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
