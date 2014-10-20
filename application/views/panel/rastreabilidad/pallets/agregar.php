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

									<div class="span3">
									  <label class="span3" for="parea">Area </label>
										<select name="parea" id="parea" class="span9" autofocus data-next="fcajas">
	                    <?php foreach ($areas['areas'] as $area){ ?>
	                      <option value="<?php echo $area->id_area ?>" data-tipo="<?php echo $area->tipo; ?>"
	                        <?php $set_select=($area->id_area == (isset($area_default) ? $area_default : ($area->predeterminado == 't' ? $area->id_area: '')));
	                         echo ($set_select? 'selected': ''); ?>><?php echo $area->nombre ?></option>
	                    <?php } ?>
	                  </select>
									</div>

									<div class="span2">
									  <label class="span4" for="ffolio">Folio </label>
										<input type="text" name="ffolio" id="ffolio" class="span6" value="<?php echo set_value('ffolio', $folio); ?>"
											maxlength="25" placeholder="Folio pallet" readonly data-next="fcajas">
									</div>

									<div class="span2">
									  <label class="span5" for="fcajas">Cajas del pallet </label>
										<input type="text" name="fcajas" id="fcajas" class="span6 vpos-int" value="<?php echo set_value('fcajas'); ?>"
											maxlength="25" placeholder="Numero de cajas" required data-next="fcliente">
									</div>

									<div class="span3">
									  <label class="span3" for="fcliente">Cliente </label>
										<input type="text" name="fcliente" id="fcliente" class="span9" value="<?php echo set_value('fcliente'); ?>"
											maxlength="25" placeholder="Cliente" data-next="fkilos">
										<input type="hidden" name="fid_cliente" value="<?php echo set_value('fid_cliente'); ?>" id="fid_cliente" class="getjsval">
									</div>

									<div class="span2">
									  <label class="span3" for="fkilos">Kilos </label>
										<input type="text" name="fkilos" id="fkilos" class="span7 vpositive" value="<?php echo set_value('fkilos'); ?>"
											maxlength="25" placeholder="Kilos" required data-next="btnmodalproductosSa">
									</div>

								</div>
								<div class="clearfix"></div>

								<div class="span12">

                  <div class="span3">
									  <label class="span3" for="fhojaspapel">Salidas de productos </label>
									  <a href="#modalProdutosSal" id="btnmodalproductosSa" role="button" class="btn btn-info" data-toggle="modal">Agregar productos</a>

									  <!-- Modal productos salida -->
										<div id="modalProdutosSal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modalProductosSalLabel" aria-hidden="true">
										  <div class="modal-header">
										    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
										    <h3 id="modalProductosSalLabel">Salidas de Productos</h3>
										  </div>
										  <div class="modal-body">
										  	<table class="table table-condensed table-striped">
										  		<thead>
										  			<tr>
										  				<th></th>
										  				<th>Producto</th>
										  				<th>Cantidad</th>
										  			</tr>
										  		</thead>
										  		<tbody>
										  			<tr>
										  				<td>Cajas</td>
										  				<td>
										  					<input type="text" name="ps[]" id="ps_caja" class="sikey span12 prod_salida" value="<?php echo set_value('ps[]'); ?>" data-xcajas="1" data-next="ps_papel">
										  					<input type="hidden" name="ps_id[]" id="ps_caja_id" value="<?php echo set_value('ps_id[]'); ?>">
										  					<input type="hidden" name="ps_row[]" id="ps_caja_row" value="1">
										  				</td>
										  				<td>
										  					<input type="text" name="ps_num[]" id="ps_caja_num" value="<?php echo set_value('ps_num[]'); ?>" class="sikey span12" readonly>
										  				</td>
										  			</tr>
										  			<tr>
										  				<td>Hojas de papel</td>
										  				<td>
										  					<input type="text" name="ps[]" id="ps_papel" class="sikey span12 prod_salida" value="<?php echo set_value('ps[]'); ?>" data-next="fhojaspapel">
										  					<input type="hidden" name="ps_id[]" id="ps_papel_id" value="<?php echo set_value('ps_id[]'); ?>">
										  					<input type="hidden" name="ps_row[]" id="ps_caja_row" value="2">
										  				</td>
										  				<td>
										  					<select name="fhojaspapel" id="fhojaspapel" class="sikey span12" data-next="ps_fleje">
															  	<option value="0" <?php echo set_select('fhojaspapel', 0); ?>>Sin papel</option>
															  	<option value="2" <?php echo set_select('fhojaspapel', 2); ?>>2 Hojas</option>
															  	<option value="4" <?php echo set_select('fhojaspapel', 4); ?>>4 Hojas</option>
															  	<option value="7" <?php echo set_select('fhojaspapel', 7); ?>>7 Hojas</option>
															  </select>
										  					<input type="text" name="ps_num[]" id="ps_papel_num" value="<?php echo set_value('ps_num[]'); ?>" class="span12">
										  				</td>
										  			</tr>
										  			<tr>
										  				<td>Fleje</td>
										  				<td>
										  					<input type="text" name="ps[]" id="ps_fleje" class="sikey span12 prod_salida" value="<?php echo set_value('ps[]'); ?>" data-next="ps_fleje_num">
										  					<input type="hidden" name="ps_id[]" id="ps_fleje_id" value="<?php echo set_value('ps_id[]'); ?>">
										  					<input type="hidden" name="ps_row[]" id="ps_caja_row" value="3">
										  				</td>
										  				<td>
										  					<input type="text" name="ps_num[]" id="ps_fleje_num" value="<?php echo set_value('ps_num[]'); ?>" class="sikey span12 vpositive" data-next="ps_grapa">
										  				</td>
										  			</tr>
										  			<tr>
										  				<td>Grapa</td>
										  				<td>
										  					<input type="text" name="ps[]" id="ps_grapa" class="sikey span12 prod_salida" value="<?php echo set_value('ps[]'); ?>" data-xcajas="20" data-next="ps_grapa_num">
										  					<input type="hidden" name="ps_id[]" id="ps_grapa_id" value="<?php echo set_value('ps_id[]'); ?>">
										  					<input type="hidden" name="ps_row[]" id="ps_caja_row" value="4">
										  				</td>
										  				<td>
										  					<input type="text" name="ps_num[]" id="ps_grapa_num" value="<?php echo set_value('ps_num[]'); ?>" class="sikey span12 vpositive" data-next="ps_tapa">
										  				</td>
										  			</tr>
										  			<tr>
										  				<td>Tapa</td>
										  				<td>
										  					<input type="text" name="ps[]" id="ps_tapa" class="sikey span12 prod_salida" value="<?php echo set_value('ps[]'); ?>" data-next="ps_tapa_num">
										  					<input type="hidden" name="ps_id[]" id="ps_tapa_id" value="<?php echo set_value('ps_id[]'); ?>">
										  					<input type="hidden" name="ps_row[]" id="ps_caja_row" value="5">
										  				</td>
										  				<td>
										  					<input type="text" name="ps_num[]" id="ps_tapa_num" value="<?php echo set_value('ps_num[]'); ?>" class="sikey span12 vpositive" data-next="ps_ficha">
										  				</td>
										  			</tr>
										  			<tr>
										  				<td>Fichas</td>
										  				<td>
										  					<input type="text" name="ps[]" id="ps_ficha" class="sikey span12 prod_salida" value="<?php echo set_value('ps[]'); ?>" data-next="ps_ficha_num">
										  					<input type="hidden" name="ps_id[]" id="ps_ficha_id" value="<?php echo set_value('ps_id[]'); ?>">
										  					<input type="hidden" name="ps_row[]" id="ps_caja_row" value="6">
										  				</td>
										  				<td>
										  					<input type="text" name="ps_num[]" id="ps_ficha_num" value="<?php echo set_value('ps_num[]'); ?>" class="sikey span12 vpositive" data-next="ps_tarima">
										  				</td>
										  			</tr>
										  			<tr>
										  				<td>Tarima</td>
										  				<td>
										  					<input type="text" name="ps[]" id="ps_tarima" class="sikey span12 prod_salida" value="<?php echo set_value('ps[]'); ?>" data-next="ps_etiqueta">
										  					<input type="hidden" name="ps_id[]" id="ps_tarima_id" value="<?php echo set_value('ps_id[]'); ?>">
										  					<input type="hidden" name="ps_row[]" id="ps_caja_row" value="7">
										  				</td>
										  				<td>
										  					<input type="text" name="ps_num[]" id="ps_tarima_num" value="1" class="sikey span12 noclear" readonly>
										  				</td>
										  			</tr>
										  			<tr>
										  				<td>Etiqueta</td>
										  				<td>
										  					<input type="text" name="ps[]" id="ps_etiqueta" class="sikey span12 prod_salida" value="<?php echo set_value('ps[]'); ?>" data-xcajas="1" data-next="ps_arpilla">
										  					<input type="hidden" name="ps_id[]" id="ps_etiqueta_id" value="<?php echo set_value('ps_id[]'); ?>">
										  					<input type="hidden" name="ps_row[]" id="ps_caja_row" value="8">
										  				</td>
										  				<td>
										  					<input type="text" name="ps_num[]" id="ps_etiqueta_num" value="<?php echo set_value('ps_num[]'); ?>" class="sikey span12" readonly>
										  				</td>
										  			</tr>
										  			<tr>
										  				<td>Arpilla</td>
										  				<td>
										  					<input type="text" name="ps[]" id="ps_arpilla" class="sikey span12 prod_salida" value="<?php echo set_value('ps[]'); ?>" data-xcajas="1" data-next="btn_ps_cerrar">
										  					<input type="hidden" name="ps_id[]" id="ps_arpilla_id" value="<?php echo set_value('ps_id[]'); ?>">
										  					<input type="hidden" name="ps_row[]" id="ps_caja_row" value="9">
										  				</td>
										  				<td>
										  					<input type="text" name="ps_num[]" id="ps_arpilla_num" value="<?php echo set_value('ps_num[]'); ?>" class="sikey span12" readonly>
										  				</td>
										  			</tr>
										  		</tbody>
										  	</table>
										  </div>
										  <div class="modal-footer">
										    <button id="btn_ps_cerrar" class="btn" data-dismiss="modal" aria-hidden="true">Ok</button>
										  </div>
										</div>

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
													maxlength="100" placeholder="Caja" data-next="fcalibre">
												<input type="hidden" name="fidunidad" id="fidunidad" value="<?php echo set_value('fidunidad'); ?>">
											</div>

											<div class="span2">
												<input type="text" name="fcalibre" id="fcalibre" class="span12" value="<?php echo set_value('fcalibre'); ?>"
													maxlength="100" placeholder="Tamaño" data-next="fetiqueta">
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

