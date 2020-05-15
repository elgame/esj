<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class productos_salidas_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  /**
   * Obtiene el listado de facturas
   *
   * @return
   */
  public function getSalidas($perpage = '40')
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
      $sql = " AND Date(cs.fecha_creacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(cs.fecha_creacion) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(cs.fecha_creacion) = '".$this->input->get('ffecha2')."'";


    if($this->input->get('ffolio') != '')
    {
      $sql .= " AND cs.folio = '".$this->input->get('ffolio')."'";
    }

    if($this->input->get('did_empresa') != '')
    {
      $sql .= " AND e.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND cs.status = '".$this->input->get('fstatus')."'";
    }
    else
    {
      $sql .= " AND cs.status in ('s', 'ca')";
    }

    $sql_fil_prod = '';
    if ($this->input->get('fconceptoId') > 0) {
      // $sql_fil_prod = "INNER JOIN (
      //     SELECT id_salida FROM compras_salidas_productos WHERE id_producto = {$this->input->get('fconceptoId')} GROUP BY id_salida
      //   ) sp ON cs.id_salida = sp.id_salida";
      $sql .= " AND sp.id_producto = {$this->input->get('fconceptoId')}";
    }

    $query = BDUtil::pagination(
        "SELECT cs.id_salida,
                cs.id_empresa, e.nombre_fiscal AS empresa,
                cs.id_empleado, u.nombre AS empleado,
                cs.folio, cs.fecha_creacion AS fecha, cs.fecha_registro,
                cs.status, cs.concepto, Count(sp.id_salida) AS productos,
                cs.tipo
        FROM compras_salidas AS cs
          INNER JOIN empresas AS e ON e.id_empresa = cs.id_empresa
          INNER JOIN usuarios AS u ON u.id = cs.id_empleado
          LEFT JOIN compras_salidas_productos sp ON cs.id_salida = sp.id_salida
        WHERE 1 = 1 {$sql}
        GROUP BY cs.id_salida, e.id_empresa, u.id
        ORDER BY (cs.fecha_creacion, cs.folio) DESC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'salidas'           => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['salidas'] = $res->result();

    return $response;
  }

  /**
   * Agrega la info de una salida sin productos.
   *
   * @return array
   */
  public function agregar($data = null)
  {
    if ( ! $data)
    {
      $data = array(
        'id_empresa'        => $_POST['empresaId'],
        'id_empleado'       => $this->session->userdata('id_usuario'),
        'folio'             => $_POST['folio'],
        'fecha_creacion'    => str_replace('T', ' ', $_POST['fecha']),
        'fecha_registro'    => date("Y-m-d H:i:s"),
        // 'concepto'       => '', //$_POST['conceptoSalida']
        'status'            => 's',
        'solicito'          => $_POST['solicito'],
        'recibio'           => $_POST['recibio'],
        'id_almacen'        => $_POST['id_almacen'],
        // 'id_traspaso'    => intval($this->input->post('tid_almacen')),

        'no_receta'         => $this->input->post('no_receta')? $_POST['no_receta']: NULL,
        'etapa'             => $this->input->post('etapa')? $_POST['etapa']: NULL,
        'rancho'            => $this->input->post('ranchoC_id')? $_POST['ranchoC_id']: NULL,
        'centro_costo'      => $this->input->post('centro_costo_id')? $_POST['centro_costo_id']: NULL,
        'hectareas'         => $this->input->post('hectareas')? $_POST['hectareas']: NULL,
        'grupo'             => $this->input->post('grupo')? $_POST['grupo']: NULL,
        'no_secciones'      => $this->input->post('no_secciones')? $_POST['no_secciones']: NULL,
        'dias_despues_de'   => $this->input->post('dias_despues_de')? $_POST['dias_despues_de']: NULL,
        'metodo_aplicacion' => $this->input->post('metodo_aplicacion')? $_POST['metodo_aplicacion']: NULL,
        'ciclo'             => $this->input->post('ciclo')? $_POST['ciclo']: NULL,
        'tipo_aplicacion'   => $this->input->post('tipo_aplicacion')? $_POST['tipo_aplicacion']: NULL,
        'observaciones'     => $this->input->post('observaciones')? $_POST['observaciones']: NULL,
        'fecha_aplicacion'  => $this->input->post('fecha_aplicacion')? $_POST['fecha_aplicacion']: NULL,

        'tipo'              => $this->input->post('tipo')? $_POST['tipo']: 's',

        'id_area'           => ($this->input->post('areaId')? $_POST['areaId']: NULL),

        'id_proyecto'       => (!empty($this->input->post('proyecto'))? $_POST['proyecto']: NULL),

        // 'id_rancho'         => ($this->input->post('ranchoId')? $_POST['ranchoId']: NULL),
        // 'id_centro_costo'   => ($this->input->post('centroCostoId')? $_POST['centroCostoId']: NULL),
        'id_activo'         => ($this->input->post('activoId')? $_POST['activoId']: NULL),
        'id_empresa_ap'     => ($this->input->post('empresaApId')? $_POST['empresaApId']: NULL)
      );

      if (isset($_POST['fid_trabajador']{0})) {
        $data['id_usuario'] = $_POST['fid_trabajador'];
      }
    }
    if ($this->input->post('id_salida') > 0) {
      $id_salida = $this->input->post('id_salida');
      $this->db->update('compras_salidas', $data, "id_salida = {$id_salida}");
    } else {
      $this->db->insert('compras_salidas', $data);
      $id_salida = $this->db->insert_id('compras_salidas_id_salida_seq');
    }

    // Inserta los ranchos
    $this->agregarRanchos($id_salida);

    // Inserta los centros de costo
    $this->agregarCentrosCostos($id_salida);

    // Si es tipo combustible
    if ($this->input->post('tipo') === 'c') {
      $this->agregarCombustible($id_salida, $data);
    }

    return array('passes' => true, 'msg' => 3, 'id_salida' => $id_salida);
  }

  public function modificar($id_salida, $data = null)
  {
    if ( ! $data)
    {
      $data = array(
        'id_area'           => ($this->input->post('areaId')? $_POST['areaId']: NULL),
        // 'id_rancho'         => ($this->input->post('ranchoId')? $_POST['ranchoId']: NULL),
        // 'id_centro_costo'   => ($this->input->post('centroCostoId')? $_POST['centroCostoId']: NULL),
        'id_activo'         => ($this->input->post('activoId')? $_POST['activoId']: NULL),
        'id_empresa_ap'     => ($this->input->post('empresaApId')? $_POST['empresaApId']: NULL)
      );
    }

    $this->db->update('compras_salidas', $data, ['id_salida' => $id_salida]);

    // Inserta los ranchos
    $this->agregarRanchos($id_salida);

    // Inserta los centros de costo
    $this->agregarCentrosCostos($id_salida);

    return array('passes' => true, 'msg' => 3, 'id_salida' => $id_salida);
  }

  public function agregarCentrosCostos($idSalida, $centrosCostos = null)
  {
    $this->db->delete('compras_salidas_centro_costo', "id_salida = {$idSalida}");
    if (!$centrosCostos) {
      if (isset($_POST['centroCostoId']) && count($_POST['centroCostoId']) > 0) {
        foreach ($_POST['centroCostoId'] as $keyr => $id_centro_costo) {
          $this->db->insert('compras_salidas_centro_costo', [
            'id_centro_costo' => $id_centro_costo,
            'id_salida'       => $idSalida,
            'num'             => count($_POST['centroCostoId'])
          ]);
        }
      }
    } else {
      if (count($centrosCostos) > 0) {
        foreach ($centrosCostos as $keyr => $id_centro_costo) {
          $this->db->insert('compras_salidas_centro_costo', [
            'id_centro_costo' => $id_centro_costo,
            'id_salida'       => $idSalida,
            'num'             => count($centrosCostos)
          ]);
        }
      }
    }
  }

  public function agregarRanchos($idSalida, $ranchos = null)
  {
    $this->db->delete('compras_salidas_rancho', "id_salida = {$idSalida}");
    if (!$ranchos) {
      if (isset($_POST['ranchoId']) && count($_POST['ranchoId']) > 0) {
        foreach ($_POST['ranchoId'] as $keyr => $id_rancho) {
          $this->db->insert('compras_salidas_rancho', [
            'id_rancho' => $id_rancho,
            'id_salida' => $idSalida,
            'num'       => count($_POST['ranchoId'])
          ]);
        }
      }
    } else {
      if (count($ranchos) > 0) {
        foreach ($ranchos as $keyr => $id_rancho) {
          $this->db->insert('compras_salidas_rancho', [
            'id_rancho' => $id_rancho,
            'id_salida' => $idSalida,
            'num'       => count($ranchos)
          ]);
        }
      }
    }
  }

  public function agregarCombustible($idSalida, $data=null)
  {
    $id_combustible = isset($_POST['id_combustible'])? $_POST['id_combustible']: null;
    if ($id_combustible > 0) {
      $this->db->update('compras_salidas_combustible', [
        'id_salida'       => $idSalida,
        'id_labor'        => $_POST['clabor_id'],
        'fecha'           => substr($_POST['fecha'], 0, 10),
        'implemento'      => $_POST['cimplemento'],
        'hora_carga'      => $_POST['chora_carga'],
        'horometro_fin'   => floatval($_POST['chorometro']),
        'odometro_fin'    => floatval($_POST['codometro']),
        'lts_combustible' => floatval($_POST['clitros']),
        'precio'          => floatval($_POST['cprecio'])
      ], ['id_combustible' => $id_combustible]);
    } else {
      $this->db->insert('compras_salidas_combustible', [
        'id_salida'       => $idSalida,
        'id_labor'        => $_POST['clabor_id'],
        'fecha'           => substr($_POST['fecha'], 0, 10),
        'implemento'      => $_POST['cimplemento'],
        'hora_carga'      => (isset($_POST['chora_carga'])? $_POST['chora_carga'].':00' : date("H:i:s")),
        'horometro_fin'   => floatval($_POST['chorometro']),
        'odometro_fin'    => floatval($_POST['codometro']),
        'lts_combustible' => floatval($_POST['clitros']),
        'precio'          => floatval($_POST['cprecio'])
      ]);
      $id_combustible = $this->db->insert_id('compras_salidas_combustible_id_combustible_seq');
    }

    if (isset($data['id_activo'])) {
      $this->updateHorometroFin($idSalida, $data['id_activo'], floatval($_POST['chorometro']), floatval($_POST['codometro']), $id_combustible);
    }
  }

  public function updateHorometroFin($idSalida, $idActivo, $horometro, $odometro, $id_combustible)
  {
    $combust = $this->db->query("SELECT
        cs.id_salida, csc.id_combustible, csc.odometro, csc.horometro_fin, csc.odometro_fin, cs.fecha_creacion
      FROM compras_salidas cs
        INNER JOIN compras_salidas_combustible csc ON cs.id_salida = csc.id_salida
      WHERE cs.tipo = 'c' AND cs.status = 's' AND cs.id_activo = {$idActivo}
        AND cs.id_salida < {$idSalida}
      ORDER BY cs.id_salida DESC
      LIMIT 1")->row();
    if ($id_combustible > 0 && isset($combust->horometro_fin)) {
      $this->db->update('compras_salidas_combustible',
        ['horometro' => $combust->horometro_fin, 'odometro' => $combust->odometro_fin, 'fecha' => substr($combust->fecha_creacion, 0, 10)],
        "id_combustible = {$id_combustible}"
      );
    }
  }

  /**
   * Agrega los productos de una salida.
   *
   * @return array
   */
  public function agregarProductos($idSalida, $productos = null, $delProd = true)
  {
    if ( ! $productos)
    {
      $this->load->model('inventario_model');

      $productos = array();
      foreach ($_POST['concepto'] as $key => $concepto)
      {
        if($_POST['precioUnit'][$key] <= 0) {
          $res = $this->inventario_model->promedioData($_POST['productoId'][$key], date('Y-m-d'), date('Y-m-d'));
          $saldo = array_shift($res);
          $saldo = $saldo['saldo'][1];
        }else
          $saldo = $_POST['precioUnit'][$key];

        $productos[] = array(
          'id_salida'                    => $idSalida,
          'id_producto'                  => $_POST['productoId'][$key],
          'no_row'                       => $key,
          'cantidad'                     => $_POST['cantidad'][$key],
          'precio_unitario'              => $saldo,
          // 'id_area'                   => $_POST['codigoAreaId'][$key],
          // $_POST['codigoCampo'][$key] => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
          'id_cat_codigos'               => $this->input->post('centro_costo_id')? $_POST['centro_costo_id']: NULL,
          'tipo_orden'                   => $_POST['tipoProducto'][$key],
        );
      }
    }

    if ($delProd) {
      $this->db->delete('compras_salidas_productos', "id_salida = {$idSalida}");
    }
    $this->db->insert_batch('compras_salidas_productos', $productos);

    // si es transferencia de almacenes
    if ($this->input->post('tid_almacen') > 0) {
      $this->load->model('compras_ordenes_model');

      $fecha = date("Y-m-d H:i:s");
      $rows_compras = 0;
      $proveedor = $this->db->query("SELECT id_proveedor FROM proveedores WHERE UPPER(nombre_fiscal)='FICTICIO' LIMIT 1")->row();
      $departamento = $this->db->query("SELECT id_departamento FROM compras_departamentos WHERE UPPER(nombre)='FICTICIO' LIMIT 1")->row();
      $data = array(
        'id_empresa'         => $_POST['empresaId'],
        'id_proveedor'       => $proveedor->id_proveedor,
        'id_departamento'    => $departamento->id_departamento,
        'id_empleado'        => $this->session->userdata('id_usuario'),
        'folio'              => $this->compras_ordenes_model->folio('t'),
        'tipo_orden'         => 't',
        'status'             => 'n',
        'autorizado'         => 't',
        'fecha_autorizacion' => $fecha,
        'fecha_aceptacion'   => $fecha,
        'fecha_creacion'     => $fecha,
        'id_almacen'         => $this->input->post('tid_almacen')
      );

      $res = $this->compras_ordenes_model->agregarData($data);
      $id_orden = $res['id_orden'];
      $compra = array();
      foreach ($productos as $key => $produto)
      {
        $ultima_compra = $this->compras_ordenes_model->getUltimaCompra($produto['id_producto']);
        $precio_unitario = (isset($ultima_compra->precio_unitario)? $ultima_compra->precio_unitario: 0);
        $presenta = $this->db->query("SELECT p.nombre, pp.id_presentacion
            FROM productos p LEFT JOIN (
              SELECT * FROM productos_presentaciones WHERE status = 'ac' AND cantidad = 1
            ) pp ON p.id_producto = pp.id_producto
            WHERE p.id_producto = {$produto['id_producto']}")->row();
        $compra[] = array(
          'id_orden'         => $id_orden,
          'num_row'          => $rows_compras,
          'id_producto'      => $produto['id_producto'],
          'id_presentacion'  => ($presenta->id_presentacion>0? $presenta->id_presentacion: NULL),
          'descripcion'      => $presenta->nombre,
          'cantidad'         => abs($produto['cantidad']),
          'precio_unitario'  => $precio_unitario,
          'importe'          => (abs($produto['cantidad'])*$precio_unitario),
          'status'           => 'a',
          'fecha_aceptacion' => $fecha,
        );
        $rows_compras++;
      }
      $this->compras_ordenes_model->agregarProductosData($compra);

      // actualiza el campo traspaso, de la salida
      $this->db->update('compras_salidas',
        array('id_traspaso' => $id_orden),
        array('id_salida' => $idSalida));

      $this->db->insert('compras_transferencias', array('id_salida' => $idSalida, 'id_orden' => $id_orden));
    }

    $this->db->query("SELECT refreshallmaterializedviews();");

    return array('passes' => true, 'msg' => 3);
  }

  public function validaProductosExistencia($id_almacen, $productos, $extras = [])
  {
    $this->load->model('inventario_model');
    $response = array();
    if (count($productos)) {
      foreach ($productos as $key => $producto) {
        $item = $this->inventario_model->getEPUData($producto['id'], $id_almacen, true, $extras);
        if (isset($item[0]->saldo_anterior)) {
          $existencia = $item[0]->saldo_anterior+$item[0]->entradas-$item[0]->salidas-
                        ((!isset($extras['con_req']) || $extras['con_req'])? $item[0]->con_req: 0);
          $existencia = MyString::float( $existencia );
          if ( MyString::float($existencia-$producto['cantidad']) < 0) {
            $response[] = $item[0]->nombre_producto.' ('.($existencia-$producto['cantidad']).')';
          }
        }
      }
    }
    if (count($response)>0) {
      return ['passes' => false, 'msg' => 'No hay existencia suficiente en: '.implode(', ', $response)];
    }
    return ['passes' => true, 'msg' => 'ok'];
  }

  /**
   * Modificar los productos de una salida.
   *
   * @return array
   */
  public function modificarProductos($idSalida)
  {
    foreach ($_POST['id_producto'] as $key => $producto)
    {
      $this->db->update('compras_salidas_productos',
        array(
          'cantidad' => $_POST['cantidad'][$key],
        ),
        array('id_salida' => $idSalida, 'id_producto' => $producto));
    }

    $this->db->query("SELECT refreshallmaterializedviews();");

    return array('passes' => true, 'msg' => 5);
  }

  public function cancelar($idOrden)
  {
    $this->db->update('compras_salidas', array('status' => 'ca'), array('id_salida' => $idOrden));

    $orden = $this->db->query("SELECT id_orden FROM compras_transferencias WHERE id_salida = ".$idOrden)->row();
    $this->db->update('compras_ordenes', array('status' => 'ca'), array('id_orden' => $orden->id_orden));

    $this->db->delete('otros.recetas_salidas', "id_salida = {$idOrden}");

    $this->db->query("SELECT refreshallmaterializedviews();");

    return array('passes' => true);
  }

  public function info($idSalida, $full = false, $importe = false)
  {
    $sql_field = $sql_join = '';
    if ($importe) {
      $sql_join = " LEFT JOIN (
        SELECT id_salida, Sum((cantidad * precio_unitario))::Numeric(12, 3) AS importe
        FROM compras_salidas_productos
        GROUP BY id_salida
      ) AS imp ON cs.id_salida = imp.id_salida";
      $sql_field = ", imp.importe";
    }
    $query = $this->db->query(
      "SELECT cs.id_salida,
              cs.id_empresa, e.nombre_fiscal AS empresa, e.logo, e.dia_inicia_semana,
              cs.id_empleado, (u.nombre || ' ' || u.apellido_paterno) AS empleado,
              cs.folio, cs.fecha_creacion AS fecha, cs.fecha_registro,
              cs.status, cs.concepto, cs.solicito, cs.recibio,
              cs.id_usuario, (t.nombre || ' ' || t.apellido_paterno) AS trabajador,
              cs.no_impresiones, cs.no_impresiones_tk,
              ca.id_almacen, ca.nombre AS almacen, cs.id_traspaso,
              cs.no_receta, cs.etapa, cs.rancho, cs.centro_costo, cs.hectareas, cs.grupo,
              cs.no_secciones, cs.dias_despues_de, cs.metodo_aplicacion, cs.ciclo,
              cs.tipo_aplicacion, cs.observaciones, cs.fecha_aplicacion,
              ccr.nombre AS rancho_n, ccc.nombre AS centro_c,
              cs.id_area, cs.id_activo, Coalesce(rs.cargas) AS receta_cargas, rs.id_bascula,
              cs.tipo, cs.id_empresa_ap, ea.nombre_fiscal AS empresa_ap, cs.id_proyecto
              {$sql_field}
        FROM compras_salidas AS cs
          INNER JOIN empresas AS e ON e.id_empresa = cs.id_empresa
          INNER JOIN usuarios AS u ON u.id = cs.id_empleado
          INNER JOIN compras_almacenes AS ca ON ca.id_almacen = cs.id_almacen
          LEFT JOIN usuarios AS t ON t.id = cs.id_usuario
          LEFT JOIN otros.cat_codigos ccr ON ccr.id_cat_codigos = cs.rancho
          LEFT JOIN otros.cat_codigos ccc ON ccc.id_cat_codigos = cs.centro_costo
          LEFT JOIN otros.recetas_salidas rs ON cs.id_salida = rs.id_salida
          LEFT JOIN empresas AS ea ON ea.id_empresa = cs.id_empresa_ap
          {$sql_join}
        WHERE cs.id_salida = {$idSalida}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();

      $query->free_result();
      if ($full)
      {
        $query = $this->db->query(
          "SELECT csp.id_salida, csp.no_row,
                  csp.id_producto, pr.nombre AS producto, pr.codigo,
                  pu.abreviatura, pu.nombre as unidad,
                  csp.cantidad, csp.precio_unitario, csp.tipo_orden,
                  COALESCE(cca.id_cat_codigos, ca.id_area) AS id_area,
                  COALESCE(cca.nombre, ca.nombre) AS nombre_codigo,
                  COALESCE((CASE WHEN cca.codigo <> '' THEN cca.codigo ELSE cca.nombre END), ca.codigo_fin) AS codigo_fin,
                  (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_area' ELSE 'id_cat_codigos' END) AS campo
           FROM compras_salidas_productos AS csp
             INNER JOIN productos AS pr ON pr.id_producto = csp.id_producto
             LEFT JOIN productos_unidades AS pu ON pu.id_unidad = pr.id_unidad
             LEFT JOIN compras_areas AS ca ON ca.id_area = csp.id_area
             LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = csp.id_cat_codigos
           WHERE csp.id_salida = {$data['info'][0]->id_salida}");

        $data['info'][0]->bascula = null;
        if ($data['info'][0]->id_bascula > 0)
        {
          $this->load->model('bascula_model');
          $data['info'][0]->bascula = $this->bascula_model->getBasculaInfo(false, 0, true, [], $data['info'][0]->id_bascula)['info'][0];
        }

        $data['info'][0]->productos = array();
        if ($query->num_rows() > 0)
        {
          $data['info'][0]->productos = $query->result();
        }

        $data['info'][0]->traspaso = null;
        if ($data['info'][0]->id_traspaso > 0)
        {
          $this->load->model('compras_ordenes_model');
          $data['info'][0]->traspaso = $this->compras_ordenes_model->info($data['info'][0]->id_traspaso)['info'][0];
        }

        if ($data['info'][0]->id_proyecto > 0) {
          $this->load->model('proyectos_model');
          $data['info'][0]->proyecto = $this->proyectos_model->getProyectoInfo($data['info'][0]->id_proyecto, true);
        }

        $data['info'][0]->area = null;
        if ($data['info'][0]->id_area)
        {
          $this->load->model('areas_model');
          $data['info'][0]->area = $this->areas_model->getAreaInfo($data['info'][0]->id_area, true)['info'];
        }

        $data['info'][0]->rancho = $this->db->query("SELECT r.id_rancho, r.nombre
                                   FROM compras_salidas_rancho csr
                                    INNER JOIN otros.ranchos r ON r.id_rancho = csr.id_rancho
                                   WHERE csr.id_salida = {$data['info'][0]->id_salida}")->result();

        $data['info'][0]->centroCosto = $this->db->query("SELECT cc.id_centro_costo, cc.nombre
                                   FROM compras_salidas_centro_costo cscc
                                    INNER JOIN otros.centro_costo cc ON cc.id_centro_costo = cscc.id_centro_costo
                                   WHERE cscc.id_salida = {$data['info'][0]->id_salida}")->result();

        $data['info'][0]->activo = null;
        if ($data['info'][0]->id_activo)
        {
          $this->load->model('productos_model');
          $data['info'][0]->activo = $this->productos_model->getProductosInfo($data['info'][0]->id_activo, true)['info'];
        }

        if ($data['info'][0]->tipo == 'c') {
          $data['info'][0]->combustible = $this->db->query("SELECT sc.id_combustible, sc.id_salida, sc.id_labor, sc.fecha,
              sc.implemento, sc.hora_carga, sc.odometro, sc.lts_combustible, sc.precio, sl.nombre AS labor
           FROM compras_salidas_combustible sc
            INNER JOIN compras_salidas_labores sl ON sl.id_labor = sc.id_labor
           WHERE sc.id_salida = {$data['info'][0]->id_salida}")->row();
        }
      }

    }

    return $data;
  }

  public function folio($tipo = 'p')
  {
    $res = $this->db->select('folio')
      ->from('compras_salidas')
      ->where('concepto', null)
      ->order_by('folio', 'DESC')
      ->limit(1)->get()->row();

    $folio = (isset($res->folio) ? $res->folio : 0) + 1;

    return $folio;
  }

  public function getSalidasAjax($datos)
  {
    $filtro = isset($datos['filtro']{0})? " AND cs.folio = {$datos['filtro']}": '';
    // $filtro .= isset($datos['proveedorId']{0})? " AND p.id_proveedor = {$datos['proveedorId']} ": '';

    $query = $this->db->query("SELECT cs.id_salida, Date(cs.fecha_creacion) AS fecha, cs.folio, e.nombre_fiscal AS empresa, cs.concepto
                               FROM compras_salidas AS cs
                                  INNER JOIN empresas AS e ON e.id_empresa = cs.id_empresa
                               WHERE e.id_empresa = {$datos['empresaId']} AND cs.status = 's'
                                {$filtro} AND Date(cs.fecha_creacion) >= (now() - interval '8 months')
                               ORDER BY cs.fecha_creacion DESC, cs.folio DESC");
    $response = array();
    if($query->num_rows() > 0)
      $response = $query->result();
    $query->free_result();
    return $response;
  }


  /**
  * Visualiza/Descarga el PDF de la orden de compra.
  *
  * @return void
  */
  public function print_orden_compra($salidaID, $path = null)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $orden = $this->info($salidaID, true);

    if (isset($orden['info'][0]->productos) && count($orden['info'][0]->productos) === 0) {
      $this->print_orden_pre_salida($orden, $path);
    }

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    // $pdf->show_head = true;
    $pdf->titulo1 = $orden['info'][0]->empresa;
    $tipo_orden = 'SALIDA DE PRODUCTOS';
    // if($orden['info'][0]->tipo_orden == 'd')
    //   $tipo_orden = 'ORDEN DE SERVICIO';
    // elseif($orden['info'][0]->tipo_orden == 'f')
    //   $tipo_orden = 'ORDEN DE FLETE';

    $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetXY(6, $pdf->GetY()-10);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(150, 50));
    $pdf->Row(array(
      $tipo_orden,
      'No '.MyString::formatoNumero($orden['info'][0]->folio, 2, ''),
    ), false, false);
    // $pdf->SetFont('helvetica','', 8);
    // $pdf->SetX(6);
    // $pdf->Row(array(
    //   'PROVEEDOR: ' . $orden['info'][0]->empleado,
    //   MyString::fechaATexto($orden['info'][0]->fecha, '/c'),
    // ), false, false);

    $aligns = array('C', 'C', 'L', 'R', 'R');
    $widths = array(35, 25, 94, 25, 25);
    $header = array('CANT.', 'CODIGO', 'DESCRIPCION', 'PRECIO', 'IMPORTE');

    $subtotal = $iva = $total = $retencion = $ieps = 0;

    $tipoCambio = 0;
    $codigoAreas = array();

    foreach ($orden['info'][0]->productos as $key => $prod)
    {
      $tipoCambio = 1;

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
        $prod->producto,
        MyString::formatoNumero($prod->precio_unitario, 2, '$', false),
        MyString::formatoNumero(($prod->precio_unitario*$prod->cantidad), 2, '$', false),
      );

      $pdf->SetX(6);
      $pdf->Row($datos, false);

      $total     += floatval($prod->precio_unitario*$prod->cantidad);

      if($prod->id_area != '' && !array_key_exists($prod->id_area, $codigoAreas))
        $codigoAreas[$prod->id_area] = $this->{($prod->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($prod->id_area);
    }

    $yy = $pdf->GetY();

    //Otros datos
    // $pdf->SetXY(6, $yy);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(154));

    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(104, 50));
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado), '' ), false, false);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(154));
    $pdf->SetXY(6, $pdf->GetY()-2);
    $pdf->Row(array('SOLICITA: '.strtoupper($orden['info'][0]->solicito)), false, false);
    $pdf->SetXY(6, $pdf->GetY()-2);
    $pdf->Row(array('RECIBE: '.strtoupper($orden['info'][0]->recibio)), false, false);

    $pdf->SetXY(6, $pdf->GetY()+4);
    $pdf->Row(array('________________________________________________________________________________________________'), false, false);
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

    if ($orden['info'][0]->trabajador != '') {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetWidths(array(155));
      $pdf->Row(array('Se asigno a: ' . $orden['info'][0]->trabajador), false, false);
    }

    // ($tipoCambio ? "TIPO DE CAMBIO: " . $tipoCambio : ''),

    // $pdf->SetXY(6, $pdf->GetY());
    // $pdf->Row(array('OBSERVACIONES: '.$orden['info'][0]->descripcion), false, false);
    // if($orden['info'][0]->tipo_orden == 'f'){
    //   $pdf->SetWidths(array(205));
    //   $pdf->SetX(6);
    //   $pdf->Row(array(substr($clientessss, 2)), false, false);
    //   $pdf->SetXY(6, $pdf->GetY()-3);
    //   $pdf->Row(array('_________________________________________________________________________________________________________________________________'), false, false);
    // }

    $y_compras = $pdf->GetY();

    $pdf->SetX(6);
    $pdf->SetWidths(array(100, 100));
    $pdf->Row(array( 'Impresión '.($orden['info'][0]->no_impresiones==0? 'ORIGINAL': 'COPIA '.$orden['info'][0]->no_impresiones),
                    'Almacen: '.$orden['info'][0]->almacen.($orden['info'][0]->id_traspaso>0? ' | Traspaso de almacen': '') ), false, false);
    if (isset($orden['info'][0]->traspaso)) {
      $pdf->SetX(6);
      $pdf->Row(array( 'TRASPASO: '.$orden['info'][0]->traspaso->almacen,
                      'Fecha: '.$orden['info'][0]->traspaso->fecha.' | Orden: '.$orden['info'][0]->traspaso->folio ), false, false);
    }

    //Totales
    $pdf->SetFont('Arial','',8);
    $pdf->SetXY(160, $yy);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(25, 25));
    $pdf->SetX(160);
    $pdf->Row(array('TOTAL', MyString::formatoNumero($total, 2, '$', false)), false, true);

    $this->db->update('compras_salidas', ['no_impresiones' => $orden['info'][0]->no_impresiones+1], "id_salida = ".$orden['info'][0]->id_salida);

    if ($path)
    {
      $file = $path.'SALIDA_PRODUCTO'.date('Y-m-d').'.pdf';
      $pdf->Output($file, 'F');
      return $file;
    }
    else
    {
      $pdf->Output('SALIDA_PRODUCTO'.date('Y-m-d').'.pdf', 'I');
    }
  }

  public function print_orden_pre_salida($orden, $path = null)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    // $pdf->show_head = true;
    $pdf->titulo1 = $orden['info'][0]->empresa;
    $tipo_orden = 'ORDEN DE SALIDA DE PRODUCTOS';
    // if($orden['info'][0]->tipo_orden == 'd')
    //   $tipo_orden = 'ORDEN DE SERVICIO';
    // elseif($orden['info'][0]->tipo_orden == 'f')
    //   $tipo_orden = 'ORDEN DE FLETE';

    $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetXY(6, $pdf->GetY()-6);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(150, 50));
    $pdf->Row(array(
      $tipo_orden,
      'No '.MyString::formatoNumero($orden['info'][0]->folio, 2, ''),
    ), false, false);
    // $pdf->SetFont('helvetica','', 8);
    // $pdf->SetX(6);
    // $pdf->Row(array(
    //   'PROVEEDOR: ' . $orden['info'][0]->empleado,
    //   MyString::fechaATexto($orden['info'][0]->fecha, '/c'),
    // ), false, false);

    $aligns = array('C', 'C', 'L', 'R', 'R');
    $widths = array(35, 25, 94, 25, 25);
    $header = array('CANT.', 'CODIGO', 'DESCRIPCION', 'PRECIO', 'IMPORTE');

    $subtotal = $iva = $total = $retencion = $ieps = 0;

    $tipoCambio = 0;
    $codigoAreas = array();

    for ($i=0; $i < 15; $i++) {
      $tipoCambio = 1;

      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $i==0) { //salta de pagina si exede el max
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
        '',
        '',
        '',
        '',
        '',
      );

      $pdf->SetX(6);
      $pdf->Row($datos, false);
    }

    $yy = $pdf->GetY();

    //Otros datos
    // $pdf->SetXY(6, $yy);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(154));

    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(104, 50));
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado), '' ), false, false);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(154));
    $pdf->SetXY(6, $pdf->GetY()-2);
    $pdf->Row(array('SOLICITA: '.strtoupper($orden['info'][0]->solicito)), false, false);
    $pdf->SetXY(6, $pdf->GetY()-2);
    $pdf->Row(array('RECIBE: '.strtoupper($orden['info'][0]->recibio)), false, false);

    $pdf->SetXY(6, $pdf->GetY()+4);
    $pdf->Row(array('________________________________________________________________________________________________'), false, false);
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

    if ($orden['info'][0]->trabajador != '') {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetWidths(array(155));
      $pdf->Row(array('Se asigno a: ' . $orden['info'][0]->trabajador), false, false);
    }

    // ($tipoCambio ? "TIPO DE CAMBIO: " . $tipoCambio : ''),

    // $pdf->SetXY(6, $pdf->GetY());
    // $pdf->Row(array('OBSERVACIONES: '.$orden['info'][0]->descripcion), false, false);
    // if($orden['info'][0]->tipo_orden == 'f'){
    //   $pdf->SetWidths(array(205));
    //   $pdf->SetX(6);
    //   $pdf->Row(array(substr($clientessss, 2)), false, false);
    //   $pdf->SetXY(6, $pdf->GetY()-3);
    //   $pdf->Row(array('_________________________________________________________________________________________________________________________________'), false, false);
    // }

    $y_compras = $pdf->GetY();

    $pdf->SetX(6);
    $pdf->SetWidths(array(100, 100));
    $pdf->Row(array( 'Impresión '.($orden['info'][0]->no_impresiones==0? 'ORIGINAL': 'COPIA '.$orden['info'][0]->no_impresiones),
                    'Almacen: '.$orden['info'][0]->almacen.($orden['info'][0]->id_traspaso>0? ' | Traspaso de almacen': '') ), false, false);
    if (isset($orden['info'][0]->traspaso)) {
      $pdf->SetX(6);
      $pdf->Row(array( 'TRASPASO: '.$orden['info'][0]->traspaso->almacen,
                      'Fecha: '.$orden['info'][0]->traspaso->fecha.' | Orden: '.$orden['info'][0]->traspaso->folio ), false, false);
    }
    $auxy = $pdf->GetY();

    //Totales
    $pdf->SetFont('Arial','',8);
    $pdf->SetXY(160, $yy);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(25, 25));
    $pdf->SetX(160);
    $pdf->Row(array('TOTAL', MyString::formatoNumero($total, 2, '$', false)), false, true);

    $pdf->SetFont('Arial','',8);
    $pdf->SetXY(5, $auxy+5);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(40, 120));
    $pdf->Row(array('Cultivo / Actividad / Producto', ''), false, true);

    $pdf->SetFont('Arial','',8);
    $pdf->SetXY(5, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(40, 120));
    $pdf->Row(array('Areas / Ranchos / Lineas', ''), false, true);

    $pdf->SetFont('Arial','',8);
    $pdf->SetXY(5, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(40, 120));
    $pdf->Row(array('Centro de costo', ''), false, true);

    $pdf->SetFont('Arial','',8);
    $pdf->SetXY(5, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(40, 120));
    $pdf->Row(array('Activo', ''), false, true);

    if ($path)
    {
      $file = $path.'SALIDA_PRODUCTO'.date('Y-m-d').'.pdf';
      $pdf->Output($file, 'F');
      return $file;
    }
    else
    {
      $pdf->Output('SALIDA_PRODUCTO'.date('Y-m-d').'.pdf', 'I');
      exit;
    }
  }

  public function imprimir_salidaticket($salidaID, $path = null)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $orden = $this->info($salidaID, true);
    // echo "<pre>";
    //   var_dump($orden['info']);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 180));
    $pdf->show_head = false;
    $pdf->AddPage();
    $pdf->AddFont($pdf->fount_num, '');

    $tituloo = 'SALIDA DE PRODUCTOS';
    if ($orden['info'][0]->tipo == 'r')
      $tituloo = 'SALIDA DE RECETA';
    elseif ($orden['info'][0]->tipo == 'r')
      $tituloo = 'SALIDA DE COMBUSTIBLE';

    // Título
    $pdf->SetFont($pdf->fount_txt, 'B', 8.5);
    $pdf->SetXY(0, 3);
    $pdf->MultiCell($pdf->pag_size[0], 4, $tituloo.($orden['info'][0]->id_traspaso>0? '(Traspaso)': ''), 0, 'C');
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 4, $orden['info'][0]->empresa, 0, 'C');
    $pdf->SetFont($pdf->fount_txt, '', 7);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 4, $pdf->reg_fed, 0, 'C');

    $pdf->SetWidths(array(10, 20, 11, 20));
    $pdf->SetAligns(array('L','L', 'R', 'R'));
    $pdf->SetFounts(array($pdf->fount_txt));
    $pdf->SetX(0);
    $pdf->Row2(array('Folio: ', $orden['info'][0]->folio, 'Fecha: ', MyString::fechaAT( substr($orden['info'][0]->fecha, 0, 10) )), false, false, 5);

    $semana = MyString::obtenerSemanaDeFecha(substr($orden['info'][0]->fecha, 0, 10), $orden['info'][0]->dia_inicia_semana);

    $pdf->SetFounts(array($pdf->fount_txt), [], ['B']);
    $pdf->SetWidths(array(64));
    $pdf->SetAligns(array('L', 'L'));

    if (isset($orden['info'][0]->proyecto)) {
      $pdf->SetFounts(array($pdf->fount_txt), [], ['B']);
      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->Row2(array('Proyecto: '. $orden['info'][0]->proyecto['info']->nombre), false, false);
    }

    $pdf->SetXY(0, $pdf->GetY()-1);
    $pdf->Row2(array('Empresa aplicación: '), false, false);
    $pdf->SetFounts(array($pdf->fount_txt), [], ['']);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array($orden['info'][0]->empresa_ap), false, false);

    if (isset($orden['info'][0]->area->nombre)) {
      $pdf->SetFounts(array($pdf->fount_txt), [], ['B']);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Cultivo / Actividad / Producto: '), false, false);
      $pdf->SetFounts(array($pdf->fount_txt), [], ['']);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array($orden['info'][0]->area->nombre), false, false);
    }

    $txtranchos = [];
    if (count($orden['info'][0]->rancho) > 0) {
      $pdf->SetXY(0, $pdf->GetY()-1);
      $txtranchos = array_column($orden['info'][0]->rancho, 'nombre');
    }
    $pdf->SetFounts(array($pdf->fount_txt), [], ['B']);
    $pdf->SetXY(0, $pdf->GetY()-1);
    $pdf->Row2(array('Areas / Ranchos / Lineas: '), false, false);
    $pdf->SetFounts(array($pdf->fount_txt), [], ['']);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array(implode(', ', $txtranchos)), false, false);

    $txtcentroCostos = [];
    if (count($orden['info'][0]->centroCosto) > 0) {
      $txtcentroCostos = array_column($orden['info'][0]->centroCosto, 'nombre');
    }
    $pdf->SetFounts(array($pdf->fount_txt), [], ['B']);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Centros de costo: '), false, false);
    $pdf->SetFounts(array($pdf->fount_txt), [], ['']);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array(implode(', ', $txtcentroCostos)), false, false);

    if (isset($orden['info'][0]->activo->nombre)) {
      $pdf->SetFounts(array($pdf->fount_txt), [], ['B']);
      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->Row2(array('Activo: '), false, false);
      $pdf->SetFounts(array($pdf->fount_txt), [], ['']);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array($orden['info'][0]->activo->nombre), false, false);
    }

    if ($orden['info'][0]->tipo == 'c' && isset($orden['info'][0]->combustible)) {
      $pdf->SetWidths(array(32, 32));
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Odometro: '.MyString::formatoNumero($orden['info'][0]->combustible->odometro, 2, ''),
                      'Hr Carga: '.substr($orden['info'][0]->combustible->hora_carga, 0, 8) ), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Labor: '.$orden['info'][0]->combustible->labor, 'Implemento: '.$orden['info'][0]->combustible->implemento ), false, false);
    }

    if ($orden['info'][0]->tipo == 'r') {
      $pdf->SetWidths(array(32, 32));
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('No Receta: '.$orden['info'][0]->no_receta, 'Semana: '.$semana['semana'] ), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Etapa: '.$orden['info'][0]->etapa, 'Rancho: '.$orden['info'][0]->rancho_n ), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('CC: '.$orden['info'][0]->centro_c, 'Hectareas: '.$orden['info'][0]->hectareas ), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Grupo: '.$orden['info'][0]->grupo, 'No melgas: '.$orden['info'][0]->no_secciones ), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('DD FS: '.$orden['info'][0]->dias_despues_de, 'Metodo A: '.$orden['info'][0]->metodo_aplicacion ), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Ciclo: '.$orden['info'][0]->ciclo, 'Tipo A: '.$orden['info'][0]->tipo_aplicacion ), false, false);
    }

    $pdf->SetWidths(array(32, 32));
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Almacen: '.$orden['info'][0]->almacen, 'Fecha A: '.MyString::fechaAT($orden['info'][0]->fecha_aplicacion) ), false, false);
    if (isset($orden['info'][0]->traspaso)) {
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Traspaso: '.$orden['info'][0]->traspaso->almacen, 'Fecha: '.MyString::fechaAT($orden['info'][0]->traspaso->fecha) ), false, false);
    }
    $pdf->SetWidths(array(65));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Observaciones: '.$orden['info'][0]->observaciones ), false, false);

    $pdf->SetFont($pdf->fount_txt, '', 7);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 2, '--------------------------------------------------------------------------', 0, 'L');
    $pdf->SetFont($pdf->fount_txt, '', $pdf->font_size-1);

    $pdf->SetWidths(array(10, 28, 11, 14));
    $pdf->SetAligns(array('L','L','R','R'));
    $pdf->SetFounts(array($pdf->fount_txt), array(-1,-2,-2,-2));
    $pdf->SetX(0);
    $pdf->Row2(array('CANT.', 'DESCRIPCION', 'P.U.', 'IMPORTE'), false, true, 5);

    $pdf->SetFounts(array($pdf->fount_num,$pdf->fount_txt,$pdf->fount_num,$pdf->fount_num),
                   array(0,-1.5,-1.3,-1.2));
    $subtotal = $iva = $total = $retencion = $ieps = 0;
    $tipoCambio = 0;
    $codigoAreas = array();
    foreach ($orden['info'][0]->productos as $key => $prod) {
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array(
        $prod->cantidad.' '.$prod->abreviatura,
        $prod->producto,
        MyString::formatoNumero($prod->precio_unitario, 2, '', true),
        MyString::formatoNumero(($prod->precio_unitario*$prod->cantidad), 2, '', true),), false, false);

      $total += floatval($prod->precio_unitario*$prod->cantidad);

      if($prod->id_area != '' && !array_key_exists($prod->id_area, $codigoAreas))
        $codigoAreas[$prod->id_area] = $this->{($prod->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($prod->id_area);
      $this->load->model('catalogos_sft_model');
    }

    // $pdf->SetX(29);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(13, 20));
    // $pdf->SetX(29);
    // $pdf->Row(array('TOTAL', MyString::formatoNumero($total, 2, '$', false)), false, true);
    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
    $pdf->SetX(30);
    $pdf->Row2(array('TOTAL', MyString::formatoNumero($total, 2, '', true)), false, true, 5);

    if ($orden['info'][0]->concepto != '') {
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(66));
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array($orden['info'][0]->concepto), false, false);
    }

    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(66, 0));
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row2(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado), '' ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('SOLICITA: '.strtoupper($orden['info'][0]->solicito)), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('RECIBE: '.strtoupper($orden['info'][0]->recibio)), false, false);

    $pdf->SetXY(0, $pdf->GetY()+3);
    $pdf->Row2(array('_____________________________________________'), false, false);
    $yy2 = $pdf->GetY();
    if(count($codigoAreas) > 0){
      $yy2 = $pdf->GetY();
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
    }

    if ($orden['info'][0]->trabajador != '') {
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Se asigno a: '.strtoupper($orden['info'][0]->trabajador)), false, false);
    }

    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Expedido el: '.MyString::fechaAT(date("Y-m-d"))), false, false);

    $pdf->SetX(0);
    $pdf->Row(array( 'Impresión '.($orden['info'][0]->no_impresiones_tk==0? 'ORIGINAL': 'COPIA '.$orden['info'][0]->no_impresiones_tk)), false, false);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $this->db->update('compras_salidas', ['no_impresiones_tk' => $orden['info'][0]->no_impresiones_tk+1], "id_salida = ".$orden['info'][0]->id_salida);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }


  /**
   * Reportes
   *******************************
   * @return void
   */
  public function getDataGastos()
  {
    $this->load->model('compras_areas_model');
    $sql_compras = $sql_caja = $sql = $sql2 = '';

    //Filtro de fecha.
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '') {
      $sql_caja .= " AND Date(cg.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
      $sql_compras .= " AND Date(cp.fecha_aceptacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    }
    elseif($this->input->get('ffecha1') != '') {
      $sql_caja .= " AND Date(cg.fecha) = '".$this->input->get('ffecha1')."'";
      $sql_compras .= " AND Date(cp.fecha_aceptacion) = '".$this->input->get('ffecha1')."'";
      $sql .= " AND Date(csc.fecha) = '".$this->input->get('ffecha1')."'";
    }
    elseif($this->input->get('ffecha2') != ''){
      $sql_caja .= " AND Date(cg.fecha) = '".$this->input->get('ffecha2')."'";
      $sql_compras .= " AND Date(cp.fecha_aceptacion) = '".$this->input->get('ffecha2')."'";
      $sql .= " AND Date(csc.fecha) = '".$this->input->get('ffecha2')."'";
    }

    $sql2 = $sql;

    // vehiculos

    $response = array();


    if (isset($_GET['dareas']) && count($_GET['dareas']) > 0)
    {
      foreach ($_GET['dareas'] as $key => $value) {
        $ids_hijos = $value.$this->compras_areas_model->getHijos($value);
        $result = $this->db->query("SELECT ca.nombre, (
            SELECT Sum(importe) importe
            FROM (
              SELECT Sum(cp.total) importe
              FROM compras_productos cp INNER JOIN compras_ordenes co ON co.id_orden = cp.id_orden
              WHERE cp.id_area In({$ids_hijos}) {$sql_compras} AND cp.status = 'a' AND co.status <> 'ca'
              UNION
              SELECT Sum(cg.monto) importe
              FROM cajachica_gastos cg
              WHERE cg.id_area In({$ids_hijos}) {$sql_caja}
            ) t
          ) importe
          FROM compras_areas ca
          WHERE ca.id_area = {$value}");
        $response[] = $result->row();
        $result->free_result();

        // $result = $this->db->query("SELECT ca.nombre, COALESCE(
        //                               (SELECT (Sum(csp.cantidad * csp.precio_unitario)) AS importe
        //                               FROM compras_salidas_productos csp
        //                               WHERE csp.id_area In({$ids_hijos}))
        //                             , 0) AS importe
        //                             FROM compras_areas ca
        //                             WHERE ca.id_area = {$value}");
        // $response[] = $result->row();
        // $result->free_result();

        // // Se obtienen los costos de nomina
        // $result = $this->db->query("SELECT Sum(importe) AS importe
        //                             FROM nomina_trabajos_dia
        //                             WHERE id_area In({$ids_hijos})")->row();
        // $response[count($response)-1]->importe += $result->importe;

        // // Se obtienen los costos de los gastos de caja chica
        // $result = $this->db->query("SELECT Sum(cg.monto) AS importe
        //                             FROM cajachica_gastos cg
        //                             WHERE cg.id_area In({$ids_hijos}) {$sql_caja}")->row();
        // $response[count($response)-1]->importe += $result->importe;


        if (isset($_GET['dmovimientos']{0}) && $_GET['dmovimientos'] == '1' && $response[count($response)-1]->importe == 0)
          array_pop($response);
        else {
          if (isset($_GET['ddesglosado']{0}) && $_GET['ddesglosado'] == '1') {
            $response[count($response)-1]->detalle = $this->db->query(
            "SELECT *
              FROM (
                SELECT
                  ca.id_area, ca.nombre, Date(cp.fecha_aceptacion) fecha_orden, co.folio::text folio_orden,
                  Date(c.fecha) fecha_compra, (c.serie || c.folio) folio_compra, cp.descripcion producto,
                  cp.total importe
                FROM compras_ordenes co
                  INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
                  INNER JOIN compras_areas ca ON ca.id_area = cp.id_area
                  LEFT JOIN compras c ON c.id_compra = cp.id_compra
                WHERE ca.id_area In({$ids_hijos}) {$sql_compras}
                  AND cp.status = 'a' AND co.status <> 'ca'
                UNION
                SELECT ca.id_area, ca.nombre, Date(cg.fecha) fecha_orden, cg.folio::text folio_orden,
                  NULL fecha_compra, NULL folio_compra,
                  ('Caja #' || cg.no_caja || ' ' || cg.concepto) producto,
                  cg.monto AS importe
                FROM cajachica_gastos cg
                  INNER JOIN compras_areas ca ON ca.id_area = cg.id_area
                WHERE ca.id_area In({$ids_hijos}) {$sql_caja}
              ) t
              ORDER BY fecha_orden ASC")->result();

            // // Si es desglosado carga independientes de compras salidas
            // $response[count($response)-1]->detalle = $this->db->query(
            //     "SELECT ca.id_area, ca.nombre, Date(cs.fecha_creacion) AS fecha, cs.folio, p.nombre AS producto, (csp.cantidad * csp.precio_unitario) AS importe
            //     FROM compras_salidas cs
            //       INNER JOIN compras_salidas_productos csp ON cs.id_salida = csp.id_salida
            //       INNER JOIN compras_areas ca ON ca.id_area = csp.id_area
            //       INNER JOIN productos p ON p.id_producto = csp.id_producto
            //     WHERE ca.id_area In({$ids_hijos})
            //     ORDER BY nombre")->result();

            // // Si es desglosado carga los gastos de las nominas
            // $response[count($response)-1]->detalle = array_merge(
            //   $response[count($response)-1]->detalle,
            //   $this->db->query(
            //     "SELECT ca.id_area, ca.nombre, Date(cs.fecha) AS fecha, 'NOM' AS folio,
            //       (u.apellido_paterno || ' ' || u.apellido_materno || ' ' || u.nombre || ' - ' ||
            //         (SELECT string_agg(css.nombre, ',') FROM nomina_trabajos_dia_labores nt
            //           INNER JOIN compras_salidas_labores css ON css.id_labor = nt.id_labor
            //           WHERE nt.id_area = ca.id_area AND nt.fecha = cs.fecha AND nt.id_usuario = u.id)) AS producto, cs.importe
            //     FROM nomina_trabajos_dia cs
            //       INNER JOIN usuarios u ON cs.id_usuario = u.id
            //       INNER JOIN compras_areas ca ON ca.id_area = cs.id_area
            //     WHERE ca.id_area In({$ids_hijos})
            //     ORDER BY nombre")->result()
            // );

            // // Si es desglosado carga los gastos de caja chica
            // $response[count($response)-1]->detalle = array_merge(
            //   $response[count($response)-1]->detalle,
            //   $this->db->query(
            //     "SELECT ca.id_area, ca.nombre, Date(cg.fecha) AS fecha, cg.folio AS folio,
            //       ('Caja #' || cg.no_caja || ' ' || cg.concepto) AS producto, cg.monto AS importe
            //     FROM cajachica_gastos cg
            //       INNER JOIN compras_areas ca ON ca.id_area = cg.id_area
            //     WHERE ca.id_area In({$ids_hijos}) {$sql_caja}
            //     ORDER BY nombre")->result()
            // );
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
    $aligns2 = array('L', 'L', 'L', 'L', 'L', 'L', 'R', 'R');
    $widths2 = array(18, 18, 18, 18, 60, 45, 29);
    $header2 = array('Fecha O', 'Folio O', 'Fecha C', 'Folio C', 'C Costo', 'Producto', 'Importe');

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
            MyString::fechaAT($item->fecha_orden),
            $item->folio_orden,
            MyString::fechaAT($item->fecha_compra),
            $item->folio_compra,
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
          <td colspan="8" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="8" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="8" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="8"></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="6" style="border:1px solid #000;background-color: #cccccc;">Nombre</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
    if (isset($combustible[0]->detalle)) {
      $html .= '<tr style="font-weight:bold">
        <td></td>
        <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Fecha O</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio O</td>
        <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Fecha C</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio C</td>
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
          <td colspan="6" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->nombre.'</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->importe.'</td>
        </tr>';
      if (isset($vehiculo->detalle)) {
        foreach ($vehiculo->detalle as $key2 => $item)
        {
          $html .= '<tr>
              <td></td>
              <td style="width:100px;border:1px solid #000;background-color: #cccccc;">'.$item->fecha_orden.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->folio_orden.'</td>
              <td style="width:100px;border:1px solid #000;background-color: #cccccc;">'.$item->fecha_compra.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->folio_compra.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->nombre.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->producto.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->importe.'</td>
            </tr>';
        }
      }

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="6">TOTALES</td>
          <td colspan="2" style="border:1px solid #000;">'.$lts_combustible.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

  /**
   * Reporte de salidas por codigo
   *
   * @return
   */
  public function getProductosSalidasCodData($tipo = 'salida')
  {
    $sqlr = $sqlc = $sql = '';
    $sqcol = [
      'fields' => ", '' AS color, '' AS tipo_apli",
      'table' => ''
    ];

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $sql .= " AND Date(co.fecha_creacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

    if($this->input->get('fid_producto') != ''){
      $id_producto = $this->input->get('fid_producto');
      $sql .= " AND p.id_producto = ".$id_producto;
    }

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND co.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('did_almacen') > 0) {
      $sql .= " AND co.id_almacen = ".$this->input->get('did_almacen');
    }

    if ($this->input->get('empresaApId') > 0) {
      $sql .= " AND co.id_empresa_ap = ".$this->input->get('empresaApId');
      $sqcol['fields'] = ", String_agg(DISTINCT pca.color, ',') AS color, String_agg(DISTINCT pca.tipo_apli, ',') AS tipo_apli";
      $sqcol['table'] = "LEFT JOIN (
          SELECT id_producto, id_empresa, color, tipo_apli
          FROM productos_color_agro
          WHERE id_empresa = {$this->input->get('empresaApId')}
        ) pca ON pca.id_producto = p.id_producto";
    }

    if ($this->input->get('areaId') > 0) {
      $sql .= " AND co.id_area = ".$this->input->get('areaId');
    }

    if ($this->input->get('activoId') > 0) {
      $sql .= " AND co.id_activo = ".$this->input->get('activoId');
    }

    if(is_array($this->input->get('ranchoId'))){
      $sqlr .= " AND csr.id_rancho IN (".implode(',', $this->input->get('ranchoId')).")";
    }

    if(is_array($this->input->get('centroCostoId'))){
      $sqlc .= " AND cscc.id_centro_costo IN (".implode(',', $this->input->get('centroCostoId')).")";
    }

    if ($tipo === 'salida') {
      $res = $this->db->query(
        "SELECT *
        FROM (
          SELECT
            co.id_salida, Date(co.fecha_creacion) fecha_orden, co.folio::text folio_orden,
            p.nombre producto, co.solicito, Sum(cp.cantidad*cp.precio_unitario) importe, cp.cantidad,
            (Sum(cp.cantidad*cp.precio_unitario) / Coalesce(NULLIF(Sum(cp.cantidad), 0), 1))::Numeric(10, 2) AS precio_unitario,
            pu.nombre AS unidad, String_agg(DISTINCT cscc.centro_costo, ',') AS centro_costo,
            String_agg(DISTINCT csr.ranchos, ',') AS ranchos
          FROM compras_salidas co
            INNER JOIN compras_salidas_productos cp ON co.id_salida = cp.id_salida
            INNER JOIN productos p ON p.id_producto = cp.id_producto
            INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
            LEFT JOIN (
              SELECT csr.id_salida, String_agg(DISTINCT r.nombre, ',') AS ranchos
              FROM compras_salidas_rancho csr
                INNER JOIN otros.ranchos r ON r.id_rancho = csr.id_rancho
              WHERE 1 = 1 {$sqlr}
              GROUP BY csr.id_salida
            ) csr ON csr.id_salida = co.id_salida
            LEFT JOIN (
              SELECT cscc.id_salida, String_agg(DISTINCT cc.codigo, ',') AS centro_costo
              FROM compras_salidas_centro_costo cscc
                LEFT JOIN otros.centro_costo cc ON cc.id_centro_costo = cscc.id_centro_costo
              WHERE 1 = 1 {$sqlc}
              GROUP BY cscc.id_salida
            ) cscc ON cscc.id_salida = co.id_salida
          WHERE co.status <> 'ca' AND co.status <> 'n' {$sql}
          GROUP BY co.id_salida, p.nombre, cp.cantidad, cp.precio_unitario, pu.nombre
        ) t
        ORDER BY fecha_orden ASC
        ");
    } elseif ($tipo === 'producto') {
      $res = $this->db->query(
        "SELECT *
        FROM (
          SELECT
            p.id_producto, p.nombre producto, Sum(cp.cantidad*cp.precio_unitario) importe, Sum(cp.cantidad) AS cantidad,
            (Sum(cp.cantidad*cp.precio_unitario) / Coalesce(NULLIF(Sum(cp.cantidad), 0), 1))::Numeric(10, 2) AS precio_unitario,
            pu.nombre AS unidad {$sqcol['fields']}
          FROM compras_salidas co
            INNER JOIN compras_salidas_productos cp ON co.id_salida = cp.id_salida
            INNER JOIN productos p ON p.id_producto = cp.id_producto
            INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
            LEFT JOIN (
              SELECT csr.id_salida, String_agg(DISTINCT r.nombre, ',') AS ranchos
              FROM compras_salidas_rancho csr
                INNER JOIN otros.ranchos r ON r.id_rancho = csr.id_rancho
              GROUP BY csr.id_salida
            ) csr ON csr.id_salida = co.id_salida
            LEFT JOIN (
              SELECT cscc.id_salida, String_agg(DISTINCT cc.codigo, ',') AS centro_costo
              FROM compras_salidas_centro_costo cscc
                LEFT JOIN otros.centro_costo cc ON cc.id_centro_costo = cscc.id_centro_costo
              GROUP BY cscc.id_salida
            ) cscc ON cscc.id_salida = co.id_salida
            {$sqcol['table']}
          WHERE co.status <> 'ca' AND co.status <> 'n' {$sql}
          GROUP BY p.id_producto, p.nombre, pu.nombre
        ) t
        ORDER BY producto ASC
        ");
    }

    $response = array();
    if($res->num_rows() > 0)
      $response = $res->result();

    return $response;
  }

  /**
   * Reporte salidas de productos
   */
  public function getProductosSalidasPdf(){
    $res = $this->getProductosSalidasCodData('producto');

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte Salidas por Producto';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= (isset($almacen['info']->nombre)? "Almacen: {$almacen['info']->nombre} | ": '');
    $pdf->titulo3 .= ($this->input->get('area')? "Cultivo: {$this->input->get('area')} | " : '');
    $pdf->titulo3 .= ($this->input->get('ranchoText')? "Ranchos: ".implode(', ', $this->input->get('ranchoText'))." | " : '');
    $pdf->titulo3 .= ($this->input->get('centroCostoText')? "Centros: ".implode(', ', $this->input->get('centroCostoText'))." | " : '');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'R', 'R', 'R', 'L');
    $widths = array(110, 30, 30, 30, 30, 30);
    $header = array('Producto', 'Unidad', 'Cantidad', 'Costo', 'Importe', 'Color / Aplic');

    $total_importe = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
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
        $item->producto,
        $item->unidad,
        MyString::formatoNumero($item->cantidad, 2, '', false),
        MyString::formatoNumero($item->precio_unitario, 2, '', false),
        MyString::formatoNumero($item->importe, 2, '', false),
        "{$item->color} / {$item->tipo_apli}",
      );
      $total_importe += $item->importe;

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->SetFont('Arial','B',8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(['R', 'R']);
    $pdf->SetWidths([200, 30]);
    $pdf->Row(array('TOTAL',
      MyString::formatoNumero($total_importe, 2, '', false),
      ), false, true);

    $pdf->Output('salidas_productos.pdf', 'I');
  }

  public function getProductosSalidasXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=salidas_productos.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getProductosSalidasCodData('producto');

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte Salidas por Producto';
    $titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $titulo3 .= (isset($almacen['info']->nombre)? "Almacen: {$almacen['info']->nombre} | ": '');
    $titulo3 .= ($this->input->get('area')? "Cultivo: {$this->input->get('area')} | " : '');
    $titulo3 .= ($this->input->get('ranchoText')? "Ranchos: ".implode(', ', $this->input->get('ranchoText'))." | " : '');
    $titulo3 .= ($this->input->get('centroCostoText')? "Centros: ".implode(', ', $this->input->get('centroCostoText'))." | " : '');

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
          <td style="width:30px;border:1px solid #000;background-color: #cccccc;"></td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Producto</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Unidad</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Costo</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';

    $total_importe = 0;
    foreach($res as $key => $item){
      $html .= '<tr>
          <td style="width:30px;border:1px solid #000;"></td>
          <td style="width:200px;border:1px solid #000;">'.$item->producto.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->unidad.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->cantidad.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->precio_unitario.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->importe.'</td>
        </tr>';
      $total_importe += $item->importe;
    }

    $html .= '
            <tr style="font-weight:bold">
              <td colspan="5"></td>
              <td style="border:1px solid #000;">'.$total_importe.'</td>
            </tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
  }

  /**
   * Reporte salidas de productos x codigo
   */
  public function getProductosSalidasCodPdf(){
    $res = $this->getProductosSalidasCodData('salida');

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte Salidas por Codigo';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= (isset($almacen['info']->nombre)? "Almacen: {$almacen['info']->nombre} | ": '');
    $pdf->titulo3 .= ($this->input->get('area')? "Cultivo: {$this->input->get('area')} | " : '');
    $pdf->titulo3 .= ($this->input->get('ranchoText')? "Ranchos: ".implode(', ', $this->input->get('ranchoText'))." | " : '');
    $pdf->titulo3 .= ($this->input->get('centroCostoText')? "Centros: ".implode(', ', $this->input->get('centroCostoText'))." | " : '');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'R', 'R', 'R');
    $widths = array(17, 18, 35, 31, 45, 45, 18, 20, 20, 20);
    $header = array('Fecha', 'Folio', 'Solicito', 'Área', 'C Costo', 'Producto', 'Unidad', 'Cantidad', 'Costo', 'Importe');

    $total_importe = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
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
        MyString::fechaAT($item->fecha_orden),
        $item->folio_orden,
        $item->solicito,
        $item->ranchos,
        $item->centro_costo,
        $item->producto,
        $item->unidad,
        MyString::formatoNumero($item->cantidad, 2, '', false),
        MyString::formatoNumero($item->precio_unitario, 2, '', false),
        MyString::formatoNumero($item->importe, 2, '', false),
      );
      $total_importe += $item->importe;

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->SetFont('Arial','B',8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(['R', 'R']);
    $pdf->SetWidths([249, 20]);
    $pdf->Row(array('TOTAL',
      MyString::formatoNumero($total_importe, 2, '', false),
      ), false, true);

    $pdf->Output('salidas_productos_cod.pdf', 'I');
  }

  public function getProductosSalidasCodXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=salidas_productos_cod.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getProductosSalidasCodData('salida');

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte Salidas por Codigo';
    $titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $titulo3 .= (isset($almacen['info']->nombre)? "Almacen: {$almacen['info']->nombre} | ": '');
    $titulo3 .= ($this->input->get('area')? "Cultivo: {$this->input->get('area')} | " : '');
    $titulo3 .= ($this->input->get('ranchoText')? "Ranchos: ".implode(', ', $this->input->get('ranchoText'))." | " : '');
    $titulo3 .= ($this->input->get('centroCostoText')? "Centros: ".implode(', ', $this->input->get('centroCostoText'))." | " : '');

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="10" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="10" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="10" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="10"></td>
        </tr>
        <tr style="font-weight:bold">
          <td style="width:30px;border:1px solid #000;background-color: #cccccc;"></td>
          <td style="width:300px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Folio</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Solicito</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">C Costo</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Producto</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Unidad</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Costo</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';

    $total_importe = 0;
    foreach($res as $key => $item){
      $html .= '<tr>
          <td style="width:30px;border:1px solid #000;"></td>
          <td style="width:300px;border:1px solid #000;">'.MyString::fechaAT($item->fecha_orden).'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->folio_orden.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->solicito.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->centro_costo.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->producto.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->unidad.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->cantidad.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->precio_unitario.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->importe.'</td>
        </tr>';
      $total_importe += $item->importe;
    }

    $html .= '
            <tr style="font-weight:bold">
              <td colspan="9"></td>
              <td style="border:1px solid #000;">'.$total_importe.'</td>
            </tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
  }

}