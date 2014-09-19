<div id="content" class="span<?php echo isset($_GET['idf']) ? '12' : '10' ?>">

  <?php
    $autorizar_active = $this->usuarios_model->tienePrivilegioDe("", "compras_ordenes/autorizar/");
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

        <form class="form-horizontal" action="<?php echo base_url('panel/compras_requisicion/modificar/?'.String::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $orden['info'][0]->empresa) ?>" placeholder="" autofocus><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                  <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $orden['info'][0]->id_empresa) ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="solicito">Solicito</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="solicito" class="span11" id="solicito" value="<?php echo set_value('solicito', $orden['info'][0]->empleado_solicito) ?>" placeholder="">
                  </div>
                </div>
              </div>
              <input type="hidden" id="form-modif" value="true">

              <div class="control-group">
                <label class="control-label" for="departamento">Departamento</label>
                <div class="controls">
                  <select name="departamento" class="span11" id="departamento">
                    <option></option>
                    <?php foreach ($departamentos as $key => $depa) { ?>
                      <option value="<?php echo $depa->id_departamento ?>" <?php echo set_select('departamento', $depa->id_departamento, $orden['info'][0]->id_departamento == $depa->id_departamento ? true : false); ?>><?php echo $depa->nombre ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="cliente">Cliente</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="cliente" class="span11" id="cliente" value="<?php echo set_value('cliente', $orden['info'][0]->cliente) ?>" placeholder="">
                  </div>
                </div>
                  <input type="hidden" name="clienteId" id="clienteId" value="<?php echo set_value('clienteId', $orden['info'][0]->id_cliente) ?>">
              </div>

              <div class="control-group">
                <label class="control-label" for="descripcion">Observaciones</label>
                <div class="controls">
                  <div class="input-append span12">
                    <textarea name="descripcion" class="span11" id="descripcion"><?php echo set_value('descripcion', $orden['info'][0]->descripcion) ?></textarea>
                  </div>
                </div>
              </div>

              <div class="control-group" id="verVehiculoChk" <?php echo (set_select('tipoOrden', 'd')==' selected="selected"'? '': 'style="display:none;"'); ?>>
                <label class="control-label" for="descripcion">Vehiculo</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="checkbox" name="es_vehiculo" id="es_vehiculo" data-uniform="false" value="si" data-next="fecha" <?php echo set_checkbox('es_vehiculo', 'si', $orden['info'][0]->id_vehiculo > 0 ? true : false); ?>>
                  </div>
                </div>
              </div>
              
            <?php if ($autorizar_active){ ?>
              <div class="control-group">
                <label class="control-label" for="autorizo">Autoriza</label>
                <div class="controls">
                  <input type="text" name="autorizo" class="span11" id="autorizo" value="<?php echo set_value('autorizo', $orden['info'][0]->autorizo) ?>">
                  <input type="hidden" name="autorizoId" id="autorizoId" value="<?php echo set_value('autorizoId', $orden['info'][0]->id_autorizo) ?>" required>
                </div>
              </div>
            <?php } ?>

            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', isset($orden['info'][0]->fecha) ? substr(str_replace(' ', 'T', $orden['info'][0]->fecha), 0, 16) : $fecha); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="tipoOrden">Tipo de Orden</label>
                <div class="controls">
                  <select name="tipoOrden" class="span9" id="tipoOrden">
                    <option value="p" <?php echo set_select('tipoOrden', 'p', $orden['info'][0]->tipo_orden === 'p' ? true : false); ?>>Productos</option>
                    <option value="d" <?php echo set_select('tipoOrden', 'd', $orden['info'][0]->tipo_orden === 'd' ? true : false); ?>>Servicios</option>
                    <option value="oc" <?php echo set_select('tipoOrden', 'oc', $orden['info'][0]->tipo_orden === 'oc' ? true : false); ?>>Orden de compra</option>
                    <option value="f" <?php echo set_select('tipoOrden', 'f', $orden['info'][0]->tipo_orden === 'f' ? true : false); ?> <?php echo (isset($ordenFlete) && $ordenFlete) ? 'selected': '' ?>>Fletes</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio">Folio</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo set_value('folio', $orden['info'][0]->folio); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="tipoPago">Tipo de Pago</label>
                <div class="controls">
                  <select name="tipoPago" class="span9" id="tipoPago">
                    <option value="cr" <?php echo set_select('tipoPago', 'cr', $orden['info'][0]->tipo_pago === 'cr' ? true : false); ?>>Credito</option>
                    <option value="co" <?php echo set_select('tipoPago', 'co', $orden['info'][0]->tipo_pago === 'co' ? true : false); ?>>Contado</option>
                  </select>
                </div>
              </div>

              <div class="control-group" <?php echo ($orden['info'][0]->tipo_orden === 'f'? '': 'style="display:none;"'); ?> id="fletesFactura">
                <label class="control-label" for="tipoPago">Ligar Factura/Remision</label>
                <div class="controls">
                  <button type="button" class="btn btn-info" id="show-facturas">Buscar</button>
                  <span id="facturaLigada" style="cursor:pointer;">
                    <?php
                    $folios = '';
                    foreach ($orden['info'][0]->facturasligadas as $key => $value)
                    {
                      $folios .= $value->serie.$value->folio.' | ';
                    }
                      echo $folios.' <input type="hidden" name="remfacs" value="'.$orden['info'][0]->ids_facrem.'"><input type="hidden" name="remfacs_folio" value="'.$folios.'">';
                    ?>
                  </span>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
                <?php if ($autorizar_active){ ?>
                      <br><br><button type="button" id="btnAutorizar" class="btn btn-info btn-large btn-block" style="width:100%;">Autorizar - Crear O. Compras</button>
                      <input type="hidden" name="txtBtnAutorizar" id="txtBtnAutorizar" value="false"> 
                <?php } ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row-fluid" id="groupVehiculo" style="display: <?php echo isset($_POST['es_vehiculo']) ? ($_POST['es_vehiculo'] === 'si' ? 'block' : 'none') : ($orden['info'][0]->id_vehiculo > 0 ? 'block' : 'none') ?>;">  <!-- Box Productos -->
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
                        <input type="text" name="vehiculo" class="span7 sikey" id="vehiculo" value="<?php echo set_value('vehiculo', ($orden['info'][0]->id_vehiculo) ? $orden['info'][0]->placa.' '.$orden['info'][0]->modelo.' '.$orden['info'][0]->marca : '') ?>" placeholder="Vehiculos" data-next="tipo_vehiculo" style="float: left;">

                        <select name="tipo_vehiculo" id="tipo_vehiculo" class="span4 sikey" style="float: right;" data-next="dkilometros">
                          <option value="ot" <?php echo set_select('tipo_vehiculo', 'ot', $orden['info'][0]->tipo_vehiculo === 'ot' ? true : false) ?>>REFACCIONES Y OTROS</option>
                          <option value="g" <?php echo set_select('tipo_vehiculo', 'g', $orden['info'][0]->tipo_vehiculo === 'g' ? true : false) ?>>GASOLINA</option>
                          <option value="d" <?php echo set_select('tipo_vehiculo', 'd', $orden['info'][0]->tipo_vehiculo === 'd' ? true : false) ?>>DIESEL</option>
                        </select>
                      </div>
                        <input type="hidden" name="vehiculoId" id="vehiculoId" value="<?php echo set_value('vehiculoId', $orden['info'][0]->id_vehiculo) ?>">
                    </div>
                  </div>
                </div><!--/row-fluid -->

                <div class="row-fluid" id="group_gasolina" style="display: <?php echo isset($_POST['tipo_vehiculo']) ? ($_POST['tipo_vehiculo'] === 'ot' ? 'none' : 'block') : ($orden['info'][0]->tipo_vehiculo === 'ot' ? 'none' : 'block') ?>;">
                  <div class="span4">
                    <div class="control-group">
                      <div class="controls span9">
                        Kilometros <input type="text" name="dkilometros" class="span12 sikey vpos-int" id="dkilometros" value="<?php echo set_value('dkilometros', isset($orden['info'][0]->gasolina[0]->kilometros) ? $orden['info'][0]->gasolina[0]->kilometros : ''); ?>" maxlength="10" data-next="dlitros">
                      </div>
                    </div>
                  </div>
                  <div class="span4">
                    <div class="control-group">
                      <div class="controls span9">
                        Litros <input type="text" name="dlitros" class="span12 sikey vpositive" id="dlitros" value="<?php echo set_value('dlitros', isset($orden['info'][0]->gasolina[0]->litros) ? $orden['info'][0]->gasolina[0]->litros : ''); ?>" maxlength="10" data-next="dprecio">
                      </div>
                    </div>
                  </div>
                  <div class="span4">
                    <div class="control-group">
                      <div class="controls span9">
                        Precio <input type="text" name="dprecio" class="span12 sikey vpositive" id="dprecio" value="<?php echo set_value('dprecio', isset($orden['info'][0]->gasolina[0]->precio) ? $orden['info'][0]->gasolina[0]->precio : ''); ?>" maxlength="10" data-next="fconcepto">
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
                          <th rowspan="2" style="vertical-align: middle;">CODIGO AREA</th>
                          <th rowspan="2" style="vertical-align: middle;">CODIGO PROD.</th>
                          <th rowspan="2" style="vertical-align: middle;">CANT.</th>
                          <th rowspan="2" style="vertical-align: middle;">UNIDAD PRESEN.</th>
                          <th rowspan="2" style="vertical-align: middle;">PRODUCTO</th>
                          <th colspan="<?php echo $autorizar_active?'3':'2'; ?>">
                            <div class="input-append span12">
                              <input type="text" name="proveedor1" class="span10" id="proveedor1" value="<?php echo set_value('proveedor1', $orden['info'][0]->proveedores[0]['nombre_fiscal']) ?>" placeholder="Proveedor 1"><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                              <input type="hidden" name="proveedorId1" id="proveedorId1" value="<?php echo set_value('proveedorId1', $orden['info'][0]->proveedores[0]['id_proveedor']) ?>">
                            </div>
                          </th>
                          <th colspan="<?php echo $autorizar_active?'3':'2'; ?>">
                            <div class="input-append span12">
                              <input type="text" name="proveedor2" class="span10" id="proveedor2" value="<?php echo set_value('proveedor2', $orden['info'][0]->proveedores[1]['nombre_fiscal']) ?>" placeholder="Proveedor 2"><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                              <input type="hidden" name="proveedorId2" id="proveedorId2" value="<?php echo set_value('proveedorId2', $orden['info'][0]->proveedores[1]['id_proveedor']) ?>">
                            </div>
                          </th>
                          <th colspan="<?php echo $autorizar_active?'3':'2'; ?>">
                            <div class="input-append span12">
                              <input type="text" name="proveedor3" class="span10" id="proveedor3" value="<?php echo set_value('proveedor3', $orden['info'][0]->proveedores[2]['nombre_fiscal']) ?>" placeholder="Proveedor 3"><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                              <input type="hidden" name="proveedorId3" id="proveedorId3" value="<?php echo set_value('proveedorId3', $orden['info'][0]->proveedores[2]['id_proveedor']) ?>">
                            </div>
                          </th>
                          <th></th>
                        </tr>
                        <tr>
                          <th <?php echo $autorizar_active? 'colspan="2"': ''; ?>>P.U.</th>
                          <th>IMPORTE</th>
                          <th <?php echo $autorizar_active? 'colspan="2"': ''; ?>>P.U.</th>
                          <th>IMPORTE</th>
                          <th <?php echo $autorizar_active? 'colspan="2"': ''; ?>>P.U.</th>
                          <th>IMPORTE</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody class="bodyproducs">
                        <?php if (isset($orden['info'][0]->productos)) {
                              foreach ($orden['info'][0]->productos as $key => $concepto) { ?>

                            
                          <tr class="rowprod">
                            <td style="width: 60px;">
                              <input type="text" name="codigoArea[]" value="<?php echo $concepto->codigo_fin ?>" id="codigoArea" class="span12 showCodigoArea" readonly>
                              <input type="hidden" name="codigoAreaId[]" value="<?php echo $concepto->id_area ?>" id="codigoAreaId" class="span12">
                            </td>
                            <td style="width: 60px;">
                              <?php echo $concepto->codigo ?>
                              <input type="hidden" name="codigo[]" value="<?php echo $concepto->codigo ?>" class="span12">
                              <input type="hidden" name="tipo_cambio[]" value="<?php echo $concepto->tipo_cambio ?>" class="span12">
                              <input type="hidden" name="prodIdOrden[]" value="<?php echo $concepto->id_requisicion ?>" class="span12 prodIdOrden">
                              <input type="hidden" name="prodIdNumRow[]" value="<?php echo $concepto->num_row ?>" class="span12">
                            </td>
                            <td style="width: 65px;">
                                <input type="number" step="any" name="cantidad[]" value="<?php echo ($concepto->cantidad/($concepto->presen_cantidad>0?$concepto->presen_cantidad:1)) ?>" id="cantidad" class="span12 vpositive" min="0">
                            </td>
                            <td style="width: 70px;">
                              <select name="unidad[]" id="unidad" class="span12">
                                <?php foreach ($unidades as $unidad) { ?>
                                  <option value="<?php echo $unidad->id_unidad ?>" <?php echo $concepto->id_unidad == $unidad->id_unidad ? 'selected' : ''; ?>><?php echo $unidad->nombre ?></option>
                                <?php } ?>
                              </select>
                              <select name="presentacion[]" class="span12">
                                <option value="<?php echo $concepto->id_presentacion ?>" data-cantidad="<?php echo $concepto->presen_cantidad ?>"><?php echo $concepto->presentacion ?></option>
                              </select>
                              <input type="hidden" name="presentacionCant[]" value="<?php echo $concepto->presen_cantidad ?>" id="presentacionCant" class="span12">
                              <input type="hidden" name="presentacionText[]" value="<?php echo $concepto->presentacion ?>" id="presentacionText" class="span12">
                            </td>
                            <td>
                              <?php echo $concepto->descripcion ?>
                              <input type="hidden" name="concepto[]" value="<?php echo $concepto->descripcion ?>" id="concepto" class="span12">
                              <input type="hidden" name="productoId[]" value="<?php echo $concepto->id_producto ?>" id="productoId" class="span12">
                            </td>
                          <?php if ($autorizar_active){ ?>
                            <td style="width: 10px;">
                              <input type="radio" name="prodSelOrden<?php echo $concepto->num_row; ?>[]" value="<?php echo $orden['info'][0]->proveedores[0]['id_proveedor'] ?>" class="prodSelOrden prodSelOrden1" checked data-uniform="false">
                            </td>
                          <?php } ?>
                            <td style="width: 90px;">
                              <?php $precio_unitario = $concepto->{'precio_unitario'.$orden['info'][0]->proveedores[0]['id_proveedor']} * 
                                                      ($concepto->presen_cantidad>0?$concepto->presen_cantidad:1);  ?>
                              <input type="text" name="valorUnitario1[]" value="<?php echo $precio_unitario; ?>" id="valorUnitario1" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo String::formatoNumero($concepto->{'importe'.$orden['info'][0]->proveedores[0]['id_proveedor']}, 2, '$', false); ?></span>
                              <input type="hidden" name="importe1[]" value="<?php echo $concepto->{'importe'.$orden['info'][0]->proveedores[0]['id_proveedor']} ?>" id="importe1" class="span12 provimporte vpositive">
                              <input type="hidden" name="total1[]" value="<?php echo $concepto->{'total'.$orden['info'][0]->proveedores[0]['id_proveedor']} ?>" id="total1" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal1[]" value="<?php echo $concepto->{'iva'.$orden['info'][0]->proveedores[0]['id_proveedor']} ?>" id="trasladoTotal1" class="span12">
                              <input type="hidden" name="iepsTotal1[]" value="<?php echo $concepto->{'ieps'.$orden['info'][0]->proveedores[0]['id_proveedor']} ?>" id="iepsTotal1" class="span12">
                              <input type="hidden" name="retTotal1[]" value="<?php echo $concepto->{'retencion_iva'.$orden['info'][0]->proveedores[0]['id_proveedor']} ?>" id="retTotal1" class="span12" readonly>
                            </td>
                          <?php if ($autorizar_active){ ?>
                            <td style="width: 10px;">
                              <input type="radio" name="prodSelOrden<?php echo $concepto->num_row; ?>[]" value="<?php echo $orden['info'][0]->proveedores[1]['id_proveedor'] ?>" class="prodSelOrden prodSelOrden2" data-uniform="false">
                            </td>
                          <?php } ?>
                            <td style="width: 90px;">
                              <?php $precio_unitario = $concepto->{'precio_unitario'.$orden['info'][0]->proveedores[1]['id_proveedor']} * 
                                                      ($concepto->presen_cantidad>0?$concepto->presen_cantidad:1);  ?>
                              <input type="text" name="valorUnitario2[]" value="<?php echo $precio_unitario ?>" id="valorUnitario2" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo String::formatoNumero($concepto->{'importe'.$orden['info'][0]->proveedores[1]['id_proveedor']}, 2, '$', false); ?></span>
                              <input type="hidden" name="importe2[]" value="<?php echo $concepto->{'importe'.$orden['info'][0]->proveedores[1]['id_proveedor']} ?>" id="importe2" class="span12 provimporte vpositive">
                              <input type="hidden" name="total2[]" value="<?php echo $concepto->{'total'.$orden['info'][0]->proveedores[1]['id_proveedor']} ?>" id="total2" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal2[]" value="<?php echo $concepto->{'iva'.$orden['info'][0]->proveedores[1]['id_proveedor']} ?>" id="trasladoTotal2" class="span12">
                              <input type="hidden" name="iepsTotal2[]" value="<?php echo $concepto->{'ieps'.$orden['info'][0]->proveedores[1]['id_proveedor']} ?>" id="iepsTotal2" class="span12">
                              <input type="hidden" name="retTotal2[]" value="<?php echo $concepto->{'retencion_iva'.$orden['info'][0]->proveedores[1]['id_proveedor']} ?>" id="retTotal2" class="span12" readonly>
                            </td>
                          <?php if ($autorizar_active){ ?>
                            <td style="width: 10px;">
                              <input type="radio" name="prodSelOrden<?php echo $concepto->num_row; ?>[]" value="<?php echo $orden['info'][0]->proveedores[2]['id_proveedor'] ?>" class="prodSelOrden prodSelOrden3" data-uniform="false">
                            </td>
                          <?php } ?>
                            <td style="width: 90px;">
                              <?php $precio_unitario = $concepto->{'precio_unitario'.$orden['info'][0]->proveedores[2]['id_proveedor']} * 
                                                      ($concepto->presen_cantidad>0?$concepto->presen_cantidad:1);  ?>
                              <input type="text" name="valorUnitario3[]" value="<?php echo $precio_unitario; ?>" id="valorUnitario3" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo String::formatoNumero($concepto->{'importe'.$orden['info'][0]->proveedores[2]['id_proveedor']}, 2, '$', false); ?></span>
                              <input type="hidden" name="importe3[]" value="<?php echo $concepto->{'importe'.$orden['info'][0]->proveedores[2]['id_proveedor']} ?>" id="importe3" class="span12 provimporte vpositive">
                              <input type="hidden" name="total3[]" value="<?php echo $concepto->{'total'.$orden['info'][0]->proveedores[2]['id_proveedor']} ?>" id="total3" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal3[]" value="<?php echo $concepto->{'iva'.$orden['info'][0]->proveedores[2]['id_proveedor']} ?>" id="trasladoTotal3" class="span12">
                              <input type="hidden" name="iepsTotal3[]" value="<?php echo $concepto->{'ieps'.$orden['info'][0]->proveedores[2]['id_proveedor']} ?>" id="iepsTotal3" class="span12">
                              <input type="hidden" name="retTotal3[]" value="<?php echo $concepto->{'retencion_iva'.$orden['info'][0]->proveedores[2]['id_proveedor']} ?>" id="retTotal3" class="span12" readonly>
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
                                              <option value="0" <?php echo $concepto->porcentaje_iva === '0' ? 'selected' : '' ?>>0%</option>
                                              <option value="11" <?php echo $concepto->porcentaje_iva === '11' ? 'selected' : ''?>>11%</option>
                                              <option value="16" <?php echo $concepto->porcentaje_iva === '16' ? 'selected' : ''?>>16%</option>
                                            </select>
                                            <input type="hidden" name="trasladoPorcent[]" value="<?php echo $concepto->porcentaje_iva ?>" id="trasladoPorcent" class="span12">
                                        </td>
                                        <td style="width: 66px;">
                                            <input type="text" name="iepsPorcent[]" value="<?php echo $concepto->porcentaje_ieps ?>" id="iepsPorcent" class="span12">
                                        </td>
                                        <td>
                                          <input type="text" name="observacion[]" value="<?php echo $concepto->observacion ?>" id="observacion" class="span12">
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
                              <td id="importe-format1" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImporte1', 0))?></td>
                                <input type="hidden" name="totalImporte1" id="totalImporte1" value="<?php echo set_value('totalImporte1', 0); ?>">
                              <td id="importe-format2" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImporte2', 0))?></td>
                                <input type="hidden" name="totalImporte2" id="totalImporte2" value="<?php echo set_value('totalImporte2', 0); ?>">
                              <td id="importe-format3" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImporte3', 0))?></td>
                                <input type="hidden" name="totalImporte3" id="totalImporte3" value="<?php echo set_value('totalImporte3', 0); ?>">
                            </tr>
                            <tr>
                              <td colspan="5" style="text-align: right;">IVA</td>
                              <td id="traslado-format1" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImpuestosTrasladados1', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados1" id="totalImpuestosTrasladados1" value="<?php echo set_value('totalImpuestosTrasladados1', 0); ?>">
                              <td id="traslado-format2" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImpuestosTrasladados2', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados2" id="totalImpuestosTrasladados2" value="<?php echo set_value('totalImpuestosTrasladados2', 0); ?>">
                              <td id="traslado-format3" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalImpuestosTrasladados3', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados3" id="totalImpuestosTrasladados3" value="<?php echo set_value('totalImpuestosTrasladados3', 0); ?>">
                            </tr>
                            <tr>
                              <td colspan="5" style="text-align: right;">IEPS</td>
                              <td id="ieps-format1" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalIeps1', 0))?></td>
                                <input type="hidden" name="totalIeps1" id="totalIeps1" value="<?php echo set_value('totalIeps1', 0); ?>">
                              <td id="ieps-format2" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalIeps2', 0))?></td>
                                <input type="hidden" name="totalIeps2" id="totalIeps2" value="<?php echo set_value('totalIeps2', 0); ?>">
                              <td id="ieps-format3" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalIeps3', 0))?></td>
                                <input type="hidden" name="totalIeps3" id="totalIeps3" value="<?php echo set_value('totalIeps3', 0); ?>">
                            </tr>
                            <tr>
                              <td colspan="5" style="text-align: right;">RET.</td>
                              <td id="retencion-format1" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalRetencion1', 0))?></td>
                                <input type="hidden" name="totalRetencion1" id="totalRetencion1" value="<?php echo set_value('totalRetencion1', 0); ?>">
                              <td id="retencion-format2" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalRetencion2', 0))?></td>
                                <input type="hidden" name="totalRetencion2" id="totalRetencion2" value="<?php echo set_value('totalRetencion2', 0); ?>">
                              <td id="retencion-format3" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalRetencion3', 0))?></td>
                                <input type="hidden" name="totalRetencion3" id="totalRetencion3" value="<?php echo set_value('totalRetencion3', 0); ?>">
                            </tr>
                            <tr style="font-weight:bold;font-size:1.2em;">
                              <td colspan="5" style="text-align: right;">TOTAL</td>
                              <td id="total-format1" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalOrden1', 0))?></td>
                                <input type="hidden" name="totalOrden1" id="totalOrden1" value="<?php echo set_value('totalOrden1', 0); ?>">
                              <td id="total-format2" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalOrden2', 0))?></td>
                                <input type="hidden" name="totalOrden2" id="totalOrden2" value="<?php echo set_value('totalOrden2', 0); ?>">
                              <td id="total-format3" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo String::formatoNumero(set_value('totalOrden3', 0))?></td>
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