		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Papeleta de Salida
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-th-large"></i> Papeleta de Salida</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/rastreabilidad_paletas/'); ?>" method="get" class="form-search">
							<fieldset style="text-align: center;">
								<legend>Filtros</legend>

								<label for="fnombre">Buscar</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
									class="input-large search-query" placeholder="Cliente, Folio" autofocus> |

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-xlarge search-query" id="dempresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa'); ?>"> |

                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-medium search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date("Y-m-01")); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-medium search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', date("Y-m-d")); ?>" size="10">

                <br>
								<label for="ftipo">Tipo</label>
								<select name="ftipo" id="ftipo">
                  <option <?php echo set_select('ftipo', 'todos', false, $this->input->get('ftipo')); ?> value="todos">Todos</option>
									<option <?php echo set_select('ftipo', 'lo', false, $this->input->get('ftipo')); ?> value="lo">Local</option>
                  <option <?php echo set_select('ftipo', 'na', false, $this->input->get('ftipo')); ?> value="na">Nacional</option>
                  <option <?php echo set_select('ftipo', 'naex', false, $this->input->get('ftipo')); ?> value="naex">Nacional o Exportación (pallets)</option>
                </select>

								<label for="fstatus">Estado</label>
								<select name="fstatus">
									<option value="todos" <?php echo set_select('fstatus', 'todos', false, $this->input->get('fstatus')); ?>>Todos</option>
                  <option value="r" <?php echo set_select('fstatus', 'r', false, $this->input->get('fstatus')); ?>>Registrados</option>
									<option value="f" <?php echo set_select('fstatus', 'f', false, $this->input->get('fstatus')); ?>>Finalizados</option>
									<option value="ca" <?php echo set_select('fstatus', 'ca', false, $this->input->get('fstatus')); ?>>Cancelados</option>
								</select>

								<input type="submit" name="enviar" value="Buscar" class="btn">
							</fieldset>
						</form>

						<?php
							echo $this->usuarios_model->getLinkPrivSm('rastreabilidad_paletas/agregar/', array(
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
								  <th>Clientes</th>
									<th>Tipo</th>
									<th>Estatus</th>
								  <th style="width: 150px">Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($paletas['paletas'] as $paleta){ ?>
							<tr>
								<td><?php echo $paleta->folio; ?></td>
								<td><?php echo $paleta->fecha; ?></td>
								<td><?php echo $paleta->clientes; ?></td>
								<td>
                  <?php
                    $v_status = 'Local';
                    if($paleta->tipo === 'na'){
                      $v_status = 'Nacional';
                    }elseif($paleta->tipo === 'naex'){
                      $v_status = 'Nacional o Exportación (pallets)';
                    }
                  ?>
                  <?php echo $v_status; ?>
                </td>
								<td>
									<?php
										$v_status = 'Registrado';
										$vlbl_status = 'label-success';
										if($paleta->status === 'f'){
                      $v_status = 'Finalizado';
                      $vlbl_status = 'label-info';
										}elseif($paleta->status === 'ca'){
											$v_status = 'Cancelado';
											$vlbl_status = 'label-warning';
										}
									?>
									<span class="label <?php echo $vlbl_status; ?>"><?php echo $v_status; ?></span>
								</td>
								<td class="center">
										<?php
										echo $this->usuarios_model->getLinkPrivSm('rastreabilidad_paletas/modificar/', array(
												'params'   => 'id='.$paleta->id_paleta_salida,
												'btn_type' => 'btn-success')
										);
										echo $this->usuarios_model->getLinkPrivSm('rastreabilidad_paletas/imprimir/', array(
												'params'   => 'id='.$paleta->id_paleta_salida,
												'btn_type' => 'btn-info',
												'attrs' => array('target' => '_BLANCK') )
										);
										echo $this->usuarios_model->getLinkPrivSm('rastreabilidad_paletas/eliminar/', array(
												'params'   => 'id='.$paleta->id_paleta_salida,
												'btn_type' => 'btn-danger',
												'attrs' => array('id' => 'pallet'.$paleta->id_paleta_salida, 'onclick' => "msb.confirm('Estas seguro de eliminar la paleta de salida?', 'paleta', this); return false;"))
										);

                    if ($paleta->status !== 'f' && $this->usuarios_model->tienePrivilegioDe('', 'rastreabilidad_paletas/remisionar/')) {
                      echo '<a class="btn btn-warning modal-series" title="Remisionar" data-id="'.$paleta->id_paleta_salida.'">
                        <i class="icon-qrcode icon-white"></i> <span class="hidden-tablet">Remisionar</span></a>';
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
								'total_rows'		=> $paletas['total_rows'],
								'per_page'			=> $paletas['items_per_page'],
								'cur_page'			=> $paletas['result_page']*$paletas['items_per_page'],
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

<!-- Modal series -->
<div id="modal-series" class="modal modal-w50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="myModalLabel">Series</h3>
  </div>
  <div class="modal-body">
    <div class="row-fluid">
      <select id="serieRemisionar">
        <?php foreach ($series as $key => $value): ?>
        <option value="<?php echo $value->serie ?>"><?php echo $value->serie.' - '.$value->leyenda ?></option>
        <?php endforeach ?>
      </select>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
    <a href="" class="btn btn-primary" id="BtnRemisionar" data-href="<?php echo base_url('panel/rastreabilidad_paletas/remisionar/?id='); ?>">Seleccionar</a>
  </div>
</div><!--/modal series -->

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


