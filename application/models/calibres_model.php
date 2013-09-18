<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class calibres_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  public function addCalibre($nombre)
  {
    $existe = $this->db->query(
      "SELECT COUNT(id_calibre) as total
       FROM calibres
       WHERE nombre = '{$nombre}'")
      ->result();

    if (floatval($existe[0]->total) > 0)
    {
      $existe = true;
      $id = null;
    }
    else
    {
      $this->db->insert('calibres', array('nombre' => $nombre));
      $id = $this->db->insert_id();

      $existe = false;
    }

    return array('existe' => $existe, 'id' => $id, 'nombre' => $nombre);
  }

  public function getCalibresAjax(){
    $sql = '';
    $res = $this->db->query("
        SELECT id_calibre, nombre
        FROM calibres
        WHERE lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'
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