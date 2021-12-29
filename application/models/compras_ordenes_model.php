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
  public function getOrdenes($perpage = '100', $autorizadas = true, $regresa_product = false)
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

    if($this->input->get('falmacen') != '')
    {
      $sql .= " AND co.id_almacen = ".$this->input->get('falmacen')."";
    }

    $sql .= $autorizadas ? " AND co.autorizado = 't'" : " AND co.autorizado = 'f'";

    $sql .= $regresa_product ? " AND co.regresa_product = 't'" : " AND co.status <> 'n'";

    $sql_fil_prod = '';
    if ($this->input->get('fconceptoId') > 0) {
      $sql_fil_prod = "INNER JOIN (
          SELECT id_orden FROM compras_productos WHERE id_producto = {$this->input->get('fconceptoId')} GROUP BY id_orden
        ) cp ON co.id_orden = cp.id_orden";
    }

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
                ) as prod_sincompras,
                ca.nombre AS almacen,
                clig.compras
        FROM compras_ordenes AS co
          {$sql_fil_prod}
          INNER JOIN empresas AS e ON e.id_empresa = co.id_empresa
          INNER JOIN proveedores AS p ON p.id_proveedor = co.id_proveedor
          INNER JOIN compras_departamentos AS cd ON cd.id_departamento = co.id_departamento
          INNER JOIN usuarios AS u ON u.id = co.id_empleado
          LEFT JOIN usuarios AS us ON us.id = co.id_autorizo
          LEFT JOIN compras_almacenes ca ON ca.id_almacen = co.id_almacen
          LEFT JOIN (
            SELECT cf.id_orden, STRING_AGG((Date(c.fecha)::text || ' / ' || c.serie || c.folio::text), '<br>') AS compras
            FROM compras_facturas cf
              INNER JOIN compras c ON c.id_compra = cf.id_compra
            GROUP BY cf.id_orden
          ) clig ON clig.id_orden = co.id_orden
        WHERE 1 = 1  {$sql}
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
      'cont_x_dia'      => $this->folioDia(substr($_POST['fecha'], 0, 10)),
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
    $ordenId = $this->db->insert_id('compras_ordenes_id_orden_seq');

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

  public function agregarData($data, $dataVeiculo=array(), $dataOrdenCats = [])
  {
    $this->db->insert('compras_ordenes', $data);
    $id_orden = $this->db->insert_id('compras_ordenes_id_orden_seq');

    $this->db->update('compras_ordenes', ['cont_x_dia' => $this->folioDia(substr($data['fecha_creacion'], 0, 10))], "id_orden = {$id_orden}");
    if(is_array($dataVeiculo) && count($dataVeiculo) > 0)
    {
      $dataVeiculo['id_orden'] = $id_orden;
      $this->db->insert('compras_vehiculos_gasolina', $dataVeiculo);
    }

    // liga las requisiciones
    if(isset($dataOrdenCats['requisiciones']) && count($dataOrdenCats['requisiciones']) > 0) {
      foreach ($dataOrdenCats['requisiciones'] as $keyr => $requiss) {
        $requiss['id_orden'] = $id_orden;
        $this->db->insert('compras_ordenes_requisiciones', $requiss);
      }
    }

    // Si es un gasto son requeridos los campos de catálogos
    if(isset($dataOrdenCats['area']) && count($dataOrdenCats['area']) > 0) {
      foreach ($dataOrdenCats['area'] as $keyr => $area) {
        $area['id_orden'] = $id_orden;
        $this->db->insert('compras_ordenes_areas', $area);
      }
    }
    if(isset($dataOrdenCats['rancho']) && count($dataOrdenCats['rancho']) > 0) {
      foreach ($dataOrdenCats['rancho'] as $keyr => $rancho) {
        $rancho['id_orden'] = $id_orden;
        $this->db->insert('compras_ordenes_rancho', $rancho);
      }
    }
    if(isset($dataOrdenCats['centroCosto']) && count($dataOrdenCats['centroCosto']) > 0) {
      foreach ($dataOrdenCats['centroCosto'] as $keyr => $centro_costo) {
        $centro_costo['id_orden'] = $id_orden;
        $this->db->insert('compras_ordenes_centro_costo', $centro_costo);
      }
    }
    if(isset($dataOrdenCats['activo']) && count($dataOrdenCats['activo']) > 0) {
      foreach ($dataOrdenCats['activo'] as $keyr => $activo) {
        $activo['id_orden'] = $id_orden;
        $this->db->insert('compras_ordenes_activos', $activo);
      }
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
        foreach ($productos as $key => $producto) {
          $this->db->insert('compras_productos', $producto);
        }
        // $this->db->insert_batch('compras_productos', $productos);
      }

      return array('passes' => true);
    }

    else
    {
      $ordennn = $this->db->select("status, otros_datos")
        ->from("compras_ordenes")
        ->where("id_orden", $idOrden)
        ->get()->row();
      $status = $ordennn->status;

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

        'folio_hoja'      => (!empty($this->input->post('folioHoja'))? $_POST['folioHoja']: NULL),
        'uso_cfdi'        => (!empty($this->input->post('duso_cfdi'))? $_POST['duso_cfdi']: 'G03'),
      );

      // Si es un gasto son requeridos los campos de catálogos
      if ($_POST['tipoOrden'] == 'd' || $_POST['tipoOrden'] == 'oc' || $_POST['tipoOrden'] == 'f' || $_POST['tipoOrden'] == 'a'
          || $_POST['tipoOrden'] == 'p') {
        // Inserta las areas
        $this->db->delete('compras_ordenes_areas', ['id_orden' => $idOrden]);
        if (isset($_POST['areaId']) && count($_POST['areaId']) > 0) {
          foreach ($_POST['areaId'] as $keyr => $id_area) {
            $this->db->insert('compras_ordenes_areas', [
              'id_area'  => $id_area,
              'id_orden' => $idOrden,
              'num'      => count($_POST['areaId'])
            ]);
          }
        }

        // Inserta los ranchos
        $this->db->delete('compras_ordenes_rancho', ['id_orden' => $idOrden]);
        if (isset($_POST['ranchoId']) && count($_POST['ranchoId']) > 0) {
          foreach ($_POST['ranchoId'] as $keyr => $id_rancho) {
            $this->db->insert('compras_ordenes_rancho', [
              'id_rancho' => $id_rancho,
              'id_orden'  => $idOrden,
              'num'       => count($_POST['ranchoId'])
            ]);
          }
        }

        // Inserta los centros de costo
        $this->db->delete('compras_ordenes_centro_costo', ['id_orden' => $idOrden]);
        if (isset($_POST['centroCostoId']) && count($_POST['centroCostoId']) > 0) {
          foreach ($_POST['centroCostoId'] as $keyr => $id_centro_costo) {
            $this->db->insert('compras_ordenes_centro_costo', [
              'id_centro_costo' => $id_centro_costo,
              'id_orden'        => $idOrden,
              'num'             => count($_POST['centroCostoId'])
            ]);
          }
        }

        if ($_POST['tipoOrden'] !== 'a') {
          // Inserta los activos
          $this->db->delete('compras_ordenes_activos', ['id_orden' => $idOrden]);
          if (isset($_POST['activoId']) && count($_POST['activoId']) > 0) {
            foreach ($_POST['activoId'] as $keyr => $id_activo) {
              $this->db->insert('compras_ordenes_activos', [
                'id_activo' => $id_activo,
                'id_orden'  => $idOrden,
                'num'       => count($_POST['activoId'])
              ]);
            }
          }
        }
      }

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

      // Si trae datos extras
      $data['otros_datos'] = isset($ordennn->otros_datos)? (array)json_decode($ordennn->otros_datos): [];
      if ($this->input->post('infRecogerProv') != false) {
        $data['otros_datos']['infRecogerProv'] = $_POST['infRecogerProv'];
        $data['otros_datos']['infRecogerProvNom'] = $_POST['infRecogerProvNom'];
      }
      if ($this->input->post('infPasarBascula') != false) {
        $data['otros_datos']['infPasarBascula'] = $_POST['infPasarBascula'];
      }
      if ($this->input->post('infEntOrdenCom') != false) {
        $data['otros_datos']['infEntOrdenCom'] = $_POST['infEntOrdenCom'];
      }
      if ($this->input->post('infCotizacion') != false) {
        $data['otros_datos']['infCotizacion'] = $_POST['infCotizacion'];
      }
      $data['otros_datos'] = json_encode($data['otros_datos']);

      // Si agrega boletas
      if ($this->input->post('boletasEntradaId') != false) {
        $this->load->model('bascula_model');
        $boletasEntrada = explode('|', $this->input->post('boletasEntradaId'));

        foreach ($boletasEntrada as $key => $idBascula) {
          if ($idBascula != '') {
            $this->bascula_model->ligarOrdenes($idBascula, [
              'lig_ordenes' => [$idOrden],
              'lig_recibio' => $this->session->userdata('nombre'),
              'lig_entrego' => '',
            ]);
          }
        }
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
      $idsProductos = array();
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

        $statusp = ((isset($_POST['isProdOk'][$key]) && $_POST['isProdOk'][$key] === '1') || $status === 'a' ? 'a' : 'p');
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
          'observaciones'        => $_POST['observaciones'][$key],
          'status'               => $statusp,
          'ieps'                 => is_numeric($_POST['iepsTotal'][$key]) ? $_POST['iepsTotal'][$key] : 0,
          'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
          'tipo_cambio'          => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
          'id_compra'            => $prod_id_compra,
          // 'id_area'              => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
          $_POST['codigoCampo'][$key] => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
          'retencion_isr'        => $_POST['ret_isrTotal'][$key],
          'porcentaje_isr'       => $_POST['ret_isrPorcent'][$key],
        );

        if ($statusp == 'a' && $_POST['productoId'][$key] !== '') {
          if (!isset($idsProductos[$_POST['productoId'][$key]])) {
            $idsProductos[$_POST['productoId'][$key]] = [$_POST['productoId'][$key], $pu];
          }
        }
      }

      // Bitacora
      $this->bitacora_model->_updateExt($id_bitacora, 'compras_productos', $idOrden, $productos,
                                array(':id'             => 'id_orden',
                                      ':titulo'         => 'Productos',
                                      ':updates_fields' => 'compras_ordenes_productos'));

      $this->db->delete('compras_productos', array('id_orden' => $idOrden));
      $this->db->insert_batch('compras_productos', $productos);

      // Calcula costo promedio de los productos aceptados
      $this->calculaCostoPromedio($idsProductos);

      //envia el email al momento de autorizar la orden
      if(isset($data['autorizado']))
        if($data['autorizado'] == 't')
          $this->sendEmail($idOrden, $_POST['proveedorId']);
    }

    return array('passes' => true, 'msg' => 7);
  }

  public function actualizarExt($idOrden, $datos)
  {
    $ordennn = $this->db->select("status, otros_datos")
      ->from("compras_ordenes")
      ->where("id_orden", $idOrden)
      ->get()->row();
    $status = $ordennn->status;

    $data = array();

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

    if (count($data) > 0) {
      // Bitacora
      $id_bitacora = $this->bitacora_model->_update('compras_ordenes', $idOrden, $data,
                                array(':accion'       => 'modifico la orden de compra', ':seccion' => 'ordenes de compra',
                                      ':folio'        => $this->input->post('folio'),
                                      ':id_empresa'   => $this->input->post('empresaId'),
                                      ':empresa'      => 'en '.$this->input->post('empresa'),
                                      ':id'           => 'id_orden',
                                      ':titulo'       => 'Orden de compra'));

      $this->db->update('compras_ordenes', $data, array('id_orden' => $idOrden));

      // Actualiza los datos del vehiculo
      $this->actualizaVehiculo($idOrden);
    }

    return array('passes' => true, 'msg' => 7);
  }

  public function calculaCostoPromedio($idsProductos)
  {
    $ids = array_keys($idsProductos);
    if (count($ids) > 0) {
      $query = $this->db->query("UPDATE productos SET precio_promedio = t.costo
        FROM (
          SELECT p.id_producto, p.nombre, Round(pc.costo::decimal, 2) AS costo
          FROM productos p
            INNER JOIN productos_familias pf ON pf.id_familia = p.id_familia
            INNER JOIN (
              SELECT id_producto, (Sum(importe) / (CASE Sum(cantidad) WHEN 0 THEN 1 ELSE Sum(cantidad) END)) AS costo
              FROM compras_productos
              WHERE id_producto > 0 AND status = 'a' AND id_producto IN(".implode(',', $ids).")
              GROUP BY id_producto
            ) pc ON p.id_producto = pc.id_producto
          WHERE pf.tipo = 'p' AND p.status = 'ac'
        ) t
        WHERE productos.id_producto = t.id_producto");
    }

    foreach ($idsProductos as $key => $value) {
      $this->db->update('productos', ['last_precio' => $value[1]], "id_producto = {$key}");
    }
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
    $ordenesIdssss = $ordenesIds;
    $ordenesIds = explode(',', $ordenesIds);

    $ids_comprass = null;
    if ($ordenesIdssss != '') {
      $ids_comprass = $this->db->query("SELECT String_agg(ids_compras, '') AS ids_compras,
          String_agg(ids_salidas_almacen, '') AS ids_salidas_almacen,
          String_agg(ids_gastos_caja, '') AS ids_gastos_caja,
          String_agg(descripcion, ', ') AS descripcion
        FROM compras_ordenes WHERE id_orden in({$ordenesIdssss})")->row();
    }

    // datos de la compra.
    $data = array(
      'id_proveedor'        => $proveedorId,
      'id_empresa'          => $empresaId,
      'id_empleado'         => $this->session->userdata('id_usuario'),
      'serie'               => $_POST['serie'],
      'folio'               => $_POST['folio'],
      'condicion_pago'      => $_POST['condicionPago'],
      'plazo_credito'       => $_POST['plazoCredito'] !== '' ? $_POST['plazoCredito'] : 0,
      'tipo_documento'      => $_POST['tipo_documento'],
      'fecha'               => str_replace('T', ' ', $_POST['fecha']),
      'fecha_factura'       => str_replace('T', ' ', $_POST['fecha_factura']),
      'subtotal'            => $_POST['totalImporte'],
      'importe_iva'         => $_POST['totalImpuestosTrasladados'],
      'importe_ieps'        => $_POST['totalIeps'],
      'retencion_iva'       => $_POST['totalRetencion'],
      'retencion_isr'       => $_POST['totalRetencionIsr'],
      'total'               => $_POST['totalOrden'],
      'concepto'            => (isset($ids_comprass->descripcion)? mb_substr($ids_comprass->descripcion, 0, 190, 'UTF-8'): 'Concepto'),
      'observaciones'       => (isset($ids_comprass->descripcion)? $ids_comprass->descripcion: ''),
      'isgasto'             => 'f',
      'status'              => $_POST['condicionPago'] ===  'co' ? 'pa' : 'p',
      'uuid'                => $this->input->post('uuid'),
      'no_certificado'      => $this->input->post('noCertificado'),
      'ids_compras'         => (isset($ids_comprass->ids_compras{0})? $ids_comprass->ids_compras: NULL),
      'ids_salidas_almacen' => (isset($ids_comprass->ids_salidas_almacen{0})? $ids_comprass->ids_salidas_almacen: NULL),
      'ids_gastos_caja'     => (isset($ids_comprass->ids_gastos_caja{0})? $ids_comprass->ids_gastos_caja: NULL)
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
    $compraId = $this->db->insert_id('compras_id_compra_seq');

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
              co.ids_facrem, co.ids_compras, co.ids_salidas_almacen, co.ids_gastos_caja,
              co.no_impresiones, co.no_impresiones_tk,
              co.regresa_product, co.flete_de,
              co.id_almacen, ca.nombre AS almacen,
              co.cont_x_dia,
              co.id_registra, (use.nombre || ' ' || use.apellido_paterno || ' ' || use.apellido_materno) AS dio_entrada,
              -- co.id_area, co.id_activo,
              co.id_empresa_ap, co.id_orden_aplico,
              co.otros_datos, co.es_receta, co.id_proyecto, co.folio_hoja, co.uso_cfdi, co.forma_pago
       FROM compras_ordenes AS co
         INNER JOIN empresas AS e ON e.id_empresa = co.id_empresa
         INNER JOIN proveedores AS p ON p.id_proveedor = co.id_proveedor
         INNER JOIN compras_departamentos AS cd ON cd.id_departamento = co.id_departamento
         INNER JOIN usuarios AS u ON u.id = co.id_empleado
         LEFT JOIN usuarios AS us ON us.id = co.id_autorizo
         LEFT JOIN usuarios AS use ON use.id = co.id_registra
         LEFT JOIN clientes AS cl ON cl.id_cliente = co.id_cliente
         LEFT JOIN compras_vehiculos cv ON cv.id_vehiculo = co.id_vehiculo
         LEFT JOIN compras_almacenes ca ON ca.id_almacen = co.id_almacen
       WHERE co.id_orden = {$idOrden}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();

      $data['info'][0]->otros_datos = json_decode($data['info'][0]->otros_datos);

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
                  (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_area' ELSE 'id_cat_codigos' END) AS campo,
                  Date(cp.fecha_aceptacion) AS fecha_aceptacion, cp.folio_aceptacion, cp.observaciones
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
        if ($data['info'][0]->id_vehiculo > 0)
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

          if ($data['info'][0]->tipo_vehiculo != 'ot') {
            // Vehiculo anterior
            $query = $this->db->query(
              "SELECT co.id_orden, co.folio, co.fecha_creacion AS fecha, co.id_vehiculo, co.tipo_vehiculo,
                cvg.kilometros, cvg.litros, cvg.precio, (cv.placa || ' ' || cv.modelo || ' ' || cv.marca) AS vehiculo
              FROM compras_ordenes co
                INNER JOIN compras_vehiculos_gasolina cvg ON co.id_orden = cvg.id_orden
                INNER JOIN compras_vehiculos cv ON cv.id_vehiculo = co.id_vehiculo
              WHERE co.id_orden <> {$data['info'][0]->id_orden}
                AND co.id_vehiculo = {$data['info'][0]->id_vehiculo}
                AND co.tipo_vehiculo <> 'ot'
              ORDER BY co.id_orden DESC LIMIT 100");

            if ($query->num_rows() > 0)
            {
              $data['info'][0]->gasolina_ant = $query->row();
            }
          }
        }

        if ($data['info'][0]->id_proyecto > 0) {
          $this->load->model('proyectos_model');
          $data['info'][0]->proyecto = $this->proyectos_model->getProyectoInfo($data['info'][0]->id_proyecto, true, true);
        }

        $data['info'][0]->uso_cfdi_all = null;
        if ($data['info'][0]->uso_cfdi != '') {
          $usoCFDI = new UsoCfdi;
          $data['info'][0]->uso_cfdi_all = $usoCFDI->search($data['info'][0]->uso_cfdi);
        }

        $data['info'][0]->forma_pago = empty($data['info'][0]->forma_pago)? '99': $data['info'][0]->forma_pago;
        $formPago = new FormaPago;
        $data['info'][0]->forma_pago_all = $formPago->search($data['info'][0]->forma_pago);

        // facturas ligadas
        $data['info'][0]->facturasligadas = array();
        $data['info'][0]->boletasligadas = array();
        if ($data['info'][0]->flete_de === 'v') { // facturas y remisiones
          $this->load->model('facturacion_model');
          $facturasss = explode('|', $data['info'][0]->ids_facrem);
          if (count($facturasss) > 0)
          {
            array_pop($facturasss);
            foreach ($facturasss as $key => $value)
            {
              $facturaa = explode(':', $value);
              $data['info'][0]->facturasligadas[] = $this->facturacion_model->getInfoFactura($facturaa[1])['info'];
            }
          }
        } else { // boletas
          $this->load->model('bascula_model');
          $boletasss = explode('|', $data['info'][0]->ids_facrem);
          if (count($boletasss) > 0)
          {
            array_pop($boletasss);
            foreach ($boletasss as $key => $value)
            {
              $data['info'][0]->boletasligadas[] = $this->bascula_model->getBasculaInfo($value, 0, false, [], $value)['info'][0];
            }
          }
        }

        $data['info'][0]->comprasligadas = array();
        if ($data['info'][0]->tipo_orden === 'd' && $data['info'][0]->ids_compras != '') { // compras
          $this->load->model('compras_model');
          $comprasss = explode('|', $data['info'][0]->ids_compras);
          if (count($comprasss) > 0)
          {
            array_pop($comprasss);
            foreach ($comprasss as $key => $value)
            {
              $data['info'][0]->comprasligadas[] = $this->compras_model->getInfoCompra($value)['info'];
            }
          }
        }

        $data['info'][0]->salidasalmacenligadas = array();
        if ($data['info'][0]->tipo_orden === 'd' && $data['info'][0]->ids_salidas_almacen != '') { // salidas almacen
          $this->load->model('productos_salidas_model');
          $comprasss = explode('|', $data['info'][0]->ids_salidas_almacen);
          if (count($comprasss) > 0)
          {
            array_pop($comprasss);
            foreach ($comprasss as $key => $value)
            {
              $data['info'][0]->salidasalmacenligadas[] = $this->productos_salidas_model->info($value, false, true)['info'][0];
            }
          }
        }

        $data['info'][0]->gastoscajaligadas = array();
        if ($data['info'][0]->tipo_orden === 'd' && $data['info'][0]->ids_gastos_caja != '') { // gastos caja
          $this->load->model('caja_chica_model');
          $comprasss = explode('|', $data['info'][0]->ids_gastos_caja);
          if (count($comprasss) > 0)
          {
            array_pop($comprasss);
            foreach ($comprasss as $key => $value)
            {
              $data['info'][0]->gastoscajaligadas[] = $this->caja_chica_model->getDataGasto($value);
            }
          }
        }

        // Boletas ligadas
        $data['info'][0]->boletas_lig = $this->db->query(
              "SELECT b.id_bascula, b.folio, bl.recicio, bl.entrego, a.nombre AS area, b.fecha_bruto
               FROM bascula_lig_orden bl
                LEFT JOIN bascula b ON b.id_bascula = bl.id_bascula
                LEFT JOIN areas a ON a.id_area = b.id_area
               WHERE bl.id_orden = {$data['info'][0]->id_orden}")->result();

        // Compras de la orden
        $this->load->model('compras_model');
        $compras_data = $this->db->query("SELECT id_compra
                                   FROM compras_facturas
                                   WHERE id_orden = {$data['info'][0]->id_orden}");
        $data['info'][0]->compras = $compras_data->result();

        //eNTRADA ALMACEN
        $data['info'][0]->entrada_almacen = array();
        $data['info'][0]->entrada_almacen = $this->getInfoEntrada(0,0, $data['info'][0]->id_orden);

        // Requisiciones ligadas
        $data['info'][0]->requisiciones = $this->db->query("SELECT cr.id_requisicion, cr.folio, cor.num_row
                                           FROM compras_ordenes_requisiciones cor
                                            INNER JOIN compras_requisicion cr ON cr.id_requisicion = cor.id_requisicion
                                           WHERE cor.id_orden = {$data['info'][0]->id_orden}")->result();

        // Orden ligada de cuando se registra de agro insumos
        $data['info'][0]->ordenAplico = null;
        if ($data['info'][0]->id_orden_aplico) {
          $data['info'][0]->ordenAplico = $this->info($data['info'][0]->id_orden_aplico)['info'][0];
        }

        $data['info'][0]->empresaAp = null;
        if ($data['info'][0]->id_empresa_ap)
        {
          $this->load->model('empresas_model');
          $data['info'][0]->empresaAp = $this->empresas_model->getInfoEmpresa($data['info'][0]->id_empresa_ap, true)['info'];
        }

        // Codigos nuevos
        $data['info'][0]->area = $this->db->query("SELECT a.id_area, a.nombre, csa.num
                                   FROM compras_ordenes_areas csa
                                    INNER JOIN areas a ON a.id_area = csa.id_area
                                   WHERE csa.id_orden = {$data['info'][0]->id_orden}")->result();

        $data['info'][0]->rancho = $this->db->query("SELECT r.id_rancho, r.nombre, csr.num
                                   FROM compras_ordenes_rancho csr
                                    INNER JOIN otros.ranchos r ON r.id_rancho = csr.id_rancho
                                   WHERE csr.id_orden = {$data['info'][0]->id_orden}")->result();

        $data['info'][0]->centroCosto = $this->db->query("SELECT cc.id_centro_costo, cc.nombre, cscc.num
                                   FROM compras_ordenes_centro_costo cscc
                                    INNER JOIN otros.centro_costo cc ON cc.id_centro_costo = cscc.id_centro_costo
                                   WHERE cscc.id_orden = {$data['info'][0]->id_orden}")->result();

        $data['info'][0]->activo = $this->db->query("SELECT p.id_producto, p.nombre, csa.num
                                   FROM compras_ordenes_activos csa
                                    INNER JOIN productos p ON p.id_producto = csa.id_activo
                                   WHERE csa.id_orden = {$data['info'][0]->id_orden}")->result();
      }
    }
    return $data;
  }

  public function infoPago($idOrden)
  {
    $query = $this->db->query(
      "SELECT c.serie, c.folio, Date(ca.fecha) AS fecha, ca.concepto, ca.total, bc.alias, c.status
      FROM compras_ordenes co
        INNER JOIN compras_facturas cf ON co.id_orden = cf.id_orden
        INNER JOIN compras c ON c.id_compra = cf.id_compra
        INNER JOIN compras_abonos ca ON c.id_compra = ca.id_compra
        INNER JOIN banco_cuentas bc ON bc.id_cuenta = ca.id_cuenta
      WHERE co.id_orden = {$idOrden}");
    $data = $query->result();

    return $data;
  }

  public function infoHistNoImpreciones($ordenId)
  {
    $query = $this->db->query("SELECT *
                               FROM compras_ordenes_hist_imp
                               WHERE id_orden = {$ordenId}");
    $data = $query->result();

    return $data;
  }

  public function folioDia($fecha)
  {
    $res = $this->db->select('cont_x_dia')
      ->from('compras_ordenes')
      ->where('Date(fecha_creacion)', $fecha)
      ->order_by('cont_x_dia', 'DESC')
      ->limit(1)->get()->row();

    $cont_x_dia = (isset($res->cont_x_dia) ? $res->cont_x_dia : 0) + 1;

    return $cont_x_dia;
  }

  public function folio($tipo = 'p', $regresa_product=false)
  {
    $res = $this->db->select('folio')
      ->from('compras_ordenes')
      ->where('tipo_orden', $tipo)
      ->where('regresa_product', ($regresa_product?'t':'f'))
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

  // Obtiene el folio de la entrada de almacén
  public function getFolioEntradaAlmacen($idOrden)
  {
    $orden = $this->db->query("SELECT id_empresa
      FROM compras_ordenes
      WHERE id_orden = {$idOrden}")->row();

    $anio = Date('Y');
    $orden = $this->db->query("SELECT Coalesce(Max(cp.folio_aceptacion), 1) AS folio_aceptacion
      FROM compras_ordenes co
        INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
      WHERE co.id_empresa = {$orden->id_empresa} AND Date(cp.fecha_aceptacion) BETWEEN '{$anio}-01-01' AND '{$anio}-12-31'
      GROUP BY cp.folio_aceptacion
      LIMIT 1")->row();

    return isset($orden->folio_aceptacion)? $orden->folio_aceptacion: 1;
  }

  public function entrada($idOrden)
  {
    $ordennn = $this->db->select("status, otros_datos")
        ->from("compras_ordenes")
        ->where("id_orden", $idOrden)
        ->get()->row();

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

    $folioEntrada = $this->getFolioEntradaAlmacen($idOrden);

    $almacen = array();
    $res_prodc_orden = $this->db->query("SELECT id_orden, num_row, id_compra FROM compras_productos
              WHERE id_orden = {$idOrden}")->result();
    $idsProductos = $productos = $productosFaltantes = array();
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

      $statusp = ($_POST['isProdOk'][$key] === '1' ? 'a' : 'r');
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
        'status'               => $statusp,
        // 'fecha_aceptacion'     => date('Y-m-d H:i:s'),
        'faltantes'            => $faltantesProd,
        'observacion'          => $_POST['observacion'][$key],
        'observaciones'        => $_POST['observaciones'][$key],
        'ieps'                 => is_numeric($_POST['iepsTotal'][$key]) ? $_POST['iepsTotal'][$key] : 0,
        'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
        'tipo_cambio'          => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
        'id_compra'            => $prod_id_compra,
        // 'id_area'              => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
        $_POST['codigoCampo'][$key] => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
        'retencion_isr'        => $_POST['ret_isrTotal'][$key],
        'porcentaje_isr'       => $_POST['ret_isrPorcent'][$key],
      );

      if ($statusp === 'a') {
        $productos[count($productos)-1]['folio_aceptacion'] = $folioEntrada;
        $productos[count($productos)-1]['fecha_aceptacion'] = date('Y-m-d H:i:s');
      }

      if ($statusp == 'a' && $_POST['productoId'][$key] !== '') {
        if (!isset($idsProductos[$_POST['productoId'][$key]])) {
          $idsProductos[$_POST['productoId'][$key]] = [$_POST['productoId'][$key], $pu];
        }
      }

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
          'observaciones'        => $_POST['observaciones'][$key],
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
        'id_registra'      => $this->session->userdata('id_usuario'),
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

    // Si trae datos extras
    $data['otros_datos'] = isset($ordennn->otros_datos)? (array)json_decode($ordennn->otros_datos): [];
    if ($this->input->post('infRecogerProv') != false) {
      $data['otros_datos']['infRecogerProv'] = $_POST['infRecogerProv'];
      $data['otros_datos']['infRecogerProvNom'] = $_POST['infRecogerProvNom'];
    }
    if ($this->input->post('infPasarBascula') != false) {
      $data['otros_datos']['infPasarBascula'] = $_POST['infPasarBascula'];
    }
    if ($this->input->post('infEntOrdenCom') != false) {
      $data['otros_datos']['infEntOrdenCom'] = $_POST['infEntOrdenCom'];
    }
    if ($this->input->post('infCotizacion') != false) {
      $data['otros_datos']['infCotizacion'] = $_POST['infCotizacion'];
    }
    $data['otros_datos'] = json_encode($data['otros_datos']);

    // Si agrega boletas
    if ($this->input->post('boletasEntradaId') != false) {
      $this->load->model('bascula_model');
      $boletasEntrada = explode('|', $this->input->post('boletasEntradaId'));

      foreach ($boletasEntrada as $key => $idBascula) {
        if ($idBascula != '') {
          $this->bascula_model->ligarOrdenes($idBascula, [
            'lig_ordenes' => [$idOrden],
            'lig_recibio' => $this->session->userdata('nombre'),
            'lig_entrego' => '',
          ]);
        }
      }
    }


    $this->db->delete('compras_productos', array('id_orden' => $idOrden));

    $this->actualizar($idOrden, $data, $productos);

    // Calcula costo promedio de los productos aceptados
    $this->calculaCostoPromedio($idsProductos);

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
    $data = $this->info($idOrden, true);
    $data['info'] = $data['info'][0];

    if (is_object($data['info']->otros_datos)) {
      $data['info']->otros_datos->prev_orden_faltantes = [
        'id_orden' => $idOrden,
        'folio'    => $data['info']->folio,
        'fecha'    => substr($data['info']->fecha_aceptacion, 0, 10)
      ];
    } else {
      $data['info']->otros_datos['prev_orden_faltantes'] = [
        'id_orden' => $idOrden,
        'folio'    => $data['info']->folio,
        'fecha'    => substr($data['info']->fecha_aceptacion, 0, 10)
      ];
    }

    $dataOrden = array(
      'id_empresa'          => $data['info']->id_empresa,
      'id_proveedor'        => $data['info']->id_proveedor,
      'id_departamento'     => $data['info']->id_departamento,
      'id_empleado'         => $data['info']->id_empleado,
      'id_autorizo'         => $data['info']->id_autorizo,
      'folio'               => $this->folio($data['info']->tipo_orden),
      'fecha_autorizacion'  => $data['info']->fecha_autorizacion,
      'fecha_aceptacion'    => $data['info']->fecha_aceptacion,
      'fecha_creacion'      => $data['info']->fecha,
      'tipo_pago'           => $data['info']->tipo_pago,
      'tipo_orden'          => $data['info']->tipo_orden,
      'status'              => 'p',
      'autorizado'          => 't',
      'id_cliente'          => (is_numeric($data['info']->id_cliente)? $data['info']->id_cliente: NULL),
      'solicito'            => $data['info']->empleado_solicito,
      'descripcion'         => $data['info']->descripcion,

      'id_vehiculo'         => $data['info']->id_vehiculo,
      'tipo_vehiculo'       => $data['info']->tipo_vehiculo,
      'ids_facrem'          => $data['info']->ids_facrem,
      'id_almacen'          => $data['info']->id_almacen,
      'regresa_product'     => $data['info']->regresa_product,
      'flete_de'            => $data['info']->flete_de,
      'cont_x_dia'          => $data['info']->cont_x_dia,
      'id_registra'         => $data['info']->id_registra,
      'otros_datos'         => json_encode($data['info']->otros_datos),
      'es_receta'           => $data['info']->es_receta,
      // 'ids_compras'         => $data['info']->ids_compras,
      'id_empresa_ap'       => $data['info']->id_empresa_ap,
      // 'id_orden_aplico'     => $data['info']->id_orden_aplico,
      // 'ids_salidas_almacen' => $data['info']->ids_salidas_almacen,
      // 'ids_gastos_caja'     => $data['info']->ids_gastos_caja,
      'id_proyecto'         => $data['info']->id_proyecto,
      'folio_hoja'          => $data['info']->folio_hoja,
      'uso_cfdi'            => $data['info']->uso_cfdi,
      'forma_pago'          => $data['info']->forma_pago,
    );

    //si es flete
    if ($data['info']->tipo_orden == 'f')
    {
      $dataOrden['ids_facrem'] = $data['info']->ids_facrem;
    }


    $dataOrdenCats = [];
    if (isset($data['info']->requisiciones)) {
      $dataOrdenCats['requisiciones'][] = [
        'id_requisicion' => $data['info']->requisiciones[0]->id_requisicion,
        'id_orden'       => '',
        'num_row'        => 0
      ];
    }

    // Si es un gasto son requeridos los campos de catálogos
    if ($data['info']->tipo_orden == 'd' || $data['info']->tipo_orden == 'oc' || $data['info']->tipo_orden == 'f' ||
        $data['info']->tipo_orden == 'a' || $data['info']->tipo_orden == 'p') {


      // Inserta las areas
      if (isset($data['info']->area) && count($data['info']->area) > 0) {
        foreach ($data['info']->area as $key => $area) {
          $dataOrdenCats['area'][] = [
            'id_area' => $area->id_area,
            'id_orden'  => '',
            'num'       => count($data['info']->area)
          ];
        }
      }

      // Inserta los ranchos
      if (isset($data['info']->rancho) && count($data['info']->rancho) > 0) {
        foreach ($data['info']->rancho as $keyr => $drancho) {
          $dataOrdenCats['rancho'][] = [
            'id_rancho' => $drancho->id_rancho,
            'id_orden'  => '',
            'num'       => count($data['info']->rancho)
          ];
        }
      }

      // Inserta los centros de costo
      if (isset($data['info']->centroCosto) && count($data['info']->centroCosto) > 0) {
        foreach ($data['info']->centroCosto as $keyr => $dcentro_costo) {
          $dataOrdenCats['centroCosto'][] = [
            'id_centro_costo' => $dcentro_costo->id_centro_costo,
            'id_orden'        => '',
            'num'             => count($data['info']->centroCosto)
          ];
        }
      }

      // Inserta los activos
      if (isset($data['info']->activo) && count($data['info']->activo) > 0) {
        foreach ($data['info']->activo as $orkey => $activ) {
          $dataOrdenCats['activo'][] = [
            'id_activo' => $activ->id_activo,
            'id_orden'  => '',
            'num'       => count($data['info']->activo)
          ];
        }
      }
    }

    $res = $this->agregarData($dataOrden, [], $dataOrdenCats);
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

  public function getProductoAjax($idEmpresa = null, $tipo, $term, $def = 'codigo', $id_almacen = 1){
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
              p.last_precio AS precio_unitario, ep.existencia AS inventario
        FROM productos AS p
        INNER JOIN productos_familias pf ON pf.id_familia = p.id_familia
        INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        LEFT JOIN existencia_productos ep ON ep.id_producto = p.id_producto
        WHERE p.status = 'ac' AND
              {$term}
              {$sqlEmpresa}
              {$tipo_prod}
              pf.status = 'ac'
        ORDER BY p.nombre ASC
        -- LIMIT 20");

    $response = array();
    if($res->num_rows() > 0)
    {
      foreach($res->result() as $itm)
      {
        // if(isset($_GET['did_empresa']{0}))
        // {
        //   // $_GET['fid_producto'] = $itm->id_producto;
        //   $itm->inventario = $this->inventario_model->getEPUData($itm->id_producto, $id_almacen);
        //   $itm->inventario = isset($itm->inventario[0])? $itm->inventario[0]: false;
        // }

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
              pf.nombre as familia, pf.codigo as codigo_familia, pf.tipo AS tipo_familia,
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
    if(!empty($res) && $res->num_rows() > 0)
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

  public function getCompras($datos)
  {
    $filtro = isset($datos['filtro']{0})? " AND f.folio = '{$datos['filtro']}'": '';
    $filtro .= isset($datos['proveedorId']{0})? " AND p.id_proveedor = {$datos['proveedorId']} ": '';
    $query = $this->db->query("SELECT f.id_compra, Date(f.fecha) AS fecha, f.serie, f.folio, p.nombre_fiscal AS proveedor
                               FROM compras AS f
                                  INNER JOIN proveedores AS p ON p.id_proveedor = f.id_proveedor
                               WHERE f.id_empresa = {$datos['empresaId']} AND f.status <> 'ca' AND f.id_nc IS NULL
                                {$filtro} AND f.fecha >= (now() - interval '15 months')
                               ORDER BY f.fecha DESC, f.folio DESC");
    $response = array();
    if($query->num_rows() > 0)
      $response = $query->result();
    $query->free_result();
    return $response;
  }

  public function getBoletas($datos)
  {
    $tipo = isset($datos['tipoo']{0})? $datos['tipoo']: 'en';
    $filtro = isset($datos['filtro']{0})? " AND b.folio = '{$datos['filtro']}'": '';
    $accion = isset($datos['accion'][0])? "'".implode("','", $datos['accion'])."'": "'en', 'p', 'b'";
    $area = isset($datos['area']{0})? " AND a.id_area = '{$datos['area']}'": '';

    $campos = "p.nombre_fiscal AS proveedor,";
    $tablas = "INNER JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor";
    if ($tipo === 'sa') {
      $campos = "c.nombre_fiscal AS cliente, e.id_empresa, ";
      $tablas = "INNER JOIN clientes AS c ON c.id_cliente = b.id_cliente";
    }

    $query = $this->db->query("SELECT b.id_bascula,
                b.folio,
                b.tipo,
                b.status,
                e.nombre_fiscal AS empresa,
                a.nombre AS area,
                {$campos}
                ch.nombre AS chofer,
                (ca.marca || ' ' || ca.modelo) AS camion,
                ca.placa AS placas,
                Date(b.fecha_bruto) AS fecha,
                b.id_bonificacion
        FROM bascula AS b
          INNER JOIN empresas AS e ON e.id_empresa = b.id_empresa
          INNER JOIN areas AS a ON a.id_area = b.id_area
          {$tablas}
          LEFT JOIN choferes AS ch ON ch.id_chofer = b.id_chofer
          LEFT JOIN camiones AS ca ON ca.id_camion = b.id_camion
        WHERE b.tipo = '{$tipo}' AND b.accion in({$accion}) {$filtro}
          {$area}
        ORDER BY b.folio DESC
        LIMIT 100");
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
      $this->load->model('proveedores_model');
      $this->load->model('almacenes_model');
      $this->load->model('banco_cuentas_model');

      $orden = $this->info($ordenId, true);
      $ordenPago = $this->infoPago($ordenId);
      $ordenHistImp = $this->infoHistNoImpreciones($ordenId);
      $emp_cuenta = $this->banco_cuentas_model->getCuentaConcentradora($orden['info'][0]->id_empresa);
      $almacen = $this->almacenes_model->getAlmacenInfo($orden['info'][0]->id_almacen);
      $proveedor = $this->proveedores_model->getProveedorInfo($orden['info'][0]->id_proveedor);
      $proveedor_cuentas = $this->proveedores_model->getCuentas($orden['info'][0]->id_proveedor);
      // echo "<pre>";
      //   var_dump($orden);
      // echo "</pre>";exit;

      $orientacion = ($orden['info'][0]->status == 'f')? 'L': 'P'; // $orden['info'][0]->status == 'a' ||
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf($orientacion, 'mm', 'Letter');
      $pdf->show_head = false;
      $pdf->noShowPagesPos = ($orientacion==='L'? 265: 205);
      $pdf->titulo1 = "{$orden['info'][0]->id_empresa} - {$orden['info'][0]->empresa}";

      $pdf->titulo3 = 'Almacen: '.$orden['info'][0]->almacen;
      $tipo_orden = 'ORDEN DE COMPRA';
      if($orden['info'][0]->tipo_orden == 'd') {
        $tipo_orden = 'ORDEN DE SERVICIO';
        if (count($orden['info'][0]->comprasligadas) > 0) {
          $tipo_orden = 'SERVICIO INTERNO';
        }
      }
      elseif($orden['info'][0]->tipo_orden == 'f')
        $tipo_orden = 'ORDEN DE FLETE';
      // $pdf->titulo2 = $tipo_orden;
      // $pdf->titulo2 = 'Proveedor: ' . $orden['info'][0]->proveedor;
      // $pdf->titulo3 = " Fecha: ". date('Y-m-d') . ' Orden: ' . $orden['info'][0]->folio;

      $pdf->SetLeftMargin(5);
      $pdf->AliasNbPages();
      $pdf->limiteY = 235;
      $pdf->AddPage();

      $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';
      if($pdf->logo != '')
        $pdf->Image(APPPATH.(str_replace(APPPATH, '', $pdf->logo)), 6, 5, 50);

      $pdf->SetXY(150, $pdf->GetY());
      $pdf->SetFillColor(200,200,200);
      $pdf->SetFont('helvetica','B', 10);
      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(60));
      $pdf->Row(array($tipo_orden), true, true);
      $pdf->SetXY(150, $pdf->GetY());
      $pdf->Row(array('No '.MyString::formatoNumero($orden['info'][0]->folio, 2, '')."\n \n "), false, true);
      $pdf->SetFont('helvetica','B', 8.5);
      $pdf->SetXY(150, $pdf->GetY()-8);
      $pdf->Row(array(MyString::fechaATexto($orden['info'][0]->fecha, '/c', true)), false, false);
      $pdf->SetFont('helvetica','B', 10);
      $pdf->SetXY(150, $pdf->GetY());

      $pdf->SetFont('helvetica','', 9);
      $pdf->SetXY(80, $pdf->GetY()-20);
      $pdf->Row(array('Impresión '.($orden['info'][0]->no_impresiones==0? 'ORIGINAL': ($orden['info'][0]->no_impresiones==1? 'COPIA ARCHIVO': 'COPIA '.$orden['info'][0]->no_impresiones)).
        "\n".MyString::fechaATexto(date("Y-m-d H:i:s"), '/c', true)), false, false);

      $pdf->SetXY(95, $pdf->GetY()+4);
      $aux_y1 = $pdf->getY();

      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(100));
      $pdf->Row(array('Modo de Facturación'), false, false);
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetWidths(array(30, 50));
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('Condiciones:', ($proveedor['info']->condicion_pago=='co'? 'Contado': "Crédito {$proveedor['info']->dias_credito} DIAS")), false, false);
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $formaPago = "99 (Por Definir)";
      if ($orden['info'][0]->forma_pago_all) { // agroinsumos
        $formaPago = "{$orden['info'][0]->forma_pago_all['key']} ({$orden['info'][0]->forma_pago_all['value']})";
      }
      $pdf->Row(array('Forma de Pago:', $formaPago), false, false);
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('Método de Pago:', "PPD (Pago Parcialidades/Diferido)"), false, false);
      $usoCFDI = 'G03 (Gastos en General)';
      if ($orden['info'][0]->uso_cfdi_all) { // agroinsumos
        $usoCFDI = "{$orden['info'][0]->uso_cfdi_all['key']} ({$orden['info'][0]->uso_cfdi_all['value']})";
      }

      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('Uso del CFDI:', $usoCFDI), false, false);
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('Almacén:', $orden['info'][0]->almacen), false, false);

      $pdf->SetXY(95, $pdf->GetY()+3);
      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L', 'L', 'L'));
      $pdf->SetWidths(array(100));
      $pdf->Row(array('Complementos de Pago'), false, false);
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetWidths(array(30, 40));
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('Método de Pago:', 'Transferencia'), false, false);
      $pdf->SetWidths(array(30, 40, 40));
      if (isset($emp_cuenta['info']->id_cuenta)) {
        $pdf->SetXY(95, $pdf->GetY()-1.5);
        $pdf->Row(array('Cta. Ordenante:', $emp_cuenta['info']->banco, $emp_cuenta['info']->cuenta), false, false);
      }
      if (count($proveedor_cuentas) > 0) {
        $pdf->SetXY(95, $pdf->GetY()-1.5);
        $pdf->Row(array('Cta. Beneficiario:', $proveedor_cuentas[0]->banco, $proveedor_cuentas[0]->cuenta), false, false);
        $pdf->SetWidths(array(30, 40));
        $pdf->SetXY(95, $pdf->GetY()-1.5);
        $pdf->Row(array('Ref Bancaria:', $proveedor_cuentas[0]->referencia), false, false);
      }

      $pdf->SetXY(95, $pdf->GetY()+3);
      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L', 'L', 'L'));
      $pdf->SetWidths(array(100));
      $pdf->Row(array('Requisitos para la Entrega de Mercancía'), false, false);
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('( '.(isset($orden['info'][0]->otros_datos->infPasarBascula)? 'Si': 'No').' ) Pasar a Bascula a pesar la mercancía y entregar Boleta a almacén.'), false, false);
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('( '.(isset($orden['info'][0]->otros_datos->infEntOrdenCom)? 'Si': 'No').' ) Entregar la mercancía al almacenista, referenciando la presente Orden de Compra, así como anexarla a su Factura.'), false, false);

      $aux_y2 = $pdf->GetY();

      $pdf->SetXY(5, $aux_y1+15);

      $pdf->SetFont('helvetica','B', 10);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(150));
      $pdf->Row(array($orden['info'][0]->empresa), false, false);

      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L', 'L', 'L'));
      $pdf->SetWidths(array(90));
      $pdf->Row(array('Proveedor / Beneficiario'), false, false);
      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetXY(5, $pdf->GetY()-1.5);
      $pdf->Row(array($proveedor['info']->nombre_fiscal), false, false);
      $pdf->SetXY(5, $pdf->GetY()-1.5);
      $direccion = ($proveedor['info']->calle!=''? $proveedor['info']->calle: '').
        ($proveedor['info']->no_exterior!=''? " No {$proveedor['info']->no_exterior}": '').
        ($proveedor['info']->no_interior!=''? " {$proveedor['info']->no_interior}": '').
        ($proveedor['info']->cp!=''? ", CP {$proveedor['info']->cp}": '').
        ($proveedor['info']->colonia!=''? ", Col. {$proveedor['info']->colonia}": '').
        ($proveedor['info']->municipio!=''? " {$proveedor['info']->municipio}": '').
        ($proveedor['info']->estado!=''? " {$proveedor['info']->estado}": '');
      $pdf->Row(array($direccion), false, false);
      $pdf->SetXY(5, $pdf->GetY()-1.5);
      $pdf->Row(array("RFC {$proveedor['info']->rfc} / Tel. {$proveedor['info']->telefono}"), false, false);

      $pdf->SetXY(5, $pdf->GetY()+3);
      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L', 'L', 'L'));
      $pdf->SetWidths(array(90));
      $pdf->Row(array('Dirección de Entrega'), false, false);
      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetXY(5, $pdf->GetY()-1.5);
      $pdf->Row(array($proveedor['info']->nombre_fiscal), false, false);
      $pdf->SetXY(5, $pdf->GetY()-1.5);
      if (isset($orden['info'][0]->otros_datos->infRecogerProv)) {
        $pdf->Row(array("Entregar la mercancía a: \n{$orden['info'][0]->otros_datos->infRecogerProvNom}"), false, false);
      } else {
        $direccion = ($almacen['info']->calle!=''? $almacen['info']->calle: '').
          ($almacen['info']->no_exterior!=''? " No {$almacen['info']->no_exterior}": '').
          ($almacen['info']->no_interior!=''? " {$almacen['info']->no_interior}": '').
          ($almacen['info']->cp!=''? ", CP {$almacen['info']->cp}": '').
          ($almacen['info']->colonia!=''? ", Col. {$almacen['info']->colonia}": '').
          ($almacen['info']->municipio!=''? " {$almacen['info']->municipio}": '').
          ($almacen['info']->estado!=''? " {$almacen['info']->estado}": '');
        $pdf->Row(array($direccion), false, false);
        $pdf->SetFont('helvetica','B', 8);
        $pdf->SetXY(5, $pdf->GetY()-1.5);
        $pdf->Row(array("Horario de Entrega: {$almacen['info']->horario}"), false, false);
      }

      // Pagos de la orden
      if (count($ordenPago) > 0) {
        // $aux_y2 = $pdf->GetY();
        $pdf->SetXY(215, $aux_y1);
        $pdf->SetFont('helvetica','B', 8);
        $pdf->SetAligns(array('C', 'C', 'C'));
        $pdf->SetWidths(array(55));

        if ($ordenPago[0]->status == 'pa') {
          $pdf->Row(array('Orden Cerrada'), true, true);
          $pdf->SetXY(215, $pdf->GetY());
        }

        $pdf->Row(array('Datos del Pago'), false, false);
        $pdf->SetFont('helvetica', '', 8);
        // $pdf->SetWidths(array(20, 25));
        $pdf->SetAligns(array('L'));
        $pdf->SetXY(215, $pdf->GetY());
        foreach ($ordenPago as $key => $value) {
          $pdf->SetXY(215, $pdf->GetY());
          $pdf->Row(array(
            "Fecha: {$value->fecha}\nFactura: {$value->serie}{$value->folio}\nCuenta: {$value->alias}\nImporte: ".MyString::formatoNumero($value->total, 2, '$', false).""), false, true);
          $pdf->Line(215, $pdf->GetY(), 250, $pdf->GetY());
        }
      }

      // Boletas ligadas
      if (isset($orden['info'][0]->boletas_lig) && count($orden['info'][0]->boletas_lig) > 0 && $orientacion === 'L') {
        // $aux_y2 = $pdf->GetY();
        $pdf->SetXY(221, $pdf->GetY());
        if (count($ordenPago) === 0) {
          $pdf->SetXY(221, $aux_y1+10);
        }

        $pdf->SetFont('helvetica','B', 8);
        $pdf->SetAligns(array('C', 'C', 'C'));
        $pdf->SetWidths(array(45));
        $pdf->Row(array('Bascula'), false, false);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetWidths(array(20, 25));
        $pdf->SetXY(221, $pdf->GetY());
        $pdf->Row(array('Fecha', 'Boleta'), true, true);
        foreach ($orden['info'][0]->boletas_lig as $key => $value) {
          $pdf->SetXY(221, $pdf->GetY());
          $pdf->Row(array(substr($value->fecha_bruto, 0, 10), $value->folio), false, true);
        }
      }

      if ($aux_y2 > $pdf->getY()) {
        $pdf->SetY($aux_y2);
      }

      $pdf->SetY($pdf->getY()+5);

      $aligns = array('C', 'L', 'C', 'R', 'R', 'C', 'C');
      $ultima_compra = false;
      if ($orden['info'][0]->no_impresiones > -1) {
        $widths = array(93, 18, 25, 25, 25, 25, 25);
        $header = array('DESCRIPCION', 'CANT.', 'PRECIO', 'IMPORTE');

        $aligns2 = array('R', 'C');
        $widths2 = array(20, 22);
        $header2 = array('PRECIO', 'ULTIMA/COMP');
        $ultima_compra = true;
      } else {
        $widths = array(35, 101, 18, 25, 25, 25, 25, 25);
        $header = array('CODIGO', 'DESCRIPCION', 'CANT.', 'PRECIO', 'IMPORTE');
      }
      if ($orientacion === 'L') {
        $header[] = 'FECHA';
        $header[] = 'FOLIO';
      }

      $subtotal = $iva = $total = $retencion = $ieps = 0;

      $tipoCambio = 0;
      $codigoAreas = array();

      foreach ($orden['info'][0]->productos as $key => $prod)
      {
        $tipoCambio = 1;
        $decimales = 2;
        if ($prod->tipo_cambio != 0)
        {
          $tipoCambio = $prod->tipo_cambio;
          $decimales = 4;
        }

        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
          if($pdf->GetY()+5 >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          // $pdf->SetTextColor(255,255,255);
          $pdf->SetFillColor(200,200,200);

          $pdf->SetX(6);
          if ($ultima_compra) {
            $pdf->SetAligns($aligns2);
            $pdf->SetWidths($widths2);
            $pdf->Row($header2, true);
            $pdf->SetY($pdf->GetY()-5.8);
            $pdf->SetX(49);
          }

          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
          // $pdf->Output('dd.pdf', 'I');
        }

        $pdf->SetFont('Arial','',8);
        $pdf->SetTextColor(0,0,0);

        $ultcompra = null;
        $datos = array();

        $pdf->SetX(6);
        if ($ultima_compra) {
          $ultcompra = $this->getUltimaCompra($prod->id_producto, $orden['info'][0]->id_orden);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths2);
          $pdf->Row([
            MyString::formatoNumero((isset($ultcompra->precio_unitario)? $ultcompra->precio_unitario: ''), 2, '$', false),
            substr((isset($ultcompra->fecha_aceptacion)? $ultcompra->fecha_aceptacion: ''), 0, 10)
          ], false);

          $pdf->SetY($pdf->GetY()-5.8);
          $pdf->SetX(49);
        } else {
          $datos[] = $prod->codigo.'/'.$prod->codigo_fin;
        }
        $datos[] = "{$prod->id_producto} - ".$prod->descripcion.($prod->observacion!=''? " ({$prod->observacion})": '');
        // $this->getFechaUltimaCompra($prod->id_producto, $prod->id_area, $prod->campo),
        $datos[] = $prod->cantidad.' '.$prod->abreviatura;
        $datos[] = MyString::formatoNumero($prod->precio_unitario/$tipoCambio, $decimales, '$', false);
        $datos[] = MyString::formatoNumero($prod->importe/$tipoCambio, 2, '$', false);

        if ($orientacion === 'L') {
          $datos[] = $prod->fecha_aceptacion;
          $datos[] = $prod->folio_aceptacion; //cont_x_dia
        }

        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
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

      //Totales
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(160, $pdf->GetY());
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(25, 25));
      $pdf->Row(array('SUB-TOTAL', MyString::formatoNumero($subtotal, 2, '$', false)), false, true);
      $pdf->SetX(160);
      $pdf->Row(array('IVA', MyString::formatoNumero($iva, 2, '$', false)), false, true);
      if ($ieps > 0)
      {
        $pdf->SetX(160);
        $pdf->Row(array('IEPS', MyString::formatoNumero($ieps, 2, '$', false)), false, true);
      }
      if ($retencion > 0)
      {
        $pdf->SetX(160);
        $pdf->Row(array('Ret. IVA', MyString::formatoNumero($retencion, 2, '$', false)), false, true);
      }
      $pdf->SetX(160);
      $pdf->Row(array('TOTAL', MyString::formatoNumero($total, 2, '$', false)), false, true);

      // Historial de impreciones
      if (count($ordenHistImp) > 0 && $orientacion === 'L') {
        $pdf->SetFont('helvetica','B', 8);
        $pdf->SetAligns(array('C', 'C', 'C'));
        $pdf->SetWidths(array(45));
        $pdf->SetXY(221, $pdf->GetY()-10);
        $pdf->Row(array('Historial Re Impresiones'), false, false);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetWidths(array(50));
        foreach ($ordenHistImp as $key => $value) {
          $pdf->SetXY(221, $pdf->GetY());
          $noImpresion = ' / '.($value->no_impresiones==0? 'ORIGINAL': ($value->no_impresiones==1? 'COPIA ARCHIVO': 'COPIA '.$value->no_impresiones));
          $pdf->Row(array(str_replace(' ', ' / ', substr($value->fecha, 0, 19)).$noImpresion), false, true);
        }
      }

      //Otros datos
      $pdf->SetXY(6, $yy);
      $pdf->SetX(6);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(154));
      if($orden['info'][0]->tipo_orden == 'f'){
        // $this->load->model('facturacion_model');
        $this->load->model('documentos_model');
        // $facturasss = explode('|', $orden['info'][0]->ids_facrem);
        $info_bascula = false;
        if (count($orden['info'][0]->facturasligadas) > 0 || count($orden['info'][0]->boletasligadas) > 0 || count($orden['info'][0]->comprasligadas) > 0)
        {
          $tituloclientt = $clientessss = $facturassss = $tituloclient = '';
          if ($orden['info'][0]->flete_de == 'v') {
            foreach ($orden['info'][0]->facturasligadas as $key => $value)
            {
              $facturassss .= ' / '.$value->serie.$value->folio.' '.$value->fechaT;
              $clientessss .= ', '.$value->cliente->nombre_fiscal;

              if($info_bascula === false)
              {
                $info_bascula = $this->documentos_model->getClienteDocs($value->id_factura, 1);
                if(!isset($info_bascula[0]) || $info_bascula[0]->data == 'NULL' )
                  $info_bascula = false;
              }
            }
            $tituloclient = 'FOLIO: ';
            $tituloclientt = 'Clientes: ';
          } else {
            foreach ($orden['info'][0]->boletasligadas as $key => $value)
            {
              $facturassss .= ' / '.$value->folio.' '.substr($value->fecha_tara, 0, 10);
              $clientessss .= ', '.$value->proveedor;
            }
            $tituloclient = 'BOLETAS: ';
            $tituloclientt = 'Proveedores: ';
          }

          // array_pop($facturasss);
          // foreach ($facturasss as $key => $value)
          // {
          //   $facturaa = explode(':', $value);
          //   $facturaa = $this->facturacion_model->getInfoFactura($facturaa[1]);
          //   $facturassss .= '/'.$facturaa['info']->serie.$facturaa['info']->folio.' '.$facturaa['info']->fechaT;
          //   $clientessss .= ', '.$facturaa['info']->cliente->nombre_fiscal;

          //   if($info_bascula === false)
          //   {
          //     $info_bascula = $this->documentos_model->getClienteDocs($facturaa['info']->id_factura, 1);
          //     if(!isset($info_bascula[0]) || $info_bascula[0]->data == 'NULL' )
          //       $info_bascula = false;
          //   }
          // }
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->Row(array($tituloclient.substr($facturassss, 3) ), false, false);
        }
        $pdf->SetX(6);
        $pdf->Row(array('CLIENTE: '.$orden['info'][0]->cliente), false, false);
        $pdf->SetXY(6, $pdf->GetY()+6);
        $pdf->Row(array('________________________________________________________________________________________________'), false, false);
        $pdf->SetXY(6, $pdf->GetY()-2);
        $pdf->Row(array('CHOFER: '.strtoupper($orden['info'][0]->empleado_solicito)), false, false);
      } elseif ($orden['info'][0]->tipo_orden == 'd' && count($orden['info'][0]->comprasligadas) > 0) {
        $facturassss = $clientessss = '';
        foreach ($orden['info'][0]->comprasligadas as $key => $value)
        {
          $facturassss .= ' / '.$value->serie.$value->folio.' '.substr($value->fecha, 0, 10);
          $clientessss .= ', '.$value->proveedor->nombre_fiscal;
        }
        $tituloclient = 'FOLIO: ';
        $tituloclientt = 'Proveedor: ';
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array($tituloclient.substr($facturassss, 3) ), false, false);
        $pdf->SetX(6);
        $pdf->Row(array('PROVEEDOR: '.$orden['info'][0]->proveedor), false, false);
        $pdf->SetXY(6, $pdf->GetY()+6);
        $pdf->Row(array('________________________________________________________________________________________________'), false, false);
        $pdf->SetXY(6, $pdf->GetY()-2);
        $pdf->Row(array('SOLICITA: '.strtoupper($orden['info'][0]->empleado_solicito)), false, false);
      } else {
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

      if($orden['info'][0]->tipo_orden == 'f'){
        $pdf->SetWidths(array(205));
        $pdf->SetX(6);
        $pdf->Row(array($tituloclientt.substr($clientessss, 2)), false, false);
        $pdf->SetXY(6, $pdf->GetY()-3);
        $pdf->Row(array('_________________________________________________________________________________________________________________________________'), false, false);
      }

      if ($orden['info'][0]->es_receta == 't') {
        $recetasss = isset($orden['info'][0]->otros_datos->noRecetas)? implode(', ', $orden['info'][0]->otros_datos->noRecetas): '';
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('RECETAS: '.$recetasss), false, false);
      }

      $pdf->SetWidths(array(205));
      $pdf->SetFont('Arial', 'B', 8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->Row(array('ESTIMADO PROVEEDOR: PARA QUE PROCEDA SU PAGO, LE SOLICITAMOS REALIZAR SU FACTURA CON LAS ESPECIFICACIONES ARRIBA SEÑALADAS, CUMPLIENDO CON LOS REQUISITOS DE ENTREGA Y ENVIARLA AL CORREO: compras@empaquesanjorge.com'), true, true);

      $y_compras = $pdf->GetY();

      if ($orden['info'][0]->no_impresiones > -1) {
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('OBSERVACIONES: '.$orden['info'][0]->descripcion), false, false);

        $y_compras = $pdf->GetY();

        if (!empty($orden['info'][0]->ordenAplico)) {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->Row(array("ORDEN GENERADA AUTOMATICAMENTE DE LA EMPRESA {$orden['info'][0]->ordenAplico->empresa} CON FOLIO {$orden['info'][0]->ordenAplico->folio} EL DIA {$orden['info'][0]->ordenAplico->fecha}"), false, false);
        }

        $pdf->SetFont('Arial', 'B', 8);

        $y_auxx = $pdf->GetY();
        $pag_auxx = $pdf->page;

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(5, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(25, 70));
        $pdf->Row(array('EMPRESA', "{$orden['info'][0]->id_empresa} - {$orden['info'][0]->empresa}"), false, true);

        // El dato de la requisicion
        if (!empty($orden['info'][0]->requisiciones)) {
          $pdf->SetFont('Arial','',8);
          $pdf->SetXY(5, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(25, 70));
          $requiss = [];
          foreach ($orden['info'][0]->requisiciones as $key => $value) {
            $requiss[] = $value->folio;
          }
          $pdf->Row(array('ENLACE', "Requisicion(es) ".implode(' | ', $requiss)), false, true);
        }
        if (!empty($orden['info'][0]->otros_datos->prev_orden_faltantes)) {
          $pdf->SetFont('Arial','',8);
          $pdf->SetXY(5, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(25, 70));
          $pdf->Row(array('O. FALTANTES', $orden['info'][0]->otros_datos->prev_orden_faltantes->folio), false, true);
        }
        if (!empty($orden['info'][0]->area)) {
          $pdf->SetFont('Arial','',8);
          $pdf->SetXY(5, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(25, 70));
          $areas = [];
          foreach ($orden['info'][0]->area as $key => $value) {
            $areas[] = $value->nombre;
          }
          $pdf->Row(array('Cultivo / Actividad / Producto', implode(' | ', $areas)), false, true);
        }
        if (!empty($orden['info'][0]->rancho)) {
          $pdf->SetFont('Arial','',8);
          $pdf->SetXY(5, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(25, 70));
          $ranchos = [];
          foreach ($orden['info'][0]->rancho as $key => $value) {
            $ranchos[] = $value->nombre;
          }
          $pdf->Row(array('Areas / Ranchos / Lineas', implode(' | ', $ranchos)), false, true);
        }
        if (!empty($orden['info'][0]->centroCosto)) {
          $pdf->SetFont('Arial','',8);
          $pdf->SetXY(5, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(25, 70));
          $centroCosto = [];
          foreach ($orden['info'][0]->centroCosto as $key => $value) {
            $centroCosto[] = $value->nombre;
          }
          $pdf->Row(array('Centro de costo', implode(' | ', $centroCosto)), false, true);
        }
        if (!empty($orden['info'][0]->activo)) {
          $pdf->SetFont('Arial','',8);
          $pdf->SetXY(5, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(25, 70));
          $activos = [];
          foreach ($orden['info'][0]->activo as $key => $value) {
            $activos[] = $value->nombre;
          }
          $pdf->Row(array('Activo', implode(' | ', $activos)), false, true);
        }

        $pdf->page = $pag_auxx;
        $pdf->SetY($y_auxx);

        if (isset($orden['info'][0]->proyecto['info'])) {
          $pdf->SetWidths(array(120));
          $pdf->SetXY(90, $pdf->GetY()-1);
          $pdf->Row(array("PROYECTO {$orden['info'][0]->proyecto['info']->id_proyecto}: ".
              "{$orden['info'][0]->proyecto['info']->nombre} / P:".intval($orden['info'][0]->proyecto['info']->presupuesto)." / A:".intval($orden['info'][0]->proyecto['info']->aplicado)
            ), false, true);
        }

        if (!empty($orden['info'][0]->folio_hoja)) {
          $pdf->SetWidths(array(120));
          $pdf->SetXY(90, $pdf->GetY());
          $pdf->Row(array("Folio Orden: {$orden['info'][0]->folio_hoja}"), false, true);
        }


        // Si tiene compras, salidas de almacen y/o gastos de caja
        if ((isset($orden['info'][0]->comprasligadas) && count($orden['info'][0]->comprasligadas) > 0) ||
            (isset($orden['info'][0]->salidasalmacenligadas) && count($orden['info'][0]->salidasalmacenligadas) > 0) ||
            (isset($orden['info'][0]->gastoscajaligadas) && count($orden['info'][0]->gastoscajaligadas) > 0)) {
          $pdf->AddPage('P');

          $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';
          if($pdf->logo != '')
            $pdf->Image(APPPATH.(str_replace(APPPATH, '', $pdf->logo)), 6, 5, 50);

          $pdf->SetXY(150, $pdf->GetY());
          $pdf->SetFillColor(200,200,200);
          $pdf->SetFont('helvetica','B', 10);
          $pdf->SetAligns(array('C'));
          $pdf->SetWidths(array(60));
          $pdf->Row(array('ORDEN DE SERVICIO'), true, true);
          $pdf->SetXY(150, $pdf->GetY());
          $pdf->Row(array('No '.MyString::formatoNumero($orden['info'][0]->folio, 2, '')."\n \n "), false, true);
          $pdf->SetFont('helvetica','B', 8.5);
          $pdf->SetXY(150, $pdf->GetY()-8);
          $pdf->Row(array(MyString::fechaATexto($orden['info'][0]->fecha, '/c', true)), false, false);
          $pdf->SetFont('helvetica','B', 10);
          $pdf->SetXY(150, $pdf->GetY());

          $pdf->SetFont('helvetica','', 9);
          $pdf->SetXY(80, $pdf->GetY()-20);
          $pdf->Row(array('Impresión '.($orden['info'][0]->no_impresiones==0? 'ORIGINAL': ($orden['info'][0]->no_impresiones==1? 'COPIA ARCHIVO': 'COPIA '.$orden['info'][0]->no_impresiones)).
            "\n".MyString::fechaATexto(date("Y-m-d H:i:s"), '/c', true)), false, false);

          $pdf->SetXY(6, $pdf->GetY()+35);

          $total_serviciooo = $total;

          $pdf->SetFont('Arial','B',10);
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('R'));
          $pdf->SetWidths(array(204));
          $pdf->Row(array('Importe Orden: '.MyString::formatoNumero($total_serviciooo, 2, '$', false)), false, false);

          if ((isset($orden['info'][0]->comprasligadas) && count($orden['info'][0]->comprasligadas) > 0)) {
            $pdf->SetFont('Arial','B',8);
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('C'));
            $pdf->SetWidths(array(204));
            $pdf->Row(array('COMPRAS DEL SERVICIO'), true, true);

            $pdf->SetFont('Arial','B',7);
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'L', 'R'));
            $pdf->SetWidths(array(18, 22, 134, 30));
            $pdf->Row(array('FECHA', 'FOLIO', 'PROVEEDOR', 'MONTO'), true, true);

            $total_comprasss = 0;
            $pdf->SetFont('Arial','',7);
            foreach ($orden['info'][0]->comprasligadas as $key => $value) {
              $total_serviciooo += $value->total;
              $total_comprasss += $value->total;

              $pdf->SetXY(6, $pdf->GetY());
              $pdf->Row([
                substr($value->fecha, 0, 10),
                $value->serie.$value->folio,
                $value->proveedor->nombre_fiscal,
                MyString::formatoNumero($value->total, 2, '$', false),
              ], false, true);
            }

            $pdf->SetFont('Arial','B',8);
            $pdf->SetAligns(array('R', 'R'));
            $pdf->SetWidths(array(174, 30));
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->Row([
              'TOTAL COMPRAS',
              MyString::formatoNumero($total_comprasss, 2, '$', false),
            ], true, true);
          }

          if ((isset($orden['info'][0]->salidasalmacenligadas) && count($orden['info'][0]->salidasalmacenligadas) > 0)) {
            $pdf->SetFont('Arial','B',8);
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('C'));
            $pdf->SetWidths(array(204));
            $pdf->Row(array('SALIDAS DE ALMACEN'), true, true);

            $pdf->SetFont('Arial','B',7);
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R'));
            $pdf->SetWidths(array(18, 22, 100, 34, 30));
            $pdf->Row(array('FECHA', 'FOLIO', 'EMPRESA', 'ALMACEN', 'MONTO'), true, true);

            $total_salidass = 0;
            $pdf->SetFont('Arial','',7);
            foreach ($orden['info'][0]->salidasalmacenligadas as $key => $value) {
              $total_serviciooo += $value->importe;
              $total_salidass += $value->importe;

              $pdf->SetXY(6, $pdf->GetY());
              $pdf->Row([
                substr($value->fecha, 0, 10),
                $value->folio,
                $value->empresa,
                $value->almacen,
                MyString::formatoNumero($value->importe, 2, '$', false),
              ], false, true);
            }

            $pdf->SetFont('Arial','B',8);
            $pdf->SetAligns(array('R', 'R'));
            $pdf->SetWidths(array(174, 30));
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->Row([
              'TOTAL SALIDAS',
              MyString::formatoNumero($total_salidass, 2, '$', false),
            ], true, true);
          }

          if ((isset($orden['info'][0]->gastoscajaligadas) && count($orden['info'][0]->gastoscajaligadas) > 0)) {
            $pdf->SetFont('Arial','B',8);
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('C'));
            $pdf->SetWidths(array(204));
            $pdf->Row(array('GASTOS DE CAJA'), true, true);

            $pdf->SetFont('Arial','B',7);
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'L', 'R'));
            $pdf->SetWidths(array(18, 22, 134, 30));
            $pdf->Row(array('FECHA', 'FOLIO', 'EMPRESA', 'MONTO'), true, true);

            $total_gasto_cajasss = 0;
            $pdf->SetFont('Arial','',7);
            foreach ($orden['info'][0]->gastoscajaligadas as $key => $value) {
              $total_serviciooo += $value->monto;
              $total_gasto_cajasss += $value->monto;

              $pdf->SetXY(6, $pdf->GetY());
              $pdf->Row([
                substr($value->fecha, 0, 10),
                $value->folio_sig,
                $value->empresal,
                MyString::formatoNumero($value->monto, 2, '$', false),
              ], false, true);
            }

            $pdf->SetFont('Arial','B',8);
            $pdf->SetAligns(array('R', 'R'));
            $pdf->SetWidths(array(174, 30));
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->Row([
              'TOTAL GASTOS CAJA',
              MyString::formatoNumero($total_gasto_cajasss, 2, '$', false),
            ], true, true);
          }

          $pdf->SetFont('Arial','B',8);
          $pdf->SetAligns(array('R', 'R'));
          $pdf->SetWidths(array(174, 30));
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->Row([
            'COSTO TOTAL',
            MyString::formatoNumero($total_serviciooo, 2, '$', false),
          ], true, true);

        }
      }

      //a si es vehiculo
      if($orden['info'][0]->tipo_orden == 'd' && $orden['info'][0]->tipo_vehiculo != 'ot' && $orden['info'][0]->id_vehiculo > 0){
        $pdf->SetY($y_compras);

        $pdf->SetFont('Arial', '', 7);
        $pdf->SetAligns(array('C', 'L'));
        $pdf->SetWidths(array(55));
        $pdf->SetX(100);
        $pdf->Row(array('ULTIMA/COMP'), false, true);

        if (isset($orden['info'][0]->gasolina_ant)) {
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(15, 40));
          $pdf->SetX(100);
          $pdf->Row(array('Fecha', substr($orden['info'][0]->gasolina_ant->fecha, 0, 10)), false, true);
          $pdf->SetX(100);
          $pdf->Row(array('Vehículo', $orden['info'][0]->gasolina_ant->vehiculo), false, true);
          $pdf->SetX(100);
          $pdf->Row(array('Tipo', ($orden['info'][0]->gasolina_ant->tipo_vehiculo == 'd'? 'Diesel': 'Gasolina')), false, true);
          $pdf->SetX(100);
          $pdf->Row(array('Km', MyString::formatoNumero($orden['info'][0]->gasolina_ant->kilometros, 2, '', false)), false, true);
          $pdf->SetX(100);
          $pdf->Row(array('Litros', MyString::formatoNumero($orden['info'][0]->gasolina_ant->litros, 2, '', false)), false, true);
          $pdf->SetX(100);
          $pdf->Row(array('Precio', MyString::formatoNumero($orden['info'][0]->gasolina_ant->precio, 2, '', false)), false, true);
        }

        $pdf->SetY($y_compras);
        $pdf->SetAligns(array('C', 'L'));
        $pdf->SetWidths(array(55));
        $pdf->SetX(155);
        $pdf->Row(array('COMP/ACTUAL'), false, true);

        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(15, 40));
        $pdf->SetX(155);
        $pdf->Row(array('Fecha', substr($orden['info'][0]->fecha, 0, 10)), false, true);
        $pdf->SetX(155);
        $pdf->Row(array('Vehículo', $orden['info'][0]->placa.' '.$orden['info'][0]->modelo.' '.$orden['info'][0]->marca), false, true);
        $pdf->SetX(155);
        $pdf->Row(array('Tipo', ($orden['info'][0]->tipo_vehiculo == 'd'? 'Diesel': 'Gasolina')), false, true);
        if (isset($orden['info'][0]->gasolina[0])) {
          $pdf->SetX(155);
          $pdf->Row(array('Km', MyString::formatoNumero($orden['info'][0]->gasolina[0]->kilometros, 2, '', false)), false, true);
          $pdf->SetX(155);
          $pdf->Row(array('Litros', MyString::formatoNumero($orden['info'][0]->gasolina[0]->litros, 2, '', false)), false, true);
          $pdf->SetX(155);
          $pdf->Row(array('Precio', MyString::formatoNumero($orden['info'][0]->gasolina[0]->precio, 2, '', false)), false, true);
        } else {
          $pdf->SetX(155);
          $pdf->Row(array('No Capturados', ''), false, true);
        }

        $pdf->SetFont('Arial', '', 8);
      }

      //a si es flete
      if($orden['info'][0]->tipo_orden == 'f' && is_array($info_bascula) && $info_bascula[0]->data != null){
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(25, 50));
        $info_bascula = json_decode($info_bascula[0]->data);
        if(isset($info_bascula->no_ticket{0}))
        {
          $this->load->model('bascula_model');
          $id_bascula = $this->bascula_model->getIdfolio($info_bascula->no_ticket, 'sa', $info_bascula->area_id);
          $data_bascula = $this->bascula_model->getBasculaInfo($id_bascula);

          $pdf->SetX(160);
          $pdf->Row(array('Ticket No', MyString::formatoNumero($info_bascula->no_ticket, 2, '')), false, false);
          $pdf->SetX(160);
          $pdf->Row(array('Bruto', MyString::formatoNumero($data_bascula['info'][0]->kilos_bruto, 2, '', false)), false, false);
          $pdf->SetX(160);
          $pdf->Row(array('Tara', MyString::formatoNumero($data_bascula['info'][0]->kilos_tara, 2, '', false)), false, false);
          $pdf->SetX(160);
          $pdf->Row(array('Neto', MyString::formatoNumero($data_bascula['info'][0]->kilos_neto, 2, '', false)), false, false);
        }
      }

      $pdf->SetWidths(array(154));

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
      //         ($pagado_compra == $total_compra->total? 'PAGADO ':'PENDIENTE ').MyString::fechaATexto($total_compra->fecha_pago, '/c').' '.
      //         $total_compra->ref_movimiento.' '.$total_compra->alias.' ('.$total_compra->serie.$total_compra->folio.')'), false);
      //      }
      //    }
      // }


      $this->db->where('id_orden', $orden['info'][0]->id_orden)->set('no_impresiones', 'no_impresiones+1', false)->update('compras_ordenes');

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

    $hh = 270;
    $pdf = new MYpdf('P', 'mm', array(63, $hh));
    $pdf->limiteY = 50;
    $pdf->SetMargins(0, 0, 0);
    // $pdf->SetAutoPageBreak(false);
    $pdf->show_head = false;
    $pdf->onAddPage = 0;

    // $pdf->show_head = true;
    // $pdf->titulo1 = $orden['info'][0]->empresa;
    $tipo_orden = $orden['info'][0]->regresa_product=='f'?'ORDEN DE COMPRA' : 'PRODUCTOS REGRESADOS';
    if($orden['info'][0]->tipo_orden == 'd')
      $tipo_orden = 'ORDEN DE SERVICIO';
    elseif($orden['info'][0]->tipo_orden == 'f')
      $tipo_orden = 'ORDEN DE FLETE';

    $entrada_almacen = 'ENTRADA';

    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, -1);
    $pdf->Row(array($orden['info'][0]->empresa), false, false);

    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array($tipo_orden), false, false);
    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array($entrada_almacen), false, false);

    $pdf->SetFont('helvetica','', 8);
    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetX(0);
    $pdf->Row(array('No '.MyString::formatoNumero($orden['info'][0]->folio, 2, ''), MyString::fechaATexto($orden['info'][0]->fecha, '/c') ), false, false);

    $pdf->SetFont('helvetica','', 7);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('Proveedor: ' . $orden['info'][0]->proveedor), false, false);

    $pdf->SetAligns(array('C'));
    $pdf->SetXY(0, $pdf->GetY()-3);
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
        MyString::formatoNumero($prod->precio_unitario/$tipoCambio, 2, '$', false),
        MyString::formatoNumero($prod->importe/$tipoCambio, 2, '$', false),
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
                    MyString::formatoNumero($prod->precio_unitario/$tipoCambio, 2, '$', false),
                    MyString::formatoNumero($prod->importe/$tipoCambio, 2, '$', false)), false, false);

      $pdf->SetWidths(array(63));
      $pdf->SetAligns(array('C'));
      $pdf->SetXY(0, $pdf->GetY()-3);
      $pdf->Row(array('____________________________________________'), false, false);

      $subtotal  += floatval($prod->importe/$tipoCambio);
      $iva       += floatval($prod->iva/$tipoCambio);
      $total     += floatval($prod->total/$tipoCambio);
      $retencion += floatval($prod->retencion_iva/$tipoCambio);
      $ieps      += floatval($prod->ieps/$tipoCambio);

      if($prod->id_area != '' && !array_key_exists($prod->id_area, $codigoAreas)){
        $cod_soft = $this->{($prod->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($prod->id_area);
        $cod_soft = explode('/', $cod_soft);
        if (count($cod_soft) > 0) {
          $codigoAreas[$prod->id_area] = count($cod_soft)>1? $cod_soft[count($cod_soft)-2].'/'.$cod_soft[count($cod_soft)-1] : $cod_soft[0];
        }
      }
    }

    $pdf->SetWidths(array(20, 20, 23));
    $pdf->SetAligns(array('L', 'R', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array(($tipoCambio>1 ? "TC: " . $tipoCambio : ''), 'SUB-TOTAL', MyString::formatoNumero($subtotal, 2, '$', false)), false, false);
    $pdf->SetWidths(array(20, 23));
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetXY(20, $pdf->GetY()-2);
    $pdf->Row(array('IVA', MyString::formatoNumero($iva, 2, '$', false)), false, false);
    if ($ieps > 0)
    {
      $pdf->SetXY(20, $pdf->GetY()-2);
      $pdf->Row(array('IEPS', MyString::formatoNumero($ieps, 2, '$', false)), false, false);
    }
    if ($retencion > 0)
    {
      $pdf->SetXY(20, $pdf->GetY()-2);
      $pdf->Row(array('Ret. IVA', MyString::formatoNumero($retencion, 2, '$', false)), false, false);
    }
    $pdf->SetXY(20, $pdf->GetY()-2);
    $pdf->Row(array('TOTAL', MyString::formatoNumero($total, 2, '$', false)), false, false);

    //Otros datos
    $pdf->SetX(0);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    if($orden['info'][0]->tipo_orden == 'f'){
      // $this->load->model('facturacion_model');
      $this->load->model('documentos_model');
      // $facturasss = explode('|', $orden['info'][0]->ids_facrem);
      $info_bascula = false;
      if (count($orden['info'][0]->facturasligadas) > 0 || count($orden['info'][0]->boletasligadas) > 0)
      {
        $tituloclientt = $clientessss = $facturassss = $tituloclient = '';
        if ($orden['info'][0]->flete_de == 'v') {
          foreach ($orden['info'][0]->facturasligadas as $key => $value)
          {
            $facturassss .= ' / '.$value->serie.$value->folio.' '.$value->fechaT;
            $clientessss .= ', '.$value->cliente->nombre_fiscal;

            if($info_bascula === false)
            {
              $info_bascula = $this->documentos_model->getClienteDocs($value->id_factura, 1);
              if(!isset($info_bascula[0]) || $info_bascula[0]->data == 'NULL' )
                $info_bascula = false;
            }
          }
          $tituloclient = 'FOLIO: ';
          $tituloclientt = 'Clientes: ';
        } else {
          foreach ($orden['info'][0]->boletasligadas as $key => $value)
          {
            $facturassss .= ' / '.$value->folio.' '.substr($value->fecha_tara, 0, 10);
            $clientessss .= ', '.$value->proveedor;
          }
          $tituloclient = 'BOLETAS: ';
          $tituloclientt = 'Proveedores: ';
        }
        $pdf->SetXY(0, $pdf->GetY()-2);
        $pdf->Row(array($tituloclient.substr($facturassss, 3) ), false, false);
      }
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array('CLIENTE: '.$orden['info'][0]->cliente), false, false);
      $pdf->SetAligns(array('L'));
      $pdf->SetXY(0, $pdf->GetY()+6);
      $pdf->Row(array('____________________________________________'), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array('CHOFER: '.strtoupper($orden['info'][0]->empleado_solicito)), false, false);
    }else
    {
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado).
          '  EL  '.MyString::fechaATexto($orden['info'][0]->fecha, '/c') ), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array('DIO ENTRADA: '.strtoupper($orden['info'][0]->dio_entrada).
          '  EL  '.MyString::fechaATexto($orden['info'][0]->fecha_aceptacion, '/c') ), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array('SOLICITA: '.strtoupper($orden['info'][0]->empleado_solicito)), false, false);
    }


    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('AUTORIZA: '.strtoupper($orden['info'][0]->autorizo)), false, false);

    $pdf->SetAligns(array('L'));
    if(count($codigoAreas) > 0){
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
    }

    $pdf->SetAligns(array('L'));
    $pdf->SetXY(0, $pdf->GetY()-1);
    $pdf->Row(array('ALMACEN ' . $orden['info'][0]->almacen), false, false);

    if (isset($orden['info'][0]->proyecto['info'])) {
      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->Row(array('PROYECTO: ' . $orden['info'][0]->proyecto['info']->nombre), false, false);
    }

    $pdf->SetFont('helvetica','', 8);
    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetX(0);
    $pdf->Row(array('No '.MyString::formatoNumero($orden['info'][0]->cont_x_dia, 2, ''), MyString::fechaATexto($orden['info'][0]->fecha, '/c') ), false, false);

    if (isset($orden['info'][0]->boletas_lig) && count($orden['info'][0]->boletas_lig) > 0) {
      foreach ($orden['info'][0]->boletas_lig as $key => $value) {
        $pdf->SetX(0);
        $pdf->Row(array('BOLETA: ' . $value->folio, $value->area), false, false);
        $pdf->SetWidths(array(63));
        $pdf->SetXY(0, $pdf->GetY()-2);
        $pdf->Row(array('RECIBIO: ' . $value->recicio), false, false);
        $pdf->SetXY(0, $pdf->GetY()-2);
        $pdf->Row(array('ENTREGO: ' . $value->entrego), false, false);
      }
    }

    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY());
    if (strlen($orden['info'][0]->descripcion) > 0) {
      $pdf->Row(array('OBSERVACIONES: '.$orden['info'][0]->descripcion), false, false);
    }
    if($orden['info'][0]->tipo_orden == 'f'){
      $pdf->SetX(0);
      $pdf->Row(array($tituloclientt.substr($clientessss, 2)), false, false);
      $pdf->SetXY(0, $pdf->GetY()-3);
      $pdf->Row(array('______________________________________'), false, false);
    }

    // $pdf->SetXY(0, $pdf->GetY());
    // $pdf->Row(array('PROVEEDOR: ES INDISPENSABLE PRESENTAR ESTA ORDEN DE COMPRA JUNTO CON SU FACTURA PARA QUE PROCEDA SU PAGO.'), false, false);

    $pdf->SetAligns(array('C'));
    $pdf->SetX(0);
    $pdf->Row(array( 'Impresión '.($orden['info'][0]->no_impresiones_tk==0? 'ORIGINAL': 'COPIA '.$orden['info'][0]->no_impresiones_tk)), false, false);

    $this->db->where('id_orden', $orden['info'][0]->id_orden)->set('no_impresiones_tk', 'no_impresiones_tk+1', false)->update('compras_ordenes');

    $pdf->Output('orden.pdf', 'I');
  }

   public function getFechaUltimaCompra($id_producto, $id_codigo, $campo)
   {
    if ($id_producto > 0 && $id_codigo > 0) {
      $query = $this->db->query("SELECT Date(fecha_aceptacion) AS fecha
                                 FROM compras_productos
                                 WHERE status = 'a' AND id_producto = {$id_producto} AND {$campo} = {$id_codigo}")->row();
    }
    return isset($query->fecha)? $query->fecha: '';
   }

  public function getUltimaCompra($id_producto, $id_orden = null, $sql = '')
  {
    $query = null;
    if ($id_producto > 0) {
      $sql .= isset($id_orden)? " AND co.id_orden < {$id_orden}": '';

      $query = $this->db->query("SELECT cp.*, co.fecha_creacion
        FROM compras_productos cp
          INNER JOIN compras_ordenes co ON co.id_orden = cp.id_orden
        WHERE cp.status = 'a' AND cp.id_producto = {$id_producto}
          AND co.id_proveedor <> 1104 {$sql}
        ORDER BY co.fecha_aceptacion DESC
        LIMIT 1")->row();
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
    $pdf->Row(array('FECHA: '.MyString::fechaATexto($data->fecha, '/c'), 'REG. No '.$data->folio_almacen), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array($data->proveeor), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(25, 40));
    $pdf->Row(array('FOLIO: '.MyString::formatoNumero($data->folio, 2, ''), 'IMPORTE: '.MyString::formatoNumero($data->total)), false, false);
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
    fwrite($file, 'FECHA: '.MyString::fechaATexto($data->fecha, '/c').'  REG. No '.$data->folio_almacen . "\r\n");
    fwrite($file, $data->proveeor . "\r\n");
    fwrite($file, 'FOLIO: '.MyString::formatoNumero($data->folio, 2, '').'  IMPORTE: '.MyString::formatoNumero($data->total) . "\r\n");
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
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
    elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1']);
    elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha2']);

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
        MyString::formatoNumero($vehiculo->importe, 2, '', false),
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
            MyString::fechaAT($item->fecha),
            $item->folio,
            $item->nombre,
            $item->producto,
            MyString::formatoNumero($item->importe, 2, '', false),
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
        MyString::formatoNumero($lts_combustible, 2, '', false) ),
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
            MyString::fechaAT($mov->fecha),
            $mov->folio,
            $mov->proveedor,
            MyString::formatoNumero($mov->total, 2, '$', true),
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
        MyString::formatoNumero($total_importes, 2, '$', true)
      ), false);
    }

    //total general
    $pdf->SetFont('helvetica','B',8);
    $pdf->SetTextColor(0 ,0 ,0 );
    $pdf->SetX(82);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(37));
    $pdf->Row(array(
      MyString::formatoNumero($total_importes_total, 2, '$', true)
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
   * Reporte existencias por unidad
   *
   * @return
   */
  public function getActivosGastosData()
  {
    $sql = '';
    $ids_productos = '';
    $ids_activos = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    $sql .= " AND Date(co.fecha_creacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']{0}) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND co.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if(is_array($this->input->get('ids_productos')))
      $ids_productos = " WHERE p.id_producto IN(".implode(',', $this->input->get('ids_productos')).")";

    if(is_array($this->input->get('ids_activos'))){
      $ids_activos = " WHERE a.id_producto IN(".implode(',', $this->input->get('ids_activos')).")";
    }

    $response = array();
    $productos = $this->db->query("SELECT co.id_orden, Date(co.fecha_creacion) AS fecha, co.folio, cp.ids_productos, cp.productos, coa.ids_activos, coa.activos,
        coa.num, coa.num_real, (cp.importe/coa.num*coa.num_real) AS importe, cp.importe, cp.cantidad, co.descripcion
      FROM compras_ordenes co
        INNER JOIN (
          SELECT cp.id_orden, Sum(cp.importe) AS importe, string_agg(Distinct(p.nombre), ', ') productos,
            string_agg(Distinct(p.id_producto::text), ', ') ids_productos, Sum(cp.cantidad) AS cantidad
          FROM productos p
            INNER JOIN compras_productos cp ON p.id_producto = cp.id_producto
          {$ids_productos}
          GROUP BY cp.id_orden
          ORDER BY id_orden desc
        ) cp ON cp.id_orden = co.id_orden
        INNER JOIN (
          SELECT coa.id_orden, string_agg(Distinct(a.nombre), ', ') activos, string_agg(Distinct(a.id_producto::text), ', ') ids_activos,
            coa.num, Count(coa.num) AS num_real
          FROM productos a
            INNER JOIN compras_ordenes_activos coa ON a.id_producto = coa.id_activo
          {$ids_activos}
          GROUP BY coa.id_orden, coa.num
          ORDER BY id_orden desc
        ) coa ON coa.id_orden = co.id_orden
      WHERE co.status in('a', 'f') {$sql}
      ORDER BY id_orden DESC");
    $response = $productos->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getActivosGastosPdf(){
    $res = $this->getActivosGastosData();

    $this->load->model('empresas_model');
    $this->load->model('areas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->heightHeader = 25;

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte Gasto de Activos';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    // if ($this->input->get('areaId') > 0) {
    //   $darea = $this->areas_model->getAreaInfo($this->input->get('areaId'), true);
    //   $pdf->titulo3 .= "Cultivo / Actividad / Producto: {$darea['info']->nombre} \n";
    // }
    // if (is_array($this->input->get('ranchoId')) && count($this->input->get('ranchoId')) > 0) {
    //   $pdf->titulo3 .= "Areas / Ranchos / Lineas: ".implode(',', $this->input->get('ranchoText'))." \n";
    // }

    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'L', 'R', 'R', 'L');
    $widths = array(18, 18, 50, 50, 20, 20, 30);
    $header = array('Fecha', 'Folio', 'Productos', 'Activos', 'Cantidad', 'Importe', 'Descripcion');

    $familia = '';
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $producto){
      if($pdf->GetY() >= $pdf->limiteY || $key==0){
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
      $datos = array(
        $producto->fecha,
        $producto->folio,
        $producto->productos,
        $producto->activos,
        MyString::formatoNumero($producto->cantidad, 2, '', false),
        MyString::formatoNumero($producto->importe, 2, '', false),
        $producto->descripcion
      );
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);

      $proveedor_cantidad  += $producto->cantidad;
      $proveedor_importe   += $producto->importe;
    }

    $datos = array(
      '',
      '',
      '',
      'Total General',
      MyString::formatoNumero($proveedor_cantidad, 2, '', false),
      MyString::formatoNumero($proveedor_importe, 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->SetMyLinks(array());
    $pdf->Row($datos, false);

    $pdf->Output('activos_gastos.pdf', 'I');
  }

  public function getActivosGastosXls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=activos_gastos.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getActivosGastosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte Gasto de Activos';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    // if ($this->input->get('areaId') > 0) {
    //   $titulo3 .= "Cultivo / Actividad / Producto: {$this->input->get('area')} \n";
    // }
    // if (is_array($this->input->get('ranchoId')) && count($this->input->get('ranchoId')) > 0) {
    //   $titulo3 .= "Areas / Ranchos / Lineas: ".implode(',', $this->input->get('ranchoText'))." \n";
    // }


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
      $html .= '<tr style="font-weight:bold">
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Productos</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Activos</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Importe</td>
      </tr>';
    $familia = '';
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $producto) {
      $html .= '<tr>
          <td style="width:400px;border:1px solid #000;">'.$producto->fecha.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->folio.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->productos.'</td>
          <td style="width:150px;border:1px solid #000;">'.$producto->activos.'</td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero($producto->cantidad, 2, '', false).'</td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero($producto->importe, 2, '', false).'</td>
        </tr>';

      $proveedor_cantidad  += $producto->cantidad;
      $proveedor_importe   += $producto->importe;

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="4">TOTALES</td>
          <td style="border:1px solid #000;">'.$proveedor_cantidad.'</td>
          <td style="border:1px solid #000;">'.$proveedor_importe.'</td>
        </tr>
      </tbody>
    </table>';

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
