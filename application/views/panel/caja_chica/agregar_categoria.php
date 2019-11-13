<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/caja_chica/categorias/'); ?>">Categorias</a> <span class="divider">/</span>
      </li>
      <li>Agregar</li>
    </ul>
  </div>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Agregar categoria</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/caja_chica/categorias_agregar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="nombre">Nombre </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="nombre" class="span11" id="nombre" value="<?php echo set_value('nombre') ?>" maxlength="60" autofocus required>
                  </div>
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="abreviatura">Abreviatura </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="abreviatura" class="span11" id="abreviatura" value="<?php echo set_value('abreviatura') ?>" maxlength="20" required>
                  </div>
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="pempresa">Empresa</label>
                <div class="controls">
                  <input type="text" name="pempresa" value="<?php echo set_value('pempresa', (isset($_POST['pempresa']) ? $_POST['pempresa'] : $empresa_default->nombre_fiscal)) ?>" id="pempresa" class="span11 next" placeholder="Empresa">
                  <input type="hidden" name="pid_empresa" value="<?php echo set_value('pid_empresa', (isset($_POST['pid_empresa']) ? $_POST['pid_empresa'] : $empresa_default->id_empresa)) ?>" id="pid_empresa">
                </div>
              </div>
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