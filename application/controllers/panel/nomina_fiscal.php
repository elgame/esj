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
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('panel/facturacion/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('facturacion_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Facturas');

    $params['datos_s'] = $this->facturacion_model->getFacturas();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/admin',$params);
    $this->load->view('panel/footer',$params);
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
    $params['pagar_ordent']   = false;

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    $params['empresaDefault'] = $this->empresas_model->getDefaultEmpresa();

    $filtros = array(
      'semana'    => isset($_GET['semana']) ? $_GET['semana'] : '',
      'empresaId' => isset($_GET['empresaId']) ? $_GET['empresaId'] : $params['empresaDefault']->id_empresa,
      'puestoId'  => isset($_GET['puestoId']) ? $_GET['puestoId'] : '',
    );

    // Datos para la vista.
    $params['empleados'] = $this->nomina_fiscal_model->listadoEmpleadosAsistencias($filtros);
    $params['empresas'] = $this->empresas_model->getEmpresasAjax();
    $params['puestos'] = $this->usuarios_model->puestos();
    $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno();

    // Determina cual es la semana que dejara seleccionada en la vista.
    $semanaActual = $this->nomina_fiscal_model->semanaActualDelMes();
    $params['numSemanaSelected'] = isset($_GET['semana']) ? $_GET['semana'] : $semanaActual['semana'];

    // Obtiene los rangos de fecha de la semana seleccionada para obtener
    // las fechas de los 7 dias siguientes.
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($params['numSemanaSelected']);
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
    $this->nomina_fiscal_model->addAsistencias($_POST['empleados'], $_POST['numSemana']);

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

    // Obtiene los dias de la semana.
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($_GET['sem']);
    $params['dias'] = String::obtenerSiguientesXDias($semana['fecha_inicio'], 7);
    $params['nombresDias'] = array('Viernes', 'Sabado', 'Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves');

    // Obtiene los bonos y otros que ya tiene el empleado de la semana.
    $params['bonosOtros'] = $this->nomina_fiscal_model->getBonosOtrosEmpleado($_GET['eid'], $_GET['sem']);

    // Obtiene los prestamos que se hicieron en la semana cargada.
    $params['prestamos'] = $this->nomina_fiscal_model->getPrestamosEmpleado($_GET['eid'], $_GET['sem']);

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
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

?>