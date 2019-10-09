<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class recetas_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
    $this->load->model('bitacora_model');
  }

  public function getRecetas($perpage = '100', $autorizadas = true)
  {
    $sql = '';
    //paginacion
    $params = array(
        'result_items_per_page' => $perpage,
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    );

    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    if($this->input->get('fbuscar') != '')
    {
      $sqlfolio = is_numeric($this->input->get('fbuscar'))? "f.folio = '".$this->input->get('fbuscar')."' OR r.folio = '".$this->input->get('fbuscar')."' OR ": '';
      $sql .= " AND ({$sqlfolio} f.nombre LIKE '%".$this->input->get('fbuscar')."%')";
    }

    if($this->input->get('ftipo') != '')
    {
      $sql .= " AND r.tipo = '".$this->input->get('ftipo')."'";
    }

    if($this->input->get('did_empresa') != '')
    {
      $sql .= "  AND r.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if($this->input->get('did_area') != '')
    {
      $sql .= " AND r.id_area = '".$this->input->get('did_area')."'";
    }

    if($this->input->get('fstatus') != '')
    {
      $sql .= " AND r.status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT r.id_recetas, r.id_formula, r.id_empresa, r.id_area, a.nombre AS area,
          f.nombre, r.folio, f.folio AS folio_formula, r.tipo, r.status, r.fecha
        FROM otros.recetas r INNER JOIN otros.formulas f ON r.id_formula = f.id_formula
          INNER JOIN areas a ON a.id_area = r.id_area
        WHERE 1 = 1 {$sql}
        ORDER BY r.folio DESC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'recetas'       => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['recetas'] = $res->result();

    return $response;
  }

  public function folio($empresaId, $tipo = 'kg')
  {
    $res = $this->db->select('folio')
      ->from('otros.recetas')
      ->where('tipo', $tipo)
      ->where('id_empresa', $empresaId)
      ->order_by('folio', 'DESC')
      ->limit(1)->get()->row();

    $folio = (isset($res->folio) ? $res->folio : 0) + 1;

    return $folio;
  }

  public function info($recetaId, $full = false)
  {
    $query = $this->db->query(
      "SELECT r.id_recetas, r.id_empresa, e.nombre_fiscal AS empresa, r.id_area, a.nombre AS area, r.fecha,
        r.id_realizo, (rea.nombre || ' ' || rea.apellido_paterno || ' ' || rea.apellido_materno) AS realizo,
        r.id_solicito, (sol.nombre || ' ' || sol.apellido_paterno || ' ' || sol.apellido_materno) AS solicito,
        r.id_autorizo, (aut.nombre || ' ' || aut.apellido_paterno || ' ' || aut.apellido_materno) AS autorizo,
        r.folio, r.objetivo, r.semana, r.dosis_planta, r.planta_ha, r.ha_neta, r.no_plantas, r.kg_totales,
        r.a_etapa, r.a_ciclo, r.a_dds, r.a_turno, r.a_via, r.a_aplic, r.a_equipo, r.a_observaciones, r.status,
        r.id_formula, f.nombre AS formula, f.folio AS folio_formula, r.tipo, r.ha_bruta, r.carga1, r.carga2, r.ph
      FROM otros.recetas r
        INNER JOIN areas a ON a.id_area = r.id_area
        INNER JOIN empresas e ON e.id_empresa = r.id_empresa
        INNER JOIN usuarios aut ON aut.id = r.id_autorizo
        INNER JOIN usuarios rea ON rea.id = r.id_realizo
        INNER JOIN usuarios sol ON sol.id = r.id_solicito
        INNER JOIN otros.formulas f ON f.id_formula = r.id_formula
      WHERE r.id_recetas = {$recetaId}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->row();

      $query->free_result();
      if ($full)
      {
        $query = $this->db->query(
          "SELECT rp.id_receta, rp.id_producto, rp.rows, pr.nombre AS producto, pr.codigo,
            pr.id_unidad, rp.percent, rp.dosis_mezcla, rp.aplicacion_total, rp.precio, rp.importe
          FROM otros.recetas_productos AS rp
            INNER JOIN productos AS pr ON pr.id_producto = rp.id_producto
          WHERE rp.id_receta = {$data['info']->id_recetas}
          ORDER BY rp.rows ASC");

        $data['info']->productos = $query->result();
        $query->free_result();

        $this->load->model('empresas_model');
        $data['info']->empresaData = $this->empresas_model->getInfoEmpresa($data['info']->id_empresa)['info'];

        $data['info']->rancho = $this->db->query("SELECT r.id_rancho, r.nombre, csr.num
                                  FROM otros.recetas_rancho csr
                                    INNER JOIN otros.ranchos r ON r.id_rancho = csr.id_rancho
                                  WHERE csr.id_receta = {$data['info']->id_recetas}")->result();

        $data['info']->centroCosto = $this->db->query("SELECT cc.id_centro_costo, cc.nombre, cscc.num
                                  FROM otros.recetas_centro_costo cscc
                                    INNER JOIN otros.centro_costo cc ON cc.id_centro_costo = cscc.id_centro_costo
                                  WHERE cscc.id_receta = {$data['info']->id_recetas}")->result();
      }
    }

    return $data;
  }

  /**
   * Recetas agregar
   *
   * @return array
   */
  public function agregar()
  {
    if ($_POST['empresaId'] !== '')
      $diaComienza = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $_POST['empresaId'])->get()->row()->dia_inicia_semana;
    else
      $diaComienza = '4';
    $ff = explode('-', $_POST['fecha']);
    $semana = MyString::obtenerSemanasDelAnioV2($ff[0], 0, $diaComienza, false, $_POST['fecha']);

    $data = array(
      'id_empresa'      => $_POST['empresaId'],
      'id_formula'      => $_POST['formulaId'],
      'id_realizo'      => $this->session->userdata('id_usuario'),
      'id_solicito'     => $_POST['solicitoId'],
      'id_autorizo'     => $_POST['autorizoId'],
      'id_area'         => $_POST['areaId'],
      'fecha'           => $_POST['fecha'],
      'folio'           => $_POST['folio'],
      'objetivo'        => $_POST['objetivo'],
      'semana'          => $semana['semana'],
      'tipo'            => $_POST['tipo'],

      'dosis_planta'    => floatval($_POST['dosis_planta']),
      'planta_ha'       => floatval($_POST['planta_ha']),
      'ha_neta'         => floatval($_POST['ha_neta']),
      'no_plantas'      => floatval($_POST['no_plantas']),
      'kg_totales'      => floatval($_POST['kg_totales']),
      'ha_bruta'        => floatval($_POST['ha_bruta']),
      'carga1'          => floatval($_POST['carga1']),
      'carga2'          => floatval($_POST['carga2']),
      'ph'              => floatval($_POST['ph']),

      'a_etapa'         => $_POST['a_etapa'],
      'a_ciclo'         => $_POST['a_ciclo'],
      'a_dds'           => $_POST['a_dds'],
      'a_turno'         => $_POST['a_turno'],
      'a_via'           => $_POST['a_via'],
      'a_aplic'         => $_POST['a_aplic'],
      'a_equipo'        => $_POST['a_equipo'],
      'a_observaciones' => $_POST['a_observaciones'],
    );

    $this->db->insert('otros.recetas', $data);
    $recetaId = $this->db->insert_id('otros.recetas_id_recetas_seq');

    $productos = array();
    foreach ($_POST['concepto'] as $key => $concepto)
    {
      $productos[] = array(
        'id_receta'        => $recetaId,
        'id_producto'      => $_POST['productoId'][$key],
        'rows'             => $key,
        'percent'          => $_POST['percent'][$key],
        'dosis_mezcla'     => $_POST['cantidad'][$key],
        'aplicacion_total' => $_POST['aplicacion_total'][$key],
        'precio'           => $_POST['precio'][$key],
        'importe'          => $_POST['importe'][$key],
      );
    }

    if(count($productos) > 0)
      $this->db->insert_batch('otros.recetas_productos', $productos);

    $this->saveCatalogos($recetaId);

    return array('passes' => true, 'msg' => 3);
  }

  /**
   * Recetas modificar
   *
   * @return array
   */
  public function modificar($recetaId)
  {
    if ($_POST['empresaId'] !== '')
      $diaComienza = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $_POST['empresaId'])->get()->row()->dia_inicia_semana;
    else
      $diaComienza = '4';
    $ff = explode('-', $_POST['fecha']);
    $semana = MyString::obtenerSemanasDelAnioV2($ff[0], 0, $diaComienza, false, $_POST['fecha']);

    $data = array(
      'id_empresa'      => $_POST['empresaId'],
      'id_formula'      => $_POST['formulaId'],
      // 'id_realizo'      => $this->session->userdata('id_usuario'),
      'id_solicito'     => $_POST['solicitoId'],
      'id_autorizo'     => $_POST['autorizoId'],
      'id_area'         => $_POST['areaId'],
      'fecha'           => $_POST['fecha'],
      'folio'           => $_POST['folio'],
      'objetivo'        => $_POST['objetivo'],
      'semana'          => $semana['semana'],
      'tipo'            => $_POST['tipo'],

      'dosis_planta'    => floatval($_POST['dosis_planta']),
      'planta_ha'       => floatval($_POST['planta_ha']),
      'ha_neta'         => floatval($_POST['ha_neta']),
      'no_plantas'      => floatval($_POST['no_plantas']),
      'kg_totales'      => floatval($_POST['kg_totales']),
      'ha_bruta'        => floatval($_POST['ha_bruta']),
      'carga1'          => floatval($_POST['carga1']),
      'carga2'          => floatval($_POST['carga2']),
      'ph'              => floatval($_POST['ph']),

      'a_etapa'         => $_POST['a_etapa'],
      'a_ciclo'         => $_POST['a_ciclo'],
      'a_dds'           => $_POST['a_dds'],
      'a_turno'         => $_POST['a_turno'],
      'a_via'           => $_POST['a_via'],
      'a_aplic'         => $_POST['a_aplic'],
      'a_equipo'        => $_POST['a_equipo'],
      'a_observaciones' => $_POST['a_observaciones'],
    );

    $this->db->update('otros.recetas', $data, "id_recetas = {$recetaId}");

    $productos = array();
    foreach ($_POST['concepto'] as $key => $concepto)
    {
      $productos[] = array(
        'id_receta'        => $recetaId,
        'id_producto'      => $_POST['productoId'][$key],
        'rows'             => $key,
        'percent'          => $_POST['percent'][$key],
        'dosis_mezcla'     => $_POST['cantidad'][$key],
        'aplicacion_total' => $_POST['aplicacion_total'][$key],
        'precio'           => $_POST['precio'][$key],
        'importe'          => $_POST['importe'][$key],
      );
    }

    $this->db->delete('otros.recetas_productos', ['id_receta' => $recetaId]);
    if(count($productos) > 0)
      $this->db->insert_batch('otros.recetas_productos', $productos);

    $this->saveCatalogos($recetaId);

    return array('passes' => true, 'msg' => 3);
  }

  private function saveCatalogos($recetaId)
  {
    // Inserta los ranchos
    $this->db->delete('otros.recetas_rancho', ['id_receta' => $recetaId]);
    if (isset($_POST['ranchoId']) && count($_POST['ranchoId']) > 0) {
      foreach ($_POST['ranchoId'] as $keyr => $id_rancho) {
        $this->db->insert('otros.recetas_rancho', [
          'id_rancho' => $id_rancho,
          'id_receta' => $recetaId,
          'num'       => count($_POST['ranchoId'])
        ]);
      }
    }

    // Inserta los centros de costo
    $this->db->delete('otros.recetas_centro_costo', ['id_receta' => $recetaId]);
    if (isset($_POST['centroCostoId']) && count($_POST['centroCostoId']) > 0) {
      foreach ($_POST['centroCostoId'] as $keyr => $id_centro_costo) {
        $this->db->insert('otros.recetas_centro_costo', [
          'id_centro_costo' => $id_centro_costo,
          'id_receta'       => $recetaId,
          'num'             => count($_POST['centroCostoId'])
        ]);
      }
    }
  }

  /**
   * Obtiene el listado de facturas
   *
   * @return
   */
  public function getProductosFaltantes($aplicar=false)
  {
    $sql = '';

    $joins = $fields = '';
    if ($aplicar) {
      $joins = "LEFT JOIN compras_requisicion_centro_costo crc ON cr.id_requisicion = crc.id_requisicion
        LEFT JOIN compras_requisicion_rancho crr ON cr.id_requisicion = crr.id_requisicion";
      $fields = ", (Sum(crq.precio_unitario)/Count(cr.id_requisicion)) AS precio_unitario, Sum(crq.importe) AS importe,
        Sum(crq.iva) AS iva, Sum(crq.retencion_iva) AS retencion_iva, Sum(crq.total) AS total,
        (Sum(crq.porcentaje_iva)/Count(cr.id_requisicion)) AS porcentaje_iva, (Sum(crq.porcentaje_retencion)/Count(cr.id_requisicion)) AS porcentaje_retencion,
        Sum(crq.ieps) AS ieps, (Sum(crq.porcentaje_ieps)/Count(cr.id_requisicion)) AS porcentaje_ieps,
        Sum(crq.retencion_isr) AS retencion_isr, (Sum(crq.porcentaje_isr)/Count(cr.id_requisicion)) AS porcentaje_isr,
        string_agg(crc.id_centro_costo::text, ', ') AS ids_centros_costos, string_agg(crr.id_rancho::text, ', ') AS ids_ranchos,
        string_agg(cr.id_area::text, ', ') AS ids_areas, string_agg(cr.id_activo::text, ', ') AS ids_activos,
        string_agg(cr.solicito, ', ') AS empleado_solicito, (Sum(crq.tipo_cambio)/Count(cr.id_requisicion)) AS tipo_cambio,
        string_agg(cr.otros_datos::text, '&-,-&') AS otros_datos, string_agg(crq.activos::text, '&-,-&') AS activos";
    }

    if($this->input->get('did_empresa') != '')
    {
      // $sql .= " AND cr.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    $res = $this->db->query("SELECT string_agg(cr.id_requisicion::text, ', ') AS ids_requisicion, p.id_producto, cr.id_almacen,
        e.id_empresa, e.nombre_fiscal AS empresa, string_agg(cr.folio::text, ', ') AS folio,
        p.nombre AS producto, string_agg(crq.num_row::text, ', ') AS num_rows,
        pr.id_proveedor, pr.nombre_fiscal AS proveedor, Sum(crq.cantidad) AS cantidad, pu.abreviatura AS unidad
        {$fields}
      FROM compras_requisicion cr
        INNER JOIN compras_requisicion_productos crq ON cr.id_requisicion = crq.id_requisicion
        INNER JOIN productos p ON p.id_producto = crq.id_producto
        INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        INNER JOIN proveedores pr ON pr.id_proveedor = crq.id_proveedor
        INNER JOIN empresas e ON e.id_empresa = cr.id_empresa
        {$joins}
      WHERE cr.status = 'p' AND cr.tipo_orden = 'p' AND cr.autorizado = 'f' AND cr.id_autorizo IS NULL
        AND cr.es_receta = 't' AND crq.importe > 0
        {$sql}
      GROUP BY p.id_producto, cr.id_almacen, e.id_empresa, pr.id_proveedor, pu.id_unidad
      ORDER BY (e.id_empresa, cr.id_almacen, p.id_producto) ASC
    ");

    $productos = array();
    if($res->num_rows() > 0) {
      $this->load->model('inventario_model');
      $data = $res->result();
      foreach ($data as $key => $value) {
        $item = $this->inventario_model->getEPUData($value->id_producto, $value->id_almacen, true, ['empresa' => true]);
        $existencia = MyString::float( $item[0]->saldo_anterior+$item[0]->entradas-$item[0]->salidas-$item[0]->con_req );
        if ( MyString::float($existencia) < 0) {
          $value->faltantes = $existencia * -1;
          $productos[] = $value;
        }
      }
    }

    return $productos;
  }

  public function crearOrdenesFaltantes()
  {
    $this->load->model('compras_ordenes_model');

    $data = $this->getProductosFaltantes(true);

    $ordenes = [];
    $productos = [];
    $dataOrdenCats = [];
    $idReqCerrar = [];

    $keyorden = '';
    $rows_compras = 0;
    foreach ($data as $key => $value)
    {
      $keyaux = $value->id_empresa.$value->id_almacen.$value->id_proveedor;
      if ($keyorden !== $keyaux) {
        $ordenes[$keyaux] = [
          'id_empresa'         => $value->id_empresa,
          'id_proveedor'       => $value->id_proveedor,
          'id_departamento'    => 1, // <-
          'id_empleado'        => $this->session->userdata('id_usuario'),
          'folio'              => $this->compras_ordenes_model->folio('p'),
          'status'             => 'p',
          'autorizado'         => 't',
          'fecha_autorizacion' => date("Y-m-d H:i:s"),
          'fecha_aceptacion'   => date("Y-m-d H:i:s"),
          'fecha_creacion'     => date("Y-m-d H:i:s"),
          'tipo_pago'          => 'cr',
          'tipo_orden'         => 'p',
          'solicito'           => $value->empleado_solicito,
          'id_cliente'         => NULL,
          'descripcion'        => 'Orden creada de productos faltantes para surtir recetas',
          'id_autorizo'        => $this->session->userdata('id_usuario'),
          'id_almacen'         => $value->id_almacen,
          'otros_datos'        => [],
          'es_receta'          => 't'
        ];

        $keyorden = $keyaux;
        $rows_compras = 0;
      }

      $importe   = round($value->faltantes*$value->precio_unitario, 2);
      $iva       = ($importe*$value->porcentaje_iva/100);
      $retencion = ($importe*$value->porcentaje_retencion/100);
      $ieps      = ($importe*$value->porcentaje_ieps/100);
      $isr       = ($importe*$value->porcentaje_isr/100);
      $productos[$keyaux][] = [
        'id_orden'             => '',
        'num_row'              => $rows_compras,
        'id_producto'          => $value->id_producto,
        'id_presentacion'      => null, // <-
        'descripcion'          => $value->producto,
        'cantidad'             => $value->faltantes,
        'precio_unitario'      => $value->precio_unitario,
        'importe'              => $importe,
        'iva'                  => $iva,
        'retencion_iva'        => $retencion,
        'total'                => ($importe+$iva+$ieps-$retencion-$isr),
        'porcentaje_iva'       => $value->porcentaje_iva,
        'porcentaje_retencion' => $value->porcentaje_retencion,
        'faltantes'            => '0',
        'observacion'          => '',
        'ieps'                 => $ieps,
        'porcentaje_ieps'      => $value->porcentaje_ieps,
        'tipo_cambio'          => $value->tipo_cambio,
        // 'id_area'              => $prod->id_area,
        'id_cat_codigos'       => NULL, // <-
        'retencion_isr'        => $isr,
        'porcentaje_isr'       => $value->porcentaje_isr,
      ];

      // inserta las hist reposiciones
      if (isset($value->ids_requisicion) && $value->ids_requisicion != '') {
        $value->ids_requisicion = array_unique(explode(', ', $value->ids_requisicion));
        foreach ($value->ids_requisicion as $keyr => $drequisicion) {
          $dataOrdenCats[$keyaux]['requisiciones'][] = [
            'id_requisicion' => $drequisicion,
            'id_orden'       => '',
            'num_row'        => $keyr
          ];
          $idReqCerrar[] = $drequisicion;
        }
      }

      // Inserta las areas cultivos
      if (isset($value->ids_areas) && $value->ids_areas != '') {
        $value->ids_areas = array_unique(explode(', ', $value->ids_areas));
        foreach ($value->ids_areas as $keyr => $darea) {
          $dataOrdenCats[$keyaux]['area'][] = [
            'id_area'  => $darea,
            'id_orden' => '',
            'num'      => count($value->ids_areas)
          ];
        }
      }

      // Inserta los ranchos
      if (isset($value->ids_ranchos) && $value->ids_ranchos != '') {
        $value->ids_ranchos = array_unique(explode(', ', $value->ids_ranchos));
        foreach ($value->ids_ranchos as $keyr => $drancho) {
          $dataOrdenCats[$keyaux]['rancho'][] = [
            'id_rancho' => $drancho,
            'id_orden'  => '',
            'num'       => count($value->ids_ranchos)
          ];
        }
      }

      // Inserta los centros de costo
      if (isset($value->ids_centros_costos) && $value->ids_centros_costos != '') {
        $value->ids_centros_costos = array_unique(explode(', ', $value->ids_centros_costos));
        foreach ($value->ids_centros_costos as $keyr => $dcentro_costo) {
          $dataOrdenCats[$keyaux]['centroCosto'][] = [
            'id_centro_costo' => $dcentro_costo,
            'id_orden'        => '',
            'num'             => count($value->ids_centros_costos)
          ];
        }
      }

      // Inserta los activos
      if (isset($value->activos) && $value->activos != '') {
        $value->activos = explode('&-,-&', $value->activos);
        foreach ($value->activos as $keyr => $ddatos) {
          $ddatos = json_decode($ddatos);
          foreach ($ddatos as $keyaa => $campo) {
            if (!isset($dataOrdenCats[$keyaux]['activo'][$campo->id])) {
              $dataOrdenCats[$keyaux]['activo'][$campo->id] = [
                'id_activo' => $campo->id,
                'id_orden'  => '',
                'num'       => 1
              ];
            }
          }
        }
      }

      // if (isset($value->ids_activos) && $value->ids_activos != '') {
      //   $value->ids_activos = array_unique(explode(', ', $value->ids_activos));
      //   foreach ($value->ids_activos as $keyr => $dactivo) {
      //     $dataOrdenCats[$keyaux]['activo'][] = [
      //       'id_activo' => $dactivo,
      //       'id_orden'  => '',
      //       'num'       => count($value->ids_activos)
      //     ];
      //   }
      // }

      // Inserta otros datos (pasar a bascula, recoger, etc)
      if (isset($value->otros_datos) && $value->otros_datos != '') {
        $value->otros_datos = explode('&-,-&', $value->otros_datos);
        $auxOtrosDatos = [];
        foreach ($value->otros_datos as $keyr => $ddatos) {
          $ddatos = json_decode($ddatos);
          foreach ($ddatos as $keyaa => $campo) {
            if (!isset($auxOtrosDatos[$keyaa])) {
              $auxOtrosDatos[$keyaa] = $campo;
            }
          }
        }
        $ordenes[$keyaux]['otros_datos'] = json_encode($auxOtrosDatos);
      }

      $rows_compras++;
    }

    foreach ($dataOrdenCats as $keya => $actt) {
      if (isset($actt['activo'])) {
        foreach ($actt['activo'] as $keyy => $value) {
          $dataOrdenCats[$keya]['activo'][$keyy]['num'] = count($actt['activo']);
        }
      }
    }

    // echo "<pre>";
    //   var_dump($ordenes, $dataOrdenCats, $productos);
    // echo "</pre>";exit;

    // creamos las ordenes
    foreach ($ordenes as $key => $orden) {
      $veiculoData = [];
      $ordenCats = isset($dataOrdenCats[$key])? $dataOrdenCats[$key]: NULL;
      $res = $this->compras_ordenes_model->agregarData($orden, $veiculoData, $ordenCats);
      $id_orden = $res['id_orden'];

      $dataProductos = [];
      foreach ($productos[$key] as $keyp => $prod) {
        $prod['id_orden'] = $id_orden;
        $dataProductos[] = $prod;
      }
      $this->compras_ordenes_model->agregarProductosData($dataProductos);
    }

    // Cerramos las requisiciones que se crearon las ordenes
    $this->db->update('compras_requisicion',
      [
        'id_autorizo' => $this->session->userdata('id_usuario'),
        'autorizado'  => 't'
      ],
      "id_requisicion in(".implode(',', $idReqCerrar).")");
  }


  /**
    * Visualiza/Descarga el PDF de la receta.
    *
    * @return void
    */
   public function print_receta($recetaId, $path = null)
   {
      $receta = $this->info($recetaId, true);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('L', 'mm', 'Letter');
      // $pdf->show_head = true;
      $pdf->titulo1 = $receta['info']->empresa;

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
      $pdf->Row(array('Uso del CFDI:', "G03 (Gastos en General)"), false, false);
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
        $widths = array(10, 20, 46, 65, 18, 25, 18, 25, 40);
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
            $prod->descripcion.($prod->observacion!=''? " ({$prod->observacion})": ''),
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
        $pdf->Row(array('EMPRESA', $orden['info'][0]->empresa), false, true);

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

}