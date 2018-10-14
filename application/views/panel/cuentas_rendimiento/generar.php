<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="es" class="no-js"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title><?php echo $seo['titulo'];?></title>
  <meta name="description" content="<?php echo $seo['titulo'];?>">
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

  <div id="content" class="container-fluid" style="padding-right: 0;">
    <div class="row-fluid">
      <!--[if lt IE 7]>
        <div class="alert alert-info">
          <button type="button" class="close" data-dismiss="alert">×</button>
          <p>Usted está usando un navegador desactualizado. <a href="http://browsehappy.com/">Actualice su navegador</a> o <a href="http://www.google.com/chromeframe/?redirect=true">instale Google Chrome Frame</a> para experimentar mejor este sitio.</p>
        </div>
      <![endif]-->

      <div class="span12">

      <?php
      $ffecha = isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d');
      $farea = isset($_GET['farea']) ? $_GET['farea'] : 2;
      ?>

        <form class="form-horizontal" action="<?php echo base_url('panel/cuentas_rendimiento/cargar').'?ffecha='.$ffecha.'&farea='.$farea; ?>" method="POST" id="frmcajachica">
          <!-- Header -->
          <div class="span12" style="margin: 10px 0 0 0;">
            <div class="row-fluid">
              <div class="span4" style="text-align: center;">
                <img alt="logo" src="<?php echo base_url(); ?>/application/images/logo.png" height="54">
              </div>
              <div class="span2" style="text-align: right;">
                <div class="row-fluid">
                  <div class="span12">Fecha <input type="date" name="fecha_caja_chica" value="<?php echo set_value('fecha_caja_chica', $ffecha) ?>" id="fecha_caja" class="input-medium" readonly>
                    <input type="hidden" name="id_area" value="<?php echo set_value('id_area', $farea) ?>" id="id_area" class="input-medium" readonly>
                  </div>
                </div>
              </div>
              <div class="span4">
                <div class="row-fluid">

                  <?php if (true){ ?>
                    <div class="span4"><input type="submit" class="btn btn-success btn-large span12" value="Guardar"></div>
                  <?php } ?>

                    <div class="span4"><a href="<?php echo base_url('panel/cuentas_rendimiento/print_rendimiento?'.MyString::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Ingresos -->
          <div class="row-fluid">
            <div class="span6">
              <div class="row-fluid">
                <div class="span12">

                    <!-- Facturas del dia -->
                    <div class="row-fluid" style="margin-top: 5px;">
                      <div class="span12">
                        <div class="row-fluid">

                          <div class="span12">
                            <div class="row-fluid">
                              <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">REPORTE CAJA "COMPRAS LIMON"</div> -->
                              <div class="row-fluid">
                                <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">INGRESOS <button type="button" class="btn btn-success" id="btn-add-ingreso" style="padding: 2px 7px 2px;"><i class="icon-plus"></i></button></div> -->
                                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-ingresos">
                                    <thead>
                                      <tr>
                                        <th colspan="8">INGRESOS</th>
                                      </tr>
                                      <tr>
                                        <th>REMISION</th>
                                        <th>CLIENTE</th>
                                        <th>CLASIF</th>
                                        <th>CODIGO</th>
                                        <th>BULTOS</th>
                                        <th>KGS</th>
                                        <th>PRECIO</th>
                                        <th>IMPORTE</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                        $total_facturas = $total_bultos = $total_kilos = 0;
                                        foreach ($rpt['facturas'] as $key => $factura) {
                                          $total_facturas += floatval($factura->importe);
                                          $total_bultos += floatval($factura->cantidad);
                                          $total_kilos += floatval($factura->kgs);
                                        ?>
                                        <tr>
                                          <td><?php echo $factura->serie.$factura->folio ?></td>
                                          <td><?php echo $factura->nombre_fiscal ?></td>
                                          <td><?php echo $factura->ccodigo ?></td>
                                          <td><?php echo $factura->ucodigo ?></td>
                                          <td style="text-align: right;"><?php echo MyString::formatoNumero($factura->cantidad, 2, '') ?></td>
                                          <td style="text-align: right;"><?php echo MyString::formatoNumero($factura->kgs, 2, '') ?></td>
                                          <td style="text-align: right;"><?php echo MyString::formatoNumero($factura->precio_unitario, 2, '$') ?></td>
                                          <td style="text-align: right;"><?php echo MyString::formatoNumero($factura->importe, 2, '$') ?></td>
                                        </tr>
                                      <?php } ?>
                                    </tbody>
                                    <tbody>
                                      <tr>
                                        <td colspan="4">
                                          <input type="hidden" name="total_bultos" value="<?php echo $total_bultos ?>" id="total_bultos">
                                          <input type="hidden" name="total_kilos" value="<?php echo $total_kilos ?>" id="total_kilos">
                                          <input type="hidden" name="total_facturas" value="<?php echo $total_facturas ?>" id="total_facturas">
                                        </td>
                                        <td style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($total_bultos, 2, '') ?></td>
                                        <td style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($total_kilos, 2, '') ?></td>
                                        <td style="text-align: right; font-weight: bold;"></td>
                                        <td style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($total_facturas, 2, '$') ?></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>

                        </div>
                      </div>
                    </div>
                    <!-- /Facturas del dia -->

                    <!-- Existencia del dia -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                        <select id="unidad_medidas" class="span12" style="display:none;">
                          <?php foreach ($unidades as $key => $u) {?>
                            <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>"><?php echo $u->nombre ?></option>
                          <?php } ?>
                        </select>
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-existencia">
                          <thead>
                            <tr>
                              <th colspan="7">EXISTENCIA DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;margin-right: 2px;"><i class="icon-plus"></i></button></th>
                            </tr>
                            <tr>
                              <th>CLASIF</th>
                              <th>CODIGO</th>
                              <th>BULTOS</th>
                              <th>KGS</th>
                              <th>PRECIO</th>
                              <th>IMPORTE</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              $total_existencias = $total_exis_bultos = $total_exis_kilos = 0;
                              if (isset($_POST['prod_did_prod'])) {
                                foreach ($_POST['prod_did_prod'] as $k => $concepto) {
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="prod_ddescripcion[]" class="span12" value="<?php echo $_POST['prod_ddescripcion'][$k]?>" id="prod_ddescripcion">
                                  <input type="hidden" name="prod_did_prod[]" class="span12" value="<?php echo $_POST['prod_did_prod'][$k]?>" id="prod_did_prod">
                                </td>
                                <td>
                                  <select name="prod_dmedida[]" id="prod_dmedida" class="span12">
                                    <?php $unidad_sel = null;
                                    foreach ($unidades as $key => $u) {
                                      if($_POST['prod_dmedida'][$k] == $u->id_unidad)
                                        $unidad_sel = $u;
                                    ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo $_POST['prod_dmedida'][$k] == $u->id_unidad ? 'selected' : '' ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <?php
                                  $total_existencias += $_POST['prod_importe'][$k];
                                  $total_exis_bultos += $_POST['prod_bultos'][$k];
                                  $total_exis_kilos  += $_POST['prod_kilos'][$k];
                                ?>
                                <td class="cporte">
                                  <input type="text" name="prod_bultos[]" value="<?php echo $_POST['prod_bultos'][$k] ?>" id="prod_bultos" class="span12 vpositive" style="width: 80px;">
                                </td>
                                <td class="cporte">
                                  <input type="text" name="prod_kilos[]" value="<?php echo $_POST['prod_kilos'][$k] ?>" id="prod_kilos" class="span12" style="width: 80px;" readonly>
                                </td>
                                <td class="cporte">
                                  <input type="text" name="prod_precio[]" value="<?php echo $_POST['prod_precio'][$k] ?>" id="prod_precio" class="span12 vpositive" style="width: 80px;">
                                </td>
                                <td class="cporte">
                                  <input type="text" name="prod_importe[]" value="<?php echo $_POST['prod_importe'][$k] ?>" id="prod_importe" class="span12" style="width: 80px;" readonly>
                                </td>
                                <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                              </tr>
                            <?php }
                              } else {

                              foreach ($rpt['existencia'] as $existencia) {
                              ?>
                              <tr>
                                <td>
                                  <input type="text" name="prod_ddescripcion[]" class="span12" value="<?php echo $existencia->cnombre ?>" id="prod_ddescripcion">
                                  <input type="hidden" name="prod_did_prod[]" class="span12" value="<?php echo $existencia->id_clasificacion ?>" id="prod_did_prod">
                                </td>
                                <td>
                                  <select name="prod_dmedida[]" id="prod_dmedida" class="span12">
                                    <?php $unidad_sel = null;
                                    foreach ($unidades as $key => $u) {
                                      if($existencia->id_unidad == $u->id_unidad)
                                        $unidad_sel = $u;
                                    ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo $existencia->id_unidad == $u->id_unidad ? 'selected' : '' ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                              <?php

                                $total_existencias += $existencia->importe;
                                $total_exis_bultos += $existencia->bultos;
                                $total_exis_kilos  += $existencia->kgs;
                              ?>
                                <td class="cporte">
                                  <input type="text" name="prod_bultos[]" value="<?php echo $existencia->bultos ?>" id="prod_bultos" class="span12 vpositive" style="width: 80px;">
                                </td>
                                <td class="cporte">
                                  <input type="text" name="prod_kilos[]" value="<?php echo $existencia->kgs ?>" id="prod_kilos" class="span12" style="width: 80px;" readonly>
                                </td>
                                <td class="cporte">
                                  <input type="text" name="prod_precio[]" value="<?php echo $existencia->precio ?>" id="prod_precio" class="span12 vpositive" style="width: 80px;">
                                </td>
                                <td class="cporte">
                                  <input type="text" name="prod_importe[]" value="<?php echo $existencia->importe ?>" id="prod_importe" class="span12" style="width: 80px;" readonly>
                                </td>
                                <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                              </tr>
                            <?php }} ?>

                            <tr class="row-total">
                              <td colspan="2" style="text-align: right; font-weight: bolder;">TOTAL
                                <input type="hidden" name="total_exis_bultos" value="<?php echo $total_exis_bultos ?>" id="total_exis_bultos">
                                <input type="hidden" name="total_exis_kilos" value="<?php echo $total_exis_kilos ?>" id="total_exis_kilos">
                                <input type="hidden" name="total_existencias" value="<?php echo $total_existencias ?>" id="total_existencias">
                              </td>
                              <td style="text-align: right; font-weight: bold;" id="total_exis_bultos_txt"><?php echo MyString::formatoNumero($total_exis_bultos, 2, '') ?></td>
                              <td style="text-align: right; font-weight: bold;" id="total_exis_kilos_txt"><?php echo MyString::formatoNumero($total_exis_kilos, 2, '') ?></td>
                              <td style="text-align: right; font-weight: bold;"></td>
                              <td style="text-align: right; font-weight: bold;" id="total_existencias_txt"><?php echo MyString::formatoNumero($total_existencias, 2, '$') ?></td>
                              <td style="text-align: right; font-weight: bold;"></td>
                            </tr>
                            <tr class="row-suma_total">
                              <td colspan="2" style="text-align: right; font-weight: bolder;">DESCUENTO PARCIAL S/O VENTA
                              </td>
                              <td style="text-align: right; font-weight: bold;"></td>
                              <td style="text-align: right; font-weight: bold;"></td>
                              <td style="text-align: right; font-weight: bold;"></td>
                              <td style="text-align: right; font-weight: bold;">
                              <?php $descuento_parcial_ventas = (isset($rpt['info']->descuento_parcial)? $rpt['info']->descuento_parcial: 0); ?>
                                <input type="text" name="descuento_parcial_ventas" value="<?php echo $descuento_parcial_ventas ?>" id="descuento_parcial_ventas" class="span12 vpositive" style="width: 80px;"></td>
                              <td style="text-align: right; font-weight: bold;"></td>
                            </tr>
                            <tr class="row-suma_total">
                              <td colspan="2" style="text-align: right; font-weight: bolder;">SUMA TOTALES</td>
                              <td style="text-align: right; font-weight: bold;" id="suma_totales_bultos">
                                <?php echo MyString::formatoNumero($total_exis_bultos+$total_bultos, 2, '') ?>
                              </td>
                              <td style="text-align: right; font-weight: bold;" id="suma_totales_kilos">
                                <?php echo MyString::formatoNumero($total_exis_kilos+$total_kilos, 2, '') ?>
                              </td>
                              <td style="text-align: right; font-weight: bold;"></td>
                              <td style="text-align: right; font-weight: bold;" id="suma_totales_existencias">
                                <?php echo MyString::formatoNumero($total_existencias+$total_facturas-$descuento_parcial_ventas, 2, '$') ?>
                              </td>
                              <td style="text-align: right; font-weight: bold;"></td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!-- /Existencia del dia -->

                    <!-- Otros ingresos del dia -->
                    <div class="row-fluid" style="margin-top: 5px;">
                      <div class="span12">
                        <div class="row-fluid">

                          <div class="span12">
                            <div class="row-fluid">
                              <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">REPORTE CAJA "COMPRAS LIMON"</div> -->
                              <div class="row-fluid">
                                <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">INGRESOS <button type="button" class="btn btn-success" id="btn-add-ingreso" style="padding: 2px 7px 2px;"><i class="icon-plus"></i></button></div> -->
                                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-otringresos">
                                    <thead>
                                      <tr>
                                        <th colspan="5">OTROS INGRESOS</th>
                                      </tr>
                                      <tr>
                                        <th>CLASIF</th>
                                        <th>BULTOS</th>
                                        <th>UNIDAD</th>
                                        <th>PRECIO</th>
                                        <th>IMPORTE</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                        $total_otsingr = $total_otsingr_bultos = 0;
                                        foreach ($rpt['otros_ingresos'] as $key => $otr_ingreso) {
                                          $total_otsingr += floatval($otr_ingreso->importe);
                                          $total_otsingr_bultos += floatval($otr_ingreso->cantidad);
                                        ?>
                                        <tr>
                                          <td><?php echo "({$otr_ingreso->ccodigo}) ".$otr_ingreso->cnombre ?></td>
                                          <td style="text-align: right;"><?php echo MyString::formatoNumero($otr_ingreso->cantidad, 2, '') ?></td>
                                          <td><?php echo $otr_ingreso->unidad ?></td>
                                          <td style="text-align: right;"><?php echo MyString::formatoNumero($otr_ingreso->precio_unitario, 2, '$') ?></td>
                                          <td style="text-align: right;"><?php echo MyString::formatoNumero($otr_ingreso->importe, 2, '$') ?></td>
                                        </tr>
                                      <?php } ?>
                                    </tbody>
                                    <tbody>
                                      <tr>
                                        <td>
                                          <input type="hidden" name="total_otsingr_bultos" value="<?php echo $total_otsingr_bultos ?>" id="total_otsingr_bultos">
                                          <input type="hidden" name="total_otsingr" value="<?php echo $total_otsingr ?>" id="total_otsingr">
                                        </td>
                                        <td style="text-align: right; font-weight: bold;" id="total_otsingr_bultos_txt"><?php echo MyString::formatoNumero($total_otsingr_bultos, 2, '') ?></td>
                                        <td></td>
                                        <td style="text-align: right; font-weight: bold;"></td>
                                        <td style="text-align: right; font-weight: bold;" id="total_otsingr_txt"><?php echo MyString::formatoNumero($total_otsingr, 2, '$') ?></td>
                                      </tr>
                                    </tbody>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>

                        </div>
                      </div>
                    </div>
                    <!-- /Otros ingresos del dia -->

                    <!-- Total ingresos -->
                    <div class="row-fluid" style="margin-top: 5px;">
                      <div class="span12">
                        <div class="row-fluid">

                          <div class="span12">
                            <div class="row-fluid">
                              <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">REPORTE CAJA "COMPRAS LIMON"</div> -->
                              <div class="row-fluid">
                                <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">INGRESOS <button type="button" class="btn btn-success" id="btn-add-ingreso" style="padding: 2px 7px 2px;"><i class="icon-plus"></i></button></div> -->
                                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-total_ingres">
                                    <thead>
                                      <tr>
                                        <td style="text-align: right; font-weight: bold;">TOTAL DE INGRESOS</td>
                                        <td style="text-align: right; font-weight: bold;" id="total_ingres_bultos">
                                          <?php echo MyString::formatoNumero($total_otsingr_bultos+$total_exis_bultos+$total_bultos, 2, '') ?>
                                        </td>
                                        <td style="text-align: right; font-weight: bold;" id="total_ingres">
                                          <?php echo MyString::formatoNumero($total_otsingr+$total_existencias+$total_facturas-$descuento_parcial_ventas, 2, '$') ?>
                                        </td>
                                      </tr>
                                    </thead>
                                  </table>
                                </div>
                              </div>
                            </div>
                          </div>

                        </div>
                      </div>
                    </div>
                    <!-- /Total ingresos -->


                </div>
              </div>
            </div>

            <div class="span6">

              <!-- Existencia anterior -->
              <div class="row-fluid" style="margin-top: 5px;">
                <div class="span12">
                  <div class="row-fluid">

                    <div class="span12">
                      <div class="row-fluid">
                        <div class="row-fluid">
                          <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                            <table class="table table-striped table-bordered table-hover table-condensed" id="table-exis_anterior">
                              <thead>
                                <tr>
                                  <th colspan="6">EXISTENCIA ANTERIOR</th>
                                </tr>
                                <tr>
                                  <th>CLASIF</th>
                                  <th>CODIGO</th>
                                  <th>BULTOS</th>
                                  <th>KGS</th>
                                  <th>PRECIO</th>
                                  <th>IMPORTE</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                  $total_exis_anterior = $total_exis_anterior_bultos = $total_exis_anterior_kgs = 0;
                                  foreach ($rpt['existencia_anterior'] as $key => $existen_anterior) {
                                    $total_exis_anterior        += floatval($existen_anterior->importe);
                                    $total_exis_anterior_bultos += floatval($existen_anterior->cantidad);
                                    $total_exis_anterior_kgs    += floatval($existen_anterior->kgs);
                                  ?>
                                  <tr>
                                    <td><?php echo $existen_anterior->ccodigo ?></td>
                                    <td><?php echo $existen_anterior->ucodigo ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($existen_anterior->cantidad, 2, '') ?></td>
                                    <td><?php echo MyString::formatoNumero($existen_anterior->kgs, 2, '') ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($existen_anterior->precio, 2, '$') ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($existen_anterior->importe, 2, '$') ?></td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                              <tbody>
                                <tr>
                                  <td>
                                    <input type="hidden" name="total_exis_anterior_bultos" value="<?php echo $total_exis_anterior_bultos ?>" id="total_exis_anterior_bultos">
                                    <input type="hidden" name="total_exis_anterior_kgs" value="<?php echo $total_exis_anterior_kgs ?>" id="total_exis_anterior_kgs">
                                    <input type="hidden" name="total_exis_anterior" value="<?php echo $total_exis_anterior ?>" id="total_exis_anterior">
                                  </td>
                                  <td></td>
                                  <td style="text-align: right; font-weight: bold;" id="total_exis_anterior_bultos_txt"><?php echo MyString::formatoNumero($total_exis_anterior_bultos, 2, '') ?></td>
                                  <td style="text-align: right; font-weight: bold;" id="total_exis_anterior_kgs_txt"><?php echo MyString::formatoNumero($total_exis_anterior_kgs, 2, '') ?></td>
                                  <td></td>
                                  <td style="text-align: right; font-weight: bold;" id="total_exis_anterior_txt"><?php echo MyString::formatoNumero($total_exis_anterior, 2, '$') ?></td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
              <!-- /Existencia anterior -->

              <!-- Compra empacada -->
              <div class="row-fluid">
                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-compra_empa">
                    <thead>
                      <tr>
                        <th colspan="7">COMPRA EMPACADA <button type="button" class="btn btn-success" id="btn-add-compra" style="padding: 2px 7px 2px;margin-right: 2px;"><i class="icon-plus"></i></button></th>
                      </tr>
                      <tr>
                        <th>CLASIF</th>
                        <th>CODIGO</th>
                        <th>BULTOS</th>
                        <th>KGS</th>
                        <th>PRECIO</th>
                        <th>IMPORTE</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        $total_compra_empa = $total_compra_empa_bultos = $total_compra_empa_kilos = 0;
                        if (isset($_POST['compe_did_prod'])) {
                          foreach ($_POST['compe_did_prod'] as $k => $concepto) {
                      ?>
                        <tr>
                          <td>
                            <input type="text" name="compe_ddescripcion[]" class="span12" value="<?php echo $_POST['compe_ddescripcion'][$k]?>" id="compe_ddescripcion">
                            <input type="hidden" name="compe_did_prod[]" class="span12" value="<?php echo $_POST['compe_did_prod'][$k]?>" id="compe_did_prod">
                          </td>
                          <td>
                            <select name="compe_dmedida[]" id="compe_dmedida" class="span12">
                              <?php $unidad_sel = null;
                              foreach ($unidades as $key => $u) {
                                if($_POST['compe_dmedida'][$k] == $u->id_unidad)
                                  $unidad_sel = $u;
                              ?>
                                <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo $_POST['compe_dmedida'][$k] == $u->id_unidad ? 'selected' : '' ?>><?php echo $u->nombre ?></option>
                              <?php } ?>
                            </select>
                          </td>
                          <?php
                            $total_compra_empa        += $_POST['compe_importe'][$k];
                            $total_compra_empa_bultos += $_POST['compe_bultos'][$k];
                            $total_compra_empa_kilos  += $_POST['compe_kilos'][$k];
                          ?>
                          <td class="cporte">
                            <input type="text" name="compe_bultos[]" value="<?php echo $_POST['compe_bultos'][$k] ?>" id="compe_bultos" class="span12 vpositive" style="width: 80px;">
                          </td>
                          <td class="cporte">
                            <input type="text" name="compe_kilos[]" value="<?php echo $_POST['compe_kilos'][$k] ?>" id="compe_kilos" class="span12" style="width: 80px;" readonly>
                          </td>
                          <td class="cporte">
                            <input type="text" name="compe_precio[]" value="<?php echo $_POST['compe_precio'][$k] ?>" id="compe_precio" class="span12 vpositive" style="width: 80px;">
                          </td>
                          <td class="cporte">
                            <input type="text" name="compe_importe[]" value="<?php echo $_POST['compe_importe'][$k] ?>" id="compe_importe" class="span12" style="width: 80px;" readonly>
                          </td>
                          <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                        </tr>
                      <?php }
                        } else {

                        foreach ($rpt['compras_empacadas'] as $comp_emp) {
                        ?>
                        <tr>
                          <td>
                            <input type="text" name="compe_ddescripcion[]" class="span12" value="<?php echo $comp_emp->cnombre ?>" id="compe_ddescripcion">
                            <input type="hidden" name="compe_did_prod[]" class="span12" value="<?php echo $comp_emp->id_clasificacion ?>" id="compe_did_prod">
                          </td>
                          <td>
                            <select name="compe_dmedida[]" id="compe_dmedida" class="span12">
                              <?php $unidad_sel = null;
                              foreach ($unidades as $key => $u) {
                                if($comp_emp->id_unidad == $u->id_unidad)
                                  $unidad_sel = $u;
                              ?>
                                <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo $comp_emp->id_unidad == $u->id_unidad ? 'selected' : '' ?>><?php echo $u->nombre ?></option>
                              <?php } ?>
                            </select>
                          </td>
                        <?php

                          $total_compra_empa        += $comp_emp->importe;
                          $total_compra_empa_bultos += $comp_emp->bultos;
                          $total_compra_empa_kilos  += $comp_emp->kgs;
                        ?>
                          <td class="cporte">
                            <input type="text" name="compe_bultos[]" value="<?php echo $comp_emp->bultos ?>" id="compe_bultos" class="span12 vpositive" style="width: 80px;">
                          </td>
                          <td class="cporte">
                            <input type="text" name="compe_kilos[]" value="<?php echo $comp_emp->kgs ?>" id="compe_kilos" class="span12" style="width: 80px;" readonly>
                          </td>
                          <td class="cporte">
                            <input type="text" name="compe_precio[]" value="<?php echo $comp_emp->precio ?>" id="compe_precio" class="span12 vpositive" style="width: 80px;">
                          </td>
                          <td class="cporte">
                            <input type="text" name="compe_importe[]" value="<?php echo $comp_emp->importe ?>" id="compe_importe" class="span12" style="width: 80px;" readonly>
                          </td>
                          <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                        </tr>
                      <?php }} ?>

                      <tr class="row-total">
                        <td colspan="2" style="text-align: right; font-weight: bolder;">TOTAL
                          <input type="hidden" name="total_compra_empa_bultos" value="<?php echo $total_compra_empa_bultos ?>" id="total_compra_empa_bultos">
                          <input type="hidden" name="total_compra_empa_kilos" value="<?php echo $total_compra_empa_kilos ?>" id="total_compra_empa_kilos">
                          <input type="hidden" name="total_compra_empa" value="<?php echo $total_compra_empa ?>" id="total_compra_empa">
                        </td>
                        <td style="text-align: right; font-weight: bold;" id="total_compra_empa_bultos_txt"><?php echo MyString::formatoNumero($total_compra_empa_bultos, 2, '') ?></td>
                        <td style="text-align: right; font-weight: bold;" id="total_compra_empa_kilos_txt"><?php echo MyString::formatoNumero($total_compra_empa_kilos, 2, '') ?></td>
                        <td style="text-align: right; font-weight: bold;"></td>
                        <td style="text-align: right; font-weight: bold;" id="total_compra_empa_txt"><?php echo MyString::formatoNumero($total_compra_empa, 2, '$') ?></td>
                        <td style="text-align: right; font-weight: bold;"></td>
                      </tr>
                      <tr class="row-suma_total">
                        <td colspan="2" style="text-align: right; font-weight: bolder;">SUMA TOTALES</td>
                        <td style="text-align: right; font-weight: bold;" id="suma_total_compra_empa_bultos">
                          <?php echo MyString::formatoNumero($total_exis_anterior_bultos+$total_compra_empa_bultos, 2, '') ?>
                        </td>
                        <td style="text-align: right; font-weight: bold;" id="suma_total_compra_empa_kilos">
                          <?php echo MyString::formatoNumero($total_exis_anterior_kgs+$total_compra_empa_kilos, 2, '') ?>
                        </td>
                        <td style="text-align: right; font-weight: bold;"></td>
                        <td style="text-align: right; font-weight: bold;" id="suma_total_compra_empa_existencias">
                          <?php echo MyString::formatoNumero($total_exis_anterior+$total_compra_empa, 2, '$') ?>
                        </td>
                        <td style="text-align: right; font-weight: bold;"></td>
                      </tr>

                      <tr class="row-suma_total">
                        <td colspan="2" style="text-align: right; font-weight: bolder;">VENTA NETA DEL DIA</td>
                        <td style="text-align: right; font-weight: bold;" id="suma_total_compra_empa_bultos">
                          <?php echo MyString::formatoNumero(($total_exis_bultos+$total_bultos)-($total_exis_anterior_bultos+$total_compra_empa_bultos), 2, '') ?>
                        </td>
                        <td style="text-align: right; font-weight: bold;" id="suma_total_compra_empa_kilos">
                          <?php echo MyString::formatoNumero(($total_exis_kilos+$total_kilos)-($total_exis_anterior_kgs+$total_compra_empa_kilos), 2, '') ?>
                        </td>
                        <td style="text-align: right; font-weight: bold;"></td>
                        <td style="text-align: right; font-weight: bold;" id="suma_total_compra_empa_existencias">
                          <?php echo MyString::formatoNumero(($total_otsingr+$total_existencias+$total_facturas-$descuento_parcial_ventas)-($total_exis_anterior+$total_compra_empa), 2, '$') ?>
                        </td>
                        <td style="text-align: right; font-weight: bold;"></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!-- /Compra empacada -->

              <!-- Compras limon tecoman -->
              <div class="row-fluid" style="margin-top: 5px;">
                <div class="span12">
                  <div class="row-fluid">

                    <div class="span12">
                      <div class="row-fluid">
                        <div class="row-fluid">
                          <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                            <table class="table table-striped table-bordered table-hover table-condensed" id="table-compra_tecoman">
                              <thead>
                                <tr>
                                  <th colspan="5">COMPRAS LIMON TECOMAN</th>
                                </tr>
                                <tr>
                                  <th>NOMBRE</th>
                                  <th>BULTOS</th>
                                  <th>KGS</th>
                                  <th>PRECIO</th>
                                  <th>IMPORTE</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                  $total_compra_bascula = $total_compra_bascula_bultos = $total_compra_bascula_kgs = 0;
                                  foreach ($rpt['compras_bascula'] as $key => $compra) {
                                    $total_compra_bascula        += floatval($compra->importe);
                                    $total_compra_bascula_bultos += floatval($compra->cajas);
                                    $total_compra_bascula_kgs    += floatval($compra->kilos);
                                  ?>
                                  <tr>
                                    <td><?php echo $compra->nombre ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($compra->cajas, 2, '') ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($compra->kilos, 2, '') ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($compra->precio, 2, '$') ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($compra->importe, 2, '$') ?></td>
                                  </tr>
                                <?php }
                                  $total_compra_bascula        += isset($rpt['compras_bascula_bonifica']->importe)? floatval($rpt['compras_bascula_bonifica']->importe): 0;
                                  // $total_compra_bascula_bultos += isset($rpt['compras_bascula_bonifica']->cajas)? floatval($rpt['compras_bascula_bonifica']->cajas): 0;
                                  // $total_compra_bascula_kgs    += isset($rpt['compras_bascula_bonifica']->kilos)? floatval($rpt['compras_bascula_bonifica']->kilos): 0;

                                  if (isset($rpt['compras_bascula_bonifica']->nombre)) {
                                ?>

                                  <tr>
                                    <td><?php echo $rpt['compras_bascula_bonifica']->nombre ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($rpt['compras_bascula_bonifica']->cajas, 2, '') ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($rpt['compras_bascula_bonifica']->kilos, 2, '') ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($rpt['compras_bascula_bonifica']->precio, 2, '$') ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($rpt['compras_bascula_bonifica']->importe, 2, '$') ?></td>
                                  </tr>
                                <?php } ?>
                                <?php if(count($rpt['ingresos_movimientos_bascula']) > 0) {
                                  foreach ($rpt['ingresos_movimientos_bascula'] as $key => $compra) {
                                    $total_compra_bascula        += floatval($compra->importe);
                                    $total_compra_bascula_bultos += floatval($compra->cajas);
                                    $total_compra_bascula_kgs    += floatval($compra->kilos);
                                  ?>
                                  <tr>
                                    <td>INGRESOS X MOVIMIENTOS</td>
                                    <td style="text-align: right;"><input type="text" name="ingresos_movimientos_bascula_caja[]" value="<?php echo $compra->cajas ?>" id="ingresos_movimientos_bascula_caja" class="span12 vpositive"></td>
                                    <td style="text-align: right;"><input type="text" name="ingresos_movimientos_bascula_kilos[]" value="<?php echo $compra->kilos ?>" id="ingresos_movimientos_bascula_kilos" class="span12 vpositive"></td>
                                    <td style="text-align: right;"><input type="text" name="ingresos_movimientos_bascula_precio[]" value="<?php echo $compra->precio ?>" id="ingresos_movimientos_bascula_precio" class="span12 vpositive"></td>
                                    <td style="text-align: right;"><input type="text" name="ingresos_movimientos_bascula_importe[]" value="<?php echo $compra->importe ?>" id="ingresos_movimientos_bascula_importe" class="span12 vpositive"></td>
                                  </tr>
                                <?php }
                                  } else { ?>
                                  <tr>
                                    <td>INGRESOS X MOVIMIENTOS</td>
                                    <td style="text-align: right;"><input type="text" name="ingresos_movimientos_bascula_caja[]" value="" id="ingresos_movimientos_bascula_caja" class="span12 vpositive"></td>
                                    <td style="text-align: right;"><input type="text" name="ingresos_movimientos_bascula_kilos[]" value="" id="ingresos_movimientos_bascula_kilos" class="span12 vpositive"></td>
                                    <td style="text-align: right;"><input type="text" name="ingresos_movimientos_bascula_precio[]" value="" id="ingresos_movimientos_bascula_precio" class="span12 vpositive"></td>
                                    <td style="text-align: right;"><input type="text" name="ingresos_movimientos_bascula_importe[]" value="" id="ingresos_movimientos_bascula_importe" class="span12 vpositive"></td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                              <tbody>
                                <tr>
                                  <td>
                                    <input type="hidden" name="total_compra_bascula_bultos" value="<?php echo $total_compra_bascula_bultos ?>" id="total_compra_bascula_bultos" data-value="<?php echo $total_compra_bascula_bultos ?>">
                                    <input type="hidden" name="total_compra_bascula_kgs" value="<?php echo $total_compra_bascula_kgs ?>" id="total_compra_bascula_kgs" data-value="<?php echo $total_compra_bascula_kgs ?>">
                                    <input type="hidden" name="total_compra_bascula" value="<?php echo $total_compra_bascula ?>" id="total_compra_bascula" data-value="<?php echo $total_compra_bascula ?>">
                                  </td>
                                  <td style="text-align: right; font-weight: bold;" id="total_compra_bascula_bultos_txt"><?php echo MyString::formatoNumero($total_compra_bascula_bultos, 2, '') ?></td>
                                  <td style="text-align: right; font-weight: bold;" id="total_compra_bascula_kgs_txt"><?php echo MyString::formatoNumero($total_compra_bascula_kgs, 2, '') ?></td>
                                  <td></td>
                                  <td style="text-align: right; font-weight: bold;" id="total_compra_bascula_txt"><?php echo MyString::formatoNumero($total_compra_bascula, 2, '$') ?></td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
              <!-- /Compras limon tecoman -->

              <!-- Otros egresos apatzingan -->
              <div class="row-fluid">
                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-apatzin">
                    <thead>
                      <tr>
                        <th colspan="5">OTROS EGRESOS DE APATZINGAN <button type="button" class="btn btn-success" id="btn-add-apatzin" style="padding: 2px 7px 2px;margin-right: 2px;"><i class="icon-plus"></i></button></th>
                      </tr>
                      <tr>
                        <th>NOMBRE</th>
                        <th>UNIDAD</th>
                        <th>PRECIO</th>
                        <th>IMPORTE</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        $total_apatzin = 0;
                        if (isset($_POST['apatzin_did_prod'])) {
                          foreach ($_POST['apatzin_did_prod'] as $k => $concepto) {
                      ?>
                        <tr>
                          <td>
                            <input type="text" name="apatzin_ddescripcion[]" class="span12" value="<?php echo $_POST['apatzin_ddescripcion'][$k]?>" id="apatzin_ddescripcion">
                          </td>
                          <td>
                            <input type="text" name="apatzin_dmedida[]" class="span12" value="<?php echo $_POST['apatzin_dmedida'][$k]?>" id="apatzin_dmedida">
                          </td>
                          <?php
                            $total_apatzin += $_POST['apatzin_importe'][$k];
                          ?>
                          <td class="cporte">
                            <input type="text" name="apatzin_precio[]" value="<?php echo $_POST['apatzin_precio'][$k] ?>" id="apatzin_precio" class="span12 vpositive" style="width: 80px;">
                          </td>
                          <td class="cporte">
                            <input type="text" name="apatzin_importe[]" value="<?php echo $_POST['apatzin_importe'][$k] ?>" id="apatzin_importe" class="span12" style="width: 80px;" readonly>
                          </td>
                          <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                        </tr>
                      <?php }
                        } else {

                        foreach ($rpt['compras_apatzingan'] as $apatzin) {
                        ?>
                        <tr>
                          <td>
                            <input type="text" name="apatzin_ddescripcion[]" class="span12" value="<?php echo $apatzin->nombre ?>" id="apatzin_ddescripcion">
                          </td>
                          <td>
                            <input type="text" name="apatzin_dmedida[]" class="span12" value="<?php echo $apatzin->unidad ?>" id="apatzin_dmedida">
                          </td>
                        <?php
                          $total_apatzin += $apatzin->importe;
                        ?>
                          <td class="cporte">
                            <input type="text" name="apatzin_precio[]" value="<?php echo $apatzin->precio ?>" id="apatzin_precio" class="span12 vpositive" style="width: 80px;">
                          </td>
                          <td class="cporte">
                            <input type="text" name="apatzin_importe[]" value="<?php echo $apatzin->importe ?>" id="apatzin_importe" class="span12" style="width: 80px;" readonly>
                          </td>
                          <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                        </tr>
                      <?php }} ?>

                      <tr class="row-total">
                        <td colspan="2" style="text-align: right; font-weight: bolder;">TOTAL
                          <input type="hidden" name="total_apatzin" value="<?php echo $total_apatzin ?>" id="total_apatzin">
                        </td>
                        <td style="text-align: right; font-weight: bold;"></td>
                        <td style="text-align: right; font-weight: bold;" id="total_apatzin_txt"><?php echo MyString::formatoNumero($total_apatzin, 2, '$') ?></td>
                        <td style="text-align: right; font-weight: bold;"></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
              <!-- /Otros egresos apatzingan -->

              <!-- Costo de venta -->
              <div class="row-fluid" style="margin-top: 5px;">
                <div class="span12">
                  <div class="row-fluid">

                    <div class="span12">
                      <div class="row-fluid">
                        <div class="row-fluid">
                          <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                            <table class="table table-striped table-bordered table-hover table-condensed" id="table-costo_venta">
                              <thead>
                                <tr>
                                  <th colspan="6">COSTO DE VENTA</th>
                                </tr>
                                <tr>
                                  <th>CODIGO</th>
                                  <th>KGS/BULTOS</th>
                                  <th>BULTOS</th>
                                  <th>KGS</th>
                                  <th>PRECIO</th>
                                  <th>IMPORTE</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                  $total_costo_venta = $total_costo_venta_bultos = $total_costo_venta_kgs = 0;
                                  foreach ($rpt['costo_venta'] as $key => $costo_venta) {
                                    $importe = $costo_venta->bultos*$costo_venta->precio;
                                    $total_costo_venta        += $importe;
                                    $total_costo_venta_bultos += floatval($costo_venta->bultos);
                                    $total_costo_venta_kgs    += floatval($costo_venta->kilos);
                                  ?>
                                  <tr>
                                    <td><?php echo $costo_venta->ucodigo ?></td>
                                    <td><?php echo $costo_venta->kilos/$costo_venta->bultos ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($costo_venta->bultos, 2, '') ?></td>
                                    <td><?php echo MyString::formatoNumero($costo_venta->kilos, 2, '') ?></td>
                                    <td style="text-align: right;">
                                      <input type="text" name="costo_venta_precio[]" value="<?php echo $costo_venta->precio ?>" id="costo_venta_precio"
                                        data-bultos="<?php echo $costo_venta->bultos ?>" data-kilos="<?php echo $costo_venta->kilos ?>" class="span12 vpositive" style="width: 80px;">
                                      <input type="hidden" name="costo_venta_id_unidad[]" value="<?php echo $costo_venta->id_unidad ?>" id="costo_venta_id_unidad" class="span12 vpositive" style="width: 80px;">
                                    </td>
                                    <td id="costo_venta_importe" style="text-align: right;"><?php echo MyString::formatoNumero($importe, 2, '$') ?></td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                              <tbody>
                                <tr>
                                  <td>
                                    <input type="hidden" name="total_costo_venta_bultos" value="<?php echo $total_costo_venta_bultos ?>" id="total_costo_venta_bultos">
                                    <input type="hidden" name="total_costo_venta_kgs" value="<?php echo $total_costo_venta_kgs ?>" id="total_costo_venta_kgs">
                                    <input type="hidden" name="total_costo_venta" value="<?php echo $total_costo_venta ?>" id="total_costo_venta">
                                  </td>
                                  <td></td>
                                  <td style="text-align: right; font-weight: bold;" id="total_costo_venta_bultos_txt"><?php echo MyString::formatoNumero($total_costo_venta_bultos, 2, '') ?></td>
                                  <td style="text-align: right; font-weight: bold;" id="total_costo_venta_kgs_txt"><?php echo MyString::formatoNumero($total_costo_venta_kgs, 2, '') ?></td>
                                  <td></td>
                                  <td style="text-align: right; font-weight: bold;" id="total_costo_venta_txt"><?php echo MyString::formatoNumero($total_costo_venta, 2, '$') ?></td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
              <!-- /Costo de venta -->

              <!-- Industrial proceso -->
              <div class="row-fluid" style="margin-top: 5px;">
                <div class="span12">
                  <div class="row-fluid">

                    <div class="span12">
                      <div class="row-fluid">
                        <div class="row-fluid">
                          <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                            <table class="table table-striped table-bordered table-hover table-condensed" id="table-industrial">
                              <thead>
                                <tr>
                                  <th colspan="5">INDUSTRIAL PROCESO</th>
                                </tr>
                                <tr>
                                  <th>KGS</th>
                                  <th>PRECIO</th>
                                  <th>IMPORTE</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                  // $total_industrial_bultos = abs($total_bultos+$total_exis_bultos-$total_exis_anterior_bultos-$total_compra_empa_bultos-$total_compra_bascula_bultos);
                                  $total_industrial_kilos = abs(($total_kilos+$total_exis_kilos)-($total_exis_anterior_kgs+$total_compra_empa_kilos+$total_compra_bascula_kgs));
                                  $total_industrial_precio = isset($rpt['industrial']->precio)? $rpt['industrial']->precio : 0;
                                  $total_industrial = $total_industrial_precio*$total_industrial_kilos;

                                  $total_porsn_kilos = $total_costo_venta_kgs+$total_industrial_kilos;
                                ?>
                                <tr>
                                  <td style="text-align: right; font-weight: bold;" id="total_industrial_kilos_txt"><?php echo MyString::formatoNumero($total_industrial_kilos, 2, '') ?></td>
                                  <td style="text-align: right;">
                                    <input type="text" name="industrial_precio" value="<?php echo $total_industrial_precio ?>" id="industrial_precio"
                                      class="span12 vpositive" style="width: 80px;">

                                    <input type="hidden" name="total_industrial_kilos" value="<?php echo $total_industrial_kilos ?>" id="total_industrial_kilos">
                                    <input type="hidden" name="total_industrial" value="<?php echo $total_industrial ?>" id="total_industrial">
                                  </td>
                                  <td style="text-align: right; font-weight: bold;" id="total_industrial_txt"><?php echo MyString::formatoNumero($total_industrial, 2, '$') ?></td>
                                </tr>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
              <!-- /Industrial proceso -->

              <!-- Tabla general de rendimientos -->
              <div class="row-fluid" style="margin-top: 5px;">
                <div class="span12">
                  <div class="row-fluid">

                    <div class="span12">
                      <div class="row-fluid">
                        <div class="row-fluid">
                          <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                            <table class="table table-striped table-bordered table-hover table-condensed" id="table-rendimiento">
                              <thead>
                                <tr>
                                  <th colspan="6">TABLA GENERAL DE RENDIMIENTOS</th>
                                </tr>
                                <tr>
                                  <th>CLASIF</th>
                                  <th>%</th>
                                  <th>CODIGO</th>
                                  <th>BULTOS</th>
                                  <th>KILOS</th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                  foreach ($rpt['rendimientos'] as $key => $rendimiento) {
                                  ?>
                                  <tr>
                                    <td><?php echo $rendimiento->cnombre ?></td>
                                    <td><?php echo MyString::formatoNumero($rendimiento->kilos*100/$total_porsn_kilos, 2, '') ?> %</td>
                                    <td><?php echo $rendimiento->ccodigo ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($rendimiento->bultos, 2, '') ?></td>
                                    <td style="text-align: right;"><?php echo MyString::formatoNumero($rendimiento->kilos, 2, '') ?></td>
                                  </tr>
                                <?php } ?>
                              </tbody>
                            </table>
                          </div>
                        </div>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
              <!-- /Tabla general de rendimientos -->

            </div>
          </div>
          <!-- /Ingresos por Reposicion -->
        </form>
      </div>

    </div><!--/#content.span10-->
  </div><!--/fluid-row-->

  <div class="clear"></div>



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
</body>
</html>