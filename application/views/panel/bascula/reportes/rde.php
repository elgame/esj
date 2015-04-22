    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/bascula/rde_pdf/'); ?>" method="get" id="form" class="form-search" target="rdeReporte">
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
                  <label class="control-label" for="name">Area</label>
                  <div class="controls">
                    <select name="farea" id="farea" class="span12 getjsval" required>
                      <option value=""></option>
                      <option value="all">Todas</option>
                      <?php foreach ($areas['areas'] as $area) { ?>
                        <?php echo '<option value="'.$area->id_area.'">'.$area->nombre.'</option>' ?>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fcalidad">Calidades</label>
                  <div class="controls">
                    <select name="fcalidad" id="fcalidad" class="span12">
                      <option value=""></option>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ftipo">Tipo</label>
                  <div class="controls">
                    <select name="ftipo" id="ftipo" class="getjsval">
                      <option value="en">ENTRADA</option>
                      <option value="sa">SALIDA</option>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label autocomplet_en" for="fproveedor">Proveedor</label>
                  <label class="control-label autocomplet_sa" for="fproveedor">Cliente</label>
                  <div class="controls">
                    <input type="text" name="fproveedor"
                      value="<?php echo set_value('fproveedor', $this->input->post('fproveedor')) ?>" id="fproveedor" class="span12 getjsval" placeholder="Buscar">
                    <input type="hidden" name="fid_proveedor" value="<?php echo set_value('fid_proveedor', $this->input->post('fid_proveedor')) ?>" id="fid_proveedor" class="getjsval">
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
                      <option value="">TODOS</option>
                      <option value="1">PAGADOS</option>
                      <option value="2">NO PAGADOS</option>
                    </select>
                    <input type="checkbox" name="fefectivo" id="fefectivo" value="si"> <label for="fefectivo">Efectivo</label>
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary btn-large span12">Enviar</button>
                </div>
                <a href="" id="linkXls" class="linksm pull-right" data-href="<?php echo base_url('panel/bascula/rde_xls/'); ?>">
                    <i class="icon-table"></i> Excel</a>

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
          <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/bascula/rdefull_xls'); ?>" class="linksm" target="_blank">
            <i class="icon-table"></i> Excel</a>
          <div class="box-content">
            <div class="row-fluid">
              <iframe name="rdeReporte" id="rdeReporte" class="span12" src="<?php echo base_url('panel/bascula/rde_pdf/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
