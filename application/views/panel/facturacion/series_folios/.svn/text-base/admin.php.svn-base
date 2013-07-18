    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
           <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/facturacin/'); ?>">Facturacion</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="#">Series y Folios</a>
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Facturas</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
              <a href="#" class="btn btn-close btn-round"><i class="icon-remove"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/facturacion/series_folios/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="fserie">Serie</label>
                <input type="text" name="fserie" id="fserie" value="<?php echo set_value_get('fserie'); ?>" class="input-medium search-query" autofocus>

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <?php
            echo $this->empleados_model->getLinkPrivSm('panel/facturacion/agregar_serie_folio/', array(
                    'params'   => '',
                    'btn_type' => 'btn-success pull-right',
                    'attrs' => array('style' => 'margin-bottom: 10px;') )
                );
             ?>
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Empresa</th>
                  <th>Serie</th>
                  <th>No Aprobación</th>
                  <th>Folio Inicio</th>
                  <th>Folio Fin</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($datos_s['series'] as $serie) {?>
                <tr>
                  <td><?php echo $serie->empresa; ?></td>
                  <td><?php echo $serie->serie; ?></td>
                  <td><?php echo $serie->no_aprobacion; ?></td>
                  <td><?php echo $serie->folio_inicio; ?></td>
                  <td><?php echo $serie->folio_fin; ?></td>
                  <td class="center">
                    <?php
                      echo $this->empleados_model->getLinkPrivSm('facturacion/modificar_serie_folio/', array(
                          'params'   => 'id='.$serie->id_serie_folio,
                          'btn_type' => 'btn-success')
                      );
                    ?>
                  </td>
                </tr>
            <?php }?>
              </tbody>
            </table>

            <?php
            //Paginacion
            $this->pagination->initialize(array(
                'base_url'      => base_url($this->uri->uri_string()).'?'.String::getVarsLink(array('pag')).'&',
                'total_rows'    => $datos_s['total_rows'],
                'per_page'      => $datos_s['items_per_page'],
                'cur_page'      => $datos_s['result_page']*$datos_s['items_per_page'],
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
