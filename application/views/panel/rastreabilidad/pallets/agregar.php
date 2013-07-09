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
						<form action="<?php echo base_url('panel/rastreabilidad_pallets/agregar'); ?>" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<div class="span12">
									<div class="span3">
									  <label class="span4" for="ffolio">Folio </label>
										<input type="text" name="ffolio" id="ffolio" class="span6" value="<?php echo set_value('ffolio', $folio); ?>" 
											maxlength="25" placeholder="Folio pallet" readonly data-next="fclasificacion">
									</div>

									<div class="span5">
									  <label class="span4" for="fclasificacion">Clasificacion </label>
										<input type="text" name="fclasificacion" id="fclasificacion" class="span7" value="<?php echo set_value('fclasificacion'); ?>" 
											maxlength="100" placeholder="Nombre" autofocus required data-next="fcajas">
										<input type="hidden" name="fid_clasificacion" id="fid_clasificacion" value="<?php echo set_value('fid_clasificacion'); ?>">
									</div>

									<div class="span4">
									  <label class="span4" for="fcajas">Cajas del pallet </label>
										<input type="text" name="fcajas" id="fcajas" class="span6 vpos-int" value="<?php echo set_value('fcajas'); ?>" 
											maxlength="25" placeholder="Numero de cajas" required data-next="btn_submit">
									</div>
								</div>
								<div class="clearfix"></div>

								<table class="table table-striped table-bordered bootstrap-datatable">
								  <thead>
									  <tr>
									  	<th>Folio</th>
									  	<th>Fecha</th>
										  <th>Clasificacion</th>
											<th>Cajas</th>
											<th>Cajas agregs</th>
											<th>Cajas Falt</th>
											<th>Estatus</th>
										  <th>Opciones</th>
									  </tr>
								  </thead>
								  <tbody>
								<?php foreach($pallets['pallets'] as $pallet){ ?>
									<tr>
										<td><?php echo $pallet->folio; ?></td>
										<td><?php echo $pallet->fecha; ?></td>
										<td><?php echo $pallet->nombre; ?></td>
										<td><?php echo $pallet->no_cajas; ?></td>
										<td><?php echo $pallet->cajas; ?></td>
										<td><?php echo ($pallet->no_cajas-$pallet->cajas); ?></td>
										<td>
											<?php
												if(($pallet->no_cajas-$pallet->cajas) == 0){
													$v_status = 'Completo';
													$vlbl_status = 'label-success';
												}else{
													$v_status = 'Pendiente';
													$vlbl_status = 'label-warning';
												}
											?>
											<span class="label <?php echo $vlbl_status; ?>"><?php echo $v_status; ?></span>
										</td>
										<td class="center">
												<?php 
												echo $this->usuarios_model->getLinkPrivSm('usuarios/modificar/', array(
														'params'   => 'id='.$pallet->id_pallet,
														'btn_type' => 'btn-success')
												);
												
												?>
										</td>
									</tr>
							<?php }?>
								  </tbody>
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

