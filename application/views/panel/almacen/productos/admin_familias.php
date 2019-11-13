
                <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Tipo</th>
                  <th>Opciones</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($familias['familias'] as $familia){ ?>
              <tr>
                <td><?php echo $familia->nombre; ?></td>
                <td><span class="label label-info"><?php echo $familia->tipo_text; ?></span></td>
                <td class="center">
                    <?php
                    echo $this->usuarios_model->getLinkPrivSm('productos/modificar_familia/', array(
                        'params'   => 'id='.$familia->id_familia,
                        'btn_type' => 'btn-success', 'text_link' => 'hide',
                        'attrs'    => array('rel' => 'superbox-40x500') )
                    );
                    if ($familia->status == 'ac') {
                      echo $this->usuarios_model->getLinkPrivSm('productos/eliminar_familia/', array(
                          'params'   => 'id='.$familia->id_familia,
                          'btn_type' => 'btn-danger', 'text_link' => 'hide',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar la familia?<br>Se eliminaran los productos de la familia.', 'familias', this, familias.remove); return false;"))
                      );
                    }else{
                      echo $this->usuarios_model->getLinkPrivSm('productos/activar_familia/', array(
                          'params'   => 'id='.$familia->id_familia,
                          'btn_type' => 'btn-danger', 'text_link' => 'hide',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar el area?', 'familias', this); return false;"))
                      );
                    }

                    ?>
                    <a class="btn" href="#" onclick="familias.loadProd(<?php echo $familia->id_familia; ?>, <?php echo $familia->id_empresa; ?>); return false;" title="Productos">
                      <i class="icon-angle-right"></i> <span class="hide">Productos</span></a>
                </td>
              </tr>
          <?php }?>
              </tbody>
            </table>

            <?php
            //Paginacion
            $this->pagination->initialize(array(
              'base_url'      => '',
                      'javascript'    => 'javascript:familias.page({pag});void(0);',
              'total_rows'    => $familias['total_rows'],
              'per_page'      => $familias['items_per_page'],
              'cur_page'      => $familias['result_page']*$familias['items_per_page'],
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