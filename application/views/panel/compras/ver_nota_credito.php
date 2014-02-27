<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/compras/'); ?>">Compras</a> <span class="divider">/</span>
      </li>
      <li>Nota de Crédito</li>
    </ul>
  </div>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-th-list"></i> Nota de Crédito</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/compras/ver_nota_credito/?'.String::getVarsLink(array('msg'))); ?>" method="POST" id="form" enctype="multipart/form-data">

          <div class="row-fluid">
            <div class="span12">
              <div class="row-fluid">
                <div class="span3">
                  <div class="control-group">
                    <div class="controls span9">
                      Serie <input type="text" name="serie" class="span12" id="serie" value="<?php echo set_value('serie', $nota_credito['info']->serie); ?>" maxlength="4" autofocus >
                    </div>
                  </div>
                </div>
                <div class="span3">
                  <div class="control-group">
                    <div class="controls span9">
                      Folio<input type="text" name="folio" class="span12" id="folio" value="<?php echo set_value('folio', $nota_credito['info']->folio); ?>">
                    </div>
                  </div>
                </div>
                <div class="span3">
                  <div class="control-group">
                    <div class="controls span9">
                      Fecha<input type="date" name="fecha" class="span12" id="fecha" value="<?php echo set_value('fecha', $fecha); ?>">
                    </div>
                  </div>
                </div>
                <div class="span3">
                  <div class="control-group">
                    <div class="controls span9">
                      XML<input type="file" name="xml" class="span12" id="xml" data-uniform="false" accept="text/xml">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row-fluid">
            <div class="span3 offset9 well">
                <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Actualizar</button>
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
                    <div class="span3">
                      <!-- data-next="fcodigo" -->
                      <input type="text" class="span12" id="fcodigo" placeholder="Codigo" data-next="fcodigo">
                    </div><!--/span3s -->
                    <div class="span6">
                      <div class="input-append span12">
                        <input type="text" class="span12" id="fconcepto" placeholder="Producto / Descripción">
                        <!-- <a href="" rel="superbox-70x550" class="btn btn-info" type="button" data-rel="tooltip" data-title="Agregar Producto"><i class="icon-plus" ></i></a> -->
                      </div>
                      <input type="hidden" class="span1" id="fconceptoId">
                    </div><!--/span3s -->
                    <div class="span1">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fcantidad" min="0.01" placeholder="Cant.">
                    </div><!--/span3s -->
                    <div class="span2">
                      <input type="text" class="span12 vpositive" id="fprecio" placeholder="Precio Unitario">
                    </div><!--/span3s -->

                  </div><!--/span12 -->
                  <br><br>
                  <div class="span12 mquit">
                    <!-- <div class="span3">
                      <label for="fpresentacion" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">PRESENTACION</label>
                      <select class="span12" id="fpresentacion">
                      </select>
                    </div> --><!--/span3 -->
                     <div class="span2">
                      <label for="funidad" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">UNIDAD</label>
                      <select class="span12" id="funidad">
                        <?php foreach ($unidades as $key => $unidad) { ?>
                          <option value="<?php echo $unidad->id_unidad ?>"><?php echo $unidad->nombre ?></option>
                        <?php } ?>
                      </select>
                    </div><!--/span2 -->
                    <div class="span2">
                      <label for="ftraslado" class="span12" style="min-height:20px;font-size: 12px;font-weight: bolder;">IVA</label>
                      <select class="span12" id="ftraslado">
                        <option value="0">0%</option>
                        <option value="11">11%</option>
                        <option value="16">16%</option>
                      </select>
                    </div><!--/span2 -->
                    <div class="span2">
                      <label>&nbsp</label>
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
                          <th>CODIGO</th>
                          <th>PRODUCTO</th>
                          <th>UNIDAD</th>
                          <th>CANT.</th>
                          <th>P.U.</th>
                          <th>IVA</th>
                          <th>RET 4%</th>
                          <th>IMPORTE</th>
                          <th>DESCRIP</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>

                        <?php if (isset($_POST['concepto'])) {
                              foreach ($_POST['concepto'] as $key => $concepto) { ?>
                            <tr>
                              <td style="width: 70px;">
                                <?php echo $_POST['codigo'][$key] ?>
                                <input type="hidden" name="codigo[]" value="<?php echo $_POST['codigo'][$key] ?>" class="span12">
                              </td>
                              <td>
                                  <?php echo $concepto ?>
                                  <input type="hidden" name="concepto[]" value="<?php echo $concepto ?>" id="concepto" class="span12">
                                  <input type="hidden" name="productoId[]" value="<?php echo $_POST['productoId'][$key] ?>" id="productoId" class="span12">
                              </td>
                              <td style="width: 120px;">
                                <select name="unidad[]" id="unidad" class="span12">
                                  <?php foreach ($unidades as $unidad) { ?>
                                    <option value="<?php echo $unidad->id_unidad ?>" <?php echo $_POST['unidad'][$key] == $unidad->id_unidad ? 'selected' : ''; ?>><?php echo $unidad->nombre ?></option>
                                  <?php } ?>
                                </select>
                              </td>
                              <td style="width: 65px;">
                                  <input type="number" name="cantidad[]" value="<?php echo $_POST['cantidad'][$key] ?>" id="cantidad" class="span12 vpositive" min="1">
                              </td>
                              <td style="width: 90px;">
                                  <input type="text" name="valorUnitario[]" value="<?php echo $_POST['valorUnitario'][$key] ?>" id="valorUnitario" class="span12 vpositive">
                              </td>
                              <td style="width: 66px;">
                                  <select name="traslado[]" id="traslado" class="span12">
                                    <option value="0" <?php echo $_POST['traslado'][$key] === '0' ? 'selected' : '' ?>>0%</option>
                                    <option value="11" <?php echo $_POST['traslado'][$key] === '11' ? 'selected' : ''?>>11%</option>
                                    <option value="16" <?php echo $_POST['traslado'][$key] === '16' ? 'selected' : ''?>>16%</option>
                                  </select>
                                  <input type="hidden" name="trasladoTotal[]" value="<?php echo $_POST['trasladoTotal'][$key] ?>" id="trasladoTotal" class="span12">
                                  <input type="hidden" name="trasladoPorcent[]" value="<?php echo $_POST['trasladoPorcent'][$key] ?>" id="trasladoPorcent" class="span12">
                              </td>
                              <td style="width: 66px;">
                                  <input type="text" name="retTotal[]" value="<?php echo $_POST['retTotal'][$key] ?>" id="retTotal" class="span12" readonly>
                              </td>
                              <td>
                                  <span><?php echo String::formatoNumero($_POST['importe'][$key]) ?></span>
                                  <input type="hidden" name="importe[]" value="<?php echo $_POST['importe'][$key] ?>" id="importe" class="span12 vpositive">
                                  <input type="hidden" name="total[]" value="<?php echo $_POST['total'][$key] ?>" id="total" class="span12 vpositive">
                              </td>
                              <td>
                                  <input type="text" name="observacion[]" value="<?php echo $_POST['observacion'][$key] ?>" id="observacion" class="span12">
                              </td>
                              <td style="width: 35px;"><button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button></td>
                            </tr>
                          <?php }} else {
                            foreach ($nota_credito['productos'] as $prod) {
                          ?>
                            <tr>
                              <td style="width: 70px;">
                                <?php echo $prod->codigo ?>
                                <input type="hidden" name="codigo[]" value="<?php echo $prod->codigo ?>" class="span12">
                              </td>
                              <td>
                                <?php echo $prod->producto ?>
                                <input type="hidden" name="concepto[]" value="<?php echo $prod->producto ?>" id="concepto" class="span12">
                                <input type="hidden" name="productoId[]" value="<?php echo $prod->id_producto ?>" id="productoId" class="span12">
                              </td>
                              <td style="width: 120px;">
                                <select name="unidad[]" id="unidad" class="span12">
                                  <?php foreach ($unidades as $unidad) { ?>
                                    <option value="<?php echo $unidad->id_unidad ?>" <?php echo $prod->id_unidad == $unidad->id_unidad ? 'selected' : ''; ?>><?php echo $unidad->nombre ?></option>
                                  <?php } ?>
                                </select>
                              </td>
                              <td style="width: 65px;">
                                  <input type="number" name="cantidad[]" value="<?php echo $prod->cantidad ?>" id="cantidad" class="span12 vpositive" min="1">
                              </td>
                              <td style="width: 90px;">
                                  <input type="text" name="valorUnitario[]" value="<?php echo $prod->precio_unitario ?>" id="valorUnitario" class="span12 vpositive">
                              </td>
                              <td style="width: 66px;">
                                <select name="traslado[]" id="traslado" class="span12">
                                  <option value="0" <?php echo $prod->porcentaje_iva === '0' ? 'selected' : '' ?>>0%</option>
                                  <option value="11" <?php echo $prod->porcentaje_iva === '11' ? 'selected' : ''?>>11%</option>
                                  <option value="16" <?php echo $prod->porcentaje_iva === '16' ? 'selected' : ''?>>16%</option>
                                </select>
                                <input type="hidden" name="trasladoTotal[]" value="<?php echo $prod->iva ?>" id="trasladoTotal" class="span12">
                                <input type="hidden" name="trasladoPorcent[]" value="<?php echo $prod->porcentaje_iva ?>" id="trasladoPorcent" class="span12">
                              </td>
                              <td style="width: 66px;">
                                <input type="text" name="retTotal[]" value="<?php echo $prod->retencion_iva ?>" id="retTotal" class="span12" readonly>
                              </td>
                              <td>
                                <span><?php echo String::formatoNumero($prod->importe) ?></span>
                                <input type="hidden" name="importe[]" value="<?php echo $prod->importe ?>" id="importe" class="span12 vpositive">
                                <input type="hidden" name="total[]" value="<?php echo $prod->total ?>" id="total" class="span12 vpositive">
                              </td>
                              <td>
                                  <input type="text" name="observacion[]" value="<?php echo $prod->observacion ?>" id="observacion" class="span12">
                              </td>
                              <td style="width: 35px;"><button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button></td>
                            </tr>
                          <?php }} ?>
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
                    <th style="background-color:#FFF !important;">TOTAL CON LETRA</th>
                    <th style="background-color:#FFF !important;">TOTALES</th>
                    <th style="background-color:#FFF !important;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td rowspan="7">
                      <textarea name="totalLetra" rows="5" class="nokey" style="width:98%;max-width:98%;" id="totalLetra" readonly><?php echo set_value('totalLetra', '');?></textarea>
                    </td>
                  </tr>
                  <tr>
                    <td><em>Subtotal</em></td>
                    <td>
                      <input type="text" name="totalImporte" id="totalImporte" value="<?php echo String::formatoNumero(set_value('totalImporte', 0), 2, '$', false)?>">
                    </td>
                  </tr>
                  <tr>
                    <td>IVA</td>
                    <td>
                      <input type="text" name="totalImpuestosTrasladados" id="totalImpuestosTrasladados" value="<?php echo String::formatoNumero(set_value('totalImpuestosTrasladados', 0), 2, '$', false)?>">
                    </td>
                  </tr>
                  <tr>
                    <td>RET.</td>
                    <td><input type="text" name="totalRetencion" id="totalRetencion" value="<?php echo String::formatoNumero(set_value('totalRetencion', 0), 2, '$', false)?>"></td>
                  </tr>
                  <tr style="font-weight:bold;font-size:1.2em;">
                    <td>TOTAL</td>
                    <td><input type="text" name="totalOrden" id="totalOrden" value="<?php echo String::formatoNumero(set_value('totalOrden', 0), 2, '$', false)?>"></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
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