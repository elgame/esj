<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class proveedores extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('proveedores/ajax_get_proveedores/');

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

		$this->load->model('proveedores_model');
		$params['proveedores'] = $this->proveedores_model->getProveedores();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);


// 		$gestor = @fopen("Proveedores.txt", "r");
// 		if ($gestor) {
// 		    while (($bufer = fgets($gestor, 4096)) !== false) {
// 		    	$bufer = utf8_encode($bufer);
// 		    	echo "INSERT INTO proveedores (
// nombre_fiscal, rfc, curp, cuenta_cpi)
// VALUES ('".trim(substr($bufer, 10, 101))."', '".trim(substr($bufer, 101, 21))."', 
// '".trim(substr($bufer, 132, 51))."', '".trim(substr($bufer, 186, 31))."');\n";

// 		        // echo trim(substr($búfer, 10, 101))."<br>"; //nombre
// 		        // echo trim(substr($búfer, 101, 21))."<br>"; //rfc
// 		        // echo trim(substr($búfer, 132, 51))."<br>"; //curp
// 		        // echo trim(substr($búfer, 186, 31))."<br>"; //cuenta contpaqi
// 		    }
// 		    if (!feof($gestor)) {
// 		        echo "Error: fallo inesperado de fgets()\n";
// 		    }
// 		    fclose($gestor);
// 		}

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/proveedores/admin', $params);
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
			'titulo' => 'Agregar Proveedor'
		);

		$this->configAddModProveedor();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('proveedores_model');
			$res_mdl = $this->proveedores_model->addProveedor();

			if(!$res_mdl['error'])
				redirect(base_url('panel/proveedores/agregar/?'.String::getVarsLink(array('msg')).'&msg=3'));
		}


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/proveedores/agregar', $params);
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

			$this->load->model('proveedores_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar proveedor'
			);

			$this->configAddModProveedor('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->proveedores_model->updateProveedor($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data'] = $this->proveedores_model->getProveedorInfo();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/proveedores/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un proveedor
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('proveedores_model');
			$res_mdl = $this->proveedores_model->updateProveedor( $this->input->get('id'), array('status' => 'e') );
			if($res_mdl)
				redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un proveedor eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('proveedores_model');
			$res_mdl = $this->proveedores_model->updateProveedor( $this->input->get('id'), array('status' => 'ac') );
			if($res_mdl)
				redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de productores para el autocomplete, ajax
	 */
	public function ajax_get_proveedores(){
		$this->load->model('proveedores_model');
		$params = $this->proveedores_model->getProveedoresAjax();

		echo json_encode($params);
	}



  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModProveedor($accion='agregar')
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
						'rules' => 'max_length[70]|valid_email'),
			array('field' => 'ftipo_proveedor',
						'label' => 'Tipo de proveedor',
						'rules' => 'required|max_length[2]'),
			array('field' => 'fcuenta_cpi',
						'label' => 'Cuenta ContpaqI',
						'rules' => 'max_length[12]'),
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
				$txt = 'El proveedor se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El proveedor se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El proveedor se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El proveedor se activó correctamente.';
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
