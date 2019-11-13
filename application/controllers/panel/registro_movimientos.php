<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class registro_movimientos extends MY_Controller {

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
      array('panel/polizas/registro_mov.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Registro de movimientos'
    );

    $this->load->library('pagination');
    $this->load->model('registro_movimientos_model');

    $params['polizas'] = $this->registro_movimientos_model->getPolizas();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d"));

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/registro_movimientos/admin', $params);
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
      array('general/supermodal.js'),
      array('general/util.js'),
      array('general/keyjump.js'),
      array('panel/polizas/registro_agregar.js'),
    ));

    $this->load->model('registro_movimientos_model');
    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar movimiento'
    );

    $this->configAddPoliza();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->registro_movimientos_model->agregar();

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/registro_movimientos/agregar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg'].'&print='.$res_mdl['id_poliza'] ));
      }
    }

    $params['fecha']      = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['metods_pago']  = array(
      array('nombre' => 'Transferencia', 'value' => 'transferencia'),
      array('nombre' => 'Cheque', 'value' => 'cheque'),
      array('nombre' => 'Efectivo', 'value' => 'efectivo'),
      array('nombre' => 'Deposito', 'value' => 'deposito'),
    );

    //imprimir
    $params['prints'] = isset($_GET['print'])? $_GET['print']: '';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/registro_movimientos/agregar', $params);
    $this->load->view('panel/footer');
  }

  public function modificar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      array('general/keyjump.js'),
      array('panel/polizas/registro_agregar.js'),
    ));

    $this->load->model('registro_movimientos_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Ver salida'
    );

    $this->configAddPoliza();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->registro_movimientos_model->modificar($_GET['id']);

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/registro_movimientos/modificar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    $params['poliza'] = $this->registro_movimientos_model->info($_GET['id'], true);
    //imprimir
    $params['prints'] = isset($_GET['print'])? $_GET['print']: '';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/registro_movimientos/modificar', $params);
    $this->load->view('panel/footer');
  }

  public function cancelar()
  {
    $this->load->model('registro_movimientos_model');
    $this->registro_movimientos_model->cancelar($_GET['id']);

    redirect(base_url('panel/registro_movimientos/?' . MyString::getVarsLink(array('id')).'&msg=4'));
  }

  public function imprimir()
  {
    $this->load->model('registro_movimientos_model');

    if (isset($_GET['id']))
    {
      $this->registro_movimientos_model->print_orden_compra($_GET['id']);
    }
  }
  public function imprimirticket()
  {
    $this->load->model('registro_movimientos_model');

    if (isset($_GET['id']))
    {
      $this->registro_movimientos_model->imprimir_salidaticket($_GET['id']);
    }
  }



  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddPoliza()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'empresaId',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'empresa',
            'label' => '',
            'rules' => ''),
      array('field' => 'concepto',
            'label' => 'Concepto',
            'rules' => 'max_length[200]'),

      array('field' => 'fecha',
            'label' => 'Fecha',
            'rules' => 'required'),

      array('field' => 'centroCostoId[]',
            'label' => 'Centro de costo',
            'rules' => 'required'),
      array('field' => 'centroCosto[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'tipo[]',
            'label' => 'Tipo',
            'rules' => ''),
      array('field' => 'cantidad[]',
            'label' => 'Cantidad',
            'rules' => 'required|greater_than[0]')
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
        $txt = 'El movimiento se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El movimiento se cancelo correctamente.';
        $icono = 'success';
      break;
      case 5:
        $txt = 'El movimiento se modifico correctamente.';
        $icono = 'success';
      break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}