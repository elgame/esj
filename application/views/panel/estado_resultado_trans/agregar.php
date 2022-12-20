<div id="content" class="span10">

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
                  <input type="text" name="dactivo" class="span9" id="dactivo" value="<?php echo set_value('dactivo', isset($borrador) ? $borrador['info']->cliente->nombre_fiscal : ''); ?>" size="73">
                  <input type="hidden" name="did_activo" id="did_activo" value="<?php echo set_value('did_activo', isset($borrador) ? $borrador['info']->cliente->id_cliente : ''); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dfolio">Folio</label>
                <div class="controls">
                  <input type="number" name="dfolio" class="span9 nokey" id="dfolio" value="<?php echo isset($_POST['dfolio']) ? $_POST['dfolio'] : (isset($borrador)? $borrador['info']->folio: ''); ?>" size="15" readonly>
                  <input type="hidden" name="dano_aprobacion" id="dano_aprobacion" value="<?php echo set_value('dano_aprobacion'); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dchofer">Chofer</label>
                <div class="controls">
                  <input type="text" name="dchofer" class="span9" id="dchofer" value="<?php echo set_value('dchofer', isset($borrador) ? $borrador['info']->cliente->nombre_fiscal : ''); ?>" size="73">
                  <input type="hidden" name="did_chofer" id="did_chofer" value="<?php echo set_value('did_chofer', isset($borrador) ? $borrador['info']->cliente->id_cliente : ''); ?>">
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

            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="dfecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="dfecha" class="span9" id="dfecha" value="<?php echo set_value('dfecha', isset($borrador) ? $borrador['info']->fecha : $fecha); ?>" size="25">
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
                  <input type="text" name="rend_lts" class="span9" id="rend_lts" value="<?php echo set_value('rend_lts', isset($borrador) ? $borrador['info']->rend_lts : ''); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="rend_precio">Diesel Precio</label>
                <div class="controls">
                  <input type="text" name="rend_precio" class="span9" id="rend_precio" value="<?php echo set_value('rend_precio', isset($borrador) ? $borrador['info']->rend_precio : ''); ?>" size="25">
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
                    <th colspan="10">VENTAS
                      <a href="#modal-remisiones" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-remisiones" style="padding: 2px 7px 2px; float: right;">Remisiones</a>
                    </th>
                  </tr>
                  <tr>
                    <th>FECHA</th>
                    <th>FOLIO</th>
                    <th colspan="3">CLIENTE</th>
                    <th>CONCEPTO</th>
                    <th>CANTIDAD</th>
                    <th>PRECIO</th>
                    <th>IMPORTE</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    if (isset($_POST['remision_concepto'])) {
                      foreach ($_POST['remision_concepto'] as $key => $concepto) {
                        // $totalIngresosRemisiones += floatval($_POST['otros_monto'][$key]);
                      ?>
                        <tr>
                          <td style="">
                            <input type="text" name="remision_empresa[]" value="<?php echo $_POST['remision_empresa'][$key] ?>" class="span12 gasto-cargo" style="" required <?php echo $readonly ?>>
                            <input type="hidden" name="remision_empresa_id[]" value="<?php echo $_POST['remision_empresa_id'][$key] ?>" class="vpositive gasto-cargo-id">
                            <input type="hidden" name="remision_row[]" value="" class="vpositive remision_row">
                          </td>
                          <td style=""><input type="text" name="remision_numero[]" value="<?php echo $_POST['remision_numero'][$key] ?>" class="remision-numero vpositive " placeholder="" readonly style="" <?php echo $readonly ?>></td>
                          <td style=""><input type="date" name="remision_fecha[]" value="<?php echo $_POST['remision_fecha'][$key] ?>" class="remision_fecha" placeholder="fecha" style="" <?php echo $readonly ?>></td>
                          <td style="width: 40px;">
                            <select name="remision_nomenclatura[]" class="remision_nomenclatura" style="width: 70px;" <?php echo $readonly ?>>
                              <?php foreach ($nomenclaturas as $n) { ?>
                                <?php if ($n->tipo === 't'): ?>
                                <option value="<?php echo $n->id ?>" <?php echo $_POST['remision_nomenclatura'][$key] == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                <?php endif ?>
                              <?php } ?>
                            </select>
                          </td>
                          <td colspan="3">
                            <input type="text" name="remision_concepto[]" value="<?php echo $concepto ?>" class="remision-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                            <input type="hidden" name="remision_id[]" value="<?php echo $_POST['remision_id'][$key] ?>" class="remision-id span12" required>
                          </td>
                          <td style=""><input type="number" step="any" name="remision_importe[]" value="<?php echo $_POST['remision_importe'][$key] ?>" class="remision-importe vpositive " placeholder="Importe" required <?php echo $readonly ?>></td>
                          <td style="width: 30px;">
                            <button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                            <input type="hidden" name="remision_del[]" value="" id="remision_del">
                          </td>
                        </tr>
                    <?php }} elseif(isset($caja['remisiones'])) {
                        foreach ($caja['remisiones'] as $remision) {
                          // $totalIngresosRemisiones += floatval($otro->monto);
                        ?>
                          <tr>
                            <td style="">
                              <input type="text" name="remision_empresa[]" value="<?php echo $remision->empresa ?>" class="span12 gasto-cargo" style="" required <?php echo $readonly ?>>
                              <input type="hidden" name="remision_empresa_id[]" value="<?php echo $remision->id_categoria ?>" class="vpositive gasto-cargo-id">
                              <input type="hidden" name="remision_row[]" value="<?php echo $remision->row ?>" class="vpositive remision_row">
                              <a href="<?php echo base_url('panel/caja_chica/print_vale_rm/?fecha='.$remision->fecha.'&id_remision='.$remision->id_remision.'&row='.$remision->row.'&noCaja='.$remision->no_caja)?>" target="_blank" title="Imprimir VALE DE CAJA CHICA">
                                <i class="ico icon-print" style="cursor:pointer"></i></a>
                            </td>
                            <td style=""><input type="text" name="remision_numero[]" value="<?php echo $remision->folio ?>" class="remision-numero vpositive " placeholder="" readonly style="" <?php echo $readonly ?>></td>
                            <td style=""><input type="date" name="remision_fecha[]" value="<?php echo $remision->fecha_rem ?>" class="remision_fecha" placeholder="fecha" style="" <?php echo $readonly ?>></td>
                            <td style="width: 40px;">
                              <select name="remision_nomenclatura[]" class="remision_nomenclatura" style="width: 70px;" <?php echo $readonly.$mod_ing_readonly ?>>
                                <?php foreach ($nomenclaturas as $n) { ?>
                                  <?php if ($n->tipo === 't'): ?>
                                  <option value="<?php echo $n->id ?>" <?php echo $remision->id_nomenclatura == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                  <?php endif ?>
                                <?php } ?>
                              </select>
                            </td>
                            <td colspan="3">
                              <input type="text" name="remision_concepto[]" value="<?php echo $remision->observacion ?>" class="remision-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                              <input type="hidden" name="remision_id[]" value="<?php echo $remision->id_remision ?>" class="remision-id span12" required>
                            </td>
                            <td style=""><input type="number" step="any" name="remision_importe[]" value="<?php echo $remision->monto ?>" class="remision-importe vpositive " placeholder="Importe" required <?php echo $readonly.$readonlyCC ?>></td>
                            <td style="width: 30px;">
                              <?php if (!$cajas_cerradas && $modificar_campos): ?>
                                <button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                              <?php endif ?>
                              <input type="hidden" name="remision_del[]" value="" id="remision_del">
                            </td>
                          </tr>
                  <?php }} ?>

                  <?php if (isset($_POST['remision_concepto'])) {
                    foreach ($_POST['remision_concepto'] as $key => $remision) {
                        $totalIngresosRemisiones += floatval($_POST['remision_importe'][$key]);
                      ?>
                  <?php }} elseif(isset($caja['remisiones'])) {
                    foreach ($caja['remisiones'] as $remision) {
                        $totalIngresosRemisiones += floatval($remision->monto);
                      ?>
                  <?php }} ?>

                  <tr class='row-total'>
                    <td colspan="9"></td>
                    <td style="">
                      <input type="text" name="total_ingresosRemisiones" value="<?php echo MyString::float(MyString::formatoNumero($totalIngresosRemisiones, 2, '')) ?>" class="span12" id="total-ingresosRemisiones" readonly style="text-align: right;">
                    </td>
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
                        <table class="table table-striped table-bordered table-hover table-condensed" id="table-traspasos">
                          <thead>
                            <tr>
                              <th colspan="2">SUELDOS
                                <button type="button" class="btn btn-success" id="btn-add-traspaso" style="padding: 2px 7px 2px;margin-right: 2px;"><i class="icon-plus"></i></button>
                              </th>
                              <th colspan="2"></th>
                              <th colspan="2">IMPORTE</th>
                            </tr>
                            <tr>
                              <th style="width: 15%;">FECHA</th>
                              <th style="width: 30%;">PROVEEDOR</th>
                              <th style="width: 37%;">CONCEPTO</th>
                              <th style="width: 15%;">IMPORTE</th>
                              <th style="width: 3%;"></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              if (isset($_POST['traspaso_concepto'])) {
                                foreach ($_POST['traspaso_concepto'] as $key => $concepto) {
                                  $totalSueldos += ($_POST['traspaso_tipo'][$key] == 't'? 1: -1) * floatval($_POST['traspaso_importe'][$key]); ?>
                                <tr>
                                  <td>
                                    <!-- <select name="traspaso_tipo[]" class="span12 ingreso_nomenclatura" <?php echo $readonly ?>>
                                      <option value="t" <?php echo $_POST['traspaso_tipo'][$key] == 't' ? 'selected' : '' ?>>Ingreso</option>
                                      <option value="f" <?php echo $_POST['traspaso_tipo'][$key] == 'f' ? 'selected' : '' ?>>Egreso</option>
                                    </select> -->
                                    <select name="traspaso_tipo[]" class="span12 traspaso_tipo">
                                      <option value="otros" <?php echo $_POST['traspaso_tipo'][$key]=='otros'? 'selected': ''; ?>>Otros</option>
                                      <option value="caja_limon" <?php echo $_POST['traspaso_tipo'][$key]=='caja_limon'? 'selected': ''; ?>>Caja limón</option>
                                      <option value="caja_gastos" <?php echo $_POST['traspaso_tipo'][$key]=='caja_gastos'? 'selected': ''; ?>>Caja gastos</option>
                                      <option value="caja_fletes" <?php echo $_POST['traspaso_tipo'][$key]=='caja_fletes'? 'selected': ''; ?>>Caja fletes</option>
                                      <option value="caja_plasticos" <?php echo $_POST['traspaso_tipo'][$key]=='caja_plasticos'? 'selected': ''; ?>>Caja Plasticos Gdl</option>
                                      <option value="caja_general" <?php echo $_POST['traspaso_tipo'][$key]=='caja_general'? 'selected': ''; ?>>Caja Distribuidora</option>
                                      <option value="caja_prestamo" <?php echo $_POST['traspaso_tipo'][$key]=='caja_prestamo'? 'selected': ''; ?>>Caja Préstamo</option>
                                    </select>
                                    <input type="hidden" name="traspaso_id_traspaso[]" value="" id="traspaso_id_traspaso">
                                    <input type="hidden" name="traspaso_del[]" value="" id="traspaso_del">
                                  </td>
                                  <td></td>
                                  <td>
                                    <select name="traspaso_afectar_fondo[]" class="span12 traspaso_afectar_fondo">
                                      <option value="f" <?php echo $_POST['traspaso_afectar_fondo'][$key] == 'f' ? 'selected' : '' ?>>No</option>
                                      <option value="t" <?php echo $_POST['traspaso_afectar_fondo'][$key] == 't' ? 'selected' : '' ?>>Si</option>
                                    </select>
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
                                $totalSueldos += ($traspaso->tipo == 't'? 1: -1) * floatval($traspaso->monto);
                              ?>
                              <tr>
                                <td>
                                  <?php echo ucfirst(str_replace('_', ' ', $traspaso->tipo_caja)); ?>
                                  <input type="hidden" name="traspaso_tipo[]" value="<?php echo $traspaso->tipo_caja ?>">
                                  <input type="hidden" name="traspaso_id_traspaso[]" value="<?php echo $traspaso->id_traspaso ?>" id="traspaso_id_traspaso">
                                  <input type="hidden" name="traspaso_del[]" value="" id="traspaso_del">
                                  <a href="<?php echo base_url('panel/caja_chica/print_vale_tras/?id_traspaso='.$traspaso->id_traspaso.'&noCaja='.$traspaso->no_caja)?>" target="_blank" title="Imprimir Gasto comprobar">
                                    <i class="ico icon-print" style="cursor:pointer"></i></a>
                                </td>
                                <td><?php echo $traspaso->tipo == 't' ? 'Ingreso' : 'Egreso' ?></td>
                                <td>
                                  <?php echo $traspaso->afectar_fondo == 't' ? 'Si' : 'No'; ?>
                                  <input type="hidden" name="traspaso_afectar_fondo[]" value="<?php echo $traspaso->afectar_fondo ?>">
                                </td>
                                <td style="">
                                  <?php if ($traspaso->guardado == 't'): ?>
                                  <input type="text" name="traspaso_concepto[]" value="<?php echo $traspaso->concepto ?>" class="span12 traspaso-concepto" <?php echo $readonly ?>>
                                  <?php else: ?>
                                    <input type="hidden" name="traspaso_concepto[]" value="-@-" class="span12 traspaso-concepto" <?php echo $readonly ?>>
                                    <?php echo $traspaso->concepto." (Traspasado de caja No {$traspaso->no_caja})" ?>
                                  <?php endif ?>
                                </td>
                                <td style="width: 60px;">
                                  <?php if ($traspaso->guardado == 't'): ?>
                                  <input type="text" name="traspaso_importe[]" value="<?php echo $traspaso->monto ?>" class="span12 vpositive traspaso-importe" <?php echo $readonly.$readonlyCC ?>>
                                  <?php else: ?>
                                    <input type="hidden" name="traspaso_importe[]" value="<?php echo $traspaso->monto ?>" class="span12 vpositive traspaso-importe" <?php echo $readonly.$readonlyCC ?>>
                                    <?php echo $traspaso->monto ?>
                                  <?php endif ?>
                                </td>
                                <td style="width: 30px;">
                                  <?php if (!$cajas_cerradas && $modificar_campos && $traspaso->guardado == 't'): ?>
                                  <button type="button" class="btn btn-danger btn-del-traspaso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                  <?php endif ?>
                                </td>
                              </tr>
                            <?php }} ?>
                            <tr class="row-total">
                              <td colspan="3" style="text-align: right; font-weight: bolder;">TOTAL</td>
                              <td><input type="text" value="<?php echo $totalSueldos ?>" class="input-small vpositive" id="ttotal-traspasos" style="text-align: right;" readonly></td>
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
          <!-- /SUELDOS -->

          <div class="row-fluid">
            <?php $totalRepMant = 0; ?>
            <!-- <div class="span2" style="font-weight: bold; text-align: center;margin-top: 1px;">OTROS <button type="button" class="btn btn-success" id="btn-add-otros" style="padding: 2px 7px 2px; <?php echo $display ?>"><i class="icon-plus"></i></button></div> -->
            <div class="span12" style="margin-top: 1px;">
              <table class="table table-striped table-bordered table-hover table-condensed" id="table-repmant">
                <thead>
                  <tr>
                    <th colspan="10">REP Y MTTO DE EQUIPO TRASPORTE
                      <a href="#modal-repmant" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-repmant" style="padding: 2px 7px 2px; float: right;">Gastos</a>
                    </th>
                  </tr>
                  <tr>
                    <th>FECHA</th>
                    <th>FOLIO</th>
                    <th colspan="3">PROVEEDOR</th>
                    <th>DESCRIPCION</th>
                    <th>CANTIDAD</th>
                    <th>PRECIO</th>
                    <th>IMPORTE</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                    if (isset($_POST['remision_concepto'])) {
                      foreach ($_POST['remision_concepto'] as $key => $concepto) {
                        // $totalRepMant += floatval($_POST['otros_monto'][$key]);
                      ?>
                        <tr>
                          <td style="">
                            <input type="text" name="repmant_empresa[]" value="<?php echo $_POST['repmant_empresa'][$key] ?>" class="span12 gasto-cargo" style="" required <?php echo $readonly ?>>
                            <input type="hidden" name="repmant_empresa_id[]" value="<?php echo $_POST['repmant_empresa_id'][$key] ?>" class="vpositive gasto-cargo-id">
                            <input type="hidden" name="repmant_row[]" value="" class="vpositive repmant_row">
                          </td>
                          <td style=""><input type="text" name="repmant_numero[]" value="<?php echo $_POST['repmant_numero'][$key] ?>" class="remision-numero vpositive " placeholder="" readonly style="" <?php echo $readonly ?>></td>
                          <td style=""><input type="date" name="repmant_fecha[]" value="<?php echo $_POST['repmant_fecha'][$key] ?>" class="repmant_fecha" placeholder="fecha" style="" <?php echo $readonly ?>></td>
                          <td style="width: 40px;">
                            <select name="repmant_nomenclatura[]" class="repmant_nomenclatura" style="width: 70px;" <?php echo $readonly ?>>
                              <?php foreach ($nomenclaturas as $n) { ?>
                                <?php if ($n->tipo === 't'): ?>
                                <option value="<?php echo $n->id ?>" <?php echo $_POST['repmant_nomenclatura'][$key] == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                <?php endif ?>
                              <?php } ?>
                            </select>
                          </td>
                          <td colspan="3">
                            <input type="text" name="repmant_concepto[]" value="<?php echo $concepto ?>" class="remision-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                            <input type="hidden" name="repmant_id[]" value="<?php echo $_POST['repmant_id'][$key] ?>" class="remision-id span12" required>
                          </td>
                          <td style=""><input type="number" step="any" name="repmant_importe[]" value="<?php echo $_POST['repmant_importe'][$key] ?>" class="remision-importe vpositive " placeholder="Importe" required <?php echo $readonly ?>></td>
                          <td style="width: 30px;">
                            <button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                            <input type="hidden" name="repmant_del[]" value="" id="repmant_del">
                          </td>
                        </tr>
                    <?php }} elseif(isset($caja['remisiones'])) {
                        foreach ($caja['remisiones'] as $remision) {
                          // $totalRepMant += floatval($otro->monto);
                        ?>
                          <tr>
                            <td style="">
                              <input type="text" name="repmant_empresa[]" value="<?php echo $remision->empresa ?>" class="span12 gasto-cargo" style="" required <?php echo $readonly ?>>
                              <input type="hidden" name="repmant_empresa_id[]" value="<?php echo $remision->id_categoria ?>" class="vpositive gasto-cargo-id">
                              <input type="hidden" name="repmant_row[]" value="<?php echo $remision->row ?>" class="vpositive repmant_row">
                              <a href="<?php echo base_url('panel/caja_chica/print_vale_rm/?fecha='.$remision->fecha.'&id_remision='.$remision->id_remision.'&row='.$remision->row.'&noCaja='.$remision->no_caja)?>" target="_blank" title="Imprimir VALE DE CAJA CHICA">
                                <i class="ico icon-print" style="cursor:pointer"></i></a>
                            </td>
                            <td style=""><input type="text" name="repmant_numero[]" value="<?php echo $remision->folio ?>" class="remision-numero vpositive " placeholder="" readonly style="" <?php echo $readonly ?>></td>
                            <td style=""><input type="date" name="repmant_fecha[]" value="<?php echo $remision->fecha_rem ?>" class="repmant_fecha" placeholder="fecha" style="" <?php echo $readonly ?>></td>
                            <td style="width: 40px;">
                              <select name="repmant_nomenclatura[]" class="repmant_nomenclatura" style="width: 70px;" <?php echo $readonly.$mod_ing_readonly ?>>
                                <?php foreach ($nomenclaturas as $n) { ?>
                                  <?php if ($n->tipo === 't'): ?>
                                  <option value="<?php echo $n->id ?>" <?php echo $remision->id_nomenclatura == $n->id ? 'selected' : '' ?>><?php echo $n->nomenclatura ?></option>
                                  <?php endif ?>
                                <?php } ?>
                              </select>
                            </td>
                            <td colspan="3">
                              <input type="text" name="repmant_concepto[]" value="<?php echo $remision->observacion ?>" class="remision-concepto span12" maxlength="500" placeholder="Concepto" required <?php echo $readonly ?>>
                              <input type="hidden" name="repmant_id[]" value="<?php echo $remision->id_remision ?>" class="remision-id span12" required>
                            </td>
                            <td style=""><input type="number" step="any" name="repmant_importe[]" value="<?php echo $remision->monto ?>" class="remision-importe vpositive " placeholder="Importe" required <?php echo $readonly.$readonlyCC ?>></td>
                            <td style="width: 30px;">
                              <?php if (!$cajas_cerradas && $modificar_campos): ?>
                                <button type="button" class="btn btn-danger btn-del-otros" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                              <?php endif ?>
                              <input type="hidden" name="repmant_del[]" value="" id="repmant_del">
                            </td>
                          </tr>
                  <?php }} ?>

                  <?php if (isset($_POST['repmant_concepto'])) {
                    foreach ($_POST['repmant_concepto'] as $key => $repmant) {
                        $totalRepMant += floatval($_POST['repmant_importe'][$key]);
                      ?>
                  <?php }} elseif(isset($caja['repmantes'])) {
                    foreach ($caja['repmantes'] as $repmant) {
                        $totalRepMant += floatval($repmant->monto);
                      ?>
                  <?php }} ?>

                  <tr class='row-total'>
                    <td colspan="9"></td>
                    <td style="">
                      <input type="text" name="total_repmante" value="<?php echo MyString::float(MyString::formatoNumero($totalRepMant, 2, '')) ?>" class="span12" id="total-repmante" readonly style="text-align: right;">
                    </td>
                  </tr>

                </tbody>
              </table>
            </div>
          </div>

          <!-- GASTOS -->
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
                              <th colspan="2">GASTOS
                                <button type="button" class="btn btn-success" id="btn-add-gastos" style="padding: 2px 7px 2px;margin-right: 2px;"><i class="icon-plus"></i></button>
                              </th>
                              <th colspan="2"></th>
                              <th colspan="2">IMPORTE</th>
                            </tr>
                            <tr>
                              <th style="width: 15%;">FECHA</th>
                              <th style="width: 30%;">PROVEEDOR</th>
                              <th style="width: 37%;">CONCEPTO</th>
                              <th style="width: 15%;">IMPORTE</th>
                              <th style="width: 3%;"></th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              if (isset($_POST['gastos_concepto'])) {
                                foreach ($_POST['gastos_concepto'] as $key => $concepto) {
                                  $totalGastos += floatval($_POST['traspaso_importe'][$key]); ?>
                                <tr>
                                  <td>
                                    <!-- <select name="traspaso_tipo[]" class="span12 ingreso_nomenclatura" <?php echo $readonly ?>>
                                      <option value="t" <?php echo $_POST['traspaso_tipo'][$key] == 't' ? 'selected' : '' ?>>Ingreso</option>
                                      <option value="f" <?php echo $_POST['traspaso_tipo'][$key] == 'f' ? 'selected' : '' ?>>Egreso</option>
                                    </select> -->
                                    <select name="traspaso_tipo[]" class="span12 traspaso_tipo">
                                      <option value="otros" <?php echo $_POST['traspaso_tipo'][$key]=='otros'? 'selected': ''; ?>>Otros</option>
                                      <option value="caja_limon" <?php echo $_POST['traspaso_tipo'][$key]=='caja_limon'? 'selected': ''; ?>>Caja limón</option>
                                      <option value="caja_gastos" <?php echo $_POST['traspaso_tipo'][$key]=='caja_gastos'? 'selected': ''; ?>>Caja gastos</option>
                                      <option value="caja_fletes" <?php echo $_POST['traspaso_tipo'][$key]=='caja_fletes'? 'selected': ''; ?>>Caja fletes</option>
                                      <option value="caja_plasticos" <?php echo $_POST['traspaso_tipo'][$key]=='caja_plasticos'? 'selected': ''; ?>>Caja Plasticos Gdl</option>
                                      <option value="caja_general" <?php echo $_POST['traspaso_tipo'][$key]=='caja_general'? 'selected': ''; ?>>Caja Distribuidora</option>
                                      <option value="caja_prestamo" <?php echo $_POST['traspaso_tipo'][$key]=='caja_prestamo'? 'selected': ''; ?>>Caja Préstamo</option>
                                    </select>
                                    <input type="hidden" name="traspaso_id_traspaso[]" value="" id="traspaso_id_traspaso">
                                    <input type="hidden" name="traspaso_del[]" value="" id="traspaso_del">
                                  </td>
                                  <td></td>
                                  <td>
                                    <select name="traspaso_afectar_fondo[]" class="span12 traspaso_afectar_fondo">
                                      <option value="f" <?php echo $_POST['traspaso_afectar_fondo'][$key] == 'f' ? 'selected' : '' ?>>No</option>
                                      <option value="t" <?php echo $_POST['traspaso_afectar_fondo'][$key] == 't' ? 'selected' : '' ?>>Si</option>
                                    </select>
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
                                $totalGastos += ($traspaso->tipo == 't'? 1: -1) * floatval($traspaso->monto);
                              ?>
                              <tr>
                                <td>
                                  <?php echo ucfirst(str_replace('_', ' ', $traspaso->tipo_caja)); ?>
                                  <input type="hidden" name="traspaso_tipo[]" value="<?php echo $traspaso->tipo_caja ?>">
                                  <input type="hidden" name="traspaso_id_traspaso[]" value="<?php echo $traspaso->id_traspaso ?>" id="traspaso_id_traspaso">
                                  <input type="hidden" name="traspaso_del[]" value="" id="traspaso_del">
                                  <a href="<?php echo base_url('panel/caja_chica/print_vale_tras/?id_traspaso='.$traspaso->id_traspaso.'&noCaja='.$traspaso->no_caja)?>" target="_blank" title="Imprimir Gasto comprobar">
                                    <i class="ico icon-print" style="cursor:pointer"></i></a>
                                </td>
                                <td><?php echo $traspaso->tipo == 't' ? 'Ingreso' : 'Egreso' ?></td>
                                <td>
                                  <?php echo $traspaso->afectar_fondo == 't' ? 'Si' : 'No'; ?>
                                  <input type="hidden" name="traspaso_afectar_fondo[]" value="<?php echo $traspaso->afectar_fondo ?>">
                                </td>
                                <td style="">
                                  <?php if ($traspaso->guardado == 't'): ?>
                                  <input type="text" name="traspaso_concepto[]" value="<?php echo $traspaso->concepto ?>" class="span12 traspaso-concepto" <?php echo $readonly ?>>
                                  <?php else: ?>
                                    <input type="hidden" name="traspaso_concepto[]" value="-@-" class="span12 traspaso-concepto" <?php echo $readonly ?>>
                                    <?php echo $traspaso->concepto." (Traspasado de caja No {$traspaso->no_caja})" ?>
                                  <?php endif ?>
                                </td>
                                <td style="width: 60px;">
                                  <?php if ($traspaso->guardado == 't'): ?>
                                  <input type="text" name="traspaso_importe[]" value="<?php echo $traspaso->monto ?>" class="span12 vpositive traspaso-importe" <?php echo $readonly.$readonlyCC ?>>
                                  <?php else: ?>
                                    <input type="hidden" name="traspaso_importe[]" value="<?php echo $traspaso->monto ?>" class="span12 vpositive traspaso-importe" <?php echo $readonly.$readonlyCC ?>>
                                    <?php echo $traspaso->monto ?>
                                  <?php endif ?>
                                </td>
                                <td style="width: 30px;">
                                  <?php if (!$cajas_cerradas && $modificar_campos && $traspaso->guardado == 't'): ?>
                                  <button type="button" class="btn btn-danger btn-del-traspaso" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>
                                  <?php endif ?>
                                </td>
                              </tr>
                            <?php }} ?>
                            <tr class="row-total">
                              <td colspan="3" style="text-align: right; font-weight: bolder;">TOTAL</td>
                              <td><input type="text" value="<?php echo $totalGastos ?>" class="input-small vpositive" id="ttotal-traspasos" style="text-align: right;" readonly></td>
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