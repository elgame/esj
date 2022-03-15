<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class etiquetas extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
    'etiquetas/etiqueta_pdf/',
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
		$this->carabiner->css(array(
      array('libs/jquery.treeview.css', 'screen')
    ));
    $this->carabiner->js(array(
      array('libs/jquery.treeview.js'),
      // array('panel/productos_salidas/rpt_gastos.js'),
    ));

    $this->load->model('compras_areas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Etiquetas');

    // $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacenes/etiquetas', $params);
    $this->load->view('panel/footer',$params);
	}

  public function etiqueta_pdf()
  {
    if ($this->input->get('tipo') > 0 && $this->input->get('caja') > 0 && $this->input->get('rollos') > 0) {
      $this->load->model('etiquetas_model');
      $this->etiquetas_model->{"etiqueta{$_GET['tipo']}_pdf"}($_GET);
    }
  }
}



/* End of file usuarios.php */
/* Location: ./application/controllers/panel/usuarios.php */
