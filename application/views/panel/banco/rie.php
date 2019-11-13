    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/banco/rie_pdf/'); ?>" method="GET" id="frmverform" class="form-search" target="rdeReporte">
              <div class="form-actions form-filters">

                <div class="control-group span6">
                  <label class="control-label" for="ffecha1">Del</label>
                  <div class="controls">
                    <input type="date" name="ffecha1" class="span11" id="ffecha1" value="<?php echo isset($_GET['ffecha1']) ? $_GET['ffecha1'] : date('Y-m-01'); ?>">
                  </div>
                </div>

                <div class="control-group span6">
                  <label class="control-label" for="ffecha2">Al</label>
                  <div class="controls">
                    <input type="date" name="ffecha2" class="span11" id="ffecha2" value="<?php echo isset($_GET['ffecha2']) ? $_GET['ffecha2'] : date('Y-m-d'); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="dempresa"
                      value="EMPAQUE SAN JORGE SA DE CV" id="dempresa" class="span12" placeholder="Nombre">
                    <input type="hidden" name="did_empresa" value="2" id="did_empresa">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ftipo">Tipo</label>
                  <div class="controls">
                    <select name="ftipo" id="ftipo" class="span12" required data-next="btn_submit">
                      <option value="a">Ingreso/Egreso</option>
                      <option value="e">Egreso</option>
                      <option value="i">Ingreso</option>
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
            <a href="<?php echo base_url('panel/banco/rie_xls/'); ?>" id="linkDownXls" data-url="<?php echo base_url('panel/banco/rie_xls/'); ?>" class="linksm" target="_blank">
              <i class="icon-table"></i> Excel</a>
            <div class="row-fluid">
              <iframe name="rdeReporte" id="rdeReporte" class="span12"
                src="<?php echo base_url('panel/banco/rie_pdf/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
