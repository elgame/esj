<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class choferes_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getChoferes($paginados = true)
	{
		$sql = '';
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

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 't');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." status='".$this->input->get('fstatus')."'";

		$query = BDUtil::pagination("
				SELECT id_chofer, nombre, status
				FROM choferes
				".$sql."
				ORDER BY nombre ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'choferes'       => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['choferes'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un chofer a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addChofer($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'nombre'  => $this->input->post('fnombre'),
						);
		}

		$this->db->insert('choferes', $data);
		// $id_chofer = $this->db->insert_id('proveedores', 'id_chofer');

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un chofer
	 * @param  [type] $id_chofer [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateChofer($id_chofer, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'nombre'  => $this->input->post('fnombre'),
						);
		}

		$this->db->update('choferes', $data, array('id_chofer' => $id_chofer));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un chofer
	 * @param  boolean $id_chofer [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getChoferInfo($id_chofer=FALSE, $basic_info=FALSE)
	{
		$id_chofer = (isset($_GET['id']))? $_GET['id']: $id_chofer;

		$sql_res = $this->db->select("id_chofer, nombre, status" )
												->from("choferes")
												->where("id_chofer", $id_chofer)
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
	 * Obtiene el listado de camiones para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en las placas, modelo, marca
	 */
	public function getChoferesAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";

		$res = $this->db->query("
				SELECT id_chofer, nombre, status 
				FROM choferes
				WHERE status = 't' ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_chofer,
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