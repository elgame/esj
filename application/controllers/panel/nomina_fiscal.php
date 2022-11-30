<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_fiscal extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'nomina_fiscal/addAsistencias/',
    'nomina_fiscal/validaAddAsistencias/',
    'nomina_fiscal/show_otros/',
    'nomina_fiscal/bonos_otros/',
    'nomina_fiscal/add_vacaciones/',
    'nomina_fiscal/add_incapacidades/',

    'nomina_fiscal/add_nomina/',
    'nomina_fiscal/ajax_add_nomina_empleado/',
    'nomina_fiscal/ajax_timbrar_nomina_empleado/',
    'nomina_fiscal/ajax_get_empleado/',
    'nomina_fiscal/add_finiquito/',
    'nomina_fiscal/ajax_add_prenomina_empleado/',
    'nomina_fiscal/ajax_get_semana/',
    'nomina_fiscal/ajax_get_reg_patronales/',
    'nomina_fiscal/ajax_add_nomina_ptu_empleado/',
    'nomina_fiscal/ajax_add_nomina_aguinaldo_empleado/',
    'nomina_fiscal/ajax_add_nomina_terminada/',

    'nomina_fiscal/nomina_fiscal_pdf/',
    'nomina_fiscal/nomina_fiscal_cfdis/',
    'nomina_fiscal/nomina_fiscal_banco/',
    'nomina_fiscal/nomina_fiscal_rpt_pdf/',
    'nomina_fiscal/recibo_nomina_pdf/',
    'nomina_fiscal/recibo_vacaciones_pdf/',
    'nomina_fiscal/recibo_finiquito_pdf/',
    'nomina_fiscal/recibo_incapacidad_pdf/',
    'nomina_fiscal/recibos_nomina_pdf/',
    'nomina_fiscal/recibo_tfiniquito_pdf/',

    'nomina_fiscal/recibo_nomina_ptu_pdf/',
    'nomina_fiscal/recibos_nomina_ptu_pdf/',
    'nomina_fiscal/nomina_ptu_pdf/',
    'nomina_fiscal/nomina_ptu_cfdis/',
    'nomina_fiscal/nomina_ptu_banco/',
    'nomina_fiscal/nomina_ptu_rpt_pdf/',

    'nomina_fiscal/recibo_nomina_aguinaldo_pdf/',
    'nomina_fiscal/recibos_nomina_aguinaldo_pdf/',
    'nomina_fiscal/nomina_aguinaldo_pdf/',
    'nomina_fiscal/nomina_aguinaldo_cfdis/',
    'nomina_fiscal/nomina_aguinaldo_banco/',
    'nomina_fiscal/nomina_aguinaldo_rpt_pdf/',

    'nomina_fiscal/rpt_vacaciones_pdf/',
    'nomina_fiscal/rpt_pdf/',
    'nomina_fiscal/rpt_xls/',
    'nomina_fiscal/asistencia_pdf/',
    'nomina_fiscal/cancelar/',
    'nomina_fiscal/cancelar_ptu/',
    'nomina_fiscal/cancelar_aguinaldo/',
    'nomina_fiscal/rpt_dim/',
    'nomina_fiscal/calc_anual/',

    'nomina_fiscal/show_import_asistencias/',
    'nomina_fiscal/parcheGeneraXML/',

    'nomina_fiscal/show_import_nomina_corona/',
    'nomina_fiscal/download_descuentos_corona/',

    'nomina_fiscal/cuadro_antiguedad_pdf/',
    'nomina_fiscal/cuadro_antiguedad_xls/',

    'nomina_fiscal/nominas_empleados_pdf/',
    'nomina_fiscal/nominas_empleados_xls/',
    'nomina_fiscal/rpt_asistencias_pdf/',
    'nomina_fiscal/rpt_asistencias_xls/',
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

  public function parcheGeneraXML()
  {
    $nominas = $this->db->query("SELECT * FROM public.nomina_fiscal WHERE id_empresa = 2 and semana = 48")->result();
    foreach ($nominas as $key => $value) {
      $cfdi_ext = strlen(trim($value->cfdi_ext))>0? "'".pg_escape_string($value->cfdi_ext)."'": 'NULL';
      $otros_datos = strlen(trim($value->otros_datos))>0? "'".pg_escape_string($value->otros_datos)."'": 'NULL';
      echo "INSERT INTO public.nomina_fiscal(id_empleado, id_empresa, anio, semana, fecha_inicio, fecha_final, fecha, dias_trabajados, salario_diario, salario_integral, subsidio, sueldo_semanal, bonos, otros, subsidio_pagado, vacaciones, prima_vacacional_grabable, prima_vacacional_exento, prima_vacacional, aguinaldo_grabable, aguinaldo_exento, aguinaldo, total_percepcion, imss, vejez, isr, infonavit, subsidio_cobrado, prestamos, deduccion_otros, total_deduccion, total_neto, id_empleado_creador, ptu_exento, ptu_grabable, ptu, id_puesto, salario_real, sueldo_real, total_no_fiscal, horas_extras, horas_extras_grabable, horas_extras_excento, descuento_playeras, xml, uuid, utilidad_empresa, descuento_otros, domingo, esta_asegurado, fondo_ahorro, pasistencia, despensa, cfdi_ext, descuento_cocina, otros_datos)
            VALUES ({$value->id_empleado}, {$value->id_empresa}, {$value->anio}, {$value->semana}, '{$value->fecha_inicio}', '{$value->fecha_final}', '{$value->fecha}', {$value->dias_trabajados}, {$value->salario_diario}, {$value->salario_integral}, {$value->subsidio}, {$value->sueldo_semanal}, {$value->bonos}, {$value->otros}, {$value->subsidio_pagado}, {$value->vacaciones}, {$value->prima_vacacional_grabable}, {$value->prima_vacacional_exento}, {$value->prima_vacacional}, {$value->aguinaldo_grabable}, {$value->aguinaldo_exento}, {$value->aguinaldo}, {$value->total_percepcion}, {$value->imss}, {$value->vejez}, {$value->isr}, {$value->infonavit}, {$value->subsidio_cobrado}, {$value->prestamos}, {$value->deduccion_otros}, {$value->total_deduccion}, {$value->total_neto}, {$value->id_empleado_creador}, {$value->ptu_exento}, {$value->ptu_grabable}, {$value->ptu}, {$value->id_puesto}, {$value->salario_real}, {$value->sueldo_real}, {$value->total_no_fiscal}, {$value->horas_extras}, {$value->horas_extras_grabable}, {$value->horas_extras_excento}, {$value->descuento_playeras}, '".pg_escape_string($value->xml)."', '{$value->uuid}', {$value->utilidad_empresa}, {$value->descuento_otros}, {$value->domingo}, '{$value->esta_asegurado}', {$value->fondo_ahorro}, {$value->pasistencia}, {$value->despensa}, {$cfdi_ext}, {$value->descuento_cocina}, {$otros_datos});\n\n";
    }


    // $nominas = $this->db->query("SELECT nf.*, u.rfc FROM nomina_fiscal nf
    //   INNER JOIN usuarios u ON u.id = nf.id_empleado WHERE nf.anio = 2019 AND nf.uuid <> ''
    //   ORDER BY nf.id_empresa ASC")->result();
    // $this->load->library('cfdi');
    // $auxempresa = 0;
    // foreach ($nominas as $key => $value) {
    //   if ($auxempresa != $value->id_empresa) {
    //     $this->cfdi->cargaDatosFiscales($value->id_empresa);
    //     $auxempresa = $value->id_empresa;
    //   }
    //   $this->cfdi->anio = $value->anio;
    //   $this->cfdi->semana = $value->semana;
    //   $this->cfdi->guardarXMLNomina($value->xml, $value->rfc);
    // }

    // // $nominas = $this->db->query("SELECT f.* FROM facturacion f WHERE Date(f.fecha) BETWEEN '2019-01-01' AND '2019-05-30' AND f.uuid <> ''
    // //   ORDER BY f.id_empresa ASC")->result();
    // // $auxempresa = 0;
    // // foreach ($nominas as $key => $value) {
    // //   if ($auxempresa != $value->id_empresa) {
    // //     $this->cfdi->cargaDatosFiscales($value->id_empresa);
    // //     $auxempresa = $value->id_empresa;
    // //   }
    // //   $this->cfdi->guardarXMLFactura($value->xml, $this->cfdi->rfc, $value->serie, $value->folio, $value->fecha);
    // // }
  }

  public function rpt_dim()
  {
    $this->load->model('nomina_fiscal_otros_model');
    $this->nomina_fiscal_otros_model->rpt_dim();
  }

  public function calc_anual()
  {
    // $this->load->model('nomina_fiscal_otros_model');
    // $this->nomina_fiscal_otros_model->setSubsidioCausado(2018, 2);

    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      // array('panel/nomina_fiscal/bonos_otros.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Nomina Fiscal'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Nomina Fiscal - Calculo anual');

    $anio = isset($_GET['anio'])? $_GET['anio']: date("Y");
    $empresaId = isset($_GET['empresaId'])? $_GET['empresaId']: 0;

    $tipo = 'tabla';
    if (isset($_POST['guardar'])) {
      $tipo = 'guardar';
    } elseif (isset($_POST['descargar'])) {
      $tipo = 'descargar';
    }

    $this->load->model('nomina_fiscal_otros_model');
    $datos = $this->db->query("SELECT Count(*) AS numeros FROM nomina_calculo_anual WHERE anio = {$anio}")->row();
    $params['calculo'] = $this->nomina_fiscal_otros_model->data_calc_anual($empresaId, $anio, $tipo);
    $params['guardado'] = $datos->numeros;
    if ($tipo === 'guardar') {
      redirect(base_url('panel/nomina_fiscal/calc_anual?'.MyString::getVarsLink(array('msg')).'&'));
    }

    if(isset($_GET['msg']{0}))
    {
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      if ($_GET['msg'] === '3')
      {
        $params['close'] = true;
      }
    }

    $this->load->view('panel/nomina_fiscal/calculo_anual', $params);
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
      'calcMes'     => isset($_GET['calcMes']) ? $_GET['calcMes'] : false,
      'semana'      => isset($_GET['semana']) ? $_GET['semana'] : '',
      'anio'        => isset($_GET['anio']) ? $_GET['anio'] : date("Y"),
      'empresaId'   => isset($_GET['empresaId']) ? $_GET['empresaId'] : $params['empresaDefault']->id_empresa,
      'puestoId'    => isset($_GET['puestoId']) ? $_GET['puestoId'] : '',
      'regPatronal' => isset($_GET['fregistro_patronal']) ? $_GET['fregistro_patronal'] : '',
      'tipo_nomina' => ['tipo' => 'se', 'con_vacaciones' => '0', 'con_aguinaldo' => '0']
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
    $configuraciones = $this->nomina_fiscal_model->configuraciones($filtros['anio']);
    $params['empleados'] = $this->nomina_fiscal_model->nomina($configuraciones, $filtros);
    $params['empresas'] = $this->empresas_model->getEmpresasAjax();
    $params['puestos'] = $this->usuarios_model->puestos();
    // $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno();

    $params['empresa'] = $this->empresas_model->getInfoEmpresa($filtros['empresaId'], true)['info'];
    $params['registros_patronales'] = explode('|', (isset($params['empresa']->registro_patronal)? $params['empresa']->registro_patronal: ''));

    $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno($dia, $filtros['anio']);
    $params['tipoNomina'] = ($dia == 15? 'quincena': 'semana');

    // Determina cual es la semana que dejara seleccionada en la vista.
    $semanaActual = $this->nomina_fiscal_model->semanaActualDelMes(null, 0, $dia);
    $params['numSemanaSelected'] = isset($_GET['semana']) ? $_GET['semana'] : $semanaActual[$params['tipoNomina']];
    $filtros['semana'] = $filtros['semana'] != ''? $filtros['semana'] : $semanaActual[$params['tipoNomina']];

    // Obtiene los rangos de fecha de la semana seleccionada para obtener
    // las fechas de los 7 dias siguientes.
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($params['numSemanaSelected'], $filtros['anio'], $dia);
    $params['dias'] = MyString::obtenerSiguientesXDias($semana['fecha_inicio'], ($dia == 15? 15: 7));
    $anio = (new DateTime($semana['fecha_inicio']))->format('Y');

    $params['sat_incapacidades'] = $this->nomina_fiscal_model->satCatalogoIncapacidades();

    // Query para saber si existen nominas generadas para la semana.
    // $query = $this->db->query(
    //   "SELECT COUNT(id_empleado) as total_nominas
    //    FROM nomina_fiscal
    //    WHERE id_empresa = {$filtros['empresaId']} AND anio = {$anio} AND semana = {$semana['semana']}"
    // )->result();


    $data_nom_guar = $this->db->query("SELECT Count(*) AS num
      FROM nomina_fiscal_guardadas
      WHERE id_empresa = {$filtros['empresaId']} AND anio = {$filtros['anio']}
        AND semana = {$filtros['semana']} AND tipo = 'se'
        AND registro_patronal = '{$filtros['regPatronal']}'")->row();
    $params['nominas_generadas'] = $data_nom_guar->num > 0? true: false;

    // Total de nominas de los empleados generadas.
    $totalGeneradas = 0;
    foreach ($params['empleados'] as $empleado)
    {
      if ($empleado->esta_generada !== 'false')
      {
        $totalGeneradas++;
        // $params['nominas_generadas'] = true;
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

  public function ptu()
  {
    if (isset($_GET['empresaId']) && $_GET['empresaId'] === '')
      redirect(base_url('panel/nomina_fiscal?msg=9'));

    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('libs/jquery.numeric.js'),
      array('general/util.js'),
      array('panel/nomina_fiscal/nomina_ptu.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Nomina Fiscal');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    $params['empresaDefault'] = $this->empresas_model->getDefaultEmpresa();

    $filtros = array(
      'semana'      => isset($_GET['semana']) ? $_GET['semana'] : '',
      'anio'        => isset($_GET['anio']) ? $_GET['anio'] : date("Y"),
      'empresaId'   => isset($_GET['empresaId']) ? $_GET['empresaId'] : $params['empresaDefault']->id_empresa,
      'puestoId'    => isset($_GET['puestoId']) ? $_GET['puestoId'] : '',
      'regPatronal' => isset($_GET['fregistro_patronal']) ? $_GET['fregistro_patronal'] : '',
      'asegurado'   => true,
      'tipo_nomina' => ['tipo' => 'ptu', 'con_vacaciones' => '0', 'con_aguinaldo' => '0']
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

    $params['ptu'] = isset($_POST['ptu']) ? $_POST['ptu'] : null;

    // Datos para la vista.
    $this->load->model('nomina_fiscal_model');
    $_GET['cid_empresa'] = $filtros['empresaId']; //para las cuentas del contpaq
    $configuraciones = $this->nomina_fiscal_model->configuraciones($filtros['anio']);
    $params['empleados'] = $this->nomina_fiscal_model->nomina($configuraciones, $filtros, null, null, null, null, null, $params['ptu'], null, 'ptu');
    $params['all_efectivo'] = isset($_POST['en_efectivo']{0}) ? true : false; // que se pague todo en efectivo
    $params['empresas'] = $this->empresas_model->getEmpresasAjax();
    $params['puestos'] = $this->usuarios_model->puestos();
    // $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno();

    $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno($dia, $filtros['anio']);
    $params['tipoNomina'] = ($dia == 15? 'quincena': 'semana');

    // // Ajuste de Ptu
    // // ===================
    // $this->load->model('nomina_ajustes_model');
    // $this->nomina_ajustes_model->confAjustePtu2019()->ajustePtu2019($params['empleados']);
    // // echo "<pre>";
    // // var_dump($params['empleados']);
    // // echo "</pre>";exit;

    // Determina cual es la semana que dejara seleccionada en la vista.
    $semanaActual = $this->nomina_fiscal_model->semanaActualDelMes(null, 0, $dia);
    $params['numSemanaSelected'] = isset($_GET['semana']) ? $_GET['semana'] : $semanaActual[$params['tipoNomina']];

    // Obtiene los rangos de fecha de la semana seleccionada para obtener
    // las fechas de los 7 dias siguientes.
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($params['numSemanaSelected'], $filtros['anio'], $dia);
    $params['dias'] = MyString::obtenerSiguientesXDias($semana['fecha_inicio'], ($dia == 15? 15: 7));
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
      if ($empleado->ptu_generado !== 'false')
      {
        $totalGeneradas++;
        $params['nominas_generadas'] = true;
      }
    }

    $params['empresa'] = $this->empresas_model->getInfoEmpresa($filtros['empresaId'], true)['info'];
    $params['registros_patronales'] = explode('|', (isset($params['empresa']->registro_patronal)? $params['empresa']->registro_patronal: ''));

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
    $this->load->view('panel/nomina_fiscal/nomina_ptu', $params);
    $this->load->view('panel/footer');
  }

  public function aguinaldo()
  {
    if (isset($_GET['empresaId']) && $_GET['empresaId'] === '')
      redirect(base_url('panel/nomina_fiscal?msg=9'));

    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('libs/jquery.numeric.js'),
      array('general/util.js'),
      array('panel/nomina_fiscal/nomina_aguinaldo.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Nomina Fiscal');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    $params['empresaDefault'] = $this->empresas_model->getDefaultEmpresa();

    $filtros = array(
      'semana'      => isset($_GET['semana']) ? $_GET['semana'] : '',
      'anio'        => isset($_GET['anio']) ? $_GET['anio'] : date("Y"),
      'regPatronal' => isset($_GET['fregistro_patronal']) ? $_GET['fregistro_patronal'] : '',
      'empresaId'   => isset($_GET['empresaId']) ? $_GET['empresaId'] : $params['empresaDefault']->id_empresa,
      'puestoId'    => isset($_GET['puestoId']) ? $_GET['puestoId'] : '',
      'tipo_nomina' => ['tipo' => 'ag', 'con_vacaciones' => '0', 'con_aguinaldo' => '1']
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

    $params['ptu'] = isset($_POST['ptu']) ? $_POST['ptu'] : null;

    $_POST['con_aguinaldo'] = '1';
    $_POST['con_vacaciones'] = '0';
    $_POST['horas_extras'] = '0';
    $_POST['ptu'] = '0';

    // Datos para la vista.
    $this->load->model('nomina_fiscal_model');
    $_GET['cid_empresa'] = $filtros['empresaId']; //para las cuentas del contpaq
    $configuraciones = $this->nomina_fiscal_model->configuraciones($filtros['anio']);
    $params['empleados'] = $this->nomina_fiscal_model->nomina($configuraciones, $filtros, null, null, null, null, null, null, null, 'ag');
    $params['empresas'] = $this->empresas_model->getEmpresasAjax();
    $params['puestos'] = $this->usuarios_model->puestos();
    // $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno();

    $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno($dia, $filtros['anio']);
    $params['tipoNomina'] = ($dia == 15? 'quincena': 'semana');

    // Determina cual es la semana que dejara seleccionada en la vista.
    $semanaActual = $this->nomina_fiscal_model->semanaActualDelMes(null, 0, $dia);
    $params['numSemanaSelected'] = isset($_GET['semana']) ? $_GET['semana'] : $semanaActual[$params['tipoNomina']];

    // Obtiene los rangos de fecha de la semana seleccionada para obtener
    // las fechas de los 7 dias siguientes.
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($params['numSemanaSelected'], $filtros['anio'], $dia);
    $params['dias'] = MyString::obtenerSiguientesXDias($semana['fecha_inicio'], ($dia == 15? 15: 7));
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
      if ($empleado->aguinaldo_generado !== 'false' || $empleado->esta_asegurado == 'f')
      {
        $totalGeneradas++;
        $params['nominas_generadas'] = true;
      }
    }

    $params['empresa'] = $this->empresas_model->getInfoEmpresa($filtros['empresaId'], true)['info'];
    $params['registros_patronales'] = explode('|', (isset($params['empresa']->registro_patronal)? $params['empresa']->registro_patronal: ''));

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
    $this->load->view('panel/nomina_fiscal/nomina_aguinaldo', $params);
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

    redirect(base_url('panel/nomina_fiscal/?'.MyString::getVarsLink(array('msg')).'&msg='.$msg));
  }

  /**
   * Agrega una factura a la bd
   *
   * @return void
   */
  public function asistencia()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
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
    $params['dias'] = MyString::obtenerSiguientesXDias($semana['fecha_inicio'], 7);

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
    if (isset($_POST['ajax'])) {
      foreach ($_POST['empleados'] as $key => $dias) {
        foreach ($dias as $keydd => $value) {
          $datos[$key][$value['fecha']] = $value['valor'];
        }
      }
    } else
      $datos = $_POST['empleados'];

    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->addAsistencias($datos, $_POST['numSemana'], $_GET['did_empresa'], $_GET['anio']);

    if (isset($_POST['ajax'])) {
      echo json_encode(['response' => true]);
    } else
      redirect(base_url('panel/nomina_fiscal/asistencia/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
  }

  public function validaAddAsistencias()
  {
    $this->load->model('nomina_fiscal_model');
    $response['error'] = $this->nomina_fiscal_model->validaAddAsistencias($_POST['empleados'], $_POST['numSemana'], $_POST['empresaId'], $_POST['anio']);

    echo json_encode($response);
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
    $params['dias'] = MyString::obtenerSiguientesXDias($semana['fecha_inicio'], 7);
    foreach ($params['dias'] as $key => $value)
      $params['nombresDias'][] = MyString::dia($value);

    // Obtiene los bonos y otros que ya tiene el empleado de la semana.
    $params['bonosOtros'] = $this->nomina_fiscal_model->getBonosOtrosEmpleado($_GET['eid'], $_GET['sem'], $anio, $params['empleado']['info'][0]->dia_inicia_semana);

    // Obtiene los prestamos que se hicieron en la semana cargada.
    $params['prestamos'] = $this->nomina_fiscal_model->getPrestamosEmpleado($_GET['eid'], $_GET['sem'], $anio, $params['empleado']['info'][0]->dia_inicia_semana);

    // Obtiene el registro si se agrego vacaciones.
    $params['vacaciones'] = $this->nomina_fiscal_model->getVacacionesEmpleado($_GET['eid'], $_GET['sem'], $anio, $params['empleado']['info'][0]->dia_inicia_semana);

    //Incapacidades
    $params['sat_incapacidades'] = $this->nomina_fiscal_model->satCatalogoIncapacidades();
    $params['incapacidades'] = $this->nomina_fiscal_model->getIncapacidadesEmpleado($_GET['eid'], $_GET['sem'], $anio, $params['empleado']['info'][0]->dia_inicia_semana);

    $params['metods_pago']  = array(
      array('nombre' => 'Transferencia', 'value' => 'transferencia'),
      array('nombre' => 'Cheque', 'value' => 'cheque'),
      array('nombre' => 'Efectivo', 'value' => 'efectivo'),
      array('nombre' => 'Deposito', 'value' => 'deposito'),
    );

    $this->load->model('banco_cuentas_model');
    $data['id_empresa'] = $params['empleado'];
    $pdatos = ['id_empresa' => $params['empleado']['info'][0]->id_empresa];
    $cuentas = $this->banco_cuentas_model->getCuentas(false, null, $pdatos);
    $params['cuentas'] = $cuentas['cuentas'];

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

  public function show_import_asistencias()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('panel/nomina_fiscal/bonos_otros.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Nomina Fiscal'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Nomina Fiscal - Importar asistencias');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');

    $anio = isset($_GET['anio'])? $_GET['anio']: date("Y");
    // Obtiene la informacion de la empresa.
    $params['empresa'] = $this->empresas_model->getInfoEmpresa($_GET['id'])['info'];


    // Obtiene los dias de la semana.
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($_GET['sem'], $anio, $params['empresa']->dia_inicia_semana);
    $params['semana'] = $semana;
    $_POST['dia_inicia_semana'] = $params['empresa']->dia_inicia_semana;

    if (isset($_POST['id_empresa'])) {
      $this->configImportarAsistencias();
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $this->load->model('nomina_fiscal_otros_model');
        $res_mdl = $this->nomina_fiscal_otros_model->importAsistencias2($semana);
        if (isset($res_mdl['resumen']) && count($res_mdl['resumen']) > 0) {
          $res_mdl['error'] = '503';
          $params['resumen'] = $res_mdl['resumen'];
        }

        $_GET['msg'] = $res_mdl['error'];
      }
    }

    if(isset($_GET['msg']{0}))
    {
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      if ($_GET['msg'] === '500')
      {
        $params['close'] = true;
      }
    }

    $this->load->view('panel/nomina_fiscal/importar_asistencias', $params);
  }

  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configImportarAsistencias()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'id_empresa',
            'label' => 'Empresa',
            'rules' => 'required|is_natural'),
      array('field' => 'semana',
            'label' => 'Semana',
            'rules' => 'required|is_natural'),
      array('field' => 'anio',
            'label' => 'Año',
            'rules' => 'required|is_natural'),
    );

    $this->form_validation->set_rules($rules);
  }

  public function show_import_nomina_corona()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('panel/nomina_fiscal/bonos_otros.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Nomina Fiscal'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Nomina Fiscal - Importar Nomina Corona');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');

    $anio = isset($_GET['anio'])? $_GET['anio']: date("Y");
    // Obtiene la informacion de la empresa.
    $params['empresa'] = $this->empresas_model->getInfoEmpresa($_GET['id'])['info'];


    // Obtiene los dias de la semana.
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($_GET['sem'], $anio, $params['empresa']->dia_inicia_semana);
    $params['semana'] = $semana;

    if (isset($_POST['id_empresa'])) {
      $this->configImportarNominaCorona();
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $this->load->model('nomina_fiscal_otros_model');
        if (isset($_POST['btnActividades'])) {
          $filtros = array_merge($semana, $_POST);
          $res_mdl = $this->nomina_fiscal_otros_model->importNomina($filtros);
        } else {
          $res_mdl = $this->nomina_fiscal_otros_model->importNominaCorina($semana);
        }
        $_GET['msg'] = $res_mdl['error'];

        if (isset($res_mdl['resumen']) && count($res_mdl['resumen']) > 0) {
          $params['resumen'] = $res_mdl['resumen'];
          $_GET['msg'] = '556';
        }

      }
    }

    if(isset($_GET['msg']{0}))
    {
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      if ($_GET['msg'] === '550')
      {
        $params['close'] = true;
      }
    }

    $this->load->view('panel/nomina_fiscal/importar_nomina_corona', $params);
  }

  public function download_descuentos_corona()
  {
    if (!empty($_GET['id']) && !empty($_GET['sem']) && !empty($_GET['anio'])) {
      $this->load->model('nomina_fiscal_otros_model');
      $this->nomina_fiscal_otros_model->descargarNominaCorona($_GET);
    }
  }

  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configImportarNominaCorona()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'id_empresa',
            'label' => 'Empresa',
            'rules' => 'required|is_natural'),
      array('field' => 'semana',
            'label' => 'Semana',
            'rules' => 'required|is_natural'),
      array('field' => 'anio',
            'label' => 'Año',
            'rules' => 'required|is_natural'),
    );

    $this->form_validation->set_rules($rules);
  }


  public function bonos_otros()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->addBonosOtros($_GET['eid'], $_POST, $_GET['sem']);

    redirect(base_url('panel/nomina_fiscal/show_otros/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
  }

  public function add_prestamos()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->addPrestamos($_GET['eid'], $_POST, $_GET['sem'], $_GET['anio']);

    redirect(base_url('panel/nomina_fiscal/show_otros/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
  }

  public function add_vacaciones()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->addVacaciones($_GET['eid'], $_POST, $_GET['sem']);

    redirect(base_url('panel/nomina_fiscal/show_otros/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
  }

  public function add_incapacidades()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->addIncapaciades($_GET['eid'], $_POST, $_GET['sem']);

    redirect(base_url('panel/nomina_fiscal/show_otros/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
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

    $despido['indem_cons'] = isset($_GET['indem_cons'])? true: false;
    $despido['indem']      = isset($_GET['indem'])? true: false;
    $despido['prima']      = isset($_GET['prima'])? true: false;
    $despido['aguin']      = isset($_GET['aguin'])? true: false;

    // Datos para la vista.
    if (isset($_GET['empleadoId']) && $_GET['empleadoId'] !== '' && isset($_GET['fechaSalida']) && $_GET['fechaSalida'] !== '')
    {
      $params['empleado'] = $this->nomina_fiscal_model->finiquito($_GET['empleadoId'], $_GET['fechaSalida'], $despido);
    }
    else if (isset($_GET['empleadoId']) && $_GET['empleadoId'] == '' || isset($_GET['fechaSalida']) && $_GET['fechaSalida'] == '')
    {
      $params['frm_errors'] = $this->showMsgs(6);
    }

    $despido2 = false;
    if ($despido['indem_cons'] || $despido['indem'] || $despido['prima'])
      $despido2 = true;
    $params['indemni'] = $despido2;

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
    $despido['indem_cons'] = isset($_GET['indem_cons'])? true: false;
    $despido['indem']      = isset($_GET['indem'])? true: false;
    $despido['prima']      = isset($_GET['prima'])? true: false;
    $despido['aguin']      = isset($_GET['aguin'])? true: false;
    $result = $this->nomina_fiscal_model->add_finiquito($_GET['empleadoId'], $_GET['fechaSalida'], $despido);

    if ( ! $result['errorTimbrar'])
    {
      redirect(base_url('panel/nomina_fiscal/finiquito/?msg=7'));
    }
    else
    {
      redirect(base_url('panel/nomina_fiscal/finiquito/?'.MyString::getVarsLink(array('msg')).'&msg=8&custom='.$result['msg']));
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
    $this->load->model('nomina_fiscal_otros_model');
    $trabajadorId = isset($_GET['fid_trabajador']) ? $_GET['fid_trabajador'] : false;
    $fecha1 = isset($_GET['ffecha1']) ? $_GET['ffecha1'] : false;
    $fecha2 = isset($_GET['ffecha2']) ? $_GET['ffecha2'] : false;
    $todos = isset($_GET['ftodos']) ? true : false;
    $id_empresa = isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : '0';

    if ($trabajadorId > 0)
      $this->nomina_fiscal_model->rptTrabajadoresPrestamosPdf($trabajadorId, $fecha1, $fecha2, $todos, $id_empresa);
    else
      $this->nomina_fiscal_otros_model->rptTrabajadoresPrestamosPdf($trabajadorId, $fecha1, $fecha2, $todos, $id_empresa);
  }

  public function rpt_xls()
  {
    $this->load->model('nomina_fiscal_model');
    $this->load->model('nomina_fiscal_otros_model');
    $trabajadorId = isset($_GET['fid_trabajador']) ? $_GET['fid_trabajador'] : false;
    $fecha1 = isset($_GET['ffecha1']) ? $_GET['ffecha1'] : false;
    $fecha2 = isset($_GET['ffecha2']) ? $_GET['ffecha2'] : false;
    $todos = isset($_GET['ftodos']) && $_GET['ftodos']=='1' ? true : false;
    $id_empresa = isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : '0';

    if ($trabajadorId > 0)
      $this->nomina_fiscal_otros_model->rptTrabajadoresPrestamosXls1($trabajadorId, $fecha1, $fecha2, $todos, $id_empresa);
    else
      $this->nomina_fiscal_otros_model->rptTrabajadoresPrestamosXls($trabajadorId, $fecha1, $fecha2, $todos, $id_empresa);
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
      'despido' => isset($_GET['despido']) ? true: false,
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
      'fid_trabajador'      => isset($_GET['fid_trabajador']) ?: '',
      'fsalario_real'       => isset($_GET['fsalario_real']) ?: '',
      'ffecha_inicio'       => isset($_GET['ffecha_inicio']) ?: '',
      'fdias_incapacidad'   => isset($_GET['fdias_incapacidad']) ?: '',
      'fincapacidad_seguro' => isset($_GET['fincapacidad_seguro']) ?: '',
      'fporcentaje'         => isset($_GET['fporcentaje']) ?: '100',
    ), $_GET);

    $this->nomina_fiscal_model->printReciboIncapacidad($_GET);
  }

  public function cuadro_antiguedad()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info'];
    $params['seo']           = array('titulo' => 'CUADRO DE ANTIGUEDAD DE LOS TRABAJADORES');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_cuadro_antiguedad',$params);
    $this->load->view('panel/footer',$params);
  }
  public function cuadro_antiguedad_pdf(){
    $this->load->model('nomina_fiscal_otros_model');
    $this->nomina_fiscal_otros_model->getCuadroAntiguedadXls(true);
  }
  public function cuadro_antiguedad_xls(){
    $this->load->model('nomina_fiscal_otros_model');
    $this->nomina_fiscal_otros_model->getCuadroAntiguedadXls();
  }

  public function nominas_empleados()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/rpt_nominas.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info'];
    $params['seo']           = array('titulo' => 'Reporte Acumulado Nominas');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    $params['registros_patronales'] = explode('|', (isset($params['empresa']->registro_patronal)? $params['empresa']->registro_patronal: ''));

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_acumulado_nominas',$params);
    $this->load->view('panel/footer',$params);
  }
  public function nominas_empleados_pdf() {
    $this->load->model('nomina_fiscal_otros_model');
    $this->nomina_fiscal_otros_model->getAcumuladoNominasEmpleadosXls(true);
  }
  public function nominas_empleados_xls() {
    $this->load->model('nomina_fiscal_otros_model');
    $this->nomina_fiscal_otros_model->getAcumuladoNominasEmpleadosXls();
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
  public function ajax_timbrar_nomina_empleado()
  {
    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');

    $empresaDefault = $this->empresas_model->getDefaultEmpresa();
    $empresaId = isset($_POST['empresa_id']) ? $_POST['empresa_id'] : $empresaDefault->id_empresa;

    $result = $this->nomina_fiscal_model->add_nominas_timbrar($_POST, $empresaId, $_POST['empleado_id']);

    echo json_encode($result);
  }
  public function ajax_add_nomina_terminada()
  {
    $this->load->model('nomina_fiscal_model');

    $result = $this->nomina_fiscal_model->add_nomina_terminada($_POST);

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
    $filtros = array('semana' => $_POST['semana'],
                    'anio'        => $_POST['anio'],
                    'empresaId'   => $_POST['empresa_id'],
                    'tipo_nomina' => ['tipo' => 'se', 'con_vacaciones' => $_POST['con_vacaciones'], 'con_aguinaldo' => $_POST['con_aguinaldo']]);
    if ($filtros['empresaId'] !== '')
    {
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $filtros['empresaId'])->get()->row()->dia_inicia_semana;
    }
    else
    {
      $dia = '4';
    }
    $filtros['dia_inicia_semana'] = $dia;

    $this->load->model('nomina_fiscal_model');
    $_GET['cid_empresa'] = $filtros['empresaId']; //para las cuentas del contpaq
    $configuraciones = $this->nomina_fiscal_model->configuraciones($filtros['anio']);
    $empleado = $this->nomina_fiscal_model->nomina($configuraciones, $filtros, $_POST['empleado_id'], $_POST['horas_extras'], null, null, null, $_POST['ptu']);
    echo json_encode($empleado);
  }

  public function ajax_add_nomina_ptu_empleado()
  {
    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');

    $empresaDefault = $this->empresas_model->getDefaultEmpresa();
    $empresaId = isset($_POST['empresa_id']) ? $_POST['empresa_id'] : $empresaDefault->id_empresa;

    $result = $this->nomina_fiscal_model->add_nominas_ptu($_POST, $empresaId, $_POST['empleado_id']);

    echo json_encode($result);
  }

  public function ajax_get_semana()
  {
    $this->load->model('nomina_fiscal_model');
    $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $_GET['did_empresa'])->get()->row()->dia_inicia_semana;
    $anio = isset($_GET['anio'])? $_GET['anio']: null;
    echo json_encode($this->nomina_fiscal_model->semanasDelAno($dia, $anio));
  }

  public function ajax_get_reg_patronales()
  {
    $this->load->model('empresas_model');

    $params['empresa'] = $this->empresas_model->getInfoEmpresa($_GET['did_empresa'], true)['info'];
    $params['registros_patronales'] = explode('|', (isset($params['empresa']->registro_patronal)? $params['empresa']->registro_patronal: ''));

    echo json_encode($params);
  }

  public function nomina_fiscal_pdf()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfNominaFiscal($_GET['semana'], $_GET['empresaId'], $_GET['anio'], $_GET['fregistro_patronal']);
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
    $this->nomina_fiscal_model->descargarZipNomina($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
  }

  public function nomina_fiscal_banco()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->descargarTxtBanco($_GET['semana'], $_GET['empresaId'], $_GET['anio'], $_GET['fregistro_patronal']);
  }

  public function recibo_nomina_pdf()
  {
    $anio = isset($_GET['anio'])?$_GET['anio']:date("Y");
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfReciboNominaFiscal($_GET['empleadoId'], $_GET['semana'], $anio, $_GET['empresaId'], null, $_GET['fregistro_patronal']);
  }

  public function recibo_tfiniquito_pdf()
  {
    $anio = isset($_GET['anio'])?$_GET['anio']:date("Y");
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfReciboNominaFiscalFiniquito($_GET['empleadoId'], $_GET['semana'], $anio, $_GET['empresaId']);
  }

  public function recibos_nomina_pdf()
  {
    $anio = isset($_GET['anio'])?$_GET['anio']:date("Y");
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfRecibNomin($_GET['semana'], $anio, $_GET['empresaId'], $_GET['fregistro_patronal']);
  }

  public function asistencia_pdf()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->asistencia_pdf($_GET['id'], $_GET['sem'], $_GET['anio']);
  }

  public function rpt_asistencias()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_inventarios.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('productos_model');
    $this->load->model('almacenes_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de Asistencias');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_asistencias',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_asistencias_pdf(){
    $this->load->model('nomina_fiscal_otros_model');
    $this->nomina_fiscal_otros_model->getRptAsistenciasPdf();
  }
  public function rpt_asistencias_xls(){
    $this->load->model('nomina_fiscal_otros_model');
    $this->nomina_fiscal_otros_model->getRptAsistenciasXls();
  }

  public function recibo_nomina_ptu_pdf()
  {
    $anio = isset($_GET['anio'])?$_GET['anio']:date("Y");
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfReciboNominaFiscalPtu($_GET['empleadoId'], $_GET['semana'], $anio, $_GET['empresaId']);
  }

  public function recibos_nomina_ptu_pdf()
  {
    $anio = isset($_GET['anio'])?$_GET['anio']:date("Y");
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfRecibNominPtu($_GET['semana'], $anio, $_GET['empresaId']);
  }

  public function nomina_ptu_pdf()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfNominaFiscalPtu($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
  }

  public function nomina_ptu_banco()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->descargarTxtBancoPtu($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
  }

  public function nomina_ptu_rpt_pdf()
  {
    $this->load->model('nomina_fiscal_model');
    if(isset($_POST['xls']{0}))
      $this->nomina_fiscal_model->xlsRptNominaPtu($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
    else
      $this->nomina_fiscal_model->pdfRptNominaPtu($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
  }

  public function cancelar()
  {
    if (isset($_GET['empleadoId']{0}) && isset($_GET['anio']{0}) && isset($_GET['semana']{0}) && isset($_GET['empresaId']{0}) && !empty($_GET['fregistro_patronal']))
    {
      $this->load->model('nomina_fiscal_model');
      $response = $this->nomina_fiscal_model->cancelaFactura($_GET['empleadoId'], $_GET['anio'], $_GET['semana'], $_GET['empresaId'], $_GET['fregistro_patronal']);

      // if ($response['cancelada']) {
      //   $this->db->delete('nomina_fiscal_guardadas', array('id_empresa' => $_GET['empresaId'], 'anio' => $_GET['anio'],
      //     'semana' => $_GET['semana'], 'tipo' => 'se'));
      // }

      redirect(base_url("panel/nomina_fiscal/?msg={$response['msg']}&anio={$_GET['anio']}&empresa={$response['empresa']}&empresaId={$_GET['empresaId']}&semana={$_GET['semana']}"));
    }
  }

  public function cancelar_ptu()
  {
    if (isset($_GET['empleadoId']{0}) && isset($_GET['anio']{0}) && isset($_GET['semana']{0}) && isset($_GET['empresaId']{0}))
    {
      $this->load->model('nomina_fiscal_model');
      $response = $this->nomina_fiscal_model->cancelaPtu($_GET['empleadoId'], $_GET['anio'], $_GET['semana'], $_GET['empresaId']);

      redirect(base_url("panel/nomina_fiscal/ptu?msg={$response['msg']}&anio={$_GET['anio']}&empresa={$response['empresa']}&empresaId={$_GET['empresaId']}&semana={$_GET['semana']}"));
    }
  }

  public function cancelar_aguinaldo()
  {
    if (isset($_GET['empleadoId']{0}) && isset($_GET['anio']{0}) && isset($_GET['semana']{0}) && isset($_GET['empresaId']{0}))
    {
      $this->load->model('nomina_fiscal_model');
      $response = $this->nomina_fiscal_model->cancelaAguinaldo($_GET['empleadoId'], $_GET['anio'], $_GET['semana'], $_GET['empresaId']);

      redirect(base_url("panel/nomina_fiscal/aguinaldo?msg={$response['msg']}&anio={$_GET['anio']}&empresa={$response['empresa']}&empresaId={$_GET['empresaId']}&semana={$_GET['semana']}"));
    }
  }

  /*
   |------------------------------------------------------------------------
   | aguinaldo
   |------------------------------------------------------------------------
   */

  public function ajax_add_nomina_aguinaldo_empleado()
  {
    $_POST['con_aguinaldo'] = '1';
    $_POST['con_vacaciones'] = '0';
    $_POST['horas_extras'] = '0';
    $_POST['ptu'] = '0';

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');

    $empresaDefault = $this->empresas_model->getDefaultEmpresa();
    $empresaId = isset($_POST['empresa_id']) ? $_POST['empresa_id'] : $empresaDefault->id_empresa;

    $result = $this->nomina_fiscal_model->add_nominas_aguinaldo($_POST, $empresaId, $_POST['empleado_id']);

    echo json_encode($result);
  }

  public function recibo_nomina_aguinaldo_pdf()
  {
    $anio = isset($_GET['anio'])?$_GET['anio']:date("Y");
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfReciboNominaFiscalAguinaldo($_GET['empleadoId'], $_GET['semana'], $anio, $_GET['empresaId']);
  }

  public function recibos_nomina_aguinaldo_pdf()
  {
    $anio = isset($_GET['anio'])?$_GET['anio']:date("Y");
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfRecibNominAguinaldo($_GET['semana'], $anio, $_GET['empresaId']);
  }

  public function nomina_aguinaldo_pdf()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->pdfNominaFiscalAguinaldo($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
  }

  public function nomina_aguinaldo_banco()
  {
    $this->load->model('nomina_fiscal_model');
    $this->nomina_fiscal_model->descargarTxtBancoAguinaldo($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
  }

  public function nomina_aguinaldo_rpt_pdf()
  {
    $this->load->model('nomina_fiscal_model');
    if(isset($_POST['xls']{0}))
      $this->nomina_fiscal_model->xlsRptNominaAguinaldo($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
    else
      $this->nomina_fiscal_model->pdfRptNominaAguinaldo($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
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
        $txt = 'Error al intentar generar el finiquito, '.$_GET['custom'];
        $icono = 'error';
        break;
      case 9:
        $txt = 'Favor de especificar una empresa para generar su nomina.';
        $icono = 'error';
        break;

      case 102:
        $txt = 'El timbrado aun esta pendiente.';
        $icono = 'error';
        break;
      case 201:
        $txt = 'El Recibo se cancelo correctamente.';
        $icono = 'success';
        break;
      case 202:
        $txt = 'El Recibo se cancelo correctamente.';
        $icono = 'success';
        break;
      case 205:
        $txt = 'Error al intentar cancelar: UUID No existente.';
        $icono = 'error';
        break;
      case 708:
        $txt = 'No se pudo conectar al SAT para realizar la cancelación de El Recibo, intentelo mas tarde.';
        $icono = 'error';
        break;

      case 500:
        $txt = 'Las asistencias se guardaron correctamente.';
        $icono = 'success';
        break;
      case 501:
        $txt = 'Ocurrió un error al subir el archivo de asistencias.';
        $icono = 'error';
        break;
      case 502:
        $txt = 'Ocurrió un error al leer el archivo de asistencias.';
        $icono = 'error';
        break;
      case 503:
        $txt = 'Las asistencias se guardaron correctamente, pero hay algunos detalles en algunos trabajadores.';
        $icono = 'error';
        break;

      case 550:
        $txt = 'La nomina se cargo correctamente.';
        $icono = 'success';
        break;
      case 551:
        $txt = 'Ocurrió un error al subir el archivo de nomina.';
        $icono = 'error';
        break;
      case 552:
        $txt = 'Ocurrió un error al leer el archivo de nomina.';
        $icono = 'error';
        break;
      case 553:
        $txt = 'La empresa, semana y/o año son erróneos en el archivo de nomina.';
        $icono = 'error';
        break;
      case 554:
        $txt = 'La empresa, semana y/o año son erróneos en el archivo de nomina.';
        $icono = 'error';
        break;
      case 555:
        $txt = 'La empresa, semana y/o año son erróneos en el archivo de nomina.';
        $icono = 'error';
        break;
      case 556:
        $txt = 'La nomina se cargo correctamente, hay algunos errores en los trabajadores.';
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