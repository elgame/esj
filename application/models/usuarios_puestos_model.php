<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class usuarios_puestos_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getPuestos($paginados = true)
	{
		$sql = '';
		$params = array('result_items_per_page' => '60', 'result_page' => 0);
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
			$sql = "WHERE ( lower(p.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 't');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." p.status='".$this->input->get('fstatus')."'";

		$query['query'] = "SELECT p.id_puesto, p.nombre, p.abreviatura, p.status
					FROM usuarios_puestos p
					".$sql."
					ORDER BY p.nombre ASC";
		$query['total_rows'] = 0;
		if($paginados)
			$query = BDUtil::pagination($query, $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'puestos'       => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['puestos'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un camion a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addPuesto($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
					'nombre'      => $this->input->post('fnombre'),
					'abreviatura' => $this->input->post('fabreviatura'),
				);
		}

		$this->db->insert('usuarios_puestos', $data);
		// $id_camion = $this->db->insert_id('proveedores', 'id_camion');

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_puesto [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updatePuesto($id_puesto, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
					'nombre'      => $this->input->post('fnombre'),
					'abreviatura' => $this->input->post('fabreviatura'),
				);
		}

		$this->db->update('usuarios_puestos', $data, array('id_puesto' => $id_puesto));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un camion
	 * @param  boolean $id_camion [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getPuestoInfo($id_puesto=FALSE, $basic_info=FALSE)
	{
		$id_puesto = (isset($_GET['id']))? $_GET['id']: $id_puesto;

		$sql_res = $this->db->select("id_puesto, nombre, abreviatura, status" )
												->from("usuarios_puestos")
												->where("id_puesto", $id_puesto)
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
	public function getPuestosAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";

		$res = $this->db->query("
				SELECT id_puesto, nombre, abreviatura, status
				FROM camiones
				WHERE status = 't' ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_puesto,
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