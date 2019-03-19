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
      <h3>Calculo anual
        <?php if (isset($guardado) && $guardado > 0): ?>
         (Guardado)
        <?php endif ?>
      </h3>
    </div><!--/modal-header -->

    <div class="modal-body" style="max-height: none;">
      <form class="form-horizontal" action="<?php echo base_url('panel/nomina_fiscal/calc_anual/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form-bonos">
          <div class="row-fluid">
            <div class="span12">
              <table class="table table-striped table-bordered table-hover table-condensed" id="table-bonos-otros">
                <thead>
                  <tr>
                    <th>Trabajador</th>
                    <th>Gravado</th>
                    <th>ISR (+) / Subsidio (-)</th>
                  </tr>
                </thead>
                <tbody>
                <?php if (isset($calculo) && count($calculo) > 0): ?>

                  <?php
                  $total_gravado = $total_isr_sub = 0;
                  foreach ($calculo as $key => $item) {
                    $total_gravado += $item->total_gravado;
                    $total_isr_sub += $item->total_isr_sub;
                  ?>
                    <tr>
                      <td><?php echo $item->nombre.' '.$item->apellido_paterno.' '.$item->apellido_materno; ?></td>
                      <td><?php echo MyString::formatoNumero($item->total_gravado, 2, '', false) ?></td>
                      <td><?php echo MyString::formatoNumero($item->total_isr_sub, 2, '', false) ?></td>
                    </tr>
                  <?php } ?>

                  <tr style="font-weight: bold;">
                    <td>Total</td>
                    <td><?php echo MyString::formatoNumero($total_gravado, 2, '', false) ?></td>
                    <td><?php echo MyString::formatoNumero($total_isr_sub, 2, '', false) ?></td>
                  </tr>
                <?php endif ?>
                </tbody>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <?php if (isset($guardado) && $guardado == 0): ?>
            <button type="submit" name="guardar" class="btn btn-success" id="btn-guardar-bonos">Guardar calculo</button>
            <?php endif ?>
            <button type="submit" name="descargar" class="btn btn-info" id="btn-guardar-bonos">Descargar calculo</button>
          </div><!--/modal-footer -->
      </form><!--/form-horizontal -->
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
          window.parent.location.reload();
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