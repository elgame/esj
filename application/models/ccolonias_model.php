<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ccolonias_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
  }


  public function getColonias($params, $limit = 20)
  {
    $sql = '';
    if (isset($params['term']))
      $sql .= " AND (translate(lower(nombre),'áéíóúäëïöü','aeiouaeiou') LIKE '%".pg_escape_string(mb_strtolower($params['term'], 'UTF-8'))."%' OR
                    translate(lower(c_colonia),'áéíóúäëïöü','aeiouaeiou') LIKE '%".pg_escape_string(mb_strtolower($params['term'], 'UTF-8'))."%')";
    if (isset($params['c_cp'])) {
      $sql .= " AND c_cp = '{$params['c_cp']}'";
    }
    $res = $this->db->query(" SELECT id, c_cp, c_colonia, nombre
        FROM otros.c_colonias
        WHERE 1 = 1 {$sql}
        ORDER BY nombre ASC
        LIMIT {$limit}");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id'    => $itm->c_colonia,
            'label' => $itm->nombre,
            'value' => $itm->nombre,
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

  public static function getColoniaKey($key, $c_cp)
  {
    $obj = get_instance();
    $res = $obj->db->query(" SELECT id, c_cp, c_colonia, nombre
        FROM otros.c_colonias
        WHERE c_colonia = '{$key}' AND c_cp = '{$c_cp}'
        LIMIT 1");

    $response = null;
    if($res->num_rows() > 0){
      $response = $res->row()->nombre;
    }

    return $response;
  }

}