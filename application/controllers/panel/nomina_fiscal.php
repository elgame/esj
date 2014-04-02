<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_fiscal extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'nomina_fiscal/addAsistencias/',
    'nomina_fiscal/show_otros/',
    'nomina_fiscal/bonos_otros/',
    'nomina_fiscal/add_vacaciones/',
    'nomina_fiscal/add_incapacidades/',

    'nomina_fiscal/add_nomina/',
    'nomina_fiscal/ajax_add_nomina_empleado/',
    'nomina_fiscal/ajax_get_empleado/',
    'nomina_fiscal/add_finiquito/',
    'nomina_fiscal/ajax_add_prenomina_empleado/',
    'nomina_fiscal/ajax_get_semana/',

    'nomina_fiscal/nomina_fiscal_pdf/',
    'nomina_fiscal/nomina_fiscal_cfdis/',
    'nomina_fiscal/nomina_fiscal_banco/',
    'nomina_fiscal/nomina_fiscal_rpt_pdf/',
    'nomina_fiscal/recibo_nomina_pdf/',
    'nomina_fiscal/recibo_vacaciones_pdf/',
    'nomina_fiscal/recibo_finiquito_pdf/',
    'nomina_fiscal/recibo_incapacidad_pdf/',

    'nomina_fiscal/rpt_vacaciones_pdf/',
    'nomina_fiscal/rpt_pdf/',
    'nomina_fiscal/asistencia_pdf/',
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
    if (isset($_GET['empresaId']) && $_GET['empresaId'] === '')
      redirect(base_url('panel/nomina_fiscal?msg=9'));

    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('libs/jquery.numeric.js'),
      array('general/util.js'),
      array('panel/nomina_fiscal/nomina.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Nomina Fiscal');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    $params['empresaDefault'] = $this->empresas_model->getDefaultEmpresa();

    $filtros = array(
      'semana'    => isset($_GET['semana']) ? $_GET['semana'] : '',
      'anio'    => isset($_GET['anio']) ? $_GET['anio'] : date("Y"),
      'empresaId' => isset($_GET['empresaId']) ? $_GET['empresaId'] : $params['empresaDefault']->id_empresa,
      'puestoId'  => isset($_GET['puestoId']) ? $_GET['puestoId'] : '',
    );
    if ($filtros['empresaId'] !== '')
    {
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $filtros['empresaId'])->get()->row()->dia_inicia_semana;
    }
    else
    {
      $dia = '4';
    }
    $filtros['dia_inicia_semana'] = $dia;

    $_GET['cid_empresa'] = $filtros['empresaId']; //para las cuentas del contpaq
    // Datos para la vista.
    $configuraciones = $this->nomina_fiscal_model->configuraciones();
    $params['empleados'] = $this->nomina_fiscal_model->nomina($configuraciones, $filtros);
    $params['empresas'] = $this->empresas_model->getEmpresasAjax();
    $params['puestos'] = $this->usuarios_model->puestos();
    // $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno();

    $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno($dia, $filtros['anio']);

    // Determina cual es la semana que dejara seleccionada en la vista.
    $semanaActual = $this->nomina_fiscal_model->semanaActualDelMes();
    $params['numSemanaSelected'] = isset($_GET['semana']) ? $_GET['semana'] : $semanaActual['semana'];

    // Obtiene los rangos de fecha de la semana seleccionada para obtener
    // las fechas de los 7 dias siguientes.
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($params['numSemanaSelected'], $filtros['anio']);
    $params['dias'] = String::obtenerSiguientesXDias($semana['fecha_inicio'], 7);
    $anio = (new DateTime($semana['fecha_inicio']))->format('Y');

    $params['sat_incapacidades'] = $this->nomina_fiscal_model->satCatalogoIncapacidades();

    // Query para saber si existen nominas generadas para la semana.
    // $query = $this->db->query(
    //   "SELECT COUNT(id_empleado) as total_nominas
    //    FROM nomina_fiscal
    //    WHERE id_empresa = {$filtros['empresaId']} AND anio = {$anio} AND semana = {$semana['semana']}"
    // )->result();

    // Total de nominas de los empleados generadas.
    $totalGeneradas = 0;

    $params['nominas_generadas'] = false;
    foreach ($params['empleados'] as $empleado)
    {
      if ($empleado->esta_generada !== 'false')
      {
        $totalGeneradas++;
        $params['nominas_generadas'] = true;
      }
    }

    // Indica si ya se generaron todas las nominas de los empleados de la semana.
    $params['nominas_finalizadas'] = false;
    if (count($params['empleados']) == $totalGeneradas && $totalGeneradas != 0)
    {
      $params['nominas_finalizadas'] = true;
    }

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/nomina_fiscal/nomina', $params);
    $this->load->view('panel/footer');
  }

  public function add_nomina()
  {
    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');

    $empresaDefault = $this->empresas_model->getDefaultEmpresa();
    $empresaId = isset($_GET['empresaId']) ? $_GET['empresaId'] : $empresaDefault->id_empresa;

    $result = $this->nomina_fiscal_model->add_nominas($_POST, $empresaId);

    // Si ocurrio algun problema al tratar de timbrar alguna nomina mostrara el
    // mensaje adecuado.
    if ($result['errorTimbrar'])
    {
      $msg = '5';
    }
    else
    {
      $msg = '4';
    }

    redirect(base_url('panel/nomina_fiscal/?'.String::getVarsLink(array('msg')).'&msg='.$msg));
  }

  /**
   * Agrega una factura a la bd
   *
   * @return void
   */
  public function asistencia()
  {
    $this->carabiner->js(array(
        array('general/supermodal.js'),
        array('panel/nomina_fiscal/asistencia.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Nomina Fiscal - Asistencia');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');
    $this->load->model('usuarios_departamentos_model');

    $params['empresaDefault'] = $this->empresas_model->getDefaultEmpresa();

    $filtros = array(
      'semana'    => isset($_GET['semana']) ? $_GET['semana'] : '',
      'anio'      => isset($_GET['anio']) ? $_GET['anio'] : date("Y"),
      'empresaId' => isset($_GET['empresaId']) ? $_GET['empresaId'] : $params['empresaDefault']->id_empresa,
      'puestoId'  => isset($_GET['puestoId']) ? $_GET['puestoId'] : '',
    );
    $_GET['anio'] = $filtros['anio'];
    if ($filtros['empresaId'] !== '')
    {
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $filtros['empresaId'])->get()->row()->dia_inicia_semana;
    }
    else
    {
      $dia = '4';
    }
    $filtros['dia_inicia_semana'] = $dia;

    // Datos para la vista.
    $params['empleados'] = $this->nomina_fiscal_model->listadoEmpleadosAsistencias($filtros);
    $params['empresas'] = $this->empresas_model->getEmpresasAjax();
    // $params['puestos'] = $this->usuarios_model->departamentos(); //puestos();

    $_GET['did_empresa'] = $filtros['empresaId'];
    $params['puestos'] = $this->usuarios_departamentos_model->getPuestos(false); //puestos();


    $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno($dia, $filtros['anio']);

    // Determina cual es la semana que dejara seleccionada en la vista.
    $semanaActual = $this->nomina_fiscal_model->semanaActualDelMes();
    $params['numSemanaSelected'] = isset($_GET['semana']) ? $_GET['semana'] : $semanaActual['semana'];

    // Obtiene los rangos de fecha de la semana seleccionada para obtener
    // las fechas de los 7 dias siguientes.
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($params['numSemanaSelected'], $filtros['anio'], $dia);
    $params['dias'] = String::obtenerSiguientesXDias($semana['fecha_inicio'], 7);

    $params['sat_incapacidades'] = $this->nomina_fiscal_model->satCatalogoIncapacidades();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/nomina_fiscal/asistencia', $params);
    $this->load->view('panel/footer');
  }

  public function addAsistencias()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->addAsistencias($_POST['empleados'], $_POST['numSemana'], $_GET['empresaId'], $_GET['anio']);

    redirect(base_url('panel/nomina_fiscal/asistencia/?'.String::getVarsLink(array('msg')).'&msg=3'));
  }

  public function show_otros()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('panel/nomina_fiscal/bonos_otros.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Nomina Fiscal'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Nomina Fiscal - Abonos y Otros');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('usuarios_model');

    // Obtiene la informacion del empleado.
    $params['empleado'] = $this->usuarios_model->get_usuario_info($_GET['eid']);
    $anio = isset($_GET['anio'])? $_GET['anio']: date("Y");

    // Obtiene los dias de la semana.
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($_GET['sem'], $anio, $params['empleado']['info'][0]->dia_inicia_semana);
    $params['semana'] = $semana;
    $params['dias'] = String::obtenerSiguientesXDias($semana['fecha_inicio'], 7);
    foreach ($params['dias'] as $key => $value)
      $params['nombresDias'][] = String::dia($value);

    // Obtiene los bonos y otros que ya tiene el empleado de la semana.
    $params['bonosOtros'] = $this->nomina_fiscal_model->getBonosOtrosEmpleado($_GET['eid'], $_GET['sem'], $anio, $params['empleado']['info'][0]->dia_inicia_semana);

    // Obtiene los prestamos que se hicieron en la semana cargada.
    $params['prestamos'] = $this->nomina_fiscal_model->getPrestamosEmpleado($_GET['eid'], $_GET['sem'], $anio, $params['empleado']['info'][0]->dia_inicia_semana);

    // Obtiene el registro si se agrego vacaciones.
    $params['vacaciones'] = $this->nomina_fiscal_model->getVacacionesEmpleado($_GET['eid'], $_GET['sem'], $anio, $params['empleado']['info'][0]->dia_inicia_semana);

    //Incapacidades
    $params['sat_incapacidades'] = $this->nomina_fiscal_model->satCatalogoIncapacidades();
    $params['incapacidades'] = $this->nomina_fiscal_model->getIncapacidadesEmpleado($_GET['eid'], $_GET['sem']);

    if(isset($_GET['msg']{0}))
    {
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      if ($_GET['msg'] === '3')
      {
        $params['close'] = true;
      }
    }

    $this->load->view('panel/nomina_fiscal/bonos_otros', $params);
  }

  public function bonos_otros()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->addBonosOtros($_GET['eid'], $_POST, $_GET['sem']);

    redirect(base_url('panel/nomina_fiscal/show_otros/?'.String::getVarsLink(array('msg')).'&msg=3'));
  }

  public function add_prestamos()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->addPrestamos($_GET['eid'], $_POST, $_GET['sem']);

    redirect(base_url('panel/nomina_fiscal/show_otros/?'.String::getVarsLink(array('msg')).'&msg=3'));
  }

  public function add_vacaciones()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->addVacaciones($_GET['eid'], $_POST, $_GET['sem']);

    redirect(base_url('panel/nomina_fiscal/show_otros/?'.String::getVarsLink(array('msg')).'&msg=3'));
  }

  public function add_incapacidades()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->addIncapaciades($_GET['eid'], $_POST, $_GET['sem']);

    redirect(base_url('panel/nomina_fiscal/show_otros/?'.String::getVarsLink(array('msg')).'&msg=3'));
  }

  /**
   * Imprime la factura.
   *
   * @return void
   */
  public function imprimir()
  {
    if(isset($_GET['id']{0}))
    {
      $this->load->model('facturacion_model');
      $this->facturacion_model->generaFacturaPdf($_GET['id']);
    }
    else
      redirect(base_url('panel/facturacion/?msg=1'));
  }

  public function finiquito()
  {
    $this->carabiner->js(array(
      // array('general/supermodal.js'),
      // array('libs/jquery.numeric.js'),
      // array('general/util.js'),
      array('panel/nomina_fiscal/finiquito.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Finiquito'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Finiquito');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    // Datos para la vista.
    if (isset($_GET['empleadoId']) && $_GET['empleadoId'] !== '' && isset($_GET['fechaSalida']) && $_GET['fechaSalida'] !== '')
    {
      $params['empleado'] = $this->nomina_fiscal_model->finiquito($_GET['empleadoId'], $_GET['fechaSalida']);
    }
    else if (isset($_GET['empleadoId']) && $_GET['empleadoId'] == '' || isset($_GET['fechaSalida']) && $_GET['fechaSalida'] == '')
    {
      $params['frm_errors'] = $this->showMsgs(6);
    }

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/nomina_fiscal/finiquito', $params);
    $this->load->view('panel/footer');
  }

  public function add_finiquito()
  {
    $this->load->model('nomina_fiscal_model');
    $result = $this->nomina_fiscal_model->add_finiquito($_GET['empleadoId'], $_GET['fechaSalida']);

    if ( ! $result['errorTimbrar'])
    {
      redirect(base_url('panel/nomina_fiscal/finiquito/?msg=7'));
    }
    else
    {
      redirect(base_url('panel/nomina_fiscal/finiquito/?'.String::getVarsLink(array('msg')).'&msg=8'));
    }
  }

  /*
   |------------------------------------------------------------------------
   | Reportes
   |------------------------------------------------------------------------
   */
  public function rpt_vacaciones()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/facturacion/rpt_ventas.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Vacaciones');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rptvacaciones',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rpt_prestamos_trabajador()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/rpt_trabajador_prestamos.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte Prestamos Trabajador');

    // $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_prestamos_trabajador',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rpt_pdf()
  {
    $this->load->model('nomina_fiscal_model');
    $trabajadorId = isset($_GET['fid_trabajador']) ? $_GET['fid_trabajador'] : false;
    $fecha1 = isset($_GET['ffecha1']) ? $_GET['ffecha1'] : false;
    $fecha2 = isset($_GET['ffecha2']) ? $_GET['ffecha2'] : false;
    $todos = isset($_GET['ftodos']) ? true : false;

    $this->nomina_fiscal_model->rptTrabajadoresPrestamosPdf($trabajadorId, $fecha1, $fecha2, $todos);
  }

  public function rpt_vacaciones_pdf()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->rptVacacionesPdf($_GET['did_empresa']);
  }

  public function recibo_vacaciones()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/rpt_trabajador_prestamos.js'),
    ));

    $this->load->library('pagination');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Recibo de Vacaciones');

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_recibo_vacaciones',$params);
    $this->load->view('panel/footer',$params);
  }

  public function recibo_vacaciones_pdf()
  {
    $this->load->model('nomina_fiscal_model');

    $_GET = array_merge(array(
      'fid_trabajador' => isset($_GET['fid_trabajador']) ?: '',
      'fsalario_real' => isset($_GET['fsalario_real']) ?: '',
      'fdias' => isset($_GET['fdias']) ?: '',
    ), $_GET);

    $this->nomina_fiscal_model->printReciboVacaciones($_GET);
  }

  public function recibo_finiquito()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/rpt_trabajador_prestamos.js'),
    ));

    $this->load->library('pagination');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Recibo de Finiquito');

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_recibo_finiquito',$params);
    $this->load->view('panel/footer',$params);
  }

  public function recibo_finiquito_pdf()
  {
    $this->load->model('nomina_fiscal_model');

    $_GET = array_merge(array(
      'fid_trabajador' => isset($_GET['fid_trabajador']) ?: '',
      'fsalario_real' => isset($_GET['fsalario_real']) ?: '',
      'ffecha1' => isset($_GET['ffecha1']) ?: '',
      'ffecha2' => isset($_GET['ffecha2']) ?: '',
    ), $_GET);

    $this->nomina_fiscal_model->printReciboFiniquito($_GET);
  }

  public function recibo_incapacidad()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/rpt_trabajador_prestamos.js'),
    ));

    $this->load->library('pagination');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Recibo de Incapacidad');

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_recibo_incapacidad',$params);
    $this->load->view('panel/footer',$params);
  }

  public function recibo_incapacidad_pdf()
  {
    $this->load->model('nomina_fiscal_model');

    $_GET = array_merge(array(
      'fid_trabajador' => isset($_GET['fid_trabajador']) ?: '',
      'fsalario_real' => isset($_GET['fsalario_real']) ?: '',
      'ffecha_inicio' => isset($_GET['ffecha_inicio']) ?: '',
      'fdias_incapacidad' => isset($_GET['fdias_incapacidad']) ?: '',
      'fincapacidad_seguro' => isset($_GET['fincapacidad_seguro']) ?: '',
    ), $_GET);

    $this->nomina_fiscal_model->printReciboIncapacidad($_GET);
  }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */
  public function ajax_add_nomina_empleado()
  {
    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');

    $empresaDefault = $this->empresas_model->getDefaultEmpresa();
    $empresaId = isset($_POST['empresa_id']) ? $_POST['empresa_id'] : $empresaDefault->id_empresa;

    $result = $this->nomina_fiscal_model->add_nominas($_POST, $empresaId, $_POST['empleado_id']);

    echo json_encode($result);
  }

  public function ajax_add_prenomina_empleado()
  {
    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');

    $empresaDefault = $this->empresas_model->getDefaultEmpresa();
    $empresaId = isset($_POST['empresa_id']) ? $_POST['empresa_id'] : $empresaDefault->id_empresa;

    $result = $this->nomina_fiscal_model->add_prenominas($_POST, $empresaId, $_POST['empleado_id']);

    echo json_encode($result);
  }

  public function ajax_get_empleado()
  {
    $filtros = array('semana' => $_POST['semana'], 'empresaId' => $_POST['empresa_id']);
    $this->load->model('nomina_fiscal_model');
    $_GET['cid_empresa'] = $filtros['empresaId']; //para las cuentas del contpaq
    $configuraciones = $this->nomina_fiscal_model->configuraciones();
    $empleado = $this->nomina_fiscal_model->nomina($configuraciones, $filtros, $_POST['empleado_id'], $_POST['horas_extras'], null, null, null, $_POST['ptu']);
    echo json_encode($empleado);
  }

  public function ajax_get_semana()
  {
    $this->load->model('nomina_fiscal_model');
    $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $_GET['did_empresa'])->get()->row()->dia_inicia_semana;
    echo json_encode($this->nomina_fiscal_model->semanasDelAno($dia));
  }


  public function nomina_fiscal_pdf()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfNominaFiscal($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
  }

  public function nomina_fiscal_rpt_pdf()
  {
    $this->load->model('nomina_fiscal_model');
    if(isset($_POST['xls']{0}))
      $this->nomina_fiscal_model->xlsRptNominaFiscal($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
    else
      $this->nomina_fiscal_model->pdfRptNominaFiscal($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
  }

  public function nomina_fiscal_cfdis()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->descargarZipNomina($_GET['semana'], $_GET['empresaId']);
  }

  public function nomina_fiscal_banco()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->descargarTxtBanco($_GET['semana'], $_GET['empresaId']);
  }

  public function recibo_nomina_pdf()
  {
    $anio = isset($_GET['anio'])?$_GET['anio']:date("Y");
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfReciboNominaFiscal($_GET['empleadoId'], $_GET['semana'], $anio, $_GET['empresaId']);
  }

  public function asistencia_pdf()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->asistencia_pdf($_GET['id'], $_GET['sem'], $_GET['anio']);
  }

  /*
   |-------------------------------------------------------------------------
   |  MESAJES ALERTAS
   |-------------------------------------------------------------------------
   */

  /**
   * Muestra mensajes cuando se realiza alguna accion
   * @param unknown_type $tipo
   * @param unknown_type $msg
   * @param unknown_type $title
   */
  private function showMsgs($tipo, $msg='', $title='Nomina Fiscal!'){
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
        $txt = 'Los datos se guardaron correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'Las nominas se generaron y guardaron correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'Ocurrio un problema al intentar emitir la nomina de algun empleado, vuelva a intentarlo.';
        $icono = 'error';
        break;
      case 6:
        $txt = 'Especifique un empleado y la fecha de salida.';
        $icono = 'error';
        break;
      case 7:
        $txt = 'El finiquito se genero correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = 'Ocurrio un error al intentar generar el finiquito, intentelo de nuevo.';
        $icono = 'error';
        break;
      case 9:
        $txt = 'Favor de especificar una empresa para generar su nomina.';
        $icono = 'error';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

?>