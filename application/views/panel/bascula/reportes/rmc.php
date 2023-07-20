<!DOCTYPE HTML>
<html lang="en-US">
<head>
	<meta charset="UTF-8">
	<title></title>
	<style type="text/css" media="print,screen">
		@import "<?php echo base_url('application/css/bootstrap/normalize.css'); ?>";
/*		@import "<?php echo base_url('application/css/bootstrap/boilerplate.css'); ?>";*/

		.row{
			width: 100%;
			border: 1px #000 solid;
		}
    .border {
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
			width: 100%;
		}
		h2, h3, h4{
			font-size: 10pt;
			text-align: center;
			margin: 0;
		}
		h3{
			font-size: 9pt;
		}
		h4{
			font-size: 9pt;
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
		.txt_left{text-align: left;}
		.txt_right{text-align: right;}
		td{
			/*border: 1px red solid;*/
		}
	</style>
</head>
<body>
	<?php
		$rmc       = $data['movimientos'];
		$area      = $data['area'];
		$proveedor = $data['proveedor'];
		$fechaini  = new DateTime($_GET['fechaini']);
		$fechaend  = new DateTime($_GET['fechaend']);

		$tipo = "ENTRADAS/SALIDAS";
    if ($this->input->get('ftipop') != '')
      if ($this->input->get('ftipop') === '1')
        $tipo = "ENTRADAS";
      else
        $tipo = "SALIDAS";
		$titulo2 = "MOVIMIENTOS DE CUENTA - {$tipo} <".$area['info']->nombre."> DEL DIA " . $fechaini->format('d/m/Y') . " AL " . $fechaend->format('d/m/Y');

	$titulo3 = '';
    if (isset($proveedor['info']->nombre_fiscal))
    	$titulo3 = strtoupper($proveedor['info']->nombre_fiscal) . " (CTA: " .$proveedor['info']->cuenta_cpi . ") <br> FECHA/HORA DEL REPORTE: " . date('d/m/Y H:i:s');

	$pdf = new mypdf_ticket();
	?>
	<h2><?php echo (isset($empresa)? $empresa->nombre_fiscal: $pdf->titulo1); ?></h2>
	<h3><?php echo $titulo2; ?></h3>
	<h4><?php echo $titulo3; ?></h4>
	<table class="tblinfo">
		<tbody>

			<tr>
				<td>
					<table class="font8">
						<tr class="br_bottom">
							<!-- <td></td> -->
							<td>TIPO</td>
              <td>BOLETA</td>
							<td>REM/FAC</td>
							<td>FECHA</td>
							<td>CALIDAD</td>
							<td class="txt_right">CAJS</td>
							<td class="txt_right">PROM</td>
							<td class="txt_right">KILOS</td>
							<td class="txt_right">PRECIO</td>
							<td class="txt_right">IMPORTE</td>
							<td class="txt_right">RET ISR</td>
              <td class="txt_right">TOTAL</td>
							<td>TIPO PAGO</td>
							<td>CONCEPTO</td>
              <td>RANCHO</td>
							<td>TABLA/LOTE</td>
							<!-- <td>BONIF</td> -->
						</tr>
				<?php
				$lastFolio = 0;
    		$total_bonificaciones = 0;
				if(is_array($rmc)){
            foreach($rmc as $key => $caja)
            {
        ?>
        		<tr>
							<!-- <td><?php echo ($caja->id_bascula != $lastFolio) ? ($caja->status === 'p' ||  $caja->status === 'b' ? strtoupper($caja->status)  : '') : ''; ?></td> -->
							<td><?php echo ($caja->id_bascula != $lastFolio) ? $caja->tipo : ''; ?></td>
              <td><?php echo ($caja->id_bascula != $lastFolio) ? $caja->folio : ''; ?></td>
							<td><?php echo ($caja->id_bascula != $lastFolio) ? "{$caja->folio_rem}/{$caja->folio_fact}" : ''; ?></td>
							<td><?php echo ($caja->id_bascula != $lastFolio) ? $caja->fecha : ''; ?></td>
							<td><?php echo $isXml? $caja->calidad: substr($caja->calidad, 0, 9); ?></td>
							<td class="txt_right"><?php echo $caja->cajas; ?></td>
							<td class="txt_right"><?php echo MyString::formatoNumero($caja->promedio, 2, '', false); ?></td>
							<td class="txt_right"><?php echo MyString::formatoNumero($caja->kilos, 2, ''); ?></td>
							<td class="txt_right"><?php echo MyString::formatoNumero($caja->precio, 2, '', false); ?></td>
							<td class="txt_right"><?php echo MyString::formatoNumero($caja->importe, 2, '', false); ?></td>
              <td class="txt_right"><?php echo ($caja->id_bascula != $lastFolio) ? MyString::formatoNumero($caja->ret_isr, 2, '', false) : ''; ?></td>
              <td class="txt_right"><?php echo ($caja->id_bascula != $lastFolio) ? MyString::formatoNumero($caja->importe_todas, 2, '', false) : ''; ?></td>
							<td><?php echo ($caja->id_bascula != $lastFolio) ? strtoupper($caja->tipo_pago) : ''; ?></td>
							<td><?php echo ($caja->id_bascula != $lastFolio) ? $caja->concepto: ''; ?></td>
              <td><?php echo ($caja->id_bascula != $lastFolio) ? $caja->rancho: ''; ?></td>
							<td><?php echo ($caja->id_bascula != $lastFolio) ? $caja->tabla: ''; ?></td>
							<!-- <td><?php echo ($caja->id_bascula != $lastFolio ? (is_numeric($caja->id_bonificacion)? 'Si': ''): ''); ?></td> -->
						</tr>
        <?php
							$lastFolio = $caja->id_bascula;
							if(is_numeric($caja->id_bonificacion))
								$total_bonificaciones += $caja->importe;
            }
        }
				?>
						<tr class="br_top strong">
							<!-- <td></td> -->
							<td></td>
							<td></td>
              <td></td>
							<td></td>
							<td></td>
							<td class="txt_right"><?php echo $data['totales']['cajas']; ?></td>
							<td class="txt_right"><?php echo $data['totales']['cajas'] != 0 ? MyString::formatoNumero(floatval($data['totales']['kilos'])/floatval($data['totales']['cajas']), 2, '', false) : 0; ?></td>
							<td class="txt_right"><?php echo MyString::formatoNumero($data['totales']['kilos'], 2, '', false); ?></td>
							<td class="txt_right"><?php echo $data['totales']['kilos'] != 0 ? MyString::formatoNumero(floatval($data['totales']['importe'])/floatval($data['totales']['kilos']), 2, '$', false) : 0; ?></td>
              <td class="txt_right"><?php echo MyString::formatoNumero($data['totales']['importe'], 2, '$', false); ?></td>
							<td class="txt_right"><?php echo MyString::formatoNumero($data['totales']['ret_isr'], 2, '$', false); ?></td>
							<td class="txt_right"><?php echo MyString::formatoNumero($data['totales']['total'], 2, '$', false); ?></td>
							<td></td>
							<td></td>
              <td></td>
							<td></td>
							<!-- <td></td> -->
						</tr>
					</table>
				</td>
			</tr>

			<tr>
				<td class="marg_top20 strong">
					<table class="marg_top20">
						<tbody>
							<tr>
								<td>PAGADO</td>
								<td>NO PAGADO</td>
								<td>TOTAL IMPORTE</td>
								<td>Bonificado</td>
							</tr>
							<tr>
								<td><?php echo MyString::formatoNumero($data['totales']['pagados'], 2, '', false); ?></td>
								<td><?php echo MyString::formatoNumero($data['totales']['no_pagados'], 2, '', false); ?></td>
								<td><?php echo MyString::formatoNumero($data['totales']['total'], 2, '', false); ?></td>
								<td><?php echo MyString::formatoNumero($total_bonificaciones, 2, '', false); ?></td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>

      <?php //if (isset($_GET['farea']) && $_GET['farea'] == 3): // Piña ?>
        <tr>
          <td class="marg_top20 strong">
            <table class="marg_top20" style="width: 50%;">
              <tbody>
                <tr>
                  <td>CALIDAD</td>
                  <td>KILOS</td>
                  <td>%</td>
                </tr>
                <?php foreach ($data['totalesClasif'] as $key => $tclasif): ?>
                  <tr>
                    <td class="border"><?php echo $key ?></td>
                    <td class="border"><?php echo MyString::formatoNumero($tclasif, 2, '', false); ?></td>
                    <td class="border"><?php echo MyString::formatoNumero($tclasif/$data['totales']['kilos']*100, 2, '', false); ?></td>
                  </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </td>
        </tr>
      <?php //endif ?>

		</tbody>
	</table>

</body>
</html>