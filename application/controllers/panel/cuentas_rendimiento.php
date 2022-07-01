<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cuentas_rendimiento extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'cuentas_rendimiento/cargar/',
    'cuentas_rendimiento/guardar/',
    'cuentas_rendimiento/print_rendimiento/',
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

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de Cuentas y rendimientos');
    $params['areas'] = $this->areas_model->getAreas(false, " AND tipo='fr'")['areas'];
    $params['adefault'] = $this->areas_model->getAreaDefault();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/cuentas_rendimiento/index',$params);
    $this->load->view('panel/footer',$params);
  }

  public function cargar()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('libs/jquery.filtertable.min.js'),
      array('general/msgbox.js'),
      array('general/util.js'),
      array('panel/cuentas_rendimientos/cargar.js'),
      array('panel/caja_chica/areas_requisicion.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('cuentas_rendimiento_model');
    $this->load->model('areas_model');

    $this->configGuardaRpt();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $_POST['cerrar_dia'] = 'f';
      $res_mdl = $this->cuentas_rendimiento_model->guardar($_POST);

      if(!$res_mdl['error'])
        redirect(base_url('panel/cuentas_rendimiento/cargar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
    }

    $params['info_empleado'] = $this->info_empleado['info'];
    $params['seo']           = array('titulo' => 'Reporte de cuentas y rendimientos');

    $fecha = isset($_GET['ffecha']) ? $_GET['ffecha'] : (isset($_GET['fecha_caja_chica']) ? $_GET['fecha_caja_chica'] : date('Y-m-d'));
    $_GET['ffecha'] = $fecha;
    $_GET['farea'] = isset($_GET['farea']) ? $_GET['farea'] : (isset($_GET['id_area']) ? $_GET['id_area'] : 2);

    $params['rpt']      = $this->cuentas_rendimiento_model->get($fecha, (isset($_GET['farea'])? $_GET['farea']: null) );
    $params['unidades'] = $this->db->select('*')->from('unidades')->where('status', 't')->order_by('nombre')->get()->result();

    // $params['areas'] = $this->compras_areas_model->getTipoAreas();

    // $params['remisiones'] = $this->cuentas_rendimiento_model->getRemisiones();
    // $params['movimientos'] = $this->cuentas_rendimiento_model->getMovimientos();
    // $params['nomenclaturas'] = $this->cuentas_rendimiento_model->nomenclaturas();

    // echo "<pre>";
    //   var_dump($params['remisiones']);
    // echo "</pre>";exit;

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/cuentas_rendimiento/generar', $params);
  }

  public function print_rendimiento()
  {
    $this->load->model('cuentas_rendimiento_model');
    $this->cuentas_rendimiento_model->printRendimiento( $_GET['ffecha'], (isset($_GET['farea'])? $_GET['farea']: null) );
  }

  public function configGuardaRpt()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fecha_caja_chica',
            'label' => '',
            'rules' => 'required'),
      array('field' => 'id_area',
            'label' => 'Aria',
            'rules' => 'required|numeric'),
    );

    if (isset($_POST['prod_ddescripcion']))
    {
      $rules[] = array('field' => 'prod_ddescripcion[]',
                      'label' => 'EXISTENCIA CLASIF',
                      'rules' => 'required');
      $rules[] = array('field' => 'prod_did_prod[]',
                      'label' => 'EXISTENCIA CLASIF',
                      'rules' => 'required');
      $rules[] = array('field' => 'prod_dmedida[]',
                      'label' => 'EXISTENCIA CODIGO',
                      'rules' => 'required');
      $rules[] = array('field' => 'prod_bultos[]',
                      'label' => 'EXISTENCIA BULTOS',
                      'rules' => 'required|numeric');
      $rules[] = array('field' => 'prod_kilos[]',
                      'label' => 'EXISTENCIA KGS',
                      'rules' => 'required|numeric');
      $rules[] = array('field' => 'prod_precio[]',
                      'label' => 'EXISTENCIA PRECIO',
                      'rules' => 'required|numeric');
      $rules[] = array('field' => 'prod_importe[]',
                      'label' => 'EXISTENCIA IMPORTE',
                      'rules' => 'required|numeric');
    }

    if (isset($_POST['compe_ddescripcion']))
    {
      $rules[] = array('field' => 'compe_ddescripcion[]',
                      'label' => 'COMPRA CLASIF',
                      'rules' => 'required');
      $rules[] = array('field' => 'compe_did_prod[]',
                      'label' => 'COMPRA CLASIF',
                      'rules' => 'required');
      $rules[] = array('field' => 'compe_dmedida[]',
                      'label' => 'COMPRA CODIGO',
                      'rules' => 'required');
      $rules[] = array('field' => 'compe_bultos[]',
                      'label' => 'COMPRA BULTOS',
                      'rules' => 'required|numeric');
      $rules[] = array('field' => 'compe_kilos[]',
                      'label' => 'COMPRA KGS',
                      'rules' => 'required|numeric');
      $rules[] = array('field' => 'compe_precio[]',
                      'label' => 'COMPRA PRECIO',
                      'rules' => 'required|numeric');
      $rules[] = array('field' => 'compe_importe[]',
                      'label' => 'COMPRA IMPORTE',
                      'rules' => 'required|numeric');
    }

    if (isset($_POST['apatzin_ddescripcion']))
    {
      $rules[] = array('field' => 'apatzin_ddescripcion[]',
                      'label' => 'APATZINGAN NOMBRE',
                      'rules' => 'required|max_length[100]');
      $rules[] = array('field' => 'apatzin_dmedida[]',
                      'label' => 'APATZINGAN UNIDAD',
                      'rules' => 'required|max_length[20]');
      $rules[] = array('field' => 'apatzin_precio[]',
                      'label' => 'APATZINGAN PRECIO',
                      'rules' => 'required|numeric');
      $rules[] = array('field' => 'apatzin_importe[]',
                      'label' => 'APATZINGAN IMPORTE',
                      'rules' => 'required|numeric');
    }

    if (isset($_POST['costo_venta_id_unidad']))
    {
      $rules[] = array('field' => 'costo_venta_id_unidad[]',
                      'label' => 'COSTO DE VENTA PRECIO',
                      'rules' => 'required|numeric');
      $rules[] = array('field' => 'costo_venta_precio[]',
                      'label' => 'COSTO DE VENTA PRECIO',
                      'rules' => 'required|numeric');
    }

    $this->form_validation->set_rules($rules);
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