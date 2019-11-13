<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class contrarecibo extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
    'contrarecibo/ajax_get_proveedores/',
    'contrarecibo/rpt_seg_cert_pdf/',
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
			array('panel/compras_ordenes/admin.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administración de Contrarecibos'
		);

		$this->load->model('empresas_model');
		$this->load->model('contrarecibo_model');
		$params['contrarecibos'] = $this->contrarecibo_model->getContrarecibos();
		$params['empresa']       = $this->empresas_model->getDefaultEmpresa();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/contrarecibos/admin', $params);
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
			array('libs/jquery.numeric.js'),
			array('general/keyjump.js'),
			array('panel/contrarecibos/addmod.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Contrarecibo'
		);

		$this->load->model('contrarecibo_model');

		$this->configAddModContrarecibo();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$res_mdl = $this->contrarecibo_model->addContrarecibo();

			if(!$res_mdl['error'])
				redirect(base_url('panel/contrarecibo/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
		}

		$params['folio']         = $this->contrarecibo_model->getFolio();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/contrarecibos/agregar', $params);
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
				array('libs/jquery.numeric.js'),
				array('general/keyjump.js'),
				array('panel/contrarecibos/addmod.js'),
			));

			$this->load->model('contrarecibo_model');
			$this->load->model('banco_cuentas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar proveedor'
			);

			$this->configAddModContrarecibo();
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->contrarecibo_model->updateContrarecibo($this->input->get('id'));

				if(!$res_mdl['error'])
					redirect(base_url('panel/contrarecibo/?'.MyString::getVarsLink(array('msg')).'&msg=4'));
			}

			$params['data'] = $this->contrarecibo_model->getContrareciboInfo();
			//Cuentas del proeveedor
    	$params['facturas_contrarecibo'] = $this->contrarecibo_model->getFacturas($_GET['id']);

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/contrarecibos/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/contrarecibo/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un proveedor
	 * @return [type] [description]
	 */
	public function cancelar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('contrarecibo_model');
			$res_mdl = $this->contrarecibo_model->updateContrarecibo( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/contrarecibo/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/contrarecibo/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un proveedor eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('contrarecibo_model');
			$res_mdl = $this->contrarecibo_model->updateProveedor( $this->input->get('id'), array('status' => 'ac') );
			if($res_mdl)
				redirect(base_url('panel/proveedores/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/proveedores/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	public function imprimir()
  {
    $this->load->model('contrarecibo_model');

    if (isset($_GET['id']))
    {
      $this->contrarecibo_model->imprimirContrarecibo($_GET['id']);
    }
  }

  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModContrarecibo($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fempresa',
						'label' => 'Empresa',
						'rules' => 'required'),
			array('field' => 'did_empresa',
						'label' => 'Empresa',
						'rules' => 'required'),
			array('field' => 'fproveedor',
						'label' => 'Proveedor',
						'rules' => 'required'),
			array('field' => 'did_proveedor',
						'label' => 'Proveedor',
						'rules' => 'required'),

			array('field' => 'ffolio',
            'label' => 'Folio',
            'rules' => 'required'),
      array('field' => 'ffecha1',
            'label' => 'Fecha',
            'rules' => 'required'),
      array('field' => 'dtotal',
            'label' => 'Total',
            'rules' => 'required'),

			array('field'	=> 'facturas_folio[]',
						'label'	=> 'Facturas Folio',
						'rules'	=> 'max_length[15]'),
			array('field'	=> 'facturas_fecha[]',
						'label'	=> 'Facturas Fecha',
						'rules'	=> 'max_length[20]'),
			array('field'	=> 'facturas_importe[]',
						'label'	=> 'Facturas Importe',
						'rules'	=> ''),
			array('field'	=> 'facturas_observacion[]',
						'label'	=> 'Facturas Observacion',
						'rules'	=> 'max_length[200]'),
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
				$txt = 'El contrarecibo se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El contrarecibo se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El contrarecibo se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El contrarecibo se activó correctamente.';
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
