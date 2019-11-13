<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class rastreabilidad_pinia extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'rastreabilidad_pinia/ajax_save_entradas/',
    'rastreabilidad_pinia/ajax_del_entradas/',
    'rastreabilidad_pinia/ajax_save_rendimiento/',
    'rastreabilidad_pinia/ajax_del_rendimiento/',
    'rastreabilidad_pinia/ajax_total_rendimiento/',
    'rastreabilidad_pinia/ajax_save_dano/',
    'rastreabilidad_pinia/ajax_save_obsinter/',
    'rastreabilidad_pinia/ajax_del_obsinter/',

    'rastreabilidad_pinia/rpl_pdf/',
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
   * Muestra la vista Rendimiento por lote.
   * @return void
   */
  public function index()
  {
    $this->carabiner->js(array(
        array('general/msgbox.js'),
        array('general/keyjump.js'),
        array('general/util.js'),
        array('libs/jquery.numeric.js'),
        array('panel/rastreabilidad/rendimiento_pina.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Rendimiento de piña'
    );

    $this->load->model('rastreabilidad_pina_model');
    $this->load->model('areas_model');

    $params['areas'] = $this->areas_model->getAreas();
    // Obtenemos area predeterminada
    $params['area_default'] = null;
    if(isset($_GET['parea']{0}))
      $params['area_default'] = $_GET['parea'];
    else{
      $params['area_default'] = 3; //piña
    }

    $params['fecha'] = isset($_GET['gfecha']) ? $_GET['gfecha'] : date('Y-m-d');

    $fecha = new DateTime($params['fecha']);

    $params['info'] = $this->rastreabilidad_pina_model->info($fecha->format('Y-m-d'), $params['area_default']);

    $params['unidades'] = $this->db->select('*')->from('unidades')->where('status', 't')->order_by('nombre')->get()->result();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/rastreabilidad/rendimiento_pina', $params);
    $this->load->view('panel/footer');
  }

  public function rpl_pdf()
  {
    if (isset($_GET['parea']{0}) && isset($_GET['gfecha']{0}))
    {
      $this->load->model('rastreabilidad_pina_model');
      $this->rastreabilidad_pina_model->rpl_pdf($_GET['parea'], $_GET['gfecha']);
    }
    else redirect(base_url('panel/rastreabilidad_pinia/?'.MyString::getVarsLink(array('msg'))));
  }

  /*
   |------------------------------------------------------------------------
   | METODOS AJAX
   |------------------------------------------------------------------------
   */
  public function ajax_save_entradas()
  {
    $this->load->model('rastreabilidad_pina_model');
    echo json_encode($this->rastreabilidad_pina_model->saveEntrada());
  }

  public function ajax_del_entradas()
  {
    $this->load->model('rastreabilidad_pina_model');
    echo json_encode($this->rastreabilidad_pina_model->delEntrada());
  }

  public function ajax_save_rendimiento()
  {
    $this->load->model('rastreabilidad_pina_model');
    echo json_encode($this->rastreabilidad_pina_model->saveRendimiento());
  }

  public function ajax_del_rendimiento()
  {
    $this->load->model('rastreabilidad_pina_model');
    echo json_encode($this->rastreabilidad_pina_model->delRendimiento());
  }

  public function ajax_total_rendimiento()
  {
    $this->load->model('rastreabilidad_pina_model');
    echo json_encode($this->rastreabilidad_pina_model->totalesRendimiento());
  }

  public function ajax_save_dano()
  {
    $this->load->model('rastreabilidad_pina_model');
    echo json_encode($this->rastreabilidad_pina_model->saveDano());
  }

  public function ajax_save_obsinter()
  {
    $this->load->model('rastreabilidad_pina_model');
    echo json_encode($this->rastreabilidad_pina_model->saveObsInter());
  }

  public function ajax_del_obsinter()
  {
    $this->load->model('rastreabilidad_pina_model');
    echo json_encode($this->rastreabilidad_pina_model->delObsInter());
  }









   /*
   |------------------------------------------------------------------------
   | REPORTES
   |------------------------------------------------------------------------
   */

  /**
   * Muestra la vista para el Reporte "REPORTE DE RASTREABILIDAD DE PRODUCTO"
   *
   * @return void
   */
  public function rrp()
  {
    $this->carabiner->js(array(
      array('general/keyjump.js'),
      array('panel/rastreabilidad/reportes/rrp.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Reporte Rastreabilidad del Producto'
    );
    $this->load->model('areas_model');
    $this->load->model('calidades_model');

    $params['areas']     = $this->areas_model->getAreas(false);
    $itm_select = '';
    foreach ($params['areas']['areas'] as $key => $itm) {
      if($itm->predeterminado == 't'){
        $itm_select = $itm;
        break;
      }
    }
    $params['calidades'] = $this->calidades_model->getCalidades($itm_select->id_area, false);

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/rastreabilidad/reportes/rrp', $params);
    $this->load->view('panel/footer');
  }

 /**
   * Procesa los datos para mostrar el reporte RASTREABILIDAD DE PRODUCTO
   * @return void
   */
  public function rrp_pdf()
  {
    $this->load->model('rastreabilidad_model');
    $this->rastreabilidad_model->rrp_pdf();
  }

  /**
   * Muestra la vista para el Reporte "REPORTE DE ENTRADA DE FRUTA"
   *
   * @return void
   */
  public function ref()
  {
    $this->carabiner->js(array(
      array('general/keyjump.js'),
      array('panel/rastreabilidad/reportes/rrp.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Reporte Rastreabilidad del Producto'
    );
    $this->load->model('areas_model');
    $this->load->model('calidades_model');

    $params['areas']     = $this->areas_model->getAreas(false);

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/rastreabilidad/reportes/ref', $params);
    $this->load->view('panel/footer');
  }

 /**
   * Procesa los datos para mostrar el reporte ENTRADA DE FRUTA
   * @return void
   */
  public function ref_pdf()
  {
    $this->load->model('rastreabilidad_model');
    $this->rastreabilidad_model->ref_pdf();
  }

  /**
   * Muestra la vista para el Reporte "REPORTE DE RASTREABILIDAD Y SEGUIMIENTO"
   *
   * @return void
   */
  public function rrs()
  {
    $this->carabiner->js(array(
      array('general/keyjump.js'),
      array('panel/rastreabilidad/reportes/rrs.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Reporte Rastreabilidad y seguimiento Producto'
    );
    $this->load->model('areas_model');
    $this->load->model('calidades_model');
    $this->load->model('rastreabilidad_model');

    $params['areas']     = $this->areas_model->getAreas(false);
    // Obtenemos area predeterminada
    $params['area_default'] = null;
    foreach ($params['areas']['areas'] as $key => $value)
    {
      if($value->predeterminado == 't')
      {
        $params['area_default'] = $value->id_area;
        break;
      }
    }
    // Obtiene los lotes de la fecha indicada
    $params['lotes'] = $this->rastreabilidad_model->getLotesByFecha(date('Y-m-d'), $params['area_default']);


    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/rastreabilidad/reportes/rrs', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Procesa los datos para mostrar el reporte ENTRADA DE FRUTA
   * @return void
   */
  public function rrs_pdf()
  {
    if(isset($_GET['ffecha1']) && isset($_GET['farea']) && isset($_GET['flotes']))
    {
      $this->load->model('rastreabilidad_model');
      $this->rastreabilidad_model->rrs_pdf();
    }
  }

  public function ajax_get_lotes()
  {
    $this->load->model('rastreabilidad_model');
    // Obtiene los lotes de la fecha indicada
    $params = $this->rastreabilidad_model->getLotesByFecha($_GET['fecha'], $_GET['area']);
    echo json_encode($params);
  }


  /**
   * Muestra la vista para el Reporte "REPORTE DE RASTREABILIDAD Y SEGUIMIENTO"
   *
   * @return void
   */
  public function rrl()
  {
    $this->carabiner->js(array(
      array('general/keyjump.js'),
      array('panel/rastreabilidad/reportes/rrs.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Reporte Rendimiento de lotes'
    );
    $this->load->model('areas_model');
    $this->load->model('calidades_model');
    $this->load->model('rastreabilidad_model');

    $params['areas']     = $this->areas_model->getAreas(false);
    // Obtenemos area predeterminada
    $params['area_default'] = null;
    foreach ($params['areas']['areas'] as $key => $value)
    {
      if($value->predeterminado == 't')
      {
        $params['area_default'] = $value->id_area;
        break;
      }
    }
    // Obtiene los lotes de la fecha indicada
    // $params['lotes'] = $this->rastreabilidad_model->getLotesByFecha(date('Y-m-d'), $params['area_default']);


    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/rastreabilidad/reportes/rrl', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Procesa los datos para mostrar el reporte ENTRADA DE FRUTA
   * @return void
   */
  public function rrl_pdf()
  {
    if(isset($_GET['ffecha1']) && isset($_GET['farea']))
    {
      $this->load->model('rastreabilidad_model');
      $this->rastreabilidad_model->rrl_pdf();
    }
  }

  public function rrl_xls()
  {
    if(isset($_GET['ffecha1']) && isset($_GET['farea']))
    {
      $this->load->model('rastreabilidad_model');
      $this->rastreabilidad_model->rrl_xls();
    }
  }


  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddModBascula()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'ptipo',
            'label' => 'Tipo',
            'rules' => 'required'),
      array('field' => 'pfolio',
            'label' => 'Folio',
            'rules' => 'required|is_natural_no_zero|callback_chkfolio'),
      array('field' => 'pfecha',
            'label' => 'Fecha',
            'rules' => 'required'),

      array('field' => 'parea',
            'label' => 'Area',
            'rules' => 'required'),
      array('field' => 'pid_empresa',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'pempresa',
            'label' => '',
            'rules' => ''),
      array('field' => 'pid_proveedor',
            'label' => 'Proveedor',
            'rules' => ''),
      array('field' => 'pproveedor',
            'label' => '',
            'rules' => ''),
      array('field' => 'pid_cliente',
            'label' => 'Cliente',
            'rules' => ''),
      array('field' => 'pcliente',
            'label' => '',
            'rules' => ''),
      array('field' => 'pid_chofer',
            'label' => 'Chofer',
            'rules' => ''),
      array('field' => 'pchofer',
            'label' => '',
            'rules' => ''),
      array('field' => 'pid_camion',
            'label' => 'Camión',
            'rules' => ''),
      array('field' => 'pcamion',
            'label' => '',
            'rules' => ''),

      array('field' => 'pkilos_brutos',
            'label' => '',
            'rules' => ''),
      array('field' => 'pkilos_tara',
            'label' => '',
            'rules' => ''),
      array('field' => 'pkilos_neto',
            'label' => '',
            'rules' => ''),

      array('field' => 'ptotal_cajas',
            'label' => '',
            'rules' => ''),
      array('field' => 'ppesada',
            'label' => '',
            'rules' => ''),
      array('field' => 'ptotal',
            'label' => '',
            'rules' => ''),
      array('field' => 'pobcervaciones',
            'label' => 'Observaciones',
            'rules' => 'max_length[254]'),

      array('field' => 'pcajas[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'pcalidad[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'pcalidadtext[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'pkilos[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'ppromedio[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'pprecio[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'pimporte[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'pcajas_prestadas',
            'label' => 'Cajas Prestadas',
            'rules' => ''),



    );

    if (isset($_POST['paccion']))
    {
      if ($_POST['paccion'] == 'n')
      {
        $rules[] = array('field' => 'pkilos_brutos',
                         'label' => 'Kilos Brutos',
                         'rules' => 'required');
      }
      else if ($_POST['paccion'] == 'en' || $_POST['paccion'] == 'sa')
      {
        $rules[] = array('field' => 'pkilos_brutos',
                         'label' => 'Kilos Brutos',
                         'rules' => 'required');

        $rules[] = array('field' => 'pkilos_tara',
                         'label' => 'Kilos Tara',
                         'rules' => 'required');

        $rules[] = array('field' => 'pkilos_neto',
                         'label' => 'Kilos Neto',
                         'rules' => 'required');
      }
    }

    if (isset($_POST['ptipo']))
    {
      if ($_POST['ptipo'] === 'en')
        $rules[] = array('field'  => 'pid_proveedor',
                          'label' => 'Proveedor',
                          'rules' => 'required');
      else
      {
        $rules[] = array('field' => 'pid_cliente',
                         'label' => 'Cliente',
                         'rules' => 'required');

        $rules[] = array('field' => 'pid_chofer',
                         'label' => 'Chofer',
                         'rules' => 'required');

        $rules[] = array('field' => 'pid_camion',
                         'label' => 'Camión',
                         'rules' => 'required');
      }
    }

    $this->form_validation->set_rules($rules);
  }

    public function chkfolio($folio){
    if ( ! isset($_GET['idb']) && ! isset($_GET['e']))
    {
      $result = $this->db->query("SELECT Count(id_bascula) AS num FROM bascula
        WHERE folio = {$folio} AND tipo = '{$this->input->post('ptipo')}'
        AND id_area = {$this->input->post('parea')}")->row();
      if($result->num > 0){
        $this->form_validation->set_message('chkfolio', 'El folio ya existe, intenta con otro.');
        return false;
      }else
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
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

/* End of file bascula.php */
/* Location: ./application/controllers/panel/bascula.php */