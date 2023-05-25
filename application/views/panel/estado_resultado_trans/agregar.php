<div id="content" class="span10">
<style type="text/css">
  #lts_precios {
    margin-top: 10px;
    clear: both;
  }
  span.rowltsp {
    background-color: #ddd;
    padding: 5px 8px;
    border-radius: 7px;
    cursor: not-allowed;
    margin-right: 5px;
    margin-top: 5px;
  }
  input[type=text], input[type=number] {
    width: auto;
  }
</style>

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/estado_resultado_trans/'); ?>">Estado de Resultados</a> <span class="divider">/</span>
      </li>
      <li>Agregar</li>
    </ul>
  </div>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> <?php echo (isset($_GET['id_nrc'])? 'Agregar Nota de credito': 'Agregar Venta de Remisión') ?></h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">
    <?php if(isset($_GET['id_nrc'])){ ?>
        <span id="isNotaCredito"></span>
    <?php } ?>

        <form class="form-horizontal" action="<?php echo base_url('panel/estado_resultado_trans/agregar/'.$getId.(isset($_GET['id_nr'])? '?id_nr='.$_GET['id_nr']:'')); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="dempresa">Empresa</label>
                <div class="controls">

                  <input type="text" name="dempresa" class="span9" id="dempresa" value="<?php echo set_value('dempresa', isset($borrador) ? $borrador['info']->empresa->nombre_fiscal : $empresa_default->nombre_fiscal); ?>" size="73" autofocus>
                  <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value('did_empresa', isset($borrador) ? $borrador['info']->empresa->id_empresa : $empresa_default->id_empresa); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dactivo">Activo</label>
                <div class="controls">
                  <input type="text" name="dactivo" class="span9" id="dactivo" value="<?php echo set_value('dactivo', isset($borrador) ? $borrador['info']->activo->nombre : ''); ?>" size="73">
                  <input type="hidden" name="did_activo" id="did_activo" value="<?php echo set_value('did_activo', isset($borrador) ? $borrador['info']->activo->id_producto : ''); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dfolio">Folio</label>
                <div class="controls">
                  <input type="number" name="dfolio" class="span9 nokey" id="dfolio" value="<?php echo isset($_POST['dfolio']) ? $_POST['dfolio'] : (isset($borrador)? $borrador['info']->folio: ''); ?>" size="15" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dchofer">Chofer</label>
                <div class="controls">
                  <input type="text" name="dchofer" class="span9" id="dchofer" value="<?php echo set_value('dchofer', isset($borrador) ? $borrador['info']->chofer->nombre : ''); ?>" size="73">
                  <input type="hidden" name="did_chofer" id="did_chofer" value="<?php echo set_value('did_chofer', isset($borrador) ? $borrador['info']->chofer->id_chofer : ''); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dkm_rec">Km Recorrido</label>
                <div class="controls">
                  <input type="text" name="dkm_rec" class="span9" id="dkm_rec" value="<?php echo set_value('dkm_rec', isset($borrador) ? $borrador['info']->km_rec : ''); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dvel_max">Velocidad Max</label>
                <div class="controls">
                  <input type="text" name="dvel_max" class="span9" id="dvel_max"
                    value="<?php echo set_value('dvel_max', isset($borrador) ? $borrador['info']->vel_max : ''); ?>" placeholder="">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="drep_lt_hist">Reposición Lt/Hist</label>
                <div class="controls">
                  <input type="text" name="drep_lt_hist" class="span9" id="drep_lt_hist"
                    value="<?php echo set_value('drep_lt_hist', isset($borrador) ? $borrador['info']->rep_lt_hist : ''); ?>" placeholder="">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="destino">Destino</label>
                <div class="controls">
                  <input type="text" name="destino" class="span9" id="destino"
                    value="<?php echo set_value('destino', isset($borrador) ? $borrador['info']->destino : ''); ?>" placeholder="">
                </div>
              </div>

            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="dfecha">Fecha</label>
                <div class="controls">
                  <input type="date" name="dfecha" class="span9" id="dfecha" value="<?php echo set_value('dfecha', isset($borrador) ? $borrador['info']->fecha : $fecha); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dfecha_viaje">Fecha Viaje</label>
                <div class="controls">
                  <input type="date" name="dfecha_viaje" class="span9" id="dfecha_viaje" value="<?php echo set_value('dfecha_viaje', isset($borrador) ? $borrador['info']->fecha_viaje : $fecha); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="rend_km_gps">Rend. Km/Gps</label>
                <div class="controls">
                  <input type="text" name="rend_km_gps" class="span9" id="rend_km_gps" value="<?php echo set_value('rend_km_gps', isset($borrador) ? $borrador['info']->rend_km_gps : ''); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="rend_actual">Rend. Actual</label>
                <div class="controls">
                  <input type="text" name="rend_actual" class="span9" id="rend_actual" value="<?php echo set_value('rend_actual', isset($borrador) ? $borrador['info']->rend_actual : ''); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="rend_lts">Diesel Lts</label>
                <div class="controls">
                  <input type="text" name="rend_lts" class="span9" id="rend_lts" value="" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="rend_precio">Diesel Precio</label>
                <div class="controls">
                  <input type="text" name="rend_precio" class="input-xlarge" id="rend_precio" value="" size="25">
                  <span class="help-inline">
                    <button type="button" class="btn" id="btnAddLtsPrecios">+</button>
                  </span>
                </div>

                <?php
                $ltsps = isset($borrador) ? $borrador['info']->lts_precios : [];
                ?>
                <div id="lts_precios">
                  <?php foreach ($ltsps as $key => $value): ?>
                  <span class="rowltsp">Lts: <?php echo $value->rend_lts ?> | Precio: <?php echo $value->rend_precio ?>
                    <input type="hidden" name="arend_lts[]" value="<?php echo $value->rend_lts ?>">
                    <input type="hidden" name="arend_precio[]" value="<?php echo $value->rend_precio ?>">
                  </span>
                  <?php endforeach ?>
                </div>
              </div>


              <div class="control-group">
                <label class="control-label" for="rend_thrs_trab">Termo Hrs Trabajadas</label>
                <div class="controls">
                  <input type="text" name="rend_thrs_trab" class="span9" id="rend_thrs_trab" value="<?php echo set_value('rend_thrs_trab', isset($borrador) ? $borrador['info']->rend_thrs_trab : ''); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="rend_thrs_lts">Termo Lts</label>
                <div class="controls">
                  <input type="text" name="rend_thrs_lts" class="span9" id="rend_thrs_lts" value="<?php echo set_value('rend_thrs_lts', isset($borrador) ? $borrador['info']->rend_thrs_lts : ''); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="rend_thrs_hxl">Termo Hrs/Lts</label>
                <div class="controls">
                  <input type="text" name="rend_thrs_hxl" class="span9" id="rend_thrs_hxl" value="<?php echo set_value('rend_thrs_hxl', isset($borrador) ? $borrador['info']->rend_thrs_hxl : ''); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <a href="#modal-gastoscaja" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-gastocaja" style="padding: 2px 7px 2px;">Gasto Caja</a>
                  <input type="text" name="gasto_monto" id="gasto_monto" value="<?php echo set_value('gasto_monto', isset($borrador) ? $borrador['info']->gasto_monto : ''); ?>" readonly>
                  <input type="hidden" name="did_gasto" id="did_gasto" value="<?php echo set_value('did_gasto', isset($borrador) ? $borrador['info']->id_gasto : ''); ?>">
                  <button type="button" class="btn" id="btn-gastocaja-clear" title="Borrar anticipos">
                    <i class="icon-minus-sign"></i>
                  </button>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <button type="submit" class="btn btn-success">Guardar</button>
                </div>
              </div>

            </div>
          </div>

          <div class="row-fluid">
            <?php $totalIngresosRemisiones = 0; ?>
            <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">OTROS <button type="button" class="btn btn-success" id="btn-add-otros" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
            <div class="span12" style="margin-top: 1px;">
              <table class="table table-striped table-bordered table-hover table-condensed" id="table-remisiones">
                <thead>
                  <tr>
                    <th colspan="8">VENTAS
                      <a href="#modal-remisiones" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-remisiones" style="padding: 2px 7px 2px; float: right;">Remisiones</a>
                    </th>
                  </tr>
                  <tr>
                    <th>FECHA</th>
                    <th>FOLIO</th>
                    <th colspan="3">CLIENTE</th>
                    <!-- <th>CONCEPTO</th>
                    <th>CANTIDAD</th>
                    <th>PRECIO</th> -->
                    <th>IMPORTE</th>
                    <th>COMPRO</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    if (isset($_POST['remision_cliente'])) {
                      foreach ($_POST['remision_cliente'] as $key => $cliente) {
                        // $totalIngresosRemisiones += floatval($_POST['otros_monto'][$key]);
                      ?>
                        <tr>
                          <td style=""><input type="date" name="remision_fecha[]" value="<?php echo $_POST['remision_fecha'][$key] ?>" class="remision_fecha" placeholder="fecha" readonly></td>
                          <td style=""><input type="text" name="remision_numero[]" value="<?php echo $_POST['remision_numero'][$key] ?>" class="remision-numero vpositive " placeholder="" readonly readonly></td>
                          <td colspan="3">
                            <input type="text" name="remision_cliente[]" value="<?php echo $cliente ?>" class="remision-cliente span12" maxlength="500" placeholder="cliente" required readonly>
                            <input type="hidden" name="remision_id[]" value="<?php echo $_POST['remision_id'][$key] ?>" class="remision-id span12" required>
                            <input type="hidden" name="remision_row[]" value="" class="vpositive remision_row">
                          </td>
                          <td style=""><input type="number" step="any" name="remision_importe[]" value="<?php echo $_POST['remision_importe'][$key] ?>" class="remision-importe vpositive " placeholder="Importe" required readonly></td>
                          <td style="">
                            <input type="checkbox" value="true" class="chkcomprobacion" <?php echo ($_POST['remision_comprobacion'][$key] == 'true'? 'checked': '') ?>>
                            <input type="hidden" name="remision_comprobacion[]" value="<?php echo $_POST['remision_comprobacion'][$key] ?>" class="valcomprobacion">
                          </td>
                          <td style="width: 30px;">
                            <button type="button" class="btn btn-danger btn-del-remision" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                            <input type="hidden" name="remision_del[]" value="" id="remision_del">
                          </td>
                        </tr>
                    <?php }} elseif(isset($borrador['remisiones'])) {
                        foreach ($borrador['remisiones'] as $remision) {
                          // $totalIngresosRemisiones += floatval($otro->monto);
                        ?>
                          <tr>
                            <td style=""><input type="date" name="remision_fecha[]" value="<?php echo $remision->fecha ?>" class="remision_fecha" placeholder="fecha" readonly></td>
                            <td style=""><input type="text" name="remision_numero[]" value="<?php echo $remision->folio ?>" class="remision-numero vpositive " placeholder="" readonly style="" readonly></td>
                            <td colspan="3">
                              <input type="text" name="remision_cliente[]" value="<?php echo $remision->cliente ?>" class="remision-cliente span12" maxlength="500" placeholder="cliente" required readonly>
                              <input type="hidden" name="remision_id[]" value="<?php echo $remision->id_remision ?>" class="remision-id span12" required>
                              <input type="hidden" name="remision_row[]" value="" class="vpositive remision_row">
                            </td>
                            <td style=""><input type="number" step="any" name="remision_importe[]" value="<?php echo $remision->subtotal ?>" class="remision-importe vpositive " placeholder="Importe" required readonly></td>
                            <td style="">
                              <input type="checkbox" value="true" class="chkcomprobacion" <?php echo (isset($remision->comprobacion) && $remision->comprobacion == 't'? 'checked': '') ?>>
                              <input type="hidden" name="remision_comprobacion[]" value="<?php echo (isset($remision->comprobacion) && $remision->comprobacion == 't'? 'true': '') ?>" class="valcomprobacion">
                            </td>
                            <td style="width: 30px;">
                              <button type="button" class="btn btn-danger btn-del-remision" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                              <input type="hidden" name="remision_del[]" value="" id="remision_del">
                            </td>
                          </tr>
                  <?php }} ?>

                  <?php if (isset($_POST['remision_cliente'])) {
                    foreach ($_POST['remision_cliente'] as $key => $remision) {
                        $totalIngresosRemisiones += floatval($_POST['remision_importe'][$key]);
                      ?>
                  <?php }} elseif(isset($caja['remisiones'])) {
                    foreach ($caja['remisiones'] as $remision) {
                        $totalIngresosRemisiones += floatval($remision->monto);
                      ?>
                  <?php }} ?>

                  <tr class='row-total'>
                    <td colspan="5"></td>
                    <td style="">
                      <input type="text" name="total_ingresosRemisiones" value="<?php echo MyString::float(MyString::formatoNumero($totalIngresosRemisiones, 2, '')) ?>" class="span12" id="total-ingresosRemisiones" readonly style="text-align: right;">
                    </td>
                    <td></td>
                    <td></td>
                  </tr>

                </tbody>
              </table>
            </div>
          </div>

          <!-- SUELDOS -->
          <?php $totalSueldos = 0; ?>
          <div class="row-fluid" style="margin-top: 5px;">
            <div class="span12">
              <div class="row-fluid">
                <div class="span12">
                  <div class="row-fluid">
                    <!-- <div class="span12" style="background-color: #DADADA; text-align: center; font-weight: bold; min-height: 20px;">GASTOS DEL DIA <button type="button" class="btn btn-success" id="btn-add-gasto" style="padding: 2px 7px 2px;float: right;margin-right: 2px;<?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
                    <div class="row-fluid">
                      <div class="span12" style="margin-top: 1px;overflow-y: auto;max-height: 480px;">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-sueldos">
                          <thead>
                            <tr>
                              <th colspan="6">SUELDOS
                                <button type="button" class="btn btn-success" id="btn-add-sueldos" style="padding: 2px 7px 2px;margin-right: 2px;"><i class="icon-plus"></i></button>
                              </th>
                            </tr>
                            <tr>
                              <th style="width: 15%;">FECHA</th>
                              <th style="width: 30%;">PROVEEDOR</th>
                              <th style="width: 37%;">CONCEPTO</th>
                              <th style="width: 15%;">IMPORTE</th>
                              <th style="width: 15%;">COMPRO</th>
                              <th style="width: 3%;"></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              if (isset($_POST['sueldos_concepto'])) {
                                foreach ($_POST['sueldos_concepto'] as $key => $concepto) {
                                  $totalSueldos += floatval($_POST['sueldos_importe'][$key]); ?>
                                <tr>
                                  <td><input type="date" name="sueldos_fecha[]" value="<?php echo $_POST['sueldos_fecha'][$key] ?>" required></td>
                                  <td>
                                    <input type="hidden" name="sueldos_id_sueldo[]" value="<?php echo $_POST['sueldos_id_sueldo'][$key] ?>" id="sueldos_id_sueldo">
                                    <input type="text" name="sueldos_proveedor[]" value="<?php echo $_POST['sueldos_proveedor'][$key] ?>" class="span12 autproveedor" required>
                                    <input type="hidden" name="sueldos_proveedor_id[]" value="<?php echo $_POST['sueldos_proveedor_id'][$key] ?>" class="span12 vpositive autproveedor-id">
                                  </td>
                                  <td style="">
                                    <input type="text" name="sueldos_concepto[]" value="<?php echo $_POST['sueldos_concepto'][$key] ?>" class="span12 sueldos-concepto" required>
                                  </td>
                                  <td style="width: 60px;"><input type="text" name="sueldos_importe[]" value="<?php echo $_POST['sueldos_importe'][$key] ?>" class="span12 vpositive sueldos-importe" required></td>
                                  <td style="">
                                    <input type="checkbox" value="true" class="chkcomprobacion" <?php echo ($_POST['sueldos_comprobacion'][$key] == 'true'? 'checked': '') ?>>
                                    <input type="hidden" name="sueldos_comprobacion[]" value="<?php echo $_POST['sueldos_comprobacion'][$key] ?>" class="valcomprobacion">
                                  </td>
                                  <td style="width: 30px;">
                                    <button type="button" class="btn btn-danger btn-del-sueldos" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                    <input type="hidden" name="sueldos_del[]" value="<?php echo $_POST['sueldos_del'][$key] ?>" id="sueldos_del">
                                  </td>
                                </tr>
                            <?php }} else {
                              if (isset($borrador['sueldos']))
                              foreach ($borrador['sueldos'] as $sueldo) {
                                $totalSueldos += floatval($sueldo->importe);
                              ?>
                              <tr>
                                <td><input type="date" name="sueldos_fecha[]" value="<?php echo $sueldo->fecha ?>" required></td>
                                <td>
                                  <input type="hidden" name="sueldos_id_sueldo[]" value="<?php echo $sueldo->id ?>" id="sueldos_id_sueldo">
                                  <input type="text" name="sueldos_proveedor[]" value="<?php echo $sueldo->proveedor ?>" class="span12 autproveedor" required>
                                  <input type="hidden" name="sueldos_proveedor_id[]" value="<?php echo $sueldo->id_proveedor ?>" class="span12 vpositive autproveedor-id">
                                </td>
                                <td style="">
                                  <input type="text" name="sueldos_concepto[]" value="<?php echo $sueldo->descripcion ?>" class="span12 sueldos-concepto" required>
                                </td>
                                <td style="width: 60px;"><input type="text" name="sueldos_importe[]" value="<?php echo $sueldo->importe ?>" class="span12 vpositive sueldos-importe" required></td>
                                <td style="">
                                  <input type="checkbox" value="true" class="chkcomprobacion" <?php echo (isset($sueldo->comprobacion) && $sueldo->comprobacion == 't'? 'checked': '') ?>>
                                  <input type="hidden" name="sueldos_comprobacion[]" value="<?php echo (isset($sueldo->comprobacion) && $sueldo->comprobacion == 't'? 'true': '') ?>" class="valcomprobacion">
                                </td>
                                <td style="width: 30px;">
                                  <button type="button" class="btn btn-danger btn-del-sueldos" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                  <input type="hidden" name="sueldos_del[]" value="" id="sueldos_del">
                                </td>
                              </tr>
                            <?php }} ?>
                            <tr class="row-total">
                              <td colspan="3" style="text-align: right; font-weight: bolder;">TOTAL</td>
                              <td><input type="text" value="<?php echo $totalSueldos ?>" class="vpositive" id="ttotal-sueldos" style="text-align: right;" readonly></td>
                              <td colspan="2"></td>
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
          <!-- /SUELDOS -->

          <div class="row-fluid">
            <?php $totalRepMantSub = $totalRepMantIva = $totalRepMantTot = 0; ?>
            <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">OTROS <button type="button" class="btn btn-success" id="btn-add-otros" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
            <div class="span12" style="margin-top: 1px;">
              <table class="table table-striped table-bordered table-hover table-condensed" id="table-repmant">
                <thead>
                  <tr>
                    <th colspan="9">REP Y MTTO DE EQUIPO TRASPORTE
                      <button type="button" class="btn btn-success" id="btn-add-repmant" style="padding: 2px 7px 2px;margin-right: 2px;"><i class="icon-plus"></i></button>
                      <a href="#" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-repmant" style="padding: 2px 7px 2px; float: right;">Gastos</a>
                    </th>
                  </tr>
                  <tr>
                    <th>FECHA</th>
                    <th>FOLIO</th>
                    <th>PROVEEDOR</th>
                    <th>DESCRIPCION</th>
                    <th>SUBTOTAL</th>
                    <th>IVA</th>
                    <th>IMPORTE</th>
                    <th>COMPRO</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    if (isset($_POST['repmant_concepto'])) {
                      foreach ($_POST['repmant_concepto'] as $key => $concepto) {
                        // $totalRepMant += floatval($_POST['otros_monto'][$key]);
                        $readonly = $_POST['repmant_id'][$key] > 0? 'readonly': '';
                      ?>
                        <tr>
                          <td style=""><input type="date" name="repmant_fecha[]" value="<?php echo $_POST['repmant_fecha'][$key] ?>" class="repmant_fecha span12" placeholder="Fecha" <?php echo $readonly ?>></td>
                          <td style=""><input type="text" name="repmant_numero[]" value="<?php echo $_POST['repmant_numero'][$key] ?>" class="repmant-numero span12 vpositive" placeholder="" <?php echo $readonly ?> style=""></td>
                          <td>
                            <input type="text" name="repmant_proveedor[]" value="<?php echo $_POST['repmant_proveedor'][$key] ?>" class="repmant-proveedor" maxlength="500" placeholder="Nombre" required <?php echo $readonly ?>>
                            <input type="hidden" name="repmant_id[]" value="<?php echo $_POST['repmant_id'][$key] ?>" class="repmant-id span12" required>
                            <input type="hidden" name="repmant_row[]" value="" class="input-small vpositive repmant_row">
                          </td>
                          <td style=""><input type="text" name="repmant_concepto[]" value="<?php echo $_POST['repmant_concepto'][$key] ?>" class="repmant-concepto" placeholder="Concepto" <?php echo $readonly ?>></td>
                          <td style=""><input type="number" step="any" name="repmant_subtotal[]" value="<?php echo $_POST['repmant_subtotal'][$key] ?>" class="repmant-subtotal vpositive" placeholder="Subtotal" required <?php echo $readonly ?>></td>
                          <td style=""><input type="number" step="any" name="repmant_iva[]" value="<?php echo $_POST['repmant_iva'][$key] ?>" class="repmant-iva vpositive" placeholder="Iva" required <?php echo $readonly ?>></td>
                          <td style=""><input type="number" step="any" name="repmant_importe[]" value="<?php echo $_POST['repmant_importe'][$key] ?>" class="repmant-importe vpositive" placeholder="Importe" required <?php echo $readonly ?>></td>
                          <td style="">
                            <input type="checkbox" value="true" class="chkcomprobacion" <?php echo ($_POST['repmant_comprobacion'][$key] == 'true'? 'checked': '') ?>>
                            <input type="hidden" name="repmant_comprobacion[]" value="<?php echo $_POST['repmant_comprobacion'][$key] ?>" class="valcomprobacion">
                          </td>
                          <td style="width: 30px;">
                            <button type="button" class="btn btn-danger btn-del-repmant" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                            <input type="hidden" name="repmant_del[]" value="" id="repmant_del">
                          </td>
                        </tr>
                    <?php }} elseif(isset($borrador['repmant'])) {
                        foreach ($borrador['repmant'] as $repmant) {
                          // $totalRepMant += floatval($otro->monto);
                          $readonly = $repmant->id_compra > 0? 'readonly': '';
                        ?>
                          <tr>
                            <td style=""><input type="date" name="repmant_fecha[]" value="<?php echo $repmant->fecha ?>" class="repmant_fecha span12" placeholder="Fecha" <?php echo $readonly ?>></td>
                            <td style=""><input type="text" name="repmant_numero[]" value="<?php echo $repmant->folio ?>" class="repmant-numero span12 vpositive" placeholder="" <?php echo $readonly ?> style=""></td>
                            <td>
                              <input type="text" name="repmant_proveedor[]" value="<?php echo $repmant->proveedor ?>" class="repmant-proveedor" maxlength="500" placeholder="Nombre" required <?php echo $readonly ?>>
                              <input type="hidden" name="repmant_id[]" value="<?php echo $repmant->id_compra ?>" class="repmant-id span12" required>
                              <input type="hidden" name="repmant_row[]" value="" class="input-small vpositive repmant_row">
                            </td>
                            <td style=""><input type="text" name="repmant_concepto[]" value="<?php echo $repmant->concepto ?>" class="repmant-concepto" placeholder="Concepto" <?php echo $readonly ?>></td>
                            <td style=""><input type="number" step="any" name="repmant_subtotal[]" value="<?php echo $repmant->subtotal ?>" class="repmant-subtotal vpositive" placeholder="Subtotal" required <?php echo $readonly ?>></td>
                            <td style=""><input type="number" step="any" name="repmant_iva[]" value="<?php echo $repmant->importe_iva ?>" class="repmant-iva vpositive" placeholder="Iva" required <?php echo $readonly ?>></td>
                            <td style=""><input type="number" step="any" name="repmant_importe[]" value="<?php echo $repmant->total ?>" class="repmant-importe vpositive" placeholder="Importe" required <?php echo $readonly ?>></td>
                            <td style="">
                              <input type="checkbox" value="true" class="chkcomprobacion" <?php echo (isset($repmant->comprobacion) && $repmant->comprobacion == 't'? 'checked': '') ?>>
                              <input type="hidden" name="repmant_comprobacion[]" value="<?php echo (isset($repmant->comprobacion) && $repmant->comprobacion == 't'? 'true': '') ?>" class="valcomprobacion">
                            </td>
                            <td style="width: 30px;">
                              <button type="button" class="btn btn-danger btn-del-repmant" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                              <input type="hidden" name="repmant_del[]" value="" id="repmant_del">
                            </td>
                          </tr>
                  <?php }} ?>

                  <?php if (isset($_POST['repmant_concepto'])) {
                    foreach ($_POST['repmant_concepto'] as $key => $repmant) {
                        $totalRepMantSub += floatval($_POST['repmant_subtotal'][$key]);
                        $totalRepMantIva += floatval($_POST['repmant_iva'][$key]);
                        $totalRepMantTot += floatval($_POST['repmant_importe'][$key]);
                      ?>
                  <?php }} elseif(isset($borrador['repmant'])) {
                    foreach ($borrador['repmant'] as $repmant) {
                        $totalRepMantSub += floatval($repmant->subtotal);
                        $totalRepMantIva += floatval($repmant->importe_iva);
                        $totalRepMantTot += floatval($repmant->total);
                      ?>
                  <?php }} ?>

                  <tr class='row-total'>
                    <td colspan="4"></td>
                    <td><input type="text" name="total_repmantesub" value="<?php echo MyString::float(MyString::formatoNumero($totalRepMantSub, 2, '')) ?>" class="span12" id="total-repmantsub" readonly style="text-align: right;"></td>
                    <td><input type="text" name="total_repmanteiva" value="<?php echo MyString::float(MyString::formatoNumero($totalRepMantIva, 2, '')) ?>" class="span12" id="total-repmantiva" readonly style="text-align: right;"></td>
                    <td><input type="text" name="total_repmantetot" value="<?php echo MyString::float(MyString::formatoNumero($totalRepMantTot, 2, '')) ?>" class="span12" id="total-repmant" readonly style="text-align: right;"></td>
                    <td colspan="2">
                    </td>
                  </tr>

                </tbody>
              </table>
            </div>
          </div>

          <!-- GASTOS -->
          <?php $totalGastosSubt = $totalGastosIva = $totalGastosTot = 0; ?>
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
                              <th colspan="9">GASTOS
                                <button type="button" class="btn btn-success" id="btn-add-gastos" style="padding: 2px 7px 2px;margin-right: 2px;"><i class="icon-plus"></i></button>
                                <a href="#" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-gastos" style="padding: 2px 7px 2px; float: right;">Gastos</a>
                              </th>
                            </tr>
                            <tr>
                              <th style="width: 12%;">FECHA</th>
                              <th style="width: 12%;">FOLIO</th>
                              <th style="width: 25%;">PROVEEDOR</th>
                              <th style="width: 25%;">CONCEPTO</th>
                              <th style="width: 12%;">SUBTOTAL</th>
                              <th style="width: 12%;">IVA</th>
                              <th style="width: 12%;">IMPORTE</th>
                              <th style="width: 3%;">COMPRO</th>
                              <th style="width: 3%;"></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              if (isset($_POST['gastos_proveedor'])) {
                                foreach ($_POST['gastos_proveedor'] as $key => $concepto) {
                                  $totalGastosSubt += floatval($_POST['gastos_subtotal'][$key]);
                                  $totalGastosIva += floatval($_POST['gastos_iva'][$key]);
                                  $totalGastosTot += floatval($_POST['gastos_importe'][$key]);
                            ?>
                                <tr>
                                  <td><input type="date" name="gastos_fecha[]" value="<?php echo $_POST['gastos_fecha'][$key] ?>" required></td>
                                  <td><input type="text" name="gastos_folio[]" value="<?php echo $_POST['gastos_folio'][$key] ?>"></td>
                                  <td>
                                    <input type="hidden" name="gastos_id_compra[]" value="<?php echo $_POST['gastos_id_compra'][$key] ?>" id="gastos_id_compra">
                                    <input type="hidden" name="gastos_id_gasto[]" value="<?php echo $_POST['gastos_id_gasto'][$key] ?>" id="sueldos_id_sueldo">
                                    <input type="text" name="gastos_proveedor[]" value="<?php echo $_POST['gastos_proveedor'][$key] ?>" class="span12 autproveedor" required>
                                    <input type="hidden" name="gastos_proveedor_id[]" value="<?php echo $_POST['gastos_proveedor_id'][$key] ?>" class="span12 vpositive autproveedor-id">
                                  </td>
                                  <td style="">
                                    <input type="text" name="gastos_codg[]" value="<?php echo $_POST['gastos_codg'][$key] ?>" class="span12 codsgastos" required>
                                    <input type="hidden" name="gastos_codg_id[]" value="<?php echo $_POST['gastos_codg_id'][$key] ?>" class="span12 vpositive codsgastos-id">
                                  </td>
                                  <td style=""><input type="text" name="gastos_subtotal[]" value="<?php echo $_POST['gastos_subtotal'][$key] ?>" class="span12 vpositive gastos-subtotal" required></td>
                                  <td style=""><input type="text" name="gastos_iva[]" value="<?php echo $_POST['gastos_iva'][$key] ?>" class="span12 vpositive gastos-iva" required></td>
                                  <td style=""><input type="text" name="gastos_importe[]" value="<?php echo $_POST['gastos_importe'][$key] ?>" class="span12 vpositive gastos-importe" required></td>
                                  <td style="">
                                    <input type="checkbox" value="true" class="chkcomprobacion" <?php echo ($_POST['gastos_comprobacion'][$key] == 'true'? 'checked': '') ?>>
                                    <input type="hidden" name="gastos_comprobacion[]" value="<?php echo $_POST['gastos_comprobacion'][$key] ?>" class="valcomprobacion">
                                  </td>
                                  <td style="">
                                    <button type="button" class="btn btn-danger btn-del-gastos" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                    <input type="hidden" name="gastos_del[]" value="<?php echo $_POST['gastos_del'][$key] ?>" id="gastos_del">
                                  </td>
                                </tr>
                            <?php }} else {
                              if (isset($borrador['gastos']))
                              foreach ($borrador['gastos'] as $gasto) {
                                $totalGastosSubt += floatval($gasto->subtotal);
                                $totalGastosIva += floatval($gasto->importe_iva);
                                $totalGastosTot += floatval($gasto->total);
                              ?>
                              <tr>
                                <td><input type="date" name="gastos_fecha[]" value="<?php echo $gasto->fecha ?>" class="span12" required></td>
                                <td><input type="text" name="gastos_folio[]" value="<?php echo $gasto->folio ?>" class="span12"></td>
                                <td>
                                  <input type="hidden" name="gastos_id_compra[]" value="<?php echo $gasto->id_compra ?>" id="gastos_id_compra">
                                  <input type="hidden" name="gastos_id_gasto[]" value="<?php echo $gasto->id ?>" id="gastos_id_gasto">
                                  <input type="text" name="gastos_proveedor[]" value="<?php echo $gasto->proveedor ?>" class="span12 autproveedor" required>
                                  <input type="hidden" name="gastos_proveedor_id[]" value="<?php echo $gasto->id_proveedor ?>" class="span12 vpositive autproveedor-id">
                                </td>
                                <td style="">
                                  <input type="text" name="gastos_codg[]" value="<?php echo $gasto->codg ?>" class="span12 codsgastos" required>
                                  <input type="hidden" name="gastos_codg_id[]" value="<?php echo $gasto->id_codg ?>" class="span12 vpositive codsgastos-id">
                                </td>
                                <td style=""><input type="text" name="gastos_subtotal[]" value="<?php echo $gasto->subtotal ?>" class="span12 vpositive gastos-subtotal" required></td>
                                <td style=""><input type="text" name="gastos_iva[]" value="<?php echo $gasto->importe_iva ?>" class="span12 vpositive gastos-iva" required></td>
                                <td style=""><input type="text" name="gastos_importe[]" value="<?php echo $gasto->total ?>" class="span12 vpositive gastos-importe" required></td>
                                <td style="">
                                  <input type="checkbox" value="true" class="chkcomprobacion" <?php echo (isset($gasto->comprobacion) && $gasto->comprobacion == 't'? 'checked': '') ?>>
                                  <input type="hidden" name="gastos_comprobacion[]" value="<?php echo (isset($gasto->comprobacion) && $gasto->comprobacion == 't'? 'true': '') ?>" class="valcomprobacion">
                                </td>
                                <td style="">
                                  <button type="button" class="btn btn-danger btn-del-gastos" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                  <input type="hidden" name="gastos_del[]" value="" id="gastos_del">
                                </td>
                              </tr>
                            <?php }} ?>
                            <tr class="row-total">
                              <td colspan="4" style="text-align: right; font-weight: bolder;">TOTAL</td>
                              <td><input type="text" value="<?php echo $totalGastosSubt ?>" class="input-small vpositive" id="ttotal-gastos" style="text-align: right;" readonly></td>
                              <td><input type="text" value="<?php echo $totalGastosIva ?>" class="input-small vpositive" id="ttotal-gastos" style="text-align: right;" readonly></td>
                              <td><input type="text" value="<?php echo $totalGastosTot ?>" class="input-small vpositive" id="ttotal-gastos" style="text-align: right;" readonly></td>
                              <td colspan="3"></td>
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
          <!-- /GASTOS -->


        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->

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
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="carga-remisiones">Cargar</button>
    </div>
  </div>

  <!-- Modal -->
  <div id="modal-repmant" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 700px;left: 45%;">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Gastos</h3>
    </div>
    <div class="modal-body" style="max-height: 370px;">
      <table id="lista_repmant_modal" class="table table-striped table-bordered table-hover table-condensed">
        <caption></caption>
        <thead>
          <tr>
            <th></th>
            <th>Fecha</th>
            <th>Folio</th>
            <th>Proveedor</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <input type="hidden" id="tipo-repmant" value="repMant">
      <button class="btn btn-primary" id="carga-repmant">Cargar</button>
    </div>
  </div>

  <!-- Modal -->
  <div id="modal-gastoscaja" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 900px;left: 40%;">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Gastos Caja</h3>
    </div>
    <div class="modal-body" style="max-height: 370px;">
      <table id="lista_gastoscaja_modal" class="table table-striped table-bordered table-hover table-condensed">
        <caption></caption>
        <thead>
          <tr>
            <th></th>
            <th>Fecha</th>
            <th>Folio</th>
            <th>Empresa</th>
            <th>Concepto</th>
            <th>Nombre</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="carga-gastoscaja">Cargar</button>
    </div>
  </div>

</div>

<!-- Bloque de alertas -->
<script type="text/javascript" charset="UTF-8">
<?php if (isset($_GET['imprimir_tk']{0})) {
?>
var win = window.open(base_url+'panel/ventas/imprimir_tk/?id=<?php echo $_GET['imprimir_tk']; ?>', '_blank');
if (win)
  win.focus();
else
  noty({"text":"Activa las ventanas emergentes (pop-ups) para este sitio", "layout":"topRight", "type":"error"});
<?php
} ?>
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
  $(document).ready(function(){
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
<?php }
}?>
</script>
<!-- Bloque de alertas -->