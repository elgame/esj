    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Complementos de Pago
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Complementos de Pago (Pago en parcialidades o Diferido)</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/facturacion/pago_parcialidad'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="ffolio">Folio</label>
                <input type="number" name="ffolio" id="ffolio" value="<?php echo set_value_get('ffolio'); ?>" class="input-mini search-query" autofocus>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa'); ?>">


                <label for="dcliente">Cliente</label>
                <input type="text" name="dcliente" class="input-large search-query" id="dcliente" value="<?php echo set_value_get('dcliente'); ?>" size="73">
                <input type="hidden" name="fid_cliente" id="fid_cliente" value="<?php echo set_value_get('fid_cliente'); ?>">

                <label for="dobserv">Obervaciones</label>
                <input type="text" name="dobserv" class="input-large search-query" id="dobserv" value="<?php echo set_value_get('dobserv'); ?>" size="73">
                <br>
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date("Y-m-01")); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', $fecha); ?>" size="10">

                <label for="fstatus">Estado</label>
                <select name="fstatus" class="input-medium" id="fstatus">
                  <option value="">TODAS</option>
                  <option value="pa" <?php echo set_select_get('fstatus', 'pa'); ?>>PAGADAS</option>
                  <option value="p" <?php echo set_select_get('fstatus', 'p'); ?>>PENDIENTE</option>
                  <option value="ca" <?php echo set_select_get('fstatus', 'ca'); ?>>CANCELADAS</option>
                  <option value="b" <?php echo set_select_get('fstatus', 'b'); ?>>PREFACTURAS</option>
                </select>

                <label for="ftipo">Tipo</label>
                <select name="ftipo" class="input-medium" id="ftipo">
                  <option value="com_pago" <?php echo set_select_get('ftipo', 'com_pago'); ?>>Complementos de pago</option>
                  <option value="parcial" <?php echo set_select_get('ftipo', 'parcial'); ?>>Pago en parcialidades</option>
                </select>

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Serie-Folio</th>
                  <th>Cliente</th>
                  <th>Empresa</th>
                  <th>Total</th>
                  <th>Observaciones</th>
                  <th>Forma de Pago</th>
                  <th>Estado</th>
                  <th>Estado Timbre</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php
            if (isset($datos_s)) {
            foreach($datos_s['fact'] as $fact) {?>
                <tr>
                  <td style="width:70px;"><?php echo $fact->fecha; ?></td>
                  <td>
                    <span class="label"><?php echo ($fact->serie ? $fact->serie.' - ' : '').$fact->folio; ?></span>

                    <?php if ($fact->id_nc !== null){ ?>
                      <br><span class="label label-warning">Nota de Crédito</span>
                    <?php } ?>

                  </td>
                  <td><?php echo $fact->nombre_fiscal; ?></td>
                  <td><?php echo $fact->empresa; ?></td>
                  <td><?php echo MyString::formatoNumero($fact->total, 2, '$', false); ?></td>
                  <td><?php echo $fact->observaciones; ?></td>
                  <td><?php $texto = $fact->condicion_pago === 'cr' ? 'Credito' : 'Contado'; ?>
                      <span class="label label-info"><?php echo $texto ?></span>
                  </td>
                  <td><?php
                        if ($fact->status === 'b')
                      {
                        $texto = 'Prefactura';
                        $label = 'Inverse';
                      } else {
                        $texto = 'Cancelada';
                        $label = 'important';
                        if ($fact->status === 'p') {
                          $texto = 'Pendiente';
                          $label = 'warning';
                        } else if ($fact->status === 'pa') {
                          $texto = 'Pagada';
                          $label = 'success';
                        }
                      }?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td><?php
                      if ($fact->status === 'b')
                      {
                        $texto = 'No Timbrado';
                        $label = 'Inverse';
                      } else {
                        $texto = 'Cancelado';
                        $label = 'Inverse';
                        if ($fact->status_timbrado === 'p') {
                          $texto = 'Pendiente';
                          $label = 'warning';
                        } else if ($fact->status_timbrado === 't') {
                          $texto = 'Timbrado';
                          $label = 'success';
                        }
                      }?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td class="center">
                    <?php

                      if ($fact->status !== 'b')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('facturacion/imprimir/', array(
                          'params'   => 'id='.$fact->id_factura,
                          'btn_type' => 'btn-info',
                          'attrs' => array('target' => "_blank"))
                        );

                        if ($fact->status !== 'ca')
                        {

                          echo $this->usuarios_model->getLinkPrivSm('facturacion/cancelar/', array(
                            'params'   => 'id='.$fact->id_factura.'&sec=pp',
                            'btn_type' => 'btn-danger',
                            'attrs' => array('onclick' => "msb.confirm('Estas seguro de Cancelar la factura?<br><strong>NOTA: Esta opción no se podra revertir.</strong>', 'Facturas', this); return false;"))
                          );
                        }

                        if ($fact->status_timbrado === 't')
                        {
                          echo '<a class="btn" href="'.base_url('panel/facturacion/xml/?id='.$fact->id_factura).'" title="Descargar XML" target="_BLANK"><i class="icon-download-alt icon-white"></i> <span class="hidden-tablet">XML</span></a>';
                        }

                        echo $this->usuarios_model->getLinkPrivSm('facturacion/enviar_documentos/', array(
                          'params'   => 'id='.$fact->id_factura,
                          'btn_type' => 'btn-success',
                          'attrs' => array('rel' => 'superbox-50x450'))
                        );

                      } else {
                        echo '<a class="btn btn-success" href="'.base_url('panel/facturacion/agregar/?idb='.$fact->id_factura).'"><i class="icon-certificate icon-white"></i> <span class="hidden-tablet">Timbrar</span></a>';
                      }
                    ?>
                  </td>
                </tr>
            <?php }
            }?>

            <?php
            if (isset($datos_cp)) {
            foreach($datos_cp['pagos'] as $fact) {?>
                <tr>
                  <td style="width:70px;"><?php echo $fact->fecha; ?></td>
                  <td>
                    <span class="label"><?php echo ($fact->serie ? $fact->serie.' - ' : '').$fact->folio; ?></span>
                  </td>
                  <td><?php echo $fact->nombre_fiscal; ?></td>
                  <td><?php echo $fact->empresa; ?></td>
                  <td><?php echo MyString::formatoNumero($fact->monto, 2, '$', false); ?></td>
                  <td></td>
                  <td></td>
                  <td><?php
                      if ($fact->status === 'facturada')
                      {
                        $texto = 'Timbrada';
                        $label = 'success';
                      } else {
                        $texto = 'Cancelada';
                        $label = 'important';
                      }?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td></td>
                  <td class="center">

                    <a class="btn btn-info" href="<?php echo base_url('panel/cuentas_cobrar/imprimir_com_pago/?id='.$fact->id) ?>" target="_blank" title="Imprimir">
                      <i class="icon-print icon-white"></i> <span class="hidden-tablet">Imprimir</span></a>
                    <a class="btn" href="<?php echo base_url('panel/cuentas_cobrar/xml_com_pago/?id='.$fact->id) ?>" target="_blank" title="Imprimir">
                      <i class="icon-download-alt icon-white"></i> <span class="hidden-tablet">XML</span></a>
                    <?php
                      // if ($fact->status !== 'cancelada')
                      // {
                        echo '<a class="btn btn-danger" href="'.base_url('panel/cuentas_cobrar/cancelar_com_pago/?id='.$fact->id).'"
                          onclick="msb.confirmCancel(\'Estas seguro de Cancelar la factura?<br><strong>NOTA: Esta opción no se podra revertir.</strong>\', \'Cancelar Complementos de Pago\', this, callCancelFactura); return false;" title="Cancelar">
                        <i class="icon-ban-circle icon-white"></i> <span class="hidden-tablet">Cancelar</span></a>';
                      // }

                      // if ($fact->status !== 'b')
                      // {


                      //   if ($fact->status_timbrado === 't')
                      //   {
                      //     echo '<a class="btn" href="'.base_url('panel/facturacion/xml/?id='.$fact->id_factura).'" title="Descargar XML" target="_BLANK"><i class="icon-download-alt icon-white"></i> <span class="hidden-tablet">XML</span></a>';
                      //   }

                      //   echo $this->usuarios_model->getLinkPrivSm('facturacion/enviar_documentos/', array(
                      //     'params'   => 'id='.$fact->id_factura,
                      //     'btn_type' => 'btn-success',
                      //     'attrs' => array('rel' => 'superbox-50x450'))
                      //   );

                      // } else {
                      //   echo '<a class="btn btn-success" href="'.base_url('panel/facturacion/agregar/?idb='.$fact->id_factura).'"><i class="icon-certificate icon-white"></i> <span class="hidden-tablet">Timbrar</span></a>';
                      // }
                    ?>
                  </td>
                </tr>
            <?php }
            }?>
              </tbody>
            </table>

            <?php
            if (isset($datos_s)) {
              //Paginacion
              $this->pagination->initialize(array(
                  'base_url'      => base_url($this->uri->uri_string()).'?'.MyString::getVarsLink(array('pag')).'&',
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
            } elseif (isset($datos_cp)) {
              //Paginacion
              $this->pagination->initialize(array(
                  'base_url'      => base_url($this->uri->uri_string()).'?'.MyString::getVarsLink(array('pag')).'&',
                  'total_rows'    => $datos_cp['total_rows'],
                  'per_page'      => $datos_cp['items_per_page'],
                  'cur_page'      => $datos_cp['result_page']*$datos_cp['items_per_page'],
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
            }
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
