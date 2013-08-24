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

            <form action="<?php echo base_url('panel/areas/modificar_clasificacion/?'.String::getVarsLink(array('msg', 'fstatus'))); ?>" method="post" class="form-horizontal">
              <div class="control-group">
                <label class="control-label" for="fnombre">Nombre </label>
                <div class="controls">
                  <input type="text" name="fnombre" id="fnombre" class="span6" maxlength="40" 
                  value="<?php echo (isset($data['info']->nombre)? $data['info']->nombre: ''); ?>" required autofocus placeholder="Limon verde, limon industrial">
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
                  value="<?php echo (isset($data['info']->cuenta_cpi)? $data['info']->cuenta_cpi: ''); ?>" required placeholder="123212, 332123">
                </div>
              </div>

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


