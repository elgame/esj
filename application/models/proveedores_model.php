<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class proveedores_model extends CI_Model {
	private $pass_finkok = 'gamaL1!l';

	function __construct()
	{
		parent::__construct();
	}

	public function getProveedores($paginados = true)
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
			$sql = "WHERE ( lower(p.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.calle) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.colonia) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.municipio) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.estado) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 'ac');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." p.status='".$this->input->get('fstatus')."'";

		if($this->input->get('ftipo_proveedor') != '' && $this->input->get('ftipo_proveedor') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." p.tipo_proveedor='".$this->input->get('ftipo_proveedor')."'";

		$query = BDUtil::pagination("
				SELECT p.id_proveedor, p.nombre_fiscal, p.calle, p.no_exterior, p.no_interior, p.colonia, p.localidad, p.municipio,
							p.telefono, p.estado, p.tipo_proveedor, p.status
				FROM proveedores p
				".$sql."
				ORDER BY p.nombre_fiscal ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'proveedores'    => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['proveedores'] = $res->result();
			foreach ($response['proveedores'] as $key => $value) {
				$response['proveedores'][$key]->direccion = $value->calle.($value->no_exterior!=''? ' '.$value->no_exterior: '')
										 .($value->no_interior!=''? $value->no_interior: '')
										 .($value->colonia!=''? ', '.$value->colonia: '')
										 .($value->localidad!=''? ', '.$value->localidad: '')
										 .($value->municipio!=''? ', '.$value->municipio: '')
										 .($value->estado!=''? ', '.$value->estado: '');
			}
		}

		return $response;
	}

 	/**
 	 * Agrega un proveedor a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addProveedor($data=NULL)
	{
		//certificado
		$dcer_org   = '';
		$dcer       = '';
		$cer_caduca = '';
		$upload_res = UploadFiles::uploadFile('dcer_org');
		var_dump($upload_res);
		if($upload_res !== false && $upload_res !== 'ok'){
			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/cer.php?file={$upload_res}&path=".APPPATH."CFDI/certificados_pv/")) );
			$dcer_org   = $upload_res[0];
			$dcer       = $upload_res[1];

			$this->load->library('cfdi');
			$cer_caduca = $this->cfdi->obtenFechaCertificado($dcer_org);
		}
		//llave
		$new_pass   = $this->pass_finkok;
		$dkey_path  = '';
		$upload_res = UploadFiles::uploadFile('dkey_path');
		if($upload_res !== false && $upload_res !== 'ok'){
			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/key.php?newpass={$new_pass}&pass={$this->input->post('dpass')}&file={$upload_res}&path=".APPPATH."CFDI/certificados_pv/")) );
			$dkey_path  = $upload_res[0];
			$_POST['dpass'] = $new_pass;
		}

		if ($data==NULL)
		{
			$data = array(
						'nombre_fiscal'  => $this->input->post('fnombre_fiscal'),
						'calle'          => $this->input->post('fcalle'),
						'no_exterior'    => $this->input->post('fno_exterior'),
						'no_interior'    => $this->input->post('fno_interior'),
						'colonia'        => $this->input->post('fcolonia'),
						'localidad'      => $this->input->post('flocalidad'),
						'municipio'      => $this->input->post('fmunicipio'),
						'estado'         => $this->input->post('festado'),
						'cp'             => $this->input->post('fcp'),
						'telefono'       => $this->input->post('ftelefono'),
						'celular'        => $this->input->post('fcelular'),
						'email'          => $this->input->post('femail'),
						'cuenta_cpi'     => $this->input->post('fcuenta_cpi'),
						'tipo_proveedor' => $this->input->post('ftipo_proveedor'),
						'rfc'            => $this->input->post('frfc'),
						'curp'           => $this->input->post('fcurp'),
						'regimen_fiscal' => $this->input->post('dregimen_fiscal'),
						'cer_org'        => $dcer_org,
						'cer'            => $dcer,
						'key_path'       => $dkey_path,
						'pass'           => $this->input->post('dpass'),
						'cfdi_version'   => $this->input->post('dcfdi_version'),
						);
			if($cer_caduca != '')
				$data['cer_caduca'] = $cer_caduca;
		}

		$this->db->insert('proveedores', $data);
		// $id_proveedor = $this->db->insert_id('proveedores', 'id_proveedor');

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_proveedor [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateProveedor($id_proveedor, $data=NULL)
	{
		$info = $this->getProveedorInfo($id_proveedor);

		//certificado
		$dcer_org   = (isset($info['info']->cer_org)? $info['info']->cer_org: '');
		$dcer       = (isset($info['info']->cer)? $info['info']->cer: '');
		$cer_caduca = (isset($info['info']->cer_caduca)? $info['info']->cer_caduca: '');
		$upload_res = UploadFiles::uploadFile('dcer_org');
		if($upload_res !== false && $upload_res !== 'ok'){
			if($dcer_org != '' && strpos($dcer_org, $upload_res) === false){
				UploadFiles::deleteFile($dcer_org);
				UploadFiles::deleteFile($dcer);
			}

			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/cer.php?file={$upload_res}&path=".APPPATH."CFDI/certificados_pv/")) );
			$dcer_org   = $upload_res[0];
			$dcer       = $upload_res[1];
			//se obtiene la fecha que caduca el certificado
			$this->load->library('cfdi');
			$cer_caduca = $this->cfdi->obtenFechaCertificado($dcer_org);
		}
		//llave
		$new_pass = $this->pass_finkok;
		$dkey_path = (isset($info['info']->key_path)? $info['info']->key_path: '');
		$upload_res = UploadFiles::uploadFile('dkey_path');
		if($upload_res !== false && $upload_res !== 'ok'){
			if($dkey_path != '' && strpos($dkey_path, $upload_res) === false)
				UploadFiles::deleteFile($dkey_path);

			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/key.php?newpass={$new_pass}&pass={$this->input->post('dpass')}&file={$upload_res}&path=".APPPATH."CFDI/certificados_pv/")) );
			$dkey_path  = $upload_res[0];
			$_POST['dpass'] = $new_pass;
		}

		if ($data==NULL)
		{
			$data = array(
						'nombre_fiscal'  => $this->input->post('fnombre_fiscal'),
						'calle'          => $this->input->post('fcalle'),
						'no_exterior'    => $this->input->post('fno_exterior'),
						'no_interior'    => $this->input->post('fno_interior'),
						'colonia'        => $this->input->post('fcolonia'),
						'localidad'      => $this->input->post('flocalidad'),
						'municipio'      => $this->input->post('fmunicipio'),
						'estado'         => $this->input->post('festado'),
						'cp'             => $this->input->post('fcp'),
						'telefono'       => $this->input->post('ftelefono'),
						'celular'        => $this->input->post('fcelular'),
						'email'          => $this->input->post('femail'),
						'cuenta_cpi'     => $this->input->post('fcuenta_cpi'),
						'tipo_proveedor' => $this->input->post('ftipo_proveedor'),
						'rfc'            => $this->input->post('frfc'),
						'curp'           => $this->input->post('fcurp'),
						'regimen_fiscal' => $this->input->post('dregimen_fiscal'),
						'cer_org'        => $dcer_org,
						'cer'            => $dcer,
						'key_path'       => $dkey_path,
						'pass'           => $this->input->post('dpass'),
						'cfdi_version'   => $this->input->post('dcfdi_version'),
						);
			if($cer_caduca != '')
				$data['cer_caduca'] = $cer_caduca;
		}

		$this->db->update('proveedores', $data, array('id_proveedor' => $id_proveedor));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_proveedor [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getProveedorInfo($id_proveedor=FALSE, $basic_info=FALSE)
	{
		$id_proveedor = $id_proveedor ? $id_proveedor : $_GET['id'] ;

		$sql_res = $this->db->select("id_proveedor, nombre_fiscal, calle, no_exterior, no_interior, colonia, localidad, municipio,
							estado, cp, telefono, celular, email, cuenta_cpi, tipo_proveedor, rfc, curp, status,
                            cer_org, cer, key_path, pass, cfdi_version, cer_caduca, regimen_fiscal" )
												->from("proveedores")
												->where("id_proveedor", $id_proveedor)
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

	public function getRanchosAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND upper(rancho) LIKE '%".mb_strtoupper($this->input->get('term'), 'UTF-8')."%'";
		$res = $this->db->query("
				SELECT rancho
				FROM ranchos_bascula
				WHERE rancho <> '' ".$sql."
				ORDER BY rancho ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => '0',
						'label' => $itm->rancho,
						'value' => $itm->rancho,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */