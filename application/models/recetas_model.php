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
          f.nombre, r.folio, f.folio AS folio_formula, r.tipo, r.status, r.fecha,
          r.total_importe
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
        r.id_formula, f.nombre AS formula, f.folio AS folio_formula, r.tipo, r.ha_bruta, r.carga1, r.carga2, r.ph,
        r.dosis_equipo, r.dosis_equipo_car2, r.total_importe
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
            pr.id_unidad, rp.percent, rp.dosis_mezcla, rp.aplicacion_total, rp.precio, rp.importe,
            rp.dosis_carga1, rp.dosis_carga2
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
      'id_empresa'        => $_POST['empresaId'],
      'id_formula'        => $_POST['formulaId'],
      'id_realizo'        => $this->session->userdata('id_usuario'),
      'id_solicito'       => $_POST['solicitoId'],
      'id_autorizo'       => $_POST['autorizoId'],
      'id_area'           => $_POST['areaId'],
      'fecha'             => $_POST['fecha'],
      'folio'             => $_POST['folio'],
      'objetivo'          => $_POST['objetivo'],
      'semana'            => $semana['semana'],
      'tipo'              => $_POST['tipo'],

      'dosis_planta'      => floatval($_POST['dosis_planta']),
      'planta_ha'         => floatval($_POST['planta_ha']),
      'ha_neta'           => floatval($_POST['ha_neta']),
      'no_plantas'        => floatval($_POST['no_plantas']),
      'kg_totales'        => floatval($_POST['kg_totales']),
      'ha_bruta'          => floatval($_POST['ha_bruta']),
      'carga1'            => floatval($_POST['carga1']),
      'carga2'            => floatval($_POST['carga2']),
      'ph'                => floatval($_POST['ph']),
      'dosis_equipo'      => floatval($_POST['dosis_equipo']),
      'dosis_equipo_car2' => floatval($_POST['dosis_equipo_car2']),

      'a_etapa'           => $_POST['a_etapa'],
      'a_ciclo'           => $_POST['a_ciclo'],
      'a_dds'             => $_POST['a_dds'],
      'a_turno'           => $_POST['a_turno'],
      'a_via'             => $_POST['a_via'],
      'a_aplic'           => $_POST['a_aplic'],
      'a_equipo'          => $_POST['a_equipo'],
      'a_observaciones'   => $_POST['a_observaciones'],

      'total_importe'     => floatval($_POST['total_importe']),
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
        'dosis_carga1'     => floatval($_POST['pcarga1'][$key]),
        'dosis_carga2'     => floatval($_POST['pcarga2'][$key]),
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
      'id_empresa'        => $_POST['empresaId'],
      'id_formula'        => $_POST['formulaId'],
      // 'id_realizo'     => $this->session->userdata('id_usuario'),
      'id_solicito'       => $_POST['solicitoId'],
      'id_autorizo'       => $_POST['autorizoId'],
      'id_area'           => $_POST['areaId'],
      'fecha'             => $_POST['fecha'],
      'folio'             => $_POST['folio'],
      'objetivo'          => $_POST['objetivo'],
      'semana'            => $semana['semana'],
      'tipo'              => $_POST['tipo'],

      'dosis_planta'      => floatval($_POST['dosis_planta']),
      'planta_ha'         => floatval($_POST['planta_ha']),
      'ha_neta'           => floatval($_POST['ha_neta']),
      'no_plantas'        => floatval($_POST['no_plantas']),
      'kg_totales'        => floatval($_POST['kg_totales']),
      'ha_bruta'          => floatval($_POST['ha_bruta']),
      'carga1'            => floatval($_POST['carga1']),
      'carga2'            => floatval($_POST['carga2']),
      'ph'                => floatval($_POST['ph']),
      'dosis_equipo'      => floatval($_POST['dosis_equipo']),
      'dosis_equipo_car2' => floatval($_POST['dosis_equipo_car2']),

      'a_etapa'           => $_POST['a_etapa'],
      'a_ciclo'           => $_POST['a_ciclo'],
      'a_dds'             => $_POST['a_dds'],
      'a_turno'           => $_POST['a_turno'],
      'a_via'             => $_POST['a_via'],
      'a_aplic'           => $_POST['a_aplic'],
      'a_equipo'          => $_POST['a_equipo'],
      'a_observaciones'   => $_POST['a_observaciones'],

      'total_importe'     => floatval($_POST['total_importe']),
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
        'dosis_carga1'     => floatval($_POST['pcarga1'][$key]),
        'dosis_carga2'     => floatval($_POST['pcarga2'][$key]),
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

  public function cancelar($recetaId)
  {
    $data = array('status' => 'f');
    $this->db->update('otros.recetas', $data, "id_recetas = {$recetaId}");

    return array('passes' => true);
  }

  public function activar($recetaId)
  {
    $data = array('status' => 't');
    $this->db->update('otros.recetas', $data, "id_recetas = {$recetaId}");

    return array('passes' => true);
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
      // echo "<pre>";
      // var_dump($receta);
      // echo "</pre>";exit;

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('L', 'mm', 'Letter');
      // $pdf->show_head = true;
      $pdf->titulo1 = $receta['info']->empresa;

      $tipo_orden = 'ALMACENISTA';
      $pdf->logo = $receta['info']->empresaData->logo!=''? (file_exists($receta['info']->empresaData->logo)? $receta['info']->empresaData->logo: '') : '';

      $pdf->AliasNbPages();
      $pdf->AddPage();

      $pdf->SetFillColor(190, 190, 190);

      $pdf->SetXY(6, $pdf->GetY());
      $yaux = $pdf->GetY();
      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(22, 90));
      $pdf->Row(array('EMPRESA', $receta['info']->empresa), false, 'B');
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array('CULTIVO', $receta['info']->area), false, 'B');
      $pdf->SetXY(6, $pdf->GetY());
      $ranchos = [];
      if (count($receta['info']->rancho) > 0) {
        foreach ($receta['info']->rancho as $key => $value) {
          $ranchos[] = $value->nombre;
        }
      }
      $pdf->Row(array('RANCHOS', implode(', ', $ranchos)), false, 'B');
      $pdf->SetXY(6, $pdf->GetY());
      $centros_costo = [];
      if (count($receta['info']->centroCosto) > 0) {
        foreach ($receta['info']->centroCosto as $key => $value) {
          $centros_costo[] = $value->nombre;
        }
      }
      $pdf->Row(array('C COSTO', implode(', ', $centros_costo)), false, 'B');
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array('OBJETIVO', $receta['info']->objetivo), false, 'B');

      if ($receta['info']->tipo === 'kg') {
        // $yaux = $pdf->GetY();
        $pdf->SetXY(120, $yaux);
        $pdf->SetFont('helvetica','B', 8);
        $pdf->SetAligns(array('C', 'C'));
        $pdf->SetWidths(array(35, 35));
        $pdf->Row(array('Dosis Planta', 'Planta x Ha'), false, false);
        $pdf->SetXY(120, $pdf->GetY());
        $pdf->Row(array($receta['info']->dosis_planta, $receta['info']->planta_ha), false, true);
        $pdf->SetXY(120, $pdf->GetY());
        $pdf->Row(array('Ha Neta', 'No Plantas'), false, false);
        $pdf->SetXY(120, $pdf->GetY());
        $pdf->Row(array($receta['info']->ha_neta, $receta['info']->no_plantas), false, true);
        $pdf->SetXY(120, $pdf->GetY());
        $pdf->Row(array('Kg Total', $receta['info']->kg_totales), false, true);
      } else {
      }

      $yaux_datos = $pdf->GetY();

      $pdf->SetXY(192, $yaux);
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetAligns(array('R', 'L'));
      $pdf->SetWidths(array(25, 55));
      $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_txt), array(2,3), array('B', 'B'));
      $pdf->Row2(array('RECETA:', $receta['info']->folio), false, 'B', null, [[0,0,0], [236,0,0]]);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetXY(192, $pdf->GetY());
      $pdf->Row2(array('FORMULA:', $receta['info']->folio_formula), false, 'B', null);
      $pdf->SetXY(192, $pdf->GetY());
      $pdf->Row2(array('FECHA:', MyString::fechaATexto($receta['info']->fecha, '/c')), false, 'B', null);
      $pdf->SetXY(192, $pdf->GetY());
      $pdf->Row2(array('SEMANA:', $receta['info']->semana), false, 'B', null);

      $yaux_sem = $pdf->GetY();

      if ($receta['info']->tipo === 'kg') {
        $tpercent = $tcantidad = $ttaplicacion = $timporte = 0;
        $aligns = array('C', 'L', 'R', 'R', 'R', 'R');
        $widths = array(14, 75, 22, 26, 22, 26);
        $header = array('%', 'PRODUCTO', 'DOSIS MEZCLA', 'A. TOTAL', 'PRECIO', 'IMPORTE');

        $pdf->SetY(($yaux_datos > $yaux_sem? $yaux_datos: $yaux_sem)+5);
        $yaux = $pdf->GetY();
        $page_aux = $pdf->page;
        foreach ($receta['info']->productos as $key => $prod)
        {
          if($pdf->GetY() >= $pdf->limiteY || $key === 0) {
            if($pdf->GetY()+5 >= $pdf->limiteY)
              $pdf->AddPage();
            $pdf->SetFont('Arial','B',7);

            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, true);
          }

          $pdf->SetFont('Arial','',7);
          $pdf->SetTextColor(0,0,0);
          $datos = array(
            "{$prod->percent}%",
            $prod->producto,
            MyString::formatoNumero($prod->dosis_mezcla, 2, '', false),
            MyString::formatoNumero($prod->aplicacion_total, 2, '', false),
            MyString::formatoNumero($prod->precio, 2, '$', false),
            MyString::formatoNumero($prod->importe, 2, '$', false)
          );

          $pdf->SetX(6);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, false);

          $tpercent     += $prod->percent;
          $tcantidad    += $prod->dosis_mezcla;
          $ttaplicacion += $prod->aplicacion_total;
          $timporte     += $prod->importe;
        }

        // Totales
        $pdf->SetFont('Arial','B',7);
        $pdf->SetX(6);
        $pdf->Row([
          "{$tpercent}%",
          $prod->producto,
          MyString::formatoNumero($tcantidad, 2, '', false),
          MyString::formatoNumero($ttaplicacion, 2, '', false),
          '',
          MyString::formatoNumero($timporte, 2, '$', false)
        ], false);
      } else { // lts
      }

      $page_aux2 = $pdf->page;
      $yaux_prod = $pdf->GetY();

      $pdf->page = $page_aux;
      $pdf->SetXY(192, $yaux);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->SetWidths(array(80));
      $pdf->Row(['PROGRAMA DE APLICACION'], true);

      $pdf->SetAligns(array('R', 'L'));
      $pdf->SetWidths(array(20, 60));
      $pdf->SetX(192);
      $pdf->Row(['ETAPA', $receta['info']->a_etapa], false, false);
      $pdf->SetX(192);
      $pdf->Row(['CICLO', $receta['info']->a_ciclo], false, false);
      $pdf->SetX(192);
      $pdf->Row(['DDS', $receta['info']->a_dds], false, false);
      $pdf->SetX(192);
      $pdf->Row(['TURNO', $receta['info']->a_turno], false, false);
      $pdf->SetX(192);
      $pdf->Row(['VIA', $receta['info']->a_via], false, false);
      $pdf->SetX(192);
      $pdf->Row(['APLICACION', $receta['info']->a_aplic], false, false);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->SetWidths(array(80));
      $pdf->SetX(192);
      $pdf->Row(['OBSERVACIONES'], false);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(80));
      $pdf->SetX(192);
      $pdf->Row([$receta['info']->a_observaciones], false, 'B');
      $pdf->Line(192, $yaux, 192, $pdf->GetY());
      $pdf->Line(272, $yaux, 272, $pdf->GetY());

      $pdf->page = $page_aux2;
      $pdf->SetXY(6, $yaux_prod);

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(75));
      $pdf->SetXY(6, $pdf->GetY()+6);
      $pdf->Row(array('SOLICITA: __________________________________________'), false, false);
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->Row(array(strtoupper($receta['info']->solicito)), false, false);

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(75));
      $pdf->SetXY(90, $pdf->GetY()-9);
      $pdf->Row(array('REALIZO: __________________________________________'), false, false);
      $pdf->SetXY(90, $pdf->GetY()-2);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->Row(array(strtoupper($receta['info']->realizo)), false, false);

      if($pdf->GetY()+5 >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(75));
      $pdf->SetXY(6, $pdf->GetY()+6);
      $pdf->Row(array('AUTORIZO: __________________________________________'), false, false);
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->Row(array(strtoupper($receta['info']->autorizo)), false, false);

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(75));
      $pdf->SetXY(90, $pdf->GetY()-9);
      $pdf->Row(array('RECIBIO: __________________________________________'), false, false);
      $pdf->SetXY(90, $pdf->GetY()-2);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->Row(array(''), false, false);

      $pdf->Output('receta'.date('Y-m-d').'.pdf', 'I');
   }

}