    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form id="form" action="<?php echo base_url('panel/estado_resultado_trans/rpt_estado_results_pdf/'); ?>" method="GET" class="form-search" target="frame_reporte">
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
                      value="<?php echo (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: ''); ?>" id="dempresa" class="span12" placeholder="Nombre">
                    <input type="hidden" name="did_empresa" value="<?php echo (isset($empresa->id_empresa)? $empresa->id_empresa: ''); ?>" id="did_empresa">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dempresa">Tipo Flete</label>
                  <div class="controls">
                    <select name="dtipo" class="span9" id="dtipo">
                      <?php foreach ($tipos as $tipo => $text): ?>
                      <option value="<?php echo $tipo ?>" <?php echo set_select('dtipo', $tipo, false, (!empty($borrador['info']->tipo_flete) ? $borrador['info']->tipo_flete : $this->input->get('dtipo'))); ?>><?php echo $text ?></option>
                      <?php endforeach ?>
                    </select>
                  </div>
                </div>

                <div class="control-group" id="activosGrup">
                  <label class="control-label" for="activos">Activos </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="activos" class="span11" id="activos" value="<?php echo set_value('activos') ?>" placeholder="Nissan FRX, Maquina limon">
                    </div>
                    <input type="hidden" name="activoId" id="activoId" value="<?php echo set_value('activoId') ?>">
                  </div>
                </div><!--/control-group -->

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary btn-large span12">Enviar</button>
                </div>

              </div>
            </form> <!-- /form -->

          </div>
        </div><!--/span12 -->
      </div><!--/row-fluid -->
    </div><!-- /span3 -->

    <div id="content" class="span9">
      <!-- content starts -->

      <div class="box span12">
        <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/estado_resultado_trans/rpt_estado_results_xls'); ?>" class="linksm" target="_blank">
          <i class="icon-table"></i> Excel</a>
        <div class="box-content">
          <div class="row-fluid">
            <iframe id="frame_reporte" name="frame_reporte" src="<?php echo base_url('panel/estado_resultado_trans/rpt_estado_results_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>
          </div>
        </div>
      </div><!--/span-->

    </div><!--/#content.span9-->

