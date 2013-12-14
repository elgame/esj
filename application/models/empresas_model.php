<?php

class empresas_model extends CI_Model{
	private $pass_finkok = 'gamaL1!l';

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
				'result_items_per_page' => '40',
				'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
		);
		if($params['result_page'] % $params['result_items_per_page'] == 0)
			$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

		//Filtros para buscar
		if($this->input->get('fnombre') != ''){
			$sql = " WHERE (
				lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
				lower(calle) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
				lower(colonia) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
				lower(municipio) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
				lower(estado) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";
		}

		$_GET['fstatus'] = $this->input->get('fstatus')!==false? $this->input->get('fstatus'): 't';
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." status='".$this->input->get('fstatus')."'";

		$query = BDUtil::pagination("
				SELECT id_empresa, nombre_fiscal, rfc, calle, no_exterior, no_interior, colonia, localidad, municipio, estado, status
				FROM empresas
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

		if($res->num_rows() > 0){
			$response['empresas'] = $res->result();
			foreach ($response['empresas'] as $key => $value) {
				$response['empresas'][$key]->domicilio = $value->calle.($value->no_exterior!=''? ' '.$value->no_exterior: '')
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

	public function getDefaultEmpresa(){
		$params = $this->db->select("*")
      ->from("empresas")
      ->where("predeterminado", "t")
      ->get()
      ->row();
      if (isset($params->id_empresa))
      	return $params;
      else
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

		//certificado
		$dcer_org   = '';
		$dcer       = '';
		$cer_caduca = '';
		$upload_res = UploadFiles::uploadFile('dcer_org');
		if($upload_res !== false && $upload_res !== 'ok'){
			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/cer.php?file={$upload_res}&path=".APPPATH."CFDI/certificados/")) );
			$dcer_org   = $upload_res[0];
			$dcer       = $upload_res[1];

			// $dcer_org = APPPATH.'CFDI/certificados/'.$upload_res;
			// //se genera el archivo cer.pem
			// $certificateCAcerContent = file_get_contents($dcer_org);
			// $certificateCApemContent =  '-----BEGIN CERTIFICATE-----'.PHP_EOL
			// .chunk_split(base64_encode($certificateCAcerContent), 64, PHP_EOL)
			// .'-----END CERTIFICATE-----'.PHP_EOL;
			// $dcer = $dcer_org.'.pem';
			// file_put_contents($dcer, $certificateCApemContent);
			//se obtiene la fecha que caduca el certificado
			$this->load->library('cfdi');
			$cer_caduca = $this->cfdi->obtenFechaCertificado($dcer_org);
		}
		//llave
		$new_pass   = $this->pass_finkok;
		$dkey_path  = '';
		$upload_res = UploadFiles::uploadFile('dkey_path');
		if($upload_res !== false && $upload_res !== 'ok'){
			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/key.php?newpass={$new_pass}&pass={$this->input->post('dpass')}&file={$upload_res}&path=".APPPATH."CFDI/certificados/")) );
			$dkey_path  = $upload_res[0];
			$_POST['dpass'] = $new_pass;
			// $dkey_path = APPPATH.'CFDI/certificados/'.$upload_res;
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
			'email'          => $this->input->post('demail'),
			'pag_web'        => $this->input->post('dpag_web'),
			'logo'           => $path_img,
			'regimen_fiscal' => $this->input->post('dregimen_fiscal'),
			'cer_org'        => $dcer_org,
			'cer'            => $dcer,
			'key_path'       => $dkey_path,
			'pass'           => $this->input->post('dpass'),
			'cfdi_version'   => $this->input->post('dcfdi_version'),
		);
		if($cer_caduca != '')
			$data['cer_caduca'] = $cer_caduca;
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

			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/cer.php?file={$upload_res}&path=".APPPATH."CFDI/certificados/")) );
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

			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/key.php?newpass={$new_pass}&pass={$this->input->post('dpass')}&file={$upload_res}&path=".APPPATH."CFDI/certificados/")) );
			$dkey_path  = $upload_res[0];
			$_POST['dpass'] = $new_pass;
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
			'email'          => $this->input->post('demail'),
			'pag_web'        => $this->input->post('dpag_web'),
			'logo'           => $path_img,
			'regimen_fiscal' => $this->input->post('dregimen_fiscal'),
			'cer_org'        => $dcer_org,
			'cer'            => $dcer,
			'key_path'       => $dkey_path,
			'pass'           => $this->input->post('dpass'),
			'cfdi_version'   => $this->input->post('dcfdi_version'),
		);
		if($cer_caduca != '')
			$data['cer_caduca'] = $cer_caduca;
		$this->db->update('empresas', $data, "id_empresa = '".$_GET['id']."'");

		return array(true, '', 4);
	}

	/**
	 * Elimina a un cliente, cambia su status a "e":eliminado
	 */
	public function eliminarEmpresa(){
		$this->db->update('empresas', array('status' => 'f'), "id_empresa = '".$_GET['id']."'");
		return array(true, '');
	}

	/**
	 * Elimina a un cliente, cambia su status a "e":eliminado
	 */
	public function activarEmpresa(){
		$this->db->update('empresas', array('status' => 't'), "id_empresa = '".$_GET['id']."'");
		return array(true, '');
	}


	/**
	 * Obtiene el listado de empresas para usar en peticiones Ajax.
	 */
	public function getEmpresasAjax(){
		$sql = '';
		$res = $this->db->query("
				SELECT id_empresa, nombre_fiscal, rfc, calle, no_exterior, no_interior, colonia, localidad, municipio, estado, pais, predeterminado
				FROM empresas
				WHERE status = 't' AND lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'
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

  /**
   * Obtiene el listado de proveedores para usar ajax
   * @param term. termino escrito en la caja de texto, busca en el nombre
   * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
   */
  public function getEmpresasAjaxFac($sqlX = null){
    $sql = '';
    if ($this->input->get('term') !== false)
      $sql = " AND lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";

    if ( ! is_null($sqlX))
      $sql .= $sqlX;

    $res = $this->db->query(
      "SELECT id_empresa, nombre_fiscal, rfc, calle, no_exterior, no_interior, colonia, municipio, estado, cp, telefono
        FROM empresas
        WHERE status = 't'
        {$sql}
        ORDER BY nombre_fiscal ASC
        LIMIT 20"
    );

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id'    => $itm->id_empresa,
            'label' => $itm->nombre_fiscal,
            'value' => $itm->nombre_fiscal,
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

}