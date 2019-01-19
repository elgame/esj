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

        <form class="form-horizontal" action="<?php echo base_url('panel/registro_movimientos/agregar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

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
                <label class="control-label" for="concepto">Concepto</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="concepto" class="span11" id="concepto" value="<?php echo set_value('concepto') ?>" placeholder="" required>
                  </div>
                </div>
              </div>
            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="date" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', $fecha); ?>">
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
                <h2><i class="icon-barcode"></i> Movimientos</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">

                  <div class="span12 mquit">
                    <!-- <div class="row-fluid">
                    </div> -->
                      <div class="span4">
                        <input type="text" class="span12" id="centroCosto" value="" placeholder="Mantenimiento, Gasto general">
                        <input type="hidden" id="centroCostoId" value="">
                      </div><!--/span3s -->
                      <div class="span2">
                        <input type="text" value="" class="span12 vpositive" id="fcuentaCtp" placeholder="Contpaq">
                      </div>
                      <div class="span2">
                        <select id="tipo" class="span12">
                          <option value="t">Suma</option>
                          <option value="f">Resta</option>
                        </select>
                      </div><!--/span3s -->
                      <div class="span2">
                        <input type="number" step="any" value="" class="span12 vpositive" id="fcantidad" min="0.01" placeholder="Cant." data-next="conceptoMov">
                      </div>

                      <div style="display: none" id="grupoBanco">
                        <div class="span4">
                          <input type="text" class="span12 sikey" id="conceptoMov" value="" placeholder="Concepto del movimiento">
                        </div>
                        <div class="span4">
                          <input type="text" class="span12 sikey" id="cliente" value="" placeholder="Cliente">
                          <input type="hidden" id="did_cliente" value="">
                        </div>
                        <div class="span2">
                          <select id="fmetodo_pago" class="sikey">
                            <?php  foreach ($metods_pago as $key => $value) {
                            ?>
                            <option value="<?php echo $value['value']; ?>"><?php echo $value['nombre']; ?></option>
                            <?php
                            } ?>
                          </select>
                        </div>

                        <div class="span2" id="divAbonoCuenta">
                          Abono a cuenta
                          <input type="checkbox" id="fabonoCuenta" value="true" class="sikey">
                        </div>
                      </div>

                      <div class="span2">
                        <button type="button" class="btn btn-success span12" id="btnAddProd">Agregar</button>
                      </div>
                    <!-- <div class="row-fluid">
                    </div> -->
                  </div><!--/span12 -->
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

                        <?php if (isset($_POST['centroCostoId'])) {
                              foreach ($_POST['centroCostoId'] as $key => $concepto) { ?>
                            <tr>
                              <td>
                                <input type="hidden" name="conceptoMov[]" value="<?php echo $_POST['conceptoMov'][$key] ?>" class="conceptoMov">
                                <input type="hidden" name="cliente[]" value="<?php echo $_POST['cliente'][$key] ?>" class="cliente">
                                <input type="hidden" name="idCliente[]" value="<?php echo $_POST['idCliente'][$key] ?>" class="idCliente">
                                <input type="hidden" name="metodoPago[]" value="<?php echo $_POST['metodoPago'][$key] ?>" class="metodoPago">

                                <input type="hidden" name="centroCosto[]" value="<?php echo $_POST['centroCosto'][$key] ?>" class="centroCosto">
                                <input type="hidden" name="centroCostoId[]" value="<?php echo $_POST['centroCostoId'][$key] ?>" class="centroCostoId">
                                <?php echo $_POST['centroCosto'][$key] ?>
                              </td>
                              <td>
                                <?php echo $_POST['cuentaCtp'][$key] ?>
                                <input type="hidden" name="cuentaCtp[]" value="<?php echo $_POST['cuentaCtp'][$key] ?>" class="span12 cuentaCtp">
                              </td>
                              <td>
                                <?php echo ($_POST['tipo'][$key] == 't'? 'Suma': 'Resta') ?>
                                <input type="hidden" name="tipo[]" value="<?php echo $_POST['tipo'][$key] ?>" class="span12 tipo">
                              </td>
                              <td>
                                <?php echo $_POST['cantidad'][$key] ?>
                                <input type="hidden" name="cantidad[]" value="<?php echo $_POST['cantidad'][$key] ?>" class="span12 cantidad">
                              </td>
                              <td style="width: 35px;"><button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button></td>
                            </tr>
                         <?php }} ?>
                      </tbody>
                      <tfoot>
                        <tr>
                          <td></td>
                          <td>Sumas: <strong id="sumas"></strong></td>
                          <td>Restas: <strong id="restas"></strong></td>
                          <td>Diferencia: <strong id="diferencia"></strong></td>
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