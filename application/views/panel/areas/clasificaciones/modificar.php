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
            <a href="<?php echo base_url('panel/areas/modificar/?id='.$this->input->get('idarea')); ?>">Atras</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>


      <div class="row-fluid">

        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar clasificacion</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">

            <form action="<?php echo base_url('panel/areas/modificar_clasificacion/?'.MyString::getVarsLink(array('msg', 'fstatus'))); ?>" method="post" class="form-horizontal">
              <div class="control-group">
                <label class="control-label" for="fnombre">Nombre </label>
                <div class="controls">
                  <input type="text" name="fnombre" id="fnombre" class="span6" maxlength="100"
                  value="<?php echo (isset($data['info']->nombre)? $data['info']->nombre: ''); ?>" required autofocus placeholder="Limon verde, limon industrial">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fcodigo">Codigo </label>
                <div class="controls">
                  <input type="text" name="fcodigo" id="fcodigo" class="span6" maxlength="15"
                  value="<?php echo (isset($data['info']->codigo)? $data['info']->codigo: ''); ?>" placeholder="AL2, EXT, 500">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fnombre">IVA </label>
                <div class="controls">
                  <select name="diva" id="diva" class="span3">
                    <option value="0" <?php echo set_select('diva', '0', $data['info']->iva == '0' ? true : false); ?>>0%</option>
                    <option value="11" <?php echo set_select('diva', '11', $data['info']->iva == '11' ? true : false); ?>>11%</option>
                    <option value="16" <?php echo set_select('diva', '16', $data['info']->iva == '16' ? true : false); ?>>16%</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fnombre">Unidad / Medida </label>
                <div class="controls">
                  <select name="dunidad" id="dunidad" class="span3">
                    <?php foreach ($unidades as $key => $u) { ?>
                      <option value="<?php echo $u->id_unidad ?>" <?php echo set_select('dunidad', $u->id_unidad, $data['info']->id_unidad == $u->id_unidad ? true : false); ?>><?php echo $u->nombre ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dinventario">Inventario </label>
                <div class="controls">
                  <input type="checkbox" name="dinventario" id="dinventario" value="t" <?php echo set_checkbox('dinventario', 't', ($data['info']->inventario=='t'?true:false)); ?>>
                </div>
              </div>

              <!-- <div class="control-group">
                <label class="control-label" for="fprecio_venta">Precio de venta </label>
                <div class="controls">
                  <input type="text" name="fprecio_venta" id="fprecio_venta" class="span6 vpositive" maxlength="11"
                  value="<?php echo (isset($data['info']->precio_venta)? $data['info']->precio_venta: ''); ?>" required placeholder="4.4, 33">
                </div>
              </div> -->

              <div class="control-group tipo3">
                <label class="control-label" for="farea">Area </label>
                <div class="controls">
                  <select name="farea" id="farea">
                <?php
                foreach ($areas['areas'] as $key => $value) {
                ?>
                    <option value="<?php echo $value->id_area; ?>" <?php echo set_select('farea', $value->id_area, false, (isset($data['info']->id_area)? $data['info']->id_area: '') ); ?>><?php echo $value->nombre; ?></option>
                <?php
                }?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fcuenta_cpi">Cuenta contpaq </label>
                <div class="controls">
                  <input type="text" name="fcuenta_cpi" id="fcuenta_cpi" class="span6 vpositive" maxlength="11"
                  value="<?php echo (isset($data['info']->cuenta_cpi)? $data['info']->cuenta_cpi: ''); ?>" placeholder="123212, 332123">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fcuenta_cpi2">Cuenta contpaq 2 (Otras)</label>
                <div class="controls">
                  <div class="span5">
                    <input type="text" name="fcuenta_cpi2" id="fcuenta_cpi2" class="span12 vpositive" maxlength="11"
                    value="" placeholder="123212, 332123">
                  </div>
                  <div class="span5">
                    <input type="text" name="fempresa" id="fempresa" class="span10" value="" placeholder="Empresa" autocomplete="on">
                    <input type="hidden" name="did_empresa" value="" id="did_empresa">
                  </div>
                  <div class="span2">
                    <button type="button"class="btn" id="btnAddCuenta"><i class="icon-plus"></i></button>
                  </div>

                  <div id="listasCuentas" style="margin-top: 10px;clear: both;">
                    <ul>
                      <?php
                      if (is_array($cuentas)) {
                          foreach ($cuentas as $key => $cuenta) { ?>
                        <li><?php echo $cuenta->cuenta.' - '.$cuenta->empresa.' <i class="icon-remove" style="cursor:pointer"></i>' ?>
                          <input type="hidden" name="fcuentas[<?php echo $key ?>][id]" value="<?php echo $cuenta->id ?>" class="id">
                          <input type="hidden" name="fcuentas[<?php echo $key ?>][empresa]" value="<?php echo $cuenta->empresa ?>" class="empresa">
                          <input type="hidden" name="fcuentas[<?php echo $key ?>][cuenta]" value="<?php echo $cuenta->cuenta ?>" class="cuenta">
                        </li>
                        <?php }
                      } ?>
                    </ul>
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dclave_producto">Clave de Productos/Servicios </label>
                <div class="controls">
                  <input type="text" name="dclave_producto" class="span9" id="dclave_producto"
                    value="<?php echo (!empty($data['info']->clave_prod_serv)? $data['cprodserv']->label: ''); ?>" size="73">
                  <input type="hidden" name="dclave_producto_cod" class="span9" id="dclave_producto_cod"
                    value="<?php echo (!empty($data['info']->clave_prod_serv)? $data['cprodserv']->c_clave_prodserv: ''); ?>" size="73">
                </div>
              </div>

              <!-- <div class="control-group">
                <label class="control-label" for="dclave_unidad">Clave de unidad </label>
                <div class="controls">
                  <input type="text" name="dclave_unidad" class="span9" id="dclave_unidad"
                    value="<?php echo (!empty($data['info']->clave_unidad)? $data['cunidad']->label: ''); ?>" size="73">
                  <input type="hidden" name="dclave_unidad_cod" class="span9" id="dclave_unidad_cod"
                    value="<?php echo (!empty($data['info']->clave_unidad)? $data['cunidad']->c_clave_unidad: ''); ?>" size="73">
                </div>
              </div> -->

               <!-- <div class="control-group">
                <label class="control-label" for="fcalibres">Autocomplete Calibres </label>
                <div class="controls">
                  <input type="text" id="auto-calibres" class="span3"
                    value="" placeholder="Nombre del Calibre">
                </div> -->
              </div>

               <!-- <div class="control-group">
                <label class="control-label" for="fcalibres">Calibres Seleccionados</label>
                <div class="controls" id="list-calibres"> -->
                  <!-- <label><input type="checkbox" name="fcalibres[]" value="1" class="sel-calibres"><input type="hidden" name="fcalibre_nombre[]" value="Calibre 1">Calibre 1</label> -->

                  <?php
                    /*if (isset($_POST['fcalibres'])) {
                      foreach ($_POST['fcalibres'] as $key => $value) { ?>
                        <label><input type="checkbox" name="fcalibres[]" value="<?php echo $value ?>" class="sel-calibres" checked><input type="hidden" name="fcalibre_nombre[]" value="<?php echo $_POST['fcalibre_nombre'][$key] ?>"><?php echo $_POST['fcalibre_nombre'][$key] ?></label>
                  <?php }}

                    else if (isset($data['calibres'])) {
                      foreach ($data['calibres'] as $key => $calibre) { ?>
                        <label><input type="checkbox" name="fcalibres[]" value="<?php echo $calibre->id_calibre ?>" class="sel-calibres" checked><input type="hidden" name="fcalibre_nombre[]" value="<?php echo $calibre->nombre ?>"><?php echo $calibre->nombre ?></label>
                  <?php }} */?>

                <!-- </div>
              </div> -->

              <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="<?php echo base_url('panel/areas/modificar/?id='.$this->input->get('idarea')); ?>" class="btn">Cancelar</a>
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


