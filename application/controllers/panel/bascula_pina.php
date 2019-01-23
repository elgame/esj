<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bascula_pina extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'bascula_pina/show_view_guardar_pina/',
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

  /**
   * Muestra formulario agregar camion.
   * @return void
   */
  public function show_view_guardar_pina()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/keyjump.js'),
      array('panel/bascula/guardar_salida_pina.js'),
    ));

    $this->load->model('bascula_model');
    $this->load->model('bascula_pina_model');
    $this->load->model('calidades_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Salida de Piña'
    );

    $this->configAddSalidaPina();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $bascula = $this->bascula_pina_model->addSalidaPina($_POST);

      if($res_mdl['passes'])
        redirect(base_url('panel/bascula/show_view_guardar_pina/?'.MyString::getVarsLink(array('msg')).'&msg=15&close=1'));
    }

    $data = $this->bascula_model->getBasculaInfo($_GET['idb']);
    $params['pina'] = $this->bascula_pina_model->getInfo($_GET['idb'], "bsp.id_bascula");
    echo "<pre>";
      var_dump($params['pina']);
    echo "</pre>";exit;

    $params['boleta'] = $data['info'][0];

    $calidades = $this->calidades_model->getCalidades($params['boleta']->id_area, false);
    $params['calidades'] = $calidades['calidades'];

    $params['closeModal'] = false;
    if (isset($_GET['close']))
      $params['closeModal'] = true;

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $params['template'] = $this->load->view('panel/bascula/cultivos/agregar_pina', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
  }


  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */
  public function configAddSalidaPina()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'folio',
            'label' => 'Folio',
            'rules' => 'required|integer'),
      array('field' => 'rancho',
            'label' => 'Rancho',
            'rules' => ''),
      array('field' => 'ranchoId',
            'label' => 'Rancho',
            'rules' => 'required'),
      array('field' => 'kilos_neto',
            'label' => 'Kilos netos',
            'rules' => 'required'),
      array('field' => 'total_piezas',
            'label' => 'Piezas',
            'rules' => 'required'),
      array('field' => 'kg_pieza',
            'label' => 'Kg x Pieza',
            'rules' => 'required'),

      array('field' => 'estiba[]',
            'label' => 'Estiba',
            'rules' => 'required'),
      array('field' => 'id_centro_costo[]',
            'label' => 'Melga',
            'rules' => 'required'),
      array('field' => 'id_calidad[]',
            'label' => 'Calidad',
            'rules' => 'required'),
      array('field' => 'cantidad[]',
            'label' => 'Cantidad',
            'rules' => 'required'),
    );

    $this->form_validation->set_rules($rules);
  }

  /*
   |------------------------------------------------------------------------
   | Metodos para peticiones Ajax.
   |------------------------------------------------------------------------
   */

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
        $txt = 'La empresa se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El proveedor se agregó correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'El chofer se agregó correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'El camión se activó correctamente.';
        $icono = 'success';
        break;

      case 7:
        $txt = 'La entrada se agrego correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = 'La bascula se cancelo correctamente.';
        $icono = 'success';
        break;

      case 9:
        $txt = 'La bascula se activo correctamente.';
        $icono = 'success';
        break;

      case 10:
        $txt = 'No existe el folio especificado.';
        $icono = 'error';
        break;
      case 11:
        $txt = 'El cliente se agregó correctamente.';
        $icono = 'success';
        break;
      case 12:
        $txt = 'La bonificación se agregó correctamente.';
        $icono = 'success';
        break;
      case 13:
        $txt = 'Especifique un Proveedor!';
        $icono = 'error';
        break;
      case 14:
        $txt = 'El pago se realizo correctamente!';
        $icono = 'success';
        break;
      case 15:
        $txt = 'El lote se agrego correctamente!';
        $icono = 'success';
        break;

      case 30:
        $txt = 'Las ordenes se ligaron correctamente!';
        $icono = 'success';
        break;

      case 20:
        $txt = 'Se modifico correctamente la compra!';
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