<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class empleados extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
    'empleados/ajax_get_usuarios/',
    'empleados/ajax_get_depa_pues/',
    'empleados/ajax_get_usuarios2/',
    'empleados/persep_deduc_pdf/',
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
			'titulo' => 'Administración de Usuarios'
		);

		$this->load->model('usuarios_model');

    // Obtiene los datos de la empresa predeterminada.
    $this->load->model('empresas_model');
    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    if(!$this->input->get('did_empresa'))
      $_GET['did_empresa'] = $params['empresa']->id_empresa;

		$params['usuarios'] = $this->usuarios_model->get_usuarios(true, 't');

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/empleados/admin', $params);
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
			'titulo' => 'Agregar Empleado'
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
				redirect(base_url('panel/empleados/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
		}

		$this->load->model('usuarios_puestos_model');
		$this->load->model('usuarios_departamentos_model');
		$this->load->model('empresas_model');
    $this->load->model('nomina_catalogos_model');

		$params['empresa']       = $this->empresas_model->getDefaultEmpresa();
		$_GET['did_empresa']     = $params['empresa']->id_empresa;
		$params['puestos']       = $this->usuarios_puestos_model->getPuestos(false);
		$params['departamentos'] = $this->usuarios_departamentos_model->getPuestos(false);

    $params['tipo_contratos'] = $this->nomina_catalogos_model->tipo('tc');
    $params['tipo_regimens']  = $this->nomina_catalogos_model->tipo('rc');
    $params['tipo_jornadas']  = $this->nomina_catalogos_model->tipo('tj');
    $params['riesgo_puestos'] = $this->nomina_catalogos_model->tipo('rp');

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/empleados/agregar', $params);
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
				array('panel/usuarios/add_mod_frm.js')
			));

			$this->load->model('usuarios_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar Empleados'
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
					redirect(base_url('panel/empleados/?'.MyString::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$this->load->model('usuarios_puestos_model');
      $this->load->model('usuarios_departamentos_model');
			$this->load->model('nomina_catalogos_model');

			$params['data'] = $this->usuarios_model->get_usuario_info();
			$_GET['did_empresa']     = $params['data']['info'][0]->id_empresa;
			$params['puestos']       = $this->usuarios_puestos_model->getPuestos(false);
      $params['departamentos'] = $this->usuarios_departamentos_model->getPuestos(false);

      $params['tipo_contratos'] = $this->nomina_catalogos_model->tipo('tc');
      $params['tipo_regimens']  = $this->nomina_catalogos_model->tipo('rc');
      $params['tipo_jornadas']  = $this->nomina_catalogos_model->tipo('tj');
      $params['riesgo_puestos'] = $this->nomina_catalogos_model->tipo('rp');

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/empleados/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/empleados/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
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
				redirect(base_url('panel/empleados/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/empleados/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/*
 	|	Activa un articulo
 	*/
	public function activar()
	{
		if (isset($_GET['id']))
		{
      $this->load->library('form_validation');
      $user = $this->usuarios_model->get_usuario_info($_GET['id'])['info'][0];

      if ($this->validano_checador($user->no_checador) || $user->no_checador == '') {
        if ($this->validano_empleado($user->no_empleado, $user->id_empresa))
        {
    			$this->load->model('usuarios_model');
    			$res_mdl = $this->usuarios_model->activar_usuario($this->input->get('id'));
    			if($res_mdl)
    				redirect(base_url('panel/empleados/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
        } else
          redirect(base_url('panel/empleados/?'.MyString::getVarsLink(array('msg')).'&msg=8'));
      } else
        redirect(base_url('panel/empleados/?'.MyString::getVarsLink(array('msg')).'&msg=10'));
		}
		else
			redirect(base_url('panel/empleados/?'.MyString::getVarsLink(array('msg')).'&msg=9'));
	}

  public function historial()
  {
    $this->load->model('usuario_historial_model');

    $this->usuario_historial_model->printHistorialDeEmpleado($_GET['id']);
  }

  public function prestamos()
  {
    $this->load->model('usuario_historial_model');

    $this->usuario_historial_model->printPrestamosDeEmpleado($_GET['id']);
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

   public function ajax_get_usuarios2()
   {
      $this->load->model('usuarios_model');
      $this->load->model('nomina_fiscal_model');
      $data = $this->usuarios_model->getUsuariosAjax();
      $_GET['cid_empresa'] = $_GET['did_empresa'];
      $filtros = array(
        'semana'            => '',
        'anio'              => date("Y"),
        'empresaId'         => $_GET['did_empresa'],
        'puestoId'          => '',
        'dia_inicia_semana' => '4',
        'tipo_nomina' => ['tipo' => 'se', 'con_vacaciones' => '0', 'con_aguinaldo' => '0']
      );
      $configuraciones = $this->nomina_fiscal_model->configuraciones($filtros['anio']);
      foreach ($data as $key => $value)
      {
        $data[$key]['nomina'] = $this->nomina_fiscal_model->nomina($configuraciones, $filtros, $value['id']);
        $data[$key]['nomina'] = count($data[$key]['nomina'])>0? $data[$key]['nomina'][0]: null;
      }
      echo json_encode($data);
   }

   public function ajax_get_depa_pues()
   {
  	$this->load->model('usuarios_puestos_model');
  	$this->load->model('usuarios_departamentos_model');
  	$params['puestos']       = $this->usuarios_puestos_model->getPuestos(false)['puestos'];
  	$params['departamentos'] = $this->usuarios_departamentos_model->getPuestos(false)['puestos'];
  	echo json_encode($params);
   }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */
  public function sueldos()
  {
    $this->carabiner->js(array(
        array('libs/jquery.numeric.js'),
        array('general/keyjump.js'),
        array('general/msgbox.js'),
        array('panel/usuarios/sueldos.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Usuarios'
    );

    $this->load->model('usuarios_model');
    $this->load->model('empresas_model');
    $this->load->model('nomina_fiscal_model');

    $this->config_sueldos();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('usuarios_model');
      $this->usuarios_model->updateSueldos($_POST);
      redirect(base_url('panel/empleados/sueldos/?'.MyString::getVarsLink(array('msg')).'&msg=7'));
    }

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    // Se obtienen los trabajadores de la empresa
    $filtros = array(
      'semana'      => '',
      'anio'        => date("Y"),
      'empresaId'   => isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $params['empresa']->id_empresa,
      'puestoId'    => '',
      'tipo_nomina' => ['tipo' => 'se', 'con_vacaciones' => '0', 'con_aguinaldo' => '0']
    );
    if ($filtros['empresaId'] !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $filtros['empresaId'])->get()->row()->dia_inicia_semana;
    else
      $dia = '4';
    $filtros['dia_inicia_semana'] = $dia;

    $_GET['cid_empresa'] = $filtros['empresaId'];
    $configuraciones = $this->nomina_fiscal_model->configuraciones($filtros['anio']);

    $params['empleados'] = $this->nomina_fiscal_model->nomina($configuraciones, $filtros);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/empleados/sueldos', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Reporte de resumen de prersepciones y deducciones empleados
   * @return [type] [description]
   */
  public function percep_deduc()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/rpt_perc_deduc.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');
    $this->load->model('nomina_fiscal_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de seguimientos x Producto');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    if ($params['empresa']->id_empresa !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $params['empresa']->id_empresa)->get()->row()->dia_inicia_semana;
    else
      $dia = '4';
    $anio = isset($_GET['anio'])? $_GET['anio']: date("Y");
    $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno($dia, $anio);

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_recibo_perc_deduc',$params);
    $this->load->view('panel/footer',$params);
  }
  public function persep_deduc_pdf(){
    $this->load->model('usuarios_model');
    $this->usuarios_model->getPercDeducPdf($_GET);
  }


  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function config_add_usuario($accion='agregar')
	{
		$this->load->library('form_validation');
    $rules = array(
							array('field' => 'did_empresa',
										'label' => 'Empresa',
										'rules' => 'required|numeric'),
							array('field' => 'fempresa',
										'label' => 'Empresa',
										'rules' => 'required'),

              array('field' => 'fnombre',
                    'label' => 'Nombre',
                    'rules' => 'required|max_length[90]'),
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

              array('field' => 'ffecha_contrato',
                    'label' => 'Fecha vencimiento contrato',
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
              array('field' => 'ftelefono',
                    'label' => 'Teléfono',
                    'rules' => 'max_length[20]'),
              array('field' => 'fcuenta_cpi',
                    'label' => 'Cuenta contpaqi',
                    'rules' => 'max_length[12]'),

							array('field' => 'finfonavit',
										'label' => 'Infonavit',
										'rules' => 'numeric'),
              array('field' => 'ffondo_ahorro',
                    'label' => 'Fondo de Ahorro',
                    'rules' => 'numeric'),

							array('field' => 'festa_asegurado',
										'label' => 'Asegurado',
										'rules' => ''),

							array('field' => 'dcuenta_banco',
										'label' => 'Banco',
										'rules' => ''),
							array('field' => 'dno_seguro',
										'label' => 'No seguro',
										'rules' => ''),
              array('field' => 'dno_trabajador',
                    'label' => 'No Trabajador',
                    'rules' => 'required|max_length[8]|callback_validano_empleado'),

              array('field' => 'dno_checador',
                    'label' => 'No Checador',
                    'rules' => 'max_length[8]|callback_validano_checador'),

              array('field' => 'fdepartamente',
                    'label' => 'Departamento',
                    'rules' => ''),
              array('field' => 'fpuesto',
                    'label' => 'Puesto',
                    'rules' => ''),
              array('field' => 'area',
                    'label' => 'Cultivo',
                    'rules' => ''),
              array('field' => 'areaId',
                    'label' => 'Cultivo',
                    'rules' => ''),
		);

		if ($accion == 'agregar')
		{
      $rules[] = array('field' => 'fnombre',
                        'label' => 'Nombre',
                        'rules' => 'required|max_length[90]|callback_valida_nombre_full');
      $rules[] = array('field' => 'frfc',
                        'label' => 'RFC',
                        'rules' => 'callback_valida_rfc');
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
                               WHERE user_nomina = 't' AND de_rancho = 'n' AND
                                     id_empresa = ".$this->input->post('did_empresa')." AND
                                     lower(nombre) = '".mb_strtolower(trim($_POST['fnombre']))."' AND
                                     lower(apellido_paterno) = '".mb_strtolower(trim($_POST['fapellido_paterno']))."' AND
                                     lower(apellido_materno) = '".mb_strtolower(trim($_POST['fapellido_materno']))."'");
    if ($query->num_rows() > 0)
    {
      $this->form_validation->set_message('valida_nombre_full', 'Ya existe un empleado con el nombre y apellidos especificado, si esta eliminado lo puede activar de nuevo.');
      return false;
    }

    return true;
  }
  public function valida_valida_rfc($rfc)
  {
    if ($rfc != '')
    {
      $query = $this->db->query("SELECT id
                                 FROM usuarios
                                 WHERE de_rancho = 'n' AND id_empresa = ".$this->input->post('did_empresa')." AND lower(rfc) = '".mb_strtolower(trim($rfc))."'");

      if ($query->num_rows() > 0)
      {
        $this->form_validation->set_message('valida_valida_rfc', 'Ya existe un empleado con el mismo RFC, si esta eliminado lo puede activar de nuevo.');
        return false;
      }
    }
  }

  public function validano_empleado($no_empleado, $id_empresa='')
  {
    if ($no_empleado != '')
    {
      $id_empresa = $id_empresa!=''? $id_empresa: $this->input->post('did_empresa');
      $sql = isset($_GET['id'])? "id <> {$_GET['id']} AND": '';
      $query = $this->db->query("SELECT * FROM usuarios
                                 WHERE {$sql} id_empresa = ".$id_empresa."
                                  AND no_empleado = '{$no_empleado}' AND status = 't'");

      if ($query->num_rows() > 0)
      {
        $dt = $query->row();
        $this->form_validation->set_message('validano_empleado', 'Ya existe un empleado con el mismo No de Trabajador, '.$dt->nombre.' '.$dt->apellido_paterno);
        return false;
      }
    }else{
      $this->form_validation->set_message('validano_empleado', 'Es requerido el No de Trabajador');
      return false;
    }
    return true;
  }

  public function validano_checador($no_empleado)
  {
    if ($no_empleado != '')
    {
      $sql = isset($_GET['id'])? "id <> {$_GET['id']}": '';
      $query = $this->db->query("SELECT * FROM usuarios
                                 WHERE {$sql}
                                  AND no_checador = '{$no_empleado}' AND status = 't'");

      if ($query->num_rows() > 0)
      {
        $dt = $query->row();
        $this->form_validation->set_message('validano_checador', 'Ya existe un empleado con el mismo No de Checador, '.$dt->nombre.' '.$dt->apellido_paterno);
        return false;
      }
    }
    // else {
    //   $this->form_validation->set_message('validano_checador', 'Es requerido el No de Checador');
    //   return false;
    // }
    return true;
  }

  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function config_sueldos($accion='agregar')
  {
    $this->load->library('form_validation');
    $rules = array(
              array('field' => 'did_empresa',
                    'label' => 'Empresa',
                    'rules' => ''),
              array('field' => 'dempresa',
                    'label' => 'Empresa',
                    'rules' => '')
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

      case 7:
        $txt = 'Se cambio el sueldo correctamente.';
        $icono = 'success';
        break;

      case 8:
        $txt = 'Ya existe un empleado con el mismo No de Trabajador.';
        $icono = 'error';
        break;
      case 9:
        $txt = 'Es requerido el No de Trabajador.';
        $icono = 'error';
        break;
      case 10:
        $txt = 'Ya existe un empleado con el mismo No de checador.';
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
