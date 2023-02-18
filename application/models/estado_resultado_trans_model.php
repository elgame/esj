<?php
class estado_resultado_trans_model extends privilegios_model{

	function __construct(){
		parent::__construct();
    $this->load->model('bitacora_model');
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
	public function getEstadosRes($perpage = '40', $sql2='')
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
      $sql = " AND Date(er.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(er.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(er.fecha) = '".$this->input->get('ffecha2')."'";

    if($this->input->get('fbuscar') != '' && is_numeric($this->input->get('fbuscar')))
      $sql .= " AND er.folio = '".$this->input->get('fbuscar')."'";
    // if($this->input->get('fstatus') != '')
    //   $sql .= " AND er.status = '".$this->input->get('fstatus')."'";
    $empresa_default = $this->empresas_model->getDefaultEmpresa();
    if($this->input->get('did_empresa') != '') {
      $sql .= " AND er.id_empresa = '".$this->input->get('did_empresa')."'";
    } else {
      $sql .= " AND er.id_empresa = '".$empresa_default->id_empresa."'";
    }

    if($this->input->get('fbuscar') != '')
      $sql .= " AND (
        lower(c.nombre) LIKE '%".mb_strtolower($this->input->get('fbuscar'), 'UTF-8')."%' OR
        lower(p.nombre) LIKE '%".mb_strtolower($this->input->get('fbuscar'), 'UTF-8')."%' OR
        er.folio = ".intval($this->input->get('fbuscar')).")";

    $query = BDUtil::pagination("
        SELECT er.id, er.fecha, er.folio, c.nombre AS chofer,
          e.nombre_fiscal AS empresa, p.nombre AS activo, er.status
        FROM otros.estado_resultado_trans AS er
          INNER JOIN empresas AS e ON e.id_empresa = er.id_empresa
          INNER JOIN choferes AS c ON c.id_chofer = er.id_chofer
          INNER JOIN productos AS p ON p.id_producto = er.id_activo
        WHERE er.status = 't' ".$sql.$sql2."
        ORDER BY er.fecha DESC, er.folio DESC
        ", $params, true);
    $res = $this->db->query($query['query']);

    $response = array(
        'res_trans'      => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['res_trans'] = $res->result();

    return $response;
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
        (c.serie || c.folio) AS folio, c.concepto, c.subtotal, c.total
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
    $res = $this->db->query("
        SELECT *
        FROM otros.estado_resultado_trans_cods
        WHERE status = 't' AND lower(nombre) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%'
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
    $this->load->model('clientes_model');
    $this->load->model('clasificaciones_model');

    $datosFactura = array(
      'id_chofer'      => $this->input->post('did_chofer'),
      'id_activo'      => $this->input->post('did_activo'),
      'id_empresa'     => $this->input->post('did_empresa'),
      'id_creo'        => $this->session->userdata('id_usuario'),
      'fecha'          => $this->input->post('dfecha'),
      'folio'          => $this->getFolio($this->input->post('did_empresa'), $this->input->post('did_activo')),
      'km_rec'         => floatval($this->input->post('dkm_rec')),
      'vel_max'        => floatval($this->input->post('dvel_max')),
      'rep_lt_hist'    => floatval($this->input->post('drep_lt_hist')),
      'rend_km_gps'    => floatval($this->input->post('rend_km_gps')),
      'rend_actual'    => floatval($this->input->post('rend_actual')),
      'rend_lts'       => floatval($this->input->post('rend_lts')),
      'rend_precio'    => floatval($this->input->post('rend_precio')),
      'rend_thrs_trab' => floatval($this->input->post('rend_thrs_trab')),
      'rend_thrs_lts'  => floatval($this->input->post('rend_thrs_lts')),
      'rend_thrs_hxl'  => floatval($this->input->post('rend_thrs_hxl')),
      'destino'        => $this->input->post('destino'),
      'id_gasto'       => $this->input->post('did_gasto') > 0? $this->input->post('did_gasto'): null,
      'gasto_monto'    => $this->input->post('gasto_monto') > 0? $this->input->post('gasto_monto'): 0,
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
        $repmant[] = array(
          'id_estado'    => $id_estado,
          'id_compra'    => $_POST['repmant_id'][$key] !== '' ? $_POST['repmant_id'][$key] : null,
          'comprobacion' => $_POST['repmant_comprobacion'][$key] == 'true' ? 't' : 'f',
          'fecha'        => $_POST['repmant_fecha'][$key] !== '' ? $_POST['repmant_fecha'][$key] : null,
          'folio'        => $_POST['repmant_numero'][$key] !== '' ? $_POST['repmant_numero'][$key] : null,
          'proveedor'    => $_POST['repmant_proveedor'][$key] !== '' ? $_POST['repmant_proveedor'][$key] : null,
          'concepto'     => $_POST['repmant_concepto'][$key] !== '' ? $_POST['repmant_concepto'][$key] : null,
          'subtotal'     => $_POST['repmant_importe'][$key] !== '' ? $_POST['repmant_importe'][$key] : null,
        );
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
          $id_cod = $this->addCods($_POST['gastos_codg'][$key]);
        }

        $gastos[] = array(
          'id_estado'    => $id_estado,
          'id_proveedor' => $_POST['gastos_proveedor_id'][$key] !== '' ? $_POST['gastos_proveedor_id'][$key] : null,
          'id_cod'       => $id_cod,
          'fecha'        => $_POST['gastos_fecha'][$key] !== '' ? $_POST['gastos_fecha'][$key] : null,
          'importe'      => $_POST['gastos_importe'][$key] !== '' ? $_POST['gastos_importe'][$key] : 0,
          'cantidad'     => 0,
          'precio'       => 0,
          'comprobacion' => $_POST['gastos_comprobacion'][$key] == 'true' ? 't' : 'f',
          'id_compra'    => $_POST['gastos_id_compra'][$key] !== '' ? $_POST['gastos_id_compra'][$key] : null,
          'folio'        => $_POST['gastos_folio'][$key] !== '' ? $_POST['gastos_folio'][$key] : '',
        );
      }

      if(count($gastos) > 0)
        $this->db->insert_batch('otros.estado_resultado_trans_gastos', $gastos);
    }

    return array('passes' => true, 'id_estado' => $id_estado);
  }

