    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/empleados/'); ?>">Empleados</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar empleados</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/empleados/modificar/?id='.$_GET['id']); ?>" method="post" class="form-horizontal">
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

                  <!-- <div class="control-group">
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
                  </div> -->

                  <div class="control-group">
                    <label class="control-label" for="festa_asegurado">Esta asegurado? </label>
                    <div class="controls">
                      <input type="checkbox" name="festa_asegurado" id="festa_asegurado" value="t" data-uniform="false"
                        <?php echo set_checkbox('festa_asegurado', 't', isset($data['info'][0]->esta_asegurado)?($data['info'][0]->esta_asegurado=='t'?true:false): false); ?>>
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
                      <label class="control-label" for="ffecha_imss">Fecha Alta IMSS </label>
                      <div class="controls">
                        <input type="date" name="ffecha_imss" id="ffecha_imss" class="span6" value="<?php echo isset($data['info'][0]->fecha_imss)?$data['info'][0]->fecha_imss:''; ?>" maxlength="25" placeholder="Fecha IMSS">
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
                    <label class="control-label" for="frfc">RFC </label>
                    <div class="controls">
                      <input type="text" name="frfc" id="frfc" class="span12"
                        value="<?php echo isset($data['info'][0]->rfc)?$data['info'][0]->rfc:''; ?>" pattern=".{12,13}" placeholder="RFC">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fcurp">CURP </label>
                    <div class="controls">
                      <input type="text" name="fcurp" id="fcurp" class="span12"
                        value="<?php echo isset($data['info'][0]->curp)?$data['info'][0]->curp:''; ?>" maxlength="30" placeholder="CURP">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fempresa">Empresa </label>
                    <div class="controls">
                    <input type="text" name="fempresa" id="fempresa" class="span12" value="<?php echo isset($data['info'][0]->nombre_fiscal)?$data['info'][0]->nombre_fiscal:''; ?>" placeholder="Nombre" required>
                    <input type="hidden" name="did_empresa" value="<?php echo isset($data['info'][0]->id_empresa)?$data['info'][0]->id_empresa:''; ?>" id="did_empresa" required>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fdepartamente">Departamento </label>
                    <div class="controls">
                    <select name="fdepartamente" id="fdepartamente">
                    <?php foreach ($departamentos['puestos'] as $key => $value)
                    {
                    ?>
                      <option value="<?php echo $value->id_departamento ?>"
                        <?php echo set_select('fdepartamente', $value->id_departamento, false, (isset($data['info'][0]->id_departamente)?$data['info'][0]->id_departamente:'')); ?>><?php echo $value->nombre ?></option>
                    <?php
                    } ?>
                    </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fpuesto">Puesto </label>
                    <div class="controls">
                    <select name="fpuesto" id="fpuesto">
                    <?php foreach ($puestos['puestos'] as $key => $value)
                    {
                    ?>
                      <option value="<?php echo $value->id_puesto ?>"
                        <?php echo set_select('fpuesto', $value->id_puesto, false, (isset($data['info'][0]->id_puesto)?$data['info'][0]->id_puesto:'')); ?>><?php echo $value->nombre." ({$value->abreviatura})" ?></option>
                    <?php
                    } ?>
                    </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fsalario_diario">Salario diario </label>
                    <div class="controls">
                      <input type="text" name="fsalario_diario" id="fsalario_diario" class="span12 vpositive"
                        value="<?php echo isset($data['info'][0]->salario_diario)?$data['info'][0]->salario_diario:''; ?>" maxlength="12" placeholder="Salario de nomina fiscal">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fsalario_diario_real">Salario diario real </label>
                    <div class="controls">
                      <input type="text" name="fsalario_diario_real" id="fsalario_diario_real" class="span12 vpositive"
                        value="<?php echo isset($data['info'][0]->salario_diario_real)?$data['info'][0]->salario_diario_real:''; ?>" maxlength="12" placeholder="Salario de nomina real">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="finfonavit">Infonavit </label>
                    <div class="controls">
                      <input type="text" name="finfonavit" id="finfonavit" class="span12 vpositive"
                        value="<?php echo isset($data['info'][0]->infonavit)?$data['info'][0]->infonavit:''; ?>" maxlength="12" placeholder="Infonavit">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fregimen_contratacion">Regimen contratacion </label>
                    <div class="controls">
                    <select name="fregimen_contratacion" id="fregimen_contratacion">
                      <option value="2" <?php echo set_select('fregimen_contratacion', '2', false, (isset($data['info'][0]->regimen_contratacion)?$data['info'][0]->regimen_contratacion:'')); ?>>Sueldos y salarios</option>
                      <option value="3" <?php echo set_select('fregimen_contratacion', '3', false, (isset($data['info'][0]->regimen_contratacion)?$data['info'][0]->regimen_contratacion:'')); ?>>Jubilados</option>
                      <option value="4" <?php echo set_select('fregimen_contratacion', '4', false, (isset($data['info'][0]->regimen_contratacion)?$data['info'][0]->regimen_contratacion:'')); ?>>Pensionados</option>
                    </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dcuenta_banco">Cuenta Banco </label>
                    <div class="controls">
                      <input type="text" name="dcuenta_banco" id="dcuenta_banco" class="span12 vpositive" value="<?php echo isset($data['info'][0]->cuenta_banco)?$data['info'][0]->cuenta_banco:''; ?>" maxlength="12" placeholder="Cuenta Banco">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dno_seguro">No Seguro </label>
                    <div class="controls">
                      <input type="text" name="dno_seguro" id="dno_seguro" class="span12 vpositive" value="<?php echo isset($data['info'][0]->no_seguro)?$data['info'][0]->no_seguro:''; ?>" maxlength="12" placeholder="# Seguro">
                    </div>
                  </div>

                  <input type="hidden" name="duser_nomina" value="t">

                </div> <!--/span-->

                <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/empleados/'); ?>" class="btn">Cancelar</a>
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


