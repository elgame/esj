    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/facturacion'); ?>">Facturación</a> <span class="divider">/</span>
          </li>
          <li>
            Reporte Ventas Productos
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Facturas</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/facturacion/rvp_pdf'); ?>" method="GET" class="form-search" target="rvcReporte">
              <div class="form-actions form-filters center">

                <label for="dfamilia">Familia</label>
                <select name="dfamilia">
                  <option value=""></option>
                  <?php foreach ($familias as $k => $v){ ?>
                    <option value="<?php echo $v->id_familia?>"><?php echo $v->nombre ?></option>
                  <?php } ?>
                </select>

                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="text" name="ffecha1" class="input-small search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="text" name="ffecha2" class="input-small search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2'); ?>" size="10">

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <div class="row-fluid">
              <iframe name="rvcReporte" id="iframe-reporte" class="span12" src="<?php echo base_url('panel/facturacion/rvp_pdf')?>" style="height:600px;"></iframe>
            </div>

          </div>
        </div><!--/span-->

      </div><!--/row-->




          <!-- content ends -->
    </div><!--/#content.span10-->


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
