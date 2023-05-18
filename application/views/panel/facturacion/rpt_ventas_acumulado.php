    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/facturacion/ventasAcumulado_pdf/'); ?>" method="GET" class="form-search" target="rpfReporte" id="form">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="ffecha1">Del</label>
                  <div class="controls">
                    <input type="date" name="ffecha1" class="input-medium search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m').'-01'); ?>" size="10">
                  </div>
                </div>
                <div class="control-group">
                  <label class="control-label" for="ffecha2">Al</label>
                  <div class="controls">
                    <input type="date" name="ffecha2" class="input-medium search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', date('Y-m-d')); ?>" size="10">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dempresar">Empresa</label>
                  <div class="controls">
                    <input type="text" name="dempresar" class="input-xlarge search-query" id="dempresar" value="<?php echo set_value_get('dempresar', $empresa->nombre_fiscal); ?>" size="73">
                    <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa', $empresa->id_empresa); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dserie">Serie</label>
                  <div class="controls">
                    <select name="dserie" class="span9" id="dserie">
                      <option></option>
                      <?php foreach ($series['data'] as $key => $value): ?>
                      <option value="<?php echo $value->serie ?>"><?php echo $value->serie ?> - <?php echo $value->leyenda ?></option>
                      <?php endforeach ?>
                    </select>
                    <input type="hidden" id="serie-selected" value="">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dtipoReporte">Tipo Reporte</label>
                  <div class="controls">
                    <select name="dtipoReporte" id="dtipoReporte">
                      <option value="piezas">Por unidades</option>
                      <option value="importe">Por Importes</option>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dproducto">Producto</label>
                  <!-- <div class="controls">
                    <input type="text" name="dproducto" class="input-xlarge search-query" id="dproducto" value="<?php echo set_value_get('dproducto'); ?>" size="73">
                    <input type="hidden" name="did_producto" id="did_producto" value="<?php echo set_value_get('did_producto'); ?>">
                  </div> -->
                  <div class="input-append span12">
                    <a href="#modal-productos" role="button" class="btn" data-toggle="modal">Productos</a>
                    <!-- <input type="text" name="dproducto" value="" id="dproducto" class="span9" placeholder="Buscar"> -->
                    <!-- <button class="btn" type="button" id="btnAddProducto" style="margin-left:-3px;"><i class="icon-plus-sign"></i></button> -->
                    <input type="hidden" name="did_producto" value="" id="did_producto">
                  </div>
                  <div class="clearfix"></div>
                  <div style="height:110px;overflow-y: scroll;background-color:#eee;">
                    <ul id="lista_proveedores" style="list-style: none;margin-left: 4px;">
                    </ul>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dcontiene">Contiene:</label>
                  <div class="controls">
                    <input type="text" name="dcontiene" class="input-xlarge search-query" id="dcontiene" value="<?php echo set_value_get('dcontiene'); ?>" size="73">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dtipo">Tipo:</label>
                  <div class="controls">
                    <select name="dtipo" id="dtipo">
                      <option value="f">Remisiones</option>
                      <option value="t">Facturas</option>
                      <option value="">Remisiones y facturas</option>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dcliente">Cliente</label>
                  <div class="controls">
                    <input type="text" name="dcliente" class="input-xlarge search-query" id="dcliente" value="<?php echo set_value_get('dcliente'); ?>" size="73">
                    <input type="hidden" name="fid_cliente" id="fid_cliente" value="<?php echo set_value_get('fid_cliente'); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dpagadas">Pagadas</label>
                  <div class="controls">
                    <input type="checkbox" name="dpagadas" value="1">
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" id="btn_submit" class="btn btn-primary btn-large span12">Enviar</button>
                </div>

                <!-- Modal -->
                <div id="modal-productos" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modal-productos" aria-hidden="true">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 id="myModalLabel">Productos</h3>
                  </div>
                  <div class="modal-body">
                    <form id="frmproductos" class="form-inline" onsubmit="return false;">
                      <input id="txtbuscar" type="text" class="input-small" placeholder="Buscar">
                    </form>
                    <table id="tblProductos" class="table table-condensed">
                      <thead>
                        <tr>
                          <th></th>
                          <th>Producto</th>
                        </tr>
                      </thead>
                      <tbody>

                      </tbody>
                    </table>
                  </div>
                  <div class="modal-footer">
                    <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true" id="addProductos">Agregar</button>
                    <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
                  </div>
                </div>

              </div>
            </form> <!-- /form -->
          </div>
        </div><!--/span12 -->
      </div><!--/row-fluid -->
    </div><!-- /span2 -->

    <div id="content" class="span9">
      <!-- content starts -->

      <div class="row-fluid">
        <div class="box span12">
          <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/facturacion/ventasAcumulado_xls'); ?>" class="linksm" target="_blank">
            <i class="icon-table"></i> Excel</a>

          <div class="box-content">
            <div class="row-fluid">
              <iframe name="rpfReporte" id="iframe-reporte" class="span12"
                src="<?php echo base_url('panel/facturacion/ventasAcumulado_pdf')?>" style="height:520px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->
