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
        $action = base_url('panel/caja_chica_prest/cargar/?'.MyString::getVarsLink(array('msg')));
        if (isset($caja['status']) && $caja['status'] === 'f' && ! $this->usuarios_model->tienePrivilegioDe('', 'caja_chica_prest/modificar_caja/'))
        {
          $readonly = 'readonly';
          $display = 'display: none;';
          $show = false;
          $action = '';
        }

        $fecha = isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d');
      ?>

      <div class="span12">

        <select id="nomeclaturas_base" style="display: none;">
          <?php foreach ($nomenclaturas as $n) { ?>
            <option value="<?php echo $n->id ?>"><?php echo $n->nomenclatura ?></option>
          <?php } ?>
        </select>

        <form class="form-horizontal" action="<?php echo $action ?>" method="POST" id="frmcajachica" name="registerform">
          <?php
          $totalfondo = $totalprestamos = $totalpagos = $totalpreslp_salini = $totalpreslp_pago_dia = $totalpreslp_salfin = 0;
          $totalprescp_salini = $totalprescp_pago_dia = $totalprescp_salfin = 0;
          $fecha_caja_chica = set_value('fecha_caja_chica', isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d'));
          ?>
          <!-- Header -->
          <div class="span12" style="margin: 10px 0 0 0;">
            <div class="row-fluid">
              <div class="span4" style="text-align: center;">
                <img alt="logo" src="<?php echo base_url(); ?>/application/images/logo.png" height="54">
              </div>
              <div class="span2" style="text-align: right;">
                <div class="row-fluid">
                  <div class="span12">Fecha <input type="date" name="fecha_caja_chica" value="<?php echo $fecha_caja_chica ?>" id="fecha_caja" class="input-medium" readonly></div>
                </div>
                <div class="row-fluid" style="margin: 3px 0;">
                  <div class="span12">Saldo Inicial <input type="text" name="saldo_inicial" value="<?php echo set_value('saldo_inicial', $caja['saldo_inicial']) ?>" id="saldo_inicial" class="input-medium vpositive" <?php echo $readonly ?>></div>
                </div>
              </div>
              <div class="span4">
                <div class="row-fluid">
                  <input type="hidden" name="fno_caja" id="fno_caja" value="<?php echo $_GET['fno_caja']; ?>">

                  <?php if ($show){ ?>
                    <div class="span4"><input type="submit" class="btn btn-success btn-large span12" value="Guardar"></div>
                  <?php } ?>

                  <?php if (isset($caja['status']) && $caja['status'] === 't'){ ?>
                    <div class="span4"><a href="<?php echo base_url('panel/caja_chica_prest/cerrar_caja/?id='.$caja['id'].'&'.MyString::getVarsLink(array('msg', 'id'))) ?>" class="btn btn-success btn-large span12">Cerrar Caja</a></div>
                  <?php } ?>

                  <?php if (isset($caja['status']) && $caja['status'] === 'f') { ?>
                    <div class="span4"><a href="<?php echo base_url('panel/caja_chica_prest/print_caja?'.MyString::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
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
                              <th colspan="5" id="dvfondo_caja"></th>
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
                                $saldofc = 0;
                                  foreach ($caja['fondos_caja'] as $fondoc) {
                                      $totalfondo += floatval($fondoc->monto);
                                      $saldofc = ($fondoc->tipo_movimiento=='t'? $saldofc+$fondoc->monto: $saldofc-$fondoc->monto);
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
                                      <td class="fondoc_saldo"><?php echo $saldofc ?></td>
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

                    <!-- Prestamos largo plazo -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-prestamolp">
                          <thead>
                            <tr>
                              <th colspan="9">PRESTAMOS A LARGO PLAZO
                                <!-- <button type="button" class="btn btn-success" id="btn-add-prestamo" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button> -->
                                <!-- <a href="#modal-movimientos" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-movimientos" style="padding: 2px 7px 2px; float: right;<?php echo $display ?>">Movimientos</a> -->
                              </th>
                              <th colspan="1">IMPORTE</th>
                            </tr>
                            <tr>
                              <th>EMPRESA</th>
                              <th>TRABAJADOR</th>
                              <th>FECHA</th>
                              <th>REFERENCIA</th>
                              <th>CARGO <br> PRESTAMOS</th>
                              <th>SALDOS <br> INICIALES</th>
                              <th>ABONO <br> DEL DIA</th>
                              <th>No.</th>
                              <th>TICKET <br> INGRESO</th>
                              <th>SALDOS <br> FINALES</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                                  $tipoo = '';
                                  $totalpreslp_salini_fi = $totalpreslp_pago_dia_fi = $totalpreslp_salfin_fi = 0;
                                  $totalpreslp_salini_ef = $totalpreslp_pago_dia_ef = $totalpreslp_salfin_ef = 0;
                                  $totalpreslp_salini_efd = $totalpreslp_pago_dia_efd = $totalpreslp_salfin_efd = 0;
                                  $totalpreslp_ef_rec = [];
                                  foreach ($caja['prestamos_lp'] as $prestamo) {
                                      $totalpreslp_salini += floatval($prestamo->saldo_ini);
                                      $totalpreslp_pago_dia += floatval($prestamo->pago_dia);
                                      $totalpreslp_salfin += floatval($prestamo->saldo_fin);

                                      if ($prestamo->tipo == 'efd') {
                                        $totalpreslp_salini_efd += floatval($prestamo->saldo_ini);
                                        $totalpreslp_pago_dia_efd += floatval($prestamo->pago_dia);
                                        $totalpreslp_salfin_efd += floatval($prestamo->saldo_fin);

                                        if (isset($totalpreslp_ef_rec[$prestamo->categoria])) {
                                          $totalpreslp_ef_rec[$prestamo->categoria] += $prestamo->pago_dia;
                                        } else {
                                          $totalpreslp_ef_rec[$prestamo->categoria] = $prestamo->pago_dia;
                                        }
                                      } elseif ($prestamo->tipo == 'ef') {
                                        $totalpreslp_salini_ef += floatval($prestamo->saldo_ini);
                                        $totalpreslp_pago_dia_ef += floatval($prestamo->pago_dia);
                                        $totalpreslp_salfin_ef += floatval($prestamo->saldo_fin);

                                        // if (isset($totalpreslp_ef_rec[$prestamo->categoria])) {
                                        //   $totalpreslp_ef_rec[$prestamo->categoria] += $prestamo->pago_dia;
                                        // } else {
                                        //   $totalpreslp_ef_rec[$prestamo->categoria] = $prestamo->pago_dia;
                                        // }
                                      } else {
                                        $totalpreslp_salini_fi += floatval($prestamo->saldo_ini);
                                        $totalpreslp_pago_dia_fi += floatval($prestamo->pago_dia);
                                        $totalpreslp_salfin_fi += floatval($prestamo->saldo_fin);
                                      }

                                      if ($tipoo != $prestamo->tipo && $prestamo->tipo != 'mt') {
                                        switch ($prestamo->tipo) {
                                          case 'efd': $tipo = 'Efectivo Fijo'; break;
                                          case 'ef': $tipo = 'Efectivo'; break;
                                          default: $tipo = 'Fiscal'; break;
                                        }
                                        $tipoo = $prestamo->tipo;
                            ?>
                                    <tr>
                                      <td colspan="10"><strong><?php echo $tipo ?></strong></td>
                                    </tr>
                            <?php
                                      }
                            ?>
                                    <tr>
                                      <td><?php echo $prestamo->categoria ?>
                                        <a href="<?php echo base_url('panel/caja_chica_prest/print_prestamolp/?id='.$prestamo->id_prestamo_nom)?>" target="_blank" title="Imprimir vale prestamo">
                                          <i class="ico icon-print" style="cursor:pointer"></i></a>
                                      </td>
                                      <td><?php echo $prestamo->empleado ?></td>
                                      <td><?php echo MyString::fechaAT($prestamo->fecha) ?></td>
                                      <td><?php echo $prestamo->referencia ?></td>
                                      <td><?php echo $prestamo->monto ?></td>
                                      <td><?php echo $prestamo->saldo_ini ?></td>
                                      <td><?php echo $prestamo->pago_dia ?></td>
                                      <td><?php echo $prestamo->no_pagos.'/'.$prestamo->tno_pagos ?></td>
                                      <td><a href="<?php echo base_url('panel/caja_chica_prest/print_prestamolp/?id='.$prestamo->no_ticket."&fecha=".$fecha_caja_chica)?>"
                                            target="_blank" title="Imprimir" style="display:<?php echo ($prestamo->no_ticket>0? 'block': 'none') ?>">
                                          <i class="ico icon-print" style="cursor:pointer"></i> <?php echo $prestamo->no_ticket ?></a></td>
                                      <td>
                                        <?php if ($priv_saldar_prestamo): ?>
                                          <a href="<?php echo base_url('panel/caja_chica_prest/saldar_prestamos/?id='.$prestamo->id_prestamo_nom."&fecha=".$fecha_caja_chica."&fno_caja=".$_GET['fno_caja'])?>"
                                            onclick="msb.confirm('Estas seguro saldar este préstamo? \n No se podrá revertir.', 'Prestamos', this); return false;">
                                        <?php endif ?>

                                        <?php echo $prestamo->saldo_fin ?>

                                        <?php if ($priv_saldar_prestamo): ?>
                                          </a>
                                        <?php endif ?>
                                      </td>
                                    </tr>
                            <?php } ?>
                                  <tr class="row-total">
                                    <td colspan="5" style="text-align: right; font-weight: bolder;">SUMAS</td>
                                    <td><?php echo $totalpreslp_salini ?></td>
                                    <td><?php echo $totalpreslp_pago_dia ?></td>
                                    <td colspan="2"></td>
                                    <td><?php echo $totalpreslp_salfin ?></td>
                                  </tr>
                                  <tr class="row-total">
                                    <td colspan="5" style="text-align: right; font-weight: bolder;">Fiscal</td>
                                    <td><?php echo $totalpreslp_salini_fi ?></td>
                                    <td><?php echo $totalpreslp_pago_dia_fi ?></td>
                                    <td colspan="2"></td>
                                    <td><?php echo $totalpreslp_salfin_fi ?></td>
                                  </tr>
                                  <tr class="row-total">
                                    <td colspan="5" style="text-align: right; font-weight: bolder;">Efectivo</td>
                                    <td><?php echo $totalpreslp_salini_ef ?></td>
                                    <td><?php echo $totalpreslp_pago_dia_ef ?></td>
                                    <td colspan="2"></td>
                                    <td><?php echo $totalpreslp_salfin_ef ?></td>
                                  </tr>
                                  <tr class="row-total">
                                    <td colspan="5" style="text-align: right; font-weight: bolder;">Efectivo Fijo</td>
                                    <td><?php echo $totalpreslp_salini_efd ?></td>
                                    <td><?php echo $totalpreslp_pago_dia_efd ?></td>
                                    <td colspan="2"></td>
                                    <td><?php echo $totalpreslp_salfin_efd ?></td>
                                  </tr>

                                  <tr class="row-total">
                                    <td colspan="10"><strong>Recuperar Efectivo Fijo</strong></td>
                                  </tr>
                                  <tr class="row-total">
                                    <td><strong>Saldo Anterior</strong></td>
                                    <td><?php echo $caja['saldo_prest_fijo'] ?></td>
                                    <td colspan="8"></td>
                                  </tr>
                            <?php
                              $total_prestamos_recuperar = 0;
                              if (count($totalpreslp_ef_rec) > 0) {
                            ?>
                                <tr class="row-total">
                                  <td colspan="10"><strong>Cobro Prestamos Fijos</strong></td>
                                </tr>
                            <?php
                                foreach ($totalpreslp_ef_rec as $key => $value) {
                                  if ($value > 0) {
                                    $total_prestamos_recuperar += $value;
                            ?>
                                <tr class="row-total">
                                  <td><?php echo $key ?></td>
                                  <td><?php echo $value ?></td>
                                  <td colspan="8"></td>
                                </tr>
                            <?php
                                  }
                                }
                              }
                            ?>
                            <tr class="row-total">
                              <td><strong>Traspasos</strong></td>
                              <td><?php echo $caja['traspasos'] ?></td>
                              <td colspan="8"></td>
                            </tr>
                            <tr class="row-total">
                              <td><strong>Saldo</strong></td>
                              <td><?php echo $caja['saldo_prest_fijo']+$total_prestamos_recuperar-$caja['traspasos'] ?></td>
                              <td colspan="8">
                                <input type="hidden" name="saldo_prest_fijo" value="<?php echo $caja['saldo_prest_fijo']+$total_prestamos_recuperar-$caja['traspasos'] ?>">
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Prestamos largo plazo -->

                    <!-- Prestamos corto plazo -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-ingresos">
                          <thead>
                            <tr>
                              <th colspan="10">PRESTAMOS A CORTO PLAZO
                                <button type="button" class="btn btn-success" id="btn-add-prestamo" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button>
                                <!-- <a href="#modal-movimientos" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-movimientos" style="padding: 2px 7px 2px; float: right;<?php echo $display ?>">Movimientos</a> -->
                              </th>
                              <th></th>
                            </tr>
                            <tr>
                              <th>EMPRESA</th>
                              <th>TRABAJADOR</th>
                              <th>FECHA</th>
                              <th>REFERENCIA</th>
                              <th>CARGO <br>PRESTAMO</th>
                              <th>SALDO <br>INICIAL</th>
                              <th>ABONO <br>DEL DIA</th>
                              <th></th>
                              <th>TICKET <br> INGRESO</th>
                              <th>SALDOS <br> FINALES</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                                  foreach ($caja['prestamos'] as $prestamo) {
                                      $totalprestamos += floatval($prestamo->monto);
                                      $totalprescp_salini += floatval($prestamo->saldo_ini);
                                      $totalprescp_pago_dia += floatval($prestamo->pago_dia);
                                      $totalprescp_salfin += floatval($prestamo->saldo_fin);
                                    ?>
                                    <tr>
                                      <td style="width: 100px;">
                                        <input type="text" name="prestamo_empresa[]" value="<?php echo $prestamo->categoria ?>" class="input-small gasto-cargo" style="width: 150px;" required <?php echo $readonly ?>>
                                        <input type="hidden" name="prestamo_empresa_id[]" value="<?php echo $prestamo->id_categoria ?>" class="input-small vpositive gasto-cargo-id">
                                        <input type="hidden" name="prestamo_id_prestamo[]" value="<?php echo $prestamo->id_prestamo ?>" id="prestamo_id_prestamo" class="input-small vpositive">
                                        <input type="hidden" name="prestamo_del[]" value="" id="prestamo_del">
                                        <input type="hidden" name="prestamo_id_prestamo_nom[]" value="<?php echo $prestamo->id_prestamo_nom ?>" class="input-small vpositive">
                                        <!-- <input type="hidden" name="prestamo_id_empleado[]" value="<?php echo $prestamo->id_empleado ?>" class="input-small vpositive"> -->
                                      </td>
                                      <td>
                                        <input type="text" name="prestamo_empleado[]" value="<?php echo $prestamo->empleado ?>" class="prestamo-empleado span12" maxlength="500" placeholder="Trabajador" required <?php echo $readonly ?>>
                                        <input type="hidden" name="prestamo_empleado_id[]" value="<?php echo $prestamo->id_empleado ?>" class="prestamo-empleado-id span12" required>
                                      </td>
                                      <td><?php echo MyString::fechaAT($prestamo->fecha) ?></td>
                                      <td>
                                        <input type="text" name="prestamo_concepto[]" value="<?php echo $prestamo->concepto ?>" class="prestamo-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                                      </td>
                                      <td style="width: 100px;"><input type="text" name="prestamo_monto[]" value="<?php echo $prestamo->monto ?>" class="prestamo-monto vpositive input-small" placeholder="Monto" required <?php echo ($prestamo->id_prestamo_nom>0? 'readonly': $readonly) ?>></td>
                                      <td><a href="#" class="btn prestamo-cp-pago"><?php echo $prestamo->saldo_ini ?></a></td>
                                      <td><?php echo $prestamo->pago_dia ?></td>
                                      <td></td>
                                      <td><a href="<?php echo base_url('panel/caja_chica_prest/print_prestamocp/?id='.$prestamo->id_pago."&fecha=".$fecha_caja_chica)?>"
                                            target="_blank" title="Imprimir" style="display:<?php echo ($prestamo->id_pago>0? 'block': 'none') ?>">
                                          <i class="ico icon-print" style="cursor:pointer"></i> <?php echo $prestamo->id_pago ?></a></td>
                                      <td><?php echo $prestamo->saldo_fin ?></td>
                                      <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-prestamo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                    </tr>
                            <?php } ?>
                                  <tr class="row-total">
                                    <td colspan="5" style="text-align: right; font-weight: bolder;">SUMAS</td>
                                    <td><?php echo $totalprescp_salini ?></td>
                                    <td><?php echo $totalprescp_pago_dia ?></td>
                                    <td colspan="2"></td>
                                    <td><?php echo $totalprescp_salfin ?></td>
                                    <td></td>
                                  </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Prestamos corto plazo -->

                    <!-- Prestamos a largo y corto plazo -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-presdia">
                          <thead>
                            <tr>
                              <th colspan="10">PRESTAMOS DEL DIA
                              </th>
                              <th></th>
                            </tr>
                            <tr>
                              <th>EMPRESA</th>
                              <th>TRABAJADOR</th>
                              <th>FECHA</th>
                              <th>REFERENCIA</th>
                              <th>CARGO <br>PRESTAMO</th>
                              <th>SALDO <br>INICIAL</th>
                              <th>ABONO <br>DEL DIA</th>
                              <th></th>
                              <th>TICKET <br> INGRESO</th>
                              <th>SALDOS <br> FINALES</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              $totalpreslgcp_monto = $totalpreslgcp_salini = $totalpreslgcp_pago_dia = $totalpreslgcp_salfin = 0;
                                  foreach ($caja['prestamos_dia'] as $prestamo) {
                                    $totalpreslgcp_monto += floatval($prestamo->monto);
                                    $totalpreslgcp_salini += floatval($prestamo->saldo_ini);
                                    $totalpreslgcp_pago_dia += floatval($prestamo->pago_dia);
                                    $totalpreslgcp_salfin += floatval($prestamo->saldo_fin);
                                    if (isset($prestamo->id_prestamo) && $prestamo->id_prestamo > 0) { // corto plazo
                                    ?>
                                    <tr>
                                      <td style="width: 100px;">
                                        <input type="text" name="prestamo_empresa[]" value="<?php echo $prestamo->categoria ?>" class="input-small gasto-cargo" style="width: 150px;" required <?php echo $readonly ?>>
                                        <input type="hidden" name="prestamo_empresa_id[]" value="<?php echo $prestamo->id_categoria ?>" class="input-small vpositive gasto-cargo-id">
                                        <input type="hidden" name="prestamo_id_prestamo[]" value="<?php echo $prestamo->id_prestamo ?>" id="prestamo_id_prestamo" class="input-small vpositive">
                                        <input type="hidden" name="prestamo_del[]" value="" id="prestamo_del">
                                        <input type="hidden" name="prestamo_id_prestamo_nom[]" value="<?php echo $prestamo->id_prestamo_nom ?>" class="input-small vpositive">
                                        <!-- <input type="hidden" name="prestamo_id_empleado[]" value="<?php echo $prestamo->id_empleado ?>" class="input-small vpositive"> -->
                                      </td>
                                      <td>
                                        <input type="text" name="prestamo_empleado[]" value="<?php echo $prestamo->empleado ?>" class="prestamo-empleado span12" maxlength="500" placeholder="Trabajador" required <?php echo $readonly ?>>
                                        <input type="hidden" name="prestamo_empleado_id[]" value="<?php echo $prestamo->id_empleado ?>" class="prestamo-empleado-id span12" required>
                                      </td>
                                      <td><?php echo MyString::fechaAT($prestamo->fecha) ?></td>
                                      <td>
                                        <input type="text" name="prestamo_concepto[]" value="<?php echo $prestamo->concepto ?>" class="prestamo-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                                      </td>
                                      <td style="width: 100px;"><input type="text" name="prestamo_monto[]" value="<?php echo $prestamo->monto ?>" class="prestamo-monto vpositive input-small" placeholder="Monto" required <?php echo ($prestamo->id_prestamo_nom>0? 'readonly': $readonly) ?>></td>
                                      <td><a href="#" class="btn prestamo-cp-pago <?php echo ($prestamo->saldo_fin==0? ' hide': '') ?>"><?php echo $prestamo->saldo_ini ?></a></td>
                                      <td><?php echo $prestamo->pago_dia ?></td>
                                      <td></td>
                                      <td><a href="<?php echo base_url('panel/caja_chica_prest/print_prestamocp/?id='.$prestamo->id_pago."&fecha=".$fecha_caja_chica)?>"
                                            target="_blank" title="Imprimir" style="display:<?php echo ($prestamo->id_pago>0? 'block': 'none') ?>">
                                          <i class="ico icon-print" style="cursor:pointer"></i> <?php echo $prestamo->id_pago ?></a></td>
                                      <td><?php echo $prestamo->saldo_fin ?></td>
                                      <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-prestamo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                    </tr>
                            <?php
                                    } else {
                            ?>
                                    <tr>
                                      <td><?php echo $prestamo->categoria ?></td>
                                      <td><?php echo $prestamo->empleado ?></td>
                                      <td><?php echo MyString::fechaAT($prestamo->fecha) ?></td>
                                      <td><?php echo $prestamo->referencia ?></td>
                                      <td><?php echo $prestamo->monto ?></td>
                                      <td><?php echo $prestamo->saldo_ini ?></td>
                                      <td><?php echo $prestamo->pago_dia ?></td>
                                      <td><?php echo $prestamo->no_pagos.'/'.$prestamo->tno_pagos ?></td>
                                      <td><a href="<?php echo base_url('panel/caja_chica_prest/print_prestamolp/?id='.$prestamo->no_ticket."&fecha=".$fecha_caja_chica)?>"
                                            target="_blank" title="Imprimir" style="display:<?php echo ($prestamo->no_ticket>0? 'block': 'none') ?>">
                                          <i class="ico icon-print" style="cursor:pointer"></i> <?php echo $prestamo->no_ticket ?></a></td>
                                      <td><?php echo $prestamo->saldo_fin ?></td>
                                      <td></td>
                                    </tr>
                            <?php
                                    }
                                  } ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Prestamos a largo y corto plazo -->

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

                    <!-- Descuento de materiales -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-prestamolp">
                          <thead>
                            <tr>
                              <th colspan="9">DESCUENTO DE MATERIALES Y/O HERRAMIENTAS
                                <!-- <button type="button" class="btn btn-success" id="btn-add-prestamo" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button> -->
                                <!-- <a href="#modal-movimientos" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-movimientos" style="padding: 2px 7px 2px; float: right;<?php echo $display ?>">Movimientos</a> -->
                              </th>
                              <th colspan="1">IMPORTE</th>
                            </tr>
                            <tr>
                              <th>EMPRESA</th>
                              <th>TRABAJADOR</th>
                              <th>FECHA</th>
                              <th>REFERENCIA</th>
                              <th>CARGO <br> PRESTAMOS</th>
                              <th>SALDOS <br> INICIALES</th>
                              <th>ABONO <br> DEL DIA</th>
                              <th>No.</th>
                              <th>TICKET <br> INGRESO</th>
                              <th>SALDOS <br> FINALES</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                                  $tipoo = '';
                                  $totalpreslp_salini_fi = $totalpreslp_pago_dia_fi = $totalpreslp_salfin_fi = 0;
                                  $totalpreslp_salini_ef = $totalpreslp_pago_dia_ef = $totalpreslp_salfin_ef = 0;
                                  $totalpreslp_salini_efd = $totalpreslp_pago_dia_efd = $totalpreslp_salfin_efd = 0;
                                  $totalpreslp_ef_rec = [];
                                  foreach ($caja['materiales'] as $prestamo) {
                                      $totalpreslp_salini += floatval($prestamo->saldo_ini);
                                      $totalpreslp_pago_dia += floatval($prestamo->pago_dia);
                                      $totalpreslp_salfin += floatval($prestamo->saldo_fin);

                                      // if ($prestamo->tipo == 'efd') {
                                      //   $totalpreslp_salini_efd += floatval($prestamo->saldo_ini);
                                      //   $totalpreslp_pago_dia_efd += floatval($prestamo->pago_dia);
                                      //   $totalpreslp_salfin_efd += floatval($prestamo->saldo_fin);

                                      //   if (isset($totalpreslp_ef_rec[$prestamo->categoria])) {
                                      //     $totalpreslp_ef_rec[$prestamo->categoria] += $prestamo->pago_dia;
                                      //   } else {
                                      //     $totalpreslp_ef_rec[$prestamo->categoria] = $prestamo->pago_dia;
                                      //   }
                                      // } elseif ($prestamo->tipo == 'ef') {
                                      //   $totalpreslp_salini_ef += floatval($prestamo->saldo_ini);
                                      //   $totalpreslp_pago_dia_ef += floatval($prestamo->pago_dia);
                                      //   $totalpreslp_salfin_ef += floatval($prestamo->saldo_fin);

                                      //   // if (isset($totalpreslp_ef_rec[$prestamo->categoria])) {
                                      //   //   $totalpreslp_ef_rec[$prestamo->categoria] += $prestamo->pago_dia;
                                      //   // } else {
                                      //   //   $totalpreslp_ef_rec[$prestamo->categoria] = $prestamo->pago_dia;
                                      //   // }
                                      // } else {
                                        $totalpreslp_salini_fi += floatval($prestamo->saldo_ini);
                                        $totalpreslp_pago_dia_fi += floatval($prestamo->pago_dia);
                                        $totalpreslp_salfin_fi += floatval($prestamo->saldo_fin);
                                      // }

                                      if ($tipoo != $prestamo->tipo && $prestamo->tipo != 'mt') {
                                        switch ($prestamo->tipo) {
                                          case 'efd': $tipo = 'Efectivo Fijo'; break;
                                          case 'ef': $tipo = 'Efectivo'; break;
                                          default: $tipo = 'Fiscal'; break;
                                        }
                                        $tipoo = $prestamo->tipo;
                            ?>
                                    <tr>
                                      <td colspan="10"><strong><?php echo $tipo ?></strong></td>
                                    </tr>
                            <?php
                                      }
                            ?>
                                    <tr>
                                      <td><?php echo $prestamo->categoria ?>
                                        <a href="<?php echo base_url('panel/caja_chica_prest/print_prestamolp/?id='.$prestamo->id_prestamo_nom)?>" target="_blank" title="Imprimir vale prestamo">
                                          <i class="ico icon-print" style="cursor:pointer"></i></a>
                                      </td>
                                      <td><?php echo $prestamo->empleado ?></td>
                                      <td><?php echo MyString::fechaAT($prestamo->fecha) ?></td>
                                      <td><?php echo $prestamo->referencia ?></td>
                                      <td><?php echo $prestamo->monto ?></td>
                                      <td><?php echo $prestamo->saldo_ini ?></td>
                                      <td><?php echo $prestamo->pago_dia ?></td>
                                      <td><?php echo $prestamo->no_pagos.'/'.$prestamo->tno_pagos ?></td>
                                      <td><a href="<?php echo base_url('panel/caja_chica_prest/print_prestamolp/?id='.$prestamo->no_ticket."&fecha=".$fecha_caja_chica)?>"
                                            target="_blank" title="Imprimir" style="display:<?php echo ($prestamo->no_ticket>0? 'block': 'none') ?>">
                                          <i class="ico icon-print" style="cursor:pointer"></i> <?php echo $prestamo->no_ticket ?></a></td>
                                      <td>
                                        <?php if ($priv_saldar_prestamo): ?>
                                          <a href="<?php echo base_url('panel/caja_chica_prest/saldar_prestamos/?id='.$prestamo->id_prestamo_nom."&fecha=".$fecha_caja_chica."&fno_caja=".$_GET['fno_caja'])?>"
                                            onclick="msb.confirm('Estas seguro saldar este préstamo? \n No se podrá revertir.', 'Prestamos', this); return false;">
                                        <?php endif ?>

                                        <?php echo $prestamo->saldo_fin ?>

                                        <?php if ($priv_saldar_prestamo): ?>
                                          </a>
                                        <?php endif ?>
                                      </td>
                                    </tr>
                            <?php } ?>
                                <tr class="row-total">
                                  <td colspan="5" style="text-align: right; font-weight: bolder;">Suma</td>
                                  <td><?php echo $totalpreslp_salini_fi ?></td>
                                  <td><?php echo $totalpreslp_pago_dia_fi ?></td>
                                  <td colspan="2"></td>
                                  <td><?php echo $totalpreslp_salfin_fi ?></td>
                                </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Descuento de materiales -->

                    <!-- Deudores -->
                    <?php $totalDeudores = 0; ?>
                    <div class="row-fluid" style="margin-top: 5px;">
                      <div class="span12">
                        <div class="row-fluid">
                          <div class="span12">
                            <div class="row-fluid">
                              <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">GASTOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;float: right;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                              <div class="row-fluid">
                                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-deudor">
                                    <thead>
                                      <tr>
                                        <th colspan="9">DEUDORES
                                          <?php if ($show): ?>
                                            <button type="button" class="btn btn-success" id="btn-add-deudor" style="padding: 2px 7px 2px;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button>
                                          <?php endif ?>
                                        </th>
                                      </tr>
                                      <tr>
                                        <th>FECHA</th>
                                        <th>TIPO</th>
                                        <th>NOM</th>
                                        <th>NOMBRE</th>
                                        <th>CONCEPTO</th>
                                        <th>PRESTADO</th>
                                        <th>ABONOS</th>
                                        <th>SALDO</th>
                                        <th></th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                        $modificar_gasto = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/modificar_gastos/');
                                        $mod_gas_readonly = !$modificar_gasto && $readonly == ''? ' readonly': '';

                                        if (count($caja['deudores']) == 0 && isset($_POST['deudor_nombre']) && count($_POST['deudor_nombre']) > 0) {
                                          foreach ($_POST['deudor_nombre'] as $key => $concepto) {
                                            $totalDeudores += floatval($_POST['deudor_importe'][$key]); ?>
                                              <tr>
                                                <td style="">
                                                  <input type="hidden" name="deudor_fecha[]" value="">
                                                </td>
                                                <td style="width: 80px;">
                                                  <select name="deudor_tipo[]" style="width: 80px;">
                                                    <option value="otros" <?php echo $_POST['deudor_tipo'][$key]=='otros'? 'selected': ''; ?>>Otros</option>
                                                    <option value="caja_limon" <?php echo $_POST['deudor_tipo'][$key]=='caja_limon'? 'selected': ''; ?>>Caja limón</option>
                                                    <option value="caja_gastos" <?php echo $_POST['deudor_tipo'][$key]=='caja_gastos'? 'selected': ''; ?>>Caja gastos</option>
                                                    <option value="caja_general" <?php echo $_POST['deudor_tipo'][$key]=='caja_general'? 'selected': ''; ?>>Caja Distribuidora</option>
                                                    <option value="prestamo" <?php echo $_POST['deudor_tipo'][$key]=='prestamo'? 'selected': ''; ?>>Prestamo</option>
                                                  </select>
                                                </td>
                                                <td style="width: 80px;">
                                                  <select name="deudor_nomenclatura[]" class="span12 deudor_nomenclatura" <?php echo $readonly ?>>
                                                    <?php foreach ($nomenclaturas as $n) { ?>
                                                      <?php if ($n->tipo === 'f'): ?>
                                                      <option value="<?php echo $n->id ?>" <?php echo $_POST['deudor_nomenclatura'][$key] == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                                      <?php endif ?>
                                                    <?php } ?>
                                                  </select>
                                                </td>
                                                <td style="width: 200px;">
                                                  <input type="text" name="deudor_nombre[]" value="<?php echo $_POST['deudor_nombre'][$key] ?>" class="span12 deudor_nombre" required autocomplete="off" <?php echo $readonly.$mod_gas_readonly ?>>
                                                  <input type="hidden" name="deudor_id_deudor[]" value="<?php echo $_POST['deudor_id_deudor'][$key] ?>" id="deudor_id_gasto">
                                                  <input type="hidden" name="deudor_del[]" value="" id="deudor_del">
                                                </td>
                                                <td style="width: 200px;">
                                                  <input type="text" name="deudor_concepto[]" value="<?php echo $_POST['deudor_concepto'][$key] ?>" class="span12 deudor-cargo" required <?php echo $readonly.$mod_gas_readonly ?>>
                                                </td>
                                                <td style="width: 80px;">
                                                  <input type="text" name="deudor_importe[]" value="<?php echo $_POST['deudor_importe'][$key] ?>" class="span12 vpositive deudor-importe" <?php echo $readonly.$mod_gas_readonly ?>>
                                                </td>
                                                <td style="width: 80px;" class="deudor_abonos" data-abonos="0">
                                                </td>
                                                <td style="width: 80px;" class="deudor_saldo" data-saldo="0">
                                                </td>
                                                <td style="width: 30px;">
                                                  <?php if ($modificar_gasto): ?>
                                                    <button type="button" class="btn btn-danger btn-del-deudor" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                                  <?php endif ?>
                                                </td>
                                              </tr>
                                      <?php }} else {
                                        foreach ($caja['deudores'] as $deudor) {
                                          $totalDeudores += floatval($deudor->saldo);
                                        ?>
                                        <tr>
                                          <td style="width: 80px;">
                                            <?php echo $deudor->fecha ?>
                                            <input type="hidden" name="deudor_fecha[]" value="<?php echo $deudor->fecha ?>">
                                          </td>
                                          <td style="width: 80px;">
                                            <?php echo str_replace('_', ' ', $deudor->tipo); ?>
                                            <input type="hidden" name="deudor_tipo[]" value="<?php echo $deudor->tipo ?>">
                                          </td>
                                          <td style="width: 80px;">
                                            <select name="deudor_nomenclatura[]" class="span12 deudor_nomenclatura" <?php echo $readonly ?>>
                                              <?php foreach ($nomenclaturas as $n) { ?>
                                                <?php if ($n->tipo === 'f'): ?>
                                                <option value="<?php echo $n->id ?>" <?php echo $deudor->id_nomenclatura == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                                <?php endif ?>
                                              <?php } ?>
                                            </select>
                                          </td>
                                          <td style="width: 200px;">
                                            <input type="text" name="deudor_nombre[]" value="<?php echo $deudor->nombre ?>" class="span12 deudor_nombre" required autocomplete="off" <?php echo $deudor->mismo_dia.$readonly.$mod_gas_readonly ?>>
                                            <input type="hidden" name="deudor_id_deudor[]" value="<?php echo $deudor->id_deudor ?>" id="deudor_id_gasto">
                                            <input type="hidden" name="deudor_del[]" value="" id="deudor_del">
                                            <a href="<?php echo base_url('panel/caja_chica/print_vale_deudor/?id='.$deudor->id_deudor.'&noCaja='.$deudor->no_caja)?>" target="_blank" title="Imprimir vale prestamo">
                                              <i class="ico icon-print" style="cursor:pointer"></i></a>
                                          </td>
                                          <td style="width: 200px;">
                                            <input type="text" name="deudor_concepto[]" value="<?php echo $deudor->concepto ?>" class="span12 deudor-cargo" required <?php echo $deudor->mismo_dia.$readonly.$mod_gas_readonly ?>>
                                          </td>
                                          <td style="width: 80px;">
                                            <input type="text" name="deudor_importe[]" value="<?php echo $deudor->monto ?>" class="span12 vpositive deudor-importe" <?php echo $deudor->mismo_dia.$readonly.$mod_gas_readonly ?>>
                                          </td>
                                          <td style="width: 80px;" class="deudor_abonos" data-abonos="<?php echo $deudor->abonos ?>">
                                            <?php echo $deudor->abonos ?>
                                          </td>
                                          <td style="width: 80px;" class="deudor_saldo" data-saldo="<?php echo $deudor->saldo ?>" data-mismo="<?php echo $deudor->mismo_dia ?>">
                                            <?php if ((!isset($caja['status']) || $caja['status'] === 't') && $show):
                                              $noCajaDeudor = $_GET['fno_caja']+100;
                                            ?>
                                            <a class="btn_abonos_deudores" href="<?php echo base_url('panel/caja_chica/agregar_abono_deudor/')."?id={$deudor->id_deudor}&fecha={$fecha}&no_caja={$noCajaDeudor}&monto={$deudor->saldo}" ?>" style="" rel="superbox-50x500" title="Abonar">
                                              <?php echo $deudor->saldo ?></a>
                                            <?php else: ?>
                                              <?php echo $deudor->saldo ?>
                                            <?php endif ?>
                                          </td>
                                          <td style="width: 30px;">
                                            <?php if ($modificar_gasto && $deudor->mismo_dia == '' && $show): ?>
                                              <button type="button" class="btn btn-danger btn-del-deudor" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                            <?php endif ?>
                                          </td>
                                        </tr>
                                      <?php }} ?>
                                      <tr class="row-total">
                                        <td colspan="2"></td>
                                        <td style="text-align: right; font-weight: bolder;">PRESTAMOS DEL DIA</td>
                                        <td style="text-align: right; font-weight: bolder;">
                                          <input type="text" value="<?php echo $caja['deudores_prest_dia'] ?>" class="input-small vpositive" id="total-deudores-pres-dia" style="text-align: right;" readonly>
                                        </td>
                                        <td style="text-align: right; font-weight: bolder;">ABONOS DEL DIA</td>
                                        <td style="text-align: right; font-weight: bolder;">
                                          <input type="text" value="<?php echo $caja['deudores_abonos_dia'] ?>" class="input-small vpositive" id="total-deudores-abono-dia" style="text-align: right;" readonly>
                                        </td>
                                        <td style="text-align: right; font-weight: bolder;">TOTAL</td>
                                        <td><input type="text" value="<?php echo $totalDeudores ?>" class="input-small vpositive" id="total-deudores" style="text-align: right;" readonly></td>
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
                    <!-- /Deudores -->

                    <?php
                    $totalAcreedores = $totalAcreedoresHoy = 0;
                    ?>
                    <!-- Acreedores -->
                    <div class="row-fluid" style="margin-top: 5px;">
                      <div class="span12">
                        <div class="row-fluid">
                          <div class="span12">
                            <div class="row-fluid">
                              <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">GASTOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;float: right;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                              <div class="row-fluid">
                                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-acreedor">
                                    <thead>
                                      <tr>
                                        <th colspan="8">ACREEDOR CAJA</th>
                                      </tr>
                                      <tr>
                                        <th>FECHA</th>
                                        <th>TIPO</th>
                                        <th>NOMBRE</th>
                                        <th>CONCEPTO</th>
                                        <th>PRESTADO</th>
                                        <th>ABONOS</th>
                                        <th>SALDO</th>
                                        <th></th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                      if (count($caja['acreedores']) > 0) {
                                        foreach ($caja['acreedores'] as $acreedor) {
                                          $totalAcreedores += floatval($acreedor->saldo);
                                          // if ($acreedor->mismo_dia) {
                                          //   $totalAcreedoresHoy += floatval($acreedor->saldo);
                                          // }
                                        ?>
                                        <tr>
                                          <td style="width: 80px;">
                                            <?php echo $acreedor->fecha ?>
                                          </td>
                                          <td style="width: 80px;">
                                            <?php echo str_replace('_', ' ', $acreedor->tipo); ?>
                                          </td>
                                          <td style="width: 200px;">
                                            <?php echo $acreedor->nombre ?>
                                          </td>
                                          <td style="width: 200px;">
                                            <?php echo $acreedor->concepto ?>
                                          </td>
                                          <td style="width: 80px;">
                                            <?php echo $acreedor->monto ?>
                                          </td>
                                          <td style="width: 80px;">
                                            <?php echo $acreedor->abonos ?>
                                          </td>
                                          <td style="width: 80px;">
                                            <?php echo $acreedor->saldo ?>
                                          </td>
                                          <td style="width: 30px;">
                                          </td>
                                        </tr>
                                      <?php }
                                      } ?>
                                      <tr class="row-total">
                                        <td></td>
                                        <td style="text-align: right; font-weight: bolder;">PRESTAMOS DEL DIA</td>
                                        <td style="text-align: right; font-weight: bolder;">
                                          <input type="text" value="<?php echo $caja['acreedor_prest_dia'] ?>" class="input-small vpositive" id="total-acreddor-pres-dia" style="text-align: right;" readonly>
                                        </td>
                                        <td style="text-align: right; font-weight: bolder;">ABONOS DEL DIA</td>
                                        <td style="text-align: right; font-weight: bolder;">
                                          <input type="text" value="<?php echo $caja['acreedor_abonos_dia'] ?>" class="input-small vpositive" id="total-acreddor-abono-dia" style="text-align: right;" readonly>
                                        </td>
                                        <td style="text-align: right; font-weight: bolder;">TOTAL</td>
                                        <td><input type="text" value="<?php echo $totalAcreedores ?>" class="input-small vpositive" id="total-acreddor" style="text-align: right;" readonly></td>
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
                    <!-- /Acreedores -->

                </div>
              </div>
            </div>

            <div class="span6">

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
                                  <td style="text-align: right;"><?php echo MyString::formatoNumero($_POST['denominacion_denom'][$key], 2, '$') ?></td>
                                  <td><input type="text" name="denominacion_total[]" value="<?php echo MyString::float($_POST['denominacion_total'][$key]) ?>" class="input-small vpositive denom-total" style="text-align: right;" <?php echo $readonly ?>></td>
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
                            <td style="text-align: right;"><?php echo MyString::formatoNumero($denominacion['denominacion'], 2, '$') ?></td>
                            <td><input type="text" name="denominacion_total[]" value="<?php echo MyString::float($denominacion['total']) ?>" class="input-small vpositive denom-total" style="text-align: right;" <?php echo $readonly ?>></td>
                          </tr>
                        <?php }} ?>
                        <tbody>
                          <tr>
                            <td colspan="2">TOTAL EFECTIVO</td>
                            <td id="total-efectivo-den" style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($totalEfectivo, 2, '$') ?></td>
                          </tr>
                          <!-- <tr>
                            <td colspan="2">TOTAL DIFERENCIA</td>
                            <td id="total-efectivo-diferencia" style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($totalEfectivo, 2, '$') ?></td>
                          </tr> -->
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
                          <?php
                          $tt_saldo_inicial       = $totalpreslp_salini+$totalprescp_salini;
                          $tt_saldo_finales       = $totalpreslp_salfin+$totalprescp_salfin+$totalpreslgcp_salfin;
                          $tt_efectivo_anterior   = $saldofc-$tt_saldo_inicial;
                          $tt_caja_ingreso        = $totalpreslp_pago_dia+$totalprescp_pago_dia+$totalpreslgcp_pago_dia;
                          $tt_caja_egreso         = $totalpreslgcp_monto;
                          $tt_efectivo_disponible = $tt_efectivo_anterior+$tt_caja_ingreso-$tt_caja_egreso;
                          ?>
                            <tr>
                              <td>SALDO INICIAL:</td>
                              <td><input type="text" name="" value="<?php echo $tt_saldo_inicial; //$caja['saldo_inicial'] ?>" class="input-small vpositive" id="total-saldo-inicial" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>EFECTIVO ANTERIOR:</td>
                              <td><input type="text" name="" value="<?php echo $tt_efectivo_anterior; //$caja['saldo_inicial'] ?>" class="input-small vpositive" id="total-efectivo-anter" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>CAJA INGRESOS:</td>
                              <td><input type="text" name="" value="<?php echo $tt_caja_ingreso; ?>" class="input-small vpositive" id="total-saldo-prestamo" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>CAJA EGRESOS:</td>
                              <td><input type="text" name="" value="<?php echo $totalpreslgcp_monto ?>" class="input-small vpositive" id="ttotal-pagos" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>EFECTIVO DISPONIBLE:</td>
                              <td><input type="text" name="saldo_corte" value="<?php echo $tt_efectivo_disponible ?>" class="input-small vpositive" id="ttotal-corte" style="text-align: right;" readonly></td>
                            </tr>
                            <tr>
                              <td>DIFERENCIA DEL CORTE:</td>
                              <td><input type="text" name="total_diferencia" value="<?php echo $tt_efectivo_disponible-$totalEfectivo ?>" class="input-small vpositive" id="ttotal-diferencia" style="text-align: right;" readonly></td>
                            </tr>
                            <!-- <tr>
                              <td>SALDO DEL CORTE:</td>
                              <td><input type="text" name="saldo_corte" value="<?php echo $caja['saldo_inicial'] - $totalprestamos + $totalpagos ?>" class="input-small vpositive" id="ttotal-corte" style="text-align: right;" readonly></td>
                            </tr> -->
                            <!-- <tr>
                              <td colspan="2"></td>
                            </tr> -->
                            <tr>
                              <td>FONDO DE CAJA:</td>
                              <td><input type="text" name="fondo_caja" value="<?php echo ($totalEfectivo+($tt_efectivo_disponible-$totalEfectivo)+$tt_saldo_finales); ?>" class="input-small vpositive" id="ttotal-fondo_caja" style="text-align: right;" readonly></td>
                            </tr>
                          </tbody>
                        </table>

                        <div class="span12" style="margin-left: 0;"> <br>
                          <?php if ($show){ ?>
                            <div class="span5"><button type="submit" class="btn btn-success btn-large span12">Guardar</button></div>
                          <?php } ?>

                          <?php if (isset($caja['status']) && $caja['status'] === 't'){ ?>
                            <div class="span5"><a href="<?php echo base_url('panel/caja_chica_prest/cerrar_caja/?id='.$caja['id'].'&'.MyString::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12">Cerrar Caja</a></div>
                          <?php } ?>

                          <?php if (isset($caja['status']) && $caja['status'] === 'f') { ?>
                            <div class="span5"><a href="<?php echo base_url('panel/caja_chica_prest/print_caja?'.MyString::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
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

  <!-- Modal -->
  <div id="addPrestamosCp" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <form class="form-horizontal" action="<?php echo base_url();?>panel/caja_chica_prest/abono_prestamo_cp" method="POST" id="frmcajachicapres" name="frmcajachicapres">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel">Prestamo a corto plazo</h3>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id_prestamo_caja" id="pc_id_prestamo_caja" value="">
        <input type="hidden" name="no_caja" id="pc_no_caja" value="">
        <input type="hidden" name="id_categoria" id="pc_id_categoria" value="">
        <div class="control-group">
          <label class="control-label" for="fecha">*Fecha </label>
          <div class="controls">
            <input type="date" name="fecha" id="pc_fecha" value="" class="input-xlarge" required>
          </div>
        </div>

        <div class="control-group">
          <label class="control-label" for="concepto">*Concepto </label>
          <div class="controls">
            <input type="text" name="concepto" id="pc_concepto" value="" class="input-xlarge" required>
          </div>
        </div>

        <div class="control-group">
          <label class="control-label" for="monto">*Monto </label>
          <div class="controls">
            <input type="text" name="monto" id="pc_monto" value="" class="input-xlarge vpositive" required>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
        <button class="btn btn-primary">Guardar</button>
      </div>
    </form>
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