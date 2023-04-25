    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/bascula/rpt_boletas_porpagar_pdf/'); ?>" method="get" id="form" class="form-search" target="rdeReporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <div class="controls">
                    <label for="ffecha1" class="pull-left span12">De <input type="date" name="ffecha1" class="span12 getjsval" id="ffecha1"
                      value="<?php echo date('Y-m-').'01'; ?>"></label>
                    <br />
                    <label for="ffecha2" class="pull-left span12">Hasta <input type="date" name="ffecha2" class="span12 getjsval" id="ffecha2"
                      value="<?php echo date('Y-m-d'); ?>"></label>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="name">Area</label>
                  <div class="controls">
                    <select name="farea" id="farea" class="span12 getjsval" required>
                      <option value=""></option>
                      <?php foreach ($areas['areas'] as $area) { ?>
                        <?php echo '<option value="'.$area->id_area.'">'.$area->nombre.'</option>' ?>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="fempresa"
                      value="<?php echo set_value('fempresa') ?>" id="fempresa" class="span12 getjsval" placeholder="Empresa">
                    <input type="hidden" name="fid_empresa" value="<?php echo set_value('fid_empresa') ?>" id="fid_empresa" class="getjsval">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fstatus">Status</label>
                  <div class="controls">
                    <select name="fstatus" id="fstatus" class="getjsval">
                      <option value="en">PENDIENTES</option>
                      <option value="pb">PAGADOS</option>
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
          <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/bascula/rpt_boletas_porpagar_xls'); ?>" class="linksm" target="_blank">
            <i class="icon-table"></i> Excel</a>

          <div class="box-content">
            <div class="row-fluid">
              <iframe name="rdeReporte" id="rdeReporte" class="span12" src="<?php echo base_url('panel/bascula/rpt_boletas_porpagar_pdf/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
