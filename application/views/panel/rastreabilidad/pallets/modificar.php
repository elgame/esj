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
					<li>Modificar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-edit"></i> Modificar Pallet</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/rastreabilidad_pallets/modificar?id='.$_GET['id']); ?>" id="form-search" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<?php $data = $info['info']; ?>

								<div class="span12">
									<div class="span2">
									  <label class="span4" for="ffolio">Folio </label>
										<input type="text" name="ffolio" id="ffolio" class="span6" value="<?php echo (isset($data->folio)? $data->folio: ''); ?>" 
											maxlength="25" placeholder="Folio pallet" readonly data-next="fcajas">
									</div>

									<div class="span3">
									  <label class="span4" for="fcajas">Cajas del pallet </label>
										<input type="text" name="fcajas" id="fcajas" class="span6 vpos-int" value="<?php echo (isset($data->no_cajas)? $data->no_cajas: ''); ?>" 
											maxlength="25" placeholder="Numero de cajas" required autofocus data-next="fclasificacion">
									</div>

									<div class="span3">
									  <label class="span3" for="fcliente">Cliente </label>
										<input type="text" name="fcliente" id="fcliente" class="span9" value="<?php echo (isset($info['cliente']->nombre_fiscal)? $info['cliente']->nombre_fiscal: ''); ?>" 
											maxlength="25" placeholder="Cliente" data-next="fcalibres">
										<input type="hidden" name="fid_cliente" value="<?php echo (isset($info['cliente']->id_cliente)? $info['cliente']->id_cliente: ''); ?>" id="fid_cliente" class="getjsval">
									</div>

									<div class="span4">
									  <label class="span3" for="fcalibres">Calibres </label>
									  <select name="fcalibres[]" id="fcalibres" class="chosen-select" multiple data-next="fclasificacion">
									<?php  
									foreach ($calibres['calibres'] as $key => $calibre)
									{
										$default = $this->rastreabilidad_pallets_model->calibreSelec($calibre->id_calibre, $info['calibres']);
									?>
										<option value="<?php echo $calibre->id_calibre; ?>" 
											<?php echo set_select('fcalibres[]', $calibre->id_calibre, $default ); ?>><?php echo $calibre->nombre; ?></option>
									<?php 
									} ?>
									  </select>
									</div>
								</div>
								<div class="clearfix"></div>

								<div class="row-fluid">
									<fieldset class="span6">
										<legend>Disponibles</legend>
										<div class="span12">
										  <label class="span4" for="fclasificacion">Clasificacion </label>
											<input type="text" name="fclasificacion" id="fclasificacion" class="span7" value="<?php echo set_value('fclasificacion'); ?>" 
												maxlength="100" placeholder="Nombre" data-next="btn_submit">
											<input type="hidden" name="fid_clasificacion" id="fid_clasificacion" value="<?php echo set_value('fid_clasificacion'); ?>">
										</div>
										<table class="table table-striped table-bordered bootstrap-datatable">
										  <thead>
											  <tr>
											  	<th>Fecha</th>
												  <th>Lote</th>
													<th>Cajas libres</th>
												  <th>Opciones</th>
											  </tr>
										  </thead>
										  <tbody id="tblrendimientos">
										  </tbody>
									  </table>
								  </fieldset>

								  <fieldset class="span6">
										<legend>Seleccionadas</legend>
										<table class="table table-striped table-bordered bootstrap-datatable">
										  <thead>
											  <tr>
											  	<th>Fecha</th>
												  <th>Lote</th>
												  <th>Clasif</th>
													<th>Cajas</th>
												  <th>Opciones</th>
											  </tr>
										  </thead>
										  <tbody id="tblrendimientossel">
										 <?php
										 $total_cajas_sel = 0;
										 if(isset($info['rendimientos'])){
										 		foreach ($info['rendimientos'] as $key => $value) {
										 ?>
										 		<tr id="row_rendsel<?php echo $value->id_rendimiento; ?>_<?php echo $value->id_clasificacion; ?>">
								          <td class="fecha"><?php echo $value->fecha; ?></td>
								          <td class="lote"><?php echo $value->lote; ?></td>
								          <td class="clsif"><?php echo $value->nombre; ?></td>
								          <td><input type="number" class="span12 cajasel" name="rendimientos[]" value="<?php echo $value->cajas; ?>" min="1" max="<?php echo $value->cajas; ?>"></td>
								          <td><input type="hidden" class="span5" name="idrendimientos[]" value="<?php echo $value->id_rendimiento; ?>">
								             <input type="hidden" class="span5" name="idclasificacion[]" value="<?php echo $value->id_clasificacion; ?>">
								             <buttom class="btn btn-danger remove_cajassel" data-idrow="<?php echo $value->id_rendimiento; ?>_<?php echo $value->id_clasificacion; ?>"><i class="icon-remove"></i></buttom></td>
								        </tr>
							       <?php 
							       			$total_cajas_sel += $value->cajas;
							       		}
							     		} 
							     		?>
										  </tbody>
										  <tfoot>
										  	<tr>
										  		<td colspan="3" style="text-align: right;">Cajas seleccionadas</td>
										  		<td id="total_cajas_sel" style="font-weight: bold;"><?php echo $total_cajas_sel; ?></td>
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

