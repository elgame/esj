              
              <table class="table table-striped table-bordered bootstrap-datatable">
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Precio</th>
                    <th>Cuenta</th>
                    <th>Estatus</th>
                    <th>Opc</th>
                  </tr>
                </thead>
                <tbody id="acla_body">
                <?php 
               foreach($clasificaciones['clasificaciones'] as $clasificacion){
                ?>
                  <tr>
                    <td><?php echo $clasificacion->nombre; ?></td>
                    <td><?php echo $clasificacion->precio_venta; ?></td>
                    <td><?php echo $clasificacion->cuenta_cpi; ?></td>
                    <td>
                      <?php
                        if($clasificacion->status == 't'){
                          $v_status = 'Activo';
                          $vlbl_status = 'label-success';
                        }else{
                          $v_status = 'Eliminado';
                          $vlbl_status = 'label-important';
                        }
                      ?>
                      <span class="label <?php echo $vlbl_status; ?>"><?php echo $v_status; ?></span>
                    </td>
                    <td class="center">
                        <?php 
                        echo $this->usuarios_model->getLinkPrivSm('areas/modificar_clasificacion/', array(
                            'params'   => 'id='.$clasificacion->id_clasificacion.'&idarea='.$this->input->get('id'),
                            'text_link' => 'hide',
                            'btn_type' => 'btn-success')
                        );
                        if ($clasificacion->status == 't') {
                          echo $this->usuarios_model->getLinkPrivSm('areas/eliminar_clasificacion/', array(
                              'params'   => 'id='.$clasificacion->id_clasificacion.'&idarea='.$this->input->get('id'),
                              'btn_type' => 'btn-danger',
                              'text_link' => 'hide',
                              'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar la clasificacion?', 'areas', this); return false;"))
                          );
                        }else{
                          echo $this->usuarios_model->getLinkPrivSm('areas/activar_clasificacion/', array(
                              'params'   => 'id='.$clasificacion->id_clasificacion.'&idarea='.$this->input->get('id'),
                              'btn_type' => 'btn-danger',
                              'text_link' => 'hide',
                              'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar la clasificacion?', 'areas', this); return false;"))
                          );
                        }
                        
                        ?>
                    </td>
                  </tr>
               <?php 
                } ?>
                </tbody>
              </table>
              
              <?php
              //Paginacion
              $this->pagination->initialize(array(
                  'base_url'      => '',
                  'javascript'    => 'javascript:edit_clasificacion.page({pag});void(0);',
                  'total_rows'    => $clasificaciones['total_rows'],
                  'per_page'      => $clasificaciones['items_per_page'],
                  'cur_page'      => $clasificaciones['result_page']*$clasificaciones['items_per_page'],
                  'page_query_string' => TRUE,
                  'num_links'     => 1,
                  'anchor_class'  => 'pags corner-all',
                  'num_tag_open'  => '<li>',
                  'num_tag_close' => '</li>',
                  'cur_tag_open'  => '<li class="active"><a href="#">',
                  'cur_tag_close' => '</a></li>'
              ));
              $pagination = $this->pagination->create_links();
              echo '<div id="calidades_pagination" class="pagination pagination-centered"><ul>'.$pagination.'</ul></div>';
              ?>