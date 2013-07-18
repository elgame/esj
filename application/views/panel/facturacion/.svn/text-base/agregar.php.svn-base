<div class="span10">
  <form class="form-horizontal" action="<?php echo base_url('panel/facturacion/agregar'); ?>" method="POST" id="form">

    <div class="row">
      <div class="span6">

        <div class="control-group">
          <label class="control-label" for="dempresa">Empresa</label>
          <div class="controls">
            <input type="text" name="dempresa" class="span9" id="dempresa" value="<?php echo set_value('dempresa'); ?>" size="73" autofocus>
            <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value('did_empresa'); ?>">
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

            <input type="hidden" name="dano_aprobacion" id="dano_aprobacion" value="<?php echo set_value('dano_aprobacion'); ?>">
            <input type="hidden" name="dimg_cbb" id="dimg_cbb" value="<?php echo set_value('dimg_cbb'); ?>">
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
            <input type="date" name="dfecha" class="span9" id="dfecha" value="<?php echo set_value('dfecha', $fecha); ?>" size="25">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label" for="dno_aprobacion">No. Aprobación</label>
          <div class="controls">
            <input type="text" name="dno_aprobacion" class="span9" id="dno_aprobacion" value="<?php echo set_value('dno_aprobacion'); ?>" size="25" readonly>
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
          <div class="controls">
            <div class="well span9">
                <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;" id="submit">Guardar Factura</button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <div class="row">
      <div class="span12">
        <legend>Productos</legend>
        <div class="span6">
          <div class="input-prepend input-append" style="width:100%;"><span class="add-on">Descripción</span><input class="prod" name="ddescripcion" id="ddescripcion" type="text" style="width:80%;"></div>
          <input type="hidden" value="" id="did_prod">
        </div>
        <div class="span5">
          <div class="input-prepend input-append"><span class="add-on">Precio U.</span><input class="input-mini vpositive prod" name="dpreciou" id="dpreciou" type="text"></div>
          <div class="input-prepend input-append"><span class="add-on">Cant.</span><input class="input-mini vpositive prod" name="dcantidad" id="dcantidad" type="number"></div>
          <div class="input-prepend input-append"><span class="add-on">IVA</span><select name="diva" id="diva" class="input-mini prod">
            <option value="0" <?php echo set_select('diva', '0'); ?>>0%</option>
            <option value="11" <?php echo set_select('diva', '.11'); ?>>11%</option>
            <option value="16" <?php echo set_select('diva', '.16'); ?>>16%</option>
          </select></div>

          <div class="input-prepend input-append" style="margin-top:10px;"><span class="add-on">Medida</span><input class="input-mini prod" name="dmedida" id="dmedida" type="text"></div>
          <div class="input-prepend input-append"><span class="add-on">Desc %</span><input class="input-mini vpos-int prod" name="ddescuento" value="0" id="ddescuento" type="number" min="0" max="100"></div>
          <div class="input-prepend input-append"><span class="add-on">Retención</span><select name="dreten_iva" id="dreten_iva" class="input-small prod">
              <option value="0" <?php echo set_select('dreten_iva', '0'); ?>>No retener</option>
              <option value="0.04" <?php echo set_select('dreten_iva', '0.04'); ?>>4%</option>
              <option value="0.6666" <?php echo set_select('dreten_iva', '0.6666'); ?>>2 Terceras</option>
              <option value="1" <?php echo set_select('dreten_iva', '1'); ?>>100 %</option>
            </select></div>
          <button type="button" class="btn btn-small btn-danger" id="addProducto" style="margin-top:10px;">Agregar Producto</button>
        </div>

      </div>
    </div>

    <div class="row">
      <div class="span12">
        <table class="table table-striped table-bordered table-hover table-condensed" style="margin-top: 10px;" id="table_prod">
          <caption></caption>
          <thead>
            <tr>
              <th>Cant.</th>
              <th>Descripción</th>
              <th>Desc</th>
              <th>P Unitario</th>
              <th>Importe</th>
              <th>IVA</th>
              <th>Medida</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if (isset($_POST['prod_did_prod'])) {
                    foreach ($_POST['prod_did_prod'] as $k => $v){ ?>
                        <tr>
                          <td>
                              <input type="hidden" name="prod_did_prod[]" value="<?php echo $v ?>" id="prod_did_prod">
                              <input type="hidden" name="prod_dcantidad[]" value="<?php echo $_POST['prod_dcantidad'][$k]?>" id="prod_dcantidad"><?php echo $_POST['prod_dcantidad'][$k] ?></td>
                          <td>
                            <input type="hidden" name="prod_ddescripcion[]" value="<?php echo $_POST['prod_ddescripcion'][$k]?>" id="prod_ddescripcion"><?php echo $_POST['prod_ddescripcion'][$k]?></td>
                          <td>
                            <input type="hidden" name="prod_ddescuento[]" value="<?php echo $_POST['prod_ddescuento'][$k]?>" id="prod_ddescuento">
                            <input type="hidden" name="prod_ddescuento_porcent[]" value="<?php echo $_POST['prod_ddescuento_porcent'][$k]?>" id="prod_ddescuento_porcent"><?php echo $_POST['prod_ddescuento_porcent'][$k]?>%</td>
                          <td>
                            <input type="hidden" name="prod_dpreciou[]" value="<?php echo $_POST['prod_dpreciou'][$k]?>" id="prod_dpreciou"><?php echo String::formatoNumero($_POST['prod_dpreciou'][$k])?></td>
                          <td>
                            <input type="hidden" name="prod_importe[]" value="<?php echo $_POST['prod_importe'][$k]?>" id="prod_importe"><?php echo String::formatoNumero($_POST['prod_importe'][$k])?></td>
                          <td>
                              <input type="hidden" name="prod_diva_total[]" value="<?php echo $_POST['prod_diva_total'][$k]?>" id="prod_diva_total">
                              <input type="hidden" name="prod_dreten_iva_total[]" value="<?php echo $_POST['prod_dreten_iva_total'][$k]?>" id="prod_dreten_iva_total">
                              <input type="hidden" name="prod_dreten_iva_porcent[]" value="<?php echo $_POST['prod_dreten_iva_porcent'][$k]?>" id="prod_dreten_iva_porcent">
                              <input type="hidden" name="prod_diva_porcent[]" value="<?php echo $_POST['prod_diva_porcent'][$k]?>" id="prod_diva_porcent"><?php echo $_POST['prod_diva_porcent'][$k]?>%
                          </td>
                          <td><input type="hidden" name="prod_dmedida[]" value="<?php echo $_POST['prod_dmedida'][$k]?>"><?php echo $_POST['prod_dmedida'][$k]?></td>
                          <td><button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button></td>
                        </tr>
            <?php }} ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="row">
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
                  <textarea name="dttotal_letra" rows="10" style="width:98%;max-width:98%;" id="total_letra"><?php echo set_value('dttotal_letra');?></textarea>
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