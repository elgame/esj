<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bascula_facturas_model extends CI_Model {


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
                                  COALESCE(pc.sucursal, 0) AS sucursal, b.codigo AS codigo_banco, c.id_compra, bpc.descripcion
                               FROM banco_pagos_compras AS bpc
                                 INNER JOIN compras AS c ON c.id_compra = bpc.id_compra
                                 LEFT JOIN proveedores_cuentas AS pc ON pc.id_cuenta = bpc.id_cuenta
                                 LEFT JOIN banco_bancos AS b ON b.id_banco = pc.id_banco
                               WHERE bpc.status = 'f' AND bpc.id_proveedor = {$value->id_proveedor} {$sql}
                               ORDER BY c.folio ASC")->result();
      if(count($value->pagos) > 0)
        $value->cuentas_proveedor = $this->proveedores_model->getCuentas($value->id_proveedor);
      else
        unset($response[$key]);
    }

		return $response;
	}

  public function crearFactura($datos)
  {
    $factura = array(
      'id_empresa'   => $datos['did_empresa'],
      'id_proveedor' => $datos['did_proveedor'],
      'serie'        => $datos['dserie'],
      'folio'        => $datos['dfolio'],
      'subtotal'     => $datos['dsubtotal'],
      'total'        => $datos['dtotal'],
    );
    if (isset($datos['id_pago'])) {
      $factura['id_pago'] = $datos['id_pago'];
    }
    $this->db->insert('bascula_facturas', $factura);
    $id_factura = $this->db->insert_id('bascula_facturas_id_factura_seq');
    foreach ($datos['boletas'] as $key => $value)
    {
      $this->db->insert('bascula_facturas_boletas', array(
        'id_factura' => $id_factura,
        'id_bascula' => $value,
        ));
    }
  }

  public function actualizarFactura($id_factura, $datos)
  {
    $this->db->update('bascula_facturas', array(
          'id_empresa'   => $datos['did_empresa'],
          'id_proveedor' => $datos['did_proveedor'],
          'serie'        => '',
          'folio'        => $datos['did_proveedor'],
          'subtotal'     => $datos['dsubtotal'],
          'total'        => $datos['dtotal'],
          ), "id_factura = {$id_factura}");
    $this->db->delete('bascula_facturas', "id_factura = {$id_factura}");
    foreach ($datos['boletas'] as $key => $value)
    {
      $this->db->insert('bascula_facturas_boletas', array(
        'id_factura' => $id_factura,
        'id_bascula' => $value,
        ));
    }
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */