<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bascula_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  /**
   * Obtiene el listado de entradas|salida ya sea pagina o no.
   * @param  boolean $paginados
   * @return array
   */
  public function getBasculas($paginados = true)
  {
    $sql = '';
    //paginacion
    if($paginados)
    {
      $this->load->library('pagination');
      $params = array(
          'result_items_per_page' => '60',
          'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
      );
      if($params['result_page'] % $params['result_items_per_page'] == 0)
        $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
    }

    //Filtros para buscar
    if($this->input->get('fnombre') !== '')
      $sql = "WHERE (( b.folio::text LIKE '%".$this->input->get('fnombre')."%' ) OR
                    ( lower(p.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' ) OR
                    ( lower(cl.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' ) OR
                    ( lower(ch.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' ) OR
                    ( lower(ca.modelo) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' ) OR
                    ( lower(ca.placa) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' ))";

    if (isset($_GET['farea']))
      if ($this->input->get('farea') !== '')
        $sql .= (empty($sql) ? "WHERE " : " AND ") . "a.id_area = " . $this->input->get('farea');

    $_GET['fstatusb'] = $this->input->get('fstatusb')!==false ? $this->input->get('fstatusb') : 't';

    if($this->input->get('fstatusb') != '' && $this->input->get('fstatusb') != 'todos')
      $sql .= (empty($sql) ? 'WHERE ': ' AND ')."b.status='".$this->input->get('fstatusb')."'";

    if($this->input->get('ftipob') != '' && $this->input->get('ftipob') != 'todos')
      $sql .= (empty($sql) ? "WHERE ": " AND ") . "b.tipo='".$this->input->get('ftipob')."'";

    if (isset($_GET['fechaini']))
      if ($this->input->get('fechaini') !== '')
        $sql .= (empty($sql) ? "WHERE ": " AND ") . "DATE(b.fecha_bruto) >= '".$this->input->get('fechaini')."'";

    if (isset($_GET['fechaend']))
      if ($this->input->get('fechaend') !== '')
        $sql .= (empty($sql) ? "WHERE ": " AND ") . "DATE(b.fecha_bruto) <= '".$this->input->get('fechaend')."'";

    $str_query =
        "SELECT b.id_bascula,
                b.folio,
                b.tipo,
                b.status,
                e.nombre_fiscal AS empresa,
                a.nombre AS area,
                p.nombre_fiscal AS proveedor,
                ch.nombre AS chofer,
                (ca.marca || ' ' || ca.modelo) AS camion,
                ca.placa AS placas,
                b.fecha_bruto AS fecha,
                cl.nombre_fiscal AS cliente,
                b.id_bonificacion
        FROM bascula AS b
        INNER JOIN empresas AS e ON e.id_empresa = b.id_empresa
        INNER JOIN areas AS a ON a.id_area = b.id_area
        LEFT JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor
        LEFT JOIN clientes AS cl ON cl.id_cliente = b.id_cliente
        LEFT JOIN choferes AS ch ON ch.id_chofer = b.id_chofer
        LEFT JOIN camiones AS ca ON ca.id_camion = b.id_camion
        ".$sql."
        ORDER BY fecha DESC
        ";
    if($paginados){
      $query = BDUtil::pagination($str_query, $params, true);
      $res = $this->db->query($query['query']);
    }else
      $res = $this->db->query($str_query);

    $response = array(
        'basculas'       => array(),
        'total_rows'     => (isset($query['total_rows'])? $query['total_rows']: ''),
        'items_per_page' => (isset($params['result_items_per_page'])? $params['result_items_per_page']: ''),
        'result_page'    => (isset($params['result_page'])? $params['result_page']: '')
    );
    if($res->num_rows() > 0){
      $response['basculas'] = $res->result();
    }

    return $response;
  }

  public function addBascula($data=null, $bonificacion=false)
  {
    if (is_null($data))
    {
      $idb = isset($_POST['pidb']) ? $_POST['pidb'] : '';

      if ($_POST['paccion'] == 'n')
      {
        // $_POST['pfolio'] = $this->getSiguienteFolio();

        $data = array(
          'id_empresa'   => $this->input->post('pid_empresa'),
          'id_area'      => $this->input->post('parea'),
          'id_chofer'    => empty($_POST['pid_chofer']) ? null : $_POST['pid_chofer'],
          'id_camion'    => empty($_POST['pid_camion']) ? null : $_POST['pid_camion'],
          'folio'        => $this->input->post('pfolio'),
          // 'fecha_bruto'  => str_replace('T', ' ', $_POST['pfecha'].':'.date('s')),
          // 'kilos_bruto'  => $this->input->post('pkilos_brutos'),
          'accion'       => 'en',
          'tipo'         => $this->input->post('ptipo'),
          'cajas_prestadas' => empty($_POST['pcajas_prestadas']) ? null : $_POST['pcajas_prestadas'],
        );

        if ($this->input->post('ptipo') === 'en')
        {
          $data['fecha_bruto'] = str_replace('T', ' ', $_POST['pfecha'].':'.date('s'));
          $data['kilos_bruto'] = $this->input->post('pkilos_brutos');
        }
        else
        {
          $data['fecha_tara'] = str_replace('T', ' ', $_POST['pfecha'].':'.date('s'));
          $data['kilos_tara'] = $this->input->post('pkilos_tara');
        }

        if ($bonificacion)
        {
          $data['id_bonificacion'] = $_POST['pidb'];
          $data['accion']          = isset($_POST['pstatus']) ? 'p' : 'en';
          $data['fecha_tara']      = str_replace('T', ' ', $_POST['pfecha'].':'.date('s'));
          $data['kilos_tara']      = $this->input->post('pkilos_tara');
          $data['kilos_neto']      = $this->input->post('pkilos_neto');
        }

        if ($_POST['ptipo'] === 'en')
          $data['id_proveedor'] = $this->input->post('pid_proveedor');
        else
          $data['id_cliente'] = $this->input->post('pid_cliente');

        $this->db->insert('bascula', $data);
        $idb = $this->db->insert_id();
      }

      $data2 = array(
        'importe'       => empty($_POST['ptotal']) ? 0 : $_POST['ptotal'],
        'total_cajas'   => empty($_POST['ptotal_cajas']) ? 0 : $_POST['ptotal_cajas'],
        'obcervaciones' => $this->input->post('pobcervaciones'),
      );

      if ($_POST['paccion'] === 'en' || $_POST['paccion'] === 'sa' ||
          $_POST['paccion'] === 'p' || $_POST['paccion'] === 'b')
      {
        $data2['id_empresa'] = $this->input->post('pid_empresa');
        $data2['id_area']    = $this->input->post('parea');

        if ($_POST['ptipo'] === 'en')
        {
          $data2['id_proveedor'] = $this->input->post('pid_proveedor');
          $data2['id_cliente']    = null;
        }
        else
        {
          $data2['id_cliente']    = $this->input->post('pid_cliente');
          $data2['id_proveedor'] = null;
        }

        $data2['id_chofer'] = empty($_POST['pid_chofer']) ? null : $_POST['pid_chofer'];
        $data2['id_camion'] = empty($_POST['pid_camion']) ? null : $_POST['pid_camion'];

        $info_boleta = $this->getBasculaInfo($idb);
        if($info_boleta['info'][0]->fecha_tara != '' && substr($info_boleta['info'][0]->fecha_tara, 0, 16) != str_replace('T', ' ', $_POST['pfecha']) ){
          $data2['fecha_bruto'] = str_replace('T', ' ', $_POST['pfecha'].':'.date('s'));
          $data2['fecha_tara'] = str_replace('T', ' ', $_POST['pfecha'].':'.date('s'));
        }else
          $data2['fecha_tara'] = str_replace('T', ' ', $_POST['pfecha'].':'.date('s'));

        $data2['kilos_bruto'] = $this->input->post('pkilos_brutos');
        $data2['kilos_tara'] = $this->input->post('pkilos_tara');

        $data2['kilos_neto']  = $this->input->post('pkilos_neto');
        $data2['kilos_neto2'] = $this->input->post('ppesada');
        $data2['accion']      = 'sa';
        $data2['tipo']        = $this->input->post('ptipo');

        $data2['cajas_prestadas'] = empty($_POST['pcajas_prestadas']) ? null : $_POST['pcajas_prestadas'];

        if (isset($_POST['pstatus'])) $data2['accion'] = 'p';
      }

      $cajas = null;
      if (isset($_POST['pcajas']))
      {
        $cajas = array();
        foreach ($_POST['pcajas'] as $key => $caja)
        {
          if ( (!empty($_POST['pprecio'][$key]) && $_POST['pprecio'][$key] != 0) || $bonificacion==false)
          {
            $cajas[] = array(
              'id_bascula'   => $idb,
              'id_calidad'   => $_POST['pcalidad'][$key],
              'cajas'        => $caja,
              'kilos'        => $_POST['pkilos'][$key],
              'promedio'     => $_POST['ppromedio'][$key],
              'precio'       => $_POST['pprecio'][$key],
              'importe'      => $_POST['pimporte'][$key],
              'num_registro' => $key,
            );
          }
        }
      }

      $this->updateBascula($idb, $data2, $cajas);

      $msg = '7';
      if ($bonificacion)
        $msg = '12';

      return array('passes'=>true, 'msg'=>$msg, 'idb' => $idb);
    }

    $this->db->insert('bascula', $data);
    return array('passes'=>true);
  }

  public function updateBascula($id=null, $data=null, $cajas=null)
  {
    $id = is_null($id) ? $_GET['id'] : $id;

    if (is_null($data))
    {

    }

    $this->db->update('bascula', $data, array('id_bascula' => $id));

    if ( ! is_null($cajas) && count($cajas) > 0)
    {
      $this->db->delete('bascula_compra', array('id_bascula' => $id));
      $this->db->insert_batch('bascula_compra', $cajas);
    }

    return array('passes'=>true);
  }

  /**
   * Obtiene la informacion de una bascula
   * @param  boolean $id
   * @param  boolean $basic_info
   * @return array
   */
  public function getBasculaInfo($id=false, $folio=0, $basic_info=false)
  {
    $id = (isset($_GET['id']))? $_GET['id']: $id;

    $sql_res = $this->db
      ->select("b.*,
                e.nombre_fiscal AS empresa,
                a.nombre AS area,
                p.nombre_fiscal AS proveedor,
                p.cuenta_cpi AS cpi_proveedor,
                ch.nombre AS chofer,
                (ca.marca || ' ' || ca.modelo) AS camion,
                ca.placa AS camion_placas,
                cl.nombre_fiscal as cliente,
                cl.cuenta_cpi AS cpi_cliente,
                b.tipo")
      ->from("bascula AS b")
      ->join('empresas AS e', 'e.id_empresa = b.id_empresa', "inner")
      ->join('areas AS a', 'a.id_area = b.id_area', "inner")
      ->join('proveedores AS p', 'p.id_proveedor = b.id_proveedor', "left")
      ->join('clientes AS cl', 'cl.id_cliente = b.id_cliente', "left")
      ->join('choferes AS ch', 'ch.id_chofer = b.id_chofer', "left")
      ->join('camiones AS ca', 'ca.id_camion = b.id_camion', "left")
      ->where("b.id_bascula", $id)
      ->or_where('b.folio', $folio)
      ->get();

    $data['info'] = array();
    $data['cajas'] = array();

    if ($sql_res->num_rows() > 0)
    {
      $data['info'] = $sql_res->result();

      $sql_res->free_result();

      if ($basic_info === false)
      {
        $sql_res = $this->db
          ->select("bc.*, c.nombre as calidad")
          ->from("bascula_compra AS bc")
          ->join("calidades AS c", "c.id_calidad = bc.id_calidad", "inner")
          ->where("id_bascula", $data['info'][0]->id_bascula)
          ->order_by('num_registro', 'ASC')
          ->get();

        if ($sql_res->num_rows() > 0)
          $data['cajas'] = $sql_res->result();
      }
    }

    return $data;
  }

  // /**
  //  * Obtiene el folio siguiente.
  //  * @return int
  //  */
  // public function getSiguienteFolio()
  // {
  //   $lastFolio = $this->db->select('folio')
  //     ->from('bascula')
  //     ->order_by('id_bascula', 'DESC')
  //     ->limit(1)
  //     ->get();

  //   if ($lastFolio->num_rows() > 0)
  //     return intval($lastFolio->row()->folio) + 1;
  //   else
  //     return 1;
  // }

  /**
   * Obtiene el folio siguiente segun el tipo (entrada o salida) y el area.
   * @return int
   */
  public function getSiguienteFolio($tipo = 'en', $id_area = null)
  {

    $id_area = $id_area ? $id_area : $this->db->select('id_area')
      ->from('areas')
      ->where('predeterminado', 't')->get()->row()->id_area;

    $lastFolio = $this->db->select('folio')
      ->from('bascula')
      ->where('tipo', $tipo)
      ->where('id_area', $id_area)
      ->order_by('folio', 'DESC')
      ->limit(1)
      ->get();

    if ($lastFolio->num_rows() > 0)
      return intval($lastFolio->row()->folio) + 1;
    else
      return 1;
  }

  public function getIdfolio($folio, $tipo, $id_area)
  {
    $sql = $this->db->select("id_bascula")
      ->from("bascula")
      ->where("folio", $folio)
      ->where("tipo", $tipo)
      ->where("id_area", $id_area)
      ->get();

    return $sql->num_rows() > 0 ? $sql->row()->id_bascula : 0;
  }

  /**
   * Imprime el ticket
   * @return pdf
   */
  public function imprimir_ticket($id)
  {
    $this->load->library('mypdf_ticket');

    $data = $this->getBasculaInfo($id);

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $pdf = new mypdf_ticket();
    $pdf->SetFont('Arial','',8);
    $pdf->AddPage();

    $pdf->printTicket($data['info'][0], $data['cajas']);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }

  public function getMovimientos()
  {
    $data =  array(
      'movimientos' => array(),
      'area'        => array(),
      'proveedor'   => array(),
    );

    $data['totales'] = array(
        'importe'     => 0,
        'pesada'      => 0,
        'total'       => 0,
        'pagados'     => 0,
        'kilos'       => 0,
        'cajas'       => 0,
        'precio_prom' => 0, // importe / kilos
        'no_pagados'  => 0,
      );

    if (isset($_GET['fid_proveedor']))
    {
      $sql = '';

      $_GET['fechaini'] = $this->input->get('fechaini') != '' ? $_GET['fechaini'] : date('Y-m-01');
      $_GET['fechaend'] = $this->input->get('fechaend') != '' ? $_GET['fechaend'] : date('Y-m-d');
      if ($this->input->get('fechaini') != '' && $this->input->get('fechaend') != '')
      $sql .= " AND DATE(b.fecha_bruto) >= '".$this->input->get('fechaini')."' AND
                    DATE(b.fecha_bruto) <= '".$this->input->get('fechaend')."'";

      $_GET['farea'] = $this->input->get('farea') != '' ? $_GET['farea'] : '1';
      if ($this->input->get('farea') != '')
        $sql .= " AND b.id_area = " . $_GET['farea'];

      if ($this->input->get('fid_proveedor') != ''){
        if($this->input->get('ftipop') == 'sa'){
          $sql .= " AND b.id_cliente = '".$_GET['fid_proveedor']."'";
        }else{
          $sql .= " AND b.id_proveedor = '".$_GET['fid_proveedor']."'";
        }
      }

      if ($this->input->get('fstatusp') != '')
        if ($this->input->get('fstatusp') === '1')
          $sql .= " AND b.accion IN ('p', 'b')";
        else
          $sql .= " AND b.accion IN ('en', 'sa')";

      //Filtros del tipo de pesadas
      if ($this->input->get('ftipop') != '')
        $sql .= " AND b.tipo = '{$_GET['ftipop']}'";
      $table_ms = 'LEFT JOIN proveedores p ON p.id_proveedor = b.id_proveedor';
      $tipo_rpt = "Entrada";
      if($this->input->get('ftipop') == 'sa') {
        $table_ms = 'LEFT JOIN clientes c ON c.id_cliente = b.id_cliente';
        $tipo_rpt = "Salida";
      }

      // if ($this->input->get('ftipop') != '')
      //   if ($this->input->get('ftipop') === '1')
      //     $sql .= " AND b.tipo = 'en'";
      //   else
      //     $sql .= " AND b.tipo = 'sa'";

      if (isset($_GET['pe']))
        $sql = " AND b.id_bascula IN (".$_GET['pe'].")";

      $query = $this->db->query(
        "SELECT b.id_bascula,
               b.accion as status,
               b.folio,
               DATE(b.fecha_bruto) as fecha,
               ca.nombre as calidad,
               bc.cajas,
               bc.promedio,
               Coalesce(bc.kilos, b.kilos_neto) AS kilos,
               bc.precio,
               bc.importe,
               b.importe as importe_todas,
               b.tipo,
               pagos.tipo_pago,
               pagos.concepto,
               b.id_bonificacion
        FROM bascula AS b
          LEFT JOIN bascula_compra AS bc ON b.id_bascula = bc.id_bascula
          {$table_ms}
          LEFT JOIN calidades AS ca ON ca.id_calidad = bc.id_calidad
          LEFT JOIN (SELECT bpb.id_bascula, bp.tipo_pago, bp.concepto
                    FROM bascula_pagos AS bp
                    INNER JOIN bascula_pagos_basculas AS bpb ON bpb.id_pago = bp.id_pago) AS pagos
                    ON pagos.id_bascula = b.id_bascula
        WHERE
              b.status = true 
              {$sql}
        ORDER BY b.folio, bc.id_calidad ASC
      ");

      $movimientos = $query->result();

      foreach ($movimientos as $key => $caja)
      {
        $data['totales']['importe']     += floatval($caja->importe);
        $data['totales']['total']       += floatval($caja->importe);
        if(!is_numeric($caja->id_bonificacion)){
          $data['totales']['kilos']       += floatval($caja->kilos);
          $data['totales']['cajas']       += floatval($caja->cajas);
        }
        // $data['precio_prom'] += floatval($caja->promedio);

        if ($caja->status === 'p' || $caja->status === 'b')
          $data['totales']['pagados'] += floatval($caja->importe);
        else
          $data['totales']['no_pagados'] += floatval($caja->importe);
      }


      $this->load->model('areas_model');
      $this->load->model('proveedores_model');
      $this->load->model('clientes_model');

      // Obtiene la informacion del Area filtrada.
      $data['area'] = $this->areas_model->getAreaInfo($_GET['farea']);

      // Obtiene la informacion del proveedor filtrado.
      if($this->input->get('ftipop') == 'sa') {
        $data['proveedor'] = $this->clientes_model->getClienteInfo($_GET['fid_proveedor']);
      }else
        $data['proveedor'] = $this->proveedores_model->getProveedorInfo($_GET['fid_proveedor']);

      $data['movimientos'] = $movimientos;
    }

    return $data;
  }

  public function pago_basculas()
  {
    $bascula_pagos = array(
      'tipo_pago' => $this->input->post('ptipo_pago'),
      'monto'     => $this->input->post('pmonto'),
      'concepto'  => $this->input->post('pconcepto'),
    );

    $this->db->insert('bascula_pagos', $bascula_pagos);
    $id_bascula_pagos = $this->db->insert_id();

    $pesadas = array();
    $pesadas_update = array();
    foreach ($_POST['ppagos'] as $pesada)
    {
      $this->db->update('bascula', array('accion' => 'b'), array('id_bascula' => $pesada));

      $pesadas[] = array(
        'id_pago' => $id_bascula_pagos,
        'id_bascula' => $pesada
      );
    }

    $this->db->insert_batch('bascula_pagos_basculas', $pesadas);

    return array('passess' => true);
  }


  /*
   |-------------------------------------------------------------------------
   |  REPORTES
   |-------------------------------------------------------------------------
  */

   public function rde_data()
   {
      $sql = $sql2 = '';

      $_GET['ffecha1'] = $this->input->get('ffecha1') != '' ? $_GET['ffecha1'] : date('Y-m-d');
      $sql .= " AND DATE(b.fecha_bruto) = '".$_GET['ffecha1']."' ";
      $sql2 .= " AND DATE(b.fecha_bruto) = '".$_GET['ffecha1']."' ";

      $this->load->model('areas_model');
      $_GET['farea'] = $this->input->get('farea') != '' ? $_GET['farea'] : $this->areas_model->getAreaDefault();
      if ($this->input->get('farea') != ''){
        $sql .= " AND b.id_area = " . $_GET['farea'];
        $sql2 .= " AND b.id_area = " . $_GET['farea'];
      }

      if ($this->input->get('fid_proveedor') != ''){
        if($this->input->get('ftipo') == 'sa'){
          $sql .= " AND b.id_cliente = '".$_GET['fid_proveedor']."'";
          $sql2 .= " AND b.id_cliente = '".$_GET['fid_proveedor']."'";
        }else{
          $sql .= " AND b.id_proveedor = '".$_GET['fid_proveedor']."'";
          $sql2 .= " AND b.id_proveedor = '".$_GET['fid_proveedor']."'";
        }
      }

      if ($this->input->get('fid_empresa') != ''){
        $sql .= " AND b.id_empresa = '".$_GET['fid_empresa']."'";
        $sql2 .= " AND b.id_empresa = '".$_GET['fid_empresa']."'";
      }
      if ($this->input->get('fstatus') != '')
        if ($this->input->get('fstatus') === '1')
          $sql .= " AND (b.accion = 'p' OR b.accion = 'b')";
        else
          $sql .= " AND (b.accion = 'en' OR b.accion = 'sa')";

      //Filtros del tipo de pesadas
      if ($this->input->get('ftipo') != '')
        $sql .= " AND b.tipo = '{$_GET['ftipo']}'";
      $campos = "p.nombre_fiscal AS proveedor, p.cuenta_cpi, ";
      $table_ms = 'LEFT JOIN proveedores p ON p.id_proveedor = b.id_proveedor';
      $tipo_rpt = "Entrada";
      if($this->input->get('ftipo') == 'sa') {
        $campos = "c.nombre_fiscal AS proveedor, c.cuenta_cpi, ";
        $table_ms = 'LEFT JOIN clientes c ON c.id_cliente = b.id_cliente';
        $tipo_rpt = "Salida";
      }

      $query = $this->db->query(
        "SELECT bc.id_bascula,
          bc.id_calidad,
          bc.cajas,
          bc.kilos,
          bc.promedio,
          bc.precio,
          bc.importe,
          {$campos}
          b.folio,
          b.accion AS pagado
        FROM bascula_compra AS bc
        INNER JOIN bascula AS b ON b.id_bascula = bc.id_bascula
        {$table_ms}
        WHERE b.status = true
              {$sql}
        ORDER BY (b.folio, bc.id_calidad) ASC
        "
      );

      $this->load->model('areas_model');

      // Obtiene la informacion del Area filtrada.
      $area = $this->areas_model->getAreaInfo($_GET['farea']);

      $rde = array();
      if ($query->num_rows() > 0)
      {
        // echo "<pre>";
        //   var_dump($area);
        // echo "</pre>";exit;

        foreach ($area['calidades'] as $key => $calidad)
        {
          $rde[$key] = array('calidad' => $calidad->nombre, 'cajas' => array());
          foreach ($query->result() as $key2 => $caja)
            if ($caja->id_calidad == $calidad->id_calidad)
              $rde[$key]['cajas'][] = $caja;
        }

        foreach ($rde as $key => $calidad)
          if (count($calidad['cajas']) === 0)
            unset($rde[$key]);
      }

      $cancelados = $this->db->query(
        "SELECT SUM(b.importe) as cancelado
        FROM bascula AS b
        WHERE b.id_bonificacion is null AND
              b.status = false AND
              b.tipo = 'en'
              {$sql2}
        ")->row()->cancelado;

      return array('rde' => $rde, 'area' => $area, 'cancelados' => $cancelados, 'tipo' => $tipo_rpt);
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Diario de Entradas.
    *
    * @return void
    */
   public function rde_pdf()
   {

    // Obtiene los datos del reporte.
    $data = $this->rde_data();

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

      $rde = $data['rde'];

      $area = $data['area'];
      // echo "<pre>";
      //   var_dump($area);
      // echo "</pre>";exit;

      $fecha = new DateTime($_GET['ffecha1']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "REPORTE DIARIO DE ENTRADAS <".$area['info']->nombre."> DEL DIA " . $fecha->format('d/m/Y');
      $pdf->titulo3 = $this->input->get('fproveedor').' | '.$data['tipo'].' | '.$this->input->get('fempresa');

      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'C', 'L', 'C', 'C', 'C', 'C', 'C');
      $aligns1 = array('C', 'C', 'C', 'L', 'R', 'R', 'R', 'R', 'R');
      $widths = array(6, 20, 17, 55, 16, 25, 25, 17, 25);
      $header = array('',   'BOLETA', 'CUENTA','NOMBRE', 'PROM',
                      'CAJAS', 'KILOS', 'PRECIO','IMPORTE');

      $totalPagado    = 0;
      $totalNoPagado  = 0;
      $totalCancelado = 0;

      foreach($rde as $key => $calidad)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B', 8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, false);
        }

        $pdf->SetFont('helvetica','', 9);
        $pdf->SetTextColor(0,0,0);

        $pdf->SetY($pdf->GetY()-1);
        $pdf->SetX(6);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(206));
        $pdf->Row(array($calidad['calidad']), false, false);

        $pdf->SetFont('helvetica','',8);
        $pdf->SetTextColor(0,0,0);

        $promedio = 0;
        $cajas    = 0;
        $kilos    = 0;
        $precio   = 0;
        $importe  = 0;

        foreach ($calidad['cajas'] as $caja)
        {
          if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
          {
            $pdf->AddPage();

            $pdf->SetFont('helvetica','B', 8);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFillColor(160,160,160);
            $pdf->SetY($pdf->GetY()-2);
            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, false);
          }

          $pdf->SetFont('helvetica','',8);
          $pdf->SetTextColor(0,0,0);

          $promedio += $caja->promedio;
          $cajas    += $caja->cajas;
          $kilos    += $caja->kilos;
          $precio   += $caja->precio;
          $importe  += $caja->importe;

          if ($caja->pagado === 'p' || $caja->pagado === 'b')
            $totalPagado += $caja->importe;
          else
            $totalNoPagado += $caja->importe;

          $datos = array(($caja->pagado === 'p' || $caja->pagado === 'b') ? ucfirst($caja->pagado) : '',
                         $caja->folio,
                         $caja->cuenta_cpi,
                         substr($caja->proveedor, 0, 28),
                         String::formatoNumero($caja->promedio, 2, '', false),
                         $caja->cajas,
                         $caja->kilos,
                         String::formatoNumero($caja->precio, 2, '$', false),
                         String::formatoNumero($caja->importe, 2, '$', false));

          $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns1);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, false, false);
        }

        $pdf->SetY($pdf->GetY()-1);
        $pdf->SetX(6);
        $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(98, 16, 25, 25, 17, 25));
        $pdf->Row(array(
          'TOTALES',
          String::formatoNumero($kilos/$cajas, 2, '', false),
          $cajas,
          $kilos,
          String::formatoNumero($precio/count($calidad['cajas']), 2, '$', false),
          String::formatoNumero($importe, 2, '$', false)), false, false);

      }

      if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
      {
        $pdf->AddPage();
      }

      $pdf->SetFont('helvetica','B', 8);
      // $pdf->SetX(6);
      $pdf->SetY($pdf->getY() + 6);
      $pdf->SetAligns(array('C', 'C', 'C', 'C'));
      $pdf->SetWidths(array(50, 50, 50, 50));
      $pdf->Row(array(
        'PAGADO',
        'NO PAGADO',
        'CANCELADO',
        'TOTAL IMPORTE'), false);

      $totalImporte = (floatval($totalPagado) + floatval($totalNoPagado)) - floatval($data['cancelados']);

      if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
      {
        $pdf->AddPage();
      }
      $pdf->SetAligns(array('C', 'C', 'C', 'C'));
      $pdf->SetWidths(array(50, 50, 50, 50));
      $pdf->Row(array(
        String::formatoNumero($totalPagado, 2, '$', false),
        String::formatoNumero($totalNoPagado, 2, '$', false),
        String::formatoNumero($data['cancelados'], 2, '$', false),
        String::formatoNumero($totalImporte, 2, '$', false)), false);

      $pdf->Output('REPORTE_DIARIO_ENTRADAS_'.$area['info']->nombre.'_'.$fecha->format('d/m/Y').'.pdf', 'I');
  }

  /**
   * REPORTE DE ACUMULADOS DE PRODUCTOS
   * @return [type] [description]
   */
  public function r_acumulados_data()
   {
      $response = array('data' => array(), 'tipo' => 'Entrada');
      $sql = $sql2 = '';

      if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
        $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
        $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
      }
      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
        $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
        $sql .= " AND Date(b.fecha_tara) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      }

      $this->load->model('areas_model');
      $_GET['farea'] = $this->input->get('farea') != '' ? $_GET['farea'] : $this->areas_model->getAreaDefault();
      if ($this->input->get('farea') != '')
        $sql .= " AND b.id_area = " . $_GET['farea'];

      if ($this->input->get('fid_empresa') != '')
        $sql .= " AND b.id_empresa = '".$_GET['fid_empresa']."'";

      //Filtros del tipo de pesadas
      if ($this->input->get('ftipo') != '')
        $sql .= " AND b.tipo = '{$_GET['ftipo']}'";
      $order_by = "p.nombre_fiscal";
      $group_by = "p.id_proveedor, p.nombre_fiscal, p.cuenta_cpi";
      $campo_id = "p.id_proveedor, p.nombre_fiscal AS proveedor, p.cuenta_cpi";
      $table_ms = 'LEFT JOIN proveedores p ON p.id_proveedor = b.id_proveedor';
      if($this->input->get('ftipo') == 'sa') {
        $order_by = "c.nombre_fiscal";
        $group_by = "c.id_cliente, c.nombre_fiscal, c.cuenta_cpi";
        $campo_id = "c.id_cliente, c.nombre_fiscal AS proveedor, c.cuenta_cpi";
        $table_ms = 'LEFT JOIN clientes c ON c.id_cliente = b.id_cliente';
        $response['tipo'] = "Salida";
      }

      $response['status'] = 'Todos';
      if ($this->input->get('fstatus') != ''){
        if ($this->input->get('fstatus') === '1'){
          $sql .= " AND (b.accion = 'p' OR b.accion = 'b')";
          $response['status'] = 'Pagados';
        }else{
          $sql .= " AND (b.accion = 'en' OR b.accion = 'sa')";
          $response['status'] = 'No Pagados';
        }
      }

      $query = $this->db->query(
        "SELECT Sum(b.kilos_neto) AS kilos,
            Sum(b.total_cajas) AS cajas,
            Sum(b.importe) AS importe,
            (CASE Sum(b.kilos_neto) WHEN 0 THEN (Sum(b.importe)/1) ELSE (Sum(b.importe)/Sum(b.kilos_neto)) END) AS precio,
            {$campo_id}
         FROM bascula b
         JOIN ( SELECT bascula_compra.id_bascula, sum(bascula_compra.precio) / count(bascula_compra.id_calidad)::double precision AS precio
                 FROM bascula_compra
                GROUP BY bascula_compra.id_bascula) bc ON b.id_bascula = bc.id_bascula
         {$table_ms}
        WHERE b.status = true
           {$sql}
        GROUP BY {$group_by}
        ORDER BY {$order_by} ASC
        "
      );
      if($query->num_rows() > 0)
        $response['data'] = $query->result();

      //Pagadas y pendientes de pago
      $result = $this->db->query("SELECT
         ( SELECT Sum(b.importe) FROM bascula b WHERE b.status = true AND accion IN('p', 'b') {$sql} ) AS pagadas,
         ( SELECT Sum(b.importe) FROM bascula b WHERE b.status = true AND accion IN('en', 'sa') {$sql} ) AS pendientes");
      $response['pagados_yno'] = $result->row();

      // Obtiene la informacion del Area filtrada.
      $this->load->model('areas_model');
      $response['area'] = $this->areas_model->getAreaInfo($_GET['farea']);

      return $response;
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Diario de Entradas.
    *
    * @return void
    */
   public function r_acumulados_pdf()
   {

      // Obtiene los datos del reporte.
      $data = $this->r_acumulados_data();

      $area = $data['area'];

      $fecha = new DateTime($_GET['ffecha1']);
      $fecha2 = new DateTime($_GET['ffecha2']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "REPORTE DE ACUMULADOS DE PRODUCTOS <{$area['info']->nombre}> DEL {$fecha->format('d/m/Y')} AL {$fecha2->format('d/m/Y')}";
      $pdf->titulo3 = $this->input->get('fempresa').' | '.$data['tipo'].' | '.$data['status'];

      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('L', 'L', 'R', 'R', 'R', 'R');
      $widths = array(20, 75, 30, 25, 20, 35);
      $header = array('CUENTA', 'NOMBRE', 'KILOS','CAJAS', 'P.P.', 'TOTAL');

      $total_kilos   = 0;
      $total_cajas   = 0;
      $total_importe = 0;

      foreach($data['data'] as $key => $proveedor)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, false, false);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
          $pdf->Row(array(
              $proveedor->cuenta_cpi,
              substr($proveedor->proveedor, 0, 42),
              String::formatoNumero($proveedor->kilos, 2, ''),
              String::formatoNumero($proveedor->cajas, 2, ''),
              String::formatoNumero($proveedor->precio, 2, '$', false),
              String::formatoNumero($proveedor->importe, 2, '$', false)
            ), false, false);
        $total_cajas   += $proveedor->cajas;
        $total_kilos   += $proveedor->kilos;
        $total_importe += $proveedor->importe;
      }

      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
        $pdf->Row(array(
            '',
            '',
            String::formatoNumero($total_kilos, 2, ''),
            String::formatoNumero($total_cajas, 2, ''),
            String::formatoNumero($total_importe/($total_kilos>0? $total_kilos: 1), 2, '$', false),
            String::formatoNumero($total_importe, 2, '$', false)
          ), false, false);

      //Total de pagadas no pagadas
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetX(6);
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(30, 45));
      $pdf->Row(array('Pagados', String::formatoNumero($data['pagados_yno']->pagadas, 2, '$', false)), false);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(6);
      $pdf->Row(array('No Pagados', String::formatoNumero($data['pagados_yno']->pendientes, 2, '$', false)), false);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(6);
      $pdf->Row(array('Total', String::formatoNumero( ($data['pagados_yno']->pendientes+$data['pagados_yno']->pagadas), 2, '$', false )), false);


      $pdf->Output('REPORTE_ACUMULADOS_PROD_'.$area['info']->nombre.'_'.$fecha->format('d/m/Y').'.pdf', 'I');
  }

  /**
    * Visualiza/Descarga el PDF para el Reporte Diario de Entradas.
    *
    * @return void
    */
   public function rmc_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->getMovimientos();

      // echo "<pre>";
      //   var_dump($data['totales']);
      // echo "</pre>";exit;

      $rmc = $data['movimientos'];

      $area = $data['area'];

      $proveedor = $data['proveedor'];
      // echo "<pre>";
      //   var_dump($proveedor);
      // echo "</pre>";exit;

      $fechaini = new DateTime($_GET['fechaini']);
      $fechaend = new DateTime($_GET['fechaend']);


      $tipo = "ENTRADAS/SALIDAS";
      if ($this->input->get('ftipop') != '')
        if ($this->input->get('ftipop') === '1')
          $tipo = "ENTRADAS";
        else
          $tipo = "SALIDAS";

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "MOVIMIENTOS DE CUENTA - {$tipo} <".$area['info']->nombre."> DEL DIA " . $fechaini->format('d/m/Y') . " AL " . $fechaend->format('d/m/Y');
      $pdf->titulo3 = strtoupper($proveedor['info']->nombre_fiscal) . " (CTA: " .$proveedor['info']->cuenta_cpi . ") \n FECHA/HORA DEL REPORTE: " . date('d/m/Y H:i:s');

      $pdf->noShowPages = false;
      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'C', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C');
      $widths = array(5, 14, 17, 18, 10, 11, 12, 13, 17, 17, 28, 30, 12);
      $header = array('',   'BOLETA', 'FECHA','CALIDAD',
                      'CAJS', 'PROM', 'KILOS', 'PRECIO','IMPORTE', 'TOTAL', 'TIPO PAGO', 'CONCEPTO', 'BONIF');

      $lastFolio = 0;
      $total_bonificaciones = 0;
      foreach($rmc as $key => $caja)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetY($pdf->GetY()-1);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, false);
        }

        $pdf->SetFont('helvetica','',8);
        $pdf->SetTextColor(0,0,0);

        $datos = array(($caja->id_bascula != $lastFolio) ? ($caja->status === 'p' ||  $caja->status === 'b' ? strtoupper($caja->status)  : '') : '',
                       ($caja->id_bascula != $lastFolio) ? $caja->folio : '',
                       ($caja->id_bascula != $lastFolio) ? $caja->fecha : '',
                       substr($caja->calidad, 0, 9),
                       $caja->cajas,
                       $caja->promedio,
                       String::formatoNumero($caja->kilos, 2, ''),
                       String::formatoNumero($caja->precio, 2, ''),
                       String::formatoNumero($caja->importe, 2, ''),
                       ($caja->id_bascula != $lastFolio) ? String::formatoNumero($caja->importe_todas, 2, '') : '',
                       ($caja->id_bascula != $lastFolio) ? strtoupper($caja->tipo_pago) : '',
                       ($caja->id_bascula != $lastFolio) ? $caja->concepto: '',
                       ($caja->id_bascula != $lastFolio ? (is_numeric($caja->id_bonificacion)? 'Si': ''): ''),
                      );

        $pdf->SetY($pdf->GetY()-1);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false, false);

        $lastFolio = $caja->id_bascula;

        if(is_numeric($caja->id_bonificacion))
          $total_bonificaciones += $caja->importe;
      }

      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $prom_total = floatval($data['totales']['kilos'])/(floatval($data['totales']['cajas'])>0? floatval($data['totales']['cajas']): 1);
      $pdf->Row(array('', '', '', '',
        $data['totales']['cajas'],
        String::formatoNumero($prom_total, 2, ''),
        $data['totales']['kilos'],
        $data['totales']['kilos'] != 0 ? String::formatoNumero(floatval($data['totales']['importe'])/floatval($data['totales']['kilos']), 3, '') : 0,
        String::formatoNumero($data['totales']['importe']),
        String::formatoNumero($data['totales']['total']),
        '',''
      ), false, false);

      if($pdf->GetY()+20 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetY($pdf->GetY() + 6);
      $pdf->SetX(6);
      $pdf->SetAligns(array('C', 'C', 'C'));
      $pdf->SetWidths(array(66, 66, 66));
      $pdf->Row(array(
        'PAGADO',
        'NO PAGADO',
        'TOTAL IMPORTE',), false);

      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(6);
      $pdf->SetAligns(array('C', 'C', 'C'));
      $pdf->SetWidths(array(66, 66, 66));
      $pdf->Row(array(
        String::formatoNumero($data['totales']['pagados']),
        String::formatoNumero($data['totales']['no_pagados']),
        String::formatoNumero($data['totales']['total'])
      ), false);

      $pdf->SetX(6);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(80));
      $pdf->Row(array(
        'Bonificado: '.String::formatoNumero($total_bonificaciones),
      ), false, false);

      $pdf->Output('REPORTE_MOVIMIENTOS_CUENTA.pdf', 'I');
  }


}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */