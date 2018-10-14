<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class compras_requisicion extends MY_Controller {

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

  public function requisicion()
  {
    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('general/msgbox.js'),
      array('panel/compras_ordenes/admin.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Ordenes de Requisición'
    );

    $this->load->library('pagination');
    $this->load->model('compras_requisicion_model');

    // Obtiene los datos de la empresa predeterminada.
    $this->load->model('empresas_model');
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();
    if(intval($this->input->get('did_empresa')) < 1)
      $_GET['did_empresa'] = $params['empresa_default']->id_empresa;

    $params['ordenes'] = $this->compras_requisicion_model->getOrdenes(40, false);

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['requisicion'] = true;
    $params['method']     = 'requisicion/';
    $params['titleBread'] = 'Ordenes de Requisición';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/ordenes_requisicion/admin', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Visualiza el formulario para agregar.
   *
   * @return void
   */
  public function agregar()
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
      array('panel/compras_ordenes/agregar_requisicion.js'),
      array('panel/compras_ordenes/areas_requisicion.js'),
    ));

    $this->load->model('compras_requisicion_model');
    $this->load->model('compras_areas_model');
    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar orden de requisición'
    );

    $params['next_folio']    = $this->compras_requisicion_model->folio();
    $params['fecha']         = str_replace(' ', 'T', date("Y-m-d H:i"));
    $params['departamentos'] = $this->compras_requisicion_model->departamentos();
    $params['unidades']      = $this->compras_requisicion_model->unidades();
    $params['almacenes']     = $this->almacenes_model->getAlmacenes(false);

    $this->configAddOrden();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->compras_requisicion_model->agregar();

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/compras_requisicion/agregar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $params['areas'] = $this->compras_areas_model->getTipoAreas();

    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();
    // $this->db
    //   ->select("e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org")
    //   ->from("empresas AS e")
    //   ->where("e.predeterminado", "t")
    //   ->get()
    //   ->row();

    if (isset($_GET['idf']) && $_GET['idf'] !== '')
    {
      $this->load->model('facturacion_model');
      $params['factura'] = $this->facturacion_model->getInfoFactura($_GET['idf']);
      $params['ordenFlete'] = true;
      $params['next_folio'] = $this->compras_requisicion_model->folio('f');
      $params['noHeader'] = true;

      $params['empresa_default'] = new StdClass;
      $params['empresa_default']->id_empresa = $params['factura']['info']->empresa->id_empresa;
      $params['empresa_default']->nombre_fiscal = $params['factura']['info']->empresa->nombre_fiscal;
      $params['empresa_default']->cer_caduca = $params['factura']['info']->empresa->cer_caduca;
      $params['empresa_default']->cfdi_version = $params['factura']['info']->empresa->cfdi_version;
      $params['empresa_default']->cer_org = $params['factura']['info']->empresa->cer_org;

      $this->load->view('panel/header', $params);
      // $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/ordenes_requisicion/agregar', $params);
      $this->load->view('panel/footer');
    }
    else
    {
      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/ordenes_requisicion/agregar', $params);
      $this->load->view('panel/footer');
    }
  }

  /**
   * Visualiza el formulario para modificar una orden.
   *
   * @return void
   */
  public function modificar()
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
      array('panel/compras_ordenes/agregar_requisicion.js'),
      array('panel/compras_ordenes/areas_requisicion.js'),
    ));

    $this->load->model('compras_requisicion_model');
    $this->load->model('almacenes_model');
    $this->load->model('compras_areas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => (isset($_GET['w'])? ($_GET['w']=='c'? 'Orden de compra': 'Orden de requisición'): 'Orden de compra')
    );

    $params['fecha']         = str_replace(' ', 'T', date("Y-m-d H:i"));
    $params['departamentos'] = $this->compras_requisicion_model->departamentos();
    $params['unidades']      = $this->compras_requisicion_model->unidades();
    $params['almacenes']     = $this->almacenes_model->getAlmacenes(false);

    // if ( ! isset($_GET['m']))
    // {
      $this->configAddOrden();
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $response = $this->compras_requisicion_model->actualizar($_GET['id']);

        if ($response['passes'])
        {
          if ($response['autorizado'])
          {
            redirect(base_url('panel/compras_ordenes/?'.MyString::getVarsLink(array('msg', 'mod', 'w')).'&msg='.$response['msg'].'&w=c&print=true'));
          }
          else
          {
            redirect(base_url('panel/compras_requisicion/modificar/?'.MyString::getVarsLink(array('msg')).'&msg='.$response['msg']));
          }
        }
      }
    // }
    // else
    // {
    //   // Si esta autorizando una orden de compra.
    //   if ($_GET['m'] === 'a')
    //   {
    //     $this->compras_requisicion_model->autorizar($_GET['id']);

    //     redirect(base_url('panel/compras_ordenes/modificar/?'.MyString::getVarsLink(array('m')).'&msg=4&print=true'));
    //   }

    //   // Si esta dando la entrada de una orden.
    //   elseif ($_GET['m'] === 'e')
    //   {
    //     $response = $this->compras_requisicion_model->entrada($_GET['id']);

    //     if ($response['msg'] === 5)
    //     {
    //       $printFaltantes = ($response['faltantes']) ? '&print_faltantes=true' : '';
    //       $printFaltantes .= (is_array($response['entrada'])) ? '&entrada='.$response['entrada']['folio'] : '';

    //       redirect(base_url('panel/compras_ordenes/modificar/?'.MyString::getVarsLink(array('m', 'print')).'&msg='.$response['msg'].'&print=t'.$printFaltantes));
    //     }
    //     redirect(base_url('panel/compras_ordenes/modificar/?'.MyString::getVarsLink(array('m', 'print')).'&msg='.$response['msg']));
    //   }
    // }

    $params['areas'] = $this->compras_areas_model->getTipoAreas();
    $params['orden'] = $this->compras_requisicion_model->info($_GET['id'], true);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    if (isset($_GET['print']))
      $params['print'] = true;

    if (isset($_GET['print_faltantes']))
      $params['print_faltantes'] = true;

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/ordenes_requisicion/modificar', $params);
    $this->load->view('panel/footer');
  }

  public function autorizar()
  {
    redirect(base_url('panel/compras_ordenes/modificar/?' . MyString::getVarsLink()));
  }

  public function entrada()
  {
    redirect(base_url('panel/compras_ordenes/modificar/?' . MyString::getVarsLink()));
  }

  public function ver()
  {
    redirect(base_url('panel/compras_ordenes/modificar/?' . MyString::getVarsLink()));
  }

  public function cancelar()
  {
    $this->load->model('compras_requisicion_model');
    $this->compras_requisicion_model->cancelar($_GET['id']);

    if ($_GET['w'] === 'c')
    {
      redirect(base_url('panel/compras_requisicion/?' . MyString::getVarsLink(array('id', 'w')).'&msg=8'));
    }
    else
    {
      redirect(base_url('panel/compras_requisicion/requisicion/?' . MyString::getVarsLink(array('id', 'w')).'&msg=8'));
    }
  }

  public function ligar()
  {
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/util.js'),
      array('general/keyjump.js'),
      array('general/msgbox.js'),
      array('panel/compras_ordenes/agregar.js'),
      array('panel/compras/ligar_ordenes.js'),
    ));

    $this->load->model('proveedores_model');
    $this->load->model('compras_requisicion_model');
    $this->load->model('banco_cuentas_model');

    $this->configAddOrdenLigar();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->compras_requisicion_model->agregarCompra($_POST['proveedorId'], $_POST['empresaId'], $_GET['ids'], $_FILES['xml']);

      if ($res_mdl['passes'])
      {
        $params['frm_errors'] = $this->showMsgs(9);
        $params['id_movimiento'] = (isset($res_mdl['ver_cheque']) ? $res_mdl['id_movimiento'] : '');
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

    $params['proveedor'] = $this->proveedores_model->getProveedorInfo($_GET['idp'], true);
    $params['fecha'] = str_replace(' ', 'T', date("Y-m-d H:i"));

    $ids = explode(',', $_GET['ids']);

    $params['productos'] = array();
    foreach ($ids as $key => $ordenId)
    {
      $orden = $this->compras_requisicion_model->info($ordenId, true, true);

      foreach ($orden['info'][0]->productos as $prod)
      {
        $prod->tipo_orden = $orden['info'][0]->tipo_orden;
        $params['productos'][] = $prod;
      }
    }

    //Cuentas de banco
    $params['cuentas'] = $this->banco_cuentas_model->getCuentas(false);
    //metodos de pago
    $params['metods_pago']  = array(
      array('nombre' => 'Transferencia', 'value' => 'transferencia'),
      array('nombre' => 'Cheque', 'value' => 'cheque'),
      array('nombre' => 'Efectivo', 'value' => 'efectivo'),
      array('nombre' => 'Deposito', 'value' => 'deposito'),
    );
    //Cuentas del proeveedor
    $params['cuentas_proveedor'] = $this->proveedores_model->getCuentas($_GET['idp']);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    if (isset($_GET['rel']))
      $params['reload'] = true;

    $this->load->view('panel/compras_ordenes/ligar_ordenes', $params);
  }

  public function imprimir()
  {
    $this->load->model('compras_requisicion_model');

    if (isset($_GET['p']))
    {
      $this->compras_requisicion_model->print_orden_compra($_GET['id']);
    }
    else
    {
      $this->load->view('panel/compras_ordenes/print_orden_compra');
    }
  }

  public function imprimir_entrada()
  {
    $this->load->model('compras_requisicion_model');
    $this->compras_requisicion_model->imprimir_entrada($_GET['folio'], $_GET['ide']);
  }

  public function email()
  {
    $this->load->model('compras_requisicion_model');

    $response = $this->compras_requisicion_model->email($_GET['id']);

    redirect(base_url('panel/compras_ordenes/?msg=' . $response['msg']));
  }

  public function imprimir_recibo_faltantes()
  {
    $this->load->model('compras_requisicion_model');

    if (isset($_GET['p']))
    {
      $this->compras_requisicion_model->print_recibo_faltantes($_GET['id']);
    }
    else
    {
      $this->load->view('panel/compras_ordenes/print_orden_compra');
    }
  }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

  public function ajax_producto_by_codigo()
  {
    $this->load->model('compras_requisicion_model');

    $producto = $this->compras_requisicion_model->getProductoByCodigoAjax($_GET['ide'], $_GET['tipo'], $_GET['cod']);

    echo json_encode($producto);
  }

  public function ajax_producto()
  {
    $this->load->model('compras_requisicion_model');

    $where = "lower(p.nombre) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%' AND";

    $productos = $this->compras_requisicion_model->getProductoAjax($_GET['ide'], $_GET['tipo'], $where, 'nombre');

    echo json_encode($productos);
  }

  public function ajax_get_producto_all()
  {
    $this->load->model('compras_requisicion_model');

    $where = "lower(p.nombre) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%' AND";

    $productos = $this->compras_requisicion_model->getProductoAjax(null, $_GET['tipo'], $where, 'nombre');

    echo json_encode($productos);
  }

  public function ajax_get_folio()
  {
    $this->load->model('compras_requisicion_model');
    echo $this->compras_requisicion_model->folio($_GET['tipo']);
  }

  public function ajaxGetFactRem()
  {
    $this->load->model('compras_requisicion_model');
    $productos = $this->compras_requisicion_model->getFactRem($_GET);

    echo json_encode($productos);
  }

  public function ajax_get_tipo_cambio()
  {
    $xml_string = file_get_contents("http://www.banxico.org.mx/rsscb/rss?BMXC_canal=fix&BMXC_idioma=es");
    preg_match('/<cb:value frequency(.+)>(\d+.?\d+)<\/cb:value>/', $xml_string, $coincidencias);
    echo (is_numeric($coincidencias[0])? $coincidencias[0]: $coincidencias[2]);
  }

  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddOrden()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'empresaId',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'empresa',
            'label' => '',
            'rules' => ''),
      array('field' => 'id_almacen',
            'label' => 'Almacen',
            'rules' => 'required'),

      array('field' => 'proveedorId1',
            'label' => 'Proveedor',
            'rules' => 'callback_val_proveedor|callback_val_proveedor2'),
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
            'rules' => 'required'),

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