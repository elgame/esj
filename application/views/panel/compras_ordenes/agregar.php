<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <?php if ( ! isset($_GET['w']) || $_GET['w'] === 'r'){
                  $titulo = 'Agregar orden de requisición';
        ?>
          <a href="<?php echo base_url('panel/compras_ordenes/requisicion'); ?>">Ordenes de Requisicion</a> <span class="divider">/</span>
        <?php } else {
                  $titulo = 'Agregar orden de compra';
          ?>
          <a href="<?php echo base_url('panel/compras_ordenes/'); ?>">Ordenes de Compras</a> <span class="divider">/</span>
        <?php } ?>
      </li>
      <li>Agregar</li>
    </ul>
  </div>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> <?php echo $titulo; ?></h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/compras_ordenes/agregar/?'.String::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $empresa_default->nombre_fiscal) ?>" placeholder="" autofocus><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                  <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $empresa_default->id_empresa) ?>">
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
                <label class="control-label" for="solicito">Solicito</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="solicito" class="span11" id="solicito" value="<?php echo set_value('solicito') ?>" placeholder="">
                  </div>
                </div>
              </div>

              <!-- <div class="control-group">
                <label class="control-label" for="autorizo">Autorizo</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="autorizo" class="span11" id="autorizo" value="<?php echo set_value('autorizo') ?>" placeholder="" required>
                  </div>
                </div>
                  <input type="hidden" name="autorizoId" id="autorizoId" value="<?php echo set_value('autorizoId') ?>" required>
              </div> -->

              <div class="control-group">
                <label class="control-label" for="departamento">Departamento</label>
                <div class="controls">
                  <select name="departamento" class="span11" id="departamento">
                    <option></option>
                    <?php foreach ($departamentos as $key => $depa) { ?>
                      <option value="<?php echo $depa->id_departamento ?>" <?php echo set_select('departamento', $depa->id_departamento); ?>><?php echo $depa->nombre ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="cliente">Cliente</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="cliente" class="span11" id="cliente" value="<?php echo set_value('cliente') ?>" placeholder="">
                  </div>
                </div>
                  <input type="hidden" name="clienteId" id="clienteId" value="<?php echo set_value('clienteId') ?>">
              </div>

              <div class="control-group">
                <label class="control-label" for="descripcion">Observaciones</label>
                <div class="controls">
                  <div class="input-append span12">
                    <textarea name="descripcion" class="span11" id="descripcion"><?php echo set_value('descripcion') ?></textarea>
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="descripcion">Vehiculo</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="checkbox" name="es_vehiculo" id="es_vehiculo" data-uniform="false" value="si" data-next="fecha" <?php echo set_checkbox('es_vehiculo', 'si'); ?>></label>
                  </div>
                </div>
              </div>
            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', $fecha); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="tipoOrden">Tipo de Orden</label>
                <div class="controls">
                  <select name="tipoOrden" class="span9" id="tipoOrden">
                    <option value="p" <?php echo set_select('tipoOrden', 'p'); ?>>Productos</option>
                    <option value="d" <?php echo set_select('tipoOrden', 'd'); ?>>Descripciones</option>
                    <option value="f" <?php echo set_select('tipoOrden', 'f'); ?>>Fletes</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio">Folio</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo set_value('folio', $next_folio); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="tipoPago">Tipo de Pago</label>
                <div class="controls">
                  <select name="tipoPago" class="span9" id="tipoPago">
                    <option value="cr" <?php echo set_select('tipoPago', 'cr'); ?>>Credito</option>
                    <option value="co" <?php echo set_select('tipoPago', 'co'); ?>>Contado</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row-fluid" id="groupVehiculo" style="display: <?php echo isset($_POST['es_vehiculo']) ? ($_POST['es_vehiculo'] === 'si' ? 'block' : 'none') : 'none' ?>;">  <!-- Box Productos -->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-truck"></i> Vehículos</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">
                  <div class="span12 mquit">
                    <div class="control-group">
                      <label class="control-label" for="vehiculo">Vehiculos</label>
                      <div class="controls">
                        <input type="text" name="vehiculo" class="span7 sikey" id="vehiculo" value="<?php echo set_value('vehiculo') ?>" placeholder="Vehiculos" data-next="tipo_vehiculo" style="float: left;">

                        <select name="tipo_vehiculo" id="tipo_vehiculo" class="span4 sikey" style="float: right;" data-next="dkilometros">
                          <option value="ot" <?php echo set_select('tipo_vehiculo', 'ot') ?>>REFACCIONES Y OTROS</option>
                          <option value="g" <?php echo set_select('tipo_vehiculo', 'g') ?>>GASOLINA</option>
                          <option value="d" <?php echo set_select('tipo_vehiculo', 'd') ?>>DIESEL</option>
                        </select>
                      </div>
                        <input type="hidden" name="vehiculoId" id="vehiculoId" value="<?php echo set_value('vehiculoId') ?>">
                    </div>
                  </div>
                </div><!--/row-fluid -->

                <div class="row-fluid" id="group_gasolina" style="display: <?php echo isset($_POST['tipo_vehiculo']) ? ($_POST['tipo_vehiculo'] === 'ot' ? 'none' : 'block') : 'none' ?>;">
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
                        Precio <input type="text" name="dprecio" class="span12 sikey vpositive" id="dprecio" value="<?php echo set_value('dprecio', ''); ?>" maxlength="10" data-next="fconcepto">
                      </div>
                    </div>
                  </div>
                </div>

               </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->

          <div class="row-fluid">  <!-- Box Productos -->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-barcode"></i> Productos</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">

                  <div class="span12 mquit">
                    <div class="span3">
                      <!-- data-next="fcodigo" -->
                      <input type="text" class="span12" id="fcodigo" placeholder="Codigo" data-next="fcodigo">
                    </div><!--/span3s -->
                    <div class="span6">
                      <div class="input-append span12">
                        <input type="text" class="span12" id="fconcepto" placeholder="Producto / Descripción">
                        <!-- <a href="" rel="superbox-70x550" class="btn btn-info" type="button" data-rel="tooltip" data-title="Agregar Producto"><i class="icon-plus" ></i></a> -->
                      </div>
                      <input type="hidden" class="span1" id="fconceptoId">
                    </div><!--/span3s -->
                    <div class="span1">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fcantidad" min="0.01" placeholder="Cant.">
                    </div><!--/span3s -->
                    <div class="span2">
                      <input type="text" class="span12 vpositive" id="fprecio" placeholder="Precio Unitario">
                    </div><!--/span3s -->

                  </div><!--/span12 -->
                  <br><br>
                  <div class="span12 mquit">
                    <div class="span3">
                      <label for="fpresentacion" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">PRESENTACION</label>
                      <select class="span12" id="fpresentacion">
                      </select>
                    </div><!--/span3 -->
                     <div class="span2">
                      <label for="funidad" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">UNIDAD</label>
                      <select class="span12" id="funidad">
                        <?php foreach ($unidades as $key => $unidad) { ?>
                          <option value="<?php echo $unidad->id_unidad ?>"><?php echo $unidad->nombre ?></option>
                        <?php } ?>
                      </select>
                    </div><!--/span2 -->
                    <div class="span1">
                      <label for="ftraslado" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">IVA</label>
                      <select class="span12" id="ftraslado">
                        <option value="0">0%</option>
                        <option value="11">11%</option>
                        <option value="16">16%</option>
                      </select>
                    </div><!--/span1 -->
                    <div class="span1">
                      <label for="fieps" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">IEPS (%)</label>
                      <input type="text" class="span12 vpositive" id="fieps" placeholder="%">
                    </div><!--/span1 -->
                    <div class="span2">
                      <label for="ftipo_moneda" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">Tipo de cambio</label>
                      <select class="span7 pull-left" id="ftipo_moneda">
                        <option value="peso">Pesos</option>
                        <option value="dolar">Dolares</option>
                      </select>
                      <input type="text" class="span5 vpositive" id="ftipo_cambio" placeholder="12.45">
                    </div><!--/span2 -->
                    <div class="span2 offset1">
                      <button type="button" class="btn btn-success span12" id="btnAddProd">Agregar</button>
                    </div><!--/span2 -->
                  </div><!--/span12 -->

                </div><!--/row-fluid -->
                <br>
                <div class="row-fluid">
                  <div class="span12 mquit">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                      <thead>
                        <tr>
                          <th>CODIGO</th>
                          <th>PRODUCTO</th>
                          <th>PRESEN.</th>
                          <th>UNIDAD</th>
                          <th>CANT.</th>
                          <th>FALTANTES</th>
                          <th>P.U.</th>
                          <th>IVA</th>
                          <th>IEPS (%)</th>
                          <th>RET 4%</th>
                          <th>IMPORTE</th>
                          <th>DESCRIP</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>

                        <?php if (isset($_POST['concepto'])) {
                              foreach ($_POST['concepto'] as $key => $concepto) { ?>
                            <tr>
                              <td style="width: 70px;">
                                <?php echo $_POST['codigo'][$key] ?>
                                <input type="hidden" name="codigo[]" value="<?php echo $_POST['codigo'][$key] ?>" class="span12">
                              </td>
                              <td>
                                  <?php echo $concepto ?>
                                  <input type="hidden" name="concepto[]" value="<?php echo $concepto ?>" id="concepto" class="span12">
                                  <input type="hidden" name="productoId[]" value="<?php echo $_POST['productoId'][$key] ?>" id="productoId" class="span12">
                              </td>
                              <td style="width: 160px;">
                                <select name="presentacion[]">
                                  <option value="<?php echo $_POST['presentacion'][$key]?>" data-cantidad="<?php echo $_POST['presentacionCant'][$key] ?>"><?php echo $_POST['presentacionText'][$key] ?></option>
                                </select>
                                <input type="hidden" name="presentacionCant[]" value="<?php echo $_POST['presentacionCant'][$key] ?>" id="presentacionCant" class="span12">
                                <input type="hidden" name="presentacionText[]" value="<?php echo $_POST['presentacionText'][$key] ?>" id="presentacionText" class="span12">
                              </td>
                              <td style="width: 120px;">
                                <select name="unidad[]" id="unidad" class="span12">
                                  <?php foreach ($unidades as $unidad) { ?>
                                    <option value="<?php echo $unidad->id_unidad ?>" <?php echo $_POST['unidad'][$key] == $unidad->id_unidad ? 'selected' : ''; ?>><?php echo $unidad->nombre ?></option>
                                  <?php } ?>
                                </select>
                              </td>
                              <td style="width: 65px;">
                                  <input type="number" name="cantidad[]" value="<?php echo $_POST['cantidad'][$key] ?>" id="cantidad" class="span12 vpositive" min="1">
                              </td>
                              <td style="width: 65px;">
                                  <input type="number" name="faltantes[]" value="<?php echo $_POST['faltantes'][$key] ?>" id="faltantes" class="span12 vpositive" min="0">
                              </td>
                              <td style="width: 90px;">
                                  <input type="text" name="valorUnitario[]" value="<?php echo $_POST['valorUnitario'][$key] ?>" id="valorUnitario" class="span12 vpositive">
                              </td>
                              <td style="width: 66px;">
                                  <select name="traslado[]" id="traslado" class="span12">
                                    <option value="0" <?php echo $_POST['traslado'][$key] === '0' ? 'selected' : '' ?>>0%</option>
                                    <option value="11" <?php echo $_POST['traslado'][$key] === '11' ? 'selected' : ''?>>11%</option>
                                    <option value="16" <?php echo $_POST['traslado'][$key] === '16' ? 'selected' : ''?>>16%</option>
                                  </select>
                                  <input type="hidden" name="trasladoTotal[]" value="<?php echo $_POST['trasladoTotal'][$key] ?>" id="trasladoTotal" class="span12">
                                  <input type="hidden" name="trasladoPorcent[]" value="<?php echo $_POST['trasladoPorcent'][$key] ?>" id="trasladoPorcent" class="span12">
                              </td>
                              <td style="width: 66px;">
                                <input type="text" name="iepsPorcent[]" value="<?php echo $_POST['iepsPorcent'][$key] ?>" id="iepsPorcent" class="span12">
                                <input type="hidden" name="iepsTotal[]" value="<?php echo $_POST['iepsTotal'][$key] ?>" id="iepsTotal" class="span12">
                              </td>
                              <td style="width: 66px;">
                                  <input type="text" name="retTotal[]" value="<?php echo $_POST['retTotal'][$key] ?>" id="retTotal" class="span12" readonly>
                              </td>
                              <td>
                                  <span><?php echo String::formatoNumero($_POST['importe'][$key]) ?></span>
                                  <input type="hidden" name="importe[]" value="<?php echo $_POST['importe'][$key] ?>" id="importe" class="span12 vpositive">
                                  <input type="hidden" name="total[]" value="<?php echo $_POST['total'][$key] ?>" id="total" class="span12 vpositive">
                              </td>
                              <td>
                                  <input type="text" name="observacion[]" value="<?php echo $_POST['observacion'][$key] ?>" id="observacion" class="span12">
                              </td>
                              <td style="width: 35px;"><button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button></td>
                            </tr>
                         <?php }} ?>
                      </tbody>
                    </table>
                  </div>
                </div>
               </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->

          <div class="row-fluid">
            <div class="span12">
              <table class="table">
                <thead>
                  <tr>
                    <th style="background-color:#FFF !important;">TOTAL CON LETRA</th>
                    <th style="background-color:#FFF !important;">TOTALES</th>
                    <th style="background-color:#FFF !important;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td rowspan="7">
                        <textarea name="totalLetra" rows="5" class="nokey" style="width:98%;max-width:98%;" id="totalLetra" readonly><?php echo set_value('totalLetra', '');?></textarea>
                    </td>
                  </tr>
                  <tr>
                    <td><em>Subtotal</em></td>
                    <td id="importe-format"><?php echo String::formatoNumero(set_value('totalImporte', 0))?></td>
                    <input type="hidden" name="totalImporte" id="totalImporte" value="<?php echo set_value('totalImporte', 0); ?>">
                  </tr>
                  <tr>
                    <td>IVA</td>
                    <td id="traslado-format"><?php echo String::formatoNumero(set_value('totalImpuestosTrasladados', 0))?></td>
                    <input type="hidden" name="totalImpuestosTrasladados" id="totalImpuestosTrasladados" value="<?php echo set_value('totalImpuestosTrasladados', 0); ?>">
                  </tr>
                  <tr>
                    <td>IEPS</td>
                    <td id="ieps-format"><?php echo String::formatoNumero(set_value('totalIeps', 0))?></td>
                    <input type="hidden" name="totalIeps" id="totalIeps" value="<?php echo set_value('totalIeps', 0); ?>">
                  </tr>
                  <tr>
                    <td>RET.</td>
                    <td id="retencion-format"><?php echo String::formatoNumero(set_value('totalRetencion', 0))?></td>
                    <input type="hidden" name="totalRetencion" id="totalRetencion" value="<?php echo set_value('totalRetencion', 0); ?>">
                  </tr>
                  <tr style="font-weight:bold;font-size:1.2em;">
                    <td>TOTAL</td>
                    <td id="total-format"><?php echo String::formatoNumero(set_value('totalOrden', 0))?></td>
                    <input type="hidden" name="totalOrden" id="totalOrden" value="<?php echo set_value('totalOrden', 0); ?>">
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->

</div>

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