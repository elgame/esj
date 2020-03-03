    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form id="form" action="<?php echo base_url('panel/productos_salidas/rpt_salidas_productos_pdf/'); ?>" method="GET" class="form-search" target="frame_reporte">
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
                  <label class="control-label" for="did_almacen">Almacen</label>
                  <div class="controls">
                    <div class="input-append span12">
                      <select name="did_almacen" id="did_almacen" class="span11">
                        <option value=""></option>
                      <?php $default = '';
                      foreach ($almacenes['almacenes'] as $key => $value) { ?>
                        <option value="<?php echo $value->id_almacen ?>" <?php echo set_select('did_almacen', $value->id_almacen, false, $default) ?>><?php echo $value->nombre ?></option>
                      <?php } ?>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="control-group" id="empresaApGrup">
                  <label class="control-label" for="empresaAp">Empresa aplicación </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="empresaAp" class="span11" id="empresaAp" value="<?php echo set_value('empresaAp') ?>" placeholder="Empaque, Mamita, etc">
                    </div>
                    <input type="hidden" name="empresaApId" id="empresaApId" value="<?php echo set_value('empresaApId') ?>">
                  </div>
                </div><!--/control-group -->

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
                  <label class="control-label" for="activos">Activos </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="activos" class="span11" id="activos" value="<?php echo set_value('activos') ?>" placeholder="Nissan FRX, Maquina limon">
                    </div>
                    <input type="hidden" name="activoId" id="activoId" value="<?php echo set_value('activoId') ?>">
                  </div>
                </div><!--/control-group -->

                <div class="control-group">
                  <label class="control-label" for="fproducto">Producto</label>
                  <div class="controls">
                    <input type="text" name="fproducto" data-add="false"
                      value="<?php echo set_value_get('fproducto', $this->input->get('fproducto')) ?>" id="fproducto" class="span12" placeholder="Nombre">
                    <input type="hidden" name="fid_producto" value="<?php echo set_value_get('fid_producto', $this->input->get('fid_producto')) ?>" id="fid_producto">
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
        <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/productos_salidas/rpt_salidas_productos_xls'); ?>" class="linksm" target="_blank">
          <i class="icon-table"></i> Excel</a>
        <div class="box-content">
          <div class="row-fluid">
            <iframe id="frame_reporte" name="frame_reporte" src="<?php echo base_url('panel/productos_salidas/rpt_salidas_productos_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>
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