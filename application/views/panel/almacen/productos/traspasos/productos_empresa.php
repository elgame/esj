
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>E. del Sistema</th>
                  <th>Cantidad a traspasar</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($data['productos'] as $producto) { ?>
                    <tr>
                        <td><?php echo $producto->nombre_producto; ?>
                          <input type="hidden" class="idproducto" name="idproducto[]" value="<?php echo $producto->id_producto; ?>">
                          <input type="hidden" class="descripcion" name="descripcion[]" value="<?php echo $producto->nombre_producto; ?>">
                          <input type="hidden" class="precio_producto" name="precio_producto[]" value="<?php echo $producto->data[1]; ?>">
                          <input type="hidden" class="esistema" name="esistema[]" value="<?php echo $producto->data[0]; ?>">
                        </td>
                        <td><?php echo MyString::formatoNumero($producto->data[0], 2, '').' '.$producto->abreviatura; ?></td>
                        <td style="width: 133px;"><input type="text" class="prod-cantidad vpositive span6" value="0" placeholder="Cantidad" style="width: 133px;"></td>
                        <td><a class="btn" onclick="productos.check('<?php echo $producto->nombre_producto ?>', this); return false;" href="#" title="Traspasar"><i class="icon-angle-right"></i></a></td>
                    </tr>
                <?php } ?>
              </tbody>
            </table>

            <?php
            //Paginacion
            $this->pagination->initialize(array(
                'base_url'      => '',
                'javascript'    => 'javascript:productos.page({pag});void(0);',
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