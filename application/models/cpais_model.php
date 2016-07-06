<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cpais_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
  }


  public function getPaises($term='', $limit = 20)
  {
    $sql = '';
    if ($term !== '')
      $sql = "translate(lower(nombre),'áéíóúäëïöü','aeiouaeiou') LIKE '%".pg_escape_string(mb_strtolower($term, 'UTF-8'))."%'";
    $res = $this->db->query(" SELECT id, c_pais, nombre
        FROM otros.c_paises
        WHERE {$sql}
        ORDER BY nombre ASC
        LIMIT {$limit}");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id'    => $itm->c_pais,
            'label' => $itm->nombre,
            'value' => $itm->nombre,
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

}