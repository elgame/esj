<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/facturacion/'); ?>">Facturacion</a> <span class="divider">/</span>
      </li>
      <li>Agregar</li>
    </ul>
  </div>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Agregar Factura</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/facturacion/agregar?'.$getId); ?>" method="POST" id="form">
          <ul class="nav nav-tabs" id="myTab">
            <li class="active"><a href="#tabFactura">Factura</a></li>
            <li><a href="#tabComercioExterior">Comercio Exterior</a></li>
            <li><a href="#tabCartaPorte">Carta Porte</a></li>
          </ul>

          <div id="myTabContent" class="tab-content">

            <div class="tab-pane active" id="tabFactura">
              <?php
                if($this->usuarios_model->tienePrivilegioDe('', 'facturacion/prod_descripciones/')){ ?>
                  <input type="hidden" value="si" name="privAddDescripciones" id="privAddDescripciones">
              <?php } ?>

              <div class="row-fluid">
                <div class="span6">
                  <input type="hidden" name="id_nr" id="id_nr" value="<?php echo set_value('id_nr', (isset($id_nr)? $id_nr: '')); ?>">

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
                      <input type="number" name="dfolio" class="span9 nokey" id="dfolio" value="<?php echo isset($_POST['dfolio']) ? $_POST['dfolio'] : (isset($borrador)? $borrador['info']->folio: ''); ?>" size="15" readonly>
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

                  <div class="control-group">
                    <label class="control-label" for="es_carta_porte">Carta Porte</label>
                    <div class="controls">
                      <input type="checkbox" name="es_carta_porte" id="es-carta-porte" value="1" <?php echo set_checkbox('es_carta_porte', '1', (isset($borrador) && isset($borrador['carta_porte'])) ? true : false); ?>>
                    </div>
                  </div>

                  <?php
                    $displayCPorte = 'display: none;';
                    $displayPallets = 'display: ;';
                    if (isset($_POST['es_carta_porte']) || (isset($borrador) && isset($borrador['carta_porte']))) {
                      $displayCPorte = 'display:;';
                      $displayPallets = 'display: none;';
                    }
                  ?>

                  <div id="campos-pallets" style="<?php echo $displayPallets ?>">
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
                      <label class="control-label">Sin Costo</label>
                      <div class="controls">
                        <div class="input-append">
                          <input type="checkbox" name="dsincosto" id="dsincosto" class="nokey" <?php echo isset($borrador) ? ($borrador['info']->sin_costo == 't' ? 'checked' : '' ) : (isset($_POST['dsincosto']) ? 'checked' : '') ?>>
                        </div>
                      </div>
                    </div>
                    <div class="control-group">
                      <label class="control-label">Ventas de Remisión</label>
                      <div class="controls">
                        <div>
                          <button type="button" class="btn btn-info" id="show-remisiones">Buscar</button>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div id="capos-carta-porte" style="<?php echo $displayCPorte ?>">
                    <div class="control-group" style="margin-top: 145px;">
                      <label class="control-label">Remitente</label>
                      <div class="controls">
                        <div class="input-append">
                          <a href="#modal-remitente" role="button" class="btn btn-info" data-toggle="modal">Informacion Remitente</a>
                        </div>
                      </div>
                    </div>
                    <div class="control-group">
                      <label class="control-label">Destinatario</label>
                      <div class="controls">
                        <div class="input-append">
                          <a href="#modal-destinatario" role="button" class="btn btn-info" data-toggle="modal">Informacion Destinatario</a>
                        </div>
                      </div>
                    </div>
                  </div>
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
                        <!-- <option value="MXN" <?php echo set_select('moneda', 'MXN', false, $moneda_borrado); ?>>Peso Mexicano (MXN)</option> -->
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
                        foreach ($formaPago as $key => $frp) {
                        ?>
                          <option value="<?php echo $frp['key'] ?>" <?php echo set_select('dforma_pago', $frp['key'], $formap == $frp['key'] ? true : false); ?>><?php echo $frp['key'].' - '.$frp['value'] ?></option>
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
                          $metodo = isset($borrador) ? $borrador['info']->cfdi_ext->uso_cfdi : '';
                         ?>
                        <?php foreach ($usoCfdi as $key => $usoCfd) { ?>
                          <option value="<?php echo $usoCfd['key'] ?>" <?php echo set_select('duso_cfdi', $usoCfd['key'], $metodo === $usoCfd['key'] ? true : false); ?>><?php echo $usoCfd['key'].' - '.$usoCfd['value'] ?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                  <?php }?>

                  <div style="text-align: center;">
                    <button type="button" id="btnCfdiRelPrev" class="btn">Sustitución de CFDI</button>
                    <input type="hidden" name="cfdiRelPrev" id="cfdiRelPrev" value="<?php echo set_value('cfdiRelPrev'); ?>">
                    <br>
                    <span id="cfdiRelPrevText"><?php echo set_value('cfdiRelPrev'); ?></span>
                    <br>
                    <select name="cfdiRelPrevTipo" id="cfdiRelPrevTipo" style="display: none;width: 55%;">
                      <?php
                      $cfdirel = !empty($_POST['cfdiRelPrevTipo']) ? $_POST['cfdiRelPrevTipo'] : '04';
                      foreach ($tipoRelacion as $key => $tipoRel) { ?>
                        <option value="<?php echo $tipoRel['key'] ?>" <?php echo set_select('cfdiRelPrevTipo', $tipoRel['key'], ($cfdirel === $tipoRel['key'] ? true : false)); ?>><?php echo $tipoRel['key'].' - '.$tipoRel['value'] ?></option>
                      <?php } ?>
                    </select>
                  </div>

                  <div class="control-group">
                    <div class="controls">
                      <div class="well span9">
                          <button type="submit" name="borrador" class="btn btn-success btn-large btn-block" style="width:100%;" id="">Guardar</button><br><br>
                          <button type="submit" name="timbrar" class="btn btn-success btn-large btn-block" style="width:100%;" id="btn-timbrar">Timbrar</button>
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
                        <th class="cporte" style="<?php echo $displayCPorte; ?>">Clase</th>
                        <th class="cporte" style="<?php echo $displayCPorte; ?>">Peso</th>
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
                            if (isset($borrador) && ! isset($_POST['prod_did_prod']))
                            {
                              foreach ($borrador['productos'] as $key => $p) {
                                $_POST['no_identificacion'][$key]       = $p->no_identificacion;
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
                                $_POST['prod_diva_porcent'][$key]       = $p->porcentaje_iva_real? $p->porcentaje_iva_real: 0; //$p->porcentaje_iva;
                                $_POST['prod_diva_total'][$key]         = $p->iva;
                                $_POST['prod_dreten_iva_porcent'][$key] = $p->porcentaje_retencion;
                                $_POST['prod_dreten_iva_total'][$key]   = $p->retencion_iva;
                                $_POST['pallets_id'][$key]              = $p->ids_pallets;
                                $_POST['remisiones_id'][$key]           = $p->ids_remisiones;
                                $_POST['prod_dkilos'][$key]             = $p->kilos;
                                $_POST['prod_dcajas'][$key]             = $p->cajas;
                                $_POST['id_unidad_rendimiento'][$key]   = $p->id_unidad_rendimiento;
                                $_POST['id_size_rendimiento'][$key]     = $p->id_size_rendimiento;
                                $_POST['dieps_total'][$key]             = $p->ieps;
                                $_POST['dieps'][$key]                   = $p->porcentaje_ieps;
                                $_POST['disr_total'][$key]              = $p->isr;
                                $_POST['disr'][$key]                    = $p->porcentaje_isr;

                                $_POST['prod_dclase'][$key]             = $p->clase;
                                $_POST['prod_dpeso'][$key]              = $p->peso;
                                $_POST['isCert'][$key]                  = $p->certificado === 't' ? '1' : '0';

                                $cfdi_extp = json_decode($p->cfdi_ext);
                                $_POST['pclave_unidad'][$key]     = '';
                                $_POST['pclave_unidad_cod'][$key] = '';
                                if (isset($cfdi_extp->clave_unidad)) {
                                  $_POST['pclave_unidad'][$key]     = $cfdi_extp->clave_unidad->value;
                                  $_POST['pclave_unidad_cod'][$key] = $cfdi_extp->clave_unidad->key;
                                }
                              }
                            } ?>

                            <?php if (isset($_POST['prod_did_prod'])) {
                              foreach ($_POST['prod_did_prod'] as $k => $v) {
                                if ($_POST['prod_importe'][$k] >= 0) {
                                ?>
                                  <tr data-pallets="<?php echo $_POST['pallets_id'][$k] ?>" data-remisiones="<?php echo $_POST['remisiones_id'][$k] ?>">
                                    <td style="width:31px;">
                                      <div class="btn-group">
                                        <button type="button" class="btn ventasmore">
                                          <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu ventasmore">
                                          <li class="clearfix">
                                            <label class="pull-left"># ident:</label> <input type="text" name="no_identificacion[]" value="<?php echo $_POST['no_identificacion'][$k]?>" id="no_identificacion" class="span9 pull-right">
                                          </li>
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
                                      <input type="hidden" name="remisiones_id[]" value="<?php echo $_POST['remisiones_id'][$k] ?>" id="remisiones_id" class="span12">
                                      <input type="hidden" name="id_unidad_rendimiento[]" value="<?php echo $_POST['id_unidad_rendimiento'][$k] ?>" id="id_unidad_rendimiento" class="span12">
                                      <input type="hidden" name="id_size_rendimiento[]" value="<?php echo $_POST['id_size_rendimiento'][$k] ?>" id="id_size_rendimiento" class="span12">
                                    </td>
                                    <td class="cporte" style="<?php echo $displayCPorte; ?>">
                                      <input type="text" name="prod_dclase[]" value="<?php echo $_POST['prod_dclase'][$k] ?>" id="prod_dclase" class="span12" style="width: 50px;">
                                    </td>
                                    <td class="cporte" style="<?php echo $displayCPorte; ?>">
                                      <input type="text" name="prod_dpeso[]" value="<?php echo $_POST['prod_dpeso'][$k] ?>" id="prod_dpeso" class="span12 vpositive" style="width: 80px;">
                                    </td>
                                    <td>
                                      <select name="prod_dmedida[]" id="prod_dmedida" class="span12">
                                        <?php foreach ($unidades as $key => $u) {
                                          if ($_POST['prod_dmedida'][$k] == $u->nombre) $uid = $u->id_unidad; ?>
                                          <option value="<?php echo $u->nombre ?>" <?php echo $_POST['prod_dmedida'][$k] == $u->nombre ? 'selected' : '' ?> data-id="<?php echo $u->id_unidad ?>"><?php echo $u->nombre ?></option>
                                        <?php } ?>
                                      </select>
                                      <input type="hidden" name="prod_dmedida_id[]" value="<?php echo $uid ?>" id="prod_dmedida_id" class="span12 vpositive">

                                      <input type="text" name="pclave_unidad[]" class="span12" id="pclave_unidad" value="<?php echo $_POST['pclave_unidad'][$k] ?>" placeholder="Clave de Unidad">
                                      <input type="hidden" name="pclave_unidad_cod[]" class="span9" id="pclave_unidad_cod" value="<?php echo $_POST['pclave_unidad_cod'][$k] ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="prod_dcantidad[]" class="span12 vpositive" value="<?php echo $_POST['prod_dcantidad'][$k]; ?>" id="prod_dcantidad">
                                        <input type="hidden" name="prod_dcajas[]" value="<?php echo $_POST['prod_dcajas'][$k] ?>" id="prod_dcajas" class="span12 vpositive">
                                        <input type="hidden" name="prod_dkilos[]" value="<?php echo $_POST['prod_dkilos'][$k] ?>" id="prod_dkilos" class="span12 vpositive">
                                    </td>
                                    <td>
                                      <input type="text" name="prod_dpreciou[]" class="span12 vnumeric" value="<?php echo $_POST['prod_dpreciou'][$k]; ?>" id="prod_dpreciou">
                                    </td>
                                    <td>
                                        <select name="diva" id="diva" class="span12">
                                          <option value="0" <?php echo "{$_POST['prod_diva_porcent'][$k]}" == 0 ? 'selected' : ''; ?>>0%</option>
                                          <option value="8" <?php echo "{$_POST['prod_diva_porcent'][$k]}" == 8 ? 'selected' : ''; ?>>8%</option>
                                          <option value="16" <?php echo "{$_POST['prod_diva_porcent'][$k]}" == 16 ? 'selected' : ''; ?>>16%</option>
                                          <option value="exento" <?php echo "{$_POST['prod_diva_porcent'][$k]}" == 'exento' ? 'selected' : ''; ?>>Exento</option>
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
                                        <ul class="dropdown-menu impuestosEx" style="width: 250px;">
                                          <li class="clearfix">
                                            <label class="pull-left">% IEPS:</label> <input type="number" step="any" name="dieps[]" value="<?php echo $_POST['dieps'][$k] ?>" id="dieps" max="100" min="0" class="span9 pull-right vpositive">
                                            <input type="hidden" name="dieps_total[]" value="<?php echo $_POST['dieps_total'][$k] ?>" id="dieps_total" class="span12">
                                          </li>
                                          <li class="clearfix">
                                            <label class="pull-left">% Ret ISR:</label> <input type="number" step="any" name="disr[]" value="<?php echo $_POST['disr'][$k] ?>" id="disr" max="100" min="0" class="span9 pull-right vpositive">
                                            <input type="hidden" name="disr_total[]" value="<?php echo $_POST['disr_total'][$k] ?>" id="disr_total" class="span12">
                                          </li>
                                        </ul>
                                      </div>
                                      <button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>
                                    </td>
                                  </tr>
                            <?php }}} ?>
                            <tr data-pallets="" data-remisiones="">
                              <td style="width:31px;">
                                <div class="btn-group">
                                  <button type="button" class="btn ventasmore">
                                    <span class="caret"></span>
                                  </button>
                                  <ul class="dropdown-menu ventasmore">
                                    <li class="clearfix">
                                      <label class="pull-left"># ident:</label> <input type="text" name="no_identificacion[]" value="" id="no_identificacion" class="span9 pull-right">
                                    </li>
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
                                <input type="text" name="prod_ddescripcion[]" value="" id="prod_ddescripcion" class="span12" data-next="prod_dclase|prod_dmedida">
                                <input type="hidden" name="prod_did_prod[]" value="" id="prod_did_prod" class="span12">
                                <input type="hidden" name="pallets_id[]" value="" id="pallets_id" class="span12">
                                <input type="hidden" name="remisiones_id[]" value="" id="remisiones_id" class="span12">
                                <input type="hidden" name="id_unidad_rendimiento[]" value="" id="id_unidad_rendimiento" class="span12">
                                <input type="hidden" name="id_size_rendimiento[]" value="" id="id_size_rendimiento" class="span12">
                              </td>
                              <td class="cporte" style="<?php echo $displayCPorte ?>">
                                <input type="text" name="prod_dclase[]" value="" id="prod_dclase" class="span12 sikey" style="width: 50px;" data-next="prod_dpeso">
                              </td>
                              <td class="cporte" style="<?php echo $displayCPorte ?>">
                                <input type="text" name="prod_dpeso[]" value="" id="prod_dpeso" class="span12 vpositive sikey" style="width: 80px;" data-next="prod_dmedida">
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
                                <input type="text" name="prod_dpreciou[]" value="0" id="prod_dpreciou" class="span12 vnumeric">
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
                                  <ul class="dropdown-menu impuestosEx" style="width: 250px;">
                                    <li class="clearfix">
                                      <label class="pull-left">% IEPS:</label> <input type="number" step="any" name="dieps[]" value="0" id="dieps" max="100" min="0" class="span9 pull-right vpositive">
                                      <input type="hidden" name="dieps_total[]" value="0" id="dieps_total" class="span12">
                                    </li>
                                    <li class="clearfix">
                                      <label class="pull-left">% Ret ISR:</label> <input type="number" step="any" name="disr[]" value="0" id="disr" max="100" min="0" class="span9 pull-right vpositive">
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

              <div id="remisiones-selected">
                <?php
                  if (isset($_POST['remisionesIds'])) {
                    foreach ($_POST['remisionesIds'] as $remisionId) { ?>
                    <input type="hidden" value="<?php echo $remisionId ?>" name="remisionesIds[]" class="remision-selected" id="remision<?php echo $remisionId ?>">
                <?php }} else  if (isset($borrador) && count($borrador['remisiones']) > 0) {
                    foreach ($borrador['remisiones'] as $remision) { ?>
                      <input type="hidden" value="<?php echo $remision->id_venta ?>" name="remisionesIds[]" class="remision-selected" id="remision<?php echo $remision->id_venta ?>">
                <?php }} else  if (isset($id_nr)) { ?>
                      <input type="hidden" value="<?php echo $id_nr; ?>" name="remisionesIds[]" class="remision-selected" id="remision<?php echo $id_nr; ?>">
                <?php } ?>
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

              <!-- Modal Remitente-->
              <div id="modal-remitente" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modal-remitente" aria-hidden="true">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                  <h3 id="myModalLabel">Informacion Remitente</h3>
                </div>
                <div class="modal-body">
                  <div class="control-group">
                    <label class="control-label" for="remitente_nombre" style="width: auto;" style="width: auto;">Remitente</label>
                    <div class="controls" style="margin-left: 0" style="margin-left: 0">
                      <input type="text" name="remitente_nombre" class="span12" id="remitente_nombre" value="<?php echo set_value('remitente_nombre', (isset($borrador) && isset($borrador['carta_porte'])) ? $borrador['carta_porte']['remitente'][0]->nombre : (isset($empresa_default->nombre_fiscal) ? $empresa_default->nombre_fiscal : '')); ?>" maxlenth="130">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="remitente_rfc" style="width: auto;">RFC</label>
                    <div class="controls" style="margin-left: 0">
                      <input type="text" name="remitente_rfc" class="span12" id="remitente_rfc" value="<?php echo set_value('remitente_rfc', (isset($borrador) && isset($borrador['carta_porte'])) ? $borrador['carta_porte']['remitente'][0]->rfc : (isset($empresa_default->rfc) ? $empresa_default->rfc : '')); ?>" maxlenth="13">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="remitente_domicilio" style="width: auto;">Domicilio</label>
                    <div class="controls" style="margin-left: 0">
                      <input type="text" name="remitente_domicilio" class="span12" id="remitente_domicilio" value="<?php echo set_value('remitente_domicilio', (isset($borrador) && isset($borrador['carta_porte'])) ? $borrador['carta_porte']['remitente'][0]->direccion : (isset($dire) ? $dire : '')); ?>" maxlenth="250">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="remitente_chofer" style="width: auto;">Chofer</label>
                    <div class="controls" style="margin-left: 0">
                      <input type="text" name="remitente_chofer" class="span12" id="remitente_chofer" value="<?php echo set_value('remitente_chofer', (isset($borrador) && isset($borrador['carta_porte'])) ? $borrador['carta_porte']['remitente'][0]->chofer : ''); ?>" maxlenth="50">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="remitente_marca" style="width: auto;">Marca</label>
                    <div class="controls" style="margin-left: 0">
                      <input type="text" name="remitente_marca" class="span12" id="remitente_marca" value="<?php echo set_value('remitente_marca', (isset($borrador) && isset($borrador['carta_porte'])) ? $borrador['carta_porte']['remitente'][0]->marca : ''); ?>" maxlenth="50">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="remitente_modelo" style="width: auto;">Modelo</label>
                    <div class="controls" style="margin-left: 0">
                      <input type="text" name="remitente_modelo" class="span12" id="remitente_modelo" value="<?php echo set_value('remitente_modelo', (isset($borrador) && isset($borrador['carta_porte'])) ? $borrador['carta_porte']['remitente'][0]->modelo : ''); ?>" maxlenth="50">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="remitente_placas" style="width: auto;">Placas</label>
                    <div class="controls" style="margin-left: 0">
                      <input type="text" name="remitente_placas" class="span12" id="remitente_placas" value="<?php echo set_value('remitente_placas', (isset($borrador) && isset($borrador['carta_porte'])) ? $borrador['carta_porte']['remitente'][0]->placas : ''); ?>" maxlenth="30">
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
                </div>
              </div>

              <!-- Modal Destinatario-->
              <div id="modal-destinatario" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-header">
                  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                  <h3 id="myModalLabel">Informacion Destinatario</h3>
                </div>
                <div class="modal-body">
                  <div class="control-group">
                    <label class="control-label" for="destinatario_nombre" style="width: auto;">Remitente</label>
                    <div class="controls" style="margin-left: 0">
                      <input type="text" name="destinatario_nombre" class="span12" id="destinatario_nombre" value="<?php echo set_value('destinatario_nombre', (isset($borrador) && isset($borrador['carta_porte'])) ? $borrador['carta_porte']['destinatario'][0]->nombre : ''); ?>" maxlenth="130">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="destinatario_rfc" style="width: auto;">RFC</label>
                    <div class="controls" style="margin-left: 0">
                      <input type="text" name="destinatario_rfc" class="span12" id="destinatario_rfc" value="<?php echo set_value('destinatario_rfc', (isset($borrador) && isset($borrador['carta_porte'])) ? $borrador['carta_porte']['destinatario'][0]->rfc : ''); ?>" maxlenth="13">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="destinatario_domicilio" style="width: auto;">Domicilio</label>
                    <div class="controls" style="margin-left: 0">
                      <input type="text" name="destinatario_domicilio" class="span12" id="destinatario_domicilio" value="<?php echo set_value('destinatario_domicilio', (isset($borrador) && isset($borrador['carta_porte'])) ? $borrador['carta_porte']['destinatario'][0]->direccion : ''); ?>" maxlenth="250">
                    </div>
                  </div>
                </div>
                <div class="modal-footer">
                  <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
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
                        <input type="text" name="cert_num_operacion51[]" class="span12 sikey field-check" id="cert_num_operacion51" value="<?php echo set_value('cert_num_operacion51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->num_operacion : ''); ?>" placeholder="Num Operacion" data-next="pproveedor_certificado51">
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

              <!-- Modal Orden Flete -->
              <div id="modal-orden-flete" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-header">
                  <!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button> -->
                  <h3 id="myModalLabel">Desea agregar una Orden de Flete?</h3>
                </div>
                <div class="modal-body center">
                  <input type="hidden" name="new_orden_flete" value="0" id="new_orden_flete" class="span12">
                  <button class="btn btn-large btn-success cboot-btn" id="btnOrdenFleteSi">SI</button>
                  <button class="btn btn-large btn-warning" id="btnOrdenFleteNo">NO</button>
                </div>
                <div class="modal-footer">
                  <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
                </div>
              </div>
            </div>

            <div class="tab-pane" id="tabComercioExterior">
              <div class="row-fluid">
                <div class="span12 mquit">
                  <div class="alert alert-block">
                    <h4 class="alert-heading"> COMPLEMENTO PARA COMERCIO EXTERIOR</h4>
                    <span class="help-inline">Este complemento será utilizado por los contribuyentes que exporten mercancías en definitiva con la clave de pedimento “A1”, de conformidad con la regla 2.7.1.22. de la Resolución Miscelánea Fiscal vigente, en relación con las Reglas Generales de Comercio Exterior 3.1.35. y 3.1.36., también vigentes; y servirá para incorporar la información del tipo de operación, datos de identificación fiscal del emisor, receptor o destinatario de la mercancía y la descripción de las mercancías exportadas.</span>
                  </div>
                  <div class="row-fluid">

                    <!-- Datos ComercioExterior -->
                    <div class="span6">
                      <div class="control-group">
                        <label class="control-label" for="cce_motivo_traslado">Motivo traslado <i class="icon-question-sign helpover" data-title=""></i></label>
                        <div class="controls">
                          <?php
                            $com_ex = isset($borrador) && isset($borrador['ce']) ? $borrador['ce'] : null;
                            isset($com_ex) ? $com_ex->extra = json_decode($com_ex->extras) : '';
                            $motivo_traslado = isset($com_ex) ? $com_ex->motivo_traslado : ''; ?>
                          <select name="comercioExterior[motivoTraslado]" class="span12 sikey" id="cce_motivo_traslado" data-next="cce_tipo_operacion">
                            <option value=""></option>
                            <?php foreach ($ceMotTraslado as $key => $value): ?>
                            <option value="<?php echo $value['key'] ?>" <?php echo set_select('comercioExterior[motivoTraslado]', $value['key'], $motivo_traslado === $value['key'] ? true : false); ?>><?php echo $value['key'] ?> - <?php echo $value['value'] ?></option>
                            <?php endforeach ?>
                          </select>
                        </div>
                      </div><!--/control-group -->
                      <div class="control-group">
                        <label class="control-label" for="cce_tipo_operacion">Tipo Operacion <i class="icon-question-sign helpover" data-title="Tipo Operacion: Atributo requerido que indica el tipo de operación de comercio exterior que se realiza, puede ser importación o exportación, A = exportación de servicios. 2 = exportación."></i></label>
                        <div class="controls">
                          <?php
                            $com_ex = isset($borrador) && isset($borrador['ce']) ? $borrador['ce'] : null;
                            $tipo_operacion = isset($com_ex) ? $com_ex->tipo_operacion : ''; ?>
                          <select name="comercioExterior[tipoOperacion]" class="span12 sikey" id="cce_tipo_operacion" data-next="cce_clave_pedimento">
                            <option value=""></option>
                            <option value="2" <?php echo set_select('comercioExterior[tipoOperacion]', '2', $tipo_operacion === '2' ? true : false); ?>>2 - Exportación</option>
                          </select>
                        </div>
                      </div><!--/control-group -->
                      <div class="control-group">
                        <label class="control-label" for="cce_clave_pedimento">Clave de pedimento <i class="icon-question-sign helpover" data-title="Clave de pedimento: Atributo que indica la clave de pedimento que se haya declarado conforme al apéndice 2 del anexo 22 de las reglas generales de comercio exterior."></i></label>
                        <div class="controls">
                          <?php $clave_pedimento = isset($com_ex) ? $com_ex->clave_pedimento : ''; ?>
                          <select name="comercioExterior[clavePedimento]" class="span12 sikey" id="cce_clave_pedimento" data-next="cce_certificado_origen">
                            <option value="" <?php echo set_select('comercioExterior[clavePedimento]', '', $clave_pedimento === '' ? true : false); ?>></option>
                            <option value="A1" <?php echo set_select('comercioExterior[clavePedimento]', 'A1', $clave_pedimento === 'A1' ? true : false); ?>>A1 - IMPORTACION O EXPORTACION DEFINITIVA</option>
                          </select>
                        </div>
                      </div><!--/control-group -->
                      <div class="control-group">
                        <label class="control-label" for="cce_certificado_origen">Certificado de origen <i class="icon-question-sign helpover" data-title="Certificado de origen: Atributo derivado de la excepción de certificados de Origen de los Tratados de Libre Comercio que ha celebrado México con diversos países. 0 = No Funge como certificado de origen 1 = Funge como certificado de origen."></i></label>
                        <div class="controls">
                          <?php $certificado_origen = isset($com_ex) ? $com_ex->certificado_origen : ''; ?>
                          <select name="comercioExterior[certificadoOrigen]" class="span12 sikey" id="cce_certificado_origen" data-next="cce_num_certificado_origen">
                            <option value="" <?php echo set_select('comercioExterior[certificadoOrigen]', '', $certificado_origen === '' ? true : false); ?>></option>
                            <option value="0" <?php echo set_select('comercioExterior[certificadoOrigen]', '0', $certificado_origen === '0' ? true : false); ?>>No Funge como certificado de origen</option>
                            <option value="1" <?php echo set_select('comercioExterior[certificadoOrigen]', '1', $certificado_origen === '1' ? true : false); ?>>Funge como certificado de origen</option>
                          </select>
                        </div>
                      </div><!--/control-group -->
                      <div class="control-group">
                        <label class="control-label" for="cce_num_certificado_origen">Num de certificado de origen <i class="icon-question-sign helpover" data-title="Num de certificado de origen: Atributo opcional para expresar el folio del certificado de origen o el folio fiscal del CFDI con el que se pagó la expedición del certificado de origen"></i></label>
                        <div class="controls">
                          <input type="text" name="comercioExterior[numCertificadoOrigen]" class="span12 sikey" id="cce_num_certificado_origen" value="<?php echo set_value('comercioExterior[numCertificadoOrigen]', isset($com_ex) ? $com_ex->num_certificado_origen : ''); ?>" placeholder="Num de certificado de origen" minlength="6" maxlength="40" data-next="cce_numero_exportador_confiable">
                        </div>
                      </div><!--/control-group -->
                      <div class="control-group">
                        <label class="control-label" for="cce_numero_exportador_confiable">Numero exportador confiable <i class="icon-question-sign helpover" data-title="Numero exportador confiable: Atributo opcional que indica el número de exportador confiable, conforme al artículo 22 del Anexo 1 del Tratado de Libre Comercio con la Asociación Europea y a la Decisión de la Comunidad Europea"></i></label>
                        <div class="controls">
                          <input type="text" name="comercioExterior[numeroExportadorConfiable]" class="span12 sikey" id="cce_numero_exportador_confiable" value="<?php echo set_value('comercioExterior[numeroExportadorConfiable]', isset($com_ex) ? $com_ex->num_certificado_origen : ''); ?>" placeholder="Numero exportador confiable" minlength="1" maxlength="50" data-next="cce_incoterm">
                        </div>
                      </div><!--/control-group -->
                    </div>

                    <!-- Datos ComercioExterior -->
                    <div class="span6">
                      <div class="control-group">
                        <label class="control-label" for="cce_incoterm">Incoterm <i class="icon-question-sign helpover" data-title="Incoterm: Atributo que indica la clave del INCOTERM aplicable a la factura."></i></label>
                        <div class="controls">
                          <?php $incoterm = isset($com_ex) ? $com_ex->incoterm : ''; ?>
                          <select name="comercioExterior[incoterm]" class="span12 sikey" id="cce_incoterm" data-next="cce_subdivision">
                            <option value="" <?php echo set_select('comercioExterior[incoterm]', '', $incoterm === '' ? true : false); ?>></option>
                            <?php foreach ($ceIncoterm as $key => $value): ?>
                            <option value="<?php echo $value['key'] ?>" <?php echo set_select('comercioExterior[incoterm]', $value['key'], $incoterm === $value['key'] ? true : false); ?>><?php echo $value['key'] ?> - <?php echo $value['value'] ?></option>
                            <?php endforeach ?>
                          </select>
                        </div>
                      </div><!--/control-group -->
                      <div class="control-group">
                        <label class="control-label" for="cce_subdivision">Subdivision <i class="icon-question-sign helpover" data-title="Subdivision: Atributo que indica si la factura tiene o no subdivisión. Valores posibles:0 - no tiene subdivisión,1 - si tiene subdivisión."></i></label>
                        <div class="controls">
                          <?php $subdivision = isset($com_ex) ? $com_ex->subdivision : ''; ?>
                          <select name="comercioExterior[subdivision]" class="span12 sikey" id="cce_subdivision" data-next="cce_observaciones">
                            <option value="" <?php echo set_select('comercioExterior[subdivision]', '', $subdivision === '' ? true : false); ?>></option>
                            <option value="0" <?php echo set_select('comercioExterior[subdivision]', '0', $subdivision === '0' ? true : false); ?>>No tiene subdivisión</option>
                            <option value="1" <?php echo set_select('comercioExterior[subdivision]', '1', $subdivision === '1' ? true : false); ?>>Si tiene subdivisión</option>
                          </select>
                        </div>
                      </div><!--/control-group -->
                      <div class="control-group">
                        <label class="control-label" for="cce_observaciones">Observaciones <i class="icon-question-sign helpover" data-title="Observaciones: Atributo opcional en caso de ingresar alguna información adicional, como alguna leyenda que debe incluir el CFDI"></i></label>
                        <div class="controls">
                          <input type="text" name="comercioExterior[observaciones]" class="span12 sikey" id="cce_observaciones" value="<?php echo set_value('comercioExterior[observaciones]', isset($com_ex) ? $com_ex->observaciones : ''); ?>" placeholder="Observaciones" minlength="1" maxlength="300" data-next="cce_tipocambio_USD">
                        </div>
                      </div><!--/control-group -->
                      <div class="control-group">
                        <label class="control-label" for="cce_tipocambio_USD">Tipo Cambio USD <i class="icon-question-sign helpover" data-title="Tipo Cambio USD: Atributo que indica el número de pesos mexicanos que equivalen a un dólar de Estados Unidos, de acuerdo al artículo 20 del Código Fiscal de la Federación"></i></label>
                        <div class="controls">
                          <input type="number" step="any" name="comercioExterior[tipoCambioUSD]" class="span12 vnumeric sikey" id="cce_tipocambio_USD" value="<?php echo set_value('comercioExterior[tipoCambioUSD]', isset($com_ex) ? $com_ex->tipocambio_USD : ''); ?>" placeholder="Tipo Cambio USD" data-next="cce_total_USD">
                        </div>
                      </div><!--/control-group -->
                      <div class="control-group">
                        <label class="control-label" for="cce_total_USD">Total USD <i class="icon-question-sign helpover" data-title="Total USD: Atributo que indica el importe total del comprobante en dólares de Estados Unidos"></i></label>
                        <div class="controls">
                          <input type="number" step="any" name="comercioExterior[totalUSD]" class="span12 vnumeric sikey" id="cce_total_USD" value="<?php echo set_value('comercioExterior[totalUSD]', isset($com_ex) ? $com_ex->total_USD : ''); ?>" placeholder="Total USD" data-next="cce_emisor_curp">
                        </div>
                      </div><!--/control-group -->
                    </div>
                  </div><!--/row-fluid -->

                  <!-- Emisor -->
                  <div class="row-fluid">
                    <div class="box span6">
                      <div class="box-header well">
                        <h2><i class="icon-align-justify"></i><span class="break"></span>Emisor</h2>
                        <div class="box-icon">
                        </div>
                      </div>
                      <div class="box-content" style="padding: 5 5 0 0;">
                        <div class="control-group">
                          <label class="control-label" for="cce_emisor_curp">Curp <i class="icon-question-sign helpover" data-title="Curp: Atributo opcional para expresar la CURP del emisor del CFDI cuando es una persona física"></i></label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[emisor][curp]" class="span12 sikey" id="cce_emisor_curp" value="<?php echo set_value('comercioExterior[emisor][curp]', isset($com_ex->emisor_curp) ? $com_ex->emisor_curp : ''); ?>" placeholder="Curp" data-next="cce_emisor_calle">
                          </div>
                        </div><!--/control-group -->

                        <hr>

                        <div class="control-group">
                          <label class="control-label" for="cce_emisor_calle">Calle:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[emisor][domicilio][calle]" id="cce_emisor_calle" class="span12 sikey" value="<?php echo set_value('comercioExterior[emisor][domicilio][calle]', isset($com_ex->extra->emisor) ? $com_ex->extra->emisor->domicilio->calle : ''); ?>" maxlength="100" data-next="cce_emisor_no_exterior">
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_emisor_no_exterior">No exterior:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[emisor][domicilio][numeroExterior]" id="cce_emisor_no_exterior" class="span12 sikey" value="<?php echo set_value('comercioExterior[emisor][domicilio][numeroExterior]', isset($com_ex->extra->emisor) ? $com_ex->extra->emisor->domicilio->numeroExterior : ''); ?>" maxlength="50" data-next="cce_emisor_no_interior">
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_emisor_no_interior">No interior:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[emisor][domicilio][numeroInterior]" id="cce_emisor_no_interior" class="span12 sikey" value="<?php echo set_value('comercioExterior[emisor][domicilio][numeroInterior]', isset($com_ex->extra->emisor) ? $com_ex->extra->emisor->domicilio->numeroInterior : ''); ?>" maxlength="50" autocomplete="off" data-next="cce_emisor_pais">
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_emisor_pais">País:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[emisor][domicilio][pais]" id="cce_emisor_pais" class="span12 sikey" value="<?php echo set_value('comercioExterior[emisor][domicilio][pais]', isset($com_ex->extra->emisor) ? $com_ex->extra->emisor->domicilio->pais : ''); ?>" maxlength="60" autocomplete="off" data-next="cce_emisor_estado">
                            <span class="cce_emisor_pais help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_emisor_estado">Estado:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[emisor][domicilio][estado]" id="cce_emisor_estado" class="span12 sikey" value="<?php echo set_value('comercioExterior[emisor][domicilio][estado]', isset($com_ex->extra->emisor) ? $com_ex->extra->emisor->domicilio->estado : ''); ?>" maxlength="45" autocomplete="off" data-next="cce_emisor_municipio">
                            <span class="cce_emisor_estado help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_emisor_municipio">Municipio / Delegación:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[emisor][domicilio][municipio]" id="cce_emisor_municipio" class="span12 sikey" value="<?php echo set_value('comercioExterior[emisor][domicilio][municipio]', isset($com_ex->extra->emisor) ? $com_ex->extra->emisor->domicilio->municipio : ''); ?>" maxlength="45" autocomplete="off" data-next="cce_emisor_localidad">
                            <span class="cce_emisor_municipio help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_emisor_localidad">Localidad:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[emisor][domicilio][localidad]" id="cce_emisor_localidad" class="span12 sikey" value="<?php echo set_value('comercioExterior[emisor][domicilio][localidad]', isset($com_ex->extra->emisor) ? $com_ex->extra->emisor->domicilio->localidad : ''); ?>" maxlength="45" autocomplete="off" data-next="cce_emisor_cp">
                            <span class="cce_emisor_localidad help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_emisor_cp">CP:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[emisor][domicilio][codigoPostal]" id="cce_emisor_cp" class="span12 sikey" value="<?php echo set_value('comercioExterior[emisor][domicilio][codigoPostal]', isset($com_ex->extra->emisor) ? $com_ex->extra->emisor->domicilio->codigoPostal : ''); ?>" maxlength="10" autocomplete="off" data-next="cce_emisor_colonia">
                            <span class="cce_emisor_cp help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_emisor_colonia">Colonia:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[emisor][domicilio][colonia]" id="cce_emisor_colonia" class="span12 sikey" value="<?php echo set_value('comercioExterior[emisor][domicilio][colonia]', isset($com_ex->extra->emisor) ? $com_ex->extra->emisor->domicilio->colonia : ''); ?>" maxlength="60" autocomplete="off" data-next="cce_receptor_num_reg_id_trib">
                            <span class="cce_emisor_colonia help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>
                      </div>
                    </div>
                  <!--/Emisor -->

                  <!-- Receptor -->
                    <div class="box span6">
                      <div class="box-header well">
                        <h2><i class="icon-align-justify"></i><span class="break"></span>Receptor</h2>
                        <div class="box-icon">
                        </div>
                      </div>
                      <div class="box-content" style="padding: 5 5 0 0;">
                        <div class="control-group">
                          <label class="control-label" for="cce_receptor_num_reg_id_trib">Num Reg Id Trib <i class="icon-question-sign helpover" data-title="Num Reg Id Trib: Atributo requerido para incorporar el número de identificación o registro fiscal del país de residencia para efectos fiscales del receptor del CFDI"></i></label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[receptor][numRegIdTrib]" class="span12 sikey" id="cce_receptor_num_reg_id_trib" value="<?php echo set_value('comercioExterior[receptor][numRegIdTrib]', isset($com_ex->receptor_numregidtrib) ? $com_ex->receptor_numregidtrib : ''); ?>" placeholder="Num Reg Id Trib" minlength="6" maxlength="40" data-next="cce_receptor_calle">
                          </div>
                        </div><!--/control-group -->

                        <hr>

                        <div class="control-group">
                          <label class="control-label" for="cce_receptor_calle">Calle:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[receptor][domicilio][calle]" id="cce_receptor_calle" class="span12 sikey" value="<?php echo set_value('comercioExterior[receptor][domicilio][calle]', isset($com_ex->extra->receptor) ? $com_ex->extra->receptor->domicilio->calle : ''); ?>" maxlength="100" data-next="cce_receptor_no_exterior">
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_receptor_no_exterior">No exterior:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[receptor][domicilio][numeroExterior]" id="cce_receptor_no_exterior" class="span12 sikey" value="<?php echo set_value('comercioExterior[receptor][domicilio][numeroExterior]', isset($com_ex->extra->receptor) ? $com_ex->extra->receptor->domicilio->numeroExterior : ''); ?>" maxlength="50" data-next="cce_receptor_no_interior">
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_receptor_no_interior">No interior:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[receptor][domicilio][numeroInterior]" id="cce_receptor_no_interior" class="span12 sikey" value="<?php echo set_value('comercioExterior[receptor][domicilio][numeroInterior]', isset($com_ex->extra->receptor) ? $com_ex->extra->receptor->domicilio->numeroInterior : ''); ?>" maxlength="50" data-next="cce_receptor_pais">
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_receptor_pais">País:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[receptor][domicilio][pais]" id="cce_receptor_pais" class="span12 sikey" value="<?php echo set_value('comercioExterior[receptor][domicilio][pais]', isset($com_ex->extra->receptor) ? $com_ex->extra->receptor->domicilio->pais : ''); ?>" maxlength="60" autocomplete="off" data-next="cce_receptor_estado">
                            <span class="cce_receptor_pais help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_receptor_estado">Estado:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[receptor][domicilio][estado]" id="cce_receptor_estado" class="span12 sikey" value="<?php echo set_value('comercioExterior[receptor][domicilio][estado]', isset($com_ex->extra->receptor) ? $com_ex->extra->receptor->domicilio->estado : ''); ?>" maxlength="45" autocomplete="off" data-next="cce_receptor_municipio">
                            <span class="cce_receptor_estado help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_receptor_municipio">Municipio / Delegación:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[receptor][domicilio][municipio]" id="cce_receptor_municipio" class="span12 sikey" value="<?php echo set_value('comercioExterior[receptor][domicilio][municipio]', isset($com_ex->extra->receptor) ? $com_ex->extra->receptor->domicilio->municipio : ''); ?>" maxlength="45" data-next="cce_receptor_localidad">
                            <span class="cce_receptor_municipio help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_receptor_localidad">Localidad:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[receptor][domicilio][localidad]" id="cce_receptor_localidad" class="span12 sikey" value="<?php echo set_value('comercioExterior[receptor][domicilio][localidad]', isset($com_ex->extra->receptor) ? $com_ex->extra->receptor->domicilio->localidad : ''); ?>" maxlength="45" data-next="cce_receptor_cp">
                            <span class="cce_receptor_localidad help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_receptor_cp">CP:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[receptor][domicilio][codigoPostal]" id="cce_receptor_cp" class="span12 sikey" value="<?php echo set_value('comercioExterior[receptor][domicilio][codigoPostal]', isset($com_ex->extra->receptor) ? $com_ex->extra->receptor->domicilio->codigoPostal : ''); ?>" maxlength="10" data-next="cce_receptor_colonia">
                            <span class="cce_receptor_cp help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>

                        <div class="control-group">
                          <label class="control-label" for="cce_receptor_colonia">Colonia:</label>
                          <div class="controls">
                            <input type="text" name="comercioExterior[receptor][domicilio][colonia]" id="cce_receptor_colonia" class="span12 sikey" value="<?php echo set_value('comercioExterior[receptor][domicilio][colonia]', isset($com_ex->extra->receptor) ? $com_ex->extra->receptor->domicilio->colonia : ''); ?>" maxlength="60" data-next="cce_propietario_numRegIdTrib">
                            <span class="cce_receptor_colonia help-block nomarg" style="color:#bd362f"></span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div><!--/Receptor -->

                  <!-- Propietario -->
                  <div class="row-fluid">
                    <div class="box span12">
                      <div class="box-header well">
                        <h2><i class="icon-align-justify"></i><span class="break"></span>Propietario</h2>
                        <div class="box-icon">
                        </div>
                      </div>
                      <div class="box-content" style="padding: 0;">

                        <table class="table table-striped" id="table-destinatariod">
                            <thead>
                              <tr>
                                <th>Num Reg Id Trib <i class="icon-question-sign helpover" data-title=""></i></th>
                                <th>Residencia Fiscal <i class="icon-question-sign helpover" data-title=""></i></th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td class="center"><input type="text" name="comercioExterior[propietario][numRegIdTrib]" value="<?php echo set_value('comercioExterior[propietario][numRegIdTrib]', isset($com_ex->extra->propietario) ? $com_ex->extra->propietario[0]->numRegIdTrib : ''); ?>" id="cce_propietario_numRegIdTrib" class="span12 sikey" data-next="cce_propietario_residenciaFiscal"></td>
                                <td class="center"><input type="text" name="comercioExterior[propietario][residenciaFiscal]" value="<?php echo set_value('comercioExterior[propietario][residenciaFiscal]', isset($com_ex->extra->propietario) ? $com_ex->extra->propietario[0]->residenciaFiscal : ''); ?>" id="cce_propietario_residenciaFiscal" class="span12 sikey" data-next="cce_destinatario_num_reg_id_trib"></td>
                              </tr>
                            </tbody>
                         </table>
                      </div>
                    </div>
                  </div><!--/Propietario -->

                  <!-- Destinatario -->
                  <div class="row-fluid">
                    <div class="box span12">
                      <div class="box-header well">
                        <h2><i class="icon-align-justify"></i><span class="break"></span>Destinatario</h2>
                        <div class="box-icon">
                        </div>
                      </div>
                      <div class="box-content" style="padding: 0;">

                        <div class="alert alert-info">
                          Seccion opcional para capturar los datos del destinatario de la mercancía cuando éste sea distinto del receptor del CFDI.
                        </div>

                        <div class="row-fluid">
                          <div class="span4">
                            <div class="control-group">
                              <label class="control-label" for="cce_destinatario_num_reg_id_trib" style="width: 115px;">Num Reg Id Trib <i class="icon-question-sign helpover" data-title="Num Reg Id Trib: Atributo opcional para incorporar el número de identificación o registro fiscal del país de residencia para efectos fiscales del destinatario de la mercancía exportada."></i></label>
                              <div class="controls" style="margin-left: 133px;">
                                <input type="text" name="comercioExterior[destinatario][numRegIdTrib]" class="span12 sikey" id="cce_destinatario_num_reg_id_trib" value="<?php echo set_value('comercioExterior[destinatario][numRegIdTrib]', isset($com_ex->destinatario) ? $com_ex->destinatario->numregidtrib : ''); ?>" placeholder="Num Reg Id Trib" minlength="6" maxlength="40" data-next="cce_destinatario_nombre">
                              </div>
                            </div><!--/control-group -->
                          </div>

                          <div class="span5">
                            <div class="control-group">
                              <label class="control-label" for="cce_destinatario_nombre" style="width: 80px;">Nombre <i class="icon-question-sign helpover" data-title="Nombre: Atributo opcional para expresar el nombre completo, denominación o razón social del destinatario de la mercancía exportada"></i></label>
                              <div class="controls" style="margin-left: 83px;">
                                <input type="text" name="comercioExterior[destinatario][nombre]" class="span12 sikey" id="cce_destinatario_nombre" value="<?php echo set_value('comercioExterior[destinatario][nombre]', isset($com_ex->destinatario) ? $com_ex->destinatario->nombre : ''); ?>" placeholder="Nombre" data-next="cce_destinatario_dom_calle">
                              </div>
                            </div><!--/control-group -->
                          </div>
                        </div>

                        <table class="table table-striped" id="table-destinatariod">
                            <thead>
                              <tr>
                                <th>Calle <i class="icon-question-sign helpover" data-title="Calle: Atributo requerido sirve para precisar la calle en que está ubicado el domicilio del destinatario de la mercancía."></i></th>
                                <th>No. Exterior <i class="icon-question-sign helpover" data-title="No. Exterior: Atributo opcional sirve para expresar el número exterior en donde se ubica el domicilio del destinatario de la mercancía."></i></th>
                                <th>No. Interior <i class="icon-question-sign helpover" data-title="No. Interior: Campo opcional sirve para expresar información adicional para especificar la ubicación cuando calle y número exterior no resulten suficientes para determinar la ubicación precisa del inmuebleAtributo opcional sirve para expresar el número interior, en caso de existir, en donde se ubica el domicilio del destinatario de la mercancía."></i></th>
                                <th>Referencia <i class="icon-question-sign helpover" data-title="Referencia: Atributo opcional para expresar una referencia geográfica adicional que permita una más fácil o precisa ubicación del domicilio del destinatario de la mercancía, por ejemplo las coordenadas GPS."></i></th>
                                <th>Pais <i class="icon-question-sign helpover" data-title="Pais: Atributo requerido que sirve para precisar el país donde  se encuentra ubicado el destinatario de la mercancía."></i></th>
                                <th>Estado <i class="icon-question-sign helpover" data-title="Estado: Atributo requerido para señalar el estado, entidad, región, comunidad u otra figura análoga en donde  se encuentra ubicado el  domicilio del destinatario de la mercancía."></i></th>
                                <th>Municipio <i class="icon-question-sign helpover" data-title="Municipio: Atributo opcional que sirve para precisar el municipio, delegación, condado u otro análogo en donde se encuentra ubicado el destinatario de la mercancía."></i></th>
                                <th>Localidad <i class="icon-question-sign helpover" data-title="Localidad: Atributo opcional que sirve para precisar la ciudad, población, distrito u otro análogo en donde se ubica el domicilio del  destinatario de la mercancía."></i></th>
                                <th>Codigo Postal <i class="icon-question-sign helpover" data-title="Codigo Postal: Atributo requerido que sirve para asentar el código postal (PO, BOX) en donde se encuentra ubicado el domicilio del destinatario de la mercancía."></i></th>
                                <th>Colonia <i class="icon-question-sign helpover" data-title="Colonia: Atributo opcional sirve para expresar la colonia o dato análogo en donde se ubica el domicilio del destinatario de la mercancía."></i></th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td class="center"><input type="text" name="comercioExterior[destinatario][domicilio][calle]" value="<?php echo set_value('comercioExterior[destinatario][domicilio][calle]', isset($com_ex->destinatario) ? $com_ex->destinatario->calle : ''); ?>" id="cce_destinatario_dom_calle" minlength="1" maxlength="100" class="span12 sikey" data-next="cce_destinatario_dom_numeroExterior"></td>
                                <td class="center"><input type="text" name="comercioExterior[destinatario][domicilio][numeroExterior]" value="<?php echo set_value('comercioExterior[destinatario][domicilio][numeroExterior]', isset($com_ex->destinatario) ? $com_ex->destinatario->numero_exterior : ''); ?>" id="cce_destinatario_dom_numeroExterior" minlength="1" maxlength="55" class="span12 sikey" data-next="cce_destinatario_dom_numeroInterior"></td>
                                <td class="center"><input type="text" name="comercioExterior[destinatario][domicilio][numeroInterior]" value="<?php echo set_value('comercioExterior[destinatario][domicilio][numeroInterior]', isset($com_ex->destinatario) ? $com_ex->destinatario->numero_interior : ''); ?>" id="cce_destinatario_dom_numeroInterior" minlength="1" maxlength="55" class="span12 sikey" data-next="cce_destinatario_dom_referencia"></td>
                                <td class="center"><input type="text" name="comercioExterior[destinatario][domicilio][referencia]" value="<?php echo set_value('comercioExterior[destinatario][domicilio][referencia]', isset($com_ex->destinatario) ? $com_ex->destinatario->referencia : ''); ?>" id="cce_destinatario_dom_referencia" minlength="1" maxlength="250" class="span12 sikey" data-next="cce_destinatario_dom_pais"></td>
                                <td class="center"><input type="text" name="comercioExterior[destinatario][domicilio][pais]" value="<?php echo set_value('comercioExterior[destinatario][domicilio][pais]', isset($com_ex->destinatario) ? $com_ex->destinatario->pais : ''); ?>" id="cce_destinatario_dom_pais" maxlength="40" class="span12 sikey" data-next="cce_destinatario_dom_estado"></td>
                                <td class="center"><input type="text" name="comercioExterior[destinatario][domicilio][estado]" value="<?php echo set_value('comercioExterior[destinatario][domicilio][estado]', isset($com_ex->destinatario) ? $com_ex->destinatario->estado : ''); ?>" id="cce_destinatario_dom_estado" maxlength="60" class="span12 sikey" data-next="cce_destinatario_dom_municipio"></td>
                                <td class="center"><input type="text" name="comercioExterior[destinatario][domicilio][municipio]" value="<?php echo set_value('comercioExterior[destinatario][domicilio][municipio]', isset($com_ex->destinatario) ? $com_ex->destinatario->municipio : ''); ?>" id="cce_destinatario_dom_municipio" minlength="1" maxlength="120" class="span12 sikey" data-next="cce_destinatario_dom_localidad"></td>
                                <td class="center"><input type="text" name="comercioExterior[destinatario][domicilio][localidad]" value="<?php echo set_value('comercioExterior[destinatario][domicilio][localidad]', isset($com_ex->destinatario) ? $com_ex->destinatario->localidad : ''); ?>" id="cce_destinatario_dom_localidad" minlength="1" maxlength="120" class="span12 sikey" data-next="cce_destinatario_dom_codigopostal"></td>
                                <td class="center"><input type="text" name="comercioExterior[destinatario][domicilio][codigoPostal]" value="<?php echo set_value('comercioExterior[destinatario][domicilio][codigoPostal]', isset($com_ex->destinatario) ? $com_ex->destinatario->codigo_postal : ''); ?>" class="span12 sikey" id="cce_destinatario_dom_codigopostal" minlength="1" maxlength="12" data-next="cce_destinatario_dom_colonia"></td>
                                <td class="center"><input type="text" name="comercioExterior[destinatario][domicilio][colonia]" value="<?php echo set_value('comercioExterior[destinatario][domicilio][colonia]', isset($com_ex->destinatario) ? $com_ex->destinatario->colonia : ''); ?>" id="cce_destinatario_dom_colonia" minlength="1" maxlength="120" class="span12 sikey" data-next="cce_destinatario_dom_calle"></td>
                                <!-- <td>
                                  <select name="comercioExterior[Destinatario][Domicilio][Estado]" id="cce_destinatario_dom_estado" class="span12 sikey" data-next="cce_destinatario_dom_pais">
                                    <option value=""></option>
                                    @foreach ($entidadesiso->toArray() as $clave => $entidad)
                                      <option value="{{ $clave }}" {{ Input::old('comercioExterior')['Destinatario']['Domicilio']['Estado'] == $clave ? 'selected' : '' }}>{{ $entidad['nombre'] }} - {{ $entidad['pais'] }}</option>
                                    @endforeach
                                  </select>
                                </td>
                                <td>
                                  <select name="comercioExterior[Destinatario][Domicilio][Pais]" id="cce_destinatario_dom_pais" class="span12 sikey" data-next="cce_destinatario_dom_codigopostal">
                                    <option value=""></option>
                                    @foreach ($paises->toArray() as $clave => $pais)
                                      <option value="{{ $clave }}" {{ $clave == Input::old('comercioExterior')['Destinatario']['Domicilio']['Pais'] ? 'selected' : '' }}>{{ $pais }}</option>
                                    @endforeach
                                  </select>
                                </td> -->
                              </tr>
                            </tbody>
                         </table>
                      </div>
                    </div>
                  </div><!--/Destinatario -->

                  <!-- Mercancias -->
                  <div class="row-fluid">
                    <div class="box span12">
                      <div class="box-header well">
                        <h2><i class="icon-align-justify"></i><span class="break"></span>Mercancias</h2>
                        <div class="box-icon">
                          <a href="javascript:void(0);" class="btn-add" id="btn-add-mercancias" data-rel="tooltip" data-title="Agregar Mercancias"><i class="icon-plus"></i></a>
                        </div>
                      </div>
                      <div class="box-content" style="padding: 0;">

                        <div class="alert alert-info">
                          Seccion opcional para capturar la información de la declaración de las mercancías exportadas.
                        </div>

                        <select id="mercancias-unidades" style="display: none;">
                          <option value=""></option>
                          <?php foreach ($ceUnidades as $clave => $unidad) { ?>
                            <option value="<?php echo $clave; ?>"><?php echo $clave.' - '.$unidad['value']; ?></option>
                          <?php } ?>
                        </select>

                        <table class="table table-striped" id="table-mercancias">
                            <thead>
                              <tr>
                                <th>No Identificacion <i class="icon-question-sign helpover" data-title="No Identificacion: Atributo requerido que sirve para expresar el número de parte, la clave de identificación que asigna la empresa o el número de serie de la mercancía exportada."></i></th>
                                <th>Fraccion Arancelaria <i class="icon-question-sign helpover" data-title="Fraccion Arancelaria: Atributo opcional que sirve para expresar la fracción arancelaria correspondiente a la descripción de la mercancía exportada, este dato se vuelve requerido cuando se cuente con él o se esté obligado legalmente a contar con él."></i></th>
                                <th>Cantidad Aduana <i class="icon-question-sign helpover" data-title="Cantidad Aduana: Atributo opcional para precisar la cantidad de bienes en la aduana conforme a la UnidadAduana cuando en el nodo Comprobante:Conceptos:Concepto se hubiera registrado información comercial."></i></th>
                                <th>Unidad Aduana <i class="icon-question-sign helpover" data-title="Unidad Aduana: Atributo opcional para precisar la unidad de medida aplicable para la cantidad expresada en la mercancía en la aduana."></i></th>
                                <th>Valor Unitario Aduana <i class="icon-question-sign helpover" data-title="Valor Unitario Aduana: Atributo opcional para precisar el valor o precio unitario del bien en la aduana. Se expresa en dólares de Estados Unidos (USD)."></i></th>
                                <th>Valor Dolares <i class="icon-question-sign helpover" data-title="Valor Dolares: Atributo requerido que indica el valor total en dólares de Estados Unidos."></i></th>
                                <th></th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php $keyindex = 0;
                              if (isset($this->input->post("comercioExterior")['mercancias']) && count($this->input->post("comercioExterior")['mercancias']['noIdentificacion']) > 0) {
                                foreach ($this->input->post("comercioExterior")['mercancias']['noIdentificacion'] as $key => $NoIdentificacion) {
                              ?>
                                <?php $keyindex = $key; ?>
                                  <tr>
                                    <td class="center"><input type="text" name="comercioExterior[mercancias][noIdentificacion][]" value="<?php echo $_POST['comercioExterior']['mercancias']['noIdentificacion'][$key] ?>" class="span12 sikey" maxlength="100"></td>
                                    <td class="center"><input type="text" name="comercioExterior[mercancias][fraccionArancelaria][]" value="<?php echo $_POST['comercioExterior']['mercancias']['fraccionArancelaria'][$key] ?>" class="fraccionArancelaria span12 sikey" maxlength="20"></td>
                                    <td class="center"><input type="text" name="comercioExterior[mercancias][cantidadAduana][]" value="<?php echo $_POST['comercioExterior']['mercancias']['cantidadAduana'][$key] ?>" class="span12 sikey vpositive"></td>
                                    <td class="center">
                                      <select name="comercioExterior[mercancias][unidadAduana][]" class="span12 sikey ceUnidadAduana">
                                        <option value=""></option>
                                      <?php foreach ($ceUnidades as $clave => $unidad) { ?>
                                        <option value="<?php echo $clave; ?>" <?php echo $_POST['comercioExterior']['mercancias']['unidadAduana'][$key] == $clave? 'selected': '' ?>><?php echo $clave.' - '.$unidad['value']; ?></option>
                                      <?php } ?>
                                      </select>
                                    </td>
                                    <td class="center"><input type="text" name="comercioExterior[mercancias][valorUnitarioAduana][]" value="<?php echo $_POST['comercioExterior']['mercancias']['valorUnitarioAduana'][$key] ?>" class="span12 sikey vpositive"></td>
                                    <td class="center"><input type="text" name="comercioExterior[mercancias][valorDolares][]" value="<?php echo $_POST['comercioExterior']['mercancias']['valorDolares'][$key] ?>" class="span12 sikey vpositive"></td>
                                    <td class="center">
                                      <button type="button" class="btn btn-danger btn-del-mercancias" data-index="<?php echo $key ?>"><i class="icon-remove"></i></button>
                                      <button type="button" class="btn btn-success btn-add-desc-especifica" data-index="<?php echo $key ?>"><i class="icon-plus"></i></button>
                                    </td>
                                  </tr>
                                  <?php
                                  if (isset($this->input->post("comercioExterior")['mercancias']['descripcionesEspecificas']) &&
                                      count($this->input->post("comercioExterior")['mercancias']['descripcionesEspecificas'][$key]['marca']) > 0) {
                                    foreach ($this->input->post("comercioExterior")['mercancias']['descripcionesEspecificas'][$key]['marca'] as $key2 => $descEsp) { ?>
                                      <tr class="<?php echo $key ?>">
                                        <td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas][<?php echo $key ?>][marca][]" value="<?php echo $_POST['comercioExterior']['mercancias']['descripcionesEspecificas'][$key]['marca'][$key2] ?>" placeholder="Marca" class="span12 sikey" maxlength="35"></td>
                                        <td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas][<?php echo $key ?>][modelo][]" value="<?php echo $_POST['comercioExterior']['mercancias']['descripcionesEspecificas'][$key]['modelo'][$key2] ?>" placeholder="Modelo" class="span12 sikey" maxlength="80"></td>
                                        <td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas][<?php echo $key ?>][subModelo][]" value="<?php echo $_POST['comercioExterior']['mercancias']['descripcionesEspecificas'][$key]['subModelo'][$key2] ?>" placeholder="SubModelo" class="span12 sikey" maxlength="50"></td>
                                        <td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas][<?php echo $key ?>][numeroSerie][]" value="<?php echo $_POST['comercioExterior']['mercancias']['descripcionesEspecificas'][$key]['numeroSerie'][$key2] ?>" placeholder="NumeroSerie" class="span12 sikey" maxlength="40"></td>
                                        <td class="center">
                                          <button type="button" class="btn btn-danger btn-del-mercancias"><i class="icon-remove"></i></button>
                                        </td>
                                      </tr>
                                <?php
                                    }
                                  }
                                }
                              }
                              elseif(isset($com_ex->mercancias)) {
                                foreach ($com_ex->mercancias as $key => $mercancia) {
                              ?>
                                <?php $keyindex = $key; ?>
                                  <tr>
                                    <td class="center"><input type="text" name="comercioExterior[mercancias][noIdentificacion][]" value="<?php echo $mercancia->noidentificacion; ?>" class="span12 sikey" maxlength="100"></td>
                                    <td class="center"><input type="text" name="comercioExterior[mercancias][fraccionArancelaria][]" value="<?php echo $mercancia->fraccionar_ancelaria; ?>" class="fraccionArancelaria span12 sikey" maxlength="20"></td>
                                    <td class="center"><input type="text" name="comercioExterior[mercancias][cantidadAduana][]" value="<?php echo $mercancia->cantidad_aduana; ?>" class="span12 sikey vpositive"></td>
                                    <td class="center">
                                      <select name="comercioExterior[mercancias][unidadAduana][]" class="span12 sikey ceUnidadAduana">
                                        <option value=""></option>
                                      <?php foreach ($ceUnidades as $clave => $unidad) { ?>
                                          <option value="<?php echo $clave; ?>" <?php echo $clave == $mercancia->unidad_aduana ? 'selected' : '' ?>><?php echo $clave.' - '.$unidad['value']; ?></option>
                                      <?php }?>
                                      </select>
                                    </td>
                                    <td class="center"><input type="text" name="comercioExterior[mercancias][valorUnitarioAduana][]" value="<?php echo $mercancia->valor_unitario_aduana; ?>" class="span12 sikey vpositive"></td>
                                    <td class="center"><input type="text" name="comercioExterior[mercancias][valorDolares][]" value="<?php echo $mercancia->valor_dolares; ?>" class="span12 sikey vpositive"></td>
                                    <td class="center">
                                      <button type="button" class="btn btn-danger btn-del-mercancias" data-index="<?php echo $key ?>"><i class="icon-remove"></i></button>
                                      <button type="button" class="btn btn-success btn-add-desc-especifica" data-index="<?php echo $key ?>"><i class="icon-plus"></i></button>
                                    </td>
                                  </tr>
                                  <?php if(isset($mercancia->esp)) {
                                    foreach ($mercancia->esp as $key2 => $descEsp) { ?>
                                      <tr class="<?php echo $key ?>">
                                        <td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas][<?php echo $key ?>][marca][]" value="<?php echo $descEsp->marca; ?>" placeholder="Marca" class="span12 sikey" maxlength="35"></td>
                                        <td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas][<?php echo $key ?>][modelo][]" value="<?php echo $descEsp->modelo; ?>" placeholder="Modelo" class="span12 sikey" maxlength="80"></td>
                                        <td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas][<?php echo $key ?>][subModelo][]" value="<?php echo $descEsp->submodelo; ?>" placeholder="SubModelo" class="span12 sikey" maxlength="50"></td>
                                        <td class="center"><input type="text" name="comercioExterior[mercancias][descripcionesEspecificas][<?php echo $key ?>][numeroSerie][]" value="<?php echo $descEsp->numeroserie; ?>" placeholder="NumeroSerie" class="span12 sikey" maxlength="40"></td>
                                        <td class="center">
                                          <button type="button" class="btn btn-danger btn-del-mercancias"><i class="icon-remove"></i></button>
                                        </td>
                                      </tr>
                              <?php
                                    }
                                  }
                                }
                              }?>
                            </tbody>
                         </table>
                         <span id="indexMercancias" style="visibility:hidden"><?php echo $keyindex; ?></span>
                      </div>
                    </div>
                  </div><!--/Mercancias -->
                </div>
              </div>
            </div>

            <div class="tab-pane" id="tabCartaPorte">
              <div class="row-fluid">
                <div class="span12 mquit">
                  <div class="alert alert-block">
                    <h4 class="alert-heading"> COMPLEMENTO DE CARTA PORTE</h4>
                    <!-- <span class="help-inline">Este complemento será utilizado por los contribuyentes que exporten mercancías en definitiva con la clave de pedimento “A1”, de conformidad con la regla 2.7.1.22. de la Resolución Miscelánea Fiscal vigente, en relación con las Reglas Generales de Comercio Exterior 3.1.35. y 3.1.36., también vigentes; y servirá para incorporar la información del tipo de operación, datos de identificación fiscal del emisor, receptor o destinatario de la mercancía y la descripción de las mercancías exportadas.</span> -->
                  </div>
                  <div class="row-fluid">

                    <div class="span6">
                      <div class="control-group">
                        <label class="control-label" for="cp_transpInternac">Transporte Internacional <i class="icon-question-sign helpover" data-title=""></i></label>
                        <div class="controls">
                          <?php
                            $cfdi_ext = empty($borrador['info']->cfdi_ext)? (!empty($cfdiExt)? json_decode($cfdiExt): null): json_decode($borrador['info']->cfdi_ext);
                            // echo "<pre>";
                            //   var_dump($cfdi_ext);
                            // echo "</pre>";exit;
                            $cpobj = isset($cfdi_ext->cartaPorteSat) ? $cfdi_ext->cartaPorteSat : '';
                            $transpInternac = isset($cpobj->transpInternac) ? $cpobj->transpInternac : '';
                          ?>
                          <select name="cp[transpInternac]" class="span12 sikey" id="cp_transpInternac" data-next="cp_entradaSalidaMerc">
                            <option value=""></option>
                            <option value="Sí" <?php echo set_select('cp[transpInternac]', 'Sí', $transpInternac === 'Sí' ? true : false, $transpInternac); ?>>Sí</option>
                            <option value="No" <?php echo set_select('cp[transpInternac]', 'No', $transpInternac === 'No' ? true : false, $transpInternac); ?>>No</option>
                          </select>
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for="cp_entradaSalidaMerc">Entrada / Salida de Mercancía <i class="icon-question-sign helpover" data-title="Tipo Operacion: Atributo requerido que indica el tipo de operación de comercio exterior que se realiza, puede ser importación o exportación, A = exportación de servicios. 2 = exportación."></i></label>
                        <div class="controls">
                          <?php
                            $entradaSalidaMerc = isset($cpobj->entradaSalidaMerc) ? $cpobj->entradaSalidaMerc : ''; ?>
                          <select name="cp[entradaSalidaMerc]" class="span12 sikey" id="cp_entradaSalidaMerc" data-next="cp_paisOrigenDestino_text">
                            <option value=""></option>
                            <option value="Entrada" <?php echo set_select('cp[entradaSalidaMerc]', 'Entrada', $entradaSalidaMerc === 'Entrada' ? true : false, $entradaSalidaMerc); ?>>Entrada</option>
                            <option value="Salida" <?php echo set_select('cp[entradaSalidaMerc]', 'Salida', $entradaSalidaMerc === 'Salida' ? true : false, $entradaSalidaMerc); ?>>Salida</option>
                          </select>
                        </div>
                      </div>

                      <div class="control-group">
                        <label class="control-label" for="cp_paisOrigenDestino_text">País:</label>
                        <div class="controls">
                          <input type="text" name="cp[paisOrigenDestino_text]" id="cp_paisOrigenDestino_text" class="span12 sikey" value="<?php echo set_value('cp[paisOrigenDestino_text]', isset($cpobj->paisOrigenDestino_text) ? $cpobj->paisOrigenDestino_text : ''); ?>" maxlength="60" autocomplete="off" data-next="cp_viaEntradaSalida">
                          <input type="hidden" name="cp[paisOrigenDestino]" id="cp_paisOrigenDestino" class="span12 sikey" value="<?php echo set_value('cp[paisOrigenDestino]', isset($cpobj->paisOrigenDestino) ? $cpobj->paisOrigenDestino : ''); ?>" maxlength="60" autocomplete="off">
                          <span class="cp_paisOrigenDestino help-block nomarg" style="color:#bd362f"></span>
                        </div>
                      </div>
                    </div>

                    <div class="span6">
                      <div class="control-group">
                        <label class="control-label" for="cp_viaEntradaSalida">Via de Entrada / Salida <i class="icon-question-sign helpover" data-title=""></i></label>
                        <div class="controls">
                          <?php $viaEntradaSalida = isset($cpobj->viaEntradaSalida) ? $cpobj->viaEntradaSalida : ''; ?>
                          <select name="cp[viaEntradaSalida]" class="span12 sikey" id="cp_viaEntradaSalida" data-next="cp_totalDistRec">
                            <option value="" <?php echo set_select('cp[viaEntradaSalida]', '', $viaEntradaSalida === '' ? true : false); ?>></option>
                            <option value="01" <?php echo set_select('cp[viaEntradaSalida]', '01', $viaEntradaSalida === '01' ? true : false, $viaEntradaSalida); ?>>01 - Autotransporte Federal</option>
                            <option value="02" <?php echo set_select('cp[viaEntradaSalida]', '02', $viaEntradaSalida === '02' ? true : false, $viaEntradaSalida); ?>>02 - Transporte Marítimo</option>
                            <option value="03" <?php echo set_select('cp[viaEntradaSalida]', '03', $viaEntradaSalida === '03' ? true : false, $viaEntradaSalida); ?>>03 - Transporte Aéreo</option>
                            <option value="04" <?php echo set_select('cp[viaEntradaSalida]', '04', $viaEntradaSalida === '04' ? true : false, $viaEntradaSalida); ?>>04 - Transporte Ferroviario</option>
                            <option value="05" <?php echo set_select('cp[viaEntradaSalida]', '05', $viaEntradaSalida === '05' ? true : false, $viaEntradaSalida); ?>>05 - Ducto</option>
                          </select>
                        </div>
                      </div>
                      <div class="control-group">
                        <label class="control-label" for="cp_totalDistRec">Total Distancia Recorrida (Km) <i class="icon-question-sign helpover" data-title=""></i></label>
                        <div class="controls">
                          <?php $totalDistRec = isset($cpobj->totalDistRec) ? $cpobj->totalDistRec : ''; ?>
                          <input type="number" step="any" name="cp[totalDistRec]" class="span12 sikey" id="cp_totalDistRec" value="<?php echo set_value('cp[totalDistRec]', $totalDistRec); ?>" placeholder="" data-next="cp_origenDestino_pais">
                        </div>
                      </div>
                    </div>
                    <!-- !-- Datos Carta Porte -->
                  </div>

                  <!-- Ubicaciones -->
                  <div class="row-fluid">
                    <div class="box span12">
                      <div class="box-header well">
                        <h2><i class="icon-align-justify"></i><span class="break"></span>Ubicaciones</h2>
                        <div class="box-icon">
                          <a href="javascript:void(0);" class="btn-add" id="btn-add-CpUbicaciones" data-rel="tooltip" data-title="Agregar Ubicaciones"><i class="icon-plus"></i></a>
                        </div>
                      </div>
                      <div class="box-content" style="padding: 0;" id="boxUbicaciones">

                        <?php if (isset($cpobj->ubicaciones) && count($cpobj->ubicaciones) > 0): ?>
                        <?php $count = 0; ?>
                        <?php foreach ($cpobj->ubicaciones as $key => $ubic): ?>
                        <div class="ubicacionn">
                          <table class="table table-striped table-cpOrigen">
                            <thead>
                              <tr>
                                <th>Tipo Ubicacion <i class="icon-question-sign helpover" data-title=""></i></th>
                                <th>ID Ubicacion <i class="icon-question-sign helpover" data-title=""></i></th>
                                <th>RFC <i class="icon-question-sign helpover" data-title=""></i></th>
                                <th>Nombre <i class="icon-question-sign helpover" data-title=""></i></th>
                                <th>Num Reg Id Trib <i class="icon-question-sign helpover" data-title=""></i></th>
                                <th>Residencia Fiscal <i class="icon-question-sign helpover" data-title=""></i>
                                  <button type="button" class="btn btn-danger btn-cp-removeUbicacion">Quitar</button>
                                </th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td class="center">
                                  <?php $tipoUbicacion = isset($ubic->tipoUbicacion) ? $ubic->tipoUbicacion : ''; ?>
                                  <select name="cp[ubicaciones][<?php echo $count ?>][tipoUbicacion]" id="cp_ubic_tipoUbicacion">
                                    <option value="" <?php echo set_select('cp[ubicaciones]['.$count.'][tipoUbicacion]', '', $tipoUbicacion === '' ? true : false, $tipoUbicacion); ?>></option>
                                    <option value="Origen" <?php echo set_select('cp[ubicaciones]['.$count.'][tipoUbicacion]', 'Origen', $tipoUbicacion === 'Origen' ? true : false, $tipoUbicacion); ?>>Origen</option>
                                    <option value="Destino" <?php echo set_select('cp[ubicaciones]['.$count.'][tipoUbicacion]', 'Destino', $tipoUbicacion === 'Destino' ? true : false, $tipoUbicacion); ?>>Destino</option>
                                  </select>
                                </td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][idUbicacion]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][idUbicacion]', isset($ubic->idUbicacion) ? $ubic->idUbicacion : ''); ?>" id="cp_ubic_idUbicacion" minlength="8" maxlength="8" class="span12 sikey" data-next="cp_ubic_rfcRemitenteDestinatario"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][rfcRemitenteDestinatario]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][rfcRemitenteDestinatario]', isset($ubic->rfcRemitenteDestinatario) ? $ubic->rfcRemitenteDestinatario : ''); ?>" id="cp_ubic_rfcRemitenteDestinatario" minlength="12" maxlength="13" class="span12 sikey" data-next="cp_ubic_nombreRemitenteDestinatario"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][nombreRemitenteDestinatario]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][nombreRemitenteDestinatario]', isset($ubic->nombreRemitenteDestinatario) ? $ubic->nombreRemitenteDestinatario : ''); ?>" id="cp_ubic_nombreRemitenteDestinatario" minlength="1" maxlength="254" class="span12 sikey" data-next="cp_ubic_numRegIdTrib"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][numRegIdTrib]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][numRegIdTrib]', isset($ubic->numRegIdTrib) ? $ubic->numRegIdTrib : ''); ?>" id="cp_ubic_numRegIdTrib" minlength="6" maxlength="40" class="span12 sikey" data-next="cp_ubic_residenciaFiscal_text"></td>
                                <td class="center">
                                  <input type="text" name="cp[ubicaciones][<?php echo $count ?>][residenciaFiscal_text]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][residenciaFiscal_text]', isset($ubic->residenciaFiscal_text) ? $ubic->residenciaFiscal_text : ''); ?>" id="cp_ubic_residenciaFiscal_text" maxlength="40" class="span12 sikey" data-next="cp_ubic_numEstacion">
                                  <input type="hidden" name="cp[ubicaciones][<?php echo $count ?>][residenciaFiscal]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][residenciaFiscal]', isset($ubic->residenciaFiscal) ? $ubic->residenciaFiscal : ''); ?>" id="cp_ubic_residenciaFiscal" maxlength="40" class="span12 sikey">
                                </td>
                              </tr>
                              <tr>
                                <th>Num Estacion <i class="icon-question-sign helpover" data-title=""></i></th>
                                <th>Nombre Estacion <i class="icon-question-sign helpover" data-title=""></i></th>
                                <th>Navegacion Trafico <i class="icon-question-sign helpover" data-title=""></i></th>
                                <th>Fecha y Hora de Salida <i class="icon-question-sign helpover" data-title=""></i></th>
                                <th>Tipo Estación <i class="icon-question-sign helpover" data-title=""></i></th>
                                <th>Distancia Recorrida (Km) <i class="icon-question-sign helpover" data-title=""></i></th>
                              </tr>
                              <tr>
                                <td class="center">
                                  <input type="text" name="cp[ubicaciones][<?php echo $count ?>][numEstacion]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][numEstacion]', isset($ubic->numEstacion) ? $ubic->numEstacion : ''); ?>" id="cp_ubic_numEstacion" maxlength="60" class="span12 sikey" data-next="cp_ubic_nombreEstacion">
                                  <input type="hidden" name="cp[ubicaciones][<?php echo $count ?>][numEstacion_text]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][numEstacion_text]', isset($ubic->numEstacion_text) ? $ubic->numEstacion_text : ''); ?>" id="cp_ubic_numEstacion_text" maxlength="60" class="span12 sikey">
                                </td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][nombreEstacion]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][nombreEstacion]', isset($ubic->nombreEstacion) ? $ubic->nombreEstacion : ''); ?>" id="cp_ubic_nombreEstacion" minlength="1" maxlength="50" class="span12 sikey" data-next="cp_ubic_navegacionTrafico"></td>
                                <td class="center">
                                  <?php $navegacionTrafico = isset($ubic->navegacionTrafico) ? $ubic->navegacionTrafico : ''; ?>
                                  <select name="cp[ubicaciones][<?php echo $count ?>][navegacionTrafico]" id="cp_ubic_navegacionTrafico">
                                    <option value="" <?php echo set_select('cp[ubicaciones]['.$count.'][navegacionTrafico]', '', $navegacionTrafico === '' ? true : false, $navegacionTrafico); ?>></option>
                                    <option value="Altura" <?php echo set_select('cp[ubicaciones]['.$count.'][navegacionTrafico]', 'Altura', $navegacionTrafico === 'Altura' ? true : false, $navegacionTrafico); ?>>Altura</option>
                                    <option value="Cabotaje" <?php echo set_select('cp[ubicaciones]['.$count.'][navegacionTrafico]', 'Cabotaje', $navegacionTrafico === 'Cabotaje' ? true : false, $navegacionTrafico); ?>>Cabotaje</option>
                                  </select>
                                </td>
                                <td class="center"><input type="datetime-local" name="cp[ubicaciones][<?php echo $count ?>][fechaHoraSalidaLlegada]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][fechaHoraSalidaLlegada]', isset($ubic->fechaHoraSalidaLlegada) ? $ubic->fechaHoraSalidaLlegada : ''); ?>" id="cp_ubic_fechaHoraSalidaLlegada" class="span12 sikey" minlength="1" maxlength="12" data-next="cce_destinatario_dom_colonia"></td>
                                <td class="center">
                                  <?php $tipoEstacion = isset($ubic->tipoEstacion) ? $ubic->tipoEstacion : ''; ?>
                                  <select name="cp[ubicaciones][<?php echo $count ?>][tipoEstacion]" class="span12 sikey" id="cp_tipoEstacion" data-next="cp_totalDistRec">
                                    <option value="" <?php echo set_select('cp[ubicaciones]['.$count.'][tipoEstacion]', '', $tipoEstacion === '' ? true : false, $tipoEstacion); ?>></option>
                                    <option value="01" <?php echo set_select('cp[ubicaciones]['.$count.'][tipoEstacion]', '01', $tipoEstacion === '01' ? true : false, $tipoEstacion); ?>>01 - Origen Nacional</option>
                                    <option value="02" <?php echo set_select('cp[ubicaciones]['.$count.'][tipoEstacion]', '02', $tipoEstacion === '02' ? true : false, $tipoEstacion); ?>>02 - Intermedia</option>
                                    <option value="03" <?php echo set_select('cp[ubicaciones]['.$count.'][tipoEstacion]', '03', $tipoEstacion === '03' ? true : false, $tipoEstacion); ?>>03 - Destino Final Nacional</option>
                                  </select>
                                </td>
                                <td class="center">
                                  <?php $distanciaRecorrida = isset($ubic->distanciaRecorrida) ? $ubic->distanciaRecorrida : ''; ?>
                                  <input type="text" name="cp[ubicaciones][<?php echo $count ?>][distanciaRecorrida]" class="span12 sikey" id="cp_ubicaciones_distanciaRecorrida" value="<?php echo set_value('cp[ubicaciones]['.$count.'][distanciaRecorrida]', $distanciaRecorrida); ?>" placeholder="Nombre" data-next="cce_destinatario_dom_calle">
                                </td>
                              </tr>
                            </tbody>
                          </table>

                          <table class="table table-striped table-cpDomicilio">
                            <thead>
                              <tr>
                                <th>Calle <i class="icon-question-sign helpover" data-title="Calle: Atributo requerido sirve para precisar la calle en que está ubicado el domicilio del destinatario de la mercancía."></i></th>
                                <th>No. Exterior <i class="icon-question-sign helpover" data-title="No. Exterior: Atributo opcional sirve para expresar el número exterior en donde se ubica el domicilio del destinatario de la mercancía."></i></th>
                                <th>No. Interior <i class="icon-question-sign helpover" data-title="No. Interior: Campo opcional sirve para expresar información adicional para especificar la ubicación cuando calle y número exterior no resulten suficientes para determinar la ubicación precisa del inmuebleAtributo opcional sirve para expresar el número interior, en caso de existir, en donde se ubica el domicilio del destinatario de la mercancía."></i></th>
                                <th>Referencia <i class="icon-question-sign helpover" data-title="Referencia: Atributo opcional para expresar una referencia geográfica adicional que permita una más fácil o precisa ubicación del domicilio del destinatario de la mercancía, por ejemplo las coordenadas GPS."></i></th>
                                <th>Pais <i class="icon-question-sign helpover" data-title="Pais: Atributo requerido que sirve para precisar el país donde  se encuentra ubicado el destinatario de la mercancía."></i></th>
                                <th>Estado <i class="icon-question-sign helpover" data-title="Estado: Atributo requerido para señalar el estado, entidad, región, comunidad u otra figura análoga en donde  se encuentra ubicado el  domicilio del destinatario de la mercancía."></i></th>
                                <th>Municipio <i class="icon-question-sign helpover" data-title="Municipio: Atributo opcional que sirve para precisar el municipio, delegación, condado u otro análogo en donde se encuentra ubicado el destinatario de la mercancía."></i></th>
                                <th>Localidad <i class="icon-question-sign helpover" data-title="Localidad: Atributo opcional que sirve para precisar la ciudad, población, distrito u otro análogo en donde se ubica el domicilio del  destinatario de la mercancía."></i></th>
                                <th>Codigo Postal <i class="icon-question-sign helpover" data-title="Codigo Postal: Atributo requerido que sirve para asentar el código postal (PO, BOX) en donde se encuentra ubicado el domicilio del destinatario de la mercancía."></i></th>
                                <th>Colonia <i class="icon-question-sign helpover" data-title="Colonia: Atributo opcional sirve para expresar la colonia o dato análogo en donde se ubica el domicilio del destinatario de la mercancía."></i></th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][domicilio][calle]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][domicilio][calle]', isset($ubic->domicilio->calle) ? $ubic->domicilio->calle : ''); ?>" id="cp_ubic_dom_calle" minlength="1" maxlength="100" class="span12 sikey" data-next="cp_ubic_dom_numeroExterior"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][domicilio][numeroExterior]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][domicilio][numeroExterior]', isset($ubic->domicilio->numeroExterior) ? $ubic->domicilio->numeroExterior : ''); ?>" id="cp_ubic_dom_numeroExterior" minlength="1" maxlength="55" class="span12 sikey" data-next="cp_ubic_dom_numeroInterior"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][domicilio][numeroInterior]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][domicilio][numeroInterior]', isset($ubic->domicilio->numeroInterior) ? $ubic->domicilio->numeroInterior : ''); ?>" id="cp_ubic_dom_numeroInterior" minlength="1" maxlength="55" class="span12 sikey" data-next="cp_ubic_dom_referencia"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][domicilio][referencia]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][domicilio][referencia]', isset($ubic->domicilio->referencia) ? $ubic->domicilio->referencia : ''); ?>" id="cp_ubic_dom_referencia" minlength="1" maxlength="250" class="span12 sikey" data-next="cp_ubic_dom_pais"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][domicilio][pais]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][domicilio][pais]', isset($ubic->domicilio->pais) ? $ubic->domicilio->pais : ''); ?>" id="cp_ubic_dom_pais" maxlength="40" class="span12 sikey" data-next="cp_ubic_dom_estado"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][domicilio][estado]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][domicilio][estado]', isset($ubic->domicilio->estado) ? $ubic->domicilio->estado : ''); ?>" id="cp_ubic_dom_estado" maxlength="60" class="span12 sikey" data-next="cp_ubic_dom_municipio"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][domicilio][municipio]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][domicilio][municipio]', isset($ubic->domicilio->municipio) ? $ubic->domicilio->municipio : ''); ?>" id="cp_ubic_dom_municipio" minlength="1" maxlength="120" class="span12 sikey" data-next="cp_ubic_dom_localidad"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][domicilio][localidad]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][domicilio][localidad]', isset($ubic->domicilio->localidad) ? $ubic->domicilio->localidad : ''); ?>" id="cp_ubic_dom_localidad" minlength="1" maxlength="12" class="span12 sikey" data-next="cp_ubic_dom_codigopostal"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][domicilio][codigoPostal]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][domicilio][codigoPostal]', isset($ubic->domicilio->codigoPostal) ? $ubic->domicilio->codigoPostal : ''); ?>" class="span12 sikey" id="cp_ubic_dom_codigopostal" minlength="1" maxlength="12" data-next="cp_ubic_dom_colonia"></td>
                                <td class="center"><input type="text" name="cp[ubicaciones][<?php echo $count ?>][domicilio][colonia]" value="<?php echo set_value('cp[ubicaciones]['.$count.'][domicilio][colonia]', isset($ubic->domicilio->colonia) ? $ubic->domicilio->colonia : ''); ?>" id="cp_ubic_dom_colonia" minlength="1" maxlength="120" class="span12 sikey" data-next="cp_ubic_dom_calle"></td>
                              </tr>
                            </tbody>
                          </table>

                          <hr class="div">
                        </div>
                        <?php $count++; ?>
                        <?php endforeach ?>
                        <?php endif ?>

                      </div>
                    </div>
                  </div>
                  <!-- !--/Ubicaciones -->

                  <!-- Mercancias -->
                  <div class="row-fluid">
                    <div class="box span12">
                      <div class="box-header well">
                        <h2><i class="icon-align-justify"></i><span class="break"></span>Mercancías</h2>
                        <div class="box-icon">
                          <a href="#modal-cpsat-mercancia" class="btn-add" id="btn-add-CpMercancias" data-rel="tooltip" data-title="Agregar Mercancia" data-toggle="modal"><i class="icon-plus"></i></a>
                        </div>
                      </div>
                      <div class="box-content" style="padding: 0;" id="boxMercancias">
                        <div class="span4">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_pesoBrutoTotal">Peso Bruto Total <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="number" name="cp[mercancias][pesoBrutoTotal]" value="<?php echo set_value('cp[mercancias][pesoBrutoTotal]', isset($cpobj->mercancias->pesoBrutoTotal) ? $cpobj->mercancias->pesoBrutoTotal : ''); ?>" id="cp_mercancias_pesoBrutoTotal" class="span12 sikey" data-next="cp_mercancias_unidadPeso_text">
                            </div>
                          </div>
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_unidadPeso_text">Unidad Peso </label>
                            <div class="controls">
                              <input type="text" name="cp[mercancias][unidadPeso_text]" value="<?php echo set_value('cp[mercancias][unidadPeso_text]', isset($cpobj->mercancias->unidadPeso_text) ? $cpobj->mercancias->unidadPeso_text : ''); ?>" id="cp_mercancias_unidadPeso_text" maxlength="40" class="span12 sikey" data-next="cp_mercancias_pesoNetoTotal">
                              <input type="hidden" name="cp[mercancias][unidadPeso]" value="<?php echo set_value('cp[mercancias][unidadPeso]', isset($cpobj->mercancias->unidadPeso) ? $cpobj->mercancias->unidadPeso : ''); ?>" id="cp_mercancias_unidadPeso" maxlength="40" class="span12 sikey">
                            </div>
                          </div>
                        </div>

                        <div class="span4">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_pesoNetoTotal">Peso Neto Total <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="number" name="cp[mercancias][pesoNetoTotal]" value="<?php echo set_value('cp[mercancias][pesoNetoTotal]', isset($cpobj->mercancias->pesoNetoTotal) ? $cpobj->mercancias->pesoNetoTotal : ''); ?>" id="cp_mercancias_pesoNetoTotal" class="span12 sikey" data-next="cp_mercancias_numTotalMercancias">
                            </div>
                          </div>
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_numTotalMercancias">Num Total Mercancias <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="number" name="cp[mercancias][numTotalMercancias]" value="<?php echo set_value('cp[mercancias][numTotalMercancias]', isset($cpobj->mercancias->numTotalMercancias) ? $cpobj->mercancias->numTotalMercancias : ''); ?>" id="cp_mercancias_numTotalMercancias" class="span12 sikey" data-next="cp_mercancias_cargoPorTasacion">
                            </div>
                          </div>
                        </div>

                        <div class="span4">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_cargoPorTasacion">Cargo Por Tasacion <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="number" name="cp[mercancias][cargoPorTasacion]" value="<?php echo set_value('cp[mercancias][cargoPorTasacion]', isset($cpobj->mercancias->cargoPorTasacion) ? $cpobj->mercancias->cargoPorTasacion : ''); ?>" id="cp_mercancias_cargoPorTasacion" class="span12 sikey">
                            </div>
                          </div>
                        </div>

                        <table class="table table-hover table-condensed" id="table-mercanciass">
                          <thead>
                            <tr>
                              <th>BIENES</th>
                              <th>DESCRIPCIÓN</th>
                              <th>CANTIDAD</th>
                              <th>CLAVE UNIDAD</th>
                              <th>PESO EN KG</th>
                              <th>Opc</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (isset($cpobj->mercancias->mercancias) && count($cpobj->mercancias->mercancias) > 0): ?>
                            <?php $count = 0; ?>
                            <?php foreach ($cpobj->mercancias->mercancias as $key => $merca): ?>
                            <tr class="cp-mercans" id="cp-mercanss<?php echo $count ?>">
                              <td>
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][bienesTransp]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][bienesTransp]', isset($merca->bienesTransp) ? $merca->bienesTransp : ''); ?>" class="cpMercans-bienesTransp">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][bienesTransp_text]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][bienesTransp_text]', isset($merca->bienesTransp_text) ? $merca->bienesTransp_text : ''); ?>" class="cpMercans-bienesTransp_text">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][claveSTCC]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][claveSTCC]', isset($merca->claveSTCC) ? $merca->claveSTCC : ''); ?>" class="cpMercans-claveSTCC">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][claveSTCC_text]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][claveSTCC_text]', isset($merca->claveSTCC_text) ? $merca->claveSTCC_text : ''); ?>" class="cpMercans-claveSTCC_text">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][descripcion]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][descripcion]', isset($merca->descripcion) ? $merca->descripcion : ''); ?>" class="cpMercans-descripcion">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][cantidad]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][cantidad]', isset($merca->cantidad) ? $merca->cantidad : ''); ?>" class="cpMercans-cantidad">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][claveUnidad]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][claveUnidad]', isset($merca->claveUnidad) ? $merca->claveUnidad : ''); ?>" class="cpMercans-claveUnidad">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][claveUnidad_text]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][claveUnidad_text]', isset($merca->claveUnidad_text) ? $merca->claveUnidad_text : ''); ?>" class="cpMercans-claveUnidad_text">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][unidad]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][unidad]', isset($merca->unidad) ? $merca->unidad : ''); ?>" class="cpMercans-unidad">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][dimensiones]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][dimensiones]', isset($merca->dimensiones) ? $merca->dimensiones : ''); ?>" class="cpMercans-dimensiones">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][materialPeligroso]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][materialPeligroso]', isset($merca->materialPeligroso) ? $merca->materialPeligroso : ''); ?>" class="cpMercans-materialPeligroso">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][cveMaterialPeligroso]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][cveMaterialPeligroso]', isset($merca->cveMaterialPeligroso) ? $merca->cveMaterialPeligroso : ''); ?>" class="cpMercans-cveMaterialPeligroso">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][cveMaterialPeligroso_text]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][cveMaterialPeligroso_text]', isset($merca->cveMaterialPeligroso_text) ? $merca->cveMaterialPeligroso_text : ''); ?>" class="cpMercans-cveMaterialPeligroso_text">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][embalaje]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][embalaje]', isset($merca->embalaje) ? $merca->embalaje : ''); ?>" class="cpMercans-embalaje">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][descripEmbalaje]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][descripEmbalaje]', isset($merca->descripEmbalaje) ? $merca->descripEmbalaje : ''); ?>" class="cpMercans-descripEmbalaje">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][pesoEnKg]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][pesoEnKg]', isset($merca->pesoEnKg) ? $merca->pesoEnKg : ''); ?>" class="cpMercans-pesoEnKg">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][valorMercancia]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][valorMercancia]', isset($merca->valorMercancia) ? $merca->valorMercancia : ''); ?>" class="cpMercans-valorMercancia">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][moneda]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][moneda]', isset($merca->moneda) ? $merca->moneda : ''); ?>" class="cpMercans-moneda">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][fraccionArancelaria]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][fraccionArancelaria]', isset($merca->fraccionArancelaria) ? $merca->fraccionArancelaria : ''); ?>" class="cpMercans-fraccionArancelaria">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][fraccionArancelaria_text]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][fraccionArancelaria_text]', isset($merca->fraccionArancelaria_text) ? $merca->fraccionArancelaria_text : ''); ?>" class="cpMercans-fraccionArancelaria_text">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][uuidComercioExt]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][uuidComercioExt]', isset($merca->uuidComercioExt) ? $merca->uuidComercioExt : ''); ?>" class="cpMercans-uuidComercioExt">

                                <?php if (isset($merca->pedimentos)): ?>
                                  <?php foreach ($merca->pedimentos as $key2 => $pedimento): ?>
                                  <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][pedimentos][<?php echo $key2 ?>][pedimento]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][pedimentos]['.$key2.'][pedimento]', isset($pedimento->pedimento) ? $pedimento->pedimento : ''); ?>" class="cpMercans-pedimentos-pedimento">
                                  <?php endforeach ?>
                                <?php endif ?>

                                <?php if (isset($merca->guiasIdentificacion)): ?>
                                  <?php foreach ($merca->guiasIdentificacion as $key2 => $guia): ?>
                                  <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][guiasIdentificacion][<?php echo $key2 ?>][numeroGuiaIdentificacion]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][guiasIdentificacion]['.$key2.'][numeroGuiaIdentificacion]', isset($guia->numeroGuiaIdentificacion) ? $guia->numeroGuiaIdentificacion : ''); ?>" class="cpMercans-guia-numeroGuiaIdentificacion">
                                  <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][guiasIdentificacion][<?php echo $key2 ?>][descripGuiaIdentificacion]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][guiasIdentificacion]['.$key2.'][descripGuiaIdentificacion]', isset($guia->descripGuiaIdentificacion) ? $guia->descripGuiaIdentificacion : ''); ?>" class="cpMercans-guia-descripGuiaIdentificacion">
                                  <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][guiasIdentificacion][<?php echo $key2 ?>][pesoGuiaIdentificacion]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][guiasIdentificacion]['.$key2.'][pesoGuiaIdentificacion]', isset($guia->pesoGuiaIdentificacion) ? $guia->pesoGuiaIdentificacion : ''); ?>" class="cpMercans-guia-pesoGuiaIdentificacion">
                                  <?php endforeach ?>
                                <?php endif ?>

                                <?php if (isset($merca->cantidadTransporta)): ?>
                                  <?php foreach ($merca->cantidadTransporta as $key2 => $ctrans): ?>
                                  <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][cantidadTransporta][<?php echo $key2 ?>][cantidad]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][cantidadTransporta]['.$key2.'][cantidad]', isset($ctrans->cantidad) ? $ctrans->cantidad : ''); ?>" class="cpMercans-cantTrans-cantidad">
                                  <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][cantidadTransporta][<?php echo $key2 ?>][idOrigen]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][cantidadTransporta]['.$key2.'][idOrigen]', isset($ctrans->idOrigen) ? $ctrans->idOrigen : ''); ?>" class="cpMercans-cantTrans-idOrigen">
                                  <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][cantidadTransporta][<?php echo $key2 ?>][idDestino]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][cantidadTransporta]['.$key2.'][idDestino]', isset($ctrans->idDestino) ? $ctrans->idDestino : ''); ?>" class="cpMercans-cantTrans-idDestino">
                                  <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][cantidadTransporta][<?php echo $key2 ?>][cvesTransporte]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][cantidadTransporta]['.$key2.'][cvesTransporte]', isset($ctrans->cvesTransporte) ? $ctrans->cvesTransporte : ''); ?>" class="cpMercans-cantTrans-cvesTransporte">
                                  <?php endforeach ?>
                                <?php endif ?>

                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][detalleMercancia][unidadPeso]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][detalleMercancia][unidadPeso]', isset($merca->detalleMercancia->unidadPeso) ? $merca->detalleMercancia->unidadPeso : ''); ?>" class="cpMercans-detaMerca-unidadPeso">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][detalleMercancia][unidadPeso_text]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][detalleMercancia][unidadPeso_text]', isset($merca->detalleMercancia->unidadPeso_text) ? $merca->detalleMercancia->unidadPeso_text : ''); ?>" class="cpMercans-detaMerca-unidadPeso_text">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][detalleMercancia][pesoBruto]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][detalleMercancia][pesoBruto]', isset($merca->detalleMercancia->pesoBruto) ? $merca->detalleMercancia->pesoBruto : ''); ?>" class="cpMercans-detaMerca-pesoBruto">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][detalleMercancia][pesoNeto]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][detalleMercancia][pesoNeto]', isset($merca->detalleMercancia->pesoNeto) ? $merca->detalleMercancia->pesoNeto : ''); ?>" class="cpMercans-detaMerca-pesoNeto">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][detalleMercancia][pesoTara]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][detalleMercancia][pesoTara]', isset($merca->detalleMercancia->pesoTara) ? $merca->detalleMercancia->pesoTara : ''); ?>" class="cpMercans-detaMerca-pesoTara">
                                <input type="hidden" name="cp[mercancias][mercancias][<?php echo $count ?>][detalleMercancia][numPiezas]" value="<?php echo set_value('cp[mercancias][mercancias]['.$count.'][detalleMercancia][numPiezas]', isset($merca->detalleMercancia->numPiezas) ? $merca->detalleMercancia->numPiezas : ''); ?>" class="cpMercans-detaMerca-numPiezas">

                                <?php echo (isset($merca->bienesTransp) ? $merca->bienesTransp : '') ?>
                              </td>
                              <td><?php echo (isset($merca->descripcion) ? $merca->descripcion : '') ?></td>
                              <td><?php echo (isset($merca->cantidad) ? $merca->cantidad : '') ?></td>
                              <td><?php echo (isset($merca->claveUnidad_text) ? $merca->claveUnidad_text : '') ?></td>
                              <td><?php echo (isset($merca->pesoEnKg) ? $merca->pesoEnKg : '') ?></td>
                              <td style="width: 20px;">
                                <?php
                                  $merca->datos = clone $merca;
                                  $jsonMerca = MyString::encodeURIComponent(json_encode($merca));
                                ?>
                                <button type="button" class="btn btn-cp-editMercancia" data-json="<?php echo $jsonMerca ?>">Editar</button>
                                <button type="button" class="btn btn-danger btn-cp-removeMercancia">Quitar</button>
                              </td>
                            </tr>
                            <?php $count++; ?>
                            <?php endforeach ?>
                            <?php endif ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  <!-- !--/Mercancias -->

                  <!-- Autotransporte -->
                  <div class="row-fluid">
                    <div class="box span12">
                      <div class="box-header well">
                        <h2><i class="icon-align-justify"></i><span class="break"></span>Autotransporte</h2>
                        <div class="box-icon">
                        </div>
                      </div>
                      <div class="box-content" style="padding: 0;" id="boxAutotransporte">
                        <div class="span6">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_permSCT">Permiso SCT <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="hidden" name="cp[mercancias][autotransporte][show]" value="true" id="cp_mercancias_autotransporte_show">
                              <?php $permSCT = isset($cpobj->mercancias->autotransporte->permSCT) ? $cpobj->mercancias->autotransporte->permSCT : ''; ?>
                              <select name="cp[mercancias][autotransporte][permSCT]" class="span12 sikey" id="cp_mercancias_autotransporte_permSCT" data-next="cp_mercancias_autotransporte_numPermisoSCT">
                                <option value="" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', '', $permSCT === '' ? true : false); ?>></option>
                                <option value="TPAF01" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF01', $permSCT === 'TPAF01' ? true : false, $permSCT); ?>>TPAF01 - Autotransporte Federal de carga general.</option>
                                <option value="TPAF02" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF02', $permSCT === 'TPAF02' ? true : false, $permSCT); ?>>TPAF02 - Transporte privado de carga.</option>
                                <option value="TPAF03" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF03', $permSCT === 'TPAF03' ? true : false, $permSCT); ?>>TPAF03 - Autotransporte Federal de Carga Especializada de materiales y residuos peligrosos.</option>
                                <option value="TPAF04" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF04', $permSCT === 'TPAF04' ? true : false, $permSCT); ?>>TPAF04 - Transporte de automóviles sin rodar en vehículo tipo góndola.</option>
                                <option value="TPAF05" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF05', $permSCT === 'TPAF05' ? true : false, $permSCT); ?>>TPAF05 - Transporte de carga de gran peso y/o volumen de hasta 90 toneladas.</option>
                                <option value="TPAF06" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF06', $permSCT === 'TPAF06' ? true : false, $permSCT); ?>>TPAF06 - Transporte de carga especializada de gran peso y/o volumen de más 90 toneladas.</option>
                                <option value="TPAF07" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF07', $permSCT === 'TPAF07' ? true : false, $permSCT); ?>>TPAF07 - Transporte Privado de materiales y residuos peligrosos.</option>
                                <option value="TPAF08" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF08', $permSCT === 'TPAF08' ? true : false, $permSCT); ?>>TPAF08 - Autotransporte internacional de carga de largo recorrido.</option>
                                <option value="TPAF09" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF09', $permSCT === 'TPAF09' ? true : false, $permSCT); ?>>TPAF09 - Autotransporte internacional de carga especializada de materiales y residuos peligrosos de largo recorrido.</option>
                                <option value="TPAF10" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF10', $permSCT === 'TPAF10' ? true : false, $permSCT); ?>>TPAF10 - Autotransporte Federal de Carga General cuyo ámbito de aplicación comprende la franja fronteriza con Estados Unidos.</option>
                                <option value="TPAF11" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF11', $permSCT === 'TPAF11' ? true : false, $permSCT); ?>>TPAF11 - Autotransporte Federal de Carga Especializada cuyo ámbito de aplicación comprende la franja fronteriza con Estados Unidos.</option>
                                <option value="TPAF12" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF12', $permSCT === 'TPAF12' ? true : false, $permSCT); ?>>TPAF12 - Servicio auxiliar de arrastre en las vías generales de comunicación.</option>
                                <option value="TPAF13" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF13', $permSCT === 'TPAF13' ? true : false, $permSCT); ?>>TPAF13 - Servicio auxiliar de servicios de arrastre, arrastre y salvamento, y depósito de vehículos en las vías generales de comunicación.</option>
                                <option value="TPAF14" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF14', $permSCT === 'TPAF14' ? true : false, $permSCT); ?>>TPAF14 - Servicio de paquetería y mensajería en las vías generales de comunicación.</option>
                                <option value="TPAF15" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF15', $permSCT === 'TPAF15' ? true : false, $permSCT); ?>>TPAF15 - Transporte especial para el tránsito de grúas industriales con peso máximo de 90 toneladas.</option>
                                <option value="TPAF16" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF16', $permSCT === 'TPAF16' ? true : false, $permSCT); ?>>TPAF16 - Servicio federal para empresas arrendadoras servicio público federal.</option>
                                <option value="TPAF17" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF17', $permSCT === 'TPAF17' ? true : false, $permSCT); ?>>TPAF17 - Empresas trasladistas de vehículos nuevos.</option>
                                <option value="TPAF18" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF18', $permSCT === 'TPAF18' ? true : false, $permSCT); ?>>TPAF18 - Empresas fabricantes o distribuidoras de vehículos nuevos.</option>
                                <option value="TPAF19" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPAF19', $permSCT === 'TPAF19' ? true : false, $permSCT); ?>>TPAF19 - Autorización expresa para circular en los caminos y puentes de jurisdicción federal con configuraciones de tractocamión doblemente articulado.</option>
                                <option value="TPTM01" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPTM01', $permSCT === 'TPTM01' ? true : false, $permSCT); ?>>TPTM01 - Permiso temporal para navegación de cabotaje</option>
                                <option value="TPTA01" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPTA01', $permSCT === 'TPTA01' ? true : false, $permSCT); ?>>TPTA01 - Concesión y/o autorización para el servicio regular nacional y/o internacional para empresas mexicanas</option>
                                <option value="TPTA02" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPTA02', $permSCT === 'TPTA02' ? true : false, $permSCT); ?>>TPTA02 - Permiso para el servicio aéreo regular de empresas extranjeras</option>
                                <option value="TPTA03" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPTA03', $permSCT === 'TPTA03' ? true : false, $permSCT); ?>>TPTA03 - Permiso para el servicio nacional e internacional no regular de fletamento</option>
                                <option value="TPTA04" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPTA04', $permSCT === 'TPTA04' ? true : false, $permSCT); ?>>TPTA04 - Permiso para el servicio nacional e internacional no regular de taxi aéreo</option>
                                <option value="TPXX00" <?php echo set_select('cp[mercancias][autotransporte][permSCT]', 'TPXX00', $permSCT === 'TPXX00' ? true : false, $permSCT); ?>>TPXX00</option>
                              </select>
                            </div>
                          </div>
                        </div>
                        <div class="span6">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_numPermisoSCT">Num del Permiso SCT </label>
                            <div class="controls">
                              <input type="text" name="cp[mercancias][autotransporte][numPermisoSCT]"
                                value="<?php echo set_value('cp[mercancias][autotransporte][numPermisoSCT]', isset($cpobj->mercancias->autotransporte->numPermisoSCT) ? $cpobj->mercancias->autotransporte->numPermisoSCT : ''); ?>"
                                id="cp_mercancias_autotransporte_numPermisoSCT" maxlength="50" class="span12 sikey" data-next="cp_mercancias_autotransporte_ident_configVehicular">
                            </div>
                          </div>
                        </div>

                        <h4>Identificacion Vehicular</h4>

                        <div class="span4">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_ident_configVehicular">Configuración Vehicular <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <?php $configVehicular = isset($cpobj->mercancias->autotransporte->identificacionVehicular->configVehicular) ? $cpobj->mercancias->autotransporte->identificacionVehicular->configVehicular : ''; ?>
                              <select name="cp[mercancias][autotransporte][identificacionVehicular][configVehicular]" class="span12 sikey" id="cp_mercancias_autotransporte_ident_configVehicular" data-next="cp_mercancias_autotransporte_ident_placaVM">
                                <option value="" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', '', $configVehicular === '' ? true : false, $configVehicular); ?>></option>
                                <option value="C2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'C2', $configVehicular === 'C2' ? true : false, $configVehicular); ?>>C2</option>
                                <option value="C3" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'C3', $configVehicular === 'C3' ? true : false, $configVehicular); ?>>C3</option>
                                <option value="C2R2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'C2R2', $configVehicular === 'C2R2' ? true : false, $configVehicular); ?>>C2R2</option>
                                <option value="C3R2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'C3R2', $configVehicular === 'C3R2' ? true : false, $configVehicular); ?>>C3R2</option>
                                <option value="C2R3" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'C2R3', $configVehicular === 'C2R3' ? true : false, $configVehicular); ?>>C2R3</option>
                                <option value="C3R3" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'C3R3', $configVehicular === 'C3R3' ? true : false, $configVehicular); ?>>C3R3</option>
                                <option value="T2S1" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T2S1', $configVehicular === 'T2S1' ? true : false, $configVehicular); ?>>T2S1</option>
                                <option value="T2S2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T2S2', $configVehicular === 'T2S2' ? true : false, $configVehicular); ?>>T2S2</option>
                                <option value="T2S3" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T2S3', $configVehicular === 'T2S3' ? true : false, $configVehicular); ?>>T2S3</option>
                                <option value="T3S1" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T3S1', $configVehicular === 'T3S1' ? true : false, $configVehicular); ?>>T3S1</option>
                                <option value="T3S2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T3S2', $configVehicular === 'T3S2' ? true : false, $configVehicular); ?>>T3S2</option>
                                <option value="T3S3" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T3S3', $configVehicular === 'T3S3' ? true : false, $configVehicular); ?>>T3S3</option>
                                <option value="T2S1R2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T2S1R2', $configVehicular === 'T2S1R2' ? true : false, $configVehicular); ?>>T2S1R2</option>
                                <option value="T2S2R2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T2S2R2', $configVehicular === 'T2S2R2' ? true : false, $configVehicular); ?>>T2S2R2</option>
                                <option value="T2S1R3" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T2S1R3', $configVehicular === 'T2S1R3' ? true : false, $configVehicular); ?>>T2S1R3</option>
                                <option value="T3S1R2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T3S1R2', $configVehicular === 'T3S1R2' ? true : false, $configVehicular); ?>>T3S1R2</option>
                                <option value="T3S1R3" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T3S1R3', $configVehicular === 'T3S1R3' ? true : false, $configVehicular); ?>>T3S1R3</option>
                                <option value="T3S2R2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T3S2R2', $configVehicular === 'T3S2R2' ? true : false, $configVehicular); ?>>T3S2R2</option>
                                <option value="T3S2R3" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T3S2R3', $configVehicular === 'T3S2R3' ? true : false, $configVehicular); ?>>T3S2R3</option>
                                <option value="T3S2R4" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T3S2R4', $configVehicular === 'T3S2R4' ? true : false, $configVehicular); ?>>T3S2R4</option>
                                <option value="T2S2S2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T2S2S2', $configVehicular === 'T2S2S2' ? true : false, $configVehicular); ?>>T2S2S2</option>
                                <option value="T3S2S2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T3S2S2', $configVehicular === 'T3S2S2' ? true : false, $configVehicular); ?>>T3S2S2</option>
                                <option value="T3S3S2" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'T3S3S2', $configVehicular === 'T3S3S2' ? true : false, $configVehicular); ?>>T3S3S2</option>
                                <option value="OTROEV" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'OTROEV', $configVehicular === 'OTROEV' ? true : false, $configVehicular); ?>>OTROEV</option>
                                <option value="OTROEGP" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'OTROEGP', $configVehicular === 'OTROEGP' ? true : false, $configVehicular); ?>>OTROEGP</option>
                                <option value="OTROSG" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'OTROSG', $configVehicular === 'OTROSG' ? true : false, $configVehicular); ?>>OTROSG</option>
                                <option value="VL" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'VL', $configVehicular === 'VL' ? true : false, $configVehicular); ?>>VL</option>
                                <option value="OTROEVGP" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'OTROEVGP', $configVehicular === 'OTROEVGP' ? true : false, $configVehicular); ?>>OTROEVGP</option>
                                <option value="GPLUTA" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'GPLUTA', $configVehicular === 'GPLUTA' ? true : false, $configVehicular); ?>>GPLUTA</option>
                                <option value="GPLUTB" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'GPLUTB', $configVehicular === 'GPLUTB' ? true : false, $configVehicular); ?>>GPLUTB</option>
                                <option value="GPLUTC" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'GPLUTC', $configVehicular === 'GPLUTC' ? true : false, $configVehicular); ?>>GPLUTC</option>
                                <option value="GPLUTD" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'GPLUTD', $configVehicular === 'GPLUTD' ? true : false, $configVehicular); ?>>GPLUTD</option>
                                <option value="GPLATA" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'GPLATA', $configVehicular === 'GPLATA' ? true : false, $configVehicular); ?>>GPLATA</option>
                                <option value="GPLATB" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'GPLATB', $configVehicular === 'GPLATB' ? true : false, $configVehicular); ?>>GPLATB</option>
                                <option value="GPLATC" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'GPLATC', $configVehicular === 'GPLATC' ? true : false, $configVehicular); ?>>GPLATC</option>
                                <option value="GPLATD" <?php echo set_select('cp[mercancias][autotransporte][identificacionVehicular][configVehicular]', 'GPLATD', $configVehicular === 'GPLATD' ? true : false, $configVehicular); ?>>GPLATD</option>
                              </select>
                            </div>
                          </div>
                        </div>

                        <div class="span4">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_ident_placaVM">Placa vehicular del autotransporte <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="text" name="cp[mercancias][autotransporte][identificacionVehicular][placaVM]"
                                value="<?php echo set_value('cp[mercancias][autotransporte][identificacionVehicular][placaVM]', isset($cpobj->mercancias->autotransporte->identificacionVehicular->placaVM) ? $cpobj->mercancias->autotransporte->identificacionVehicular->placaVM : ''); ?>"
                                id="cp_mercancias_autotransporte_ident_placaVM" class="span12 sikey" data-next="cp_mercancias_autotransporte_ident_anioModeloVM">
                            </div>
                          </div>
                        </div>

                        <div class="span3">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_ident_anioModeloVM">Año del autotransporte <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="text" name="cp[mercancias][autotransporte][identificacionVehicular][anioModeloVM]"
                                value="<?php echo set_value('cp[mercancias][autotransporte][identificacionVehicular][anioModeloVM]', isset($cpobj->mercancias->autotransporte->identificacionVehicular->anioModeloVM) ? $cpobj->mercancias->autotransporte->identificacionVehicular->anioModeloVM : ''); ?>"
                                id="cp_mercancias_autotransporte_ident_anioModeloVM" class="span12 sikey" data-next="cp_mercancias_autotransporte_seguro_aseguraRespCivil">
                            </div>
                          </div>
                        </div>

                        <h4 style="clear: both;">Seguros</h4>

                        <div class="span4">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_seguro_aseguraRespCivil">Nombre Aseguradora Civil <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="text" name="cp[mercancias][autotransporte][seguros][aseguraRespCivil]"
                                value="<?php echo set_value('cp[mercancias][autotransporte][seguros][aseguraRespCivil]', isset($cpobj->mercancias->autotransporte->seguros->aseguraRespCivil) ? $cpobj->mercancias->autotransporte->seguros->aseguraRespCivil : ''); ?>"
                                id="cp_mercancias_autotransporte_seguro_aseguraRespCivil" class="span12 sikey" data-next="cp_mercancias_autotransporte_seguro_polizaRespCivil">
                            </div>
                          </div>
                        </div>

                        <div class="span4">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_seguro_polizaRespCivil">No Poliza Civil <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="text" name="cp[mercancias][autotransporte][seguros][polizaRespCivil]"
                                value="<?php echo set_value('cp[mercancias][autotransporte][seguros][polizaRespCivil]', isset($cpobj->mercancias->autotransporte->seguros->polizaRespCivil) ? $cpobj->mercancias->autotransporte->seguros->polizaRespCivil : ''); ?>"
                                id="cp_mercancias_autotransporte_seguro_polizaRespCivil" class="span12 sikey" data-next="cp_mercancias_autotransporte_seguro_aseguraMedAmbiente">
                            </div>
                          </div>
                        </div>

                        <div class="span4" style="clear: both;">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_seguro_aseguraMedAmbiente">Nombre Aseguradora Med Ambiente <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="text" name="cp[mercancias][autotransporte][seguros][aseguraMedAmbiente]"
                                value="<?php echo set_value('cp[mercancias][autotransporte][seguros][aseguraMedAmbiente]', isset($cpobj->mercancias->autotransporte->seguros->aseguraMedAmbiente) ? $cpobj->mercancias->autotransporte->seguros->aseguraMedAmbiente : ''); ?>"
                                id="cp_mercancias_autotransporte_seguro_aseguraMedAmbiente" class="span12 sikey" data-next="cp_mercancias_autotransporte_seguro_polizaMedAmbiente">
                            </div>
                          </div>
                        </div>

                        <div class="span4">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_seguro_polizaMedAmbiente">No Poliza Med Ambiente <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="text" name="cp[mercancias][autotransporte][seguros][polizaMedAmbiente]"
                                value="<?php echo set_value('cp[mercancias][autotransporte][seguros][polizaMedAmbiente]', isset($cpobj->mercancias->autotransporte->seguros->polizaMedAmbiente) ? $cpobj->mercancias->autotransporte->seguros->polizaMedAmbiente : ''); ?>"
                                id="cp_mercancias_autotransporte_seguro_polizaMedAmbiente" class="span12 sikey" data-next="cp_mercancias_autotransporte_seguro_aseguraCarga">
                            </div>
                          </div>
                        </div>

                        <div class="span4" style="clear: both;">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_seguro_aseguraCarga">Nombre Aseguradora Carga <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="text" name="cp[mercancias][autotransporte][seguros][aseguraCarga]"
                                value="<?php echo set_value('cp[mercancias][autotransporte][seguros][aseguraCarga]', isset($cpobj->mercancias->autotransporte->seguros->aseguraCarga) ? $cpobj->mercancias->autotransporte->seguros->aseguraCarga : ''); ?>"
                                id="cp_mercancias_autotransporte_seguro_aseguraCarga" class="span12 sikey" data-next="cp_mercancias_autotransporte_seguro_polizaCarga">
                            </div>
                          </div>
                        </div>

                        <div class="span4">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_seguro_polizaCarga">No Poliza Carga <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="text" name="cp[mercancias][autotransporte][seguros][polizaCarga]"
                                value="<?php echo set_value('cp[mercancias][autotransporte][seguros][polizaCarga]', isset($cpobj->mercancias->autotransporte->seguros->polizaCarga) ? $cpobj->mercancias->autotransporte->seguros->polizaCarga : ''); ?>"
                                id="cp_mercancias_autotransporte_seguro_polizaCarga" class="span12 sikey" data-next="cp_mercancias_autotransporte_seguro_primaSeguro">
                            </div>
                          </div>
                        </div>

                        <div class="span4" style="clear: both;">
                          <div class="control-group">
                            <label class="control-label" for="cp_mercancias_autotransporte_seguro_primaSeguro">Prima Seguro <i class="icon-question-sign helpover" data-title=""></i></label>
                            <div class="controls">
                              <input type="text" name="cp[mercancias][autotransporte][seguros][primaSeguro]"
                                value="<?php echo set_value('cp[mercancias][autotransporte][seguros][primaSeguro]', isset($cpobj->mercancias->autotransporte->seguros->primaSeguro) ? $cpobj->mercancias->autotransporte->seguros->primaSeguro : ''); ?>"
                                id="cp_mercancias_autotransporte_seguro_primaSeguro" class="span12 sikey" data-next="cp_mercancias_autotransporte_ident_anioModeloVM">
                            </div>
                          </div>
                        </div>

                        <div style="clear: both;"></div>
                        <h4 style="clear: both;">Remolques</h4>
                        <a class="btn-add pull-right" id="btn-add-CpRemolques" data-title="Agregar Remolque" style="cursor: pointer;font-size: 1.1em; margin-right: 10px;"><i class="icon-plus"></i></a>
                        <table class="table table-hover table-condensed" id="table-remolequess">
                          <thead>
                            <tr>
                              <th>TIPO</th>
                              <th>PLACA</th>
                              <th>Opc</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (isset($cpobj->mercancias->autotransporte->remolques) && count($cpobj->mercancias->autotransporte->remolques) > 0): ?>
                            <?php foreach ($cpobj->mercancias->autotransporte->remolques as $key2 => $rem): ?>
                            <tr class="cp-mercans-remolques" data-row="<?php echo $key2 ?>">
                              <td>
                                <?php $subTipoRem = isset($rem->subTipoRem) ? $rem->subTipoRem : ''; ?>
                                <select name="cp[mercancias][autotransporte][remolques][<?php echo $key2 ?>][subTipoRem]" class="cpMercans-autotrans_rem_subTipoRem">
                                  <option value="CTR001" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR001', $subTipoRem === 'CTR001' ? true : false, $subTipoRem); ?>>CTR001 - Caballete</option>
                                  <option value="CTR002" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR002', $subTipoRem === 'CTR002' ? true : false, $subTipoRem); ?>>CTR002 - Caja</option>
                                  <option value="CTR003" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR003', $subTipoRem === 'CTR003' ? true : false, $subTipoRem); ?>>CTR003 - Caja Abierta</option>
                                  <option value="CTR004" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR004', $subTipoRem === 'CTR004' ? true : false, $subTipoRem); ?>>CTR004 - Caja Cerrada</option>
                                  <option value="CTR005" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR005', $subTipoRem === 'CTR005' ? true : false, $subTipoRem); ?>>CTR005 - Caja De Recolección Con Cargador Frontal</option>
                                  <option value="CTR006" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR006', $subTipoRem === 'CTR006' ? true : false, $subTipoRem); ?>>CTR006 - Caja Refrigerada</option>
                                  <option value="CTR007" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR007', $subTipoRem === 'CTR007' ? true : false, $subTipoRem); ?>>CTR007 - Caja Seca</option>
                                  <option value="CTR008" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR008', $subTipoRem === 'CTR008' ? true : false, $subTipoRem); ?>>CTR008 - Caja Transferencia</option>
                                  <option value="CTR009" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR009', $subTipoRem === 'CTR009' ? true : false, $subTipoRem); ?>>CTR009 - Cama Baja o Cuello Ganso</option>
                                  <option value="CTR010" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR010', $subTipoRem === 'CTR010' ? true : false, $subTipoRem); ?>>CTR010 - Chasis Portacontenedor</option>
                                  <option value="CTR011" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR011', $subTipoRem === 'CTR011' ? true : false, $subTipoRem); ?>>CTR011 - Convencional De Chasis</option>
                                  <option value="CTR012" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR012', $subTipoRem === 'CTR012' ? true : false, $subTipoRem); ?>>CTR012 - Equipo Especial</option>
                                  <option value="CTR013" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR013', $subTipoRem === 'CTR013' ? true : false, $subTipoRem); ?>>CTR013 - Estacas</option>
                                  <option value="CTR014" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR014', $subTipoRem === 'CTR014' ? true : false, $subTipoRem); ?>>CTR014 - Góndola Madrina</option>
                                  <option value="CTR015" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR015', $subTipoRem === 'CTR015' ? true : false, $subTipoRem); ?>>CTR015 - Grúa Industrial</option>
                                  <option value="CTR016" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR016', $subTipoRem === 'CTR016' ? true : false, $subTipoRem); ?>>CTR016 - Grúa</option>
                                  <option value="CTR017" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR017', $subTipoRem === 'CTR017' ? true : false, $subTipoRem); ?>>CTR017 - Integral</option>
                                  <option value="CTR018" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR018', $subTipoRem === 'CTR018' ? true : false, $subTipoRem); ?>>CTR018 - Jaula</option>
                                  <option value="CTR019" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR019', $subTipoRem === 'CTR019' ? true : false, $subTipoRem); ?>>CTR019 - Media Redila</option>
                                  <option value="CTR020" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR020', $subTipoRem === 'CTR020' ? true : false, $subTipoRem); ?>>CTR020 - Pallet o Celdillas</option>
                                  <option value="CTR021" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR021', $subTipoRem === 'CTR021' ? true : false, $subTipoRem); ?>>CTR021 - Plataforma</option>
                                  <option value="CTR022" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR022', $subTipoRem === 'CTR022' ? true : false, $subTipoRem); ?>>CTR022 - Plataforma Con Grúa</option>
                                  <option value="CTR023" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR023', $subTipoRem === 'CTR023' ? true : false, $subTipoRem); ?>>CTR023 - Plataforma Encortinada</option>
                                  <option value="CTR024" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR024', $subTipoRem === 'CTR024' ? true : false, $subTipoRem); ?>>CTR024 - Redilas</option>
                                  <option value="CTR025" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR025', $subTipoRem === 'CTR025' ? true : false, $subTipoRem); ?>>CTR025 - Refrigerador</option>
                                  <option value="CTR026" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR026', $subTipoRem === 'CTR026' ? true : false, $subTipoRem); ?>>CTR026 - Revolvedora</option>
                                  <option value="CTR027" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR027', $subTipoRem === 'CTR027' ? true : false, $subTipoRem); ?>>CTR027 - Semicaja</option>
                                  <option value="CTR028" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR028', $subTipoRem === 'CTR028' ? true : false, $subTipoRem); ?>>CTR028 - Tanque</option>
                                  <option value="CTR029" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR029', $subTipoRem === 'CTR029' ? true : false, $subTipoRem); ?>>CTR029 - Tolva</option>
                                  <option value="CTR030" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR030', $subTipoRem === 'CTR030' ? true : false, $subTipoRem); ?>>CTR030 - Tractor</option>
                                  <option value="CTR031" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR031', $subTipoRem === 'CTR031' ? true : false, $subTipoRem); ?>>CTR031 - Volteo</option>
                                  <option value="CTR032" <?php echo set_select('cp[mercancias][autotransporte][remolques]['.$key2.'][subTipoRem]', 'CTR032', $subTipoRem === 'CTR032' ? true : false, $subTipoRem); ?>>CTR032 - Volteo Desmontable</option>
                                </select>
                              </td>
                              <td><input type="text" name="cp[mercancias][autotransporte][remolques][<?php echo $key2 ?>][placa]" value="<?php echo set_value('cp[mercancias][autotransporte][remolques]['.$key2.'][placa]', isset($rem->placa) ? $rem->placa : ''); ?>" class="cpMercans-autotrans_rem_placa"></td>
                              <td style="width: 20px;">
                                <button type="button" class="btn btn-danger delete">Quitar</button>
                              </td>
                            </tr>
                            <?php endforeach ?>
                            <?php endif ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  <!--/Autotransporte -->

                  <!-- Figura Transporte -->
                  <div class="row-fluid">
                    <div class="box span12">
                      <div class="box-header well">
                        <h2><i class="icon-align-justify"></i><span class="break"></span>Figura Transporte</h2>
                        <div class="box-icon">
                          <a href="#modal-cpsat-FiguraTrans" class="btn-add" id="btn-add-CpFiguraTrans" data-rel="tooltip" data-title="Agregar Figura" data-toggle="modal"><i class="icon-plus"></i></a>
                        </div>
                      </div>
                      <div class="box-content" style="padding: 0;" id="boxFiguraTrans">
                        <table class="table table-hover table-condensed" id="table-FiguraTrans">
                          <thead>
                            <tr>
                              <th>TIPO FIGURA</th>
                              <th>RFC FIGURA</th>
                              <th>NOMBRE FIGURA</th>
                              <th>NUM LICENCIA</th>
                              <th>DOMICILIO</th>
                              <th>Opc</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (isset($cpobj->figuraTransporte) && isset($cpobj->figuraTransporte->tiposFigura) && count($cpobj->figuraTransporte->tiposFigura) > 0): ?>
                            <?php foreach ($cpobj->figuraTransporte->tiposFigura as $key => $figura): ?>
                            <tr class="cp-figura" id="cp-figuras<?php echo $key ?>">
                              <td>
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][tipoFigura]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][tipoFigura]', isset($figura->tipoFigura) ? $figura->tipoFigura : ''); ?>" class="cpFigTransTipoFigura">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][rfcFigura]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][rfcFigura]', isset($figura->rfcFigura) ? $figura->rfcFigura : ''); ?>" class="cpFigTransRfcFigura">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][numLicencia]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][numLicencia]', isset($figura->numLicencia) ? $figura->numLicencia : ''); ?>" class="cpFigTransNumLicencia">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][nombreFigura]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][nombreFigura]', isset($figura->nombreFigura) ? $figura->nombreFigura : ''); ?>" class="cpFigTransNombreFigura">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][numRegIdTribFigura]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][numRegIdTribFigura]', isset($figura->numRegIdTribFigura) ? $figura->numRegIdTribFigura : ''); ?>" class="cpFigTransNumRegIdTribFigura">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][residenciaFiscalFigura]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][residenciaFiscalFigura]', isset($figura->residenciaFiscalFigura) ? $figura->residenciaFiscalFigura : ''); ?>" class="cpFigTransResidenciaFiscalFigura">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][residenciaFiscalFigura_text]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][residenciaFiscalFigura_text]', isset($figura->residenciaFiscalFigura_text) ? $figura->residenciaFiscalFigura_text : ''); ?>" class="cpFigTransResidenciaFiscalFigura_text">

                                <?php if (isset($figura->partesTransporte) && count($figura->partesTransporte) > 0): ?>
                                <?php foreach ($figura->partesTransporte as $key2 => $parte): ?>
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][partesTransporte][<?php echo $key2 ?>][parteTransporte]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][partesTransporte]['.$key2.'][parteTransporte]', isset($parte->parteTransporte) ? $parte->parteTransporte : ''); ?>" class="cpFigTransParteTransporte">
                                <?php endforeach ?>
                                <?php endif ?>

                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][calle]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][calle]', isset($figura->domicilio->calle) ? $figura->domicilio->calle : ''); ?>" class="cpFigTransDomCalle">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][numeroExterior]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][numeroExterior]', isset($figura->domicilio->numeroExterior) ? $figura->domicilio->numeroExterior : ''); ?>" class="cpFigTransDomNumeroExterior">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][numeroInterior]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][numeroInterior]', isset($figura->domicilio->numeroInterior) ? $figura->domicilio->numeroInterior : ''); ?>" class="cpFigTransDomNumeroInterior">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][pais]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][pais]', isset($figura->domicilio->pais) ? $figura->domicilio->pais : ''); ?>" class="cpFigTransDomPais">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][pais_text]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][pais_text]', isset($figura->domicilio->pais_text) ? $figura->domicilio->pais_text : ''); ?>" class="cpFigTransDomPais_text">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][estado]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][estado]', isset($figura->domicilio->estado) ? $figura->domicilio->estado : ''); ?>" class="cpFigTransDomEstado">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][estado_text]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][estado_text]', isset($figura->domicilio->estado_text) ? $figura->domicilio->estado_text : ''); ?>" class="cpFigTransDomEstado_text">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][municipio]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][municipio]', isset($figura->domicilio->municipio) ? $figura->domicilio->municipio : ''); ?>" class="cpFigTransDomMunicipio">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][municipio_text]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][municipio_text]', isset($figura->domicilio->municipio_text) ? $figura->domicilio->municipio_text : ''); ?>" class="cpFigTransDomMunicipio_text">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][localidad]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][localidad]', isset($figura->domicilio->localidad) ? $figura->domicilio->localidad : ''); ?>" class="cpFigTransDomLocalidad">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][localidad_text]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][localidad_text]', isset($figura->domicilio->localidad_text) ? $figura->domicilio->localidad_text : ''); ?>" class="cpFigTransDomLocalidad_text">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][codigoPostal]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][codigoPostal]', isset($figura->domicilio->codigoPostal) ? $figura->domicilio->codigoPostal : ''); ?>" class="cpFigTransDomCodigoPostal">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][colonia]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][colonia]', isset($figura->domicilio->colonia) ? $figura->domicilio->colonia : ''); ?>" class="cpFigTransDomColonia">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][colonia_text]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][colonia_text]', isset($figura->domicilio->colonia_text) ? $figura->domicilio->colonia_text : ''); ?>" class="cpFigTransDomColonia_text">
                                <input type="hidden" name="cp[figuraTransporte][tiposFigura][<?php echo $key ?>][domicilio][referencia]" value="<?php echo set_value('cp[figuraTransporte][tiposFigura]['.$key.'][domicilio][referencia]', isset($figura->domicilio->referencia) ? $figura->domicilio->referencia : ''); ?>" class="cpFigTransDomReferencia">

                                <?php echo (isset($figura->tipoFigura) ? $figura->tipoFigura : ''); ?>
                              </td>
                              <td><?php echo (isset($figura->rfcFigura) ? $figura->rfcFigura : ''); ?></td>
                              <td><?php echo (isset($figura->nombreFigura) ? $figura->nombreFigura : ''); ?></td>
                              <td><?php echo (isset($figura->numLicencia) ? $figura->numLicencia : ''); ?></td>
                              <td><?php echo (isset($figura->domicilio->calle) ? $figura->domicilio->calle : ''); ?></td>
                              <td style="width: 20px;">
                                <?php
                                  $figura->datos = clone $figura;
                                  $jsonfigura = MyString::encodeURIComponent(json_encode($figura));
                                ?>
                                <button type="button" class="btn btn-cp-editFiguraTrans" data-json="<?php echo $jsonfigura ?>">Editar</button>
                                <button type="button" class="btn btn-danger btn-cp-removeFiguraTrans">Quitar</button>
                              </td>
                            </tr>
                            <?php endforeach ?>
                            <?php endif ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  <!-- !--/Figura Transporte -->

                </div>
              </div>
            </div>

          </div>

        </form>
      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->


  <!-- Modal CartaPorteSat Mercancias -->
  <div id="modal-cpsat-mercancia" class="modal modal80 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabelProd" aria-hidden="true">
    <div class="modal-header">
      <h3 id="myModalLabelProd">Mercancía</h3>
    </div>
    <div class="modal-body center">
      <div class="row-fluid">
        <div class="span4">
          <label class="control-label" for="mcpsat_bienesTransp_text">Bienes Transportados
            <input type="text" id="mcpsat_bienesTransp_text" class="span12" value="">
            <input type="hidden" id="mcpsat_bienesTransp" class="span12" value="">
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="mcpsat_claveSTCC_text">Clave STCC
            <input type="text" id="mcpsat_claveSTCC_text" class="span12" value="">
            <input type="hidden" id="mcpsat_claveSTCC" class="span12" value="">
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="mcpsat_descripcion">Descripcion
            <input type="text" id="mcpsat_descripcion" class="span12" value="">
          </label>
        </div>
      </div>

      <div class="row-fluid">
        <div class="span4">
          <label class="control-label" for="mcpsat_cantidad">Cantidad
            <input type="number" id="mcpsat_cantidad" class="span12" value="">
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="mcpsat_claveUnidad_text">Clave Unidad
            <input type="text" id="mcpsat_claveUnidad_text" class="span12" value="">
            <input type="hidden" id="mcpsat_claveUnidad" class="span12" value="">
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="mcpsat_unidad">Unidad
            <input type="text" id="mcpsat_unidad" class="span12" value="">
          </label>
        </div>
      </div>

      <div class="row-fluid">
        <div class="span4">
          <label class="control-label" for="mcpsat_dimensiones">Dimensiones
            <input type="text" id="mcpsat_dimensiones" class="span12" value="">
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="mcpsat_materialPeligroso">Material Peligroso
            <select id="mcpsat_materialPeligroso" class="span12">
              <option value=""></option>
              <option value="Sí">Sí</option>
              <option value="No">No</option>
            </select>
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="mcpsat_cveMaterialPeligroso_text">Clave Material Peligroso
            <input type="text" id="mcpsat_cveMaterialPeligroso_text" class="span12" value="">
            <input type="hidden" id="mcpsat_cveMaterialPeligroso" class="span12" value="">
          </label>
        </div>
      </div>

      <div class="row-fluid">
        <div class="span4">
          <label class="control-label" for="mcpsat_embalaje">Embalaje
            <select id="mcpsat_embalaje" class="span12">
              <option value=""></option>
              <option value="1A1">1A1 - Bidones (Tambores) de Acero 1 de tapa no desmontable</option>
              <option value="1A2">1A2 - Bidones (Tambores) de Acero 1 de tapa desmontable</option>
              <option value="1B1">1B1 - Bidones (Tambores) de Aluminio de tapa no desmontable</option>
              <option value="1B2">1B2 - Bidones (Tambores) de Aluminio de tapa desmontable</option>
              <option value="1D">1D - Bidones (Tambores) de Madera contrachapada</option>
              <option value="1G">1G - Bidones (Tambores) de Cartón</option>
              <option value="1H1">1H1 - Bidones (Tambores) de Plástico de tapa no desmontable</option>
              <option value="1H2">1H2 - Bidones (Tambores) de Plástico de tapa desmontable</option>
              <option value="1N1">1N1 - Bidones (Tambores) de Metal que no sea acero ni aluminio de tapa no desmontable</option>
              <option value="1N2">1N2 - Bidones (Tambores) de Metal que no sea acero ni aluminio de tapa desmontable</option>
              <option value="3A1">3A1 - Jerricanes (Porrones) de Acero de tapa no desmontable</option>
              <option value="3A2">3A2 - Jerricanes (Porrones) de Acero de tapa desmontable</option>
              <option value="3B1">3B1 - Jerricanes (Porrones) de Aluminio de tapa no desmontable</option>
              <option value="3B2">3B2 - Jerricanes (Porrones) de Aluminio de tapa desmontable</option>
              <option value="3H1">3H1 - Jerricanes (Porrones) de Plástico de tapa no desmontable</option>
              <option value="3H2">3H2 - Jerricanes (Porrones) de Plástico de tapa desmontable</option>
              <option value="4A">4A - Cajas de Acero</option>
              <option value="4B">4B - Cajas de Aluminio</option>
              <option value="4C1">4C1 - Cajas de Madera natural ordinaria</option>
              <option value="4C2">4C2 - Cajas de Madera natural de paredes a prueba de polvos (estancas a los pulverulentos)</option>
              <option value="4D">4D - Cajas de Madera contrachapada</option>
              <option value="4F">4F - Cajas de Madera reconstituida</option>
              <option value="4G">4G - Cajas de Cartón</option>
              <option value="4H1">4H1 - Cajas de Plástico Expandido</option>
              <option value="4H2">4H2 - Cajas de Plástico Rígido</option>
              <option value="5H1">5H1 - Sacos (Bolsas) de Tejido de plástico sin forro ni revestimientos interiores</option>
              <option value="5H2">5H2 - Sacos (Bolsas) de Tejido de plástico a prueba de polvos (estancos a los pulverulentos)</option>
              <option value="5H3">5H3 - Sacos (Bolsas) de Tejido de plástico resistente al agua</option>
              <option value="5H4">5H4 - Sacos (Bolsas) de Película de plástico</option>
              <option value="5L1">5L1 - Sacos (Bolsas) de Tela sin forro ni revestimientos interiores</option>
              <option value="5L2">5L2 - Sacos (Bolsas) de Tela a prueba de polvos (estancos a los pulverulentos)</option>
              <option value="5L3">5L3 - Sacos (Bolsas) de Tela resistentes al agua</option>
              <option value="5M1">5M1 - Sacos (Bolsas) de Papel de varias hojas</option>
              <option value="5M2">5M2 - Sacos (Bolsas) de Papel de varias hojas, resistentes al agua</option>
              <option value="6HA1">6HA1 - Envases y embalajes compuestos de Recipiente de plástico, con bidón (tambor) de acero</option>
              <option value="6HA2">6HA2 - Envases y embalajes compuestos de Recipiente de plástico, con una jaula o caja de acero</option>
              <option value="6HB1">6HB1 - Envases y embalajes compuestos de Recipiente de plástico, con un bidón (tambor) exterior de aluminio</option>
              <option value="6HB2">6HB2 - Envases y embalajes compuestos de Recipiente de plástico, con una jaula o caja de aluminio</option>
              <option value="6HC">6HC - Envases y embalajes compuestos de Recipiente de plástico, con una caja de madera</option>
              <option value="6HD1">6HD1 - Envases y embalajes compuestos de Recipiente de plástico, con un bidón (tambor) de madera contrachapada</option>
              <option value="6HD2">6HD2 - Envases y embalajes compuestos de Recipiente de plástico, con una caja de madera contrachapada</option>
              <option value="6HG1">6HG1 - Envases y embalajes compuestos de Recipiente de plástico, con un bidón (tambor) de cartón</option>
              <option value="6HG2">6HG2 - Envases y embalajes compuestos de Recipiente de plástico, con una caja de cartón</option>
              <option value="6HH1">6HH1 - Envases y embalajes compuestos de Recipiente de plástico, con un bidón (tambor) de plástico</option>
              <option value="6HH2">6HH2 - Envases y embalajes compuestos de Recipiente de plástico, con caja de plástico rígido</option>
              <option value="6PA1">6PA1 - Envases y embalajes compuestos de Recipiente de vidrio, porcelana o de gres, con un bidón (tambor) de acero</option>
              <option value="6PA2">6PA2 - Envases y embalajes compuestos de Recipiente de vidrio, porcelana o de gres, con una jaula o una caja de acero</option>
              <option value="6PB1">6PB1 - Envases y embalajes compuestos de Recipiente de vidrio, porcelana o de gres, con un bidón (tambor) exterior de aluminio</option>
              <option value="6PB2">6PB2 - Envases y embalajes compuestos de Recipiente de vidrio, porcelana o de gres, con una jaula o una caja de aluminio</option>
              <option value="6PC">6PC - Envases y embalajes compuestos de Recipiente de vidrio, porcelana o de gres, con una caja de madera</option>
              <option value="6PD1">6PD1 - Envases y embalajes compuestos de Recipiente de vidrio, porcelana o de gres, con bidón (tambor) de madera contrachapada</option>
              <option value="6PD2">6PD2 - Envases y embalajes compuestos de Recipiente de vidrio, porcelana o de gres, con canasta de mimbre</option>
              <option value="6PG1">6PG1 - Envases y embalajes compuestos de Recipiente de vidrio, porcelana o de gres, con un bidón (tambor) de cartón</option>
              <option value="6PG2">6PG2 - Envases y embalajes compuestos de Recipiente de vidrio, porcelana o de gres, con una caja de cartón</option>
              <option value="6PH1">6PH1 - Envases y embalajes compuestos de Recipiente de vidrio, porcelana o de gres, con un envase y embalaje de plástico expandido</option>
              <option value="6PH2">6PH2 - Envases y embalajes compuestos de Recipiente de vidrio, porcelana o de gres, con un envase y embalaje de plástico rígido</option>
              <option value="7H1">7H1 - Bultos de Plástico</option>
              <option value="7L1">7L1 - Bultos de Tela</option>
              <option value="Z01">Z01 - No aplica</option>
            </select>
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="mcpsat_descripEmbalaje">Descripción Embalaje
            <input type="text" id="mcpsat_descripEmbalaje" class="span12" value="">
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="mcpsat_pesoEnKg">Peso en Kg
            <input type="text" id="mcpsat_pesoEnKg" class="span12" value="">
          </label>
        </div>
      </div>

      <div class="row-fluid">
        <div class="span4">
          <label class="control-label" for="mcpsat_valorMercancia">Valor Mercancía
            <input type="text" id="mcpsat_valorMercancia" class="span12" value="">
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="mcpsat_moneda">Moneda
            <select id="mcpsat_moneda" class="span12">
              <option value=""></option>
              <option value="MXN">Peso Mexicano</option>
              <option value="USD">Dolar americano</option>
              <option value="EUR">Euro</option>
            </select>
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="mcpsat_fraccionArancelaria_text">Fracción Arancelaria
            <input type="text" id="mcpsat_fraccionArancelaria_text" class="span12" value="">
            <input type="hidden" id="mcpsat_fraccionArancelaria" class="span12" value="">
          </label>
        </div>
      </div>

      <div class="row-fluid">
        <div class="span4">
          <label class="control-label" for="mcpsat_uuidComercioExt">UUID Comercio Ext
            <input type="text" id="mcpsat_uuidComercioExt" class="span12" value="">
          </label>
        </div>
      </div>

      <fieldset>
        <legend>Pedimentos
          <a href="javascript:void(0);" class="btn-add pull-right" id="btn-add-cp-pedimentos" data-rel="tooltip" data-title="Agregar pedimentos"><i class="icon-plus"></i></a>
        </legend>

        <table class="table table-hover table-condensed" id="table-mcpsat_pedimentos">
          <thead>
            <tr>
              <th>PEDIMENTO</th>
              <th>ACCIONES</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><input type="number" class="mcpsat_pedimentos_pedimento" value="" placeholder="52 45 4214 4213546"></td>
              <td><i class="icon-ban-circle delete"></i></td>
            </tr>
          </tbody>
        </table>
      </fieldset>

      <fieldset>
        <legend>Guias de Identificacion
          <a href="javascript:void(0);" class="btn-add pull-right" id="btn-add-cp-guias" data-rel="tooltip" data-title="Agregar Guias de Identificacion"><i class="icon-plus"></i></a>
        </legend>

        <table class="table table-hover table-condensed" id="table-mcpsat_guias">
          <thead>
            <tr>
              <th>NUMERO GUIA</th>
              <th>DESCRIPCION</th>
              <th>PESO</th>
              <th>ACCIONES</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><input type="number" step="any" class="mcpsat_guia_numeroGuiaIdentificacion" value=""></td>
              <td><input type="text" class="mcpsat_guia_descripGuiaIdentificacion" value=""></td>
              <td><input type="number" step="any" class="mcpsat_guia_pesoGuiaIdentificacion" value=""></td>
              <td><i class="icon-ban-circle delete"></i></td>
            </tr>
          </tbody>
        </table>
      </fieldset>

      <fieldset>
        <legend>Cantidad Transporta
          <a href="javascript:void(0);" class="btn-add pull-right" id="btn-add-cantidadTransporta" data-rel="tooltip" data-title="Agregar cantidad Transporta"><i class="icon-plus"></i></a>
        </legend>

        <table class="table table-hover table-condensed" id="table-mcpsat_cantidadTransporta">
          <thead>
            <tr>
              <th>CANTIDAD</th>
              <th>ID ORIGEN</th>
              <th>ID DESTINO</th>
              <th>CVES TRANSPORTE</th>
              <th>ACCIONES</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><input type="number" class="mcpsat_cantidadTransporta_cantidad" value=""></td>
              <td><input type="text" class="mcpsat_cantidadTransporta_idOrigen" value=""></td>
              <td><input type="text" class="mcpsat_cantidadTransporta_idDestino" value=""></td>
              <td>
                <select class="mcpsat_cantidadTransporta_cvesTransporte">
                  <option></option>
                  <option value="01">01 - Autotransporte Federal</option>
                  <option value="02">02 - Transporte Marítimo</option>
                  <option value="03">03 - Transporte Aéreo</option>
                  <option value="04">04 - Transporte Ferroviario</option>
                  <option value="05">05 - Ducto</option>
                </select>
              </td>
              <td><i class="icon-ban-circle delete"></i></td>
            </tr>
          </tbody>
        </table>
      </fieldset>

      <fieldset>
        <legend>Detalle Mercancia</legend>

        <div class="row-fluid">
          <div class="span4">
            <label class="control-label" for="mcpsat_detalleMercancia_unidadPeso_text">Unidad Peso
              <input type="text" id="mcpsat_detalleMercancia_unidadPeso_text" class="span12" value="">
              <input type="hidden" id="mcpsat_detalleMercancia_unidadPeso" class="span12" value="">
            </label>
          </div>
          <div class="span4">
            <label class="control-label" for="mcpsat_detalleMercancia_pesoBruto">Peso Bruto
              <input type="number" id="mcpsat_detalleMercancia_pesoBruto" class="span12" value="">
            </label>
          </div>
          <div class="span4">
            <label class="control-label" for="mcpsat_detalleMercancia_pesoNeto">Peso Neto
              <input type="number" id="mcpsat_detalleMercancia_pesoNeto" class="span12" value="">
            </label>
          </div>
        </div>

        <div class="row-fluid">
          <div class="span4">
            <label class="control-label" for="mcpsat_detalleMercancia_pesoTara">Peso Tara
              <input type="number" id="mcpsat_detalleMercancia_pesoTara" class="span12" value="">
            </label>
          </div>
          <div class="span4">
            <label class="control-label" for="mcpsat_detalleMercancia_numPiezas">Num Piezas
              <input type="number" id="mcpsat_detalleMercancia_numPiezas" class="span12" value="">
            </label>
          </div>
        </div>

      </fieldset>

    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="btn-add-CpProductoModal">Guardar</button>
    </div>
  </div>

  <!-- Modal CartaPorteSat Figuras Trans -->
  <div id="modal-cpsat-FiguraTrans" class="modal modal50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabelProd" aria-hidden="true">
    <div class="modal-header">
      <h3 id="myModalLabelProd">Figura Transporte</h3>
    </div>
    <div class="modal-body center">
      <div class="row-fluid">
        <div class="span4">
          <label class="control-label" for="ftcpsat_tipoFigura">Tipo Figura
            <select id="ftcpsat_tipoFigura">
              <option></option>
              <option value="01">01 - Operador</option>
              <option value="02">02 - Propietario</option>
              <option value="03">03 - Arrendatario</option>
              <option value="04">04 - Notificado</option>
            </select>
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="ftcpsat_rfcFigura">RFC
            <input type="text" id="ftcpsat_rfcFigura" class="span12" value="">
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="ftcpsat_numLicencia">Num Licencia
            <input type="text" id="ftcpsat_numLicencia" class="span12" value="">
          </label>
        </div>
      </div>

      <div class="row-fluid">
        <div class="span4">
          <label class="control-label" for="ftcpsat_nombreFigura">Nombre
            <input type="text" id="ftcpsat_nombreFigura" class="span12" value="">
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="ftcpsat_numRegIdTribFigura">Num Reg Id Trib
            <input type="text" id="ftcpsat_numRegIdTribFigura" class="span12" value="">
          </label>
        </div>
        <div class="span4">
          <label class="control-label" for="ftcpsat_residenciaFiscalFigura_text">Residencia Fiscal
            <input type="text" id="ftcpsat_residenciaFiscalFigura_text" class="span12 sikey" value="" maxlength="60" autocomplete="off">
            <input type="hidden" id="ftcpsat_residenciaFiscalFigura" class="span12">
          </label>
        </div>
      </div>

      <fieldset>
        <legend>Partes Transporte
          <a href="javascript:void(0);" class="btn-add pull-right" id="btn-add-cp-partesTrans" data-rel="tooltip" data-title="Agregar Partes Transporte"><i class="icon-plus"></i></a>
        </legend>

        <table class="table table-hover table-condensed" id="table-ftcpsat_partesTrans">
          <thead>
            <tr>
              <th>PARTE</th>
              <th>ACCIONES</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <select class="ftcpsat_parteTransporte">
                  <option></option>
                  <option value="PT01">PT01</option>
                  <option value="PT02">PT02</option>
                  <option value="PT03">PT03</option>
                  <option value="PT04">PT04</option>
                  <option value="PT05">PT05</option>
                  <option value="PT06">PT06</option>
                  <option value="PT07">PT07</option>
                  <option value="PT08">PT08</option>
                  <option value="PT09">PT09</option>
                  <option value="PT10">PT10</option>
                  <option value="PT11">PT11</option>
                  <option value="PT12">PT12</option>
                </select>
              </td>
              <td><i class="icon-ban-circle delete"></i></td>
            </tr>
          </tbody>
        </table>
      </fieldset>


      <fieldset>
        <legend>Domicilio</legend>

        <div class="row-fluid">
          <div class="span4">
            <label class="control-label" for="ftcpsat_domi_calle">Calle
              <input type="text" id="ftcpsat_domi_calle" class="span12" value="">
            </label>
          </div>
          <div class="span4">
            <label class="control-label" for="ftcpsat_domi_numeroExterior">Numero Exterior
              <input type="text" id="ftcpsat_domi_numeroExterior" class="span12" value="">
            </label>
          </div>
          <div class="span4">
            <label class="control-label" for="ftcpsat_domi_numeroInterior">Numero Interior
              <input type="text" id="ftcpsat_domi_numeroInterior" class="span12" value="">
            </label>
          </div>
        </div>
        <div class="row-fluid">
          <div class="span4">
            <label class="control-label" for="ftcpsat_domi_pais_text">País
              <input type="text" id="ftcpsat_domi_pais_text" class="span12" value="" autocomplete="off">
              <input type="hidden" id="ftcpsat_domi_pais" class="span12" value="">
            </label>
          </div>
          <div class="span4">
            <label class="control-label" for="ftcpsat_domi_estado_text">Estado
              <input type="text" id="ftcpsat_domi_estado_text" class="span12" value="" autocomplete="off">
              <input type="hidden" id="ftcpsat_domi_estado" class="span12" value="">
            </label>
          </div>
          <div class="span4">
            <label class="control-label" for="ftcpsat_domi_municipio_text">Municipio
              <input type="text" id="ftcpsat_domi_municipio_text" class="span12" value="" autocomplete="off">
              <input type="hidden" id="ftcpsat_domi_municipio" class="span12" value="">
            </label>
          </div>
        </div>
        <div class="row-fluid">
          <div class="span4">
            <label class="control-label" for="ftcpsat_domi_localidad_text">Localidad
              <input type="text" id="ftcpsat_domi_localidad_text" class="span12" value="" autocomplete="off">
              <input type="hidden" id="ftcpsat_domi_localidad" class="span12" value="">
            </label>
          </div>
          <div class="span4">
            <label class="control-label" for="ftcpsat_domi_codigoPostal">Código Postal
              <input type="text" id="ftcpsat_domi_codigoPostal" class="span12" value="">
            </label>
          </div>
          <div class="span4">
            <label class="control-label" for="ftcpsat_domi_colonia_text">Colonia
              <input type="text" id="ftcpsat_domi_colonia_text" class="span12" value="" autocomplete="off">
              <input type="hidden" id="ftcpsat_domi_colonia" class="span12" value="">
            </label>
          </div>
        </div>

        <div class="row-fluid">
          <div class="span4">
            <label class="control-label" for="ftcpsat_domi_referencia">Referencia
              <input type="text" id="ftcpsat_domi_referencia" class="span12" value="">
            </label>
          </div>
        </div>

      </fieldset>

    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="btn-add-CpTiposFigura">Guardar</button>
    </div>
  </div>

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
              <th style="width:70px;">Fecha</th>
              <th>Clasificacion</th>
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

  <!-- Modal remisiones-->
  <div id="modal-remisiones" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Remisiones
        <input type="text" id="empresarem" placeholder="Empresas">
        <input type="hidden" id="idempresarem">
      </h3>
    </div>
    <div class="modal-body">
      <div class="row-fluid">
        <table class="table table-hover table-condensed" id="table-pallets-cliente">
          <thead>
            <tr>
              <th></th>
              <th>Serie - Folio</th>
              <th>Cliente</th>
              <th style="width:70px;">Fecha</th>
              <!-- <th>Clasificacion</th> -->
            </tr>
          </thead>
          <tbody id="mdlRemisiones">

            <?php
            if (isset($remisiones)) {
              foreach ($remisiones as $key => $remision) {
                    $rendimientos = array();
                    foreach ($remision->pallets as $pallet) {
                      $rendimientos = array_merge($rendimientos, $pallet['rendimientos']);
                    }
            ?>

              <tr style="" id="chk-cli-remision-<?php echo $remision->id_factura ?>">
                <td><input type="checkbox" value="<?php echo $remision->id_factura ?>" class="chk-cli-remisiones"><input type="hidden" id="jsonData" value="<?php echo htmlentities(json_encode($rendimientos)) ?>"></td>
                <td><?php echo ($remision->serie !== '' && $remision->serie !== null ? $remision->serie.'-' : '').$remision->folio ?></td>
                <td><?php echo $remision->nombre_fiscal ?></td>
                <td><?php echo $remision->fecha ?></td>
                <!-- <td></td> -->
              </tr>
            <?php }
            } ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="BtnAddRemisiones">Agregar Remisiones</button>
    </div>
  </div><!--/modal pallets -->

  <!-- Modal -->
  <div id="modal-cfdiRelPrev" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Sustitución de CFDI</h3>
    </div>
    <div class="modal-body">
      <div class="row-fluid">
        <input type="file" id="fileCfdiRelPrev" placeholder="XML Factura" accept="text/xml">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary hide" id="BtnClearCfdiRel">Quitar</button>
    </div>
  </div><!--/modal pallets -->

</div>

<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){
    noty({"text":"<?php echo preg_replace("/\r|\n/", "", addslashes($frm_errors['msg'])); ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>", "timeout": 10000});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->