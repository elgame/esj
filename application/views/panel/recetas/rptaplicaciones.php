    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/recetas/rptaplicaciones_pdf/'); ?>" method="GET" class="form-search" id="frmrptcproform" target="frame_reporte">
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

                <div class="control-group">
                  <label class="control-label" for="dempresa">Via Aplic</label>
                  <div class="controls">
                    <select name="a_via" class="span12 datosapl" id="a_via">
                      <option value=""></option>
                      <option value="Elaboración de Solución" <?php echo set_select('a_via', 'Elaboración de Solución'); ?>>Elaboración de Solución</option>
                      <option value="Elaboración de Composta" <?php echo set_select('a_via', 'Elaboración de Composta'); ?>>Elaboración de Composta</option>
                      <option value="Aplicación Foliar" <?php echo set_select('a_via', 'Aplicación Foliar'); ?>>Aplicación Foliar</option>
                      <option value="Aplicación Solida al Suelo" <?php echo set_select('a_via', 'Aplicación Solida al Suelo'); ?>>Aplicación Solida al Suelo</option>
                      <option value="Aplicación en Sistema Riego" <?php echo set_select('a_via', 'Aplicación en Sistema Riego'); ?>>Aplicación en Sistema Riego</option>
                      <option value="Aplicación en Drench a Piso" <?php echo set_select('a_via', 'Aplicación en Drench a Piso'); ?>>Aplicación en Drench a Piso</option>
                      <option value="Aplicación en Drench a Planta" <?php echo set_select('a_via', 'Aplicación en Drench a Planta'); ?>>Aplicación en Drench a Planta</option>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="area">Cultivo</label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="area" class="span12" id="area" value="<?php echo set_value('area') ?>" placeholder="Limon, Piña">
                    </div>
                    <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId') ?>">
                  </div>
                </div>

                <div class="control-group" id="ranchosGrup">
                  <label class="control-label" for="rancho">Areas / Ranchos </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="rancho" class="span12" id="rancho" value="<?php echo set_value('rancho') ?>" placeholder="Milagro A, Linea 1">
                      <input type="hidden" name="ranchoId" id="ranchoId" value="<?php echo set_value('ranchoId') ?>">
                    </div>
                  </div>
                  <!-- <ul class="tags" id="tagsRanchoIds">
                  <?php if (isset($_POST['ranchoId'])) {
                    foreach ($_POST['ranchoId'] as $key => $ranchoId) { ?>
                      <li><span class="tag"><?php echo $_POST['ranchoText'][$key] ?></span>
                        <input type="hidden" name="ranchoId[]" class="ranchoId" value="<?php echo $ranchoId ?>">
                        <input type="hidden" name="ranchoText[]" class="ranchoText" value="<?php echo $_POST['ranchoText'][$key] ?>">
                      </li>
                   <?php }} ?>
                  </ul> -->
                </div>

                <div class="control-group" id="centrosCostosGrup">
                  <label class="control-label" for="centroCosto">Centro de costo </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="centroCosto" class="span12" id="centroCosto" value="<?php echo set_value('centroCosto') ?>" placeholder="Mantenimiento, Gasto general">
                      <input type="hidden" name="centroCostoId" id="centroCostoId" value="<?php echo set_value('centroCostoId') ?>">
                    </div>
                  </div>
                  <!-- <ul class="tags" id="tagsCCIds">
                  <?php if (isset($_POST['centroCostoId'])) {
                    foreach ($_POST['centroCostoId'] as $key => $centroCostoId) { ?>
                      <li>
                        <span class="tag"><?php echo $_POST['centroCostoText'][$key] ?></span>
                        <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="<?php echo $centroCostoId ?>">
                        <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="<?php echo $_POST['centroCostoText'][$key] ?>">

                        <input type="hidden" name="centroCostoHec[]" class="centroCostoHec" value="<?php echo $_POST['centroCostoHec'][$key] ?>">
                        <input type="hidden" name="centroCostoNoplantas[]" class="centroCostoNoplantas" value="<?php echo $_POST['centroCostoNoplantas'][$key] ?>">
                      </li>
                   <?php }} ?>
                  </ul> -->
                </div>

                <div class="control-group">
                  <label class="control-label" for="fproducto">Producto</label>
                  <div class="controls">
                    <input type="text" name="fproducto"
                      value="<?php echo set_value_get('fproducto', $this->input->get('fproducto')) ?>" id="fproducto" class="span12" placeholder="Nombre">
                    <input type="hidden" name="fid_producto" value="<?php echo set_value_get('fid_producto', $this->input->get('fid_producto')) ?>" id="fid_producto">
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

      <div class="row-fluid">
        <div class="box span12">
          <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/recetas/rptaplicaciones_xls'); ?>" class="linksm" target="_blank">
            <i class="icon-table"></i> Excel</a>

          <div class="box-content">
            <div class="row-fluid">
              <iframe id="frame_reporte" name="frame_reporte" src="<?php echo base_url('panel/recetas/rptaplicaciones_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->

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