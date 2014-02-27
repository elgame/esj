		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/usuarios_departamentos/'); ?>">Departamentos</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar departamento</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/usuarios_departamentos/agregar'); ?>" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<div class="control-group">
								  <label class="control-label" for="fnombre">Empresa </label>
								  <div class="controls">
										<input type="text" name="dempresa" id="dempresa" class="span6" 
										value="<?php echo set_value('dempresa', $empresa->nombre_fiscal); ?>" required autofocus placeholder="Empresa">
										<input type="hidden" name="did_empresa" value="<?php echo set_value('did_empresa', $empresa->id_empresa); ?>" id="did_empresa">
								  </div>
								</div>

								<div class="control-group">
								  <label class="control-label" for="fnombre">Nombre </label>
								  <div class="controls">
										<input type="text" name="fnombre" id="fnombre" class="span6" maxlength="30"
										value="<?php echo set_value('fnombre'); ?>" required placeholder="Tractorista, XDia">
								  </div>
								</div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/usuarios_departamentos/'); ?>" class="btn">Cancelar</a>
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

