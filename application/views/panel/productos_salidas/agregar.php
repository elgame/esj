<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/productos_salidas/'); ?>">Salidas de Productos</a> <span class="divider">/</span>
      </li>
      <li>Agregar</li>
    </ul>
  </div>


  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> Agregar Salida</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/productos_salidas/agregar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $empresa_default->nombre_fiscal) ?>" placeholder="" autofocus><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                  <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $empresa_default->id_empresa) ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="solicito">Solicito</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="solicito" class="span11" id="solicito" value="<?php echo set_value('solicito') ?>" placeholder="" required>
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="recibio">Recibio</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="recibio" class="span11" id="recibio" value="<?php echo set_value('recibio') ?>" placeholder="" required>
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="ftrabajador">Trabajador</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="ftrabajador" class="span11" id="ftrabajador" value="<?php echo set_value('ftrabajador') ?>" placeholder="Asignar material y/o herramienta">
                    <input type="hidden" name="fid_trabajador" class="span12" id="fid_trabajador" value="" required="">
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="id_almacen">Almacen</label>
                <div class="controls">
                  <div class="input-append span12">
                    <select name="id_almacen" class="span11">
                    <?php $default = ($this->input->post('id_almacen')>0? $this->input->post('id_almacen'): '1');
                    foreach ($almacenes['almacenes'] as $key => $value) { ?>
                      <option value="<?php echo $value->id_almacen ?>" <?php echo set_select('id_almacen', $value->id_almacen, false, $default) ?>><?php echo $value->nombre ?></option>
                    <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="tid_almacen">Transferir a:</label>
                <div class="controls">
                  <div class="input-append span12">
                    <select name="tid_almacen" class="span11">
                      <option value=""></option>
                    <?php foreach ($almacenes['almacenes'] as $key => $value) { ?>
                      <option value="<?php echo $value->id_almacen ?>" <?php echo set_select('tid_almacen', $value->id_almacen, false, $this->input->post('tid_almacen')) ?>><?php echo $value->nombre ?></option>
                    <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- <div class="control-group">
                <label class="control-label" for="empresa">Concepto </label>
                <div class="controls">
                  <div class="input-append span12">
                    <textarea name="conceptoSalida" class="span12" rows="7" maxlength="200"><?php echo set_value('conceptoSalida') ?></textarea>
                  </div>
                </div>
              </div> --><!--/control-group -->
            </div>

            <div class="span6">
              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="datetime-local" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', $fecha); ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio">Folio</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo set_value('folio', $next_folio); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <div class="controls">
                  <div class="well span9">
                      <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Guardar</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row-fluid tblproductos0" id="generalCodigo">  <!-- Box Otros datos -->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-barcode"></i> Otros datos de la salida</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content" style="display: block;">
                <div class="span6">

                  <div class="control-group">
                    <label class="control-label" for="no_receta">No receta</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="no_receta" class="span11" id="no_receta" value="<?php echo set_value('no_receta') ?>" placeholder="">
                      </div>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="etapa">Etapa</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <select name="etapa" class="span11">
                          <option value="" <?php echo set_select('etapa', '') ?>></option>
                          <option value="Preparacion de terreno" <?php echo set_select('etapa', 'Preparacion de terreno') ?>>Preparacion de terreno</option>
                          <option value="Seleccion de semillas" <?php echo set_select('etapa', 'Seleccion de semillas') ?>>Seleccion de semillas</option>
                          <option value="Siembra" <?php echo set_select('etapa', 'Siembra') ?>>Siembra</option>
                          <option value="Desarrollo de planta" <?php echo set_select('etapa', 'Desarrollo de planta') ?>>Desarrollo de planta</option>
                          <option value="Desarrollo de fruta" <?php echo set_select('etapa', 'Desarrollo de fruta') ?>>Desarrollo de fruta</option>
                          <option value="Cosecha" <?php echo set_select('etapa', 'Cosecha') ?>>Cosecha</option>
                          <option value="Empaque" <?php echo set_select('etapa', 'Empaque') ?>>Empaque</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="rancho">Rancho</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="rancho" class="span11 showCodigoAreaAuto notr" id="rancho" data-ini="371" value="<?php echo set_value('rancho') ?>" placeholder="" required>
                        <input type="hidden" name="rancho_id" value="<?php echo set_value('rancho_id') ?>" class="span12 showCodigoAreaAutoId" required>
                        <i class="ico icon-list showCodigoArea notr" data-ini="371" style="cursor:pointer"></i>
                      </div>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="centro_costo">Centro de costo</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="centro_costo" class="span11 showCodigoAreaAuto notr" id="centro_costo" value="<?php echo set_value('centro_costo') ?>" placeholder="" required>
                        <input type="hidden" name="centro_costo_id" value="<?php echo set_value('centro_costo_id') ?>" class="span12 showCodigoAreaAutoId" required>
                        <i class="ico icon-list showCodigoArea notr" style="cursor:pointer"></i>
                      </div>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="hectareas">Hectareas</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="hectareas" class="span11" id="hectareas" value="<?php echo set_value('hectareas') ?>" placeholder="">
                      </div>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="grupo">Grupo</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="grupo" class="span11" id="grupo" value="<?php echo set_value('grupo') ?>" placeholder="">
                      </div>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="no_secciones">No melgas/seccion</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="no_secciones" class="span11" id="no_secciones" value="<?php echo set_value('no_secciones') ?>" placeholder="">
                      </div>
                    </div>
                  </div>

                </div>

                <div class="span6">

                  <div class="control-group">
                    <label class="control-label" for="dias_despues_de">Dias despues de Forza/Siembra</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="dias_despues_de" class="span11" id="dias_despues_de" value="<?php echo set_value('dias_despues_de') ?>" placeholder="">
                      </div>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="metodo_aplicacion">Metodo de aplicacion</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <select name="metodo_aplicacion" class="span11">
                          <option value="" <?php echo set_select('metodo_aplicacion', '') ?>></option>
                          <option value="Spray boom" <?php echo set_select('metodo_aplicacion', 'Spray boom') ?>>Spray boom</option>
                          <option value="Tambos" <?php echo set_select('metodo_aplicacion', 'Tambos') ?>>Tambos</option>
                          <option value="Mochila" <?php echo set_select('metodo_aplicacion', 'Mochila') ?>>Mochila</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="ciclo">Ciclo</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="ciclo" class="span11" id="ciclo" value="<?php echo set_value('ciclo') ?>" placeholder="">
                      </div>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="tipo_aplicacion">Tipo de aplicacion</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <select name="tipo_aplicacion" class="span11">
                          <option value="" <?php echo set_select('tipo_aplicacion', '') ?>></option>
                          <option value="Foliar" <?php echo set_select('tipo_aplicacion', 'Foliar') ?>>Foliar</option>
                          <option value="Drench" <?php echo set_select('tipo_aplicacion', 'Drench') ?>>Drench</option>
                          <option value="Manual" <?php echo set_select('tipo_aplicacion', 'Manual') ?>>Manual</option>
                        </select>
                      </div>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="observaciones">Observaciones</label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="observaciones" class="span11" id="observaciones" value="<?php echo set_value('observaciones') ?>" placeholder="">
                      </div>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fecha_aplicacion">Fecha de aplicacion</label>
                    <div class="controls">
                      <input type="datetime-local" name="fecha_aplicacion" class="span9" id="fecha_aplicacion" value="<?php echo set_value('fecha_aplicacion', $fecha); ?>">
                    </div>
                  </div>

                </div>
              </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->

          <div class="row-fluid" id="productos">  <!-- Box Productos -->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-barcode"></i> Productos</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">

                  <div class="span12 mquit">
                    <div class="span3">
                      <!-- data-next="fcodigo" -->
                      <input type="text" class="span12" id="fcodigo" placeholder="Codigo" data-next="fcodigo">
                    </div><!--/span3s -->
                    <div class="span6">
                      <div class="input-append span12">
                        <input type="text" class="span12" id="fconcepto" placeholder="Producto / Descripción">
                        <!-- <a href="" rel="superbox-70x550" class="btn btn-info" type="button" data-rel="tooltip" data-title="Agregar Producto"><i class="icon-plus" ></i></a> -->
                      </div>
                      <input type="hidden" class="span1" id="fconceptoId">
                      <input type="hidden" class="span1" id="ftipoproducto">
                      <input type="hidden" class="span1" id="fprecio_unitario">
                    </div><!--/span3s -->
                    <div class="span1">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fcantidad" min="0.01" placeholder="Cant.">
                    </div><!--/span3s -->
                    <div class="span2">
                      <button type="button" class="btn btn-success span12" id="btnAddProd">Agregar</button>
                    </div><!--/span2 -->
                  </div><!--/span12 -->
                </div><!--/row-fluid -->
                <br>
                <div class="row-fluid">
                  <div class="span12 mquit">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                      <thead>
                        <tr>
                          <!-- <th>COD AREA <input type="checkbox" id="chkcopydatos"></th> -->
                          <th>CODIGO</th>
                          <th>PRODUCTO</th>
                          <th>CANT.</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>

                        <?php if (isset($_POST['concepto'])) {
                              foreach ($_POST['concepto'] as $key => $concepto) { ?>
                            <tr>
                              <!-- <td style="width: 60px;">
                                <input type="text" name="codigoArea[]" value="<?php echo $_POST['codigoArea'][$key] ?>" id="codigoArea" class="span12 showCodigoAreaAuto" required>
                                <input type="hidden" name="codigoAreaId[]" value="<?php echo $_POST['codigoAreaId'][$key] ?>" id="codigoAreaId" class="span12" required>
                                <input type="hidden" name="codigoCampo[]" value="<?php echo $_POST['codigoCampo'][$key] ?>" id="codigoCampo" class="span12">
                                <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                              </td> -->
                              <td style="width: 70px;">
                                <input type="hidden" name="tipoProducto[]" value="<?php echo $_POST['tipoProducto'][$key] ?>">
                                <input type="hidden" name="precioUnit[]" value="<?php echo $_POST['precioUnit'][$key] ?>">
                                <?php echo $_POST['codigo'][$key] ?>
                                <input type="hidden" name="codigo[]" value="<?php echo $_POST['codigo'][$key] ?>" class="span12">
                              </td>
                              <td>
                                  <?php echo $concepto ?>
                                  <input type="hidden" name="concepto[]" value="<?php echo $concepto ?>" id="concepto" class="span12">
                                  <input type="hidden" name="productoId[]" value="<?php echo $_POST['productoId'][$key] ?>" id="productoId" class="span12">
                              </td>
                              <td style="width: 65px;">
                                  <input type="number" step="any" name="cantidad[]" value="<?php echo $_POST['cantidad'][$key] ?>" id="cantidad" class="span12 vpositive" min="0.01">
                              </td>
                              <td style="width: 35px;"><button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button></td>
                            </tr>
                         <?php }} ?>
                      </tbody>
                    </table>
                  </div>
                </div>
               </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->
        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->


  <!-- Modal -->
  <div id="modalAreas" class="modal modal-w70 hide fade" tabindex="-1" role="dialog" aria-labelledby="modalAreasLavel" aria-hidden="true">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="modalAreasLavel">Catalogo de maquinaria, equipos e instalaciones</h3>
    </div>
    <div class="modal-body">

      <div class="row-fluid">

        <div>

      <?php foreach ($areas as $key => $value)
      { ?>
          <div class="span3" id="tblAreasDiv<?php echo $value->id_tipo ?>" style="display: none;">
            <table class="table table-hover table-condensed <?php echo ($key==0? 'tblAreasFirs': ''); ?>"
                id="tblAreas<?php echo $value->id_tipo ?>" data-id="<?php echo $value->id_tipo ?>">
              <thead>
                <tr>
                  <th style="width:10px;"></th>
                  <th>Codigo</th>
                  <th><?php echo $value->nombre ?></th>
                </tr>
              </thead>
              <tbody>
                <!-- <tr class="areaClick" data-id="" data-sig="">
                  <td><input type="radio" name="modalRadioSel" value="" data-uniform="false"></td>
                  <td>9</td>
                  <td>EMPAQUE</td>
                </tr> -->
              </tbody>
            </table>
          </div>
      <?php
      } ?>

        </div>

      </div>

    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
      <button class="btn btn-primary" id="btnModalAreasSel">Seleccionar</button>
    </div>
  </div>

</div>

<?php if (floatval($prints) > 0) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/productos_salidas/imprimir/?id=' . $prints."'") ?>, '_blank');
    win.focus();
  </script>
<?php } ?>
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