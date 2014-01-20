<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cuentas_cobrar extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'cuentas_cobrar/cuenta/',
    'cuentas_cobrar/detalle/',

    'cuentas_cobrar/cuenta_pdf/',
    'cuentas_cobrar/cuenta_xls/',

    'cuentas_cobrar/saldos_pdf/',
    'cuentas_cobrar/saldos_xls/',

    'cuentas_cobrar/imprimir_abono/'
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
      array('panel/facturacion/cuentas_cobrar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('cuentas_cobrar_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Cuentas por cobrar');

    $params['data'] = $this->cuentas_cobrar_model->getCuentasCobrarData();

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/cuentas_cobrar/cuentasXCobrar',$params);
    $this->load->view('panel/footer',$params);
  }
  public function saldos_pdf(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->cuentasCobrarPdf();
  }
  public function saldos_xls(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->cuentasCobrarExcel();
  }

  public function cuenta()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      array('panel/facturacion/cuentas_cobrar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('cuentas_cobrar_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Cuentas por cobrar');

    $params['data'] = $this->cuentas_cobrar_model->getCuentaClienteData();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/cuentas_cobrar/cuentaCliente',$params);
    $this->load->view('panel/footer',$params);
  }
  public function cuenta_pdf(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->cuentaClientePdf();
  }
  public function cuenta_xls(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->cuentaClienteExcel();
  }


  public function detalle()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('panel/facturacion/cuentas_cobrar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('cuentas_cobrar_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Cuentas por cobrar');

    $params['data'] = $this->cuentas_cobrar_model->getDetalleVentaFacturaData();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/cuentas_cobrar/detalle',$params);
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
      array('panel/facturacion/cuentas_cobrar.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('cuentas_cobrar_model');
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
          $respons = $this->cuentas_cobrar_model->addAbonoMasivo();
        else
          $respons = $this->cuentas_cobrar_model->addAbono();

        $params['closeModal'] = true;
        $params['frm_errors'] = $this->showMsgs(4);
      }

      if(isset($_GET['total']{0})) //si es masivo
      {
        $params['data'] = array('saldo' => $_GET['total'], 'facturas' => array() );
        $ids   = explode(',', substr($ids_aux, 1));
        $tipos = explode(',', substr($tipos_aux, 1));

        foreach ($ids as $key => $value) 
        {
          $params['data']['facturas'][] = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($value, $tipos[$key]);
        }
        $_GET['id'] = $ids_aux;
        $_GET['tipo'] = $tipos_aux;
      }else  
        $params['data'] = $this->cuentas_cobrar_model->getDetalleVentaFacturaData();

      //Cuentas de banco
      $params['cuentas'] = $this->banco_cuentas_model->getCuentas(false);
      //metodos de pago
      $params['metods_pago']  = array( 
        array('nombre' => 'Transferencia', 'value' => 'transferencia'),
        array('nombre' => 'Cheque', 'value' => 'cheque'),
        array('nombre' => 'Efectivo', 'value' => 'efectivo'),
        array('nombre' => 'Deposito', 'value' => 'deposito'),
      );

      $params['template'] = $this->load->view('panel/cuentas_cobrar/tpl_agregar_abono', $params, true);;
    }else
      $_GET['msg'] = 1;

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/cuentas_cobrar/agregar_abonos',$params);
  }

  public function eliminar_abono(){
    if (isset($_GET['ida']{0}) && isset($_GET['tipo']{0})) 
    {
      $this->load->model('cuentas_cobrar_model');
      $respons = $this->cuentas_cobrar_model->removeAbono();
      redirect(base_url('panel/cuentas_cobrar/detalle?'.String::getVarsLink(array('msg', 'ida')).'&msg=5'));
    }else
      redirect(base_url('panel/cuentas_cobrar/detalle?'.String::getVarsLink(array('msg', 'ida')).'&msg=1'));
  }

  public function imprimir_abono()
  {
    $this->load->model('cuentas_cobrar_model');

    if (isset($_GET['p']))
    {
      $this->cuentas_cobrar_model->imprimir_abono($_GET['p']);
    }
    else
    {
      $this->load->view('panel/cuentas_cobrar/print_orden_compra');
    }
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
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

?>