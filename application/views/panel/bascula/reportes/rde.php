    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/bascula/rde_pdf/'); ?>" method="GET" class="form-search" target="rdeReporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="fechaini">Dia</label>
                  <div class="controls">
                    <input type="text" name="fechaini" class="span12" id="fechaini" value="<?php echo set_value($this->input->post('fechaini'), date('Y-m-d')); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="name">Area</label>
                  <div class="controls">
                    <select name="farea" class="span12">
                      <?php foreach ($areas['areas'] as $area) { ?>
                        <?php echo '<option value="'.$area->id_area.'">'.$area->nombre.'</option>' ?>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fproveedor">Proveedor</label>
                  <div class="controls">
                    <input type="text" name="fproveedor"
                      value="<?php echo set_value('fproveedor', $this->input->post('fproveedor')) ?>" id="fproveedor" class="span12" placeholder="Proveedor">
                    <input type="text" name="fid_proveedor" value="<?php echo set_value('fid_proveedor', $this->input->post('fid_proveedor')) ?>" id="fid_proveedor">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="fempresa"
                      value="<?php echo set_value('fempresa') ?>" id="fempresa" class="span12" placeholder="Empresa">
                    <input type="text" name="fid_empresa" value="<?php echo set_value('fid_empresa') ?>" id="fid_empresa">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fstatus">Status</label>
                  <div class="controls">
                    <select name="fstatus">
                      <option value="">TODOS</option>
                      <option value="1">PAGADOS</option>
                      <option value="2">NO PAGADOS</option>
                    </select>
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary btn-large span12">Enviar</button>
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
              <iframe name="rdeReporte" id="iframe-reporte" class="span12" src="<?php echo base_url('panel/bascula/rde_pdf/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->