    <div id="content" class="span12">
      <!-- content starts -->


      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-plus"></i> Lote</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/bascula_pina/show_view_guardar_pina/?idb='.$_GET['idb']); ?>" method="post"
              class="form-horizontal" id="form">
              <fieldset class="row-fluid">
                <legend></legend>

                <input type="hidden" name="id_salida" id="id_salida" value="">
                <input type="hidden" name="id_bascula" id="id_bascula" value="<?php echo $boleta->id_bascula ?>">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo $boleta->id_empresa ?>">
                <input type="hidden" name="id_area" id="id_area" value="<?php echo $boleta->id_area ?>">
                <!--
                <input type="hidden" name="total_piezas" value="<?php echo $boleta->total_piezas ?>">
                 -->

                <div class="span5">
                  <div class="control-group">
                    <label class="control-label" for="folio">Folio</label>
                    <div class="controls">
                      <input type="text" name="folio" id="folio" class="span12 vpos-int" required
                      value="<?php echo set_value('folio', $boleta->folio); ?>" autofocus placeholder="1, 2, 40, 100">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="rancho">Rancho</label>
                    <div class="controls">
                      <input type="text" name="rancho" id="rancho" class="span12" required
                        value="<?php echo set_value('rancho'); ?>" placeholder="Milagro A">
                      <input type="hidden" name="ranchoId" id="ranchoId" value="<?php echo set_value('ranchoId') ?>">
                    </div>
                  </div>
                </div>

                <div class="span5">
                  <div class="control-group">
                    <label class="control-label" for="kilos_neto">Kilos Neto</label>
                    <div class="controls">
                      <input type="text" name="kilos_neto" id="kilos_neto" class="span12 vpos-int"
                        value="<?php echo set_value('kilos_neto', $boleta->kilos_neto); ?>" readonly>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="total_piezas">Piezas</label>
                    <div class="controls">
                      <input type="text" name="total_piezas" id="total_piezas" class="span12"
                        value="<?php echo set_value('total_piezas'); ?>" readonly>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="kg_pieza">Kg por pieza</label>
                    <div class="controls">
                      <input type="text" name="kg_pieza" id="kg_pieza" class="span12"
                        value="<?php echo set_value('kg_pieza'); ?>" readonly>
                    </div>
                  </div>
                </div>

                <div class="clearfix"></div>
                <hr>

                <div class="row-fluid">
                  <div class="span1">
                    <label>Cantidad</label>
                    <input type="text" id="icantidad" class="span11 vpos-int">
                  </div>
                  <div class="span1">
                    <label>Estiba ini</label>
                    <input type="text" id="iestibaIni" class="span11 vpos-int">
                  </div>
                  <div class="span1">
                    <label>Estiba fin</label>
                    <input type="text" id="iestibaFin" class="span11 vpos-int">
                  </div>
                  <div class="span2">
                    <label>Calidad</label>
                    <select class="input-medium" id="icalidad">
                      <option value=""></option>
                      <?php foreach ($calidades as $key => $value): ?>
                      <option value="<?php echo $value->id_calidad ?>"><?php echo $value->nombre ?></option>
                      <?php endforeach ?>
                    </select>
                  </div>
                  <div class="span3">
                    <label>Melga</label>
                    <input type="text" class="span11" id="icentroCosto" placeholder="Melga 1">
                    <input type="hidden" id="icentroCostoId">
                  </div>
                  <div class="span1">
                    <a href="javascript:void(0)" id="addCaja"><i class="icon-plus-sign-alt icon-4x"></i></a>
                  </div>
                </div>
                <br>
                <div class="row-fluid">
                  <div class="span12">

                    <table class="table table-striped table-bordered table-hover" id="tableEstibas">
                      <thead>
                        <tr>
                          <th>Estiba</th>
                          <th>Melga</th>
                          <th>Calidad</th>
                          <th>Cantidad</th>
                          <th>Opc</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td><input type="text" name="estiba[]" value="" readonly></td>
                          <td><input type="hidden" name="id_centro_costo[]" value=""></td>
                          <td><input type="hidden" name="id_calidad[]" value=""></td>
                          <td><input type="text" name="cantidad[]" value="" readonly></td>
                          <td></td>
                        </tr>
                        <?php if (isset($pina['pcajas'])) {
                                foreach ($_POST['pcajas'] as $key => $caja) {
                        ?>
                                  <tr data-kneto="">
                                    <td><?php echo $caja ?>
                                      <input type="hidden" name="pnum_registro[]" value="<?php echo $_POST['pnum_registro'][$key] ?>" id="pnum_registro">
                                      <input type="hidden" name="pcajas[]" value="<?php echo $caja ?>" id="pcajas">
                                      <input type="hidden" name="pcalidad[]" value="<?php echo $_POST['pcalidad'][$key] ?>" id="pcalidad">
                                      <input type="hidden" name="pcalidadtext[]" value="<?php echo $_POST['pcalidadtext'][$key] ?>" id="pcalidadtext">
                                      <!-- <input type="hidden" name="pkilos[]" value="<?php //echo $_POST['pkilos'][$key] ?>" id="pkilos"> -->
                                      <!-- <input type="hidden" name="ppromedio[]" value="<?php //echo $_POST['ppromedio'][$key] ?>" id="ppromedio"> -->
                                      <!-- <input type="hidden" name="pprecio[]" value="<?php //echo $_POST['pprecio'][$key] ?>" id="pprecio"> -->
                                      <input type="hidden" name="pimporte[]" value="<?php echo $_POST['pimporte'][$key] ?>" id="pimporte">
                                    </td>
                                    <td><?php echo $_POST['pcalidadtext'][$key] ?></td>
                                    <td id="tdkilos">

                                      <span><?php echo ($_POST['pkilos_neto'] > 300) ? $_POST['pkilos'][$key] : '' ?></span>
                                      <input type="<?php echo ($_POST['pkilos_neto'] > 300) ? 'hidden' : 'text' ?>" name="pkilos[]" value="<?php echo $_POST['pkilos'][$key] ?>" id="pkilos" style="width: 100px;">
                                    </td>
                                    <td id="tdpromedio">
                                      <input type="text" name="ppromedio[]" value="<?php echo $_POST['ppromedio'][$key] ?>" id="ppromedio" style="width: 80px;" <?php echo $bmod['cajas'][1]; ?>>
                                    </td>
                                    <td>
                                      <?php //echo $_POST['pprecio'][$key] ?>
                                      <input type="text" name="pprecio[]" value="<?php echo $_POST['pprecio'][$key] ?>" class="vpositive" id="pprecio" style="width: 80px;" <?php echo $bmod['cajas'][1]; ?>>
                                    </td>
                                    <td id="tdimporte"><?php echo $_POST['pimporte'][$key] ?></td>
                                    <td><button class="btn btn-info" type="button" title="Eliminar" id="delCaja" <?php echo $disabled.$bmod['cajas'][0]; ?>><i class="icon-trash"></i></button></td>
                                  </tr>
                        <?php }} ?>
                      </tbody>
                    </table>

                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
              </fieldset>
            </form>

          </div>
        </div><!--/span-->

      </div><!--/row-->


          <!-- content ends -->
    </div><!--/#content.span10-->



<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){
    <?php if($frm_errors['ico'] == 'success'){ ?>
      parent.setLoteBoleta();
    <?php } ?>
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->

