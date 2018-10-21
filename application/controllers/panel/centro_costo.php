<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class centro_costo extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'centro_costo/ajax_get_centro_costo/',
    'centro_costo/merges/',
    'centro_costo/catalogo_xls/',
    'centro_costo/show_view_agregar_productor/'
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
      array('panel/centro_costo/agregar.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de centros de costo'
    );

    $this->load->model('empresas_model');

    $this->load->model('centros_costos_model');
    $params['centros_costos'] = $this->centros_costos_model->getCentrosCostos();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/centro_costo/admin', $params);
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
      array('panel/centro_costo/agregar.js'),
    ));

    $this->load->model('empresas_model');
    $this->load->model('banco_cuentas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar centro de costo'
    );

    $this->configAddModCentroCosto();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('centros_costos_model');
      $res_mdl = $this->centros_costos_model->addCentroCosto();

      if(!$res_mdl['error'])
        redirect(base_url('panel/centro_costo/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
    }

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    //Cuentas de banco
    $params['cuentas'] = $this->banco_cuentas_model->getCuentas(false, null, array('id_empresa' => $params['empresa']->id_empresa));

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/centro_costo/agregar', $params);
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
        array('panel/centro_costo/agregar.js'),
      ));

      $this->load->model('centros_costos_model');
      $this->load->model('empresas_model');

      $params['info_empleado'] = $this->info_empleado['info']; //info empleado
      $params['seo'] = array(
        'titulo' => 'Modificar centro de costo'
      );

      $this->configAddModCentroCosto('modificar');
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $res_mdl = $this->centros_costos_model->updateCentroCosto($this->input->get('id'));

        if($res_mdl['error'] == FALSE)
          redirect(base_url('panel/centro_costo/?'.MyString::getVarsLink(array('msg', 'id')).'&msg=4'));
      }

      $params['centro_costo'] = $this->centros_costos_model->getCentroCostoInfo();

      if (isset($_GET['msg']))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/centro_costo/modificar', $params);
      $this->load->view('panel/footer');
    }
    else
      redirect(base_url('panel/centro_costo/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * pone eliminado a un proveedor
   * @return [type] [description]
   */
  public function eliminar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('centros_costos_model');
      $res_mdl = $this->centros_costos_model->updateCentroCosto( $this->input->get('id'), array('status' => 'f') );
      if($res_mdl)
        redirect(base_url('panel/centro_costo/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
    }
    else
      redirect(base_url('panel/centro_costo/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Activa un proveedor eliminado
   * @return [type] [description]
   */
  public function activar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('centros_costos_model');
      $res_mdl = $this->centros_costos_model->updateCentroCosto( $this->input->get('id'), array('status' => 't') );
      if($res_mdl)
        redirect(base_url('panel/centro_costo/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
    }
    else
      redirect(base_url('panel/centro_costo/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Obtiene lostado de productores para el autocomplete, ajax
   */
  public function ajax_get_centro_costo(){
    $this->load->model('centros_costos_model');
    $params = $this->centros_costos_model->getCentrosCostosAjax();

    echo json_encode($params);
  }

  public function catalogo_xls()
  {
    $this->load->model('centros_costos_model');
    $this->centros_costos_model->catalogo_xls();
  }



  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configAddModCentroCosto($accion='agregar')
  {
    $this->load->library('form_validation');

    $val = false;
    if ($this->input->post('tipo') == 'melga' || $this->input->post('tipo') == 'tabla' ||
        $this->input->post('tipo') == 'seccion') {
      $val = true;
    }

    $rules = array(
      array('field' => 'nombre',
            'label' => 'Nombre',
            'rules' => 'required|max_length[100]'),
      array('field' => 'tipo',
            'label' => 'Tipo',
            'rules' => 'required'),

      array('field' => 'farea',
            'label' => '',
            'rules' => ($val? 'required': '')),
      array('field' => 'did_area',
            'label' => 'Área',
            'rules' => ($val? 'required|numeric': '')),
      array('field' => 'hectareas',
            'label' => 'Hectáreas',
            'rules' => ($val? 'required|numeric': '')),
      array('field' => 'no_plantas',
            'label' => 'No de plantas',
            'rules' => ($val? 'required|numeric': '')),

      array('field' => 'anios_credito',
            'label' => 'Años del crédito',
            'rules' => ($this->input->post('tipo') == 'creditobancario'? 'required|numeric': '')),

      array('field' => 'id_cuenta',
            'label' => 'Cuenta de banco',
            'rules' => ($this->input->post('tipo') == 'banco'? 'required|numeric': '')),
      array('field' => 'cuenta',
            'label' => 'Cuenta de banco',
            'rules' => ''),
      array('field' => 'fempresa',
            'label' => 'Empresa',
            'rules' => ''),
      array('field' => 'did_empresa',
            'label' => 'Empresa',
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
        $txt = 'El centro de costo se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El centro de costo se modificó correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'El centro de costo se eliminó correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'El centro de costo se activó correctamente.';
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
