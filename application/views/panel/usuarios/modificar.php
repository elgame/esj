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
                    <label class="control-label" for="fnombre">Nombre </label>
                    <div class="controls">
                      <input type="text" name="fnombre" id="fnombre" class="span6" 
                        value="<?php echo isset($data['info'][0]->nombre)?$data['info'][0]->nombre:''; ?>" maxlength="90" placeholder="Usuario" autofocus required>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fapellido_paterno">Apellido paterno </label>
                    <div class="controls">
                      <input type="text" name="fapellido_paterno" id="fapellido_paterno" class="span6" 
                        value="<?php echo isset($data['info'][0]->apellido_paterno)?$data['info'][0]->apellido_paterno:''; ?>" maxlength="25" placeholder="Apellido paterno" >
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fapellido_materno">Apellido materno </label>
                    <div class="controls">
                      <input type="text" name="fapellido_materno" id="fapellido_materno" class="span6" 
                        value="<?php echo isset($data['info'][0]->apellido_materno)?$data['info'][0]->apellido_materno:''; ?>" maxlength="25" placeholder="Apellido materno" >
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fusuario">Usuario </label>
                    <div class="controls">
                      <input type="text" name="fusuario" id="fusuario" class="span6" value="<?php echo isset($data['info'][0]->usuario)?$data['info'][0]->usuario:''; ?>" maxlength="30" placeholder="correo@gmail.com">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fpass">Password </label>
                    <div class="controls">
                      <input type="text" name="fpass" id="fpass" class="span6" value="<?php echo isset($data['info'][0]->password)?$data['info'][0]->password:''; ?>" maxlength="32" placeholder="********">
                    </div>
                  </div>

                  <fieldset>
                    <legend>Domicilio</legend>
                    <div class="control-group">
                      <label class="control-label" for="fcalle">Calle </label>
                      <div class="controls">
                        <input type="text" name="fcalle" id="fcalle" class="span6" 
                          value="<?php echo isset($data['info'][0]->calle)?$data['info'][0]->calle:''; ?>" maxlength="60" placeholder="calle">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="fnumero">Numero </label>
                      <div class="controls">
                        <input type="text" name="fnumero" id="fnumero" class="span6" 
                          value="<?php echo isset($data['info'][0]->numero)?$data['info'][0]->numero:''; ?>" maxlength="7" placeholder="numero">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="fcolonia">Colonia </label>
                      <div class="controls">
                        <input type="text" name="fcolonia" id="fcolonia" class="span6" 
                          value="<?php echo isset($data['info'][0]->colonia)?$data['info'][0]->colonia:''; ?>" maxlength="60" placeholder="colonia">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="fmunicipio">Municipio </label>
                      <div class="controls">
                        <input type="text" name="fmunicipio" id="fmunicipio" class="span6" 
                          value="<?php echo isset($data['info'][0]->municipio)?$data['info'][0]->municipio:''; ?>" maxlength="45" placeholder="municipio">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="festado">Estado </label>
                      <div class="controls">
                        <input type="text" name="festado" id="festado" class="span6" 
                          value="<?php echo isset($data['info'][0]->estado)?$data['info'][0]->estado:''; ?>" maxlength="45" placeholder="estado">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="fcp">Codigo Postal </label>
                      <div class="controls">
                        <input type="text" name="fcp" id="fcp" class="span6" 
                          value="<?php echo isset($data['info'][0]->cp)?$data['info'][0]->cp:''; ?>" maxlength="12" placeholder="codigo postal">
                      </div>
                    </div>
                  </fieldset>

                  <fieldset>
                    <legend>Otros</legend>

                    <div class="control-group">
                      <label class="control-label" for="ffecha_nacimiento">Fecha de nacimiento </label>
                      <div class="controls">
                        <input type="date" name="ffecha_nacimiento" id="ffecha_nacimiento" class="span6" 
                          value="<?php echo isset($data['info'][0]->fecha_nacimiento)?$data['info'][0]->fecha_nacimiento:''; ?>" maxlength="25" placeholder="Fecha de nacimiento">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="ffecha_entrada">Fecha de entrada </label>
                      <div class="controls">
                        <input type="date" name="ffecha_entrada" id="ffecha_entrada" class="span6" 
                          value="<?php echo isset($data['info'][0]->fecha_entrada)?$data['info'][0]->fecha_entrada:''; ?>" maxlength="25" placeholder="Fecha de entrada">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="ffecha_salida">Fecha de salida </label>
                      <div class="controls">
                        <input type="date" name="ffecha_salida" id="ffecha_salida" class="span6" 
                          value="<?php echo isset($data['info'][0]->fecha_salida)?$data['info'][0]->fecha_salida:''; ?>" maxlength="25" placeholder="Fecha de salida">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="fnacionalidad">Nacionalidad </label>
                      <div class="controls">
                        <input type="text" name="fnacionalidad" id="fnacionalidad" class="span6" 
                          value="<?php echo isset($data['info'][0]->nacionalidad)?$data['info'][0]->nacionalidad:''; ?>" maxlength="20" placeholder="Nacionalidad">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="festado_civil">Estado civil </label>
                      <div class="controls">
                        <select name="festado_civil" id="festado_civil">
                          <option value="Soltero" <?php echo set_select('festado_civil', 'Soltero', false, (isset($data['info'][0]->estado_civil)?$data['info'][0]->estado_civil:'') ); ?>>Soltero</option>
                          <option value="Casado" <?php echo set_select('festado_civil', 'Casado', false, (isset($data['info'][0]->estado_civil)?$data['info'][0]->estado_civil:'') ); ?>>Casado</option>
                          <option value="Divorciado" <?php echo set_select('festado_civil', 'Divorciado', false, (isset($data['info'][0]->estado_civil)?$data['info'][0]->estado_civil:'') ); ?>>Divorciado</option>
                          <option value="Viudo" <?php echo set_select('festado_civil', 'Viudo', false, (isset($data['info'][0]->estado_civil)?$data['info'][0]->estado_civil:'') ); ?>>Viudo</option>
                          <option value="Union libre" <?php echo set_select('festado_civil', 'Union libre', false, (isset($data['info'][0]->estado_civil)?$data['info'][0]->estado_civil:'') ); ?>>Union libre</option>
                        </select>
                      </div>
                    </div>

                    <div class="control-group tipo3">
                      <label class="control-label" for="fsexo">Sexo </label>
                      <div class="controls">
                        <select name="fsexo" id="fsexo">
                          <option value="h" <?php echo set_select('fsexo', 'h', false, (isset($data['info'][0]->sexo)?$data['info'][0]->sexo:'') ); ?>>Masculino</option>
                          <option value="m" <?php echo set_select('fsexo', 'm', false, (isset($data['info'][0]->sexo)?$data['info'][0]->sexo:'') ); ?>>Femenino</option>
                        </select>
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="femail">Email </label>
                      <div class="controls">
                        <input type="text" name="femail" id="femail" class="span6" 
                          value="<?php echo isset($data['info'][0]->email)?$data['info'][0]->email:''; ?>" maxlength="70" placeholder="correo@gmail.com">
                      </div>
                    </div>

                    <div class="control-group">
                      <label class="control-label" for="fcuenta_cpi">Cuenta contpaq </label>
                      <div class="controls">
                        <input type="text" name="fcuenta_cpi" id="fcuenta_cpi" class="span6" 
                          value="<?php echo isset($data['info'][0]->cuenta_cpi)?$data['info'][0]->cuenta_cpi:''; ?>" maxlength="12" placeholder="Cuenta contpaq">
                      </div>
                    </div>

                  </fieldset>
                </div> <!--/span-->

                <div class="span4">
                  <div class="control-group">
                    <label class="control-label" style="width: 100px;">Privilegios </label>
                    <div class="controls" style="margin-left: 120px;">
                      <div id="list_privilegios" style="height: 500px; overflow-y: auto; border:1px #ddd solid;">
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


