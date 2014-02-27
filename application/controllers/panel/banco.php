<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
		'banco/get_cuentas_banco/',
		'banco/cambia_entransito/',
		'banco/conciliacion/',
		'banco/cheque/',
		'banco/cuenta_banamex/',

		'banco/cuenta_pdf/',
		'banco/cuenta_xls/',

		'banco/saldos_pdf/',
		'banco/saldos_xls/',
		);

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
			array('general/msgbox.js'),
			array('panel/banco/cuentas_banco.js')
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Saldos Cuentas Bancarias'
		);

		$this->load->model('banco_cuentas_model');
		$this->load->model('empresas_model');
		$params['data']    = $this->banco_cuentas_model->getSaldosCuentasData();
		$params['empresa'] = $this->empresas_model->getDefaultEmpresa();
		$params['bancos']  = $this->banco_cuentas_model->getBancos(false);

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/banco/saldos_cuentas', $params);
		$this->load->view('panel/footer');
	}

	public function saldos_pdf()
	{
		$this->load->model('banco_cuentas_model');
    	$this->banco_cuentas_model->saldosCuentasPdf();
	}

	public function saldos_xls()
	{
		$this->load->model('banco_cuentas_model');
    	$this->banco_cuentas_model->saldosCuentasExcel();
	}


	public function cuenta()
	{
		$this->carabiner->js(array(
			array('general/msgbox.js'),
			array('general/util.js'),
			array('panel/banco/cuentas_banco.js'),
			array('panel/banco/saldo_cuenta.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Saldo Cuenta'
		);

		$this->load->model('banco_cuentas_model');
		$this->load->model('empresas_model');
		$params['data']    = $this->banco_cuentas_model->getSaldoCuentaData();
		$params['empresa'] = $this->empresas_model->getDefaultEmpresa();
		$params['bancos']  = $this->banco_cuentas_model->getBancos(false);

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/banco/saldo_cuenta', $params);
		$this->load->view('panel/footer');
	}

	public function cuenta_pdf()
	{
		$this->load->model('banco_cuentas_model');
    	$this->banco_cuentas_model->getSaldoCuentaPdf();
	}

	public function cuenta_xls()
	{
		$this->load->model('banco_cuentas_model');
    	$this->banco_cuentas_model->getSaldoCuentaExcel();
	}

	public function conciliacion()
	{
		$this->load->model('banco_cuentas_model');
    	$this->banco_cuentas_model->showConciliacion();
	}

	public function cuenta_banamex()
	{
		$this->load->model('banco_layout_model');
    	$this->banco_layout_model->get();
	}


	/**
	 * **********************************************
	 * ********* MOVIMIENTOS DE CUENTAS *************
	 * ********* DEPOSITOS Y RETIROS ****************
	 * @return [type] [description]
	 */
	public function depositar()
	{
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
			'titulo' => 'Agregar retiro'
		);
		
		$this->load->model('banco_cuentas_model');

		$this->configAddDeposito();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$res_mdl = $this->banco_cuentas_model->addRetiro();

			if(!$res_mdl['error'])
				redirect(base_url('panel/banco/retirar/?'.String::getVarsLink(array('msg')).'&msg=8'.
						($res_mdl['ver_cheque'] ? "&id_movimiento={$res_mdl['id_movimiento']}" : '') ));
			else
				redirect(base_url('panel/banco/retirar/?'.String::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
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
		$this->load->view('panel/banco/movimientos/retiros', $params);
		$this->load->view('panel/footer');
	}

	public function cheque()
	{
		if(isset($_GET['id']{0}))
		{
			$this->load->model('banco_cuentas_model');
			$this->banco_cuentas_model->generaCheque($_GET['id']);
		}else
			redirect(base_url('panel/banco?msg=1'));
	}

	public function get_cuentas_banco(){
		$response = array('cuentas' => array());
		if (isset($_GET['id_banco']{0})) {
			$this->load->model('banco_cuentas_model');
			$response = $this->banco_cuentas_model->getCuentas(false);
		}
		echo json_encode($response);
	}

	public function eliminar_movimiento(){
		if (isset($_GET['id_movimiento']{0}))
		{
			$this->load->model('banco_cuentas_model');
			$response = $this->banco_cuentas_model->deleteMovimiento($_GET['id_movimiento']);
			redirect(base_url('panel/banco/cuenta/?'.String::getVarsLink(array('msg', 'id_movimiento')).'&msg=10'));
		}else
			redirect(base_url('panel/banco/cuenta/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	public function cancelar_movimiento(){
		if (isset($_GET['id_movimiento']{0}))
		{
			$this->load->model('banco_cuentas_model');
			$response = $this->banco_cuentas_model->deleteMovimiento($_GET['id_movimiento'], true);
			redirect(base_url('panel/banco/cuenta/?'.String::getVarsLink(array('msg', 'id_movimiento')).'&msg=9'));
		}else
			redirect(base_url('panel/banco/cuenta/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	public function cambia_entransito(){
		if (isset($_GET['id_movimiento']{0}))
		{
			$this->load->model('banco_cuentas_model');
			$response = $this->banco_cuentas_model->updateMovimiento($_GET['id_movimiento'], 
				array('entransito' => ($this->input->get('mstatus')=='Trans'? 'f' : 't') ));
			redirect(base_url('panel/banco/cuenta/?'.String::getVarsLink(array('msg', 'id_movimiento', 'mstatus')).'&msg=11'));
		}else
			redirect(base_url('panel/banco/cuenta/?'.String::getVarsLink(array('msg', 'id_movimiento', 'mstatus')).'&msg=1'));
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
			case 9:
				$txt = 'Se cancelo la operacion correctamente.';
				$icono = 'success';
				break;
			case 10:
				$txt = 'Se elimino la operacion correctamente.';
				$icono = 'success';
				break;
			case 11:
				$txt = 'La operacion cambio de estado correctamente.';
				$icono = 'success';
				break;
			case 30:
				$txt = 'La cuenta no tiene saldo suficiente.';
				$icono = 'error';
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
