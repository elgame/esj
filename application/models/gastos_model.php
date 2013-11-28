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
    );

    //si es contado, se verifica que la cuenta tenga saldo
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

    return array('passes' => true, 'id_compra' => $compraId);
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

      $this->db->update('compras', array('xml' => 'application'.$xmlFile[1]), array('id_compra' => $compraId));
    }
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