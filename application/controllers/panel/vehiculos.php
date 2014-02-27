<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class vehiculos extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('vehiculos/ajax_get_vehiculos/',
		'vehiculos/combustible_pdf/');

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
			'titulo' => 'Administración de vehiculos'
		);

		$this->load->model('vehiculos_model');
		$params['vehiculos'] = $this->vehiculos_model->getVehiculos();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/vehiculos/admin', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Muestra el Formulario para agregar un vehiculo
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
			'titulo' => 'Agregar vehiculo'
		);

		$this->configAddModVehiculo();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('vehiculos_model');
			$res_mdl = $this->vehiculos_model->addVehiculo();

			if(!$res_mdl['error'])
				redirect(base_url('panel/vehiculos/agregar/?'.String::getVarsLink(array('msg')).'&msg=3'));
		}


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/vehiculos/agregar', $params);
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

			$this->load->model('vehiculos_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar camion'
			);

			$this->configAddModVehiculo('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->vehiculos_model->updateVehiculo($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/vehiculos/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data'] = $this->vehiculos_model->getVehiculoInfo();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/vehiculos/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/vehiculos/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un camion
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('vehiculos_model');
			$res_mdl = $this->vehiculos_model->updateVehiculo( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/vehiculos/?'.String::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/vehiculos/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un camion eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('vehiculos_model');
			$res_mdl = $this->vehiculos_model->updateVehiculo( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/vehiculos/?'.String::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/vehiculos/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de vehiculos para el autocomplete, ajax
	 */
	public function ajax_get_vehiculos(){
		$this->load->model('vehiculos_model');
		$params = $this->vehiculos_model->getVehiculosAjax();

		echo json_encode($params);
	}


	/**
   * Reporte de costos
   * @return [type] [description]
   */
	public function combustible()
	{
		$this->carabiner->js(array(
		  array('general/msgbox.js'),
		  array('panel/vehiculos/rpt_combustible.js'),
		));

		$this->load->library('pagination');
		$this->load->model('productos_model');

		$params['info_empleado']  = $this->info_empleado['info'];
		$params['seo']        = array('titulo' => 'Rendimiento de combustible');

		// $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

		if(isset($_GET['msg']{0}))
		  $params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header',$params);
		$this->load->view('panel/vehiculos/rcombustible',$params);
		$this->load->view('panel/footer',$params);
	}
	public function combustible_pdf(){
		$this->load->model('vehiculos_model');
		$this->vehiculos_model->getRCombustiblePdf();

	}



  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModVehiculo($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fplacas',
						'label' => 'Placas',
						'rules' => 'required|max_length[15]'),
			array('field' => 'fmodelo',
						'label' => 'Modelo',
						'rules' => 'max_length[15]'),
			array('field' => 'fmarca',
						'label' => 'Marca',
						'rules' => 'max_length[15]'),
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
				$txt = 'El vehiculo se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El vehiculo se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El vehiculo se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El vehiculo se activó correctamente.';
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
