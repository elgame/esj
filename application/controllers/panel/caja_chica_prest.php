<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class caja_chica_prest extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'caja_chica_prest/cargar/',
    'caja_chica_prest/guardar/',
    'caja_chica_prest/ajax_get_categorias/',
    'caja_chica_prest/cerrar_caja/',
    'caja_chica_prest/print_caja/',
    'caja_chica_prest/rpt_gastos_pdf/',
    'caja_chica_prest/rpt_gastos_xls/',
    'caja_chica_prest/rpt_ingresos_pdf/',
    'caja_chica_prest/rpt_ingresos_xls/',
    'caja_chica_prest/print_vale/',
    'caja_chica_prest/print_fondo/',
    'caja_chica_prest/print_prestamolp/',
    'caja_chica_prest/print_prestamocp/',
    'caja_chica_prest/ajax_saldar_adeudos/',
    'caja_chica_prest/abono_prestamo_cp/',
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

    $params['info_empleado'] = $this->info_empleado['info'];
    $params['seo']           = array('titulo' => 'Caja prestamos');
    $params['nomenclaturas'] = $this->caja_chica_model->getNomenclaturas();

    // $this->db->query("SELECT refreshallmaterializedviews();");

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/caja_chica/caja_prestamos',$params);
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
      array('panel/caja_chica/cargar_prestamos.js'),
      // array('panel/caja_chica/areas_requisicion.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('caja_chica_model');
    $this->load->model('caja_chica_prest_model');

    $this->configGuardaCajaChica();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->caja_chica_prest_model->guardar($_POST);

      if(!$res_mdl['error'])
        redirect(base_url('panel/caja_chica_prest/cargar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
    }

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Caja chica');

    $fecha = isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d');
    $_GET['ffecha'] = $fecha;

    $params['caja'] = $this->caja_chica_prest_model->get($fecha, (isset($_GET['fno_caja'])? $_GET['fno_caja']: '1') );

    // $params['areas'] = $this->compras_areas_model->getTipoAreas();

    // $params['remisiones'] = $this->caja_chica_model->getRemisiones();
    // $params['movimientos'] = $this->caja_chica_model->getMovimientos();
    $params['nomenclaturas'] = $this->caja_chica_model->nomenclaturas();

    $params['priv_saldar_prestamo'] = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica_prest/saldar_prestamos/');

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/caja_chica/generar_prestamos', $params);
  }

  public function abono_prestamo_cp()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'id_prestamo_caja',
            'label' => 'Prestamo',
            'rules' => 'required'),
      array('field' => 'no_caja',
            'label' => 'NO caja',
            'rules' => 'required|numeric'),
      array('field' => 'id_categoria',
            'label' => 'Empresa',
            'rules' => 'required|numeric'),
      array('field' => 'fecha',
            'label' => 'Fecha',
            'rules' => 'required'),
      array('field' => 'concepto',
            'label' => 'Concepto',
            'rules' => 'required'),
      array('field' => 'monto',
            'label' => 'Monto',
            'rules' => 'required|numeric'),
    );
    $this->form_validation->set_rules($rules);

    $this->load->model('caja_chica_prest_model');
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->caja_chica_prest_model->guardarPago($_POST);

      if(!$res_mdl['error'])
        redirect(base_url('panel/caja_chica_prest/cargar?ffecha='.$_POST['fecha'].'&fno_caja='.$_POST['no_caja']));
    }
  }

  /**
   * Saldar adeudos de prestamos a largo plazo
   * @return [type] [description]
   */
  public function saldar_prestamos()
  {
    $this->load->model('caja_chica_prest_model');
    $this->caja_chica_prest_model->saldarPrestamo($this->input->get('id'), $this->input->get('fecha'));

    redirect(base_url('panel/caja_chica_prest/cargar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
  }

  public function ajax_saldar_adeudos()
  {
    $this->load->model('caja_chica_prest_model');
    $this->caja_chica_prest_model->saldarPrestamosEmpleado($this->input->get('empleadoId'), $this->input->get('fecha'));

    echo json_encode(true);
  }


  // /**
  //  * REPORTES
  //  * @return [type] [description]
  //  */
  // public function rpt_gastos()
  // {
  //   $this->carabiner->js(array(
  //     array('general/msgbox.js'),
  //     array('panel/caja_chica/rpt_gastos.js'),
  //   ));

  //   // $this->load->library('pagination');
  //   $this->load->model('caja_chica_model');

  //   $params['info_empleado']  = $this->info_empleado['info'];
  //   $params['seo']        = array('titulo' => 'Reporte de gastos');

  //   $params['nomenclatura'] = $this->caja_chica_model->getNomenclaturas();

  //   if(isset($_GET['msg']{0}))
  //     $params['frm_errors'] = $this->showMsgs($_GET['msg']);

  //   $this->load->view('panel/header',$params);
  //   $this->load->view('panel/caja_chica/rpt_gastos',$params);
  //   $this->load->view('panel/footer',$params);
  // }

  // public function rpt_gastos_pdf(){
  //   $this->load->model('caja_chica_model');
  //   $this->caja_chica_model->getRptGastosPdf();
  // }
  // public function rpt_gastos_xls(){
  //   $this->load->model('caja_chica_model');
  //   $this->caja_chica_model->getRptGastosXls();
  // }

  // public function rpt_ingresos()
  // {
  //   $this->carabiner->js(array(
  //     array('general/msgbox.js'),
  //     array('panel/caja_chica/rpt_gastos.js'),
  //   ));

  //   // $this->load->library('pagination');
  //   $this->load->model('caja_chica_model');

  //   $params['info_empleado']  = $this->info_empleado['info'];
  //   $params['seo']        = array('titulo' => 'Reporte de ingresos');

  //   $params['nomenclatura'] = $this->caja_chica_model->getNomenclaturas();

  //   if(isset($_GET['msg']{0}))
  //     $params['frm_errors'] = $this->showMsgs($_GET['msg']);

  //   $this->load->view('panel/header',$params);
  //   $this->load->view('panel/caja_chica/rpt_ingresos',$params);
  //   $this->load->view('panel/footer',$params);
  // }

  // public function rpt_ingresos_pdf(){
  //   $this->load->model('caja_chica_model');
  //   $this->caja_chica_model->getRptIngresosPdf();
  // }
  // public function rpt_ingresos_xls(){
  //   $this->load->model('caja_chica_model');
  //   $this->caja_chica_model->getRptIngresosXls();
  // }

  // public function rpt_cajas()
  // {
  //   $this->carabiner->js(array(
  //     array('general/msgbox.js'),
  //     array('panel/caja_chica/rpt_gastos.js'),
  //   ));

  //   // $this->load->library('pagination');
  //   $this->load->model('caja_chica_model');

  //   $params['info_empleado']  = $this->info_empleado['info'];
  //   $params['seo']        = array('titulo' => 'Reporte de gastos');

  //   if(isset($_GET['msg']{0}))
  //     $params['frm_errors'] = $this->showMsgs($_GET['msg']);

  //   $this->load->view('panel/header',$params);
  //   $this->load->view('panel/caja_chica/rpt_cajas',$params);
  //   $this->load->view('panel/footer',$params);
  // }


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

    if (isset($_POST['prestamo_monto']))
    {
      $rules[] = array('field' => 'prestamo_empresa[]',
                      'label' => 'Prestamo Emprea',
                      'rules' => '');
      $rules[] = array('field' => 'prestamo_empresa_id[]',
                      'label' => 'Prestamo Emprea',
                      'rules' => 'required');
      $rules[] = array('field' => 'prestamo_id_prestamo[]',
                      'label' => 'Prestamo',
                      'rules' => '');
      $rules[] = array('field' => 'prestamo_id_prestamo_nom[]',
                      'label' => 'Prestamo',
                      'rules' => '');
      $rules[] = array('field' => 'prestamo_empleado_id[]',
                      'label' => 'Prestamo',
                      'rules' => 'required');
      // $rules[] = array('field' => 'prestamo_nomenclatura[]',
      //                 'label' => 'Prestamo Nomenclatura',
      //                 'rules' => 'required');
      $rules[] = array('field' => 'prestamo_concepto[]',
                      'label' => 'Prestamo Concepto',
                      'rules' => 'required');
      $rules[] = array('field' => 'prestamo_monto[]',
                      'label' => 'Prestamo Monto',
                      'rules' => 'required|numeric');
    }

    if (isset($_POST['pago_importe']))
    {
      $rules[] = array('field' => 'pago_empresa[]',
                      'label' => 'Pago Emprea',
                      'rules' => '');
      $rules[] = array('field' => 'pago_empresa_id[]',
                      'label' => 'Pago Emprea',
                      'rules' => 'required');
      $rules[] = array('field' => 'pago_id[]',
                      'label' => 'Pago',
                      'rules' => '');
      $rules[] = array('field' => 'pago_id_empleado[]',
                      'label' => 'Pago',
                      'rules' => '');
      $rules[] = array('field' => 'pago_id_empresa[]',
                      'label' => 'Pago',
                      'rules' => '');
      $rules[] = array('field' => 'pago_anio[]',
                      'label' => 'Pago',
                      'rules' => '');
      $rules[] = array('field' => 'pago_semana[]',
                      'label' => 'Pago',
                      'rules' => '');
      $rules[] = array('field' => 'pago_id_prestamo[]',
                      'label' => 'Pago',
                      'rules' => '');
      $rules[] = array('field' => 'pago_nomenclatura[]',
                      'label' => 'Pago Nomenclatura',
                      'rules' => 'required');
      $rules[] = array('field' => 'pago_concepto[]',
                      'label' => 'Pago Concepto',
                      'rules' => 'required');
      $rules[] = array('field' => 'pago_importe[]',
                      'label' => 'Pago Monto',
                      'rules' => 'required|numeric');
    }

    $rules[] = array('field' => 'denominacion_cantidad[]',
                      'label' => 'Numero de Denominacion',
                      'rules' => 'required|numeric');

    $rules[] = array('field' => 'denominacion_total[]',
                      'label' => 'Total de Denominacion',
                      'rules' => 'required|numeric');


    $this->form_validation->set_rules($rules);
  }

  // /*
  //  |------------------------------------------------------------------------
  //  | Categorias
  //  |------------------------------------------------------------------------
  //  */

  // public function categorias()
  // {
  //   $this->carabiner->js(array(
  //     array('general/msgbox.js'),
  //   ));

  //   $params['info_empleado'] = $this->info_empleado['info']; //info empleado
  //   $params['seo'] = array(
  //     'titulo' => 'Administración de Categorias'
  //   );

  //   $this->load->library('pagination');
  //   $this->load->model('caja_chica_model');

  //   $params['categorias'] = $this->caja_chica_model->getCategorias();
  //   // echo "<pre>";
  //   //   var_dump($params['categorias']);
  //   // echo "</pre>";exit;
  //   if (isset($_GET['msg']))
  //     $params['frm_errors'] = $this->showMsgs($_GET['msg']);

  //   $this->load->view('panel/header', $params);
  //   $this->load->view('panel/general/menu', $params);
  //   $this->load->view('panel/caja_chica/categorias_admin', $params);
  //   $this->load->view('panel/footer');
  // }

  // public function categorias_agregar()
  // {
  //   $this->carabiner->css(array(
  //     array('libs/jquery.uniform.css', 'screen'),
  //   ));

  //   $this->carabiner->js(array(
  //     array('general/msgbox.js'),
  //     array('panel/caja_chica/categorias.js'),
  //   ));

  //   $this->load->model('caja_chica_model');

  //   $params['info_empleado'] = $this->info_empleado['info']; //info empleado
  //   $params['seo'] = array(
  //     'titulo' => 'Agregar Categoria'
  //   );

  //   $this->configAddCategoria();
  //   if ($this->form_validation->run() == FALSE)
  //   {
  //     $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
  //   }
  //   else
  //   {
  //     $res_mdl = $this->caja_chica_model->agregarCategoria($_POST);

  //     if ($res_mdl)
  //     {
  //       redirect(base_url('panel/caja_chica/categorias_agregar/?'.MyString::getVarsLink(array('msg')).'&msg=4'));
  //     }
  //   }

  //   $params['empresa_default'] = $this->db->select("id_empresa, nombre_fiscal")
  //     ->from("empresas")
  //     ->where("predeterminado", "t")
  //     ->get()
  //     ->row();

  //   if (isset($_GET['msg']))
  //     $params['frm_errors'] = $this->showMsgs($_GET['msg']);

  //   $this->load->view('panel/header', $params);
  //   $this->load->view('panel/general/menu', $params);
  //   $this->load->view('panel/caja_chica/agregar_categoria', $params);
  //   $this->load->view('panel/footer');
  // }

  // public function categorias_modificar()
  // {
  //   $this->carabiner->css(array(
  //     array('libs/jquery.uniform.css', 'screen'),
  //   ));

  //   $this->carabiner->js(array(
  //     array('general/msgbox.js'),
  //     array('panel/caja_chica/categorias.js'),
  //   ));

  //   $this->load->model('caja_chica_model');

  //   $params['info_empleado'] = $this->info_empleado['info']; //info empleado
  //   $params['seo'] = array(
  //     'titulo' => 'Modificar Categoria'
  //   );

  //   $this->configAddCategoria();
  //   if ($this->form_validation->run() == FALSE)
  //   {
  //     $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
  //   }
  //   else
  //   {
  //     $res_mdl = $this->caja_chica_model->modificarCategoria($_GET['id'], $_POST);

  //     if ($res_mdl)
  //     {
  //       redirect(base_url('panel/caja_chica/categorias_modificar/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
  //     }
  //   }

  //   $params['categoria'] = $this->caja_chica_model->info($_GET['id'], true);

  //   $params['empresa_default'] = $this->db->select("id_empresa, nombre_fiscal")
  //     ->from("empresas")
  //     ->where("predeterminado", "t")
  //     ->get()
  //     ->row();

  //   if (isset($_GET['msg']))
  //     $params['frm_errors'] = $this->showMsgs($_GET['msg']);

  //   $this->load->view('panel/header', $params);
  //   $this->load->view('panel/general/menu', $params);
  //   $this->load->view('panel/caja_chica/modificar_categoria', $params);
  //   $this->load->view('panel/footer');
  // }

  // public function configAddCategoria()
  // {
  //   $this->load->library('form_validation');

  //   $rules = array(
  //     array('field' => 'nombre',
  //           'label' => 'Nombre',
  //           'rules' => 'required|max_length[60]'),
  //     array('field' => 'abreviatura',
  //           'label' => 'Abreviatura',
  //           'rules' => 'required|max_length[20]'),
  //     array('field' => 'pempresa',
  //           'label' => 'Empresa',
  //           'rules' => ''),
  //     array('field' => 'pid_empresa',
  //           'label' => 'Empresa',
  //           'rules' => ''),
  //   );

  //   $this->form_validation->set_rules($rules);
  // }

  // public function categorias_eliminar()
  // {
  //   $this->load->model('caja_chica_model');
  //   $this->caja_chica_model->elimimnarCategoria($_GET['id']);

  //   redirect(base_url('panel/caja_chica/categorias/?&msg=6'));
  // }

  // public function ajax_get_categorias()
  // {
  //   $this->load->model('caja_chica_model');
  //   echo json_encode($this->caja_chica_model->ajaxCategorias());
  // }


  // /*
  //  |------------------------------------------------------------------------
  //  | Nomenclaturas
  //  |------------------------------------------------------------------------
  //  */

  // public function nomenclaturas()
  // {
  //   $this->carabiner->js(array(
  //     array('general/msgbox.js'),
  //   ));

  //   $params['info_empleado'] = $this->info_empleado['info']; //info empleado
  //   $params['seo'] = array(
  //     'titulo' => 'Administración de Nomenclaturas'
  //   );

  //   $this->load->library('pagination');
  //   $this->load->model('caja_chica_model');

  //   $params['nomenclaturas'] = $this->caja_chica_model->getNomenclaturas();

  //   if (isset($_GET['msg']))
  //     $params['frm_errors'] = $this->showMsgs($_GET['msg']);

  //   $this->load->view('panel/header', $params);
  //   $this->load->view('panel/general/menu', $params);
  //   $this->load->view('panel/caja_chica/nomenclaturas_admin', $params);
  //   $this->load->view('panel/footer');
  // }

  // public function nomenclaturas_agregar()
  // {
  //   $this->carabiner->css(array(
  //     array('libs/jquery.uniform.css', 'screen'),
  //   ));

  //   $this->carabiner->js(array(
  //     array('general/msgbox.js'),
  //     array('panel/caja_chica/categorias.js'),
  //   ));

  //   $this->load->model('caja_chica_model');

  //   $params['info_empleado'] = $this->info_empleado['info']; //info empleado
  //   $params['seo'] = array(
  //     'titulo' => 'Agregar Nomenclatura'
  //   );

  //   $this->configAddNomenclaturas();
  //   if ($this->form_validation->run() == FALSE)
  //   {
  //     $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
  //   }
  //   else
  //   {
  //     $res_mdl = $this->caja_chica_model->agregarNomenclaturas($_POST);

  //     if ($res_mdl)
  //     {
  //       redirect(base_url('panel/caja_chica/nomenclaturas_agregar/?'.MyString::getVarsLink(array('msg')).'&msg=8'));
  //     }
  //   }

  //   if (isset($_GET['msg']))
  //     $params['frm_errors'] = $this->showMsgs($_GET['msg']);

  //   $this->load->view('panel/header', $params);
  //   $this->load->view('panel/general/menu', $params);
  //   $this->load->view('panel/caja_chica/agregar_nomenclatura', $params);
  //   $this->load->view('panel/footer');
  // }

  // public function nomenclaturas_modificar()
  // {
  //   $this->carabiner->css(array(
  //     array('libs/jquery.uniform.css', 'screen'),
  //   ));

  //   $this->carabiner->js(array(
  //     array('general/msgbox.js'),
  //     array('panel/caja_chica/categorias.js'),
  //   ));

  //   $this->load->model('caja_chica_model');

  //   $params['info_empleado'] = $this->info_empleado['info']; //info empleado
  //   $params['seo'] = array(
  //     'titulo' => 'Modificar Nomenclatura'
  //   );

  //   $this->configAddNomenclaturas();
  //   if ($this->form_validation->run() == FALSE)
  //   {
  //     $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
  //   }
  //   else
  //   {
  //     $res_mdl = $this->caja_chica_model->modificarNomenclaturas($_GET['id'], $_POST);

  //     if ($res_mdl)
  //     {
  //       redirect(base_url('panel/caja_chica/nomenclaturas_modificar/?'.MyString::getVarsLink(array('msg')).'&msg=9'));
  //     }
  //   }

  //   $params['nomenclatura'] = $this->caja_chica_model->infoNomenclaturas($_GET['id']);

  //   if (isset($_GET['msg']))
  //     $params['frm_errors'] = $this->showMsgs($_GET['msg']);

  //   $this->load->view('panel/header', $params);
  //   $this->load->view('panel/general/menu', $params);
  //   $this->load->view('panel/caja_chica/modificar_nomenclatura', $params);
  //   $this->load->view('panel/footer');
  // }

  // public function nomenclaturas_eliminar()
  // {
  //   $this->load->model('caja_chica_model');
  //   $this->caja_chica_model->elimimnarNomenclaturas($_GET['id'], (isset($_GET['activar'])? $_GET['activar']: 'f') );

  //   redirect(base_url('panel/caja_chica/nomenclaturas/?&msg=10'));
  // }

  // public function configAddNomenclaturas()
  // {
  //   $this->load->library('form_validation');

  //   $rules = array(
  //     array('field' => 'nombre',
  //           'label' => 'Nombre',
  //           'rules' => 'required|max_length[30]')
  //   );

  //   $this->form_validation->set_rules($rules);
  // }


  public function cerrar_caja()
  {
    $this->load->model('caja_chica_prest_model');
    $this->caja_chica_prest_model->cerrarCaja($_GET['id'], $_GET['fno_caja']);

    redirect(base_url('panel/caja_chica_prest/cargar/?'.MyString::getVarsLink(array('id', 'msg')).'&msg=7'));
  }

  public function print_caja()
  {
    $this->load->model('caja_chica_prest_model');
    $this->caja_chica_prest_model->printCaja($_GET['ffecha'], $_GET['fno_caja']);
  }

  public function print_fondo()
  {
    $this->load->model('caja_chica_prest_model');
    $this->caja_chica_prest_model->printFondo($_GET['id']);
  }

  public function print_prestamolp()
  {
    $this->load->model('caja_chica_prest_model');
    $this->caja_chica_prest_model->printPrestamoLp($_GET['id'], $_GET['fecha']);
  }

  public function print_prestamocp()
  {
    $this->load->model('caja_chica_prest_model');
    $this->caja_chica_prest_model->printPrestamoCp($_GET['id'], $_GET['fecha']);
  }

  public function print_vale()
  {
    $this->load->model('caja_chica_prest_model');
    $this->caja_chica_prest_model->printVale($_GET['id']);
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