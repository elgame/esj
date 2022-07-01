<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bodega_guadalajara extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'bodega_guadalajara/cargar/',
    'bodega_guadalajara/guardar/',
    'bodega_guadalajara/saveTotales/',
    'bodega_guadalajara/ajax_get_categorias/',
    'bodega_guadalajara/ajax_save_rastreo/',
    'bodega_guadalajara/ajax_del_rastreo/',
    'bodega_guadalajara/cerrar_caja/',
    'bodega_guadalajara/print_caja/',
    'bodega_guadalajara/print_vale/',
    'bodega_guadalajara/print_vale_ipr/',
    'bodega_guadalajara/print_vale_deudor/',
    'bodega_guadalajara/print_vale_rastreo/',
    'bodega_guadalajara/rpt_gastos_pdf/',
    'bodega_guadalajara/rpt_gastos_xls/',
    'bodega_guadalajara/rpt_ingresos_pdf/',
    'bodega_guadalajara/rpt_ingresos_xls/',
    'bodega_guadalajara/rpt_estado_res_pdf/',
    'bodega_guadalajara/rpt_estado_res_xls/',
    'bodega_guadalajara/agregar_abono_deudor/',
    'bodega_guadalajara/quitar_abono_deudor/',
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
    $this->load->model('bodega_guadalajara_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Bodega guadalajara');
    $params['nomenclaturas'] = $this->bodega_guadalajara_model->getNomenclaturas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/bodega_guadalajara/index',$params);
    $this->load->view('panel/footer',$params);
  }

  public function caja2()
  {
    $this->load->library('pagination');
    $this->load->model('bodega_guadalajara_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Caja chica 2');
    $params['nomenclaturas'] = $this->bodega_guadalajara_model->getNomenclaturas();

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
      array('general/supermodal.js'),
      array('general/util.js'),
      array('panel/facturacion/cuentas_cobrar.js'),
      array('panel/bodega_guadalajara/cargar.js'),
      array('panel/bodega_guadalajara/catalogo_bodega.js'),
    ));
    $this->carabiner->css(array(
      array('panel/caja_chica.css', 'screen'),
    ));

    $this->load->library('pagination');
    $this->load->model('bodega_guadalajara_model');
    $this->load->model('bodega_catalogo_model');

    $this->configGuardaCajaChica();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->bodega_guadalajara_model->guardar($_POST);

      if(!$res_mdl['error'])
        redirect(base_url('panel/bodega_guadalajara/cargar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
    }

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Bodega guadalajara');

    $fecha = isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d');
    $_GET['ffecha'] = $fecha;


    $params['areas'] = $this->bodega_catalogo_model->getTipoAreas();

    $params['nomenclaturas'] = $this->bodega_guadalajara_model->nomenclaturas();

    $params['caja']     = $this->bodega_guadalajara_model->get($fecha, (isset($_GET['fno_caja'])? $_GET['fno_caja']: '1'));
    $params['unidades'] = $this->db->select('*')->from('unidades')->where('status', 't')->order_by('nombre')->get()->result();
    // echo "<pre>";
    //   var_dump($params['remisiones']);
    // echo "</pre>";exit;

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/bodega_guadalajara/generar', $params);
  }

  public function saveTotales()
  {
    $this->load->model('bodega_guadalajara_model');
    $res_mdl = $this->bodega_guadalajara_model->saveTotales($_POST);

    return $res_mdl;
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
    $this->load->model('bodega_guadalajara_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de gastos');

    $params['nomenclatura'] = $this->bodega_guadalajara_model->getNomenclaturas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/bodega_guadalajara/rpt_gastos',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rpt_gastos_pdf(){
    $this->load->model('bodega_guadalajara_model');
    $this->bodega_guadalajara_model->getRptGastosPdf();
  }
  public function rpt_gastos_xls(){
    $this->load->model('bodega_guadalajara_model');
    $this->bodega_guadalajara_model->getRptGastosXls();
  }

  public function rpt_estado_res()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/rpt_gastos.js'),
    ));

    // $this->load->library('pagination');
    $this->load->model('bodega_guadalajara_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de gastos');

    $params['nomenclatura'] = $this->bodega_guadalajara_model->getNomenclaturas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/bodega_guadalajara/rpt_estado_res',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rpt_estado_res_pdf(){
    $this->load->model('bodega_guadalajara_model');
    $this->bodega_guadalajara_model->getRptEstadoResXls();
  }
  public function rpt_estado_res_xls(){
    $this->load->model('bodega_guadalajara_model');
    $this->bodega_guadalajara_model->getRptEstadoResXls(true);
  }

  public function rpt_ingresos()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/rpt_gastos.js'),
    ));

    // $this->load->library('pagination');
    $this->load->model('bodega_guadalajara_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de ingresos');

    $params['nomenclatura'] = $this->bodega_guadalajara_model->getNomenclaturas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/bodega_guadalajara/rpt_ingresos',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rpt_ingresos_pdf(){
    $this->load->model('bodega_guadalajara_model');
    $this->bodega_guadalajara_model->getRptIngresosPdf();
  }
  public function rpt_ingresos_xls(){
    $this->load->model('bodega_guadalajara_model');
    $this->bodega_guadalajara_model->getRptIngresosXls();
  }

  public function rpt_cajas()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/rpt_gastos.js'),
    ));

    // $this->load->library('pagination');
    $this->load->model('bodega_guadalajara_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de gastos');

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/bodega_guadalajara/rpt_cajas',$params);
    $this->load->view('panel/footer',$params);
  }


  public function configGuardaCajaChica()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fecha_caja_chica',
            'label' => '',
            'rules' => 'required'),
      // array('field' => 'saldo_inicial',
      //       'label' => 'Saldo Inicial',
      //       'rules' => 'required|numeric'),
      array('field' => 'fno_caja',
            'label' => 'No Caja',
            'rules' => 'required|numeric'),
    );

    if (isset($_POST['remision_id_factura']))
    {
      $rules[] = array('field' => 'remision_id_factura[]',
                      'label' => 'Ingreso Conceptos',
                      'rules' => 'required');
    }

    if (isset($_POST['venta_id_factura']))
    {
      $rules[] = array('field' => 'venta_id_factura[]',
                        'label' => 'Remision',
                        'rules' => 'required');
    }

    if (isset($_POST['exisd_id_factura']))
    {
      $rules[] = array('field' => 'exisd_id_factura[]',
                        'label' => 'Remision',
                        'rules' => 'required|numeric');
      $rules[] = array('field' => 'exisd_id_unidad[]',
                        'label' => 'Unidad',
                        'rules' => 'required|numeric');
      $rules[] = array('field' => 'exisd_descripcion[]',
                        'label' => 'Desc',
                        'rules' => 'required');
      $rules[] = array('field' => 'exisd_cantidad[]',
                        'label' => 'Cantidad',
                        'rules' => 'required|numeric');
      $rules[] = array('field' => 'exisd_precio_unitario[]',
                        'label' => 'Precio u',
                        'rules' => 'required|numeric');
      $rules[] = array('field' => 'exisd_importe[]',
                      'label' => 'Importe',
                      'rules' => 'required|numeric');
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
    $this->load->model('bodega_guadalajara_model');

    $params['categorias'] = $this->bodega_guadalajara_model->getCategorias();
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

    $this->load->model('bodega_guadalajara_model');

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
      $res_mdl = $this->bodega_guadalajara_model->agregarCategoria($_POST);

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

    $this->load->model('bodega_guadalajara_model');

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
      $res_mdl = $this->bodega_guadalajara_model->modificarCategoria($_GET['id'], $_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/caja_chica/categorias_modificar/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
      }
    }

    $params['categoria'] = $this->bodega_guadalajara_model->info($_GET['id'], true);

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
    $this->load->model('bodega_guadalajara_model');
    $this->bodega_guadalajara_model->elimimnarCategoria($_GET['id']);

    redirect(base_url('panel/caja_chica/categorias/?&msg=6'));
  }

  public function ajax_get_categorias()
  {
    $this->load->model('bodega_guadalajara_model');
    echo json_encode($this->bodega_guadalajara_model->ajaxCategorias());
  }

  public function ajax_save_rastreo()
  {
    $this->load->model('bodega_guadalajara_model');
    echo json_encode($this->bodega_guadalajara_model->ajaxSaveRastreo($_GET));
  }

  public function ajax_del_rastreo()
  {
    $this->db->delete('otros.bodega_rastreo_efectivo', "id_rastreo = ".$_GET['id']);
    echo true;
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
    $this->load->model('bodega_guadalajara_model');

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
        $respons = $this->bodega_guadalajara_model->addAbonoDeudor($_POST);

        $params['closeModal'] = true;
        $params['frm_errors'] = $this->showMsgs(4);
      }

      $params['deudor'] = $this->bodega_guadalajara_model->getInfoDeudor($params['id']);

    }else
      $_GET['msg'] = 1;

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/bodega_guadalajara/agregar_abonos_deudor', $params);
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
    $this->load->model('bodega_guadalajara_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Agregar abonos');

    $params['closeModal'] = false;

    if (isset($_GET['id']{0}) && isset($_GET['no_caja']{0}))
    {
      $params['id']      = $_GET['id'];
      $params['fecha']   = $_GET['fecha'];
      $params['no_caja'] = $_GET['no_caja'];
      $params['monto']   = $_GET['monto'];

      $this->db->delete('otros.bodega_deudores_pagos', ["id_deudor" => $_GET['id'], "fecha_creacion" => $_GET['fecha_creacion']]);
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
    $this->load->model('bodega_guadalajara_model');

    $params['nomenclaturas'] = $this->bodega_guadalajara_model->getNomenclaturas();

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

    $this->load->model('bodega_guadalajara_model');

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
      $res_mdl = $this->bodega_guadalajara_model->agregarNomenclaturas($_POST);

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

    $this->load->model('bodega_guadalajara_model');

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
      $res_mdl = $this->bodega_guadalajara_model->modificarNomenclaturas($_GET['id'], $_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/caja_chica/nomenclaturas_modificar/?'.MyString::getVarsLink(array('msg')).'&msg=9'));
      }
    }

    $params['nomenclatura'] = $this->bodega_guadalajara_model->infoNomenclaturas($_GET['id']);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/caja_chica/modificar_nomenclatura', $params);
    $this->load->view('panel/footer');
  }

  public function nomenclaturas_eliminar()
  {
    $this->load->model('bodega_guadalajara_model');
    $this->bodega_guadalajara_model->elimimnarNomenclaturas($_GET['id'], (isset($_GET['activar'])? $_GET['activar']: 'f') );

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
    $this->load->model('bodega_guadalajara_model');
    $this->bodega_guadalajara_model->cerrarCaja($_GET['id'], $_GET['fno_caja']);

    redirect(base_url('panel/bodega_guadalajara/cargar/?'.MyString::getVarsLink(array('id', 'msg')).'&msg=7'));
  }

  public function print_caja()
  {
    $this->load->model('bodega_guadalajara_model');
    $this->bodega_guadalajara_model->printCaja($_GET['ffecha'], $_GET['fno_caja']);
  }

  public function print_vale()
  {
    $this->load->model('bodega_guadalajara_model');
    if($this->input->get('p') == 'true')
      $this->bodega_guadalajara_model->printVale($_GET['id']);
    else{
      $params['url'] = 'panel/bodega_guadalajara/print_vale/?id='.$this->input->get('id').'&p=true';
      $this->load->view('panel/caja_chica/print_ticket', $params);
    }
  }

  public function print_vale_ipr()
  {
    $this->load->model('bodega_guadalajara_model');
    if($this->input->get('p') == 'true')
      $this->bodega_guadalajara_model->printValeIngresos($_GET['id_ingresos'], $_GET['noCaja']);
    else{
      $params['url'] = 'panel/bodega_guadalajara/print_vale_ipr/?id_ingresos='.$_GET['id_ingresos'].'&noCaja='.$_GET['noCaja'].'&p=true';
      $this->load->view('panel/caja_chica/print_ticket', $params);
    }
  }

  public function print_vale_deudor()
  {
    $this->load->model('bodega_guadalajara_model');
    if($this->input->get('p') == 'true')
      $this->bodega_guadalajara_model->printValeDeudor($_GET['id'], $_GET['noCaja']);
    else{
      $params['url'] = 'panel/bodega_guadalajara/print_vale_deudor/?id='.$_GET['id'].'&noCaja='.$_GET['noCaja'].'&p=true';
      $this->load->view('panel/caja_chica/print_ticket', $params);
    }
  }

  public function print_vale_rastreo()
  {
    $this->load->model('bodega_guadalajara_model');
    if($this->input->get('p') == 'true')
      $this->bodega_guadalajara_model->printValeRastreo($_GET['id'], $_GET['noCaja']);
    else{
      $params['url'] = 'panel/bodega_guadalajara/print_vale_rastreo/?id='.$_GET['id_rastreo'].'&noCaja='.$_GET['noCaja'].'&p=true';
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
