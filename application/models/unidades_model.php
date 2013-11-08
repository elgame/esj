<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class unidades_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function info($idUnidad, $full = false)
  {
    $query = $this->db->query(
      "SELECT *
        FROM unidades
        WHERE id_unidad = {$idUnidad}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();

      $query->free_result();
      if ($full)
      {
        $query = $this->db->query(
          "SELECT up.id_unidad, up.id_producto, up.cantidad, pr.nombre
           FROM unidades_productos AS up
           INNER JOIN productos AS pr ON pr.id_producto = up.id_producto
           WHERE up.id_unidad = {$data['info'][0]->id_unidad}");

        $data['info'][0]->productos = array();
        if ($query->num_rows() > 0)
        {
          $data['info'][0]->productos = $query->result();
        }
      }

    }

    return $data;
  }

	/**
	 * Obtiene el listado de unidades para usar ajax
	 * @param term. termino escrito en la caja de texto
	 */
	public function ajaxUnidades(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";

		$res = $this->db->query("
				SELECT id_unidad, nombre, status
				FROM unidades
				WHERE status = 't' ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_unidad,
						'label' => $itm->nombre,
						'value' => $itm->nombre,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */