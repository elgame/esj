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
  public function getOrdenes($perpage = '40', $autorizadas = true)
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
                co.autorizado
        FROM compras_ordenes AS co
        INNER JOIN empresas AS e ON e.id_empresa = co.id_empresa
        INNER JOIN proveedores AS p ON p.id_proveedor = co.id_proveedor
        INNER JOIN compras_departamentos AS cd ON cd.id_departamento = co.id_departamento
        INNER JOIN usuarios AS u ON u.id = co.id_empleado
        LEFT JOIN usuarios AS us ON us.id = co.id_autorizo
        WHERE 1 = 1 {$sql}
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
    );

    $this->db->insert('compras_ordenes', $data);

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
        'id_orden'        => $this->db->insert_id(),
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
      );
    }

    $this->db->insert_batch('compras_productos', $productos);

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
        'id_autorizo'     => null,
        'id_empleado'     => $this->session->userdata('id_usuario'),
        // 'folio'           => $_POST['folio'],
        'fecha_creacion'  => str_replace('T', ' ', $_POST['fecha']),
        'tipo_pago'       => $_POST['tipoPago'],
        'tipo_orden'      => $_POST['tipoOrden'],
      );

      if (isset($_POST['autorizar']) && $status === 'p')
      {
        $data['id_autorizo']        = $this->session->userdata('id_usuario');
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

      $this->db->update('compras_ordenes', $data, array('id_orden' => $idOrden));

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
          'porcentaje_retencion'  => $_POST['retTotal'][$key] == '0' ? '0' : '4',
        );
      }

      $this->db->delete('compras_productos', array('id_orden' => $idOrden));
      $this->db->insert_batch('compras_productos', $productos);
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
  public function agregarCompra($proveedorId, $empresaId, $ordenesIds)
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
      'total'          => $_POST['totalOrden'],
      'concepto'       => 'Concepto',
      'isgasto'        => 'f',
      'status'         => $_POST['condicionPago'] ===  'co' ? 'pa' : 'p',
    );

    // inserta la compra
    $this->db->insert('compras', $data);

    // obtiene el id de la compra insertada.
    $compraId = $this->db->insert_id();

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
      );

      $this->db->update('compras_productos', $prodData, array(
        'id_orden' => $_POST['ordenId'][$key],
        'num_row'  => $_POST['row'][$key]
      ));
    }

    return array('passes' => true);
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
              co.id_proveedor, p.nombre_fiscal AS proveedor,
              co.id_departamento, cd.nombre AS departamento,
              co.id_empleado, u.nombre AS empleado,
              co.id_autorizo, us.nombre AS autorizo,
              co.folio, co.fecha_creacion AS fecha, co.fecha_autorizacion,
              co.fecha_aceptacion, co.tipo_pago, co.tipo_orden, co.status,
              co.autorizado
       FROM compras_ordenes AS co
       INNER JOIN empresas AS e ON e.id_empresa = co.id_empresa
       INNER JOIN proveedores AS p ON p.id_proveedor = co.id_proveedor
       INNER JOIN compras_departamentos AS cd ON cd.id_departamento = co.id_departamento
       INNER JOIN usuarios AS u ON u.id = co.id_empleado
       LEFT JOIN usuarios AS us ON us.id = co.id_autorizo
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
                  cp.porcentaje_retencion, cp.status
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
      'id_autorizo'        => $this->session->userdata('id_usuario'),
      'fecha_autorizacion' => date('Y-m-d H:i:s'),
      'autorizado'         => 't',
    );

    $this->actualizar($idOrden, $data);

    return array('status' => true, 'msg' => 4);
  }

  public function entrada($idOrden)
  {
    $this->db->delete('compras_productos', array('id_orden' => $idOrden));

    $ordenRechazada = false;

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
        'porcentaje_retencion'  => $_POST['retTotal'][$key] == '0' ? '0' : '4',
        'status'          => $_POST['isProdOk'][$key] === '1' ? 'a' : 'r'
      );

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

    return array('status' => true, 'msg' => $msg);
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

    $sqlEmpresa = "";
    if ($idEmpresa)
    {
      $sqlEmpresa = "p.id_empresa = {$idEmpresa} AND";
    }

    $res = $this->db->query(
       "SELECT p.*,
              pf.nombre as familia, pf.codigo as codigo_familia,
              pu.nombre as unidad, pu.abreviatura as unidad_abreviatura
        FROM productos as p
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

}