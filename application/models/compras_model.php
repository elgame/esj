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
                co.tipo, co.id_nc, co.observaciones, co.total
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
        $response['productos'][count($response['productos'])-1]->cuenta_cpi    = $response['info']->cuenta_cpi_gst;//Cuenta del gasto
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
    $compra = $this->getInfoCompra($compraId);

    // cambia el status de la compra a cancelado.
    $this->db->update('compras', array('status' => 'ca'), array('id_compra' => $compraId));

    // obtiene las ordenes de compra que estan ligadas a la compra.
    $ordenes = $this->db->select('id_orden')->from('compras_facturas')->where('id_compra', $compraId)->get()->result();

    // recorre las ordenes y les cambia el status a aceptadas para que esten
    // disponibles y puedan ser ligadas a otra compra.
    $this->load->model('compras_ordenes_model');
    foreach ($ordenes as $orden)
    {
      $orden_prods = $this->compras_ordenes_model->info($orden->id_orden, true, false, $compraId);
      if(count($orden_prods['info'][0]->productos) > 0){
        $this->db->update('compras_ordenes', array('status' => 'a'), array('id_orden' => $orden->id_orden));
        $this->db->update('compras_productos', array('id_compra' => NULL), array('id_orden' => $orden->id_orden, 'id_compra' => $compraId));
      }
    }

    //si es una nota de credito la que se cancela, cambia de estado la compra a pendiente
    if($compra['info']->id_nc != '') {
      $this->db->update('compras', array('status' => 'p'), array('id_compra' => $compra['info']->id_nc));
      $tit_notac = 'nota de credito de la ';
    }

    // Bitacora
    $datoscompra = $this->getInfoCompra($compraId);
    $this->bitacora_model->_cancel('compras', $compraId,
                                    array(':accion'     => 'la '.$tit_notac.'compra', ':seccion' => 'compras',
                                          ':folio'      => $datoscompra['info']->folio,
                                          ':id_empresa' => $datoscompra['info']->id_empresa,
                                          ':empresa'    => 'de '.$datoscompra['info']->empresa->nombre_fiscal));

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
      'serie'         => $this->input->post('serie'),
      'folio'         => $this->input->post('folio'),
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
    $datoscompra = $this->getInfoCompra($compraId);
    $id_bitacora = $this->bitacora_model->_update('compras', $compraId, $compra,
                              array(':accion'       => 'la compra', ':seccion' => 'compras',
                                    ':folio'        => $compra['serie'].$compra['folio'],
                                    ':id_empresa'   => $datoscompra['info']->id_empresa,
                                    ':empresa'      => 'en '.$datoscompra['info']->empresa->nombre_fiscal,
                                    ':id'           => 'id_compra',
                                    ':titulo'       => 'Compra'));

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
    //Actualiza la compra si es que se paga
    $this->actualizaCompra($compraId, $datos['total']);

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
    if (isset($data['concepto']))
    {
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
    }

    if (count($productos) > 0)
    {
      $this->db->insert_batch('compras_notas_credito_productos', $productos);
    }
    //Actualiza la compra si es que se paga
    $this->actualizaCompra($notaCredito['info']->id_nc, $datos['total'], $notaCredito['info']->total);

    return array('passes' => true, 'msg' => '6');
  }
  /**
   * Actualiza el estado de la compra cuando se agregan nc
   * @param  [type]  $id_compra       [description]
   * @param  [type]  $total           [description]
   * @param  integer $total_nc_update [description]
   * @return [type]                   [description]
   */
  public function actualizaCompra($id_compra, $total, $total_nc_update=0)
  {
    $this->load->model('cuentas_pagar_model');
    $pagada = false;
    $inf_factura = $this->cuentas_pagar_model->getDetalleVentaFacturaData($id_compra);
    if (($inf_factura['saldo']+$total_nc_update) <= $total){ //se ajusta
      $pagada = true;
    }
    //verifica si la factura se pago, se cambia el status
    if($pagada){
      $this->db->update('compras', array('status' => 'pa'), "id_compra = {$id_compra}");
    }else
      $this->db->update('compras', array('status' => 'p'), "id_compra = {$id_compra}");
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

  public function getRptComprasData()
  {
    $sql = '';
      $idsproveedores = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];
    $sql .= " AND Date(c.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."'";

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']{0}) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
      $idsproveedores = " WHERE p.id_empresa = '".$this->input->get('did_empresa')."'";
    }

      $response = array();
      $productos = $this->db->query("SELECT
          c.id_compra, c.serie, c.folio, Date(c.fecha) AS fecha, p.nombre_fiscal, c.total, c.status, array_to_string(array_agg(cea.folio), ',') AS folio_almacen
        FROM compras c
          INNER JOIN compras_facturas cf ON c.id_compra = cf.id_compra
          INNER JOIN compras_entradas_almacen cea ON cf.id_orden = cea.id_orden
          INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
        WHERE c.status <> 'ca' {$sql}
        GROUP BY c.id_compra, c.serie, c.folio, Date(c.fecha), p.nombre_fiscal, c.total, c.status
        ORDER BY fecha ASC, folio ASC");
      $response = $productos->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getRptComprasPdf(){
    $res = $this->getRptComprasData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de Compras';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'R', 'L', 'R');
    $widths = array(20, 25, 80, 30, 20, 30);
    $header = array('Fecha', 'Factura', 'Proveedor', 'Importe', 'Estado', 'Folio Almacen');

    $familia = '';
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $factura){
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY()+2);
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $datos = array(String::fechaATexto($factura->fecha, '/c'),
        $factura->serie.$factura->folio,
        $factura->nombre_fiscal,
        String::formatoNumero($factura->total, 2, '', false),
        ($factura->status=='p'? 'Pendiente': 'Pagada'),
        $factura->folio_almacen,
        );
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);

      $proveedor_importe   += $factura->total;

    }
    $datos = array('Total General',
      '', '',
      String::formatoNumero($proveedor_importe, 2, '', false),
      '', '',
      );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row($datos, false);

    $pdf->Output('compras_proveedor.pdf', 'I');
  }

  public function getRptComprasProductosData()
  {
    $sql = '';
      $idsproveedores = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];
    $sql .= " AND Date(c.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."'";

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']{0}) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
      $idsproveedores = " WHERE p.id_empresa = '".$this->input->get('did_empresa')."'";
    }

      $response = array();
      $productos = $this->db->query("SELECT
          c.id_compra, c.serie, c.folio, Date(c.fecha) AS fecha, p.nombre_fiscal, c.total, c.status, cea.folio AS folio_almacen,
          pr.nombre, cp.cantidad, cp.precio_unitario, cp.importe, pu.abreviatura AS unidad
        FROM compras c
          INNER JOIN compras_facturas cf ON c.id_compra = cf.id_compra
          LEFT JOIN compras_entradas_almacen cea ON cf.id_orden = cea.id_orden
          INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
          INNER JOIN compras_productos cp ON c.id_compra = cp.id_compra
          INNER JOIN productos pr ON pr.id_producto = cp.id_producto
          INNER JOIN productos_unidades pu ON pu.id_unidad = pr.id_unidad
        WHERE c.status <> 'ca' {$sql}
        ORDER BY fecha ASC, id_compra ASC, folio ASC");
      $response = $productos->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getRptComprasProductosPdf(){
    $res = $this->getRptComprasProductosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de Compras y Productos';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'L', 'R', 'L', 'R', 'R');
    $widths = array(19, 20, 40, 40, 15, 12, 16, 20, 15, 12);
    $header = array('Fecha', 'Factura', 'Proveedor', 'Producto', 'Cantidad', 'Unidad', 'P.U.', 'Importe', 'Estado', 'Folio A');

    $compra_aux = '';
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $factura){
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY()+2);
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $datos = array( ($compra_aux !== $factura->id_compra? String::fechaATexto($factura->fecha, '/c'): ''),
        ($compra_aux !== $factura->id_compra? $factura->serie.$factura->folio: ''),
        ($compra_aux !== $factura->id_compra? $factura->nombre_fiscal: ''),
        $factura->nombre,
        String::formatoNumero($factura->cantidad, 2, '', false),
        $factura->unidad,
        String::formatoNumero($factura->precio_unitario, 2, '', false),
        String::formatoNumero($factura->importe, 2, '', false),
        ($compra_aux !== $factura->id_compra? ($factura->status=='p'? 'Pendiente': 'Pagada'): ''),
        $factura->folio_almacen,
        );
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);

      if($compra_aux !== $factura->id_compra )
        $compra_aux = $factura->id_compra;
      $proveedor_importe   += $factura->importe;

    }
    $datos = array('Total General',
      String::formatoNumero($proveedor_importe, 2, '', false),
      );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(162, 20));
    $pdf->Row($datos, false);

    $pdf->Output('compras_proveedor.pdf', 'I');
  }

}