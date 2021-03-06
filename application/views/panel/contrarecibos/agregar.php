		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/contrarecibo/'); ?>">Contrarecibos</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar contrarecibo</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/contrarecibo/agregar'); ?>" method="post" class="form-horizontal" id="formprovee">
						  <fieldset>
								<legend></legend>

								<div class="span6">

									<div class="control-group">
									  <label class="control-label" for="fempresa">Empresa </label>
									  <div class="controls">
											<input type="text" name="fempresa" id="fempresa" class="span10" value="<?php echo set_value('fempresa'); ?>" required placeholder="Empresa">
											<input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value('did_empresa'); ?>">
									  </div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="fproveedor">Proveedor </label>
									  <div class="controls">
											<input type="text" name="fproveedor" id="fproveedor" class="span10" value="<?php echo set_value('fproveedor'); ?>" required placeholder="Proveedor">
											<input type="hidden" name="did_proveedor" id="did_proveedor" value="<?php echo set_value('did_proveedor'); ?>">
									  </div>
									</div>

								</div> <!--/span-->

								<div class="span5">
									<div class="control-group">
										<label class="control-label" for="ffecha1">Fecha:</label>
										<div class="controls">
											<input type="date" name="ffecha1" id="ffecha1" class="span12" value="<?php echo set_value('ffecha1'); ?>" required maxlength="100">
										</div>
									</div>

									<div class="control-group">
									  <label class="control-label" for="ffolio">Folio </label>
									  <div class="controls">
											<input type="text" name="ffolio" id="ffolio" class="span12" value="<?php echo set_value('ffolio', $folio); ?>"
												maxlength="120" required readonly placeholder="F4">
									  </div>
									</div>

	              </div> <!--/span-->

        				<div class="span11">
        					<table class="table table-striped table-bordered table-hover table-condensed">
										<thead>
										  <tr>
										    <th>Folio</th>
										    <th>Fecha</th>
										    <th>Importe</th>
										    <th>Observacion</th>
										    <th>Opc</th>
										  </tr>
										</thead>
										<tbody id="tableCuentas">
										<?php if (is_array($this->input->post('facturas_folio')))
										{
										  foreach ($this->input->post('facturas_folio') as $key => $value)
										  {
										?>
											<tr>
											    <td><input type="text" name="facturas_folio[]" value="<?php echo $_POST['facturas_folio'][$key]; ?>" class="facturas_folio"></td>
											    <td><input type="date" name="facturas_fecha[]" value="<?php echo $_POST['facturas_fecha'][$key]; ?>" class="facturas_fecha"></td>
											    <td><input type="text" name="facturas_importe[]" value="<?php echo $_POST['facturas_importe'][$key]; ?>" class="facturas_importe vnumeric"></td>
                          <td><input type="text" name="facturas_observacion[]" value="<?php echo $_POST['facturas_observacion'][$key]; ?>" class="facturas_observacion" maxlength="200"></td>
											    <td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>
											</tr>
										<?php
										  }
										} ?>
										<tr>
											<td><input type="text" name="facturas_folio[]" value="" class="facturas_folio"></td>
											<td><input type="date" name="facturas_fecha[]" value="" class="facturas_fecha"></td>
											<td><input type="text" name="facturas_importe[]" value="" class="facturas_importe vnumeric"></td>
											<td><input type="text" name="facturas_observacion[]" value="" class="facturas_observacion" maxlength="200"></td>
											<td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>
										</tr>

										</tbody>
										<tfoot>
											<tr>
												<th colspan="2"></th>
												<th><input type="number" name="dtotal" id="dtotal" value="<?php echo set_value('dtotal', 0); ?>" readonly></th>
											</tr>
										</tfoot>
									</table>
	              				</div>

	              				<div class="clearfix"></div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/contrarecibo/'); ?>" class="btn">Cancelar</a>
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

