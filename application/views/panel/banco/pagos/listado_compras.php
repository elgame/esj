    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Pagos de compras
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Pagos de compras</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <?php $id_empresa = set_value_get('did_empresa', (isset($empresa->id_empresa)? $empresa->id_empresa: '')); ?>
          <div class="box-content">
            <form action="<?php echo base_url('panel/banco_pagos/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa', (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: '') ); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo $id_empresa; ?>">

                <button type="submit" class="btn">Cargar</button>
              </div>
            </form>

            <form action="<?php echo base_url('panel/banco_pagos/'); ?>" method="post">
              <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo $id_empresa; ?>">
              <div class="row-fluid">
                <div class="span12">
                  <button type="submit" class="btn btn-success pull-right">Guardar</button>
              <?php if ($rows_completos)
              { ?>
                  <select name="cuenta_retiro" id="cuenta_retiro" required>
                  <?php
                  $primera_cuenta = null;
                  foreach ($data['cuentas'] as $keyc => $cuentasp)
                  {
                    if($cuentasp->is_pago_masivo == 't'){ //banamex
                      if($primera_cuenta == null)
                        $primera_cuenta = $cuentasp->id_cuenta;
                  ?>
                    <option value="<?php echo $cuentasp->id_cuenta; ?>"><?php echo $cuentasp->alias.' * '.MyString::formatoNumero($cuentasp->saldo); ?></option>
                  <?php
                    }
                  } ?>
                  </select>
                  <a href="<?php echo base_url('panel/banco_pagos/layout/?layout=banamex&tipo=ba&cuentaretiro='.$primera_cuenta.'&ide='.$id_empresa); ?>" id="downloadBanamex" class="btn"><i class="icon-download-alt"></i> Banamex</a>
                  <a href="<?php echo base_url('panel/banco_pagos/layout/?layout=banamex&tipo=in&cuentaretiro='.$primera_cuenta.'&ide='.$id_empresa); ?>" id="downloadInterban" class="btn"><i class="icon-download-alt"></i> Banamex Interb</a>
                  <a href="<?php echo base_url('panel/banco_pagos/layout/?layout=bajio&tipo=in&cuentaretiro='.$primera_cuenta.'&ide='.$id_empresa); ?>" id="downloadBajio" class="btn"><i class="icon-download-alt"></i> Bajio</a>
                  <a href="<?php echo base_url('panel/banco_pagos/aplica_pagos/?cuentaretiro='.$primera_cuenta.'&ide='.$id_empresa); ?>" id="aplicarPagos" class="btn"
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
                $total_pagar_proveedor = $total_pagar_proveedor_new = 0;
                $html = '';
                $facturas_desc = array();

                foreach ($pago->pagos as $key => $value)
                {
                  $total_pagar += $value->monto;
                  $total_pagar_proveedor += $value->monto;
                  $total_pagar_proveedor_new += $value->new_total;
                  $facturas_desc[] = $value->serie.$value->folio;
                  $html .= '<tr>
                            <td>'.$value->fecha.'</td>
                            <td>'.$value->serie.$value->folio.'
                              <input type="hidden" name="id_pago['.$keyp.'][]" value="'.$value->id_pago.'">
                            </td>
                            <td colspan="5"><input type="text" name="monto['.$keyp.'][]" value="'.$value->monto.'" class="monto vpositive" required readonly></td>
                            <td>
                              '.$this->usuarios_model->getLinkPrivSm('banco_pagos/eliminar_pago/', array(
                                  'params'   => "id_pago={$value->id_pago}",
                                  'btn_type' => 'btn-danger pull-right', )
                              ).'
                            </td>
                          </tr>';
                }
                echo '<tr>
                    <td style="font-weight:bold" colspan="2">'.$pago->nombre_fiscal.'</td>
                    <td style="font-weight:bold">'.MyString::formatoNumero($total_pagar_proveedor, 2, '$', false).' <br>
                    '.MyString::formatoNumero($total_pagar_proveedor_new, 2, '$', false).'</td>
                    <td><select name="cuenta_proveedor['.$keyp.'][]" class="tipo_cuenta span12">
                                <option value=""></option>';
                          foreach ($pago->cuentas_proveedor as $keyc => $cuentasp)
                          {
                            $select = (($value->id_cuenta==$cuentasp->id_cuenta || ($keyc==0 && $value->modificado_banco=='f'))? 'selected': '');
                            echo '<option value="'.$cuentasp->id_cuenta.'-'.$cuentasp->is_banamex.'" '.$select.'
                            data-ref="'.($cuentasp->referencia!=''? $cuentasp->referencia: '1').'" data-descrip="'.implode(' -', $facturas_desc).'">'.$cuentasp->banco.' - '.$cuentasp->alias.'</option>';
                          }
                              echo '</select></td>
                    <td><input type="text" name="ref_numerica['.$keyp.'][]" value="'.$value->referencia.'" class="span12 ref_numerica" maxlength="7"></td>
                    <td><input type="text" name="ref_alfanumerica['.$keyp.'][]" value="'.$value->ref_alfanumerica.'" class="ref_alfa span12"></td>
                    <td><input type="text" name="descripcion['.$keyp.'][]" value="'.$value->descripcion.'" class="ref_descripcion span12"></td>
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
                    <td id="total_pagar" colspan="2"><?php echo MyString::formatoNumero($total_pagar, 2, '$', false); ?></td>
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
