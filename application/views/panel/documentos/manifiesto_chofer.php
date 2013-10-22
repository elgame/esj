<form class="form-horizontal" action="<?php echo base_url('panel/facturacion/agregar'); ?>" method="POST" id="formManifiestoChofer">

  <h3 style="text-align: center;">MANIFIESTO CHOFER</h3><br>

  <div class="row-fluid">
    <div class="span6">

      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5>DATOS FACTURA</h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dfolio">Folio Factura</label>
        <div class="controls">
          <input type="text" name="dfolio" class="span6" id="dfolio" value="<?php echo set_value('dfolio', isset($dataDocumento->folio) ? $dataDocumento->folio : $dataFactura['info']->folio); ?>" autofocus>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dimporte">Importe</label>
        <div class="controls">
          <input type="text" name="dimporte" class="span6" id="dimporte" value="<?php echo set_value('dimporte', isset($dataDocumento->importe) ? $dataDocumento->importe : $dataFactura['info']->total); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="ddireccion">Direccion</label>
        <div class="controls">
          <input type="text" name="ddireccion" class="span6" id="ddireccion" value="<?php echo set_value('ddireccion', isset($dataDocumento->direccion) ? $dataDocumento->direccion : $dataFactura['info']->cliente->municipio); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dcliente">Cliente</label>
        <div class="controls">
          <input type="text" name="dcliente" class="span6" id="dcliente" value="<?php echo set_value('dcliente', isset($dataDocumento->cliente) ? $dataDocumento->cliente : $dataFactura['info']->cliente->nombre_fiscal); ?>">
          <input type="hidden" name="did_cliente" class="span6" id="did_cliente" value="<?php echo set_value('did_cliente', isset($dataDocumento->id_cliente) ? $dataDocumento->folio : $dataFactura['info']->id_cliente); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dfecha">Fecha</label>
        <div class="controls">
          <!-- <input type="text" name="dfecha" class="span6" id="dfecha" value="<?php //echo set_value('dfecha', isset($dataDocumento->fecha) ? $dataDocumento->fecha : ''); ?>"> -->
          <input type="date" name="dfecha" class="span6" id="dfecha" value="<?php echo set_value('dfecha', isset($dataDocumento->fecha) ? str_replace('', '', $dataDocumento->fecha) : str_replace(' ', 'T', date("Y-m-d"))); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5>DATOS LINEA TRANSPORTISTA</h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dlinea_trans">Linea Transportista</label>
        <div class="controls">
          <input type="text" name="dlinea_trans" class="span6" id="dlinea_trans" value="<?php echo set_value('dlinea_trans', isset($dataDocumento->linea_trans) ? $dataDocumento->linea_trans : ''); ?>">
          <input type="hidden" name="did_linea" class="span6" id="did_linea" value="<?php echo set_value('did_linea', isset($dataDocumento->linea_id) ? $dataDocumento->linea_id : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dlinea_tel">Tel</label>
        <div class="controls">
          <input type="text" name="dlinea_tel" class="span6" id="dlinea_tel" value="<?php echo set_value('dlinea_tel', isset($dataDocumento->linea_tel) ? $dataDocumento->linea_tel : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dlinea_ID">ID</label>
        <div class="controls">
          <input type="text" name="dlinea_ID" class="span6" id="dlinea_ID" value="<?php echo set_value('dlinea_ID', isset($dataDocumento->linea_ID) ? $dataDocumento->linea_ID : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dno_carta_porte">No. Carta Porte</label>
        <div class="controls">
          <input type="text" name="dno_carta_porte" class="span6" id="dno_carta_porte" value="<?php echo set_value('dno_carta_porte', isset($dataDocumento->no_carta_porte) ? $dataDocumento->no_carta_porte : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5>AREA Y TICKET PESADA</h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="darea">Area</label>
        <div class="controls">
          <select name="darea" class="span6" id="darea">
            <option value=""></option>
            <?php foreach ($dataAreas['areas'] as $area){ ?>
              <option value="<?php echo $area->id_area ?>"
                <?php echo set_select('darea', $area->id_area, false, isset($dataDocumento->area_id) ? $dataDocumento->area_id : ($area->predeterminado == 't' ? $area->id_area: '') ) ?>><?php echo $area->nombre ?></option>
            <?php } ?>
          </select>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dchofer">No. Ticket Pesada</label>
        <div class="controls">
          <div class="input-append">
            <input class="span6" id="ticket" type="text" value="<?php echo set_value('', isset($dataDocumento->no_ticket) ? $dataDocumento->no_ticket : '') ?>"><button class="btn" type="button" id="loadTicket">Cargar</button>
          </div>
        </div>
      </div><!--/control-group -->

    </div><!--/span6 -->

    <div class="span6">

      <!-- DATOS DEL CHOFER -->
      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5>DATOS DEL CHOFER</h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group" id="alertChofer" style="display: none;">
        <label class="control-label"></label>
        <div class="controls">
            <div class="alert span6">
              <!-- <button type="button" class="close" data-dismiss="alert">&times;</button> -->
              <strong>El chofer no cuenta con la copia de la licencia y/o IFE.</strong>
            </div>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dchofer">Chofer</label>
        <div class="controls">
          <input type="text" name="dchofer" class="span6" id="dchofer" value="<?php echo set_value('dchofer', isset($dataDocumento->chofer) ? $dataDocumento->chofer : ''); ?>">
          <input type="hidden" name="did_chofer" class="span6" id="did_chofer" value="<?php echo set_value('did_chofer', isset($dataDocumento->chofer_id) ? $dataDocumento->chofer_id : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dchofer_tel">Teléfono</label>
        <div class="controls">
          <input type="text" name="dchofer_tel" class="span6" id="dchofer_tel" value="<?php echo set_value('dchofer_tel', isset($dataDocumento->chofer_tel) ? $dataDocumento->chofer_tel : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dchofer_ID">ID</label>
        <div class="controls">
          <input type="text" name="dchofer_ID" class="span6" id="dchofer_ID" value="<?php echo set_value('dchofer_ID', isset($dataDocumento->chofer_ID) ? $dataDocumento->chofer_ID : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dchofer_no_licencia">No. Licencia</label>
        <div class="controls">
          <input type="text" name="dchofer_no_licencia" class="span6" id="dchofer_no_licencia" value="<?php echo set_value('dchofer_no_licencia', isset($dataDocumento->chofer_no_licencia) ? $dataDocumento->chofer_no_licencia : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dchofer_ife">No. IFE</label>
        <div class="controls">
          <input type="text" name="dchofer_ife" class="span6" id="dchofer_ife" value="<?php echo set_value('dchofer_ife', isset($dataDocumento->chofer_ife) ? $dataDocumento->chofer_ife : ''); ?>">
        </div>
      </div><!--/control-group -->

      <!-- DATOS DEL CAMION -->
      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5>DATOS DEL CAMION</h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dcamion_placas">Camion Placas</label>
        <div class="controls">
          <input type="text" name="dcamion_placas" class="span6" id="dcamion_placas" value="<?php echo set_value('dcamion_placas', isset($dataDocumento->camion_placas) ? $dataDocumento->camion_placas : ''); ?>">
          <input type="hidden" name="did_camion" class="span6" id="did_camion" value="<?php echo set_value('did_camion', isset($dataDocumento->camion_id) ? $dataDocumento->camion_id : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dcamion_placas_econ">No. ECON</label>
        <div class="controls">
          <input type="text" name="dcamion_placas_econ" class="span6" id="dcamion_placas_econ" value="<?php echo set_value('dcamion_placas_econ', isset($dataDocumento->camion_placas_econ) ? $dataDocumento->camion_placas_econ : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dcamion_marca">Marca</label>
        <div class="controls">
          <input type="text" name="dcamion_marca" class="span6" id="dcamion_marca" value="<?php echo set_value('dcamion_marca', isset($dataDocumento->camion_marca) ? $dataDocumento->camion_marca : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dcamion_model">Modelo</label>
        <div class="controls">
          <input type="text" name="dcamion_model" class="span6" id="dcamion_model" value="<?php echo set_value('dcamion_model', isset($dataDocumento->camion_model) ? $dataDocumento->camion_model : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dcamion_color">Color</label>
        <div class="controls">
          <input type="text" name="dcamion_color" class="span6" id="dcamion_color" value="<?php echo set_value('dcamion_color', isset($dataDocumento->camion_color) ? $dataDocumento->camion_color : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dcamion_placas_termo">Placas Termo</label>
        <div class="controls">
          <input type="text" name="dcamion_placas_termo" class="span6" id="dcamion_placas_termo" value="<?php echo set_value('dcamion_placas_termo', isset($dataDocumento->camion_placas_termo) ? $dataDocumento->camion_placas_termo : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dcamion_placas_termo_econ">No. ECON</label>
        <div class="controls">
          <input type="text" name="dcamion_placas_termo_econ" class="span6" id="dcamion_placas_termo_econ" value="<?php echo set_value('dcamion_placas_termo_econ', isset($dataDocumento->camion_placas_termo_econ) ? $dataDocumento->camion_placas_termo_econ : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <div class="controls">
          <div class="well span6">
            <div class="row-fluid">
              <?php if ($finalizados === 'f'){ ?>
              <button type="button" class="btn btn-success btn-large span12" id="btnSave">Guardar</button>
              <?php } ?>
            </div>

            <?php if (count($dataDocumento) > 0) { ?>
              <br>
              <div class="row-fluid">
                <a href="<?php echo base_url($doc->url_print.'?idf='.$dataFactura['info']->id_factura.'&idd='.$idDocumento); ?>" class="btn btn-success btn-large span12" target="_BLANK">Imprimir</a>
              </div>
            <?php } ?>

          </div>
        </div>
      </div>

    </div><!--/span6 -->

  </div><!--/row-fluid -->
</form><!--/form -->