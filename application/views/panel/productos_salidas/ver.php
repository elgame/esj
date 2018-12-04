<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/productos_salidas/'); ?>">Salidas de Productos</a> <span class="divider">/</span>
      </li>
      <li>Agregar</li>
    </ul>
  </div>


  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Ver Salida</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/productos_salidas/ver/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $salida['info'][0]->empresa) ?>" placeholder="" readonly>
                    <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $salida['info'][0]->id_empresa) ?>">
                  </div>
                </div>
              </div><!--/control-group -->

             <!--  <div class="control-group">
                <label class="control-label" for="empresa">Concepto </label>
                <div class="controls">
                  <div class="input-append span12">
                    <textarea name="conceptoSalida" class="span12" rows="7" maxlength="200" readonly><?php echo set_value('conceptoSalida', $salida['info'][0]->concepto) ?></textarea>
                  </div>
                </div>
              </div> --><!--/control-group -->
            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', str_replace(' ', 'T', substr($salida['info'][0]->fecha, 0, 16))); ?>" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio">Folio</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo set_value('folio', $salida['info'][0]->folio); ?>" readonly>
                </div>
              </div>

                <div class="control-group">
                  <div class="controls">
                    <div class="well span9">
              <?php if ($modificar){ ?>
                        <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
              <?php }
                    echo $this->usuarios_model->getLinkPrivSm('productos_salidas/imprimir/', array(
                      'params'   => 'id='.$salida['info'][0]->id_salida,
                      'btn_type' => 'btn-info btn-large btn-block',
                      'attrs' => array('target' => '_BLANK'))
                    );
              ?>
                    </div>
                  </div>
                </div>
            </div>
          </div>

          <div class="row-fluid" id="groupCatalogos">  <!-- Box catalogos-->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-truck"></i> Catálogos</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">
                  <div class="span6">
                    <div class="control-group" id="cultivosGrup">
                      <label class="control-label" for="area">Cultivo / Actividad / Producto </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area', isset($salida['info'][0]->area->nombre) ? $salida['info'][0]->area->nombre : '') ?>" placeholder="Limon, Piña" <?php echo $modificar ? '' : 'readonly' ?>>
                        </div>
                        <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId', isset($salida['info'][0]->area->id_area) ? $salida['info'][0]->area->id_area : '') ?>">
                      </div>
                    </div><!--/control-group -->

                    <div class="control-group" id="ranchosGrup">
                      <label class="control-label" for="rancho">Areas / Ranchos / Lineas </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="rancho" class="span11" id="rancho" value="" placeholder="Milagro A, Linea 1" <?php echo $modificar ? '' : 'readonly' ?>>
                        </div>
                      </div>
                      <ul class="tags" id="tagsRanchoIds">
                      <?php if (isset($salida['info'][0]->rancho)) {
                        foreach ($salida['info'][0]->rancho as $key => $rancho) { ?>
                          <li class="<?php echo $modificar? '': 'disable' ?>"><span class="tag"><?php echo $rancho->nombre ?></span>
                            <input type="hidden" name="ranchoId[]" class="ranchoId" value="<?php echo $rancho->id_rancho ?>">
                            <input type="hidden" name="ranchoText[]" class="ranchoText" value="<?php echo $rancho->nombre ?>">
                          </li>
                       <?php }} ?>
                      </ul>
                    </div><!--/control-group -->
                  </div>

                  <div class="span6">
                    <div class="control-group" id="centrosCostosGrup">
                      <label class="control-label" for="centroCosto">Centro de costo </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="centroCosto" class="span11" id="centroCosto" value="" placeholder="Mantenimiento, Gasto general" <?php echo $modificar ? '' : 'readonly' ?>>
                        </div>
                      </div>
                      <ul class="tags" id="tagsCCIds">
                      <?php if (isset($salida['info'][0]->centroCosto)) {
                        foreach ($salida['info'][0]->centroCosto as $key => $centroCosto) { ?>
                          <li class="<?php echo $modificar? '': 'disable' ?>"><span class="tag"><?php echo $centroCosto->nombre ?></span>
                            <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="<?php echo $centroCosto->id_centro_costo ?>">
                            <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="<?php echo $centroCosto->nombre ?>">
                          </li>
                       <?php }} ?>
                      </ul>
                    </div><!--/control-group -->

                    <div class="control-group" id="activosGrup">
                      <label class="control-label" for="activos">Activos </label>
                      <div class="controls">
                        <div class="input-append span12">
                          <input type="text" name="activos" class="span11" id="activos" value="<?php echo set_value('activos', isset($salida['info'][0]->activo->nombre) ? $salida['info'][0]->activo->nombre : '') ?>" placeholder="Nissan FRX, Maquina limon" <?php echo $modificar ? '' : 'readonly' ?>>
                        </div>
                        <input type="hidden" name="activoId" id="activoId" value="<?php echo set_value('activoId', isset($salida['info'][0]->activo->id_producto) ? $salida['info'][0]->activo->id_producto : '') ?>">
                      </div>
                    </div><!--/control-group -->
                  </div>

                </div>

               </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->

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
                        foreach ($salida['info'][0]->productos as $key => $concepto) {
                          $importe = floatval($concepto->precio_unitario*$concepto->cantidad);
                          $total   += $importe;
                          ?>
                          <tr>
                            <td style="width: 70px;">
                              <?php echo $concepto->codigo ?>
                              <input type="hidden" value="<?php echo $concepto->id_producto ?>" name="id_producto[]">
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