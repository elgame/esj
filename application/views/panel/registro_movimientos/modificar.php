<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/registro_movimientos/'); ?>">Registro de movimientos</a> <span class="divider">/</span>
      </li>
      <li>Agregar</li>
    </ul>
  </div>


  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Agregar movimiento</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/registro_movimientos/modificar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo isset($poliza['info']->empresa)? $poliza['info']->empresa:''; ?>" placeholder="" autofocus readonly><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                  <input type="hidden" name="empresaId" id="empresaId" value="<?php echo isset($poliza['info']->id_empresa)? $poliza['info']->id_empresa:''; ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="concepto">Concepto</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="concepto" class="span11" id="concepto" value="<?php echo isset($poliza['info']->concepto)? $poliza['info']->concepto:''; ?>" placeholder="" required readonly>
                  </div>
                </div>
              </div>
            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="date" name="fecha" class="span9" id="fecha" value="<?php echo isset($poliza['info']->fecha)? $poliza['info']->fecha:''; ?>" readonly>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <!-- <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button> -->
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row-fluid" id="productos">  <!-- Box Productos -->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-barcode"></i> Movimientos</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">

                  <!-- <div class="span12 mquit">
                    <div class="span4">
                      <input type="text" name="centroCosto" class="span12" id="centroCosto" value="" placeholder="Mantenimiento, Gasto general">
                      <input type="hidden" name="centroCostoId" id="centroCostoId" value="">
                    </div>
                    <div class="span2">
                      <input type="text" value="" class="span12 vpositive" id="fcuentaCtp" placeholder="Contpaq">
                    </div>
                    <div class="span2">
                      <select name="tipo" id="tipo" class="span12">
                        <option value="t">Suma</option>
                        <option value="f">Resta</option>
                      </select>
                    </div>
                    <div class="span2">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fcantidad" min="0.01" placeholder="Cant.">
                    </div>
                    <div class="span2">
                      <button type="button" class="btn btn-success span12" id="btnAddProd">Agregar</button>
                    </div>
                  </div> -->
                </div><!--/row-fluid -->
                <br>
                <div class="row-fluid">
                  <div class="span12 mquit">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                      <thead>
                        <tr>
                          <th>CENTRO DE COSTO</th>
                          <th>CONTPAQ</th>
                          <th>TIPO</th>
                          <th>CANT.</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>

                        <?php
                            $sumas = $restas = 0;
                            if (isset($poliza['info']->movimientos)) {
                              foreach ($poliza['info']->movimientos as $key => $movimiento) {
                                if ($movimiento->tipo == 't') {
                                  $tipo = 'Suma';
                                  $sumas += $movimiento->monto;
                                } else {
                                  $tipo = 'Resta';
                                  $restas += $movimiento->monto;
                                }
                        ?>
                            <tr>
                              <td>
                                <input type="hidden" name="centroCosto[]" value="<?php echo $movimiento->centro_costo ?>" class="centroCosto">
                                <input type="hidden" name="centroCostoId[]" value="<?php echo $movimiento->id_centro_costo ?>" class="centroCostoId">
                                <?php echo $movimiento->centro_costo ?>
                              </td>
                              <td>
                                <?php echo $movimiento->cuenta_cpi ?>
                                <input type="hidden" name="cuentaCtp[]" value="<?php echo $movimiento->cuenta_cpi ?>" class="span12 cuentaCtp">
                              </td>
                              <td>
                                <?php echo $tipo ?>
                                <input type="hidden" name="tipo[]" value="<?php echo $movimiento->tipo ?>" class="span12 tipo">
                              </td>
                              <td>
                                <?php echo $movimiento->monto ?>
                                <input type="hidden" name="cantidad[]" value="<?php echo $movimiento->monto ?>" class="span12 cantidad">
                              </td>
                              <td style="width: 35px;">
                                <!-- <button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button> -->
                              </td>
                            </tr>
                         <?php }} ?>
                      </tbody>
                      <tfoot>
                        <tr>
                          <td>Sumas: <strong id="sumas"><?php echo $sumas ?></strong></td>
                          <td>Restas: <strong id="restas"><?php echo $restas ?></strong></td>
                          <td>Diferencia: <strong id="diferencia"><?php echo $sumas - $restas ?></strong></td>
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

<?php if (floatval($prints) > 0) { ?>
  <!-- <script>
    var win=window.open(<?php echo "'".base_url('panel/registro_movimientos/imprimir/?id=' . $prints."'") ?>, '_blank');
    win.focus();
  </script> -->
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