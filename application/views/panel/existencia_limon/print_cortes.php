    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/existencias_limon/print_caja_rpt/'); ?>" method="GET" class="form-search" id="frmverformprod" target="frame_reporte">
              <div class="form-actions form-filters">

                <div class="control-group span12">
                  <label class="control-label" for="ffecha">Fecha</label>
                  <div class="controls">
                    <input type="date" name="ffecha" class="span11" id="ffecha" value="<?php echo isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d'); ?>">
                    <input type="hidden" name="fno_caja" value="1">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="farea">Caja</label>
                  <div class="controls">
                    <select name="farea" class="span11" id="farea">
                      <?php foreach ($areas['areas'] as $area){ ?>
                        <option value="<?php echo $area->id_area ?>" data-tipo="<?php echo $area->tipo; ?>" data-coco="<?php echo ($area->nombre == 'COCOS'? 't': 'f') ?>"
                          <?php $set_select=set_select('farea', $area->id_area, false, isset($_POST['farea']) ? $_POST['farea'] : ($area->predeterminado == 't' ? $area->id_area: '') );
                           echo $set_select.($set_select==' selected="selected"'? '': ''); ?>><?php echo $area->nombre ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="tipo">Tipo</label>
                  <div class="controls">
                    <select name="tipo" class="span11" id="tipo">
                      <option value="print_caja">Formato 1</option>
                      <option value="print_caja2">Formato 2</option>
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

      <iframe id="frame_reporte" name="frame_reporte" src="" style="width: 100%;height: 475px;"></iframe>

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