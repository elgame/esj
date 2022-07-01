<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cfraccionarancelaria_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
  }


  public function getFraccionArancelaria($term, $limit = 20)
  {
    $sql = '';
    if (isset($term))
      $sql .= " AND (lower(numero) LIKE '".pg_escape_string(mb_strtolower($term, 'UTF-8'))."%' OR
        lower(descripcion) LIKE '%".pg_escape_string(mb_strtolower($term, 'UTF-8'))."%')";
    $res = $this->db->query(" SELECT id, numero, descripcion, unidad
        FROM otros.c_fraccion_arancelaria
        WHERE status = 't' {$sql}
        ORDER BY numero ASC
        LIMIT {$limit}");

    $response = array();
    if($res->num_rows() > 0) {
      foreach($res->result() as $itm) {
        $response[] = array(
            'id'    => $itm->numero,
            'label' => "{$itm->numero} - {$itm->descripcion}",
            'value' => "{$itm->descripcion}",
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

}