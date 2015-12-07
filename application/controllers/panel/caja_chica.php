<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class caja_chica extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'caja_chica/cargar/',
    'caja_chica/guardar/',
    'caja_chica/ajax_get_categorias/',
    'caja_chica/cerrar_caja/',
    'caja_chica/print_caja/',
    'caja_chica/rpt_gastos_pdf/',
    'caja_chica/rpt_gastos_xls/',
    'caja_chica/rpt_ingresos_pdf/',
    'caja_chica/rpt_ingresos_xls/',
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
    $this->load->model('caja_chica_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Caja chica');
    $params['nomenclaturas'] = $this->caja_chica_model->getNomenclaturas();

    $this->db->query("REFRESH MATERIALIZED VIEW saldos_facturas_remisiones");

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/caja_chica/index',$params);
    $this->load->view('panel/footer',$params);
  }

  public function caja2()
  {
    $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Caja chica 2');
    $params['nomenclaturas'] = $this->caja_chica_model->getNomenclaturas();

    $this->db->query("REFRESH MATERIALIZED VIEW saldos_facturas_remisiones");

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/caja_chica/index2',$params);
    $this->load->view('panel/footer',$params);
  }

  public function cargar()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('libs/jquery.filtertable.min.js'),
      array('general/msgbox.js'),
      array('general/util.js'),
      array('panel/caja_chica/cargar.js'),
      array('panel/caja_chica/areas_requisicion.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('caja_chica_model');
    $this->load->model('compras_areas_model');

    $this->configGuardaCajaChica();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->caja_chica_model->guardar($_POST);

      if(!$res_mdl['error'])
        redirect(base_url('panel/caja_chica/cargar/?'.String::getVarsLink(array('msg')).'&msg=3'));
    }

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Caja chica');

    $fecha = isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d');
    $_GET['ffecha'] = $fecha;

    $params['caja'] = $this->caja_chica_model->get($fecha, (isset($_GET['fno_caja'])? $_GET['fno_caja']: '1') );

    $params['areas'] = $this->compras_areas_model->getTipoAreas();

    $params['remisiones'] = $this->caja_chica_model->getRemisiones();
    $params['movimientos'] = $this->caja_chica_model->getMovimientos();
    $params['nomenclaturas'] = $this->caja_chica_model->nomenclaturas();

    // echo "<pre>";
    //   var_dump($params['remisiones']);
    // echo "</pre>";exit;

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/caja_chica/generar', $params);
  }

  /**
   * REPORTES
   * @return [type] [description]
   */
  public function rpt_gastos()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/rpt_gastos.js'),
    ));

    // $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de gastos');

    $params['nomenclatura'] = $this->caja_chica_model->getNomenclaturas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/caja_chica/rpt_gastos',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rpt_gastos_pdf(){
    $this->load->model('caja_chica_model');
    $this->caja_chica_model->getRptGastosPdf();
  }
  public function rpt_gastos_xls(){
    $this->load->model('caja_chica_model');
    $this->caja_chica_model->getRptGastosXls();
  }

  public function rpt_ingresos()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/rpt_gastos.js'),
    ));

    // $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de ingresos');

    $params['nomenclatura'] = $this->caja_chica_model->getNomenclaturas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/caja_chica/rpt_ingresos',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rpt_ingresos_pdf(){
    $this->load->model('caja_chica_model');
    $this->caja_chica_model->getRptIngresosPdf();
  }
  public function rpt_ingresos_xls(){
    $this->load->model('caja_chica_model');
    $this->caja_chica_model->getRptIngresosXls();
  }

  public function rpt_cajas()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/rpt_gastos.js'),
    ));

    // $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de gastos');

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/caja_chica/rpt_cajas',$params);
    $this->load->view('panel/footer',$params);
  }


  public function configGuardaCajaChica()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fecha_caja_chica',
            'label' => '',
            'rules' => 'required'),
      array('field' => 'saldo_inicial',
            'label' => 'Saldo Inicial',
            'rules' => 'required|numeric'),
      array('field' => 'fno_caja',
            'label' => 'No Caja',
            'rules' => 'required|numeric'),
    );

    if (isset($_POST['ingreso_concepto']))
    {
      $rules[] = array('field' => 'ingreso_empresa[]',
                      'label' => 'Ingreso Emprea',
                      'rules' => '');
      $rules[] = array('field' => 'ingreso_empresa_id[]',
                      'label' => 'Ingreso Emprea',
                      'rules' => 'required');
      $rules[] = array('field' => 'ingreso_nomenclatura[]',
                      'label' => 'Ingreso Nomenclatura',
                      'rules' => 'required');
      $rules[] = array('field' => 'ingreso_poliza[]',
                      'label' => 'Ingreso Poliza',
                      'rules' => '');
      $rules[] = array('field' => 'ingreso_concepto_id[]',
                      'label' => 'Ingreso Conceptos',
                      'rules' => '');

      $rules[] = array('field' => 'ingreso_concepto[]',
                      'label' => 'Ingreso Conceptos',
                      'rules' => 'required');

      $rules[] = array('field' => 'ingreso_monto[]',
                      'label' => 'Ingreso Monto',
                      'rules' => 'required|numeric');
    }

    if (isset($_POST['remision_concepto']))
    {
      $rules[] = array('field' => 'remision_empresa[]',
                        'label' => 'Concepto Otros',
                        'rules' => '');
      $rules[] = array('field' => 'remision_empresa_id[]',
                        'label' => 'Empresa Remisiones',
                        'rules' => 'required');
      $rules[] = array('field' => 'remision_numero[]',
                        'label' => 'Remision Remisiones',
                        'rules' => '');
      $rules[] = array('field' => 'remision_folio[]',
                        'label' => 'Folio Remisiones',
                        'rules' => '');
      $rules[] = array('field' => 'remision_concepto[]',
                        'label' => 'Concepto Remisiones',
                        'rules' => 'required');
      $rules[] = array('field' => 'remision_importe[]',
                        'label' => 'Importe Remisiones',
                        'rules' => 'required|numeric');
      $rules[] = array('field' => 'remision_id[]',
                        'label' => 'Remision',
                        'rules' => 'required');
    }

    $rules[] = array('field' => 'denominacion_cantidad[]',
                      'label' => 'Numero de Denominacion',
                      'rules' => 'required|numeric');

    $rules[] = array('field' => 'denominacion_total[]',
                      'label' => 'Total de Denominacion',
                      'rules' => 'required|numeric');

    if (isset($_POST['gasto_concepto']))
    {
      $rules[] = array('field' => 'gasto_empresa[]',
                        'label' => 'Concepto Gastos',
                        'rules' => '');
      $rules[] = array('field' => 'gasto_empresa_id[]',
                        'label' => 'Empresa Gastos',
                        'rules' => 'required');
      $rules[] = array('field' => 'gasto_nomenclatura[]',
                        'label' => 'Nomenclatura Gastos',
                        'rules' => 'required');
      $rules[] = array('field' => 'gasto_folio[]',
                        'label' => 'Folio Gastos',
                        'rules' => '');
      $rules[] = array('field' => 'gasto_empresa_id[]',
                        'label' => 'Empresa Gastos',
                        'rules' => 'required');
      $rules[] = array('field' => 'gasto_concepto[]',
                      'label' => 'Concepto Gastos',
                      'rules' => 'required|max_length[500]');
      $rules[] = array('field' => 'gasto_importe[]',
                      'label' => 'Importe Gastos',
                      'rules' => 'required|numeric');
    }

    $this->form_validation->set_rules($rules);
  }

  /*
   |------------------------------------------------------------------------
   | Categorias
   |------------------------------------------------------------------------
   */

  public function categorias()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Categorias'
    );

    $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $params['categorias'] = $this->caja_chica_model->getCategorias();
    // echo "<pre>";
    //   var_dump($params['categorias']);
    // echo "</pre>";exit;
    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/caja_chica/categorias_admin', $params);
    $this->load->view('panel/footer');
  }

  public function categorias_agregar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/categorias.js'),
    ));

    $this->load->model('caja_chica_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Categoria'
    );

    $this->configAddCategoria();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->caja_chica_model->agregarCategoria($_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/caja_chica/categorias_agregar/?'.String::getVarsLink(array('msg')).'&msg=4'));
      }
    }

    $params['empresa_default'] = $this->db->select("id_empresa, nombre_fiscal")
      ->from("empresas")
      ->where("predeterminado", "t")
      ->get()
      ->row();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/caja_chica/agregar_categoria', $params);
    $this->load->view('panel/footer');
  }

  public function categorias_modificar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/categorias.js'),
    ));

    $this->load->model('caja_chica_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Modificar Categoria'
    );

    $this->configAddCategoria();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->caja_chica_model->modificarCategoria($_GET['id'], $_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/caja_chica/categorias_modificar/?'.String::getVarsLink(array('msg')).'&msg=5'));
      }
    }

    $params['categoria'] = $this->caja_chica_model->info($_GET['id'], true);

    $params['empresa_default'] = $this->db->select("id_empresa, nombre_fiscal")
      ->from("empresas")
      ->where("predeterminado", "t")
      ->get()
      ->row();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/caja_chica/modificar_categoria', $params);
    $this->load->view('panel/footer');
  }

  public function configAddCategoria()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'nombre',
            'label' => 'Nombre',
            'rules' => 'required|max_length[60]'),
      array('field' => 'abreviatura',
            'label' => 'Abreviatura',
            'rules' => 'required|max_length[20]'),
      array('field' => 'pempresa',
            'label' => 'Empresa',
            'rules' => ''),
      array('field' => 'pid_empresa',
            'label' => 'Empresa',
            'rules' => ''),
    );

    $this->form_validation->set_rules($rules);
  }

  public function categorias_eliminar()
  {
    $this->load->model('caja_chica_model');
    $this->caja_chica_model->elimimnarCategoria($_GET['id']);

    redirect(base_url('panel/caja_chica/categorias/?&msg=6'));
  }

  public function ajax_get_categorias()
  {
    $this->load->model('caja_chica_model');
    echo json_encode($this->caja_chica_model->ajaxCategorias());
  }


  /*
   |------------------------------------------------------------------------
   | Nomenclaturas
   |------------------------------------------------------------------------
   */

  public function nomenclaturas()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Nomenclaturas'
    );

    $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $params['nomenclaturas'] = $this->caja_chica_model->getNomenclaturas();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/caja_chica/nomenclaturas_admin', $params);
    $this->load->view('panel/footer');
  }

  public function nomenclaturas_agregar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/categorias.js'),
    ));

    $this->load->model('caja_chica_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Nomenclatura'
    );

    $this->configAddNomenclaturas();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->caja_chica_model->agregarNomenclaturas($_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/caja_chica/nomenclaturas_agregar/?'.String::getVarsLink(array('msg')).'&msg=8'));
      }
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/caja_chica/agregar_nomenclatura', $params);
    $this->load->view('panel/footer');
  }

  public function nomenclaturas_modificar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/categorias.js'),
    ));

    $this->load->model('caja_chica_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Modificar Nomenclatura'
    );

    $this->configAddNomenclaturas();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->caja_chica_model->modificarNomenclaturas($_GET['id'], $_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/caja_chica/nomenclaturas_modificar/?'.String::getVarsLink(array('msg')).'&msg=9'));
      }
    }

    $params['nomenclatura'] = $this->caja_chica_model->infoNomenclaturas($_GET['id']);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/caja_chica/modificar_nomenclatura', $params);
    $this->load->view('panel/footer');
  }

  public function nomenclaturas_eliminar()
  {
    $this->load->model('caja_chica_model');
    $this->caja_chica_model->elimimnarNomenclaturas($_GET['id'], (isset($_GET['activar'])? $_GET['activar']: 'f') );

    redirect(base_url('panel/caja_chica/nomenclaturas/?&msg=10'));
  }

  public function configAddNomenclaturas()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'nombre',
            'label' => 'Nombre',
            'rules' => 'required|max_length[30]')
    );

    $this->form_validation->set_rules($rules);
  }


  public function cerrar_caja()
  {
    $this->load->model('caja_chica_model');
    $this->caja_chica_model->cerrarCaja($_GET['id'], $_GET['fno_caja']);

    redirect(base_url('panel/caja_chica/cargar/?'.String::getVarsLink(array('id', 'msg')).'&msg=7'));
  }

  public function print_caja()
  {
    $this->load->model('caja_chica_model');
    $this->caja_chica_model->printCaja($_GET['ffecha'], $_GET['fno_caja']);
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