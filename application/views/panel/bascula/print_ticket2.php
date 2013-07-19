<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title></title>
	<style type="text/css" media="print,screen">
		@import "<?php echo base_url('application/css/bootstrap/normalize.css'); ?>";
		@import "<?php echo base_url('application/css/bootstrap/boilerplate.css'); ?>";

		.row{
			width: 100%;
			border: 1px #000 solid;
		}
		.pleft{
			float: left;
		}
		.pright{
			float: right;
		}

		body{
			font-size: 9pt;
			margin-left: 3pt;
		}
		h2{
			font-size: 10pt;
			text-align: center;
		}

		table, .tblinfo{
			width: 100%;
		}
		.td60{ width: 60%;}
		.td50{ width: 50%;}
		.td40{ width: 40%;}
		.td30{ width: 30%;}
		.td20{ width: 20%;}
		.td10{ width: 10%;}

		.br_bottom{ border-bottom: 1px #000 solid; }
		.br_top{ border-top: 1px #000 solid; }

		.font7{ font-size: 7pt;}
		.font7_5{ font-size: 7.5pt;}
		.font8{ font-size: 8pt;}
		.font9{ font-size: 9pt;}

		.strong{font-weight: bold;}

		.marg_top5{padding-top: 5pt;}
		.marg_top20{padding-top: 20pt;}

		.txt_center{text-align: center;}
		td{
			/*border: 1px red solid;*/
		}
	</style>
	<script type="text/javascript">
		window.onload=function(){
			window.print();
			window.close();
			// window.onfocus=function(){window.close();}
		};
		// window.print();
	</script>
</head>
<body>
	<?php 
		// var_dump($data['info']);

		$pdf = new mypdf_ticket();
	?>
	<h2><?php echo $pdf->titulo1; ?></h2>
	<table class="tblinfo">
		<tbody>
			<tr class="tr1">
				<td>
					<table class="br_top">
						<tr>
							<td class="td50 font8">NO. BOLETA: <?php echo $data['info'][0]->folio; ?></td>
							<td class="td50">FECHA: <?php echo substr($data['info'][0]->fecha_bruto, 0, 10); ?></td>
						</tr>
					</table>
				</td>
			</tr>

			<tr>
				<td>
					<table>
						<tr>
							<td class="td30 font8">BRUTO :</td>
							<td class="td40"><?php echo String::formatoNumero($data['info'][0]->kilos_bruto, 2, ''); ?></td>
							<td class="td30"><?php echo substr($data['info'][0]->fecha_bruto, -11, -3); ?></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table>
						<tr>
							<td class="td30 font8">TARA :</td>
							<td class="td40"><?php echo String::formatoNumero($data['info'][0]->kilos_tara, 2, ''); ?></td>
							<td class="td30"><?php echo substr($data['info'][0]->fecha_tara, -11, -3); ?></td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>
				<td>
					<table class="br_bottom">
						<tr>
							<td class="td30">NETO :</td>
							<td class="td40"><?php echo String::formatoNumero($data['info'][0]->kilos_neto, 2, ''); ?></td>
							<td class="td30"></td>
						</tr>
					</table>
				</td>
			</tr>

			<tr>
				<td>
					<table class="font8">
						<tr class="br_bottom">
							<td>CJS</td>
							<td>LIMON</td>
							<td>KILOS</td>
							<td>PROM</td>
							<td>PCIO</td>
							<td>IMPORTE</td>
						</tr>
				<?php
				if(is_array($data['cajas'])){
            foreach ($data['cajas'] as $prod){
        ?>
        		<tr>
							<td><?php echo $prod->cajas ?></td>
							<td><?php echo $prod->calidad ?></td>
							<td><?php echo String::formatoNumero($prod->kilos, 2, '') ?></td>
							<td><?php echo $prod->promedio ?></td>
							<td><?php echo String::formatoNumero($prod->precio, 2, '') ?></td>
							<td><?php echo String::formatoNumero($prod->importe, 2, '') ?></td>
						</tr>
        <?php
            }
        }
				?>
						<tr class="br_top strong">
							<td colspan="4">IMPORTE TOTAL</td>
							<td colspan="2"><?php echo String::formatoNumero($data['info'][0]->importe, 2, '') ?></td>
						</tr>
					</table>
				</td>
			</tr>

<?php
if ($data['info'][0]->tipo === 'en')
{
  $cuentaCpi = $data['info'][0]->cpi_proveedor;
  $nombreCpi = $data['info'][0]->proveedor;
}
else
{
  $cuentaCpi = $data['info'][0]->cpi_cliente;
  $nombreCpi = $data['info'][0]->cliente;
}
?>
			<tr>
				<td><?php echo 'CUENTA: ' . strtoupper($cuentaCpi) ?></td>
			</tr>
			<tr>
				<td><?php echo strtoupper($nombreCpi) ?></td>
			</tr>
			<tr>
				<td><?php echo 'CHOFER: ' . strtoupper($data['info'][0]->chofer) ?></td>
			</tr>
			<tr>
				<td><?php echo 'CAMION: ' . strtoupper($data['info'][0]->camion) ?></td>
			</tr>
			<tr>
				<td><?php echo 'PLACAS: ' . strtoupper($data['info'][0]->camion_placas) ?></td>
			</tr>

			<tr>
				<td class="marg_top ">EXPEDIDO EL: <?php echo substr($data['info'][0]->fecha_tara, 0, 19) ?></td>
			</tr>

			<tr>
				<td class="marg_top20 br_bottom"></td>
			</tr>
			<tr>
				<td class="txt_center">FIRMA</td>
			</tr>
		</tbody>
	</table>

</body>
</html>