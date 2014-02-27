<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getBancos($paginados = true)
	{
		$sql = '';
		$query['total_rows'] = $params['result_items_per_page'] = $params['result_page'] = '';
		//paginacion
		if($paginados)
		{
			$this->load->library('pagination');
			$params = array(
					'result_items_per_page' => '60',
					'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
			);
			if($params['result_page'] % $params['result_items_per_page'] == 0)
				$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
		}
		//Filtros para buscar
		if($this->input->get('fnombre') != '')
			$sql = "WHERE ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 'ac');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? ' WHERE ': ' AND ')." status = '".$this->input->get('fstatus')."'";
 		$query['query'] = 
 						"SELECT id_banco, nombre, status
						FROM banco_bancos 
						{$sql}
						ORDER BY nombre ASC";
		if($paginados)
			$query = BDUtil::pagination($query['query'], $params, true);

		$res = $this->db->query($query['query']);

		$response = array(
				'bancos'        => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0)
		{
			$response['bancos'] = $res->result();
		}

		return $response;
	}

	/**
	 * Obtiene la informacion de un banco
	 * @param  boolean $id_banco [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getBancoInfo($id_banco=FALSE, $basic_info=FALSE)
	{
		$id_banco = (isset($_GET['id']))? $_GET['id']: $id_banco;

		$sql_res = $this->db->select("id_banco, nombre, status" )
												->from("banco_bancos")
												->where("id_banco", $id_banco)
												->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();
		$sql_res->free_result();

		if ($basic_info == False) {

		}

		return $data;
	}

	/**
	 * Obtiene el listado de bancos para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en las placas, modelo, marca
	 */
	public function getBancosAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";

		$res = $this->db->query(
				"SELECT id_banco, nombre, status
				FROM banco_bancos 
				WHERE status = 'ac' ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_banco,
						'label' => $itm->nombre,
						'value' => $itm->nombre,
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