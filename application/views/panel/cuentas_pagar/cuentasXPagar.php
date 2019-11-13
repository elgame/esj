    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Cuentas por Pagar
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Cuentas por Pagar</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <a href="<?php echo base_url('panel/cuentas_pagar/saldos_pdf/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-print"></i> Imprimir</a> |
            <a href="<?php echo base_url('panel/cuentas_pagar/saldos_xls/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-table"></i> Excel</a> |
            <a href="<?php echo base_url('panel/cuentas_pagar/estado_cuenta_pdf/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-print"></i> Estado cuenta</a> |
            <a href="<?php echo base_url('panel/cuentas_pagar/estado_cuenta_xls/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-table"></i> Estado cuenta</a> |
            <a href="<?php echo base_url('panel/cuentas_pagar/rpt_compras_xls/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-table"></i> Compras pagadas</a>

            <form action="<?php echo base_url('panel/cuentas_pagar/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-large search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-large search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2'); ?>" size="10"> |

                <label for="ftipo">Pagos:</label>
                <select name="ftipo" id="ftipo" class="input-large search-query">
                  <option value="to" <?php echo set_select_get('ftipo', 'to'); ?>>Todas</option>
                  <option value="pp" <?php echo set_select_get('ftipo', 'pp'); ?>>Pendientes por pagar</option>
                  <option value="pv" <?php echo set_select_get('ftipo', 'pv'); ?>>Plazo vencido</option>
                </select>
                <label for="fcon_saldo">Con saldo:</label>
                <input type="checkbox" name="fcon_saldo" id="fcon_saldo" value="si" <?php echo isset($_GET['fcon_saldo'])? 'checked': ''; ?>>
                <br>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa', (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: '') ); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa', (isset($empresa->id_empresa)? $empresa->id_empresa: '')); ?>">

                <label for="dcliente">Proveedor</label>
                <input type="text" name="dcliente" class="input-large search-query" id="dcliente" value="<?php echo set_value_get('dcliente'); ?>" size="73">
                <input type="hidden" name="fid_cliente" id="fid_cliente" value="<?php echo set_value_get('fid_cliente'); ?>"> |

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Proveedor</th>
                  <th>Cargos</th>
                  <th>Abonos</th>
                  <th>Saldo</th>
                </tr>
              </thead>
              <tbody>
            <?php
            $total_saldo = $total_abono = $total_cargo = 0;
            foreach($data['cuentas'] as $cuenta){
              $total_cargo += $cuenta->total;
              $total_abono += $cuenta->abonos;
              $total_saldo += $cuenta->saldo;
            ?>
                <tr>
                  <td><a href="<?php echo base_url('panel/cuentas_pagar/cuenta').'?id_proveedor='.$cuenta->id_proveedor.'&'.
                    String::getVarsLink(array('id_proveedor', 'msg')); ?>" class="linksm lkzoom"><?php echo $cuenta->nombre; ?></a></td>
                  <td style="text-align: right;"><?php echo String::formatoNumero($cuenta->total, 2, '$', false); ?></td>
                  <td style="text-align: right;"><?php echo String::formatoNumero($cuenta->abonos, 2, '$', false); ?></td>
                  <td style="text-align: right;"><?php echo String::formatoNumero($cuenta->saldo, 2, '$', false); ?></td>
                </tr>
            <?php }?>
                <tr style="background-color:#ccc;font-weight: bold;">
                  <td class="a-r">Total x Página:</td>
                  <td style="text-align: right;"><?php echo String::formatoNumero($total_cargo, 2, '$', false); ?></td>
                  <td style="text-align: right;"><?php echo String::formatoNumero($total_abono, 2, '$', false); ?></td>
                  <td style="text-align: right;"><?php echo String::formatoNumero($total_saldo, 2, '$', false); ?></td>
                </tr>
                <tr style="background-color:#ccc;font-weight: bold;">
                  <td class="a-r">Total:</td>
                  <td style="text-align: right;"><?php echo String::formatoNumero($data['ttotal_cargos'], 2, '$', false); ?></td>
                  <td style="text-align: right;"><?php echo String::formatoNumero($data['ttotal_abonos'], 2, '$', false); ?></td>
                  <td style="text-align: right;"><?php echo String::formatoNumero($data['ttotal_saldo'], 2, '$', false); ?></td>
                </tr>
              </tbody>
            </table>

            <?php
            //Paginacion
            $this->pagination->initialize(array(
                'base_url'      => base_url($this->uri->uri_string()).'?'.String::getVarsLink(array('pag')).'&',
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
