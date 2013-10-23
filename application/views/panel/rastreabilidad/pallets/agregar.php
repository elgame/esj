		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/rastreabilidad_pallets/'); ?>">Pallets</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-edit"></i> Agregar Pallet</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/rastreabilidad_pallets/agregar'); ?>" id="form-search" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<div class="span12">
									<div class="span2">
									  <label class="span4" for="ffolio">Folio </label>
										<input type="text" name="ffolio" id="ffolio" class="span6" value="<?php echo set_value('ffolio', $folio); ?>" 
											maxlength="25" placeholder="Folio pallet" readonly data-next="fcajas">
									</div>

									<div class="span3">
									  <label class="span4" for="fcajas">Cajas del pallet </label>
										<input type="text" name="fcajas" id="fcajas" class="span6 vpos-int" value="<?php echo set_value('fcajas'); ?>" 
											maxlength="25" placeholder="Numero de cajas" required autofocus data-next="fcliente">
									</div>

									<div class="span3">
									  <label class="span3" for="fcliente">Cliente </label>
										<input type="text" name="fcliente" id="fcliente" class="span9" value="<?php echo set_value('fcliente'); ?>" 
											maxlength="25" placeholder="Cliente" data-next="fclasificacion">
										<input type="hidden" name="fid_cliente" value="<?php echo set_value('fid_cliente'); ?>" id="fid_cliente" class="getjsval">
									</div>

								</div>
								<div class="clearfix"></div>

								<div class="row-fluid">
									<fieldset class="span6">
										<legend>Disponibles</legend>
										<div class="row-fluid">
											<div class="span5">
												<input type="text" name="fclasificacion" id="fclasificacion" class="span12" value="<?php echo set_value('fclasificacion'); ?>" 
													maxlength="100" placeholder="Clasificación" data-next="funidad">
												<input type="hidden" name="fid_clasificacion" id="fid_clasificacion" value="<?php echo set_value('fid_clasificacion'); ?>">
											</div>

											<div class="span2">
												<input type="text" name="funidad" id="funidad" class="span12" value="<?php echo set_value('funidad'); ?>" 
													maxlength="100" placeholder="Unidad" data-next="fcalibre">
												<input type="hidden" name="fidunidad" id="fidunidad" value="<?php echo set_value('fidunidad'); ?>">
											</div>

											<div class="span2">
												<input type="text" name="fcalibre" id="fcalibre" class="span12" value="<?php echo set_value('fcalibre'); ?>" 
													maxlength="100" placeholder="Calibre" data-next="fetiqueta">
												<input type="hidden" name="fidcalibre" id="fidcalibre" value="<?php echo set_value('fidcalibre'); ?>">
											</div>

											<div class="span3">
												<input type="text" name="fetiqueta" id="fetiqueta" class="span12" value="<?php echo set_value('fetiqueta'); ?>" 
													maxlength="100" placeholder="Etiqueta" data-next="fclasificacion">
												<input type="hidden" name="fidetiqueta" id="fidetiqueta" value="<?php echo set_value('fidetiqueta'); ?>">
											</div>
										</div>
										<table class="table table-striped table-bordered bootstrap-datatable">
										  <thead>
											  <tr>
											  	<th>Fecha</th>
												<th>Lote</th>
												<th>Unidad</th>
												<th>Calibre</th>
												<th>Etiqueta</th>
												<th>Cajas libres</th>
												<th>Opciones</th>
											  </tr>
										  </thead>
										  <tbody id="tblrendimientos">
										  </tbody>
									  </table>
								  </fieldset>

								  <fieldset class="span6 nomarg">
										<legend>Seleccionadas</legend>
										<table class="table table-striped table-bordered bootstrap-datatable">
										  <thead>
											  <tr>
												<th>Fecha</th>
												<th>Lote</th>
												<th>Clasif</th>
												<th>Más Inf.</th>
												<th>Cajas</th>
												<th>Opciones</th>
											  </tr>
										  </thead>
										  <tbody id="tblrendimientossel">
										  </tbody>
										  <tfoot>
										  	<tr>
										  		<td colspan="4" style="text-align: right;">Cajas seleccionadas</td>
										  		<td id="total_cajas_sel" style="font-weight: bold;">0</td>
										  	</tr>
										  </tfoot>
									  </table>
								  </fieldset>
							  </div>

								<div class="form-actions">
								  <button type="submit" id="btn_submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/rastreabilidad_pallets/'); ?>" class="btn">Cancelar</a>
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

