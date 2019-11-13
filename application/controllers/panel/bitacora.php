<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bitacora extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'bitacora/bitacora_pdf/',
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
   * Muestra la vista para el Reporte "REPORTE DE bitacora"
   *
   * @return void
   */
  public function index()
  {
    $this->carabiner->js(array(
      // array('general/msgbox.js'),
      array('panel/bascula/admin.js'),
      array('panel/bascula/reportes/rde.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Bitacora'
    );
    $this->load->model('bitacora_msg_model');

    $params['secciones'] = $this->bitacora_msg_model->getSecciones();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    // $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bitacora/bitacora', $params);
    $this->load->view('panel/footer');
  }

  public function bitacora_pdf()
  {
    $this->load->model('bitacora_model');
    $this->bitacora_model->bitacora_pdf();
  }

}

/* End of file bascula.php */
/* Location: ./application/controllers/panel/bascula.php */