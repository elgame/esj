<form class="form-horizontal" action="<?php echo base_url('panel/documentos/certificado_tlc/?id='.$_GET['id']) ?>" method="POST" id="formEmbarque">

  <h3 style="text-align: center;">CERTIFICADO DE TLC</h3><br>

  <input type="hidden" name="embIdDoc" value="<?php echo $idDocumento ?>" id="embIdDoc">
  <input type="hidden" name="embIdFac" value="<?php echo $dataFactura['info']->id_factura ?>" id="embIdFac">
  <input type="hidden" name="embId" value="<?php echo isset($dataEmbarque['info'][0]->id_embarque) ? $dataEmbarque['info'][0]->id_embarque : '' ?>" id="embId">

  <div class="row-fluid">

    <div class="span6">

      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5>DATOS EXPORTADOR</h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dempresa">Exportador</label>
        <div class="controls">
          <input type="text" name="dempresa" class="span6" id="dempresa" value="<?php echo set_value('dempresa', isset($dataDocumento->empresa) ? $dataDocumento->empresa : $empresa_default->nombre_fiscal); ?>" autofocus>

          <input type="text" name="dempresa_id" class="span6" id="dempresa_id" value="<?php echo set_value('dempresa_id', isset($dataDocumento->empresa_id) ? $dataDocumento->empresa_id : $empresa_default->id_empresa) ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="ddomicilio">Domicilio</label>
        <div class="controls">
          <input type="text" name="ddomicilio" class="span6" id="ddomicilio" value="<?php echo set_value('ddomicilio', isset($dataDocumento->domicilio) ? $dataDocumento->domicilio : 'KM. 8  CARRETERA TECOMAN PLAYA AZUL'); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dregistroFiscal">No de Registro Fiscal</label>
        <div class="controls">
          <input type="text" name="dregistroFiscal" class="span6" id="dregistroFiscal" value="<?php echo set_value('dregistroFiscal', isset($dataDocumento->registro_fiscal) ? $dataDocumento->registro_fiscal : $empresa_default->rfc); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="ddireccion">Periodo que cubre</label>
        <div class="controls">
          DE <input type="date" name="dfecha1" class="span6" id="dfecha1" value="<?php echo set_value('dfecha1', isset($dataDocumento->fecha1) ? str_replace('', '', $dataDocumento->fecha1) : str_replace(' ', 'T', date("Y-m-d"))); ?>">
          A <input type="date" name="dfecha2" class="span6" id="dfecha2" value="<?php echo set_value('dfecha2', isset($dataDocumento->fecha2) ? str_replace('', '', $dataDocumento->fecha2) : str_replace(' ', 'T', date("Y-m-d"))); ?>">
        </div>
      </div><!--/control-group -->

    </div><!--/span6 -->

    <div class="span6">

      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5>DATOS IMPORTADOR</h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dcliente_tlc">Importador</label>
        <div class="controls">
          <input type="text" name="dcliente_tlc" class="span6" id="dcliente_tlc" value="<?php echo set_value('dcliente_tlc', isset($dataDocumento->cliente) ? $dataDocumento->cliente : $dataFactura['info']->cliente->nombre_fiscal); ?>">

          <input type="text" name="dcliente_id_tlc" class="span6" id="dcliente_id_tlc" value="<?php echo set_value('dcliente_id_tlc', isset($dataDocumento->cliente_id) ? $dataDocumento->cliente_id : $dataFactura['info']->cliente->id_cliente) ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dcliente_domicilio">Domicilio</label>
        <div class="controls">
          <input type="text" name="dcliente_domicilio" class="span6" id="dcliente_domicilio" value="<?php echo set_value('dcliente_domicilio', isset($dataDocumento->cliente_domicilio) ? $dataDocumento->cliente_domicilio : $dataFactura['info']->cliente->calle.' '.$dataFactura['info']->cliente->municipio.' '.$dataFactura['info']->cliente->cp.' '.$dataFactura['info']->cliente->pais); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dcliente_no_reg_fiscal_tlc">Número de Registro Fiscal</label>
        <div class="controls">
          <input type="text" name="dcliente_no_reg_fiscal_tlc" class="span6" id="dcliente_no_reg_fiscal_tlc" value="<?php echo set_value('dcliente_no_reg_fiscal_tlc', isset($dataDocumento->cliente_no_reg_fiscal) ? $dataDocumento->cliente_no_reg_fiscal : $dataFactura['info']->cliente->rfc); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5>OTROS DATOS</h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dtelefono">Teléfono</label>
        <div class="controls">
          <input type="text" name="dtelefono" class="span6" id="dtelefono" value="<?php echo set_value('dtelefono', isset($dataDocumento->telefono) ? $dataDocumento->telefono : '313 32 44420'); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dfax">Fax</label>
        <div class="controls">
          <input type="text" name="dfax" class="span6" id="dfax" value="<?php echo set_value('dfax', isset($dataDocumento->fax) ? $dataDocumento->fax : '313 32 44420'); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <div class="controls">
          <div class="well span6">

            <?php
                $span = '12';
                if (count($dataDocumento) > 0) {
                $span = '6';
              ?>
                <a href="<?php echo base_url($doc->url_print.'?idf='.$dataFactura['info']->id_factura.'&idd='.$idDocumento); ?>" class="btn btn-success btn-large span6" target="_BLANK">Imprimir</a>
            <?php } ?>

            <?php if ($finalizados === 'f') { ?>
              <button type="submit" class="btn btn-success btn-large span<?php echo $span ?>">Guardar</button>
            <?php } ?>

          </div>
        </div>
      </div>

    </div><!--/span6 -->

  </div><!--/row-fluid -->

</form>