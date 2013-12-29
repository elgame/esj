    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>Configuraciones</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Configuraciones</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/home/configuraciones/'); ?>" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>

                <div class="span6">
                  <div class="control-group">
                    <label class="control-label" for="daguinaldo">Aguinaldo </label>
                    <div class="controls">
                      <input type="text" name="daguinaldo" id="daguinaldo" class="vpos-int" 
                        value="<?php echo isset($data['conf']->aguinaldo)?$data['conf']->aguinaldo:''; ?>" placeholder="Aguinaldo" autofocus required>
                      <span class="help-inline">dias</span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dprima_vacacional">Prima Vacacional </label>
                    <div class="controls">
                      <input type="text" name="dprima_vacacional" id="dprima_vacacional" class="vpositive" 
                        value="<?php echo isset($data['conf']->prima_vacacional)?$data['conf']->prima_vacacional:''; ?>" placeholder="Prima Vacacional" required>
                      <span class="help-inline">%</span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dpuntualidad">Puntualidad </label>
                    <div class="controls">
                      <input type="text" name="dpuntualidad" id="dpuntualidad" class="vpositive" 
                        value="<?php echo isset($data['conf']->puntualidad)?$data['conf']->puntualidad:''; ?>" placeholder="Puntualidad" required>
                      <span class="help-inline">%</span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dasistencia">Asistencia </label>
                    <div class="controls">
                      <input type="text" name="dasistencia" id="dasistencia" class="vpositive" 
                        value="<?php echo isset($data['conf']->asistencia)?$data['conf']->asistencia:''; ?>" placeholder="Asistencia" required>
                      <span class="help-inline">%</span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="ddespensa">Despensa </label>
                    <div class="controls">
                      <input type="text" name="ddespensa" id="ddespensa" class="vpositive" 
                        value="<?php echo isset($data['conf']->despensa)?$data['conf']->despensa:''; ?>" placeholder="Despensa" required>
                      <span class="help-inline">%</span>
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="span5">
                  
                  <div class="control-group">
                    <label class="control-label" for="dzona_a">Salari Zona A</label>
                    <div class="controls">
                      <input type="text" name="dzona_a" id="dzona_a" class="vpositive" 
                        value="<?php echo isset($data['salarios_minimos']->zona_a)?$data['salarios_minimos']->zona_a:''; ?>" placeholder="Salari Zona A" required>
                      <span class="help-inline">Pesos</span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dzona_b">Salari Zona B </label>
                    <div class="controls">
                      <input type="text" name="dzona_b" id="dzona_b" class="vpositive" 
                        value="<?php echo isset($data['salarios_minimos']->zona_b)?$data['salarios_minimos']->zona_b:''; ?>" placeholder="Salari Zona B" required>
                      <span class="help-inline">Pesos</span>
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="clearfix"></div>

                <div class="span12 nomarg">
                  
                  <table class="table table-striped table-bordered bootstrap-datatable">
                    <caption>Dias de Vacaciones por años trabajados</caption>
                    <tbody>
                      <?php 
                      $html_fields = $html_header = '';
                      foreach($data['conf_vacaciones'] as $vaca){ 
                          $html_fields .= '<td style="width:40px;">
                            <input type="hidden" name="anio1[]" value="'.$vaca->anio1.'">
                            <input type="hidden" name="anio2[]" value="'.$vaca->anio2.'">
                            <input type="text" name="dias[]" value="'.$vaca->dias.'" class="span12 vpos-int">
                          </td>';
                          $html_header .= '<td>'.$vaca->anio1.' a '.$vaca->anio2.' Años (Dias)</td>';
                      } ?>
                        <tr>
                          <?php echo $html_header ?>
                        </tr>
                        <tr>
                          <?php echo $html_fields ?>
                        </tr>
                    </tbody>
                  </table>

                </div> <!--/span-->

                <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/usuarios/'); ?>" class="btn">Cancelar</a>
                </div>
              </fieldset>
            </form>

          </div>
        </div><!--/span-->

      </div><!--/row-->


          <!-- content ends -->
    </div><!--/#content.span10-->



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


