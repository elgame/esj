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
        LEFT JOIN compras_departamentos AS cd ON cd.id_departamento = co.id_departamento
        LEFT JOIN compras_requisicion_productos AS crp ON co.id_requisicion = crp.id_requisicion
        LEFT JOIN proveedores AS p ON p.id_proveedor = crp.id_proveedor
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
    $folio = $this->folio($_POST['tipoOrden']);
    $data = array(
      'id_empresa'      => $_POST['empresaId'],
      'id_sucursal'     => (is_numeric($_POST['sucursalId'])? $_POST['sucursalId']: NULL),
      // 'id_proveedor' => $_POST['proveedorId'],
      'id_departamento' => (is_numeric($_POST['departamento'])? $_POST['departamento']: NULL),
      'id_empleado'     => $this->session->userdata('id_usuario'),
      'folio'           => $folio,
      'fecha_creacion'  => str_replace('T', ' ', $_POST['fecha']),
      'tipo_pago'       => $_POST['tipoPago'],
      'tipo_orden'      => $_POST['tipoOrden'],
      'solicito'        => $_POST['solicito'],
      'id_cliente'      => (is_numeric($_POST['clienteId'])? $_POST['clienteId']: NULL),
      'descripcion'     => $_POST['descripcion'],
      'id_almacen'      => (is_numeric($_POST['id_almacen'])? $_POST['id_almacen']: NULL),

      'id_proyecto'     => (!empty($this->input->post('proyecto'))? $_POST['proyecto']: NULL),
      'folio_hoja'      => (!empty($this->input->post('folioHoja'))? $_POST['folioHoja']: NULL),
      'uso_cfdi'        => (!empty($this->input->post('duso_cfdi'))? $_POST['duso_cfdi']: 'G03'),
      'forma_pago'      => (!empty($this->input->post('dforma_pago'))? $_POST['dforma_pago']: '99'),
    );

    // Si es un gasto son requeridos los campos de catálogos
    if ($_POST['tipoOrden'] == 'd' || $_POST['tipoOrden'] == 'oc' || $_POST['tipoOrden'] == 'f' || $_POST['tipoOrden'] == 'a'
        || $_POST['tipoOrden'] == 'p') {
      $data['id_empresa_ap'] = $this->input->post('empresaApId')? $this->input->post('empresaApId'): NULL;
      $data['id_area']       = $this->input->post('areaId')? $this->input->post('areaId'): NULL;
      // $data['id_rancho']       = $this->input->post('ranchoId')? $this->input->post('ranchoId'): NULL;
      // $data['id_centro_costo'] = $this->input->post('centroCostoId')? $this->input->post('centroCostoId'): NULL;

      // if ($_POST['tipoOrden'] !== 'a') {
      //   $data['id_activo'] = $this->input->post('activoId')? $this->input->post('activoId'): NULL;
      // }
    }

    //si es una receta
    if (isset($_POST['es_receta']))
    {
      $data['es_receta'] = $_POST['es_receta'];
    }

    //si se registra a un vehiculo
    if (isset($_POST['es_vehiculo']))
    {
      $data['tipo_vehiculo'] = $_POST['tipo_vehiculo'];
      $data['id_vehiculo'] = $_POST['vehiculoId'];
    }
    //si es flete
    if ($_POST['tipoOrden'] == 'f')
    {
      $data['flete_de'] = $_POST['fleteDe'];
      $data['ids_facrem'] = $data['flete_de']==='v'? $_POST['remfacs'] : $_POST['boletas'];
    } elseif ($_POST['tipoOrden'] == 'd') {
      if ($_POST['compras'] != '') {
        $data['ids_compras'] = $_POST['compras'];
      }
      if ($_POST['salidasAlmacen'] != '') {
        $data['ids_salidas_almacen'] = $_POST['salidasAlmacen'];
      }
      if ($_POST['gastosCaja'] != '') {
        $data['ids_gastos_caja'] = $_POST['gastosCaja'];
      }
    }

    // Si trae datos extras
    $data['otros_datos'] = [];
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
    if ($this->input->post('no_recetas') != false) {
      $data['otros_datos']['noRecetas'] = explode(',', $_POST['no_recetas']);
    }
    $data['otros_datos'] = json_encode($data['otros_datos']);

    $this->db->insert('compras_requisicion', $data);
    $ordenId = $this->db->insert_id('compras_requisicion_id_requisicion_seq');

    // Bitacora
    $this->bitacora_model->_insert('compras_requisicion', $ordenId,
                                    array(':accion'     => 'la orden de requisicion', ':seccion' => 'ordenes de compra',
                                          ':folio'      => $data['folio'],
                                          ':id_empresa' => $data['id_empresa'],
                                          ':empresa'    => 'en '.$this->input->post('empresa')));

    // Si es un gasto son requeridos los campos de catálogos
    if ($_POST['tipoOrden'] == 'd' || $_POST['tipoOrden'] == 'oc' || $_POST['tipoOrden'] == 'f' || $_POST['tipoOrden'] == 'a'
        || $_POST['tipoOrden'] == 'p') {
      // Inserta los ranchos
      if (isset($_POST['ranchoId']) && count($_POST['ranchoId']) > 0) {
        foreach ($_POST['ranchoId'] as $keyr => $id_rancho) {
          $this->db->insert('compras_requisicion_rancho', [
            'id_rancho'      => $id_rancho,
            'id_requisicion' => $ordenId,
            'num'            => count($_POST['ranchoId'])
          ]);
        }
      }

      // Inserta los centros de costo
      if (isset($_POST['centroCostoId']) && count($_POST['centroCostoId']) > 0) {
        foreach ($_POST['centroCostoId'] as $keyr => $id_centro_costo) {
          $this->db->insert('compras_requisicion_centro_costo', [
            'id_centro_costo' => $id_centro_costo,
            'id_requisicion'  => $ordenId,
            'num'             => count($_POST['centroCostoId'])
          ]);
        }
      }
    }

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
    foreach (array('1') as $value)
    {
      foreach ($_POST['concepto'] as $key => $concepto)
      {
        $id_proveedor = $_POST['proveedorId'][$key];

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
          'porcentaje_retencion' => $_POST['ret_iva'][$key],
          // 'faltantes'         => $_POST['faltantes'.$value][$key] === '' ? '0' : $_POST['faltantes'.$value][$key],
          'observacion'          => $_POST['observacion'][$key],
          'ieps'                 => is_numeric($_POST['iepsTotal'.$value][$key]) ? $_POST['iepsTotal'.$value][$key] : 0,
          'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
          'tipo_cambio'          => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
          // 'id_area'              => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
          'id_cat_codigos'       => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
          'retencion_isr'        => $_POST['retIsrTotal'.$value][$key],
          'porcentaje_isr'       => $_POST['ret_isrPorcent'][$key],
          'observaciones'        => (!empty($_POST['observacionesP'][$key])? $_POST['observacionesP'][$key]: ''),
          'activos'              => (!empty($_POST['activosP'][$key])? str_replace('”', '"', $_POST['activosP'][$key]): NULL)
        );
      }
    }

    if(count($productos) > 0)
      $this->db->insert_batch('compras_requisicion_productos', $productos);

    return array('passes' => true, 'msg' => 3);
  }

  public function agregarData($requisiciones, $productos, $ranchos = null, $centrosCostos = null)
  {
    $productosReq = [];

    foreach ($productos as $key => $prodsareas) {
      if (count($prodsareas) > 0) {
        $requisiciones[$key]['folio'] = $this->folio('p');
        $this->db->insert('compras_requisicion', $requisiciones[$key]);
        $ordenId = $this->db->insert_id('compras_requisicion_id_requisicion_seq');

        // Bitacora
        $this->bitacora_model->_insert('compras_requisicion', $ordenId,
                                      array(':accion'     => 'la orden de requisicion (del modulo de recetas)', ':seccion' => 'ordenes de compra',
                                            ':folio'      => $requisiciones[$key]['folio'],
                                            ':id_empresa' => $requisiciones[$key]['id_empresa'],
                                            ':empresa'    => 'en '));

        // productos
        foreach ($prodsareas as $key2 => $prod) {
          $productos[$key][$key2]['id_requisicion'] = $ordenId;

          foreach ($prod['prodSurtir'] as $keyps => $prodSurtir) {
            $productosReq[$ordenId][] = $prodSurtir;
          }

          unset($productos[$key][$key2]['prodSurtir']);
        }
        $this->db->insert_batch('compras_requisicion_productos', $productos[$key]);

        // Inserta los ranchos
        if (isset($ranchos[$key]) && count($ranchos[$key]) > 0) {
          foreach ($ranchos[$key] as $keyr => $id_rancho) {
            $this->db->insert('compras_requisicion_rancho', [
              'id_rancho'      => $id_rancho,
              'id_requisicion' => $ordenId,
              'num'            => count($ranchos[$key])
            ]);
          }
        }

        // Inserta los centros de costo
        if (isset($centrosCostos[$key]) && count($centrosCostos[$key]) > 0) {
          foreach ($centrosCostos[$key] as $keyr => $id_centro_costo) {
            $this->db->insert('compras_requisicion_centro_costo', [
              'id_centro_costo' => $id_centro_costo,
              'id_requisicion'  => $ordenId,
              'num'             => count($centrosCostos[$key])
            ]);
          }
        }
      }
    }

    return array('passes' => true, 'msg' => 3, 'productosReq' => $productosReq);
  }

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
        'id_almacen'      => $_POST['id_almacen'],

        'id_proyecto'     => (!empty($this->input->post('proyecto'))? $_POST['proyecto']: NULL),
        'folio_hoja'      => (!empty($this->input->post('folioHoja'))? $_POST['folioHoja']: NULL),
        'uso_cfdi'        => (!empty($this->input->post('duso_cfdi'))? $_POST['duso_cfdi']: 'G03'),
        'forma_pago'      => (!empty($this->input->post('dforma_pago'))? $_POST['dforma_pago']: '99'),
      );

      // Si es un gasto son requeridos los campos de catálogos
      if ($_POST['tipoOrden'] == 'd' || $_POST['tipoOrden'] == 'oc' || $_POST['tipoOrden'] == 'f' || $_POST['tipoOrden'] == 'a'
          || $_POST['tipoOrden'] == 'p') {
        $data['id_empresa_ap'] = $this->input->post('empresaApId')? $this->input->post('empresaApId'): NULL;
        $data['id_area']       = $this->input->post('areaId')? $this->input->post('areaId'): NULL;
        // $data['id_rancho']       = $this->input->post('ranchoId')? $this->input->post('ranchoId'): NULL;
        // $data['id_centro_costo'] = $this->input->post('centroCostoId')? $this->input->post('centroCostoId'): NULL;

        // Inserta los ranchos
        $this->db->delete('compras_requisicion_rancho', ['id_requisicion' => $idOrden]);
        if (isset($_POST['ranchoId']) && count($_POST['ranchoId']) > 0) {
          foreach ($_POST['ranchoId'] as $keyr => $id_rancho) {
            $this->db->insert('compras_requisicion_rancho', [
              'id_rancho'      => $id_rancho,
              'id_requisicion' => $idOrden,
              'num'            => count($_POST['ranchoId'])
            ]);
          }
        }

        // Inserta los centros de costo
        $this->db->delete('compras_requisicion_centro_costo', ['id_requisicion' => $idOrden]);
        if (isset($_POST['centroCostoId']) && count($_POST['centroCostoId']) > 0) {
          foreach ($_POST['centroCostoId'] as $keyr => $id_centro_costo) {
            $this->db->insert('compras_requisicion_centro_costo', [
              'id_centro_costo' => $id_centro_costo,
              'id_requisicion'  => $idOrden,
              'num'             => count($_POST['centroCostoId'])
            ]);
          }
        }

        if ($_POST['tipoOrden'] !== 'a') {
          $data['id_activo'] = $this->input->post('activoId')? $this->input->post('activoId'): NULL;
        }
      }

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

      //si es una receta
      $data['es_receta'] = 'f';
      if (isset($_POST['es_receta']))
      {
        $data['es_receta'] = 't';
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
        $data['flete_de'] = $_POST['fleteDe'];
        $data['ids_facrem'] = $data['flete_de']==='v'? $_POST['remfacs'] : $_POST['boletas'];
      } elseif ($_POST['tipoOrden'] == 'd') {
        $data['ids_compras'] = $_POST['compras'];
        $data['ids_salidas_almacen'] = $_POST['salidasAlmacen'];
        $data['ids_gastos_caja'] = $_POST['gastosCaja'];
      }

      // Si trae datos extras
      $data['otros_datos'] = [];
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
      if ($this->input->post('no_recetas') != false) {
        $data['otros_datos']['noRecetas'] = explode(',', $_POST['no_recetas']);
      }
      $data['otros_datos'] = json_encode($data['otros_datos']);

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
      foreach (array('1') as $value)
      {
        foreach ($_POST['concepto'] as $key => $concepto)
        {
          $id_proveedor = $_POST['proveedorId'][$key];
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
            'porcentaje_retencion' => $_POST['ret_iva'][$key],
            // 'faltantes'         => $_POST['faltantes'.$value][$key] === '' ? '0' : $_POST['faltantes'.$value][$key],
            'observacion'          => $_POST['observacion'][$key],
            'ieps'                 => is_numeric($_POST['iepsTotal'.$value][$key]) ? $_POST['iepsTotal'.$value][$key] : 0,
            'porcentaje_ieps'      => is_numeric($_POST['iepsPorcent'][$key]) ? $_POST['iepsPorcent'][$key] : 0,
            'tipo_cambio'          => is_numeric($_POST['tipo_cambio'][$key]) ? $_POST['tipo_cambio'][$key] : 0,
            // 'id_area'              => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
            // $_POST['codigoCampo'][$key] => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
            'id_cat_codigos'       => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
            'prod_sel'             => $prod_sel,
            'retencion_isr'        => $_POST['retIsrTotal'.$value][$key],
            'porcentaje_isr'       => $_POST['ret_isrPorcent'][$key],
            'observaciones'        => (!empty($_POST['observacionesP'][$key])? $_POST['observacionesP'][$key]: ''),
            'activos'              => (!empty($_POST['activosP'][$key])? str_replace('”', '"', $_POST['activosP'][$key]): NULL)
          );
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
        $dataOrdenCats = null;
        $dataOrden = array(
          'id_empresa'          => $data->id_empresa,
          'id_sucursal'         => (!empty($data->id_sucursal)? $data->id_sucursal: NULL),
          'id_proveedor'        => $value['id_proveedor'],
          'id_departamento'     => $data->id_departamento,
          'id_empleado'         => $data->id_empleado,
          'folio'               => $this->compras_ordenes_model->folio($data->tipo_orden),
          'status'              => 'p',
          'autorizado'          => 't',
          'fecha_autorizacion'  => $data->fecha_autorizacion,
          'fecha_aceptacion'    => substr($data->fecha_aceptacion, 0, 19),
          'fecha_creacion'      => $data->fecha,
          'tipo_pago'           => $data->tipo_pago,
          'tipo_orden'          => $data->tipo_orden,
          'solicito'            => $data->empleado_solicito,
          'id_cliente'          => (is_numeric($data->id_cliente)? $data->id_cliente: NULL),
          // 'id_proveedor_compra' => (is_numeric($data->id_proveedor_compra)? $data->id_proveedor_compra: NULL),
          'descripcion'         => $data->descripcion,
          'id_autorizo'         => $data->id_autorizo,
          'id_almacen'          => $data->id_almacen,
          'es_receta'           => $data->es_receta,

          'id_proyecto'         => (!empty($data->id_proyecto)? $data->id_proyecto: NULL),
          'folio_hoja'          => (!empty($data->folio_hoja)? $data->folio_hoja: NULL),
          'uso_cfdi'            => (!empty($data->uso_cfdi)? $data->uso_cfdi: 'G03'),
          'forma_pago'          => (!empty($data->forma_pago)? $data->forma_pago: '99'),
        );

        $dataOrdenCats['requisiciones'][] = [
          'id_requisicion' => $idOrden,
          'id_orden'       => '',
          'num_row'        => 0
        ];

        // Si es un gasto son requeridos los campos de catálogos
        if ($data->tipo_orden == 'd' || $data->tipo_orden == 'oc' || $data->tipo_orden == 'f' || $data->tipo_orden == 'a'
            || $data->tipo_orden == 'p') {

          $dataOrden['id_empresa_ap'] = !empty($data->id_empresa_ap)? $data->id_empresa_ap: NULL;

          // Inserta las areas
          if (isset($data->id_area) && $data->id_area > 0) {
            $dataOrdenCats['area'][] = [
              'id_area' => $data->id_area,
              'id_orden'  => '',
              'num'       => 1
            ];
          }

          // Inserta los ranchos
          if (isset($data->rancho) && count($data->rancho) > 0) {
            foreach ($data->rancho as $keyr => $drancho) {
              $dataOrdenCats['rancho'][] = [
                'id_rancho' => $drancho->id_rancho,
                'id_orden'  => '',
                'num'       => $drancho->num
              ];
            }
          }

          // Inserta los centros de costo
          if (isset($data->centroCosto) && count($data->centroCosto) > 0) {
            foreach ($data->centroCosto as $keyr => $dcentro_costo) {
              $dataOrdenCats['centroCosto'][] = [
                'id_centro_costo' => $dcentro_costo->id_centro_costo,
                'id_orden'        => '',
                'num'             => $dcentro_costo->num
              ];
            }
          }

          // Inserta los activos
          $or_activos = array();
          foreach ($value['productos'] as $keypr => $prod)
          {
            // activos
            if (isset($prod->activos) && !empty($prod->activos)) {
              foreach ($prod->activos as $kact => $activ) {
                if (!isset($or_activos[$kact])) {
                  $or_activos[$activ->id] = $activ;
                }
              }
            }
          }
          if (isset($or_activos) && count($or_activos) > 0) {
            foreach ($or_activos as $orkey => $activ) {
              $dataOrdenCats['activo'][] = [
                'id_activo' => $orkey,
                'id_orden'  => '',
                'num'       => count($or_activos)
              ];
            }
          }
        }

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
          $dataOrden['flete_de']   = $data->flete_de;
        } elseif ($data->tipo_orden == 'd')
        {
          $dataOrden['ids_compras'] = $data->ids_compras;
          $dataOrden['ids_salidas_almacen'] = $data->ids_salidas_almacen;
          $dataOrden['ids_gastos_caja'] = $data->ids_gastos_caja;
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

        // Si trae datos extras
        $dataOrden['otros_datos'] = [];
        if (!empty($data->otros_datos)) {
          $dataOrden['otros_datos'] = $data->otros_datos;
        }
        $dataOrden['otros_datos'] = json_encode($dataOrden['otros_datos']);

        $res = $this->compras_ordenes_model->agregarData($dataOrden, $veiculoData, $dataOrdenCats);
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
            // 'id_area'              => $prod->id_area,
            $prod->campo           => $prod->id_area,
            'retencion_isr'        => $prod->retencion_isr,
            'porcentaje_isr'       => $prod->porcentaje_isr,
            'observaciones'        => $prod->observaciones,
          );
          $rows_compras++;
        }

        if(count($productos) > 0)
          $this->compras_ordenes_model->agregarProductosData($productos);


        // ============================================
        // Si se esta creando con la empresa de Agro insumos crea la otra orden
        if ($dataOrden['id_empresa'] == 20 && $dataOrden['id_empresa_ap'] > 0 &&
            ($data->tipo_orden == 'd' || $data->tipo_orden == 'oc')) {
          $dataOrden['id_empresa']      = $dataOrden['id_empresa_ap'];
          $dataOrden['folio']           = $this->compras_ordenes_model->folio($data->tipo_orden);
          $dataOrden['id_orden_aplico'] = $id_orden;

          $data_proveedor = $this->db->query("SELECT id_proveedor FROM proveedores WHERE id_empresa = {$dataOrden['id_empresa']} AND LOWER(nombre_fiscal) LIKE LOWER('AGRO INSUMOS SANJORGE SA DE CV')")->row();
          if (!empty($data_proveedor)) {
            $dataOrden['id_proveedor'] = $data_proveedor->id_proveedor;
          }

          $res22 = $this->compras_ordenes_model->agregarData($dataOrden, $veiculoData, $dataOrdenCats);
          $id_orden22 = $res22['id_orden'];

          $productos22 = [];
          foreach ($productos as $keyon => $proon) {
            $productos[$keyon]['id_orden'] = $id_orden22;
            $data_prod = $this->db->query("SELECT id_producto FROM productos WHERE id_empresa = {$dataOrden['id_empresa']} AND LOWER(nombre) LIKE LOWER('{$proon['descripcion']}')")->row();
            if (!empty($data_prod)) {
              $productos[$keyon]['id_producto'] = $data_prod->id_producto;
              $productos22[] = $productos[$keyon];
            }
          }

          if (count($productos22) > 0) {
            $this->compras_ordenes_model->agregarProductosData($productos22);
          }
        }

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
    $this->load->model('catalogos_sft_model');

    $query = $this->db->query(
      "SELECT co.id_requisicion,
              co.id_empresa, e.nombre_fiscal AS empresa,
              co.id_sucursal, es.nombre_fiscal AS sucursal,
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
              co.es_receta,
              COALESCE(cv.placa, null) as placa,
              COALESCE(cv.modelo, null) as modelo,
              COALESCE(cv.marca, null) as marca,
              COALESCE(cv.color, null) as color,
              co.ids_facrem, co.ids_compras, co.ids_salidas_almacen, co.ids_gastos_caja,
              co.flete_de, co.id_almacen, ca.nombre AS almacen,
              co.id_area, co.id_activo, co.id_empresa_ap,
              otros_datos, co.id_proyecto, co.folio_hoja, co.uso_cfdi, co.forma_pago
      FROM compras_requisicion AS co
       INNER JOIN empresas AS e ON e.id_empresa = co.id_empresa
       INNER JOIN usuarios AS u ON u.id = co.id_empleado
       LEFT JOIN compras_departamentos AS cd ON cd.id_departamento = co.id_departamento
       LEFT JOIN usuarios AS us ON us.id = co.id_autorizo
       LEFT JOIN clientes AS cl ON cl.id_cliente = co.id_cliente
       LEFT JOIN compras_vehiculos cv ON cv.id_vehiculo = co.id_vehiculo
       LEFT JOIN compras_almacenes ca ON ca.id_almacen = co.id_almacen
       LEFT JOIN empresas_sucursales es ON es.id_sucursal = co.id_sucursal
      WHERE co.id_requisicion = {$idOrden}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();

      $data['info'][0]->otros_datos = json_decode($data['info'][0]->otros_datos);

      $query->free_result();
      if ($full)
      {
        $sql_produc = $prodAcep? " AND cp.prod_sel = 't'": '';
        // $sql_produc .= $idCompra!==NULL? " AND (cp.id_compra = {$idCompra} OR (cp.id_compra IS NULL AND Date(cp.fecha_aceptacion) <= '2014-05-26'))": '';
        $query = $this->db->query(
          "SELECT cp.id_requisicion, cp.num_row, p.id_proveedor, p.nombre_fiscal AS proveedor,
                  cp.id_producto, pr.nombre AS producto, pr.codigo, pr.id_unidad, pu.abreviatura, pu.nombre as unidad,
                  cp.id_presentacion, pp.nombre AS presentacion, pp.cantidad as presen_cantidad,
                  cp.descripcion, cp.cantidad, cp.precio_unitario, cp.importe,
                  cp.iva, cp.retencion_iva, cp.retencion_isr, cp.total, cp.porcentaje_iva,
                  cp.porcentaje_retencion, cp.porcentaje_isr, cp.observacion, cp.prod_sel,
                  cp.ieps, cp.porcentaje_ieps, cp.tipo_cambio, COALESCE(cca.id_cat_codigos, ca.id_area) AS id_area,
                  COALESCE((CASE WHEN cca.codigo <> '' THEN cca.codigo ELSE cca.nombre END), ca.codigo_fin) AS codigo_fin,
                  (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_cat_codigos' ELSE 'id_cat_codigos' END) AS campo,
                  activos, cp.observaciones
           FROM compras_requisicion_productos AS cp
           LEFT JOIN proveedores AS p ON p.id_proveedor = cp.id_proveedor
           LEFT JOIN productos AS pr ON pr.id_producto = cp.id_producto
           LEFT JOIN productos_presentaciones AS pp ON pp.id_presentacion = cp.id_presentacion
           LEFT JOIN productos_unidades AS pu ON pu.id_unidad = pr.id_unidad
           LEFT JOIN compras_areas AS ca ON ca.id_area = cp.id_area
           LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = cp.id_cat_codigos
           WHERE cp.id_requisicion = {$data['info'][0]->id_requisicion} {$sql_produc}
           ORDER BY p.id_proveedor ASC, cp.id_producto ASC");

        $data['info'][0]->productos2 = array();
        $data['info'][0]->productos = array();
        $data_proveedores = $data['info'][0]->proveedores = $data['info'][0]->data_desCodigos = array();
        if ($query->num_rows() > 0)
        {
          $productos = $query->result();
          foreach ($productos as $key => $value)
          {
            if (!empty($value->activos)) {
              $value->activos = json_decode($value->activos);
            }
            $data['info'][0]->productos2[] = clone $value;

            $data_proveedores[$value->id_proveedor] = array('id_proveedor' => $value->id_proveedor,
                                                          'nombre_fiscal' => $value->proveedor);

              // $data['info'][0]->data_desCodigos[] = $this->compras_areas_model->getDescripCodigo($value->id_area);
              // $data['info'][0]->data_desCodigos[] = $this->catalogos_sft_model->getDescripCodigo($value->id_area);
            if($value->id_area != '')
              $data['info'][0]->data_desCodigos[] = $this->{($value->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($value->id_area);

            // var_dump ($value->id_producto.$value->num_row, $value);
            $data['info'][0]->productos[$value->id_producto.$value->num_row]                                           = $value;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'precio_unitario'.$value->id_proveedor} = $value->precio_unitario;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'importe'.$value->id_proveedor}         = $value->importe;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'iva'.$value->id_proveedor}             = $value->iva;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'retencion_iva'.$value->id_proveedor}   = $value->retencion_iva;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'total'.$value->id_proveedor}           = $value->total;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'ieps'.$value->id_proveedor}            = $value->ieps;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->{'retencion_isr'.$value->id_proveedor}   = $value->retencion_isr;

            $data['info'][0]->productos[$value->id_producto.$value->num_row]->precio_unitario                          = 0;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->importe                                  = 0;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->iva                                      = 0;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->retencion_iva                            = 0;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->total                                    = 0;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->ieps                                     = 0;
            $data['info'][0]->productos[$value->id_producto.$value->num_row]->retencion_isr                            = 0;
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

        if ($data['info'][0]->id_proyecto > 0) {
          $this->load->model('proyectos_model');
          $data['info'][0]->proyecto = $this->proyectos_model->getProyectoInfo($data['info'][0]->id_proyecto, true);
        }

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
              $data['info'][0]->facturasligadas[] = $this->facturacion_model->getInfoFactura($facturaa[1], true)['info'];
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
              $data['info'][0]->boletasligadas[] = $this->bascula_model->getBasculaInfo($value, 0, true)['info'][0];
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
              $data['info'][0]->comprasligadas[] = $this->compras_model->getInfoCompra($value, true)['info'];
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
              $data['info'][0]->salidasalmacenligadas[] = $this->productos_salidas_model->info($value, false)['info'][0];
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

        $data['info'][0]->empresaAp = null;
        if ($data['info'][0]->id_empresa_ap)
        {
          $this->load->model('empresas_model');
          $data['info'][0]->empresaAp = $this->empresas_model->getInfoEmpresa($data['info'][0]->id_empresa_ap, true)['info'];
        }

        $data['info'][0]->area = null;
        if ($data['info'][0]->id_area)
        {
          $this->load->model('areas_model');
          $data['info'][0]->area = $this->areas_model->getAreaInfo($data['info'][0]->id_area, true)['info'];
        }

        $data['info'][0]->rancho = $this->db->query("SELECT r.id_rancho, r.nombre, csr.num
                                   FROM compras_requisicion_rancho csr
                                    INNER JOIN otros.ranchos r ON r.id_rancho = csr.id_rancho
                                   WHERE csr.id_requisicion = {$data['info'][0]->id_requisicion}")->result();

        $data['info'][0]->centroCosto = $this->db->query("SELECT cc.id_centro_costo, cc.nombre, cscc.num
                                   FROM compras_requisicion_centro_costo cscc
                                    INNER JOIN otros.centro_costo cc ON cc.id_centro_costo = cscc.id_centro_costo
                                   WHERE cscc.id_requisicion = {$data['info'][0]->id_requisicion}")->result();

        $data['info'][0]->activo = null;
        if ($data['info'][0]->id_activo)
        {
          $this->load->model('productos_model');
          $data['info'][0]->activo = $this->productos_model->getProductosInfo($data['info'][0]->id_activo, true)['info'];
        }
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

      $this->load->model('compras_ordenes_model');
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('L', 'mm', 'Letter');
      // $pdf->show_head = true;
      $pdf->titulo1 = $orden['info'][0]->empresa;

      $tipo_requisicion = count($orden['info'][0]->productos)>0? true: false; // requisicion, pre-requisicion

      $tipo_orden = $tipo_requisicion? 'ORDEN DE REQUISICION': 'PRE REQUISICION';


      $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

      $pdf->AliasNbPages();
      $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY()-10);

      $pdf->SetFont('helvetica','B', 10);
      $pdf->SetAligns(array('L', 'C', 'R'));
      $pdf->SetWidths(array(50, 160, 50));
      $pdf->Row(array(
        MyString::fechaATexto($orden['info'][0]->fecha, '/c'),
        $tipo_orden,
        'No '.MyString::formatoNumero($orden['info'][0]->folio, 2, ''),
      ), false, false);

      $usoCFDI = 'G03 (Gastos en General)';
      if ($orden['info'][0]->id_empresa == 20) { // agroinsumos
        $usoCFDI = 'G01 (Adquisición de mercancias)';
      }
      $yyy = $pdf->GetY();
      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(100));
      $pdf->Row(array('Modo de Facturación'), false, false);
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetWidths(array(30, 50));
      $pdf->SetXY(6, $pdf->GetY()-1.5);
      $pdf->Row(array('Condiciones:', "Crédito"), false, false);
      $pdf->SetXY(6, $pdf->GetY()-1.5);
      $pdf->Row(array('Forma de Pago:', "99 (Por Definir)"), false, false);
      $pdf->SetXY(6, $pdf->GetY()-1.5);
      $pdf->Row(array('Método de Pago:', "PPD (Pago Parcialidades/Diferido)"), false, false);
      $pdf->SetXY(6, $pdf->GetY()-1.5);
      $pdf->Row(array('Uso del CFDI:', $usoCFDI), false, false);
      $pdf->SetXY(6, $pdf->GetY()-1.5);
      $pdf->Row(array('Almacén:', $orden['info'][0]->almacen), false, false);
      $yyy1 = $pdf->GetY();

      $pdf->SetXY(95, $yyy);
      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L', 'L', 'L'));
      $pdf->SetWidths(array(100));
      $pdf->Row(array('Requisitos para la Entrega de Mercancía'), false, false);
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('( '.(isset($orden['info'][0]->otros_datos->infPasarBascula)? 'Si': 'No').' ) Pasar a Bascula a pesar la mercancía y entregar Boleta a almacén.'), false, false);
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('( '.(isset($orden['info'][0]->otros_datos->infEntOrdenCom)? 'Si': 'No').' ) Entregar la mercancía al almacenista, referenciando la presente Orden de Compra, así como anexarla a su Factura.'), false, false);

      $pdf->SetY($yyy1+2);

      $subtotal = $iva = $total = $retencion = 0;

      if ($tipo_requisicion) {
        $aligns = array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'L', 'L');
        $widths2 = array(43, 43, 43);
        $widths = array(10, 20, 46, 75, 18, 25, 18, 25, 30);
        $header = array('PROD', 'CANT', 'PROVEEDOR', 'PRODUCTO', 'P.U.', 'IMPORTE', 'ULTIMA/COM', 'PRECIO', 'Activos');

        $orden['info'][0]->totales['subtotal']  = 0;
        $orden['info'][0]->totales['iva']       = 0;
        $orden['info'][0]->totales['ieps']      = 0;
        $orden['info'][0]->totales['total']     = 0;
        $orden['info'][0]->totales['retencion'] = 0;

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

            // $pdf->SetX(144);
            // $pdf->SetAligns($aligns);
            // $pdf->SetWidths($widths2);
            // $pdf->Row(array($orden['info'][0]->proveedores[0]['nombre_fiscal'],
            //                 $orden['info'][0]->proveedores[1]['nombre_fiscal'],
            //                 $orden['info'][0]->proveedores[2]['nombre_fiscal']), false);

            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, false);
          }


          $precio_unitario1 = $prod->{'precio_unitario'.$prod->id_proveedor}/$tipoCambio*($prod->presen_cantidad>0?$prod->presen_cantidad:1);
          $activos = '';
          if (isset($prod->activos) && !empty($prod->activos)) {
            foreach ($prod->activos as $keya => $act) {
              $activos .= '-'.$act->text." \n";
            }
          }

          $ultimaCompra = $this->compras_ordenes_model->getUltimaCompra($prod->id_producto);

          $pdf->SetFont('Arial','',7);
          $pdf->SetTextColor(0,0,0);
          $datos = array(
            $prod->codigo,
            ($prod->cantidad/($prod->presen_cantidad>0?$prod->presen_cantidad:1)).''.($prod->presentacion==''? $prod->unidad: $prod->presentacion),
            $prod->proveedor,
            "{$prod->id_producto} - ".$prod->descripcion.($prod->observacion!=''? " ({$prod->observacion})": ''),
            MyString::formatoNumero($precio_unitario1, 2, '$', false),
            MyString::formatoNumero($prod->{'importe'.$prod->id_proveedor}/$tipoCambio, 2, '$', false),
            (isset($ultimaCompra->fecha_creacion)? substr($ultimaCompra->fecha_creacion, 0, 10): ''),
            (isset($ultimaCompra->precio_unitario)? MyString::formatoNumero($ultimaCompra->precio_unitario, 2, '$', false): ''),
            $activos,
          );

          $pdf->SetX(6);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, false);

          $orden['info'][0]->totales['subtotal']  += floatval($prod->{'importe'.$prod->id_proveedor}/$tipoCambio);
          $orden['info'][0]->totales['iva']       += floatval($prod->{'iva'.$prod->id_proveedor}/$tipoCambio);
          $orden['info'][0]->totales['ieps']      += floatval($prod->{'ieps'.$prod->id_proveedor}/$tipoCambio);
          $orden['info'][0]->totales['total']     += floatval($prod->{'total'.$prod->id_proveedor}/$tipoCambio);
          $orden['info'][0]->totales['retencion'] += floatval($prod->{'retencion_iva'.$prod->id_proveedor}/$tipoCambio);
        }

        // Totales
        $pdf->SetFont('Arial','B',7);
        $pdf->SetX(82);
        $pdf->SetAligns(array('R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(65, 43, 43, 43));
        $pdf->Row(array('SUB-TOTAL', MyString::formatoNumero($orden['info'][0]->totales['subtotal'], 2, '$', false)), false, true);
        $pdf->SetX(82);
        $pdf->Row(array('IVA', MyString::formatoNumero($orden['info'][0]->totales['iva'], 2, '$', false)), false, true);
        if ($orden['info'][0]->totales['ieps'] > 0)
        {
          $pdf->SetX(82);
          $pdf->Row(array('IEPS', MyString::formatoNumero($orden['info'][0]->totales['ieps'], 2, '$', false)), false, true);
        }
        if ($orden['info'][0]->totales['retencion'] > 0)
        {
          $pdf->SetX(82);
          $pdf->Row(array('Ret. IVA', MyString::formatoNumero($orden['info'][0]->totales['retencion'], 2, '$', false)), false, true);
        }
        $pdf->SetX(82);
        $pdf->Row(array('TOTAL', MyString::formatoNumero($orden['info'][0]->totales['total'], 2, '$', false)), false, true);
      }

      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(60, 50));
      $pdf->SetXY(6, $pdf->GetY()-15);
      $pdf->Row(array(($tipoCambio>0? "TIPO DE CAMBIO: " . $tipoCambio : ($tipoCambio==''? 'TIPO DE CAMBIO: ': '')) ), false, false);

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
      $pdf->SetXY(6, $pdf->GetY()+4);

      // if ($tipo_requisicion) {
      //   $pdf->SetWidths(array(250));
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->Row(array('DESCRIPCION DE CODIGOS: '.implode(', ', $orden['info'][0]->data_desCodigos)), false, false);
      // } else {
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(5, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(25, 80));
        $pdf->Row(array('EMPRESA', "{$orden['info'][0]->id_empresa} - {$orden['info'][0]->empresa}"), false, true);

        // El dato de la requisicion
        // if (!empty($orden['info'][0]->folio_requisicion)) {
        //   $pdf->SetFont('Arial','',8);
        //   $pdf->SetXY(5, $pdf->GetY());
        //   $pdf->SetAligns(array('L', 'L'));
        //   $pdf->SetWidths(array(25, 80));
        //   $pdf->Row(array('ENLACE', "Requisicion No {$orden['info'][0]->folio_requisicion}"), false, true);
        // }
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(5, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(25, 80));
        $pdf->Row(array('Cultivo / Actividad / Producto',
          (!empty($orden['info'][0]->area)? $orden['info'][0]->area->nombre: '')), false, true);

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(5, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(25, 80));
        $ranchos = [];
        foreach ($orden['info'][0]->rancho as $key => $value) {
          $ranchos[] = $value->nombre;
        }
        $pdf->Row(array('Areas / Ranchos / Lineas',
          (!empty($orden['info'][0]->rancho)? implode(' | ', $ranchos): '')), false, true);

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(5, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(25, 80));
        $centroCosto = [];
        foreach ($orden['info'][0]->centroCosto as $key => $value) {
          $centroCosto[] = $value->nombre;
        }
        $pdf->Row(array('Centro de costo',
          (!empty($orden['info'][0]->centroCosto)? implode(' | ', $centroCosto): '')), false, true);

        // $pdf->SetFont('Arial','',8);
        // $pdf->SetXY(5, $pdf->GetY());
        // $pdf->SetAligns(array('L', 'L'));
        // $pdf->SetWidths(array(25, 80));
        // $pdf->Row(array('Activo',
        //   (!empty($orden['info'][0]->activo)? $orden['info'][0]->activo->nombre: '')), false, true);
      // }


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

  public function print_pre_orden_compra($ordenId, $path = null)
  {
    $orden = $this->info($ordenId, true);
    $tipo_requisicion = count($orden['info'][0]->productos)>0? true: false; // requisicion, pre-requisicion
    if (!$tipo_requisicion && $orden['info'][0]->es_receta == 't') {
      return $this->print_pre_receta($orden, $path); // pre recetas
    } elseif ($tipo_requisicion) {
      return $this->print_orden_compra($ordenId, $path); // requisiciones con datos
    }
    // else imprime la pre orden requisición sin datos

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');
    // $pdf->show_head = true;
    $pdf->titulo1 = $orden['info'][0]->empresa;

    $tipo_orden = $tipo_requisicion? 'ORDEN DE REQUISICION': 'PRE REQUISICION';


    $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetXY(6, $pdf->GetY()-10);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetAligns(array('L', 'C', 'R'));
    $pdf->SetWidths(array(50, 160, 50));
    $pdf->Row(array(
      MyString::fechaATexto($orden['info'][0]->fecha, '/c'),
      $tipo_orden,
      'No '.MyString::formatoNumero($orden['info'][0]->folio, 2, ''),
    ), false, false);

    $subtotal = $iva = $total = $retencion = 0;

    if ($tipo_requisicion) {
      $aligns = array('L', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R');
      $widths2 = array(43, 43, 43);
      $widths = array(15, 10, 18, 30, 70, 18, 25, 18, 20, 18, 25);
      $header = array('AREA', 'PROD', 'CANT', 'UNIDAD', 'PRODUCTO', 'P.U.', 'IMPORTE', 'P.U.', 'IMPORTE', 'P.U.', 'IMPORTE');

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
          "{$prod->id_producto} - ".$prod->descripcion.($prod->observacion!=''? " ({$prod->observacion})": ''),
          MyString::formatoNumero($precio_unitario1, 2, '$', false),
          MyString::formatoNumero($prod->{'importe'.$orden['info'][0]->proveedores[0]['id_proveedor']}/$tipoCambio, 2, '$', false),
          MyString::formatoNumero($precio_unitario2, 2, '$', false),
          MyString::formatoNumero($prod->{'importe'.$orden['info'][0]->proveedores[1]['id_proveedor']}/$tipoCambio, 2, '$', false),
          MyString::formatoNumero($precio_unitario3, 2, '$', false),
          MyString::formatoNumero($prod->{'importe'.$orden['info'][0]->proveedores[2]['id_proveedor']}/$tipoCambio, 2, '$', false),
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

      // Totales
      $pdf->SetFont('Arial','B',7);
      $pdf->SetX(79);
      $pdf->SetAligns(array('R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(65, 43, 43, 43));
      $pdf->Row(array('SUB-TOTAL', MyString::formatoNumero($orden['info'][0]->proveedores[0]['subtotal'], 2, '$', false),
                                  MyString::formatoNumero($orden['info'][0]->proveedores[1]['subtotal'], 2, '$', false),
                                  MyString::formatoNumero($orden['info'][0]->proveedores[2]['subtotal'], 2, '$', false)), false, true);
      $pdf->SetX(79);
      $pdf->Row(array('IVA', MyString::formatoNumero($orden['info'][0]->proveedores[0]['iva'], 2, '$', false),
                            MyString::formatoNumero($orden['info'][0]->proveedores[1]['iva'], 2, '$', false),
                            MyString::formatoNumero($orden['info'][0]->proveedores[2]['iva'], 2, '$', false)), false, true);
      if ($orden['info'][0]->proveedores[0]['ieps'] > 0)
      {
        $pdf->SetX(79);
        $pdf->Row(array('IEPS', MyString::formatoNumero($orden['info'][0]->proveedores[0]['ieps'], 2, '$', false),
                                    MyString::formatoNumero($orden['info'][0]->proveedores[1]['ieps'], 2, '$', false),
                                    MyString::formatoNumero($orden['info'][0]->proveedores[2]['ieps'], 2, '$', false)), false, true);
      }
      if ($orden['info'][0]->proveedores[0]['retencion'] > 0)
      {
        $pdf->SetX(79);
        $pdf->Row(array('Ret. IVA', MyString::formatoNumero($orden['info'][0]->proveedores[0]['retencion'], 2, '$', false),
                                    MyString::formatoNumero($orden['info'][0]->proveedores[1]['retencion'], 2, '$', false),
                                    MyString::formatoNumero($orden['info'][0]->proveedores[2]['retencion'], 2, '$', false)), false, true);
      }
      $pdf->SetX(79);
      $pdf->Row(array('TOTAL', MyString::formatoNumero($orden['info'][0]->proveedores[0]['total'], 2, '$', false),
                              MyString::formatoNumero($orden['info'][0]->proveedores[1]['total'], 2, '$', false),
                              MyString::formatoNumero($orden['info'][0]->proveedores[2]['total'], 2, '$', false)), false, true);
    } else { // *********** cuando es una pre requisición
      $aligns = array('L', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R');
      $aligns2 = array('R', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R');
      $widths2 = array(20, 51, 51, 51);
      $widths = array(18, 30, 65, 21, 30, 21, 30, 21, 30);
      $header = array('CANT', 'UNIDAD', 'PRODUCTO', 'P.U.', 'IMPORTE', 'P.U.', 'IMPORTE', 'P.U.', 'IMPORTE');

      for ($i=0; $i < 15; $i++) {
        if($pdf->GetY() >= $pdf->limiteY || $i === 0) { //salta de pagina si exede el max
          if($pdf->GetY()+5 >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetXY(6, $pdf->GetY()+3);
          $pdf->SetFont('Arial','B',7);
          // $pdf->SetTextColor(255,255,255);
          // $pdf->SetFillColor(160,160,160);

          $pdf->SetX(99);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths2);
          $pdf->Row(array('Cotizaciones:', '', '', ''), false);
          $pdf->SetX(99);
          $pdf->Row(array('Proveedores:', '', '', ''), false, true, null, 6);

          $pdf->SetFont('Arial','',8);
          $pdf->SetXY(6, $pdf->GetY()-6);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(17, 70));
          $pdf->Row(array('Almacén:', $orden['info'][0]->almacen), false, false);

          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, false);
        }

        $pdf->SetFont('Arial','',7);
        $pdf->SetTextColor(0,0,0);
        $datos = array('','','','','','','','','');

        $pdf->SetX(6);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }

      // Totales
      $pdf->SetFont('Arial','B',7);
      $pdf->SetX(99);
      $pdf->SetAligns(array('R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(20, 51, 51, 51));
      $pdf->Row(array('SUB-TOTAL', '', '', ''), false, true);
      $pdf->SetX(99);
      $pdf->Row(array('IVA', '', '', ''), false, true);
      $pdf->SetX(99);
      $pdf->Row(array('TOTAL', '', '', ''), false, true);

      $tipoCambio = '';
    }

    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(60, 50));
    $pdf->SetXY(6, $pdf->GetY()-15);
    $pdf->Row(array(($tipoCambio>0? "TIPO DE CAMBIO: " . $tipoCambio : ($tipoCambio==''? 'TIPO DE CAMBIO: ': '')) ), false, false);

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
    $pdf->SetXY(6, $pdf->GetY()+4);

    // if ($tipo_requisicion) {
    //   $pdf->SetWidths(array(250));
    //   $pdf->SetXY(6, $pdf->GetY());
    //   $pdf->Row(array('DESCRIPCION DE CODIGOS: '.implode(', ', $orden['info'][0]->data_desCodigos)), false, false);
    // } else {
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 80));
      $pdf->Row(array('EMPRESA', "{$orden['info'][0]->id_empresa} - {$orden['info'][0]->empresa}"), false, true);

      // El dato de la requisicion
      // if (!empty($orden['info'][0]->folio_requisicion)) {
      //   $pdf->SetFont('Arial','',8);
      //   $pdf->SetXY(5, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L'));
      //   $pdf->SetWidths(array(25, 80));
      //   $pdf->Row(array('ENLACE', "Requisicion No {$orden['info'][0]->folio_requisicion}"), false, true);
      // }
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 80));
      $pdf->Row(array('Cultivo / Actividad / Producto',
        (!empty($orden['info'][0]->area)? $orden['info'][0]->area->nombre: '')), false, true);

      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 80));
      $ranchos = [];
      foreach ($orden['info'][0]->rancho as $key => $value) {
        $ranchos[] = $value->nombre;
      }
      $pdf->Row(array('Areas / Ranchos / Lineas',
        (!empty($orden['info'][0]->rancho)? implode(' | ', $ranchos): '')), false, true);

      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 80));
      $centroCosto = [];
      foreach ($orden['info'][0]->centroCosto as $key => $value) {
        $centroCosto[] = $value->nombre;
      }
      $pdf->Row(array('Centro de costo',
        (!empty($orden['info'][0]->centroCosto)? implode(' | ', $centroCosto): '')), false, true);

      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 80));
      $pdf->Row(array('Activo', (!empty($orden['info'][0]->activo)? $orden['info'][0]->activo->nombre: '')), false, true);

    // }


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

  public function print_pre_receta($orden, $path = null)
  {
    // $orden = $this->info($ordenId, true);
    $tipo_requisicion = count($orden['info'][0]->productos)>0? true: false; // requisicion, pre-requisicion

    $tipo_orden = 'PRE REQUISICION';

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');
    // $pdf->show_head = true;
    $pdf->titulo1 = ''; // $orden['info'][0]->empresa;
    $pdf->titulo2 = $tipo_orden;

    $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

    $pdf->noShowPages = false;
    $pdf->noShowDate = false;
    $pdf->AddPage();

    // /home/gama/www/sanjorge/application/images/mas
    $pdf->Image(APPPATH.'images/mas/pre-recetas.png', 205, 72, 60);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetAligns(array('L', 'C', 'R'));
    $pdf->SetWidths(array(50));
    $pdf->SetXY(222, $pdf->GetY()-20);
    $pdf->Row(array('PRE-REQ: '.MyString::formatoNumero($orden['info'][0]->folio, 2, '')), false, true);
    $pdf->SetXY(222, $pdf->GetY());
    $pdf->Row(array('RECETA: '.(isset($orden['info'][0]->otros_datos->noRecetas)? implode(',', $orden['info'][0]->otros_datos->noRecetas): '')), false, true);

    $pdf->SetFont('helvetica','B', 7);
    $pdf->SetAligns(array('L', 'L', 'R', 'L', 'R', 'L', 'R', 'L', ));
    $pdf->SetWidths(array(16, 63, 16, 25, 20, 25, 12, 25, ));
    $pdf->SetXY(6, $pdf->GetY()+12);
    $auxt = $pdf->GetY();
    $pdf->Row(array(
      'EMPRESA:', $orden['info'][0]->empresa,
      '# HAS:', '______________',
      'PLANTA X HA:', '______________',
      'SEM:', '______________',
    ), false, false);

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(array(
      'RANCHO:', '____________________________________________',
      '# CARGAS:', '______________',
      'KG PLANTAS:', '______________',
      'CICLO:', '______________',
    ), false, false);

    $pdf->SetWidths(array(16, 63, 20, 21, 20, 25, 12, 25, ));
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(array(
      'SECCION:', '____________________________________________',
      '_____________', '____________',
      'PH FORMULA:', '______________',
      '', '',
    ), false, false);
    $pdf->SetTextColor(200, 200, 200);
    $pdf->SetXY(6, $pdf->GetY()-6);
    $pdf->Row(array(
      '', '',
      'COMPLETA', 'MEDIA',
      '', '',
      '', '',
    ), false, false);

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(45));
    $pdf->SetXY(220, $auxt-5);
    $pdf->Row(array('FECHA'), false, TRUE);
    $pdf->SetWidths(array(15, 15, 15));
    $pdf->SetXY(220, $auxt+1);
    $pdf->Row(array('', '', ''), false, TRUE);

    $pdf->SetWidths(array(11));
    $pdf->SetXY(209, $pdf->GetY()+5);
    $pdf->Row(array('ETAPA'), false, false);
    $pdf->SetWidths(array(11, 34));
    $pdf->SetXY(220, $pdf->GetY()-9);
    $pdf->Row(array('DP', ''), false, true);
    $pdf->SetXY(220, $pdf->GetY()+1);
    $pdf->Row(array('DF', ''), false, true);

    $pdf->SetFont('helvetica','B', 7);
    $pdf->SetWidths(array(16, 81));
    $pdf->SetXY(170, $pdf->GetY()+1);
    $pdf->Row(array('OBJETIVO: ', '_________________________________________________________'), false, false);

    $pdf->SetXY(6, $pdf->GetY()+5);

    $subtotal = $iva = $total = $retencion = 0;

    $aligns = array('C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C');
    $aligns2 = array('R', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R');
    $widths2 = array(20, 51, 51, 51);
    $widths = array(18, 65, 21, 22, 25, 30, 85);
    $header = array('(%) (Pz) MEZCLA', 'ORDEN DE APLICACION', 'DOSIS', 'APLICACIÓN TOTAL', 'PRECIO', 'IMPORTE', 'DATOS DE APLICACION');

    $auxy = $pdf->GetY();
    for ($i=0; $i < 15; $i++) {
      if($pdf->GetY() >= $pdf->limiteY || $i === 0) { //salta de pagina si exede el max
        if($pdf->GetY()+5 >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetXY(6, $pdf->GetY()+3);
        $pdf->SetFont('Arial','B',7);

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, false);
      }

      $pdf->SetFont('Arial','',7);
      $pdf->SetTextColor(0,0,0);
      $datos = array('','','','','','');

      $pdf->SetX(6);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    // Totales
    $pdf->SetFont('Arial','B',7);
    $pdf->SetX(6);
    $pdf->SetAligns(array('R', 'R', 'R', 'R', 'C', 'C'));
    $pdf->SetWidths(array(83, 21, 22, 25, 30, 85));
    $pdf->Row(array('SUMAS', '', '', 'TOTAL', '', 'FIRMA'), false, true);

    $pdf->SetY($auxy+11);
    $pdf->SetFont('Arial','',7);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(15, 21, 15, 17, 17, 15, 15));
    $pdf->SetX(187);
    $pdf->Row(array("   TURNO    ", 'AMANECER', 'DIA', 'TARDE', 'NOCHE'), false, true);
    $pdf->SetWidths(array(15, 14, 12, 15, 14, 15, 15));
    $pdf->SetX(187);
    $pdf->Row(array('VIA', 'SISTEMA', 'FOLIAR', 'SOLIDA PISO', 'DRENCH', 'UNTADO'), false, true);
    $pdf->SetWidths(array(17, 17, 15, 20, 16, 14, 12));
    $pdf->SetX(187);
    $pdf->Row(array('APLICACION', 'MANUAL', 'RUEGO', 'TERRESTRE', 'ACERO'), false, true);
    $pdf->SetWidths(array(17, 17, 15, 20, 16, 14, 12));
    $pdf->SetX(187);
    $pdf->Row(array('EQUIPOS', 'BOOM', 'PIPA', 'AGUILON', 'TANQUITA'), false, true);
    $pdf->SetX(187);
    $pdf->Row(array('', 'DRON', 'TAMBO', 'MOCHILA', 'OTROS'), false, true);

    $pdf->SetWidths(array(17, 50));
    $pdf->SetX(187);
    $pdf->Row(array('LITROS DE AGUA', '__________________________________'), false, false);
    $pdf->SetWidths(array(17, 68));
    $pdf->SetX(187);
    $pdf->Row(array('OBSERVACIONES', '________________________________________________'), false, false);
    $pdf->SetX(187);
    $pdf->Row(array('', '________________________________________________'), false, false);
    $pdf->SetXY(187, $pdf->GetY()+2);
    $pdf->Row(array('', '________________________________________________'), false, false);
    $pdf->SetXY(187, $pdf->GetY()+2);
    $pdf->Row(array('', '________________________________________________'), false, false);

    $pdf->SetXY(187, $pdf->GetY()+2);
    $pdf->Row(array('SOLICITA', '________________________________________________'), false, false);

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