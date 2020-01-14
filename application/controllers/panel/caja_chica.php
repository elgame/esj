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
    'caja_chica/print_vale/',
    'caja_chica/print_vale_rm/',
    'caja_chica/print_vale_ipr/',
    'caja_chica/print_vale_tras/',
    'caja_chica/print_vale_deudor/',
    'caja_chica/rpt_ingresos_gastos_pdf/',
    'caja_chica/rpt_ingresos_gastos_xls/',
    'caja_chica/ajax_get_remisiones/',
    'caja_chica/ajax_get_movimientos/',
    'caja_chica/ajax_get_gastosdirectos/',
    'caja_chica/ajax_get_deudores/',
    'caja_chica/agregar_abono_deudor/',
    'caja_chica/quitar_abono_deudor/',
    'caja_chica/ajax_registra_gasto_comp/',
    'caja_chica/ajax_cambiar_pregasto',
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

    $privilegio = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/', true);

    $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => $privilegio->nombre);
    $params['nomenclaturas'] = $this->caja_chica_model->getNomenclaturas();

    $this->db->query("SELECT refreshallmaterializedviews();");

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

    $privilegio = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/caja2/', true);

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => $privilegio->nombre);
    $params['nomenclaturas'] = $this->caja_chica_model->getNomenclaturas();

    $this->db->query("SELECT refreshallmaterializedviews();");

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/caja_chica/index2',$params);
    $this->load->view('panel/footer',$params);
  }

  public function caja3()
  {
    $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $privilegio = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/caja3/', true);

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => $privilegio->nombre);
    $params['nomenclaturas'] = $this->caja_chica_model->getNomenclaturas();

    $this->db->query("SELECT refreshallmaterializedviews();");

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/caja_chica/index3',$params);
    $this->load->view('panel/footer',$params);
  }

  public function caja4()
  {
    // Caja general Vianey
    $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $privilegio = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/caja4/', true);

    $params['info_empleado'] = $this->info_empleado['info'];
    $params['seo']           = array('titulo' => $privilegio->nombre);
    $params['nomenclaturas'] = $this->caja_chica_model->getNomenclaturas();

    $this->db->query("SELECT refreshallmaterializedviews();");

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/caja_chica/index4',$params);
    $this->load->view('panel/footer',$params);
  }

  public function caja5()
  {
    $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $privilegio = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/caja5/', true);

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => $privilegio->nombre);
    $params['nomenclaturas'] = $this->caja_chica_model->getNomenclaturas();

    $this->db->query("SELECT refreshallmaterializedviews();");

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/caja_chica/index5',$params);
    $this->load->view('panel/footer',$params);
  }

  public function cargar()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('libs/jquery.filtertable.min.js'),
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      array('panel/caja_chica/cargar.js'),
      array('panel/caja_chica/areas_requisicion.js'),
    ));
    $this->carabiner->css(array(
      array('panel/caja_chica.css', 'screen'),
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
        redirect(base_url('panel/caja_chica/cargar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
    }

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Caja chica');

    $fecha = isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d');
    $_GET['ffecha'] = $fecha;

    $no_caja = (isset($_GET['fno_caja'])? $_GET['fno_caja']: '1');
    $params['caja'] = $this->caja_chica_model->get($fecha, $no_caja );

    $cerrados = $this->db->query("SELECT Count(id_efectivo) AS num FROM cajachica_efectivo
      WHERE fecha > '{$fecha}' AND no_caja = {$no_caja} AND status = 'f'")->row();
    $params['cajas_cerradas'] = $cerrados->num > 0? true: false;

    $params['areas'] = $this->compras_areas_model->getTipoAreas();

    // $params['remisiones'] = $this->caja_chica_model->getRemisiones();
    // $params['movimientos'] = $this->caja_chica_model->getMovimientos();
    $params['nomenclaturas'] = $this->caja_chica_model->nomenclaturas($no_caja);

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
    $params['cajas'] = $this->db->query("SELECT no_caja, nombre FROM cajachicas ORDER BY no_caja ASC")->result();

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
    $params['cajas'] = $this->db->query("SELECT no_caja, nombre FROM cajachicas ORDER BY no_caja ASC")->result();

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

  public function rpt_ingresos_gastos()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/rpt_gastos.js'),
    ));

    // $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de ingresos/gastos');

    // $params['nomenclatura'] = $this->caja_chica_model->getNomenclaturas();
    $params['cajas'] = $this->db->query("SELECT no_caja, nombre FROM cajachicas ORDER BY no_caja ASC")->result();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/caja_chica/rpt_ingresos_gastos',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rpt_ingresos_gastos_pdf(){
    $this->load->model('caja_chica_model');
    $this->caja_chica_model->getRptIngresosGastosPdf();
  }
  public function rpt_ingresos_gastos_xls(){
    $this->load->model('caja_chica_model');
    $this->caja_chica_model->getRptIngresosGastosXls();
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

    $params['cajas'] = $this->caja_chica_model->getCajasChicas();

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
      $rules[] = array('field' => 'gasto_reposicion[]',
                      'label' => 'Reposicion Gastos',
                      'rules' => '');
      $rules[] = array('field' => 'gasto_importe[]',
                      'label' => 'Importe Gastos',
                      'rules' => 'required|numeric');

      $rules[] = array('field' => 'areaId[]',
            'label' => 'Cultivo',
            'rules' => 'required|numeric');
      $rules[] = array('field' => 'area[]',
            'label' => 'Cultivo',
            'rules' => '');
      $rules[] = array('field' => 'ranchoId[]',
            'label' => 'Area',
            'rules' => 'required|numeric');
      $rules[] = array('field' => 'rancho[]',
            'label' => 'Area',
            'rules' => '');
      $rules[] = array('field' => 'centroCostoId[]',
            'label' => 'Centro de costo',
            'rules' => 'required|numeric');
      $rules[] = array('field' => 'centroCosto[]',
            'label' => 'Centro de costo',
            'rules' => '');
      $rules[] = array('field' => 'activoId[]',
            'label' => 'Activo',
            'rules' => '');
      $rules[] = array('field' => 'activos[]',
            'label' => 'Activo',
            'rules' => '');
      $rules[] = array('field' => 'empresaId[]',
            'label' => 'Empresa',
            'rules' => '');
    }

    if (isset($_POST['deudor_nombre']))
    {
      $rules[] = array('field' => 'deudor_fecha[]',
                        'label' => 'Fecha deudor',
                        'rules' => 'required');
      $rules[] = array('field' => 'deudor_tipo[]',
                        'label' => 'Tipo deudor',
                        'rules' => 'required');
      $rules[] = array('field' => 'deudor_nombre[]',
                        'label' => 'Nombre deudor',
                        'rules' => 'required');
      $rules[] = array('field' => 'deudor_concepto[]',
                        'label' => 'Concepto deudor',
                        'rules' => 'required');
      $rules[] = array('field' => 'deudor_id_deudor[]',
                        'label' => 'Deudor',
                        'rules' => '');
      $rules[] = array('field' => 'deudor_concepto[]',
                      'label' => 'Abonos deudor',
                      'rules' => '');
      $rules[] = array('field' => 'deudor_importe[]',
                      'label' => 'Saldo deudor',
                      'rules' => '');
    }

    $this->form_validation->set_rules($rules);
  }

  public function agregar_abono_deudor() {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('general/keyjump.js'),
      array('general/util.js'),
      array('panel/facturacion/cuentas_cobrar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Agregar abonos');

    $params['closeModal'] = false;

    if (isset($_GET['id']{0}) && isset($_GET['no_caja']{0}))
    {
      $params['id'] = $_GET['id'];
      $params['fecha'] = $_GET['fecha'];
      $params['no_caja'] = $_GET['no_caja'];
      $params['monto'] = $_GET['monto'];

      if (isset($_POST['btnGuardarAbono'])) {
        $_POST = array_merge($_POST, $_GET);
      }

      $this->configAddAbonoDeudor();
      if($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $respons = $this->caja_chica_model->addAbonoDeudor($_POST);

        $params['closeModal'] = true;
        $params['frm_errors'] = $this->showMsgs(4);
      }

      $params['deudor'] = $this->caja_chica_model->getInfoDeudor($params['id']);

    }else
      $_GET['msg'] = 1;


    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/caja_chica/agregar_abonos_deudor', $params);
  }

  public function quitar_abono_deudor() {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('general/keyjump.js'),
      array('general/util.js'),
      array('panel/facturacion/cuentas_cobrar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('caja_chica_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Agregar abonos');

    $params['closeModal'] = false;

    if (isset($_GET['id']{0}) && isset($_GET['no_caja']{0}))
    {
      $params['id']      = $_GET['id'];
      $params['fecha']   = $_GET['fecha'];
      $params['no_caja'] = $_GET['no_caja'];
      $params['monto']   = $_GET['monto'];

      $this->db->delete('cajachica_deudores_pagos', ["id_deudor" => $_GET['id'], "fecha_creacion" => $_GET['fecha_creacion']]);
      $this->agregar_abono_deudor();
    } else
      $_GET['msg'] = 1;
  }

  public function configAddAbonoDeudor()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'dmonto',
            'label' => 'Monto',
            'rules' => 'required|numeric'),
      array('field' => 'id',
            'label' => 'Id deuda',
            'rules' => 'required|numeric'),
      array('field' => 'fecha',
            'label' => 'Fecha pago',
            'rules' => 'required'),
      array('field' => 'no_caja',
            'label' => 'No Caja',
            'rules' => 'required|numeric'),
      array('field' => 'monto',
            'label' => 'Deuda',
            'rules' => 'required|numeric'),
    );

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
        redirect(base_url('panel/caja_chica/categorias_agregar/?'.MyString::getVarsLink(array('msg')).'&msg=4'));
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
        redirect(base_url('panel/caja_chica/categorias_modificar/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
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

  public function ajax_get_remisiones()
  {
    $this->load->model('caja_chica_model');
    echo json_encode($this->caja_chica_model->getRemisiones());
  }

  public function ajax_get_movimientos()
  {
    $this->load->model('caja_chica_model');
    echo json_encode($this->caja_chica_model->getMovimientos());
  }

  public function ajax_get_gastosdirectos()
  {
    $this->load->model('caja_chica_model');
    echo json_encode($this->caja_chica_model->getGastosDirectos());
  }

  public function ajax_get_deudores()
  {
    $this->load->model('caja_chica_model');
    echo json_encode($this->caja_chica_model->ajaxDeudores());
  }

  public function ajax_registra_gasto_comp()
  {
    $this->load->model('caja_chica_model');
    echo json_encode($this->caja_chica_model->ajaxRegGastosComprobar($_POST));
  }

  public function ajax_cambiar_pregasto()
  {
    $this->load->model('caja_chica_model');
    echo json_encode($this->caja_chica_model->ajaxCambiarPreGastos($_POST));
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
        redirect(base_url('panel/caja_chica/nomenclaturas_agregar/?'.MyString::getVarsLink(array('msg')).'&msg=8'));
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
        redirect(base_url('panel/caja_chica/nomenclaturas_modificar/?'.MyString::getVarsLink(array('msg')).'&msg=9'));
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

    redirect(base_url('panel/caja_chica/cargar/?'.MyString::getVarsLink(array('id', 'msg')).'&msg=7'));
  }

  public function print_caja()
  {
    $this->load->model('caja_chica_model');
    if ($_GET['fno_caja'] == 'prest1') {
      $_GET['fno_caja'] = '1';
      $this->load->model('caja_chica_prest_model');
      $this->caja_chica_prest_model->printCaja($_GET['ffecha'], $_GET['fno_caja']);
    } else
      $this->caja_chica_model->printCaja($_GET['ffecha'], $_GET['fno_caja']);
  }

  public function print_vale()
  {
    $this->load->model('caja_chica_model');
    if($this->input->get('p') == 'true')
      $this->caja_chica_model->printVale($_GET['id']);
    else{
      $params['url'] = 'panel/caja_chica/print_vale/?id='.$this->input->get('id').'&p=true';
      $this->load->view('panel/caja_chica/print_ticket', $params);
    }
  }

  public function print_vale_rm()
  {
    $this->load->model('caja_chica_model');
    if($this->input->get('p') == 'true')
      $this->caja_chica_model->printValeRemision($_GET['fecha'], $_GET['id_remision'], $_GET['row'], $_GET['noCaja']);
    else{
      $params['url'] = 'panel/caja_chica/print_vale_rm/?fecha='.$_GET['fecha'].'&id_remision='.$_GET['id_remision'].'&row='.$_GET['row'].'&noCaja='.$_GET['noCaja'].'&p=true';
      $this->load->view('panel/caja_chica/print_ticket', $params);
    }
  }

  public function print_vale_ipr()
  {
    $this->load->model('caja_chica_model');
    if($this->input->get('p') == 'true')
      $this->caja_chica_model->printValeIngresos($_GET['id_ingresos'], $_GET['noCaja']);
    else{
      $params['url'] = 'panel/caja_chica/print_vale_ipr/?id_ingresos='.$_GET['id_ingresos'].'&noCaja='.$_GET['noCaja'].'&p=true';
      $this->load->view('panel/caja_chica/print_ticket', $params);
    }
  }

  public function print_vale_tras()
  {
    $this->load->model('caja_chica_model');
    if($this->input->get('p') == 'true')
      $this->caja_chica_model->printValeTraspasos($_GET['id_traspaso'], $_GET['noCaja']);
    else{
      $params['url'] = 'panel/caja_chica/print_vale_tras/?id_traspaso='.$_GET['id_traspaso'].'&noCaja='.$_GET['noCaja'].'&p=true';
      $this->load->view('panel/caja_chica/print_ticket', $params);
    }
  }

  public function print_vale_deudor()
  {
    $this->load->model('caja_chica_model');
    if($this->input->get('p') == 'true')
      $this->caja_chica_model->printValeDeudor($_GET['id'], $_GET['noCaja']);
    else{
      $params['url'] = 'panel/caja_chica/print_vale_deudor/?id='.$_GET['id'].'&noCaja='.$_GET['noCaja'].'&p=true';
      $this->load->view('panel/caja_chica/print_ticket', $params);
    }
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