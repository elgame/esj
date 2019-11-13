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
    var base_url = "<?php echo base_url();?>";
  </script>
</head>
  <body>

    <div class="modal-header">
      <h3><?php echo $empresa->nombre_fiscal ?> | Semana: <?php echo $semana['semana'] ?> | Año: <?php echo $semana['anio'] ?>
      <button type="button" class="btn btn-info" title="Recargar" id="btn-refresh" style="float: right;"><i class="icon-refresh"></i></button></h3>
    </div><!--/modal-header -->

    <div class="modal-body" style="max-height: none;">
      <form class="form-horizontal" action="<?php echo base_url('panel/nomina_fiscal/show_import_asistencias/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" enctype="multipart/form-data">
        <div>
          <label>Seleccionar archivo: <input type="file" name="archivo_asistencias" accept=".csv" placeholder=""></label>
          <input type="hidden" name="id_empresa" value="<?php echo $empresa->id_empresa ?>">
          <input type="hidden" name="semana" value="<?php echo $semana['semana'] ?>">
          <input type="hidden" name="anio" value="<?php echo $semana['anio'] ?>">
        </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success" id="btn-impirtar">Guardar</button>
          </div><!--/modal-footer -->
      </form><!--/form-horizontal -->
    </div><!--/modal-body -->

    </div>

<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){

    <?php if (isset($close)) {?>
        setInterval(function() {
          window.parent.$('#supermodal').modal('hide');
          window.parent.location.reload();
      }, 2000);
    <?php }?>

    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->

  </body>
</html>