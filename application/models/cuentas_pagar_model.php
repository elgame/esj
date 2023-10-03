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

		if($this->input->get('fid_proveedor') != ''){
			$sql .= " AND f.id_proveedor = '".$this->input->get('fid_proveedor')."'";
			$sqlt .= " AND f.id_proveedor = '".$this->input->get('fid_proveedor')."'";
		}

    if($this->input->get('ftipodoc') != '') {
      $sql .= " AND f.tipo_documento = '".$this->input->get('ftipodoc')."'";
    }

		$this->load->model('empresas_model');
		$client_default = $this->empresas_model->getDefaultEmpresa();
		$_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
		$_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != '' && $this->input->get('did_empresa') != 'all'){
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
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
								UNION
								(
									SELECT
										f.id_proveedor,
										Sum(f.total) AS abonos
									FROM
										compras AS f
									WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_nc IS NOT NULL
										AND Date(f.fecha) <= '{$fecha}'{$sql}
									GROUP BY f.id_proveedor
								)
							) AS ffaa
							GROUP BY ffaa.id_proveedor
						) AS faa ON c.id_proveedor = faa.id_proveedor
					WHERE  f.status <> 'ca' AND f.status <> 'b' AND f.id_nc IS NULL
						AND Date(f.fecha) <= '{$fecha}'{$sql}
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
		$res = $this->getCuentasPagarData(10000);
    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->logo = $empresa['info']->logo!=''? (file_exists($empresa['info']->logo)? $empresa['info']->logo: '') : '';
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Cuentas por pagar';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': $this->input->get('ftipo') == 'pp'? 'Pendientes por pagar': 'Todas');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'R', 'R');
    $widths = array(100, 35, 35, 35);
    $header = array('Cliente', 'Cargos', 'Abonos', 'Saldo');


		$total_cargos = $total_abonos = $total_saldo = 0;
		foreach($res['cuentas'] as $key => $item){
      if ($item->saldo > 0 || $_GET['ftipo'] == 'to')
      {
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
  				MyString::formatoNumero($item->total, 2, '$', false),
  				MyString::formatoNumero($item->abonos, 2, '$', false),
  				MyString::formatoNumero($item->saldo, 2, '$', false),
  				);
  			$total_cargos += $item->total;
  			$total_abonos += $item->abonos;
  			$total_saldo += $item->saldo;

  			$pdf->SetX(6);
  			$pdf->SetAligns($aligns);
  			$pdf->SetWidths($widths);
  			$pdf->Row($datos, false);
      }
		}

		$pdf->SetX(6);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(255,255,255);
		$pdf->Row(array('Total:',
			MyString::formatoNumero($total_cargos, 2, '$', false),
			MyString::formatoNumero($total_abonos, 2, '$', false),
			MyString::formatoNumero($total_saldo, 2, '$', false),
			), true);

		$pdf->Output('cuentas_x_cobrar.pdf', 'I');
	}

	public function cuentasPagarExcel(){
		$res = $this->getCuentasPagarData(1000);

		$this->load->library('myexcel');
		$xls = new myexcel();

		$worksheet =& $xls->workbook->addWorksheet();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $xls->titulo1 = $empresa['info']->nombre_fiscal;
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

    foreach ($data_fac as $key => $value)
    {
      if ($value->saldo == 0)
        unset($data_fac[$key]);
    }

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
	public function getCuentaProveedorData($otros = null)
	{
		$sqlp1 = $sqlp2 = $sqlp3 = $sql = '';

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
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

		$sql = $sqlt = $sql2 = '';
		if($this->input->get('ftipo')=='pv'){
			$sql = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito";
			$sqlt = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito";
			$sql2 = 'WHERE saldo > 0';
		}

    $sql_only_sel_table = $sql_only_sel_where = $sql_only_sel_order = $sql_only_sel_fiels = '';

    if($this->input->get('did_empresa') != ''){
      if (isset($otros['all_empresas']) && $otros['all_empresas']) {
        $sql_only_sel_order .= ' id_empresa ASC,';
      } else {
        $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
        $sqlt .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
      }
    }

    if($this->input->get('id_proveedor') != ''){
      $sqlp1 = " AND f.id_proveedor = '".$this->input->get('id_proveedor')."'";
      $sqlp2 = " AND c.id_proveedor = '".$this->input->get('id_proveedor')."'";
      $sqlp3 = " AND id_proveedor = '".$this->input->get('id_proveedor')."'";
    }

    if($this->input->get('ftipodoc') != '') {
      $sql .= " AND f.tipo_documento = '".$this->input->get('ftipodoc')."'";
    }

    if (isset($otros['only_select'])) { // solo los seleccionados para pago masivo
      $sql_only_sel_table = " INNER JOIN banco_pagos_compras bpc ON f.id_compra = bpc.id_compra
        LEFT JOIN (
          SELECT
              cf.id_compra,
              ('Ordenes: ' || String_agg(co.folio::character varying, ', ')) AS ordenes
          FROM compras_ordenes co
            INNER JOIN compras_facturas as cf ON co.id_orden = cf.id_orden
          GROUP BY cf.id_compra
        ) ord ON f.id_compra = ord.id_compra";
      $sql_only_sel_where = " AND bpc.status = 'f'";
      $sql_only_sel_order .= ' proveedor ASC,';
      $sql_only_sel_fiels = ', ord.ordenes';
    }

    if (!(isset($otros['all_empresas']) && $otros['all_empresas'])) {
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
              'f'::text as tipo
            FROM
              proveedores AS c
              INNER JOIN compras AS f ON c.id_proveedor = f.id_proveedor
              LEFT JOIN (
                SELECT
                  d.id_proveedor,
                  d.id_compra,
                  Sum(d.abonos) AS abonos
                FROM
                (
                  SELECT
                    f.id_proveedor,
                    f.id_compra,
                    Sum(fa.total) AS abonos
                  FROM
                    compras AS f
                      INNER JOIN compras_abonos AS fa ON f.id_compra = fa.id_compra
                  WHERE f.status <> 'ca' AND f.status <> 'b'
                    {$sqlp1}
                    AND Date(fa.fecha) <= '{$fecha2}'{$sql}
                  GROUP BY f.id_proveedor, f.id_compra

                  UNION

                  SELECT
                    f.id_proveedor,
                    f.id_nc AS id_compra,
                    Sum(f.total) AS abonos
                  FROM
                    compras AS f
                  WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_nc IS NOT NULL
                    {$sqlp1}
                    AND Date(f.fecha) <= '{$fecha2}'{$sql}
                  GROUP BY f.id_proveedor, f.id_compra
                ) AS d
                GROUP BY d.id_proveedor, d.id_compra
              ) AS faa ON f.id_proveedor = faa.id_proveedor AND f.id_compra = faa.id_compra
            WHERE f.status <> 'ca' AND f.status <> 'b' {$sqlp2}
               AND id_nc IS NULL
               AND Date(f.fecha) < '{$fecha1}'
               {$sql}
            GROUP BY c.id_proveedor, c.nombre_fiscal, faa.abonos, tipo
          ) AS sal
        {$sql2}
        GROUP BY id_proveedor, tipo
        ");
    }

		/*** Facturas y ventas en el rango de fechas ***/
		$res = $this->db->query(
			"SELECT
				f.id_compra,
				f.serie,
				f.folio,
        Date(f.fecha) AS fecha,
				Date(f.fecha_factura) AS fecha_factura,
				COALESCE(f.total, 0) AS cargo,
				COALESCE(f.importe_iva, 0) AS iva,
				COALESCE(ac.abono, 0) AS abono,
				(COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(100,2) AS saldo,
				(CASE (COALESCE(f.total, 0) - COALESCE(ac.abono, 0)) WHEN 0 THEN 'Pagada' ELSE 'Pendiente' END) AS estado,
				Date(f.fecha_factura + (f.plazo_credito || ' days')::interval) AS fecha_vencimiento,
				(Date('{$fecha2}'::timestamp with time zone)-Date(f.fecha_factura)) AS dias_transc,
				('Factura ' || f.serie || f.folio) AS concepto, f.concepto AS concepto2,
				'f'::text as tipo, f.status,
        COALESCE((SELECT id_pago FROM banco_pagos_compras WHERE status = 'f' AND id_compra = f.id_compra), 0) AS en_pago,
        p.nombre_fiscal AS proveedor,
        e.id_empresa, e.nombre_fiscal AS empresa {$sql_only_sel_fiels}
			FROM
				compras AS f
        {$sql_only_sel_table}
				LEFT JOIN (
					SELECT id_compra, Sum(abono) AS abono
					FROM (
						(
							SELECT
								id_compra,
								Sum(total) AS abono,
                'a' AS tipo
							FROM
								compras_abonos as fa
							WHERE Date(fecha) <= '{$fecha2}'
							GROUP BY id_compra
						)
						UNION
						(
							SELECT
								id_nc AS id_compra,
								Sum(total) AS abonos,
                'nc' AS tipo
							FROM
								compras
							WHERE status <> 'ca' AND status <> 'b' AND id_nc IS NOT NULL
								{$sqlp3}
								AND Date(fecha) <= '{$fecha2}'
							GROUP BY id_nc
						)
					) AS ffs
					GROUP BY id_compra
				) AS ac ON f.id_compra = ac.id_compra {$sql}
        LEFT JOIN proveedores p ON p.id_proveedor = f.id_proveedor
        LEFT JOIN empresas e ON e.id_empresa = f.id_empresa
			WHERE f.status <> 'ca' AND f.id_nc IS NULL
        {$sqlp1}
				AND (Date(f.fecha) >= '{$fecha1}' AND Date(f.fecha) <= '{$fecha2}')
				{$sql}
        {$sql_only_sel_where}

			ORDER BY {$sql_only_sel_order} fecha ASC, serie ASC, folio ASC
			");


		//obtenemos la info del proveedor
    $prov = null;
		if (!empty($_GET['id_proveedor'])) {
      $this->load->model('proveedores_model');
      $prov = $this->proveedores_model->getProveedorInfo($_GET['id_proveedor'], true);
      $prov = $prov['info'];
    }

		$response = array(
			'cuentas'   => array(),
			'anterior'  => array(),
			'proveedor' => $prov,
			'fecha1'    => $fecha1
		);
		if($res->num_rows() > 0){
			$response['cuentas'] = $res->result();

			//verifica q no sea negativo o exponencial el saldo
			foreach ($response['cuentas'] as $key => $cuenta) {
				$cuenta->saldo = floatval(MyString::float($cuenta->saldo));
				if($cuenta->saldo == 0){
					$cuenta->estado = 'Pagada';
					$cuenta->fecha_vencimiento = $cuenta->dias_transc = '';

					if($this->input->get('ftipo')=='pv' || $this->input->get('ftipo')=='pp')
						unset($response['cuentas'][$key]);
				}
			}
		}

    if (!(isset($otros['all_empresas']) && $otros['all_empresas'])) {
  		if($saldo_anterior->num_rows() > 0) {
  			$response['anterior'] = $saldo_anterior->result();
  			foreach ($response['anterior'] as $key => $c) {
  				if ($key > 0){
  					$response['anterior'][0]->total += $c->total;
  					$response['anterior'][0]->abonos += $c->abonos;
  					$response['anterior'][0]->saldo += $c->saldo;
  				}
  			}
  		}
    }

		return $response;
	}

	public function getInfoOrdenesFlete($ids_ordenes)
	{
		$result = $this->db->query("SELECT co.id_orden,
										replace(substr(replace(co.ids_facrem, 't:', ''), 1, length(replace(co.ids_facrem, 't:', ''))-1), '|', ',') AS ids_facrem
									FROM compras_ordenes co INNER JOIN compras_facturas cf ON co.id_orden = cf.id_orden
									WHERE co.tipo_orden = 'f' AND co.status = 'f' AND cf.id_compra in({$ids_ordenes}) ORDER BY co.id_orden ASC");
		$response = $result->result();
		foreach ($response as $key => $orden) {
			$orden->facturas = $this->db->query("SELECT string_agg(f.serie || f.folio, ',') AS facturas, c.nombre_fiscal
					FROM facturacion f INNER JOIN clientes c ON c.id_cliente = f.id_cliente WHERE f.id_factura in({$orden->ids_facrem})
					GROUP BY c.id_cliente")->row();
			$orden->productos = $this->db->query("SELECT id_producto, descripcion, cantidad, precio_unitario, importe, iva, retencion_iva, total
							FROM compras_productos WHERE id_orden = {$orden->id_orden} ORDER BY id_orden ASC")->result();
		}

		return $response;
	}

	/**
	 * Descarga el listado de cuentas por pagar en formato pdf
	 */
	public function cuentaProveedorPdf(){
		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');

    $res = $this->getCuentaProveedorData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $pdf->logo = $empresa['info']->logo!=''? (file_exists($empresa['info']->logo)? $empresa['info']->logo: '') : '';
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    if (count($res['anterior']) > 0)
      $res['anterior'] = $res['anterior'][0];

		$pdf->titulo2 = 'Cuenta de '.$res['proveedor']->nombre_fiscal;
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por pagar');
		$pdf->AliasNbPages();
		//$pdf->AddPage();

		$response = $this->cuentaProveedorCurpPdf($pdf, $res);

		// si tiene ordenes de flete carga los productos y facturas de las ordenes
		if (count($response[0]) > 0) {
			$datos_fletes = $this->getInfoOrdenesFlete(implode(',', $response[0]));
			if (count($datos_fletes) > 0) {
				$pdf->SetXY(6, $pdf->GetY()+10);
	      $pdf->SetFont('Arial','B', 10);
	      $pdf->SetTextColor(0,0,0);
	      $pdf->SetAligns(array('L'));
	      $pdf->SetWidths(array(205));
	      $pdf->Row(array('Ordenes de flete'), false, false);
	      $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
	      $pdf->SetWidths(array(27, 60, 30, 22.5, 18, 18, 22.5));
	      $pdf->SetFont('Arial','B', 8);
	      $pdf->Row(array('Facturas', 'Cliente', 'Cantidad', 'Importe', 'IVA', 'Ret IVA', 'Total'), false);

	      $pdf->SetFont('Arial','', 8);
	      $aux_clasif = 0;
	      $total_iva = $total_retiva = $total_importe = $total_total = $total_cantidad = 0;
	      foreach ($datos_fletes as $key => $orden)
	      {
	        // $pdf->SetAligns(array('L', 'L', 'L', 'R'));
	        // $pdf->SetWidths(array(23, 23, 120, 30));
	        $pdf->Row(array(
	          $orden->facturas->facturas,
	          $orden->facturas->nombre_fiscal,
	          (count($orden->productos)>0? $orden->productos[0]->cantidad.' '.$orden->productos[0]->descripcion: ''),
	          (count($orden->productos)>0? MyString::formatoNumero($orden->productos[0]->importe, 2, '$', false): ''),
	          (count($orden->productos)>0? MyString::formatoNumero($orden->productos[0]->iva, 2, '$', false): ''),
	          (count($orden->productos)>0? MyString::formatoNumero($orden->productos[0]->retencion_iva, 2, '$', false): ''),
	          (count($orden->productos)>0? MyString::formatoNumero($orden->productos[0]->total, 2, '$', false): ''),
	          ), false);
					$total_cantidad += (count($orden->productos)>0? $orden->productos[0]->cantidad: 0);
					$total_importe  += (count($orden->productos)>0? $orden->productos[0]->importe: 0);
					$total_iva      += (count($orden->productos)>0? $orden->productos[0]->iva: 0);
					$total_retiva   += (count($orden->productos)>0? $orden->productos[0]->retencion_iva: 0);
					$total_total    += (count($orden->productos)>0? $orden->productos[0]->total: 0);

					if (count($orden->productos)>1) {
						for ($i=1; $i < count($orden->productos); $i++) {
							$pdf->Row(array(
			          '',
			          '',
			          $orden->productos[$i]->cantidad.' '.$orden->productos[$i]->descripcion,
			          MyString::formatoNumero($orden->productos[$i]->importe, 2, '$', false),
			          MyString::formatoNumero($orden->productos[$i]->iva, 2, '$', false),
			          MyString::formatoNumero($orden->productos[$i]->retencion_iva, 2, '$', false),
			          MyString::formatoNumero($orden->productos[$i]->total, 2, '$', false),
			          ), false);
							$total_cantidad += $orden->productos[$i]->cantidad;
							$total_importe  += $orden->productos[$i]->importe;
							$total_iva      += $orden->productos[$i]->iva;
							$total_retiva   += $orden->productos[$i]->retencion_iva;
							$total_total    += $orden->productos[$i]->total;
						}
					}
	      }
	      $pdf->Row(array(
	          'Total',
	          '',
	          $total_cantidad,
	          MyString::formatoNumero($total_importe, 2, '$', false),
	          MyString::formatoNumero($total_iva, 2, '$', false),
	          MyString::formatoNumero($total_retiva, 2, '$', false),
	          MyString::formatoNumero($total_total, 2, '$', false),
	        ), false);
			}
		}

		//si es NORMATIVIDAD, NORMEX
		if ($_GET['id_proveedor'] == 3297 || $_GET['id_proveedor'] == 807) {
			$aux = $_GET['id_proveedor'];

			$_GET['id_proveedor'] = 147; // sagarpa
			$res = $this->getCuentaProveedorData();
			$response2 = $this->cuentaProveedorCurpPdf($pdf, $res, false, $response[1]);

			$_GET['id_proveedor'] = $aux;

			// $pdf->SetFont('Arial','B',10);
			// $pdf->SetTextColor(0,0,0);
			// $pdf->SetXY(6, $pdf->GetY()+2);
			// $pdf->SetAligns(array('L'));
			// $pdf->SetWidths(array(150));
			// $pdf->Row(array('Nacional: '.MyString::formatoNumero($response2[2]['nacional'], 2, '$', false)), false, false);
			// $pdf->SetXY(6, $pdf->GetY());
			// $pdf->Row(array('Internacional: '.MyString::formatoNumero($response2[2]['internacional'], 2, '$', false)), false, false);
		}
		// si es normex y sagarpa
    // Productos ligados de facturacion (Certificados de origen, filtro sanitario, seguros, etc)
    if($pdf->GetY()+11 >= $pdf->limiteY)
      $pdf->AddPage();
    $this->load->model('gastos_model');
    $fac_ligados = array();
    $total_producto = $totla_general = $iva_general = 0;
    if(count($response[0]) > 0){
      $fac_ligados = $this->gastos_model->getFacturasLigadas(array('idc' => $response[0]), true);

      if (count($fac_ligados['ligadas']) > 0 || count($fac_ligados['ligadas']) > 0) {
	      $pdf->SetXY(6, $pdf->GetY()+10);
	      $pdf->SetFont('Arial','B', 10);
	      $pdf->SetTextColor(0,0,0);
	      $pdf->SetAligns(array('L'));
	      $pdf->SetWidths(array(205));
	      $pdf->Row(array('Productos Facturados'), false, false);
	      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'R'));
	      $pdf->SetWidths(array(18, 23, 80, 25, 25, 25));
	      $pdf->SetFont('Arial','B', 8);
	      $pdf->Row(array('Fecha', 'Folio', 'Cliente', 'Pol/Cer', '# operacion', 'Importe'), false);

	      $pdf->SetFont('Arial','', 8);
	      $aux_clasif = 0;
	      $total_producto = $totla_general = $iva_general = 0;
	      foreach ($fac_ligados['ligadas'] as $key => $value)
	      {
	        if($value->id_clasificacion != $aux_clasif)
	        {
	          if($key > 0){
	            $pdf->SetAligns(array('R', 'R'));
	            $pdf->SetWidths(array(171, 25));
	            $pdf->Row(array('Total', MyString::formatoNumero($total_producto, 2, '$', false)), false);
	          }

	          $pdf->SetAligns(array('L'));
	          $pdf->SetWidths(array(205));
	          $pdf->Row(array($value->nombre), false, false);
	          $aux_clasif = $value->id_clasificacion;
	          $total_producto = 0;
	        }

	        $otro_dato = '';
	        if($value->id_clasificacion == '49'){
	          $otro_dato = $value->pol_seg;
	        }
	        elseif(($value->id_clasificacion == '51' || $value->id_clasificacion == '52')){
	          $otro_dato = $value->certificado;
	        }
	        elseif($value->id_clasificacion == '53'){
	          $otro_dato = $value->certificado;
	        }

	        $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'R'));
	        $pdf->SetWidths(array(18, 23, 80, 25, 25, 25));
	        $pdf->Row(array(
	          $value->fecha,
	          $value->serie.$value->folio,
	          $value->cliente,
	          $otro_dato,
	          $value->num_operacion,
	          MyString::formatoNumero($value->importe, 2, '$', false),
	          ), false);
	        $total_producto += $value->importe;
	        $totla_general += $value->importe;
	        $iva_general += $value->iva;
	      }
	      $pdf->SetAligns(array('R', 'R'));
	      $pdf->SetWidths(array(171, 25));
	      $pdf->Row(array('Total', MyString::formatoNumero($total_producto, 2, '$', false)), false);

	      $total_producto = 0;
	      foreach ($fac_ligados['canceladas'] as $key => $value)
	      {
	        $pdf->SetAligns(array('L'));
	        $pdf->SetWidths(array(205));
	        $pdf->Row(array('CANCELADAS'), false, false);

	        $pdf->SetAligns(array('L', 'L', 'L', 'R'));
	        $pdf->SetWidths(array(23, 23, 120, 30));
	        $pdf->Row(array(
	          $value->fecha,
	          $value->serie.$value->folio,
	          $value->cliente,
	          MyString::formatoNumero($value->importe, 2, '$', false),
	          ), false);
	        $total_producto += $value->importe;
	        $totla_general += $value->importe;
	        $iva_general += $value->iva;
	      }
	      $pdf->SetAligns(array('R', 'R'));
	      $pdf->SetWidths(array(166, 30));
	      if ($total_producto > 0) {
	      	$pdf->Row(array('Total', MyString::formatoNumero($total_producto, 2, '$', false)), false);
	      }

	      $pdf->Row(array('SubTotal General', MyString::formatoNumero($totla_general, 2, '$', false)), false);
	      $pdf->Row(array('IVA', MyString::formatoNumero($iva_general, 2, '$', false)), false);
	      $pdf->Row(array('Total General', MyString::formatoNumero($totla_general+$iva_general, 2, '$', false)), false);
      }
    }
    //si es normex y sagarpa
		if ($_GET['id_proveedor'] == 3297) {
			$pdf->SetFont('Arial','B',10);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetXY(6, $pdf->GetY()+2);
			$pdf->SetAligns(array('L'));
			$pdf->SetWidths(array(150));
			$pdf->Row(array('Diferencia: '.MyString::formatoNumero(($totla_general+$iva_general)-($response[1]+$response2[1]), 2, '$', false)), false, false);
		}

		$pdf->Output('cuentas_proveedor.pdf', 'I');
	}

	private function cuentaProveedorCurpPdf(&$pdf, &$res, $first=true, $total_antr=0)
	{
		if (!$first) {
			$pdf->SetFont('Arial','B',8);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetXY(6, $pdf->GetY()+2);
			$pdf->SetAligns(array('L'));
			$pdf->SetWidths(array(150));
			$pdf->Row(array($res['proveedor']->nombre_fiscal), false, false);
		}

		$pdf->SetFont('Arial','',8);

		$aligns = array('C', 'C', 'C', 'L', 'R', 'R', 'R', 'C', 'C', 'C');
		$widths = array(17, 11, 20, 40, 23, 23, 23, 16, 17, 15);
		$header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cargo', 'Abono', 'Saldo', 'Estado', 'F. Ven.', 'D. Trans.');

		$total_cargo = 0;
		$total_abono = 0;
		$total_saldo = 0;
		$totales_x_tipo = array('nacional' => 0, 'internacional' => 0);

		$bad_saldo_ante = true;
		if(isset($res['anterior']->saldo)){ //se suma a los totales del saldo anterior
			// $total_cargo += $res['anterior']->total;
			// $total_abono += $res['anterior']->abonos;
			// $total_saldo += $res['anterior']->saldo;
		}else{
			$res['anterior'] = new stdClass();
			$res['anterior']->total = 0;
			$res['anterior']->abonos = 0;
			$res['anterior']->saldo = 0;
		}
		$res['anterior']->concepto = 'Saldo anterior a '.$res['fecha1'];
    $comprasids = array();
		foreach($res['cuentas'] as $key => $item){
      $comprasids[] = $item->id_compra;
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				if ($first || $pdf->GetY() >= $pdf->limiteY)
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
					MyString::formatoNumero($res['anterior']->total, 2, '$', false),
					MyString::formatoNumero($res['anterior']->abonos, 2, '$', false),
					MyString::formatoNumero($res['anterior']->saldo, 2, '$', false),
					'', '', ''), false);
				$bad_saldo_ante = false;
			}

			if (preg_match('/internacional/', $item->concepto2)) {
				$totales_x_tipo['internacional'] += $item->cargo;
			} else {
				$totales_x_tipo['nacional'] += $item->cargo;
			}

			$datos = array($item->fecha,
									$item->serie,
									$item->folio,
									$item->concepto.(!$first? ' '.$item->concepto2: ''),
									MyString::formatoNumero($item->cargo, 2, '$', false),
									MyString::formatoNumero($item->abono, 2, '$', false),
									MyString::formatoNumero($item->saldo, 2, '$', false),
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
				MyString::formatoNumero($total_cargo, 2, '$', false),
				MyString::formatoNumero($total_abono, 2, '$', false),
				MyString::formatoNumero($total_saldo, 2, '$', false)), true);

		if (!$first) {
			$pdf->SetX(6);
			$pdf->Row(array('Total General:',
					'',
					'',
					MyString::formatoNumero($total_saldo+$total_antr, 2, '$', false)), true);
		}

		return array($comprasids, $total_saldo, $totales_x_tipo);
	}

	public function cuentaProveedorExcel(&$xls=null, $close=true){
		$res = $this->getCuentaProveedorData();

		if (count($res['anterior']) > 0)
			$res['anterior'] = $res['anterior'][0];

		$this->load->library('myexcel');
		if($xls == null)
			$xls = new myexcel();

		$worksheet =& $xls->workbook->addWorksheet();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $xls->titulo1 = $empresa['info']->nombre_fiscal;

		$xls->titulo2 = 'Cuenta de '.$res['proveedor']->nombre_fiscal;
		$xls->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		$xls->titulo4 = ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por pagar');

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


  public function cuenta2ProveedorAllPdf(){
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');

    $res = $this->getCuentaProveedorData(['only_select' => true, 'all_empresas' => true]);

    // $this->load->model('empresas_model');
    // $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    // $pdf->logo = $empresa['info']->logo!=''? (file_exists($empresa['info']->logo)? $empresa['info']->logo: '') : '';
    $pdf->titulo1 = '';

    if (count($res['anterior']) > 0)
      $res['anterior'] = $res['anterior'][0];

    $pdf->titulo2 = isset($res['proveedor']->nombre_fiscal)? 'Cuenta de '.$res['proveedor']->nombre_fiscal: '';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por pagar');
    $pdf->AliasNbPages();
    //$pdf->AddPage();

    // $response = $this->cuentaProveedorCurpPdf($pdf, $res);
    // echo "<pre>";
    //   var_dump($res);
    // echo "</pre>";exit;


    $pdf->SetFont('Arial','',8);

    $aligns = array('C', 'C', 'C', 'L', 'L', 'R', 'R', 'R', 'C', 'C', 'C');
    $widths = array(17, 17, 26, 45, 47, 24, 24, 24, 16, 17, 13);
    $header = array('Fecha F', 'Fecha', 'Folio', 'Concepto', 'Proveedor', 'Cargo', 'Abono', 'Saldo', 'Estado', 'F. Ven.', 'D Trans');

    $total_cargo = 0;
    $total_abono = 0;
    $total_saldo = 0;
    $total_cargo_p = $total_abono_p = $total_saldo_p = 0;
    $total_cargo_e = $total_abono_e = $total_saldo_e = 0;
    $aux_prov = '';
    $aux_empresa = '';
    $totales_x_tipo = array('nacional' => 0, 'internacional' => 0);

    // $bad_saldo_ante = true;
    // if(isset($res['anterior']->saldo)){ //se suma a los totales del saldo anterior
    //   // $total_cargo += $res['anterior']->total;
    //   // $total_abono += $res['anterior']->abonos;
    //   // $total_saldo += $res['anterior']->saldo;
    // }else{
    //   $res['anterior'] = new stdClass();
    //   $res['anterior']->total = 0;
    //   $res['anterior']->abonos = 0;
    //   $res['anterior']->saldo = 0;
    // }
    // $res['anterior']->concepto = 'Saldo anterior a '.$res['fecha1'];
    $comprasids = array();
    foreach($res['cuentas'] as $key => $item){
      $comprasids[] = $item->id_compra;
      $band_head = false;
      if($pdf->GetY()+25 >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();
      }

      // $pdf->SetTextColor(0,0,0);
      // if($bad_saldo_ante){
      //   $pdf->SetFont('Arial','',7);
      //   $pdf->SetX(6);
      //   $pdf->SetAligns($aligns);
      //   $pdf->SetWidths($widths);
      //   $pdf->Row(array('', '', '', $res['anterior']->concepto, '',
      //     MyString::formatoNumero($res['anterior']->total, 2, '$', false),
      //     MyString::formatoNumero($res['anterior']->abonos, 2, '$', false),
      //     MyString::formatoNumero($res['anterior']->saldo, 2, '$', false),
      //     '', '', ''), false);
      //   $bad_saldo_ante = false;
      // }


      if ($aux_prov != $res['cuentas'][$key]->proveedor) {
        if ($key > 0) {
          $pdf->SetX(6);
          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(220,220,220);
          $pdf->SetAligns(array('R', 'R', 'R', 'R'));
          $pdf->SetWidths(array(152, 24, 24, 24));
          $pdf->Row(array('Total Proveedor:',
              MyString::formatoNumero($total_cargo_p, 2, '$', false),
              MyString::formatoNumero($total_abono_p, 2, '$', false),
              MyString::formatoNumero($total_saldo_p, 2, '$', false)), true);
        }

        $total_cargo_p = $total_abono_p = $total_saldo_p = 0;
        $pdf->SetY($pdf->GetY()+3);
        $aux_prov = $res['cuentas'][$key]->proveedor;
      }

      if ($aux_empresa != $item->id_empresa) {
        if ($key > 0) {
          $pdf->SetX(6);
          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(220,220,220);
          $pdf->SetAligns(array('R', 'R', 'R', 'R'));
          $pdf->SetWidths(array(152, 24, 24, 24));
          $pdf->Row(array('Total Empresa:',
              MyString::formatoNumero($total_cargo_e, 2, '$', false),
              MyString::formatoNumero($total_abono_e, 2, '$', false),
              MyString::formatoNumero($total_saldo_e, 2, '$', false)), true);
        }

        $total_cargo_e = $total_abono_e = $total_saldo_e = 0;

        $pdf->SetFont('Arial','B', 10);
        $pdf->SetX(6);
        $pdf->SetAligns(['L']);
        $pdf->SetWidths([150]);
        $pdf->Row([$item->empresa], false, false);
        $aux_empresa = $item->id_empresa;

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      $pdf->SetFont('Arial','',8);

      if (preg_match('/internacional/', $item->concepto2)) {
        $totales_x_tipo['internacional'] += $item->cargo;
      } else {
        $totales_x_tipo['nacional'] += $item->cargo;
      }

      $datos = array($item->fecha_factura,
                  $item->fecha,
                  $item->serie.$item->folio,
                  $item->ordenes,
                  $item->proveedor,
                  MyString::formatoNumero($item->cargo, 2, '$', false),
                  MyString::formatoNumero($item->abono, 2, '$', false),
                  MyString::formatoNumero($item->saldo, 2, '$', false),
                  $item->estado, $item->fecha_vencimiento,
                  $item->dias_transc);

      $total_cargo_p += $item->cargo;
      $total_abono_p += $item->abono;
      $total_saldo_p += $item->saldo;

      $total_cargo_e += $item->cargo;
      $total_abono_e += $item->abono;
      $total_saldo_e += $item->saldo;

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
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(220,220,220);
    $pdf->SetAligns(array('R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(152, 24, 24, 24));
    $pdf->Row(array('Total Proveedor:',
        MyString::formatoNumero($total_cargo_p, 2, '$', false),
        MyString::formatoNumero($total_abono_p, 2, '$', false),
        MyString::formatoNumero($total_saldo_p, 2, '$', false)), true);

    $pdf->SetX(6);
    $pdf->SetFont('Arial','B',8);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(220,220,220);
    $pdf->SetAligns(array('R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(152, 24, 24, 24));
    $pdf->Row(array('Total Empresa:',
        MyString::formatoNumero($total_cargo_e, 2, '$', false),
        MyString::formatoNumero($total_abono_e, 2, '$', false),
        MyString::formatoNumero($total_saldo_e, 2, '$', false)), true);

    $pdf->SetX(6);
    $pdf->SetFont('Arial','B',8);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(160,160,160);
    $pdf->SetAligns(array('R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(152, 24, 24, 24));
    $pdf->Row(array('Totales:',
        MyString::formatoNumero($total_cargo, 2, '$', false),
        MyString::formatoNumero($total_abono, 2, '$', false),
        MyString::formatoNumero($total_saldo, 2, '$', false)), true);


    $pdf->Output('cuentas_pagar.pdf', 'I');
  }
  public function cuenta2ProveedorPdf(){
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');

    $res = $this->getCuentaProveedorData(['only_select' => true]);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $pdf->logo = $empresa['info']->logo!=''? (file_exists($empresa['info']->logo)? $empresa['info']->logo: '') : '';
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    if (count($res['anterior']) > 0)
      $res['anterior'] = $res['anterior'][0];

    $pdf->titulo2 = isset($res['proveedor']->nombre_fiscal)? 'Cuenta de '.$res['proveedor']->nombre_fiscal: '';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por pagar');
    $pdf->AliasNbPages();
    //$pdf->AddPage();

    // $response = $this->cuentaProveedorCurpPdf($pdf, $res);
    // echo "<pre>";
    //   var_dump($res);
    // echo "</pre>";exit;


    $pdf->SetFont('Arial','',8);

    $aligns = array('C', 'C', 'C', 'L', 'L', 'R', 'R', 'R', 'C', 'C', 'C');
    $widths = array(17, 17, 26, 45, 47, 24, 24, 24, 16, 17, 13);
    $header = array('Fecha F', 'Fecha', 'Folio', 'Concepto', 'Proveedor', 'Cargo', 'Abono', 'Saldo', 'Estado', 'F. Ven.', 'D Trans');

    $total_cargo = 0;
    $total_abono = 0;
    $total_saldo = 0;
    $total_cargo_p = $total_abono_p = $total_saldo_p = 0;
    $aux_prov = '';
    $totales_x_tipo = array('nacional' => 0, 'internacional' => 0);

    $bad_saldo_ante = true;
    if(isset($res['anterior']->saldo)){ //se suma a los totales del saldo anterior
      // $total_cargo += $res['anterior']->total;
      // $total_abono += $res['anterior']->abonos;
      // $total_saldo += $res['anterior']->saldo;
    }else{
      $res['anterior'] = new stdClass();
      $res['anterior']->total = 0;
      $res['anterior']->abonos = 0;
      $res['anterior']->saldo = 0;
    }
    $res['anterior']->concepto = 'Saldo anterior a '.$res['fecha1'];
    $comprasids = array();
    foreach($res['cuentas'] as $key => $item){
      $comprasids[] = $item->id_compra;
      $band_head = false;
      if($pdf->GetY()+5 >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        if ($pdf->GetY()+5 >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      $pdf->SetTextColor(0,0,0);
      if($bad_saldo_ante){
        $pdf->SetFont('Arial','',7);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array('', '', '', $res['anterior']->concepto, '',
          MyString::formatoNumero($res['anterior']->total, 2, '$', false),
          MyString::formatoNumero($res['anterior']->abonos, 2, '$', false),
          MyString::formatoNumero($res['anterior']->saldo, 2, '$', false),
          '', '', ''), false);
        $bad_saldo_ante = false;
      }


      // if (!isset($res['cuentas'][$key+1]) || $aux_prov != $res['cuentas'][$key+1]->proveedor) {
      if ($aux_prov != $res['cuentas'][$key]->proveedor) {
        if ($key > 0) {
          $pdf->SetX(6);
          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(220,220,220);
          $pdf->SetAligns(array('R', 'R', 'R', 'R'));
          $pdf->SetWidths(array(152, 24, 24, 24));
          $pdf->Row(array('Totales:',
              MyString::formatoNumero($total_cargo_p, 2, '$', false),
              MyString::formatoNumero($total_abono_p, 2, '$', false),
              MyString::formatoNumero($total_saldo_p, 2, '$', false)), true);
        }

        $total_cargo_p = $total_abono_p = $total_saldo_p = 0;
        $pdf->SetY($pdf->GetY()+3);
        $aux_prov = $res['cuentas'][$key]->proveedor;
      }

      $pdf->SetFont('Arial','',8);

      if (preg_match('/internacional/', $item->concepto2)) {
        $totales_x_tipo['internacional'] += $item->cargo;
      } else {
        $totales_x_tipo['nacional'] += $item->cargo;
      }

      $datos = array($item->fecha_factura,
                  $item->fecha,
                  $item->serie.$item->folio,
                  $item->ordenes,
                  $item->proveedor,
                  MyString::formatoNumero($item->cargo, 2, '$', false),
                  MyString::formatoNumero($item->abono, 2, '$', false),
                  MyString::formatoNumero($item->saldo, 2, '$', false),
                  $item->estado, $item->fecha_vencimiento,
                  $item->dias_transc);

      $total_cargo_p += $item->cargo;
      $total_abono_p += $item->abono;
      $total_saldo_p += $item->saldo;

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
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(220,220,220);
    $pdf->SetAligns(array('R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(152, 24, 24, 24));
    $pdf->Row(array('Totales:',
        MyString::formatoNumero($total_cargo_p, 2, '$', false),
        MyString::formatoNumero($total_abono_p, 2, '$', false),
        MyString::formatoNumero($total_saldo_p, 2, '$', false)), true);

    $pdf->SetX(6);
    $pdf->SetFont('Arial','B',8);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(160,160,160);
    $pdf->SetAligns(array('R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(152, 24, 24, 24));
    $pdf->Row(array('Totales:',
        MyString::formatoNumero($total_cargo, 2, '$', false),
        MyString::formatoNumero($total_abono, 2, '$', false),
        MyString::formatoNumero($total_saldo, 2, '$', false)), true);


    $pdf->Output('cuentas_proveedor.pdf', 'I');
  }
  public function cuenta2ProveedorExcel(&$xls=null, $close=true){
    $res = $this->getCuentaProveedorData(['only_select' => true]);

    if (count($res['anterior']) > 0)
      $res['anterior'] = $res['anterior'][0];

    $this->load->library('myexcel');
    if($xls == null)
      $xls = new myexcel();

    $worksheet =& $xls->workbook->addWorksheet();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $xls->titulo1 = $empresa['info']->nombre_fiscal;

    $xls->titulo2 = (isset($res['proveedor']->nombre_fiscal)? 'Cuenta de '.$res['proveedor']->nombre_fiscal: '');
    $xls->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $xls->titulo4 = ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por pagar');

    if(is_array($res['anterior'])){
      $res['anterior'] = new stdClass();
      $res['anterior']->cargo = 0;
      $res['anterior']->abono = 0;
      $res['anterior']->saldo = 0;
    }else{
      $res['anterior']->cargo = $res['anterior']->total;
      $res['anterior']->abono = $res['anterior']->abonos;
    }
    $res['anterior']->fecha = $res['anterior']->fecha_factura = $res['anterior']->serie = $res['anterior']->folio = '';
    $res['anterior']->concepto = $res['anterior']->estado = $res['anterior']->fecha_vencimiento = '';
    $res['anterior']->proveedor = '';
    $res['anterior']->dias_transc = '';
    $res['anterior']->command_sum = false;

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
        'head' => array('Fecha F.', 'Fecha', 'Serie', 'Folio', 'Concepto', 'Proveedor', 'Cargo', 'Abono', 'Saldo', 'Estado', 'Fecha Vencimiento', 'Dias Trans.'),
        'conte' => array(
            array('name' => 'fecha_factura', 'format' => 'format4', 'sum' => -1),
            array('name' => 'fecha', 'format' => 'format4', 'sum' => -1),
            array('name' => 'serie', 'format' => 'format4', 'sum' => -1),
            array('name' => 'folio', 'format' => 'format4', 'sum' => -1),
            array('name' => 'concepto', 'format' => 'format4', 'sum' => -1),
            array('name' => 'proveedor', 'format' => 'format4', 'sum' => -1),
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
	public function getDetalleVentaFacturaData($id_factura=null, $tipo=null, $tipo_cambio=0)
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
												plazo_credito, id_proveedor, id_empresa, 'f'::text AS tipo
												FROM compras
												WHERE id_compra={$_GET['id']}")->result();
			$sql = array('tabla' => 'compras_abonos',
										'where_field' => 'id_compra');
			$sql_nc = "UNION
						SELECT
							id_compra AS id_abono,
							fecha,
							total AS abono,
							('Nota de credito ' || serie || folio) AS concepto,
							'nc' AS tipo
						FROM compras
						WHERE status <> 'ca' AND status <> 'b' AND id_nc IS NOT NULL
							AND id_nc = {$_GET['id']}
							AND Date(fecha) <= '{$fecha2}' ";
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
							'ab'::text AS tipo
						FROM {$sql['tabla']}
						WHERE {$sql['where_field']} = {$_GET['id']}
							AND Date(fecha) <= '{$fecha2}'
					{$sql_nc}
				) AS tt
					ORDER BY fecha ASC
			");

		// obtiene el nuevo total de la compra de acuerdo al tipo de cambio actual
		$productos = $this->db->query(
											"SELECT cantidad, precio_unitario, porcentaje_iva, porcentaje_retencion, porcentaje_ieps, tipo_cambio, total
												FROM compras_productos
												WHERE id_compra={$_GET['id']}")->result();
		$new_total = 0;
		$entro1 = false;
		if (count($productos) > 0) {
			foreach ($productos as $key => $value) {
				$pu = $value->precio_unitario;
				if($value->tipo_cambio > 0 && $tipo_cambio > 0) {
					$pu = ($value->precio_unitario/$value->tipo_cambio)*$tipo_cambio;
					$subtotal = $value->cantidad*$pu;
					$new_total += floor( ($subtotal+($subtotal*$value->porcentaje_iva/100)+
											($subtotal*$value->porcentaje_ieps/100)-
											($subtotal*$value->porcentaje_retencion/100) ) * 100)/100;
					$entro1 = true;
				} else {
          $new_total += $value->total;
        }
			}
		}

		$new_total = $new_total==0||!$entro1? $data['info'][0]->total : $new_total;

		//obtenemos la info del proveedor
		$prov['info'] = '';
		if (isset($data['info'][0]->id_proveedor))
		{
			$this->load->model('proveedores_model');
			$prov = $this->proveedores_model->getProveedorInfo($data['info'][0]->id_proveedor, true);
		}

		//obtenemos la info de la empresa
		$empresa['info'] = '';
		if (isset($data['info'][0]->id_empresa))
		{
			$this->load->model('empresas_model');
			$empresa = $this->empresas_model->getInfoEmpresa($data['info'][0]->id_empresa, true);
		}

		$response = array(
				'abonos'    => array(),
				'saldo'     => '',
				'new_total' => $new_total,
				'cobro'     => $data['info'],
				'proveedor' => $prov['info'],
				'empresa'   => $empresa['info'],
				'fecha1'    => $fecha1
		);

		$abonos = 0;
		if($res->num_rows() > 0){
			$response['abonos'] = $res->result();

			foreach ($response['abonos'] as $key => $value) {
				$abonos += $value->abono;
			}
		}
		$response['saldo']     = $response['cobro'][0]->total - $abonos;
		$response['new_total'] -= $abonos;

		return $response;
	}

  public function historialAbonosPdf(){
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = false;

    $res = $this->getDetalleVentaFacturaData();
    // echo "<pre>";
    //   var_dump($res);
    // echo "</pre>";exit;

    $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetFont('Arial','B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(205));
    $pdf->Row(array("Proveedor: {$res['proveedor']->nombre_fiscal}"), false, false);

    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L'));
    $pdf->SetWidths(array(40, 40, 125));
    $pdf->Row(array(
      "Factura: {$res['cobro'][0]->serie}{$res['cobro'][0]->folio}",
      "Fecha: {$res['cobro'][0]->fecha}",
      "Total: ".number_format($res['cobro'][0]->total, 2)
    ), false, false);

    $pdf->SetAligns(array('L', 'L', 'L'));
    $pdf->SetWidths(array(20, 80, 20));
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('Fecha', 'Concepto', 'Abono'), false, true);
    $pdf->SetFont('Arial','', 9);
    foreach ($res['abonos'] as $key => $value) {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array(
        $value->fecha,
        $value->concepto,
        '$'.number_format($value->abono, 2)
      ), false, true);
    }

    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetFont('Arial','B', 10);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(205));
    $pdf->Row(array("Saldo: $".number_format($res['saldo'], 2)), false, false);

    $pdf->Output('cuentas_proveedor.pdf', 'I');
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
			$total += $_POST['new_total'][$key]; //$_POST['montofv'][$key]
			$desc .= ' | '.$_POST['factura_desc'][$key].'=>'.MyString::formatoNumero($_POST['new_total'][$key], 2, '', false);
		}
		$desc = ' ('.substr($desc, 1).')';
		$resp = $this->banco_cuentas_model->addRetiro(array(
					'id_cuenta'           => $this->input->post('dcuenta'),
					'id_banco'            => $data_cuenta->id_banco,
					'fecha'               => $this->input->post('dfecha'),
					'numero_ref'          => $this->input->post('dreferencia'),
					'concepto'            => $this->input->post('dconcepto').$desc,
					'monto'               => $total,
					'tipo'                => 'f',
					'entransito'          => 'f',
					'metodo_pago'         => $this->input->post('fmetodo_pago'),
					'id_proveedor'        => $inf_factura['proveedor']->id_proveedor,
					'a_nombre_de'         => $inf_factura['proveedor']->nombre_fiscal,
					'id_cuenta_proveedor' => ($this->input->post('fcuentas_proveedor')!=''? $this->input->post('fcuentas_proveedor'): NULL),
					'clasificacion'       => ($this->input->post('fmetodo_pago')=='cheque'? 'echeque': 'egasto'),
					'tcambio'             => floatval($this->input->post('tcambio')),
					));

		if ($resp['error'] == false)
		{
			foreach ($_POST['ids'] as $key => $value)  //foreach ($ids as $key => $value)
			{
				$_GET['id']   = $value;
				$_GET['tipo'] = $_POST['tipos'][$key];
				$data = array('fecha'  => $this->input->post('dfecha'),
							'concepto'       => $this->input->post('dconcepto'),
							'total'          => $_POST['montofv'][$key], //$total,
              'total_bc'       => $_POST['montofv'][$key], //$total,
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
		$tipo = (isset($data['tipo']) && $data['tipo'] == 'f')? $data['tipo'] : $this->input->get('tipo');

		if ($tipo == 'f') {
			$camps = array('id_compra', 'compras_abonos', 'compras', 'compras_abonos_id_abono_seq');

      if ((isset($data['tipo']) && $data['tipo'] == 'f')) {
        unset($data['tipo']);
      }
		}else{
			// $camps = array('id_venta', 'facturacion_ventas_remision_abonos', 'facturacion_ventas_remision');
		}

		if ($data == null) {
			$data = array(
            'fecha'               => $this->input->post('dfecha'),
            'concepto'            => $this->input->post('dconcepto'),
            'total'               => $this->input->post('dmonto'),
            'total_bc'            => $this->input->post('dmonto'),
            'id_cuenta'           => $this->input->post('dcuenta'),
            'ref_movimiento'      => $this->input->post('dreferencia'),
            'id_cuenta_proveedor' => ($this->input->post('fcuentas_proveedor')!=''? $this->input->post('fcuentas_proveedor'): NULL)
          );
		}

		$pagada = false;
		$inf_factura = $this->cuentas_pagar_model->getDetalleVentaFacturaData($id, $tipo);
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

			$data['concepto'] .= ' ('.$inf_factura['cobro'][0]->serie.$inf_factura['cobro'][0]->folio.'=>'.MyString::formatoNumero($data['total'], 2, '', false).')';
			$resp = $this->banco_cuentas_model->addRetiro(array(
						'id_cuenta'    => $data['id_cuenta'],
						'id_banco'     => $data_cuenta->id_banco,
						'fecha'        => $data['fecha'],
						'numero_ref'   => $data['ref_movimiento'],
						'concepto'     => $data['concepto'],
						'monto'        => (isset($data['total_bc']{0})? $data['total_bc']: $data['total']),
						'tipo'         => 'f',
						'entransito'   => 'f',
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
		$data['id_abono'] = $this->db->insert_id($camps[3]);

		// Bitacora
    $this->bitacora_model->_insert($camps[1], $data['id_abono'],
                            array(':accion'    => 'un abono a la compra ',
                            			':seccion' => 'cuentas por pagar',
                                  ':folio'     => $inf_factura['cobro'][0]->serie.$inf_factura['cobro'][0]->folio.' por '.MyString::formatoNumero($data['total']),
                                  ':id_empresa' => $inf_factura['empresa']->id_empresa,
                                  ':empresa'   => 'de '.$inf_factura['proveedor']->nombre_fiscal));

    $this->db->update('banco_pagos_compras', array('status' => 't'), array($camps[0] => $id));

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

		$info_abano = $this->db->query("SELECT * FROM compras_abonos WHERE {$camps[0]} = {$ida}")->row();

		$this->db->delete($camps[1], "{$camps[0]} = {$ida}");
		//Se cambia el estado de la factura
		$this->db->update($camps[2], array('status' => 'p'), "{$camps[3]} = {$id}");

		// Bitacora
		$inf_factura = $this->cuentas_pagar_model->getDetalleVentaFacturaData($id);
    $this->bitacora_model->_cancel('compras_abonos', $ida,
                            array(':accion'     => 'un abono a la compra ', ':seccion' => 'cuentas por pagar',
                                  ':folio'      => $inf_factura['cobro'][0]->serie.$inf_factura['cobro'][0]->folio.' por '.MyString::formatoNumero($info_abano->total),
                                  ':id_empresa' => $inf_factura['empresa']->id_empresa,
                                  ':empresa'    => 'de '.$inf_factura['proveedor']->nombre_fiscal));

		return true;
	}

  /**
   * Obtiene el listado de abonos echos en cuentas x pagar o 1 espesifico
   * @param  [type] $movimientoId [description]
   * @return [type]               [description]
   */
  public function getAbonosData($movimientoId=null)
  {
    //paginacion
    $params = array(
        'result_items_per_page' => '60',
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
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

      if ($this->input->get('did_proveedor') != '')
        $sql .= " AND c.id_proveedor = '".$_GET['did_proveedor']."'";
    }

    $query = BDUtil::pagination(
      "SELECT
          bmf.id_movimiento, fa.ref_movimiento, fa.concepto, Sum(fa.total) AS total_abono,
          bc.cuenta_cpi, Sum(f.subtotal) AS subtotal, Sum(f.total) AS total, Sum(((fa.total*100/f.total)*f.importe_iva/100)) AS importe_iva,
          Sum(((fa.total*100/f.total)*f.retencion_iva/100)) AS retencion_iva, c.nombre_fiscal,
          c.cuenta_cpi AS cuenta_cpi_cliente, Date(fa.fecha) AS fecha, e.nombre_fiscal AS empresa, e.logo
        FROM compras AS f
          INNER JOIN compras_abonos AS fa ON fa.id_compra = f.id_compra
          INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta
          INNER JOIN proveedores AS c ON c.id_proveedor = f.id_proveedor
          INNER JOIN empresas AS e ON e.id_empresa = f.id_empresa
          INNER JOIN banco_movimientos_compras AS bmf ON bmf.id_compra_abono = fa.id_abono
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
        FROM compras AS f
          INNER JOIN compras_abonos AS fa ON fa.id_compra = f.id_compra
          INNER JOIN banco_movimientos_compras AS bmf ON bmf.id_compra_abono = fa.id_abono
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
   public function imprimir_recibo($movimientoId, $path = null)
   {
      $orden = $this->getAbonosData($movimientoId);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf();
      $pdf->show_head = true;
      $pdf->titulo1 = $orden['abonos'][0]->empresa;
      $pdf->titulo2 = 'Proveedor: ' . $orden['abonos'][0]->nombre_fiscal;
      $pdf->titulo3 = 'RECIBO DE PAGO';

      $pdf->logo = $orden['abonos'][0]->logo!=''? (file_exists($orden['abonos'][0]->logo)? $orden['abonos'][0]->logo: '') : '';

      $pdf->AliasNbPages();
      $pdf->AddPage();

      $aligns = array('C', 'C', 'R');
      $widths = array(25, 25, 40);
      $header = array('FECHA', 'FOLIO', 'TOTAL');

      $total = 0;

      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFillColor(255,255,255);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($header, true, false);
      $pdf->Line(6, $pdf->GetY()-1, 205, $pdf->GetY()-1);
      foreach ($orden['facturas'] as $key => $prod)
      {
        $pdf->SetFont('Arial','',8);
        $pdf->SetTextColor(0,0,0);
        $datos = array(
          $prod->fecha,
          $prod->serie.$prod->folio,
          MyString::formatoNumero($prod->total, 2, '$', false),
        );
        $pdf->SetXY(6, $pdf->GetY()-2);
        $pdf->Row($datos, false, false);

        $total += $prod->total;
      }
      $pdf->SetFont('Arial','B',8);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array('', 'TOTAL', MyString::formatoNumero($total, 2, '$', false) ), false, false);

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
   *  ESTADO DE CUENTAS
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
    }elseif($this->input->get('ftipo')=='to'){
      $all_clientes = true;
      $all_facturas = true;
      if($this->input->get('fid_cliente') != '')
        $sql_clientes .= " AND c.id_proveedor = ".$this->input->get('fid_cliente');
    }

      if($this->input->get('did_empresa') != ''){
        $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
        $sqlt .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
        $sql_clientes .= " AND c.id_empresa = ".$this->input->get('did_empresa');
      }

      if($this->input->get('fid_cliente') != ''){
        $sql .= " AND f.id_proveedor = '".$this->input->get('fid_cliente')."'";
        $sqlt .= " AND f.id_proveedor = '".$this->input->get('fid_cliente')."'";
      }

      /*** Saldo anterior ***/
      $saldo_anterior = $this->db->query(
        "SELECT
          id_proveedor,
          Sum(total) AS total,
          Sum(iva) AS iva,
          Sum(abonos) AS abonos,
          Sum(saldo)::numeric(12, 2) AS saldo
        FROM
          (
            SELECT
              c.id_proveedor,
              c.nombre_fiscal,
              Sum(f.total) AS total,
              Sum(f.importe_iva) AS iva,
              COALESCE(Sum(faa.abonos),0) as abonos,
              COALESCE(Sum(f.total) - COALESCE(Sum(faa.abonos),0), 0) AS saldo,
              'f'::text as tipo
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
                  WHERE f.status <> 'ca' AND f.status <> 'b'
                    AND Date(fa.fecha) <= '{$fecha2}'{$sql}
                  GROUP BY f.id_proveedor, f.id_compra
                )
                UNION
                (
                  SELECT
                    f.id_proveedor,
                    f.id_compra,
                    Sum(f.total) AS abonos
                  FROM
                    compras AS f
                  WHERE f.status <> 'ca' AND f.status <> 'b'
                    AND f.id_nc IS NOT NULL AND f.tipo = 'nc'
                    AND Date(f.fecha) <= '{$fecha2}'{$sql}
                  GROUP BY f.id_proveedor, f.id_compra
                )
              ) AS faa ON f.id_proveedor = faa.id_proveedor AND f.id_compra = faa.id_compra
            WHERE c.status = 'ac' {$sql_clientes} AND f.status <> 'ca' AND f.status <> 'b'
              AND Date(f.fecha) < '{$fecha1}'{$sql} {$sqlext[0]}
            GROUP BY c.id_proveedor, c.nombre_fiscal, faa.abonos, tipo
          ) AS sal
        {$sql2}
        GROUP BY id_proveedor
      ");
      $saldo_anterior_data = $saldo_anterior->result();
      $saldo_anterior = [];
      foreach ($saldo_anterior_data as $key => $value) {
        $saldo_anterior[$value->id_proveedor] = $value;
      }

      $saldo_anterior_vencido = $this->db->query(
        "SELECT
          id_proveedor,
          Sum(total) AS total,
          Sum(iva) AS iva,
          Sum(abonos) AS abonos,
          Sum(saldo)::numeric(12, 2) AS saldo
        FROM
          (
            SELECT
              c.id_proveedor,
              c.nombre_fiscal,
              Sum(f.total) AS total,
              Sum(f.importe_iva) AS iva,
              COALESCE(Sum(faa.abonos),0) as abonos,
              COALESCE(Sum(f.total) - COALESCE(Sum(faa.abonos),0), 0) AS saldo,
              'f'::text as tipo
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
                  WHERE f.status <> 'ca' AND f.status <> 'b'
                    AND Date(fa.fecha) <= '{$fecha2}'{$sql}
                  GROUP BY f.id_proveedor, f.id_compra
                )
                UNION
                (
                  SELECT
                    f.id_proveedor,
                    f.id_compra,
                    Sum(f.total) AS abonos
                  FROM
                    compras AS f
                  WHERE f.status <> 'ca' AND f.status <> 'b'
                    AND f.id_nc IS NOT NULL AND f.tipo = 'nc'
                    AND Date(f.fecha) <= '{$fecha2}'{$sql}
                  GROUP BY f.id_proveedor, f.id_compra
                )
              ) AS faa ON f.id_proveedor = faa.id_proveedor AND f.id_compra = faa.id_compra
            WHERE c.status = 'ac' {$sql_clientes} AND f.status <> 'ca' AND f.status <> 'b'
              AND Date(f.fecha) < '{$fecha1}'{$sql} {$sqlext[0]} AND
              Date(f.fecha + (f.plazo_credito || ' days')::interval) < '{$fecha2}'
            GROUP BY c.id_proveedor, c.nombre_fiscal, faa.abonos, tipo
          ) AS sal
        {$sql2}
        GROUP BY id_proveedor
      ");
      $saldo_anterior_vencido_data = $saldo_anterior_vencido->result();
      $saldo_anterior_vencido = [];
      foreach ($saldo_anterior_vencido_data as $key => $value) {
        $saldo_anterior_vencido[$value->id_proveedor] = $value;
      }

      $proveedores = $this->db->query("SELECT id_proveedor, nombre_fiscal, cuenta_cpi
        FROM proveedores AS c WHERE c.status = 'ac' {$sql_clientes} ORDER BY cuenta_cpi ASC ");
      $response = array();
      foreach ($proveedores->result() as $keyc => $proveedor)
      {
        $proveedor->saldo = 0;
        $proveedor->saldo_anterior_vencido = [];

        /*** Saldo anterior ***/
        /*$saldo_anterior = $this->db->query(
          "SELECT
            id_proveedor,
            Sum(total) AS total,
            Sum(iva) AS iva,
            Sum(abonos) AS abonos,
            Sum(saldo)::numeric(12, 2) AS saldo
          FROM
            (
              SELECT
                c.id_proveedor,
                c.nombre_fiscal,
                Sum(f.total) AS total,
                Sum(f.importe_iva) AS iva,
                COALESCE(Sum(faa.abonos),0) as abonos,
                COALESCE(Sum(f.total) - COALESCE(Sum(faa.abonos),0), 0) AS saldo,
                'f'::text as tipo
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
                    WHERE f.status <> 'ca' AND f.status <> 'b'
                      AND f.id_proveedor = '{$proveedor->id_proveedor}'
                      AND Date(fa.fecha) <= '{$fecha2}'{$sql}
                    GROUP BY f.id_proveedor, f.id_compra
                  )
                  UNION
                  (
                    SELECT
                      f.id_proveedor,
                      f.id_compra,
                      Sum(f.total) AS abonos
                    FROM
                      compras AS f
                    WHERE f.status <> 'ca' AND f.status <> 'b'
                      AND f.id_nc IS NOT NULL AND f.tipo = 'nc'
                      AND f.id_proveedor = '{$proveedor->id_proveedor}'
                      AND Date(f.fecha) <= '{$fecha2}'{$sql}
                    GROUP BY f.id_proveedor, f.id_compra
                  )
                ) AS faa ON f.id_proveedor = faa.id_proveedor AND f.id_compra = faa.id_compra
              WHERE c.id_proveedor = '{$proveedor->id_proveedor}' AND f.status <> 'ca' AND f.status <> 'b'
                AND Date(f.fecha) < '{$fecha1}'{$sql} {$sqlext[0]}
              GROUP BY c.id_proveedor, c.nombre_fiscal, faa.abonos, tipo
            ) AS sal
          {$sql2}
          GROUP BY id_proveedor
        ");

        $saldo_anterior_vencido = $this->db->query(
          "SELECT
            id_proveedor,
            Sum(total) AS total,
            Sum(iva) AS iva,
            Sum(abonos) AS abonos,
            Sum(saldo)::numeric(12, 2) AS saldo
          FROM
            (
              SELECT
                c.id_proveedor,
                c.nombre_fiscal,
                Sum(f.total) AS total,
                Sum(f.importe_iva) AS iva,
                COALESCE(Sum(faa.abonos),0) as abonos,
                COALESCE(Sum(f.total) - COALESCE(Sum(faa.abonos),0), 0) AS saldo,
                'f'::text as tipo
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
                    WHERE f.status <> 'ca' AND f.status <> 'b'
                      AND f.id_proveedor = '{$proveedor->id_proveedor}'
                      AND Date(fa.fecha) <= '{$fecha2}'{$sql}
                    GROUP BY f.id_proveedor, f.id_compra
                  )
                  UNION
                  (
                    SELECT
                      f.id_proveedor,
                      f.id_compra,
                      Sum(f.total) AS abonos
                    FROM
                      compras AS f
                    WHERE f.status <> 'ca' AND f.status <> 'b'
                      AND f.id_nc IS NOT NULL AND f.tipo = 'nc'
                      AND f.id_proveedor = '{$proveedor->id_proveedor}'
                      AND Date(f.fecha) <= '{$fecha2}'{$sql}
                    GROUP BY f.id_proveedor, f.id_compra
                  )
                ) AS faa ON f.id_proveedor = faa.id_proveedor AND f.id_compra = faa.id_compra
              WHERE c.id_proveedor = '{$proveedor->id_proveedor}' AND f.status <> 'ca' AND f.status <> 'b'
                AND Date(f.fecha) < '{$fecha1}'{$sql} {$sqlext[0]} AND
                Date(f.fecha + (f.plazo_credito || ' days')::interval) < '{$fecha2}'
              GROUP BY c.id_proveedor, c.nombre_fiscal, faa.abonos, tipo
            ) AS sal
          {$sql2}
          GROUP BY id_proveedor
        ");*/

        // Asigna el saldo anterior vencido del cliente.
        // $proveedor->saldo_anterior_vencido = $saldo_anterior_vencido->row();
        $proveedor->saldo_anterior_vencido = isset($saldo_anterior_vencido[$proveedor->id_proveedor])? $saldo_anterior_vencido[$proveedor->id_proveedor]: [];

        $proveedor->saldo_anterior = isset($saldo_anterior[$proveedor->id_proveedor])? $saldo_anterior[$proveedor->id_proveedor]: [];
        // $proveedor->saldo_anterior = $saldo_anterior->row();
        // $saldo_anterior->free_result();
        if( isset($proveedor->saldo_anterior->saldo) )
          $proveedor->saldo = $proveedor->saldo_anterior->saldo;

        /** Facturas ***/
        $sql_field_cantidad = '';
        if($all_clientes && $all_facturas)
          $sql_field_cantidad = ", (SELECT Sum(cp.cantidad) FROM compras_facturas AS cf
                                    INNER JOIN compras_productos AS cp ON cp.id_orden = cf.id_orden
                                    WHERE cf.id_compra = f.id_compra) AS cantidad_productos";
        $facturas = $this->db->query("SELECT id_compra, Date(fecha) AS fecha, Date(fecha) AS fecha_factura, serie, folio,
            (CASE tipo_documento WHEN 'fa' THEN 'FACTURA' ELSE 'REMISION' END)::text AS concepto, subtotal, importe_iva, total,
            Date(fecha_factura + (plazo_credito || ' days')::interval) AS fecha_vencimiento {$sql_field_cantidad}
          FROM compras as f
          WHERE id_proveedor = {$proveedor->id_proveedor}
            AND status <> 'ca' AND status <> 'b' AND id_nc IS NULL
            AND (Date(fecha) >= '{$fecha1}' AND Date(fecha) <= '{$fecha2}')
            {$sql} {$sqlext[1]}
          ORDER BY fecha ASC, folio ASC");
        $proveedor->saldo_facturas = 0;
        $proveedor->facturas = $facturas->result();
        $facturas->free_result();
        foreach ($proveedor->facturas as $key => $factura)
        {
          $proveedor->saldo += $factura->total;
          $proveedor->facturas[$key]->saldo = $factura->total;

          /** abonos **/
          $abonos = $this->db->query("SELECT id_abono, serie, folio, fecha, concepto, abono
            FROM (
              (
                SELECT
                  id_abono,
                  ''::text AS serie,
                  id_abono AS folio,
                  Date(fecha) AS fecha,
                  'Pago al proveedor'::text AS concepto,
                  total AS abono
                FROM
                  compras_abonos as fa
                WHERE id_compra = {$factura->id_compra} AND Date(fecha) <= '{$fecha2}'
              )
              UNION
              (
                SELECT
                  id_compra AS id_abono,
                  serie,
                  folio,
                  Date(fecha) AS fecha,
                  'NOTA CREDITO'::text AS concepto,
                  total AS abono
                FROM
                  compras
                WHERE status <> 'ca' AND status <> 'b' AND id_nc = {$factura->id_compra}
                  AND Date(fecha) <= '{$fecha2}'
              )
            ) AS ffs
            ORDER BY id_abono");
          $proveedor->facturas[$key]->abonos = $abonos->result();
          $abonos->free_result();

          $proveedor->facturas[$key]->abonos_total = 0;
          foreach ($proveedor->facturas[$key]->abonos as $keyab => $abono)
          {
            $proveedor->facturas[$key]->abonos_total += $abono->abono;
          }
          $proveedor->saldo -= $proveedor->facturas[$key]->abonos_total;
          $proveedor->facturas[$key]->saldo -= $proveedor->facturas[$key]->abonos_total;

          if(round($proveedor->facturas[$key]->saldo, 4) <= 0 && $all_facturas == false)
            unset($proveedor->facturas[$key]);
        }

        if( $proveedor->saldo > 0 || $all_clientes)
        {
          if($proveedor->saldo > 0 && $all_clientes == false)
            $response[] = $proveedor;
          elseif($all_clientes && count($proveedor->facturas) > 0)
            $response[] = $proveedor;
        }
      }
      $proveedores->free_result();

    return $response;
  }

  /**
   * Descarga el listado de cuentas por pagar en formato pdf
   */
  public function estadoCuentaPdf(){
    $res = $this->getEstadoCuentaData();
    // var_dump($res);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'ESTADO DE CUENTA DE PROVEEDORES';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
    $pdf->AliasNbPages();
    // $pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'R', 'L', 'R', 'R', 'R', 'L');
    $widths = array(28, 11, 20, 50, 23, 23, 23, 23);
    $header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cargos', 'Abonos', 'Saldo', 'F. Ven.');

    $total_saldo_cliente = 0;
    $totalVencido = 0;
    foreach($res as $key => $item){
      if (($item->saldo > 0 && $this->input->get('fcon_saldo')=='si') || $this->input->get('fcon_saldo')!='si')
      {
        $total_cargo = 0;
        $total_abono = 0;
        $total_saldo = 0;
        $totalVencido = 0;

        if (isset($item->saldo_anterior_vencido->saldo))
          $totalVencido += $item->saldo_anterior_vencido->saldo;

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
                MyString::formatoNumero($item->saldo_anterior->saldo, 2, '', false),
                '', '', '',
              );
            $pdf->SetXY(6, $pdf->GetY()-2);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($datos, false, false);
          }

          $datos = array(MyString::fechaATexto($factura->fecha, '/c'),
                  $factura->serie,
                  $factura->folio,
                  $factura->concepto,
                  MyString::formatoNumero($factura->total, 2, '', false),
                  '',
                  MyString::formatoNumero( ($factura->saldo) , 2, '', false),
                  MyString::fechaATexto($factura->fecha_vencimiento, '/c'),
                );
          //si esta vencido
              if (strtotime($this->input->get('ffecha2')) > strtotime($factura->fecha_vencimiento))
              {
                $totalVencido += $factura->saldo;
                if(MyString::formatoNumero( ($factura->saldo) , 2, '', false) != '0.00')
                  $pdf->SetFillColor(255,255,204);
                else
                  $pdf->SetFillColor(255,255,255);
              }else
                $pdf->SetFillColor(255,255,255);

          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, true, true);

          foreach ($factura->abonos as $keya => $abono)
          {
            $total_abono += $abono->abono;
            $datos = array('   '.MyString::fechaATexto($abono->fecha, '/c'),
                  $abono->serie,
                  $abono->folio,
                  $abono->concepto,
                  '',
                  '('.MyString::formatoNumero($abono->abono, 2, '', false).')',
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
            MyString::formatoNumero($total_cargo, 2, '', false),
            MyString::formatoNumero($total_abono, 2, '', false),
            MyString::formatoNumero($total_saldo, 2, '', false)), false);

        $saldo_cliente = ((isset($item->saldo_anterior->saldo)? $item->saldo_anterior->saldo: 0) + $total_cargo - $total_abono);
        $pdf->SetAligns(array('R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(50, 23, 23, 23));
        $pdf->SetX(65);
        $pdf->Row(array('Saldo Inicial', MyString::formatoNumero( (isset($item->saldo_anterior->saldo)? $item->saldo_anterior->saldo: 0) , 2, '', false), 'Vencido', MyString::formatoNumero($totalVencido, 2, '', false)), false);

        $pdf->SetX(65);
        $pdf->Row(array('(+) Cargos', MyString::formatoNumero($total_cargo, 2, '', false)), false);
        $pdf->SetX(65);
        $pdf->Row(array('(-) Abonos', MyString::formatoNumero($total_abono, 2, '', false)), false);
        $pdf->SetX(65);
        $pdf->Row(array('(=) Saldo Final', MyString::formatoNumero( $saldo_cliente , 2, '', false)), false);

        $total_saldo_cliente += $saldo_cliente;
      }
    }

    $pdf->SetXY(65, $pdf->GetY()+4);
    $pdf->Row(array('TOTAL SALDO DE CLIENTES', MyString::formatoNumero( $total_saldo_cliente , 2, '', false)), false);


    $pdf->Output('estado_cuenta.pdf', 'I');
  }

  public function estadoCuentaXls()
  {
    header('Content-type: application/vnd.ms-excel');
    header("Content-Disposition: attachment; filename=estado_cuenta.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getEstadoCuentaData();

    $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "ESTADO DE CUENTA DE CLIENTES";
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2');

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="8" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="8" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="8" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr style="font-weight:bold">
          <td style="width:80px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="width:60px;border:1px solid #000;background-color: #cccccc;">Serie</td>
          <td style="width:60px;border:1px solid #000;background-color: #cccccc;">Folio</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Concepto</td>
          <td style="width:80px;border:1px solid #000;background-color: #cccccc;">Cargos</td>
          <td style="width:80px;border:1px solid #000;background-color: #cccccc;">Abonos</td>
          <td style="width:80px;border:1px solid #000;background-color: #cccccc;">Saldo</td>
          <td style="width:80px;border:1px solid #000;background-color: #cccccc;">F. Ven.</td>
        </tr>';
    $total_saldo_cliente = 0;
    foreach ($res as $key => $value)
    {
      if (($value->saldo > 0 && $this->input->get('fcon_saldo')=='si') || $this->input->get('fcon_saldo')!='si')
      {
        $total_cargo = $total_abono = $total_saldo = $totalVencido = 0;

        if (isset($value->saldo_anterior_vencido->saldo))
              $totalVencido += $value->saldo_anterior_vencido->saldo;

        $html .= '
        <tr style="font-weight:bold;">
          <td>CLIENTE:</td>
          <td colspan="7" style="text-align:left;">'.$value->cuenta_cpi.'</td>
        </tr>
        <tr style="font-weight:bold;">
          <td>NOMBRE:</td>
          <td colspan="7">'.$value->nombre_fiscal.'</td>
        </tr>';

        foreach ($value->facturas as $keyf => $factura)
        {
          $total_cargo += $factura->total;
          $total_saldo += $factura->saldo;

          if( $keyf == 0 && isset($value->saldo_anterior->saldo) )
            $html .= '
            <tr>
              <td colspan="3"></td>
              <td>Saldo Inicial</td>
              <td>'.$value->saldo_anterior->saldo.'</td>
              <td colspan="3"></td>
            </tr>';

          //si esta vencido
          $color = '255,255,255';
              if (strtotime($this->input->get('ffecha2')) > strtotime($factura->fecha_vencimiento))
              {
                $totalVencido += $factura->saldo;
                if(MyString::formatoNumero( ($factura->saldo) , 2, '', false) != '0.00')
                  $color = '255,255,204';
              }

          $html .= '
          <tr>
            <td style="border:1px solid #000;background-color: rgb('.$color.');text-align:left;">'.MyString::fechaATexto($factura->fecha, '/c').'</td>
            <td style="border:1px solid #000;background-color: rgb('.$color.');">'.$factura->serie.'</td>
            <td style="border:1px solid #000;background-color: rgb('.$color.');">'.$factura->folio.'</td>
            <td style="border:1px solid #000;background-color: rgb('.$color.');">'.$factura->concepto.'</td>
            <td style="border:1px solid #000;background-color: rgb('.$color.');">'.$factura->total.'</td>
            <td style="border:1px solid #000;background-color: rgb('.$color.');"></td>
            <td style="border:1px solid #000;background-color: rgb('.$color.');">'.$factura->saldo.'</td>
            <td style="border:1px solid #000;background-color: rgb('.$color.');">'.MyString::fechaATexto($factura->fecha_vencimiento, '/c').'</td>
          </tr>';

          foreach ($factura->abonos as $keya => $abono)
          {
            $total_abono += $abono->abono;

            $html .= '
            <tr>
              <td>'.MyString::fechaATexto($abono->fecha, '/c').'</td>
              <td>'.$abono->serie.'</td>
              <td>'.$abono->folio.'</td>
              <td>'.$abono->concepto.'</td>
              <td></td>
              <td>'.$abono->abono.'</td>
              <td></td>
              <td></td>
            </tr>';
          }
        }

        $saldo_cliente = ((isset($value->saldo_anterior->saldo)? $value->saldo_anterior->saldo: 0) + $total_cargo - $total_abono);
        $total_saldo_cliente += $saldo_cliente;
        $html .= '<tr style="font-weight:bold">
          <td colspan="4"></td>
          <td style="border:1px solid #000;">'.$total_cargo.'</td>
          <td style="border:1px solid #000;">'.$total_abono.'</td>
          <td style="border:1px solid #000;">'.$total_saldo.'</td>
          <td></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="3"></td>
          <td style="border:1px solid #000;text-align:right;">Saldo Inicial</td>
          <td style="border:1px solid #000;">'.(isset($value->saldo_anterior->saldo)? $value->saldo_anterior->saldo: 0).'</td>
          <td style="border:1px solid #000;background-color: rgb(255,255,204);">Vencido</td>
          <td style="border:1px solid #000;background-color: rgb(255,255,204);">'.$totalVencido.'</td>
          <td></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="3"></td>
          <td style="border:1px solid #000;text-align:right;">(+) Cargos</td>
          <td style="border:1px solid #000;">'.$total_cargo.'</td>
          <td colspan="3"></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="3"></td>
          <td style="border:1px solid #000;text-align:right;">(-) Abonos</td>
          <td style="border:1px solid #000;">'.$total_abono.'</td>
          <td colspan="3"></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="3"></td>
          <td style="border:1px solid #000;text-align:right;">(=) Saldo Final</td>
          <td style="border:1px solid #000;">'.$saldo_cliente.'</td>
          <td colspan="3"></td>
        </tr>
        <tr>
          <td colspan="8"></td>
        </tr>';
      }
    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="3"></td>
          <td style="border:1px solid #000;">TOTAL SALDO DE CLIENTES</td>
          <td style="border:1px solid #000;">'.$total_saldo_cliente.'</td>
          <td colspan="3"></td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

  public function getRptComptasData()
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
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    $sql .= " AND Date(fa.fecha) BETWEEN '".$fecha1."' AND '".$fecha2."'";
    // $sql .= " AND f.status = 'pa'";

    $response = array();
    $response = $this->db->query(
        "SELECT p.id_proveedor, p.rfc, p.nombre_fiscal, Sum(ac.subtotal) AS subtotal, Sum(ac.importe_iva) AS importe_iva,
            Sum(ac.total_abono) AS total
          FROM proveedores p
          INNER JOIN (
            SELECT
              p.id_proveedor, Sum(fa.total) AS total_abono, Sum(((fa.total*100/f.total)*f.subtotal/100)) AS subtotal, Sum(f.total) AS total,
              Sum(((fa.total*100/f.total)*f.importe_iva/100)) AS importe_iva
              FROM compras AS f
                INNER JOIN compras_abonos AS fa ON fa.id_compra = f.id_compra
                INNER JOIN banco_movimientos_compras AS bmc ON bmc.id_compra_abono = fa.id_abono
                INNER JOIN proveedores p ON p.id_proveedor = f.id_proveedor
              WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_nc IS NULL
                  {$sql}
              GROUP BY p.id_proveedor
          ) AS ac ON p.id_proveedor = ac.id_proveedor
          GROUP BY p.id_proveedor
          ORDER BY p.nombre_fiscal ASC")->result();

    return $response;
  }

  public function rptComprasXls()
  {
    header('Content-type: application/vnd.ms-excel');
    header("Content-Disposition: attachment; filename=compras.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getRptComptasData();

    $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "ACTOS GRAVADOS";
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2');

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="5" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="5" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="5" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">RFC</td>
          <td style="width:400px;border:1px solid #000;background-color: #cccccc;">PROVEEDOR</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">IMPORTE</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">IVA</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">TOTAL</td>
        </tr>';
    $total_importe = $total_iva = 0;
    foreach ($res as $key => $value)
    {
      $html .= '<tr>
          <td style="width:150px;border:1px solid #000;">'.$value->rfc.'</td>
          <td style="width:400px;border:1px solid #000;">'.$value->nombre_fiscal.'</td>
          <td style="width:100px;border:1px solid #000;">'.$value->subtotal.'</td>
          <td style="width:100px;border:1px solid #000;">'.$value->importe_iva.'</td>
          <td style="width:150px;border:1px solid #000;">'.($value->subtotal+$value->importe_iva).'</td>
        </tr>';
        $total_importe += $value->subtotal;
        $total_iva += $value->importe_iva;
    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="2">TOTALES</td>
          <td style="border:1px solid #000;">'.$total_importe.'</td>
          <td style="border:1px solid #000;">'.$total_iva.'</td>
          <td style="border:1px solid #000;">'.($total_importe+$total_iva).'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

  public function rptFacturasVencidasData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];
    $sql .= " AND (Date(f.fecha) >= '{$_GET['ffecha1']}' AND Date(f.fecha) <= '{$_GET['ffecha2']}')";

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']{0}) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND f.id_empresa = {$_GET['did_empresa']}";
    }

    $response = array();
    $facturas = $this->db->query("SELECT
      f.id_compra,
      f.serie,
      f.folio,
      Date(f.fecha) AS fecha,
      Date(f.fecha_factura) AS fecha_factura,
      p.nombre_fiscal AS proveedor,
      COALESCE(f.total, 0) AS cargo,
      COALESCE(f.importe_iva, 0) AS iva,
      COALESCE(ac.abono, 0) AS abono,
      (COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(100,2) AS saldo,
      (CASE (COALESCE(f.total, 0) - COALESCE(ac.abono, 0)) WHEN 0 THEN 'Pagada' ELSE 'Pendiente' END) AS estado,
      Date(f.fecha_factura + (f.plazo_credito || ' days')::interval) AS fecha_vencimiento,
      (Date('2019-09-10'::timestamp with time zone)-Date(f.fecha_factura)) AS dias_transc,
      ('Factura ' || f.serie || f.folio) AS concepto, f.concepto AS concepto2,
      'f'::text as tipo, f.status,
      COALESCE((SELECT id_pago FROM banco_pagos_compras WHERE status = 'f' AND id_compra = f.id_compra), 0) AS en_pago
    FROM compras AS f
      INNER JOIN proveedores p ON p.id_proveedor = f.id_proveedor
      LEFT JOIN (
        SELECT id_compra, Sum(abono) AS abono
        FROM (
          (
            SELECT
              id_compra,
              Sum(total) AS abono
            FROM
              compras_abonos as fa
            WHERE Date(fecha) <= '2019-09-10'
            GROUP BY id_compra
          )
          UNION
          (
            SELECT
              id_nc AS id_compra,
              Sum(total) AS abonos
            FROM
              compras
            WHERE status <> 'ca' AND status <> 'b' AND id_nc IS NOT NULL
              AND id_proveedor = 173
              AND Date(fecha) <= '2019-09-10'
            GROUP BY id_nc
          )
        ) AS ffs
        GROUP BY id_compra
      ) AS ac ON f.id_compra = ac.id_compra  AND f.id_empresa = {$_GET['did_empresa']}
    WHERE f.status <> 'ca' AND f.id_nc IS NULL
      AND (COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(100,2) > 0
      AND Date(f.fecha + (f.plazo_credito || ' days')::interval) <= Date(Now())
      {$sql}
    ORDER BY proveedor ASC, fecha ASC, serie ASC, folio ASC");
    $response = $facturas->result();

    return $response;
  }
  public function rptFacturasVencidasPdf(){
    $res = $this->rptFacturasVencidasData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de Facturas Vencidas';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= ($this->input->get('tipo')==''? 'Todas': ($this->input->get('tipo')=='t'? 'Facturas': 'Remisiones') );
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'R', 'R', 'R', 'L');
    $widths = array(19, 25, 60, 25, 25, 25, 25);
    $header = array('Fecha', 'Factura', 'Concepto', 'Cargo', 'Abono', 'Saldo', 'F. Vencimiento');

    $proveedor_aux = '';
    $show_headers = false;
    foreach($res as $key => $factura){

      if ($proveedor_aux !== $factura->proveedor) {
        if ($pdf->GetY() >= $pdf->limiteY || $key==0) {
          $pdf->AddPage();
        }

        $pdf->SetFont('Arial','B',8);
        $pdf->SetX(6);
        $pdf->SetAligns(['L']);
        $pdf->SetWidths([200]);
        $pdf->Row([$factura->proveedor], false, false);

        $proveedor_aux = $factura->proveedor;
        $show_headers = true;
      }

      if($pdf->GetY() >= $pdf->limiteY || $key==0 || $show_headers){ //salta de pagina si exede el max
        $show_headers = false;
        if ($pdf->GetY() >= $pdf->limiteY) {
          $pdf->AddPage();
        }

        $pdf->SetFont('Arial','B',8);
        $pdf->SetFillColor(200,200,200);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY()+2);
      }

      $pdf->SetFont('Arial','',8);
      $datos = [
        MyString::fechaATexto($factura->fecha, '/c'),
        $factura->serie.$factura->folio,
        $factura->concepto,
        MyString::formatoNumero($factura->cargo, 2, '', false),
        MyString::formatoNumero($factura->abono, 2, '', false),
        MyString::formatoNumero($factura->saldo, 2, '', false),
        MyString::fechaATexto($factura->fecha_vencimiento, '/c'),
      ];
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);
    }


    $pdf->Output('facturas_vencidas.pdf', 'I');
  }

}