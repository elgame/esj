    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Facturación
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Facturas</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/facturacion/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="ffolio">Folio</label>
                <input type="text" name="ffolio" id="ffolio" value="<?php echo set_value_get('ffolio'); ?>" class="input-mini search-query" autofocus>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-medium search-query" id="dempresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa'); ?>">


                <label for="dcliente">Cliente</label>
                <input type="text" name="dcliente" class="input-medium search-query" id="dcliente" value="<?php echo set_value_get('dcliente'); ?>" size="73">
                <input type="hidden" name="fid_cliente" id="fid_cliente" value="<?php echo set_value_get('fid_cliente'); ?>">
                <br>
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="text" name="ffecha1" class="input-small search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="text" name="ffecha2" class="input-small search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2'); ?>" size="10">

                <label for="fstatus">Estado</label>
                <select name="fstatus" class="input-medium" id="fstatus">
                  <option value="">TODAS</option>
                  <option value="pa" <?php echo set_select_get('fstatus', 'pa'); ?>>PAGADAS</option>
                  <option value="p" <?php echo set_select_get('fstatus', 'p'); ?>>PENDIENTE</option>
                  <option value="ca" <?php echo set_select_get('fstatus', 'ca'); ?>>CANCELADAS</option>
                </select>

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <?php
            echo $this->empleados_model->getLinkPrivSm('facturacion/agregar/', array(
                    'params'   => '',
                    'btn_type' => 'btn-success pull-right',
                    'attrs' => array('style' => 'margin-bottom: 10px;') )
                );
             ?>
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Serie-Folio</th>
                  <th>Cliente</th>
                  <th>Empresa</th>
                  <th>Forma de Pago</th>
                  <th>Estado</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($datos_s['fact'] as $fact) {?>
                <tr>
                  <td><?php echo $fact->fecha; ?></td>
                  <td><?php echo $fact->serie.' - '.$fact->folio; ?></td>
                  <td><?php echo $fact->nombre_fiscal; ?></td>
                  <td><?php echo $fact->empresa; ?></td>
                  <td><?php echo $fact->condicion_pago==='cr' ? 'Credito' : 'Contado'; ?></td>
                  <td><?php echo ($fact->status === 'p') ? 'Pendiente' : (($fact->status === 'pa') ? 'Pagada' : 'Cancelada'); ?></td>
                  <td class="center">
                    <?php
                      if ($fact->status === 'p')
                      {
                        echo $this->empleados_model->getLinkPrivSm('facturacion/pagar/', array(
                          'params'   => 'id='.$fact->id_factura,
                          'btn_type' => 'btn-success',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de Pagar la factura?', 'Facturas', this); return false;"))
                        );
                      }

                      if ($fact->status !== 'ca')
                      {
                        echo $this->empleados_model->getLinkPrivSm('facturacion/cancelar/', array(
                          'params'   => 'id='.$fact->id_factura,
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de Cancelar la factura?', 'Facturas', this); return false;"))
                        );
                      }

                      echo $this->empleados_model->getLinkPrivSm('facturacion/imprimir/', array(
                          'params'   => 'id='.$fact->id_factura,
                          'btn_type' => 'btn-info',
                          'attrs' => array('target' => "_blank"))
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
