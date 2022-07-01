    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/inventario/cproductos_pdf/'); ?>" method="GET" class="form-search comprasxproductos" id="form" target="frame_reporte">
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
                      value="<?php echo (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: ''); ?>" id="dempresa" class="span12" placeholder="Nombre" required>
                    <input type="hidden" name="did_empresa" value="<?php echo (isset($empresa->id_empresa)? $empresa->id_empresa: ''); ?>" id="did_empresa">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fproductor">Producto</label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="fproductor" value="" id="fproductor" class="span9" placeholder="Buscar">
                      <button class="btn" type="button" id="btnAddProducto" style="margin-left:-3px;"><i class="icon-plus-sign"></i></button>
                      <input type="hidden" name="fid_producto" id="fid_producto" value="" class="fid_producto">
                    </div>
                    <div class="clearfix"></div>
                    <div style="height:130px;overflow-y: scroll;background-color:#eee;">
                      <ul id="lista_proveedores" style="list-style: none;margin-left: 4px;">
                      </ul>
                    </div>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ffamilias">Familias</label>
                  <div class="controls">
                    <div style="height:130px;overflow-y: scroll;background-color:#eee;">
                      <ul id="lista_familias" style="list-style: none;margin-left: 4px;">
                        <?php foreach ($familias as $key => $value): ?>
                          <li><label> <input type="checkbox" name="familias[]" value="<?php echo $value->id_familia ?>"> <?php echo $value->nombre ?></label></li>
                        <?php endforeach ?>
                      </ul>
                    </div>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dcon_mov">Con Movimientos</label>
                  <div class="controls">
                    <input type="checkbox" name="dcon_mov" value="si" id="dcon_mov" >
                  </div>
                </div>

                <!-- <div class="control-group" id="cultivosGrup">
                  <label class="control-label" for="area">Cultivo / Actividad / Producto </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area') ?>" placeholder="Limon, Piña">
                    </div>
                    <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId') ?>">
                  </div>
                  <div style="clear: both;"></div>
                </div> -->
                <!--/control-group -->

                <!-- <div class="control-group" id="ranchosGrup">
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
                </div> -->
                <!--/control-group -->

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
          <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/inventario/cproductos_xls'); ?>" class="linksm" target="_blank">
            <i class="icon-table"></i> Excel</a>

          <div class="box-content">
            <div class="row-fluid">
              <iframe id="frame_reporte" name="frame_reporte" src="<?php echo base_url('panel/inventario/cproductos_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>
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