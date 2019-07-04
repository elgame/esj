		<div id="content" class="span10">
			<!-- content starts -->


			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						<a href="<?php echo base_url('panel/rastreabilidad_paletas/'); ?>">Paletas</a> <span class="divider">/</span>
					</li>
					<li>Agregar</li>
				</ul>
			</div>

			<div class="row-fluid">
				<div class="box span12">
					<div class="box-header well" data-original-title>
						<h2><i class="icon-edit"></i> Agregar Paleta de salida</h2>
						<div class="box-icon">
							<a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
						</div>
					</div>
					<div class="box-content">
						<form action="<?php echo base_url('panel/rastreabilidad_paletas/agregar'); ?>" id="form-search" method="post" class="form-horizontal">
						  <fieldset>
								<legend></legend>

								<div class="row-fluid">

                  <div class="control-group span4">
                    <label class="control-label" for="infBoletasSalidas">Boletas entrada </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <button type="button" class="btn btn-info" id="show-boletasSalidas">Buscar</button>
                        <input type="text" name="boletasSalidasFolio" id="boletasSalidasFolio" value="<?php echo set_value('boletasSalidasFolio'); ?>" class="span7" readonly>
                        <input type="hidden" name="boletasSalidasId" id="boletasSalidasId" value="<?php echo set_value('boletasSalidasId'); ?>">
                      </div>
                    </div>
                  </div><!--/control-group -->

                  <div class="control-group span4">
                    <label class="control-label" for="empresa">Empresa </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="empresa" id="empresa" class="span11" value="<?php echo set_value('empresa'); ?>" data-next="tipo" readonly>
                        <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value('empresaId'); ?>">
                      </div>
                    </div>
                  </div><!--/control-group -->

									<div class="control-group span3">
                    <label class="control-label" for="tipo">Tipo </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <select name="tipo" id="tipo" class="span11" data-next="fecha">
                          <option value="lo" <?php echo set_select('tipo', 'lo'); ?>>Local</option>
                          <option value="na" <?php echo set_select('tipo', 'na'); ?>>Nacional</option>
                          <option value="naex" <?php echo set_select('tipo', 'naex'); ?>>Nacional o Exportación (pallets)</option>
                        </select>
                      </div>
                    </div>
                  </div><!--/control-group -->

								</div>
								<div class="clearfix"></div>

								<div class="row-fluid">
                  <div class="control-group span4">
                    <label class="control-label" for="fecha">Fecha </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="date" name="fecha" id="fecha" class="span11" value="<?php echo set_value('fecha', date("Y-m-d")); ?>" data-next="prod_cliente">
                      </div>
                    </div>
                  </div><!--/control-group -->
                </div>
                <div class="clearfix"></div>

								<div class="row-fluid" id="show-table-prod">
                  <div class="span12">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table_prod">
                      <thead>
                        <tr>
                          <th>Cliente</th>
                          <th>Clasificación</th>
                          <th>Medida</th>
                          <th>Cant.</th>
                          <th>Kg</th>
                          <th>Accion</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr data-pallets="" data-remisiones="">
                          <td>
                            <input type="text" name="prod_cliente[]" value="" id="prod_cliente" class="span12" data-next="prod_ddescripcion">
                            <input type="hidden" name="prod_id_cliente[]" value="" id="prod_id_cliente" class="span12">
                          </td>
                          <td>
                            <input type="text" name="prod_ddescripcion[]" value="" id="prod_ddescripcion" class="span12" data-next="prod_dmedida">
                            <input type="hidden" name="prod_did_prod[]" value="" id="prod_did_prod" class="span12">
                          </td>
                          <td>
                            <select name="prod_dmedida[]" id="prod_dmedida" class="span12" data-next="prod_dcantidad">
                              <?php foreach ($unidades as $key => $u) {
                                  if ($key === 0) $uni = $u->id_unidad;
                                ?>
                                <option value="<?php echo $u->nombre ?>" data-id="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>"><?php echo $u->nombre ?></option>
                              <?php } ?>
                            </select>
                            <input type="hidden" name="prod_dmedida_id[]" value="<?php echo $uni ?>" id="prod_dmedida_id" class="span12 vpositive">
                          </td>
                          <td>
                            <input type="text" name="prod_dcantidad[]" value="0" id="prod_dcantidad" class="span12 vpositive">
                          </td>
                          <td>
                            <span id="prod_dmedida_kilos_text"></span>
                            <input type="hidden" name="prod_dmedida_kilos[]" value="0" id="prod_dmedida_kilos" class="span12 vpositive" readonly="readonly">
                          </td>
                          <td><button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button></td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="row-fluid" id="show-table-pallets">
                  <div class="span5">
                    <h6>Acomodo de Pallets</h6>
                    <div id="select_pallets">
                      <div class="span1 nums">1</div>
                      <div class="span4 slots">
                        <span class="holder">Posición 1</span>
                      </div>
                      <div class="span4 slots">
                        <span class="holder">Posición 2</span>
                      </div>
                      <div class="span1 nums">2</div>
                    </div>
                  </div>

                  <div class="span7">
                    <fieldset>

                      <label for="fnombre">Buscar</label>
                      <input type="text" name="fnombre" id="fnombre" value=""
                        class="input-large search-query" placeholder="Folio" autofocus> |

                      <label for="ffecha">Fecha</label>
                      <input type="date" name="ffecha" id="ffecha" value=""> |

                      <input type="button" name="enviar" value="Buscar" class="btn">
                    </fieldset>

                    <div id="table_pallets">
                      <div class="span12 pallet" data-id="1">aaaa</div>
                      <div class="span12 pallet" data-id="2">wwww</div>
                      <div class="span12 pallet" data-id="3">zzzz</div>
                    </div>

                  </div>
                </div>

								<div class="form-actions">
								  <button type="submit" id="btn_submit" class="btn btn-primary">Guardar</button>
								  <a href="<?php echo base_url('panel/rastreabilidad_paletas/'); ?>" class="btn">Cancelar</a>
								</div>
						  </fieldset>
						</form>

					</div>
				</div><!--/span-->

			</div><!--/row-->


					<!-- content ends -->
		</div><!--/#content.span10-->

<!-- Modal boletas -->
<div id="modal-boletas" class="modal modal-w50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="myModalLabel">Boletas</h3>
  </div>
  <div class="modal-body">
    <div class="row-fluid">
      <input type="text" id="filBoleta" class="pull-left" placeholder="Folio"> <span class="pull-left"> | </span>
    </div>
    <div class="row-fluid">
      <table class="table table-hover table-condensed" id="table-boletas">
        <thead>
          <tr>
            <th style="width:70px;">Fecha</th>
            <th># Folio</th>
            <th>Proveedor</th>
            <th>Área</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
    <button class="btn btn-primary" id="BtnAddBoleta">Seleccionar</button>
  </div>
</div><!--/modal boletas -->

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

