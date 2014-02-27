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

									<div class="span2">
									  <label class="span5" for="fcajas">Cajas del pallet </label>
										<input type="text" name="fcajas" id="fcajas" class="span6 vpos-int" value="<?php echo (isset($data->no_cajas)? $data->no_cajas: ''); ?>" 
											maxlength="25" placeholder="Numero de cajas" required autofocus data-next="fcliente">
									</div>

									<div class="span3">
									  <label class="span3" for="fcliente">Cliente </label>
										<input type="text" name="fcliente" id="fcliente" class="span9" value="<?php echo (isset($info['cliente']->nombre_fiscal)? $info['cliente']->nombre_fiscal: ''); ?>" 
											maxlength="25" placeholder="Cliente" data-next="fkilos">
										<input type="hidden" name="fid_cliente" value="<?php echo (isset($info['cliente']->id_cliente)? $info['cliente']->id_cliente: ''); ?>" id="fid_cliente" class="getjsval">
									</div>

									<div class="span2">
									  <label class="span3" for="fkilos">Kilos </label>
										<input type="text" name="fkilos" id="fkilos" class="span7 vpos-int" value="<?php echo (isset($data->kilos_pallet)? $data->kilos_pallet: ''); ?>" 
											maxlength="25" placeholder="Kilos" required data-next="fhojaspapel">
									</div>

									<div class="span3"><?php $no_hojas = (isset($data->no_hojas)? intval($data->no_hojas): 0); ?>
									  <label class="span3" for="fhojaspapel">Hojas de papel </label>
									  <select name="fhojaspapel" id="fhojaspapel" class="span8" data-next="fclasificacion">
									  	<option value="0" <?php echo set_select('fhojaspapel', 0, false, $no_hojas); ?>>Sin papel</option>
									  	<option value="2" <?php echo set_select('fhojaspapel', 2, false, $no_hojas); ?>>2 Hojas</option>
									  	<option value="4" <?php echo set_select('fhojaspapel', 4, false, $no_hojas); ?>>4 Hojas</option>
									  	<option value="7" <?php echo set_select('fhojaspapel', 7, false, $no_hojas); ?>>7 Hojas</option>
									  </select>
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
												<th>Caja</th>
												<th>Tamaño</th>
												<th>Etiqueta</th>
												<th>Kilos</th>
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
												<th>Más Inf.</th>
												<th>Cajas</th>
												<th>Opciones</th>
											  </tr>
										  </thead>
										  <tbody id="tblrendimientossel">
										 <?php
										 $total_cajas_sel = 0;
										 if(isset($info['rendimientos'])){
										 	foreach ($info['rendimientos'] as $key => $value) {
										 		$idrow = $value->id_rendimiento.'_'.$value->id_unidad.'_'.$value->id_calibre.'_'.$value->id_etiqueta.'_'.
										 				$value->id_clasificacion.'_'.$value->id_size.'_'.str_replace('.', '-', $value->kilos);
										 ?>
										 	<tr id="row_rendsel<?php echo $idrow; ?>">
												<td class="fecha"><?php echo $value->fecha; ?></td>
												<td class="lote"><?php echo $value->lote; ?></td>
												<td class="clsif"><?php echo $value->nombre; ?></td>
												<td class="mas"><?php echo $value->unidad.'|'.$value->calibre.'|'.$value->etiqueta; ?></td>
												<td><input type="number" class="span12 cajasel" name="rendimientos[]" value="<?php echo $value->cajas; ?>" min="1" max="<?php echo $value->cajas; ?>"></td>
												<td><input type="hidden" name="idrendimientos[]" value="<?php echo $value->id_rendimiento; ?>">
													<input type="hidden" name="idclasificacion[]" value="<?php echo $value->id_clasificacion; ?>">
													<input type="hidden" name="idunidad[]" value="<?php echo $value->id_unidad; ?>">
													<input type="hidden" name="idcalibre[]" value="<?php echo $value->id_calibre; ?>">
													<input type="hidden" name="idetiqueta[]" value="<?php echo $value->id_etiqueta; ?>">
													<input type="hidden" name="idsize[]" value="<?php echo $value->id_size; ?>">
													<input type="hidden" name="dkilos[]" value="<?php echo $value->kilos; ?>">

													 <buttom class="btn btn-danger remove_cajassel" data-idrow="<?php echo $idrow; ?>"><i class="icon-remove"></i></buttom></td>
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

