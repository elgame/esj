		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/banco/'); ?>">Banco</a> <span class="divider">/</span>
					</li>
					<li>Depositar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar deposito</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/banco/depositar'); ?>" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<div class="control-group">
								  <label class="control-label" for="ffecha">Fecha </label>
								  <div class="controls">
										<input type="datetime-local" name="ffecha" id="ffecha" class="span5" value="<?php echo set_value('ffecha', date("Y-m-d\Th:i")); ?>" required autofocus>
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
								  <label class="control-label" for="fcuenta">Cuenta </label>
								  <div class="controls">
										<select name="fcuenta" id="fcuenta" required>
								<?php
								foreach ($cuentas['cuentas'] as $key => $value) {
									$select = set_select('fcuenta', $value->id_cuenta);
									if($select == ' selected="selected"')
										$cuenta_saldo = $value->saldo;
								?>
											<option value="<?php echo $value->id_cuenta; ?>" data-saldo="<?php echo $value->saldo; ?>" <?php echo $select; ?>><?php echo $value->alias.' - '.String::formatoNumero($value->saldo); ?></option>
								<?php
								}?>
										</select>
								  </div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="fmetodo_pago">Metodo de pago </label>
								  <div class="controls">
										<select name="fmetodo_pago" id="fmetodo_pago" required>
								<?php  foreach ($metods_pago as $key => $value) {
								?>
											<option value="<?php echo $value['value']; ?>"><?php echo $value['nombre']; ?></option>
								<?php
								}?>
										</select>
								  </div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="freferencia">Referencia </label>
								  <div class="controls">
										<input type="text" name="freferencia" id="freferencia" class="span5" maxlength="20"
										value="<?php echo set_value('freferencia'); ?>" placeholder="12352">
								  </div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="fconcepto">Concepto </label>
								  <div class="controls">
										<input type="text" name="fconcepto" id="fconcepto" class="span5" value="<?php echo set_value('fconcepto'); ?>"
											maxlength="100" placeholder="Deposito en efectivo" required>
								  </div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="fmonto">Monto </label>
								  <div class="controls">
										<input type="number" step="any" name="fmonto" id="fmonto" class="span5" value="<?php echo set_value('fmonto'); ?>"
											maxlength="12" min="0.1" placeholder="1052" required>
								  </div>
								</div>

								<div class="control-group">
									<label class="control-label" for="dcliente">Cliente</label>
									<div class="controls">
										<input type="text" name="dcliente" class="span5" id="dcliente" value="<?php echo set_value('dcliente'); ?>">
                  	<input type="hidden" name="did_cliente" id="did_cliente" value="<?php echo set_value('did_cliente'); ?>">
									</div>
								</div>

                <div class="control-group">
                  <label class="control-label" for="dcuenta_cpi">Cuenta Contpaq</label>
                  <div class="controls">
                    <input type="text" name="dcuenta_cpi" class="span5" id="dcuenta_cpi" value="<?php echo set_value('dcuenta_cpi'); ?>">
                    <input type="hidden" name="did_cuentacpi" id="did_cuentacpi" value="<?php echo set_value('did_cuentacpi'); ?>">
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

