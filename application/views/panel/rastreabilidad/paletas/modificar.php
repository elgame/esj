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
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar Paleta de salida</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/rastreabilidad_paletas/modificar?id='.$_GET['id']); ?>" id="form-search" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>

                <div class="row-fluid">

                  <div class="control-group span4">
                    <label class="control-label" for="infBoletasSalidas">Boletas entrada </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <?php if ($disabled !== 'disabled'): ?>
                        <button type="button" class="btn btn-info" id="show-boletasSalidas">Buscar</button>
                        <?php endif ?>
                        <input type="text" name="boletasSalidasFolio" id="boletasSalidasFolio" value="<?php echo $info['paleta']->folio; ?>" class="span7" readonly>
                        <input type="hidden" name="boletasSalidasId" id="boletasSalidasId" value="<?php echo $info['paleta']->id_bascula; ?>">
                      </div>
                    </div>
                  </div><!--/control-group -->

                  <div class="control-group span4">
                    <label class="control-label" for="empresa">Empresa </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="empresa" id="empresa" class="span11" value="<?php echo $info['paleta']->empresa; ?>" data-next="tipo" readonly>
                        <input type="hidden" name="empresaId" id="empresaId" value="<?php echo $info['paleta']->id_empresa; ?>">
                      </div>
                    </div>
                  </div><!--/control-group -->

                  <div class="control-group span3">
                    <label class="control-label" for="tipo">Tipo </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <select name="tipo" id="tipo" class="span11" data-next="fecha" <?php echo $disabled ?>>
                          <option value="lo" <?php echo set_select('tipo', 'lo', false, $info['paleta']->tipo); ?>>Local</option>
                          <option value="na" <?php echo set_select('tipo', 'na', false, $info['paleta']->tipo); ?>>Nacional</option>
                          <option value="naex" <?php echo set_select('tipo', 'naex', false, $info['paleta']->tipo); ?>>Nacional o Exportación (pallets)</option>
                        </select>
                      </div>
                    </div>
                  </div><!--/control-group -->

                  <div class="control-group span4">
                    <label class="control-label" for="tipoNP">Nacional Pallets </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="checkbox" name="tipoNP" id="tipoNP" value="si" <?php echo set_checkbox('tipoNP', 'si'); ?> data-next="empresa_contratante">
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
                        <input type="date" name="fecha" id="fecha" class="span11" value="<?php echo $info['paleta']->fecha; ?>" data-next="prod_cliente" <?php echo $readonly ?>>
                      </div>
                    </div>
                  </div><!--/control-group -->

                  <div class="control-group span3">
                    <label class="control-label" for="status">Estado </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <select name="status" id="status" class="span11" data-next="fecha" <?php echo $disabled ?>>
                          <option value="r" <?php echo set_select('status', 'r', false, $info['paleta']->status); ?>>Registrado</option>
                          <option value="f" <?php echo set_select('status', 'f', false, $info['paleta']->status); ?>>Finalizado</option>
                        </select>
                      </div>
                    </div>
                  </div><!--/control-group -->
                </div>

                <div class="row-fluid">
                  <div class="control-group span6">
                    <label class="control-label" for="empresa_contratante">Empresa contratante </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="empresa_contratante" id="empresa_contratante" class="span11" value="<?php echo (isset($info['paleta']->manifesto->empresa_contratante)? $info['paleta']->manifesto->empresa_contratante: ''); ?>" data-next="cliente_destino">
                      </div>
                    </div>
                  </div><!--/control-group -->

                  <div class="control-group span6">
                    <label class="control-label" for="cliente_destino">Cliente Destino </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="cliente_destino" id="cliente_destino" class="span11" value="<?php echo (isset($info['paleta']->manifesto->cliente_destino)? $info['paleta']->manifesto->cliente_destino: ''); ?>" data-next="direccion">
                      </div>
                    </div>
                  </div><!--/control-group -->
                </div>

                <div class="row-fluid">
                  <div class="control-group span12">
                    <label class="control-label" for="direccion">Dirección </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="direccion" id="direccion" class="span11" value="<?php echo (isset($info['paleta']->manifesto->direccion)? $info['paleta']->manifesto->direccion: ''); ?>" data-next="dia_llegada">
                      </div>
                    </div>
                  </div><!--/control-group -->
                </div>

                <div class="row-fluid">
                  <div class="control-group span4">
                    <label class="control-label" for="dia_llegada">Día de llegada </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="date" name="dia_llegada" id="dia_llegada" class="span12" value="<?php echo (isset($info['paleta']->manifesto->dia_llegada)? $info['paleta']->manifesto->dia_llegada: ''); ?>" data-next="hr_entrega">
                      </div>
                    </div>
                  </div><!--/control-group -->

                  <div class="control-group span4">
                    <label class="control-label" for="hr_entrega">Hr de entrega </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="hr_entrega" id="hr_entrega" class="span12" value="<?php echo (isset($info['paleta']->manifesto->hr_entrega)? $info['paleta']->manifesto->hr_entrega: ''); ?>" data-next="placa_termo">
                      </div>
                    </div>
                  </div><!--/control-group -->

                  <div class="control-group span4">
                    <label class="control-label" for="placa_termo">Placa termo </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="placa_termo" id="placa_termo" class="span12" value="<?php echo (isset($info['paleta']->manifesto->placa_termo)? $info['paleta']->manifesto->placa_termo: ''); ?>" data-next="temperatura">
                      </div>
                    </div>
                  </div><!--/control-group -->

                  <div class="control-group span4">
                    <label class="control-label" for="temperatura">Temperatura </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="temperatura" id="temperatura" class="span12" value="<?php echo (isset($info['paleta']->manifesto->temperatura)? $info['paleta']->manifesto->temperatura: ''); ?>" data-next="orden_flete">
                      </div>
                    </div>
                  </div><!--/control-group -->

                  <div class="control-group span4">
                    <label class="control-label" for="orden_flete">Orden de Flete </label>
                    <div class="controls">
                      <div class="input-append span12">
                        <input type="text" name="orden_flete" id="orden_flete" class="span12" value="<?php echo (isset($info['paleta']->manifesto->orden_flete)? $info['paleta']->manifesto->orden_flete: ''); ?>" data-next="prod_cliente">
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
                          <th>>Calibre</th>
                          <th>Medida</th>
                          <th>Cant.</th>
                          <th>Kg</th>
                          <th>Accion</th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php if (isset($info['clasificaciones']) && count($info['clasificaciones']) > 0): ?>
                        <?php foreach ($info['clasificaciones'] as $key => $clasificacion): ?>
                          <tr data-pallet="<?php echo $clasificacion->id_pallet; ?>">
                            <td>
                              <input type="text" name="prod_cliente[]" value="<?php echo $clasificacion->cliente; ?>" id="prod_cliente" class="span12" data-next="prod_ddescripcion" <?php echo $readonly ?>>
                              <input type="hidden" name="prod_id_cliente[]" value="<?php echo $clasificacion->id_cliente; ?>" id="prod_id_cliente" class="span12">
                              <input type="hidden" name="prod_id_pallet[]" value="<?php echo $clasificacion->id_pallet; ?>" id="prod_id_pallet" class="span12">
                            </td>
                            <td>
                              <input type="text" name="prod_ddescripcion[]" value="<?php echo $clasificacion->clasificacion; ?>" id="prod_ddescripcion" class="span12" data-next="prod_dcalibre" <?php echo $readonly ?>>
                              <input type="hidden" name="prod_did_prod[]" value="<?php echo $clasificacion->id_clasificacion; ?>" id="prod_did_prod" class="span12">
                            </td>
                            <td>
                              <input type="text" name="prod_dcalibre[]" value="<?php echo $clasificacion->calibre; ?>" id="prod_dcalibre" class="span12" data-next="prod_dmedida">
                              <input type="hidden" name="prod_did_calibre[]" value="<?php echo $clasificacion->id_calibre; ?>" id="prod_did_calibre" class="span12">
                            </td>
                            <td>
                              <select name="prod_dmedida[]" id="prod_dmedida" class="span12" data-next="prod_dcantidad" <?php echo $disabled ?>>
                                <?php
                                foreach ($unidades as $keyu => $u) {
                                  $selected = '';
                                  if ($u->id_unidad == $clasificacion->id_unidad) {
                                    $selected = 'selected';
                                    $u->cantidad = $clasificacion->cantidad_unidad;
                                  }
                                ?>
                                  <option value="<?php echo $u->nombre ?>" data-id="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo $selected ?>><?php echo $u->nombre ?></option>
                                <?php } ?>
                              </select>
                              <input type="hidden" name="prod_dmedida_id[]" value="<?php echo $clasificacion->id_unidad ?>" id="prod_dmedida_id" class="span12 vpositive">
                            </td>
                            <td>
                              <input type="text" name="prod_dcantidad[]" value="<?php echo $clasificacion->cantidad ?>" id="prod_dcantidad" class="span12 vpositive" <?php echo $readonly ?>>
                            </td>
                            <td>
                              <span id="prod_dmedida_kilos_text"><?php echo $clasificacion->kilos ?></span>
                              <input type="hidden" name="prod_dmedida_kilos[]" value="<?php echo $clasificacion->kilos ?>" id="prod_dmedida_kilos" class="span12 vpositive" readonly="readonly">
                            </td>
                            <td>
                              <?php if ($disabled !== 'disabled'): ?>
                              <button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>
                              <?php endif ?>
                            </td>
                          </tr>
                        <?php endforeach ?>
                      <?php elseif($disabled !== 'disabled'): ?>
                          <tr data-pallet="">
                            <td>
                              <input type="text" name="prod_cliente[]" value="" id="prod_cliente" class="span12" data-next="prod_ddescripcion" <?php echo $readonly ?>>
                              <input type="hidden" name="prod_id_cliente[]" value="" id="prod_id_cliente" class="span12">
                              <input type="hidden" name="prod_id_pallet[]" value="" id="prod_id_pallet" class="span12">
                            </td>
                            <td>
                              <input type="text" name="prod_ddescripcion[]" value="" id="prod_ddescripcion" class="span12" data-next="prod_dcalibre" <?php echo $readonly ?>>
                              <input type="hidden" name="prod_did_prod[]" value="" id="prod_did_prod" class="span12">
                            </td>
                            <td>
                              <input type="text" name="prod_dcalibre[]" value="" id="prod_dcalibre" class="span12" data-next="prod_dmedida">
                              <input type="hidden" name="prod_did_calibre[]" value="" id="prod_did_calibre" class="span12">
                            </td>
                            <td>
                              <select name="prod_dmedida[]" id="prod_dmedida" class="span12" data-next="prod_dcantidad" <?php echo $disabled ?>>
                                <?php
                                foreach ($unidades as $keyu => $u) {
                                  $selected = '';
                                  if ($keyu === 0) {
                                    $selected = 'selected';
                                    $uni = $u->id_unidad;
                                  }
                                ?>
                                  <option value="<?php echo $u->nombre ?>" data-id="<?php echo $u->id_unidad ?>" data-cantidad="<?php echo $u->cantidad ?>" <?php echo $selected ?>><?php echo $u->nombre ?></option>
                                <?php } ?>
                              </select>
                              <input type="hidden" name="prod_dmedida_id[]" value="<?php echo $uni ?>" id="prod_dmedida_id" class="span12 vpositive">
                            </td>
                            <td>
                              <input type="text" name="prod_dcantidad[]" value="" id="prod_dcantidad" class="span12 vpositive" <?php echo $readonly ?>>
                            </td>
                            <td>
                              <span id="prod_dmedida_kilos_text"></span>
                              <input type="hidden" name="prod_dmedida_kilos[]" value="" id="prod_dmedida_kilos" class="span12 vpositive" readonly="readonly">
                            </td>
                            <td>
                              <button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>
                            </td>
                          </tr>
                      <?php endif ?>
                        <!-- <tr data-pallet="">
                          <td>
                            <input type="text" name="prod_cliente[]" value="" id="prod_cliente" class="span12" data-next="prod_ddescripcion">
                            <input type="hidden" name="prod_id_cliente[]" value="" id="prod_id_cliente" class="span12">
                            <input type="hidden" name="prod_id_pallet[]" value="" id="prod_id_pallet" class="span12">
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
                        </tr> -->
                      </tbody>
                    </table>
                  </div>
                </div>

                <div class="row-fluid" id="show-table-pallets">
                  <div class="span12"><h4>Acomodo de Pallets</h4></div>

                  <div class="row-fluid">
                    <div class="span5">
                      <div id="select_pallets">
                        <?php for ($i = 0; $i < 15; $i++): ?>
                        <div class="row-fluid">
                          <div class="span1 nums"><?php echo ($i*2)+1 ?></div>
                          <div class="span4 slots">
                            <?php $exist = isset($info['pallets'][($i*2)+1]); ?>
                            <span class="holder" style="display: <?php echo ($exist? 'none': 'block') ?>">Posición <?php echo ($i*2)+1 ?>
                              <input type="hidden" name="pallets_posicion[]" class="pallets_posicion" value="<?php echo ($i*2)+1 ?>">
                              <input type="hidden" name="pallets_id[]" class="pallets_id" value="<?php echo ($exist? $info['pallets'][($i*2)+1]->id_pallet: '') ?>">
                            </span>
                            <?php if ($exist): ?>
                              <div class="span12 pallet post-draggable correct" data-id="<?php echo $info['pallets'][($i*2)+1]->id_pallet ?>"
                                  data-folio="" data-cajas=""
                                  data-fecha="" data-cliente=""
                                  data-idcliente=""
                                  aria-disabled="true" style="width: 185px; height: 60px;">
                                <span class="dataInSlot" style="display: inline;">
                                  Folio: <?php echo $info['pallets'][($i*2)+1]->folio ?> | Cajas: <?php echo $info['pallets'][($i*2)+1]->no_cajas ?></span>
                                  <?php if ($disabled !== 'disabled'): ?>
                                  <i class="icon-remove quit" title="Quitar"></i>
                                  <?php endif ?>
                              </div>
                            <?php endif ?>
                          </div>
                          <div class="span4 slots">
                            <?php $exist = isset($info['pallets'][($i+1)*2]); ?>
                            <span class="holder" style="display: <?php echo ($exist? 'none': 'block') ?>">Posición <?php echo ($i+1)*2 ?>
                              <input type="hidden" name="pallets_posicion[]" class="pallets_posicion" value="<?php echo ($i+1)*2 ?>">
                              <input type="hidden" name="pallets_id[]" class="pallets_id" value="<?php echo ($exist? $info['pallets'][($i+1)*2]->id_pallet: '') ?>">
                            </span>
                            <?php if ($exist): ?>
                              <div class="span12 pallet post-draggable correct" data-id="<?php echo $info['pallets'][($i+1)*2]->id_pallet ?>"
                                  data-folio="" data-cajas=""
                                  data-fecha="" data-cliente=""
                                  data-idcliente=""
                                  aria-disabled="true" style="width: 185px; height: 60px;">
                                <span class="dataInSlot" style="display: inline;">
                                  Folio: <?php echo $info['pallets'][($i+1)*2]->folio ?> | Cajas: <?php echo $info['pallets'][($i+1)*2]->no_cajas ?></span>
                                  <?php if ($disabled !== 'disabled'): ?>
                                  <i class="icon-remove quit" title="Quitar"></i>
                                  <?php endif ?>
                              </div>
                            <?php endif ?>
                          </div>
                          <div class="span1 nums"><?php echo ($i+1)*2 ?></div>
                        </div>
                        <?php endfor ?>
                      </div>
                    </div>

                    <div class="span7">
                      <?php if ($disabled !== 'disabled'): ?>
                      <fieldset>
                        <label for="fnombre">Buscar</label>
                        <input type="text" name="fnombre" id="fnombre" value=""
                          class="input-large search-query" placeholder="Folio" autofocus> |

                        <label for="ffecha">Fecha</label>
                        <input type="date" name="ffecha" id="ffecha" value=""> |

                        <input type="button" name="enviar" value="Buscar" class="btn" id="fbtnFindPallet">
                      </fieldset>
                      <?php endif ?>

                      <div id="table_pallets">
                        No hay resultados
                      </div>

                    </div>
                  </div>
                </div>

                <div class="form-actions">
                  <?php if ($disabled !== 'disabled'): ?>
                  <button type="submit" id="btn_submit" class="btn btn-primary">Guardar</button>
                  <?php endif ?>
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

