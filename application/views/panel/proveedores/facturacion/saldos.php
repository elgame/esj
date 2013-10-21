    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Proveedores Facturación
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Proveedores Facturas</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/proveedores_facturacion/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="ffecha1" style="margin-top: 15px;">Fecha</label>
                <input type="date" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">

                <label for="dproveedor">Proveedor</label>
                <input type="text" name="fnombre" class="input-large search-query" id="dproveedor" value="<?php echo set_value_get('fnombre'); ?>" size="73">
                <input type="hidden" name="fid_proveedor" id="fid_proveedor" value="<?php echo set_value_get('fid_proveedor'); ?>">

                <button type="submit" class="btn">Enviar</button>
              </div>
            </form>

            <?php
            echo $this->usuarios_model->getLinkPrivSm('proveedores_facturacion/agregar/', array(
                  'params'   => '',
                  'btn_type' => 'btn-success pull-right',
                  'attrs' => array('style' => 'margin-bottom: 10px;') )
              );
            ?>
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Productor</th>
                  <th>Limite</th>
                  <th>Facturado</th>
                  <th>Saldo</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($datos_s['proveedores'] as $proveedor) {?>
                <tr>
                  <td>
                    <a href="<?php echo base_url('panel/proveedores_facturacion/admin?fid_proveedor='.$proveedor->id_proveedor.'&'.String::getVarsLink(array('fid_proveedor', 'fstatus')) ); ?>"><?php echo $proveedor->nombre_fiscal; ?></a>
                  </td>
                  <td><?php echo String::formatoNumero($proveedor->limite, 2, '$', false); ?></td>
                  <td><?php echo String::formatoNumero($proveedor->facturado, 2, '$', false); ?></td>
                  <td><?php echo String::formatoNumero($proveedor->saldo, 2, '$', false); ?></td>
                </tr>
            <?php }?>
              </tbody>
            </table>

            <?php
            //Paginacion
            $this->pagination->initialize(array(
                'base_url'      => base_url($this->uri->uri_string()).'?'.String::getVarsLink(array('pag')).'&',
                'total_rows'    => $datos_s['total_rows'],
                'per_page'      => $datos_s['items_per_page'],
                'cur_page'      => $datos_s['result_page']*$datos_s['items_per_page'],
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
