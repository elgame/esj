<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class gastos extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'gastos/ajax_get_cuentas_proveedor/'
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
   redirect(base_url('panel/gastos/agregar/'));
  }

  public function agregar()
  {
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/util.js'),
      array('general/keyjump.js'),
      array('general/msgbox.js'),
      // array('general/supermodal.js'),
      // array('panel/compras_ordenes/agregar.js'),
      array('panel/gastos/agregar.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Gasto'
    );

    $this->load->model('gastos_model');
    $this->load->model('proveedores_model');
    $this->load->model('banco_cuentas_model');

    $this->configAddGasto();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->gastos_model->agregar($_POST, $_FILES['xml']);

      if ($res_mdl['passes'])
      {
        // redirect(base_url('panel/gastos/agregar/?msg=3'));
        $params['frm_errors'] = $this->showMsgs(3);
        if(count($res_mdl['banco']) > 0)
          $params['id_movimiento'] = ($res_mdl['banco']['ver_cheque'] ? $res_mdl['banco']['id_movimiento'] : '');
        $params['reload'] = true;
      }
      else
      {
        $params['frm_errors'] = $this->showMsgs($res_mdl['msg']);
      }
      // if ($res_mdl['passes'])
      // {
      //   redirect(base_url('panel/compras_ordenes/ligar/?'.String::getVarsLink(array('msg')).'&msg=9&rel=t'));
      // }
    }

    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->db
      ->select("e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org")
      ->from("empresas AS e")
      ->where("e.predeterminado", "t")
      ->get()
      ->row();

    $params['proveedores'] = $this->db->query(
      "SELECT p.id_proveedor, p.nombre_fiscal
        FROM proveedores p
        WHERE p.status = 'ac'")->result();

    $params['fecha'] = str_replace(' ', 'T', date("Y-m-d H:i"));

    //Cuentas de banco
    $params['cuentas'] = $this->banco_cuentas_model->getCuentas(false);
    //metodos de pago
    $params['metods_pago']  = array(
      array('nombre' => 'Transferencia', 'value' => 'transferencia'),
      array('nombre' => 'Cheque', 'value' => 'cheque'),
      array('nombre' => 'Efectivo', 'value' => 'efectivo'),
      array('nombre' => 'Deposito', 'value' => 'deposito'),
    );

    if (isset($_POST['proveedorId']))
    {
      $params['cuentas_proveedor'] = $this->proveedores_model->getCuentas($_POST['proveedorId']);
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    if (isset($_GET['rel']))
      $params['reload'] = true;

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/gastos/agregar', $params);
    $this->load->view('panel/footer');
  }

  public function ver()
  {
    $this->load->model('gastos_model');
    $this->load->model('compras_model');
    $this->load->model('proveedores_model');
    $this->load->model('empresas_model');

    $this->configUpdateXml();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->gastos_model->updateXml($_GET['id'], $_GET['idp'], $_FILES['xml']);

      $params['frm_errors'] = $this->showMsgs(4);
    }

    $params['proveedor'] = $this->proveedores_model->getProveedorInfo($_GET['idp'], true);
    $params['gasto']     = $this->compras_model->getInfoCompra($_GET['id'], false);
    $params['empresa']   = $this->empresas_model->getInfoEmpresa($params['gasto']['info']->id_empresa, true);

    $this->load->view('panel/gastos/ver', $params);
  }

  public function cancelar()
  {
    $this->load->model('compras_model');
    $this->compras_model->cancelar($_GET['id']);

    redirect(base_url('panel/compras/?' . String::getVarsLink(array('id')).'&msg=3'));
  }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

   public function ajax_get_cuentas_proveedor()
   {
      $this->load->model('proveedores_model');
     //Cuentas del proeveedor
      $cuentas = $this->proveedores_model->getCuentas($_GET['idp']);
      echo json_encode($cuentas);
   }

  public function configAddGasto()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'empresaId',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'empresa',
            'label' => '',
            'rules' => ''),

      array('field' => 'proveedorId',
            'label' => 'Proveedor',
            'rules' => 'required'),
      array('field' => 'proveedor',
            'label' => '',
            'rules' => ''),

      array('field' => 'tipo_documento',
            'label' => 'Tipo de Documento',
            'rules' => 'required'),

      array('field' => 'serie',
            'label' => 'Serie',
            'rules' => ''),
      array('field' => 'folio',
            'label' => 'Folio',
            'rules' => 'required'),

      array('field' => 'fecha',
            'label' => 'Fecha',
            'rules' => 'required'),

      array('field' => 'condicionPago',
            'label' => 'Condicion de Pago',
            'rules' => 'required'),
      array('field' => 'plazoCredito',
            'label' => 'Plazo Credito',
            'rules' => ''),

      array('field' => 'concepto',
            'label' => 'Concepto',
            'rules' => 'required|max_length[200]'),

      array('field' => 'fcuentas_proveedor',
            'label' => 'Cuenta Proveedor',
            'rules' => ''),

      array('field' => 'subtotal',
            'label' => 'Subtotal',
            'rules' => 'required|greater_than[0]'),
      array('field' => 'iva',
            'label' => 'IVA',
            'rules' => 'required'),
      array('field' => 'total',
            'label' => 'Total',
            'rules' => 'required|greater_than[0]'),
      array('field' => 'xml',
            'label' => 'XML',
            'rules' => 'callback_xml_check'),
    );

    $rules[] = array('field' => 'es_vehiculo',
                    'label' => 'Vehiculo',
                    'rules' => '');
    $rules[] = array('field' => 'vehiculo',
                    'label' => 'Vehiculos',
                    'rules' => '');
    $rules[] = array('field' => 'vehiculoId',
                    'label' => 'Vehiculos',
                    'rules' => '');
    if ($this->input->post('es_vehiculo') == 'si')
    {
      $rules[count($rules)-1]['rules'] = 'required|numeric';

      $rules[] = array('field' => 'tipo_vehiculo',
                      'label' => 'Tipo vehiculo',
                      'rules' => '');
      if ($this->input->post('tipo_vehiculo') == 'g')
      {
        $rules[] = array('field' => 'dkilometros',
                        'label' => 'Kilometros',
                        'rules' => 'required|numeric');
        $rules[] = array('field' => 'dlitros',
                        'label' => 'Litros',
                        'rules' => 'required|numeric');
        $rules[] = array('field' => 'dprecio',
                        'label' => 'Precio',
                        'rules' => 'required|numeric');
      }
    }

    $this->form_validation->set_rules($rules);
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
        $txt = 'El gasto se agrego correctamente.';
        $icono = 'success';
      break;
      case 4:
        $txt = 'El XML se actualizo correctamente.';
        $icono = 'success';
      break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}