		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Trabajo agricola
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-user"></i> Trabajo agricola</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/btrabajo_agricola/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">Folio</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
									class="input-large search-query" placeholder="2, 451" autofocus> |

								<label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa'); ?>">

								<label for="fstatusb">Del</label>
                <input type="date" name="fechaini" class="input-medium" id="fechaini" value="<?php echo set_value_get('fechaini') ?>" placeholder="">

                <label for="fstatusb">Al</label>
                <input type="date" name="fechaend" class="input-medium" id="fechaend" value="<?php echo set_value_get('fechaend') ?>" placeholder="">

								<input type="submit" name="enviar" value="Buscar" class="btn">
							</fieldset>
						</form>

						<?php
						echo $this->usuarios_model->getLinkPrivSm('btrabajo_agricola/agregar/', array(
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
									<th>Vehiculo</th>
									<th>T Km</th>
									<th>T Hrs</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($btrabajo_agricola['btrabajo_agricola'] as $entrada){ ?>
							<tr>
								<td><?php echo $entrada->folio; ?></td>
								<td><?php echo $entrada->fecha; ?></td>
								<td><?php echo $entrada->vehiculo; ?></td>
								<td><?php echo $entrada->horometro_total; ?></td>
								<td><?php echo $entrada->total_hrs; ?></td>
								<td class="center">
										<?php
										echo $this->usuarios_model->getLinkPrivSm('btrabajo_agricola/modificar/', array(
												'params'   => 'id='.$entrada->id_trabajo_agricola,
												'btn_type' => 'btn-success')
										);
										echo $this->usuarios_model->getLinkPrivSm('btrabajo_agricola/printEntrada/', array(
												'params'   => 'id='.$entrada->id_trabajo_agricola,
												'btn_type' => 'btn-info',
												'attrs' => array('target' => "_blank"))
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
								'total_rows'		=> $btrabajo_agricola['total_rows'],
								'per_page'			=> $btrabajo_agricola['items_per_page'],
								'cur_page'			=> $btrabajo_agricola['result_page']*$btrabajo_agricola['items_per_page'],
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


