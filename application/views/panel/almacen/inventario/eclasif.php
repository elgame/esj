    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/inventario/eclasif_pdf/'); ?>" method="GET" class="form-search" target="frame_reporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="ffecha1">Al</label>
                  <div class="controls">
                    <input type="date" name="ffecha1" class="span11" id="ffecha1" value="<?php echo isset($_GET['ffecha1']) ? $_GET['ffecha1'] : date('Y-m-d'); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dunidad">Unidad</label>
                  <div class="controls">
                    <input type="text" name="dunidad" value="<?php echo set_value_get('dunidad', $this->input->get('dunidad')) ?>"
                      id="dunidad" class="span12" placeholder="Nombre unidad">
                    <input type="hidden" name="did_unidad" value="<?php echo set_value_get('did_unidad', $this->input->get('did_unidad')) ?>" id="did_unidad">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="detiqueta">Etiqueta</label>
                  <div class="controls">
                    <input type="text" name="detiqueta"
                      value="<?php echo set_value_get('detiqueta', $this->input->get('detiqueta')) ?>" id="detiqueta" class="span12" placeholder="Nombre">
                    <input type="hidden" name="did_etiqueta" value="<?php echo set_value_get('did_etiqueta', $this->input->get('did_etiqueta')) ?>" id="did_etiqueta">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dcalibre">Calibre</label>
                  <div class="controls">
                    <input type="text" name="dcalibre"
                      value="<?php echo set_value_get('dcalibre', $this->input->get('dcalibre')) ?>" id="dcalibre" class="span12" placeholder="Nombre">
                    <input type="hidden" name="did_calibre" value="<?php echo set_value_get('did_calibre', $this->input->get('did_calibre')) ?>" id="did_calibre">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dcon_mov">Con Movimientos</label>
                  <div class="controls">
                    <input type="checkbox" name="dcon_mov" value="si" id="dcon_mov" >
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

      <iframe id="frame_reporte" src="<?php echo base_url('panel/inventario/eclasif_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>

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