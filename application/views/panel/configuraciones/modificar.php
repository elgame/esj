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
                    <label class="control-label" for="dzona_a">UMA</label>
                    <div class="controls">
                      <input type="text" name="dzona_a" id="dzona_a" class="vpositive"
                        value="<?php echo isset($data['salarios_minimos']->zona_a)?$data['salarios_minimos']->zona_a:''; ?>" placeholder="UMA" required>
                      <span class="help-inline">Pesos</span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dzona_b">Salario mínimo </label>
                    <div class="controls">
                      <input type="text" name="dzona_b" id="dzona_b" class="vpositive"
                        value="<?php echo isset($data['salarios_minimos']->zona_b)?$data['salarios_minimos']->zona_b:''; ?>" placeholder="Salario mínimo" required>
                      <span class="help-inline">Pesos</span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dzona_anio">Año </label>
                    <div class="controls">
                      <input type="text" name="dzona_anio" id="dzona_anio" class="vpositive"
                        value="<?php echo isset($data['salarios_minimos']->anio)? $data['salarios_minimos']->anio: ''; ?>" placeholder="Año salario" required>
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="clearfix"></div>

                <div class="span12 nomarg">

                  <table class="table table-condensed table-bordered bootstrap-datatable">
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

                <div class="row-fluid">
                  <div class="span6">
                    <fieldset>
                      <legend>Tablas ISR</legend>

                        <table class="table table-condensed table-bordered bootstrap-datatable">
                          <caption>Semanal</caption>
                          <thead>
                            <tr>
                              <th>Límite Inferior</th>
                              <th>Límite Superior</th>
                              <th>Cuota Fija</th>
                              <th>%</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            foreach($data['semanal_art113'] as $art){
                            ?>
                            <tr>
                              <td style="width:40px;">
                                <input type="hidden" name="sem_id[]" value="<?php echo $art->id_art_113 ?>">
                                <input type="text" name="sem_lim_inferior[]" value="<?php echo $art->lim_inferior ?>" class="span12 vpositive"></td>
                              <td style="width:40px;"><input type="text" name="sem_lim_superior[]" value="<?php echo $art->lim_superior ?>" class="span12 vpositive"></td>
                              <td style="width:40px;"><input type="text" name="sem_cuota_fija[]" value="<?php echo $art->cuota_fija ?>" class="span12 vpositive"></td>
                              <td style="width:40px;"><input type="text" name="sem_porcentaje[]" value="<?php echo $art->porcentaje ?>" class="span12 vpositive"></td>
                            </tr>
                            <?php
                            } ?>
                          </tbody>
                        </table>

                        <table class="table table-condensed table-bordered bootstrap-datatable">
                          <caption>Diaria</caption>
                          <thead>
                            <tr>
                              <th>Límite Inferior</th>
                              <th>Límite Superior</th>
                              <th>Cuota Fija</th>
                              <th>%</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            foreach($data['diaria_art113'] as $art){
                            ?>
                            <tr>
                              <td style="width:40px;">
                                <input type="hidden" name="dia_id[]" value="<?php echo $art->id_art_113 ?>">
                                <input type="text" name="dia_lim_inferior[]" value="<?php echo $art->lim_inferior ?>" class="span12 vpositive"></td>
                              <td style="width:40px;"><input type="text" name="dia_lim_superior[]" value="<?php echo $art->lim_superior ?>" class="span12 vpositive"></td>
                              <td style="width:40px;"><input type="text" name="dia_cuota_fija[]" value="<?php echo $art->cuota_fija ?>" class="span12 vpositive"></td>
                              <td style="width:40px;"><input type="text" name="dia_porcentaje[]" value="<?php echo $art->porcentaje ?>" class="span12 vpositive"></td>
                            </tr>
                            <?php
                            } ?>
                          </tbody>
                        </table>

                    </fieldset>
                  </div>

                  <div class="span6">
                    <fieldset>
                      <legend>Tablas Subsidios</legend>

                        <table class="table table-condensed table-bordered bootstrap-datatable">
                          <caption>Semanal</caption>
                          <thead>
                            <tr>
                              <th>Límite Inferior</th>
                              <th>Límite Superior</th>
                              <th>Subsidio</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            foreach($data['semanal_subsidios'] as $art){
                            ?>
                            <tr>
                              <td style="width:40px;">
                                <input type="hidden" name="sub_sem_id[]" value="<?php echo $art->id_subsidio ?>">
                                <input type="text" name="sub_sem_lim_inferior[]" value="<?php echo $art->de ?>" class="span12 vpositive"></td>
                              <td style="width:40px;"><input type="text" name="sub_sem_lim_superior[]" value="<?php echo $art->hasta ?>" class="span12 vpositive"></td>
                              <td style="width:40px;"><input type="text" name="sub_sem_subsidio[]" value="<?php echo $art->subsidio ?>" class="span12 vpositive"></td>
                            </tr>
                            <?php
                            } ?>
                          </tbody>
                        </table>

                        <table class="table table-condensed table-bordered bootstrap-datatable">
                          <caption>Diarios</caption>
                          <thead>
                            <tr>
                              <th>Límite Inferior</th>
                              <th>Límite Superior</th>
                              <th>Subsidio</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            foreach($data['diaria_subsidios'] as $art){
                            ?>
                            <tr>
                              <td style="width:40px;">
                                <input type="hidden" name="sub_dia_id[]" value="<?php echo $art->id_subsidio ?>">
                                <input type="text" name="sub_dia_lim_inferior[]" value="<?php echo $art->de ?>" class="span12 vpositive"></td>
                              <td style="width:40px;"><input type="text" name="sub_dia_lim_superior[]" value="<?php echo $art->hasta ?>" class="span12 vpositive"></td>
                              <td style="width:40px;"><input type="text" name="sub_dia_subsidio[]" value="<?php echo $art->subsidio ?>" class="span12 vpositive"></td>
                            </tr>
                            <?php
                            } ?>
                          </tbody>
                        </table>

                    </fieldset>
                  </div>
                </div>

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


