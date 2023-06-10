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
        $action = base_url('panel/bodega_guadalajara/cargar/?'.MyString::getVarsLink(array('msg')));
        if (isset($caja['status']) && $caja['status'] === 'f' && ! $this->usuarios_model->tienePrivilegioDe('', 'bodega_guadalajara/modificar_caja/'))
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

        <form class="form-horizontal" action="<?php echo $action ?>" method="POST" id="frmcajachica">
          <?php $totalIngresosExt = 0; $totalIngresos = 0; $totalSaldoIngresos = 0; ?>
          <!-- Header -->
          <div class="span12" style="margin: 10px 0 0 0;">
            <div class="row-fluid">
              <div class="span4" style="text-align: center;">
                <img alt="logo" src="<?php echo base_url(); ?>/application/images/logo.png" height="54">
              </div>
              <div class="span2" style="text-align: right;">
                <div class="row-fluid">
                  <div class="span12">Fecha <input type="date" name="fecha_caja_chica" value="<?php echo set_value('fecha_caja_chica', isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d')) ?>" id="fecha_caja" class="input-medium" readonly></div>

                  <?php $fecha = (isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d')); ?>
                </div>
              </div>
              <div class="span4">
                <div class="row-fluid">
                  <input type="hidden" name="fno_caja" id="fno_caja" value="<?php echo $_GET['fno_caja']; ?>">

                  <?php if ($show){ ?>
                    <div class="span4"><input type="submit" class="btn btn-success btn-large span12" value="Guardar"></div>
                  <?php } ?>

                  <?php if (isset($caja['status']) && $caja['status'] === 't'){ ?>
                    <div class="span4"><a href="<?php echo base_url('panel/bodega_guadalajara/cerrar_caja/?id='.$caja['id'].'&'.MyString::getVarsLink(array('msg', 'id'))) ?>" class="btn btn-success btn-large span12">Cerrar Caja</a></div>
                  <?php } ?>

                  <?php if (isset($caja['status']) && $caja['status'] === 'f') { ?>
                    <div class="span4"><a href="<?php echo base_url('panel/bodega_guadalajara/print_caja?'.MyString::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
                  <?php }  ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Ingresos -->
          <div class="row-fluid">
            <div class="span6">
              <div class="row-fluid">
                <div class="span12">

                    <!-- Cuentas x cobrar -->
                    <div class="row-fluid">
                      <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">OTROS <button type="button" class="btn btn-success" id="btn-add-otros" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-remisiones">
                          <thead>
                            <tr>
                              <th colspan="4">CUENTAS POR COBRAR</th>
                              <th>IMPORTE</th>
                              <th>
                                <?php echo $this->usuarios_model->getLinkPrivSm('cuentas_cobrar/agregar_abono/', array(
                                  'params'   => "",
                                  'btn_type' => 'btn-success pull-right btn_abonos_masivo',
                                  'attrs' => array('style' => 'display:none;', 'rel' => 'superbox-50x500') )
                                ); ?>
                              </th>
                            </tr>
                            <tr>
                              <th>CLIENTE</th>
                              <th>FECHA</th>
                              <th>REM No.</th>
                              <th>S/INICIAL</th>
                              <th>INGRESOS</th>
                              <th>S/FINAL</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                                  $totalSalAnt = $totalCont = $totalSal = 0;
                                  $aux_client = 0;
                                  foreach ($caja['cts_cobrar'] as $ct_cobrar) {
                                    $totalSalAnt += floatval($ct_cobrar->saldo_ant);
                                    $totalCont += floatval($ct_cobrar->abonos_hoy);
                                    $totalSal += floatval($ct_cobrar->saldo);
                                  ?>
                                    <tr>
                                      <td style="width: 120px;"><?php echo ($ct_cobrar->id_cliente != $aux_client ? $ct_cobrar->cliente: '') ?></td>
                                      <td style="width: 50px;"><?php echo $ct_cobrar->fecha ?></td>
                                      <td style="width: 70px;"><?php echo $ct_cobrar->serie.$ct_cobrar->folio ?></td>
                                      <td style="width: 100px;"><?php echo $ct_cobrar->saldo_ant ?></td>
                                      <td style="width: 100px;"><?php echo $ct_cobrar->abonos_hoy ?></td>
                                      <!-- <td style="width: 100px;"><?php echo $ct_cobrar->saldo ?></td> -->
                                      <td style="width: 100px;text-align: right;" class="<?php echo $ct_cobrar->cliente!=''?'sel_abonom':''; ?>"
                                        data-id="<?php echo $ct_cobrar->id_factura; ?>" data-tipo="f"><?php echo $ct_cobrar->saldo ?></td>
                                    </tr>
                                  <?php
                                    $aux_client = $ct_cobrar->id_cliente != $aux_client ? $ct_cobrar->id_cliente: $aux_client;
                                  } ?>

                            <tr class='row-total'>
                              <td colspan="3"></td>
                              <td><input type="text" name="totalSalAnt" value="<?php echo MyString::float(MyString::formatoNumero($totalSalAnt, 2, '')) ?>" class="span12" id="totalSalAnt" maxlength="500" readonly style="text-align: right;"></td>
                              <td><input type="text" name="totalCont" value="<?php echo MyString::float(MyString::formatoNumero($totalCont, 2, '')) ?>" class="span12" id="totalCont" maxlength="500" readonly style="text-align: right;"></td>
                              <td><input type="text" name="totalSal" value="<?php echo MyString::float(MyString::formatoNumero($totalSal, 2, '')) ?>" class="span12" id="totalSal" maxlength="500" readonly style="text-align: right;"></td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Cuentas x cobrar -->

                    <!-- Ingresos por Reposicion-->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-ingresos">
                          <thead>
                            <tr>
                              <th colspan="4">INGRESOS POR REPOSICION
                                <button type="button" class="btn btn-success" id="btn-add-ingreso" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button>
                                <!-- <a href="#modal-movimientos" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-movimientos" style="padding: 2px 7px 2px; float: right;<?php echo $display ?>">Movimientos</a> -->
                              </th>
                              <th colspan="2">IMPORTE</th>
                            </tr>
                            <tr>
                              <th>EMPRESA</th>
                              <th>NOM</th>
                              <th>POLIZA</th>
                              <th>NOMBRE Y/O CONCEPTO</th>
                              <th>ABONO</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              if (isset($_POST['ingreso_concepto'])) {
                                foreach ($_POST['ingreso_concepto'] as $key => $concepto) {
                                    $totalIngresosExt += floatval($_POST['ingreso_monto'][$key]);
                                  ?>
                                  <tr>
                                    <td style="width: 100px;">
                                      <input type="hidden" name="ingreso_id_ingresos[]" value="" id="ingreso_id_ingresos">
                                      <input type="hidden" name="ingreso_del[]" value="" id="ingreso_del">
                                      <input type="text" name="ingreso_empresa[]" value="<?php echo $_POST['ingreso_empresa'][$key] ?>" class="input-small gasto-cargo" style="width: 150px;" required <?php echo $readonly ?>>
                                      <input type="hidden" name="ingreso_empresa_id[]" value="<?php echo $_POST['ingreso_empresa_id'][$key] ?>" class="input-small vpositive gasto-cargo-id">
                                    </td>
                                    <td style="width: 40px;">
                                      <select name="ingreso_nomenclatura[]" class="ingreso_nomenclatura" style="width: 70px;" <?php echo $readonly ?>>
                                        <?php foreach ($nomenclaturas as $n) { ?>
                                          <option value="<?php echo $n->id ?>" <?php echo $_POST['ingreso_nomenclatura'][$key] == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                        <?php } ?>
                                      </select>
                                    </td>
                                    <td style="width: 100px;"><input type="text" name="ingreso_poliza[]" value="<?php echo $_POST['ingreso_poliza'][$key] ?>" class="ingreso_poliza span12" maxlength="100" placeholder="Poliza" style="width: 100px;" <?php echo $readonly ?>></td>
                                    <td>
                                      <input type="text" name="ingreso_concepto[]" value="<?php echo $concepto ?>" class="ingreso-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                                      <input type="hidden" name="ingreso_concepto_id[]" value="<?php echo $_POST['ingreso_concepto_id'][$key] ?>" class="ingreso_concepto_id span12" placeholder="Concepto">
                                    </td>
                                    <td style="width: 100px;"><input type="text" name="ingreso_monto[]" value="<?php echo $_POST['ingreso_monto'][$key] ?>" class="ingreso-monto vpositive input-small" placeholder="Monto" required <?php echo $readonly ?>></td>
                                    <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-ingreso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                  </tr>
                            <?php }} else {
                                  foreach ($caja['ingresos'] as $ingreso) {
                                      $totalIngresosExt += floatval($ingreso->monto);
                                    ?>
                                    <tr>
                                      <td style="width: 100px;">
                                        <input type="hidden" name="ingreso_id_ingresos[]" value="<?php echo $ingreso->id_ingresos ?>" id="ingreso_id_ingresos">
                                        <input type="hidden" name="ingreso_del[]" value="" id="ingreso_del">
                                        <input type="text" name="ingreso_empresa[]" value="<?php echo $ingreso->categoria ?>" class="input-small gasto-cargo" style="width: 150px;" required <?php echo $readonly ?>>
                                        <input type="hidden" name="ingreso_empresa_id[]" value="<?php echo $ingreso->id_categoria ?>" class="input-small vpositive gasto-cargo-id">
                                        <a href="<?php echo base_url('panel/bodega_guadalajara/print_vale_ipr/?id_ingresos='.$ingreso->id_ingresos.'&noCaja='.$ingreso->no_caja)?>" target="_blank" title="Imprimir Ingreso por reposicion">
                                          <i class="ico icon-print" style="cursor:pointer"></i></a>
                                      </td>
                                      <td style="width: 40px;">
                                        <select name="ingreso_nomenclatura[]" class="ingreso_nomenclatura" style="width: 70px;" <?php echo $readonly ?>>
                                          <?php foreach ($nomenclaturas as $n) { ?>
                                            <option value="<?php echo $n->id ?>" <?php echo $ingreso->nomenclatura == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                          <?php } ?>
                                        </select>
                                      </td>
                                      <td style="width: 100px;"><input type="text" name="ingreso_poliza[]" value="<?php echo $ingreso->poliza ?>" class="ingreso_poliza span12" maxlength="100" placeholder="Poliza" style="width: 100px;" <?php echo $readonly ?>></td>
                                      <td>
                                        <input type="text" name="ingreso_concepto[]" value="<?php echo $ingreso->concepto ?>" class="ingreso-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                                        <input type="hidden" name="ingreso_concepto_id[]" value="<?php echo $ingreso->id_movimiento ?>" class="ingreso_concepto_id span12" placeholder="Concepto">
                                      </td>
                                      <td style="width: 100px;"><input type="text" name="ingreso_monto[]" value="<?php echo $ingreso->monto ?>" class="ingreso-monto vpositive input-small" placeholder="Monto" required <?php echo $readonly ?>></td>
                                      <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-ingreso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                    </tr>
                            <?php }} ?>
                          </tbody>
                          <tfoot>
                            <tr class='row-total'>
                              <td colspan="4"></td>
                              <td style="width: 100px;"><input type="text" name="total_ingresos_ext" value="<?php echo MyString::float(MyString::formatoNumero($totalIngresosExt, 2, '')) ?>" class="span12" id="total-ingresos-ext" maxlength="500" readonly style="text-align: right;"></td>
                              <td></td>
                            </tr>
                          </tfoot>
                        </table>
                      </div>
                    </div>
                    <!--/ Ingresos por Reposicion-->

                    <!-- Existencia anterior -->
                    <div class="row-fluid">
                      <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">OTROS <button type="button" class="btn btn-success" id="btn-add-otros" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-remisiones">
                          <thead>
                            <tr>
                              <th colspan="4">EXISTENCIA ANTERIOR</th>
                              <th colspan="2">IMPORTE</th>
                            </tr>
                            <tr>
                              <th>REM No.</th>
                              <th>PROVEEDOR</th>
                              <th>CLASIF.</th>
                              <th>BULTOS</th>
                              <th>PRECIO</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                                  $totalExisAnt = $bultosExisAnt = $aux = 0;
                                  foreach ($caja['existencia_ant'] as $exis_ant) {
                                    if ($aux == $exis_ant->id_factura) {
                                      $exis_ant->nombre_fiscal = '';
                                      $exis_ant->serie = '';
                                      $exis_ant->folio = '';
                                    } else
                                      $aux = $exis_ant->id_factura;
                                    $totalExisAnt += floatval($exis_ant->importe);
                                    $bultosExisAnt += floatval($exis_ant->cantidad);
                                  ?>
                                    <tr>
                                      <td style="width: 50px;"><?php echo $exis_ant->serie.$exis_ant->folio ?></td>
                                      <td style="width: 120px;"><?php echo $exis_ant->nombre_fiscal ?>
                                      </td>
                                      <td style="width: 70px;"><?php echo $exis_ant->codigo ?></td>
                                      <td style="width: 50px;"><?php echo $exis_ant->cantidad ?></td>
                                      <td style="width: 70px;"><?php echo $exis_ant->precio_unitario ?></td>
                                      <td style="width: 100px;"><?php echo $exis_ant->importe ?></td>
                                    </tr>
                            <?php } ?>

                            <tr class='row-total'>
                              <td colspan="3"></td>
                              <td><input type="text" name="bultos_exis_ant" value="<?php echo MyString::float(MyString::formatoNumero($bultosExisAnt, 2, '')) ?>" class="span12" id="total-ingresos" maxlength="500" readonly style="text-align: right;"></td>
                              <td><input type="text" name="pu_exis_ant" value="<?php echo MyString::float(MyString::formatoNumero($totalExisAnt/($bultosExisAnt>0?$bultosExisAnt:1), 2, '')) ?>" class="span12" id="total-ingresos" maxlength="500" readonly style="text-align: right;"></td>
                              <td style="width: 100px;"><input type="text" name="total_exis_ant" value="<?php echo MyString::float(MyString::formatoNumero($totalExisAnt, 2, '')) ?>" class="span12" id="total_exis_ant" maxlength="500" readonly style="text-align: right;"></td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Existencia anterior -->

                    <!-- Ingresos del dia -->
                    <div class="row-fluid">
                      <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">OTROS <button type="button" class="btn btn-success" id="btn-add-otros" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-remisiones">
                          <thead>
                            <tr>
                              <th colspan="4">INGRESOS DE MERCANCIAS
                                <!-- <button type="button" class="btn btn-success" id="btn-add-otros" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button> -->
                                <!-- <a href="#modal-remisiones" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-remisiones" style="padding: 2px 7px 2px; float: right; <?php echo $display ?>">Remisiones</a> -->
                              </th>
                              <th colspan="2">IMPORTE</th>
                            </tr>
                            <tr>
                              <th>REM No.</th>
                              <th>PROVEEDOR</th>
                              <th>CLASIF.</th>
                              <th>BULTOS</th>
                              <th>PRECIO</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                                  $totalIngresos = $bultosIngresos = $aux = 0;
                                  foreach ($caja['remisiones'] as $remision) {
                                    if ($aux == $remision->id_factura) {
                                      $remision->nombre_fiscal = '';
                                      $remision->serie = '';
                                      $remision->folio = '';
                                    } else
                                      $aux = $remision->id_factura;
                                    $totalIngresos += floatval($remision->importe);
                                    $bultosIngresos += floatval($remision->cantidad);
                                  ?>
                                    <tr>
                                      <td style="width: 50px;"><?php echo $remision->serie.$remision->folio ?></td>
                                      <td style="width: 120px;"><?php echo $remision->nombre_fiscal ?>
                                        <input type="hidden" name="remision_id_factura[]" value="<?php echo $remision->id_factura ?>">
                                      </td>
                                      <td style="width: 70px;"><?php echo $remision->codigo ?></td>
                                      <td style="width: 50px;"><?php echo $remision->cantidad ?></td>
                                      <td style="width: 70px;"><?php echo $remision->precio_unitario ?></td>
                                      <td style="width: 100px;"><?php echo $remision->importe ?></td>
                                    </tr>
                            <?php } ?>

                            <tr class='row-total'>
                              <td colspan="3"></td>
                              <td><input type="text" name="bultos_ingresos" value="<?php echo MyString::float(MyString::formatoNumero($bultosIngresos, 2, '')) ?>" class="span12" id="total-ingresos" maxlength="500" readonly style="text-align: right;"></td>
                              <td><input type="text" name="pu_ingresos" value="<?php echo MyString::float(MyString::formatoNumero($totalIngresos/($bultosIngresos>0?$bultosIngresos:1), 2, '')) ?>" class="span12" id="total-ingresos" maxlength="500" readonly style="text-align: right;"></td>
                              <td style="width: 100px;"><input type="text" name="total_ingresos" value="<?php echo MyString::float(MyString::formatoNumero($totalIngresos, 2, '')) ?>" class="span12" id="total_ingresos_mercan" maxlength="500" readonly style="text-align: right;"></td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Ingresos del dia -->

                    <!-- Prestamos de bodegas -->
                    <div class="row-fluid" style="margin-top: 5px;">
                      <div class="span12">
                        <div class="row-fluid">
                          <div class="span12">
                            <div class="row-fluid">
                              <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">PRESTAMOS <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;float: right;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                              <div class="row-fluid">
                                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-prestamos">
                                    <thead>
                                      <tr>
                                        <th colspan="6">PRESTAMOS Y DEVOLUCIONES <button type="button" class="btn btn-success" id="btn-add-prestamos" style="padding: 2px 7px 2px;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></th>
                                        <th colspan="3">IMPORTE</th>
                                      </tr>
                                      <tr>
                                        <th>EMPRESA</th>
                                        <th>CONCEPTO</th>
                                        <th>CLASIF.</th>
                                        <th>UNIDAD</th>
                                        <th>BULTOS</th>
                                        <th>PRECIO</th>
                                        <th>IMPORTE</th>
                                        <th>TIPO</th>
                                        <th></th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                        $totalPrestamos = $totalPrestamosRestas = $totalPrestamosBultos = 0;
                                        if (isset($_POST['prestamo_descripcion'])) {
                                          foreach ($_POST['prestamo_descripcion'] as $key => $concepto) {
                                            if ($_POST['prestamo_tipo'][$k] == 'dev' || $_POST['prestamo_tipo'][$k] == 'true') {
                                              $totalPrestamosRestas += floatval($_POST['prestamo_importe'][$key]);
                                            }
                                            $totalPrestamos       += floatval($_POST['prestamo_importe'][$key]);
                                            $totalPrestamosBultos += floatval($_POST['prestamo_cantidad'][$key]);
                                          ?>
                                              <tr>
                                                <td style="width: 100px;">
                                                  <input type="text" name="prestamo_empresa[]" value="<?php echo $_POST['prestamo_empresa'][$key] ?>" class="input-small gasto-cargo" style="width: 150px;" <?php echo $readonly ?>>
                                                  <input type="hidden" name="prestamo_empresa_id[]" value="<?php echo $_POST['prestamo_empresa_id'][$key] ?>" class="input-small vpositive gasto-cargo-id">
                                                </td>
                                                <td style="width: 120px;">
                                                  <input type="text" name="prestamo_concepto[]" value="<?php echo $_POST['prestamo_concepto'][$key] ?>" class="span12" <?php echo $readonly ?>>
                                                </td>
                                                <td style="width: 120px;">
                                                  <input type="text" name="prestamo_descripcion[]" value="<?php echo $_POST['prestamo_descripcion'][$key] ?>" id="prestamo_descripcion" class="span12" <?php echo $readonly ?>>
                                                  <input type="hidden" name="prestamo_id_prod[]" value="<?php echo $_POST['prestamo_id_prod'][$key] ?>" id="prestamo_id_prod" class="span12">
                                                </td>
                                                <td style="width: 70px;">
                                                  <select name="prestamo_umedida[]" id="prestamo_umedida" class="span12">
                                                    <?php foreach ($unidades as $key => $u) { ?>
                                                      <option value="<?php echo $u->id_unidad ?>" <?php echo $_POST['prestamo_umedida'][$k] == $u->id_unidad ? 'selected' : '' ?>><?php echo $u->nombre ?></option>
                                                    <?php } ?>
                                                  </select>
                                                </td>
                                                <td style="width: 50px;">
                                                  <input type="text" name="prestamo_cantidad[]" value="<?php echo $_POST['prestamo_cantidad'][$key] ?>" class="span12 vpositive prestamo_cantidad" <?php echo $readonly ?>>
                                                </td>
                                                <td style="width: 50px;">
                                                  <input type="text" name="prestamo_precio[]" value="<?php echo $_POST['prestamo_precio'][$key] ?>" class="span12 vpositive prestamo_precio" <?php echo $readonly ?>>
                                                </td>
                                                <td style="width: 50px;">
                                                  <input type="text" name="prestamo_importe[]" value="<?php echo $_POST['prestamo_importe'][$key] ?>" class="span12 vpositive prestamo_importe" readonly>
                                                </td>
                                                <td style="width: 50px;">
                                                  <select name="prestamo_tipo[]" id="prestamo_tipo" class="span12">
                                                    <option value="dev" <?php echo $_POST['prestamo_tipo'][$k] == 'dev' ? 'selected' : '' ?>>Devolucion (-)</option>
                                                    <option value="true" <?php echo $_POST['prestamo_tipo'][$k] == 'true' ? 'selected' : '' ?>>Prestamo (-)</option>
                                                    <option value="false" <?php echo $_POST['prestamo_tipo'][$k] == 'false' ? 'selected' : '' ?>>Pago (+)</option>
                                                  </select>
                                                </td>
                                                <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-prestamo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                              </tr>
                                      <?php }} else {
                                        foreach ($caja['prestamos'] as $prestamo) {
                                          if ($prestamo->tipo == 'dev' || $prestamo->tipo == 'true') {
                                            $totalPrestamosRestas += floatval($prestamo->importe);
                                          }
                                          $totalPrestamos += floatval($prestamo->importe);
                                          $totalPrestamosBultos += floatval($prestamo->cantidad);
                                        ?>
                                          <tr>
                                            <td style="width: 100px;">
                                              <input type="text" name="prestamo_empresa[]" value="<?php echo $prestamo->empresa ?>" class="input-small gasto-cargo" style="width: 150px;" <?php echo $readonly ?>>
                                              <input type="hidden" name="prestamo_empresa_id[]" value="<?php echo $prestamo->id_categoria ?>" class="input-small vpositive gasto-cargo-id">
                                            </td>
                                            <td style="width: 120px;">
                                              <input type="text" name="prestamo_concepto[]" value="<?php echo $prestamo->concepto ?>" class="span12" <?php echo $readonly ?>>
                                            </td>
                                            <td style="width: 120px;">
                                              <input type="text" name="prestamo_descripcion[]" value="<?php echo $prestamo->descripcion ?>" id="prestamo_descripcion" class="span12" <?php echo $readonly ?>>
                                              <input type="hidden" name="prestamo_id_prod[]" value="<?php echo $prestamo->id_clasificacion ?>" id="prestamo_id_prod" class="span12">
                                            </td>
                                            <td style="width: 70px;">
                                              <select name="prestamo_umedida[]" id="prestamo_umedida" class="span12">
                                                <?php foreach ($unidades as $key => $u) { ?>
                                                  <option value="<?php echo $u->id_unidad ?>" <?php echo $prestamo->id_unidad == $u->id_unidad ? 'selected' : '' ?>><?php echo $u->nombre ?></option>
                                                <?php } ?>
                                              </select>
                                            </td>
                                            <td style="width: 50px;">
                                              <input type="text" name="prestamo_cantidad[]" value="<?php echo $prestamo->cantidad ?>" class="span12 vpositive prestamo_cantidad" <?php echo $readonly ?>>
                                            </td>
                                            <td style="width: 50px;">
                                              <input type="text" name="prestamo_precio[]" value="<?php echo $prestamo->precio_unitario ?>" class="span12 vpositive prestamo_precio" <?php echo $readonly ?>>
                                            </td>
                                            <td style="width: 50px;">
                                              <input type="text" name="prestamo_importe[]" value="<?php echo $prestamo->importe ?>" class="span12 vpositive prestamo_importe" readonly>
                                            </td>
                                            <td style="width: 50px;">
                                              <select name="prestamo_tipo[]" id="prestamo_tipo" class="span12">
                                                <option value="dev" <?php echo $prestamo->tipo == 'dev' ? 'selected' : '' ?>>Devolucion (-)</option>
                                                <option value="true" <?php echo $prestamo->tipo == 'true' ? 'selected' : '' ?>>Prestamo (-)</option>
                                                <option value="false" <?php echo $prestamo->tipo == 'false' ? 'selected' : '' ?>>Pago (+)</option>
                                              </select>
                                            </td>
                                            <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-prestamo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                          </tr>
                                      <?php }} ?>
                                      <tr class="row-total">
                                        <td colspan="4" style="text-align: right; font-weight: bolder;">TOTAL</td>
                                        <td colspan="1"><input type="text" value="<?php echo MyString::float(MyString::formatoNumero($totalPrestamosBultos, 2, '')) ?>" class="input-small vpositive" id="ttotal-prestamos-bultos" style="text-align: right;" readonly></td>
                                        <td colspan="1"><input type="text" value="<?php echo MyString::float(MyString::formatoNumero(($totalPrestamos/($totalPrestamosBultos>0?$totalPrestamosBultos:1)) , 2, '')) ?>" class="input-small vpositive" id="ttotal-prestamos-precio" style="text-align: right;" readonly></td>
                                        <td colspan="3"><input type="text" value="<?php echo MyString::float(MyString::formatoNumero($totalPrestamos, 2, '')) ?>" class="input-small vpositive" id="ttotal-prestamos" style="text-align: right;" readonly>
                                          <input type="text" value="<?php echo MyString::float(MyString::formatoNumero($totalPrestamosRestas, 2, '')) ?>" class="input-small vpositive" id="ttotal-prestamos-restas" style="text-align: right;" readonly>
                                        </td>
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
                    <!-- /Prestamos de bodegas -->

                    <!-- Ventas del dia -->
                    <div class="row-fluid" style="margin-top: 5px;">
                      <div class="span12">
                        <div class="row-fluid">

                          <div class="span12">
                            <div class="row-fluid">
                              <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">REPORTE CAJA "COMPRAS LIMON"</div> -->
                              <div class="row-fluid">
                                <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">INGRESOS <button type="button" class="btn btn-success" id="btn-add-ingreso" style="padding: 2px 7px 2px;"><i class="icon-plus"></i></button></div> -->
                                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-boletas">
                                    <thead>
                                      <tr>
                                        <th colspan="5">VENTAS DEL DIA</th>
                                        <th colspan="2">IMPORTE</th>
                                        <th colspan="3"><?php echo $_GET['ffecha'] ?>
                                          <?php echo $this->usuarios_model->getLinkPrivSm('cuentas_cobrar/agregar_abono/', array(
                                              'params'   => "",
                                              'btn_type' => 'btn-success pull-right btn_abonos_masivo',
                                              'attrs' => array('style' => 'display:none;', 'rel' => 'superbox-50x500') )
                                            ); ?>
                                        </th>
                                      </tr>
                                      <tr>
                                        <th>EMPRESA</th>
                                        <th>REM No.</th>
                                        <th>CLIENTE</th>
                                        <th>CLASIF.</th>
                                        <th>BULTOS</th>
                                        <th>PRECIO</th>
                                        <th>IMPORTE</th>
                                        <th>ABONOS HOY</th>
                                        <th>T. ABONOS</th>
                                        <th>SALDO</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php $bultosVentas = $totalVentas = $abonoshVentas = $abonosVentas = $saldoVentas = 0;
                                            foreach ($caja['ventas'] as $venta) {
                                              $totalVentas += floatval($venta->importe);
                                              $bultosVentas += floatval($venta->cantidad);
                                              $abonoshVentas += floatval($venta->abonos_hoy);
                                              $abonosVentas += floatval($venta->abonos);
                                              $saldoVentas += floatval($venta->saldo);
                                            ?>
                                              <tr>
                                                <td style="width: 50px;"><?php echo $venta->categoria ?></td>
                                                <td style="width: 50px;"><?php echo $venta->serie.$venta->folio ?></td>
                                                <td style="width: 120px;"><?php echo $venta->cliente ?>
                                                  <input type="hidden" name="venta_id_factura[]" value="<?php echo $venta->id_factura ?>">
                                                </td>
                                                <td style="width: 70px;"><?php echo $venta->codigo ?></td>
                                                <td style="width: 50px;text-align: right;"><?php echo $venta->cantidad ?></td>
                                                <td style="width: 70px;text-align: right;"><?php echo $venta->precio_unitario ?></td>
                                                <td style="width: 100px;text-align: right;"><?php echo $venta->importe ?></td>
                                                <td style="width: 100px;text-align: right;"><?php echo $venta->abonos_hoy ?></td>
                                                <td style="width: 100px;text-align: right;"><?php echo $venta->abonos ?></td>
                                                <td style="width: 100px;text-align: right;" class="<?php echo $venta->cliente!=''?'sel_abonom':''; ?>"
                                                  data-id="<?php echo $venta->id_factura; ?>" data-tipo="f"><?php echo $venta->saldo ?></td>
                                              </tr>
                                      <?php } ?>
                                    </tbody>
                                    <tbody>
                                      <tr>
                                        <td colspan="4"><input type="hidden" value="<?php echo $totalVentas ?>" id="total-boletas"></td>
                                        <td><?php echo MyString::formatoNumero($bultosVentas, 2, '') ?></td>
                                        <td><?php echo MyString::formatoNumero($totalVentas/($bultosVentas>0?$bultosVentas:1), 2, '') ?></td>
                                        <td style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($totalVentas, 2, '$') ?></td>
                                        <td style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($abonoshVentas, 2, '$') ?>
                                          <input type="hidden" name="abonoshVentas" id="abonoshVentas" value="<?php echo $abonoshVentas ?>">
                                        </td>
                                        <td style="text-align: right;"><?php echo MyString::formatoNumero($abonosVentas, 2, '$') ?></td>
                                        <td style="text-align: right;"><?php echo MyString::formatoNumero($saldoVentas, 2, '$') ?></td>
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
                    <!-- /Ventas del dia -->

                    <!-- Existencia del dia -->
                    <div class="row-fluid" style="margin-top: 5px;">
                      <div class="span12">
                        <div class="row-fluid">

                          <div class="span12">
                            <div class="row-fluid">
                              <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">REPORTE CAJA "COMPRAS LIMON"</div> -->
                              <div class="row-fluid">
                                <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">INGRESOS <button type="button" class="btn btn-success" id="btn-add-ingreso" style="padding: 2px 7px 2px;"><i class="icon-plus"></i></button></div> -->
                                <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-boletas">
                                    <thead>
                                      <tr>
                                        <th colspan="4">EXISTENCIA DEL DIA</th>
                                        <th colspan="2">IMPORTE</th>
                                      </tr>
                                      <tr>
                                        <th>REM No.</th>
                                        <th>PROVEEDOR</th>
                                        <th>CLASIF.</th>
                                        <th>BULTOS</th>
                                        <th>PRECIO</th>
                                        <th>IMPORTE</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php $bultosExisD = $totalExisD = 0; $aux = 0;
                                            foreach ($caja['existencia_dia'] as $exis_dia) {
                                              if ($aux == $exis_dia->id_factura) {
                                                $exis_dia->nombre_fiscal = '';
                                                $exis_dia->serie = '';
                                                $exis_dia->folio = '';
                                              } else
                                                $aux = $exis_dia->id_factura;
                                              $totalExisD += floatval($exis_dia->importe);
                                              $bultosExisD += floatval($exis_dia->cantidad);
                                            ?>
                                              <tr>
                                                <td style="width: 50px;"><?php echo $exis_dia->serie.$exis_dia->folio ?></td>
                                                <td style="width: 120px;"><?php echo $exis_dia->nombre_fiscal ?>
                                                  <input type="hidden" name="exisd_id_factura[]" value="<?php echo $exis_dia->id_factura ?>">
                                                  <input type="hidden" name="exisd_id_unidad[]" value="<?php echo $exis_dia->id_unidad ?>">
                                                  <input type="hidden" name="exisd_descripcion[]" value="<?php echo $exis_dia->descripcion ?>">
                                                  <input type="hidden" name="exisd_cantidad[]" value="<?php echo $exis_dia->cantidad ?>">
                                                  <input type="hidden" name="exisd_precio_unitario[]" value="<?php echo $exis_dia->precio_unitario ?>">
                                                  <input type="hidden" name="exisd_importe[]" value="<?php echo $exis_dia->importe ?>">
                                                  <input type="hidden" name="exisd_id_clasificacion[]" value="<?php echo $exis_dia->id_clasificacion ?>">
                                                </td>
                                                <td style="width: 70px;"><?php echo $exis_dia->codigo ?></td>
                                                <td style="width: 50px;"><?php echo $exis_dia->cantidad ?></td>
                                                <td style="width: 70px;"><?php echo $exis_dia->precio_unitario ?></td>
                                                <td style="width: 100px;"><?php echo $exis_dia->importe ?></td>
                                              </tr>
                                      <?php } ?>
                                    </tbody>
                                    <tbody>
                                      <tr>
                                        <td colspan="3"><input type="hidden" value="<?php echo $totalExisD ?>" id="total-boletas_exis"></td>
                                        <td style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($bultosExisD, 2, '') ?></td>
                                        <td style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($totalExisD/($bultosExisD>0?$bultosExisD:1), 2, '') ?></td>
                                        <td style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($totalExisD, 2, '$') ?></td>
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
                    <!-- /Existencia del dia -->

                </div>
              </div>
            </div>

            <div class="span6">

              <!-- Gastos -->
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
                                  <th colspan="6">GASTOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></th>
                                  <th colspan="2">IMPORTE</th>
                                </tr>
                                <tr>
                                  <th>COD AREA</th>
                                  <th>EMPRESA</th>
                                  <th>NOM</th>
                                  <th>FOLIO</th>
                                  <th>NOMBRE</th>
                                  <th>CONCEPTO</th>
                                  <th>CARGO</th>
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
                                          <td style="width: 60px;">
                                            <input type="hidden" name="gasto_id_gasto[]" value="" id="gasto_id_gasto">
                                            <input type="hidden" name="gasto_del[]" value="" id="gasto_del">
                                            <input type="text" name="codigoArea[]" value="<?php echo $_POST['codigoArea'][$key] ?>" id="codigoArea" class="span12 showCodigoAreaAuto" required>
                                            <input type="hidden" name="codigoAreaId[]" value="<?php echo $_POST['codigoAreaId'][$key] ?>" id="codigoAreaId" class="span12" required>
                                            <input type="hidden" name="codigoCampo[]" value="<?php echo $_POST['codigoCampo'][$key] ?>" id="codigoCampo" class="span12">
                                            <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                                            <input type="hidden" name="area[]" value="<?php echo $_POST['area'][$key] ?>" class="area span12">
                                            <input type="hidden" name="areaId[]" value="<?php echo $_POST['areaId'][$key] ?>" class="areaId span12">
                                            <input type="hidden" name="rancho[]" value="<?php echo $_POST['rancho'][$key] ?>" class="rancho span12">
                                            <input type="hidden" name="ranchoId[]" value="<?php echo $_POST['ranchoId'][$key] ?>" class="ranchoId span12">
                                            <input type="hidden" name="centroCosto[]" value="<?php echo $_POST['centroCosto'][$key] ?>" class="centroCosto span12">
                                            <input type="hidden" name="centroCostoId[]" value="<?php echo $_POST['centroCostoId'][$key] ?>" class="centroCostoId span12">
                                            <input type="hidden" name="activos[]" value="<?php echo $_POST['activos'][$key] ?>" class="activos span12">
                                            <input type="hidden" name="activoId[]" value="<?php echo $_POST['activoId'][$key] ?>" class="activoId span12">
                                            <input type="hidden" name="empresaId[]" value="<?php echo $_POST['empresaId'][$key] ?>" class="empresaId span12">
                                          </td>
                                          <td style="width: 100px;">
                                            <input type="text" name="gasto_empresa[]" value="<?php echo $_POST['gasto_empresa'][$key] ?>" class="span12 gasto-cargo" required <?php echo $readonly ?>>
                                            <input type="hidden" name="gasto_empresa_id[]" value="<?php echo $_POST['gasto_empresa_id'][$key] ?>" class="input-small vpositive gasto-cargo-id">
                                          </td>
                                          <td style="width: 40px;">
                                            <select name="gasto_nomenclatura[]" class="span12 ingreso_nomenclatura" <?php echo $readonly ?>>
                                              <?php foreach ($nomenclaturas as $n) { ?>
                                                <option value="<?php echo $n->id ?>" <?php echo $_POST['gasto_nomenclatura'][$key] == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                              <?php } ?>
                                            </select>
                                          </td>
                                          <td style="width: 40px;"><input type="text" name="gasto_folio[]" value="<?php echo $_POST['gasto_folio'][$key] ?>" class="span12 gasto-folio" <?php echo $readonly ?>></td>
                                          <td style="">
                                            <input type="text" name="gasto_nombre[]" value="<?php echo $_POST['gasto_nombre'][$key] ?>" class="span12 gasto-nombre" <?php echo $readonly ?>>
                                          </td>
                                          <td style="">
                                            <input type="text" name="gasto_concepto[]" value="<?php echo $_POST['gasto_concepto'][$key] ?>" class="span12 gasto-concepto"  <?php echo $readonly ?>>
                                          </td>
                                          <td style="width: 60px;"><input type="text" name="gasto_importe[]" value="<?php echo $_POST['gasto_importe'][$key] ?>" class="span12 vpositive gasto-importe" <?php echo $readonly ?>></td>
                                          <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                        </tr>
                                <?php }} else {
                                  foreach ($caja['gastos'] as $gasto) {
                                    $totalGastos += floatval($gasto->monto);
                                  ?>
                                  <tr>
                                    <td style="width: 60px;">
                                      <input type="hidden" name="gasto_id_gasto[]" value="<?php echo $gasto->id_gasto ?>" id="gasto_id_gasto">
                                      <input type="hidden" name="gasto_del[]" value="" id="gasto_del">
                                      <input type="text" name="codigoArea[]" value="<?php echo $gasto->nombre_codigo ?>" id="codigoArea" class="span12 showCodigoAreaAuto" required>
                                      <input type="hidden" name="codigoAreaId[]" value="<?php echo $gasto->id_area ?>" id="codigoAreaId" class="span12" required>
                                      <input type="hidden" name="codigoCampo[]" value="<?php echo $gasto->campo ?>" id="codigoCampo" class="span12">
                                      <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                                      <input type="hidden" name="area[]" value="<?php echo $gasto->area ?>" class="area span12">
                                      <input type="hidden" name="areaId[]" value="<?php echo $gasto->id_areac ?>" class="areaId span12">
                                      <input type="hidden" name="rancho[]" value="<?php echo $gasto->rancho ?>" class="rancho span12">
                                      <input type="hidden" name="ranchoId[]" value="<?php echo $gasto->id_rancho ?>" class="ranchoId span12">
                                      <input type="hidden" name="centroCosto[]" value="<?php echo $gasto->centro_costo ?>" class="centroCosto span12">
                                      <input type="hidden" name="centroCostoId[]" value="<?php echo $gasto->id_centro_costo ?>" class="centroCostoId span12">
                                      <input type="hidden" name="activos[]" value="<?php echo $gasto->activo ?>" class="activos span12">
                                      <input type="hidden" name="activoId[]" value="<?php echo $gasto->id_activo ?>" class="activoId span12">
                                      <input type="hidden" name="empresaId[]" value="<?php echo $gasto->id_empresa ?>" class="empresaId span12">
                                      <a href="<?php echo base_url('panel/bodega_guadalajara/print_vale/?id='.$gasto->id_gasto)?>" target="_blank" title="Imprimir VALE">
                                        <i class="ico icon-print" style="cursor:pointer"></i></a>
                                    </td>
                                    <td style="width: 100px;">
                                      <input type="text" name="gasto_empresa[]" value="<?php echo $gasto->empresa ?>" class="span12 gasto-cargo" required <?php echo $readonly ?>>
                                      <input type="hidden" name="gasto_empresa_id[]" value="<?php echo $gasto->id_categoria ?>" class="input-small vpositive gasto-cargo-id">
                                    </td>
                                    <td style="width: 40px;">
                                      <select name="gasto_nomenclatura[]" class="span12 ingreso_nomenclatura" <?php echo $readonly ?>>
                                        <?php foreach ($nomenclaturas as $n) { ?>
                                          <option value="<?php echo $n->id ?>" <?php echo $gasto->id_nomenclatura == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                        <?php } ?>
                                      </select>
                                    </td>
                                    <td style="width: 40px;"><input type="text" name="gasto_folio[]" value="<?php echo $gasto->folio ?>" class="span12 gasto-folio" <?php echo $readonly ?>></td>
                                    <td style="">
                                      <input type="text" name="gasto_nombre[]" value="<?php echo $gasto->nombre_gasto ?>" class="span12 gasto-nombre" <?php echo $readonly ?>>
                                    </td>
                                    <td style="">
                                      <input type="text" name="gasto_concepto[]" value="<?php echo $gasto->concepto ?>" class="span12 gasto-concepto" <?php echo $readonly ?>>
                                    </td>
                                    <td style="width: 60px;"><input type="text" name="gasto_importe[]" value="<?php echo $gasto->monto ?>" class="span12 vpositive gasto-importe" <?php echo $readonly ?>></td>
                                    <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                  </tr>
                                <?php }} ?>
                                <tr class="row-total">
                                  <td colspan="5" style="text-align: right; font-weight: bolder;">TOTAL</td>
                                  <td><input type="text" value="<?php echo $totalGastos ?>" class="input-small vpositive" id="ttotal-gastos" style="text-align: right;" readonly></td>
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
              <!-- /Gastos -->

              <!-- Traspasos -->
              <div class="row-fluid" style="margin-top: 5px;">
                <div class="span12">
                  <div class="row-fluid">
                    <div class="span12">
                      <div class="row-fluid">
                        <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">GASTOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;float: right;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                        <div class="row-fluid">
                          <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                            <table class="table table-striped table-bordered table-hover table-condensed" id="table-traspasos">
                              <thead>
                                <tr>
                                  <th colspan="2">TRASPASOS <button type="button" class="btn btn-success" id="btn-add-traspaso" style="padding: 2px 7px 2px;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></th>
                                  <th colspan="2">IMPORTE</th>
                                </tr>
                                <tr>
                                  <th>TIPO</th>
                                  <th>CONCEPTO</th>
                                  <th>CARGO</th>
                                  <th></th>
                                </tr>
                              </thead>
                              <tbody>
                                <?php
                                  $totalTraspasos = 0;
                                  if (isset($_POST['traspaso_concepto'])) {
                                    foreach ($_POST['traspaso_concepto'] as $key => $concepto) {
                                      $totalTraspasos += floatval($_POST['traspaso_importe'][$key]); ?>
                                    <tr>
                                      <td>
                                        <select name="traspaso_tipo[]" class="span12 ingreso_nomenclatura" <?php echo $readonly ?>>
                                          <option value="t" <?php echo $_POST['traspaso_tipo'][$key] == 't' ? 'selected' : '' ?>>Ingreso</option>
                                          <option value="f" <?php echo $_POST['traspaso_tipo'][$key] == 'f' ? 'selected' : '' ?>>Egreso</option>
                                        </select>
                                        <input type="hidden" name="traspaso_id_traspaso[]" value="" id="traspaso_id_traspaso">
                                        <input type="hidden" name="traspaso_del[]" value="" id="traspaso_del">
                                      </td>
                                      <td style="">
                                        <input type="text" name="traspaso_concepto[]" value="<?php echo $_POST['traspaso_concepto'][$key] ?>" class="span12 traspaso-concepto" <?php echo $readonly ?>>
                                      </td>
                                      <td style="width: 60px;"><input type="text" name="traspaso_importe[]" value="<?php echo $_POST['traspaso_importe'][$key] ?>" class="span12 vpositive traspaso-importe" <?php echo $readonly ?>></td>
                                      <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-traspaso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                    </tr>
                                <?php }} else {
                                  if (isset($caja['traspasos']))
                                  foreach ($caja['traspasos'] as $traspaso) {
                                    $totalTraspasos += floatval($traspaso->monto);
                                  ?>
                                  <tr>
                                    <td>
                                      <select name="traspaso_tipo[]" class="span12 ingreso_nomenclatura" <?php echo $readonly ?>>
                                        <option value="t" <?php echo $traspaso->tipo == 't' ? 'selected' : '' ?>>Ingreso</option>
                                        <option value="f" <?php echo $traspaso->tipo == 'f' ? 'selected' : '' ?>>Egreso</option>
                                      </select>
                                      <input type="hidden" name="traspaso_id_traspaso[]" value="<?php echo $traspaso->id_traspaso ?>" id="traspaso_id_traspaso">
                                      <input type="hidden" name="traspaso_del[]" value="" id="traspaso_del">
                                    </td>
                                    <td style="">
                                      <input type="text" name="traspaso_concepto[]" value="<?php echo $traspaso->concepto ?>" class="span12 traspaso-concepto" <?php echo $readonly ?>>
                                    </td>
                                    <td style="width: 60px;"><input type="text" name="traspaso_importe[]" value="<?php echo $traspaso->monto ?>" class="span12 vpositive traspaso-importe" <?php echo $readonly ?>></td>
                                    <td style="width: 30px;"><button type="button" class="btn btn-danger btn-del-traspaso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button></td>
                                  </tr>
                                <?php }} ?>
                                <tr class="row-total">
                                  <td colspan="2" style="text-align: right; font-weight: bolder;">TOTAL</td>
                                  <td><input type="text" value="<?php echo $totalTraspasos ?>" class="input-small vpositive" id="ttotal-traspasos" style="text-align: right;" readonly></td>
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
              <!-- /Traspasos -->

              <!-- Deudores -->
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
                                  <th colspan="8">DEUDORES
                                      <button type="button" class="btn btn-success" id="btn-add-deudor" style="padding: 2px 7px 2px;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button>
                                  </th>
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
                                  $modificar_gasto = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/modificar_gastos/');
                                  $mod_gas_readonly = !$modificar_gasto && $readonly == ''? ' readonly': '';
                                  $totalDeudores = 0;
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
                                              <option value="prestamo" <?php echo $_POST['deudor_tipo'][$key]=='prestamo'? 'selected': ''; ?>>Prestamos</option>
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
                                    <td style="width: 200px;">
                                      <input type="text" name="deudor_nombre[]" value="<?php echo $deudor->nombre ?>" class="span12 deudor_nombre" required autocomplete="off" <?php echo $deudor->mismo_dia.$readonly.$mod_gas_readonly ?>>
                                      <input type="hidden" name="deudor_id_deudor[]" value="<?php echo $deudor->id_deudor ?>" id="deudor_id_gasto">
                                      <input type="hidden" name="deudor_del[]" value="" id="deudor_del">
                                      <a href="<?php echo base_url('panel/bodega_guadalajara/print_vale_deudor/?id='.$deudor->id_deudor.'&noCaja='.$deudor->no_caja)?>" target="_blank" title="Imprimir vale prestamo">
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
                                      <?php if ((!isset($caja['status']) || $caja['status'] === 't') && $readonly == ''): ?>
                                      <a class="btn_abonos_deudores" href="<?php echo base_url('panel/bodega_guadalajara/agregar_abono_deudor/')."?id={$deudor->id_deudor}&fecha={$fecha}&no_caja={$_GET['fno_caja']}&monto={$deudor->saldo}" ?>" style="" rel="superbox-50x500" title="Abonar">
                                        <?php echo $deudor->saldo ?></a>
                                      <?php else: ?>
                                        <?php echo $deudor->saldo ?>
                                      <?php endif ?>
                                    </td>
                                    <td style="width: 30px;">
                                      <?php if ($modificar_gasto && $deudor->mismo_dia == '' && $readonly == 'readonly'): ?>
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

              <!-- Tabulacion -->
              <div class="row-fluid">
                <div class="span12">
                  <div class="span12" style="font-weight: bold; min-height: 25px;">
                    <?php $total_saldo_corte = $totalCont+$abonoshVentas+$totalIngresosExt-$totalGastos; ?>
                    SALDO AL CORTE: <span id="ttotal-corte1"><?php echo MyString::formatoNumero($total_saldo_corte, 2, '$') ?></span>
                    <input type="hidden" name="ttotal-corte" value="<?php echo $total_saldo_corte ?>" id="ttotal-corte">
                  </div>
                </div>
              </div>
              <!--/Tabulacion -->

              <!-- Tabulacion -->
              <div class="row-fluid">
                <div class="span12">
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
                          <tr>
                            <td colspan="2">TOTAL DIFERENCIA
                            <input type="hidden" name="total_diferencia" value="<?php echo $total_saldo_corte-$totalEfectivo ?>" id="ttotal-diferencia"></td>
                            <td id="total-efectivo-diferencia" style="text-align: right; font-weight: bold;"><?php echo MyString::formatoNumero($total_saldo_corte-$totalEfectivo, 2, '$') ?></td>
                          </tr>

                          <input type="hidden" name="costo_venta" value="<?php echo ($caja['costo_venta']!=0? $caja['costo_venta']: ($totalExisAnt+$totalIngresos-$totalPrestamosRestas-$totalExisD)) ?>" id="costo_venta" class="input-small" <?php echo $readonly ?>>
                          <input type="hidden" name="utilidad" value="<?php echo ($caja['utilidad']!=0? $caja['utilidad']: ($totalIngresosExt+$totalVentas-$totalGastos-($totalExisAnt+$totalIngresos-$totalPrestamosRestas-$totalExisD))) ?>" id="utilidad" class="input-small" <?php echo $readonly ?>>
                          <input type="hidden" name="a_gastos" value="<?php echo $caja['a_gastos'] ?>" id="a_gastos" class="input-small" <?php echo $readonly ?>>
                          <input type="hidden" name="a_bultos_vendidos" value="<?php echo $caja['a_bultos_vendidos'] ?>" id="a_bultos_vendidos" class="input-small" <?php echo $readonly ?>>
                          <input type="hidden" name="a_utilidad" value="<?php echo $caja['a_utilidad']+($caja['utilidad']!=0? 0: ($totalIngresosExt+$totalVentas-$totalGastos-($totalExisAnt+$totalIngresos-$totalPrestamosRestas-$totalExisD)) ) ?>" id="a_utilidad" class="input-small" <?php echo $readonly ?>>

                          <input type="hidden" name="totalesGrl[totalVentas]" id="grltotalVentas" value="<?php echo $totalVentas ?>">
                          <input type="hidden" name="totalesGrl[totalExisAnt]" id="grltotalExisAnt" value="<?php echo $totalExisAnt ?>">
                          <input type="hidden" name="totalesGrl[totalIngresos]" id="grltotalIngresos" value="<?php echo $totalIngresos ?>">
                          <input type="hidden" name="totalesGrl[totalExisD]" id="grltotalExisD" value="<?php echo $totalExisD ?>">
                          <input type="hidden" name="totalesGrl[totalPrestamos]" id="grltotalPrestamos" value="<?php echo $totalPrestamos ?>">
                          <input type="hidden" name="totalesGrl[costoVenta]" id="grlcostoVenta" value="<?php echo ($totalExisAnt+$totalIngresos-$totalExisD-$totalPrestamos) ?>">
                          <input type="hidden" name="totalesGrl[totalGastos]" id="grltotalGastos" value="<?php echo $totalGastos ?>">
                          <input type="hidden" name="totalesGrl[utilidad]" id="grlutilidad" value="<?php echo ($totalVentas-($totalExisAnt+$totalIngresos-$totalExisD-$totalPrestamos)-$totalGastos) ?>">
                          <input type="hidden" name="totalesGrl[bultosVentas]" id="grlbultosVentas" value="<?php echo $bultosVentas ?>">
                          <input type="hidden" name="totalesGrl[utilidadBulto]" id="grlutilidadBulto" value="<?php echo ( ($totalVentas-($totalExisAnt+$totalIngresos-$totalExisD-$totalPrestamos)-$totalGastos)/($bultosVentas>0? $bultosVentas: 1) ) ?>">
                          <input type="hidden" name="totalesGrl[clientes]" id="grlclientes" value="<?php echo ($totalSal+$saldoVentas) ?>">
                        </tbody>
                      </table>
                    </div>

                  </div>
                </div>
              </div>
              <!--/Tabulacion -->

              <!-- Rastreo de efectivo -->
              <div class="row-fluid">
                <div class="span12" style="margin-top: 1px;">
                  <table class="table table-striped table-bordered table-hover table-condensed" id="table-rastreo-efetivo">
                    <thead>
                      <tr>
                        <th colspan="4">RASTREO DE EFECTIVO
                          <button type="button" class="btn btn-success" id="btn-add-rastreo-efetivo" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button>
                        </th>
                        <th colspan="2">IMPORTE</th>
                      </tr>
                      <tr>
                        <th>FOLIO</th>
                        <th>FECHA</th>
                        <th>NOMBRE</th>
                        <th>NOTA</th>
                        <th>IMPORTE</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        if (isset($_POST['rastreo_id_rastreo'])) {
                          foreach ($_POST['rastreo_id_rastreo'] as $key => $concepto) {
                      ?>
                            <tr>
                              <td style="width: 100px;">
                                <input type="hidden" name="rastreo_id_rastreo[]" value="<?php echo $_POST['rastreo_id_rastreo'] ?>" id="rastreo_id_rastreo">
                                <input type="hidden" name="rastreo_del[]" value="" id="rastreo_del">
                              </td>
                              <td style="width: 100px;"><input type="date" name="rastreo_fecha[]" value="<?php echo $_POST['rastreo_fecha'] ?>" class="rastreo_fecha span12" maxlength="100" placeholder="fecha"></td>
                              <td style="width: 300px;"><input type="text" name="rastreo_nombre[]" value="<?php echo $_POST['rastreo_nombre'] ?>" class="rastreo_nombre span12" maxlength="100" placeholder="Nombre"></td>
                              <td style="width: 300px;"><input type="text" name="rastreo_nota[]" value="<?php echo $_POST['rastreo_nota'] ?>" class="rastreo_nota span12" maxlength="100" placeholder="Nota"></td>
                              <td style="width: 100px;"><input type="text" name="rastreo_monto[]" value="<?php echo $_POST['rastreo_monto'] ?>" class="rastreo_monto vpositive input-small" placeholder="Monto" required></td>
                              <td style="width: 30px;">
                                <button type="button" class="btn btn-success btn-save-rastreo" style="padding: 2px 7px 2px;"><i class="icon-plus"></i></button>
                                <button type="button" class="btn btn-danger btn-del-rastreo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                              </td>
                            </tr>
                      <?php }} else {
                            foreach ($caja['rastreo_efectivo'] as $rastreo) {
                              ?>
                              <tr>
                                <td style="width: 100px;">
                                  <input type="hidden" name="rastreo_id_rastreo[]" value="<?php echo $rastreo->id_rastreo ?>" id="rastreo_id_rastreo">
                                  <input type="hidden" name="rastreo_del[]" value="" id="rastreo_del">
                                  <a href="<?php echo base_url('panel/bodega_guadalajara/print_vale_rastreo/?id_rastreo='.$rastreo->id_rastreo.'&noCaja='.$rastreo->no_caja)?>" target="_blank" title="Imprimir Rastreo Efectivo">
                                    <i class="ico icon-print" style="cursor:pointer"></i> <?php echo $rastreo->id_rastreo ?></a>
                                </td>
                                <td style="width: 100px;"><input type="date" name="rastreo_fecha[]" value="<?php echo $rastreo->fecha_rastreo ?>" class="rastreo_fecha span12" maxlength="100" placeholder="fecha" <?php echo $readonly ?>></td>
                                <td style="width: 300px;"><input type="text" name="rastreo_nombre[]" value="<?php echo $rastreo->nombre ?>" class="rastreo_nombre span12" maxlength="100" placeholder="Nombre" <?php echo $readonly ?>></td>
                                <td style="width: 300px;"><input type="text" name="rastreo_nota[]" value="<?php echo $rastreo->nota ?>" class="rastreo_nota span12" maxlength="100" placeholder="Nota" <?php echo $readonly ?>></td>
                                <td style="width: 100px;"><input type="text" name="rastreo_monto[]" value="<?php echo $rastreo->monto ?>" class="rastreo_monto vpositive input-small" placeholder="Monto" required <?php echo $readonly ?>></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-success btn-save-rastreo" style="padding: 2px 7px 2px;"><i class="icon-plus"></i></button>
                                  <button type="button" class="btn btn-danger btn-del-rastreo" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                      <?php }} ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Ingresos por Reposicion-->
            </div>
          </div>
          <!-- /Ingresos por Reposicion -->
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
      <table id="lista_remisiones_modal" class="table table-striped table-bordered table-hover table-condensed">
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
              <td><input type="checkbox" class="chk-remision" data-id="<?php echo $remision->id_factura ?>" data-numremision="<?php echo $remision->folio ?>" data-total="<?php echo $remision->saldo ?>" data-foliofactura="<?php echo $remision->folio_factura ?>" data-concepto="<?php echo $remision->cliente ?>"></td>
              <td style="width: 66px;"><?php echo $remision->fecha ?></td>
              <td><?php echo ($remision->serie ? $remision->serie.'-':'').$remision->folio ?></td>
              <td><?php echo $remision->cliente ?></td>
              <td style="text-align: right;"><?php echo MyString::formatoNumero(MyString::float($remision->saldo), 2, '$') ?></td>
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

    <!-- Modal movimientos -->

  <div id="modal-movimientos" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 700px;left: 45%;">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <br>
      <h3 id="myModalLabel">Movimientos <!-- <input type="text" id="search-movimientos" placeholder="filtro"></input> --></h3>
    </div>
    <div class="modal-body" style="max-height: 370px;">
      <table id="lista_movimientos_modal" class="table table-striped table-bordered table-hover table-condensed" id="table-modal-movimientos">
        <caption></caption>
        <thead>
          <tr>
            <th></th>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>Poliza</th>
            <th>Monto</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($movimientos as $movi) { ?>
            <tr>
              <td><input type="checkbox" class="chk-movimiento" data-id="<?php echo $movi->id_movimiento ?>" data-total="<?php echo $movi->monto ?>" data-proveedor="<?php echo $movi->proveedor ?>" data-poliza="<?php echo $movi->numero_ref." ".$movi->banco ?>"></td>
              <td style="width: 66px;"><?php echo $movi->fecha ?></td>
              <td class="search-field"><?php echo $movi->proveedor ?></td>
              <td><?php echo $movi->numero_ref." ".$movi->banco ?></td>
              <td style="text-align: right;"><?php echo MyString::formatoNumero(MyString::float($movi->monto), 2, '$') ?></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="carga-movimientos">Cargar</button>
    </div>
  </div>

  <!-- Modal -->
  <div id="modalAreas" class="modal modal-w70 hide fade" tabindex="-1" role="dialog" aria-labelledby="modalAreasLavel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="modalAreasLavel">Catalogo bodega guadalajara</h3>
    </div>
    <div class="modal-body">

      <div class="row-fluid">

        <div>

      <?php //foreach ($areas as $key => $value)
      for ($i=1; $i < 10; $i++)
      { ?>
          <div class="span3" id="tblAreasDiv<?php echo $i ?>" style="display: none;">
            <table class="table table-hover table-condensed <?php echo ($i==1? 'tblAreasFirs': ''); ?>"
                id="tblAreas<?php echo $i ?>" data-id="<?php echo $i ?>">
              <thead>
                <tr>
                  <th style="width:10px;"></th>
                  <th>Codigo</th>
                  <th>Nombre</th>
                </tr>
              </thead>
              <tbody>
                <!-- <tr class="areaClick" data-id="" data-sig="">
                  <td><input type="radio" name="modalRadioSel" value="" data-uniform="false"></td>
                  <td>9</td>
                  <td>EMPAQUE</td>
                </tr> -->
              </tbody>
            </table>
          </div>
      <?php
      } ?>

        </div>

      </div>

    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
      <button class="btn btn-primary" id="btnModalAreasSel">Seleccionar</button>
    </div>
  </div>

    <!-- Modal -->
  <div id="modalCatalogos" class="modal modal-w70 hide fade" tabindex="-1" role="dialog" aria-labelledby="modalCatalogosLavel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="modalCatalogosLavel">Catálogos</h3>
    </div>
    <div class="modal-body">

      <div class="row-fluid">
        <div class="span6">
          <input type="hidden" id="accion_catalogos" value="true">
          <div class="control-group">
            <label class="control-label" for="dempresa">Empresa</label>
            <div class="controls">
              <input type="text" name="dempresa" class="span11" id="dempresa" value="" size="">
              <input type="hidden" name="did_empresa" id="did_empresa" value="">
              <input type="hidden" name="did_categoria" id="did_categoria" value="">
            </div>
          </div>

          <div class="control-group" id="cultivosGrup">
            <label class="control-label" for="area">Cultivo / Actividad / Producto </label>
            <div class="controls">
              <div class="input-append span12">
                <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area') ?>" placeholder="Limon, Piña">
              </div>
              <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId') ?>">
            </div>
          </div><!--/control-group -->

          <div class="control-group" id="ranchosGrup">
            <label class="control-label" for="rancho">Areas / Ranchos/ Lineas </label>
            <div class="controls">
              <div class="input-append span12">
                <input type="text" name="rancho" class="span11" id="rancho" value="<?php echo set_value('rancho') ?>" placeholder="Milagro A, Linea 1">
              </div>
              <input type="hidden" name="ranchoId" id="ranchoId" value="<?php echo set_value('ranchoId') ?>">
            </div>
          </div><!--/control-group -->

        </div>

        <div class="span6">
          <div class="control-group" id="centrosCostosGrup">
            <label class="control-label" for="centroCosto">Centro de costo </label>
            <div class="controls">
              <div class="input-append span12">
                <input type="text" name="centroCosto" class="span11" id="centroCosto" value="<?php echo set_value('centroCosto') ?>" placeholder="Mantenimiento, Gasto general">
              </div>
              <input type="hidden" name="centroCostoId" id="centroCostoId" value="<?php echo set_value('centroCostoId') ?>">
            </div>
          </div><!--/control-group -->

          <div class="control-group" id="activosGrup">
            <label class="control-label" for="activos">Activos </label>
            <div class="controls">
              <div class="input-append span12">
                <input type="text" name="activos" class="span11" id="activos" value="<?php echo set_value('activos') ?>" placeholder="Nissan FRX, Maquina limon">
              </div>
              <input type="hidden" name="activoId" id="activoId" value="<?php echo set_value('activoId') ?>">
            </div>
          </div><!--/control-group -->
        </div>

      </div>

    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
      <button class="btn btn-primary" id="btnModalCatalogosSel">Guardar</button>
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
