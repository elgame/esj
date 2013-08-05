<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class home extends MY_Controller {
	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('');

	public function _remap($method){

		$this->load->model("usuarios_model");
		if($this->usuarios_model->checkSession()){
			$this->usuarios_model->excepcion_privilegio = $this->excepcion_privilegio;
			$this->info_empleado                         = $this->usuarios_model->get_usuario_info($this->session->userdata('id_usuario'), true);

			$this->{$method}();
		}else
			$this->{'login'}();
	}

	public function index(){

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/home.js'),
    ));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Panel de Administración'
		);

		$this->load->library('cfdi');
		$this->cfdi->cargaDatosFiscales(2);
		// echo $this->cfdi->obtenCertificado($this->cfdi->path_certificado, false);
		// echo $this->cfdi->obtenLlave($this->cfdi->path_key);
		// echo base64_decode("LS0tLS1CRUdJTiBSU0EgUFJJVkFURSBLRVktLS0tLQpQcm9jLVR5cGU6IDQsRU5DUllQVEVECkRFSy1JbmZvOiBERVMtRURFMy1DQkMsQ0YyNzFGNDE1Q0NERjMzOQoKeUZVMEtVMEdNdThya21BWWZ5ZHY0WjJLOUttSkpPVHJEMDdqVWNuUWhTbmMwVVBSK0VRdCtwejkvczFxdmtmSgp5OHkyMlphUS9LNGc2TTJLVWNZZkFvWnIwZHNjSHM3MGc5eDBLZU9tdkhXS3ZFN3M2aFY2ZVpVVjZSTEdoWWgzCkQ2Q1NIS2JHdXovNlltU0JCME5DQTJZemZ0SCt6U09zV0o4b2pESzhadmd0N1RiejRza01GaXNmeStlQU9FSEQKMk1GNjJWNVZlVFg5bGhma1ZWREVmOTJvb0VSMDZjdVQ4czlKa1BXemJ1ZGVlMkZYV210U3BkNXU3NURGVlpGdApCZ2d3cU15QytRMjRUd2J5WXNWZkpQS3ViUXdsWlVvTkdyMnBlMTJaajJCOUc2aDcxbTExQ2Flam5maHhqNExECjhucS81T21uUUFvYVlvQXNJTHJ1MWhsSUJWN2dRSXBmNDMxbEJnZnczL0NjdVJTYklrVGw4WXF4aE9OVS9UdmUKNWNrVFpYYkVZb2o5MUVrems2dW5JOWg2Vm9IYjNzNGtSRml2b2E3OE9tWm5EWXJKVUp1OU9PUHpTKzhIY2FIdgo3aEtjY1R3WWJETFB1YUNWdHZjT0paek9Qb0laaCtrbXQyODdyYm55VXZ4NTlaOUd3ZjJmM0dYYzE0WTF6VjFlCkdLUWRCZlZuZWZQc1FNRHVPVHhnc1pKNmc1VXllNlc4MDFLZDJRbWk4NE8xanp6UUhjVytHRGRid2tIaFlNdFUKd2dsek8wZGJLSGxyYVdPa0NUdnNZVjJlQlBlNXZRd0xkWDNOQjBHNHBESjFhbGhtT2F3NzBYeGdSd2M2UHV2MQpSOVRtVVRjQlVjTUY2dVQwdWYzaXhkVHVaY0lMYkVyaTZVTHFvWStNaVFSQ0Z6ZEdvcEJhNTFRM1hpWFhzbGZPCnh1Y0tRbVFDTituVjQyOHFHckZaVmxzVXo4ZzBoR0hSSy95WGc1RG1rVm9MdlE4dzRBUFVCQ2s3clRIbGFQc3UKMFNQZTZDaldjdHNXT0JMdStmNWduZEMxNm44VDRxU3RBZnRLS1hGa1o0UmV3ZFNEeWwwS21BPT0KLS0tLS1FTkQgUlNBIFBSSVZBVEUgS0VZLS0tLS0K");

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/general/home', $params);
		$this->load->view('panel/footer');
	}




	/**
	 * carga el login para entrar al panel
	 */
	public function login(){

		$params['seo'] = array(
			'titulo' => 'Login'
		);

		$this->load->library('form_validation');
		$rules = array(
			array('field'	=> 'usuario',
				'label'		=> 'Usuario',
				'rules'		=> 'required'),
			array('field'	=> 'pass',
				'label'		=> 'Contraseña',
				'rules'		=> 'required')
		);
		$this->form_validation->set_rules($rules);
		if($this->form_validation->run() == FALSE){
			$params['frm_errors'] = array(
					'title' => 'Error al Iniciar Sesión!',
					'msg' => preg_replace("[\n|\r|\n\r]", '', validation_errors()),
					'ico' => 'error');
		}else{
			$data = array('usuario' => $this->input->post('usuario'), 'pass' => $this->input->post('pass'));
			$mdl_res = $this->usuarios_model->setLogin($data);
			if ($mdl_res[0] && $this->usuarios_model->checkSession()) {
				redirect(base_url('panel/home'));
			}
			else{
				$params['frm_errors'] = array(
					'title' => 'Error al Iniciar Sesión!',
					'msg' => 'El usuario y/o contraseña son incorrectos, o no cuenta con los permisos necesarios para loguearse',
					'ico' => 'error');
			}
		}

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/login', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * cierra la sesion del usuario
	 */
	public function logout(){
		$this->session->sess_destroy();
		redirect(base_url('panel/home'));
	}
}

?>