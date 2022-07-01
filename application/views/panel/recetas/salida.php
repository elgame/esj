<div id="content" class="span<?php echo isset($_GET['idf']) ? '12' : '10' ?>">

  <?php
    $titulo = 'Modificar receta';
    if (! isset($_GET['idf'])){ ?>
    <div>
      <ul class="breadcrumb">
        <li>
          <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
        </li>
        <li>
          <a href="<?php echo base_url('panel/recetas/'); ?>">Recetas</a> <span class="divider">/</span>
        </li>
        <li>Agregar salidas</li>
      </ul>
    </div>
  <?php } ?>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Datos de receta</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal modificar-receta" action="<?php echo base_url('panel/recetas/salida/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

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

              <div class="control-group">
                <label class="control-label" for="almacenId">Almacén</label>
                <div class="controls">
                  <select name="almacenId" class="span11" id="almacenId" required data-next="carga_salida|plantas_salida">
                    <option value=""></option>
                    <?php foreach ($almacenes['almacenes'] as $key => $almacen): ?>
                    <option value="<?php echo $almacen->id_almacen ?>" <?php echo set_select('almacenId', $almacen->id_almacen); ?>><?php echo $almacen->nombre ?></option>
                    <?php endforeach ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="infBoletasSalidas">Boleta </label>
                <div class="controls">
                  <div class="input-append">
                    <button type="button" class="btn btn-info" id="show-boletasSalidas">Buscar</button>
                    <input type="text" name="boletasSalidasFolio" id="boletasSalidasFolio" value="<?php echo set_value('boletasSalidasFolio'); ?>" class="span7" readonly required>
                    <input type="hidden" name="boletasSalidasId" id="boletasSalidasId" value="<?php echo set_value('boletasSalidasId'); ?>" required>
                  </div>
                </div>
              </div><!--/control-group -->

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
                <h2><i class="icon-barcode"></i> Productos <span id="show_info_prod" style="display:none;"><i class="icon-hand-right"></i> <span>Existencia: 443 | Stok: 43</span></span></h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">

                <div class="row-fluid">

                  <div class="span12 mquit">
                    <div class="span8">
                      <?php if ($receta['info']->tipo == 'lts'): ?>
                        <input type="number" id="carga_salida" name="carga_salida" value="<?php echo set_value('carga_salida') ?>"
                          step="any" min="0.01" max="<?php echo $receta['info']->saldo_cargas ?>" class="span5" placeholder="Carga" required autofocus style="float: left;">
                        <span class="span3"> de <?php echo $receta['info']->saldo_cargas ?> | Total de cargas: <?php echo $receta['info']->carga1+$receta['info']->carga2 ?></span>
                      <?php else: ?>
                        <input type="number" id="plantas_salida" name="plantas_salida" value="<?php echo set_value('plantas_salida') ?>"
                          step="any" min="0.01" max="<?php echo $receta['info']->saldo_plantas ?>" class="span5" placeholder="Plantas" required autofocus style="float: left;">
                        <span class="span3"> de <span id="plantas_saldo"><?php echo $receta['info']->saldo_plantas ?></span> | Total de plantas: <?php echo $receta['info']->no_plantas ?></span>
                      <?php endif ?>
                    </div><!--/span3s -->
                  </div><!--/span12 -->

                </div><!--/row-fluid -->
                <br>
                <div class="row-fluid">
                  <div class="span12 mquit">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                      <thead>
                        <tr>
                          <th style="vertical-align: middle;">ETIQUETAS</th>
                          <th style="vertical-align: middle;">PRODUCTO</th>
                          <th style="vertical-align: middle;">CANT.</th>
                          <th class="tipostyle" style="vertical-align: <?php echo ($receta['info']->tipo == 'lts'? '':'middle;display: none;') ?>">CARGA 1</th>
                          <th class="tipostyle" style="vertical-align: <?php echo ($receta['info']->tipo == 'lts'? '':'middle;display: none;') ?>">CARGA 2</th>
                          <th style="vertical-align: middle;">APLI TOTAL</th>
                          <th style="vertical-align: middle;">SALDO TOTAL</th>
                          <th style="vertical-align: middle;">PRECIO</th>
                          <th style="vertical-align: middle;">IMPORTE</th>
                          <th style="vertical-align: middle;">OPC</th>
                        </tr>
                      </thead>
                      <tbody class="bodyproducs">
                        <?php if (isset($receta['info']->productos)) {
                        foreach ($receta['info']->productos as $key => $prod) { ?>

                          <tr class="rowprod">
                            <td style="width: 80px;">
                              <input type="text" name="no_etiqueta[]" value="<?php echo set_value('no_etiqueta', $prod->no_etiqueta, $key) ?>" id="no_etiqueta" class="span12 vinteger" min="0">
                              <span class="percent" style=""><?php echo $prod->percent ?>%</span>
                              <input type="hidden" name="percent[]" value="<?php echo $prod->percent ?>" id="percent">
                              <input type="hidden" name="rows[]" value="<?php echo $prod->rows ?>">
                            </td>
                            <td>
                              <?php echo $prod->producto ?>
                              <input type="hidden" name="concepto[]" value="<?php echo $prod->producto ?>" id="concepto" class="span12">
                              <input type="hidden" name="productoId[]" value="<?php echo $prod->id_producto ?>" id="productoId" class="span12">
                            </td>
                            <td style="width: 90px;">
                              <input type="number" step="any" name="cantidad[]" value="<?php echo $prod->dosis_mezcla ?>" id="cantidad" class="span12 vpositive" min="0" max="<?php echo $prod->aplicacion_total_saldo ?>">
                            </td>
                            <td class="tipostyle" style="width: 80px;<?php echo ($receta['info']->tipo == 'lts'? '':'middle;display: none;') ?>">
                                <input type="number" step="any" name="pcarga1[]" value="<?php echo $prod->dosis_carga1 ?>" id="pcarga1" class="span12 vpositive" min="0" readonly>
                            </td>
                            <td class="tipostyle" style="width: 80px;<?php echo ($receta['info']->tipo == 'lts'? '':'middle;display: none;') ?>">
                                <input type="number" step="any" name="pcarga2[]" value="<?php echo $prod->dosis_carga2 ?>" id="pcarga2" class="span12 vpositive" min="0" readonly>
                            </td>
                            <td style="width: 100px;">
                              <input type="number" step="any" name="aplicacion_total[]" value="<?php echo $prod->aplicacion_total ?>" id="aplicacion_total" class="span12 vpositive" min="0" readonly>
                            </td>
                            <td style="width: 100px;">
                              <input type="number" step="any" name="aplicacion_total_saldo[]" value="<?php echo $prod->aplicacion_total_saldo ?>" id="aplicacion_total_saldo" data-saldo="<?php echo $prod->aplicacion_total_saldo ?>" class="span12 vpositive" min="0" readonly>
                            </td>
                            <td style="width: 90px;">
                              <input type="number" step="any" name="precio[]" value="<?php echo $prod->precio ?>" id="precio" class="span12 vpositive" min="0" readonly>
                            </td>
                            <td style="width: 100px;">
                              <input type="number" step="any" name="importe[]" value="<?php echo $prod->importe ?>" id="importe" class="span12 vpositive" min="0" readonly>
                            </td>
                            <td style="width: 40px;">
                              <button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button>
                            </td>
                          </tr>
                        <?php }} ?>
                      </tbody>
                      <tfoot>
                        <tr style="font-weight: bold;">
                          <td id="ttpercent"></td>
                          <td></td>
                          <td id="ttcantidad"></td>
                          <th id="ttcargo1" class="tipostyle" style="<?php echo ($receta['info']->tipo == 'lts'? '':'middle;display: none;') ?>"></th>
                          <th id="ttcargo2" class="tipostyle" style="<?php echo ($receta['info']->tipo == 'lts'? '':'middle;display: none;') ?>"></th>
                          <td id="ttaplicacion_total"></td>
                          <td></td>
                          <td></td>
                          <td>
                            <span id="ttimporte"></span>
                            <input type="hidden" name="total_importe" id="total_importe" value="<?php echo $receta['info']->total_importe ?>">
                          </td>
                          <td></td>
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

<!-- Modal boletas -->
<div id="modal-boletas" class="modal modal-w50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="myModalLabel">Boletas</h3>
  </div>
  <div class="modal-body">
    <div class="row-fluid">
      <input type="text" id="filBoleta" class="pull-left" placeholder="Folio"> <span class="pull-left"> | </span>
    </div>
    <div class="row-fluid">
      <table class="table table-hover table-condensed" id="table-boletas">
        <thead>
          <tr>
            <th style="width:70px;">Fecha</th>
            <th># Folio</th>
            <th>Proveedor</th>
            <th>Área</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
    <button class="btn btn-primary" id="BtnAddBoleta">Seleccionar</button>
  </div>
</div><!--/modal boletas -->

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
