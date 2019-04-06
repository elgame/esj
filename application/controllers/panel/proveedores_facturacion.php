<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class proveedores_facturacion extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'proveedores_facturacion/admin/',
    'proveedores_facturacion/get_folio/',
    'proveedores_facturacion/get_series/',
    'proveedores_facturacion/email/',

    'proveedores_facturacion/rvc_pdf/',
    'proveedores_facturacion/rvp_pdf/',

    'proveedores_facturacion/ajax_get_clasificaciones/',
    'proveedores_facturacion/ajax_get_proveedor_fac/',
    'proveedores_facturacion/ajax_get_empresas/',
    'proveedores_facturacion/ajax_get_ticket/',
    'proveedores_facturacion/get_empresa_default/',

    'proveedores_facturacion/xml/'
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
      array('panel/proveedores/facturacion/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('proveedores_facturacion_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Facturas');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = !empty($_GET['did_empresa'])? $_GET['did_empresa']: $params['empresa']->id_empresa;
    $_GET['ffecha1']    = (isset($_GET['ffecha1']{8})? $_GET['ffecha1']: date("Y-m-d"));
    $params['datos_s'] = $this->proveedores_facturacion_model->getProveedores( $_GET['ffecha1'] );


    // if(isset($_GET['id_mov']{0}))
    //   $params['id_mov'] = $_GET['id_mov'];

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/proveedores/facturacion/saldos',$params);
    $this->load->view('panel/footer', $params);
  }

  public function admin()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('panel/proveedores/facturacion/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('proveedores_facturacion_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo'] = array('titulo' => 'Facturas');

    $params['datos_s'] = $this->proveedores_facturacion_model->getFacturas();

    $_GET['ffecha1']   = (isset($_GET['ffecha1']{8})? $_GET['ffecha1']: date("Y-m-d"));

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/proveedores/facturacion/admin',$params);
    $this->load->view('panel/footer',$params);
  }

  /**
   * Agrega una factura a la bd
   *
   * @return void
   */
  public function agregar()
  {
    $this->carabiner->js(array(
        array('libs/jquery.numeric.js'),
        array('general/keyjump.js'),
        array('general/util.js'),
        array('panel/proveedores/facturacion/frm_addmod.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Proveedores'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Agregar factura');
    $params['pagar_ordent']   = false;

    if(isset($_GET['ordent']{0}))
      $this->asignaOrdenTrabajo($_GET['ordent']);

    $this->load->library('cfdi');
    $this->load->model('proveedores_facturacion_model');

    if ( ! isset($_POST['borrador']))
    {
      $this->configAddModFactura();

      if($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $respons = $this->proveedores_facturacion_model->addFactura();

        if($respons['passes'])
          redirect(base_url('panel/proveedores_facturacion/agregar/?msg=3'));
          // redirect(base_url('panel/documentos/agregar/?msg=3&id='.$respons['id_factura']));
        else
          $params['frm_errors'] = $this->showMsgs(2, $respons['msg']);
      }

    }
    else
      $params['frm_errors'] = $this->procesaBorrador();

    // Parametros por default.
    $params['series'] = $this->proveedores_facturacion_model->getSeriesFolios(100);
    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['getId'] = '';

    // Parametros por default.
    if (isset($_GET['id']))
    {
      $params['getId'] = 'id='.$_GET['id'];
    }
    else // Parametros por default.
    {
      // Obtiene los datos de la empresa predeterminada.
      // $params['empresa_default'] = $this->db
      //   ->select("e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org")
      //   ->from("empresas AS e")
      //   ->where("e.predeterminado", "t")
      //   ->get()
      //   ->row();

      // // Obtiene el numero de certificado de la empresa predeterminada.
      // $params['no_certificado'] = $this->cfdi->obtenNoCertificado($params['empresa_default']->cer_org);
    }

    // Verifica si existe un borrador y carga sus datos.
    $idBorrador = $this->proveedores_facturacion_model->getBorradorFactura();
    if ( ! is_null($idBorrador))
    {
      $params['getId'] = 'id='.$idBorrador;

      $params['borrador'] = $this->proveedores_facturacion_model->getInfoFactura($idBorrador);
    } elseif($this->input->get('fid_proveedor') > 0 && $this->input->get('did_empresa') > 0) {
      $params['borrador'] = $this->proveedores_facturacion_model->getInfoPredatosFactura($this->input->get('fid_proveedor'), $this->input->get('did_empresa'));
    }

    // echo "<pre>";
    //   var_dump($params['borrador']);
    // echo "</pre>";exit;

    $this->load->model('areas_model');
    $params['areas'] = $this->areas_model->getAreas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/proveedores/facturacion/agregar', $params);
    $this->load->view('panel/footer');
  }

  public function procesaBorrador()
  {
    $this->load->model('proveedores_facturacion_model');

    $this->configAddModFactura(true);

    if($this->form_validation->run() == FALSE)
    {
      return $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      if (isset($_GET['id']))
        $this->proveedores_facturacion_model->updateFacturaBorrador($_GET['id']);
      else
        $this->proveedores_facturacion_model->addFacturaBorrador();

      if($respons['passes'])
        redirect(base_url('panel/documentos/agregar/?msg=3&id='.$respons['id_factura']));
      else
        $params['frm_errors'] = $this->showMsgs(2, $respons['msg']);
    }
    redirect(base_url('panel/proveedores_facturacion/agregar/?&msg=11'));
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
    if (isset($_GET['id']{0}))
    {
      $this->load->model('proveedores_facturacion_model');
      $response = $this->proveedores_facturacion_model->cancelaFactura($_GET['id']);

      redirect(base_url("panel/proveedores_facturacion/admin/?msg={$response['msg']}&".MyString::getVarsLink(array('msg', 'id')) ));
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
      $this->load->model('proveedores_facturacion_model');
      $this->proveedores_facturacion_model->descargarZip($_GET['id']);
    }
    else redirect(base_url('panel/proveedores_facturacion/?msg=1'));
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

    $this->load->model('proveedores_facturacion_model');
    $this->load->model('empresas_model');

    $factura = $this->proveedores_facturacion_model->getInfoFactura($_GET['id']);
    $empresa = $this->empresas_model->getInfoEmpresa($factura['info']->id_empresa);

    $params['emails_default'] = array();
    if ($empresa['info']->email !== '')
      $params['emails_default'] = explode(',', $empresa['info']->email);

    if(isset($_GET['msg']{0}))
    {
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      if ($_GET['msg'] == 10)
      {
        $params['close'] = 1;
      }

    }

    $this->load->view('panel/proveedores/facturacion/email',$params);
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
      $this->load->model('proveedores_facturacion_model');
      $response = $this->proveedores_facturacion_model->enviarEmail($_GET['id']);

      redirect(base_url("panel/proveedores_facturacion/enviar_documentos?id={$_GET['id']}&msg={$response['msg']}"));
    }
    else redirect(base_url('panel/proveedores_facturacion/?msg=1'));
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

        array('field'   => 'did_proveedor',
              'label'   => 'Proveedor',
              'rules'   => 'required|max_length[25]'),
        array('field'   => 'did_empresa',
              'label'   => 'Empresa',
              'rules'   => 'required|max_length[25]'),
        array('field'   => 'dserie',
              'label'   => 'Serie',
              'rules'   => 'max_length[25]'),
        array('field'   => 'dfolio',
              'label'   => 'Folio',
              'rules'   => 'required|numeric|callback_seriefolio_check'),
        array('field'   => 'dno_aprobacion',
              'label'   => 'Numero de aprobacion',
              'rules'   => ''),
        array('field'   => 'dano_aprobacion',
              'label'   => 'Fecha de aprobacion',
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

        array('field'   => 'total_descuento',
              'label'   => 'Descuento',
              'rules'   => $required.'|numeric'),
        array('field'   => 'total_iva',
              'label'   => 'IVA',
              'rules'   => $required.'|numeric'),
        array('field'   => 'total_retiva',
              'label'   => 'Retencion IVA',
              'rules'   => $required.'|numeric'),
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

        array('field'   => 'dproveedor',
              'label'   => 'Proveedor',
              'rules'   => ''),
        array('field'   => 'dempresa',
              'label'   => 'Empresa',
              'rules'   => ''),
        array('field'   => 'dempresa_rfc',
              'label'   => 'Empresa',
              'rules'   => ''),
        array('field'   => 'dempresa_domici',
              'label'   => 'Empresa',
              'rules'   => ''),
        array('field'   => 'dempresa_ciudad',
              'label'   => 'Empresa',
              'rules'   => ''),
        array('field'   => 'dttotal_letra',
              'label'   => 'letra',
              'rules'   => ''),
        array('field'   => 'dreten_iva',
              'label'   => 'Retecion IVA',
              'rules'   => ''),

        array('field'   => 'prod_did_prod[]',
              'label'   => 'prod_did_prod',
              'rules'   => ''),
        array('field'   => 'prod_dcantidad[]',
              'label'   => 'prod_dcantidad',
              'rules'   => ''),
        array('field'   => 'prod_ddescripcion[]',
              'label'   => 'prod_ddescripcion',
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
              'label'   => 'prod_importe',
              'rules'   => ''),
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

        array('field'   => 'dversion',
              'label'   => '',
              'rules'   => ''),
        array('field'   => 'dcer_caduca',
              'label'   => 'Proveedor',
              'rules'   => ''),

        array('field'   => 'dno_certificado',
              'label'   => 'No. Certificado',
              'rules'   => ''),
        array('field'   => 'dtipo_comprobante',
              'label'   => 'Tipo comproante',
              'rules'   => $required),
        array('field'   => 'dobservaciones',
              'label'   => 'Observaciones',
              'rules'   => ''),

        array('field'   => 'prod_folio[]',
              'label'   => 'prod_folio',
              'rules'   => ''),


    );
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
      $this->load->model('proveedores_facturacion_model');
      $this->proveedores_facturacion_model->generaFacturaPdf($_GET['id']);
    }
    else
      redirect(base_url('panel/facturacion/?msg=1'));
  }


  public function chk_cer_caduca($date)
  {
    $hoy = date('Y-m-d');

    if (strtotime($hoy) > strtotime($date))
    {
      $this->form_validation->set_message('chk_cer_caduca', 'El certificado del proveedor caducó, actualize la información de la misma.');
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

      $res = $this->db->select('Count(id_factura) AS num')
        ->from('proveedores_facturacion')
        ->where("serie = '".$this->input->post('dserie')."' AND folio = ".$str." AND id_proveedor = ". $this->input->post('did_proveedor').' AND status != \'b\'')
        ->get();
      $data = $res->row();
      if($data->num > 0){
        $this->form_validation->set_message('seriefolio_check', 'El serie y folio ya esta utilizado por otra Factura.');
        return false;
      } else {
        $anoLimite = date('Y-m-d',strtotime($this->input->post('dano_aprobacion') . " + 730 day"));

        $hoy = date('Y-m-d');
        // $hoy = '2015-07-19';

        if (strtotime($hoy) > strtotime($anoLimite))
        {
          $this->form_validation->set_message('seriefolio_check', 'El serie y folio ya caducaron, no pueden ser utilizados.');
          return false;
        }
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
    if($str <= 0){
      $this->form_validation->set_message('val_total', 'El Total no puede ser 0, verifica los datos ingresados.');
      return false;
    } else {
      $info = $this->proveedores_facturacion_model->getLimiteProveedores($_POST['did_proveedor'], date('Y'));

      if ((floatval($info['facturado']) + floatval($str)) > floatval($info['limite']))
      {
        $this->form_validation->set_message('val_total', 'El total de la factura sobrepasa el limite permitido a facturar del proveedor.');
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
    $this->load->model('proveedores_facturacion_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Administrar Series y Folios');

    $params['datos_s'] = $this->proveedores_facturacion_model->getSeriesFolios();

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/proveedores/facturacion/series_folios/admin',$params);
    $this->load->view('panel/footer',$params);
  }

  public function agregar_serie_folio()
  {
    $this->carabiner->js(array(
        array('panel/proveedores/facturacion/series_folios/frm_addmod.js')
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']  = array('titulo' => 'Agregar Series y Folios');

    $this->load->model('proveedores_facturacion_model');

    $this->configAddSerieFolio();
    if($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2,preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $model_resp = $this->proveedores_facturacion_model->addSerieFolio();

      if($model_resp['passes'])
        redirect(base_url('panel/proveedores_facturacion/agregar_serie_folio/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
    }

    if(isset($_GET['msg']{0}))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/proveedores/facturacion/series_folios/agregar',$params);
    $this->load->view('panel/footer',$params);
  }

  public function modificar_serie_folio()
  {
    if(isset($_GET['id']{0})){
      $this->carabiner->js(array(
          array('panel/proveedores/facturacion/series_folios/frm_addmod.js')
      ));

      $this->load->model('proveedores_facturacion_model');
      $this->configAddSerieFolio('edit');

      if($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2,preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $model_resp = $this->proveedores_facturacion_model->editSerieFolio($_GET['id']);

        if($model_resp['passes'])
          $params['frm_errors'] = $this->showMsgs(6);
      }

      $params['info_empleado']  = $this->info_empleado['info'];
      $params['opcmenu_active']   = 'Proveedores'; //activa la opcion del menu
      $params['seo']['titulo']  = 'Modificar Serie y Folio';

      $params['serie_info'] = $this->proveedores_facturacion_model->getInfoSerieFolio($_GET['id']);

      if(isset($_GET['msg']{0}))
          $params['frm_errors'] = $this->showMsgs($_GET['msg']);

        $this->load->view('panel/header',$params);
        $this->load->view('panel/general/menu',$params);
        $this->load->view('panel/proveedores/facturacion/series_folios/modificar',$params);
        $this->load->view('panel/footer',$params);
    }
    else
      redirect(base_url('panel/proveedores_facturacion/index_serie_folios/').MyString::getVarsLink(array('msg')).'&msg=1');
  }

  /**
   * obtiene el folio siguiente de la serie seleccionada
   */
  public function get_folio()
  {
    if(isset($_GET['serie']) && isset($_GET['idp']))
    {
      $this->load->model('proveedores_facturacion_model');
      $res = $this->proveedores_facturacion_model->getFolioSerie($_GET['serie'], $_GET['idp'], "es_nota_credito = 'f'");

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
      $this->load->model('proveedores_facturacion_model');
      $res = $this->proveedores_facturacion_model->getSeriesProveedor($_GET['ide']);

      $param =  $this->showMsgs(2, $res[1]);
      $param['data'] = $res[0];
      echo json_encode($param);
    }
  }

  private function configAddSerieFolio($tipo='add')
  {
    $this->load->library('form_validation');

    $rules = array(
            array('field' => 'fid_proveedor',
                'label' => 'Proveedor',
                'rules' => 'required|numeric'),
            array('field' => 'fproveedor',
                'label' => 'Proveedor',
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

    if ($_POST['fid_proveedor'] !== '')
    {
      if ($str != '')
      {
        if($tipo=='add'){
          if($this->proveedores_facturacion_model->exist('proveedores_series_folios',
              array('serie' => mb_strtoupper($str), 'id_proveedor' => $this->input->post('fid_proveedor')) )){
            $this->form_validation->set_message('isValidSerie', 'El campo %s ya existe');
            return false;
          }
          return true;
        }
        else{
          $row = $this->proveedores_facturacion_model->exist('proveedores_series_folios',
            array('serie' => mb_strtoupper($str), 'id_proveedor' => $this->input->post('fid_proveedor')), true);

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

      echo json_encode($this->clasificaciones_model->ajaxClasificaciones());
   }

  /**
    * Obtiene listado de empresas por ajax
    */
  public function ajax_get_proveedor_fac(){
    $this->load->model('proveedores_facturacion_model');
    $params = $this->proveedores_facturacion_model->getFacProveedoresAjax();

    echo json_encode($params);
  }

  /**
    * Obtiene listado de los clientes que tienen RFC por ajax.
    */
  public function ajax_get_empresas(){
    $this->load->model('empresas_model');
    $params = $this->empresas_model->getEmpresasAjaxFac(" AND rfc != ''");

    echo json_encode($params);
  }

  /**
    * Obtiene los productos del ticket.
    */
  public function ajax_get_ticket(){
    $this->load->model('proveedores_facturacion_model');
    $params = $this->proveedores_facturacion_model->getTicketInfo($_GET['t'], $_GET['a']);

    echo json_encode(array('response' => $params));
  }

  /**
    * Obtiene los productos del ticket.
    */
  public function get_empresa_default()
  {
    $this->load->model('empresas_model');
    $params = $this->empresas_model->getDefaultEmpresa();

    echo json_encode($params);
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
      case 'error':
        $txt = 'Error al intentar cancelar: UUID No existente.';
        $icono = 'error';
        break;
      case 708:
        $txt = 'No se pudo cancelar la factura debido a un error del servicio, vuelva a intentarlo en unos minutos.';
        $icono = 'error';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}
