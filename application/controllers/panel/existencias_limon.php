<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class existencias_limon extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'existencias_limon/cargar/',
    'existencias_limon/guardar/',
    'existencias_limon/ajax_get_categorias/',
    'existencias_limon/cerrar_caja/',
    'existencias_limon/print_caja/',
    'existencias_limon/rpt_gastos_pdf/',
    'existencias_limon/rpt_gastos_xls/',
    'existencias_limon/rpt_ingresos_pdf/',
    'existencias_limon/rpt_ingresos_xls/',
    'existencias_limon/print_vale/',
    'existencias_limon/print_fondo/',
    'existencias_limon/print_prestamolp/',
    'existencias_limon/print_prestamocp/',
    'existencias_limon/ajax_saldar_adeudos/',
    'existencias_limon/abono_prestamo_cp/',
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
    // $this->carabiner->js(array(
    //   array('')
    // ));

    $this->load->library('pagination');
    $this->load->model('areas_model');

    $params['info_empleado'] = $this->info_empleado['info'];
    $params['seo']           = array('titulo' => 'Reporte de Existencia de Limon');

    $params['areas'] = $this->areas_model->getAreas();

    // $this->db->query("SELECT refreshallmaterializedviews();");

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/existencia_limon/existencia_limon', $params);
    $this->load->view('panel/footer',$params);
  }

  public function cargar()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('libs/jquery.filtertable.min.js'),
      array('general/msgbox.js'),
      array('general/util.js'),
      array('panel/otros/rpt_existencias_limon.js'),
      array('panel/caja_chica/areas_requisicion.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('caja_chica_model');
    $this->load->model('existencias_limon_model');
    $this->load->model('compras_areas_model');

    $this->configGuardaCajaChica();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->existencias_limon_model->guardar($_POST);

      if(!$res_mdl['error'])
        redirect(base_url('panel/existencias_limon/cargar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
    }

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Existencia de Limon');

    $fecha = isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d');
    $_GET['ffecha'] = $fecha;
    $area = isset($_GET['farea']) ? $_GET['farea'] : 2;

    $params['unidades'] = $this->db->select('*')
      ->from('unidades')
      ->where('status', 't')
      ->order_by('nombre')
      ->get()->result();

    $params['caja'] = $this->existencias_limon_model->get($fecha, (isset($_GET['fno_caja'])? $_GET['fno_caja']: '1'), $area );

    $params['areas'] = $this->compras_areas_model->getTipoAreas();

    $params['priv_saldar_prestamo'] = $this->usuarios_model->tienePrivilegioDe('', 'existencias_limon/saldar_prestamos/');

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/existencia_limon/generar_exis_limon', $params);
  }


  public function configGuardaCajaChica()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fecha_caja_chica',
            'label' => '',
            'rules' => 'required'),
      array('field' => 'farea',
            'label' => '',
            'rules' => 'required'),
      array('field' => 'saldo_inicial',
            'label' => 'Saldo Inicial',
            'rules' => 'required|numeric'),
      array('field' => 'fno_caja',
            'label' => 'No Caja',
            'rules' => 'required|numeric'),
    );

    if (isset($_POST['produccion_costo']))
    {
      $rules[] = array('field' => 'produccion_costo[]',
                      'label' => 'Produccion costo',
                      'rules' => '');
      $rules[] = array('field' => 'produccion_id_produccion[]',
                      'label' => 'Produccion',
                      'rules' => '');
      $rules[] = array('field' => 'produccion_id_calibre[]',
                      'label' => 'Produccion calibre',
                      'rules' => 'required|numeric');
      $rules[] = array('field' => 'produccion_id_unidad[]',
                      'label' => 'Produccion unidad',
                      'rules' => 'required|numeric');
      // $rules[] = array('field' => 'produccion_kilos[]',
      //                 'label' => 'Produccion kilos',
      //                 'rules' => 'required|numeric');
      // $rules[] = array('field' => 'produccion_cantidad[]',
      //                 'label' => 'Producción Cantidad',
      //                 'rules' => 'required|numeric');
      $rules[] = array('field' => 'produccion_importe[]',
                      'label' => 'Producción importe',
                      'rules' => 'required|numeric');
    }

    $this->form_validation->set_rules($rules);
  }


  public function cerrar_caja()
  {
    $this->load->model('existencias_limon_model');
    $this->existencias_limon_model->cerrarCaja($_GET['id'], $_GET['fno_caja']);

    redirect(base_url('panel/caja_chica_prest/cargar/?'.MyString::getVarsLink(array('id', 'msg')).'&msg=7'));
  }

  public function print_caja()
  {
    $this->load->model('existencias_limon_model');
    $this->existencias_limon_model->printCaja($_GET['ffecha'], $_GET['fno_caja'], $_GET['farea']);
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
        $txt = 'La categoria se agrego correctamente!';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La categoria se modifico correctamente!';
        $icono = 'success';
        break;
      case 6:
        $txt = 'La categoria se elimino correctamente!';
        $icono = 'success';
        break;
      case 7:
        $txt = 'La caja chica se cerro correctamente!';
        $icono = 'success';
        break;

      case 8:
        $txt = 'La nomenclatura se agrego correctamente!';
        $icono = 'success';
        break;
      case 9:
        $txt = 'La nomenclatura se modifico correctamente!';
        $icono = 'success';
        break;
      case 10:
        $txt = 'La nomenclatura se elimino correctamente!';
        $icono = 'success';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}