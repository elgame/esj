    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/rastreabilidad/rrp_pdf/'); ?>" method="GET" id="form-search" class="form-search" target="rdeReporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="ffecha1">Del</label>
                  <div class="controls">
                    <input type="date" name="ffecha1" class="span12" id="ffecha1" value="<?php echo set_value($this->input->post('ffecha1'), date('Y-m-d')); ?>" autofocus data-next="ffecha2">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="ffecha2">Al</label>
                  <div class="controls">
                    <input type="date" name="ffecha2" class="span12" id="ffecha2" value="<?php echo set_value($this->input->post('ffecha2'), date('Y-m-d')); ?>" data-next="farea">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="farea">Area</label>
                  <div class="controls">
                    <select name="farea" id="farea" class="span12" data-next="fcalidad">
                      <?php foreach ($areas['areas'] as $area) { ?>
                        <?php echo '<option value="'.$area->id_area.'">'.$area->nombre.'</option>' ?>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fcalidad">Calidad</label>
                  <div class="controls">
                    <select name="fcalidad" id="fcalidad" class="span12" data-next="ftipo">
                      <?php 
                      $calidad_1 = '';
                      foreach ($calidades['calidades'] as $key => $calidad) { 
                        if($key == 0) $calidad_1 = $calidad->id_calidad;
                      ?>
                        <?php echo '<option value="'.$calidad->id_calidad.'">'.$calidad->nombre.'</option>' ?>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" id="btn_submit" class="btn btn-primary btn-large span12">Enviar</button>
                </div>

              </div>
            </form> <!-- /form -->
          </div>
        </div><!--/span12 -->
      </div><!--/row-fluid -->
    </div><!-- /span2 -->

    <div id="content" class="span9">
      <!-- content starts -->

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <div class="row-fluid">
              <iframe name="rdeReporte" id="iframe-reporte" class="span12" 
                src="<?php echo base_url('panel/rastreabilidad/rrp_pdf/?fcalidad='.$calidad_1)?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
