		<div id="content" class="span<?php echo isset($_GET['modal']) ? '12' : '10' ?>">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Productos
					</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span6">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-sign-blank"></i> Familias</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/productos/'); ?>" method="get" class="form-search" id="frmfamilias">

							<input type="text" name="fempresa" value="<?php echo set_value_get('fempresa') ?>" id="fempresa" class="input-xlarge" placeholder="Empresa" autofocus>
                  			<input type="hidden" name="fid_empresa" value="<?php echo set_value_get('fid_empresa') ?>" id="fid_empresa">

							<input type="submit" name="enviar" value="Buscar" class="btn">

							<?php 
							echo $this->usuarios_model->getLinkPrivSm('productos/agregar_familia/', array(
									'params'   => '',
									'btn_type' => 'btn-success pull-right',
									'attrs' => array('rel' => 'superbox-40x550') )
								);
							 ?>
						</form>

						<div id="content_familias">
						<?php echo $html_familias; ?>
						</div>

					</div>
				</div><!--/span-->

				<div id="boxproductos" class="box span6" style="display: none;">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-th-large"></i> Productos <span id="familia_sel"></span></h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/productos/'); ?>" method="get" class="form-search" id="frmproductos">

							<input type="text" name="fproducto" value="<?php echo set_value_get('fproducto') ?>" id="fproducto" class="input-xlarge" placeholder="Buscar productos">
                  			<input type="hidden" name="fid_familia" value="<?php echo set_value_get('fid_familia') ?>" id="fid_familia">

							<input type="submit" name="enviar" value="Buscar" class="btn">

							<?php 
							echo $this->usuarios_model->getLinkPrivSm('productos/agregar/', array(
									'params'   => '',
									'btn_type' => 'btn-success pull-right',
									'attrs' => array('id' => 'addproducto', 'rel' => 'superbox-40x500') )
								);
							 ?>
						</form>

						<div id="content_productos">
						<?php echo $html_productos; ?>
						</div>

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


