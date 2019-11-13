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

              <form action="<?php echo base_url('panel/control_maquinaria?'.String::getVarsLink(array('msg'))); ?>" method="GET" class="form-horizontal" id="form">

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
                    <th>Centro Costo</th>
                    <th>Labor</th>
                    <th>Implemento</th>
                    <th>Lts Combustible</th>
                    <th>Hr Inicio</th>
                    <th>Hr Final</th>
                    <th style="width:55px;">Total Hrs</th>
                    <th style="width:55px;">Lts/Hrs</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>

                  <?php foreach ($combustible['combustible'] as $key => $c) { ?>
                    <tr>
                      <td>
                        <input type="text" id="fcentro_costo" value="<?php echo $c->centro_costo ?>" class="span11 pull-left showCodigoAreaAuto">
                        <input type="hidden" id="fcentro_costo_id" value="<?php echo $c->id_centro_costo ?>" class="span12">
                        <i class="ico icon-list pull-right showCodigoArea" style="cursor:pointer"></i>

                        <input type="hidden" id="fid_combustible" value="<?php echo $c->id_combustible ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="flabor" value="<?php echo $c->labor ?>" class="span12 showLabores">
                        <input type="hidden" id="flabor_id" value="<?php echo $c->id_labor ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="fimplemento" value="<?php echo $c->implemento ?>" class="span11 pull-left showCodigoAreaAuto">
                        <input type="hidden" id="fimplemento_id" value="<?php echo $c->id_implemento ?>" class="span12">
                        <i class="ico icon-list pull-right showCodigoArea" style="cursor:pointer"></i>
                      </td>
                      <td>
                        <input type="number" id="flts_combustible" value="<?php echo $c->lts_combustible ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <input type="time" name="fhr_ini" id="fhr_ini" value="<?php echo substr($c->hora_inicial, 0, 5); ?>" class="span12">
                      </td>
                      <td>
                        <input type="time" name="fhr_fin" id="fhr_fin" value="<?php echo substr($c->hora_final, 0, 5); ?>" class="span12">
                      </td>
                      <td>
                        <input type="text" id="ftotal_hrs" value="<?php echo $c->horas_totales ?>" class="span12" readonly>
                      </td>
                      <td>
                        <input type="text" id="flitro_hr" value="<?php echo ($c->lts_combustible/($c->horas_totales>0?$c->horas_totales:1)) ?>" class="span12" readonly>
                      </td>
                      <td>
                        <button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>
                        <button type="button" class="btn btn-danger btn-small" id="btnDelClasif">Eliminar</button>
                      </td>
                    </tr>
                  <?php } ?>

                  <tr>
                    <td>
                      <input type="text" id="fcentro_costo" value="" class="span11 pull-left showCodigoAreaAuto">
                      <input type="hidden" id="fcentro_costo_id" value="" class="span12">
                      <i class="ico icon-list pull-right showCodigoArea" style="cursor:pointer"></i>

                      <input type="hidden" id="fid_combustible" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="flabor" value="" class="span12 showLabores">
                      <input type="hidden" id="flabor_id" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="fimplemento" value="" class="span11 pull-left showCodigoAreaAuto">
                      <input type="hidden" id="fimplemento_id" value="" class="span12">
                      <i class="ico icon-list pull-right showCodigoArea" style="cursor:pointer"></i>
                    </td>
                    <td>
                      <input type="number" id="flts_combustible" value="" class="span12 vpositive">
                    </td>
                    <td>
                      <input type="time" name="fhr_ini" id="fhr_ini" value="" class="span12">
                    </td>
                    <td>
                      <input type="time" name="fhr_fin" id="fhr_fin" value="" class="span12">
                    </td>
                    <td>
                      <input type="text" id="ftotal_hrs" value="" class="span12" readonly>
                    </td>
                    <td>
                      <input type="text" id="flitro_hr" value="" class="span12" readonly>
                    </td>
                    <td>
                      <button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>
                      <button type="button" class="btn btn-danger btn-small" id="btnDelClasif">Eliminar</button>
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

