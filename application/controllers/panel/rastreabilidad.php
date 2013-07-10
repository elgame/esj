<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class rastreabilidad extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'rastreabilidad/rrp_pdf/',
    'rastreabilidad/ref_pdf/',

    'rastreabilidad/ajax_get_clasificaciones/',
    'rastreabilidad/ajax_save_clasifi/',
    'rastreabilidad/ajax_edit_clasifi/',
    'rastreabilidad/ajax_get_prev_clasifi/',
    'rastreabilidad/ajax_del_clasifi/',

    'rastreabilidad/siguiente_lote/',

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
        array('general/msgbox.js'),
        array('panel/bascula/admin.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Rastreabilidad'
    );

    $this->load->model('bascula_model');
    $this->load->model('areas_model');

    $params['basculas'] = $this->bascula_model->getBasculas(true);
    $params['areas'] = $this->areas_model->getAreas();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/admin', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Muestra la vista Rendimiento por lote.
   * @return void
   */
  public function rendimiento_lote()
  {
    $this->carabiner->js(array(
        array('general/msgbox.js'),
        array('general/keyjump.js'),
        array('libs/jquery.numeric.js'),
        array('panel/rastreabilidad/rendimiento_lote.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Rendimiento por Lote'
    );

    $this->load->model('rastreabilidad_model');

    $params['fecha'] = isset($_GET['gfecha']) ? $_GET['gfecha'] : date('Y-m-d');

    $fecha = new DateTime($params['fecha']);

    // Obtiene la semana [01 - 52/53] y el dia de la semana [1 - 7]
    $params['semana']     = $fecha->format("W");
    $params['dia_semana'] = String::obtenerDiaSemana($fecha->format('Y-m-d')) + 1;

    // Obtiene los lotes de la fecha indicada
    $params['lotes'] = $this->rastreabilidad_model->getLotesByFecha($fecha->format('Y-m-d'));

    $params['clasificaciones'] = array('clasificaciones' => array());

    // Obtiene las clasificaciones del lote seleccionado desde el formulario
    if (isset($_GET['glote']) && $_GET['glote'] !== '')
    {
      $params['clasificaciones'] = $this->rastreabilidad_model->getLoteInfo($_GET['glote']);

      $params['lote_actual'] = intval($params['clasificaciones']['info']->lote);

      $params['ant_lote'] = $params['lote_actual'] - 1;
      $params['sig_lote'] = $params['lote_actual'] + 1;
    }
    else
    {
      // Si no selecciono ningun lote desde el formulario entonces verifica si
      // existen lotes para la fecha indicada y si existen lotes entonces
      // obtiene las clasificaciones del primer lote.
      //
      // Si no existe lote para la fecha indicada entonces crea el primer lote
      if (count($params['lotes']) > 0)
        $params['clasificaciones'] = $this->rastreabilidad_model->getLoteInfo($params['lotes'][0]->id_rendimiento);
      else
      {
        // Crea el primer lote para la fecha indicada
        // $this->rastreabilidad_model->createFirstLote($fecha->format('Y-m-d'));
       $id = $this->rastreabilidad_model->createLote($fecha->format('Y-m-d'), 1);

        // Obtiene los lotes de la fecha.
        $params['lotes'] = $this->rastreabilidad_model->getLotesByFecha($fecha->format('Y-m-d'));
      }

      //
      $_GET['glote'] = $params['lotes'][0]->id_rendimiento;

      $params['lote_actual'] = 1;

      $params['ant_lote'] = 0;
      $params['sig_lote'] = 2;
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/rastreabilidad/rendimiento_lote', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Carga el siguiente lote.
   * @return void
   */
  public function siguiente_lote()
  {
    if (isset($_GET['glote']{0}) && isset($_GET['gfecha']{0}))
    {
      $this->load->model('rastreabilidad_model');

      // Obtiene los lotes de la fecha que llega en get
      $lotes = $this->rastreabilidad_model->getLotesByFecha($_GET['gfecha']);

      // Recorre los lotes existentes en esa fecha y verifica si el siguiente
      // lote a cargar ya existe. Si existe entonces redirecciona.
      foreach ($lotes as $key => $lote)
        if (intval($lote->lote) === intval($_GET['glote']))
          redirect(base_url('panel/rastreabilidad/rendimiento_lote?gfecha='.$_GET['gfecha'].'&glote='.$lote->id_rendimiento));

      // Si no existe entonces crea el lote.
      $id_rendimiento = $this->rastreabilidad_model->createLote($_GET['gfecha'], $_GET['glote']);

      // Redirecciona con el nuevo lote.
      redirect(base_url('panel/rastreabilidad/rendimiento_lote?gfecha='.$_GET['gfecha'].'&glote='.$id_rendimiento));
    }
  }

  /*
   |------------------------------------------------------------------------
   | METODOS AJAX
   |------------------------------------------------------------------------
   */

  public function ajax_get_clasificaciones()
  {
    $this->load->model('clasificaciones_model');
    echo json_encode($this->clasificaciones_model->ajaxClasificaciones());
  }

  public function ajax_save_clasifi()
  {
  $this->load->model('rastreabilidad_model');
  echo json_encode($this->rastreabilidad_model->saveClasificacion());
  }

  public function ajax_edit_clasifi()
  {
    $this->load->model('rastreabilidad_model');
    echo json_encode($this->rastreabilidad_model->editClasificacion());
  }

  public function ajax_del_clasifi()
  {
    $this->load->model('rastreabilidad_model');
    echo json_encode($this->rastreabilidad_model->delClasificacion());
  }

  public function ajax_get_prev_clasifi()
  {
    $this->load->model('rastreabilidad_model');
    echo json_encode($this->rastreabilidad_model->getPrevClasificacion($_GET['id_rendimiento'], $_GET['id_clasificacion'], $_GET['loteActual']));
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
    $result = array_filter($params['areas']['areas'], function($itm){
        return ($itm->predeterminado == 't'? true: false);
    });
    $params['calidades'] = $this->calidades_model->getCalidades($result[0]->id_area, false);

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