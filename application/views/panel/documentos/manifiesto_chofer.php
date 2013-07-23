<form class="form-horizontal" action="<?php echo base_url('panel/facturacion/agregar'); ?>" method="POST" id="form">

  <h3 style="text-align: center;">MANIFIESTO DEL CHOFER</h3><br>

  <div class="row-fluid">
    <div class="span6">

      <div class="control-group">
        <label class="control-label" for="dfolio">Folio Factura</label>
        <div class="controls">
          <input type="text" name="dfolio" class="span6" id="dfolio" value="<?php echo set_value('dfolio', $dataFactura['info']->folio); ?>" autofocus>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dimporte">Importe</label>
        <div class="controls">
          <input type="text" name="dimporte" class="span6" id="dimporte" value="<?php echo set_value('dimporte', $dataFactura['info']->total); ?>" autofocus>
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
          <input type="text" name="dlinea_trans" class="span6" id="dlinea_trans" value="<?php echo set_value('dlinea_trans'); ?>">
          <input type="text" name="did_linea" class="span6" id="did_linea" value="<?php echo set_value('did_linea'); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dlinea_tel">Tel</label>
        <div class="controls">
          <input type="text" name="dlinea_tel" class="span6" id="dlinea_tel" value="<?php echo set_value('dlinea_tel'); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dlinea_ID">ID</label>
        <div class="controls">
          <input type="text" name="dlinea_ID" class="span6" id="dlinea_ID" value="<?php echo set_value('dlinea_ID'); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dno_carta_porte">No. Carta Porte</label>
        <div class="controls">
          <input type="text" name="dno_carta_porte" class="span6" id="dno_carta_porte" value="<?php echo set_value('dno_carta_porte'); ?>">
        </div>
      </div><!--/control-group -->

    </div><!--/span6 -->

    <div class="span6">

      <div class="control-group">
        <label class="control-label" for="dfolio"></label>
        <div class="controls">
          <h5>DATOS DEL CHOFER Y CAMION</h5>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dchofer">No. Ticket Pesada</label>
        <div class="controls">
          <div class="input-append">
            <input class="span6" id="ticket" type="text"><button class="btn" type="button" id="loadTicket">Cargar</button>
          </div>
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dlinea_tel">Tel</label>
        <div class="controls">
          <input type="text" name="dlinea_tel" class="span6" id="dlinea_tel" value="<?php echo set_value('dlinea_tel'); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dlinea_ID">ID</label>
        <div class="controls">
          <input type="text" name="dlinea_ID" class="span6" id="dlinea_ID" value="<?php echo set_value('dlinea_ID'); ?>">
        </div>
      </div><!--/control-group -->

      <div class="control-group">
        <label class="control-label" for="dno_carta_porte">No. Carta Porte</label>
        <div class="controls">
          <input type="text" name="dno_carta_porte" class="span6" id="dno_carta_porte" value="<?php echo set_value('dno_carta_porte'); ?>">
        </div>
      </div><!--/control-group -->

    </div><!--/span6 -->

  </div><!--/row-fluid -->
</form><!--/form -->