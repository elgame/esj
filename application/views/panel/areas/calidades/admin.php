
                <table class="table table-striped table-bordered bootstrap-datatable">
                  <thead>
                    <tr>
                      <th>Nombre</th>
                      <th>Precio</th>
                      <th>Estatus</th>
                      <th>Opc</th>
                    </tr>
                  </thead>
                  <tbody id="acal_body">
                 <?php 
                 foreach($calidades['calidades'] as $calidad){
                  ?>
                    <tr>
                      <td><?php echo $calidad->nombre; ?></td>
                      <td><?php echo $calidad->precio_compra; ?></td>
                      <td>
                        <?php
                          if($calidad->status == 't'){
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
                          echo $this->usuarios_model->getLinkPrivSm('areas/modificar_calidad/', array(
                              'params'   => 'id='.$calidad->id_calidad.'&idarea='.$this->input->get('id'),
                              'text_link' => 'hide',
                              'btn_type' => 'btn-success')
                          );
                          if ($calidad->status == 't') {
                            echo $this->usuarios_model->getLinkPrivSm('areas/eliminar_calidad/', array(
                                'params'   => 'id='.$calidad->id_calidad.'&idarea='.$this->input->get('id'),
                                'btn_type' => 'btn-danger',
                                'text_link' => 'hide',
                                'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar la calidad?', 'areas', this); return false;"))
                            );
                          }else{
                            echo $this->usuarios_model->getLinkPrivSm('areas/activar_calidad/', array(
                                'params'   => 'id='.$calidad->id_calidad.'&idarea='.$this->input->get('id'),
                                'btn_type' => 'btn-danger',
                                'text_link' => 'hide',
                                'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar la calidad?', 'areas', this); return false;"))
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
                    'total_rows'    => $calidades['total_rows'],
                    'per_page'      => $calidades['items_per_page'],
                    'cur_page'      => $calidades['result_page']*$calidades['items_per_page'],
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