<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_ranchos extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'nomina_ranchos/ajax_add_nomina_empleado/',
    'nomina_ranchos/lista_asistencia/',
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
      array('panel/nomina_fiscal/nomina_ranchos.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Nomina Ranchos');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('nomina_ranchos_model');
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

    // Datos para la vista.
    $params['empleados'] = $this->nomina_ranchos_model->nomina($filtros);

    $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno($dia, $filtros['anio']);

    // Determina cual es la semana que dejara seleccionada en la vista.
    $semanaActual = $this->nomina_fiscal_model->semanaActualDelMes();
    $params['numSemanaSelected'] = isset($_GET['semana']) ? $_GET['semana'] : $semanaActual['semana'];

    // Obtiene los rangos de fecha de la semana seleccionada para obtener
    // las fechas de los 7 dias siguientes.
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($params['numSemanaSelected'], $filtros['anio']);
    $params['dias'] = String::obtenerSiguientesXDias($semana['fecha_inicio'], 7);
    $anio = (new DateTime($semana['fecha_inicio']))->format('Y');

    $params['nominas_finalizadas'] = true;
    foreach ($params['empleados'] as $key => $value)
    {
      if ($value->generada == 0)
      {
        $params['nominas_finalizadas'] = false;
        break;
      }
    }

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/nomina_fiscal/ranchos/nomina', $params);
    $this->load->view('panel/footer');
  }

  public function lista_asistencia()
  {
    $this->load->model('nomina_ranchos_model');
    $result = $this->nomina_ranchos_model->listadoAsistenciaPdf($_GET);
  }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */
  public function ajax_add_nomina_empleado()
  {
    $this->load->model('nomina_ranchos_model');
    $this->load->model('empresas_model');

    $empresaDefault = $this->empresas_model->getDefaultEmpresa();
    $empresaId = isset($_POST['id_empresa']) ? $_POST['id_empresa'] : $empresaDefault->id_empresa;

    $result = $this->nomina_ranchos_model->add_nominas($_POST, $empresaId, $_POST['id_empleado']);

    echo json_encode($result);
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