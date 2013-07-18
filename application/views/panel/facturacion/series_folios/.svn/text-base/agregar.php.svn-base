<div id="content" class="span10">
  <!-- content starts -->
  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/facturacion/'); ?>">Facturación</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/facturacion/series_folios/'); ?>">Series y Folios</a> <span class="divider">/</span>
      </li>
      <li>Agregar Series y Folios</li>
    </ul>
  </div>

  <form class="form-horizontal" action="<?php echo base_url('panel/facturacion/agregar_serie_folio');?>" method="POST" enctype="multipart/form-data">

      <div class="control-group">
        <label for="fempresa" class="control-label">Empresa</label>
        <div class="controls">
          <input type="text" name="fempresa" class="span6" id="fempresa" value="<?php echo set_value('fempresa'); ?>" autofocus>
          <input type="hidden" name="fid_empresa" id="fid_empresa" value="<?php echo set_value('fid_empresa'); ?>">
        </div>
      </div>

      <div class="control-group">
        <label for="fserie" class="control-label">Serie</label>
        <div class="controls">
          <input type="text" name="fserie" id="fserie" value="<?php echo set_value('fserie') ?>" size="30" maxlength="30" placeholder="Serie">
        </div>
      </div>

      <div class="control-group">
        <label for="fno_aprobacion" class="control-label">No Aprobación</label>
        <div class="controls">
          <input type="text" name="fno_aprobacion" id="fno_aprobacion" value="<?php echo set_value('fno_aprobacion') ?>" size="30" placeholder="No Aprobación">
        </div>
      </div>

       <div class="control-group">
        <label for="ffolio_inicio" class="control-label">Folio Inicio</label>
        <div class="controls">
          <input type="text" name="ffolio_inicio" id="ffolio_inicio" value="<?php echo set_value('ffolio_inicio') ?>" size="30" placeholder="Folio Inicio">
        </div>
      </div>

       <div class="control-group">
        <label for="ffolio_fin" class="control-label">Folio Fin</label>
        <div class="controls">
          <input type="text" name="ffolio_fin" id="ffolio_fin" value="<?php echo set_value('ffolio_fin') ?>" size="30" placeholder="Folio Fin">
        </div>
      </div>

      <div class="control-group">
        <label for="fano_aprobacion" class="control-label">Fecha Aprobación</label>
        <div class="controls">
          <input type="text" name="fano_aprobacion" class="datepicker" id="fano_aprobacion" value="<?php echo set_value('fano_aprobacion') ?>" size="30" placeholder="Fecha Aprobación">
        </div>
      </div>

      <div class="control-group">
        <label for="durl_img" class="control-label">Imagen</label>
        <div class="controls">
          <input type="file" name="durl_img" id="durl_img" value="<?php echo set_value('durl_img') ?>" size="30">
        </div>
      </div>

      <div class="control-group">
        <label for="fleyenda" class="control-label">Leyenda</label>
        <div class="controls">
          <input type="text" name="fleyenda" class="input-xxlarge" id="fleyenda" value="<?php echo set_value('fleyenda') ?>" size="72" placeholder="Leyenda">
        </div>
      </div>

      <div class="control-group">
        <label for="fleyenda1" class="control-label">Leyenda 1</label>
        <div class="controls">
          <input type="text" name="fleyenda1" class="input-xxlarge" id="fleyenda1" value="<?php echo set_value('fleyenda1', 'La reproducción apócrifa de este comprobante constituye un delito en los términos de las disposiciones fiscales.') ?>" size="72" placeholder="Leyenda 1">
        </div>
      </div>

      <div class="control-group">
        <label for="fleyenda2" class="control-label">Leyenda 2</label>
        <div class="controls">
          <input type="text" name="fleyenda2" class="input-xxlarge" id="fleyenda2" value="<?php echo set_value('fleyenda2', 'Esté comprobante tendrá una vigencia de dos años contados a partir de la fecha de aprobación de la asignación de folios, la cual es') ?>" size="72" placeholder="Leyenda 1">
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Guardar</button>
        <button type="reset" class="btn">Cancelar</button>
      </div>
  </form>
</div>


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