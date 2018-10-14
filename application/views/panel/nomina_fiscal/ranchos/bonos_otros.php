<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="es" class="no-js"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title><?php echo $seo['titulo'];?></title>
  <meta name="description" content="<?php echo $seo['titulo'];?>">
  <meta name="viewport" content="width=device-width">

<?php
  if(isset($this->carabiner)){
    $this->carabiner->display('css');
    $this->carabiner->display('base_panel');
    $this->carabiner->display('js');
  }
?>

  <!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
  <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

  <script type="text/javascript" charset="UTF-8">
    var base_url = "<?php echo base_url();?>";
  </script>
</head>
  <body>

    <div class="modal-header">
      <h3><?php echo $empleado['info'][0]->apellido_paterno.' '.$empleado['info'][0]->apellido_materno.' '.$empleado['info'][0]->nombre ?>
        <button type="button" class="btn btn-info" title="Recargar" id="btn-refresh" style="float: right;"><i class="icon-refresh"></i></button></h3>
    </div><!--/modal-header -->

    <div class="modal-body" style="max-height: none;">
      <ul class="nav nav-tabs" id="myTab">
        <li class="active"><a href="#tab-prestamos">Prestamos</a></li>
      </ul>
      <div class="tab-content">
          <div class="tab-pane active" id="tab-prestamos">
            <form class="form-horizontal" action="<?php echo base_url('panel/empleadosr/add_prestamos/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form-prestamos">
              <?php if (count($prestamos) > 0) { ?>
                <input type="hidden" name="prestamos_existentes" value="1" id="prestamos-existentes">
              <?php } ?>

                <div class="row-fluid" style="text-align: center;">
                  <div class="span12">
                    <button type="button" class="btn btn-success" id="btn-add-prestamo">Agregar Prestamo</button>
                  </div>
                </div>
                <br>
                <div class="row-fluid">
                  <div class="span12">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-prestamos">
                      <thead>
                        <tr>
                          <th>Fecha</th>
                          <th>Cantidad</th>
                          <th>Pago semana</th>
                          <th>Fecha inicio pagos</th>
                          <th>Pausar</th>
                          <th>Eliminar</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($prestamos as $key => $prestamo) { ?>
                          <tr>
                            <td style="width: 200px;"><input type="text" name="fecha[]" value="<?php echo $prestamo->fecha ?>" class="span12" readonly> </td>
                            <td style="width: 100px;"><input type="text" name="cantidad[]" value="<?php echo $prestamo->prestado ?>" class="span12 vpositive cantidad" required></td>
                            <td style="width: 100px;"><input type="text" name="pago_semana[]" value="<?php echo $prestamo->pago_semana ?>" class="span12 vpositive pago-semana" required></td>
                            <td style="width: 200px;"><input type="date" name="fecha_inicia_pagar[]" value="<?php echo $prestamo->inicio_pago ?>" class="span12 vpositive" required></td>
                            <td style="width: 100px;">
                              <input type="hidden" name="id_prestamo[]" value="<?php echo $prestamo->id_prestamo; ?>">
                              <select name="pausarp[]" required style="width: 100px;">
                                <option value="f" <?php echo set_select('pausarp', 'f', false, $prestamo->pausado); ?>>Activo</option>
                                <option value="t" <?php echo set_select('pausarp', 't', false, $prestamo->pausado); ?>>Pausado</option>
                              </select></td>
                            <td>
                              <input type="checkbox" name="eliminar_prestamo[]" value="<?php echo $prestamo->id_prestamo; ?>">
                            </td>
                          </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success" id="btn-guardar-prestamos">Guardar</button>
                </div><!--/modal-footer -->
            </form><!--/form-horizontal -->
          </div><!--/tab-pane -->
      </div><!--/tab-content -->
    </div><!--/modal-body -->

    </div>

<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){

    <?php if (isset($close)) {?>
        setInterval(function() {
          window.parent.$('#supermodal').modal('hide');
      }, 2000);
    <?php }?>

    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->

  </body>
</html>