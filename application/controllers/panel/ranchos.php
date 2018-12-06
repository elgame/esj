<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ranchos extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('ranchos/ajax_get_ranchos/',
		'ranchos/merges/',
		'ranchos/catalogo_xls/',
		'ranchos/show_view_agregar_productor/');

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

public function fecha($fecha)
{
	$meses = array('ENE' => '01', 'FEB' => '02', 'MAR' => '03', 'ABR' => '04', 'MAY' => '05', 'JUN' => '06', 'JUL' => '07', 'AGO' => '08', 'SEP' => '09', 'OCT' => '10', 'NOV' => '11', 'DIC' => '12' );
	$fecha = explode('/', $fecha);
	return $fecha[2].'-'.$meses[strtoupper($fecha[1])].'-'.$fecha[0];
}


  public function index()
  {
		$this->carabiner->js(array(
	        array('general/msgbox.js'),
			array('panel/ranchos/agregar.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administración de Areas / Ranchos / Lineas'
		);

    	$this->load->model('empresas_model');

		$this->load->model('ranchos_model');
		$params['ranchos'] = $this->ranchos_model->getRanchos();
    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    if(!isset($_GET['did_empresa']))
    	$_GET['did_empresa'] = $params['empresa']->id_empresa;

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/ranchos/admin', $params);
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
			array('panel/ranchos/agregar.js'),
		));

    $this->load->model('empresas_model');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Areas / Ranchos / Lineas'
		);

		$this->configAddModRanchos();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('ranchos_model');
			$res_mdl = $this->ranchos_model->addRancho();

			if(!$res_mdl['error'])
				redirect(base_url('panel/ranchos/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
		}

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/ranchos/agregar', $params);
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
				array('panel/ranchos/agregar.js'),
			));

      $this->load->model('ranchos_model');
			$this->load->model('empresas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar Areas / Ranchos / Lineas'
			);

			$this->configAddModRanchos('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->ranchos_model->updateRancho($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/ranchos/?'.MyString::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['rancho'] = $this->ranchos_model->getRanchoInfo();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/ranchos/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/ranchos/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un proveedor
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('ranchos_model');
			$res_mdl = $this->ranchos_model->updateRancho( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/ranchos/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/ranchos/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un proveedor eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('ranchos_model');
			$res_mdl = $this->ranchos_model->updateRancho( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/ranchos/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/ranchos/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de productores para el autocomplete, ajax
	 */
	public function ajax_get_ranchos(){
		$this->load->model('ranchos_model');
		$params = $this->ranchos_model->getRanchosAjax();

		echo json_encode($params);
	}

	public function catalogo_xls()
	{
		$this->load->model('ranchos_model');
		$this->ranchos_model->catalogo_xls();
	}



  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModRanchos($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'nombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[150]'),
			array('field' => 'fempresa',
            'label' => '',
            'rules' => 'required'),
      array('field' => 'did_empresa',
            'label' => 'Empresa',
            'rules' => 'required|numeric'),
      array('field' => 'farea',
            'label' => '',
            'rules' => 'required'),
      array('field' => 'did_area',
            'label' => 'Área',
            'rules' => 'required|numeric'),
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
				$txt = 'El Areas / Ranchos / Lineas se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El Areas / Ranchos / Lineas se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El Areas / Ranchos / Lineas se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El Areas / Ranchos / Lineas se activó correctamente.';
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
