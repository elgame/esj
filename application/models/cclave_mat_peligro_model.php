<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cclave_mat_peligro_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
  }


  public function get($term = '', $limit = 20)
  {
    $sql = " (lower(clave) LIKE '".pg_escape_string(mb_strtolower($term, 'UTF-8'))."%' OR
        lower(nombre) LIKE '%".pg_escape_string(mb_strtolower($term, 'UTF-8'))."%')";

    $res = $this->db->query(" SELECT id, clave, nombre, clase, peligro
        FROM c_materiales_peligrosos
        WHERE {$sql}
        ORDER BY clave ASC
        LIMIT {$limit}");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id'    => $itm->clave,
            'label' => "{$itm->clave} - {$itm->nombre}",
            'value' => "{$itm->clave} - {$itm->nombre}",
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

}