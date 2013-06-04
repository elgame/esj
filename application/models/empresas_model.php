<?php

class empresas_model extends CI_Model{

	function __construct(){
		parent::__construct();
	}

	/**
	 * Obtiene el listado de proveedores
	 */
	public function getEmpresas(){
		$sql = '';
		//paginacion
		$params = array(
				'result_items_per_page' => '30',
				'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
		);
		if($params['result_page'] % $params['result_items_per_page'] == 0)
			$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

		//Filtros para buscar
		if($this->input->get('fnombre') != '')
			$sql = " AND lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%'";

		$query = BDUtil::pagination("
				SELECT id_empresa, nombre_fiscal, rfc, telefono
				FROM empresas
				WHERE status = 'ac'
				".$sql."
				ORDER BY nombre_fiscal ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
			'empresas'       => array(),
			'total_rows'     => $query['total_rows'],
			'items_per_page' => $params['result_items_per_page'],
			'result_page'    => $params['result_page']
		);

		if($res->num_rows() > 0)
			$response['empresas'] = $res->result();

		return $response;
	}

	/**
	 * Obtiene la informacion de un cliente
	 */
	public function getInfoEmpresa($id, $info_basic=false){
		$res = $this->db
			->select('*')
			->from('empresas AS e')
			->where("e.id_empresa = '".$id."'")
		->get();
		if($res->num_rows() > 0){
			$response['info'] = $res->row();
			$res->free_result();

			return $response;
		}else
			return false;
	}

	/**
	 * Agrega la informacion de una sucursal de una empresa, o la info de una empresa
	 * sin sucursales
	 * @param unknown_type $sucu
	 */
	public function addEmpresa($sucu=false){
		$path_img = '';
		//valida la imagen
		$upload_res = UploadFiles::uploadEmpresaLogo();

		if(is_array($upload_res)){
			if($upload_res[0] == false)
				return array(false, $upload_res[1]);
			$path_img = APPPATH.'images/empresas/'.$upload_res[1]['file_name'];
		}
		
		$data = array(
			'nombre_fiscal'  => $this->input->post('dnombre_fiscal'),
			'calle'          => $this->input->post('dcalle'),
			'no_exterior'    => $this->input->post('dno_exterior'),
			'no_interior'    => $this->input->post('dno_interior'),
			'colonia'        => $this->input->post('dcolonia'),
			'localidad'      => $this->input->post('dlocalidad'),
			'municipio'      => $this->input->post('dmunicipio'),
			'estado'         => $this->input->post('destado'),
			'cp'             => $this->input->post('dcp'),
			'rfc'            => $this->input->post('drfc'),
			'telefono'       => $this->input->post('dtelefono'),
			'celular'        => $this->input->post('dcelular'),
			'email'          => $this->input->post('demail'),
			'pag_web'        => $this->input->post('dpag_web'),
			'logo'           => $path_img,
			'regimen_fiscal' => $this->input->post('dregimen_fiscal')
		);
		$this->db->insert('empresas', $data);

		return array(true, '', 3);
	}

	/**
	 * Modifica la informacion de una sucursal de una empresa, o la info de una empresa
	 * sin sucursales
	 */
	public function updateEmpresa(){

		$info = $this->getInfoEmpresa($_GET['id']);

		$path_img = (isset($info['info']->logo)? $info['info']->logo: '');
		//valida la imagen
		$upload_res = UploadFiles::uploadEmpresaLogo();

		if(is_array($upload_res)){
			if($upload_res[0] == false)
				return array(false, $upload_res[1]);

			if($path_img != '')
				UploadFiles::deleteFile($path_img);
			$path_img = APPPATH.'images/empresas/'.$upload_res[1]['file_name'];
		}
		
		$data = array(
			'nombre_fiscal'  => $this->input->post('dnombre_fiscal'),
			'calle'          => $this->input->post('dcalle'),
			'no_exterior'    => $this->input->post('dno_exterior'),
			'no_interior'    => $this->input->post('dno_interior'),
			'colonia'        => $this->input->post('dcolonia'),
			'localidad'      => $this->input->post('dlocalidad'),
			'municipio'      => $this->input->post('dmunicipio'),
			'estado'         => $this->input->post('destado'),
			'cp'             => $this->input->post('dcp'),
			'rfc'            => $this->input->post('drfc'),
			'telefono'       => $this->input->post('dtelefono'),
			'celular'        => $this->input->post('dcelular'),
			'email'          => $this->input->post('demail'),
			'pag_web'        => $this->input->post('dpag_web'),
			'logo'           => $path_img,
			'regimen_fiscal' => $this->input->post('dregimen_fiscal')
		);
		$this->db->update('empresas', $data, "id_empresa = '".$_GET['id']."'");

		return array(true, '', 4);
	}

	/**
	 * Elimina a un cliente, cambia su status a "e":eliminado
	 */
	public function eliminarEmpresa(){
		$this->db->update('empresas', array('status' => 'e'), "id_empresa = '".$_GET['id']."'");
		return array(true, '');
	}


	/**
	 * Obtiene el listado de clientes para usar ajax
	 */
	public function getEmpresasAjax(){
		$sql = '';
		$res = $this->db->query("
				SELECT id_empresa, nombre_fiscal, calle, no_exterior, no_interior, colonia, localidad, municipio, estado, cp, rfc
				FROM empresas
				WHERE status = 'ac' AND lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'
				ORDER BY nombre_fiscal ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id' => $itm->id_empresa,
						'label' => $itm->nombre_fiscal,
						'value' => $itm->nombre_fiscal,
						'item' => $itm,
				);
			}
		}

		return $response;
	}

}