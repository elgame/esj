<?php
class cuentas_cobrar_model extends privilegios_model{

	function __construct(){
		parent::__construct();
	}


	/**
	 * Saldos de los clientes
   *
   * @return
	 */
	public function getCuentasCobrarData($perpage = '60', $sql2='')
  {
		$sql = '';
		//paginacion
		$params = array(
				'result_items_per_page' => $perpage,
				'result_page' 			=> (isset($_GET['pag'])? $_GET['pag']: 0)
		);
		if($params['result_page'] % $params['result_items_per_page'] == 0)
			$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];
		$_GET['ftipo'] = (isset($_GET['ftipo']))?$_GET['ftipo']:'pp';

		$sql = $this->input->get('ftipo')=='pv'? " AND (Date('".$fecha."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito": '';
		$sqlt = $this->input->get('ftipo')=='pv'? " AND (Date('".$fecha."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito": '';

		if($this->input->get('fid_cliente') != ''){
			$sql .= " AND f.id_cliente = '".$this->input->get('fid_cliente')."'";
			$sqlt .= " AND f.id_cliente = '".$this->input->get('fid_cliente')."'";
		}

		$this->load->model('empresas_model');
		$client_default = $this->empresas_model->getDefaultEmpresa();
		$_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
		$_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
	    if($this->input->get('did_empresa') != ''){
	      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
	      $sqlt .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
	    }

