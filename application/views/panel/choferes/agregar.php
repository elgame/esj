		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/choferes/'); ?>">Choferes</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-plus"></i> Agregar chofer</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/choferes/agregar'); ?>" method="post" class="form-horizontal" enctype="multipart/form-data">
						  <fieldset>
								<legend></legend>

								<div class="control-group">
								  <label class="control-label" for="fnombre">Nombre </label>
								  <div class="controls">
										<input type="text" name="fnombre" id="fnombre" class="span6" maxlength="120"
										value="<?php echo set_value('fnombre'); ?>" required autofocus placeholder="Jair Macias, Pedro Castañeda">
								  </div>
								</div>

                <div class="control-group">
                  <label class="control-label" for="ftelefono">Teléfono</label>
                  <div class="controls">
                    <input type="text" name="ftelefono" id="ftelefono" class="span6" value="<?php echo set_value('ftelefono'); ?>" maxlength="15" placeholder="312 309 1234">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fid_nextel">ID Nextel</label>
                  <div class="controls">
                    <input type="text" name="fid_nextel" id="fid_nextel" class="span6" value="<?php echo set_value('fid_nextel'); ?>" maxlength="20" placeholder="55*97*103954">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fno_licencia">No. Licencia</label>
                  <div class="controls">
                    <input type="text" name="fno_licencia" id="fno_licencia" class="span6" value="<?php echo set_value('fno_licencia'); ?>" maxlength="50" placeholder="123457890">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fno_ife">No. IFE</label>
                  <div class="controls">
                    <input type="text" name="fno_ife" id="fno_ife" class="span6" value="<?php echo set_value('fno_ife'); ?>" maxlength="30" placeholder="ASDF1234GHA">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="flicencia_doc">Licencia Documento</label>
                  <div class="controls">
                    <input type="file" name="flicencia_doc" />
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fife_docu">IFE Documento</label>
                  <div class="controls">
                    <input type="file" name="fife_docu" />
                  </div>
                </div>

								<div class="form-actions">
								  <button type="submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/choferes/'); ?>" class="btn">Cancelar</a>
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

