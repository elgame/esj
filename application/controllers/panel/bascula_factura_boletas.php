<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bascula_factura_boletas extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('bascula_factura_boletas/ajax_get_camiones/');

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
			'titulo' => 'Administración de facturas de boletas'
		);

		$this->load->model('bascula_factura_boletas_model');
		$params['camiones'] = $this->bascula_factura_boletas_model->getCamiones();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/bascula/bascula_factura_boletas/admin', $params);
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
			'titulo' => 'Agregar facturas de boletas'
		);

		$this->configAddModCamion();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('bascula_factura_boletas_model');
			$res_mdl = $this->bascula_factura_boletas_model->addCamion();

			if(!$res_mdl['error'])
				redirect(base_url('panel/camiones/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
		}


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/camiones/agregar', $params);
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

			$this->load->model('bascula_factura_boletas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar camion'
			);

			$this->configAddModCamion('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->bascula_factura_boletas_model->updateCamion($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/camiones/?'.MyString::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data'] = $this->bascula_factura_boletas_model->getCamionInfo();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/camiones/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/camiones/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un camion
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('bascula_factura_boletas_model');
			$res_mdl = $this->bascula_factura_boletas_model->updateCamion( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/camiones/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/camiones/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un camion eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('bascula_factura_boletas_model');
			$res_mdl = $this->bascula_factura_boletas_model->updateCamion( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/camiones/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/camiones/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de camiones para el autocomplete, ajax
	 */
	public function ajax_get_camiones(){
		$this->load->model('bascula_factura_boletas_model');
		$params = $this->bascula_factura_boletas_model->getCamionesAjax();

		echo json_encode($params);
	}



  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModCamion($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fplacas',
						'label' => 'Placas',
						'rules' => 'required|max_length[15]|callback_chckPlaca'),
			array('field' => 'fmodelo',
						'label' => 'Modelo',
						'rules' => 'max_length[15]'),
			array('field' => 'fmarca',
						'label' => 'Marca',
						'rules' => 'max_length[15]'),
		);

		$this->form_validation->set_rules($rules);
	}

  public function chckPlaca($placa)
  {
    $sql = $this->input->get('id')? " AND id_camion <> ".$this->input->get('id'): '';
    $result = $this->db->query("SELECT COUNT(placa) as total
      FROM camiones
      WHERE lower(placa) = '".mb_strtolower($placa)."' {$sql}")->row();

    if($result->total > 0){
        $this->form_validation->set_message('chckNombre', 'La placa del camión ya existe.');
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
				$txt = 'El camion se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El camion se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El camion se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El camion se activó correctamente.';
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
