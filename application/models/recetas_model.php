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
          f.nombre, f.folio AS folio_formula, r.tipo, r.status, r.fecha
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

}