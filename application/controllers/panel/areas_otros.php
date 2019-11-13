<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class areas_otros extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
			'areas_otros/ajax_get_calidades/',
      'areas_otros/ajax_get_tamano/',
      'areas_otros/ajax_get_calibres/',
			'areas_otros/ajax_add_new_calibre/',

			'areas_otros/clasificaciones_xls/',
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
		// $this->carabiner->js(array(
		// 		array('general/msgbox.js')
		// ));

		// $params['info_empleado'] = $this->info_empleado['info']; //info empleado
		// $params['seo'] = array(
		// 	'titulo' => 'Administración de Areas'
		// );

		// $this->load->model('areas_model');
		// $params['areas'] = $this->areas_model->getAreas();

		// if (isset($_GET['msg']))
		// 	$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		// $this->load->view('panel/header', $params);
		// $this->load->view('panel/general/menu', $params);
		// $this->load->view('panel/areas/admin', $params);
		// $this->load->view('panel/footer');
	}


	/**
	 * CALIDADES
	 * *******************************************************
	 */
	function calidades(){
		if (isset($_GET['id']))
		{
			$this->load->model('calidades_ventas_model');
			$params['calidades_ventas'] = $this->calidades_ventas_model->getCalidades($_GET['id']);

			$html = $this->load->view('panel/areas/calidades_ventas/admin', $params, true);
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

			$this->load->model('calidades_ventas_model');
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
				$res_mdl = $this->calidades_ventas_model->addCalidad();

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/areas/modificar/?id='.$this->input->get('id').'&msg=13'));
			}

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/areas/calidades_ventas/agregar', $params);
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

			$this->load->model('calidades_ventas_model');
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
				$res_mdl = $this->calidades_ventas_model->updateCalidad($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=11'));
			}

			$params['data'] = $this->calidades_ventas_model->getCalidadInfo($_GET['id']);
			$params['areas'] = $this->areas_model->getAreas(false);

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/areas/calidades_ventas/modificar', $params);
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
			$this->load->model('calidades_ventas_model');
			$res_mdl = $this->calidades_ventas_model->updateCalidad( $this->input->get('id'), array('status' => 'f') );
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
			$this->load->model('calidades_ventas_model');
			$res_mdl = $this->calidades_ventas_model->updateCalidad( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=8'));
		}
		else
			redirect(base_url('panel/areas/?msg=1'));
	}

	/**
	 * Obtiene lostado de productores para el autocomplete, ajax
	 */
	public function ajax_get_calidades()
	{
		$this->load->model('calidades_ventas_model');

		echo json_encode($this->calidades_ventas_model->get_calidades());
	}


	/**
	 * TAMAÑOS
	 * *******************************************************
	 */
	function tamanos(){
		if (isset($_GET['id']))
		{
			$this->load->model('tamanos_ventas_model');
			$params['tamanos_ventas'] = $this->tamanos_ventas_model->getTamanios($_GET['id']);

			$html = $this->load->view('panel/areas/tamanios/admin', $params, true);
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

	public function agregar_tamano(){
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

			$this->load->model('tamanos_ventas_model');
			$this->load->model('areas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Agregar tamaño'
			);

			$this->configAddModTamanio();
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->tamanos_ventas_model->addTamanio();

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/areas/modificar/?id='.$this->input->get('id').'&msg=14'));
			}

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/areas/tamanios/agregar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/areas/modificar/?id='.$this->input->get('id').'&msg=1'));
	}

	public function modificar_tamano(){
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

			$this->load->model('tamanos_ventas_model');
			$this->load->model('areas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar tamaño'
			);

			$this->configAddModTamanio('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->tamanos_ventas_model->updateTamanio($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=12'));
			}

			$params['data'] = $this->tamanos_ventas_model->getTamanioInfo($_GET['id']);
			$params['areas'] = $this->areas_model->getAreas(false);

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/areas/tamanios/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=1'));
	}

	/**
	 * pone eliminado a un calidad
	 * @return [type] [description]
	 */
	public function eliminar_tamano()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('tamanos_ventas_model');
			$res_mdl = $this->tamanos_ventas_model->updateTamanio( $this->input->get('id'), array('status' => 'f') );
			if($res_mdl)
				redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=9'));
		}
		else
			redirect(base_url('panel/areas/?msg=1'));
	}

	/**
	 * Activa una calidad eliminado
	 * @return [type] [description]
	 */
	public function activar_tamano()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('tamanos_ventas_model');
			$res_mdl = $this->tamanos_ventas_model->updateTamanio( $this->input->get('id'), array('status' => 't') );
			if($res_mdl)
				redirect(base_url('panel/areas/modificar/?id='.$this->input->get('idarea').'&msg=10'));
		}
		else
			redirect(base_url('panel/areas/?msg=1'));
	}

	/**
	 * Obtiene lostado de productores para el autocomplete, ajax
	 */
	public function ajax_get_tamano()
	{
		$this->load->model('tamanos_ventas_model');

		echo json_encode($this->tamanos_ventas_model->get_tamanios());
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

	public function configAddModCalidad($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fnombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[20]'),
			array('field' => 'farea',
						'label' => 'Area',
						'rules' => 'required|numeric'),
		);

		$this->form_validation->set_rules($rules);
	}

	public function configAddModTamanio($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fnombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[30]'),
			array('field' => 'farea',
						'label' => 'Area',
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
				$txt = 'El tamaño se eliminó correctamente.';
				$icono = 'success';
				break;
			case 10:
				$txt = 'El tamaño se activó correctamente.';
				$icono = 'success';
				break;

			case 11:
				$txt = 'La calidad se modificó correctamente.';
				$icono = 'success';
				break;
			case 12:
				$txt = 'El tamaño se modificó correctamente.';
				$icono = 'success';
				break;

			case 13:
				$txt = 'La calidad se agregó correctamente.';
				$icono = 'success';
				break;
			case 14:
				$txt = 'El tamaño se agregó correctamente.';
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
