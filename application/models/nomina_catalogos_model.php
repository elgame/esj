<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_catalogos_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
  }

  /**
   * Obtiene un catalogo especificando el tipo.
   * pe: percepciones,
   * de: deducciones,
   * ba: banco,
   *
   * @param  string $tipo
   * @return Illuminate\Support\Collection
   */
  public function tipo($tipo = 'pe', $value='')
  {
    $sql_res = $this->db->select("*" )->from("nomina_catalogos")
                        ->where("status", 't')->where("tipo", $tipo)
                        ->order_by('clave', 'ASC')->get();
    // foreach ($res as $key => $item) {
    //   $res[$key]['selected'] = $item['clave']==$value? 'selected': '';
    // }
    return $sql_res->result();
  }

  /**
   * Buscan un banco del catalogo.
   *
   * @return Illuminate\Support\Collection
   */
  public function findByClave($clave, $tipo = 'ba')
  {
    $sql_res = $this->db->select("*" )->from("nomina_catalogos")
                        ->where("status", 't')->where("tipo", $tipo)->where("clave", ''.$clave)
                        ->order_by('clave', 'ASC')->get();
    return $sql_res->row();
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */