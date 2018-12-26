<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/labores_codigo/'); ?>">Categorias</a> <span class="divider">/</span>
      </li>
      <li>Moficiar</li>
    </ul>
  </div>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-edit"></i> Modificar labor</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/labores_codigo/modificar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="nombre">Nombre </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="nombre" class="span11" id="nombre" value="<?php echo set_value('nombre', $categoria['info'][0]->nombre) ?>" maxlength="60" autofocus required>
                  </div>
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="codigo">Codigo </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="codigo" class="span11" id="codigo" value="<?php echo set_value('codigo', $categoria['info'][0]->codigo) ?>" maxlength="20" required>
                  </div>
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="costo">Costo x unidad </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="costo" class="span11" id="costo" value="<?php echo set_value('costo', $categoria['info'][0]->costo) ?>" maxlength="20" required>
                  </div>
                </div>
              </div><!--/control-group -->

            </div>

            <div class="span6">

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                    <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->

</div>

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