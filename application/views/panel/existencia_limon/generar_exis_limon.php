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
        $action = base_url('panel/existencias_limon/cargar/?'.MyString::getVarsLink(array('msg')));
        if (isset($caja['status']) && $caja['status'] === 'f' && ! $this->usuarios_model->tienePrivilegioDe('', 'existencias_limon/modificar_caja/'))
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
          <?php
          $fecha_caja_chica = set_value('fecha_caja_chica', isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d'));
          $farea = set_value('farea', isset($_GET['farea']) ? $_GET['farea'] : 2);
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
              <div class="span2" style="text-align: right;">
                <div class="row-fluid" style="margin: 3px 0;">
                  <div class="span12">Factor Merma
                    <input type="text" name="factor_merma" value="<?php echo set_value('factor_merma', (isset($caja['ext']->factor_merma)? $caja['ext']->factor_merma: '')) ?>" id="factor_merma" class="input-medium vpositive">
                  </div>
                </div>
              </div>
              <div class="span4">
                <div class="row-fluid">
                  <input type="hidden" name="fno_caja" id="fno_caja" value="<?php echo $_GET['fno_caja']; ?>">
                  <input type="hidden" name="farea" id="farea" value="<?php echo $farea; ?>">

                  <?php if ($show){ ?>
                    <div class="span4"><input type="submit" class="btn btn-success btn-large span12" value="Guardar"></div>
                  <?php } ?>

                  <?php if (isset($caja['status']) && $caja['status'] === 't'){ ?>
                    <div class="span4"><a href="<?php echo base_url('panel/existencias_limon/cerrar_caja/?id='.$caja['id'].'&'.MyString::getVarsLink(array('msg', 'id'))) ?>" class="btn btn-success btn-large span12">Cerrar Caja</a></div>
                  <?php } ?>

                  <?php if ($caja['guardado']) { ?>
                    <div class="span3"><a href="<?php echo base_url('panel/existencias_limon/print_caja?farea='.$farea.'&'.MyString::getVarsLink(array('msg', 'farea'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
                    <!-- <div class="span3"><a href="<?php echo base_url('panel/existencias_limon/print_caja2?farea='.$farea.'&'.MyString::getVarsLink(array('msg', 'farea'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir 2</a></div> -->
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

                    <!-- Existencia Anterior -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-existencia_ant">
                          <thead>
                            <tr>
                              <th colspan="7">EXISTENCIA ANTERIOR</th>
                            </tr>
                            <tr>
                              <th>CALIBRE</th>
                              <th>CLASIFICACION</th>
                              <th>UNIDAD</th>
                              <th>KILOS</th>
                              <th>CANTIDAD</th>
                              <th>COSTO</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $existencia_ant_kilos = $existencia_ant_cantidad = $existencia_ant_importe = 0;
                              foreach ($caja['existencia_anterior'] as $existencia_ant) {
                                $existencia_ant_kilos    += floatval($existencia_ant->kilos);
                                $existencia_ant_cantidad += floatval($existencia_ant->cantidad);
                                $existencia_ant_importe  += floatval($existencia_ant->importe);
                            ?>
                              <tr>
                                <td><?php echo $existencia_ant->calibre ?></td>
                                <td><?php echo $existencia_ant->clasificacion ?></td>
                                <td><?php echo $existencia_ant->unidad ?></td>
                                <td><?php echo $existencia_ant->kilos ?></td>
                                <td class="existencia_ant_cantidad"><?php echo $existencia_ant->cantidad ?></td>
                                <td class="existencia_ant_costo"><?php echo $existencia_ant->costo ?></td>
                                <td class="existencia_ant_importe"><?php echo $existencia_ant->importe ?></td>
                              </tr>
                            <?php } ?>

                            <tr>
                              <th colspan="3"></th>
                              <th><?php echo $existencia_ant_kilos ?></th>
                              <th><?php echo $existencia_ant_cantidad ?></th>
                              <th></th>
                              <th><?php echo $existencia_ant_importe ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Existencia Anterior -->

                    <!-- Existencia de piso anterior -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-existencia-pisoant">
                          <thead>
                            <tr>
                              <th colspan="6">EXISTENCIA DE PISO ANTERIOR</th>
                            </tr>
                            <tr>
                              <th>CALIBRE</th>
                              <th>UNIDAD</th>
                              <th>CANTIDAD</th>
                              <th>KILOS</th>
                              <th>COSTO</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $existenciaPisoAnt_kilos = $existenciaPisoAnt_cantidad = $existenciaPisoAnt_importe = 0;
                            foreach ($caja['existencia_piso_anterior'] as $existencia) {
                              $existenciaPisoAnt_kilos    += floatval($existencia->kilos);
                              $existenciaPisoAnt_cantidad += floatval($existencia->cantidad);
                              $existenciaPisoAnt_importe  += floatval($existencia->importe);
                            ?>
                              <tr>
                                <td>
                                  <?php echo $existencia->calibre ?>
                                </td>
                                <td>
                                  <?php foreach ($unidades as $key => $u) {
                                    echo ($u->id_unidad == $existencia->id_unidad? $u->nombre: '');
                                  } ?>
                                </td>
                                <td><?php echo $existencia->cantidad ?></td>
                                <td><?php echo $existencia->kilos ?></td>
                                <td><?php echo $existencia->costo ?></td>
                                <td><?php echo $existencia->importe ?></td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th colspan="2"></th>
                              <th id="exisPisoCantidadCant"><?php echo $existenciaPisoAnt_cantidad ?></th>
                              <th id="exisPisoKilosCant"><?php echo $existenciaPisoAnt_kilos ?></th>
                              <th></th>
                              <th id="exisPisoImporteCant"><?php echo $existenciaPisoAnt_importe ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Existencia de piso anterior -->

                    <!-- Compra de fruta empacada -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-compra-fruta-comp">
                          <thead>
                            <tr>
                              <th colspan="6">COMPRA DE FRUTA EMPACADA</th>
                              <th>
                                <button type="button" class="btn btn-success" id="btnAddFrutaCom"><i class="icon-plus"></i></button>
                              </th>
                            </tr>
                            <tr>
                              <th>CALIBRE</th>
                              <th>UNIDAD</th>
                              <th>CANTIDAD</th>
                              <th>KILOS</th>
                              <th>COSTO</th>
                              <th>IMPORTE</th>
                              <th>OPC</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $frutaCompra_kilos = $frutaCompra_cantidad = $frutaCompra_importe = 0;
                            if (isset($_POST['frutaCompra_id_unidad'])) {
                              foreach ($_POST['frutaCompra_id_unidad'] as $keyp => $existencia) {
                                $frutaCompra_kilos    += floatval($_POST['frutaCompra_kilos'][$keyp]);
                                $frutaCompra_cantidad += floatval($_POST['frutaCompra_cantidad'][$keyp]);
                                $frutaCompra_importe  += floatval($_POST['frutaCompra_importe'][$keyp]);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="frutaCompra_calibre[]" value="<?php echo $_POST['frutaCompra_calibre'][$keyp] ?>" class="span12 frutaCompra_calibre" required>
                                  <input type="hidden" name="frutaCompra_id_calibre[]" value="<?php echo $_POST['frutaCompra_id_calibre'][$keyp] ?>" class="span12 frutaCompra_id_calibre" required>
                                </td>
                                <td>
                                  <select name="frutaCompra_id_unidad[]" class="span12 frutaCompra_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $_POST['frutaCompra_id_unidad'][$keyp]? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="frutaCompra_cantidad[]" value="<?php echo $_POST['frutaCompra_cantidad'][$keyp] ?>" class="span12 vpositive frutaCompra_cantidad" required></td>
                                <td><input type="text" name="frutaCompra_kilos[]" value="<?php echo $_POST['frutaCompra_kilos'][$keyp] ?>" class="span12 vpositive frutaCompra_kilos" readonly></td>
                                <td><input type="text" name="frutaCompra_costo[]" value="<?php echo $_POST['frutaCompra_costo'][$keyp] ?>" class="span12 vpositive frutaCompra_costo" required></td>
                                <td><input type="text" name="frutaCompra_importe[]" value="<?php echo $_POST['frutaCompra_importe'][$keyp] ?>" class="span12 vpositive frutaCompra_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger frutaCompra_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php }
                            } ?>
                            <?php
                              foreach ($caja['compra_fruta_empacada'] as $existencia) {
                                $frutaCompra_kilos    += floatval($existencia->kilos);
                                $frutaCompra_cantidad += floatval($existencia->cantidad);
                                $frutaCompra_importe  += floatval($existencia->importe);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="frutaCompra_calibre[]" value="<?php echo $existencia->calibre ?>" class="span12 frutaCompra_calibre" required>
                                  <input type="hidden" name="frutaCompra_id_calibre[]" value="<?php echo $existencia->id_calibre ?>" class="span12 frutaCompra_id_calibre" required>
                                </td>
                                <td>
                                  <select name="frutaCompra_id_unidad[]" class="span12 frutaCompra_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $existencia->id_unidad? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="frutaCompra_cantidad[]" value="<?php echo $existencia->cantidad ?>" class="span12 vpositive frutaCompra_cantidad" required></td>
                                <td><input type="text" name="frutaCompra_kilos[]" value="<?php echo $existencia->kilos ?>" class="span12 vpositive frutaCompra_kilos" readonly></td>
                                <td><input type="text" name="frutaCompra_costo[]" value="<?php echo $existencia->costo ?>" class="span12 vpositive frutaCompra_costo" required></td>
                                <td><input type="text" name="frutaCompra_importe[]" value="<?php echo $existencia->importe ?>" class="span12 vpositive frutaCompra_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger frutaCompra_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th></th>
                              <th id="frutaCompraCantidad"><?php echo $frutaCompra_cantidad ?></th>
                              <th id="frutaCompraKilos"><?php echo $frutaCompra_kilos ?></th>
                              <th colspan="2"></th>
                              <th id="frutaCompraImporte"><?php echo $frutaCompra_importe ?></th>
                              <th></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Compra de fruta empacada -->

                    <!-- Compra de fruta materia prima -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-prestamolp">
                          <thead>
                            <tr>
                              <th colspan="5">MATERIA PRIMA</th>
                            </tr>
                            <tr>
                              <th>EMPRESA</th>
                              <th>CALIDAD</th>
                              <th>KILOS</th>
                              <th>PRECIO</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $compra_fruta_kilos = $compra_fruta_importe = 0;
                              foreach ($caja['compra_fruta'] as $com_fruta) {
                                  $compra_fruta_kilos += floatval($com_fruta->kilos);
                                  $compra_fruta_importe += floatval($com_fruta->importe);
                            ?>
                                <tr>
                                  <td><?php echo $com_fruta->nombre_fiscal ?></td>
                                  <td><?php echo $com_fruta->calidad ?></td>
                                  <td><?php echo $com_fruta->kilos ?></td>
                                  <td><?php echo $com_fruta->precio ?></td>
                                  <td><?php echo $com_fruta->importe ?></td>
                                </tr>
                            <?php } ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Compra de fruta materia prima -->

                    <!-- Devolución de fruta -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-devolucion-fruta">
                          <thead>
                            <tr>
                              <th colspan="6">DEVOLUCIÓN DE FRUTA
                                <input type="text" name="precio_dev_fruta" id="precio_dev_fruta" class="vpositive"
                                value="<?php echo set_value('precio_dev_fruta', (isset($caja['ext']->precio_dev_fruta)? $caja['ext']->precio_dev_fruta: '')) ?>" placeholder="Precio para devolucion de fruta">
                              </th>
                              <th>
                                <button type="button" class="btn btn-success" id="btnAddDevFruta"><i class="icon-plus"></i></button>
                              </th>
                            </tr>
                            <tr>
                              <th>CALIBRE</th>
                              <th>UNIDAD</th>
                              <th>CANTIDAD</th>
                              <th>KILOS</th>
                              <th>COSTO</th>
                              <th>IMPORTE</th>
                              <th>OPC</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $devFruta_kilos = $devFruta_cantidad = $devFruta_importe = 0;
                            if (isset($_POST['devFruta_id_unidad'])) {
                              foreach ($_POST['devFruta_id_unidad'] as $keyp => $existencia) {
                                $devFruta_kilos    += floatval($_POST['devFruta_kilos'][$keyp]);
                                $devFruta_cantidad += floatval($_POST['devFruta_cantidad'][$keyp]);
                                $devFruta_importe  += floatval($_POST['devFruta_importe'][$keyp]);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="devFruta_calibre[]" value="<?php echo $_POST['devFruta_calibre'][$keyp] ?>" class="span12 devFruta_calibre" required>
                                  <input type="hidden" name="devFruta_id_calibre[]" value="<?php echo $_POST['devFruta_id_calibre'][$keyp] ?>" class="span12 devFruta_id_calibre" required>
                                </td>
                                <td>
                                  <select name="devFruta_id_unidad[]" class="span12 devFruta_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $_POST['devFruta_id_unidad'][$keyp]? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="devFruta_cantidad[]" value="<?php echo $_POST['devFruta_cantidad'][$keyp] ?>" class="span12 vpositive devFruta_cantidad" required></td>
                                <td><input type="text" name="devFruta_kilos[]" value="<?php echo $_POST['devFruta_kilos'][$keyp] ?>" class="span12 vpositive devFruta_kilos" readonly></td>
                                <td><input type="text" name="devFruta_costo[]" value="<?php echo $_POST['devFruta_costo'][$keyp] ?>" class="span12 vpositive devFruta_costo" required></td>
                                <td><input type="text" name="devFruta_importe[]" value="<?php echo $_POST['devFruta_importe'][$keyp] ?>" class="span12 vpositive devFruta_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger devFruta_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php }
                            } ?>
                            <?php
                              foreach ($caja['devolucion_fruta'] as $existencia) {
                                $devFruta_kilos    += floatval($existencia->kilos);
                                $devFruta_cantidad += floatval($existencia->cantidad);
                                $devFruta_importe  += floatval($existencia->importe);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="devFruta_calibre[]" value="<?php echo $existencia->calibre ?>" class="span12 devFruta_calibre" required>
                                  <input type="hidden" name="devFruta_id_calibre[]" value="<?php echo $existencia->id_calibre ?>" class="span12 devFruta_id_calibre" required>
                                </td>
                                <td>
                                  <select name="devFruta_id_unidad[]" class="span12 devFruta_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $existencia->id_unidad? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="devFruta_cantidad[]" value="<?php echo $existencia->cantidad ?>" class="span12 vpositive devFruta_cantidad" required></td>
                                <td><input type="text" name="devFruta_kilos[]" value="<?php echo $existencia->kilos ?>" class="span12 vpositive devFruta_kilos" readonly></td>
                                <td><input type="text" name="devFruta_costo[]" value="<?php echo $existencia->costo ?>" class="span12 vpositive devFruta_costo" required></td>
                                <td><input type="text" name="devFruta_importe[]" value="<?php echo $existencia->importe ?>" class="span12 vpositive devFruta_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger devFruta_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th></th>
                              <th id="devFrutaCantidad"><?php echo $devFruta_cantidad ?></th>
                              <th id="devFrutaKilos"><?php echo $devFruta_kilos ?></th>
                              <th colspan="2"></th>
                              <th id="devFrutaImporte"><?php echo $devFruta_importe ?></th>
                              <th></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Devolución de fruta -->

                    <!-- Devolución de fruta industrial -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-devolucion-fruta-indus">
                          <thead>
                            <tr>
                              <th colspan="6">DEVOLUCIÓN AL INDUSTRIAL</th>
                              <th>
                                <button type="button" class="btn btn-success" id="btnAddDevFrutaIndus"><i class="icon-plus"></i></button>
                              </th>
                            </tr>
                            <tr>
                              <th>CALIBRE</th>
                              <th>UNIDAD</th>
                              <th>CANTIDAD</th>
                              <th>KILOS</th>
                              <th>COSTO</th>
                              <th>IMPORTE</th>
                              <th>OPC</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $devFrutaInds_kilos = $devFrutaInds_cantidad = $devFrutaInds_importe = 0;
                            if (isset($_POST['devFrutaInds_id_unidad'])) {
                              foreach ($_POST['devFrutaInds_id_unidad'] as $keyp => $existencia) {
                                $devFrutaInds_kilos    += floatval($_POST['devFrutaInds_kilos'][$keyp]);
                                $devFrutaInds_cantidad += floatval($_POST['devFrutaInds_cantidad'][$keyp]);
                                $devFrutaInds_importe  += floatval($_POST['devFrutaInds_importe'][$keyp]);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="devFrutaInds_calibre[]" value="<?php echo $_POST['devFrutaInds_calibre'][$keyp] ?>" class="span12 devFrutaInds_calibre" required>
                                  <input type="hidden" name="devFrutaInds_id_calibre[]" value="<?php echo $_POST['devFrutaInds_id_calibre'][$keyp] ?>" class="span12 devFrutaInds_id_calibre" required>
                                </td>
                                <td>
                                  <select name="devFrutaInds_id_unidad[]" class="span12 devFrutaInds_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $_POST['devFrutaInds_id_unidad'][$keyp]? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="devFrutaInds_cantidad[]" value="<?php echo $_POST['devFrutaInds_cantidad'][$keyp] ?>" class="span12 vpositive devFrutaInds_cantidad" required></td>
                                <td><input type="text" name="devFrutaInds_kilos[]" value="<?php echo $_POST['devFrutaInds_kilos'][$keyp] ?>" class="span12 vpositive devFrutaInds_kilos" readonly></td>
                                <td><input type="text" name="devFrutaInds_costo[]" value="<?php echo $_POST['devFrutaInds_costo'][$keyp] ?>" class="span12 vpositive devFrutaInds_costo" required></td>
                                <td><input type="text" name="devFrutaInds_importe[]" value="<?php echo $_POST['devFrutaInds_importe'][$keyp] ?>" class="span12 vpositive devFrutaInds_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger devFrutaInds_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php }
                            } ?>
                            <?php
                              foreach ($caja['devolucion_fruta_indust'] as $existencia) {
                                $devFrutaInds_kilos    += floatval($existencia->kilos);
                                $devFrutaInds_cantidad += floatval($existencia->cantidad);
                                $devFrutaInds_importe  += floatval($existencia->importe);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="devFrutaInds_calibre[]" value="<?php echo $existencia->calibre ?>" class="span12 devFrutaInds_calibre" required>
                                  <input type="hidden" name="devFrutaInds_id_calibre[]" value="<?php echo $existencia->id_calibre ?>" class="span12 devFrutaInds_id_calibre" required>
                                </td>
                                <td>
                                  <select name="devFrutaInds_id_unidad[]" class="span12 devFrutaInds_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $existencia->id_unidad? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="devFrutaInds_cantidad[]" value="<?php echo $existencia->cantidad ?>" class="span12 vpositive devFrutaInds_cantidad" required></td>
                                <td><input type="text" name="devFrutaInds_kilos[]" value="<?php echo $existencia->kilos ?>" class="span12 vpositive devFrutaInds_kilos" readonly></td>
                                <td><input type="text" name="devFrutaInds_costo[]" value="<?php echo $existencia->costo ?>" class="span12 vpositive devFrutaInds_costo" required></td>
                                <td><input type="text" name="devFrutaInds_importe[]" value="<?php echo $existencia->importe ?>" class="span12 vpositive devFrutaInds_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger devFrutaInds_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th></th>
                              <th id="devFrutaIndsCantidad"><?php echo $devFrutaInds_cantidad ?></th>
                              <th id="devFrutaIndsKilos"><?php echo $devFrutaInds_kilos ?></th>
                              <th colspan="2"></th>
                              <th id="devFrutaIndsImporte"><?php echo $devFrutaInds_importe ?></th>
                              <th></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Devolución de fruta industrial -->

                    <!-- Ventas -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-ventas">
                          <thead>
                            <tr>
                              <th colspan="5">VENTAS</th>
                              <th colspan="5" id="dvfondo_caja"></th>
                            </tr>
                            <tr>
                              <!-- <th>FECHA</th> -->
                              <th>FOLIO</th>
                              <th>NO SALIDA</th>
                              <th>CLIENTE</th>
                              <th>CALIBRE</th>
                              <th>CLASIF</th>
                              <th>UNIDAD</th>
                              <th>KILOS</th>
                              <th>CANTIDAD</th>
                              <th>PRECIO</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody style="overflow-y: auto;max-height: 300px;">
                            <?php
                                $venta_importe = $venta_kilos = $venta_cantidad = 0;
                                  foreach ($caja['ventas'] as $venta) {
                                      $venta_importe += floatval($venta->importe);
                                      $venta_kilos += floatval($venta->kilos);
                                      $venta_cantidad += floatval($venta->cantidad);
                                    ?>
                                    <tr>
                                      <td><?php echo $venta->serie.$venta->folio ?></td>
                                      <td><?php echo $venta->no_salida_fruta ?></td>
                                      <td><?php echo $venta->nombre_fiscal ?></td>
                                      <td><?php echo $venta->calibre ?></td>
                                      <td><?php echo $venta->clasificacion ?></td>
                                      <td><?php echo $venta->unidad ?></td>
                                      <td><?php echo $venta->kilos ?></td>
                                      <td><?php echo $venta->cantidad ?></td>
                                      <td><?php echo $venta->precio ?></td>
                                      <td><?php echo $venta->importe ?></td>
                                    </tr>
                            <?php } ?>
                            <tr>
                              <th colspan="6"></th>
                              <th><?php echo $venta_kilos ?></th>
                              <th><?php echo $venta_cantidad ?></th>
                              <th></th>
                              <th><?php echo $venta_importe ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Ventas -->

                    <!-- Existencia Empacada -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-existencia">
                          <thead>
                            <tr>
                              <th colspan="7">EXISTENCIA EMPACADA</th>
                            </tr>
                            <tr>
                              <th>CALIBRE</th>
                              <th>CLASIFICACION</th>
                              <th>UNIDAD</th>
                              <th>KILOS</th>
                              <th>CANTIDAD</th>
                              <th>COSTO</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $existencia_kilos = $existencia_cantidad = $existencia_importe = 0;
                              foreach ($caja['existencia'] as $existencia) {
                                $existencia_kilos    += floatval($existencia->kilos);
                                $existencia_cantidad += floatval($existencia->cantidad);
                                $existencia_importe  += floatval($existencia->importe);
                            ?>
                              <tr>
                                <td><?php echo $existencia->calibre ?></td>
                                <td><?php echo $existencia->clasificacion ?>
                                  <input type="hidden" name="existencia_id_calibre[]" value="<?php echo $existencia->id_calibre ?>">
                                  <input type="hidden" name="existencia_id_unidad[]" value="<?php echo $existencia->id_unidad ?>">
                                  <input type="hidden" name="existencia_kilos[]" value="<?php echo $existencia->kilos ?>">
                                  <input type="hidden" name="existencia_cantidad[]" value="<?php echo $existencia->cantidad ?>" class="existencia_cantidad">
                                  <input type="hidden" name="existencia_importe[]" value="<?php echo $existencia->importe ?>" class="existencia_importee">
                                </td>
                                <td><?php echo $existencia->unidad ?></td>
                                <td><?php echo $existencia->kilos ?></td>
                                <td class="existencia_cantidad"><?php echo $existencia->cantidad ?></td>
                                <td>
                                  <input type="text" name="existencia_costo[] " value="<?php echo $existencia->costo ?>" class="span12 existencia_costo" required>
                                </td>
                                <td class="existencia_importe"><?php echo $existencia->importe ?></td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th colspan="3"></th>
                              <th><?php echo $existencia_kilos ?></th>
                              <th><?php echo $existencia_cantidad ?></th>
                              <th></th>
                              <th id="exisImporte"><?php echo $existencia_importe ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Existencia Empacada -->

                    <!-- Existencia de piso -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-existencia-piso">
                          <thead>
                            <tr>
                              <th colspan="6">EXISTENCIA DE PISO</th>
                              <th>
                                <button type="button" class="btn btn-success" id="btnAddExisPiso"><i class="icon-plus"></i></button>
                                <input type="hidden" id="unidades" value='<?php echo json_encode($unidades) ?>'>
                              </th>
                            </tr>
                            <tr>
                              <th>CALIBRE</th>
                              <th>UNIDAD</th>
                              <th>CANTIDAD</th>
                              <th>KILOS</th>
                              <th>COSTO</th>
                              <th>IMPORTE</th>
                              <th>OPC</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $existenciaPiso_kilos = $existenciaPiso_cantidad = $existenciaPiso_importe = 0;
                            if (isset($_POST['existenciaPiso_id_unidad'])) {
                              foreach ($_POST['existenciaPiso_id_unidad'] as $keyp => $existencia) {
                                $existenciaPiso_kilos    += floatval($_POST['existenciaPiso_kilos'][$keyp]);
                                $existenciaPiso_cantidad += floatval($_POST['existenciaPiso_cantidad'][$keyp]);
                                $existenciaPiso_importe  += floatval($_POST['existenciaPiso_importe'][$keyp]);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="existenciaPiso_calibre[]" value="<?php echo $_POST['existenciaPiso_calibre'][$keyp] ?>" class="span12 existenciaPiso_calibre" required>
                                  <input type="hidden" name="existenciaPiso_id_calibre[]" value="<?php echo $_POST['existenciaPiso_id_calibre'][$keyp] ?>" class="span12 existenciaPiso_id_calibre" required>
                                </td>
                                <td>
                                  <select name="existenciaPiso_id_unidad[]" class="span12 existenciaPiso_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $_POST['existenciaPiso_id_unidad'][$keyp]? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="existenciaPiso_cantidad[]" value="<?php echo $_POST['existenciaPiso_cantidad'][$keyp] ?>" class="span12 vpositive existenciaPiso_cantidad" required></td>
                                <td><input type="text" name="existenciaPiso_kilos[]" value="<?php echo $_POST['existenciaPiso_kilos'][$keyp] ?>" class="span12 vpositive existenciaPiso_kilos" readonly></td>
                                <td><input type="text" name="existenciaPiso_costo[]" value="<?php echo $_POST['existenciaPiso_costo'][$keyp] ?>" class="span12 vpositive existenciaPiso_costo" required></td>
                                <td><input type="text" name="existenciaPiso_importe[]" value="<?php echo $_POST['existenciaPiso_importe'][$keyp] ?>" class="span12 vpositive existenciaPiso_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger existenciaPiso_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php }
                            } ?>
                            <?php
                              foreach ($caja['existencia_piso'] as $existencia) {
                                $existenciaPiso_kilos    += floatval($existencia->kilos);
                                $existenciaPiso_cantidad += floatval($existencia->cantidad);
                                $existenciaPiso_importe  += floatval($existencia->importe);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="existenciaPiso_calibre[]" value="<?php echo $existencia->calibre ?>" class="span12 existenciaPiso_calibre" required>
                                  <input type="hidden" name="existenciaPiso_id_calibre[]" value="<?php echo $existencia->id_calibre ?>" class="span12 existenciaPiso_id_calibre" required>
                                </td>
                                <td>
                                  <select name="existenciaPiso_id_unidad[]" class="span12 existenciaPiso_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $existencia->id_unidad? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="existenciaPiso_cantidad[]" value="<?php echo $existencia->cantidad ?>" class="span12 vpositive existenciaPiso_cantidad" required></td>
                                <td><input type="text" name="existenciaPiso_kilos[]" value="<?php echo $existencia->kilos ?>" class="span12 vpositive existenciaPiso_kilos" readonly></td>
                                <td><input type="text" name="existenciaPiso_costo[]" value="<?php echo $existencia->costo ?>" class="span12 vpositive existenciaPiso_costo" required></td>
                                <td><input type="text" name="existenciaPiso_importe[]" value="<?php echo $existencia->importe ?>" class="span12 vpositive existenciaPiso_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger existenciaPiso_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th></th>
                              <th id="exisPisoCantidad"><?php echo $existenciaPiso_cantidad ?></th>
                              <th id="exisPisoKilos"><?php echo $existenciaPiso_kilos ?></th>
                              <th colspan="2"></th>
                              <th id="exisPisoImporte"><?php echo $existenciaPiso_importe ?></th>
                              <th></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Existencia de piso -->

                    <!-- Existencia de reproceso -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-existencia-reproceso">
                          <thead>
                            <tr>
                              <th colspan="6">EXISTENCIA REPROCESO</th>
                              <th style="width: 35px;">
                                <button type="button" class="btn btn-success" id="btnAddExisRepro"><i class="icon-plus"></i></button>
                              </th>
                            </tr>
                            <tr>
                              <th>CALIBRE</th>
                              <th>UNIDAD</th>
                              <th>CANTIDAD</th>
                              <th>KILOS</th>
                              <th>COSTO</th>
                              <th>IMPORTE</th>
                              <th>OPC</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $existenciaReProceso_kilos = $existenciaReProceso_cantidad = $existenciaReProceso_importe = 0;
                            if (isset($_POST['existenciaRepro_calibre'])) {
                              foreach ($_POST['existenciaRepro_calibre'] as $keyp => $existencia) {
                                $existenciaReProceso_kilos    += floatval($_POST['existenciaRepro_kilos'][$keyp]);
                                $existenciaReProceso_cantidad += floatval($_POST['existenciaRepro_cantidad'][$keyp]);
                                $existenciaReProceso_importe  += floatval($_POST['existenciaRepro_importe'][$keyp]);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="existenciaRepro_calibre[]" value="<?php echo $_POST['existenciaRepro_calibre'][$keyp] ?>" class="span12 existenciaRepro_calibre" required>
                                  <input type="hidden" name="existenciaRepro_id_calibre[]" value="<?php echo $_POST['existenciaRepro_id_calibre'][$keyp] ?>" class="span12 existenciaRepro_id_calibre" required>
                                </td>
                                <td>
                                  <select name="existenciaRepro_id_unidad[]" class="span12 existenciaRepro_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $_POST['existenciaRepro_id_unidad'][$keyp]? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="existenciaRepro_cantidad[]" value="<?php echo $_POST['existenciaRepro_cantidad'][$keyp] ?>" class="span12 vpositive existenciaRepro_cantidad" required></td>
                                <td><input type="text" name="existenciaRepro_kilos[]" value="<?php echo $_POST['existenciaRepro_kilos'][$keyp] ?>" class="span12 vpositive existenciaRepro_kilos" readonly></td>
                                <td><input type="text" name="existenciaRepro_costo[]" value="<?php echo $_POST['existenciaRepro_costo'][$keyp] ?>" class="span12 vpositive existenciaRepro_costo" required></td>
                                <td><input type="text" name="existenciaRepro_importe[]" value="<?php echo $_POST['existenciaRepro_importe'][$keyp] ?>" class="span12 vpositive existenciaRepro_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger existenciaRepro_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php }
                            } ?>
                            <?php
                              foreach ($caja['existencia_reproceso'] as $existencia) {
                                $existenciaReProceso_kilos    += floatval($existencia->kilos);
                                $existenciaReProceso_cantidad += floatval($existencia->cantidad);
                                $existenciaReProceso_importe  += floatval($existencia->importe);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="existenciaRepro_calibre[]" value="<?php echo $existencia->calibre ?>" class="span12 existenciaRepro_calibre" required>
                                  <input type="hidden" name="existenciaRepro_id_calibre[]" value="<?php echo $existencia->id_calibre ?>" class="span12 existenciaRepro_id_calibre" required>
                                </td>
                                <td>
                                  <select name="existenciaRepro_id_unidad[]" class="span12 existenciaRepro_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $existencia->id_unidad? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="existenciaRepro_cantidad[]" value="<?php echo $existencia->cantidad ?>" class="span12 vpositive existenciaRepro_cantidad" required></td>
                                <td><input type="text" name="existenciaRepro_kilos[]" value="<?php echo $existencia->kilos ?>" class="span12 vpositive existenciaRepro_kilos" readonly></td>
                                <td><input type="text" name="existenciaRepro_costo[]" value="<?php echo $existencia->costo ?>" class="span12 vpositive existenciaRepro_costo" required></td>
                                <td><input type="text" name="existenciaRepro_importe[]" value="<?php echo $existencia->importe ?>" class="span12 vpositive existenciaRepro_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger existenciaRepro_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th></th>
                              <th id="exisReproCantidad"><?php echo $existenciaReProceso_cantidad ?></th>
                              <th id="exisReproKilos"><?php echo $existenciaReProceso_kilos ?></th>
                              <th colspan="2"></th>
                              <th id="exisReproImporte"><?php echo $existenciaReProceso_importe ?></th>
                              <th></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Existencia de reproceso -->

                    <!-- INDUSTRIAL -->
                    <?php $resultado_kilos = $existencia_ant_kilos + $compra_fruta_kilos - $existencia_kilos - $venta_kilos + $frutaCompra_kilos; ?>
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-industrial">
                          <thead>
                            <tr>
                              <th colspan="4">INDUSTRIAL</th>
                            </tr>
                            <tr>
                              <th>CALIBRE</th>
                              <th>KILOS</th>
                              <th>COSTO</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              $industrial_kilos = $industrial_importe = 0;
                              $industrial_kilos    = floatval($resultado_kilos);
                              $industrial_costo    = (isset($caja['industrial']->costo)? $caja['industrial']->costo: 0);
                              $industrial_importe  = $resultado_kilos * $industrial_costo;
                            ?>
                            <tr>
                              <td>Industrial</td>
                              <td><input type="text" name="industrial_kilos[]" value="<?php echo $industrial_kilos ?>" class="span12 vpositive industrial_kilos" readonly></td>
                              <td><input type="text" name="industrial_costo[]" value="<?php echo $industrial_costo ?>" class="span12 vpositive industrial_costo"></td>
                              <td><input type="text" name="industrial_importe[]" value="<?php echo $industrial_importe ?>" class="span12 vpositive industrial_importe" readonly></td>
                            </tr>

                            <tr class="footer">
                              <th></th>
                              <th id="indusKilos"><?php echo $industrial_kilos ?></th>
                              <th></th>
                              <th id="indusImporte"><?php echo $industrial_importe ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ INDUSTRIAL -->

                    <!-- cOSTO Produccion -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-produccion">
                          <thead>
                            <tr>
                              <th colspan="7">COSTO PRODUCCION</th>
                            </tr>
                            <tr>
                              <th>COSTO X ENVACE</th>
                              <th>CALIBRE</th>
                              <th>CLASIFICACION</th>
                              <th>UNIDAD</th>
                              <th>KILOS</th>
                              <th>CANTIDAD</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $produccion_kilos = $produccion_cantidad = $produccion_importe = 0;
                              foreach ($caja['produccion'] as $produccion) {
                                $produccion_kilos    += floatval($produccion->kilos);
                                $produccion_cantidad += floatval($produccion->cantidad);
                                $produccion_importe  += floatval($produccion->importe);
                            ?>
                              <tr>
                                <td style="width: 100px;">
                                  <input type="text" name="produccion_costo[]" value="<?php echo $produccion->costo ?>" class="input-small produccion_costo" style="width: 150px;" <?php echo $readonly ?>>
                                  <input type="hidden" name="produccion_id_produccion[]" value="<?php echo $produccion->id_produccion ?>" id="produccion_id_produccion" class="input-small vpositive">
                                  <input type="hidden" name="produccion_id_calibre[]" value="<?php echo $produccion->id_calibre ?>" id="produccion_id_calibre" class="input-small vpositive">
                                  <input type="hidden" name="produccion_id_unidad[]" value="<?php echo $produccion->id_unidad ?>" class="input-small produccion_id_unidad vpositive gasto-cargo-id">
                                  <!-- <input type="hidden" name="produccion_kilos[]" value="<?php echo $produccion->kilos ?>" class="input-small produccion_kilos vpositive gasto-cargo-id"> -->
                                  <!-- <input type="hidden" name="produccion_cantidad[]" value="<?php echo $produccion->cantidad ?>" class="input-small tproduccion_cantidad vpositive gasto-cargo-id"> -->
                                  <input type="hidden" name="produccion_importe[]" value="<?php echo $produccion->importe ?>" class="input-small tproduccion_importe vpositive gasto-cargo-id">
                                </td>
                                <td><?php echo $produccion->calibre ?></td>
                                <td><?php echo $produccion->clasificacion ?></td>
                                <td><?php echo $produccion->unidad ?></td>
                                <td><?php echo $produccion->kilos ?></td>
                                <td class="produccion_cantidad"><?php echo $produccion->cantidad ?></td>
                                <td class="produccion_importe"><?php echo $produccion->importe ?></td>
                              </tr>
                            <?php } ?>

                            <tr>
                              <th colspan="4"></th>
                              <th><?php echo $produccion_kilos ?></th>
                              <th><?php echo $produccion_cantidad ?></th>
                              <th><?php echo $produccion_importe ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ cOSTO Produccion -->

                    <!-- Costo de venta -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-costo-ventas" style="margin-bottom: 0px;">
                          <thead>
                            <tr>
                              <th colspan="3">COSTO DE VENTAS</th>
                              <th style="width: 35px;">
                                <button type="button" class="btn btn-success" id="btnAddCostoVentas"><i class="icon-plus"></i></button>
                              </th>
                            </tr>
                            <tr>
                              <th>NOMBRE</th>
                              <th>DESCRIPCION</th>
                              <th>IMPORTE</th>
                              <th>OPC</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $descuentoVentas_importe = 0;
                            if (isset($_POST['descuentoVentas_nombre'])) {
                              foreach ($_POST['descuentoVentas_nombre'] as $keyp => $desc) {
                                $descuentoVentas_importe  += floatval($_POST['descuentoVentas_importe'][$keyp]);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="descuentoVentas_nombre[]" value="<?php echo $_POST['descuentoVentas_nombre'][$keyp] ?>" class="span12 descuentoVentas_nombre" required>
                                  <input type="hidden" name="descuentoVentas_id[]" value="" class="span12 descuentoVentas_id">
                                  <input type="hidden" name="descuentoVentas_delete[]" value="<?php echo $_POST['descuentoVentas_delete'][$keyp] ?>" class="span12 descuentoVentas_delete">
                                </td>
                                <td>
                                  <input type="text" name="descuentoVentas_descripcion[]" value="<?php echo $_POST['descuentoVentas_descripcion'][$keyp] ?>" class="span12 descuentoVentas_descripcion" required>
                                </td>
                                <td><input type="text" name="descuentoVentas_importe[]" value="<?php echo $_POST['descuentoVentas_importe'][$keyp] ?>" class="span12 vpositive descuentoVentas_importe" required></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger descuentoVentas_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php }
                            } ?>
                            <?php
                              foreach ($caja['costo_ventas'] as $desc) {
                                $descuentoVentas_importe  += floatval($desc->importe);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="descuentoVentas_nombre[]" value="<?php echo $desc->nombre ?>" class="span12 descuentoVentas_nombre" required>
                                  <input type="hidden" name="descuentoVentas_id[]" value="<?php echo $desc->id ?>" class="span12 descuentoVentas_id">
                                  <input type="hidden" name="descuentoVentas_delete[]" value="false" class="span12 descuentoVentas_delete">
                                </td>
                                <td>
                                  <input type="text" name="descuentoVentas_descripcion[]" value="<?php echo $desc->descripcion ?>" class="span12 descuentoVentas_descripcion" required>
                                </td>
                                <td><input type="text" name="descuentoVentas_importe[]" value="<?php echo $desc->importe ?>" class="span12 vpositive descuentoVentas_importe" required></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger descuentoVentas_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th></th>
                              <th></th>
                              <th id="descuentoVentas_importe"><?php echo $descuentoVentas_importe ?></th>
                              <th></th>
                            </tr>
                          </tbody>
                        </table>
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-costo-ventas-fletes">
                          <thead>
                            <tr>
                              <th colspan="5">FLETES</th>
                            </tr>
                            <tr>
                              <th>FOLIO</th>
                              <th>REM/FAC</th>
                              <th>PROVEEDOR</th>
                              <th>CANTIDAD</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              $descuentoVentasFletes_cantidad = $descuentoVentasFletes_importe = 0;
                              foreach ($caja['costo_ventas_fletes'] as $desc) {
                                $descuentoVentasFletes_cantidad  += floatval($desc->cantidad);
                                $descuentoVentasFletes_importe  += floatval($desc->importe);
                            ?>
                              <tr>
                                <td><?php echo $desc->folio ?></td>
                                <td><?php echo $desc->facturas ?></td>
                                <td><?php echo $desc->proveedor ?></td>
                                <td><?php echo $desc->cantidad ?></td>
                                <td><?php echo $desc->importe ?></td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th></th>
                              <th></th>
                              <th></th>
                              <th><?php echo $descuentoVentasFletes_cantidad ?></th>
                              <th><?php echo $descuentoVentasFletes_importe ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Costo de venta -->

                    <!-- Certificados -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-costo-ventas" style="margin-bottom: 0px;">
                          <thead>
                            <tr>
                              <th colspan="6">CERTIFICADOS</th>
                            </tr>
                            <tr>
                              <th>FOLIO</th>
                              <th>PROVEEDORES</th>
                              <th>TIPO</th>
                              <th>CANTIDAD</th>
                              <th>PRECIO</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              $certificados_importe = 0;
                              foreach ($caja['certificados'] as $cert) {
                                $certificados_importe  += floatval($cert->importe);
                              ?>
                              <tr>
                                <td><?php echo $cert->serie.$cert->folio ?></td>
                                <td><?php echo $cert->proveedores ?></td>
                                <td><?php echo $cert->clasificacion ?></td>
                                <td><?php echo $cert->certificado ?></td>
                                <td><?php echo $cert->precio ?></td>
                                <td><?php echo $cert->importe ?></td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th colspan="5"></th>
                              <th id="certificados_importe"><?php echo $certificados_importe ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Certificados -->

                    <!-- Comisiones a terceros -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 10px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-comision-terceros">
                          <thead>
                            <tr>
                              <th colspan="5">COMISIONES A TERCEROS</th>
                              <th style="width: 35px;">
                                <button type="button" class="btn btn-success" id="btnAddComisionTerceros"><i class="icon-plus"></i></button>
                              </th>
                            </tr>
                            <tr>
                              <th>NOMBRE</th>
                              <th>DESCRIPCION</th>
                              <th>CANTIDAD</th>
                              <th>COSTO</th>
                              <th>IMPORTE</th>
                              <th>OPC</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $comisionTerceros_cantidad = $comisionTerceros_importe = 0;
                            if (isset($_POST['comisionTerceros_nombre'])) {
                              foreach ($_POST['comisionTerceros_nombre'] as $keyp => $desc) {
                                $comisionTerceros_cantidad  += floatval($_POST['comisionTerceros_cantidad'][$keyp]);
                                $comisionTerceros_importe  += floatval($_POST['comisionTerceros_importe'][$keyp]);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="comisionTerceros_nombre[]" value="<?php echo $_POST['comisionTerceros_nombre'][$keyp] ?>" class="span12 comisionTerceros_nombre" required>
                                  <input type="hidden" name="comisionTerceros_id[]" value="" class="span12 comisionTerceros_id">
                                  <input type="hidden" name="comisionTerceros_delete[]" value="<?php echo $_POST['comisionTerceros_delete'][$keyp] ?>" class="span12 comisionTerceros_delete">
                                </td>
                                <td>
                                  <input type="text" name="comisionTerceros_descripcion[]" value="<?php echo $_POST['comisionTerceros_descripcion'][$keyp] ?>" class="span12 comisionTerceros_descripcion" required>
                                </td>
                                <td><input type="text" name="comisionTerceros_cantidad[]" value="<?php echo $_POST['comisionTerceros_cantidad'][$keyp] ?>" class="span12 vpositive comisionTerceros_cantidad" required></td>
                                <td><input type="text" name="comisionTerceros_costo[]" value="<?php echo $_POST['comisionTerceros_costo'][$keyp] ?>" class="span12 vpositive comisionTerceros_costo" required></td>
                                <td><input type="text" name="comisionTerceros_importe[]" value="<?php echo $_POST['comisionTerceros_importe'][$keyp] ?>" class="span12 vpositive comisionTerceros_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger comisionTerceros_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php }
                            } ?>
                            <?php
                              foreach ($caja['comision_terceros'] as $desc) {
                                $comisionTerceros_cantidad  += floatval($desc->cantidad);
                                $comisionTerceros_importe  += floatval($desc->importe);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="comisionTerceros_nombre[]" value="<?php echo $desc->nombre ?>" class="span12 comisionTerceros_nombre" required>
                                  <input type="hidden" name="comisionTerceros_id[]" value="<?php echo $desc->id ?>" class="span12 comisionTerceros_id">
                                  <input type="hidden" name="comisionTerceros_delete[]" value="false" class="span12 comisionTerceros_delete">
                                </td>
                                <td>
                                  <input type="text" name="comisionTerceros_descripcion[]" value="<?php echo $desc->descripcion ?>" class="span12 comisionTerceros_descripcion" required>
                                </td>
                                <td><input type="text" name="comisionTerceros_cantidad[]" value="<?php echo $desc->cantidad ?>" class="span12 vpositive comisionTerceros_cantidad" required></td>
                                <td><input type="text" name="comisionTerceros_costo[]" value="<?php echo $desc->costo ?>" class="span12 vpositive comisionTerceros_costo" required></td>
                                <td><input type="text" name="comisionTerceros_importe[]" value="<?php echo $desc->importe ?>" class="span12 vpositive comisionTerceros_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger comisionTerceros_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th></th>
                              <th></th>
                              <th id="comisionTerceros_cantidad"><?php echo $comisionTerceros_cantidad ?></th>
                              <th></th>
                              <th id="comisionTerceros_importe"><?php echo $comisionTerceros_importe ?></th>
                              <th></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Comisiones a terceros -->

                    <!-- Ventas industrial -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-ventas">
                          <thead>
                            <tr>
                              <th colspan="5">VENTAS INDUSTRIAL</th>
                              <th colspan="5" id="dvfondo_caja"></th>
                            </tr>
                            <tr>
                              <!-- <th>FECHA</th> -->
                              <th>FOLIO</th>
                              <th>NO SALIDA</th>
                              <th>CLIENTE</th>
                              <th>CALIBRE</th>
                              <th>CLASIF</th>
                              <th>UNIDAD</th>
                              <th>KILOS</th>
                              <th>CANTIDAD</th>
                              <th>PRECIO</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody style="overflow-y: auto;max-height: 300px;">
                            <?php
                                $venta_importe_ind = $venta_kilos_ind = $venta_cantidad_ind = 0;
                                  foreach ($caja['ventas_industrial'] as $venta) {
                                      $venta_importe_ind += floatval($venta->importe);
                                      $venta_kilos_ind += floatval($venta->kilos);
                                      $venta_cantidad_ind += floatval($venta->cantidad);
                                    ?>
                                    <tr>
                                      <td><?php echo $venta->serie.$venta->folio ?></td>
                                      <td><?php echo $venta->no_salida_fruta ?></td>
                                      <td><?php echo $venta->nombre_fiscal ?></td>
                                      <td><?php echo $venta->calibre ?></td>
                                      <td><?php echo $venta->clasificacion ?></td>
                                      <td><?php echo $venta->unidad ?></td>
                                      <td><?php echo $venta->kilos ?></td>
                                      <td><?php echo $venta->cantidad ?></td>
                                      <td><?php echo $venta->precio ?></td>
                                      <td><?php echo $venta->importe ?></td>
                                    </tr>
                            <?php } ?>
                            <tr>
                              <th colspan="6"></th>
                              <th><?php echo $venta_kilos_ind ?></th>
                              <th><?php echo $venta_cantidad_ind ?></th>
                              <th></th>
                              <th><?php echo $venta_importe_ind ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Ventas industrial -->

                    <!-- Gastos -->
                    <?php $totalGastos = 0; ?>
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
                                        <th colspan="2">GASTOS GENERALES
                                          <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button>
                                        </th>
                                        <th colspan="2">IMPORTE</th>
                                      </tr>
                                      <tr>
                                        <!-- <th>COD AREA</th> -->
                                        <th>NOMBRE</th>
                                        <th>CONCEPTO</th>
                                        <th>CARGO</th>
                                        <th></th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      <?php
                                        $modificar_gasto = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/modificar_gastos/');
                                        $mod_gas_readonly = !$modificar_gasto && $readonly == ''? ' readonly': '';

                                        if (count($caja['gastos']) == 0 && isset($_POST['gasto_concepto']) && count($_POST['gasto_concepto']) > 0) {
                                          foreach ($_POST['gasto_concepto'] as $key => $concepto) {
                                            $totalGastos += floatval($_POST['gasto_importe'][$key]); ?>
                                              <tr>
                                                <!-- <td style=""> -->
                                                  <input type="hidden" name="gasto_id_gasto[]" value="" id="gasto_id_gasto">
                                                  <input type="hidden" name="gasto_del[]" value="" id="gasto_del">
                                                  <!-- <input type="text" name="codigoArea[]" value="<?php echo $_POST['codigoArea'][$key] ?>" id="codigoArea" class="span12 showCodigoAreaAuto"> -->
                                                  <input type="hidden" name="codigoAreaId[]" value="<?php echo $_POST['codigoAreaId'][$key] ?>" id="codigoAreaId" class="span12">
                                                  <input type="hidden" name="codigoCampo[]" value="<?php echo $_POST['codigoCampo'][$key] ?>" id="codigoCampo" class="span12">
                                                  <!-- <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i> -->
                                                <!-- </td> -->
                                                <td style="">
                                                  <input type="text" name="gasto_nombre[]" value="<?php echo $_POST['gasto_nombre'][$key] ?>" class="span12 gasto-nombre" >
                                                </td>
                                                <td style="">
                                                  <input type="text" name="gasto_concepto[]" value="<?php echo $_POST['gasto_concepto'][$key] ?>" class="span12 gasto-concepto" >
                                                </td>
                                                <td style=""><input type="text" name="gasto_importe[]" value="<?php echo $_POST['gasto_importe'][$key] ?>" class="span12 vpositive gasto-importe"></td>
                                                <td style="">
                                                  <button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                                </td>
                                              </tr>
                                      <?php }} else {
                                        foreach ($caja['gastos'] as $gasto) {
                                          $totalGastos += floatval($gasto->monto);
                                        ?>
                                        <tr>
                                          <!-- <td style=""> -->
                                            <input type="hidden" name="gasto_id_gasto[]" value="<?php echo $gasto->id_gasto ?>" id="gasto_id_gasto">
                                            <input type="hidden" name="gasto_del[]" value="" id="gasto_del">
                                            <!-- <input type="text" name="codigoArea[]" value="<?php echo $gasto->nombre_codigo ?>" id="codigoArea" class="span12 showCodigoAreaAuto"> -->
                                            <input type="hidden" name="codigoAreaId[]" value="<?php echo $gasto->id_area ?>" id="codigoAreaId" class="span12">
                                            <input type="hidden" name="codigoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12">
                                            <!-- <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                                            <a href="<?php echo base_url('panel/caja_chica/print_vale/?id='.$gasto->id_gasto)?>" target="_blank" title="Imprimir VALE DE CAJA CHICA">
                                              <i class="ico icon-print" style="cursor:pointer"></i></a> -->
                                          <!-- </td> -->
                                          <td style="">
                                            <input type="text" name="gasto_nombre[]" value="<?php echo $gasto->nombre ?>" class="span12 gasto-nombre">
                                          </td>
                                          <td style="">
                                            <input type="text" name="gasto_concepto[]" value="<?php echo $gasto->concepto ?>" class="span12 gasto-concepto">
                                          </td>
                                          <td style=""><input type="text" name="gasto_importe[]" value="<?php echo $gasto->monto ?>" class="span12 vpositive gasto-importe"></td>
                                          <td style="">
                                            <button type="button" class="btn btn-danger btn-del-gasto" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                          </td>
                                        </tr>
                                      <?php }} ?>
                                      <tr class="row-total">
                                        <td colspan="2" style="text-align: right; font-weight: bolder;">TOTAL</td>
                                        <td colspan="2"><input type="text" value="<?php echo $totalGastos ?>" class="vpositive" id="ttotal-gastos" style="text-align: right;" readonly></td>
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

                    <!-- MANO DE OBRA E INSUMOS -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-manoo-insumos">
                          <thead>
                            <tr>
                              <th colspan="6">MANO DE OBRA E INSUMOS</th>
                              <th>
                                <button type="button" class="btn btn-success" id="btnAddManooInsumos"><i class="icon-plus"></i></button>
                              </th>
                            </tr>
                            <tr>
                              <th>DESCRIPCION</th>
                              <th>UNIDAD</th>
                              <th>CANTIDAD</th>
                              <th>KILOS</th>
                              <th>COSTO</th>
                              <th>IMPORTE</th>
                              <th>OPC</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $manooInsumos_kilos = $manooInsumos_cantidad = $manooInsumos_importe = 0;
                            if (isset($_POST['manooInsumos_id_unidad'])) {
                              foreach ($_POST['manooInsumos_id_unidad'] as $keyp => $manoinsumos) {
                                $manooInsumos_kilos    += floatval($_POST['manooInsumos_kilos'][$keyp]);
                                $manooInsumos_cantidad += floatval($_POST['manooInsumos_cantidad'][$keyp]);
                                $manooInsumos_importe  += floatval($_POST['manooInsumos_importe'][$keyp]);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="manooInsumos_descripcion[]" value="<?php echo $_POST['manooInsumos_descripcion'][$keyp] ?>" class="span12 manooInsumos_descripcion" required>
                                </td>
                                <td>
                                  <select name="manooInsumos_id_unidad[]" class="span12 manooInsumos_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $_POST['manooInsumos_id_unidad'][$keyp]? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="manooInsumos_cantidad[]" value="<?php echo $_POST['manooInsumos_cantidad'][$keyp] ?>" class="span12 vpositive manooInsumos_cantidad" required></td>
                                <td><input type="text" name="manooInsumos_kilos[]" value="<?php echo $_POST['manooInsumos_kilos'][$keyp] ?>" class="span12 vpositive manooInsumos_kilos" readonly></td>
                                <td><input type="text" name="manooInsumos_costo[]" value="<?php echo $_POST['manooInsumos_costo'][$keyp] ?>" class="span12 vpositive manooInsumos_costo" required></td>
                                <td><input type="text" name="manooInsumos_importe[]" value="<?php echo $_POST['manooInsumos_importe'][$keyp] ?>" class="span12 vpositive manooInsumos_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger manooInsumos_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php }
                            } ?>
                            <?php
                              foreach ($caja['manooInsumos'] as $manoinsumos) {
                                $manooInsumos_kilos    += floatval($manoinsumos->kilos);
                                $manooInsumos_cantidad += floatval($manoinsumos->cantidad);
                                $manooInsumos_importe  += floatval($manoinsumos->importe);
                            ?>
                              <tr>
                                <td>
                                  <input type="text" name="manooInsumos_descripcion[]" value="<?php echo $manoinsumos->descripcion ?>" class="span12 manooInsumos_descripcion" required>
                                </td>
                                <td>
                                  <select name="manooInsumos_id_unidad[]" class="span12 manooInsumos_id_unidad" required>
                                    <?php foreach ($unidades as $key => $u) { ?>
                                      <option value="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo ($u->id_unidad == $manoinsumos->id_unidad? 'selected': '') ?>><?php echo $u->nombre ?></option>
                                    <?php } ?>
                                  </select>
                                </td>
                                <td><input type="text" name="manooInsumos_cantidad[]" value="<?php echo $manoinsumos->cantidad ?>" class="span12 vpositive manooInsumos_cantidad" required></td>
                                <td><input type="text" name="manooInsumos_kilos[]" value="<?php echo $manoinsumos->kilos ?>" class="span12 vpositive manooInsumos_kilos" readonly></td>
                                <td><input type="text" name="manooInsumos_costo[]" value="<?php echo $manoinsumos->costo ?>" class="span12 vpositive manooInsumos_costo" required></td>
                                <td><input type="text" name="manooInsumos_importe[]" value="<?php echo $manoinsumos->importe ?>" class="span12 vpositive manooInsumos_importe" readonly></td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger manooInsumos_del" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                </td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th></th>
                              <th id="manooInsumosCantidad"><?php echo $manooInsumos_cantidad ?></th>
                              <th id="manooInsumosKilos"><?php echo $manooInsumos_kilos ?></th>
                              <th colspan="2"></th>
                              <th id="manooInsumosImporte"><?php echo $manooInsumos_importe ?></th>
                              <th></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ MANO DE OBRA E INSUMOS -->

                    <!-- ESTIMACION DE PRECIOS -->
                    <div class="row-fluid">
                      <div class="span4" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-estima-precio">
                          <thead>
                            <tr>
                              <th colspan="2">ESTIMACION DE PRECIOS</th>
                            </tr>
                            <tr>
                              <th>CALIBRE</th>
                              <th>IMPORTE</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (isset($caja_data['tabla_rendimientos']) && count($caja_data['tabla_rendimientos']) > 0): ?>
                            <?php foreach ($caja_data['tabla_rendimientos'] as $estimaprecio): ?>
                              <tr>
                                <td><?php echo $estimaprecio['calibre'] ?>
                                  <input type="hidden" name="estimaprecio_id_calibre[]" value="<?php echo $estimaprecio['id_calibre'] ?>" class="span12 estimaprecio_id_calibre">
                                  <input type="hidden" name="estimaprecio_calibre[]" value="<?php echo $estimaprecio['calibre'] ?>" class="span12 estimaprecio_calibre">
                                  <input type="hidden" name="estimaprecio_cantidad[]" value="<?php echo $estimaprecio['cantidad'] ?>" class="span12 estimaprecio_cantidad">
                                  <input type="hidden" name="estimaprecio_kilos[]" value="<?php echo $estimaprecio['kilos'] ?>" class="span12 estimaprecio_kilos">
                                </td>
                                <td><input type="text" name="estimaprecio_importe[]" value="<?php echo (isset($estimaprecio['importe'])? $estimaprecio['importe']: '') ?>" class="span12 vpositive estimaprecio_importe" required></td>
                              </tr>
                            <?php endforeach ?>
                            <?php endif ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ ESTIMACION DE PRECIOS -->

                </div>
              </div>
            </div>

          </div>
        </form>
      </div>

    </div><!--/#content.span10-->
  </div><!--/fluid-row-->

  <div class="clear"></div>

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
          <input type="hidden" id="accion_catalogos_tipo" value="gasto">
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
            <label class="control-label" for="rancho">Areas / Ranchos / Lineas </label>
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

  <!-- Modal -->
  <div id="modalAreas" class="modal modal-w70 hide fade" tabindex="-1" role="dialog" aria-labelledby="modalAreasLavel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="modalAreasLavel">Catalogo de maquinaria, equipos e instalaciones</h3>
    </div>
    <div class="modal-body">

      <div class="row-fluid">

        <div>

      <?php foreach ($areas as $key => $value)
      { ?>
          <div class="span3" id="tblAreasDiv<?php echo $value->id_tipo ?>" style="display: none;">
            <table class="table table-hover table-condensed <?php echo ($key==0? 'tblAreasFirs': ''); ?>"
                id="tblAreas<?php echo $value->id_tipo ?>" data-id="<?php echo $value->id_tipo ?>">
              <thead>
                <tr>
                  <th style="width:10px;"></th>
                  <th>Codigo</th>
                  <th><?php echo $value->nombre ?></th>
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