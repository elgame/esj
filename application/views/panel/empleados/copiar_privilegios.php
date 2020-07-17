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
      <!--[if lt IE 7]>
        <div class="alert alert-info">
          <button type="button" class="close" data-dismiss="alert">×</button>
          <p>Usted está usando un navegador desactualizado. <a href="http://browsehappy.com/">Actualice su navegador</a> o <a href="http://www.google.com/chromeframe/?redirect=true">instale Google Chrome Frame</a> para experimentar mejor este sitio.</p>
        </div>
      <![endif]-->

      <div id="content" class="span12">
        <div class="row-fluid">
          <div class="box span12">
            <div class="box-header well" data-original-title>
              <h2><i class="icon-plus"></i> Copiar privilegios</h2>
              <div class="box-icon">
                <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
              </div>
            </div>
            <div class="box-content">

              <form class="form-horizontal" action="<?php echo base_url('panel/usuarios/copiar_privilegios/?'.MyString::getVarsLink(array('msg', 'rel'))); ?>" method="POST" id="form" enctype="multipart/form-data">
                <input type="hidden" name="empresaId" value="<?php echo $_GET['ide'] ?>">
                <input type="hidden" name="usuarioId" value="<?php echo $_GET['idu'] ?>">

                <div class="row-fluid">  <!-- Box Productos -->
                  <div class="box span12">
                    <div class="box-header well" data-original-title>
                      <h2><i class="icon-barcode"></i> Empresas</h2>
                      <div class="box-icon">
                        <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                      </div>
                    </div><!--/box-header -->
                    <div class="box-content">
                      <div class="row-fluid">
                        <div class="span12 mquit">
                          <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                            <thead>
                              <tr>
                                <th></th>
                                <th>NOMBRE</th>
                              </tr>
                            </thead>
                            <tbody>
                                  <?php
                                       foreach ($empresas['empresas'] as $key => $empresa) {
                                        ?>
                                       <tr>
                                        <td style="width:15px;"><input type="checkbox" id="id_empresass<?php echo $key ?>" class="id_empresass" name="id_empresas[]" value="<?php echo $empresa->id_empresa ?>" data-uniform="false"></td>
                                         <td style="">
                                          <label for="id_empresass<?php echo $key ?>"><?php echo $empresa->nombre_fiscal ?></label>
                                         </td>
                                       </tr>
                                  <?php  } ?>

                            </tbody>
                          </table>
                        </div>
                      </div>
                     </div> <!-- /box-body -->
                  </div> <!-- /box -->

                  <button type="submit" class="btn btn-success">Copiar</button>

                </div><!-- /row-fluid -->

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

        <?php if(isset($reload)) { ?>
          setTimeout(function(){
            <?php
                if (isset($id_movimiento{0}))
                  echo "window.open(base_url+'panel/banco/cheque?id='+{$id_movimiento}, 'Print cheque');";
            ?>

            window.parent.location.reload();
          },1500)
        <?php } ?>

      </script>
      <?php }
      }?>
      <!-- Bloque de alertas -->

    </div><!--/fluid-row-->
  </div><!--/.fluid-container-->

  <div class="clear"></div>
</body>
</html>