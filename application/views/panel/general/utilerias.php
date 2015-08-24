		<div id="content" class="span10">
			<!-- content starts -->

			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Utilerias
					</li>
				</ul>
			</div>

      <div class="row-fluid sortable">
        <div class="box span6">
          <div class="box-header well">
            <h2>Drop all</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <?php
            echo $this->usuarios_model->getLinkPrivSm('utilerias/drop_all/', array(
                          'params'   => 'id=1',
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar todos los archivos del sistema?', 'Utilidades', this); return false;"))
                      );
            ?>
          </div>
        </div><!--/span-->

        <div class="box span6">
          <div class="box-header well">
            <h2>Productos bajos de inventario</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content" style="height:300px;overflow-y:auto;">

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