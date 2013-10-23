<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class unidades_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
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