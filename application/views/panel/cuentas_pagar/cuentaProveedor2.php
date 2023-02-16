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
            <a href="<?php echo base_url('panel/cuentas_pagar/?'.MyString::getVarsLink(array('msg'))); ?>" class="linksm">
              <i class="icon-chevron-left"></i> Atras</a> |
            <a href="<?php echo base_url('panel/cuentas_pagar/cuenta2_pdf/?'.MyString::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-print"></i> Imprimir</a> |
            <a href="<?php echo base_url('panel/cuentas_pagar/cuenta2_xls/?'.MyString::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-table"></i> Excel</a> |
            <a href="<?php echo base_url('panel/cuentas_pagar/cuenta2_all_pdf/?'.MyString::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-print"></i> Imprimir Todos</a>

            <form action="<?php echo base_url('panel/cuentas_pagar/cuenta2'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters span12">
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-medium search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-medium search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2'); ?>" size="10"> |

                <label for="ftipo">Pagos:</label>
                <select name="ftipo" id="ftipo" class="input-large search-query">
                  <option value="to" <?php echo set_select_get('ftipo', 'to'); ?>>Todas</option>
                  <option value="pp" <?php echo set_select_get('ftipo', 'pp'); ?>>Pendientes por pagar</option>
                  <option value="pv" <?php echo set_select_get('ftipo', 'pv'); ?>>Plazo vencido</option>
                </select><br>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa', (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: '') ); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa', (isset($empresa->id_empresa)? $empresa->id_empresa: '')); ?>">

                <label for="dproveedor">Proveedor</label>
                <input type="text" name="dproveedor" class="input-large search-query" id="dproveedor" value="<?php echo set_value_get('dproveedor'); ?>" size="73">
                <input type="hidden" name="fid_proveedor" id="fid_proveedor" value="<?php echo set_value_get('fid_proveedor'); ?>"> |

                <input type="hidden" name="id_proveedor" id="id_proveedor" value="<?php echo set_value_get('id_proveedor'); ?>">

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <div class="span6 hide" id="hide_agregar_abono">
              <?php echo $this->usuarios_model->getLinkPrivSm('cuentas_pagar/agregar_abono/', array(
                'params'   => "",
                'btn_type' => 'btn-success pull-right btn_abonos_masivo',
                'attrs' => array('style' => 'margin-top: 30px; display:none;', 'rel' => 'superbox-50x500') )
              ); ?>
            </div>

            <div id="sumaRowsSel" style="display:none;position:fixed;top:200px;right: 0px;width: 100px;background-color:#FFFF00;padding:3px 0px 3px 3px;font-size:14px;font-weight:bold;"></div>

            Tipo de cambio: <input type="number" step="any" id="tipo_cambio" value="" min="0.001">
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th style="width:15px;"></th>
                  <th>Fecha F.</th>
                  <th>Fecha</th>
                  <th>Serie</th>
                  <th>Folio</th>
                  <th>Concepto</th>
                  <th>Proveedor</th>
                  <th>Cargo</th>
                  <th>Abono</th>
                  <th>Saldo <input type="checkbox" id="select-all-abonom" title="Seleccionar/Deseleccionar"></th>
                  <th>Estado</th>
                  <th>F. Vencimiento</th>
                  <th>D. Transcurridos</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td></td>
                  <td>Saldo anterior a <?php echo $data['fecha1']; ?></td>
                  <td></td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero(
                      (isset($data['anterior'][0]->total)? $data['anterior'][0]->total: 0), 2, '$', false ); ?></td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero(
                      (isset($data['anterior'][0]->abonos)? $data['anterior'][0]->abonos: 0), 2, '$', false ); ?></td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero(
                      (isset($data['anterior'][0]->saldo)? $data['anterior'][0]->saldo: 0), 2, '$', false ); ?></td>
                  <td></td>
                  <td></td>
                  <td></td>
                </tr>
            <?php
            $total_cargo = 0;
            $total_abono = 0;
            $total_saldo = 0;
            // if(isset($data['anterior'][0]->saldo)){ //se suma a los totales saldo anterior
            //  $total_cargo += $data['anterior'][0]->total;
            //  $total_abono += $data['anterior'][0]->abonos;
            //  $total_saldo += $data['anterior'][0]->saldo;
            // }
            foreach($data['cuentas'] as $cuenta){
              $ver = true;

              if($ver){
                $total_cargo += $cuenta->cargo;
                $total_abono += $cuenta->abono;
                $total_saldo += $cuenta->saldo;
            ?>
                <tr>
                  <td>
                  <?php if($cuenta->estado == 'Pendiente'){ ?>
                    <input type="checkbox" class="change_spago" <?php echo ($cuenta->en_pago>0? 'checked': ''); ?>
                        data-idcompra="<?php echo $cuenta->id_compra; ?>" data-idproveedor="<?php echo $this->input->get('id_proveedor'); ?>"
                        data-monto="<?php echo $cuenta->saldo; ?>" data-status="<?php echo $cuenta->status ?>"
                        data-folio="<?php echo $cuenta->serie.$cuenta->folio; ?>">
                  <?php } ?>
                  </td>
                  <td><?php echo $cuenta->fecha_factura; ?></td>
                  <td><?php echo $cuenta->fecha; ?></td>
                  <td><?php echo $cuenta->serie; ?></td>
                  <td>
                    <a href="<?php echo base_url('panel/cuentas_pagar/detalle/').'?id='.$cuenta->id_compra.'&tipo='.$cuenta->tipo.
                      '&'.MyString::getVarsLink(array('id', 'tipo', 'enviar', 'msg')); ?>" class="linksm lkzoom"><?php echo $cuenta->folio; ?></a>
                  </td>
                  <td>
                    <a href="<?php echo base_url('panel/cuentas_pagar/detalle/').'?id='.$cuenta->id_compra.'&tipo='.$cuenta->tipo.
                          '&'.MyString::getVarsLink(array('id', 'tipo', 'enviar', 'msg')); ?>" class="linksm lkzoom"><?php echo $cuenta->concepto ?></a>
                  </td>
                  <td><?php echo $cuenta->proveedor ?></td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero($cuenta->cargo, 2, "$", false); ?></td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero($cuenta->abono, 2, "$", false); ?></td>
                  <td class="sel_abonom" data-id="<?php echo $cuenta->id_compra; ?>" data-tipo="<?php echo $cuenta->tipo ?>" style="text-align: right;"><?php echo MyString::formatoNumero($cuenta->saldo, 2, "$", false); ?></td>
                  <td><?php echo $cuenta->estado; ?></td>
                  <td><?php echo $cuenta->fecha_vencimiento; ?></td>
                  <td><?php echo $cuenta->dias_transc; ?></td>
                </tr>
            <?php }
            } ?>
                <tr style="background-color:#ccc;font-weight: bold;">
                  <td colspan="7" class="a-r">Totales:</td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero($total_cargo, 2, "$", false); ?></td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero($total_abono, 2, "$", false); ?></td>
                  <td style="text-align: right;"><?php echo MyString::formatoNumero($total_saldo, 2, "$", false); ?></td>
                  <td colspan="3"></td>
                </tr>
              </tbody>
            </table>
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
