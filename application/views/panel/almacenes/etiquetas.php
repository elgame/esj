    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/etiquetas/etiqueta_pdf/'); ?>" method="GET" class="form-search" target="rpfReporte" id="form">
              <div class="form-actions form-filters">

                <div class="control-group" style="display: none;">
                  <label class="control-label" for="tipo">Tipo</label>
                  <div class="controls">
                    <select name="tipo">
                      <option value="1">1</option>
                    </select>
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

                <div class="control-group">
                  <label class="control-label" for="caja">Folio Caja</label>
                  <div class="controls">
                    <input type="number" name="caja" class="input-medium search-query" id="caja" value="<?php echo set_value_get('caja'); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="rollos">No Rollos</label>
                  <div class="controls">
                    <input type="number" name="rollos" class="input-medium search-query" id="rollos" value="<?php echo set_value_get('caja'); ?>">
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
            <div class="row-fluid">
              <iframe name="rpfReporte" id="iframe-reporte" class="span12"
                src="<?php echo base_url('panel/etiquetas/etiqueta_pdf')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
