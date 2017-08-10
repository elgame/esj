    <div id="content" class="span10">
      <!-- content starts -->

      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/banco'); ?>">Saldos</a> <span class="divider">/</span>
          </li>
          <li>
            Cuenta
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-archive"></i> Cuenta < <?php echo $data['cuenta']['info']->banco.' - '.$data['cuenta']['info']->alias; ?> ></h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
          	<a href="<?php echo base_url('panel/banco/?'.String::getVarsLink(array('msg', 'id_cuenta', 'fstatus'))); ?>" class="linksm">
              <i class="icon-chevron-left"></i> Atras</a> |
            <a href="<?php echo base_url('panel/banco/cuenta_pdf/?'.String::getVarsLink(array('msg', 'fstatus'))); ?>" class="linksm" target="_blank">
              <i class="icon-print"></i> Imprimir</a> |
            <a href="<?php echo base_url('panel/banco/cuenta_xls/?'.String::getVarsLink(array('msg', 'fstatus'))); ?>" class="linksm" target="_blank">
              <i class="icon-table"></i> Excel</a>
            <a href="" data-href="<?php echo base_url('panel/banco/conciliacion/?'.String::getVarsLink(array('msg', 'fstatus'))); ?>" id="verConciliacion" class="linksm" target="_blank">
              <i class="icon-archive"></i> Conciliacion</a>
        <?php if($data['cuenta']['info']->banco == 'Banamex'){ ?>
               |
            <a href="<?php echo base_url('panel/banco/cuenta_banamex/?'.String::getVarsLink(array('msg', 'fstatus'))); ?>" class="linksm" target="_blank">
              <i class="icon-file-text"></i> Banamex</a>
        <?php } ?>

            <a href="<?php echo base_url('panel/banco/mover_movimiento/?'.String::getVarsLink(array('msg'))) ?>" class="btn btn-info" id="cambia-fecha-movi"
              onclick="msb.confirm('Estas seguro de mover los movimientos al siguiente mes?', 'Cuentas', this); return false;">Trasladar cuentas</a>

            <form action="<?php echo base_url('panel/banco/cuenta/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
              	<input type="hidden" name="id_cuenta" value="<?php echo set_value_get('id_cuenta'); ?>">
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

                | <label for="dcliente"> Cliente / Proveedor</label>
                <input type="text" name="dcliente" class="input-large search-query" id="dcliente" value="<?php echo set_value_get('dcliente', ''); ?>" size="73">
                <input type="hidden" name="did_cliente" id="did_cliente" value="<?php echo set_value_get('did_cliente', ''); ?>">

                <br>

                | <label for="toperacion">Retiros:</label>
                <select name="toperacion" id="toperacion" class="input-large search-query">
                  <option value="" <?php echo set_select_get('toperacion', ''); ?>>Todas</option>
                  <option value="in" <?php echo set_select_get('toperacion', 'in'); ?>>Interbancarios</option>
                  <option value="ba" <?php echo set_select_get('toperacion', 'ba'); ?>>Banamex</option>
                </select>

                | <label for="tmetodo_pago">Metodo de Pago:</label>
                <select name="tmetodo_pago" id="tmetodo_pago" class="input-large search-query">
                  <option value="" <?php echo set_select_get('tmetodo_pago', ''); ?>>Todas</option>
                  <option value="transferencia" <?php echo set_select_get('tmetodo_pago', 'transferencia'); ?>>Transferencia</option>
                  <option value="cheque" <?php echo set_select_get('tmetodo_pago', 'cheque'); ?>>Cheque</option>
                </select> |

                <label for="tipomovimiento">Tipo movimiento:</label>
                <select name="tipomovimiento" id="tipomovimiento" class="input-large search-query">
                  <option value="" <?php echo set_select_get('tipomovimiento', ''); ?>>Todas</option>
                  <option value="t" <?php echo set_select_get('tipomovimiento', 't'); ?>>Deposito</option>
                  <option value="f" <?php echo set_select_get('tipomovimiento', 'f'); ?>>Retiro</option>
                </select>

                <button type="submit" class="btn">Enviar</button>
              </div>
            </form>

            <div style="overflow-x: auto;">

              <table class="table table-striped table-bordered bootstrap-datatable" style="">
                <thead>
                  <tr>
          					<th></th>
          					<th>Fecha</th>
          					<th>Ref</th>
          					<th>Cliente / Proveedor</th>
          					<th>Concepto</th>
          					<th>Metodo de pago</th>
          					<th>Deposito</th>
                    <th>Retiro</th>
          					<th>Saldo</th>
          					<th>Estado</th>
          					<th></th>
                  </tr>
                </thead>
                <tbody>
              <?php
              foreach($data['movimientos'] as $movimiento)
              {
              	$status = array();
              	if($movimiento->entransito!='')
          			$status = explode('|', $movimiento->entransito);
              ?>
              	<tr>
              		<td><?php
                    $opc_html = '';
              			// if(count($status) > 0 && $movimiento->metodo_pago == 'cheque')
                    if(count($status) > 0)
                    {
              				$opc_html .= '<li><input type="checkbox" class="transit_chekrs" id="transit'.$movimiento->id_movimiento.'"
              					value="'.'id_movimiento='.$movimiento->id_movimiento.'&mstatus='.$status[0].'&'.String::getVarsLink(array('id_movimiento', 'mstatus', 'fstatus', 'msg')).'"
              					data-status="'.$status[0].'" '.($status[0]=='Trans'? 'checked' : '').' data-id="'.$movimiento->id_movimiento.'"> Transito</li>';
                      $opc_html .= '<li><input type="checkbox" class="sbc_chekrs" id="sbc'.$movimiento->id_movimiento.'"
                        value="'.'id_movimiento='.$movimiento->id_movimiento.'&mstatus='.$status[0].'&'.String::getVarsLink(array('id_movimiento', 'mstatus', 'fstatus', 'msg')).'"
                        data-status="'.$movimiento->salvo_buen_cobro.'" '.($movimiento->salvo_buen_cobro=='t'? 'checked' : '').' data-id="'.$movimiento->id_movimiento.'"> Salvo buen cobro</li>';
              		?>
                    <div class="btn-group">
                      <a class="btn dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
                      <ul class="dropdown-menu">
                        <?php echo $opc_html; ?>
                        <li><a href="#" role="button" class="modalimprimir"
                          data-idm="<?php echo $movimiento->id_movimiento ?>"
                          data-idc=""><i class="icon-print"></i> Sello digital</a></li>
                      </ul>
                    </div>
                <?php } ?>
                  </td>
              		<td><div style="width: 80px;"><?php echo $movimiento->fecha; ?></div></td>
              		<td><?php echo $movimiento->numero_ref; ?></td>
              		<td><div style="width: 200px;"><?php echo $movimiento->cli_pro; ?></div></td>
              		<td><div style="width: 300px; word-wrap: break-word;"><?php echo $movimiento->concepto; ?></div></td>
              		<td><?php echo $movimiento->metodo_pago; ?></td>
              		<td style="text-align: right;"><?php echo String::formatoNumero($movimiento->deposito, 2, '$', false); ?></td>
                  <td style="text-align: right;"><?php echo String::formatoNumero($movimiento->retiro, 2, '$', false); ?></td>
              		<td style="text-align: right;"><?php echo String::formatoNumero($movimiento->saldo, 2, '$', false); ?></td>
              		<td><?php
              			if(count($status)>0)
              				echo '<span class="label label-'.($status[0]=='Trans'? 'info' : 'success').'">'.$status[0].'</span>'
          						.'<br><span class="label label-'.($status[1]=='Cancelado'? 'important' : 'success').'">'.$status[1].'</span>';
              		?></td>
              		<td><?php
              			if(count($status)>0)
              			{
              				if($movimiento->status == 't')
  		            			echo $this->usuarios_model->getLinkPrivSm('banco/cancelar_movimiento/', array(
      									'params'   => 'id_movimiento='.$movimiento->id_movimiento.'&'.String::getVarsLink(array('id_movimiento', 'fstatus', 'msg')),
      									'btn_type' => 'btn-danger',
      									'text_link'=> 'hidden',
      									'attrs' => array('onclick' => "msb.confirm('Estas seguro de Cancelar la operación?<br>Nota: Se eliminara tambien en cobranza o cuentas por pagar si esta ligada la operacion.<br><strong>Este cambio no se puede revertir</strong>', 'cuentas', this); return false;"))
      								);
      							echo $this->usuarios_model->getLinkPrivSm('banco/eliminar_movimiento/', array(
      								'params'   => 'id_movimiento='.$movimiento->id_movimiento.'&'.String::getVarsLink(array('id_movimiento', 'fstatus', 'msg')),
      								'btn_type' => 'btn-danger',
      								'text_link'=> 'hidden',
      								'attrs' => array('onclick' => "msb.confirm('Estas seguro de Eliminar la operación?<br>Nota: Se eliminara tambien en cobranza o cuentas por pagar si esta ligada la operacion.<br><strong>Este cambio no se puede revertir</strong>', 'cuentas', this); return false;"))
      							);
                    echo $this->usuarios_model->getLinkPrivSm('banco/modificar_movimiento/', array(
                      'params'   => 'id_movimiento='.$movimiento->id_movimiento.'&'.String::getVarsLink(array('id_movimiento', 'fstatus', 'msg')),
                      'btn_type' => 'btn-info',
                      'text_link'=> 'hidden',
                      'attrs' => array('rel' => "superbox-50x500"))
                    );
      						}
              		?></td>
              	</tr>
              <?php }?>
                  <tr style="background-color:#ccc;font-weight: bold;">
                    <td style="text-align: right" colspan="6">Total:</td>
                    <td style="text-align: right"><?php echo String::formatoNumero($data['total_deposito'], 2, '$', false); ?></td>
                    <td style="text-align: right"><?php echo String::formatoNumero($data['total_retiro'], 2, '$', false); ?></td>
                    <td id="total_saldo" style="text-align: right"><?php echo String::formatoNumero($data['total_saldos'], 2, '$', false); ?></td>
                    <td></td>
                    <td></td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
        </div><!--/span-->

      </div><!--/row-->

      	<div id="saldobanco" style="position: fixed;top:150px;right: 0;width: 240px;font-size: .9em;background-color: #cddD9F;padding: 3px 0 3px 3px;">
			<table>
				<tr>
					<td>Banco (disponible)</td>
					<td><input type="text" id="sb_banco" class="span11"></td>
				</tr>
				<tr>
					<td>Empresa (REAL)</td>
					<td id="sb_empresar"></td>
				</tr>
				<tr>
					<td>Dif</td>
					<td id="sb_dif1"></td>
				</tr>
				<tr>
					<td>Cheq no cob</td>
					<td id="sb_cheque_ncob"><?php echo String::formatoNumero($data['cheques_no_cobrados']); ?></td>
				</tr>
				<tr>
					<td>Dif</td>
					<td id="sb_dif2"></td>
				</tr>
			</table>
		</div>

    <!-- Modal -->
    <div id="modal-imprimir" class="modal modal-w50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel">Imprimir (Sellos)</h3>
      </div>
      <div class="modal-body">
        <div class="row-fluid">
          <select name="lista_impresoras" id="lista_impresoras">
          <?php foreach ($impresoras as $key => $value) { ?>
            <option value="<?php echo base64_encode($value->ruta) ?>"><?php echo $value->impresora ?></option>
          <?php } ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
        <button class="btn btn-primary" id="BtnImprimir" data-idm="" data-idc="">Imprimir</button>
      </div>
    </div><!--/modal impresoras -->

    <!-- Modal -->
    <div id="modal-transito" class="modal modal-w50 hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel">Transito</h3>
      </div>
      <div class="modal-body">
        <div class="row-fluid">
          <input type="date" name="" value="">
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn" id="btnTransitoCancelar" data-dismiss="modal" aria-hidden="true">Cancelar</button>
        <button class="btn btn-primary" id="btnTransitoContinuar" data-idm="" data-idc="">Continuar</button>
      </div>
    </div><!--/modal transito -->


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
