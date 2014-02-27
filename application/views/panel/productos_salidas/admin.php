    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Salidas de Productos
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-shopping-cart"></i> Salidas de Productos</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/productos_salidas/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="ffolio">Folio</label>
                <input type="number" name="ffolio" id="ffolio" value="<?php echo set_value_get('ffolio'); ?>" class="input-mini search-query" autofocus>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="empresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="empresaId" value="<?php echo set_value_get('did_empresa'); ?>">

                <br>
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="datetime-local" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m-01\TH:i')); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="datetime-local" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', $fecha); ?>" size="10">

                <label for="fstatus">Estado</label>
                <select name="fstatus" class="input-medium" id="fstatus">
                  <option value="">TODAS</option>
                  <option value="ca" <?php echo set_select_get('fstatus', 'ca'); ?>>CANCELADAS</option>
                  <option value="s" <?php echo set_select_get('fstatus', 's'); ?>>NO CANCELADAS</option>
                </select>

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <?php
              echo $this->usuarios_model->getLinkPrivSm('productos_salidas/agregar/', array(
                'params'   => '',
                'btn_type' => 'btn-success pull-right',
                'attrs' => array('style' => 'margin-bottom: 10px;') )
              );
             ?>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Folio</th>
                  <th>Empresa</th>
                  <th>Estado</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($salidas['salidas'] as $salida) {?>
                <tr>
                  <td><?php echo substr($salida->fecha, 0, 10); ?></td>
                  <td><span class="label"><?php echo $salida->folio; ?></span></td>
                  <td><?php echo $salida->empresa; ?></td>
                  <td><?php
                          $texto = 'CANCELADA';
                          $label = 'warning';
                        if ($salida->status === 's') {
                          $texto = 'NO CANCELADA';
                          $label = 'success';
                        }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td class="center">
                    <?php
                      echo $this->usuarios_model->getLinkPrivSm('productos_salidas/ver/', array(
                        'params'   => 'id='.$salida->id_salida,
                        'btn_type' => 'btn-success',
                        'attrs' => array())
                      );

                      if ($salida->status !== 'ca')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('productos_salidas/cancelar/', array(
                          'params'   => 'id='.$salida->id_salida,
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de Cancelar la salida?', 'Salidas', this); return false;"))
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
                'total_rows'    => $salidas['total_rows'],
                'per_page'      => $salidas['items_per_page'],
                'cur_page'      => $salidas['result_page']*$salidas['items_per_page'],
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
