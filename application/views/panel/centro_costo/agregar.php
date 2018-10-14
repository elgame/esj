    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/centro_costo/'); ?>">Centros de costos</a> <span class="divider">/</span>
          </li>
          <li>Agregar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-plus"></i> Agregar centro de costo</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/centro_costo/'.(isset($method)? $method: 'agregar') ); ?>" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>

                <div class="span12">

                  <div class="control-group">
                    <label class="control-label" for="nombre">Nombre </label>
                    <div class="controls">
                      <input type="text" name="nombre" id="nombre" class="span10" maxlength="100"
                      value="<?php echo set_value('nombre'); ?>" required placeholder="centro de costo">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="tipo">Tipo </label>
                    <div class="controls">
                      <select name="tipo" id="tipo">
                        <option value="gasto" <?php echo set_select('tipo', 'gasto') ?>>Gasto</option>
                        <option value="banco" <?php echo set_select('tipo', 'banco') ?>>Banco</option>
                        <option value="melga" <?php echo set_select('tipo', 'melga') ?>>Melga</option>
                        <option value="tabla" <?php echo set_select('tipo', 'tabla') ?>>Tabla</option>
                        <option value="seccion" <?php echo set_select('tipo', 'seccion') ?>>Sección</option>
                      </select>
                    </div>
                  </div>

                  <?php
                    $show_lote = 'hide';
                    if ($this->input->post('tipo') == 'melga' || $this->input->post('tipo') == 'tabla' ||
                      $this->input->post('tipo') == 'seccion') {
                      $show_lote = '';
                    }
                  ?>
                  <div id="is_lotes" class="<?php echo $show_lote ?>">
                    <div class="control-group">
                      <label class="control-label" for="farea">Área </label>
                      <div class="controls">
                      <input type="text" name="farea" id="farea" class="span10" value="<?php echo set_value('farea'); ?>" placeholder="Limon, Piña">
                      <input type="hidden" name="did_area" value="<?php echo set_value('did_area'); ?>" id="did_area">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="hectareas">Hectáreas </label>
                      <div class="controls">
                        <input type="number" step="any" name="hectareas" id="hectareas" class="span10" maxlength="100"
                        value="<?php echo set_value('hectareas'); ?>" placeholder="5, 6">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="no_plantas">No de plantas </label>
                      <div class="controls">
                        <input type="number" step="any" name="no_plantas" id="no_plantas" class="span10" maxlength="100"
                        value="<?php echo set_value('no_plantas'); ?>" placeholder="100, 500">
                      </div>
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/centro_costo/'); ?>" class="btn">Cancelar</a>
                </div>
              </fieldset>
            </form>

          </div>
        </div><!--/span-->

      </div><!--/row-->


          <!-- content ends -->
    </div><!--/#content.span10-->



<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->

