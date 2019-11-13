		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Pallets
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-th-large"></i> Pallets</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/rastreabilidad_pallets/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">Buscar</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
									class="input-large search-query" placeholder="Cliente, Folio" autofocus> |

								<label for="ffecha">Buscar</label>
								<input type="date" name="ffecha" id="ffecha" value="<?php echo set_value_get('ffecha'); ?>"> |

								<label for="parea">Area</label>
								<select name="parea" id="parea">
									<option value=""></option>
                  <?php foreach ($areas['areas'] as $area){ ?>
                    <option value="<?php echo $area->id_area ?>" data-tipo="<?php echo $area->tipo; ?>"
                      <?php echo set_select_get('parea', $area->id_area); ?>><?php echo $area->nombre ?></option>
                  <?php } ?>
                </select>

								<label for="fstatus">Estado</label>
								<select name="fstatus">
									<option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>TODOS</option>
									<option value="t" <?php echo set_select('fstatus', 't', false, $this->input->get('fstatus')); ?>>ACTIVOS</option>
									<option value="f" <?php echo set_select('fstatus', 'f', false, $this->input->get('fstatus')); ?>>ELIMINADOS</option>
								</select>

								<input type="submit" name="enviar" value="Buscar" class="btn">
							</fieldset>
						</form>

						<?php
							echo $this->usuarios_model->getLinkPrivSm('rastreabilidad_pallets/agregar/', array(
											'params'   => '',
											'btn_type' => 'btn-success pull-right',
											'attrs' => array('style' => 'margin-bottom: 10px;') )
									);
						 ?>
						<table class="table table-striped table-bordered bootstrap-datatable">
						  <thead>
							  <tr>
							  	<th>Folio</th>
							  	<th>Fecha</th>
								  <th>Cliente</th>
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
								<td><?php echo $pallet->nombre_fiscal; ?></td>
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
										echo $this->usuarios_model->getLinkPrivSm('rastreabilidad_pallets/modificar/', array(
												'params'   => 'id='.$pallet->id_pallet,
												'btn_type' => 'btn-success')
										);
										echo $this->usuarios_model->getLinkPrivSm('rastreabilidad_pallets/imprimir/', array(
												'params'   => 'id='.$pallet->id_pallet,
												'btn_type' => 'btn-info',
												'attrs' => array('target' => '_BLANCK') )
										);
										echo $this->usuarios_model->getLinkPrivSm('rastreabilidad_pallets/eliminar/', array(
												'params'   => 'id='.$pallet->id_pallet,
												'btn_type' => 'btn-danger',
												'attrs' => array('id' => 'pallet'.$pallet->id_pallet, 'onclick' => "msb.confirm('Estas seguro de eliminar el pallet? <br> <p><input type=&quot;checkbox&quot; class=&quot;del-all&quot; data-pallet=&quot;".$pallet->id_pallet."&quot;>Eliminar los rendimiento ligados al pallet</p>', 'pallet', this); return false;"))
										);

										?>
								</td>
							</tr>
					<?php }?>
						  </tbody>
					  </table>

					  <?php
						//Paginacion
						$this->pagination->initialize(array(
								'base_url' 			=> base_url($this->uri->uri_string()).'?'.String::getVarsLink(array('pag')).'&',
								'total_rows'		=> $pallets['total_rows'],
								'per_page'			=> $pallets['items_per_page'],
								'cur_page'			=> $pallets['result_page']*$pallets['items_per_page'],
								'page_query_string'	=> TRUE,
								'num_links'			=> 1,
								'anchor_class'	=> 'pags corner-all',
								'num_tag_open' 	=> '<li>',
								'num_tag_close' => '</li>',
								'cur_tag_open'	=> '<li class="active"><a href="#">',
								'cur_tag_close' => '</a></li>'
						));
						$pagination = $this->pagination->create_links();
						echo '<div class="pagination pagination-centered"><ul>'.$pagination.'</ul></div>';
						?>
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


