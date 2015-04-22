<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class unidades extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
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
      array('general/supermodal.js'),
      array('general/msgbox.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Unidades'
    );

    $this->load->library('pagination');
    $this->load->model('unidades_model');

    $params['unidades'] = $this->unidades_model->getUnidades();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/unidades/admin', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Visualiza el formulario para agregar.
   *
   * @return void
   */
  public function agregar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/keyjump.js'),
      array('panel/unidades/agregar.js'),
    ));

    $this->load->model('unidades_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Unidad'
    );

    $this->configAddUnidad();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->unidades_model->agregar();

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/unidades/agregar/?'.String::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/unidades/agregar', $params);
    $this->load->view('panel/footer');
  }

   /**
   * Visualiza el formulario para modificar.
   *
   * @return void
   */
  public function modificar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/keyjump.js'),
      array('panel/unidades/agregar.js'),
    ));

    $this->load->model('unidades_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Unidad'
    );

    $this->configAddUnidad();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->unidades_model->modificar($_GET['id']);

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/unidades/modificar/?'.String::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    $params['unidad'] = $this->unidades_model->info($_GET['id'], true);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/unidades/modificar', $params);
    $this->load->view('panel/footer');
  }

  public function eliminar()
  {
    $this->load->model('unidades_model');
    $this->unidades_model->eliminar($_GET['id']);

    redirect(base_url('panel/unidades/?&msg=5'));
  }

  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddUnidad()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'nombre',
            'label' => 'Nombre',
            'rules' => 'required|max_length[40]'),
      array('field' => 'codigo',
            'label' => 'Codigo',
            'rules' => 'max_length[20]'),
      array('field' => 'cantidad',
            'label' => 'Cantidad',
            'rules' => 'numeric'),

      array('field' => 'concepto[]',
            'label' => 'Productos',
            'rules' => ''),
      array('field' => 'productoId[]',
            'label' => 'Productos',
            'rules' => ''),
      array('field' => 'cantidad[]',
            'label' => 'Cantidad',
            'rules' => 'greater_than[0]'),
    );

    $this->form_validation->set_rules($rules);
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
        $txt = 'La unidad se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'La unidad se modifico correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La unidad se elimino correctamente.';
        $icono = 'success';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}