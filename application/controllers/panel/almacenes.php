<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class almacenes extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('vehiculos/ajax_get_vehiculos/',
		'vehiculos/combustible_pdf/',
    'vehiculos/combustible_general_pdf/',
    'almacenes/historial_pdf/');

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
			'titulo' => 'Administración de almacenes'
		);

		$this->load->model('almacenes_model');
		$params['almacenes'] = $this->almacenes_model->getAlmacenes();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/almacenes/admin', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Muestra el Formulario para agregar un almacen
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
			'titulo' => 'Agregar almacen'
		);

		$this->configAddModAlmacenes();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('almacenes_model');
			$res_mdl = $this->almacenes_model->addAlmacen();

			if(!$res_mdl['error'])
				redirect(base_url('panel/almacenes/agregar/?'.String::getVarsLink(array('msg')).'&msg=3'));
		}


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/almacenes/agregar', $params);
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

			$this->load->model('almacenes_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar almacen'
			);

			$this->configAddModAlmacenes('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->almacenes_model->updateAlmacen($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/almacenes/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data'] = $this->almacenes_model->getAlmacenInfo();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/almacenes/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/almacenes/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un camion
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('almacenes_model');
			$res_mdl = $this->almacenes_model->updateAlmacen( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/almacenes/?'.String::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/almacenes/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un camion eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('almacenes_model');
			$res_mdl = $this->almacenes_model->updateAlmacen( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/almacenes/?'.String::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/almacenes/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de vehiculos para el autocomplete, ajax
	 */
	public function ajax_get_vehiculos(){
		$this->load->model('almacenes_model');
		$params = $this->almacenes_model->getVehiculosAjax();

		echo json_encode($params);
	}

  /**
   * Reporte de costos
   * @return [type] [description]
   */
  public function historial()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_inventarios.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Historial de traspasos');

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacenes/historial',$params);
    $this->load->view('panel/footer',$params);
  }
  public function historial_pdf(){
    $this->load->model('almacenes_model');
    $this->almacenes_model->getHistorialPdf();

  }


  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModAlmacenes($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'nombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[60]'),
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
				$txt = 'El almacen se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El almacen se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El almacen se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El almacen se activó correctamente.';
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
