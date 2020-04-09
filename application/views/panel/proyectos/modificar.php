    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/proyectos/'); ?>">Proyectos</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar proyecto</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/proyectos/modificar/?id='.$_GET['id']); ?>" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>

                <?php $data = $proyecto['info']; ?>

                <div class="span12">

                  <div class="control-group">
                    <label class="control-label" for="fempresa">Empresa </label>
                    <div class="controls">
                      <input type="text" name="fempresa" id="fempresa" class="span10" maxlength="90" autofocus
                        value="<?php echo isset($data->empresa->nombre_fiscal)? $data->empresa->nombre_fiscal:''; ?>" required>
                      <input type="hidden" name="did_empresa" id="did_empresa"
                        value="<?php echo isset($data->empresa->id_empresa)? $data->empresa->id_empresa:''; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="nombre">Nombre </label>
                    <div class="controls">
                      <input type="text" name="nombre" id="nombre" class="span10" maxlength="90"
                      value="<?php echo isset($data->nombre)? $data->nombre:''; ?>" required placeholder="Nombre proyecto">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="presupuesto">Presupuesto </label>
                    <div class="controls">
                      <input type="number" name="presupuesto" id="presupuesto" class="span10"
                        value="<?php echo isset($data->presupuesto)? $data->presupuesto:''; ?>" placeholder="150000, 50000" required>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fecha_inicio">Fecha de inicio </label>
                    <div class="controls">
                      <input type="date" name="fecha_inicio" id="fecha_inicio" class="span10"
                        value="<?php echo isset($data->fecha_inicio)? $data->fecha_inicio:''; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fecha_terminacion">Fecha de terminación </label>
                    <div class="controls">
                      <input type="date" name="fecha_terminacion" id="fecha_terminacion" class="span10"
                        value="<?php echo isset($data->fecha_terminacion)? $data->fecha_terminacion:''; ?>">
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/proyectos/'); ?>" class="btn">Cancelar</a>
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


