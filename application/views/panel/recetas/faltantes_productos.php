    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <?php echo $titleBread ?>
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-shopping-cart"></i> Faltantes de Productos</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/recetas/faltantes_productos/'.$method); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="empresa" value="<?php echo set_value_get('dempresa', $empresa_default->nombre_fiscal) ?>" size="73">
                <input type="hidden" name="did_empresa" id="empresaId" value="<?php echo set_value_get('did_empresa', $empresa_default->id_empresa) ?>">
                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th></th>
                  <th>Fecha</th>
                  <th>Folio</th>
                  <th>Proveedor</th>
                  <th>Producto</th>
                  <th>Faltante</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($productos as $producto) { ?>
                <tr>
                  <td></td>
                  <td><?php echo $producto->fecha_creacion ?></td>
                  <td><?php echo $producto->folio ?></td>
                  <td><?php echo $producto->proveedor ?></td>
                  <td><?php echo $producto->producto ?></td>
                  <td><?php echo $producto->faltantes ?></td>
                </tr>
            <?php }?>
              </tbody>
            </table>


          </div>
        </div><!--/span-->

      </div><!--/row-->


      <!-- Modal -->
      <div id="modalOrden" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
        aria-hidden="true" style="width: 80%;left: 25%;top: 40%;height: 600px;">
        <div class="modal-body" style="max-height: 1500px;">
          <iframe id="frmOrdenView" src="" style="width: 100%;height: 800px;"></iframe>
        </div>
      </div>



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
