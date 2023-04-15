<div id="content">

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Registrar Complemento de Pago</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/cuentas_cobrar/com_pago?'.MyString::getVarsLink(array())); ?>" method="post" id="formCompago">

          <div class="row-fluid">
            <div class="span12">

              <div class="control-group">
                <label class="control-label" for="dcuenta">Método de pago</label>
                <div class="controls">
                  <strong><?php echo $metodo_pago ?></strong>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="moneda">Moneda</label>
                <div class="controls">
                  <select name="moneda" class="span8 pull-left" id="moneda">
                    <option value="MXN" selected="selected">Peso mexicano (MXN)</option>
                    <option value="USD">Dólar estadounidense (USD)</option>
                  </select>
                  <input type="text" name="tipoCambio" class="span3 pull-left vpositive" id="tipoCambio" value="" style="display: none;" placeholder="Tipo de Cambio">
                </div>
              </div>

            <?php if ($metodo_pago != 'efectivo') { ?>
              <div class="control-group">
                <label class="control-label" for="dcuenta">Cuenta Bancaria Cliente</label>
                <div class="controls">
                  <select name="dcuenta" id="dcuenta" required>
                    <option value=""></option>
                    <?php
                    foreach ($cuentas as $key => $value) {
                    ?>
                    <option value="<?php echo $value->id_cuenta; ?>" <?php echo set_select('dcuenta', $value->id_cuenta); ?>><?php echo $value->full_alias; ?></option>
                    <?php
                    }
                    ?>
                  </select>
                </div>
              </div>
            <?php } ?>
              <hr>
              <div class="control-group">
                <label class="control-label" for="dcuenta">Tipo relación</label>
                <div class="controls">
                  <select name="cfdiRel[tipo]" id="cfdiRelTipo">
                    <option value=""></option>
                    <?php
                    foreach ($tiposRelacion as $key => $value) {
                    ?>
                    <option value="<?php echo $value['key']; ?>" <?php echo set_select('cfdiRel[tipo]', $value['key']); ?>><?php echo $value['key'].' - '.$value['value']; ?></option>
                    <?php
                    }
                    ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="addComplemento">Complemento de pago </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" class="span11" id="addComplemento" placeholder="folio de complemento de pago">
                    <input type="hidden" id="empresaId" value="<?php echo $movs->id_empresa ?>">
                    <input type="hidden" id="clienteId" value="<?php echo $movs->id_cliente ?>">
                  </div>
                </div>
              </div>

              <div class="control-group">
                <ol id="listaComPago">
                </ol>
              </div>

            <button type="submit" name="save" id="btnRegComPago" class="btn btn-success btn-large" style="float: right;">
              <img class="loader" src="<?php echo base_url('application/images/bootstrap/ajax-loaders/ajax-loader-9.gif'); ?>" style="display: none;">
              Registrar</button>
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
    noty({"text": "<?php echo addslashes($frm_errors['msg']); ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<?php if ($closeModal) { ?>
  <script>
  $(function(){
    setTimeout(function() {
      window.parent.$('#supermodal').modal('hide');
      window.parent.location.href = window.parent.location.href;
    }, 1000);
  });
  </script>
<?php } ?>
<!-- Bloque de alertas -->
