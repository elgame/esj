<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cuentas_pagar extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'cuentas_pagar/cuenta/',
    'cuentas_pagar/detalle/',

    'cuentas_pagar/cuenta_pdf/',
    'cuentas_pagar/cuenta_xls/',
    'cuentas_pagar/cuenta2_pdf/',
    'cuentas_pagar/cuenta2_xls/',

    'cuentas_pagar/saldos_pdf/',
    'cuentas_pagar/saldos_xls/',

    'cuentas_pagar/estado_cuenta_pdf/',
    'cuentas_pagar/estado_cuenta_xls/',
    'cuentas_pagar/rpt_compras_xls/',

    'cuentas_pagar/reporte_pdf/',
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
      array('panel/almacen/cuentas_pagar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('cuentas_pagar_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Cuentas por pagar');

    $params['data'] = $this->cuentas_pagar_model->getCuentasPagarData();

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/cuentas_pagar/cuentasXPagar',$params);
    $this->load->view('panel/footer',$params);
  }
  public function saldos_pdf(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->cuentasPagarPdf();
  }
  public function saldos_xls(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->cuentasPagarExcel();
  }

  public function estado_cuenta_pdf(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->estadoCuentaPdf();
  }
  public function estado_cuenta_xls(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->estadoCuentaXls();
  }

  public function cuenta()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      array('panel/almacen/cuentas_pagar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('cuentas_pagar_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Cuentas por pagar');

    $params['data'] = $this->cuentas_pagar_model->getCuentaProveedorData();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/cuentas_pagar/cuentaProveedor',$params);
    $this->load->view('panel/footer',$params);
  }
  public function cuenta_pdf(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->cuentaProveedorPdf();
  }
  public function cuenta_xls(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->cuentaProveedorExcel();
  }

  public function cuenta2()
  {
    $this->carabiner->css(array(
      array('panel/cuentas_pagar_cobrar.css'),
    ));
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      array('panel/almacen/cuentas_pagar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('cuentas_pagar_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Cuentas por pagar');

    if($this->input->get('did_empresa') == false){
      $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = $params['empresa']->id_empresa;
      $_GET['dempresa'] = $params['empresa']->nombre_fiscal;
    }

    $params['data'] = $this->cuentas_pagar_model->getCuentaProveedorData();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/cuentas_pagar/cuentaProveedor2',$params);
    $this->load->view('panel/footer',$params);
  }
  public function cuenta2_pdf(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->cuenta2ProveedorPdf();
  }
  public function cuenta2_xls(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->cuenta2ProveedorExcel();
  }


  public function detalle()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('panel/almacen/cuentas_pagar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('cuentas_pagar_model');

    $params['info_empleado'] = $this->info_empleado['info'];
    $params['seo']           = array('titulo' => 'Cuentas por pagar');

    $params['data'] = $this->cuentas_pagar_model->getDetalleVentaFacturaData();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/cuentas_pagar/detalle',$params);
    $this->load->view('panel/footer',$params);
  }

  /**
   * ABONOS
   * *****************************
   * @return [type] [description]
   */
  public function agregar_abono(){
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('general/keyjump.js'),
      array('general/util.js'),
      array('panel/almacen/cuentas_pagar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('cuentas_pagar_model');
    $this->load->model('banco_cuentas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Agregar abonos');

    $params['template']   = '';
    $params['closeModal'] = false;

    if (isset($_GET['id']{0}) && isset($_GET['tipo']{0}))
    {
      $ids_aux = $_GET['id'];
      $tipos_aux = $_GET['tipo'];

      $this->configAddAbono();
      if($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        if(isset($_GET['total']{0})) //si es masivo
          $respons = $this->cuentas_pagar_model->addAbonoMasivo();
        else
          $respons = $this->cuentas_pagar_model->addAbono();

        $params['closeModal'] = true;
        if ($respons['error']==false)
        {
          $params['frm_errors'] = $this->showMsgs(4);
          $params['id_movimiento'] = ($respons['ver_cheque'] ? $respons['id_movimiento'] : '');
        }else
          $params['frm_errors'] = $this->showMsgs($respons['msg']);
      }

      if(isset($_GET['total']{0})) //si es masivo
      {
        $params['data'] = array('saldo' => $_GET['total'], 'facturas' => array() );
        $ids   = explode(',', substr($ids_aux, 1));
        $tipos = explode(',', substr($tipos_aux, 1));
        foreach ($ids as $key => $value)
        {
          $params['data']['facturas'][] = $this->cuentas_pagar_model
            ->getDetalleVentaFacturaData($value, $tipos[$key], (isset($_GET['tcambio'])?$_GET['tcambio']:0) );
        }
        $proveedor = $params['data']['facturas'][0]['proveedor'];
        $_GET['id'] = $ids_aux;
        $_GET['tipo'] = $tipos_aux;
      }else
      {
        $params['data'] = $this->cuentas_pagar_model->getDetalleVentaFacturaData();
        $proveedor = $params['data']['proveedor'];
      }
      $id_empresa = isset($params['data']['empresa']->id_empresa)? $params['data']['empresa']->id_empresa : $params['data']['facturas'][0]['empresa']->id_empresa;

      //Cuentas de banco
      $params['cuentas'] = $this->banco_cuentas_model->getCuentas(false, null, array('id_empresa' => $id_empresa));
      //metodos de pago
      $params['metods_pago']  = array(
        array('nombre' => 'Transferencia', 'value' => 'transferencia'),
        array('nombre' => 'Cheque', 'value' => 'cheque'),
        array('nombre' => 'Efectivo', 'value' => 'efectivo'),
        array('nombre' => 'Deposito', 'value' => 'deposito'),
      );
      //Cuentas del proeveedor
      $params['cuentas_proveedor'] = $this->proveedores_model->getCuentas($proveedor->id_proveedor);

      $params['template'] = $this->load->view('panel/cuentas_pagar/tpl_agregar_abono', $params, true);;
    }else
      $_GET['msg'] = 1;

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/cuentas_pagar/agregar_abonos',$params);
  }

  public function eliminar_abono(){
    if (isset($_GET['ida']{0}) && isset($_GET['tipo']{0}))
    {
      $this->load->model('cuentas_pagar_model');
      $respons = $this->cuentas_pagar_model->removeAbono();
      redirect(base_url('panel/cuentas_pagar/detalle?'.MyString::getVarsLink(array('msg', 'ida')).'&msg=5'));
    }else
      redirect(base_url('panel/cuentas_pagar/detalle?'.MyString::getVarsLink(array('msg', 'ida')).'&msg=1'));
  }

  /**
   * Listado de pagos echos desde cuentas por pagar
   * @return [type] [description]
   */
  public function lista_pagos()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('panel/facturacion/cuentas_cobrar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('cuentas_pagar_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Cuentas por pagar');

    $params['data'] = $this->cuentas_pagar_model->getAbonosData();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/cuentas_pagar/lista_pagos',$params);
    $this->load->view('panel/footer',$params);
  }
  /**
   * Elimina un abono a una compra, lo quita tambien de bancos
   * @return [type] [description]
   */
  public function eliminar_movimiento()
  {
    if (isset($_GET['id_movimiento']{0}) )
    {
      $this->load->model('banco_cuentas_model');
      $response = $this->banco_cuentas_model->deleteMovimiento($_GET['id_movimiento']);
      redirect(base_url('panel/cuentas_pagar/lista_pagos?'.MyString::getVarsLink(array('msg', 'id_movimiento')).'&msg=5'));
    }else
      redirect(base_url('panel/cuentas_pagar/lista_pagos?'.MyString::getVarsLink(array('msg', 'id_movimiento')).'&msg=1'));
  }
  public function imprimir_recibo()
  {
    $this->load->model('cuentas_pagar_model');

    if (isset($_GET['id_movimiento']))
    {
      $this->cuentas_pagar_model->imprimir_recibo($_GET['id_movimiento']);
    }
  }

  /**
   * RPTS
   */
  public function rpt_compras_xls(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->rptComprasXls();
  }


  public function reporte()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte Facturas Vencidas');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/cuentas_pagar/rpt_facturas_vencidas',$params);
    $this->load->view('panel/footer',$params);
  }
  public function reporte_pdf(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->rptFacturasVencidasPdf();
  }



  /**
   * Configura los metodos de agregar y modificar
   */
  private function configAddAbono()
  {
    $this->load->library('form_validation');
    $rules = array(

        array('field'   => 'dfecha',
              'label'   => 'Fecha',
              'rules'   => 'required'),
        array('field'   => 'dconcepto',
              'label'   => 'Concepto',
              'rules'   => 'required|max_length[100]'),
        array('field'   => 'dmonto',
              'label'   => 'Monto',
              'rules'   => 'required|numeric'),
        array('field'   => 'dcuenta',
              'label'   => 'Cuenta Bancaria',
              'rules'   => 'required|numeric'),
        array('field'   => 'dreferencia',
              'label'   => 'Referencia',
              'rules'   => 'required|max_length[10]'),
    );
    $this->form_validation->set_rules($rules);
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
        $txt = 'El abono se modifico correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El abono se agrego correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'El abono se elimino correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = $msg;
        $icono = 'success';
        break;
      case 9:
        $txt = 'El abono se pagó correctamente.';
        $icono = 'success';
        break;

      case 30:
        $txt = 'No hay saldo suficiente para procesar la operación.';
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