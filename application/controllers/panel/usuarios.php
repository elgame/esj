<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Usuarios extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('ajax_get_siguiente_numero');

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
			'titulo' => 'Administración de Usuarios'
		);

		$this->load->model('usuarios_model');
		$params['usuarios'] = $this->usuarios_model->get_usuarios();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/usuarios/admin', $params);
		$this->load->view('panel/footer');
	}

	/*
 	|	Muestra el Formulario para agregar un Usuarios
 	*/
	public function agregar()
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

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Usuario'
		);

		$this->config_add_usuario();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('usuarios_model');
			$res_mdl = $this->usuarios_model->setRegistro();

			if(!$res_mdl['error'])
				redirect(base_url('panel/usuarios/agregar/?'.String::getVarsLink(array('msg')).'&msg=3'));
		}


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/usuarios/agregar', $params);
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

			$this->load->model('usuarios_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar usuario'
			);

			$this->config_add_usuario('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->usuarios_model->modificar_usuario($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/usuarios/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data'] = $this->usuarios_model->get_usuario_info();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/usuarios/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/usuarios/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/*
 	|	Elimina un usuarios
 	*/
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('usuarios_model');
			$res_mdl = $this->usuarios_model->eliminar_usuario($this->input->get('id'));
			if($res_mdl)
				redirect(base_url('panel/usuarios/?'.String::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/usuarios/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/*
 	|	Activa un articulo
 	*/
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('usuarios_model');
			$res_mdl = $this->usuarios_model->activar_usuario($this->input->get('id'));
			if($res_mdl)
				redirect(base_url('panel/usuarios/?'.String::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/usuarios/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function config_add_usuario($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
							array('field' => 'fnombre',
										'label' => 'Nombre',
										'rules' => 'required|max_length[110]'),
							array('field' => 'ftipo',
										'label' => 'Tipo de Usuario',
										'rules' => 'required'),
							array('field' => 'dprivilegios[]',
										'label' => 'Privilegios',
										'rules' => 'is_natural_no_zero'),
							array('field' => 'femail',
										'label' => 'Email',
										'rules' => 'max_length[70]'),
		);

		if ($accion == 'agregar')
		{
			$rules[] = 	array('field' => 'fpass',
												'label' => 'Password',
												'rules' => 'required|max_length[32]');
			$rules[] = 	array('field' => 'fusuario',
												'label' => 'Usuario',
												'rules' => 'required|max_length[10]|is_unique[usuarios.usuario]');
		}
		else
		{
			$rules[] = 	array('field' => 'fpass',
												'label' => 'Password',
												'rules' => 'max_length[32]');
			$rules[] = 	array('field' => 'fusuario',
												'label' => 'Usuario',
												'rules' => 'required|max_length[10]|callback_valida_email');
		}

		$this->form_validation->set_rules($rules);
	}


	public function valida_email($email)
	{
		if (!$this->usuarios_model->valida_email('usuarios', array('id'=>$_GET['id'], 'usuario'=>$email))) {
			$this->form_validation->set_message('valida_email', 'El %s no esta disponible, intenta con otro.');
			return FALSE;
		}
		return TRUE;
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
				$txt = 'El usuario se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El usuario se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El usuario se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El usuario se activó correctamente.';
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
