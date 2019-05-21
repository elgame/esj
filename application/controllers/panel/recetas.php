<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class recetas extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'compras_requisicion/ajax_producto_by_codigo/',
    'compras_requisicion/ajax_producto/',
    'compras_requisicion/ajax_get_folio/',
    'compras_requisicion/ajax_get_producto_all/',
    'compras_requisicion/ajax_get_tipo_cambio/',

    'compras_requisicion/ligar/',
    'compras_requisicion/imprimir_recibo_faltantes/',
    'compras_requisicion/ajaxGetFactRem/',
    'compras_requisicion/imprimir_entrada/',
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
      array('general/util.js'),
      array('panel/compras_ordenes/admin.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Ordenes de Compra'
    );

    $this->load->library('pagination');
    $this->load->model('compras_requisicion_model');

    $params['ordenes'] = $this->compras_requisicion_model->getOrdenes();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['requisicion'] = false;
    $params['method']     = '';
    $params['titleBread'] = 'Ordenes de Compras';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/compras_ordenes/admin', $params);
    $this->load->view('panel/footer');
  }




  public function faltantes_productos()
  {
    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('general/msgbox.js'),
      array('general/util.js'),
      array('panel/compras_ordenes/admin.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Faltantes de productos'
    );

    // Obtiene los datos de la empresa predeterminada.
    $this->load->model('empresas_model');
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();
    if(intval($this->input->get('did_empresa')) < 1)
      $_GET['did_empresa'] = $params['empresa_default']->id_empresa;

    $this->load->model('recetas_model');
    $params['productos'] = $this->recetas_model->getProductosFaltantes();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['requisicion'] = false;
    $params['method']      = '';
    $params['titleBread']  = 'Faltantes de productos';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/recetas/faltantes_productos', $params);
    $this->load->view('panel/footer');
  }






  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddOrden($prereq = false)
  {
    $this->load->library('form_validation');

    $valGasto = $valFlete = false;
    $tipoOrden = $this->input->post('tipoOrden');
    if ($tipoOrden == 'd' || $tipoOrden == 'oc' || $tipoOrden == 'f') {
      $valGasto = true;

      if ($tipoOrden == 'f')
        $valFlete = true;
    }

    $rules = array(
      array('field' => 'empresaId',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'empresa',
            'label' => '',
            'rules' => ''),
      array('field' => 'id_almacen',
            'label' => 'Almacen',
            'rules' => ($prereq? '': 'required')),
      array('field' => 'es_receta',
            'label' => 'Es receta',
            'rules' => ''),

      array('field' => 'proveedorId1',
            'label' => 'Proveedor',
            'rules' => ($prereq? '': 'callback_val_proveedor|callback_val_proveedor2')),
      array('field' => 'proveedorId2',
            'label' => 'Proveedor',
            'rules' => ''),
      array('field' => 'proveedorId3',
            'label' => 'Proveedor',
            'rules' => ''),
      array('field' => 'proveedor1',
            'label' => '',
            'rules' => ''),
      array('field' => 'proveedor2',
            'label' => '',
            'rules' => ''),
      array('field' => 'proveedor3',
            'label' => '',
            'rules' => ''),

      array('field' => 'solicito',
            'label' => '',
            'rules' => ''),

      // array('field' => 'autorizoId',
      //       'label' => 'Autorizo',
      //       'rules' => 'required'),
      // array('field' => 'autorizo',
      //       'label' => 'Autorizo',
      //       'rules' => 'required'),

      array('field' => 'departamento',
            'label' => 'Departamento',
            'rules' => ($prereq? '': 'required')),

      array('field' => 'descripcion',
            'label' => 'Observacion',
            'rules' => ''),

      array('field' => 'clienteId',
            'label' => 'Cliente',
            'rules' => ''),
      array('field' => 'cliente',
            'label' => 'Cliente',
            'rules' => ''),

      array('field' => 'fecha',
            'label' => 'Fecha',
            'rules' => 'required'),
      array('field' => 'folio',
            'label' => 'Folio',
            'rules' => 'required'),
      array('field' => 'tipoPago',
            'label' => 'Tipo de Pago',
            'rules' => 'required'),
      array('field' => 'tipoOrden',
            'label' => 'Tipo de Orden',
            'rules' => 'required'),

      array('field' => 'infRecogerProv',
            'label' => 'Recoger con el proveedor',
            'rules' => ''),
      array('field' => 'infRecogerProvNom',
            'label' => 'Recoger con el proveedor',
            'rules' => ''),
      array('field' => 'infPasarBascula',
            'label' => 'Pasar a Bascula',
            'rules' => ''),
      array('field' => 'infEntOrdenCom',
            'label' => 'Entregar la mercancía',
            'rules' => ''),

      array('field' => 'areaId',
            'label' => 'Cultivo',
            'rules' => ($valGasto? 'required|numeric': '')),
      array('field' => 'area',
            'label' => 'Cultivo',
            'rules' => ($valGasto? 'required': '')),
      array('field' => 'ranchoId[]',
            'label' => 'Rancho',
            'rules' => ($valGasto && !$valFlete? 'required|numeric': '')),
      array('field' => 'ranchoText[]',
            'label' => 'Rancho',
            'rules' => ''),
      array('field' => 'centroCostoId[]',
            'label' => 'Centro de costo',
            'rules' => ($valGasto && !$valFlete? 'required|numeric': '')),
      array('field' => 'centroCostoText[]',
            'label' => 'Centro de costo',
            'rules' => ''),
      array('field' => 'activoId',
            'label' => 'Activo',
            'rules' => ($valGasto && !$valFlete? 'numeric': '')),
      array('field' => 'activos',
            'label' => 'Activo',
            'rules' => ($valGasto && !$valFlete? '': '')),

      array('field' => 'totalLetra1',
            'label' => '',
            'rules' => ''),
      array('field' => 'totalLetra2',
            'label' => '',
            'rules' => ''),
      array('field' => 'totalLetra3',
            'label' => '',
            'rules' => ''),
      array('field' => 'codigoArea[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'codigoAreaId[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'codigo[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'tipo_cambio[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'prodIdOrden[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'prodIdNumRow[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'cantidad[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'unidad[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'presentacion[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'presentacionCant[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'presentacionText[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'concepto[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'productoId[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'valorUnitario1[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'valorUnitario2[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'valorUnitario3[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'trasladoTotal1[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'trasladoTotal2[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'trasladoTotal3[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'iepsTotal1[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'iepsTotal2[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'iepsTotal3[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'retTotal1[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'retTotal2[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'retTotal3[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'importe1[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'importe2[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'importe3[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'total1[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'total2[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'total3[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'observacion[]',
            'label' => '',
            'rules' => 'max_length[200]'),
      array('field' => 'traslado[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'trasladoPorcent[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'iepsPorcent[]',
            'label' => '',
            'rules' => ''),

      array('field' => 'totalImporte1',
            'label' => 'Subtotal',
            'rules' => ''),
      array('field' => 'totalImporte2',
            'label' => 'Subtotal',
            'rules' => ''),
      array('field' => 'totalImporte3',
            'label' => 'Subtotal',
            'rules' => ''),
      array('field' => 'totalImpuestosTrasladados1',
            'label' => 'IVA',
            'rules' => ''),
      array('field' => 'totalImpuestosTrasladados2',
            'label' => 'IVA',
            'rules' => ''),
      array('field' => 'totalImpuestosTrasladados3',
            'label' => 'IVA',
            'rules' => ''),
      array('field' => 'totalIeps1',
            'label' => 'IEPS',
            'rules' => ''),
      array('field' => 'totalIeps2',
            'label' => 'IEPS',
            'rules' => ''),
      array('field' => 'totalIeps3',
            'label' => 'IEPS',
            'rules' => ''),
      array('field' => 'totalRetencion1',
            'label' => 'RET.',
            'rules' => ''),
      array('field' => 'totalRetencion2',
            'label' => 'RET.',
            'rules' => ''),
      array('field' => 'totalRetencion3',
            'label' => 'RET.',
            'rules' => ''),
      array('field' => 'totalOrden1',
            'label' => 'Total',
            'rules' => 'greater_than[-1]'),
      array('field' => 'totalOrden2',
            'label' => 'Total',
            'rules' => 'greater_than[-1]'),
      array('field' => 'totalOrden3',
            'label' => 'Total',
            'rules' => 'greater_than[-1]'),
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

    if($this->input->post('tipoOrden') == 'f')
    {
      $rules[] = array('field' => 'fleteDe',
                    'label' => 'Flete de',
                    'rules' => 'required');
      if($this->input->post('fleteDe') === 'v') {
        $rules[] = array('field' => 'remfacs',
                      'label' => 'Factura/Remision',
                      'rules' => 'required');
        $rules[] = array('field' => 'remfacs_folio',
                      'label' => 'Factura/Remision',
                      'rules' => '');
      } else {
        $rules[] = array('field' => 'boletas',
                      'label' => 'Boletas',
                      'rules' => 'required');
        $rules[] = array('field' => 'boletas_folio',
                      'label' => 'Boletas',
                      'rules' => '');
      }
    }

    $this->form_validation->set_rules($rules);
  }

  public function val_proveedor($proveedor)
  {
    if ($this->input->post('proveedorId1') == '' && $this->input->post('proveedorId2') == '' && $this->input->post('proveedorId3') == '' )
    {
      $this->form_validation->set_message('val_proveedor', 'Por lo menos un proveedor se tiene que seleccionar.');
      return false;
    }
    else
    {
      return true;
    }
  }
  public function val_proveedor2($proveedor)
  {
    if ($this->input->post('proveedorId1') == '' && $_POST['totalOrden1'] > 0 )
    {
      $this->form_validation->set_message('val_proveedor2', 'Se tiene que seleccionar el proveedor 1.');
      return false;
    }elseif ($this->input->post('proveedorId2') == '' && $_POST['totalOrden2'] > 0 )
    {
      $this->form_validation->set_message('val_proveedor2', 'Se tiene que seleccionar el proveedor 2.');
      return false;
    }elseif ($this->input->post('proveedorId3') == '' && $_POST['totalOrden3'] > 0 )
    {
      $this->form_validation->set_message('val_proveedor2', 'Se tiene que seleccionar el proveedor 3.');
      return false;
    }
    return true;
  }

  public function configAddOrdenLigar()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'proveedorId',
            'label' => 'Proveedor',
            'rules' => 'required'),
      array('field' => 'empresaId',
            'label' => 'Empresa',
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
      array('field' => 'iepsTotal[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'iepsPorcent[]',
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
      array('field' => 'totalIeps',
            'label' => 'IEPS',
            'rules' => ''),
      array('field' => 'totalOrden',
            'label' => 'Total',
            'rules' => 'greater_than[-1]'),
      array('field' => 'xml',
            'label' => 'XML',
            'rules' => 'callback_xml_check'),
    );

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
  private function showMsgs($tipo, $msg='', $title='Bascula')
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
        $txt = 'La orden de compra se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'La orden se autorizo correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La orden se acepto correctamente.';
        $icono = 'success';
      break;
      case 6:
        $txt = 'La orden fue rechazada.';
        $icono = 'error';
      break;
      case 7:
        $txt = 'La orden se actualizo correctamente.';
        $icono = 'success';
      break;
      case 8:
        $txt = 'La orden se cancelo correctamente.';
        $icono = 'success';
      break;
      case 9:
        $txt = 'La compra se agrego correctamente.';
        $icono = 'success';
      break;
      case 10:
        $txt = 'El email se envio correctamente.';
        $icono = 'success';
      break;
      case 11:
        $txt = 'El email no se pudo enviar porque el proveedor no cuenta con un email.';
        $icono = 'error';
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