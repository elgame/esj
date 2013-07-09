<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class rastreabilidad_pallets extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'rastreabilidad_pallets/rrp_pdf/',
    'rastreabilidad_pallets/ref_pdf/',
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
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Rastreabilidad'
    );

    $this->load->model('rastreabilidad_pallets_model');

    $params['pallets'] = $this->rastreabilidad_pallets_model->getPallets(true);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/rastreabilidad/pallets/admin', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Muestra el Formulario para agregar un pallet
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
      array('panel/rastreabilidad/pallets_agregar.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Pallet'
    );

    $this->load->model('rastreabilidad_pallets_model');

    $this->configAddModPallet();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->rastreabilidad_pallets_model->addPallet();
      
      redirect(base_url('panel/rastreabilidad_pallets/agregar/?'.String::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
    }

    $params['folio'] = $this->rastreabilidad_pallets_model->getNextFolio();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/rastreabilidad/pallets/agregar', $params);
    $this->load->view('panel/footer');
  }
  


  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddModPallet()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'ffolio',
            'label' => 'Folio',
            'rules' => 'required|is_natural_no_zero|is_unique[rastria_pallets.folio]'),
      array('field' => 'fid_clasificacion',
            'label' => 'Clasificacion',
            'rules' => 'required|is_natural_no_zero'),
      array('field' => 'fcajas',
            'label' => 'Cajas',
            'rules' => 'required|is_natural_no_zero'),

      array('field' => 'fclasificacion',
            'label' => 'Clasificacion',
            'rules' => ''),
    );


    $this->form_validation->set_rules($rules);
  }

    public function chkfolio($folio){
    if ( ! isset($_GET['idb']) && ! isset($_GET['e']))
    {
      $result = $this->db->query("SELECT Count(id_bascula) AS num FROM bascula
        WHERE folio = {$folio} AND tipo = '{$this->input->post('ptipo')}'
        AND id_area = {$this->input->post('parea')}")->row();
      if($result->num > 0){
        $this->form_validation->set_message('chkfolio', 'El folio ya existe, intenta con otro.');
        return false;
      }else
        return true;
    }
  }


  /*
   |------------------------------------------------------------------------
   | Mensajes.
   |------------------------------------------------------------------------
   */
  private function showMsgs($tipo, $msg='', $title='Bascula')
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
        $txt = 'El pallet se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'Existe un Pallet de la misma clasificacion pendiente.';
        $icono = 'error';
        break;
      case 5:
        $txt = 'El chofer se agregó correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'El camión se activó correctamente.';
        $icono = 'success';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

/* End of file bascula.php */
/* Location: ./application/controllers/panel/bascula.php */