<?php
class gastos_model extends privilegios_model{

	function __construct(){
		parent::__construct();
	}

  /**
   * Argegar un gasto.
   *
   * @param  array $data
   * @return array
   */
  public function agregar($data, $xml)
  {
    // datos del gasto.
    $datos = array(
      'id_empresa'     => $data['empresaId'],
      'id_proveedor'   => $data['proveedorId'],
      'id_empleado'    => $this->session->userdata('id_usuario'),
      'serie'          => $data['serie'],
      'folio'          => $data['folio'],
      'condicion_pago' => $data['condicionPago'],
      'plazo_credito'  => $data['plazoCredito'] !== '' ? $data['plazoCredito'] : 0,
      'tipo_documento' => $data['tipo_documento'],
      'fecha'          => str_replace('T', ' ', $data['fecha']),
      'subtotal'       => $data['subtotal'],
      'importe_iva'    => $data['iva'],
      'total'          => $data['total'],
      'concepto'       => $data['concepto'],
      'isgasto'        => 't',
      'status'         => $data['condicionPago'] ===  'co' ? 'pa' : 'p',
      'retencion_iva'  => $data['ret_iva'],
      'retencion_isr'  => $data['ret_isr'],
    );
    //Cuenta espesifica al gasto
    if(is_numeric($data['did_cuentacpi']))
        $datos['cuenta_cpi_gst'] = $data['did_cuentacpi'];

    //si se registra a un vehiculo
    if (isset($data['es_vehiculo']))
    {
      $datos['tipo_vehiculo'] = $data['tipo_vehiculo'];
      $datos['id_vehiculo'] = $data['vehiculoId'];
    }

    // //si es contado, se verifica que la cuenta tenga saldo
    // if ($datos['condicion_pago'] == 'co')
    // {
    //   $this->load->model('banco_cuentas_model');
    //   $cuenta = $this->banco_cuentas_model->getCuentas(false, $_POST['dcuenta']);
    //   if ($cuenta['cuentas'][0]->saldo < $datos['total'])
    //     return array('passes' => false, 'msg' => 30);
    // }

    // Realiza el upload del XML.
    if ($xml && $xml['tmp_name'] !== '')
    {
      $this->load->library("my_upload");
      $this->load->model('proveedores_model');

      $proveedor = $this->proveedores_model->getProveedorInfo($datos['id_proveedor']);
      $path      = $this->creaDirectorioProveedorCfdi($proveedor['info']->nombre_fiscal);

      $xmlName   = ($_POST['serie'] !== '' ? $_POST['serie'].'-' : '') . $_POST['folio'].'.xml';

      $config_upload = array(
        'upload_path'     => $path,
        'allowed_types'   => '*',
        'max_size'        => '2048',
        'encrypt_name'    => FALSE,
        'file_name'       => $xmlName,
      );
      $this->my_upload->initialize($config_upload);

      $xmlData = $this->my_upload->do_upload('xml');

      $xmlFile     = explode('application', $xmlData['full_path']);
      $datos['xml'] = 'application'.$xmlFile[1];
    }

    // inserta la compra
    $this->db->insert('compras', $datos);

    // obtiene el id de la compra insertada.
    $compraId = $this->db->insert_id();

    // Bitacora
    $this->bitacora_model->_insert('compras', $compraId,
                                    array(':accion'     => 'el gasto', ':seccion' => 'compras',
                                          ':folio'      => $datos['serie'].$datos['folio'],
                                          ':id_empresa' => $datos['id_empresa'],
                                          ':empresa'    => 'en '.$this->input->post('empresa')));

    $respons = array();
    // //si es contado, se registra el abono y el retiro del banco
    // if ($datos['condicion_pago'] == 'co')
    // {
    //   $this->load->model('cuentas_pagar_model');
    //   $data_abono = array('fecha'             => $data['fecha'],
    //                     'concepto'            => substr($data['concepto'], 0, 119),
    //                     'total'               => $data['total'],
    //                     'id_cuenta'           => $this->input->post('dcuenta'),
    //                     'ref_movimiento'      => $this->input->post('dreferencia'),
    //                     'id_cuenta_proveedor' => $this->input->post('fcuentas_proveedor') );
    //   $_GET['tipo'] = 'f';
    //   $respons = $this->cuentas_pagar_model->addAbono($data_abono, $compraId);
    // }

    //si se registra a un vehiculo
    if (isset($data['es_vehiculo']))
    {
      //si es de tipo gasolina se registra los litros
      if($data['tipo_vehiculo'] == 'g')
      {
        $this->db->insert('compras_vehiculos_gasolina', array(
          'id_compra'  => $compraId,
          'kilometros' => $data['dkilometros'],
          'litros'     => $data['dlitros'],
          'precio'     => $data['dprecio'],
          ));
      }
    }

    //Si el gasto trae ordenes logadas
    if (isset($data['ordenes']))
    {
      foreach ($data['ordenes'] as $key => $orden)
      {
        $this->db->insert('compras_facturas', array(
          'id_compra'  => $compraId,
          'id_orden' => $orden,
          ));
        $this->db->update('compras_ordenes', array('status' => 'f'), array('id_orden' => $orden));
      }
    }

    return array('passes' => true, 'id_compra' => $compraId, 'banco' => $respons);
  }

