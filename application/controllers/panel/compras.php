<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class compras extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
  'compras_ordenes/ajax_producto_by_codigo/',
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
      $this->compras_model->updateXml($_GET['id'], $_GET['idp'], $_FILES['xml']);

      $params['frm_errors'] = $this->showMsgs(4);
    }

    $ordenes = $this->db->select('id_orden')->from('compras_facturas')->where('id_compra', $_GET['id'])->get()->result();

    $params['proveedor'] = $this->proveedores_model->getProveedorInfo($_GET['idp'], true);

    $params['compra'] = $this->compras_model->getInfoCompra($_GET['id'], true);

    $params['productos'] = array();
    foreach ($ordenes as $key => $orden)
    {
      $orden = $this->compras_ordenes_model->info($orden->id_orden, true);

      foreach ($orden['info'][0]->productos as $prod)
      {
        $prod->tipo_orden = $orden['info'][0]->tipo_orden;
        $params['productos'][] = $prod;
      }
    }

    $this->load->view('panel/compras/ver', $params);
  }

  public function cancelar()
  {
    $this->load->model('compras_model');
    $this->compras_model->cancelar($_GET['id']);

    redirect(base_url('panel/compras/?' . String::getVarsLink(array('id')).'&msg=3'));
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
    );

    $this->form_validation->set_rules($rules);
  }

  public function xml_check($file)
  {
    if ($_FILES['xml']['type'] !== '' && $_FILES['xml']['type'] !== 'text/xml')
    {
      $this->form_validation->set_message('xml_check', 'El %s debe ser un archivo XML.');
      return false;
    }
    else
    {
      return true;
    }
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
        $txt = 'EL XML se actualizo correctamente.';
        $icono = 'success';
      break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}