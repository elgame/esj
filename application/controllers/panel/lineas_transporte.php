<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class lineas_transporte extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('lineas_transporte/ajax_get_lineas/');

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
			'titulo' => 'Administración de lineas transporte'
		);

		$this->load->model('lineas_transporte_model');
		$params['lineas'] = $this->lineas_transporte_model->getLineas();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/lineas_transporte/admin', $params);
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
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Linea'
		);

		$this->configAddModLinea();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('lineas_transporte_model');
			$res_mdl = $this->lineas_transporte_model->addLinea();

			if(!$res_mdl['error'])
				redirect(base_url('panel/lineas_transporte/agregar/?'.String::getVarsLink(array('msg')).'&msg=3'));
		}


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/lineas_transporte/agregar', $params);
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
				array('libs/jquery.treeview.js'),
				array('panel/usuarios/add_mod_frm.js')
			));

			$this->load->model('lineas_transporte_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar camion'
			);

			$this->configAddModLinea('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->lineas_transporte_model->updateLinea($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/lineas_transporte/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data'] = $this->lineas_transporte_model->getLineaInfo();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/lineas_transporte/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/lineas_transporte/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un camion
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('lineas_transporte_model');
			$res_mdl = $this->lineas_transporte_model->updateLinea( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/lineas_transporte/?'.String::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/lineas_transporte/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un camion eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('lineas_transporte_model');
			$res_mdl = $this->lineas_transporte_model->updateLinea( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/lineas_transporte/?'.String::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/lineas_transporte/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de camiones para el autocomplete, ajax
	 */
	public function ajax_get_lineas(){
		$this->load->model('lineas_transporte_model');
		$params = $this->lineas_transporte_model->getLineasAjax();

		echo json_encode($params);
	}



  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModLinea($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fnombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[100]'),
			array('field' => 'ftelefonos',
						'label' => 'Telefonos',
						'rules' => 'max_length[70]'),
			array('field' => 'fid',
						'label' => 'Id',
						'rules' => 'max_length[30]'),
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
				$txt = 'La linea se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'La linea se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'La linea se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'La linea se activó correctamente.';
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
