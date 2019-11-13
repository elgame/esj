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

          <form class="form-horizontal" action="<?php echo base_url('panel/caja_chica/agregar_abono_deudor?'.MyString::getVarsLink(array())); ?>" method="post" id="form">

            <div class="row-fluid">
              <div class="span12">

                <div class="control-group">
                  <label class="control-label" for="dfecha">Fecha</label>
                  <div class="controls">
                    <?php echo isset($fecha)? $fecha: '' ?>
                    <input type="hidden" name="id" value="<?php echo $this->input->get('id') ?>">
                    <input type="hidden" name="fecha" value="<?php echo $this->input->get('fecha') ?>">
                    <input type="hidden" name="no_caja" value="<?php echo $this->input->get('no_caja') ?>">
                    <input type="hidden" name="monto" value="<?php echo $this->input->get('monto') ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dmonto">Monto</label>
                  <div class="controls">
                    <input type="number" step="any" name="dmonto" class="span8 vpositive" id="dmonto" value="<?php echo set_value('dmonto', (isset($monto)? $monto: '')); ?>" min="1">
                  </div>
                </div>

              </div>

              <button type="submit" name="btnGuardarAbono" id="btnGuardarAbono" class="btn btn-success btn-large" <?php echo ($frm_errors['ico'] === 'success'? 'disabled' : '') ?>>Guardar</button>
            </div><!--/row-->

          </form>

          <?php if (count($deudor->abonos) > 0): ?>
            <h3>Abonos</h3>
            <table class="table table-striped table-bordered table-hover table-condensed" id="table-deudor">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Monto</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($deudor->abonos as $key => $abono): ?>
                  <tr>
                    <td><?php echo $abono->fecha ?></td>
                    <td><?php echo MyString::formatoNumero($abono->monto, 2, '$') ?></td>
                    <td>
                      <?php if ($this->input->get('fecha') === $abono->fecha): ?>
                      <a href="<?php echo base_url('panel/caja_chica/quitar_abono_deudor?'.MyString::getVarsLink(array('fecha_creacion'))."&fecha_creacion={$abono->fecha_creacion}"); ?>">Quitar</a>
                      <?php endif ?>
                    </td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          <?php endif ?>

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


  <?php if (isset($print_recibo)) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/cuentas_cobrar/imprimir_abono?p=' . $print_recibo."'") ?>, '_blank');
    win.focus();
  </script>
<?php } ?>

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

  </body>
</html>