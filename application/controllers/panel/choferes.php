<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class choferes extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('choferes/ajax_get_choferes/');

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
			'titulo' => 'Administración de choferes'
		);

		$this->load->model('choferes_model');
		$params['choferes'] = $this->choferes_model->getChoferes();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/choferes/admin', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Muestra el Formulario para agregar un chofer
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
			'titulo' => 'Agregar chofer'
		);

		$this->configAddModChofer();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('choferes_model');
			$res_mdl = $this->choferes_model->addChofer();

			if(!$res_mdl['error'])
				redirect(base_url('panel/choferes/agregar/?'.String::getVarsLink(array('msg')).'&msg=3'));
		}


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/choferes/agregar', $params);
		$this->load->view('panel/footer');
	}

	/*
 	|	Muestra el Formulario para modificar un chofer
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

			$this->load->model('choferes_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar chofer'
			);

			$this->configAddModChofer('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->choferes_model->updateChofer($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/choferes/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data'] = $this->choferes_model->getChoferInfo();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/choferes/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/choferes/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un chofer
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('choferes_model');
			$res_mdl = $this->choferes_model->updateChofer( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/choferes/?'.String::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/choferes/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un chofer eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('choferes_model');
			$res_mdl = $this->choferes_model->updateChofer( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/choferes/?'.String::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/choferes/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de choferes para el autocomplete, ajax
	 */
	public function ajax_get_choferes(){
		$this->load->model('choferes_model');
		$params = $this->choferes_model->getChoferesAjax();

		echo json_encode($params);
	}



  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModChofer($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fnombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[120]'),
		);

    if ($accion === 'agregar')
    {
      $rules = array(
        array('field' => 'fnombre',
              'label' => 'Nombre',
              'rules' => 'required|max_length[120]|callback_chckNombre'),
      );
    }

		$this->form_validation->set_rules($rules);
	}


  public function chckNombre($nombre)
  {
    $result = $this->db->query("SELECT COUNT(nombre) as total
      FROM choferes
      WHERE lower(nombre) = '".mb_strtolower($nombre)."'")->row();

    if($result->total > 0){
        $this->form_validation->set_message('chckNombre', 'El nombre del chofer ya existe.');
      return false;
    }else
      return true;
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
				$txt = 'El chofer se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El chofer se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El chofer se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El chofer se activó correctamente.';
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
