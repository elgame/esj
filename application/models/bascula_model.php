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
    // if($this->input->get('fnombre') != '')
    //   $sql = "WHERE ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

    // $_GET['fstatus'] = $this->input->get('fstatus')!==false? $this->input->get('fstatus'): 't';
    // if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
    //   $sql .= ($sql==''? 'WHERE': ' AND')." b.status='".$this->input->get('fstatus')."'";

    // if($this->input->get('ftipo') != '' && $this->input->get('ftipo') != 'todos')
    //   $sql .= ($sql==''? 'WHERE': ' AND')." tipo='".$this->input->get('ftipo')."'";

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
                b.fecha_bruto AS fecha
        FROM bascula AS b
        INNER JOIN empresas AS e ON e.id_empresa = b.id_empresa
        INNER JOIN areas AS a ON a.id_area = b.id_area
        INNER JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor
        INNER JOIN choferes AS ch ON ch.id_chofer = b.id_chofer
        INNER JOIN camiones AS ca ON ca.id_camion = b.id_camion
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
        $data = array(
          'id_empresa'   => $this->input->post('pid_empresa'),
          'id_area'      => $this->input->post('parea'),
          'id_proveedor' => $this->input->post('pid_proveedor'),
          'id_chofer'    => $this->input->post('pid_chofer'),
          'id_camion'    => $this->input->post('pid_camion'),
          'folio'        => $this->input->post('pfolio'),
          'fecha_bruto'  => str_replace('T', ' ', $_POST['pfecha']),
          'kilos_bruto'  => $this->input->post('pkilos_brutos'),
          'accion'       => 'en',
          'tipo'         => $this->input->post('ptipo'),
        );

        $this->db->insert('bascula', $data);
        $idb = $this->db->insert_id();
      }

      $data2 = array(
        'importe'       => empty($_POST['ptotal']) ? 0 : $_POST['ptotal'], // checar
        'total_cajas'   => empty($_POST['ptotal_cajas']) ? 0 : $_POST['ptotal_cajas'],
        'obcervaciones' => $this->input->post('pobcervaciones'),
      );

      if ($_POST['paccion'] === 'en' || $_POST['paccion'] === 'sa')
      {
        $data2['id_empresa']   = $this->input->post('pid_empresa');
        $data2['id_area']      = $this->input->post('parea');
        $data2['id_proveedor'] = $this->input->post('pid_proveedor');
        $data2['id_chofer']    = $this->input->post('pid_chofer');
        $data2['id_camion']    = $this->input->post('pid_camion');

        $data2['fecha_tara']  = str_replace('T', ' ', $_POST['pfecha']);
        $data2['kilos_tara']  = $this->input->post('pkilos_tara');
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
      ->select("*" )
      ->from("bascula")
      ->where("id_bascula", $id)
      ->or_where('folio', $folio)
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

}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */