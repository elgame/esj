<form action="<?php echo base_url('panel/documentos/chofer_copia_ife/?id='.$_GET['id']) ?>" method="POST" id="formEmbarque" enctype="multipart/form-data">

  <h3 style="text-align: center;">CHOFER COPIA DEL IFE</h3><br>

  <input type="hidden" name="embIdDoc" value="<?php echo $idDocumento ?>" id="embIdDoc">
  <input type="hidden" name="embIdFac" value="<?php echo $dataFactura['info']->id_factura ?>" id="embIdFac">

  <div class="row-fluid">

    <div class="span2 offset3">
      <div class="control-group">
        <label class="control-label" for="pife_file">Copia del IFE</label>
        <div class="controls">
          <input type="file" name="pife_file" class="span12" id="pife_file">
          <label>Marcar como entregada sin subir el archivo:
            <input type="checkbox" name="pife_check" value="si" <?php echo (isset($dataDocumento->check)? 'checked': '') ?>></label>
        </div>
      </div><!--/control-group -->
    </div>

    <div class="span4">
      <div class="control-group">
        <div class="controls">
          <div class="well span12 pull-right">

            <?php
                $span = '12';
                if (isset($dataDocumento->url{0})) {
                $span = '6';
              ?>
                <a href="<?php echo base_url($dataDocumento->url) ?>" class="btn btn-success btn-large span6" rel="superbox-80x600">Ver</a>
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