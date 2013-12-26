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

    'cuentas_pagar/saldos_pdf/',
    'cuentas_pagar/saldos_xls/',
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
        $ids   = explode(',', substr($_GET['id'], 1));
        $tipos = explode(',', substr($_GET['tipo'], 1));
        foreach ($ids as $key => $value) 
        {
          $params['data']['facturas'][] = $this->cuentas_pagar_model->getDetalleVentaFacturaData($value, $tipos[$key]);
        }
        $proveedor = $params['data']['facturas'][0]['proveedor'];
      }else
      {
        $params['data'] = $this->cuentas_pagar_model->getDetalleVentaFacturaData();
        $proveedor = $params['data']['proveedor'];
      }

      //Cuentas de banco
      $params['cuentas'] = $this->banco_cuentas_model->getCuentas(false);
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
      redirect(base_url('panel/cuentas_pagar/detalle?'.String::getVarsLink(array('msg', 'ida')).'&msg=5'));
    }else
      redirect(base_url('panel/cuentas_pagar/detalle?'.String::getVarsLink(array('msg', 'ida')).'&msg=1'));
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