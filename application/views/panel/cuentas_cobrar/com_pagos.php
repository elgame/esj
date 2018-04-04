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

        <form class="form-horizontal" action="<?php echo base_url('panel/cuentas_cobrar/com_pago?'.String::getVarsLink(array())); ?>" method="post" id="form">

          <div class="row-fluid">
            <div class="span12">

              <div class="control-group">
                <label class="control-label" for="dcuenta">Método de pago</label>
                <div class="controls">
                  <strong><?php echo $metodo_pago ?></strong>
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

            <button type="submit" name="save" class="btn btn-success btn-large">Registrar</button>
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
    noty({"text": "<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<?php if ($closeModal) { ?>
  <script>
  $(function(){
    setInterval(function() {
      window.parent.$('#supermodal').modal('hide');
      window.parent.location = window.parent.location;
    }, 1000);
  });
  </script>
<?php } ?>
<!-- Bloque de alertas -->
