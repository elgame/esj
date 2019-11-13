<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class recetas_formulas extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'recetas_formulas/ajax_get_folio/',
    'recetas_formulas/ajax_get_formulas/',
    'recetas_formulas/ajax_get_formula/',



    'compras_requisicion/ajax_producto_by_codigo/',
    'compras_requisicion/ajax_producto/',
    'compras_requisicion/ajax_get_producto_all/',
    'compras_requisicion/ajax_get_tipo_cambio/',

    'compras_requisicion/ligar/',
    'compras_requisicion/imprimir_recibo_faltantes/',
    'compras_requisicion/ajaxGetFactRem/',
    'compras_requisicion/imprimir_entrada/',
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
      array('general/util.js'),
      array('panel/recetas/formulas.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Formulas'
    );

    $this->load->library('pagination');
    $this->load->model('recetas_formulas_model');

    // Obtiene los datos de la empresa predeterminada.
    $this->load->model('empresas_model');
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $params['formulas'] = $this->recetas_formulas_model->getFormulas();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['requisicion'] = false;
    $params['method']     = '';
    $params['titleBread'] = 'Administración de Formulas';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/recetas/formulas/admin', $params);
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
      array('panel/tags.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      array('general/keyjump.js'),
      array('panel/recetas/formulas_add.js'),
    ));

    $this->load->model('recetas_formulas_model');
    $this->load->model('compras_areas_model');
    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar formula'
    );

    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $params['next_folio'] = $this->recetas_formulas_model->folio($params['empresa_default']->id_empresa);

    $this->configAddFormula();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->recetas_formulas_model->agregar();

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/recetas_formulas/agregar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/recetas/formulas/agregar', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Visualiza el formulario para modificar una formula.
   *
   * @return void
   */
  public function modificar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
      array('panel/tags.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      array('general/keyjump.js'),
      array('panel/recetas/formulas_add.js'),
    ));

    $this->load->model('recetas_formulas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Modificar formula'
    );

    $params['fecha']         = str_replace(' ', 'T', date("Y-m-d H:i"));

    $this->configAddFormula();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $response = $this->recetas_formulas_model->actualizar($_GET['id']);

      if ($response['passes'])
      {
        redirect(base_url('panel/recetas_formulas/?'.MyString::getVarsLink(array('msg')).'&msg='.$response['msg']));
      }
    }

    $params['formula'] = $this->recetas_formulas_model->info($_GET['id'], true);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    if (isset($_GET['print']))
      $params['print'] = true;

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/recetas/formulas/modificar', $params);
    $this->load->view('panel/footer');
  }

  // public function ver()
  // {
  //   redirect(base_url('panel/compras_ordenes/modificar/?' . MyString::getVarsLink()));
  // }

  public function cancelar()
  {
    $this->load->model('recetas_formulas_model');
    $this->recetas_formulas_model->cancelar($_GET['id']);

    redirect(base_url('panel/recetas_formulas/?' . MyString::getVarsLink(array('id', 'w')).'&msg=8'));
  }

  public function activar()
  {
    $this->load->model('recetas_formulas_model');
    $this->recetas_formulas_model->activar($_GET['id']);

    redirect(base_url('panel/recetas_formulas/?' . MyString::getVarsLink(array('id', 'w')).'&msg=9'));
  }

  public function imprimir()
  {
    $this->load->model('recetas_formulas_model');

    if (isset($_GET['p']))
    {
      $this->recetas_formulas_model->print_pre_orden_compra($_GET['id']);
    }
    else
    {
      $this->load->view('panel/compras_ordenes/print_orden_compra');
    }
  }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

  public function ajax_get_folio()
  {
    $this->load->model('recetas_formulas_model');
    echo $this->recetas_formulas_model->folio($_GET['ide'], $_GET['tipo']);
  }

  public function ajax_get_formulas()
  {
    $this->load->model('recetas_formulas_model');
    $formulas = $this->recetas_formulas_model->getFormulasAjax($_GET['term'], $_GET['did_empresa'], $_GET['tipo']);
    echo json_encode($formulas);
  }

  public function ajax_get_formula()
  {
    $this->load->model('recetas_formulas_model');
    $formula = $this->recetas_formulas_model->info($_GET['id'], true);
    echo json_encode($formula);
  }

  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddFormula($prereq = false)
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'empresaId',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'empresa',
            'label' => '',
            'rules' => ''),
      array('field' => 'nombre',
            'label' => 'Nombre',
            'rules' => 'required'),
      array('field' => 'areaId',
            'label' => 'Cultivo',
            'rules' => 'required|numeric'),
      array('field' => 'area',
            'label' => 'Cultivo',
            'rules' => 'required'),

      array('field' => 'tipo',
            'label' => 'Tipo',
            'rules' => 'required'),
      array('field' => 'folio',
            'label' => 'Folio',
            'rules' => 'required'),

      array('field' => 'cantidad[]',
            'label' => 'Cantidad',
            'rules' => 'required|numeric'),
      array('field' => 'percent[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'concepto[]',
            'label' => 'Producto',
            'rules' => 'required'),
      array('field' => 'productoId[]',
            'label' => 'Producto',
            'rules' => 'required'),
    );

    // if($this->input->post('tipoOrden') == 'f')
    // {
    //   $rules[] = array('field' => 'fleteDe',
    //                 'label' => 'Flete de',
    //                 'rules' => 'required');
    //   if($this->input->post('fleteDe') === 'v') {
    //     $rules[] = array('field' => 'remfacs',
    //                   'label' => 'Factura/Remision',
    //                   'rules' => 'required');
    //     $rules[] = array('field' => 'remfacs_folio',
    //                   'label' => 'Factura/Remision',
    //                   'rules' => '');
    //   } else {
    //     $rules[] = array('field' => 'boletas',
    //                   'label' => 'Boletas',
    //                   'rules' => 'required');
    //     $rules[] = array('field' => 'boletas_folio',
    //                   'label' => 'Boletas',
    //                   'rules' => '');
    //   }
    // }

    $this->form_validation->set_rules($rules);
  }

  public function serie_folio($folio)
  {
    $serie = mb_strtoupper($this->input->post('serie'), 'utf-8');
    $query = $this->db->query("SELECT Count(id_compra) AS num FROM compras WHERE status <> 'ca' AND folio = {$folio} AND UPPER(serie) = '{$serie}'
      AND id_empresa = ".$this->input->post('empresaId')." AND id_proveedor = ".$this->input->post('proveedorId')."  ".
      (isset($_GET['id']{0})? " AND id_compra <> ".$_GET['id']: '') )->row();
    if ($query->num > 0)
    {
      $this->form_validation->set_message('serie_folio', 'El %s ya esta asignado.');
      return false;
    }
    else
    {
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
        $txt = 'La formula se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'La formula se autorizo correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La formula se acepto correctamente.';
        $icono = 'success';
      break;
      case 6:
        $txt = 'La formula fue rechazada.';
        $icono = 'error';
      break;
      case 7:
        $txt = 'La formula se actualizo correctamente.';
        $icono = 'success';
      break;
      case 8:
        $txt = 'La formula se cancelo correctamente.';
        $icono = 'success';
      break;
      case 9:
        $txt = 'La formula se activo correctamente.';
        $icono = 'success';
      break;
      case 10:
        $txt = 'El email se envio correctamente.';
        $icono = 'success';
      break;
      case 11:
        $txt = 'El email no se pudo enviar porque el proveedor no cuenta con un email.';
        $icono = 'error';
      break;

      case 30:
        $txt = 'La cuenta no tiene saldo suficiente.';
        $icono = 'error';
      break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}