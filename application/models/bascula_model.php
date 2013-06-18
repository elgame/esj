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
                cl.nombre_fiscal AS cliente
        FROM bascula AS b
        INNER JOIN empresas AS e ON e.id_empresa = b.id_empresa
        INNER JOIN areas AS a ON a.id_area = b.id_area
        LEFT JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor
        LEFT JOIN clientes AS cl ON cl.id_cliente = b.id_cliente
        LEFT JOIN choferes AS ch ON ch.id_chofer = b.id_chofer
        LEFT JOIN camiones AS ca ON ca.id_camion = b.id_camion
        ".$sql."
        ORDER BY folio DESC
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


  public function addBascula($data=null)
  {
    if (is_null($data))
    {
      $idb = isset($_POST['pidb']) ? $_POST['pidb'] : '';

      if ($_POST['paccion'] == 'n')
      {
        $_POST['pfolio'] = $this->getSiguienteFolio();

        $data = array(
          'id_empresa'   => $this->input->post('pid_empresa'),
          'id_area'      => $this->input->post('parea'),
          'id_chofer'    => empty($_POST['pid_chofer']) ? null : $_POST['pid_chofer'],
          'id_camion'    => empty($_POST['pid_camion']) ? null : $_POST['pid_camion'],
          'folio'        => $this->input->post('pfolio'),
          'fecha_bruto'  => str_replace('T', ' ', $_POST['pfecha'].':'.date('s')),
          'kilos_bruto'  => $this->input->post('pkilos_brutos'),
          'accion'       => 'en',
          'tipo'         => $this->input->post('ptipo'),
        );

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

      if ($_POST['paccion'] === 'en' || $_POST['paccion'] === 'sa' || $_POST['paccion'] === 'f')
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

        $data2['fecha_tara'] = str_replace('T', ' ', $_POST['pfecha'].':'.date('s'));
        $data2['kilos_tara'] = $this->input->post('pkilos_tara');

        $data2['kilos_neto']  = $this->input->post('pkilos_neto');
        $data2['kilos_neto2'] = $this->input->post('ppesada');
        $data2['accion']      = 'sa';
        $data2['tipo']        = $this->input->post('ptipo');

        if (isset($_POST['pstatus'])) $data2['accion'] = 'f';
      }

      $cajas = null;
      if (isset($_POST['pcajas']))
      {
        $cajas = array();
        foreach ($_POST['pcajas'] as $key => $caja)
        {
          $cajas[] = array(
            'id_bascula' => $idb,
            'id_calidad' => $_POST['pcalidad'][$key],
            'cajas'      => $caja,
            'kilos'      => $_POST['pkilos'][$key],
            'promedio'   => $_POST['ppromedio'][$key],
            'precio'     => $_POST['pprecio'][$key],
            'importe'    => $_POST['pimporte'][$key],
          );
        }
      }

      $this->updateBascula($idb, $data2, $cajas);

      return array('passes'=>true, 'msg'=>'7');
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

    if ( ! is_null($cajas))
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
          ->get();

        if ($sql_res->num_rows() > 0)
          $data['cajas'] = $sql_res->result();
      }
    }

    return $data;
  }


  /**
   * Obtiene el folio siguiente.
   * @return int
   */
  public function getSiguienteFolio()
  {
    $lastFolio = $this->db->select('folio')
      ->from('bascula')
      ->order_by('id_bascula', 'DESC')
      ->limit(1)
      ->get();

    if ($lastFolio->num_rows() > 0)
      return intval($lastFolio->row()->folio) + 1;
    else
      return 1;
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


}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */