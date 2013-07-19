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

        <form class="form-horizontal" action="<?php echo base_url('panel/facturacion/agregar'); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="dempresa">Empresa</label>
                <div class="controls">
                  <input type="text" name="dempresa" class="span9" id="dempresa" value="<?php echo set_value('dempresa', $empresa_default->nombre_fiscal); ?>" size="73" autofocus>
                  <input type="text" name="did_empresa" id="did_empresa" value="<?php echo set_value('did_empresa', $empresa_default->id_empresa); ?>">
                  <input type="text" name="dversion" id="dversion" value="<?php echo set_value('dversion', $empresa_default->version); ?>">
                  <input type="text" name="dcer_caduca" id="dcer_caduca" value="<?php echo set_value('dcer_caduca', $empresa_default->cer_caduca); ?>">
                  <input type="text" name="dno_certificado" id="dno_certificado" value="<?php echo set_value('dno_certificado', $no_certificado); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dserie">Serie</label>
                <div class="controls">
                  <select name="dserie" class="span9" id="dserie">
                     <option value=""></option>
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
                  <input type="number" name="dfolio" class="span9" id="dfolio" value="<?php echo set_value('dfolio', (isset($folio)? $folio[0]: '')); ?>" size="15" readonly>

                  <input type="text" name="dano_aprobacion" id="dano_aprobacion" value="<?php echo set_value('dano_aprobacion'); ?>">
                  <!-- <input type="hidden" name="dimg_cbb" id="dimg_cbb" value="<?php echo set_value('dimg_cbb'); ?>"> -->
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente">Cliente</label>
                <div class="controls">
                  <input type="text" name="dcliente" class="span9" id="dcliente" value="<?php echo set_value('dcliente'); ?>" size="73">
                  <input type="hidden" name="did_cliente" id="did_cliente" value="<?php echo set_value('did_cliente'); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente_rfc">RFC</label>
                <div class="controls">
                  <input type="text" name="dcliente_rfc" class="span9" id="dcliente_rfc" value="<?php echo set_value('dcliente_rfc'); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente_domici">Domicilio</label>
                <div class="controls">
                  <input type="text" name="dcliente_domici" class="span9" id="dcliente_domici" value="<?php echo set_value('dcliente_domici'); ?>" size="65">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcliente_ciudad">Ciudad</label>
                <div class="controls">
                  <input type="text" name="dcliente_ciudad" class="span9" id="dcliente_ciudad" value="<?php echo set_value('dcliente_ciudad'); ?>" size="25">
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
                    <option value="egreso" <?php echo set_select('dtipo_comprobante', 'egreso'); ?>>Egreso</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dforma_pago">Forma de pago</label>
                <div class="controls">
                  <select name="dforma_pago" class="span9" id="dforma_pago">
                    <option value="Pago en una sola exhibición" <?php echo set_select('dforma_pago', 'Pago en una sola exhibición'); ?>>Pago en una sola exhibición</option>
                    <option value="Pago en parcialidades" <?php echo set_select('dforma_pago', 'Pago en parcialidades'); ?>>Pago en parcialidades</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dforma_pago">Parcialidades</label>
                <div class="controls">
                  <input type="text" name="dforma_pago_parcialidad" class="span9" id="dforma_pago_parcialidad" value="<?php echo set_value('dforma_pago_parcialidad', 'Parcialidad 1 de X'); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dmetodo_pago">Metodo de pago</label>
                <div class="controls">
                  <select name="dmetodo_pago" class="span9" id="dmetodo_pago">
                    <option value="efectivo" <?php echo set_select('dmetodo_pago', 'efectivo'); ?>>Efectivo</option>
                    <option value="cheque" <?php echo set_select('dmetodo_pago', 'cheque'); ?>>Cheque</option>
                    <option value="tarjeta" <?php echo set_select('dmetodo_pago', 'tarjeta'); ?>>Tarjeta</option>
                    <option value="transferencia" <?php echo set_select('dmetodo_pago', 'transferencia'); ?>>Transferencia</option>
                    <option value="deposito" <?php echo set_select('dmetodo_pago', 'deposito'); ?>>Deposito</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dmetodo_pago_digitos">Ultimos 4 digitos</label>
                <div class="controls">
                  <input type="text" name="dmetodo_pago_digitos" class="span9" id="dmetodo_pago_digitos" value="<?php echo set_value('dmetodo_pago_digitos', 'No identificado'); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcondicion_pago">Condición de pago</label>
                <div class="controls">
                  <select name="dcondicion_pago" class="span9" id="dcondicion_pago">
                    <option value="co" <?php echo set_select('dcondicion_pago', 'co'); ?>>Contado</option>
                    <option value="cr" <?php echo set_select('dcondicion_pago', 'cr'); ?>>Credito</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcondicion_pago">Plazo de crédito</label>
                <div class="controls">
                  <input type="number" name="dplazo_credito" class="span9 vinteger" id="dplazo_credito" value="<?php echo set_value('dplazo_credito', 0); ?>">
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;" id="submit">Guardar Factura</button>
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
                    <th>IVA</th>
                    <th>Retención</th>
                    <th>Importe</th>
                    <th>Accion</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (isset($_POST['prod_did_prod'])) {
                          foreach ($_POST['prod_did_prod'] as $k => $v){
                            if ($_POST['prod_importe'][$k] != 0) { ?>

                              <tr>
                                <td>
                                  <input type="text" name="prod_ddescripcion[]" class="span12" value="<?php echo $_POST['prod_ddescripcion'][$k]?>" id="prod_ddescripcion"><?php echo $_POST['prod_ddescripcion'][$k]?>
                                  <input type="text" name="prod_did_prod[]" class="span12" value="<?php echo $v ?>" id="prod_did_prod">
                                </td>
                                <td>
                                  <input type="text" name="prod_dmedida[]" class="span12" value="<?php echo $_POST['prod_dmedida'][$k]?>" id="prod_dmedida">
                                </td>
                                <td>
                                    <input type="text" name="prod_dcantidad[]" class="span12 vpositive" value="<?php echo $_POST['prod_dcantidad'][$k]?>" id="prod_dcantidad">
                                </td>
                                <td>
                                  <input type="text" name="prod_dpreciou[]" class="span12 vpositive" value="<?php echo $_POST['prod_dpreciou'][$k]?>" id="prod_dpreciou">
                                </td>
                                <td>
                                    <select name="diva" id="diva" class="span12">
                                      <option value="0" <?php echo $_POST['prod_diva_porcent'][$k] == 0 ? 'selected' : '' ?>>0%</option>
                                      <option value="11" <?php echo $_POST['prod_diva_porcent'][$k] == 11 ? 'selected' : '' ?>>11%</option>
                                      <option value="16" <?php echo $_POST['prod_diva_porcent'][$k] == 16 ? 'selected' : '' ?>>16%</option>
                                    </select></div>

                                    <input type="text" name="prod_diva_total[]" class="span12" value="<?php echo $_POST['prod_diva_total'][$k]?>" id="prod_diva_total">
                                    <input type="text" name="prod_diva_porcent[]" class="span12" value="<?php echo $_POST['prod_diva_porcent'][$k]?>" id="prod_diva_porcent">
                                </td>
                                <td>
                                  <select name="dreten_iva" id="dreten_iva" class="span12 prod">
                                    <option value="0" <?php echo $_POST['prod_dreten_iva_porcent'][$k] == 0 ? 'selected' : '' ?>>No retener</option>
                                    <option value="0.04" <?php echo $_POST['prod_dreten_iva_porcent'][$k] == 0.04 ? 'selected' : '' ?>>4%</option>
                                    <option value="0.6666" <?php echo $_POST['prod_dreten_iva_porcent'][$k] == 0.6666 ? 'selected' : '' ?>>2 Terceras</option>
                                    <option value="1" <?php echo $_POST['prod_dreten_iva_porcent'][$k] == 1 ? 'selected' : '' ?>>100 %</option>
                                  </select>

                                  <input type="text" name="prod_dreten_iva_total[]" value="<?php echo $_POST['prod_dreten_iva_total'][$k] ?>" id="prod_dreten_iva_total" class="span12">
                                  <input type="text" name="prod_dreten_iva_porcent[]" value="<?php echo $_POST['prod_dreten_iva_porcent'][$k] ?>" id="prod_dreten_iva_porcent" class="span12">
                                </td>
                                 <td>
                                  <input type="text" name="prod_importe[]" class="span12 vpositive" value="<?php echo $_POST['prod_importe'][$k]?>" id="prod_importe">
                                </td>
                                <td>
                                  <button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                  <?php }}} ?>
                  <tr>
                    <td>
                      <input type="text" name="prod_ddescripcion[]" value="" id="prod_ddescripcion" class="span12">
                      <input type="text" name="prod_did_prod[]" value="" id="prod_did_prod" class="span12">
                    </td>
                    <td><input type="text" name="prod_dmedida[]" value="" id="prod_dmedida" class="span12"></td>
                    <td>
                        <input type="text" name="prod_dcantidad[]" value="0" id="prod_dcantidad" class="span12 vpositive">
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

                        <input type="text" name="prod_diva_total[]" value="0" id="prod_diva_total" class="span12">
                        <input type="text" name="prod_diva_porcent[]" value="0" id="prod_diva_porcent" class="span12">
                    </td>
                    <td>
                      <select name="dreten_iva" id="dreten_iva" class="span12 prod">
                        <option value="0">No retener</option>
                        <option value="0.04">4%</option>
                        <option value="0.6666">2 Terceras</option>
                        <option value="1">100 %</option>
                      </select>

                      <input type="text" name="prod_dreten_iva_total[]" value="0" id="prod_dreten_iva_total" class="span12">
                      <input type="text" name="prod_dreten_iva_porcent[]" value="0" id="prod_dreten_iva_porcent" class="span12">
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
                        <textarea name="dttotal_letra" rows="10" class="nokey" style="width:98%;max-width:98%;" id="total_letra"><?php echo set_value('dttotal_letra');?></textarea>
                    </td>
                  </tr>
                  <tr>
                    <td><em>Subtotal</em></td>
                    <td id="importe-format"><?php echo String::formatoNumero(set_value('total_importe', 0))?></td>
                    <input type="hidden" name="total_importe" id="total_importe" value="<?php echo set_value('total_importe', 0); ?>">
                  </tr>
                  <tr>
                    <td>Descuento</td>
                    <td id="descuento-format"><?php echo String::formatoNumero(set_value('total_descuento', 0))?></td>
                    <input type="hidden" name="total_descuento" id="total_descuento" value="<?php echo set_value('total_descuento', 0); ?>">
                  </tr>
                  <tr>
                    <td>SUBTOTAL</td>
                    <td id="subtotal-format"><?php echo String::formatoNumero(set_value('total_subtotal', 0))?></td>
                    <input type="hidden" name="total_subtotal" id="total_subtotal" value="<?php echo set_value('total_subtotal', 0); ?>">
                  </tr>
                  <tr>
                    <td>IVA</td>
                    <td id="iva-format"><?php echo String::formatoNumero(set_value('total_iva', 0))?></td>
                    <input type="hidden" name="total_iva" id="total_iva" value="<?php echo set_value('total_iva', 0); ?>">
                  </tr>
                  <tr>
                    <td>Ret. IVA</td>
                    <td id="retiva-format"><?php echo String::formatoNumero(set_value('total_retiva', 0))?></td>
                    <input type="hidden" name="total_retiva" id="total_retiva" value="<?php echo set_value('total_retiva', 0); ?>">
                  </tr>
                  <tr style="font-weight:bold;font-size:1.2em;">
                    <td>TOTAL</td>
                    <td id="totfac-format"><?php echo String::formatoNumero(set_value('total_totfac', 0))?></td>
                    <input type="hidden" name="total_totfac" id="total_totfac" value="<?php echo set_value('total_totfac', 0); ?>">
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

  <?php if($frm_errors['ico'] === 'success') {
    echo 'window.open("'.base_url('panel/facturacion/imprimir/?id='.$id).'")';
  }?>

  $(document).ready(function(){
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->