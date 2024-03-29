<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class compras extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
  'compras/ajax_producto_by_codigo/',
  'compras/ajax_producto/',
  'compras/rpt_compras_pdf/',
  'compras/rpt_compras_xls/',
  'compras/rpt_compras_productos_pdf/',
  'compras/rpt_compras_productos_xls/',
  );

  public function _remap($method){

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
      array('general/supermodal.js'),
      array('general/msgbox.js'),
      array('panel/compras_ordenes/admin.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Compras'
    );

    $this->load->library('pagination');
    $this->load->model('compras_model');

    // Obtiene los datos de la empresa predeterminada.
    $this->load->model('empresas_model');
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();
    if(!$this->input->get('did_empresa'))
      $_GET['did_empresa'] = $params['empresa_default']->id_empresa;

    $params['compras'] = $this->compras_model->getCompras();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/compras/admin', $params);
    $this->load->view('panel/footer');
  }

  public function ver()
  {
    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('general/util.js'),
      array('panel/compras_ordenes/ver.js'),
    ));

    $this->load->model('proveedores_model');
    $this->load->model('compras_model');
    $this->load->model('compras_ordenes_model');

    $this->configUpdateXml();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $response = $this->compras_model->updateXml($_GET['id'], $_GET['idp'], (isset($_FILES['xml'])? $_FILES['xml']: false));

      if (is_array($response)) {
        $params['frm_errors'] = $this->showMsgs(2, $response[0]);
      } else
        $params['frm_errors'] = $this->showMsgs(4);
    }

    $ordenes = $this->db->select('id_orden')->from('compras_facturas')->where('id_compra', $_GET['id'])->get()->result();

    $params['proveedor'] = $this->proveedores_model->getProveedorInfo($_GET['idp'], true);

    $params['compra'] = $this->compras_model->getInfoCompra($_GET['id'], false);

    $params['productos'] = array();
    foreach ($ordenes as $key => $orden)
    {
      $orden = $this->compras_ordenes_model->info($orden->id_orden, true, false, $_GET['id']);

      foreach ($orden['info'][0]->productos as $prod)
      {
        $prod->tipo_orden = $orden['info'][0]->tipo_orden;
        $params['productos'][] = $prod;
      }
    }

    $this->load->view('panel/compras/ver', $params);
  }

  public function agregar_nota_credito()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      // array('general/buttons.toggle.js'),
      array('general/keyjump.js'),
      array('panel/compras/agregar_nota_credito.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Nota de Crédito'
    );

    $this->load->model('proveedores_model');
    $this->load->model('compras_model');
    $this->load->model('compras_ordenes_model');

    $this->configAddNotaCredito();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->compras_model->agregarNotaCredito($_GET['id'], $_POST, $_FILES['xml']);

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/compras/agregar_nota_credito/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    $params['unidades']      = $this->compras_ordenes_model->unidades();
    $params['fecha'] = str_replace(' ', 'T', date("Y-m-d"));
    $params['compra'] = $this->compras_model->getInfoCompra($_GET['id']);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/compras/nota_credito', $params);
    $this->load->view('panel/footer');
  }

  public function cancelar()
  {
    $this->load->model('compras_model');
    $this->compras_model->cancelar($_GET['id']);

    redirect(base_url('panel/compras/?' . MyString::getVarsLink(array('id')).'&msg=3'));
  }

  public function ver_nota_credito()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      // array('general/buttons.toggle.js'),
      array('general/keyjump.js'),
      array('panel/compras/agregar_nota_credito.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Nota de Crédito'
    );

    $this->load->model('proveedores_model');
    $this->load->model('compras_model');
    $this->load->model('compras_ordenes_model');

    $this->configAddNotaCredito();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $id_resp = $_GET['id'];
      $res_mdl = $this->compras_model->actualizarNotaCredito($_GET['id'], $_POST, $_FILES['xml']);

      $_GET['id'] = $id_resp;
      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/compras/ver_nota_credito/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    $params['unidades']      = $this->compras_ordenes_model->unidades();
    $params['nota_credito'] = $this->compras_model->getInfoNotaCredito($_GET['id']);
    $params['fecha'] = substr($params['nota_credito']['info']->fecha, 0, 10);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/compras/ver_nota_credito', $params);
    $this->load->view('panel/footer');
  }


  public function rpt_compras()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Compras');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/compras/rptcompras',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_compras_pdf(){
    $this->load->model('compras_model');
    $this->compras_model->getRptComprasPdf();
  }
  public function rpt_compras_xls(){
    $this->load->model('compras_model');
    $this->compras_model->getRptComprasXls();
  }

  public function rpt_compras_productos()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte Compras y productos');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/compras/rptcompras_productos',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_compras_productos_pdf(){
    $this->load->model('compras_model');
    $this->compras_model->getRptComprasProductosPdf();
  }
  public function rpt_compras_productos_xls(){
    $this->load->model('compras_model');
    $this->compras_model->getRptComprasProductosXls();
  }



  public function configUpdateXml()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'xml',
            'label' => 'XML',
            'rules' => 'callback_xml_check'),
      array('field' => 'aux',
            'label' => '',
            'rules' => ''),
      array('field' => 'uuid',
            'label' => 'UUID',
            'rules' => 'callback_uuid_check'),
      array('field' => 'fecha_factura',
            'label' => 'Fecha Factura',
            'rules' => 'required'),
    );

    $this->form_validation->set_rules($rules);
  }

  public function xml_check($file)
  {
    if (isset($_FILES['xml']) && $_FILES['xml']['type'] !== '' && $_FILES['xml']['type'] !== 'text/xml')
    {
      $this->form_validation->set_message('xml_check', 'El %s debe ser un archivo XML.');
      return false;
    }
    else
    {
      return true;
    }
  }

  public function uuid_check($uuid)
  {
    if (isset($_POST['uuid']) && $_POST['uuid'] !== '')
    {
      $query = $this->db->query("SELECT Count(id_compra) AS num, STRING_AGG((serie || folio)::text, ', ') AS folios FROM compras WHERE status <> 'ca' AND uuid = '{$uuid}'".
        (isset($_GET['id']{0})? " AND id_compra <> ".$_GET['id']: '') )->row();

      if ($query->num > 0) {
        $this->form_validation->set_message('uuid_check', "El UUID ya esta registrado en la(s) compra(s) {$query->folios}.");
        return false;
      }
      return true;
    }
    else
    {
      return true;
    }
  }

  public function configAddNotaCredito()
  {
    $this->load->library('form_validation');

    $rules = array(
      // array('field' => 'proveedorId',
      //       'label' => 'Proveedor',
      //       'rules' => 'required'),

      array('field' => 'serie',
            'label' => 'Serie',
            'rules' => ''),
      array('field' => 'folio',
            'label' => 'Folio',
            'rules' => 'required'),

      array('field' => 'fecha',
            'label' => 'Fecha',
            'rules' => 'required'),

      // array('field' => 'condicionPago',
      //       'label' => 'Condicion de Pago',
      //       'rules' => 'required'),
      // array('field' => 'plazoCredito',
      //       'label' => 'Plazo Credito',
      //       'rules' => ''),

      array('field' => 'totalLetra',
            'label' => '',
            'rules' => ''),
      array('field' => 'concepto[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'cantidad[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'productoId[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'ordenId[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'row[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'valorUnitario[]',
            'label' => 'Precio Unitario',
            'rules' => 'greater_than[-1]'),
      array('field' => 'trasladoTotal[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'trasladoPorcent[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'importe[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'total[]',
            'label' => '',
            'rules' => ''),

      array('field' => 'totalImporte',
            'label' => 'Subtotal',
            'rules' => ''),
      array('field' => 'totalImpuestosTrasladados',
            'label' => 'IVA',
            'rules' => ''),
      array('field' => 'totalRetencion',
            'label' => 'IVA',
            'rules' => '')  ,
      array('field' => 'totalOrden',
            'label' => 'Total',
            'rules' => 'greater_than[-1]'),

      array('field' => 'uuid',
            'label' => 'UUID',
            'rules' => '')  ,
      array('field' => 'noCertificado',
            'label' => 'Certificado',
            'rules' => '')  ,

      // array('field' => 'xml',
      //       'label' => 'XML',
      //       'rules' => 'callback_xml_check'),
    );

    $this->form_validation->set_rules($rules);
  }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

  public function ajax_producto_by_codigo()
  {
    $this->load->model('compras_model');
    $producto = $this->compras_model->getProductoByCodigoAjax($_GET['cod']);
    echo json_encode($producto);
  }

  public function ajax_producto()
  {
    $this->load->model('compras_model');
    $where = "lower(p.nombre) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%' AND";
    $productos = $this->compras_model->getProductoAjax($where, 'nombre');
    echo json_encode($productos);
  }

  /*
   |------------------------------------------------------------------------
   | Mensajes.
   |------------------------------------------------------------------------
   */
  private function showMsgs($tipo, $msg='', $title='Compras')
  {
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
        $txt = 'La orden se cancelo correctamente.';
        $icono = 'success';
      break;
      case 4:
        $txt = 'Se actualizo correctamente.';
        $icono = 'success';
      break;
      case 5:
        $txt = 'Nota de Crédito agregada correctamente!';
        $icono = 'success';
      break;
      case 6:
        $txt = 'Nota de Crédito actualizada correctamente!';
        $icono = 'success';
      break;
      case 30:
        $txt = 'La cuenta no tiene saldo suficiente.';
        $icono = 'error';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}