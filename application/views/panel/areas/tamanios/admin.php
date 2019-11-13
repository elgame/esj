
                <table class="table table-striped table-bordered bootstrap-datatable">
                  <thead>
                    <tr>
                      <th>Nombre</th>
                      <th>Estatus</th>
                      <th>Opc</th>
                    </tr>
                  </thead>
                  <tbody id="acal_body">
                 <?php
                 foreach($tamanos_ventas['tamanios'] as $tamano){
                  ?>
                    <tr>
                      <td><?php echo $tamano->nombre; ?></td>
                      <td>
                        <?php
                          if($tamano->status == 't'){
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
                          echo $this->usuarios_model->getLinkPrivSm('areas_otros/modificar_tamano/', array(
                              'params'   => 'id='.$tamano->id_tamanio.'&idarea='.$this->input->get('id'),
                              'text_link' => 'hide',
                              'btn_type' => 'btn-success')
                          );
                          if ($tamano->status == 't') {
                            echo $this->usuarios_model->getLinkPrivSm('areas_otros/eliminar_tamano/', array(
                                'params'   => 'id='.$tamano->id_tamanio.'&idarea='.$this->input->get('id'),
                                'btn_type' => 'btn-danger',
                                'text_link' => 'hide',
                                'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar la tamano?', 'areas', this); return false;"))
                            );
                          }else{
                            echo $this->usuarios_model->getLinkPrivSm('areas_otros/activar_tamano/', array(
                                'params'   => 'id='.$tamano->id_tamanio.'&idarea='.$this->input->get('id'),
                                'btn_type' => 'btn-danger',
                                'text_link' => 'hide',
                                'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar la tamano?', 'areas', this); return false;"))
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
                    'javascript'    => 'javascript:edit_calidades.page({pag});void(0);',
                    'total_rows'    => $tamanos_ventas['total_rows'],
                    'per_page'      => $tamanos_ventas['items_per_page'],
                    'cur_page'      => $tamanos_ventas['result_page']*$tamanos_ventas['items_per_page'],
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