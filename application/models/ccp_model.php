<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ccp_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
  }


  public function getCPs($params, $limit = 20)
  {
    $sql = '';
    if (isset($params['term']))
      $sql .= " AND lower(c_cp) LIKE '%".pg_escape_string(mb_strtolower($params['term'], 'UTF-8'))."%'";
    if (isset($params['c_estado']{0})) {
      $sql .= " AND c_estado = '{$params['c_estado']}'";
    }
    if (isset($params['c_municipio']{0})) {
      $sql .= " AND c_municipio = '{$params['c_municipio']}'";
    }
    if (isset($params['c_localidad']{0})) {
      $sql .= " AND c_localidad = '{$params['c_localidad']}'";
    }
    $res = $this->db->query(" SELECT id, c_estado, c_municipio, c_localidad, c_cp
        FROM otros.c_cps
        WHERE 1 = 1 {$sql}
        ORDER BY c_cp ASC
        LIMIT {$limit}");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id'    => $itm->c_cp,
            'label' => $itm->c_cp,
            'value' => $itm->c_cp,
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

}