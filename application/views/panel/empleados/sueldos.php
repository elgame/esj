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
            <form id="form" action="<?php echo base_url('panel/empleados/sueldos'); ?>" method="post">
              <fieldset>
                <div class="input-append span4">
                  <label class="control-label" for="dempresa">Empresa </label>
                  <input type="text" name="dempresa" id="dempresa" class="span9" value="<?php echo set_value('dempresa', $empresa->nombre_fiscal); ?>" placeholder="Nombre">
                  <input type="hidden" name="did_empresa" value="<?php echo set_value('did_empresa', $empresa->id_empresa); ?>" id="did_empresa">
                </div>

                <div class="input-append span6">
                  <input type="text" name="dempleado" value="" id="dempleado" class="span9" placeholder="Buscar">
                  <button class="btn" type="button" id="btnAddProveedor" style="margin-left:-3px;"><i class="icon-plus-sign"></i></button>
                  <input type="hidden" name="did_empleado" value="" id="did_empleado">
                </div>
              </fieldset>

  						<table class="table table-striped table-bordered bootstrap-datatable">
  						  <thead>
  							  <tr>
  							  	<th>Nombre</th>
  									<th>Tipo</th>
  									<th>Sueldo (SDI)</th>
  									<th>Sueldo Real</th>
  								  <th style="width:90px;">Opciones</th>
  							  </tr>
  						  </thead>
  						  <tbody id="lista">
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


