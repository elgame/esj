<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class estado_resultado_trans extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'estado_resultado_trans/ajax_get_remisiones/',
    'estado_resultado_trans/ajax_get_repmant/',
    'estado_resultado_trans/ajax_get_proveedores/',
    'estado_resultado_trans/ajax_get_cods/',
    'estado_resultado_trans/ajax_get_gastos_caja/',

    'estado_resultado_trans/imprimir/',
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
      array('panel/estado_resultado_trans/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('estado_resultado_trans_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Estado de Resultados en EQUIPO DE TRANSPORTE');

    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $params['datos_s'] = $this->estado_resultado_trans_model->getEstadosRes();

    $params['fecha']  = date("Y-m-d");
    $params['method']  = '';

    $params['desbloquear'] = false;
    if ($this->usuarios_model->tienePrivilegioDe('', 'ventas/desbloquear/')) {
      $params['desbloquear'] = true;
    }

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/estado_resultado_trans/admin',$params);
    $this->load->view('panel/footer',$params);
  }

  /**
   * Agrega una venta de remision a la bd
   *
   * @return void
   */
  public function agregar()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('libs/jquery.filtertable.min.js'),
      array('general/keyjump.js'),
      array('general/util.js'),
      ['panel/estado_resultado_trans/addmod.js'],
      // array('panel/facturacion/gastos_productos.js'),
      // array('panel/ventas_remision/frm_addmod.js'),
      // array('panel/facturacion/frm_otros.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Agregar Estado de Resultados');
    $params['pagar_ordent']   = false;

    $this->load->library('cfdi');
    $this->load->model('facturacion_model');
    $this->load->model('estado_resultado_trans_model');
    $this->load->model('empresas_model');

    $this->configAddModEstadoRest();
    if($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      if (isset($_GET['id_nr']))
        $respons = $this->estado_resultado_trans_model->updateEstadoResult($_GET['id_nr']);
      else
        $respons = $this->estado_resultado_trans_model->addEstadoResult();

      if($respons['passes'])
      {
        if (isset($_GET['id_nr']))
          redirect(base_url('panel/estado_resultado_trans/agregar/?msg=3&id_nr='.$_GET['id_nr']));
        else
          redirect(base_url('panel/estado_resultado_trans/?msg=3'));
      }
      else
        $params['frm_errors'] = $this->showMsgs(2, $respons['msg']);
    }

    // Parametros por default.
    // $params['series'] = $this->estado_resultado_trans_model->getSeriesFolios(100);
    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    // Parametros por default.
    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();


    $params['getId'] = '';
    if (isset($_GET['id_nr']) || isset($_GET['id_nrc']))
    {
      $params['borrador'] = $this->estado_resultado_trans_model->getInfoVenta( (isset($_GET['id_nr'])? $_GET['id_nr']: $_GET['id_nrc']), false, true );
      // echo "<pre>";
      // var_dump($params['borrador']);
      // echo "</pre>";exit;
    }

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/estado_resultado_trans/agregar', $params);
    $this->load->view('panel/footer');
  }

  public function cancelar()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('estado_resultado_trans_model');
      $this->estado_resultado_trans_model->cancelar($_GET['id']);

      redirect(base_url('panel/estado_resultado_trans/?'.MyString::getVarsLink(array('msg','id')).'&msg=5'));
    }
  }

  public function ajax_get_remisiones($id_empresa = 24)
  {
    $id_empresa = isset($_GET['did_empresa'])? $_GET['did_empresa']: $id_empresa;
    $this->load->model('estado_resultado_trans_model');
    echo json_encode($this->estado_resultado_trans_model->getRemisiones($id_empresa));
  }

  public function ajax_get_repmant($id_empresa = 24)
  {
    $id_empresa = isset($_GET['did_empresa'])? $_GET['did_empresa']: $id_empresa;
    $this->load->model('estado_resultado_trans_model');
    echo json_encode($this->estado_resultado_trans_model->getRepMant($id_empresa));
  }

  public function ajax_get_proveedores($id_empresa = 24)
  {
    $id_empresa = isset($_GET['did_empresa'])? $_GET['did_empresa']: $id_empresa;
    $this->load->model('estado_resultado_trans_model');
    echo json_encode($this->estado_resultado_trans_model->ajaxProveedores($id_empresa));
  }

  public function ajax_get_cods()
  {
    $this->load->model('estado_resultado_trans_model');
    echo json_encode($this->estado_resultado_trans_model->ajaxCodsGastos());
  }

  public function ajax_get_gastos_caja($id_empresa = 24)
  {
    $id_empresa = isset($_GET['did_empresa'])? $_GET['did_empresa']: $id_empresa;
    $this->load->model('estado_resultado_trans_model');
    echo json_encode($this->estado_resultado_trans_model->getGastosCaja($id_empresa));
  }


  /**
   * Configura los metodos de agregar y modificar
   *
   * @return void
   */
  private function configAddModEstadoRest($borrador = false)
  {
    $this->load->library('form_validation');
    $rules = array(
        ['field' => 'dempresa'                , 'label' => 'dempresa'              , 'rules' => '']                 ,
        ['field' => 'did_empresa'             , 'label' => 'did_empresa'           , 'rules' => 'required|numeric'] ,
        ['field' => 'dactivo'                 , 'label' => 'dactivo'               , 'rules' => '']                 ,
        ['field' => 'did_activo'              , 'label' => 'did_activo'            , 'rules' => 'required|numeric'] ,
        ['field' => 'did_gasto'               , 'label' => 'did_gasto'             , 'rules' => 'numeric']          ,
        ['field' => 'dchofer'                 , 'label' => 'dchofer'               , 'rules' => '']                 ,
        ['field' => 'did_chofer'              , 'label' => 'did_chofer'            , 'rules' => 'required|numeric'] ,
        ['field' => 'dkm_rec'                 , 'label' => 'dkm_rec'               , 'rules' => 'numeric']          ,
        ['field' => 'dvel_max'                , 'label' => 'dvel_max'              , 'rules' => 'numeric']          ,
        ['field' => 'drep_lt_hist'            , 'label' => 'drep_lt_hist'          , 'rules' => 'numeric']          ,
        ['field' => 'dfecha'                  , 'label' => 'dfecha'                , 'rules' => '']                 ,
        ['field' => 'rend_km_gps'             , 'label' => 'rend_km_gps'           , 'rules' => 'numeric']          ,
        ['field' => 'rend_actual'             , 'label' => 'rend_actual'           , 'rules' => 'numeric']          ,
        ['field' => 'rend_lts'                , 'label' => 'rend_lts'              , 'rules' => 'numeric']          ,
        ['field' => 'rend_precio'             , 'label' => 'rend_precio'           , 'rules' => 'numeric']          ,

        ['field' => 'remision_fecha[]'        , 'label' => 'remision_fecha'        , 'rules' => '']                 ,
        ['field' => 'remision_numero[]'       , 'label' => 'remision_numero'       , 'rules' => '']                 ,
        ['field' => 'remision_cliente[]'      , 'label' => 'remision_cliente'      , 'rules' => '']                 ,
        ['field' => 'remision_id[]'           , 'label' => 'remision_id'           , 'rules' => 'numeric']          ,
        ['field' => 'remision_row[]'          , 'label' => 'remision_row'          , 'rules' => '']                 ,
        ['field' => 'remision_importe[]'      , 'label' => 'remision_importe'      , 'rules' => 'numeric']          ,
        ['field' => 'remision_comprobacion[]' , 'label' => 'remision_comprobacion' , 'rules' => '']                 ,
        ['field' => 'remision_del[]'          , 'label' => 'remision_del'          , 'rules' => '']                 ,

        ['field' => 'sueldos_fecha[]'         , 'label' => 'sueldos_fecha'         , 'rules' => '']                 ,
        ['field' => 'sueldos_id_sueldo[]'     , 'label' => 'sueldos_id_sueldo'     , 'rules' => '']          ,
        ['field' => 'sueldos_proveedor[]'     , 'label' => 'sueldos_proveedor'     , 'rules' => '']                 ,
        ['field' => 'sueldos_proveedor_id[]'  , 'label' => 'sueldos_proveedor_id'  , 'rules' => 'numeric']          ,
        ['field' => 'sueldos_concepto[]'      , 'label' => 'sueldos_concepto'      , 'rules' => '']                 ,
        ['field' => 'sueldos_importe[]'       , 'label' => 'sueldos_importe'       , 'rules' => 'numeric']          ,
        ['field' => 'sueldos_comprobacion[]'  , 'label' => 'sueldos_comprobacion'  , 'rules' => '']                 ,
        ['field' => 'sueldos_del[]'           , 'label' => 'sueldos_del'           , 'rules' => '']                 ,

        ['field' => 'repmant_fecha[]'         , 'label' => 'repmant_fecha'         , 'rules' => '']                 ,
        ['field' => 'repmant_numero[]'        , 'label' => 'repmant_numero'        , 'rules' => '']                 ,
        ['field' => 'repmant_proveedor[]'     , 'label' => 'repmant_proveedor'     , 'rules' => '']                 ,
        ['field' => 'repmant_id[]'            , 'label' => 'repmant_id'            , 'rules' => '']                 ,
        ['field' => 'repmant_row[]'           , 'label' => 'repmant_row'           , 'rules' => '']                 ,
        ['field' => 'repmant_concepto[]'      , 'label' => 'repmant_concepto'      , 'rules' => '']                 ,
        ['field' => 'repmant_importe[]'       , 'label' => 'repmant_importe'       , 'rules' => '']                 ,
        ['field' => 'repmant_comprobacion[]'  , 'label' => 'repmant_comprobacion'  , 'rules' => '']                 ,
        ['field' => 'repmant_del[]'           , 'label' => 'repmant_del'           , 'rules' => '']                 ,

        ['field' => 'gastos_fecha[]'          , 'label' => 'gastos_fecha'          , 'rules' => '']                 ,
        ['field' => 'gastos_id_gasto[]'       , 'label' => 'gastos_id_gasto'       , 'rules' => '']                 ,
        ['field' => 'gastos_proveedor[]'      , 'label' => 'gastos_proveedor'      , 'rules' => '']                 ,
        ['field' => 'gastos_proveedor_id[]'   , 'label' => 'gastos_proveedor_id'   , 'rules' => 'numeric']                 ,
        ['field' => 'gastos_codg[]'           , 'label' => 'gastos_codg'           , 'rules' => '']                 ,
        ['field' => 'gastos_codg_id[]'        , 'label' => 'gastos_codg_id'        , 'rules' => '']                 ,
        ['field' => 'gastos_importe[]'        , 'label' => 'gastos_importe'        , 'rules' => '']                 ,
        ['field' => 'gastos_comprobacion[]'   , 'label' => 'gastos_comprobacion'   , 'rules' => '']                 ,
        ['field' => 'gastos_del[]'            , 'label' => 'gastos_del'            , 'rules' => '']                 ,
    );

    $this->form_validation->set_rules($rules);
  }


  /**
   * Imprime la venta remision
   * @return [type] [description]
   */
  public function imprimir()
  {
    if(isset($_GET['id']{0}))
    {
      $this->load->model('estado_resultado_trans_model');
      $this->estado_resultado_trans_model->print($_GET['id']);
      // if($this->input->get('p') == 'true')
      // else {
      //   $params['url'] = 'panel/estado_resultado_trans/imprimir/?id='.$_GET['id'].'&p=true';
      //   $this->load->view('panel/facturacion/print_view', $params);
      // }
    }
    else
      redirect(base_url('panel/estado_resultado_trans/?msg=1'));
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
  private function showMsgs($tipo, $msg='', $title='Facturacion!'){
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
        $txt = 'El Estado de Resultados se guardo correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'La Nota de remisión se agrego correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La Nota de remisión se cancelo correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = $msg;
        $icono = 'success';
        break;
      case 9:
        $txt = 'La Nota de remisión se pagó correctamente.';
        $icono = 'success';
        break;
      case 10:
        $txt = 'La Nota de credito se agrego correctamente.';
        $icono = 'success';
        break;
      case 11:
        $txt = 'La Remision se agrego correctamente.';
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