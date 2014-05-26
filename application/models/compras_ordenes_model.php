<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class compras_ordenes_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
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
                (SELECT SUM(total) FROM compras_productos WHERE id_orden = co.id_orden) as total
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

  public function agregarData($data)
  {
    $this->db->insert('compras_ordenes', $data);

    return array('passes' => true, 'msg' => 3, 'id_orden' => $this->db->insert_id());
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

      $this->db->update('compras_ordenes', $data, array('id_orden' => $idOrden));

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
          'porcentaje_retencion' => $_POST['retTotal'][$key] == '0' ? '0' : '4',
          'faltantes' => $_POST['faltantes'][$key] === '' ? '0' : $_POST['faltantes'][$key],
          'observacion'     => $_POST['observacion'][$key],
          'status' => isset($_POST['isProdOk'][$key]) && $_POST['isProdOk'][$key] === '1' ? 'a' : 'p',
          'ieps'             => is_numeric($_POST['iepsTotal'][$key]) ? $_POST['iepsTotal'][$key] : 0,
          'porcentaje_ieps'  => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
          'tipo_cambio'      => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
        );
      }

      $this->db->delete('compras_productos', array('id_orden' => $idOrden));
      $this->db->insert_batch('compras_productos', $productos);

      //envia el email al momento de autorizar la orden
      if(isset($data['autorizado']))
        if($data['autorizado'] == 't')
          $this->sendEmail($idOrden, $_POST['proveedorId']);
    }

    return array('passes' => true, 'msg' => 7);
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

    // construye el array de las ordenes a ligar con la compra.
    $ordenes = array();
    foreach ($ordenesIds as $ordenId)
    {
      $ordenes[] = array(
        'id_compra' => $compraId,
        'id_orden'  => $ordenId,
      );

      $this->db->update('compras_ordenes', array('status' => 'f'), array('id_orden' => $ordenId));
    }

    // inserta los ids de las ordenes.
    $this->db->insert_batch('compras_facturas', $ordenes);

    // Actualiza los productos.
    foreach ($_POST['concepto'] as $key => $producto)
    {
      $prodData = array(
        'precio_unitario' => $_POST['valorUnitario'][$key],
        'importe'         => $_POST['importe'][$key],
        'iva'             => $_POST['trasladoTotal'][$key],
        'retencion_iva'   => $_POST['retTotal'][$key],
        'total'           => $_POST['total'][$key],
        'porcentaje_iva'  => $_POST['trasladoPorcent'][$key],
        'porcentaje_retencion'  => $_POST['retTotal'][$key] == '0' ? '0' : '4',
        'ieps'             => is_numeric($_POST['iepsTotal'][$key]) ? $_POST['iepsTotal'][$key] : 0,
        'porcentaje_ieps'  => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
      );

      $this->db->update('compras_productos', $prodData, array(
        'id_orden' => $_POST['ordenId'][$key],
        'num_row'  => $_POST['row'][$key]
      ));
    }

    $respons['passes'] = true;

    return $respons;
  }

  public function cancelar($idOrden)
  {
    $data = array('status' => 'ca');
    $this->actualizar($idOrden, $data);

    return array('passes' => true);
  }

  public function info($idOrden, $full = false)
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
              co.ids_facrem
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
        $query = $this->db->query(
          "SELECT cp.id_orden, cp.num_row,
                  cp.id_producto, pr.nombre AS producto, pr.codigo, pr.id_unidad, pu.abreviatura, pu.nombre as unidad,
                  cp.id_presentacion, pp.nombre AS presentacion, pp.cantidad as presen_cantidad,
                  cp.descripcion, cp.cantidad, cp.precio_unitario, cp.importe,
                  cp.iva, cp.retencion_iva, cp.total, cp.porcentaje_iva,
                  cp.porcentaje_retencion, cp.status, cp.faltantes, cp.observacion,
                  cp.ieps, cp.porcentaje_ieps, cp.tipo_cambio
           FROM compras_productos AS cp
           LEFT JOIN productos AS pr ON pr.id_producto = cp.id_producto
           LEFT JOIN productos_presentaciones AS pp ON pp.id_presentacion = cp.id_presentacion
           LEFT JOIN productos_unidades AS pu ON pu.id_unidad = pr.id_unidad
           WHERE id_orden = {$data['info'][0]->id_orden}");

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
    $this->db->delete('compras_productos', array('id_orden' => $idOrden));

    $ordenRechazada = false;

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
      );

      if ($faltantesProd !== '0')
      {
        $faltantes = true;
      }

      if ($_POST['isProdOk'][$key] === '0')
      {
        $ordenRechazada = true;
      }
    }

    // Si todos los productos fueron aceptados entonces la orden se marca
    // como aceptada.
    if ( ! $ordenRechazada)
    {
      $data = array(
        'fecha_aceptacion' => date('Y-m-d H:i:s'),
        'status'           => 'a',
      );

      $msg = 5;
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

    return array('status' => true, 'msg' => $msg, 'faltantes' => $faltantes);
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
      $pdf = new MYpdf('P', 'mm', 'Letter');
      // $pdf->show_head = true;
      $pdf->titulo1 = $orden['info'][0]->empresa;
      $pdf->titulo2 = 'Proveedor: ' . $orden['info'][0]->proveedor;
      $pdf->titulo3 = " Fecha: ". date('Y-m-d') . ' Orden: ' . $orden['info'][0]->folio;

      $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

      $pdf->AliasNbPages();
      // $pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'L', 'R', 'R');
      $widths = array(35, 25, 94, 25, 25);
      $header = array('CANT.', 'CODIGO', 'DESCRIPCION', 'PRECIO', 'IMPORTE');

      $subtotal = $iva = $total = $retencion = 0;

      $tipoCambio = 0;

      foreach ($orden['info'][0]->productos as $key => $prod)
      {
        if ($prod->tipo_cambio != 0)
        {
          $tipoCambio = $prod->tipo_cambio;
        }

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
        $datos = array(
          $prod->cantidad.' '.$prod->abreviatura,
          $prod->codigo,
          $prod->descripcion.($prod->observacion!=''? " ({$prod->observacion})": ''),
          String::formatoNumero($prod->precio_unitario, 2, '$', false),
          String::formatoNumero($prod->importe, 2, '$', false),
        );

        $pdf->SetX(6);
        $pdf->Row($datos, false);

        $subtotal += floatval($prod->importe);
        $iva      += floatval($prod->iva);
        $total    += floatval($prod->total);
        $retencion += floatval($prod->retencion_iva);
      }

      $pdf->SetX(6);
      $pdf->SetAligns(array('L', 'L', 'L', 'R'));
      $pdf->SetWidths(array(114, 40, 25, 25));
      $pdf->Row(array(
        'AREA DE APLICACION: ' . $orden['info'][0]->departamento,
        ($tipoCambio ? "TIPO DE CAMBIO: " . $tipoCambio : ''),
        'SUB-TOTAL',
        String::formatoNumero($subtotal, 2, '$', false),
      ), false, false);

      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(154, 25, 25));

      $dato_cliente = '';
      if($orden['info'][0]->tipo_orden == 'f')
        $dato_cliente = 'CLIENTE: '.$orden['info'][0]->cliente;
      $pdf->SetX(6);
      $pdf->Row(array(
        $dato_cliente,
        'IVA',
        String::formatoNumero($iva, 2, '$', false),
      ), false, false);

      if ($retencion > 0)
      {
        $pdf->SetX(6);
        $pdf->Row(array(
          '',
          'Ret. IVA',
          String::formatoNumero($retencion, 2, '$', false),
        ), false, false);
      }

      $pdf->SetX(6);
      $pdf->Row(array(
        'OBSERVACIONES: '.$orden['info'][0]->descripcion,
        'TOTAL',
        String::formatoNumero($total, 2, '$', false),
      ), false, false);

      if($orden['info'][0]->tipo_orden == 'f'){
        $this->load->model('facturacion_model');
        $facturasss = explode('|', $orden['info'][0]->ids_facrem);
        if (count($facturasss) > 0)
        {
          array_pop($facturasss);
          foreach ($facturasss as $key => $value)
          {
            $facturaa = explode(':', $value);
            $facturaa = $this->facturacion_model->getInfoFactura($facturaa[1], true);
            $pdf->SetX(6);
            $pdf->Row(array(
              ($facturaa['info']->is_factura=='t'? 'FACTURA: ': 'REMISION: ').$facturaa['info']->serie.$facturaa['info']->folio,
              '', '',
            ), false, false);
          }
        }
      }

      $x = $pdf->GetX();
      $y = $pdf->GetY();

      $pdf->SetXY($x - 4, $y + 5);
      $pdf->cell(203, 6, '"PROVEEDOR: ES INDISPENSABLE PRESENTAR ESTA ORDEN DE COMPRA JUNTO CON SU FACTURA PARA QUE PROCEDA SU PAGO, GRACIAS"', false, false, 'L');

      $pdf->SetAligns(array('C', 'C', 'C'));
      $pdf->SetWidths(array(65, 65, 65));
      $pdf->SetX(6);
      $pdf->SetY($y + 11);
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->Row(array(
        ($orden['info'][0]->tipo_orden == 'f'? 'CHOFER': 'SOLICITA'),
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