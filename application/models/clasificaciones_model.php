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

		$str_query = "
				SELECT id_clasificacion, id_area, nombre, precio_venta, cuenta_cpi, status
				FROM clasificaciones
				".$sql."
				ORDER BY nombre ASC
				";
		if($paginados){
			$query = BDUtil::pagination($str_query, $params, true);
			$res = $this->db->query($query['query']);
		}else
			$res = $this->db->query($str_query);

		$response = array(
				'clasificaciones'=> array(),
				'total_rows'     => (isset($query['total_rows'])? $query['total_rows']: ''),
				'items_per_page' => (isset($params['result_items_per_page'])? $params['result_items_per_page']: ''),
				'result_page'    => (isset($params['result_page'])? $params['result_page']: '')
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
						// 'precio_venta' => $this->input->post('fprecio_venta'),
						'cuenta_cpi'   => $this->input->post('fcuenta_cpi'),
						);
		}

		$this->db->insert('clasificaciones', $data);

		$id_clasificacion = $this->db->insert_id('clasificaciones', 'id_clasificacion');

    // if (isset($_POST['fcalibres']))
    // {
    //   $calibres = array();

    //   foreach ($_POST['fcalibres'] as $idCalibre)
    //   {
    //     $calibres[] = array('id_clasificacion' => $id_clasificacion, 'id_calibre' => $idCalibre);
    //   }

    //   $this->db->insert_batch('clasificaciones_calibres', $calibres);
    // }

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

      // $this->db->delete('clasificaciones_calibres', array('id_clasificacion' => $id_clasificacion));

      // if (isset($_POST['fcalibres']))
      // {
      //   $calibres = array();

      //   foreach ($_POST['fcalibres'] as $idCalibre)
      //   {
      //     $calibres[] = array('id_clasificacion' => $id_clasificacion, 'id_calibre' => $idCalibre);
      //   }

      //   $this->db->insert_batch('clasificaciones_calibres', $calibres);
      // }
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

		if ($basic_info == false)
    {
      $sql_res = $this->db->query(
        "SELECT cal.id_calibre, cal.nombre
         FROM calibres as cal
         INNER JOIN clasificaciones_calibres as clas_cal ON clas_cal.id_calibre = cal.id_calibre
         WHERE clas_cal.id_clasificacion = {$id_clasificacion}");

      $data['calibres'] = array();

      if ($sql_res->num_rows() > 0)
      {
        $data['calibres'] = $sql_res->result();
      }
		}

		return $data;
	}

	/**
	 * Obtiene el listado de clasificaciones para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. clasificaciones de una area
	 */
	public function ajaxClasificaciones(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";
		if($this->input->get('type') !== false)
			$sql .= " AND id_area = {$this->input->get('type')}";
		$res = $this->db->query(" SELECT id_clasificacion, id_area, nombre, status
				FROM clasificaciones
				WHERE status = true {$sql}
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_clasificacion,
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