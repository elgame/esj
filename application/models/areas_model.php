<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class areas_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getAreas($paginados = true, $sql2='')
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

		$_GET['fstatus'] = $this->input->get('fstatus')!==false? $this->input->get('fstatus'): 't';
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." status='".$this->input->get('fstatus')."'";

		if($this->input->get('ftipo') != '' && $this->input->get('ftipo') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." tipo='".$this->input->get('ftipo')."'";

		$str_query = "
				SELECT id_area, nombre, tipo, status, predeterminado
				FROM areas
				".$sql.$sql2."
				ORDER BY nombre ASC
				";
		if($paginados){
			$query = BDUtil::pagination($str_query, $params, true);
			$res = $this->db->query($query['query']);
		}else
			$res = $this->db->query($str_query);

		$response = array(
				'areas'    => array(),
				'total_rows'     => (isset($query['total_rows'])? $query['total_rows']: ''),
				'items_per_page' => (isset($params['result_items_per_page'])? $params['result_items_per_page']: ''),
				'result_page'    => (isset($params['result_page'])? $params['result_page']: '')
		);
		if($res->num_rows() > 0){
			$response['areas'] = $res->result();
		}

		return $response;
	}

	public function getAreaDefault(){
		$result = $this->db->query("SELECT id_area
		                           FROM areas
		                           WHERE status = 't' AND predeterminado = 't'");
		if($result->num_rows() > 0){
			$result = $result->row();
			return $result->id_area;
		}else
			return 2;
	}

 	/**
 	 * Agrega un area mas calidades y clasificaciones a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addArea($data=NULL)
	{
		if ($data==NULL)
		{
			$data = array(
						'nombre' => $this->input->post('fnombre'),
						'tipo'   => $this->input->post('ftipo'),
						);
		}

		$this->db->insert('areas', $data);
		$id_area = $this->db->insert_id('areas', 'id_area');

		//se agregan las calidades
		if ($this->input->post('cal_nombre') !== false) {
			$this->load->model('calidades_model');

			foreach ($this->input->post('cal_nombre') as $key => $value) {
				$this->calidades_model->addCalidad(array(
					'id_area'       => $id_area,
					'nombre'        => $_POST['cal_nombre'][$key],
					'precio_compra' => $_POST['cal_precio'][$key],
					));
			}
		}

		//se agregan las clasificaciones
		if ($this->input->post('cla_nombre') !== false) {
			$this->load->model('clasificaciones_model');

			foreach ($this->input->post('cla_nombre') as $key => $value) {
				$this->clasificaciones_model->addClasificacion(array(
					'id_area'      => $id_area,
					'nombre'       => $_POST['cla_nombre'][$key],
					'precio_venta' => $_POST['cla_precio'][$key],
					'cuenta_cpi'   => $_POST['cla_cuenta'][$key],
					));
			}
		}

    //se agregan las empresas
    if (is_array($this->input->post('fempresas'))) {
      foreach ($this->input->post('fempresas') as $key => $value) {
        $this->db->insert('areas_empresas', [
          'id_area'    => $id_area,
          'id_empresa' => $value
        ]);
      }
    }

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de una area
	 * @param  [type] $id_area [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateArea($id_area, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'nombre' => $this->input->post('fnombre'),
						'tipo'   => $this->input->post('ftipo'),
						);
		}

		$this->db->update('areas', $data, array('id_area' => $id_area));

    //se agregan las empresas
    if (is_array($this->input->post('fempresas'))) {
      $this->db->delete('areas_empresas', "id_area = {$id_area}");
      foreach ($this->input->post('fempresas') as $key => $value) {
        $this->db->insert('areas_empresas', [
          'id_area'    => $id_area,
          'id_empresa' => $value
        ]);
      }
    }

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de una area
	 * @param  boolean $id_area [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getAreaInfo($id_area=FALSE, $basic_info=FALSE)
	{
		// $id_area = (isset($_GET['id']))? $_GET['id']: $id_area;
    $id_area = $id_area? $id_area: (isset($_GET['id'])? $_GET['id']: 0);

		$sql_res = $this->db->select("id_area, nombre, tipo, status" )
												->from("areas")
												->where("id_area", $id_area)
												->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();
		$sql_res->free_result();

		if ($basic_info == false) {
      $data['calidades'] = array();

      $sql_res = $this->db->select("id_calidad, id_area, nombre, precio_compra, status")
        ->from("calidades")
        ->where("id_area", (isset($data['info']->id_area)? $data['info']->id_area: 0))
        ->get();

      if ($sql_res->num_rows() > 0)
        $data['calidades'] = $sql_res->result();

      $data['empresas'] = array();
      $sql_res = $this->db->select("id_empresa")
        ->from("areas_empresas")
        ->where("id_area", (isset($data['info']->id_area)? $data['info']->id_area: 0))
        ->get();

      if ($sql_res->num_rows() > 0)
        $data['empresas'] = $sql_res->result();
		}

		return $data;
	}

	/**
	 * Obtiene el listado de proveedores para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
	 */
	public function getAreasAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND lower(a.nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";

    $join = '';
    if ($this->input->get('did_empresa') !== false && $this->input->get('did_empresa') !== '') {
      $sql .= " AND ae.id_empresa = ".$this->input->get('did_empresa')."";
      $join = " INNER JOIN areas_empresas ae ON a.id_area = ae.id_area";
    }

		$res = $this->db->query("
				SELECT a.id_area, a.nombre, a.tipo, a.status
				FROM areas a {$join}
				WHERE a.status = 't' ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_area,
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