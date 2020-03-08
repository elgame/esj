<div id="content" class="span<?php echo isset($_GET['idf']) ? '12' : '10' ?>">

  <?php
    $titulo = 'Agregar receta';
    if (! isset($_GET['idf'])){ ?>
    <div>
      <ul class="breadcrumb">
        <li>
          <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
        </li>
        <li>
          <a href="<?php echo base_url('panel/recetas/'); ?>">Recetas</a> <span class="divider">/</span>
        </li>
        <li>Agregar</li>
      </ul>
    </div>
  <?php } ?>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-plus"></i> <?php echo $titulo; ?></h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/recetas/agregar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $empresa_default->nombre_fiscal) ?>" data-next="empresa_ap" autofocus><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                  <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $empresa_default->id_empresa) ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="empresa_ap">Empresa Aplicación </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa_ap" class="span11" id="empresa_ap" value="<?php echo set_value('empresa_ap') ?>" data-next="tipo">
                  </div>
                  <input type="hidden" name="empresaId_ap" id="empresaId_ap" value="<?php echo set_value('empresaId_ap') ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="formula">Formula </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="formula" class="span11" id="formula" value="<?php echo set_value('formula') ?>" placeholder="Selecciona una formula">
                    <input type="hidden" name="formulaId" id="formulaId" value="<?php echo set_value('formulaId') ?>">
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="area">Cultivo</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area') ?>" placeholder="Limon, Piña" required>
                  </div>
                  <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId') ?>">
                </div>
              </div>

              <div class="control-group" id="ranchosGrup">
                <label class="control-label" for="rancho">Areas / Ranchos </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="rancho" class="span11" id="rancho" value="<?php echo set_value('rancho') ?>" placeholder="Milagro A, Linea 1">
                  </div>
                </div>
                <ul class="tags" id="tagsRanchoIds">
                <?php if (isset($_POST['ranchoId'])) {
                  foreach ($_POST['ranchoId'] as $key => $ranchoId) { ?>
                    <li><span class="tag"><?php echo $_POST['ranchoText'][$key] ?></span>
                      <input type="hidden" name="ranchoId[]" class="ranchoId" value="<?php echo $ranchoId ?>">
                      <input type="hidden" name="ranchoText[]" class="ranchoText" value="<?php echo $_POST['ranchoText'][$key] ?>">
                    </li>
                 <?php }} ?>
                </ul>
              </div>

              <div class="control-group" id="centrosCostosGrup">
                <label class="control-label" for="centroCosto">Centro de costo </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="centroCosto" class="span11" id="centroCosto" value="<?php echo set_value('centroCosto') ?>" placeholder="Mantenimiento, Gasto general">
                    <a href="#modalCentrosCostos" role="button" class="btn" data-toggle="modal"><i class="icon-plus" ></i></a>
                  </div>
                </div>
                <ul class="tags" id="tagsCCIds">
                <?php if (isset($_POST['centroCostoId'])) {
                  foreach ($_POST['centroCostoId'] as $key => $centroCostoId) { ?>
                    <li>
                      <span class="tag"><?php echo $_POST['centroCostoText'][$key] ?></span>
                      <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="<?php echo $centroCostoId ?>">
                      <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="<?php echo $_POST['centroCostoText'][$key] ?>">

                      <input type="hidden" name="centroCostoHec[]" class="centroCostoHec" value="<?php echo $_POST['centroCostoHec'][$key] ?>">
                      <input type="hidden" name="centroCostoNoplantas[]" class="centroCostoNoplantas" value="<?php echo $_POST['centroCostoNoplantas'][$key] ?>">
                    </li>
                 <?php }} ?>
                </ul>
              </div>

              <div class="control-group">
                <label class="control-label" for="objetivo">Objetivo </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="objetivo" class="span11" id="objetivo" value="<?php echo set_value('objetivo') ?>">
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="solicito">Solicito</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="solicito" class="span11" id="solicito" value="<?php echo set_value('solicito') ?>" placeholder="" required>
                  </div>
                </div>
                  <input type="hidden" name="solicitoId" id="solicitoId" value="<?php echo set_value('solicitoId') ?>" required>
              </div>

              <div class="control-group">
                <label class="control-label" for="autorizo">Autoriza</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="autorizo" class="span11" id="autorizo" value="<?php echo set_value('autorizo') ?>" placeholder="" required>
                  </div>
                </div>
                  <input type="hidden" name="autorizoId" id="autorizoId" value="<?php echo set_value('autorizoId') ?>" required>
              </div>

            </div>

            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="tipo">Tipo de Orden</label>
                <div class="controls">
                  <select name="tipo" class="span9" id="tipo" data-next="formula">
                    <option value="kg" <?php echo set_select('tipo', 'kg'); ?>>Kg</option>
                    <option value="lts" <?php echo set_select('tipo', 'lts'); ?>>Lts</option>
                  </select>
                  <input type="hidden" id="tipooo" value="<?php echo (!empty($_POST['tipo'])? 'true': 'false') ?>">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio_formula">Folio Formula</label>
                <div class="controls">
                  <input type="text" name="folio_formula" class="span9" id="folio_formula" value="<?php echo set_value('folio_formula'); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio">Folio Receta</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo set_value('folio', $next_folio); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio_hoja">Folio Receta Hoja</label>
                <div class="controls">
                  <input type="text" name="folio_hoja" class="span9" id="folio_hoja" value="<?php echo set_value('folio_hoja'); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="date" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', date("Y-m-d")); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fecha_aplicacion">Fecha Aplicación</label>
                <div class="controls">
                  <input type="date" name="fecha_aplicacion" class="span9" id="fecha_aplicacion" value="<?php echo set_value('fecha_aplicacion', date("Y-m-d")); ?>" size="25">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="calendario">Calendario</label>
                <div class="controls">
                  <select name="calendario" class="span9" id="calendario" data-next="formula" required>
                  </select>
                  <input type="hidden" id="calendariooo" value="<?php echo (empty($_POST['calendario'])? '': $_POST['calendario']) ?>">
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

          <div class="row-fluid">
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> Programa de Aplicación</h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="row-fluid">
                  <div class="span3">
                    Etapa
                    <input type="text" name="a_etapa" class="span12 datosapl" id="a_etapa" value="<?php echo set_value('a_etapa'); ?>">
                  </div>

                  <div class="span3">
                    Ciclo
                    <input type="text" name="a_ciclo" class="span12 datosapl" id="a_ciclo" value="<?php echo set_value('a_ciclo'); ?>">
                  </div>

                  <div class="span3">
                    DDS
                    <input type="text" name="a_dds" class="span12 datosapl" id="a_dds" value="<?php echo set_value('a_dds'); ?>">
                  </div>

                  <div class="span3">
                    Turno
                    <input type="text" name="a_turno" class="span12 datosapl" id="a_turno" value="<?php echo set_value('a_turno'); ?>">
                  </div>
                </div>

                <div class="row-fluid">
                  <div class="span3">
                    Via
                    <input type="text" name="a_via" class="span12 datosapl" id="a_via" value="<?php echo set_value('a_via'); ?>">
                  </div>

                  <div class="span3">
                    Aplicación
                    <input type="text" name="a_aplic" class="span12 datosapl" id="a_aplic" value="<?php echo set_value('a_aplic'); ?>">
                  </div>

                  <div class="span3">
                    Equipo
                    <input type="text" name="a_equipo" class="span12 datosapl" id="a_equipo" value="<?php echo set_value('a_equipo'); ?>">
                  </div>

                  <div class="span3">
                    Observaciones
                    <input type="text" name="a_observaciones" class="span12 datosapl" id="a_observaciones" value="<?php echo set_value('a_observaciones'); ?>" data-next="dosis_planta|ha_bruta">
                  </div>
                </div>
              </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->

          <div class="row-fluid">
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-list-alt"></i> <span class="titulo-box-kglts">Datos Kg</span></h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div><!--/box-header -->
              <div class="box-content">
                <div class="span3 datos-kg">
                  Dosis Planta
                  <input type="number" step="any" name="dosis_planta" class="span12 sikey datoskl" id="dosis_planta" value="<?php echo set_value('dosis_planta'); ?>" data-next="planta_ha">
                </div>

                <div class="span3 datos-lts">
                  Ha Bruta
                  <input type="number" step="any" name="ha_bruta" class="span12 sikey datoskl" id="ha_bruta" value="<?php echo set_value('ha_bruta'); ?>" data-next="planta_ha">
                </div>

                <div class="span3">
                  Plantas x Ha
                  <input type="number" step="any" name="planta_ha" class="span12 sikey datoskl" id="planta_ha" value="<?php echo set_value('planta_ha'); ?>" data-next="ha_neta|no_plantas">
                </div>

                <div class="span3">
                  Ha Netas
                  <input type="number" step="any" name="ha_neta" class="span12 sikey datoskl" id="ha_neta" value="<?php echo set_value('ha_neta'); ?>" data-next="fconcepto">
                </div>

                <div class="span3">
                  No plantas
                  <input type="number" step="any" name="no_plantas" class="span12 sikey datoskl" id="no_plantas" value="<?php echo set_value('no_plantas'); ?>" data-next="carga1" style="display: none;">  <!-- readonly -->
                </div>

                <div class="span3 datos-kg">
                  Kg Total
                  <input type="number" step="any" name="kg_totales" class="span12 sikey datoskl" id="kg_totales" value="<?php echo set_value('kg_totales'); ?>">  <!-- readonly -->
                </div>

                <div class="span3 datos-lts">
                  Carga 1
                  <input type="number" step="any" name="carga1" class="span12 sikey datoskl" id="carga1" value="<?php echo set_value('carga1'); ?>" data-next="dosis_equipo">
                </div>

                <div class="span3 datos-lts">
                  Dosis Equipo Carga 1
                  <input type="number" step="any" name="dosis_equipo" class="span12 sikey datoskl" id="dosis_equipo" value="<?php echo set_value('dosis_equipo'); ?>" data-next="carga2">
                </div>

                <div class="span3 datos-lts">
                  Carga 2
                  <input type="number" step="any" name="carga2" class="span12 sikey datoskl" id="carga2" value="<?php echo set_value('carga2'); ?>" data-next="ph">
                </div>

                <div class="span3 datos-lts">
                  Lts de Cargas Extras
                  <input type="number" step="any" name="dosis_equipo_car2" class="span12 sikey datoskl" id="dosis_equipo_car2" value="<?php echo set_value('dosis_equipo_car2'); ?>">  <!-- readonly -->
                </div>

                <div class="span3 datos-lts">
                  PH
                  <input type="number" step="any" name="ph" class="span12 sikey datoskl" id="ph" value="<?php echo set_value('ph'); ?>" data-next="fconcepto">
                </div>
              </div> <!-- /box-body -->
            </div> <!-- /box -->
          </div><!-- /row-fluid -->


          <div class="row-fluid" id="productos">  <!-- Box Productos -->
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-barcode"></i> Productos <span id="show_info_prod" style="display:none;"><i class="icon-hand-right"></i> <span>Existencia: 443 | Stok: 43</span></span></h2>
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
                        <input type="text" class="span10" id="fconcepto" placeholder="Producto / Descripción">
                        <a href="<?php echo base_url('panel/productos').'?modal=true' ?>" rel="superbox-70x550" class="btn btn-info" type="button" data-rel="tooltip" data-title="Agregar Producto"><i class="icon-plus" ></i></a>
                      </div>
                      <input type="hidden" class="span1" id="fconceptoId">
                    </div><!--/span3s -->

                  </div><!--/span12 -->
                  <br><br>
                  <div class="span12 mquit">
                    <div class="span3">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fcantidad" min="0.01" placeholder="Cant." data-next="fprecio">
                    </div><!--/span3s -->

                    <div class="span3">
                      <input type="number" step="any" value="" class="span12 vpositive" id="fprecio" min="0.01" placeholder="Precio" data-next="btnAddProd">
                    </div><!--/span3s -->

                    <div class="span2 offset3">
                      <button type="button" class="btn btn-success span12" id="btnAddProd">Agregar</button>
                    </div><!--/span2 -->
                  </div>

                </div><!--/row-fluid -->
                <br>
                <div class="row-fluid">
                  <div class="span12 mquit">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-productos">
                      <thead>
                        <tr>
                          <th rowspan="2" style="vertical-align: middle;">%</th>
                          <th rowspan="2" style="vertical-align: middle;">PRODUCTO</th>
                          <th rowspan="2" style="vertical-align: middle;">CANT.</th>
                          <th rowspan="2" class="tipostyle" style="vertical-align: middle;display: none;">CARGA 1</th>
                          <th rowspan="2" class="tipostyle" style="vertical-align: middle;display: none;">CARGA 2</th>
                          <th rowspan="2" style="vertical-align: middle;">APLI TOTAL</th>
                          <th rowspan="2" style="vertical-align: middle;">PRECIO</th>
                          <th rowspan="2" style="vertical-align: middle;">IMPORTE</th>
                          <th rowspan="2" style="vertical-align: middle;">OPC</th>
                        </tr>
                      </thead>
                      <tbody class="bodyproducs">
                        <?php if (isset($_POST['concepto'])) {
                        foreach ($_POST['concepto'] as $key => $concepto) { ?>

                          <tr class="rowprod">
                            <td style="width: 50px;">
                              <span class="percent"><?php echo $_POST['percent'][$key] ?></span>
                              <input type="hidden" name="percent[]" value="<?php echo $_POST['percent'][$key] ?>" id="percent">
                            </td>
                            <td>
                              <?php echo $concepto ?>
                              <input type="hidden" name="concepto[]" value="<?php echo $concepto ?>" id="concepto" class="span12">
                              <input type="hidden" name="productoId[]" value="<?php echo $_POST['productoId'][$key] ?>" id="productoId" class="span12">
                            </td>
                            <td style="width: 80px;">
                              <input type="number" step="any" name="cantidad[]" value="<?php echo $_POST['cantidad'][$key] ?>" id="cantidad" class="span12 vpositive" min="0">
                            </td>
                            <td class="tipostyle" style="width: 80px;display: none;">
                                <input type="number" step="any" name="pcarga1[]" value="<?php echo $_POST['pcarga1'][$key] ?>" id="pcarga1" class="span12 vpositive" min="0"> <!-- readonly -->
                            </td>
                            <td class="tipostyle" style="width: 80px;display: none;">
                                <input type="number" step="any" name="pcarga2[]" value="<?php echo $_POST['pcarga2'][$key] ?>" id="pcarga2" class="span12 vpositive" min="0"> <!-- readonly -->
                            </td>
                            <td style="width: 130px;">
                              <input type="number" step="any" name="aplicacion_total[]" value="<?php echo $_POST['aplicacion_total'][$key] ?>" id="aplicacion_total" class="span12 vpositive" min="0"> <!-- readonly -->
                            </td>
                            <td style="width: 130px;">
                              <input type="number" step="any" name="precio[]" value="<?php echo $_POST['precio'][$key] ?>" id="precio" class="span12 vpositive" min="0">
                            </td>
                            <td style="width: 150px;">
                              <input type="number" step="any" name="importe[]" value="<?php echo $_POST['importe'][$key] ?>" id="importe" class="span12 vpositive" min="0" readonly>
                            </td>
                            <td style="width: 50px;">
                              <button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button>
                            </td>
                          </tr>
                        <?php }} ?>
                      </tbody>
                      <tfoot>
                        <tr style="font-weight: bold;">
                          <td id="ttpercent"></td>
                          <td></td>
                          <td id="ttcantidad"></td>
                          <th id="ttcargo1" class="tipostyle" style="display: none;"></th>
                          <th id="ttcargo2" class="tipostyle" style="display: none;"></th>
                          <td id="ttaplicacion_total"></td>
                          <td></td>
                          <td>
                            <span id="ttimporte"></span>
                            <input type="hidden" name="total_importe" id="total_importe" value="<?php echo set_value('ph'); ?>">
                          </td>
                          <td></td>
                        </tr>
                      </tfoot>
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

</div>

<!-- Modal -->
<div id="modalCentrosCostos" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
  aria-hidden="true" style="width: 40%;left: 45%;top: 40%;height: 300px;">
  <div class="modal-body" style="max-height: 1500px;">
    <label class="control-label" for="rangoCentrosCosto">Rango de Centros de Costo por Código</label>
    <input type="text" name="rangoCentrosCosto" id="rangoCentrosCosto" value="" placeholder="1-4,5,7-9,8">
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
    <button class="btn btn-primary" id="btnRangoCentrosCosto">Agregar</button>
  </div>
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
