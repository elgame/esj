<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_fiscal extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'facturacion/get_folio/',
    'facturacion/get_series/',
    'facturacion/email/',
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
   * Agrega una factura a la bd
   *
   * @return void
   */
  public function asistencia()
  {
    $this->carabiner->js(array(
        array('libs/jquery.numeric.js'),
        array('general/keyjump.js'),
        array('general/util.js'),
        array('panel/nomina_fiscal/asistencia.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Agregar factura');
    $params['pagar_ordent']   = false;

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    // $this->configAddModFactura();
    // if($this->form_validation->run() == FALSE)
    // {
    //   $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    // }
    // else
    // {
    //   $respons = $this->facturacion_model->addFactura();

    //   if($respons['passes'])
    //     redirect(base_url('panel/documentos/agregar/?msg=3&id='.$respons['id_factura']));
    //   else
    //     $params['frm_errors'] = $this->showMsgs(2, $respons['msg']);
    // }

    $filtros = array('semana' => '1');

    $params['listadoAsistencias'] = $this->nomina_fiscal_model->listadoAsistencias($filtros);
    $params['empresas'] = $this->empresas_model->getEmpresasAjax();
    $params['puestos'] = $this->usuarios_model->puestos();
    $params['semanasDelAno'] = $this->nomina_fiscal_model->semanasDelAno();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/nomina_fiscal/asistencia', $params);
    $this->load->view('panel/footer');
  }


  /*
   |------------------------------------------------------------------------
   | Validacion form
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
    );

    if (isset($_POST['palletsIds']) && isset($_POST['timbrar']))
    {
      $rules[] = array(
        'field'   => 'palletsIds[]',
        'label'   => 'Pallets',
        'rules'   => 'callback_check_existen_pallets'
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
      $this->facturacion_model->generaFacturaPdf($_GET['id']);
    }
    else
      redirect(base_url('panel/facturacion/?msg=1'));
  }

  public function chk_cer_caduca($date)
  {
    $hoy = date('Y-m-d');

    if (strtotime($hoy) > strtotime($date))
    {
      $this->form_validation->set_message('chk_cer_caduca', 'El certificado de la empresa caducó, actualize la información de la misma.');
      return false;
    }

    return true;
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

?>