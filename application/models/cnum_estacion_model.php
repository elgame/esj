<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cnum_estacion_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
  }


  public function get($term = '', $limit = 20)
  {
    $sql = " (lower(clave_identificacion) LIKE '".pg_escape_string(mb_strtolower($term, 'UTF-8'))."%' OR
        lower(descripcion) LIKE '%".pg_escape_string(mb_strtolower($term, 'UTF-8'))."%')";

    $res = $this->db->query(" SELECT id, clave_identificacion, descripcion, clave_transporte
        FROM c_estaciones
        WHERE {$sql}
        ORDER BY clave_identificacion ASC
        LIMIT {$limit}");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id'    => $itm->id,
            'label' => "{$itm->clave_identificacion} - {$itm->descripcion}",
            'value' => "{$itm->clave_identificacion} - {$itm->descripcion}",
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

}