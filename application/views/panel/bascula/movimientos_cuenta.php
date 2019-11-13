    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/bascula/movimientos/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">

                <div class="control-group span6">
                  <label class="control-label" for="fechaini">Del</label>
                  <div class="controls">
                    <input type="text" name="fechaini" class="span11" id="fechaini" value="<?php echo isset($_GET['fechaini']) ? $_GET['fechaini'] : date('Y-m-01'); ?>">
                  </div>
                </div>

                <div class="control-group span6">
                  <label class="control-label" for="fechaend">Al</label>
                  <div class="controls">
                    <input type="text" name="fechaend" class="span11" id="fechaend" value="<?php echo isset($_GET['fechaend']) ? $_GET['fechaend'] : date('Y-m-d'); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="name">Area</label>
                  <div class="controls">
                    <select name="farea" class="span12">
                      <?php foreach ($areas['areas'] as $area) { ?>
                        <option value="<?php echo $area->id_area ?>" <?php echo set_select_get('farea', $area->id_area) ?>><?php echo $area->nombre ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ftipop">Tipo</label>
                  <div class="controls">
                    <select name="ftipop" id="ftipop">
                      <option value="en" <?php echo set_select_get('ftipop', 'en') ?>>ENTRADAS</option>
                      <option value="sa" <?php echo set_select_get('ftipop', 'sa') ?>>SALIDAS</option>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="fempresa"
                      value="<?php echo set_value_get('fempresa') ?>" id="fempresa" class="span12 getjsval" placeholder="Empresa">
                    <input type="hidden" name="fid_empresa" value="<?php echo set_value_get('fid_empresa') ?>" id="fid_empresa" class="getjsval">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label autocomplet_en" for="fproveedor">Proveedor</label>
                  <label class="control-label autocomplet_sa" for="fproveedor" style="display: none;">Cliente</label>
                  <div class="controls">
                    <input type="text" name="fproveedor"
                      value="<?php echo set_value_get('fproveedor', $this->input->get('fproveedor')) ?>" id="fproveedor" class="span12" placeholder="Nombre">
                    <input type="hidden" name="fid_proveedor" value="<?php echo set_value_get('fid_proveedor', $this->input->get('fid_proveedor')) ?>" id="fid_proveedor">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="prancho">Rancho</label>
                  <div class="controls">
                    <input type="text" name="prancho"
                      value="<?php echo set_value_get('prancho') ?>" id="prancho" class="span12" placeholder="Rancho">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fstatusp">Status</label>
                  <div class="controls">
                    <select name="fstatusp">
                      <option value="" <?php echo set_select_get('fstatusp', '') ?>>TODOS</option>
                      <option value="1" <?php echo set_select_get('fstatusp', '1') ?>>PAGADOS</option>
                      <option value="2" <?php echo set_select_get('fstatusp', '2') ?>>NO PAGADOS</option>
                    </select>
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary btn-large span12">Enviar</button>
                </div>

              </div>
            </form> <!-- /form -->

          </div>
        </div><!--/span12 -->
      </div><!--/row-fluid -->
    </div><!-- /span3 -->

    <div id="content" class="span9">
      <!-- content starts -->

      <form action="<?php echo base_url('panel/bascula/pago_basculas/?'.String::getVarsLink(array('msg', 'p', 'pe'))) ?>" method="POST">
        <div class="row-fluid">
          <div class="box span12">
            <div class="box-content">
              <div class="row-fluid">
                <div class="span12">

                  <div class="row-fluid">
                    <div class="span12">
                      <div class="input-prepend span3">
                        <span class="add-on" style="width:48px;">Importe</span><input value="<?php echo $movimientos['totales']['importe'] ?>" class="" id="prependedInput" type="text" style="width: 150px;" readonly>
                      </div>
                      <div class="input-prepend span3">
                        <span class="add-on" style="width:48px;">Pesada</span><input value="<?php echo $movimientos['totales']['pesada'] ?>" class="" id="prependedInput" type="text" style="width: 150px;" readonly>
                      </div>
                      <div class="input-prepend span3">
                        <span class="add-on" style="width:48px;">Total</span><input value="<?php echo $movimientos['totales']['total'] ?>"class="" id="prependedInput" type="text" style="width: 150px;" readonly>
                      </div>
                      <div class="input-prepend span3">
                        <span class="add-on" style="width:48px;">Pagado</span><input value="<?php echo $movimientos['totales']['pagados'] ?>" class="" id="prependedInput" type="text" style="width: 150px;" readonly>
                      </div>
                    </div>
                  </div>

                  <div class="row-fluid">
                    <div class="span12">
                      <div class="input-prepend span3">
                        <span class="add-on" style="width:48px;">Kilos</span><input value="<?php echo $movimientos['totales']['kilos'] ?>" class="" id="prependedInput" type="text" style="width: 150px;" readonly>
                      </div>
                      <div class="input-prepend span3">
                        <span class="add-on" style="width:48px;">Cajas</span><input value="<?php echo $movimientos['totales']['cajas'] ?>" class="" id="prependedInput" type="text" style="width: 150px;" readonly>
                      </div>
                      <div class="input-prepend span3">
                        <span class="add-on" style="width:48px;">P. Prom</span><input value="<?php echo ($movimientos['totales']['kilos'] != 0) ? String::formatoNumero(floatval($movimientos['totales']['importe'])/floatval($movimientos['totales']['kilos']), 3, '') : 0 ?>" class="" id="prependedInput" type="text" style="width: 150px;" readonly>
                      </div>
                      <div class="input-prepend span3">
                        <span class="add-on" style="width:48px;">S/Pago</span><input value="<?php echo $movimientos['totales']['no_pagados'] ?>" class="" id="prependedInput" type="text" style="width: 150px;" readonly>
                      </div>
                    </div>
                  </div>

                  <?php if (isset($_GET['fid_proveedor'])) { ?>
                    <div class="row-fluid">
                      <div class="span12">
                        <!-- <button type="button" class="btn btn-success span3 pull-right" id="btnModalPagos">Pagar</button> -->
                        <a href="#modalPagos" class="btn btn-success span3 pull-right <?php echo ($_GET['fid_proveedor']>0? '': 'hidden') ?>" role="button" data-toggle="modal">Pagar</a>
                        <a href="<?php echo base_url('panel/bascula/rmc_pdf/?'.String::getVarsLink(array('msg'))) ?>" class="btn btn-warning span3 pull-right" target="_BLANK" style="margin-right: 5px;">Reporte</a>
                      </div>
                    </div>
                  <?php } ?>

                </div>
              </div>

              <div class="row-fluid" style="margin-top: 5px;">
                <div class="span12" style="height: 420px; overflow: auto;">
                    <table class="table table-striped table-bordered bootstrap-datatable">
                      <thead>
                        <tr>
                          <th><input type="checkbox" checked id="checkPesadas"></th>
                          <th>TIPO</th>
                          <th>BOLETA</th>
                          <th>FECHA</th>
                          <th>CALIDAD</th>
                          <th>CAJAS</th>
                          <th>PROM</th>
                          <th>KILOS</th>
                          <th>PRECIO</th>
                          <th>IMPORTE</th>
                          <th>TOTAL</th>
                          <th>TIPO PAGO</th>
                          <th>CONCEPTO</th>
                          <th style="width:15px;"><input type="checkbox" id="checkPesadas2"></th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php $lastboleta = 0;
                          foreach($movimientos['movimientos'] as $mov){ ?>
                        <tr>
                          <td>
                            <?php if ($mov->folio != $lastboleta) { ?>
                              <?php if ($mov->status === 'b' || $mov->status === 'p') { ?>
                                <span class="badge badge-important"><?php echo strtoupper($mov->status) ?></span>
                              <?php } else { ?>
                                <input type="checkbox" name="ppagos[]" value="<?php echo $mov->id_bascula ?>" checked id="pesadas" data-monto="<?php echo $mov->importe_todas ?>">
                              <?php }} ?>
                          </td>
                          <td>
                            <?php if ($mov->folio != $lastboleta) { ?>
                              <span class="label label-important"><?php echo strtoupper($mov->tipo) ?></span></td>
                            <?php } ?>
                          <td>
                            <?php if ($mov->folio != $lastboleta) {
                                   echo $mov->folio;
                                 } ?>
                          </td>
                          <td>
                            <?php if ($mov->folio != $lastboleta) {
                                   echo $mov->fecha;
                                 } ?>
                          </td>
                          <td><?php echo $mov->calidad ?></td>
                          <td><?php echo $mov->cajas ?></td>
                          <td><?php echo String::formatoNumero($mov->promedio, 2, '') ?></td>
                          <td><?php echo $mov->kilos ?></td>
                          <td><?php echo String::formatoNumero($mov->precio) ?></td>
                          <td><?php echo $mov->importe ?></td>
                          <td>
                            <?php if ($mov->folio != $lastboleta) { ?>
                              <?php echo $mov->importe_todas ?>
                            <?php } ?>
                          </td>
                          <td>
                            <?php if ($mov->folio != $lastboleta) { ?>
                              <?php echo strtoupper($mov->tipo_pago) ?>
                            <?php } ?>
                          </td>
                          <td>
                            <?php if ($mov->folio != $lastboleta) { ?>
                              <?php echo $mov->concepto ?>
                            <?php } ?>
                          </td>
                          <td>
                            <?php if ($mov->folio != $lastboleta) { ?>
                              <?php if ($mov->status === 'b' || $mov->status === 'p') { ?>
                              <?php } else { ?>
                                <input type="checkbox" class="change_spago" <?php echo ($mov->en_pago>0? 'checked': ''); ?>
                                  data-idcompra="<?php echo $mov->id_bascula; ?>" data-idproveedor="<?php echo $this->input->get('fid_proveedor'); ?>" data-monto="<?php echo $mov->importe_todas; ?>">
                              <?php }} ?>
                          </td>
                        </tr>
                      <?php
                        $lastboleta = $mov->folio;
                        }?>
                      </tbody>
                    </table>

                </div><!--/span-->
              </div>

            </div>
          </div><!--/span-->
        </div><!--/row-->

        <!-- Modal -->
        <div id="modalPagos" class="modal hide fade span3" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="left: 37%;">
          <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3 id="myModalLabel">Pago Pesadas</h3>
          </div>
          <div class="modal-body">

            <div class="control-group">
              <label class="control-label" for="ptipo_pago">TIPO DE PAGO</label>
              <div class="controls">
                <select name="ptipo_pago" id="ptipo_pago" class="span12">
                  <option value="cheque" <?php echo set_select('ptipo_pago', 'cheque', false, $this->input->post('cheque')) ?>>CHEQUE</option>
                  <option value="transferencia" <?php echo set_select('ptipo_pago', 'transferencia', false, $this->input->post('cheque')) ?>>TRANSFERENCIA</option>
                </select>
              </div>
            </div>

            <div class="control-group">
              <label class="control-label" for="pmonto">MONTO</label>
              <div class="controls">
                <input type="text" name="pmonto" value="<?php echo set_value('pmonto',$movimientos['totales']['no_pagados']) ?>" class="span12" id="pmonto" readonly>
              </div>
            </div>

            <div class="control-group">
              <label class="control-label" for="pconcepto">CONCEPTO</label>
              <div class="controls">
                <textarea name="pconcepto" class="span12" id="pconcepto" maxlength="254"><?php echo set_value('pconcepto') ?></textarea>
              </div>
            </div>

          </div>
          <div class="modal-footer">
            <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
            <button type="submit" class="btn btn-primary">Aceptar</button>
          </div>
        </div>

      </form>

    </div><!--/#content.span9-->



<?php if (isset($p) && isset($pe)) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/bascula/imprimir_pagadas/?'.String::getVarsLink(array('msg', 'p', 'pe')).'&pe='.$pe)."'" ?>, '_blank');
    win.focus();
  </script>
<?php } ?>

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