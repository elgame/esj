<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class proyectos extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'proyectos/merges/',
    'proyectos/catalogo_xls/',
    'proyectos/show_view_agregar_productor/',
    'proyectos/ajax_get_proyectos/',
    'proyectos/ajax_get_centros_costos/',
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

  public function fecha($fecha)
  {
    $meses = array('ENE' => '01', 'FEB' => '02', 'MAR' => '03', 'ABR' => '04', 'MAY' => '05', 'JUN' => '06', 'JUL' => '07', 'AGO' => '08', 'SEP' => '09', 'OCT' => '10', 'NOV' => '11', 'DIC' => '12' );
    $fecha = explode('/', $fecha);
    return $fecha[2].'-'.$meses[strtoupper($fecha[1])].'-'.$fecha[0];
  }


  public function index()
  {
    $this->carabiner->js(array(
    array('general/msgbox.js'),
      array('panel/proyectos/agregar.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de proyectos'
    );

    $this->load->model('empresas_model');
    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    if(!isset($_GET['did_empresa']))
      $_GET['did_empresa'] = $params['empresa']->id_empresa;

    $this->load->model('proyectos_model');
    $params['proyectos'] = $this->proyectos_model->getProyectos();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/proyectos/admin', $params);
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
      array('panel/proyectos/agregar.js'),
    ));

    $this->load->model('empresas_model');
    $this->load->model('banco_cuentas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar proyecto'
    );

    $this->configAddModProyecto();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('proyectos_model');
      $res_mdl = $this->proyectos_model->addProyecto();

      if(!$res_mdl['error'])
        redirect(base_url('panel/proyectos/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
    }

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/proyectos/agregar', $params);
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
        array('libs/jquery.treeview.css', 'screen')
      ));
      $this->carabiner->js(array(
        array('libs/jquery.uniform.min.js'),
        array('libs/jquery.treeview.js'),
        array('panel/proyectos/agregar.js'),
      ));

      $this->load->model('proyectos_model');
      $this->load->model('empresas_model');

      $params['info_empleado'] = $this->info_empleado['info']; //info empleado
      $params['seo'] = array(
        'titulo' => 'Modificar proyecto'
      );

      $this->configAddModProyecto('modificar');
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $res_mdl = $this->proyectos_model->updateProyecto($this->input->get('id'));

        if($res_mdl['error'] == FALSE)
          redirect(base_url('panel/proyectos/?'.MyString::getVarsLink(array('msg', 'id')).'&msg=4'));
      }

      $params['proyecto'] = $this->proyectos_model->getProyectoInfo();

      if (isset($_GET['msg']))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/proyectos/modificar', $params);
      $this->load->view('panel/footer');
    }
    else
      redirect(base_url('panel/proyectos/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * pone eliminado a un proveedor
   * @return [type] [description]
   */
  public function eliminar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('proyectos_model');
      $res_mdl = $this->proyectos_model->updateProyecto( $this->input->get('id'), array('status' => 'f') );
      if($res_mdl)
        redirect(base_url('panel/proyectos/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
    }
    else
      redirect(base_url('panel/proyectos/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Activa un proveedor eliminado
   * @return [type] [description]
   */
  public function activar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('proyectos_model');
      $res_mdl = $this->proyectos_model->updateProyecto( $this->input->get('id'), array('status' => 't') );
      if($res_mdl)
        redirect(base_url('panel/proyectos/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
    }
    else
      redirect(base_url('panel/proyectos/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  public function finalizar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('proyectos_model');
      $res_mdl = $this->proyectos_model->updateProyecto( $this->input->get('id'), array('fecha_terminacion' => date("Y-m-d")) );
      if($res_mdl)
        redirect(base_url('panel/proyectos/?'.MyString::getVarsLink(array('msg')).'&msg=7'));
    }
    else
      redirect(base_url('panel/proyectos/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Obtiene lostado de centros de costo para el autocomplete, ajax
   */
  public function ajax_get_proyectos(){
    $this->load->model('proyectos_model');
    $params = $this->proyectos_model->getProyectosAjax();

    echo json_encode($params);
  }

  public function imprimir()
  {
    $this->load->model('proyectos_model');

    if (isset($_GET['id']))
    {
      $this->proyectos_model->print($_GET['id']);
    }
  }



  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configAddModProyecto($accion='agregar')
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'nombre',
            'label' => 'Nombre',
            'rules' => 'required|max_length[250]'),
      array('field' => 'presupuesto',
            'label' => 'Presupuesto',
            'rules' => 'required|numeric'),
      array('field' => 'fempresa',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'did_empresa',
            'label' => 'Empresa',
            'rules' => 'required|numeric'),

      array('field' => 'fecha_inicio',
            'label' => 'Fecha de inicio',
            'rules' => ''),
      array('field' => 'fecha_terminacion',
            'label' => 'Fecha de terminación',
            'rules' => ''),



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
        $txt = 'El proyecto se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El proyecto se modificó correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'El proyecto se eliminó correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'El proyecto se activó correctamente.';
        $icono = 'success';
        break;
      case 7:
        $txt = 'El proyecto se finalizo correctamente.';
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
