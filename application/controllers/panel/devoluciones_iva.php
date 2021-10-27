<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class devoluciones_iva extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'devoluciones_iva/cedula_proveedores_pdf/',
    'devoluciones_iva/cedula_proveedores_xls/',
    'devoluciones_iva/cedula_totalidad_iva_pdf/',
    'devoluciones_iva/cedula_totalidad_iva_xls/',
    'devoluciones_iva/cedula_iva16_pdf/',
    'devoluciones_iva/cedula_iva16_xls/',
    'devoluciones_iva/rpt_ieps_pdf/',
    'devoluciones_iva/rpt_ieps_xls/',
    'devoluciones_iva/rpt_ieps_ventas_pdf/',
    'devoluciones_iva/rpt_ieps_ventas_xls/',

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
  }

  public function cedula_proveedores()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'CÉDULA A DETALLE DE PROVEEDORES SOLICITADOS');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/devoluciones_iva/cedula_proveedores',$params);
    $this->load->view('panel/footer',$params);
  }
  public function cedula_proveedores_pdf(){
    $this->load->model('devoluciones_iva_model');
    $this->devoluciones_iva_model->getCedulaProveedoresXls(true);
  }
  public function cedula_proveedores_xls(){
    $this->load->model('devoluciones_iva_model');
    $this->devoluciones_iva_model->getCedulaProveedoresXls();
  }

  public function cedula_totalidad_iva()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info'];
    $params['seo']           = array('titulo' => 'CEDULA INTEGRACION TOTALIDAD DE IVA');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/devoluciones_iva/cedula_totalidad_iva',$params);
    $this->load->view('panel/footer',$params);
  }
  public function cedula_totalidad_iva_pdf(){
    $this->load->model('devoluciones_iva_model');
    $this->devoluciones_iva_model->getCedulaTotalidadIvaXls(true);
  }
  public function cedula_totalidad_iva_xls(){
    $this->load->model('devoluciones_iva_model');
    $this->devoluciones_iva_model->getCedulaTotalidadIvaXls();
  }

  public function cedula_iva16()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/rpt_dev_iva/rpt_dev_iva.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info'];
    $params['seo']           = array('titulo' => 'CEDULA INTEGRACION IVA 16%');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/devoluciones_iva/cedula_iva16',$params);
    $this->load->view('panel/footer',$params);
  }
  public function cedula_iva16_pdf(){
    $this->load->model('devoluciones_iva_model');
    $this->devoluciones_iva_model->getCedulaIva16Xls(true);
  }
  public function cedula_iva16_xls(){
    $this->load->model('devoluciones_iva_model');
    $this->devoluciones_iva_model->getCedulaIva16Xls();
  }


  public function rpt_ieps()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de IEPS');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/devoluciones_iva/reporte_ieps',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_ieps_pdf(){
    $this->load->model('devoluciones_iva_model');
    $this->devoluciones_iva_model->getRptIepsXls(true);
  }
  public function rpt_ieps_xls(){
    $this->load->model('devoluciones_iva_model');
    $this->devoluciones_iva_model->getRptIepsXls();
  }

  public function rpt_ieps_ventas()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/facturacion/rpt_ieps_ventas.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de IEPS Ventas');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/devoluciones_iva/reporte_ieps_ventas',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_ieps_ventas_pdf(){
    $this->load->model('devoluciones_iva_model');
    $this->devoluciones_iva_model->getRptIepsVentasXls(true);
  }
  public function rpt_ieps_ventas_xls(){
    $this->load->model('devoluciones_iva_model');
    $this->devoluciones_iva_model->getRptIepsVentasXls();
  }


}

?>