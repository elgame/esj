<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_pagos_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getPagos($datos=array())
	{
    $this->load->model('cuentas_pagar_model');
    $this->load->model('proveedores_model');
		$sql = '';
		// //Filtros para buscar
		if(isset($datos['tipo_cuenta']))
      $sql .= " AND pc.is_banamex = '{$datos['tipo_cuenta']}'";
    if(isset($datos['con_cuenta']))
      $sql .= " AND bpc.id_cuenta IS NOT NULL";
    if(isset($datos['did_empresa']))
      $sql .= " AND c.id_empresa = '{$datos['did_empresa']}'";

		$response = array();
    $res_proveedores = $this->db->query("SELECT p.id_proveedor, p.nombre_fiscal, bpc.es_moral
            FROM banco_pagos_compras AS bpc INNER JOIN proveedores AS p ON p.id_proveedor = bpc.id_proveedor
            WHERE bpc.status = 'f'
            GROUP BY p.id_proveedor, bpc.es_moral
            ORDER BY p.nombre_fiscal ASC");
    $response = $res_proveedores->result();
    foreach ($response as $key => $value)
    {
      $value->pagos = $this->db->query("SELECT bpc.id_pago, c.serie, c.folio, bpc.referencia, bpc.ref_alfanumerica, bpc.monto, Date(c.fecha) AS fecha,
                                  COALESCE(pc.id_cuenta, 0) AS id_cuenta, COALESCE(pc.is_banamex, 'f') AS is_banamex, COALESCE(pc.cuenta, '') AS cuenta,
                                  COALESCE(pc.sucursal, 0) AS sucursal, b.codigo AS codigo_banco, c.id_compra, bpc.descripcion, bpc.modificado_banco,
                                  bpc.tcambio
                               FROM banco_pagos_compras AS bpc
                                 INNER JOIN compras AS c ON c.id_compra = bpc.id_compra
                                 LEFT JOIN proveedores_cuentas AS pc ON pc.id_cuenta = bpc.id_cuenta
                                 LEFT JOIN banco_bancos AS b ON b.id_banco = pc.id_banco
                               WHERE bpc.status = 'f' AND bpc.id_proveedor = {$value->id_proveedor} {$sql}
                               ORDER BY c.folio ASC")->result();
      if(count($value->pagos) > 0){
        foreach ($value->pagos as $kp => $pago) {
          // obtiene el nuevo total de la compra de acuerdo al tipo de cambio actual
          $data_fact = $this->cuentas_pagar_model->getDetalleVentaFacturaData($pago->id_compra, 'f', $pago->tcambio);
          $pago->new_total = $data_fact['new_total'];

          // $productos = $this->db->query(
          //                   "SELECT cantidad, precio_unitario, porcentaje_iva, porcentaje_retencion, porcentaje_ieps, tipo_cambio, total
          //                     FROM compras_productos
          //                     WHERE id_compra={$pago->id_compra}")->result();
          // $new_total = 0;
          // if (count($productos) > 0) {
          //   foreach ($productos as $kpp => $pproducto) {
          //     $pu = $pproducto->precio_unitario;
          //     if($pproducto->tipo_cambio > 0 && $pago->tcambio > 0) {
          //       $pu = ($pproducto->precio_unitario/$pproducto->tipo_cambio)*$pago->tcambio;
          //       $subtotal = $pproducto->cantidad*$pu;
          //       $new_total += floor( ($subtotal+($subtotal*$pproducto->porcentaje_iva/100)+
          //                     ($subtotal*$pproducto->porcentaje_ieps/100)-
          //                     ($subtotal*$pproducto->porcentaje_retencion/100)) * 100)/100;
          //     } else {
          //       $new_total += $pproducto->total;
          //     }
          //   }
          // }
          // $pago->new_total = $new_total==0? $pago->monto : $new_total;

        }

        $value->cuentas_proveedor = $this->proveedores_model->getCuentas($value->id_proveedor);
      }
      else
        unset($response[$key]);
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
          'id_cuenta'        => ($cuenta[0]{0}? $cuenta[0]: NULL),
          'referencia'       => $datos['ref_numerica'][$keyp][0],
          'ref_alfanumerica' => substr($datos['ref_alfanumerica'][$keyp][0], 0, 40),
          'monto'            => $datos['monto'][$keyp][$key],
          'descripcion'      => substr($datos['descripcion'][$keyp][0], 0, 40),
          'es_moral'         => ($datos['es_moral'][$keyp][0]=='si'? 't': 'f'),
          'modificado_banco' => 't',
          ), "id_pago = {$id_pago}");
      }
    }
  }

  public function layoutBanamex()
  {
    $this->load->model('banco_cuentas_model');
    $this->load->model('banco_layout_model');
    $tipo = $_GET['tipo'];
    $pagos = $this->getPagos(array(
        'did_empresa' => $_GET['did_empresa'],
        'tipo_cuenta' => ($_GET['tipo']=='ba'? 't': 'f'),
        'con_cuenta' => 'true' ));
    $cuenta_retiro = $this->banco_cuentas_model->getCuentaInfo($_GET['cuentaretiro'])['info'];

    $pagos_archivo = array();
    $total_pagar = $num_abonos = 0;
    foreach ($pagos as $key => $pago)
    {
      $total_proveedor = 0;
      foreach ($pago->pagos as $keyp => $value)
      {
        $total_pagar += $value->new_total; // monto
        $total_proveedor += $value->new_total; //monto
      }
      if ($total_proveedor > 0)
      {
        $num_abonos++;
        $pagos_archivo[] = array(
          'monto'              => $total_proveedor,
          'proveedor_sucursal' => $value->sucursal,
          'proveedor_cuenta'   => $value->cuenta,
          'ref_alfanumerica'   => $value->ref_alfanumerica,
          'beneficiario'       => $pago->nombre_fiscal,
          'es_moral'           => $pago->es_moral,
          'clave_banco'        => $value->codigo_banco,
          'ref_numerica'       => $value->referencia,
          'descripcion'        => $value->descripcion,
          );
      }
    }

    $cuentaa = explode('-', $cuenta_retiro->cuenta);
    if (count($cuentaa) == 3)
      $cuenta_retiro->cuenta = $cuentaa[1];

    $data = array(
      //Reg de Control
      'id_cuenta'      => $cuenta_retiro->id_cuenta,
      'numero_cliente' => $cuenta_retiro->no_cliente,
      'fecha_pago'     => date("Y-m-d"),
      'nombre_empresa' => $cuenta_retiro->nombre_fiscal,
      'description'    => 'Pago a proveedores',
      'toperacion'     => $tipo,
      //Reg Global
      'total_retiro' => $total_pagar,
      'sucursal'     => $cuenta_retiro->sucursal,
      'cuenta'       => $cuenta_retiro->cuenta,
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
    $pagos = $this->getPagos(array(
        'did_empresa' => $_GET['did_empresa'],
        'con_cuenta' => 'true' ));

    $pagos_archivo = array();
    foreach ($pagos as $key => $pago)
    {
      $total_pagar = $num_cargos = 0;
      $_POST['dfecha']             = date("Y-m-d");
      $_POST['dcuenta']            = $_GET['cuentaretiro'];
      $_POST['dreferencia']        = '';
      $_POST['dconcepto']          = $pago->nombre_fiscal;
      $_POST['fmetodo_pago']       = 'transferencia';
      $_POST['fcuentas_proveedor'] = '';
      $_POST['tcambio']            = (isset($pago->pagos[0]->tcambio)? $pago->pagos[0]->tcambio: 0);

      $_POST['factura_desc'] = array();
      $_POST['ids']          = array();
      $_POST['tipos']        = array();
      $_POST['montofv']      = array();
      $_POST['new_total']    = array();
      foreach ($pago->pagos as $keyp => $value)
      {
        $total_pagar += $value->monto;
        $num_cargos++;
        $_POST['factura_desc'][] = $value->serie.$value->folio;
        $_POST['ids'][]          = $value->id_compra;
        $_POST['tipos'][]        = 'f';
        $_POST['montofv'][]      = $value->monto;
        $_POST['new_total'][]    = $value->new_total;
        $this->db->update('banco_pagos_compras', array('status' => 't'), array('id_compra' => $value->id_compra));
      }
      $_POST['dmonto'] = $total_pagar;
      $this->cuentas_pagar_model->addAbonoMasivo();

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
        'monto'        => $datos['monto'],
        'tcambio'      => floatval($datos['tcambio']) ));
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
    if(isset($datos['con_cuenta']))
      $sql .= " AND bpc.id_cuenta IS NOT NULL";
    if(isset($datos['did_empresa']))
      $sql .= " AND c.id_empresa = '{$datos['did_empresa']}'";

    $response = array();
    $res_proveedores = $this->db->query("SELECT p.id_proveedor, p.nombre_fiscal, bpc.es_moral
            FROM banco_pagos_bascula AS bpc INNER JOIN proveedores AS p ON p.id_proveedor = bpc.id_proveedor
            WHERE bpc.status = 'f'
            GROUP BY p.id_proveedor, bpc.es_moral
            ORDER BY p.nombre_fiscal ASC");
    $response = $res_proveedores->result();
    foreach ($response as $key => $value)
    {
      $value->pagos = $this->db->query("SELECT bpc.id_pago, c.folio, bpc.referencia, bpc.ref_alfanumerica, bpc.monto, Date(c.fecha_bruto) AS fecha,
                                  COALESCE(pc.id_cuenta, 0) AS id_cuenta, COALESCE(pc.is_banamex, 'f') AS is_banamex, COALESCE(pc.cuenta, '') AS cuenta,
                                  COALESCE(pc.sucursal, 0) AS sucursal, b.codigo AS codigo_banco, c.id_bascula, bpc.descripcion, bpc.modificado_banco
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
          'id_cuenta'        => ($cuenta[0]{0}? $cuenta[0]: NULL),
          'referencia'       => $datos['ref_numerica'][$keyp][0],
          'ref_alfanumerica' => $datos['ref_alfanumerica'][$keyp][0],
          'monto'            => $datos['monto'][$keyp][$key],
          'descripcion'      => $datos['descripcion'][$keyp][0],
          'es_moral'         => ($datos['es_moral'][$keyp][0]=='si'? 't': 'f'),
          'modificado_banco' => 't',
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
          'monto'              => $total_proveedor,
          'proveedor_sucursal' => $value->sucursal,
          'proveedor_cuenta'   => $value->cuenta,
          'ref_alfanumerica'   => $value->ref_alfanumerica,
          'beneficiario'       => $pago->nombre_fiscal,
          'es_moral'           => $pago->es_moral,
          'clave_banco'        => $value->codigo_banco,
          'ref_numerica'       => $value->referencia,
          'descripcion'        => $value->descripcion,
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
    $this->load->model('bascula_facturas_model');
    $pagos = $this->getPagosBascula(array(
        'did_empresa' => $_GET['did_empresa'],
        'con_cuenta' => 'true' ));

    foreach ($pagos as $key => $pago)
    {
      $total_pagar = $num_cargos = 0;
      $datos = array(
        'dcuenta' => $_GET['cuentaretiro'],
        'dfecha' => date("Y-m-d"),
        'dreferencia' => '',
        'dconcepto' => 'PAGO LIQ. '.$this->getRangoPagoLiq($pago->pagos),
        'fmetodo_pago' => 'transferencia',
        'fcuentas_proveedor' => '',
        'boletas' => array(),
        'dmonto' => 0,
        'descrip' => '',
      );
      $datos_factura = array(
        'did_empresa'   => $_GET['did_empresa'],
        'did_proveedor' => $pago->id_proveedor,
        'dserie'        => '',
        'dfolio'        => 0,
        'dsubtotal'     => 0,
        'dtotal'        => 0,
        'boletas'      => array(),
      );
      foreach ($pago->pagos as $keyp => $value)
      {
        $total_pagar += $value->monto;
        $num_cargos++;
        $datos['boletas'][] = array(
          'id_bascula' => $value->id_bascula,
          'monto' => $value->monto,
          );
        $datos_factura['boletas'][] = $value->id_bascula;
        $datos['descrip'] .= '|'.$value->folio.' => '.String::formatoNumero($value->monto, 2, '', false);
        $this->db->update('banco_pagos_bascula', array('status' => 't'), array('id_bascula' => $value->id_bascula));
      }
      $_GET['did_empresa'] = $_GET['did_empresa'];
      $datos['dmonto'] = $total_pagar;
      $datos_factura['dtotal'] = $datos_factura['dsubtotal'] = $total_pagar;
      if(count($pago->pagos) > 0){
        $this->bascula_model->pago_basculas_banco($datos);
        $this->bascula_facturas_model->crearFactura($datos_factura);
      }

      // $this->db->update('banco_pagos_bascula', array('status' => 't'));
    }
  }

  public function getRangoPagoLiq($pagos)
  {
    $fecha_min = '';
    $fecha_max = '';
    foreach ($pagos as $keyp => $value)
    {
      if ( $fecha_min == '' || strtotime($fecha_min) > strtotime($value->fecha) )
        $fecha_min = $value->fecha;
      if ( $fecha_max == '' || strtotime($fecha_max) < strtotime($value->fecha) )
        $fecha_max = $value->fecha;
    }
    $str = str_replace('-', '/', $fecha_min).'-'.str_replace('-', '/', $fecha_max);
    if(strtotime($fecha_max) == strtotime($fecha_min))
      $str = str_replace('-', '/', $fecha_max);
    return $str;
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