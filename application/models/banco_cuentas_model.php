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
		}

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
						);
			if(is_numeric($_POST['did_proveedor']))
				$data['id_proveedor'] = $this->input->post('did_proveedor');
		}

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
			$this->addDeposito($data_traspaso);
		}

		return array('error' => FALSE, 'ver_cheque' => ($data['metodo_pago']=='cheque'? 'si': 'no'), 'id_movimiento' => $id_movimiento);
	}




	/**
	 * ************************************
	 * ********** CUENTAS BANCARIAS *******
	 * @param  boolean $paginados [description]
	 * @return [type]             [description]
	 */
	public function getCuentas($paginados = true)
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
										substring(bc.numero from '....$') AS numero, bc.alias, bc.cuenta_cpi, 
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