<div id="content" class="span<?php echo isset($_GET['idf']) ? '12' : '10' ?>">

  <?php
    $titulo = 'Agregar formula';
    if (! isset($_GET['idf'])){ ?>
    <div>
      <ul class="breadcrumb">
        <li>
          <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
        </li>
        <li>
          <a href="<?php echo base_url('panel/recetas_formulas/'); ?>">Formulas</a> <span class="divider">/</span>
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

        <form class="form-horizontal" action="<?php echo base_url('panel/recetas_formulas/modificar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">
            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="empresa">Empresa </label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="empresa" class="span11" id="empresa" value="<?php echo set_value('empresa', $formula['info']->empresa->nombre_fiscal) ?>" placeholder="" autofocus><a href="<?php echo base_url('panel/empresas/agregar') ?>" rel="superbox-80x550" class="btn btn-info" type="button"><i class="icon-plus" ></i></a>
                  </div>
                  <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId', $formula['info']->empresa->id_empresa) ?>">
                </div>
              </div><!--/control-group -->

              <div class="control-group">
                <label class="control-label" for="nombre">Nombre</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="nombre" class="span11" id="nombre" value="<?php echo set_value('nombre', $formula['info']->nombre) ?>" placeholder="">
                  </div>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="cliente">Cultivo</label>
                <div class="controls">
                  <div class="input-append span12">
                    <input type="text" name="area" class="span11" id="area" value="<?php echo set_value('area', $formula['info']->area) ?>" placeholder="Limon, Piña">
                  </div>
                  <input type="hidden" name="areaId" id="areaId" value="<?php echo set_value('areaId', $formula['info']->id_area) ?>">
                </div>
              </div>

            </div>

            <div class="span6">

              <div class="control-group">
                <label class="control-label" for="tipo">Tipo de Orden</label>
                <div class="controls">
                  <select name="tipo" class="span9" id="tipo">
                    <option value="kg" <?php echo set_select('tipo', 'kg', ($formula['info']->tipo==='kg')); ?>>Kg</option>
                    <option value="lts" <?php echo set_select('tipo', 'lts', ($formula['info']->tipo==='lts')); ?>>Lts</option>
                  </select>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="folio">Folio</label>
                <div class="controls">
                  <input type="text" name="folio" class="span9" id="folio" value="<?php echo set_value('folio', $formula['info']->folio); ?>" size="25" readonly>
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
                      <input type="number" step="any" value="" class="span12 vpositive" id="fcantidad" min="0.01" placeholder="Cant." data-next="btnAddProd">
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
                          <th rowspan="2" style="vertical-align: middle;">CANT.</th>
                          <th rowspan="2" style="vertical-align: middle;">PRODUCTO</th>
                          <th rowspan="2" style="vertical-align: middle;">OPC</th>
                        </tr>
                      </thead>
                      <tbody class="bodyproducs">
                        <?php if (isset($formula['info']->productos)) {
                        foreach ($formula['info']->productos as $key => $prod) { ?>

                          <tr class="rowprod">
                            <td>
                              <span class="percent"><?php echo $prod->percent ?></span>
                              <input type="hidden" name="percent[]" value="<?php echo $prod->percent ?>" id="percent">
                            </td>
                            <td style="width: 65px;">
                                <input type="number" step="any" name="cantidad[]" value="<?php echo $prod->dosis_mezcla ?>" id="cantidad" class="span12 vpositive" min="0">
                            </td>
                            <td>
                              <?php echo $prod->producto ?>
                              <input type="hidden" name="concepto[]" value="<?php echo $prod->producto ?>" id="concepto" class="span12">
                              <input type="hidden" name="productoId[]" value="<?php echo $prod->id_producto ?>" id="productoId" class="span12">
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