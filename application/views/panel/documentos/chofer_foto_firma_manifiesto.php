<h3 style="text-align: center;">CHOFER FOTO FIRMA DEL MANIFIESTO</h3><br>

<div class="row-fluid">

  <div class="span12">

    <fieldset class="span4" style="border-bottom: none;">
      <legend style="margin-bottom: 3px;">Camara LIVE<button class="btn pull-right" type="button" id="btnSnapshot" data-name="pimgsalida"><i class="icon-camera icon-2x"></i></button></legend>
      <div class="row-fluid">
        <div class="span12">
          <img src="<?php echo $this->config->item('base_url_cam_salida_stream') ?>" width="320">
        </div>
      </div>
    </fieldset><!--/span4 -->

    <fieldset class="span4" style="border-bottom: none;">
      <legend style="margin-bottom: 3px;">Captura</legend>
      <div class="row-fluid">
        <div class="span12">
          <?php $url = isset($dataDocumento->url) ? str_replace('\\', '', base_url($dataDocumento->url)) : ''; ?>
          <img src="<?php echo $url ?>" width="320" id="imgCapture">
          <input type="hidden"  value="" id="inputImgCapture">
        </div>
      </div>
    </fieldset><!--/span4 -->

    <fieldset class="span4" style="border-bottom: none;">
      <legend style="margin-bottom: 3px;">Accion</legend>
      <div class="row-fluid">
        <div class="well span12">
          <?php if ($finalizados === 'f'){ ?>
            <div class="row-fluid">
              <button type="button" class="btn btn-success btn-large span12" id="btnSnapshotSave">Guardar</button>
            </div>
          <?php } ?>
          <?php if (isset($dataDocumento->url)) {?>
            <br>
            <div class="row-fluid">
              <a class="btn btn-success btn-large span12" href="<?php echo str_replace('\\', '', base_url($dataDocumento->url)) ?>" target="_BLANK">Ver</a>
            </div>
          <?php } ?>
        </div>
      </div>
    </fieldset><!--/span4 -->

  </div><!--/span12 -->

</div><!--/row-fluid -->