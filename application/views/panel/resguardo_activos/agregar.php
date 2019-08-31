    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/resguardos_activos/'); ?>">Resguardos</a> <span class="divider">/</span>
          </li>
          <li>Agregar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-plus"></i> Agregar Resguardo</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/resguardos_activos/agregar/' ); ?>" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>

                <div class="span6">

                  <div class="control-group">
                    <label class="control-label" for="fempresa">Empresa </label>
                    <div class="controls">
                    <input type="text" name="fempresa" id="fempresa" class="span10" value="<?php echo set_value('fempresa', $empresa->nombre_fiscal); ?>" placeholder="Nombre" required autofocus>
                    <input type="hidden" name="did_empresa" value="<?php echo set_value('did_empresa', $empresa->id_empresa); ?>" id="did_empresa">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fproducto">Producto/Activo </label>
                    <div class="controls">
                      <input type="text" name="fproducto" id="fproducto" class="span10" maxlength="140"
                       value="<?php echo set_value('fproducto'); ?>" required placeholder="">
                      <input type="hidden" name="fid_producto" value="<?php echo set_value('fid_producto'); ?>" id="fid_producto">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fentrego">Entrego </label>
                    <div class="controls">
                      <input type="text" name="fentrego" id="fentrego" class="span10" maxlength="140"
                       value="<?php echo set_value('fentrego'); ?>" required placeholder="">
                      <input type="hidden" name="fid_entrego" value="<?php echo set_value('fid_entrego'); ?>" id="fid_entrego">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="frecibio">Recibió </label>
                    <div class="controls">
                      <input type="text" name="frecibio" id="frecibio" class="span10" maxlength="140"
                       value="<?php echo set_value('frecibio'); ?>" required placeholder="">
                      <input type="hidden" name="fid_recibio" value="<?php echo set_value('fid_recibio'); ?>" id="fid_recibio">
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="span5">

                  <div class="control-group">
                    <label class="control-label" for="ftipo">Tipo </label>
                    <div class="controls">
                      <select name="ftipo" id="ftipo">
                        <option value="resguardo">Resguardo</option>
                        <option value="asignacion">Asignación</option>
                      </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="ffecha_entrego">Fecha de entrega </label>
                    <div class="controls">
                      <input type="date" name="ffecha_entrego" id="ffecha_entrego" class="span12" value="<?php echo set_value('ffecha_entrego', date("Y-m-d")); ?>" required>
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/resguardos_activos/'); ?>" class="btn">Cancelar</a>
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

