<div class="container-fluid">
	<div class="row-fluid">
			
		<div class="row-fluid">
			<div class="well span5 center login-box">
				<div class="alert alert-info" style="font-size: 22px;font-weight: bold;">
					Iniciar sesión
				</div>
				<form class="form-horizontal" action="<?php echo base_url('panel/home/login'); ?>" method="post">
					<fieldset>
						<div class="input-prepend" title="Usuario" data-rel="tooltip">
							<span class="add-on"><i class="icon-user"></i>
							</span><input autofocus class="input-large span10" name="usuario" id="usuario" type="text" placeholder="admin">
						</div>
						<div class="clearfix"></div>

						<div class="input-prepend mtop" title="Contraseña" data-rel="tooltip">
							<span class="add-on"><i class="icon-lock"></i>
							</span><input class="input-large span10" name="pass" id="pass" type="password" placeholder="******">
						</div>
						<div class="clearfix"></div>

						<div class="input-prepend mtop">
						<label class="remember" for="remember"><input type="checkbox" name="remember" id="remember" />Remember me</label>
						</div>
						<div class="clearfix"></div>

						<p class="center span5 mtop">
						<button type="submit" class="btn btn-primary">Iniciar sesión</button>
						</p>
					</fieldset>
				</form>
			</div><!--/span-->
		</div><!--/row-->

	</div><!--/fluid-row-->

</div><!--/.fluid-container-->


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
