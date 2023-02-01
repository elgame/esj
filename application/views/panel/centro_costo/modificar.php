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
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar centro de costo</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/centro_costo/modificar/?id='.$_GET['id']); ?>" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>

                <?php $data = $centro_costo['info']; ?>

                <div class="span12">

                  <div class="control-group">
                    <label class="control-label" for="nombre">Nombre </label>
                    <div class="controls">
                      <input type="text" name="nombre" id="nombre" class="span10" maxlength="100"
                      value="<?php echo isset($data->nombre)? $data->nombre:''; ?>" required placeholder="centro de costo">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="cuenta_cpi">Cuenta Contpaq </label>
                    <div class="controls">
                      <input type="text" name="cuenta_cpi" id="cuenta_cpi" class="span10" maxlength="15"
                      value="<?php echo isset($data->cuenta_cpi)? $data->cuenta_cpi:''; ?>" placeholder="cuenta contpaq">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="tipo">Tipo </label>
                    <div class="controls">
                      <select name="tipo" id="tipo">
                        <option value="gasto" <?php echo set_select('tipo', 'gasto', false, (isset($data->tipo)? $data->tipo: '')) ?>>Gasto</option>
                        <option value="servicio" <?php echo set_select('tipo', 'servicio', false, (isset($data->tipo)? $data->tipo: '')) ?>>Servicio</option>
                        <option value="banco" <?php echo set_select('tipo', 'banco', false, (isset($data->tipo)? $data->tipo: '')) ?>>Banco</option>
                        <option value="gastofinanciero" <?php echo set_select('tipo', 'gastofinanciero', false, (isset($data->tipo)? $data->tipo: '')) ?>>Gastos financieros</option>
                        <option value="resultado" <?php echo set_select('tipo', 'resultado', false, (isset($data->tipo)? $data->tipo: '')) ?>>Resultado (ejercicios)</option>
                        <option value="creditobancario" <?php echo set_select('tipo', 'creditobancario', false, (isset($data->tipo)? $data->tipo: '')) ?>>Créditos bancarios</option>
                        <option value="otrosingresos" <?php echo set_select('tipo', 'otrosingresos', false, (isset($data->tipo)? $data->tipo: '')) ?>>Otros ingresos</option>
                        <option value="impuestoxpagar" <?php echo set_select('tipo', 'impuestoxpagar', false, (isset($data->tipo)? $data->tipo: '')) ?>>Impuestos por pagar</option>
                        <option value="productofinanc" <?php echo set_select('tipo', 'productofinanc', false, (isset($data->tipo)? $data->tipo: '')) ?>>Productos financieros</option>
                        <option value="impuestoafavor" <?php echo set_select('tipo', 'impuestoafavor', false, (isset($data->tipo)? $data->tipo: '')) ?>>Impuestos a favor</option>
                        <option value="costosventa" <?php echo set_select('tipo', 'costosventa', false, (isset($data->tipo)? $data->tipo: '')) ?>>Costo de venta</option>
                        <option value="melga" <?php echo set_select('tipo', 'melga', false, (isset($data->tipo)? $data->tipo: '')) ?>>Melga</option>
                        <option value="tabla" <?php echo set_select('tipo', 'tabla', false, (isset($data->tipo)? $data->tipo: '')) ?>>Tabla</option>
                        <option value="seccion" <?php echo set_select('tipo', 'seccion', false, (isset($data->tipo)? $data->tipo: '')) ?>>Sección</option>
                      </select>
                    </div>
                  </div>

                  <?php
                    $show_lote = 'hide';
                    if ($data->tipo == 'melga' || $data->tipo == 'tabla' || $data->tipo == 'seccion') {
                      $show_lote = '';
                    }
                  ?>
                  <div id="is_lotes" class="<?php echo $show_lote ?>">
                    <div class="control-group">
                      <label class="control-label" for="codigo">Código </label>
                      <div class="controls">
                        <input type="text" step="any" name="codigo" id="codigo" class="span10" maxlength="50"
                        value="<?php echo isset($data->codigo)? $data->codigo:''; ?>" placeholder="4, 5, 6">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="empresaId">Empresa </label>
                      <div class="controls">
                      <?php echo $this->session->userdata('selempresaname'); ?>
                      <input type="hidden" name="empresaId" value="<?php echo $this->session->userdata('selempresa'); ?>" id="empresaId">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="farea">Cultivo </label>
                      <div class="controls">
                      <input type="text" name="farea" id="farea" class="span10" value="<?php echo isset($data->area)? $data->area->nombre:''; ?>" placeholder="Limon, Piña">
                      <input type="hidden" name="did_area" value="<?php echo isset($data->area)? $data->area->id_area:''; ?>" id="did_area">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="rancho">Rancho </label>
                      <div class="controls">
                      <input type="text" name="rancho" id="rancho" class="span10" value="<?php echo isset($data->rancho)? $data->rancho->nombre:''; ?>" placeholder="Limon, Piña">
                      <input type="hidden" name="ranchoId" value="<?php echo isset($data->rancho)? $data->rancho->id_rancho:''; ?>" id="ranchoId">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="hectareas">Hectáreas </label>
                      <div class="controls">
                        <input type="number" step="any" name="hectareas" id="hectareas" class="span10" maxlength="100"
                        value="<?php echo isset($data->hectareas)? $data->hectareas:''; ?>" placeholder="5, 6">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="no_plantas">No de plantas </label>
                      <div class="controls">
                        <input type="number" step="any" name="no_plantas" id="no_plantas" class="span10" maxlength="100"
                        value="<?php echo isset($data->no_plantas)? $data->no_plantas:''; ?>" placeholder="100, 500">
                      </div>
                    </div>
                  </div>

                  <?php
                    $show_credito = 'hide';
                    if ($data->tipo == 'creditobancario') {
                      $show_credito = '';
                    }
                  ?>
                  <div id="is_credito" class="<?php echo $show_credito ?>">
                    <div class="control-group">
                      <label class="control-label" for="anios_credito">Años del crédito </label>
                      <div class="controls">
                        <input type="number" name="anios_credito" id="anios_credito" class="span10" maxlength="100"
                        value="<?php echo isset($data->anios_credito)? $data->anios_credito:''; ?>" placeholder="1, 5">
                      </div>
                    </div>
                  </div>

                  <?php
                    $show_cuenta = 'hide';
                    if ($data->tipo == 'banco') {
                      $show_cuenta = '';
                    }
                  ?>
                  <div id="is_cuenta" class="<?php echo $show_cuenta ?>">
                    <div class="control-group">
                      <label class="control-label" for="fempresa">Empresa </label>
                      <div class="controls">
                      <input type="text" name="fempresa" id="fempresa" class="span10" value="<?php echo isset($data->cuenta->nombre_fiscal)? $data->cuenta->nombre_fiscal:''; ?>" placeholder="Nombre">
                      <input type="hidden" name="did_empresa" value="<?php echo isset($data->cuenta->id_empresa)? $data->cuenta->id_empresa:''; ?>" id="did_empresa">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="cuenta">Cuenta de banco </label>
                      <div class="controls">
                      <input type="text" name="cuenta" id="cuenta" class="span10" value="<?php echo isset($data->cuenta->alias)? "{$data->cuenta->banco} - {$data->cuenta->alias}":''; ?>" placeholder="Aleas, Banco">
                      <input type="hidden" name="id_cuenta" value="<?php echo isset($data->cuenta->id_cuenta)? $data->cuenta->id_cuenta:''; ?>" id="id_cuenta">
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


