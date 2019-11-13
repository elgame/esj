<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class proveedores_notas_credito extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'proveedores_notas_credito/ajax_get_series_folio/',
    'proveedores_notas_credito/ajax_get_folio/',
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

  /**
   * Muestra el formulario.
   *
   * @return void
   */
  public function agregar()
  {
    if (isset($_GET['id']{0}))
    {
      $this->carabiner->js(array(
          array('libs/jquery.numeric.js'),
          array('general/keyjump.js'),
          array('general/util.js'),
          array('panel/proveedores/notas_credito/agregar.js'),
      ));

      $params['info_empleado']  = $this->info_empleado['info']; //info empleado
      $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
      $params['seo']            = array('titulo' => 'Agregar Nota de Crédito');
      $params['pagar_ordent']   = false;

      if(isset($_GET['ordent']{0}))
        $this->asignaOrdenTrabajo($_GET['ordent']);

      // $this->load->library('cfdi');
      $this->load->model('proveedores_facturacion_model');

      $this->configAddModNotaCredito();
      if($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $respons = $this->proveedores_facturacion_model->addFactura();

        if($respons['passes'])
          redirect(base_url('panel/proveedores_notas_credito/agregar/?msg=4&id='.$_GET['id']));
      }

      // $params['series'] = $this->facturacion_model->getSeriesFolios(100);
      $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

      if(isset($_GET['msg']{0}))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      // Obtiene la informacion de la factura.
      $params['factura'] = $this->proveedores_facturacion_model->getInfoFactura($_GET['id']);

      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/proveedores/notas_credito/agregar', $params);
      $this->load->view('panel/footer');
    }
    else redirect(base_url('panel/proveedores_facturacion/?msg=1'));
  }

  /*
   |-------------------------------------------------------------------------
   |  AJAX REQUEST
   |-------------------------------------------------------------------------
   */

  /**
   * obtiene el folio siguiente de la serie seleccionada
   *
   * @return  void
   */
  public function ajax_get_series_folio()
  {
    if(isset($_GET['ide']))
    {
      $this->load->model('proveedores_facturacion_model');
      $res = $this->proveedores_facturacion_model->getSeriesProveedor($_GET['ide'], "es_nota_credito = 't'");

      $param =  $this->showMsgs(2, $res[1]);
      $param['data'] = $res[0];
      echo json_encode($param);
    }
  }

  /**
   * obtiene el folio siguiente de la serie seleccionada
   *
   * @return  void
   */
  public function ajax_get_folio()
  {
    if(isset($_GET['serie']) && isset($_GET['idp']))
    {
      $this->load->model('proveedores_facturacion_model');
      $res = $this->proveedores_facturacion_model->getFolioSerie($_GET['serie'], $_GET['idp'], "es_nota_credito = 't'");

      $param =  $this->showMsgs(2, $res[1]);
      $param['data'] = $res[0];
      echo json_encode($param);
    }
  }

  /*
   |-------------------------------------------------------------------------
   |  FORMS VALIDATIONS
   |-------------------------------------------------------------------------
   */

  /**
   * Configura los metodos de agregar.
   *
   * @return void
   */
  private function configAddModNotaCredito()
  {
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
              'rules'   => 'required|max_length[10]|callback_isValidDate'),

        array('field'   => 'dfecha',
              'label'   => 'Fecha',
              'rules'   => 'required|max_length[25]'), //|callback_isValidDate

        array('field'   => 'total_importe',
              'label'   => 'SubTotal1',
              'rules'   => 'required|numeric'),
        array('field'   => 'total_subtotal',
              'label'   => 'SubTotal',
              'rules'   => 'required|numeric'),

        array('field'   => 'total_descuento',
              'label'   => 'Descuento',
              'rules'   => 'required|numeric'),
        array('field'   => 'total_iva',
              'label'   => 'IVA',
              'rules'   => 'required|numeric'),
        array('field'   => 'total_retiva',
              'label'   => 'Retencion IVA',
              'rules'   => 'required|numeric'),
        array('field'   => 'total_totfac',
              'label'   => 'Total',
              'rules'   => 'required|numeric|callback_val_total'),
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
              'rules'   => 'callback_chk_cer_caduca'),

        array('field'   => 'dno_certificado',
              'label'   => 'No. Certificado',
              'rules'   => 'required'),
        array('field'   => 'dtipo_comprobante',
              'label'   => 'Tipo comproante',
              'rules'   => 'required'),
        array('field'   => 'dobservaciones',
              'label'   => 'Observaciones',
              'rules'   => ''),
    );
    $this->form_validation->set_rules($rules);
  }

  /*
   |-------------------------------------------------------------------------
   |  MESAJES ALERTAS
   |-------------------------------------------------------------------------
   */

  /**
   * Muestra mensajes cuando se realiza alguna accion.
   *
   * @param int $tipo
   * @param string $msg
   * @param string $title
   * @return array
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
        $txt = 'La Nota de Credito se modifico correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'La Nota de Credito se agrego correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La Nota de Credito se cancelo correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = $msg;
        $icono = 'success';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}