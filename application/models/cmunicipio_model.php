<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cmunicipio_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
  }


  public function getMunicipios($params, $limit = 20)
  {
    $sql = '';
    if (isset($params['term']))
      $sql .= " AND translate(lower(nombre),'áéíóúäëïöü','aeiouaeiou') LIKE '%".pg_escape_string(mb_strtolower($params['term'], 'UTF-8'))."%'";
    if (isset($params['c_estado'])) {
      $sql .= " AND c_estado = '{$params['c_estado']}'";
    }
    $res = $this->db->query(" SELECT id, c_estado, c_municipio, nombre
        FROM otros.c_municipios
        WHERE 1 = 1 {$sql}
        ORDER BY nombre ASC
        LIMIT {$limit}");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id'    => $itm->c_municipio,
            'label' => $itm->nombre,
            'value' => $itm->nombre,
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

}