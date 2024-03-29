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
        <h2><i class="icon-plus"></i> Modificar</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/banco/modificar_movimiento?'.MyString::getVarsLink(array())); ?>" method="post" id="form">

          <div class="row-fluid">
            <div class="span12">

              <div class="control-group">
                <label class="control-label" for="dfecha">Fecha</label>
                <div class="controls">
                  <input type="date" name="dfecha" class="span6" id="dfecha" value="<?php echo set_value('dfecha', substr($mov->fecha, 0, 10) ); ?>" autofocus required>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fbanco">Banco </label>
                <div class="controls">
                  <select name="fbanco" id="fbanco" required>
              <?php  foreach ($bancos['bancos'] as $key => $value) {
              ?>
                    <option value="<?php echo $value->id_banco ?>" <?php echo set_select('fbanco', $value->id_banco, false, $mov->id_banco); ?>><?php echo $value->nombre; ?></option>
              <?php
              }?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fcuenta">Cuenta Bancaria</label>
                <div class="controls">
                  <select name="fcuenta" id="fcuenta" required>
                <?php
                foreach ($cuentas['cuentas'] as $key => $value) {
                ?>
                    <option value="<?php echo $value->id_cuenta; ?>" <?php echo set_select('fcuenta', $value->id_cuenta, false, $mov->id_cuenta); ?>><?php echo $value->alias.' - '.MyString::formatoNumero($value->saldo); ?></option>
                <?php
                }
                ?>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="dconcepto">Concepto</label>
                <div class="controls">
                  <input type="text" name="dconcepto" class="span12" id="dconcepto" value="<?php echo isset($mov->concepto)? $mov->concepto: ''; ?>" maxlength="120">
                </div>
              </div>

              <input type="hidden" name="es_ligado" value="<?php echo $mov->es_ligado; ?>">
          <?php if ($mov->es_ligado == 0)
          { ?>
              <div class="control-group">
                <label class="control-label" for="dempresa">Empresa</label>
                <div class="controls">
                  <input type="text" name="dempresa" class="span12" id="dempresa" value="<?php echo isset($empresa['info']->nombre_fiscal)? $empresa['info']->nombre_fiscal: ''; ?>" disabled>
                  <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo isset($empresa['info']->id_empresa)? $empresa['info']->id_empresa: ''; ?>">
                </div>
              </div>

            <?php if(isset($proveedor['info']->nombre_fiscal)){ ?>
              <div class="control-group">
                <label class="control-label" for="dproveedor">Proveedor</label>
                <div class="controls">
                  <input type="text" name="dproveedor" class="span12" id="dproveedor" value="<?php echo set_value('dproveedor', (isset($proveedor['info']->nombre_fiscal)? $proveedor['info']->nombre_fiscal: '')); ?>">
                  <input type="hidden" name="did_proveedor" id="did_proveedor" value="<?php echo set_value('did_proveedor', (isset($proveedor['info']->id_proveedor)? $proveedor['info']->id_proveedor: '')); ?>">
                </div>
              </div>
            <?php } ?>

            <?php if(isset($cliente['info']->nombre_fiscal)){ ?>
              <div class="control-group">
                <label class="control-label" for="dcliente">Cliente</label>
                <div class="controls">
                  <input type="text" name="dcliente" class="span5" id="dcliente" value="<?php echo set_value('dcliente', (isset($cliente['info']->nombre_fiscal)? $cliente['info']->nombre_fiscal: '')); ?>">
                  <input type="hidden" name="did_cliente" id="did_cliente" value="<?php echo set_value('did_cliente', (isset($cliente['info']->id_cliente)? $cliente['info']->id_cliente: '')); ?>">
                </div>
              </div>
            <?php } ?>

              <div class="control-group">
                <label class="control-label" for="dcuenta_cpi">Cuenta Contpaq</label>
                <div class="controls">
                  <input type="text" name="dcuenta_cpi" class="span12" id="dcuenta_cpi" value="<?php echo set_value('dcuenta_cpi', (isset($cuenta_cpi['info']->cuenta)? $cuenta_cpi['info']->nombre.' - '.$cuenta_cpi['info']->cuenta: '')); ?>">
                  <input type="hidden" name="did_cuentacpi" id="did_cuentacpi" value="<?php echo set_value('did_cuentacpi', (isset($cuenta_cpi['info']->cuenta)? $cuenta_cpi['info']->cuenta: '')); ?>">
                </div>
              </div>
          <?php
          } ?>

            <input type="hidden" name="tipo_mov" value="<?php echo $mov->tipo ?>">
            <?php if ($mov->tipo == 'f'): ?>
              <div class="row-fluid" id="groupCatalogos">  <!-- Box catalogos-->
                <div class="box span12">
                  <div class="box-header well" data-original-title>
                    <h2><i class="icon-truck"></i> Poliza</h2>
                    <div class="box-icon">
                      <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                    </div>
                  </div><!--/box-header -->
                  <div class="box-content">
                    <div class="row-fluid">
                      <div class="control-group">
                        <div class="controls span12">
                          <a class="btn btn-success" href="<?php echo base_url('panel/gastos/verXml/?ide='.$empresa['info']->id_empresa.'&idp='.(!empty($proveedor['info']->id_proveedor)? $proveedor['info']->id_proveedor: '').'&vmetodoPago=pue') ?>"
                            data-href="<?php echo base_url('panel/gastos/verXml/') ?>"
                            rel="superbox-80x550" title="Buscar" id="supermodalBtn">
                            <i class="icon-eye-open icon-white"></i> <span class="hidden-tablet">Buscar XML</span></a>
                          <span>
                            UUID: <input type="text" name="uuid" value="<?php echo $mov->uuid ?>" id="buscarUuid"> |
                            No Certificado: <input type="text" name="noCertificado" value="<?php echo $mov->no_certificado ?>" id="buscarNoCertificado">
                          </span>
                        </div>
                      </div>
                    </div>

                   </div> <!-- /box-body -->
                </div> <!-- /box -->
              </div><!-- /row-fluid -->

              <div class="row-fluid" id="groupCatalogos">  <!-- Box catalogos-->
                <div class="box span12">
                  <div class="box-header well" data-original-title>
                    <h2><i class="icon-truck"></i> Catálogos</h2>
                    <div class="box-icon">
                      <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                    </div>
                  </div><!--/box-header -->
                  <div class="box-content">
                    <div class="row-fluid">
                      <div class="span6">
                        <div class="control-group" id="cultivosGrup">
                          <label class="control-label" for="area">Cultivo </label>
                          <div class="controls">
                            <div class="input-append span12">
                              <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area', isset($mov->area->nombre) ? $mov->area->nombre : '') ?>" placeholder="Limon, Piña">
                            </div>
                            <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId', isset($mov->area->id_area) ? $mov->area->id_area : '') ?>">
                          </div>
                        </div><!--/control-group -->

                        <div class="control-group" id="ranchosGrup">
                          <label class="control-label" for="rancho">Rancho </label>
                          <div class="controls">
                            <div class="input-append span12">
                              <input type="text" name="rancho" class="span11" id="rancho" value="<?php echo set_value('rancho', isset($mov->rancho->nombre) ? $mov->rancho->nombre : '') ?>" placeholder="Milagro A, Linea 1">
                            </div>
                            <input type="hidden" name="ranchoId" id="ranchoId" value="<?php echo set_value('ranchoId', isset($mov->rancho->id_rancho) ? $mov->rancho->id_rancho : '') ?>">
                          </div>
                        </div><!--/control-group -->
                      </div>

                      <div class="span6">
                        <div class="control-group" id="centrosCostosGrup">
                          <label class="control-label" for="centroCosto">Centro de costo </label>
                          <div class="controls">
                            <div class="input-append span12">
                              <input type="text" name="centroCosto" class="span11" id="centroCosto" value="<?php echo set_value('centroCosto', isset($mov->centroCosto->nombre) ? $mov->centroCosto->nombre : '') ?>" placeholder="Mantenimiento, Gasto general">
                            </div>
                            <input type="hidden" name="centroCostoId" id="centroCostoId" value="<?php echo set_value('centroCostoId', isset($mov->centroCosto->id_centro_costo) ? $mov->centroCosto->id_centro_costo : '') ?>">
                          </div>
                        </div><!--/control-group -->

                        <div class="control-group" id="activosGrup">
                          <label class="control-label" for="activos">Activos </label>
                          <div class="controls">
                            <div class="input-append span12">
                              <input type="text" name="activos" class="span11" id="activos" value="<?php echo set_value('activos', isset($mov->activo->nombre) ? $mov->activo->nombre : '') ?>" placeholder="Nissan FRX, Maquina limon">
                            </div>
                            <input type="hidden" name="activoId" id="activoId" value="<?php echo set_value('activoId', isset($mov->activo->id_producto) ? $mov->activo->id_producto : '') ?>">
                          </div>
                        </div><!--/control-group -->
                      </div>

                    </div>

                   </div> <!-- /box-body -->
                </div> <!-- /box -->
              </div><!-- /row-fluid -->
            <?php endif ?>

            </div>
            <button type="submit" class="btn btn-success btn-large">Guardar</button>
          </div><!--/row-->

        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->

</div>

<!-- Bloque de alertas -->
<?php
if(isset($frm_errors)){
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

  <?php if ($closeModal) { ?>
    <script>
    $(function(){
      setTimeout(function() {
  <?php
      if (isset($id_movimiento{0}))
        echo "window.parent.abonom.openCheque({$id_movimiento});";
  ?>

        window.parent.$('#supermodal').modal('hide');
        window.parent.location = window.parent.location;
      }, 1000);
    });
    </script>
  <?php } ?>

  </body>
</html>