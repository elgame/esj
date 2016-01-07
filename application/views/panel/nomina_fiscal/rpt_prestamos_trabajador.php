    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/nomina_fiscal/rpt_pdf/'); ?>" method="GET" id="form-search" class="form-search" target="rdeReporte">
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
                  <label class="control-label" for="ftrabajador">Trabajador</label>
                  <div class="controls">
                    <input type="text" name="ftrabajador" class="span12" id="ftrabajador" value="" data-next="fid_trabajador">
                    <input type="hidden" name="fid_trabajador" class="span12" id="fid_trabajador" value="" data-next="ffecha1">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ftodos">Todos los prestamos</label>
                  <div class="controls">
                    <input type="checkbox" name="ftodos" id="ftodos" value="1">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="dempresa"
                      value="<?php echo (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: ''); ?>" id="dempresa" class="span12" placeholder="Nombre">
                    <input type="hidden" name="did_empresa" value="<?php echo (isset($empresa->id_empresa)? $empresa->id_empresa: ''); ?>" id="did_empresa">
                  </div>
                </div>

                <!-- <div class="control-group">
                  <label class="control-label" for="farea">Area</label>
                  <div class="controls">
                    <select name="farea" id="farea" class="span12" data-next="btn_submit">
                      <?php /*foreach ($areas['areas'] as $area) {
                        if($area->predeterminado == 't')
                          $areadefa = $area->id_area; */
                      ?>
                        <?php /* echo '<option value="'.$area->id_area.'">'.$area->nombre.'</option>'  */?>
                      <?php /*}*/ ?>
                    </select>
                  </div>
                </div> -->

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
              <iframe name="rdeReporte" id="iframe-reporte" class="span12" src="<?php echo base_url('panel/nomina_fiscal/rpt_pdf/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->
          <!-- content ends -->
    </div><!--/#content.span10-->