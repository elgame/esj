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

    $_GET['ffecha1'] = $this->input->get('ffecha1')? $this->input->get('ffecha1'): date("Y-m")."-01";
    $_GET['ffecha2'] = $this->input->get('ffecha2')? $this->input->get('ffecha2'): date("Y-m-d");
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql .= " AND Date(r.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";

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
          r.total_importe, r.paso, r.fecha_aplicacion
        FROM otros.recetas r
          INNER JOIN areas a ON a.id_area = r.id_area
          LEFT JOIN otros.formulas f ON r.id_formula = f.id_formula
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
        r.dosis_equipo, r.dosis_equipo_car2, r.total_importe, (r.carga1+r.carga2-Coalesce(rs.cargas, 0)) AS saldo_cargas,
        (r.no_plantas-Coalesce(rs.cargas, 0)) AS saldo_plantas, r.fecha_aplicacion, r.id_recetas_calendario,
        r.id_empresa_ap, eap.nombre_fiscal AS empresa_ap, r.folio_hoja
      FROM otros.recetas r
        INNER JOIN areas a ON a.id_area = r.id_area
        INNER JOIN empresas e ON e.id_empresa = r.id_empresa
        INNER JOIN usuarios aut ON aut.id = r.id_autorizo
        INNER JOIN usuarios rea ON rea.id = r.id_realizo
        INNER JOIN usuarios sol ON sol.id = r.id_solicito
        LEFT JOIN empresas eap ON eap.id_empresa = r.id_empresa_ap
        LEFT JOIN otros.formulas f ON f.id_formula = r.id_formula
        LEFT JOIN (
          SELECT id_recetas, Sum(cargas) AS cargas FROM otros.recetas_salidas GROUP BY id_recetas
        ) rs ON r.id_recetas = rs.id_recetas
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
            rp.dosis_carga1, rp.dosis_carga2, rp.aplicacion_total_saldo
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

        $data['info']->centroCosto = $this->db->query("SELECT cc.id_centro_costo, cc.nombre, cc.codigo, cscc.num,
                                    cc.hectareas, cc.no_plantas
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

    $folio = $this->folio($_POST['empresaId'], $_POST['tipo']);

    $data = array(
      'id_empresa'        => $_POST['empresaId'],
      'id_empresa_ap'     => (!empty($_POST['empresaId_ap'])? $_POST['empresaId_ap']: NULL),
      'id_formula'        => (!empty($_POST['formulaId'])? $_POST['formulaId']: NULL),
      'id_realizo'        => $this->session->userdata('id_usuario'),
      'id_solicito'       => $_POST['solicitoId'],
      'id_autorizo'       => $_POST['autorizoId'],
      'id_area'           => $_POST['areaId'],
      'fecha'             => $_POST['fecha'],
      'folio'             => $folio,
      'folio_hoja'        => $_POST['folio_hoja'],
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

      'a_etapa'               => $_POST['a_etapa'],
      'a_ciclo'               => $_POST['a_ciclo'],
      'a_dds'                 => $_POST['a_dds'],
      'a_turno'               => $_POST['a_turno'],
      'a_via'                 => $_POST['a_via'],
      'a_aplic'               => $_POST['a_aplic'],
      'a_equipo'              => $_POST['a_equipo'],
      'a_observaciones'       => $_POST['a_observaciones'],
      'fecha_aplicacion'      => $_POST['fecha_aplicacion'],
      'id_recetas_calendario' => $_POST['calendario'],

      'total_importe'     => floatval($_POST['total_importe']),
    );

    $this->db->insert('otros.recetas', $data);
    $recetaId = $this->db->insert_id('otros.recetas_id_recetas_seq');

    $productos = array();
    foreach ($_POST['concepto'] as $key => $concepto)
    {
      $productos[] = array(
        'id_receta'              => $recetaId,
        'id_producto'            => $_POST['productoId'][$key],
        'rows'                   => $key,
        'percent'                => $_POST['percent'][$key],
        'dosis_mezcla'           => $_POST['cantidad'][$key],
        'aplicacion_total'       => $_POST['aplicacion_total'][$key],
        'precio'                 => $_POST['precio'][$key],
        'importe'                => $_POST['importe'][$key],
        'dosis_carga1'           => floatval($_POST['pcarga1'][$key]),
        'dosis_carga2'           => floatval($_POST['pcarga2'][$key]),
        'aplicacion_total_saldo' => $_POST['aplicacion_total'][$key],
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
      'id_empresa_ap'     => (!empty($_POST['empresaId_ap'])? $_POST['empresaId_ap']: NULL),
      'id_formula'        => (!empty($_POST['formulaId'])? $_POST['formulaId']: NULL),
      // 'id_realizo'     => $this->session->userdata('id_usuario'),
      'id_solicito'       => $_POST['solicitoId'],
      'id_autorizo'       => $_POST['autorizoId'],
      'id_area'           => $_POST['areaId'],
      'fecha'             => $_POST['fecha'],
      'folio'             => $_POST['folio'],
      'folio_hoja'        => $_POST['folio_hoja'],
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

      'a_etapa'               => $_POST['a_etapa'],
      'a_ciclo'               => $_POST['a_ciclo'],
      'a_dds'                 => $_POST['a_dds'],
      'a_turno'               => $_POST['a_turno'],
      'a_via'                 => $_POST['a_via'],
      'a_aplic'               => $_POST['a_aplic'],
      'a_equipo'              => $_POST['a_equipo'],
      'a_observaciones'       => $_POST['a_observaciones'],
      'fecha_aplicacion'      => $_POST['fecha_aplicacion'],
      'id_recetas_calendario' => $_POST['calendario'],

      'total_importe'     => floatval($_POST['total_importe']),
    );

    $this->db->update('otros.recetas', $data, "id_recetas = {$recetaId}");

    $productos = array();
    foreach ($_POST['concepto'] as $key => $concepto)
    {
      $productos[] = array(
        'id_receta'              => $recetaId,
        'id_producto'            => $_POST['productoId'][$key],
        'rows'                   => $key,
        'percent'                => $_POST['percent'][$key],
        'dosis_mezcla'           => $_POST['cantidad'][$key],
        'aplicacion_total'       => $_POST['aplicacion_total'][$key],
        'precio'                 => $_POST['precio'][$key],
        'importe'                => $_POST['importe'][$key],
        'dosis_carga1'           => floatval($_POST['pcarga1'][$key]),
        'dosis_carga2'           => floatval($_POST['pcarga2'][$key]),
        'aplicacion_total_saldo' => $_POST['aplicacion_total'][$key],
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
   * Agregar salida de productos a receta
   *
   * @return array
   */
  public function salida($recetaId)
  {
    $this->load->model('productos_salidas_model');

    $receta = $this->info($recetaId, true);
    // echo "<pre>";
    //   var_dump($_POST, $receta);
    // echo "</pre>";exit;

    // validamos las existencias de los productos
    $id_almacen = $_POST['almacenId'];
    $productos = [];
    foreach ($_POST['productoId'] as $key => $id) {
      $productos[] = [
        'id'       => $id,
        'cantidad' => $_POST['cantidad'][$key],
      ];
    }
    $res = $this->productos_salidas_model->validaProductosExistencia($id_almacen, $productos, ['empresa' => $_POST['empresaId'], 'con_req' => false] );
    if (!$res['passes']) {
      return $res;
    }

    // Creamos la salida de producto
    $fecha      = date("Y-m-d");
    $next_folio = $this->productos_salidas_model->folio();
    $res = $this->productos_salidas_model->agregar(array(
        'id_empresa'        => $receta['info']->id_empresa,
        'id_almacen'        => $id_almacen,
        'id_empleado'       => $this->session->userdata('id_usuario'),
        'folio'             => $next_folio,
        'concepto'          => 'Salida aut. de recetas',
        'status'            => 's',
        'fecha_creacion'    => $fecha,
        'fecha_registro'    => $fecha,

        'solicito'          => $receta['info']->solicito,
        'recibio'           => $receta['info']->autorizo,
        'no_receta'         => $receta['info']->folio,
        'id_area'           => $receta['info']->id_area,
        'id_empresa_ap'     => $receta['info']->id_empresa_ap,

        'tipo'              => 'r',

        'etapa'             => $receta['info']->a_etapa,
        'ciclo'             => $receta['info']->a_ciclo,
        'dias_despues_de'   => floatval($receta['info']->a_dds),
        'metodo_aplicacion' => $receta['info']->a_aplic,
        'tipo_aplicacion'   => $receta['info']->a_via,
        'grupo'             => $receta['info']->a_equipo,
        'observaciones'     => $receta['info']->a_observaciones,
        'turno'             => $receta['info']->a_turno,
        'fecha_aplicacion'  => $receta['info']->fecha_aplicacion,
      ));
    $id_salida = $res['id_salida'];

    // Se agregan los ranchos
    if (count($receta['info']->rancho) > 0) {
      $ranchos = [];
      foreach ($receta['info']->rancho as $key => $value) {
        $ranchos[] = $value->id_rancho;
      }
      $this->productos_salidas_model->agregarRanchos($id_salida, $ranchos);
    }

    // Se agregan los centros de costo
    if (count($receta['info']->centroCosto) > 0) {
      $centros = [];
      foreach ($receta['info']->centroCosto as $key => $value) {
        $centros[] = $value->id_centro_costo;
      }
      $this->productos_salidas_model->agregarCentrosCostos($id_salida, $centros);
    }

    // Se agregan los productos a la salida
    $productos = [];
    foreach ($_POST['productoId'] as $key => $id) {
      $productos[] = [
        'id_salida'       => $id_salida,
        'id_producto'     => $id,
        'no_row'          => $key,
        'cantidad'        => $_POST['cantidad'][$key],
        'precio_unitario' => $_POST['precio'][$key],
      ];
    }
    $this->productos_salidas_model->agregarProductos($id_salida, $productos);


    $is_lts = ($_POST['tipo']==='lts');
    // Creamos la salida de receta y resta productos
    $this->db->insert('otros.recetas_salidas', [
      'id_recetas' => $receta['info']->id_recetas,
      'id_salida'  => $id_salida,
      'cargas'     => ($is_lts? $_POST['carga_salida']: $_POST['plantas_salida']),
      'id_bascula' => ($this->input->post('boletasSalidasId')? $_POST['boletasSalidasId']: NULL),
    ]);
    foreach ($_POST['productoId'] as $key => $id) {
      $result = $this->db->query("UPDATE otros.recetas_productos
        SET aplicacion_total_saldo = aplicacion_total_saldo - {$_POST['cantidad'][$key]}
        WHERE id_receta = {$receta['info']->id_recetas} AND id_producto = {$id} AND rows = {$_POST['rows'][$key]}");
    }

    $entregado_todo = $this->db->query("SELECT Sum(aplicacion_total_saldo) AS saldo
      FROM otros.recetas_productos
      WHERE id_receta = {$receta['info']->id_recetas}")->row();

    $this->db->update('otros.recetas', ['paso' => ((isset($entregado_todo->saldo) && number_format($entregado_todo->saldo, 2) <= 0)? 't': 'r')], "id_recetas = {$receta['info']->id_recetas}");

    return array('passes' => true, 'msg' => 3);
  }

  public function getSalidas($recetaId)
  {
    $sql = '';

    $res = $this->db->query("SELECT cs.id_salida, Date(cs.fecha_creacion) AS fecha_creacion, Date(cs.fecha_registro) AS fecha_registro,
        cs.folio, cs.concepto, rs.cargas, cs.solicito, cs.recibio, rs.id_recetas
      FROM public.compras_salidas cs
        INNER JOIN otros.recetas_salidas rs ON cs.id_salida = rs.id_salida
      WHERE cs.status = 's' AND rs.id_recetas = {$recetaId}
    ");

    $salidas = [];
    if($res->num_rows() > 0)
      $salidas = $res->result();

    return $salidas;
  }



  public function getSurtirRecetas()
  {
    $sql = '';

    $_GET['ffecha1'] = $this->input->get('ffecha1')? $this->input->get('ffecha1'): date("Y-m")."-01";
    $_GET['ffecha2'] = $this->input->get('ffecha2')? $this->input->get('ffecha2'): date("Y-m-d");
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql .= " AND Date(r.fecha_aplicacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";

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

    $res = $this->db->query(
      "SELECT r.id_recetas, rp.rows, r.id_formula, r.id_empresa, r.id_area, a.nombre AS area,
        f.nombre, r.folio, r.folio_hoja, f.folio AS folio_formula, r.tipo, r.status, r.fecha,
        rp.importe, r.paso, r.fecha_aplicacion, pr.id_producto, pr.nombre AS producto,
        p.id_proveedor, p.nombre_fiscal AS proveedor, rp.aplicacion_total, rp.precio, rp.surtir,
        r.id_empresa_ap, rp.quitar
      FROM otros.recetas_productos rp
        INNER JOIN productos pr ON pr.id_producto = rp.id_producto
        INNER JOIN otros.recetas r ON r.id_recetas = rp.id_receta
        INNER JOIN areas a ON a.id_area = r.id_area
        LEFT JOIN otros.formulas f ON r.id_formula = f.id_formula
        LEFT JOIN proveedores p ON p.id_proveedor = rp.id_proveedor
      WHERE r.status = 't' AND rp.id_requisicion IS NULL AND rp.quitar = 'f' {$sql}
      ORDER BY r.folio DESC, p.id_proveedor ASC, pr.nombre ASC
      ");

    $response = $res->result();

    return $response;
  }

  public function guardarSurtirReceta()
  {
    if (isset($_POST['id_proveedor']) && count($_POST['id_proveedor']) > 0) {
      foreach ($_POST['id_proveedor'] as $key => $id_proveedor) {
        $id_proveedor = ($id_proveedor > 0? $id_proveedor : null);

        $this->db->update('otros.recetas_productos', [
          'id_proveedor' => $id_proveedor,
          'surtir'       => $_POST['aplicar'][$key],
          'quitar'       => $_POST['quitar'][$key],
        ], [
          'id_receta'   => $_POST['id_receta'][$key],
          'id_producto' => $_POST['id_producto'][$key],
          'rows'        => $_POST['rows'][$key],
        ]);
      }
    }
  }

  public function crearRequisiciones()
  {
    $this->load->model('compras_requisicion_model');
    $this->guardarSurtirReceta();

    $productos_recetas = $this->getSurtirRecetas();
    $productos_recetasg = [];
    $auxarea = '';
    foreach ($productos_recetas as $key => $value) {
      if ($value->surtir == 't') {
        $productos_recetasg["{$value->id_empresa}.{$value->id_proveedor}.{$value->id_area}"][] = $value;
      }
    }

    $requisiciones = [];
    $requisiciones_productos = [];
    $requisiciones_ranchos = [];
    $requisiciones_centros = [];
    $auxid = '';
    foreach ($productos_recetasg as $key => $item) {
      $requisiciones[$key] = [
        'id_empresa'      => '',
        'id_departamento' => 24,
        'id_empleado'     => $this->session->userdata('id_usuario'),
        'folio'           => '',
        'fecha_creacion'  => date("Y-m-d H:i:s"),
        'tipo_pago'       => 'cr',
        'tipo_orden'      => 'p',
        'solicito'        => '',
        'id_cliente'      => NULL,
        'descripcion'     => '',
        'id_almacen'      => (is_numeric($_POST['id_almacen'])? $_POST['id_almacen']: NULL),
        'descripcion'     => 'Requisición creada del modulo de recetas',

        'id_area'         => '',
        'es_receta'       => 't',
        'id_empresa_ap'   => ''
      ];

      // Si trae datos extras
      $requisiciones[$key]['otros_datos']['noRecetas'] = [];

      foreach ($item as $key2 => $item2) {
        $requisiciones[$key]['id_empresa']    = $item2->id_empresa;
        $requisiciones[$key]['id_area']       = $item2->id_area;
        $requisiciones[$key]['id_empresa_ap'] = (isset($item2->id_empresa_ap)? $item2->id_empresa_ap: NULL);

        $receta = $this->info($item2->id_recetas, true);
        $requisiciones[$key]['solicito'] = $receta['info']->solicito;

        if (!in_array("{$item2->folio}/{$item2->folio_hoja}", $requisiciones[$key]['otros_datos']['noRecetas'])) {
          $requisiciones[$key]['otros_datos']['noRecetas'][] = "{$item2->folio}/{$item2->folio_hoja}";
        }

        if ($item2->id_proveedor) {
          $requisiciones_productos[$key][] = [
            'id_requisicion'       => '',
            'id_proveedor'         => $item2->id_proveedor,
            'num_row'              => $key2,
            'id_producto'          => $item2->id_producto,
            'id_presentacion'      => null,
            'descripcion'          => $item2->producto,
            'cantidad'             => $item2->aplicacion_total,
            'precio_unitario'      => $item2->precio,
            'importe'              => $item2->importe,
            'iva'                  => 0,
            'retencion_iva'        => 0,
            'total'                => $item2->importe,
            'porcentaje_iva'       => 0,
            'porcentaje_retencion' => 0,
            'observacion'          => '',
            'ieps'                 => 0,
            'porcentaje_ieps'      => 0,
            'tipo_cambio'          => 0,
            'id_cat_codigos'       => null,
            'retencion_isr'        => 0,
            'porcentaje_isr'       => 0,
            'activos'              => NULL,

            'prodSurtir' => [
              [
                'id_receta'   => $item2->id_recetas,
                'id_producto' => $item2->id_producto,
                'rows'        => $item2->rows
              ]
            ]
          ];
        }

        if (isset($receta['info']->rancho) && count($receta['info']->rancho)> 0) {
          foreach ($receta['info']->rancho as $keyR => $rancho) {
            if (!isset($requisiciones_ranchos[$key])) {
              $requisiciones_ranchos[$key] = [];
            }

            if (!in_array($rancho->id_rancho, $requisiciones_ranchos[$key])) {
              $requisiciones_ranchos[$key][] = $rancho->id_rancho;
            }
          }
        }

        if (isset($receta['info']->centroCosto) && count($receta['info']->centroCosto) > 0) {
          foreach ($receta['info']->centroCosto as $keyR => $centroCosto) {
            if (!isset($requisiciones_centros[$key])) {
              $requisiciones_centros[$key] = [];
            }

            if (!in_array($centroCosto->id_centro_costo, $requisiciones_centros[$key])) {
              $requisiciones_centros[$key][] = $centroCosto->id_centro_costo;
            }
          }
        }

      }
      $requisiciones[$key]['otros_datos'] = json_encode($requisiciones[$key]['otros_datos']);
    }

    // Unir productos repetidos
    $requisiciones_productos2 = [];
    foreach ($requisiciones_productos as $key => $reqs) {
      foreach ($reqs as $key2 => $prod) {
        if (!isset($requisiciones_productos2[$key][$prod['id_producto']])) {
          $requisiciones_productos2[$key][$prod['id_producto']] = $prod;
        } else {
          $requisiciones_productos2[$key][$prod['id_producto']]['cantidad'] += $prod['cantidad'];
          $requisiciones_productos2[$key][$prod['id_producto']]['importe'] += $prod['importe'];
          $requisiciones_productos2[$key][$prod['id_producto']]['total'] += $prod['total'];
          $requisiciones_productos2[$key][$prod['id_producto']]['prodSurtir'][] = $prod['prodSurtir'][0];
        }
      }
    }

    // echo "<pre>";
    // var_dump($requisiciones, $requisiciones_productos, $requisiciones_productos2);
    // echo "</pre>";exit;

    // Agrega las ordenes de requisición
    $response = $this->compras_requisicion_model->agregarData($requisiciones, $requisiciones_productos2, $requisiciones_ranchos, $requisiciones_centros);

    // Se actualizan los productos de las recetas con el id de la requisición
    if ($response['passes']) {
      foreach ($response['productosReq'] as $id_requisicion => $ordenes) {
        foreach ($ordenes as $keyp => $product) {
          $this->db->update('otros.recetas_productos', ['id_requisicion' => $id_requisicion], $product);
        }
      }
    }

    return $response;

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


  public function importRecetasCorona()
  {
    $config['upload_path'] = APPPATH.'media/temp/';
    $config['allowed_types'] = '*';
    $config['max_size'] = '2000';

    $this->load->library('upload', $config);

    if ( ! $this->upload->do_upload('archivo_recetas'))
    {
      return array('error' => '501');
    }
    else
    {
      $file = $this->upload->data();
      $recetasData = [];
      $val_resumenok = [];

      $handle = fopen($file['full_path'], "r");
      if ($handle) {
        $this->load->model('usuarios_model');

        if ($_POST['id_empresa'] !== '')
          $diaComienza = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $_POST['id_empresa'])->get()->row()->dia_inicia_semana;
        else
          $diaComienza = '4';
        $ff = explode('-', $_POST['fecha']);
        $semana = MyString::obtenerSemanasDelAnioV2($ff[0], 0, $diaComienza, false, $_POST['fecha']);

        // Construcción de los datos de las recetas
        while (($line = fgets($handle)) !== false) {
          if (trim($line) != '') {
            $datos = str_getcsv($line);
            $datos = $this->clearRowRecetaCorona($datos);

            if ($datos[0] == 'H') { // Cabecera
              $cargas = explode('.', $datos[10]);

              $recetasData[] = [
                'id_empresa'       => $datos[1],
                'id_empresa_ap'    => $datos[2],
                'folio_hoja'       => $datos[3],
                'tipo'             => 'lts',
                'fecha'            => MyString::fechaFormat($datos[4]),
                'fecha_aplicacion' => MyString::fechaFormat($datos[5]),
                'id_area'          => $datos[6],
                'id_rancho'        => $datos[7],
                'centros_costo'    => $datos[8],
                'dosis_planta'     => 0,
                'planta_ha'        => ($datos[11]/($datos[9]>0? $datos[9]: 1)),
                'ha_neta'          => $datos[9],
                'no_plantas'       => $datos[11],
                'kg_totales'       => 0,
                'ha_bruta'         => $datos[9],
                'cargas'           => $datos[10],
                'carga1'           => $cargas[0],
                'carga2'           => (isset($cargas[1])? "0.{$cargas[1]}": 0),
                'total_importe'    => 0,
                'semana'           => $semana['semana'],
                'calendario'       => NULL,
                'productos'        => []
              ];
            } else {
              $rowh = $recetasData[count($recetasData)-1];
              $cantidad_carga = ($datos[2]/($rowh['cargas']>0? $rowh['cargas']: 1));
              $recetasData[count($recetasData)-1]['productos'][] = [
                'id_producto'            => $datos[1],
                'dosis_mezcla'           => round($cantidad_carga, 6), // cantidad
                'aplicacion_total'       => $datos[2],
                'precio'                 => 0,
                'importe'                => 0,
                'dosis_carga1'           => round($cantidad_carga, 6),
                'dosis_carga2'           => round($cantidad_carga*$rowh['carga2'], 6),
                'aplicacion_total_saldo' => $datos[2],
              ];
            }
          }
        }
        fclose($handle);

        // Validación
        $val_resumen = [];
        if (count($recetasData) > 0) {
          foreach ($recetasData as $key => $receta) {
            $this->validaRecetaCorona($recetasData[$key], $val_resumen);
          }
        }

        if (count($val_resumen) > 0) {
          return ['error' => '503', 'resumen' => $val_resumen];
        } else {
          // Se guardan todas las recetas
          foreach ($recetasData as $key => $receta) {
            // Valida si ya existe la receta no se agrega
            $res = $this->db->query("SELECT id_recetas FROM otros.recetas WHERE folio_hoja = '{$receta['folio_hoja']}' AND id_empresa = {$receta['id_empresa']} AND id_empresa_ap = {$receta['id_empresa_ap']} AND id_area = {$receta['id_area']} AND status = 't'")->row();
            if (!isset($res->id_recetas)) {
              $this->saveRecetaData($receta);
            } else {
              $val_resumenok[] = "Receta No {$receta['folio_hoja']} ya esta registrada en la empresa y cultivo seleccionados.";
            }
          }
        }

      } else {
        return array('error' => '502');
      }

      return array('error' => '500', 'resumenok' => $val_resumenok);
    }
  }

  private function validaRecetaCorona(&$receta, &$val_resumen)
  {
    $areas = [3 => 21];
    if ($receta['id_empresa'] != $this->input->post('id_empresa')) {
      $val_resumen[] = "Receta No {$receta['folio_hoja']}; La empresa seleccionada no coincide con la del archivo.";
      return false;
    } elseif ($receta['id_area'] != $this->input->post('id_area')) {
      $val_resumen[] = "Receta No {$receta['folio_hoja']}; El cultivo seleccionada no coincide con el del archivo.";
      return false;
    } elseif (!isset($areas[$receta['id_area']]) || $areas[$receta['id_area']] != $receta['id_empresa_ap']) {
      $val_resumen[] = "Receta No {$receta['folio_hoja']}; La empresa de aplicación no coincide con el cultivo.";
      return false;
    } else {
      $res = $this->db->query("SELECT id_rancho FROM otros.ranchos WHERE codigo = '{$receta['id_rancho']}' AND id_empresa = {$receta['id_empresa_ap']} AND id_area = {$receta['id_area']} AND status = 't'")->row();
      if (!isset($res->id_rancho) ) {
        $val_resumen[] = "Receta No {$receta['folio_hoja']}; El rancho '{$receta['id_rancho']}' no existe en la empresa de aplicación y cultivo seleccionados.";
        return false;
      } else {
        $receta['id_rancho'] = $res->id_rancho;

        $acentrosc = explode(',', $receta['centros_costo']);
        if (count($acentrosc) > 0) {
          $centros_costos = [];
          $centros_costos_e = [];
          foreach ($acentrosc as $key => $cc) {
            $res = $this->db->query("SELECT id_centro_costo FROM otros.centro_costo WHERE codigo = '{$cc}' AND id_area = {$receta['id_area']} AND status = 't'")->row();
            if (isset($res->id_centro_costo)) {
              $centros_costos[] = $res->id_centro_costo;
            } else {
              $centros_costos_e[] = $cc;
            }
          }

          if (count($centros_costos_e) > 0) {
            $centros_costos_e = implode(', ', $centros_costos_e);
            $val_resumen[] = "Receta No {$receta['folio_hoja']}; Los centros de costo '{$centros_costos_e}' no existen en el cultivo seleccionado.";
            return false;
          }
          $receta['centros_costo'] = $centros_costos;


          if (count($receta['productos']) > 0) {
            $productos_e = [];
            foreach ($receta['productos'] as $key => $pp) {
              if (is_numeric($pp['id_producto'])) {
                $res = $this->db->query("SELECT id_producto, last_precio FROM productos WHERE id_producto = '{$pp['id_producto']}' AND status = 'ac'")->row();
                if (isset($res->id_producto)) {
                  $receta['productos'][$key]['precio'] = $res->last_precio;
                  $receta['productos'][$key]['importe'] = round($res->last_precio * $receta['productos'][$key]['aplicacion_total'], 2);
                  $receta['kg_totales'] += $receta['productos'][$key]['aplicacion_total'];
                  $receta['total_importe'] += $receta['productos'][$key]['importe'];
                } else {
                  $productos_e[] = $pp['id_producto'];
                }
              } else {
                $productos_e[] = $pp['id_producto'];
              }
            }
            $receta['dosis_planta'] = round($receta['kg_totales']/($receta['no_plantas']>0? $receta['no_plantas']: 1), 6);

            if (count($productos_e) > 0) {
              $productos_e = implode(', ', $productos_e);
              $val_resumen[] = "Receta No {$receta['folio_hoja']}; Los productos '{$productos_e}' no existen en el catalogo de la empresa.";
              return false;
            } else {

              $calendarios = $this->getCalendariosAjax($receta['id_area']);
              if (count($calendarios) == 0) {
                $val_resumen[] = "Receta No {$receta['folio_hoja']}; El cultivo no cuenta con un calendario asignado.";
                return false;
              } else {
                $receta['calendario'] = $calendarios[0]->id;
              }
            }

          } else {
            $val_resumen[] = "Receta No {$receta['folio_hoja']}; No tiene productos asignados.";
            return false;
          }

        } else {
          $val_resumen[] = "Receta No {$receta['folio_hoja']}; No tiene centros de costo asignados.";
          return false;
        }
      }
    }

    return true;
  }

  private function clearRowRecetaCorona($data)
  {
    foreach ($data as $key => $item) {
      $data[$key] = trim($item);
    }

    return $data;
  }

  /**
   * Recetas agregar
   *
   * @return array
   */
  public function saveRecetaData($receta)
  {
    $folio = $this->folio($receta['id_empresa'], $receta['tipo']);

    $data = array(
      'id_empresa'            => $receta['id_empresa'],
      'id_empresa_ap'         => $receta['id_empresa_ap'],
      'id_formula'            => NULL,
      'id_realizo'            => $this->session->userdata('id_usuario'),
      'id_solicito'           => 14, // sr vianey
      'id_autorizo'           => 14,
      'id_area'               => $receta['id_area'],
      'fecha'                 => $receta['fecha'],
      'folio'                 => $folio,
      'folio_hoja'            => $receta['folio_hoja'],
      'objetivo'              => 'Receta cargada automáticamente del sistema de Corona',
      'semana'                => $receta['semana'],
      'tipo'                  => $receta['tipo'],

      'dosis_planta'          => floatval($receta['dosis_planta']),
      'planta_ha'             => floatval($receta['planta_ha']),
      'ha_neta'               => floatval($receta['ha_neta']),
      'no_plantas'            => floatval($receta['no_plantas']),
      'kg_totales'            => floatval($receta['kg_totales']),
      'ha_bruta'              => floatval($receta['ha_bruta']),
      'carga1'                => floatval($receta['carga1']),
      'carga2'                => floatval($receta['carga2']),
      'ph'                    => floatval(0),
      'dosis_equipo'          => floatval(0),
      'dosis_equipo_car2'     => floatval(0),

      'a_etapa'               => '',
      'a_ciclo'               => '',
      'a_dds'                 => '',
      'a_turno'               => '',
      'a_via'                 => '',
      'a_aplic'               => '',
      'a_equipo'              => '',
      'a_observaciones'       => '',
      'fecha_aplicacion'      => $receta['fecha_aplicacion'],
      'id_recetas_calendario' => $receta['calendario'],

      'total_importe'         => floatval($receta['total_importe']),
    );

    $this->db->insert('otros.recetas', $data);
    $recetaId = $this->db->insert_id('otros.recetas_id_recetas_seq');

    $productos = array();
    foreach ($receta['productos'] as $key => $producto)
    {
      $productos[] = array(
        'id_receta'              => $recetaId,
        'id_producto'            => $producto['id_producto'],
        'rows'                   => $key,
        'percent'                => round($producto['aplicacion_total']*100/$receta['kg_totales'], 2),
        'dosis_mezcla'           => $producto['dosis_mezcla'],
        'aplicacion_total'       => $producto['aplicacion_total'],
        'precio'                 => $producto['precio'],
        'importe'                => $producto['importe'],
        'dosis_carga1'           => floatval($producto['dosis_carga1']),
        'dosis_carga2'           => floatval($producto['dosis_carga2']),
        'aplicacion_total_saldo' => $producto['aplicacion_total_saldo'],
      );
    }

    if(count($productos) > 0)
      $this->db->insert_batch('otros.recetas_productos', $productos);

    $this->saveCatalogosData($recetaId, $receta);

    return array('passes' => true, 'msg' => 3);
  }

  private function saveCatalogosData($recetaId, $receta)
  {
    // Inserta los ranchos
    $this->db->delete('otros.recetas_rancho', ['id_receta' => $recetaId]);
    if (isset($receta['id_rancho'])) {
      $this->db->insert('otros.recetas_rancho', [
        'id_rancho' => $receta['id_rancho'],
        'id_receta' => $recetaId,
        'num'       => 1
      ]);
    }

    // Inserta los centros de costo
    $this->db->delete('otros.recetas_centro_costo', ['id_receta' => $recetaId]);
    if (isset($receta['centros_costo']) && count($receta['centros_costo']) > 0) {
      foreach ($receta['centros_costo'] as $keyr => $id_centro_costo) {
        $this->db->insert('otros.recetas_centro_costo', [
          'id_centro_costo' => $id_centro_costo,
          'id_receta'       => $recetaId,
          'num'             => count($receta['centros_costo'])
        ]);
      }
    }
  }


  /**
    * Visualiza/Descarga el PDF de la receta.
    *
    * @return void
    */
   public function print_receta($recetaId, $pdf = null, $rep = 0)
   {
      $receta = $this->info($recetaId, true);
      // echo "<pre>";
      // var_dump($receta);
      // echo "</pre>";exit;

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      if (is_null($pdf)) {
        $pdf = new MYpdf('P', 'mm', 'Letter');
        $pdf->titulo2 = 'ADMINISTRADOR';
        $rep++;
      } elseif ($rep === 1) {
        $pdf->titulo2 = 'ALMACENISTA';
        $rep++;
      } else {
        $pdf->titulo2 = 'APLICADOR';
      }
      // $pdf->show_head = true;
      $pdf->titulo1 = $receta['info']->empresa;

      $pdf->logo = $receta['info']->empresaData->logo!=''? (file_exists($receta['info']->empresaData->logo)? $receta['info']->empresaData->logo: '') : '';

      if (is_null($pdf->GetY()) || $pdf->GetY()+12 >= ($pdf->limiteY/2)) {
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetXY(6, $pdf->GetY()-8);
      } else {
        $pdf->SetY($pdf->GetY()+12);
        if($pdf->logo != '')
          $pdf->Image(APPPATH.(str_replace(APPPATH, '', $pdf->logo)), 6, $pdf->GetY(), 20);
        $pdf->headerLetterP([$pdf->GetY()+1, $pdf->GetY(), $pdf->GetY()+3]);
        $pdf->SetY($pdf->GetY()+12);
      }
      $pdf->SetFillColor(190, 190, 190);

      $pdf->SetXY(6, $pdf->GetY());
      $yaux = $pdf->GetY();
      $pdf->SetFont('helvetica','B', 6.5);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(15, 95));
      $pdf->Row(array('EMPRESA', $receta['info']->empresa_ap), false, 'B');
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
          $centros_costo[] = $value->codigo;
        }
      }
      $pdf->Row(array('C COSTO', implode(', ', $centros_costo)), false, 'B');
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array('OBJETIVO', $receta['info']->objetivo), false, 'B');

      if ($receta['info']->tipo === 'kg') {
        // $yaux = $pdf->GetY();
        $pdf->SetXY(118, $yaux);
        $pdf->SetFont('helvetica','B', 6.5);
        $pdf->SetAligns(array('C', 'C'));
        $pdf->SetWidths(array(20, 20));
        $pdf->Row(array('Dosis Planta', 'Planta x Ha'), false, false);
        $pdf->SetXY(118, $pdf->GetY());
        $pdf->Row(array($receta['info']->dosis_planta, $receta['info']->planta_ha), false, true);
        $pdf->SetXY(118, $pdf->GetY());
        $pdf->Row(array('Ha Neta', 'No Plantas'), false, false);
        $pdf->SetXY(118, $pdf->GetY());
        $pdf->Row(array($receta['info']->ha_neta, $receta['info']->no_plantas), false, true);
        $pdf->SetXY(118, $pdf->GetY());
        $pdf->Row(array('Kg Total', $receta['info']->kg_totales), false, true);
      } else { // lts
        $pdf->SetXY(118, $yaux);
        $pdf->SetFont('helvetica','B', 6.5);
        $pdf->SetAligns(array('C', 'C'));
        $pdf->SetWidths(array(20, 20));
        $pdf->Row(array('Ha Bruta', 'Planta x Ha'), false, false);
        $pdf->SetXY(118, $pdf->GetY());
        $pdf->Row(array($receta['info']->ha_bruta, $receta['info']->planta_ha), false, true);
        $pdf->SetXY(118, $pdf->GetY());
        $pdf->Row(array('Ha Neta', 'No Plantas'), false, false);
        $pdf->SetXY(118, $pdf->GetY());
        $pdf->Row(array($receta['info']->ha_neta, $receta['info']->no_plantas), false, true);
        $pdf->SetXY(118, $pdf->GetY());
        $pdf->Row(array('PH', $receta['info']->ph), false, true);
      }

      $yaux_datos = $pdf->GetY();

      $pdf->SetXY(160, $yaux);
      $pdf->SetFont('helvetica','B', 6);
      $pdf->SetAligns(array('R', 'L'));
      $pdf->SetWidths(array(18, 35));
      $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_txt), array(-1, 0), array('B', 'B'));
      $pdf->Row2(array('RECETA:', $receta['info']->folio.'/'.$receta['info']->folio_hoja ), false, 'B', null, [[0,0,0], [236,0,0]]);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetXY(160, $pdf->GetY());
      $pdf->Row2(array('FORMULA:', $receta['info']->folio_formula), false, 'B', null);
      $pdf->SetXY(160, $pdf->GetY());
      $pdf->Row2(array('FECHA:', MyString::fechaATexto($receta['info']->fecha, '/c')), false, 'B', null);
      $pdf->SetXY(160, $pdf->GetY());
      $pdf->Row2(array('SEMANA:', $receta['info']->semana), false, 'B', null);

      $yaux_sem = $pdf->GetY();

      if ($receta['info']->tipo === 'kg') {
        $tpercent = $tcantidad = $ttaplicacion = $timporte = 0;
        $aligns = array('C', 'L', 'R', 'R', 'R', 'R');
        if ($pdf->titulo2 === 'ALMACENISTA' || $pdf->titulo2 === 'ADMINISTRADOR') {
          $widths = array(10, 62, 18, 22, 18, 22);
          $header = array('%', 'PRODUCTO', 'DOSIS MEZCLA', 'A. TOTAL', 'PRECIO', 'IMPORTE');
        } else {
          $widths = array(10, 62, 18, 22);
          $header = array('%', 'PRODUCTO', 'DOSIS MEZCLA', 'A. TOTAL');
        }

        $pdf->SetY(($yaux_datos > $yaux_sem? $yaux_datos: $yaux_sem)+3);
        $yaux = $pdf->GetY();
        $page_aux = $pdf->page;
        foreach ($receta['info']->productos as $key => $prod)
        {
          if($pdf->GetY() >= $pdf->limiteY || $key === 0) {
            if($pdf->GetY()+5 >= $pdf->limiteY)
              $pdf->AddPage();
            $pdf->SetFont('Arial','B', 6);

            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, true);
          }

          $pdf->SetFont('Arial','', 6);
          $pdf->SetTextColor(0,0,0);
          if ($pdf->titulo2 === 'ALMACENISTA' || $pdf->titulo2 === 'ADMINISTRADOR') {
            $datos = array(
              "{$prod->percent}%",
              $prod->producto,
              MyString::formatoNumero($prod->dosis_mezcla, 2, '', false),
              MyString::formatoNumero($prod->aplicacion_total, 2, '', false),
              MyString::formatoNumero($prod->precio, 2, '$', false),
              MyString::formatoNumero($prod->importe, 2, '$', false)
            );
          } else {
            $datos = array(
              "{$prod->percent}%",
              $prod->producto,
              MyString::formatoNumero($prod->dosis_mezcla, 2, '', false),
              MyString::formatoNumero($prod->aplicacion_total, 2, '', false)
            );
          }


          $pdf->SetX(6);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, false);

          $tpercent     += $prod->percent;
          $tcantidad    += $prod->dosis_mezcla;
          $ttaplicacion += $prod->aplicacion_total;
          $timporte     += $prod->importe;
        }

        // Totales
        $pdf->SetFont('Arial','B', 6);
        $pdf->SetX(6);
        if ($pdf->titulo2 === 'ALMACENISTA' || $pdf->titulo2 === 'ADMINISTRADOR') {
          $pdf->Row([
            "",
            '',
            MyString::formatoNumero($tcantidad, 2, '', false),
            MyString::formatoNumero($ttaplicacion, 2, '', false),
            '',
            MyString::formatoNumero($timporte, 2, '', false),
          ], false);
        } else {
          $pdf->Row([
            "",
            '',
            MyString::formatoNumero($tcantidad, 2, '', false),
            MyString::formatoNumero($ttaplicacion, 2, '', false),
          ], false);
        }

      } else { // lts
        $tpercent = $tcantidad = $ttaplicacion = $timporte = $tcarga1 = $tcarga2 = 0;
        $aligns = array('C', 'L', 'R', 'R', 'R', 'R', 'R', 'R');
        if ($pdf->titulo2 === 'ALMACENISTA' || $pdf->titulo2 === 'ADMINISTRADOR') {
          $widths = array(10, 50, 14, 14, 14, 17, 15, 18);
          $header = array('%', 'PRODUCTO', 'D. Equipo', 'CARGA 1', 'CARGA 2', 'A. TOTAL', 'PRECIO', 'IMPORTE');
        } else {
          $widths = array(10, 50, 14, 14, 14, 17);
          $header = array('%', 'PRODUCTO', 'D. Equipo', 'CARGA 1', 'CARGA 2', 'A. TOTAL');
        }

        $pdf->SetY(($yaux_datos > $yaux_sem? $yaux_datos: $yaux_sem));

        $pdf->SetFont('Arial','B', 6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->SetX(6);
        if ($pdf->titulo2 === 'ALMACENISTA' || $pdf->titulo2 === 'ADMINISTRADOR') {
          $datos = [
            '', '', 'Cargas',
            MyString::formatoNumero($receta['info']->carga1, 2, '', false),
            MyString::formatoNumero($receta['info']->carga2, 2, '', false),
            ''
          ];
        } else {
          $datos = [
            '', '', 'Cargas',
            MyString::formatoNumero($receta['info']->carga1, 2, '', false),
            MyString::formatoNumero($receta['info']->carga2, 2, '', false),
            ''
          ];
        }
        $pdf->Row($datos, false, false);

        $yaux = $pdf->GetY();
        $page_aux = $pdf->page;
        foreach ($receta['info']->productos as $key => $prod)
        {
          if($pdf->GetY() >= $pdf->limiteY || $key === 0) {
            if($pdf->GetY()+5 >= $pdf->limiteY)
              $pdf->AddPage();
            $pdf->SetFont('Arial','B', 6);

            if ($key === 0) {
              $header[3] = MyString::formatoNumero($receta['info']->dosis_equipo, 2, '', false);
              $header[4] = MyString::formatoNumero($receta['info']->dosis_equipo_car2, 2, '', false);
            }

            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, true);
          }

          $pdf->SetFont('Arial','', 6);
          $pdf->SetTextColor(0,0,0);
          if ($pdf->titulo2 === 'ALMACENISTA' || $pdf->titulo2 === 'ADMINISTRADOR') {
            $datos = array(
              "{$prod->percent}%",
              $prod->producto,
              MyString::formatoNumero($prod->dosis_mezcla, 2, '', false),
              MyString::formatoNumero($prod->dosis_carga1, 2, '', false),
              MyString::formatoNumero($prod->dosis_carga2, 2, '', false),
              MyString::formatoNumero($prod->aplicacion_total, 2, '', false),
              MyString::formatoNumero($prod->precio, 2, '$', false),
              MyString::formatoNumero($prod->importe, 2, '$', false)
            );
          } else {
            $datos = array(
              "{$prod->percent}%",
              $prod->producto,
              MyString::formatoNumero($prod->dosis_mezcla, 2, '', false),
              MyString::formatoNumero($prod->dosis_carga1, 2, '', false),
              MyString::formatoNumero($prod->dosis_carga2, 2, '', false),
              MyString::formatoNumero($prod->aplicacion_total, 2, '', false)
            );
          }

          $pdf->SetX(6);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, false);

          $tpercent     += $prod->percent;
          $tcantidad    += $prod->dosis_mezcla;
          $ttaplicacion += $prod->aplicacion_total;
          $timporte     += $prod->importe;
          $tcarga1      += $prod->dosis_carga1;
          $tcarga2      += $prod->dosis_carga2;
        }

        // Totales
        $pdf->SetFont('Arial','B', 6);
        $pdf->SetX(6);
        if ($pdf->titulo2 === 'ALMACENISTA' || $pdf->titulo2 === 'ADMINISTRADOR') {
          $pdf->Row([
            "{$tpercent}%",
            '',
            MyString::formatoNumero($tcantidad, 2, '', false),
            MyString::formatoNumero($tcarga1, 2, '', false),
            MyString::formatoNumero($tcarga2, 2, '', false),
            MyString::formatoNumero($ttaplicacion, 2, '', false),
            '',
            MyString::formatoNumero($timporte, 2, '$', false)
          ], false);
        } else {
          $pdf->Row([
            "{$tpercent}%",
            '',
            MyString::formatoNumero($tcantidad, 2, '', false),
            MyString::formatoNumero($tcarga1, 2, '', false),
            MyString::formatoNumero($tcarga2, 2, '', false),
            MyString::formatoNumero($ttaplicacion, 2, '', false)
          ], false);
        }
      }

      $page_aux2 = $pdf->page;
      $yaux_prod = $pdf->GetY();

      $val_x = ($pdf->titulo2 !== 'ALMACENISTA' && $pdf->titulo2 !== 'ADMINISTRADOR')? 130: 160;
      $val_widths1 = ($pdf->titulo2 !== 'ALMACENISTA' && $pdf->titulo2 !== 'ADMINISTRADOR')? array(83): array(53);
      $val_widths2 = ($pdf->titulo2 !== 'ALMACENISTA' && $pdf->titulo2 !== 'ADMINISTRADOR')? array(16, 37): array(16, 37);
      $pdf->page = $page_aux;
      $pdf->SetXY($val_x, $yaux);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->SetWidths($val_widths1);
      $pdf->Row(['PROGRAMA DE APLICACION'], true);

      $pdf->SetAligns(array('R', 'L'));
      $pdf->SetWidths($val_widths2);
      $pdf->SetX($val_x);
      $pdf->Row(['ETAPA', $receta['info']->a_etapa], false, false);
      $pdf->SetX($val_x);
      $pdf->Row(['CICLO', $receta['info']->a_ciclo], false, false);
      $pdf->SetX($val_x);
      $pdf->Row(['DDS', $receta['info']->a_dds], false, false);
      $pdf->SetX($val_x);
      $pdf->Row(['TURNO', $receta['info']->a_turno], false, false);
      $pdf->SetX($val_x);
      $pdf->Row(['VIA', $receta['info']->a_via], false, false);
      $pdf->SetX($val_x);
      $pdf->Row(['APLICACION', $receta['info']->a_aplic], false, false);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->SetWidths($val_widths1);
      $pdf->SetX($val_x);
      $pdf->Row(['OBSERVACIONES'], false);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths($val_widths1);
      $pdf->SetX($val_x);
      $pdf->Row([$receta['info']->a_observaciones], false, 'B');
      $pdf->Line($val_x, $yaux, $val_x, $pdf->GetY());
      $pdf->Line(213, $yaux, 213, $pdf->GetY());

      if ($pdf->titulo2 !== 'ALMACENISTA' && $pdf->titulo2 !== 'ADMINISTRADOR') {
        $pdf->page = $page_aux;
        $pdf->SetXY(175, $yaux+6);
        $pdf->SetAligns(array('C', 'L'));
        $pdf->SetWidths(array(36));
        $pdf->Row(['FECHA'], false);
        $pdf->SetXY(175, $pdf->GetY());
        $pdf->SetAligns(array('C', 'C', 'C'));
        $pdf->SetWidths(array(12, 12, 12));
        $pdf->Row(['', '', ''], false);

        $pdf->SetXY(175, $pdf->GetY()-1);
        $pdf->SetAligns(array('C', 'C', 'C'));
        $pdf->SetWidths(array(14, 22));
        $pdf->Row(['SEM', '______________'], false, false);

        $pdf->SetXY(175, $pdf->GetY());
        $pdf->SetAligns(array('C', 'C', 'C'));
        $pdf->SetWidths(array(14, 22));
        $pdf->Row(['INICIO', '______________'], false, false);

        $pdf->SetXY(175, $pdf->GetY());
        $pdf->SetAligns(array('C', 'C', 'C'));
        $pdf->SetWidths(array(14, 22));
        $pdf->Row(['TERMINO', '______________'], false, false);

        $pdf->SetXY(175, $pdf->GetY());
        $pdf->SetAligns(array('C', 'C', 'C'));
        $pdf->SetWidths(array(14, 22));
        $pdf->Row(['TOTAL', '______________'], false, false);
      }

      $pdf->page = $page_aux2;
      $pdf->SetXY(6, $yaux_prod);

      $width_firmas2 = 68;
      if ($pdf->titulo2 === 'ALMACENISTA' || $pdf->titulo2 === 'ADMINISTRADOR'){
        $width_firmas2 = 83;
      }

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(71));
      $pdf->SetXY(6, $pdf->GetY()+6);
      $pdf->Row(array('SOLICITA: ________________________________________'), false, false);
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->Row(array(strtoupper($receta['info']->solicito)), false, false);

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetXY($width_firmas2, $pdf->GetY()-9);
      $pdf->Row(array('REALIZO: _________________________________________'), false, false);
      $pdf->SetXY($width_firmas2, $pdf->GetY()-2);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->Row(array(strtoupper($receta['info']->realizo)), false, false);

      if($pdf->GetY()+5 >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetXY(6, $pdf->GetY()+6);
      $pdf->Row(array('AUTORIZO: _______________________________________'), false, false);
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->Row(array(strtoupper($receta['info']->autorizo)), false, false);

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetXY($width_firmas2, $pdf->GetY()-9);
      $pdf->Row(array('RECIBIO: _________________________________________'), false, false);
      $pdf->SetXY($width_firmas2, $pdf->GetY()-2);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->Row(array(''), false, false);

      if ($pdf->titulo2 === 'ALMACENISTA' || $pdf->titulo2 === 'ADMINISTRADOR') {
        $this->print_receta($recetaId, $pdf, $rep);
      }
      $pdf->Output('receta'.date('Y-m-d').'.pdf', 'I');
      exit;
   }

  public function print_salidaticket($salidaID, $recetaId, $pdf = null)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');
    $this->load->model('productos_salidas_model');

    $orden = $this->productos_salidas_model->info($salidaID, true);
    $receta = $this->info($recetaId, true);
    // echo "<pre>";
    //   var_dump($orden, $receta);
    // echo "</pre>";exit;

    if (!$pdf) {
      $tipo_imp = 'almacen';
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', array(63, 210));
    } else {
      $tipo_imp = 'vigilancia';
    }
    $pdf->show_head = false;
    $pdf->AddPage();
    $pdf->AddFont($pdf->fount_num, '');

    // Título
    $pdf->SetFont($pdf->fount_txt, 'B', 8.5);
    $pdf->SetXY(0, 3);
    $pdf->MultiCell($pdf->pag_size[0], 4, $receta['info']->empresa, 0, 'C');
    $pdf->SetFont($pdf->fount_txt, '', 7);
    // $pdf->SetX(0);
    // $pdf->MultiCell($pdf->pag_size[0], 4, $pdf->reg_fed, 0, 'C');

    $pdf->SetFont($pdf->fount_txt, 'B', 7);
    $pdf->SetX(0);
    $pdf->MultiCell(32, 4, 'SALIDA DE PRODUCTOS'.($orden['info'][0]->id_traspaso>0? '(Traspaso)': ''), 0, 'L');

    $pdf->SetXY(35, $pdf->GetY()-4);
    $pdf->MultiCell(27, 4, 'Folio: '.$orden['info'][0]->folio, 0, 'L');

    $pdf->SetWidths(array(32, 31));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetFounts(array($pdf->fount_txt));
    $pdf->SetX(0);
    $pdf->Row2(array('Receta: '.$receta['info']->folio,
        'Fecha S.: '.MyString::fechaATexto( substr($orden['info'][0]->fecha, 0, 10), 'cm') ), false, false, 5);

    $pdf->SetWidths(array(32, 32));
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetXY(0, $pdf->GetY()-1);
    $pdf->Row2(array('Fecha R.: '.MyString::fechaATexto($receta['info']->fecha, 'cm'), 'Semana: '.$receta['info']->semana ), false, false, 5);

    if ($receta['info']->tipo == 'lts') {
      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->SetWidths(array(64));
      $pdf->Row2(array('Cargas: '.$orden['info'][0]->receta_cargas.' de '.($receta['info']->carga1+$receta['info']->carga2)." | Saldo: {$receta['info']->saldo_cargas}"), false, false);
    } else {
      //
      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->SetWidths(array(64));
      $pdf->Row2(array('Plantas: '.$orden['info'][0]->receta_cargas.' de '.($receta['info']->no_plantas)." | Saldo: {$receta['info']->saldo_plantas}"), false, false);
    }
    $pdf->SetFont($pdf->fount_txt, '', 7);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 2, '--------------------------------------------------------------------------', 0, 'L');

    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Empresa: '.$receta['info']->empresa_ap ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $ranchos = [];
    foreach ($receta['info']->rancho as $keyr => $item) {
      $ranchos[] = $item->nombre;
    }
    $pdf->Row2(array('Rancho: '.implode(', ', $ranchos) ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Objetivo: '.$receta['info']->objetivo ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Cultivo: '.$receta['info']->area ), false, false);
    $pdf->SetXY(10, $pdf->GetY()-2);
    $pdf->Row2(array("Has: {$receta['info']->ha_neta} | No Plantas: ".MyString::formatoNumero($receta['info']->no_plantas, 2, '', true)), false, false);

    $pdf->SetWidths(array(32, 32));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Ciclo: '.$receta['info']->a_ciclo, 'Etapa: '.$receta['info']->a_etapa ), false, false);

    $pdf->SetWidths(array(64));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $centrosc = [];
    foreach ($receta['info']->centroCosto as $keyr => $item) {
      $centrosc[] = $item->codigo;
    }
    $pdf->Row2(array('Centros de costo: '.implode(', ', $centrosc) ), false, false);

    // $pdf->SetXY(0, $pdf->GetY()-2);
    // $pdf->Row2(array('Etapa: '.$orden['info'][0]->etapa, 'Rancho: '.$orden['info'][0]->rancho_n ), false, false);
    // $pdf->SetXY(0, $pdf->GetY()-2);
    // $pdf->Row2(array('CC: '.$orden['info'][0]->centro_c, 'Hectareas: '.$orden['info'][0]->hectareas ), false, false);
    // $pdf->SetXY(0, $pdf->GetY()-2);
    // $pdf->Row2(array('Grupo: '.$orden['info'][0]->grupo, 'No melgas: '.$orden['info'][0]->no_secciones ), false, false);
    // $pdf->SetXY(0, $pdf->GetY()-2);
    // $pdf->Row2(array('DD FS: '.$orden['info'][0]->dias_despues_de, 'Metodo A: '.$orden['info'][0]->metodo_aplicacion ), false, false);
    // $pdf->SetXY(0, $pdf->GetY()-2);
    // $pdf->Row2(array('Ciclo: '.$orden['info'][0]->ciclo, 'Tipo A: '.$orden['info'][0]->tipo_aplicacion ), false, false);
    // $pdf->SetXY(0, $pdf->GetY()-2);
    // $pdf->Row2(array('Almacen: '.$orden['info'][0]->almacen, 'Fecha A: '.MyString::fechaAT($orden['info'][0]->fecha_aplicacion) ), false, false);
    // if (isset($orden['info'][0]->traspaso)) {
    //   $pdf->SetXY(0, $pdf->GetY()-2);
    //   $pdf->Row2(array('Traspaso: '.$orden['info'][0]->traspaso->almacen, 'Fecha: '.MyString::fechaAT($orden['info'][0]->traspaso->fecha) ), false, false);
    // }
    // $pdf->SetWidths(array(65));
    // $pdf->SetXY(0, $pdf->GetY()-2);
    // $pdf->Row2(array('Observaciones: '.$orden['info'][0]->observaciones ), false, false);

    // $pdf->SetFont($pdf->fount_txt, '', 7);
    // $pdf->SetX(0);
    // $pdf->MultiCell($pdf->pag_size[0], 2, '--------------------------------------------------------------------------', 0, 'L');
    // $pdf->SetFont($pdf->fount_txt, '', $pdf->font_size-1);

    $pdf->SetX(0);
    if ($tipo_imp === 'almacen') {
      $pdf->SetWidths(array(10, 28, 11, 14));
      $pdf->SetAligns(array('L','L','R','R'));
      $pdf->SetFounts(array($pdf->fount_txt), array(-1,-2,-2,-2));
      $pdf->Row2(array('CANT.', 'DESCRIPCION', 'P.U.', 'IMPORTE'), false, true, 5);
    } elseif ($tipo_imp === 'vigilancia') {
      $pdf->SetWidths(array(10, 40, 11, 14));
      $pdf->SetAligns(array('L','L','R','R'));
      $pdf->SetFounts(array($pdf->fount_txt), array(-1,-2,-2,-2));
      $pdf->Row2(array('CANT.', 'DESCRIPCION'), false, true, 5);
    }

    $pdf->SetFounts(array($pdf->fount_num,$pdf->fount_txt,$pdf->fount_num,$pdf->fount_num),
                   array(0,-1.5,-1.3,-1.2));
    $cantidad = $subtotal = $iva = $total = $retencion = $ieps = 0;
    $tipoCambio = 0;
    $codigoAreas = array();
    foreach ($orden['info'][0]->productos as $key => $prod) {
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pprod = [
        $prod->cantidad.' '.$prod->abreviatura,
        $prod->producto
      ];

      if ($tipo_imp === 'almacen') {
        $pprod[] = MyString::formatoNumero($prod->precio_unitario, 2, '', true);
        $pprod[] = MyString::formatoNumero(($prod->precio_unitario*$prod->cantidad), 2, '', true);
      }

      $pdf->Row2($pprod, false, false);

      $cantidad += $prod->cantidad;
      $total += floatval($prod->precio_unitario*$prod->cantidad);
    }

    $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(10, 25, 14, 14));
    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
    $pdf->SetX(0);
    if ($tipo_imp === 'almacen') {
      $pdf->Row2(array($cantidad, '', 'TOTAL', MyString::formatoNumero($total, 2, '', true)), false, 'T', 5);
    } elseif ($tipo_imp === 'vigilancia') {
      $pdf->Row2(array($cantidad, '', '', ''), false, 'T', 5);
    }

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
    $pdf->Row2(array('ELABORO: '.strtoupper($orden['info'][0]->empleado), '' ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('SOLICITO: '.strtoupper($orden['info'][0]->solicito)), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('AUTORIZO: '.strtoupper($orden['info'][0]->recibio)), false, false);

    $pdf->SetXY(0, $pdf->GetY()-3);
    $pdf->Row2(array('_____________________________________________'), false, false);

    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('PLACAS: '.strtoupper( (isset($orden['info'][0]->bascula)? $orden['info'][0]->bascula->camion_placas : '') )), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('CAMION: '.strtoupper( (isset($orden['info'][0]->bascula)? $orden['info'][0]->bascula->camion : '') )), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('RECIBIO: '), false, false);

    $pdf->SetWidths(array(63, 0));
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-3);
    $pdf->Row2(array('____________________________________'), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Firma de Recibido'), false, false);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Expedido el: '.MyString::fechaATexto(date("Y-m-d H:i:s"), 'in', true)." Por: {$this->session->userdata('usuario')}"), false, false);

    // if ($orden['info'][0]->trabajador != '') {
    //   $pdf->SetXY(0, $pdf->GetY()-2);
    //   $pdf->Row2(array('Se asigno a: '.strtoupper($orden['info'][0]->trabajador)), false, false);
    // }

    $pdf->SetWidths(array(11, 22, 30));
    $pdf->SetAligns(array('L', 'L', 'L', 'L'));
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row2(['Bascula', "No ".(isset($orden['info'][0]->bascula)? $orden['info'][0]->bascula->folio : ''),
      MyString::fechaATexto((isset($orden['info'][0]->bascula)? $orden['info'][0]->bascula->fecha_tara : ''), 'in', true),
    ], false, true);

    $pdf->SetAligns(array('C', 'C'));
    $pdf->SetWidths(array(30, 0));
    $pdf->SetXY(30, $pdf->GetY()+4);
    $pdf->Row(array(strtoupper($tipo_imp)), false, true);
    $pdf->SetXY(30, $pdf->GetY());
    $pdf->Row(array( ($orden['info'][0]->no_impresiones_tk==0? 'ORIGINAL': 'COPIA '.$orden['info'][0]->no_impresiones_tk)), false, false);

    if ($tipo_imp === 'almacen') {
      $this->print_salidaticket($salidaID, $recetaId, $pdf);
    } elseif ($tipo_imp === 'vigilancia') {
      $this->db->update('compras_salidas', ['no_impresiones_tk' => $orden['info'][0]->no_impresiones_tk+1], "id_salida = ".$orden['info'][0]->id_salida);
      $pdf->Output();
    }
  }


  public function getEventosCalendario($datos)
  {
    $fecha = new DateTime( (isset($datos['ffecha1'])? $datos['ffecha1']: date("Y-m-d")) );
    $fecha->modify('first day of this month');
    $fecha1 = $fecha->format('Y-m-d');
    $fecha->modify('last day of this month');
    $fecha2 = $fecha->format('Y-m-d');

    $response = array();
    if (isset($datos['did_area'])) {
      $result = $this->db->query("SELECT r.id_recetas,
          ('Receta: ' || r.folio || ' | Fecha: ' || r.fecha || ' | Tipo: ' || r.tipo) AS title,
          r.fecha_aplicacion AS start
        FROM otros.recetas r
        WHERE r.status = 't' AND r.id_area = {$datos['did_area']} AND r.id_recetas_calendario = {$datos['calendario']}
          AND r.fecha_aplicacion BETWEEN '{$fecha1}' AND '{$fecha2}'");

      if($result->num_rows() > 0)
      {
        $response = $result->result();
      }
    }

    return $response;
  }

  public function getCalendariosAjax($id_area){
    $response = array();

    if ($id_area > 0) {
      $res = $this->db->query(
         "SELECT id, nombre
          FROM otros.recetas_calendarios
          WHERE status = 't' AND id_area = {$id_area}");

      if($res->num_rows() > 0)
      {
        $response = $res->result();
      }
    }

    return $response;
  }

}