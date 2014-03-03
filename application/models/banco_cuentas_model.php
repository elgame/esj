<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_cuentas_model extends banco_model {


	function __construct()
	{
		parent::__construct();
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
		$sql = $sql_todos = $sqloperacion = '';
		if($_GET['vertodos'] == 'tran')
			$sql_todos = " AND entransito = 't'";
		elseif($_GET['vertodos'] == 'notran')
			$sql_todos = " AND entransito = 'f'";

		if(isset($_GET['toperacion']{0})){
			$sqloperacion .= " AND m.metodo_pago = 'transferencia' AND m.tipo = 'f'";
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
						(SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 't' AND id_cuenta = {$this->input->get('id_cuenta')} AND Date(fecha) < '{$fecha1}') AS deposito,

						(SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 'f' AND id_cuenta = {$this->input->get('id_cuenta')} AND Date(fecha) < '{$fecha1}' {$sql_todos}) AS retiro
					) AS m")->row();
		$data_anterior->id_movimiento  = '';
		$data_anterior->fecha          = '';
		$data_anterior->numero_ref     = '';
		$data_anterior->cli_pro        = '';
		$data_anterior->metodo_pago    = '';
		$data_anterior->entransito     = '';
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
				m.metodo_pago,
				m.id_cuenta_proveedor
			FROM banco_movimientos AS m
				LEFT JOIN clientes AS c ON c.id_cliente = m.id_cliente
				LEFT JOIN proveedores AS p ON p.id_proveedor = m.id_proveedor
			WHERE m.id_cuenta = {$this->input->get('id_cuenta')}
				AND Date(m.fecha) BETWEEN '{$fecha1}' AND '{$fecha2}'
				AND (m.tipo = 't' OR (m.tipo = 'f' {$sql_todos}))
				{$sqloperacion}
			ORDER BY m.fecha ASC");

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
		$widths = array(17, 15, 40, 40, 20, 20, 20, 20, 15);
		$header = array('Fecha', 'Ref', 'Cliente / Proveedor', 'Concepto', 'M. pago', 'Retiro', 'Deposito', 'Saldo', 'Estado');

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
							String::formatoNumero($item->retiro, 2, '$', false),
							String::formatoNumero($item->deposito, 2, '$', false),
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
		$pdf->SetWidths(array(112, 20, 20, 20));
		$pdf->Row(array('Totales:',
					String::formatoNumero($res['total_retiro'], 2, '$', false),
					String::formatoNumero($res['total_deposito'], 2, '$', false),
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
				'head' => array('Fecha', 'Ref', 'Cliente / Proveedor', 'Concepto', 'M pago', 'Retiro', 'Deposito', 'Saldo', 'Estado'),
				'conte' => array(
						array('name' => 'fecha', 'format' => 'format4', 'sum' => -1),
						array('name' => 'numero_ref', 'format' => 'format4', 'sum' => -1),
						array('name' => 'cli_pro', 'format' => 'format4', 'sum' => -1),
						array('name' => 'concepto', 'format' => 'format4', 'sum' => -1),
						array('name' => 'metodo_pago', 'format' => 'format4', 'sum' => -1),
						array('name' => 'retiro', 'format' => 'format4', 'sum' => 0),
						array('name' => 'deposito', 'format' => 'format4', 'sum' => 0),
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

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Conciliacion Bancaria <'.$res['cuenta']['info']->banco.' - '.$res['cuenta']['info']->alias.'>';
		$pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";

		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);

		$aligns = array('L', 'L', 'L', 'L', 'L', 'R', 'R', 'C');
		$widths = array(17, 15, 50, 50, 20, 20, 20, 15);
		$header = array('Fecha', 'Ref', 'Cliente / Proveedor', 'Concepto', 'M. pago', 'Retiro', 'Deposito', 'Estado');

		$total_retiro   = 0;
		$total_deposito = 0;

		foreach($res['movimientos'] as $key => $item){
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();

				if($pdf->PageNo() == 1){
					$pdf->SetFont('Arial','B',10);
					$pdf->SetX(6);
					$pdf->MultiCell(160, 8, "SALDO SEGUN ESTADO DE CUENTA: ".String::formatoNumero($_GET['saldob'], 2, '$', false), '', "L", false);
					$pdf->SetX(6);
					$pdf->MultiCell(160, 8, "SALDO SEGUN CONTABILIDAD: ".$res['total_saldos'], '', "L", false);
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

			if(substr($item->entransito, 0, 5) == 'Trans')
			{
				$datos = array($item->fecha,
								$item->numero_ref,
								substr($item->cli_pro, 0, 35),
								strip_tags($item->concepto),
								$item->metodo_pago,
								String::formatoNumero($item->retiro, 2, '$', false),
								String::formatoNumero($item->deposito, 2, '$', false),
								str_replace('|', ' ', $item->entransito),
								);

				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($datos, false);

				$total_retiro   += $item->retiro;
				$total_deposito += $item->deposito;
			}
		}

		$pdf->SetX(6);
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetAligns(array('R', 'R', 'R'));
		$pdf->SetWidths(array(152, 20, 20));
		$pdf->Row(array('Totales:',
					String::formatoNumero($total_retiro, 2, '$', false),
					String::formatoNumero($total_deposito, 2, '$', false),
				), false);


		$conciliado = $res['total_saldos']+$total_retiro-$total_deposito;
		$pdf->SetFont('Arial','B',10);
		$pdf->SetAligns(array('L', 'R'));
		$pdf->SetWidths(array(50, 30));
		$pdf->Row(array('SALDO  CONCILIADO:',
					String::formatoNumero($conciliado, 2, '$', false),
				), false, false);
		$pdf->Row(array('DIFERENCIA:',
					String::formatoNumero($_GET['saldob']-$conciliado, 2, '$', false),
				), false, false);

		$pdf->Output('saldo_cuenta.pdf', 'I');
	}


	public function getChequesNoCobrados($id_cuenta)
	{
		$data = $this->db->query("SELECT Coalesce(Sum(monto), 0) AS r_nocob FROM banco_movimientos
			WHERE status = 't' AND id_cuenta = {$id_cuenta} AND metodo_pago = 'cheque'
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
						'entransito'  => 't',
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
						'entransito'  => 't',
						'metodo_pago' => $this->input->post('fmetodo_pago'),
						'a_nombre_de' => $this->input->post('dproveedor'),
						'clasificacion' => ($this->input->post('fmetodo_pago')=='cheque'? 'echeque': 'egasto'),
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
						'entransito'  => 't',
						'metodo_pago' => $this->input->post('fmetodo_pago'),
						'a_nombre_de' => $this->input->post('dproveedor'),
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
						'entransito'  => 't',
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
	public function getMovimientoInfo($id, $info_basic=false)
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

			return $response;
		}else
			return false;
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
				$this->bascula_model->cancelar_pago($value->id_bascula_pago, true);
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
								"SELECT bc.id_cuenta, bb.id_banco, e.id_empresa, bb.nombre AS banco, e.nombre_fiscal,
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

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */