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
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('ANTICIPO', MyString::formatoNumero($caja['info']->gasto_monto, 2, '$', false)), false, true);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('INGRESO', MyString::formatoNumero($ttotalRemisionesEf, 2, '$', false)), false, true);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('EGRESOS (-)', MyString::formatoNumero(($ttotalSueldos + $ttotalGastosEf), 2, '$', false)), false, true);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('REP. GTOS (-)', MyString::formatoNumero($ttotalRepMantEf, 2, '$', false)), false, true);
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
      'destino' => [''],
      'fecha' => [''],
      'chofer' => [''],
      'ingresos' => ['data' => [], 'rows' => []]
    ];
    foreach ($fletes as $keyf => $flete) {
      $infoFlete = $this->getInfoVenta($flete->id, false, true);

      $response['destino'][] = $infoFlete['info']->destino;
      $response['fecha'][] = $infoFlete['info']->fecha;
      $response['chofer'][] = $infoFlete['info']->chofer->nombre;

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
    }
    echo "<pre>";
    var_dump($response);
    echo "</pre>";exit;
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    if(is_array($this->input->get('ffamilias'))){
      $sql .= " AND pf.id_familia IN (".implode(',', $this->input->get('ffamilias')).")";
    }

    if($this->input->get('fid_producto') != '' || $id_producto > 0){
      $id_producto = $id_producto>0? $id_producto: $this->input->get('fid_producto');
      $sql .= " AND p.id_producto = ".$id_producto;
      $res_prod = $this->db->query("SELECT id_empresa FROM productos WHERE id_producto = {$id_producto}")->row();
      $_GET['did_empresa'] = $res_prod->id_empresa;
    }

    if (!isset($extras['empresa'])) {
      $this->load->model('empresas_model');
      $client_default = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
      $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      if($this->input->get('did_empresa') != ''){
        $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
      }
    } elseif (isset($extras['empresa'])) {
      $sql .= " AND p.id_empresa = '{$extras['empresa']}'";
    }

    if ($this->input->get('did_empresa') == 3) { // gomez gudiño
      $sql_com .= " AND Date(cp.fecha_aceptacion) > '2015-04-30'";
      $sql_sal .= " AND Date(sa.fecha_registro) > '2015-04-30'";
    }

    $id_almacen = $id_almacen>0? $id_almacen: $this->input->get('did_almacen');
    if ($id_almacen > 0) {
      $sql_com .= " AND co.id_almacen = ".$id_almacen;
      $sql_sal .= " AND sa.id_almacen = ".$id_almacen;
      $sql_req .= " AND cr.id_almacen = ".$id_almacen;
    }

    $sql_con_req = '';
    $sql_con_req_f = '';
    if ($con_req) { // toma en cuenta la existencia de las requisición pendientes
      $sql_con_req_f = ', COALESCE(con_req.cantidad, 0) AS con_req';
      $sql_con_req = "LEFT JOIN
      (
        SELECT crq.id_producto, Sum(crq.cantidad) AS cantidad
        FROM compras_requisicion cr
          INNER JOIN compras_requisicion_productos crq ON cr.id_requisicion = crq.id_requisicion
        WHERE cr.status = 'p' AND cr.tipo_orden = 'p' AND cr.autorizado = 'f' AND cr.id_autorizo IS NULL
          AND cr.es_receta = 't' AND crq.importe > 0
          {$sql_req}
        GROUP BY crq.id_producto
      ) AS con_req ON con_req.id_producto = p.id_producto";
    }

    $res = $this->db->query(
      "SELECT pf.id_familia, pf.nombre, p.id_producto, p.nombre AS nombre_producto, pu.abreviatura,
        COALESCE(co.cantidad, 0) AS entradas, COALESCE(sa.cantidad, 0) AS salidas,
        (COALESCE(sal_co.cantidad, 0) - COALESCE(sal_sa.cantidad, 0)) AS saldo_anterior, p.stock_min
        {$sql_con_req_f}
      FROM productos AS p
      INNER JOIN productos_familias AS pf ON pf.id_familia = p.id_familia
      INNER JOIN productos_unidades AS pu ON pu.id_unidad = p.id_unidad
      LEFT JOIN
      (
        SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
        FROM compras_ordenes AS co
          INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
        WHERE co.status <> 'ca' AND co.tipo_orden in('p', 't') AND cp.status = 'a'
          AND Date(cp.fecha_aceptacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql_com} AND co.id_orden_aplico IS NULL
        GROUP BY cp.id_producto
      ) AS co ON co.id_producto = p.id_producto
      LEFT JOIN
      (
        SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
        FROM compras_salidas AS sa
        INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
        WHERE sa.status <> 'ca' AND sp.tipo_orden = 'p'
          AND Date(sa.fecha_registro) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql_sal}
        GROUP BY sp.id_producto
      ) AS sa ON sa.id_producto = p.id_producto
      LEFT JOIN
      (
        SELECT cp.id_producto, Sum(cp.cantidad) AS cantidad
        FROM compras_ordenes AS co
          INNER JOIN compras_productos AS cp ON cp.id_orden = co.id_orden
        WHERE co.status <> 'ca' AND co.tipo_orden in('p', 't') AND cp.status = 'a'
          AND Date(cp.fecha_aceptacion) < '{$fecha}'
          {$sql_com} AND co.id_orden_aplico IS NULL
        GROUP BY cp.id_producto
      ) AS sal_co ON sal_co.id_producto = p.id_producto
      LEFT JOIN
      (
        SELECT sp.id_producto, Sum(sp.cantidad) AS cantidad
        FROM compras_salidas AS sa
        INNER JOIN compras_salidas_productos AS sp ON sp.id_salida = sa.id_salida
        WHERE sa.status <> 'ca' AND sp.tipo_orden = 'p'
          AND Date(sa.fecha_registro) < '{$fecha}'
          {$sql_sal}
        GROUP BY sp.id_producto
      ) AS sal_sa ON sal_sa.id_producto = p.id_producto
      {$sql_con_req}
      WHERE p.status='ac' AND pf.status='ac' AND pf.tipo = 'p' {$sql}
      ORDER BY nombre, nombre_producto ASC
      ");

    $response = array();
    if($res->num_rows() > 0)
      $response = $res->result();

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
    $titulo2 = 'Existencia por unidades';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    // $titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');

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
          <td style="width:300px;border:1px solid #000;background-color: #cccccc;">Producto</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Saldo</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Entradas</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Salidas</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Existencia</td>
        </tr>';

    $familia = '';
    $totales = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($key==0){

        if ($key == 0)
        {
          $familia = $item->nombre;
          $html .= '<tr>
              <td colspan="6" style="font-size:16px;border:1px solid #000;">'.$familia.'</td>
            </tr>';
        }
      }

      if ($familia <> $item->nombre)
      {
        if($key > 0){
          $html .= '
            <tr style="font-weight:bold">
              <td></td>
              <td style="border:1px solid #000;">TOTALES</td>
              <td style="border:1px solid #000;">'.$totales['familia'][0].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][1].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][2].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][3].'</td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>';
        }
        $totales['familia'] = array(0,0,0,0);

        $familia = $item->nombre;
        $html .= '<tr>
              <td colspan="6" style="font-size:16px;border:1px solid #000;">'.$familia.'</td>
            </tr>';
      }

      $imprimir = true;
      $existencia = $item->saldo_anterior+$item->entradas-$item->salidas;
      if($this->input->get('con_existencia') == 'si')
        if($existencia <= 0)
          $imprimir = false;
      if($this->input->get('con_movimiento') == 'si')
        if($item->entradas <= 0 && $item->salidas <= 0)
          $imprimir = false;


      if($imprimir)
      {
        $totales['familia'][0] += $item->saldo_anterior;
        $totales['familia'][1] += $item->entradas;
        $totales['familia'][2] += $item->salidas;
        $totales['familia'][3] += $existencia;

        $totales['general'][0] += $item->saldo_anterior;
        $totales['general'][1] += $item->entradas;
        $totales['general'][2] += $item->salidas;
        $totales['general'][3] += $existencia;

        $html .= '<tr>
              <td style="width:30px;border:1px solid #000;"></td>
              <td style="width:300px;border:1px solid #000;">'.$item->nombre_producto.' ('.$item->abreviatura.')'.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->saldo_anterior.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->entradas.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->salidas.'</td>
              <td style="width:200px;border:1px solid #000;">'.$existencia.'</td>
            </tr>';
      }
    }

    $html .= '
            <tr style="font-weight:bold">
              <td></td>
              <td style="border:1px solid #000;">TOTALES</td>
              <td style="border:1px solid #000;">'.$totales['familia'][0].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][1].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][2].'</td>
              <td style="border:1px solid #000;">'.$totales['familia'][3].'</td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>
            <tr>
              <td colspan="6"></td>
            </tr>
            <tr style="font-weight:bold">
              <td></td>
              <td style="border:1px solid #000;">GENERAL</td>
              <td style="border:1px solid #000;">'.$totales['general'][0].'</td>
              <td style="border:1px solid #000;">'.$totales['general'][1].'</td>
              <td style="border:1px solid #000;">'.$totales['general'][2].'</td>
              <td style="border:1px solid #000;">'.$totales['general'][3].'</td>
            </tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
  }

}
