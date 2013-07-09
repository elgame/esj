    <div id="content" class="span10">
      <!-- content starts -->
      <input type="text" value="<?php echo $lote_actual ?>" id="loteActual">
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
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <div class="row-fluid">

              <form action="<?php echo base_url('panel/rastreabilidad/rendimiento_lote?'.String::getVarsLink(array('msg'))); ?>" method="GET" class="form-horizontal" id="form">

                <div class="control-group span6">
                  <table class="table">
                    <thead>
                      <tr class="center">
                        <th style="background-color: #FFF; text-align: center;" class="center">Fecha</th>
                        <th style="background-color: #FFF; text-align: center;" class="center">Semana</th>
                        <th style="background-color: #FFF; text-align: center;">Dia</th>
                        <th style="background-color: #FFF; text-align: center;">Lote</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>
                          <input type="text" name="gfecha" value="<?php echo set_value_get('gfecha', $fecha); ?>" id="gfecha" class="span6"
                            style="margin: -7px auto 0 auto; text-align: center;" maxlength="10">
                        </td>
                        <td style="text-align: center;"><span class="label label-important" style="font-size: 1.4em;"><?php echo $semana ?></span></td>
                        <td style="text-align: center;"><span class="label label-important" style="font-size: 1.4em;"><?php echo $dia_semana ?></span></td>
                        <td style="text-align: center;">
                          <select name="glote" id="glote" class="span12" style="margin: -7px auto 0 auto;">
                            <option value=""></option>
                            <?php foreach ($lotes as $key => $lote) { ?>
                              <option value="<?php echo $lote->id_rendimiento ?>" <?php echo set_select_get('glote', $lote->id_rendimiento, false); ?>><?php echo $lote->lote ?></option>
                            <?php } ?>
                          </select>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div class="span2"></div>
                <div class="span2">
                  <?php if ($ant_lote >= 1) { ?>
                    <a class="btn btn-success pull-left" href="<?php echo base_url('panel/rastreabilidad/siguiente_lote?glote='.$ant_lote.'&gfecha='.$fecha); ?>">Anterior Lote</a>
                  <?php } ?>
                  <a class="btn btn-success pull-right" href="<?php echo base_url('panel/rastreabilidad/siguiente_lote?glote='.$sig_lote.'&gfecha='.$fecha); ?>">Siguiente Lote</a>
                </div>
                <div class="span2"></div>

              </form>

              <table class="table table-striped table-bordered table-hover table-condensed" id="tableClasif">
                <caption></caption>
                <thead>
                  <tr>
                    <th>CLASIFICACIÓN</th>
                    <th>EXISTENTE</th>
                    <th>LINEA 1</th>
                    <th>LINEA 2</th>
                    <th>TOTAL</th>
                    <th>RENDIMIENTO</th>
                    <th>ACCIONES</th>
                  </tr>
                </thead>
                <tbody>

                  <?php foreach ($clasificaciones['clasificaciones'] as $key => $c) { ?>
                    <tr>
                      <td>
                        <input type="text" id="fclasificacion" value="<?php echo $c->clasificacion ?>" class="span12">
                        <input type="text" id="fidclasificacion" value="<?php echo $c->id_clasificacion ?>" class="span12">
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
                        <input type="text" id="ftotal" value="<?php echo $c->total ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <span id="frd-span"><?php echo $c->rendimiento ?></span>
                        <input type="text" id="frd" value="<?php echo $c->rendimiento ?>" class="span12 vpositive">
                      </td>
                      <td>
                        <button type="button" class="btn btn-success btn-small" id="btnAddClasif">Guardar</button>
                        <button type="button" class="btn btn-success btn-small" id="btnDelClasif">Eliminar</button>
                      </td>
                    </tr>
                  <?php } ?>

                  <tr>
                    <td>
                      <input type="text" id="fclasificacion" value="" class="span12">
                      <input type="text" id="fidclasificacion" value="" class="span12">
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
                      <input type="text" id="ftotal" value="0" class="span12 vpositive">
                    </td>
                    <td>
                      <span id="frd-span">0</span>
                      <input type="text" id="frd" value="0" class="span12 vpositive">
                    </td>
                    <td>
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

