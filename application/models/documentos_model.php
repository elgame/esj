<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class documentos_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getDocumentos($orderby='nombre ASC')
	{
		$sql = '';
		$res = $this->db->query("SELECT 
					id_documento, nombre, url_form, url_print, status, orden
				FROM rastria_documentos
				WHERE status = true 
				ORDER BY {$orderby}
				");

		$response = array(
				'documentos'    => array(),
		);
		if($res->num_rows() > 0){
			$response['documentos'] = $res->result();
		}

		return $response;
	}


}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */