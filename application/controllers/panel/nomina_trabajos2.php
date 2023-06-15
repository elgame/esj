<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_trabajos2 extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'nomina_trabajos2/ajax_save/',
    'nomina_trabajos2/ajax_get/',
    'nomina_trabajos2/ajax_del/',
    'nomina_trabajos2/nomina_fiscal_ticket/',

    'nomina_trabajos2/rpt_costo_labores_pdf/',
    'nomina_trabajos2/rpt_costo_labores_xls/',
    'nomina_trabajos2/rpt_costo_labores_desg_pdf/',
    'nomina_trabajos2/rpt_costo_labores_desg_xls/',

    'nomina_trabajos2/rpt_prenomina_pdf/',

    'nomina_trabajos2/rpt_auditoria_costos/',
    'nomina_trabajos2/rpt_auditoria_costos_pdf/',
    'nomina_trabajos2/rpt_auditoria_costos_xls/',
    'nomina_trabajos2/rpt_costo_rancho_pdf/',
    'nomina_trabajos2/rpt_costo_rancho_xls/',

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
      array('panel/tags.css', 'screen'),
      array('panel/general_sanjorge.css')
    ));
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/keyjump.js'),
      array('libs/jquery.numeric.js'),
      array('panel/nomina_fiscal/nomina_trabajos2.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Nomina Fiscal - Actividades');

    // $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');
    $this->load->model('nomina_trabajos2_model');
    // $this->load->model('usuarios_departamentos_model');

    $params['empresaDefault'] = $this->empresas_model->getDefaultEmpresa();
    $params['fecha'] = isset($_GET['ffecha']) ? $_GET['ffecha'] : date('Y-m-d');

    $filtros = array(
      'semana'    => '',
      'anio'      => date("Y"),
      'empresaId' => isset($_GET['empresaId']) ? $_GET['empresaId'] : $params['empresaDefault']->id_empresa,
      'buscar'    => isset($_GET['buscar']) ? $_GET['buscar'] : '',
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

    $semana = MyString::obtenerSemanaDeFecha($params['fecha'], $filtros['dia_inicia_semana']);
    if ($semana) {
      $params['filtros'] = array_merge($filtros, $semana);
    } else {
      redirect(base_url('panel/nomina_trabajos2/?msg=10'));
    }

    $params['tareas_dia'] = $this->nomina_trabajos2_model->getActividades($params['fecha'], $filtros['empresaId'], $params['filtros']);

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/nomina_fiscal/nomina_trabajos2', $params);
    $this->load->view('panel/footer');
  }

  /*
   |------------------------------------------------------------------------
   | METODOS AJAX
   |------------------------------------------------------------------------
   */

  public function ajax_save()
  {
    $this->load->model('nomina_trabajos2_model');
    echo json_encode($this->nomina_trabajos2_model->save($_POST));
  }

  public function ajax_get()
  {
    $this->load->model('nomina_trabajos2_model');
    echo json_encode($this->nomina_trabajos2_model->getActividad($_POST));
  }

  public function ajax_del()
  {
    $this->load->model('nomina_trabajos2_model');
    echo json_encode($this->nomina_trabajos2_model->delete($_POST));
  }


  public function nomina_fiscal_ticket()
  {
    if (isset($_GET['semana']) && isset($_GET['empresaId']) && isset($_GET['anio']) && isset($_GET['fregistro_patronal'])) {
      $this->load->model('nomina_trabajos2_model');
      $this->nomina_trabajos2_model->ticketNominaFiscal($_GET['semana'], $_GET['empresaId'], $_GET['fregistro_patronal'], $_GET['anio']);
    }
  }

  public function rpt_costo_labores()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/rpt_costo_labores.js'),
    ));
    $this->carabiner->css(array(
      array('panel/tags.css', 'screen'),
    ));

    $this->load->library('pagination');
    $this->load->model('productos_model');
    $this->load->model('almacenes_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte Costos por Labor');

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
    $params['data'] = $this->productos_model->getFamilias(false, 'p');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_costo_labores',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_costo_labores_pdf(){
    $this->load->model('nomina_trabajos2_model');
    $this->nomina_trabajos2_model->rptCostoLaboresPdf();
  }
  public function rpt_costo_labores_xls(){
    $this->load->model('nomina_trabajos2_model');
    $this->nomina_trabajos2_model->rptCostoLaboresXls();
  }
  public function rpt_costo_labores_desg_pdf(){
    $this->load->model('nomina_trabajos2_model');
    $this->nomina_trabajos2_model->rptCostoLaboresDesglosadoPdf();
  }
  public function rpt_costo_labores_desg_xls(){
    $this->load->model('nomina_trabajos2_model');
    $this->nomina_trabajos2_model->rptCostoLaboresDesglosadoXls();
  }

  public function rpt_costo_rancho()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/rpt_costo_labores.js'),
    ));
    $this->carabiner->css(array(
      array('panel/tags.css', 'screen'),
    ));

    $this->load->library('pagination');
    $this->load->model('productos_model');
    $this->load->model('almacenes_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte Costos por Rancho');

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
    $params['data'] = $this->productos_model->getFamilias(false, 'p');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_costo_ranchos',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_costo_rancho_pdf() {
    $this->load->model('nomina_trabajos2_model');
    $this->nomina_trabajos2_model->rptCostoRanchosPdf();
  }
  public function rpt_costo_rancho_xls() {
    $this->load->model('nomina_trabajos2_model');
    $this->nomina_trabajos2_model->rptCostoRanchosXls();
  }

  public function rpt_auditoria_costos()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/rpt_costo_labores.js'),
    ));
    $this->carabiner->css(array(
      array('panel/tags.css', 'screen'),
    ));

    $this->load->library('pagination');
    $this->load->model('productos_model');
    $this->load->model('almacenes_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte Auditoria de Costos');

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
    $params['data'] = $this->productos_model->getFamilias(false, 'p');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_auditoria_costos',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_auditoria_costos_xls(){
    $this->load->model('nomina_trabajos2_model');
    $this->nomina_trabajos2_model->rptAuditoriaCostosXls();
  }

  public function rpt_prenomina()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/rpt_prenomina.js'),
    ));
    $this->carabiner->css(array(
      array('panel/tags.css', 'screen'),
    ));

    $this->load->library('pagination');
    $this->load->model('nomina_fiscal_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte Pre Nomina');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    $empresaId = isset($_GET['empresaId']) ? $_GET['empresaId'] : $params['empresa']->id_empresa;
    $anio = isset($_GET['anio']) ? $_GET['anio'] : date("Y");

    if ($empresaId !== '') {
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    } else {
      $dia = '4';
    }
    $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno($dia, $anio);
    $params['tipoNomina'] = ($dia == 15? 'quincena': 'semana');
    $semanaActual = $this->nomina_fiscal_model->semanaActualDelMes(null, 0, $dia);
    $params['numSemanaSelected'] = isset($_GET['semana']) ? $_GET['semana'] : $semanaActual[$params['tipoNomina']];

    $params['empresa'] = $this->empresas_model->getInfoEmpresa($empresaId, true)['info'];
    $params['registros_patronales'] = explode('|', (isset($params['empresa']->registro_patronal)? $params['empresa']->registro_patronal: ''));

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/nomina_fiscal/rpt_prenomina',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_prenomina_pdf(){
    $this->load->model('nomina_trabajos2_model');
    $this->nomina_trabajos2_model->rptPreNominaPdf();
  }
  public function rpt_prenomina_xls(){
    $this->load->model('nomina_trabajos2_model');
    $this->nomina_trabajos2_model->rptPreNominaXls();
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
      case 10:
        $txt = 'La fecha se sale del periodo actual.';
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