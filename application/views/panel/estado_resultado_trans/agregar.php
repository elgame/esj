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
                    <th colspan="7">VENTAS
                      <a href="#modal-remisiones" role="button" class="btn btn-info" data-toggle="modal" id="btn-show-remisiones" style="padding: 2px 7px 2px; float: right;">Remisiones</a>
                    </th>
                    <th colspan="2">IMPORTE</th>
                  </tr>
                  <tr>
                    <th>EMPRESA</th>
                    <th>REMISION</th>
                    <th>FECHA REM</th>
                    <th>NOM</th>
                    <th colspan="3">NOMBRE</th>
                    <th>ABONO</th>
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
                    <?php }} else {
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
                  <?php }} else {
                    foreach ($caja['remisiones'] as $remision) {
                        $totalIngresosRemisiones += floatval($remision->monto);
                      ?>
                  <?php }} ?>

                  <tr class='row-total'>
                    <td colspan="7"></td>
                    <td style=""><input type="text" name="total_ingresosRemisiones" value="<?php echo MyString::float(MyString::formatoNumero($totalIngresosRemisiones, 2, '')) ?>" class="span12" id="total-ingresosRemisiones" maxlength="500" readonly style="text-align: right;"></td>
                    <td></td>
                  </tr>

                </tbody>
              </table>
            </div>
          </div>

          <!-- Modal Seguro-->
          <div id="modal-seguro" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-header">
              <!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button> -->
              <h3 id="myModalLabel">Informacion Seguro</h3>
              <button type="button" class="btn pull-right" id="btn_seguro_add"><i class="icon-plus"></i></button>
            </div>
            <div class="modal-body">
            <?php
            if (isset($borrador) && isset($borrador['seguro'])){
              foreach ($borrador['seguro'] as $sp => $prodesp) {
                $_POST['pproveedor_seguro'][] = $prodesp->proveedor;
                $_POST['seg_id_proveedor'][]  = $prodesp->id_proveedor;
                $_POST['seg_poliza'][]        = $prodesp->pol_seg;
              }
            }

            if (isset($_POST['seg_id_proveedor']) && count($_POST['seg_id_proveedor']) > 0) {
              foreach ($_POST['seg_id_proveedor'] as $key => $value) {
            ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_seguro" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_seguro[]" value="<?php echo $_POST['pproveedor_seguro'][$key] ?>" id="pproveedor_seguro" class="span12 sikey field-check pproveedor_seguro" placeholder="Proveedor" data-next="seg_poliza">
                    <input type="hidden" name="seg_id_proveedor[]" value="<?php echo $_POST['seg_id_proveedor'][$key] ?>" id="seg_id_proveedor" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="seg_poliza" style="width: auto;">POL/SEG</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="seg_poliza[]" class="span12 sikey field-check" id="seg_poliza" value="<?php echo $_POST['seg_poliza'][$key] ?>" maxlength="30" placeholder="Poliza/Seguro" data-next="pproveedor_seguro">
                  </div>
                </div>
              </div>
            <?php }
            } else { ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_seguro" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_seguro[]" value="<?php echo set_value('pproveedor_seguro[]', isset($borrador) && isset($borrador['seguro']) ? $borrador['seguro']->proveedor : '') ?>" id="pproveedor_seguro" class="span12 sikey field-check pproveedor_seguro" placeholder="Proveedor" data-next="seg_poliza">
                    <input type="hidden" name="seg_id_proveedor[]" value="<?php echo set_value('seg_id_proveedor[]', isset($borrador) && isset($borrador['seguro']) ? $borrador['seguro']->id_proveedor : '') ?>" id="seg_id_proveedor" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="seg_poliza" style="width: auto;">POL/SEG</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="seg_poliza[]" class="span12 sikey field-check" id="seg_poliza" value="<?php echo set_value('seg_poliza[]', isset($borrador) && isset($borrador['seguro']) ? $borrador['seguro']->pol_seg : ''); ?>" maxlength="30" placeholder="Poliza/Seguro" data-next="pproveedor_seguro">
                  </div>
                </div>
              </div>
            <?php } ?>
            </div>
            <div class="modal-footer">
              <button class="btn" data-dismiss="modal" aria-hidden="true" id="btnClose" <?php echo isset($borrador) && isset($borrador['seguro']) ? '' : 'disabled' ?>>Cerrar</button>
            </div>
          </div>

          <!-- Modal Certificados -->
          <div id="modal-certificado51" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-header">
              <!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button> -->
              <h3 id="myModalLabel">Informacion Certificado</h3>
              <button type="button" class="btn pull-right" id="btn_certificado51_add"><i class="icon-plus"></i></button>
            </div>
            <div class="modal-body">
              <?php
              if (isset($borrador) && isset($borrador['certificado51'])){
                foreach ($borrador['certificado51'] as $sp => $prodesp) {
                  $_POST['pproveedor_certificado51'][] = $prodesp->proveedor;
                  $_POST['cert_id_proveedor51'][]      = $prodesp->id_proveedor;
                  $_POST['cert_certificado51'][]       = $prodesp->certificado;
                  $_POST['cert_bultos51'][]            = $prodesp->bultos;
                  $_POST['cert_num_operacion51'][]     = $prodesp->num_operacion;
                  $_POST['cert_no_certificado51'][]    = $prodesp->no_certificado;
                  $_POST['cert_id_orden51'][]          = $prodesp->id_orden;
                }
              }

              if (isset($_POST['cert_id_proveedor51']) && count($_POST['cert_id_proveedor51']) > 0) {
                foreach ($_POST['cert_id_proveedor51'] as $key => $value) {
              ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_certificado51" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_certificado51[]" value="<?php echo $_POST['pproveedor_certificado51'][$key]; ?>" id="pproveedor_certificado51" class="span12 sikey field-check pproveedor_certificado51" placeholder="Proveedor" data-next="cert_certificado51">
                    <input type="hidden" name="cert_id_proveedor51[]" value="<?php echo $_POST['cert_id_proveedor51'][$key]; ?>" id="cert_id_proveedor51" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_certificado51" style="width: auto;">CERTIFICADO</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_certificado51[]" class="span12 sikey field-check" id="cert_certificado51" value="<?php echo $_POST['cert_certificado51'][$key]; ?>" maxlength="30" placeholder="Certificado" data-next="cert_bultos51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_bultos51" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_bultos51[]" class="span12 vpositive sikey field-check" id="cert_bultos51" value="<?php echo $_POST['cert_bultos51'][$key]; ?>" placeholder="Bultos" data-next="cert_num_operacion51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_num_operacion51" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_num_operacion51[]" class="span12 sikey field-check" id="cert_num_operacion51" value="<?php echo $_POST['cert_num_operacion51'][$key] ?>" placeholder="Num Operacion" data-next="pproveedor_certificado51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_no_certificado51" style="width: auto;">Certificado de compra</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_no_certificado51[]" class="span12 sikey cursorp cert_no_certificado51" id="cert_no_certificado51" value="<?php echo $_POST['cert_no_certificado51'][$key] ?>" placeholder="Num Certificado" data-next="pproveedor_certificado51" readonly>
                    <input type="hidden" name="cert_id_orden51[]" class="span12 sikey" id="cert_id_orden51" value="<?php echo $_POST['cert_id_orden51'][$key] ?>">
                  </div>
                </div>
              </div>
            <?php }
            } else { ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_certificado51" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_certificado51[]" value="<?php echo set_value('pproveedor_certificado51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->proveedor : '') ?>" id="pproveedor_certificado51" class="span12 sikey field-check pproveedor_certificado51" placeholder="Proveedor" data-next="cert_certificado51">
                    <input type="hidden" name="cert_id_proveedor51[]" value="<?php echo set_value('cert_id_proveedor51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->id_proveedor : '') ?>" id="cert_id_proveedor51" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_certificado51" style="width: auto;">CERTIFICADO</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_certificado51[]" class="span12 sikey field-check" id="cert_certificado51" value="<?php echo set_value('cert_certificado51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->certificado : ''); ?>" maxlength="30" placeholder="Certificado" data-next="cert_bultos51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_bultos51" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_bultos51[]" class="span12 vpositive sikey field-check" id="cert_bultos51" value="<?php echo set_value('cert_bultos51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->bultos : ''); ?>" placeholder="Bultos" data-next="cert_num_operacion51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_num_operacion51" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_num_operacion51[]" class="span12 sikey field-check" id="cert_num_operacion51" value="<?php echo set_value('cert_num_operacion51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->num_operacion : ''); ?>" placeholder="Num Operacion" data-next="cert_no_certificado51">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_no_certificado51" style="width: auto;">Certificado de compra</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_no_certificado51[]" class="span12 sikey cursorp cert_no_certificado51" id="cert_no_certificado51" value="<?php echo set_value('cert_no_certificado51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->no_certificado : ''); ?>" placeholder="Num Certificado" data-next="pproveedor_certificado51" readonly>
                    <input type="hidden" name="cert_id_orden51[]" class="span12 sikey" id="cert_id_orden51" value="<?php echo set_value('cert_id_orden51[]', isset($borrador) && isset($borrador['certificado51']) ? $borrador['certificado51']->id_orden : ''); ?>">
                  </div>
                </div>
              </div>
            <?php } ?>
            </div>
            <div class="modal-footer">
              <button class="btn" data-dismiss="modal" aria-hidden="true" id="btnClose" <?php echo isset($borrador) && isset($borrador['certificado51']) ? '' : 'disabled' ?>>Cerrar</button>
            </div>
          </div>
          <div id="modal-certificado52" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-header">
              <!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button> -->
              <h3 id="myModalLabel">Informacion Certificado</h3>
              <button type="button" class="btn pull-right" id="btn_certificado52_add"><i class="icon-plus"></i></button>
            </div>
            <div class="modal-body">
            <?php
            if (isset($borrador) && isset($borrador['certificado52'])){
              foreach ($borrador['certificado52'] as $sp => $prodesp) {
                $_POST['pproveedor_certificado52'][] = $prodesp->proveedor;
                $_POST['cert_id_proveedor52'][]      = $prodesp->id_proveedor;
                $_POST['cert_certificado52'][]       = $prodesp->certificado;
                $_POST['cert_bultos52'][]            = $prodesp->bultos;
                $_POST['cert_num_operacion52'][]     = $prodesp->num_operacion;
                $_POST['cert_no_certificado52'][]    = $prodesp->no_certificado;
                $_POST['cert_id_orden52'][]          = $prodesp->id_orden;
              }
            }

            if (isset($_POST['cert_id_proveedor52']) && count($_POST['cert_id_proveedor52']) > 0) {
              foreach ($_POST['cert_id_proveedor52'] as $key => $value) {
            ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_certificado52" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_certificado52[]" value="<?php echo $_POST['pproveedor_certificado52'][$key]; ?>" id="pproveedor_certificado52" class="span12 sikey field-check pproveedor_certificado52" placeholder="Proveedor" data-next="cert_certificado52">
                    <input type="hidden" name="cert_id_proveedor52[]" value="<?php echo $_POST['cert_id_proveedor52'][$key]; ?>" id="cert_id_proveedor52" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_certificado52" style="width: auto;">CERTIFICADO</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_certificado52[]" class="span12 sikey field-check" id="cert_certificado52" value="<?php echo $_POST['cert_certificado52'][$key]; ?>" maxlength="30" placeholder="Certificado" data-next="cert_bultos52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_bultos52" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_bultos52[]" class="span12 vpositive sikey field-check" id="cert_bultos52" value="<?php echo $_POST['cert_bultos52'][$key]; ?>" placeholder="Bultos" data-next="cert_num_operacion52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_num_operacion52" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_num_operacion52[]" class="span12 sikey field-check" id="cert_num_operacion52" value="<?php echo $_POST['cert_num_operacion52'][$key] ?>" placeholder="Num Operacion" data-next="pproveedor_certificado52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_no_certificado52" style="width: auto;">Certificado de compra</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_no_certificado52[]" class="span12 sikey cursorp cert_no_certificado52" id="cert_no_certificado52" value="<?php echo $_POST['cert_no_certificado52'][$key] ?>" placeholder="Num Certificado" data-next="pproveedor_certificado52" readonly>
                    <input type="hidden" name="cert_id_orden52[]" class="span12 sikey" id="cert_id_orden52" value="<?php echo $_POST['cert_id_orden52'][$key] ?>">
                  </div>
                </div>
              </div>
            <?php }
            } else { ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_certificado52" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_certificado52[]" value="<?php echo set_value('pproveedor_certificado52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->proveedor : '') ?>" id="pproveedor_certificado52" class="span12 sikey field-check pproveedor_certificado52" placeholder="Proveedor" data-next="cert_certificado52">
                    <input type="hidden" name="cert_id_proveedor52[]" value="<?php echo set_value('cert_id_proveedor52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->id_proveedor : '') ?>" id="cert_id_proveedor52" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_certificado52" style="width: auto;">CERTIFICADO</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_certificado52[]" class="span12 sikey field-check" id="cert_certificado52" value="<?php echo set_value('cert_certificado52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->certificado : ''); ?>" maxlength="30" placeholder="Certificado" data-next="cert_bultos52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_bultos52" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_bultos52[]" class="span12 vpositive sikey field-check" id="cert_bultos52" value="<?php echo set_value('cert_bultos52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->bultos : ''); ?>" placeholder="Bultos" data-next="cert_num_operacion52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_num_operacion52" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_num_operacion52[]" class="span12 sikey field-check" id="cert_num_operacion52" value="<?php echo set_value('cert_num_operacion52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->num_operacion : ''); ?>" placeholder="Num Operacion" data-next="pproveedor_certificado52">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="cert_no_certificado52" style="width: auto;">Certificado de compra</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="cert_no_certificado52[]" class="span12 sikey cursorp cert_no_certificado52" id="cert_no_certificado52" value="<?php echo set_value('cert_no_certificado52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->no_certificado : ''); ?>" placeholder="Num Certificado" data-next="pproveedor_certificado52" readonly>
                    <input type="hidden" name="cert_id_orden52[]" class="span12 sikey" id="cert_id_orden52" value="<?php echo set_value('cert_id_orden52[]', isset($borrador) && isset($borrador['certificado52']) ? $borrador['certificado52']->id_orden : ''); ?>">
                  </div>
                </div>
              </div>
            <?php } ?>
            </div>
            <div class="modal-footer">
              <button class="btn" data-dismiss="modal" aria-hidden="true" id="btnClose" <?php echo isset($borrador) && isset($borrador['certificado52']) ? '' : 'disabled' ?>>Cerrar</button>
            </div>
          </div>

          <!-- Modal Supervisor carga -->
          <div id="modal-supcarga" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <div class="modal-header">
              <!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button> -->
              <h3 id="myModalLabel">Informacion Supervisor de carga</h3>
              <button type="button" class="btn pull-right" id="btn_supcarga_add"><i class="icon-plus"></i></button>
            </div>
            <div class="modal-body">
            <?php
            if (isset($borrador) && isset($borrador['supcarga'])){
              foreach ($borrador['supcarga'] as $sp => $prodesp) {
                $_POST['pproveedor_supcarga'][]    = $prodesp->proveedor;
                $_POST['supcarga_id_proveedor'][]  = $prodesp->id_proveedor;
                $_POST['supcarga_numero'][]        = $prodesp->certificado;
                $_POST['supcarga_bultos'][]        = $prodesp->bultos;
                $_POST['supcarga_num_operacion'][] = $prodesp->num_operacion;
              }
            }

            if (isset($_POST['supcarga_id_proveedor']) && count($_POST['supcarga_id_proveedor']) > 0) {
              foreach ($_POST['supcarga_id_proveedor'] as $key => $value) {
            ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_supcarga" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_supcarga[]" value="<?php echo $_POST['pproveedor_supcarga'][$key] ?>" id="pproveedor_supcarga" class="span12 sikey field-check pproveedor_supcarga" placeholder="Proveedor" data-next="supcarga_numero">
                    <input type="hidden" name="supcarga_id_proveedor[]" value="<?php echo $_POST['supcarga_id_proveedor'][$key] ?>" id="supcarga_id_proveedor" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_numero" style="width: auto;">Numero</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_numero[]" class="span12 sikey field-check" id="supcarga_numero" value="<?php echo $_POST['supcarga_numero'][$key] ?>" maxlength="30" placeholder="Numero" data-next="supcarga_bultos">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_bultos" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_bultos[]" class="span12 vpositive sikey field-check" id="supcarga_bultos" value="<?php echo $_POST['supcarga_bultos'][$key] ?>" placeholder="Bultos" data-next="supcarga_num_operacion">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_num_operacion" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_num_operacion[]" class="span12 sikey field-check" id="supcarga_num_operacion" value="<?php echo $_POST['supcarga_num_operacion'][$key] ?>" placeholder="Num Operacion" data-next="pproveedor_supcarga">
                  </div>
                </div>
              </div>
            <?php }
            } else { ?>
              <div class="grup_datos" style="border-bottom: 2px solid #aaa;">
                <div class="control-group">
                  <label class="control-label" for="pproveedor_supcarga" style="width: auto;">PROVEEDOR</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="pproveedor_supcarga[]" value="<?php echo set_value('pproveedor_supcarga[]', isset($borrador) && isset($borrador['supcarga']) ? $borrador['supcarga']->proveedor : '') ?>" id="pproveedor_supcarga" class="span12 sikey field-check pproveedor_supcarga" placeholder="Proveedor" data-next="supcarga_numero">
                    <input type="hidden" name="supcarga_id_proveedor[]" value="<?php echo set_value('supcarga_id_proveedor[]', isset($borrador) && isset($borrador['supcarga']) ? $borrador['supcarga']->id_proveedor : '') ?>" id="supcarga_id_proveedor" class="field-check">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_numero" style="width: auto;">Numero</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_numero[]" class="span12 sikey field-check" id="supcarga_numero" value="<?php echo set_value('supcarga_numero[]', isset($borrador) && isset($borrador['supcarga']) ? $borrador['supcarga']->certificado : ''); ?>" maxlength="30" placeholder="Numero" data-next="supcarga_bultos">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_bultos" style="width: auto;">BULTOS</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_bultos[]" class="span12 vpositive sikey field-check" id="supcarga_bultos" value="<?php echo set_value('supcarga_bultos[]', isset($borrador) && isset($borrador['supcarga']) ? $borrador['supcarga']->bultos : ''); ?>" placeholder="Bultos" data-next="supcarga_num_operacion">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="supcarga_num_operacion" style="width: auto;">Num Operacion</label>
                  <div class="controls" style="margin-left: 0">
                    <input type="text" name="supcarga_num_operacion[]" class="span12 sikey field-check" id="supcarga_num_operacion" value="<?php echo set_value('supcarga_num_operacion[]', isset($borrador) && isset($borrador['supcarga']) ? $borrador['supcarga']->num_operacion : ''); ?>" placeholder="Num Operacion" data-next="pproveedor_supcarga">
                  </div>
                </div>
              </div>
            <?php } ?>
            </div>
            <div class="modal-footer">
              <button class="btn" data-dismiss="modal" aria-hidden="true" id="btnClose" <?php echo isset($borrador) && isset($borrador['supcarga']) ? '' : 'disabled' ?>>Cerrar</button>
            </div>
          </div>

        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->

  <!-- Modal -->
  <div id="modal-pallets" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Pallets del Cliente</h3>
    </div>
    <div class="modal-body">
      <div class="row-fluid">
        <table class="table table-hover table-condensed" id="table-pallets-cliente">
          <thead>
            <tr>
              <th></th>
              <th># Folio</th>
              <th>Cajas</th>
              <th>Fecha</th>
            </tr>
          </thead>
          <tbody>
            <!-- <tr>
              <th><input type="checkbox" value="" class="" id=""><input type="hidden" value=""></th>
              <th>9</th>
              <th>100</th>
              <th>2013-10-22</th>
            </tr> -->
          </tbody>
        </table>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="BtnAddClientePallets">Agregar Pallets</button>
    </div>
  </div><!--/modal pallets -->

  <!-- Modal No Certificados compras -->
  <div id="modal-no-certificados" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 700px;left: 45%;">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">No Certificados Compras</h3>
    </div>
    <div class="modal-body" style="max-height: 370px;">
      <table id="lista_certificados_modal" class="table table-striped table-bordered table-hover table-condensed">
        <caption></caption>
        <thead>
          <tr>
            <th>Folio</th>
            <th>Empresa</th>
            <th>Certificado</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
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