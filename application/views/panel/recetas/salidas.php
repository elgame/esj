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
          <h2><i class="icon-plus"></i> Datos de receta</h2>
          <div class="box-icon">
            <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
          </div>
        </div>
        <div class="box-content">

            <div class="row-fluid">
              <div class="span6">

                <div class="control-group">
                  <label class="control-label" for="empresa">Empresa </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo $receta['info']->empresa ?>" data-next="tipo" readonly><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                    </div>
                    <input type="hidden" name="empresaId" id="empresaId" value="<?php echo $receta['info']->id_empresa ?>">
                  </div>
                </div><!--/control-group -->

                <div class="control-group">
                  <label class="control-label" for="formula">Formula </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="formula" class="span11" id="formula" value="<?php echo $receta['info']->formula ?>" placeholder="Selecciona una formula" readonly required>
                      <input type="hidden" name="formulaId" id="formulaId" value="<?php echo $receta['info']->id_formula ?>">
                    </div>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="area">Cultivo</label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="area" class="span11" id="area" value="<?php echo $receta['info']->area ?>" placeholder="Limon, Piña" readonly>
                    </div>
                    <input type="hidden" name="areaId" id="areaId" value="<?php echo $receta['info']->id_area ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fecha">Fecha</label>
                  <div class="controls">
                    <input type="date" name="fecha" class="span11" id="fecha" value="<?php echo $receta['info']->fecha ?>" size="25" readonly>
                  </div>
                </div>

              </div>

              <div class="span6">

                <div class="control-group">
                  <label class="control-label" for="tipo">Tipo de Orden</label>
                  <div class="controls">
                    <select name="tipo" class="span9" id="tipo" data-next="formula" disabled>
                      <option value="kg" <?php echo set_select('tipo', 'kg', ($receta['info']->tipo==='kg')); ?>>Kg</option>
                      <option value="lts" <?php echo set_select('tipo', 'lts', ($receta['info']->tipo==='lts')); ?>>Lts</option>
                    </select>
                    <input type="hidden" name="tipo" value="<?php echo $receta['info']->tipo ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="folio_formula">Folio Formula</label>
                  <div class="controls">
                    <input type="text" name="folio_formula" class="span9" id="folio_formula" value="<?php echo $receta['info']->folio_formula ?>" size="25" readonly required>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="folio">Folio Receta</label>
                  <div class="controls">
                    <input type="text" name="folio" class="span9" id="folio" value="<?php echo $receta['info']->folio ?>" size="25" readonly>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fecha_aplicacion">Fecha Aplicación</label>
                  <div class="controls">
                    <input type="date" name="fecha_aplicacion" class="span11" id="fecha_aplicacion" value="<?php echo $receta['info']->fecha_aplicacion ?>" size="25" readonly>
                  </div>
                </div>

              </div>
            </div>

            <div class="row-fluid" style="<?php echo ($receta['info']->paso==='t'? '': 'display:none') ?>">
              <div class="box span12">
                <div class="box-header well" data-original-title>
                  <h2><i class="icon-list-alt"></i> Datos de Aplicación Realizada</h2>
                  <div class="box-icon">
                    <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                  </div>
                </div><!--/box-header -->
                <div class="box-content">
                  <div class="row-fluid">
                    <div class="span3">
                      Semana
                      <input type="text" name="ar_semana" class="span12 datosapl" id="ar_semana" value="<?php echo $receta['info']->ar_semana ?>">
                    </div>

                    <div class="span3">
                      Fecha
                      <input type="datetime-local" name="ar_fecha" class="span12 datosapl" id="ar_fecha" value="<?php echo $receta['info']->ar_fecha ?>">
                    </div>

                    <div class="span3">
                      PH
                      <input type="text" name="ar_ph" class="span12 datosapl" id="ar_ph" value="<?php echo $receta['info']->ar_ph ?>">
                    </div>
                  </div>

                  <div class="row-fluid">
                    <button type="button" id="saveAjaxExtras" class="btn btn-success" data-idReceta="<?php echo $receta['info']->id_recetas ?>">Guardar</button>
                  </div>

                </div> <!-- /box-body -->
              </div> <!-- /box -->
            </div><!-- /row-fluid -->

            <div class="row-fluid" id="salidas">  <!-- Box Productos -->
              <div class="box span12">
                <div class="box-header well" data-original-title>
                  <h2><i class="icon-barcode"></i> Salidas de productos</h2>
                  <div class="box-icon">
                    <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                  </div>
                </div><!--/box-header -->
                <div class="box-content">

                  <div class="row-fluid">
                    <div class="span12 mquit">
                      <table class="table table-striped table-bordered table-hover table-condensed" id="table-salidas">
                        <thead>
                          <tr>
                            <th rowspan="2" style="vertical-align: middle;">F Creacion</th>
                            <th rowspan="2" style="vertical-align: middle;">F Registro</th>
                            <th rowspan="2" style="vertical-align: middle;">Folio</th>
                            <th rowspan="2" style="vertical-align: middle;">Concepto</th>
                            <th rowspan="2" style="vertical-align: middle;"><?php echo ($receta['info']->tipo == 'lts'? 'Cargas': 'Plantas') ?></th>
                            <th rowspan="2" style="vertical-align: middle;">Solicito</th>
                            <th rowspan="2" style="vertical-align: middle;">Recibió</th>
                            <th rowspan="2" style="vertical-align: middle;">OPC</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (isset($salidas)) {
                          foreach ($salidas as $key => $sal) { ?>

                            <tr class="rowprod">
                              <td>
                                <?php echo $sal->fecha_creacion ?>
                              </td>
                              <td>
                                <?php echo $sal->fecha_registro ?>
                              </td>
                              <td>
                                <?php echo $sal->folio ?>
                              </td>
                              <td>
                                <?php echo $sal->concepto ?>
                              </td>
                              <td>
                                <?php echo $sal->cargas ?>
                              </td>
                              <td>
                                <?php echo $sal->solicito ?>
                              </td>
                              <td>
                                <?php echo $sal->recibio ?>
                              </td>
                              <td style="width: 50px;">
                                <a class="btn btn-info" href="<?php echo base_url("panel/recetas/imprimir_salida/?id={$sal->id_salida}&id_receta={$sal->id_recetas}"); ?>" target="_BLANK" title="Imprimir">
                                  <i class="icon-print icon-white"></i> <span class="hidden-tablet">Ticket</span></a>
                              </td>
                            </tr>
                          <?php }} ?>
                        </tbody>
                      </table>
                    </div>
                  </div>

                 </div> <!-- /box-body -->
              </div> <!-- /box -->
            </div><!-- /row-fluid -->



        </div><!--/span-->
      </div><!--/row-->

    </div><!--/fluid-row-->
  </div><!--/.fluid-container-->

  <div class="clear"></div>
</body>
</html>