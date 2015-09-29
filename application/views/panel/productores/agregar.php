		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/productores/'); ?>">Productores</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar Productor</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/productores/'.(isset($method)? $method: 'agregar') ); ?>" method="post" class="form-horizontal">
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

									<div class="control-group">
									  <label class="control-label" for="fpais">Pais </label>
									  <div class="controls">
											<input type="text" name="fpais" id="fpais" class="span10" value="<?php echo set_value('fpais', 'MEXICO'); ?>"
												maxlength="45" placeholder="MEXICO">
									  </div>
									</div>

                  <div class="control-group">
                    <label class="control-label" for="fempresa">Empresa </label>
                    <div class="controls">
                    <input type="text" name="fempresa" id="fempresa" class="span10" value="<?php echo set_value('fempresa', $empresa->nombre_fiscal); ?>" placeholder="Nombre">
                    <input type="hidden" name="did_empresa" value="<?php echo set_value('did_empresa', $empresa->id_empresa); ?>" id="did_empresa">
                    </div>
                  </div>
								</div> <!--/span-->

								<div class="span5">

									<div class="control-group">
									  <label class="control-label" for="fparcela">Parcela </label>
									  <div class="controls">
									  	<select name="fparcela" id="fparcela">
									  		<option value="RENTADA">RENTADA</option>
									  		<option value="PROPIA">PROPIA</option>
									  	</select>
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fejido_parcela">Ejido parcela </label>
									  <div class="controls">
											<input type="text" name="fejido_parcela" id="fejido_parcela" class="span12" value="<?php echo set_value('fejido_parcela'); ?>"
												maxlength="150">
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
												maxlength="600" placeholder="correo@gmail.com">
									  </div>
									</div>

									<!-- <div class="control-group">
									  <label class="control-label" for="fcuenta_cpi" style="font-weight: bold;">Cuenta ContpaqI </label>
									  <div class="controls">
											<input type="text" name="fcuenta_cpi" id="fcuenta_cpi" class="span12" value="<?php echo set_value('fcuenta_cpi'); ?>"
												maxlength="12" placeholder="12312, 312322">
									  </div>
									</div> -->

                  <div class="control-group">
                    <label class="control-label" for="ftipo">Tipo </label>
                    <div class="controls">
                      <select name="ftipo" class="span9" id="ftipo">
                        <option value="SIN FACTURA PAGO EN EFECTIVO">SIN FACTURA PAGO EN EFECTIVO</option>
                        <option value="CON FACTURA PAGO EN EFECTIVO">CON FACTURA PAGO EN EFECTIVO</option>
                        <option value="FACTURADOR EMPAQUE SAN JORGE">FACTURADOR EMPAQUE SAN JORGE</option>
                      </select>
                    </div>
                  </div>

	              </div> <!--/span-->

	              <div class="clearfix"></div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/productores/'); ?>" class="btn">Cancelar</a>
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

