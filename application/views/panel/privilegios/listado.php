
		<div id="content" class="span10">
			<!-- content starts -->
			

			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="#">Privilegios</a>
					</li>
				</ul>
			</div>

			<div class="row-fluid">		
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-file"></i> Privilegios</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/privilegios/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">buscar:</label> 
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>" class="input-large" 
									placeholder="Modificar, usuarios/agregar" autofocus>
								
								<button class="btn">Buscar</button>
							</fieldset>
						</form>

						<?php 
						echo $this->usuarios_model->getLinkPrivSm('privilegios/agregar/', array(
										'params'   => '',
										'btn_type' => 'btn-success pull-right',
										'attrs' => array('style' => 'margin: 0px 0 10px 10px;') )
								);
						 ?>
						<table class="table table-striped table-bordered bootstrap-datatable">
						  <thead>
							  <tr>
								  <th>Nombre</th>
								  <th>Url accion</th>
								  <th>Mostrar menu</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>   
						  <tbody>
						<?php foreach($privilegios['privilegios'] as $priv){ ?>
								<tr>
									<td><?php echo $priv->nombre?></td>
									<td><?php echo $priv->url_accion; ?></td>
									<td><?php echo $priv->mostrar_menu; ?></td>
									<td class="center">
										<?php 
										echo $this->usuarios_model->getLinkPrivSm('privilegios/modificar/', array(
												'params'   => 'id='.$priv->id_privilegio,
												'btn_type' => 'btn-success')
										);
										echo $this->usuarios_model->getLinkPrivSm('privilegios/eliminar/', array(
												'params'   => 'id='.$priv->id_privilegio,
												'btn_type' => 'btn-danger',
												'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar el privilegio?', 'Privilegios', this); return false;"))
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
								'total_rows'		=> $privilegios['total_rows'],
								'per_page'			=> $privilegios['items_per_page'],
								'cur_page'			=> $privilegios['result_page']*$privilegios['items_per_page'],
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


