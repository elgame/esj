<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class facturacion extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'facturacion/agregar/',
    'facturacion/get_folio/',
    'facturacion/get_series/',
    'facturacion/email/',

    'facturacion/rvc_pdf/',
    'facturacion/rvp_pdf/',
    'facturacion/prodfact_pdf/',
    'facturacion/prodfact_xls/',
    'facturacion/rventasc_pdf/',
    'facturacion/rventasc_xls/',
    'facturacion/rventasc_detalle_pdf/',
    'facturacion/remisiones_detalle_pdf/',
    'facturacion/remisiones_detalle_xls/',
    'facturacion/rnotas_cred_pdf/',
    'facturacion/rnotas_cred_xls/',
    'facturacion/prodfact2_pdf/',
    'facturacion/prodfact2_xls/',
    'facturacion/ventasAcumulado_pdf/',
    'facturacion/ventasAcumulado_xls/',

    'facturacion/ajax_get_clasificaciones/',
    'facturacion/ajax_get_empresas_fac/',
    'facturacion/ajax_get_clientes/',
    'facturacion/ajax_get_clientes_vr/',
    'facturacion/ajax_get_pallet_folio/',
    'facturacion/ajax_get_unidades/',
    'facturacion/ajax_get_pallets_cliente/',
    'facturacion/ajax_ligar_remisiones/',
    'facturacion/ajax_remove_remision_fact/',

    'facturacion/xml/',
    'facturacion/descarga_masiva/',
    'facturacion/nomina/',

    'facturacion/getRemisiones/'
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
      array('panel/facturacion/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('facturacion_model');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Facturas');

    $params['datos_s'] = $this->facturacion_model->getFacturas('40', " AND id_nc IS NULL AND id_abono_factura IS NULL");

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/admin',$params);
    $this->load->view('panel/footer',$params);
  }

  public function notas_credito()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('panel/facturacion/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('facturacion_model');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Facturas');

    //obtenemos las notas de credito
    $params['datos_s'] = $this->facturacion_model->getFacturas('40', " AND id_nc IS NOT NULL");

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/admin_nc',$params);
    $this->load->view('panel/footer',$params);
  }

  public function pago_parcialidad()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('panel/facturacion/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('facturacion_model');
    $this->load->model('cuentas_cobrar_pago_model');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Complementos de Pago (Pago en parcialidades o Diferido)');

    //obtenemos las notas de credito
    if (isset($_GET['ftipo']) && $_GET['ftipo'] == 'parcial') {
      $params['datos_s'] = $this->facturacion_model->getFacturas('40', " AND id_abono_factura IS NOT NULL");
    } else
      $params['datos_cp'] = $this->cuentas_cobrar_pago_model->getComPagoData();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/admin_pp',$params);
    $this->load->view('panel/footer',$params);
  }

  /**
   * Agrega una factura a la bd
   *
   * @return void
   */
  public function agregar()
  {
    if($this->usuarios_model->tienePrivilegioDe('', 'facturacion/agregar/') == false && !isset($_GET['id_nr']))
      redirect(base_url('panel/home?msg=1'));

    $this->carabiner->css(array(
        array('panel/frm_cartaPorte.css'),
    ));
    $this->carabiner->js(array(
        array('bootstrap/bootstrap-tab.js'),
        array('bootstrap/bootstrap-tooltip.js'),
        array('libs/jquery.numeric.js'),
        array('general/keyjump.js'),
        array('general/util.js'),
        array('panel/facturacion/gastos_productos.js'),
        array('panel/facturacion/frm_addmod.js'),
        array('panel/facturacion/frm_otros.js'),
        array('panel/facturacion/frm_cartaPorte.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Agregar factura');
    $params['pagar_ordent']   = false;

    if(isset($_GET['ordent']{0}))
      $this->asignaOrdenTrabajo($_GET['ordent']);

    $this->load->library('cfdi');
    $this->load->model('facturacion_model');
    $this->load->model('empresas_model');
    $this->load->model('cunidadesmedida_model');

    if ( ! isset($_POST['borrador']))
    {
      $this->configAddModFactura();
      if($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $respons = $this->facturacion_model->addFactura();

        if($respons['passes'])
          redirect(base_url('panel/documentos/agregar/?msg=3&id='.$respons['id_factura'].'&of='.$_POST['new_orden_flete']));
        else
          $params['frm_errors'] = $this->showMsgs(2, $respons['msg']);
      }
    }
    else
    {
      $params['frm_errors'] = $this->procesaBorrador();

      if (isset($_POST['dfolio2']))
      {
        $_POST['dfolio'] = $_POST['dfolio2'];
      }
    }

    // Parametros por default.
    // $params['series'] = $this->facturacion_model->getSeriesFolios(100);
    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['getId'] = '';
    // Si existe el parametro get idb entonces se esta cargando un borrador de
    // factura o prefactura.
    if (isset($_GET['idb']))
    {
      $params['getId'] = 'idb='.$_GET['idb'];
      // carga los datos de la prefactura
      $params['borrador'] = $this->facturacion_model->getInfoFactura($_GET['idb']);
    }
    else // Parametros por default.
    {
      // Obtiene los datos de la empresa predeterminada.
      $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();
      // $this->db
      //   ->select("e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org, e.calle, e.colonia, e.cp, e.estado, e.localidad, e.municipio, e.pais,
      //             e.no_exterior, e.no_interior, e.rfc")
      //   ->from("empresas AS e")
      //   ->where("e.predeterminado", "t")
      //   ->get()
      //   ->row();

      $dire = [];
      if ($params['empresa_default']->calle) array_push($dire, $params['empresa_default']->calle);
      if ($params['empresa_default']->no_exterior) array_push($dire, $params['empresa_default']->no_exterior);
      if ($params['empresa_default']->no_interior) array_push($dire, $params['empresa_default']->no_interior);
      if ($params['empresa_default']->colonia) array_push($dire, $params['empresa_default']->colonia);
      if ($params['empresa_default']->localidad) array_push($dire, $params['empresa_default']->localidad);
      if ($params['empresa_default']->municipio) array_push($dire, $params['empresa_default']->municipio);
      if ($params['empresa_default']->estado) array_push($dire, $params['empresa_default']->estado);
      if ($params['empresa_default']->pais) array_push($dire, $params['empresa_default']->pais);
      if ($params['empresa_default']->cp) array_push($dire, $params['empresa_default']->cp);
      $params['dire'] = implode(' ', $dire);

      // Obtiene el numero de certificado de la empresa predeterminada.
      $params['no_certificado'] = $this->cfdi->obtenNoCertificado($params['empresa_default']->cer_org);
    }

    // Si es una nota de remision la que se quiere facturar carga sus datos.
    if ((isset($_GET['id_nr']) && $_GET['id_nr'] > 0) || (isset($_POST['id_nr']) && $_POST['id_nr'] > 0))
    {
      $params['id_nr'] = isset($_GET['id_nr'])? $_GET['id_nr']: $_POST['id_nr'];
      $params['borrador'] = $this->facturacion_model->getInfoFactura($params['id_nr']);
      $params['borrador']['info']->serie = '';
      $params['borrador']['info']->folio = '';
    } elseif (isset($_GET['id_vd']))
    {
      // Si es una venta del dia la que se quiere facturar carga sus datos.
      $this->load->model('ventas_dia_model');
      $params['borrador'] = $this->ventas_dia_model->getInfoFactura($_GET['id_vd']);
      $params['borrador']['info']->serie = '';
      $params['borrador']['info']->folio = '';
      $params['getId'] = 'id_vd='.$_GET['id_vd'];
    }

    $params['unidades'] = $this->db->select('*')->from('unidades')->where('status', 't')->order_by('nombre')->get()->result();

    // Si no es un borrador carga los datos POST
    if (empty($borrador['info']->cfdi_ext) && isset($_POST['cp'])) {
      $params['cfdiExt'] = json_encode([
        'cartaPorteSat' => $_POST['cp']
      ]);
    }

    // $params['remisiones'] = $this->facturacion_model->getRemisiones();

    $params['unidad_medidas'] = $this->cunidadesmedida_model->getCE();

    $metodosPago       = new MetodosPago();
    $formaPago         = new FormaPago();
    $usoCfdi           = new UsoCfdi();
    $tipoRelacion      = new TipoRelacion();
    $tipoDeComprobante = new TipoDeComprobante();
    $ceUnidades        = new UnidadesMedida();
    $ceMotTraslado     = new MotivoTraslado();
    $ceIncoterm        = new Incoterm();

    $params['metodosPago']       = $metodosPago->get()->all();
    $params['formaPago']         = $formaPago->get()->all();
    $params['usoCfdi']           = $usoCfdi->get()->all();
    $params['tipoRelacion']      = $tipoRelacion->get()->all();
    $params['tipoDeComprobante'] = $tipoDeComprobante->get()->all();
    $params['ceUnidades']        = $ceUnidades->getCE()->all();
    $params['ceMotTraslado']     = $ceMotTraslado->get()->all();
    $params['ceIncoterm']        = $ceIncoterm->get()->all();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/facturacion/agregar', $params);
    $this->load->view('panel/footer');
  }

  public function procesaBorrador()
  {
    $this->load->model('facturacion_model');

    $this->configAddModFactura(true);
    if($this->form_validation->run() == FALSE)
    {
      return $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      if (isset($_GET['idb']))
        $this->facturacion_model->updateFacturaBorrador($_GET['idb']);
      else
        $this->facturacion_model->addFacturaBorrador();

      redirect(base_url('panel/facturacion/agregar/?&msg=11'));
    }
  }

  public function getRemisiones()
  {
    $this->load->model('facturacion_model');
    $remisiones = $this->facturacion_model->getRemisiones();
    echo json_encode($remisiones);
  }

  /**
   * Agrega una factura a la bd
   *
   * @return void
   */
  public function refacturar()
  {
    $this->carabiner->js(array(
        array('libs/jquery.numeric.js'),
        array('general/keyjump.js'),
        array('general/util.js'),
        array('panel/facturacion/gastos_productos.js'),
        array('panel/facturacion/frm_addmod.js'),
        array('panel/facturacion/frm_otros.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Facturar');
    $params['pagar_ordent']   = false;

    if(isset($_GET['ordent']{0}))
      $this->asignaOrdenTrabajo($_GET['ordent']);

    $this->load->library('cfdi');
    $this->load->model('facturacion_model');

    $this->configAddModFactura();
    if($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $respons = $this->facturacion_model->refacturar($_GET['idr']);

      if($respons['passes'])
        redirect(base_url('panel/documentos/agregar/?msg=3&id='.$respons['id_factura']));
      else
        $params['frm_errors'] = $this->showMsgs(2, $respons['msg']);
    }

    // Parametros por default.
    $params['series'] = $this->facturacion_model->getSeriesFolios(100);
    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['factura'] = $this->facturacion_model->getInfoFactura($_GET['idr']);

    // echo "<pre>";
    //   var_dump($params['factura']);
    // echo "</pre>";exit;

    $params['unidades'] = $this->db->select('*')
      ->from('unidades')
      ->where('status', 't')
      ->order_by('nombre')
      ->get()
      ->result();

    $metodosPago       = new MetodosPago();
    $formaPago         = new FormaPago();
    $usoCfdi           = new UsoCfdi();
    $tipoDeComprobante = new TipoDeComprobante();
    // $monedas           = new Monedas();

    $params['metodosPago']       = $metodosPago->get()->all();
    $params['formaPago']         = $formaPago->get()->all();
    $params['usoCfdi']           = $usoCfdi->get()->all();
    $params['tipoDeComprobante'] = $tipoDeComprobante->get()->all();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/facturacion/refacturar', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Paga una factura.
   *
   * @return void
   */
  public function pagar()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('facturacion_model');
      $this->facturacion_model->pagaFactura();

      redirect(base_url('panel/facturacion/?'.MyString::getVarsLink(array('msg','id')).'&msg=7'));
    }
  }

  /**
   * Cancela una factura.
   *
   * @return void
   */
  public function cancelar()
  {
    if (isset($_GET['id']{0}) && isset($_GET['motivo']{0}) && isset($_GET['folioSustitucion']))
    {
      $this->load->model('facturacion_model');
      $response = $this->facturacion_model->cancelaFactura($_GET['id'], $_GET);

      if(isset($_GET['sec']) && $_GET['sec'] == 'pp')
        redirect(base_url("panel/facturacion/pago_parcialidad/?&msg={$response['msg']}"));
      else
        redirect(base_url("panel/facturacion/?&msg={$response['msg']}"));
    }
  }

  /**
   * Descarga el XML de la factura.
   *
   * @return void
   */
  public function xml()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('facturacion_model');
      $this->facturacion_model->descargarZip($_GET['id']);
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
  }

  public function descarga_masiva()
  {
    if (isset($_GET['id_empresa']{0}) && isset($_GET['fecha1']{0}) && isset($_GET['fecha2']{0}))
    {
      $this->load->model('facturacion_model');
      $this->facturacion_model->descargarMasiva($_GET['id_empresa'], $_GET['fecha1'], $_GET['fecha2']);
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
  }

  /**
   * Muestra la vista par el envio de los correo.
   *
   * @return void
   */
  public function enviar_documentos()
  {
    $this->carabiner->js(array(
      array('libs/jquery.cleditor.min.js'),
      array('panel/facturacion/email.js'),
    ));
    $this->carabiner->css(array(
      array('libs/jquery.cleditor.css','screen'),
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Facturas');

    $this->load->model('facturacion_model');
    $this->load->model('clientes_model');

    $factura = $this->facturacion_model->getInfoFactura($_GET['id']);
    $cliente = $this->clientes_model->getClienteInfo($factura['info']->id_cliente);

    $params['emails_default'] = array();
    if ($cliente['info']->email !== '')
      $params['emails_default'] = explode(',', $cliente['info']->email);

    // echo "<pre>";
    //   var_dump($params['emails_default']);
    // echo "</pre>";exit;

    if(isset($_GET['msg']{0}))
    {
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      if ($_GET['msg'] == 10)
      {
        $params['close'] = 1;
      }
    }

    $this->load->view('panel/facturacion/email',$params);
  }

  /**
   * Envia los documentos al cliente.
   *
   * @return void
   */
  public function email()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('facturacion_model');
      $response = $this->facturacion_model->enviarEmail($_GET['id']);

      redirect(base_url("panel/facturacion/enviar_documentos?id={$_GET['id']}&msg={$response['msg']}"));
    }
    else redirect(base_url('panel/facturacion/?msg='));
  }

  /*
   |------------------------------------------------------------------------
   | METODOS PARA EL TIMBRADO
   |------------------------------------------------------------------------
   */

  /**
   * Verifique si un timbre pendiente ya ah sido enviado al sat.
   *
   * @return void
   */
  public function timbre_pending()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('facturacion_model');

      $finalizado = $this->facturacion_model->verificarTimbrePendiente($_GET['id']);

      if ($finalizado)
        redirect(base_url('panel/facturacion/?msg=101'));
      else
        redirect(base_url('panel/facturacion/?msg=102'));
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
  }

  /*
   |------------------------------------------------------------------------
   | Texto
   |------------------------------------------------------------------------
   */

  /**
   * Configura los metodos de agregar y modificar
   *
   * @return void
   */
  private function configAddModFactura($borrador = false)
  {
    $required = $borrador ? '' : 'required';

    // $callback_seriefolio_check = 'callback_seriefolio_check';
    $callback_isValidDate      = 'callback_isValidDate';
    $callback_val_total        = 'callback_val_total';
    $callback_chk_cer_caduca   = 'callback_chk_cer_caduca';
    if ($borrador)
    {
      // $callback_seriefolio_check = '';
      $callback_isValidDate      = '';
      $callback_val_total        = '';
      $callback_chk_cer_caduca   = '';
    }

    $this->load->library('form_validation');
    $rules = array(

        array('field'   => 'did_empresa',
              'label'   => 'Empresa',
              'rules'   => 'required|max_length[25]'),
        array('field'   => 'did_cliente',
              'label'   => 'Cliente',
              'rules'   => 'required|max_length[25]'),
        array('field'   => 'dserie',
              'label'   => 'Serie',
              'rules'   => 'max_length[25]'),
        array('field'   => 'dfolio',
              'label'   => 'Folio',
              'rules'   => 'required|numeric|callback_seriefolio_check'),
        array('field'   => 'dno_aprobacion',
              'label'   => 'Numero de aprobacion',
              'rules'   => $required.'|numeric'),
        array('field'   => 'dano_aprobacion',
              'label'   => 'Fecha de aprobacion',
              'rules'   => $required.'|max_length[10]|'.$callback_isValidDate),

        array('field'   => 'cfdiRelPrev',
              'label'   => 'CFDIREl',
              'rules'   => ''),

        array('field'   => 'dfecha',
              'label'   => 'Fecha',
              'rules'   => $required.'|max_length[25]'), //|callback_isValidDate

        array('field'   => 'total_importe',
              'label'   => 'SubTotal1',
              'rules'   => $required.'|numeric'),
        array('field'   => 'total_subtotal',
              'label'   => 'SubTotal',
              'rules'   => $required.'|numeric'),

        array('field'   => 'id_nr',
              'label'   => 'Nota venta',
              'rules'   => 'numeric|callback_notaventa_check'),

        array('field'   => 'total_descuento',
              'label'   => 'Descuento',
              'rules'   => $required.'|numeric'),
        array('field'   => 'total_iva',
              'label'   => 'IVA',
              'rules'   => $required.'|numeric'),
        array('field'   => 'total_retiva',
              'label'   => 'Retencion IVA',
              'rules'   => $required.'|numeric'),
        array('field'   => 'total_ieps',
              'label'   => 'IEPS',
              'rules'   => 'numeric'),
        array('field'   => 'total_totfac',
              'label'   => 'Total',
              'rules'   => $required.'|numeric|'.$callback_val_total),
        array('field'   => 'dforma_pago',
              'label'   => 'Forma de pago',
              'rules'   => $required.'|max_length[80]'),
        array('field'   => 'dmetodo_pago',
              'label'   => 'Metodo de pago',
              'rules'   => $required.'|max_length[40]'),
        array('field'   => 'dmetodo_pago_digitos',
              'label'   => 'Ultimos 4 digitos',
              'rules'   => 'max_length[20]'),
        array('field'   => 'dcondicion_pago',
              'label'   => 'Condición de pago',
              'rules'   => $required.'|max_length[2]'),

        array('field'   => 'dplazo_credito',
            'label'   => 'Plazo de crédito',
            'rules'   => 'numeric'),

        array('field'   => 'dempresa',
              'label'   => 'Empresa',
              'rules'   => ''),
        array('field'   => 'dcliente',
              'label'   => 'Cliente',
              'rules'   => ''),
        array('field'   => 'dcliente_rfc',
              'label'   => 'Cliente',
              'rules'   => ''),
        array('field'   => 'dcliente_domici',
              'label'   => 'Cliente',
              'rules'   => ''),
        array('field'   => 'dcliente_ciudad',
              'label'   => 'Cliente',
              'rules'   => ''),
        array('field'   => 'dttotal_letra',
              'label'   => 'letra',
              'rules'   => ''),
        array('field'   => 'dreten_iva',
              'label'   => 'Retecion IVA',
              'rules'   => ''),

        array('field'   => 'prod_dcantidad[]',
              'label'   => 'prod_dcantidad',
              'rules'   => 'callback_check_max_decimales'),
        array('field'   => 'prod_dkilos[]',
              'label'   => 'prod_dkilos',
              'rules'   => ''),
        array('field'   => 'prod_dcajas[]',
              'label'   => 'prod_dcajas',
              'rules'   => ''),
        array('field'   => 'prod_ddescripcion[]',
              'label'   => 'prod_ddescripcion',
              'rules'   => ''),
        array('field'   => 'prod_ddescripcion2[]',
              'label'   => 'prod_ddescripcion2',
              'rules'   => ''),
        array('field'   => 'prod_ddescuento[]',
              'label'   => 'prod_ddescuento',
              'rules'   => ''),
        array('field'   => 'prod_ddescuento_porcent[]',
              'label'   => 'prod_ddescuento_porcent',
              'rules'   => ''),
        array('field'   => 'prod_dpreciou[]',
              'label'   => 'prod_dpreciou',
              'rules'   => 'callback_check_max_decimales'),
        array('field'   => 'prod_importe[]',
              'label'   => 'Importe de los productos',
              'rules'   => ''), //greater_than[0]
        array('field'   => 'prod_diva_total[]',
              'label'   => 'prod_diva_total',
              'rules'   => ''),
        array('field'   => 'prod_dreten_iva_total[]',
              'label'   => 'prod_dreten_iva_total',
              'rules'   => ''),
        array('field'   => 'prod_dreten_iva_porcent[]',
              'label'   => 'prod_dreten_iva_porcent',
              'rules'   => ''),
        array('field'   => 'prod_diva_porcent[]',
              'label'   => 'prod_diva_porcent',
              'rules'   => ''),
        array('field'   => 'dieps[]',
              'label'   => 'dieps',
              'rules'   => ''),
        array('field'   => 'dieps_total[]',
              'label'   => 'dieps_total',
              'rules'   => ''),
        array('field'   => 'prod_dmedida[]',
              'label'   => 'prod_dmedida',
              'rules'   => ''),
        array('field'   => 'isCert[]',
              'label'   => 'Cert.',
              'rules'   => ''),
        array('field'   => 'pclave_unidad[]',
              'label'   => 'Clave de unidad',
              'rules'   => ''),
        array('field'   => 'pclave_unidad_cod[]',
              'label'   => 'Clave de unidad',
              'rules'   => ''),

        array('field'   => 'dversion',
              'label'   => '',
              'rules'   => ''),
        array('field'   => 'dcer_caduca',
              'label'   => 'Empresa',
              'rules'   => $callback_chk_cer_caduca),

        array('field'   => 'dno_certificado',
              'label'   => 'No. Certificado',
              'rules'   => $required),
        array('field'   => 'dtipo_comprobante',
              'label'   => 'Tipo comproante',
              'rules'   => $required),
        array('field'   => 'dobservaciones',
              'label'   => 'Observaciones',
              'rules'   => ''),
        array('field'   => 'dno_trazabilidad',
              'label'   => 'No Trazabilidad',
              'rules'   => 'max_length[15]|callback_check_trazabilidad'),

        array('field'   => 'remitente_nombre',
              'label'   => 'Nombre Remitente',
              'rules'   => 'max_length[130]'),
        array('field'   => 'remitente_rfc',
              'label'   => 'RFC Remitente',
              'rules'   => 'max_length[13]'),
        array('field'   => 'remitente_domicilio',
              'label'   => 'Domicilio Remitente',
              'rules'   => 'max_length[250]'),
        array('field'   => 'remitente_chofer',
              'label'   => 'Chofer Remitente',
              'rules'   => 'max_length[50]'),
        array('field'   => 'remitente_marca',
              'label'   => 'Marca Remitente',
              'rules'   => 'max_length[50]'),
        array('field'   => 'remitente_modelo',
              'label'   => 'Modelo Remitente',
              'rules'   => 'max_length[50]'),
        array('field'   => 'remitente_placas',
              'label'   => 'Placas Remitente',
              'rules'   => 'max_length[50]'),

        array('field'   => 'destinatario_nombre',
              'label'   => 'Nombre Destinatario',
              'rules'   => 'max_length[130]'),
        array('field'   => 'destinatario_rfc',
              'label'   => 'RFC Destinatario',
              'rules'   => 'max_length[13]'),
        array('field'   => 'destinatario_domicilio',
              'label'   => 'Domicilio Destinatario',
              'rules'   => 'max_length[250]'),

        array('field'   => 'es_carta_porte',
              'label'   => 'Carta Porte',
              'rules'   => ''),
    );

    if (isset($_POST['privAddDescripciones']{0})) {
      $rules[] = array('field'   => 'prod_did_prod[]',
                      'label'   => 'Clasificación',
                      'rules'   => '');
      $rules[] = array('field'   => 'prod_did_calidad[]',
                      'label'   => 'Calidad',
                      'rules'   => '');
      $rules[] = array('field'   => 'prod_did_tamanio[]',
                      'label'   => 'Tamaño',
                      'rules'   => '');
    } else {
      $rules[] = array('field'   => 'prod_did_prod[]',
                    'label'   => 'Clasificación',
                    'rules'   => 'required');
      $rules[] = array('field'   => 'prod_did_calidad[]',
                      'label'   => 'Calidad',
                      'rules'   => 'required');
      $rules[] = array('field'   => 'prod_did_tamanio[]',
                      'label'   => 'Tamaño',
                      'rules'   => 'required');
      $rules[] = array('field'   => 'prod_did_tamanio_prod[]',
                      'label'   => 'TamañoProd',
                      'rules'   => 'required');
    }

    if (isset($_POST['palletsIds']) && isset($_POST['timbrar']))
    {
      $rules[] = array(
        'field'   => 'palletsIds[]',
        'label'   => 'Pallets',
        'rules'   => 'callback_check_existen_pallets'
      );
    }

    if (isset($_POST['remisionesIds']) && isset($_POST['timbrar']))
    {
      $rules[] = array(
        'field'   => 'remisionesIds[]',
        'label'   => 'Remisiones',
        'rules'   => ''
      );
    }

    $requerido_moneda = '';
    if (isset($_POST['moneda']) && $_POST['moneda'] !== 'MXN' )
      $requerido_moneda = 'required|';
    $rules[] = array(
        'field'   => 'moneda',
        'label'   => 'Moneda',
        'rules'   => $requerido_moneda.'max_length[6]'
      );
    $rules[] = array(
        'field'   => 'tipoCambio',
        'label'   => 'Tipo de Cambio',
        'rules'   => $requerido_moneda.'numeric'
      );

    $rules[] = array(
      'field'   => 'pproveedor_seguro',
      'label'   => '',
      'rules'   => ''
    );
    $rules[] = array(
      'field'   => 'seg_id_proveedor',
      'label'   => '',
      'rules'   => ''
    );
    $rules[] = array(
      'field'   => 'seg_poliza',
      'label'   => '',
      'rules'   => ''
    );

    $rules[] = array(
      'field'   => 'pproveedor_certificado51',
      'label'   => '',
      'rules'   => ''
    );
    $rules[] = array(
      'field'   => 'cert_id_proveedor51',
      'label'   => '',
      'rules'   => ''
    );
    $rules[] = array(
      'field'   => 'cert_certificado51',
      'label'   => '',
      'rules'   => ''
    );
    $rules[] = array(
      'field'   => 'cert_bultos51',
      'label'   => '',
      'rules'   => ''
    );

    $rules[] = array(
      'field'   => 'pproveedor_certificado52',
      'label'   => '',
      'rules'   => ''
    );
    $rules[] = array(
      'field'   => 'cert_id_proveedor52',
      'label'   => '',
      'rules'   => ''
    );
    $rules[] = array(
      'field'   => 'cert_certificado52',
      'label'   => '',
      'rules'   => ''
    );
    $rules[] = array(
      'field'   => 'cert_bultos52',
      'label'   => '',
      'rules'   => ''
    );

    $rules[] = array(
      'field'   => 'pproveedor_supcarga',
      'label'   => '',
      'rules'   => ''
    );
    $rules[] = array(
      'field'   => 'supcarga_id_proveedor',
      'label'   => '',
      'rules'   => ''
    );
    $rules[] = array(
      'field'   => 'supcarga_numero',
      'label'   => '',
      'rules'   => ''
    );
    $rules[] = array(
      'field'   => 'supcarga_bultos',
      'label'   => '',
      'rules'   => ''
    );

    if (!empty($this->input->post('comercioExterior')['tipoOperacion']) ||
      !empty($this->input->post('comercioExterior')['clavePedimento']) ||
      !empty($this->input->post('comercioExterior')['certificadoOrigen']) ) {
      array_push($rules,
          array(
            'field'   => 'comercioExterior[motivoTraslado]',
            'label'   => 'Motivo traslado',
            'rules'   => ''
          ), // callback_comercio_exterior_check
          array(
            'field'   => 'comercioExterior[tipoOperacion]',
            'label'   => 'Tipo de operacion',
            'rules'   => 'required'
          ), // callback_comercio_exterior_check
          array(
            'field'   => 'comercioExterior[clavePedimento]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[certificadoOrigen]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[numCertificadoOrigen]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[numeroExportadorConfiable]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[incoterm]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[subdivision]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[observaciones]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[tipoCambioUSD]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[totalUSD]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[emisor][curp]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[emisor][domicilio][calle]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[emisor][domicilio][numeroExterior]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[emisor][domicilio][numeroInterior]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[emisor][domicilio][pais]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[emisor][domicilio][estado]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[emisor][domicilio][municipio]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[emisor][domicilio][localidad]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[emisor][domicilio][codigoPostal]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[emisor][domicilio][colonia]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[receptor][numRegIdTrib]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[receptor][domicilio][calle]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[receptor][domicilio][numeroExterior]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[receptor][domicilio][numeroInterior]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[receptor][domicilio][pais]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[receptor][domicilio][estado]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[receptor][domicilio][municipio]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[receptor][domicilio][localidad]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[receptor][domicilio][codigoPostal]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[receptor][domicilio][colonia]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[propietario][numRegIdTrib]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[propietario][residenciaFiscal]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[destinatario][numRegIdTrib]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[destinatario][nombre]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[destinatario][domicilio][calle]',
            'label'   => 'Destinatario Calle',
            'rules'   => 'required|max_length[100]'
          ),
          array(
            'field'   => 'comercioExterior[destinatario][domicilio][numeroExterior]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[destinatario][domicilio][numeroInterior]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[destinatario][domicilio][referencia]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[destinatario][domicilio][pais]',
            'label'   => 'Destinatario Pais',
            'rules'   => 'required'
          ),
          array(
            'field'   => 'comercioExterior[destinatario][domicilio][estado]',
            'label'   => 'Destinatario Estado',
            'rules'   => 'required|max_length[30]'
          ),
          array(
            'field'   => 'comercioExterior[destinatario][domicilio][municipio]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[destinatario][domicilio][localidad]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[destinatario][domicilio][codigoPostal]',
            'label'   => 'Destinatario CodigoPostal',
            'rules'   => 'required|max_length[12]'
          ),
          array(
            'field'   => 'comercioExterior[destinatario][domicilio][colonia]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[mercancias][noIdentificacion][]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[mercancias][fraccionArancelaria][]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[mercancias][cantidadAduana][]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[mercancias][unidadAduana][]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[mercancias][valorUnitarioAduana][]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[mercancias][valorDolares][]',
            'label'   => '',
            'rules'   => ''
          ),
          array(
            'field'   => 'comercioExterior[mercancias][descripcionesEspecificas][]',
            'label'   => '',
            'rules'   => ''
          )
        );
    }

    $this->form_validation->set_rules($rules);
  }

  /**
   * Imprime la factura.
   *
   * @return void
   */
  public function imprimir()
  {
    if(isset($_GET['id']{0}))
    {
      $this->load->model('facturacion_model');
      if($this->input->get('p') == 'true')
        $this->facturacion_model->generaFacturaPdf($_GET['id']);
      else {
        $params['url'] = 'panel/facturacion/imprimir/?id='.$_GET['id'].'&p=true&lang='.(!empty($_GET['lang'])? $_GET['lang']: null);
        $this->load->view('panel/facturacion/print_view', $params);
      }
    }
    else
      redirect(base_url('panel/facturacion/?msg=1'));
  }


  public function chk_cer_caduca($date)
  {
    $hoy = date('Y-m-d');

    // if (strtotime($hoy) > strtotime($date))
    // {
    //   $this->form_validation->set_message('chk_cer_caduca', 'El certificado de la empresa caducó, actualize la información de la misma.');
    //   return false;
    // }

    return true;
  }

  public function notaventa_check($str)
  {
    if (floatval($str) == 0)
      return true;
    $res = $this->db->select('id_factura')
        ->from('remisiones_historial')
        ->where("id_remision = ".$str." AND status <> 'ca' AND status <> 'b'")
        ->get();
    if ($res->num_rows() > 0) {
      $this->form_validation->set_message('notaventa_check', 'La remision ya esta facturada.');
      return false;
    }
    return true;
  }

  /**
   * Verifica que la serie y folio enviados del form no esten asignados a una
   * factura y tambien que esten vigentes.
   *
   * @param string $str
   * @return boolean
   */
  public function seriefolio_check($str)
  {
    if($str != ''){
      $sql = $ms = '';

      $res = $this->db->select('id_factura')
        ->from('facturacion')
        ->where("serie = '".$this->input->post('dserie')."' AND folio = ".$str." AND id_empresa = ". $this->input->post('did_empresa')." AND is_factura = 't'")
        ->get();

      $data = $res->row();

      if($res->num_rows() > 0){

        if (isset($_GET['idb']))
        {
          if ($_GET['idb'] != $data->id_factura)
          {
            $this->form_validation->set_message('seriefolio_check', 'El serie y folio ya esta utilizado por otra Factura.');
            $folio = $this->facturacion_model->getFolioSerie($_POST['dserie'], $_POST['did_empresa'], "es_nota_credito = 'f'");
            $_POST['dfolio2'] = $folio[0]->folio;

            return false;
          }
        }
        else
        {
          $this->form_validation->set_message('seriefolio_check', 'El serie y folio ya esta utilizado por otra Factura.');

          $folio = $this->facturacion_model->getFolioSerie($_POST['dserie'], $_POST['did_empresa'], "es_nota_credito = 'f'");
          $_POST['dfolio2'] = $folio[0]->folio;
          return false;
        }

      } else {
        // $anoLimite = date('Y-m-d',strtotime($this->input->post('dano_aprobacion') . " + 730 day"));

        // $hoy = date('Y-m-d');
        // // $hoy = '2015-07-19';

        // if (strtotime($hoy) > strtotime($anoLimite))
        // {
        //   $this->form_validation->set_message('seriefolio_check', 'El serie y folio ya caducaron, no pueden ser utilizados.');
        //   return false;
        // }
      }
    }
    return true;
  }

  /**
   * Form_validation: Valida su una fecha esta en formato correcto
   */
  public function isValidDate($str)
  {
    if($str != ''){
      if(MyString::isValidDate($str) == false){
        $this->form_validation->set_message('isValidDate', 'El campo %s no es una fecha valida');
        return false;
      }
    }
    return true;
  }

  public function val_total($str)
  {
    if($str < 0){
      $this->form_validation->set_message('val_total', 'El Total no puede ser menor que 0, verifica los datos ingresados.');
      return false;
    }
    return true;
  }

  public function check_existen_pallets($str)
  {
    $error = false;
    $palletsYaFacturados = array();
    foreach ($_POST['palletsIds'] as $palletId)
    {
      $query = $this->db->query("SELECT f.id_factura, rp.folio
                                 FROM facturacion_pallets fp
                                 INNER JOIN facturacion f ON f.id_factura = fp.id_factura
                                 INNER JOIN rastria_pallets rp ON rp.id_pallet = fp.id_pallet
                                 WHERE fp.id_pallet = {$palletId} AND f.status_timbrado != 'ca' AND f.status in ('p', 'pa')");

      if ($query->num_rows() > 0)
      {
        $error = true;
        $pallet = $query->result();
        $palletsYaFacturados[] = $pallet[0]->folio;
      }

    }

    if ($error)
    {
      $this->form_validation->set_message('check_existen_pallets', 'Los pallets con los folios '.implode(', ', $palletsYaFacturados).' ya estan facturados.');
      return false;
    }

    return true;
  }

  public function check_trazabilidad($value)
  {
    if (trim($value) != '') {
      $sql = !empty($_GET['id_nr'])? " AND f.id_factura <> {$_GET['id_nr']}": '';
      $error = false;
      $query = $this->db->query("SELECT f.id_factura, fp.no_trazabilidad
                                   FROM facturacion_otrosdatos fp
                                   INNER JOIN facturacion f ON f.id_factura = fp.id_factura
                                   WHERE fp.no_trazabilidad = '{$value}'
                                    AND f.id_empresa = {$this->input->post('did_empresa')}
                                    AND f.is_factura = 'f' AND f.status <> 'ca' AND f.status <> 'b'
                                    {$sql}");

      if ($query->num_rows() > 0)
      {
        $data = $query->row();

        $this->form_validation->set_message('check_trazabilidad', "El numero de trazabilidad '{$data->no_trazabilidad}' ya esta registrado.");
        return false;
      }
    }

    return true;
  }

  // Validaciones extras del complemento ComercioExterior
  public function comercio_exterior_check($str)
  {
    $this->load->model('monedas_model');
    $monedas = $this->monedas_model->get();
    unset($monedas['M.N.']);

    $this->ineMessage = '';
    $inputs = $this->input->post();

    $this->load->model('empresas_model');
    $this->load->model('clientes_model');

    $empresa = $this->empresas_model->getInfoEmpresa($inputs['did_empresa'], true);
    $cliente = $this->clientes_model->getClienteInfo($inputs['did_cliente'], true);
    $empresa = isset($empresa['info']) ? $empresa['info'] : null;
    $cliente = isset($cliente['info']) ? $cliente['info'] : null;

    if (!$this->validateTImporte($inputs['comercioExterior']['tipoCambioUSD'])) {
      $this->form_validation->set_message('comercio_exterior_check', "El Tipo de Cambio USD no es valido, maximo 6 decimales.");
      return false;
    }
    if (!$this->validateTImporte($inputs['comercioExterior']['totalUSD'])) {
      $this->form_validation->set_message('comercio_exterior_check', "El Total USD no es valido, maximo 6 decimales.");
      return false;
    }
    if (!$this->validateTCurpLite($inputs['comercioExterior']['emisor']['curp'])) {
      $this->form_validation->set_message('comercio_exterior_check', "La CURP del Emisor no es valida.");
      return false;
    }
    // if (!$this->validateTCurpLite($inputs['comercioExterior']['receptor']['Curp'])) {
    //   $this->form_validation->set_message('comercio_exterior_check', "La CURP del Receptor no es valida.");
    //   return false;
    // }
    // if (!$this->validateTCurpLite($inputs['comercioExterior']['Destinatario']['Curp'])) {
    //   $this->form_validation->set_message('comercio_exterior_check', "La CURP del Destinatario no es valida.");
    //   return false;
    // }
    // if (!$this->validateTRfcLite($inputs['comercioExterior']['Destinatario']['Rfc'])) {
    //   $this->form_validation->set_message('comercio_exterior_check', "El RFC del Destinatario no es valido.");
    //   return false;
    // }

    if (!isset($monedas[$inputs['moneda']])) { // cfdi:Comprobante:Moneda
      $this->form_validation->set_message('comercio_exterior_check', "El Tipo de Moneda no es valido, selecciona un valor diferente a '{$inputs['moneda']}'.");
      return false;
    }

    // cfdi:Comprobante:TipoCambio
    if (!isset($inputs['tipoCambio']) || preg_match('/^[0-9]{1,14}(.([0-9]{1,6}))?$/', $inputs['tipoCambio']) !== 1) {
      $this->form_validation->set_message('comercio_exterior_check', "El Tipo de Cambio es requerido y numerico.");
      return false;
    }

    // cfdi:Comprobante:tipoDeComprobante
    // if (isset($inputs['comercioExterior']['tipoOperacion']) &&
    //     ($inputs['comercioExterior']['tipoOperacion'] == 'A' || $inputs['comercioExterior']['tipoOperacion'] == '2')) {
    //   if (!isset($inputs['dtipo_comprobante']) || $inputs['dtipo_comprobante'] != 'ingreso') {
    //     $this->form_validation->set_message('comercio_exterior_check', "El tipo de comprobante debe ser ingreso, ya que tipo de operación del complemento es A o 2.");
    //     return false;
    //   }
    // }

    // // Emisor Nodos: DomicilioFiscal y ExpedidoEn
    // if (!is_array($empresa) && $empresa->pais != '' && $empresa->pais != 'MEX') {
    //   $this->form_validation->set_message('comercio_exterior_check', "El campo país de la empresa tiene que ser MEX, modifica la empresa seleccionando del catalogo el país.");
    //   return false;
    // }
    // if (!is_array($empresa) && $empresa->estado != '') {
    //   $num = $this->db->query("SELECT * FROM otros.c_estados WHERE c_pais = '{$empresa->pais}' AND c_estado = '{$empresa->estado}'")->result();
    //   if (count($num) === 0) {
    //     $this->form_validation->set_message('comercio_exterior_check', "El campo estado de la empresa tiene que ser un valor del catalogo, modifica la empresa seleccionando el estado del catalogo.");
    //     return false;
    //   }
    // }
    // if (!is_array($empresa) && $empresa->municipio != '') {
    //   $num = $this->db->query("SELECT * FROM otros.c_municipios WHERE c_estado = '{$empresa->estado}' AND c_municipio = '{$empresa->municipio}'")->result();
    //   if (count($num) === 0) {
    //     $this->form_validation->set_message('comercio_exterior_check', "El campo municipio de la empresa tiene que ser un valor del catalogo, modifica la empresa seleccionando el municipio del catalogo.");
    //     return false;
    //   }
    // }
    // if (!is_array($empresa) && $empresa->localidad != '') {
    //   $num = $this->db->query("SELECT * FROM otros.c_localidades WHERE c_estado = '{$empresa->estado}' AND c_localidad = '{$empresa->localidad}'")->result();
    //   if (count($num) === 0) {
    //     $this->form_validation->set_message('comercio_exterior_check', "El campo localidad de la empresa tiene que ser un valor del catalogo, modifica la empresa seleccionando el localidad del catalogo.");
    //     return false;
    //   }
    // }
    // if (!is_array($empresa) && $empresa->cp != '') {
    //   $query = "SELECT * FROM otros.c_cps WHERE c_estado = '{$empresa->estado}' AND c_municipio = '{$empresa->municipio}' AND c_cp = '{$empresa->cp}'";
    //   if (!is_array($empresa) && $empresa->localidad != '')
    //     $query .= " AND c_localidad = '{$empresa->localidad}'";
    //   $num = $this->db->query($query)->result();
    //   if (count($num) === 0) {
    //     $this->form_validation->set_message('comercio_exterior_check', "El campo codigo postal de la empresa tiene que ser un valor del catalogo, modifica la empresa seleccionando el codigo postal del catalogo.");
    //     return false;
    //   }
    // }
    // if (!is_array($empresa) && $empresa->colonia != '') {
    //   $num = $this->db->query("SELECT * FROM otros.c_colonias WHERE c_cp = '{$empresa->cp}' AND c_colonia = '{$empresa->colonia}'")->result();
    //   if (count($num) === 0) {
    //     $this->form_validation->set_message('comercio_exterior_check', "El campo colonia de la empresa tiene que ser un valor del catalogo, modifica la empresa seleccionando el colonia del catalogo.");
    //     return false;
    //   }
    // }

    // cfdi:Comprobante:Receptor
    if (!is_array($cliente) && $cliente->rfc != 'XEXX010101000') { // cfdi:Comprobante:Receptor:rfc
      $this->form_validation->set_message('comercio_exterior_check', "El RFC del cliente tiene que ser XEXX010101000.");
      return false;
    }
    if (!is_array($cliente) && $cliente->nombre_fiscal == '') { // cfdi:Comprobante:Receptor:nombre
      $this->form_validation->set_message('comercio_exterior_check', "El nombre del cliente es requerido.");
      return false;
    }
    // cfdi:Comprobante:Receptor:Domicilio
    if (isset($cliente->pais))
      $pais = $this->db->query("SELECT * FROM otros.c_paises WHERE c_pais = '{$cliente->pais}' AND c_pais <> 'MEX'")->row();
    if (!isset($pais->c_pais)) {
      $this->form_validation->set_message('comercio_exterior_check', "El campo país del cliente tiene que ser un valor del catalogo y ser diferente a MEX, modifica el cliente seleccionando del catalogo el país.");
      return false;
    }
    $num = $this->db->query("SELECT * FROM otros.c_estados WHERE c_pais = '{$cliente->pais}'")->result();
    if (count($num) > 0) {
      $num = $this->db->query("SELECT * FROM otros.c_estados WHERE c_pais = '{$cliente->pais}' AND c_estado = '{$cliente->estado}'")->result();
      if (count($num) === 0) {
        $this->form_validation->set_message('comercio_exterior_check', "El campo estado del cliente tiene que ser un valor del catalogo, modifica el cliente seleccionando del catalogo el estado.");
        return false;
      }
    }
    if (!is_array($cliente) && $cliente->cp != '') {
      if ($pais->patron_cp != '') {
        if (preg_match("/^{$pais->patron_cp}$/u", $cliente->cp) !== 1) {
          $this->form_validation->set_message('comercio_exterior_check', "El formato del campo codigo postal del cliente no es valido.");
          return false;
        }
      }
    } else {
      $this->form_validation->set_message('comercio_exterior_check', "El campo codigo postal del cliente es requerido.");
      return false;
    }

    // cce:ComercioExterior:TipoOperacion
    if ($inputs['comercioExterior']['tipo_operacion'] == 'A') {
      $atributos = ['clave_pedimento' => 'str', 'certificado_origen' => 'str', 'num_certificado_origen' => 'str',
                    'numero_exportador_confiable' => 'str', 'incoterm' => 'str', 'subdivision' => 'str', 'tipocambio_USD' => 'str',
                    'total_USD' => 'str', 'Mercancias' => 'array'];
      foreach ($atributos as $key => $value) {
        if ($value == 'str' && isset($inputs['comercioExterior'][$key]{0})) {
          $this->form_validation->set_message('comercio_exterior_check', "No debe existir ".str_replace('_', ' ', ucfirst($key)).", ya que Tipo operacion es Exportación de servicios.");
          return false;
        } elseif ($value == 'array' && isset($inputs['comercioExterior'][$key]) && count($inputs['comercioExterior'][$key]) > 0) {
          $this->form_validation->set_message('comercio_exterior_check', "No debe existir ".str_replace('_', ' ', ucfirst($key)).", ya que Tipo operacion es Exportación de servicios.");
          return false;
        }
      }
    } else {
      $atributos = ['clave_pedimento' => 'str', 'certificado_origen' => 'str', 'incoterm' => 'str', 'subdivision' => 'str',
                    'tipocambio_USD' => 'str', 'total_USD' => 'str', 'Mercancias' => 'array'];
      foreach ($atributos as $key => $value) {
        if ($value == 'str' && !isset($inputs['comercioExterior'][$key]{0})) {
          $this->form_validation->set_message('comercio_exterior_check', "Debe existir ".str_replace('_', ' ', ucfirst($key)).", ya que Tipo operacion es Exportación.");
          return false;
        } elseif ($value == 'array' && (!isset($inputs['comercioExterior'][$key]) || count($inputs['comercioExterior'][$key]) === 0)) {
          $this->form_validation->set_message('comercio_exterior_check', "Debe existir ".str_replace('_', ' ', ucfirst($key)).", ya que Tipo operacion es Exportación.");
          return false;
        }
      }
    }

    // cce:ComercioExterior:TotalUSD
    if (isset($inputs['comercioExterior']['total_USD'])) {
      $total_usd = 0;
      if (isset($inputs['comercioExterior']['Mercancias']['NoIdentificacion'])) {
        foreach ($inputs['comercioExterior']['Mercancias']['NoIdentificacion'] as $key => $value) {
          if ( isset($inputs['comercioExterior']['Mercancias']['ValorDolares'][$key]{0}) )
            $total_usd += floatval($inputs['comercioExterior']['Mercancias']['ValorDolares'][$key]);
        }
      }
      if (!$total_usd == floatval($inputs['comercioExterior']['total_USD'])) {
        $this->form_validation->set_message('comercio_exterior_check', "El campo Total USD no es valido, no es igual a la suma de Valor Dolares de las mercancias.");
        return false;
      }
    }

    // cce:ComercioExterior:CertificadoOrigen
    if (isset($inputs['comercioExterior']['certificado_origen']) && $inputs['comercioExterior']['certificado_origen'] == '0' &&
        isset($inputs['comercioExterior']['num_certificado_origen']{0}) ) {
      $this->form_validation->set_message('comercio_exterior_check', "El Num de certificado no debe registrarse, ya que no funge como certificado de origen.");
      return false;
    }

    // cce:ComercioExterior:Emisor:Curp
    if (strlen($empresa->rfc) == 12 && isset($inputs['comercioExterior']['Emisor']['Curp']{0})) {
      $this->form_validation->set_message('comercio_exterior_check', "La CURP del emisor no debe registrarse, ya que el emisor es una persona moral.");
      return false;
    }

    // cce:ComercioExterior:Receptor:NumRegIdTrib
    $patron_rfc = $pais->patron_rfc;
    if ($patron_rfc == '')
      $patron_rfc = '.{6,40}';
    if ($patron_rfc != '' && isset($inputs['comercioExterior']['Receptor']['NumRegIdTrib']{0})) {
      if (preg_match("/^{$patron_rfc}$/u", $inputs['comercioExterior']['Receptor']['NumRegIdTrib']) !== 1) {
        $this->form_validation->set_message('comercio_exterior_check', "El Num Reg Id Trib del receptor no es valido el formato.");
        return false;
      }
    }

    // cce:ComercioExterior
    if (!isset($inputs['comercioExterior']['Destinatario']['NumRegIdTrib']{0}) && !isset($inputs['comercioExterior']['Destinatario']['Rfc']{0})) {
      $this->form_validation->set_message('comercio_exterior_check', "Debe existir el atributo Num Reg Id Trib o RFC del Destinatario.");
      return false;
    }
    // cce:ComercioExterior:Destinatario:Rfc
    if (isset($inputs['comercioExterior']['Destinatario']['Rfc']{0}) && $inputs['comercioExterior']['Destinatario']['Rfc'] != 'XAXX010101000'
        && $inputs['comercioExterior']['Destinatario']['Rfc'] != 'XEXX010101000') {
      $this->form_validation->set_message('comercio_exterior_check', "El RFC del destinatario no puede ser uno genérico.");
      return false;
    }
    // cce:ComercioExterior:Destinatario:Domicilio
    if (isset($inputs['comercioExterior']['Destinatario']['Domicilio']['Pais']{0})) {
      $pais = $this->db->query("SELECT * FROM otros.c_paises WHERE c_pais = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['Pais']}'")->row();
      if (!isset($pais->c_pais)) {
        $this->form_validation->set_message('comercio_exterior_check', "El campo país del destinatario tiene que ser un valor del catalogo, selecciona del catalogo el país.");
        return false;
      }

      // cce:ComercioExterior:Destinatario:NumRegIdTrib
      $patron_rfc = $pais->patron_rfc;
      if ($patron_rfc == '')
        $patron_rfc = '.{6,40}';
      if ($patron_rfc != '' && isset($inputs['comercioExterior']['Destinatario']['NumRegIdTrib']{0})) {
        if (preg_match("/^{$patron_rfc}$/u", $inputs['comercioExterior']['Destinatario']['NumRegIdTrib']) !== 1) {
          $this->form_validation->set_message('comercio_exterior_check', "El Num Reg Id Trib del destinatario no es valido el formato.");
          return false;
        }
      }

      $num = $this->db->query("SELECT * FROM otros.c_estados WHERE c_pais = '{$pais->c_pais}'")->result();
      if (count($num) > 0) {
        $num = $this->db->query("SELECT * FROM otros.c_estados WHERE c_pais = '{$pais->c_pais}' AND c_estado = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['Estado']}'")->result();
        if (count($num) === 0) {
          $this->form_validation->set_message('comercio_exterior_check', "El campo estado del destinatario tiene que ser un valor del catalogo, selecciona del catalogo el estado.");
          return false;
        }
      }

      if (isset($inputs['comercioExterior']['Destinatario']['Domicilio']['Municipio']{0}) && $pais->c_pais == 'MEX') {
        $num = $this->db->query("SELECT * FROM otros.c_municipios WHERE c_estado = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['Estado']}' AND c_municipio = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['Municipio']}'")->result();
        if (count($num) === 0) {
          $this->form_validation->set_message('comercio_exterior_check', "El campo municipio del destinatario tiene que ser un valor del catalogo, selecciona del catalogo el municipio.");
          return false;
        }
      }
      if (isset($inputs['comercioExterior']['Destinatario']['Domicilio']['Localidad']{0}) && $pais->c_pais == 'MEX') {
        $num = $this->db->query("SELECT * FROM otros.c_localidades WHERE c_estado = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['Estado']}' AND c_localidad = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['Localidad']}'")->result();
        if (count($num) === 0) {
          $this->form_validation->set_message('comercio_exterior_check', "El campo localidad del destinatario tiene que ser un valor del catalogo, selecciona del catalogo el localidad.");
          return false;
        }
      }
      if (isset($inputs['comercioExterior']['Destinatario']['Domicilio']['CodigoPostal']{0})) {
        if ($pais->c_pais == 'MEX') {
          $query = "SELECT * FROM otros.c_cps WHERE c_estado = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['Estado']}'
                      AND c_municipio = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['Municipio']}'
                      AND c_cp = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['CodigoPostal']}'";
          if (isset($inputs['comercioExterior']['Destinatario']['Domicilio']['Localidad']{0}))
            $query .= " AND c_localidad = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['Localidad']}'";
          $num = $this->db->query($query);
          if (count($num) === 0) {
            $this->form_validation->set_message('comercio_exterior_check', "El campo codigo postal del destinatario tiene que ser un valor del catalogo, selecciona del catalogo el codigo postal.");
            return false;
          }
        } elseif ($pais->c_pais != 'MEX') {
          $patron_cp = $pais->patron_cp;
          if ($patron_cp == '')
            $patron_cp = '.{1,12}';
          if (preg_match("/^{$patron_cp}$/u", $inputs['comercioExterior']['Destinatario']['Domicilio']['CodigoPostal']) !== 1) {
            $this->form_validation->set_message('comercio_exterior_check', "El formato del campo codigo postal del destinatario no es valido.");
            return false;
          }
        }
      }
      if (isset($inputs['comercioExterior']['Destinatario']['Domicilio']['Colonia']{0}) && $pais->c_pais == 'MEX' &&
          strlen($inputs['comercioExterior']['Destinatario']['Domicilio']['Colonia']) != 4) {
        $num = $this->db->query("SELECT * FROM otros.c_colonias WHERE c_cp = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['CodigoPostal']}' AND c_colonia = '{$inputs['comercioExterior']['Destinatario']['Domicilio']['Colonia']}'")->result();
        if (count($num) === 0) {
          $this->form_validation->set_message('comercio_exterior_check', "El campo colonia del destinatario tiene que ser un valor del catalogo, selecciona del catalogo la colonia.");
          return false;
        }
      }
    }

    // cce:ComercioExterior:Mercancias:Mercancia
    if (isset($inputs['prod_ddescripcion']) && count($inputs['prod_ddescripcion']) > 0) {
      $this->load->model('cunidadesmedida_model');
      foreach ($inputs['prod_ddescripcion'] as $key => $concepto) {
        if (!isset($inputs['no_identificacion'][$key]{0})) {
          $this->form_validation->set_message('comercio_exterior_check', "El campo No Identificacion del producto '{$concepto}' es requerido.");
          return false;
        }
        $res_val = $this->validaMercanciasCE($key, $inputs);
        if (!$res_val['status']) {
          $this->form_validation->set_message('comercio_exterior_check', $res_val['msg']);
          return $res_val['status'];
        }
      }
    }

    return true;
  }
  private function validaMercanciasCE($key_search, &$inputs)
  {
    $response = ['status' => true, 'msg' => ""];
    $existe_producto = false;
    $suma_fraccion_aran = 0; // fraccion 98010001
    $count_mercancias = 0;
    if (isset($inputs['comercioExterior']['Mercancias']) && count($inputs['comercioExterior']['Mercancias']) > 0)
    {
      foreach ($inputs['comercioExterior']['Mercancias']['NoIdentificacion'] as $key => $no_ident) {
        if ($no_ident == $inputs['no_identificacion'][$key_search]) {
          $existe_producto = true;
          if (!isset($inputs['comercioExterior']['Mercancias']['CantidadAduana'][$key]{0})) {
            if (preg_match("/^[0-9]{1,14}(.([0-9]{1,3}))?$/u", $inputs['prod_dcantidad'][$key_search]) !== 1 || $inputs['prod_dcantidad'][$key_search] < 0.001) {
              $response = ['status' => false, 'msg' => "El campo Cantidad del producto '{$inputs['prod_ddescripcion'][$key_search]}' debe ser mayor que 0.001 y cumplir con el formato numerico."];
              break;
            }

            $unidades_medida = $this->cunidadesmedida_model->getCE();
            if (!isset($unidades_medida[$inputs['prod_dmedida'][$key_search]])) {
              $response = ['status' => false, 'msg' => "El campo Unidad del producto '{$inputs['prod_ddescripcion'][$key_search]}' debe tener un valor del catálogo Unidad Comercio Exterior."];
              break;
            }

            if (preg_match("/^[0-9]{1,14}(.([0-9]{1,3}))?$/u", $inputs['prod_dpreciou'][$key_search]) !== 1 || $inputs['prod_dpreciou'][$key_search] < 0.001) {
              $response = ['status' => false, 'msg' => "El campo Precio unitario del producto '{$inputs['prod_ddescripcion'][$key_search]}' debe ser mayor que 0.001 y cumplir con el formato numerico."];
              break;
            }
          }

          if (isset($inputs['comercioExterior']['Mercancias']['FraccionArancelaria'][$key]{0}) && ($inputs['prod_dmedida'][$key_search] == 99 || $inputs['comercioExterior']['Mercancias']['UnidadAduana'][$key] == 99)) {
            $response = ['status' => false, 'msg' => "El campo Fraccion Arancelaria de la mercancia '{$no_ident}' no debe existir."];
            break;
          }

          if ($inputs['comercioExterior']['Mercancias']['FraccionArancelaria'][$key] == '98010001') {
            $suma_fraccion_aran += floatval($inputs['comercioExterior']['Mercancias']['ValorDolares'][$key]);
          }

          if (isset($inputs['comercioExterior']['Mercancias']['CantidadAduana'][$key]{0}) ||
              isset($inputs['comercioExterior']['Mercancias']['UnidadAduana'][$key]{0}) ||
              isset($inputs['comercioExterior']['Mercancias']['ValorUnitarioAduana'][$key]{0})) {
            if (!isset($inputs['comercioExterior']['Mercancias']['CantidadAduana'][$key]{0}) &&
                !isset($inputs['comercioExterior']['Mercancias']['UnidadAduana'][$key]{0}) &&
                !isset($inputs['comercioExterior']['Mercancias']['ValorUnitarioAduana'][$key]{0})) {
              $count_mercancias++;
            }
          }
          // if (isset($inputs['comercioExterior']['Mercancias']['UnidadAduana'][$key]{0}) && $inputs['comercioExterior']['Mercancias']['UnidadAduana'][$key] != 99) {
          //   if (isset($inputs['comercioExterior']['Mercancias']['ValorUnitarioAduana'][$key]{0}) && floatval($inputs['comercioExterior']['Mercancias']['ValorUnitarioAduana'][$key]) > 0) {
          //     $response = ['status' => false, 'msg' => "El Valor Unitario Aduana de la mercancia '{$no_ident}' no puede ser mayor que 0, ya que la Unidad Aduana es diferente de servicio."];
          //     break;
          //   }
          // }

          if (isset($inputs['comercioExterior']['Mercancias']['CantidadAduana'][$key]{0}))
          {
            $producto_merca = round(floatval($inputs['comercioExterior']['Mercancias']['CantidadAduana'][$key])*floatval($inputs['comercioExterior']['Mercancias']['ValorUnitarioAduana'][$key]), 2);
            if ($producto_merca != $inputs['comercioExterior']['Mercancias']['ValorDolares'][$key] && $inputs['comercioExterior']['Mercancias']['ValorDolares'][$key] != 1) {
              $response = ['status' => false, 'msg' => "El Valor Dolares de la mercancia '{$no_ident}' no es un valor valido, tiene que ser el producto de Cantidad Aduana por Valor Unitario Aduana."];
              break;
            }
          } else {
            // if ($inputs['comercioExterior']['Mercancias']['UnidadAduana'][$key] == 99 || $inputs['unidad'][$key_search] == 99) {
            //   $response = ['status' => false, 'msg' => "El Valor Dolares de la mercancia '{$no_ident}' no es un valor valido, tiene que ser el producto de Cantidad por Valor Unitario por Tipo Cambio entre Tipo Cambio USD."];
            //   break;
            // }
            $tipo_cambio = (isset($inputs['comercioExterior']['tipocambio_USD']{0}) && $inputs['comercioExterior']['tipocambio_USD'] > 0 ? $inputs['comercioExterior']['tipocambio_USD']: 1);
            $producto_merca = floatval($inputs['prod_dpreciou'][$key_search])*floatval($inputs['prod_dcantidad'][$key_search])*$inputs['tipoCambio']/$tipo_cambio;
            if ($producto_merca != $inputs['comercioExterior']['Mercancias']['ValorDolares'][$key] || $inputs['comercioExterior']['Mercancias']['ValorDolares'][$key] != 1) {
              $response = ['status' => false, 'msg' => "El Valor Dolares de la mercancia '{$no_ident}' no es un valor valido, tiene que ser el producto de Cantidad por Valor Unitario por Tipo Cambio entre Tipo Cambio USD."];
              break;
            }
          }
        }
      }
    }

    if (!$existe_producto) {
      $response = ['status' => false, 'msg' => "El campo No Identificacion del producto '{$inputs['prod_ddescripcion'][$key_search]}' no coincide con ningun producto de comercio exterior mercancías, el No Identificacion tiene que ser igual."];
    }

    if ($suma_fraccion_aran > 0) {
      $suma_fraccion_aran = $suma_fraccion_aran*$inputs['tipoCambio'];
      if ($inputs['total_descuento'] < $suma_fraccion_aran) {
        $response = ['status' => false, 'msg' => "La suma del campo Valor Dolares con Fraccion Arancelaria 98010001 no debe ser menor al campo Descuento del comprobante."];
      }
    }

    if ($count_mercancias > 0) {
      $response = ['status' => false, 'msg' => "Los campos Cantidad Aduana, Unidad Aduana y Valor Unitario Aduana, son requeridos para todas las mercancias si alguno de estos existe."];
    }

    return $response;
  }
  public function validateTNumCertificadoOrigen($value)
  {
    if (trim($value) !== '')
      return preg_match("/[a-f0-9A-F]{8}-[a-f0-9A-F]{4}-[a-f0-9A-F]{4}-[a-f0-9A-F]{4}-[a-f0-9A-F]{12}|[A-Za-z0-9,#,+,%,(&) ]{6,40}/u", $value);
    return true;
  }
  public function validateTImporte($value)
  {
    if (trim($value) !== '') {
      $decimales = strlen(substr(strrchr($value, "."), 1));
      if ($decimales <= 6)
        return true;
      return false;
    }
    return true;
  }
  // Valida la estructura de los rfc en base al tipo definido en el
  // anexo 20.
  public function validateTRfcLite($rfc)
  {
    // Determina la cantidad de caracteres a validar al inicio ya que para
    // personas fisicas son 4 y morales 3.
    if (trim($rfc) !== '')
    {
      $x = mb_strlen($rfc, 'UTF-8') === 12 ? '3' : '4';
      if (preg_match("/^[A-Z,Ñ,&]{{$x}}[0-9]{2}[0-1][0-9][0-3][0-9][A-Z,0-9][A-Z,0-9][0-9,A-Z]/u", $rfc))
        return true;
      else
        return false;
    }
    return true;
  }
  public function validateTCurpLite($value)
  {
    if (trim($value) !== '')
    {
      return preg_match("/[A-Z][A,E,I,O,U,X][A-Z]{2}[0-9]{2}[0-1][0-9][0-3][0-9][M,H][A-Z]{2}[B,C,D,F,G,H,J,K,L,M,N,Ñ,P,Q,R,S,T,V,W,X,Y,Z]{3}[0-9,A-Z][0-9]/u", $value);
    }

    return true;
  }

   /*
   |-------------------------------------------------------------------------
   |  SERIES Y FOLIOS
   |-------------------------------------------------------------------------
   */

  public function check_max_decimales($str)
  {
    $exp = explode('.', $str);

    if (count($exp) > 1)
    {
      if (mb_strlen($exp[1]) > 6)
      {
        $this->form_validation->set_message('check_max_decimales', 'Verifique que ninguna cantidad tenga mas de 6 decimales.');
        return false;
      }
    }

    return true;
  }

   /*
   |-------------------------------------------------------------------------
   |  SERIES Y FOLIOS
   |-------------------------------------------------------------------------
   */

  /**
   * Permite administrar los series y folios para la facturacion.
   *
   * @return void
   */
  public function series_folios()
  {
    // $this->carabiner->css(array(
    //     array('general/forms.css','screen'),
    //     array('general/tables.css','screen')
    // ));

    $this->load->library('pagination');
    $this->load->model('facturacion_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Administrar Series y Folios');

    $params['datos_s'] = $this->facturacion_model->getSeriesFolios();

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/series_folios/admin',$params);
    $this->load->view('panel/footer',$params);
  }

  public function agregar_serie_folio()
  {
    $this->carabiner->js(array(
        array('panel/facturacion/series_folios/frm_addmod.js')
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']  = array('titulo' => 'Agregar Series y Folios');

    $this->load->model('facturacion_model');

    $this->configAddSerieFolio();
    if($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2,preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $model_resp = $this->facturacion_model->addSerieFolio();

      if($model_resp['passes'])
        redirect(base_url('panel/facturacion/agregar_serie_folio/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
    }

    if(isset($_GET['msg']{0}))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/series_folios/agregar',$params);
    $this->load->view('panel/footer',$params);
  }

  public function modificar_serie_folio()
  {
    if(isset($_GET['id']{0})){
      $this->carabiner->js(array(
          array('panel/facturacion/series_folios/frm_addmod.js')
      ));

      $this->load->model('facturacion_model');
      $this->configAddSerieFolio('edit');

      if($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2,preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $model_resp = $this->facturacion_model->editSerieFolio($_GET['id']);

        if($model_resp['passes'])
          $params['frm_errors'] = $this->showMsgs(6);
      }

      $params['info_empleado']  = $this->info_empleado['info'];
      $params['opcmenu_active']   = 'Ventas'; //activa la opcion del menu
      $params['seo']['titulo']  = 'Modificar Serie y Folio';

      $params['serie_info'] = $this->facturacion_model->getInfoSerieFolio($_GET['id']);

      if(isset($_GET['msg']{0}))
          $params['frm_errors'] = $this->showMsgs($_GET['msg']);

        $this->load->view('panel/header',$params);
        $this->load->view('panel/general/menu',$params);
        $this->load->view('panel/facturacion/series_folios/modificar',$params);
        $this->load->view('panel/footer',$params);
    }
    else
      redirect(base_url('panel/facturacion/index_serie_folios/').MyString::getVarsLink(array('msg')).'&msg=1');
  }

  /**
   * obtiene el folio siguiente de la serie seleccionada
   */
  public function get_folio()
  {
    if(isset($_GET['serie']) && isset($_GET['ide']))
    {
      $this->load->model('facturacion_model');
      $res = $this->facturacion_model->getFolioSerie($_GET['serie'], $_GET['ide'], "es_nota_credito = 'f'");

      $param =  $this->showMsgs(2, $res[1]);
      $param['data'] = $res[0];
      echo json_encode($param);
    }
  }

  /**
   * obtiene el folio siguiente de la serie seleccionada
   */
  public function get_series()
  {
    if(isset($_GET['ide']))
    {
      $tipo = isset($_GET['tipof'])? $_GET['tipof']: 'f';
      $this->load->model('facturacion_model');
      $res = $this->facturacion_model->get_series($_GET['ide'], $tipo);
      echo json_encode($res);
    }
  }

  private function configAddSerieFolio($tipo='add')
  {
    $this->load->library('form_validation');

    $rules = array(
            array('field' => 'fid_empresa',
                'label' => 'Empresa',
                'rules' => 'required|numeric'),
            array('field' => 'fempresa',
                'label' => 'Empresa',
                'rules' => 'min_length[1]'),

            array('field' => 'fno_aprobacion',
                'label' => 'No Aprobación',
                'rules' => 'required|numeric'),
            array('field' => 'ffolio_inicio',
                'label' => 'Folio Inicio',
                'rules' => 'required|is_natural'),
            array('field' => 'ffolio_fin',
                'label' => 'Folio Fin',
                'rules' => 'required|is_natural'),
            array('field' => 'fano_aprobacion',
                'label' => 'Fecha Aprobación',
                'rules' => 'required|max_length[10]|callback_isValidDate'),
            array('field' => 'fleyenda',
                'label' => 'Leyenda',
                'rules' => ''),
            array('field' => 'fleyenda1',
                'label' => 'Leyenda 1',
                'rules' => ''),
            array('field' => 'fleyenda2',
                'label' => 'Leyenda 2',
                'rules' => ''),
            array('field' => 'fnota_credito',
                'label' => 'Nota de Credito',
                'rules' => ''),
        );

    if($tipo=='add')
    {
      // if(isset($_FILES['durl_img']))
      //   if($_FILES['durl_img']['name']!='')
      //     $_POST['durl_img'] = 'ok';

      $rules[] = array('field' => 'fserie',
                       'label' => 'Serie',
                       'rules' => 'max_lenght[30]|callback_isValidSerie[add]');
      // $rules[] = array('field'  => 'durl_img',
      //     'label' => 'Imagen',
      //     'rules' => 'required');
    }

    if($tipo=='edit'){
      $rules[] = array('field'  => 'fserie',
                        'label' => 'Serie',
                        'rules' => 'max_lenght[30]|callback_isValidSerie[edit]');
    }

    $this->form_validation->set_rules($rules);
  }

  /**
   * Form_validation: Valida si la Serie ya existe
   */
  public function isValidSerie($str, $tipo)
  {
    $str = $str=='' ? '' : $str;

    if ($str != '')
    {
      if($tipo=='add'){
        if($this->facturacion_model->exist('facturacion_series_folios',
            array('serie' => mb_strtoupper($str), 'id_empresa' => $this->input->post('fid_empresa')) )){
          $this->form_validation->set_message('isValidSerie', 'El campo %s ya existe');
          return false;
        }
        return true;
      }
      else{
        $row = $this->facturacion_model->exist('facturacion_series_folios',
          array('serie' => mb_strtoupper($str), 'id_empresa' => $this->input->post('fid_empresa')), true);

        if($row!=FALSE){
          if($row->id_serie_folio == $_GET['id'])
            return true;
          else{
            $this->form_validation->set_message('isValidSerie', 'El campo %s ya existe');
            return false;
          }
        }return true;
      }
    }
  }

  /*
   |-------------------------------------------------------------------------
   |  AJAX
   |-------------------------------------------------------------------------
   */

   /**
    * Obtiene las clasificaciones por ajax.
    *
    * @return JSON
    */
   public function ajax_get_clasificaciones()
   {
      $this->load->model('clasificaciones_model');

      echo json_encode($this->clasificaciones_model->ajaxClasificaciones(100));
   }

  /**
    * Obtiene listado de empresas por ajax
    */
  public function ajax_get_empresas_fac(){
    $this->load->model('facturacion_model');
    $params = $this->facturacion_model->getFacEmpresasAjax();

    echo json_encode($params);
  }

  /**
    * Obtiene listado de los clientes que tienen RFC por ajax.
    */
  public function ajax_get_clientes(){
    $this->load->model('clientes_model');
    $params = $this->clientes_model->getClientesAjax(" AND c.rfc <> ''");

    echo json_encode($params);
  }

  /**
    * Obtiene listado de los clientes que tienen RFC por ajax.
    */
  public function ajax_get_clientes_vr(){
    $this->load->model('clientes_model');
    $params = $this->clientes_model->getClientesAjax();

    echo json_encode($params);
  }

  /**
    * Obtiene listado de los clientes que tienen RFC por ajax.
    */
  public function ajax_get_pallet_folio()
  {
    $this->load->model('rastreabilidad_pallets_model');

    $pallet = $this->db->select('id_pallet')->from('rastria_pallets')->where('folio', $_GET['folio'])->get();

    if ($pallet->num_rows() > 0)
    {
      $pallet = $pallet->row();

      $existentes = $this->db->select('fp.id_factura')
        ->from('facturacion_pallets as fp')
        ->join('facturacion as f', 'f.id_factura = fp.id_factura', 'inner')
        ->where('fp.id_pallet', $pallet->id_pallet)
        ->where_in('f.status', array('p', 'pa'))
        ->get();

      if ($existentes->num_rows() > 0)
      {
        echo json_encode(false);
      }
      else
      {
        $info = $this->rastreabilidad_pallets_model->getInfoPallet($pallet->id_pallet);

        echo json_encode($info);
      }
    }
    else
    {
      echo json_encode(false);
    }
  }

  /**
    * Obtiene las unidades por ajax.
    *
    * @return JSON
    */
   public function ajax_get_unidades()
   {
    $unidades = $this->db->select('*')->from('unidades')->where('status', 't')->order_by('nombre')->get()->result();

    echo json_encode($unidades);
   }

   /**
    * Obtiene listado de los pallets que tiene un cliente.
    */
  public function ajax_get_pallets_cliente()
  {
    $this->load->model('facturacion_model');

    $response = $this->facturacion_model->palletsCliente($_GET['id']);

    echo json_encode($response);
  }

  /**
    * Liga las remisiones a una factura
    */
  public function ajax_ligar_remisiones()
  {
    $this->load->model('facturacion_model');

    $response = $this->facturacion_model->addPallestRemisiones($_POST['id_factura'], true, true);

    echo json_encode($response);
  }

  public function ajax_remove_remision_fact()
  {
    $this->load->model('facturacion_model');

    $response = $this->facturacion_model->removePallestRemisiones($_GET['id_remision'], $_GET['id_factura']);

    echo json_encode($response);
  }

  /*
   |-------------------------------------------------------------------------
   |  REPORTES
   |-------------------------------------------------------------------------
   */
  public function rvc()
  {
    $this->carabiner->js(array(
      array('panel/facturacion/admin.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte Ventas Cliente');

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/rvc',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rvc_pdf()
  {
    $this->load->model('facturacion_model');
    $this->facturacion_model->rvc_pdf();
  }

  public function rvp()
  {
    $this->carabiner->js(array(
      array('panel/facturacion/admin.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte Ventas Producto');

    $query = $this->db->query("SELECT id_familia, nombre
                               FROM productos_familias");

    $params['familias'] = $query->result();

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/rvp',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rvp_pdf()
  {
    $this->load->model('facturacion_model');
    $this->facturacion_model->rvp_pdf();
  }

  public function prodfact()
  {
    $this->carabiner->js(array(
      array('panel/facturacion/admin.js'),
      array('panel/facturacion/rep_productos_facturados.js'),
    ));

    $this->load->model('empresas_model');
    $this->load->model('facturacion_model');
    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte Productos Facturados');

    $params['series'] = $this->facturacion_model->get_series($params['empresa']->id_empresa, 'r');

    $this->load->view('panel/header',$params);
    // $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/rpf',$params);
    $this->load->view('panel/footer',$params);
  }
  public function prodfact_pdf()
  {
    $this->load->model('facturacion_model');
    $this->facturacion_model->prodfact_pdf();
  }
  public function prodfact_xls()
  {
    $this->load->model('facturacion_model');
    $this->facturacion_model->prodfact_xls();
  }

  public function prodfact2()
  {
    $this->carabiner->js(array(
      array('panel/facturacion/admin.js'),
      array('panel/facturacion/rep_productos_facturados.js'),
    ));

    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Reporte Productos Facturados con Kilos');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    $this->load->view('panel/header',$params);
    // $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/rpf2',$params);
    $this->load->view('panel/footer',$params);
  }
  public function prodfact2_pdf()
  {
    $this->load->model('facturacion2_model');
    $this->facturacion2_model->prodfact2_pdf();
  }
  public function prodfact2_xls()
  {
    $this->load->model('facturacion2_model');
    $this->facturacion2_model->prodfact2_xls();
  }

  public function ventasAcumulado()
  {
    $this->carabiner->js(array(
      array('panel/facturacion/admin.js'),
      array('panel/facturacion/rep_productos_facturados.js'),
    ));

    $this->load->model('empresas_model');
    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte de ventas acumulado');


    $this->load->view('panel/header',$params);
    // $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/rpt_ventas_acumulado',$params);
    $this->load->view('panel/footer',$params);
  }
  public function ventasAcumulado_pdf()
  {
    $this->load->model('facturacion2_model');
    $this->facturacion2_model->ventasAcumulado_pdf();
  }
  public function ventasAcumulado_xls()
  {
    $this->load->model('facturacion2_model');
    $this->facturacion2_model->ventasAcumulado_xls();
  }

  public function rventasc()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/facturacion/rpt_ventas.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Ventas por Cliente');

    $params['empresa']  = $this->empresas_model->getDefaultEmpresa();
    $params['empresas'] = $this->empresas_model->getEmpresas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/facturacion/rventasc',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rventasc_pdf(){
    $this->load->model('facturacion_model');
    $this->facturacion_model->getRVentascPdf();
  }
  public function rventasc_xls(){
    $this->load->model('facturacion_model');
    $this->facturacion_model->getRVentascXls();
  }

  public function rnotas_cred()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/facturacion/rpt_ventas.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Ventas por Cliente');

    $params['empresa']  = $this->empresas_model->getDefaultEmpresa();
    $params['empresas'] = $this->empresas_model->getEmpresas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/facturacion/rnotas_cred',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rnotas_cred_pdf(){
    $this->load->model('ventas_model');
    $this->ventas_model->getRNotasCredPdf();
  }
  public function rnotas_cred_xls(){
    $this->load->model('ventas_model');
    $this->ventas_model->getRNotasCredXls();
  }

  public function rventasc_detalle_pdf()
  {
    $this->load->model('facturacion_model');
    $this->facturacion_model->getRVentasDetallePdf();
  }

  public function rpt_remisiones_detalle()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/facturacion/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte Remisiones Facturadas y No Facturadas');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/facturacion/rpt_remisiones_detalle',$params);
    $this->load->view('panel/footer',$params);
  }

  public function remisiones_detalle_pdf()
  {
    $this->load->model('facturacion_model');

    $_GET = array_merge(array(
      'ffecha1' => isset($_GET['ffecha1']) ?: date('Y-m-d'),
      'ffecha2' => isset($_GET['ffecha2']) ?: date('Y-m-d'),
      'did_empresa' => isset($_GET['did_empresa']) ?:false,
      'ffacturadas' => isset($_GET['ffacturadas']) ?:false,
    ), $_GET);

    $this->facturacion_model->remisionesDetallePdf($_GET);
  }
  public function remisiones_detalle_xls()
  {
    $this->load->model('facturacion_model');

    $_GET = array_merge(array(
      'ffecha1' => isset($_GET['ffecha1']) ?: date('Y-m-d'),
      'ffecha2' => isset($_GET['ffecha2']) ?: date('Y-m-d'),
      'did_empresa' => isset($_GET['did_empresa']) ?:false,
      'ffacturadas' => isset($_GET['ffacturadas']) ?:false,
    ), $_GET);

    $this->facturacion_model->remisionesDetalleXls($_GET);
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
      case 4:
        $txt = 'La Factura se cancelo correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La Serie y Folio se agregaron correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'La Serie y Folio se modifico correctamente.';
        $icono = 'success';
        break;
      case 7:
        $txt = 'La Factura se pagó correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = 'El cliente no cuenta con un email para enviarle los documentos.';
        $icono = 'error';
        break;
      case 9:
        $txt = 'El email no se pudo enviar, intentelo nuevamente.';
        $icono = 'error';
        break;
      case 10:
        $txt = 'El email se envio correctamente!';
        $icono = 'success';
        break;
      case 11:
        $txt = 'Factura guardada!';
        $icono = 'success';
        break;

       case 97:
        $txt = 'La Factura se timbro correctamente.';
        $icono = 'success';
        break;
      case 98:
        $txt = 'Ocurrio un error al intentar timbrar la factura, verifique los datos fiscales de la empresa y/o cliente.';
        $icono = 'success';
        break;
      case 99:
        $txt = 'Error Timbrado: Internet Desconectado. Verifique su conexión para realizar el timbrado.';
        $icono = 'error';
        break;
      case 100:
        $txt = $msg;
        $icono = 'success';
        break;
      case 101:
        $txt = 'El timbrado ya se ha realizado correctamente!';
        $icono = 'success';
        break;
      case 102:
        $txt = 'El timbrado aun esta pendiente.';
        $icono = 'error';
        break;
      case 201:
        $txt = 'La factura se cancelo correctamente.';
        $icono = 'success';
        break;
      case 202:
        $txt = 'La factura se cancelo correctamente.';
        $icono = 'success';
        break;
      case 205:
        $txt = 'Error al intentar cancelar: UUID No existente.';
        $icono = 'error';
        break;
      case 708:
        $txt = 'No se pudo conectar al SAT para realizar la cancelación de la factura, intentelo mas tarde.';
        $icono = 'error';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

  public function nomina()
  {
    $_POST = array(
      "dempresa"=> "ASOCIACION DE AGRICULTORES DEL DISTRITO DE RIEGO 004 DON MARTIN",
      "did_empresa"=> "2",
      "dversion"=> "3.2",
      "dcer_caduca"=> "2017-10-26",
      "dno_certificado"=> "20001000000200000293",
      "dserie"=> "G",
      "dfolio"=> "4",
      "dano_aprobacion"=> "2013-07-02",
      "dcliente"=> "MARIA GUADALUPE OCHOA APARICIO",
      "did_cliente"=> "179",
      "dcliente_rfc"=> "OOAG791212H41",
      "dcliente_domici"=> "SAN ANGEL S/N E/ CALZADA H COLEGIO MILITAR Y LAZARO CARDENA #., SAN BENITO",
      "dcliente_ciudad"=> "CULIACAN, CP: 80246",
      "dobservaciones"=> "",
      "dfecha"=> "2013-12-18T16:39",
      "dno_aprobacion"=> "123456",
      "dtipo_comprobante"=> "ingreso",
      "dforma_pago"=> "Pago en una sola exhibición",
      "dforma_pago_parcialidad"=> "Parcialidad 1 de X",
      "dmetodo_pago"=> "no identificado",
      "dmetodo_pago_digitos"=> "No identificado",
      "dcondicion_pago"=> "co",
      "dplazo_credito"=> "15",
      "timbrar"=> "",
      "prod_ddescripcion" => array(
        "Pago de nomina"
      ),
      "prod_did_prod"=> array(
        ""
      ),
      "pallets_id" => array(
        ""
      ),
      "id_unidad_rendimiento"=> array(
        ""
      ),
      "prod_dmedida"=>array(
        "Servicio"
      ),
      "prod_dmedida_id"=>array(
        "10"
      ),
      "prod_dcantidad"=>array(
        "1"
      ),
      "prod_dcajas"=>array(
        "0"
      ),
      "prod_dkilos"=>array(
        "0"
      ),
      "prod_dpreciou"=>array(
        "1029.15"
      ),
      "diva"=> "0",
      "prod_diva_porcent"=>array(
        "0"
      ),
      "prod_diva_total"=>array(
        "0"
      ),
      "dreten_iva"=> "0",
      "prod_dreten_iva_total"=>array(
        "0"
      ),
      "prod_dreten_iva_porcent"=>array(
        "0"
      ),
      "prod_importe"=>array(
        "1029.15"
      ),
      "dttotal_letra"=> "TRES MIL PESOS 00/100 M.N.",
      "total_importe"=> "1029.15",
      "total_descuento"=> "16.67",
      "total_subtotal"=> "1029.15",
      "total_iva"=> "0",
      "total_retiva"=> "0",
      "total_totfac"=> "1012.48",
    );
    // echo "<pre>";
    //   var_dump($_POST);
    // echo "</pre>";exit;

    $this->load->model('facturacion_model');
    $respons = $this->facturacion_model->addFactura();

    echo "<pre>";
      var_dump($respons);
    echo "</pre>";exit;
  }
}
?>