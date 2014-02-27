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
          	<a href="<?php echo base_url('panel/cuentas_cobrar/cuenta/?'.String::getVarsLink(array('msg'))); ?>" class="linksm">
								<i class="icon-chevron-left"></i> Atras</a>
            <div class="row-fluid">
            	<fieldset class="span6" style="color: #555; font-size: .9em; border-bottom: none;">
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

							<fieldset class="span6" style="color: #555; font-size: .9em;border-bottom: none;">
								<legend style="margin: 0;">Datos de<?php echo ($_GET['tipo']=='f')?' la factura':' la venta' ?></legend>
								<strong>Fecha:</strong> <?php echo $data['cobro'][0]->fecha; ?> <br>
								<strong>Serie:</strong> <?php echo $data['cobro'][0]->serie; ?> <br>
								<strong>Folio:</strong> <?php echo $data['cobro'][0]->folio; ?> <br>
								<strong>Condicion pago: </strong> <?php echo $data['cobro'][0]->condicion_pago=='co'? 'Contado': 'Credito'; ?> 
								<strong>Plazo credito: </strong> <?php echo $data['cobro'][0]->condicion_pago=='co'? 0: $data['cobro'][0]->plazo_credito; ?> <br>
								<strong>Estado:</strong> <span id="inf_fact_estado">
									<?php echo $data['cobro'][0]->status=='pa'? 'Pagada': 'Pendiente'; ?></span>
							</fieldset>
            </div>

						<?php echo $this->usuarios_model->getLinkPrivSm('cuentas_cobrar/agregar_abono/', array(
						    'params'   => "id={$_GET['id']}&tipo={$_GET['tipo']}",
                'btn_type' => 'btn-success pull-right',
                'attrs' => array('style' => 'margin-bottom: 10px;', 'rel' => 'superbox-50x500') )
						); ?>
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Fecha</th>
									<th>Concepto</th>
									<th>Abono</th>
									<th>Saldo</th>
									<th></th>
                </tr>
              </thead>
              <tbody>
            		<?php
								$total_abono = 0;
								$total_saldo = $data['cobro'][0]->total;
								foreach($data['abonos'] as $cuenta){
									$total_abono += $cuenta->abono;
									$total_saldo -= $cuenta->abono;
								?>
								<tr>
									<td><?php echo $cuenta->fecha; ?></td>
									<td><?php echo $cuenta->concepto; ?></td>
									<td style="text-align: right;"><?php echo String::formatoNumero($cuenta->abono, 2, '$', false); ?></td>
									<td style="text-align: right;"><?php echo String::formatoNumero($total_saldo, 2, '$', false); ?></td>
									<td class="">
									<?php
									if ($_GET['tipo'] == 'v')
									{
										echo $this->usuarios_model->getLinkPrivSm('cuentas_cobrar/eliminar_abono/', array(
                        'params'   => "ida={$cuenta->id_abono}&".String::getVarsLink(array('ida','msg','nc')),
                        'btn_type' => 'btn-danger',
                        'attrs'    => array('onclick' => "msb.confirm('Estas seguro de Quitar el abono?', 'Facturas', this); return false;"))
                    );
									}
									elseif ($_GET['tipo'] == 'f')
									{
										echo $this->usuarios_model->getLinkPrivSm('cuentas_cobrar/eliminar_abono/', array(
                        'params'   => "ida={$cuenta->id_abono}".($cuenta->tipo=='nc'? '&nc=si': '')."&".String::getVarsLink(array('ida','msg','nc')),
                        'btn_type' => 'btn-danger',
                        'attrs'    => array('onclick' => "msb.confirm('Estas seguro de Quitar el abono?', 'Facturas', this); return false;"))
                    );
									}
									?>
									</td>
								</tr>
								<?php
								} ?>
								<tr style="background-color:#ccc;font-weight: bold;">
									<td colspan="2" class="a-r">Totales:</td>
									<td style="text-align: right;"><?php echo String::formatoNumero($total_abono, 2, '$', false); ?></td>
									<td style="text-align: right;" id="dtalle_total_saldo"><?php echo String::formatoNumero($total_saldo, 2, '$', false); ?></td>
									<td></td>
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
