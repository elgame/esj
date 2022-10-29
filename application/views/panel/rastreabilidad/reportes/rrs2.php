    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/rastreabilidad/rrs2_pdf/'); ?>" method="GET" id="form-search" class="form-search" target="rdeReporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="ffecha1">Fecha Ini</label>
                  <div class="controls">
                    <input type="date" name="ffecha1" class="span12" id="ffecha1" value="<?php echo set_value($this->input->post('ffecha1'), date('Y-m-').'01'); ?>" autofocus required data-next="ffecha2">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ffecha2">Fecha Fin</label>
                  <div class="controls">
                    <input type="date" name="ffecha2" class="span12" id="ffecha2" value="<?php echo set_value($this->input->post('ffecha2'), date('Y-m-d')); ?>" required data-next="farea">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="farea">Area</label>
                  <div class="controls">
                    <select name="farea" id="farea" class="span12" required data-next="btn_submit">
                      <?php foreach ($areas['areas'] as $area) { 
                      ?>
                        <?php echo '<option value="'.$area->id_area.'" '.($area_default==$area->id_area? 'selected': '').'>'.$area->nombre.'</option>' ?>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fproducto">Proveedores</label>
                  <div class="controls">
                    <input type="text" name="dproveedor" value="" id="dproveedor" class="span12" placeholder="Buscar">
                    <input type="hidden" name="did_proveedor" value="" id="did_proveedor">
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
          <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/rastreabilidad/rrs2_xls'); ?>" class="linksm" target="_blank">
            <i class="icon-table"></i> Excel</a>

          <div class="box-content">
            <div class="row-fluid">
              <iframe name="rdeReporte" id="iframe-reporte" class="span12" 
                src="<?php echo base_url('panel/rastreabilidad/rrs2_pdf/?farea='.$area_default)?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
