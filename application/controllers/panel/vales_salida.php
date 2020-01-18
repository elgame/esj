<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class vales_salida extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('clientes/ajax_get_proveedores/',
		'vales_salida/imprimir/',
		'clientes/catalogo_xls/');

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
			'titulo' => 'Control de acceso'
		);

		$this->load->model('control_acceso_model');
		$params['control_acceso'] = $this->control_acceso_model->getControl();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/control_acceso/admin', $params);
		$this->load->view('panel/footer');
	}

	public function imprimir()
  {
    $this->load->model('vales_salida_model');
    if($this->input->get('p') == 'true')
      $this->vales_salida_model->imprimir($this->input->get('id'));
    else
      $this->load->view('panel/vales_salida/print');
  }

	// /**
	//  * Muestra el Formulario para agregar un proveedor
	//  * @return [type] [description]
	//  */
	// public function entrada_salida()
	// {
	// 	$this->carabiner->css(array(
	// 		array('libs/jquery.uniform.css', 'screen'),
	// 	));
	// 	$this->carabiner->js(array(
 //      	array('libs/jquery.uniform.min.js'),
	// 		array('panel/otros/control_acceso.js'),
	// 	));

	// 	$params['info_empleado'] = $this->info_empleado['info']; //info empleado
	// 	$params['seo'] = array(
	// 		'titulo' => 'Registro de entrada y salidas'
	// 	);

	// 	$this->load->model('control_acceso_model');

	// 	$tipo = 'entrada';
	// 	if ($this->input->get('placas') != '' || $this->input->get('id') > 0) {
	// 		if (isset($params['data']->id_control)) {
	// 			$params['data'] = $this->control_acceso_model->getControlInfo(false, $this->input->get('placas'));
	// 			$tipo = 'salida';
	// 		}
	// 		if ($this->input->get('id') > 0) {
	// 			$params['data'] = $this->control_acceso_model->getControlInfo($this->input->get('id'));
	// 			$tipo = 'update';
	// 		}
	// 	}

	// 	$params['tipo'] = isset($_POST['tipo'])? $_POST['tipo']: $tipo;

	// 	$params['readonly'] = $params['readonly_v'] = '';
	// 	if ($params['tipo'] == 'salida') {
	// 		$params['readonly'] = 'readonly';
	// 		if ($params['data']->id_vale_salida > 0)
	// 			$params['readonly_v'] = 'readonly';
	// 	}
	// 	// elseif ($params['tipo'] == 'update') {
	// 	// 	# code...
	// 	// }

	// 	$this->configAddModControl($params['tipo']);
	// 	if ($this->form_validation->run() == FALSE)
	// 	{
	// 		$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
	// 	}
	// 	else
	// 	{
	// 		if ($params['tipo'] == 'entrada') {
	// 			$res_mdl = $this->control_acceso_model->addEntrada();
	// 			$msg = 3;
	// 		} elseif ($params['tipo'] == 'salida') {
	// 			$res_mdl = $this->control_acceso_model->addSalida($this->input->post('id_control'));
	// 			$msg = 4;
	// 		} elseif ($params['tipo'] == 'update') {
	// 			$res_mdl = $this->control_acceso_model->updateRegistro($this->input->post('id_control'));
	// 			$msg = 4;
	// 		}

	// 		if(!$res_mdl['error'])
	// 			redirect(base_url('panel/control_acceso/entrada_salida/?'.MyString::getVarsLink(array('msg')).'&msg='.$msg));
	// 	}

	// 	if (isset($_GET['msg']))
	// 		$params['frm_errors'] = $this->showMsgs($_GET['msg']);

	// 	$this->load->view('panel/header', $params);
	// 	$this->load->view('panel/general/menu', $params);
	// 	$this->load->view('panel/control_acceso/entrada_salida', $params);
	// 	$this->load->view('panel/footer');
	// }

	// /*
 // 	|	Muestra el Formulario para modificar un usuario
 // 	*/
	// public function modificar()
	// {
	// 	if (isset($_GET['id']))
	// 	{
	// 		$this->carabiner->css(array(
	// 			array('libs/jquery.uniform.css', 'screen'),
	// 			array('libs/jquery.treeview.css', 'screen')
	// 		));
	// 		$this->carabiner->js(array(
	// 			array('libs/jquery.uniform.min.js'),
	// 			array('libs/jquery.treeview.js'),
	// 			array('panel/usuarios/add_mod_frm.js')
	// 		));

 //      $this->load->model('clientes_model');
	// 		$this->load->model('empresas_model');

	// 		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
	// 		$params['seo'] = array(
	// 			'titulo' => 'Modificar cliente'
	// 		);

	// 		$this->configAddModControl('modificar');
	// 		if ($this->form_validation->run() == FALSE)
	// 		{
	// 			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
	// 		}
	// 		else
	// 		{
	// 			$res_mdl = $this->clientes_model->updateCliente($this->input->get('id'));

	// 			if($res_mdl['error'] == FALSE)
	// 				redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg', 'id')).'&msg=4'));
	// 		}

	// 		$params['cliente'] = $this->clientes_model->getClienteInfo();

	// 		$this->load->model('documentos_model');
	// 		$params['documentos'] = $this->documentos_model->getDocumentos();
 //      $params['empresa']       = $this->empresas_model->getInfoEmpresa($params['cliente']['info']->id_empresa);

	// 		if (isset($_GET['msg']))
	// 			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

	// 		$this->load->view('panel/header', $params);
	// 		$this->load->view('panel/general/menu', $params);
	// 		$this->load->view('panel/clientes/modificar', $params);
	// 		$this->load->view('panel/footer');
	// 	}
	// 	else
	// 		redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	// }

	// /**
	//  * pone eliminado a un proveedor
	//  * @return [type] [description]
	//  */
	// public function eliminar()
	// {
	// 	if (isset($_GET['id']))
	// 	{
	// 		$this->load->model('clientes_model');
	// 		$res_mdl = $this->clientes_model->updateCliente( $this->input->get('id'), array('status' => 'e') );
	// 		if($res_mdl)
	// 			redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
	// 	}
	// 	else
	// 		redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	// }

	// /**
	//  * Activa un proveedor eliminado
	//  * @return [type] [description]
	//  */
	// public function activar()
	// {
	// 	if (isset($_GET['id']))
	// 	{
	// 		$this->load->model('clientes_model');
	// 		$res_mdl = $this->clientes_model->updateCliente( $this->input->get('id'), array('status' => 'ac') );
	// 		if($res_mdl)
	// 			redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
	// 	}
	// 	else
	// 		redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	// }

	// /**
	//  * Obtiene lostado de productores para el autocomplete, ajax
	//  */
	// public function ajax_get_proveedores(){
	// 	$this->load->model('clientes_model');
	// 	$params = $this->clientes_model->getClientesAjax();

	// 	echo json_encode($params);
	// }

	// public function catalogo_xls()
	// {
	// 	$this->load->model('clientes_model');
	// 	$this->clientes_model->catalogo_xls();
	// }



 //  /*
 // 	|	Asigna las reglas para validar un articulo al agregarlo
 // 	*/
	// public function configAddModControl($accion='entrada')
	// {
	// 	$this->load->library('form_validation');
	// 		$rules = array(
	// 			array('field' => 'id_usaurio_ent',
	// 						'label' => 'Registro entrada',
	// 						'rules' => 'required|is_natural'),
	// 			array('field' => 'nombre',
	// 						'label' => 'Nombre',
	// 						'rules' => 'required|max_length[200]'),
	// 			array('field' => 'asunto',
	// 						'label' => 'Asunto',
	// 						'rules' => 'required|max_length[200]'),
	// 			array('field' => 'departamento',
	// 						'label' => 'Departamento',
	// 						'rules' => 'required|max_length[60]'),
	// 			array('field' => 'placas',
	// 						'label' => 'Placas',
	// 						'rules' => 'required|max_length[15]'),
	// 		);
	// 	if ($accion == 'salida' || $accion == 'update') {
	// 		$rules = array(
	// 			array('field' => 'id_usaurio_ent',
	// 						'label' => 'Registro entrada',
	// 						'rules' => 'required|is_natural'),
	// 			array('field' => 'id_usaurio_ent',
	// 						'label' => 'Registro salida',
	// 						'rules' => 'required|is_natural'),
	// 			array('field' => 'id_vale_salida',
	// 						'label' => 'Vale de salida',
	// 						'rules' => 'is_natural'),
	// 			array('field' => 'nombre',
	// 						'label' => 'Nombre',
	// 						'rules' => 'required|max_length[200]'),
	// 			array('field' => 'asunto',
	// 						'label' => 'Asunto',
	// 						'rules' => 'required|max_length[200]'),
	// 			array('field' => 'departamento',
	// 						'label' => 'Departamento',
	// 						'rules' => 'required|max_length[60]'),
	// 			array('field' => 'placas',
	// 						'label' => 'Placas',
	// 						'rules' => 'required|max_length[15]'),
	// 		);
	// 	}

	// 	$this->form_validation->set_rules($rules);
	// }


	// private function showMsgs($tipo, $msg='', $title='Usuarios')
	// {
	// 	switch($tipo){
	// 		case 1:
	// 			$txt = 'El campo ID es requerido.';
	// 			$icono = 'error';
	// 			break;
	// 		case 2: //Cuendo se valida con form_validation
	// 			$txt = $msg;
	// 			$icono = 'error';
	// 			break;
	// 		case 3:
	// 			$txt = 'Se agregó correctamente.';
	// 			$icono = 'success';
	// 			break;
	// 		case 4:
	// 			$txt = 'Se modificó correctamente.';
	// 			$icono = 'success';
	// 			break;
	// 		case 5:
	// 			$txt = 'Se eliminó correctamente.';
	// 			$icono = 'success';
	// 			break;
	// 		case 6:
	// 			$txt = 'Se activó correctamente.';
	// 			$icono = 'success';
	// 			break;
	// 	}

	// 	return array(
	// 			'title' => $title,
	// 			'msg' => $txt,
	// 			'ico' => $icono);
	// }
}



/* End of file usuarios.php */
/* Location: ./application/controllers/panel/usuarios.php */
