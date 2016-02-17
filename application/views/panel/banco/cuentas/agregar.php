		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/banco/cuentas/'); ?>">Cuentas bancarias</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar cuenta</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/banco/cuentas_agregar'); ?>" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<div class="control-group">
									<label class="control-label" for="dempresa">Empresa</label>
									<div class="controls">
										<input type="text" name="dempresa" class="span9" id="dempresa" value="<?php echo set_value('dempresa', $empresa_default->nombre_fiscal); ?>" autofocus>
                  	<input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value('did_empresa', $empresa_default->id_empresa); ?>" required>
									</div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="fbanco">Banco </label>
								  <div class="controls">
										<select name="fbanco" id="fbanco" required>
								<?php  foreach ($bancos['bancos'] as $key => $value) {
								?>
											<option value="<?php echo $value->id_banco ?>" <?php echo set_select('fbanco', $value->id_banco); ?>><?php echo $value->nombre; ?></option>
								<?php
								}?>
										</select>
								  </div>
								</div>

								<div class="control-group">
				                  <label class="control-label" for="fsucursal">Sucursal </label>
				                  <div class="controls">
				                    <input type="text" name="fsucursal" id="fsucursal" class="span6" maxlength="12"
				                    value="<?php echo set_value('fsucursal'); ?>" placeholder="12352">
				                  </div>
				                </div>

								<div class="control-group">
								  <label class="control-label" for="fnumero">Cuenta/CLABE </label>
								  <div class="controls">
										<input type="text" name="fnumero" id="fnumero" class="span6" maxlength="20"
										value="<?php echo set_value('fnumero'); ?>" placeholder="12352">
								  </div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="falias">Alias </label>
								  <div class="controls">
										<input type="text" name="falias" id="falias" class="span6" value="<?php echo set_value('falias'); ?>"
											maxlength="80" placeholder="Cuenta Banamex 1" required>
								  </div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="fcuenta_cpi">Cta contpaq </label>
								  <div class="controls">
										<input type="text" name="fcuenta_cpi" id="fcuenta_cpi" class="span6" value="<?php echo set_value('fcuenta_cpi'); ?>"
											maxlength="12" placeholder="1250015">
								  </div>
								</div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/banco/cuentas/'); ?>" class="btn">Cancelar</a>
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

