<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class almacenes_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getAlmacenes($paginados = true)
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

    $query['query'] = "
        SELECT id_almacen, nombre, status
        FROM compras_almacenes
        ".$sql."
        ORDER BY nombre ASC
        ";
    $query['total_rows'] = 0;
    if ($paginados)
		  $query = BDUtil::pagination($query['query'], $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'almacenes'      => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => (isset($params['result_items_per_page'])? $params['result_items_per_page']: 0),
				'result_page'    => (isset($params['result_page'])? $params['result_page']: 0)
		);
		if($res->num_rows() > 0){
			$response['almacenes'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un camion a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addAlmacen($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
        'nombre'  => $this->input->post('nombre')
			);
		}

		$this->db->insert('compras_almacenes', $data);
		// $id_vehiculo = $this->db->insert_id('compras_vehiculos', 'id_vehiculo');

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_almacen [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateAlmacen($id_almacen, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
			 'nombre' => $this->input->post('nombre')
			);
		}

		$this->db->update('compras_almacenes', $data, array('id_almacen' => $id_almacen));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un camion
	 * @param  boolean $id_camion [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getAlmacenInfo($id_almacen=FALSE, $basic_info=FALSE)
	{
		$id_almacen = ($id_almacen!==FALSE)? $id_almacen: $_GET['id'];

		$sql_res = $this->db->select("id_almacen, nombre, status" )
												->from("compras_almacenes")
												->where("id_almacen", $id_almacen)
												->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();
		$sql_res->free_result();

		if ($basic_info == False) {

		}

		return $data;
	}


}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */