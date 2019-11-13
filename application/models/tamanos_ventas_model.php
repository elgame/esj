<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class tamanos_ventas_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getTamanios($id_area=null, $paginados = true)
	{
		$sql = '';
		//paginacion
		if($paginados)
		{
			$this->load->library('pagination');
			$params = array(
					'result_items_per_page' => '50',
					'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
			);
			if($params['result_page'] % $params['result_items_per_page'] == 0)
				$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
		}
		//Filtros para buscar
		if($this->input->get('fnombre') != '')
			$sql = "WHERE ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = $this->input->get('fstatus')!==false? $this->input->get('fstatus'): 't';
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." status='".$this->input->get('fstatus')."'";

		if($id_area!=null)
			$sql .= ($sql==''? 'WHERE': ' AND')." id_area = '".$id_area."'";

		$str_query = "
				SELECT id_tamanio, id_area, nombre, status
				FROM otros.areas_tamanios
				".$sql."
				ORDER BY nombre ASC
				";
		if($paginados){
			$query = BDUtil::pagination($str_query, $params, true);
			$res = $this->db->query($query['query']);
		}else
			$res = $this->db->query($str_query);

		$response = array(
				'tamanios'      => array(),
				'total_rows'     => (isset($query['total_rows'])? $query['total_rows']: ''),
				'items_per_page' => (isset($params['result_items_per_page'])? $params['result_items_per_page']: ''),
				'result_page'    => (isset($params['result_page'])? $params['result_page']: '')
		);
		if($res->num_rows() > 0){
			$response['tamanios'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un area mas calidades y clasificaciones a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addTamanio($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'id_area'       => $this->input->post('farea'),
						'nombre'        => $this->input->post('fnombre'),
						);
		}

		$this->db->insert('otros.areas_tamanios', $data);
		$id_tamanio = $this->db->insert_id('otros.areas_tamanios', 'id_tamanio');

		return array('error' => FALSE, $id_tamanio);
	}

	/**
	 * Modificar la informacion de una calidad
	 * @param  [type] $id_tamanio [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateTamanio($id_tamanio, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
					'nombre'        => $this->input->post('fnombre'),
					'id_area'       => $this->input->post('farea'),
					);
		}

		$this->db->update('otros.areas_tamanios', $data, array('id_tamanio' => $id_tamanio));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_tamanio [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getTamanioInfo($id_tamanio=FALSE, $basic_info=FALSE)
	{
		$id_tamanio = (isset($_GET['id']))? $_GET['id']: $id_tamanio;

		$sql_res = $this->db->select("id_tamanio, id_area, nombre, status" )
												->from("otros.areas_tamanios")
												->where("id_tamanio", $id_tamanio)
												->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();
		$sql_res->free_result();

		if ($basic_info == False) {

		}

		return $data;
	}

	public function get_tamanios(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";
		if($this->input->get('type') !== false)
			$sql .= " AND id_area = {$this->input->get('type')}";
		$res = $this->db->query(" SELECT id_tamanio, id_area, nombre, status
				FROM otros.areas_tamanios
				WHERE status = 't' {$sql}
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_tamanio,
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