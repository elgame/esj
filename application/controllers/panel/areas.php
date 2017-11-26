<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class areas extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
			'areas/ajax_get_calidades/',
      'areas/ajax_get_clasificaciones/',
      'areas/ajax_get_calibres/',
			'areas/ajax_add_new_calibre/',

			'areas/clasificaciones_xls/',
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
			'titulo' => 'Administración de Areas'
		);

		$this->load->model('areas_model');
		$params['areas'] = $this->areas_model->getAreas();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/areas/admin', $params);
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
			array('panel/areas/frmAddArea.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Area'
		);

		$this->configAddModArea();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('areas_model');
			$res_mdl = $this->areas_model->addArea();

			if(!$res_mdl['error'])
				redirect(base_url('panel/areas/agregar/?'.String::getVarsLink(array('msg')).'&msg=3'));
		}


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/areas/agregar', $params);
		$this->load->view('panel/footer');
	}

	/*
 	|	Muestra el Formulario para modificar un area
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
				array('general/msgbox.js'),
				array('panel/areas/frmEditArea.js'),
			));

			$this->load->model('areas_model');
			$this->load->model('clasificaciones_model');
			$this->load->model('calidades_ventas_model');
			$this->load->model('tamanos_ventas_model');
			$this->load->model('calidades_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar area'
			);

			$this->configAddModArea('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->areas_model->updateArea($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/areas/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data']                 = $this->areas_model->getAreaInfo($_GET['id']);

			$params['calidades']            = $this->calidades_model->getCalidades($_GET['id']);
			$params['html_calidades']       = $this->load->view('panel/areas/calidades/admin', $params, true);

			$params['clasificaciones']      = $this->clasificaciones_model->getClasificaciones($_GET['id']);
			$params['html_clasificaciones'] = $this->load->view('panel/areas/clasificaciones/admin', $params, true);

			$params['calidades_ventas']            = $this->calidades_ventas_model->getCalidades($_GET['id']);
			$params['html_calidades_ventas']       = $this->load->view('panel/areas/calidades_ventas/admin', $params, true);

			$params['tamanos_ventas']              = $this->tamanos_ventas_model->getTamanios($_GET['id']);
			$params['html_tamanos_ventas']         = $this->load->view('panel/areas/tamanios/admin', $params, true);

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/areas/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/areas/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un area
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('areas_model');
			$res_mdl = $this->areas_model->updateArea( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/areas/?'.String::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/areas/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un area eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('areas_model');
			$res_mdl = $this->areas_model->updateArea( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/areas/?'.String::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/areas/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de productores para el autocomplete, ajax
	 */
	public function ajax_get_proveedores(){
		$this->load->model('proveedores_model');
		$params = $this->proveedores_model->getProveedoresAjax();

		echo json_encode($params);
	}


	/**
	 * CALIDADES
	 * *******************************************************
	 */
	function calidades(){
		if (isset($_GET['id']))
		{
			$this->load->model('calidades_model');
			$params['calidades'] = $this->calidades_model->getCalidades($_GET['id']);

			$html = $this->load->view('panel/areas/calidades/admin', $params, true);
			echo json_encode(array(
				'response' => array(
						'title' => '',
						'msg'   => '',
						'ico'   => 'success'),
				'data' => $html
				));
		}else
			echo json_encode(array(
				'response' => $this->showMsgs(1),
				'data' => ''
				));
	}

	public function agregar_calidad(){
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
				array('general/msgbox.js'),
				array('panel/areas/frmEditArea.js'),
			));

			$this->load->model('calidades_model');
			$this->load->model('areas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Agregar calidad'
			);

			$this->configAddModCalidad();
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->calidades_model->addCalidad();

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/areas/modificar/?id='.$this->input->get('id').'&msg=13'));
			}

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/areas/calidades/agregar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/areas/modificar/?id='.$this->input->get('id').'&msg=1'));
	}

	public function modificar_calidad(){
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
				array('general/msgbox.js'),
				array('panel/areas/frmEditArea.js'),
			));

			$this->load->model('calidades_model');
			$this->load->model('areas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar calidad'
			);

			$this->configAddModCalidad('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->calidades_model->updateCalidad($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=11'));
			}

			$params['data'] = $this->calidades_model->getCalidadInfo($_GET['id']);
			$params['areas'] = $this->areas_model->getAreas(false);

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/areas/calidades/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=1'));
	}

	/**
	 * pone eliminado a un calidad
	 * @return [type] [description]
	 */
	public function eliminar_calidad()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('calidades_model');
			$res_mdl = $this->calidades_model->updateCalidad( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=7'));
		}
		else
			redirect(base_url('panel/areas/?msg=1'));
	}

	/**
	 * Activa una calidad eliminado
	 * @return [type] [description]
	 */
	public function activar_calidad()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('calidades_model');
			$res_mdl = $this->calidades_model->updateCalidad( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=8'));
		}
		else
			redirect(base_url('panel/areas/?msg=1'));
	}

	/**
	 * Obtiene lostado de productores para el autocomplete, ajax
	 */
	public function ajax_get_calidades(){
		$this->load->model('calidades_model');
		$params = $this->calidades_model->getCalidades($_GET['area'], false);

		echo json_encode($params);
	}


	/**
	 * CLASIFICACIONES
	 * *******************************************************
	 */
	function clasificaciones(){
		if (isset($_GET['id']))
		{
			$this->load->model('clasificaciones_model');
			$params['clasificaciones'] = $this->clasificaciones_model->getClasificaciones($_GET['id']);

			$html = $this->load->view('panel/areas/clasificaciones/admin', $params, true);
			echo json_encode(array(
				'response' => array(
						'title' => '',
						'msg'   => '',
						'ico'   => 'success'),
				'data' => $html
				));
		}else
			echo json_encode(array(
				'response' => $this->showMsgs(1),
				'data' => ''
				));
	}

  public function ajax_get_calibres()
  {
    $this->load->model('calibres_model');
    echo json_encode($this->calibres_model->getCalibresAjax());
  }

  public function ajax_add_new_calibre()
  {
    $this->load->model('calibres_model');

    $response = $this->calibres_model->addCalibre($_GET['nombre']);

    echo json_encode($response);
  }

	public function agregar_clasificacion(){
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
				array('general/msgbox.js'),
				array('panel/areas/frmEditArea.js'),
        array('panel/areas/calibres.js'),
			));

			$this->load->model('clasificaciones_model');
			$this->load->model('areas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Agregar clasificacion'
			);

			$this->configAddModClasificacion();
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->clasificaciones_model->addClasificacion();

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/areas/modificar/?id='.$this->input->get('id').'&msg=14'));
			}

      $params['unidades'] = $this->db->select('*')->from('unidades')->where('status', 't')->order_by('nombre')->get()->result();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/areas/clasificaciones/agregar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/areas/modificar/?id='.$this->input->get('id').'&msg=1'));
	}

	public function modificar_clasificacion()
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
				array('general/msgbox.js'),
				array('panel/areas/frmEditArea.js'),
        array('panel/areas/calibres.js'),
			));

			$this->load->model('clasificaciones_model');
			$this->load->model('areas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar clasificacion'
			);

			$this->configAddModClasificacion('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->clasificaciones_model->updateClasificacion($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=12'));
			}

			$params['data'] = $this->clasificaciones_model->getClasificacionInfo($_GET['id']);
			$params['areas'] = $this->areas_model->getAreas(false);
      $params['unidades'] = $this->db->select('*')->from('unidades')->where('status', 't')->order_by('nombre')->get()->result();

      // echo "<pre>";
      //   var_dump($params['data']);
      // echo "</pre>";exit;

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/areas/clasificaciones/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=1'));
	}

	/**
	 * pone eliminado a un clasificacion
	 * @return [type] [description]
	 */
	public function eliminar_clasificacion()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('clasificaciones_model');
			$res_mdl = $this->clasificaciones_model->updateClasificacion( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=9'));
		}
		else
			redirect(base_url('panel/areas/?msg=1'));
	}

	/**
	 * Activa una clasificacion eliminado
	 * @return [type] [description]
	 */
	public function activar_clasificacion()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('clasificaciones_model');
			$res_mdl = $this->clasificaciones_model->updateClasificacion( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=10'));
		}
		else
			redirect(base_url('panel/areas/?msg=1'));
	}

	/**
	 * Obtiene lostado de productores para el autocomplete, ajax
	 */
	public function ajax_get_clasificaciones(){
		$this->load->model('clasificaciones_model');
		$params = $this->clasificaciones_model->ajaxClasificaciones();

		echo json_encode($params);
	}

	public function clasificaciones_xls()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('clasificaciones_model');
			$this->clasificaciones_model->clasificaciones_xls( $this->input->get('id'));
		}
	}


  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModArea($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fnombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[40]'),
			array('field' => 'ftipo',
						'label' => 'Tipo',
						'rules' => 'required|max_length[2]'),

			array('field' => 'cal_nombre[]',
						'label' => 'Nombre calidad',
						'rules' => 'max_length[40]'),
			array('field' => 'cal_precio[]',
						'label' => 'Precio calidad',
						'rules' => 'numeric|max_length[11]'),

			array('field' => 'cla_nombre[]',
						'label' => 'Nombre clasificacion',
						'rules' => 'max_length[40]'),
			array('field' => 'cla_precio[]',
						'label' => 'Precio clasificacion',
						'rules' => 'numeric|max_length[11]'),
			array('field' => 'cla_cuenta[]',
						'label' => 'Cuenta contpaq',
						'rules' => 'max_length[12]'),
		);

		$this->form_validation->set_rules($rules);
	}

	public function configAddModCalidad($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fnombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[40]'),
			array('field' => 'fprecio_compra',
						'label' => 'Precio compra',
						'rules' => 'required|numeric|max_length[11]'),
			array('field' => 'farea',
						'label' => 'Area',
						'rules' => 'required|numeric'),
			array('field' => 'fcuenta_cpi',
						'label' => 'Cuenta contpaq',
						'rules' => 'required|numeric|max_length[12]'),
		);

		$this->form_validation->set_rules($rules);
	}

	public function configAddModClasificacion($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fnombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[40]'),
			array('field' => 'fcodigo',
						'label' => 'Codigo',
						'rules' => 'max_length[15]'),
			array('field' => 'fcuenta_cpi2',
						'label' => 'Cuenta contpaq 2 (Orov)',
						'rules' => 'numeric|max_length[40]'),

			array('field' => 'dinventario',
						'label' => 'Inventario',
						'rules' => ''),

			array('field' => 'farea',
						'label' => 'Area',
						'rules' => 'required|numeric|max_length[11]'),
			array('field' => 'fcuenta_cpi',
						'label' => 'Cuenta contpaq',
						'rules' => 'numeric|max_length[12]'),
      array('field' => 'fcalibres[]',
            'label' => 'Calibres',
            'rules' => ''),
      array('field' => 'fcalibre_nombre[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'diva',
            'label' => 'IVA',
            'rules' => ''),
      array('field' => 'dunidad',
            'label' => 'Unidad / Medida',
            'rules' => ''),

      array('field' => 'dclave_producto_cod',
            'label' => 'Clave de Productos/Servicios',
            'rules' => 'required'),
      array('field' => 'dclave_unidad_cod',
            'label' => 'Clave de unidad',
            'rules' => 'required'),
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
				$txt = 'El area se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El area se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El area se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El area se activó correctamente.';
				$icono = 'success';
				break;

			case 7:
				$txt = 'La calidad se eliminó correctamente.';
				$icono = 'success';
				break;
			case 8:
				$txt = 'La calidad se activó correctamente.';
				$icono = 'success';
				break;

			case 9:
				$txt = 'La clasificacion se eliminó correctamente.';
				$icono = 'success';
				break;
			case 10:
				$txt = 'La clasificacion se activó correctamente.';
				$icono = 'success';
				break;

			case 11:
				$txt = 'La calidad se modificó correctamente.';
				$icono = 'success';
				break;
			case 12:
				$txt = 'La clasificacion se modificó correctamente.';
				$icono = 'success';
				break;

			case 13:
				$txt = 'La calidad se agregó correctamente.';
				$icono = 'success';
				break;
			case 14:
				$txt = 'La clasificacion se agregó correctamente.';
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
