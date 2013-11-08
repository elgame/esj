    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/inventario/epc_pdf/'); ?>" method="GET" class="form-search" target="frame_reporte">
              <div class="form-actions form-filters">

                <div class="control-group span6">
                  <label class="control-label" for="fechaini">Del</label>
                  <div class="controls">
                    <input type="date" name="fechaini" class="span11" id="fechaini" value="<?php echo isset($_GET['fechaini']) ? $_GET['fechaini'] : date('Y-m-01'); ?>">
                  </div>
                </div>

                <div class="control-group span6">
                  <label class="control-label" for="fechaend">Al</label>
                  <div class="controls">
                    <input type="date" name="fechaend" class="span11" id="fechaend" value="<?php echo isset($_GET['fechaend']) ? $_GET['fechaend'] : date('Y-m-d'); ?>">
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
                  <label class="control-label" for="fproducto">Familias</label>
                  <div class="controls" style="height:150px;overflow-y: scroll;background-color:#eee;">
                    <ul style="list-style: none;">
                  <?php foreach ($data['familias'] as $key => $value)
                  {
                  ?>
                    <li><label><input type="checkbox" name="ffamilias[]" value="<?php echo $value->id_familia; ?>" checked> <?php echo $value->nombre; ?></label></li>
                  <?php
                  } ?>
                    </ul>
                  </div>
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

      <iframe id="frame_reporte" src="<?php echo base_url('panel/inventario/epc_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>

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