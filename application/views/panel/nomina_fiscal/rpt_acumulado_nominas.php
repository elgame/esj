    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/nomina_fiscal/nominas_empleados_pdf/'); ?>" method="GET" class="form-search" id="frmrptcproform" target="frame_reporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="dempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="dempresa"
                      value="<?php echo (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: ''); ?>" id="dempresa" class="span12" placeholder="Nombre">
                    <input type="hidden" name="did_empresa" value="<?php echo (isset($empresa->id_empresa)? $empresa->id_empresa: ''); ?>" id="did_empresa">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dregistro_patronal">Registro Patronal: </label>
                  <div class="controls">
                    <select name="dregistro_patronal" id="dregistro_patronal">
                      <option value=""></option>
                      <?php foreach ($registros_patronales as $key => $regp): ?>
                      <option value="<?php echo $regp ?>" <?php echo set_select('dregistro_patronal', $regp, false, (isset($cuenta['info']->registro_patronal)?$cuenta['info']->registro_patronal:'')); ?>><?php echo $regp ?></option>
                      <?php endforeach ?>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dempresa">Fechas</label>
                  <div class="controls">
                    Fecha Ini: <input type="date" name="fechaini" id="fechaini" value="" placeholder=""> <br>
                    Fecha Fin: <input type="date" name="fechaend" id="fechaend" value="" placeholder="">
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
          <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/nomina_fiscal/nominas_empleados_xls'); ?>" class="linksm" target="_blank">
            <i class="icon-table"></i> Excel</a>

          <div class="box-content">
            <div class="row-fluid">
              <iframe id="frame_reporte" name="frame_reporte" src="<?php echo base_url('panel/nomina_fiscal/nominas_empleados_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>
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