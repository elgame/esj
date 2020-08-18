    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/bascula/rpt_ent_pina_pdf/'); ?>" method="get" id="form" class="form-search" target="rdeReporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <div class="controls">
                    <label for="ffecha1" class="pull-left span6">Del <input type="date" name="ffecha1" class="span12" id="ffecha1"
                      value="<?php echo date('Y-m-d'); ?>"></label>
                    <label for="ffecha2" class="pull-left span6">Al <input type="date" name="ffecha2" class="span12" id="ffecha2"
                      value="<?php echo date('Y-m-d'); ?>"></label>
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
                  <label class="control-label" for="rancho">Rancho</label>
                  <div class="controls">
                    <input type="text" name="rancho" id="rancho" class="span12"
                      value="<?php echo set_value('rancho'); ?>" placeholder="Milagro A" data-next="icantidad">
                    <input type="hidden" name="ranchoId" id="ranchoId"
                      value="<?php echo set_value('ranchoId') ?>">
                  </div>
                </div>

                <div class="control-group" id="centrosCostosGrup">
                  <label class="control-label" for="centroCosto">Centro de costo </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="centroCosto" class="span11" id="centroCosto" value="<?php echo set_value('centroCosto') ?>" placeholder="Mantenimiento, Gasto general">
                    </div>
                  </div>
                  <ul class="tags" id="tagsCCIds">
                  <?php if (isset($_GET['centroCostoId'])) {
                    foreach ($_GET['centroCostoId'] as $key => $centroCostoId) { ?>
                      <li><span class="tag"><?php echo $_GET['centroCostoText'][$key] ?></span>
                        <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="<?php echo $centroCostoId ?>">
                        <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="<?php echo $_GET['centroCostoText'][$key] ?>">
                      </li>
                   <?php }} ?>
                  </ul>
                  <div style="clear: both;"></div>
                </div><!--/control-group -->

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
          <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/bascula/rpt_ent_pina_xls'); ?>" class="linksm" target="_blank">
            <i class="icon-table"></i> Excel</a>

          <div class="box-content">
            <div class="row-fluid">
              <iframe name="rdeReporte" id="rdeReporte" class="span12" src="<?php echo base_url('panel/bascula/rpt_ent_pina_pdf/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
