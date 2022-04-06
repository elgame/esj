
		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="#">Cuentas</a>
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-file"></i> Cuentas</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/cuentas_cpi/'); ?>" method="get" class="form-search">
							<fieldset>
								<legend>Filtros</legend>

								<label for="fnombre">Buscar</label>
								<input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>" class="input-large"
									placeholder="No Cuenta, Nombre Cuenta" autofocus>

								<label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa'); ?>">


								<button class="btn">Buscar</button>
							</fieldset>
						</form>

						<?php
						echo $this->usuarios_model->getLinkPrivSm('cuentas_cpi/agregar/', array(
										'params'   => '',
										'btn_type' => 'btn-success pull-right',
										'attrs' => array('style' => 'margin: 0px 0 10px 10px;') )
								);
						 ?>
						<table class="table table-striped table-bordered bootstrap-datatable">
						  <thead>
							  <tr>
							  	<th>Cuenta</th>
								  <th>Nombre</th>
                  <th>Empresa</th>
								  <th>Registro Patronal</th>
								  <th>Opciones</th>
							  </tr>
						  </thead>
						  <tbody>
						<?php foreach($cuentas['cuentas'] as $priv){ ?>
								<tr>
									<td><?php echo $priv->cuenta ?></td>
									<td><?php echo $priv->nombre; ?></td>
                  <td><?php echo $priv->nombre_fiscal; ?></td>
									<td><?php echo $priv->registro_patronal; ?></td>

									<td class="center">
										<?php
										echo $this->usuarios_model->getLinkPrivSm('cuentas_cpi/modificar/', array(
												'params'   => 'id='.$priv->id_cuenta,
												'btn_type' => 'btn-success')
										);
										echo $this->usuarios_model->getLinkPrivSm('cuentas_cpi/eliminar/', array(
												'params'   => 'id='.$priv->id_cuenta,
												'btn_type' => 'btn-danger',
												'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar la Cuenta?', 'cuentas_cpi', this); return false;"))
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
								'base_url' 			=> base_url($this->uri->uri_string()).'?'.MyString::getVarsLink(array('pag')).'&',
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


