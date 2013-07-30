<form action="<?php echo base_url('panel/documentos/chofer_copia_licencia/?id='.$_GET['id']) ?>" method="POST" enctype="multipart/form-data">

  <h3 style="text-align: center;">CHOFER COPIA LICENCIA</h3><br>

  <input type="hidden" name="embIdDoc" value="<?php echo $idDocumento ?>" id="embIdDoc">
  <input type="hidden" name="embIdFac" value="<?php echo $dataFactura['info']->id_factura ?>" id="embIdFac">

  <div class="row-fluid">

    <div class="span2 offset3">
      <div class="control-group">
        <label class="control-label" for="plicencia_file">Copia Licencia</label>
        <div class="controls">
          <input type="file" name="plicencia_file" class="span12" id="plicencia_file">
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
                <a href="<?php echo base_url($dataDocumento->url); ?>" class="btn btn-success btn-large span6" target="_BLANK">Ver</a>
            <?php } ?>

            <?php if ($finalizados === 'f') { ?>
              <button type="submit" class="btn btn-success btn-large span<?php echo $span ?>">Guardar</button>
            <?php } ?>

          </div>
        </div>
      </div>
    </div><!--/span4 -->

  </div><!--/row-fluid -->

</form>