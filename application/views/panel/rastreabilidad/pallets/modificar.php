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
						<form action="<?php echo base_url('panel/rastreabilidad_pallets/modificar?id='.$_GET['id']); ?>" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<?php $data = $info['info']; ?>

								<div class="span12">
									<div class="span3">
									  <label class="span4" for="ffolio">Folio </label>
										<input type="text" name="ffolio" id="ffolio" class="span6" value="<?php echo (isset($data->folio)? $data->folio: ''); ?>" 
											maxlength="25" placeholder="Folio pallet" readonly data-next="fclasificacion">
									</div>

									<div class="span5">
									  <label class="span4" for="fclasificacion">Clasificacion </label>
										<input type="text" name="fclasificacion" id="fclasificacion" class="span7" value="<?php echo (isset($data->nombre)? $data->nombre: ''); ?>" 
											maxlength="100" placeholder="Nombre" readonly required data-next="fcajas">
										<input type="hidden" name="fid_clasificacion" id="fid_clasificacion" value="<?php echo (isset($data->id_clasificacion)? $data->id_clasificacion: ''); ?>">
									</div>

									<div class="span4">
									  <label class="span4" for="fcajas">Cajas del pallet </label>
										<input type="text" name="fcajas" id="fcajas" class="span6 vpos-int" value="<?php echo (isset($data->no_cajas)? $data->no_cajas: ''); ?>" 
											maxlength="25" placeholder="Numero de cajas" required data-next="btn_submit">
									</div>
								</div>
								<div class="clearfix"></div>

								<table class="table table-striped table-bordered bootstrap-datatable">
								  <thead>
									  <tr>
									  	<th>Fecha</th>
										  <th>Lote</th>
											<th>Cajas</th>
										  <th>Opciones</th>
									  </tr>
								  </thead>
								  <tbody id="tblrendimientos">
								 <?php
								 $total_cajas_sel = 0;
								 if(isset($info['rendimientos'])){
								 		foreach ($info['rendimientos'] as $key => $value) {
								 ?>
								  	<tr class="tradded" id="row_rend<?php echo $value->id_rendimiento; ?>">
					            <td><?php echo $value->fecha; ?></td>
					            <td><?php echo $value->lote; ?></td>
					            <td><?php echo $value->cajas; ?></td>
					            <td><input type="checkbox" name="rendimientos[]" value="<?php echo $value->id_rendimiento; ?>|<?php echo $value->cajas; ?>"
					              class="rendimientos" data-libres="<?php echo $value->cajas; ?>" checked data-uniform="false"></td>
					          </tr>
					       <?php 
					       			$total_cajas_sel += $value->cajas;
					       		}
					     		} 

								 if(isset($info['rend_libres'])){
								 		foreach ($info['rend_libres'] as $key => $value) {
								 ?>
								  	<tr id="row_rend<?php echo $value->id_rendimiento; ?>">
					            <td><?php echo $value->fecha; ?></td>
					            <td><?php echo $value->lote; ?></td>
					            <td><?php echo $value->libres; ?></td>
					            <td><input type="checkbox" name="rendimientos[]" value="<?php echo $value->id_rendimiento; ?>|<?php echo $value->libres; ?>"
					              class="rendimientos" data-libres="<?php echo $value->libres; ?>" data-uniform="false"></td>
					          </tr>
					       <?php 
					       		}
					     		} ?>
								  </tbody>
								  <tfoot>
								  	<tr>
								  		<td colspan="3" style="text-align: right;">Cajas seleccionadas</td>
								  		<td id="total_cajas_sel" style="font-weight: bold;"><?php echo $total_cajas_sel; ?></td>
								  	</tr>
								  </tfoot>
							  </table>

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

