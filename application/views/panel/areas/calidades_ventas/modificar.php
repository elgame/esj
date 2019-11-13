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
            <h2><i class="icon-edit"></i> Modificar calidad</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">

            <form action="<?php echo base_url('panel/areas_otros/modificar_calidad/?'.String::getVarsLink(array('msg', 'fstatus'))); ?>" method="post" class="form-horizontal">
              <div class="control-group">
                <label class="control-label" for="fnombre">Nombre </label>
                <div class="controls">
                  <input type="text" name="fnombre" id="fnombre" class="span6" maxlength="20"
                  value="<?php echo (isset($data['info']->nombre)? $data['info']->nombre: ''); ?>" required autofocus placeholder="300, MIX, PREM, 1ra">
                </div>
              </div>

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

              <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="<?php echo base_url('panel/areas_otros/modificar/?id='.$this->input->get('idarea')); ?>" class="btn">Cancelar</a>
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


