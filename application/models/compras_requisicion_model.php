<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class compras_requisicion_model extends CI_Model {

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
        "SELECT co.id_requisicion,
                co.id_empresa, e.nombre_fiscal AS empresa,
                co.id_departamento, cd.nombre AS departamento,
                co.id_empleado, u.nombre AS empleado,
                co.id_autorizo, us.nombre AS autorizo,
                array_to_string(array_agg(Distinct p.nombre_fiscal), '<br>') AS proveedor,
                co.folio, co.fecha_creacion AS fecha, co.fecha_autorizacion,
                co.fecha_aceptacion, co.tipo_pago, co.tipo_orden, co.status,
                co.autorizado
        FROM compras_requisicion AS co
        INNER JOIN empresas AS e ON e.id_empresa = co.id_empresa
        INNER JOIN compras_departamentos AS cd ON cd.id_departamento = co.id_departamento
        INNER JOIN compras_requisicion_productos AS crp ON co.id_requisicion = crp.id_requisicion
        INNER JOIN proveedores AS p ON p.id_proveedor = crp.id_proveedor
        INNER JOIN usuarios AS u ON u.id = co.id_empleado
        LEFT JOIN usuarios AS us ON us.id = co.id_autorizo
        WHERE co.status <> 'n'  {$sql}
        GROUP BY co.id_requisicion, e.nombre_fiscal, cd.nombre, u.nombre, us.nombre
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
      // 'id_proveedor'    => $_POST['proveedorId'],
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
    $this->db->insert('compras_requisicion', $data);
    $ordenId = $this->db->insert_id();

    // Bitacora
    $this->bitacora_model->_insert('compras_requisicion', $ordenId,
                                    array(':accion'     => 'la orden de requisicion', ':seccion' => 'ordenes de compra',
                                          ':folio'      => $data['folio'],
                                          ':id_empresa' => $data['id_empresa'],
                                          ':empresa'    => 'en '.$this->input->post('empresa')));

    //si se registra a un vehiculo
    if (isset($_POST['es_vehiculo']))
    {
      //si es de tipo gasolina o diesel se registra los litros
      if($_POST['tipo_vehiculo'] !== 'ot')
      {
        $this->db->insert('compras_vehiculos_reqs_gasolina', array(
          'id_requisicion'  => $ordenId,
          'kilometros' => $_POST['dkilometros'],
          'litros'     => $_POST['dlitros'],
          'precio'     => $_POST['dprecio'],
          ));
      }
    }

    $productos = array();
    foreach (array('1', '2', '3') as $value)
    {
      $id_proveedor = $_POST['proveedorId'.$value];
      if($id_proveedor != '')
      {
        foreach ($_POST['concepto'] as $key => $concepto)
        {
          if ($_POST['presentacionCant'][$key] !== '')
          {
            $cantidad = floatval($_POST['cantidad'][$key]) * floatval($_POST['presentacionCant'][$key]);
            $pu       = floatval($_POST['valorUnitario'.$value][$key]) / floatval($_POST['presentacionCant'][$key]);
          }
          else
          {
            $cantidad = $_POST['cantidad'][$key];
            $pu       = $_POST['valorUnitario'.$value][$key];
          }

          $productos[] = array(
            'id_requisicion'       => $ordenId,
            'id_proveedor'         => $id_proveedor,
            'num_row'              => $key,
            'id_producto'          => $_POST['productoId'][$key] !== '' ? $_POST['productoId'][$key] : null,
            'id_presentacion'      => $_POST['presentacion'][$key] !== '' ? $_POST['presentacion'][$key] : null,
            'descripcion'          => $concepto,
            'cantidad'             => $cantidad,
            'precio_unitario'      => $pu,
            'importe'              => $_POST['importe'.$value][$key],
            'iva'                  => $_POST['trasladoTotal'.$value][$key],
            'retencion_iva'        => $_POST['retTotal'.$value][$key],
            'total'                => $_POST['total'.$value][$key],
            'porcentaje_iva'       => $_POST['trasladoPorcent'][$key],
            'porcentaje_retencion' => $_POST['retTotal'.$value][$key] == '0' ? '0' : '4',
            // 'faltantes'         => $_POST['faltantes'.$value][$key] === '' ? '0' : $_POST['faltantes'.$value][$key],
            'observacion'          => $_POST['observacion'][$key],
            'ieps'                 => is_numeric($_POST['iepsTotal'.$value][$key]) ? $_POST['iepsTotal'.$value][$key] : 0,
            'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
            'tipo_cambio'          => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
            'id_area'              => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
          );
        }
      }
    }

    if(count($productos) > 0)
      $this->db->insert_batch('compras_requisicion_productos', $productos);

    return array('passes' => true, 'msg' => 3);
  }

  // public function agregarData($data)
  // {
  //   $this->db->insert('compras_ordenes', $data);

  //   return array('passes' => true, 'msg' => 3, 'id_orden' => $this->db->insert_id());
  // }

  // public function agregarProductosData($data)
  // {
  //   $this->db->insert_batch('compras_productos', $data);

  //   return array('passes' => true, 'msg' => 3);
  // }

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
        $this->db->update('compras_requisicion', $orden, array('id_requisicion' => $idOrden));
      }

      if ($productos)
      {
        $this->db->insert_batch('compras_requisicion_productos', $productos);
      }

      return array('passes' => true);
    }

    else
    {
      $datos_ordenn = $this->db->select("folio")
        ->from("compras_requisicion")
        ->where("id_requisicion", $idOrden)
        ->get()->row();

      $data = array(
        'id_empresa'      => $_POST['empresaId'],
        // 'id_proveedor'    => $_POST['proveedorId'],
        'id_departamento' => $_POST['departamento'],
        // 'id_autorizo'     => null,
        // 'id_empleado'     => $this->session->userdata('id_usuario'),
        // 'folio'           => $_POST['folio'],
        'fecha_creacion'  => str_replace('T', ' ', $_POST['fecha']),
        'tipo_pago'       => $_POST['tipoPago'],
        'tipo_orden'      => $_POST['tipoOrden'],
        'solicito'        => $_POST['solicito'],
        'id_cliente'      => (is_numeric($_POST['clienteId'])? $_POST['clienteId']: NULL),
        'descripcion'     => $_POST['descripcion'],
        'id_autorizo'     => (is_numeric($_POST['autorizoId'])? $_POST['autorizoId']: NULL),
      );

      if (isset($_POST['txtBtnAutorizar']) && $_POST['txtBtnAutorizar'] == 'true')
      {
        $data['id_autorizo']        = $_POST['autorizoId']; //$this->session->userdata('id_usuario');
        $data['fecha_autorizacion'] = date('Y-m-d H:i:s');
        $data['autorizado']         = 't';
      }

      // // Si esta modificando una orden rechazada entonces agrega mas campos
      // // que se actualizaran.
      // if ($status === 'r')
      // {
      // //   $data['id_autorizo'] = null;
      //   $data['status']      = 'p';
      // //   $data['autorizado']  = 'f';
      // }

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
      $id_bitacora = $this->bitacora_model->_update('compras_requisicion', $idOrden, $data,
                                array(':accion'       => 'la orden de requisicion', ':seccion' => 'ordenes de compra',
                                      ':folio'        => $datos_ordenn->folio,
                                      ':id_empresa'   => $data['id_empresa'],
                                      ':empresa'      => 'en '.$this->input->post('empresa'),
                                      ':id'           => 'id_requisicion',
                                      ':titulo'       => 'Orden de requisicion'));

      $this->db->update('compras_requisicion', $data, array('id_requisicion' => $idOrden));

      //si se registra a un vehiculo
      if (isset($_POST['es_vehiculo']))
      {
        //si es de tipo gasolina o diesel se registra los litros
        if($_POST['tipo_vehiculo'] !== 'ot')
        {
          $this->db->delete('compras_vehiculos_reqs_gasolina', array('id_requisicion' => $idOrden));
          $this->db->insert('compras_vehiculos_reqs_gasolina', array(
            'id_requisicion' => $idOrden,
            'kilometros'     => $_POST['dkilometros'],
            'litros'         => $_POST['dlitros'],
            'precio'         => $_POST['dprecio'],
            ));
        }
      }
      else
      {
        $this->db->delete('compras_vehiculos_reqs_gasolina', array('id_requisicion' => $idOrden));
      }

      $productos = array();
      foreach (array('1', '2', '3') as $value)
      {
        $id_proveedor = $_POST['proveedorId'.$value];
        if($id_proveedor != '')
        {
          foreach ($_POST['concepto'] as $key => $concepto)
          {
            if ($_POST['presentacionCant'][$key] !== '')
            {
              $cantidad = floatval($_POST['cantidad'][$key]) * floatval($_POST['presentacionCant'][$key]);
              $pu       = floatval($_POST['valorUnitario'.$value][$key]) / floatval($_POST['presentacionCant'][$key]);
            }
            else
            {
              $cantidad = $_POST['cantidad'][$key];
              $pu       = $_POST['valorUnitario'.$value][$key];
            }

            $prod_sel = 'f';
            if (isset($_POST[ 'prodSelOrden'.$_POST['prodIdNumRow'][$key] ][0]) &&
                $_POST[ 'prodSelOrden'.$_POST['prodIdNumRow'][$key] ][0] == $id_proveedor &&
                isset($data['autorizado']))
            {
              $prod_sel = 't';
            }

            $productos[] = array(
              'id_requisicion'       => $idOrden,
              'id_proveedor'         => $id_proveedor,
              'num_row'              => $key,
              'id_producto'          => $_POST['productoId'][$key] !== '' ? $_POST['productoId'][$key] : null,
              'id_presentacion'      => $_POST['presentacion'][$key] !== '' ? $_POST['presentacion'][$key] : null,
              'descripcion'          => $concepto,
              'cantidad'             => $cantidad,
              'precio_unitario'      => $pu,
              'importe'              => $_POST['importe'.$value][$key],
              'iva'                  => $_POST['trasladoTotal'.$value][$key],
              'retencion_iva'        => $_POST['retTotal'.$value][$key],
              'total'                => $_POST['total'.$value][$key],
              'porcentaje_iva'       => $_POST['trasladoPorcent'][$key],
              'porcentaje_retencion' => $_POST['retTotal'.$value][$key] == '0' ? '0' : '4',
              // 'faltantes'         => $_POST['faltantes'.$value][$key] === '' ? '0' : $_POST['faltantes'.$value][$key],
              'observacion'          => $_POST['observacion'][$key],
              'ieps'                 => is_numeric($_POST['iepsTotal'.$value][$key]) ? $_POST['iepsTotal'.$value][$key] : 0,
              'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
              'tipo_cambio'          => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
              'id_area'              => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
              'prod_sel'             => $prod_sel,
            );
          }
        }
      }

      // Bitacora
      $this->bitacora_model->_updateExt($id_bitacora, 'compras_requisicion_productos', $idOrden, $productos,
                                array(':id'             => 'id_requisicion',
                                      ':titulo'         => 'Productos',
                                      ':updates_fields' => 'compras_requisicion_productos'));

      $this->db->delete('compras_requisicion_productos', array('id_requisicion' => $idOrden));
      $this->db->insert_batch('compras_requisicion_productos', $productos);


      // Generar las ordenes compra
      if(isset($data['autorizado']))
        if($data['autorizado'] == 't')
          $this->crearOrdenes($idOrden);

    }


    return array('passes' => true, 'msg' => 7, 'autorizado' => isset($data['autorizado']));
  }


  public function crearOrdenes($idOrden)
  {
    $this->load->model('compras_ordenes_model');

    $data = $this->info($idOrden, true, false)['info'][0];

    // Se asignan los productos seleccionados x cada proveedor
    foreach ($data->productos2 as $key => $value)
    {
      foreach ($data->proveedores as $keyp => $prov)
      {
        if ($value->id_proveedor == $prov['id_proveedor'] && $value->prod_sel == 't')
        {
          $data->proveedores[$keyp]['productos'][] = $value;
        }
      }
    }

    // Se crean los ordenes de compra con productos por proveedor
    foreach ($data->proveedores as $key => $value)
    {
      if(isset($value['productos']) && count($value['productos']) > 0)
      {
        $dataOrden = array(
          'id_empresa'         => $data->id_empresa,
          'id_proveedor'       => $value['id_proveedor'],
          'id_departamento'    => $data->id_departamento,
          'id_empleado'        => $data->id_empleado,
          'folio'              => $this->compras_ordenes_model->folio($data->tipo_orden),
          'status'             => 'p',
          'autorizado'         => 't',
          'fecha_autorizacion' => $data->fecha_autorizacion,
          'fecha_aceptacion'   => $data->fecha_aceptacion,
          'fecha_creacion'     => $data->fecha,
          'tipo_pago'          => $data->tipo_pago,
          'tipo_orden'         => $data->tipo_orden,
          'solicito'           => $data->empleado_solicito,
          'id_cliente'         => (is_numeric($data->id_cliente)? $data->id_cliente: NULL),
          'descripcion'        => $data->descripcion,
          'id_autorizo'        => $data->id_autorizo,
        );
        //si se registra a un vehiculo
        if (is_numeric($data->id_vehiculo))
        {
          $dataOrden['tipo_vehiculo'] = $data->tipo_vehiculo;
          $dataOrden['id_vehiculo']   = $data->id_vehiculo;
        }
        //si es flete
        if ($data->tipo_orden == 'f')
        {
          $dataOrden['ids_facrem'] = $data->ids_facrem;
        }

        // si se registra a un vehiculo
        $veiculoData = array();
        if (is_numeric($data->id_vehiculo))
        {
          //si es de tipo gasolina o diesel se registra los litros
          if($data->tipo_vehiculo !== 'ot')
          {
            $veiculoData = array(
              'id_orden'   => null,
              'kilometros' => $data->gasolina[0]->kilometros,
              'litros'     => $data->gasolina[0]->litros,
              'precio'     => $data->gasolina[0]->precio,
            );
          }
        }

        $res = $this->compras_ordenes_model->agregarData($dataOrden, $veiculoData);
        $id_orden = $res['id_orden'];

        // Productos
        $rows_compras = 0;
        $productos = array();
        foreach ($value['productos'] as $keypr => $prod)
        {
          $productos[] = array(
            'id_orden'             => $id_orden,
            'num_row'              => $rows_compras,
            'id_producto'          => $prod->id_producto,
            'id_presentacion'      => $prod->id_presentacion !== '' ? $prod->id_presentacion : null,
            'descripcion'          => $prod->descripcion,
            'cantidad'             => $prod->cantidad,
            'precio_unitario'      => $prod->precio_unitario,
            'importe'              => $prod->importe,
            'iva'                  => $prod->iva,
            'retencion_iva'        => $prod->retencion_iva,
            'total'                => $prod->total,
            'porcentaje_iva'       => $prod->porcentaje_iva,
            'porcentaje_retencion' => $prod->porcentaje_retencion,
            'faltantes'            => '0',
            'observacion'          => $prod->observacion,
            'ieps'                 => $prod->ieps,
            'porcentaje_ieps'      => $prod->porcentaje_ieps,
            'tipo_cambio'          => $prod->tipo_cambio,
            'id_area'              => $prod->id_area,
          );
          $rows_compras++;
        }

        if(count($productos) > 0)
          $this->compras_ordenes_model->agregarProductosData($productos);

      }
    }

  }


  // /**
  //  * Agrega una compra. Esto es cuando se agregan o ligan ordenes a una factura.
  //  *
  //  * @param  string $proveedorId
  //  * @param  string $ordenesIds
  //  * @return array
  //  */
  // public function agregarCompra($proveedorId, $empresaId, $ordenesIds, $xml = null)
  // {
  //   // obtiene un array con los ids de las ordenes a ligar con la compra.
  //   $ordenesIds = explode(',', $ordenesIds);

  //   // datos de la compra.
  //   $data = array(
  //     'id_proveedor'   => $proveedorId,
  //     'id_empresa'     => $empresaId,
  //     'id_empleado'    => $this->session->userdata('id_usuario'),
  //     'serie'          => $_POST['serie'],
  //     'folio'          => $_POST['folio'],
  //     'condicion_pago' => $_POST['condicionPago'],
  //     'plazo_credito'  => $_POST['plazoCredito'] !== '' ? $_POST['plazoCredito'] : 0,
  //     // 'tipo_documento' => $_POST['algo'],
  //     'fecha'          => str_replace('T', ' ', $_POST['fecha']),
  //     'subtotal'       => $_POST['totalImporte'],
  //     'importe_iva'    => $_POST['totalImpuestosTrasladados'],
  //     'importe_ieps'   => $_POST['totalIeps'],
  //     'total'          => $_POST['totalOrden'],
  //     'concepto'       => 'Concepto',
  //     'isgasto'        => 'f',
  //     'status'         => $_POST['condicionPago'] ===  'co' ? 'pa' : 'p',
  //   );

  //   // //si es contado, se verifica que la cuenta tenga saldo
  //   // if ($data['condicion_pago'] == 'co')
  //   // {
  //   //   $this->load->model('banco_cuentas_model');
  //   //   $cuenta = $this->banco_cuentas_model->getCuentas(false, $_POST['dcuenta']);
  //   //   if ($cuenta['cuentas'][0]->saldo < $data['total'])
  //   //     return array('passes' => false, 'msg' => 30);
  //   // }

  //   // Realiza el upload del XML.
  //   if ($xml && $xml['tmp_name'] !== '')
  //   {
  //     $this->load->library("my_upload");
  //     $this->load->model('proveedores_model');

  //     $proveedor = $this->proveedores_model->getProveedorInfo($proveedorId);
  //     $path      = $this->creaDirectorioProveedorCfdi($proveedor['info']->nombre_fiscal);

  //     $xmlName   = ($_POST['serie'] !== '' ? $_POST['serie'].'-' : '') . $_POST['folio'].'.xml';

  //     $config_upload = array(
  //       'upload_path'     => $path,
  //       'allowed_types'   => '*',
  //       'max_size'        => '2048',
  //       'encrypt_name'    => FALSE,
  //       'file_name'       => $xmlName,
  //     );
  //     $this->my_upload->initialize($config_upload);

  //     $xmlData = $this->my_upload->do_upload('xml');

  //     $xmlFile     = explode('application', $xmlData['full_path']);
  //     $data['xml'] = 'application'.$xmlFile[1];
  //   }

  //   // inserta la compra
  //   $this->db->insert('compras', $data);

  //   // obtiene el id de la compra insertada.
  //   $compraId = $this->db->insert_id();

  //   // //si es contado, se registra el abono y el retiro del banco
  //   // if ($data['condicion_pago'] == 'co')
  //   // {
  //   //   $this->load->model('cuentas_pagar_model');
  //   //   $data_abono = array('fecha'             => $data['fecha'],
  //   //                     'concepto'            => 'Pago de contado',
  //   //                     'total'               => $data['total'],
  //   //                     'id_cuenta'           => $this->input->post('dcuenta'),
  //   //                     'ref_movimiento'      => $this->input->post('dreferencia'),
  //   //                     'id_cuenta_proveedor' => $this->input->post('fcuentas_proveedor') );
  //   //   $_GET['tipo'] = 'f';
  //   //   $respons = $this->cuentas_pagar_model->addAbono($data_abono, $compraId);
  //   // }

  //   // Actualiza los productos.
  //   $productos_compra = $productos_compra2 = array();
  //   foreach ($_POST['concepto'] as $key => $producto)
  //   {
  //     if(isset($productos_compra[$_POST['ordenId'][$key]]))
  //       $productos_compra[$_POST['ordenId'][$key]]++;
  //     else{
  //       $productos_compra[$_POST['ordenId'][$key]] = 1;
  //       $productos_compra2[$_POST['ordenId'][$key]] = 0;
  //     }

  //     foreach ($_POST['productoCom'] as $keyp => $produc)
  //     {
  //       $produc = explode('|', $produc);
  //       if($_POST['ordenId'][$key] === $produc[0] && $_POST['row'][$key] === $produc[1]){
  //         $productos_compra2[$_POST['ordenId'][$key]]++;
  //         $prodData = array(
  //           'precio_unitario'      => $_POST['valorUnitario'][$key],
  //           'importe'              => $_POST['importe'][$key],
  //           'iva'                  => $_POST['trasladoTotal'][$key],
  //           'retencion_iva'        => $_POST['retTotal'][$key],
  //           'total'                => $_POST['total'][$key],
  //           'porcentaje_iva'       => $_POST['trasladoPorcent'][$key],
  //           'porcentaje_retencion' => $_POST['retTotal'][$key] == '0' ? '0' : '4',
  //           'ieps'                 => is_numeric($_POST['iepsTotal'][$key]) ? $_POST['iepsTotal'][$key] : 0,
  //           'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
  //           'id_compra'            => $compraId,
  //         );

  //         $this->db->update('compras_productos', $prodData, array(
  //           'id_orden' => $_POST['ordenId'][$key],
  //           'num_row'  => $_POST['row'][$key]
  //         ));
  //       }
  //     }

  //   }

  //   // construye el array de las ordenes a ligar con la compra.
  //   $ordenes = array();
  //   foreach ($ordenesIds as $ordenId)
  //   {
  //     $ordenes[] = array(
  //       'id_compra' => $compraId,
  //       'id_orden'  => $ordenId,
  //     );

  //     // Cambia a facturada hasta q todos los productos se ligan a las compras
  //     if($productos_compra[$ordenId] == $productos_compra2[$ordenId])
  //       $this->db->update('compras_ordenes', array('status' => 'f'), array('id_orden' => $ordenId));
  //   }
  //   // inserta los ids de las ordenes.
  //   $this->db->insert_batch('compras_facturas', $ordenes);

  //   $respons['passes'] = true;

  //   return $respons;
  // }

  public function cancelar($idOrden)
  {
    $data = array('status' => 'ca');
    $this->actualizar($idOrden, $data);

    // Bitacora
    $datosorden = $this->info($idOrden);
    $this->bitacora_model->_cancel('compras_requisicion', $idOrden,
                                    array(':accion'     => 'la orden de requisicion', ':seccion' => 'ordenes de compra',
                                          ':folio'      => $datosorden['info'][0]->folio,
                                          ':id_empresa' => $datosorden['info'][0]->id_empresa,
                                          ':empresa'    => 'de '.$datosorden['info'][0]->empresa));

    return array('passes' => true);
  }

  public function info($idOrden, $full = false, $prodAcep=false, $idCompra=NULL)
  {
    $this->load->model('compras_areas_model');

    $query = $this->db->query(
      "SELECT co.id_requisicion,
              co.id_empresa, e.nombre_fiscal AS empresa,
              e.logo,
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
              co.ids_facrem
       FROM compras_requisicion AS co
       INNER JOIN empresas AS e ON e.id_empresa = co.id_empresa
       INNER JOIN compras_departamentos AS cd ON cd.id_departamento = co.id_departamento
       INNER JOIN usuarios AS u ON u.id = co.id_empleado
       LEFT JOIN usuarios AS us ON us.id = co.id_autorizo
       LEFT JOIN clientes AS cl ON cl.id_cliente = co.id_cliente
       LEFT JOIN compras_vehiculos cv ON cv.id_vehiculo = co.id_vehiculo
       WHERE co.id_requisicion = {$idOrden}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();

      $query->free_result();
      if ($full)
      {
        $sql_produc = $prodAcep? " AND cp.prod_sel = 't'": '';
        // $sql_produc .= $idCompra!==NULL? " AND (cp.id_compra = {$idCompra} OR (cp.id_compra IS NULL AND Date(cp.fecha_aceptacion) <= '2014-05-26'))": '';
        $query = $this->db->query(
          "SELECT cp.id_requisicion, cp.num_row, p.id_proveedor, p.nombre_fiscal,
                  cp.id_producto, pr.nombre AS producto, pr.codigo, pr.id_unidad, pu.abreviatura, pu.nombre as unidad,
                  cp.id_presentacion, pp.nombre AS presentacion, pp.cantidad as presen_cantidad,
                  cp.descripcion, cp.cantidad, cp.precio_unitario, cp.importe,
                  cp.iva, cp.retencion_iva, cp.total, cp.porcentaje_iva,
                  cp.porcentaje_retencion, cp.observacion, cp.prod_sel,
                  cp.ieps, cp.porcentaje_ieps, cp.tipo_cambio, ca.id_area, ca.codigo_fin
           FROM compras_requisicion_productos AS cp
           LEFT JOIN proveedores AS p ON p.id_proveedor = cp.id_proveedor
           LEFT JOIN productos AS pr ON pr.id_producto = cp.id_producto
           LEFT JOIN productos_presentaciones AS pp ON pp.id_presentacion = cp.id_presentacion
           LEFT JOIN productos_unidades AS pu ON pu.id_unidad = pr.id_unidad
           LEFT JOIN compras_areas AS ca ON ca.id_area = cp.id_area
           WHERE cp.id_requisicion = {$data['info'][0]->id_requisicion} {$sql_produc}
           ORDER BY p.id_proveedor ASC, cp.id_producto ASC");

        $data['info'][0]->productos2 = array();
        $data['info'][0]->productos = array();
        $data_proveedores = $data['info'][0]->proveedores = $data['info'][0]->data_desCodigos = array();
        if ($query->num_rows() > 0)
        {
          $productos = $query->result();
          $provee = $productos[0]->id_proveedor;
          foreach ($productos as $key => $value)
          {
            $data['info'][0]->productos2[] = clone $value;

            $data_proveedores[$value->id_proveedor] = array('id_proveedor' => $value->id_proveedor,
                                                          'nombre_fiscal' => $value->nombre_fiscal);

            if($provee == $value->id_proveedor)
            {
              if($value->id_area != '')
                $data['info'][0]->data_desCodigos[] = $this->compras_areas_model->getDescripCodigo($value->id_area);

              $data['info'][0]->productos[$value->id_producto.$value->num_row]                                           = $value;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'precio_unitario'.$value->id_proveedor} = $value->precio_unitario;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'importe'.$value->id_proveedor}         = $value->importe;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'iva'.$value->id_proveedor}             = $value->iva;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'retencion_iva'.$value->id_proveedor}   = $value->retencion_iva;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'total'.$value->id_proveedor}           = $value->total;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'ieps'.$value->id_proveedor}            = $value->ieps;

              $data['info'][0]->productos[$value->id_producto.$value->num_row]->precio_unitario                          = 0;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->importe                                  = 0;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->iva                                      = 0;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->retencion_iva                            = 0;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->total                                    = 0;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->ieps                                     = 0;
            }else
            {
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'precio_unitario'.$value->id_proveedor} = $value->precio_unitario;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'importe'.$value->id_proveedor}         = $value->importe;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'iva'.$value->id_proveedor}             = $value->iva;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'retencion_iva'.$value->id_proveedor}   = $value->retencion_iva;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'total'.$value->id_proveedor}           = $value->total;
              $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'ieps'.$value->id_proveedor}            = $value->ieps;
            }
          }

          foreach ($data_proveedores as $key => $value)
          {
            $data['info'][0]->proveedores[] = $value;
          }
          for ($i = count($data['info'][0]->proveedores); $i < 3; $i++)
            $data['info'][0]->proveedores[] = array('id_proveedor' => '', 'nombre_fiscal' => '');
        }

        $query->free_result();

        $data['info'][0]->gasolina = array();
        if ($data['info'][0]->id_vehiculo)
        {
          // Vehiculo
          $query = $this->db->query(
            "SELECT cvg.id_requisicion, cvg.kilometros, cvg.litros, cvg.precio
             FROM compras_vehiculos_reqs_gasolina AS cvg
             WHERE cvg.id_requisicion = {$data['info'][0]->id_requisicion}");

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

        // //Compras de la orden
        // $this->load->model('compras_model');
        // $compras_data = $this->db->query("SELECT id_compra
        //                            FROM compras_facturas
        //                            WHERE id_orden = {$data['info'][0]->id_orden}");
        // $data['info'][0]->compras = $compras_data->result();

        // //eNTRADA ALMACEN
        // $data['info'][0]->entrada_almacen = array();
        // $data['info'][0]->entrada_almacen = $this->getInfoEntrada(0,0, $data['info'][0]->id_orden);
      }
    }
    return $data;
  }

  public function folio($tipo = 'p')
  {
    $res = $this->db->select('folio')
      ->from('compras_requisicion')
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
      $correoEmisor   = "empaquesanjorgemx@gmail.com"; // Correo para el auth.
      $contrasena     = "2x02pxeexCUpiKncoWI50Q"; // Contraseña de $correEmisor

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

    $this->load->model('productos_model');

    $almacen = array();
    $res_prodc_orden = $this->db->query("SELECT id_orden, num_row, id_compra FROM compras_productos
              WHERE id_orden = {$idOrden}")->result();
    $productos = array();
    $faltantes = false;
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

      $faltantesProd = $_POST['faltantes'][$key] === '' ? '0' : $_POST['faltantes'][$key];

      $prod_id_compra = NULL;
      foreach ($res_prodc_orden as $keyor => $ord)
      {
        if($_POST['prodIdOrden'][$key] == $ord->id_orden && $_POST['prodIdNumRow'][$key] == $ord->num_row)
          $prod_id_compra = $ord->id_compra;
      }
      $productos[] = array(
        'id_orden'        => $idOrden,
        'num_row'         => $key,
        'id_producto'     => $_POST['productoId'][$key] !== '' ? $_POST['productoId'][$key] : null,
        'id_presentacion' => $_POST['presentacion'][$key] !== '' ? $_POST['presentacion'][$key] : null,
        'descripcion'     => $concepto,
        'cantidad'        => $cantidad,
        'precio_unitario' => $pu,
        'importe'         => $_POST['importe'][$key],
        'iva'             => $_POST['trasladoTotal'][$key],
        'retencion_iva'   => $_POST['retTotal'][$key],
        'total'           => $_POST['total'][$key],
        'porcentaje_iva'  => $_POST['trasladoPorcent'][$key],
        'porcentaje_retencion'  => $_POST['retTotal'][$key] == '0' ? '0' : '4',
        'status'          => $_POST['isProdOk'][$key] === '1' ? 'a' : 'r',
        'fecha_aceptacion' => date('Y-m-d H:i:s'),
        'faltantes'       => $faltantesProd,
        'observacion'     => $_POST['observacion'][$key],
        'ieps'             => is_numeric($_POST['iepsTotal'][$key]) ? $_POST['iepsTotal'][$key] : 0,
        'porcentaje_ieps'  => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
        'tipo_cambio'      => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
        'id_compra'        => $prod_id_compra,
      );

      if ($faltantesProd !== '0')
      {
        $faltantes = true;
      }

      if ($_POST['isProdOk'][$key] === '0')
      {
        $ordenRechazada = true;
      }

      $producto_dd = $this->productos_model->getProductoInfo(false, false, $_POST['productoId'][$key]);
      if(count($producto_dd['info']) > 0 && !in_array($producto_dd['familia']->almacen, $almacen))
        $almacen[] = $producto_dd['familia']->almacen;
    }
    $this->db->delete('compras_productos', array('id_orden' => $idOrden));

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

    $this->actualizar($idOrden, $data, $productos);

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
              {$sqlEmpresa}
              pf.tipo = '{$tipo}' AND
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
              pf.nombre as familia, pf.codigo as codigo_familia,
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
                                {$filtro} AND f.fecha >= (now() - interval '5 months')
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
      $orden = $this->info($ordenId, true);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('L', 'mm', 'Letter');
      // $pdf->show_head = true;
      $pdf->titulo1 = $orden['info'][0]->empresa;
      $tipo_orden = 'ORDEN DE REQUISICION';


      $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

      $pdf->AliasNbPages();
      $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY()-10);

      $pdf->SetFont('helvetica','B', 10);
      $pdf->SetAligns(array('L', 'C', 'R'));
      $pdf->SetWidths(array(50, 160, 50));
      $pdf->Row(array(
        String::fechaATexto($orden['info'][0]->fecha, '/c'),
        $tipo_orden,
        'No '.String::formatoNumero($orden['info'][0]->folio, 2, ''),
      ), false, false);

      $aligns = array('L', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R');
      $widths2 = array(43, 43, 43);
      $widths = array(15, 10, 18, 30, 65, 18, 25, 18, 25, 18, 25);
      $header = array('AREA', 'PROD', 'CANT', 'UNIDAD', 'PRODUCTO', 'P.U.', 'IMPORTE', 'P.U.', 'IMPORTE', 'P.U.', 'IMPORTE');

      $subtotal = $iva = $total = $retencion = 0;
      foreach ($orden['info'][0]->proveedores as $keypp => $value)
      {
        $orden['info'][0]->proveedores[$keypp]['subtotal']  = 0;
        $orden['info'][0]->proveedores[$keypp]['iva']       = 0;
        $orden['info'][0]->proveedores[$keypp]['ieps']      = 0;
        $orden['info'][0]->proveedores[$keypp]['total']     = 0;
        $orden['info'][0]->proveedores[$keypp]['retencion'] = 0;
      }

      $tipoCambio = 0;
      $first = true;
      $pdf->SetXY(6, $pdf->GetY()+2);
      foreach ($orden['info'][0]->productos as $key => $prod)
      {
        $tipoCambio = 1;
        if ($prod->tipo_cambio != 0)
        {
          $tipoCambio = $prod->tipo_cambio;
        }

        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $first) { //salta de pagina si exede el max
          $first = false;
          if($pdf->GetY()+5 >= $pdf->limiteY)
            $pdf->AddPage();
          $pdf->SetFont('Arial','B',7);
          // $pdf->SetTextColor(255,255,255);
          // $pdf->SetFillColor(160,160,160);

          $pdf->SetX(144);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths2);
          $pdf->Row(array($orden['info'][0]->proveedores[0]['nombre_fiscal'],
                          $orden['info'][0]->proveedores[1]['nombre_fiscal'],
                          $orden['info'][0]->proveedores[2]['nombre_fiscal']), false);

          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, false);
        }


        $precio_unitario1 = $prod->{'precio_unitario'.$orden['info'][0]->proveedores[0]['id_proveedor']}/$tipoCambio*($prod->presen_cantidad>0?$prod->presen_cantidad:1);
        $precio_unitario2 = $prod->{'precio_unitario'.$orden['info'][0]->proveedores[1]['id_proveedor']}/$tipoCambio*($prod->presen_cantidad>0?$prod->presen_cantidad:1);
        $precio_unitario3 = $prod->{'precio_unitario'.$orden['info'][0]->proveedores[2]['id_proveedor']}/$tipoCambio*($prod->presen_cantidad>0?$prod->presen_cantidad:1);

        $pdf->SetFont('Arial','',7);
        $pdf->SetTextColor(0,0,0);
        $datos = array(
          $prod->codigo_fin,
          $prod->codigo,
          ($prod->cantidad/($prod->presen_cantidad>0?$prod->presen_cantidad:1)),
          ($prod->presentacion==''? $prod->unidad: $prod->presentacion),
          $prod->descripcion.($prod->observacion!=''? " ({$prod->observacion})": ''),
          String::formatoNumero($precio_unitario1, 2, '$', false),
          String::formatoNumero($prod->{'importe'.$orden['info'][0]->proveedores[0]['id_proveedor']}/$tipoCambio, 2, '$', false),
          String::formatoNumero($precio_unitario2, 2, '$', false),
          String::formatoNumero($prod->{'importe'.$orden['info'][0]->proveedores[1]['id_proveedor']}/$tipoCambio, 2, '$', false),
          String::formatoNumero($precio_unitario3, 2, '$', false),
          String::formatoNumero($prod->{'importe'.$orden['info'][0]->proveedores[2]['id_proveedor']}/$tipoCambio, 2, '$', false),
        );

        $pdf->SetX(6);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);

        foreach ($orden['info'][0]->proveedores as $keypp => $value)
        {
          $orden['info'][0]->proveedores[$keypp]['subtotal']  += floatval($prod->{'importe'.$orden['info'][0]->proveedores[$keypp]['id_proveedor']}/$tipoCambio);
          $orden['info'][0]->proveedores[$keypp]['iva']       += floatval($prod->{'iva'.$orden['info'][0]->proveedores[$keypp]['id_proveedor']}/$tipoCambio);
          $orden['info'][0]->proveedores[$keypp]['ieps']      += floatval($prod->{'ieps'.$orden['info'][0]->proveedores[$keypp]['id_proveedor']}/$tipoCambio);
          $orden['info'][0]->proveedores[$keypp]['total']     += floatval($prod->{'total'.$orden['info'][0]->proveedores[$keypp]['id_proveedor']}/$tipoCambio);
          $orden['info'][0]->proveedores[$keypp]['retencion'] += floatval($prod->{'retencion_iva'.$orden['info'][0]->proveedores[$keypp]['id_proveedor']}/$tipoCambio);
        }
      }

      //Totales
      $pdf->SetFont('Arial','B',7);
      $pdf->SetX(79);
      $pdf->SetAligns(array('R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(65, 43, 43, 43));
      $pdf->Row(array('SUB-TOTAL', String::formatoNumero($orden['info'][0]->proveedores[0]['subtotal'], 2, '$', false),
                                  String::formatoNumero($orden['info'][0]->proveedores[1]['subtotal'], 2, '$', false),
                                  String::formatoNumero($orden['info'][0]->proveedores[2]['subtotal'], 2, '$', false)), false, true);
      $pdf->SetX(79);
      $pdf->Row(array('IVA', String::formatoNumero($orden['info'][0]->proveedores[0]['iva'], 2, '$', false),
                            String::formatoNumero($orden['info'][0]->proveedores[1]['iva'], 2, '$', false),
                            String::formatoNumero($orden['info'][0]->proveedores[2]['iva'], 2, '$', false)), false, true);
      if ($orden['info'][0]->proveedores[0]['ieps'] > 0)
      {
        $pdf->SetX(79);
        $pdf->Row(array('IEPS', String::formatoNumero($orden['info'][0]->proveedores[0]['ieps'], 2, '$', false),
                                    String::formatoNumero($orden['info'][0]->proveedores[1]['ieps'], 2, '$', false),
                                    String::formatoNumero($orden['info'][0]->proveedores[2]['ieps'], 2, '$', false)), false, true);
      }
      if ($orden['info'][0]->proveedores[0]['retencion'] > 0)
      {
        $pdf->SetX(79);
        $pdf->Row(array('Ret. IVA', String::formatoNumero($orden['info'][0]->proveedores[0]['retencion'], 2, '$', false),
                                    String::formatoNumero($orden['info'][0]->proveedores[1]['retencion'], 2, '$', false),
                                    String::formatoNumero($orden['info'][0]->proveedores[2]['retencion'], 2, '$', false)), false, true);
      }
      $pdf->SetX(79);
      $pdf->Row(array('TOTAL', String::formatoNumero($orden['info'][0]->proveedores[0]['total'], 2, '$', false),
                              String::formatoNumero($orden['info'][0]->proveedores[1]['total'], 2, '$', false),
                              String::formatoNumero($orden['info'][0]->proveedores[2]['total'], 2, '$', false)), false, true);


      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(60, 50));
      $pdf->SetXY(6, $pdf->GetY()-15);
      $pdf->Row(array(($tipoCambio>0? "TIPO DE CAMBIO: " . $tipoCambio : '') ), false, false);

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(75));
      $pdf->SetXY(6, $pdf->GetY()+6);
      $pdf->Row(array('SOLICITA: __________________________________________'), false, false);
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->Row(array(strtoupper($orden['info'][0]->empleado_solicito)), false, false);

      // $pdf->SetAligns(array('L', 'R'));
      // $pdf->SetWidths(array(104, 50));
      // $pdf->SetXY(6, $pdf->GetY());
      // $pdf->Row(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado), ($tipoCambio>0? "TIPO DE CAMBIO: " . $tipoCambio : '') ), false, false);

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(250));
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array('OBSERVACIONES: '.$orden['info'][0]->descripcion), false, false);

      $pdf->SetWidths(array(250));
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array('DESCRIPCION DE CODIGOS: '.implode(', ', $orden['info'][0]->data_desCodigos)), false, false);


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
      $correoEmisor   = "empaquesanjorgemx@gmail.com"; // Correo para el auth.
      $contrasena     = "2x02pxeexCUpiKncoWI50Q"; // Contraseña de $correEmisor

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