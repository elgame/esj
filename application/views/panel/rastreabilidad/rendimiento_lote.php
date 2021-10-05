    <div id="content" class="span10">
      <!-- content starts -->
      <input type="hidden" value="<?php echo $lote_actual ?>" id="loteActual">
      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/rastreabilidad/'); ?>">Rastreabilidad</a> <span class="divider">/</span>
          </li>
          <li>Rendimiento por Lote</li>
        </ul>
      </div>

      <div class="row-fluid" id="box-cajas"><!--cajas-->
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-bar-chart"></i> Rendimiento por Lote</h2>
            <div class="box-icon">
              <a href="<?php echo base_url('panel/rastreabilidad/rpt_lotes_pdf/?fecha='.$fecha.'&areaid='.(isset($clasificaciones['info']->id_area) ? $clasificaciones['info']->id_area : (isset($_GET['parea'])? $_GET['parea']: '2')) ); ?>" class="btn btn-round btn-danger" title="Imprimir reporte de lotes" target="_BLANK"><i class="icon-print"></i></a>
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content" id="box-content">
            <div class="row-fluid">

              <form action="<?php echo base_url('panel/rastreabilidad/rendimiento_lote?'.MyString::getVarsLink(array('msg'))); ?>" method="GET" class="form-horizontal" id="form">

                <div class="control-group span9">
                  <table class="table">
                    <thead>
                      <tr class="center">
                        <th style="background-color: #FFF; text-align: center;" class="center">Area</th>
                        <th style="background-color: #FFF; text-align: center;">Certificado</th>
                        <th style="background-color: #FFF; text-align: center;" class="center">Fecha</th>
                        <th style="background-color: #FFF; text-align: center;" class="center">Semana</th>
                        <th style="background-color: #FFF; text-align: center;">Dia</th>
                        <th style="background-color: #FFF; text-align: center;">Fecha Lote</th>
                        <th style="background-color: #FFF; text-align: center;">Lote</th>
                        <th style="background-color: #FFF; text-align: center;">Actualizar</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>
                          <select name="parea" id="parea" class="span12" style="margin: -7px auto 0 auto;">
                            <?php foreach ($areas['areas'] as $area){ ?>
                              <option value="<?php echo $area->id_area ?>" data-tipo="<?php echo $area->tipo; ?>"
                                <?php $set_select=($area->id_area == (isset($clasificaciones['info']->id_area) ? $clasificaciones['info']->id_area : ($area->predeterminado == 't' ? $area->id_area: '')));
                                 echo ($set_select? 'selected': ''); ?>><?php echo $area->nombre ?></option>
                            <?php } ?>
                          </select>
                        </td>
                        <td style="text-align: center;">
                          <?php
                          $certificado = '';
                          if ((isset($clasificaciones['info']->certificado) && $clasificaciones['info']->certificado === 't') || $lote_actual_ext == 1) {
                            $certificado = 'checked';
                          }
                          ?>
                          <input type="checkbox" name="certificado" id="esta-certificado" <?php echo $certificado ?>>
                        </td>
                        <td>
                          <input type="date" name="gfecha" value="<?php echo set_value_get('gfecha', $fecha); ?>" id="gfecha" class="span8"
                            style="margin: -7px auto 0 auto; text-align: center;" maxlength="10" autofocus>
                        </td>
                        <td style="text-align: center;"><span class="label label-important" style="font-size: 1.4em;"><?php echo $semana ?></span></td>
                        <td style="text-align: center;"><span class="label label-important" style="font-size: 1.4em;"><?php echo $dia_semana ?></span></td>

                        <td style="text-align: center;">
                          <input type="date" name="gfechaLote" value="<?php echo $fecha_lote; ?>" id="gfechaLote" class="span8"
                            style="margin: -7px auto 0 auto; text-align: center;" maxlength="10">
                        </td>

                        <td style="text-align: center;">
                          <select name="glote" id="glote" class="span12" style="margin: -7px auto 0 auto;">
                            <option value=""></option>
                            <?php foreach ($lotes as $key => $lote) { ?>
                              <option value="<?php echo $lote->id_rendimiento ?>" <?php echo set_select_get('glote', $lote->id_rendimiento, false); ?>><?php echo $lote->lote_ext ?></option>
                            <?php } ?>
                          </select>
                        </td>

                        <td style="text-align: center;">
                          <span class="input-append" style="max-width: 100px;">
                            <input class="span5 vpositive" id="txtActualizaLote" type="text" value="<?php echo $lote_actual_ext; ?>" data-lote="<?php echo $lote_actual_ext; ?>">
                            <button class="btn" type="button" id="btnActualizaLote">Ok</button>
                            <input type="hidden" id="id_lote_actual" value="<?php echo $id_lote_actual; ?>">
                          </span>
                        </td>

                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="span1 nomarg">

                  <?php if ($ant_lote >= 1) { ?>
                    <a class="btn btn-success pull-right" href="<?php echo base_url('panel/rastreabilidad/siguiente_lote?glote='.$ant_lote.'&gfecha='.$fecha.'&parea='.$area_default); ?>">Anterior Lote</a>
                  <?php } ?>

                </div>
                <div class="span1 nomarg">
                  <a class="btn btn-success pull-left" href="<?php echo base_url('panel/rastreabilidad/siguiente_lote?glote='.$sig_lote.'&gfecha='.$fecha.'&parea='.$area_default); ?>">Siguiente Lote</a>
                </div>
                <div class="span1 nomarg">
                  <?php if (count($clasificaciones['clasificaciones']) > 0) { ?>
                    <a class="btn btn-danger" href="<?php echo base_url('panel/rastreabilidad/rpl_pdf/?glote='.$_GET['glote']); ?>" target="_BLANK">Imprimir</a>
                  <?php } ?>
                </div>

              </form>

              <table class="table table-striped table-bordered table-hover table-condensed" id="tableClasif">
                <caption></caption>
                <thead>
                  <tr>
                    <th>CLASIFICACIÓN</th>
                    <th style="width:110px;">CAJA</th>
                    <th style="width:55px;">TAMAÑO</th>
                    <th style="width:55px;">CALIBRE</th>
                    <th style="width:110px;">ETIQUETA</th>
                    <th style="width:55px;">KILOS</th>
                    <th style="width:55px;">EXISTENTE</th>
                    <th style="width:55px;">LINEA 1</th>
                    <th style="width:55px;">LINEA 2</th>
                    <th>TOTAL</th>
                    <th>RENDIMIENTO</th>
                    <th>ACCIONES</th>
                  </tr>
                </thead>
                <tbody>

                  <?php foreach ($clasificaciones['clasificaciones'] as $key => $c) { ?>
                    <tr id="<?php echo $c->id_clasificacion ?>">
                      <td>
                        <input type="text" id="fclasificacion" value="<?php echo $c->clasificacion ?>" class="span12">
                        <input type="hidden" id="fidclasificacion" value="<?php echo $c->id_clasificacion ?>" class="span12">
                        <input type="hidden" id="fidclasificacion_old" value="<?php echo $c->id_clasificacion ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="funidad" value="<?php echo $c->unidad ?>" class="span12">
                        <input type="hidden" id="fidunidad" value="<?php echo $c->id_unidad ?>" class="span12">
                        <input type="hidden" id="fidunidad_old" value="<?php echo $c->id_unidad ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="fcalibre" value="<?php echo $c->calibre ?>" class="span12">
                        <input type="hidden" id="fidcalibre" value="<?php echo $c->id_calibre ?>" class="span12">
                        <input type="hidden" id="fidcalibre_old" value="<?php echo $c->id_calibre ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="fsize" value="<?php echo $c->size ?>" class="span12">
                        <input type="hidden" id="fidsize" value="<?php echo $c->id_size ?>" class="span12">
                        <input type="hidden" id="fidsize_old" value="<?php echo $c->id_size ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="fetiqueta" value="<?php echo $c->etiqueta ?>" class="span12">
                        <input type="hidden" id="fidetiqueta" value="<?php echo $c->id_etiqueta ?>" class="span12">
                        <input type="hidden" id="fidetiqueta_old" value="<?php echo $c->id_etiqueta ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="fkilos" value="<?php echo $c->kilos ?>" class="span12 vpositive">
                        <input type="hidden" id="fkilos_old" value="<?php echo $c->kilos ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <input type="text" id="fexistente" value="<?php echo $c->existente ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <input type="text" id="flinea1" value="<?php echo $c->linea1 ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <input type="text" id="flinea2" value="<?php echo $c->linea2 ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <span id="ftotal-span"><?php echo $c->total ?></span>
                        <input type="hidden" id="ftotal" value="<?php echo $c->total ?>" class="span12 vpositive" data-valor="<?php echo $c->total ?>">
                      </td>
                      <td>
                        <span id="frd-span"><?php echo $c->rendimiento ?></span>
                        <input type="hidden" id="frd" value="<?php echo $c->rendimiento ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <input type="checkbox" id="ffrutaCom" <?php echo ($c->fruta_com == 't'? 'checked': '') ?> data-rel="tooltip" title="Es fruta comprada?"> |
                        <button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>
                        <button type="button" class="btn btn-success btn-small" id="btnDelClasif">Eliminar</button>
                      </td>
                    </tr>
                  <?php } ?>

                  <tr>
                    <td>
                      <input type="text" id="fclasificacion" value="" class="span12">
                      <input type="hidden" id="fidclasificacion" value="" class="span12">
                      <input type="hidden" id="fidclasificacion_old" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="funidad" value="" class="span12">
                      <input type="hidden" id="fidunidad" value="" class="span12">
                      <input type="hidden" id="fidunidad_old" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="fcalibre" value="" class="span12">
                      <input type="hidden" id="fidcalibre" value="" class="span12">
                      <input type="hidden" id="fidcalibre_old" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="fsize" value="" class="span12">
                      <input type="hidden" id="fidsize" value="" class="span12">
                      <input type="hidden" id="fidsize_old" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="fetiqueta" value="SIN MARCA" class="span12">
                      <input type="hidden" id="fidetiqueta" value="15" class="span12">
                      <input type="hidden" id="fidetiqueta_old" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="fkilos" value="0" class="span12 vpositive">
                      <input type="hidden" id="fkilos_old" value="0" class="span12 vpositive">
                    </td>
                    <td>
                      <input type="text" id="fexistente" value="0" class="span12 vpositive">
                    </td>
                    <td>
                      <input type="text" id="flinea1" value="0" class="span12 vpositive">
                    </td>
                    <td>
                      <input type="text" id="flinea2" value="0" class="span12 vpositive">
                    </td>
                    <td>
                      <span id="ftotal-span">0</span>
                      <input type="hidden" id="ftotal" value="0" class="span12 vpositive">
                    </td>
                    <td>
                      <span id="frd-span">0</span>
                      <input type="hidden" id="frd" value="0" class="span12 vpositive">
                    </td>
                    <td>
                      <input type="checkbox" id="ffrutaCom" data-rel="tooltip" title="Es fruta comprada?"> |
                      <button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>
                      <button type="button" class="btn btn-success btn-small" id="btnDelClasif">Eliminar</button>
                    </td>
                  </tr>
                </tbody>
              </table>

            </div>
          </div><!--/box-content-->
        </div><!--/box span12-->
      </div><!--/row-fluid cajas-->

      <div class="form-actions">
      </div>

    </div><!--/#content.span10-->


<?php if (isset($ticket)) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/bascula/imprimir/?id=' . $ticket."'") ?>, '_blank');
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

