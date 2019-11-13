    <div id="content" class="span10">
      <!-- content starts -->
      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/rastreabilidad/'); ?>">Rastreabilidad</a> <span class="divider">/</span>
          </li>
          <li>Rendimiento de piña</li>
        </ul>
      </div>

      <div class="row-fluid" id="box-cajas"><!--cajas-->
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-bar-chart"></i> Rendimiento de piña</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content" id="box-content">
            <div class="row-fluid">

              <form action="<?php echo base_url('panel/rastreabilidad_pinia?'.String::getVarsLink(array('msg'))); ?>" method="GET" class="form-horizontal" id="form">

                <div class="control-group span7">
                  <table class="table">
                    <thead>
                      <tr class="center">
                        <th style="background-color: #FFF; text-align: center;" class="center">Area</th>
                        <th style="background-color: #FFF; text-align: center;" class="center">Fecha</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>
                          <select name="parea" id="parea" class="span12" style="margin: -7px auto 0 auto;">
                            <?php foreach ($areas['areas'] as $area){ ?>
                              <option value="<?php echo $area->id_area ?>" data-tipo="<?php echo $area->tipo; ?>"
                                <?php $set_select=($area->id_area == $area_default);
                                 echo ($set_select? 'selected': ''); ?>><?php echo $area->nombre ?></option>
                            <?php } ?>
                          </select>
                        </td>
                        <td>
                          <input type="date" name="gfecha" value="<?php echo set_value_get('gfecha', $fecha); ?>" id="gfecha" class="span8"
                            style="margin: -7px auto 0 auto; text-align: center;" maxlength="10" autofocus>
                        </td>

                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="span1 nomarg">
                    <a class="btn btn-danger" href="<?php echo base_url('panel/rastreabilidad_pinia/rpl_pdf/?parea='.$area_default.'&gfecha='.set_value_get('gfecha', $fecha)); ?>" target="_BLANK">Imprimir</a>
                </div>

              </form>

              <table class="table table-striped table-bordered table-hover table-condensed" id="tableEntradas">
                <caption>ENTRADA</caption>
                <thead>
                  <tr>
                    <th>Boleta</th>
                    <th>Kilos</th>
                    <th>Pieza</th>
                    <th>Rancho</th>
                    <th>No Melga</th>
                    <th>ACCIONES</th>
                  </tr>
                </thead>
                <tbody>

                  <?php
                  $total_kilos = 0;
                  foreach ($info['entradas'] as $key => $c) {
                    if ($c->melga > 0)
                      $total_kilos += $c->kilos_neto;
                  ?>
                    <tr id="<?php echo $c->id_bascula ?>">
                      <td>
                        <?php echo $c->folio ?>
                        <input type="hidden" id="fid_bascula" value="<?php echo $c->id_bascula ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="fkilos" value="<?php echo $c->kilos_neto ?>" class="span12" readonly>
                      </td>
                      <td>
                        <input type="text" id="fpiezas" value="<?php echo $c->total_cajas ?>" class="span12" readonly>
                      </td>
                      <td>
                        <input type="text" id="francho" value="<?php echo $c->rancho ?>" class="span12" readonly>
                      </td>
                      <td>
                        <input type="text" id="fmelga" value="<?php echo $c->melga ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <button type="button" class="btn btn-success btn-small" id="btnAddEntradas">Guardar</button>
                        <button type="button" class="btn btn-success btn-small" id="btnDelEntradas">Eliminar</button>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
                <tfoot>
                  <tr id="total_entradas">
                    <th colspan="2" style="text-align: right;">
                      <span id="ftkilos_entrada_spn"><?php echo String::formatoNumero($total_kilos, 2, '', false) ?></span>
                      <input type="hidden" id="ftkilos_entrada" value="<?php echo $total_kilos ?>" class="span12">
                    </th>
                    <th colspan="4">
                    </th>
                  </tr>
                </tfoot>
              </table>

              <script type="text/javascript" charset="utf-8">
                var json_unidades = jQuery.parseJSON('<?php echo json_encode($unidades); ?>');
              </script>
              <table class="table table-striped table-bordered table-hover table-condensed" id="tableClasif">
                <caption>RENDIMIENTO</caption>
                <thead>
                  <tr>
                    <th>No TAMAÑO</th>
                    <th>T/ENVASE</th>
                    <th>No COLOR</th>
                    <th>KILOS</th>
                    <th>TIPO</th>
                    <th>ACCIONES</th>
                  </tr>
                </thead>
                <tbody>

                  <?php $total_rendimientos = 0;
                  foreach ($info['rendimientos'] as $key => $c) {
                    $total_rendimientos += $c->kilos;
                  ?>
                    <tr id="<?php echo $c->id_rendimiento ?>">
                      <td>
                        <input type="text" id="ftamano" value="<?php echo $c->tamanio ?>" class="span12 vpositive">
                        <input type="hidden" id="fid_rendimiento" value="<?php echo $c->id_rendimiento ?>" class="span12">
                      </td>
                      <td>
                        <select id="funidad" class="span12">
                          <?php foreach ($unidades as $key => $u) { ?>
                            <option value="<?php echo $u->id_unidad ?>" <?php echo $c->id_unidad == $u->id_unidad ? 'selected' : '' ?>><?php echo $u->nombre ?></option>
                          <?php } ?>
                        </select>
                      </td>
                      <td>
                        <input type="text" id="fcolor" value="<?php echo $c->color ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="fkilos" value="<?php echo $c->kilos ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <select id="ftipo" class="span12">
                            <option value="1ra" <?php echo $c->tipo == '1ra' ? 'selected' : '' ?>>1ra</option>
                            <option value="2da" <?php echo $c->tipo == '2da' ? 'selected' : '' ?>>2da</option>
                            <option value="3ra" <?php echo $c->tipo == '3ra' ? 'selected' : '' ?>>3ra</option>
                        </select>
                      </td>
                      <td>
                        <button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>
                        <button type="button" class="btn btn-success btn-small" id="btnDelClasif">Eliminar</button>
                      </td>
                    </tr>
                  <?php } ?>

                  <tr>
                    <td>
                      <input type="text" id="ftamano" value="" class="span12 vpositive">
                      <input type="hidden" id="fid_rendimiento" value="" class="span12">
                    </td>
                    <td>
                      <select id="funidad" class="span12">
                        <?php foreach ($unidades as $key => $u) { ?>
                          <option value="<?php echo $u->id_unidad ?>"><?php echo $u->nombre ?></option>
                        <?php } ?>
                      </select>
                    </td>
                    <td>
                      <input type="text" id="fcolor" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="fkilos" value="" class="span12 vpositive">
                    </td>
                    <td>
                      <select id="ftipo" class="span12">
                          <option value="1ra">1ra</option>
                          <option value="2da">2da</option>
                          <option value="3ra">3ra</option>
                      </select>
                    </td>
                    <td>
                      <button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>
                      <button type="button" class="btn btn-success btn-small" id="btnDelClasif">Eliminar</button>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr id="total_entradas">
                    <th colspan="2">
                    </th>
                    <th colspan="2" style="text-align: right;">
                      <span id="ftkilos_rendimientos_spn"><?php echo String::formatoNumero($total_rendimientos, 2, '', false) ?></span>
                      <input type="hidden" id="ftkilos_rendimientos" value="<?php echo $total_rendimientos ?>" class="span12">
                    </th>
                    <th></th>
                  </tr>
                </tfoot>
              </table>

              <table class="table table-striped table-bordered table-hover table-condensed" id="tableTotalEntSal">
                <thead>
                  <tr>
                    <th>1ra</th>
                    <th>2da</th>
                    <th>3ra</th>
                    <th>Merma</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <input type="text" id="ftotal_1ra" value="<?php echo (isset($info['totales'])? $info['totales']->prim: 0) ?>" class="span12 vpositive" readonly>
                      <input type="hidden" id="ftotal_id" value="<?php echo (isset($info['totales'])? $info['totales']->id_pinia_rendtotal: 0) ?>">
                    </td>
                    <td>
                      <input type="text" id="ftotal_2da" value="<?php echo (isset($info['totales'])? $info['totales']->seg: 0) ?>" class="span12 vpositive" readonly>
                    </td>
                    <td>
                      <input type="text" id="ftotal_3ra" value="<?php echo (isset($info['totales'])? $info['totales']->ter: 0) ?>" class="span12 vpositive" readonly>
                    </td>
                    <td>
                      <input type="text" id="ftotal_merma" value="<?php echo (isset($info['totales'])? $info['totales']->merma: 0) ?>" class="span12 vpositive" readonly>
                    </td>
                    <td>
                      <input type="text" id="ftotal" value="<?php echo (isset($info['totales'])? $info['totales']->total: 0) ?>" class="span12 vpositive" readonly>
                    </td>
                  </tr>
                </tbody>
              </table>

              <table class="table table-striped table-bordered table-hover table-condensed" id="tableDanosExt">
                <caption>DAÑOS EXTERNOS</caption>
                <thead>
                  <tr>
                    <th>NOMBRE</th>
                    <th>VALOR %</th>
                    <th>NOMBRE</th>
                    <th>VALOR %</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <input type="text" id="dex_dano_TRASLUCIDEZ_General" value="TRASLUCIDEZ" class="span12" readonly>
                      <input type="hidden" id="dex_parte_TRASLUCIDEZ_General" value="General" class="span12">
                      <input type="hidden" id="dex_id_TRASLUCIDEZ_General"
                        value="<?php echo (isset($info['danios_ext']['_TRASLUCIDEZ_General'])? $info['danios_ext']['_TRASLUCIDEZ_General']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_TRASLUCIDEZ_General" value="<?php echo (isset($info['danios_ext']['_TRASLUCIDEZ_General'])? $info['danios_ext']['_TRASLUCIDEZ_General']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                    <td colspan="2">
                      <strong>Forma</strong>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <input type="text" id="dex_dano_GOLPE_General" value="GOLPE" class="span12" readonly>
                      <input type="hidden" id="dex_parte_GOLPE_General" value="General" class="span12">
                      <input type="hidden" id="dex_id_GOLPE_General" value="<?php echo (isset($info['danios_ext']['_GOLPE_General'])? $info['danios_ext']['_GOLPE_General']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_GOLPE_General" value="<?php echo (isset($info['danios_ext']['_GOLPE_General'])? $info['danios_ext']['_GOLPE_General']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                    <td>
                      <input type="text" id="dex_dano_NORMAL_Forma" value="NORMAL" class="span12" readonly>
                      <input type="hidden" id="dex_parte_NORMAL_Forma" value="Forma" class="span12">
                      <input type="hidden" id="dex_id_NORMAL_Forma" value="<?php echo (isset($info['danios_ext']['_NORMAL_Forma'])? $info['danios_ext']['_NORMAL_Forma']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_NORMAL_Forma" value="<?php echo (isset($info['danios_ext']['_NORMAL_Forma'])? $info['danios_ext']['_NORMAL_Forma']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <input type="text" id="dex_dano_QUEMADESOL_General" value="QUEMA DE SOL" class="span12" readonly>
                      <input type="hidden" id="dex_parte_QUEMADESOL_General" value="General" class="span12">
                      <input type="hidden" id="dex_id_QUEMADESOL_General" value="<?php echo (isset($info['danios_ext']['_QUEMADESOL_General'])? $info['danios_ext']['_QUEMADESOL_General']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_QUEMADESOL_General" value="<?php echo (isset($info['danios_ext']['_QUEMADESOL_General'])? $info['danios_ext']['_QUEMADESOL_General']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                    <td>
                      <input type="text" id="dex_dano_ABOTELLADA_Forma" value="ABOTELLADA" class="span12" readonly>
                      <input type="hidden" id="dex_parte_ABOTELLADA_Forma" value="Forma" class="span12">
                      <input type="hidden" id="dex_id_ABOTELLADA_Forma" value="<?php echo (isset($info['danios_ext']['_ABOTELLADA_Forma'])? $info['danios_ext']['_ABOTELLADA_Forma']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_ABOTELLADA_Forma" value="<?php echo (isset($info['danios_ext']['_ABOTELLADA_Forma'])? $info['danios_ext']['_ABOTELLADA_Forma']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <input type="text" id="dex_dano_PIOJOARENOSO_General" value="PIOJO ARENOSO" class="span12" readonly>
                      <input type="hidden" id="dex_parte_PIOJOARENOSO_General" value="General" class="span12">
                      <input type="hidden" id="dex_id_PIOJOARENOSO_General" value="<?php echo (isset($info['danios_ext']['_PIOJOARENOSO_General'])? $info['danios_ext']['_PIOJOARENOSO_General']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_PIOJOARENOSO_General" value="<?php echo (isset($info['danios_ext']['_PIOJOARENOSO_General'])? $info['danios_ext']['_PIOJOARENOSO_General']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                    <td>
                      <input type="text" id="dex_dano_TORCIDA_Forma" value="TORCIDA" class="span12" readonly>
                      <input type="hidden" id="dex_parte_TORCIDA_Forma" value="Forma" class="span12">
                      <input type="hidden" id="dex_id_TORCIDA_Forma" value="<?php echo (isset($info['danios_ext']['_TORCIDA_Forma'])? $info['danios_ext']['_TORCIDA_Forma']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_TORCIDA_Forma" value="<?php echo (isset($info['danios_ext']['_TORCIDA_Forma'])? $info['danios_ext']['_TORCIDA_Forma']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <input type="text" id="dex_dano_ROEDORES_General" value="ROEDORES" class="span12" readonly>
                      <input type="hidden" id="dex_parte_ROEDORES_General" value="General" class="span12">
                      <input type="hidden" id="dex_id_ROEDORES_General" value="<?php echo (isset($info['danios_ext']['_ROEDORES_General'])? $info['danios_ext']['_ROEDORES_General']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_ROEDORES_General" value="<?php echo (isset($info['danios_ext']['_ROEDORES_General'])? $info['danios_ext']['_ROEDORES_General']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                    <td colspan="2">
                      <strong>Corona</strong>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <input type="text" id="dex_dano_GUSANO_General" value="GUSANO" class="span12" readonly>
                      <input type="hidden" id="dex_parte_GUSANO_General" value="General" class="span12">
                      <input type="hidden" id="dex_id_GUSANO_General" value="<?php echo (isset($info['danios_ext']['_GUSANO_General'])? $info['danios_ext']['_GUSANO_General']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_GUSANO_General" value="<?php echo (isset($info['danios_ext']['_GUSANO_General'])? $info['danios_ext']['_GUSANO_General']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                    <td>
                      <input type="text" id="dex_dano_NORMAL_Corona" value="NORMAL" class="span12" readonly>
                      <input type="hidden" id="dex_parte_NORMAL_Corona" value="Corona" class="span12">
                      <input type="hidden" id="dex_id_NORMAL_Corona" value="<?php echo (isset($info['danios_ext']['_NORMAL_Corona'])? $info['danios_ext']['_NORMAL_Corona']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_NORMAL_Corona" value="<?php echo (isset($info['danios_ext']['_NORMAL_Corona'])? $info['danios_ext']['_NORMAL_Corona']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <input type="text" id="dex_dano_GRILLOS_General" value="GRILLOS" class="span12" readonly>
                      <input type="hidden" id="dex_parte_GRILLOS_General" value="General" class="span12">
                      <input type="hidden" id="dex_id_GRILLOS_General" value="<?php echo (isset($info['danios_ext']['_GRILLOS_General'])? $info['danios_ext']['_GRILLOS_General']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_GRILLOS_General" value="<?php echo (isset($info['danios_ext']['_GRILLOS_General'])? $info['danios_ext']['_GRILLOS_General']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                    <td>
                      <input type="text" id="dex_dano_PEQUEÑA_Corona" value="PEQUEÑA" class="span12" readonly>
                      <input type="hidden" id="dex_parte_PEQUEÑA_Corona" value="Corona" class="span12">
                      <input type="hidden" id="dex_id_PEQUEÑA_Corona" value="<?php echo (isset($info['danios_ext']['_PEQUEÑA_Corona'])? $info['danios_ext']['_PEQUEÑA_Corona']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_PEQUEÑA_Corona" value="<?php echo (isset($info['danios_ext']['_PEQUEÑA_Corona'])? $info['danios_ext']['_PEQUEÑA_Corona']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <input type="text" id="dex_dano_HONGOS_General" value="HONGOS" class="span12" readonly>
                      <input type="hidden" id="dex_parte_HONGOS_General" value="General" class="span12">
                      <input type="hidden" id="dex_id_HONGOS_General" value="<?php echo (isset($info['danios_ext']['_HONGOS_General'])? $info['danios_ext']['_HONGOS_General']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_HONGOS_General" value="<?php echo (isset($info['danios_ext']['_HONGOS_General'])? $info['danios_ext']['_HONGOS_General']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                    <td>
                      <input type="text" id="dex_dano_EXCEDIDA_Corona" value="EXCEDIDA" class="span12" readonly>
                      <input type="hidden" id="dex_parte_EXCEDIDA_Corona" value="Corona" class="span12">
                      <input type="hidden" id="dex_id_EXCEDIDA_Corona" value="<?php echo (isset($info['danios_ext']['_EXCEDIDA_Corona'])? $info['danios_ext']['_EXCEDIDA_Corona']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_EXCEDIDA_Corona" value="<?php echo (isset($info['danios_ext']['_EXCEDIDA_Corona'])? $info['danios_ext']['_EXCEDIDA_Corona']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <input type="text" id="dex_dano_OTROS_General" value="OTROS" class="span12" readonly>
                      <input type="hidden" id="dex_parte_OTROS_General" value="General" class="span12">
                      <input type="hidden" id="dex_id_OTROS_General" value="<?php echo (isset($info['danios_ext']['_OTROS_General'])? $info['danios_ext']['_OTROS_General']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_OTROS_General" value="<?php echo (isset($info['danios_ext']['_OTROS_General'])? $info['danios_ext']['_OTROS_General']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                    <td>
                      <input type="text" id="dex_dano_MULTICORONA_Corona" value="MULTICORONA" class="span12" readonly>
                      <input type="hidden" id="dex_parte_MULTICORONA_Corona" value="Corona" class="span12">
                      <input type="hidden" id="dex_id_MULTICORONA_Corona" value="<?php echo (isset($info['danios_ext']['_MULTICORONA_Corona'])? $info['danios_ext']['_MULTICORONA_Corona']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_MULTICORONA_Corona" value="<?php echo (isset($info['danios_ext']['_MULTICORONA_Corona'])? $info['danios_ext']['_MULTICORONA_Corona']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                  </tr>
                  <tr>
                    <td colspan="2">
                    </td>
                    <td>
                      <input type="text" id="dex_dano_SINCORONA_Corona" value="SIN CORONA" class="span12" readonly>
                      <input type="hidden" id="dex_parte_SINCORONA_Corona" value="Corona" class="span12">
                      <input type="hidden" id="dex_id_SINCORONA_Corona" value="<?php echo (isset($info['danios_ext']['_SINCORONA_Corona'])? $info['danios_ext']['_SINCORONA_Corona']->id_danio_ext: '') ?>" class="span12">
                    </td>
                    <td>
                      <input type="number" id="dex_valor_SINCORONA_Corona" value="<?php echo (isset($info['danios_ext']['_SINCORONA_Corona'])? $info['danios_ext']['_SINCORONA_Corona']->valor: '') ?>" class="span12 vpositive" min="0" max="100">
                    </td>
                  </tr>
                </tbody>
              </table>

              <table class="table table-striped table-bordered table-hover table-condensed" id="tableObsInter">
                <caption>OBSERVACIONES INTERNAS</caption>
                <thead>
                  <tr>
                    <th>CORCHOSIS</th>
                    <th>TRASLUCIDEZ</th>
                    <th>COLOR</th>
                    <th>TAMAÑO</th>
                    <th>BRIX</th>
                    <th>ACCIONES</th>
                  </tr>
                </thead>
                <tbody>

                  <?php
                  foreach ($info['obs_inter'] as $key => $c) {
                  ?>
                    <tr id="<?php echo $c->id_obs_inter ?>">
                      <td>
                        <input type="checkbox" id="fcorchosis" value="" <?php echo $c->corchosis=='t'? 'checked': ''; ?>>
                        <input type="hidden" id="fid_obs_inter" value="<?php echo $c->id_obs_inter ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="ftraslucidez" value="<?php echo $c->traslucidez ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="fcolor" value="<?php echo $c->color ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <input type="text" id="ftamano" value="<?php echo $c->tamano ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <input type="text" id="fbrix" value="<?php echo $c->brix ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <button type="button" class="btn btn-success btn-small" id="btnAddObsInter">Guardar</button>
                        <button type="button" class="btn btn-success btn-small" id="btnDelObsInter">Eliminar</button>
                      </td>
                    </tr>
                  <?php } ?>

                  <tr>
                    <td>
                      <input type="checkbox" id="fcorchosis" value="">
                      <input type="hidden" id="fid_obs_inter" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="ftraslucidez" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="fcolor" value="" class="span12 vpositive">
                    </td>
                    <td>
                      <input type="text" id="ftamano" value="" class="span12 vpositive">
                    </td>
                    <td>
                      <input type="text" id="fbrix" value="" class="span12 vpositive">
                    </td>
                    <td>
                      <button type="button" class="btn btn-success btn-small" id="btnAddObsInter">Guardar</button>
                      <button type="button" class="btn btn-success btn-small" id="btnDelObsInter">Eliminar</button>
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

