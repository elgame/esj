<form action="<?php echo base_url('panel/documentos/chofer_copia_licencia/?id='.$_GET['id']) ?>" method="POST" enctype="multipart/form-data">

  <h3 style="text-align: center;">REMISION Y/O FACTURA</h3><br>

  <input type="hidden" name="embIdDoc" value="<?php echo $idDocumento ?>" id="embIdDoc">
  <input type="hidden" name="embIdFac" value="<?php echo $dataFactura['info']->id_factura ?>" id="embIdFac">

  <div class="row-fluid">

    <div class="span2 offset5">
      <div class="control-group">
        <label class="control-label" for="plicencia_file"><?php echo ($dataFactura['info']->is_factura=='t'? 'FACTURA': 'REMISION') ?></label>
        <div class="controls">
          <strong><?php echo $dataFactura['info']->serie.$dataFactura['info']->folio ?></strong>
        </div>
      </div><!--/control-group -->
    </div>

  </div><!--/row-fluid -->

</form>