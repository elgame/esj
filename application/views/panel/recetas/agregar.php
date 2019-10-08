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
          <a href="<?php echo base_url('panel/recetas_formulas/'); ?>">Recetas</a> <span class="divider">/</span>
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

        <form class="form-horizontal" action="<?php echo base_url('panel/recetas_formulas/agregar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $empresa_default->nombre_fiscal) ?>" data-next="tipo" autofocus><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                  <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $empresa_default->id_empresa) ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="formula">Formula </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="formula" class="span11" id="formula" value="<?php echo set_value('formula') ?>" placeholder="Selecciona una formula" required>
                    <input type="hidden" name="formulaId" id="formulaId" value="<?php echo set_value('formulaId') ?>">
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="area">Cultivo</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area') ?>" placeholder="Limon, Piña" readonly>
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
                  </div>
                </div>
                <ul class="tags" id="tagsCCIds">
                <?php if (isset($_POST['centroCostoId'])) {
                  foreach ($_POST['centroCostoId'] as $key => $centroCostoId) { ?>
                    <li><span class="tag"><?php echo $_POST['centroCostoText'][$key] ?></span>
                      <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="<?php echo $centroCostoId ?>">
                      <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="<?php echo $_POST['centroCostoText'][$key] ?>">
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

            </div>

            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="tipo">Tipo de Orden</label>
                <div class="controls">
                  <select name="tipo" class="span9" id="tipo" data-next="formula">
                    <option value="kg" <?php echo set_select('tipo', 'kg'); ?>>Kg</option>
                    <option value="lts" <?php echo set_select('tipo', 'lts'); ?>>Lts</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio_formula">Folio Formula</label>
                <div class="controls">
                  <input type="text" name="folio_formula" class="span9" id="folio_formula" value="<?php echo set_value('folio_formula'); ?>" size="25" readonly required>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio">Folio Receta</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo set_value('folio', $next_folio); ?>" size="25" readonly>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="fecha">Fecha</label>
                <div class="controls">
                  <input type="date" name="fecha" class="span9" id="fecha" value="<?php echo set_value('fecha', date("Y-m-d")); ?>" size="25" readonly>
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
                    <input type="number" name="a_etapa" class="span12 datosapl" id="a_etapa" value="<?php echo set_value('a_etapa'); ?>">
                  </div>

                  <div class="span3">
                    Ciclo
                    <input type="number" name="a_ciclo" class="span12 datosapl" id="a_ciclo" value="<?php echo set_value('a_ciclo'); ?>">
                  </div>

                  <div class="span3">
                    DDS
                    <input type="number" name="a_dds" class="span12 datosapl" id="a_dds" value="<?php echo set_value('a_dds'); ?>">
                  </div>

                  <div class="span3">
                    Turno
                    <input type="number" name="a_turno" class="span12 datosapl" id="a_turno" value="<?php echo set_value('a_turno'); ?>">
                  </div>
                </div>

                <div class="row-fluid">
                  <div class="span3">
                    Via
                    <input type="number" name="a_via" class="span12 datosapl" id="a_via" value="<?php echo set_value('a_via'); ?>">
                  </div>

                  <div class="span3">
                    Aplicación
                    <input type="number" name="a_aplic" class="span12 datosapl" id="a_aplic" value="<?php echo set_value('a_aplic'); ?>">
                  </div>

                  <div class="span3">
                    Equipo
                    <input type="number" name="a_equipo" class="span12 datosapl" id="a_equipo" value="<?php echo set_value('a_equipo'); ?>">
                  </div>

                  <div class="span3">
                    Observaciones
                    <input type="number" name="a_observaciones" class="span12 datosapl" id="a_observaciones" value="<?php echo set_value('a_observaciones'); ?>">
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
                  <input type="number" name="dosis_planta" class="span12 datoskl" id="dosis_planta" value="<?php echo set_value('dosis_planta'); ?>">
                </div>

                <div class="span3 datos-lts">
                  Ha Bruta
                  <input type="number" name="ha_bruta" class="span12 datoskl" id="ha_bruta" value="<?php echo set_value('ha_bruta'); ?>">
                </div>

                <div class="span3">
                  Plantas x Ha
                  <input type="number" name="planta_ha" class="span12 datoskl" id="planta_ha" value="<?php echo set_value('planta_ha'); ?>">
                </div>

                <div class="span3">
                  Ha Netas
                  <input type="number" name="ha_neta" class="span12 datoskl" id="ha_neta" value="<?php echo set_value('ha_neta'); ?>">
                </div>

                <div class="span3">
                  No plantas
                  <input type="number" name="no_plantas" class="span12 datoskl" id="no_plantas" value="<?php echo set_value('no_plantas'); ?>" readonly>
                </div>

                <div class="span3 datos-kg">
                  Kg Total
                  <input type="number" name="kg_totales" class="span12 datoskl" id="kg_totales" value="<?php echo set_value('kg_totales'); ?>" readonly>
                </div>

                <div class="span3 datos-lts">
                  Carga 1
                  <input type="number" name="carga1" class="span12 datoskl" id="carga1" value="<?php echo set_value('carga1'); ?>">
                </div>

                <div class="span3 datos-lts">
                  Carga 2
                  <input type="number" name="carga2" class="span12 datoskl" id="carga2" value="<?php echo set_value('carga2'); ?>">
                </div>

                <div class="span3 datos-lts">
                  PH
                  <input type="number" name="ph" class="span12 datoskl" id="ph" value="<?php echo set_value('ph'); ?>">
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
                            <td>
                              <span class="percent"><?php echo $_POST['percent'][$key] ?></span>
                              <input type="hidden" name="percent[]" value="<?php echo $_POST['percent'][$key] ?>" id="percent">
                            </td>
                            <td style="width: 65px;">
                                <input type="number" step="any" name="cantidad[]" value="<?php echo $_POST['cantidad'][$key] ?>" id="cantidad" class="span12 vpositive" min="0">
                            </td>
                            <td>
                              <?php echo $concepto ?>
                              <input type="hidden" name="concepto[]" value="<?php echo $concepto ?>" id="concepto" class="span12">
                              <input type="hidden" name="productoId[]" value="<?php echo $_POST['productoId'][$key] ?>" id="productoId" class="span12">
                            </td>
                            <td>
                              <button type="button" class="btn btn-danger" id="btnDelProd"><i class="icon-remove"></i></button>
                            </td>
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