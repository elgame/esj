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

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="dfecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="dfecha" class="span9" id="dfecha" value="<?php echo set_value('dfecha', $fecha); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dno_aprobacion">No. Aprobación</label>
                <div class="controls">
                  <input type="text" name="dno_aprobacion" class="span9" id="dno_aprobacion" value="<?php echo set_value('dno_aprobacion'); ?>" size="25" readonly>
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
                      $option = isset($borrador) ? $borrador['info']->forma_pago : '';

                      $parcialidades = true;
                      if ($option === 'Pago en una sola exhibición' || $option === '')
                        $parcialidades = false;

                     ?>

                    <option value="Pago en una sola exhibición" <?php echo set_select('dforma_pago', 'Pago en una sola exhibición', $option === 'Pago en una sola exhibición' ? true : false); ?>>Pago en una sola exhibición</option>
                    <option value="Pago en parcialidades" <?php echo set_select('dforma_pago', 'Pago en parcialidades', $parcialidades ? true : false); ?>>Pago en parcialidades</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dforma_pago">Parcialidades</label>
                <div class="controls">
                  <input type="text" name="dforma_pago_parcialidad" class="span9" id="dforma_pago_parcialidad" value="<?php echo set_value('dforma_pago_parcialidad', $parcialidades ? $option : 'Parcialidad 1 de X'); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dmetodo_pago">Metodo de pago</label>
                <div class="controls">
                  <select name="dmetodo_pago" class="span9" id="dmetodo_pago">

                    <?php
                      $metodo = isset($borrador) ? $borrador['info']->metodo_pago : '';
                     ?>

                    <option value="no identificado" <?php echo set_select('dmetodo_pago', 'no identificado', $metodo === 'no identificado' ? true : false); ?>>No identificado</option>
                    <option value="efectivo" <?php echo set_select('dmetodo_pago', 'efectivo', $metodo === 'efectivo' ? true : false); ?>>Efectivo</option>
                    <option value="cheque" <?php echo set_select('dmetodo_pago', 'cheque', $metodo === 'cheque' ? true : false); ?>>Cheque</option>
                    <option value="tarjeta" <?php echo set_select('dmetodo_pago', 'tarjeta', $metodo === 'tarjeta' ? true : false); ?>>Tarjeta</option>
                    <option value="transferencia" <?php echo set_select('dmetodo_pago', 'transferencia', $metodo === 'transferencia' ? true : false); ?>>Transferencia</option>
                    <option value="deposito" <?php echo set_select('dmetodo_pago', 'deposito', $metodo === 'deposito' ? true : false); ?>>Deposito</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
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

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <button type="submit" name="borrador" class="btn btn-success btn-large btn-block" style="width:100%;" id="">Guardar</button><br><br>
                      <button type="submit" name="timbrar" class="btn btn-success btn-large btn-block" style="width:100%;" id="">Timbrar</button>
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
                    <th>Descripción</th>
                    <th>Medida</th>
                    <th>Cant.</th>
                    <th>P Unitario</th>
                    <th>IVA%</th>
                    <th>IVA</th>
                    <th>Retención</th>
                    <th>Importe</th>
                    <th>Accion</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                        if (isset($borrador) && ! isset($_POST['prod_did_prod']))
                        {
                          foreach ($borrador['productos'] as $key => $p) {
                            $_POST['prod_did_prod'][$key]           = $p->id_clasificacion;
                            $_POST['prod_importe'][$key]            = $p->importe;
                            $_POST['prod_ddescripcion'][$key]       = $p->descripcion;
                            $_POST['prod_dmedida'][$key]            = $p->unidad;
                            $_POST['prod_dcantidad'][$key]          = $p->cantidad;
                            $_POST['prod_dpreciou'][$key]           = $p->precio_unitario;
                            $_POST['prod_diva_porcent'][$key]       = $p->porcentaje_iva;
                            $_POST['prod_diva_total'][$key]         = $p->iva;
                            $_POST['prod_dreten_iva_porcent'][$key] = $p->porcentaje_retencion;
                            $_POST['prod_dreten_iva_total'][$key]   = $p->retencion_iva;
                            $_POST['pallets_id'][$key]      = $p->ids_pallets;
                            $_POST['remisiones_id'][$key]   = $p->ids_remisiones;
                            $_POST['prod_dkilos'][$key]     = $p->kilos;
                            $_POST['prod_dcajas'][$key]     = $p->cajas;
                            $_POST['id_unidad_rendimiento'][$key] = $p->id_unidad_rendimiento;
                            $_POST['prod_dmedida_id'][$key] = $p->id_unidad;
                          }
                        } ?>

                        <?php if (isset($_POST['prod_did_prod'])) {
                          foreach ($_POST['prod_did_prod'] as $k => $v) {
                            if ($_POST['prod_importe'][$k] != 0) {
                            ?>
                              <tr data-pallets="<?php echo $_POST['pallets_id'][$k] ?>" data-remisiones="<?php echo $_POST['remisiones_id'][$k] ?>">
                                <td>
                                  <input type="text" name="prod_ddescripcion[]" class="span12" value="<?php echo $_POST['prod_ddescripcion'][$k]?>" id="prod_ddescripcion">
                                  <input type="hidden" name="prod_did_prod[]" class="span12" value="<?php echo $v ?>" id="prod_did_prod">
                                  <input type="hidden" name="pallets_id[]" value="<?php echo $_POST['pallets_id'][$k] ?>" id="pallets_id" class="span12">
                                  <input type="hidden" name="remisiones_id[]" value="<?php echo $_POST['remisiones_id'][$k] ?>" id="remisiones_id" class="span12">
                                  <input type="hidden" name="id_unidad_rendimiento[]" value="<?php echo $_POST['id_unidad_rendimiento'][$k] ?>" id="id_unidad_rendimiento" class="span12">
                                </td>
                                <td>
                                  <select name="prod_dmedida[]" id="prod_dmedida" class="span12">
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->nombre ?>" <?php echo $_POST['prod_dmedida'][$k] == $u->nombre ? 'selected' : '' ?> data-id="<?php echo $u->id_unidad ?>"><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                  <input type="hidden" name="prod_dmedida_id[]" value="<?php echo $_POST['prod_dmedida_id'][$k] ?>" id="prod_dmedida_id" class="span12 vpositive">
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
                                      <option value="11" <?php echo $_POST['prod_diva_porcent'][$k] == 11 ? 'selected' : ''; ?>>11%</option>
                                      <option value="16" <?php echo $_POST['prod_diva_porcent'][$k] == 16 ? 'selected' : ''; ?>>16%</option>
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
                                  <button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                        <?php }}} ?>
                        <tr data-pallets="" data-remisiones="">
                          <td>
                            <input type="text" name="prod_ddescripcion[]" value="" id="prod_ddescripcion" class="span12">
                            <input type="hidden" name="prod_did_prod[]" value="" id="prod_did_prod" class="span12">
                            <input type="hidden" name="pallets_id[]" value="" id="pallets_id" class="span12">
                            <input type="hidden" name="remisiones_id[]" value="" id="remisiones_id" class="span12">
                            <input type="hidden" name="id_unidad_rendimiento[]" value="" id="id_unidad_rendimiento" class="span12">
                          </td>
                          <td>
                            <!-- <input type="text" name="prod_dmedida[]" value="" id="prod_dmedida" class="span12"> -->
                            <select name="prod_dmedida[]" id="prod_dmedida" class="span12">
                              <?php foreach ($unidades as $key => $u) {
                                  if ($key === 0) $uni = $u->id_unidad;
                                ?>
                                <option value="<?php echo $u->nombre ?>" data-id="<?php echo $u->id_unidad ?>"><?php echo $u->nombre ?></option>
                              <?php } ?>
                              <input type="hidden" name="prod_dmedida_id[]" value="<?php echo $uni ?>" id="prod_dmedida_id" class="span12 vpositive">
                            </select>
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
                                <option value="11">11%</option>
                                <option value="16">16%</option>
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
                          <td><button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button></td>
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
                    <td rowspan="7">
                        <textarea name="dttotal_letra" rows="10" class="nokey" style="width:98%;max-width:98%;" id="total_letra"><?php echo set_value('dttotal_letra', isset($borrador) ? $borrador['info']->total_letra : '');?></textarea>
                    </td>
                  </tr>
                  <tr>
                    <td><em>Subtotal</em></td>
                    <td id="importe-format"><?php echo String::formatoNumero(set_value('total_importe', isset($borrador) ? $borrador['info']->subtotal : 0))?></td>
                    <input type="hidden" name="total_importe" id="total_importe" value="<?php echo set_value('total_importe', isset($borrador) ? $borrador['info']->subtotal : 0); ?>">
                  </tr>
                  <tr>
                    <td>Descuento</td>
                    <td id="descuento-format"><?php echo String::formatoNumero(set_value('total_descuento', 0))?></td>
                    <input type="hidden" name="total_descuento" id="total_descuento" value="<?php echo set_value('total_descuento', 0); ?>">
                  </tr>
                  <tr>
                    <td>SUBTOTAL</td>
                    <td id="subtotal-format"><?php echo String::formatoNumero(set_value('total_subtotal', isset($borrador) ? $borrador['info']->subtotal : 0))?></td>
                    <input type="hidden" name="total_subtotal" id="total_subtotal" value="<?php echo set_value('total_subtotal', isset($borrador) ? $borrador['info']->subtotal : 0); ?>">
                  </tr>
                  <tr>
                    <td>IVA</td>
                    <td id="iva-format"><?php echo String::formatoNumero(set_value('total_iva', isset($borrador) ? $borrador['info']->importe_iva : 0))?></td>
                    <input type="hidden" name="total_iva" id="total_iva" value="<?php echo set_value('total_iva', isset($borrador) ? $borrador['info']->importe_iva : 0); ?>">
                  </tr>
                  <tr>
                    <td>Ret. IVA</td>
                    <td id="retiva-format"><?php echo String::formatoNumero(set_value('total_retiva', isset($borrador) ? $borrador['info']->retencion_iva : 0))?></td>
                    <input type="hidden" name="total_retiva" id="total_retiva" value="<?php echo set_value('total_retiva', isset($borrador) ? $borrador['info']->retencion_iva : 0); ?>">
                  </tr>
                  <tr style="font-weight:bold;font-size:1.2em;">
                    <td>TOTAL</td>
                    <td id="totfac-format"><?php echo String::formatoNumero(set_value('total_totfac', isset($borrador) ? $borrador['info']->total : 0))?></td>
                    <input type="hidden" name="total_totfac" id="total_totfac" value="<?php echo set_value('total_totfac', isset($borrador) ? $borrador['info']->total : 0); ?>">
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->

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
      <h3 id="myModalLabel">Remisiones</h3>
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
          <tbody>

            <?php foreach ($remisiones as $key => $remision) {
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
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="BtnAddRemisiones">Agregar Remisiones</button>
    </div>
  </div><!--/modal pallets -->

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