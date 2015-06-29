    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/nomina_fiscal/recibo_incapacidad_pdf/'); ?>" method="GET" id="form-search" class="form-search" target="rdeReporte">
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
                  <label class="control-label" for="ffecha_inicio">Inicio a partir de</label>
                  <div class="controls">
                    <input type="date" name="ffecha_inicio" class="span12" id="ffecha_inicio" value="<?php echo set_value($this->input->post('ffecha_inicio')); ?>" data-next="ffecha2">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fdias_incapacidad">Dias de Incapacidad</label>
                  <div class="controls">
                    <input type="text" name="fdias_incapacidad" class="span12 vpositive" id="fdias_incapacidad" value="<?php echo set_value($this->input->post('fdias_incapacidad'), 0); ?>" data-next="">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fincapacidad_seguro">Incapacidad pagada por el Seguro</label>
                  <div class="controls">
                    <input type="text" name="fincapacidad_seguro" class="span12 vpositive" id="fincapacidad_seguro" value="<?php echo set_value($this->input->post('fincapacidad_seguro'), 0); ?>" data-next="">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fporcentaje">Incapacidad pagada por el Seguro</label>
                  <div class="controls">
                    <input type="number" step="any" name="fporcentaje" class="span12 vpositive" id="fporcentaje" value="100" data-next="">
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
              <iframe name="rdeReporte" id="iframe-reporte" class="span12" src="<?php echo base_url('panel/nomina_fiscal/recibo_incapacidad_pdf/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->
          <!-- content ends -->
    </div><!--/#content.span10-->