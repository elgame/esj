    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/polizas/genera_poliza/'); ?>" method="GET" class="form-search" target="rdeReporte">
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
                      value="<?php echo (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: '') ?>" id="fempresa" class="span12" placeholder="Empresa">
                    <input type="hidden" name="fid_empresa" value="<?php echo (isset($empresa->id_empresa)? $empresa->id_empresa: '') ?>" id="fid_empresa">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ftipo">Tipo</label>
                  <div class="controls">
                    <select name="ftipo" id="ftipo">
                      <option value="3">Diario</option>
                      <option value="1">Ingreso</option>
                      <option value="2">Egreso</option>
                    </select>
                  </div>
                </div>
                <div class="control-group" id="grupftipo2">
                  <label class="control-label" for="ftipo2">Opciones</label>
                  <div class="controls">
                    <select name="ftipo2" id="ftipo2">
                      <option value="v">Ventas</option>
                      <option value="vnc">Ventas Notas Credito</option>
                      <option value="g">Gastos</option>
                      <option value="gnc">Gastos Notas Credito</option>
                      <option value="no">Nomina</option>
                      <option value="pr">Productos</option>
                    </select>
                  </div>
                </div>
                <div class="control-group" id="grupftipo3" style="display: none;">
                  <label class="control-label" for="ftipo3">Opciones</label>
                  <div class="controls">
                    <select name="ftipo3" id="ftipo3">
                      <option value="el">Limon</option>
                      <option value="ec">Cheques</option>
                      <option value="eg">Gastos</option>
                    </select>
                  </div>
                </div>

                <div class="control-group" id="grupftipo22" style="display: none;">
                  <label class="control-label" for="ftipo22">Area</label>
                  <div class="controls">
                    <select name="ftipo22" id="ftipo22">
                      <?php foreach ($areas['areas'] as $area) { ?>
                        <?php echo '<option value="'.$area->id_area.'">'.$area->nombre.'</option>' ?>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ffolio">Folio</label>
                  <div class="controls">
                    <input type="text" name="ffolio" id="ffolio" value="<?php echo (isset($folio['folio'])? $folio['folio']: '') ?>" class="span12" placeholder="Folio" required>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fconcepto">Concepto</label>
                  <div class="controls">
                    <textarea name="fconcepto" id="fconcepto" class="span12" maxlength="99" required><?php echo (isset($folio['concepto'])? $folio['concepto']: '') ?></textarea>
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary btn-large span12">Generar</button>
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
              <iframe name="rdeReporte" id="iframe-reporte" class="span12" src="<?php echo base_url('panel/polizas/genera_poliza/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
