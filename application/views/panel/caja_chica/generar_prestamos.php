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
        $action = base_url('panel/caja_chica_prest/cargar/?'.String::getVarsLink(array('msg')));
        if (isset($caja['status']) && $caja['status'] === 'f' && ! $this->usuarios_model->tienePrivilegioDe('', 'caja_chica_prest/modificar_caja/'))
        {
          $readonly = 'readonly';
          $display = 'display: none;';
          $show = false;
          $action = '';
        }
      ?>

      <div class="span12">

        <select id="nomeclaturas_base" style="display: none;">
          <?php foreach ($nomenclaturas as $n) { ?>
            <option value="<?php echo $n->id ?>"><?php echo $n->nomenclatura ?></option>
          <?php } ?>
        </select>

        <form class="form-horizontal" action="<?php echo $action ?>" method="POST" id="frmcajachica" name="registerform">
          <?php $totalprestamos = $totalpagos = 0; ?>
          <!-- Header -->
          <div class="span12" style="margin: 10px 0 0 0;">
            <div class="row-fluid">
              <div class="span4" style="text-align: center;">
                <img alt="logo" src="<?php echo base_url(); ?>/application/images/logo.png" height="54">
              </div>
              <div class="span2" style="text-align: right;">
                <div class="row-fluid">
                  <div class="span12">Fecha <input type="date" name="fecha_caja_chica" value="<?php echo set_value('fecha_caja_chica', isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d')) ?>" id="fecha_caja" class="input-medium" readonly></div>
                </div>
                <div class="row-fluid" style="margin: 3px 0;">
                  <div class="span12">Saldo Inicial <input type="text" name="saldo_inicial" value="<?php echo set_value('saldo_inicial', $caja['saldo_inicial']) ?>" id="saldo_inicial" class="input-medium vpositive" <?php echo $readonly ?>></div>
                </div>
              </div>
              <div class="span4">
                <div class="row-fluid">
                  <input type="hidden" name="fno_caja" value="<?php echo $_GET['fno_caja']; ?>">

                  <?php if ($show){ ?>
                    <div class="span4"><input type="submit" class="btn btn-success btn-large span12" value="Guardar"></div>
                  <?php } ?>

                  <?php if (isset($caja['status']) && $caja['status'] === 't'){ ?>
                    <div class="span4"><a href="<?php echo base_url('panel/caja_chica_prest/cerrar_caja/?id='.$caja['id'].'&'.String::getVarsLink(array('msg', 'id'))) ?>" class="btn btn-success btn-large span12">Cerrar Caja</a></div>
                  <?php } ?>

                  <?php if (isset($caja['status']) && $caja['status'] === 'f') { ?>
                    <div class="span4"><a href="<?php echo base_url('panel/caja_chica_prest/print_caja?'.String::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
                  <?php }  ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Ingresos -->
          <div class="row-fluid">
            <div class="span12">
              <div class="row-fluid">
                <div class="span12">

                    <!-- Deudores diversos -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-fondocajas">
                          <thead>
                            <tr>
                              <th colspan="4">DEUDORES DIVERSOS
                                <button type="button" class="btn btn-success" id="btn-add-fondocaja" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button>
                                <!-- <a href="#modal-movimientos" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-movimientos" style="padding: 2px 7px 2px; float: right;<?php echo $display ?>">Movimientos</a> -->
                              </th>
                              <th colspan="5"></th>
                            </tr>
                            <tr>
                              <th>EMPRESA</th>
                              <th>FONDO DE CAJA</th>
                              <th>FECHA</th>
                              <th>REFERENCIA</th>
                              <th>INGRESOS</th>
                              <th>EGRESOS</th>
                              <th>SALDOS</th>
                              <th>TICKET</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody style="overflow-y: auto;max-height: 300px;">
                            <?php
                                  foreach ($caja['fondos_caja'] as $fondoc) {
                                      $totalfondo += floatval($prestamo->monto);
                                    ?>
                                    <tr>
                                      <td>
                                        <input type="text" name="fondo_categoria[]" value="<?php echo $fondoc->categoria ?>" class="span11 gasto-cargo" id="fondo_categoria" required>
                                        <input type="hidden" name="fondo_id_categoria[]" value="<?php echo $fondoc->id_categoria ?>" id="fondo_id_categoria" class="gasto-cargo-id">
                                        <input type="hidden" name="fondo_id_fondo[]" value="<?php echo $fondoc->id_fondo ?>" id="fondo_id_fondo">
                                        <input type="hidden" name="fondo_del[]" value="" id="fondo_del">
                                      </td>
                                      <td><?php echo $fondoc->empresa ?></td>
                                      <td><input type="date" name="fondo_fecha[]" value="<?php echo $fondoc->fecha ?>" id="fondo_fecha" required></td>
                                      <td> <input type="text" name="fondo_referencia[]" value="<?php echo $fondoc->referencia ?>" id="fondo_referencia" class="span11"> </td>
                                      <td> <input type="number" name="fondo_ingreso[]" value="<?php echo ($fondoc->tipo_movimiento=='t'? $fondoc->monto: '') ?>" id="fondo_ingreso" class="span11 vpositive"></td>
                                      <td> <input type="number" name="fondo_egreso[]" value="<?php echo ($fondoc->tipo_movimiento=='f'? $fondoc->monto: '') ?>" id="fondo_egreso" class="span11 vpositive"></td>
                                      <td><?php echo $fondoc->saldo ?></td>
                                      <td><a href="<?php echo base_url('panel/caja_chica_prest/print_fondo/?id='.$fondoc->id_fondo)?>" target="_blank" title="Imprimir">
                                          <i class="ico icon-print" style="cursor:pointer"></i> <?php echo $fondoc->id_fondo ?></a></td>
                                      <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-fondo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                    </tr>
                            <?php } ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Deudores diversos -->

                    <!-- Prestamos-->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-ingresos">
                          <thead>
                            <tr>
                              <th colspan="3">PRESTAMOS
                                <button type="button" class="btn btn-success" id="btn-add-prestamo" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button>
                                <!-- <a href="#modal-movimientos" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-movimientos" style="padding: 2px 7px 2px; float: right;<?php echo $display ?>">Movimientos</a> -->
                              </th>
                              <th colspan="2">IMPORTE</th>
                            </tr>
                            <tr>
                              <th>EMPRESA</th>
                              <th>NOM</th>
                              <th>NOMBRE Y/O CONCEPTO</th>
                              <th>ABONO</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                                  foreach ($caja['prestamos'] as $prestamo) {
                                      $totalprestamos += floatval($prestamo->monto);
                                    ?>
                                    <tr>
                                      <td style="width: 100px;">
                                        <input type="text" name="prestamo_empresa[]" value="<?php echo $prestamo->categoria ?>" class="input-small gasto-cargo" style="width: 150px;" required <?php echo $readonly ?>>
                                        <input type="hidden" name="prestamo_empresa_id[]" value="<?php echo $prestamo->id_categoria ?>" class="input-small vpositive gasto-cargo-id">
                                        <input type="hidden" name="prestamo_id_prestamo[]" value="<?php echo $prestamo->id_prestamo ?>" id="prestamo_id_prestamo" class="input-small vpositive">
                                        <input type="hidden" name="prestamo_del[]" value="" id="prestamo_del">
                                        <input type="hidden" name="prestamo_id_prestamo_nom[]" value="<?php echo $prestamo->id_prestamo_nom ?>" class="input-small vpositive">
                                        <input type="hidden" name="prestamo_id_empleado[]" value="<?php echo $prestamo->id_empleado ?>" class="input-small vpositive">
                                      </td>
                                      <td style="width: 40px;">
                                        <select name="prestamo_nomenclatura[]" class="prestamo_nomenclatura" style="width: 70px;" <?php echo $readonly ?>>
                                          <?php foreach ($nomenclaturas as $n) { ?>
                                            <option value="<?php echo $n->id ?>" <?php echo $prestamo->id_nomenclatura == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                          <?php } ?>
                                        </select>
                                      </td>
                                      <td>
                                        <input type="text" name="prestamo_concepto[]" value="<?php echo $prestamo->concepto ?>" class="prestamo-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                                      </td>
                                      <td style="width: 100px;"><input type="text" name="prestamo_monto[]" value="<?php echo $prestamo->monto ?>" class="prestamo-monto vpositive input-small" placeholder="Monto" required <?php echo ($prestamo->id_prestamo_nom>0? 'readonly': $readonly) ?>></td>
                                      <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-prestamo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                    </tr>
                            <?php } ?>
                                  <tr class="row-total">
                                    <td colspan="3" style="text-align: right; font-weight: bolder;">TOTAL</td>
                                    <td><input type="text" value="<?php echo $totalprestamos ?>" class="input-small vpositive" id="ttotal-prestamo" style="text-align: right;" readonly></td>
                                    <td></td>
                                  </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Prestamos-->

                    <!-- Saldo empleados -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-empsaldo">
                          <thead>
                            <tr>
                              <th colspan="3">SALDO EMPLEADOS</th>
                              <th colspan="2">IMPORTE</th>
                            </tr>
                            <tr>
                              <th>NOMBRE</th>
                              <th>PRESTADO</th>
                              <th>PAGADO</th>
                              <th>SALDO</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody>
                              <?php
                                $totalempsaldos = 0;
                                foreach ($caja['saldos_empleados'] as $empsaldo) {
                                    $totalempsaldos += floatval($empsaldo->saldo);
                                  ?>
                                    <tr>
                                      <td><?php echo $empsaldo->nombre ?>
                                        <input type="hidden" name="empsaldo_empleado_id[]" value="<?php echo $empsaldo->id ?>" class="input-small vpositive empsaldo_empleado_id">
                                      </td>
                                      <td>
                                        <input type="text" name="empsaldo_prestado[]" value="<?php echo $empsaldo->prestado ?>" class="empsaldo_prestado span12" maxlength="500" placeholder="Prestado" required readonly>
                                      </td>
                                      <td>
                                        <input type="text" name="empsaldo_pagado[]" value="<?php echo $empsaldo->pagado ?>" class="empsaldo_pagado span12" maxlength="500" placeholder="Pagado" required readonly>
                                      </td>
                                      <td><input type="text" name="empsaldo_saldo[]" value="<?php echo $empsaldo->saldo ?>" class="empsaldo_saldo vpositive input-small" placeholder="Saldo" required readonly></td>
                                      <td style="width: 30px;">
                                        <a href="javascript:void()" class="btn btn-danger btn-del-empsaldo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></a></td>
                                    </tr>
                            <?php } ?>
                                  <tr class="row-total">
                                    <td colspan="3" style="text-align: right; font-weight: bolder;">TOTAL</td>
                                    <td><input type="text" value="<?php echo $totalempsaldos ?>" class="input-small vpositive" id="ttotal-empsaldo" style="text-align: right;" readonly></td>
                                    <td></td>
                                  </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Saldo empleados-->
                </div>
              </div>
            </div>

            <div class="span6">

              <!-- Pagos -->
              <div class="row-fluid" style="margin-top: 5px;">
                <div class="span12">
                  <div class="row-fluid">
                    <div class="span12">
                      <div class="row-fluid">
                        <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">GASTOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;float: right;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                        <div class="row-fluid">
                          <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                            <table class="table table-striped table-bordered table-hover table-condensed" id="table-gastos">
                              <thead>
                                <tr>
                                  <th colspan="3">PAGOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-pagos" style="padding: 2px 7px 2px;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></th>
                                  <th colspan="2">IMPORTE</th>
                                </tr>
                                <tr>
                                  <th>EMPRESA</th>
                                  <th>NOM</th>
                                  <th>CONCEPTO</th>
                                  <th>CARGO</th>
                                  <th></th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                  foreach ($caja['pagos'] as $pago) {
                                    $totalpagos += floatval($pago->monto);
                                  ?>
                                  <tr>
                                    <td style="width: 100px;">
                                      <input type="text" name="pago_empresa[]" value="<?php echo $pago->categoria ?>" class="span12 gasto-cargo" required <?php echo $readonly ?>>
                                      <input type="hidden" name="pago_empresa_id[]" value="<?php echo $pago->id_categoria ?>" class="input-small vpositive gasto-cargo-id">
                                      <input type="hidden" name="pago_id[]" value="<?php echo $pago->id_pago ?>" id="pago_id" class="input-small vpositive">
                                      <input type="hidden" name="pago_del[]" value="" id="pago_del">
                                      <input type="hidden" name="pago_id_empleado[]" value="<?php echo $pago->id_empleado ?>" class="input-small vpositive">
                                      <input type="hidden" name="pago_id_empresa[]" value="<?php echo $pago->id_empresa ?>" class="input-small vpositive">
                                      <input type="hidden" name="pago_anio[]" value="<?php echo $pago->anio ?>" class="input-small vpositive">
                                      <input type="hidden" name="pago_semana[]" value="<?php echo $pago->semana ?>" class="input-small vpositive">
                                      <input type="hidden" name="pago_id_prestamo[]" value="<?php echo $pago->id_prestamo ?>" class="input-small vpositive">
                                    </td>
                                    <td style="width: 40px;">
                                      <select name="pago_nomenclatura[]" class="span12 ingreso_nomenclatura" <?php echo $readonly ?>>
                                        <?php foreach ($nomenclaturas as $n) { ?>
                                          <option value="<?php echo $n->id ?>" <?php echo $pago->id_nomenclatura == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                        <?php } ?>
                                      </select>
                                    </td>
                                    <td style="">
                                      <input type="text" name="pago_concepto[]" value="<?php echo $pago->concepto ?>" class="span12 pago-concepto" <?php echo $readonly ?>>
                                    </td>
                                    <td style="width: 60px;"><input type="text" name="pago_importe[]" value="<?php echo $pago->monto ?>" class="span12 vpositive pago-importe" <?php echo ($pago->id_prestamo>0? 'readonly': $readonly) ?>></td>
                                    <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-pagos" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                  </tr>
                                <?php } ?>
                                <tr class="row-total">
                                  <td colspan="3" style="text-align: right; font-weight: bolder;">TOTAL</td>
                                  <td><input type="text" value="<?php echo $totalpagos ?>" class="input-small vpositive" id="ttotal-pago" style="text-align: right;" readonly></td>
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
              </div>
              <!-- /Pagos -->

              <!-- Tabulacion -->
              <div class="row-fluid">
                <div class="span12">
                  <div class="span12" style="text-align: center; font-weight: bold; min-height: 20px;">TABULACION DE EFECTIVO</div>
                  <div class="row-fluid">

                    <div class="span6" style="margin-top: 1px;">
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

                    <!--Totales -->
                    <div class="span4 pull-right">
                      <div class="row-fluid">
                        <table class="table table-striped table-bordered table-hover table-condensed">
                          <thead>
                            <tr>
                              <th></th>
                              <th>TOTALES</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td>SALDO INICIAL:</td>
                              <td><input type="text" name="" value="<?php echo $caja['saldo_inicial'] ?>" class="input-small vpositive" id="total-saldo-inicial" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>TOTAL PRESTAMOS:</td>
                              <td><input type="text" name="" value="<?php echo $totalprestamos; ?>" class="input-small vpositive" id="total-saldo-prestamo" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>TOTAL PAGOS:</td>
                              <td><input type="text" name="" value="<?php echo $totalpagos ?>" class="input-small vpositive" id="ttotal-pagos" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>SALDO DEL CORTE:</td>
                              <td><input type="text" name="saldo_corte" value="<?php echo $caja['saldo_inicial'] - $totalprestamos + $totalpagos ?>" class="input-small vpositive" id="ttotal-corte" style="text-align: right;" readonly></td>
                              <input type="hidden" name="total_diferencia" value="<?php echo ($totalEfectivo - ($caja['saldo_inicial'] - $totalprestamos + $totalpagos)) ?>" class="input-small vpositive" id="ttotal-diferencia" style="text-align: right;" readonly>
                            </tr>
                            <tr>
                              <td colspan="2"></td>
                            </tr>
                            <tr>
                              <td>FONDO DE CAJA:</td>
                              <td><input type="text" name="fondo_caja" value="<?php echo $caja['saldo_inicial'] - $totalprestamos + $totalpagos + $totalempsaldos; ?>" class="input-small vpositive" id="ttotal-fondo_caja" style="text-align: right;" readonly></td>
                            </tr>
                          </tbody>
                        </table>

                        <div class="span12" style="margin-left: 0;"> <br>
                          <?php if ($show){ ?>
                            <div class="span5"><button type="submit" class="btn btn-success btn-large span12">Guardar</button></div>
                          <?php } ?>

                          <?php if (isset($caja['status']) && $caja['status'] === 't'){ ?>
                            <div class="span5"><a href="<?php echo base_url('panel/caja_chica_prest/cerrar_caja/?id='.$caja['id'].'&'.String::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12">Cerrar Caja</a></div>
                          <?php } ?>

                          <?php if (isset($caja['status']) && $caja['status'] === 'f') { ?>
                            <div class="span5"><a href="<?php echo base_url('panel/caja_chica_prest/print_caja?'.String::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
                          <?php }  ?>
                        </div>
                      </div>
                    </div>
                    <!--/Totales -->

                  </div>
                </div>
              </div>
              <!--/Tabulacion -->
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