		$query = BDUtil::pagination(
			"SELECT 
				id_cliente,
				nombre_fiscal as nombre,
				SUM(total) as total,
				SUM(iva) as iva, 
				SUM(abonos) as abonos, 
				SUM(saldo) as saldo
			FROM 
			(
				(
					SELECT 
						c.id_cliente,
						c.nombre_fiscal,
						Sum(f.total) AS total,
						Sum(f.importe_iva) AS iva, 
						COALESCE(faa.abonos,0) AS abonos, 
						COALESCE(Sum(f.total) - COALESCE(faa.abonos,0), 0) AS saldo
					FROM
						clientes AS c
						INNER JOIN facturacion AS f ON c.id_cliente = f.id_cliente
						LEFT JOIN (
							SELECT ffaa.id_cliente, Sum(ffaa.abonos) AS abonos
							FROM (
								(
									SELECT 
										f.id_cliente,
										Sum(fa.total) AS abonos
									FROM
										facturacion AS f INNER JOIN facturacion_abonos AS fa ON f.id_factura = fa.id_factura
									WHERE f.status <> 'ca' AND f.status <> 'b' 
										AND Date(fa.fecha) <= '{$fecha}'{$sql}
									GROUP BY f.id_cliente
								)
								UNION
								(
									SELECT 
										f.id_cliente,
										Sum(f.total) AS abonos
									FROM
										facturacion AS f
									WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_nc IS NOT NULL
										AND Date(f.fecha) <= '{$fecha}'{$sql}
									GROUP BY f.id_cliente
								)
							) AS ffaa
							GROUP BY ffaa.id_cliente
						) AS faa ON c.id_cliente = faa.id_cliente
					WHERE  f.status <> 'ca' AND f.status <> 'b' AND f.id_nc IS NULL AND Date(f.fecha) <= '{$fecha}'{$sql}
					GROUP BY c.id_cliente, c.nombre_fiscal, faa.abonos
				)
				UNION
				(
					SELECT 
						c.id_cliente,
						c.nombre_fiscal,
						Sum(f.total) AS total,
						Sum(f.importe_iva) AS iva, 
						COALESCE(faa.abonos,0) AS abonos, 
						COALESCE(Sum(f.total) - COALESCE(faa.abonos,0), 0) AS saldo
					FROM
						clientes AS c
						INNER JOIN facturacion_ventas_remision AS f ON c.id_cliente = f.id_cliente
						LEFT JOIN (
							(
								SELECT 
									f.id_cliente,
									Sum(fa.total) AS abonos
								FROM
									facturacion_ventas_remision AS f INNER JOIN facturacion_ventas_remision_abonos AS fa ON f.id_venta = fa.id_venta
								WHERE f.status <> 'ca' 
									AND Date(fa.fecha) <= '{$fecha}'{$sqlt}
								GROUP BY f.id_cliente
							)

						) AS faa ON c.id_cliente = faa.id_cliente
					WHERE  f.status <> 'ca' AND Date(f.fecha) <= '{$fecha}'{$sqlt}
					GROUP BY c.id_cliente, c.nombre_fiscal, faa.abonos	
				)
			) AS tsaldos
			GROUP BY id_cliente, nombre_fiscal
			ORDER BY nombre_fiscal ASC
			", $params, true);
		$res = $this->db->query($query['query']);
		
		$response = array(
				'cuentas' => array(),
				'total_rows' 		=> $query['total_rows'],
				'items_per_page' 	=> $params['result_items_per_page'],
				'result_page' 		=> $params['result_page'],
				'ttotal_cargos' => 0,
				'ttotal_abonos' => 0,
				'ttotal_saldo' => 0,
		);
		if($res->num_rows() > 0)
			$response['cuentas'] = $res->result();

		foreach ($query['resultset']->result() as $cliente) {
			$response['ttotal_cargos'] += $cliente->total;
			$response['ttotal_abonos'] += $cliente->abonos;
			$response['ttotal_saldo'] += $cliente->saldo;
		}
		
		return $response;
	}
	/**
	 * Descarga el listado de cuentas por pagar en formato pdf
	 */
	public function cuentasCobrarPdf(){
		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Cuentas por cobrar';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': $this->input->get('ftipo') == 'pp'? 'Pendientes por cobrar': 'Todas');
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);
		
		$aligns = array('L', 'R', 'R', 'R');
		$widths = array(100, 35, 35, 35);
		$header = array('Cliente', 'Cargos', 'Abonos', 'Saldo');
		
		$res = $this->getCuentasCobrarData(60);

		$total_cargos = $total_abonos = $total_saldo = 0;
		foreach($res['cuentas'] as $key => $item){
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();
				
				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}
			
			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);
			$datos = array($item->nombre, 
				String::formatoNumero($item->total, 2, '$', false),
				String::formatoNumero($item->abonos, 2, '$', false),
				String::formatoNumero($item->saldo, 2, '$', false),
				);
			$total_cargos += $item->total;
			$total_abonos += $item->abonos;
			$total_saldo += $item->saldo;
			
			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}
		
		$pdf->SetX(6);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(255,255,255);
		$pdf->Row(array('Total:', 
			String::formatoNumero($total_cargos, 2, '$', false),
			String::formatoNumero($total_abonos, 2, '$', false),
			String::formatoNumero($total_saldo, 2, '$', false),
			), true);
		
		$pdf->Output('cuentas_x_cobrar.pdf', 'I');
	}

	public function cuentasCobrarExcel(){
		$res = $this->getCuentasCobrarData(60);
		
		$this->load->library('myexcel');
		$xls = new myexcel();
	
		$worksheet =& $xls->workbook->addWorksheet();
	
		$xls->titulo2 = 'Cuentas por cobrar';
		$xls->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$xls->titulo4 = ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': $this->input->get('ftipo') == 'pp'? 'Pendientes por cobrar': 'Todas');
		
		$data_fac = $res['cuentas'];
			
		$row=0;
		//Header
		$xls->excelHead($worksheet, $row, 8, array(
				array($xls->titulo2, 'format_title2'), 
				array($xls->titulo3, 'format_title3'),
				array($xls->titulo4, 'format_title3')
		));
			
		$row +=3;
		$xls->excelContent($worksheet, $row, $data_fac, array(
				'head' => array('Cliente', 'Cargos', 'Abonos', 'Saldo'),
				'conte' => array(
						array('name' => 'nombre', 'format' => 'format4', 'sum' => -1),
						array('name' => 'total', 'format' => 'format4', 'sum' => 0),
						array('name' => 'abonos', 'format' => 'format4', 'sum' => 0),
						array('name' => 'saldo', 'format' => 'format4', 'sum' => 0),
					)
		));

		foreach ($data_fac as $key => $cuenta) {
			$_GET['id_cliente'] = $cuenta->id_cliente;
			$this->cuentaClienteExcel($xls, false);	
		}
	
		$xls->workbook->send('cuentas_cobrar.xls');
		$xls->workbook->close();
	}

	
	/**
	 * 	CUENTAS
	 * ***************************************
	 * Saldo de un cliente seleccionado
	 * @return [type] [description]
	 */
	public function getCuentaClienteData()
	{
		$sql = '';
		
		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha1 = $fecha2 = '';
		if($_GET['ffecha1'] > $_GET['ffecha2']){
			$fecha2 = $_GET['ffecha1'];
			$fecha1 = $_GET['ffecha2'];
		}else{
			$fecha2 = $_GET['ffecha2'];
			$fecha1 = $_GET['ffecha1'];
		}
		
		$sql = $sqlt = $sql2 = '';
		if($this->input->get('ftipo')=='pv'){
			$sql = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito";
			$sqlt = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito";
			$sql2 = 'WHERE saldo > 0';
		}

    if($this->input->get('did_empresa') != ''){
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
      $sqlt .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
    }
		
		/*** Saldo anterior ***/
		$saldo_anterior = $this->db->query(
			"SELECT 
				id_cliente,
				Sum(total) AS total,
				Sum(iva) AS iva, 
				Sum(abonos) AS abonos, 
				Sum(saldo)::numeric(12, 2) AS saldo,
				tipo
			FROM 
				(
					SELECT 
						c.id_cliente,
						c.nombre_fiscal,
						Sum(f.total) AS total,
						Sum(f.importe_iva) AS iva, 
						COALESCE(Sum(faa.abonos),0) as abonos, 
						COALESCE(Sum(f.total) - COALESCE(Sum(faa.abonos),0), 0) AS saldo,
						'f' as tipo
					FROM
						clientes AS c
						INNER JOIN facturacion AS f ON c.id_cliente = f.id_cliente
						LEFT JOIN (
							(
								SELECT 
									f.id_cliente,
									f.id_factura,
									Sum(fa.total) AS abonos
								FROM
									facturacion AS f 
										INNER JOIN facturacion_abonos AS fa ON f.id_factura = fa.id_factura
								WHERE f.status <> 'ca' AND f.status <> 'b'
									AND f.id_cliente = '{$_GET['id_cliente']}' 
									AND Date(fa.fecha) <= '{$fecha2}'{$sql}
								GROUP BY f.id_cliente, f.id_factura
							)
							UNION
							(
								SELECT 
									f.id_cliente,
									f.id_factura,
									Sum(f.total) AS abonos
								FROM
									facturacion AS f
								WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_nc IS NOT NULL
									AND f.id_cliente = '{$_GET['id_cliente']}' 
									AND Date(f.fecha) <= '{$fecha2}'{$sql}
								GROUP BY f.id_cliente, f.id_factura
							)
						) AS faa ON f.id_cliente = faa.id_cliente AND f.id_factura = faa.id_factura
					WHERE c.id_cliente = '{$_GET['id_cliente']}' AND f.status <> 'ca' AND f.status <> 'b'
						AND Date(f.fecha) < '{$fecha1}'{$sql}
					GROUP BY c.id_cliente, c.nombre_fiscal, faa.abonos, tipo

					UNION ALL

					SELECT 
						c.id_cliente,
						c.nombre_fiscal,
						Sum(f.total) AS total,
						0 AS iva,
						COALESCE(Sum(taa.abonos), 0) as abonos,
						COALESCE(Sum(f.total) - COALESCE(Sum(taa.abonos),0), 0) AS saldo,
						'nv' as tipo
					FROM 
						clientes AS c
						INNER JOIN facturacion_ventas_remision AS f ON c.id_cliente = f.id_cliente
						LEFT JOIN (
							SELECT 
								f.id_cliente,
								f.id_venta,
								Sum(fa.total) AS abonos
							FROM
								facturacion_ventas_remision AS f 
									INNER JOIN facturacion_ventas_remision_abonos AS fa ON f.id_venta = fa.id_venta
							WHERE f.id_cliente = '{$_GET['id_cliente']}' 
								AND f.status <> 'ca'
								AND Date(fa.fecha) <= '{$fecha2}'{$sqlt}
							GROUP BY f.id_cliente, f.id_venta
						) AS taa ON c.id_cliente = taa.id_cliente AND f.id_venta=taa.id_venta
					WHERE c.id_cliente = '{$_GET['id_cliente']}' 
								AND f.status <> 'ca' AND Date(f.fecha) < '{$fecha1}'{$sqlt}
					GROUP BY c.id_cliente, c.nombre_fiscal, taa.abonos, tipo

				) AS sal
			{$sql2}
			GROUP BY id_cliente, tipo
		");
		
		/*** Facturas y ventas en el rango de fechas ***/
		$res = $this->db->query(
			"SELECT
				f.id_factura, 
				f.serie, 
				f.folio, 
				Date(f.fecha) AS fecha, 
				COALESCE(f.total, 0) AS cargo,
				COALESCE(f.importe_iva, 0) AS iva, 
				COALESCE(ac.abono, 0) AS abono,
				(COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(100,2) AS saldo,
				(CASE (COALESCE(f.total, 0) - COALESCE(ac.abono, 0)) WHEN 0 THEN 'Pagada' ELSE 'Pendiente' END) AS estado,
				Date(f.fecha + (f.plazo_credito || ' days')::interval) AS fecha_vencimiento, 
				(Date('{$fecha2}'::timestamp with time zone)-Date(f.fecha)) AS dias_transc,
				( (CASE WHEN f.is_factura='t' THEN 'FACTURA ' ELSE 'REMISION ' END) || f.serie || f.folio) AS concepto,
				'f' as tipo
			FROM
				facturacion AS f
				LEFT JOIN (
					SELECT id_factura, Sum(abono) AS abono
					FROM (
						(
							SELECT 
								id_factura,
								Sum(total) AS abono
							FROM
								facturacion_abonos as fa
							WHERE Date(fecha) <= '{$fecha2}'
							GROUP BY id_factura
						)
						UNION
						(
							SELECT 
								id_nc AS id_factura,
								Sum(total) AS abono
							FROM
								facturacion
							WHERE status <> 'ca' AND status <> 'b' AND id_nc IS NOT NULL
								AND id_cliente = {$_GET['id_cliente']} 
								AND Date(fecha) <= '{$fecha2}'
							GROUP BY id_nc
						)
					) AS ffs
					GROUP BY id_factura
				) AS ac ON f.id_factura = ac.id_factura {$sql}
			WHERE f.id_cliente = {$_GET['id_cliente']} 
				AND f.status <> 'ca' AND f.status <> 'b' AND id_nc IS NULL  
				AND (Date(f.fecha) >= '{$fecha1}' AND Date(f.fecha) <= '{$fecha2}')
				{$sql}

			UNION ALL

			(SELECT
				f.id_venta as id_factura, 
				'' as serie, 
				f.folio, 
				Date(f.fecha) AS fecha, 
				COALESCE(f.total, 0) AS cargo,
				0 AS iva, 
				COALESCE(ac.abono, 0) AS abono,
				(COALESCE(f.total, 0) - COALESCE(ac.abono, 0)) AS saldo,
				(CASE (COALESCE(f.total, 0) - COALESCE(ac.abono, 0)) WHEN 0 THEN 'Pagada' ELSE 'Pendiente' END) AS estado,
				Date(f.fecha + (f.plazo_credito || ' days')::interval) AS fecha_vencimiento, 
				(Date('{$fecha2}'::timestamp with time zone)-Date(f.fecha)) AS dias_transc,
				('Notas de venta ' || f.folio) AS concepto,
				'v' as tipo
			FROM
				facturacion_ventas_remision AS f
				LEFT JOIN (
					SELECT 
						id_venta,
						Sum(total) AS abono
					FROM
						facturacion_ventas_remision_abonos 
					WHERE Date(fecha) <= '{$fecha2}' 
					GROUP BY id_venta
				) AS ac ON f.id_venta = ac.id_venta {$sqlt}
			WHERE f.id_cliente = {$_GET['id_cliente']} 
				AND f.status <> 'ca'
				AND (Date(f.fecha) >= '{$fecha1}' AND Date(f.fecha) <= '{$fecha2}'){$sqlt}
				)

			ORDER BY fecha ASC, serie ASC, folio ASC
			");
		
		
		//obtenemos la info del cliente
		$this->load->model('clientes_model');
		$prov = $this->clientes_model->getClienteInfo($_GET['id_cliente'], true);
		
		$response = array(
				'cuentas' 			=> array(),
				'anterior'			=> array(),
				'cliente' 		=> $prov['info'],
				'fecha1' 			=> $fecha1
		);
		if($res->num_rows() > 0){
			$response['cuentas'] = $res->result();

			//verifica q no sea negativo o exponencial el saldo
			foreach ($response['cuentas'] as $key => $cuenta) {
				$cuenta->saldo = floatval(String::float($cuenta->saldo));
				if($cuenta->saldo == 0){
					$cuenta->estado = 'Pagada';
					$cuenta->fecha_vencimiento = $cuenta->dias_transc = '';

					if($this->input->get('ftipo')=='pv' || $this->input->get('ftipo')=='pp')
						unset($response['cuentas'][$key]);
				}
			}
		}

		if($saldo_anterior->num_rows() > 0){
			$response['anterior'] = $saldo_anterior->result();
			foreach ($response['anterior'] as $key => $c) {
				if ($key > 0){
					$response['anterior'][0]->total += $c->total;
					$response['anterior'][0]->abonos += $c->abonos;
					$response['anterior'][0]->saldo += $c->saldo;
				}
			}
		}

		return $response;
	}

	/**
	 * Descarga el listado de cuentas por pagar en formato pdf
	 */
	public function cuentaClientePdf(){
		$res = $this->getCuentaClienteData();
		
		if (count($res['anterior']) > 0)
			$res['anterior'] = $res['anterior'][0];

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Cuenta de '.$res['cliente']->nombre_fiscal;
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);
	
		$aligns = array('C', 'C', 'C', 'L', 'R', 'R', 'R', 'C', 'C', 'C');
		$widths = array(17, 11, 20, 40, 23, 23, 23, 16, 17, 15);
		$header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cargo', 'Abono', 'Saldo', 'Estado', 'F. Ven.', 'D. Trans.');
		
		$total_cargo = 0;
		$total_abono = 0;
		$total_saldo = 0;
		
		$bad_saldo_ante = true;
		if(isset($res['anterior']->saldo)){ //se suma a los totales del saldo anterior
			$total_cargo += $res['anterior']->total;
			$total_abono += $res['anterior']->abonos;
			$total_saldo += $res['anterior']->saldo;
		}else{
			$res['anterior'] = new stdClass();
			$res['anterior']->total = 0;
			$res['anterior']->abonos = 0;
			$res['anterior']->saldo = 0;
		}
		$res['anterior']->concepto = 'Saldo anterior a '.$res['fecha1'];
		
		foreach($res['cuentas'] as $key => $item){
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();
	
				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}
			
			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);
			if($bad_saldo_ante){
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row(array('', '', '', $res['anterior']->concepto, 
					String::formatoNumero($res['anterior']->total, 2, '$', false), 
					String::formatoNumero($res['anterior']->abonos, 2, '$', false), 
					String::formatoNumero($res['anterior']->saldo, 2, '$', false), 
					'', '', ''), false);
				$bad_saldo_ante = false;
			}
			
			$datos = array($item->fecha, 
									$item->serie, 
									$item->folio, 
									$item->concepto, 
									String::formatoNumero($item->cargo, 2, '$', false), 
									String::formatoNumero($item->abono, 2, '$', false), 
									String::formatoNumero($item->saldo, 2, '$', false), 
									$item->estado, $item->fecha_vencimiento, 
									$item->dias_transc);
			
			$total_cargo += $item->cargo;
			$total_abono += $item->abono;
			$total_saldo += $item->saldo;
				
			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}
	
		$pdf->SetX(6);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetAligns(array('R', 'R', 'R', 'R'));
		$pdf->SetWidths(array(88, 23, 23, 23));
		$pdf->Row(array('Totales:', 
				String::formatoNumero($total_cargo, 2, '$', false),
				String::formatoNumero($total_abono, 2, '$', false),
				String::formatoNumero($total_saldo, 2, '$', false)), true);
	
		$pdf->Output('cuentas_proveedor.pdf', 'I');
	}
	
	public function cuentaClienteExcel(&$xls=null, $close=true){
		$res = $this->getCuentaClienteData();
		
		if (count($res['anterior']) > 0)
			$res['anterior'] = $res['anterior'][0];
		
		$this->load->library('myexcel');
		if($xls == null)
			$xls = new myexcel();
	
		$worksheet =& $xls->workbook->addWorksheet();
	
		$xls->titulo2 = 'Cuenta de '.$res['cliente']->nombre_fiscal;
		$xls->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$xls->titulo4 = ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
		
		if(is_array($res['anterior'])){
			$res['anterior'] = new stdClass();
			$res['anterior']->cargo = 0;
			$res['anterior']->abono = 0;
			$res['anterior']->saldo = 0;
		}else{
			$res['anterior']->cargo = $res['anterior']->total;
			$res['anterior']->abono = $res['anterior']->abonos;
		}
		$res['anterior']->fecha = $res['anterior']->serie = $res['anterior']->folio = '';
		$res['anterior']->concepto = $res['anterior']->estado = $res['anterior']->fecha_vencimiento = '';
		$res['anterior']->dias_transc = '';
		
		array_unshift($res['cuentas'], $res['anterior']);
		
		$data_fac = $res['cuentas'];
			
		$row=0;
		//Header
		$xls->excelHead($worksheet, $row, 8, array(
				array($xls->titulo2, 'format_title2'), 
				array($xls->titulo3, 'format_title3'),
				array($xls->titulo4, 'format_title3')
		));
			
		$row +=3;
		$xls->excelContent($worksheet, $row, $data_fac, array(
				'head' => array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cargo', 'Abono', 'Saldo', 'Estado', 'Fecha Vencimiento', 'Dias Trans.'),
				'conte' => array(
						array('name' => 'fecha', 'format' => 'format4', 'sum' => -1),
						array('name' => 'serie', 'format' => 'format4', 'sum' => -1),
						array('name' => 'folio', 'format' => 'format4', 'sum' => -1),
						array('name' => 'concepto', 'format' => 'format4', 'sum' => -1),
						array('name' => 'cargo', 'format' => 'format4', 'sum' => 0),
						array('name' => 'abono', 'format' => 'format4', 'sum' => 0),
						array('name' => 'saldo', 'format' => 'format4', 'sum' => 0),
						array('name' => 'estado', 'format' => 'format4', 'sum' => -1),
						array('name' => 'fecha_vencimiento', 'format' => 'format4', 'sum' => -1),
						array('name' => 'dias_transc', 'format' => 'format4', 'sum' => -1))
		));
	
		if($close){
			$xls->workbook->send('cuentaCliente.xls');
			$xls->workbook->close();
		}
	}



	/**
	 * DETALLE FACTURA
	 * **************************
	 * Obtiene los abonos de una factura o nota de venta
	 * @return [type] [description]
	 */
	public function getDetalleVentaFacturaData($id_factura=null, $tipo=null)
	{
		$_GET['id'] = $id_factura==null? $_GET['id']: $id_factura;
		$_GET['tipo'] = $tipo==null? $_GET['tipo']: $tipo;
		$id_factura_aux = $_GET['id']; 
		$sql = '';
	
		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha1 = $fecha2 = '';
		if($_GET['ffecha1'] > $_GET['ffecha2']){
			$fecha2 = $_GET['ffecha1'];
			$fecha1 = $_GET['ffecha2'];
		}else{
			$fecha2 = $_GET['ffecha2'];
			$fecha1 = $_GET['ffecha1'];
		}
	
		$sql = $sql2 = '';
		if($this->input->get('ftipo')=='pv'){
			$sql = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(c.fecha)) > c.plazo_credito";
			$sql2 = 'WHERE saldo > 0';
		}
	
		$sql_nc = '';
		// if ($_GET['tipo'] == 'f')
		// {
			$data['info'] = $this->db->query(
											"SELECT id_factura AS id, DATE(fecha) as fecha, serie, folio, condicion_pago, status, total,
												plazo_credito, id_cliente, 'f' AS tipo 
												FROM facturacion
												WHERE id_factura={$_GET['id']}")->result();
			$sql = array('tabla' => 'facturacion_abonos', 
										'where_field' => 'id_factura');
			$sql_nc = "UNION 
						SELECT
							id_factura AS id_abono, 
							fecha, 
							total AS abono, 
							('Nota de credito ' || serie || folio) AS concepto,
							'nc' AS tipo
						FROM facturacion
						WHERE status <> 'ca' AND status <> 'b' AND id_nc IS NOT NULL
							AND id_nc = {$_GET['id']} 
							AND Date(fecha) <= '{$fecha2}' ";
		// }
		// else
		// {
		// 	$data['info'] = $this->db->query(
		// 									"SELECT id_venta AS id, fecha, '' as serie, folio, 	
		// 										condicion_pago,
		// 										status, total, 
		// 										plazo_credito, id_cliente, 'v' AS tipo
		// 									FROM facturacion_ventas_remision
		// 									WHERE id_venta={$_GET['id']}")->result();
		// 	$sql = array('tabla' => 'facturacion_ventas_remision_abonos', 
		// 								'where_field' => 'id_venta');
		// }

			//Obtenemos los abonos de la factura o ticket
			$res = $this->db->query(
				"SELECT id_abono, Date(fecha) AS fecha, abono, concepto, tipo
				FROM 
				(
						SELECT
							id_abono, 
							fecha, 
							total AS abono, 
							concepto,
							'ab' AS tipo
						FROM {$sql['tabla']}
						WHERE {$sql['where_field']} = {$_GET['id']}
							AND Date(fecha) <= '{$fecha2}' 
					{$sql_nc}
				) AS tt
					ORDER BY fecha ASC
			");	
	
		//obtenemos la info del cliente
		$prov['info'] = '';
		if (isset($data['info'][0]->id_cliente)) 
		{
			$this->load->model('clientes_model');
			$prov = $this->clientes_model->getClienteInfo($data['info'][0]->id_cliente, true);
		}
	
		$response = array(
				'abonos'  => array(),
				'saldo'   => '',
				'cobro'   => $data['info'],
				'cliente' => $prov['info'],
				'fecha1'  => $fecha1
		);
		$abonos = 0;
		if($res->num_rows() > 0){
			$response['abonos'] = $res->result();

			foreach ($response['abonos'] as $key => $value) {
				$abonos += $value->abono;
			}
		}
		$response['saldo'] = $response['cobro'][0]->total - $abonos;

		$_GET['id'] = $id_factura_aux;
	
		return $response;
	}


	/**
	 * Abonos de facturas y ventas
	 * ************************************
	 */
	public function getCuentaPagoAdicional()
	{
	  return '42200400';
	}

	public function getCuentaPagoMenor()
	{
	  return '00800000';
	}

	public function addAbonoMasivo()
	{
		$ids   = $_POST['ids']; //explode(',', substr($_GET['id'], 1));
		$tipos = $_POST['tipos']; //explode(',', substr($_GET['tipo'], 1));
		$total = 0; //$this->input->post('dmonto');
		$desc  = '';

		//Se registra el movimiento en la cuenta bancaria
		$this->load->model('banco_cuentas_model');
		$data_cuenta  = $this->banco_cuentas_model->getCuentaInfo($this->input->post('dcuenta'));
		$data_cuenta  = $data_cuenta['info'];
		$_GET['id']   = $ids[0];
		$_GET['tipo'] = $tipos[0];
		$inf_factura  = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($_GET['id'], $_GET['tipo']);
		//Registra deposito
		foreach ($_POST['ids'] as $key => $value)  //foreach ($ids as $key => $value) 
		{
			$total += $_POST['montofv'][$key];
			$desc .= '|'.$_POST['factura_desc'][$key].'=>'.String::formatoNumero($_POST['montofv'][$key], 2, '', false);
		}
		$desc = ' ('.substr($desc, 1).')';
		$resp = $this->banco_cuentas_model->addDeposito(array(
					'id_cuenta'   => $this->input->post('dcuenta'),
					'id_banco'    => $data_cuenta->id_banco,
					'fecha'       => $this->input->post('dfecha'),
					'numero_ref'  => $this->input->post('dreferencia'),
					'concepto'    => $this->input->post('dconcepto').$desc,
					'monto'       => $total,
					'tipo'        => 't',
					'entransito'  => 't',
					'metodo_pago' => $this->input->post('fmetodo_pago'),
					'id_cliente'  => $inf_factura['cliente']->id_cliente,
					'a_nombre_de' => $inf_factura['cliente']->nombre_fiscal,
					));

		foreach ($_POST['ids'] as $key => $value)  //foreach ($ids as $key => $value) 
		{
			$_GET['id']   = $value;
			$_GET['tipo'] = $_POST['tipos'][$key];
			$data = array('fecha'        => $this->input->post('dfecha'),
						'concepto'       => $this->input->post('dconcepto'),
						'total'          => $_POST['montofv'][$key], //$total,
						'id_cuenta'      => $this->input->post('dcuenta'),
						'ref_movimiento' => $this->input->post('dreferencia'),
						'saldar'         => $_POST['saldar'][$key] );
			$resa = $this->addAbono($data, null, true);
			$total -= $resa['total'];

			//Registra el rastro de la factura o remision que se abono en bancos
			if(isset($resp['id_movimiento']))
			{
				if($_POST['tipos'][$key] == 'f') //factura
					$this->db->insert('banco_movimientos_facturas', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_factura' => $resa['id_abono']));
				else //remision
					$this->db->insert('banco_movimientos_facturas', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_factura' => $resa['id_abono']));
					// $this->db->insert('banco_movimientos_ventas_remision', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_venta_remision' => $resa['id_abono']));
			}
		}

		return $resp;
	}

	public function addAbono($data=null, $id=null, $masivo=false)
	{
		$id = $id==null? $this->input->get('id') : $id; //id factura o nota de venta

		if ($this->input->get('tipo') == 'f') {
			$camps = array('id_factura', 'facturacion_abonos', 'facturacion');
		}else{
			$camps = array('id_factura', 'facturacion_abonos', 'facturacion');
			// $camps = array('id_venta', 'facturacion_ventas_remision_abonos', 'facturacion_ventas_remision');
		}

		if ($data == null) {
			$data = array('fecha'        => $this->input->post('dfecha'),
						'concepto'       => $this->input->post('dconcepto'),
						'total'          => $this->input->post('dmonto'),
						'id_cuenta'      => $this->input->post('dcuenta'),
						'ref_movimiento' => $this->input->post('dreferencia'),
						'saldar' => 'no' );
		}

		$pagada = false;
		$pago_mayor = 0;//valor cuando pagan de mas en una factura se carga a pagos adicionales
		$pago_saldar = 0;//valor cuando pagan de menos y aun asi saldan la cuenta
		$inf_factura = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($id, $this->input->get('tipo'));
		if ($inf_factura['saldo'] <= $data['total']){ //se ajusta
			$pago_mayor = $data['total']-$inf_factura['saldo'];
			$data['total'] -= $pago_mayor;
			$pagada = true;
		}elseif($data['saldar'] == 'si'){
			$pago_saldar = $inf_factura['saldo']-$data['total'];
			$pagada = true;
		}

		//Se registra el movimiento en la cuenta bancaria
		if ($masivo == false) 
		{
			$this->load->model('banco_cuentas_model');
			$data_cuenta  = $this->banco_cuentas_model->getCuentaInfo($data['id_cuenta']);
			$data_cuenta  = $data_cuenta['info'];

			$data['concepto'] .= ' ('.$inf_factura['cobro'][0]->serie.$inf_factura['cobro'][0]->folio.'=>'.String::formatoNumero($data['total'], 2, '', false).')';
			$resp = $this->banco_cuentas_model->addDeposito(array(
						'id_cuenta'   => $data['id_cuenta'],
						'id_banco'    => $data_cuenta->id_banco,
						'fecha'       => $data['fecha'],
						'numero_ref'  => $data['ref_movimiento'],
						'concepto'    => $data['concepto'],
						'monto'       => $data['total']+$pago_mayor,
						'tipo'        => 't',
						'entransito'  => 't',
						'metodo_pago' => $this->input->post('fmetodo_pago'),
						'id_cliente'  => $inf_factura['cliente']->id_cliente,
						'a_nombre_de' => $inf_factura['cliente']->nombre_fiscal,
						));
		}

		$data = array(
			$camps[0]        => $id, 
			'fecha'          => $data['fecha'],
			'concepto'       => $data['concepto'],
			'total'          => $data['total']+$pago_saldar,
			'id_cuenta'      => $data['id_cuenta'],
			'ref_movimiento' => $data['ref_movimiento'], );
		//se inserta el abono
		$this->db->insert($camps[1], $data);
		$data['id_abono'] = $this->db->insert_id($camps[1], 'id_abono');

		//verifica si la factura se pago, se cambia el status
		if($pagada){
			$this->db->update($camps[2], array('status' => 'pa'), "{$camps[0]} = {$id}");
		}

		//Si se hiso un pago mayor se registra a la factura
		if ($pago_mayor > 0)
		{
			$data_mayor               = $data;
			$data_mayor['concepto']   = 'Pago adicional';
			$data_mayor['total']      = $pago_mayor;
			$data_mayor['cuenta_cpi'] = $this->getCuentaPagoAdicional();
			$data_mayor['tipo']       = 'm';
			unset($data_mayor['id_cuenta'], $data_mayor['ref_movimiento']);
			$this->db->insert($camps[1].'_otros', $data_mayor);
		}elseif($pago_saldar > 0){
			$data_mayor               = $data;
			$data_mayor['concepto']   = 'Pago menor';
			$data_mayor['total']      = $pago_saldar;
			$data_mayor['cuenta_cpi'] = $this->getCuentaPagoMenor();
			$data_mayor['tipo']       = 's';
			unset($data_mayor['id_cuenta'], $data_mayor['ref_movimiento']);
			$this->db->insert($camps[1].'_otros', $data_mayor);
		}

		//Registra el rastro de la factura o remision que se abono en bancos (si no es masivo)
		if(isset($resp['id_movimiento']))
		{
			if($camps[0] == 'id_factura') //factura
				$this->db->insert('banco_movimientos_facturas', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_factura' => $data['id_abono']));
			else //remision
				$this->db->insert('banco_movimientos_facturas', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_factura' => $data['id_abono']));
				// $this->db->insert('banco_movimientos_ventas_remision', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_venta_remision' => $data['id_abono']));
		}

		if($masivo)
			return $data;
		else
			return $resp;
	}

	public function removeAbono($id=null, $tipo=null, $ida=null)
	{
		$tipo = $tipo!=null? $tipo : $this->input->get('tipo');
		$ida  = $ida!=null? $ida : $_GET['ida'];
		$id   = $id!=null? $id : $_GET['id'];

		if($this->input->get('nc') == 'si')
		{
			$this->load->model('facturacion_model');
			$this->facturacion_model->cancelaFactura($ida);
		}else
		{
			if ($tipo == 'f') {
				$camps = array('id_abono', 'facturacion_abonos', 'facturacion', 'id_factura');
			}else{
				$camps = array('id_abono', 'facturacion_abonos', 'facturacion', 'id_factura');
				// $camps = array('id_abono', 'facturacion_ventas_remision_abonos', 'facturacion_ventas_remision', 'id_venta');
			}

			$this->db->delete($camps[1], "{$camps[0]} = {$ida}");
			$this->db->delete($camps[1].'_otros', "{$camps[0]} = {$ida}"); //elimina los pagos adicionales
		}
		//Se cambia el estado de la factura
		$this->db->update($camps[2], array('status' => 'p'), "{$camps[3]} = {$id}");

		return true;
	}

	public function getAbonosData($movimientoId=null)
	{
		//paginacion
		$params = array(
				'result_items_per_page' => '60',
				'result_page' 			=> (isset($_GET['pag'])? $_GET['pag']: 0)
		);
		if($params['result_page'] % $params['result_items_per_page'] == 0)
			$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

		$response = array();
		$sql = $sql2 = '';

		if($movimientoId!=null)
			$sql .= " AND bmf.id_movimiento = {$movimientoId}";
		else{
			if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
			  $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m").'-01';
			  $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
			}
			if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
			  $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
			  $sql .= " AND Date(fa.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
			}

			if ($this->input->get('did_empresa') != '')
			  $sql .= " AND f.id_empresa = '".$_GET['did_empresa']."'";
		}


		$query = BDUtil::pagination(
			"SELECT 
			    bmf.id_movimiento, fa.ref_movimiento, fa.concepto, Sum(fa.total) AS total_abono, 
			    bc.cuenta_cpi, Sum(f.subtotal) AS subtotal, Sum(f.total) AS total, Sum(((fa.total*100/f.total)*f.importe_iva/100)) AS importe_iva, 
			    Sum(((fa.total*100/f.total)*f.retencion_iva/100)) AS retencion_iva, c.nombre_fiscal, 
			    c.cuenta_cpi AS cuenta_cpi_cliente, Date(fa.fecha) AS fecha, e.nombre_fiscal AS empresa, e.logo
			  FROM facturacion AS f 
			    INNER JOIN facturacion_abonos AS fa ON fa.id_factura = f.id_factura
			    INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta 
			    INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente 
			    INNER JOIN empresas AS e ON e.id_empresa = f.id_empresa 
			    INNER JOIN banco_movimientos_facturas AS bmf ON bmf.id_abono_factura = fa.id_abono 
			  WHERE f.status <> 'ca' AND f.status <> 'b' 
			     {$sql}
			  GROUP BY bmf.id_movimiento, fa.ref_movimiento, fa.concepto, 
			    bc.cuenta_cpi, c.nombre_fiscal, c.cuenta_cpi, Date(fa.fecha), 
			    e.nombre_fiscal, e.logo
			  ORDER BY Date(fa.fecha) DESC
			  ", $params, true);
		$res = $this->db->query($query['query']);
		
		$response = array(
			'abonos'         => array(),
			'facturas'       => array(),
			'total_rows'     => $query['total_rows'],
			'items_per_page' => $params['result_items_per_page'],
			'result_page'    => $params['result_page'],
		);

		if($res->num_rows() > 0)
		{
		  $response['abonos'] = $res->result();
		  $res->free_result();


		  if($movimientoId!=null)
		  {
		  	$res = $this->db->query(
			  "SELECT 
			    fa.id_abono, f.serie, f.folio, fa.ref_movimiento, fa.concepto, fa.total, Date(fa.fecha) AS fecha
			  FROM facturacion AS f 
			    INNER JOIN facturacion_abonos AS fa ON fa.id_factura = f.id_factura
			    INNER JOIN banco_movimientos_facturas AS bmf ON bmf.id_abono_factura = fa.id_abono 
			  WHERE f.status <> 'ca' AND f.status <> 'b' 
			     {$sql}
			  ORDER BY fa.id_abono ASC
			  ");
		  	$response['facturas'] = $res->result();
		  	$res->free_result();
		  }
		}

		return $response;
	}

  /**
    * Visualiza/Descarga el PDF del abono.
    *
    * @return void
    */
   public function imprimir_abono($movimientoId, $path = null)
   {
      $orden = $this->getAbonosData($movimientoId);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('L', 'mm', array(70, 215.9));
      $pdf->show_head = false;
      $pdf->titulo1 = $orden['abonos'][0]->empresa;
      $pdf->titulo2 = 'Cliente: ' . $orden['abonos'][0]->nombre_fiscal;
      $pdf->titulo3 = 'RECIBO DE PAGO';

      $pdf->logo = $orden['abonos'][0]->logo!=''? (file_exists($orden['abonos'][0]->logo)? $orden['abonos'][0]->logo: '') : '';

      $pdf->AliasNbPages();
      $pdf->AddPage();
      

      $y = $pdf->GetY();

      $pdf->SetFont('helvetica','B', 13);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetXY(80, $y);
      $pdf->MultiCell(60,4, 'RECIBO DE PAGO');
      // $pdf->SetY($pdf->GetY()+1);
      // $pdf->MultiCell(60,4, 'Recibi de '.$orden['abonos'][0]->nombre_fiscal);
      // $pdf->MultiCell(60,4, 'La cantidad de '.String::formatoNumero($orden['abonos'][0]->total_abono, 2, '$', false).
      // 			' ('.String::num2letras($orden['abonos'][0]->total_abono).')');
      // $pdf->MultiCell(60,4, 'A cuenta de: '.implode('-', $facturas_txt));

      $pdf->SetFont('helvetica','B', 10);
      $pdf->SetXY(10, $pdf->GetY()+6);
      $pdf->MultiCell(115,4, "FECHA: ".String::fechaATexto($orden['abonos'][0]->fecha));
      $pdf->SetXY(10, $pdf->GetY()+1);
      $pdf->MultiCell(115,4, 'Recibi de '.$orden['abonos'][0]->nombre_fiscal);
      $pdf->SetX(10);
      $pdf->MultiCell(115,4, 'La cantidad de '.String::formatoNumero($orden['abonos'][0]->total_abono, 2, '$', false).
      			' ('.String::num2letras($orden['abonos'][0]->total_abono).')');
      $pdf->SetX(10);
      $pdf->MultiCell(115,4, 'A orden de: '.$orden['abonos'][0]->empresa);

      $pdf->Text(60, 53, 'Firma ____________________________________');


      $pdf->SetXY(140, $y+6);
      $pdf->MultiCell(115,4, 'A cuenta de: ');

      $aligns = array('C', 'R');
      $widths = array(25, 40);
      $header = array('FOLIO', 'TOTAL');

      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFillColor(255,255,255);
      $pdf->SetXY(140, $pdf->GetY());
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($header, true, false);
      $pdf->Line(140, $pdf->GetY()-1, 205, $pdf->GetY()-1);
      foreach ($orden['facturas'] as $key => $prod)
      {
      	$pdf->SetFont('Arial','',8);
        $pdf->SetTextColor(0,0,0);
        $datos = array(
          $prod->serie.$prod->folio,
          String::formatoNumero($prod->total, 2, '$', false),
        );
        $pdf->SetXY(140, $pdf->GetY()-2);
        $pdf->Row($datos, false, false);
      }

      if ($path)
      {
        $file = $path.'recibo_pago.pdf';
        $pdf->Output($file, 'F');
        return $file;
      }
      else
      {
        $pdf->Output('recibo_pago.pdf', 'I');
      }
   }

   /**
	 * 	ESTADO DE CUENTAS
	 * ***************************************
	 * Saldo de un cliente seleccionado
	 * @return [type] [description]
	 */
	public function getEstadoCuentaData($sql_clientes='', $all_clientes=false, $all_facturas=false, $sqlext=array('',''))
	{
		$sql = '';
		
		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha1 = $fecha2 = '';
		if($_GET['ffecha1'] > $_GET['ffecha2']){
			$fecha2 = $_GET['ffecha1'];
			$fecha1 = $_GET['ffecha2'];
		}else{
			$fecha2 = $_GET['ffecha2'];
			$fecha1 = $_GET['ffecha1'];
		}
		
		$sql = $sqlt = $sql2 = '';
		if($this->input->get('ftipo')=='pv'){
			$sql = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito";
			$sqlt = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito";
			$sql2 = 'WHERE saldo > 0';
		}

	    if($this->input->get('did_empresa') != ''){
	      $sql .= " AND id_empresa = '".$this->input->get('did_empresa')."'";
	      $sqlt .= " AND id_empresa = '".$this->input->get('did_empresa')."'";
	    }


	    $clientes = $this->db->query("SELECT id_cliente, nombre_fiscal, cuenta_cpi FROM clientes WHERE status = 'ac' {$sql_clientes} ORDER BY cuenta_cpi ASC ");
	    $response = array();
	    foreach ($clientes->result() as $keyc => $cliente)
	    {
	    	$cliente->saldo = 0;

	    	/*** Saldo anterior ***/
			$saldo_anterior = $this->db->query(
				"SELECT 
					id_cliente,
					Sum(total) AS total,
					Sum(iva) AS iva, 
					Sum(abonos) AS abonos, 
					Sum(saldo)::numeric(12, 2) AS saldo
				FROM 
					(
						SELECT 
							c.id_cliente,
							c.nombre_fiscal,
							Sum(f.total) AS total,
							Sum(f.importe_iva) AS iva, 
							COALESCE(Sum(faa.abonos),0) as abonos, 
							COALESCE(Sum(f.total) - COALESCE(Sum(faa.abonos),0), 0) AS saldo,
							'f'::text as tipo
						FROM
							clientes AS c
							INNER JOIN facturacion AS f ON c.id_cliente = f.id_cliente
							LEFT JOIN (
								(
									SELECT 
										f.id_cliente,
										f.id_factura,
										Sum(fa.total) AS abonos
									FROM
										facturacion AS f 
											INNER JOIN facturacion_abonos AS fa ON f.id_factura = fa.id_factura
									WHERE f.status <> 'ca' AND f.status <> 'b'
										AND f.id_cliente = '{$cliente->id_cliente}' 
										AND Date(fa.fecha) <= '{$fecha2}'{$sql}
									GROUP BY f.id_cliente, f.id_factura
								)
								UNION
								(
									SELECT 
										f.id_cliente,
										f.id_factura,
										Sum(f.total) AS abonos
									FROM
										facturacion AS f
									WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_nc IS NOT NULL
										AND f.id_cliente = '{$cliente->id_cliente}' 
										AND Date(f.fecha) <= '{$fecha2}'{$sql}
									GROUP BY f.id_cliente, f.id_factura
								)
							) AS faa ON f.id_cliente = faa.id_cliente AND f.id_factura = faa.id_factura
						WHERE c.id_cliente = '{$cliente->id_cliente}' AND f.status <> 'ca' AND f.status <> 'b'
							AND Date(f.fecha) < '{$fecha1}'{$sql} {$sqlext[0]}
						GROUP BY c.id_cliente, c.nombre_fiscal, faa.abonos, tipo

						UNION ALL

						SELECT 
							c.id_cliente,
							c.nombre_fiscal,
							Sum(f.total) AS total,
							0 AS iva,
							COALESCE(Sum(taa.abonos), 0) as abonos,
							COALESCE(Sum(f.total) - COALESCE(Sum(taa.abonos),0), 0) AS saldo,
							'nv'::text as tipo
						FROM 
							clientes AS c
							INNER JOIN facturacion_ventas_remision AS f ON c.id_cliente = f.id_cliente
							LEFT JOIN (
								SELECT 
									f.id_cliente,
									f.id_venta,
									Sum(fa.total) AS abonos
								FROM
									facturacion_ventas_remision AS f 
										INNER JOIN facturacion_ventas_remision_abonos AS fa ON f.id_venta = fa.id_venta
								WHERE f.id_cliente = '{$cliente->id_cliente}' 
									AND f.status <> 'ca'
									AND Date(fa.fecha) <= '{$fecha2}'{$sqlt}
								GROUP BY f.id_cliente, f.id_venta
							) AS taa ON c.id_cliente = taa.id_cliente AND f.id_venta=taa.id_venta
						WHERE c.id_cliente = '{$cliente->id_cliente}' 
									AND f.status <> 'ca' AND Date(f.fecha) < '{$fecha1}'{$sqlt}
						GROUP BY c.id_cliente, c.nombre_fiscal, taa.abonos, tipo

					) AS sal
				{$sql2}
				GROUP BY id_cliente
			");
			$cliente->saldo_anterior = $saldo_anterior->row();
			$saldo_anterior->free_result();
			if( isset($cliente->saldo_anterior->saldo) )
				$cliente->saldo = $cliente->saldo_anterior->saldo;

			/** Facturas ***/
			$sql_field_cantidad = '';
			if($all_clientes && $all_facturas)
				$sql_field_cantidad = ", (SELECT Sum(cantidad) FROM facturacion_productos WHERE id_factura = facturacion.id_factura) AS cantidad_productos";
			$facturas = $this->db->query("SELECT id_factura, Date(fecha) AS fecha, serie, folio, 
					(CASE is_factura WHEN true THEN 'FACTURA ELECTRONICA' ELSE 'REMISION' END)::text AS concepto, subtotal, importe_iva, total, 
					Date(fecha + (plazo_credito || ' days')::interval) AS fecha_vencimiento {$sql_field_cantidad}
				FROM facturacion 
				WHERE id_cliente = {$cliente->id_cliente} 
					AND status <> 'ca' AND status <> 'b' AND id_nc IS NULL
					AND (Date(fecha) >= '{$fecha1}' AND Date(fecha) <= '{$fecha2}')
					{$sql} {$sqlext[1]}
				ORDER BY fecha ASC");
			$cliente->facturas = $facturas->result();
			$facturas->free_result();
			foreach ($cliente->facturas as $key => $factura)
			{
				$cliente->saldo += $factura->total;
				$cliente->facturas[$key]->saldo = $factura->total;

				/** abonos **/
				$abonos = $this->db->query("SELECT id_abono, serie, folio, fecha, concepto, abono
					FROM (
						(
							SELECT 
								id_abono,
								''::text AS serie,
								id_abono AS folio,
								Date(fecha) AS fecha,
								'Pago del cliente'::text AS concepto,
								total AS abono
							FROM
								facturacion_abonos as fa
							WHERE id_factura = {$factura->id_factura} AND Date(fecha) <= '{$fecha2}'
						)
						UNION
						(
							SELECT 
								id_factura AS id_abono,
								serie,
								folio,
								Date(fecha) AS fecha,
								'NOTA CREDITO DIGITAL'::text AS concepto,
								total AS abono
							FROM
								facturacion
							WHERE status <> 'ca' AND status <> 'b' AND id_nc = {$factura->id_factura}
								AND Date(fecha) <= '{$fecha2}'
						)
					) AS ffs
					ORDER BY id_abono");
				$cliente->facturas[$key]->abonos = $abonos->result();
				$abonos->free_result();

				$cliente->facturas[$key]->abonos_total = 0;
				foreach ($cliente->facturas[$key]->abonos as $keyab => $abono)
				{
					$cliente->facturas[$key]->abonos_total += $abono->abono;
				}
				$cliente->saldo -= $cliente->facturas[$key]->abonos_total;
				$cliente->facturas[$key]->saldo -= $cliente->facturas[$key]->abonos_total;

				if($cliente->facturas[$key]->saldo <= 0 && $all_facturas == false)
					unset($cliente->facturas[$key]);
			}
			
			if( $cliente->saldo > 0 || $all_clientes)
			{
				if($cliente->saldo > 0 && $all_clientes == false)
					$response[] = $cliente;
				elseif($all_clientes && count($cliente->facturas) > 0)
					$response[] = $cliente;
			}
	    }
	    $clientes->free_result();

		return $response;
	}

	/**
	 * Descarga el listado de cuentas por pagar en formato pdf
	 */
	public function estadoCuentaPdf(){
		$res = $this->getEstadoCuentaData();
		
		// var_dump($res);

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'ESTADO DE CUENTA DE CLIENTES';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		// $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
		$pdf->AliasNbPages();
		// $pdf->AddPage();
		$pdf->SetFont('Arial','',8);
	
		$aligns = array('L', 'L', 'R', 'L', 'R', 'R', 'R', 'L');
		$widths = array(28, 11, 20, 50, 23, 23, 23, 23);
		$header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cargos', 'Abonos', 'Saldo', 'F. Ven.');
		
		$total_saldo_cliente = 0;
		foreach($res as $key => $item){
			$total_cargo = 0;
			$total_abono = 0;
			$total_saldo = 0;
			
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();
	
				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}

			$pdf->SetFont('Arial','B',8);
			$pdf->SetTextColor(0,0,0);

			$pdf->SetXY(6, $pdf->GetY());
			$pdf->SetAligns(array('L', 'L'));
			$pdf->SetWidths(array(20, 170));
			$pdf->Row(array('CLIENTE:', $item->cuenta_cpi), false, false);
			$pdf->SetXY(6, $pdf->GetY()-2);
			$pdf->Row(array('NOMBRE:', $item->nombre_fiscal), false, false);

			$pdf->SetXY(6, $pdf->GetY()+3);

			$pdf->SetFont('Arial','',8);
			foreach ($item->facturas as $keyf => $factura)
			{
				$total_cargo += $factura->total;
				$total_saldo += $factura->saldo;

				if($keyf == 0 && isset($item->saldo_anterior->saldo) ){
					$datos = array('', '', '', 
							'Saldo Inicial', 
							String::formatoNumero($item->saldo_anterior->saldo, 2, '', false), 
							'', '', '',
						);
					$pdf->SetXY(6, $pdf->GetY()-2);
					$pdf->SetAligns($aligns);
					$pdf->SetWidths($widths);
					$pdf->Row($datos, false, false);
				}

				$datos = array(String::fechaATexto($factura->fecha, '/c'), 
								$factura->serie, 
								$factura->folio, 
								$factura->concepto, 
								String::formatoNumero($factura->total, 2, '', false), 
								'', 
								String::formatoNumero( ($factura->saldo) , 2, '', false), 
								String::fechaATexto($factura->fecha_vencimiento, '/c'),
							);
					
				$pdf->SetXY(6, $pdf->GetY());
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($datos, false, true);

				foreach ($factura->abonos as $keya => $abono)
				{
					$total_abono += $abono->abono;
					$datos = array('   '.String::fechaATexto($abono->fecha, '/c'), 
								$abono->serie, 
								$abono->folio, 
								$abono->concepto, 
								'', 
								'('.String::formatoNumero($abono->abono, 2, '', false).')', 
								'', '',
							);
					
					$pdf->SetXY(6, $pdf->GetY());
					$pdf->SetAligns($aligns);
					$pdf->SetWidths($widths);
					$pdf->Row($datos, false, true);
				}
			}
			
			$pdf->SetX(115);
			$pdf->SetFont('Arial','B',8);
			// $pdf->SetTextColor(255,255,255);
			$pdf->SetAligns(array('R', 'R', 'R', 'R'));
			$pdf->SetWidths(array(23, 23, 23));
			$pdf->Row(array(
					String::formatoNumero($total_cargo, 2, '', false),
					String::formatoNumero($total_abono, 2, '', false),
					String::formatoNumero($total_saldo, 2, '', false)), false);

			$saldo_cliente = ((isset($item->saldo_anterior->saldo)? $item->saldo_anterior->saldo: 0) + $total_cargo - $total_abono);
			$pdf->SetAligns(array('R', 'R', 'R', 'R'));
			$pdf->SetWidths(array(50, 23, 23));
			$pdf->SetX(65);
			$pdf->Row(array('Saldo Inicial', String::formatoNumero( (isset($item->saldo_anterior->saldo)? $item->saldo_anterior->saldo: 0) , 2, '', false)), false);
			$pdf->SetX(65);
			$pdf->Row(array('(+) Cargos', String::formatoNumero($total_cargo, 2, '', false)), false);
			$pdf->SetX(65);
			$pdf->Row(array('(-) Abonos', String::formatoNumero($total_abono, 2, '', false)), false);
			$pdf->SetX(65);
			$pdf->Row(array('(=) Saldo Final', String::formatoNumero( $saldo_cliente , 2, '', false)), false);

			$total_saldo_cliente += $saldo_cliente;
		}

		$pdf->SetXY(65, $pdf->GetY()+4);
		$pdf->Row(array('TOTAL SALDO DE CLIENTES', String::formatoNumero( $total_saldo_cliente , 2, '', false)), false);
	
	
		$pdf->Output('estado_cuenta.pdf', 'I');
	}

}