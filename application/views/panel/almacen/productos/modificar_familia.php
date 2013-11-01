<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="es" class="no-js"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title><?php echo $seo['titulo'];?></title>
  <meta name="description" content="<?php echo $seo['titulo'];?>">
  <meta name="viewport" content="width=device-width">

<?php
  if(isset($this->carabiner)){
    $this->carabiner->display('css');
    $this->carabiner->display('base_panel');
    $this->carabiner->display('js');
  }
?>

  <!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
  <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

<script type="text/javascript" charset="UTF-8">
  var base_url = "<?php echo base_url();?>",
      base_url_bascula = "<?php echo $this->config->item('base_url_bascula');?>",
      base_url_cam_salida_snapshot = "<?php echo $this->config->item('base_url_cam_salida_snapshot') ?> ";
</script>
</head>
<body>
  <div id="content" class="container-fluid">
    <div class="row-fluid">
      <!--[if lt IE 7]>
        <div class="alert alert-info">
          <button type="button" class="close" data-dismiss="alert">×</button>
          <p>Usted está usando un navegador desactualizado. <a href="http://browsehappy.com/">Actualice su navegador</a> o <a href="http://www.google.com/chromeframe/?redirect=true">instale Google Chrome Frame</a> para experimentar mejor este sitio.</p>
        </div>
      <![endif]-->
    <div id="content" class="span10">
      <!-- content starts -->

      
      <div class="row-fluid">

        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar Familias</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">

            <form action="<?php echo base_url('panel/productos/modificar_familia/?'.String::getVarsLink(array('msg', 'fstatus'))); ?>" method="post" class="form-horizontal">
              
              <div class="control-group">
                <label class="control-label" for="fempresa">Empresa </label>
                <div class="controls">
                  <input type="text" name="fempresa" value="<?php echo set_value('fempresa', (isset($data['empresa']->nombre_fiscal)? $data['empresa']->nombre_fiscal: '')) ?>" id="fempresa" class="span6" placeholder="Empresa" required autofocus>
                  <input type="hidden" name="fid_empresa" value="<?php echo set_value('fid_empresa', (isset($data['empresa']->id_empresa)? $data['empresa']->id_empresa: '')) ?>" id="fid_empresa" required>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fnombre">Nombre </label>
                <div class="controls">
                  <input type="text" name="fnombre" id="fnombre" class="span6" maxlength="40" 
                  value="<?php echo set_value('fnombre', (isset($data['info']->nombre)? $data['info']->nombre: '') ); ?>" required placeholder="Material empaque, Insumos">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="ftipo">Tipo </label>
                <div class="controls">
                  <select name="ftipo" id="ftipo" required>
                    <option value="p" <?php echo set_select('ftipo', 'p', false, (isset($data['info']->tipo)? $data['info']->tipo: '') ); ?>>Productos</option>
                    <option value="d" <?php echo set_select('ftipo', 'd', false, (isset($data['info']->tipo)? $data['info']->tipo: '') ); ?>>Servicios</option>
                    <option value="f" <?php echo set_select('ftipo', 'f', false, (isset($data['info']->tipo)? $data['info']->tipo: '') ); ?>>Fletes</option>
                  </select>
                </div>
              </div>

              <input type="hidden" name="ffamilia" id="ffamilia" value="<?php echo $this->input->get('id'); ?>">

              <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="javascript:closemodal();void(0);" class="btn">Cancelar</a>
              </div>
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

<script type="text/javascript" charset="UTF-8">
  function closemodal(){
    window.parent.$('#supermodal').modal('hide');

    var pag = parseInt(window.parent.$('#content_familias .pagination li.active a').text())-1;
    window.parent.familias.page(pag);
  }

  <?php if ($closeModal) { ?>
    $(document).ready(function(){
      setTimeout(closemodal, 1000);
    });
  <?php } ?>
</script>

<!-- Bloque de alertas -->


</div><!--/fluid-row-->

</div><!--/.fluid-container-->

  <div class="clear"></div>

</body>
</html>