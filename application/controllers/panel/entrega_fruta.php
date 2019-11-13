<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class entrega_fruta extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
    'entrega_fruta/printEntrada/',
    'entrega_fruta/rpt_seg_cert_pdf/',
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
			array('panel/bascula/admin.js'),
			array('panel/clientes/agregar.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administración de Entrega de fruta'
		);

		$this->load->model('entrega_fruta_model');
		$params['entrega_fruta'] = $this->entrega_fruta_model->getEntradas();

    $this->load->model('areas_model');
    $_GET['fstatus'] = '';
    $params['areas'] = $this->areas_model->getAreas();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/entrega_fruta/admin', $params);
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
			'titulo' => 'Crear formatos'
		);

		$this->configAddModEntrada();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('entrega_fruta_model');
			$res_mdl = $this->entrega_fruta_model->addEntradas($_POST);

			if(!$res_mdl['error'])
				redirect(base_url('panel/entrega_fruta/agregar/?'.String::getVarsLink(array('msg')).'&msg=3&hojas='.$res_mdl['hojas']));
		}

		$this->load->model('areas_model');
    $_GET['fstatus'] = '';
    $params['areas'] = $this->areas_model->getAreas();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		if (isset($_GET['hojas']))
			$params['hojas'] = $_GET['hojas'];

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/entrega_fruta/agregar', $params);
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
				array('panel/otros/addmod_entrega_fruta.js'),
				array('panel/compras_ordenes/areas_requisicion.js'),
				array('panel/usuarios/add_mod_frm.js')
			));

			$this->load->model('entrega_fruta_model');
			$this->load->model('compras_areas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar formato'
			);

			$this->configAddModEntrada('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->entrega_fruta_model->updateEntrada($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/entrega_fruta/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['data'] = $this->entrega_fruta_model->getFormatoInfo();

			$params['areas'] = $this->compras_areas_model->getTipoAreas();

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/entrega_fruta/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/entrega_fruta/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un proveedor eliminado
	 * @return [type] [description]
	 */
	public function printEntrada()
	{
		$this->load->model('entrega_fruta_model');
		if (isset($_GET['hojas']))
		{
			$this->entrega_fruta_model->printHojas( $_GET['hojas'] );
		}
		else {
			$this->load->library('mypdf');
	    // Creación del objeto de la clase heredada
	    $pdf = new MYpdf('P', 'mm', 'Letter');
	    $pdf->show_head = false;
	    $pdf->SetMargins(0, 0, 0);
	    $pdf->SetAutoPageBreak(false);
	    $pdf->AddPage();
	    $x = $y = 5;
      $this->entrega_fruta_model->printRecibo( $_GET['id'], $pdf, $x, $y );
      $x = 109;
			$this->entrega_fruta_model->printRecibo( $_GET['id'], $pdf, $x, $y );
			$pdf->Output('Reporte.pdf', 'I');
		}
	}

	// /**
	//  * Obtiene lostado de productores para el autocomplete, ajax
	//  */
	// public function ajax_get_proveedores(){
	// 	$this->load->model('proveedores_model');
	// 	$params = $this->proveedores_model->getProveedoresAjax();

	// 	echo json_encode($params);
	// }

  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModEntrada($accion='agregar')
	{
		$this->load->library('form_validation');

		if ($accion == 'agregar') {
			$rules = array(
				array('field' => 'farea',
							'label' => 'Area',
							'rules' => 'required'),
				array('field' => 'fno_formatos',
							'label' => 'No. hojas',
							'rules' => 'required|numeric')
			);
		} else {
			$rules = array(
				array('field' => 'ffecha',
							'label' => 'Fecha',
							'rules' => 'required'),
				// array('field' => 'codigoAreaId',
				// 			'label' => 'Rancho',
				// 			'rules' => 'required'),
				// array('field' => 'vehiculoId',
				// 			'label' => 'Transporte',
				// 			'rules' => 'required'),
				// array('field' => 'fchoferId',
				// 			'label' => 'Chofer',
				// 			'rules' => 'required'),
				array('field' => 'fencargadoId',
							'label' => 'Encargado',
							'rules' => 'required'),
        array('field' => 'frecibeId',
              'label' => 'Recibe',
              'rules' => 'required'),
				array('field' => 'fboleta',
							'label' => '# Boleta',
							'rules' => 'required|numeric'),
        array('field' => 'fid_bascula',
              'label' => '# Boleta',
              'rules' => 'required|numeric'),

				array('field'	=> 'prod_did_prod[]',
						'label'	=> 'Clasf',
						'rules'	=> ''),
				array('field'	=> 'prod_piso[]',
						'label'	=> 'Piso',
						'rules'	=> ''),
				array('field'	=> 'prod_estibas[]',
						'label'	=> 'Estibas',
						'rules'	=> ''),
				array('field'	=> 'prod_altura[]',
						'label'	=> 'Altura',
						'rules'	=> 'max_length[30]'),
				array('field'	=> 'prod_cantidad[]',
						'label'	=> 'Cantidad',
						'rules'	=> ''),
			);
		}

		$this->form_validation->set_rules($rules);
	}

  // public function rpt_seg_cert()
  // {
  //   $this->carabiner->js(array(
  //     array('general/msgbox.js'),
  //     array('panel/proveedores/rpt_seg_cert.js'),
  //   ));

  //   $this->load->library('pagination');
  //   $this->load->model('empresas_model');

  //   $params['info_empleado']  = $this->info_empleado['info'];
  //   $params['seo']        = array('titulo' => 'Ventas remisiones');

  //   $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

  //   if(isset($_GET['msg']{0}))
  //     $params['frm_errors'] = $this->showMsgs($_GET['msg']);

  //   $this->load->view('panel/header',$params);
  //   $this->load->view('panel/proveedores/rpt_seg_cert',$params);
  //   $this->load->view('panel/footer',$params);
  // }

  // public function rpt_seg_cert_pdf()
  // {
  //   $this->load->model('proveedores_model');
  //   $this->proveedores_model->reporteSegCert();
  // }

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
				$txt = 'El formato se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El formato se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El formato se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El formato se activó correctamente.';
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
