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
              class="form-horizontal" id="formPina">
              <fieldset class="row-fluid">
                <legend></legend>

                <input type="hidden" name="id_salida_pina" id="id_salida_pina" value="<?php echo (isset($pina['info']->id)? $pina['info']->id: '') ?>">
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
                      <input type="text" name="folio" id="folio" class="span12" required
                      value="<?php echo set_value('folio', (isset($pina['info']->folio)? $pina['info']->folio: '') ); ?>" autofocus placeholder="1, 2, 40, 100">
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="rancho">Rancho</label>
                    <div class="controls">
                      <input type="text" name="rancho" id="rancho" class="span12" required
                        value="<?php echo set_value('rancho', (isset($pina['info']->rancho)? $pina['info']->rancho: '') ); ?>" placeholder="Milagro A" data-next="icantidad">
                      <input type="hidden" name="ranchoId" id="ranchoId"
                        value="<?php echo set_value('ranchoId', (isset($pina['info']->id_rancho)? $pina['info']->id_rancho: '') ) ?>">
                    </div>
                  </div>
                </div>

                <div class="span5">
                  <div class="control-group">
                    <label class="control-label" for="kilos_neto">Kilos Neto</label>
                    <div class="controls">
                      <input type="text" name="kilos_neto" id="kilos_neto" class="span12 vpos-int"
                        value="<?php echo set_value('kilos_neto', (isset($pina['info']->kilos_neto)? $pina['info']->kilos_neto: $boleta->kilos_neto) ); ?>" readonly>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="total_piezas">Piezas</label>
                    <div class="controls">
                      <input type="text" name="total_piezas" id="total_piezas" class="span12"
                        value="<?php echo set_value('total_piezas', (isset($pina['info']->total_piezas)? $pina['info']->total_piezas: '')); ?>" readonly>
                    </div>
                  </div>
                  <div class="control-group">
                    <label class="control-label" for="kg_pieza">Kg por pieza</label>
                    <div class="controls">
                      <input type="text" name="kg_pieza" id="kg_pieza" class="span12"
                        value="<?php echo set_value('kg_pieza', (isset($pina['info']->kg_pieza)? $pina['info']->kg_pieza: '')); ?>" readonly>
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
                    <!-- <label>Melga</label>
                    <input type="text" class="span11" id="icentroCosto" placeholder="Melga 1">
                    <input type="hidden" id="icentroCostoId"> -->

                    <label for="centroCosto">Melga </label>
                    <input type="text" name="centroCosto" class="span12" id="centroCosto" value="<?php echo set_value('centroCosto') ?>" placeholder="Melga 1">
                    <ul class="tags" id="tagsCCIds">
                    <?php if (isset($_POST['centroCostoId'])) {
                      foreach ($_POST['centroCostoId'] as $key => $centroCostoId) { ?>
                        <li><span class="tag"><?php echo $_POST['centroCostoText'][$key] ?></span>
                          <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="<?php echo $centroCostoId ?>">
                          <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="<?php echo $_POST['centroCostoText'][$key] ?>">
                        </li>
                     <?php }} ?>
                    </ul>
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
                        <?php if (isset($pina['estibas']) && count($pina['estibas']) > 0) {
                                foreach ($pina['estibas'] as $key => $estiba) {
                        ?>
                          <tr>
                            <td><input type="text" name="estiba[]" value="<?php echo $estiba->estiba ?>" class="estiba" readonly></td>
                            <td><input type="hidden" name="id_centro_costo[]" value="<?php echo $estiba->id_centro_costo ?>"><?php echo $estiba->centro_costo ?></td>
                            <td><input type="hidden" name="id_calidad[]" value="<?php echo $estiba->id_calidad ?>"><?php echo $estiba->calidad ?></td>
                            <td><input type="text" name="cantidad[]" value="<?php echo $estiba->cantidad ?>" class="cantidad" readonly></td>
                            <td><button class="btn btn-info" type="button" title="Eliminar" id="delCaja"><i class="icon-trash"></i></button></td>
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

