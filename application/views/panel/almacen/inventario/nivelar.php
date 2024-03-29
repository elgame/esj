    <div class="span12">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/inventario/nivelar/'); ?>" method="GET" class="form-search">
              <label class="control-label" for="dempresa">Empresa</label>
              <input type="text" name="dempresa"
                value="<?php echo set_value_get('dempresa', (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: '')); ?>" id="dempresa" class="" placeholder="Nombre">
              <input type="hidden" name="fid_empresa" value="<?php echo set_value_get('fid_empresa', (isset($empresa->id_empresa)? $empresa->id_empresa: '')); ?>" id="did_empresa">

              <label class="control-label" for="dfamilias">Familias</label>
              <select name="dfamilias" id="dfamilias">
                <option value=""></option>
              <?php foreach ($familias['familias'] as $key => $value)
              {
              ?>
                <option value="<?php echo $value->id_familia; ?>" <?php echo set_select_get('dfamilias', $value->id_familia); ?>><?php echo $value->nombre; ?></option>
              <?php
              }
              ?>
              </select>

              <label class="control-label" for="id_almacen">Almacenes</label>
              <select name="id_almacen" id="id_almacen">
              <?php $default = ($this->input->get('id_almacen')>0? $this->input->get('id_almacen'): '1');
              foreach ($almacenes['almacenes'] as $key => $value) { ?>
                <option value="<?php echo $value->id_almacen ?>" <?php echo set_select('id_almacen', $value->id_almacen, false, $default) ?>><?php echo $value->nombre ?></option>
              <?php } ?>
              </select>

              <label class="control-label" for="dproducto">Producto</label>
              <input type="text" name="dproducto" id="dproducto" value="<?php echo (isset($_GET['dproducto'])? $_GET['dproducto']: '') ?>" class="input-large" placeholder="Producto">
              <input type="hidden" name="dproductoId" id="dproductoId" value="<?php echo (isset($_GET['dproductoId'])? $_GET['dproductoId']: '') ?>">

              <label class="control-label" for="dfecha">Fecha Nivelación</label><input type="date" name="dfecha" value="<?php echo (isset($_GET['dfecha'])? $_GET['dfecha']: date('Y-m-d')) ?>" class="input-large">

              <button type="submit" class="btn">Enviar</button>
            </form> <!-- /form -->

          </div>
        </div><!--/span12 -->
      </div><!--/row-fluid -->
    </div><!-- /span3 -->

    <div id="content" class="row-fluid">
      <!-- content starts -->

      <form action="<?php echo base_url('panel/inventario/nivelar/?'.MyString::getVarsLink(array('fstatus'))); ?>" method="post">
        <input type="submit" class="btn btn-primary pull-right" name="guardar" value="Nivelar">
        <input type="hidden" name="id_almacen" value="<?php echo $default ?>">

        <table class="table table-striped table-bordered bootstrap-datatable">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>E. del Sistema</th>
              <th>E. fisica</th>
              <th>Diferencia</th>
            </tr>
          </thead>
          <tbody>
        <?php foreach($data['productos'] as $producto){ ?>
          <tr>
            <td><?php echo $producto->nombre_producto; ?>
              <input type="hidden" class="idproducto" name="idproducto[]" value="<?php echo $producto->id_producto; ?>">
              <input type="hidden" class="descripcion" name="descripcion[]" value="<?php echo $producto->nombre_producto; ?>">
              <input type="hidden" class="precio_producto" name="precio_producto[]" value="<?php echo number_format(round($producto->ul_precio_unitario, 6), 6, '.', ''); ?>">
              <input type="hidden" class="esistema" name="esistema[]" value="<?php echo round($producto->data[0], 6); ?>">
            </td>
            <td><?php echo MyString::formatoNumero($producto->data[0], 4, '').' '.$producto->abreviatura; ?></td>
            <td><input type="text" class="vpositive efisica" name="efisica[]" value=""></td>
            <td><input type="text" class="diferencia" name="diferencia[]" value="" readonly></td>
          </tr>
      <?php }?>
          </tbody>
        </table>
        <input type="submit" class="btn btn-primary pull-right" name="guardar" value="Nivelar">
      </form>

      <?php
      //Paginacion
      $this->pagination->initialize(array(
          'base_url'      => base_url($this->uri->uri_string()).'?'.MyString::getVarsLink(array('pag', 'fstatus')).'&',
          'total_rows'    => $data['total_rows'],
          'per_page'      => $data['items_per_page'],
          'cur_page'      => $data['result_page']*$data['items_per_page'],
          'page_query_string' => TRUE,
          'num_links'     => 1,
          'anchor_class'  => 'pags corner-all',
          'num_tag_open'  => '<li>',
          'num_tag_close' => '</li>',
          'cur_tag_open'  => '<li class="active"><a href="#">',
          'cur_tag_close' => '</a></li>'
      ));
      $pagination = $this->pagination->create_links();
      echo '<div class="pagination pagination-centered"><ul>'.$pagination.'</ul></div>';
      ?>

    </div><!--/#content.span9-->



<?php if (isset($p) && isset($pe)) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/bascula/imprimir_pagadas/?'.MyString::getVarsLink(array('msg', 'p', 'pe')).'&pe='.$pe)."'" ?>, '_blank');
    win.focus();
  </script>
<?php } ?>

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