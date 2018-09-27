<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/productos_regreso/'); ?>">Salidas de Productos</a> <span class="divider">/</span>
      </li>
      <li>Agregar</li>
    </ul>
  </div>


  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Ver</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/productos_regreso/ver/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $orden['info'][0]->empresa) ?>" placeholder="" readonly>
                  </div>
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="solicito">Regreso</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="solicito" class="span11" id="solicito" value="<?php echo set_value('solicito', $orden['info'][0]->empleado_solicito) ?>" placeholder="" readonly>
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="id_almacen">Almacen</label>
                <div class="controls">
                  <div class="input-append span12">
                    <select name="id_almacen" class="span11" readonly>
                    <?php $default = ($orden['info'][0]->id_almacen>0? $orden['info'][0]->id_almacen: '1');
                    foreach ($almacenes['almacenes'] as $key => $value) { ?>
                      <option value="<?php echo $value->id_almacen ?>" <?php echo set_select('id_almacen', $value->id_almacen, false, $default) ?>><?php echo $value->nombre ?></option>
                    <?php } ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', str_replace(' ', 'T', substr($orden['info'][0]->fecha, 0, 16))); ?>" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio">Folio</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo set_value('folio', $orden['info'][0]->folio); ?>" readonly>
                </div>
              </div>

                <div class="control-group">
                  <div class="controls">
                    <div class="well span9">
              <?php if ($modificar){ ?>
                        <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
              <?php }
              ?>
                      <a class="btn btn-info btn-large btn-block" href="<?php echo base_url('panel/compras_ordenes/ticket/?id='.$orden['info'][0]->id_orden) ?>" target="_BLANK" title="Imprimir">
                        <i class="icon-print icon-white"></i> <span class="hidden-tablet">Imprimir</span></a>
                    </div>
                  </div>
                </div>
            </div>
          </div>

          <div class="row-fluid">  <!-- Box Productos -->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-barcode"></i> Productos</h2>
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
                          <th>CODIGO</th>
                          <th>PRODUCTO</th>
                          <th>CANT.</th>
                          <th>P.U.</th>
                          <th>Importe</th>
                        </tr>
                      </thead>
                      <tbody>

                        <?php
                        $total = $importe = 0;
                        foreach ($orden['info'][0]->productos as $key => $concepto) {
                          $importe = floatval($concepto->precio_unitario*$concepto->cantidad);
                          $total   += $importe;
                          ?>
                          <tr>
                            <td style="width: 70px;">
                              <?php echo $concepto->codigo ?>
                              <input type="hidden" value="<?php echo $concepto->id_producto ?>" name="id_producto[]">
                              <input type="hidden" value="<?php echo $concepto->num_row ?>" name="num_row[]">
                            </td>
                            <td>
                              <?php echo $concepto->producto ?>
                            </td>
                            <td style="width: 65px;">
                              <input type="number" step="any" name="cantidad[]" value="<?php echo $concepto->cantidad ?>" id="cantidad" class="span12 vpositive" min="0.001" <?php echo $modificar ? '' : 'readonly' ?>>
                            </td>
                            <td style="width: 90px;">
                              <input type="text" name="valorUnitario[]" value="<?php echo $concepto->precio_unitario ?>" id="valorUnitario" class="span12 vpositive" readonly>
                            </td>
                            <td style="width: 150px;">
                              <input type="text" name="importe[]" value="<?php echo $importe ?>" id="valorUnitario" class="span12 vpositive" readonly>
                            </td>
                          </tr>
                         <?php } ?>
                      </tbody>
                      <tfoot>
                        <tr>
                          <th colspan="4"></th>
                          <th><?php echo MyString::formatoNumero($total, 2, '$', false); ?></th>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>
               </div> <!-- /box-body -->
            </div> <!-- /box -->
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
</script>
<?php }
}?>
<!-- Bloque de alertas -->