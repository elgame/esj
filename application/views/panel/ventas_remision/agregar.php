<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/ventas/'); ?>">Ventas de Remisión</a> <span class="divider">/</span>
      </li>
      <li>Agregar</li>
    </ul>
  </div>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> <?php echo (isset($_GET['id_nrc'])? 'Agregar Nota de credito': 'Agregar Venta de Remisión') ?></h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">
    <?php if(isset($_GET['id_nrc'])){ ?>
        <span id="isNotaCredito"></span>
    <?php } ?>

        <input type="hidden" id="isBodegaGdl" value="<?php echo $this->config->item('is_bodega') ?>">

        <form class="form-horizontal" action="<?php echo base_url('panel/ventas/agregar/'.$getId.(isset($_GET['id_nr'])? '?id_nr='.$_GET['id_nr']:'')); ?>" method="POST" id="form">
          <?php
            if($this->usuarios_model->tienePrivilegioDe('', 'facturacion/prod_descripciones/')){ ?>
              <input type="hidden" value="si" name="privAddDescripciones" id="privAddDescripciones">
          <?php } ?>

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="dempresa">Empresa</label>
                <div class="controls">

                  <input type="text" name="dempresa" class="span9" id="dempresa" value="<?php echo set_value('dempresa', isset($borrador) ? $borrador['info']->empresa->nombre_fiscal : $empresa_default->nombre_fiscal); ?>" size="73" autofocus>
                  <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value('did_empresa', isset($borrador) ? $borrador['info']->empresa->id_empresa : $empresa_default->id_empresa); ?>">
                  <input type="hidden" name="dversion" id="dversion" value="<?php echo set_value('dversion', isset($borrador) ? $borrador['info']->version :$empresa_default->cfdi_version); ?>">
                  <input type="hidden" name="dcer_caduca" id="dcer_caduca" value="<?php echo set_value('dcer_caduca', isset($borrador) ? $borrador['info']->empresa->cer_caduca : $empresa_default->cer_caduca); ?>">
                  <input type="hidden" name="dno_certificado" id="dno_certificado" value="<?php echo set_value('dno_certificado', isset($borrador) ? $borrador['info']->no_certificado : $no_certificado); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dserie">Serie</label>
                <div class="controls">
                  <select name="dserie" class="span9" id="dserie">
                     <option value="void"></option>
                     <?php // foreach($series['series'] as $ser){ ?>
                          <!-- <option value="<?php // echo $ser->serie; ?>" <?php // echo set_select('dserie', $ser->serie); ?>> -->
                            <?php // echo $ser->serie.($ser->leyenda!=''? '-'.$ser->leyenda: ''); ?></option>
                      <?php // } ?>
                  </select>
                  <input type="hidden" id="serie-selected" value="<?php echo set_value('dserie', isset($borrador) ? $borrador['info']->serie : '') ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dfolio">Folio</label>
                <div class="controls">
                  <input type="number" name="dfolio" class="span9 nokey" id="dfolio" value="<?php echo isset($_POST['dfolio']) ? $_POST['dfolio'] : (isset($borrador)? $borrador['info']->folio: ''); ?>" size="15" >
                  <input type="hidden" name="dano_aprobacion" id="dano_aprobacion" value="<?php echo set_value('dano_aprobacion'); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente">Cliente</label>
                <div class="controls">
                  <input type="text" name="dcliente" class="span9" id="dcliente" value="<?php echo set_value('dcliente', isset($borrador) ? $borrador['info']->cliente->nombre_fiscal : ''); ?>" size="73">
                  <input type="hidden" name="did_cliente" id="did_cliente" value="<?php echo set_value('did_cliente', isset($borrador) ? $borrador['info']->cliente->id_cliente : ''); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente_rfc">RFC</label>
                <div class="controls">
                  <input type="text" name="dcliente_rfc" class="span9" id="dcliente_rfc" value="<?php echo set_value('dcliente_rfc', isset($borrador) ? $borrador['info']->cliente->rfc : ''); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente_domici">Domicilio</label>
                <div class="controls">

                  <?php
                      $domi = '';
                      if (isset($borrador))
                      {
                        $domi .= $borrador['info']->cliente->calle       !== '' ? $borrador['info']->cliente->calle : '';
                        $domi .= $borrador['info']->cliente->no_exterior !== '' ? ', #'.$borrador['info']->cliente->no_exterior : '';
                        $domi .= $borrador['info']->cliente->no_interior !== '' ? '-'.$borrador['info']->cliente->no_interior : '';
                        $domi .= $borrador['info']->cliente->colonia     !== '' ? ' '.$borrador['info']->cliente->colonia : '';
                      }
                   ?>

                  <input type="text" name="dcliente_domici" class="span9" id="dcliente_domici" value="<?php echo set_value('dcliente_domici', $domi); ?>" size="65" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente_ciudad">Ciudad</label>
                <div class="controls">

                  <?php
                      $ciudad = '';
                      if (isset($borrador))
                      {
                        $ciudad .= $borrador['info']->cliente->municipio !== '' ? ', '.$borrador['info']->cliente->municipio : '';
                        $ciudad .= $borrador['info']->cliente->estado    !== '' ? ', '.$borrador['info']->cliente->estado : '';
                        $ciudad .= $borrador['info']->cliente->cp        !== '' ? ', C.P.'.$borrador['info']->cliente->cp : '';
                      }
                   ?>

                  <input type="text" name="dcliente_ciudad" class="span9" id="dcliente_ciudad" value="<?php echo set_value('dcliente_ciudad', $ciudad); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dobservaciones">Observaciones</label>
                <div class="controls">
                  <textarea name="dobservaciones" class="span9" id="dobservaciones"><?php echo set_value('dobservaciones', isset($borrador) ? $borrador['info']->observaciones : ''); ?></textarea>
                </div>
              </div>

              <div class="control-group" style="background-color: #fffed7">
                <label class="control-label" for="dno_trazabilidad">No Trazabilidad</label>
                <div class="controls">
                  <input type="text" name="dno_trazabilidad" class="span9" id="dno_trazabilidad"
                    value="<?php echo set_value('dno_trazabilidad', isset($borrador) ? $borrador['info']->no_trazabilidad : ''); ?>" placeholder="">
                  <input type="hidden" name="id_paleta_salida" value="<?php echo (isset($borrador) ? $borrador['info']->id_paleta_salida : ''); ?>">
                </div>
              </div>

              <div class="control-group" style="background-color: #bef7b0">
                <label class="control-label" for="dno_salida_fruta">No Salida de fruta</label>
                <div class="controls">
                  <input type="text" name="dno_salida_fruta" class="span9" id="dno_salida_fruta"
                    value="<?php echo set_value('dno_salida_fruta', isset($borrador) ? $borrador['info']->no_salida_fruta : ''); ?>" placeholder="">
                </div>
              </div>

              <?php if( isset($_GET['id_nrc']) ){ ?>
                <input type="hidden" name="id_nrc" value="<?php echo $_GET['id_nrc']; ?>">
              <?php }else{ ?>
              <div class="control-group" style="margin-top: 145px;">
                <label class="control-label">Folio Pallet</label>
                <div class="controls">
                  <div class="input-append">
                    <input type="text" id="folioPallet" class="span7 nokey vinteger"><button type="button" class="btn btn-info" id="loadPallet">Cargar</button>
                    <button type="button" class="btn btn-info" id="show-pallets">Ver Pallets</button>
                  </div>
                </div>
              </div>
              <div class="control-group">
                <div class="controls">
                  <div class="input-append pull-left"> <?php $chk_sincosto = isset($borrador) ? ($borrador['info']->sin_costo == 't' ? 'checked' : '' ) : (isset($_POST['dsincosto']) ? 'checked' : ''); ?>
                    <label class="control-label">Sin Costo <input type="checkbox" name="dsincosto" id="dsincosto" class="nokey" <?php echo $chk_sincosto; ?> disabled></label>
                  </div>

                  <div class="input-append <?php echo $chk_sincosto=='checked'? '': 'hide'; ?>  pull-left" id="dsincosto_novergrup">
                    <label class="control-label">No ver <input type="checkbox" name="dsincosto_nover" id="dsincosto_nover" class="nokey" <?php echo isset($borrador) ? ($borrador['info']->sin_costo == 't' ? 'checked' : '' ) : (isset($_POST['dsincosto_nover']) ? 'checked' : '') ?>></label>
                  </div>
                </div>
              </div>
              <?php } ?>
            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="dfecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="dfecha" class="span9" id="dfecha" value="<?php echo set_value('dfecha', $fecha); ?>" size="25">
                </div>
              </div>

              <div class="control-group" style="display:none;">
                <label class="control-label" for="dno_aprobacion">No. Aprobación</label>
                <div class="controls">
                  <input type="text" name="dno_aprobacion" class="span9" id="dno_aprobacion" value="<?php echo set_value('dno_aprobacion'); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="moneda">Moneda</label>
                <div class="controls">
                  <?php $moneda_borrado = isset($borrador) ? $borrador['info']->moneda : (isset($_POST['moneda'])? $_POST['moneda']: 'MXN');
                  $moneda_borradover = (set_select('moneda', 'MXN', false, $moneda_borrado)==' selected="selected"' || $moneda_borrado=='MXN')? 'none': 'block';
                  ?>
                  <select name="moneda" class="span8 pull-left" id="moneda">
                    <option value="MXN" <?php echo set_select('moneda', 'MXN', false, $moneda_borrado); ?>>Peso mexicano (MXN)</option>
                    <option value="USD" <?php echo set_select('moneda', 'USD', false, $moneda_borrado); ?>>Dólar estadounidense (USD)</option>
                  </select>
                  <input type="text" name="tipoCambio" class="span3 pull-left vpositive" id="tipoCambio" value="<?php echo set_value('tipoCambio', isset($borrador) ? $borrador['info']->tipo_cambio : ''); ?>"
                    style="display:<?php echo $moneda_borradover; ?>" placeholder="Tipo de Cambio">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dno_aprobacion">Tipo comprobante</label>
                <div class="controls">
                  <select name="dtipo_comprobante" class="span9" id="dtipo_comprobante">
                    <option value="ingreso" <?php echo set_select('dtipo_comprobante', 'ingreso'); ?>>Ingreso</option>
                    <!-- <option value="egreso" <?php //echo set_select('dtipo_comprobante', 'egreso'); ?>>Egreso</option> -->
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dforma_pago">Forma de pago</label>
                <div class="controls">
                  <select name="dforma_pago" class="span9" id="dforma_pago">
                    <?php
                    $formap = isset($borrador) ? $borrador['info']->forma_pago : '';
                    foreach ($formaPago as $key => $frp) { ?>
                      <option value="<?php echo $frp['key'] ?>" <?php echo set_select('dmetodo_pago', $frp['key'], $formap === $frp['key'] ? true : false); ?>><?php echo $frp['key'].' - '.$frp['value'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="control-group hide">
                <label class="control-label" for="dforma_pago">Parcialidades</label>
                <div class="controls">
                  <input type="text" name="dforma_pago_parcialidad" class="span9" id="dforma_pago_parcialidad" value="<?php echo set_value('dforma_pago_parcialidad', 'Parcialidad 1 de X'); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dmetodo_pago">Metodo de pago</label>
                <div class="controls">
                  <select name="dmetodo_pago" class="span9" id="dmetodo_pago">

                    <?php
                      $metodo = isset($borrador) ? $borrador['info']->metodo_pago : '';
                     ?>
                    <?php foreach ($metodosPago as $key => $mtp) { ?>
                      <option value="<?php echo $mtp['key'] ?>" <?php echo set_select('dmetodo_pago', $mtp['key'], $metodo === $mtp['key'] ? true : false); ?>><?php echo $mtp['key'].' - '.$mtp['value'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="control-group hide">
                <label class="control-label" for="dmetodo_pago_digitos">Ultimos 4 digitos</label>
                <div class="controls">
                  <input type="text" name="dmetodo_pago_digitos" class="span9" id="dmetodo_pago_digitos" value="<?php echo set_value('dmetodo_pago_digitos', isset($borrador) ? $borrador['info']->metodo_pago_digitos : 'No identificado'); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcondicion_pago">Condición de pago</label>
                <div class="controls">
                  <select name="dcondicion_pago" class="span9" id="dcondicion_pago">

                    <?php
                      $condicion = isset($borrador) ? $borrador['info']->condicion_pago : '';
                     ?>

                    <option value="cr" <?php echo set_select('dcondicion_pago', 'cr', $condicion === 'cr' ? true : false); ?>>Credito</option>
                    <option value="co" <?php echo set_select('dcondicion_pago', 'co', $condicion === 'co' ? true : false); ?>>Contado</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcondicion_pago">Plazo de crédito</label>
                <div class="controls">
                  <input type="number" name="dplazo_credito" class="span9 vinteger" id="dplazo_credito" value="<?php echo set_value('dplazo_credito', isset($borrador) ? $borrador['info']->plazo_credito : 0); ?>">
                </div>
              </div>

              <?php
              if (!isset($borrador) || (isset($borrador) && $borrador['info']->version > 3.2)) {
              ?>
              <div class="control-group">
                <label class="control-label" for="duso_cfdi">Uso de CFDI</label>
                <div class="controls">
                  <select name="duso_cfdi" class="span9" id="duso_cfdi">

                    <?php
                      $metodo = isset($borrador) ? $borrador['info']->cfdi_ext->usoCfdi : '';
                     ?>
                    <?php foreach ($usoCfdi as $key => $usoCfdi) { ?>
                      <option value="<?php echo $usoCfdi['key'] ?>" <?php echo set_select('duso_cfdi', $usoCfdi['key'], $metodo === $usoCfdi['key'] ? true : false); ?>><?php echo $usoCfdi['key'].' - '.$usoCfdi['value'] ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <?php }?>

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <label><input type="checkbox" name="cerrarVenta" value="true"
                        <?php echo ($cerrarVenta ? 'checked' : '') ?>
                        <?php echo ($cerrarVenta && !$desbloquear? 'disabled': ''); ?>> Cerrar Venta</label> <br>

                      <?php if (!$cerrarVenta || $desbloquear): ?>
                      <!-- <button type="submit" name="borrador" class="btn btn-success btn-large btn-block" style="width:100%;" id="">Guardar</button><br><br> -->
                      <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;" name="guardar">Guardar</button>
                      <?php endif ?>

                      <?php
                      $show_imprimir = 'none';
                      if (isset($borrador['info']->empresa->nombre_fiscal) && preg_match("/bodega/i", $borrador['info']->empresa->nombre_fiscal))
                        $show_imprimir = 'block';
                      ?>
                      <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;display:<?php echo $show_imprimir ?>;" name="guardar_imp" id="guardar_imp">Guardar e Imprimir</button>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <div class="row-fluid">
            <div class="span12">
              <table class="table table-striped table-bordered table-hover table-condensed" id="table_prod">
                <thead>
                  <tr>
                    <th></th>
                    <th>Clasificación</th>
                    <th>Medida</th>
                    <th>Cant.</th>
                    <th>P Unitario</th>
                    <th>IVA%</th>
                    <th>IVA</th>
                    <th>Retención</th>
                    <th>Importe</th>
                    <th>Cert.</th>
                    <th>Accion</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                        if (isset($borrador) && ! isset($_POST['prod_did_prod']) && (isset($_GET['id_nr']) ||  isset($_GET['id_vd'])) )
                        {
                          foreach ($borrador['productos'] as $key => $p) {
                            $_POST['prod_dcalidad'][$key]           = $p->areas_calidad;
                            $_POST['prod_did_calidad'][$key]        = $p->id_calidad;
                            $_POST['prod_dtamanio'][$key]           = $p->areas_tamanio;
                            $_POST['prod_did_tamanio'][$key]        = $p->id_tamanio;
                            $_POST['prod_ddescripcion2'][$key]      = $p->descripcion2;
                            $_POST['prod_dtamanio_prod'][$key]      = $p->areas_calibre;
                            $_POST['prod_did_tamanio_prod'][$key]   = $p->id_calibre;

                            $_POST['prod_did_prod'][$key]           = $p->id_clasificacion;
                            $_POST['prod_importe'][$key]            = $p->importe;
                            $_POST['prod_ddescripcion'][$key]       = $p->descripcion;
                            $_POST['prod_dmedida'][$key]            = $p->unidad;
                            $_POST['prod_dcantidad'][$key]          = $p->cantidad;
                            $_POST['prod_dpreciou'][$key]           = $p->precio_unitario;
                            $_POST['prod_diva_porcent'][$key]       = $p->porcentaje_iva_real; //$p->porcentaje_iva;
                            $_POST['prod_diva_total'][$key]         = $p->iva;
                            $_POST['prod_dreten_iva_porcent'][$key] = $p->porcentaje_retencion;
                            $_POST['prod_dreten_iva_total'][$key]   = $p->retencion_iva;
                            $_POST['pallets_id'][$key]      = $p->ids_pallets;
                            $_POST['prod_dkilos'][$key]     = $p->kilos;
                            $_POST['prod_dcajas'][$key]     = $p->cajas;
                            $_POST['id_unidad_rendimiento'][$key] = $p->id_unidad_rendimiento;
                            // $_POST['id_size_rendimiento'][$key] = $p->id_size_rendimiento;
                            $_POST['dieps_total'][$key]             = $p->ieps;
                            $_POST['dieps'][$key]                   = $p->porcentaje_ieps;
                            $_POST['disr_total'][$key]              = $p->isr;
                            $_POST['disr'][$key]                    = $p->porcentaje_isr;

                            $_POST['prod_dmedida_id'][$key] = $p->id_unidad;
                            $_POST['isCert'][$key] = $p->certificado === 't' ? '1' : '0';

                            $_POST['prod_ieps_subtotal'][$key] = $p->ieps_subtotal;

                            $cfdi_extp = json_decode($p->cfdi_ext);
                            $_POST['pclave_unidad'][$key]     = $cfdi_extp->clave_unidad->value;
                            $_POST['pclave_unidad_cod'][$key] = $cfdi_extp->clave_unidad->key;
                          }
                        } ?>

                        <?php if (isset($_POST['prod_did_prod'])) {
                          foreach ($_POST['prod_did_prod'] as $k => $v) {
                            if ($_POST['prod_dcantidad'][$k] != 0) {
                            ?>
                              <tr data-pallets="<?php echo $_POST['pallets_id'][$k] ?>">
                                <td style="width:31px;">
                                  <div class="btn-group">
                                    <button type="button" class="btn ventasmore">
                                      <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu ventasmore">
                                      <li class="clearfix">
                                        <label class="pull-left">Calidad:</label> <input type="text" name="prod_dcalidad[]" value="<?php echo $_POST['prod_dcalidad'][$k]?>" id="prod_dcalidad" class="span9 pull-right">
                                        <input type="hidden" name="prod_did_calidad[]" value="<?php echo $_POST['prod_did_calidad'][$k]?>" id="prod_did_calidad" class="span12">
                                      </li>
                                      <li class="divider"></li>
                                      <li class="clearfix">
                                        <label class="pull-left">Tamaño:</label> <input type="text" name="prod_dtamanio[]" value="<?php echo $_POST['prod_dtamanio'][$k]?>" id="prod_dtamanio" class="span9 pull-right">
                                        <input type="hidden" name="prod_did_tamanio[]" value="<?php echo $_POST['prod_did_tamanio'][$k]?>" id="prod_did_tamanio" class="span12">
                                      </li>
                                      <li class="divider"></li>
                                      <li class="clearfix">
                                        <label class="pull-left">TamañoProd</label> <input type="text" name="prod_dtamanio_prod[]" value="<?php echo $_POST['prod_dtamanio_prod'][$k]?>" id="prod_dtamanio_prod" class="span9 pull-right">
                                        <input type="hidden" name="prod_did_tamanio_prod[]" value="<?php echo $_POST['prod_did_tamanio_prod'][$k]?>" id="prod_did_tamanio_prod" class="span12">
                                      </li>
                                      <li class="divider"></li>
                                      <li class="clearfix">
                                        <label class="pull-left">Descripción:</label> <input type="text" name="prod_ddescripcion2[]" value="<?php echo $_POST['prod_ddescripcion2'][$k]?>" id="prod_ddescripcion2" class="span9 pull-right">
                                      </li>
                                    </ul>
                                  </div>
                                </td>
                                <td>
                                  <input type="text" name="prod_ddescripcion[]" class="span12" value="<?php echo $_POST['prod_ddescripcion'][$k]?>" id="prod_ddescripcion">
                                  <input type="hidden" name="prod_did_prod[]" class="span12" value="<?php echo $v ?>" id="prod_did_prod">
                                  <input type="hidden" name="pallets_id[]" value="<?php echo $_POST['pallets_id'][$k] ?>" id="pallets_id" class="span12">
                                  <input type="hidden" name="id_unidad_rendimiento[]" value="<?php echo $_POST['id_unidad_rendimiento'][$k] ?>" id="id_unidad_rendimiento" class="span12">
                                  <input type="hidden" name="prod_ieps_subtotal[]" value="<?php echo $_POST['prod_ieps_subtotal'][$k] ?>" id="prod_ieps_subtotal" class="span12">
                                  <!-- <input type="hidden" name="id_size_rendimiento[]" value="<?php //echo $_POST['id_size_rendimiento'][$k] ?>" id="id_size_rendimiento" class="span12"> -->
                                </td>
                                <td>
                                  <select name="prod_dmedida[]" id="prod_dmedida" class="span12">
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->nombre ?>" <?php echo $_POST['prod_dmedida'][$k] == $u->nombre ? 'selected' : '' ?> data-id="<?php echo $u->id_unidad ?>"><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                  <input type="hidden" name="prod_dmedida_id[]" value="<?php echo $_POST['prod_dmedida_id'][$k] ?>" id="prod_dmedida_id" class="span12 vpositive">

                                  <input type="text" name="pclave_unidad[]" class="span12" id="pclave_unidad" value="<?php echo $_POST['pclave_unidad'][$k] ?>" placeholder="Clave de Unidad">
                                  <input type="hidden" name="pclave_unidad_cod[]" class="span9" id="pclave_unidad_cod" value="<?php echo $_POST['pclave_unidad_cod'][$k] ?>">
                                </td>
                                <td>
                                    <input type="text" name="prod_dcantidad[]" class="span12 vpositive" value="<?php echo $_POST['prod_dcantidad'][$k]; ?>" id="prod_dcantidad">
                                    <input type="hidden" name="prod_dcajas[]" value="<?php echo $_POST['prod_dcajas'][$k] ?>" id="prod_dcajas" class="span12 vpositive">
                                    <input type="hidden" name="prod_dkilos[]" value="<?php echo $_POST['prod_dkilos'][$k] ?>" id="prod_dkilos" class="span12 vpositive">
                                </td>
                                <td>
                                  <input type="text" name="prod_dpreciou[]" class="span12 vpositive" value="<?php echo $_POST['prod_dpreciou'][$k]; ?>" id="prod_dpreciou">
                                </td>
                                <td>
                                    <select name="diva" id="diva" class="span12">
                                      <option value="0" <?php echo $_POST['prod_diva_porcent'][$k] == 0 ? 'selected' : ''; ?>>0%</option>
                                      <option value="8" <?php echo $_POST['prod_diva_porcent'][$k] == 8 ? 'selected' : ''; ?>>8%</option>
                                      <option value="16" <?php echo $_POST['prod_diva_porcent'][$k] == 16 ? 'selected' : ''; ?>>16%</option>
                                      <option value="exento" <?php echo $_POST['prod_diva_porcent'][$k] == 'exento' ? 'selected' : ''; ?>>Exento</option>
                                    </select>

                                    <!-- <input type="hidden" name="prod_diva_total[]" class="span12" value="<?php //echo $_POST['prod_diva_total'][$k]; ?>" id="prod_diva_total"> -->
                                    <input type="hidden" name="prod_diva_porcent[]" class="span12" value="<?php echo $_POST['prod_diva_porcent'][$k]; ?>" id="prod_diva_porcent">
                                </td>
                                <td style="width: 80px;">
                                  <input type="text" name="prod_diva_total[]" class="span12" value="<?php echo $_POST['prod_diva_total'][$k]; ?>" id="prod_diva_total" readonly>
                                </td>
                                <td>
                                  <select name="dreten_iva" id="dreten_iva" class="span12 prod">
                                    <option value="0" <?php echo $_POST['prod_dreten_iva_porcent'][$k] == 0 ? 'selected' : ''; ?>>No retener</option>
                                    <option value="0.04" <?php echo $_POST['prod_dreten_iva_porcent'][$k] == 0.04 ? 'selected' : ''; ?>>4%</option>
                                    <option value="0.10667" <?php echo $_POST['prod_dreten_iva_porcent'][$k] == 0.10667 ? 'selected' : ''; ?>>2 Terceras</option>
                                    <option value="0.16" <?php echo $_POST['prod_dreten_iva_porcent'][$k] == 0.16 ? 'selected' : ''; ?>>100 %</option>
                                  </select>

                                  <input type="hidden" name="prod_dreten_iva_total[]" value="<?php echo $_POST['prod_dreten_iva_total'][$k] ?>" id="prod_dreten_iva_total" class="span12">
                                  <input type="hidden" name="prod_dreten_iva_porcent[]" value="<?php echo $_POST['prod_dreten_iva_porcent'][$k] ?>" id="prod_dreten_iva_porcent" class="span12">
                                </td>
                                 <td>
                                  <input type="text" name="prod_importe[]" class="span12 vpositive" value="<?php echo $_POST['prod_importe'][$k]?>" id="prod_importe">
                                </td>
                                <td>
                                  <input type="checkbox" class="is-cert-check" <?php echo ($_POST['isCert'][$k] == '1' ? 'checked' : '') ?>>
                                  <input type="hidden" name="isCert[]" value="<?php echo $_POST['isCert'][$k] ?>" class="certificado">
                                </td>
                                <td>
                                  <div class="btn-group">
                                    <button type="button" class="btn impuestosEx">
                                      <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu impuestosEx">
                                      <li class="clearfix">
                                        <label class="pull-left">% IEPS:</label> <input type="number" name="dieps[]" value="<?php echo $_POST['dieps'][$k] ?>" id="dieps" max="100" min="0" class="span9 pull-right vpositive">
                                        <input type="hidden" name="dieps_total[]" value="<?php echo $_POST['dieps_total'][$k] ?>" id="dieps_total" class="span12">
                                      </li>
                                      <li class="clearfix">
                                        <label class="pull-left">% Ret ISR:</label> <input type="number" name="disr[]" value="<?php echo $_POST['disr'][$k] ?>" id="disr" max="100" min="0" class="span9 pull-right vpositive">
                                        <input type="hidden" name="disr_total[]" value="<?php echo $_POST['disr_total'][$k] ?>" id="disr_total" class="span12">
                                      </li>
                                    </ul>
                                  </div>
                                  <button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                        <?php }}} ?>
                  <tr data-pallets="">
                    <td style="width:31px;">
                      <div class="btn-group">
                        <button type="button" class="btn ventasmore">
                          <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu ventasmore">
                          <li class="clearfix">
                            <label class="pull-left">Calidad:</label> <input type="text" name="prod_dcalidad[]" value="" id="prod_dcalidad" class="span9 pull-right">
                            <input type="hidden" name="prod_did_calidad[]" value="" id="prod_did_calidad" class="span12">
                          </li>
                          <li class="divider"></li>
                          <li class="clearfix">
                            <label class="pull-left">Tamaño:</label> <input type="text" name="prod_dtamanio[]" value="" id="prod_dtamanio" class="span9 pull-right">
                            <input type="hidden" name="prod_did_tamanio[]" value="" id="prod_did_tamanio" class="span12">
                          </li>
                          <li class="divider"></li>
                          <li class="clearfix">
                            <label class="pull-left">TamañoProd</label> <input type="text" name="prod_dtamanio_prod[]" value="" id="prod_dtamanio_prod" class="span9 pull-right">
                            <input type="hidden" name="prod_did_tamanio_prod[]" value="" id="prod_did_tamanio_prod" class="span12">
                          </li>
                          <li class="divider"></li>
                          <li class="clearfix">
                            <label class="pull-left">Descripción:</label> <input type="text" name="prod_ddescripcion2[]" value="" id="prod_ddescripcion2" class="span9 pull-right">
                          </li>
                        </ul>
                      </div>
                    </td>
                    <td>
                      <input type="text" name="prod_ddescripcion[]" value="<?php echo (isset($_GET['id_nrc'])?'Nota de credito':''); ?>" id="prod_ddescripcion" class="span12">
                      <input type="hidden" name="prod_did_prod[]" value="" id="prod_did_prod" class="span12">
                      <input type="hidden" name="pallets_id[]" value="" id="pallets_id" class="span12">
                      <input type="hidden" name="id_unidad_rendimiento[]" value="" id="id_unidad_rendimiento" class="span12">
                      <input type="hidden" name="prod_ieps_subtotal[]" value="f" id="prod_ieps_subtotal" class="span12">
                      <!-- <input type="hidden" name="id_size_rendimiento[]" value="" id="id_size_rendimiento" class="span12"> -->
                    </td>
                    <td>
                      <!-- <input type="text" name="prod_dmedida[]" value="" id="prod_dmedida" class="span12"> -->
                      <select name="prod_dmedida[]" id="prod_dmedida" class="span12">
                        <?php foreach ($unidades as $key => $u) {
                            if ($key === 0) $uni = $u->id_unidad;
                          ?>
                          <option value="<?php echo $u->nombre ?>" data-id="<?php echo $u->id_unidad ?>"><?php echo $u->nombre ?></option>
                        <?php } ?>
                      </select>
                      <input type="hidden" name="prod_dmedida_id[]" value="<?php echo $uni ?>" id="prod_dmedida_id" class="span12 vpositive">

                      <input type="text" name="pclave_unidad[]" class="span12" id="pclave_unidad" value="" placeholder="Clave de Unidad">
                      <input type="hidden" name="pclave_unidad_cod[]" class="span9" id="pclave_unidad_cod" value="">
                    </td>
                    <td>
                        <input type="text" name="prod_dcantidad[]" value="0" id="prod_dcantidad" class="span12 vpositive">
                        <input type="hidden" name="prod_dcajas[]" value="0" id="prod_dcajas" class="span12 vpositive">
                        <input type="hidden" name="prod_dkilos[]" value="0" id="prod_dkilos" class="span12 vpositive">
                    </td>
                    <td>
                      <input type="text" name="prod_dpreciou[]" value="0" id="prod_dpreciou" class="span12 vpositive">
                    </td>
                    <td>
                        <select name="diva" id="diva" class="span12">
                          <option value="0">0%</option>
                          <option value="8">8%</option>
                          <option value="16">16%</option>
                          <option value="exento">Exento</option>
                        </select>

                        <!-- <input type="hidden" name="prod_diva_total[]" value="0" id="prod_diva_total" class="span12"> -->
                        <input type="hidden" name="prod_diva_porcent[]" value="0" id="prod_diva_porcent" class="span12">
                    </td>
                    <td style="width: 80px;">
                      <input type="text" name="prod_diva_total[]" class="span12" value="0" id="prod_diva_total" readonly>
                    </td>
                    <td>
                      <select name="dreten_iva" id="dreten_iva" class="span12 prod">
                        <option value="0">No retener</option>
                        <option value="0.04">4%</option>
                        <option value="0.10667">2 Terceras</option>
                        <option value="0.16">100 %</option>
                      </select>

                      <input type="hidden" name="prod_dreten_iva_total[]" value="0" id="prod_dreten_iva_total" class="span12">
                      <input type="hidden" name="prod_dreten_iva_porcent[]" value="0" id="prod_dreten_iva_porcent" class="span12">
                    </td>
                    <td>
                      <input type="text" name="prod_importe[]" value="0" id="prod_importe" class="span12 vpositive">
                    </td>
                    <td><input type="checkbox" class="is-cert-check"><input type="hidden" name="isCert[]" value="0" class="certificado"></td>
                    <td>
                      <div class="btn-group">
                        <button type="button" class="btn impuestosEx">
                          <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu impuestosEx">
                          <li class="clearfix">
                            <label class="pull-left">% IEPS:</label> <input type="number" name="dieps[]" value="0" id="dieps" max="100" min="0" class="span9 pull-right vpositive">
                            <input type="hidden" name="dieps_total[]" value="0" id="dieps_total" class="span12">
                          </li>
                          <li class="clearfix">
                            <label class="pull-left">% Ret ISR:</label> <input type="number" name="disr[]" value="0" id="disr" max="100" min="0" class="span9 pull-right vpositive">
                            <input type="hidden" name="disr_total[]" value="0" id="disr_total" class="span12">
                          </li>
                        </ul>
                      </div>
                      <button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="row-fluid">
            <div class="span12">
              <strong>Gastos</strong>
              <table class="table table-striped table-bordered table-hover table-condensed" id="table_prod2">
                <thead>
                  <tr>
                    <th></th>
                    <th>Clasificación Descripción</th>
                    <th>Medida</th>
                    <th>Cant.</th>
                    <th>P Unitario</th>
                    <th>IVA%</th>
                    <th>IVA</th>
                    <th>Retención</th>
                    <th>Importe</th>
                    <th>Cert.</th>
                    <th>Accion</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
          </div>

          <div id="pallets-selected">
            <?php
              if (isset($_POST['palletsIds'])) {
                foreach ($_POST['palletsIds'] as $palletId) { ?>
                <input type="hidden" value="<?php echo $palletId ?>" name="palletsIds[]" class="pallet-selected" id="pallet<?php echo $palletId ?>">
            <?php }} else  if (isset($borrador) && count($borrador['pallets']) > 0) {
                foreach ($borrador['pallets'] as $pallet) { ?>
                  <input type="hidden" value="<?php echo $pallet->id_pallet ?>" name="palletsIds[]" class="pallet-selected" id="pallet<?php echo $pallet->id_pallet ?>">
            <?php }} ?>
          </div>

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
                    <td rowspan="9">
                        <textarea name="dttotal_letra" rows="10" class="nokey" style="width:98%;max-width:98%;" id="total_letra"><?php echo set_value('dttotal_letra', isset($borrador) ? $borrador['info']->total_letra : '');?></textarea>
                    </td>
                  </tr>
                  <tr>
                    <td><em>Subtotal</em></td>
                    <td id="importe-format"><?php echo MyString::formatoNumero(set_value('total_importe', isset($borrador) ? $borrador['info']->subtotal : 0))?></td>
                    <input type="hidden" name="total_importe" id="total_importe" value="<?php echo set_value('total_importe', isset($borrador) ? $borrador['info']->subtotal : 0); ?>">
                  </tr>
                  <tr>
                    <td>Descuento</td>
                    <td id="descuento-format"><?php echo MyString::formatoNumero(set_value('total_descuento', 0))?></td>
                    <input type="hidden" name="total_descuento" id="total_descuento" value="<?php echo set_value('total_descuento', 0); ?>">
                  </tr>
                  <tr>
                    <td>SUBTOTAL</td>
                    <td id="subtotal-format"><?php echo MyString::formatoNumero(set_value('total_subtotal', isset($borrador) ? $borrador['info']->subtotal : 0))?></td>
                    <input type="hidden" name="total_subtotal" id="total_subtotal" value="<?php echo set_value('total_subtotal', isset($borrador) ? $borrador['info']->subtotal : 0); ?>">
                  </tr>
                  <tr>
                    <td>IVA</td>
                    <td id="iva-format"><?php echo MyString::formatoNumero(set_value('total_iva', isset($borrador) ? $borrador['info']->importe_iva : 0))?></td>
                    <input type="hidden" name="total_iva" id="total_iva" value="<?php echo set_value('total_iva', isset($borrador) ? $borrador['info']->importe_iva : 0); ?>">
                  </tr>
                  <tr>
                    <td>IEPS</td>
                    <td id="ieps-format"><?php echo MyString::formatoNumero(set_value('total_ieps', isset($borrador) ? $borrador['info']->ieps : 0))?></td>
                    <input type="hidden" name="total_ieps" id="total_ieps" value="<?php echo set_value('total_ieps', isset($borrador) ? $borrador['info']->ieps : 0); ?>">
                  </tr>
                  <tr>
                    <td>Ret. Isr</td>
                    <td id="isr-format"><?php echo MyString::formatoNumero(set_value('total_isr', isset($borrador) ? $borrador['info']->ieps : 0))?></td>
                    <input type="hidden" name="total_isr" id="total_isr" value="<?php echo set_value('total_isr', isset($borrador) ? $borrador['info']->ieps : 0); ?>">
                  </tr>
                  <tr>
                    <td>Ret. IVA</td>
                    <td id="retiva-format"><?php echo MyString::formatoNumero(set_value('total_retiva', isset($borrador) ? $borrador['info']->retencion_iva : 0))?></td>
                    <input type="hidden" name="total_retiva" id="total_retiva" value="<?php echo set_value('total_retiva', isset($borrador) ? $borrador['info']->retencion_iva : 0); ?>">
                  </tr>
                  <tr style="font-weight:bold;font-size:1.2em;">
                    <td>TOTAL</td>
                    <td id="totfac-format"><?php echo MyString::formatoNumero(set_value('total_totfac', isset($borrador) ? $borrador['info']->total : 0))?></td>
                    <input type="hidden" name="total_totfac" id="total_totfac" value="<?php echo set_value('total_totfac', isset($borrador) ? $borrador['info']->total : 0); ?>">
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Modal Seguro-->
          <div id="modal-seguro" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-header">
              <!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button> -->
              <h3 id="myModalLabel">Informacion Seguro</h3>
              <button type="button" class="btn pull-right" id="btn_seguro_add"><i class="icon-plus"></i></button>
            </div>
            <div class="modal-body">
            <?php
            if (isset($borrador) && isset($borrador['seguro'])){
              foreach ($borrador['seguro'] as $sp => $prodesp) {
                $_POST['pproveedor_seguro'][] = $prodesp->proveedor;
                $_POST['seg_id_proveedor'][]  = $prodesp->id_proveedor;
                $_POST['seg_poliza'][]        = $prodesp->pol_seg;
              }
            }

            if (isset($_POST['seg_id_proveedor']) && count($_POST['seg_id_proveedor']) > 0) {
              foreach ($_POST['seg_id_proveedor'] as $key => $value) {
            ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_seguro" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_seguro[]" value="<?php echo $_POST['pproveedor_seguro'][$key] ?>" id="pproveedor_seguro" class="span12 sikey field-check pproveedor_seguro" placeholder="Proveedor" data-next="seg_poliza">
                    <input type="hidden" name="seg_id_proveedor[]" value="<?php echo $_POST['seg_id_proveedor'][$key] ?>" id="seg_id_proveedor" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="seg_poliza" style="width: auto;">POL/SEG</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="seg_poliza[]" class="span12 sikey field-check" id="seg_poliza" value="<?php echo $_POST['seg_poliza'][$key] ?>" maxlength="30" placeholder="Poliza/Seguro" data-next="pproveedor_seguro">
                  </div>
                </div>
              </div>
            <?php }
            } else { ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_seguro" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_seguro[]" value="<?php echo set_value('pproveedor_seguro[]', isset($borrador) && isset($borrador['seguro']) ? $borrador['seguro']->proveedor : '') ?>" id="pproveedor_seguro" class="span12 sikey field-check pproveedor_seguro" placeholder="Proveedor" data-next="seg_poliza">
                    <input type="hidden" name="seg_id_proveedor[]" value="<?php echo set_value('seg_id_proveedor[]', isset($borrador) && isset($borrador['seguro']) ? $borrador['seguro']->id_proveedor : '') ?>" id="seg_id_proveedor" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="seg_poliza" style="width: auto;">POL/SEG</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="seg_poliza[]" class="span12 sikey field-check" id="seg_poliza" value="<?php echo set_value('seg_poliza[]', isset($borrador) && isset($borrador['seguro']) ? $borrador['seguro']->pol_seg : ''); ?>" maxlength="30" placeholder="Poliza/Seguro" data-next="pproveedor_seguro">
                  </div>
                </div>
              </div>
            <?php } ?>
            </div>
            <div class="modal-footer">
              <button class="btn" data-dismiss="modal" aria-hidden="true" id="btnClose" <?php echo isset($borrador) && isset($borrador['seguro']) ? '' : 'disabled' ?>>Cerrar</button>
            </div>
          </div>

          <!-- Modal Certificados -->
          <div id="modal-certificado51" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-header">
              <!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button> -->
              <h3 id="myModalLabel">Informacion Certificado</h3>
              <button type="button" class="btn pull-right" id="btn_certificado51_add"><i class="icon-plus"></i></button>
            </div>
            <div class="modal-body">
              <?php
              if (isset($borrador) && isset($borrador['certificado51'])){
                foreach ($borrador['certificado51'] as $sp => $prodesp) {
                  $_POST['pproveedor_certificado51'][] = $prodesp->proveedor;
                  $_POST['cert_id_proveedor51'][]      = $prodesp->id_proveedor;
                  $_POST['cert_certificado51'][]       = $prodesp->certificado;
                  $_POST['cert_bultos51'][]            = $prodesp->bultos;
                  $_POST['cert_num_operacion51'][]     = $prodesp->num_operacion;
                  $_POST['cert_no_certificado51'][]    = $prodesp->no_certificado;
                  $_POST['cert_id_orden51'][]          = $prodesp->id_orden;
                }
              }

              if (isset($_POST['cert_id_proveedor51']) && count($_POST['cert_id_proveedor51']) > 0) {
                foreach ($_POST['cert_id_proveedor51'] as $key => $value) {
              ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_certificado51" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_certificado51[]" value="<?php echo $_POST['pproveedor_certificado51'][$key]; ?>" id="pproveedor_certificado51" class="span12 sikey field-check pproveedor_certificado51" placeholder="Proveedor" data-next="cert_certificado51">
                    <input type="hidden" name="cert_id_proveedor51[]" value="<?php echo $_POST['cert_id_proveedor51'][$key]; ?>" id="cert_id_proveedor51" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_certificado51" style="width: auto;">CERTIFICADO</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_certificado51[]" class="span12 sikey field-check" id="cert_certificado51" value="<?php echo $_POST['cert_certificado51'][$key]; ?>" maxlength="30" placeholder="Certificado" data-next="cert_bultos51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_bultos51" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_bultos51[]" class="span12 vpositive sikey field-check" id="cert_bultos51" value="<?php echo $_POST['cert_bultos51'][$key]; ?>" placeholder="Bultos" data-next="cert_num_operacion51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_num_operacion51" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_num_operacion51[]" class="span12 sikey field-check" id="cert_num_operacion51" value="<?php echo $_POST['cert_num_operacion51'][$key] ?>" placeholder="Num Operacion" data-next="pproveedor_certificado51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_no_certificado51" style="width: auto;">Certificado de compra</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_no_certificado51[]" class="span12 sikey cursorp cert_no_certificado51" id="cert_no_certificado51" value="<?php echo $_POST['cert_no_certificado51'][$key] ?>" placeholder="Num Certificado" data-next="pproveedor_certificado51" readonly>
                    <input type="hidden" name="cert_id_orden51[]" class="span12 sikey" id="cert_id_orden51" value="<?php echo $_POST['cert_id_orden51'][$key] ?>">
                  </div>
                </div>
              </div>
            <?php }
            } else { ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_certificado51" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_certificado51[]" value="<?php echo set_value('pproveedor_certificado51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->proveedor : '') ?>" id="pproveedor_certificado51" class="span12 sikey field-check pproveedor_certificado51" placeholder="Proveedor" data-next="cert_certificado51">
                    <input type="hidden" name="cert_id_proveedor51[]" value="<?php echo set_value('cert_id_proveedor51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->id_proveedor : '') ?>" id="cert_id_proveedor51" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_certificado51" style="width: auto;">CERTIFICADO</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_certificado51[]" class="span12 sikey field-check" id="cert_certificado51" value="<?php echo set_value('cert_certificado51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->certificado : ''); ?>" maxlength="30" placeholder="Certificado" data-next="cert_bultos51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_bultos51" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_bultos51[]" class="span12 vpositive sikey field-check" id="cert_bultos51" value="<?php echo set_value('cert_bultos51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->bultos : ''); ?>" placeholder="Bultos" data-next="cert_num_operacion51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_num_operacion51" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_num_operacion51[]" class="span12 sikey field-check" id="cert_num_operacion51" value="<?php echo set_value('cert_num_operacion51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->num_operacion : ''); ?>" placeholder="Num Operacion" data-next="cert_no_certificado51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_no_certificado51" style="width: auto;">Certificado de compra</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_no_certificado51[]" class="span12 sikey cursorp cert_no_certificado51" id="cert_no_certificado51" value="<?php echo set_value('cert_no_certificado51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->no_certificado : ''); ?>" placeholder="Num Certificado" data-next="pproveedor_certificado51" readonly>
                    <input type="hidden" name="cert_id_orden51[]" class="span12 sikey" id="cert_id_orden51" value="<?php echo set_value('cert_id_orden51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->id_orden : ''); ?>">
                  </div>
                </div>
              </div>
            <?php } ?>
            </div>
            <div class="modal-footer">
              <button class="btn" data-dismiss="modal" aria-hidden="true" id="btnClose" <?php echo isset($borrador) && isset($borrador['certificado51']) ? '' : 'disabled' ?>>Cerrar</button>
            </div>
          </div>
          <div id="modal-certificado52" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-header">
              <!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button> -->
              <h3 id="myModalLabel">Informacion Certificado</h3>
              <button type="button" class="btn pull-right" id="btn_certificado52_add"><i class="icon-plus"></i></button>
            </div>
            <div class="modal-body">
            <?php
            if (isset($borrador) && isset($borrador['certificado52'])){
              foreach ($borrador['certificado52'] as $sp => $prodesp) {
                $_POST['pproveedor_certificado52'][] = $prodesp->proveedor;
                $_POST['cert_id_proveedor52'][]      = $prodesp->id_proveedor;
                $_POST['cert_certificado52'][]       = $prodesp->certificado;
                $_POST['cert_bultos52'][]            = $prodesp->bultos;
                $_POST['cert_num_operacion52'][]     = $prodesp->num_operacion;
                $_POST['cert_no_certificado52'][]    = $prodesp->no_certificado;
                $_POST['cert_id_orden52'][]          = $prodesp->id_orden;
              }
            }

            if (isset($_POST['cert_id_proveedor52']) && count($_POST['cert_id_proveedor52']) > 0) {
              foreach ($_POST['cert_id_proveedor52'] as $key => $value) {
            ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_certificado52" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_certificado52[]" value="<?php echo $_POST['pproveedor_certificado52'][$key]; ?>" id="pproveedor_certificado52" class="span12 sikey field-check pproveedor_certificado52" placeholder="Proveedor" data-next="cert_certificado52">
                    <input type="hidden" name="cert_id_proveedor52[]" value="<?php echo $_POST['cert_id_proveedor52'][$key]; ?>" id="cert_id_proveedor52" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_certificado52" style="width: auto;">CERTIFICADO</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_certificado52[]" class="span12 sikey field-check" id="cert_certificado52" value="<?php echo $_POST['cert_certificado52'][$key]; ?>" maxlength="30" placeholder="Certificado" data-next="cert_bultos52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_bultos52" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_bultos52[]" class="span12 vpositive sikey field-check" id="cert_bultos52" value="<?php echo $_POST['cert_bultos52'][$key]; ?>" placeholder="Bultos" data-next="cert_num_operacion52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_num_operacion52" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_num_operacion52[]" class="span12 sikey field-check" id="cert_num_operacion52" value="<?php echo $_POST['cert_num_operacion52'][$key] ?>" placeholder="Num Operacion" data-next="pproveedor_certificado52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_no_certificado52" style="width: auto;">Certificado de compra</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_no_certificado52[]" class="span12 sikey cursorp cert_no_certificado52" id="cert_no_certificado52" value="<?php echo $_POST['cert_no_certificado52'][$key] ?>" placeholder="Num Certificado" data-next="pproveedor_certificado52" readonly>
                    <input type="hidden" name="cert_id_orden52[]" class="span12 sikey" id="cert_id_orden52" value="<?php echo $_POST['cert_id_orden52'][$key] ?>">
                  </div>
                </div>
              </div>
            <?php }
            } else { ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_certificado52" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_certificado52[]" value="<?php echo set_value('pproveedor_certificado52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->proveedor : '') ?>" id="pproveedor_certificado52" class="span12 sikey field-check pproveedor_certificado52" placeholder="Proveedor" data-next="cert_certificado52">
                    <input type="hidden" name="cert_id_proveedor52[]" value="<?php echo set_value('cert_id_proveedor52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->id_proveedor : '') ?>" id="cert_id_proveedor52" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_certificado52" style="width: auto;">CERTIFICADO</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_certificado52[]" class="span12 sikey field-check" id="cert_certificado52" value="<?php echo set_value('cert_certificado52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->certificado : ''); ?>" maxlength="30" placeholder="Certificado" data-next="cert_bultos52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_bultos52" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_bultos52[]" class="span12 vpositive sikey field-check" id="cert_bultos52" value="<?php echo set_value('cert_bultos52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->bultos : ''); ?>" placeholder="Bultos" data-next="cert_num_operacion52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_num_operacion52" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_num_operacion52[]" class="span12 sikey field-check" id="cert_num_operacion52" value="<?php echo set_value('cert_num_operacion52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->num_operacion : ''); ?>" placeholder="Num Operacion" data-next="pproveedor_certificado52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_no_certificado52" style="width: auto;">Certificado de compra</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_no_certificado52[]" class="span12 sikey cursorp cert_no_certificado52" id="cert_no_certificado52" value="<?php echo set_value('cert_no_certificado52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->no_certificado : ''); ?>" placeholder="Num Certificado" data-next="pproveedor_certificado52" readonly>
                    <input type="hidden" name="cert_id_orden52[]" class="span12 sikey" id="cert_id_orden52" value="<?php echo set_value('cert_id_orden52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->id_orden : ''); ?>">
                  </div>
                </div>
              </div>
            <?php } ?>
            </div>
            <div class="modal-footer">
              <button class="btn" data-dismiss="modal" aria-hidden="true" id="btnClose" <?php echo isset($borrador) && isset($borrador['certificado52']) ? '' : 'disabled' ?>>Cerrar</button>
            </div>
          </div>

          <!-- Modal Supervisor carga -->
          <div id="modal-supcarga" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-header">
              <!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button> -->
              <h3 id="myModalLabel">Informacion Supervisor de carga</h3>
              <button type="button" class="btn pull-right" id="btn_supcarga_add"><i class="icon-plus"></i></button>
            </div>
            <div class="modal-body">
            <?php
            if (isset($borrador) && isset($borrador['supcarga'])){
              foreach ($borrador['supcarga'] as $sp => $prodesp) {
                $_POST['pproveedor_supcarga'][]    = $prodesp->proveedor;
                $_POST['supcarga_id_proveedor'][]  = $prodesp->id_proveedor;
                $_POST['supcarga_numero'][]        = $prodesp->certificado;
                $_POST['supcarga_bultos'][]        = $prodesp->bultos;
                $_POST['supcarga_num_operacion'][] = $prodesp->num_operacion;
              }
            }

            if (isset($_POST['supcarga_id_proveedor']) && count($_POST['supcarga_id_proveedor']) > 0) {
              foreach ($_POST['supcarga_id_proveedor'] as $key => $value) {
            ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_supcarga" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_supcarga[]" value="<?php echo $_POST['pproveedor_supcarga'][$key] ?>" id="pproveedor_supcarga" class="span12 sikey field-check pproveedor_supcarga" placeholder="Proveedor" data-next="supcarga_numero">
                    <input type="hidden" name="supcarga_id_proveedor[]" value="<?php echo $_POST['supcarga_id_proveedor'][$key] ?>" id="supcarga_id_proveedor" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_numero" style="width: auto;">Numero</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_numero[]" class="span12 sikey field-check" id="supcarga_numero" value="<?php echo $_POST['supcarga_numero'][$key] ?>" maxlength="30" placeholder="Numero" data-next="supcarga_bultos">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_bultos" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_bultos[]" class="span12 vpositive sikey field-check" id="supcarga_bultos" value="<?php echo $_POST['supcarga_bultos'][$key] ?>" placeholder="Bultos" data-next="supcarga_num_operacion">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_num_operacion" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_num_operacion[]" class="span12 sikey field-check" id="supcarga_num_operacion" value="<?php echo $_POST['supcarga_num_operacion'][$key] ?>" placeholder="Num Operacion" data-next="pproveedor_supcarga">
                  </div>
                </div>
              </div>
            <?php }
            } else { ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_supcarga" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_supcarga[]" value="<?php echo set_value('pproveedor_supcarga[]', isset($borrador) && isset($borrador['supcarga']) ? $borrador['supcarga']->proveedor : '') ?>" id="pproveedor_supcarga" class="span12 sikey field-check pproveedor_supcarga" placeholder="Proveedor" data-next="supcarga_numero">
                    <input type="hidden" name="supcarga_id_proveedor[]" value="<?php echo set_value('supcarga_id_proveedor[]', isset($borrador) && isset($borrador['supcarga']) ? $borrador['supcarga']->id_proveedor : '') ?>" id="supcarga_id_proveedor" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_numero" style="width: auto;">Numero</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_numero[]" class="span12 sikey field-check" id="supcarga_numero" value="<?php echo set_value('supcarga_numero[]', isset($borrador) && isset($borrador['supcarga']) ? $borrador['supcarga']->certificado : ''); ?>" maxlength="30" placeholder="Numero" data-next="supcarga_bultos">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_bultos" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_bultos[]" class="span12 vpositive sikey field-check" id="supcarga_bultos" value="<?php echo set_value('supcarga_bultos[]', isset($borrador) && isset($borrador['supcarga']) ? $borrador['supcarga']->bultos : ''); ?>" placeholder="Bultos" data-next="supcarga_num_operacion">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_num_operacion" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_num_operacion[]" class="span12 sikey field-check" id="supcarga_num_operacion" value="<?php echo set_value('supcarga_num_operacion[]', isset($borrador) && isset($borrador['supcarga']) ? $borrador['supcarga']->num_operacion : ''); ?>" placeholder="Num Operacion" data-next="pproveedor_supcarga">
                  </div>
                </div>
              </div>
            <?php } ?>
            </div>
            <div class="modal-footer">
              <button class="btn" data-dismiss="modal" aria-hidden="true" id="btnClose" <?php echo isset($borrador) && isset($borrador['supcarga']) ? '' : 'disabled' ?>>Cerrar</button>
            </div>
          </div>

        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->

  <!-- Modal productos a marcar -->
  <div id="modal-produc-marcar" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabelProd" aria-hidden="true">
    <div class="modal-header">
      <h3 id="myModalLabelProd">Que productos tiene la factura?</h3>
    </div>
    <div class="modal-body center">
      <div class="row-fluid">
        <div class="span6">
          <label class="control-label" for="mprosel_seguro">Seguro <input type="checkbox" id="mprosel_seguro" class="mpromarcsel" value="49"></label>
        </div>
        <div class="span6">
          <label class="control-label" for="mprosel_flete">Flete <input type="checkbox" id="mprosel_flete" class="mpromarcsel" value="50"></label>
        </div>
      </div>
      <div class="row-fluid">
        <div class="span6">
          <label class="control-label" for="mprosel_cerfit">Certificado fitosanitario <input type="checkbox" id="mprosel_cerfit" class="mpromarcsel" value="51"></label>
        </div>
        <div class="span6">
          <label class="control-label" for="mprosel_cerorig">Certificado origen <input type="checkbox" id="mprosel_cerorig" class="mpromarcsel" value="52"></label>
        </div>
      </div>
      <div class="row-fluid">
        <div class="span6">
          <label class="control-label" for="mprosel_supcarga">Supervisor de carga <input type="checkbox" id="mprosel_supcarga" class="mpromarcsel" value="53"></label>
        </div>
        <div class="span6">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
    </div>
  </div>

  <!-- Modal -->
  <div id="modal-pallets" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Pallets del Cliente</h3>
    </div>
    <div class="modal-body">
      <div class="row-fluid">
        <table class="table table-hover table-condensed" id="table-pallets-cliente">
          <thead>
            <tr>
              <th></th>
              <th># Folio</th>
              <th>Cajas</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            <!-- <tr>
              <th><input type="checkbox" value="" class="" id=""><input type="hidden" value=""></th>
              <th>9</th>
              <th>100</th>
              <th>2013-10-22</th>
            </tr> -->
          </tbody>
        </table>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="BtnAddClientePallets">Agregar Pallets</button>
    </div>
  </div><!--/modal pallets -->

  <!-- Modal No Certificados compras -->
  <div id="modal-no-certificados" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 700px;left: 45%;">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">No Certificados Compras</h3>
    </div>
    <div class="modal-body" style="max-height: 370px;">
      <table id="lista_certificados_modal" class="table table-striped table-bordered table-hover table-condensed">
        <caption></caption>
        <thead>
          <tr>
            <th>Folio</th>
            <th>Empresa</th>
            <th>Certificado</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
    </div>
  </div>

</div>

<!-- Bloque de alertas -->
<script type="text/javascript" charset="UTF-8">
<?php if (isset($_GET['imprimir_tk']{0})) {
?>
var win = window.open(base_url+'panel/ventas/imprimir_tk/?id=<?php echo $_GET['imprimir_tk']; ?>', '_blank');
if (win)
  win.focus();
else
  noty({"text":"Activa las ventanas emergentes (pop-ups) para este sitio", "layout":"topRight", "type":"error"});
<?php
} ?>
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
  $(document).ready(function(){
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
<?php }
}?>
</script>
<!-- Bloque de alertas -->