<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class usuarios extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
    'usuarios/ajax_get_usuarios/',
    'usuarios/ajax_change_empresa/',
    'usuarios/ajax_get_usuario_priv/',
    'usuarios/ajax_update_priv/',
    'usuarios/copiar_privilegios/',

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
				array('general/msgbox.js')
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administración de Usuarios'
		);

		$this->load->model('usuarios_model');
		$params['usuarios'] = $this->usuarios_model->get_usuarios();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/usuarios/admin', $params);
		$this->load->view('panel/footer');
	}

	/*
 	|	Muestra el Formulario para agregar un Usuarios
 	*/
	public function agregar()
	{
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
			array('libs/jquery.treeview.css', 'screen')
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.treeview.js'),
			array('libs/jquery.numeric.js'),
			array('panel/usuarios/add_mod_frm.js')
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Usuario'
		);

		$this->config_add_usuario();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('usuarios_model');
			$res_mdl = $this->usuarios_model->setRegistro();

			if(!$res_mdl['error'])
				redirect(base_url('panel/usuarios/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
		}

		$this->load->model('usuarios_puestos_model');
		$params['puestos'] = $this->usuarios_puestos_model->getPuestos(false);

    $this->load->model('empresas_model');
    $params['empresas'] = $this->empresas_model->getEmpresas(150);
    $empresa_default = $this->empresas_model->getDefaultEmpresa();
    $_POST['idEmpresa'] = $empresa_default->id_empresa;


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/usuarios/agregar', $params);
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
        array('general/supermodal.js'),
        array('general/msgbox.js'),
				array('panel/usuarios/add_mod_frm.js'),
			));

			$this->load->model('usuarios_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar usuario'
			);

			$this->config_add_usuario('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->usuarios_model->modificar_usuario($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/usuarios/?'.MyString::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

      $this->load->model('empresas_model');
      $params['empresas'] = $this->empresas_model->getEmpresas(150);
      if (!isset($_POST['idEmpresa'])) {
        $empresa_default = $this->empresas_model->getDefaultEmpresa();
        $_POST['idEmpresa'] = $empresa_default->id_empresa;
      }

			$params['data'] = $this->usuarios_model->get_usuario_info(false, false, $_POST['idEmpresa']);
			$this->load->model('usuarios_puestos_model');
			$params['puestos'] = $this->usuarios_puestos_model->getPuestos(false);

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/usuarios/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/usuarios/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/*
 	|	Elimina un usuarios
 	*/
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('usuarios_model');
			$res_mdl = $this->usuarios_model->eliminar_usuario($this->input->get('id'));
			if($res_mdl)
				redirect(base_url('panel/usuarios/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/usuarios/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/*
 	|	Activa un articulo
 	*/
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('usuarios_model');
			$res_mdl = $this->usuarios_model->activar_usuario($this->input->get('id'));
			if($res_mdl)
				redirect(base_url('panel/usuarios/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/usuarios/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

   public function ajax_get_usuarios()
   {
      $this->load->model('usuarios_model');
      echo json_encode($this->usuarios_model->getUsuariosAjax());
   }

   public function ajax_change_empresa()
   {
      $this->load->model('usuarios_model');
      echo json_encode($this->usuarios_model->changeEmpresaSel($_POST['empresa']));
   }

  public function ajax_get_usuario_priv()
  {
    $this->load->model('usuarios_model');
    $data = $this->usuarios_model->get_usuario_info($_GET['id_usuario'], false, $_GET['id_empresa']);
    $privilegios = isset($data['privilegios']) ? $data['privilegios']: [];
    echo $this->usuarios_model->getFrmPrivilegios(0, true, $privilegios);
  }

  public function ajax_update_priv()
  {
    $this->load->model('usuarios_model');
    $this->usuarios_model->updatePrivilegios($this->input->post('dprivilegios'), $this->input->post('id_usuario'), $this->input->post('id_empresa'));
  }

  public function copiar_privilegios()
  {
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      array('general/keyjump.js'),
      array('general/msgbox.js'),
      array('panel/compras_ordenes/agregar.js'),
      array('panel/compras/ligar_ordenes.js'),
    ));

    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    $this->configCopyPrivilegios();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->usuarios_model->copiarPrivilegios($_POST);

      if ($res_mdl['passes'])
      {
        $params['frm_errors'] = $this->showMsgs(9);
        $params['id_movimiento'] = (isset($res_mdl['ver_cheque']) ? $res_mdl['id_movimiento'] : '');
        $params['reload'] = true;
      }
      else
      {
        $params['frm_errors'] = $this->showMsgs($res_mdl['msg']);
      }
      // if ($res_mdl['passes'])
      // {
      //   redirect(base_url('panel/compras_ordenes/ligar/?'.MyString::getVarsLink(array('msg')).'&msg=9&rel=t'));
      // }
    }

    $params['empresas'] = $this->empresas_model->getEmpresas(500);
    foreach ($params['empresas']['empresas'] as $key => $value) {
      if ($value->id_empresa == $_GET['ide']) {
        unset($params['empresas']['empresas'][$key]);
      }
    }
    // echo "<pre>";
    // var_dump($params);
    // echo "</pre>";exit;
    $params['fecha'] = str_replace(' ', 'T', date("Y-m-d H:i"));

    // $ids = explode(',', $_GET['ids']);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    if (isset($_GET['rel']))
      $params['reload'] = true;

    $this->load->view('panel/empleados/copiar_privilegios', $params);
  }


  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function config_add_usuario($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
							array('field' => 'fnombre',
										'label' => 'Nombre',
										'rules' => 'required|max_length[90]|max_length[90]'),
							array('field' => 'fapellido_paterno',
										'label' => 'Apellido paterno',
										'rules' => 'max_length[25]'),
							array('field' => 'fapellido_materno',
										'label' => 'Apellido materno',
										'rules' => 'max_length[25]'),

							array('field' => 'fcalle',
										'label' => 'Calle',
										'rules' => 'max_length[60]'),
							array('field' => 'fnumero',
										'label' => 'Numero',
										'rules' => 'max_length[7]'),
							array('field' => 'fcolonia',
										'label' => 'Colonia',
										'rules' => 'max_length[60]'),
							array('field' => 'fmunicipio',
										'label' => 'Municipio',
										'rules' => 'max_length[45]'),
							array('field' => 'festado',
										'label' => 'Estado',
										'rules' => 'max_length[45]'),
							array('field' => 'fcp',
										'label' => 'Codigo postal',
										'rules' => 'max_length[12]'),

							array('field' => 'dprivilegios[]',
										'label' => 'Privilegios',
										'rules' => 'is_natural_no_zero'),

							array('field' => 'ffecha_nacimiento',
										'label' => 'Fecha de nacimiento',
										'rules' => 'max_length[25]'),
							array('field' => 'ffecha_entrada',
										'label' => 'Fecha de entrada',
										'rules' => 'max_length[25]'),
              array('field' => 'ffecha_imss',
                    'label' => 'Fecha IMSS',
                    'rules' => 'max_length[25]'),
							array('field' => 'ffecha_salida',
										'label' => 'Fecha de salida',
										'rules' => 'max_length[25]'),
							array('field' => 'fnacionalidad',
										'label' => 'Nacionalidad',
										'rules' => 'max_length[20]'),
							array('field' => 'festado_civil',
										'label' => 'Estado civil',
										'rules' => 'max_length[15]'),
							array('field' => 'fsexo',
										'label' => 'Sexo',
										'rules' => 'max_length[1]'),
							array('field' => 'femail',
										'label' => 'Email',
										'rules' => 'max_length[70]'),
							array('field' => 'fcuenta_cpi',
										'label' => 'Cuenta contpaqi',
										'rules' => 'max_length[12]'),

							array('field' => 'did_empresa',
										'label' => 'Empresa',
										'rules' => 'required|numeric'),
							array('field' => 'fempresa',
										'label' => 'Empresa',
										'rules' => 'required'),
							array('field' => 'finfonavit',
										'label' => 'Infonavit',
										'rules' => 'numeric'),
							array('field' => 'festa_asegurado',
										'label' => 'Asegurado',
										'rules' => ''),
		);

		if ($accion == 'agregar')
		{
      $rules[] = array('field' => 'fnombre',
                      'label' => 'Nombre',
                      'rules' => 'required|max_length[90]|max_length[90]|callback_valida_nombre_full');
      $rules[] = array('field' => 'frfc',
                      'label' => 'RFC',
                      'rules' => 'is_unique[usuarios.rfc]');
			$rules[] = 	array('field' => 'fpass',
												'label' => 'Password',
												'rules' => 'max_length[32]');
			$rules[] = 	array('field' => 'fusuario',
												'label' => 'Usuario',
												'rules' => 'max_length[30]|is_unique[usuarios.usuario]');
		}
		else
		{
			$rules[] = 	array('field' => 'fpass',
								'label' => 'Password',
								'rules' => 'max_length[32]');
			$rules[] = 	array('field' => 'fusuario',
								'label' => 'Usuario',
								'rules' => 'max_length[30]|callback_valida_email');
		}

		if (isset($_POST['festa_asegurado']))
		{
			$rules[] = array('field' => 'fcurp',
							'label' => 'CURP',
							'rules' => 'required|max_length[30]');
			$rules[11] = array('field' => 'ffecha_entrada',
							'label' => 'Fecha de entrada',
							'rules' => 'required|max_length[25]');
			$rules[] = array('field' => 'fsalario_diario',
							'label' => 'Salario diario',
							'rules' => 'required|numeric');
			$rules[] = array('field' => 'fsalario_diario_real',
							'label' => 'Salario diario real',
							'rules' => 'required|numeric');
			$rules[] = array('field' => 'fregimen_contratacion',
							'label' => 'Regimen contratacion',
							'rules' => 'required|numeric');
		}else{
			$rules[] = array('field' => 'fcurp',
							'label' => 'CURP',
							'rules' => 'max_length[30]');
			$rules[] = array('field' => 'fsalario_diario',
							'label' => 'Salario diario',
							'rules' => 'numeric');
			$rules[] = array('field' => 'fsalario_diario_real',
							'label' => 'Salario diario real',
							'rules' => 'numeric');
			$rules[] = array('field' => 'fregimen_contratacion',
							'label' => 'Regimen contratacion',
							'rules' => 'numeric');
		}

		$this->form_validation->set_rules($rules);
	}


	public function valida_email($email)
	{
		if(trim($email) != '')
			if ($this->usuarios_model->valida_email('usuarios', array('id !='=>$_GET['id'], 'usuario'=>$email))) {
				$this->form_validation->set_message('valida_email', 'El %s no esta disponible, intenta con otro.');
				return FALSE;
			}
		return TRUE;
	}

  public function valida_nombre_full()
  {
    $query = $this->db->query("SELECT id
                               FROM usuarios
                               WHERE lower(nombre) = '".mb_strtolower(trim($_POST['fnombre']))."' AND
                                     lower(apellido_paterno) = '".mb_strtolower(trim($_POST['fapellido_paterno']))."' AND
                                     lower(apellido_materno) = '".mb_strtolower(trim($_POST['fapellido_materno']))."'");

    if ($query->num_rows() > 0)
    {
      $this->form_validation->set_message('valida_nombre_full', 'Ya existe un empleado con el nombre y apellidos especificado.');
      return false;
    }

    return true;
  }

  public function configCopyPrivilegios()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'empresaId',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'usuarioId',
            'label' => 'Usuario',
            'rules' => 'required'),

      array('field' => 'id_empresas[]',
            'label' => 'Empresas a copiar',
            'rules' => 'required')
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
				$txt = 'El usuario se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El usuario se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El usuario se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El usuario se activó correctamente.';
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
