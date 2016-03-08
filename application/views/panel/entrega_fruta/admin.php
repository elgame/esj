		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Entrega de fruta
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-user"></i> Entrega de fruta</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/entrega_fruta/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">Folio</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
									class="input-large search-query" placeholder="2, 451" autofocus> |

								<label for="farea">Area</label>
                <select name="farea" class="input-medium" id="farea">
                  <option value=""></option>
                  <?php foreach ($areas['areas'] as $area){ ?>
                    <option value="<?php echo $area->id_area ?>"
                      <?php echo set_select('farea', $area->id_area, false, $this->input->get('farea')) ?>><?php echo $area->nombre ?></option>
                  <?php } ?>
                </select>

								<label for="fstatusb">Del</label>
                <input type="date" name="fechaini" class="input-medium" id="fechaini" value="<?php echo set_value_get('fechaini') ?>" placeholder="">

                <label for="fstatusb">Al</label>
                <input type="date" name="fechaend" class="input-medium" id="fechaend" value="<?php echo set_value_get('fechaend') ?>" placeholder="">

								<input type="submit" name="enviar" value="Buscar" class="btn">
							</fieldset>
						</form>

						<?php
						echo $this->usuarios_model->getLinkPrivSm('entrega_fruta/agregar/', array(
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
									<th>Rancho</th>
									<th>Area</th>
									<th>Total</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($entrega_fruta['entrega_fruta'] as $entrada){ ?>
							<tr>
								<td><?php echo $entrada->folio; ?></td>
								<td><?php echo $entrada->fecha; ?></td>
								<td><?php echo $entrada->rancho; ?></td>
								<td><?php echo $entrada->area; ?></td>
								<td><?php echo $entrada->total; ?></td>
								<td class="center">
										<?php
										echo $this->usuarios_model->getLinkPrivSm('entrega_fruta/modificar/', array(
												'params'   => 'id='.$entrada->id_entrega_fruta,
												'btn_type' => 'btn-success')
										);
										echo $this->usuarios_model->getLinkPrivSm('entrega_fruta/printEntrada/', array(
												'params'   => 'id='.$entrada->id_entrega_fruta,
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
								'total_rows'		=> $entrega_fruta['total_rows'],
								'per_page'			=> $entrega_fruta['items_per_page'],
								'cur_page'			=> $entrega_fruta['result_page']*$entrega_fruta['items_per_page'],
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


