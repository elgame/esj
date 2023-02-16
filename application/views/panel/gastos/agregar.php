<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/compras/'); ?>">Compras</a> <span class="divider">/</span>
      </li>
      <li>Agregar Gasto</li>
    </ul>
  </div>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Agregar Gasto</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">
        <form class="form-horizontal" action="<?php echo base_url('panel/gastos/agregar/?'); ?>" method="POST" id="form" enctype="multipart/form-data">
            <div class="row-fluid">
              <div class="span6">

                <div class="control-group">
                  <label class="control-label" for="empresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $empresa_default->nombre_fiscal); ?>" autofocus>
                    <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $empresa_default->id_empresa); ?>">
                  </div>
                </div>

                <div class="control-group sucursales" style="display: none;">
                  <label class="control-label" for="sucursalId">Sucursal </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <select name="sucursalId" class="span11" id="sucursalId" data-selected="">
                        <option></option>
                      </select>
                    </div>
                  </div>
                </div><!--/control-group -->

                <div class="control-group">
                  <label class="control-label" for="proveedor">Proveedor</label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="proveedor" class="span11" id="proveedor" value="<?php echo set_value('proveedor') ?>" placeholder=""><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                    </div>
                  </div>
                    <input type="hidden" name="proveedorId" id="proveedorId" value="<?php echo set_value('proveedorId') ?>">
                </div>

                <div class="control-group">
                  <label class="control-label" for="tipo_documento">Tipo de Documento</label>
                  <div class="controls">
                    <select name="tipo_documento" class="span8" style="float: left;">
                      <option value="fa" <?php echo set_select('tipo_documento', 'fa') ?>>FACTURA</option>
                      <option value="nv" <?php echo set_select('tipo_documento', 'nv') ?>>NOTA DE VENTA</option>
                    </select>
                    <label for="es_vehiculo" class="span3" style="text-align: right;">Vehiculo
                        <input type="checkbox" name="es_vehiculo" id="es_vehiculo" data-uniform="false" value="si" data-next="vehiculo|serie" <?php echo set_checkbox('es_vehiculo', 'si'); ?>></label>
                  </div>
                </div>

                <div class="control-group" id="groupVehiculo" style="display: <?php echo isset($_POST['es_vehiculo']) ? ($_POST['es_vehiculo'] === 'si' ? 'block' : 'none') : 'none' ?>;">
                  <label class="control-label" for="vehiculo">Vehiculos</label>
                  <div class="controls">
                    <input type="text" name="vehiculo" class="span7 sikey" id="vehiculo" value="<?php echo set_value('vehiculo') ?>" placeholder="Vehiculos" data-next="tipo_vehiculo" style="float: left;">

                    <select name="tipo_vehiculo" id="tipo_vehiculo" class="span4 sikey" style="float: right;" data-next="serie">
                      <option value="ot" <?php echo set_select('tipo_vehiculo', 'ot') ?>>REFACCIONES Y OTROS</option>
                    </select>
                  </div>
                    <input type="hidden" name="vehiculoId" id="vehiculoId" value="<?php echo set_value('vehiculoId') ?>">
                </div>

                <div class="control-group">
                  <label class="control-label" for="serie">Serie</label>
                  <div class="controls">
                    <input type="text" name="serie" class="span12" id="serie" value="<?php echo set_value('serie'); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="folio">Folio</label>
                  <div class="controls">
                    <input type="text" name="folio" class="span12" id="folio" value="<?php echo set_value('folio'); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="proyecto">Asignar a un Proyecto</label>
                  <div class="controls">
                    <select name="proyecto" id="proyecto" class="span8" style="float: left;">
                      <!-- <?php foreach ($proyectos as $key => $value): ?>
                        <option value="<?php echo $value->id_proyecto; ?>" <?php echo set_select('proyecto', $value->id_proyecto); ?>><?php echo $value->nombre; ?></option>
                      <?php endforeach ?> -->
                    </select>
                  </div>
                </div>

              </div><!--/span6 -->

              <div class="span6">

                <div class="control-group">
                  <label class="control-label" for="fecha">Fecha</label>
                  <div class="controls">
                    <input type="datetime-local" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', $fecha); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fecha_factura">Fecha Factura</label>
                  <div class="controls">
                    <input type="datetime-local" name="fecha_factura" class="span9" id="fecha_factura" value="<?php echo set_value('fecha_factura', $fecha); ?>">
                  </div>
                </div>

                <div class="control-group" style="">
                  <label class="control-label" for="condicionPago">Condición de Pago</label>
                  <div class="controls">
                    <!-- <input type="text" name="condicionPago" class="span9" id="condicionPago" value="<?php echo set_value('condicionPago', '0'); ?>"> -->
                    <select name="condicionPago" class="span9" id="condicionPago" data-next="plazoCredito|concepto">
                      <option value="co" <?php echo set_select('condicionPago', 'co'); ?>>Contado</option>
                      <option value="cr" <?php echo set_select('condicionPago', 'cr'); ?>>Credito</option>
                    </select>
                  </div>
                </div>

                <div class="control-group" id="grup_plazo_credito" style="display: none;">
                  <label class="control-label" for="plazoCredito">Plazo de Crédito</label>
                  <div class="controls">
                    <input type="text" name="plazoCredito" class="span9" id="plazoCredito" value="<?php echo set_value('plazoCredito', '0'); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <div class="controls span8">
                    <a class="btn btn-success" href="<?php echo base_url('panel/gastos/verXml/?ide='.set_value('empresaId', $empresa_default->id_empresa).'&idp='.set_value('proveedorId').'') ?>"
                      data-href="<?php echo base_url('panel/gastos/verXml/') ?>"
                      rel="superbox-80x550" title="Buscar" id="supermodalBtn">
                      <i class="icon-eye-open icon-white"></i> <span class="hidden-tablet">Buscar XML</span></a>
                    <span style="float: right;">
                      UUID: <input type="text" name="uuid" value="" id="buscarUuid"><br>
                      No Certificado: <input type="text" name="noCertificado" value="" id="buscarNoCertificado">
                    </span>
                  </div>
                </div>
                <!-- <div class="control-group">
                  <label class="control-label" for="xml">XML</label>
                  <div class="controls">
                    <input type="file" name="xml" class="span9" id="xml" data-uniform="false" accept="text/xml">
                  </div>
                </div> -->

                <div class="control-group">
                  <label class="control-label" for="concepto">Concepto</label>
                  <div class="controls">
                    <textarea name="concepto" class="span12" id="concepto" maxlength="200" data-next="dkilometros|dcuenta|subtotal"><?php echo set_value('concepto', ''); ?></textarea>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dcuenta_cpi">Cuenta Contpaq (Gasto)</label>
                  <div class="controls">
                    <input type="text" name="dcuenta_cpi" class="span12" id="dcuenta_cpi" value="<?php echo set_value('dcuenta_cpi'); ?>">
                    <input type="hidden" name="did_cuentacpi" id="did_cuentacpi" value="<?php echo set_value('did_cuentacpi'); ?>">
                  </div>
                </div>

              </div><!--/span6 -->
            </div><!--/row-fluid -->

            <!-- <div class="row-fluid" id="group_gasolina" style="display: <?php echo isset($_POST['tipo_vehiculo']) ? ($_POST['tipo_vehiculo'] === 'ot' ? 'none' : 'block') : 'none' ?>;">
              <div class="span4">
                <div class="control-group">
                  <div class="controls span9">
                    Kilometros <input type="text" name="dkilometros" class="span12 sikey vpos-int" id="dkilometros" value="<?php echo set_value('dkilometros', ''); ?>" maxlength="10" data-next="dlitros">
                  </div>
                </div>
              </div>
              <div class="span4">
                <div class="control-group">
                  <div class="controls span9">
                    Litros <input type="text" name="dlitros" class="span12 sikey vpositive" id="dlitros" value="<?php echo set_value('dlitros', ''); ?>" maxlength="10" data-next="dprecio">
                  </div>
                </div>
              </div>
              <div class="span4">
                <div class="control-group">
                  <div class="controls span9">
                    Precio <input type="text" name="dprecio" class="span12 sikey vpositive" id="dprecio" value="<?php echo set_value('dprecio', ''); ?>" maxlength="10" data-next="dcuenta">
                  </div>
                </div>
              </div>
            </div> -->

          <div class="row-fluid" id="group_pago_contado" style="display: none;">
            <div class="span3">
              <div class="control-group">
                <div class="controls span9">
                  Cuenta Bancaria
                  <select name="dcuenta" class="span12" id="dcuenta">
                  <?php
                  foreach ($cuentas['cuentas'] as $key => $value) {
                  ?>
                      <option value="<?php echo $value->id_cuenta; ?>" <?php echo set_select('dcuenta', $value->id_cuenta); ?>><?php echo $value->alias.' - '.MyString::formatoNumero($value->saldo); ?></option>
                  <?php
                  }
                  ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="span3">
              <div class="control-group">
                <div class="controls span9">
                  Referencia <input type="text" name="dreferencia" class="span12" id="dreferencia" value="<?php echo set_value('dreferencia', ''); ?>" maxlength="10">
                </div>
              </div>
            </div>
            <div class="span3">
              <div class="control-group">
                <div class="controls span9">
                  Metodo de pago
                  <select name="fmetodo_pago" class="span12" id="fmetodo_pago">
                  <?php  foreach ($metods_pago as $key => $value) {
                  ?>
                      <option value="<?php echo $value['value']; ?>"><?php echo $value['nombre']; ?></option>
                  <?php
                  }?>
                  </select>
                </div>
              </div>
            </div>
            <div class="span3" id="cuenta_proveedor">
              <div class="control-group">
                <div class="controls span9">
                  Cuenta Proveedor
                  <select name="fcuentas_proveedor" class="span12" id="fcuentas_proveedor">
                  <?php  foreach ($cuentas_proveedor as $key => $value) {
                  ?>
                      <option value="<?php echo $value->id_cuenta; ?>" <?php echo set_select('fcuentas_proveedor', $value->id_cuenta) ?>><?php echo $value->full_alias; ?></option>
                  <?php
                  }?>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="row-fluid simpleCodArea" id="groupCatalogos">  <!-- Box catalogos-->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-truck"></i> Catálogos</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">
                  <div class="span6">
                    <div class="control-group" id="cultivosGrup">
                      <label class="control-label" for="area">Cultivo / Actividad / Producto </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area') ?>" placeholder="Limon, Piña">
                        </div>
                        <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId') ?>">
                      </div>
                    </div><!--/control-group -->

                    <div class="control-group" id="ranchosGrup">
                      <label class="control-label" for="rancho">Areas / Ranchos / Lineas </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="rancho" class="span11" id="rancho" value="<?php echo set_value('rancho') ?>" placeholder="Milagro A, Linea 1">
                        </div>
                      </div>
                      <ul class="tags" id="tagsRanchoIds">
                      <?php if (isset($_POST['ranchoId'])) {
                        foreach ($_POST['ranchoId'] as $key => $ranchoId) { ?>
                          <li><span class="tag"><?php echo $_POST['ranchoText'][$key] ?></span>
                            <input type="hidden" name="ranchoId[]" class="ranchoId" value="<?php echo $ranchoId ?>">
                            <input type="hidden" name="ranchoText[]" class="ranchoText" value="<?php echo $_POST['ranchoText'][$key] ?>">
                          </li>
                       <?php }} ?>
                      </ul>
                    </div><!--/control-group -->

                    <div class="control-group">
                      <label class="control-label" for="intangible">Gasto intangible</label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="checkbox" name="intangible" id="intangible" data-uniform="false" value="si" data-next="subtotal" <?php echo set_checkbox('intangible', 'si'); ?>></label>
                        </div>
                      </div>
                    </div>

                    <div class="control-group" id="cultivosGrup">
                      <label class="control-label" for="codigoArea">Codigo Area </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="codigoArea" class="span11 showCodigoAreaAuto" id="codigoArea" value="<?php echo set_value('codigoArea') ?>" placeholder="">
                          <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                          <input type="hidden" name="codigoAreaId" id="codigoAreaId" value="<?php echo set_value('codigoAreaId') ?>">
                        </div>
                      </div>
                    </div><!--/control-group -->
                  </div>

                  <div class="span6">
                    <div class="control-group" id="centrosCostosGrup">
                      <label class="control-label" for="centroCosto">Centro de costo </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="centroCosto" class="span11" id="centroCosto" value="<?php echo set_value('centroCosto') ?>" placeholder="Mantenimiento, Gasto general">
                        </div>
                      </div>
                      <ul class="tags" id="tagsCCIds">
                      <?php if (isset($_POST['centroCostoId'])) {
                        foreach ($_POST['centroCostoId'] as $key => $centroCostoId) { ?>
                          <li><span class="tag"><?php echo $_POST['centroCostoText'][$key] ?></span>
                            <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="<?php echo $centroCostoId ?>">
                            <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="<?php echo $_POST['centroCostoText'][$key] ?>">
                          </li>
                       <?php }} ?>
                      </ul>
                    </div><!--/control-group -->

                    <div class="control-group" id="activosGrup">
                      <label class="control-label" for="activos">Activos </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="activos" class="span11" id="activos" value="<?php echo set_value('activos') ?>" placeholder="Nissan FRX, Maquina limon">
                        </div>
                        <input type="hidden" name="activoId" id="activoId" value="<?php echo set_value('activoId') ?>">
                      </div>
                    </div><!--/control-group -->
                  </div>

                </div>

               </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->

          <div class="row-fluid">
            <div class="span4">
              <div class="control-group">
                <div class="controls span9">
                  Subtotal <input type="text" name="subtotal" class="span12 vpositive" id="subtotal" value="<?php echo set_value('subtotal', '0'); ?>">
                </div>
              </div>
            </div>
            <div class="span4">
              <div class="control-group">
                <div class="controls span9">
                  IVA <input type="text" name="iva" class="span12 vpositive" id="iva" value="<?php echo set_value('iva', '0'); ?>">
                </div>
              </div>
            </div>
            <div class="span4">
              <div class="control-group">
                <div class="controls span9">
                  Ret. IVA <input type="text" name="ret_iva" class="span12 vpositive" id="ret_iva" value="<?php echo set_value('ret_iva', '0'); ?>">
                </div>
              </div>
            </div>
          </div><!--/row-fluid -->
          <div>
            <div class="span4">
              <div class="control-group">
                <div class="controls span9">
                  Ret. ISR <input type="text" name="ret_isr" class="span12 vpositive" id="ret_isr" value="<?php echo set_value('ret_isr', '0'); ?>">
                </div>
              </div>
            </div>
            <div class="span4">
              <div class="control-group">
                <div class="controls span9">
                  IEPS <input type="text" name="ieps" class="span12 vpositive" id="ieps" value="<?php echo set_value('ieps', '0'); ?>">
                </div>
              </div>
            </div>
            <div class="span4">
              <div class="control-group">
                <div class="controls span9">
                  TOTAL <input type="text" name="total" class="span12 vpositive" id="total" value="<?php echo set_value('total', '0'); ?>" readonly>
                </div>
              </div>
            </div>
          </div><!--/row-fluid -->

          <div class="span7">
            <a href="<?php echo base_url('panel/gastos/ligar'); ?>" class="btn btn-info pull-left" id="btnLigarOrdenes" rel="superbox-70x550" data-supermodal-callback="validaParamsGasto" data-supermodal-autoshow="false">Ligar Ordenes</a>
            <div id="ordenesSeleccionadas" class="pull-left" style="margin-left: 5px;">
          <?php
          if(isset($_POST['ordenes']))
          foreach ($_POST['ordenes'] as $key => $value)
          {
          ?>
            <span class="label" style="margin-left:4px"><?php echo $_POST['ordenes_folio'][$key] ?> <i class="icon-remove ordenremove" style="cursor: pointer"></i>
              <input type="hidden" name="ordenes[]" value="<?php echo $value ?>" id="ordenes<?php echo $value ?>">
              <input type="hidden" name="ordenes_folio[]" value="<?php echo $_POST['ordenes_folio'][$key] ?>">
            </span>
          <?php
          }
          ?>
            </div>
          </div>

          <div class="span4 pull-right">
            <div class="control-group">
              <div class="controls span9">
                <div class="well span12">
                    <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
                </div>
              </div>
            </div>
          </div>
        </form>
      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->
