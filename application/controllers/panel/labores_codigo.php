<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class labores_codigo extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'labores_codigo/ajax_get_labores/',
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
    $this->carabiner->js(array(
      array('general/msgbox.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Labores'
    );

    $this->load->library('pagination');
    $this->load->model('labores_codigo_model');

    $params['labores'] = $this->labores_codigo_model->getLabores();
    // echo "<pre>";
    //   var_dump($params['categorias']);
    // echo "</pre>";exit;
    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/almacen/labores_codigos/admin', $params);
    $this->load->view('panel/footer');
  }

  public function agregar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/categorias.js'),
    ));

    $this->load->model('labores_codigo_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Labor'
    );

    $this->configAddLabor();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->labores_codigo_model->agregar($_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/labores_codigo/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=4'));
      }
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/almacen/labores_codigos/agregar', $params);
    $this->load->view('panel/footer');
  }

  public function modificar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/categorias.js'),
    ));

    $this->load->model('labores_codigo_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Modificar Categoria'
    );

    $this->configAddLabor();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->labores_codigo_model->modificar($_GET['id'], $_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/labores_codigo/modificar/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
      }
    }

    $params['categoria'] = $this->labores_codigo_model->info($_GET['id'], true);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/almacen/labores_codigos/modificar', $params);
    $this->load->view('panel/footer');
  }

  public function eliminar()
  {
    $this->load->model('labores_codigo_model');
    $this->labores_codigo_model->elimimnar($_GET['id']);

    redirect(base_url('panel/labores_codigo/?&msg=6'));
  }


  public function configAddLabor()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'nombre',
            'label' => 'Nombre',
            'rules' => 'required|max_length[120]'),
      array('field' => 'codigo',
            'label' => 'Codigo',
            'rules' => 'required|max_length[10]|callback_valid_codigo'),
    );

    $this->form_validation->set_rules($rules);
  }

  public function valid_codigo($codigo)
  {
    $sql = '';
    if (isset($_GET['id']{0})) {
      $sql = " AND id_labor <> {$_GET['id']}";
    }
    $query = $this->db->query("SELECT id_labor
                               FROM compras_salidas_labores
                               WHERE lower(codigo) = '".mb_strtolower(trim($_POST['codigo']))."' {$sql}");

    if ($query->num_rows() > 0)
    {
      $this->form_validation->set_message('valid_codigo', 'El codigo ya existe.');
      return false;
    }

    return true;
  }


  public function ajax_get_labores()
  {
    $this->load->model('labores_codigo_model');
    echo json_encode($this->labores_codigo_model->ajaxLabores());
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
        $txt = 'La información se guardo correctamente!';
        $icono = 'success';
        break;
      case 4:
        $txt = 'La labor se agrego correctamente!';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La labor se modifico correctamente!';
        $icono = 'success';
        break;
      case 6:
        $txt = 'La labor se elimino correctamente!';
        $icono = 'success';
        break;

    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}