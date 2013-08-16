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

        <form class="form-horizontal" action="<?php echo base_url('panel/cuentas_cobrar/agregar_abono?'.String::getVarsLink(array())); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span12">

              <div class="control-group">
                <label class="control-label" for="dfecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="dfecha" class="span6" id="dfecha" value="<?php echo set_value('dfecha', date("Y-m-d\TH:i")); ?>" autofocus>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dcuenta">Cuenta Bancaria</label>
                <div class="controls">
                  <select name="dcuenta" id="dcuenta">
                <?php 
                foreach ($cuentas['cuentas'] as $key => $value) {
                ?>
                    <option value="<?php echo $value->id_cuenta; ?>" <?php echo set_select('dcuenta', $value->id_cuenta); ?>><?php echo $value->alias; ?></option>
                <?php
                }
                ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dconcepto">Descripcion</label>
                <div class="controls">
                  <input type="text" name="dconcepto" class="span12" id="dconcepto" value="<?php echo set_value('dconcepto'); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dmonto">Monto</label>
                <div class="controls">
                  <input type="number" name="dmonto" class="span8 vpositive" id="dmonto" value="<?php echo set_value('dmonto', $data['saldo']); ?>" min="1" max="<?php echo $data['saldo'] ?>">
                </div>
              </div>
            </div>
            <button type="submit" class="btn btn-success btn-large">Guardar</button>
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