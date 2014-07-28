<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class compras_areas extends MY_Controller {
	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
			'compras_areas/ajax_get_areas/',
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
		$this->carabiner->js(array(
				array('general/msgbox.js')
		));
		
		$this->load->model('compras_areas_model');
		$this->load->library('pagination');
		
		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administrar Catalogo'
		);
		
		$params['areas'] = $this->compras_areas_model->obtenAreas();
		
		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);
		
		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/almacen/compras_areas/listado', $params);
		$this->load->view('panel/footer');
	}
	
	/**
	 * Agrega un privilegio a la bd
	 */
	public function agregar(){
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
			array('libs/jquery.treeview.css', 'screen')
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.treeview.js'),
			array('panel/compras_ordenes/areas_requisicion_frm.js'),
		));

		$this->load->model('compras_areas_model');
		
		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar area'
		);
		
		$this->configAddModPriv();
		
		if($this->form_validation->run() == FALSE){
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}else{
			$respons = $this->compras_areas_model->addArea();
			
			if($respons[0])
				redirect(base_url('panel/compras_areas/agregar/?'.String::getVarsLink(array('msg')).'&msg=4'));
		}

		$params['t_areas'] = $this->compras_areas_model->getTipoAreas();
		
		if(isset($_GET['msg']{0}))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);
		
		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/almacen/compras_areas/agregar', $params);
		$this->load->view('panel/footer');
	}
	
	/**
	 * Modificar privilegios
	 */
	public function modificar(){
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
			array('libs/jquery.treeview.css', 'screen')
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.treeview.js'),
			array('panel/compras_ordenes/areas_requisicion_frm.js'),
		));

		$this->load->model('compras_areas_model');
		
		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Modificar areas'
		);
		
		if(isset($_GET['id']{0})){
			$this->configAddModPriv();
			
			if($this->form_validation->run() == FALSE){
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}else{
				$respons = $this->compras_areas_model->updateArea($_GET['id']);
				
				if($respons[0])
					redirect(base_url('panel/compras_areas/?'.String::getVarsLink(array('msg', 'id')).'&msg=3'));
			}
			
			$params['areas'] = $this->compras_areas_model->getInfo($_GET['id']);
			$params['t_areas'] = $this->compras_areas_model->getTipoAreas();

			if(!is_object($params['areas']))
				unset($params['areas']);
			
			if(isset($_GET['msg']{0}))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);
		}else
			$params['frm_errors'] = $this->showMsgs(1);
		
		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/almacen/compras_areas/modificar', $params);
		$this->load->view('panel/footer');
	}
	
	/**
	 * Elimina un privilegio de la bd
	 */
	public function eliminar(){
		if(isset($_GET['id']{0})){
			$respons = $this->usuarios_model->deletePrivilegio();
			
			if($respons[0])
				redirect(base_url('panel/privilegios/?msg=5'));
		}else
			$params['frm_errors'] = $this->showMsgs(1);
	}


	public function ajax_get_areas()
	{
		$this->load->model('compras_areas_model');
		$response = $this->compras_areas_model->getAreasEspesifico($_GET['id_area'], $_GET['id_padre']);
		echo json_encode($response);
	}
	
	
	/**
	 * Configura los metodos de agregar y modificar
	 */
	private function configAddModPriv(){
		$this->load->library('form_validation');
		$rules = array(
			array('field'	=> 'dnombre',
					'label'		=> 'Nombre',
					'rules'		=> 'required|max_length[70]'),
			array('field'	=> 'dcodigo',
					'label'		=> 'Codigo',
					'rules'		=> 'required|max_length[10]|callback_val_codigo'),
			array('field'	=> 'did_tipo',
					'label'		=> 'Tipo',
					'rules'		=> ''),
			array('field'	=> 'dareas',
					'label'		=> 'Catalogo',
					'rules'		=> '')
		);
		$this->form_validation->set_rules($rules);
	}

	public function val_codigo($codigo)
  {
  	$sql = isset($_GET['id']{0})? " AND id_area <> ".$_GET['id']: '';
  	$datos = $this->db->query("SELECT Count(id_area) AS num from compras_areas 
  		where id_padre = ".$_POST['dareas']." 
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
				$txt = 'El privilegio se modifico correctamente.';
				$icono = 'success';
			break;
			case 4:
				$txt = 'El privilegio se agrego correctamente.';
				$icono = 'success';
			break;
			case 5:
				$txt = 'El privilegio se elimino correctamente.';
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