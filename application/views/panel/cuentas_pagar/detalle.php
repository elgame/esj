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
          	<a href="<?php echo base_url('panel/cuentas_pagar/cuenta/?'.String::getVarsLink(array('msg'))); ?>" class="linksm">
								<i class="icon-chevron-left"></i> Atras</a>
            <div class="row-fluid">
            	<fieldset class="span6" style="color: #555; font-size: .9em; border-bottom: none;">
								<legend style="margin: 0;">Datos del proveedor</legend>
								<strong>Nombre:</strong> <?php echo $data['proveedor']->nombre_fiscal; ?> <br>
								<strong>Dirección: </strong>
										<?php
											$info = $data['proveedor']->calle!=''? $data['proveedor']->calle: '';
											$info .= $data['proveedor']->no_exterior!=''? ' #'.$data['proveedor']->no_exterior: '';
											$info .= $data['proveedor']->no_interior!=''? '-'.$data['proveedor']->no_interior: '';
											$info .= $data['proveedor']->colonia!=''? ', '.$data['proveedor']->colonia: '';
											$info .= "\n".($data['proveedor']->localidad!=''? $data['proveedor']->localidad: '');
											$info .= $data['proveedor']->municipio!=''? ', '.$data['proveedor']->municipio: '';
											$info .= $data['proveedor']->estado!=''? ', '.$data['proveedor']->estado: '';
											echo $info;
										?> <br>
								<strong>Teléfono: </strong> <?php echo $data['proveedor']->telefono; ?>
								<strong>Email: </strong> <?php echo $data['proveedor']->email; ?>
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

						<?php echo $this->usuarios_model->getLinkPrivSm('cuentas_pagar/agregar_abono/', array(
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
									<td><?php echo String::formatoNumero($cuenta->abono); ?></td>
									<td><?php echo String::formatoNumero($total_saldo); ?></td>
									<td class="">
									<?php
									if ($_GET['tipo'] == 'v')
									{
										echo $this->usuarios_model->getLinkPrivSm('cuentas_pagar/eliminar_abono/', array(
					                        'params'   => "ida={$cuenta->id_abono}&".String::getVarsLink(array('ida','msg','nc')),
					                        'btn_type' => 'btn-danger',
					                        'attrs'    => array('onclick' => "msb.confirm('Estas seguro de Quitar el abono?', 'Facturas', this); return false;"))
					                    );
									}
									elseif ($_GET['tipo'] == 'f')
									{
										if($cuenta->tipo != 'nc')
										{
											echo $this->usuarios_model->getLinkPrivSm('cuentas_pagar/eliminar_abono/', array(
						                        'params'   => "ida={$cuenta->id_abono}".($cuenta->tipo=='nc'? '&nc=si': '')."&".String::getVarsLink(array('ida','msg','nc')),
						                        'btn_type' => 'btn-danger',
						                        'attrs'    => array('onclick' => "msb.confirm('Estas seguro de Quitar el abono?', 'Facturas', this); return false;"))
						                    );
										}
									}
									?>
									</td>
								</tr>
								<?php
								} ?>
								<tr style="background-color:#ccc;font-weight: bold;">
									<td colspan="2" class="a-r">Totales:</td>
									<td><?php echo String::formatoNumero($total_abono); ?></td>
									<td id="dtalle_total_saldo"><?php echo String::formatoNumero($total_saldo); ?></td>
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
