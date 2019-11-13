    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Lista de Pagos
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Lista de Pagos</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <!-- <a href="<?php echo base_url('panel/cuentas_pagar/saldos_pdf/?'.MyString::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-print"></i> Imprimir</a> |
            <a href="<?php echo base_url('panel/cuentas_pagar/saldos_xls/?'.MyString::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-table"></i> Excel</a> -->

            <form action="<?php echo base_url('panel/cuentas_pagar/lista_pagos/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-large search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-large search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2'); ?>" size="10"> |

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa', (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: '') ); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa', (isset($empresa->id_empresa)? $empresa->id_empresa: '')); ?>"> |

                <label for="dproveedor">Cliente</label>
                <input type="text" name="dproveedor" class="input-large search-query" id="dproveedor" value="<?php echo set_value_get('dproveedor', (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: '') ); ?>" size="73">
                <input type="hidden" name="did_proveedor" id="did_proveedor" value="<?php echo set_value_get('did_proveedor', (isset($empresa->id_empresa)? $empresa->id_empresa: '')); ?>">

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th style="width:68px;">FECHA</th>
                  <th>CLIENTE</th>
                  <th>EMPRESA</th>
                  <th>CONCEPTO</th>
                  <th>IMPORTE</th>
                  <th>OPCIONES</th>
                </tr>
              </thead>
              <tbody>
            <?php
            $total_saldo = $total_abono = $total_cargo = 0;
            foreach($data['abonos'] as $cuenta){
            ?>
                <tr>
                  <td><?php echo $cuenta->fecha; ?></td>
                  <td><?php echo $cuenta->nombre_fiscal; ?></td>
                  <td><?php echo $cuenta->empresa; ?></td>
                  <td><?php echo $cuenta->concepto; ?></td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero($cuenta->total_abono, 2, '$', false); ?></td>
                  <td>
          					<?php
          					echo $this->usuarios_model->getLinkPrivSm('cuentas_pagar/eliminar_movimiento/', array(
                        'params'   => 'id_movimiento='.$cuenta->id_movimiento.'&'.MyString::getVarsLink(array('id_movimiento', 'fstatus', 'msg')),
                        'btn_type' => 'btn-danger',
                        'attrs' => array('onclick' => "msb.confirm('Estas seguro de Eliminar la operación?<br>Nota: Se eliminara tambien en cuentas por pagar y banco si esta ligada la operacion.<br><strong>Este cambio no se puede revertir</strong>', 'cuentas', this); return false;"))
                      );
                    echo $this->usuarios_model->getLinkPrivSm('cuentas_pagar/imprimir_recibo/', array(
                        'params'   => 'id_movimiento='.$cuenta->id_movimiento,
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
                'base_url'      => base_url($this->uri->uri_string()).'?'.MyString::getVarsLink(array('pag')).'&',
                'total_rows'    => $data['total_rows'],
                'per_page'      => $data['items_per_page'],
                'cur_page'      => $data['result_page']*$data['items_per_page'],
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
