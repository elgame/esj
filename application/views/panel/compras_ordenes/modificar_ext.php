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
              <h2><i class="icon-plus"></i> Modificar Datos de Orden</h2>
              <div class="box-icon">
                <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
              </div>
            </div>
            <div class="box-content">

              <div class="row-fluid center">
                <input type="hidden" name="folio" value="<?php echo $orden['info'][0]->folio ?>">
                <input type="hidden" name="empresaId" value="<?php echo $orden['info'][0]->id_empresa ?>">

                <div class="span12"><h2><?php echo $orden['info'][0]->proveedor ?></h2></div>

                <div class="span4"><strong>Folio: <?php echo $orden['info'][0]->folio ?></strong></div>
                <div class="span4"><strong>Fecha: <?php echo MyString::fechaATexto($orden['info'][0]->fecha, '/c') ?></strong></div>
              </div>

              <form class="form-horizontal" action="<?php echo base_url('panel/compras_ordenes/modificar_ext/?'.MyString::getVarsLink(array('msg', 'rel'))); ?>" method="POST" id="form" enctype="multipart/form-data">

                <!-- Box Vehiculos -->
                <div class="row-fluid">
                  <div class="box span12">
                    <div class="box-header well" data-original-title>
                      <h2><i class="icon-truck"></i> Vehículos
                        <input type="checkbox" name="es_vehiculo" id="es_vehiculo" data-uniform="false" class="" value="si" data-next="fecha" <?php echo set_checkbox('es_vehiculo', 'si', $orden['info'][0]->id_vehiculo > 0 ? true : false); ?>></label>
                      </h2>
                      <div class="box-icon">
                        <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                      </div>
                    </div><!--/box-header -->
                    <div class="box-content" id="groupVehiculo">
                      <div class="row-fluid">

                        <div class="span12 mquit">
                          <div class="control-group">
                            <label class="control-label" for="vehiculo">Vehiculos</label>
                            <div class="controls">
                              <input type="text" name="vehiculo" class="span7 sikey" id="vehiculo" value="<?php echo set_value('vehiculo', ($orden['info'][0]->id_vehiculo) ? $orden['info'][0]->placa.' '.$orden['info'][0]->modelo.' '.$orden['info'][0]->marca : '') ?>" placeholder="Vehiculos" data-next="tipo_vehiculo" style="float: left;">

                              <select name="tipo_vehiculo" id="tipo_vehiculo" class="span4 sikey" style="float: right;" data-next="dkilometros">
                                <option value="ot" <?php echo set_select('tipo_vehiculo', 'ot', $orden['info'][0]->tipo_vehiculo === 'ot' ? true : false) ?>>REFACCIONES Y OTROS</option>
                                <option value="g" <?php echo set_select('tipo_vehiculo', 'g', $orden['info'][0]->tipo_vehiculo === 'g' ? true : false) ?>>GASOLINA</option>
                                <option value="d" <?php echo set_select('tipo_vehiculo', 'd', $orden['info'][0]->tipo_vehiculo === 'd' ? true : false) ?>>DIESEL</option>
                              </select>
                            </div>
                              <input type="hidden" name="vehiculoId" id="vehiculoId" value="<?php echo set_value('vehiculoId', $orden['info'][0]->id_vehiculo) ?>">
                          </div>
                        </div>
                      </div><!--/row-fluid -->

                      <div class="row-fluid" id="group_gasolina" style="display: <?php echo isset($_POST['tipo_vehiculo']) ? ($_POST['tipo_vehiculo'] === 'ot' ? 'none' : 'block') : ($orden['info'][0]->tipo_vehiculo === 'ot' ? 'none' : 'block') ?>;">
                        <div class="span4">
                          <div class="control-group">
                            <div class="controls span9">
                              Kilometros <input type="text" name="dkilometros" class="span12 sikey vpos-int" id="dkilometros" value="<?php echo set_value('dkilometros', isset($orden['info'][0]->gasolina[0]->kilometros) ? $orden['info'][0]->gasolina[0]->kilometros : ''); ?>" maxlength="10" data-next="dlitros">
                            </div>
                          </div>
                        </div>
                        <div class="span4">
                          <div class="control-group">
                            <div class="controls span9">
                              Litros <input type="text" name="dlitros" class="span12 sikey vpositive" id="dlitros" value="<?php echo set_value('dlitros', isset($orden['info'][0]->gasolina[0]->litros) ? $orden['info'][0]->gasolina[0]->litros : ''); ?>" maxlength="10" data-next="dprecio">
                            </div>
                          </div>
                        </div>
                        <div class="span4">
                          <div class="control-group">
                            <div class="controls span9">
                              Precio <input type="text" name="dprecio" class="span12 sikey vpositive" id="dprecio" value="<?php echo set_value('dprecio', isset($orden['info'][0]->gasolina[0]->precio) ? $orden['info'][0]->gasolina[0]->precio : ''); ?>" maxlength="10" data-next="fconcepto">
                            </div>
                          </div>
                        </div>
                      </div>

                     </div> <!-- /box-body -->

                  </div> <!-- /box -->


                  <button type="submit" class="btn btn-success btn-large btn-block">Modificar</button>

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