		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Empleados rancho
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-user"></i> Empleados rancho</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/empleadosr/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">Buscar</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
									class="input-large search-query" placeholder="jorge, jorge32, admin@host.com" autofocus>

                <label class="control-label" for="fempresa">Empresa </label>
                <input type="text" name="fempresa" id="fempresa" class="input-xlarge search-query" value="<?php echo set_value_get('fempresa', $empresa->nombre_fiscal); ?>" placeholder="Nombre">
                <input type="hidden" name="did_empresa" value="<?php echo set_value_get('did_empresa', $empresa->id_empresa); ?>" id="did_empresa">||

								<label for="fstatus">Estado</label>
								<select name="fstatus">
									<option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>TODOS</option>
									<option value="1" <?php echo set_select('fstatus', '1', false, $this->input->get('fstatus')); ?>>ACTIVOS</option>
									<option value="0" <?php echo set_select('fstatus', '0', false, $this->input->get('fstatus')); ?>>ELIMINADOS</option>
								</select>

								<input type="submit" name="enviar" value="Buscar" class="btn">
							</fieldset>
						</form>

						<?php
						echo $this->usuarios_model->getLinkPrivSm('empleadosr/agregar/', array(
										'params'   => '',
										'btn_type' => 'btn-success pull-right',
										'attrs' => array('style' => 'margin-bottom: 10px;') )
								);
						 ?>
						<table class="table table-striped table-bordered bootstrap-datatable">
						  <thead>
							  <tr>
                  <th>No Trabajador</th>
							  	<th>Nombre</th>
									<th>RFC</th>
									<th>Banco</th>
									<th>No Seguro</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($usuarios['usuarios'] as $usuario){ ?>
							<tr>
                <td><?php echo $usuario->id_usuario ?></td>
								<td><?php echo $usuario->nombre.' '.$usuario->apellido_paterno.' '.$usuario->apellido_materno; ?></td>
								<td><?php echo $usuario->rfc; ?></td>
								<td><?php echo $usuario->cuenta_banco; ?></td>
								<td><?php echo $usuario->no_seguro; ?></td>
								<td class="center">
										<?php
										echo $this->usuarios_model->getLinkPrivSm('empleadosr/modificar/', array(
												'params'   => 'id='.$usuario->id_usuario,
												'btn_type' => 'btn-success')
										);
										if ($usuario->status == 't') {
											if($usuario->id_usuario != $this->session->userdata('id_usuario'))
												echo $this->usuarios_model->getLinkPrivSm('empleadosr/eliminar/', array(
														'params'   => 'id='.$usuario->id_usuario,
														'btn_type' => 'btn-danger',
														'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar el empleado?', 'Usuarios', this); return false;"))
											);
										}else{
											echo $this->usuarios_model->getLinkPrivSm('empleadosr/activar/', array(
													'params'   => 'id='.$usuario->id_usuario,
													'btn_type' => 'btn-danger',
													'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar el empleado?', 'Usuarios', this); return false;"))
											);
										}
                    echo '<a class="btn btn-info" href="'.base_url('panel/empleadosr/show_otros/?eid='.$usuario->id_usuario).'" title="Prestamos" rel="superbox-50x450">
                            <i class="icon-edit icon-white"></i> <span class="hidden-tablet">Prestamos</span></a>';


                      echo $this->usuarios_model->getLinkPrivSm('empleados/historial/', array(
                        'params'   => 'id='.$usuario->id_usuario,
                        'btn_type' => 'btn-info',
                        'attrs' => array('target' => '_BLANK')
                      ))
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
								'total_rows'		=> $usuarios['total_rows'],
								'per_page'			=> $usuarios['items_per_page'],
								'cur_page'			=> $usuarios['result_page']*$usuarios['items_per_page'],
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


