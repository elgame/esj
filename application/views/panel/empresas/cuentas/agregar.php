		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/cuentas_cpi'); ?>">Cuentas CPI</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-edit"></i> Agregar Cuenta</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/cuentas_cpi/agregar'); ?>" method="post" class="form-horizontal" id="form">
						  <fieldset>
								<legend></legend>

								<div class="span7">
									<div class="control-group">
	                  <label class="control-label" for="dnombre">*Empresa </label>
	                  <div class="controls">
	                  	<input type="text" name="dempresa" class="input-xlarge" id="dempresa" value="<?php echo set_value('dempresa'); ?>" autofocus required>
                			<input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value('did_empresa'); ?>" required>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dnombre">*Nombre </label>
	                  <div class="controls">
											<input type="text" name="dnombre" id="dnombre" value="<?php echo set_value('dnombre'); ?>" class="input-xlarge" required>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dcuenta">*Cuenta </label>
	                  <div class="controls">
	                    <input type="text" name="dcuenta" id="dcuenta" value="<?php echo set_value('dcuenta'); ?>" class="input-xlarge" required>
	                  </div>
	                </div>

                  <div class="control-group">
                    <label class="control-label" for="dcuenta">Asignar a: </label>
                    <div class="controls">
                      <select name="dtipo_cuenta">
                        <option value=""></option>
                        <option value="IvaTrasladado" <?php echo set_select('dtipo_cuenta'); ?>>Iva Trasladado</option>
                        <option value="IvaXTrasladar" <?php echo set_select('dtipo_cuenta'); ?>>Iva Por Trasladar</option>
                        <option value="IvaRetCobradoAc" <?php echo set_select('dtipo_cuenta'); ?>>Iva Ret Cobrado</option>
                        <option value="IvaRetXCobrarAc" <?php echo set_select('dtipo_cuenta'); ?>>Iva Ret Por Cobrar</option>
                        <option value="NCVenta" <?php echo set_select('dtipo_cuenta'); ?>>Nota de Credito Venta</option>
                        <option value="IvaXAcreditar" <?php echo set_select('dtipo_cuenta'); ?>>Iva Por Acreditar</option>
                        <option value="IvaAcreditado" <?php echo set_select('dtipo_cuenta'); ?>>Iva Acreditado</option>
                        <option value="IvaRetXPagar100" <?php echo set_select('dtipo_cuenta'); ?>>Iva Ret 100% Por Pagar</option>
                        <option value="IvaRetPagado100" <?php echo set_select('dtipo_cuenta'); ?>>Iva Ret 100% Pagado</option>
                        <option value="IvaRetXPagar" <?php echo set_select('dtipo_cuenta'); ?>>Iva Ret Por Pagar</option>
                        <option value="IvaRetPagado" <?php echo set_select('dtipo_cuenta'); ?>>Iva Ret Pagado</option>
                        <option value="IvaRetXPagarHono" <?php echo set_select('dtipo_cuenta'); ?>>Iva Ret Por Pagar Honorarios</option>
                        <option value="IvaRetPagadoHono" <?php echo set_select('dtipo_cuenta'); ?>>Iva Ret Pagado Honorarios</option>

                        <option value="IepsPagar6Gasto" <?php echo set_select('dtipo_cuenta'); ?>>Ieps X Pagar 6% Gastos</option>
                        <option value="IepsPagar7Gasto" <?php echo set_select('dtipo_cuenta'); ?>>Ieps X Pagar 7% Gastos</option>
                        <option value="IepsPagar9Gasto" <?php echo set_select('dtipo_cuenta'); ?>>Ieps X Pagar 9% Gastos</option>
                        <option value="IepsPagado6Egreso" <?php echo set_select('dtipo_cuenta'); ?>>Ieps Pagado 6% Egreso</option>
                        <option value="IepsPagado7Egreso" <?php echo set_select('dtipo_cuenta'); ?>>Ieps Pagado 7% Egreso</option>
                        <option value="IepsPagado9Egreso" <?php echo set_select('dtipo_cuenta'); ?>>Ieps Pagado 9% Egreso</option>

                        <option value="IepsCobrar6Ventas" <?php echo set_select('dtipo_cuenta'); ?>>Ieps X Cobrar 6% Ventas</option>
                        <option value="IepsCobrar7Ventas" <?php echo set_select('dtipo_cuenta'); ?>>Ieps X Cobrar 7% Ventas</option>
                        <option value="IepsCobrar9Ventas" <?php echo set_select('dtipo_cuenta'); ?>>Ieps X Cobrar 9% Ventas</option>
                        <option value="IepsCobrado6Ingreso" <?php echo set_select('dtipo_cuenta'); ?>>Ieps Cobrado 6% Ingresos</option>
                        <option value="IepsCobrado7Ingreso" <?php echo set_select('dtipo_cuenta'); ?>>Ieps Cobrado 7% Ingresos</option>
                        <option value="IepsCobrado9Ingreso" <?php echo set_select('dtipo_cuenta'); ?>>Ieps Cobrado 9% Ingresos</option>

                        <option value="IsrRetXPagarHono" <?php echo set_select('dtipo_cuenta'); ?>>Isr Ret Por Pagar Honorarios</option>
                        <option value="IsrRetPagadoHono" <?php echo set_select('dtipo_cuenta'); ?>>Isr Ret Pagado Honorarios</option>
                        <option value="IsrRetXPagar" <?php echo set_select('dtipo_cuenta'); ?>>Isr Ret Por Pagar</option>
                        <option value="NCGasto" <?php echo set_select('dtipo_cuenta'); ?>>Rebajas Y Bonificaciones Gastos</option>
                        <option value="CuadreGasto" <?php echo set_select('dtipo_cuenta'); ?>>Cuenta Cuadre Gasto</option>
                        <option value="NSueldo" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Sueldo</option>
                        <option value="NSueldoProd" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Sueldo Produccion</option>
                        <option value="NVacaciones" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Vacaciones</option>
                        <option value="NVacacionesProd" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Vacaciones Produccion</option>
                        <option value="NPrimaVacacional" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Prima Vacacional</option>
                        <option value="NPrimaVacacionalProd" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Prima Vacacional Produccion</option>
                        <option value="NAguinaldo" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Aguinaldo</option>
                        <option value="NAguinaldoProd" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Aguinaldo Produccion</option>
                        <option value="NHorasHex" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Horas Ext</option>
                        <option value="NHorasHexProd" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Horas Ext Produccion</option>
                        <option value="NPAsistencia" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Premio Asistencia</option>
                        <option value="NPAsistenciaProd" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Premio Asistencia Produccion</option>
                        <option value="NIndemnizaciones" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Indemnizaciones</option>
                        <option value="NIndemnizacionesProd" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Indemnizaciones Produccion</option>
                        <option value="NominaPagar" <?php echo set_select('dtipo_cuenta'); ?>>Nominas Por Pagar</option>
                        <option value="NSubsidio" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Subsidio</option>
                        <option value="NImss" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Imss</option>
                        <option value="NVejez" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Cesantia Y Vejez</option>
                        <option value="NInfonavit" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Infonavit</option>
                        <option value="NIsr" <?php echo set_select('dtipo_cuenta'); ?>>Nomina Isr</option>
                        <option value="PagoAdicional" <?php echo set_select('dtipo_cuenta'); ?>>Pago Adicional</option>
                        <option value="PagoMenor" <?php echo set_select('dtipo_cuenta'); ?>>Pago Menor</option>
                        <option value="DiarioProductos" <?php echo set_select('dtipo_cuenta'); ?>>Diario Productos</option>
                        <option value="DiarioProductosCosto" <?php echo set_select('dtipo_cuenta'); ?>>Diario Productos Costo</option>
                      </select>
                    </div>
                  </div>
								</div> <!--/span -->

								<div class="span4">
	                <div class="control-group">
	                  <label class="control-label" style="width: 100px;">Cuentas </label>
	                  <div class="controls" style="margin-left: 120px;">
	                  	<div id="lista_cuentas" style="height: 300px; overflow-y: auto; border:1px #ddd solid;">

	                    </div>
	                  </div>
	                </div>
	              </div> <!--/span-->

	              <div class="clearfix"></div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/cuentas_cpi/'); ?>" class="btn">Cancelar</a>
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

