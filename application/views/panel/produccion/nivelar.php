<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/produccion/'); ?>">Ordenes de producción</a> <span class="divider">/</span>
      </li>
      <li>Nivelar</li>
    </ul>
  </div>


  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Nivelar</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/produccion/agregar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $empresa_default->nombre_fiscal) ?>" placeholder="" autofocus><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                  <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $empresa_default->id_empresa) ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="clasificacion">Clasificacion</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="clasificacion" class="span11" id="clasificacion" value="<?php echo set_value('clasificacion') ?>" placeholder="" required>
                    <input type="hidden" name="id_clasificacion" id="id_clasificacion" value="<?php echo set_value('id_clasificacion'); ?>">
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="cantidad_produccion">Cantidad</label>
                <div class="controls">
                  <input type="number" name="cantidad_produccion" class="span9 vpositive" id="cantidad_produccion" value="<?php echo set_value('cantidad_produccion', '1'); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="costo_adicional">Costo adicional</label>
                <div class="controls">
                  <input type="number" name="costo_adicional" class="span9 vpositive" id="costo_adicional" value="<?php echo set_value('costo_adicional', '0'); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="id_almacen">Almacen (saldra la materia prima)</label>
                <div class="controls">
                  <div class="input-append span12">
                    <select name="id_almacen" class="span11" id="id_almacen">
                    <?php $default = ($this->input->post('id_almacen')>0? $this->input->post('id_almacen'): '1');
                    foreach ($almacenes['almacenes'] as $key => $value) { ?>
                      <option value="<?php echo $value->id_almacen ?>" <?php echo set_select('id_almacen', $value->id_almacen, false, $default) ?>><?php echo $value->nombre ?></option>
                    <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <!--/control-group -->
            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="fecha_produccion">Produccion</label>
                <div class="controls">
                  <input type="datetime-local" name="fecha_produccion" class="span9" id="fecha_produccion" value="<?php echo set_value('fecha_produccion', $fecha); ?>">
                </div>
              </div>

              <!-- <div class="control-group">
                <label class="control-label" for="folio">Folio</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo set_value('folio', $next_folio); ?>" size="25" readonly>
                </div>
              </div> -->

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row-fluid" id="productos">  <!-- Box Productos -->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-barcode"></i> Clasificaciones</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">

                  <div class="span12 mquit">
                    <div class="span5">
                      <div class="input-append span12">
                        <input type="text" class="span12" id="fconcepto" placeholder="Clasificacion">
                        <!-- <a href="" rel="superbox-70x550" class="btn btn-info" type="button" data-rel="tooltip" data-title="Agregar Producto"><i class="icon-plus" ></i></a> -->
                      </div>
                      <input type="hidden" class="span1" id="fconceptoId">
                      <input type="hidden" class="span1" id="ftipoproducto">
                      <input type="hidden" class="span1" id="fprecio_unitario">
                    </div><!--/span3s -->
                    <div class="span1">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fexistencia" min="0.01" placeholder="Existencia" readonly>
                    </div><!--/span3s -->
                    <div class="span2">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fnewexistencia" min="0.01" placeholder="Nueva Existencia">
                    </div><!--/span3s -->
                    <div class="span2">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fcantidad" min="0.01" placeholder="Cant." readonly>
                    </div><!--/span3s -->
                    <div class="span2">
                      <button type="button" class="btn btn-success span12" id="btnAddProd">Agregar</button>
                    </div><!--/span2 -->
                  </div><!--/span12 -->
                </div><!--/row-fluid -->
                <br>
                <div class="row-fluid">
                  <div class="span12 mquit">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                      <thead>
                        <tr>
                          <th>Unidad</th>
                          <th>Producto</th>
                          <th>Existencia</th>
                          <th>Cantidad</th>
                          <th>Costo</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>

                        <?php if (isset($_POST['concepto'])) {
                              foreach ($_POST['concepto'] as $key => $concepto) { ?>
                            <tr>
                              <td>
                                <?php echo $_POST['unidad'][$key] ?>
                                <input type="hidden" name="tipoProducto[]" value="<?php echo $_POST['tipoProducto'][$key] ?>">
                                <input type="hidden" name="precioUnit[]" value="<?php echo $_POST['precioUnit'][$key] ?>">
                                <input type="hidden" name="unidad[]" value="<?php echo $_POST['unidad'][$key] ?>">
                              </td>
                              <td>
                                <?php echo $concepto ?>
                                <input type="hidden" name="concepto[]" value="<?php echo $concepto ?>" id="concepto" class="span12">
                                <input type="hidden" name="productoId[]" value="<?php echo $_POST['productoId'][$key] ?>" id="productoId" class="span12">
                                <input type="hidden" name="inventario[]" value="<?php echo $_POST['inventario'][$key] ?>" id="inventario" class="span12">
                              </td>
                              <td>
                                <?php echo $_POST['inventario'][$key] ?>
                              </td>
                              <td style="width: 65px;">
                                  <input type="number" step="any" name="cantidad[]" value="<?php echo $_POST['cantidad'][$key] ?>" id="cantidad" class="span12 vpositive" min="0.01">
                              </td>
                              <td style="width: 85px;">
                                  <input type="number" step="any" name="importe[]" value="<?php echo $_POST['importe'][$key] ?>" id="importe" class="span12 vpositive" min="0.01" readonly>
                              </td>
                              <td style="width: 35px;"><button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button></td>
                            </tr>
                         <?php }} ?>
                      </tbody>
                      <tfoot>
                        <tr>
                          <th colspan="4" style="text-align: right;">Costo materia prima</th>
                          <th><input type="number" step="any" name="costo_materiap" value="<?php echo (isset($_POST['costo_materiap'])? $_POST['costo_materiap']: 0) ?>" id="costo_materiap" class="span12 vpositive" min="0.01" readonly></th>
                          <th></th>
                        </tr>
                        <tr>
                          <th colspan="4" style="text-align: right;">Costo total</th>
                          <th><input type="number" step="any" name="costo" value="<?php echo (isset($_POST['costo'])? $_POST['costo']: 0) ?>" id="costo" class="span12 vpositive" min="0.01" readonly></th>
                          <th></th>
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


  <!-- Modal -->
  <div id="modalAreas" class="modal modal-w70 hide fade" tabindex="-1" role="dialog" aria-labelledby="modalAreasLavel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="modalAreasLavel">Catalogo de maquinaria, equipos e instalaciones</h3>
    </div>
    <div class="modal-body">

      <div class="row-fluid">

        <div>

      <?php foreach ($areas as $key => $value)
      { ?>
          <div class="span3" id="tblAreasDiv<?php echo $value->id_tipo ?>" style="display: none;">
            <table class="table table-hover table-condensed <?php echo ($key==0? 'tblAreasFirs': ''); ?>"
                id="tblAreas<?php echo $value->id_tipo ?>" data-id="<?php echo $value->id_tipo ?>">
              <thead>
                <tr>
                  <th style="width:10px;"></th>
                  <th>Codigo</th>
                  <th><?php echo $value->nombre ?></th>
                </tr>
              </thead>
              <tbody>
                <!-- <tr class="areaClick" data-id="" data-sig="">
                  <td><input type="radio" name="modalRadioSel" value="" data-uniform="false"></td>
                  <td>9</td>
                  <td>EMPAQUE</td>
                </tr> -->
              </tbody>
            </table>
          </div>
      <?php
      } ?>

        </div>

      </div>

    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
      <button class="btn btn-primary" id="btnModalAreasSel">Seleccionar</button>
    </div>
  </div>

</div>

<?php if (floatval($prints) > 0) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/productos_salidas/imprimirticket/?id=' . $prints."'") ?>, '_blank');
    win.focus();
  </script>
<?php } ?>
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