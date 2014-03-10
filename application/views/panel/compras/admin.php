    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Compras
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-shopping-cart"></i> Compras</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/compras/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="ffolio">Folio</label>
                <input type="number" name="ffolio" id="ffolio" value="<?php echo set_value_get('ffolio'); ?>" class="input-mini search-query" autofocus>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="empresa" value="<?php echo set_value_get('dempresa'); ?>" size="73">
                <input type="hidden" name="did_empresa" id="empresaId" value="<?php echo set_value_get('did_empresa'); ?>">

                <label for="dproveedor">Proveedor</label>
                <input type="text" name="dproveedor" class="input-large search-query" id="proveedor" value="<?php echo set_value_get('dproveedor'); ?>" size="73">
                <input type="hidden" name="did_proveedor" id="proveedorId" value="<?php echo set_value_get('did_proveedor'); ?>">

                <label for="ftipo">Tipo</label>
                <select name="ftipo" class="input-medium" id="ftipo">
                  <option value="">TODAS</option>
                  <option value="f" <?php echo set_select_get('ftipo', 'f'); ?>>COMPRAS</option>
                  <option value="t" <?php echo set_select_get('ftipo', 't'); ?>>GASTOS</option>
                </select>

                <br>
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="datetime-local" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m-01\TH:i')); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="datetime-local" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', $fecha); ?>" size="10">

                <label for="fstatus">Estado</label>
                <select name="fstatus" class="input-medium" id="fstatus">
                  <option value="">TODAS</option>
                  <option value="p" <?php echo set_select_get('fstatus', 'p'); ?>>PENDIENTES</option>
                  <option value="pa" <?php echo set_select_get('fstatus', 'pa'); ?>>PAGADAS</option>
                  <option value="ca" <?php echo set_select_get('fstatus', 'ca'); ?>>CANCELADAS</option>
                </select>

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Serie-Folio</th>
                  <th>Proveedor</th>
                  <th>Empresa</th>
                  <th>Tipo</th>
                  <th>Estado</th>
                  <th>XML?</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($compras['compras'] as $compra) {?>
                <tr>
                  <td><?php echo substr($compra->fecha, 0, 10); ?></td>
                  <td>
                    <span class="label"><?php echo ($compra->serie !== '' ? $compra->serie.'-' : '').$compra->folio; ?></span>
                    <?php if ($compra->tipo === 'nc'){ ?>
                      <span class="label label-warning">Nota de Crédito</span>
                    <?php } ?>
                  </td>
                  <td><?php echo $compra->proveedor; ?></td>
                  <td><?php echo $compra->empresa; ?></td>
                  <td>
                    <?php $tipo = $compra->isgasto === 't' ? 'GASTO' : 'COMPRA' ; ?>
                    <span class="label label-info"><?php echo $tipo ?></span>
                  </td>
                  <td><?php
                          $texto = 'CANCELADA';
                          $label = 'warning';
                        if ($compra->status === 'p') {
                          $texto = 'PENDIENTE';
                          $label = 'warning';
                        } else if ($compra->status === 'pa') {
                          $texto = 'PAGADA';
                          $label = 'success';
                        }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td><?php
                          $texto = 'NO';
                          $label = 'warning';
                        if ($compra->xml) {
                          $texto = 'SI';
                          $label = 'success';
                        }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td class="center">
                    <?php

                      if ($compra->status === 'p' || $compra->id_nc != '')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('compras/cancelar/', array(
                          'params'   => 'id='.$compra->id_compra,
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de Cancelar la compra?', 'Compras', this); return false;"))
                        );
                      }

                      if ($compra->isgasto === 't')
                      {
                        if ($compra->tipo === 'c')
                        {
                          echo $this->usuarios_model->getLinkPrivSm('gastos/ver/', array(
                            'params'   => 'id='.$compra->id_compra.'&idp='.$compra->id_proveedor,
                            'btn_type' => 'btn-success',
                            'attrs' => array('rel' => 'superbox-80x550'))
                          );

                          echo $this->usuarios_model->getLinkPrivSm('gastos/agregar_nota_credito/', array(
                            'params'   => 'id='.$compra->id_compra,
                            'btn_type' => '',
                            'attrs' => array(''))
                          );
                        }
                        else
                        {
                          echo $this->usuarios_model->getLinkPrivSm('gastos/ver_nota_credito/', array(
                            'params'   => 'id='.$compra->id_compra,
                            'btn_type' => 'btn-success',
                            'attrs' => array(''))
                          );
                        }

                      }
                      else
                      {
                        if ($compra->tipo === 'c')
                        {
                          echo $this->usuarios_model->getLinkPrivSm('compras/ver/', array(
                            'params'   => 'id='.$compra->id_compra.'&idp='.$compra->id_proveedor,
                            'btn_type' => 'btn-success',
                            'attrs' => array('rel' => 'superbox-80x550'))
                          );

                          echo $this->usuarios_model->getLinkPrivSm('compras/agregar_nota_credito/', array(
                            'params'   => 'id='.$compra->id_compra,
                            'btn_type' => '')
                          );
                        }
                        else
                        {
                          echo $this->usuarios_model->getLinkPrivSm('compras/ver_nota_credito/', array(
                            'params'   => 'id='.$compra->id_compra,
                            'btn_type' => 'btn-success')
                          );
                        }
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
                'total_rows'    => $compras['total_rows'],
                'per_page'      => $compras['items_per_page'],
                'cur_page'      => $compras['result_page']*$compras['items_per_page'],
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
