<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class recetas extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'recetas/registrar_ordenes/',

    'recetas/ajax_get_folio/',
    'recetas/ajax_get_recetas/',



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
      'titulo' => 'Administración de Recetas'
    );

    $this->load->library('pagination');
    $this->load->model('recetas_model');

    // Obtiene los datos de la empresa predeterminada.
    $this->load->model('empresas_model');
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $params['recetas'] = $this->recetas_model->getRecetas();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['requisicion'] = false;
    $params['method']     = '';
    $params['titleBread'] = 'Administración de Recetas';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/recetas/admin', $params);
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
      array('panel/recetas/recetas_add.js'),
    ));

    $this->load->model('recetas_formulas_model');
    $this->load->model('compras_areas_model');
    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar receta'
    );

    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $params['next_folio'] = $this->recetas_formulas_model->folio($params['empresa_default']->id_empresa);

    $this->configAddReceta();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->recetas_formulas_model->agregar();

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/recetas/agregar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/recetas/agregar', $params);
    $this->load->view('panel/footer');
  }




  public function faltantes_productos()
  {
    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('general/msgbox.js'),
      array('general/util.js'),
      array('panel/compras_ordenes/admin.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Faltantes de productos'
    );

    // Obtiene los datos de la empresa predeterminada.
    $this->load->model('empresas_model');
    $params['empresa_default'] = []; //$this->empresas_model->getDefaultEmpresa();
    // if(intval($this->input->get('did_empresa')) < 1)
    //   $_GET['did_empresa'] = $params['empresa_default']->id_empresa;

    $this->load->model('recetas_model');
    $params['productos'] = $this->recetas_model->getProductosFaltantes();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['requisicion'] = false;
    $params['method']      = '';
    $params['method2']     = 'registrar_ordenes';
    $params['titleBread']  = 'Faltantes de productos';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/recetas/faltantes_productos', $params);
    $this->load->view('panel/footer');
  }

  public function registrar_ordenes()
  {
    $this->load->model('recetas_model');
    $this->recetas_model->crearOrdenesFaltantes();
    redirect(base_url('panel/recetas/faltantes_productos'));
  }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

  public function ajax_get_folio()
  {
    $this->load->model('recetas_model');
    echo $this->recetas_model->folio($_GET['ide'], $_GET['tipo']);
  }

  public function ajax_get_recetas()
  {
    $this->load->model('recetas_formulas_model');
    $formulas = $this->recetas_formulas_model->getFormulasAjax($_GET['term'], $_GET['did_empresa'], $_GET['tipo']);
    echo json_encode($formulas);
  }



  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddReceta($prereq = false)
  {
    $this->load->library('form_validation');

    $rules = array(
      ['field' => 'empresa', 'label' => 'Empresa', 'rules' => ''],
      ['field' => 'empresaId', 'label' => 'Empresa', 'rules' => 'required|numeric'],
      ['field' => 'formula', 'label' => 'Formula', 'rules' => ''],
      ['field' => 'formulaId', 'label' => 'Formula', 'rules' => ''],
      ['field' => 'area', 'label' => 'Cultivo', 'rules' => ''],
      ['field' => 'areaId', 'label' => 'Cultivo', 'rules' => ''],
      ['field' => 'rancho', 'label' => 'Rancho', 'rules' => ''],
      ['field' => 'ranchoId[]', 'label' => 'Rancho', 'rules' => ''],
      ['field' => 'ranchoText[]', 'label' => 'Rancho', 'rules' => ''],
      ['field' => 'centroCosto', 'label' => 'CentroCosto', 'rules' => ''],
      ['field' => 'centroCostoId[]', 'label' => 'CentroCosto', 'rules' => ''],
      ['field' => 'centroCostoText[]', 'label' => 'CentroCosto', 'rules' => ''],
      ['field' => 'objetivo', 'label' => 'Objetivo', 'rules' => ''],
      ['field' => 'tipo', 'label' => 'Tipo', 'rules' => ''],
      ['field' => 'folio_formula', 'label' => 'Folio_formula', 'rules' => ''],
      ['field' => 'folio', 'label' => 'Folio', 'rules' => ''],
      ['field' => 'fecha', 'label' => 'Fecha', 'rules' => ''],
      ['field' => 'a_etapa', 'label' => 'Etapa', 'rules' => ''],
      ['field' => 'a_ciclo', 'label' => 'Ciclo', 'rules' => ''],
      ['field' => 'a_dds', 'label' => 'DDS', 'rules' => ''],
      ['field' => 'a_turno', 'label' => 'Turno', 'rules' => ''],
      ['field' => 'a_via', 'label' => 'Via', 'rules' => ''],
      ['field' => 'a_aplic', 'label' => 'Aplicación', 'rules' => ''],
      ['field' => 'a_equipo', 'label' => 'Equipo', 'rules' => ''],
      ['field' => 'a_observaciones', 'label' => 'Observaciones', 'rules' => ''],
      ['field' => 'dosis_planta', 'label' => 'Dosis Planta', 'rules' => ''],
      ['field' => 'ha_bruta', 'label' => 'Ha Bruta', 'rules' => ''],
      ['field' => 'planta_ha', 'label' => 'Plantas x Ha', 'rules' => ''],
      ['field' => 'ha_neta', 'label' => 'Ha Netas', 'rules' => ''],
      ['field' => 'no_plantas', 'label' => 'No plantas', 'rules' => ''],
      ['field' => 'kg_totales', 'label' => 'Kg Total', 'rules' => ''],
      ['field' => 'carga1', 'label' => 'Carga 1', 'rules' => ''],
      ['field' => 'carga2', 'label' => 'Carga 2', 'rules' => ''],
      ['field' => 'ph', 'label' => 'PH', 'rules' => ''],

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
        $txt = 'La orden de compra se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'La orden se autorizo correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La orden se acepto correctamente.';
        $icono = 'success';
      break;
      case 6:
        $txt = 'La orden fue rechazada.';
        $icono = 'error';
      break;
      case 7:
        $txt = 'La orden se actualizo correctamente.';
        $icono = 'success';
      break;
      case 8:
        $txt = 'La orden se cancelo correctamente.';
        $icono = 'success';
      break;
      case 9:
        $txt = 'La compra se agrego correctamente.';
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