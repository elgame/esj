
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Codigo</th>
                  <th>Nombre</th>
                  <th>Opciones</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($productos['productos'] as $producto){ ?>
              <tr>
                <td><?php echo $producto->codigo; ?></td>
                <td><?php echo $producto->nombre; ?></td>
                <td class="center">
                    <?php 
                    echo $this->usuarios_model->getLinkPrivSm('productos/modificar/', array(
                        'params'   => 'id='.$producto->id_producto."&fid_familia=".$this->input->get('fid_familia'),
                        'btn_type' => 'btn-success', 'text_link' => 'hide',
                        'attrs'    => array('rel' => 'superbox-40x500') )
                    );
                    if ($producto->status == 'ac') {
                      echo $this->usuarios_model->getLinkPrivSm('productos/eliminar/', array(
                          'params'   => 'id='.$producto->id_producto,
                          'btn_type' => 'btn-danger', 'text_link' => 'hide',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar el producto?', 'productos', this, productos.remove); return false;"))
                      );
                    }else{
                      echo $this->usuarios_model->getLinkPrivSm('productos/activar_familia/', array(
                          'params'   => 'id='.$producto->id_producto,
                          'btn_type' => 'btn-danger', 'text_link' => 'hide',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar el area?', 'productos', this); return false;"))
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
              'base_url'      => '',
                      'javascript'    => 'javascript:productos.page({pag});void(0);',
              'total_rows'    => $productos['total_rows'],
              'per_page'      => $productos['items_per_page'],
              'cur_page'      => $productos['result_page']*$productos['items_per_page'],
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