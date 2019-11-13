    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Categorias
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-list"></i> Categorias</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/caja_chica/categorias'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="fstatus">Estado</label>
                <select name="fstatus" class="input-medium" id="fstatus">
                  <option value="">TODAS</option>
                  <option value="t" <?php echo set_select_get('fstatus', 't'); ?>>ACTIVAS</option>
                  <option value="f" <?php echo set_select_get('fstatus', 'f'); ?>>ELIMINADAS</option>
                </select>

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <?php
              echo $this->usuarios_model->getLinkPrivSm('caja_chica/categorias_agregar/', array(
                'params'   => '',
                'btn_type' => 'btn-success pull-right',
                'attrs' => array('style' => 'margin-bottom: 10px;') )
              );
             ?>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Abreviatura</th>
                  <th>Empresa</th>
                  <th>Estado</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($categorias['categorias'] as $categoria) {?>
                <tr>
                  <td><?php echo $categoria->nombre; ?></td>
                  <td><?php echo $categoria->abreviatura; ?></td>
                  <td><?php echo $categoria->empresa; ?></td>
                  <td><?php
                          $texto = 'ELIMINADA';
                          $label = 'warning';
                        if ($categoria->status === 't') {
                          $texto = 'ACTIVA';
                          $label = 'success';
                        }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td class="center">
                    <?php
                      if ($categoria->status === 't')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('caja_chica/categorias_modificar/', array(
                          'params'   => 'id='.$categoria->id_categoria,
                          'btn_type' => 'btn-success',
                          'attrs' => array())
                        );

                        echo $this->usuarios_model->getLinkPrivSm('caja_chica/categorias_eliminar/', array(
                          'params'   => 'id='.$categoria->id_categoria,
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar la categoria?', 'Categorias', this); return false;"))
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
                'base_url'      => base_url($this->uri->uri_string()).'?'.String::getVarsLink(array('pag')).'&',
                'total_rows'    => $categorias['total_rows'],
                'per_page'      => $categorias['items_per_page'],
                'cur_page'      => $categorias['result_page']*$categorias['items_per_page'],
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
