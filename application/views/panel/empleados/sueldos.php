		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Empleados
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-user"></i> Empleados</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form id="formqs" action="<?php echo base_url('panel/empleados/sueldos'); ?>" method="get">
              <fieldset>
                <div class="input-append span4">
                  <label class="control-label" for="dempresa">Empresa </label>
                  <input type="text" name="dempresa" id="dempresa" class="span9" value="<?php echo set_value_get('dempresa', $empresa->nombre_fiscal); ?>" placeholder="Nombre">
                  <input type="hidden" name="did_empresa" value="<?php echo set_value_get('did_empresa', $empresa->id_empresa); ?>" id="did_empresa">
                  <button class="btn" type="submit" style="margin-left:-3px;">Buscar</button>
                </div>

                <div class="input-append pull-right span6">
                  <input type="text" name="dempleado" value="" id="dempleado" class="span9" placeholder="Buscar">
                  <button class="btn" type="button" id="btnAddProveedor" style="margin-left:-3px;"><i class="icon-plus-sign"></i></button>
                  <input type="hidden" name="did_empleado" value="" id="did_empleado">
                </div>
              </fieldset>
            </form>

            <form id="form" action="<?php echo base_url('panel/empleados/sueldos'); ?>" method="post">
  						<table class="table table-striped table-bordered bootstrap-datatable">
  						  <thead>
  							  <tr>
  							  	<th>Nombre</th>
  									<th>Tipo</th>
  									<th><span class="span3 pull-left">Sueldo (SDI)</span> <input type="text" id="sueldo_sdi" class="span4 pull-left vpositive nokey"></th>
  									<th><span class="span3 pull-left">Sueldo Real</span> <input type="text" id="sueldo_sr" class="span4 pull-left vpositive nokey"></th>
  								  <th style="width:90px;">Opciones <a class="btn" id="remove_all" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a></th>
  							  </tr>
  						  </thead>
  						  <tbody id="lista">
          <?php foreach ($empleados as $key => $e)
              {
                $tipo = 'No asegurado'; $salario_diario=$e->salario_diario; $factor_integracion = 0;
                $clase_sdi = ''; $readly_asegurado = '';
                if ($e->esta_asegurado != 'f'){
                  $tipo = 'Asegurado';
                  $salario_diario = $e->nomina->salario_diario_integrado;
                  $factor_integracion = $e->factor_integracion;
                  $clase_sdi = 'change_sdi';
                  $readly_asegurado = '';
                }
          ?>
                  <tr id="empleado<?php echo $e->id ?>">
                      <td><?php echo $e->nombre ?>
                         <input type="hidden" name="id_empledo[]" value="<?php echo $e->id ?>">
                         <input type="hidden" name="factor_integracion[]" value="<?php echo $factor_integracion ?>">
                      </td>
                      <td><?php echo $tipo ?><input type="hidden" name="tipo[]" value="<?php echo $e->esta_asegurado ?>"></td>
                      <td><span class="span3 pull-left"><?php echo $salario_diario ?></span>
                          <input type="text" name="sueldo_diario[]" value="<?php echo $salario_diario ?>"
                            class="span4 pull-left vpositive <?php echo $clase_sdi ?>" maxlength="9" <?php echo $readly_asegurado ?>></td>
                      <td><span class="span3 pull-left"><?php echo $e->salario_diario_real ?></span>
                          <input type="text" name="sueldo_real[]" value="<?php echo $e->salario_diario_real ?>" class="span4 pull-left vpositive change_sr" maxlength="9"></td>
                      <td><a class="btn btn-link remove_proveedor" style="padding: 2px 5px;"><i class="icon-minus-sign"></i></a></td>
                    </tr>
            <?php } ?>
  						  </tbody>
  					  </table>
              <button type="submit" class="btn btn-success pull-right">Guardar</button>
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


