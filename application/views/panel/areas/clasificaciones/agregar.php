    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/areas/'); ?>">Areas</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/areas/modificar/?id='.$this->input->get('id')); ?>">Atras</a> <span class="divider">/</span>
          </li>
          <li>Agregar</li>
        </ul>
      </div>


      <div class="row-fluid">

        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-plus"></i> Agregar clasificacion</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">

            <form action="<?php echo base_url('panel/areas/agregar_clasificacion/?'.String::getVarsLink(array('msg', 'fstatus'))); ?>" method="post" class="form-horizontal">
              <div class="control-group">
                <label class="control-label" for="fnombre">Nombre </label>
                <div class="controls">
                  <input type="text" name="fnombre" id="fnombre" class="span6" maxlength="40"
                  value="<?php echo set_value('fnombre'); ?>" autofocus required placeholder="Limon verde 500, Limon verde 300">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fcodigo">Codigo </label>
                <div class="controls">
                  <input type="text" name="fcodigo" id="fcodigo" class="span6" maxlength="15"
                  value="<?php echo set_value('fcodigo'); ?>" placeholder="AL2, EXT, 500">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="diva">IVA </label>
                <div class="controls">
                  <select name="diva" id="diva" class="span3">
                    <option value="0" <?php echo set_select('diva', '0'); ?>>0%</option>
                    <option value="11" <?php echo set_select('diva', '11'); ?>>11%</option>
                    <option value="16" <?php echo set_select('diva', '16'); ?>>16%</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dunidad">Unidad / Medida </label>
                <div class="controls">
                  <select name="dunidad" id="dunidad" class="span3">
                    <?php foreach ($unidades as $key => $u) { ?>
                      <option value="<?php echo $u->id_unidad ?>" <?php echo set_select('dunidad', $u->id_unidad); ?>><?php echo $u->nombre ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dinventario">Inventario </label>
                <div class="controls">
                  <input type="checkbox" name="dinventario" id="dinventario" value="t" <?php echo set_checkbox('dinventario', 't'); ?>>
                </div>
              </div>

              <!-- <div class="control-group">
                <label class="control-label" for="fprecio_venta">Precio de venta </label>
                <div class="controls">
                  <input type="text" name="fprecio_venta" id="fprecio_venta" class="span6 vpositive" maxlength="11"
                  value="<?php echo set_value('fprecio_venta'); ?>" required placeholder="4.4, 33">
                </div>
              </div> -->

              <div class="control-group">
                <label class="control-label" for="fcuenta_cpi">Cuenta contpaq </label>
                <div class="controls">
                  <input type="text" name="fcuenta_cpi" id="fcuenta_cpi" class="span6 vpositive" maxlength="11"
                  value="<?php echo set_value('fcuenta_cpi'); ?>" placeholder="123212, 332123">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fcuenta_cpi2">Cuenta contpaq 2 (Orov)</label>
                <div class="controls">
                  <input type="text" name="fcuenta_cpi2" id="fcuenta_cpi2" class="span6 vpositive" maxlength="11"
                  value="<?php echo set_value('fcuenta_cpi2'); ?>" placeholder="123212, 332123">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dclave_producto">Clave de Productos/Servicios </label>
                <div class="controls">
                  <input type="text" name="dclave_producto" class="span9" id="dclave_producto" value="<?php echo set_value('dclave_producto'); ?>" size="73">
                  <input type="hidden" name="dclave_producto_cod" class="span9" id="dclave_producto_cod" value="<?php echo set_value('dclave_producto_cod'); ?>" size="73">
                </div>
              </div>

              <!-- <div class="control-group">
                <label class="control-label" for="dclave_unidad">Clave de unidad </label>
                <div class="controls">
                  <input type="text" name="dclave_unidad" class="span9" id="dclave_unidad" value="<?php echo set_value('dclave_unidad'); ?>" size="73">
                  <input type="hidden" name="dclave_unidad_cod" class="span9" id="dclave_unidad_cod" value="<?php echo set_value('dclave_unidad_cod'); ?>" size="73">
                </div>
              </div> -->

              <!-- <div class="control-group">
                <label class="control-label" for="fcalibres">Calibres Seleccionados</label>
                <div class="controls" id="list-calibres">
                  <!-- <label><input type="checkbox" name="fcalibres[]" value="1" class="sel-calibres"><input type="hidden" name="fcalibre_nombre[]" value="Calibre 1">Calibre 1</label> -->

                  <?php /*
                    if (isset($_POST['fcalibres'])) {
                      foreach ($_POST['fcalibres'] as $key => $value) { ?>
                        <label><input type="checkbox" name="fcalibres[]" value="<?php echo $value ?>" class="sel-calibres" checked><input type="hidden" name="fcalibre_nombre[]" value="<?php echo $_POST['fcalibre_nombre'][$key] ?>"><?php echo $_POST['fcalibre_nombre'][$key] ?></label>
                  <?php }} */?>
                <!--</div>
              </div> -->

              <input type="hidden" name="farea" id="farea" value="<?php echo $this->input->get('id'); ?>">

              <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="<?php echo base_url('panel/areas/modificar/?id='.$this->input->get('id')); ?>" class="btn">Cancelar</a>
              </div>
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
