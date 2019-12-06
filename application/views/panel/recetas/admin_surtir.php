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
            <h2><i class="icon-shopping-cart"></i> Surtir Recetas</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/recetas/'.$method); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="empresa" value="<?php echo set_value_get('dempresa', $empresa_default->nombre_fiscal) ?>" size="73">
                <input type="hidden" name="did_empresa" id="empresaId" value="<?php echo set_value_get('did_empresa', $empresa_default->id_empresa) ?>">

                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m-01')); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', $fecha); ?>" size="10">

                <br>

                <label for="darea">Cultivo</label>
                <input type="text" name="darea" class="input-large search-query" id="darea" value="<?php echo set_value_get('darea') ?>" size="73">
                <input type="hidden" name="did_area" id="areaId" value="<?php echo set_value_get('did_area') ?>">

                <label for="ftipo">Tipo</label>
                <select name="ftipo" class="input-medium" id="ftipo">
                  <option value="">TODAS</option>
                  <option value="kg" <?php echo set_select_get('ftipo', 'kg'); ?>>Kg</option>
                  <option value="lts" <?php echo set_select_get('ftipo', 'lts'); ?>>Lts</option>
                </select>

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>


            <form action="<?php echo base_url('panel/recetas/'.$method.'?'.MyString::getVarsLink(array('msg'))); ?>" method="post">
              Almacén: <select name="id_almacen" class="span3">
                <?php $default = ($this->input->post('id_almacen')>0? $this->input->post('id_almacen'): '1');
                foreach ($almacenes['almacenes'] as $key => $value) { ?>
                  <option value="<?php echo $value->id_almacen ?>" <?php echo set_select('id_almacen', $value->id_almacen, false, $default) ?>><?php echo $value->nombre ?></option>
                <?php } ?>
              </select>

              <button type="submit" name="requisiciones" class="btn btn-info pull-right">Crear requisiciones</button>
              <button type="submit" name="guardar" class="btn btn-success pull-right">Guardar</button>

              <table class="table table-striped table-bordered bootstrap-datatable">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>F. Aplicación</th>
                    <th>Cultivo</th>
                    <th>Folio</th>
                    <th>Tipo</th>
                    <th>Cantidad</th>
                    <th>Importe</th>
                    <th>Producto</th>
                    <th>Proveedor</th>
                    <th>Aplicar</th>
                  </tr>
                </thead>
                <tbody>
              <?php foreach($recetas as $receta) { ?>
                  <tr>
                    <td><?php echo $receta->fecha; ?></td>
                    <td><?php echo $receta->fecha_aplicacion; ?></td>
                    <td><?php echo $receta->area; ?></td>
                    <td><?php echo $receta->folio; ?></td>
                    <td><?php echo $receta->tipo; ?></td>
                    <td><?php echo $receta->aplicacion_total; ?></td>
                    <td><?php echo $receta->importe; ?></td>
                    <td><?php echo $receta->producto; ?></td>
                    <td>
                      <input type="text" name="proveedor[]" class="span9 proveedor" value="<?php echo set_value('proveedor[]', $receta->proveedor) ?>" size="73">
                      <input type="hidden" name="id_proveedor[]" class="id_proveedor" value="<?php echo set_value('id_proveedor[]', $receta->id_proveedor) ?>">
                      <input type="hidden" name="id_receta[]" class="id_receta" value="<?php echo $receta->id_recetas ?>">
                      <input type="hidden" name="id_producto[]" class="id_producto" value="<?php echo $receta->id_producto ?>">
                      <input type="hidden" name="rows[]" class="rows" value="<?php echo $receta->rows ?>">
                    </td>
                    <td>
                      <select name="aplicar[]">
                        <option value="si" <?php echo ($receta->surtir == 't'? 'selected': '') ?>>Si</option>
                        <option value="no" <?php echo ($receta->surtir == 'f'? 'selected': '') ?>>No</option>
                      </select>
                    </td>
                  </tr>
              <?php }?>
                </tbody>
              </table>
            </form>

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
