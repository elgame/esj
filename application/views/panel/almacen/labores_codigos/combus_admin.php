    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Labores
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-list"></i> Labores</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/labores_codigo/combus/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="fnombre">Nombre</label>
                <input type="text" name="fnombre" value="<?php echo set_value_get('fnombre'); ?>" class="input-medium" id="fnombre" placeholder="Nombre de la labor" autofocus>

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
              echo $this->usuarios_model->getLinkPrivSm('labores_codigo/combus_agregar/', array(
                'params'   => '',
                'btn_type' => 'btn-success pull-right',
                'attrs' => array('style' => 'margin-bottom: 10px;') )
              );
             ?>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <!-- <th>Codigo</th>
                  <th>Costo</th>
                  <th>Area</th> -->
                  <th>Estado</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($labores['labores'] as $labor) {?>
                <tr>
                  <td><?php echo $labor->nombre; ?></td>
                  <!-- <td><?php echo $labor->codigo; ?></td>
                  <td><?php echo $labor->costo; ?></td>
                  <td><?php echo $labor->departamento; ?></td> -->
                  <td><?php
                          $texto = 'ELIMINADA';
                          $label = 'warning';
                        if ($labor->status === 't') {
                          $texto = 'ACTIVA';
                          $label = 'success';
                        }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td class="center">
                    <?php
                      if ($labor->status === 't')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('labores_codigo/combus_modificar/', array(
                          'params'   => 'id='.$labor->id_labor,
                          'btn_type' => 'btn-success',
                          'attrs' => array())
                        );

                        echo $this->usuarios_model->getLinkPrivSm('labores_codigo/combus_eliminar/', array(
                          'params'   => 'id='.$labor->id_labor,
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar la labor?', 'labores', this); return false;"))
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
                'total_rows'    => $labores['total_rows'],
                'per_page'      => $labores['items_per_page'],
                'cur_page'      => $labores['result_page']*$labores['items_per_page'],
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
