<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class compras_ordenes extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'compras_ordenes/ajax_producto_by_codigo/',
    'compras_ordenes/ajax_producto/',
    'compras_ordenes/ajax_get_folio/',
    'compras_ordenes/ajax_get_producto_all/',

    'compras_ordenes/ligar/',
    'compras_ordenes/imprimir_recibo_faltantes/',
    'compras_ordenes/ajaxGetFactRem/',
    'compras_ordenes/ajaxGetBoletas/',
    'compras_ordenes/ajaxGetCompras/',
    'compras_ordenes/ajaxGetSalidasAlmacen/',
    'compras_ordenes/ajaxGetGastosCaja/',
    'compras_ordenes/imprimir_entrada/',
    'compras_ordenes/ticket/',
    'compras_ordenes/ajax_imprimir_recibo/',
    'compras_ordenes/ajax_cambia_area/',

    'compras_ordenes/rpt_gastos_pdf/',
    'compras_ordenes/rpt_gastos_xls/',
    'compras_ordenes/rpt_ordenes_pdf/',
    'compras_ordenes/rpt_ordenes_xls/',
    'compras_ordenes/activosGastos_pdf/',
    'compras_ordenes/activosGastos_xls/',
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
    $this->load->model('compras_ordenes_model');

    $this->load->model('empresas_model');
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();
    if(!$this->input->get('did_empresa'))
      $_GET['did_empresa'] = $params['empresa_default']->id_empresa;

    $params['ordenes'] = $this->compras_ordenes_model->getOrdenes();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $this->load->model('almacenes_model');
    $fstatus = $this->input->get('fstatus');
    unset($_GET['fstatus']);
    $params['almacenes'] = $this->almacenes_model->getAlmacenes(false);
    $_GET['fstatus'] = $fstatus;

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
    $this->load->model('compras_ordenes_model');

    // Obtiene los datos de la empresa predeterminada.
    $this->load->model('empresas_model');
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();
    if(!$this->input->get('did_empresa'))
      $_GET['did_empresa'] = $params['empresa_default']->id_empresa;

    $params['ordenes'] = $this->compras_ordenes_model->getOrdenes(40, false);

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['requisicion'] = true;
    $params['method']     = 'requisicion/';
    $params['titleBread'] = 'Ordenes de Requisición';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/compras_ordenes/admin', $params);
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
      array('panel/tags.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      // array('general/buttons.toggle.js'),
      array('general/keyjump.js'),
      array('panel/compras_ordenes/agregar.js'),
    ));

    $this->load->model('compras_ordenes_model');
    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar orden de requisición'
    );

    $params['next_folio']    = $this->compras_ordenes_model->folio();
    $params['fecha']         = str_replace(' ', 'T', date("Y-m-d H:i"));
    $params['departamentos'] = $this->compras_ordenes_model->departamentos();
    $params['unidades']      = $this->compras_ordenes_model->unidades();

    $this->configAddOrden();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->compras_ordenes_model->agregar();

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/compras_ordenes/agregar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

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
      $params['next_folio'] = $this->compras_ordenes_model->folio('f');
      $params['noHeader'] = true;

      $params['empresa_default'] = new StdClass;
      $params['empresa_default']->id_empresa = $params['factura']['info']->empresa->id_empresa;
      $params['empresa_default']->nombre_fiscal = $params['factura']['info']->empresa->nombre_fiscal;
      $params['empresa_default']->cer_caduca = $params['factura']['info']->empresa->cer_caduca;
      $params['empresa_default']->cfdi_version = $params['factura']['info']->empresa->cfdi_version;
      $params['empresa_default']->cer_org = $params['factura']['info']->empresa->cer_org;

      $this->load->view('panel/header', $params);
      // $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/compras_ordenes/agregar', $params);
      $this->load->view('panel/footer');
    }
    else
    {
      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/compras_ordenes/agregar', $params);
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
      array('panel/tags.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      // array('general/buttons.toggle.js'),
      array('general/keyjump.js'),
      array('panel/compras_ordenes/agregar.js'),
      array('panel/compras_ordenes/areas_requisicion.js'),
    ));

    $this->load->model('compras_ordenes_model');
    $this->load->model('compras_areas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => (isset($_GET['w'])? ($_GET['w']=='c'? 'Orden de compra': 'Orden de requisición'): 'Orden de compra')
    );

    $usoCfdi = new UsoCfdi();
    $formPago = new FormaPago();

    $params['fecha']         = str_replace(' ', 'T', date("Y-m-d H:i"));
    $params['departamentos'] = $this->compras_ordenes_model->departamentos();
    $params['unidades']      = $this->compras_ordenes_model->unidades();
    $params['usoCfdi']       = $usoCfdi->get()->all();
    $params['formPagos']     = $formPago->get()->all();

    if ( ! isset($_GET['m']))
    {
      $this->configAddOrden();
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $response = $this->compras_ordenes_model->actualizar($_GET['id']);

        if ($response['passes'])
        {
          if (isset($_POST['autorizar']))
          {
            redirect(base_url('panel/compras_ordenes/modificar/?'.MyString::getVarsLink(array('msg', 'mod', 'w')).'&msg='.$response['msg'].'&w=c&print=true'));
          }
          else
          {
            redirect(base_url('panel/compras_ordenes/modificar/?'.MyString::getVarsLink(array('msg')).'&msg='.$response['msg']));
          }
        }
      }
    }
    else
    {
      // Si esta autorizando una orden de compra.
      if ($_GET['m'] === 'a')
      {
        $this->compras_ordenes_model->autorizar($_GET['id']);

        redirect(base_url('panel/compras_ordenes/modificar/?'.MyString::getVarsLink(array('m')).'&msg=4&print=true'));
      }

      // Si esta dando la entrada de una orden.
      elseif ($_GET['m'] === 'e')
      {
        $response = $this->compras_ordenes_model->entrada($_GET['id']);

        if ($response['msg'] === 5)
        {
          $printFaltantes = ($response['faltantes']) ? '&print_faltantes=true' : '';
          $printFaltantes .= (is_array($response['entrada'])) ? '&entrada='.$response['entrada']['folio'] : '';

          redirect(base_url('panel/compras_ordenes/modificar/?'.MyString::getVarsLink(array('m', 'print')).'&msg='.$response['msg'].'&print=t'.$printFaltantes));
        }
        redirect(base_url('panel/compras_ordenes/modificar/?'.MyString::getVarsLink(array('m', 'print')).'&msg='.$response['msg']));
      }
    }

    $params['areas'] = $this->compras_areas_model->getTipoAreas();
    $params['orden'] = $this->compras_ordenes_model->info($_GET['id'], true);
    // echo "<pre>";
    //   var_dump($params['orden']);
    // echo "</pre>";exit;

    $params['impresoras'] = $this->compras_ordenes_model->impresoras();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    if (isset($_GET['print']))
      $params['print'] = true;

    if (isset($_GET['print_faltantes']))
      $params['print_faltantes'] = true;

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/compras_ordenes/modificar', $params);
    $this->load->view('panel/footer');
  }

  public function modificar_ext()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
      array('panel/tags.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      // array('general/buttons.toggle.js'),
      array('general/keyjump.js'),
      array('panel/compras_ordenes/agregar.js'),
      array('panel/compras_ordenes/areas_requisicion.js'),
    ));

    $this->load->model('compras_ordenes_model');
    $this->load->model('compras_areas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Orden de compra'
    );


    $params['fecha']         = str_replace(' ', 'T', date("Y-m-d H:i"));

    $this->configAddOrdenExt();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $response = $this->compras_ordenes_model->actualizarExt($_GET['id'], $_POST);

      if ($response['passes'])
      {
        if (isset($_POST['autorizar']))
        {
          redirect(base_url('panel/compras_ordenes/modificar_ext/?'.MyString::getVarsLink(array('msg', 'mod', 'w')).'&msg='.$response['msg'].'&reload=true'));
        }
        else
        {
          redirect(base_url('panel/compras_ordenes/modificar_ext/?'.MyString::getVarsLink(array('msg')).'&msg='.$response['msg']));
        }
      }
    }

    $params['orden'] = $this->compras_ordenes_model->info($_GET['id'], true);
    // echo "<pre>";
    //   var_dump($params['orden']);
    // echo "</pre>";exit;


    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    if (isset($_GET['reload']))
      $params['reload'] = true;

    $this->load->view('panel/compras_ordenes/modificar_ext', $params);
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
    $this->load->model('compras_ordenes_model');
    $this->compras_ordenes_model->cancelar($_GET['id']);

    if ($_GET['w'] === 'c')
    {
      redirect(base_url('panel/compras_ordenes/?' . MyString::getVarsLink(array('id', 'w')).'&msg=8'));
    }
    else
    {
      redirect(base_url('panel/compras_ordenes/requisicion/?' . MyString::getVarsLink(array('id', 'w')).'&msg=8'));
    }
  }

  public function ajax_cambia_area()
  {
    if (isset($_GET['id_orden']{0}) && isset($_GET['id_area']{0}) && isset($_GET['num_row']{0})) {
      $this->load->model('compras_ordenes_model');
      $this->compras_ordenes_model->actualizaArea($_GET);
    }
  }

  public function ligar()
  {
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      array('general/keyjump.js'),
      array('general/msgbox.js'),
      array('panel/compras_ordenes/agregar.js'),
      array('panel/compras/ligar_ordenes.js'),
    ));

    $this->load->model('proveedores_model');
    $this->load->model('compras_ordenes_model');
    $this->load->model('banco_cuentas_model');

    $this->configAddOrdenLigar();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->compras_ordenes_model->agregarCompra($_POST['proveedorId'], $_POST['empresaId'],
        $_GET['ids'], (isset($_FILES['xml'])? $_FILES['xml']: null));

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
      $orden = $this->compras_ordenes_model->info($ordenId, true, true);

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
    $this->load->model('compras_ordenes_model');

    if (isset($_GET['p']))
    {
      $this->compras_ordenes_model->print_orden_compra($_GET['id']);
    }
    else
    {
      $this->load->view('panel/compras_ordenes/print_orden_compra');
    }
  }

  public function ticket()
  {
    $this->load->model('compras_ordenes_model');

    if (isset($_GET['p']))
    {
      $this->compras_ordenes_model->print_orden_compra_ticket($_GET['id']);
    }
    else
    {
      $this->load->view('panel/compras_ordenes/print_orden_compra_ticket');
    }
  }

  public function imprimir_entrada()
  {
    $this->load->model('compras_ordenes_model');
    $this->compras_ordenes_model->imprimir_entrada($_GET['folio'], $_GET['ide']);
  }
  public function ajax_imprimir_recibo()
  {
    $this->load->model('compras_ordenes_model');
    $this->compras_ordenes_model->imprimir_entradatxt($_GET['folio'], $_GET['ide'], $_GET['ruta']);
  }

  public function email()
  {
    $this->load->model('compras_ordenes_model');

    $response = $this->compras_ordenes_model->email($_GET['id']);

    redirect(base_url('panel/compras_ordenes/?msg=' . $response['msg']));
  }

  public function imprimir_recibo_faltantes()
  {
    $this->load->model('compras_ordenes_model');

    if (isset($_GET['p']))
    {
      $this->compras_ordenes_model->print_recibo_faltantes($_GET['id']);
    }
    else
    {
      $this->load->view('panel/compras_ordenes/print_orden_compra');
    }
  }

  /**
   * Reportes
   * @return [type] [description]
   */
  public function rpt_gastos()
  {
    $this->carabiner->css(array(
      array('libs/jquery.treeview.css', 'screen')
    ));
    $this->carabiner->js(array(
      array('libs/jquery.treeview.js'),
      array('panel/productos_salidas/rpt_gastos.js'),
    ));

    $this->load->model('compras_areas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte gastos');

    // $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    $this->compras_areas_model->class_treeAreas = 'treeviewcustom';
    $params['vehiculos'] = $this->compras_areas_model->getFrmAreas();

    $this->load->view('panel/header',$params);
    // $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/compras_ordenes/rpt_gastos',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_gastos_pdf()
  {
    $this->load->model('compras_ordenes_model');
    $this->compras_ordenes_model->rpt_gastos_pdf();
  }
  public function rpt_gastos_xls()
  {
    $this->load->model('compras_ordenes_model');
    $this->compras_ordenes_model->rpt_gastos_xls();
  }


  public function activosGastos()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_activos.js'),
    ));
    $this->carabiner->css(array(
      array('panel/tags.css', 'screen'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');
    $this->load->model('productos_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Compras por Producto');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    $params['familias'] = $this->productos_model->getFamiliasAjax(['id_empresa' => $params['empresa']->id_empresa]);

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/compras/rptactivos',$params);
    $this->load->view('panel/footer',$params);
  }
  public function activosGastos_pdf(){
    $this->load->model('compras_ordenes_model');
    $this->compras_ordenes_model->getActivosGastosPdf();
  }
  public function activosGastos_xls(){
    $this->load->model('compras_ordenes_model');
    $this->compras_ordenes_model->getActivosGastosXls();
  }


  public function rpt_ordenes()
  {
    $this->carabiner->js(array(
      array('general/keyjump.js'),
      array('panel/banco/rpt_rie.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'REPORTE DE ORDEN DE COMPRA ACUMULADO POR EMPRESA'
    );

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/compras_ordenes/rpt_ordenes', $params);
    $this->load->view('panel/footer');
  }
  /**
   * Procesa los datos para mostrar el reporte ENTRADA DE FRUTA
   * @return void
   */
  public function rpt_ordenes_pdf()
  {
    if(isset($_GET['ffecha1']))
    {
      $this->load->model('compras_ordenes_model');
      $this->compras_ordenes_model->rpt_ordenes_pdf();
    }
  }
  public function rpt_ordenes_xls()
  {
    if(isset($_GET['ffecha1']))
    {
      $this->load->model('compras_ordenes_model');
      $this->compras_ordenes_model->rpt_ordenes_xls();
    }
  }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

  public function ajax_producto_by_codigo()
  {
    $this->load->model('compras_ordenes_model');

    $id_almacen = isset($_GET['id_almacen']{0}) && $_GET['id_almacen'] > 0? $_GET['id_almacen']: 1;
    $producto = $this->compras_ordenes_model->getProductoByCodigoAjax($_GET['ide'], $_GET['tipo'], $_GET['cod'], $id_almacen);

    echo json_encode($producto);
  }

  public function ajax_producto()
  {
    $this->load->model('compras_ordenes_model');

    $where = "lower(p.nombre) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%'
                ".(!empty($_GET['id_familia'])? "AND p.id_familia = '{$_GET['id_familia']}' ": '')."
                AND".(strlen($_GET['term'])<3? " 1 = 2 AND ": "");

    $id_almacen = isset($_GET['id_almacen']{0}) && $_GET['id_almacen'] > 0? $_GET['id_almacen']: 1;
    $productos = $this->compras_ordenes_model->getProductoAjax($_GET['ide'],
      (isset($_GET['tipo'])? $_GET['tipo']: ''), $where, 'nombre', $id_almacen);

    echo json_encode($productos);
  }

  public function ajax_get_producto_all()
  {
    $this->load->model('compras_ordenes_model');

    $where = "lower(p.nombre) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%' AND";

    $productos = $this->compras_ordenes_model->getProductoAjax(null, $_GET['tipo'], $where, 'nombre');

    echo json_encode($productos);
  }

  public function ajax_get_folio()
  {
    $this->load->model('compras_ordenes_model');
    echo $this->compras_ordenes_model->folio($_GET['tipo']);
  }

  public function ajaxGetFactRem()
  {
    $this->load->model('compras_ordenes_model');
    $productos = $this->compras_ordenes_model->getFactRem($_GET);

    echo json_encode($productos);
  }

  public function ajaxGetCompras()
  {
    $this->load->model('compras_ordenes_model');
    $productos = $this->compras_ordenes_model->getCompras($_GET);

    echo json_encode($productos);
  }

  public function ajaxGetSalidasAlmacen()
  {
    $this->load->model('productos_salidas_model');
    $productos = $this->productos_salidas_model->getSalidasAjax($_GET);

    echo json_encode($productos);
  }

  public function ajaxGetGastosCaja()
  {
    $this->load->model('caja_chica_model');
    $productos = $this->caja_chica_model->getGastosAjax($_GET);

    echo json_encode($productos);
  }

  public function ajaxGetBoletas()
  {
    $this->load->model('compras_ordenes_model');
    $productos = $this->compras_ordenes_model->getBoletas($_GET);

    echo json_encode($productos);
  }

  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddOrden()
  {
    $this->load->library('form_validation');

    $valGasto = $valFlete = false;
    $tipoOrden = $this->input->post('tipoOrden');
    if ($tipoOrden == 'd' || $tipoOrden == 'oc' || $tipoOrden == 'f' && $tipoOrden == 'a') {
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

      array('field' => 'proveedorId',
            'label' => 'Proveedor',
            'rules' => 'required'),
      array('field' => 'proveedor',
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

      array('field' => 'areaId[]',
            'label' => 'Cultivo',
            'rules' => ($valGasto? 'required|numeric': '')),
      array('field' => 'areaText[]',
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
      array('field' => 'activoId[]',
            'label' => 'Activo',
            'rules' => ($valGasto && !$valFlete? 'numeric': '')),
      array('field' => 'activoText[]',
            'label' => 'Activo',
            'rules' => ($valGasto && !$valFlete? '': '')),

      array('field' => 'totalLetra',
            'label' => '',
            'rules' => ''),
      array('field' => 'codigo[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'tipo_cambio[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'concepto[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'productoId[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'presentacion[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'presentacionName[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'unidad[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'cantidad[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'faltantes[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'valorUnitario[]',
            'label' => '',
            'rules' => ''),
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
      array('field' => 'retTotal[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'importe[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'total[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'observacion[]',
            'label' => '',
            'rules' => 'max_length[200]'),

      array('field' => 'totalImporte',
            'label' => 'Subtotal',
            'rules' => ''),
      array('field' => 'totalImpuestosTrasladados',
            'label' => 'IVA',
            'rules' => ''),
      array('field' => 'totalIeps',
            'label' => 'IEPS',
            'rules' => ''),
      array('field' => 'totalRetencion',
            'label' => 'RET.',
            'rules' => ''),
      array('field' => 'totalOrden',
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
      $rules[] = array('field' => 'remfacs',
                    'label' => 'Factura/Remision',
                    'rules' => 'required');
      $rules[] = array('field' => 'remfacs_folio',
                    'label' => 'Factura/Remision',
                    'rules' => '');
    }

    $this->form_validation->set_rules($rules);
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
      array('field' => 'fecha_factura',
            'label' => 'Fecha Factura',
            'rules' => 'required'),

      array('field' => 'condicionPago',
            'label' => 'Condicion de Pago',
            'rules' => 'required'),
      array('field' => 'plazoCredito',
            'label' => 'Plazo Credito',
            'rules' => ''),

      array('field' => 'tipo_documento',
            'label' => 'Tipo Documento',
            'rules' => 'required'),

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
      array('field' => 'uuid',
            'label' => 'UUID',
            'rules' => 'callback_uuid_check'),
    );

    $this->form_validation->set_rules($rules);
  }

  public function configAddOrdenExt()
  {
    $this->load->library('form_validation');

    $rules = [];

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
      $query = $this->db->query("SELECT Count(id_compra) AS num FROM compras WHERE status <> 'ca' AND uuid = '{$uuid}'")->row();

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