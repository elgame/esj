<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bascula_pina_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }


  public function addSalidaPina($datos=null)
  {
    $data = [
      'id_bascula'   => $datos['id_bascula'],
      'id_rancho'    => $datos['ranchoId'],
      'kilos_neto'   => $datos['kilos_neto'],
      'total_piezas' => $datos['total_piezas'],
      'kg_pieza'     => $datos['kg_pieza'],
      'folio'        => $datos['folio'],
    ];

    if (isset($datos['id_salida_pina']) && $datos['id_salida_pina'] > 0) {
      $id = $datos['id_salida_pina'];
      $this->db->update('otros.bascula_salida_pina', $data, "id = {$id}");
    } else {
      $data['id_usuario'] = $this->session->userdata('id_usuario');
      $this->db->insert('otros.bascula_salida_pina', $data);
      $id = $this->db->insert_id('otros.bascula_salida_pina_id_seq');
    }

    $this->addEstibas($id, $datos);

    return array('passes' => true);
  }

  public function addEstibas($id, $datos)
  {
    $this->db->delete('otros.bascula_salida_pina_estibas', "id_salida_pina = {$id}");
    foreach ($datos['estiba'] as $key => $value) {
      $this->db->insert('otros.bascula_salida_pina_estibas', [
        'id_salida_pina'  => $id,
        'estiba'          => $value,
        'id_centro_costo' => $datos['id_centro_costo'][$key],
        'id_calidad'      => $datos['id_calidad'][$key],
        'cantidad'        => $datos['cantidad'][$key],
      ]);
    }
  }

  public function getInfo($id, $tipo = 'bsp.id', $basic_info=false)
  {
    $sql_res = $this->db->query("
      SELECT bsp.id, bsp.id_bascula, bsp.id_rancho, bsp.kilos_neto, bsp.total_piezas, bsp.kg_pieza,
        bsp.folio, bsp.id_usuario, bsp.fecha_registro, r.nombre AS rancho
      FROM otros.bascula_salida_pina bsp
        INNER JOIN otros.ranchos r ON r.id_rancho = bsp.id_rancho
      WHERE {$tipo} = {$id}");

    $data['info'] = array();
    $data['estibas'] = array();

    if ($sql_res->num_rows() > 0)
    {
      $data['info'] = $sql_res->row();
      $sql_res->free_result();

      if ($basic_info === false)
      {
        $sql_res = $this->db->query("
          SELECT bsp.id_salida_pina, bsp.estiba, bsp.id_centro_costo, bsp.id_calidad, bsp.cantidad,
            cc.nombre AS centro_costo, c.nombre AS calidad
          FROM otros.bascula_salida_pina_estibas bsp
            INNER JOIN otros.centro_costo cc ON cc.id_centro_costo = bsp.id_centro_costo
            INNER JOIN calidades c ON c.id_calidad = bsp.id_calidad
          WHERE bsp.id_salida_pina = {$data['info']->id}");
        $data['estibas'] = $sql_res->result();
        $sql_res->free_result();
      }
    }

    return $data;
  }

}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */