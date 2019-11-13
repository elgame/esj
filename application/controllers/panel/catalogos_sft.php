<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class catalogos_sft extends MY_Controller {
	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
			'catalogos_sft/ajax_get_areas/',
			'catalogos_sft/ajax_get_areasauto/',
			'catalogos_sft/imprimir_catalogo_soft/',
			'catalogos_sft/xls_catalogo_soft/',

			'catalogos_sft/imprimir_catalogo_codigos/',
			'catalogos_sft/xls_catalogo_codigos/',
			'catalogos_sft/ajax_get_codigos/',
			'catalogos_sft/ajax_get_codigosauto/',

			'catalogos_sft/rpt_codigos_cuentas_pdf/',
			'catalogos_sft/rpt_codigos_cuentas_xls/',

      'catalogos_sft/rpt_codigos_cuentas_salidas_pdf/',
      'catalogos_sft/rpt_codigos_cuentas_salidas_xls/',
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

	/**
	 * Default. Mustra el listado de privilegios para administrarlos
	 */
	public function index(){
		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Catalogos'
		);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/footer');
	}

	public function cat_soft(){
		$this->carabiner->js(array(
				array('general/msgbox.js')
		));

		$this->load->model('catalogos_sft_model');
		$this->load->library('pagination');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administrar Catalogo'
		);

		$params['cat_soft'] = $this->catalogos_sft_model->obtenCatSoft();

		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/catalogos/listado_soft', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Agrega un privilegio a la bd
	 */
	public function agregar_soft(){
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
			array('libs/jquery.treeview.css', 'screen')
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.treeview.js'),
			array('panel/compras_ordenes/areas_requisicion_frm.js'),
		));

		$this->load->model('catalogos_sft_model');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar catalogo software'
		);

		$this->configAddModSoft();

		if($this->form_validation->run() == FALSE){
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}else{
			$respons = $this->catalogos_sft_model->addCatSoft();

			if($respons[0])
				redirect(base_url('panel/catalogos_sft/agregar_soft/?'.String::getVarsLink(array('msg')).'&msg=4'));
		}

		// $params['t_areas'] = $this->catalogos_sft_model->getTipoAreas();

		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/catalogos/agregar_soft', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Modificar privilegios
	 */
	public function modificar_soft(){
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
			array('libs/jquery.treeview.css', 'screen')
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.treeview.js'),
			array('panel/compras_ordenes/areas_requisicion_frm.js'),
		));

		$this->load->model('catalogos_sft_model');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Modificar catalogo software'
		);

		if(isset($_GET['id']{0})){
			$this->configAddModSoft();

			if($this->form_validation->run() == FALSE){
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}else{
				$respons = $this->catalogos_sft_model->updateCatSoft($_GET['id']);

				if($respons[0])
					redirect(base_url('panel/catalogos_sft/cat_soft/?'.String::getVarsLink(array('msg', 'id')).'&msg=3'));
			}

			$params['areas'] = $this->catalogos_sft_model->getInfoCatSoft($_GET['id']);
			// $params['t_areas'] = $this->catalogos_sft_model->getTipoAreas();

			if(!is_object($params['areas']))
				unset($params['areas']);

			if(isset($_GET['msg']{0}))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);
		}else
			$params['frm_errors'] = $this->showMsgs(1);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/catalogos/modificar_soft', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Elimina un privilegio de la bd
	 */
	public function eliminar_soft(){
		if(isset($_GET['id']{0})){

			$this->load->model('catalogos_sft_model');
			$respons = $this->catalogos_sft_model->deleteCatSoft($_GET['id']);

			if($respons[0])
				redirect(base_url('panel/catalogos_sft/cat_soft/?msg=5'));
		}else
			$params['frm_errors'] = $this->showMsgs(1);
	}


	public function ajax_get_areas()
	{
		$this->load->model('catalogos_sft_model');
		$response = $this->catalogos_sft_model->getAreasEspesifico($_GET['id_area'], $_GET['id_padre']);
		echo json_encode($response);
	}

   public function ajax_get_areasauto()
   {
      $this->load->model('catalogos_sft_model');

      echo json_encode($this->catalogos_sft_model->ajaxAreas());
   }

  public function imprimir_catalogo_soft()
	{
		$this->load->model('catalogos_sft_model');
		$this->catalogos_sft_model->listaCatSoft();
	}

	public function xls_catalogo_soft()
	{
		$this->load->model('catalogos_sft_model');

		header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=catalogo_software.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

		echo $this->catalogos_sft_model->getCatSoftXls(0, true);
		exit;
	}


	/**
	 * Configura los metodos de agregar y modificar
	 */
	private function configAddModSoft(){
		$this->load->library('form_validation');
		$rules = array(
			array('field'	=> 'dnombre',
					'label'		=> 'Nombre',
					'rules'		=> 'required|max_length[80]'),
			array('field'	=> 'dcodigo',
					'label'		=> 'Codigo',
					'rules'		=> 'max_length[80]|callback_val_codigo'),
			array('field'	=> 'ddescripcion',
					'label'		=> 'Descripcion',
					'rules'		=> 'max_length[160]'),
			array('field'	=> 'dareas',
					'label'		=> 'Catalogo',
					'rules'		=> '')
		);
		$this->form_validation->set_rules($rules);
	}

	public function val_codigo($codigo)
  {
  	$sql = isset($_GET['id']{0})? " AND id_cat_soft <> ".$_GET['id']: '';

  	$id_padre = (intval($this->input->post('dareas'))>0? ' = '.$this->input->post('dareas'): ' IS NULL');

  	$datos = $this->db->query("SELECT Count(id_cat_soft) AS num from otros.cat_soft
  		where status = 't' AND id_padre {$id_padre}
  			AND lower(codigo) = '".mb_strtolower($codigo, 'UTF-8')."'".$sql)->row();
    if ($datos->num > 0)
    {
      $this->form_validation->set_message('val_codigo', 'El codigo ya existe en ese nivel del catalogo.');
      return false;
    }
    else
    {
      return true;
    }
  }


  /*******************************************
   * Catalogo de codigos
   * *****************************************
   */
  public function cat_codigos(){
		$this->carabiner->js(array(
				array('general/msgbox.js')
		));

		$this->load->model('catalogos_sft_model');
		$this->load->library('pagination');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administrar Catalogo'
		);

		$params['cat_codigos'] = $this->catalogos_sft_model->obtenCatCodigos();

		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/catalogos/listado_codigos', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Agrega un privilegio a la bd
	 */
	public function agregar_codigos(){
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
			array('libs/jquery.treeview.css', 'screen')
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.treeview.js'),
			array('panel/compras_ordenes/areas_requisicion_frm.js'),
		));

		$this->load->model('catalogos_sft_model');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar catalogo codigos'
		);

		$this->configAddModCodigos();

		if($this->form_validation->run() == FALSE){
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}else{
			$respons = $this->catalogos_sft_model->addCatCodigos();

			if($respons[0])
				redirect(base_url('panel/catalogos_sft/agregar_codigos/?'.String::getVarsLink(array('msg')).'&msg=4'));
		}

		// $params['t_areas'] = $this->catalogos_sft_model->getTipoAreas();

		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/catalogos/agregar_codigos', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Modificar privilegios
	 */
	public function modificar_codigos(){
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
			array('libs/jquery.treeview.css', 'screen')
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.treeview.js'),
			array('panel/compras_ordenes/areas_requisicion_frm.js'),
		));

		$this->load->model('catalogos_sft_model');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Modificar catalogo codigos'
		);

		if(isset($_GET['id']{0})){
			$this->configAddModCodigos();

			if($this->form_validation->run() == FALSE){
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}else{
				$respons = $this->catalogos_sft_model->updateCatCodigos($_GET['id']);

				if($respons[0])
					redirect(base_url('panel/catalogos_sft/cat_codigos/?'.String::getVarsLink(array('msg', 'id')).'&msg=3'));
			}

			$params['areas'] = $this->catalogos_sft_model->getInfoCatCodigos($_GET['id']);
			// $params['t_areas'] = $this->catalogos_sft_model->getTipoAreas();

			if(!is_object($params['areas']))
				unset($params['areas']);

			if(isset($_GET['msg']{0}))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);
		}else
			$params['frm_errors'] = $this->showMsgs(1);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/catalogos/modificar_codigos', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Elimina un privilegio de la bd
	 */
	public function eliminar_codigos(){
		if(isset($_GET['id']{0})){

			$this->load->model('catalogos_sft_model');
			$respons = $this->catalogos_sft_model->deleteCatCodigos($_GET['id']);

			if($respons[0])
				redirect(base_url('panel/catalogos_sft/cat_codigos/?msg=5'));
		}else
			$params['frm_errors'] = $this->showMsgs(1);
	}


	public function ajax_get_codigos()
	{
		$this->load->model('catalogos_sft_model');
		$response = $this->catalogos_sft_model->getCatCodigosEspesifico($_GET['id_area'], $_GET['id_padre']);
		echo json_encode($response);
	}

   public function ajax_get_codigosauto()
   {
      $this->load->model('catalogos_sft_model');

      echo json_encode($this->catalogos_sft_model->ajaxCatCodigos());
   }

  public function imprimir_catalogo_codigos()
	{
		$this->load->model('catalogos_sft_model');
		$this->catalogos_sft_model->listaCatCodigos();
	}

	public function xls_catalogo_codigos()
	{
		$this->load->model('catalogos_sft_model');

		header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=catalogo_codigosware.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

		echo $this->catalogos_sft_model->getCatCodigosXls(0, true);
		exit;
	}

  public function rpt_codigos_cuentas()
  {
    $this->carabiner->css(array(
      array('libs/jquery.treeview.css', 'screen')
    ));
    $this->carabiner->js(array(
      array('libs/jquery.treeview.js'),
      array('panel/catalogos/rpt_codigos_cuentas.js'),
    ));

    $this->load->model('catalogos_sft_model');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte codigos gastos');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    $this->catalogos_sft_model->class_treeAreas = 'treeviewcustom';
    $params['vehiculos'] = $this->catalogos_sft_model->getFrmCatCodigos();

    $this->load->view('panel/header',$params);
    // $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/catalogos/rpt_codigos_cuentas',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_codigos_cuentas_pdf()
  {
    $this->load->model('catalogos_sft_model');
    $this->catalogos_sft_model->rpt_codigos_cuentas_pdf();
  }
  public function rpt_codigos_cuentas_xls()
  {
    $this->load->model('catalogos_sft_model');
    $this->catalogos_sft_model->rpt_codigos_cuentas_xls();
  }

  public function rpt_codigos_cuentas_salidas()
  {
    $this->carabiner->css(array(
      array('libs/jquery.treeview.css', 'screen')
    ));
    $this->carabiner->js(array(
      array('libs/jquery.treeview.js'),
      array('panel/catalogos/rpt_codigos_cuentas.js'),
    ));

    $this->load->model('catalogos_sft_model');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte codigos salidas');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    $this->catalogos_sft_model->class_treeAreas = 'treeviewcustom';
    $params['vehiculos'] = $this->catalogos_sft_model->getFrmCatCodigos();

    $this->load->view('panel/header',$params);
    // $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/catalogos/rpt_codigos_salidas',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_codigos_cuentas_salidas_pdf()
  {
    $this->load->model('catalogos_sft_model');
    $this->catalogos_sft_model->rpt_codigos_cuentas_salidas_pdf();
  }
  public function rpt_codigos_cuentas_salidas_xls()
  {
    $this->load->model('catalogos_sft_model');
    $this->catalogos_sft_model->rpt_codigos_cuentas_salidas_xls();
  }


	/**
	 * Configura los metodos de agregar y modificar
	 */
	private function configAddModCodigos(){
		$this->load->library('form_validation');
		$rules = array(
			array('field'	=> 'dnombre',
					'label'		=> 'Nombre',
					'rules'		=> 'required|max_length[80]'),
			array('field'	=> 'dcodigo',
					'label'		=> 'Codigo',
					'rules'		=> 'max_length[80]|callback_val_codigo2'),
			array('field'	=> 'ddescripcion',
					'label'		=> 'Descripcion',
					'rules'		=> 'max_length[160]'),
			array('field'	=> 'dubicacion',
					'label'		=> 'Ubicacion',
					'rules'		=> 'max_length[100]'),
			array('field'	=> 'dotro_dato',
					'label'		=> 'Otro dato',
					'rules'		=> 'max_length[100]'),
			array('field'	=> 'dareas',
					'label'		=> 'Catalogo',
					'rules'		=> '')
		);
		$this->form_validation->set_rules($rules);
	}

	public function val_codigo2($codigo)
  {
  	$sql = isset($_GET['id']{0})? " AND id_cat_codigos <> ".$_GET['id']: '';

  	$id_padre = (intval($this->input->post('dareas'))>0? ' = '.$this->input->post('dareas'): ' IS NULL');

  	$datos = $this->db->query("SELECT Count(id_cat_codigos) AS num from otros.cat_codigos
  		where status = 't' AND id_padre {$id_padre}
  			AND lower(codigo) = '".mb_strtolower($codigo, 'UTF-8')."'".$sql)->row();
    if ($datos->num > 0)
    {
      $this->form_validation->set_message('val_codigo', 'El codigo ya existe en ese nivel del catalogo.');
      return false;
    }
    else
    {
      return true;
    }
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
				$txt = 'Se modifico correctamente.';
				$icono = 'success';
			break;
			case 4:
				$txt = 'Se agrego correctamente.';
				$icono = 'success';
			break;
			case 5:
				$txt = 'Se elimino correctamente.';
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