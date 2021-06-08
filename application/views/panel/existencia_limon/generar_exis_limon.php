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
                    <div class="span4"><a href="<?php echo base_url('panel/existencias_limon/print_caja?'.MyString::getVarsLink(array('msg'))) ?>" class="btn btn-success btn-large span12" target="_blank">Imprimir</a></div>
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

                    <!-- Existencia -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-existencia">
                          <thead>
                            <tr>
                              <th colspan="6">EXISTENCIA EMPACADA</th>
                            </tr>
                            <tr>
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
                                  <input type="hidden" name="existencia_costo[] " value="<?php echo $existencia->costo ?>">
                                  <input type="hidden" name="existencia_kilos[]" value="<?php echo $existencia->kilos ?>">
                                  <input type="hidden" name="existencia_cantidad[]" value="<?php echo $existencia->cantidad ?>">
                                  <input type="hidden" name="existencia_importe[]" value="<?php echo $existencia->importe ?>">
                                </td>
                                <td><?php echo $existencia->unidad ?></td>
                                <td><?php echo $existencia->kilos ?></td>
                                <td class="existencia_cantidad"><?php echo $existencia->cantidad ?></td>
                                <td class="existencia_importe"><?php echo $existencia->importe ?></td>
                              </tr>
                            <?php } ?>

                            <tr>
                              <th colspan="3"></th>
                              <th><?php echo $existencia_kilos ?></th>
                              <th><?php echo $existencia_cantidad ?></th>
                              <th><?php echo $existencia_importe ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Existencia -->

                    <!-- Existencia de piso -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-existencia-piso">
                          <thead>
                            <tr>
                              <th colspan="5">EXISTENCIA DE PISO</th>
                              <th>
                                <button type="button" class="btn btn-success" id="btnAddExisPiso"><i class="icon-plus"></i></button>
                                <input type="hidden" id="unidades" value='<?php echo json_encode($unidades) ?>'>
                              </th>
                            </tr>
                            <tr>
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
                              <th></th>
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
                              <th></th>
                              <th id="exisReproImporte"><?php echo $existenciaReProceso_importe ?></th>
                              <th></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Existencia de reproceso -->

                    <!-- Compra de fruta -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-prestamolp">
                          <thead>
                            <tr>
                              <th colspan="4">COMPRA DE LIMON</th>
                            </tr>
                            <tr>
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
                    <!--/ Compra de fruta -->

                    <!-- Produccion -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-produccion">
                          <thead>
                            <tr>
                              <th colspan="7">PRODUCCION</th>
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
                    <!--/ Produccion -->

                    <!-- Existencia Anterior -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-existencia_ant">
                          <thead>
                            <tr>
                              <th colspan="6">EXISTENCIA ANTERIOR</th>
                            </tr>
                            <tr>
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
                                <td class="existencia_ant_importe"><?php echo $existencia_ant->importe ?></td>
                              </tr>
                            <?php } ?>

                            <tr>
                              <th colspan="3"></th>
                              <th><?php echo $existencia_ant_kilos ?></th>
                              <th><?php echo $existencia_ant_cantidad ?></th>
                              <th><?php echo $existencia_ant_importe ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Existencia Anterior -->

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
                              <th colspan="3">FLETES</th>
                            </tr>
                            <tr>
                              <th>NOMBRE</th>
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
                                <td>
                                  <?php echo $desc->descripcion ?>
                                </td>
                                <td>
                                  <?php echo $desc->cantidad ?>
                                </td>
                                <td><?php echo $desc->importe ?></td>
                              </tr>
                            <?php } ?>

                            <tr class="footer">
                              <th></th>
                              <th><?php echo $descuentoVentasFletes_cantidad ?></th>
                              <th><?php echo $descuentoVentasFletes_importe ?></th>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Costo de venta -->

                    <!-- Comisiones a terceros -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;">
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
  <div id="addPrestamosCp" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <form class="form-horizontal" action="<?php echo base_url();?>panel/existencias_limon/abono_prestamo_cp" method="POST" id="frmcajachicapres" name="frmcajachicapres">
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