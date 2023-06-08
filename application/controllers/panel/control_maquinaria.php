<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class control_maquinaria extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'control_maquinaria/ajax_save/',
    'control_maquinaria/ajax_delete/',

    'control_maquinaria/rptcombustible_pdf/',
    'control_maquinaria/rptcombustible_xls/',
    'control_maquinaria/rptcombustibleAcumulado_pdf/',
    'control_maquinaria/rptcombustibleAcumulado_xls/',
    'control_maquinaria/ajax_get_implemento/',

    'control_maquinaria/rptGastosActivos/',
    'control_maquinaria/rptGastosActivos_pdf/',
    'control_maquinaria/rptGastosActivos_xls/',
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
        array('libs/jquery.numeric.js'),
        array('panel/almacen/control_maquinaria.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Control de maquinaria'
    );

    $this->load->model('control_maquinaria_model');
    $this->load->model('compras_areas_model');

    $params['fecha'] = isset($_GET['gfecha']) ? $_GET['gfecha'] : date('Y-m-d');

    $params['combustible'] = $this->control_maquinaria_model->info($params['fecha']);

    $params['areas'] = $this->compras_areas_model->getTipoAreas();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/almacen/control_maquinaria/admin', $params);
    $this->load->view('panel/footer');
  }

  public function rptcombustible()
  {
    $this->carabiner->css(array(
      array('libs/jquery.treeview.css', 'screen')
    ));
    $this->carabiner->js(array(
      array('libs/jquery.treeview.js'),
      array('panel/facturacion/admin.js'),
      array('panel/almacen/rpt_combustible.js'),
    ));

    $this->load->model('compras_areas_model');
    $this->load->model('productos_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte Combutible');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    // $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    $this->compras_areas_model->class_treeAreas = 'treeviewcustom';
    $params['vehiculos'] = $this->compras_areas_model->getFrmAreas(620);
    $params['grupos'] = $this->productos_model->getGruposActivos();

    $this->load->view('panel/header',$params);
    // $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/almacen/control_maquinaria/rptcombustible', $params);
    $this->load->view('panel/footer',$params);
  }
  public function rptcombustible_pdf()
  {
    $this->load->model('control_maquinaria_model');
    $this->control_maquinaria_model->rptcombustible_pdf();
  }
  public function rptcombustible_xls()
  {
    $this->load->model('control_maquinaria_model');
    $this->control_maquinaria_model->rptcombustible_xls();
  }

  public function rptGastosActivos()
  {
    $this->carabiner->css(array(
      array('libs/jquery.treeview.css', 'screen')
    ));
    $this->carabiner->js(array(
      array('libs/jquery.treeview.js'),
      array('panel/facturacion/admin.js'),
      array('panel/almacen/rpt_combustible.js'),
    ));

    $this->load->model('compras_areas_model');
    $this->load->model('productos_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte Gastos');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    // $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    $this->compras_areas_model->class_treeAreas = 'treeviewcustom';
    $params['vehiculos'] = $this->compras_areas_model->getFrmAreas(620);
    $params['grupos'] = $this->productos_model->getGruposActivos();

    $this->load->view('panel/header',$params);
    // $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/almacen/control_maquinaria/rptGastosActivos', $params);
    $this->load->view('panel/footer',$params);
  }
  public function rptGastosActivos_pdf()
  {
    $this->load->model('control_maquinaria_model');
    $this->control_maquinaria_model->rptGastosActivos_pdf();
  }
  public function rptGastosActivos_xls()
  {
    $this->load->model('control_maquinaria_model');
    $this->control_maquinaria_model->rptcombustible_xls();
  }

  public function rptcombustibleAcumulado()
  {
    $this->carabiner->css(array(
      array('libs/jquery.treeview.css', 'screen')
    ));
    $this->carabiner->js(array(
      array('libs/jquery.treeview.js'),
      array('panel/facturacion/admin.js'),
      array('panel/almacen/rpt_combustible.js'),
    ));

    $this->load->model('compras_areas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte Combustible Acumulado');

    $this->load->view('panel/header',$params);
    // $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/almacen/control_maquinaria/rptcombustibleAcumulado',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rptcombustibleAcumulado_pdf()
  {
    $this->load->model('control_maquinaria_model');
    $this->control_maquinaria_model->rptcombustibleAcumulado_pdf();
  }
  public function rptcombustibleAcumulado_xls()
  {
    $this->load->model('control_maquinaria_model');
    $this->control_maquinaria_model->rptcombustibleAcumulado_xls();
  }

  /*
   |------------------------------------------------------------------------
   | METODOS AJAX
   |------------------------------------------------------------------------
   */

  public function ajax_save()
  {
    $this->load->model('control_maquinaria_model');
    echo json_encode($this->control_maquinaria_model->save($_POST));
  }

  public function ajax_delete()
  {
    $this->load->model('control_maquinaria_model');
    echo json_encode($this->control_maquinaria_model->delete($_POST));
  }

  public function ajax_get_implemento()
  {
    $this->load->model('control_maquinaria_model');
    echo json_encode($this->control_maquinaria_model->ajaxImplementos());
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