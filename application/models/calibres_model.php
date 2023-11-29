<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class calibres_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  public function getCalibres()
  {
    $sql = '';
    $res = $this->db->query("
        SELECT id_calibre, nombre
        FROM calibres
        ORDER BY nombre ASC");

    $response = array('calibres' => array());
    if($res->num_rows() > 0)
      $response['calibres'] = $res->result();

    return $response;
  }

  public function getCalibre($id)
  {
    $sql = '';
    $res = $this->db->query("
        SELECT id_calibre, nombre, tipo, order
        FROM calibres
        WHERE id_calibre = {$id}
        ORDER BY nombre ASC");

    $response = null;
    if($res->num_rows() > 0){
      $response = $res->row();
    }

    return $response;
  }

  public function addCalibre($nombre)
  {
    $nombre = str_replace(' ', '', $nombre);
    $existe = $this->db->query(
      "SELECT id_calibre
       FROM calibres
       WHERE nombre = '{$nombre}'")
      ->result();

    if (count($existe) > 0)
    {
      $id = $existe[0]->id_calibre;
      $existe = true;
    }
    else
    {
      $this->db->insert('calibres', array('nombre' => $nombre));
      $id = $this->db->insert_id('calibres_id_calibre_seq');

      $existe = false;
    }

    return array('existe' => $existe, 'id' => $id, 'nombre' => $nombre);
  }

  public function getCalibresAjax()
  {
    $sql = '';
    if ($this->input->get('tipo')) {
      $sql .= " AND tipo = '{$_GET['tipo']}'";
    }

    $res = $this->db->query("
        SELECT id_calibre, nombre
        FROM calibres
        WHERE status = 't' {$sql}
          AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'
        ORDER BY nombre ASC
        LIMIT 20");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id' => $itm->id_calibre,
            'label' => $itm->nombre,
            'value' => $itm->nombre,
            'item' => $itm,
        );
      }
    }

    return $response;
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */