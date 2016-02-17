<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class proveedores extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
    'proveedores/ajax_get_proveedores/',
    'proveedores/rpt_seg_cert_pdf/',
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
			array('panel/clientes/agregar.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administración de Proveedores'
		);

		$this->load->model('empresas_model');
		$this->load->model('proveedores_model');
		$params['proveedores'] = $this->proveedores_model->getProveedores();
		$params['empresa'] = $this->empresas_model->getDefaultEmpresa();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);


// 		$gestor = @fopen("Proveedores.txt", "r");
// 		if ($gestor) {
// 		    while (($bufer = fgets($gestor, 4096)) !== false) {
// 		    	$bufer = utf8_encode($bufer);
// 		    	echo "INSERT INTO proveedores (
// nombre_fiscal, rfc, curp, cuenta_cpi)
// VALUES ('".trim(substr($bufer, 10, 101))."', '".trim(substr($bufer, 101, 21))."',
// '".trim(substr($bufer, 132, 51))."', '".trim(substr($bufer, 186, 31))."');\n";

// 		        // echo trim(substr($búfer, 10, 101))."<br>"; //nombre
// 		        // echo trim(substr($búfer, 101, 21))."<br>"; //rfc
// 		        // echo trim(substr($búfer, 132, 51))."<br>"; //curp
// 		        // echo trim(substr($búfer, 186, 31))."<br>"; //cuenta contpaqi
// 		    }
// 		    if (!feof($gestor)) {
// 		        echo "Error: fallo inesperado de fgets()\n";
// 		    }
// 		    fclose($gestor);
// 		}

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/proveedores/admin', $params);
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
			array('panel/proveedores/addmod.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Proveedor'
		);

		$this->load->model('banco_cuentas_model');

		$this->configAddModProveedor();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('proveedores_model');
			$res_mdl = $this->proveedores_model->addProveedor();

			if(!$res_mdl['error'])
				redirect(base_url('panel/proveedores/agregar/?'.String::getVarsLink(array('msg')).'&msg=3'));
		}

		//bancos
    	$params['bancos'] = $this->banco_cuentas_model->getBancos(false);

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/proveedores/agregar', $params);
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
				array('libs/jquery.numeric.js'),
				array('general/keyjump.js'),
				array('panel/proveedores/addmod.js'),
				array('panel/usuarios/add_mod_frm.js')
			));

			$this->load->model('proveedores_model');
			$this->load->model('banco_cuentas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar proveedor'
			);

			$this->configAddModProveedor('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->proveedores_model->updateProveedor($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data'] = $this->proveedores_model->getProveedorInfo();
			//Cuentas del proeveedor
    		$params['cuentas_proveedor'] = $this->proveedores_model->getCuentas($_GET['id']);
    		//bancos
    		$params['bancos'] = $this->banco_cuentas_model->getBancos(false);

      $params['editar_cuenta'] = 'readonly';
      if($this->usuarios_model->tienePrivilegioDe('', 'proveedores/editar_cuentas/'))
        $params['editar_cuenta'] = '';

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/proveedores/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un proveedor
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('proveedores_model');
			$res_mdl = $this->proveedores_model->updateProveedor( $this->input->get('id'), array('status' => 'e') );
			if($res_mdl)
				redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un proveedor eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('proveedores_model');
			$res_mdl = $this->proveedores_model->updateProveedor( $this->input->get('id'), array('status' => 'ac') );
			if($res_mdl)
				redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/proveedores/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de productores para el autocomplete, ajax
	 */
	public function ajax_get_proveedores(){
		$this->load->model('proveedores_model');
		$params = $this->proveedores_model->getProveedoresAjax();

		echo json_encode($params);
	}

  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModProveedor($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fnombre_fiscal',
						'label' => 'Nombre fiscal',
						'rules' => 'required|max_length[140]'),
			array('field' => 'fcalle',
						'label' => 'Calle',
						'rules' => 'max_length[60]'),
			array('field' => 'fno_exterior',
						'label' => 'No. exterior',
						'rules' => 'max_length[7]'),
			array('field' => 'fno_interior',
						'label' => 'No. interior',
						'rules' => 'max_length[7]'),
			array('field' => 'fcolonia',
						'label' => 'Colonia',
						'rules' => 'max_length[60]'),
			array('field' => 'flocalidad',
						'label' => 'Localidad',
						'rules' => 'max_length[45]'),
			array('field' => 'fmunicipio',
						'label' => 'Municipio',
						'rules' => 'max_length[45]'),
			array('field' => 'festado',
						'label' => 'Estado',
						'rules' => 'max_length[45]'),

			array('field' => 'fempresa',
						'label' => 'Empresa',
						'rules' => 'required'),
			array('field' => 'did_empresa',
						'label' => 'Empresa',
						'rules' => 'required'),

			array('field' => 'frfc',
						'label' => 'RFC',
						'rules' => 'min_length[12]|max_length[13]'),
			array('field' => 'fcurp',
						'label' => 'CURP',
						'rules' => 'max_length[35]'),
			array('field' => 'fcp',
						'label' => 'CP',
						'rules' => 'max_length[10]'),
			array('field' => 'ftelefono',
						'label' => 'Telefono',
						'rules' => 'max_length[15]'),
			array('field' => 'fcelular',
						'label' => 'Celular',
						'rules' => 'max_length[20]'),

			array('field' => 'femail',
						'label' => 'Email',
						'rules' => 'max_length[70]|valid_email'),
			array('field' => 'ftipo_proveedor',
						'label' => 'Tipo de proveedor',
						'rules' => 'required|max_length[2]'),
			array('field' => 'fcuenta_cpi',
						'label' => 'Cuenta ContpaqI',
						'rules' => 'max_length[12]'),

			array('field'	=> 'dregimen_fiscal',
						'label'	=> 'Régimen fiscal',
						'rules'	=> 'max_length[100]'),
			array('field'	=> 'dpass',
						'label'	=> 'Clave',
						'rules'	=> 'max_length[20]'),
			array('field'	=> 'dcfdi_version',
					'label'	=> 'Version CFDI',
					'rules'	=> 'max_length[6]'),

			array('field'	=> 'cuentas_banamex[]',
					'label'	=> 'Cuenta banamex',
					'rules'	=> 'max_length[6]'),
			array('field'	=> 'cuentas_id[]',
					'label'	=> 'Id cuenta',
					'rules'	=> 'max_length[9]'),
			array('field'	=> 'cuentas_alias[]',
					'label'	=> 'ALIAS',
					'rules'	=> 'max_length[60]'),
			array('field'	=> 'cuentas_sucursal[]',
					'label'	=> 'SUCURSAL',
					'rules'	=> 'max_length[9]'),
			array('field'	=> 'cuentas_cuenta[]',
					'label'	=> 'CUENTA/CLABE',
					'rules'	=> 'max_length[18]'),
			array('field'	=> 'fbanco[]',
					'label'	=> 'Banco',
					'rules'	=> 'max_length[5]'),

			array('field'	=> 'condicionPago',
					'label'	=> 'Condición de Pago',
					'rules'	=> 'max_length[2]'),
			array('field'	=> 'plazoCredito',
					'label'	=> 'Plazo de Crédito',
					'rules'	=> 'max_length[3]'),
		);

		$this->form_validation->set_rules($rules);
	}

  public function rpt_seg_cert()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/proveedores/rpt_seg_cert.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Ventas remisiones');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/proveedores/rpt_seg_cert',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rpt_seg_cert_pdf()
  {
    $this->load->model('proveedores_model');
    $this->proveedores_model->reporteSegCert();
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
				$txt = 'El proveedor se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El proveedor se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El proveedor se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El proveedor se activó correctamente.';
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
