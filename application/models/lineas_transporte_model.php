<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class lineas_transporte_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getLineas($paginados = true)
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
			$sql = "WHERE ( lower(p.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.id) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 't');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." p.status='".$this->input->get('fstatus')."'";

		$query = BDUtil::pagination("
				SELECT p.id_linea, p.nombre, p.telefonos, p.id, p.status
				FROM lineas_transporte p
				".$sql."
				ORDER BY p.nombre ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'lineas'       => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['lineas'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un camion a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addLinea($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'nombre'    => $this->input->post('fnombre'),
						'telefonos' => $this->input->post('ftelefonos'),
						'id'        => $this->input->post('fid'),
						);
		}

		$this->db->insert('lineas_transporte', $data);
		// $id_linea = $this->db->insert_id('lineas_transporte', 'id_linea');

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_linea [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateLinea($id_linea, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'nombre'    => $this->input->post('fnombre'),
						'telefonos' => $this->input->post('ftelefonos'),
						'id'        => $this->input->post('fid'),
						);
		}

		$this->db->update('lineas_transporte', $data, array('id_linea' => $id_linea));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un camion
	 * @param  boolean $id_linea [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getLineaInfo($id_linea=FALSE, $basic_info=FALSE)
	{
		$id_linea = (isset($_GET['id']))? $_GET['id']: $id_linea;

		$sql_res = $this->db->select("id_linea, nombre, telefonos, id, status" )
												->from("lineas_transporte")
												->where("id_linea", $id_linea)
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
	public function getLineasAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";

		$res = $this->db->query("
				SELECT id_linea, nombre, telefonos, id, status
				FROM lineas_transporte
				WHERE status = 't' ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_linea,
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