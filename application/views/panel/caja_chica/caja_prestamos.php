
  <div class="row-fluid">
    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid center">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/caja_chica_prest/cargar/'); ?>" method="GET" id="form-search" class="form-search" target="rdeReporte">
              <label class="control-label" for="ffecha">Fecha</label>
              <input type="date" name="ffecha" class="input-medium search-query" id="ffecha" value="<?php echo set_value($this->input->post('ffecha'), date('Y-m-d')); ?>" data-next="ffecha2">
              <input type="hidden" name="fno_caja" value="1">
              <button type="submit" id="btn_submit" class="btn btn-primary">Enviar</button>
            </form> <!-- /form -->
          </div>
        </div><!--/span12 -->
      </div><!--/row-fluid -->
    </div><!-- /span2 -->

  </div>

  <div class="row-fluid">
    <div id="content" class="span12">
      <!-- content starts -->

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <div class="row-fluid">
              <iframe name="rdeReporte" id="iframe-reporte" class="span12" src="<?php echo base_url('panel/caja_chica_prest/cargar/?fno_caja=1')?>" style="height:600px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->
          <!-- content ends -->
    </div><!--/#content.span10-->
  </div>
