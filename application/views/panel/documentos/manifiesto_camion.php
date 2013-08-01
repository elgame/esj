<form class="form-horizontal" action="<?php echo base_url('panel/documentos/manifiesto_camion/?id='.$_GET['id']) ?>" method="POST" id="formManifiestoCamion">

  <h3 style="text-align: center;">MANIFIESTO DEL CAMIÓN</h3><br>

  <input type="hidden" name="embIdDoc" value="<?php echo $idDocumento ?>" id="embIdDoc">
  <input type="hidden" name="embIdFac" value="<?php echo $dataFactura['info']->id_factura ?>" id="embIdFac">
  <input type="hidden" name="embId" value="<?php echo isset($dataEmbarque['info'][0]->id_embarque) ? $dataEmbarque['info'][0]->id_embarque : '' ?>" id="embId">

  <div class="row-fluid">

    <div class="span6">

      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5></h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dremitente">Remitente</label>
        <div class="controls">
          <input type="text" name="dremitente" class="span6" id="dremitente" value="<?php echo set_value('dremitente', isset($dataDocumento->remitente) ? $dataDocumento->remitente : $dataFactura['info']->empresa->nombre_fiscal); ?>" autofocus>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dconsignatorio">Consignatorio</label>
        <div class="controls">
          <input type="text" name="dconsignatorio" class="span6" id="dconsignatorio" value="<?php echo set_value('dconsignatorio', isset($dataDocumento->consignatorio) ? $dataDocumento->consignatorio : $dataFactura['info']->cliente->nombre_fiscal); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dfecha_embarque">Fecha de Embarque</label>
        <div class="controls">
          <input type="date" name="dfecha_embarque" class="span6" id="dfecha_embarque" value="<?php echo set_value('dfecha_embarque', isset($dataDocumento->fecha_embarque) ? $dataDocumento->fecha_embarque : $dataEmbarque['info'][0]->fecha_embarque); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label"></label>
        <div class="controls">
          <table class="table table-striped table-bordered table-hover table-condensed">
            <caption>Clasificaciones</caption>
            <thead>
              <tr>
                <th>Clasificacion</th>
                <th>Cajas</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($dataClasificaciones['clasificaciones'] as $key => $clasifi) { ?>
                      <tr>
                        <td><?php echo $clasifi->clasificacion ?></td>
                        <td><?php echo $clasifi->cajas ?></td>
                      </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </div><!--/control-group -->

    </div><!--/span6 -->

    <div class="span6">

      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5>DATOS CAMION</h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dmc_camion_placas">Camion Placas</label>
        <div class="controls">
          <input type="text" name="dmc_camion_placas" class="span6" id="dmc_camion_placas" value="<?php echo set_value('dmc_camion_placas', isset($dataDocumento->camion_placas) ? $dataDocumento->camion_placas : $dataManChofer->camion_placas); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dmc_caja_no">Caja No</label>
        <div class="controls">
          <input type="text" name="dmc_caja_no" class="span6" id="dmc_caja_no" value="<?php echo set_value('dmc_caja_no', isset($dataDocumento->caja_no) ? $dataDocumento->caja_no : $dataManChofer->camion_placas_termo.' NUMERO ECONOMICO '.$dataManChofer->camion_placas_termo_econ); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dmc_linea_transporte">Linea de Transporte</label>
        <div class="controls">
          <input type="text" name="dmc_linea_transporte" class="span6" id="dmc_linea_transporte" value="<?php echo set_value('dmc_linea_transporte', isset($dataDocumento->linea_transporte) ? $dataDocumento->linea_transporte : $dataManChofer->linea_trans); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5>DATOS ADICIONALES TRANSPORTISTA</h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dmc_razon_social">Nombre o Razon Social</label>
        <div class="controls">
          <input type="text" name="dmc_razon_social" class="span6" id="dmc_razon_social" value="<?php echo set_value('dmc_razon_social', isset($dataDocumento->razon_social) ? $dataDocumento->razon_social : $dataManChofer->linea_trans); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dmc_domicilio_fiscal">Domicilio Fiscal</label>
        <div class="controls">
          <input type="text" name="dmc_domicilio_fiscal" class="span6" id="dmc_domicilio_fiscal" value="<?php echo set_value('dmc_domicilio_fiscal', isset($dataDocumento->domicilio_fiscal) ? $dataDocumento->domicilio_fiscal : 'GUADALUPE NO. 30 COL. SAN PABLO CP.44279 ENCARNACION DE DIOS JALISCO'); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dmc_rfc">RFC</label>
        <div class="controls">
          <input type="text" name="dmc_rfc" class="span6" id="dmc_rfc" value="<?php echo set_value('dmc_rfc', isset($dataDocumento->rfc) ? $dataDocumento->rfc : 'TEMO20524ST7'); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dmc_curp">CURP</label>
        <div class="controls">
          <input type="text" name="dmc_curp" class="span6" id="dmc_curp" value="<?php echo set_value('dmc_curp', isset($dataDocumento->curp) ? $dataDocumento->curp : ''); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <div class="controls">
          <div class="well span6">

            <?php if (count($dataDocumento) > 0) { ?>
            <div class="row-fluid">
              <a href="<?php echo base_url($doc->url_print.'?idf='.$dataFactura['info']->id_factura.'&idd='.$idDocumento); ?>" class="btn btn-success btn-large span12" target="_BLANK">Imprimir</a>
            </div>
            <?php } ?>

            <?php if ($finalizados === 'f') { ?>
              <br>
              <div class="row-fluid">
                <button type="submit" class="btn btn-success btn-large span12">Guardar</button>
              </div>
            <?php } ?>

          </div>
        </div>
      </div>

    </div><!--/span6 -->

  </div><!--/row-fluid -->

</form>