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
    'cuentas_cobrar/cuenta2_pdf/',
    'cuentas_cobrar/cuenta2_xls/',

    'cuentas_cobrar/saldos_pdf/',
    'cuentas_cobrar/saldos_xls/',
    'cuentas_cobrar/saldos_all_pdf/',
    'cuentas_cobrar/saldos_all_xls/',

    'cuentas_cobrar/imprimir_abono/',

    'cuentas_cobrar/estado_cuenta_pdf/',
    'cuentas_cobrar/estado_cuenta_xls/',
    'cuentas_cobrar/rpt_ventas_xls/',
    'cuentas_cobrar/rpt_ventas2_xls/',

    'cuentas_cobrar/factura_abono_parci/',

    'cuentas_cobrar/com_pago/',
    'cuentas_cobrar/cancelar_com_pago/',
    'cuentas_cobrar/imprimir_com_pago/',
    'cuentas_cobrar/xml_com_pago/',
    'cuentas_cobrar/ajax_get_com_pagos/',

    'cuentas_cobrar/reporte_pdf/',
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

  public function saldos_all_pdf(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->cuentasCobrarAllPdf();
  }
  public function saldos_all_xls(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->cuentasCobrarAllExcel();
  }

  public function estado_cuenta_pdf(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->estadoCuentaPdf();
  }
  public function estado_cuenta_xls(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->estadoCuentaXls();
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

  public function cuenta2()
  {
    $this->carabiner->css(array(
      array('panel/cuentas_pagar_cobrar.css'),
    ));
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

    if($this->input->get('did_empresa') == false){
      $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = $params['empresa']->id_empresa;
      $_GET['dempresa'] = $params['empresa']->nombre_fiscal;
    }

    $params['data'] = $this->cuentas_cobrar_model->getCuentaClienteData();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/cuentas_cobrar/cuentaCliente2',$params);
    $this->load->view('panel/footer',$params);
  }
  public function cuenta2_pdf(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->cuenta2ClientePdf();
  }
  public function cuenta2_xls(){
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

        if (isset($respons[0]) && $respons[0] == false) {
          $params['frm_errors'] = $this->showMsgs(2, $respons['msg']);
        } else {
          if($this->input->post('imprimir') == 'si')
            $params['print_recibo'] = $respons['id_movimiento'];

          $params['closeModal'] = true;
          $params['frm_errors'] = $this->showMsgs(4);
        }
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
      redirect(base_url('panel/cuentas_cobrar/detalle?'.MyString::getVarsLink(array('msg', 'ida')).'&msg=5'));
    }else
      redirect(base_url('panel/cuentas_cobrar/detalle?'.MyString::getVarsLink(array('msg', 'ida')).'&msg=1'));
  }

  public function imprimir_abono()
  {
    $this->load->model('cuentas_cobrar_model');

    if (isset($_GET['p']))
    {
      $this->cuentas_cobrar_model->imprimir_abono($_GET['p']);
    }
  }

  public function lista_pagos()
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

    $params['data'] = $this->cuentas_cobrar_model->getAbonosData();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/cuentas_cobrar/lista_pagos',$params);
    $this->load->view('panel/footer',$params);
  }

  public function eliminar_movimiento()
  {
    if (isset($_GET['id_movimiento']{0}) )
    {
      $this->load->model('banco_cuentas_model');
      $this->load->model('cuentas_cobrar_pago_model');

      $pago = $this->cuentas_cobrar_pago_model->getInfoComPago(false, $_GET['id_movimiento']);

      if (isset($pago->id))
        redirect(base_url('panel/cuentas_cobrar/lista_pagos?'.MyString::getVarsLink(array('msg', 'id_movimiento')).'&msg=101'));
      else {
        $response = $this->banco_cuentas_model->deleteMovimiento($_GET['id_movimiento']);
        redirect(base_url('panel/cuentas_cobrar/lista_pagos?'.MyString::getVarsLink(array('msg', 'id_movimiento')).'&msg=10'));
      }
    }else
      redirect(base_url('panel/cuentas_cobrar/lista_pagos?'.MyString::getVarsLink(array('msg', 'id_movimiento')).'&msg=1'));
  }

  public function factura_abono_parci()
  {
    if (isset($_GET['ida']{0}) && isset($_GET['tipo']{0}))
    {
      $this->load->model('cuentas_cobrar_model');
      $respons = $this->cuentas_cobrar_model->creaFacturaAbono($_GET['ida']);
      if($respons['passes'])
        redirect(base_url('panel/cuentas_cobrar/detalle?'.MyString::getVarsLink(array('msg', 'ida')).'&msg=11'));
      else
        redirect(base_url('panel/cuentas_cobrar/detalle?'.MyString::getVarsLink(array('msg', 'ida')).'&msg='.$respons['codigo']));

    }else
      redirect(base_url('panel/cuentas_cobrar/detalle?'.MyString::getVarsLink(array('msg', 'ida')).'&msg=1'));
  }

  /**
   * Genera los complementos de pago
   * @return [type] [description]
   */
  public function com_pago()
  {
    if (isset($_GET['idm']{0}))
    {
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

      $movs = $this->db->query("SELECT bm.id_cliente, bm.metodo_pago, bc.id_empresa
        FROM banco_movimientos bm
          INNER JOIN banco_cuentas bc ON bc.id_cuenta = bm.id_cuenta
        WHERE bm.id_movimiento = {$_GET['idm']}")->row();

        // echo "<pre>";
        //   var_dump('dd', $_POST);
        // echo "</pre>";exit;
      if (isset($_POST['save']) && (isset($_POST['dcuenta']{0}) || $movs->metodo_pago == 'efectivo'))
      {
        $this->load->model('cuentas_cobrar_pago_model');
        $respons = $this->cuentas_cobrar_pago_model->addComPago($_GET['idm'], (isset($_POST['dcuenta']{0})? $_POST['dcuenta']: 0), $_POST['cfdiRel'], $_POST);
        if($respons['passes']) {
          $params['frm_errors'] = $this->showMsgs('8', $respons['msg']);
          $params['closeModal'] = true;
        }
        else {
          if (isset($respons['msg']))
            $params['frm_errors'] = $this->showMsgs('2', $respons['msg']);
          else
            $params['frm_errors'] = $this->showMsgs($respons['codigo']);
        }
      } elseif (isset($_POST['save'])) {
        $params['frm_errors'] = $this->showMsgs('2', 'La cuenta del cliente es requerida.');
      }

      $this->load->model('clientes_model');
      $params['cuentas'] = $this->clientes_model->getCuentas($movs->id_cliente);
      $params['metodo_pago'] = $movs->metodo_pago;
      $params['movs'] = $movs;

      $tipoRelacionC = new TipoRelacion;
      $params['tiposRelacion'] = $tipoRelacionC->get();

      $params['noHeader'] = false;
      $this->load->view('panel/header', $params);
      $this->load->view('panel/cuentas_cobrar/com_pagos', $params);
      $this->load->view('panel/footer', $params);
    }else
      redirect(base_url('panel/cuentas_cobrar/lista_pagos?'.MyString::getVarsLink(array('msg', 'idm')).'&msg=1'));
  }

  public function ajax_get_com_pagos(){
    $this->load->model('cuentas_cobrar_pago_model');
    $params = $this->cuentas_cobrar_pago_model->getComPagosAjax();

    echo json_encode($params);
  }

  public function cancelar_com_pago()
  {
    if (isset($_GET['id']{0}) && isset($_GET['motivo']{0}) && isset($_GET['folioSustitucion']))
    {
      $this->load->model('banco_cuentas_model');
      $this->load->model('cuentas_cobrar_pago_model');

      $pago = $this->cuentas_cobrar_pago_model->cancelaFactura($_GET['id'], $_GET);

      // if (isset($pago->id))
      //   redirect(base_url('panel/facturacion/pago_parcialidad?'.MyString::getVarsLink(array('msg', 'id')).'&msg=101'));
      // else {
      //   // $response = $this->banco_cuentas_model->deleteMovimiento($_GET['id']);
        redirect(base_url('panel/facturacion/pago_parcialidad?'.MyString::getVarsLink(array('msg', 'id')).'&msg='.$pago['msg']));
      // }
    }else
      redirect(base_url('panel/facturacion/pago_parcialidad?'.MyString::getVarsLink(array('msg', 'id')).'&msg=1'));
  }

  public function imprimir_com_pago()
  {
    if(isset($_GET['id']{0}))
    {
      $this->load->model('cuentas_cobrar_pago_model');
      if($this->input->get('p') == 'true')
        $this->cuentas_cobrar_pago_model->generaFacturaPdf($_GET['id']);
      else {
        $params['url'] = 'panel/cuentas_cobrar/imprimir_com_pago/?id='.$_GET['id'].'&p=true';
        $this->load->view('panel/facturacion/print_view', $params);
      }
    }
    else
      redirect(base_url('panel/facturacion/?msg=1'));
  }

  public function xml_com_pago()
  {
    if(isset($_GET['id']{0}))
    {
      $this->load->library('cfdi');
      $this->load->model('cuentas_cobrar_pago_model');
      $this->cuentas_cobrar_pago_model->descargarZipCP($_GET['id']);
      // $factura = $this->cuentas_cobrar_pago_model->getInfoComPago($_GET['id']);

      // $folio = $this->cfdi->acomodarFolio($factura->folio);
      // $file = $factura->cfdi_ext->emisor->rfc.'-'.$factura->serie.$folio;
      // header('Content-type: text/xml');
      // header('Content-Disposition: attachment; filename="'.$file.'.xml"');
      // echo $factura->xml;
    }
    else
      redirect(base_url('panel/facturacion/?msg=1'));
  }


  /**
   * RPTS
   */
  public function rpt_ventas_xls(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->rptVentasXls();
  }
  public function rpt_ventas2_xls(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->rptVentasClienteXls();
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
    $this->load->view('panel/cuentas_cobrar/rpt_facturas_vencidas',$params);
    $this->load->view('panel/footer',$params);
  }
  public function reporte_pdf(){
    $this->load->model('cuentas_cobrar_model');
    $this->cuentas_cobrar_model->rptFacturasVencidasPdf();
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
        array('field'   => 'fmetodo_pago',
              'label'   => 'Metodo de pago',
              'rules'   => 'required'),
        array('field'   => 'dtipomov',
              'label'   => 'Tipo Movimiento',
              'rules'   => 'required'),

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
      case 10:
        $txt = 'Los abonos se eliminaron correctamente.';
        $icono = 'success';
        break;
      case 101:
        $txt = 'Los abonos no se pueden eliminar porque tiene un complemento de pago (se tiene que cancelar).';
        $icono = 'error';
        break;
      case 102:
        $txt = 'El comprobante de pago no se pudo subir.';
        $icono = 'error';
        break;

      case 12:
        $txt = 'Ya se registro el complemento de pago.';
        $icono = 'success';
        break;
      case 13:
        $txt = 'Los CFDI no requieren complemento de pago, o son remisiones los comprobantes.';
        $icono = 'success';
        break;
      case 14:
        $txt = "La empresa no tiene una serie 'P', agregala a la empresa.";
        $icono = 'error';
        break;

      case 11:
        $txt = 'La Factura se timbro correctamente.';
        $icono = 'success';
        break;
      case 500:
        $txt = 'Error en el servidor del timbrado. Pongase en contacto con el equipo de desarrollo del sistema.';
        $icono = 'error';
        break;
      case 'ERR_INTERNET_DISCONNECTED':
        $txt = 'Error Timbrado: Internet Desconectado. Verifique su conexión para realizar el timbrado.';
        $icono = 'error';
        break;
      default:
        $txt = 'Ocurrio un error al intentar timbrar la factura, verifique los datos fiscales de la empresa y/o cliente.';
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