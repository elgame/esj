<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class clientes extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('clientes/ajax_get_proveedores/');

	public function _remap($method){

		$this->load->model("usuarios_model");
		if($this->usuarios_model->checkSession()){
			$this->usuarios_model->excepcion_privilegio = $this->excepcion_privilegio;
			$this->info_empleado                         = $this->usuarios_model->get_usuario_info($this->session->userdata('id_usuario'), true);

			if($this->usuarios_model->tienePrivilegioDe('', get_class($this).'/'.$method.'/')){
				$this->{$method}();
			}else
				redirect(base_url('panel/home?msg=1'));
		}else
			redirect(base_url('panel/home'));
	}

  public function index()
  {
		$this->carabiner->js(array(
				array('general/msgbox.js')
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administración de Proveedores'
		);

		$this->load->model('clientes_model');
		$params['clientes'] = $this->clientes_model->getClientes();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);


		// $fila = 1;
		// //C¢digo Cliente,Raz¢n Social,R.F.C.,Estatus,Calle,N£mero Exterior,N£mero Interior,Colonia,C¢digo Postal,eMail,Pa¡s,Estado,Ciudad,Municipio
		// if (($gestor = fopen("Clientes_Activos.csv", "r")) !== FALSE) {
		//     while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
		//         $numero = count($datos);
		//         $res = $this->db->query("SELECT * FROM clientes WHERE rfc like '".trim($datos[2])."' AND rfc <> ''");
		//         if ($res->num_rows() > 0)
		//         {
		//         	// $datos[11] = trim($datos[11]);
		//         	// $datos[9] = trim($datos[9]);
		//         	// $datos[2] = trim($datos[2]);
		//         	// if($datos[2] != '')
		//         	// 	echo "UPDATE clientes SET estado='{$datos[11]}', email='{$datos[9]}' WHERE rfc like '{$datos[2]}'; <br>";
		//         }else{
		//         	if($datos[2] != '')
		//         		echo "INSERT INTO clientes (
		// 				nombre_fiscal, calle, no_exterior, no_interior, 
		// 				colonia, cp, email, pais, 
		// 				estado, localidad, municipio, rfc, cuenta_cpi) VALUES 
		// 				('".utf8_encode(trim($datos[1]))."', '".utf8_encode(trim($datos[4]))."', '".trim($datos[5])."', '".trim($datos[6])."', 
		// 				'".utf8_encode(trim($datos[7]))."', '".trim($datos[8])."', '".trim($datos[9])."', '".utf8_encode(trim($datos[10]))."', 
		// 				'".utf8_encode(trim($datos[11]))."', '".utf8_encode(trim($datos[12]))."', '".utf8_encode(trim($datos[13]))."', '".trim($datos[2])."', '".trim($datos[0])."'  ); <br>";
		// 			// echo "'".trim($datos[2])."',";
		//         }
		//         $res->free_result();
		//     }
		//     fclose($gestor);
		// }

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/clientes/admin', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Muestra el Formulario para agregar un proveedor
	 * @return [type] [description]
	 */
	public function agregar()
	{
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Cliente'
		);

		$this->configAddModCliente();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('clientes_model');
			$res_mdl = $this->clientes_model->addCliente();

			if(!$res_mdl['error'])
				redirect(base_url('panel/clientes/agregar/?'.String::getVarsLink(array('msg')).'&msg=3'));
		}

		$this->load->model('documentos_model');
		$params['documentos'] = $this->documentos_model->getDocumentos();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/clientes/agregar', $params);
		$this->load->view('panel/footer');
	}

	/*
 	|	Muestra el Formulario para modificar un usuario
 	*/
	public function modificar()
	{
		if (isset($_GET['id']))
		{
			$this->carabiner->css(array(
				array('libs/jquery.uniform.css', 'screen'),
				array('libs/jquery.treeview.css', 'screen')
			));
			$this->carabiner->js(array(
				array('libs/jquery.uniform.min.js'),
				array('libs/jquery.treeview.js'),
				array('panel/usuarios/add_mod_frm.js')
			));

			$this->load->model('clientes_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar cliente'
			);

			$this->configAddModCliente('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->clientes_model->updateCliente($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/clientes/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['cliente'] = $this->clientes_model->getClienteInfo();

			$this->load->model('documentos_model');
			$params['documentos'] = $this->documentos_model->getDocumentos();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/clientes/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/clientes/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un proveedor
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('clientes_model');
			$res_mdl = $this->clientes_model->updateCliente( $this->input->get('id'), array('status' => 'e') );
			if($res_mdl)
				redirect(base_url('panel/clientes/?'.String::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/clientes/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un proveedor eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('clientes_model');
			$res_mdl = $this->clientes_model->updateCliente( $this->input->get('id'), array('status' => 'ac') );
			if($res_mdl)
				redirect(base_url('panel/clientes/?'.String::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/clientes/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de productores para el autocomplete, ajax
	 */
	public function ajax_get_proveedores(){
		$this->load->model('clientes_model');
		$params = $this->clientes_model->getClientesAjax();

		echo json_encode($params);
	}



  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModCliente($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fnombre_fiscal',
						'label' => 'Nombre fiscal',
						'rules' => 'required|max_length[140]'),
			array('field' => 'fcalle',
						'label' => 'Calle',
						'rules' => 'max_length[60]'),
			array('field' => 'fno_exterior',
						'label' => 'No. exterior',
						'rules' => 'max_length[7]'),
			array('field' => 'fno_interior',
						'label' => 'No. interior',
						'rules' => 'max_length[7]'),
			array('field' => 'fcolonia',
						'label' => 'Colonia',
						'rules' => 'max_length[60]'),
			array('field' => 'flocalidad',
						'label' => 'Localidad',
						'rules' => 'max_length[45]'),
			array('field' => 'fmunicipio',
						'label' => 'Municipio',
						'rules' => 'max_length[45]'),
			array('field' => 'festado',
						'label' => 'Estado',
						'rules' => 'max_length[45]'),
			array('field' => 'fpais',
						'label' => 'Pais',
						'rules' => 'max_length[25]'),

			array('field' => 'frfc',
						'label' => 'RFC',
						'rules' => 'max_length[13]'),
			array('field' => 'fcurp',
						'label' => 'CURP',
						'rules' => 'max_length[35]'),
			array('field' => 'fcp',
						'label' => 'CP',
						'rules' => 'max_length[10]'),
			array('field' => 'ftelefono',
						'label' => 'Telefono',
						'rules' => 'max_length[15]'),
			array('field' => 'fcelular',
						'label' => 'Celular',
						'rules' => 'max_length[20]'),

			array('field' => 'femail',
						'label' => 'Email',
						'rules' => 'max_length[250]'),
			array('field' => 'fcuenta_cpi',
						'label' => 'Cuenta ContpaqI',
						'rules' => 'max_length[12]'),
			array('field' => 'fdias_credito',
						'label' => 'Dias de credito',
						'rules' => 'is_natural'),

			array('field' => 'documentos[]',
						'label' => 'Documentos del cliente',
						'rules' => 'is_natural_no_zero'),
		);

		$this->form_validation->set_rules($rules);
	}


	private function showMsgs($tipo, $msg='', $title='Usuarios')
	{
		switch($tipo){
			case 1:
				$txt = 'El campo ID es requerido.';
				$icono = 'error';
				break;
			case 2: //Cuendo se valida con form_validation
				$txt = $msg;
				$icono = 'error';
				break;
			case 3:
				$txt = 'El cliente se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El cliente se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El cliente se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El cliente se activó correctamente.';
				$icono = 'success';
				break;
		}

		return array(
				'title' => $title,
				'msg' => $txt,
				'ico' => $icono);
	}
}



/* End of file usuarios.php */
/* Location: ./application/controllers/panel/usuarios.php */
