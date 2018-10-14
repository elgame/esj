<div id="content">

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Agregar Abono</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/cuentas_cobrar/agregar_abono?'.MyString::getVarsLink(array())); ?>" method="post" id="form">

          <div class="row-fluid">
            <div class="span12">

              <div class="control-group">
                <label class="control-label" for="dfecha">Fecha</label>
                <div class="controls">
                  <input type="date" name="dfecha" class="span6" id="dfecha" value="<?php echo set_value('dfecha', date("Y-m-d")); ?>" autofocus required>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcuenta">Cuenta Bancaria</label>
                <div class="controls">
                  <select name="dcuenta" id="dcuenta" required>
                <?php
                foreach ($cuentas['cuentas'] as $key => $value) {
                ?>
                    <option value="<?php echo $value->id_cuenta; ?>" <?php echo set_select('dcuenta', $value->id_cuenta); ?>><?php echo $value->alias.' - '.MyString::formatoNumero($value->saldo); ?></option>
                <?php
                }
                ?>
                  </select>

                  <label for="imprimir" style="float: right;">Imprimir recibo?
                    <input type="checkbox" name="imprimir" id="imprimir" value="si">
                  </label>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fmetodo_pago">Metodo de pago </label>
                <div class="controls">
                  <select name="fmetodo_pago" id="fmetodo_pago" required>
                  <?php foreach ($metods_pago as $key => $value) { ?>
                    <option value="<?php echo $value['value']; ?>" <?php echo set_select('fmetodo_pago', $value['value']); ?>><?php echo $value['nombre']; ?></option>
                  <?php }?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dreferencia">Referencia</label>
                <div class="controls">
                  <input type="text" name="dreferencia" class="span6" id="dreferencia" value="<?php echo set_value('dreferencia'); ?>" maxlength="10" required>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dconcepto">Concepto</label>
                <div class="controls">
                  <input type="text" name="dconcepto" class="span12" id="dconcepto" value="<?php echo set_value('dconcepto'); ?>" maxlength="100" required>
                </div>
              </div>

              <div class="control-group" style="display: <?php echo (isset($_GET['total']{0})? 'none': 'block'); ?>">
                <label class="control-label" for="dmonto">Monto</label>
                <div class="controls">
                  <input type="number" step="any" name="dmonto" class="span8 vpositive" id="dmonto" value="<?php echo set_value('dmonto', $data['saldo']); ?>" min="1" data-max="<?php echo $data['saldo'] ?>">
                </div>
              </div>

            </div>
            <?php
            if(isset($_GET['total']{0})) //si es masivo
            {
            ?>
            <div class="span11" id="abonomasivo">
              <table class="table table-striped table-bordered table-condensed bootstrap-datatable">
              <thead>
                <tr>
                  <th>Factura</th>
                  <th>Saldo</th>
                  <th>Monto</th>
                  <th>Saldar</th>
                </tr>
              </thead>
              <tbody>
              <?php
              $suma_saldo = $suma_monto = 0;
              foreach ($data['facturas'] as $key => $value)
              {
                $suma_saldo += $value['saldo'];
                $suma_monto += $value['saldo'];
              ?>
                <tr>
                  <td><?php echo $value['cobro'][0]->serie.$value['cobro'][0]->folio; ?>
                    <input type="hidden" name="factura_desc[]" value="<?php echo $value['cobro'][0]->serie.$value['cobro'][0]->folio; ?>">
                    <input type="hidden" name="ids[]" value="<?php echo $value['cobro'][0]->id; ?>">
                    <input type="hidden" name="tipos[]" value="<?php echo $value['cobro'][0]->tipo; ?>">
                  </td>
                  <td><?php echo $value['saldo']; ?></td>
                  <td><input type="number" step="any" name="montofv[]" class="monto_factura" value="<?php echo $value['saldo'] ?>" min="1" data-max="<?php echo $value['saldo'] ?>"></td>
                  <td>
                    <select name="saldar[]">
                      <option value="no">No</option>
                      <option value="si">Si</option>
                    </select>
                  </td>
                </tr>
              <?php
              }
              ?>
                <tr style="font-weight:bold;">
                  <td>Totales</td>
                  <td><?php echo MyString::formatoNumero($suma_saldo, 2, '$', false); ?></td>
                  <td id="suma_monto"><?php echo MyString::formatoNumero($suma_monto, 2, '$', false); ?></td>
                </tr>
              </tbody>
              </table>
            </div>
            <div class="clearfix"></div>
            <?php } ?>

            <button type="submit" id="btnGuardarAbono" class="btn btn-success btn-large" <?php echo ($frm_errors['ico'] === 'success'? 'disabled' : '') ?>>Guardar</button>
          </div><!--/row-->

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