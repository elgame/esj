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
      <div class="box span12">
        <div class="box-header well" data-original-title>
          <h2><i class="icon-eye-open"></i> Ver Gasto</h2>
          <div class="box-icon">
            <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
          </div>
        </div>
        <div class="box-content">
          <form class="form-horizontal" action="<?php echo base_url('panel/gastos/ver/?'.String::getVarsLink(array('msg'))); ?>" method="POST" id="form" enctype="multipart/form-data">

              <div class="row-fluid">
                <div class="span6">

                  <div class="control-group">
                    <label class="control-label" for="empresa">Empresa</label>
                    <div class="controls">
                      <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $empresa['info']->nombre_fiscal); ?>" readonly>
                      <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $empresa['info']->id_empresa); ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dserie">Proveedor</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="proveedor" class="span10" id="proveedor" value="<?php echo set_value('proveedor', $proveedor['info']->nombre_fiscal) ?>" placeholder="" readonly><a href="<?php echo base_url('panel/proveedores/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                      </div>
                    </div>
                      <input type="hidden" name="proveedorId" id="proveedorId" value="<?php echo set_value('proveedorId', $proveedor['info']->id_proveedor) ?>">
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="tipo_documento">Tipo de Documento</label>
                    <div class="controls">
                      <select name="tipo_documento" class="span8" style="float: left;" readonly>
                        <option value="fa" <?php echo set_select('tipo_documento', 'fa', false, $gasto['info']->tipo_documento) ?>>FACTURA</option>
                        <option value="nv" <?php echo set_select('tipo_documento', 'nv', false, $gasto['info']->tipo_documento) ?>>NOTA DE VENTA</option>
                      </select>
                      <!-- <label for="es_vehiculo" class="span3" style="text-align: right;">Vehiculo
                        <input type="checkbox" name="es_vehiculo" id="es_vehiculo" data-uniform="false" value="si" readonly <?php echo ($gasto['info']->id_vehiculo != '' ? 'checked' : ''); ?>></label> -->
                    </div>
                  </div>

                  <!-- <div class="control-group" id="groupVehiculo" style="display: <?php echo ($gasto['info']->id_vehiculo != '' ? 'block' : 'none'); ?>;">
                    <label class="control-label" for="vehiculo">Vehiculos</label>
                    <div class="controls">
                      <input type="text" name="vehiculo" class="span7 sikey" id="vehiculo" value="<?php echo (isset($gasto['vehiculo']->nombre)? $gasto['vehiculo']->nombre: ''); ?>" placeholder="Vehiculos" data-next="tipo_vehiculo" readonly style="float: left;">

                      <select name="tipo_vehiculo" id="tipo_vehiculo" class="span4 sikey" style="float: right;" data-next="serie" readonly>
                        <option value="ot" <?php echo set_select('tipo_vehiculo', 'ot', false, $gasto['info']->tipo_vehiculo) ?>>OTRO</option>
                        <option value="g" <?php echo set_select('tipo_vehiculo', 'g', false, $gasto['info']->tipo_vehiculo) ?>>GASOLINA</option>
                      </select>
                    </div>
                      <input type="hidden" name="vehiculoId" id="vehiculoId" value="<?php echo $gasto['info']->id_vehiculo; ?>">
                  </div> -->

                  <div class="control-group">
                    <label class="control-label" for="serie">Serie</label>
                    <div class="controls">
                      <input type="text" name="serie" class="span12" id="serie" value="<?php echo set_value('serie', $gasto['info']->serie); ?>" readonly>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="folio">Folio</label>
                    <div class="controls">
                      <input type="text" name="folio" class="span12" id="folio" value="<?php echo set_value('folio', $gasto['info']->folio); ?>" readonly>
                    </div>
                  </div>

                </div><!--/span6 -->

                <div class="span6">

                  <div class="control-group">
                    <label class="control-label" for="fecha">Fecha</label>
                    <div class="controls">
                      <input type="datetime-local" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', str_replace(' ', 'T', substr($gasto['info']->fecha, 0, 16))); ?>" readonly>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="condicionPago">Condición de Pago</label>
                    <div class="controls">
                      <select name="condicionPago" class="span9" id="condicionPago" data-next="plazoCredito|dcuenta" readonly>
                        <option value="co" <?php echo set_select('condicionPago', 'co', $gasto['info']->condicion_pago); ?>>Contado</option>
                        <option value="cr" <?php echo set_select('condicionPago', 'cr', $gasto['info']->condicion_pago); ?>>Credito</option>
                      </select>
                    </div>
                  </div>

                  <div class="control-group" id="grup_plazo_credito" style="display: <?php echo isset($_POST['condicionPago']) ? ($_POST['condicionPago'] === 'co' ? 'none' : 'block') : 'none' ?>;">
                    <label class="control-label" for="plazoCredito">Plazo de Crédito</label>
                    <div class="controls">
                      <input type="text" name="plazoCredito" class="span9" id="plazoCredito" value="<?php echo set_value('plazoCredito', $gasto['info']->plazo_credito); ?>" readonly>
                    </div>
                  </div>

                  <?php //if ( ! $gasto['info']->xml){ ?>
                    <div class="control-group">
                      <label class="control-label" for="xml">XML</label>
                      <div class="controls">
                        <input type="file" name="xml" class="span9" id="xml" data-uniform="false" accept="text/xml">
                      </div>
                    </div>
                  <?php //} ?>

                  <div class="control-group">
                    <label class="control-label" for="concepto">Concepto</label>
                    <div class="controls">
                      <textarea name="concepto" class="span12" id="concepto" maxlength="200" readonly><?php echo set_value('concepto', $gasto['info']->concepto); ?></textarea>
                    </div>
                  </div>

                </div><!--/span6 -->
              </div><!--/row-fluid -->

              <!-- <div class="row-fluid" id="group_gasolina" style="display: <?php echo ($gasto['info']->tipo_vehiculo === 'ot' ? 'none' : 'block') ?>;">
                <div class="span4">
                  <div class="control-group">
                    <div class="controls span9">
                      Kilometros <input type="text" name="dkilometros" class="span12" id="dkilometros" value="<?php echo (isset($gasto['gasolina']->kilometros)? $gasto['gasolina']->kilometros: ''); ?>" maxlength="10" readonly>
                    </div>
                  </div>
                </div>
                <div class="span4">
                  <div class="control-group">
                    <div class="controls span9">
                      Litros <input type="text" name="dlitros" class="span12" id="dlitros" value="<?php echo (isset($gasto['gasolina']->litros)? $gasto['gasolina']->litros: ''); ?>" maxlength="10" readonly>
                    </div>
                  </div>
                </div>
                <div class="span4">
                  <div class="control-group">
                    <div class="controls span9">
                      Precio <input type="text" name="dprecio" class="span12" id="dprecio" value="<?php echo (isset($gasto['gasolina']->precio)? $gasto['gasolina']->precio: ''); ?>" maxlength="10" readonly>
                    </div>
                  </div>
                </div>
              </div> -->

            <div class="row-fluid">
              <div class="span4">
                <div class="control-group">
                  <div class="controls span9">
                    Subtotal <input type="text" name="subtotal" class="span12 vpositive" id="subtotal" value="<?php echo set_value('subtotal', $gasto['info']->subtotal); ?>">
                  </div>
                </div>
              </div>
              <div class="span4">
                <div class="control-group">
                  <div class="controls span9">
                    IVA <input type="text" name="iva" class="span12 vpositive" id="iva" value="<?php echo set_value('iva', $gasto['info']->importe_iva); ?>">
                  </div>
                </div>
              </div>
              <div class="span4">
                <div class="control-group">
                  <div class="controls span9">
                    Ret. IVA <input type="text" name="ret_iva" class="span12 vpositive" id="ret_iva" value="<?php echo set_value('ret_iva', $gasto['info']->retencion_iva); ?>">
                  </div>
                </div>
              </div>
            </div><!--/row-fluid -->
            <div class="row-fluid">
              <div class="span4">
                <div class="control-group">
                  <div class="controls span9">
                    Ret. ISR <input type="text" name="ret_isr" class="span12 vpositive" id="ret_isr" value="<?php echo set_value('ret_isr', $gasto['info']->retencion_isr); ?>">
                  </div>
                </div>
              </div>
              <div class="span4">
                <div class="control-group">
                  <div class="controls span9">
                    TOTAL <input type="text" name="total" class="span12 vpositive" id="total" value="<?php echo set_value('total', $gasto['info']->total); ?>">
                  </div>
                </div>
              </div>
            </div><!--/row-fluid -->

            <?php //if ( ! $gasto['info']->xml) { ?>
              <div class="span4 pull-right">
                <div class="control-group">
                  <div class="controls span9">
                    <div class="well span12">
                        <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
                    </div>
                  </div>
                </div>
              </div>
            <?php //} ?>

          </form>
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