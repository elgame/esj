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
                      <input type="text" name="nombre" id="nombre" class="span10" maxlength="100" autofocus
                      value="<?php echo set_value('nombre'); ?>" required placeholder="centro de costo">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="cuenta_cpi">Cuenta Contpaq </label>
                    <div class="controls">
                      <input type="text" name="cuenta_cpi" id="cuenta_cpi" class="span10" maxlength="15"
                      value="<?php echo set_value('cuenta_cpi'); ?>" placeholder="cuenta contpaq">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="tipo">Tipo </label>
                    <div class="controls">
                      <select name="tipo" id="tipo">
                        <option value="gasto" <?php echo set_select('tipo', 'gasto') ?>>Gasto</option>
                        <option value="servicio" <?php echo set_select('tipo', 'servicio') ?>>Servicio</option>
                        <option value="banco" <?php echo set_select('tipo', 'banco') ?>>Banco</option>
                        <option value="gastofinanciero" <?php echo set_select('tipo', 'gastofinanciero') ?>>Gastos financieros</option>
                        <option value="resultado" <?php echo set_select('tipo', 'resultado') ?>>Resultado (ejercicios)</option>
                        <option value="creditobancario" <?php echo set_select('tipo', 'creditobancario') ?>>Créditos bancarios</option>
                        <option value="otrosingresos" <?php echo set_select('tipo', 'otrosingresos') ?>>Otros ingresos</option>
                        <option value="impuestoxpagar" <?php echo set_select('tipo', 'impuestoxpagar') ?>>Impuestos por pagar</option>
                        <option value="productofinanc" <?php echo set_select('tipo', 'productofinanc') ?>>Productos financieros</option>
                        <option value="impuestoafavor" <?php echo set_select('tipo', 'impuestoafavor') ?>>Impuestos a favor</option>
                        <option value="costosventa" <?php echo set_select('tipo', 'costosventa') ?>>Costo de venta</option>
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
                      <label class="control-label" for="codigo">Código </label>
                      <div class="controls">
                        <input type="text" step="any" name="codigo" id="codigo" class="span10" maxlength="50"
                        value="<?php echo set_value('codigo'); ?>" placeholder="4, 5, 6">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="farea">Cultivo </label>
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

                  <?php
                    $show_credito = 'hide';
                    if ($this->input->post('tipo') == 'creditobancario') {
                      $show_credito = '';
                    }
                  ?>
                  <div id="is_credito" class="<?php echo $show_credito ?>">
                    <div class="control-group">
                      <label class="control-label" for="anios_credito">Años del crédito </label>
                      <div class="controls">
                        <input type="number" name="anios_credito" id="anios_credito" class="span10" maxlength="100"
                        value="<?php echo set_value('anios_credito'); ?>" placeholder="1, 5">
                      </div>
                    </div>
                  </div>

                  <?php
                    $show_cuenta = 'hide';
                    if ($this->input->post('tipo') == 'banco') {
                      $show_cuenta = '';
                    }
                  ?>
                  <div id="is_cuenta" class="<?php echo $show_cuenta ?>">
                    <div class="control-group">
                      <label class="control-label" for="fempresa">Empresa </label>
                      <div class="controls">
                      <input type="text" name="fempresa" id="fempresa" class="span10" value="<?php echo set_value('fempresa', $empresa->nombre_fiscal); ?>" placeholder="Nombre">
                      <input type="hidden" name="did_empresa" value="<?php echo set_value('did_empresa', $empresa->id_empresa); ?>" id="did_empresa">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="cuenta">Cuenta de banco </label>
                      <div class="controls">
                      <input type="text" name="cuenta" id="cuenta" class="span10" value="<?php echo set_value('cuenta'); ?>" placeholder="Aleas, Banco">
                      <input type="hidden" name="id_cuenta" value="<?php echo set_value('id_cuenta', $empresa->id_empresa); ?>" id="id_cuenta">
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

