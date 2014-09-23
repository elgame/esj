<div id="content" class="span<?php echo isset($_GET['idf']) ? '12' : '10' ?>">

  <?php
    $titulo = 'Agregar orden de compra';
    if (! isset($_GET['idf'])){ ?>
    <div>
      <ul class="breadcrumb">
        <li>
          <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
        </li>
        <li>
          <?php if ( ! isset($_GET['w']) || $_GET['w'] === 'r'){
                    $titulo = 'Agregar orden de requisición';
          ?>
            <a href="<?php echo base_url('panel/compras_requisicion/requisicion'); ?>">Ordenes de Requisicion</a> <span class="divider">/</span>
          <?php } else {
                    $titulo = 'Agregar orden de compra';
            ?>
            <a href="<?php echo base_url('panel/compras_ordenes/'); ?>">Ordenes de Compras</a> <span class="divider">/</span>
          <?php } ?>
        </li>
        <li>Agregar</li>
      </ul>
    </div>
  <?php } ?>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> <?php echo $titulo; ?></h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/compras_requisicion/agregar/?'.String::getVarsLink(array('msg'))); ?>" method="POST" id="form">

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
                    <input type="text" name="cliente" class="span11" id="cliente" value="<?php echo set_value('cliente', isset($factura) ? $factura['info']->cliente->nombre_fiscal : '') ?>" placeholder="">
                  </div>
                </div>
                  <input type="hidden" name="clienteId" id="clienteId" value="<?php echo set_value('clienteId', isset($factura) ? $factura['info']->cliente->id_cliente : '') ?>">
              </div>

              <div class="control-group">
                <label class="control-label" for="descripcion">Observaciones</label>
                <div class="controls">
                  <div class="input-append span12">
                    <textarea name="descripcion" class="span11" id="descripcion"><?php echo set_value('descripcion') ?></textarea>
                  </div>
                </div>
              </div>

              <div class="control-group" id="verVehiculoChk" <?php echo (set_select('tipoOrden', 'd')==' selected="selected"'? '': 'style="display:none;"'); ?>>
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
                    <option value="d" <?php echo set_select('tipoOrden', 'd'); ?>>Servicios</option>
                    <option value="oc" <?php echo set_select('tipoOrden', 'oc'); ?>>Orden de compra</option>
                    <option value="f" <?php echo set_select('tipoOrden', 'f'); ?> <?php echo (isset($ordenFlete) && $ordenFlete) ? 'selected': '' ?>>Fletes</option>
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

              <div class="control-group" <?php echo (set_select('tipoOrden', 'f')==' selected="selected"' || (isset($ordenFlete) && $ordenFlete) ? '': 'style="display:none;"'); ?> id="fletesFactura">
                <label class="control-label" for="tipoPago">Ligar Factura/Remision</label>
                <div class="controls">
                  <button type="button" class="btn btn-info" id="show-facturas">Buscar</button>
                  <span id="facturaLigada" style="cursor:pointer;">
                    <?php if(isset($_POST['remfacs'])){
                      echo $_POST['remfacs_folio'].' <input type="hidden" name="remfacs" value="'.$_POST['remfacs'].'"><input type="hidden" name="remfacs_folio" value="'.$_POST['remfacs_folio'].'">';
                    } else if (isset($ordenFlete) && $ordenFlete) {
                      echo $factura['info']->serie.$factura['info']->folio.' | <input type="hidden" name="remfacs" value="t:'.$factura['info']->id_factura.'|"><input type="hidden" name="remfacs_folio" value="'.$factura['info']->serie.$factura['info']->folio.' | ">';
                    } ?>
                  </span>
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



          <div class="row-fluid" id="productos">  <!-- Box Productos -->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-barcode"></i> Productos <span id="show_info_prod" style="display:none;"><i class="icon-hand-right"></i> <span>Existencia: 443 | Stok: 43</span></span></h2>
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
                        <input type="text" class="span10" id="fconcepto" placeholder="Producto / Descripción">
                        <a href="<?php echo base_url('panel/productos').'?modal=true' ?>" rel="superbox-70x550" class="btn btn-info" type="button" data-rel="tooltip" data-title="Agregar Producto"><i class="icon-plus" ></i></a>
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
                          <th rowspan="2" style="vertical-align: middle;">CODIGO AREA</th>
                          <th rowspan="2" style="vertical-align: middle;">CODIGO PROD.</th>
                          <th rowspan="2" style="vertical-align: middle;">CANT.</th>
                          <th rowspan="2" style="vertical-align: middle;">UNIDAD PRESEN.</th>
                          <th rowspan="2" style="vertical-align: middle;">PRODUCTO</th>
                          <th colspan="2">
                            <div class="input-append span12">
                              <input type="text" name="proveedor1" class="span10" id="proveedor1" value="<?php echo set_value('proveedor1') ?>" placeholder="Proveedor 1"><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                              <input type="hidden" name="proveedorId1" id="proveedorId1" value="<?php echo set_value('proveedorId1') ?>">
                            </div>
                          </th>
                          <th colspan="2">
                            <div class="input-append span12">
                              <input type="text" name="proveedor2" class="span10" id="proveedor2" value="<?php echo set_value('proveedor2') ?>" placeholder="Proveedor 2"><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                              <input type="hidden" name="proveedorId2" id="proveedorId2" value="<?php echo set_value('proveedorId2') ?>">
                            </div>
                          </th>
                          <th colspan="2">
                            <div class="input-append span12">
                              <input type="text" name="proveedor3" class="span10" id="proveedor3" value="<?php echo set_value('proveedor3') ?>" placeholder="Proveedor 3"><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                              <input type="hidden" name="proveedorId3" id="proveedorId3" value="<?php echo set_value('proveedorId3') ?>">
                            </div>
                          </th>
                          <th></th>
                        </tr>
                        <tr>
                          <th>P.U.</th>
                          <th>IMPORTE</th>
                          <th>P.U.</th>
                          <th>IMPORTE</th>
                          <th>P.U.</th>
                          <th>IMPORTE</th>
                          <!-- <th>IVA</th>
                          <th>IEPS (%)</th>
                          <th>RET 4%</th>
                          <th>DESCRIP</th> -->
                          <th></th>
                        </tr>
                      </thead>
                      <tbody class="bodyproducs">
                        <?php if (isset($_POST['concepto'])) {
                              foreach ($_POST['concepto'] as $key => $concepto) { ?>


                          <tr class="rowprod">
                            <td style="width: 60px;">
                              <input type="text" name="codigoArea[]" value="<?php echo $_POST['codigoArea'][$key] ?>" id="codigoArea" class="span12 showCodigoArea" readonly>
                              <input type="hidden" name="codigoAreaId[]" value="<?php echo $_POST['codigoAreaId'][$key] ?>" id="codigoAreaId" class="span12">
                            </td>
                            <td style="width: 60px;">
                              <?php echo $_POST['codigo'][$key] ?>
                              <input type="hidden" name="codigo[]" value="<?php echo $_POST['codigo'][$key] ?>" class="span12">
                              <input type="hidden" name="tipo_cambio[]" value="<?php echo $_POST['tipo_cambio'][$key] ?>" class="span12">
                              <input type="hidden" name="prodIdOrden[]" value="<?php echo $_POST['prodIdOrden'][$key] ?>" class="span12">
                              <input type="hidden" name="prodIdNumRow[]" value="<?php echo $_POST['prodIdNumRow'][$key] ?>" class="span12">
                            </td>
                            <td style="width: 65px;">
                                <input type="number" step="any" name="cantidad[]" value="<?php echo $_POST['cantidad'][$key] ?>" id="cantidad" class="span12 vpositive" min="0">
                            </td>
                            <td style="width: 70px;">
                              <select name="unidad[]" id="unidad" class="span12">
                                <?php foreach ($unidades as $unidad) { ?>
                                  <option value="<?php echo $unidad->id_unidad ?>" <?php echo $_POST['unidad'][$key] == $unidad->id_unidad ? 'selected' : ''; ?>><?php echo $unidad->nombre ?></option>
                                <?php } ?>
                              </select>
                              <select name="presentacion[]" class="span12">
                                <option value="<?php echo $_POST['presentacion'][$key]?>" data-cantidad="<?php echo $_POST['presentacionCant'][$key] ?>"><?php echo $_POST['presentacionText'][$key] ?></option>
                              </select>
                              <input type="hidden" name="presentacionCant[]" value="<?php echo $_POST['presentacionCant'][$key] ?>" id="presentacionCant" class="span12">
                              <input type="hidden" name="presentacionText[]" value="<?php echo $_POST['presentacionText'][$key] ?>" id="presentacionText" class="span12">
                            </td>
                            <td>
                              <?php echo $concepto ?>
                              <input type="hidden" name="concepto[]" value="<?php echo $concepto ?>" id="concepto" class="span12">
                              <input type="hidden" name="productoId[]" value="<?php echo $_POST['productoId'][$key] ?>" id="productoId" class="span12">
                            </td>
                            <td style="width: 90px;">
                              <input type="text" name="valorUnitario1[]" value="<?php echo $_POST['valorUnitario1'][$key] ?>" id="valorUnitario1" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo String::formatoNumero($_POST['importe1'][$key], 2, '$', false); ?></span>
                              <input type="hidden" name="importe1[]" value="<?php echo $_POST['importe1'][$key] ?>" id="importe1" class="span12 provimporte vpositive">
                              <input type="hidden" name="total1[]" value="<?php echo $_POST['total1'][$key] ?>" id="total1" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal1[]" value="<?php echo $_POST['trasladoTotal1'][$key] ?>" id="trasladoTotal1" class="span12">
                              <input type="hidden" name="iepsTotal1[]" value="<?php echo $_POST['iepsTotal1'][$key] ?>" id="iepsTotal1" class="span12">
                              <input type="hidden" name="retTotal1[]" value="<?php echo $_POST['retTotal1'][$key] ?>" id="retTotal1" class="span12" readonly>
                            </td>
                            <td style="width: 90px;">
                              <input type="text" name="valorUnitario2[]" value="<?php echo $_POST['valorUnitario2'][$key] ?>" id="valorUnitario2" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo String::formatoNumero($_POST['importe2'][$key], 2, '$', false); ?></span>
                              <input type="hidden" name="importe2[]" value="<?php echo $_POST['importe2'][$key] ?>" id="importe2" class="span12 provimporte vpositive">
                              <input type="hidden" name="total2[]" value="<?php echo $_POST['total2'][$key] ?>" id="total2" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal2[]" value="<?php echo $_POST['trasladoTotal2'][$key] ?>" id="trasladoTotal2" class="span12">
                              <input type="hidden" name="iepsTotal2[]" value="<?php echo $_POST['iepsTotal2'][$key] ?>" id="iepsTotal2" class="span12">
                              <input type="hidden" name="retTotal2[]" value="<?php echo $_POST['retTotal2'][$key] ?>" id="retTotal2" class="span12" readonly>
                            </td>
                            <td style="width: 90px;">
                              <input type="text" name="valorUnitario3[]" value="<?php echo $_POST['valorUnitario3'][$key] ?>" id="valorUnitario3" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo String::formatoNumero($_POST['importe3'][$key], 2, '$', false); ?></span>
                              <input type="hidden" name="importe3[]" value="<?php echo $_POST['importe3'][$key] ?>" id="importe3" class="span12 provimporte vpositive">
                              <input type="hidden" name="total3[]" value="<?php echo $_POST['total3'][$key] ?>" id="total3" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal3[]" value="<?php echo $_POST['trasladoTotal3'][$key] ?>" id="trasladoTotal3" class="span12">
                              <input type="hidden" name="iepsTotal3[]" value="<?php echo $_POST['iepsTotal3'][$key] ?>" id="iepsTotal3" class="span12">
                              <input type="hidden" name="retTotal3[]" value="<?php echo $_POST['retTotal3'][$key] ?>" id="retTotal3" class="span12" readonly>
                            </td>
                            <td style="width: 35px;">
                              <div style="position:relative;"><button type="button" class="btn btn-info" id="btnListOtros"><i class="icon-list"></i></button>
                                <div class="popover fade left in" style="top:-55.5px;left:-411px;">
                                  <div class="arrow"></div><h3 class="popover-title">Otros</h3>
                                  <div class="popover-content">
                                    <table>
                                      <tr>
                                        <td style="width: 66px;">IVA</td>
                                        <td style="width: 66px;">IEPS</td>
                                        <td>DESCRIP</td>
                                      </tr>
                                      <tr>
                                        <td style="width: 66px;">
                                            <select name="traslado[]" id="traslado" class="span12">
                                              <option value="0" <?php echo $_POST['traslado'][$key] === '0' ? 'selected' : '' ?>>0%</option>
                                              <option value="11" <?php echo $_POST['traslado'][$key] === '11' ? 'selected' : ''?>>11%</option>
                                              <option value="16" <?php echo $_POST['traslado'][$key] === '16' ? 'selected' : ''?>>16%</option>
                                            </select>
                                            <input type="hidden" name="trasladoPorcent[]" value="<?php echo $_POST['trasladoPorcent'][$key] ?>" id="trasladoPorcent" class="span12">
                                        </td>
                                        <td style="width: 66px;">
                                            <input type="text" name="iepsPorcent[]" value="<?php echo $_POST['iepsPorcent'][$key] ?>" id="iepsPorcent" class="span12">
                                        </td>
                                        <td>
                                          <input type="text" name="observacion[]" value="<?php echo $_POST['observacion'][$key] ?>" id="observacion" class="span12">
                                        </td>
                                      </tr>
                                    </table>
                                  </div>
                                </div>
                              </div>
                              <button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button>
                            </td>
                          </tr>
                         <?php }} ?>

                        <tfoot>
                          <tr>
                              <td colspan="5" style="text-align: right;"><em>Subtotal</em></td>
                              <td id="importe-format1" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImporte1', 0))?></td>
                                <input type="hidden" name="totalImporte1" id="totalImporte1" value="<?php echo set_value('totalImporte1', 0); ?>">
                              <td id="importe-format2" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImporte2', 0))?></td>
                                <input type="hidden" name="totalImporte2" id="totalImporte2" value="<?php echo set_value('totalImporte2', 0); ?>">
                              <td id="importe-format3" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImporte3', 0))?></td>
                                <input type="hidden" name="totalImporte3" id="totalImporte3" value="<?php echo set_value('totalImporte3', 0); ?>">
                            </tr>
                            <tr>
                              <td colspan="5" style="text-align: right;">IVA</td>
                              <td id="traslado-format1" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImpuestosTrasladados1', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados1" id="totalImpuestosTrasladados1" value="<?php echo set_value('totalImpuestosTrasladados1', 0); ?>">
                              <td id="traslado-format2" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImpuestosTrasladados2', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados2" id="totalImpuestosTrasladados2" value="<?php echo set_value('totalImpuestosTrasladados2', 0); ?>">
                              <td id="traslado-format3" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImpuestosTrasladados3', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados3" id="totalImpuestosTrasladados3" value="<?php echo set_value('totalImpuestosTrasladados3', 0); ?>">
                            </tr>
                            <tr>
                              <td colspan="5" style="text-align: right;">IEPS</td>
                              <td id="ieps-format1" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalIeps1', 0))?></td>
                                <input type="hidden" name="totalIeps1" id="totalIeps1" value="<?php echo set_value('totalIeps1', 0); ?>">
                              <td id="ieps-format2" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalIeps2', 0))?></td>
                                <input type="hidden" name="totalIeps2" id="totalIeps2" value="<?php echo set_value('totalIeps2', 0); ?>">
                              <td id="ieps-format3" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalIeps3', 0))?></td>
                                <input type="hidden" name="totalIeps3" id="totalIeps3" value="<?php echo set_value('totalIeps3', 0); ?>">
                            </tr>
                            <tr>
                              <td colspan="5" style="text-align: right;">RET.</td>
                              <td id="retencion-format1" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalRetencion1', 0))?></td>
                                <input type="hidden" name="totalRetencion1" id="totalRetencion1" value="<?php echo set_value('totalRetencion1', 0); ?>">
                              <td id="retencion-format2" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalRetencion2', 0))?></td>
                                <input type="hidden" name="totalRetencion2" id="totalRetencion2" value="<?php echo set_value('totalRetencion2', 0); ?>">
                              <td id="retencion-format3" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalRetencion3', 0))?></td>
                                <input type="hidden" name="totalRetencion3" id="totalRetencion3" value="<?php echo set_value('totalRetencion3', 0); ?>">
                            </tr>
                            <tr style="font-weight:bold;font-size:1.2em;">
                              <td colspan="5" style="text-align: right;">TOTAL</td>
                              <td id="total-format1" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalOrden1', 0))?></td>
                                <input type="hidden" name="totalOrden1" id="totalOrden1" value="<?php echo set_value('totalOrden1', 0); ?>">
                              <td id="total-format2" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalOrden2', 0))?></td>
                                <input type="hidden" name="totalOrden2" id="totalOrden2" value="<?php echo set_value('totalOrden2', 0); ?>">
                              <td id="total-format3" colspan="2" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalOrden3', 0))?></td>
                                <input type="hidden" name="totalOrden3" id="totalOrden3" value="<?php echo set_value('totalOrden3', 0); ?>">
                            </tr>
                        </tfoot>
                      </tbody>
                    </table>
                  </div>
                </div>

               </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->



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



  <!-- Modal -->
  <div id="modal-facturas" class="modal modal-w50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Facturas y Remisiones</h3>
    </div>
    <div class="modal-body">
      <div class="row-fluid">
        <input type="text" id="filFolio" class="pull-left" placeholder="Folio"> <span class="pull-left"> | </span>
        <label class="pull-left"><input type="radio" name="filTipoFacturas" class="filTipoFacturas" value="f" checked>Facturas</label>
        <label class="pull-left"><input type="radio" name="filTipoFacturas" class="filTipoFacturas" value="r">Remision</label>
      </div>
      <div class="row-fluid">
        <table class="table table-hover table-condensed" id="table-facturas">
          <thead>
            <tr>
              <th></th>
              <th style="width:70px;">Fecha</th>
              <th># Folio</th>
              <th>Clientes</th>
            </tr>
          </thead>
          <tbody>
            <!-- <tr>
              <tr><input type="checkbox" value="" class="" id=""><input type="hidden" value=""></tr>
              <tr>2013-10-22</tr>
              <tr>9</tr>
              <tr>100</tr>
            </tr> -->
          </tbody>
        </table>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="BtnAddFactura">Seleccionar</button>
    </div>
  </div><!--/modal pallets -->

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