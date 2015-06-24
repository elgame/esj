    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Cuentas por Cobrar
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Cuentas por Cobrar</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
          	<a href="<?php echo base_url('panel/cuentas_cobrar/?'.String::getVarsLink(array('msg'))); ?>" class="linksm">
							<i class="icon-chevron-left"></i> Atras</a> |
						<a href="<?php echo base_url('panel/cuentas_cobrar/cuenta_pdf/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
							<i class="icon-print"></i> Imprimir</a> |
						<a href="<?php echo base_url('panel/cuentas_cobrar/cuenta_xls/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
							<i class="icon-table"></i> Excel</a>

            <form action="<?php echo base_url('panel/cuentas_cobrar/cuenta'); ?>" method="GET" class="form-search">
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

                <label for="dcliente">Cliente</label>
                <input type="text" name="dcliente" class="input-large search-query" id="dcliente" value="<?php echo set_value_get('dcliente'); ?>" size="73">
                <input type="hidden" name="fid_cliente" id="fid_cliente" value="<?php echo set_value_get('fid_cliente'); ?>"> |

                <input type="hidden" name="id_cliente" id="id_cliente" value="<?php echo set_value_get('id_cliente'); ?>">

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <div class="row-fluid">
            	<fieldset class="span6" style="color: #555; font-size: .9em;">
								<legend style="margin: 0;">Datos del cliente</legend>
								<strong>Nombre:</strong> <?php echo $data['cliente']->nombre_fiscal; ?> <br>
								<strong>Dirección: </strong>
										<?php
											$info = $data['cliente']->calle!=''? $data['cliente']->calle: '';
											$info .= $data['cliente']->no_exterior!=''? ' #'.$data['cliente']->no_exterior: '';
											$info .= $data['cliente']->no_interior!=''? '-'.$data['cliente']->no_interior: '';
											$info .= $data['cliente']->colonia!=''? ', '.$data['cliente']->colonia: '';
											$info .= "\n".($data['cliente']->localidad!=''? $data['cliente']->localidad: '');
											$info .= $data['cliente']->municipio!=''? ', '.$data['cliente']->municipio: '';
											$info .= $data['cliente']->estado!=''? ', '.$data['cliente']->estado: '';
											echo $info;
										?> <br>
								<strong>Teléfono: </strong> <?php echo $data['cliente']->telefono; ?>
								<strong>Email: </strong> <?php echo $data['cliente']->email; ?>
							</fieldset>

							<div class="span6">
            	<?php echo $this->usuarios_model->getLinkPrivSm('cuentas_cobrar/agregar_abono/', array(
						    'params'   => "",
                'btn_type' => 'btn-success pull-right btn_abonos_masivo',
                'attrs' => array('style' => 'margin-top: 30px; display:none;', 'rel' => 'superbox-50x500') )
							); ?>
							</div>
            </div>

            <div id="sumaRowsSel" style="display:none;position:fixed;top:200px;right: 0px;width: 100px;background-color:#FFFF00;padding:3px 0px 3px 3px;font-size:14px;font-weight:bold;"></div>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Fecha</th>
									<th>Serie</th>
									<th>Folio</th>
									<th>Concepto</th>
									<th>Cargo</th>
									<th>Abono</th>
									<th>Saldo</th>
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
									<td>Saldo anterior a <?php echo $data['fecha1']; ?></td>
									<td style="text-align: right;"><?php echo String::formatoNumero(
											(isset($data['anterior'][0]->total)? $data['anterior'][0]->total: 0), 2, '$', false ); ?></td>
									<td style="text-align: right;"><?php echo String::formatoNumero(
											(isset($data['anterior'][0]->abonos)? $data['anterior'][0]->abonos: 0), 2, '$', false ); ?></td>
									<td style="text-align: right;"><?php echo String::formatoNumero(
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
						// 	$total_cargo += $data['anterior'][0]->total;
						// 	$total_abono += $data['anterior'][0]->abonos;
						// 	$total_saldo += $data['anterior'][0]->saldo;
						// }
						foreach($data['cuentas'] as $cuenta){
							$ver = true;

							if($ver){
								$total_cargo += $cuenta->cargo;
								$total_abono += $cuenta->abono;
								$total_saldo += $cuenta->saldo;
						?>
								<tr>
									<td><?php echo $cuenta->fecha; ?></td>
									<td><?php echo $cuenta->serie; ?></td>
									<td>
											<a href="<?php echo base_url('panel/cuentas_cobrar/detalle/').'?id='.$cuenta->id_factura.'&tipo='.$cuenta->tipo.
													'&'.String::getVarsLink(array('id', 'tipo', 'enviar', 'msg')); ?>" class="linksm lkzoom"><?php echo $cuenta->folio; ?></a>
									<td>
										<a href="<?php echo base_url('panel/cuentas_cobrar/detalle/').'?id='.$cuenta->id_factura.'&tipo='.$cuenta->tipo.
													'&'.String::getVarsLink(array('id', 'tipo', 'enviar', 'msg')); ?>" class="linksm lkzoom"><?php echo $cuenta->concepto ?></a>
									</td>
									<td style="text-align: right;"><?php echo String::formatoNumero($cuenta->cargo, 2, '$', false); ?></td>
									<td style="text-align: right;"><?php echo String::formatoNumero($cuenta->abono, 2, '$', false); ?></td>
									<td style="text-align: right;" class="sel_abonom" data-id="<?php echo $cuenta->id_factura; ?>" data-tipo="<?php echo $cuenta->tipo ?>"><?php echo String::formatoNumero($cuenta->saldo, 2, '$', false); ?></td>
									<td><?php echo $cuenta->estado; ?></td>
									<td><?php echo $cuenta->fecha_vencimiento; ?></td>
									<td><?php echo $cuenta->dias_transc; ?></td>
								</tr>
						<?php }
						} ?>
								<tr style="background-color:#ccc;font-weight: bold;">
									<td colspan="4" class="a-r">Totales:</td>
									<td style="text-align: right;"><?php echo String::formatoNumero($total_cargo, 2, '$', false); ?></td>
									<td style="text-align: right;"><?php echo String::formatoNumero($total_abono, 2, '$', false); ?></td>
									<td style="text-align: right;"><?php echo String::formatoNumero($total_cargo-$total_abono, 2, '$', false); ?></td>
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
