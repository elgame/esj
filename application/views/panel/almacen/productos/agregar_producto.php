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
  var base_url = "<?php echo base_url();?>",
      base_url_bascula = "<?php echo $this->config->item('base_url_bascula');?>",
      base_url_cam_salida_snapshot = "<?php echo $this->config->item('base_url_cam_salida_snapshot') ?> ";
</script>
</head>
<body>
  <div id="content" class="container-fluid">
    <div class="row-fluid">
      <!--[if lt IE 7]>
        <div class="alert alert-info">
          <button type="button" class="close" data-dismiss="alert">×</button>
          <p>Usted está usando un navegador desactualizado. <a href="http://browsehappy.com/">Actualice su navegador</a> o <a href="http://www.google.com/chromeframe/?redirect=true">instale Google Chrome Frame</a> para experimentar mejor este sitio.</p>
        </div>
      <![endif]-->
    <div id="content" class="span12">
      <!-- content starts -->


      <div class="row-fluid">

        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-plus"></i> Agregar Producto</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">

            <form action="<?php echo base_url('panel/productos/agregar/?'.String::getVarsLink(array('msg', 'fstatus'))); ?>" method="post" class="form-horizontal">

              <div class="span6">
                <div class="control-group">
                  <label class="control-label" for="fcodigo">Codigo </label>
                  <div class="controls">
                    <input type="text" name="fcodigo" value="<?php echo set_value('fcodigo', $folio) ?>" id="fcodigo" class="span12" axlength="25" placeholder="Codigo" required>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fnombre">Nombre </label>
                  <div class="controls">
                    <input type="text" name="fnombre" id="fnombre" class="span12" maxlength="90"
                    value="<?php echo set_value('fnombre'); ?>" required placeholder="Nombre del producto" autofocus>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="funidad">Unidad medida </label>
                  <div class="controls">
                    <select name="funidad" id="funidad" class="span12" required>
                  <?php foreach ($unidades['unidades'] as $key => $value)
                  { ?>
                      <option value="<?php echo $value->id_unidad; ?>"><?php echo $value->nombre; ?></option>
                  <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="span6">
                <div class="control-group">
                  <label class="control-label" for="fstock_min">Stock min </label>
                  <div class="controls">
                    <input type="text" name="fstock_min" id="fstock_min" class="span12 vpositive" maxlength="40"
                    value="<?php echo set_value('fstock_min'); ?>" placeholder="Stock min">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ubicacion">Ubicacion </label>
                  <div class="controls">
                    <input type="text" name="ubicacion" id="ubicacion" class="span12" maxlength="70"
                    value="<?php echo set_value('ubicacion'); ?>" placeholder="Ubicacion del producto">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fieps">IEPS (%) </label>
                  <div class="controls">
                    <input type="text" name="fieps" id="fieps" class="span12 vpositive"
                    value="<?php echo set_value('fieps', 0) ?>" placeholder="Porcentaje: 4, 10, 15, etc">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="cuenta_contpaq"><strong>Cuenta contpaq</strong> </label>
                  <div class="controls">
                    <input type="text" name="cuenta_contpaq" id="cuenta_contpaq" class="span12" maxlength="12"
                    value="<?php echo set_value('cuenta_contpaq'); ?>" placeholder="Cuenta afectable contpaq">
                  </div>
                </div>
              </div>

              <div class="row-fluid">
                <a href="#" onclick="productos.add(); return false;" title="Agregar Presentacion">Agregar Presentacion</a>
                <table class="table table-condensed">
                  <thead>
                    <tr>
                      <th>Nombre</th>
                      <th>Cantidad</th>
                      <th>Opc</th>
                    </tr>
                  </thead>
                  <tbody id="tblproductosrow">
              <?php
              if (is_array($this->input->post('pnombre')))
              {
                foreach ($this->input->post('pnombre') as $key => $value)
                {
                  if ($value != '')
                  {
              ?>
                    <tr class="rowprod">
                      <td><input type="text" name="pnombre[]" value="<?php echo $value; ?>" class="span12 presnombre" placeholder="Presentacion">
                        <input type="hidden" name="pidpresentacion[]" value=""></td>
                      <td><input type="text" name="pcantidad[]" value="<?php echo $_POST['pcantidad'][$key]; ?>" class="span12 prescantidad vpositive" placeholder="Cantidad"></td>
                      <td><a class="btn btn-danger" href="#" onclick="productos.quitar(this); return false;" title="Quitar">
                        <i class="icon-remove icon-white"></i> <span class="hide">Quitar</span></a></td>
                    </tr>
              <?php
                  }
                }
              } ?>
                    <tr class="rowprod">
                      <td><input type="text" name="pnombre[]" class="span12 presnombre" placeholder="Presentacion">
                        <input type="hidden" name="pidpresentacion[]" value=""></td>
                      <td><input type="text" name="pcantidad[]" class="span12 prescantidad vpositive" placeholder="Cantidad"></td>
                      <td><a class="btn btn-danger" href="#" onclick="productos.quitar(this); return false;" title="Quitar">
                        <i class="icon-remove icon-white"></i> <span class="hide">Quitar</span></a></td>
                    </tr>
                  </tbody>
                </table>
              </div>


              <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="javascript:closemodal();void(0);" class="btn">Cancelar</a>
              </div>
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
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>

<script type="text/javascript" charset="UTF-8">
  function closemodal(){
    window.parent.$('#supermodal').modal('hide');

    var pag = parseInt(window.parent.$('#content_productos .pagination li.active a').text())-1;
    window.parent.productos.page(pag);
  }

  <?php if ($closeModal) { ?>
    $(document).ready(function(){
      setTimeout(closemodal, 1000);
    });
  <?php } ?>
</script>

<!-- Bloque de alertas -->


</div><!--/fluid-row-->

</div><!--/.fluid-container-->

  <div class="clear"></div>

</body>
</html>