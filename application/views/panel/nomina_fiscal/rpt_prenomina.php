    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form id="form" action="<?php echo base_url('panel/nomina_trabajos2/rpt_prenomina_pdf/'); ?>" method="GET" class="form-search" target="frame_reporte">
              <div class="form-actions form-filters">

                <div class="control-group span6">
                  <label class="control-label" for="anio">Año</label>
                  <div class="controls">
                    <input type="number" name="anio" class="span11" id="anio" value="<?php echo isset($_GET['anio']) ? $_GET['anio'] : date('Y'); ?>">
                  </div>
                </div>
                <div style="clear: both;"></div>

                <div class="control-group">
                  <label class="control-label" for="dempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="dempresa"
                      value="<?php echo (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: ''); ?>" id="dempresa" class="span12" placeholder="Nombre">
                    <input type="hidden" name="did_empresa" value="<?php echo (isset($empresa->id_empresa)? $empresa->id_empresa: ''); ?>" id="did_empresa">
                  </div>
                </div>

                <div class="control-group" id="cultivosGrup">
                  <label class="control-label" for="semana">Semana </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <select name="semana" class="span12" id="semana">
                        <?php
                          foreach ($semanasDelAno as $semana) {
                          ?>
                          <option value="<?php echo $semana[$tipoNomina] ?>" <?php echo $semana[$tipoNomina] == $numSemanaSelected ? 'selected' : '' ?>><?php echo "{$semana[$tipoNomina]} - Del {$semana['fecha_inicio']} Al {$semana['fecha_final']}" ?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>
                </div><!--/control-group -->
                <div style="clear: both;"></div>

                <div class="control-group" id="cultivosGrup" style="margin-top: 10px;">
                  <label class="control-label" for="fregistro_patronal">Registro Patronal </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <select name="fregistro_patronal" id="fregistro_patronal" class="span12">
                          <option value=""></option>
                          <?php foreach ($registros_patronales as $key => $regp): ?>
                          <option value="<?php echo $regp ?>" <?php echo set_select_get('fregistro_patronal', $regp, ($this->input->get('fregistro_patronal') == $regp)); ?>><?php echo $regp ?></option>
                          <?php endforeach ?>
                      </select>
                    </div>
                  </div>
                </div><!--/control-group -->
                <div style="clear: both;"></div>

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
            <iframe id="frame_reporte" name="frame_reporte" src="<?php echo base_url('panel/nomina_trabajos2/rpt_prenomina_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>
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