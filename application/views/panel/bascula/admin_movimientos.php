    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Movimientos
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-exchange"></i> Movimientos</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/bascula/admin_movimientos'); ?>" method="get" class="form-search">
              <fieldset>
                <legend>Filtros</legend>

                <label for="fnombre">Buscar</label>
                <input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
                  class="input-xlarge search-query" placeholder="Proveedor" autofocus> |

                <label for="fstatusb">Del</label>
                <input type="text" name="fechaini" class="input-medium" id="fechaini" value="<?php echo set_value_get('fechaini') ?>" placeholder="">

                <label for="fstatusb">Al</label>
                <input type="text" name="fechaend" class="input-medium" id="fechaend" value="<?php echo set_value_get('fechaend') ?>" placeholder="">

                <input type="submit" name="enviar" value="Buscar" class="btn">
              </fieldset>
            </form>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Tipo Pago</th>
                  <th>Monto</th>
                  <th>Concepto</th>
                  <th>Folios Bascula</th>
                  <th>Proveedor</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($basculas['basculas'] as $b){ ?>
              <tr>
                <td><?php echo $b->tipo_pago; ?></td>
                <td><?php echo $b->monto; ?></td>
                <td><?php echo $b->concepto; ?></td>
                <td><?php echo $b->folios; ?></td>
                <td><?php echo $b->proveedor; ?></td>
                <td class="center">
                    <?php
                      echo $this->usuarios_model->getLinkPrivSm('bascula/cancelar_movimiento/', array(
                          'params'   => 'id='.$b->id_pago,
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de cancelar el pago?', 'bascula', this); return false;"))
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
                'total_rows'    => $basculas['total_rows'],
                'per_page'      => $basculas['items_per_page'],
                'cur_page'      => $basculas['result_page']*$basculas['items_per_page'],
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


