    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/empleados/persep_deduc_pdf/'); ?>" method="GET" class="form-search" id="frmverformprod" target="frame_reporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="dempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="dempresa"
                      value="<?php echo (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: ''); ?>" id="dempresa" class="span12" placeholder="Nombre">
                    <input type="hidden" name="did_empresa" value="<?php echo (isset($empresa->id_empresa)? $empresa->id_empresa: ''); ?>" id="did_empresa">
                  </div>
                </div>

                <div class="control-group span6">
                  <label class="control-label" for="anio">Año</label>
                  <div class="controls">
                    <input type="number" name="anio" class="span11" id="anio" value="<?php echo isset($_GET['anio']) ? $_GET['anio'] : date('Y'); ?>">
                  </div>
                </div>
                <div class="clearfix"></div>

                <div class="row-fluid">
                  <div class="control-group span6">
                    <label class="control-label" for="fsemana1">Del</label>
                    <div class="controls">
                      <select name="fsemana1" class="span12" id="fsemana1">
                        <?php foreach ($semanasDelAno as $semana) {
                          ?>
                          <option value="<?php echo $semana['semana'] ?>"><?php echo "{$semana['semana']} - Del {$semana['fecha_inicio']} Al {$semana['fecha_final']}" ?></option>
                        <?php }
                        ?>
                      </select>
                    </div>
                  </div>

                  <div class="control-group span6">
                    <label class="control-label" for="fsemana2">Al</label>
                    <div class="controls">
                      <select name="fsemana2" class="span12" id="fsemana2">
                        <?php
                        $numSemanaSelected = count($semanasDelAno);
                        foreach ($semanasDelAno as $semana) { ?>
                          <option value="<?php echo $semana['semana'] ?>" <?php echo $semana['semana'] == $numSemanaSelected ? 'selected' : '' ?>><?php echo "{$semana['semana']} - Del {$semana['fecha_inicio']} Al {$semana['fecha_final']}" ?></option>
                        <?php }
                        ?>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fempleado">Empleados</label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="fempleado" value="" id="fempleado" class="span9" placeholder="Buscar">
                      <button class="btn" type="button" id="btnAddProducto" style="margin-left:-3px;"><i class="icon-plus-sign"></i></button>
                      <input type="hidden" name="fid_empleado" value="" id="fid_empleado">
                    </div>
                    <div class="clearfix"></div>
                    <div style="height:130px;overflow-y: scroll;background-color:#eee;">
                      <ul id="lista_proveedores" style="list-style: none;margin-left: 4px;">
                      </ul>
                    </div>
                  </div>
                </div>

                <!-- <div class="control-group">
                  <label class="control-label" for="dcon_mov">Con Movimientos</label>
                  <div class="controls">
                    <input type="checkbox" name="dcon_mov" value="si" id="dcon_mov" >
                  </div>
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

      <iframe id="frame_reporte" src="<?php echo base_url('panel/empleados/persep_deduc_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>

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