  // public function cancelar($compraId)
  // {
  //   // cambia el status de la compra a cancelado.
  //   $this->db->update('compras', array('status' => 'ca'), array('id_compra' => $compraId));

  //   // obtiene las ordenes de compra que estan ligadas a la compra.
  //   $ordenes = $this->db->select('id_orden')->from('compras_facturas')->where('id_compra', $compraId)->get()->result();

  //   // recorre las ordenes y les cambia el status a aceptadas para que esten
  //   // disponibles y puedan ser ligadas a otra compra.
  //   foreach ($ordenes as $orden)
  //   {
  //     $this->db->update('compras_ordenes', array('status' => 'a'), array('id_orden' => $orden->id_orden));
  //   }

  //   return true;
  // }

  public function updateXml($compraId, $proveedorId, $xml)
  {
    $compra = array(
      'subtotal'      => String::float($this->input->post('subtotal')),
      'importe_iva'   => String::float($this->input->post('iva')),
      'retencion_iva' => String::float($this->input->post('ret_iva')),
      'retencion_isr' => String::float($this->input->post('ret_isr')),
      'total'         => String::float($this->input->post('total')),
      'fecha'         => $this->input->post('fecha'),
    );

    // Realiza el upload del XML.
    if ($xml && $xml['tmp_name'] !== '')
    {
      $this->load->library("my_upload");
      $this->load->model('proveedores_model');

      $proveedor = $this->proveedores_model->getProveedorInfo($proveedorId);
      $path      = $this->creaDirectorioProveedorCfdi($proveedor['info']->nombre_fiscal);

      $xmlName   = ($_POST['serie'] !== '' ? $_POST['serie'].'-' : '') . $_POST['folio'].'.xml';

      $config_upload = array(
        'upload_path'     => $path,
        'allowed_types'   => '*',
        'max_size'        => '2048',
        'encrypt_name'    => FALSE,
        'file_name'       => $xmlName,
      );
      $this->my_upload->initialize($config_upload);

      $xmlData = $this->my_upload->do_upload('xml');

      $xmlFile     = explode('application', $xmlData['full_path']);

      $compra['xml'] = 'application'.$xmlFile[1];
    }

    // Bitacora
    $this->load->model('compras_model');
    $datoscompra = $this->compras_model->getInfoCompra($compraId);
    $id_bitacora = $this->bitacora_model->_update('compras', $compraId, $compra,
                              array(':accion'       => 'la compra', ':seccion' => 'compras',
                                    ':folio'        => $datoscompra['info']->serie.$datoscompra['info']->folio,
                                    ':id_empresa'   => $datoscompra['info']->id_empresa,
                                    ':empresa'      => 'en '.$datoscompra['info']->empresa->nombre_fiscal,
                                    ':id'           => 'id_compra',
                                    ':titulo'       => 'Compra'));

    $this->db->update('compras', $compra, array('id_compra' => $compraId));
  }


  public function saveLigarFactura($datos)
  {
    $data = array();
    foreach ($datos['idclasif'] as $key => $value)
    {
      $data[] = array(
        'id_compra'        => $datos['id_compra'],
        'id_factura'       => $datos['idfactura'][$key],
        'id_clasificacion' => $value,
        );
    }
    if(count($data) > 0){
      $this->db->delete('compras_facturacion_prodc', "id_compra = {$datos['id_compra']}");
      $this->db->insert_batch('compras_facturacion_prodc', $data);
    }
  }

