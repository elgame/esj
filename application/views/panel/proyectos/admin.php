		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Proyectos
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-user"></i> Proyectos</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/proyectos/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">Buscar</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
									class="input-large search-query" placeholder="Nombre" autofocus> |

                <label class="control-label" for="fempresa">Empresa </label>
                <input type="text" name="fempresa" id="fempresa" class="input-xlarge search-query" value="<?php echo set_value_get('fempresa', $empresa->nombre_fiscal); ?>" placeholder="Nombre">
                <input type="hidden" name="did_empresa" value="<?php echo set_value_get('did_empresa', $empresa->id_empresa); ?>" id="did_empresa"> |

								<label for="fstatus">Estado</label>
								<select name="fstatus">
									<option value="t" <?php echo set_select('fstatus', 't', false, $this->input->get('fstatus')); ?>>ACTIVOS</option>
									<option value="f" <?php echo set_select('fstatus', 'f', false, $this->input->get('fstatus')); ?>>ELIMINADOS</option>
									<option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>TODOS</option>
								</select>

								<input type="submit" name="enviar" value="Buscar" class="btn">
							</fieldset>
						</form>


						<?php
						echo $this->usuarios_model->getLinkPrivSm('proyectos/agregar/', array(
										'params'   => '',
										'btn_type' => 'btn-success pull-right',
										'attrs' => array('style' => 'margin-bottom: 10px;') )
								);
						 ?>
						<table class="table table-striped table-bordered bootstrap-datatable">
						  <thead>
							  <tr>
							  	<th>Nombre</th>
								  <th>Empresa</th>
                  <th>Presupuesto</th>
                  <th>Fecha Ini</th>
                  <th>Fecha Fin</th>
                  <th>Estatus</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($proyectos['proyectos'] as $proyecto){ ?>
							<tr>
								<td><?php echo $proyecto->nombre; ?></td>
                <td><?php echo $proyecto->nombre_fiscal; ?></td>
                <td><?php echo number_format($proyecto->presupuesto, 2); ?></td>
                <td><?php echo $proyecto->fecha_inicio; ?></td>
								<td><?php echo $proyecto->fecha_terminacion; ?></td>
								<td>
									<?php
										if($proyecto->status == 't'){
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
                    echo $this->usuarios_model->getLinkPrivSm('proyectos/imprimir/', array(
                        'params'   => 'id='.$proyecto->id_proyecto,
                        'btn_type' => 'btn-info',
                        'attrs'    => ['target' => '_blank']
                      )
                    );

										if ($proyecto->status == 't') {
                      echo $this->usuarios_model->getLinkPrivSm('proyectos/modificar/', array(
                          'params'   => 'id='.$proyecto->id_proyecto,
                          'btn_type' => 'btn-success')
                      );

											echo $this->usuarios_model->getLinkPrivSm('proyectos/eliminar/', array(
														'params'   => 'id='.$proyecto->id_proyecto,
														'btn_type' => 'btn-danger',
														'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar el proyecto?', 'proyectos', this); return false;"))
											);
										}else{
											echo $this->usuarios_model->getLinkPrivSm('proyectos/activar/', array(
													'params'   => 'id='.$proyecto->id_proyecto,
													'btn_type' => 'btn-danger',
													'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar el proyecto?', 'proyectos', this); return false;"))
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
								'total_rows'		=> $proyectos['total_rows'],
								'per_page'			=> $proyectos['items_per_page'],
								'cur_page'			=> $proyectos['result_page']*$proyectos['items_per_page'],
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


