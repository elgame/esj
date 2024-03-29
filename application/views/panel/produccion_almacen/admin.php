    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>

          </li>
          <li>
            Producción de soluciones
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-shopping-cart"></i> Producción de soluciones</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/produccion_almacen/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="ffolio">Folio</label>
                <input type="number" name="ffolio" id="ffolio" value="<?php echo set_value_get('ffolio'); ?>" class="input-mini search-query" autofocus>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="empresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="empresaId" value="<?php echo set_value_get('did_empresa'); ?>">

                <label for="dproducto">Producto</label>
                <input type="text" name="dproducto" class="input-large search-query" id="dproducto" value="<?php echo set_value_get('dproducto'); ?>" size="73">
                <input type="hidden" name="did_producto" id="did_producto" value="<?php echo set_value_get('did_producto'); ?>">

                <br>
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m-01')); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', $fecha); ?>" size="10">

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <?php
              echo $this->usuarios_model->getLinkPrivSm('produccion_almacen/agregar/', array(
                'btn_type' => 'btn-success pull-right',
                'attrs' => array('style' => 'margin-bottom: 10px;') )
              );
             ?>

             <div id="sumaRowsSel" style="display:none;position:fixed;top:200px;right: 0px;width: 130px;background-color:#FFFF00;padding:3px 0px 3px 3px;font-size:16px;font-weight:bold;"></div>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Folio</th>
                  <th>Producto</th>
                  <th>Empresa</th>
                  <th>Cantidad</th>
                  <th>Costo</th>
                  <th>Estado</th>
                  <th>Creacion</th>
                  <th>Produccion</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($produccion['produccion'] as $produc) { ?>
                <tr>
                  <td><span class="label"><?php echo $produc->folio; ?></span></td>
                  <td><?php echo $produc->producto; ?></td>
                  <td><?php echo $produc->nombre_fiscal; ?></td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero($produc->cantidad, 2, '', false); ?></td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero($produc->costo, 2, '$', false); ?></td>
                  <td><?php
                        $texto = 'Activo';
                        $label = 'success';
                        if ($produc->status === 'ca') {
                          $texto = 'Cancelado';
                          $label = 'warning';
                        }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td><?php echo substr($produc->fecha, 0, 10); ?></td>
                  <td><?php echo $produc->descripcion; ?></td>
                  <td class="center">
                    <?php

                      if ($produc->status === 'n')
                      {
                        echo '<a class="btn btn-info" href="'.base_url("panel/produccion_almacen/imprimirticket/?id_salida={$produc->id_salida}&id_orden={$produc->id_orden}").'" title="Imprimir producción" target="_BLANK">
                          <i class="icon-print icon-white"></i> <span class="hidden-tablet">Imprimir</span></a>';
                      }

                      if ($produc->status == 'n')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('produccion_almacen/cancelar/', array(
                          'params'   => "id_salida={$produc->id_salida}&id_orden={$produc->id_orden}",
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de Cancelar?', 'Ordenes de Producción', this); return false;"))
                        );
                      }
                    ?>
                  </td>
                </tr>
            <?php }?>
              </tbody>
            </table>

            <?php
            //Paginacion
            $this->pagination->initialize(array(
                'base_url'      => base_url($this->uri->uri_string()).'?'.MyString::getVarsLink(array('pag')).'&',
                'total_rows'    => $produccion['total_rows'],
                'per_page'      => $produccion['items_per_page'],
                'cur_page'      => $produccion['result_page']*$produccion['items_per_page'],
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
