    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/bitacora/bitacora_pdf/'); ?>" method="get" class="form-search" target="reporte">
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
                  <label class="control-label" for="fseccion">Secciones</label>
                  <div class="controls">
                    <select name="fseccion" class="span12">
                      <option value="">Todo</option>
                      <?php foreach ($secciones as $seccion) { ?>
                        <?php echo '<option value="'.$seccion.'">'.ucfirst($seccion).'</option>' ?>
                      <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ftipo">Tipo</label>
                  <div class="controls">
                    <select name="ftipo">
                      <option value="">Todo</option>
                      <option value="Agregar">Agregar</option>
                      <option value="Editar">Editar</option>
                      <option value="Cancelar">Cancelar</option>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="fempresa"
                      value="<?php echo set_value('fempresa') ?>" id="fempresa" class="span12" placeholder="Empresa">
                    <input type="hidden" name="fid_empresa" value="<?php echo set_value('fid_empresa') ?>" id="fid_empresa">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fusuario">Usuario</label>
                  <div class="controls">
                    <input type="text" name="fusuario"
                      value="<?php echo set_value('fusuario') ?>" id="fusuario" class="span12" placeholder="Usuario">
                    <input type="hidden" name="fid_usuario" value="<?php echo set_value('fid_usuario') ?>" id="fid_usuario">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fdescripcion">Descripcion</label>
                  <div class="controls">
                    <input type="text" name="fdescripcion" value="" class="span12">
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
              <iframe name="reporte" id="reporte" class="span12" src="<?php echo base_url('panel/bitacora/bitacora_pdf/')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->
      <!-- content ends -->
    </div><!--/#content.span10-->
