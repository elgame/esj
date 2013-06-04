    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/usuarios/'); ?>">Usuarios</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar usuario</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/usuarios/modificar/?id='.$_GET['id']); ?>" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>

                <div class="span7">
                  <div class="control-group">
                    <label class="control-label" for="fnombre">*Nombre </label>
                    <div class="controls">
                      <input type="text" name="fnombre" id="fnombre" class="span6" value="<?php echo isset($data['info'][0]->nombre)?$data['info'][0]->nombre:''; ?>" maxlength="110" placeholder="Usuario" autofocus >
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fusuario">*Usuario </label>
                    <div class="controls">
                      <input type="text" name="fusuario" id="fusuario" class="span6" value="<?php echo isset($data['info'][0]->usuario)?$data['info'][0]->usuario:''; ?>" maxlength="10" placeholder="correo@gmail.com">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fpass">*Password </label>
                    <div class="controls">
                      <input type="text" name="fpass" id="fpass" class="span6" value="<?php echo isset($data['info'][0]->password)?$data['info'][0]->password:''; ?>" maxlength="32" placeholder="********">
                    </div>
                  </div>

                  <div class="control-group tipo3">
                    <label class="control-label" for="ftipo">*Tipo de Usuario </label>
                    <div class="controls">
                      <select name="ftipo" id="ftipo">
                        <option value="admin" <?php echo set_select('ftipo', 'admin', false, $data['info'][0]->tipo); ?>>ADMINISTRADOR</option>
                        <option value="usuario" <?php echo set_select('ftipo', 'usuario', false, $data['info'][0]->tipo); ?>>USUARIO</option>
                      </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="femail">Email </label>
                    <div class="controls">
                      <input type="text" name="femail" id="femail" class="span6" value="<?php echo isset($data['info'][0]->email)?$data['info'][0]->email:''; ?>" maxlength="70" placeholder="correo@gmail.com">
                    </div>
                  </div>
                </div> <!--/span-->

                <div class="span4">
                  <div class="control-group">
                    <label class="control-label" style="width: 100px;">Privilegios </label>
                    <div class="controls" style="margin-left: 120px;">
                      <div id="list_privilegios" style="height: 300px; overflow-y: auto; border:1px #ddd solid;">
                        <?php
                          if($this->usuarios_model->tienePrivilegioDe('', 'privilegios/index/')){
                            echo $this->usuarios_model->getFrmPrivilegios(0, true, (isset($data['privilegios'])? $data['privilegios']: array()));
                          }
                        ?>
                      </div>
                    </div>
                  </div>
                </div> <!--/span-->

                <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/usuarios/'); ?>" class="btn">Cancelar</a>
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


