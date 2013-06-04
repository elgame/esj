		<div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/privilegios'); ?>">Privilegios</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar Privilegio</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/privilegios/modificar/?'.String::getVarsLink(array('msg'))); ?>" method="post" enctype="multipart/form-data" class="form-horizontal" id="form">
              <fieldset>
                <legend></legend>

                <div class="span7">
	                <div class="control-group">
	                  <label class="control-label" for="dnombre">*Nombre </label>
	                  <div class="controls">
											<input type="text" name="dnombre" id="dnombre" value="<?php echo isset($privilegio->nombre)?$privilegio->nombre:''; ?>" class="input-xlarge" autofocus>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="durl_accion">*Url accion </label>
	                  <div class="controls">
	                    <input type="text" name="durl_accion" id="durl_accion" value="<?php echo isset($privilegio->url_accion)?$privilegio->url_accion:''; ?>" class="input-xlarge" >
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="durl_icono">*icono </label>
	                  <div class="controls">
	                    <input type="text" name="durl_icono" id="durl_icono" class="input-large" value="<?php echo isset($privilegio->url_icono)?$privilegio->url_icono:''; ?>">
	                    <p class="help-block">Iconos de Bootstrap</p>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dmostrar_menu">Mostrar menu </label>
	                  <div class="controls">
	                    <input type="checkbox" name="dmostrar_menu" id="dmostrar_menu" value="si" 
												<?php echo (isset($privilegio->mostrar_menu)? ($privilegio->mostrar_menu=='t'? 'checked': ''): ''); ?>>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dtarget_blank">Target blank</label>
	                  <div class="controls">
	                    <input type="checkbox" name="dtarget_blank" id="dtarget_blank" value="si" 
												<?php echo (isset($privilegio->target_blank)? ($privilegio->target_blank=='t'? 'checked': ''): ''); ?>>
	                  </div>
	                </div>
								</div> <!--/span -->

								<div class="span4">
	                <div class="control-group">
	                  <label class="control-label" style="width: 100px;">Privilegios </label>
	                  <div class="controls" style="margin-left: 120px;">
	                  	<div style="height: 300px; overflow-y: auto; border:1px #ddd solid;">
	                    	<?php echo $this->usuarios_model->getFrmPrivilegios(0, true, (isset($privilegio->id_padre)? $privilegio->id_padre: null), true); ?>
	                    </div>
	                  </div>
	                </div>
	              </div> <!--/span-->

	              <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/privilegios/'); ?>" class="btn">Cancelar</a>
                </div>
              </fieldset>
            </form>

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

