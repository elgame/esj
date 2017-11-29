<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ventas extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'ventas/get_folio/',
    'ventas/rventasr_pdf/',
    'ventas/rventasr_xls/',
    'ventas/rpsaldo_vencido_pdf/',
    'ventas/rpsaldo_vencido_xls/',
    'ventas/rventas_nc_pdf/',
    'ventas/rventas_nc_xls/',
    'ventas/imprimir_tk/',

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
      array('general/supermodal.js'),
      array('panel/ventas_remision/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('ventas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Ventas de remisión');

    $params['datos_s'] = $this->ventas_model->getVentas(40, " AND f.id_nc IS NULL");

    $params['fecha']  = date("Y-m-d");
    $params['method']  = '';

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/ventas_remision/admin',$params);
    $this->load->view('panel/footer',$params);
  }

  public function notas_credito()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('panel/ventas_remision/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('ventas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Notas de credito de Ventas de remisión');

    $params['datos_s'] = $this->ventas_model->getVentas(40, " AND f.id_nc IS NOT NULL");

    $params['fecha']  = date("Y-m-d");
    $params['method']  = 'notas_credito';

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/ventas_remision/admin',$params);
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
      array('general/keyjump.js'),
      array('general/util.js'),
      array('panel/ventas_remision/frm_addmod.js'),
      array('panel/facturacion/frm_otros.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Agregar remision');
    $params['pagar_ordent']   = false;

    $this->load->library('cfdi');
    $this->load->model('facturacion_model');
    $this->load->model('ventas_model');
    $this->load->model('empresas_model');

    $this->configAddModFactura();
    if($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {

      if (isset($_GET['id_nr']))
        $respons = $this->ventas_model->updateNotaVenta($_GET['id_nr']);
      else
        $respons = $this->ventas_model->addNotaVenta();


      if($respons['passes'])
      {
        if(isset($_POST['id_nrc']{0}))
          redirect(base_url('panel/ventas/?msg=10'));
        elseif(isset($_POST['guardar_imp']))
          redirect(base_url('panel/ventas/agregar/?msg=11&imprimir_tk='.$respons['id_venta']));
        else
          redirect(base_url('panel/documentos/agregar/?msg=3&id='.$respons['id_venta']));
      }
      else
        $params['frm_errors'] = $this->showMsgs(2, $respons['msg']);
    }

    // Parametros por default.
    $params['series'] = $this->facturacion_model->getSeriesFolios(100);
    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['getId'] = '';

    // Parametros por default.
    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();
    // $this->db
    //   ->select("e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org")
    //   ->from("empresas AS e")
    //   ->where("e.predeterminado", "t")
    //   ->get()
    //   ->row();

    // Obtiene el numero de certificado de la empresa predeterminada.
    $params['no_certificado'] = $this->cfdi->obtenNoCertificado($params['empresa_default']->cer_org);

    $params['unidades'] = $this->db->select('*')
      ->from('unidades')
      ->where('status', 't')
      ->order_by('nombre')
      ->get()->result();

    $params['getId'] = '';
    if (isset($_GET['id_nr']) || isset($_GET['id_nrc']))
    {
      $params['borrador'] = $this->ventas_model->getInfoVenta( (isset($_GET['id_nr'])? $_GET['id_nr']: $_GET['id_nrc']), false, true );
      if(isset($_GET['id_nr']))
        $params['fecha']    = isset($params['borrador']) ? $params['borrador']['info']->fechaT : $params['fecha'];
      if(isset($_GET['id_nrc']))
      {
        $params['borrador']['info']->serie         = '';
        $params['borrador']['info']->folio         = '';
        $params['borrador']['info']->subtotal      = '';
        $params['borrador']['info']->subtotal      = '';
        $params['borrador']['info']->importe_iva   = '';
        $params['borrador']['info']->retencion_iva = '';
        $params['borrador']['info']->total         = '';
        $params['seo']['titulo'] = 'Agregar Nota de credito';
      }
    } elseif (isset($_GET['id_vd']))
    {
      // Si es una venta del dia la que se quiere facturar carga sus datos.
      $this->load->model('ventas_dia_model');
      $params['borrador'] = $this->ventas_dia_model->getInfoFactura($_GET['id_vd']);
      $params['borrador']['info']->serie = '';
      $params['borrador']['info']->folio = '';
      $params['getId'] = '?id_vd='.$_GET['id_vd'];
    }

    $metodosPago       = new MetodosPago();
    $formaPago         = new FormaPago();
    $usoCfdi           = new UsoCfdi();
    $tipoDeComprobante = new TipoDeComprobante();
    // $monedas           = new Monedas();

    $params['metodosPago']       = $metodosPago->get()->all();
    $params['formaPago']         = $formaPago->get()->all();
    $params['usoCfdi']           = $usoCfdi->get()->all();
    $params['tipoDeComprobante'] = $tipoDeComprobante->get()->all();
    // $params['monedas']           = $monedas->get()->all();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/ventas_remision/agregar', $params);
    $this->load->view('panel/footer');
  }

  public function pagar()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('ventas_model');
      $this->ventas_model->pagaNotaRemison($_GET['id']);

      redirect(base_url('panel/ventas/?'.String::getVarsLink(array('msg','id')).'&msg=9'));
    }
  }

  public function cancelar()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('ventas_model');
      $this->ventas_model->cancelaNotaRemison($_GET['id']);

      redirect(base_url('panel/ventas/?'.String::getVarsLink(array('msg','id')).'&msg=5'));
    }
  }


  public function rventasr()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/facturacion/rpt_ventas.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Ventas remisiones');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/ventas_remision/rventasc',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rventasr_pdf(){
    $this->load->model('ventas_model');
    $this->ventas_model->getRVentasrPdf();
  }

  public function rventasr_xls()
  {
    $this->load->model('ventas_model');
    $this->ventas_model->getRVentasrXLS();
  }

  /**
   * Reporte de facturas y notas de credito
   * @return [type] [description]
   */
  public function rventas_nc()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/facturacion/rpt_ventas.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Facturas y NC');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/ventas_remision/rptfacturas_nc',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rventas_nc_pdf(){
    $this->load->model('ventas_model');
    $this->ventas_model->getRFacturasNCPdf();
  }
  public function rventas_nc_xls(){
    $this->load->model('ventas_model');
    $this->ventas_model->getRFacturasNCXls();
  }

  /**
   * Reporte de ventas con saldo vencido
   * @return [type] [description]
   */
  public function rpsaldo_vencido()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/facturacion/rpt_ventas.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Ventas Vencidas');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/ventas_remision/rpsaldo_vencido',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpsaldo_vencido_pdf(){
    $this->load->model('ventas_model');
    $this->ventas_model->getRVencidasPdf();
  }
  public function rpsaldo_vencido_xls(){
    $this->load->model('ventas_model');
    $this->ventas_model->getRVencidasXls();
  }

  /**
   * Configura los metodos de agregar y modificar
   *
   * @return void
   */
  private function configAddModFactura($borrador = false)
  {
    $required = 'required';

    // $callback_seriefolio_check = 'callback_seriefolio_check';
    $callback_isValidDate      = 'callback_isValidDate';
    $callback_val_total        = 'callback_val_total';
    $callback_chk_cer_caduca   = 'callback_chk_cer_caduca';
    // if ($borrador)
    // {
    //   // $callback_seriefolio_check = '';
    //   $callback_isValidDate      = '';
    //   $callback_val_total        = '';
    //   $callback_chk_cer_caduca   = '';
    // }

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
              'rules'   => 'required|numeric'),
        array('field'   => 'dano_aprobacion',
              'label'   => 'Fecha de aprobacion',
              'rules'   => 'required|max_length[10]|'.$callback_isValidDate),

        array('field'   => 'dfecha',
              'label'   => 'Fecha',
              'rules'   => 'required|max_length[25]'), //|callback_isValidDate

        array('field'   => 'total_importe',
              'label'   => 'SubTotal1',
              'rules'   => 'numeric'),
        array('field'   => 'total_subtotal',
              'label'   => 'SubTotal',
              'rules'   => 'numeric'),

        array('field'   => 'total_descuento',
              'label'   => 'Descuento',
              'rules'   => $required.'|numeric'),
        array('field'   => 'total_iva',
              'label'   => 'IVA',
              'rules'   => 'numeric'),
        array('field'   => 'total_retiva',
              'label'   => 'Retencion IVA',
              'rules'   => $required.'|numeric'),
        array('field'   => 'total_totfac',
              'label'   => 'Total',
              'rules'   => 'required|numeric|'.$callback_val_total),
        array('field'   => 'dforma_pago',
              'label'   => 'Forma de pago',
              'rules'   => 'required|max_length[80]'),
        array('field'   => 'dmetodo_pago',
              'label'   => 'Metodo de pago',
              'rules'   => 'required|max_length[40]'),
        array('field'   => 'dmetodo_pago_digitos',
              'label'   => 'Ultimos 4 digitos',
              'rules'   => 'max_length[20]'),
        array('field'   => 'dcondicion_pago',
              'label'   => 'Condición de pago',
              'rules'   => 'required|max_length[2]'),

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
              'rules'   => ''),
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
              'rules'   => ''),
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
        array('field'   => 'prod_dmedida[]',
              'label'   => 'prod_dmedida',
              'rules'   => ''),
        array('field'   => 'isCert[]',
              'label'   => 'Certificado',
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
              'rules'   => ''),
        array('field'   => 'dtipo_comprobante',
              'label'   => 'Tipo comproante',
              'rules'   => 'required'),
        array('field'   => 'dobservaciones',
              'label'   => 'Observaciones',
              'rules'   => ''),
    );

    if (isset($_POST['privAddDescripciones']{0}) || isset($_POST['id_nrc']{0})) {
      $rules[] = array('field'   => 'prod_did_prod[]',
                      'label'   => 'prod_did_prod',
                      'rules'   => '');
      $rules[] = array('field'   => 'prod_did_calidad[]',
                      'label'   => 'prod_did_calidad',
                      'rules'   => '');
      $rules[] = array('field'   => 'prod_did_tamanio[]',
                      'label'   => 'prod_did_tamanio',
                      'rules'   => '');
    } else {
      $rules[] = array('field'   => 'prod_did_prod[]',
                    'label'   => 'prod_did_prod',
                    'rules'   => 'required');
      $rules[] = array('field'   => 'prod_did_calidad[]',
                      'label'   => 'prod_did_calidad',
                      'rules'   => 'required');
      $rules[] = array('field'   => 'prod_did_tamanio[]',
                      'label'   => 'prod_did_tamanio',
                      'rules'   => 'required');
    }

    if (isset($_POST['palletsIds']))
    {
      $rules[] = array(
        'field'   => 'palletsIds[]',
        'label'   => 'Pallets',
        'rules'   => 'callback_check_existen_pallets'
      );
    }

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

    $this->form_validation->set_rules($rules);
  }

  /**
   * Verifica que la serie y folio enviados del form no esten asignados a una
   * factura y tambien que esten vigentes.
   *
   * @param string $str
   * @return boolean
   */
  public function seriefolio_check($str){
    if($str != ''){
      $sql = $ms = '';

      $sql = (isset($_GET['id_nr'])? " AND id_factura <> {$_GET['id_nr']}": '');

      $res = $this->db->select('Count(id_factura) AS num')
        ->from('facturacion')
        ->where("folio = ".$str." AND serie = '".$this->input->post('dserie')."' AND id_empresa = ". $this->input->post('did_empresa') ." AND is_factura = 'f' AND status != 'ca' ".$sql)
        ->get();
      $data = $res->row();
      if($data->num > 0){
        $this->form_validation->set_message('seriefolio_check', 'El folio ya esta utilizado por otra Nota.');
        return false;
      }
    }
    return true;
  }

  public function val_total($str){
    if($str <= -1){
      $this->form_validation->set_message('val_total', 'El Total no puede ser menor que -1, verifica los datos ingresados.');
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

  /**
   * Imprime la venta remision
   * @return [type] [description]
   */
  public function imprimir()
  {
    if(isset($_GET['id']{0}))
    {
      $this->load->model('ventas_model');
      if($this->input->get('p') == 'true')
        $this->ventas_model->generaNotaRemisionPdf($_GET['id']);
      else {
        $params['url'] = 'panel/ventas/imprimir/?id='.$_GET['id'].'&p=true';
        $this->load->view('panel/facturacion/print_view', $params);
      }
    }
    else
      redirect(base_url('panel/ventas/?msg=1'));
  }

  public function imprimir_tk()
  {
    if(isset($_GET['id']{0}))
    {
      $this->load->model('ventas_model');
      if($this->input->get('p') == 'true')
        $this->ventas_model->ticketNotaRemisionPdf($_GET['id']);
      else {
        $params['url'] = 'panel/ventas/imprimir_tk/?id='.$_GET['id'].'&p=true';
        $params['autoclose'] = true;
        $this->load->view('panel/facturacion/print_view', $params);
      }
    }
    else
      redirect(base_url('panel/ventas/?msg=1'));
  }

  /**
   * Muestra la vista par el envio de los correo.
   *
   * @return void
   */
  public function enviar_documentos()
  {
    $this->carabiner->js(array(
      array('panel/facturacion/email.js'),
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
   * obtiene el folio siguiente de la serie seleccionada
   */
  public function get_folio()
  {
    if(isset($_GET['ide']))
    {
      $this->load->model('ventas_model');
      $res = $this->ventas_model->getFolio($_GET['ide'], $_GET['serie']);

      $param =  $this->showMsgs(2, $res[1]);
      $param['data'] = $res[0];
      echo json_encode($param);
    }
  }


  /*
   |-------------------------------------------------------------------------
   |  AJAX
   |-------------------------------------------------------------------------
   */


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
        $txt = 'La Nota de remisión se modifico correctamente.';
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