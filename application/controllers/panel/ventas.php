<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ventas extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'ventas/get_folio/',

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
      array('panel/ventas_remision/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('ventas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Ventas de remisión');

    $params['datos_s'] = $this->ventas_model->getVentas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/ventas_remision/admin',$params);
    $this->load->view('panel/footer',$params);
  }

  /**
   * Agrega una nota de remision a la bd
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
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['seo']            = array('titulo' => 'Agregar Nota remisión');
    $params['pagar_ordent']   = false;

    $this->load->model('ventas_model');

    $this->configAddModFactura();
    if($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $respons = $this->ventas_model->addNotaVenta();

      if($respons[0])
        redirect(base_url('panel/ventas/agregar/?msg=4&id='.$respons[2]));
    }

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    if (isset($_GET['id']))
    {
      $params['id'] = $_GET['id'];
    }
    
    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->db
      ->select("e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org")
      ->from("empresas AS e")
      ->where("e.predeterminado", "t")
      ->get()
      ->row();

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

  /**
   * Configura los metodos de agregar y modificar
   */
  private function configAddModFactura()
  {
    $this->load->library('form_validation');
    $rules = array(

        array('field'   => 'did_empresa',
              'label'   => 'Empresa',
              'rules'   => 'required|numeric'),
        array('field'   => 'did_cliente',
              'label'   => 'Cliente',
              'rules'   => 'required|numeric'),
        array('field'   => 'dfolio',
              'label'   => 'Folio',
              'rules'   => 'required|numeric|callback_seriefolio_check'),

        array('field'   => 'dfecha',
              'label'   => 'Fecha',
              'rules'   => 'required|max_length[25]'), //|callback_isValidDate

        array('field'   => 'total_importe',
              'label'   => 'SubTotal',
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
        array('field'   => 'prod_dpreciou[]',
              'label'   => 'prod_dpreciou',
              'rules'   => ''),
        array('field'   => 'prod_importe[]',
              'label'   => 'prod_importe',
              'rules'   => ''),
        array('field'   => 'prod_dmedida[]',
              'label'   => 'prod_dmedida',
              'rules'   => ''),
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

      $res = $this->db->select('Count(id_venta) AS num')
        ->from('facturacion_ventas_remision')
        ->where("folio = ".$str." AND id_empresa = ". $this->input->post('did_empresa'))
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
    if($str <= 0){
      $this->form_validation->set_message('val_total', 'El Total no puede ser 0, verifica los datos ingresados.');
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
      $this->ventas_model->generaNotaRemisionPdf($_GET['id']);
    }
    else
      redirect(base_url('panel/ventas/?msg=1'));
  }




  /**
   * obtiene el folio siguiente de la serie seleccionada
   */
  public function get_folio()
  {
    if(isset($_GET['ide']))
    {
      $this->load->model('ventas_model');
      $res = $this->ventas_model->getFolio($_GET['ide']);

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
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

?>