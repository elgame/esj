    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form id="form" action="<?php echo base_url('panel/nomina_trabajos2/rpt_costo_labores_pdf/'); ?>" method="GET" class="form-search" target="frame_reporte">
              <div class="form-actions form-filters">

                <div class="control-group span6">
                  <label class="control-label" for="ffecha1">Del</label>
                  <div class="controls">
                    <input type="date" name="ffecha1" class="span11" id="ffecha1" value="<?php echo isset($_GET['ffecha1']) ? $_GET['ffecha1'] : date('Y-m-01'); ?>">
                  </div>
                </div>

                <div class="control-group span6">
                  <label class="control-label" for="ffecha2">Al</label>
                  <div class="controls">
                    <input type="date" name="ffecha2" class="span11" id="ffecha2" value="<?php echo isset($_GET['ffecha2']) ? $_GET['ffecha2'] : date('Y-m-d'); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="dempresa"
                      value="<?php echo (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: ''); ?>" id="dempresa" class="span12" placeholder="Nombre">
                    <input type="hidden" name="did_empresa" value="<?php echo (isset($empresa->id_empresa)? $empresa->id_empresa: ''); ?>" id="did_empresa">
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
                  <div style="clear: both;"></div>
                </div><!--/control-group -->

                <div class="control-group" id="ranchosGrup">
                  <label class="control-label" for="rancho">Areas / Ranchos / Lineas </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="rancho" class="span11" id="rancho" value="<?php echo set_value('rancho') ?>" placeholder="Milagro A, Linea 1">
                    </div>
                  </div>
                  <ul class="tags" id="tagsRanchoIds">
                  <?php if (isset($_POST['ranchoId'])) {
                    foreach ($_POST['ranchoId'] as $key => $ranchoId) { ?>
                      <li><span class="tag"><?php echo $_POST['ranchoText'][$key] ?></span>
                        <input type="hidden" name="ranchoId[]" class="ranchoId" value="<?php echo $ranchoId ?>">
                        <input type="hidden" name="ranchoText[]" class="ranchoText" value="<?php echo $_POST['ranchoText'][$key] ?>">
                      </li>
                   <?php }} ?>
                  </ul>
                  <div style="clear: both;"></div>
                </div><!--/control-group -->

                <div class="control-group" id="centrosCostosGrup">
                  <label class="control-label" for="centroCosto">Centro de costo </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="centroCosto" class="span11" id="centroCosto" value="<?php echo set_value('centroCosto') ?>" placeholder="Mantenimiento, Gasto general">
                    </div>
                  </div>
                  <ul class="tags" id="tagsCCIds">
                  <?php if (isset($_POST['centroCostoId'])) {
                    foreach ($_POST['centroCostoId'] as $key => $centroCostoId) { ?>
                      <li><span class="tag"><?php echo $_POST['centroCostoText'][$key] ?></span>
                        <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="<?php echo $centroCostoId ?>">
                        <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="<?php echo $_POST['centroCostoText'][$key] ?>">
                      </li>
                   <?php }} ?>
                  </ul>
                  <div style="clear: both;"></div>
                </div><!--/control-group -->

                <div class="control-group" id="activosGrup">
                  <label class="control-label" for="dempleado">Empleado </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="dempleado" class="span11" id="dempleado" value="<?php echo set_value('dempleado') ?>" placeholder="">
                    </div>
                    <input type="hidden" name="dempleadoId" id="dempleadoId" value="<?php echo set_value('dempleadoId') ?>">
                  </div>
                </div><!--/control-group -->

                <div class="control-group">
                  <label class="control-label" for="dlabor">Labor</label>
                  <div class="controls">
                    <input type="text" name="dlabor" data-add="false"
                      value="<?php echo set_value_get('dlabor', $this->input->get('dlabor')) ?>" id="dlabor" class="span11" placeholder="Nombre">
                    <input type="hidden" name="dlaborId" value="<?php echo set_value_get('dlaborId', $this->input->get('dlaborId')) ?>" id="dlaborId">
                  </div>
                </div>

                <!-- <div>
                  <label for="con_existencia">Con Existencia <input type="checkbox" name="con_existencia" id="con_existencia" value="si"> </label> |
                  <label for="con_movimiento">Con Movimientos <input type="checkbox" name="con_movimiento" id="con_movimiento" value="si"> </label>
                </div> -->

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

      <div class="box span12">
        <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/nomina_trabajos2/rpt_costo_labores_xls'); ?>" class="linksm" target="_blank">
          <i class="icon-table"></i> Excel</a>
        <div class="box-content">
          <div class="row-fluid">
            <iframe id="frame_reporte" name="frame_reporte" src="<?php echo base_url('panel/nomina_trabajos2/rpt_costo_labores_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>
          </div>
        </div>
      </div><!--/span-->

    </div><!--/#content.span9-->



<?php if (isset($p) && isset($pe)) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/bascula/imprimir_pagadas/?'.MyString::getVarsLink(array('msg', 'p', 'pe')).'&pe='.$pe)."'" ?>, '_blank');
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