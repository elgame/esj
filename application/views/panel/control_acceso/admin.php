		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Control de acceso
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-user"></i> Control de acceso</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/control_acceso/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">Buscar</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
									class="input-large search-query" placeholder="GAS MENGUC SA DE CV, 5 DE MAYO" autofocus> |

								<select name="ffil_fecha">
									<option value="fecha_entrada">Fecha de entrada</option>
									<option value="fecha_salida">Fecha de salida</option>
								</select>
				        <label for="ffecha1" style="margin-top: 15px;">del</label>
                <input type="date" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2'); ?>" size="10">

								<input type="submit" name="enviar" value="Buscar" class="btn">
							</fieldset>
						</form>

						<?php
						echo $this->usuarios_model->getLinkPrivSm('control_acceso/entrada_salida/', array(
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
								  <th>Placas</th>
									<th>Asunto</th>
									<th>Departamento</th>
									<th>Fecha Entrada</th>
									<th>Fecha Salida</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($control_acceso['control_acceso'] as $control){ ?>
							<tr>
								<td><?php echo $control->id_control; ?></td>
								<td><?php echo $control->nombre; ?></td>
								<td><?php echo $control->placas; ?></td>
								<td><?php echo $control->asunto; ?></td>
								<td><?php echo $control->departamento; ?></td>
								<td><?php echo $control->fecha_entrada; ?></td>
								<td><?php echo $control->fecha_salida; ?></td>
								<td class="center">
								<?php
								if ($this->usuarios_model->tienePrivilegioDe('', 'control_acceso/modificar/')) {
								?>
									<a class="btn btn-success" href="<?php echo base_url('panel/control_acceso/entrada_salida/?id='.$control->id_control) ?>" title="Modificar">
										<i class="icon-edit icon-white"></i> <span class="hidden-tablet">Modificar</span></a>
								<?php
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
								'total_rows'		=> $control_acceso['total_rows'],
								'per_page'			=> $control_acceso['items_per_page'],
								'cur_page'			=> $control_acceso['result_page']*$control_acceso['items_per_page'],
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


