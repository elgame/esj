<?php
class inventario_model extends privilegios_model{

	function __construct(){
		parent::__construct();
	}


	/**
	 * Reporte existencias por unidad
   *
   * @return
	 */
	public function getEPUData()
  {
		$sql = '';

		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

		if(is_array($this->input->get('ffamilias'))){
			$sql .= " AND pf.id_familia IN (".implode(',', $this->input->get('ffamilias')).")";
		}
		if($this->input->get('fid_producto') != ''){
			$sql .= " AND p.id_producto = ".$this->input->get('fid_producto');
		}
		$this->load->model('empresas_model');
		$client_default = $this->empresas_model->getDefaultEmpresa();
		$_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
		$_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
	    if($this->input->get('did_empresa') != ''){
	      $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
	    }

		$res = $this->db->query(
			"SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura, COALESCE(co.cantidad, 0) AS entradas, COALESCE(sa.cantidad, 0) AS salidas
			FROM productos AS p 
			INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
			INNER JOIN productos_unidades AS pu ON pu.id_unidad = p.id_unidad
			LEFT JOIN 
			(
				SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
				FROM compras_ordenes AS co 
				INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden 
				WHERE co.status IN ('a','f','n') AND co.tipo_orden = 'p' AND Date(co.fecha_aceptacion) <= '{$fecha}'
				GROUP BY cp.id_producto 
			) AS co ON co.id_producto = p.id_producto
			LEFT JOIN 
			(
				SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
				FROM compras_salidas AS sa 
				INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida 
				WHERE sa.status <> 'ca' AND Date(sa.fecha_registro) <= '{$fecha}'
				GROUP BY sp.id_producto 
			) AS sa ON sa.id_producto = p.id_producto
			WHERE p.status='ac' AND pf.status='ac' AND pf.tipo = 'p' {$sql}
			ORDER BY nombre, nombre_producto ASC
			");
		
		$response = array();
		if($res->num_rows() > 0)
			$response = $res->result();

		return $response;
	}
	/**
	 * Reporte existencias por unidad pdf
	 */
	public function getEPUPdf(){
		$res = $this->getEPUData();

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Existencia por unidades';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);
		
		$aligns = array('L', 'R', 'R', 'R');
		$widths = array(100, 35, 35, 35);
		$header = array('Producto', 'Entradas', 'Salidas', 'Existencia');

		$familia = '';
		$total_cargos = $total_abonos = $total_saldo = 0;
		foreach($res as $key => $item){
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();
				
				if ($key == 0)
				{
					$pdf->SetFont('Arial','B',11);
					$pdf->SetX(6);
					$pdf->SetAligns($aligns);
					$pdf->SetWidths(array(150));
					$pdf->Row(array($item->nombre), false, false);
					$familia = $item->nombre;
				}

				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}

			if ($familia <> $item->nombre)
			{
				$pdf->SetFont('Arial','B',11);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths(array(150));
				$pdf->Row(array($item->nombre), false, false);
				$familia = $item->nombre;
			}
			
			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);
			$datos = array($item->nombre_producto.' ('.$item->abreviatura.')', 
				String::formatoNumero($item->entradas, 2, '', false),
				String::formatoNumero($item->salidas, 2, '', false),
				String::formatoNumero(($item->entradas-$item->salidas), 2, '', false),
				);
			
			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}
		
		$pdf->Output('epu.pdf', 'I');
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
	 * Reporte de existencias por costo
	 * @return [type] [description]
	 */
	public function getEPCData()
	{
		$sql = '';

		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

		if(is_array($this->input->get('ffamilias'))){
			$sql .= " AND pf.id_familia IN (".implode(',', $this->input->get('ffamilias')).")";
		}
		if($this->input->get('fid_producto') != ''){
			$sql .= " AND p.id_producto = ".$this->input->get('fid_producto');
		}
		$this->load->model('empresas_model');
		$client_default = $this->empresas_model->getDefaultEmpresa();
		$_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
		$_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
	    if($this->input->get('did_empresa') != ''){
	      $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
	    }

		$res = $this->db->query(
			"SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura
			FROM productos AS p 
				INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
				INNER JOIN productos_unidades AS pu ON pu.id_unidad = p.id_unidad
			WHERE p.status='ac' AND pf.status='ac' AND pf.tipo = 'p' {$sql}
			ORDER BY nombre, nombre_producto ASC
			");
		
		$response = array();
		if($res->num_rows() > 0)
		{
			$response = $res->result();
			foreach ($response as $key => $value)
			{
				$data = $this->promedioData($value->id_producto, $_GET['ffecha1'], $fecha);
				$value->data = array_pop($data);
				$response[$key] = $value;
			}
		}

		return $response;
	}
	/**
	 * Reporte existencias por costo pdf
	 */
	public function getEPCPdf()
	{
		$res = $this->getEPCData();

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Existencia por unidades';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);
		
		$aligns = array('L', 'R', 'R', 'R');
		$widths = array(100, 35, 35, 35);
		$header = array('Producto', 'Entradas', 'Salidas', 'Existencia');

		$familia = '';
		$total_cargos = $total_abonos = $total_saldo = 0;
		foreach($res as $key => $item){
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();
				
				if ($key == 0)
				{
					$pdf->SetFont('Arial','B',11);
					$pdf->SetX(6);
					$pdf->SetAligns($aligns);
					$pdf->SetWidths(array(150));
					$pdf->Row(array($item->nombre), false, false);
					$familia = $item->nombre;
				}

				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}

			if ($familia <> $item->nombre)
			{
				$pdf->SetFont('Arial','B',11);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths(array(150));
				$pdf->Row(array($item->nombre), false, false);
				$familia = $item->nombre;
			}
			
			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);
			$datos = array($item->nombre_producto.' ('.$item->abreviatura.')', 
				String::formatoNumero($item->data['entrada'][2], 2, '$', false),
				String::formatoNumero($item->data['salida'][2], 2, '$', false),
				String::formatoNumero(($item->data['saldo'][2]), 2, '$', false),
				);
			
			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->SetMyLinks(array(base_url('panel/inventario/promedio_pdf?id_producto='.$item->id_producto.'&ffecha1='.
								$this->input->get('ffecha1').'&ffecha2='.$this->input->get('ffecha2'))));
			$pdf->Row($datos, false);

			$pdf->SetMyLinks(array());
		}
		
