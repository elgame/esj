<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class reportes extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
  );

  public function _remap($method)
  {
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

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Reportes'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Reportes');

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/general/blank', $params);
    $this->load->view('panel/footer');
  }

}
?>