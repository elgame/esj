<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class empresas extends MY_Controller {
	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('empresas/ajax_get_empresas/');

	public function _remap($method){
		$this->load->model("empleados_model");
		if($this->empleados_model->checkSession()){
			$this->empleados_model->excepcion_privilegio = $this->excepcion_privilegio;
			$this->info_empleado                         = $this->empleados_model->getInfoEmpleado($this->session->userdata('id_usuario'), true);

			if($this->empleados_model->tienePrivilegioDe('', get_class($this).'/'.$method.'/')){
				$this->{$method}();
			}else
				redirect(base_url('panel/home?msg=1'));
		}else
			redirect(base_url('panel/home'));
	}

	/**
	 * Default. Mustra el listado de proveedores para administrarlos
	 */
	public function index(){
		$this->carabiner->js(array(
			array('general/msgbox.js'),
		));
		$this->load->model('empresas_model');
		$this->load->library('pagination');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administrar Empresas'
		);

		$params['empresas'] = $this->empresas_model->getEmpresas();

		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/empresas/listado', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Agrega un cliente a la bd
	 */
	public function agregar(){
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.numeric.js'),
			array('panel/clientes/frm_addmod.js')
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Empresa'
		);

		$this->configAddModEmpresa();

		if($this->form_validation->run() == FALSE){
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}else{
			$this->load->model('empresas_model');
			$respons = $this->empresas_model->addEmpresa();

			if($respons[0])
        redirect(base_url('panel/empresas/agregar/?'.String::getVarsLink(array('msg')).'&msg='.$respons[2]));
      else
        $params['frm_errors'] = $this->showMsgs(2, $respons[1]);
		}

		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/empresas/agregar', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Modificar una sucursal a un cliente a la bd
	 */
	public function modificar(){
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.numeric.js'),
			array('general/msgbox.js'),
			array('panel/clientes/frm_addmod.js')
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
				'titulo' => 'Modificar Empresa'
		);

		if(isset($_GET['id']{0})){
			$this->configAddModEmpresa();
			$this->load->model('empresas_model');

			if($this->form_validation->run() == FALSE){
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}else{
				$respons = $this->empresas_model->updateEmpresa();

				if($respons[0])
					redirect(base_url('panel/empresas/modificar/?'.String::getVarsLink(array('msg')).'&msg='.$respons[2]));
			}

			$params['info'] = $this->empresas_model->getInfoEmpresa($_GET['id']);
		}else
			$params['frm_errors'] = $this->showMsgs(1);

		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/empresas/modificar', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Elimina a un cliente, cambia el status a "e":eliminado
	 */
	public function eliminar(){
		if(isset($_GET['id']{0})){
			$this->load->model('empresas_model');
			$respons = $this->empresas_model->eliminarEmpresa();
			if($respons[0])
				redirect(base_url('panel/empresas/?msg=5'));
		}else
			$params['frm_errors'] = $this->showMsgs(1);
	}

	/**
	 * Obtiene lostado de clientes para el autocomplete, ajax
	 */
	public function ajax_get_empresas(){
		$this->load->model('empresas_model');
		$params = $this->empresas_model->getEmpresasAjax();

		echo json_encode($params);
	}

	/**
	 * Configura los metodos de agregar y modificar
	 */
	private function configAddModEmpresa(){
		$this->load->library('form_validation');
		$contacto = false;

			$rules = array(
				array('field'	=> 'dnombre_fiscal',
						'label'	=> 'Nombre Fiscal',
						'rules'	=> 'required|max_length[130]'),
				array('field'	=> 'drfc',
						'label'	=> 'RFC',
						'rules'	=> 'max_length[13]'),
				array('field'	=> 'dcalle',
						'label'	=> 'Calle',
						'rules'	=> 'max_length[60]'),
				array('field'	=> 'dno_exterior',
						'label'	=> 'No exterior',
						'rules'	=> 'max_length[7]'),
				array('field'	=> 'dno_interior',
						'label'	=> 'No interior',
						'rules'	=> 'max_length[7]'),
				array('field'	=> 'dcolonia',
						'label'	=> 'Colonia',
						'rules'	=> 'max_length[60]'),
				array('field'	=> 'dlocalidad',
						'label'	=> 'Localidad',
						'rules'	=> 'max_length[45]'),
				array('field'	=> 'dmunicipio',
						'label'	=> 'Municipio',
						'rules'	=> 'max_length[45]'),
				array('field'	=> 'destado',
						'label'	=> 'Estado',
						'rules'	=> 'max_length[45]'),
				array('field'	=> 'dcp',
						'label'	=> 'CP',
						'rules'	=> 'max_length[10]'),
				array('field'	=> 'dregimen_fiscal',
						'label'	=> 'Régimen fiscal',
						'rules'	=> 'required|max_length[200]'),
				array('field'	=> 'dtelefono',
						'label'	=> 'Teléfono',
						'rules'	=> 'max_length[15]'),
				array('field'	=> 'dcelular',
						'label'	=> 'Celular',
						'rules'	=> 'max_length[20]'),
				array('field'	=> 'demail',
						'label'	=> 'Email',
						'rules'	=> 'valid_email|max_length[70]'),
				array('field'	=> 'dpag_web',
						'label'	=> 'Pag Web',
						'rules'	=> 'max_length[80]')
			);

		$this->form_validation->set_rules($rules);
	}


	/**
	 * Muestra mensajes cuando se realiza alguna accion
	 * @param unknown_type $tipo
	 * @param unknown_type $msg
	 * @param unknown_type $title
	 */
	private function showMsgs($tipo, $msg='', $title='Clientes!'){
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
				$txt = 'La empresa se agrego correctamente.';
				$icono = 'success';
			break;
			case 4:
				$txt = 'La empresa se modifico correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'La empresa se elimino correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El contacto se agrego correctamente.';
				$icono = 'success';
			break;
			case 7:
				$txt = 'El contacto se elimino correctamente.';
				$icono = 'success';
			break;
		}

		return array(
			'title' => $title,
			'msg' => $txt,
			'ico' => $icono);
	}
}

?>