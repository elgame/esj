<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class camiones_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getCamiones($paginados = true)
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
			$sql = "WHERE ( lower(p.placa) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.modelo) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.marca) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 't');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." p.status='".$this->input->get('fstatus')."'";

		$query = BDUtil::pagination("
				SELECT p.id_camion, p.placa, p.modelo, p.marca, p.status
				FROM camiones p
				".$sql."
				ORDER BY p.placa ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'camiones'       => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['camiones'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un camion a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addCamion($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'placa'  => $this->input->post('fplacas'),
						'modelo' => $this->input->post('fmodelo'),
            'marca'  => $this->input->post('fmarca'),
						'color'  => $this->input->post('fcolor'),
						);
		}

		$this->db->insert('camiones', $data);
		// $id_camion = $this->db->insert_id('proveedores', 'id_camion');

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_camion [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateCamion($id_camion, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'placa'  => $this->input->post('fplacas'),
						'modelo' => $this->input->post('fmodelo'),
						'marca'  => $this->input->post('fmarca'),
            'color'  => $this->input->post('fcolor'),
						);
		}

		$this->db->update('camiones', $data, array('id_camion' => $id_camion));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un camion
	 * @param  boolean $id_camion [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getCamionInfo($id_camion=FALSE, $basic_info=FALSE)
	{
		$id_camion = (isset($_GET['id']))? $_GET['id']: $id_camion;

		$sql_res = $this->db->select("id_camion, placa, modelo, marca, status, color" )
												->from("camiones")
												->where("id_camion", $id_camion)
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
	public function getCamionesAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(placa) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
								lower(modelo) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
								lower(marca) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";
    if ($this->input->get('alldata') !== false)
      $sql .= " AND Coalesce(placa, '') <> '' AND Coalesce(modelo, '') <> '' AND Coalesce(marca, '') <> '' AND Coalesce(color, '') <> ''";

		$res = $this->db->query("
				SELECT id_camion, placa, modelo, marca, status
				FROM camiones
				WHERE status = 't' {$sql}
				ORDER BY placa ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_camion,
						'label' => $itm->placa,
						'value' => $itm->placa,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */