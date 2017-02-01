    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Productos Regresados
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-shopping-cart"></i> Productos Regresados</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/productos_regreso/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="ffolio">Folio</label>
                <input type="number" name="ffolio" id="ffolio" value="<?php echo set_value_get('ffolio'); ?>" class="input-mini search-query" autofocus>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="empresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="empresaId" value="<?php echo set_value_get('did_empresa'); ?>">

                <label for="dproveedor">Proveedor</label>
                <input type="text" name="dproveedor" class="input-large search-query" id="proveedor" value="<?php echo set_value_get('dproveedor'); ?>" size="73">
                <input type="hidden" name="did_proveedor" id="proveedorId" value="<?php echo set_value_get('did_proveedor'); ?>">

                <br>
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="datetime-local" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m-01\TH:i')); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="datetime-local" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', $fecha); ?>" size="10">

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <?php
              echo $this->usuarios_model->getLinkPrivSm('productos_regreso/agregar/', array(
                'params'   => 'w=c',
                'btn_type' => 'btn-success pull-right',
                'attrs' => array('style' => 'margin-bottom: 10px;') )
              );
             ?>

             <div id="sumaRowsSel" style="display:none;position:fixed;top:200px;right: 0px;width: 130px;background-color:#FFFF00;padding:3px 0px 3px 3px;font-size:16px;font-weight:bold;"></div>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th></th>
                  <th>Fecha</th>
                  <th>Folio</th>
                  <th>Proveedor</th>
                  <th>Empresa</th>
                  <th>Importe</th>
                  <th>Autorizada</th>
                  <th>Estado</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($ordenes['ordenes'] as $orden) { ?>
                <tr>
                  <td>
                    <?php if (($orden->status === 'a' || $orden->status === 'r') && isset($_GET['did_proveedor']) && $_GET['did_proveedor'] !== '' &&
                              isset($_GET['did_empresa']) && $_GET['did_empresa'] !== ''){ ?>
                      <input type="checkbox" class="addToFactura" value="<?php echo $orden->id_orden ?>" data-total="<?php echo $orden->total; ?>">
                    <?php } ?>
                  </td>
                  <td><?php echo substr($orden->fecha, 0, 10); ?></td>
                  <td><span class="label"><?php echo $orden->folio; ?></span></td>
                  <td><?php echo $orden->proveedor; ?></td>
                  <td><?php echo $orden->empresa; ?></td>
                  <td style="text-align: right;"><?php echo String::formatoNumero($orden->total, 2, '$', false); ?></td>
                  <td><span class="label label-info"><?php echo $orden->autorizado === 't' ? 'SI' : 'NO'?></span></td>
                  <td><?php
                          $texto = 'CANCELADA';
                          $label = 'warning';
                        if ($orden->status === 'n') {
                          $texto = 'REGISTRADA';
                          $label = 'success';
                        } else if ($orden->status === 'f') {
                          $texto = 'FACTURADA';
                          $label = 'success';
                        }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td class="center">
                    <?php

                      if ($orden->status === 'r')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('productos_regreso/modificar/', array(
                          'params'   => 'id='.$orden->id_orden.'&w=c',
                          'btn_type' => 'btn-info',
                          'attrs' => array())
                        );
                      }

                      if ($orden->status === 'a' || $orden->status === 'n' || $orden->status === 'ca')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('productos_regreso/ver/', array(
                          'params'   => 'id='.$orden->id_orden.'&w=c',
                          'btn_type' => 'btn-success',
                          'attrs' => array())
                        );
                      }

                      if ($orden->status === 'a' || $orden->status === 'f' || $orden->status === 'p')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('productos_regreso/imprimir/', array(
                          'params'   => 'id='.$orden->id_orden.'&p=true',
                          'btn_type' => 'btn-success',
                          'attrs' => array('target' => '_BLANK'))
                        );
                      }

                      if ($orden->status !== 'r' && $orden->status !== 'f' && $orden->status !== 'ca' && $orden->prod_sincompras == 0)
                      {
                        echo $this->usuarios_model->getLinkPrivSm('productos_regreso/cancelar/', array(
                          'params'   => 'id='.$orden->id_orden.'&w=c',
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de Cancelar el regreso de productos?', 'Ordenes de Compras', this); return false;"))
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
                'total_rows'    => $ordenes['total_rows'],
                'per_page'      => $ordenes['items_per_page'],
                'cur_page'      => $ordenes['result_page']*$ordenes['items_per_page'],
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
