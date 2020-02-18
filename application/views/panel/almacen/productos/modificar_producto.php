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
            <h2><i class="icon-edit"></i> Modificar Producto</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">

            <form action="<?php echo base_url('panel/productos/modificar/?'.MyString::getVarsLink(array('msg', 'fstatus'))); ?>" method="post" class="form-horizontal">

              <div class="span6">
                <div class="control-group">
                  <label class="control-label" for="ffamilia">Familia </label>
                  <div class="controls">
                    <select name="ffamilia" id="ffamilia" class="span12" required>
                  <?php foreach ($familias['familias'] as $key => $value)
                  { ?>
                      <option value="<?php echo $value->id_familia; ?>" <?php echo set_select('ffamilia', $value->id_familia, false, (isset($data['info']->id_familia)? $data['info']->id_familia: '')); ?>><?php echo $value->nombre; ?></option>
                  <?php } ?>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fcodigo">Codigo
                    <?php echo ($data['familia']->tipo == 'a'? '<br>RFC(3)-FOLIO-FECHA(YYMMDD)': '') ?>
                  </label>
                  <div class="controls">
                    <input type="text" name="fcodigo" value="<?php echo set_value('fcodigo', (isset($data['info']->codigo)? $data['info']->codigo: '')) ?>" id="fcodigo" class="span12" axlength="25" placeholder="Codigo" required autofocus>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fnombre">Nombre </label>
                  <div class="controls">
                    <input type="text" name="fnombre" id="fnombre" class="span12" maxlength="90"
                    value="<?php echo set_value('fnombre', (isset($data['info']->nombre)? $data['info']->nombre: '')) ?>" required placeholder="Nombre del producto">

                    <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo $data['familia']->id_empresa ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="funidad">Unidad medida </label>
                  <div class="controls">
                    <select name="funidad" id="funidad" class="span12" required>
                  <?php foreach ($unidades['unidades'] as $key => $value)
                  { ?>
                      <option value="<?php echo $value->id_unidad; ?>" <?php echo set_select('funidad', $value->id_unidad, false, (isset($data['info']->id_unidad)? $data['info']->id_unidad: '')); ?>><?php echo $value->nombre; ?></option>
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
                    value="<?php echo set_value('fstock_min', (isset($data['info']->stock_min)? $data['info']->stock_min: '')) ?>" placeholder="Stock min">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ubicacion">Ubicacion </label>
                  <div class="controls">
                    <input type="text" name="ubicacion" id="ubicacion" class="span12" maxlength="70"
                    value="<?php echo set_value('ubicacion', (isset($data['info']->ubicacion)? $data['info']->ubicacion: '')) ?>" placeholder="Ubicacion del producto">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fieps">IEPS (%) </label>
                  <div class="controls">
                    <input type="text" name="fieps" id="fieps" class="span12 vpositive"
                    value="<?php echo set_value('fieps', (isset($data['info']->ieps)? $data['info']->ieps: 0)) ?>" placeholder="Porcentaje: 4, 10, 15, etc">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="cuenta_contpaq"><strong>Cuenta contpaq</strong> </label>
                  <div class="controls">
                    <input type="text" name="cuenta_contpaq" id="cuenta_contpaq" class="span12" maxlength="12"
                    value="<?php echo set_value('fcodigo', (isset($data['info']->cuenta_cpi)? $data['info']->cuenta_cpi: '')) ?>" placeholder="Cuenta afectable contpaq">
                  </div>
                </div>

              </div>

              <div class="span6">
              <?php if ($data['familia']->id_empresa != 2): ?> <!-- Empresa Agro 20 -->
                <div class="control-group">
                  <label class="control-label" for="ftipo">Tipo lista</label>
                  <div class="controls">
                    <select name="ftipo" id="ftipo" class="span12">
                      <option value=""></option>
                      <option value="v" <?php echo set_select('ftipo', 'v', false, (isset($data['info']->tipo)? $data['info']->tipo: '')); ?>>Verde (Orgánico)</option>
                      <option value="a" <?php echo set_select('ftipo', 'a', false, (isset($data['info']->tipo)? $data['info']->tipo: '')); ?>>Amarillo (Orgánico Opc)</option>
                      <option value="r" <?php echo set_select('ftipo', 'r', false, (isset($data['info']->tipo)? $data['info']->tipo: '')); ?>>Rojo (No Orgánico)</option>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="ftipo_apli">Tipo</label>
                  <div class="controls">
                    <select name="ftipo_apli" id="ftipo_apli" class="span12">
                      <option value=""></option>
                      <option value="n" <?php echo set_select('ftipo_apli', 'n', false, (isset($data['info']->tipo_apli)? $data['info']->tipo_apli: '')); ?>>Nutrición</option>
                      <option value="fs" <?php echo set_select('ftipo_apli', 'fs', false, (isset($data['info']->tipo_apli)? $data['info']->tipo_apli: '')); ?>>Fito sanidad</option>
                    </select>
                  </div>
                </div>
              <?php endif ?>

                <?php if ($data['familia']->tipo == 'a'): ?>
                <div class="control-group">
                  <label class="control-label" for="ftipo_activo">Tipo activo</label>
                  <div class="controls">
                    <select name="ftipo_activo" id="ftipo_activo" class="span12">
                      <option value=""></option>
                      <option value="et" <?php echo set_select('ftipo_activo', 'et', false, (isset($data['info']->tipo_activo)? $data['info']->tipo_activo: '')); ?>>Equipo De Transporte</option>
                      <option value="ec" <?php echo set_select('ftipo_activo', 'ec', false, (isset($data['info']->tipo_activo)? $data['info']->tipo_activo: '')); ?>>Equipo De Computo</option>
                      <option value="meo" <?php echo set_select('ftipo_activo', 'meo', false, (isset($data['info']->tipo_activo)? $data['info']->tipo_activo: '')); ?>>Mobiliario Y Equipo De Oficina</option>
                      <option value="me" <?php echo set_select('ftipo_activo', 'me', false, (isset($data['info']->tipo_activo)? $data['info']->tipo_activo: '')); ?>>Maquinaria Y Equipo</option>
                      <option value="ec" <?php echo set_select('ftipo_activo', 'ec', false, (isset($data['info']->tipo_activo)? $data['info']->tipo_activo: '')); ?>>Edificios Y Construcciones</option>
                      <option value="t" <?php echo set_select('ftipo_activo', 't', false, (isset($data['info']->tipo_activo)? $data['info']->tipo_activo: '')); ?>>Terrenos</option>
                      <option value="ia" <?php echo set_select('ftipo_activo', 'ia', false, (isset($data['info']->tipo_activo)? $data['info']->tipo_activo: '')); ?>>Inversiones Agrícolas</option>
                      <option value="gpo" <?php echo set_select('ftipo_activo', 'gpo', false, (isset($data['info']->tipo_activo)? $data['info']->tipo_activo: '')); ?>>Gastos Pre-operativos</option>
                    </select>
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="fmonto">Monto</label>
                  <div class="controls">
                    <input type="text" name="fmonto" id="fmonto" class="span12" maxlength="12"
                        value="<?php echo set_value('fmonto', (isset($data['info']->monto)? $data['info']->monto: '')) ?>" placeholder="Monto">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fdescripcion">Descripción</label>
                  <div class="controls">
                    <textarea name="fdescripcion" class="span12" rows="4"><?php echo set_value('fdescripcion', (isset($data['info']->descripcion)? $data['info']->descripcion: '')); ?></textarea>
                  </div>
                </div>
                <?php endif ?>
              </div>

              <input type="hidden" name="tipo_familia" value="<?php echo $data['familia']->tipo ?>">


              <?php if ($data['familia']->id_empresa == 2 && $data['familia']->tipo == 'p'): ?> <!-- Empresa Agro 20 -->
              <div class="row-fluid">
                <h4 style="background-color: #ccc;">Colores de productos</h4>
                <div class="row-fluid">
                  <div style="width: 49%; float: left;"> Empresa
                    <input type="text" id="pcolorsEmpresa" value="" placeholder="Empresa" class="span12">
                    <input type="hidden" id="pcolorsEmpresaId" value="">
                  </div>

                  <div style="width: 49%; float: right;"> Color
                    <select id="pcolorColor" class="span12">
                      <option value="">Selecciona Color</option>
                      <option value="v">Verde (Orgánico)</option>
                      <option value="a">Amarillo (Orgánico Opc)</option>
                      <option value="r">Rojo (No Orgánico)</option>
                    </select>
                  </div>

                  <div style="width: 55%; float: left;">Tipo aplicacion
                    <select name="pcolorTipoApl" id="pcolorTipoApl" class="span12">
                      <option value="">Selecciona Aplicación</option>
                      <option value="n">Nutrición</option>
                      <option value="fs">Fito sanidad</option>
                    </select>
                  </div>

                  <div style="width: 35%; float: right;">
                    <button type="button" class="btn" onclick="colores.add();" style="margin-top: 15px;">Agregar Color</button>
                  </div>
                </div>

                <table class="table table-condensed">
                  <thead>
                    <tr>
                      <th>Empresa</th>
                      <th>Color</th>
                      <th>Tipo</th>
                      <th>Opc</th>
                    </tr>
                  </thead>
                  <tbody id="tblColorRow">
                    <?php
                    if (is_array($data['colores']))
                    {
                      $varColores = [
                        'v' => 'Verde (Orgánico)',
                        'a' => 'Amarillo (Orgánico Opc)',
                        'r' => 'Rojo (No Orgánico)',
                      ];
                      $varTipoApli = [
                        'n' => 'Nutrición',
                        'fs' => 'Fito sanidad',
                      ];

                      foreach ($data['colores'] as $key => $value)
                      {
                    ?>
                    <tr class="rowColor">
                      <td>
                        <input type="text" name="colorEmpresa[]" value="<?php echo $value->empresa ?>" class="span12 colorEmpresa" readonly>
                        <input type="hidden" name="colorEmpresaId[]" value="<?php echo $value->id_empresa ?>" class="colorEmpresaId">
                      </td>
                      <td style="width: 100px;"> <?php echo $varColores[$value->color] ?>
                        <input type="text" name="colorColor[]" value="<?php echo $value->color ?>" class="span12 colorColor" readonly>
                      </td>
                      <td style="width: 100px;"> <?php echo $varTipoApli[$value->tipo_apli] ?>
                        <input type="text" name="colorTipoApli[]" value="<?php echo $value->tipo_apli ?>" class="span12 colorTipoApli" readonly>
                      </td>
                      <td style="width: 50px;">
                        <a class="btn btn-danger" href="#" onclick="colores.quitar(this); return false;" title="Quitar">
                        <i class="icon-remove icon-white"></i> <span class="hide">Quitar</span></a>
                      </td>
                    </tr>
                    <?php
                      }
                    } ?>
                  </tbody>
                </table>
              </div>
              <?php endif ?>

              <hr>

              <?php if ($data['familia']->tipo != 'a'): ?>
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
                    if(isset($data['presentaciones']))
                    {
                      foreach ($data['presentaciones'] as $key => $value)
                      {
                        if ($value != '')
                        {
                    ?>
                    <tr class="rowprod">
                      <td><input type="text" name="pnombre[]" value="<?php echo $value->nombre; ?>" class="span12 presnombre" placeholder="Presentacion">
                        <input type="hidden" name="pidpresentacion[]" value="<?php echo $value->id_presentacion; ?>"></td>
                      <td><input type="text" name="pcantidad[]" value="<?php echo $value->cantidad; ?>" class="span12 prescantidad vpositive" placeholder="Cantidad"></td>
                      <td><label for="pquitar<?php echo $value->id_presentacion; ?>">
                          <input type="checkbox" name="pquitar<?php echo $value->id_presentacion; ?>"
                            id="pquitar<?php echo $value->id_presentacion; ?>" value="si">Eliminar</label></td>
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
              <?php endif ?>

              <?php if ($data['familia']->tipo == 'a'): ?>
              <div class="row-fluid">
                <a href="#" onclick="productos.add('pz'); return false;" title="Agregar Piezas">Agregar Piezas</a>
                <table class="table table-condensed" id="tblPiezasProductos">
                  <thead>
                    <tr>
                      <th>Nombre</th>
                      <th>Cantidad</th>
                      <th>Opc</th>
                    </tr>
                  </thead>
                  <tbody id="tblproductosrow">
                    <?php
                    if(isset($data['piezas']))
                    {
                      foreach ($data['piezas'] as $key => $value)
                      {
                        if ($value != '')
                        {
                    ?>
                    <tr class="rowprod">
                      <td>
                        <input type="text" name="pnombre[]" value="<?php echo $value->nombre; ?>" class="span12 presnombre" placeholder="Productos (Partes)">
                        <input type="hidden" name="pidpresentacion[]" value="<?php echo $value->id_pieza; ?>">
                        <input type="hidden" name="pidproducto[]" value="<?php echo $value->id_producto_pieza; ?>" class="pidproducto">
                      </td>
                      <td><input type="text" name="pcantidad[]" value="<?php echo $value->cantidad; ?>" class="span12 prescantidad vpositive" placeholder="Cantidad"></td>
                      <td><label for="pquitar<?php echo $value->id_pieza; ?>">
                          <input type="checkbox" name="pquitar<?php echo $value->id_pieza; ?>"
                            id="pquitar<?php echo $value->id_pieza; ?>" value="si">Eliminar</label></td>
                    </tr>
                    <?php
                        }
                      }
                    } ?>
                    <tr class="rowprod">
                      <td><input type="text" name="pnombre[]" class="span12 presnombre" placeholder="Productos (Partes)">
                        <input type="hidden" name="pidpresentacion[]" value="">
                        <input type="hidden" name="pidproducto[]" value="" class="pidproducto">
                      </td>
                      <td><input type="text" name="pcantidad[]" class="span12 prescantidad vpositive" placeholder="Cantidad"></td>
                      <td><a class="btn btn-danger" href="#" onclick="productos.quitar(this); return false;" title="Quitar">
                        <i class="icon-remove icon-white"></i> <span class="hide">Quitar</span></a></td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <?php endif ?>

              <input type="hidden" name="fproducto" id="fproducto" value="<?php echo $this->input->get('id'); ?>">

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