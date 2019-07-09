<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class gastos extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'gastos/ajax_get_cuentas_proveedor/',
    'gastos/ligar/',
    'gastos/ajax_get_facturas/',
    'gastos/verXml/'
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
    $this->carabiner->css(array(
      array('panel/tags.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/util.js'),
      array('general/keyjump.js'),
      array('general/msgbox.js'),
      array('general/supermodal.js'),
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
    $this->load->model('empresas_model');

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
      //   redirect(base_url('panel/compras_ordenes/ligar/?'.MyString::getVarsLink(array('msg')).'&msg=9&rel=t'));
      // }
    }

    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();
    // $this->db
    //   ->select("e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org")
    //   ->from("empresas AS e")
    //   ->where("e.predeterminado", "t")
    //   ->get()
    //   ->row();

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

    if (isset($_POST['proveedorId']) && $_POST['proveedorId'] !== '')
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
    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('general/util.js'),
      array('panel/compras_ordenes/ver.js'),
    ));

    $this->carabiner->css(array(
      array('panel/tags.css', 'screen'),
    ));

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
      $this->gastos_model->updateXml($_GET['id'], $_GET['idp'], (isset($_FILES['xml'])? $_FILES['xml']: false));

      $params['frm_errors'] = $this->showMsgs(4);
    }

    $params['proveedor'] = $this->proveedores_model->getProveedorInfo($_GET['idp'], true);
    $params['gasto']     = $this->compras_model->getInfoCompra($_GET['id'], false);
    $params['empresa']   = $this->empresas_model->getInfoEmpresa($params['gasto']['info']->id_empresa, true);

    $this->load->view('panel/gastos/ver', $params);
  }

  public function verXml()
  {
    $this->carabiner->js(array(
      array('general/util.js'),
      array('panel/gastos/verXml.js')
    ));
    $this->carabiner->css(array(
      array('panel/tags.css', 'screen'),
    ));

    $this->load->model('gastos_model');
    $this->load->model('compras_model');
    $this->load->model('proveedores_model');
    $this->load->model('empresas_model');

    $params['proveedor'] = $this->proveedores_model->getProveedorInfo($_GET['idp'], true);
    if (!empty($_GET['id'])) {
      $params['gasto'] = $this->compras_model->getInfoCompra($_GET['id'], false);
    }
    $ide = !empty($_GET['ide'])? $_GET['ide']: $params['gasto']['info']->id_empresa;
    $params['ide']     = $ide;
    $params['empresa'] = $this->empresas_model->getInfoEmpresa($ide, true);

    $rfcProv = !empty($_GET['rfc'])? trim(strtoupper($_GET['rfc'])): $params['proveedor']['info']->rfc;
    $params['rfc'] = $rfcProv;

    $path = "C:\DescargasXMLenlinea/{$params['empresa']['info']->rfc}/RECIBIDOS";
    if (is_dir($path)) {
      $response = MyFiles::searchXmlEnlinea($path, $rfcProv, $this->input->get('ffolio'),
        $this->input->get('ffecha1'), $this->input->get('ffecha2'));
      $params['files'] = $response;
      // echo "<pre>";
      //   var_dump($response);
      // echo "</pre>";exit;
    }

    $this->load->view('panel/gastos/addXmls', $params);
  }

  public function cancelar()
  {
    $this->load->model('compras_model');
    $this->compras_model->cancelar($_GET['id']);

    redirect(base_url('panel/compras/?' . MyString::getVarsLink(array('id')).'&msg=3'));
  }


  public function ligar()
  {
     $this->carabiner->js(array(
      array('general/keyjump.js'),
      array('general/msgbox.js'),
      array('panel/gastos/agregar.js')
    ));

    $this->load->library('pagination');
    $this->load->model('compras_ordenes_model');

    $params['ordenes'] = $this->compras_ordenes_model->getOrdenes();

    $this->load->view('panel/gastos/ligar', $params);
  }

  public function ligar_facturas()
  {
     $this->carabiner->js(array(
      array('general/keyjump.js'),
      array('general/msgbox.js'),
      array('panel/gastos/ligar_facturas.js')
    ));

     $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Ligar facturas'
    );

    $this->load->model('gastos_model');
    $this->load->model('empresas_model');

    $this->configLigarFacturas();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->gastos_model->saveLigarFactura($_POST);

      $params['frm_errors'] = $this->showMsgs(31);
    }

    $params['facturas'] = $this->gastos_model->getFacturasLigadas($_GET);
    $params['empresa'] = $this->empresas_model->getInfoEmpresa($this->input->get('ide'));

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/gastos/ligar_facturas', $params);
    $this->load->view('panel/footer');
  }

  public function ajax_get_facturas()
  {
    $this->load->model('gastos_model');
    $params = $this->gastos_model->getFacturasLibre($_GET);

    echo json_encode($params);
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
      array('panel/gastos/agregar_nota_credito.js'),
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
      $res_mdl = $this->compras_model->agregarNotaCredito($_GET['id'], $_POST, $_FILES['xml'], true);

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/gastos/agregar_nota_credito/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    $params['unidades']      = $this->compras_ordenes_model->unidades();
    $params['fecha'] = str_replace(' ', 'T', date("Y-m-d"));
    $params['compra'] = $this->compras_model->getInfoCompra($_GET['id']);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/gastos/nota_credito', $params);
    $this->load->view('panel/footer');
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
      array('panel/gastos/agregar_nota_credito.js'),
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
      $res_mdl = $this->compras_model->actualizarNotaCredito($_GET['id'], $_POST, $_FILES['xml'], true);

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/gastos/ver_nota_credito/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    $params['unidades']      = $this->compras_ordenes_model->unidades();
    $params['nota_credito'] = $this->compras_model->getInfoNotaCredito($_GET['id']);
    $params['fecha'] = substr($params['nota_credito']['info']->fecha, 0, 10);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/gastos/ver_nota_credito', $params);
    $this->load->view('panel/footer');
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
            'rules' => 'required|numeric'),

      array('field' => 'fecha',
            'label' => 'Fecha',
            'rules' => 'required'),
      array('field' => 'observaciones',
            'label' => 'Observaciones',
            'rules' => ''),

      // array('field' => 'condicionPago',
      //       'label' => 'Condicion de Pago',
      //       'rules' => 'required'),
      // array('field' => 'plazoCredito',
      //       'label' => 'Plazo Credito',
      //       'rules' => ''),

      array('field' => 'totalLetra',
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
      array('field' => 'xml',
            'label' => 'XML',
            'rules' => 'callback_xml_check'),
    );

    $this->form_validation->set_rules($rules);
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
            'rules' => 'required|numeric|callback_serie_folio'),

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

      array('field' => 'areaId',
            'label' => 'Cultivo',
            'rules' => 'required|numeric'),
      array('field' => 'area',
            'label' => 'Cultivo',
            'rules' => 'required'),
      array('field' => 'ranchoId[]',
            'label' => 'Rancho',
            'rules' => 'required|numeric'),
      array('field' => 'ranchoText[]',
            'label' => 'Rancho',
            'rules' => ''),
      array('field' => 'centroCostoId[]',
            'label' => 'Centro de costo',
            'rules' => 'required|numeric'),
      array('field' => 'centroCostoText[]',
            'label' => 'Centro de costo',
            'rules' => ''),
      array('field' => 'activoId',
            'label' => 'Activo',
            'rules' => 'numeric'),
      array('field' => 'activos',
            'label' => 'Activo',
            'rules' => ''),
      array('field' => 'intangible',
            'label' => 'Gasto intangible',
            'rules' => ''),

      array('field' => 'subtotal',
            'label' => 'Subtotal',
            'rules' => 'required|greater_than[0]'),
      array('field' => 'iva',
            'label' => 'IVA',
            'rules' => 'required'),
      array('field' => 'ret_iva',
            'label' => 'Ret. IVA',
            'rules' => 'required'),
      array('field' => 'ret_isr',
            'label' => 'Ret. ISR',
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

    $rules[] = array('field' => 'ordenes[]',
                    'label' => 'Ordenes',
                    'rules' => '');
    $rules[] = array('field' => 'ordenes_folio[]',
                    'label' => 'Ordenes',
                    'rules' => '');

    $this->form_validation->set_rules($rules);
  }

  public function serie_folio($folio)
  {
    $serie = mb_strtoupper($this->input->post('serie'), 'utf-8');
    $query = $this->db->query("SELECT Count(id_compra) AS num FROM compras WHERE status <> 'ca' AND folio = {$folio} AND UPPER(serie) = '{$serie}'
      AND id_empresa = ".$this->input->post('empresaId')." AND id_proveedor = ".$this->input->post('proveedorId')."  ".
      (isset($_GET['id']{0})? " AND id_compra <> ".$_GET['id']: '') )->row();
    if ($query->num > 0)
    {
      $this->form_validation->set_message('serie_folio', 'El %s ya esta asignado.');
      return false;
    }
    else
    {
      return true;
    }
  }

  public function configUpdateXml()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'xml',
            'label' => 'XML',
            'rules' => 'callback_xml_check'),
      array('field' => 'uuid',
            'label' => 'UUID',
            'rules' => 'callback_uuid_check'),
      array('field' => 'aux',
            'label' => '',
            'rules' => ''),
      array('field' => 'serie',
            'label' => 'Serie',
            'rules' => ''),
      array('field' => 'folio',
            'label' => 'Folio',
            'rules' => 'required|numeric|callback_serie_folio'),
    );

    $this->form_validation->set_rules($rules);
  }

  public function configLigarFacturas()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'id_compra',
            'label' => 'compra',
            'rules' => 'required|numeric'),
      array('field' => 'id_empresa',
            'label' => 'empresa',
            'rules' => 'required|numeric'),
      array('field' => 'idclasif[]',
            'label' => 'clasificaciones',
            'rules' => 'required|numeric'),
      array('field' => 'idfactura[]',
            'label' => 'Factura',
            'rules' => ''),
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
      $query = $this->db->query("SELECT Count(id_compra) AS num FROM compras WHERE status <> 'ca' AND uuid = '{$uuid}'".
        (isset($_GET['id']{0})? " AND id_compra <> ".$_GET['id']: '') )->row();

      if ($query->num > 0) {
        $this->form_validation->set_message('uuid_check', 'El UUID ya esta registrado en otra compra.');
        return false;
      }
      return true;
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
      case 31:
        $txt = 'Se ligaron las facturas correctamente.';
        $icono = 'success';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}