    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/bascula/rpt_auditorias_pdf/'); ?>" method="GET" target="frame_reporte" class="form-search">
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

      <div class="box span12">
        <div class="box-content">
          <div class="row-fluid">
            <iframe id="frame_reporte" name="frame_reporte"
              src="<?php echo base_url('panel/bascula/rpt_auditorias_pdf/?'.MyString::getVarsLink(array('msg', 'p', 'pe'))) ?>"
              style="width: 100%;height: 475px;">

              </style>>
              dd
            </iframe>
          </div>
        </div>
      </div>

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