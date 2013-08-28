<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('banco/get_cuentas_banco/');

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

 	

	public function depositar(){
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('general/util.js'),
			array('panel/banco/deposito_retiro.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar deposito'
		);
		
		$this->load->model('banco_cuentas_model');

		$this->configAddDeposito();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$res_mdl = $this->banco_cuentas_model->addDeposito();

			if(!$res_mdl['error'])
				redirect(base_url('panel/banco/depositar/?'.String::getVarsLink(array('msg')).'&msg=7 '));
		}

		$params['bancos']       = $this->banco_cuentas_model->getBancos(false);
		$_GET['id_banco']       = $params['bancos']['bancos'][0]->id_banco;
		$params['cuentas']      = $this->banco_cuentas_model->getCuentas(false);
		$params['cuenta_saldo'] = (isset($params['cuentas']['cuentas'][0])? $params['cuentas']['cuentas'][0]->saldo: 0);
		
		$params['metods_pago']  = array( 
			array('nombre' => 'Transferencia', 'value' => 'transferencia'),
			array('nombre' => 'Cheque', 'value' => 'cheque'),
			array('nombre' => 'Efectivo', 'value' => 'efectivo'),
			array('nombre' => 'Deposito', 'value' => 'deposito'),
		);

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/banco/movimientos/deposito', $params);
		$this->load->view('panel/footer');
	}

	public function retirar(){

	}

	public function get_cuentas_banco(){
		$response = array('cuentas' => array());
		if (isset($_GET['id_banco']{0})) {
			$this->load->model('banco_cuentas_model');
			$response = $this->banco_cuentas_model->getCuentas(false);
		}
		echo json_encode($response);
	}



	/**
	 * ************************************************
	 * ***** ADMINISTRAR CUENTAS BANCARIAS ************
	 * @return [type] [description]
	 */
  public function cuentas()
  {
		$this->carabiner->js(array(
				array('general/msgbox.js')
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administración Cuentas Bancarias'
		);

		$this->load->model('banco_cuentas_model');
		$params['cuentas'] = $this->banco_cuentas_model->getCuentas();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/banco/cuentas/admin', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Muestra el Formulario para agregar una cuenta
	 * @return [type] [description]
	 */
	public function cuentas_agregar()
	{
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('panel/banco/cuentas_banco.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar cuenta'
		);
		
		$this->load->model('empresas_model');
		$this->load->model('banco_cuentas_model');

		$this->configAddModCuenta();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$res_mdl = $this->banco_cuentas_model->addCuenta();

			if(!$res_mdl['error'])
				redirect(base_url('panel/banco/cuentas_agregar/?'.String::getVarsLink(array('msg')).'&msg=3'));
		}

		$params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();
		$params['bancos'] = $this->banco_cuentas_model->getBancos(false);

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/banco/cuentas/agregar', $params);
		$this->load->view('panel/footer');
	}

	/*
 	|	Muestra el Formulario para modificar una cuenta
 	*/
	public function cuentas_modificar()
	{
		if (isset($_GET['id']))
		{
			$this->carabiner->css(array(
				array('libs/jquery.uniform.css', 'screen'),
				array('libs/jquery.treeview.css', 'screen')
			));
			$this->carabiner->js(array(
				array('libs/jquery.uniform.min.js'),
				array('panel/banco/cuentas_banco.js')
			));

			$this->load->model('banco_cuentas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar cuenta'
			);

			$this->configAddModCuenta('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->banco_cuentas_model->updateCuenta($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/banco/cuentas/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data'] = $this->banco_cuentas_model->getCuentaInfo();
			$params['bancos'] = $this->banco_cuentas_model->getBancos(false);

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/banco/cuentas/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/banco/cuentas/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a una cuenta
	 * @return [type] [description]
	 */
	public function cuentas_eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('banco_cuentas_model');
			$res_mdl = $this->banco_cuentas_model->updateCuenta( $this->input->get('id'), array('status' => 'e') );
			if($res_mdl)
				redirect(base_url('panel/banco/cuentas/?'.String::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/banco/cuentas/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un camion eliminado
	 * @return [type] [description]
	 */
	public function cuentas_activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('banco_cuentas_model');
			$res_mdl = $this->banco_cuentas_model->updateCuenta( $this->input->get('id'), array('status' => 'ac') );
			if($res_mdl)
				redirect(base_url('panel/banco/cuentas/?'.String::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/banco/cuentas/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	// /**
	//  * Obtiene lostado de camiones para el autocomplete, ajax
	//  */
	// public function ajax_get_lineas(){
	// 	$this->load->model('banco_cuentas_model');
	// 	$params = $this->banco_cuentas_model->getLineasAjax();

	// 	echo json_encode($params);
	// }



  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModCuenta($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'did_empresa',
						'label' => 'Empresa',
						'rules' => 'required|numeric'),
			array('field' => 'fbanco',
						'label' => 'Banco',
						'rules' => 'required|numeric'),
			array('field' => 'falias',
						'label' => 'Alias',
						'rules' => 'required|max_length[80]'),

			array('field' => 'fnumero',
						'label' => 'Numero',
						'rules' => 'max_length[20]'),
			array('field' => 'fcuenta_cpi',
						'label' => 'Cta contpaq',
						'rules' => 'max_length[12]'),
			array('field' => 'dempresa',
						'label' => 'Empresa',
						'rules' => ''),
		);

		$this->form_validation->set_rules($rules);
	}

	public function configAddDeposito(){
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'ffecha',
						'label' => 'Fecha',
						'rules' => 'required'),
			array('field' => 'fbanco',
						'label' => 'Banco',
						'rules' => 'required|numeric'),
			array('field' => 'fcuenta',
						'label' => 'Cuenta',
						'rules' => 'required|numeric'),

			array('field' => 'fmetodo_pago',
						'label' => 'Metodo de pago',
						'rules' => 'required|max_length[20]'),
			array('field' => 'freferencia',
						'label' => 'Referencia',
						'rules' => 'max_length[20]'),
			array('field' => 'fconcepto',
						'label' => 'Concepto',
						'rules' => 'required|max_length[100]'),
			array('field' => 'fmonto',
						'label' => 'Monto',
						'rules' => 'required|numeric'),

			array('field' => 'dcliente',
						'label' => 'Cliente',
						'rules' => ''),
			array('field' => 'did_cliente',
						'label' => 'Cliente',
						'rules' => ''),
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
				$txt = 'La cuenta se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'La cuenta se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'La cuenta se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'La cuenta se activó correctamente.';
				$icono = 'success';
				break;

			case 7:
				$txt = 'Se registro el depósito correctamente.';
				$icono = 'success';
				break;
			case 8:
				$txt = 'Se registro el retiro correctamente.';
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
