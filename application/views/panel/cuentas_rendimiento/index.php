
  <div class="row-fluid">
    <div class="span8">
      <!-- content starts -->
      <div class="row-fluid center">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/cuentas_rendimiento/cargar/'); ?>" method="GET" id="form-search" class="form-search" target="rdeReporte">
              <label class="control-label" for="ffecha">Fecha</label>
              <input type="date" name="ffecha" class="input-medium search-query" id="ffecha" value="<?php echo set_value($this->input->post('ffecha'), date('Y-m-d')); ?>" data-next="ffecha2">

              <label class="control-label" for="farea">Fecha</label>
              <select name="farea">
              <?php foreach ($areas as $key => $value) { ?>
                <option value="<?php echo $value->id_area; ?>" <?php echo ($adefault==$value->id_area? 'selected': '') ?>><?php echo $value->nombre ?></option>
              <?php } ?>
              </select>
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
              <iframe name="rdeReporte" id="iframe-reporte" class="span12" src="<?php echo base_url('panel/cuentas_rendimiento/cargar/')?>" style="height:600px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->
          <!-- content ends -->
    </div><!--/#content.span10-->
  </div>
