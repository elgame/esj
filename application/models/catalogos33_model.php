<?php
class catalogos33_model extends privilegios_model{

	function __construct(){
		parent::__construct();
	}

  public function claveProdServ($cod=false)
  {
    $sql = '';
    if ($cod) {
      $sql = "AND Lower(c_clave_prodserv) LIKE Lower('{$cod}')";
    } else {
      $term = $this->input->get('term');
      $sql = "AND (Lower(descripcion) LIKE Lower('{$term}%') OR Lower(c_clave_prodserv) LIKE Lower('{$term}%'))";
    }
    $res = $this->db->query("
        SELECT id, c_clave_prodserv, descripcion, iva, ieps
        FROM otros.c_clave_prodservs
        WHERE deleted_at IS NULL {$sql}
        ORDER BY descripcion ASC
        LIMIT 30");

    $response = array();
    if($res->num_rows() > 0){
      if ($cod) {
        $response = $res->row();
        $response->label = "{$response->descripcion} ({$response->c_clave_prodserv})";
      } else {
        foreach($res->result() as $itm){
          $response[] = array(
              'id'    => $itm->c_clave_prodserv,
              'label' => "{$itm->descripcion} ({$itm->c_clave_prodserv})",
              'value' => "{$itm->descripcion} ({$itm->c_clave_prodserv})",
              'item'  => $itm,
          );
        }
      }
    }

    return $response;
  }

  public function claveUnidad($cod=false)
  {
    $sql = '';
    if ($cod) {
      $sql = "AND Lower(c_clave_unidad) LIKE Lower('{$cod}')";
    } else {
      $term = $this->input->get('term');
      $sql = "AND (Lower(descripcion) LIKE Lower('{$term}%') OR Lower(c_clave_unidad) LIKE Lower('{$term}%'))";
    }
    $res = $this->db->query("
        SELECT id, c_clave_unidad, descripcion
        FROM otros.c_clave_unidades
        WHERE deleted_at IS NULL {$sql}
        ORDER BY descripcion ASC
        LIMIT 30");

    $response = array();
    if($res->num_rows() > 0){
      if ($cod) {
        $response = $res->row();
        $response->label = "{$response->descripcion} ({$response->c_clave_unidad})";
      } else {
        foreach($res->result() as $itm){
          $response[] = array(
              'id'    => $itm->c_clave_unidad,
              'label' => "{$itm->descripcion} ({$itm->c_clave_unidad})",
              'value' => "{$itm->descripcion} ({$itm->c_clave_unidad})",
              'item'  => $itm,
          );
        }
      }
    }

    return $response;
  }

  public function regimenFiscales($cod=false)
  {
    $sql = '';
    if ($cod) {
      $sql = "WHERE Lower(\"c_RegimenFiscal\") LIKE Lower('{$cod}')";
    } else {
      // $term = $this->input->get('term');
      // $sql = "AND (Lower(nombre) LIKE Lower('{$term}%') OR Lower(c_RegimenFiscal) LIKE Lower('{$term}%'))";
    }
    $res = $this->db->query("
        SELECT id, \"c_RegimenFiscal\", nombre, tipo_persona, (nombre || ' (' || \"c_RegimenFiscal\" || ')') AS label
        FROM otros.c_regimen_fiscal
        {$sql}
        ORDER BY nombre ASC");

    $response = array();
    if($res->num_rows() > 0){
      if ($cod) {
        $response = $res->row();
      } else {
        $response = $res->result();
      }
    }

    return $response;
  }

}