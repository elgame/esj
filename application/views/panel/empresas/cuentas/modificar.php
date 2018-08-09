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
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar Cuenta</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/cuentas_cpi/modificar/?'.String::getVarsLink(array('msg'))); ?>" method="post" class="form-horizontal" id="form">
              <fieldset>
                <legend></legend>

                <div class="span7">
                	<div class="control-group">
	                  <label class="control-label" for="dnombre">*Empresa </label>
	                  <div class="controls">
	                  	<input type="text" name="dempresa" class="input-xlarge" id="dempresa" value="<?php echo isset($cuenta['info']->nombre_fiscal)?$cuenta['info']->nombre_fiscal:''; ?>" readonly required>
                			<input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo isset($cuenta['info']->id_empresa)?$cuenta['info']->id_empresa:''; ?>" required>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dnombre">*Nombre </label>
	                  <div class="controls">
											<input type="text" name="dnombre" id="dnombre" value="<?php echo isset($cuenta['info']->nombre)?$cuenta['info']->nombre:''; ?>" class="input-xlarge" autofocus required>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dcuenta">*Cuenta </label>
	                  <div class="controls">
	                    <input type="text" name="dcuenta" id="dcuenta" value="<?php echo isset($cuenta['info']->cuenta)?$cuenta['info']->cuenta:''; ?>" class="input-xlarge" required>
	                  </div>
	                </div>

                  <div class="control-group">
                    <label class="control-label" for="dtipo_cuenta">Asignar a: </label>
                    <div class="controls">
                      <select name="dtipo_cuenta">
                        <option value=""></option>
                        <option value="IvaTrasladado" <?php echo set_select('dtipo_cuenta', 'IvaTrasladado', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Trasladado</option>
                        <option value="IvaXTrasladar" <?php echo set_select('dtipo_cuenta', 'IvaXTrasladar', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Por Trasladar</option>
                        <option value="IvaRetCobradoAc" <?php echo set_select('dtipo_cuenta', 'IvaRetCobradoAc', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Ret Cobrado</option>
                        <option value="IvaRetXCobrarAc" <?php echo set_select('dtipo_cuenta', 'IvaRetXCobrarAc', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Ret Por Cobrar</option>
                        <option value="NCVenta" <?php echo set_select('dtipo_cuenta', 'NCVenta', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nota de Credito Venta</option>
                        <option value="IvaXAcreditar" <?php echo set_select('dtipo_cuenta', 'IvaXAcreditar', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Por Acreditar</option>
                        <option value="IvaAcreditado" <?php echo set_select('dtipo_cuenta', 'IvaAcreditado', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Acreditado</option>
                        <option value="IvaRetXPagar100" <?php echo set_select('dtipo_cuenta', 'IvaRetXPagar100', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Ret 100% Por Pagar</option>
                        <option value="IvaRetPagado100" <?php echo set_select('dtipo_cuenta', 'IvaRetPagado100', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Ret 100% Pagado</option>
                        <option value="IvaRetXPagar" <?php echo set_select('dtipo_cuenta', 'IvaRetXPagar', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Ret Por Pagar</option>
                        <option value="IvaRetPagado" <?php echo set_select('dtipo_cuenta', 'IvaRetPagado', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Ret Pagado</option>
                        <option value="IvaRetXPagarHono" <?php echo set_select('dtipo_cuenta', 'IvaRetXPagarHono', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Ret Por Pagar Honorarios</option>
                        <option value="IvaRetPagadoHono" <?php echo set_select('dtipo_cuenta', 'IvaRetPagadoHono', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Iva Ret Pagado Honorarios</option>
                        <option value="IsrRetXPagarHono" <?php echo set_select('dtipo_cuenta', 'IsrRetXPagarHono', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Isr Ret Por Pagar Honorarios</option>
                        <option value="IsrRetPagadoHono" <?php echo set_select('dtipo_cuenta', 'IsrRetPagadoHono', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Isr Ret Pagado Honorarios</option>
                        <option value="IsrRetXPagar" <?php echo set_select('dtipo_cuenta', 'IsrRetXPagar', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Isr Ret Por Pagar</option>
                        <option value="NCGasto" <?php echo set_select('dtipo_cuenta', 'NCGasto', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Rebajas Y Bonificaciones Gastos</option>
                        <option value="CuadreGasto" <?php echo set_select('dtipo_cuenta', 'CuadreGasto', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Cuenta Cuadre Gasto</option>
                        <option value="NSueldo" <?php echo set_select('dtipo_cuenta', 'NSueldo', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Sueldo</option>
                        <option value="NSueldoProd" <?php echo set_select('dtipo_cuenta', 'NSueldoProd', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Sueldo Produccion</option>
                        <option value="NVacaciones" <?php echo set_select('dtipo_cuenta', 'NVacaciones', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Vacaciones</option>
                        <option value="NVacacionesProd" <?php echo set_select('dtipo_cuenta', 'NVacacionesProd', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Vacaciones Produccion</option>
                        <option value="NPrimaVacacional" <?php echo set_select('dtipo_cuenta', 'NPrimaVacacional', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Prima Vacacional</option>
                        <option value="NPrimaVacacionalProd" <?php echo set_select('dtipo_cuenta', 'NPrimaVacacionalProd', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Prima Vacacional Produccion</option>
                        <option value="NAguinaldo" <?php echo set_select('dtipo_cuenta', 'NAguinaldo', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Aguinaldo</option>
                        <option value="NAguinaldoProd" <?php echo set_select('dtipo_cuenta', 'NAguinaldoProd', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Aguinaldo Produccion</option>
                        <option value="NHorasHex" <?php echo set_select('dtipo_cuenta', 'NHorasHex', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Horas Hex</option>
                        <option value="NHorasHexProd" <?php echo set_select('dtipo_cuenta', 'NHorasHexProd', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Horas Hex Produccion</option>
                        <option value="NPAsistencia" <?php echo set_select('dtipo_cuenta', 'NPAsistencia', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Premio Asistencia</option>
                        <option value="NPAsistenciaProd" <?php echo set_select('dtipo_cuenta', 'NPAsistenciaProd', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Premio Asistencia Produccion</option>
                        <option value="NIndemnizaciones" <?php echo set_select('dtipo_cuenta', 'NIndemnizaciones', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Indemnizaciones</option>
                        <option value="NIndemnizacionesProd" <?php echo set_select('dtipo_cuenta', 'NIndemnizacionesProd', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Indemnizaciones Produccion</option>
                        <option value="NominaPagar" <?php echo set_select('dtipo_cuenta', 'NominaPagar', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nominas Por Pagar</option>
                        <option value="NSubsidio" <?php echo set_select('dtipo_cuenta', 'NSubsidio', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Subsidio</option>
                        <option value="NImss" <?php echo set_select('dtipo_cuenta', 'NImss', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Imss</option>
                        <option value="NVejez" <?php echo set_select('dtipo_cuenta', 'NVejez', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Cesantia Y Vejez</option>
                        <option value="NInfonavit" <?php echo set_select('dtipo_cuenta', 'NInfonavit', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Infonavit</option>
                        <option value="NIsr" <?php echo set_select('dtipo_cuenta', 'NIsr', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Nomina Isr</option>
                        <option value="PagoAdicional" <?php echo set_select('dtipo_cuenta', 'PagoAdicional', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Pago Adicional</option>
                        <option value="PagoMenor" <?php echo set_select('dtipo_cuenta', 'PagoMenor', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Pago Menor</option>
                        <option value="DiarioProductos" <?php echo set_select('dtipo_cuenta', 'DiarioProductos', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Diario Productos</option>
                        <option value="DiarioProductosCosto" <?php echo set_select('dtipo_cuenta', 'DiarioProductosCosto', false, (isset($cuenta['info']->tipo_cuenta)?$cuenta['info']->tipo_cuenta:'')); ?>>Diario Productos Costo</option>
                      </select>
                    </div>
                  </div>
								</div> <!--/span -->

								<div class="span4">
	                <div class="control-group">
	                  <label class="control-label" style="width: 100px;">Cuentas </label>
	                  <div class="controls" style="margin-left: 120px;">
	                  	<div id="lista_cuentas" style="height: 300px; overflow-y: auto; border:1px #ddd solid;">
	                    	<?php echo $cuentas; ?>
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

