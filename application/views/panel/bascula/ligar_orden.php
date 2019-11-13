    <div id="content" class="span12">
      <!-- content starts -->


      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-plus"></i> Ligar orden</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/bascula/show_view_ligar_orden/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <input type="hidden" name="idb" value="<?php echo $_GET['idb'] ?>">
                <label for="ffolio">Folio</label>
                <input type="number" name="ffolio" id="ffolio" value="<?php echo set_value_get('ffolio'); ?>" class="input-mini search-query" autofocus>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="empresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="empresaId" value="<?php echo set_value_get('did_empresa'); ?>">

                <label for="dproveedor">Proveedor</label>
                <input type="text" name="dproveedor" class="input-large search-query" id="proveedor" value="<?php echo set_value_get('dproveedor'); ?>" size="73">
                <input type="hidden" name="did_proveedor" id="proveedorId" value="<?php echo set_value_get('did_proveedor'); ?>">

                <br>
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="datetime-local" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m-01\TH:i')); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="datetime-local" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', date('Y-m-d\TH:i')); ?>" size="10">

                <input type="submit" name="Buscar" value="Buscar" class="btn">
              </div>
            </form>

            <form action="<?php echo base_url('panel/bascula/show_view_ligar_orden/?idb='.$_GET['idb']); ?>" method="post" class="form-horizontal">
              <table class="table table-striped table-bordered bootstrap-datatable">
                <thead>
                  <tr>
                    <th></th>
                    <th>Fecha</th>
                    <th>Folio</th>
                    <th>Proveedor</th>
                    <th>Empresa</th>
                    <th>Importe</th>
                  </tr>
                <?php
                if (isset($ordenes_lig) && count($ordenes_lig) > 0) {
                  foreach ($ordenes_lig as $key => $orden) {
                ?>
                  <tr>
                    <th>
                      <input type="checkbox" name="lig_ordenes[]" checked class="addToFactura" value="<?php echo $orden->id_orden ?>" data-total="<?php echo $orden->total; ?>">
                    </th>
                    <th><?php echo substr($orden->fecha, 0, 10); ?></th>
                    <th><span class="label"><?php echo $orden->folio; ?></span></th>
                    <th><?php echo $orden->proveedor; ?></th>
                    <th><?php echo $orden->empresa; ?></th>
                    <th style="text-align: right;"><?php echo String::formatoNumero($orden->total, 2, '$', false); ?></th>
                  </tr>
                <?php
                  }
                } ?>
                </thead>
                <tbody>
              <?php
              if (isset($ordenes) && count($ordenes) > 0) {
                foreach($ordenes['ordenes'] as $orden) { ?>
                  <tr>
                    <td>
                      <input type="checkbox" name="lig_ordenes[]" class="addToFactura" value="<?php echo $orden->id_orden ?>" data-total="<?php echo $orden->total; ?>">
                    </td>
                    <td><?php echo substr($orden->fecha, 0, 10); ?></td>
                    <td><span class="label"><?php echo $orden->folio; ?></span></td>
                    <td><?php echo $orden->proveedor; ?></td>
                    <td><?php echo $orden->empresa; ?></td>
                    <td style="text-align: right;"><?php echo String::formatoNumero($orden->total, 2, '$', false); ?></td>
                  </tr>
              <?php }
                }?>
                </tbody>
              </table>

              <div class="control-group">
                <label class="control-label" for="lig_entrego">Entrego</label>
                <div class="controls">
                  <input type="text" name="lig_entrego" id="lig_entrego" class="span6" value="<?php echo set_value('lig_entrego', $lig_entrego); ?>" autofocus required>
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="lig_recibio">Recibio</label>
                <div class="controls">
                  <input type="text" name="lig_recibio" id="lig_recibio" class="span6" value="<?php echo set_value('lig_recibio', $lig_recibio); ?>" required>
                </div>
              </div>

              <div class="form-actions">
                <button type="submit" class="btn btn-primary">Guardar</button>
              </div>
            </form>

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
    <?php if($frm_errors['ico'] == 'success'){ ?>
      parent.setLoteBoleta();
    <?php } ?>
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->

