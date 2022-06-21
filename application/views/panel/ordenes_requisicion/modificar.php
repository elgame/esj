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

        <form class="form-horizontal editando" action="<?php echo base_url('panel/compras_requisicion/modificar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

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

              <div class="control-group sucursales" style="display: none;">
                <label class="control-label" for="sucursalId">Sucursal </label>
                <div class="controls">
                  <div class="input-append span12">
                    <select name="sucursalId" class="span11" id="sucursalId" data-selected="<?php echo $orden['info'][0]->id_sucursal ?>">
                      <option></option>
                      <?php foreach ($sucursales as $key => $sucur) { ?>
                        <option value="<?php echo $sucur->id_sucursal ?>" selected="selected" <?php echo set_select('sucursalId', $sucur->id_departamento); ?>><?php echo $depa->nombre_fiscal ?></option>
                      <?php } ?>
                    </select>
                  </div>
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

              <div class="control-group">
                <label class="control-label" for="id_almacen">Almacen</label>
                <div class="controls">
                  <div class="input-append span12">
                    <select name="id_almacen" class="span11">
                    <?php $default = ($orden['info'][0]->id_almacen>0? $orden['info'][0]->id_almacen: '1');
                    foreach ($almacenes['almacenes'] as $key => $value) { ?>
                      <option value="<?php echo $value->id_almacen ?>" <?php echo set_select('id_almacen', $value->id_almacen, false, $default) ?>><?php echo $value->nombre ?></option>
                    <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="control-group grpes_receta" <?php echo ((isset($orden['info'][0]->tipo_orden) && $orden['info'][0]->tipo_orden == 'p')? '': 'style="display:none;"'); ?>>
                <label class="control-label" for="es_receta">Es receta</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="checkbox" name="es_receta" id="es_receta" value="true" data-uniform="false"
                      <?php $receta_activa = (isset($orden['info'][0]->es_receta) && $orden['info'][0]->es_receta == 't');
                        echo ($receta_activa? 'checked': ''); ?>>
                    <input type="text" name="no_recetas"
                    value="<?php echo (isset($orden['info'][0]->otros_datos->noRecetas)? implode(',', $orden['info'][0]->otros_datos->noRecetas) : '') ?>" id="no_recetas"
                    class="span11" placeholder="No de recetas (si es mas de una separar con ,)" <?php echo $receta_activa? '': 'readonly'; ?>>
                  </div>
                </div>
              </div>

              <div class="control-group classProyecto" <?php echo ((isset($orden['info'][0]->tipo_orden) && ($orden['info'][0]->tipo_orden == 'd' || $orden['info'][0]->tipo_orden == 'oc'))? '': 'style="display:none;"'); ?>>
                <label class="control-label" for="proyecto">Asignar a un Proyecto</label>
                <div class="controls">
                  <select name="proyecto" id="proyecto" class="span9" style="float: left;">
                      <option value=""></option>
                    <?php foreach ($proyectos as $key => $value): ?>
                      <option value="<?php echo $value['id']; ?>" <?php echo set_select('proyecto', $value['id'], (isset($orden['info'][0]->proyecto['info']) && $value['id']==$orden['info'][0]->proyecto['info']->id_proyecto) ); ?>><?php echo $value['value']; ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
              </div>

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
                    <option value="d" <?php echo set_select('tipoOrden', 'd', $orden['info'][0]->tipo_orden === 'd' ? true : false); ?>>Servicios (Gasto)</option>
                    <option value="oc" <?php echo set_select('tipoOrden', 'oc', $orden['info'][0]->tipo_orden === 'oc' ? true : false); ?>>Gastos (Gasto)</option>
                    <option value="f" <?php echo set_select('tipoOrden', 'f', $orden['info'][0]->tipo_orden === 'f' ? true : false); ?> <?php echo (isset($ordenFlete) && $ordenFlete) ? 'selected': '' ?>>Fletes (Gasto)</option>
                    <option value="a" <?php echo set_select('tipoOrden', 'a', $orden['info'][0]->tipo_orden === 'a' ? true : false); ?>>Activo</option>
                  </select>
                </div>
              </div>

              <div class="control-group" id="grpFleteDe" <?php echo ($orden['info'][0]->tipo_orden === 'f' || (isset($ordenFlete) && $ordenFlete) ? '': 'style="display:none;"'); ?>>
                <label class="control-label" for="fleteDe">Flete de</label>
                <div class="controls">
                  <select name="fleteDe" class="span9" id="fleteDe">
                    <option value="v" <?php echo set_select('fleteDe', 'v', $orden['info'][0]->flete_de === 'v' ? true : false); ?>>Venta</option>
                    <option value="c" <?php echo set_select('fleteDe', 'c', $orden['info'][0]->flete_de === 'c' ? true : false); ?>>Compra</option>
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
                <label class="control-label" for="folioHoja">Folio Hoja</label>
                <div class="controls">
                  <input type="text" name="folioHoja" class="span9" id="folioHoja" value="<?php echo set_value('folioHoja', $orden['info'][0]->folio_hoja); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="duso_cfdi">Uso de CFDI</label>
                <div class="controls">
                  <select name="duso_cfdi" class="span9" id="duso_cfdi">
                    <?php foreach ($usoCfdi as $key => $usoCfd) { ?>
                      <option value="<?php echo $usoCfd['key'] ?>" <?php echo set_select('duso_cfdi', $usoCfd['key'], ($orden['info'][0]->uso_cfdi == $usoCfd['key'])); ?>><?php echo $usoCfd['key'].' - '.$usoCfd['value'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dforma_pago">Forma de Pago</label>
                <div class="controls">
                  <select name="dforma_pago" class="span9" id="dforma_pago">
                    <?php foreach ($formPagos as $key => $formPago) { ?>
                      <option value="<?php echo $formPago['key'] ?>" <?php echo set_select('dforma_pago', $formPago['key'], ($orden['info'][0]->forma_pago == $formPago['key'])); ?>><?php echo $formPago['key'].' - '.$formPago['value'] ?></option>
                    <?php } ?>
                  </select>
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

              <div class="control-group" <?php echo ($orden['info'][0]->tipo_orden === 'f' && $orden['info'][0]->flete_de === 'v'? '': 'style="display:none;"'); ?> id="fletesFactura">
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

              <div class="control-group" <?php echo ($orden['info'][0]->tipo_orden === 'd'? '': 'style="display:none;"'); ?> id="serCompras">
                <label class="control-label" for="tipoPago">Ligar Compras</label>
                <div class="controls">
                  <button type="button" class="btn btn-info" id="show-compras">Buscar</button>
                  <span id="comprasLigada" style="cursor:pointer;">
                    <?php
                    $folios = '';
                    foreach ($orden['info'][0]->comprasligadas as $key => $value)
                    {
                      $folios .= $value->serie.$value->folio.' | ';
                    }
                      echo $folios.' <input type="hidden" name="compras" value="'.$orden['info'][0]->ids_compras.'"><input type="hidden" name="compras_folio" value="'.$folios.'">';
                    ?>
                  </span>
                </div>
              </div>

              <div class="control-group" <?php echo ($orden['info'][0]->tipo_orden === 'd'? '': 'style="display:none;"'); ?> id="serSalidasAlmacen">
                <label class="control-label" for="show-salidasAlmacen">Ligar Salidas Almacén</label>
                <div class="controls">
                  <button type="button" class="btn btn-info" id="show-salidasAlmacen">Buscar</button>
                  <span id="salidasAlmacenLigada" style="cursor:pointer;">
                    <?php
                    $folios = '';
                    foreach ($orden['info'][0]->salidasalmacenligadas as $key => $value)
                    {
                      $folios .= $value->folio.' | ';
                    }
                      echo $folios.' <input type="hidden" name="salidasAlmacen" value="'.$orden['info'][0]->ids_salidas_almacen.'"><input type="hidden" name="salidasAlmacen_folio" value="'.$folios.'">';
                    ?>
                  </span>
                </div>
              </div>

              <div class="control-group" <?php echo ($orden['info'][0]->tipo_orden === 'd'? '': 'style="display:none;"'); ?> id="serGastosCaja">
                <label class="control-label" for="show-gastosCaja">Ligar Gastos Caja 2</label>
                <div class="controls">
                  <button type="button" class="btn btn-info" id="show-gastosCaja">Buscar</button>
                  <span id="gastosCajaLigada" style="cursor:pointer;">
                    <?php
                    $folios = '';
                    foreach ($orden['info'][0]->gastoscajaligadas as $key => $value)
                    {
                      $folios .= $value->folio_sig.' | ';
                    }
                      echo $folios.' <input type="hidden" name="gastosCaja" value="'.$orden['info'][0]->ids_gastos_caja.'"><input type="hidden" name="gastosCaja_folio" value="'.$folios.'">';
                    ?>
                  </span>
                </div>
              </div>

              <div class="control-group" <?php echo ($orden['info'][0]->tipo_orden === 'f' && $orden['info'][0]->flete_de === 'c'? '': 'style="display:none;"'); ?> id="fletesBoletas">
                <label class="control-label" for="ligarBoleta">Ligar BOLETA</label>
                <div class="controls">
                  <button type="button" class="btn btn-info" id="show-boletas">Buscar</button>
                  <span id="boletasLigada" style="cursor:pointer;">
                    <?php
                    $folios = '';
                    foreach ($orden['info'][0]->boletasligadas as $key => $value)
                    {
                      $folios .= $value->folio.' | ';
                    }
                      echo $folios.' <input type="hidden" name="boletas" value="'.$orden['info'][0]->ids_facrem.'"><input type="hidden" name="boletas_folio" value="'.$folios.'">';
                    ?>
                  </span>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
                      <button type="submit" name="guardarprereq" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar Pre Req</button>
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

          <div class="row-fluid" id="groupInfoExt">  <!-- Box catalogos-->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-truck"></i> Información extra</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">
                  <div class="span12">
                    <div class="control-group span6">
                      <label class="control-label" for="infRecogerProv">Recoger con el proveedor </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="checkbox" data-uniform="false" name="infRecogerProv" id="infRecogerProv" value="si" <?php echo set_checkbox('infRecogerProv', 'si', isset($orden['info'][0]->otros_datos->infRecogerProv) ? true : false) ?>>
                          <input type="text" name="infRecogerProvNom" class="span11" id="infRecogerProvNom" value="<?php echo set_value('infRecogerProvNom', isset($orden['info'][0]->otros_datos->infRecogerProvNom) ? $orden['info'][0]->otros_datos->infRecogerProvNom : '') ?>" placeholder="Nombre quien recoge">
                        </div>
                      </div>
                    </div><!--/control-group -->

                    <div class="control-group span6">
                      <label class="control-label" for="infCotizacion">No cotización </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="infCotizacion" class="span11" id="infCotizacion" value="<?php echo set_value('infCotizacion', isset($orden['info'][0]->otros_datos->infCotizacion) ? $orden['info'][0]->otros_datos->infCotizacion : '') ?>">
                        </div>
                      </div>
                    </div><!--/control-group -->

                    <div class="control-group span10">
                      <label class="control-label" for="rancho">Requisitos para la entrega de mercancias </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <label id="infPasarBascula"><input type="checkbox" data-uniform="false" name="infPasarBascula" id="infPasarBascula" value="si" <?php echo set_checkbox('infPasarBascula', 'si', isset($orden['info'][0]->otros_datos->infPasarBascula) ? true : false) ?>>
                          Pasar a Bascula a pesar la mercancía y entregar Boleta a almacén.</label>
                          <label id="infEntOrdenCom"><input type="checkbox" data-uniform="false" name="infEntOrdenCom" id="infEntOrdenCom" value="si" <?php echo set_checkbox('infEntOrdenCom', 'si', isset($orden['info'][0]->otros_datos->infEntOrdenCom) ? true : false) ?>>
                          Entregar la mercancía al almacenista, referenciando la presente Orden de Compra, así como anexarla a su Factura</label>
                        </div>
                      </div>
                    </div><!--/control-group -->
                  </div>

                </div>

               </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->

          <div class="row-fluid" id="groupCatalogos" style="display: <?php echo ($orden['info'][0]->tipo_orden !== 'a' && $orden['info'][0]->tipo_orden !== 'a' ? 'block' : 'block') ?>;">  <!-- Box catalogos-->
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
                      <label class="control-label" for="empresaAp">Empresa aplicación </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="empresaAp" class="span11" id="empresaAp" value="<?php echo set_value('empresaAp', isset($orden['info'][0]->empresaAp->nombre_fiscal) ? $orden['info'][0]->empresaAp->nombre_fiscal : '') ?>" placeholder="Empaque, Mamita, etc">
                        </div>
                        <input type="hidden" name="empresaApId" id="empresaApId" value="<?php echo set_value('empresaApId', isset($orden['info'][0]->empresaAp->id_empresa) ? $orden['info'][0]->empresaAp->id_empresa : '') ?>">
                      </div>
                    </div><!--/control-group -->

                    <div class="control-group" id="cultivosGrup">
                      <label class="control-label" for="area">Cultivo / Actividad / Producto </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area', isset($orden['info'][0]->area->nombre) ? $orden['info'][0]->area->nombre : '') ?>" placeholder="Limon, Piña">
                        </div>
                        <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId', isset($orden['info'][0]->area->id_area) ? $orden['info'][0]->area->id_area : '') ?>">
                      </div>
                    </div><!--/control-group -->
                  </div>

                  <div class="span6">
                    <div class="control-group" id="ranchosGrup" style="display: <?php echo ($orden['info'][0]->tipo_orden !== 'f'? 'block' : 'none') ?>;">
                      <label class="control-label" for="rancho">Areas / Ranchos / Lineas </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="rancho" class="span11" id="rancho" value="" placeholder="Milagro A, Linea 1">
                        </div>
                      </div>
                      <ul class="tags" id="tagsRanchoIds">
                      <?php if (isset($orden['info'][0]->rancho)) {
                        foreach ($orden['info'][0]->rancho as $key => $rancho) { ?>
                          <li class=""><span class="tag"><?php echo $rancho->nombre ?></span>
                            <input type="hidden" name="ranchoId[]" class="ranchoId" value="<?php echo $rancho->id_rancho ?>">
                            <input type="hidden" name="ranchoText[]" class="ranchoText" value="<?php echo $rancho->nombre ?>">
                          </li>
                       <?php }} ?>
                      </ul>
                    </div><!--/control-group -->

                    <div class="control-group" id="centrosCostosGrup" style="display: <?php echo ($orden['info'][0]->tipo_orden !== 'f'? 'block' : 'none') ?>;">
                      <label class="control-label" for="centroCosto">Centro de costo </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="centroCosto" class="span11" id="centroCosto" value="" placeholder="Mantenimiento, Gasto general">
                        </div>
                      </div>
                      <ul class="tags" id="tagsCCIds">
                      <?php if (isset($orden['info'][0]->centroCosto)) {
                        foreach ($orden['info'][0]->centroCosto as $key => $centroCosto) { ?>
                          <li class=""><span class="tag"><?php echo $centroCosto->nombre ?></span>
                            <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="<?php echo $centroCosto->id_centro_costo ?>">
                            <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="<?php echo $centroCosto->nombre ?>">
                          </li>
                       <?php }} ?>
                      </ul>
                    </div><!--/control-group -->

                    <!-- <div class="control-group" id="activosGrup" style="display: <?php echo ($orden['info'][0]->tipo_orden !== 'f'? 'block' : 'none') ?>;">
                      <label class="control-label" for="activos">Activos </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="activos" class="span11" id="activos" value="<?php echo set_value('activos', isset($orden['info'][0]->activo->nombre) ? $orden['info'][0]->activo->nombre : '') ?>" placeholder="Nissan FRX, Maquina limon">
                        </div>
                        <input type="hidden" name="activoId" id="activoId" value="<?php echo set_value('activoId', isset($orden['info'][0]->activo->id_producto) ? $orden['info'][0]->activo->id_producto : '') ?>">
                      </div>
                    </div> -->
                    <!--/control-group -->
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
                    <div class="span2">
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
                        <option value="8">8%</option>
                        <option value="16">16%</option>
                      </select>
                    </div><!--/span1 -->
                    <div class="span1">
                      <label for="fretencionIva" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">Ret IVA</label>
                      <select class="span12" id="fretencionIva" data-next="fretencionIsr">
                        <option value="0">No retener</option>
                        <option value="4">4%</option>
                        <option value="10.6667">2 Terceras</option>
                        <option value="16">100 %</option>
                        <option value="6">6 %</option>
                        <option value="8">8 %</option>
                      </select>
                    </div><!--/span1 -->
                    <div class="span1">
                      <label for="fIsrPercent" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">Ret. ISR</label>
                      <input type="text" class="span12 vpositive" id="fIsrPercent" placeholder="%">
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
                  </div><!--/span12 -->
                  <div class="span12 mquit">
                    <div class="span8">
                      <div class="input-append span12">
                        <input type="text" class="span10" id="fproveedor" placeholder="Proveedor">
                        <a href="<?php echo base_url('panel/proveedores/agregar').'?modal=true' ?>" rel="superbox-70x550" class="btn btn-info" type="button" data-rel="tooltip" data-title="Agregar Producto"><i class="icon-plus" ></i></a>
                      </div>
                      <input type="hidden" class="span1" id="fproveedorId">
                    </div><!--/span2 -->
                    <div class="span2 offset2">
                      <button type="button" class="btn btn-success span12" id="btnAddProd">Agregar</button>
                    </div><!--/span2 -->
                  </div>

                </div><!--/row-fluid -->
                <br>
                <div class="row-fluid">
                  <div class="span12 mquit">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                      <thead>
                        <tr>
                          <th rowspan="2" style="vertical-align: middle;">PROVEEDOR</th>
                          <th rowspan="2" style="vertical-align: middle;">CODIGO AREA</th>
                          <th rowspan="2" style="vertical-align: middle;">CODIGO PROD.</th>
                          <th rowspan="2" style="vertical-align: middle;">CANT / PIEZAS</th>
                          <th rowspan="2" style="vertical-align: middle;">UNIDAD PRESEN.</th>
                          <th rowspan="2" style="vertical-align: middle;">PRODUCTO</th>
                          <!-- <th colspan="<?php echo $autorizar_active?'3':'2'; ?>">
                            <div class="input-append span12">
                              <input type="text" name="proveedor1" class="span10" id="proveedor1" value="<?php echo set_value('proveedor1', (isset($orden['info'][0]->proveedores[0]['nombre_fiscal'])? $orden['info'][0]->proveedores[0]['nombre_fiscal']: '')) ?>" placeholder="Proveedor 1"><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                              <input type="hidden" name="proveedorId1" id="proveedorId1" value="<?php echo set_value('proveedorId1', (isset($concepto->id_proveedor)? $concepto->id_proveedor: '')) ?>">
                            </div>
                          </th>
                          <th colspan="<?php echo $autorizar_active?'3':'2'; ?>">
                            <div class="input-append span12">
                              <input type="text" name="proveedor2" class="span10" id="proveedor2" value="<?php echo set_value('proveedor2', (isset($orden['info'][0]->proveedores[1]['nombre_fiscal'])? $orden['info'][0]->proveedores[1]['nombre_fiscal']: '')) ?>" placeholder="Proveedor 2"><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                              <input type="hidden" name="proveedorId2" id="proveedorId2" value="<?php echo set_value('proveedorId2', (isset($orden['info'][0]->proveedores[1]['id_proveedor'])? $orden['info'][0]->proveedores[1]['id_proveedor']: '')) ?>">
                            </div>
                          </th>
                          <th colspan="<?php echo $autorizar_active?'3':'2'; ?>">
                            <div class="input-append span12">
                              <input type="text" name="proveedor3" class="span10" id="proveedor3" value="<?php echo set_value('proveedor3', (isset($orden['info'][0]->proveedores[2]['nombre_fiscal'])? $orden['info'][0]->proveedores[2]['nombre_fiscal']: '')) ?>" placeholder="Proveedor 3"><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                              <input type="hidden" name="proveedorId3" id="proveedorId3" value="<?php echo set_value('proveedorId3', (isset($orden['info'][0]->proveedores[2]['id_proveedor'])? $orden['info'][0]->proveedores[2]['id_proveedor']: '')) ?>">
                            </div>
                          </th>
                          <th></th> -->
                        </tr>
                        <tr>
                          <th <?php echo $autorizar_active? 'colspan="2"': ''; ?>>P.U.</th>
                          <th>IMPORTE</th>
                          <!-- <th <?php echo $autorizar_active? 'colspan="2"': ''; ?>>P.U.</th>
                          <th>IMPORTE</th>
                          <th <?php echo $autorizar_active? 'colspan="2"': ''; ?>>P.U.</th>
                          <th>IMPORTE</th> -->
                          <th></th>
                        </tr>
                      </thead>
                      <tbody class="bodyproducs">
                        <?php if (isset($orden['info'][0]->productos) && count($orden['info'][0]->productos) > 0) {
                              foreach ($orden['info'][0]->productos as $key => $concepto) { ?>


                          <tr class="rowprod">
                            <td style="">
                              <?php echo $concepto->proveedor ?>
                              <input type="hidden" name="proveedor[]" value="<?php echo $concepto->proveedor ?>" id="proveedor" class="span12" >
                              <input type="hidden" name="proveedorId[]" value="<?php echo $concepto->id_proveedor ?>" id="proveedorId" class="span12" readonly>
                            </td>
                            <td style="width: 60px;">
                              <input type="text" name="codigoArea[]" value="<?php echo $concepto->codigo_fin ?>" id="codigoArea" class="span12 showCodigoAreaAuto">
                              <input type="hidden" name="codigoAreaId[]" value="<?php echo $concepto->id_area ?>" id="codigoAreaId" class="span12">
                              <input type="hidden" name="codigoCampo[]" value="<?php echo $concepto->campo ?>" id="codigoCampo" class="span12">
                              <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                            </td>
                            <td style="width: 60px;">
                              <?php echo $concepto->codigo ?>
                              <input type="hidden" name="codigo[]" value="<?php echo $concepto->codigo ?>" class="span12">
                              <input type="hidden" name="tipo_cambio[]" value="<?php echo $concepto->tipo_cambio ?>" class="span12">
                              <input type="hidden" name="prodIdOrden[]" value="<?php echo $concepto->id_requisicion ?>" class="span12 prodIdOrden">
                              <input type="hidden" name="prodIdNumRow[]" value="<?php echo $concepto->num_row ?>" class="span12">
                            </td>
                            <td style="width: 120px;">
                                <input type="number" step="any" name="cantidad[]" value="<?php echo ($concepto->cantidad/($concepto->presen_cantidad>0?$concepto->presen_cantidad:1)) ?>" id="cantidad" class="span12 vpositive" min="0">/
                                <input type="number" step="any" name="piezas[]" value="<?php echo ($concepto->piezas) ?>" id="piezas" class="span12 vpositive" min="0">
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

                          <?php $precio_unitario = $concepto->{'precio_unitario'.$concepto->id_proveedor} *
                                                  ($concepto->presen_cantidad>0?$concepto->presen_cantidad:1);  ?>
                          <?php if ($autorizar_active){ ?>
                            <td style="width: 10px;">
                              <input type="radio" name="prodSelOrden<?php echo $concepto->num_row; ?>[]" value="<?php echo $concepto->id_proveedor ?>" class="prodSelOrden prodSelOrden1" <?php echo ($precio_unitario? 'checked': '') ?> data-uniform="false">
                            </td>
                          <?php } ?>
                            <td style="width: 120px;">
                              <input type="text" name="valorUnitario1[]" value="<?php echo $precio_unitario; ?>" id="valorUnitario1" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo MyString::formatoNumero($concepto->{'importe'.$concepto->id_proveedor}, 2, '$', false); ?></span>
                              <input type="hidden" name="importe1[]" value="<?php echo $concepto->{'importe'.$concepto->id_proveedor} ?>" id="importe1" class="span12 provimporte vpositive">
                              <input type="hidden" name="total1[]" value="<?php echo $concepto->{'total'.$concepto->id_proveedor} ?>" id="total1" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal1[]" value="<?php echo $concepto->{'iva'.$concepto->id_proveedor} ?>" id="trasladoTotal1" class="span12">
                              <input type="hidden" name="iepsTotal1[]" value="<?php echo $concepto->{'ieps'.$concepto->id_proveedor} ?>" id="iepsTotal1" class="span12">
                              <input type="hidden" name="retTotal1[]" value="<?php echo $concepto->{'retencion_iva'.$concepto->id_proveedor} ?>" id="retTotal1" class="span12" readonly>
                              <input type="hidden" name="retIsrTotal1[]" value="<?php echo $concepto->{'retencion_isr'.$concepto->id_proveedor} ?>" id="retIsrTotal1" class="span12" readonly>
                            </td>

                          <?php
                            // $precio_unitario = $concepto->{'precio_unitario'.$orden['info'][0]->proveedores[1]['id_proveedor']} *
                            //                       ($concepto->presen_cantidad>0?$concepto->presen_cantidad:1);  ?>
                          <?php if ($autorizar_active){ ?>
                            <!-- <td style="width: 10px;">
                              <input type="radio" name="prodSelOrden<?php echo $concepto->num_row; ?>[]" value="<?php echo $orden['info'][0]->proveedores[1]['id_proveedor'] ?>" class="prodSelOrden prodSelOrden2" <?php echo ($precio_unitario>0? 'checked': '') ?> data-uniform="false">
                            </td> -->
                          <?php } ?>
                            <!-- <td style="width: 90px;">
                              <input type="text" name="valorUnitario2[]" value="<?php echo $precio_unitario ?>" id="valorUnitario2" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo MyString::formatoNumero($concepto->{'importe'.$orden['info'][0]->proveedores[1]['id_proveedor']}, 2, '$', false); ?></span>
                              <input type="hidden" name="importe2[]" value="<?php echo $concepto->{'importe'.$orden['info'][0]->proveedores[1]['id_proveedor']} ?>" id="importe2" class="span12 provimporte vpositive">
                              <input type="hidden" name="total2[]" value="<?php echo $concepto->{'total'.$orden['info'][0]->proveedores[1]['id_proveedor']} ?>" id="total2" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal2[]" value="<?php echo $concepto->{'iva'.$orden['info'][0]->proveedores[1]['id_proveedor']} ?>" id="trasladoTotal2" class="span12">
                              <input type="hidden" name="iepsTotal2[]" value="<?php echo $concepto->{'ieps'.$orden['info'][0]->proveedores[1]['id_proveedor']} ?>" id="iepsTotal2" class="span12">
                              <input type="hidden" name="retTotal2[]" value="<?php echo $concepto->{'retencion_iva'.$orden['info'][0]->proveedores[1]['id_proveedor']} ?>" id="retTotal2" class="span12" readonly>
                              <input type="hidden" name="retIsrTotal2[]" value="<?php echo $concepto->{'retencion_isr'.$concepto->id_proveedor} ?>" id="retIsrTotal2" class="span12" readonly>
                            </td> -->

                          <?php
                            // $precio_unitario = $concepto->{'precio_unitario'.$orden['info'][0]->proveedores[2]['id_proveedor']} *
                            //                       ($concepto->presen_cantidad>0?$concepto->presen_cantidad:1);  ?>
                          <?php if ($autorizar_active){ ?>
                            <!-- <td style="width: 10px;">
                              <input type="radio" name="prodSelOrden<?php echo $concepto->num_row; ?>[]" value="<?php echo $orden['info'][0]->proveedores[2]['id_proveedor'] ?>" class="prodSelOrden prodSelOrden3" <?php echo ($precio_unitario>0? 'checked': '') ?> data-uniform="false">
                            </td> -->
                          <?php } ?>
                            <!-- <td style="width: 90px;">
                              <input type="text" name="valorUnitario3[]" value="<?php echo $precio_unitario; ?>" id="valorUnitario3" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo MyString::formatoNumero($concepto->{'importe'.$orden['info'][0]->proveedores[2]['id_proveedor']}, 2, '$', false); ?></span>
                              <input type="hidden" name="importe3[]" value="<?php echo $concepto->{'importe'.$orden['info'][0]->proveedores[2]['id_proveedor']} ?>" id="importe3" class="span12 provimporte vpositive">
                              <input type="hidden" name="total3[]" value="<?php echo $concepto->{'total'.$orden['info'][0]->proveedores[2]['id_proveedor']} ?>" id="total3" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal3[]" value="<?php echo $concepto->{'iva'.$orden['info'][0]->proveedores[2]['id_proveedor']} ?>" id="trasladoTotal3" class="span12">
                              <input type="hidden" name="iepsTotal3[]" value="<?php echo $concepto->{'ieps'.$orden['info'][0]->proveedores[2]['id_proveedor']} ?>" id="iepsTotal3" class="span12">
                              <input type="hidden" name="retTotal3[]" value="<?php echo $concepto->{'retencion_iva'.$orden['info'][0]->proveedores[2]['id_proveedor']} ?>" id="retTotal3" class="span12" readonly>
                              <input type="hidden" name="retIsrTotal3[]" value="<?php echo $concepto->{'retencion_isr'.$concepto->id_proveedor} ?>" id="retIsrTotal3" class="span12" readonly>
                            </td> -->
                            <td style="width: 35px;">
                              <div style="position:relative;"><button type="button" class="btn btn-inverse" id="btnListActivos"><i class="icon-font"></i></button>
                                <div class="popover fade left in" style="top:-55.5px;left:-411px;margin-right: 43px;">
                                  <div class="arrow"></div><h3 class="popover-title">Activos</h3>
                                  <div class="popover-content">

                                    <div class="control-group" style="width: 375px;">
                                      <input type="text" name="observacionesP[]" class="span11" value="<?php echo $concepto->observaciones ?>" placeholder="Observaciones">
                                    </div>

                                    <div class="control-group activosGrup" style="width: 375px;display: <?php echo ($orden['info'][0]->tipo_orden !== 'f'? 'block' : 'none') ?>;">
                                      <div class="input-append span12">
                                        <input type="text" class="span11 clsActivos" value="" placeholder="Nissan FRX, Maquina limon">
                                      </div>
                                      <ul class="tags tagsActivosIds">
                                      <?php if (isset($concepto->activos)) {
                                        $activosjson = json_encode($concepto->activos);
                                        foreach ($concepto->activos as $key2 => $activo) { ?>
                                          <li data-id="<?php echo $key2 ?>"><span class="tag"><?php echo $activo->text ?></span></li>
                                       <?php }} ?>
                                      </ul>
                                      <input type="hidden" name="activosP[]" class="activosP"
                                        value="<?php echo (isset($activosjson)? str_replace('"', '”', $activosjson): '') ?>">
                                    </div>

                                  </div>
                                </div>
                              </div>
                              <div style="position:relative;"><button type="button" class="btn btn-info" id="btnListOtros"><i class="icon-list"></i></button>
                                <div class="popover fade left in" style="top:-55.5px;left:-411px;margin-right: 43px;">
                                  <div class="arrow"></div><h3 class="popover-title">Otros</h3>
                                  <div class="popover-content">
                                    <table>
                                      <tr>
                                        <td style="width: 66px;">IVA</td>
                                        <td style="width: 66px;">Ret IVA</td>
                                        <td style="width: 66px;">Ret ISR</td>
                                        <td style="width: 66px;">IEPS</td>
                                        <td>DESCRIP</td>
                                      </tr>
                                      <tr>
                                        <td style="width: 66px;">
                                            <select name="traslado[]" id="traslado" class="span12">
                                              <option value="0" <?php echo $concepto->porcentaje_iva === '0' ? 'selected' : '' ?>>0%</option>
                                              <option value="8" <?php echo $concepto->porcentaje_iva === '8' ? 'selected' : ''?>>8%</option>
                                              <option value="16" <?php echo $concepto->porcentaje_iva === '16' ? 'selected' : ''?>>16%</option>
                                            </select>
                                            <input type="hidden" name="trasladoPorcent[]" value="<?php echo $concepto->porcentaje_iva ?>" id="trasladoPorcent" class="span12">
                                        </td>
                                        <td style="width: 66px;">
                                            <select name="ret_iva[]" id="ret_iva" class="span12">
                                              <option value="0" <?php echo $concepto->porcentaje_retencion === '0' ? "selected" : '' ?>>No retener</option>
                                              <option value="4" <?php echo $concepto->porcentaje_retencion === '4' ? "selected" : '' ?>>4%</option>
                                              <option value="10.6667" <?php echo $concepto->porcentaje_retencion === '10.6667' ? "selected" : '' ?>>2 Terceras</option>
                                              <option value="16" <?php echo $concepto->porcentaje_retencion === '16' ? "selected" : '' ?>>100 %</option>
                                              <option value="6" <?php echo $concepto->porcentaje_retencion === '6' ? "selected" : '' ?>>6 %</option>
                                              <option value="8" <?php echo $concepto->porcentaje_retencion === '8' ? "selected" : '' ?>>8 %</option>
                                            </select>
                                        </td>
                                        <td style="width: 66px;">
                                            <input type="text" name="ret_isrPorcent[]" value="<?php echo $concepto->porcentaje_isr ?>" id="ret_isrPorcent" class="span12">
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
                              <td colspan="6" style="text-align: right;"><em>Subtotal</em></td>
                              <td id="importe-format1" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImporte1', 0))?></td>
                                <input type="hidden" name="totalImporte1" id="totalImporte1" value="<?php echo set_value('totalImporte1', 0); ?>">
                              <!-- <td id="importe-format2" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImporte2', 0))?></td>
                                <input type="hidden" name="totalImporte2" id="totalImporte2" value="<?php echo set_value('totalImporte2', 0); ?>">
                              <td id="importe-format3" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImporte3', 0))?></td>
                                <input type="hidden" name="totalImporte3" id="totalImporte3" value="<?php echo set_value('totalImporte3', 0); ?>"> -->
                            </tr>
                            <tr>
                              <td colspan="6" style="text-align: right;">IVA</td>
                              <td id="traslado-format1" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImpuestosTrasladados1', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados1" id="totalImpuestosTrasladados1" value="<?php echo set_value('totalImpuestosTrasladados1', 0); ?>">
                              <!-- <td id="traslado-format2" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImpuestosTrasladados2', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados2" id="totalImpuestosTrasladados2" value="<?php echo set_value('totalImpuestosTrasladados2', 0); ?>">
                              <td id="traslado-format3" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImpuestosTrasladados3', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados3" id="totalImpuestosTrasladados3" value="<?php echo set_value('totalImpuestosTrasladados3', 0); ?>"> -->
                            </tr>
                            <tr>
                              <td colspan="6" style="text-align: right;">IEPS</td>
                              <td id="ieps-format1" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalIeps1', 0))?></td>
                                <input type="hidden" name="totalIeps1" id="totalIeps1" value="<?php echo set_value('totalIeps1', 0); ?>">
                              <!-- <td id="ieps-format2" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalIeps2', 0))?></td>
                                <input type="hidden" name="totalIeps2" id="totalIeps2" value="<?php echo set_value('totalIeps2', 0); ?>">
                              <td id="ieps-format3" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalIeps3', 0))?></td>
                                <input type="hidden" name="totalIeps3" id="totalIeps3" value="<?php echo set_value('totalIeps3', 0); ?>"> -->
                            </tr>
                            <tr>
                              <td colspan="6" style="text-align: right;">RET.</td>
                              <td id="retencion-format1" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencion1', 0))?></td>
                                <input type="hidden" name="totalRetencion1" id="totalRetencion1" value="<?php echo set_value('totalRetencion1', 0); ?>">
                              <!-- <td id="retencion-format2" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencion2', 0))?></td>
                                <input type="hidden" name="totalRetencion2" id="totalRetencion2" value="<?php echo set_value('totalRetencion2', 0); ?>">
                              <td id="retencion-format3" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencion3', 0))?></td>
                                <input type="hidden" name="totalRetencion3" id="totalRetencion3" value="<?php echo set_value('totalRetencion3', 0); ?>"> -->
                            </tr>
                            <tr>
                              <td colspan="6" style="text-align: right;">RET ISR</td>
                              <td id="retencionisr-format1" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencionIsr1', 0))?></td>
                                <input type="hidden" name="totalRetencionIsr1" id="totalRetencionIsr1" value="<?php echo set_value('totalRetencionIsr1', 0); ?>">
                              <!-- <td id="retencionisr-format2" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencionIsr2', 0))?></td>
                                <input type="hidden" name="totalRetencionIsr2" id="totalRetencionIsr2" value="<?php echo set_value('totalRetencionIsr2', 0); ?>">
                              <td id="retencionisr-format3" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencionIsr3', 0))?></td>
                                <input type="hidden" name="totalRetencionIsr3" id="totalRetencionIsr3" value="<?php echo set_value('totalRetencionIsr3', 0); ?>"> -->
                            </tr>
                            <tr style="font-weight:bold;font-size:1.2em;">
                              <td colspan="6" style="text-align: right;">TOTAL</td>
                              <td id="total-format1" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalOrden1', 0))?></td>
                                <input type="hidden" name="totalOrden1" id="totalOrden1" value="<?php echo set_value('totalOrden1', 0); ?>">
                              <!-- <td id="total-format2" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalOrden2', 0))?></td>
                                <input type="hidden" name="totalOrden2" id="totalOrden2" value="<?php echo set_value('totalOrden2', 0); ?>">
                              <td id="total-format3" colspan="<?php echo $autorizar_active?'3':'2'; ?>" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalOrden3', 0))?></td>
                                <input type="hidden" name="totalOrden3" id="totalOrden3" value="<?php echo set_value('totalOrden3', 0); ?>"> -->
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


  <!-- Modal -->
  <div id="modal-compras" class="modal modal-w50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Compras</h3>
    </div>
    <div class="modal-body">
      <div class="row-fluid">
        <input type="text" name="serProveedor" class="pull-left" id="serProveedor" value="<?php echo set_value('serProveedor') ?>" placeholder="Proveedor">
        <input type="hidden" name="serProveedorId" id="serProveedorId" value="<?php echo set_value('serProveedorId') ?>">
         <span class="pull-left"> | </span>
        <input type="text" id="filFolioCompras" class="pull-left" placeholder="Folio">
      </div>
      <div class="row-fluid">
        <table class="table table-hover table-condensed" id="table-facturas">
          <thead>
            <tr>
              <th></th>
              <th style="width:70px;">Fecha</th>
              <th># Folio</th>
              <th>Proveedor</th>
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
      <button class="btn btn-primary" id="BtnAddCompra">Seleccionar</button>
    </div>
  </div><!--/modal pallets -->

  <!-- Modal boletas -->
  <div id="modal-boletas" class="modal modal-w50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Boletas</h3>
    </div>
    <div class="modal-body">
      <div class="row-fluid">
        <input type="text" id="filBoleta" class="pull-left" placeholder="Folio"> <span class="pull-left"> | </span>
        <!-- <label class="pull-left"><input type="radio" name="filTipoboletas" class="filTipoboletas" value="f" checked>boletas</label>
        <label class="pull-left"><input type="radio" name="filTipoboletas" class="filTipoboletas" value="r">Remision</label> -->
      </div>
      <div class="row-fluid">
        <table class="table table-hover table-condensed" id="table-boletas">
          <thead>
            <tr>
              <th></th>
              <th style="width:70px;">Fecha</th>
              <th># Folio</th>
              <th>Proveedor</th>
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
      <button class="btn btn-primary" id="BtnAddBoleta">Seleccionar</button>
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