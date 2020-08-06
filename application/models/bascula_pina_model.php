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
      'kilos_neto'   => $datos['kilos_neto'],
      'total_piezas' => $datos['total_piezas'],
      'kg_pieza'     => $datos['kg_pieza'],
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
    $this->db->delete('otros.bascula_salida_pina_estibas_centro_costo', "id_salida_pina = {$id}");
    foreach ($datos['estiba'] as $key => $value) {
      $this->db->insert('otros.bascula_salida_pina_estibas', [
        'id_salida_pina' => $id,
        'estiba'         => $value,
        'folio'          => $datos['folio'][$key],
        'id_rancho'      => $datos['ranchoId'][$key],
        'id_calidad'     => $datos['id_calidad'][$key],
        'cantidad'       => $datos['cantidad'][$key],
      ]);

      $centros_costos = explode(',', $datos['id_centro_costo'][$key]);
      foreach ($centros_costos as $keyc => $centro) {
        $this->db->insert('otros.bascula_salida_pina_estibas_centro_costo', [
          'id_salida_pina'  => $id,
          'estiba'          => $value,
          'folio'           => $datos['folio'][$key],
          'id_centro_costo' => $centro,
          'num'             => count($centros_costos),
        ]);
      }
    }

  }

  public function getInfo($id, $tipo = 'bsp.id', $basic_info=false)
  {
    $sql_res = $this->db->query("
      SELECT bsp.id, bsp.id_bascula, bsp.kilos_neto, bsp.total_piezas, bsp.kg_pieza,
        bsp.id_usuario, bsp.fecha_registro
      FROM otros.bascula_salida_pina bsp
      WHERE {$tipo} = {$id}
      LIMIT 1");

    $data['info'] = array();
    $data['estibas'] = array();

    if ($sql_res->num_rows() > 0)
    {
      $data['info'] = $sql_res->row();
      $sql_res->free_result();

      if ($basic_info === false)
      {
        $sql_res = $this->db->query("
          SELECT bsp.id_salida_pina, bsp.estiba, cc.id_centro_costo, bsp.id_calidad, bsp.cantidad,
            cc.centro_costo, c.nombre AS calidad, bsp.id_rancho, r.nombre AS rancho, bsp.folio
          FROM otros.bascula_salida_pina_estibas bsp
            INNER JOIN calidades c ON c.id_calidad = bsp.id_calidad
            INNER JOIN (
              SELECT bspec.id_salida_pina, bspec.estiba, bspec.folio, string_agg(cc.nombre, ', ') AS centro_costo,
                string_agg(cc.id_centro_costo::text, ',') AS id_centro_costo
              FROM otros.bascula_salida_pina_estibas_centro_costo bspec
                INNER JOIN otros.centro_costo cc ON cc.id_centro_costo = bspec.id_centro_costo
              GROUP BY bspec.id_salida_pina, bspec.estiba, bspec.folio
            ) AS cc ON (cc.id_salida_pina = bsp.id_salida_pina AND cc.estiba = bsp.estiba AND cc.folio = bsp.folio)
            INNER JOIN otros.ranchos r ON r.id_rancho = bsp.id_rancho
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