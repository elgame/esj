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
                  value="<?php echo set_value('fnombre'); ?>" required autofocus placeholder="Limon verde 500, Limon verde 300">
                </div>
              </div>

              <!-- <div class="control-group">
                <label class="control-label" for="fprecio_venta">Precio de venta </label>
                <div class="controls">
                  <input type="text" name="fprecio_venta" id="fprecio_venta" class="span6 vpositive" maxlength="11" 
                  value="<?php echo set_value('fprecio_venta'); ?>" required placeholder="4.4, 33">
                </div>
              </div> -->

              <!-- <div class="control-group">
                <label class="control-label" for="fcuenta_cpi">Cuenta contpaq </label>
                <div class="controls">
                  <input type="text" name="fcuenta_cpi" id="fcuenta_cpi" class="span6 vpositive" maxlength="11" 
                  value="<?php echo set_value('fcuenta_cpi'); ?>" required placeholder="123212, 332123">
                </div>
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
