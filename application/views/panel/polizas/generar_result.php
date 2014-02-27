<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="es" class="no-js"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title></title>
  <meta name="description" content="">
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
      <!-- content starts -->

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <div class="row-fluid">
              <form action="<?php echo base_url('panel/polizas/genera_poliza?'.String::getVarsLink(array('poliza_nombre'))); ?>" method="post" class="form-horizontal">
                <button class="btn btn-success btn-large pull-right">Guardar</button>

                <textarea id="poliza" name="poliza" class="span12" rows="20" readonly autofocus><?php echo isset($poliza['data'])? $poliza['data']: ''; ?></textarea>
              </form>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->

      <script type="text/javascript">
      <?php 
      if (isset($_GET['poliza_nombre'])) {
      ?>  
          window.parent.newPoliza();
          window.location = '<?php echo base_url("panel/polizas/descargar_poliza/?poliza_nombre={$_GET['poliza_nombre']}"); ?>';
      <?php 
      };
      ?>
       
      </script>


          <!-- content ends -->
    </div><!--/#content.span10-->

</body>
</html>