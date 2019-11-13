    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Compras
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-shopping-cart"></i> Compras</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
              <form class="form-horizontal" action="<?php echo base_url('panel/bascula/facturas_agregar/?'.String::getVarsLink(array('msg', 'rel'))); ?>" method="POST" enctype="multipart/form-data">
                    <div class="row-fluid">
                      <div class="span4">
                        <div class="control-group">
                          <div class="controls span9">
                            Empresa <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $empresa->nombre_fiscal) ?>" required autofocus>
                            <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $empresa->id_empresa) ?>">
                          </div>
                        </div>
                      </div>
                      <div class="span4">
                        <div class="control-group">
                          <div class="controls span9">
                            Proveedor <input type="text" name="proveedor" class="span10" id="proveedor" value="" placeholder="" required>
                            <input type="hidden" name="proveedorId" id="proveedorId" value="">
                          </div>
                        </div>
                      </div>
                      <div class="span4">
                        <div class="control-group">
                          <div class="controls span9">
                            Fecha <input type="datetime-local" name="fecha" class="span12" id="fecha" value="<?php echo set_value('fecha', $fecha); ?>" required>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="row-fluid">
                      <div class="span4">
                        <div class="control-group">
                          <div class="controls span9">
                            Serie <input type="text" name="serie" class="span12" id="serie" value="<?php echo set_value('serie'); ?>">
                          </div>
                        </div>
                      </div>
                      <div class="span4">
                        <div class="control-group">
                          <div class="controls span9">
                            Folio<input type="text" name="folio" class="span12" id="folio" value="<?php echo set_value('folio'); ?>" vnumeric required>
                          </div>
                        </div>
                      </div>
                      <div class="span4">
                        <div class="control-group">
                          <div class="controls span9">
                            XML<input type="file" name="xml" class="span12" id="xml" data-uniform="false" accept="text/xml">
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="row-fluid">
                      <?php //if ( ! $compra['info']->xml){ ?>
                        <div class="span4">
                          <div class="control-group">
                            <div class="controls span9">
                              <input type="hidden" name="aux" value="1">
                              <button type="submit" class="btn btn-success btn-large btn-block pull-right" style="width:100%;">Guardar</button>
                            </div>
                          </div>
                        </div>
                      <?php //} ?>
                    </div>

                    <div class="row-fluid">  <!-- Box Productos -->
                      <div class="box span12">
                        <div class="box-header well" data-original-title>
                          <h2><i class="icon-barcode"></i> Boletas</h2>
                          <div class="box-icon">
                            <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                          </div>
                        </div><!--/box-header -->
                        <div class="box-content">
                          <div class="row-fluid">
                            <div class="span12">
                              <select name="ptipo" class="span4 pull-left" id="ptipo">
                                <option value="en" <?php $set_select=set_select('ptipo', 'en', false, $this->input->post('ptipo')); echo $set_select; ?>>Entrada</option>
                                <option value="sa" <?php $set_select=set_select('ptipo', 'sa', false, $this->input->post('ptipo')); echo $set_select; ?>>Salida</option>
                              </select>

                              <select name="parea" class="span4 pull-left" id="parea">
                                <option value=""></option>
                                <?php foreach ($areas['areas'] as $area){ ?>
                                  <option value="<?php echo $area->id_area ?>" data-tipo="<?php echo $area->tipo; ?>"
                                    <?php $set_select=set_select('parea', $area->id_area, false, isset($_POST['parea']) ? $_POST['parea'] : ($area->predeterminado == 't' ? $area->id_area: '') );
                                     echo $set_select; ?>><?php echo $area->nombre ?></option>
                                <?php } ?>
                              </select>
                              <input type="text" name="pfolio" id="pfolio" value="" class="span3 pull-left" placeholder="Folio">
                              <button type="button" class="btn pull-left" id="btnAgregarBoleta">Agregar</button>
                            </div>
                            <div class="span12 mquit">
                              <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                                <thead>
                                  <tr>
                                    <th>FECHA</th>
                                    <th>FOLIO</th>
                                    <th>IMPORTE</th>
                                    <th></th>
                                  </tr>
                                </thead>
                                <tbody id="boletasList">

                                </tbody>
                              </table>
                            </div>
                          </div>
                         </div> <!-- /box-body -->
                      </div> <!-- /box -->
                    </div><!-- /row-fluid -->

                    <div class="row-fluid">
                      <div class="span12">
                        <table class="table">
                          <thead>
                            <tr>
                              <th style="background-color:#FFF !important;">TOTALES</th>
                              <th style="background-color:#FFF !important;"></th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td><em>Subtotal</em></td>
                              <td id="importe-format">
                                <input type="text" name="totalImporte" id="totalImporte" class="vnumeric" value="0">
                              </td>
                            </tr>
                            <tr>
                              <td>IVA</td>
                              <td id="traslado-format">
                                <input type="text" name="totalImpuestosTrasladados" id="totalImpuestosTrasladados" class="vnumeric" value="0">
                              </td>
                            </tr>
                            <tr style="font-weight:bold;font-size:1.2em;">
                              <td>TOTAL</td>
                              <td id="total-format">
                                <input type="text" name="totalOrden" id="totalOrden" class="vnumeric" value="0">
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
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
<!-- Bloque de alertas -->
