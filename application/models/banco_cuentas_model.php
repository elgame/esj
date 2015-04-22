<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_cuentas_model extends banco_model {


	function __construct()
	{
		parent::__construct();
		$this->load->model('bitacora_model');
	}

	public function getSaldosCuentasData()
	{
		//Filtros para la consulta
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

		$_GET['vertodos'] = $this->input->get('vertodos')!=''? $this->input->get('vertodos'): 'all';
		$sql = $sql_todos = '';
		if($_GET['vertodos'] == 'tran')
			$sql_todos = " AND entransito = 't'";
		elseif($_GET['vertodos'] == 'notran')
			$sql_todos = " AND entransito = 'f'";

		if(isset($_GET['fid_banco']{0}))
			$sql .= " AND bb.id_banco = {$this->input->get('fid_banco')}";

		$this->load->model('empresas_model');
		$empresa = $this->empresas_model->getDefaultEmpresa();
		if(isset($_GET['did_empresa']{0}))
			$sql .= " AND e.id_empresa = {$this->input->get('did_empresa')}";
		else
			$sql .= " AND e.id_empresa = {$empresa->id_empresa}";

		$res = $this->db->query(
								"SELECT bc.id_cuenta, bb.id_banco, e.id_empresa, bb.nombre AS banco, e.nombre_fiscal,
									substring(bc.numero from '....$') AS numero, bc.alias, bc.cuenta_cpi,
									(
										(SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 't' AND id_cuenta = bc.id_cuenta AND Date(fecha) <= '{$fecha}') -
										(SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 'f' AND id_cuenta = bc.id_cuenta AND Date(fecha) <= '{$fecha}' {$sql_todos})
									) AS saldo
								FROM banco_cuentas AS bc
									INNER JOIN banco_bancos AS bb ON bc.id_banco = bb.id_banco
									INNER JOIN empresas AS e ON bc.id_empresa = e.id_empresa
								WHERE bc.status = 'ac' {$sql}
								ORDER BY bb.nombre ASC, bc.alias ASC");

		$response = array(
			'cuentas'        => array(),
			'total_saldos'   => 0,
		);
		if($res->num_rows() > 0)
		{
			$response['cuentas'] = $res->result();
			foreach ($response['cuentas'] as $key => $value) {
				$response['total_saldos'] += $value->saldo;
			}
		}

		return $response;
	}

	/**
	 * Descarga los saldos de las cuentas
	 */
	public function saldosCuentasPdf()
	{
		$res = $this->getSaldosCuentasData();

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Saldos de Cuentas '.(isset($_GET['dempresa']{0})? '<'.$this->input->get('dempresa').'>': '');
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		if($this->input->get('vertodos') == 'tran')
			$pdf->titulo3 .= 'Transito';
		elseif($this->input->get('vertodos') == 'notran')
			$pdf->titulo3 .= 'Cobrados';
		else
			$pdf->titulo3 .= 'Todos';

		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'L', 'L', 'R');
		$widths = array(50, 24, 80, 50);
		$header = array('Banco', 'Cuenta', 'Alias', 'Saldo');

		$total_cargo = 0;
		$total_abono = 0;
		$total_saldo = 0;

		foreach($res['cuentas'] as $key => $item){
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

			$datos = array($item->banco,
									$item->numero,
									$item->alias,
									String::formatoNumero($item->saldo, 2, '$', false) );

			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}

		$pdf->SetX(6);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetAligns(array('R', 'R'));
		$pdf->SetWidths(array(154, 50));
		$pdf->Row(array('Totales:',
										String::formatoNumero($res['total_saldos'], 2, '$', false)
									), true);

		$pdf->Output('saldos_cuentas.pdf', 'I');
	}

	public function saldosCuentasExcel(&$xls=null, $close=true)
	{
		$res = $this->getSaldosCuentasData();

		$this->load->library('myexcel');
		if($xls == null)
			$xls = new myexcel();

		$worksheet =& $xls->workbook->addWorksheet();

		$xls->titulo2 = 'Saldos de Cuentas '.(isset($_GET['dempresa']{0})? '<'.$this->input->get('dempresa').'>': '');
		$xls->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		if($this->input->get('vertodos') == 'tran')
			$xls->titulo4 .= 'Transito';
		elseif($this->input->get('vertodos') == 'notran')
			$xls->titulo4 .= 'Cobrados';
		else
			$xls->titulo4 .= 'Todos';

		$row=0;
		//Header
		$xls->excelHead($worksheet, $row, 8, array(
				array($xls->titulo2, 'format_title2'),
				array($xls->titulo3, 'format_title3'),
				array($xls->titulo4, 'format_title3')
		));

		$row +=3;
		$xls->excelContent($worksheet, $row, $res['cuentas'], array(
				'head' => array('Banco', 'Cuenta', 'Alias', 'Saldo'),
				'conte' => array(
						array('name' => 'banco', 'format' => 'format4', 'sum' => -1),
						array('name' => 'numero', 'format' => 'format4', 'sum' => -1),
						array('name' => 'alias', 'format' => 'format4', 'sum' => -1),
						array('name' => 'saldo', 'format' => 'format4', 'sum' => 0) )
		));

		if($close)
		{
			$xls->workbook->send('saldos_cuentas.xls');
			$xls->workbook->close();
		}
	}

	/**
	 * Movimientos de una cuenta
	 * @return [type] [description]
	 */
	public function getSaldoCuentaData()
	{
		//Filtros para la consulta
		$fecha1 = $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$fecha2 = $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		if ($_GET['ffecha1'] > $_GET['ffecha2']) {
			$fecha1 = $_GET['ffecha2'];
			$fecha2 = $_GET['ffecha1'];
		}

		$_GET['vertodos'] = $this->input->get('vertodos')!=''? $this->input->get('vertodos'): 'all';
		$sql = $sql_todos = $sqloperacion = $sqlsaldo = '';
		if($_GET['vertodos'] == 'tran')
			$sql_todos = " AND entransito = 't'";
		elseif($_GET['vertodos'] == 'notran')
			$sql_todos = " AND entransito = 'f'";

		if(isset($_GET['toperacion']{0})){
			$sqloperacion .= " AND m.metodo_pago = 'transferencia' AND m.tipo = 'f'";
		}

    if(isset($_GET['dcliente']{0})){
      $sqloperacion .= " AND (lower(Coalesce(c.nombre_fiscal, p.nombre_fiscal, a_nombre_de, '')) LIKE '%".mb_strtolower($this->input->get('dcliente'), 'UTF-8')."%')";
    }

    if(isset($_GET['tmetodo_pago']{0}) && $_GET['tmetodo_pago'] !== ''){
      $sqloperacion .= " AND m.metodo_pago = '{$_GET['tmetodo_pago']}'";
    }

    if(isset($_GET['tipomovimiento']{0})){
			$sqloperacion .= " AND m.tipo = '".$_GET['tipomovimiento']."'";
			$sqlsaldo .= " AND tipo = '".$_GET['tipomovimiento']."'";
		}

		/*if(isset($_GET['fid_banco']{0}))
			$sql .= " AND bb.id_banco = {$this->input->get('fid_banco')}";

		$this->load->model('empresas_model');
		$empresa = $this->empresas_model->getDefaultEmpresa();
		if(isset($_GET['did_empresa']{0}))
			$sql .= " AND e.id_empresa = {$this->input->get('did_empresa')}";
		else
			$sql .= " AND e.id_empresa = {$empresa->id_empresa}";*/

		// Se Obtiene el saldo anterior a fecha1
		$data_anterior = $this->db->query(
				"SELECT deposito, retiro, (deposito - retiro) AS saldo
				FROM (
					SELECT
						(SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 't' AND id_cuenta = {$this->input->get('id_cuenta')} AND Date(fecha) < '{$fecha1}' {$sqlsaldo}) AS deposito,

						(SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 'f' AND id_cuenta = {$this->input->get('id_cuenta')} AND Date(fecha) < '{$fecha1}' {$sql_todos} {$sqlsaldo}) AS retiro
					) AS m")->row();
		$data_anterior->id_movimiento  = '';
		$data_anterior->fecha          = '';
		$data_anterior->numero_ref     = '';
		$data_anterior->cli_pro        = '';
		$data_anterior->metodo_pago    = '';
		$data_anterior->entransito     = '';
    $data_anterior->salvo_buen_cobro     = '';
		$data_anterior->entransito_chk = '';
		$data_anterior->status         = '';
		$data_anterior->concepto       = '<strong>Saldo anterior al '.$fecha1.'</strong>';

		$response = array(
			'movimientos'         => array(),
			'total_saldos'        => $data_anterior->saldo,
			'total_deposito'      => 0,
			'total_retiro'        => 0,
			'cheques_no_cobrados' => $this->getChequesNoCobrados($this->input->get('id_cuenta')),
			'cuenta'              => $this->getCuentaInfo($this->input->get('id_cuenta')),
		);
		$response['movimientos'][] = $data_anterior;

		//Saldo en el rango de fecha
		$res = $this->db->query("SELECT
				m.id_movimiento,
				Date(m.fecha) AS fecha,
				m.numero_ref,
				m.concepto,
				Coalesce(c.nombre_fiscal, p.nombre_fiscal, a_nombre_de, '') AS cli_pro,
				Coalesce(c.id_cliente, p.id_proveedor, 0) AS id_cli_pro,
				m.monto,
				'' AS retiro,
				'' AS deposito,
				0 AS saldo,
				m.tipo,
				m.status,
				m.entransito,
        m.salvo_buen_cobro,
				m.metodo_pago,
				m.id_cuenta_proveedor,
        m.desglosar_iva
			FROM banco_movimientos AS m
				LEFT JOIN clientes AS c ON c.id_cliente = m.id_cliente
				LEFT JOIN proveedores AS p ON p.id_proveedor = m.id_proveedor
			WHERE m.id_cuenta = {$this->input->get('id_cuenta')}
				AND Date(m.fecha) BETWEEN '{$fecha1}' AND '{$fecha2}'
				AND (m.tipo = 't' OR (m.tipo = 'f' {$sql_todos}))
				{$sqloperacion}
			ORDER BY m.fecha ASC, m.id_movimiento ASC");

		if($res->num_rows() > 0)
		{
			foreach ($res->result() as $key => $item)
			{
				//estado de los cheques
				if ($item->entransito == 't' && $item->metodo_pago == 'cheque')
					$item->entransito = 'Trans';
				else
					$item->entransito = 'Aplic';

				if ($item->status == 't')
					$item->entransito .= '|Activo';
				else
					$item->entransito .= '|Cancelado';

				if ($item->tipo == 't') //deposito
				{
					if ($item->status == 't'){
						$response['total_saldos']   += $item->monto;
						$response['total_deposito'] += $item->monto;
					}
					$item->deposito = $item->monto;
					$item->saldo    = $response['total_saldos'];
				}else //retiros
				{
					if ($item->status == 't'){
						$response['total_saldos'] -= $item->monto;
						$response['total_retiro'] += $item->monto;
					}
					$item->retiro = $item->monto;
					$item->saldo  = $response['total_saldos'];
				}

				$response['movimientos'][] = $item;
			}
		}

		return $response;
	}

	/**
	 * Descarga los movimientos de una cuenta
	 */
	public function getSaldoCuentaPdf()
	{
		$res = $this->getSaldoCuentaData();

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Cuenta <'.$res['cuenta']['info']->banco.' - '.$res['cuenta']['info']->alias.'>';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		if($this->input->get('vertodos') == 'tran')
			$pdf->titulo3 .= 'Transito';
		elseif($this->input->get('vertodos') == 'notran')
			$pdf->titulo3 .= 'Cobrados';
		else
			$pdf->titulo3 .= 'Todos';

		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'C');
		$widths = array(17, 13, 40, 40, 20, 22, 22, 22, 11);
		$header = array('Fecha', 'Ref', 'Cliente / Proveedor', 'Concepto', 'M. pago', 'Deposito', 'Retiro', 'Saldo', 'Estado');

		$total_cargo = 0;
		$total_abono = 0;
		$total_saldo = 0;

		foreach($res['movimientos'] as $key => $item){
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();

				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, false);
			}

			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);

			$datos = array($item->fecha,
							$item->numero_ref,
							substr($item->cli_pro, 0, 35),
							strip_tags($item->concepto),
							$item->metodo_pago,
							String::formatoNumero($item->deposito, 2, '$', false),
              String::formatoNumero($item->retiro, 2, '$', false),
							String::formatoNumero($item->saldo, 2, '$', false),
							str_replace('|', ' ', $item->entransito),
							);

			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}

		$pdf->SetX(6);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetAligns(array('R', 'R', 'R', 'R'));
		$pdf->SetWidths(array(130, 22, 22, 22));
		$pdf->Row(array('Totales:',
					String::formatoNumero($res['total_deposito'], 2, '$', false),
          String::formatoNumero($res['total_retiro'], 2, '$', false),
					String::formatoNumero($res['total_saldos'], 2, '$', false),
				), false);

		$pdf->Output('saldo_cuenta.pdf', 'I');
	}

	public function getSaldoCuentaExcel(&$xls=null, $close=true)
	{
		$res = $this->getSaldoCuentaData();

		$this->load->library('myexcel');
		if($xls == null)
			$xls = new myexcel();

		$worksheet =& $xls->workbook->addWorksheet();

		$xls->titulo2 = 'Cuenta <'.$res['cuenta']['info']->banco.' - '.$res['cuenta']['info']->alias.'>';
		$xls->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
		if($this->input->get('vertodos') == 'tran')
			$xls->titulo4 .= 'Transito';
		elseif($this->input->get('vertodos') == 'notran')
			$xls->titulo4 .= 'Cobrados';
		else
			$xls->titulo4 .= 'Todos';

		$row=0;
		//Header
		$xls->excelHead($worksheet, $row, 8, array(
				array($xls->titulo2, 'format_title2'),
				array($xls->titulo3, 'format_title3'),
				array($xls->titulo4, 'format_title3')
		));

		$row +=3;
		$xls->excelContent($worksheet, $row, $res['movimientos'], array(
				'head' => array('Fecha', 'Ref', 'Cliente / Proveedor', 'Concepto', 'M pago', 'Deposito', 'Retiro', 'Saldo', 'Estado'),
				'conte' => array(
						array('name' => 'fecha', 'format' => 'format4', 'sum' => -1),
						array('name' => 'numero_ref', 'format' => 'format4', 'sum' => -1),
						array('name' => 'cli_pro', 'format' => 'format4', 'sum' => -1),
						array('name' => 'concepto', 'format' => 'format4', 'sum' => -1),
						array('name' => 'metodo_pago', 'format' => 'format4', 'sum' => -1),
						array('name' => 'deposito', 'format' => 'format4', 'sum' => 0),
            array('name' => 'retiro', 'format' => 'format4', 'sum' => 0),
						array('name' => 'saldo', 'format' => 'format4', 'sum' => 0),
						array('name' => 'entransito', 'format' => 'format4', 'sum' => -1),
						 )
		));

		if($close)
		{
			$xls->workbook->send('saldo_cuenta.xls');
			$xls->workbook->close();
		}
	}

	public function showConciliacion()
	{
		$res = $this->getSaldoCuentaData();
    // echo "<pre>";
    //   var_dump($res);
    // echo "</pre>";exit;

    $this->load->model('empresas_model');
    $defempresa = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = isset($_GET['did_empresa'])? $_GET['did_empresa']: $defempresa->id_empresa;
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
		$pdf->titulo2 = $res['cuenta']['info']->banco.' - '.$res['cuenta']['info']->alias;
		$pdf->titulo3 = 'CONCILIACION BANCARIA AL '.String::fechaATexto($this->input->get('ffecha2'), '/c')."\n";
    if(file_exists($empresa['info']->logo))
      $pdf->logo = $empresa['info']->logo;
    else
      $pdf->logo = '';

		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'L', 'L', 'L', 'L', 'R', 'R', 'R');
		$widths = array(17, 25, 40, 40, 20, 25, 18, 18);
		// $header = array('FECHA', 'REF', 'BENEFICIARIO', 'CONCEPTO', 'M. pago', 'Retiro', 'Deposito', 'Estado');
    $header = array('FECHA', 'REF', 'BENEFICIARIO', 'CONCEPTO', 'M. PAGO', 'IMPORTE', 'IVA', 'Ret IVA');

		$total_retiro = $total_retiro_sbc = 0;
		$total_deposito = $total_deposito_sbc = 0;
    $salvo_bc = array();

		foreach($res['movimientos'] as $key => $item){
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();

				if($pdf->PageNo() == 1){
					$pdf->SetFont('Arial','B',10);
					$pdf->SetX(6);
          $pdf->SetAligns(array('L', 'R'));
          $pdf->SetWidths(array(142, 50));
          $pdf->Row(array('SALDO DEL BANCO: ',
                String::formatoNumero($_GET['saldob'], 2, '$', false),
              ), false, false);
					$pdf->SetX(6);
					// $pdf->MultiCell(160, 8, "SALDO SEGUN CONTABILIDAD: ".$res['total_saldos'], '', "L", false);
          $pdf->MultiCell(160, 8, "MENOS: ", '', "L", false);
				}

				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, false);
			}

			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);

			if(substr($item->entransito, 0, 5) == 'Trans' || $item->salvo_buen_cobro == 't')
			{
        $monto_r = $item->retiro;
        $iva_r = $ret_iva_r = 0;
        $monto_d = $item->deposito;
        $iva_d = $ret_iva_d = 0;
        if ($item->desglosar_iva == 't'){
          $iva_r = $item->retiro-($item->retiro/1.16);
          $iva_d = $item->deposito-($item->deposito/1.16);
        }
        else
        {
          $info_compras = $this->db->query("SELECT
            bmc.id_movimiento, fa.ref_movimiento, fa.concepto, Sum(fa.total) AS total_abono,
            bc.cuenta_cpi, Sum(f.subtotal) AS subtotal, Sum(f.total) AS total, Sum(((fa.total*100/f.total)*f.importe_iva/100)) AS importe_iva,
            Sum(((fa.total*100/f.total)*f.retencion_iva/100)) AS retencion_iva, Sum(((fa.total*100/f.total)*f.importe_ieps/100)) AS importe_ieps, c.nombre_fiscal,
            c.cuenta_cpi AS cuenta_cpi_proveedor, bm.metodo_pago, Date(fa.fecha) AS fecha, 0 AS es_compra, 0 AS es_traspaso,
            'facturas'::character varying AS tipoo, 'f' AS desglosar_iva, '' as banco_cuenta_contpaq
          FROM compras AS f
            INNER JOIN compras_abonos AS fa ON fa.id_compra = f.id_compra
            INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta
            INNER JOIN proveedores AS c ON c.id_proveedor = f.id_proveedor
            INNER JOIN banco_movimientos_compras AS bmc ON bmc.id_compra_abono = fa.id_abono
            INNER JOIN banco_movimientos AS bm ON bm.id_movimiento = bmc.id_movimiento
          WHERE f.status <> 'ca' AND fa.poliza_egreso = 'f' AND bm.id_movimiento = {$item->id_movimiento}
          GROUP BY bmc.id_movimiento, fa.ref_movimiento, fa.concepto,
            bc.cuenta_cpi, c.nombre_fiscal, c.cuenta_cpi, bm.metodo_pago, Date(fa.fecha)
          ORDER BY bmc.id_movimiento ASC")->row();
          if(isset($info_compras->importe_iva))
          {
            $iva_r = $info_compras->importe_iva;
            $ret_iva_r = $info_compras->retencion_iva;
            $iva_d = $info_compras->importe_iva;
            $ret_iva_d = $info_compras->retencion_iva;
          }
        }

				$datos = array($item->fecha,
								$item->numero_ref,
								substr($item->cli_pro, 0, 35),
								strip_tags($item->concepto),
								$item->metodo_pago,
								String::formatoNumero($monto_r, 2, '$', false),
								String::formatoNumero($iva_r, 2, '$', false),
								String::formatoNumero($ret_iva_r, 2, '$', false),
								);

        if(substr($item->entransito, 0, 5) == 'Trans'){
  				$pdf->SetX(6);
  				$pdf->SetAligns($aligns);
  				$pdf->SetWidths($widths);
  				$pdf->Row($datos, false);

  				$total_retiro   += $item->retiro;
  				$total_deposito += $item->deposito;
        }elseif($item->salvo_buen_cobro == 't'){
          $info_compras = $this->db->query("SELECT
            bmc.id_movimiento, fa.ref_movimiento, fa.concepto, Sum(fa.total) AS total_abono,
            bc.cuenta_cpi, Sum(f.subtotal) AS subtotal, Sum(f.total) AS total, Sum(((fa.total*100/f.total)*f.importe_iva/100)) AS importe_iva,
            Sum(((fa.total*100/f.total)*f.retencion_iva/100)) AS retencion_iva, c.nombre_fiscal,
            c.cuenta_cpi AS cuenta_cpi_proveedor, bm.metodo_pago, Date(fa.fecha) AS fecha, 0 AS es_compra, 0 AS es_traspaso,
            'facturas'::character varying AS tipoo, 'f' AS desglosar_iva, '' as banco_cuenta_contpaq
          FROM facturacion AS f
            INNER JOIN facturacion_abonos AS fa ON fa.id_factura = f.id_factura
            INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta
            INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
            INNER JOIN banco_movimientos_facturas AS bmc ON bmc.id_abono_factura = fa.id_abono
            INNER JOIN banco_movimientos AS bm ON bm.id_movimiento = bmc.id_movimiento
          WHERE f.status <> 'ca' AND bm.id_movimiento = {$item->id_movimiento}
          GROUP BY bmc.id_movimiento, fa.ref_movimiento, fa.concepto,
            bc.cuenta_cpi, c.nombre_fiscal, c.cuenta_cpi, bm.metodo_pago, Date(fa.fecha)
          ORDER BY bmc.id_movimiento ASC")->row();
          if(isset($info_compras->importe_iva))
          {
            $iva_d = $info_compras->importe_iva;
            $ret_iva_d = $info_compras->retencion_iva;
          }

          $datos[5] = String::formatoNumero($item->deposito, 2, '$', false);
          $datos[6] = String::formatoNumero($iva_d, 2, '$', false);
          $datos[7] = String::formatoNumero($ret_iva_d, 2, '$', false);
          $salvo_bc[] = $datos;

          $total_retiro_sbc   += $item->retiro;
          $total_deposito_sbc += $item->deposito;
        }
			}
		}

		$pdf->SetX(6);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetAligns(array('R', 'R', 'R'));
		$pdf->SetWidths(array(142, 25, 20));
		$pdf->Row(array('SUMA DE CHEQUES EN TRANSITO:',
					String::formatoNumero($total_retiro, 2, '$', false),
					// String::formatoNumero($total_deposito, 2, '$', false),
				), false);

    if(count($salvo_bc) > 0)
    {
      $pdf->MultiCell(160, 8, "MAS: ", '', "L", false);
      foreach ($salvo_bc as $key => $value)
      {
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($value, false);
      }
      $pdf->SetX(6);
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetAligns(array('R', 'R', 'R'));
      $pdf->SetWidths(array(142, 25, 20));
      $pdf->Row(array('SUMA SALVO BUEN COBRO:',
            String::formatoNumero($total_deposito_sbc, 2, '$', false),
            // String::formatoNumero($total_deposito, 2, '$', false),
          ), false);
    }


		// $conciliado = $res['total_saldos']+$total_retiro-$total_deposito;
    $conciliado = $_GET['saldob']-$total_retiro+$total_deposito_sbc;
		$pdf->SetFont('Arial','B',10);
		$pdf->SetAligns(array('L', 'R'));
		$pdf->SetWidths(array(142, 50));
    $pdf->SetX(6);
		$pdf->Row(array('SALDO EN LIBROS AL '.String::fechaATexto($this->input->get('ffecha2'), '/c').':',
					String::formatoNumero($conciliado, 2, '$', false),
				), false, false);
		// $pdf->Row(array('DIFERENCIA:',
		// 			String::formatoNumero($_GET['saldob']-$conciliado, 2, '$', false),
		// 		), false, false);

		$pdf->Output('saldo_cuenta.pdf', 'I');
	}


	public function getChequesNoCobrados($id_cuenta)
	{
		$data = $this->db->query("SELECT Coalesce(Sum(monto), 0) AS r_nocob FROM banco_movimientos
			WHERE status = 't' AND id_cuenta = {$id_cuenta} AND lower(metodo_pago) = 'cheque'
				AND entransito = 't' AND tipo = 'f'")->row();
		return $data->r_nocob;
	}



	/**
	 * *************************************
	 * ******** MOVIMIENTOS EN CUENTAS *****
	 */
	public function addDeposito($data=NULL)
	{
		if ($data==NULL)
		{
			$data = array(
						'id_cuenta'   => $this->input->post('fcuenta'),
						'id_banco'    => $this->input->post('fbanco'),
						'fecha'       => $this->input->post('ffecha').':'.date("s"),
						'numero_ref'  => $this->input->post('freferencia'),
						'concepto'    => $this->input->post('fconcepto'),
						'monto'       => $this->input->post('fmonto'),
						'tipo'        => 't',
						'entransito'  => 'f',
						'metodo_pago' => $this->input->post('fmetodo_pago'),
						'a_nombre_de' => $this->input->post('dcliente'),
						);
			if(is_numeric($_POST['did_cliente']))
				$data['id_cliente'] = $this->input->post('did_cliente');
      if(is_numeric($_POST['did_cuentacpi']))
        $data['cuenta_cpi'] = $this->input->post('did_cuentacpi');
		}
		$data['concepto'] = substr($data['concepto'], 0, 120);

		$this->db->insert('banco_movimientos', $data);
		$id_movimiento = $this->db->insert_id('banco_movimientos', 'id_movimiento');

		// Bitacora
		$data_cuenta = $this->getCuentaInfo($data['id_cuenta']);
    $this->bitacora_model->_insert('banco_movimientos', $id_movimiento,
                            array(':accion'    => 'un deposito a la cuenta',
                            			':seccion' => 'banco',
                                  ':folio'     => $data_cuenta['info']->alias.' por '.String::formatoNumero($data['monto']),
                                  ':id_empresa' => $data_cuenta['info']->id_empresa,
                                  ':empresa'   => 'de '.$data_cuenta['info']->nombre_fiscal));

		return array('error' => FALSE, 'ver_cheque' => ($data['metodo_pago']=='cheque'? 'si': 'no'), 'id_movimiento' => $id_movimiento);
	}

	public function addRetiro($data=NULL, $data_comision=null, $data_traspaso=null, $traspaso=false, $comision=0)
	{
		if ($data==NULL)
		{
			$comision = (isset($_POST['fcomision']{0})? $_POST['fcomision']: 0);
			$traspaso = ($this->input->post('ftraspaso') == 'si'? true: false);
			$data = array(
						'id_cuenta'   => $this->input->post('fcuenta'),
						'id_banco'    => $this->input->post('fbanco'),
						'fecha'       => $this->input->post('ffecha').':'.date("s"),
						'numero_ref'  => $this->input->post('freferencia'),
						'concepto'    => $this->input->post('fconcepto'),
						'monto'       => $this->input->post('fmonto'),
						'tipo'        => 'f',
						'entransito'  => 'f',
						'metodo_pago' => $this->input->post('fmetodo_pago'),
						'a_nombre_de' => $this->input->post('dproveedor'),
						'clasificacion' => ($this->input->post('fmetodo_pago')=='cheque'? 'echeque': 'egasto'),
            'desglosar_iva' => ($this->input->post('fdesglosa_iva')=='t'? 't': 'f'),
						);
			if(is_numeric($_POST['did_proveedor']))
				$data['id_proveedor'] = $this->input->post('did_proveedor');
      if(is_numeric($_POST['did_cuentacpi']))
        $data['cuenta_cpi'] = $this->input->post('did_cuentacpi');
		}
		$data['concepto'] = substr($data['concepto'], 0, 120);

		//Valida que tenga saldo disponible
		// $cuenta = $this->getCuentas(false, $data['id_cuenta']);
		// if ($cuenta['cuentas'][0]->saldo < $data['monto']+$comision)
		// 	return array('error' => true, 'msg' => 30);

		$this->db->insert('banco_movimientos', $data);
		$id_movimiento = $this->db->insert_id('banco_movimientos', 'id_movimiento');

		// Bitacora
		$data_cuenta = $this->getCuentaInfo($data['id_cuenta']);
    $this->bitacora_model->_insert('banco_movimientos', $id_movimiento,
                            array(':accion'    => 'un retiro a la cuenta',
                            			':seccion' => 'banco',
                                  ':folio'     => $data_cuenta['info']->alias.' por '.String::formatoNumero($data['monto']),
                                  ':id_empresa' => $data_cuenta['info']->id_empresa,
                                  ':empresa'   => 'de '.$data_cuenta['info']->nombre_fiscal));

		//registrar la comision
		if($comision > 0)
		{
			if($data_comision == null)
			{
				$data_comision = array(
						'id_cuenta'   => $this->input->post('fcuenta'),
						'id_banco'    => $this->input->post('fbanco'),
						'fecha'       => $this->input->post('ffecha').':'.date("s"),
						'numero_ref'  => $this->input->post('freferencia'),
						'concepto'    => 'Comision por SPEI',
						'monto'       => $comision,
						'tipo'        => 'f',
						'entransito'  => 'f',
						'metodo_pago' => $this->input->post('fmetodo_pago'),
						'a_nombre_de' => $this->input->post('dproveedor'),
            'desglosar_iva' => 't',
						);
			}
			$this->addRetiro($data_comision);
		}

		//Registro el traspaso de dinero a otra cuenta
		if($traspaso)
		{
			if ($data_traspaso==null)
			{
				$data_traspaso = array(
						'id_cuenta'   => $this->input->post('fcuenta_destino'),
						'id_banco'    => $this->input->post('fbanco_destino'),
						'fecha'       => $this->input->post('ffecha').':'.date("s"),
						'numero_ref'  => $this->input->post('freferencia'),
						'concepto'    => $this->input->post('fconcepto'),
						'monto'       => $this->input->post('fmonto'),
						'tipo'        => 't',
						'entransito'  => 'f',
						'metodo_pago' => $this->input->post('fmetodo_pago'),
						);
			}
			$res_trasp = $this->addDeposito($data_traspaso);
			$this->db->update('banco_movimientos', array('id_traspaso' => $res_trasp['id_movimiento']), "id_movimiento = {$id_movimiento}");
		}

		return array('error' => FALSE, 'ver_cheque' => ($data['metodo_pago']=='cheque'? true: false), 'id_movimiento' => $id_movimiento);
	}

	public function generaCheque($id_movimiento)
	{
		$data = $this->getMovimientoInfo($id_movimiento);
		if(isset($data['info']->id_movimiento))
		{
			$cheque = new Cheque();
			$cheque->{'generaCheque_'.$data['info']->id_banco}(
						$data['info']->a_nombre_de,
						$data['info']->monto,
						substr($data['info']->fecha, 0, 10)
						);
		}else
			echo "No se obtubo la informacion del cheque";
	}

	/**
	 * Obtiene la informacion de una operacion
	 */
	public function getMovimientoInfo($id, $info_basic=true)
	{
		$res = $this->db
			->select('*')
			->from('banco_movimientos AS bm')
			->where("bm.id_movimiento = '".$id."'")
		->get();
		if($res->num_rows() > 0){
			$response['info'] = $res->row();
			$res->free_result();
			if($info_basic)
				return $response;

      $response['bascula'] = $this->db->query("SELECT * FROM banco_movimientos_bascula WHERE id_movimiento = {$response['info']->id_movimiento}")->result();
      $response['compras'] = $this->db->query("SELECT * FROM banco_movimientos_compras WHERE id_movimiento = {$response['info']->id_movimiento}")->result();
      $response['facturas'] = $this->db->query("SELECT * FROM banco_movimientos_facturas WHERE id_movimiento = {$response['info']->id_movimiento}")->result();
      $response['info']->es_ligado = count($response['bascula'])+count($response['compras'])+count($response['facturas']);

      $cuenta = $this->banco_cuentas_model->getCuentaInfo($response['info']->id_cuenta)['info'];

      $this->load->model('empresas_model');
      $response['empresa'] = $this->empresas_model->getInfoEmpresa($cuenta->id_empresa, true);
      $this->load->model('proveedores_model');
      $response['proveedor'] = $this->proveedores_model->getProveedorInfo($response['info']->id_proveedor, true);
      $this->load->model('clientes_model');
      $response['cliente'] = $this->clientes_model->getClienteInfo($response['info']->id_cliente, true);
      $this->load->model('cuentas_cpi_model');
      $response['cuenta_cpi'] = $this->cuentas_cpi_model->getCuentaInfo(array('cuenta' => $response['info']->cuenta_cpi), true);

			return $response;
		}else
			return false;
	}

  public function editMovimiento($datosP, $datosG)
  {
    $data = array(
            'id_cuenta' => $datosP['fcuenta'],
            'id_banco'  => $datosP['fbanco'],
            'fecha'     => $datosP['dfecha'],
            'concepto'  => $datosP['dconcepto'],
            );
    if (isset($datosP['did_proveedor']) && $datosP['did_proveedor'] != '')
    {
      $data['a_nombre_de'] = $datosP['dproveedor'];
      $data['id_proveedor'] = $datosP['did_proveedor'];
    }
    if (isset($datosP['did_cuentacpi']) && $datosP['did_cuentacpi'] != '')
      $data['cuenta_cpi'] = $datosP['did_cuentacpi'];
    if (isset($datosP['did_cliente']) && $datosP['did_cliente'] != '')
    {
      $data['a_nombre_de'] = $datosP['dcliente'];
      $data['id_cliente'] = $datosP['did_cliente'];
    }
    $this->db->update('banco_movimientos', $data, "id_movimiento = {$datosG['id_movimiento']}");

    if ($datosP['es_ligado'] > 0)
    {
      $movimiento = $this->getMovimientoInfo($datosG['id_movimiento'], false);
      foreach ($movimiento['bascula'] as $key => $value)
        $this->db->update('bascula_pagos', array('fecha' => $datosP['dfecha'], 'id_cuenta' => $datosP['fcuenta'], 'concepto' => $datosP['dconcepto']), "id_pago = {$value->id_bascula_pago}");
      foreach ($movimiento['compras'] as $key => $value)
        $this->db->update('compras_abonos', array('fecha' => $datosP['dfecha'], 'id_cuenta' => $datosP['fcuenta'], 'concepto' => $datosP['dconcepto']), "id_abono = {$value->id_compra_abono}");
      foreach ($movimiento['facturas'] as $key => $value)
        $this->db->update('facturacion_abonos', array('fecha' => $datosP['dfecha'], 'id_cuenta' => $datosP['fcuenta'], 'concepto' => $datosP['dconcepto']), "id_abono = {$value->id_abono_factura}");
    }

  }

	public function deleteMovimiento($id_movimiento, $cancelar=false)
	{
		//compras, ventas, notas de venta ligadas al movimiento bancario
		// $data_compras     = $this->db->query("SELECT bm.id_movimiento, bm.id_compra_abono, ca.id_compra
		// 	FROM banco_movimientos_compras AS bm INNER JOIN compras_abonos AS ca ON ca.id_abono = bm.id_compra_abono
		// 	WHERE bm.id_movimiento = {$id_movimiento}");
		$data_facturas    = $this->db->query("SELECT bm.id_movimiento, bm.id_abono_factura, af.id_factura
			FROM banco_movimientos_facturas AS bm INNER JOIN facturacion_abonos AS af ON af.id_abono = bm.id_abono_factura
			WHERE bm.id_movimiento = {$id_movimiento}")->result();
		$data_notas_venta = $this->db->query("SELECT bm.id_movimiento, bm.id_abono_venta_remision, af.id_venta
			FROM banco_movimientos_ventas_remision AS bm INNER JOIN facturacion_ventas_remision_abonos AS af ON af.id_abono = bm.id_abono_venta_remision
			WHERE bm.id_movimiento = {$id_movimiento}")->result();
		$data_compras = $this->db->query("SELECT bm.id_movimiento, bm.id_compra_abono, af.id_compra
			FROM banco_movimientos_compras AS bm INNER JOIN compras_abonos AS af ON af.id_abono = bm.id_compra_abono
			WHERE bm.id_movimiento = {$id_movimiento}")->result();
		$data_bascula = $this->db->query("SELECT bm.id_movimiento, bm.id_bascula_pago
			FROM banco_movimientos_bascula AS bm INNER JOIN bascula_pagos AS af ON af.id_pago = bm.id_bascula_pago
			WHERE bm.id_movimiento = {$id_movimiento}")->result();

		// Bitacora
		$inf_movi = $this->getMovimientoInfo($id_movimiento);
		$data_cuenta = $this->getCuentaInfo($inf_movi['info']->id_cuenta);
    $this->bitacora_model->_cancel('banco_movimientos', $id_movimiento,
                            array(':accion'     => ($cancelar? 'cancelo': 'elimino').' un '.($inf_movi['info']->tipo=='t'? 'deposito': 'retiro').' de la cuenta ', ':seccion' => 'banco',
                                  ':folio'      => $data_cuenta['info']->alias.' por '.String::formatoNumero($inf_movi['info']->monto),
                                  ':id_empresa' => $data_cuenta['info']->id_empresa,
                                  ':empresa'    => 'de '.$data_cuenta['info']->nombre_fiscal));

		if($cancelar)//cancelar movimiento
			$this->updateMovimiento($id_movimiento, array('status' => 'f') );
		else
			$this->db->delete('banco_movimientos', "id_movimiento = {$id_movimiento}");

		//cuendo es una venta (facturas) se cancelan los abonos a la venta (facturas)
		$this->load->model('cuentas_cobrar_model');
		if(count($data_facturas) > 0){
			foreach ($data_facturas as $key => $value) {
				$this->cuentas_cobrar_model->removeAbono($value->id_factura, 'f', $value->id_abono_factura);
			}
		}

		//cuendo es una venta (notas de venta) se cancelan los abonos a la venta (notas de venta)
		if(count($data_notas_venta) > 0){
			foreach ($data_notas_venta as $key => $value) {
				$this->cuentas_cobrar_model->removeAbono($value->id_venta, 'vr', $value->id_abono_venta_remision);
			}
		}

		//cuendo es una compra se cancelan los abonos a la compra
		$this->load->model('cuentas_pagar_model');
		if(count($data_compras) > 0){
			foreach ($data_compras as $key => $value) {
				$this->cuentas_pagar_model->removeAbono($value->id_compra, 'f', $value->id_compra_abono);
			}
		}

		//cuendo es pago de bascula cancelan los abonos
		$this->load->model('bascula_model');
		if(count($data_bascula) > 0){
			foreach ($data_bascula as $key => $value) {
				$this->bascula_model->cancelar_pago($value->id_bascula_pago, !$cancelar);
			}
		}

		return true;
	}

	public function updateMovimiento($id_movimiento, $data=null)
	{
		$this->db->update('banco_movimientos', $data, "id_movimiento = {$id_movimiento}");
		return true;
	}




	/**
	 * ************************************
	 * ********** CUENTAS BANCARIAS *******
	 * @param  boolean $paginados [description]
	 * @return [type]             [description]
	 */
	public function getCuentas($paginados = true, $id_cuenta=null, $data=array())
	{
		$sql = '';
		$query['total_rows'] = $params['result_items_per_page'] = $params['result_page'] = '';
		//paginacion
		if($paginados)
		{
			$this->load->library('pagination');
			$params = array(
					'result_items_per_page' => '60',
					'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
			);
			if($params['result_page'] % $params['result_items_per_page'] == 0)
				$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
		}
		//Filtros para buscar
		if($this->input->get('fnombre') != '')
			$sql = "WHERE ( lower(c.numero) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(c.alias) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(bb.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(e.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 'ac');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? ' WHERE ': ' AND ')." c.status = '".$this->input->get('fstatus')."'";

		if(isset($_GET['id_banco']{0}))
			$sql .= ($sql==''? ' WHERE ': ' AND ')." bb.id_banco = '{$this->input->get('id_banco')}'";

		if ($id_cuenta != null)
			$sql .= ($sql==''? ' WHERE ': ' AND ')." c.id_cuenta = {$id_cuenta}";

		if(isset($data['id_empresa']))
			$sql .= ($sql==''? ' WHERE ': ' AND ')." e.id_empresa = {$data['id_empresa']}";

 		$query['query'] =
 						"SELECT c.id_cuenta, c.id_empresa, c.id_banco, bb.nombre AS banco, e.nombre_fiscal,
 										c.numero, c.alias, c.cuenta_cpi, c.status,
 										(
											(SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 't' AND id_cuenta = c.id_cuenta) -
											(SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 'f' AND id_cuenta = c.id_cuenta)
										) AS saldo
						FROM banco_cuentas AS c
							INNER JOIN banco_bancos AS bb ON c.id_banco = bb.id_banco
							INNER JOIN empresas AS e ON c.id_empresa = e.id_empresa
						{$sql}
						ORDER BY (bb.nombre, c.alias) ASC";
		if($paginados)
			$query = BDUtil::pagination($query['query'], $params, true);

		$res = $this->db->query($query['query']);

		$response = array(
				'cuentas'        => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0)
		{
			$response['cuentas'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un proveedor a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addCuenta($data=NULL)
	{
		if ($data==NULL)
		{
			$data = array(
						'id_empresa' => $this->input->post('did_empresa'),
						'id_banco'   => $this->input->post('fbanco'),
						'numero'     => $this->input->post('fnumero'),
						'alias'      => $this->input->post('falias'),
						'cuenta_cpi' => $this->input->post('fcuenta_cpi'),
						'sucursal'   => $this->input->post('fsucursal'),
						);
		}

		$this->db->insert('banco_cuentas', $data);
		$id_cuenta = $this->db->insert_id('banco_cuentas', 'id_cuenta');

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_cuenta [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateCuenta($id_cuenta, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'id_empresa' => $this->input->post('did_empresa'),
						'id_banco'   => $this->input->post('fbanco'),
						'numero'     => $this->input->post('fnumero'),
						'alias'      => $this->input->post('falias'),
						'cuenta_cpi' => $this->input->post('fcuenta_cpi'),
						'sucursal'   => $this->input->post('fsucursal'),
            'numero_cheque'   => $this->input->post('fnumero_cheque'),
						);
		}

		$this->db->update('banco_cuentas', $data, array('id_cuenta' => $id_cuenta));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_cuenta [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getCuentaInfo($id_cuenta=FALSE, $basic_info=FALSE)
	{
		$id_cuenta = $id_cuenta? $id_cuenta: $_GET['id'];

		$sql_res = $this->db->query(
								"SELECT bc.id_cuenta, bb.id_banco, e.id_empresa, bb.nombre AS banco, e.nombre_fiscal, bc.numero_cheque, bc.no_cliente,
										substring(bc.numero from '....$') AS numero, bc.alias, bc.cuenta_cpi, bc.numero AS cuenta, bc.sucursal,
										(
											(SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 't' AND id_cuenta = bc.id_cuenta) -
											(SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 'f' AND id_cuenta = bc.id_cuenta)
										) AS saldo
                 FROM banco_cuentas AS bc
                 		INNER JOIN banco_bancos AS bb ON bc.id_banco = bb.id_banco
										INNER JOIN empresas AS e ON bc.id_empresa = e.id_empresa
                 WHERE bc.id_cuenta = {$id_cuenta}");
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();
		$sql_res->free_result();

		return $data;
	}

	// /**
	//  * Obtiene el listado de proveedores para usar ajax
	//  * @param term. termino escrito en la caja de texto, busca en el nombre
	//  * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
	//  */
	// public function getClientesAjax($sqlX = null){
	// 	$sql = '';
	// 	if ($this->input->get('term') !== false)
	// 		$sql = " AND lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";

 //    if ( ! is_null($sqlX))
 //      $sql .= $sqlX;

	// 	$res = $this->db->query(
 //      "SELECT id_cliente, nombre_fiscal, rfc, calle, no_exterior, no_interior, colonia, municipio, estado, cp, telefono, dias_credito
 //  			FROM clientes
 //  			WHERE status = 'ac'
 //        {$sql}
 //  			ORDER BY nombre_fiscal ASC
 //  			LIMIT 20"
 //    );

	// 	$response = array();
	// 	if($res->num_rows() > 0){
	// 		foreach($res->result() as $itm){
	// 			$response[] = array(
	// 					'id'    => $itm->id_cliente,
	// 					'label' => $itm->nombre_fiscal,
	// 					'value' => $itm->nombre_fiscal,
	// 					'item'  => $itm,
	// 			);
	// 		}
	// 	}

	// 	return $response;
	// }

  public function moverMovimiento($ids)
  {
    if ($ids !== '')
    {
      $ids = explode(',', $ids);

      foreach ($ids as $id)
      {
        $this->db->query("UPDATE banco_movimientos SET fecha = fecha + CAST('1 months' AS INTERVAL) WHERE id_movimiento = $id");
      }

      return 31;
    }

    return 32;
  }



  /*
   |-------------------------------------------------------------------------
   |  REPORTES
   |-------------------------------------------------------------------------
   */

    /**
   * REPORTE DE RENDIMIENTO
   * @return [type] [description]
   */
  public function rie_data()
  {
    $response = array();
    $sql = '';

    $_GET['ffecha1'] = isset($_GET['ffecha1'])? $_GET['ffecha1']: date("Y-m-1");
    $_GET['ffecha2'] = isset($_GET['ffecha2'])? $_GET['ffecha2']: date("Y-m-d");
    $sql .= " AND Date(bm.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."'";

    //Filtros de area
    $sql .= " AND bm.tipo = '".($this->input->get('ftipo')==='i'? 't': 'f')."'";


    // Obtenemos los rendimientos en los lotes de ese dia
    $query = $this->db->query(
      "SELECT bm.id_movimiento, Date(bm.fecha) AS fecha, bm.numero_ref, initcap(bm.metodo_pago) AS tipo,
      	bm.concepto, bm.monto, bm.a_nombre_de, e.id_empresa, e.nombre_fiscal, bc.alias AS cuenta
      FROM banco_movimientos bm
        INNER JOIN banco_cuentas bc ON bc.id_cuenta = bm.id_cuenta
        INNER JOIN empresas e ON e.id_empresa = bc.id_empresa
      WHERE bm.status = 't' {$sql}
      ORDER BY e.id_empresa ASC, bc.alias ASC");
    if($query->num_rows() > 0) {
    	$aux = '';
      foreach ($query->result() as $key => $value) {
      	if ($aux != $value->cuenta) {
      		$aux = $value->cuenta;
      	} else {
      		$value->cuenta = '';
      	}
      	$response[$value->id_empresa][] = $value;
      }
    }
    $query->free_result();


    return $response;
 }

 /**
  * Reporte de rendimientos de fruta
  * @return void
  */
 public function rie_pdf()
 {
    // Obtiene los datos del reporte.
    $data = $this->rie_data();
    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->titulo2 = "REPORTE BANCOS ACUMULADO POR EMPRESA";
    $pdf->titulo3 = ($_GET['ftipo']==='i'? 'INGRESOS': 'EGRESOS')." DEL {$fecha->format('d/m/Y')} al {$fecha2->format('d/m/Y')}\n";
    // $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
    // $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

    $pdf->AliasNbPages();
	  $pdf->AddPage();


    // Listado de Rendimientos
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetY($pdf->GetY()+2);

    $aligns = array('L', 'L', 'L', 'R', 'L', 'L');
    $widths = array(18, 40, 15, 22, 60, 50);
    $header = array('Fecha', 'Cuenta', 'Tipo', 'Importe', 'Beneficiario', 'Descripcion');

    $total_importes = 0;
    $total_importes_total = 0;

    foreach($data as $key => $movimiento)
    {
	    $pdf->SetFont('helvetica','B',8);
    	$pdf->SetX(6);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(205));
      $pdf->Row(array(
        $movimiento[0]->nombre_fiscal
      ), false, false);

      $total_importes = 0;
      foreach ($movimiento as $keym => $mov) {
	      if($pdf->GetY() >= $pdf->limiteY || $keym==0) //salta de pagina si exede el max
	      {
	        if($pdf->GetY() >= $pdf->limiteY)
	          $pdf->AddPage();

	        $pdf->SetFont('helvetica','B',8);
	        $pdf->SetTextColor(0,0,0);
	        $pdf->SetFillColor(200,200,200);
	        // $pdf->SetY($pdf->GetY()-2);
	        $pdf->SetX(6);
	        $pdf->SetAligns($aligns);
	        $pdf->SetWidths($widths);
	        $pdf->Row($header, true);
	      }

	      $pdf->SetFont('helvetica','', 8);
	      $pdf->SetTextColor(0,0,0);

	      // $pdf->SetY($pdf->GetY()-2);
	      $pdf->SetX(6);
	      $pdf->SetAligns($aligns);
	      $pdf->SetWidths($widths);
	      $pdf->Row(array(
	          $mov->fecha,
	          $mov->cuenta,
	          substr($mov->tipo, 0, 5),
	          String::formatoNumero($mov->monto, 2, '$', true),
	          substr($mov->a_nombre_de, 0, 33),
	          $mov->numero_ref.($mov->numero_ref!=''? ' | ': '').$mov->concepto,
	        ), false);

				$total_importes       += $mov->monto;
				$total_importes_total += $mov->monto;
      }

      //total
		  $pdf->SetX(64);
      $pdf->SetAligns(array('R'));
      $pdf->SetWidths(array(37));
      $pdf->Row(array(
        String::formatoNumero($total_importes, 2, '$', true)
      ), false);
    }

    //total general
    $pdf->SetFont('helvetica','B',8);
    $pdf->SetTextColor(0 ,0 ,0 );
    $pdf->SetX(64);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(37));
    $pdf->Row(array(
      String::formatoNumero($total_importes_total, 2, '$', true)
    ), false);


    $pdf->Output('reporte_banco.pdf', 'I');
 }

 public function rie_xls(){
    $data = $this->rie_data();

    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_banco.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "REPORTE BANCOS ACUMULADO POR EMPRESA";
    $titulo3 = ($_GET['ftipo']==='i'? 'INGRESOS': 'EGRESOS')." DEL {$fecha->format('d/m/Y')} al {$fecha2->format('d/m/Y')}";

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
    $total_importes_total = 0;
    foreach($data as $key => $movimiento)
    {
      $html .= '<tr>
          <td style="font-weight:bold;" colspan="6">'.$movimiento[0]->nombre_fiscal.'</td>
        </tr>';
      $total_importes = 0;
    	foreach ($movimiento as $keym => $mov) {
    		if($keym==0) //salta de pagina si exede el max
	      {
	        $html .= '<tr style="font-weight:bold">
	          <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
	          <td style="border:1px solid #000;background-color: #cccccc;">Cuenta</td>
	          <td style="border:1px solid #000;background-color: #cccccc;">Tipo</td>
	          <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
	          <td style="border:1px solid #000;background-color: #cccccc;">Beneficiario</td>
	          <td style="border:1px solid #000;background-color: #cccccc;">Descripcion</td>
	        </tr>';
	      }
	      $html .= '<tr>
          <td style="border:1px solid #000;">'.$mov->fecha.'</td>
          <td style="border:1px solid #000;">'.$mov->cuenta.'</td>
          <td style="border:1px solid #000;">'.substr($mov->tipo, 0, 5).'</td>
          <td style="border:1px solid #000;">'.$mov->monto.'</td>
          <td style="border:1px solid #000;">'.substr($mov->a_nombre_de, 0, 33).'</td>
          <td style="border:1px solid #000;">'.$mov->numero_ref.($mov->numero_ref!=''? ' | ': '').$mov->concepto.'</td>
        </tr>';

				$total_importes       += $mov->monto;
				$total_importes_total += $mov->monto;
    	}

    	//total
      $html .= '<tr>
        <td style="" colspan="2"></td>
        <td style="border:1px solid #000;" colspan="2">'.$total_importes.'</td>
        <td style="" colspan="2"></td>
      </tr>';

    }

    $html .= '<tr>
        <td style="" colspan="2"></td>
        <td style="border:1px solid #000;" colspan="2">'.$total_importes_total.'</td>
        <td style="" colspan="2"></td>
      </tr>';

    echo $html;
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */