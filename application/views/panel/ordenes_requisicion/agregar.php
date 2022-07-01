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

        <form class="form-horizontal" action="<?php echo base_url('panel/compras_requisicion/agregar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

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

              <div class="control-group sucursales" style="display: none;">
                <label class="control-label" for="sucursalId">Sucursal </label>
                <div class="controls">
                  <div class="input-append span12">
                    <select name="sucursalId" class="span11" id="sucursalId">
                      <option></option>
                      <?php foreach ($sucursales as $key => $sucur) { ?>
                        <option value="<?php echo $sucur->id_sucursal ?>" <?php echo set_select('sucursalId', $sucur->id_departamento); ?>><?php echo $depa->nombre_fiscal ?></option>
                      <?php } ?>
                    </select>
                  </div>
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

              <div class="control-group">
                <label class="control-label" for="id_almacen">Almacen</label>
                <div class="controls">
                  <div class="input-append span12">
                    <select name="id_almacen" class="span11">
                    <?php $default = ($this->input->post('id_almacen')>0? $this->input->post('id_almacen'): '1');
                    foreach ($almacenes['almacenes'] as $key => $value) { ?>
                      <option value="<?php echo $value->id_almacen ?>" <?php echo set_select('id_almacen', $value->id_almacen, false, $default) ?>><?php echo $value->nombre ?></option>
                    <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="control-group grpes_receta" <?php echo ((set_select('tipoOrden', 'p')==' selected="selected"' || !isset($_POST['tipoOrden']))? '': 'style="display:none;"'); ?>>
                <label class="control-label" for="es_receta">Es receta</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="checkbox" name="es_receta" id="es_receta" value="true" data-uniform="false" data-next="no_recetas">
                    <input type="text" name="no_recetas" id="no_recetas" class="span11" placeholder="No de recetas (si es mas de una separar con ,)" readonly>
                  </div>
                </div>
              </div>

              <div class="control-group classProyecto" style="display: none;">
                <label class="control-label" for="proyecto">Asignar a un Proyecto</label>
                <div class="controls">
                  <select name="proyecto" id="proyecto" class="span9" style="float: left;">
                    <!-- <?php foreach ($proyectos as $key => $value): ?>
                      <option value="<?php echo $value->id_proyecto; ?>" <?php echo set_select('proyecto', $value->id_proyecto); ?>><?php echo $value->nombre; ?></option>
                    <?php endforeach ?> -->
                  </select>
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
                    <option value="d" <?php echo set_select('tipoOrden', 'd'); ?>>Servicios (Gasto)</option>
                    <option value="oc" <?php echo set_select('tipoOrden', 'oc'); ?>>Gastos (Gasto)</option>
                    <option value="f" <?php echo set_select('tipoOrden', 'f'); ?> <?php echo (isset($ordenFlete) && $ordenFlete) ? 'selected': '' ?>>Fletes (Gasto)</option>
                    <option value="a" <?php echo set_select('tipoOrden', 'a'); ?>>Activo</option>
                  </select>
                </div>
              </div>

              <div class="control-group" id="grpFleteDe" <?php echo (set_select('tipoOrden', 'f')==' selected="selected"' || (isset($ordenFlete) && $ordenFlete) ? '': 'style="display:none;"'); ?>>
                <label class="control-label" for="fleteDe">Flete de</label>
                <div class="controls">
                  <select name="fleteDe" class="span9" id="fleteDe">
                    <option value="v" <?php echo set_select('fleteDe', 'v'); ?>>Venta</option>
                    <option value="c" <?php echo set_select('fleteDe', 'c'); ?>>Compra</option>
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
                <label class="control-label" for="folioHoja">Folio Hoja</label>
                <div class="controls">
                  <input type="text" name="folioHoja" class="span9" id="folioHoja" value="<?php echo set_value('folioHoja'); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="duso_cfdi">Uso de CFDI</label>
                <div class="controls">
                  <select name="duso_cfdi" class="span9" id="duso_cfdi">
                    <?php foreach ($usoCfdi as $key => $usoCfd) { ?>
                      <option value="<?php echo $usoCfd['key'] ?>" <?php echo set_select('duso_cfdi', $usoCfd['key'], false, 'G03'); ?>><?php echo $usoCfd['key'].' - '.$usoCfd['value'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dforma_pago">Forma de Pago</label>
                <div class="controls">
                  <select name="dforma_pago" class="span9" id="dforma_pago">
                    <?php foreach ($formPagos as $key => $formPago) { ?>
                      <option value="<?php echo $formPago['key'] ?>" <?php echo set_select('dforma_pago', $formPago['key'], false, 'G03'); ?>><?php echo $formPago['key'].' - '.$formPago['value'] ?></option>
                    <?php } ?>
                  </select>
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

              <div class="control-group" <?php echo (set_select('tipoOrden', 'd')==' selected="selected"' || (isset($ordenFlete) && $ordenFlete) ? '': 'style="display:none;"'); ?> id="serCompras">
                <label class="control-label" for="ligcompras">Ligar Compras</label>
                <div class="controls">
                  <button type="button" class="btn btn-info" id="show-compras">Buscar</button>
                  <span id="comprasLigada" style="cursor:pointer;">
                    <?php if(isset($_POST['compras'])){
                      echo $_POST['compras_folio'].' <input type="hidden" name="compras" value="'.$_POST['compras'].'"><input type="hidden" name="compras_folio" value="'.$_POST['compras_folio'].'">';
                    } else if (isset($ordenFlete) && $ordenFlete) {
                      echo $factura['info']->serie.$factura['info']->folio.' | <input type="hidden" name="compras" value="'.$factura['info']->id_factura.'|"><input type="hidden" name="compras_folio" value="'.$factura['info']->serie.$factura['info']->folio.' | ">';
                    } ?>
                  </span>
                </div>
              </div>

              <div class="control-group" <?php echo (set_select('tipoOrden', 'd')==' selected="selected"' || (isset($ordenFlete) && $ordenFlete) ? '': 'style="display:none;"'); ?> id="serSalidasAlmacen">
                <label class="control-label" for="show-salidasAlmacen">Ligar Salidas Almacén</label>
                <div class="controls">
                  <button type="button" class="btn btn-info" id="show-salidasAlmacen">Buscar</button>
                  <span id="salidasAlmacenLigada" style="cursor:pointer;">
                    <?php if(isset($_POST['salidasAlmacen'])){
                      echo $_POST['salidasAlmacen_folio'].' <input type="hidden" name="salidasAlmacen" value="'.$_POST['salidasAlmacen'].'"><input type="hidden" name="salidasAlmacen_folio" value="'.$_POST['salidasAlmacen_folio'].'">';
                    } else if (isset($ordenFlete) && $ordenFlete) {
                      echo $factura['info']->serie.$factura['info']->folio.' | <input type="hidden" name="salidasAlmacen" value="'.$factura['info']->id_factura.'|"><input type="hidden" name="salidasAlmacen_folio" value="'.$factura['info']->serie.$factura['info']->folio.' | ">';
                    } ?>
                  </span>
                </div>
              </div>

              <div class="control-group" <?php echo (set_select('tipoOrden', 'd')==' selected="selected"' || (isset($ordenFlete) && $ordenFlete) ? '': 'style="display:none;"'); ?> id="serGastosCaja">
                <label class="control-label" for="show-gastosCaja">Ligar Gastos Caja 2</label>
                <div class="controls">
                  <button type="button" class="btn btn-info" id="show-gastosCaja">Buscar</button>
                  <span id="gastosCajaLigada" style="cursor:pointer;">
                    <?php if(isset($_POST['gastosCaja'])){
                      echo $_POST['gastosCaja_folio'].' <input type="hidden" name="gastosCaja" value="'.$_POST['gastosCaja'].'"><input type="hidden" name="gastosCaja_folio" value="'.$_POST['gastosCaja_folio'].'">';
                    } else if (isset($ordenFlete) && $ordenFlete) {
                      echo $factura['info']->serie.$factura['info']->folio.' | <input type="hidden" name="gastosCaja" value="'.$factura['info']->id_factura.'|"><input type="hidden" name="gastosCaja_folio" value="'.$factura['info']->serie.$factura['info']->folio.' | ">';
                    } ?>
                  </span>
                </div>
              </div>

              <div class="control-group" <?php echo (set_select('tipoOrden', 'f')==' selected="selected"' || (isset($ordenFlete) && $ordenFlete) ? '': 'style="display:none;"'); ?> id="fletesBoletas">
                <label class="control-label" for="ligarBoleta">Ligar BOLETA</label>
                <div class="controls">
                  <button type="button" class="btn btn-info" id="show-boletas">Buscar</button>
                  <span id="boletasLigada" style="cursor:pointer;">
                    <?php if(isset($_POST['boletas'])){
                      echo $_POST['boletas_folio'].' <input type="hidden" name="boletas" value="'.$_POST['boletas'].'"><input type="hidden" name="boletas_folio" value="'.$_POST['boletas_folio'].'">';
                    } else if (isset($ordenFlete) && $ordenFlete) {
                      echo $factura['info']->serie.$factura['info']->folio.' | <input type="hidden" name="boletas" value="t:'.$factura['info']->id_factura.'|"><input type="hidden" name="boletas_folio" value="'.$factura['info']->serie.$factura['info']->folio.' | ">';
                    } ?>
                  </span>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
                      <button type="submit" name="guardarprereq" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar Pre Req</button>
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

                  <div class="span12" style="text-align: center;">
                    <input type="hidden" name="dimg_gas" id="dimg_gas" value="">
                    <img id="img_show_gas" src="<?php echo base_url('application/images/ctrl-v.jpg') ?>" style="height: 250px;width: auto;border: 3px #000 solid;">
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
                          <input type="checkbox" data-uniform="false" name="infRecogerProv" id="infRecogerProv" value="si" <?php echo set_value('infRecogerProv') ?>>
                          <input type="text" name="infRecogerProvNom" class="span11" id="infRecogerProvNom" value="" placeholder="Nombre quien recoge">
                        </div>
                      </div>
                    </div><!--/control-group -->

                    <div class="control-group span6">
                      <label class="control-label" for="infCotizacion">No cotización </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="infCotizacion" class="span11" id="infCotizacion" value="<?php echo set_value('infCotizacion') ?>">
                        </div>
                      </div>
                    </div><!--/control-group -->

                    <div class="control-group span10">
                      <label class="control-label" for="rancho">Requisitos para la entrega de mercancias </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <label><input type="checkbox" data-uniform="false" name="infPasarBascula" id="infPasarBascula" value="si" <?php echo set_value('infPasarBascula') ?>>
                          Pasar a Bascula a pesar la mercancía y entregar Boleta a almacén.</label>
                          <label><input type="checkbox" data-uniform="false" name="infEntOrdenCom" id="infEntOrdenCom" value="si" <?php echo set_value('infEntOrdenCom') ?>>
                          Entregar la mercancía al almacenista, referenciando la presente Orden de Compra, así como anexarla a su Factura</label>
                        </div>
                      </div>
                    </div><!--/control-group -->
                  </div>

                </div>

               </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->

          <div class="row-fluid" id="groupCatalogos" style="display: <?php echo isset($_POST['tipoOrden']) ? ($_POST['tipoOrden'] !== 'a' ? 'block' : 'none') : 'block' ?>;">  <!-- Box catalogos-->
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
                          <input type="text" name="empresaAp" class="span11" id="empresaAp" value="<?php echo set_value('empresaAp') ?>" placeholder="Empaque, Mamita, etc">
                        </div>
                        <input type="hidden" name="empresaApId" id="empresaApId" value="<?php echo set_value('empresaApId') ?>">
                      </div>
                    </div><!--/control-group -->

                    <div class="control-group" id="cultivosGrup">
                      <label class="control-label" for="area">Cultivo / Actividad / Producto </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area') ?>" placeholder="Limon, Piña">
                        </div>
                        <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId') ?>">
                      </div>
                    </div><!--/control-group -->
                  </div>

                  <div class="span6">
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

                    <!-- <div class="control-group" id="activosGrup">
                      <label class="control-label" for="activos">Activos </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="activos" class="span11" id="activos" value="<?php echo set_value('activos') ?>" placeholder="Nissan FRX, Maquina limon">
                        </div>
                        <input type="hidden" name="activoId" id="activoId" value="<?php echo set_value('activoId') ?>">
                      </div>
                    </div> --><!--/control-group -->
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
                    <!-- <div class="span2"> -->
                    <!-- </div> -->
                    <div class="span6">
                      <div class="input-append span12">
                      <input type="hidden" class="span12" id="fcodigo" placeholder="Codigo" style="display: none;">
                        <input type="text" class="span10" id="fconcepto" placeholder="Producto / Descripción">
                        <a href="<?php echo base_url('panel/productos').'?modal=true' ?>" rel="superbox-70x550" class="btn btn-info" type="button" data-rel="tooltip" data-title="Agregar Producto"><i class="icon-plus" ></i></a>
                      </div>
                      <input type="hidden" class="span1" id="fconceptoId">
                    </div><!--/span3s -->
                    <div class="span2">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fcantidad" min="0.01" placeholder="Cant.">
                    </div><!--/span2s -->
                    <div class="span2">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fpiezas" min="0.01" placeholder="Piezas">
                    </div><!--/span2s -->
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
                      <select class="span12" id="fretencionIva" data-next="fIsrPercent">
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
                          <th rowspan="2" style="vertical-align: middle;">CANT.</th>
                          <th rowspan="2" style="vertical-align: middle;">UNIDAD PRESEN.</th>
                          <th rowspan="2" style="vertical-align: middle;">PRODUCTO</th>
                          <!-- <th colspan="2">
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
                          <th></th> -->
                        </tr>
                        <tr>
                          <th>P.U.</th>
                          <th>IMPORTE</th>
                          <!-- <th>P.U.</th>
                          <th>IMPORTE</th>
                          <th>P.U.</th>
                          <th>IMPORTE</th>
                          <th>IVA</th>
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
                            <td style="">
                              <?php echo $_POST['proveedor'][$key] ?>
                              <input type="hidden" name="proveedor[]" value="<?php echo $_POST['proveedor'][$key] ?>" id="proveedor" class="span12" >
                              <input type="hidden" name="proveedorId[]" value="<?php echo $_POST['proveedorId'][$key] ?>" id="proveedorId" class="span12" readonly>
                            </td>
                            <td style="width: 60px;">
                              <input type="text" name="codigoArea[]" value="<?php echo $_POST['codigoArea'][$key] ?>" id="codigoArea" class="span12 showCodigoAreaAuto">
                              <input type="hidden" name="codigoAreaId[]" value="<?php echo $_POST['codigoAreaId'][$key] ?>" id="codigoAreaId" class="span12">
                              <input type="hidden" name="codigoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12">
                              <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                            </td>
                            <td style="width: 60px;">
                              <?php echo $_POST['codigo'][$key] ?>
                              <input type="hidden" name="codigo[]" value="<?php echo $_POST['codigo'][$key] ?>" class="span12">
                              <input type="hidden" name="tipo_cambio[]" value="<?php echo $_POST['tipo_cambio'][$key] ?>" class="span12">
                              <input type="hidden" name="prodIdOrden[]" value="<?php echo $_POST['prodIdOrden'][$key] ?>" class="span12">
                              <input type="hidden" name="prodIdNumRow[]" value="<?php echo $_POST['prodIdNumRow'][$key] ?>" class="span12">
                            </td>
                            <td style="width: 120px;">
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
                            <td style="width: 120px;">
                              <input type="text" name="valorUnitario1[]" value="<?php echo $_POST['valorUnitario1'][$key] ?>" id="valorUnitario1" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo MyString::formatoNumero($_POST['importe1'][$key], 2, '$', false); ?></span>
                              <input type="hidden" name="importe1[]" value="<?php echo $_POST['importe1'][$key] ?>" id="importe1" class="span12 provimporte vpositive">
                              <input type="hidden" name="total1[]" value="<?php echo $_POST['total1'][$key] ?>" id="total1" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal1[]" value="<?php echo $_POST['trasladoTotal1'][$key] ?>" id="trasladoTotal1" class="span12">
                              <input type="hidden" name="iepsTotal1[]" value="<?php echo $_POST['iepsTotal1'][$key] ?>" id="iepsTotal1" class="span12">
                              <input type="hidden" name="retTotal1[]" value="<?php echo $_POST['retTotal1'][$key] ?>" id="retTotal1" class="span12" readonly>
                              <input type="hidden" name="retIsrTotal1[]" value="<?php echo $_POST['retIsrTotal1'][$key] ?>" id="retIsrTotal1" class="span12" readonly>
                            </td>
                            <!-- <td style="width: 90px;">
                              <input type="text" name="valorUnitario2[]" value="<?php echo $_POST['valorUnitario2'][$key] ?>" id="valorUnitario2" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo MyString::formatoNumero($_POST['importe2'][$key], 2, '$', false); ?></span>
                              <input type="hidden" name="importe2[]" value="<?php echo $_POST['importe2'][$key] ?>" id="importe2" class="span12 provimporte vpositive">
                              <input type="hidden" name="total2[]" value="<?php echo $_POST['total2'][$key] ?>" id="total2" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal2[]" value="<?php echo $_POST['trasladoTotal2'][$key] ?>" id="trasladoTotal2" class="span12">
                              <input type="hidden" name="iepsTotal2[]" value="<?php echo $_POST['iepsTotal2'][$key] ?>" id="iepsTotal2" class="span12">
                              <input type="hidden" name="retTotal2[]" value="<?php echo $_POST['retTotal2'][$key] ?>" id="retTotal2" class="span12" readonly>
                              <input type="hidden" name="retIsrTotal2[]" value="<?php echo $_POST['retIsrTotal2'][$key] ?>" id="retIsrTotal2" class="span12" readonly>
                            </td>
                            <td style="width: 90px;">
                              <input type="text" name="valorUnitario3[]" value="<?php echo $_POST['valorUnitario3'][$key] ?>" id="valorUnitario3" class="span12 provvalorUnitario vpositive">
                            </td>
                            <td>
                              <span><?php echo MyString::formatoNumero($_POST['importe3'][$key], 2, '$', false); ?></span>
                              <input type="hidden" name="importe3[]" value="<?php echo $_POST['importe3'][$key] ?>" id="importe3" class="span12 provimporte vpositive">
                              <input type="hidden" name="total3[]" value="<?php echo $_POST['total3'][$key] ?>" id="total3" class="span12 provtotal vpositive">
                              <input type="hidden" name="trasladoTotal3[]" value="<?php echo $_POST['trasladoTotal3'][$key] ?>" id="trasladoTotal3" class="span12">
                              <input type="hidden" name="iepsTotal3[]" value="<?php echo $_POST['iepsTotal3'][$key] ?>" id="iepsTotal3" class="span12">
                              <input type="hidden" name="retTotal3[]" value="<?php echo $_POST['retTotal3'][$key] ?>" id="retTotal3" class="span12" readonly>
                              <input type="hidden" name="retIsrTotal3[]" value="<?php echo $_POST['retIsrTotal3'][$key] ?>" id="retIsrTotal3" class="span12" readonly>
                            </td -->
                            <td style="width: 35px;">
                              <div style="position:relative;"><button type="button" class="btn btn-inverse" id="btnListActivos"><i class="icon-font"></i></button>
                                <div class="popover fade left in" style="top:-55.5px;left:-411px;margin-right: 43px;">
                                  <div class="arrow"></div><h3 class="popover-title">Activos</h3>
                                  <div class="popover-content">

                                    <div class="control-group" style="width: 375px;">
                                      <input type="text" name="observacionesP[]" class="span11" value="<?php echo $_POST['observacionesP'][$key] ?>" placeholder="Observaciones">
                                    </div>

                                    <div class="control-group activosGrup" style="width: 375px;">
                                      <div class="input-append span12">
                                        <input type="text" class="span11 clsActivos" value="" placeholder="Nissan FRX, Maquina limon">
                                      </div>
                                      <ul class="tags tagsActivosIds">
                                      <?php if (isset($_POST['activosP'][$key])) {
                                        $json = json_decode(str_replace('”', '"', $_POST['activosP'][$key]) );
                                        if ($json != null) {
                                          foreach ($json as $key2 => $activo) { ?>
                                            <li data-id="<?php echo $key2 ?>"><span class="tag"><?php echo $activo->text ?></span></li>
                                      <?php }
                                        }
                                      } ?>
                                      </ul>
                                      <input type="hidden" name="activosP[]" class="activosP" value="<?php echo $_POST['activosP'][$key] ?>">
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
                                              <option value="0" <?php echo $_POST['traslado'][$key] === '0' ? 'selected' : '' ?>>0%</option>
                                              <option value="8" <?php echo $_POST['traslado'][$key] === '8' ? 'selected' : ''?>>8%</option>
                                              <option value="16" <?php echo $_POST['traslado'][$key] === '16' ? 'selected' : ''?>>16%</option>
                                            </select>
                                            <input type="hidden" name="trasladoPorcent[]" value="<?php echo $_POST['trasladoPorcent'][$key] ?>" id="trasladoPorcent" class="span12">
                                        </td>
                                        <td style="width: 66px;">
                                            <select name="ret_iva[]" id="ret_iva" class="span12">
                                              <option value="0" <?php echo $_POST['ret_iva'][$key] === '0' ? "selected" : '' ?>>No retener</option>
                                              <option value="4" <?php echo $_POST['ret_iva'][$key] === '4' ? "selected" : '' ?>>4%</option>
                                              <option value="10.6667" <?php echo $_POST['ret_iva'][$key] === '10.6667' ? "selected" : '' ?>>2 Terceras</option>
                                              <option value="16" <?php echo $_POST['ret_iva'][$key] === '16' ? "selected" : '' ?>>100 %</option>
                                              <option value="6" <?php echo $_POST['ret_iva'][$key] === '6' ? "selected" : '' ?>>6 %</option>
                                              <option value="8" <?php echo $_POST['ret_iva'][$key] === '8' ? "selected" : '' ?>>8 %</option>
                                            </select>
                                        </td>
                                        <td style="width: 66px;">
                                            <input type="text" name="ret_isrPorcent[]" value="<?php echo $_POST['ret_isrPorcent'][$key] ?>" id="ret_isrPorcent" class="span12">
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
                              <td colspan="6" style="text-align: right;"><em>Subtotal</em></td>
                              <td id="importe-format1" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImporte1', 0))?></td>
                                <input type="hidden" name="totalImporte1" id="totalImporte1" value="<?php echo set_value('totalImporte1', 0); ?>">
                              <!-- <td id="importe-format2" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImporte2', 0))?></td>
                                <input type="hidden" name="totalImporte2" id="totalImporte2" value="<?php echo set_value('totalImporte2', 0); ?>">
                              <td id="importe-format3" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImporte3', 0))?></td>
                                <input type="hidden" name="totalImporte3" id="totalImporte3" value="<?php echo set_value('totalImporte3', 0); ?>"> -->
                            </tr>
                            <tr>
                              <td colspan="6" style="text-align: right;">IVA</td>
                              <td id="traslado-format1" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImpuestosTrasladados1', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados1" id="totalImpuestosTrasladados1" value="<?php echo set_value('totalImpuestosTrasladados1', 0); ?>">
                              <!-- <td id="traslado-format2" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImpuestosTrasladados2', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados2" id="totalImpuestosTrasladados2" value="<?php echo set_value('totalImpuestosTrasladados2', 0); ?>">
                              <td id="traslado-format3" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalImpuestosTrasladados3', 0))?></td>
                                <input type="hidden" name="totalImpuestosTrasladados3" id="totalImpuestosTrasladados3" value="<?php echo set_value('totalImpuestosTrasladados3', 0); ?>"> -->
                            </tr>
                            <tr>
                              <td colspan="6" style="text-align: right;">IEPS</td>
                              <td id="ieps-format1" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalIeps1', 0))?></td>
                                <input type="hidden" name="totalIeps1" id="totalIeps1" value="<?php echo set_value('totalIeps1', 0); ?>">
                              <!-- <td id="ieps-format2" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalIeps2', 0))?></td>
                                <input type="hidden" name="totalIeps2" id="totalIeps2" value="<?php echo set_value('totalIeps2', 0); ?>">
                              <td id="ieps-format3" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalIeps3', 0))?></td>
                                <input type="hidden" name="totalIeps3" id="totalIeps3" value="<?php echo set_value('totalIeps3', 0); ?>"> -->
                            </tr>
                            <tr>
                              <td colspan="6" style="text-align: right;">RET.</td>
                              <td id="retencion-format1" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencion1', 0))?></td>
                                <input type="hidden" name="totalRetencion1" id="totalRetencion1" value="<?php echo set_value('totalRetencion1', 0); ?>">
                              <!-- <td id="retencion-format2" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencion2', 0))?></td>
                                <input type="hidden" name="totalRetencion2" id="totalRetencion2" value="<?php echo set_value('totalRetencion2', 0); ?>">
                              <td id="retencion-format3" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencion3', 0))?></td>
                                <input type="hidden" name="totalRetencion3" id="totalRetencion3" value="<?php echo set_value('totalRetencion3', 0); ?>"> -->
                            </tr>
                            <tr>
                              <td colspan="6" style="text-align: right;">RET ISR</td>
                              <td id="retencionisr-format1" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencionIsr1', 0))?></td>
                                <input type="hidden" name="totalRetencionIsr1" id="totalRetencionIsr1" value="<?php echo set_value('totalRetencionIsr1', 0); ?>">
                              <!-- <td id="retencionisr-format2" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencionIsr2', 0))?></td>
                                <input type="hidden" name="totalRetencionIsr2" id="totalRetencionIsr2" value="<?php echo set_value('totalRetencionIsr2', 0); ?>">
                              <td id="retencionisr-format3" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalRetencionIsr3', 0))?></td>
                                <input type="hidden" name="totalRetencionIsr3" id="totalRetencionIsr3" value="<?php echo set_value('totalRetencionIsr3', 0); ?>"> -->
                            </tr>
                            <tr style="font-weight:bold;font-size:1.2em;">
                              <td colspan="6" style="text-align: right;">TOTAL</td>
                              <td id="total-format1" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalOrden1', 0))?></td>
                                <input type="hidden" name="totalOrden1" id="totalOrden1" value="<?php echo set_value('totalOrden1', 0); ?>">
                              <!-- <td id="total-format2" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalOrden2', 0))?></td>
                                <input type="hidden" name="totalOrden2" id="totalOrden2" value="<?php echo set_value('totalOrden2', 0); ?>">
                              <td id="total-format3" colspan="2" style="text-align: right;"><?php echo MyString::formatoNumero(set_value('totalOrden3', 0))?></td>
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

  <!-- Modal Ligar Compras -->
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

  <!-- Modal Ligar Salidas Almacen -->
  <div id="modal-salidas-almacen" class="modal modal-w50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Salidas de Almacén</h3>
    </div>
    <div class="modal-body">
      <div class="row-fluid">
        <input type="text" name="serEmpresaSA" class="pull-left" id="serEmpresaSA" value="<?php echo set_value('serEmpresaSA') ?>" placeholder="Empresa">
        <input type="hidden" name="serEmpresaSAId" id="serEmpresaSAId" value="<?php echo set_value('serEmpresaSAId') ?>">
         <span class="pull-left"> | </span>
        <input type="number" id="filFolioSalidasAlmacen" class="pull-left" placeholder="Folio">
      </div>
      <div class="row-fluid">
        <table class="table table-hover table-condensed" id="table-salidas-almacen">
          <thead>
            <tr>
              <th></th>
              <th style="width:70px;">Fecha</th>
              <th># Folio</th>
              <th>Empresa</th>
              <th>Concepto</th>
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
      <button class="btn btn-primary" id="BtnAddSalidaAlmacen">Seleccionar</button>
    </div>
  </div><!--/modal pallets -->

  <!-- Modal Ligar Gastos caja -->
  <div id="modal-gastos-caja" class="modal modal-w50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Gastos de Caja</h3>
    </div>
    <div class="modal-body">
      <div class="row-fluid">
        <input type="text" name="serEmpresaGC" class="pull-left" id="serEmpresaGC" value="<?php echo set_value('serEmpresaGC') ?>" placeholder="Empresa">
        <input type="hidden" name="serEmpresaGCId" id="serEmpresaGCId" value="<?php echo set_value('serEmpresaGCId') ?>">
         <span class="pull-left"> | </span>
        <input type="number" id="filFolioGastosCaja" class="pull-left" placeholder="Folio">
        <span class="pull-left"> | </span>
        <input type="number" id="filCajaGastosCaja" class="pull-left" placeholder="Caja">
      </div>
      <div class="row-fluid">
        <table class="table table-hover table-condensed" id="table-gastos-caja">
          <thead>
            <tr>
              <th></th>
              <th style="width:70px;">Fecha</th>
              <th># Folio</th>
              <th>Empresa</th>
              <th>No Caja</th>
              <th>Monto</th>
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
      <button class="btn btn-primary" id="BtnAddGastosCaja">Seleccionar</button>
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