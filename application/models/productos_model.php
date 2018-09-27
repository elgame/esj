<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class productos_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getFamilias($paginados = true, $tipo=null)
	{
		$sql = '';
		//paginacion
		if($paginados)
		{
			$this->load->library('pagination');
			$params = array(
					'result_items_per_page' => '30',
					'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
			);
			if($params['result_page'] % $params['result_items_per_page'] == 0)
				$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
		}
		$this->load->model('empresas_model');
		$empresa = $this->empresas_model->getDefaultEmpresa();
		//Filtros para buscar
		$_GET['fempresa']    = (isset($_GET['fempresa']{0})? $_GET['fempresa'] : $empresa->nombre_fiscal);
		$_GET['fid_empresa'] = (isset($_GET['fid_empresa']{0})? $_GET['fid_empresa'] : $empresa->id_empresa);
		$sql .= ($sql==''? 'WHERE': ' AND ')." id_empresa = '".$this->input->get('fid_empresa')."'";

		$_GET['fstatus'] = (isset($_GET['fstatus'])? $_GET['fstatus']: 'ac');
		$sql .= ($sql==''? 'WHERE': ' AND ')." status = '".$this->input->get('fstatus')."'";

		if ($tipo!=null)
			$sql .= ($sql==''? 'WHERE': ' AND ')." tipo = '".$tipo."'";

		$str_query = "
				SELECT id_familia, id_empresa, codigo, nombre, tipo, status
				FROM productos_familias
				".$sql."
				ORDER BY nombre ASC
				";

		if($paginados){
			$query = BDUtil::pagination($str_query, $params, true);
			$res = $this->db->query($query['query']);
		}else
			$res = $this->db->query($str_query);

		$response = array(
				'familias'       => array(),
				'total_rows'     => (isset($query['total_rows'])? $query['total_rows']: ''),
				'items_per_page' => (isset($params['result_items_per_page'])? $params['result_items_per_page']: ''),
				'result_page'    => (isset($params['result_page'])? $params['result_page']: ''),
				'empresa'        => $empresa,
		);
		if($res->num_rows() > 0){
			$response['familias'] = $res->result();
			foreach ($response['familias'] as $key => $value)
			{
				switch ($response['familias'][$key]->tipo) {
					case 'p': $response['familias'][$key]->tipo_text = 'Producto'; break;
					case 'd': $response['familias'][$key]->tipo_text = 'Servicio'; break;
          case 'f': $response['familias'][$key]->tipo_text = 'Flete'; break;
					case 'a': $response['familias'][$key]->tipo_text = 'Activos'; break;
				}
			}
		}

		return $response;
	}

 	/**
 	 * Agrega una familia
 	 * @param [type] $data [description]
 	 */
	public function addFamilia($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
				'id_empresa' => $this->input->post('fid_empresa'),
				'codigo'     => '',
				'nombre'     => $this->input->post('fnombre'),
				'tipo'       => $this->input->post('ftipo'),
        'almacen'    => $this->input->post('falmacen'),
				);
		}

		$this->db->insert('productos_familias', $data);

		$id_familia = $this->db->insert_id('productos_familias', 'id_familia');

		return array('error' => FALSE, $id_familia);
	}

	/**
	 * Modificar la informacion de una familia
	 * @param  [type] $id_familia [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateFamilia($id_familia, $data=NULL)
	{
		if ($data==NULL)
		{
			$data = array(
				'id_empresa' => $this->input->post('fid_empresa'),
				'codigo'     => '',
				'nombre'     => $this->input->post('fnombre'),
				'tipo'       => $this->input->post('ftipo'),
        'almacen'    => $this->input->post('falmacen'),
				);
		}

		$this->db->update('productos_familias', $data, array('id_familia' => $id_familia));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un familia
	 * @param  boolean $id_familia [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getFamiliaInfo($id_familia=FALSE, $basic_info=FALSE)
	{
		$id_familia = ($id_familia==FALSE)? $_GET['id']: $id_familia;

		$sql_res = $this->db->select("id_familia, id_empresa, codigo, nombre, tipo, status, almacen" )
												->from("productos_familias")
												->where("id_familia", $id_familia)
												->get();

		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
		{
			$data['info']	= $sql_res->row();
			switch ($data['info']->tipo) {
				case 'p': $data['info']->tipo_text = 'Producto'; break;
				case 'd': $data['info']->tipo_text = 'Servicio'; break;
				case 'f': $data['info']->tipo_text = 'Flete'; break;
			}
		}
    	$sql_res->free_result();

    	if (!$basic_info)
    	{
    		$this->load->model('empresas_model');
    		$data['empresa'] = $this->empresas_model->getInfoEmpresa($data['info']->id_empresa)['info'];
    	}

		return $data;
	}

	public function deleteFamilia($id_familia)
	{
		$this->db->update('productos_familias', array('status' => 'e'), "id_familia = {$id_familia}");
		$this->db->update('productos', array('status' => 'e'), "id_familia = {$id_familia}");
		return true;
	}



	/**
	 * ***************** PRODUCTOS *********************
	 * *************************************************
	 * @param  boolean $paginados [description]
	 * @return [type]             [description]
	 */
	public function getProductos($paginados = true)
	{
		$sql = '';
		//paginacion
		if($paginados)
		{
			$this->load->library('pagination');
			$params = array(
					'result_items_per_page' => '30',
					'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
			);
			if($params['result_page'] % $params['result_items_per_page'] == 0)
				$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
		}
		$familia = array();

		//Filtros para buscar
		if (isset($_GET['fid_familia']))
		{
			$sql .= ($sql==''? 'WHERE': ' AND ')." id_familia = '".$this->input->get('fid_familia')."'";
			$familia = $this->getFamiliaInfo($this->input->get('fid_familia'));
		}

		if (isset($_GET['fproducto']{0}))
			$sql .= ($sql==''? 'WHERE': ' AND ')." lower(nombre) LIKE '%".mb_strtolower($this->input->get('fproducto'), 'UTF-8')."%'";

		$_GET['fstatus'] = (isset($_GET['fstatus'])? $_GET['fstatus']: 'ac');
		$sql .= ($sql==''? 'WHERE': ' AND ')." status = '".$this->input->get('fstatus')."'";

		$str_query = "
				SELECT id_producto, id_empresa, id_familia, id_unidad, codigo, nombre, stock_min, ubicacion, precio_promedio, status, cuenta_cpi, tipo
				FROM productos
				".$sql."
				ORDER BY nombre ASC
				";
		if($paginados){
			$query = BDUtil::pagination($str_query, $params, true);
			$res = $this->db->query($query['query']);
		}else
			$res = $this->db->query($str_query);

		$response = array(
				'productos'       => array(),
				'total_rows'     => (isset($query['total_rows'])? $query['total_rows']: ''),
				'items_per_page' => (isset($params['result_items_per_page'])? $params['result_items_per_page']: ''),
				'result_page'    => (isset($params['result_page'])? $params['result_page']: ''),
				'familia'        => $familia,
		);
		if($res->num_rows() > 0){
			$response['productos'] = $res->result();
		}

		return $response;
	}

	/**
 	 * Agrega una familia
 	 * @param [type] $data [description]
 	 */
	public function addProducto($data=NULL)
	{

		if ($data==NULL)
		{
			$familia = $this->getFamiliaInfo($this->input->get('fid_familia'));
			$data = array(
        'id_empresa' => $familia['empresa']->id_empresa,
        'id_familia' => $familia['info']->id_familia,
        'id_unidad'  => $this->input->post('funidad'),
        'codigo'     => $this->input->post('fcodigo'),
        'nombre'     => $this->input->post('fnombre'),
        'stock_min'  => (is_numeric($this->input->post('fstock_min'))? $this->input->post('fstock_min'): 0),
        'ubicacion'  => $this->input->post('ubicacion'),
        'ieps'       => is_numeric($this->input->post('fieps')) ? $this->input->post('fieps') : 0,
        'cuenta_cpi' => $this->input->post('cuenta_contpaq'),
        'tipo'       => $this->input->post('ftipo'),
				);
		}

		$this->db->insert('productos', $data);
		$id_producto = $this->db->insert_id('productos', 'id_producto');

		$this->addPresentacion($id_producto);

		return array('error' => FALSE, $id_producto);
	}

	/**
 	 * Agrega una familia
 	 * @param [type] $data [description]
 	 */
	public function updateProducto($id_producto, $data=NULL)
	{
		if ($data==NULL)
		{
			$familia = $this->getFamiliaInfo($this->input->post('ffamilia')); // fid_familia
			$data = array(
        'id_empresa' => $familia['empresa']->id_empresa,
        'id_familia' => $familia['info']->id_familia,
        'id_unidad'  => $this->input->post('funidad'),
        'codigo'     => $this->input->post('fcodigo'),
        'nombre'     => $this->input->post('fnombre'),
        'stock_min'  => (is_numeric($this->input->post('fstock_min'))? $this->input->post('fstock_min'): 0),
        'ubicacion'  => $this->input->post('ubicacion'),
        'ieps'       => is_numeric($this->input->post('fieps')) ? $this->input->post('fieps') : 0,
        'cuenta_cpi' => $this->input->post('cuenta_contpaq'),
        'tipo'       => $this->input->post('ftipo'),
			);
		}

		$this->db->update('productos', $data, "id_producto = {$id_producto}");

		$this->addPresentacion($id_producto);

		return array('error' => FALSE, $id_producto);
	}

	/**
	 * Agrega o actualiza las presentaciones
	 * @param [type] $id_producto [description]
	 * @param [type] $data        [description]
	 */
	public function addPresentacion($id_producto, $data=NULL)
	{
		if ($data==NULL)
		{
			foreach ($this->input->post('pnombre') as $key => $value)
			{
				if ($value != '' && $_POST['pcantidad'][$key] != '')
				{
					if ($_POST['pidpresentacion'][$key] != '')
					{
					  $this->db->update('productos_presentaciones', array(
							'nombre'    => $value,
							'cantidad'  => (is_numeric($_POST['pcantidad'][$key])? $_POST['pcantidad'][$key]: 1),
							'status'    => (isset($_POST['pquitar'.$_POST['pidpresentacion'][$key]])? 'e': 'ac'),
						), "id_presentacion = ".$_POST['pidpresentacion'][$key]);
					}else
					{
						$this->db->insert('productos_presentaciones', array(
							'id_producto' => $id_producto,
							'nombre'      => $value,
							'cantidad'    => (is_numeric($_POST['pcantidad'][$key])? $_POST['pcantidad'][$key]: 1),
						));
					}
				}
			}
		}

		return true;
	}

	/**
	 * Obtiene la informacion de un producto
	 * @param  boolean $id_producto [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getProductoInfo($id_producto=FALSE, $basic_info=FALSE, $id2_producto=NULL)
	{
		$id_producto = (isset($_GET['id']))? $_GET['id']: $id_producto;
    $id_producto = $id2_producto!=NULL? $id2_producto: $id_producto;

		$sql_res = $this->db->select("id_producto, id_empresa, id_familia, id_unidad, codigo, nombre, stock_min,
									ubicacion, precio_promedio, status, cuenta_cpi, ieps, tipo" )
							->from("productos")
							->where("id_producto", $id_producto)
							->get();

		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
		{
			$data['info']	= $sql_res->row();
		}
    	$sql_res->free_result();

    	if (!$basic_info)
    	{
    		$data['presentaciones'] = $this->getPresentaciones($id_producto);

        $data['familia'] = $this->getFamiliaInfo($data['info']->id_familia, true)['info'];
    	}

		return $data;
	}

	public function deleteProducto($id_producto, $status = 'e')
	{
		$this->db->update('productos', array('status' => $status), "id_producto = {$id_producto}");
		return true;
	}

	public function getFolioNext($id_familia)
	{
		$codigo = 1;
		$res = $this->db->query("SELECT codigo FROM productos WHERE id_familia = {$id_familia} ORDER BY codigo::integer DESC")->row();
		if(isset($res->codigo))
			$codigo = intval($res->codigo) + 1;
		return $codigo;
	}



	public function getUnidades($paginados = true)
	{
		$sql = '';
		//paginacion
		if($paginados)
		{
			$this->load->library('pagination');
			$params = array(
					'result_items_per_page' => '30',
					'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
			);
			if($params['result_page'] % $params['result_items_per_page'] == 0)
				$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
		}

		//Filtros para buscar
		// $_GET['fstatus'] = (isset($_GET['fstatus'])? $_GET['fstatus']: 'ac');
		// $sql .= ($sql==''? 'WHERE': ' AND ')." status = '".$this->input->get('fstatus')."'";

		$str_query = "
				SELECT id_unidad, nombre, abreviatura
				FROM productos_unidades
				".$sql."
				ORDER BY nombre ASC
				";
		if($paginados){
			$query = BDUtil::pagination($str_query, $params, true);
			$res = $this->db->query($query['query']);
		}else
			$res = $this->db->query($str_query);

		$response = array(
				'unidades'       => array(),
				'total_rows'     => (isset($query['total_rows'])? $query['total_rows']: ''),
				'items_per_page' => (isset($params['result_items_per_page'])? $params['result_items_per_page']: ''),
				'result_page'    => (isset($params['result_page'])? $params['result_page']: ''),
		);
		if($res->num_rows() > 0){
			$response['unidades'] = $res->result();
		}

		return $response;
	}

	public function getPresentaciones($id_producto)
	{
		$str_query = "
				SELECT id_presentacion, id_producto, nombre, cantidad, status
				FROM productos_presentaciones
				WHERE status = 'ac' AND id_producto = {$id_producto}
				ORDER BY nombre ASC
				";
		$res = $this->db->query($str_query);

		$response = array();
		if($res->num_rows() > 0){
			$response = $res->result();
		}

		return $response;
	}

	public function infoProducto($text)
	{
		$id_producto = (isset($_GET['id']))? $_GET['id']: $id_producto;
    $id_producto = $id2_producto!=NULL? $id2_producto: $id_producto;

		$result = $this->db->query("SELECT id_producto, id_empresa, id_familia, id_unidad, codigo, nombre,
      	stock_min, ubicacion, precio_promedio, status, cuenta_cpi, impuesto_iva, ieps
      FROM productos WHERE id_empresa = 2 AND lower(nombre) = '".mb_strtolower($text, 'UTF-8')."'")->row();

		return $result;
	}





	/**
	 * Obtiene el listado de familias para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param empresa. Familias de una empresa
	 */
	public function ajaxClasificaciones(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";
		if($this->input->get('empresa') !== false)
			$sql .= " AND id_empresa = {$this->input->get('empresa')}";
		$res = $this->db->query(" SELECT id_familia, id_empresa, codigo, nombre, tipo, status
				FROM productos_familias
				WHERE status = 'ac' {$sql}
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_familia,
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