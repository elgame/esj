<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class resguardos_activos extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array('productores/ajax_get_productores/',
    'productores/merges/',
    'productores/catalogo_xls/',
    'productores/show_view_agregar_productor/');

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
      array('panel/clientes/agregar.js'),
    ));

    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Cliente'
    );

    $this->configAddModCliente();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('resguardos_activos_model');
      $res_mdl = $this->resguardos_activos_model->addProductor();

      if(!$res_mdl['error'])
        redirect(base_url('panel/productores/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
    }

    $params['empresa']       = $this->empresas_model->getDefaultEmpresa();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/productores/agregar', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Muestra formulario agregar area.
   * @return void
   */
  public function show_view_agregar_productor()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));
    $this->carabiner->js(array(
        array('libs/jquery.uniform.min.js'),
      array('panel/clientes/agregar.js'),
    ));

    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Productor'
    );

    $this->configAddModCliente();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('resguardos_activos_model');
      $res_mdl = $this->resguardos_activos_model->addProductor();

      if(!$res_mdl['error'])
        redirect(base_url('panel/productores/show_view_agregar_productor/?'.MyString::getVarsLink(array('msg')).'&msg=3&close=1'));
    }

    $params['closeModal'] = false;
    if (isset($_GET['close']))
      $params['closeModal'] = true;

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $params['empresa']       = $this->empresas_model->getDefaultEmpresa();
    $params['method'] = 'show_view_agregar_productor';

    $params['template'] = $this->load->view('panel/productores/agregar', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
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
        array('panel/usuarios/add_mod_frm.js')
      ));

      $this->load->model('resguardos_activos_model');
      $this->load->model('empresas_model');

      $params['info_empleado'] = $this->info_empleado['info']; //info empleado
      $params['seo'] = array(
        'titulo' => 'Modificar productor'
      );

      $this->configAddModCliente('modificar');
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $res_mdl = $this->resguardos_activos_model->updateProductor($this->input->get('id'));

        if($res_mdl['error'] == FALSE)
          redirect(base_url('panel/productores/?'.MyString::getVarsLink(array('msg', 'id')).'&msg=4'));
      }

      $params['productor'] = $this->resguardos_activos_model->getProductorInfo();

      $params['empresa']       = $this->empresas_model->getInfoEmpresa($params['productor']['info']->id_empresa);

      if (isset($_GET['msg']))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/productores/modificar', $params);
      $this->load->view('panel/footer');
    }
    else
      redirect(base_url('panel/productores/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
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
      $res_mdl = $this->resguardos_activos_model->updateProductor( $this->input->get('id'), array('status' => 'e') );
      if($res_mdl)
        redirect(base_url('panel/productores/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
    }
    else
      redirect(base_url('panel/productores/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
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
      $res_mdl = $this->resguardos_activos_model->updateProductor( $this->input->get('id'), array('status' => 'ac') );
      if($res_mdl)
        redirect(base_url('panel/productores/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
    }
    else
      redirect(base_url('panel/productores/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Obtiene lostado de productores para el autocomplete, ajax
   */
  public function ajax_get_productores(){
    $this->load->model('resguardos_activos_model');
    $params = $this->resguardos_activos_model->getProductorAjax();

    echo json_encode($params);
  }

  public function catalogo_xls()
  {
    $this->load->model('resguardos_activos_model');
    $this->resguardos_activos_model->catalogo_xls();
  }



  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configAddModCliente($accion='agregar')
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fnombre_fiscal',
            'label' => 'Nombre fiscal',
            'rules' => 'required|max_length[140]'),
      array('field' => 'fcalle',
            'label' => 'Calle',
            'rules' => 'max_length[60]'),
      array('field' => 'fno_exterior',
            'label' => 'No. exterior',
            'rules' => 'max_length[7]'),
      array('field' => 'fno_interior',
            'label' => 'No. interior',
            'rules' => 'max_length[7]'),
      array('field' => 'fcolonia',
            'label' => 'Colonia',
            'rules' => 'max_length[60]'),
      array('field' => 'flocalidad',
            'label' => 'Localidad',
            'rules' => 'max_length[45]'),
      array('field' => 'fmunicipio',
            'label' => 'Municipio',
            'rules' => 'max_length[45]'),
      array('field' => 'festado',
            'label' => 'Estado',
            'rules' => 'max_length[45]'),
      array('field' => 'fpais',
            'label' => 'Pais',
            'rules' => 'max_length[25]'),

      array('field' => 'fparcela',
            'label' => 'Parcela',
            'rules' => 'max_length[20]'),
      array('field' => 'fejido_parcela',
            'label' => 'Ejido parcela',
            'rules' => 'max_length[150]'),
      array('field' => 'fcp',
            'label' => 'CP',
            'rules' => 'max_length[10]'),
      array('field' => 'ftelefono',
            'label' => 'Telefono',
            'rules' => 'max_length[15]'),
      array('field' => 'fcelular',
            'label' => 'Celular',
            'rules' => 'max_length[20]'),
      array('field' => 'ftipo',
            'label' => 'Metodo de Pago',
            'rules' => 'max_length[40]'),
      array('field' => 'femail',
            'label' => 'Email',
            'rules' => 'max_length[600]'),

      array('field' => 'fcuenta_cpi',
            'label' => 'Cuenta ContpaqI',
            'rules' => 'max_length[12]'),

      array('field' => 'fempresa',
            'label' => '',
            'rules' => ''),
      array('field' => 'did_empresa',
            'label' => 'Empresa',
            'rules' => ''),

      array('field' => 'no_coeplim',
            'label' => '# Coeplim',
            'rules' => 'max_length[55]'),
      array('field' => 'hectareas',
            'label' => '# Hectareas',
            'rules' => 'is_natural|max_length[15]'),
      array('field' => 'pequena_propiedad',
            'label' => 'Pequeña propiedad',
            'rules' => 'max_length[55]'),
      array('field' => 'propietario',
            'label' => 'Propietario',
            'rules' => 'max_length[150]'),
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
        $txt = 'El productor se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El productor se modificó correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'El productor se eliminó correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'El productor se activó correctamente.';
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
