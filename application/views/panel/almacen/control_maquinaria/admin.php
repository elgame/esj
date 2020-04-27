    <div id="content" class="span10">
      <!-- content starts -->
      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>Control de maquinaria</li>
        </ul>
      </div>

      <div class="row-fluid" id="box-cajas"><!--cajas-->
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-bar-chart"></i> Control de maquinaria</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content" id="box-content">
            <div class="row-fluid">

              <form action="<?php echo base_url('panel/control_maquinaria?'.MyString::getVarsLink(array('msg'))); ?>" method="GET" class="form-horizontal" id="form">

                <div class="control-group span7">
                  <table class="table">
                    <thead>
                      <tr class="center">
                        <th style="background-color: #FFF; text-align: center;" class="center">Fecha</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>
                          <input type="date" name="gfecha" value="<?php echo set_value_get('gfecha', $fecha); ?>" id="gfecha" class="span8"
                            style="margin: -7px auto 0 auto; text-align: center;" maxlength="10" autofocus>
                        </td>
                        <td><button type="submit" class="btn">Cargar</button></td>
                      </tr>
                    </tbody>
                  </table>
                </div>

              </form>

              <table class="table table-striped table-bordered table-hover table-condensed" id="tableClasif">
                <caption></caption>
                <thead>
                  <tr>
                    <th>Hr Carga</th>
                    <th>Activo</th>
                    <th>Labor</th>
                    <th>Implemento</th>
                    <th>Lts Combustible</th>
                    <th>Precio</th>
                    <th>Hor Inicio</th>
                    <th>Hor Final</th>
                    <th style="width:55px;">Total Hrs</th>
                    <th style="width:55px;">Lts/Hrs</th>
                    <th>Km Inicio</th>
                    <th>Km Final</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>

                  <?php foreach ($combustible['combustible'] as $key => $c) { ?>
                    <tr>
                      <td>
                        <input type="time" id="fhora_carga" value="<?php echo substr($c->hora_carga, 0, 8) ?>" class="span11 pull-left fhora_carga">
                      </td>
                      <td>
                        <input type="text" name="factivos" class="span11 factivos" value="<?php echo $c->activo ?>" placeholder="Nissan FRX, Maquina limon">
                        <input type="hidden" name="factivoId" class="factivoId" value="<?php echo $c->id_activo ?>">

                        <input type="hidden" id="fid_combustible" value="<?php echo $c->id_combustible ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="flabor" value="<?php echo $c->labor ?>" class="span12 showLabores">
                        <input type="hidden" id="flabor_id" value="<?php echo $c->id_labor ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="fimplemento" value="<?php echo $c->implemento ?>" class="span11 pull-left fimplemento">
                      </td>
                      <td>
                        <input type="number" id="flts_combustible" value="<?php echo $c->lts_combustible ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <input type="number" id="fprecio" value="<?php echo $c->precio ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <input type="number" name="fhorometro" id="fhorometro" value="<?php echo $c->horometro; ?>" class="span12">
                      </td>
                      <td>
                        <input type="number" name="fhorometro_fin" id="fhorometro_fin" value="<?php echo $c->horometro_fin; ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="ftotal_hrs" value="<?php echo $c->horas_totales ?>" class="span12" readonly>
                      </td>
                      <td>
                        <input type="text" id="flitro_hr" value="<?php echo round($c->lts_combustible/($c->horas_totales>0?$c->horas_totales:1), 2) ?>" class="span12" readonly>
                      </td>
                      <td>
                        <input type="number" name="fodometro" id="fodometro" value="<?php echo $c->odometro; ?>" class="span12">
                      </td>
                      <td>
                        <input type="number" name="fodometro_fin" id="fodometro_fin" value="<?php echo $c->odometro_fin; ?>" class="span12">
                      </td>
                      <td>
                        <button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>
                        <button type="button" class="btn btn-danger btn-small" id="btnDelClasif">Eliminar</button>
                      </td>
                    </tr>
                  <?php } ?>

                  <!-- <tr>
                    <td>
                      <input type="time" id="fhora_carga" value="" class="span11 pull-left fhora_carga">
                    </td>
                    <td>
                      <input type="text" name="factivos" class="span11 factivos" value="" placeholder="Nissan FRX, Maquina limon">
                      <input type="hidden" name="factivoId" class="factivoId" value="">

                      <input type="hidden" id="fid_combustible" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="flabor" value="" class="span12 showLabores">
                      <input type="hidden" id="flabor_id" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="fimplemento" value="" class="span11 pull-left fimplemento">
                    </td>
                    <td style="width: 100px;">
                      <input type="number" id="flts_combustible" value="" class="span12 vpositive">
                    </td>
                    <td style="width: 100px;">
                      <input type="number" id="fprecio" value="" class="span12 vpositive">
                    </td>
                    <td style="width: 100px;">
                      <input type="number" name="fodometro" id="fodometro" value="" class="span12">
                    </td>
                    <td style="width: 100px;">
                      <input type="number" name="fodometro_fin" id="fodometro_fin" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="ftotal_hrs" value="" class="span12" readonly>
                    </td>
                    <td>
                      <input type="text" id="flitro_hr" value="" class="span12" readonly>
                    </td>
                    <td style="width: 60px;">
                      <button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>
                      <button type="button" class="btn btn-danger btn-small" id="btnDelClasif">Eliminar</button>
                    </td>
                  </tr> -->
                </tbody>
              </table>

            </div>
          </div><!--/box-content-->
        </div><!--/box span12-->
      </div><!--/row-fluid cajas-->

      <div class="form-actions">
      </div>


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

