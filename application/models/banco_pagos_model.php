<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_pagos_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getPagos($datos=array())
	{
    $this->load->model('proveedores_model');
		$sql = '';
		// //Filtros para buscar
		if(isset($datos['tipo_cuenta']))
      $sql .= " AND pc.is_banamex = '{$datos['tipo_cuenta']}'";

		$response = array();
    $res_proveedores = $this->db->query("SELECT p.id_proveedor, p.nombre_fiscal
            FROM banco_pagos_compras AS bpc INNER JOIN proveedores AS p ON p.id_proveedor = bpc.id_proveedor
            WHERE bpc.status = 'f'
            GROUP BY p.id_proveedor
            ORDER BY p.nombre_fiscal ASC");
    $response = $res_proveedores->result();
    foreach ($response as $key => $value)
    {
      $value->pagos = $this->db->query("SELECT bpc.id_pago, c.serie, c.folio, bpc.referencia, bpc.ref_alfanumerica, bpc.monto, Date(c.fecha) AS fecha,
                                  COALESCE(pc.id_cuenta, 0) AS id_cuenta, COALESCE(pc.is_banamex, 'f') AS is_banamex, COALESCE(pc.cuenta, '') AS cuenta,
                                  COALESCE(pc.sucursal, 0) AS sucursal, b.codigo AS codigo_banco, c.id_compra
                               FROM banco_pagos_compras AS bpc
                               INNER JOIN compras AS c ON c.id_compra = bpc.id_compra
                               LEFT JOIN proveedores_cuentas AS pc ON pc.id_cuenta = bpc.id_cuenta
                               LEFT JOIN banco_bancos AS b ON b.id_banco = pc.id_banco
                               WHERE bpc.status = 'f' AND bpc.id_proveedor = {$value->id_proveedor} {$sql}
                               ORDER BY c.folio ASC")->result();
      $value->cuentas_proveedor = $this->proveedores_model->getCuentas($value->id_proveedor);
    }

		return $response;
	}

  public function actualizarPagos($datos)
  {
    foreach ($datos['cuenta_proveedor'] as $keyp => $value)
    {
      $cuenta = explode('-', $datos['cuenta_proveedor'][$keyp][0]);
      foreach ($datos['id_pago'][$keyp] as $key => $id_pago)
      {
        $this->db->update('banco_pagos_compras', array(
          'id_cuenta'        => $cuenta[0],
          'referencia'       => $datos['ref_numerica'][$keyp][0],
          'ref_alfanumerica' => $datos['ref_alfanumerica'][$keyp][0],
          'monto'            => $datos['monto'][$keyp][$key],
          ), "id_pago = {$id_pago}");
      }
    }
  }

  public function layoutBanamex()
  {
    $this->load->model('banco_cuentas_model');
    $this->load->model('banco_layout_model');
    $pagos = $this->getPagos(array('tipo_cuenta' => ($_GET['tipo']=='ba'? 't': 'f') ));
    $cuenta_retiro = $this->banco_cuentas_model->getCuentaInfo($_GET['cuentaretiro'])['info'];

    $pagos_archivo = array();
    $total_pagar = $num_abonos = 0;
    foreach ($pagos as $key => $pago)
    {
      $total_proveedor = 0;
      foreach ($pago->pagos as $keyp => $value)
      {
        $total_pagar += $value->monto;
        $total_proveedor += $value->monto;
      }
      if ($total_proveedor > 0)
      {
        $num_abonos++;
        $pagos_archivo[] = array(
          'monto' => $total_proveedor,
          'proveedor_sucursal' => $value->sucursal,
          'proveedor_cuenta' => $value->cuenta,
          'ref_alfanumerica' => $value->ref_alfanumerica,
          'beneficiario' => $pago->nombre_fiscal.'/',
          'instrucciones' => '',
          'clave_banco' => $value->codigo_banco,
          'ref_numerica' => $value->referencia,
          );
      }
    }

    $data = array(
      //Reg de Control
      'id_cuenta' => $cuenta_retiro->id_cuenta,
      'numero_cliente' => $cuenta_retiro->no_cliente,
      'fecha_pago'     => date("Y-m-d"),
      'nombre_empresa'  => $cuenta_retiro->nombre_fiscal,
      'description'  => 'Pago a proveedores',
      'toperacion'  => $_GET['tipo'],
      //Reg Global
      'total_retiro' => $total_pagar,
      'sucursal' => $cuenta_retiro->sucursal,
      'cuenta' => $cuenta_retiro->cuenta,
      //Reg Individual
      'pagos' => $pagos_archivo,
      //Reg Totales
      'num_abonos' => $num_abonos,
      'num_cargos' => 1,
      );

    $this->banco_layout_model->get($data);
  }

  public function aplicarPagos()
  {
    $this->load->model('cuentas_pagar_model');
    $this->load->model('banco_cuentas_model');
    $pagos = $this->getPagos();

    $pagos_archivo = array();
    foreach ($pagos as $key => $pago)
    {
      $total_pagar = $num_cargos = 0;
      $_POST['dfecha'] = date("Y-m-d");
      $_POST['dcuenta'] = $_GET['cuentaretiro'];
      $_POST['dreferencia'] = '';
      $_POST['dconcepto'] = $pago->nombre_fiscal;
      $_POST['fmetodo_pago'] = 'transferencia';
      $_POST['fcuentas_proveedor'] = '';

      $_POST['factura_desc'] = array();
      $_POST['ids']          = array();
      $_POST['tipos']        = array();
      $_POST['montofv']      = array();
      foreach ($pago->pagos as $keyp => $value)
      {
        $total_pagar += $value->monto;
        $num_cargos++;
        $_POST['factura_desc'][] = $value->serie.$value->folio;
        $_POST['ids'][]          = $value->id_compra;
        $_POST['tipos'][]        = 'f';
        $_POST['montofv'][]      = $value->monto;
      }
      $_POST['dmonto'] = $total_pagar;
      $this->cuentas_pagar_model->addAbonoMasivo();

      $this->db->update('banco_pagos_compras', array('status' => 't'));
    }
  }

	/**
	 * Obtiene la informacion de un banco
	 * @param  boolean $id_banco [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getInfoPagoCompra($id_pago=FALSE, $basic_info=FALSE)
	{
		$id_pago = ($id_pago==false)? $_GET['id']: $id_pago;

		$sql_res = $this->db->select("*" )
												->from("banco_pagos_compras")
												->where("id_pago", $id_pago)
												->get();
		$data['info'] = null;

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();
		$sql_res->free_result();

		if ($basic_info == False) {

		}

		return $data;
	}

  /**
   * Elimina un pago de compras
   */
  public function eliminarPago($id_pago)
  {
    $this->db->delete('banco_pagos_compras', "id_pago = {$id_pago}");
  }

  /**
   * Asigna o quita una compra que no se ha aplicado el pago
   */
  public function setCompra($datos)
  {
    $pago = $this->db->query("SELECT *
                               FROM banco_pagos_compras
                               WHERE status = 'f' AND id_compra = {$datos['id_compra']}")->row();
    if(isset($pago->id_pago))
    {
      $this->db->delete('banco_pagos_compras', "status = 'f' AND id_compra = {$datos['id_compra']}");
    }else
    { //se inserta el registro del pago
      $this->db->insert('banco_pagos_compras', array(
        'id_compra'    =>  $datos['id_compra'],
        'id_proveedor' =>  $datos['id_proveedor'],
        'monto'        => $datos['monto']));
    }
    return array('status' => 'ok');
  }


  /**
   * *****************************************************
   * ******* Bascula
   * *****************************************************
   */
  public function getPagosBascula($datos=array())
  {
    $this->load->model('proveedores_model');
    $sql = '';
    // //Filtros para buscar
    if(isset($datos['tipo_cuenta']))
      $sql .= " AND pc.is_banamex = '{$datos['tipo_cuenta']}'";

    $response = array();
    $res_proveedores = $this->db->query("SELECT p.id_proveedor, p.nombre_fiscal
            FROM banco_pagos_bascula AS bpc INNER JOIN proveedores AS p ON p.id_proveedor = bpc.id_proveedor
            WHERE bpc.status = 'f'
            GROUP BY p.id_proveedor
            ORDER BY p.nombre_fiscal ASC");
    $response = $res_proveedores->result();
    foreach ($response as $key => $value)
    {
      $value->pagos = $this->db->query("SELECT bpc.id_pago, c.folio, bpc.referencia, bpc.ref_alfanumerica, bpc.monto, Date(c.fecha_bruto) AS fecha,
                                  COALESCE(pc.id_cuenta, 0) AS id_cuenta, COALESCE(pc.is_banamex, 'f') AS is_banamex, COALESCE(pc.cuenta, '') AS cuenta,
                                  COALESCE(pc.sucursal, 0) AS sucursal, b.codigo AS codigo_banco, c.id_bascula
                               FROM banco_pagos_bascula AS bpc
                               INNER JOIN bascula AS c ON c.id_bascula = bpc.id_bascula
                               LEFT JOIN proveedores_cuentas AS pc ON pc.id_cuenta = bpc.id_cuenta
                               LEFT JOIN banco_bancos AS b ON b.id_banco = pc.id_banco
                               WHERE bpc.status = 'f' AND bpc.id_proveedor = {$value->id_proveedor} {$sql}
                               ORDER BY c.folio ASC")->result();
      $value->cuentas_proveedor = $this->proveedores_model->getCuentas($value->id_proveedor);
    }

    return $response;
  }

  public function actualizarPagosBascula($datos)
  {
    foreach ($datos['cuenta_proveedor'] as $keyp => $value)
    {
      $cuenta = explode('-', $datos['cuenta_proveedor'][$keyp][0]);
      foreach ($datos['id_pago'][$keyp] as $key => $id_pago)
      {
        $this->db->update('banco_pagos_bascula', array(
          'id_cuenta'        => $cuenta[0],
          'referencia'       => $datos['ref_numerica'][$keyp][0],
          'ref_alfanumerica' => $datos['ref_alfanumerica'][$keyp][0],
          'monto'            => $datos['monto'][$keyp][$key],
          ), "id_pago = {$id_pago}");
      }
    }
  }

  public function layoutBanamexBascula()
  {
    $this->load->model('banco_cuentas_model');
    $this->load->model('banco_layout_model');
    $pagos = $this->getPagosBascula(array('tipo_cuenta' => ($_GET['tipo']=='ba'? 't': 'f') ));
    $cuenta_retiro = $this->banco_cuentas_model->getCuentaInfo($_GET['cuentaretiro'])['info'];

    $pagos_archivo = array();
    $total_pagar = $num_abonos = 0;
    foreach ($pagos as $key => $pago)
    {
      $total_proveedor = 0;
      foreach ($pago->pagos as $keyp => $value)
      {
        $total_pagar += $value->monto;
        $total_proveedor += $value->monto;
      }
      if ($total_proveedor > 0)
      {
        $num_abonos++;
        $pagos_archivo[] = array(
          'monto' => $total_proveedor,
          'proveedor_sucursal' => $value->sucursal,
          'proveedor_cuenta' => $value->cuenta,
          'ref_alfanumerica' => $value->ref_alfanumerica,
          'beneficiario' => $pago->nombre_fiscal.'/'.'/',
          'instrucciones' => '',
          'clave_banco' => $value->codigo_banco,
          'ref_numerica' => $value->referencia,
          );
      }
    }

    $data = array(
      //Reg de Control
      'id_cuenta' => $cuenta_retiro->id_cuenta,
      'numero_cliente' => $cuenta_retiro->no_cliente,
      'fecha_pago'     => date("Y-m-d"),
      'nombre_empresa'  => $cuenta_retiro->nombre_fiscal,
      'description'  => 'Pago a proveedores',
      'toperacion'  => $_GET['tipo'],
      //Reg Global
      'total_retiro' => $total_pagar,
      'sucursal' => $cuenta_retiro->sucursal,
      'cuenta' => $cuenta_retiro->cuenta,
      //Reg Individual
      'pagos' => $pagos_archivo,
      //Reg Totales
      'num_abonos' => $num_abonos,
      'num_cargos' => 1,
      );

    $this->banco_layout_model->get($data);
  }

  public function aplicarPagosBascula()
  {
    $this->load->model('bascula_model');
    $this->load->model('banco_cuentas_model');
    $pagos = $this->getPagosBascula();

    foreach ($pagos as $key => $pago)
    {
      $total_pagar = $num_cargos = 0;
      $datos = array(
        'dcuenta' => $_GET['cuentaretiro'],
        'dfecha' => date("Y-m-d"),
        'dreferencia' => '',
        'dconcepto' => $pago->nombre_fiscal,
        'fmetodo_pago' => 'transferencia',
        'fcuentas_proveedor' => '',
        'boletas' => array(),
        'dmonto' => 0,
        'descrip' => '',
      );
      foreach ($pago->pagos as $keyp => $value)
      {
        $total_pagar += $value->monto;
        $num_cargos++;
        $datos['boletas'][] = array(
          'id_bascula' => $value->id_bascula,
          'monto' => $value->monto,
          );
        $datos['descrip'] .= '|'.$value->folio.' => '.String::formatoNumero($value->monto, 2, '', false);
      }
      $datos['dmonto'] = $total_pagar;
      $this->bascula_model->pago_basculas_banco($datos);

      $this->db->update('banco_pagos_bascula', array('status' => 't'));
    }
  }

  /**
   * Elimina un pago de bascula
   */
  public function eliminarPagoBascula($id_pago)
  {
    $this->db->delete('banco_pagos_bascula', "id_pago = {$id_pago}");
  }

  /**
   * Asigna o quita una compra que no se ha aplicado el pago
   */
  public function setBascula($datos)
  {
    $pago = $this->db->query("SELECT *
                               FROM banco_pagos_bascula
                               WHERE status = 'f' AND id_bascula = {$datos['id_bascula']}")->row();
    if(isset($pago->id_pago))
    {
      $this->db->delete('banco_pagos_bascula', "status = 'f' AND id_bascula = {$datos['id_bascula']}");
    }else
    { //se inserta el registro del pago
      $this->db->insert('banco_pagos_bascula', array(
        'id_bascula'   =>  $datos['id_bascula'],
        'id_proveedor' =>  $datos['id_proveedor'],
        'monto'        => $datos['monto']));
    }
    return array('status' => 'ok');
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */