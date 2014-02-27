<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="es" class="no-js"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title></title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width">

<?php
  if(isset($this->carabiner)){
    $this->carabiner->display('css');
    $this->carabiner->display('base_panel');
    $this->carabiner->display('js');
  }
?>

  <!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
  <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

<script type="text/javascript" charset="UTF-8">
  var base_url = "<?php echo base_url();?>",
      base_url_bascula = "<?php echo $this->config->item('base_url_bascula');?>",
      base_url_cam_salida_snapshot = "<?php echo $this->config->item('base_url_cam_salida_snapshot') ?> ";
</script>
</head>
<body>
  <div id="content" class="container-fluid">
    <div class="row-fluid">
      <!--[if lt IE 7]>
        <div class="alert alert-info">
          <button type="button" class="close" data-dismiss="alert">×</button>
          <p>Usted está usando un navegador desactualizado. <a href="http://browsehappy.com/">Actualice su navegador</a> o <a href="http://www.google.com/chromeframe/?redirect=true">instale Google Chrome Frame</a> para experimentar mejor este sitio.</p>
        </div>
      <![endif]-->

      <div id="content" class="span12">
        <div class="row-fluid">
          <div class="box span12">
            <div class="box-header well" data-original-title>
              <h2><i class="icon-plus"></i> Ligar Ordenes de Compra</h2>
              <div class="box-icon">
                <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
              </div>
            </div>
            <div class="box-content">

              <div class="row-fluid center">
                <div class="span12"><h2><?php echo $proveedor['info']->nombre_fiscal ?></h2></div>
              </div>

              <form class="form-horizontal" action="<?php echo base_url('panel/compras_ordenes/ligar/?'.String::getVarsLink(array('msg', 'rel'))); ?>" method="POST" id="form" enctype="multipart/form-data">
                <input type="hidden" name="proveedorId" value="<?php echo $proveedor['info']->id_proveedor ?>">
                <input type="hidden" name="empresaId" value="<?php echo $_GET['ide'] ?>">
                <div class="row-fluid">
                  <div class="span4">
                    <div class="control-group">
                      <div class="controls span9">
                        Serie <input type="text" name="serie" class="span12" id="serie" value="<?php echo set_value('serie'); ?>" autofocus>
                      </div>
                    </div>
                  </div>
                  <div class="span4">
                    <div class="control-group">
                      <div class="controls span9">
                        Folio<input type="text" name="folio" class="span12" id="folio" value="<?php echo set_value('folio'); ?>">
                      </div>
                    </div>
                  </div>
                  <div class="span4">
                    <div class="control-group">
                      <div class="controls span9">
                        Fecha<input type="datetime-local" name="fecha" class="span12" id="fecha" value="<?php echo set_value('fecha', $fecha); ?>">
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row-fluid">
                  <div class="span4" style="display:none;">
                    <div class="control-group">
                      <div class="controls span9">
                        Condicion de Pago
                        <input type="text" name="condicionPago" class="span12" id="condicionPago" value="<?php echo $proveedor['info']->condicion_pago ?>">
                        <!-- <select name="condicionPago" class="span12" id="condicionPago" data-next="plazoCredito|dcuenta">
                          <option value="co" <?php echo set_select('condicionPago', 'co'); ?>>Contado</option>
                          <option value="cr" <?php echo set_select('condicionPago', 'cr'); ?>>Credito</option>
                        </select> -->
                      </div>
                    </div>
                  </div>
                  <div class="span4" id="grup_plazo_credito" style="display: none;">
                    <div class="control-group">
                      <div class="controls span9">
                        Plazo de Crédito<input type="text" name="plazoCredito" class="span12" id="plazoCredito" value="<?php echo $proveedor['info']->dias_credito ?>">
                      </div>
                    </div>
                  </div>
                  <div class="span4">
                    <div class="control-group">
                      <div class="controls span9">
                        XML<input type="file" name="xml" class="span12" id="xml" data-uniform="false" accept="text/xml">
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row-fluid" id="group_pago_contado" style="display: none;">
                  <div class="span3">
                    <div class="control-group">
                      <div class="controls span9">
                        Cuenta Bancaria
                        <select name="dcuenta" class="span12" id="dcuenta">
                        <?php
                        foreach ($cuentas['cuentas'] as $key => $value) {
                        ?>
                            <option value="<?php echo $value->id_cuenta; ?>" <?php echo set_select('dcuenta', $value->id_cuenta); ?>><?php echo $value->alias.' - '.String::formatoNumero($value->saldo); ?></option>
                        <?php
                        }
                        ?>
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="span3">
                    <div class="control-group">
                      <div class="controls span9">
                        Referencia <input type="text" name="dreferencia" class="span12" id="dreferencia" value="<?php echo set_value('dreferencia', ''); ?>" maxlength="10">
                      </div>
                    </div>
                  </div>
                  <div class="span3">
                    <div class="control-group">
                      <div class="controls span9">
                        Metodo de pago
                        <select name="fmetodo_pago" class="span12" id="fmetodo_pago">
                        <?php  foreach ($metods_pago as $key => $value) {
                        ?>
                            <option value="<?php echo $value['value']; ?>"><?php echo $value['nombre']; ?></option>
                        <?php
                        }?>
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="span3" id="cuenta_proveedor">
                    <div class="control-group">
                      <div class="controls span9">
                        Cuenta Proveedor
                        <select name="fcuentas_proveedor" class="span12" id="fcuentas_proveedor">
                        <?php  foreach ($cuentas_proveedor as $key => $value) {
                        ?>
                            <option value="<?php echo $value->id_cuenta; ?>"><?php echo $value->full_alias; ?></option>
                        <?php
                        }?>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="span4 pull-right">
                  <div class="control-group">
                    <div class="controls span9">
                      <div class="well span12">
                          <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row-fluid">  <!-- Box Productos -->
                  <div class="box span12">
                    <div class="box-header well" data-original-title>
                      <h2><i class="icon-barcode"></i> Productos</h2>
                      <div class="box-icon">
                        <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                      </div>
                    </div><!--/box-header -->
                    <div class="box-content">
                      <div class="row-fluid">
                        <div class="span12 mquit">
                          <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                            <thead>
                              <tr>
                                <th>NOMBRE</th>
                                <th>CANT.</th>
                                <th>P.U.</th>
                                <th>IVA</th>
                                <th>IEPS (%)</th>
                                <th>RET 4%</th>
                                <th>IMPORTE</th>
                              </tr>
                            </thead>
                            <tbody>
                                  <?php
                                        $subtotal = $iva = $ieps = $total = $retencion = 0;
                                       foreach ($productos as $key => $prod) {
                                          $subtotal += $prod->importe;
                                          $iva      += $prod->iva;
                                          $ieps     += $prod->ieps;
                                          $retencion+= $prod->retencion_iva;
                                          $total    += $prod->total;

                                          $cantidad = $prod->cantidad;
                                          $pu       = $prod->precio_unitario;
                                        ?>
                                       <tr>
                                         <td style="">
                                            <?php echo $prod->descripcion ?>
                                            <input type="hidden" name="concepto[]" value="<?php echo $prod->descripcion ?>" id="concepto" class="span12">
                                            <input type="hidden" name="productoId[]" value="<?php echo $prod->id_producto ?>" id="productoId" class="span12">
                                            <input type="hidden" name="ordenId[]" value="<?php echo $prod->id_orden ?>" id="productoId" class="span12">
                                            <input type="hidden" name="row[]" value="<?php echo $prod->num_row ?>" id="productoId" class="span12">
                                            <input type="hidden" name="prodTipoOrden[]" value="<?php echo $prod->tipo_orden ?>" id="prodTipoOrden" class="span12">
                                         </td>
                                         <td style="width: 65px;">
                                            <?php echo $prod->cantidad.' '.$prod->abreviatura ?>
                                            <input type="hidden" name="cantidad[]" value="<?php echo $prod->cantidad ?>" id="cantidad" class="span12 vpositive" min="1">
                                         </td>
                                         <td style="width: 90px;">
                                             <input type="text" name="valorUnitario[]" value="<?php echo set_value('valorUnitario[]', $pu) ?>" id="valorUnitario" class="span12 vpositive">
                                         </td>
                                         <td style="width: 66px;">
                                             <select name="traslado[]" id="traslado" class="span12">
                                               <option value="0"  <?php echo (isset($_POST['trasladoPorcent']) ? ($_POST['trasladoPorcent'][$key] === '0' ? 'selected' : '') : ($prod->porcentaje_iva === '0' ? 'selected' : '')) ?>>0%</option>
                                               <option value="11" <?php echo (isset($_POST['trasladoPorcent']) ? ($_POST['trasladoPorcent'][$key] === '11' ? 'selected' : '') : ($prod->porcentaje_iva === '11' ? 'selected' : '')) ?>>11%</option>
                                               <option value="16" <?php echo (isset($_POST['trasladoPorcent']) ? ($_POST['trasladoPorcent'][$key] === '16' ? 'selected' : '') : ($prod->porcentaje_iva === '16' ? 'selected' : '')) ?>>16%</option>
                                             </select>
                                             <input type="hidden" name="trasladoTotal[]" value="<?php echo set_value('trasladoTotal[]', $prod->iva) ?>" id="trasladoTotal" class="span12">
                                             <input type="hidden" name="trasladoPorcent[]" value="<?php echo set_value('trasladoPorcent[]', $prod->porcentaje_iva) ?>" id="trasladoPorcent" class="span12">
                                         </td>
                                         <td style="width: 66px;">
                                           <input type="text" name="iepsPorcent[]" value="<?php echo isset($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : $prod->porcentaje_ieps ?>" id="iepsPorcent" class="span12">
                                           <input type="hidden" name="iepsTotal[]" value="<?php echo isset($_POST['iepsTotal'][$key]) ? $_POST['iepsTotal'][$key] : $prod->ieps ?>" id="iepsTotal" class="span12">
                                         </td>
                                         <td style="width: 66px;">
                                             <input type="text" name="retTotal[]" value="<?php echo isset($_POST['retTotal'][$key]) ? $_POST['retTotal'][$key]: $prod->retencion_iva ?>" id="retTotal" class="span12" readonly>
                                         </td>
                                         <td style="">
                                             <span><?php echo String::formatoNumero(isset($_POST['importe'][$key]) ? $_POST['importe'][$key] : $prod->importe) ?></span>
                                             <input type="hidden" name="importe[]" value="<?php echo set_value('importe[]', $prod->importe) ?>" id="importe" class="span12 vpositive">
                                             <input type="hidden" name="total[]" value="<?php echo set_value('total[]', $prod->total) ?>" id="total" class="span12 vpositive">
                                         </td>
                                       </tr>
                                  <?php  } ?>

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
                              <textarea name="totalLetra" rows="5" class="nokey" style="width:98%;max-width:98%;" id="totalLetra" readonly><?php echo set_value('totalLetra', String::num2letras($total));?></textarea>
                          </td>
                        </tr>
                        <tr>
                          <td><em>Subtotal</em></td>
                          <td id="importe-format"><?php echo String::formatoNumero(set_value('totalImporte', $subtotal))?></td>
                          <input type="hidden" name="totalImporte" id="totalImporte" value="<?php echo set_value('totalImporte', $subtotal); ?>">
                        </tr>
                        <tr>
                          <td>IVA</td>
                          <td id="traslado-format"><?php echo String::formatoNumero(set_value('totalImpuestosTrasladados', $iva))?></td>
                          <input type="hidden" name="totalImpuestosTrasladados" id="totalImpuestosTrasladados" value="<?php echo set_value('totalImpuestosTrasladados', $iva); ?>">
                        </tr>
                        <tr>
                          <td>IEPS</td>
                          <td id="ieps-format"><?php echo String::formatoNumero(set_value('totalIeps', $ieps))?></td>
                          <input type="hidden" name="totalIeps" id="totalIeps" value="<?php echo set_value('totalIeps', $ieps); ?>">
                        </tr>
                        <tr>
                          <td>RET.</td>
                          <td id="retencion-format"><?php echo String::formatoNumero(set_value('totalRetencion', $retencion))?></td>
                          <input type="hidden" name="totalRetencion" id="totalRetencion" value="<?php echo set_value('totalRetencion', $retencion); ?>">
                        </tr>
                        <tr style="font-weight:bold;font-size:1.2em;">
                          <td>TOTAL</td>
                          <td id="total-format"><?php echo String::formatoNumero(set_value('totalOrden', $total))?></td>
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

      <!-- Bloque de alertas -->
      <?php if(isset($frm_errors)){
        if($frm_errors['msg'] != ''){
      ?>
      <script type="text/javascript" charset="UTF-8">
        $(document).ready(function(){
          noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
        });

        <?php if(isset($reload)) { ?>
          setTimeout(function(){
            <?php
                if (isset($id_movimiento{0}))
                  echo "window.open(base_url+'panel/banco/cheque?id='+{$id_movimiento}, 'Print cheque');";
            ?>

            window.parent.location.reload();
          },1500)
        <?php } ?>

      </script>
      <?php }
      }?>
      <!-- Bloque de alertas -->

    </div><!--/fluid-row-->
  </div><!--/.fluid-container-->

  <div class="clear"></div>
</body>
</html>