</div>

<!-- Modal -->
<div id="modalAreas" class="modal modal-w70 hide fade" tabindex="-1" role="dialog" aria-labelledby="modalAreasLavel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="modalAreasLavel">Catalogo de maquinaria, equipos e instalaciones</h3>
  </div>
  <div class="modal-body">

    <div class="row-fluid">

      <div>

    <?php foreach ($areas as $key => $value)
    { ?>
        <div class="span3" id="tblAreasDiv<?php echo $value->id_tipo ?>" style="display: none;">
          <table class="table table-hover table-condensed <?php echo ($key==0? 'tblAreasFirs': ''); ?>"
              id="tblAreas<?php echo $value->id_tipo ?>" data-id="<?php echo $value->id_tipo ?>">
            <thead>
              <tr>
                <th style="width:10px;"></th>
                <th>Codigo</th>
                <th><?php echo $value->nombre ?></th>
              </tr>
            </thead>
            <tbody>
              <!-- <tr class="areaClick" data-id="" data-sig="">
                <td><input type="radio" name="modalRadioSel" value="" data-uniform="false"></td>
                <td>9</td>
                <td>EMPAQUE</td>
              </tr> -->
            </tbody>
          </table>
        </div>
    <?php
    } ?>

      </div>

    </div>

  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
    <button class="btn btn-primary" id="btnModalAreasSel">Seleccionar</button>
  </div>
</div>


<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });

  <?php if(isset($reload)) { ?>
    setTimeout(function(){
      <?php
          if (isset($id_movimiento{0}))
            echo "window.open(base_url+'panel/banco/cheque?id='+{$id_movimiento}, 'Print cheque');";
      ?>

      window.location = base_url+"panel/gastos/agregar";
    },1500)
  <?php } ?>

</script>
<?php }
}?>
<!-- Bloque de alertas -->
