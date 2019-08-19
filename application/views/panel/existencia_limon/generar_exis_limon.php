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

                  <?php if (isset($caja['status']) && $caja['status'] === 'f') { ?>
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
                              <th colspan="4">VENTAS</th>
                              <th colspan="5" id="dvfondo_caja"></th>
                            </tr>
                            <tr>
                              <!-- <th>FECHA</th> -->
                              <th>FOLIO</th>
                              <th>CLIENTE</th>
                              <th>CLASIF</th>
                              <th>UNIDAD</th>
                              <th>CANTIDAD</th>
                              <th>PRECIO</th>
                              <th>IMPORTE</th>
                              <th></th>
                            </tr>
                          </thead>
                          <tbody style="overflow-y: auto;max-height: 300px;">
                            <?php
                                $venta_importe = 0;
                                  foreach ($caja['ventas'] as $venta) {
                                      $venta_importe += floatval($venta->importe);
                                    ?>
                                    <tr>
                                      <td><?php echo $venta->serie.$venta->folio ?></td>
                                      <td><?php echo $venta->nombre_fiscal ?></td>
                                      <td><?php echo $venta->clasificacion ?></td>
                                      <td><?php echo $venta->unidad ?></td>
                                      <td><?php echo $venta->cantidad ?></td>
                                      <td><?php echo $venta->precio ?></td>
                                      <td><?php echo $venta->importe ?></td>
                                    </tr>
                            <?php } ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                    <!--/ Ventas -->

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
                              <th colspan="6">PRODUCCION</th>
                            </tr>
                            <tr>
                              <th>COSTO X ENVACE</th>
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
                                  <input type="hidden" name="produccion_id_clasificacion[]" value="<?php echo $produccion->id_clasificacion ?>" id="produccion_id_clasificacion" class="input-small vpositive">
                                  <input type="hidden" name="produccion_id_unidad[]" value="<?php echo $produccion->id_unidad ?>" class="input-small produccion_id_unidad vpositive gasto-cargo-id">
                                  <!-- <input type="hidden" name="produccion_kilos[]" value="<?php echo $produccion->kilos ?>" class="input-small produccion_kilos vpositive gasto-cargo-id"> -->
                                  <!-- <input type="hidden" name="produccion_cantidad[]" value="<?php echo $produccion->cantidad ?>" class="input-small tproduccion_cantidad vpositive gasto-cargo-id"> -->
                                  <input type="hidden" name="produccion_importe[]" value="<?php echo $produccion->importe ?>" class="input-small tproduccion_importe vpositive gasto-cargo-id">
                                </td>
                                <td><?php echo $produccion->clasificacion ?></td>
                                <td><?php echo $produccion->unidad ?></td>
                                <td><?php echo $produccion->kilos ?></td>
                                <td class="produccion_cantidad"><?php echo $produccion->cantidad ?></td>
                                <td class="produccion_importe"><?php echo $produccion->importe ?></td>
                              </tr>
                            <?php } ?>

                            <tr>
                              <th colspan="3"></th>
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