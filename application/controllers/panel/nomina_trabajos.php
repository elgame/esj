<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_trabajos extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'nomina_trabajos/ajax_save/',
    'nomina_trabajos/nomina_fiscal_ticket/',
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

  /**
   * Agrega una factura a la bd
   *
   * @return void
   */
  public function index()
  {
    $this->carabiner->css(array(
      array('panel/general_sanjorge.css')
    ));
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/keyjump.js'),
      array('libs/jquery.numeric.js'),
      array('panel/nomina_fiscal/nomina_trabajos.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Nomina Fiscal - Asistencia');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');
    $this->load->model('nomina_trabajos_model');
    $this->load->model('usuarios_departamentos_model');

    $params['empresaDefault'] = $this->empresas_model->getDefaultEmpresa();
    $params['fecha'] = isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d');

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
    $params['infoE'] = $this->nomina_trabajos_model->info($params['fecha'], $filtros['empresaId']);
    // $params['empresas'] = $this->empresas_model->getEmpresasAjax();
    // $params['puestos'] = $this->usuarios_model->departamentos(); //puestos();

    $_GET['did_empresa'] = $filtros['empresaId'];
    $params['puestos'] = $this->usuarios_departamentos_model->getPuestos(false); //puestos();
    $params['sat_incapacidades'] = $this->nomina_fiscal_model->satCatalogoIncapacidades();

    // $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno($dia, $filtros['anio']);

    // // Determina cual es la semana que dejara seleccionada en la vista.
    // $semanaActual = $this->nomina_fiscal_model->semanaActualDelMes();
    // $params['numSemanaSelected'] = isset($_GET['semana']) ? $_GET['semana'] : $semanaActual['semana'];

    // // Obtiene los rangos de fecha de la semana seleccionada para obtener
    // // las fechas de los 7 dias siguientes.
    // $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($params['numSemanaSelected'], $filtros['anio'], $dia);
    // $params['dias'] = String::obtenerSiguientesXDias($semana['fecha_inicio'], 7);

    // $params['sat_incapacidades'] = $this->nomina_fiscal_model->satCatalogoIncapacidades();



    $this->load->model('compras_areas_model');

    $params['areas'] = $this->compras_areas_model->getTipoAreas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/nomina_fiscal/nomina_trabajos', $params);
    $this->load->view('panel/footer');
  }

  /*
   |------------------------------------------------------------------------
   | METODOS AJAX
   |------------------------------------------------------------------------
   */

  public function ajax_save()
  {
    $this->load->model('nomina_trabajos_model');
    echo json_encode($this->nomina_trabajos_model->save($_POST));
  }


  public function nomina_fiscal_ticket()
  {
    $this->load->model('nomina_trabajos_model');
    $this->nomina_trabajos_model->ticketNominaFiscal($_GET['semana'], $_GET['empresaId'], $_GET['anio']);
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
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

?>