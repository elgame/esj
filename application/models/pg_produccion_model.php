<?php
class pg_produccion_model extends privilegios_model{
  private $otrosDatoss = null;

  public $tipos = [
    '' => '',
    'bg' => '(Bodega GDL)',
    'ff' => '(Flete Foráneo)',
    'fl' => '(Flete Loca)l'
  ];

	function __construct(){
		parent::__construct();
    $this->load->model('bitacora_model');
	}


  public function getProduccion($perpage = '40', $sql2='')
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
      $sql = " AND Date(pgp.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(pgp.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(pgp.fecha) = '".$this->input->get('ffecha2')."'";

    if($this->input->get('fbuscar') != '' && is_numeric($this->input->get('fbuscar'))) {
      $sql .= " AND pgp.folio = '".$this->input->get('fbuscar')."'";
    } elseif($this->input->get('fbuscar') != '') {
      $sql .= " AND (
        lower(es.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fbuscar'), 'UTF-8')."%' OR
        lower(pgm.nombre) LIKE '%".mb_strtolower($this->input->get('fbuscar'), 'UTF-8')."%' OR
        lower(pgmo.nombre) LIKE '%".mb_strtolower($this->input->get('fbuscar'), 'UTF-8')."%'
      )";
    }

    if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'all') {
      $sql .= " AND pgp.status = ".$this->input->get('fstatus')."";
    }

    $empresa_default = $this->empresas_model->getDefaultEmpresa();
    if($this->input->get('did_empresa') != '') {
      $sql .= " AND pgp.id_empresa = '".$this->input->get('did_empresa')."'";
    } else {
      $sql .= " AND pgp.id_empresa = '".$empresa_default->id_empresa."'";
    }

    $query = BDUtil::pagination("
        SELECT pgp.id_produccion, pgp.folio, pgp.turno, pgp.fecha, pgp.cajas_total,
          pgp.status, es.nombre_fiscal AS sucursal, pgm.nombre AS maquina, pgmo.nombre AS molde
        FROM otros.pg_produccion pgp
          LEFT JOIN empresas_sucursales es ON es.id_sucursal = pgp.id_sucursal
          INNER JOIN otros.pg_maquinas pgm ON pgm.id_maquina = pgp.id_maquina
          INNER JOIN otros.pg_moldes pgmo ON pgmo.id_molde = pgp.id_molde
        WHERE 1 = 1 ".$sql."
        ORDER BY pgp.fecha DESC, pgp.folio DESC
        ", $params, true);
    $res = $this->db->query($query['query']);

    $response = array(
        'producciones'   => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['producciones'] = $res->result();

    return $response;
  }

  public function addProduccion($data=NULL)
  {

    if ($data==NULL)
    {
      $data = array(
        'id_empresa'    => $this->input->post('did_empresa'),
        'id_sucursal'   => $this->input->post('sucursalId'),
        'id_maquina'    => $this->input->post('dmaquina'),
        'id_molde'      => $this->input->post('dmolde'),
        'id_grupo'      => $this->input->post('dgrupo'),
        'id_registro'   => $this->session->userdata('id_usuario'),
        'id_jefe_turno' => $this->input->post('djefeTurnId'),
        'folio'         => $this->input->post('folio'),
        'turno'         => $this->input->post('dturno'),
        'fecha'         => $this->input->post('dfecha'),
        'cajas_buenas'  => $this->input->post('cajas_buenas'),
        'cajas_merma'   => $this->input->post('cajas_merma'),
        'cajas_total'   => $this->input->post('cajas_total'),
        'plasta_kg'     => $this->input->post('plasta_kg'),
        'inyectado_kg'  => $this->input->post('inyectado_kg'),
        'peso_prom'     => $this->input->post('peso_prom'),
        'tiempo_ciclo'  => $this->input->post('tiempo_ciclo'),
      );
    }

    $this->db->insert('otros.pg_produccion', $data);
    $id_produccion = $this->db->insert_id('otros.pg_produccion_id_produccion_seq');

    return array('passes' => true);
  }






  /*
   |-------------------------------------------------------------------------
   |  FACTURACION
   |-------------------------------------------------------------------------
   */

	/**
	 * Obtiene el listado de facturas
   *
   * @return
	 */


  public function getTipos()
  {
    $query = $this->db->query("SELECT * FROM otros.estado_resultado_trans_tiposg ORDER BY orden ASC");

    return $query->result();
  }

  public function getRemisiones($id_empresa)
  {
    $remisiones = $this->db->query(
      "SELECT f.id_factura, DATE(f.fecha) as fecha, f.serie, f.folio, f.subtotal, f.total, c.nombre_fiscal as cliente
            -- ,fp.descripcion, fp.cantidad, fp.precio_unitario, fp.importe
       FROM facturacion f
         INNER JOIN clientes c ON c.id_cliente = f.id_cliente
         -- INNER JOIN facturacion_productos fp ON fp.id_factura = f.id_factura
       WHERE f.is_factura = 'f' AND f.status = 'p' AND f.id_empresa = {$id_empresa}
       ORDER BY (f.fecha, f.serie, f.folio) DESC
       LIMIT 1500"
    );

    $response = $remisiones->result();

    return $response;
  }

  public function getProdRemisiones($id_rem)
  {
    $remisiones = $this->db->query(
      "SELECT fp.descripcion, fp.cantidad, fp.precio_unitario, fp.importe
       FROM facturacion_productos fp
       WHERE fp.id_factura = {$id_rem}
       ORDER BY num_row ASC"
    );

    $response = $remisiones->result();

    return $response;
  }

  public function getRepMant($id_empresa)
  {
    $gastos = $this->db->query(
      "SELECT c.id_compra, Date(c.fecha) AS fecha, p.id_proveedor, p.nombre_fiscal AS proveedor,
        (c.serie || c.folio) AS folio, c.concepto, c.subtotal, c.total, c.importe_iva
       FROM compras c
         INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
       WHERE c.id_empresa = {$id_empresa} AND c.status <> 'ca'
        AND c.isgasto = 't'
       ORDER BY (c.fecha, c.serie, c.folio) DESC
       LIMIT 1500"
    );

    $response = $gastos->result();

    return $response;
  }

  public function ajaxProveedores($id_empresa)
  {
    $sql = '';
    $res = $this->db->query("
        SELECT *
        FROM proveedores
        WHERE status = 'ac' AND id_empresa = {$id_empresa} AND
          lower(nombre_fiscal) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%'
        ORDER BY nombre_fiscal ASC
        LIMIT 20");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
          'id' => $itm->id_proveedor,
          'label' => $itm->nombre_fiscal,
          'value' => $itm->nombre_fiscal,
          'item' => $itm,
        );
      }
    }

    return $response;
  }

  public function ajaxCodsGastos()
  {
    $sql = '';
    if (!empty($_GET['tipo'])) {
      $sql .= " AND tipo = '{$_GET['tipo']}'";
    }

    $res = $this->db->query("
        SELECT *
        FROM otros.estado_resultado_trans_cods
        WHERE status = 't' AND lower(nombre) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%'
          {$sql}
        ORDER BY nombre ASC
        LIMIT 25");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
          'id' => $itm->id,
          'label' => $itm->nombre,
          'value' => $itm->nombre,
          'item' => $itm,
        );
      }
    }

    return $response;
  }

  public function getGastosCaja($id_empresa)
  {
    $gastos = $this->db->query(
      "SELECT c.id_gasto, Date(c.fecha) AS fecha, cc.abreviatura,
        c.folio_sig AS folio, c.monto, c.concepto, c.nombre
      FROM cajachica_gastos c
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = c.id_categoria
      WHERE c.no_caja = 2 AND c.tipo = 'gc' AND c.status = 't'
      ORDER BY (c.fecha, c.folio) DESC
      LIMIT 2000"
    );

    $response = $gastos->result();

    return $response;
  }


  /**
   * Agrega una nota remison a la bd
   */
  public function addEstadoResult()
  {
    $msg = '1';
    $passes = true;
    $this->load->model('clientes_model');
    $this->load->model('clasificaciones_model');

    $lts_precios = [];
    if ($this->input->post('arend_lts')) {
      foreach ($_POST['arend_lts'] as $key => $value) {
        $lts_precios[] = [
          'rend_lts' => $value,
          'rend_precio' => $_POST['arend_precio'][$key],
        ];
      }
    }

    $otrosDatos = [
      'od_termo' => $this->input->post('od_termo'),
      'od_termoId' => $this->input->post('od_termoId'),
      'od_camionCapTanq' => $this->input->post('od_camionCapTanq'),
      'od_camionRendHist' => $this->input->post('od_camionRendHist'),
      'od_camionTEncendido' => $this->input->post('od_camionTEncendido'),
      'od_termoCapTanq' => $this->input->post('od_termoCapTanq'),
      'od_hrsalida' => $this->input->post('od_hrsalida'),
      'od_hrllegada' => $this->input->post('od_hrllegada'),
      'od_gobernado' => $this->input->post('od_gobernado'),
      'od_maxdiesel' => $this->input->post('od_maxdiesel'),
      'od_1captanque' => $this->input->post('od_1captanque'),
      'od_2captanque' => $this->input->post('od_2captanque'),
      'od_costoEstimado' => $this->input->post('od_costoEstimado'),
      'od_costoGeneral' => $this->input->post('od_costoGeneral'),
    ];

    $datosFactura = array(
      'id_chofer'      => $this->input->post('did_chofer'),
      'id_activo'      => $this->input->post('did_activo'),
      'id_empresa'     => $this->input->post('did_empresa'),
      'id_creo'        => $this->session->userdata('id_usuario'),
      'tipo_flete'     => $this->input->post('dtipo'),
      'fecha'          => $this->input->post('dfecha'),
      'fecha_viaje'    => $this->input->post('dfecha_viaje'),
      'folio'          => $this->getFolio($this->input->post('did_empresa'), $this->input->post('did_activo')),
      'km_rec'         => floatval($this->input->post('dkm_rec')),
      'vel_max'        => floatval($this->input->post('dvel_max')),
      'rep_lt_hist'    => floatval($this->input->post('drep_lt_hist')),
      'rend_km_gps'    => floatval($this->input->post('rend_km_gps')),
      'rend_actual'    => floatval($this->input->post('rend_actual')),
      // 'rend_lts'       => floatval($this->input->post('rend_lts')),
      // 'rend_precio'    => floatval($this->input->post('rend_precio')),
      'rend_thrs_trab' => floatval($this->input->post('rend_thrs_trab')),
      'rend_thrs_lts'  => floatval($this->input->post('rend_thrs_lts')),
      'rend_thrs_hxl'  => floatval($this->input->post('rend_thrs_hxl')),
      'destino'        => $this->input->post('destino'),
      'id_gasto'       => $this->input->post('did_gasto') > 0? $this->input->post('did_gasto'): null,
      'gasto_monto'    => $this->input->post('gasto_monto') > 0? $this->input->post('gasto_monto'): 0,
      'lts_precios'    => json_encode($lts_precios),
      'otros_datos'    => json_encode($otrosDatos),
    );

    $this->db->insert('otros.estado_resultado_trans', $datosFactura);
    $id_estado = $this->db->insert_id('otros.estado_resultado_trans_id_seq');

    $ventas = array();
    if (!empty($_POST['remision_cliente'])) {
      foreach ($_POST['remision_cliente'] as $key => $descripcion)
      {
        $ventas[] = array(
          'id_estado' => $id_estado,
          'id_remision' => $_POST['remision_id'][$key] !== '' ? $_POST['remision_id'][$key] : null,
          'comprobacion' => $_POST['remision_comprobacion'][$key] == 'true' ? 't' : 'f',
          'imp_comprobacion' => floatval($_POST['remision_comprobacionimpt'][$key]),
        );
      }
      if(count($ventas) > 0)
        $this->db->insert_batch('otros.estado_resultado_trans_ventas', $ventas);
    }

    $sueldos = array();
    if (!empty($_POST['sueldos_concepto'])) {
      foreach ($_POST['sueldos_concepto'] as $key => $descripcion)
      {
        $sueldos[] = array(
          'id_estado'    => $id_estado,
          'id_proveedor' => $_POST['sueldos_proveedor_id'][$key] !== '' ? $_POST['sueldos_proveedor_id'][$key] : null,
          'fecha'        => $_POST['sueldos_fecha'][$key] !== '' ? $_POST['sueldos_fecha'][$key] : null,
          'descripcion'  => $_POST['sueldos_concepto'][$key] !== '' ? $_POST['sueldos_concepto'][$key] : '',
          'cantidad'     => $_POST['sueldos_cantidad'][$key] !== '' ? $_POST['sueldos_cantidad'][$key] : 0,
          'importe'      => $_POST['sueldos_importe'][$key] !== '' ? $_POST['sueldos_importe'][$key] : 0,
          // 'comprobacion' => $_POST['sueldos_comprobacion'][$key] == 'true' ? 't' : 'f',
        );
      }
      if(count($sueldos) > 0)
        $this->db->insert_batch('otros.estado_resultado_trans_sueldos', $sueldos);
    }

    $repmant = array();
    if (!empty($_POST['repmant_proveedor'])) {
      foreach ($_POST['repmant_proveedor'] as $key => $descripcion)
      {
        $id_cod = intval($_POST['repmant_codg_id'][$key]);
        if ($id_cod == 0) {
          $passes = false;
          $msg = '34';
          // $id_cod = $this->addCods($_POST['gastos_codg'][$key]);
        } else {
          $repmant[] = array(
            'id_estado'    => $id_estado,
            'id_cod'       => $id_cod,
            'id_compra'    => $_POST['repmant_id'][$key] !== '' ? $_POST['repmant_id'][$key] : null,
            'comprobacion' => $_POST['repmant_comprobacion'][$key] == 'true' ? 't' : 'f',
            'fecha'        => $_POST['repmant_fecha'][$key] !== '' ? $_POST['repmant_fecha'][$key] : null,
            'folio'        => $_POST['repmant_numero'][$key] !== '' ? $_POST['repmant_numero'][$key] : null,
            'proveedor'    => $_POST['repmant_proveedor'][$key] !== '' ? $_POST['repmant_proveedor'][$key] : null,
            'concepto'     => $_POST['repmant_concepto'][$key] !== '' ? $_POST['repmant_concepto'][$key] : null,
            'subtotal'     => $_POST['repmant_subtotal'][$key] !== '' ? $_POST['repmant_subtotal'][$key] : null,
            'iva'          => $_POST['repmant_iva'][$key] !== '' ? $_POST['repmant_iva'][$key] : null,
            'importe'      => $_POST['repmant_importe'][$key] !== '' ? $_POST['repmant_importe'][$key] : null,
          );
        }
      }
      if(count($repmant) > 0)
        $this->db->insert_batch('otros.estado_resultado_trans_rep_mtto', $repmant);
    }

    $gastos = array();
    if (!empty($_POST['gastos_proveedor'])) {
      foreach ($_POST['gastos_proveedor'] as $key => $descripcion)
      {
        $id_cod = intval($_POST['gastos_codg_id'][$key]);
        if ($id_cod == 0) {
          $passes = false;
          $msg = '33';
          // $id_cod = $this->addCods($_POST['gastos_codg'][$key]);
        } else {
          $gastos[] = array(
            'id_estado'    => $id_estado,
            'id_proveedor' => $_POST['gastos_proveedor_id'][$key] !== '' ? $_POST['gastos_proveedor_id'][$key] : null,
            'id_cod'       => $id_cod,
            'fecha'        => $_POST['gastos_fecha'][$key] !== '' ? $_POST['gastos_fecha'][$key] : null,
            'subtotal'     => $_POST['gastos_subtotal'][$key] !== '' ? $_POST['gastos_subtotal'][$key] : 0,
            'iva'          => $_POST['gastos_iva'][$key] !== '' ? $_POST['gastos_iva'][$key] : 0,
            'importe'      => $_POST['gastos_importe'][$key] !== '' ? $_POST['gastos_importe'][$key] : 0,
            'cantidad'     => 0,
            'precio'       => 0,
            'comprobacion' => $_POST['gastos_comprobacion'][$key] == 'true' ? 't' : 'f',
            'id_compra'    => $_POST['gastos_id_compra'][$key] !== '' ? $_POST['gastos_id_compra'][$key] : null,
            'folio'        => $_POST['gastos_folio'][$key] !== '' ? $_POST['gastos_folio'][$key] : '',
          );
        }

      }

      if(count($gastos) > 0)
        $this->db->insert_batch('otros.estado_resultado_trans_gastos', $gastos);
    }

    return array('passes' => $passes, 'id_estado' => $id_estado, 'msg' => $msg);
  }

  public function updateEstadoResult($id_estado)
  {
    $msg = '1';
    $passes = true;
    $lts_precios = [];
    if ($this->input->post('arend_lts')) {
      foreach ($_POST['arend_lts'] as $key => $value) {
        $lts_precios[] = [
          'rend_lts' => $value,
          'rend_precio' => $_POST['arend_precio'][$key],
        ];
      }
    }

    $otrosDatos = [
      'od_termo' => $this->input->post('od_termo'),
      'od_termoId' => $this->input->post('od_termoId'),
      'od_camionCapTanq' => $this->input->post('od_camionCapTanq'),
      'od_camionRendHist' => $this->input->post('od_camionRendHist'),
      'od_camionTEncendido' => $this->input->post('od_camionTEncendido'),
      'od_termoCapTanq' => $this->input->post('od_termoCapTanq'),
      'od_hrsalida' => $this->input->post('od_hrsalida'),
      'od_hrllegada' => $this->input->post('od_hrllegada'),
      'od_gobernado' => $this->input->post('od_gobernado'),
      'od_maxdiesel' => $this->input->post('od_maxdiesel'),
      'od_1captanque' => $this->input->post('od_1captanque'),
      'od_2captanque' => $this->input->post('od_2captanque'),
      'od_costoEstimado' => $this->input->post('od_costoEstimado'),
      'od_costoGeneral' => $this->input->post('od_costoGeneral'),
    ];

    $datosFactura = array(
      'id_chofer'      => $this->input->post('did_chofer'),
      'id_activo'      => $this->input->post('did_activo'),
      'id_empresa'     => $this->input->post('did_empresa'),
      // 'id_creo'        => $this->session->userdata('id_usuario'),
      'tipo_flete'     => $this->input->post('dtipo'),
      'fecha'          => $this->input->post('dfecha'),
      'fecha_viaje'    => $this->input->post('dfecha_viaje'),
      // 'folio'       => $this->getFolio($this->input->post('did_empresa'), $this->input->post('did_activo')),
      'km_rec'         => floatval($this->input->post('dkm_rec')),
      'vel_max'        => floatval($this->input->post('dvel_max')),
      'rep_lt_hist'    => floatval($this->input->post('drep_lt_hist')),
      'rend_km_gps'    => floatval($this->input->post('rend_km_gps')),
      'rend_actual'    => floatval($this->input->post('rend_actual')),
      // 'rend_lts'       => floatval($this->input->post('rend_lts')),
      // 'rend_precio'    => floatval($this->input->post('rend_precio')),
      'rend_thrs_trab' => floatval($this->input->post('rend_thrs_trab')),
      'rend_thrs_lts'  => floatval($this->input->post('rend_thrs_lts')),
      'rend_thrs_hxl'  => floatval($this->input->post('rend_thrs_hxl')),
      'destino'        => $this->input->post('destino'),
      'id_gasto'       => $this->input->post('did_gasto') > 0? $this->input->post('did_gasto'): null,
      'gasto_monto'    => $this->input->post('gasto_monto') > 0? $this->input->post('gasto_monto'): 0,
      'lts_precios'    => json_encode($lts_precios),
      'otros_datos'    => json_encode($otrosDatos),
    );

    $this->db->update('otros.estado_resultado_trans', $datosFactura, "id = {$id_estado}");

    $ventas = array();
    $this->db->delete('otros.estado_resultado_trans_ventas', "id_estado = {$id_estado}");
    if (!empty($_POST['remision_cliente'])) {
      foreach ($_POST['remision_cliente'] as $key => $descripcion)
      {
        $ventas[] = array(
          'id_estado' => $id_estado,
          'id_remision' => $_POST['remision_id'][$key] !== '' ? $_POST['remision_id'][$key] : null,
          'comprobacion' => $_POST['remision_comprobacion'][$key] == 'true' ? 't' : 'f',
          'imp_comprobacion' => floatval($_POST['remision_comprobacionimpt'][$key]),
        );
      }
      if(count($ventas) > 0)
        $this->db->insert_batch('otros.estado_resultado_trans_ventas', $ventas);
    }

    $sueldos = array();
    if (!empty($_POST['sueldos_concepto'])) {
      foreach ($_POST['sueldos_concepto'] as $key => $descripcion)
      {
        if ($_POST['sueldos_del'][$key] == 'true' && $_POST['sueldos_id_sueldo'][$key] > 0) {
          $this->db->delete('otros.estado_resultado_trans_sueldos', "id = {$_POST['sueldos_id_sueldo'][$key]}");
        } elseif ($_POST['sueldos_id_sueldo'][$key] > 0) {
          $this->db->update('otros.estado_resultado_trans_sueldos', array(
            'id_estado'    => $id_estado,
            'id_proveedor' => $_POST['sueldos_proveedor_id'][$key] !== '' ? $_POST['sueldos_proveedor_id'][$key] : null,
            'fecha'        => $_POST['sueldos_fecha'][$key] !== '' ? $_POST['sueldos_fecha'][$key] : null,
            'descripcion'  => $_POST['sueldos_concepto'][$key] !== '' ? $_POST['sueldos_concepto'][$key] : '',
            'cantidad'     => $_POST['sueldos_cantidad'][$key] !== '' ? $_POST['sueldos_cantidad'][$key] : 0,
            'importe'      => $_POST['sueldos_importe'][$key] !== '' ? $_POST['sueldos_importe'][$key] : 0,
            'comprobacion' => $_POST['sueldos_comprobacion'][$key] == 'true' ? 't' : 'f',
          ), "id = {$_POST['sueldos_id_sueldo'][$key]}");
        } else {
          $sueldos[] = array(
            'id_estado'    => $id_estado,
            'id_proveedor' => $_POST['sueldos_proveedor_id'][$key] !== '' ? $_POST['sueldos_proveedor_id'][$key] : null,
            'fecha'        => $_POST['sueldos_fecha'][$key] !== '' ? $_POST['sueldos_fecha'][$key] : null,
            'descripcion'  => $_POST['sueldos_concepto'][$key] !== '' ? $_POST['sueldos_concepto'][$key] : '',
            'cantidad'     => $_POST['sueldos_cantidad'][$key] !== '' ? $_POST['sueldos_cantidad'][$key] : 0,
            'importe'      => $_POST['sueldos_importe'][$key] !== '' ? $_POST['sueldos_importe'][$key] : 0,
            'comprobacion' => $_POST['sueldos_comprobacion'][$key] == 'true' ? 't' : 'f',
          );
        }
      }
      if(count($sueldos) > 0)
        $this->db->insert_batch('otros.estado_resultado_trans_sueldos', $sueldos);
    }

    $repmant = array();
    // $this->db->delete('otros.estado_resultado_trans_rep_mtto', "id_estado = {$id_estado}");
    if (!empty($_POST['repmant_proveedor'])) {
      foreach ($_POST['repmant_proveedor'] as $key => $descripcion)
      {
        $id_cod = intval($_POST['repmant_codg_id'][$key]);
        if ($id_cod == 0) {
          if ($_POST['repmant_del'][$key] == 'true' && $_POST['repmant_idrm'][$key] != '') {
            $this->db->delete('otros.estado_resultado_trans_rep_mtto', "id = '{$_POST['repmant_idrm'][$key]}'");
          } else {
            $passes = false;
            $msg = '34';
            // $id_cod = $this->addCods($_POST['gastos_codg'][$key]);
          }
        } else {
          if ($_POST['repmant_del'][$key] == 'true' && $_POST['repmant_idrm'][$key] != '') {
            $this->db->delete('otros.estado_resultado_trans_rep_mtto', "id = '{$_POST['repmant_idrm'][$key]}'");
          } elseif ($_POST['repmant_idrm'][$key] != '') {
            $this->db->update('otros.estado_resultado_trans_rep_mtto', array(
              'id_estado'    => $id_estado,
              'id_cod'       => $id_cod,
              'id_compra'    => $_POST['repmant_id'][$key] !== '' ? $_POST['repmant_id'][$key] : null,
              'comprobacion' => $_POST['repmant_comprobacion'][$key] == 'true' ? 't' : 'f',
              'fecha'        => $_POST['repmant_fecha'][$key] !== '' ? $_POST['repmant_fecha'][$key] : null,
              'folio'        => $_POST['repmant_numero'][$key] !== '' ? $_POST['repmant_numero'][$key] : null,
              'proveedor'    => $_POST['repmant_proveedor'][$key] !== '' ? $_POST['repmant_proveedor'][$key] : null,
              'concepto'     => $_POST['repmant_concepto'][$key] !== '' ? $_POST['repmant_concepto'][$key] : null,
              'subtotal'     => $_POST['repmant_subtotal'][$key] !== '' ? $_POST['repmant_subtotal'][$key] : null,
              'iva'          => $_POST['repmant_iva'][$key] !== '' ? $_POST['repmant_iva'][$key] : null,
              'importe'      => $_POST['repmant_importe'][$key] !== '' ? $_POST['repmant_importe'][$key] : null,
            ), "id = '{$_POST['repmant_idrm'][$key]}'");
          } else {
            $repmant[] = array(
              'id_estado'    => $id_estado,
              'id_cod'       => $id_cod,
              'id_compra'    => $_POST['repmant_id'][$key] !== '' ? $_POST['repmant_id'][$key] : null,
              'comprobacion' => $_POST['repmant_comprobacion'][$key] == 'true' ? 't' : 'f',
              'fecha'        => $_POST['repmant_fecha'][$key] !== '' ? $_POST['repmant_fecha'][$key] : null,
              'folio'        => $_POST['repmant_numero'][$key] !== '' ? $_POST['repmant_numero'][$key] : null,
              'proveedor'    => $_POST['repmant_proveedor'][$key] !== '' ? $_POST['repmant_proveedor'][$key] : null,
              'concepto'     => $_POST['repmant_concepto'][$key] !== '' ? $_POST['repmant_concepto'][$key] : null,
              'subtotal'     => $_POST['repmant_subtotal'][$key] !== '' ? $_POST['repmant_subtotal'][$key] : null,
              'iva'          => $_POST['repmant_iva'][$key] !== '' ? $_POST['repmant_iva'][$key] : null,
              'importe'      => $_POST['repmant_importe'][$key] !== '' ? $_POST['repmant_importe'][$key] : null,
            );
          }
        }
      }

      if(count($repmant) > 0){
        $this->db->insert_batch('otros.estado_resultado_trans_rep_mtto', $repmant);
      }
    }

    $gastos = array();
    if (!empty($_POST['gastos_proveedor'])) {
      foreach ($_POST['gastos_proveedor'] as $key => $descripcion)
      {
        $id_cod = intval($_POST['gastos_codg_id'][$key]);
        if ($id_cod == 0) {
          $passes = false;
          $msg = '33';
          // $id_cod = $this->addCods($_POST['gastos_codg'][$key]);
        } else {
          if ($_POST['gastos_del'][$key] == 'true' && $_POST['gastos_id_gasto'][$key] > 0) {
            $this->db->delete('otros.estado_resultado_trans_gastos', "id = {$_POST['gastos_id_gasto'][$key]}");
          } elseif ($_POST['gastos_id_gasto'][$key] > 0) {
            $this->db->update('otros.estado_resultado_trans_gastos', array(
              'id_estado'    => $id_estado,
              'id_proveedor' => $_POST['gastos_proveedor_id'][$key] !== '' ? $_POST['gastos_proveedor_id'][$key] : null,
              'id_cod'       => $id_cod,
              'fecha'        => $_POST['gastos_fecha'][$key] !== '' ? $_POST['gastos_fecha'][$key] : null,
              'subtotal'     => $_POST['gastos_subtotal'][$key] !== '' ? $_POST['gastos_subtotal'][$key] : 0,
              'iva'          => $_POST['gastos_iva'][$key] !== '' ? $_POST['gastos_iva'][$key] : 0,
              'importe'      => $_POST['gastos_importe'][$key] !== '' ? $_POST['gastos_importe'][$key] : 0,
              'cantidad'     => 0,
              'precio'       => 0,
              'comprobacion' => $_POST['gastos_comprobacion'][$key] == 'true' ? 't' : 'f',
              'id_tipo'      => $_POST['gastos_tipo'][$key] !== '' ? $_POST['gastos_tipo'][$key] : null,
            ), "id = {$_POST['gastos_id_gasto'][$key]}");
          } else {
            $gastos[] = array(
              'id_estado'    => $id_estado,
              'id_proveedor' => $_POST['gastos_proveedor_id'][$key] !== '' ? $_POST['gastos_proveedor_id'][$key] : null,
              'id_cod'       => $id_cod,
              'fecha'        => $_POST['gastos_fecha'][$key] !== '' ? $_POST['gastos_fecha'][$key] : null,
              'subtotal'     => $_POST['gastos_subtotal'][$key] !== '' ? $_POST['gastos_subtotal'][$key] : 0,
              'iva'          => $_POST['gastos_iva'][$key] !== '' ? $_POST['gastos_iva'][$key] : 0,
              'importe'      => $_POST['gastos_importe'][$key] !== '' ? $_POST['gastos_importe'][$key] : 0,
              'cantidad'     => 0,
              'precio'       => 0,
              'comprobacion' => $_POST['gastos_comprobacion'][$key] == 'true' ? 't' : 'f',
              'id_compra'    => $_POST['gastos_id_compra'][$key] !== '' ? $_POST['gastos_id_compra'][$key] : null,
              'folio'        => $_POST['gastos_folio'][$key] !== '' ? $_POST['gastos_folio'][$key] : '',
              'id_tipo'      => $_POST['gastos_tipo'][$key] !== '' ? $_POST['gastos_tipo'][$key] : null,
            );
          }
        }
      }
      if(count($gastos) > 0) {
        $this->db->insert_batch('otros.estado_resultado_trans_gastos', $gastos);
      }
    }

    return array('passes' => $passes, 'id_estado' => $id_estado, 'msg' => $msg);
  }
  /**
   * Obtiene el folio de acuerdo a la serie seleccionada
   */
  public function getFolio($empresa, $activo)
  {
    $res = $this->db->select('folio')->
                      from('otros.estado_resultado_trans')->
                      where("id_empresa = {$empresa}")->
                      where("id_activo = '{$activo}'")->
                      order_by('folio', 'DESC')->
                      limit(1)->get()->row();

    $folio = (isset($res->folio)? $res->folio: 0)+1;

    return $folio;
  }



  public function maquinasGet($perpage = '40', $status = null)
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
    if ($this->input->get('fnombre') != '')
    {
      $sql .= " AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%'";
    }

    $_GET['fstatus'] = $status? $status: $this->input->get('fstatus');
    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT id_maquina, nombre, status
        FROM otros.pg_maquinas
        WHERE 1 = 1 {$sql}
        ORDER BY (nombre) ASC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'conceptos'     => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['conceptos'] = $res->result();

    return $response;
  }

  public function maquinasAgregar($data)
  {
    $insertData = array(
      'nombre' => $data['nombre']
    );

    $this->db->insert('otros.pg_maquinas', $insertData);

    return true;
  }

  public function maquinasInfo($id)
  {
    $query = $this->db->query(
      "SELECT id_maquina, nombre, status
        FROM otros.pg_maquinas
        WHERE id_maquina = {$id}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();
    }

    return $data;
  }

  public function maquinasModificar($id, $data)
  {
    $updateData = array(
      'nombre' => $data['nombre'],
    );

    $this->db->update('otros.pg_maquinas', $updateData, array('id_maquina' => $id));

    return true;
  }

  public function maquinasEliminar($id)
  {
    $this->db->update('otros.pg_maquinas', array('status' => 'f'), array('id_maquina' => $id));

    return true;
  }


  public function moldesGet($perpage = '40', $status = null)
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
    if ($this->input->get('fnombre') != '')
    {
      $sql .= " AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%'";
    }

    $_GET['fstatus'] = $status? $status: $this->input->get('fstatus');
    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT id_molde, nombre, status
        FROM otros.pg_moldes
        WHERE 1 = 1 {$sql}
        ORDER BY (nombre) ASC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'conceptos'     => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['conceptos'] = $res->result();

    return $response;
  }

  public function moldesAgregar($data)
  {
    $insertData = array(
      'nombre' => $data['nombre']
    );

    $this->db->insert('otros.pg_moldes', $insertData);

    return true;
  }

  public function moldesInfo($id)
  {
    $query = $this->db->query(
      "SELECT id_molde, nombre, status
        FROM otros.pg_moldes
        WHERE id_molde = {$id}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();
    }

    return $data;
  }

  public function moldesModificar($id, $data)
  {
    $updateData = array(
      'nombre' => $data['nombre'],
    );

    $this->db->update('otros.pg_moldes', $updateData, array('id_molde' => $id));

    return true;
  }

  public function moldesEliminar($id)
  {
    $this->db->update('otros.pg_moldes', array('status' => 'f'), array('id_molde' => $id));

    return true;
  }

  public function gruposGet($perpage = '40', $status = null)
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
    if ($this->input->get('fnombre') != '')
    {
      $sql .= " AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%'";
    }

    $_GET['fstatus'] = $status? $status: $this->input->get('fstatus');
    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT id_grupo, nombre, status
        FROM otros.pg_grupos
        WHERE 1 = 1 {$sql}
        ORDER BY (nombre) ASC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'conceptos'     => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['conceptos'] = $res->result();

    return $response;
  }

}
