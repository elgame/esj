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

    <form class="form-horizontal" action="<?php echo base_url('panel/facturacion/email/?id='.$_GET['id']); ?>" method="POST">

      <div class="modal-header">
        <h3>Enviar Email</h3>
      </div><!--/modal-header -->

      <div class="modal-body">

        <div class="control-group">
          <label class="control-label" for="pextras">Otros Emails</label>
          <div class="controls">
            <input type="text" name="pextras" id="pextras" class="span6" placeholder="email1@gmail.com, email2@hotmail.com">
          </div>
        </div>

         <div class="control-group">
          <label class="control-label">Emails Default</label>
          <div class="controls">
            <?php if (count($emails_default) > 0){ ?>
              <ul class="unstyled">
                <li><input type="checkbox" id="check-emails" checked>Todos</li>
                <?php foreach ($emails_default as $key => $email) { ?>
                  <li style="margin-left: 15px;"><input type="checkbox" name="emails[]" class="email-default" value="<?php echo trim($email) ?>" checked> <?php echo trim($email) ?></li>
                <?php } ?>
              </ul>
            <?php } else { ?>
              <div style="font-size: 1.1em;color:red;">El cliente no cuenta con emails.</div>
            <?php } ?>
          </div>
        </div>

        <div class="control-group">
          <label class="control-label" for="dcomentario">Comentario</label>
          <div class="controls">
            <textarea id="dcomentario" name="dcomentario" class="span6"></textarea>
          </div>
        </div>

      </div><!--/modal-body -->

      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Enviar</button>
      </div><!--/modal-footer -->

    </form><!--/form-horizontal -->

<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){

    <?php if (isset($close)) {?>
        setInterval(function() {
          window.parent.$('#supermodal').modal('hide');
      }, 1000);
    <?php }?>

    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->

  </body>
</html>