		$pdf->Output('epc.pdf', 'I');
	}

	/**
	 * Reporte costos promedio productos
	 * @param  [type] $id_producto [description]
	 * @param  [type] $fecha1      [description]
	 * @param  [type] $fecha2      [description]
	 * @return [type]              [description]
	 */
	public function promedioData($id_producto, $fecha1, $fecha2)
	{
		//saldo anterior
		// $saldo = $this->db->query(
		// "SELECT c.id_producto, COALESCE(c.cantidad, 0) AS entradas, COALESCE(c.importe, 0) AS importe, COALESCE(s.cantidad, 0) AS salidas
		// FROM 
		// 	(
		// 		SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad, Sum(cp.importe) AS importe, 'c' AS tipo
		// 		FROM compras_ordenes AS co 
		// 		INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden 
		// 		WHERE cp.id_producto = {$id_producto} AND co.status IN ('a','f','n') 
		// 			AND co.tipo_orden = 'p' AND Date(co.fecha_aceptacion) < '{$fecha1}'
		// 		GROUP BY cp.id_producto, tipo
		// 	) AS c
		// 	LEFT JOIN 
		// 	(
		// 		SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad, 0 AS importe, 's' AS tipo
		// 		FROM compras_salidas AS sa 
		// 		INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida 
		// 		WHERE sp.id_producto = {$id_producto} AND sa.status <> 'ca' AND Date(sa.fecha_registro) < '{$fecha1}'
		// 		GROUP BY sp.id_producto, tipo
		// 	) AS s ON s.id_producto = c.id_producto")->row();
		// if (isset($saldo->id_producto))
		// {
		// 	$saldo->pu = $saldo->importe/($saldo->entradas>0? $saldo->entradas: 1);
		// 	$saldo->importe = $saldo->pu * $saldo->entradas;
		// 	$saldo->saldo_cantidad = $saldo->entradas - $saldo->salidas;
		// 	$saldo->saldo_importe = $saldo->saldo_cantidad * $saldo->pu;
		// }else{
		// 	$saldo = new stdClass;
		// 	$saldo->importe = 0;
		// 	$saldo->entradas = 0;
		// 	$saldo->salidas = 0;
		// 	$saldo->saldo_cantidad = 0;
		// 	$saldo->saldo_importe = 0;
		// 	$saldo->pu = 0;
		// }

		$res = $this->db->query(
		"SELECT id_producto, Date(fecha) AS fecha, cantidad, precio_unitario, importe, tipo 
		FROM 
			(
				(
				SELECT cp.id_producto, co.fecha_aceptacion AS fecha, cp.cantidad, cp.precio_unitario, cp.importe, 'c' AS tipo
				FROM compras_ordenes AS co 
				INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden 
				WHERE cp.id_producto = {$id_producto} AND co.status IN ('a','f','n') 
					AND co.tipo_orden = 'p' AND Date(co.fecha_aceptacion) <= '{$fecha2}'
				)
				UNION
				(
				SELECT sp.id_producto, sa.fecha_registro AS fecha, sp.cantidad, sp.precio_unitario, (sp.cantidad * sp.precio_unitario) AS importe, 's' AS tipo
				FROM compras_salidas AS sa 
				INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida 
				WHERE sp.id_producto = {$id_producto} AND sa.status <> 'ca' AND Date(sa.fecha_registro) <= '{$fecha2}'
				)
			) AS t
		ORDER BY fecha ASC");

		$result   = array();
		$result[] = array('fecha' => 'S. Anterior',
						'entrada' => array(0, 0, 0), 
						'salida' => array(0, 0, 0), 
						'saldo' => array(0, 0, 0), );
		foreach ($res->result() as $key => $value)
		{
			$row = array('fecha' => $value->fecha, 'entrada' => array('', '', ''), 'salida' => array('', '', ''), 'saldo' => array(0, 0, 0));
			if ($value->tipo == 'c')
			{
				$row['entrada'][0] = $value->cantidad;
				$row['entrada'][1] = $value->precio_unitario;
				$row['entrada'][2] = $value->cantidad*$value->precio_unitario;

				$row['saldo'][0] = $value->cantidad+$result[$key]['saldo'][0];
				$row['saldo'][2] = $row['entrada'][2]+$result[$key]['saldo'][2];
				$row['saldo'][1] = $row['saldo'][2]/$row['saldo'][0];
			}else
			{
				$row['salida'][0] = $value->cantidad;
				$row['salida'][1] = $result[$key]['saldo'][1];
				$row['salida'][2] = $value->cantidad*$row['salida'][1];

				$row['saldo'][0] = $result[$key]['saldo'][0]-$value->cantidad;
				$row['saldo'][1] = $result[$key]['saldo'][1];
				$row['saldo'][2] = $row['saldo'][0]*$row['saldo'][1];
			}

			$result[] = $row;
		}

		$valkey = $entro = 0;
		foreach ($result as $key => $value)
		{
			if($fecha1 > $value['fecha'])
			{
				$valkey = $key-1;
				unset($result[$valkey]);
				$entro = 1;
			}
		}
		if($entro == 1)
			$result[$valkey+1] = array('fecha' => 'S. Anterior', 'entrada' => array('', '', ''), 'salida' => array('', '', ''), 
						'saldo' => $result[$valkey+1]['saldo'] );

		$keyconta = $entrada_cantidad = $entrada_importe = $salida_cantidad = $salida_importe = 0;
		foreach ($result as $key => $value)
		{
			$entrada_cantidad += $value['entrada'][0];
			$entrada_importe += $value['entrada'][2];
			if ($keyconta > 0)
			{
				$salida_cantidad += $value['salida'][0];
				$salida_importe += $value['salida'][2];
			}else
			{
				$entrada_cantidad += $value['saldo'][0];
				$entrada_importe += $value['saldo'][2];
			}
			$keyconta++;
		}
		$result[] = array('fecha' => 'Totales', 'entrada' => array($entrada_cantidad, '', $entrada_importe), 'salida' => array($salida_cantidad, '', $salida_importe), 
						'saldo' => array(($entrada_cantidad-$salida_cantidad), '',($entrada_importe-$salida_importe)) );

		return $result;
	}

	public function getPromediodf()
	{
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

		$res = $this->promedioData($_GET['id_producto'], $_GET['ffecha1'], $_GET['ffecha2']);

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Reporte de inventario costo promedio';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);
		
		$aligns = array('C', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R');
		$widths = array(20, 18, 18, 26, 18, 18, 26, 18, 18, 26);
		$header = array('Fecha', 'CANT.', 'P.U.', 'P.T.', 'CANT.', 'P.U.', 'P.T.', 'CANT.', 'P.U.', 'P.T.');

		$familia = '';
		$keyconta = 0;
		foreach($res as $key => $item){
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY || $keyconta==0){ //salta de pagina si exede el max
				$pdf->AddPage();

				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(26);
				$pdf->SetAligns(array('C', 'C', 'C'));
				$pdf->SetWidths(array(62, 62, 62));
				$pdf->Row(array('Entradas', 'Salidas', 'Saldo'), true);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}
			
			$keyconta++;

			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);
			$datos = array(
				$item['fecha'],

				$item['entrada'][0]!=''? String::formatoNumero($item['entrada'][0], 2, '', false): $item['entrada'][0],
				$item['entrada'][1]!=''? String::formatoNumero($item['entrada'][1], 2, '$', false): $item['entrada'][1],
				$item['entrada'][2]!=''? String::formatoNumero($item['entrada'][2], 2, '$', false): $item['entrada'][2],

				$item['salida'][0]!=''? String::formatoNumero($item['salida'][0], 2, '', false): $item['salida'][0],
				$item['salida'][1]!=''? String::formatoNumero($item['salida'][1], 2, '$', false): $item['salida'][1],
				$item['salida'][2]!=''? String::formatoNumero($item['salida'][2], 2, '$', false): $item['salida'][2],

				$item['saldo'][0]!=''? String::formatoNumero($item['saldo'][0], 2, '', false): $item['saldo'][0],
				$item['saldo'][1]!=''? String::formatoNumero($item['saldo'][1], 2, '$', false): $item['saldo'][1],
				$item['saldo'][2]!=''? String::formatoNumero($item['saldo'][2], 2, '$', false): $item['saldo'][2],
				);
			
			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}

		$pdf->Output('promedio.pdf', 'I');
	}

}