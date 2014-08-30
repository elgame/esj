		<div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/cuentas_cpi'); ?>">Cuentas CPI</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar Cuenta</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/cuentas_cpi/modificar/?'.String::getVarsLink(array('msg'))); ?>" method="post" class="form-horizontal" id="form">
              <fieldset>
                <legend></legend>

                <div class="span7">
                	<div class="control-group">
	                  <label class="control-label" for="dnombre">*Empresa </label>
	                  <div class="controls">
	                  	<input type="text" name="dempresa" class="input-xlarge" id="dempresa" value="<?php echo isset($cuenta['info']->nombre_fiscal)?$cuenta['info']->nombre_fiscal:''; ?>" readonly required>
                			<input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo isset($cuenta['info']->id_empresa)?$cuenta['info']->id_empresa:''; ?>" required>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dnombre">*Nombre </label>
	                  <div class="controls">
											<input type="text" name="dnombre" id="dnombre" value="<?php echo isset($cuenta['info']->nombre)?$cuenta['info']->nombre:''; ?>" class="input-xlarge" autofocus required>
	                  </div>
	                </div>

	                <div class="control-group">
	                  <label class="control-label" for="dcuenta">*Cuenta </label>
	                  <div class="controls">
	                    <input type="text" name="dcuenta" id="dcuenta" value="<?php echo isset($cuenta['info']->cuenta)?$cuenta['info']->cuenta:''; ?>" class="input-xlarge" required>
	                  </div>
	                </div>
								</div> <!--/span -->

								<div class="span4">
	                <div class="control-group">
	                  <label class="control-label" style="width: 100px;">Cuentas </label>
	                  <div class="controls" style="margin-left: 120px;">
	                  	<div id="lista_cuentas" style="height: 300px; overflow-y: auto; border:1px #ddd solid;">
	                    	<?php echo $cuentas; ?>
	                    </div>
	                  </div>
	                </div>
	              </div> <!--/span-->

	              <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/cuentas_cpi/'); ?>" class="btn">Cancelar</a>
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

