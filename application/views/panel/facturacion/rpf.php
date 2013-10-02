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
            Reporte Productos Facturados
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Productos Facturados</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/facturacion/prodfact_pdf'); ?>" method="GET" class="form-search" target="rpfReporte" id="form">
              <div class="form-actions form-filters center">

                <label for="dproducto">Producto</label>
                <input type="text" name="dproducto" class="input-xlarge search-query" id="dproducto" value="<?php echo set_value_get('dproducto'); ?>" size="73">
                <input type="hidden" name="did_producto" id="did_producto" value="<?php echo set_value_get('did_producto'); ?>">

                <label for="dcliente">Cliente</label>
                <input type="text" name="dcliente" class="input-xlarge search-query" id="dcliente" value="<?php echo set_value_get('dcliente'); ?>" size="73">
                <input type="hidden" name="fid_cliente" id="fid_cliente" value="<?php echo set_value_get('fid_cliente'); ?>">

                <label for="dpagadas">Pagadas</label>
                <input type="checkbox" name="dpagadas" value="1">

                <br>

                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-medium search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m').'-01'); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-medium search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', date('Y-m-d')); ?>" size="10">

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <div class="row-fluid">
              <iframe name="rpfReporte" id="iframe-reporte" class="span12" src="<?php echo base_url('panel/facturacion/prodfact_pdf')?>" style="height:600px;"></iframe>
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
