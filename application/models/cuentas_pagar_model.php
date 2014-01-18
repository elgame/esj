<?php
class cuentas_pagar_model extends privilegios_model{

	function __construct(){
		parent::__construct();
	}


	/**
	 * Saldos de los clientes
   *
   * @return
	 */
	public function getCuentasPagarData($perpage = '60', $sql2='')
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
			$sql .= " AND f.id_proveedor = '".$this->input->get('fid_cliente')."'";
			$sqlt .= " AND f.id_proveedor = '".$this->input->get('fid_cliente')."'";
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
				id_proveedor,
				nombre_fiscal as nombre,
				SUM(total) as total,
				SUM(iva) as iva, 
				SUM(abonos) as abonos, 
				SUM(saldo) as saldo
			FROM 
			(
				(
					SELECT 
						c.id_proveedor,
						c.nombre_fiscal,
						Sum(f.total) AS total,
						Sum(f.importe_iva) AS iva, 
						COALESCE(faa.abonos,0) AS abonos, 
						COALESCE(Sum(f.total) - COALESCE(faa.abonos,0), 0) AS saldo
					FROM
						proveedores AS c
						INNER JOIN compras AS f ON c.id_proveedor = f.id_proveedor
						LEFT JOIN (
							SELECT ffaa.id_proveedor, Sum(ffaa.abonos) AS abonos
							FROM (
								(
									SELECT 
										f.id_proveedor,
										Sum(fa.total) AS abonos
									FROM
										compras AS f INNER JOIN compras_abonos AS fa ON f.id_compra = fa.id_compra
									WHERE f.status <> 'ca' AND f.status <> 'b' 
										AND Date(fa.fecha) <= '{$fecha}'{$sql}
									GROUP BY f.id_proveedor
								)
							) AS ffaa
							GROUP BY ffaa.id_proveedor
						) AS faa ON c.id_proveedor = faa.id_proveedor
					WHERE  f.status <> 'ca' AND f.status <> 'b' AND Date(f.fecha) <= '{$fecha}'{$sql}
					GROUP BY c.id_proveedor, c.nombre_fiscal, faa.abonos
				)
			) AS tsaldos
			GROUP BY id_proveedor, nombre_fiscal
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
	public function cuentasPagarPdf(){
		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Cuentas por pagar';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': $this->input->get('ftipo') == 'pp'? 'Pendientes por pagar': 'Todas');
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);
		
		$aligns = array('L', 'R', 'R', 'R');
		$widths = array(100, 35, 35, 35);
		$header = array('Cliente', 'Cargos', 'Abonos', 'Saldo');
		
		$res = $this->getCuentasPagarData(60);

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

	public function cuentasPagarExcel(){
		$res = $this->getCuentasPagarData(60);
		
		$this->load->library('myexcel');
		$xls = new myexcel();
	
		$worksheet =& $xls->workbook->addWorksheet();
	
		$xls->titulo2 = 'Cuentas por pagar';
		$xls->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$xls->titulo4 = ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': $this->input->get('ftipo') == 'pp'? 'Pendientes por pagar': 'Todas');
		
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
			$_GET['id_proveedor'] = $cuenta->id_proveedor;
			$this->cuentaProveedorExcel($xls, false);	
		}
	
		$xls->workbook->send('cuentas_pagar.xls');
		$xls->workbook->close();
	}

	
	/**
	 * 	CUENTAS
	 * ***************************************
	 * Saldo de un cliente seleccionado
	 * @return [type] [description]
	 */
	public function getCuentaProveedorData()
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
				id_proveedor,
				Sum(total) AS total,
				Sum(iva) AS iva, 
				Sum(abonos) AS abonos, 
				Sum(saldo)::numeric(12, 2) AS saldo,
				tipo
			FROM 
				(
					SELECT 
						c.id_proveedor,
						c.nombre_fiscal,
						Sum(f.total) AS total,
						Sum(f.importe_iva) AS iva, 
						COALESCE(Sum(faa.abonos),0) as abonos, 
						COALESCE(Sum(f.total) - COALESCE(Sum(faa.abonos),0), 0) AS saldo,
						'f' as tipo
					FROM
						proveedores AS c
						INNER JOIN compras AS f ON c.id_proveedor = f.id_proveedor
						LEFT JOIN (
							(
								SELECT 
									f.id_proveedor,
									f.id_compra,
									Sum(fa.total) AS abonos
								FROM
									compras AS f 
										INNER JOIN compras_abonos AS fa ON f.id_compra = fa.id_compra
								WHERE f.status <> 'ca' 
									AND f.id_proveedor = '{$_GET['id_proveedor']}' 
									AND Date(fa.fecha) <= '{$fecha2}'{$sql}
								GROUP BY f.id_proveedor, f.id_compra
							)
						) AS faa ON f.id_proveedor = faa.id_proveedor AND f.id_compra = faa.id_compra
					WHERE c.id_proveedor = '{$_GET['id_proveedor']}' AND f.status <> 'ca' AND f.status <> 'b'
						AND Date(f.fecha) < '{$fecha1}'{$sql}
					GROUP BY c.id_proveedor, c.nombre_fiscal, faa.abonos, tipo

				) AS sal
			{$sql2}
			GROUP BY id_proveedor, tipo
		");
		
		/*** Facturas y ventas en el rango de fechas ***/
		$res = $this->db->query(
			"SELECT
				f.id_compra, 
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
				('Factura ' || f.serie || f.folio) AS concepto,
				'f' as tipo
			FROM
				compras AS f
				LEFT JOIN (
					SELECT id_compra, Sum(abono) AS abono
					FROM (
						(
							SELECT 
								id_compra,
								Sum(total) AS abono
							FROM
								compras_abonos as fa
							WHERE Date(fecha) <= '{$fecha2}'
							GROUP BY id_compra
						)
					) AS ffs
					GROUP BY id_compra
				) AS ac ON f.id_compra = ac.id_compra {$sql}
			WHERE f.id_proveedor = {$_GET['id_proveedor']} 
				AND f.status <> 'ca' 
				AND (Date(f.fecha) >= '{$fecha1}' AND Date(f.fecha) <= '{$fecha2}')
				{$sql}

			ORDER BY fecha ASC, serie ASC, folio ASC
			");
		
		
		//obtenemos la info del proveedor
		$this->load->model('proveedores_model');
		$prov = $this->proveedores_model->getProveedorInfo($_GET['id_proveedor'], true);
		
		$response = array(
			'cuentas'   => array(),
			'anterior'  => array(),
			'proveedor' => $prov['info'],
			'fecha1'    => $fecha1
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
	public function cuentaProveedorPdf(){
		$res = $this->getCuentaProveedorData();
		
		if (count($res['anterior']) > 0)
			$res['anterior'] = $res['anterior'][0];

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Cuenta de '.$res['proveedor']->nombre_fiscal;
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
	
	public function cuentaProveedorExcel(&$xls=null, $close=true){
		$res = $this->getCuentaProveedorData();
		
		if (count($res['anterior']) > 0)
			$res['anterior'] = $res['anterior'][0];
		
		$this->load->library('myexcel');
		if($xls == null)
			$xls = new myexcel();
	
		$worksheet =& $xls->workbook->addWorksheet();
	
		$xls->titulo2 = 'Cuenta de '.$res['proveedor']->nombre_fiscal;
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
			$xls->workbook->send('cuentaProveedor.xls');
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
											"SELECT id_compra AS id, DATE(fecha) as fecha, serie, folio, condicion_pago, status, total,
												plazo_credito, id_proveedor, 'f' AS tipo 
												FROM compras
												WHERE id_compra={$_GET['id']}")->result();
			$sql = array('tabla' => 'compras_abonos', 
										'where_field' => 'id_compra');
		// }
		// else
		// {
			// $data['info'] = $this->db->query(
			// 								"SELECT id_venta AS id, fecha, '' as serie, folio, 	
			// 									condicion_pago,
			// 									status, total, 
			// 									plazo_credito, id_proveedor, 'v' AS tipo
			// 								FROM facturacion_ventas_remision
			// 								WHERE id_venta={$_GET['id']}")->result();
			// $sql = array('tabla' => 'facturacion_ventas_remision_abonos', 
			// 							'where_field' => 'id_venta');
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
				) AS tt
					ORDER BY fecha ASC
			");	
	
		//obtenemos la info del proveedor
		$prov['info'] = '';
		if (isset($data['info'][0]->id_proveedor)) 
		{
			$this->load->model('proveedores_model');
			$prov = $this->proveedores_model->getProveedorInfo($data['info'][0]->id_proveedor, true);
		}
	
		$response = array(
				'abonos'    => array(),
				'saldo'     => '',
				'cobro'     => $data['info'],
				'proveedor' => $prov['info'],
				'fecha1'    => $fecha1
		);
		$abonos = 0;
		if($res->num_rows() > 0){
			$response['abonos'] = $res->result();

			foreach ($response['abonos'] as $key => $value) {
				$abonos += $value->abono;
			}
		}
		$response['saldo'] = $response['cobro'][0]->total - $abonos;
	
		return $response;
	}


	/**
	 * Abonos de facturas y ventas
	 * ************************************
	 */
	public function addAbonoMasivo()
	{
		$ids   = $_POST['ids']; //explode(',', substr($_POST['ids'], 1));
		$tipos = $_POST['tipos']; //explode(',', substr($_POST['tipos'], 1));
		$total = 0; //$this->input->post('dmonto');
		$desc  = '';
		
		//Se registra el movimiento en la cuenta bancaria
		$this->load->model('banco_cuentas_model');
		$data_cuenta  = $this->banco_cuentas_model->getCuentaInfo($this->input->post('dcuenta'));
		$data_cuenta  = $data_cuenta['info'];
		$_GET['id']   = $ids[0];
		$_GET['tipo'] = $tipos[0];
		$inf_factura  = $this->cuentas_pagar_model->getDetalleVentaFacturaData($_GET['id']);
		//Registra deposito
		foreach ($_POST['ids'] as $key => $value)  //foreach ($ids as $key => $value) 
		{
			$total += $_POST['montofv'][$key];
			$desc .= '|'.$_POST['factura_desc'][$key].'=>'.String::formatoNumero($_POST['montofv'][$key], 2, '', false);
		}
		$desc = ' ('.substr($desc, 1).')';
		$resp = $this->banco_cuentas_model->addRetiro(array(
					'id_cuenta'    => $this->input->post('dcuenta'),
					'id_banco'     => $data_cuenta->id_banco,
					'fecha'        => $this->input->post('dfecha'),
					'numero_ref'   => $this->input->post('dreferencia'),
					'concepto'     => $this->input->post('dconcepto').$desc,
					'monto'        => $total,
					'tipo'         => 'f',
					'entransito'   => 't',
					'metodo_pago'  => $this->input->post('fmetodo_pago'),
					'id_proveedor' => $inf_factura['proveedor']->id_proveedor,
					'a_nombre_de'  => $inf_factura['proveedor']->nombre_fiscal,
					'id_cuenta_proveedor' => ($this->input->post('fcuentas_proveedor')!=''? $this->input->post('fcuentas_proveedor'): NULL),
					'clasificacion'       => ($this->input->post('fmetodo_pago')=='cheque'? 'echeque': 'egasto'),
					));

		if ($resp['error'] == false)
		{
			foreach ($_POST['ids'] as $key => $value)  //foreach ($ids as $key => $value) 
			{
				$_GET['id']   = $value;
				$_GET['tipo'] = $_POST['tipos'][$key];
				$data = array('fecha'        => $this->input->post('dfecha'),
							'concepto'       => $this->input->post('dconcepto'),
							'total'          => $_POST['montofv'][$key], //$total,
							'id_cuenta'      => $this->input->post('dcuenta'),
							'ref_movimiento' => $this->input->post('dreferencia') );
				$resa = $this->addAbono($data, null, true);
				$total -= $resa['total'];

				//Registra el rastro de la compra que se abono en bancos
				if(isset($resp['id_movimiento']))
				{
					if($_POST['tipos'][$key] == 'f') //factura
						$this->db->insert('banco_movimientos_compras', array('id_movimiento' => $resp['id_movimiento'], 'id_compra_abono' => $resa['id_abono']));
				}
			}
		}

		return $resp;
	}

	public function addAbono($data=null, $id=null, $masivo=false)
	{
		$id = $id==null? $this->input->get('id') : $id; //id factura o nota de venta

		if ($this->input->get('tipo') == 'f') {
			$camps = array('id_compra', 'compras_abonos', 'compras');
		}else{
			// $camps = array('id_venta', 'facturacion_ventas_remision_abonos', 'facturacion_ventas_remision');
		}

		if ($data == null) {
			$data = array('fecha'        => $this->input->post('dfecha'),
						'concepto'       => $this->input->post('dconcepto'),
						'total'          => $this->input->post('dmonto'),
						'id_cuenta'      => $this->input->post('dcuenta'),
						'ref_movimiento' => $this->input->post('dreferencia'),
						'id_cuenta_proveedor' => ($this->input->post('fcuentas_proveedor')!=''? $this->input->post('fcuentas_proveedor'): NULL) );
		}

		$pagada = false;
		$inf_factura = $this->cuentas_pagar_model->getDetalleVentaFacturaData($id);
		if ($inf_factura['saldo'] <= $data['total']){ //se ajusta
			$data['total'] -= $data['total']-$inf_factura['saldo'];
			$pagada = true;
		}

		//Se registra el movimiento en la cuenta bancaria
		if ($masivo == false) 
		{
			$this->load->model('banco_cuentas_model');
			$data_cuenta  = $this->banco_cuentas_model->getCuentaInfo($data['id_cuenta']);
			$data_cuenta  = $data_cuenta['info'];

			$data['concepto'] .= ' ('.$inf_factura['cobro'][0]->serie.$inf_factura['cobro'][0]->folio.'=>'.String::formatoNumero($data['total'], 2, '', false).')';
			$resp = $this->banco_cuentas_model->addRetiro(array(
						'id_cuenta'    => $data['id_cuenta'],
						'id_banco'     => $data_cuenta->id_banco,
						'fecha'        => $data['fecha'],
						'numero_ref'   => $data['ref_movimiento'],
						'concepto'     => $data['concepto'],
						'monto'        => $data['total'],
						'tipo'         => 'f',
						'entransito'   => 't',
						'metodo_pago'  => $this->input->post('fmetodo_pago'),
						'id_proveedor' => $inf_factura['proveedor']->id_proveedor,
						'a_nombre_de'  => $inf_factura['proveedor']->nombre_fiscal,
						'id_cuenta_proveedor' => ($data['id_cuenta_proveedor']!=''? $data['id_cuenta_proveedor']: NULL),
						'clasificacion'       => ($this->input->post('fmetodo_pago')=='cheque'? 'echeque': 'egasto'),
						));
			//No hay saldo
			if($resp['error'])
				return $resp;
		}

		$data = array(
			$camps[0]        => $id, 
			'fecha'          => $data['fecha'],
			'concepto'       => $data['concepto'],
			'total'          => $data['total'],
			'id_cuenta'      => $data['id_cuenta'],
			'ref_movimiento' => $data['ref_movimiento'], );
		//se inserta el abono
		$this->db->insert($camps[1], $data);
		$data['id_abono'] = $this->db->insert_id($camps[1], 'id_abono');

		//verifica si la factura se pago, se cambia el status
		if($pagada){
			$this->db->update($camps[2], array('status' => 'pa'), "{$camps[0]} = {$id}");
		}

		//Registra el rastro de la factura o remision que se abono en bancos (si no es masivo)
		if(isset($resp['id_movimiento']))
		{
			if($camps[0] == 'id_compra') //factura
				$this->db->insert('banco_movimientos_compras', array('id_movimiento' => $resp['id_movimiento'], 'id_compra_abono' => $data['id_abono']));
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

		$camps = array('id_abono', 'compras_abonos', 'compras', 'id_compra');
		$this->db->delete($camps[1], "{$camps[0]} = {$ida}");
		//Se cambia el estado de la factura
		$this->db->update($camps[2], array('status' => 'p'), "{$camps[3]} = {$id}");

		return true;
	}

}