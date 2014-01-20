<?php
class compras_model extends privilegios_model{

	function __construct(){
		parent::__construct();
	}

  /**
   * Obtiene el listado de facturas
   *
   * @return
   */
  public function getCompras($perpage = '40', $autorizadas = true)
  {
    $sql = '';
    //paginacion
    $params = array(
        'result_items_per_page' => $perpage,
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    );

    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql = " AND Date(co.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(co.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(co.fecha) = '".$this->input->get('ffecha2')."'";


    if($this->input->get('ffolio') != '')
    {
      $sql .= " AND co.folio = '".$this->input->get('ffolio')."'";
    }

    if($this->input->get('did_proveedor') != '')
    {
      $sql .= " AND p.id_proveedor = '".$this->input->get('did_proveedor')."'";
    }

    if($this->input->get('did_empresa') != '')
    {
      $sql .= " AND e.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if($this->input->get('fstatus') != '')
    {
      $sql .= " AND co.status = '".$this->input->get('fstatus')."'";
    }

    if($this->input->get('ftipo') != '')
    {
      $sql .= " AND co.isgasto = '".$this->input->get('ftipo')."'";
    }

    $query = BDUtil::pagination(
        "SELECT co.id_compra,
                co.id_proveedor, p.nombre_fiscal AS proveedor,
                co.id_empresa, e.nombre_fiscal as empresa,
                co.id_empleado, u.nombre AS empleado,
                co.serie, co.folio, co.condicion_pago, co.plazo_credito,
                co.tipo_documento, co.fecha, co.status, co.xml, co.isgasto
        FROM compras AS co
        INNER JOIN proveedores AS p ON p.id_proveedor = co.id_proveedor
        INNER JOIN empresas AS e ON e.id_empresa = co.id_empresa
        INNER JOIN usuarios AS u ON u.id = co.id_empleado
        WHERE 1 = 1 {$sql}
        ORDER BY (co.fecha, co.folio) DESC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'compras'           => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['compras'] = $res->result();

    return $response;
  }

	/**
	 * Obtiene la informacion de una compra
	 */
	public function getInfoCompra($id_compra, $info_basic=false)
  {
		$res = $this->db
            ->select("*")
            ->from('compras')
            ->where("id_compra = {$id_compra}")
            ->get();

    if($res->num_rows() > 0)
    {
			$response['info'] = $res->row();
      // $response['info']->fechaT = str_replace(' ', 'T', substr($response['info']->fecha, 0, 16));
      // $response['info']->fecha = substr($response['info']->fecha, 0, 10);

			$res->free_result();

      if($info_basic)
				return $response;

      // Carga la info de la empresa.
      // $this->load->model('empresas_model');
      // $empresa = $this->empresas_model->getInfoEmpresa($response['info']->id_empresa);
      // $response['info']->empresa = $empresa['info'];

      // Carga la info de la empresa.
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($response['info']->id_empresa);
      $response['info']->empresa = $empresa['info'];

      // Carga la info del proveedor.
			$this->load->model('proveedores_model');
			$prov = $this->proveedores_model->getProveedorInfo($response['info']->id_proveedor);
			$response['info']->proveedor = $prov['info'];

      //Productos
      $res = $this->db->query(
          "SELECT cf.id_compra, cp.id_orden, cp.num_row,
                  cp.id_producto, pr.nombre AS producto, pr.codigo, pr.id_unidad, pu.abreviatura, pu.nombre as unidad,
                  cp.id_presentacion, pp.nombre AS presentacion, pp.cantidad as presen_cantidad,
                  cp.descripcion, cp.cantidad, cp.precio_unitario, cp.importe,
                  cp.iva, cp.retencion_iva, cp.total, cp.porcentaje_iva,
                  cp.porcentaje_retencion, cp.status, pr.cuenta_cpi
           FROM compras_facturas AS cf
             INNER JOIN compras_productos AS cp ON cf.id_orden = cp.id_orden
             LEFT JOIN productos AS pr ON pr.id_producto = cp.id_producto
             LEFT JOIN productos_presentaciones AS pp ON pp.id_presentacion = cp.id_presentacion
             LEFT JOIN productos_unidades AS pu ON pu.id_unidad = pr.id_unidad
           WHERE cf.id_compra = {$id_compra}");

      $response['productos'] = $res->result();

      //gasolina 
      $res = $this->db->query(
          "SELECT id_compra, kilometros, litros, precio
           FROM compras_vehiculos_gasolina
           WHERE id_compra = {$id_compra}");

      $response['gasolina'] = $res->row();

      //veiculo
      $this->load->model('vehiculos_model');
      $prov = $this->vehiculos_model->getVehiculoInfo(floatval($response['info']->id_vehiculo));
      $response['vehiculo'] = $prov['info'];

			return $response;
		}else
			return false;
	}

  public function cancelar($compraId)
  {
    // cambia el status de la compra a cancelado.
    $this->db->update('compras', array('status' => 'ca'), array('id_compra' => $compraId));

    // obtiene las ordenes de compra que estan ligadas a la compra.
    $ordenes = $this->db->select('id_orden')->from('compras_facturas')->where('id_compra', $compraId)->get()->result();

    // recorre las ordenes y les cambia el status a aceptadas para que esten
    // disponibles y puedan ser ligadas a otra compra.
    foreach ($ordenes as $orden)
    {
      $this->db->update('compras_ordenes', array('status' => 'a'), array('id_orden' => $orden->id_orden));
    }

    return true;
  }

  public function updateXml($compraId, $proveedorId, $xml)
  {
    $compra = array(
      'subtotal'      => String::float($this->input->post('totalImporte')),
      'importe_iva'   => String::float($this->input->post('totalImpuestosTrasladados')),
      'retencion_iva' => String::float($this->input->post('totalRetencion')),
      'total'         => String::float($this->input->post('totalOrden')),
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
    $this->db->update('compras', $compra, array('id_compra' => $compraId));
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
    $path = APPPATH.'media/compras/cfdi/';

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