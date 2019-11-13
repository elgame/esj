<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class catalogos33 extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'catalogos33/claveProdServ/',
    'catalogos33/claveUnidad/',
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
  }

  public function claveProdServ(){
    $this->load->model('catalogos33_model');
    $params = $this->catalogos33_model->claveProdServ();

    echo json_encode($params);
  }

  public function claveUnidad(){
    $this->load->model('catalogos33_model');
    $params = $this->catalogos33_model->claveUnidad();

    echo json_encode($params);
  }

  public function regimenFiscales(){
    $this->load->model('catalogos33_model');
    $params = $this->catalogos33_model->regimenFiscales();

    echo json_encode($params);
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
        $txt = 'El almacen se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El almacen se modificó correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'El almacen se eliminó correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'El almacen se activó correctamente.';
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
