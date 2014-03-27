<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cuentas_cpi_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Obtiene el listado de bancos para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en las placas, modelo, marca
	 */
	public function getCuentasAjax($did_empresa=null){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
                    lower(cuenta) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";
    $did_empresa = $did_empresa!=null? $did_empresa : $this->input->get('did_empresa') ;
    if(is_numeric($did_empresa))
      $sql .= " AND id_empresa = {$did_empresa}";

		$res = $this->db->query(
				"SELECT id_cuenta, id_padre, nivel, cuenta, nombre, tipo, id_empresa
				FROM cuentas_contpaq
				WHERE id_cuenta > 0 ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
					'id'    => $itm->cuenta,
					'label' => $itm->nombre.' - '.$itm->cuenta,
					'value' => $itm->nombre.' - '.$itm->cuenta,
					'item'  => $itm,
				);
			}
		}
		$res->free_result();

		return $response;
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */