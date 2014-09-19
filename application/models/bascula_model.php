<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bascula_model extends CI_Model {

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

  public function addBascula($data=null, $bonificacion=false, $logBitacora = false, $usuario_auth = false)
  {
    $new_boleta = false;
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
          'cajas_prestadas' => empty($_POST['pcajas_prestadas']) ? 0 : $_POST['pcajas_prestadas'],
          'certificado' => isset($_POST['certificado']) ? 't' : 'f',
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
        {
          $data['id_proveedor'] = $this->input->post('pid_proveedor');
          $data['rancho']       = mb_strtoupper($this->input->post('prancho'), 'UTF-8');
        }
        else
        {
          $data['id_cliente'] = $this->input->post('pid_cliente');
          $data['rancho']     = '';
        }

        $this->db->insert('bascula', $data);
        $idb = $this->db->insert_id();
        $new_boleta = true;
      }

      $data2 = array(
        'importe'       => empty($_POST['ptotal']) ? 0 : $_POST['ptotal'],
        'total_cajas'   => empty($_POST['ptotal_cajas']) ? 0 : $_POST['ptotal_cajas'],
        'obcervaciones' => $this->input->post('pobcervaciones'),
        'rancho'       => mb_strtoupper($this->input->post('prancho'), 'UTF-8'),
        'certificado' => isset($_POST['certificado']) ? 't' : 'f',
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
        if($info_boleta['info'][0]->fecha_tara != '' && strtotime(substr($info_boleta['info'][0]->fecha_tara, 0, 16)) != strtotime(str_replace('T', ' ', $_POST['pfecha'])) ){
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

        $data2['cajas_prestadas'] = empty($_POST['pcajas_prestadas']) ? 0 : $_POST['pcajas_prestadas'];

        if (isset($_POST['pstatus'])){
          if($info_boleta['info'][0]->accion == 'b')
            $data2['accion'] = $info_boleta['info'][0]->accion;
          else
            $data2['accion'] = 'p';
        }

        if(isset($_POST['pfecha_pago']) && $data2['accion'] === 'p')
          $data2['fecha_pago'] = $_POST['pfecha_pago'];

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
              'aux_num_registro' => $_POST['pnum_registro'][$key],
            );
          }
        }
      }

      $this->updateBascula($idb, $data2, $cajas, $logBitacora, $usuario_auth);

      $msg = '7';
      if ($bonificacion)
        $msg = '12';

      return array('passes'=>true, 'msg'=>$msg, 'idb' => $idb, 'new_boleta' => $new_boleta);
    }

    $this->db->insert('bascula', $data);
    return array('passes'=>true);
  }

  public function updateBascula($id=null, $data=null, $cajas=null, $logBitacora = false, $usuario_auth = false, $all = true)
  {
    $id = is_null($id) ? $_GET['id'] : $id;

    if (is_null($data))
    {

    }

    if ($logBitacora && is_numeric($usuario_auth))
    {
      $this->logBitacora($id, $data, $usuario_auth, $cajas, $all);
    }

    $this->db->update('bascula', $data, array('id_bascula' => $id));
    if ( ! is_null($cajas) && count($cajas) > 0)
    {
      foreach ($cajas as $key => $caja)
      {
        unset($cajas[$key]['aux_num_registro']);
      }
    }

    if ( ! is_null($cajas) && count($cajas) > 0)
    {
      $this->db->delete('bascula_compra', array('id_bascula' => $id));
      $this->db->insert_batch('bascula_compra', $cajas);
    }

    return array('passes' => true);
  }

  /**
   * Obtiene la informacion de una bascula
   * @param  boolean $id
   * @param  boolean $basic_info
   * @return array
   */
  public function getBasculaInfo($id=false, $folio=0, $basic_info=false, $sql_ext=array())
  {
    $id = (isset($_GET['id']))? $_GET['id']: $id;

    if(count($sql_ext) > 0)
      $this->db->where($sql_ext);
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
                b.tipo,
                b.no_impresiones,
                b.certificado")
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
          ->select("bc.*, c.nombre as calidad, c.cuenta_cpi")
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

    //Actualiza el control de impresiones, se le suma 1
    //al valor de la BD para la siguiente impresion
    $this->db->where('id_bascula', $id)->set('no_impresiones', 'no_impresiones+1', false)
        ->update('bascula');

    foreach ($data['cajas'] as $key => $value)
    {
      if ($data['info'][0]->id_bonificacion != NULL)
        $data['cajas'][$key]->calidad = 'BONIF.';
    }

    $pdf = new mypdf_ticket();
    $pdf->titulo1 = $data['info'][0]->empresa;
    if($data['info'][0]->id_empresa != 2)
      $pdf->reg_fed = '';
    $pdf->SetFont('Arial','',8);
    $pdf->AddPage();

    $pdf->printTicket($data['info'][0], $data['cajas']);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }

  public function imprimir_boletaR($id_boleta)
  {
    $data = $this->getBasculaInfo($id_boleta);
    $data = $data['info'][0];

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->show_head = false;

    $pdf->AddPage();
    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetXY(0, 1);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(43, 20));
    $pdf->Row(array('BOLETA DE RECEPCION', String::formatoNumero($data->folio, 2, '') ), false, false);
    $pdf->Line(43, $pdf->GetY()-1, 62, $pdf->GetY()-1);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array($data->empresa), false, false);
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(30, 30));
    $pdf->Row(array('FECHA: '.String::fechaATexto(substr($data->fecha_bruto, 0, 10), '/c'), 'HORA: '.substr($data->fecha_bruto, 11, 8)), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array('Prov. '.$data->proveedor), false, false);
    $pdf->SetXY(2, $pdf->GetY()-2);
    $pdf->SetAligns(array('L', 'C', 'C'));
    $pdf->SetWidths(array(14, 22, 22));
    $pdf->Row(array('', 'CAJAS', 'PRECIO'), false, false);
    $pdf->SetAligns(array('L', 'L', 'L'));
    $pdf->SetXY(2, $pdf->GetY());
    $pdf->Row(array('IND', '', ''), false, true);
    $pdf->SetXY(2, $pdf->GetY());
    $pdf->Row(array('ALIM', '', ''), false, true);
    $pdf->SetXY(2, $pdf->GetY());
    $pdf->Row(array('FRUTA', '', ''), false, true);
    $pdf->SetXY(2, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(14, 44));
    $pdf->Row(array('LOTE', ''), false, true);
    $pdf->SetXY(2, $pdf->GetY());
    $pdf->Row(array('OBSERV', ''), false, true);
    $pdf->SetXY(0, $pdf->GetY()+4);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array('RECIBI: '), false, false);
    $pdf->SetFont('helvetica','', 7);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('C'));
    $pdf->Row(array('REG. ESJ97052763A0620061646'), false, false);

    $pdf->Rect(0.5, 0.5, 62, $pdf->GetY());

    $pdf->AutoPrint(true);
    $pdf->Output();
  }

  public function checkLimiteProveedor($idProveedor)
  {
    $total = $this->db->query(
      "SELECT COALESCE(SUM(importe), 0) AS total
       FROM bascula
       WHERE id_proveedor = {$idProveedor} AND
             tipo = 'en' AND
             status = 't'");

    $total = $total->result();

    $this->load->model('proveedores_facturacion_model');

    $info = $this->proveedores_facturacion_model->getLimiteProveedores($idProveedor, date('Y'));

    if (floatval($total[0]->total) > floatval($info['limite'])) return true;

    else return false;
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
               b.id_bonificacion,
               COALESCE((SELECT id_pago FROM banco_pagos_bascula WHERE status = 'f' AND id_bascula = b.id_bascula), 0) AS en_pago
        FROM bascula AS b
          LEFT JOIN bascula_compra AS bc ON b.id_bascula = bc.id_bascula
          {$table_ms}
          LEFT JOIN calidades AS ca ON ca.id_calidad = bc.id_calidad
          LEFT JOIN (SELECT bpb.id_bascula, bp.tipo_pago, bp.concepto
                    FROM bascula_pagos AS bp
                    INNER JOIN bascula_pagos_basculas AS bpb ON bpb.id_pago = bp.id_pago
                    WHERE bp.status = 't') AS pagos
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
        if(!is_numeric($caja->id_bonificacion))
        {
          $data['totales']['kilos']       += floatval($caja->kilos);
          $data['totales']['cajas']       += floatval($caja->cajas);
        }else
          $caja->calidad = 'BONIFICACION';
        // $data['precio_prom'] += floatval($caja->promedio);

        if ($caja->status === 'p' || $caja->status === 'b')
        {
          $data['totales']['pagados'] += floatval($caja->importe);
          if ($caja->status === 'p')
            $caja->tipo_pago = 'EFECTIVO';
        }else
          $data['totales']['no_pagados'] += floatval($caja->importe);

        if ($caja->tipo == 'en')
          $caja->tipo = 'E';
        elseif ($caja->tipo == 'sa')
          $caja->tipo = 'S';
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

  public function pago_basculas_banco($datos)
  {
    //Se registra el movimiento en la cuenta bancaria
    $this->load->model('banco_cuentas_model');
    $data_cuenta  = $this->banco_cuentas_model->getCuentaInfo( $datos['dcuenta'] );
    $data_cuenta  = $data_cuenta['info'];
    $_GET['id']   = $datos['boletas'][0]['id_bascula'];
    $inf_factura  = $this->getBasculaInfo($_GET['id'], 0, true);

    $resp = $this->banco_cuentas_model->addRetiro(array(
          'id_cuenta'           => $datos['dcuenta'],
          'id_banco'            => $data_cuenta->id_banco,
          'fecha'               => $datos['dfecha'],
          'numero_ref'          => $datos['dreferencia'],
          'concepto'            => $datos['descrip'],
          'monto'               => $datos['dmonto'],
          'tipo'                => 'f',
          'entransito'          => 't',
          'metodo_pago'         => $datos['fmetodo_pago'],
          'id_proveedor'        => $inf_factura['info'][0]->id_proveedor,
          'a_nombre_de'         => $inf_factura['info'][0]->proveedor,
          'id_cuenta_proveedor' => ($datos['fcuentas_proveedor']!=''? $datos['fcuentas_proveedor']: NULL),
          'clasificacion'       => 'elimon',
          ));

    if ($resp['error'] == false)
    {

      $bascula_pagos = array(
        'tipo_pago'  => $datos['fmetodo_pago'],
        'monto'      => $datos['dmonto'],
        'concepto'   => $datos['dconcepto'],
        'id_cuenta'  => $datos['dcuenta'],
      );

      $this->db->insert('bascula_pagos', $bascula_pagos);
      $id_bascula_pagos = $this->db->insert_id();

      $this->db->insert('banco_movimientos_bascula', array('id_movimiento' => $resp['id_movimiento'], 'id_bascula_pago' => $id_bascula_pagos ));

      $pesadas = array();
      $pesadas_update = array();
      foreach ($datos['boletas'] as $pesada)
      {
        $this->db->update('bascula', array('accion' => 'b'), array('id_bascula' => $pesada['id_bascula']));

        $pesadas[] = array(
          'id_pago' => $id_bascula_pagos,
          'id_bascula' => $pesada['id_bascula']
        );
        $this->db->update('banco_pagos_bascula', array('status' => 't'), array('id_bascula' => $pesada['id_bascula']));
      }

      $this->db->insert_batch('bascula_pagos_basculas', $pesadas);
    }


    return array('passess' => true);
  }

  public function getPagos($paginados=true)
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
      $sql = " AND (
                      ( lower(p.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' ) )";

    if (isset($_GET['fechaini']))
      if ($this->input->get('fechaini') !== '')
        $sql .= " AND DATE(b.fecha_bruto) >= '".$this->input->get('fechaini')."'";

    if (isset($_GET['fechaend']))
      if ($this->input->get('fechaend') !== '')
        $sql .= " AND DATE(b.fecha_bruto) <= '".$this->input->get('fechaend')."'";

    $str_query =
        "SELECT
                bp.id_pago,
                bp.tipo_pago,
                bp.monto,
                bp.concepto,
                string_agg(b.folio::text, ', ') AS folios,
                p.nombre_fiscal AS proveedor
        FROM bascula AS b
          INNER JOIN areas AS a ON a.id_area = b.id_area
          LEFT JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor
          INNER JOIN bascula_pagos_basculas AS bpb ON b.id_bascula = bpb.id_bascula
          INNER JOIN bascula_pagos AS bp ON bpb.id_pago = bp.id_pago
        WHERE (b.accion = 'p' OR b.accion = 'b') AND bp.status = 't' {$sql}
        GROUP BY p.nombre_fiscal, bp.id_pago, bp.tipo_pago, bp.monto, bp.concepto
        ORDER BY bp.id_pago DESC
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

  public function cancelar_pago($id_pago, $delete=false)
  {
    $basculas = $this->db->query("SELECT b.id_bascula, b.accion FROM bascula_pagos_basculas AS bpb
                                  INNER JOIN bascula AS b ON b.id_bascula = bpb.id_bascula WHERE bpb.id_pago = {$id_pago}");
    foreach ($basculas->result() as $key => $value)
    {
      if($value->accion != 'p')
        $this->db->update('bascula', array('accion' => 'sa'), "id_bascula = {$value->id_bascula}");
    }
    if($delete)
    {
      //Elimina el mov del banco
      $data_bascula = $this->db->query("SELECT id_movimiento, id_bascula_pago
                                        FROM banco_movimientos_bascula
                                        WHERE id_bascula_pago = {$id_pago}")->result();
      if(count($data_bascula) > 0){
        foreach ($data_bascula as $key => $value) {
          $this->db->delete('banco_movimientos', "id_movimiento = {$value->id_movimiento}");
        }
      }

      $this->db->delete('bascula_pagos', "id_pago = {$id_pago}");
    }
    else
      $this->db->update('bascula_pagos', array('status' => 'f'), "id_pago = {$id_pago}");
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
      $_GET['ffecha2'] = $this->input->get('ffecha2') != '' ? $_GET['ffecha2'] : date('Y-m-d');
      $fecha_compara = 'fecha_tara';

      $this->load->model('areas_model');
      $_GET['farea'] = $this->input->get('farea') != '' ? $_GET['farea'] : $this->areas_model->getAreaDefault();
      if ($this->input->get('farea') != ''){
        $sql .= " AND b.id_area = " . $_GET['farea'];
        $sql2 .= " AND b.id_area = " . $_GET['farea'];
      }

      $calidad_val = null;
      if(isset($_GET['fcalidad']{0})) {
        $calidad_val = $_GET['fcalidad'];
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
      {
        if ($this->input->get('fstatus') === '1')
          if($this->input->get('fefectivo') == 'si')
          {
            $sql .= " AND b.accion = 'p'";
            $fecha_compara = 'fecha_pago';
          }
          else
            $sql .= " AND (b.accion = 'p' OR b.accion = 'b')";
        else
          $sql .= " AND (b.accion = 'en' OR b.accion = 'sa')";
      }

      $sql .= " AND DATE(b.{$fecha_compara}) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      $sql2 .= " AND DATE(b.{$fecha_compara}) BETWEEN '".$_GET['ffecha1']."'  AND '".$_GET['ffecha2']."' ";

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
          b.accion AS pagado,
          Date(b.{$fecha_compara}) AS fecha
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
          if ($calidad_val == $calidad->id_calidad || $calidad_val === null) {
            $rde[$key] = array('calidad' => $calidad->nombre, 'cajas' => array());
            foreach ($query->result() as $key2 => $caja)
              if ($caja->id_calidad == $calidad->id_calidad)
                $rde[$key]['cajas'][] = $caja;
          }
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
      $fecha2 = new DateTime($_GET['ffecha2']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');

      if (isset($_GET['fid_empresa']) && $_GET['fid_empresa'] !== '')
      {
        $this->load->model('empresas_model');
        $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('fid_empresa'));

        if ($empresa['info']->logo !== '')
          $pdf->logo = $empresa['info']->logo;
        $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      }

      $pdf->titulo2 = "REPORTE DIARIO DE ENTRADAS <".$area['info']->nombre."> <".$data['tipo'].'>';
      $pdf->titulo3 = $fecha->format('d/m/Y')." Al ".$fecha2->format('d/m/Y')." | ".$this->input->get('fproveedor').' | '.$this->input->get('fempresa');

      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'C', 'L', 'C', 'C', 'C', 'C', 'C');
      $aligns1 = array('C', 'C', 'C', 'L', 'R', 'R', 'R', 'R', 'R');
      $widths = array(6, 20, 17, 55, 16, 25, 25, 17, 25);
      $header = array('',   'FECHA', 'BOLETA','NOMBRE', 'PROM',
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
                         $caja->fecha,
                         $caja->folio,
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
          String::formatoNumero($importe/$kilos, 2, '$', false),
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

  public function rdefull_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_diario_entradas.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Obtiene los datos del reporte.
    $data = $this->rde_data();

    $rde = $data['rde'];

    $area = $data['area'];

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $titulo1 = '';
    if (isset($_GET['fid_empresa']) && $_GET['fid_empresa'] !== '')
    {
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $titulo1 = $empresa['info']->nombre_fiscal;
    }

    $titulo2 = "REPORTE DIARIO DE ENTRADAS <".$area['info']->nombre."> <".$data['tipo'].'>';
    $titulo3 = $fecha->format('d/m/Y')." Al ".$fecha2->format('d/m/Y')." | ".$this->input->get('fproveedor').' | '.$this->input->get('fempresa');

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
          <td style="width:50px;border:1px solid #000;background-color: #cccccc;">BOLETA</td>
          <td style="width:80px;border:1px solid #000;background-color: #cccccc;">CUENTA</td>
          <td style="width:300px;border:1px solid #000;background-color: #cccccc;">NOMBRE</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">PROM</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">CAJAS</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">KILOS</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">PRECIO</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">IMPORTE</td>
        </tr>';
    $totalPagado    = 0;
    $totalNoPagado  = 0;
    $totalCancelado = 0;
    foreach($rde as $key => $calidad)
    {
      $promedio = 0;
      $cajas    = 0;
      $kilos    = 0;
      $precio   = 0;
      $importe  = 0;

      $html .= '<tr>
            <td colspan="9" style="font-size:14px;border:1px solid #000;">'.$calidad['calidad'].'</td>
          </tr>';
      foreach ($calidad['cajas'] as $caja)
      {
        $promedio += $caja->promedio;
        $cajas    += $caja->cajas;
        $kilos    += $caja->kilos;
        $precio   += $caja->precio;
        $importe  += $caja->importe;

        if ($caja->pagado === 'p' || $caja->pagado === 'b')
          $totalPagado += $caja->importe;
        else
          $totalNoPagado += $caja->importe;

        $html .= '<tr>
            <td style="width:30px;border:1px solid #000;">'.(($caja->pagado === 'p' || $caja->pagado === 'b') ? ucfirst($caja->pagado) : '').'</td>
            <td style="width:50px;border:1px solid #000;">'.$caja->folio.'</td>
            <td style="width:80px;border:1px solid #000;">'.$caja->cuenta_cpi.'</td>
            <td style="width:300px;border:1px solid #000;">'.substr($caja->proveedor, 0, 28).'</td>
            <td style="width:100px;border:1px solid #000;">'.$caja->promedio.'</td>
            <td style="width:100px;border:1px solid #000;">'.$caja->cajas.'</td>
            <td style="width:100px;border:1px solid #000;">'.$caja->kilos.'</td>
            <td style="width:100px;border:1px solid #000;">'.$caja->precio.'</td>
            <td style="width:100px;border:1px solid #000;">'.$caja->importe.'</td>
          </tr>';
      }

      $html .= '
        <tr style="font-weight:bold">
          <td colspan="4">TOTALES</td>
          <td style="border:1px solid #000;">'.($kilos/$cajas).'</td>
          <td style="border:1px solid #000;">'.$cajas.'</td>
          <td style="border:1px solid #000;">'.$kilos.'</td>
          <td style="border:1px solid #000;">'.($importe/$kilos).'</td>
          <td style="border:1px solid #000;">'.$importe.'</td>
        </tr>
        <tr>
          <td colspan="9"></td>
        </tr>
        <tr>
          <td colspan="9"></td>
        </tr>';
    }
    $totalImporte = (floatval($totalPagado) + floatval($totalNoPagado)) - floatval($data['cancelados']);
    $html .= '
        <tr style="font-weight:bold">
          <td colspan="3">PAGADO</td>
          <td colspan="2">NO PAGADO</td>
          <td colspan="2">CANCELADO</td>
          <td colspan="2">TOTAL IMPORTE</td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="3">'.$totalPagado.'</td>
          <td colspan="2">'.$totalNoPagado.'</td>
          <td colspan="2">'.$data['cancelados'].'</td>
          <td colspan="2">'.$totalImporte.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }


  public function rde_xls()
  {
    $res = $this->rde_data();

    $data = array();
    foreach ($res['rde'] as $key => $calidad)
    {
      foreach ($calidad['cajas'] as $key => $caja)
      {
        if (array_key_exists($caja->folio, $data))
          $data[$caja->folio]->importe += $caja->importe;
        else
          $data[$caja->folio] = $caja;
      }
    }

    $this->load->library('myexcel');
    $xls = new myexcel();

    $worksheet =& $xls->workbook->addWorksheet();

    $xls->titulo2 = 'REPORTE DIARIO DE ENTRADAS';
    $xls->titulo3 = "<".$res['area']['info']->nombre."> DEL DIA " . $this->input->get('ffecha1');
    $xls->titulo4 = 'Pagos en efectivo';

    $row=0;
    //Header
    $xls->excelHead($worksheet, $row, 8, array(
        array($xls->titulo2, 'format_title2'),
        array($xls->titulo3, 'format_title3'),
        array($xls->titulo4, 'format_title3')
    ));

    foreach ($data as $key => $value)
    {
      $data[$key]->colnull = '';
    }

    $row +=3;
    $xls->excelContent($worksheet, $row, $data, array(
        'head' => array('BOLETA', 'PRODUCTOR', '', '', 'IMPORTE'),
        'conte' => array(
            array('name' => 'folio', 'format' => 'format4', 'sum' => -1),
            array('name' => 'proveedor', 'format' => 'format4', 'sum' => -1),
            array('name' => 'colnull', 'format' => 'format4', 'sum' => -1),
            array('name' => 'colnull', 'format' => 'format4', 'sum' => -1),
            array('name' => 'importe', 'format' => 'format4', 'sum' => 0),
          )
    ));

    $xls->workbook->send('reporte_diario_entradas.xls');
    $xls->workbook->close();
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

      if (isset($_GET['fid_empresa']{0}))
      {
        $this->load->model('empresas_model');
        $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('fid_empresa'));

        if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;
        $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      }

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

  public function pagarBoleta($idBascula)
  {
    $this->db->update('bascula', array('accion' => 'p', 'fecha_pago' => date("Y-m-d H:i:s")), array('id_bascula' => $idBascula));
  }

  public function logBitacora($idBascula, $data, $usuario_auth, $cajas = null, $all = true)
  {
    $camposExcluidos = array(
      'fecha_bruto' => '',
      'fecha_tara'  => '',
      'kilos_neto2' => '',
      // 'accion'      => '',
    );

    // Quita los campos que no seran verificados.
    $data = array_diff_key($data, $camposExcluidos);

    // Array asoc que asocia el nombre del campo de la tabla con un nombre
    // mas entendible para el usuario.
    $campos = array(
      'tipo'            => 'Tipo',
      'id_area'         => 'Area',
      'id_empresa'      => 'Empresa',
      'id_cliente'      => 'Cliente',
      'id_proveedor'    => 'Proveedor',
      'rancho'          => 'Rancho',
      'id_camion'       => 'Camion',
      'id_chofer'       => 'Chofer',
      'kilos_bruto'     => 'Kilos Brutos',
      'kilos_tara'      => 'Kilos Tara',
      'cajas_prestadas' => 'Cajas Prestadas',
      'kilos_neto'      => 'Kilos Neto',
      'no_lote'         => 'No. Lote',
      'chofer_es_productor' => 'Chofer es productor',
      'id_bonificacion' => 'Bonificacion',
      'importe'         => 'Importe',
      'total_cajas'     => 'Total Cajas',
      'obcervaciones'   => 'Observaciones',
      'accion'          => 'Accion',
      'certificado'     => 'Certificado',
      'fecha_pago'      => 'Fecha de pago',
    );

    // Campos que son ids, para facilitar la busqueda de sus valores.
    $camposIds = array(
      'id_area'      => "SELECT nombre as dato FROM areas WHERE id_area = ?",
      'id_empresa'   => "SELECT nombre_fiscal as dato FROM empresas WHERE id_empresa = ?",
      'id_cliente'   => "SELECT nombre_fiscal as dato FROM clientes WHERE id_cliente = ?",
      'id_proveedor' => "SELECT nombre_fiscal as dato FROM proveedores WHERE id_proveedor = ?",
      'id_camion'    => "SELECT placa as dato FROM camiones WHERE id_camion = ?",
      'id_chofer'    => "SELECT nombre as dato FROM choferes WHERE id_chofer = ?",
    );

    // Obtiene la informacion de la pesada.
    $info = $this->getBasculaInfo($idBascula);

    // echo "<pre>";
    //   var_dump($info['info'][0], $data);
    // echo "</pre>";exit;

    $camposEditados = array();
    $fecha = date('Y-m-d H:i:s');

    // Query para obtener la ultima edicion de la boleta para la fecha indicada.
    $sql = $this->db->query(
      "SELECT no_edicion
       FROM bascula_bitacora
       WHERE id_bascula = {$idBascula} AND
             DATE(fecha) = DATE('".$fecha."')
        ORDER BY (id) DESC
        LIMIT 1
    ");

    $noEdicion = ($sql->num_rows() > 0) ? ($sql->result()[0]->no_edicion + 1) : 1;

    // Recorre los campos para verificar sus valores y si alguno cambio entonces
    // es agregado al array $camposEditados
    foreach ($info['info'][0] as $campoDb => $valorDb)
    {
      if ($all)
      {
        if (array_key_exists($campoDb, $camposIds))
        {
          if ($data[$campoDb] === null)
          {
            $data[$campoDb] = 0;
          }
        }
      }

      if (isset($data[$campoDb]))
      {
        if ($valorDb != $data[$campoDb])
        {
          if (array_key_exists($campoDb, $camposIds))
          {
            if ($valorDb !== null && $valorDb !== '')
            {
              $query = $this->db->query(str_replace('?', $valorDb, $camposIds[$campoDb]));
              $antes = $query->result()[0]->dato;
              $query->free_result();
            }
            else
            {
              $antes = '';
            }

            if ($data[$campoDb] != 0 && $data[$campoDb] != '')
            {
              $query = $this->db->query(str_replace('?', $data[$campoDb], $camposIds[$campoDb]));
              $despues = $query->result()[0]->dato;
            }
            else
            {
              $despues = '';
            }
          }
          else
          {
            $antes = $valorDb?: '';
            $despues = $data[$campoDb];
          }

          $camposEditados[] = array(
            'id_usuario_auth'     => $usuario_auth,
            'id_usuario_logueado' => $this->session->userdata['id_usuario'],
            'id_bascula'          => $idBascula,
            'fecha'               => $fecha,
            'no_edicion'          => $noEdicion,
            'antes'               => $antes,
            'despues'             => $despues,
            'campo'               => $campos[$campoDb],
          );
        }
      }
    }

    if ($all)
    {
      // Verifica los cambios en las Cajas
      $cajasDb = $this->db->query(
        "SELECT *
        FROM bascula_compra
        WHERE id_bascula = $idBascula
        ORDER BY num_registro ASC
      ");

      // Si la boleta tiene cajas.
      if ($cajasDb->num_rows() > 0)
      {
        $cajasDb = $cajasDb->result();

        // echo "<pre>";
        //   var_dump($cajasDb, $cajas);
        // echo "</pre>";exit;

        // Si hay cajas recibidas.
        if (count($cajas) > 0)
        {
          // Recorre las cajas de la bdd.
          foreach ($cajasDb as $cajaDb)
          {
            // Auxiliar para saber si la caja que se esta verificando existe
            // entre las cajas que llegaron.
            $existe = false;

            // Recorre las cajas que llegaron del form u otro lado.
            foreach ($cajas as $key => $cajaRec)
            {
              // Si la caja es nueva.
              if ($cajaRec['aux_num_registro'] == '')
              {
                $cRec = array_diff_key($cajaRec, array('id_bascula' => '', 'num_registro' => '', 'aux_num_registro' => ''));
                $despues = implode('|', $cRec);

                $camposEditados[] = array(
                  'id_usuario_auth'     => $usuario_auth,
                  'id_usuario_logueado' => $this->session->userdata['id_usuario'],
                  'id_bascula'          => $idBascula,
                  'fecha'               => $fecha,
                  'no_edicion'          => $noEdicion,
                  'antes'               => "",
                  'despues'             => "calidad|cajas|kilos|promedio|precio|importe \n $despues",
                  'campo'               => 'Cajas',
                );

                unset($cajas[$key]);
              }

              // Si la caja es una de las que existen en los registros de la bdd entra.
              else
              {
                if ($cajaDb->num_registro == $cajaRec['aux_num_registro'])
                {
                  $existe = true;

                  $cDb = array_diff_key((array)$cajaDb, array('id_bascula' => '', 'num_registro' => '', 'aux_num_registro' => ''));
                  $antes = implode('|', $cDb);

                  $cRec = array_diff_key($cajaRec, array('id_bascula' => '', 'num_registro' => '', 'aux_num_registro' => ''));
                  $despues = implode('|', $cRec);

                  if ($antes !== $despues)
                  {
                    $camposEditados[] = array(
                      'id_usuario_auth'     => $usuario_auth,
                      'id_usuario_logueado' => $this->session->userdata['id_usuario'],
                      'id_bascula'          => $idBascula,
                      'fecha'               => $fecha,
                      'no_edicion'          => $noEdicion,
                      'antes'               => "calidad|cajas|kilos|promedio|precio|importe \n $antes",
                      'despues'             => "calidad|cajas|kilos|promedio|precio|importe \n $despues",
                      'campo'               => 'Cajas',
                    );
                  }

                  // break 2;
                }
                // else
                // {
                //   $existe = false;
                // }
              }
            }

            // Si se da el caso que la caja si existe en los registros de la bdd pero
            // no en los que llegaron del form entonces entra.
            if ( ! $existe)
            {
              $cDb = array_diff_key((array)$cajaDb, array('id_bascula' => '', 'num_registro' => '', 'aux_num_registro' => ''));
              $antes = implode('|', $cDb);

              $camposEditados[] = array(
                'id_usuario_auth'     => $usuario_auth,
                'id_usuario_logueado' => $this->session->userdata['id_usuario'],
                'id_bascula'          => $idBascula,
                'fecha'               => $fecha,
                'no_edicion'          => $noEdicion,
                'antes'               => "calidad|cajas|kilos|promedio|precio|importe \n $antes",
                'despues'             => "Eliminado",
                'campo'               => 'Cajas',
              );
            }
          }
          // echo "<pre>";
          //   var_dump($cajas);
          // echo "</pre>";exit;
        }

        // Si no hay cajas entonces significa que eliminaron todas las cajas del form
        // entonces no se pueden comparar con las que existe en la bdd.
        else
        {
          foreach ($cajasDb as $caja)
          {
            $c = array_diff_key((array)$caja, array('id_bascula' => '', 'num_registro' => '', 'aux_num_registro' => ''));
            $antes = implode('|', $c);

            $camposEditados[] = array(
              'id_usuario_auth'     => $usuario_auth,
              'id_usuario_logueado' => $this->session->userdata['id_usuario'],
              'id_bascula'          => $idBascula,
              'fecha'               => $fecha,
              'no_edicion'          => $noEdicion,
              'antes'               => "calidad|cajas|kilos|promedio|precio|importe \n $antes",
              'despues'             => "Eliminado",
              'campo'               => 'Cajas',
            );
          }
        }
        // echo "<pre>";
        //   var_dump($camposEditados);
        // echo "</pre>";exit;
      }

      // Si la boleta no tiene cajas pero se registraron nuevas.
      else if ($cajasDb->num_rows() === 0 && count($cajas) > 0)
      {
        foreach ($cajas as $caja)
        {
          $c = array_diff_key($caja, array('id_bascula' => '', 'num_registro' => '', 'aux_num_registro' => ''));
          $despues = implode('|', $c);

          $camposEditados[] = array(
            'id_usuario_auth'     => $usuario_auth,
            'id_usuario_logueado' => $this->session->userdata['id_usuario'],
            'id_bascula'          => $idBascula,
            'fecha'               => $fecha,
            'no_edicion'          => $noEdicion,
            'antes'               => '',
            'despues'             => "calidad|cajas|kilos|promedio|precio|importe \n $despues",
            'campo'               => 'Cajas',
          );
        }
      }
    }

    if (count($camposEditados) > 0)
    {
      $this->db->insert_batch('bascula_bitacora', $camposEditados);
    }

    // echo "<pre>";
    //   var_dump($camposEditados);
    // echo "</pre>";exit;
  }

  private function bitacora()
  {
    $sql = "";
    if ((isset($_GET['ffecha1']) && $_GET['ffecha1']) && (isset($_GET['ffecha2']) && $_GET['ffecha2']))
    {
      $sql .= " AND DATE(ba.fecha_tara) >= '{$_GET['ffecha1']}' AND DATE(ba.fecha_tara) <= '{$_GET['ffecha2']}'";
    }
    else
    {
      $sql .= " AND DATE(ba.fecha_tara) >= '".date('Y-m-d')."' AND DATE(ba.fecha_tara) <= '".date('Y-m-d')."'";
    }

    if (isset($_GET['farea']) && $_GET['farea'])
    {
      $sql .= " AND ba.id_area = {$_GET['farea']}";
    }

    if (isset($_GET['ftipo']) && $_GET['ftipo'])
    {
      $sql .= " AND ba.tipo = '{$_GET['ftipo']}'";
    }

    if (isset($_GET['fid_empresa']) && $_GET['fid_empresa'])
    {
      $sql .= " AND ba.id_empresa = {$_GET['fid_empresa']}";
    }

    if (isset($_GET['fstatus']) && $_GET['fstatus'])
    {
      $sql .=  $_GET['fstatus'] == 1 ? " AND ba.accion = 'p'" : " AND ba.accion != 'p'";
    }

    $query = $this->db->query(
      "SELECT ba.id_bascula,
              ba.folio,
              DATE(ba.fecha_tara) as fecha_bascula,
              em.nombre_fiscal as empresa,
              ba.tipo,
              ar.nombre as area,
              ba.accion as status,
              (us.nombre || ' ' || us.apellido_paterno || ' ' || us.apellido_materno) as usuario_logueado,
              (us2.nombre || ' ' || us2.apellido_paterno || ' ' || us2.apellido_materno) as usuario_auth,
              bb.no_edicion,
              bb.antes,
              bb.despues,
              bb.campo,
              bb.fecha
       FROM bascula_bitacora bb
       INNER JOIN bascula ba ON ba.id_bascula = bb.id_bascula
       INNER JOIN usuarios us ON us.id = bb.id_usuario_logueado
       INNER JOIN usuarios us2 ON us2.id = bb.id_usuario_auth
       INNER JOIN empresas em ON em.id_empresa = ba.id_empresa
       INNER JOIN areas ar ON ar.id_area = ba.id_area
       WHERE 1=1 $sql
       ORDER BY bb.id_bascula, bb.fecha, bb.no_edicion
    ");

    return $query->result();
  }

  public function bitacora_pdf()
  {
    // Obtiene los datos del reporte.
    $data = $this->bitacora();

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $fecha = new DateTime(isset($_GET['ffecha1'])?$_GET['ffecha1']:date('Y-m-d'));
    $fecha2 = new DateTime(isset($_GET['ffecha2'])?$_GET['ffecha2']:date('Y-m-d'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if (isset($_GET['fid_empresa']) && $_GET['fid_empresa'])
    {
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('fid_empresa'));

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    }

    $pdf->titulo2 = "BITACORA BASCULA DEL {$fecha->format('d/m/Y')} AL {$fecha2->format('d/m/Y')}";
    // $pdf->titulo3 = $this->input->get('fempresa').' | '.$data['tipo'].' | '.$data['status'];

    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);

    $aligns = array('L', 'L', 'L', 'L', 'L', 'L');
    $widths = array(20, 20, 85, 40, 20, 20);
    $header = array('FECHA', 'FOLIO', 'EMPRESA', 'AREA', 'TIPO', 'STATUS');

    $aligns2 = array('L', 'L', 'L', 'L', 'L', 'L');
    $widths2 = array(35, 35, 20, 40, 40, 26);
    $header2 = array('USUARIO MOVIMIENTO', 'USUARIO AUTORIZO', 'CAMPO', 'ANTES', 'DESPUES', 'FECHA');

    $aux = 0;

    foreach($data as $key => $log)
    {
      if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      {
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(240,240,240);
        $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, 1, 1);
      }

      if ($aux != $log->id_bascula)
      {
        $pdf->SetFont('helvetica', 'B', 8);

        // se colocaria la info de la bascula
        $pdf->SetY($pdf->GetY());
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
            $log->fecha_bascula,
            $log->folio,
            $log->empresa,
            $log->area,
            $log->tipo === 'sa' ? 'SALIDA' : 'ENTRADA',
            $log->status === 'p' ? 'PAGADO' : 'NO PAGADO',
          ), false, false);

        // se coloca el header de la tabla de los registros
        $pdf->SetFont('helvetica','', 7);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255, 255, 255);

        $pdf->SetY($pdf->GetY() + 5);
        $pdf->SetX(15);
        $pdf->SetAligns($aligns2);
        $pdf->SetWidths($widths2);
        $pdf->Row($header2, false, 1);

        $pdf->SetX(15);
        $pdf->Row(array(
            $log->usuario_logueado,
            $log->usuario_auth,
            $log->campo,
            $log->antes,
            $log->despues,
            str_replace('-05', '', $log->fecha),
          ), false, false);

        $aux = $log->id_bascula;
      }
      else
      {
        $pdf->SetFont('helvetica','', 7);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255, 255, 255);

        $pdf->SetY($pdf->GetY());
        $pdf->SetX(15);
        $pdf->SetAligns($aligns2);
        $pdf->SetWidths($widths2);
        $pdf->Row(array(
            $log->usuario_logueado,
            $log->usuario_auth,
            $log->campo,
            $log->antes,
            $log->despues,
            str_replace('-05', '', $log->fecha),
          ), false, false);
      }
    }

    $pdf->Output('BITACORA_BASCULA_'.$fecha->format('d/m/Y').'.pdf', 'I');
  }


  public function getFacturas($perpage = '40', $autorizadas = true)
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
      $sql = " AND Date(co.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(co.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(co.fecha) = '".$this->input->get('ffecha2')."'";


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

    $query = BDUtil::pagination(
        "SELECT co.id_factura,
                co.id_proveedor, p.nombre_fiscal AS proveedor,
                co.id_empresa, e.nombre_fiscal as empresa,
                co.serie, co.folio, co.fecha, co.status, co.xml, co.total
        FROM bascula_facturas AS co
        INNER JOIN proveedores AS p ON p.id_proveedor = co.id_proveedor
        INNER JOIN empresas AS e ON e.id_empresa = co.id_empresa
        WHERE 1 = 1 {$sql}
        ORDER BY (co.fecha, co.folio) DESC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'compras'           => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['compras'] = $res->result();

    return $response;
  }

  public function cancelarFactura($id_factura)
  {
    $this->db->update('bascula_facturas', array('status' => 'ca'), "id_factura = {$id_factura}");
  }

  public function getInfoFactura($id_factura)
  {
    $res = $this->db->query("SELECT *
                               FROM bascula_facturas
                               WHERE id_factura = {$id_factura}");
    $response = array('info' => array(), 'boletas' => array());
    if ($res->num_rows() > 0) {
      $response['info'] = $res->row();

      $res = $this->db->query("SELECT b.id_bascula, b.folio, b.fecha_bruto, b.importe
                                 FROM bascula_facturas_boletas bf
                                  INNER JOIN bascula b ON b.id_bascula = bf.id_bascula
                                 WHERE bf.id_factura = {$id_factura}");
      $response['boletas'] = $res->result();
    }
    return $response;
  }

  public function updateFactura($compraId, $proveedorId, $xml)
  {
    $compra = array(
      'subtotal'      => String::float($this->input->post('totalImporte')),
      'importe_iva'   => String::float($this->input->post('totalImpuestosTrasladados')),
      'total'         => String::float($this->input->post('totalOrden')),
      'fecha'         => $this->input->post('fecha'),
      'serie'         => $this->input->post('serie'),
      'folio'         => $this->input->post('folio'),
    );

    // Realiza el upload del XML.
    if ($xml && $xml['tmp_name'] !== '')
    {
      $this->load->library("my_upload");
      $this->load->model('proveedores_model');
      $this->load->model('compras_model');

      $proveedor = $this->proveedores_model->getProveedorInfo($proveedorId);
      $path      = $this->compras_model->creaDirectorioProveedorCfdi($proveedor['info']->nombre_fiscal);

      $xmlName   = ($_POST['serie'] !== '' ? $_POST['serie'].'-' : '') . $_POST['folio'].'.xml';

      $config_upload = array(
        'upload_path'     => $path,
        'allowed_types'   => '*',
        'max_size'        => '2048',
        'encrypt_name'    => FALSE,
        'file_name'       => $xmlName,
      );
      $this->my_upload->initialize($config_upload);

      $xmlData = $this->my_upload->do_upload('xml');

      $xmlFile     = explode('application', $xmlData['full_path']);

      $compra['xml'] = 'application'.$xmlFile[1];
    }
    $this->db->update('bascula_facturas', $compra, array('id_factura' => $compraId));

    if (is_array($this->input->post('pid_bascula'))) {
      $this->db->delete('bascula_facturas_boletas', "id_factura = {$compraId}");
      foreach ($_POST['pid_bascula'] as $key => $value)
      {
        $this->db->insert('bascula_facturas_boletas', array(
          'id_factura' => $compraId,
          'id_bascula' => $value,
          ));
      }
    }
  }

  public function addFactura($datos, $xml)
  {
    $compra = array(
      'id_empresa'    => $datos['empresaId'],
      'id_proveedor'  => $datos['proveedorId'],
      'subtotal'      => String::float($datos['totalImporte']),
      'importe_iva'   => String::float($datos['totalImpuestosTrasladados']),
      'total'         => String::float($datos['totalOrden']),
      'fecha'         => $datos['fecha'],
      'serie'         => $datos['serie'],
      'folio'         => $datos['folio'],
    );

    // Realiza el upload del XML.
    if ($xml && $xml['tmp_name'] !== '')
    {
      $this->load->library("my_upload");
      $this->load->model('proveedores_model');
      $this->load->model('compras_model');

      $proveedor = $this->proveedores_model->getProveedorInfo($proveedorId);
      $path      = $this->compras_model->creaDirectorioProveedorCfdi($proveedor['info']->nombre_fiscal);

      $xmlName   = ($datos['serie'] !== '' ? $datos['serie'].'-' : '') . $datos['folio'].'.xml';

      $config_upload = array(
        'upload_path'     => $path,
        'allowed_types'   => '*',
        'max_size'        => '2048',
        'encrypt_name'    => FALSE,
        'file_name'       => $xmlName,
      );
      $this->my_upload->initialize($config_upload);

      $xmlData = $this->my_upload->do_upload('xml');

      $xmlFile     = explode('application', $xmlData['full_path']);

      $compra['xml'] = 'application'.$xmlFile[1];
    }
    $this->db->insert('bascula_facturas', $compra);
    $compraId = $this->db->insert_id();

    if (is_array($this->input->post('pid_bascula'))) {
      $this->db->delete('bascula_facturas_boletas', "id_factura = {$compraId}");
      foreach ($_POST['pid_bascula'] as $key => $value)
      {
        $this->db->insert('bascula_facturas_boletas', array(
          'id_factura' => $compraId,
          'id_bascula' => $value,
          ));
      }
    }
  }

}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */