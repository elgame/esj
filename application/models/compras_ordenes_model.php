<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class compras_ordenes_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
    $this->load->model('bitacora_model');
  }

  /**
   * Obtiene el listado de facturas
   *
   * @return
   */
  public function getOrdenes($perpage = '100', $autorizadas = true)
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
      $sql = " AND Date(co.fecha_creacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(co.fecha_creacion) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(co.fecha_creacion) = '".$this->input->get('ffecha2')."'";


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

    $sql .= $autorizadas ? " AND co.autorizado = 't'" : " AND co.autorizado = 'f'";

    $query = BDUtil::pagination(
        "SELECT co.id_orden,
                co.id_empresa, e.nombre_fiscal AS empresa,
                co.id_proveedor, p.nombre_fiscal AS proveedor,
                co.id_departamento, cd.nombre AS departamento,
                co.id_empleado, u.nombre AS empleado,
                co.id_autorizo, us.nombre AS autorizo,
                co.folio, co.fecha_creacion AS fecha, co.fecha_autorizacion,
                co.fecha_aceptacion, co.tipo_pago, co.tipo_orden, co.status,
                co.autorizado,
                (SELECT SUM(faltantes) FROM compras_productos WHERE id_orden = co.id_orden) as faltantes,
                (SELECT SUM(total) FROM compras_productos WHERE id_orden = co.id_orden) as total,
                (
                  (SELECT Count(*) FROM compras_productos WHERE id_orden = co.id_orden) -
                  (SELECT Count(*) FROM compras_productos WHERE id_orden = co.id_orden AND id_compra IS NULL)
                ) as prod_sincompras
        FROM compras_ordenes AS co
        INNER JOIN empresas AS e ON e.id_empresa = co.id_empresa
        INNER JOIN proveedores AS p ON p.id_proveedor = co.id_proveedor
        INNER JOIN compras_departamentos AS cd ON cd.id_departamento = co.id_departamento
        INNER JOIN usuarios AS u ON u.id = co.id_empleado
        LEFT JOIN usuarios AS us ON us.id = co.id_autorizo
        WHERE co.status <> 'n'  {$sql}
        ORDER BY (co.fecha_creacion, co.folio) DESC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'ordenes'           => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['ordenes'] = $res->result();

    return $response;
  }

  /**
   * Agrega una orden de compra
   *
   * @return array
   */
  public function agregar()
  {
    $data = array(
      'id_empresa'      => $_POST['empresaId'],
      'id_proveedor'    => $_POST['proveedorId'],
      'id_departamento' => $_POST['departamento'],
      'id_empleado'     => $this->session->userdata('id_usuario'),
      'folio'           => $_POST['folio'],
      'fecha_creacion'  => str_replace('T', ' ', $_POST['fecha']),
      'tipo_pago'       => $_POST['tipoPago'],
      'tipo_orden'      => $_POST['tipoOrden'],
      'solicito'        => $_POST['solicito'],
      'id_cliente'      => (is_numeric($_POST['clienteId'])? $_POST['clienteId']: NULL),
      'descripcion'     => $_POST['descripcion'],
    );

    //si se registra a un vehiculo
    if (isset($_POST['es_vehiculo']))
    {
      $data['tipo_vehiculo'] = $_POST['tipo_vehiculo'];
      $data['id_vehiculo'] = $_POST['vehiculoId'];
    }
    //si es flete
    if ($_POST['tipoOrden'] == 'f')
    {
      $data['ids_facrem'] = $_POST['remfacs'];
    }
    $this->db->insert('compras_ordenes', $data);
    $ordenId = $this->db->insert_id();

    //si se registra a un vehiculo
    if (isset($_POST['es_vehiculo']))
    {
      //si es de tipo gasolina o diesel se registra los litros
      if($_POST['tipo_vehiculo'] !== 'ot')
      {
        $this->db->insert('compras_vehiculos_gasolina', array(
          'id_orden'  => $ordenId,
          'kilometros' => $_POST['dkilometros'],
          'litros'     => $_POST['dlitros'],
          'precio'     => $_POST['dprecio'],
          ));
      }
    }

    $productos = array();
    foreach ($_POST['concepto'] as $key => $concepto)
    {

      if ($_POST['presentacionCant'][$key] !== '')
      {
        $cantidad = floatval($_POST['cantidad'][$key]) * floatval($_POST['presentacionCant'][$key]);
        $pu       = floatval($_POST['valorUnitario'][$key]) / floatval($_POST['presentacionCant'][$key]);
      }
      else
      {
        $cantidad = $_POST['cantidad'][$key];
        $pu       = $_POST['valorUnitario'][$key];
      }

      $productos[] = array(
        'id_orden'             => $ordenId,
        'num_row'              => $key,
        'id_producto'          => $_POST['productoId'][$key] !== '' ? $_POST['productoId'][$key] : null,
        'id_presentacion'      => $_POST['presentacion'][$key] !== '' ? $_POST['presentacion'][$key] : null,
        'descripcion'          => $concepto,
        'cantidad'             => $cantidad,
        'precio_unitario'      => $pu,
        'importe'              => $_POST['importe'][$key],
        'iva'                  => $_POST['trasladoTotal'][$key],
        'retencion_iva'        => $_POST['retTotal'][$key],
        'total'                => $_POST['total'][$key],
        'porcentaje_iva'       => $_POST['trasladoPorcent'][$key],
        'porcentaje_retencion' => $_POST['retTotal'][$key] == '0' ? '0' : '4',
        'faltantes'            => $_POST['faltantes'][$key] === '' ? '0' : $_POST['faltantes'][$key],
        'observacion'          => $_POST['observacion'][$key],
        'ieps'                 => is_numeric($_POST['iepsTotal'][$key]) ? $_POST['iepsTotal'][$key] : 0,
        'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
        'tipo_cambio'          => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
      );
    }

    $this->db->insert_batch('compras_productos', $productos);

    return array('passes' => true, 'msg' => 3);
  }

  public function agregarData($data, $dataVeiculo=array())
  {
    $this->db->insert('compras_ordenes', $data);
    $id_orden = $this->db->insert_id();

    if(is_array($dataVeiculo) && count($dataVeiculo) > 0)
    {
      $dataVeiculo['id_orden'] = $id_orden;
      $this->db->insert('compras_vehiculos_gasolina', $dataVeiculo);
    }

    return array('passes' => true, 'msg' => 3, 'id_orden' => $id_orden);
  }

  public function agregarProductosData($data)
  {
    $this->db->insert_batch('compras_productos', $data);

    return array('passes' => true, 'msg' => 3);
  }

  /**
   * Actualiza los datos de una orden de compra junton con sus productos.
   *
   * @param  string $idOrden
   * @param  mixed $orden
   * @param  mixed $productos
   * @return array
   */
  public function actualizar($idOrden, $orden = null, $productos = null)
  {
    // Si $orden o $productos son pasados a la funcion.
    if ($orden || $productos)
    {
      if ($orden)
      {
        $this->db->update('compras_ordenes', $orden, array('id_orden' => $idOrden));
      }

      if ($productos)
      {
        $this->db->insert_batch('compras_productos', $productos);
      }

      return array('passes' => true);
    }

    else
    {
      $status = $this->db->select("status")
        ->from("compras_ordenes")
        ->where("id_orden", $idOrden)
        ->get()->row()->status;

      $data = array(
        'id_empresa'      => $_POST['empresaId'],
        'id_proveedor'    => $_POST['proveedorId'],
        'id_departamento' => $_POST['departamento'],
        // 'id_autorizo'     => null,
        'id_empleado'     => $this->session->userdata('id_usuario'),
        // 'folio'           => $_POST['folio'],
        'fecha_creacion'  => str_replace('T', ' ', $_POST['fecha']),
        'tipo_pago'       => $_POST['tipoPago'],
        'tipo_orden'      => $_POST['tipoOrden'],
        'solicito'        => $_POST['solicito'],
        'id_cliente'      => (is_numeric($_POST['clienteId'])? $_POST['clienteId']: NULL),
        'descripcion'     => $_POST['descripcion'],
        'id_autorizo'     => (is_numeric($_POST['autorizoId'])? $_POST['autorizoId']: NULL),
      );

      if (isset($_POST['autorizar']) && $status === 'p')
      {
        $data['id_autorizo']        = $_POST['autorizoId']; //$this->session->userdata('id_usuario');
        $data['fecha_autorizacion'] = date('Y-m-d H:i:s');
        $data['autorizado']         = 't';
      }

      // Si esta modificando una orden rechazada entonces agrega mas campos
      // que se actualizaran.
      if ($status === 'r')
      {
      //   $data['id_autorizo'] = null;
        $data['status']      = 'p';
      //   $data['autorizado']  = 'f';
      }

      //si se registra a un vehiculo
      if (isset($_POST['es_vehiculo']))
      {
        $data['tipo_vehiculo'] = $_POST['tipo_vehiculo'];
        $data['id_vehiculo'] = $_POST['vehiculoId'];
      }
      else
      {
        $data['tipo_vehiculo'] = 'ot';
        $data['id_vehiculo'] = null;
      }
      //si es flete
      if ($_POST['tipoOrden'] == 'f')
      {
        $data['ids_facrem'] = $_POST['remfacs'];
      }

      // Bitacora
      $id_bitacora = $this->bitacora_model->_update('compras_ordenes', $idOrden, $data,
                                array(':accion'       => ($data['status']=='a'? 'acepto ': 'modifico ').'la orden de compra', ':seccion' => 'ordenes de compra',
                                      ':folio'        => $this->input->post('folio'),
                                      ':id_empresa'   => $this->input->post('empresaId'),
                                      ':empresa'      => 'en '.$this->input->post('empresa'),
                                      ':id'           => 'id_orden',
                                      ':titulo'       => 'Orden de compra'));

      $this->db->update('compras_ordenes', $data, array('id_orden' => $idOrden));

      // Actualiza los datos del vehiculo
      $this->actualizaVehiculo($idOrden);

      $res_prodc_orden = $this->db->query("SELECT id_orden, num_row, id_compra FROM compras_productos
              WHERE id_orden = {$idOrden}")->result();
      $productos = array();
      foreach ($_POST['concepto'] as $key => $concepto)
      {

        if ($_POST['presentacionCant'][$key] !== '')
        {
          $cantidad = floatval($_POST['cantidad'][$key]) * floatval($_POST['presentacionCant'][$key]);
          $pu       = floatval($_POST['valorUnitario'][$key]) / floatval($_POST['presentacionCant'][$key]);
        }
        else
        {
          $cantidad = $_POST['cantidad'][$key];
          $pu       = $_POST['valorUnitario'][$key];
        }

        $prod_id_compra = NULL;
        foreach ($res_prodc_orden as $keyor => $ord)
        {
          if($_POST['prodIdOrden'][$key] == $ord->id_orden && $_POST['prodIdNumRow'][$key] == $ord->num_row)
            $prod_id_compra = $ord->id_compra;
        }
        $productos[] = array(
          'id_orden'             => $idOrden,
          'num_row'              => $key,
          'id_producto'          => $_POST['productoId'][$key] !== '' ? $_POST['productoId'][$key] : null,
          'id_presentacion'      => $_POST['presentacion'][$key] !== '' ? $_POST['presentacion'][$key] : null,
          'descripcion'          => $concepto,
          'cantidad'             => $cantidad,
          'precio_unitario'      => $pu,
          'importe'              => $_POST['importe'][$key],
          'iva'                  => $_POST['trasladoTotal'][$key],
          'retencion_iva'        => $_POST['retTotal'][$key],
          'total'                => $_POST['total'][$key],
          'porcentaje_iva'       => $_POST['trasladoPorcent'][$key],
          'porcentaje_retencion' => $_POST['ret_iva'][$key],
          'faltantes'            => $_POST['faltantes'][$key] === '' ? '0' : $_POST['faltantes'][$key],
          'observacion'          => $_POST['observacion'][$key],
          'status'               => (isset($_POST['isProdOk'][$key]) && $_POST['isProdOk'][$key] === '1') || $status === 'a' ? 'a' : 'p',
          'ieps'                 => is_numeric($_POST['iepsTotal'][$key]) ? $_POST['iepsTotal'][$key] : 0,
          'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
          'tipo_cambio'          => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
          'id_compra'            => $prod_id_compra,
          // 'id_area'              => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
          $_POST['codigoCampo'][$key] => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
          'retencion_isr'        => $_POST['ret_isrTotal'][$key],
          'porcentaje_isr'       => $_POST['ret_isrPorcent'][$key],
        );
      }

      // Bitacora
      $this->bitacora_model->_updateExt($id_bitacora, 'compras_productos', $idOrden, $productos,
                                array(':id'             => 'id_orden',
                                      ':titulo'         => 'Productos',
                                      ':updates_fields' => 'compras_ordenes_productos'));

      $this->db->delete('compras_productos', array('id_orden' => $idOrden));
      $this->db->insert_batch('compras_productos', $productos);

      //envia el email al momento de autorizar la orden
      if(isset($data['autorizado']))
        if($data['autorizado'] == 't')
          $this->sendEmail($idOrden, $_POST['proveedorId']);
    }

    return array('passes' => true, 'msg' => 7);
  }

  public function actualizaVehiculo($idOrden)
  {
    //si se registra a un vehiculo
    if (isset($_POST['es_vehiculo']))
    {
      //si es de tipo gasolina o diesel se registra los litros
      if($_POST['tipo_vehiculo'] !== 'ot')
      {
        $this->db->delete('compras_vehiculos_gasolina', array('id_orden' => $idOrden));
        $this->db->insert('compras_vehiculos_gasolina', array(
          'id_orden'   => $idOrden,
          'kilometros' => $_POST['dkilometros'],
          'litros'     => $_POST['dlitros'],
          'precio'     => $_POST['dprecio'],
          ));
      }
    }
    else
    {
      $this->db->delete('compras_vehiculos_gasolina', array('id_orden' => $idOrden));
    }
  }

  public function actualizaArea($params)
  {
    $this->db->update('compras_productos', array('id_area' => $params['id_area']), "id_orden = {$params['id_orden']} AND num_row = {$params['num_row']}");
  }

  /**
   * Agrega una compra. Esto es cuando se agregan o ligan ordenes a una factura.
   *
   * @param  string $proveedorId
   * @param  string $ordenesIds
   * @return array
   */
  public function agregarCompra($proveedorId, $empresaId, $ordenesIds, $xml = null)
  {
    // obtiene un array con los ids de las ordenes a ligar con la compra.
    $ordenesIds = explode(',', $ordenesIds);

    // datos de la compra.
    $data = array(
      'id_proveedor'   => $proveedorId,
      'id_empresa'     => $empresaId,
      'id_empleado'    => $this->session->userdata('id_usuario'),
      'serie'          => $_POST['serie'],
      'folio'          => $_POST['folio'],
      'condicion_pago' => $_POST['condicionPago'],
      'plazo_credito'  => $_POST['plazoCredito'] !== '' ? $_POST['plazoCredito'] : 0,
      // 'tipo_documento' => $_POST['algo'],
      'fecha'          => str_replace('T', ' ', $_POST['fecha']),
      'subtotal'       => $_POST['totalImporte'],
      'importe_iva'    => $_POST['totalImpuestosTrasladados'],
      'importe_ieps'   => $_POST['totalIeps'],
      'retencion_iva'  => $_POST['totalRetencion'],
      'retencion_isr'  => $_POST['totalRetencionIsr'],
      'total'          => $_POST['totalOrden'],
      'concepto'       => 'Concepto',
      'isgasto'        => 'f',
      'status'         => $_POST['condicionPago'] ===  'co' ? 'pa' : 'p',
    );

    // //si es contado, se verifica que la cuenta tenga saldo
    // if ($data['condicion_pago'] == 'co')
    // {
    //   $this->load->model('banco_cuentas_model');
    //   $cuenta = $this->banco_cuentas_model->getCuentas(false, $_POST['dcuenta']);
    //   if ($cuenta['cuentas'][0]->saldo < $data['total'])
    //     return array('passes' => false, 'msg' => 30);
    // }

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
      $data['xml'] = 'application'.$xmlFile[1];
    }

    // inserta la compra
    $this->db->insert('compras', $data);

    // obtiene el id de la compra insertada.
    $compraId = $this->db->insert_id();

    // Bitacora
    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($data['id_empresa']);
    $this->bitacora_model->_insert('compras', $compraId,
                                    array(':accion'     => 'la compra', ':seccion' => 'compras',
                                          ':folio'      => $data['serie'].$data['folio'],
                                          ':id_empresa' => $data['id_empresa'],
                                          ':empresa'    => 'en '.$empresa['info']->nombre_fiscal));

    // //si es contado, se registra el abono y el retiro del banco
    // if ($data['condicion_pago'] == 'co')
    // {
    //   $this->load->model('cuentas_pagar_model');
    //   $data_abono = array('fecha'             => $data['fecha'],
    //                     'concepto'            => 'Pago de contado',
    //                     'total'               => $data['total'],
    //                     'id_cuenta'           => $this->input->post('dcuenta'),
    //                     'ref_movimiento'      => $this->input->post('dreferencia'),
    //                     'id_cuenta_proveedor' => $this->input->post('fcuentas_proveedor') );
    //   $_GET['tipo'] = 'f';
    //   $respons = $this->cuentas_pagar_model->addAbono($data_abono, $compraId);
    // }

    // Actualiza los productos.
    $productos_compra = $productos_compra2 = array();
    foreach ($_POST['concepto'] as $key => $producto)
    {
      if(isset($productos_compra[$_POST['ordenId'][$key]]))
        $productos_compra[$_POST['ordenId'][$key]]++;
      else{
        $productos_compra[$_POST['ordenId'][$key]] = 1;
        $productos_compra2[$_POST['ordenId'][$key]] = 0;
      }

      foreach ($_POST['productoCom'] as $keyp => $produc)
      {
        $produc = explode('|', $produc);
        if($_POST['ordenId'][$key] === $produc[0] && $_POST['row'][$key] === $produc[1]){
          $productos_compra2[$_POST['ordenId'][$key]]++;
          $prodData = array(
            'precio_unitario'      => $_POST['valorUnitario'][$key],
            'importe'              => $_POST['importe'][$key],
            'iva'                  => $_POST['trasladoTotal'][$key],
            'retencion_iva'        => $_POST['retTotal'][$key],
            'total'                => $_POST['total'][$key],
            'porcentaje_iva'       => $_POST['trasladoPorcent'][$key],
            'porcentaje_retencion' => $_POST['ret_iva'][$key],
            'ieps'                 => is_numeric($_POST['iepsTotal'][$key]) ? $_POST['iepsTotal'][$key] : 0,
            'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
            'id_compra'            => $compraId,
            'retencion_isr'        => $_POST['ret_isrTotal'][$key],
            'porcentaje_isr'       => $_POST['ret_isrPorcent'][$key],
          );

          $this->db->update('compras_productos', $prodData, array(
            'id_orden' => $_POST['ordenId'][$key],
            'num_row'  => $_POST['row'][$key]
          ));
        }
      }

    }

    // construye el array de las ordenes a ligar con la compra.
    $ordenes = array();
    foreach ($ordenesIds as $ordenId)
    {
      $ordenes[] = array(
        'id_compra' => $compraId,
        'id_orden'  => $ordenId,
      );

      // Cambia a facturada hasta q todos los productos se ligan a las compras
      if($productos_compra[$ordenId] == $productos_compra2[$ordenId])
        $this->db->update('compras_ordenes', array('status' => 'f'), array('id_orden' => $ordenId));
    }
    // inserta los ids de las ordenes.
    $this->db->insert_batch('compras_facturas', $ordenes);

    $respons['passes'] = true;

    return $respons;
  }

  public function cancelar($idOrden)
  {
    $data = array('status' => 'ca');
    $this->actualizar($idOrden, $data);

    // Bitacora
    $datosorden = $this->info($idOrden);
    $this->bitacora_model->_cancel('compras_ordenes', $idOrden,
                                    array(':accion'     => 'la orden de compra', ':seccion' => 'ordenes de compra',
                                          ':folio'      => $datosorden['info'][0]->folio,
                                          ':id_empresa' => $datosorden['info'][0]->id_empresa,
                                          ':empresa'    => 'de '.$datosorden['info'][0]->empresa));

    return array('passes' => true);
  }

  public function info($idOrden, $full = false, $prodAcep=false, $idCompra=NULL)
  {
    $query = $this->db->query(
      "SELECT co.id_orden,
              co.id_empresa, e.nombre_fiscal AS empresa,
              e.logo,
              co.id_proveedor, p.nombre_fiscal AS proveedor,
              co.id_departamento, cd.nombre AS departamento,
              co.id_empleado, u.nombre AS empleado,
              co.id_autorizo, (us.nombre || ' ' || us.apellido_paterno || ' ' || us.apellido_materno) AS autorizo,
              co.id_cliente, cl.nombre_fiscal AS cliente,
              co.folio, co.fecha_creacion AS fecha, co.fecha_autorizacion,
              co.fecha_aceptacion, co.tipo_pago, co.tipo_orden, co.status,
              co.autorizado,
              co.solicito as empleado_solicito, co.descripcion,
              co.id_vehiculo,
              co.tipo_vehiculo,
              COALESCE(cv.placa, null) as placa,
              COALESCE(cv.modelo, null) as modelo,
              COALESCE(cv.marca, null) as marca,
              COALESCE(cv.color, null) as color,
              co.ids_facrem,
              co.no_impresiones_tk
       FROM compras_ordenes AS co
       INNER JOIN empresas AS e ON e.id_empresa = co.id_empresa
       INNER JOIN proveedores AS p ON p.id_proveedor = co.id_proveedor
       INNER JOIN compras_departamentos AS cd ON cd.id_departamento = co.id_departamento
       INNER JOIN usuarios AS u ON u.id = co.id_empleado
       LEFT JOIN usuarios AS us ON us.id = co.id_autorizo
       LEFT JOIN clientes AS cl ON cl.id_cliente = co.id_cliente
       LEFT JOIN compras_vehiculos cv ON cv.id_vehiculo = co.id_vehiculo
       WHERE co.id_orden = {$idOrden}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();

      $query->free_result();
      if ($full)
      {
        $sql_produc = $prodAcep? " AND cp.status = 'a' AND cp.id_compra IS NULL": '';
        $sql_produc .= $idCompra!==NULL? " AND (cp.id_compra = {$idCompra} OR (cp.id_compra IS NULL AND Date(cp.fecha_aceptacion) <= '2014-05-26'))": '';
        $query = $this->db->query(
          "SELECT cp.id_orden, cp.num_row,
                  cp.id_producto, pr.nombre AS producto, pr.codigo, pr.id_unidad, pu.abreviatura, pu.nombre as unidad,
                  cp.id_presentacion, pp.nombre AS presentacion, pp.cantidad as presen_cantidad,
                  cp.descripcion, cp.cantidad, cp.precio_unitario, cp.importe,
                  cp.iva, cp.retencion_iva, cp.retencion_isr, cp.porcentaje_isr, cp.total, cp.porcentaje_iva,
                  cp.porcentaje_retencion, cp.status, cp.faltantes, cp.observacion,
                  cp.ieps, cp.porcentaje_ieps, cp.tipo_cambio, COALESCE(cca.id_cat_codigos, ca.id_area) AS id_area,
                  COALESCE((CASE WHEN cca.codigo <> '' THEN cca.codigo ELSE cca.nombre END), ca.codigo_fin) AS codigo_fin,
                  (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_area' ELSE 'id_cat_codigos' END) AS campo
           FROM compras_productos AS cp
           LEFT JOIN productos AS pr ON pr.id_producto = cp.id_producto
           LEFT JOIN productos_presentaciones AS pp ON pp.id_presentacion = cp.id_presentacion
           LEFT JOIN productos_unidades AS pu ON pu.id_unidad = pr.id_unidad
           LEFT JOIN compras_areas AS ca ON ca.id_area = cp.id_area
           LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = cp.id_cat_codigos
           WHERE id_orden = {$data['info'][0]->id_orden} {$sql_produc}");

        $data['info'][0]->productos = array();
        if ($query->num_rows() > 0)
        {
          $data['info'][0]->productos = $query->result();
        }

        $query->free_result();

        $data['info'][0]->gasolina = array();
        if ($data['info'][0]->id_vehiculo)
        {
          // Vehiculo
          $query = $this->db->query(
            "SELECT cvg.id_orden, cvg.kilometros, cvg.litros, cvg.precio
             FROM compras_vehiculos_gasolina AS cvg
             WHERE cvg.id_orden = {$data['info'][0]->id_orden}");

          if ($query->num_rows() > 0)
          {
            $data['info'][0]->gasolina = $query->result();
          }
        }

        // facturas ligadas
        $data['info'][0]->facturasligadas = array();
        $this->load->model('facturacion_model');
        $facturasss = explode('|', $data['info'][0]->ids_facrem);
        if (count($facturasss) > 0)
        {
          array_pop($facturasss);
          foreach ($facturasss as $key => $value)
          {
            $facturaa = explode(':', $value);
            $data['info'][0]->facturasligadas[] = $this->facturacion_model->getInfoFactura($facturaa[1], true)['info'];
          }
        }

        //Compras de la orden
        $this->load->model('compras_model');
        $compras_data = $this->db->query("SELECT id_compra
                                   FROM compras_facturas
                                   WHERE id_orden = {$data['info'][0]->id_orden}");
        $data['info'][0]->compras = $compras_data->result();

        //eNTRADA ALMACEN
        $data['info'][0]->entrada_almacen = array();
        $data['info'][0]->entrada_almacen = $this->getInfoEntrada(0,0, $data['info'][0]->id_orden);
      }
    }
    return $data;
  }

  public function folio($tipo = 'p')
  {
    $res = $this->db->select('folio')
      ->from('compras_ordenes')
      ->where('tipo_orden', $tipo)
      ->order_by('folio', 'DESC')
      ->limit(1)->get()->row();

    $folio = (isset($res->folio) ? $res->folio : 0) + 1;

    return $folio;
  }

  public function autorizar($idOrden)
  {
    $data = array(
      'id_autorizo'        => $_POST['autorizoId'], //$this->session->userdata('id_usuario'),
      'fecha_autorizacion' => date('Y-m-d H:i:s'),
      'autorizado'         => 't',
    );

    $this->actualizar($idOrden, $data);

    $this->sendEmail($idOrden, $_POST['proveedorId']);

    return array('status' => true, 'msg' => 4);
  }

  public function sendEmail($idOrden, $proveedorId)
  {
    // Si la orden no esta rechazada verifica si el proveedor tiene el email
    // asignado para enviarle la orden de compra.
    $this->load->model('proveedores_model');
    $proveedor = $this->proveedores_model->getProveedorInfo($proveedorId);

    if ($proveedor['info']->email !== '')
    {
      // Si el proveedor tiene email asigando le envia la orden.
      $this->load->library('my_email');

      $correoEmisorEm = "empaquesanjorge@hotmail.com"; // Correo con el q se emitira el correo.
      $nombreEmisor   = 'Empaque San Jorge';
      $correoEmisor   = "postmaster@empaquesanjorge.com"; // Correo para el auth. empaquesanjorgemx@gmail.com (mandrill)
      $contrasena     = "2b9f25bc4737f34edada0b29a56ff682"; // Contraseña de $correEmisor S4nj0rg3V14n3y

      $path = APPPATH . 'media/temp/';

      $file = $this->print_orden_compra($idOrden, $path);

      $datosEmail = array(
        'correoEmisorEm' => $correoEmisorEm,
        'correoEmisor'   => $correoEmisor,
        'nombreEmisor'   => $nombreEmisor,
        'contrasena'     => $contrasena,
        'asunto'         => 'Nueva orden de compra ' . date('Y-m-d H:m'),
        'altBody'        => 'Nueva orden de compra.',
        'body'           => 'Nueva orden de compra.',
        'correoDestino'  => array($proveedor['info']->email),
        'nombreDestino'  => $proveedor['info']->nombre_fiscal,
        'cc'             => '',
        'adjuntos'       => array('ORDEN_COMPRA_'.$orden['info'][0]->folio.'.pdf' => $file)
      );

      $result = $this->my_email->setData($datosEmail)->send();
      unlink($file);
    }
  }

  public function entrada($idOrden)
  {
    $ordenRechazada = false;
    // Verifica si la orden se va rechazar
    foreach ($_POST['concepto'] as $key => $concepto)
    {
      if ($_POST['isProdOk'][$key] === '0')
      {
        $ordenRechazada = true;
      }
    }

    $this->load->model('productos_model');

    $almacen = array();
    $res_prodc_orden = $this->db->query("SELECT id_orden, num_row, id_compra FROM compras_productos
              WHERE id_orden = {$idOrden}")->result();
    $productos = $productosFaltantes = array();
    $faltantes = false;
    foreach ($_POST['concepto'] as $key => $concepto)
    {
      $faltantesProd = $_POST['faltantes'][$key] === '' ? '0' : $_POST['faltantes'][$key];
      if( ! $ordenRechazada)
        $_POST['cantidad'][$key] -= $faltantesProd;

      if ($_POST['presentacionCant'][$key] !== '')
      {
        $cantidad = floatval($_POST['cantidad'][$key]) * floatval($_POST['presentacionCant'][$key]);
        $pu       = floatval($_POST['valorUnitario'][$key]) / floatval($_POST['presentacionCant'][$key]);
      }
      else
      {
        $cantidad = $_POST['cantidad'][$key];
        $pu       = $_POST['valorUnitario'][$key];
      }

      $prod_id_compra = NULL;
      foreach ($res_prodc_orden as $keyor => $ord)
      {
        if($_POST['prodIdOrden'][$key] == $ord->id_orden && $_POST['prodIdNumRow'][$key] == $ord->num_row)
          $prod_id_compra = $ord->id_compra;
      }
      $productos[] = array(
        'id_orden'             => $idOrden,
        'num_row'              => $key,
        'id_producto'          => $_POST['productoId'][$key] !== '' ? $_POST['productoId'][$key] : null,
        'id_presentacion'      => $_POST['presentacion'][$key] !== '' ? $_POST['presentacion'][$key] : null,
        'descripcion'          => $concepto,
        'cantidad'             => $cantidad,
        'precio_unitario'      => $pu,
        'importe'              => $_POST['importe'][$key],
        'iva'                  => $_POST['trasladoTotal'][$key],
        'retencion_iva'        => $_POST['retTotal'][$key],
        'total'                => $_POST['total'][$key],
        'porcentaje_iva'       => $_POST['trasladoPorcent'][$key],
        'porcentaje_retencion' => $_POST['ret_iva'][$key],
        'status'               => $_POST['isProdOk'][$key] === '1' ? 'a' : 'r',
        'fecha_aceptacion'     => date('Y-m-d H:i:s'),
        'faltantes'            => $faltantesProd,
        'observacion'          => $_POST['observacion'][$key],
        'ieps'                 => is_numeric($_POST['iepsTotal'][$key]) ? $_POST['iepsTotal'][$key] : 0,
        'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
        'tipo_cambio'          => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
        'id_compra'            => $prod_id_compra,
        // 'id_area'              => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
        $_POST['codigoCampo'][$key] => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
        'retencion_isr'        => $_POST['ret_isrTotal'][$key],
        'porcentaje_isr'       => $_POST['ret_isrPorcent'][$key],
      );

      if ($faltantesProd != '0')
      {
        if ($_POST['presentacionCant'][$key] !== '')
          $faltantesProd = $faltantesProd * floatval($_POST['presentacionCant'][$key]);

        // productos faltantes para registrar en una nueva orden
        $productosFaltantes[] = array(
          'id_producto'          => $_POST['productoId'][$key] !== '' ? $_POST['productoId'][$key] : null,
          'id_presentacion'      => $_POST['presentacion'][$key] !== '' ? $_POST['presentacion'][$key] : null,
          'descripcion'          => $concepto,
          'cantidad'             => $faltantesProd,
          'precio_unitario'      => $pu,
          'importe'              => $faltantesProd * $pu,
          'iva'                  => ($faltantesProd * $pu * $_POST['trasladoPorcent'][$key]/100),
          'retencion_iva'        => 0,
          'total'                => $_POST['total'][$key],
          'porcentaje_iva'       => $_POST['trasladoPorcent'][$key],
          'porcentaje_retencion' => $_POST['ret_iva'][$key],
          'status'               => 'p',
          'fecha_aceptacion'     => date('Y-m-d H:i:s'),
          'faltantes'            => 0,
          'observacion'          => $_POST['observacion'][$key],
          'ieps'                 => ($faltantesProd * $pu * floatval($_POST['iepsPorcent'][$key])/100),
          'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
          'tipo_cambio'          => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
          'id_compra'            => NULL,
          // 'id_area'              => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
          $_POST['codigoCampo'][$key] => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
          'retencion_isr'        => $_POST['ret_isrTotal'][$key],
          'porcentaje_isr'       => $_POST['ret_isrPorcent'][$key],
        );
        $productosFaltantes[count($productosFaltantes)-1]['total'] = $productosFaltantes[count($productosFaltantes)-1]['importe'] +
                                                                      $productosFaltantes[count($productosFaltantes)-1]['iva'] +
                                                                      $productosFaltantes[count($productosFaltantes)-1]['ieps'];
        $faltantes = true;
      }

      // if ($_POST['isProdOk'][$key] === '0')
      // {
      //   $ordenRechazada = true;
      // }

      $producto_dd = $this->productos_model->getProductoInfo(false, false, $_POST['productoId'][$key]);
      if(count($producto_dd['info']) > 0 && !in_array($producto_dd['familia']->almacen, $almacen))
        $almacen[] = $producto_dd['familia']->almacen;
    }

    $data_almacen = null;
    // Si todos los productos fueron aceptados entonces la orden se marca
    // como aceptada.
    if ( ! $ordenRechazada)
    {
      $data = array(
        'fecha_aceptacion' => date('Y-m-d H:i:s'),
        'status'           => 'a',
      );

      $msg = 5;

      // se registra la entrada al almacen
      $getFolio = $this->db->query("SELECT (coalesce((SELECT folio FROM compras_entradas_almacen WHERE status = 't' AND id_empresa = {$_POST['empresaId']} ORDER BY folio DESC LIMIT 1),0)+1) AS folio")->row();
      $data_almacen = array(
        'id_orden'   => $idOrden,
        'id_empresa' => $_POST['empresaId'],
        'id_recibio' => $this->session->userdata('id_usuario'),
        'folio'      => $getFolio->folio,
        'fecha'      => date('Y-m-d H:i:s'),
        'almacen'    => implode('|', $almacen),
        );
      $this->db->insert('compras_entradas_almacen', $data_almacen);

      // Crea la orden de los faltantes
      if (count($productosFaltantes) > 0) {
        $this->creaOrdenFaltantes($idOrden, $productosFaltantes);
      }
    }

    // Si al menos un producto no fue aceptado entonces la orden es
    // rechazada.
    else
    {
      $data = array(
        'status' => 'r',
      );

      $msg = 6;
    }

    // Bitacora
    $id_bitacora = $this->bitacora_model->_update('compras_ordenes', $idOrden, $data,
                              array(':accion'       => ($data['status']=='a'? 'acepto ': 'rechazo ').'la orden de compra', ':seccion' => 'ordenes de compra',
                                    ':folio'        => $this->input->post('folio'),
                                    ':id_empresa'   => $this->input->post('empresaId'),
                                    ':empresa'      => 'en '.$this->input->post('empresa'),
                                    ':id'           => 'id_orden',
                                    ':titulo'       => 'Orden de compra'));
    // Bitacora
    $this->bitacora_model->_updateExt($id_bitacora, 'compras_productos', $idOrden, $productos,
                              array(':id'             => 'id_orden',
                                    ':titulo'         => 'Productos',
                                    ':updates_fields' => 'compras_ordenes_productos'));

    //si se registra a un vehiculo
    if (isset($_POST['es_vehiculo']))
    {
      $data['tipo_vehiculo'] = $_POST['tipo_vehiculo'];
      $data['id_vehiculo'] = $_POST['vehiculoId'];
    }
    else
    {
      $data['tipo_vehiculo'] = 'ot';
      $data['id_vehiculo'] = null;
    }

    $this->db->delete('compras_productos', array('id_orden' => $idOrden));

    $this->actualizar($idOrden, $data, $productos);

    // Actualiza los datos del vehiculo
    $this->actualizaVehiculo($idOrden);

    // // Si la orden no esta rechazada verifica si el proveedor tiene el email
    // // asignado para enviarle la orden de compra.
    // if ( ! $ordenRechazada)
    // {
    //   $this->load->model('proveedores_model');
    //   $proveedor = $this->proveedores_model->getProveedorInfo($_POST['proveedorId']);

    //   if ($proveedor['info']->email !== '')
    //   {
    //     // Si el proveedor tiene email asigando le envia la orden.
    //     $this->load->library('my_email');

    //     $correoEmisorEm = "empaquesanjorge@hotmail.com"; // Correo con el q se emitira el correo.
    //     $nombreEmisor   = 'Empaque San Jorge';
    //     $correoEmisor   = "empaquesanjorgemx@gmail.com"; // Correo para el auth.
    //     $contrasena     = "2x02pxeexCUpiKncoWI50Q"; // Contraseña de $correEmisor

    //     $path = APPPATH . 'media/temp/';

    //     $file = $this->print_orden_compra($idOrden, $path);

    //     $datosEmail = array(
    //       'correoEmisorEm' => $correoEmisorEm,
    //       'correoEmisor'   => $correoEmisor,
    //       'nombreEmisor'   => $nombreEmisor,
    //       'contrasena'     => $contrasena,
    //       'asunto'         => 'Nueva orden de compra ' . date('Y-m-d H:m'),
    //       'altBody'        => 'Nueva orden de compra.',
    //       'body'           => 'Nueva orden de compra.',
    //       'correoDestino'  => array($proveedor['info']->email),
    //       'nombreDestino'  => $proveedor['info']->nombre_fiscal,
    //       'cc'             => '',
    //       'adjuntos'       => array('ORDEN_COMPRA_'.$orden['info'][0]->folio.'.pdf' => $file)
    //     );

    //     $result = $this->my_email->setData($datosEmail)->send();
    //     unlink($file);
    //   }
    // }

    return array('status' => true, 'msg' => $msg, 'faltantes' => $faltantes, 'entrada' => $data_almacen);
  }

  public function creaOrdenFaltantes($idOrden, $productos)
  {
    $data = $this->info($idOrden);
    $data['info'] = $data['info'][0];
    $dataOrden = array(
      'id_empresa'         => $data['info']->id_empresa,
      'id_proveedor'       => $data['info']->id_proveedor,
      'id_departamento'    => $data['info']->id_departamento,
      'id_empleado'        => $data['info']->id_empleado,
      'folio'              => $this->folio($data['info']->tipo_orden),
      'status'             => 'p',
      'autorizado'         => 't',
      'fecha_autorizacion' => $data['info']->fecha_autorizacion,
      'fecha_aceptacion'   => $data['info']->fecha_aceptacion,
      'fecha_creacion'     => $data['info']->fecha,
      'tipo_pago'          => $data['info']->tipo_pago,
      'tipo_orden'         => $data['info']->tipo_orden,
      'solicito'           => $data['info']->empleado_solicito,
      'id_cliente'         => (is_numeric($data['info']->id_cliente)? $data['info']->id_cliente: NULL),
      'descripcion'        => $data['info']->descripcion,
      'id_autorizo'        => $data['info']->id_autorizo,
    );
    //si es flete
    if ($data['info']->tipo_orden == 'f')
    {
      $dataOrden['ids_facrem'] = $data['info']->ids_facrem;
    }

    $res = $this->agregarData($dataOrden);
    $id_orden = $res['id_orden'];

    // Productos
    $rows_compras = 0;
    foreach ($productos as $keypr => $prod)
    {
      $productos[$keypr]['id_orden'] = $id_orden;
      $productos[$keypr]['num_row']  = $rows_compras;
      $rows_compras++;
    }

    if(count($productos) > 0)
      $this->compras_ordenes_model->agregarProductosData($productos);
  }

  public function impresoras()
  {
    $impresoras = $this->db->query("SELECT id_impresora, impresora, ruta FROM impresoras");
    return $impresoras->result();
  }

  /*
   |------------------------------------------------------------------------
   |
   |------------------------------------------------------------------------
   */

  public function departamentos()
  {
    $depas = $this->db->select("*")
      ->from("compras_departamentos")
      ->order_by('nombre')
      ->get();

    if ($depas->num_rows > 0)
    {
      return $depas->result();
    }

    return array();
  }

  public function unidades()
  {
    $unidades = $this->db->select("*")
      ->from("productos_unidades")
      ->order_by('nombre')
      ->get();

    if ($unidades->num_rows > 0)
    {
      return $unidades->result();
    }

    return array();
  }

  public function getProductoAjax($idEmpresa = null, $tipo, $term, $def = 'codigo'){
    $sql = '';

    $this->load->model('inventario_model');
    $sqlEmpresa = "";
    if ($idEmpresa)
    {
      $sqlEmpresa = "p.id_empresa = {$idEmpresa} AND";
      $_GET['did_empresa'] = $idEmpresa;
    }

    $tipo_prod = '';
    if($tipo != '')
      $tipo_prod = "pf.tipo = '{$tipo}' AND ";

    $res = $this->db->query(
       "SELECT p.*,
              pf.nombre as familia, pf.codigo as codigo_familia, pf.tipo AS tipo_familia,
              pu.nombre as unidad, pu.abreviatura as unidad_abreviatura,
              (SELECT precio_unitario FROM compras_productos WHERE id_producto = p.id_producto ORDER BY id_orden DESC LIMIT 1) AS precio_unitario
        FROM productos AS p
        INNER JOIN productos_familias pf ON pf.id_familia = p.id_familia
        INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        WHERE p.status = 'ac' AND
              {$term}
              {$sqlEmpresa}
              {$tipo_prod}
              pf.status = 'ac'
        ORDER BY p.nombre ASC
        LIMIT 20");

    $response = array();
    if($res->num_rows() > 0)
    {
      foreach($res->result() as $itm)
      {
        if(isset($_GET['did_empresa']{0}))
        {
          $_GET['fid_producto'] = $itm->id_producto;
          $itm->inventario = $this->inventario_model->getEPUData();
          $itm->inventario = isset($itm->inventario[0])? $itm->inventario[0]: false;
        }

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

  public function getProductoByCodigoAjax($idEmpresa, $tipo, $codigo)
  {
    $sql = '';

    $term = "lower(p.codigo) = '".mb_strtolower($codigo, 'UTF-8')."'";

    $res = $this->db->query(
       "SELECT p.*,
              pf.nombre as familia, pf.codigo as codigo_familia, pf.tipo AS tipo_familia
              pu.nombre as unidad, pu.abreviatura as unidad_abreviatura
        FROM productos as p
        INNER JOIN productos_familias pf ON pf.id_familia = p.id_familia
        INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        WHERE p.status = 'ac' AND
              {$term} AND
              p.id_empresa = {$idEmpresa} AND
              pf.tipo = '{$tipo}' AND
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

  public function getFactRem($datos)
  {
    $tipo = $datos['tipo'] == 'f'? 't': 'f';
    $filtro = isset($datos['filtro']{0})? " AND f.folio = '{$datos['filtro']}'": '';
    $query = $this->db->query("SELECT f.id_factura, Date(f.fecha) AS fecha, f.serie, f.folio, f.is_factura, c.nombre_fiscal AS cliente
                               FROM facturacion AS f INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
                               WHERE c.id_cliente = {$datos['clienteId']} AND f.is_factura = '{$tipo}' AND f.status IN('p', 'pa') AND f.id_nc IS NULL
                                {$filtro} AND f.fecha >= (now() - interval '115 months')
                               ORDER BY f.fecha DESC, f.folio DESC");
    $response = array();
    if($query->num_rows() > 0)
      $response = $query->result();
    $query->free_result();
    return $response;
  }

  /*
   |------------------------------------------------------------------------
   | PDF's
   |------------------------------------------------------------------------
   */

  /**
    * Visualiza/Descarga el PDF de la orden de compra.
    *
    * @return void
    */
   public function print_orden_compra($ordenId, $path = null)
   {
      $this->load->model('compras_areas_model');
      $this->load->model('catalogos_sft_model');

      $orden = $this->info($ordenId, true);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      // $pdf->show_head = true;
      $pdf->titulo1 = $orden['info'][0]->empresa;
      $tipo_orden = 'ORDEN DE COMPRA';
      if($orden['info'][0]->tipo_orden == 'd')
        $tipo_orden = 'ORDEN DE SERVICIO';
      elseif($orden['info'][0]->tipo_orden == 'f')
        $tipo_orden = 'ORDEN DE FLETE';
      // $pdf->titulo2 = $tipo_orden;
      // $pdf->titulo2 = 'Proveedor: ' . $orden['info'][0]->proveedor;
      // $pdf->titulo3 = " Fecha: ". date('Y-m-d') . ' Orden: ' . $orden['info'][0]->folio;

      $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

      $pdf->AliasNbPages();
      $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY()-10);

      $pdf->SetFont('helvetica','B', 10);
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(150, 50));
      $pdf->Row(array(
        $tipo_orden,
        'No '.String::formatoNumero($orden['info'][0]->folio, 2, ''),
      ), false, false);
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetX(6);
      $pdf->Row(array(
        'PROVEEDOR: ' . $orden['info'][0]->proveedor,
        String::fechaATexto($orden['info'][0]->fecha, '/c'),
      ), false, false);

      $aligns = array('C', 'C', 'L', 'R', 'R');
      $widths = array(25, 35, 76, 18, 25, 25);
      $header = array('CANT.', 'CODIGO', 'DESCRIPCION', 'F COMPRA', 'PRECIO', 'IMPORTE');

      $subtotal = $iva = $total = $retencion = $ieps = 0;

      $tipoCambio = 0;
      $codigoAreas = array();

      foreach ($orden['info'][0]->productos as $key => $prod)
      {
        $tipoCambio = 1;
        if ($prod->tipo_cambio != 0)
        {
          $tipoCambio = $prod->tipo_cambio;
        }

        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
          if($pdf->GetY()+5 >= $pdf->limiteY)
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
        $datos = array(
          $prod->cantidad.' '.$prod->abreviatura,
          $prod->codigo.'/'.$prod->codigo_fin,
          $prod->descripcion.($prod->observacion!=''? " ({$prod->observacion})": ''),
          $this->getFechaUltimaCompra($prod->id_producto, $prod->id_area, $prod->campo),
          String::formatoNumero($prod->precio_unitario/$tipoCambio, 2, '$', false),
          String::formatoNumero($prod->importe/$tipoCambio, 2, '$', false),
        );

        $pdf->SetX(6);
        $pdf->Row($datos, false);

        $subtotal  += floatval($prod->importe/$tipoCambio);
        $iva       += floatval($prod->iva/$tipoCambio);
        $total     += floatval($prod->total/$tipoCambio);
        $retencion += floatval($prod->retencion_iva/$tipoCambio);
        $ieps      += floatval($prod->ieps/$tipoCambio);

        if($prod->id_area != '' && !array_key_exists($prod->id_area, $codigoAreas))
          $codigoAreas[$prod->id_area] = $this->{($prod->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($prod->id_area);
      }

      $yy = $pdf->GetY();

      //Otros datos
      // $pdf->SetXY(6, $yy);
      $pdf->SetX(6);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(154));
      if($orden['info'][0]->tipo_orden == 'f'){
        $this->load->model('facturacion_model');
        $this->load->model('documentos_model');
        $facturasss = explode('|', $orden['info'][0]->ids_facrem);
        $info_bascula = false;
        if (count($facturasss) > 0)
        {
          $clientessss = $facturassss = '';
          array_pop($facturasss);
          foreach ($facturasss as $key => $value)
          {
            $facturaa = explode(':', $value);
            $facturaa = $this->facturacion_model->getInfoFactura($facturaa[1]);
            $facturassss .= '/'.$facturaa['info']->serie.$facturaa['info']->folio.' '.$facturaa['info']->fechaT;
            $clientessss .= ', '.$facturaa['info']->cliente->nombre_fiscal;

            if($info_bascula === false)
            {
              $info_bascula = $this->documentos_model->getClienteDocs($facturaa['info']->id_factura, 1);
              if(!isset($info_bascula[0]) || $info_bascula[0]->data == 'NULL' )
                $info_bascula = false;
            }
          }
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->Row(array('FOLIO: '.substr($facturassss, 1) ), false, false);
        }
        $pdf->SetX(6);
        $pdf->Row(array('CLIENTE: '.$orden['info'][0]->cliente), false, false);
        $pdf->SetXY(6, $pdf->GetY()+6);
        $pdf->Row(array('________________________________________________________________________________________________'), false, false);
        $pdf->SetXY(6, $pdf->GetY()-2);
        $pdf->Row(array('CHOFER: '.strtoupper($orden['info'][0]->empleado_solicito)), false, false);
      }else
      {
        $pdf->SetAligns(array('L', 'R'));
        $pdf->SetWidths(array(104, 50));
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado), ($tipoCambio>1 ? "TIPO DE CAMBIO: " . $tipoCambio : '') ), false, false);
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(154));
        $pdf->SetXY(6, $pdf->GetY()-2);
        $pdf->Row(array('SOLICITA: '.strtoupper($orden['info'][0]->empleado_solicito)), false, false);
      }

      $pdf->SetXY(6, $pdf->GetY()+6);
      $pdf->Row(array('________________________________________________________________________________________________'), false, false);
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->Row(array('AUTORIZA: '.strtoupper($orden['info'][0]->autorizo)), false, false);
      $yy2 = $pdf->GetY();
      if(count($codigoAreas) > 0){
        // $yy2 -= 9;
        // $pdf->SetXY(160, $yy2);
        // $pdf->Row(array('_______________________________'), false, false);
        $yy2 = $pdf->GetY();
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetWidths(array(155));
        $pdf->Row(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
      }
      // ($tipoCambio ? "TIPO DE CAMBIO: " . $tipoCambio : ''),

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array('OBSERVACIONES: '.$orden['info'][0]->descripcion), false, false);
      if($orden['info'][0]->tipo_orden == 'f'){
        $pdf->SetWidths(array(205));
        $pdf->SetX(6);
        $pdf->Row(array(substr($clientessss, 2)), false, false);
        $pdf->SetXY(6, $pdf->GetY()-3);
        $pdf->Row(array('_________________________________________________________________________________________________________________________________'), false, false);
      }

      $pdf->SetFont('Arial','B',8);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array('PROVEEDOR: ES INDISPENSABLE PRESENTAR ESTA ORDEN DE COMPRA JUNTO CON SU FACTURA PARA QUE PROCEDA SU PAGO.'), false, false);

      $y_compras = $pdf->GetY();

      //Totales
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(160, $yy);
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(25, 25));
      $pdf->Row(array('SUB-TOTAL', String::formatoNumero($subtotal, 2, '$', false)), false, true);
      $pdf->SetX(160);
      $pdf->Row(array('IVA', String::formatoNumero($iva, 2, '$', false)), false, true);
      if ($ieps > 0)
      {
        $pdf->SetX(160);
        $pdf->Row(array('IEPS', String::formatoNumero($ieps, 2, '$', false)), false, true);
      }
      if ($retencion > 0)
      {
        $pdf->SetX(160);
        $pdf->Row(array('Ret. IVA', String::formatoNumero($retencion, 2, '$', false)), false, true);
      }
      $pdf->SetX(160);
      $pdf->Row(array('TOTAL', String::formatoNumero($total, 2, '$', false)), false, true);
      //a si es flete
      if($orden['info'][0]->tipo_orden == 'f' && is_array($info_bascula) && $info_bascula[0]->data != null){
        $info_bascula = json_decode($info_bascula[0]->data);
        if(isset($info_bascula->no_ticket{0}))
        {
          $this->load->model('bascula_model');
          $id_bascula = $this->bascula_model->getIdfolio($info_bascula->no_ticket, 'sa', $info_bascula->area_id);
          $data_bascula = $this->bascula_model->getBasculaInfo($id_bascula);

          $pdf->SetX(160);
          $pdf->Row(array('Ticket No', String::formatoNumero($info_bascula->no_ticket, 2, '')), false, false);
          $pdf->SetX(160);
          $pdf->Row(array('Bruto', String::formatoNumero($data_bascula['info'][0]->kilos_bruto, 2, '', false)), false, false);
          $pdf->SetX(160);
          $pdf->Row(array('Tara', String::formatoNumero($data_bascula['info'][0]->kilos_tara, 2, '', false)), false, false);
          $pdf->SetX(160);
          $pdf->Row(array('Neto', String::formatoNumero($data_bascula['info'][0]->kilos_neto, 2, '', false)), false, false);
        }
      }

      $pdf->SetWidths(array(154));

      if($orden['info'][0]->status == 'f'){
        $pdf->SetAligns(array('C'));
        $pdf->SetY($y_compras);
        foreach ($orden['info'][0]->compras as $key => $value)
         {
           $query = $this->db->query("SELECT c.id_compra, c.serie, c.folio, c.total, Date(ca.fecha) AS fecha_pago, ca.ref_movimiento, bc.alias, Sum(ca.total) AS pagado
              FROM compras c
                LEFT JOIN compras_abonos ca ON c.id_compra = ca.id_compra
                LEFT JOIN banco_cuentas bc ON ca.id_cuenta = bc.id_cuenta
              WHERE c.id_compra = {$value->id_compra}
              GROUP BY c.id_compra, c.serie, c.folio, Date(ca.fecha), ca.ref_movimiento, bc.alias");
           $total_compra = $pagado_compra = 0;
           foreach ($query->result() as $keyd => $compra1)
           {
            $pagado_compra += $compra1->pagado;
            $total_compra = $compra1;
           }
           $query->free_result();
           if ($total_compra->total > 0) {
            $pdf->SetX(20);
            $pdf->Row(array(
              ($pagado_compra == $total_compra->total? 'PAGADO ':'PENDIENTE ').String::fechaATexto($total_compra->fecha_pago, '/c').' '.
              $total_compra->ref_movimiento.' '.$total_compra->alias.' ('.$total_compra->serie.$total_compra->folio.')'), false);
           }
         }
      }

      if ($path)
      {
        $file = $path.'ORDEN_COMPRA_'.date('Y-m-d').'.pdf';
        $pdf->Output($file, 'F');
        return $file;
      }
      else
      {
        $pdf->Output('ORDEN_COMPRA_'.date('Y-m-d').'.pdf', 'I');
      }
   }

  public function print_orden_compra_ticket($ordenId, $path = null)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $orden = $this->info($ordenId, true);

    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->limiteY = 50;
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->show_head = false;

    // $pdf->show_head = true;
    // $pdf->titulo1 = $orden['info'][0]->empresa;
    $tipo_orden = 'ORDEN DE COMPRA';
    if($orden['info'][0]->tipo_orden == 'd')
      $tipo_orden = 'ORDEN DE SERVICIO';
    elseif($orden['info'][0]->tipo_orden == 'f')
      $tipo_orden = 'ORDEN DE FLETE';

    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, -1);
    $pdf->Row(array($orden['info'][0]->empresa), false, false);

    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array($tipo_orden), false, false);

    $pdf->SetFont('helvetica','', 8);
    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetX(0);
    $pdf->Row(array('No '.String::formatoNumero($orden['info'][0]->folio, 2, ''), String::fechaATexto($orden['info'][0]->fecha, '/c') ), false, false);

    $pdf->SetFont('helvetica','', 7);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('Proveedor: ' . $orden['info'][0]->proveedor), false, false);

    $pdf->SetAligns(array('C'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('____________________________________________'), false, false);


    // $aligns = array('C', 'C', 'L', 'R', 'R');
    // $widths = array(25, 35, 76, 18, 25, 25);
    // $header = array('CANT.', 'CODIGO', 'DESCRIPCION', 'F COMPRA', 'PRECIO', 'IMPORTE');

    $subtotal = $iva = $total = $retencion = $ieps = 0;

    $tipoCambio = 0;
    $codigoAreas = array();

    foreach ($orden['info'][0]->productos as $key => $prod)
    {
      $tipoCambio = 1;
      if ($prod->tipo_cambio != 0)
      {
        $tipoCambio = $prod->tipo_cambio;
      }

      // $band_head = false;
      // if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
      //   if($pdf->GetY()+5 >= $pdf->limiteY)
      //     $pdf->AddPage();

      //   $pdf->SetFont('Arial','B',8);
      //   $pdf->SetTextColor(255,255,255);
      //   $pdf->SetFillColor(160,160,160);
      //   $pdf->SetX(6);
      //   $pdf->SetAligns($aligns);
      //   $pdf->SetWidths($widths);
      //   $pdf->Row($header, true);
      // }

      $pdf->SetFont('Arial','',7);
      $pdf->SetTextColor(0,0,0);
      $datos = array(
        $prod->cantidad.' '.$prod->abreviatura,
        $prod->codigo.'/'.$prod->codigo_fin,
        $prod->descripcion.($prod->observacion!=''? " ({$prod->observacion})": ''),
        $this->getFechaUltimaCompra($prod->id_producto, $prod->id_area, $prod->campo),
        String::formatoNumero($prod->precio_unitario/$tipoCambio, 2, '$', false),
        String::formatoNumero($prod->importe/$tipoCambio, 2, '$', false),
      );

      // $pdf->SetFont('helvetica','', 8);
      $pdf->SetWidths(array(20, 43));
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array($this->getFechaUltimaCompra($prod->id_producto, $prod->id_area, $prod->campo), $prod->codigo.'/'.$prod->codigo_fin), false, false);
      $pdf->SetWidths(array(63));
      $pdf->SetAligns(array('L'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array($prod->descripcion.($prod->observacion!=''? " ({$prod->observacion})": '')), false, false);
      $pdf->SetWidths(array(20, 20, 23));
      $pdf->SetAligns(array('R', 'R', 'R'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array($prod->cantidad.' '.$prod->abreviatura,
                    String::formatoNumero($prod->precio_unitario/$tipoCambio, 2, '$', false),
                    String::formatoNumero($prod->importe/$tipoCambio, 2, '$', false)), false, false);

      $pdf->SetWidths(array(63));
      $pdf->SetAligns(array('C'));
      $pdf->SetXY(0, $pdf->GetY()-3);
      $pdf->Row(array('____________________________________________'), false, false);

      $subtotal  += floatval($prod->importe/$tipoCambio);
      $iva       += floatval($prod->iva/$tipoCambio);
      $total     += floatval($prod->total/$tipoCambio);
      $retencion += floatval($prod->retencion_iva/$tipoCambio);
      $ieps      += floatval($prod->ieps/$tipoCambio);

      if($prod->id_area != '' && !array_key_exists($prod->id_area, $codigoAreas))
        $codigoAreas[$prod->id_area] = $this->{($prod->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($prod->id_area);
    }

    $pdf->SetWidths(array(20, 20, 23));
    $pdf->SetAligns(array('L', 'R', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array(($tipoCambio>1 ? "TC: " . $tipoCambio : ''), 'SUB-TOTAL', String::formatoNumero($subtotal, 2, '$', false)), false, false);
    $pdf->SetWidths(array(20, 23));
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetXY(20, $pdf->GetY()-2);
    $pdf->Row(array('IVA', String::formatoNumero($iva, 2, '$', false)), false, false);
    if ($ieps > 0)
    {
      $pdf->SetXY(20, $pdf->GetY()-2);
      $pdf->Row(array('IEPS', String::formatoNumero($ieps, 2, '$', false)), false, false);
    }
    if ($retencion > 0)
    {
      $pdf->SetXY(20, $pdf->GetY()-2);
      $pdf->Row(array('Ret. IVA', String::formatoNumero($retencion, 2, '$', false)), false, false);
    }
    $pdf->SetXY(20, $pdf->GetY()-2);
    $pdf->Row(array('TOTAL', String::formatoNumero($total, 2, '$', false)), false, false);

    //Otros datos
    $pdf->SetX(0);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    if($orden['info'][0]->tipo_orden == 'f'){
      $this->load->model('facturacion_model');
      $this->load->model('documentos_model');
      $facturasss = explode('|', $orden['info'][0]->ids_facrem);
      $info_bascula = false;
      if (count($facturasss) > 0)
      {
        $clientessss = $facturassss = '';
        array_pop($facturasss);
        foreach ($facturasss as $key => $value)
        {
          $facturaa = explode(':', $value);
          $facturaa = $this->facturacion_model->getInfoFactura($facturaa[1]);
          $facturassss .= '/'.$facturaa['info']->serie.$facturaa['info']->folio.' '.$facturaa['info']->fechaT;
          $clientessss .= ', '.$facturaa['info']->cliente->nombre_fiscal;

          if($info_bascula === false)
          {
            $info_bascula = $this->documentos_model->getClienteDocs($facturaa['info']->id_factura, 1);
            if(!isset($info_bascula[0]) || $info_bascula[0]->data == 'NULL' )
              $info_bascula = false;
          }
        }
        $pdf->SetXY(0, $pdf->GetY()-1);
        $pdf->Row(array('FOLIO: '.substr($facturassss, 1) ), false, false);
      }
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array('CLIENTE: '.$orden['info'][0]->cliente), false, false);
      $pdf->SetAligns(array('C'));
      $pdf->SetXY(0, $pdf->GetY()+6);
      $pdf->Row(array('____________________________________________'), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array('CHOFER: '.strtoupper($orden['info'][0]->empleado_solicito)), false, false);
    }else
    {
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado) ), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array('SOLICITA: '.strtoupper($orden['info'][0]->empleado_solicito)), false, false);
    }


    $pdf->SetAligns(array('C'));
    $pdf->SetXY(0, $pdf->GetY()+3);
    $pdf->Row(array('____________________________________________'), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('AUTORIZA: '.strtoupper($orden['info'][0]->autorizo)), false, false);

    $pdf->SetAligns(array('L'));
    if(count($codigoAreas) > 0){
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
    }

    $pdf->SetXY(0, $pdf->GetY());
    if (strlen($orden['info'][0]->descripcion) > 0) {
      $pdf->Row(array('OBSERVACIONES: '.$orden['info'][0]->descripcion), false, false);
    }
    if($orden['info'][0]->tipo_orden == 'f'){
      $pdf->SetX(0);
      $pdf->Row(array(substr($clientessss, 2)), false, false);
      $pdf->SetXY(0, $pdf->GetY()-3);
      $pdf->Row(array('____________________________________________'), false, false);
    }

    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('PROVEEDOR: ES INDISPENSABLE PRESENTAR ESTA ORDEN DE COMPRA JUNTO CON SU FACTURA PARA QUE PROCEDA SU PAGO.'), false, false);

    $pdf->SetAligns(array('C'));
    $pdf->SetX(0);
    $pdf->Row(array( 'Impresión '.($orden['info'][0]->no_impresiones_tk==0? 'ORIGINAL': 'COPIA '.$orden['info'][0]->no_impresiones_tk)), false, false);

    $this->db->where('id_orden', $orden['info'][0]->id_orden)->set('no_impresiones_tk', 'no_impresiones_tk+1', false)->update('compras_ordenes');

    $pdf->Output('orden.pdf', 'I');

    // //a si es flete
    // if($orden['info'][0]->tipo_orden == 'f' && is_array($info_bascula) && $info_bascula[0]->data != null){
    //   $info_bascula = json_decode($info_bascula[0]->data);
    //   if(isset($info_bascula->no_ticket{0}))
    //   {
    //     $this->load->model('bascula_model');
    //     $id_bascula = $this->bascula_model->getIdfolio($info_bascula->no_ticket, 'sa', $info_bascula->area_id);
    //     $data_bascula = $this->bascula_model->getBasculaInfo($id_bascula);

    //     $pdf->SetX(160);
    //     $pdf->Row(array('Ticket No', String::formatoNumero($info_bascula->no_ticket, 2, '')), false, false);
    //     $pdf->SetX(160);
    //     $pdf->Row(array('Bruto', String::formatoNumero($data_bascula['info'][0]->kilos_bruto, 2, '', false)), false, false);
    //     $pdf->SetX(160);
    //     $pdf->Row(array('Tara', String::formatoNumero($data_bascula['info'][0]->kilos_tara, 2, '', false)), false, false);
    //     $pdf->SetX(160);
    //     $pdf->Row(array('Neto', String::formatoNumero($data_bascula['info'][0]->kilos_neto, 2, '', false)), false, false);
    //   }
    // }

    // $pdf->SetWidths(array(154));

    // if($orden['info'][0]->status == 'f'){
    //   $pdf->SetAligns(array('C'));
    //   $pdf->SetY($y_compras);
    //   foreach ($orden['info'][0]->compras as $key => $value)
    //    {
    //      $query = $this->db->query("SELECT c.id_compra, c.serie, c.folio, c.total, Date(ca.fecha) AS fecha_pago, ca.ref_movimiento, bc.alias, Sum(ca.total) AS pagado
    //         FROM compras c
    //           LEFT JOIN compras_abonos ca ON c.id_compra = ca.id_compra
    //           LEFT JOIN banco_cuentas bc ON ca.id_cuenta = bc.id_cuenta
    //         WHERE c.id_compra = {$value->id_compra}
    //         GROUP BY c.id_compra, c.serie, c.folio, Date(ca.fecha), ca.ref_movimiento, bc.alias");
    //      $total_compra = $pagado_compra = 0;
    //      foreach ($query->result() as $keyd => $compra1)
    //      {
    //       $pagado_compra += $compra1->pagado;
    //       $total_compra = $compra1;
    //      }
    //      $query->free_result();
    //      if ($total_compra->total > 0) {
    //       $pdf->SetX(20);
    //       $pdf->Row(array(
    //         ($pagado_compra == $total_compra->total? 'PAGADO ':'PENDIENTE ').String::fechaATexto($total_compra->fecha_pago, '/c').' '.
    //         $total_compra->ref_movimiento.' '.$total_compra->alias.' ('.$total_compra->serie.$total_compra->folio.')'), false);
    //      }
    //    }
    // }
  }

   public function getFechaUltimaCompra($id_producto, $id_codigo, $campo)
   {
    if ($id_producto > 0 && $id_codigo > 0) {
      $query = $this->db->query("SELECT Date(fecha_aceptacion) AS fecha
                                 FROM compras_productos
                                 WHERE id_producto = {$id_producto} AND {$campo} = {$id_codigo}")->row();
    }
    return isset($query->fecha)? $query->fecha: '';
   }

  public function getUltimaCompra($id_producto)
  {
    $query = null;
    if ($id_producto > 0) {
      $query = $this->db->query("SELECT *
                                 FROM compras_productos
                                 WHERE id_producto = {$id_producto}")->row();
    }
    return $query;
  }

  public function getInfoEntrada($folio, $empresa, $id_orden=null)
  {
    $sql = $id_orden? " AND cea.id_orden = {$id_orden} ": " AND cea.folio = {$folio} AND cea.id_empresa = {$empresa} ";
    $query = $this->db->query("SELECT cea.folio AS folio_almacen, Date(cea.fecha) AS fecha, cea.almacen,
                                  co.folio, e.nombre_fiscal AS empresa, p.nombre_fiscal AS proveeor,
                                  (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS recibio,
                                  (SELECT Coalesce(Sum(total), 0) FROM compras_productos WHERE id_orden = co.id_orden GROUP BY id_orden) AS total
                               FROM compras_entradas_almacen cea
                                INNER JOIN compras_ordenes co ON co.id_orden = cea.id_orden
                                INNER JOIN empresas e ON e.id_empresa = cea.id_empresa
                                INNER JOIN proveedores p ON p.id_proveedor = co.id_proveedor
                                INNER JOIN usuarios u ON u.id = cea.id_recibio
                               WHERE cea.status = 't' {$sql} ")->row();
    return $query;
  }

  public function imprimir_entrada($folio, $empresa)
  {
    $data = $this->getInfoEntrada($folio, $empresa);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->show_head = false;

    $pdf->AddPage();
    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetXY(0, 1);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array('INGRESO ALMACEN '.$data->almacen), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array($data->empresa), false, false);
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(30, 30));
    $pdf->Row(array('FECHA: '.String::fechaATexto($data->fecha, '/c'), 'REG. No '.$data->folio_almacen), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array($data->proveeor), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(25, 40));
    $pdf->Row(array('FOLIO: '.String::formatoNumero($data->folio, 2, ''), 'IMPORTE: '.String::formatoNumero($data->total)), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array('RECIBI: '.$data->recibio), false, false);

    $pdf->Rect(0.5, 0.5, 62, $pdf->GetY()+4);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }

  public function imprimir_entradatxt($folio, $empresa, $ruta)
  {
    $data = $this->getInfoEntrada($folio, $empresa);

    $file = fopen(APPPATH."media/imprimir/entradatxt.txt", "w");
    fwrite($file, "----------------------------------------\r\n");
    fwrite($file, '     INGRESO ALMACEN '.$data->almacen . "\r\n");
    fwrite($file, $data->empresa . "\r\n");
    fwrite($file, 'FECHA: '.String::fechaATexto($data->fecha, '/c').'  REG. No '.$data->folio_almacen . "\r\n");
    fwrite($file, $data->proveeor . "\r\n");
    fwrite($file, 'FOLIO: '.String::formatoNumero($data->folio, 2, '').'  IMPORTE: '.String::formatoNumero($data->total) . "\r\n");
    fwrite($file, 'RECIBI: '.$data->recibio . "\r\n");
    fwrite($file, "----------------------------------------\r\n");
    fclose($file);

    shell_exec("c:\\xampp\\htdocs\\sanjorge\\application\\media\\imprimir\\printApp.exe c:\\xampp\\htdocs\\sanjorge\\application\\media\\imprimir\\entradatxt.txt ".base64_decode($ruta));
    echo base64_decode($ruta);
    // exec('C:\Users\gama\Documents\sanjorge\application\printApp\printApp\bin\Debug\printApp.exe entradatxt.txt '.base64_decode($ruta));
  }

   /**
    * Visualiza/Descarga el PDF de la orden de compra.
    *
    * @return void
    */
   public function print_recibo_faltantes($ordenId)
   {
      $orden = $this->info($ordenId, true);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      // $pdf->show_head = true;
      $pdf->titulo2 = 'Proveedor: ' . $orden['info'][0]->proveedor;
      $pdf->titulo3 = " Fecha: ". date('Y-m-d') . ' Orden: ' . $orden['info'][0]->id_orden." \n RECIBO DE FALTANTES";

      $pdf->AliasNbPages();
      // $pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'C', 'C');
      $widths = array(25, 25, 129, 25);
      $header = array('CANT.', 'CODIGO', 'DESCRIPCION', 'FALTANTES');

      $subtotal = $iva = $total = 0;
      foreach ($orden['info'][0]->productos as $key => $prod)
      {
        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
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

        if ($prod->faltantes > 0)
        {
          $datos = array(
            $prod->cantidad,
            $prod->codigo,
            $prod->descripcion,
            $prod->faltantes,
          );

          $pdf->SetX(6);
          $pdf->Row($datos, false);
        }
      }

      $x = $pdf->GetX();
      $y = $pdf->GetY();

      $pdf->SetXY($x - 4, $y + 5);
      $pdf->cell(203, 6, '"PROVEEDOR: ES INDISPENSABLE PRESENTAR ESTA ORDEN DE COMPRA JUNTO CON SU FACTURA PAR QUE PROCEDA SU PAGO, GRACIAS"', false, false, 'L');

      $pdf->SetAligns(array('C', 'C', 'C'));
      $pdf->SetWidths(array(65, 65, 65));
      $pdf->SetX(6);
      $pdf->SetY($y + 11);
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->Row(array(
        'SOLICITA',
        'AUTORIZA',
        'REGISTRO',
      ), false, false);

      $pdf->SetY($y + 20);
      $pdf->Row(array(
        '____________________________________',
        '____________________________________',
        '____________________________________',
      ), false, false);

      $pdf->SetY($y + 30);
      $pdf->Row(array(
        strtoupper($orden['info'][0]->empleado_solicito),
        strtoupper($orden['info'][0]->autorizo),
        strtoupper($orden['info'][0]->empleado),
      ), false, false);

      // $pdf->AutoPrint(true);
      $pdf->Output('ORDEN_COMPRA_FALTANTES_'.date('Y-m-d').'.pdf', 'I');
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
   * ------------
   * Reporte de gastos
   * @return [type] [description]
   */
  public function getDataGastos()
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');
    $sql = $sql2 = '';

    //Filtro de fecha.
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql .= " AND Date(cs.fecha_creacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql .= " AND Date(cs.fecha_creacion) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql .= " AND Date(cs.fecha_creacion) = '".$this->input->get('ffecha2')."'";

    $sql2 = $sql;

    // vehiculos

    $response = array();


    if (isset($_GET['dareas']) && count($_GET['dareas']) > 0)
    {
      foreach ($_GET['dareas'] as $key => $value) {
        $ids_hijos = $value.$this->compras_areas_model->getHijos($value);
        $result = $this->db->query("SELECT ca.nombre, COALESCE(
                                      (SELECT Sum(csp.importe) AS importe
                                      FROM compras_ordenes cs
                                        INNER JOIN compras_productos csp ON cs.id_orden = csp.id_orden
                                      WHERE csp.status = 'a' AND csp.id_area In({$ids_hijos}) {$sql})
                                    , 0) AS importe
                                    FROM compras_areas ca
                                    WHERE ca.id_area = {$value}");
        $response[] = $result->row();
        $result->free_result();

        if (isset($_GET['dmovimientos']{0}) && $_GET['dmovimientos'] == '1' && $response[count($response)-1]->importe == 0)
          array_pop($response);
        else {
          // Si es desglosado carga independientes
          if (isset($_GET['ddesglosado']{0}) && $_GET['ddesglosado'] == '1') {
            $response[count($response)-1]->detalle = $this->db->query(
                "SELECT ca.id_area, ca.nombre, Date(cs.fecha_creacion) AS fecha, cs.folio, p.nombre AS producto, (csp.cantidad * csp.precio_unitario) AS importe
                FROM compras_ordenes cs
                  INNER JOIN compras_productos csp ON cs.id_orden = csp.id_orden
                  INNER JOIN compras_areas ca ON ca.id_area = csp.id_area
                  INNER JOIN productos p ON p.id_producto = csp.id_producto
                WHERE csp.status = 'a' AND ca.id_area In({$ids_hijos}) {$sql}
                ORDER BY nombre")->result();
          }
        }

      }
    }

    return $response;
  }
  public function rpt_gastos_pdf()
  {
    $combustible = $this->getDataGastos();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Reporte de Gastos";

    $pdf->titulo3 = ''; //"{$_GET['dproducto']} \n";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
    elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 .= "Del ".$_GET['ffecha1'];
    elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".$_GET['ffecha2'];

    $pdf->AliasNbPages();
    // $links = array('', '', '', '');
    $pdf->SetY(30);
    $aligns = array('L', 'R');
    $widths = array(170, 35);
    $header = array('Nombre', 'Importe');
    $aligns2 = array('L', 'L', 'L', 'L', 'R', 'R');
    $widths2 = array(18, 22, 65, 65, 35);
    $header2 = array('Fecha', 'Folio', 'C Costo', 'Producto', 'Importe');

    $lts_combustible = 0;
    $horas_totales = 0;

    $entro = false;
    foreach($combustible as $key => $vehiculo)
    {
      $cantidad = 0;
      $importe = 0;
      if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      {
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);

        if (isset($vehiculo->detalle)) {
          $pdf->SetX(6);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths2);
          $pdf->Row($header2, true);
        }
      }
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
        $vehiculo->nombre,
        String::formatoNumero($vehiculo->importe, 2, '', false),
      ), false, false);

      $lts_combustible += floatval($vehiculo->importe);

      if (isset($vehiculo->detalle)) {
        foreach ($vehiculo->detalle as $key2 => $item)
        {
          $band_head = false;
          if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
          {
            $pdf->AddPage();

            $pdf->SetFont('Arial','B',8);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFillColor(160,160,160);
            $pdf->SetX(6);
            $pdf->SetAligns($aligns2);
            $pdf->SetWidths($widths2);
            $pdf->Row($header2, true);
          }

          $pdf->SetFont('Arial','',8);
          $pdf->SetTextColor(0,0,0);

          $datos = array(
            $item->fecha,
            $item->folio,
            $item->nombre,
            $item->producto,
            String::formatoNumero($item->importe, 2, '', false),
          );

          $pdf->SetX(6);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths2);
          $pdf->Row($datos, false, false);
        }
      }

    }

    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);

    $pdf->SetFont('Arial','B',9);
    $pdf->SetTextColor(0,0,0);
    $pdf->Row(array('TOTALES',
        String::formatoNumero($lts_combustible, 2, '', false) ),
    true, false);

    $pdf->Output('reporte_gasto_codigo.pdf', 'I');
  }

  public function rpt_gastos_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_gasto_codigo.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $combustible = $this->getDataGastos();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Reporte de Gastos";
    $titulo3 = "";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $titulo3 .= "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
    elseif (!empty($_GET['ffecha1']))
        $titulo3 .= "Del ".$_GET['ffecha1'];
    elseif (!empty($_GET['ffecha2']))
        $titulo3 .= "Del ".$_GET['ffecha2'];

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="4" style="border:1px solid #000;background-color: #cccccc;">Nombre</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
    if (isset($combustible[0]->detalle)) {
      $html .= '<tr style="font-weight:bold">
        <td></td>
        <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">C Costo</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Producto</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Importe</td>
      </tr>';
    }
    $lts_combustible = $horas_totales = 0;
    foreach ($combustible as $key => $vehiculo)
    {
      $lts_combustible += floatval($vehiculo->importe);

      $html .= '<tr style="font-weight:bold">
          <td colspan="4" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->nombre.'</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->importe.'</td>
        </tr>';
      if (isset($vehiculo->detalle)) {
        foreach ($vehiculo->detalle as $key2 => $item)
        {
          $html .= '<tr>
              <td></td>
              <td style="width:100px;border:1px solid #000;background-color: #cccccc;">'.$item->fecha.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->folio.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->nombre.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->producto.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->importe.'</td>
            </tr>';
        }
      }

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="4">TOTALES</td>
          <td colspan="2" style="border:1px solid #000;">'.$lts_combustible.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }


  public function rpt_ordenes_data()
  {
    $response = array();
    $sql = '';

    $_GET['ffecha1'] = isset($_GET['ffecha1'])? $_GET['ffecha1']: date("Y-m-1");
    $_GET['ffecha2'] = isset($_GET['ffecha2'])? $_GET['ffecha2']: date("Y-m-d");
    $sql .= " AND Date(co.fecha_autorizacion) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."'";

    // //Filtros de area
    // $sql .= " AND bm.tipo = '".($this->input->get('ftipo')==='i'? 't': 'f')."'";


    // Obtenemos los rendimientos en los lotes de ese dia
    $query = $this->db->query(
      "SELECT co.id_orden, co.folio, p.nombre_fiscal AS proveedor, e.id_empresa, e.nombre_fiscal AS empresa,
        Sum(cp.total) AS total, string_agg(cp.descripcion, ', ') AS descripcion,
        Date(co.fecha_autorizacion) AS fecha, co.status
      FROM compras_ordenes co
        INNER JOIN proveedores p ON p.id_proveedor = co.id_proveedor
        INNER JOIN empresas e ON e.id_empresa = co.id_empresa
        INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
      WHERE co.status <> 'ca' AND co.status <> 'n' {$sql}
      GROUP BY co.id_orden, p.nombre_fiscal, e.id_empresa
      ORDER BY empresa ASC, folio ASC");
    if($query->num_rows() > 0) {
      $aux = 0;
      foreach ($query->result() as $key => $value) {
        $response[$value->id_empresa][] = $value;
      }
    }
    $query->free_result();


    return $response;
 }

 /**
  * Reporte de rendimientos de fruta
  * @return void
  */
 public function rpt_ordenes_pdf()
 {
    // Obtiene los datos del reporte.
    $data = $this->rpt_ordenes_data();
    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->titulo2 = "REPORTE DE ORDEN DE COMPRA ACUMULADO POR EMPRESA";
    $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} al {$fecha2->format('d/m/Y')}\n";
    // $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
    // $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

    $pdf->AliasNbPages();
    $pdf->AddPage();


    // Listado de Rendimientos
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetY($pdf->GetY()+2);

    $aligns = array('L', 'L', 'L', 'R', 'L', 'L');
    $widths = array(18, 18, 55, 22, 20, 73);
    $header = array('Fecha', 'Folio', 'Proveedor', 'Importe', 'Estado', 'Descripcion');

    $total_importes = 0;
    $total_importes_total = 0;

    foreach($data as $key => $movimiento)
    {
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetX(6);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(205));
      $pdf->Row(array(
        $movimiento[0]->empresa
      ), false, false);

      $total_importes = 0;
      foreach ($movimiento as $keym => $mov) {
        if($pdf->GetY() >= $pdf->limiteY || $keym==0) //salta de pagina si exede el max
        {
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
            $mov->fecha,
            $mov->folio,
            $mov->proveedor,
            String::formatoNumero($mov->total, 2, '$', true),
            ($mov->status=='f'? 'FACTURADA': 'PENDIENTE'),
            $mov->descripcion,
          ), false);

        $total_importes       += $mov->total;
        $total_importes_total += $mov->total;
      }

      //total
      $pdf->SetX(82);
      $pdf->SetAligns(array('R'));
      $pdf->SetWidths(array(37));
      $pdf->Row(array(
        String::formatoNumero($total_importes, 2, '$', true)
      ), false);
    }

    //total general
    $pdf->SetFont('helvetica','B',8);
    $pdf->SetTextColor(0 ,0 ,0 );
    $pdf->SetX(82);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(37));
    $pdf->Row(array(
      String::formatoNumero($total_importes_total, 2, '$', true)
    ), false);


    $pdf->Output('reporte_compra_acomulados.pdf', 'I');
 }

 public function rpt_ordenes_xls() {
    $data = $this->rpt_ordenes_data();

    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_compra_acomulados.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "REPORTE DE ORDEN DE COMPRA ACUMULADO POR EMPRESA";
    $titulo3 = "DEL {$fecha->format('d/m/Y')} al {$fecha2->format('d/m/Y')}";

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
    $total_importes_total = 0;
    foreach($data as $key => $movimiento)
    {
      $html .= '<tr>
          <td style="font-weight:bold;" colspan="6">'.$movimiento[0]->empresa.'</td>
        </tr>';
      $total_importes = 0;
      foreach ($movimiento as $keym => $mov) {
        if($keym==0) //salta de pagina si exede el max
        {
          $html .= '<tr style="font-weight:bold">
            <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Folio</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Proveedor</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Estado</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Descripcion</td>
          </tr>';
        }
        $html .= '<tr>
          <td style="border:1px solid #000;">'.$mov->fecha.'</td>
          <td style="border:1px solid #000;">'.$mov->folio.'</td>
          <td style="border:1px solid #000;">'.$mov->proveedor.'</td>
          <td style="border:1px solid #000;">'.$mov->total.'</td>
          <td style="border:1px solid #000;">'.($mov->status=='f'? 'FACTURADA': 'PENDIENTE').'</td>
          <td style="border:1px solid #000;">'.$mov->descripcion.'</td>
        </tr>';

        $total_importes       += $mov->total;
        $total_importes_total += $mov->total;
      }

      //total
      $html .= '<tr>
        <td style="" colspan="2"></td>
        <td style="border:1px solid #000;" colspan="2">'.$total_importes.'</td>
        <td style="" colspan="2"></td>
      </tr>';

    }

    $html .= '<tr>
        <td style="" colspan="2"></td>
        <td style="border:1px solid #000;" colspan="2">'.$total_importes_total.'</td>
        <td style="" colspan="2"></td>
      </tr>';

    echo $html;
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

  public function email($ordenId)
  {
    $this->load->model('proveedores_model');

    $orden = $this->info($ordenId);
    $proveedor = $this->proveedores_model->getProveedorInfo($orden['info'][0]->id_proveedor);

    if ($proveedor['info']->email !== '')
    {
      // Si el proveedor tiene email asigando le envia la orden.
      $this->load->library('my_email');

      $correoEmisorEm = "empaquesanjorge@hotmail.com"; // Correo con el q se emitira el correo.
      $nombreEmisor   = 'Empaque San Jorge';
      $correoEmisor   = "postmaster@empaquesanjorge.com"; // Correo para el auth. empaquesanjorgemx@gmail.com (mandrill)
      $contrasena     = "2b9f25bc4737f34edada0b29a56ff682"; // Contraseña de $correEmisor S4nj0rg3V14n3y

      $path = APPPATH . 'media/temp/';

      $file = $this->print_orden_compra($ordenId, $path);

      $datosEmail = array(
        'correoEmisorEm' => $correoEmisorEm,
        'correoEmisor'   => $correoEmisor,
        'nombreEmisor'   => $nombreEmisor,
        'contrasena'     => $contrasena,
        'asunto'         => 'Nueva orden de compra ' . date('Y-m-d H:m'),
        'altBody'        => 'Nueva orden de compra.',
        'body'           => 'Nueva orden de compra.',
        'correoDestino'  => array($proveedor['info']->email),
        'nombreDestino'  => $proveedor['info']->nombre_fiscal,
        'cc'             => '',
        'adjuntos'       => array('ORDEN_COMPRA_'.$orden['info'][0]->folio.'.pdf' => $file)
      );

      $result = $this->my_email->setData($datosEmail)->send();
      unlink($file);

      $msg = 10;
    }
    else
    {
      $msg = 11;
    }

    return array('passes' => true, 'msg' => $msg);
  }
}