  public function updateEstadoResult($id_estado)
  {
    $datosFactura = array(
      'id_chofer'      => $this->input->post('did_chofer'),
      'id_activo'      => $this->input->post('did_activo'),
      'id_empresa'     => $this->input->post('did_empresa'),
      'id_creo'        => $this->session->userdata('id_usuario'),
      'fecha'          => $this->input->post('dfecha'),
      // 'folio'       => $this->getFolio($this->input->post('did_empresa'), $this->input->post('did_activo')),
      'km_rec'         => floatval($this->input->post('dkm_rec')),
      'vel_max'        => floatval($this->input->post('dvel_max')),
      'rep_lt_hist'    => floatval($this->input->post('drep_lt_hist')),
      'rend_km_gps'    => floatval($this->input->post('rend_km_gps')),
      'rend_actual'    => floatval($this->input->post('rend_actual')),
      'rend_lts'       => floatval($this->input->post('rend_lts')),
      'rend_precio'    => floatval($this->input->post('rend_precio')),
      'rend_thrs_trab' => floatval($this->input->post('rend_thrs_trab')),
      'rend_thrs_lts'  => floatval($this->input->post('rend_thrs_lts')),
      'rend_thrs_hxl'  => floatval($this->input->post('rend_thrs_hxl')),
      'destino'        => $this->input->post('destino'),
      'id_gasto'       => $this->input->post('did_gasto') > 0? $this->input->post('did_gasto'): null,
      'gasto_monto'    => $this->input->post('gasto_monto') > 0? $this->input->post('gasto_monto'): 0,

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
            'importe'      => $_POST['sueldos_importe'][$key] !== '' ? $_POST['sueldos_importe'][$key] : 0,
            'comprobacion' => $_POST['sueldos_comprobacion'][$key] == 'true' ? 't' : 'f',
          ), "id = {$_POST['sueldos_id_sueldo'][$key]}");
        } else {
          $sueldos[] = array(
            'id_estado'    => $id_estado,
            'id_proveedor' => $_POST['sueldos_proveedor_id'][$key] !== '' ? $_POST['sueldos_proveedor_id'][$key] : null,
            'fecha'        => $_POST['sueldos_fecha'][$key] !== '' ? $_POST['sueldos_fecha'][$key] : null,
            'descripcion'  => $_POST['sueldos_concepto'][$key] !== '' ? $_POST['sueldos_concepto'][$key] : '',
            'importe'      => $_POST['sueldos_importe'][$key] !== '' ? $_POST['sueldos_importe'][$key] : 0,
            'comprobacion' => $_POST['sueldos_comprobacion'][$key] == 'true' ? 't' : 'f',
          );
        }
      }
      if(count($sueldos) > 0)
        $this->db->insert_batch('otros.estado_resultado_trans_sueldos', $sueldos);
    }

    $repmant = array();
    $this->db->delete('otros.estado_resultado_trans_rep_mtto', "id_estado = {$id_estado}");
    if (!empty($_POST['repmant_proveedor'])) {
      foreach ($_POST['repmant_proveedor'] as $key => $descripcion)
      {
        $repmant[] = array(
          'id_estado'    => $id_estado,
          'id_compra'    => $_POST['repmant_id'][$key] !== '' ? $_POST['repmant_id'][$key] : null,
          'comprobacion' => $_POST['repmant_comprobacion'][$key] == 'true' ? 't' : 'f',
          'fecha'        => $_POST['repmant_fecha'][$key] !== '' ? $_POST['repmant_fecha'][$key] : null,
          'folio'        => $_POST['repmant_numero'][$key] !== '' ? $_POST['repmant_numero'][$key] : null,
          'proveedor'    => $_POST['repmant_proveedor'][$key] !== '' ? $_POST['repmant_proveedor'][$key] : null,
          'concepto'     => $_POST['repmant_concepto'][$key] !== '' ? $_POST['repmant_concepto'][$key] : null,
          'subtotal'     => $_POST['repmant_importe'][$key] !== '' ? $_POST['repmant_importe'][$key] : null,
        );
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
          $id_cod = $this->addCods($_POST['gastos_codg'][$key]);
        }

        if ($_POST['gastos_del'][$key] == 'true' && $_POST['gastos_id_gasto'][$key] > 0) {
          $this->db->delete('otros.estado_resultado_trans_gastos', "id = {$_POST['gastos_id_gasto'][$key]}");
        } elseif ($_POST['gastos_id_gasto'][$key] > 0) {
          $this->db->update('otros.estado_resultado_trans_gastos', array(
            'id_estado'    => $id_estado,
            'id_proveedor' => $_POST['gastos_proveedor_id'][$key] !== '' ? $_POST['gastos_proveedor_id'][$key] : null,
            'id_cod'       => $id_cod,
            'fecha'        => $_POST['gastos_fecha'][$key] !== '' ? $_POST['gastos_fecha'][$key] : null,
            'importe'      => $_POST['gastos_importe'][$key] !== '' ? $_POST['gastos_importe'][$key] : 0,
            'cantidad'     => 0,
            'precio'       => 0,
            'comprobacion' => $_POST['gastos_comprobacion'][$key] == 'true' ? 't' : 'f',
          ), "id = {$_POST['gastos_id_gasto'][$key]}");
        } else {
          $gastos[] = array(
            'id_estado'    => $id_estado,
            'id_proveedor' => $_POST['gastos_proveedor_id'][$key] !== '' ? $_POST['gastos_proveedor_id'][$key] : null,
            'id_cod'       => $id_cod,
            'fecha'        => $_POST['gastos_fecha'][$key] !== '' ? $_POST['gastos_fecha'][$key] : null,
            'importe'      => $_POST['gastos_importe'][$key] !== '' ? $_POST['gastos_importe'][$key] : 0,
            'cantidad'     => 0,
            'precio'       => 0,
            'comprobacion' => $_POST['gastos_comprobacion'][$key] == 'true' ? 't' : 'f',
            'id_compra'    => $_POST['gastos_id_compra'][$key] !== '' ? $_POST['gastos_id_compra'][$key] : null,
            'folio'        => $_POST['gastos_folio'][$key] !== '' ? $_POST['gastos_folio'][$key] : '',
          );
        }
      }
      if(count($gastos) > 0) {
        $this->db->insert_batch('otros.estado_resultado_trans_gastos', $gastos);
      }
    }

    return array('passes' => true, 'id_estado' => $id_estado);
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

  public function addCods($name)
  {
    $query = $this->db->query("SELECT id, count(id) FROM otros.estado_resultado_trans_cods
      WHERE regexp_replace(translate(Lower(nombre), 'áâãäåāăąèééêëēĕėęěìíîïìĩīĭóôõöōŏőùúûüũūŭů',
        'aaaaaaaaeeeeeeeeeeiiiiiiiiooooooouuuuuuuu'), '[^\w]+', '', 'g') =
        regexp_replace(translate(Lower('{$name}'),
        'áâãäåāăąèééêëēĕėęěìíîïìĩīĭóôõöōŏőùúûüũūŭů',
        'aaaaaaaaeeeeeeeeeeiiiiiiiiooooooouuuuuuuu'), '[^\w]+', '', 'g')
      GROUP BY id")->row();
    // echo "<pre>";
    // var_dump(count($query));
    // echo "</pre>";exit;
    if (count($query) == 0 || $query->count == 0) {
      $cod = [
        'nombre' => mb_strtoupper($name, 'UTF-8')
      ];
      $this->db->insert('otros.estado_resultado_trans_cods', $cod);
      $id_cod = $this->db->insert_id('otros.estado_resultado_trans_cods_id_seq');
    } else {
      $id_cod = $query->id;
    }

     return $id_cod;
  }

	/**
	 * Obtiene la informacion de una factura
	 */
	public function getInfoVenta($id, $info_basic=false, $full=false)
  {
		$res = $this->db
      ->select("f.*")
      ->from('otros.estado_resultado_trans as f')
      // ->join('cajachica_gastos g', "g.id_gasto = f.id_gasto", 'left')
      ->where("f.id = {$id}")
      ->get();

    if($res->num_rows() > 0)
    {
			$response['info'] = $res->row();
			$res->free_result();

      if($info_basic)
				return $response;

      // Carga la info de la empresa.
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($response['info']->id_empresa);
      $response['info']->empresa = $empresa['info'];

			$this->load->model('choferes_model');
			$prov = $this->choferes_model->getChoferInfo($response['info']->id_chofer, false, true);
			$response['info']->chofer = $prov['info'];

      $this->load->model('productos_model');
      $prov = $this->productos_model->getProductosInfo($response['info']->id_activo);
      $response['info']->activo = $prov['info'];

      if ($full) {
        $res = $this->db
          ->select('v.id_remision, Date(f.fecha) AS fecha, (f.serie || f.folio) AS folio,
            c.id_cliente, c.nombre_fiscal AS cliente, f.subtotal, f.total, v.comprobacion,
            fp.id_clasificacion, fp.cantidad, fp.descripcion, fp.precio_unitario, fp.importe, fp.iva, fp.unidad')
          ->from('otros.estado_resultado_trans_ventas v')
            ->join('facturacion f', 'v.id_remision = f.id_factura', 'inner')
            ->join('clientes c', 'c.id_cliente = f.id_cliente', 'inner')
            ->join('facturacion_productos fp', 'fp.id_factura = f.id_factura', 'inner')
          ->where('v.id_estado = ' . $id)->order_by('v.id_remision', 'asc')
          ->get();
      } else {
        $res = $this->db
          ->select('v.id_remision, Date(f.fecha) AS fecha, (f.serie || f.folio) AS folio,
            c.id_cliente, c.nombre_fiscal AS cliente, f.subtotal, f.total, v.comprobacion')
          ->from('otros.estado_resultado_trans_ventas v')
          ->join('facturacion f', 'v.id_remision = f.id_factura', 'inner')
          ->join('clientes c', 'c.id_cliente = f.id_cliente', 'inner')
          ->where('v.id_estado = ' . $id)->order_by('v.id_remision', 'asc')
          ->get();
      }
      $response['remisiones'] = $res->result();

      $res = $this->db
        ->select('s.id, p.id_proveedor, Date(s.fecha) AS fecha, s.comprobacion,
          s.descripcion, s.importe, p.nombre_fiscal AS proveedor')
        ->from('otros.estado_resultado_trans_sueldos s')
        ->join('proveedores p', 'p.id_proveedor = s.id_proveedor', 'inner')
        ->where('s.id_estado = ' . $id)->order_by('s.id', 'asc')
        ->get();
      $response['sueldos'] = $res->result();

      $res = $this->db->query("SELECT v.id_compra AS id_compra, Coalesce(Date(f.fecha), v.fecha ) AS fecha,
          Coalesce((f.serie || f.folio), v.folio ) AS folio, p.id_proveedor,
          Coalesce(p.nombre_fiscal, v.proveedor ) AS proveedor,
          Coalesce(f.subtotal, v.subtotal ) AS subtotal, Coalesce(f.total, v.subtotal ) AS total,
          Coalesce(f.importe_iva, 0::double precision ) AS importe_iva,
          Coalesce(f.concepto, v.concepto ) AS concepto, v.comprobacion
        FROM otros.estado_resultado_trans_rep_mtto v
          LEFT JOIN compras f ON v.id_compra = f.id_compra
          LEFT JOIN proveedores p ON p.id_proveedor = f.id_proveedor
        WHERE v.id_estado = {$id}
        ORDER BY fecha asc");
      $response['repmant'] = $res->result();

      $res = $this->db->query("SELECT v.id, v.id_compra AS id_compra,
          Coalesce((f.serie || f.folio), v.folio ) AS folio, p.id_proveedor,
          p.nombre_fiscal AS proveedor, Coalesce(Date(f.fecha), v.fecha ) AS fecha,
          Coalesce(f.subtotal, v.importe ) AS subtotal, Coalesce(f.total, v.importe ) AS total,
          Coalesce(f.importe_iva, 0::double precision ) AS importe_iva,
          c.nombre AS codg, c.id AS id_codg, v.comprobacion
        FROM otros.estado_resultado_trans_gastos v
          INNER JOIN proveedores p ON p.id_proveedor = v.id_proveedor
          INNER JOIN otros.estado_resultado_trans_cods c ON c.id = v.id_cod
          LEFT JOIN compras f ON v.id_compra = f.id_compra
        WHERE v.id_estado = {$id}
        ORDER BY fecha asc");
      // echo $this->db->last_query();
      $response['gastos'] = $res->result();

			return $response;
		}else
			return false;
	}

  /**
   * Cancela una nota, la elimina
   */
  public function cancelar($id){
    $this->db->update('otros.estado_resultado_trans', array('status' => 'f'), "id = '{$id}'");

    return array(true, '');
  }

  public function print($id)
  {
    $caja = $this->getInfoVenta($id, false, true);

    $subtitulo = '';
    $logo = 'images/logistic.png';

    // echo "<pre>";
    //   var_dump($caja);
    // echo "</pre>";exit;
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->limiteY = 235; //limite de alto

    // Reporte caja
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(140));
    $pdf->Row(array(mb_strtoupper("Estado de Resultados en EQUIPO DE TRANSPORTE", 'UTF-8')), true, true, null, 3);

    $pdf->Image(APPPATH.(str_replace(APPPATH, '', $logo)), 160, 5, 50);
    $pdf->Ln(20);

    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);

    // Fecha
    $pdf->SetXY(6, $pdf->GetY() - 20);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('FECHA: ' . MyString::fechaAT($caja['info']->fecha)), false, false);

    // Fecha dia
    $pdf->SetXY(6, $pdf->GetY() - 2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('IMPRESION: ' . MyString::fechaAT(Date("Y-m-d")). ' '.Date("H:i")), false, false);

    $pdf->SetXY(6, $pdf->GetY() );
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('FOLIO: '.MyString::formatoNumero($caja['info']->folio, 0, '', false)), false, false);

    $pdf->SetXY(6, $pdf->GetY() );
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('CHOFER: '.$caja['info']->chofer->nombre), false, false);

    $pdf->SetXY(129, $pdf->GetY()-13 );
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(80));
    $pdf->Row(array($caja['info']->activo->nombre), false, false);

    $pdf->SetXY(6, $pdf->GetY()+7 );
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('DESTINO: '.$caja['info']->destino), false, false);

    $comprobacion = ['t' => 'Si', 'f' => 'No'];

    $ttotalRemisiones = 0;
    $ttotalRemisionesEf = 0;
    $pdf->SetXY(6, $pdf->GetY()+5);
    if (count($caja['remisiones']) > 0) {
      $pdf->SetFont('Arial','B', 6);
      $pdf->SetAligns(array('L', 'C'));
      $pdf->SetWidths(array(206));
      $pdf->Row(array('VENTAS'), false, false);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetX(6);
      $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'));
      $pdf->SetWidths(array(5, 15, 18, 43, 42, 15, 15, 15, 18));
      $pdf->Row(array('C', 'FECHA', 'FOLIO', 'CLIENTE', 'CONCEPTO', 'UNIDAD', 'CANTIDAD', 'PRECIO', 'IMPORTE'), true, true);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('C', 'C', 'L', 'L', 'L', 'L', 'R', 'R', 'R'));
      $pdf->SetWidths(array(5, 15, 18, 43, 42, 15, 15, 15, 18));
      $auxrem = 0;
      foreach ($caja['remisiones'] as $key => $rem)
      {
        $pdf->SetX(6);

        $pdf->Row(array(
          ($auxrem != $rem->id_remision? $comprobacion[$rem->comprobacion]: ''),
          ($auxrem != $rem->id_remision? $rem->fecha: ''),
          ($auxrem != $rem->id_remision? $rem->folio: ''),
          ($auxrem != $rem->id_remision? $rem->cliente: ''),
          $rem->descripcion,
          $rem->unidad,
          MyString::formatoNumero($rem->cantidad, 2, '', false),
          MyString::formatoNumero($rem->precio_unitario, 2, '', false),
          MyString::formatoNumero($rem->importe, 2, '', false)
        ), false, 'B');

        $auxrem = $rem->id_remision;
        $ttotalRemisiones += floatval($rem->importe);
        if ($rem->comprobacion == 't') {
          $ttotalRemisionesEf += floatval($rem->importe);
        }
      }
    }
    if ($ttotalRemisiones > 0) {
      $pdf->SetTextColor(0, 0, 0);

      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(20));
      $pdf->SetFont('Arial', 'B', 6);
      $pdf->SetXY(192, $pdf->GetY()-10);
      $pdf->Row(array('Ventas'), false, 'B');
      $pdf->SetX(192);
      $pdf->Row(array(MyString::formatoNumero($ttotalRemisiones, 2, '$', false)), false, 'B');
    }

    $ttotalGastos = 0;
    $ttotalSueldos = 0;
    $pdf->SetXY(6, $pdf->GetY()+5);
    if (count($caja['sueldos']) > 0) {
      $pdf->SetFont('Arial','B', 6);
      $pdf->SetAligns(array('L', 'C'));
      $pdf->SetWidths(array(206));
      $pdf->Row(array('SUELDOS'), false, false);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetX(6);
      $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
      $pdf->SetWidths(array(5, 15, 75, 74, 18));
      $pdf->Row(array('C', 'FECHA', 'PROVEEDOR', 'DESCRIPCION', 'IMPORTE'), true, true);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('C', 'C', 'L', 'L', 'R'));
      $pdf->SetWidths(array(5, 15, 75, 74, 18));
      foreach ($caja['sueldos'] as $key => $sueldo)
      {
        $pdf->SetX(6);

        $pdf->Row(array(
          $comprobacion[$sueldo->comprobacion],
          $sueldo->fecha,
          $sueldo->proveedor,
          $sueldo->descripcion,
          MyString::formatoNumero($sueldo->importe, 2, '', false)
        ), false, 'B');

        $ttotalGastos += floatval($sueldo->importe);
        if ($sueldo->comprobacion == 't') {
          $ttotalSueldos += floatval($sueldo->importe);
        }
      }
    }

    $pdf->SetXY(6, $pdf->GetY()+5);
    $ttotalRepMantEf = $ttotalRepMant = 0;
    if (count($caja['repmant']) > 0) {
      $pdf->SetFont('Arial','B', 6);
      $pdf->SetAligns(array('L', 'C'));
      $pdf->SetWidths(array(206));
      $pdf->Row(array('REP Y MTTO DE EQUIPO TRASPORTE'), false, false);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetX(6);
      $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C', 'C'));
      $pdf->SetWidths(array(5, 15, 18, 66, 65, 18));
      $pdf->Row(array('C', 'FECHA', 'FOLIO', 'PROVEEDOR', 'CONCEPTO', 'IMPORTE'), true, true);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('C', 'C', 'L', 'L', 'L', 'R'));
      $pdf->SetWidths(array(5, 15, 18, 66, 65, 18));
      $auxrem = 0;
      foreach ($caja['repmant'] as $key => $rem)
      {
        $pdf->SetX(6);

        $pdf->Row(array(
          $comprobacion[$rem->comprobacion],
          $rem->fecha,
          $rem->folio,
          $rem->proveedor,
          $rem->concepto,
          MyString::formatoNumero($rem->subtotal, 2, '', false)
        ), false, 'B');

        $ttotalGastos += floatval($rem->subtotal);
        $ttotalRepMant += floatval($rem->total);
        if ($rem->comprobacion == 't') {
          $ttotalRepMantEf += floatval($rem->total);
        }
      }
    }

    $ttotalGastosEf = 0;
    $pdf->SetXY(6, $pdf->GetY()+5);
    if (count($caja['gastos']) > 0) {
      $pdf->SetFont('Arial','B', 6);
      $pdf->SetAligns(array('L', 'C'));
      $pdf->SetWidths(array(206));
      $pdf->Row(array('GASTOS GENERALES'), false, false);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetX(6);
      $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C', 'C'));
      $pdf->SetWidths(array(5, 15, 15, 65, 69, 18));
      $pdf->Row(array('C', 'FECHA', 'FOLIO', 'PROVEEDOR', 'DESCRIPCION', 'IMPORTE'), true, true);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('C', 'C', 'L', 'L', 'L', 'R'));
      $pdf->SetWidths(array(5, 15, 15, 65, 69, 18));
      foreach ($caja['gastos'] as $key => $gasto)
      {
        $pdf->SetX(6);

        $pdf->Row(array(
          $comprobacion[$gasto->comprobacion],
          $gasto->fecha,
          $gasto->folio,
          $gasto->proveedor,
          $gasto->codg,
          MyString::formatoNumero($gasto->subtotal, 2, '', false)
        ), false, 'B');

        $ttotalGastos += floatval($gasto->subtotal);
        if ($gasto->comprobacion == 't') {
          $ttotalGastosEf += floatval($gasto->subtotal);
        }
      }
    }

    if ($ttotalGastos > 0) {
      $pdf->SetTextColor(0, 0, 0);

      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(20));
      $pdf->SetFont('Arial', 'B', 6);
      $pdf->SetXY(193, $pdf->GetY()-10);
      $pdf->Row(array('Gtos'), false, 'B');
      $pdf->SetX(193);
      $pdf->Row(array(MyString::formatoNumero($ttotalGastos, 2, '$', false)), false, 'B');
    }

    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(25, 25));
    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(163, $pdf->GetY()+5);
    $yaux = $pdf->GetY();
    $pagaux = $pdf->page;
    $pdf->Row(array(' Utilidad Estimada', MyString::formatoNumero($ttotalRemisiones - $ttotalGastos, 2, '$', false)), false, 'B');

    if ($pdf->limiteY <= $pdf->GetY()+15) {
      $pdf->AddPage();
    }
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(20, 20, 20));
    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(85, $pdf->GetY()+5);
    $pdf->Row(array('Term Hrs Trabajadas', 'Term Lts', 'Term Hrs/Lts'), false, true);
    $pdf->SetXY(85, $pdf->GetY());
    $pdf->Row(array($caja['info']->rend_thrs_trab, $caja['info']->rend_thrs_lts, $caja['info']->rend_thrs_hxl), false, true);

    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(20, 20, 20));
    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(150, $pdf->GetY()-12);
    $pdf->Row(array('Km Recorrido', 'Velocidad Max', 'Reposición Lt/Hist'), false, true);
    $pdf->SetXY(150, $pdf->GetY());
    $pdf->Row(array($caja['info']->km_rec, $caja['info']->vel_max, $caja['info']->rep_lt_hist), false, true);

    if ($pdf->limiteY <= $pdf->GetY()+15) {
      $pdf->AddPage();
    }
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(20, 20, 20, 20, 20));
    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(110, $pdf->GetY()+5);
    $pdf->Row(array('Rend Km/Gps', 'Rend Actual', 'Diesel Lts', 'Diesel Precio', 'Diesel Importe'), false, true);
    $pdf->SetXY(110, $pdf->GetY());
    $pdf->Row(array(
      $caja['info']->rend_km_gps,
      $caja['info']->rend_actual,
      $caja['info']->rend_lts,
      $caja['info']->rend_precio,
      ($caja['info']->rend_lts * $caja['info']->rend_precio),
    ), false, true);

    $pdf->page = $pagaux;
    $pdf->SetY($yaux);

    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(60));
    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('COMPROBACION DE GASTOS CAJA 2'), false, true);
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(35, 25));
    $pdf->SetFont('Arial', '', 6);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('Cuenta', 'Importe'), false, true);
    $pdf->chkSaltaPag([6, 6], 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('ANTICIPO', MyString::formatoNumero($caja['info']->gasto_monto, 2, '$', false)), false, true);
    $pdf->chkSaltaPag([6, 6], 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('INGRESO', MyString::formatoNumero($ttotalRemisionesEf, 2, '$', false)), false, true);
    $pdf->chkSaltaPag([6, 6], 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('EGRESOS (-)', MyString::formatoNumero(($ttotalSueldos + $ttotalGastosEf), 2, '$', false)), false, true);
    $pdf->chkSaltaPag([6, 6], 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('REP. GTOS (-)', MyString::formatoNumero($ttotalRepMantEf, 2, '$', false)), false, true);
    $pdf->chkSaltaPag([6, 6], 8);
    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(6, $pdf->GetY());
    $ttotalefectivo = $caja['info']->gasto_monto + $ttotalRemisionesEf - $ttotalSueldos - $ttotalGastosEf - $ttotalRepMantEf;
    $pdf->Row(array('DEV. EFECTIVO', MyString::formatoNumero($ttotalefectivo, 2, '$', false)), false, true);

    $pdf->Output('estado_resultado.pdf', 'I');
  }

  public function getRelFletesData($id_producto=null, $id_almacen=null, $con_req=false, $extras = [])
  {
    $sql_com = $sql_sal = $sql_req = $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');

    $fletes = $this->db->query("SELECT id
      FROM otros.estado_resultado_trans
      WHERE id_empresa = {$_GET['did_empresa']} AND fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
        AND status = 't' AND id_activo = {$_GET['activoId']}")->result();

    $response = [
      'activo' => '',
      'destino' => [''],
      'fecha' => [''],
      'chofer' => [''],
      'ingresos' => ['rows' => []],
      'gastos' => ['rows' => []],

      'km_recorridos' => ['KM RECORRIDOS'],
      'lts_diesel' => ['LTS DIESEL'],
      'rendimiento_lts' => ['REND KM POR LITRO'],
      'hrs_trabajadas' => ['HORAS TRABAJADAS'],
      'hrs_lts_termo' => ['LTS DIESEL TERMO'],
      'hrs_rendimiento' => ['REND HR POR LITRO'],
    ];
    foreach ($fletes as $keyf => $flete) {
      $infoFlete = $this->getInfoVenta($flete->id, false, true);
      // echo "<pre>";
      // var_dump($infoFlete);
      // echo "</pre>";exit;

      $response['activo'] = $infoFlete['info']->activo->nombre;

      $response['destino'][] = $infoFlete['info']->destino;
      $response['fecha'][] = $infoFlete['info']->fecha;
      $response['chofer'][] = $infoFlete['info']->chofer->nombre;

      $response['km_recorridos'][] = $infoFlete['info']->km_rec;
      $response['lts_diesel'][] = $infoFlete['info']->rend_lts;
      $response['rendimiento_lts'][] = $infoFlete['info']->rend_actual;

      $response['hrs_trabajadas'][] = $infoFlete['info']->rend_thrs_trab;
      $response['hrs_lts_termo'][] = $infoFlete['info']->rend_thrs_lts;
      $response['hrs_rendimiento'][] = $infoFlete['info']->rend_thrs_hxl;

      // ingresos
      $remisiones = []; // agrupamos por clasif
      foreach ($infoFlete['remisiones'] as $key => $rem) {
        if (isset($remisiones[$rem->id_clasificacion])) {
          $remisiones[$rem->id_clasificacion]->cantidad += $rem->cantidad;
          $remisiones[$rem->id_clasificacion]->importe += $rem->importe;
          $remisiones[$rem->id_clasificacion]->iva += $rem->iva;
        } else {
          $remisiones[$rem->id_clasificacion] = $rem;
        }
      }
      foreach ($remisiones as $key => $rem) { // agrega los nuevos conceptos de ingreso
        if (isset($response['ingresos']['rows'][$rem->id_clasificacion])) {
          $response['ingresos']['rows'][$rem->id_clasificacion][] = $rem->importe;
        } else {
          $response['ingresos']['rows'][$rem->id_clasificacion][] = $rem->descripcion;
          for ($i=0; $i < $keyf; $i++) {
            $response['ingresos']['rows'][$rem->id_clasificacion][] = 0;
          }
          $response['ingresos']['rows'][$rem->id_clasificacion][] = $rem->importe;
        }
      }
      foreach ($response['ingresos']['rows'] as $key => $row) { // ajusta todos los conceptos al # de rows
        if ($keyf+2 > count($row)) {
          $response['ingresos']['rows'][$key][] = 0;
        }
      }

      // gastos
      $gastos = []; // agrupamos por concepto repmant
      foreach ($infoFlete['repmant'] as $key => $rem) {
        $kkk = MyString::toAscii($rem->concepto);
        if (isset($gastos[$kkk])) {
          $gastos[$kkk]['subtotal'] += $rem->subtotal;
          $gastos[$kkk]['total'] += $rem->total;
          $gastos[$kkk]['importe_iva'] += $rem->importe_iva;
        } else {
          $gastos[$kkk] = [
            'descripcion' => $rem->concepto,
            'subtotal' => $rem->subtotal,
            'total' => $rem->total,
            'importe_iva' => $rem->importe_iva,
          ];
        }
      }
      foreach ($infoFlete['gastos'] as $key => $rem) { // agrupamos por concepto gastos
        $kkk = MyString::toAscii($rem->codg);
        if (isset($gastos[$kkk])) {
          $gastos[$kkk]['subtotal'] += $rem->subtotal;
          $gastos[$kkk]['total'] += $rem->total;
          $gastos[$kkk]['importe_iva'] += $rem->importe_iva;
        } else {
          $gastos[$kkk] = [
            'descripcion' => $rem->codg,
            'subtotal' => $rem->subtotal,
            'total' => $rem->total,
            'importe_iva' => $rem->importe_iva,
          ];
        }
      }
      foreach ($gastos as $key => $rem) { // agrega los nuevos conceptos de gastos
        if (isset($response['gastos']['rows'][$key])) {
          $response['gastos']['rows'][$key][] = $rem['subtotal'];
        } else {
          $response['gastos']['rows'][$key][] = $rem['descripcion'];
          for ($i=0; $i < $keyf; $i++) {
            $response['gastos']['rows'][$key][] = 0;
          }
          $response['gastos']['rows'][$key][] = $rem['subtotal'];
        }
      }
      foreach ($response['gastos']['rows'] as $key => $row) { // ajusta todos los conceptos al # de rows
        if ($keyf+2 > count($row)) {
          $response['gastos']['rows'][$key][] = 0;
        }
      }

    }

    // echo "<pre>";
    // var_dump($response);
    // echo "</pre>";exit;

    return $response;
  }
  public function getRelFletesXls($tipo = 'html'){
    if ($tipo == 'xls') {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=rel_fletes.xls");
      header("Pragma: no-cache");
      header("Expires: 0");
    }

    $res = $this->getRelFletesData();

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'RELACIÓN DE FLETES';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."<br>\n";
    $titulo3 .= "Activo: {$res['activo']}";

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
        <tr>
          <td colspan="6"></td>
        </tr>';

    $colspan = count($res['fecha']);

    $html .= '<tr style="font-weight:bold">';
    foreach ($res['fecha'] as $key => $value) {
      $html .= '<td style="width:300px;border:1px solid #000;background-color: #cccccc;">'.$value.'</td>';
    }
    $html .= '</tr>';

    $html .= '<tr style="font-weight:bold">';
    foreach ($res['chofer'] as $key => $value) {
      $html .= '<td style="width:300px;border:1px solid #000;background-color: #cccccc;">'.$value.'</td>';
    }
    $html .= '</tr>';

    $html .= '<tr style="font-weight:bold">';
    foreach ($res['destino'] as $key => $value) {
      $html .= '<td style="width:300px;border:1px solid #000;background-color: #cccccc;">'.$value.'</td>';
    }
    $html .= '</tr>';

    // Ingresos
    $res['ingresos']['totales'] = [];
    $html .= '<tr style="font-weight:bold">
      <td colspan="'.$colspan.'" style="width:300px;border:1px solid #000;background-color: #cccccc;text-align:center">INGRESOS</td>
    </tr>';
    foreach ($res['ingresos']['rows'] as $key => $row) {
      $totali = 0;
      $html .= '<tr style="">';
      foreach ($row as $keyr => $value) {
        $html .= '<td style="width:300px;border:1px solid #000;">'.$value.'</td>';

        if (isset($res['ingresos']['totales'][$keyr])) {
          if (is_numeric($value)) {
            $res['ingresos']['totales'][$keyr] += floatval($value);
          }
        } else {
          $res['ingresos']['totales'][$keyr] = is_numeric($value)? floatval($value): 'Total Ingresos';
        }
      }
      $html .= '</tr>';
    }
    $html .= '<tr style="font-weight:bold">';
    foreach ($res['ingresos']['totales'] as $key => $value) {
      $html .= '<td style="width:300px;border:1px solid #000;background-color: #cccccc;">'.$value.'</td>';
    }
    $html .= '</tr>';

    // Gastos
    $res['gastos']['totales'] = [];
    $html .= '<tr style="font-weight:bold">
      <td colspan="'.$colspan.'" style="width:300px;border:1px solid #000;background-color: #cccccc;text-align:center">GASTOS</td>
    </tr>';
    foreach ($res['gastos']['rows'] as $key => $row) {
      $totali = 0;
      $html .= '<tr style="">';
      foreach ($row as $keyr => $value) {
        $html .= '<td style="width:300px;border:1px solid #000;">'.$value.'</td>';

        if (isset($res['gastos']['totales'][$keyr])) {
          if (is_numeric($value)) {
            $res['gastos']['totales'][$keyr] += floatval($value);
          }
        } else {
          $res['gastos']['totales'][$keyr] = is_numeric($value)? floatval($value): 'Total Gastos';
        }
      }
      $html .= '</tr>';
    }
    $html .= '<tr style="font-weight:bold">';
    foreach ($res['gastos']['totales'] as $key => $value) {
      $html .= '<td style="width:300px;border:1px solid #000;background-color: #cccccc;">'.$value.'</td>';
    }
    $html .= '</tr>';

    $html .= '<tr style="font-weight:bold">';
    foreach ($res['gastos']['totales'] as $key => $value) {
      if ($key == 0) {
        $html .= '<td style="width:300px;border:1px solid #000;background-color: #cccccc;">Perdida/Ganancia</td>';
      } else {
        $html .= '<td style="width:300px;border:1px solid #000;background-color: #cccccc;">'.($res['ingresos']['totales'][$key] - $value).'</td>';
      }
    }
    $html .= '</tr>';

    $html .= '<tr>
        <td colspan="'.$colspan.'"> </td>
      </tr>
      <tr>
        <td colspan="'.$colspan.'"> </td>
      </tr>
      <tr>
        <td colspan="'.$colspan.'"> </td>
      </tr>';

    $html .= '<tr style="font-weight:bold">
      <td colspan="'.$colspan.'" style="width:300px;border:1px solid #000;background-color: #cccccc;text-align:center">RENDIMIENTO</td>
    </tr>';
    $html .= '<tr style="">';
    foreach ($res['km_recorridos'] as $key => $value) {
      $html .= '<td style="width:300px;border:1px solid #000;">'.$value.'</td>';
    }
    $html .= '</tr>';
    $html .= '<tr style="">';
    foreach ($res['lts_diesel'] as $key => $value) {
      $html .= '<td style="width:300px;border:1px solid #000;">'.$value.'</td>';
    }
    $html .= '</tr>';
    $html .= '<tr style="font-weight:bold">';
    foreach ($res['rendimiento_lts'] as $key => $value) {
      $html .= '<td style="width:300px;border:1px solid #000;">'.$value.'</td>';
    }
    $html .= '</tr>';

    $html .= '<tr style="font-weight:bold">
      <td colspan="'.$colspan.'" style="width:300px;border:1px solid #000;background-color: #cccccc;text-align:center">TERMO</td>
    </tr>';
    $html .= '<tr style="">';
    foreach ($res['hrs_trabajadas'] as $key => $value) {
      $html .= '<td style="width:300px;border:1px solid #000;">'.$value.'</td>';
    }
    $html .= '</tr>';
    $html .= '<tr style="">';
    foreach ($res['hrs_lts_termo'] as $key => $value) {
      $html .= '<td style="width:300px;border:1px solid #000;">'.$value.'</td>';
    }
    $html .= '</tr>';
    $html .= '<tr style="font-weight:bold">';
    foreach ($res['hrs_rendimiento'] as $key => $value) {
      $html .= '<td style="width:300px;border:1px solid #000;">'.$value.'</td>';
    }
    $html .= '</tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
  }

}
