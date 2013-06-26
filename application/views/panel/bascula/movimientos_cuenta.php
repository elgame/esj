    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Bascula
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-road"></i> Bascula</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/bascula/movimientos'); ?>" method="GET" class="form-search">
              <fieldset>
                <legend>Filtros</legend>

                <label class="control-label" for="fproveedor">Proveedor</label>
                <input type="text" name="fproveedor"
                  value="<?php echo set_value_get('fproveedor', $this->input->get('fproveedor')) ?>" id="fproveedor" class="input-xlarge search-query" placeholder="Proveedor">
                <input type="text" name="fid_proveedor" value="<?php echo set_value_get('fid_proveedor', $this->input->get('fid_proveedor')) ?>" id="fid_proveedor">

                <!-- <label for="fnombre">Buscar</label>
                <input type="text" name="fnombre" id="fnombre" value="<?php echo set_value_get('fnombre'); ?>"
                  class="input-xlarge search-query" placeholder="Folio, Proveedor, Chofer, Camion, Placas" autofocus> -->

                <label for="fstatusb">Del</label>
                <input type="text" name="fechaini" class="input-medium" id="fechaini" value="<?php echo set_value_get('fechaini') ?>" placeholder="">

                <label for="fstatusb">Al</label>
                <input type="text" name="fechaend" class="input-medium" id="fechaend" value="<?php echo set_value_get('fechaend') ?>" placeholder="">

                <input type="submit" name="enviar" value="Buscar" class="btn">
              </fieldset>
            </form>

            <?php
            // echo $this->usuarios_model->getLinkPrivSm('bascula/agregar/', array(
            //         'params'   => '',
            //         'btn_type' => 'btn-success pull-right',
            //         'attrs' => array('style' => 'margin-bottom: 10px;') )
            //     );
             ?>
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Tipo</th>
                  <th>Folio</th>
                  <th>Proveedor/Cliente</th>
                  <th>Chofer</th>
                  <th>Camión</th>
                  <th>Placas</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($basculas['basculas'] as $b){ ?>
              <tr>
                <td><?php echo substr($b->fecha, 0, 19); ?></td>
                <td>
                  <?php
                    if($b->tipo == 'en'){
                      $v_status = 'Entrada';
                      $vlbl_status = 'label-success';
                    }else{
                      $v_status = 'Salida';
                      $vlbl_status = 'label-warning';
                    }

                  ?>
                  <span class="label <?php echo $vlbl_status; ?>"><?php echo $v_status; ?></span>

                  <?php if ($b->id_bonificacion != null)
                    echo '<br><br><span class="label label-important">Bonificación</span>'
                  ?>

                </td>
                <td><?php echo $b->folio; ?></td>
                <td><?php
                    if ($b->proveedor != null) {
                      echo $b->proveedor;
                    } else {
                      echo $b->cliente;
                    } ?></td>
                <td><?php echo $b->chofer; ?></td>
                <td><?php echo $b->camion; ?></td>
                <td><?php echo $b->placas; ?></td>
                <td class="center">
                    <?php
                    if ($b->id_bonificacion == null) {

                      echo $this->usuarios_model->getLinkPrivSm('bascula/modificar/', array(
                          'params'   => 'folio='.$b->folio,
                          'btn_type' => 'btn-success')
                      );
                    } else {
                      echo $this->usuarios_model->getLinkPrivSm('bascula/modificar_bonificacion/', array(
                          'params'   => 'idb='.$b->id_bascula,
                          'btn_type' => 'btn-success')
                      );
                    }

                    if ($b->status == 't') {
                      echo $this->usuarios_model->getLinkPrivSm('bascula/cancelar/', array(
                          'params'   => 'id='.$b->id_bascula,
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de cancelar la bascula?', 'bascula', this); return false;"))
                      );
                    }else{
                      echo $this->usuarios_model->getLinkPrivSm('bascula/activar/', array(
                          'params'   => 'id='.$b->id_bascula,
                          'btn_type' => 'btn-success',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar la bascula?', 'bascula', this); return false;"))
                      );
                    }
                    echo $this->usuarios_model->getLinkPrivSm('bascula/imprimir/', array(
                        'params'   => 'id='.$b->id_bascula,
                        'btn_type' => 'btn-success',
                        'attrs' => array('target' => '_BLANK'))
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


