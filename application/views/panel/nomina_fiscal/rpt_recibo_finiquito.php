    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/nomina_fiscal/recibo_finiquito_pdf/'); ?>" method="GET" id="form-search" class="form-search" target="rdeReporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="ftrabajador">Trabajador</label>
                  <div class="controls">
                    <input type="text" name="ftrabajador" class="span12" id="ftrabajador" value="" required>
                    <input type="hidden" name="fid_trabajador" class="span12" id="fid_trabajador" value="" required>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fsalario_real">Salario Diaro</label>
                  <div class="controls">
                    <input type="text" name="fsalario_real" class="span12 vpositive" id="fsalario_real" value="">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ffecha1">Fecha Entrada</label>
                  <div class="controls">
                    <input type="date" name="ffecha1" class="span12" id="ffecha1" value="<?php echo set_value($this->input->post('ffecha1')); ?>" data-next="ffecha2">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ffecha2">Fecha Salida</label>
                  <div class="controls">
                    <input type="date" name="ffecha2" class="span12" id="ffecha2" value="<?php echo set_value($this->input->post('ffecha2'), date('Y-m-d')); ?>" data-next="farea">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="indem_cons">Indemnización constitucional</label>
                  <div class="controls">
                    <input type="checkbox" name="indem_cons" id="indem_cons" value="true" <?php echo $this->input->get('indem_cons')=='true'? 'checked': '';  ?>>
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="indem">Indemnización</label>
                  <div class="controls">
                    <input type="checkbox" name="indem" id="indem" value="true" <?php echo $this->input->get('indem')=='true'? 'checked': '';  ?>>
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="prima">Prima de antigüedad</label>
                  <div class="controls">
                    <input type="checkbox" name="prima" id="prima" value="true" <?php echo $this->input->get('prima')=='true'? 'checked': '';  ?>>
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
              <iframe name="rdeReporte" id="iframe-reporte" class="span12" src="<?php echo base_url('panel/nomina_fiscal/recibo_finiquito_pdf/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->
          <!-- content ends -->
    </div><!--/#content.span10-->