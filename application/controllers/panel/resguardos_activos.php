<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class resguardos_activos extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'resguardos_activos/resguardo_pdf/',
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
      array('panel/resguardo_activos/resguardo.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Resguardo de Activos'
    );

    $this->load->model('empresas_model');

    $this->load->model('resguardos_activos_model');
    $params['resguardos_activos'] = $this->resguardos_activos_model->getResguardos();
    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    if(!isset($_GET['did_empresa']))
      $_GET['did_empresa'] = $params['empresa']->id_empresa;

    $params['fecha'] = date("Y-m-d");

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/resguardo_activos/admin', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Muestra el Formulario para agregar un resguardo
   * @return [type] [description]
   */
  public function agregar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));
    $this->carabiner->js(array(
        array('libs/jquery.uniform.min.js'),
      array('panel/resguardo_activos/agregar.js'),
    ));

    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Resguardo'
    );

    $this->configAddModResguardo();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('resguardos_activos_model');
      $res_mdl = $this->resguardos_activos_model->addResguardo();

      if(!$res_mdl['error'])
        redirect(base_url('panel/resguardos_activos/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
    }

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/resguardo_activos/agregar', $params);
    $this->load->view('panel/footer');
  }

  /*
  | Muestra el Formulario para modificar un usuario
  */
  public function modificar()
  {
    if (isset($_GET['id']))
    {
      $this->carabiner->css(array(
        array('libs/jquery.uniform.css', 'screen'),
      ));
      $this->carabiner->js(array(
          array('libs/jquery.uniform.min.js'),
        array('panel/resguardo_activos/agregar.js'),
      ));

      $this->load->model('resguardos_activos_model');
      $this->load->model('empresas_model');

      $params['info_empleado'] = $this->info_empleado['info']; //info empleado
      $params['seo'] = array(
        'titulo' => 'Modificar Resguardo'
      );

      $this->configAddModResguardo('modificar');
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $res_mdl = $this->resguardos_activos_model->updateResguardo($this->input->get('id'));

        if($res_mdl['error'] == FALSE)
          redirect(base_url('panel/resguardos_activos/?'.MyString::getVarsLink(array('msg', 'id')).'&msg=4'));
      }

      $params['resguardo'] = $this->resguardos_activos_model->getResguardoInfo();

      if (isset($_GET['msg']))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/resguardo_activos/modificar', $params);
      $this->load->view('panel/footer');
    }
    else
      redirect(base_url('panel/resguardos_activos/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * pone eliminado a un proveedor
   * @return [type] [description]
   */
  public function eliminar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('resguardos_activos_model');
      $res_mdl = $this->resguardos_activos_model->updateResguardo( $this->input->get('id'), array('status' => 'f') );
      if($res_mdl)
        redirect(base_url('panel/resguardos_activos/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
    }
    else
      redirect(base_url('panel/resguardos_activos/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Activa un proveedor eliminado
   * @return [type] [description]
   */
  public function activar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('resguardos_activos_model');
      $res_mdl = $this->resguardos_activos_model->updateResguardo( $this->input->get('id'), array('status' => 't') );
      if($res_mdl)
        redirect(base_url('panel/resguardos_activos/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
    }
    else
      redirect(base_url('panel/resguardos_activos/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  public function imprimir()
  {
    $this->load->model('resguardos_activos_model');

    if (isset($_GET['id']))
    {
      $this->resguardos_activos_model->printResguardo($_GET['id']);
    }
  }

  public function resguardo_pdf()
  {
    $this->load->model('resguardos_activos_model');
    $this->resguardos_activos_model->printListado();
  }



  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configAddModResguardo($accion='agregar')
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fempresa',
            'label' => 'Empresa',
            'rules' => 'required|max_length[140]'),
      array('field' => 'did_empresa',
            'label' => 'Empresa',
            'rules' => 'required'),

      array('field' => 'fproducto',
            'label' => 'Producto/Activo',
            'rules' => 'required|max_length[140]'),
      array('field' => 'fid_producto',
            'label' => 'Producto/Activo',
            'rules' => 'required'),

      array('field' => 'fentrego',
            'label' => 'Entrego',
            'rules' => 'required|max_length[140]'),
      array('field' => 'fid_entrego',
            'label' => 'Entrego',
            'rules' => 'required'),

      array('field' => 'frecibio',
            'label' => 'Recibió',
            'rules' => 'required|max_length[140]'),
      array('field' => 'fid_recibio',
            'label' => 'Recibió',
            'rules' => 'required'),

      array('field' => 'ftipo',
            'label' => 'Tipo',
            'rules' => 'max_length[60]'),
      array('field' => 'ffecha_entrego',
            'label' => 'Fecha de entrega',
            'rules' => 'max_length[60]'),
      array('field' => 'fobservaciones',
            'label' => 'Observaciones',
            'rules' => 'max_length[1000]'),
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
        $txt = 'El resguardo se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El resguardo se modificó correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'El resguardo se eliminó correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'El resguardo se activó correctamente.';
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
