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
      <form class="form-horizontal" action="<?php echo base_url('panel/bascula/facturas_ver/?'.MyString::getVarsLink(array('msg', 'rel'))); ?>" method="POST" enctype="multipart/form-data">
        <div id="content" class="span12">
          <div class="row-fluid">
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-plus"></i> Factura</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div>
              <div class="box-content">

                <div class="row-fluid center">
                  <div class="span12"><h2><?php echo $proveedor['info']->nombre_fiscal ?></h2></div>
                </div>

                  <div class="row-fluid">
                    <div class="span4">
                      <div class="control-group">
                        <div class="controls span9">
                          Serie <input type="text" name="serie" class="span12" id="serie" value="<?php echo set_value('serie', $compra['info']->serie); ?>">
                        </div>
                      </div>
                    </div>
                    <div class="span4">
                      <div class="control-group">
                        <div class="controls span9">
                          Folio<input type="text" name="folio" class="span12" id="folio" value="<?php echo set_value('folio', $compra['info']->folio); ?>">
                        </div>
                      </div>
                    </div>
                    <div class="span4">
                      <div class="control-group">
                        <div class="controls span9">
                          Fecha<input type="datetime-local" name="fecha" class="span12" id="fecha" value="<?php echo set_value('fecha', str_replace(' ', 'T', substr($compra['info']->fecha, 0, 16))); ?>">
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="row-fluid">
                    <?php //if ( ! $compra['info']->xml){ ?>
                      <div class="span4">
                        <div class="control-group">
                          <div class="controls span9">
                            XML<input type="file" name="xml" class="span12" id="xml" data-uniform="false" accept="text/xml">
                            <input type="hidden" name="aux" value="1">
                            <button type="submit" class="btn btn-success btn-large btn-block pull-right" style="width:100%;">Guardar</button>
                          </div>
                        </div>
                      </div>
                    <?php //} ?>
                  </div>

                  <div class="row-fluid">  <!-- Box Productos -->
                    <div class="box span12">
                      <div class="box-header well" data-original-title>
                        <h2><i class="icon-barcode"></i> Boletas</h2>
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
                                  <th>FECHA</th>
                                  <th>FOLIO</th>
                                  <th>IMPORTE</th>
                                  <th></th>
                                </tr>
                              </thead>
                              <tbody>
                                    <?php
                                    $total = $subtotal = $iva = 0;
                                         foreach ($compra['boletas'] as $key => $prod) {
                                          $total += $prod->importe;
                                          $subtotal += $prod->importe;
                                          ?>
                                         <tr>
                                           <td style="">
                                              <?php echo $prod->fecha_bruto ?>
                                              <input type="hidden" name="pid_bascula[]" value="<?php echo $prod->id_bascula ?>" id="pid_bascula" class="span12">
                                           </td>
                                           <td style="">
                                              <?php echo $prod->folio ?>
                                           </td>
                                           <td class="ppimporte" data-importe="<?php echo $prod->importe ?>">
                                               <?php echo MyString::formatoNumero($prod->importe); ?>
                                           </td>
                                           <td style="">
                                            <button class="btn btn-danger removeBoleta"><i class="icon-remove"></i></button>
                                           </td>
                                         </tr>
                                    <?php  } ?>

                              </tbody>
                            </table>
                          </div>
                        </div>
                       </div> <!-- /box-body -->
                    </div> <!-- /box -->
                  </div><!-- /row-fluid -->

                  <div class="row-fluid">
                    <div class="span12">
                      <table class="table">
                        <thead>
                          <tr>
                            <th style="background-color:#FFF !important;">TOTALES</th>
                            <th style="background-color:#FFF !important;"></th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td><em>Subtotal</em></td>
                            <td id="importe-format">
                              <input type="text" name="totalImporte" id="totalImporte" value="<?php echo MyString::formatoNumero(set_value('totalImporte', $subtotal), 2, '$', false)?>">
                            </td>
                          </tr>
                          <tr>
                            <td>IVA</td>
                            <td id="traslado-format">
                              <input type="text" name="totalImpuestosTrasladados" id="totalImpuestosTrasladados" value="<?php echo MyString::formatoNumero(set_value('totalImpuestosTrasladados', $iva), 2, '$', false)?>">
                            </td>
                          </tr>
                          <tr style="font-weight:bold;font-size:1.2em;">
                            <td>TOTAL</td>
                            <td id="total-format">
                              <input type="text" name="totalOrden" id="totalOrden" value="<?php echo MyString::formatoNumero(set_value('totalOrden', $total), 2, '$', false)?>">
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
              </div><!--/span-->
            </div><!--/row-->
          </div><!--/row-->

        </div>
      </form>
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