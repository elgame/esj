<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class cuentas_cpi extends MY_Controller {
	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('cuentas_cpi/ajax_get_cuentas/');

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

	/**
	 * Default. Mustra el listado de cuentas para administrarlos
	 */
	public function index(){
		$this->carabiner->js(array(
				array('general/msgbox.js'),
				array('panel/empresas/cuentas.js'),
		));

		$this->load->library('pagination');
		$this->load->model('cuentas_cpi_model');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administrar cuentas'
		);

		$params['cuentas'] = $this->cuentas_cpi_model->obtenCuentas();

		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/empresas/cuentas/listado', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Agrega un cuenta a la bd
	 */
	public function agregar(){
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
			array('libs/jquery.treeview.css', 'screen')
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.treeview.js'),
			array('panel/empresas/cuentas.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar cuenta'
		);

		$this->load->model('cuentas_cpi_model');
		$this->configAddModCuenta();

		if($this->form_validation->run() == FALSE){
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}else{
			$respons = $this->cuentas_cpi_model->addCuenta();

			if($respons[0])
				redirect(base_url('panel/cuentas_cpi/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=4'));
		}

		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/empresas/cuentas/agregar', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Modificar cuenta
	 */
	public function modificar(){
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
			array('libs/jquery.treeview.css', 'screen')
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.treeview.js')
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Modificar cuenta'
		);

		if(isset($_GET['id']{0})){
			$this->load->model('cuentas_cpi_model');
			$this->configAddModCuenta();

			if($this->form_validation->run() == FALSE){
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}else{
				$respons = $this->cuentas_cpi_model->updateCuenta($_GET['id']);

				if($respons[0])
					redirect(base_url('panel/cuentas_cpi/?'.MyString::getVarsLink(array('msg', 'id')).'&msg=3'));
			}

			$params['cuenta'] = $this->cuentas_cpi_model->getCuentaInfo(array('id_cuenta' => $_GET['id']));

			$params['cuentas'] = $this->cuentas_cpi_model->getArbolCuenta($params['cuenta']['info']->id_empresa, 'NULL', true,
																												(isset($params['cuenta']['info']->id_padre)? $params['cuenta']['info']->id_padre: 'radio'), true);

			if(isset($_GET['msg']{0}))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);
		}else
			$params['frm_errors'] = $this->showMsgs(1);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/empresas/cuentas/modificar', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Elimina un privilegio de la bd
	 */
	public function eliminar(){
		if(isset($_GET['id']{0})){
			$this->load->model('cuentas_cpi_model');
			$respons = $this->cuentas_cpi_model->deleteCuenta($_GET['id']);

			if($respons[0])
				redirect(base_url('panel/cuentas_cpi/?msg=5'));
		}else
			$params['frm_errors'] = $this->showMsgs(1);
	}

	public function ajax_get_cuentas()
	{
		$this->load->model('cuentas_cpi_model');
		if(isset($_GET['id_empresa']))
			echo $this->cuentas_cpi_model->getArbolCuenta($_GET['id_empresa'], 'NULL', true, 'radio', true);
	}


	/**
	 * Configura los metodos de agregar y modificar
	 */
	private function configAddModCuenta(){
		$this->load->library('form_validation');
		$rules = array(
			array('field'	=> 'dempresa',
					'label'		=> 'Empresa',
					'rules'		=> 'required'),
			array('field'	=> 'did_empresa',
					'label'		=> 'Empresa',
					'rules'		=> 'required'),
			array('field'	=> 'dnombre',
					'label'		=> 'Nombre',
					'rules'		=> 'required|max_length[100]'),
			array('field'	=> 'dcuenta',
					'label'		=> 'No Cuenta',
					'rules'		=> 'required|max_length[10]|callback_valida_cuenta'),
			array('field'	=> 'dcuenta_padre',
					'label'		=> 'Cuenta padre',
					'rules'		=> 'required'),
      array('field' => 'dtipo_cuenta',
          'label'   => 'Asignar a',
          'rules'   => ''),
		);
		$this->form_validation->set_rules($rules);
	}

	public function valida_cuenta($cuenta)
  {
  	$sql = isset($_GET['id'])? " AND id_cuenta <> ".$_GET['id']: '';
    $query = $this->db->query("SELECT id_cuenta
                               FROM cuentas_contpaq
                               WHERE status = 't' AND id_empresa = ".$this->input->post('did_empresa')."
                               			AND lower(cuenta) = '".mb_strtolower(trim($cuenta))."'".$sql);
    if ($query->num_rows() > 0)
    {
      $this->form_validation->set_message('valida_cuenta', 'Ya existe el No de cuenta');
      return false;
    }
    return true;
  }

	/**
	 * Muestra mensajes cuando se realiza alguna accion
	 * @param unknown_type $tipo
	 * @param unknown_type $msg
	 * @param unknown_type $title
	 */
	private function showMsgs($tipo, $msg='', $title='Privilegio!'){
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
				$txt = 'La cuenta se modifico correctamente.';
				$icono = 'success';
			break;
			case 4:
				$txt = 'La cuenta se agrego correctamente.';
				$icono = 'success';
			break;
			case 5:
				$txt = 'La cuenta se elimino correctamente.';
				$icono = 'success';
			break;
		}

		return array(
			'title' => $title,
			'msg' => $txt,
			'ico' => $icono);
	}
}

?>