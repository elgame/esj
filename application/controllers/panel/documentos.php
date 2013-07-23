<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class documentos extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'facturacion/get_folio/',
    'facturacion/get_series/',

    'facturacion/rvc_pdf/',
    'facturacion/rvp_pdf/',

    'facturacion/ajax_get_clasificaciones/',
    'facturacion/ajax_get_empresas_fac/'
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
   * Visualiza la vista para realizar la documentacion.
   *
   * @return void
   */
  public function agregar()
  {
    if (isset($_GET['id']{0}) && $_GET['id'] !== '')
    {
      $this->carabiner->js(array(
          array('libs/jquery.numeric.js'),
          array('general/keyjump.js'),
          array('general/util.js'),
          array('panel/documentos/agregar.js'),
      ));

      $params['info_empleado']  = $this->info_empleado['info']; //info empleado
      $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
      $params['seo']            = array('titulo' => 'Agregar factura');
      $params['pagar_ordent']   = false;

      $this->load->model('facturacion_model');

      // Obtiene la informacion de la factura.
      $params['factura'] = $this->facturacion_model->getInfoFactura($_GET['id']);

      // Carga la vista de la factura con sus datos.
      $params['facturaView'] = $this->load->view('panel/facturacion/ver', $params, true);

      // Obtiene la vista de los documentos del cliente.
      $params['documentos'] = $this->generaDocsView($params['factura']['info']->id_factura);

      // echo "<pre>";
      //   var_dump($params['factura']);
      // echo "</pre>";exit;

      if(isset($_GET['msg']{0}))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/documentos/agregar', $params);
      $this->load->view('panel/footer');
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
  }

  /**
   * Genera la vista del listado de los documentos.
   *
   * @param  boolean $idFactura
   * @return string
   */
  public function generaDocsView($idFactura=false)
  {
    $this->load->model('documentos_model');

    $idFactura = $idFactura ? $idFactura : $_GET['id'];

    // Obtiene la informacion de la factura.
    $params['factura'] = $this->facturacion_model->getInfoFactura($idFactura);

    // Obtiene los documentos del cliente.
    $params['documentos'] = $this->documentos_model->getClienteDocs($idFactura);

    // Construye la vista del listado de documentos.
    return $this->load->view('panel/documentos/agregar_listado', $params, true);
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
        $txt = 'La Factura se agrego correctamente.';
        $icono = 'success';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}