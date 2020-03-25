<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <?php if ($_GET['w'] === 'c'){ ?>
          <a href="<?php echo base_url('panel/compras_ordenes/'); ?>">Ordenes de Compras</a> <span class="divider">/</span>
        <?php } else { ?>
          <a href="<?php echo base_url('panel/compras_ordenes/requisicion'); ?>">Ordenes de Requisicion</a> <span class="divider">/</span>
        <?php } ?>
      </li>
      <li>Modificar | Ver</li>
    </ul>
  </div>

  <?php

    $badgeTitle = 'PENDIENTE';
    $badgeStyle = '';

    $readonly = '';
    $disabled = '';

    $txtButton  = 'Guardar';
    $showButton = true;

    $method     = '';
    $htmlProdOk = '';
    $prodOk = false;

    $readonlyCat = '';
    $receta_readonly = '';
    if ($orden['info'][0]->status === 'p' AND $orden['info'][0]->autorizado === 'f' AND ! isset($_GET['mod']))
    {
      $badgeTitle = 'NO AUTORIZADO';
      $badgeStyle = '-warning';
      $readonly = 'readonly';
      $disabled = 'disabled';
      $txtButton = 'Autorizar';
      $method = '&m=a';
    }
    else
    {
      if ($orden['info'][0]->status === 'p' AND $orden['info'][0]->autorizado === 't' AND ! isset($_GET['mod']))
      {
        $badgeTitle = 'PENDIENTE';
        $badgeStyle = '-info';
        $readonly = 'readonly';
        $disabled = 'disabled';
        $txtButton = 'Dar Entrada';
        $method = '&m=e';
        // $htmlProdOk = '<input type="checkbox" value="1" class="prodOk"><input type="hidden" name="isProdOk[]" value="0" id="idProdOk">';
        $prodOk = true;
      }
      else if ($orden['info'][0]->status === 'a' AND $orden['info'][0]->autorizado === 't')
      {
        $badgeTitle = 'ACEPTADA';
        $badgeStyle = '-success';
        $readonly = 'readonly';
        $readonlyCat = 'readonly';
        $disabled = '';
        $showButton = true;
      }
      else if ($orden['info'][0]->status === 'f' AND $orden['info'][0]->autorizado === 't')
      {
        $badgeTitle = 'FACTURADA';
        $badgeStyle = '-success';
        $readonly = 'readonly';
        $disabled = 'disabled';
        $showButton = false;
      }
      else if ($orden['info'][0]->status === 'ca')
      {
        $badgeTitle = 'CANCELADA';
        $badgeStyle = '-warning';
        $readonly = 'readonly';
        $disabled = 'disabled';
        $showButton = false;
      }
      else if ($orden['info'][0]->status === 'r' AND $orden['info'][0]->autorizado === 't')
      {
        $badgeTitle = 'RECHAZADA';
        $badgeStyle = '-warning';
      }
    }

    if ($orden['info'][0]->es_receta == 't') {
      $receta_readonly = 'readonly';
      $readonlyCat = 'disable';
    }
  ?>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-shopping-cart"></i> <?php echo $seo['titulo']; ?> <span class="badge badge<?php echo $badgeStyle ?>"><?php echo $badgeTitle ?></span></h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/compras_ordenes/modificar?'.MyString::getVarsLink(array('m', 'msg', 'print')).$method); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $orden['info'][0]->empresa) ?>" autofocus <?php echo $readonly ?>><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                  <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $orden['info'][0]->id_empresa) ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="proveedor">Proveedor</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="proveedor" class="span11" id="proveedor" value="<?php echo set_value('proveedor', $orden['info'][0]->proveedor) ?>" <?php echo $readonly ?>><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                </div>
                  <input type="hidden" name="proveedorId" id="proveedorId" value="<?php echo set_value('proveedorId', $orden['info'][0]->id_proveedor) ?>">
              </div>

              <div class="control-group">
                <label class="control-label" for="solicito">Solicito</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="solicito" class="span11" id="solicito" value="<?php echo set_value('solicito', $orden['info'][0]->empleado_solicito) ?>" placeholder="" <?php echo $receta_readonly ?>>
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="departamento">Departamento</label>
                <div class="controls">
                  <select name="departamento" class="span12" id="departamento" <?php echo $disabled ?>>
                    <option></option>
                    <?php foreach ($departamentos as $key => $depa) { ?>
                      <option value="<?php echo $depa->id_departamento ?>" <?php echo set_select('departamento', $depa->id_departamento, $orden['info'][0]->id_departamento == $depa->id_departamento ? true : false); ?> ><?php echo $depa->nombre ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="cliente">Cliente</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="cliente" class="span11" id="cliente" value="<?php echo set_value('cliente', $orden['info'][0]->cliente) ?>" <?php echo $readonly ?>>
                  </div>
                </div>
                  <input type="hidden" name="clienteId" id="clienteId" value="<?php echo set_value('clienteId', $orden['info'][0]->id_cliente) ?>">
              </div>

              <div class="control-group">
                <label class="control-label" for="descripcion">Observaciones</label>
                <div class="controls">
                  <div class="input-append span12">
                    <textarea name="descripcion" class="span11" id="descripcion"
                      <?php echo $receta_readonly ?>><?php echo set_value('descripcion', $orden['info'][0]->descripcion) ?></textarea>
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="descripcion">Vehiculo</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="checkbox" name="es_vehiculo" id="es_vehiculo" data-uniform="false" value="si" data-next="fecha" <?php echo set_checkbox('es_vehiculo', 'si', $orden['info'][0]->id_vehiculo > 0 ? true : false); ?>></label>
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="autorizo">Autoriza</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="autorizo" class="span11" id="autorizo" value="<?php echo set_value('autorizo', $orden['info'][0]->autorizo) ?>" placeholder="" required <?php echo $receta_readonly ?>>
                  </div>
                </div>
                  <input type="hidden" name="autorizoId" id="autorizoId" value="<?php echo set_value('autorizoId', $orden['info'][0]->id_autorizo) ?>" required>
              </div>

            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', isset($orden['info'][0]->fecha) ? substr(str_replace(' ', 'T', $orden['info'][0]->fecha), 0, 16) : $fecha); ?>" <?php echo $readonly ?>>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="tipoOrden">Tipo de Orden</label>
                <div class="controls">
                  <select name="tipoOrden" class="span9" id="tipoOrden" <?php echo $disabled ?>>
                    <option value="p" <?php echo set_select('tipoOrden', 'p', $orden['info'][0]->tipo_orden === 'p' ? true : false); ?>>Productos</option>
                    <option value="d" <?php echo set_select('tipoOrden', 'd', $orden['info'][0]->tipo_orden === 'd' ? true : false); ?>>Servicios (Gasto)</option>
                    <option value="oc" <?php echo set_select('tipoOrden', 'oc', $orden['info'][0]->tipo_orden === 'oc' ? true : false); ?>>Gastos (Gasto)</option>
                    <option value="f" <?php echo set_select('tipoOrden', 'f', $orden['info'][0]->tipo_orden === 'f' ? true : false); ?>>Fletes (Gasto)</option>
                    <option value="a" <?php echo set_select('tipoOrden', 'a', $orden['info'][0]->tipo_orden === 'a' ? true : false); ?>>Activo</option>
                  </select>
                </div>
              </div>

              <div class="control-group" id="grpFleteDe" <?php echo ($orden['info'][0]->tipo_orden === 'f' || (isset($ordenFlete) && $ordenFlete) ? '': 'style="display:none;"'); ?>>
                <label class="control-label" for="fleteDe">Flete de</label>
                <div class="controls">
                  <select name="fleteDe" class="span9" id="fleteDe" <?php echo $disabled ?>>
                    <option value="v" <?php echo set_select('fleteDe', 'v', $orden['info'][0]->flete_de === 'v' ? true : false); ?>>Venta</option>
                    <option value="c" <?php echo set_select('fleteDe', 'c', $orden['info'][0]->flete_de === 'c' ? true : false); ?>>Compra</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio">Folio</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo set_value('folio', $orden['info'][0]->folio); ?>" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="tipoPago">Tipo de Pago</label>
                <div class="controls">
                  <select name="tipoPago" class="span9" id="tipoPago" <?php echo $disabled ?>>
                    <option value="cr" <?php echo set_select('tipoPago', 'cr', $orden['info'][0]->tipo_pago === 'cr' ? true : false); ?>>Credito</option>
                    <option value="co" <?php echo set_select('tipoPago', 'co', $orden['info'][0]->tipo_pago === 'co' ? true : false); ?>>Contado</option>
                  </select>
                </div>
              </div>

              <?php if (isset($orden['info'][0]->proyecto['info'])): ?>
              <div class="control-group">
                <label class="control-label" for="tipoPago">Proyecto asignado</label>
                <div class="controls">
                  <?php echo $orden['info'][0]->proyecto['info']->nombre ?>
                </div>
              </div>
              <?php endif ?>

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

              <?php if ($showButton){ ?>
                  <div class="control-group">
                    <div class="controls">
                      <div class="well span9">
                          <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;"><?php echo $txtButton ?></button><br><br>
                          <?php if($orden['info'][0]->status == 'a' && isset($orden['info'][0]->entrada_almacen->folio_almacen)){ ?>
                            <!-- <a href="#modal-imprimir" role="button" class="btn btn-primary" data-toggle="modal">Imprimir (entrada almacen)</a> -->
                            <!-- <a href="<?php echo base_url('panel/compras_ordenes/imprimir_entrada/?folio='.$orden['info'][0]->entrada_almacen->folio_almacen.'&ide='.$orden['info'][0]->id_empresa); ?>"
                              onclick="$('#modalIngresoAlmacen').modal('hide');" target="_blank" class="btn btn-primary">Imprimir (entrada almacen)</a> -->
                              <a href="<?php echo base_url('panel/compras_ordenes/ticket/?id='.$orden['info'][0]->id_orden); ?>"
                              onclick="$('#modalIngresoAlmacen').modal('hide');" target="_blank" class="btn btn-primary">Imprimir (entrada almacen)</a>
                          <?php } ?>
                          <?php if ($this->usuarios_model->tienePrivilegioDe("", "compras_ordenes/autorizar/") && isset($_GET['mod'])) { ?>
                            <label style="font-weight: bold;"><input type="checkbox" name="autorizar" value="1"> AUTORIZAR ENTRADA</label>
                          <?php } ?>
                      </div>
                    </div>
                  </div>
              <?php } ?>

            </div>
          </div>

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

                    <div class="control-group span7">
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

                    <div class="control-group span4">
                      <label class="control-label" for="infBoletasEntrada">Boletas entrada </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <button type="button" class="btn btn-info" id="show-boletasEntrada">Buscar</button>
                          <span id="boletasEntrada" style="cursor:pointer;">
                            <?php
                            $boletasEntFolios = '';
                            $boletasEntIds = '';
                            foreach ($orden['info'][0]->boletas_lig as $key => $value)
                            {
                              $boletasEntFolios .= $value->folio.' | ';
                              $boletasEntIds .= $value->id_bascula.'|';
                            }
                            echo $boletasEntFolios.' <input type="hidden" name="boletasEntradaId" value="'.$boletasEntIds.'"><input type="hidden" name="boletasEntradaFolio" value="'.$boletasEntFolios.'">';
                            ?>
                          </span>
                        </div>
                      </div>
                    </div><!--/control-group -->

                  </div>

                </div>

               </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->

          <!-- Box Productos -->
          <div class="row-fluid" id="groupVehiculo" style="display: <?php echo isset($_POST['es_vehiculo']) ? ($_POST['es_vehiculo'] === 'si' ? 'block' : 'none') : ($orden['info'][0]->id_vehiculo > 0 ? 'block' : 'none') ?>;">
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
                      <label class="control-label" for="area">Cultivo / Actividad / Producto </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="area" class="span11" id="area" value="" placeholder="Limon, Piña" <?php echo $receta_readonly ?>>
                        </div>
                      </div>
                      <ul class="tags" id="tagsAreaIds">
                      <?php if (isset($orden['info'][0]->area)) {
                        foreach ($orden['info'][0]->area as $key => $area) { ?>
                          <li class="<?php echo $readonlyCat==''? '': 'disable' ?>">
                            <span class="tag <?php echo $readonlyCat ?>"><?php echo $area->nombre ?></span>
                            <input type="hidden" name="areaId[]" class="areaId" value="<?php echo $area->id_area ?>">
                            <input type="hidden" name="areaText[]" class="areaText" value="<?php echo $area->nombre ?>">
                          </li>
                       <?php }} ?>
                      </ul>
                    </div><!--/control-group -->

                    <div class="control-group" id="ranchosGrup" style="display: <?php echo ($orden['info'][0]->tipo_orden !== 'f'? 'block' : 'none') ?>;">
                      <label class="control-label" for="rancho">Areas / Ranchos / Lineas </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="rancho" class="span11" id="rancho" value="" placeholder="Milagro A, Linea 1" <?php echo $receta_readonly ?>>
                        </div>
                      </div>
                      <ul class="tags" id="tagsRanchoIds">
                      <?php if (isset($orden['info'][0]->rancho)) {
                        foreach ($orden['info'][0]->rancho as $key => $rancho) { ?>
                          <li class="<?php echo $readonlyCat==''? '': 'disable' ?>">
                            <span class="tag <?php echo $readonlyCat ?>"><?php echo $rancho->nombre ?></span>
                            <input type="hidden" name="ranchoId[]" class="ranchoId" value="<?php echo $rancho->id_rancho ?>">
                            <input type="hidden" name="ranchoText[]" class="ranchoText" value="<?php echo $rancho->nombre ?>">
                          </li>
                       <?php }} ?>
                      </ul>
                    </div><!--/control-group -->
                  </div>

                  <div class="span6">
                    <div class="control-group" id="centrosCostosGrup" style="display: <?php echo ($orden['info'][0]->tipo_orden !== 'f'? 'block' : 'none') ?>;">
                      <label class="control-label" for="centroCosto">Centro de costo </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="centroCosto" class="span11" id="centroCosto" value="" placeholder="Mantenimiento, Gasto general" <?php echo $receta_readonly ?>>
                        </div>
                      </div>
                      <ul class="tags" id="tagsCCIds">
                      <?php if (isset($orden['info'][0]->centroCosto)) {
                        foreach ($orden['info'][0]->centroCosto as $key => $centroCosto) { ?>
                          <li class="<?php echo $readonlyCat==''? '': 'disable' ?>">
                            <span class="tag <?php echo $readonlyCat ?>"><?php echo $centroCosto->nombre ?></span>
                            <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="<?php echo $centroCosto->id_centro_costo ?>">
                            <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="<?php echo $centroCosto->nombre ?>">
                          </li>
                       <?php }} ?>
                      </ul>
                    </div><!--/control-group -->

                    <div class="control-group" id="activosGrup" style="display: <?php echo ($orden['info'][0]->tipo_orden !== 'f'? 'block' : 'none') ?>;">
                      <label class="control-label" for="activos">Activos </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="activos" class="span11" id="activos" value="" placeholder="Nissan FRX, Maquina limon" <?php echo $receta_readonly ?>>
                        </div>
                      </div>
                      <ul class="tags" id="tagsCCIds">
                      <?php if (isset($orden['info'][0]->activo)) {
                        foreach ($orden['info'][0]->activo as $key => $activo) { ?>
                          <li class="<?php echo $readonlyCat==''? '': 'disable' ?>">
                            <span class="tag <?php echo $readonlyCat ?>"><?php echo $activo->nombre ?></span>
                            <input type="hidden" name="activoId[]" class="activoId" value="<?php echo $activo->id_producto ?>">
                            <input type="hidden" name="activoText[]" class="activoText" value="<?php echo $activo->nombre ?>">
                          </li>
                       <?php }} ?>
                      </ul>
                    </div><!--/control-group -->
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
                      <input type="text" class="span12" id="fcodigo" placeholder="Codigo">
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
                    <div class="span2">
                      <label for="ftraslado" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">IVA</label>
                      <select class="span12" id="ftraslado">
                        <option value="0">0%</option>
                        <option value="8">8%</option>
                        <option value="16">16%</option>
                      </select>
                    </div><!--/span2 -->
                    <div class="span2">
                      <label for="fieps" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">IEPS (%)</label>
                      <input type="text" class="span12 vpositive" id="fieps" placeholder="%">
                    </div><!--/span2 -->
                    <div class="span2 offset1">
                      <?php if ($showButton){ ?>
                          <button type="button" class="btn btn-success span12" id="btnAddProd">Agregar</button>
                      <?php } ?>
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
                          <th>RET</th>
                          <th>RET ISR</th>
                          <th>IMPORTE</th>
                          <th>DESCRIP</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (isset($_POST['concepto'])) {
                              $retencionisr = $subtotal = $iva = $ieps = $total = $retencion = 0;
                              foreach ($_POST['concepto'] as $key => $concepto) {

                                $subtotal     += $_POST['importe'][$key];
                                $iva          += $_POST['trasladoTotal'][$key];
                                $ieps         += $_POST['iepsTotal'][$key];
                                $retencion    += $_POST['retTotal'][$key];
                                $retencionisr += $_POST['ret_isrTotal'][$key];
                                $total        += $_POST['total'][$key];
                            ?>
                            <tr>
                              <td style="width: 70px;">
                                <?php echo $_POST['codigo'][$key] ?>
                                <input type="hidden" name="codigo[]" value="<?php echo $_POST['codigo'][$key] ?>" class="span12">
                                <input type="hidden" name="tipo_cambio[]" value="<?php echo $_POST['tipo_cambio'][$key] ?>" class="span12">
                                <input type="hidden" name="prodIdOrden[]" value="<?php echo $_POST['prodIdOrden'][$key] ?>" class="span12">
                                <input type="hidden" name="prodIdNumRow[]" value="<?php echo $_POST['prodIdNumRow'][$key] ?>" class="span12">

                                <input type="hidden" name="codigoArea[]" value="<?php echo $_POST['codigoArea'][$key] ?>" id="codigoArea" class="span12 showCodigoAreaAuto">
                                <input type="hidden" name="codigoAreaId[]" value="<?php echo $_POST['codigoAreaId'][$key] ?>" id="codigoAreaId" class="span12">
                                <input type="hidden" name="codigoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12">
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
                                  <input type="number" step="any" name="cantidad[]" value="<?php echo $_POST['cantidad'][$key] ?>" id="cantidad" class="span12 vpositive" min="0">
                              </td>
                              <td style="width: 65px;">
                                  <input type="number" step="any" name="faltantes[]" value="<?php echo $_POST['faltantes'][$key] ?>" id="faltantes" class="span12 vpositive" min="0">
                              </td>
                              <td style="width: 90px;">
                                  <input type="text" name="valorUnitario[]" value="<?php echo $_POST['valorUnitario'][$key] ?>" id="valorUnitario" class="span12 vpositive">
                              </td>
                              <td style="width: 66px;">
                                  <select name="traslado[]" id="traslado" class="span12">
                                    <option value="0" <?php echo $_POST['traslado'][$key] === '0' ? 'selected' : '' ?>>0%</option>
                                    <option value="8" <?php echo $_POST['traslado'][$key] === '8' ? 'selected' : ''?>>11%</option>
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
                                  <select name="ret_iva[]" id="ret_iva" class="span12">
                                    <option value="0" <?php echo $_POST['ret_iva'][$key] === '0' ? "selected" : '' ?>>No retener</option>
                                    <option value="4" <?php echo $_POST['ret_iva'][$key] === '4' ? "selected" : '' ?>>4%</option>
                                    <option value="10.6667" <?php echo $_POST['ret_iva'][$key] === '10.6667' ? "selected" : '' ?>>2 Terceras</option>
                                    <option value="16" <?php echo $_POST['ret_iva'][$key] === '16' ? "selected" : '' ?>>100 %</option>
                                  </select>
                                   <input type="hidden" name="retTotal[]" value="<?php echo $_POST['retTotal'][$key] ?>" id="retTotal" class="span12" readonly>
                               </td>
                               <td style="width: 66px;">
                                  <input type="text" name="ret_isrPorcent[]" value="<?php echo $_POST['ret_isrPorcent'][$key] ?>" id="ret_isrPorcent" class="span12">
                                  <input type="hidden" name="ret_isrTotal[]" value="<?php echo $_POST['ret_isrTotal'][$key] ?>" id="ret_isrTotal" class="span12">
                               </td>
                              <td>
                                  <span><?php echo MyString::formatoNumero($_POST['importe'][$key]) ?></span>
                                  <input type="hidden" name="importe[]" value="<?php echo $_POST['importe'][$key] ?>" id="importe" class="span12 vpositive">
                                  <input type="hidden" name="total[]" value="<?php echo $_POST['total'][$key] ?>" id="total" class="span12 vpositive">
                              </td>
                              <td>
                                  <input type="text" name="observacion[]" value="<?php echo $_POST['observacion'][$key] ?>" id="observacion" class="span12 vpositive">
                              </td>
                              <td style="width: 35px;"><button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button></td>
                            </tr>
                          <?php  }} else {
                               $retencionisr = $subtotal = $iva = $ieps = $total = $retencion = 0;
                               foreach ($orden['info'][0]->productos as $key => $prod) {
                                  $subtotal += $prod->importe;
                                  $iva      += $prod->iva;
                                  $ieps     += $prod->ieps;
                                  $retencion += $prod->retencion_iva;
                                  $retencionisr += $prod->retencion_isr;
                                  $total    += $prod->total;

                                  if ($prod->id_presentacion !== null)
                                  {
                                    $cantidad = $prod->cantidad / $prod->presen_cantidad;
                                    $pu       = $prod->precio_unitario * $prod->presen_cantidad;
                                  }
                                  else
                                  {
                                    $cantidad = $prod->cantidad;
                                    $pu       = $prod->precio_unitario;
                                  }

                                  $readonly = $prod->status === 'a' ? 'readonly' : '';
                                  $disabled = $prod->status === 'a' ? '' : '';

                                  $redBg    = $prod->status === 'r' ? 'background-color: #FFE5E5;' : '';

                                  $htmlProdOk = '';
                                  if ( ! isset($_GET['mod']))
                                  {
                                    if ($prod->status === 'a')
                                    {
                                      if ($prodOk)
                                      { //<input type="checkbox" value="1" class="prodOk" checked>
                                        $htmlProdOk = '<input type="hidden" name="isProdOk[]" value="1" id="idProdOk">';
                                      }
                                      else
                                      {
                                        $htmlProdOk = '<input type="hidden" name="isProdOk[]" value="1" id="idProdOk">';
                                      }
                                    }
                                    else
                                    {
                                      if ($prodOk)
                                      {
                                        $htmlProdOk = '<input type="checkbox" value="1" class="prodOk"><input type="hidden" name="isProdOk[]" value="0" id="idProdOk">';
                                      }
                                      else
                                      {
                                        $htmlProdOk = '<input type="hidden" name="isProdOk[]" value="0" id="idProdOk">';
                                      }
                                    }
                                  }
                                ?>
                               <tr>
                                 <td style="width: 70px;<?php echo $redBg ?>">
                                   <?php echo $htmlProdOk ?>
                                   <?php echo $prod->codigo?>
                                   <input type="hidden" name="codigo[]" value="<?php echo $prod->codigo || '' ?>" class="span12">
                                   <input type="hidden" name="tipo_cambio[]" value="<?php echo $prod->tipo_cambio ?>" class="span12">
                                   <input type="hidden" name="prodIdOrden[]" value="<?php echo $prod->id_orden ?>" class="span12" id="prodIdOrden">
                                   <input type="hidden" name="prodIdNumRow[]" value="<?php echo $prod->num_row ?>" class="span12" id="prodIdNumRow">

                                    <input type="text" name="codigoArea[]" value="<?php echo $prod->codigo_fin ?>" id="codigoArea" class="span12 showCodigoAreaAuto" data-call="ComprasOrdenes">
                                    <input type="hidden" name="codigoAreaId[]" value="<?php echo $prod->id_area ?>" id="codigoAreaId" class="span12">
                                    <input type="hidden" name="codigoCampo[]" value="<?php echo $prod->campo ?>" id="codigoCampo" class="span12">
                                    <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                                   <!-- <input type="hidden" name="codigoArea[]" value="<?php echo $prod->codigo_fin ?>" id="codigoArea" class="span12 showCodigoAreaAuto">
                                   <input type="hidden" name="codigoAreaId[]" value="<?php echo $prod->id_area ?>" id="codigoAreaId" class="span12"> -->
                                 </td>
                                 <td style="<?php echo $redBg ?>">
                                     <?php echo $prod->descripcion ?>
                                     <input type="hidden" name="concepto[]" value="<?php echo $prod->descripcion ?>" id="concepto" class="span12">
                                     <input type="hidden" name="productoId[]" value="<?php echo $prod->id_producto ?>" id="productoId" class="span12">
                                 </td>
                                 <td style="width: 160px;<?php echo $redBg ?>">
                                    <?php if ($prod->id_presentacion){ ?>
                                      <select name="presentacion[]" <?php echo $readonly ?>>
                                        <option value="<?php echo $prod->id_presentacion ?>" data-cantidad="<?php echo $prod->presen_cantidad ?>"><?php echo $prod->presentacion .' '.$prod->presen_cantidad .' '.$prod->abreviatura ?></option>
                                      </select>
                                      <input type="hidden" name="presentacionCant[]" value="<?php echo $prod->presen_cantidad ?>" id="presentacionCant" class="span12">
                                      <input type="hidden" name="presentacionText[]" value="<?php echo $prod->presentacion .' '.$prod->presen_cantidad .' '.$prod->abreviatura ?>" id="presentacionText" class="span12">
                                    <?php } else { ?>
                                      <select name="presentacion[]" <?php echo $readonly ?>>
                                        <option value="" data-cantidad=""></option>
                                      </select>
                                      <input type="hidden" name="presentacionCant[]" value="" id="presentacionCant" class="span12">
                                      <input type="hidden" name="presentacionText[]" value="" id="presentacionText" class="span12">
                                    <?php } ?>
                                 </td>
                                 <td style="width: 120px;<?php echo $redBg ?>">
                                   <select name="unidad[]" id="unidad" class="span12" <?php echo $disabled.' '.$readonly ?>>
                                     <?php foreach ($unidades as $unidad) { ?>
                                       <option value="<?php echo $unidad->id_unidad ?>" <?php echo $prod->id_unidad == $unidad->id_unidad ? 'selected' : ''; ?>><?php echo $unidad->nombre ?></option>
                                     <?php } ?>
                                   </select>
                                 </td>
                                 <td style="width: 65px;<?php echo $redBg ?>">
                                     <input type="number" step="any" name="cantidad[]" value="<?php echo $cantidad ?>" id="cantidad" class="span12 vpositive" min="0" <?php echo $readonly ?>>
                                 </td>
                                 <td style="width: 65px;<?php echo $redBg ?>">
                                     <input type="number" step="any" name="faltantes[]" value="<?php echo $prod->faltantes ?>" id="faltantes" class="span12 vpositive" min="0" <?php echo $readonly ?>>
                                 </td>
                                 <td style="width: 90px;<?php echo $redBg ?>">
                                     <input type="text" name="valorUnitario[]" value="<?php echo $pu ?>" id="valorUnitario" class="span12 vpositive" <?php echo $readonly ?>>
                                 </td>
                                 <td style="width: 66px;<?php echo $redBg ?>">
                                     <select name="traslado[]" id="traslado" class="span12" <?php echo $disabled.' '.$readonly ?>>
                                       <option value="0" <?php echo $prod->porcentaje_iva === '0' ? 'selected' : '' ?>>0%</option>
                                       <option value="8" <?php echo $prod->porcentaje_iva === '8' ? 'selected' : ''?>>8%</option>
                                       <option value="16" <?php echo $prod->porcentaje_iva === '16' ? 'selected' : ''?>>16%</option>
                                     </select>
                                     <input type="hidden" name="trasladoTotal[]" value="<?php echo $prod->iva ?>" id="trasladoTotal" class="span12">
                                     <input type="hidden" name="trasladoPorcent[]" value="<?php echo $prod->porcentaje_iva ?>" id="trasladoPorcent" class="span12">
                                 </td>
                                 <td style="width: 66px;">
                                   <input type="text" name="iepsPorcent[]" value="<?php echo $prod->porcentaje_ieps ?>" id="iepsPorcent" <?php echo $readonly ?> class="span12">
                                   <input type="hidden" name="iepsTotal[]" value="<?php echo $prod->ieps ?>" id="iepsTotal" class="span12">
                                 </td>
                                 <td style="width: 66px;<?php echo $redBg ?>">
                                    <select name="ret_iva[]" id="ret_iva" class="span12">
                                      <option value="0" <?php echo $prod->porcentaje_retencion === '0' ? "selected" : '' ?>>No retener</option>
                                      <option value="4" <?php echo $prod->porcentaje_retencion === '4' ? "selected" : '' ?>>4%</option>
                                      <option value="10.6667" <?php echo $prod->porcentaje_retencion === '10.6667' ? "selected" : '' ?>>2 Terceras</option>
                                      <option value="16" <?php echo $prod->porcentaje_retencion === '16' ? "selected" : '' ?>>100 %</option>
                                    </select>
                                     <input type="hidden" name="retTotal[]" value="<?php echo $prod->retencion_iva ?>" id="retTotal" class="span12" readonly>
                                 </td>
                                 <td style="width: 66px;">
                                    <input type="text" name="ret_isrPorcent[]" value="<?php echo $prod->porcentaje_isr ?>" id="ret_isrPorcent" class="span12 vpositive">
                                    <input type="hidden" name="ret_isrTotal[]" value="<?php echo $prod->retencion_isr ?>" id="ret_isrTotal" class="span12">
                                 </td>
                                 <td style="<?php echo $redBg ?>">
                                     <span><?php echo MyString::formatoNumero($prod->importe) ?></span>
                                     <input type="hidden" name="importe[]" value="<?php echo $prod->importe ?>" id="importe" class="span12 vpositive">
                                     <input type="hidden" name="total[]" value="<?php echo $prod->total ?>" id="total" class="span12 vpositive">
                                 </td>
                                 <td style="<?php echo $redBg ?>">
                                    <input type="text" name="observacion[]" value="<?php echo $prod->observacion ?>" id="observacion" class="span12" <?php echo $readonly ?>>
                                </td>
                                 <td style="width: 35px;<?php echo $redBg ?>">
                                  <?php if ($showButton && $prod->status != 'a'){ ?>
                                    <button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button>
                                  <?php } ?>
                                </td>
                               </tr>
                         <?php  }} ?>
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
                        <textarea name="totalLetra" rows="5" class="nokey" style="width:98%;max-width:98%;" id="totalLetra" readonly><?php echo set_value('totalLetra', MyString::num2letras($total));?></textarea>
                    </td>
                  </tr>
                  <tr>
                    <td><em>Subtotal</em></td>
                    <td id="importe-format"><?php echo MyString::formatoNumero(set_value('totalImporte', $subtotal))?></td>
                    <input type="hidden" name="totalImporte" id="totalImporte" value="<?php echo set_value('totalImporte', $subtotal); ?>">
                  </tr>
                  <tr>
                    <td>IVA</td>
                    <td id="traslado-format"><?php echo MyString::formatoNumero(set_value('totalImpuestosTrasladados', $iva))?></td>
                    <input type="hidden" name="totalImpuestosTrasladados" id="totalImpuestosTrasladados" value="<?php echo set_value('totalImpuestosTrasladados', $iva); ?>">
                  </tr>
                  <tr>
                    <td>IEPS</td>
                    <td id="ieps-format"><?php echo MyString::formatoNumero(set_value('totalIeps', $ieps))?></td>
                    <input type="hidden" name="totalIeps" id="totalIeps" value="<?php echo set_value('totalIeps', $ieps); ?>">
                  </tr>
                  <tr>
                    <td>RET.</td>
                    <td id="retencion-format"><?php echo MyString::formatoNumero(set_value('totalRetencion', $retencion))?></td>
                    <input type="hidden" name="totalRetencion" id="totalRetencion" value="<?php echo set_value('totalRetencion', $retencion); ?>">
                  </tr>
                  <tr>
                    <td>RET ISR</td>
                    <td id="retencionisr-format"><?php echo MyString::formatoNumero(set_value('totalRetencionIsr', $retencionisr))?></td>
                    <input type="hidden" name="totalRetencionIsr" id="totalRetencionIsr" value="<?php echo set_value('totalRetencionIsr', $retencion); ?>">
                  </tr>
                  <tr style="font-weight:bold;font-size:1.2em;">
                    <td>TOTAL</td>
                    <td id="total-format"><?php echo MyString::formatoNumero(set_value('totalOrden', $total))?></td>
                    <input type="hidden" name="totalOrden" id="totalOrden" value="<?php echo set_value('totalOrden', $total); ?>">
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
              <th>Área</th>
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

  <!-- Modal -->
  <div id="modal-imprimir" class="modal modal-w50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Imprimir (entrada almacen)</h3>
    </div>
    <div class="modal-body">
      <div class="row-fluid">
        <select name="lista_impresoras" id="lista_impresoras">
        <?php foreach ($impresoras as $key => $value) { ?>
          <option value="<?php echo base64_encode($value->ruta) ?>"><?php echo $value->impresora ?></option>
        <?php } ?>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="BtnImprimir" data-folio="<?php echo $orden['info'][0]->entrada_almacen->folio_almacen ?>"
        data-ide="<?php echo $orden['info'][0]->id_empresa ?>">Imprimir</button>
    </div>
  </div><!--/modal impresoras -->


<?php if (isset($print)) {
    if($orden['info'][0]->tipo_orden === 'p' && isset($_GET['entrada'])){ ?>
  <!-- Modal ingreso almacen -->
  <div id="modalIngresoAlmacen" class="modal hide fade" tabindex="-1" role="dialog">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="modalIngresoAlmacenLbl">Imprimir Ingreso Almacen?</h3>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <!-- <a href="<?php echo base_url('panel/compras_ordenes/imprimir_entrada/?folio='.$_GET['entrada'].'&ide='.$orden['info'][0]->id_empresa); ?>" onclick="$('#modalIngresoAlmacen').modal('hide');" target="_blank" class="btn btn-primary">Imprimir</a> -->
      <a href="<?php echo base_url('panel/compras_ordenes/ticket/?id='.$_GET['id'].'&p=true'); ?>" onclick="$('#modalIngresoAlmacen').modal('hide');" target="_blank" class="btn btn-primary">Imprimir</a>
    </div>
  </div>
  <?php } ?>
  <script>
    $('#modalIngresoAlmacen').modal('show');
    var win=window.open(<?php echo "'".base_url('panel/compras_ordenes/ticket/?id=' . $_GET['id'].'&p=true'."'") ?>, '_blank');
    win.focus();
  </script>
<?php } ?>

<?php if (isset($print_faltantes)) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/compras_ordenes/imprimir_recibo_faltantes/?id=' . $_GET['id'].'&p=true'."'") ?>, '_blank');
    win.focus();
  </script>
<?php } ?>


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