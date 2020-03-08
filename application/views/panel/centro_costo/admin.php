		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Centros de costo
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-user"></i> Centros de costo</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/centro_costo/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">Buscar</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
									class="input-large search-query" placeholder="Nombre" autofocus> |

                <label class="control-label" for="farea">Area </label>
                <input type="text" name="farea" id="farea" class="input-xlarge search-query" value="<?php echo set_value_get('farea'); ?>" placeholder="Limon, Piña">
                <input type="hidden" name="did_area" value="<?php echo set_value_get('did_area'); ?>" id="did_area"> |

								<label for="fstatus">Estado</label>
								<select name="fstatus">
									<option value="t" <?php echo set_select('fstatus', 't', false, $this->input->get('fstatus')); ?>>ACTIVOS</option>
									<option value="f" <?php echo set_select('fstatus', 'f', false, $this->input->get('fstatus')); ?>>ELIMINADOS</option>
									<option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>TODOS</option>
								</select> |

                <label for="ftipo">Tipo</label>
                <select name="ftipo">
                  <option value="" <?php echo set_select('ftipo', '', false, $this->input->get('ftipo')) ?>>Todos</option>
                  <option value="gasto" <?php echo set_select('ftipo', 'gasto', false, $this->input->get('ftipo')) ?>>Gasto</option>
                  <option value="banco" <?php echo set_select('ftipo', 'banco', false, $this->input->get('ftipo')) ?>>Banco</option>
                  <option value="gastofinanciero" <?php echo set_select('ftipo', 'gastofinanciero') ?>>Gastos financieros</option>
                  <option value="resultado" <?php echo set_select('ftipo', 'resultado') ?>>Resultado (ejercicios)</option>
                  <option value="creditobancario" <?php echo set_select('ftipo', 'creditobancario') ?>>Créditos bancarios</option>
                  <option value="otrosingresos" <?php echo set_select('ftipo', 'otrosingresos') ?>>Otros ingresos</option>
                  <option value="impuestoxpagar" <?php echo set_select('ftipo', 'impuestoxpagar') ?>>Impuestos por pagar</option>
                  <option value="productofinanc" <?php echo set_select('ftipo', 'productofinanc') ?>>Productos financieros</option>
                  <option value="impuestoafavor" <?php echo set_select('ftipo', 'impuestoafavor') ?>>Impuestos a favor</option>
                  <option value="melga" <?php echo set_select('ftipo', 'melga', false, $this->input->get('ftipo')) ?>>Melga</option>
                  <option value="tabla" <?php echo set_select('ftipo', 'tabla', false, $this->input->get('ftipo')) ?>>Tabla</option>
                  <option value="seccion" <?php echo set_select('ftipo', 'seccion', false, $this->input->get('ftipo')) ?>>Sección</option>
                </select>

								<input type="submit" name="enviar" value="Buscar" class="btn">
							</fieldset>
						</form>


						<?php
						echo $this->usuarios_model->getLinkPrivSm('centro_costo/agregar/', array(
										'params'   => '',
										'btn_type' => 'btn-success pull-right',
										'attrs' => array('style' => 'margin-bottom: 10px;') )
								);
						 ?>
						<table class="table table-striped table-bordered bootstrap-datatable">
						  <thead>
							  <tr>
							  	<th>Nombre</th>
								  <th>Tipo</th>
                  <th>Área</th>
									<th>Código</th>
                  <th>Estatus</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($centros_costos['centros_costos'] as $centro_costo){ ?>
							<tr>
								<td><?php echo $centro_costo->nombre; ?></td>
								<td><?php echo $centro_costo->tipo; ?></td>
                <td><?php echo $centro_costo->area; ?></td>
								<td><?php echo $centro_costo->codigo; ?></td>
								<td>
									<?php
										if($centro_costo->status == 't'){
											$v_status = 'Activo';
											$vlbl_status = 'label-success';
										}else{
											$v_status = 'Eliminado';
											$vlbl_status = 'label-important';
										}
									?>
									<span class="label <?php echo $vlbl_status; ?>"><?php echo $v_status; ?></span>
								</td>
								<td class="center">
										<?php
										echo $this->usuarios_model->getLinkPrivSm('centro_costo/modificar/', array(
												'params'   => 'id='.$centro_costo->id_centro_costo,
												'btn_type' => 'btn-success')
										);
										if ($centro_costo->status == 't') {
											echo $this->usuarios_model->getLinkPrivSm('centro_costo/eliminar/', array(
														'params'   => 'id='.$centro_costo->id_centro_costo,
														'btn_type' => 'btn-danger',
														'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar el centro de costo?', 'centro de costos', this); return false;"))
											);
										}else{
											echo $this->usuarios_model->getLinkPrivSm('centro_costo/activar/', array(
													'params'   => 'id='.$centro_costo->id_centro_costo,
													'btn_type' => 'btn-danger',
													'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar el centro de costo?', 'centro de costos', this); return false;"))
											);
										}

										?>
								</td>
							</tr>
					<?php }?>
						  </tbody>
					  </table>

					  <?php
						//Paginacion
						$this->pagination->initialize(array(
								'base_url' 			=> base_url($this->uri->uri_string()).'?'.MyString::getVarsLink(array('pag')).'&',
								'total_rows'		=> $centros_costos['total_rows'],
								'per_page'			=> $centros_costos['items_per_page'],
								'cur_page'			=> $centros_costos['result_page']*$centros_costos['items_per_page'],
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


