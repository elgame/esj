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
                      <label class="control-label" for="ffecha_contrato">Fecha vencimiento contrato </label>
                      <div class="controls">
                        <input type="date" name="ffecha_contrato" id="ffecha_contrato" class="span6"
                          value="<?php echo isset($data['info'][0]->fecha_contrato)?$data['info'][0]->fecha_contrato:''; ?>" maxlength="25" placeholder="Fecha vencimiento de contrato">
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
                      <label class="control-label" for="flugar_nacimiento">Lugar de nacimiento </label>
                      <div class="controls">
                        <select name="flugar_nacimiento" id="flugar_nacimiento">
                          <option value=""></option>
                          <option value="Aguascalientes" <?php echo set_select('flugar_nacimiento', 'Aguascalientes', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Aguascalientes</option>
                          <option value="Baja California" <?php echo set_select('flugar_nacimiento', 'Baja California', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Baja California</option>
                          <option value="Baja California Sur" <?php echo set_select('flugar_nacimiento', 'Baja California Sur', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Baja California Sur</option>
                          <option value="Campeche" <?php echo set_select('flugar_nacimiento', 'Campeche', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Campeche</option>
                          <option value="Chiapas" <?php echo set_select('flugar_nacimiento', 'Chiapas', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Chiapas</option>
                          <option value="Chihuahua" <?php echo set_select('flugar_nacimiento', 'Chihuahua', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Chihuahua</option>
                          <option value="Ciudad de México" <?php echo set_select('flugar_nacimiento', 'Ciudad de México', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Ciudad de México</option>
                          <option value="Coahuila" <?php echo set_select('flugar_nacimiento', 'Coahuila', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Coahuila</option>
                          <option value="Colima" <?php echo set_select('flugar_nacimiento', 'Colima', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Colima</option>
                          <option value="Durango" <?php echo set_select('flugar_nacimiento', 'Durango', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Durango</option>
                          <option value="Estado de México" <?php echo set_select('flugar_nacimiento', 'Estado de México', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Estado de México</option>
                          <option value="Guanajuato" <?php echo set_select('flugar_nacimiento', 'Guanajuato', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Guanajuato</option>
                          <option value="Guerrero" <?php echo set_select('flugar_nacimiento', 'Guerrero', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Guerrero</option>
                          <option value="Hidalgo" <?php echo set_select('flugar_nacimiento', 'Hidalgo', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Hidalgo</option>
                          <option value="Jalisco" <?php echo set_select('flugar_nacimiento', 'Jalisco', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Jalisco</option>
                          <option value="Michoacán" <?php echo set_select('flugar_nacimiento', 'Michoacán', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Michoacán</option>
                          <option value="Morelos" <?php echo set_select('flugar_nacimiento', 'Morelos', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Morelos</option>
                          <option value="Nayarit" <?php echo set_select('flugar_nacimiento', 'Nayarit', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Nayarit</option>
                          <option value="Nuevo León" <?php echo set_select('flugar_nacimiento', 'Nuevo León', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Nuevo León</option>
                          <option value="Oaxaca" <?php echo set_select('flugar_nacimiento', 'Oaxaca', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Oaxaca</option>
                          <option value="Puebla" <?php echo set_select('flugar_nacimiento', 'Puebla', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Puebla</option>
                          <option value="Querétaro" <?php echo set_select('flugar_nacimiento', 'Querétaro', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Querétaro</option>
                          <option value="Quintana Roo" <?php echo set_select('flugar_nacimiento', 'Quintana Roo', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Quintana Roo</option>
                          <option value="San Luis Potosí" <?php echo set_select('flugar_nacimiento', 'San Luis Potosí', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>San Luis Potosí</option>
                          <option value="Sinaloa" <?php echo set_select('flugar_nacimiento', 'Sinaloa', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Sinaloa</option>
                          <option value="Sonora" <?php echo set_select('flugar_nacimiento', 'Sonora', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Sonora</option>
                          <option value="Tabasco" <?php echo set_select('flugar_nacimiento', 'Tabasco', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Tabasco</option>
                          <option value="Tamaulipas" <?php echo set_select('flugar_nacimiento', 'Tamaulipas', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Tamaulipas</option>
                          <option value="Tlaxcala" <?php echo set_select('flugar_nacimiento', 'Tlaxcala', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Tlaxcala</option>
                          <option value="Veracruz" <?php echo set_select('flugar_nacimiento', 'Veracruz', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Veracruz</option>
                          <option value="Yucatán" <?php echo set_select('flugar_nacimiento', 'Yucatán', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Yucatán</option>
                          <option value="Zacatecas" <?php echo set_select('flugar_nacimiento', 'Zacatecas', false, (isset($data['info'][0]->lugar_nacimiento)? $data['info'][0]->lugar_nacimiento: '')); ?>>Zacatecas</option>
                        </select>
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
                      <label class="control-label" for="ftelefono">Teléfono </label>
                      <div class="controls">
                        <input type="text" name="ftelefono" id="ftelefono" class="span6"
                          value="<?php echo isset($data['info'][0]->telefono)?$data['info'][0]->telefono:''; ?>" maxlength="20" placeholder="31252235">
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
                    <label class="control-label" for="dregistro_patronal">Registro patronal:</label>
                    <div class="controls">
                      <select name="fregistro_patronal" id="fregistro_patronal">
                        <option></option>
                        <?php foreach ($registros_patronales as $key => $regp): ?>
                        <option value="<?php echo $regp ?>" <?php echo set_select('fregistro_patronal', $regp, false, (isset($data['info'][0]->registro_patronal)? $data['info'][0]->registro_patronal:'')); ?>><?php echo $regp ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>
                  </div>

                  <div class="control-group" id="cultivosGrup">
                    <label class="control-label" for="area">Cultivo / Actividad / Producto </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="area" class="span11" id="area" value="<?php echo isset($data['cultivo']->nombre)?$data['cultivo']->nombre:''; ?>" placeholder="Limon, Piña, Administrativos">
                      </div>
                      <input type="hidden" name="areaId" id="areaId" value="<?php echo isset($data['cultivo']->id_area)?$data['cultivo']->id_area:''; ?>">
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
                    <label class="control-label" for="ffondo_ahorro">Fondo de Ahorro </label>
                    <div class="controls">
                      <input type="text" name="ffondo_ahorro" id="ffondo_ahorro" class="span12 vpositive"
                        value="<?php echo isset($data['info'][0]->fondo_ahorro)?$data['info'][0]->fondo_ahorro:''; ?>" maxlength="12" placeholder="Fondo de Ahorro">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="ffondo_ahorro_cpi">Cuenta Fondo de Ahorro </label>
                    <div class="controls">
                      <input type="text" name="ffondo_ahorro_cpi" id="ffondo_ahorro_cpi" class="span12 vpositive"
                        value="<?php echo isset($data['info'][0]->fondo_ahorro_cpi)?$data['info'][0]->fondo_ahorro_cpi:''; ?>" maxlength="12" placeholder="Cuenta contpaq Fondo de Ahorro">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fbanco">Banco </label>
                    <div class="controls">
                    <select name="fbanco" id="fbanco">
                      <option value="bancr" <?php echo set_select('fbanco', 'bancr', false, (isset($data['info'][0]->banco)?$data['info'][0]->banco:'')); ?>>BBVA Bancomer</option>
                      <option value="santr" <?php echo set_select('fbanco', 'santr', false, (isset($data['info'][0]->banco)?$data['info'][0]->banco:'')); ?>>Santander</option>
                      <option value="banor" <?php echo set_select('fbanco', 'banor', false, (isset($data['info'][0]->banco)?$data['info'][0]->banco:'')); ?>>Banorte</option>
                      <option value="efectivo" <?php echo set_select('fbanco', 'efectivo', false, (isset($data['info'][0]->banco)?$data['info'][0]->banco:'')); ?>>Efectivo</option>
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
                    <label class="control-label" for="dno_proveedor_banorte">Clave Proveedor Banco </label>
                    <div class="controls">
                      <input type="text" name="dno_proveedor_banorte" id="dno_proveedor_banorte" class="span12 vpositive" value="<?php echo isset($data['info'][0]->no_proveedor_banorte)?$data['info'][0]->no_proveedor_banorte:''; ?>" maxlength="13" placeholder="Clave Proveedor Banco">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dno_seguro">No Seguro </label>
                    <div class="controls">
                      <input type="text" name="dno_seguro" id="dno_seguro" class="span12 vpositive" value="<?php echo isset($data['info'][0]->no_seguro)?$data['info'][0]->no_seguro:''; ?>" maxlength="12" placeholder="# Seguro">
                    </div>
                  </div>

                  <!-- <div class="control-group">
                    <label class="control-label" for="dno_trabajador">No Trabajador </label>
                    <div class="controls">
                      <input type="text" name="dno_trabajador" id="dno_trabajador" class="span12" value="<?php echo isset($data['info'][0]->no_empleado)?$data['info'][0]->no_empleado:''; ?>" maxlength="8" placeholder="# Trabajador">
                    </div>
                  </div> -->

                  <!-- <div class="control-group">
                    <label class="control-label" for="dno_checador">No Checador </label>
                    <div class="controls">
                      <input type="text" name="dno_checador" id="dno_checador" class="span12" value="<?php echo isset($data['info'][0]->no_checador)?$data['info'][0]->no_checador:''; ?>" maxlength="8" placeholder="# Checador">
                    </div>
                  </div> -->

                  <div class="control-group">
                    <label class="control-label" for="tipo_contrato">Tipo Contrato </label>
                    <div class="controls">
                    <select name="tipo_contrato" id="tipo_contrato">
                    <?php foreach ($tipo_contratos as $key => $value)
                    {
                    ?>
                      <option value="<?php echo $value->clave ?>"
                        <?php echo set_select('tipo_contrato', $value->clave, false, (isset($data['info'][0]->tipo_contrato)?$data['info'][0]->tipo_contrato:'')); ?>><?php echo $value->nombre_corto." ({$value->clave})" ?></option>
                    <?php
                    } ?>
                    </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fregimen_contratacion">Regimen contratacion </label>
                    <div class="controls">
                    <select name="fregimen_contratacion" id="fregimen_contratacion">
                      <?php foreach ($tipo_regimens as $key => $value)
                      {
                      ?>
                        <option value="<?php echo $value->clave ?>"
                          <?php echo set_select('fregimen_contratacion', $value->clave, false, (isset($data['info'][0]->regimen_contratacion)?$data['info'][0]->regimen_contratacion:'')); ?>><?php echo $value->nombre_corto." ({$value->clave})" ?></option>
                      <?php
                      } ?>
                    </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="tipo_jornada">Tipo Jornada </label>
                    <div class="controls">
                    <select name="tipo_jornada" id="tipo_jornada">
                    <?php foreach ($tipo_jornadas as $key => $value)
                    {
                    ?>
                      <option value="<?php echo $value->clave ?>"
                        <?php echo set_select('tipo_jornada', $value->clave, false, (isset($data['info'][0]->tipo_jornada)?$data['info'][0]->tipo_jornada:'')); ?>><?php echo $value->nombre_corto." ({$value->clave})" ?></option>
                    <?php
                    } ?>
                    </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="riesgo_puesto">Riesgo Puesto </label>
                    <div class="controls">
                    <select name="riesgo_puesto" id="riesgo_puesto">
                    <?php foreach ($riesgo_puestos as $key => $value)
                    {
                    ?>
                      <option value="<?php echo $value->clave ?>"
                        <?php echo set_select('riesgo_puesto', $value->clave, false, (isset($data['info'][0]->riesgo_puesto)?$data['info'][0]->riesgo_puesto:'')); ?>><?php echo $value->nombre_corto." ({$value->clave})" ?></option>
                    <?php
                    } ?>
                    </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dp_alimenticia">Pensión alimenticia %</label>
                    <div class="controls">
                      <input type="text" name="dp_alimenticia" id="dp_alimenticia" class="span12 vpositive" value="<?php echo isset($data['info'][0]->p_alimenticia)? $data['info'][0]->p_alimenticia:''; ?>" max="100" placeholder="% Pension alimenticia">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dinfonacot">Infonacot x sem</label>
                    <div class="controls">
                      <input type="text" name="dinfonacot" id="dinfonacot" class="span12 vpositive" value="<?php echo isset($data['info'][0]->fonacot)? $data['info'][0]->fonacot:''; ?>" max="100" placeholder="">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dhrs_turno">Hrs del turno</label>
                    <div class="controls">
                      <input type="text" name="dhrs_turno" id="dhrs_turno" class="span12 vpositive" value="<?php echo isset($data['info'][0]->hrs_turno)? $data['info'][0]->hrs_turno:''; ?>" max="100" placeholder="">
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


