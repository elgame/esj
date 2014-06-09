    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Pagos de bascula
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Pagos de bascula</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <!-- <a href="<?php echo base_url('panel/banco/saldos_pdf/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-print"></i> Imprimir</a> |
            <a href="<?php echo base_url('panel/banco/saldos_xls/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-table"></i> Excel</a>

            <form action="<?php echo base_url('panel/banco/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-large search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-large search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2'); ?>" size="10"> |

                <label for="vertodos">Tipo:</label>
                <select name="vertodos" id="vertodos" class="input-large search-query">
                  <option value="" <?php echo set_select_get('vertodos', ''); ?>>Todas</option>
                  <option value="tran" <?php echo set_select_get('vertodos', 'tran'); ?>>En transito</option>
                  <option value="notran" <?php echo set_select_get('vertodos', 'notran'); ?>>Cobrados (no transito)</option>
                </select><br>

                <label for="fid_banco">Banco:</label>
                <select name="fid_banco" id="fid_banco" class="input-large search-query">
                  <option value="" <?php echo set_select_get('fid_banco', ''); ?>></option>
              <?php
              foreach ($bancos['bancos'] as $key => $banco) {
              ?>
                  <option value="<?php echo $banco->id_banco; ?>" <?php echo set_select_get('fid_banco', $banco->id_banco); ?>><?php echo $banco->nombre; ?></option>
              <?php
              } ?>
                </select>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa', (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: '') ); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa', (isset($empresa->id_empresa)? $empresa->id_empresa: '')); ?>">

                <button type="submit" class="btn">Enviar</button>
              </div>
            </form> -->

            <form action="<?php echo base_url('panel/banco_pagos/bascula/'); ?>" method="post">
              <div class="row-fluid">
                <div class="span12">
                  <button type="submit" class="btn btn-success pull-right">Guardar</button>
              <?php if ($rows_completos)
              { ?>
                  <select name="cuenta_retiro" id="cuenta_retiro">
                  <?php
                  $primera_cuenta = null;
                  foreach ($data['cuentas'] as $keyc => $cuentasp)
                  {
                    if($cuentasp->id_banco == '2'){ //banamex
                      if($primera_cuenta == null)
                        $primera_cuenta = $cuentasp->id_cuenta;
                  ?>
                    <option value="<?php echo $cuentasp->id_cuenta; ?>"><?php echo $cuentasp->alias.' * '.String::formatoNumero($cuentasp->saldo); ?></option>
                  <?php
                    }
                  } ?>
                  </select>
                  <a href="<?php echo base_url('panel/banco_pagos/layout_bascula/?tipo=ba&cuentaretiro='.$primera_cuenta); ?>" id="downloadBanamex" class="btn"><i class="icon-download-alt"></i> Banamex</a>
                  <a href="<?php echo base_url('panel/banco_pagos/layout_bascula/?tipo=in&cuentaretiro='.$primera_cuenta); ?>" id="downloadInterban" class="btn"><i class="icon-download-alt"></i> Interbancarios</a>
                  <a href="<?php echo base_url('panel/banco_pagos/aplica_pagos_bascula/?cuentaretiro='.$primera_cuenta); ?>" id="aplicarPagos" class="btn"
                    onclick="msb.confirm('Estas seguro de Aplicar los Pagos?', 'Facturas', this); return false;"><i class="icon-tag"></i> Aplicar pagos</a>
              <?php
              } ?>
                </div>
              </div>
              <table class="table table-striped table-bordered bootstrap-datatable">
                <thead>
                  <tr>
                    <th>Fecha</th>
                    <th>Serie/Folio</th>
                    <th>Monto</th>
                    <th>Cuentas</th>
                    <th style="width:20px;">Numerica</th>
                    <th>Alfanumerica</th>
                    <th>Descripcion</th>
                    <th>Persona moral?</th>
                  </tr>
                </thead>
                <tbody>
              <?php
              $total_pagar = 0;
              foreach($pagos as $keyp => $pago){
                $total_pagar_proveedor = 0;
                $html = '';

                foreach ($pago->pagos as $key => $value)
                {
                  $total_pagar += $value->monto;
                  $total_pagar_proveedor += $value->monto;
                  $html .= '<tr>
                            <td>'.$value->fecha.'</td>
                            <td>'.$value->folio.'
                              <input type="hidden" name="id_pago['.$keyp.'][]" value="'.$value->id_pago.'">
                            </td>
                            <td colspan="5"><input type="text" name="monto['.$keyp.'][]" value="'.$value->monto.'" class="monto vpositive" required readonly></td>
                            <td>
                              '.$this->usuarios_model->getLinkPrivSm('banco_pagos/eliminar_pago/', array(
                                  'params'   => "id_pago={$value->id_pago}",
                                  'btn_type' => 'btn-danger pull-right',
                                  'attrs' => array('onclick' => "msb.confirm('Estas seguro de Quitar el pago?', 'Facturas', this); return false;") )
                              ).'
                            </td>
                          </tr>';
                }
                echo '<tr>
                    <td colspan="2">'.$pago->nombre_fiscal.'</td>
                    <td>'.String::formatoNumero($total_pagar_proveedor, 2, '$', false).'</td>
                    <td><select name="cuenta_proveedor['.$keyp.'][]" class="tipo_cuenta span12" required>
                                <option value=""></option>';
                          foreach ($pago->cuentas_proveedor as $keyc => $cuentasp)
                          {
                            $select = $value->id_cuenta==$cuentasp->id_cuenta? 'selected': '';
                            echo '<option value="'.$cuentasp->id_cuenta.'-'.$cuentasp->is_banamex.'" '.$select.'>'.$cuentasp->alias.' *'.substr($cuentasp->cuenta, -4).'</option>';
                          }
                              echo '</select></td>
                    <td><input type="text" name="ref_numerica['.$keyp.'][]" value="'.$value->referencia.'" class="span12 ref_numerica" maxlength="7" required></td>
                    <td><input type="text" name="ref_alfanumerica['.$keyp.'][]" value="'.$value->ref_alfanumerica.'" class="ref_alfa span12" required></td>
                    <td><input type="text" name="descripcion['.$keyp.'][]" value="'.$value->descripcion.'" class="ref_descripcion span12" required></td>
                    <td><label></label>
                      <select name="es_moral['.$keyp.'][]" class="span12">
                        <option value="si" '.($pago->es_moral=='t'? 'selected': '').'>Si</option>
                        <option value="no" '.($pago->es_moral=='f'? 'selected': '').'>No</option>
                      </select>
                    </td>
                  </tr>'.$html;
              } ?>
                  <tr style="background-color:#ccc;font-weight: bold;">
                    <td style="text-align: right" colspan="2">Total:</td>
                    <td id="total_pagar" colspan="2"><?php echo String::formatoNumero($total_pagar, 2, '$', false); ?></td>
                    <td colspan="4"></td>
                  </tr>
                </tbody>
              </table>
            </form>

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
