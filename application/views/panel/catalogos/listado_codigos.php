
		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="#">Catalogo codigos</a>
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-file"></i> Catalogo codigos</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/catalogos_sft/cat_codigos'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">buscar:</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>" class="input-large"
									placeholder="Codigo o nombre" autofocus>

								<button class="btn">Buscar</button>

								<a href="<?php echo base_url('panel/catalogos_sft/imprimir_catalogo_codigos'); ?>" class="btn btn-info pull-right" target="_blank"><i class="icon icon-print"></i> Lista</a>
								<a href="<?php echo base_url('panel/catalogos_sft/xls_catalogo_codigos'); ?>" class="btn btn-info pull-right" target="_blank"><i class="icon icon-table"></i> Excel</a>
							</fieldset>
						</form>

						<?php
						echo $this->usuarios_model->getLinkPrivSm('catalogos_sft/agregar_codigos/', array(
										'params'   => '',
										'btn_type' => 'btn-success pull-right',
										'attrs' => array('style' => 'margin: 0px 0 10px 10px;') )
								);
						 ?>
						<table class="table table-striped table-bordered bootstrap-datatable">
						  <thead>
							  <tr>
								  <th>Codigo</th>
								  <th>Nombre</th>
								  <th>Tipo</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($cat_codigos['cat_codigos'] as $area){ ?>
								<tr>
									<td><?php echo $area->codigo; ?></td>
									<td><?php echo $area->nombre; ?></td>
									<td><?php echo $area->descripcion; ?></td>
									<td class="center">
										<?php
										echo $this->usuarios_model->getLinkPrivSm('catalogos_sft/modificar_codigos/', array(
												'params'   => 'id='.$area->id_cat_codigos,
												'btn_type' => 'btn-success')
										);
										echo $this->usuarios_model->getLinkPrivSm('catalogos_sft/eliminar_codigos/', array(
												'params'   => 'id='.$area->id_cat_codigos,
												'btn_type' => 'btn-danger',
												'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar del catalogo?', 'Catalogo', this); return false;"))
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
								'total_rows'		=> $cat_codigos['total_rows'],
								'per_page'			=> $cat_codigos['items_per_page'],
								'cur_page'			=> $cat_codigos['result_page']*$cat_codigos['items_per_page'],
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


