    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/compras/'); ?>">Compras</a><span class="divider">/</span>
          </li>
          <li>
            Departamentos
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-shopping-cart"></i> Unidades</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/compras_departamentos/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                Nombre<input type="text" name="ffiltro" value="<?php echo set_value_get('ffiltro') ?>" class="search-query" autofocus>
                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <?php
              echo $this->usuarios_model->getLinkPrivSm('compras_departamentos/agregar/', array(
                'params'   => '',
                'btn_type' => 'btn-success pull-right',
                'attrs' => array('style' => 'margin-bottom: 10px;') )
              );
             ?>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($departamentos['departamentos'] as $depa) {?>
                <tr>
                  <td><?php echo $depa->nombre; ?></td>
                  <td class="center">
                    <?php
                        echo $this->usuarios_model->getLinkPrivSm('compras_departamentos/modificar/', array(
                          'params'   => 'id='.$depa->id_departamento,
                          'btn_type' => 'btn-success',
                          'attrs' => array())
                        );
                    ?>
                  </td>
                </tr>
            <?php } ?>
              </tbody>
            </table>

            <?php
            //Paginacion
            $this->pagination->initialize(array(
                'base_url'      => base_url($this->uri->uri_string()).'?'.String::getVarsLink(array('pag')).'&',
                'total_rows'    => $departamentos['total_rows'],
                'per_page'      => $departamentos['items_per_page'],
                'cur_page'      => $departamentos['result_page']*$departamentos['items_per_page'],
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
