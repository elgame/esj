  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Datos Factura</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/facturacion'); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="dempresa">Empresa</label>
                <div class="controls">
                  <input type="text" name="dempresa" class="span9" id="dempresa" value="<?php echo set_value('dempresa', $factura['info']->empresa->nombre_fiscal); ?>" size="73" readonly>
                  <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value('did_empresa', $factura['info']->empresa->id_empresa); ?>">
                  <input type="hidden" name="dversion" id="dversion" value="<?php echo set_value('dversion', $factura['info']->empresa->cfdi_version); ?>">
                  <input type="hidden" name="dcer_caduca" id="dcer_caduca" value="<?php echo set_value('dcer_caduca', $factura['info']->empresa->cer_caduca); ?>">
                  <input type="hidden" name="dno_certificado" id="dno_certificado" value="<?php echo set_value('dno_certificado', $factura['info']->no_certificado); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dserie">Serie</label>
                <div class="controls">
                  <select name="dserie" class="span9" id="dserie" readonly>
                     <option value=""><?php echo $factura['info']->serie?></option>
                     <?php // foreach($series['series'] as $ser){ ?>
                          <!-- <option value="<?php // echo $ser->serie; ?>" <?php // echo set_select('dserie', $ser->serie); ?>> -->
                            <?php // echo $ser->serie.($ser->leyenda!=''? '-'.$ser->leyenda: ''); ?></option>
                      <?php // } ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dfolio">Folio</label>
                <div class="controls">
                  <input type="number" name="dfolio" class="span9" id="dfolio" value="<?php echo set_value('dfolio', $factura['info']->folio); ?>" size="15" readonly>

                  <input type="hidden" name="dano_aprobacion" id="dano_aprobacion" value="<?php echo set_value('dano_aprobacion', $factura['info']->ano_aprobacion); ?>">
                  <!-- <input type="hidden" name="dimg_cbb" id="dimg_cbb" value="<?php //echo set_value('dimg_cbb'); ?>"> -->
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente">Cliente</label>
                <div class="controls">
                  <input type="text" name="dcliente" class="span9" id="dcliente" value="<?php echo set_value('dcliente', $factura['info']->cliente->nombre_fiscal); ?>" size="73" readonly>
                  <input type="hidden" name="did_cliente" id="did_cliente" value="<?php echo set_value('did_cliente', $factura['info']->cliente->id_cliente); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente_rfc">RFC</label>
                <div class="controls">
                  <input type="text" name="dcliente_rfc" class="span9" id="dcliente_rfc" value="<?php echo set_value('dcliente_rfc', $factura['info']->cliente->rfc); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente_domici">Domicilio</label>
                <div class="controls">
                  <input type="text" name="dcliente_domici" class="span9" id="dcliente_domici" value="<?php echo set_value('dcliente_domici', $factura['info']->cliente->calle); ?>" size="65" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente_ciudad">Ciudad</label>
                <div class="controls">
                  <input type="text" name="dcliente_ciudad" class="span9" id="dcliente_ciudad" value="<?php echo set_value('dcliente_ciudad', $factura['info']->cliente->municipio); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dobservaciones">Observaciones</label>
                <div class="controls">
                  <textarea name="dobservaciones" class="span9" id="dobservaciones" readonly><?php echo set_value('dobservaciones', $factura['info']->observaciones); ?></textarea>
                </div>
              </div>
            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="dfecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="dfecha" class="span9" id="dfecha" value="<?php echo set_value('dfecha', $factura['info']->fechaT); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dno_aprobacion">No. Aprobación</label>
                <div class="controls">
                  <input type="text" name="dno_aprobacion" class="span9" id="dno_aprobacion" value="<?php echo set_value('dno_aprobacion', $factura['info']->no_aprobacion); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dno_aprobacion">Tipo comprobante</label>
                <div class="controls">
                  <select name="dtipo_comprobante" class="span9" id="dtipo_comprobante" readonly>
                    <option value="ingreso" <?php echo set_select('dtipo_comprobante', 'ingreso', false, $factura['info']->tipo_comprobante); ?>>Ingreso</option>
                    <option value="egreso" <?php echo set_select('dtipo_comprobante', 'egreso', false, $factura['info']->tipo_comprobante); ?>>Egreso</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dforma_pago">Forma de pago</label>
                <div class="controls">
                  <?php
                      $formaPago = 'Pago en una sola exhibición';
                      $parcialidades = 'Parcialidad 1 de X';
                      if ($factura['info']->forma_pago !== 'Pago en una sola exhibición') {
                        $formaPago = 'Pago en parcialidades';
                        $parcialidades = $factura['info']->forma_pago;
                      }
                  ?>
                  <select name="dforma_pago" class="span9" id="dforma_pago" readonly>
                    <option value="Pago en una sola exhibición" <?php echo set_select('dforma_pago', 'Pago en una sola exhibición', false, $formaPago); ?>>Pago en una sola exhibición</option>
                    <option value="Pago en parcialidades" <?php echo set_select('dforma_pago', 'Pago en parcialidades', false, $formaPago); ?>>Pago en parcialidades</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dforma_pago">Parcialidades</label>
                <div class="controls">
                  <input type="text" name="dforma_pago_parcialidad" class="span9" id="dforma_pago_parcialidad" value="<?php echo set_value('dforma_pago_parcialidad', $parcialidades); ?>" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dmetodo_pago">Metodo de pago</label>
                <div class="controls">
                  <select name="dmetodo_pago" class="span9" id="dmetodo_pago" readonly>
                    <option value="efectivo" <?php echo set_select('dmetodo_pago', 'efectivo', false, $factura['info']->metodo_pago); ?>>Efectivo</option>
                    <option value="cheque" <?php echo set_select('dmetodo_pago', 'cheque', false, $factura['info']->metodo_pago); ?>>Cheque</option>
                    <option value="tarjeta" <?php echo set_select('dmetodo_pago', 'tarjeta', false, $factura['info']->metodo_pago); ?>>Tarjeta</option>
                    <option value="transferencia" <?php echo set_select('dmetodo_pago', 'transferencia', false, $factura['info']->metodo_pago); ?>>Transferencia</option>
                    <option value="deposito" <?php echo set_select('dmetodo_pago', 'deposito', false, $factura['info']->metodo_pago); ?>>Deposito</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dmetodo_pago_digitos">Ultimos 4 digitos</label>
                <div class="controls">
                  <input type="text" name="dmetodo_pago_digitos" class="span9" id="dmetodo_pago_digitos" value="<?php echo set_value('dmetodo_pago_digitos', $factura['info']->metodo_pago_digitos); ?>" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcondicion_pago">Condición de pago</label>
                <div class="controls">
                  <select name="dcondicion_pago" class="span9" id="dcondicion_pago" readonly>
                    <option value="co" <?php echo set_select('dcondicion_pago', 'co', false, $factura['info']->condicion_pago); ?>>Contado</option>
                    <option value="cr" <?php echo set_select('dcondicion_pago', 'cr', false, $factura['info']->condicion_pago); ?>>Credito</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcondicion_pago">Plazo de crédito</label>
                <div class="controls">
                  <input type="number" name="dplazo_credito" class="span9 vinteger" id="dplazo_credito" value="<?php echo set_value('dplazo_credito', $factura['info']->plazo_credito); ?>" readonly>
                </div>
              </div>

              <!-- <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;" id="submit">Guardar Factura</button>
                  </div>
                </div>
              </div> -->

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
                    <th>IVA</th>
                    <th>Retención</th>
                    <th>Importe</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (isset($factura['productos'])) {
                          foreach ($factura['productos'] as $key => $concepto) {
                            if ( $factura['info']->sin_costo == 'f') {
                  ?>
                            <tr>
                              <td>
                                <input type="text" name="prod_ddescripcion[]" class="span12" value="<?php echo $concepto->descripcion?>" id="prod_ddescripcion" readonly>
                                <input type="hidden" name="prod_did_prod[]" class="span12" value="<?php echo $concepto->id_clasificacion ?>" id="prod_did_prod">
                              </td>
                              <td>
                                <input type="text" name="prod_dmedida[]" class="span12" value="<?php echo $concepto->unidad?>" id="prod_dmedida" readonly>
                              </td>
                              <td>
                                  <input type="text" name="prod_dcantidad[]" class="span12 vpositive" value="<?php echo $concepto->cantidad?>" id="prod_dcantidad" readonly>
                              </td>
                              <td>
                                <input type="text" name="prod_dpreciou[]" class="span12 vpositive" value="<?php echo $concepto->precio_unitario?>" id="prod_dpreciou" readonly>
                              </td>
                              <td>
                                  <input type="text" name="prod_diva_total[]" class="span12" value="<?php echo $concepto->iva?>" id="prod_diva_total" readonly>
                              </td>
                              <td>
                                <input type="text" name="prod_dreten_iva_total[]" value="<?php echo $concepto->retencion_iva ?>" id="prod_dreten_iva_total" class="span12" readonly>
                              </td>
                               <td>
                                <input type="text" name="prod_importe[]" class="span12 vpositive" value="<?php echo $concepto->importe?>" id="prod_importe" readonly>
                              </td>
                            </tr>
                  <?php } else {

                        if ($concepto->id_clasificacion != '48' AND $concepto->id_clasificacion != '49' AND
                            $concepto->id_clasificacion != '50' AND $concepto->id_clasificacion != '51' AND
                            $concepto->id_clasificacion != '52')
                        {
                    ?>
                          <tr>
                            <td>
                              <input type="text" name="prod_ddescripcion[]" class="span12" value="<?php echo $concepto->descripcion?>" id="prod_ddescripcion" readonly>
                              <input type="hidden" name="prod_did_prod[]" class="span12" value="<?php echo $concepto->id_clasificacion ?>" id="prod_did_prod">
                            </td>
                            <td>
                              <input type="text" name="prod_dmedida[]" class="span12" value="<?php echo $concepto->unidad?>" id="prod_dmedida" readonly>
                            </td>
                            <td>
                                <input type="text" name="prod_dcantidad[]" class="span12 vpositive" value="<?php echo $concepto->cantidad?>" id="prod_dcantidad" readonly>
                            </td>
                            <td>
                              <input type="text" name="prod_dpreciou[]" class="span12 vpositive" value="<?php echo $concepto->precio_unitario?>" id="prod_dpreciou" readonly>
                            </td>
                            <td>
                                <input type="text" name="prod_diva_total[]" class="span12" value="<?php echo $concepto->iva?>" id="prod_diva_total" readonly>
                            </td>
                            <td>
                              <input type="text" name="prod_dreten_iva_total[]" value="<?php echo $concepto->retencion_iva ?>" id="prod_dreten_iva_total" class="span12" readonly>
                            </td>
                             <td>
                              <input type="text" name="prod_importe[]" class="span12 vpositive" value="<?php echo $concepto->importe?>" id="prod_importe" readonly>
                            </td>
                          </tr>

                <?php } }}} ?>
                </tbody>
              </table>
            </div>
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
                        <textarea name="dttotal_letra" rows="10" class="nokey" style="width:98%;max-width:98%;" id="total_letra"><?php echo set_value('dttotal_letra', $factura['info']->total_letra);?></textarea>
                    </td>
                  </tr>
                  <tr>
                    <td><em>Subtotal</em></td>
                    <td id="importe-format"><?php echo String::formatoNumero(set_value('total_importe', $factura['info']->subtotal))?></td>
                    <input type="hidden" name="total_importe" id="total_importe" value="<?php echo set_value('total_importe', $factura['info']->subtotal); ?>">
                  </tr>
                  <tr>
                    <td>Descuento</td>
                    <td id="descuento-format"><?php echo String::formatoNumero(set_value('total_descuento', 0))?></td>
                    <input type="hidden" name="total_descuento" id="total_descuento" value="<?php echo set_value('total_descuento', 0); ?>">
                  </tr>
                  <tr>
                    <td>SUBTOTAL</td>
                    <td id="subtotal-format"><?php echo String::formatoNumero(set_value('total_subtotal', $factura['info']->subtotal))?></td>
                    <input type="hidden" name="total_subtotal" id="total_subtotal" value="<?php echo set_value('total_subtotal', $factura['info']->subtotal); ?>">
                  </tr>
                  <tr>
                    <td>IVA</td>
                    <td id="iva-format"><?php echo String::formatoNumero(set_value('total_iva', $factura['info']->importe_iva))?></td>
                    <input type="hidden" name="total_iva" id="total_iva" value="<?php echo set_value('total_iva', $factura['info']->importe_iva); ?>">
                  </tr>
                  <tr>
                    <td>Ret. IVA</td>
                    <td id="retiva-format"><?php echo String::formatoNumero(set_value('total_retiva', $factura['info']->retencion_iva))?></td>
                    <input type="hidden" name="total_retiva" id="total_retiva" value="<?php echo set_value('total_retiva', $factura['info']->retencion_iva); ?>">
                  </tr>
                  <tr style="font-weight:bold;font-size:1.2em;">
                    <td>TOTAL</td>
                    <td id="totfac-format"><?php echo String::formatoNumero(set_value('total_totfac', $factura['info']->total))?></td>
                    <input type="hidden" name="total_totfac" id="total_totfac" value="<?php echo set_value('total_totfac', $factura['info']->total); ?>">
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->

<!-- </div> -->