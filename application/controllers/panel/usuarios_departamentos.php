<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class usuarios_departamentos extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('usuarios_departamentos/ajax_get_departamentos/');

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
			'titulo' => 'Administración de puestos'
		);

		$this->load->model('usuarios_departamentos_model');
		$params['puestos'] = $this->usuarios_departamentos_model->getPuestos();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/usuarios_departamentos/admin', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Muestra el Formulario para agregar un camion
	 * @return [type] [description]
	 */
	public function agregar()
	{
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('panel/usuarios/usuarios_puestos.js'),
		));

		$this->load->model('empresas_model');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar puesto'
		);

		$this->configAddModPuestos();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('usuarios_departamentos_model');
			$res_mdl = $this->usuarios_departamentos_model->addPuesto();

			if(!$res_mdl['error'])
				redirect(base_url('panel/usuarios_departamentos/agregar/?'.String::getVarsLink(array('msg')).'&msg=3'));
		}

		$params['empresa'] = $this->empresas_model->getDefaultEmpresa();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/usuarios_departamentos/agregar', $params);
		$this->load->view('panel/footer');
	}

	/*
 	|	Muestra el Formulario para modificar un camion
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
				array('panel/usuarios/usuarios_puestos.js')
			));

			$this->load->model('usuarios_departamentos_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar puesto'
			);

			$this->configAddModPuestos('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->usuarios_departamentos_model->updatePuesto($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/usuarios_departamentos/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data'] = $this->usuarios_departamentos_model->getPuestoInfo();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/usuarios_departamentos/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/usuarios_departamentos/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un camion
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('usuarios_departamentos_model');
			$res_mdl = $this->usuarios_departamentos_model->updatePuesto( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/usuarios_departamentos/?'.String::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/usuarios_departamentos/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un camion eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('usuarios_departamentos_model');
			$res_mdl = $this->usuarios_departamentos_model->updatePuesto( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/usuarios_departamentos/?'.String::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/usuarios_departamentos/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de camiones para el autocomplete, ajax
	 */
	public function ajax_get_departamentos(){
		$this->load->model('usuarios_departamentos_model');
		$params = $this->usuarios_departamentos_model->getDepartamentoAjax();

		echo json_encode($params);
	}



  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModPuestos($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'dempresa',
						'label' => 'Empresa',
						'rules' => ''),
			array('field' => 'did_empresa',
						'label' => 'Empresa',
						'rules' => 'required'),
			array('field' => 'fnombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[30]'),
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
				$txt = 'El departamento se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El departamento se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El departamento se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El departamento se activó correctamente.';
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
