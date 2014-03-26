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

      <?php

        $readonly = '';
        $show = true;
        $display = '';
        $action = base_url('panel/caja_chica/cargar/?'.String::getVarsLink(array('msg')));
        if (isset($caja['status']) && $caja['status'] === 'f' && ! $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/modificar_caja/'))
        {
          $readonly = 'readonly';
          $display = 'display: none;';
          $show = false;
          $action = '';
        }

       ?>

      <div class="span12">
        <form class="form-horizontal" action="<?php echo $action ?>" method="POST">
          <?php $totalIngresos = 0; $totalSaldoIngresos = $caja['saldo_inicial']; ?>
          <!-- Header -->
          <div class="span12" style="margin: 10px 0 0 0;">
            <div class="row-fluid">
              <div class="span4" style="text-align: center;">
                <img alt="logo" src="http://sanjorge.dev/application/images/logo.png" height="54">
              </div>
              <div class="span4" style="text-align: right;">
                <div class="row-fluid">
                  <div class="span12">Fecha <input type="date" name="fecha_caja_chica" value="<?php echo set_value('fecha_caja_chica', isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d')) ?>" id="fecha_caja" class="input-medium" readonly></div>
                </div>
                <div class="row-fluid" style="margin: 3px 0;">
                  <div class="span12">Saldo Inicial <input type="text" name="saldo_inicial" value="<?php echo set_value('saldo_inicial', $caja['saldo_inicial']) ?>" id="saldo_inicial" class="input-medium vpositive" <?php echo $readonly ?>></div>
                </div>
              </div>
              <div class="span4">
                <div class="row-fluid">

                  <?php if ($show){ ?>
                    <div class="span4"><input type="submit" class="btn btn-success btn-large span12" value="Guardar"></div>
                  <?php } ?>

                  <?php if (isset($caja['status']) && $caja['status'] === 't'){ ?>
                    <div class="span4"><a href="<?php echo base_url('panel/caja_chica/cerrar_caja/?id='.$caja['id'].'&'.String::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12">Cerrar Caja</a></div>
                  <?php } ?>

                  <?php if (isset($caja['status']) && $caja['status'] === 'f') { ?>
                    <div class="span4"><a href="<?php echo base_url('panel/caja_chica/print?'.String::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
                  <?php }  ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Reporte Caja compras limon -->
          <div class="row-fluid">
            <div class="span12">
              <div class="row-fluid">
                <div class="span8">
                  <div class="row-fluid">
                    <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">REPORTE CAJA "COMPRAS LIMON"</div>
                    <div class="row-fluid">
                      <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">INGRESOS <button type="button" class="btn btn-success" id="btn-add-ingreso" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button></div>
                      <div class="span7" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-ingresos">
                          <thead>
                            <tr>
                              <th>CONCEPTO</th>
                              <th>MONTO</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              if (isset($_POST['ingreso_concepto'])) {
                                foreach ($_POST['ingreso_concepto'] as $key => $concepto) {
                                    $totalIngresos += floatval($_POST['ingreso_monto'][$key]);
                                  ?>
                                  <tr>
                                    <td><input type="text" name="ingreso_concepto[]" value="<?php echo $concepto ?>" class="ingreso-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>></td>
                                    <td style="width: 100px;"><input type="text" name="ingreso_monto[]" value="<?php echo $_POST['ingreso_monto'][$key] ?>" class="ingreso-monto vpositive input-small" placeholder="Monto" required <?php echo $readonly ?>></td>
                                    <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-ingreso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                  </tr>
                            <?php }} else {
                                  foreach ($caja['ingresos'] as $ingreso) {
                                      $totalIngresos += floatval($ingreso->monto);
                                    ?>
                                    <tr>
                                      <td><input type="text" name="ingreso_concepto[]" value="<?php echo $ingreso->concepto ?>" class="ingreso-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>></td>
                                      <td style="width: 100px;"><input type="text" name="ingreso_monto[]" value="<?php echo $ingreso->monto ?>" class="ingreso-monto vpositive input-small" placeholder="Monto" required <?php echo $readonly ?>></td>
                                      <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-ingreso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                    </tr>
                            <?php }} ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <div class="row-fluid">
                      <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">OTROS <button type="button" class="btn btn-success" id="btn-add-otros" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button></div>
                      <div class="span7" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-otros">
                          <thead>
                          </thead>
                          <tbody>
                            <?php
                              if (isset($_POST['otros_concepto'])) {
                                foreach ($_POST['otros_concepto'] as $key => $concepto) {
                                  $totalIngresos += floatval($_POST['otros_monto'][$key]);
                                ?>
                                  <tr>
                                    <td><input type="text" name="otros_concepto[]" value="<?php echo $concepto ?>" class="otros-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>></td>
                                    <td style="width: 100px;"><input type="text" name="otros_monto[]" value="<?php echo $_POST['otros_monto'][$key] ?>" class="otros-monto vpositive input-small" placeholder="Monto" required <?php echo $readonly ?>></td>
                                    <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                  </tr>
                            <?php }} else {
                                  foreach ($caja['otros'] as $otro) {
                                    $totalIngresos += floatval($otro->monto);
                                  ?>
                                    <tr>
                                      <td><input type="text" name="otros_concepto[]" value="<?php echo $otro->concepto ?>" class="otros-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>></td>
                                      <td style="width: 100px;"><input type="text" name="otros_monto[]" value="<?php echo $otro->monto ?>" class="otros-monto vpositive input-small" placeholder="Monto" required <?php echo $readonly ?>></td>
                                      <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                    </tr>
                            <?php }} ?>
                          </tbody>
                        </table>
                      </div>
                      <div class="span3">
                        <div class="row-fluid">
                          <input type="text" name="total_ingresos" value="<?php echo String::float(String::formatoNumero($totalIngresos, 2, '')) ?>" class="span12" id="total-ingresos" maxlength="500" readonly style="text-align: right;">
                        </div>
                        <div class="row-fluid" style="margin-top: 3px;">
                          <?php $totalReporteCaja = $totalSaldoIngresos + $totalIngresos ?>
                          <input type="text" name="tota_saldo_ingresos" value="<?php echo String::float(String::formatoNumero($totalReporteCaja, 2, '')) ?>" class="span12" id="total-saldo-ingresos" maxlength="500" readonly style="text-align: right;">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="span4">
                  <div class="span12" style="text-align: center; font-weight: bold; min-height: 20px;">DESGLOSE "OTROS INGRESOS" <a href="#modal-remisiones" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-remisiones" style="padding: 2px 7px 2px; float: right; <?php echo $display ?>">Remisiones</a></div>
                  <div class="row-fluid">
                    <div class="span12" style="margin-top: 1px;">
                      <table class="table table-striped table-bordered table-hover table-condensed" id="table-remisiones">
                        <thead>
                          <tr>
                            <th>OBSV.</th>
                            <th>No. REM</th>
                            <th>IMPORTE</th>
                            <th></th>
                          </tr>
                        </thead>
                        <tbody id="table-rem-tbody">
                          <?php
                            $totalRemisiones = 0;
                            if (isset($_POST['remision_concepto'])) {
                              foreach ($_POST['remision_concepto'] as $key => $remision) {
                                  $totalRemisiones += floatval($_POST['remision_importe'][$key]);
                                ?>
                                <tr>
                                  <td>
                                    <input type="text" name="remision_concepto[]" value="<?php echo $remision ?>" class="remision-concepto span12" maxlength="500" placeholder="Observacion" required <?php echo $readonly ?>>
                                    <input type="hidden" name="remision_id[]" value="<?php echo $_POST['remision_id'][$key] ?>" class="remision-id span12" required>
                                  </td>
                                  <td><input type="text" name="remision_numero[]" value="<?php echo $_POST['remision_numero'][$key] ?>" class="remision-numero vpositive input-small" placeholder="#" readonly style="width: 45px;" <?php echo $readonly ?>></td>
                                  <td><input type="text" name="remision_importe[]" value="<?php echo $_POST['remision_importe'][$key] ?>" class="remision-importe vpositive input-small" placeholder="Importe" required style="width: 55px;text-align: right;" <?php echo $readonly ?>></td>
                                  <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-remision" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                </tr>
                          <?php }} else {
                                foreach ($caja['remisiones'] as $remision) {
                                    $totalRemisiones += floatval($remision->monto);
                                  ?>
                                  <tr>
                                    <td>
                                      <input type="text" name="remision_concepto[]" value="<?php echo $remision->observacion ?>" class="remision-concepto span12" maxlength="500" placeholder="Observacion" required <?php echo $readonly ?>>
                                      <input type="hidden" name="remision_id[]" value="<?php echo $remision->id_remision ?>" class="remision-id span12" required>
                                    </td>
                                    <td><input type="text" name="remision_numero[]" value="<?php echo $remision->folio ?>" class="remision-numero vpositive input-small" placeholder="#" readonly style="width: 45px;" <?php echo $readonly ?>></td>
                                    <td><input type="text" name="remision_importe[]" value="<?php echo $remision->monto ?>" class="remision-importe vpositive input-small" placeholder="Importe" required style="width: 55px;text-align: right;" <?php echo $readonly ?>></td>
                                    <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-remision" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                  </tr>
                          <?php }} ?>
                        </tbody>
                        <tbody>
                          <tr>
                            <td colspan="3" id="total-remisiones" style="text-align: right; font-weight: bold;"><?php echo String::formatoNumero($totalRemisiones, 2, '$') ?></td>
                            <td></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- /Reporte Caja compras limon -->

          <!-- Boletas Pesadas -->
          <div class="row-fluid" style="margin-top: 5px;">
            <div class="span12">
              <div class="row-fluid">
                <div class="span8">
                  <div class="row-fluid">
                    <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">REPORTE CAJA "COMPRAS LIMON"</div> -->
                    <div class="row-fluid">
                      <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">INGRESOS <button type="button" class="btn btn-success" id="btn-add-ingreso" style="padding: 2px 7px 2px;"><i class="icon-plus"></i></button></div> -->
                      <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-boletas">
                          <thead>
                            <tr>
                              <th>BOLETA</th>
                              <th>PRODUCTOR</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              $totalBoletas = 0;
                              foreach ($caja['boletas'] as $boleta) {
                                $totalBoletas += floatval($boleta->importe);
                              ?>
                              <tr>
                                <td><?php echo $boleta->folio ?></td>
                                <td><?php echo $boleta->proveedor ?></td>
                                <td style="text-align: right;"><?php echo String::formatoNumero($boleta->importe, 2, '$') ?></td>
                              </tr>
                            <?php } ?>
                          </tbody>
                          <tbody>
                            <tr>
                              <td colspan="2"><input type="hidden" value="<?php echo $totalBoletas ?>" id="total-boletas"></td>
                              <td style="text-align: right; font-weight: bold;"><?php echo String::formatoNumero($totalBoletas, 2, '$') ?></td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="span4">
                  <div class="span12" style="text-align: center; font-weight: bold; min-height: 20px;">TABULACION DE EFECTIVO</div>
                  <div class="row-fluid">
                    <div class="span12" style="margin-top: 1px;">
                      <table class="table table-striped table-bordered table-hover table-condensed" id="table-tabulaciones">
                        <thead>
                          <tr>
                            <th>NUMERO</th>
                            <th>DENOMINACION</th>
                            <th>TOTAL</th>
                          </tr>
                        </thead>
                        <tbody>
                        </tbody>

                        <?php
                          $totalEfectivo = 0;
                          if (isset($_POST['denominacion_cantidad'])) {
                            foreach ($_POST['denominacion_cantidad'] as $key => $cantidad) {
                              $totalEfectivo += floatval($_POST['denominacion_total'][$key]); ?>
                                <tr>
                                  <td>
                                    <input type="text" name="denominacion_cantidad[]" value="<?php echo $cantidad ?>" class="input-small vpositive denom-num" data-denominacion="<?php echo $_POST['denominacion_denom'][$key] ?>" <?php echo $readonly ?>>
                                    <input type="hidden" name="denominacion_denom[]" value="<?php echo $_POST['denominacion_denom'][$key] ?>" class="input-small vpositive denom-num" <?php echo $readonly ?>>
                                    <input type="hidden" name="denom_abrev[]" value="<?php echo $_POST['denom_abrev'][$key] ?>" class="input-small vpositive denom-num" <?php echo $readonly ?>>
                                  </td>
                                  <td style="text-align: right;"><?php echo String::formatoNumero($_POST['denominacion_denom'][$key], 2, '$') ?></td>
                                  <td><input type="text" name="denominacion_total[]" value="<?php echo String::float($_POST['denominacion_total'][$key]) ?>" class="input-small vpositive denom-total" style="text-align: right;" <?php echo $readonly ?>></td>
                                </tr>
                        <?php }} else {
                          foreach ($caja['denominaciones'] as $denominacion) {
                            $totalEfectivo += floatval($denominacion['total']);
                          ?>
                          <tr>
                            <td>
                              <input type="text" name="denominacion_cantidad[]" value="<?php echo $denominacion['cantidad'] ?>" class="input-small vpositive denom-num" data-denominacion="<?php echo $denominacion['denominacion'] ?>" <?php echo $readonly ?>>
                              <input type="hidden" name="denominacion_denom[]" value="<?php echo $denominacion['denominacion'] ?>" class="input-small vpositive denom-num" <?php echo $readonly ?>>
                              <input type="hidden" name="denom_abrev[]" value="<?php echo $denominacion['denom_abrev'] ?>" class="input-small vpositive denom-num" <?php echo $readonly ?>>
                            </td>
                            <td style="text-align: right;"><?php echo String::formatoNumero($denominacion['denominacion'], 2, '$') ?></td>
                            <td><input type="text" name="denominacion_total[]" value="<?php echo String::float($denominacion['total']) ?>" class="input-small vpositive denom-total" style="text-align: right;" <?php echo $readonly ?>></td>
                          </tr>
                        <?php }} ?>
                        <tbody>
                          <tr>
                            <td colspan="2">TOTAL EFECTIVO</td>
                            <td id="total-efectivo-den" style="text-align: right; font-weight: bold;"><?php echo String::formatoNumero($totalEfectivo, 2, '$') ?></td>
                          </tr>
                          <tr>
                            <td colspan="2">TOTAL DIFERENCIA</td>
                            <td id="total-efectivo-diferencia" style="text-align: right; font-weight: bold;"><?php echo String::formatoNumero($totalEfectivo, 2, '$') ?></td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- /Boletas Pesadas -->

          <!-- Gastos -->
          <div class="row-fluid" style="margin-top: 5px;">
            <div class="span12">
              <div class="row-fluid">
                <div class="span8">
                  <div class="row-fluid">
                    <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">GASTOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;float: right;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></div>
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-gastos">
                          <thead>
                            <tr>
                              <th>CONCEPTO</th>
                              <th>CARGO</th>
                              <th>IMPORTE</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              $totalGastos = 0;
                              if (isset($_POST['gasto_concepto'])) {
                                foreach ($_POST['gasto_concepto'] as $key => $concepto) {
                                  $totalGastos += floatval($_POST['gasto_importe'][$key]); ?>
                                    <tr>
                                      <td><input type="text" name="gasto_concepto[]" value="<?php echo $concepto ?>" class="input-xlarge span12 gasto-concepto" <?php echo $readonly ?>></td>
                                      <td style="width: 100px;">
                                        <input type="text" name="gasto_cargo[]" value="<?php echo $_POST['gasto_cargo'][$key] ?>" class="input-small gasto-cargo" style="width: 150px;" <?php echo $readonly ?>>
                                        <input type="hidden" name="gasto_cargo_id[]" value="<?php echo $_POST['gasto_cargo_id'][$key] ?>" class="input-small vpositive gasto-cargo-id">
                                      </td>
                                      <td style="width: 100px;"><input type="text" name="gasto_importe[]" value="<?php echo $_POST['gasto_importe'][$key] ?>" class="input-small vpositive gasto-importe" style="text-align: right;" <?php echo $readonly ?>></td>
                                      <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                    </tr>
                            <?php }} else {
                              foreach ($caja['gastos'] as $gasto) {
                                $totalGastos += floatval($gasto->monto);
                              ?>
                                <tr>
                                  <td><input type="text" name="gasto_concepto[]" value="<?php echo $gasto->concepto ?>" class="input-xlarge span12 gasto-concepto" <?php echo $readonly ?>></td>
                                  <td style="width: 100px;">
                                    <input type="text" name="gasto_cargo[]" value="<?php echo $gasto->abreviatura ?>" class="input-small gasto-cargo" style="width: 150px;" <?php echo $readonly ?>>
                                    <input type="hidden" name="gasto_cargo_id[]" value="<?php echo $gasto->id_categoria ?>" class="input-small vpositive gasto-cargo-id">
                                  </td>
                                  <td style="width: 100px;"><input type="text" name="gasto_importe[]" value="<?php echo $gasto->monto ?>" class="input-small vpositive gasto-importe" style="text-align: right;" <?php echo $readonly ?>></td>
                                  <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                </tr>
                            <?php }} ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="span4">
                  <div class="span12" style="text-align: center; font-weight: bold; min-height: 20px;"></div>
                  <div class="row-fluid">
                    <div class="span12" style="margin-top: 1px; font-size: 1.3em; font-weight: bold;">
                      Total Gastos: <input type="text" value="<?php echo $totalGastos ?>" class="input-small vpositive" id="ttotal-gastos" style="text-align: right;" readonly>
                      <br><br><br>
                      Saldo Al Corte: <input type="text" name="saldo_corte" value="<?php echo $totalReporteCaja - $totalBoletas - $totalGastos ?>" class="input-small vpositive" id="ttotal-corte" style="text-align: right;" readonly>
                      <input type="hidden" name="total_diferencia" value="<?php echo $totalEfectivo - ($totalReporteCaja - $totalBoletas - $totalGastos) ?>" class="input-small vpositive" id="ttotal-diferencia" style="text-align: right;" readonly>
                    </div>
                    <div class="span12" style="margin-left: 0;"> <br>
                      <?php if ($show){ ?>
                        <div class="span5"><input type="submit" class="btn btn-success btn-large span12" value="Guardar"></div>
                      <?php } ?>

                      <?php if (isset($caja['status']) && $caja['status'] === 't'){ ?>
                        <div class="span5"><a href="<?php echo base_url('panel/caja_chica/cerrar_caja/?id='.$caja['id'].'&'.String::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12">Cerrar Caja</a></div>
                      <?php } ?>

                      <?php if (isset($caja['status']) && $caja['status'] === 'f') { ?>
                        <div class="span5"><a href="<?php echo base_url('panel/caja_chica/print?'.String::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
                      <?php }  ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <!-- /Gastos -->

          <!-- Reposicion -->
          <div class="row-fluid" style="margin-top: 5px;">
            <div class="span12">
              <div class="row-fluid">
                <div class="span8">
                  <div class="row-fluid">
                    <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">REPOSICION DE GASTOS X CONCEPTOS</div>
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-gastos">
                          <thead>
                          </thead>
                          <tbody>
                            <?php
                              if (isset($_POST['reposicion_nombre'])) {
                                foreach ($_POST['reposicion_nombre'] as $i => $nombre) { ?>
                                  <tr id="repo-<?php echo $_POST['reposicion_id'][$i] ?>">
                                    <td><?php echo $i + 1 ?></td>
                                    <td>
                                      <?php echo $nombre ?>
                                      <input type="hidden" name="reposicion_nombre[]" value="<?php echo $nombre ?>">
                                      <input type="hidden" name="reposicion_id[]" value="<?php echo $_POST['reposicion_id'][$i] ?>">
                                    </td>
                                    <td style="width: 30px;"><input type="text" name="reposicion_importe[]" value="<?php echo $_POST['reposicion_importe'][$i] ?>" class="input-small vpositive reposicion-importe" style="text-align: right;" readonly></td>
                                  </tr>
                              <?php }} else {
                                foreach ($caja['categorias'] as $i => $categoria) { ?>
                                <tr id="repo-<?php echo $categoria->id_categoria ?>">
                                  <td><?php echo $i + 1 ?></td>
                                  <td>
                                    <?php echo $categoria->nombre ?>
                                    <input type="hidden" name="reposicion_nombre[]" value="<?php echo $categoria->nombre ?>">
                                    <input type="hidden" name="reposicion_id[]" value="<?php echo $categoria->id_categoria ?>">
                                  </td>
                                  <td style="width: 30px;"><input type="text" name="reposicion_importe[]" value="<?php echo $categoria->importe ?>" class="input-small vpositive reposicion-importe" style="text-align: right;" readonly></td>
                                </tr>
                            <?php }} ?>
                            <tr>
                              <td colspan="2" style="text-align: right;"> TOTAL</td>
                              <td style="width: 30px; text-align: right;" id="td-total-gastos"><?php echo String::formatoNumero($totalGastos, 2, '$')  ?></td>
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
          <!-- /Reposicion -->
        </form>
      </div>

    </div><!--/#content.span10-->
  </div><!--/fluid-row-->

  <div class="clear"></div>

  <!-- Modal -->
  <div id="modal-remisiones" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 700px;left: 45%;">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Remisiones</h3>
    </div>
    <div class="modal-body" style="max-height: 370px;">
      <table class="table table-striped table-bordered table-hover table-condensed">
        <caption></caption>
        <thead>
          <tr>
            <th></th>
            <th>Fecha</th>
            <th>Folio</th>
            <th>Cliente</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($remisiones as $remision) { ?>
            <tr>
              <td><input type="checkbox" class="chk-remision" data-id="<?php echo $remision->id_factura ?>" data-total="<?php echo $remision->total ?>" data-folio="<?php echo $remision->folio ?>"></td>
              <td style="width: 66px;"><?php echo $remision->fecha ?></td>
              <td><?php echo ($remision->serie ? $remision->serie.'-':'').$remision->folio ?></td>
              <td><?php echo $remision->cliente ?></td>
              <td style="text-align: right;"><?php echo String::formatoNumero(String::float($remision->total), 2, '$') ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="carga-remisiones">Cargar</button>
    </div>
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
</body>
</html>