  public function getFacturasLigadas($params, $multiple=false)
  {
    $sql = $multiple? "cf.id_compra IN(".implode(',', $params['idc']).")": "cf.id_compra = {$params['idc']}";
    $result = $this->db->query("SELECT cf.id_compra, f.id_empresa, f.id_factura, f.serie, f.folio, Date(f.fecha) AS fecha,
            c.nombre_fiscal AS cliente, fp.id_clasificacion, fp.nombre, ffp.importe, ffp.iva
          FROM compras_facturacion_prodc AS cf
            INNER JOIN facturacion AS f ON f.id_factura = cf.id_factura
            INNER JOIN clasificaciones AS fp ON cf.id_clasificacion = fp.id_clasificacion
            INNER JOIN clientes AS c ON f.id_cliente = c.id_cliente
            INNER JOIN facturacion_productos AS ffp ON f.id_factura = ffp.id_factura AND fp.id_clasificacion = ffp.id_clasificacion
          WHERE {$sql}
          ORDER BY fp.id_clasificacion ASC");
    $response = array();
    if($result->num_rows() > 0)
      $response = $result->result();

    $result->free_result();

    return $response;
  }

  public function getFacturasLibre($datos){
    $sql = '';
    $sql .= isset($datos['id_cliente']{0})? " AND f.id_cliente = {$datos['id_cliente']}": '';
    $sql .= isset($datos['folio']{0})? " AND f.folio = '{$datos['folio']}'": '';
    $result = $this->db->query("SELECT cf.id_compra, f.id_factura, f.serie, f.folio, f.fecha, f.cliente
          FROM compras_facturacion_prodc AS cf RIGHT JOIN
          (
            SELECT f.id_factura, f.serie, f.folio, Date(f.fecha) AS fecha, c.nombre_fiscal AS cliente
            FROM facturacion AS f
              INNER JOIN facturacion_productos AS fp ON f.id_factura = fp.id_factura
              INNER JOIN clientes AS c ON f.id_cliente = c.id_cliente
            WHERE f.id_empresa = {$datos['id_empresa']} AND fp.id_clasificacion = {$datos['id_clasificacion']}
              {$sql}
            GROUP BY f.id_factura, c.nombre_fiscal
            ORDER BY f.folio DESC
          ) AS f ON f.id_factura = cf.id_factura AND cf.id_clasificacion = {$datos['id_clasificacion']}
          WHERE cf.id_compra IS NULL");
    $response = array();
    if($result->num_rows() > 0)
      $response = $result->result();

    $result->free_result();

    return $response;
  }


  /*
   |------------------------------------------------------------------------
   | HELPERS
   |------------------------------------------------------------------------
   */

  /**
   * Crea el directorio por proveedor.
   *
   * @param  string $clienteNombre
   * @param  string $folioFactura
   * @return string
   */
  public function creaDirectorioProveedorCfdi($proveedor)
  {
    $path = APPPATH.'media/gastos/cfdi/';

    if ( ! file_exists($path))
    {
      // echo $path.'<br>';
      mkdir($path, 0777);
    }

    $path .= strtoupper($proveedor).'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    $path .= date('Y').'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    $path .= $this->mesToString(date('m')).'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    // $path .= ($serie !== '' ? $serie.'-' : '').$folio.'/';
    // if ( ! file_exists($path))
    // {
    //   // echo $path;
    //   mkdir($path, 0777);
    // }

    return $path;
  }

  /**
   * Regresa el MES que corresponde en texto.
   *
   * @param  int $mes
   * @return string
   */
  private function mesToString($mes)
  {
    switch(floatval($mes))
    {
      case 1: return 'ENERO'; break;
      case 2: return 'FEBRERO'; break;
      case 3: return 'MARZO'; break;
      case 4: return 'ABRIL'; break;
      case 5: return 'MAYO'; break;
      case 6: return 'JUNIO'; break;
      case 7: return 'JULIO'; break;
      case 8: return 'AGOSTO'; break;
      case 9: return 'SEPTIEMBRE'; break;
      case 10: return 'OCTUBRE'; break;
      case 11: return 'NOVIEMBRE'; break;
      case 12: return 'DICIEMBRE'; break;
    }
  }
}