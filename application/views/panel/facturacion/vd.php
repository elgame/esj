    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/ventas_dia/vd_pdf/'); ?>" method="GET" id="frmverform" class="form-search" target="rdeReporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="ffecha1">Dia</label>
                  <div class="controls">
                    <input type="date" name="ffecha1" class="span12" id="ffecha1" value="<?php echo set_value($this->input->post('ffecha1'), date('Y-m-d')); ?>" autofocus required data-next="ffecha2">
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
            <a href="<?php echo base_url('panel/ventas_dia/vd_xls/'); ?>" id="linkDownXls" data-url="<?php echo base_url('panel/ventas_dia/vd_xls/'); ?>" class="linksm" target="_blank">
              <i class="icon-table"></i> Excel</a>
            <div class="row-fluid">
              <iframe name="rdeReporte" id="rdeReporte" class="span12"
                src="<?php echo base_url('panel/ventas_dia/vd_pdf/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
