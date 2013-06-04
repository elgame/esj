		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="#">Empresas</a>
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-user"></i> Empresas</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
							<a href="#" class="btn btn-close btn-round"><i class="icon-remove"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/empresas'); ?>" method="get" class="form-search">
							<div class="form-actions form-filters">
								<label for="fnombre">Nombre</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>" class="input-medium search-query" autofocus>

								<input type="submit" name="enviar" value="Enviar" class="btn">
							</div>
						</form>

						<?php 
						echo $this->empleados_model->getLinkPrivSm('empresas/agregar/', array(
										'params'   => '',
										'btn_type' => 'btn-success pull-right',
										'attrs' => array('style' => 'margin-bottom: 10px;') )
								);
						 ?>
						<table class="table table-striped table-bordered bootstrap-datatable">
						  <thead>
							  <tr>
								  <th>Nombre</th>
									<th>RFC</th>
									<th>Telefono</th>
								  <th>Opc</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($empresas['empresas'] as $empresa){?>
								<tr>
									<td><?php echo $empresa->nombre_fiscal; ?></td>
									<td><?php echo $empresa->rfc; ?></td>
									<td><?php echo $empresa->telefono; ?></td>
									<td class="center">
											<?php 
											echo $this->empleados_model->getLinkPrivSm('empresas/modificar/', array(
													'params'   => 'id='.$empresa->id_empresa,
													'btn_type' => 'btn-success')
											);
											echo $this->empleados_model->getLinkPrivSm('empresas/eliminar/', array(
													'params'   => 'id='.$empresa->id_empresa,
													'btn_type' => 'btn-danger',
													'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar?', 'Clientes', this); return false;"))
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
								'total_rows'		=> $empresas['total_rows'],
								'per_page'			=> $empresas['items_per_page'],
								'cur_page'			=> $empresas['result_page']*$empresas['items_per_page'],
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
