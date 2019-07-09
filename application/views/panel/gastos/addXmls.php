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
    <div class="row-fluid">
      <div class="box span12">
        <div class="box-header well" data-original-title>
          <h2><i class="icon-eye-open"></i> Buscar CFDIs</h2>
          <div class="box-icon">
            <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
          </div>
        </div>
        <div class="box-content">
          <form action="<?php echo base_url('panel/gastos/verXml/?'.MyString::getVarsLink(array('msg'))); ?>" method="get"  class="form-search">
            <input type="hidden" name="idp" value="<?php echo $_GET['idp'] ?>">
            <input type="hidden" name="id" value="<?php echo (!empty($_GET['id'])? $_GET['id']: '') ?>">
            <input type="hidden" name="ide" value="<?php echo (!empty($_GET['ide'])? $_GET['ide']: $ide) ?>">

            <label for="rfc">RFC</label>
            <input type="text" name="rfc" id="rfc" value="<?php echo set_value_get('rfc', $rfc); ?>" class="search-query" autofocus>

            <label for="ffolio">Folio</label>
            <input type="text" name="ffolio" id="ffolio" value="<?php echo set_value_get('ffolio'); ?>" class="search-query">

            <br> <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
            <input type="date" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">
            <label for="ffecha2">Al</label>
            <input type="date" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2'); ?>" size="10">

             | <button type="submit">Enviar</button>
          </form>

          <table class="table table-striped table-bordered bootstrap-datatable">
            <thead>
              <tr>
                <th>RFC</th>
                <th>FECHA</th>
                <th>FOLIO</th>
                <th>TOTAL</th>
                <th>UUID</th>
              </tr>
            </thead>
            <tbody>
              <?php if (isset($files) && count($files) > 0): ?>
                <?php foreach ($files as $key => $file): ?>
                <tr class="itemXml" style="cursor: pointer;"
                  data-uuid="<?php echo $file['uuid'] ?>"
                  data-noCertificado="<?php echo $file['noCertificado'] ?>">
                  <td><?php echo $file['rfc'] ?></td>
                  <td><?php echo $file['fecha'] ?></td>
                  <td><?php echo $file['folio'] ?></td>
                  <td><?php echo number_format($file['total'], 2, '.', ',') ?></td>
                  <td><?php echo $file['uuid'] ?></td>
                </tr>
                <?php endforeach ?>
              <?php endif ?>
            </tbody>
          </table>
        </div>
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


    </div><!--/fluid-row-->
  </div><!--/.fluid-container-->

  <div class="clear"></div>
</body>
</html>