		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Proveedores
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-user"></i> Proveedores</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/proveedores/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">Buscar</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
									class="input-large search-query" placeholder="GAS MENGUC SA DE CV, 5 DE MAYO" autofocus> |

								<label class="control-label" for="fempresa">Empresa </label>
				                <input type="text" name="fempresa" id="fempresa" class="input-xlarge search-query" value="<?php echo set_value_get('fempresa', $empresa->nombre_fiscal); ?>" placeholder="Nombre">
				                <input type="hidden" name="did_empresa" value="<?php echo set_value_get('did_empresa', $empresa->id_empresa); ?>" id="did_empresa"> |

								<label for="fstatus">Estado</label>
								<select name="fstatus">
									<option value="ac" <?php echo set_select('fstatus', 'ac', false, $this->input->get('fstatus')); ?>>ACTIVOS</option>
									<option value="e" <?php echo set_select('fstatus', 'e', false, $this->input->get('fstatus')); ?>>ELIMINADOS</option>
									<option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>TODOS</option>
								</select>

								<label for="ftipo_proveedor">Tipo</label>
								<select name="ftipo_proveedor">
									<option value="todos" <?php echo set_select('ftipo_proveedor', 'todos', false, $this->input->get('ftipo_proveedor')); ?>>TODOS</option>
									<option value="in" <?php echo set_select('ftipo_proveedor', 'in', false, $this->input->get('ftipo_proveedor')); ?>>INSUMOS</option>
									<option value="fr" <?php echo set_select('ftipo_proveedor', 'fr', false, $this->input->get('ftipo_proveedor')); ?>>FRUTA</option>
								</select>

								<input type="submit" name="enviar" value="Buscar" class="btn">
							</fieldset>
						</form>

            <a href="<?php echo base_url('panel/proveedores/catalogo_xls/?'.MyString::getVarsLink(array('fnombre')) ); ?>"
                class="pull-left">
              <i class="icon-table"></i> Catalogo</a>
						<?php
						echo $this->usuarios_model->getLinkPrivSm('proveedores/agregar/', array(
										'params'   => '',
										'btn_type' => 'btn-success pull-right',
										'attrs' => array('style' => 'margin-bottom: 10px;') )
								);
						 ?>
						<table class="table table-striped table-bordered bootstrap-datatable">
						  <thead>
							  <tr>
                  <th>ID</th>
							  	<th>Nombre</th>
								  <th>Direccion</th>
									<th>Telefono</th>
									<th>Tipo</th>
									<th>Estatus</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($proveedores['proveedores'] as $proveedor){ ?>
							<tr>
                <td><?php echo $proveedor->id_proveedor; ?></td>
								<td><?php echo $proveedor->nombre_fiscal; ?></td>
								<td><?php echo $proveedor->direccion; ?></td>
								<td><?php echo $proveedor->telefono; ?></td>
								<td>
									<?php
										if($proveedor->tipo_proveedor == 'in'){
											$v_status = 'Insumos';
											$vlbl_status = 'label-info';
										}else{
											$v_status = 'Fruta';
											$vlbl_status = 'label-info';
										}
									?>
									<span class="label <?php echo $vlbl_status; ?>"><?php echo $v_status; ?></span>
								</td>
								<td>
									<?php
										if($proveedor->status == 'ac'){
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
										echo $this->usuarios_model->getLinkPrivSm('proveedores/modificar/', array(
												'params'   => 'id='.$proveedor->id_proveedor,
												'btn_type' => 'btn-success')
										);
										if ($proveedor->status == 'ac') {
												echo $this->usuarios_model->getLinkPrivSm('proveedores/eliminar/', array(
														'params'   => 'id='.$proveedor->id_proveedor,
														'btn_type' => 'btn-danger',
														'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar el proveedor?', 'proveedores', this); return false;"))
											);
										}else{
											echo $this->usuarios_model->getLinkPrivSm('proveedores/activar/', array(
													'params'   => 'id='.$proveedor->id_proveedor,
													'btn_type' => 'btn-danger',
													'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar el proveedor?', 'proveedores', this); return false;"))
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
								'total_rows'		=> $proveedores['total_rows'],
								'per_page'			=> $proveedores['items_per_page'],
								'cur_page'			=> $proveedores['result_page']*$proveedores['items_per_page'],
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


