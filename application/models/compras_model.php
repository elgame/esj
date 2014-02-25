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
                co.tipo_documento, co.fecha, co.status, co.xml, co.isgasto,
                co.tipo
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

      if($response['info']->isgasto == 't')
      {
        $response['productos'][] = new stdClass;
        $response['productos'][count($response['productos'])-1]->iva           = $response['info']->importe_iva;
        $response['productos'][count($response['productos'])-1]->retencion_iva = $response['info']->retencion_iva;
        $response['productos'][count($response['productos'])-1]->importe       = $response['info']->total;
        $response['productos'][count($response['productos'])-1]->retencion_isr = $response['info']->retencion_isr;
        $response['productos'][count($response['productos'])-1]->cuenta_cpi    = '';
      }

      //gasolina
      // $res = $this->db->query(
      //     "SELECT id_compra, kilometros, litros, precio
      //      FROM compras_vehiculos_gasolina
      //      WHERE id_compra = {$id_compra}");

      // $response['gasolina'] = $res->row();

      //veiculo
      // $this->load->model('vehiculos_model');
      // $prov = $this->vehiculos_model->getVehiculoInfo(floatval($response['info']->id_vehiculo));
      // $response['vehiculo'] = $prov['info'];

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
      'importe_ieps'  => String::float($this->input->post('totalIeps')),
      'retencion_iva' => String::float($this->input->post('totalRetencion')),
      'total'         => String::float($this->input->post('totalOrden')),
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
  public function creaDirectorioProveedorCfdi($proveedor, $isGasto = false)
  {
    $folder = $isGasto ? 'gastos' : 'compras';

    $path = APPPATH.'media/'.$folder.'/cfdi/';

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

    /**
     * Obtiene la informacion de una compra
     */
    public function getInfoNotaCredito($notaCreditoId, $info_basic=false)
    {
      $res = $this->db
              ->select("*")
              ->from('compras')
              ->where("id_compra = {$notaCreditoId}")
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
          "SELECT cnc.id_compra, cnc.num_row,
                  cnc.id_producto, pr.nombre AS producto, pr.codigo, pr.id_unidad, pu.abreviatura, pu.nombre as unidad,
                  cnc.id_presentacion, pp.nombre AS presentacion, pp.cantidad as presen_cantidad,
                  cnc.descripcion, cnc.cantidad, cnc.precio_unitario, cnc.importe,
                  cnc.iva, cnc.retencion_iva, cnc.total, cnc.porcentaje_iva,
                  cnc.porcentaje_retencion, cnc.observacion, pr.cuenta_cpi
           FROM compras_notas_credito_productos AS cnc
             LEFT JOIN productos AS pr ON pr.id_producto = cnc.id_producto
             LEFT JOIN productos_presentaciones AS pp ON pp.id_presentacion = cnc.id_presentacion
             LEFT JOIN productos_unidades AS pu ON pu.id_unidad = cnc.id_unidad
           WHERE cnc.id_compra = {$notaCreditoId}");

        $response['productos'] = $res->result();

        if($response['info']->isgasto == 't')
        {
          $response['productos'][] = new stdClass;
          $response['productos'][count($response['productos'])-1]->iva           = $response['info']->importe_iva;
          $response['productos'][count($response['productos'])-1]->retencion_iva = $response['info']->retencion_iva;
          $response['productos'][count($response['productos'])-1]->importe       = $response['info']->total;
          $response['productos'][count($response['productos'])-1]->retencion_isr = $response['info']->retencion_isr;
        }

        return $response;
      }else
        return false;
    }

  public function agregarNotaCredito($compraId, $data, $xml, $deGasto = false)
  {
    $compra = $this->compras_model->getInfoCompra($compraId);

    $datos = array(
      'id_proveedor' => $compra['info']->id_proveedor,
      'id_empleado' => $this->session->userdata('id_usuario'),
      'serie' => $data['serie'],
      'folio' => $data['folio'],
      // 'condicion_pago' => $data['asdasdasd'],
      // 'plazo_credito' => $data['asdasdasd'],
      // 'tipo_documento' => $data['asdasdasd'],
      'fecha' => $data['fecha'],
      'subtotal' => $data['totalImporte'],
      'importe_iva' => $data['totalImpuestosTrasladados'],
      'retencion_iva' => $data['totalRetencion'],
      'total' => $data['totalOrden'],
      'concepto' => 'Nota de Credito '. ($deGasto ? 'Gasto ' : 'Compra ') . $compraId,
      // 'isgasto' => $data['asdasdasd'],
      // 'status' => $data['asdasdasd'],
      // 'poliza_diario' => $data['asdasdasd'],
      'id_empresa' => $compra['info']->id_empresa,
      // 'id_vehiculo' => $data['asdasdasd'],
      // 'id_departamento' => $data['asdasdasd'],
      // 'tipo_vehiculo' => $data['asdasdasd'],
      'tipo' => 'nc',
      'id_nc' => $compraId,
    );

    if ($deGasto)
    {
      $datos['isgasto'] = 't';
    }

    if (isset($data['totalRetencionIsr']))
    {
      $datos['retencion_isr'] = $data['totalRetencionIsr'];
    }

    if (isset($data['observaciones']) && $data['observaciones'] !== '')
    {
      $datos['observaciones'] = $data['observaciones'];
    }

    // Realiza el upload del XML.
    if ($xml && $xml['tmp_name'] !== '')
    {
      $this->load->library("my_upload");
      $this->load->model('proveedores_model');

      $proveedor = $this->proveedores_model->getProveedorInfo($compra['info']->id_proveedor);
      $path      = $this->creaDirectorioProveedorCfdi($proveedor['info']->nombre_fiscal, $deGasto);

      $xmlName   = 'nc-'.($data['serie'] !== '' ? $data['serie'].'-' : '') . $data['folio'].'.xml';

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

    $this->db->insert('compras', $datos);
    $id = $this->db->insert_id();

    $productos = array();
    foreach ($data['concepto'] as $key => $concepto)
    {
      $productos[] = array(
        'id_compra' => $id,
        'num_row' => $key,
        'id_producto' => $data['productoId'][$key],
        'id_presentacion' => $data['asdasd'][$key],
        'descripcion' => $concepto,
        'cantidad' => $data['cantidad'][$key],
        'precio_unitario' => $data['valorUnitario'][$key],
        'importe' => $data['importe'][$key],
        'iva' => $data['trasladoTotal'][$key],
        'retencion_iva' => $data['retTotal'][$key],
        'total' => $data['total'][$key],
        'porcentaje_iva' => $data['trasladoPorcent'][$key],
        'porcentaje_retencion' => 0,
        'observacion' => $data['observacion'][$key],
        'id_unidad' => $data['unidad'][$key],
      );
    }

    if (count($productos) > 0)
    {
      $this->db->insert_batch('compras_notas_credito_productos', $productos);
    }

    return array('passes' => true, 'msg' => '5');
  }

  public function actualizarNotaCredito($notaCreditoId, $data, $xml, $deGasto = false)
  {
    $notaCredito = $this->compras_model->getInfoNotaCredito($notaCreditoId);

    $datos = array(
      // 'id_proveedor' => $compra['info']->id_proveedor,
      // 'id_empleado' => $this->session->userdata('id_usuario'),
      'serie' => $data['serie'],
      'folio' => $data['folio'],
      // 'condicion_pago' => $data['asdasdasd'],
      // 'plazo_credito' => $data['asdasdasd'],
      // 'tipo_documento' => $data['asdasdasd'],
      'fecha' => $data['fecha'],
      'subtotal' => $data['totalImporte'],
      'importe_iva' => $data['totalImpuestosTrasladados'],
      'retencion_iva' => $data['totalRetencion'],
      'total' => $data['totalOrden'],
      // 'concepto' => 'Nota de Credito Compra ' . $compraId,
      // 'isgasto' => $data['asdasdasd'],
      // 'status' => $data['asdasdasd'],
      // 'poliza_diario' => $data['asdasdasd'],
      // 'id_empresa' => $compra['info']->id_empresa,
      // 'id_vehiculo' => $data['asdasdasd'],
      // 'id_departamento' => $data['asdasdasd'],
      // 'tipo_vehiculo' => $data['asdasdasd'],
      // 'tipo' => 'nc',
      // 'id_nc' => $compraId,
    );

    if (isset($data['totalRetencionIsr']))
    {
      $datos['retencion_isr'] = $data['totalRetencionIsr'];
    }

    if (isset($data['observaciones']) && $data['observaciones'] !== '')
    {
      $datos['observaciones'] = $data['observaciones'];
    }

    // Realiza el upload del XML.
    if ($xml && $xml['tmp_name'] !== '')
    {
      unlink($notaCredito['info']->xml);
      $this->load->library("my_upload");
      $this->load->model('proveedores_model');

      $proveedor = $this->proveedores_model->getProveedorInfo($notaCredito['info']->id_proveedor);
      $path      = $this->creaDirectorioProveedorCfdi($proveedor['info']->nombre_fiscal, $deGasto);

      $xmlName   = 'nc-'.($data['serie'] !== '' ? $data['serie'].'-' : '') . $data['folio'].'.xml';

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

    $this->db->update('compras', $datos, array('id_compra' => $notaCreditoId));

    $this->db->delete('compras_notas_credito_productos', array('id_compra' => $notaCreditoId));

    $productos = array();
    foreach ($data['concepto'] as $key => $concepto)
    {
      $productos[] = array(
        'id_compra' => $notaCreditoId,
        'num_row' => $key,
        'id_producto' => $data['productoId'][$key],
        // 'id_presentacion' => $data['asdasd'][$key],
        'descripcion' => $concepto,
        'cantidad' => $data['cantidad'][$key],
        'precio_unitario' => $data['valorUnitario'][$key],
        'importe' => $data['importe'][$key],
        'iva' => $data['trasladoTotal'][$key],
        'retencion_iva' => $data['retTotal'][$key],
        'total' => $data['total'][$key],
        'porcentaje_iva' => $data['trasladoPorcent'][$key],
        'porcentaje_retencion' => 0,
        'observacion' => $data['observacion'][$key],
        'id_unidad' => $data['unidad'][$key],
      );
    }

    if (count($productos) > 0)
    {
      $this->db->insert_batch('compras_notas_credito_productos', $productos);
    }

    return array('passes' => true, 'msg' => '6');
  }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

  public function getProductoAjax($term, $def = 'codigo'){
    $sql = '';

    $res = $this->db->query(
       "SELECT p.*,
              pf.nombre as familia, pf.codigo as codigo_familia,
              pu.nombre as unidad, pu.abreviatura as unidad_abreviatura,
              (SELECT precio_unitario FROM compras_productos WHERE id_producto = p.id_producto ORDER BY id_orden DESC LIMIT 1) AS precio_unitario
        FROM productos AS p
        INNER JOIN productos_familias pf ON pf.id_familia = p.id_familia
        INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        WHERE p.status = 'ac' AND
              {$term}
              pf.status = 'ac'
        ORDER BY p.nombre ASC
        LIMIT 20");

    $response = array();
    if($res->num_rows() > 0)
    {
      foreach($res->result() as $itm)
      {
        $query = $this->db->select('*')
          ->from("productos_presentaciones")
          ->where("id_producto", $itm->id_producto)
          ->where("status", "ac")
          ->get();

        $itm->presentaciones = array();
        if ($query->num_rows() > 0)
        {
          $itm->presentaciones = $query->result();
        }

        if ($def == 'codigo')
        {
          $labelValue = $itm->codigo;
        }
        else
        {
          $labelValue = $itm->nombre;
        }

        $response[] = array(
            'id' => $itm->id_producto,
            'label' => $labelValue,
            'value' => $labelValue,
            'item' => $itm,
        );
      }
    }

    return $response;
  }

  public function getProductoByCodigoAjax($codigo)
  {
    $sql = '';

    $term = "lower(p.codigo) = '".mb_strtolower($codigo, 'UTF-8')."'";

    $res = $this->db->query(
       "SELECT p.*,
              pf.nombre as familia, pf.codigo as codigo_familia,
              pu.nombre as unidad, pu.abreviatura as unidad_abreviatura
        FROM productos as p
        INNER JOIN productos_familias pf ON pf.id_familia = p.id_familia
        INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        WHERE p.status = 'ac' AND
              {$term} AND
              pf.status = 'ac'
        ORDER BY p.nombre ASC
        LIMIT 20");

    $prod = array();
    if($res->num_rows() > 0)
    {
      $prod = $res->result();

      $query = $this->db->select('*')
        ->from("productos_presentaciones")
        ->where("id_producto", $prod[0]->id_producto)
        ->where("status", "ac")
        ->get();

      $prod[0]->presentaciones = array();
      if ($query->num_rows() > 0)
      {
        $prod[0]->presentaciones = $query->result();
      }
    }

    return $prod;
  }
}