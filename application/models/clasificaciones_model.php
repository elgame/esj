<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class clasificaciones_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getClasificaciones($id_area=null, $paginados = true)
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

		$query = BDUtil::pagination("
				SELECT id_clasificacion, id_area, nombre, precio_venta, cuenta_cpi, status
				FROM clasificaciones
				".$sql."
				ORDER BY nombre ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'clasificaciones'=> array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['clasificaciones'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un area mas calidades y clasificaciones a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addClasificacion($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'id_area'      => $this->input->post('farea'),
						'nombre'       => $this->input->post('fnombre'),
						'precio_venta' => $this->input->post('fprecio_venta'),
						'cuenta_cpi'   => $this->input->post('fcuenta_cpi'),
						);
		}

		$this->db->insert('clasificaciones', $data);
		$id_clasificacion = $this->db->insert_id('clasificaciones', 'id_clasificacion');

		return array('error' => FALSE, $id_clasificacion);
	}

	/**
	 * Modificar la informacion de una clasificacion
	 * @param  [type] $id_clasificacion [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateClasificacion($id_clasificacion, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'nombre'       => $this->input->post('fnombre'),
						'precio_venta' => $this->input->post('fprecio_venta'),
						'cuenta_cpi'   => $this->input->post('fcuenta_cpi'),
						'id_area'      => $this->input->post('farea'),
						);
		}

		$this->db->update('clasificaciones', $data, array('id_clasificacion' => $id_clasificacion));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un clasificacion
	 * @param  boolean $id_clasificacion [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getClasificacionInfo($id_clasificacion=FALSE, $basic_info=FALSE)
	{
		$id_clasificacion = (isset($_GET['id']))? $_GET['id']: $id_clasificacion;

		$sql_res = $this->db->select("id_clasificacion, id_area, nombre, precio_venta, cuenta_cpi, status" )
												->from("clasificaciones")
												->where("id_clasificacion", $id_clasificacion)
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
	 * Obtiene el listado de proveedores para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
	 */
	public function getProveedoresAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";
		if($this->input->get('type') !== false)
			$sql .= " AND tipo_proveedor = '".mb_strtolower($this->input->get('type'), 'UTF-8')."'";
		$res = $this->db->query("
				SELECT id_proveedor, nombre_fiscal, rfc, calle, no_exterior, no_interior, colonia, municipio, estado, cp, telefono 
				FROM proveedores
				WHERE status = 'ac' ".$sql."
				ORDER BY nombre_fiscal ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_proveedor,
						'label' => $itm->nombre_fiscal,
						'value' => $itm->nombre_fiscal,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */