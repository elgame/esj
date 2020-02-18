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

		$id_familia = $this->db->insert_id('productos_familias_id_familia_seq');

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
				case 'a': $data['info']->tipo_text = 'Activo'; break;
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
			$sql .= ($sql==''? 'WHERE': ' AND ')." (lower(nombre) LIKE '%".mb_strtolower($this->input->get('fproducto'), 'UTF-8')."%' OR
          lower(codigo) LIKE '".mb_strtolower($this->input->get('fproducto'), 'UTF-8')."%')";

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
        'id_empresa'  => $familia['empresa']->id_empresa,
        'id_familia'  => $familia['info']->id_familia,
        'id_unidad'   => $this->input->post('funidad'),
        'codigo'      => $this->input->post('fcodigo'),
        'nombre'      => $this->input->post('fnombre'),
        'stock_min'   => (is_numeric($this->input->post('fstock_min'))? $this->input->post('fstock_min'): 0),
        'ubicacion'   => $this->input->post('ubicacion'),
        'ieps'        => is_numeric($this->input->post('fieps')) ? $this->input->post('fieps') : 0,
        'cuenta_cpi'  => $this->input->post('cuenta_contpaq'),
        'tipo'        => $this->input->post('ftipo'),
        'tipo_apli'   => $this->input->post('ftipo_apli'),
        // Activos
        'tipo_activo' => ($this->input->post('ftipo_activo')? $this->input->post('ftipo_activo'): ''),
        'monto'       => ($this->input->post('fmonto')? $this->input->post('fmonto'): 0),
        'descripcion' => ($this->input->post('fdescripcion')? $this->input->post('fdescripcion'): ''),
			);

      // Activos
      if ($this->input->post('ftipo_activo')) {
        $ccodigo = explode('-', $this->input->post('fcodigo'));
        if (count($ccodigo) === 3) {
          if ($ccodigo[2] != '') {
            $date = DateTime::createFromFormat('ymd', $ccodigo[2]);
            if ($date) {
              $data['fecha_compra'] = $date->format("Y-m-d");
            }
          }
        }
      }
		}

		$this->db->insert('productos', $data);
		$id_producto = $this->db->insert_id('productos_id_producto_seq');

    $this->addPresentacion($id_producto);

    if ($data['id_empresa'] == '20') { // Empresa Agro 20
      $this->addColores($id_producto);
    }

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
        'id_empresa'  => $familia['empresa']->id_empresa,
        'id_familia'  => $familia['info']->id_familia,
        'id_unidad'   => $this->input->post('funidad'),
        'codigo'      => $this->input->post('fcodigo'),
        'nombre'      => $this->input->post('fnombre'),
        'stock_min'   => (is_numeric($this->input->post('fstock_min'))? $this->input->post('fstock_min'): 0),
        'ubicacion'   => $this->input->post('ubicacion'),
        'ieps'        => is_numeric($this->input->post('fieps')) ? $this->input->post('fieps') : 0,
        'cuenta_cpi'  => $this->input->post('cuenta_contpaq'),
        'tipo'        => $this->input->post('ftipo'),
        'tipo_apli'   => $this->input->post('ftipo_apli'),
        // Activos
        'tipo_activo' => ($this->input->post('ftipo_activo')? $this->input->post('ftipo_activo'): ''),
        'monto'       => ($this->input->post('fmonto')? $this->input->post('fmonto'): 0),
        'descripcion' => ($this->input->post('fdescripcion')? $this->input->post('fdescripcion'): ''),
			);

      // Activos
      if ($this->input->post('ftipo_activo')) {
        $ccodigo = explode('-', $this->input->post('fcodigo'));
        if (count($ccodigo) === 3) {
          if ($ccodigo[2] != '') {
            $date = DateTime::createFromFormat('ymd', $ccodigo[2]);
            if ($date) {
              $data['fecha_compra'] = $date->format("Y-m-d");
            }
          }
        }
      }
		}

		$this->db->update('productos', $data, "id_producto = {$id_producto}");

		$this->addPresentacion($id_producto);

    if ($data['id_empresa'] == '20') { // Empresa Agro 20
      $this->addColores($id_producto);
    }

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
      $tabla_insert = 'productos_presentaciones';
      $field_where = 'id_presentacion';
      if ($this->input->post('tipo_familia') == 'a') {
        $tabla_insert = 'productos_piezas';
        $field_where = 'id_pieza';
      }

			foreach ($this->input->post('pnombre') as $key => $value)
			{
				if ($value != '' && $_POST['pcantidad'][$key] != '')
				{
					if ($_POST['pidpresentacion'][$key] != '')
					{
            $datos = array(
              'nombre'    => $value,
              'cantidad'  => (is_numeric($_POST['pcantidad'][$key])? $_POST['pcantidad'][$key]: 1),
              'status'    => (isset($_POST['pquitar'.$_POST['pidpresentacion'][$key]])? 'e': 'ac'),
            );
            if ($this->input->post('tipo_familia') == 'a' && !empty($_POST['pidproducto'][$key])) {
              $datos['id_producto_pieza'] = $_POST['pidproducto'][$key];
            }
					  $this->db->update($tabla_insert, $datos, "{$field_where} = ".$_POST['pidpresentacion'][$key]);
					}else
					{
            $datos = array(
              'id_producto' => $id_producto,
              'nombre'      => $value,
              'cantidad'    => (is_numeric($_POST['pcantidad'][$key])? $_POST['pcantidad'][$key]: 1),
            );
            if ($this->input->post('tipo_familia') == 'a' && !empty($_POST['pidproducto'][$key])) {
              $datos['id_producto_pieza'] = $_POST['pidproducto'][$key];
            }
						$this->db->insert($tabla_insert, $datos);
					}
				}
			}
		}

		return true;
	}

  public function addColores($id_producto, $data=NULL)
  {
    if ($data == NULL) {
      $this->db->delete('productos_color_agro', "id_producto = {$id_producto}");

      if ($this->input->post('colorEmpresaId')) {
        foreach ($this->input->post('colorEmpresaId') as $key => $idEmpresa) {
          $this->db->insert('productos_color_agro', [
            'id_producto' => $id_producto,
            'id_empresa'  => $_POST['colorEmpresaId'][$key],
            'color'       => $_POST['colorColor'][$key],
            'tipo_apli'   => $_POST['colorTipoApli'][$key],
          ]);
        }
      }
    }
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
									ubicacion, precio_promedio, status, cuenta_cpi, ieps, tipo, tipo_activo, monto, tipo_apli, descripcion" )
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
  		$data['piezas'] = $this->getPiezas($id_producto);
      $data['familia'] = $this->getFamiliaInfo($data['info']->id_familia, true)['info'];
      $data['colores'] = $this->getColores($id_producto);
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
		$res = $this->db->query("SELECT Coalesce(t.codigo[2], t.codigo[1]) AS codigo, t.rfc,
        t.codigo[3] AS codigo_fecha, t.tipo
      FROM (
        SELECT string_to_array(p.codigo, '-') AS codigo, substring(e.rfc, 1, 3) AS rfc,
          pf.tipo
        FROM empresas e
          LEFT JOIN productos_familias pf ON e.id_empresa = pf.id_empresa
          LEFT JOIN productos p ON pf.id_familia = p.id_familia
        WHERE pf.id_familia = {$id_familia}
      ) t
      ORDER BY Coalesce(t.codigo[2], t.codigo[1])::integer DESC")->row();

		if(isset($res->codigo)){
      $codigo = intval($res->codigo) + 1;
    }

    // Si es activo
    if ($res->tipo == 'a') {
      $codigo = "{$res->rfc}-{$codigo}-";
    }

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

  public function getColores($id_producto)
  {
    $str_query = "
        SELECT pca.id_producto, pca.id_empresa, pca.color, pca.tipo_apli,
          e.nombre_fiscal AS empresa
        FROM productos_color_agro pca
          INNER JOIN empresas e ON e.id_empresa = pca.id_empresa
        WHERE pca.id_producto = {$id_producto}
        ORDER BY empresa ASC
        ";
    $res = $this->db->query($str_query);

    $response = array();
    if($res->num_rows() > 0){
      $response = $res->result();
    }

    return $response;
  }

  public function getPiezas($id_producto)
  {
    $str_query = "
        SELECT id_pieza, id_producto, nombre, cantidad, status, id_producto_pieza
        FROM productos_piezas
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
      	stock_min, ubicacion, precio_promedio, status, cuenta_cpi, impuesto_iva, ieps, tipo_apli, descripcion
      FROM productos WHERE id_empresa = 2 AND lower(nombre) = '".mb_strtolower($text, 'UTF-8')."'")->row();

		return $result;
	}

  public function getProductosInfo($id_producto=FALSE, $basic_info=FALSE)
  {
    $id_producto = $id_producto? $id_producto: (isset($_GET['id'])? $_GET['id']: 0);

    $sql_res = $this->db->select("id_producto, id_empresa, id_familia, id_unidad, codigo, nombre, stock_min,
                                  ubicacion, precio_promedio, status, cuenta_cpi, impuesto_iva, ieps,
                                  last_precio, tipo, tipo_apli, descripcion" )
                        ->from("productos")
                        ->where("id_producto", $id_producto)
                        ->get();
    $data['info'] = array();

    if ($sql_res->num_rows() > 0)
      $data['info'] = $sql_res->row();
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
  public function getProductosAjax(){
    $sql = '';
    if ($this->input->get('term') !== false)
      $sql .= " AND lower(p.nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";
    if ($this->input->get('tipo') !== false) {
      if (is_array($this->input->get('tipo'))) {
        $sql .= " AND pf.tipo in('".implode("','", $this->input->get('tipo'))."')";
      } else
        $sql .= " AND pf.tipo = '".$this->input->get('tipo')."'";
    }

    if ($this->input->get('did_empresa') !== false && $this->input->get('did_empresa') !== '')
      $sql .= " AND p.id_empresa in(".$this->input->get('did_empresa').")";

    $res = $this->db->query(
        "SELECT p.id_producto, p.nombre, p.codigo, p.tipo, pf.nombre AS familia
        FROM productos p
          INNER JOIN productos_familias pf ON pf.id_familia = p.id_familia
        WHERE pf.status = 'ac' AND p.status = 'ac'
          {$sql}
        ORDER BY p.nombre ASC
        LIMIT 20"
    );

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $nombre = $itm->nombre;
        if ($this->input->get('con_fam') == 'true') {
          $nombre = $itm->nombre.' - '.$itm->familia;
        }
        $response[] = array(
            'id'    => $itm->id_producto,
            'label' => $nombre,
            'value' => $nombre,
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

  public function getFamiliasAjax($params)
  {
    $sql = '';
    if (isset($params['id_empresa'])) {
      $sql = " AND id_empresa = {$params['id_empresa']}";
    }

    if (isset($params['tipo'])) {
      $sql = " AND tipo = '{$params['tipo']}'";
    }

    $query = $this->db->query("SELECT id_familia, id_empresa, codigo, nombre, tipo
        FROM productos_familias
        WHERE status = 'ac' {$sql}
        ORDER BY nombre ASC");

    return $query->result();
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */