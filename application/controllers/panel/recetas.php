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
    'recetas/ajax_get_calendarios/',
    'recetas/imprimir_salida/',
    'recetas/modificar_ajax/',
    'recetas/show_import_recetas_corona/',
    'recetas/rptaplicaciones_pdf/',
    'recetas/rptaplicaciones_xls/'
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

    $params['fecha'] = str_replace(' ', 'T', date("Y-m-d"));

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
      array('panel/recetas/recetas_volumenes.js'),
      array('panel/recetas/recetas_add.js'),
      array('panel/recetas/rango_centros_costo.js'),
    ));

    $this->load->model('recetas_model');
    $this->load->model('compras_areas_model');
    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar receta'
    );

    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $params['next_folio'] = $this->recetas_model->folio($params['empresa_default']->id_empresa);

    $this->configAddReceta();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->recetas_model->agregar();

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

  /**
   * Visualiza el formulario para modificar una receta.
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
      array('panel/recetas/recetas_volumenes.js'),
      array('panel/recetas/recetas_add.js'),
    ));

    $this->load->model('recetas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Modificar receta'
    );

    $params['fecha']         = str_replace(' ', 'T', date("Y-m-d H:i"));

    $this->configAddReceta();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $response = $this->recetas_model->modificar($_GET['id']);

      if ($response['passes'])
      {
        redirect(base_url('panel/recetas/?'.MyString::getVarsLink(array('msg')).'&msg='.$response['msg']));
      }
    }

    $params['receta'] = $this->recetas_model->info($_GET['id'], true);
    $params['calendarios'] = $this->recetas_model->getCalendariosAjax($params['receta']['info']->id_area);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    if (isset($_GET['print']))
      $params['print'] = true;

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/recetas/modificar', $params);
    $this->load->view('panel/footer');
  }

  public function modificar_ajax()
  {
    $response = array('passes' => false, 'msg' => '');

    $this->configUpdRecetaAjax();
    if ($this->form_validation->run() == FALSE)
    {
      $response['msg'] = preg_replace("[\n|\r|\n\r]", '', validation_errors());
    }
    else
    {
      $this->load->model('recetas_model');
      $response = $this->recetas_model->modificarAjax($_GET['id']);
    }

    echo json_encode($response);
  }

  /**
   * Visualiza el formulario para registrar una salida de receta.
   *
   * @return void
   */
  public function salida()
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
      array('panel/recetas/recetas_salida.js'),
    ));

    $this->load->model('recetas_model');
    $this->load->model('almacenes_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Salida producto de receta'
    );

    $params['fecha']         = str_replace(' ', 'T', date("Y-m-d H:i"));

    $this->configSalidaReceta();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $response = $this->recetas_model->salida($_GET['id']);

      if ($response['passes'])
      {
        redirect(base_url('panel/recetas/?'.MyString::getVarsLink(array('msg')).'&msg='.$response['msg']));
      } else {
        $params['frm_errors'] = $this->showMsgs(2, $response['msg']);
      }
    }

    $params['receta'] = $this->recetas_model->info($_GET['id'], true);
    $params['almacenes'] = $this->almacenes_model->getAlmacenes(false);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    if (isset($_GET['print']))
      $params['print'] = true;

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/recetas/salida', $params);
    $this->load->view('panel/footer');
  }

  public function salidas()
  {
    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('general/util.js'),
      array('panel/compras_ordenes/ver.js'),
      array('panel/recetas/recetas_list_salidas.js'),
    ));

    $this->load->model('recetas_model');

    // // Obtiene los datos de la empresa predeterminada.
    // $this->load->model('empresas_model');
    // $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $params['receta'] = $this->recetas_model->info($_GET['id'], true);
    $params['salidas'] = $this->recetas_model->getSalidas($_GET['id']);

    $this->load->view('panel/recetas/salidas', $params);
  }

  public function cancelar()
  {
    $this->load->model('recetas_model');
    $this->recetas_model->cancelar($_GET['id']);

    redirect(base_url('panel/recetas/?' . MyString::getVarsLink(array('id', 'w')).'&msg=8'));
  }

  public function activar()
  {
    $this->load->model('recetas_model');
    $this->recetas_model->activar($_GET['id']);

    redirect(base_url('panel/recetas/?' . MyString::getVarsLink(array('id', 'w')).'&msg=9'));
  }

  public function imprimir()
  {
    $this->load->model('recetas_model');

    $this->recetas_model->print_receta($_GET['id']);
  }

  public function imprimir_salida()
  {
    $this->load->model('recetas_model');

    $this->recetas_model->print_salidaticket($_GET['id'], $_GET['id_receta']);
  }



  public function surtir()
  {
    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('general/msgbox.js'),
      array('general/util.js'),
      array('panel/recetas/surtir_recetas.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Surtir Recetas'
    );

    $this->load->library('pagination');
    $this->load->model('recetas_model');
    $this->load->model('almacenes_model');

    if (isset($_POST['guardar'])) {
      $this->recetas_model->guardarSurtirReceta();
    } elseif (isset($_POST['requisiciones'])) {
      $respo = $this->recetas_model->crearRequisiciones();
      $_GET['msg'] = $respo['msg'];
    }

    // Obtiene los datos de la empresa predeterminada.
    $this->load->model('empresas_model');
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $params['recetas'] = $this->recetas_model->getSurtirRecetas();
    $params['almacenes'] = $this->almacenes_model->getAlmacenes(false);

    $params['fecha'] = str_replace(' ', 'T', date("Y-m-d"));

    $params['requisicion'] = false;
    $params['method']     = 'surtir';
    $params['titleBread'] = 'Surtir Recetas';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/recetas/admin_surtir', $params);
    $this->load->view('panel/footer');
  }

  public function calendario()
  {
    $this->carabiner->css(array(
      array('libs/fullcalendar.css'),
      array('libs/fullcalendar.print.css'),
      array('panel/recetas_calendario.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/fullcalendar-moment.min.js'),
      array('libs/fullcalendar.min.js'),
      array('libs/fullcalendar-lang-all.js'),
      array('general/supermodal.js'),
      array('general/msgbox.js'),
      array('general/util.js'),
      array('panel/recetas/calendarios.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Calendarios'
    );

    $this->load->library('pagination');
    $this->load->model('recetas_model');

    // Obtiene los datos de la empresa predeterminada.
    $this->load->model('empresas_model');
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $params['eventos'] = $this->recetas_model->getEventosCalendario($_GET);
    $params['calendarios'] = $this->recetas_model->getCalendariosAjax((isset($_GET['did_area'])? $_GET['did_area']: ''));

    $params['fecha'] = str_replace(' ', 'T', date("Y-m-d"));

    $params['requisicion'] = false;
    $params['method']     = 'surtir';
    $params['titleBread'] = 'Calendarios';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/recetas/calendarios', $params);
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


  public function show_import_recetas_corona()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('panel/nomina_fiscal/bonos_otros.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Nomina Fiscal'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Recetas - Importar Recetas Corona');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');

    // Obtiene la informacion de la empresa.
    $params['empresa'] = $this->empresas_model->getInfoEmpresa($_GET['id'])['info'];


    if (isset($_POST['id_empresa'])) {
      $this->configImportarRecetasCorona();
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $this->load->model('recetas_model');
        $res_mdl = $this->recetas_model->importRecetasCorona();
        $_GET['msg'] = $res_mdl['error'];

        if (isset($res_mdl['resumen']) && count($res_mdl['resumen']) > 0) {
          $params['resumen'] = $res_mdl['resumen'];
        }
        if (isset($res_mdl['resumenok']) && count($res_mdl['resumenok']) > 0) {
          $params['resumenok'] = $res_mdl['resumenok'];
        }

      }
    }

    if(isset($_GET['msg']{0}))
    {
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      if ($_GET['msg'] === '550')
      {
        $params['close'] = true;
      }
    }

    $this->load->view('panel/recetas/importar_recetas_corona', $params);
  }

  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configImportarRecetasCorona()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'id_empresa',
            'label' => 'Empresa',
            'rules' => 'required|is_natural'),
      array('field' => 'id_area',
            'label' => 'Cultivo',
            'rules' => 'required|is_natural'),
      array('field' => 'fecha',
            'label' => 'Fecha',
            'rules' => 'required'),
    );

    $this->form_validation->set_rules($rules);
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
    $this->load->model('recetas_model');
    $formulas = $this->recetas_model->getFormulasAjax($_GET['term'], $_GET['did_empresa'], $_GET['tipo']);
    echo json_encode($formulas);
  }

  public function ajax_get_calendarios()
  {
    $this->load->model('recetas_model');
    $formulas = [];
    if ($_GET['id_area'] > 0) {
      $formulas = $this->recetas_model->getCalendariosAjax($_GET['id_area']);
    }
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

    $val_datos = [
      'dosis_planta' => false, 'ha_bruta' => true, 'planta_ha' => true,
      'ha_neta' => true, 'no_plantas' => true, 'kg_totales' => false,
      'carga1' => true, 'carga2' => true, 'ph' => true,
      'dosis_equipo' => true, 'dosis_equipo_car2' => true
    ];
    if ($this->input->post('tipo') === 'kg') {
      $val_datos = [
        'dosis_planta' => true, 'ha_bruta' => false, 'planta_ha' => true,
        'ha_neta' => true, 'no_plantas' => true, 'kg_totales' => true,
        'carga1' => false, 'carga2' => false, 'ph' => false,
        'dosis_equipo' => false, 'dosis_equipo_car2' => false
      ];
    }

    $rules = array(
      ['field' => 'empresa',                'label' => 'Empresa',              'rules' => 'required'],
      ['field' => 'empresaId',              'label' => 'Empresa',              'rules' => 'required|numeric'],
      ['field' => 'empresa_ap',             'label' => 'Empresa Aplicación',   'rules' => ''],
      ['field' => 'empresaId_ap',           'label' => 'Empresa Aplicación',   'rules' => 'numeric'],
      ['field' => 'formula',                'label' => 'Formula',              'rules' => ''],
      ['field' => 'formulaId',              'label' => 'Formula',              'rules' => 'numeric'],
      ['field' => 'area',                   'label' => 'Cultivo',              'rules' => 'required'],
      ['field' => 'areaId',                 'label' => 'Cultivo',              'rules' => 'required|numeric'],
      ['field' => 'rancho',                 'label' => 'Rancho',               'rules' => ''],
      ['field' => 'ranchoId[]',             'label' => 'Rancho',               'rules' => 'required|numeric'],
      ['field' => 'ranchoText[]',           'label' => 'Rancho',               'rules' => 'required'],
      ['field' => 'centroCosto',            'label' => 'CentroCosto',          'rules' => ''],
      ['field' => 'centroCostoId[]',        'label' => 'CentroCosto',          'rules' => 'required|numeric'],
      ['field' => 'centroCostoText[]',      'label' => 'CentroCosto',          'rules' => 'required'],
      ['field' => 'centroCostoHec[]',       'label' => 'CentroCosto',          'rules' => ''],
      ['field' => 'centroCostoNoplantas[]', 'label' => 'CentroCosto',          'rules' => ''],
      ['field' => 'objetivo',               'label' => 'Objetivo',             'rules' => ''],
      ['field' => 'tipo',                   'label' => 'Tipo',                 'rules' => 'required'],
      ['field' => 'folio_formula',          'label' => 'Folio formula',        'rules' => 'numeric'],
      ['field' => 'folio',                  'label' => 'Folio',                'rules' => 'required|numeric'],
      ['field' => 'folio_hoja',             'label' => 'Folio Hoja',           'rules' => 'max_length[15]'],
      ['field' => 'fecha',                  'label' => 'Fecha',                'rules' => 'required'],
      ['field' => 'solicito',               'label' => 'solicito',             'rules' => 'required'],
      ['field' => 'solicitoId',             'label' => 'solicitoId',           'rules' => 'required|numeric'],
      ['field' => 'autorizo',               'label' => 'autorizo',             'rules' => 'required'],
      ['field' => 'autorizoId',             'label' => 'autorizoId',           'rules' => 'required|numeric'],

      ['field' => 'a_etapa',                'label' => 'Etapa',                'rules' => 'max_length[40]'],
      ['field' => 'a_ciclo',                'label' => 'Ciclo',                'rules' => 'max_length[40]'],
      ['field' => 'a_dds',                  'label' => 'DDS',                  'rules' => 'max_length[40]'],
      ['field' => 'a_turno',                'label' => 'Turno',                'rules' => 'max_length[40]'],
      ['field' => 'a_via',                  'label' => 'Via',                  'rules' => 'max_length[40]'],
      ['field' => 'a_aplic',                'label' => 'Aplicación',           'rules' => 'max_length[40]'],
      ['field' => 'a_equipo',               'label' => 'Equipo',               'rules' => 'max_length[40]'],
      ['field' => 'a_observaciones',        'label' => 'Observaciones',        'rules' => ''],
      ['field' => 'fecha_aplicacion',       'label' => 'Fecha Aplicación',     'rules' => ''],
      ['field' => 'calendario',             'label' => 'Calendario',           'rules' => 'required'],

      ['field' => 'ar_semana',              'label' => 'Semana',               'rules' => ''],
      ['field' => 'ar_fecha',               'label' => 'Fecha',                'rules' => ''],
      ['field' => 'ar_ph',                  'label' => 'Ph',                   'rules' => ''],

      ['field' => 'dosis_planta',           'label' => 'Dosis Planta',         'rules' => ($val_datos['dosis_planta']? 'required': '')],
      ['field' => 'ha_bruta',               'label' => 'Ha Bruta',             'rules' => ($val_datos['ha_bruta']? 'required': '')],
      ['field' => 'planta_ha',              'label' => 'Plantas x Ha',         'rules' => ($val_datos['planta_ha']? 'required': '')],
      ['field' => 'ha_neta',                'label' => 'Ha Netas',             'rules' => ($val_datos['ha_neta']? 'required': '')],
      ['field' => 'no_plantas',             'label' => 'No plantas',           'rules' => ($val_datos['no_plantas']? 'required': '')],
      ['field' => 'kg_totales',             'label' => 'Kg Total',             'rules' => ($val_datos['kg_totales']? 'required': '')],
      ['field' => 'carga1',                 'label' => 'Carga 1',              'rules' => ($val_datos['carga1']? 'required': '')],
      ['field' => 'carga2',                 'label' => 'Carga 2',              'rules' => ($val_datos['carga2']? 'required': '')],
      ['field' => 'ph',                     'label' => 'PH',                   'rules' => ($val_datos['ph']? 'required': '')],
      ['field' => 'dosis_equipo',           'label' => 'Dosis Equipo Carga',   'rules' => ($val_datos['dosis_equipo']? 'required': '')],
      ['field' => 'dosis_equipo_car2',      'label' => 'Lts de Cargas Extras', 'rules' => ($val_datos['dosis_equipo_car2']? 'required': '')],

      ['field' => 'percent[]',              'label' => 'PH',                   'rules' => ''],
      ['field' => 'concepto[]',             'label' => 'PH',                   'rules' => ''],
      ['field' => 'productoId[]',           'label' => 'PH',                   'rules' => ''],
      ['field' => 'cantidad[]',             'label' => 'PH',                   'rules' => ''],
      ['field' => 'aplicacion_total[]',     'label' => 'PH',                   'rules' => ''],
      ['field' => 'precio[]',               'label' => 'PH',                   'rules' => ''],
      ['field' => 'importe[]',              'label' => 'PH',                   'rules' => ''],

      ['field' => 'total_importe',          'label' => 'Importe',              'rules' => ''],

    );

    $this->form_validation->set_rules($rules);
  }

  public function configUpdRecetaAjax($prereq = false)
  {
    $this->load->library('form_validation');

    $rules = array(
      ['field' => 'ar_semana',              'label' => 'Semana',               'rules' => 'required|numeric'],
      ['field' => 'ar_fecha',               'label' => 'Fecha',                'rules' => 'required'],
      ['field' => 'ar_ph',                  'label' => 'Ph',                   'rules' => 'numeric'],
    );

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


  public function configSalidaReceta($prereq = false)
  {
    $this->load->library('form_validation');

    $val_datos = [
      'carga_salida' => true, 'plantas_salida' => false
    ];
    if ($this->input->post('tipo') === 'kg') {
      $val_datos = [
        'carga_salida' => false, 'plantas_salida' => true
      ];
    }

    $rules = array(
      ['field' => 'empresa',                  'label' => 'Empresa',                  'rules' => 'required'],
      ['field' => 'empresaId',                'label' => 'Empresa',                  'rules' => 'required|numeric'],
      ['field' => 'formula',                  'label' => 'Formula',                  'rules' => ''],
      ['field' => 'formulaId',                'label' => 'Formula',                  'rules' => 'numeric'],
      ['field' => 'area',                     'label' => 'Cultivo',                  'rules' => 'required'],
      ['field' => 'areaId',                   'label' => 'Cultivo',                  'rules' => 'required|numeric'],
      ['field' => 'tipo',                     'label' => 'Tipo',                     'rules' => 'required'],
      ['field' => 'folio_formula',            'label' => 'Folio formula',            'rules' => 'numeric'],
      ['field' => 'folio',                    'label' => 'Folio',                    'rules' => 'required|numeric'],
      ['field' => 'fecha',                    'label' => 'Fecha',                    'rules' => 'required'],
      ['field' => 'almacenId',                'label' => 'Almacén',                  'rules' => 'required'],
      ['field' => 'boletasSalidasId',         'label' => 'Boleta',                   'rules' => 'required'],
      ['field' => 'carga_salida',             'label' => 'Cargas',                   'rules' => ($val_datos['carga_salida']? 'required': '')],
      ['field' => 'plantas_salida',           'label' => 'Cargas',                   'rules' => ($val_datos['plantas_salida']? 'required': '')],

      ['field' => 'percent[]',                'label' => 'P %',                      'rules' => ''],
      ['field' => 'concepto[]',               'label' => 'P concepto',               'rules' => ''],
      ['field' => 'productoId[]',             'label' => 'P producto',               'rules' => ''],
      ['field' => 'cantidad[]',               'label' => 'P cantidad',               'rules' => ''],
      ['field' => 'aplicacion_total[]',       'label' => 'P A.total',                'rules' => ''],
      ['field' => 'precio[]',                 'label' => 'P precio',                 'rules' => ''],
      ['field' => 'importe[]',                'label' => 'P importe',                'rules' => ''],
      ['field' => 'aplicacion_total_saldo[]', 'label' => 'P importe', 'rules' => ''],

      ['field' => 'total_importe',            'label' => 'Importe',       'rules' => ''],

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
    $txt = '';
    $icono = 'error';
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
        $txt = 'La receta se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'La receta se autorizo correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La receta se acepto correctamente.';
        $icono = 'success';
      break;
      case 6:
        $txt = 'La receta fue rechazada.';
        $icono = 'error';
      break;
      case 7:
        $txt = 'La receta se actualizo correctamente.';
        $icono = 'success';
      break;
      case 8:
        $txt = 'La receta se cancelo correctamente.';
        $icono = 'success';
      break;
      case 9:
        $txt = 'La receta se activo correctamente.';
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

      case 500:
        $txt = 'Las recetas se guardaron correctamente.';
        $icono = 'success';
        break;
      case 501:
        $txt = 'Ocurrió un error al subir el archivo de recetas.';
        $icono = 'error';
        break;
      case 502:
        $txt = 'Ocurrió un error al leer el archivo de recetas.';
        $icono = 'error';
        break;
      case 503:
        $txt = 'Algunas recetas no se guardaron, revisar el detalle de errores.';
        $icono = 'error';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

  /*-------------------------------------------
   --------------- Rpt ----------------
   -------------------------------------------*/

  public function rptaplicaciones()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de Aplicaciones');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/recetas/rptaplicaciones',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rptaplicaciones_pdf(){
    $this->load->model('inventario_model');
    $this->inventario_model->getCProveedorPdf();
  }
  public function rptaplicaciones_xls(){
    $this->load->model('inventario_model');
    $this->inventario_model->getCProveedorXls();
  }
}