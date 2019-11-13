    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/compras_ordenes/rpt_gastos_pdf/'); ?>" method="GET" class="form-search" target="rpfReporte" id="form">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="ffecha1">Del</label>
                  <div class="controls">
                    <input type="date" name="ffecha1" class="input-medium search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m').'-01'); ?>" size="10">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="ffecha2">Al</label>
                  <div class="controls">
                    <input type="date" name="ffecha2" class="input-medium search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', date('Y-m-d')); ?>" size="10">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dproducto">Area de costo</label>
                  <div style="height:190px;overflow-y: scroll;background-color:#eee;">
                    <?php echo $vehiculos; ?>
                  </div>
                </div>

                <div class="control-group">
                  <div class="span4">
                    <label class="control-label" for="ddesglosado">Desclosado</label>
                    <div class="controls">
                      <input type="checkbox" name="ddesglosado" id="ddesglosado" value="1">
                    </div>
                  </div>
                  <div class="span4">
                    <label class="control-label" for="dmovimientos">Con movimientos</label>
                    <div class="controls">
                      <input type="checkbox" name="dmovimientos" id="dmovimientos" value="1">
                    </div>
                  </div>
                  <div class="clearfix"></div>
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
          <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/compras_ordenes/rpt_gastos_xls'); ?>" class="linksm" target="_blank">
            <i class="icon-table"></i> Excel</a>

          <div class="box-content">
            <div class="row-fluid">
              <iframe name="rpfReporte" id="iframe-reporte" class="span12"
                src="<?php echo base_url('panel/compras_ordenes/rpt_gastos_pdf')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
