    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/almacenes/'); ?>">Almacenes</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar vehiculo</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/almacenes/modificar/?id='.$_GET['id']); ?>" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>
                <?php
                  $data = $data['info'];
                ?>

                <div class="control-group">
                  <label class="control-label" for="nombre">Nombre </label>
                  <div class="controls">
                    <input type="text" name="nombre" id="nombre" class="span6" maxlength="15"
                    value="<?php echo isset($data->nombre)? $data->nombre:''; ?>" required autofocus placeholder="nombre">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dcalle">Calle:</label>
                  <div class="controls">
                    <input type="text" name="dcalle" id="dcalle" class="span12" value="<?php echo (isset($data->calle)? $data->calle: ''); ?>" maxlength="60">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dno_exterior">No exterior:</label>
                  <div class="controls">
                    <input type="text" name="dno_exterior" id="dno_exterior" class="span12" value="<?php echo (isset($data->no_exterior)? $data->no_exterior: ''); ?>" maxlength="7">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dno_interior">No interior:</label>
                  <div class="controls">
                    <input type="text" name="dno_interior" id="dno_interior" class="span12" value="<?php echo (isset($data->no_interior)? $data->no_interior: ''); ?>" maxlength="7">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dpais">País:</label>
                  <div class="controls">
                    <input type="text" name="dpais" id="dpais" class="span12" value="<?php echo (isset($data->pais)? $data->pais: ''); ?>" maxlength="60">
                    <span class="dpais help-block nomarg" style="color:#bd362f"></span>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="destado">Estado:</label>
                  <div class="controls">
                    <input type="text" name="destado" id="destado" class="span12" value="<?php echo (isset($data->estado)? $data->estado: ''); ?>" maxlength="45">
                    <span class="destado help-block nomarg" style="color:#bd362f"></span>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dmunicipio">Municipio / Delegación:</label>
                  <div class="controls">
                    <input type="text" name="dmunicipio" id="dmunicipio" class="span12" value="<?php echo (isset($data->municipio)? $data->municipio: ''); ?>" maxlength="45">
                    <span class="dmunicipio help-block nomarg" style="color:#bd362f"></span>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dlocalidad">Localidad:</label>
                  <div class="controls">
                    <input type="text" name="dlocalidad" id="dlocalidad" class="span12" value="<?php echo (isset($data->localidad)? $data->localidad: ''); ?>" maxlength="45">
                    <span class="dlocalidad help-block nomarg" style="color:#bd362f"></span>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dcp">CP:</label>
                  <div class="controls">
                    <input type="text" name="dcp" id="dcp" class="span12" value="<?php echo (isset($data->cp)? $data->cp: ''); ?>" maxlength="10">
                    <span class="dcp help-block nomarg" style="color:#bd362f"></span>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dcolonia">Colonia:</label>
                  <div class="controls">
                    <input type="text" name="dcolonia" id="dcolonia" class="span12" value="<?php echo (isset($data->colonia)? $data->colonia: ''); ?>" maxlength="60">
                    <span class="dcolonia help-block nomarg" style="color:#bd362f"></span>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dtelefono">Teléfono:</label>
                  <div class="controls">
                    <input type="text" name="dtelefono" id="dtelefono" class="span12" value="<?php echo (isset($data->telefono)? $data->telefono: ''); ?>" maxlength="15">
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/almacenes/'); ?>" class="btn">Cancelar</a>
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


