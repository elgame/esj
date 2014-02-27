		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Cuentas Bancarias
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-suitcase"></i> Cuentas Bancarias</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/banco/cuentas/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">Buscar</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>" 
									class="input-large search-query" placeholder="banco, cuenta, alias, empresa" autofocus> |

								<label for="fstatus">Estado</label>
								<select name="fstatus">
									<option value="ac" <?php echo set_select('fstatus', 'ac', false, $this->input->get('fstatus')); ?>>ACTIVOS</option>
									<option value="e" <?php echo set_select('fstatus', 'e', false, $this->input->get('fstatus')); ?>>ELIMINADOS</option>
									<option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>TODOS</option>
								</select>

								<input type="submit" name="enviar" value="Buscar" class="btn">
							</fieldset>
						</form>

						<?php 
						echo $this->usuarios_model->getLinkPrivSm('banco/cuentas_agregar/', array(
										'params'   => '',
										'btn_type' => 'btn-success pull-right',
										'attrs' => array('style' => 'margin-bottom: 10px;') )
								);
						 ?>
						<table class="table table-striped table-bordered bootstrap-datatable">
						  <thead>
							  <tr>
							  	<th>Banco</th>
								  <th>Cuenta</th>
									<th>Alias</th>
									<th>Empresa</th>
									<th>Cta contpaq</th>
									<th>Estatus</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($cuentas['cuentas'] as $cuenta){ ?>
							<tr>
								<td><?php echo $cuenta->banco; ?></td>
								<td><?php echo $cuenta->numero; ?></td>
								<td><?php echo $cuenta->alias; ?></td>
								<td><?php echo $cuenta->nombre_fiscal; ?></td>
								<td><?php echo $cuenta->cuenta_cpi; ?></td>
								<td>
									<?php
										if($cuenta->status == 'ac'){
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
										echo $this->usuarios_model->getLinkPrivSm('banco/cuentas_modificar/', array(
												'params'   => 'id='.$cuenta->id_cuenta,
												'btn_type' => 'btn-success')
										);
										if ($cuenta->status == 'ac') {
											echo $this->usuarios_model->getLinkPrivSm('banco/cuentas_eliminar/', array(
													'params'   => 'id='.$cuenta->id_cuenta,
													'btn_type' => 'btn-danger',
													'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar la cuenta?', 'cuentas', this); return false;"))
											);
										}else{
											echo $this->usuarios_model->getLinkPrivSm('banco/cuentas_activar/', array(
													'params'   => 'id='.$cuenta->id_cuenta,
													'btn_type' => 'btn-danger',
													'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar la cuenta?', 'cuentas', this); return false;"))
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
								'base_url' 			=> base_url($this->uri->uri_string()).'?'.String::getVarsLink(array('pag')).'&',
								'total_rows'		=> $cuentas['total_rows'],
								'per_page'			=> $cuentas['items_per_page'],
								'cur_page'			=> $cuentas['result_page']*$cuentas['items_per_page'],
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


