<form action="<?php echo base_url('panel/documentos/acomodo_embarque/?id='.$_GET['id']) ?>" method="POST" id="formEmbarque">

  <h3 style="text-align: center;">ACOMODO DEL EMBARQUE</h3><br>

  <input type="hidden" name="embIdDoc" value="<?php echo $idDocumento ?>" id="embIdDoc">
  <input type="hidden" name="embIdFac" value="<?php echo $dataFactura['info']->id_factura ?>" id="embIdFac">
  <input type="hidden" name="embId" value="<?php echo isset($dataEmbarque['info'][0]->id_embarque) ? $dataEmbarque['info'][0]->id_embarque : '' ?>" id="embId">

  <div class="row-fluid">
    <div class="span2">
      <div class="control-group">
        <label class="control-label" for="pctrl_embarque">Ctrl. Embarque</label>
        <div class="controls">
          <input type="text" name="pctrl_embarque" class="span12" id="pctrl_embarque" value="<?php echo set_value('pctrl_embarque', isset($dataEmbarque['info'][0]->ctrl_embarque) ? $dataEmbarque['info'][0]->ctrl_embarque : $dataFactura['info']->id_factura); ?>" autofocus>
        </div>
      </div><!--/control-group -->
    </div>

    <div class="span2">
      <div class="control-group">
        <label class="control-label" for="pfecha">Fecha</label>
        <div class="controls">
          <input type="date" name="pfecha" class="span12" id="pfecha" value="<?php echo set_value('pfecha', isset($dataDocumento->fecha) ? str_replace(' ', 'T', $dataDocumento->fecha) : str_replace(' ', 'T', date("Y-m-d"))); ?>">
        </div>
      </div><!--/control-group -->
    </div>

    <div class="span2">
      <div class="control-group">
        <label class="control-label" for="pfecha_carga">Fecha Carga</label>
        <div class="controls">
          <input type="date" name="pfecha_carga" class="span12" id="pfecha_carga" value="<?php echo set_value('pfecha_carga', isset($dataEmbarque['info'][0]->fecha_carga) ? str_replace(' ', 'T', $dataEmbarque['info'][0]->fecha_carga) : str_replace(' ', 'T', date("Y-m-d"))); ?>">
        </div>
      </div><!--/control-group -->
    </div>

    <div class="span2">
      <div class="control-group">
        <label class="control-label" for="pinicio">Inicio</label>
        <div class="controls">
          <input type="text" name="pinicio" class="span12" id="pinicio" value="<?php echo set_value('pinicio', isset($dataDocumento->inicio) ? $dataDocumento->inicio : ''); ?>">
        </div>
      </div><!--/control-group -->
    </div>

    <div class="span2">
      <div class="control-group">
        <label class="control-label" for="ptermino">Termino</label>
        <div class="controls">
          <input type="text" name="ptermino" class="span12" id="ptermino" value="<?php echo set_value('ptermino', isset($dataDocumento->termino) ? $dataDocumento->termino : ''); ?>">
        </div>
      </div><!--/control-group -->
    </div>

    <div class="span2">
      <div class="control-group">
        <label class="control-label" for="pfecha_empaque">Fecha Empaque</label>
        <div class="controls">
          <input type="date" name="pfecha_empaque" class="span12" id="pfecha_empaque" value="<?php echo set_value('pfecha_empaque', isset($dataEmbarque['info'][0]->fecha_embarque) ? $dataEmbarque['info'][0]->fecha_embarque : str_replace(' ', 'T', date("Y-m-d"))); ?>">
        </div>
      </div><!--/control-group -->
    </div>

  </div><!--/row-fluid -->


  <div class="row-fluid">
    <div class="span2">
      <div class="control-group">
        <label class="control-label" for="pelaboro">Elaboro</label>
        <div class="controls">
          <input type="text" name="pelaboro" class="span12" id="pelaboro" value="<?php echo set_value('pelaboro', isset($dataDocumento->elaboro) ? $dataDocumento->elaboro : $this->session->userdata('usuario')); ?>" readonly>
        </div>
      </div><!--/control-group -->
    </div>

    <div class="span2">
      <div class="control-group">
        <label class="control-label" for="pdestino">Destino</label>
        <div class="controls">
          <input type="text" name="pdestino" class="span12" id="pdestino" value="<?php echo set_value('pdestino', isset($dataDocumento->destino) ? $dataDocumento->destino : $dataFactura['info']->cliente->municipio); ?>">
        </div>
      </div><!--/control-group -->
    </div>

    <div class="span4">
      <div class="control-group">
        <label class="control-label" for="pdestinatario">Destinatario</label>
        <div class="controls">
          <input type="text" name="pdestinatario" class="span12" id="pdestinatario" value="<?php echo set_value('pdestinatario', isset($dataDocumento->destinatario) ? $dataDocumento->destinatario : $dataFactura['info']->cliente->nombre_fiscal); ?>">
        </div>
      </div><!--/control-group -->
    </div>

    <div class="span4">
      <div class="control-group">
        <div class="controls">
          <div class="well span12 pull-right">

            <?php
                $span = '12';
                if (count($dataDocumento) > 0) {
                $span = '6';
              ?>
                <a href="<?php echo base_url($doc->url_print.'?idf='.$dataFactura['info']->id_factura.'&idd='.$idDocumento); ?>" class="btn btn-success btn-large span6" target="_BLANK">Imprimir</a>
            <?php } ?>

            <?php if ($finalizados === 'f') { ?>
              <button type="button" class="btn btn-success btn-large span<?php echo $span ?>" id="sendEmbarque">Guardar</button>
            <?php } ?>

          </div>
        </div>
      </div>
    </div><!--/span4 -->
  </div>

  <div class="row-fluid">

    <div class="span3"><!-- Listado de Pallets libres -->
      <table class="table table-bordered table-condensed datatable">
        <caption>Pallets Libres </caption>
        <thead>
          <tr>
            <th>Folio</th>
            <th>Cajas</th>
            <th>Clasificación(es)</th>
          </tr>

        </thead>
        <tbody>
          <?php foreach ($pallets as $key => $pallet) { ?>
              <tr>
                <td><?php echo $pallet->folio ?></td>
                <td>
                  <div id="draggable" class="ui-widget-content draggableitem" data-id-pallet="<?php echo $pallet->id_pallet ?>" data-cajas="<?php echo $pallet->no_cajas ?>" data-clasificaciones="<?php echo $pallet->clasificaciones ?>" style="z-index: 10;">
                    <p><?php echo $pallet->no_cajas ?></p>
                  </div>
                </td>
                <td><?php echo $pallet->clasificaciones ?></td>
              </tr>
          <?php } ?>

        </tbody>
      </table>

    </div><!--/span3 -->

    <div class="span2"><!-- Caja Camion con los pallets -->

      <table class="table table-striped table-bordered table-hover table-condensed">
        <caption>Track - Pallet Nos.</caption>
        <!-- <thead>
          <tr>
            <th></th>
            <th></th>
          </tr>
        </thead> -->
        <tbody>
          <?php for ($i=1; $i <24 ; $i = $i + 2) {  ?>
              <tr>
                <td><?php echo $i ?></td>
                <td><?php echo $i+1 ?></td>
              </tr>
              <tr>

                <?php
                    $txtDefault1 = 'Vacio';
                    $txtDefault2 = 'Vacio';
                    if (isset($dataEmbarque['pallets']))
                    {
                      foreach ($dataEmbarque['pallets'] as $key => $pallet)
                      {
                        if ($pallet->no_posicion == $i)
                        {
                          if ($pallet->id_pallet != null)
                            $txtDefault1 = $pallet->cajas;
                          else
                            $txtDefault1 = $pallet->descripcion;
                        }

                        if ($pallet->no_posicion == $i+1)
                        {
                          if ($pallet->id_pallet != null)
                            $txtDefault2 = $pallet->cajas;
                          else
                            $txtDefault2 = $pallet->descripcion;
                        }

                        if ($txtDefault1 !== 'Vacio' && $txtDefault2 !== 'Vacio')
                         break;
                      }
                    }
                 ?>
                <td>
                  <div id="droppable" class="ui-widget-header track<?php echo $i ?>" data-no-posicion="<?php echo $i ?>" data-drag="">
                    <p style="text-align: center;"><?php echo $txtDefault1 ?></p>
                  </div>
                </td>
                <td>
                  <div id="droppable" class="ui-widget-header track<?php echo $i+1 ?>" data-no-posicion="<?php echo $i+1 ?>"  data-drag="">
                    <p style="text-align: center;"><?php echo $txtDefault2 ?></p>
                  </div>
                </td>
              </tr>
          <?php } ?>
        </tbody>
      </table>

    </div><!--/span4 -->

    <div class="span7"> <!-- Tabla con el listado de pallets seleccionado -->

      <table class="table table-striped table-bordered table-hover table-condensed" id="tableDatosEmbarque">
        <caption>Datos de Embarque</caption>
        <thead>
          <tr>
            <th>No</th>
            <th>Marca</th>
            <th>Clasificacion(es)</th>
            <th>Cajas</th>
            <th>Temperatura</th>
            <th></th>
          </tr>

        </thead>
        <tbody>
          <?php for ($i=1; $i < 25 ; $i++) {
                  $idPallet       = '';
                  $pmarca         = 'SAN JORGE';
                  $pclasificacion = '';
                  $pcajas         = '';
                  $ptemperatura   = '';
                  $potro          = '';
                  $checked        = '';
                  if (isset($dataEmbarque['pallets']))
                  {
                    foreach ($dataEmbarque['pallets'] as $key => $pallet)
                    {
                      if ($pallet->no_posicion == $i)
                      {
                        $idPallet       = $pallet->id_pallet != null ? $pallet->id_pallet : '';
                        $pmarca         = $pallet->id_pallet != null ? $pallet->marca : $pallet->descripcion;
                        $pclasificacion = $pallet->clasificaciones;
                        $pcajas         = $pallet->cajas;
                        $ptemperatura   = $pallet->temperatura;
                        $potro          = $pallet->id_pallet == null ? 'checked="checked"' : '';
                        break;
                      }
                    }
                  }

          ?>
            <tr id="noPos<?php echo $i ?>">
              <td class="">
                <?php echo $i ?>
                <input type="hidden" name="pno_posicion[]" value="<?php echo $i ?>" id="pno_posicion" class="span4" readonly>
                <input type="hidden" name="pid_pallet[]" value="<?php echo $idPallet ?>" id="pid_pallet" class="span12">
              </td>
              <td><input type="text" name="pmarca[]"  value="<?php echo $pmarca ?>" id="pmarca" class="span12"></td>
              <td><input type="text" name="pclasificacion[]" value="<?php echo $pclasificacion ?>" id="pclasificacion" class="span12" readonly></td>
              <td><input type="text" name="pcajas[]" value="<?php echo $pcajas ?>" id="pcajas" class="span12" readonly></td>
              <td><input type="text" name="ptemperatura[]" value="<?php echo $ptemperatura ?>" id="ptemperatura" class="span12"></td>
              <td><input type="checkbox" name="potro[]" value="<?php echo $i ?>" id="potro" <?php echo $potro ?>></td>
            </tr>
          <?php } ?>
        </tbody>
      </table>


    </div><!--/span4 -->
  </div><!--/row-fluid -->
</form>