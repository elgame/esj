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
      'titulo' => 'Administración de Ordenes de Compra'
    );

    $this->load->library('pagination');
    $this->load->model('compras_ordenes_model');

    $params['ordenes'] = $this->compras_ordenes_model->getOrdenes();

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
    $this->load->model('compras_ordenes_model');

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

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar orden de compra'
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
        redirect(base_url('panel/compras_ordenes/agregar/?'.String::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->db
      ->select("e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org")
      ->from("empresas AS e")
      ->where("e.predeterminado", "t")
      ->get()
      ->row();

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/compras_ordenes/agregar', $params);
    $this->load->view('panel/footer');
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
      array('panel/compras_ordenes/agregar.js'),
    ));

    $this->load->model('compras_ordenes_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Orden de compra'
    );

    $params['fecha']         = str_replace(' ', 'T', date("Y-m-d H:i"));
    $params['departamentos'] = $this->compras_ordenes_model->departamentos();
    $params['unidades']      = $this->compras_ordenes_model->unidades();

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
            redirect(base_url('panel/compras_ordenes/modificar/?'.String::getVarsLink(array('msg', 'mod', 'w')).'&msg='.$response['msg'].'&w=c'));
          }
          else
          {
            redirect(base_url('panel/compras_ordenes/modificar/?'.String::getVarsLink(array('msg')).'&msg='.$response['msg']));
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

        redirect(base_url('panel/compras_ordenes/modificar/?'.String::getVarsLink(array('m')).'&msg=4'));
      }

      // Si esta dando la entrada de una orden.
      elseif ($_GET['m'] === 'e')
      {
        $response = $this->compras_ordenes_model->entrada($_GET['id']);

        if ($response['msg'] === 5)
        {
          $printFaltantes = ($response['faltantes']) ? '&print_faltantes=true' : '';

          redirect(base_url('panel/compras_ordenes/modificar/?'.String::getVarsLink(array('m', 'print')).'&msg='.$response['msg'].'&print=t'.$printFaltantes));
        }
        redirect(base_url('panel/compras_ordenes/modificar/?'.String::getVarsLink(array('m', 'print')).'&msg='.$response['msg']));
      }
    }

    $params['orden'] = $this->compras_ordenes_model->info($_GET['id'], true);

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

  public function autorizar()
  {
    redirect(base_url('panel/compras_ordenes/modificar/?' . String::getVarsLink()));
  }

  public function entrada()
  {
    redirect(base_url('panel/compras_ordenes/modificar/?' . String::getVarsLink()));
  }

  public function ver()
  {
    redirect(base_url('panel/compras_ordenes/modificar/?' . String::getVarsLink()));
  }

  public function cancelar()
  {
    $this->load->model('compras_ordenes_model');
    $this->compras_ordenes_model->cancelar($_GET['id']);

    if ($_GET['w'] === 'c')
    {
      redirect(base_url('panel/compras_ordenes/?' . String::getVarsLink(array('id', 'w')).'&msg=8'));
    }
    else
    {
      redirect(base_url('panel/compras_ordenes/requisicion/?' . String::getVarsLink(array('id', 'w')).'&msg=8'));
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
    ));

    $this->load->model('proveedores_model');
    $this->load->model('compras_ordenes_model');

    $this->configAddOrdenLigar();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->compras_ordenes_model->agregarCompra($_POST['proveedorId'], $_POST['empresaId'], $_GET['ids']);

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/compras_ordenes/ligar/?'.String::getVarsLink(array('msg')).'&msg=9&rel=t'));
      }
    }

    $params['proveedor'] = $this->proveedores_model->getProveedorInfo($_GET['idp'], true);
    $params['fecha'] = str_replace(' ', 'T', date("Y-m-d H:i"));

    $ids = explode(',', $_GET['ids']);

    $params['productos'] = array();
    foreach ($ids as $key => $ordenId)
    {
      $orden = $this->compras_ordenes_model->info($ordenId, true);

      foreach ($orden['info'][0]->productos as $prod)
      {
        $prod->tipo_orden = $orden['info'][0]->tipo_orden;
        $params['productos'][] = $prod;
      }
    }

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

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

  public function ajax_producto_by_codigo()
  {
    $this->load->model('compras_ordenes_model');

    $producto = $this->compras_ordenes_model->getProductoByCodigoAjax($_GET['ide'], $_GET['tipo'], $_GET['cod']);

    echo json_encode($producto);
  }

  public function ajax_producto()
  {
    $this->load->model('compras_ordenes_model');

    $where = "lower(p.nombre) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%' AND";

    $productos = $this->compras_ordenes_model->getProductoAjax($_GET['ide'], $_GET['tipo'], $where, 'nombre');

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

      array('field' => 'proveedorId',
            'label' => 'Proveedor',
            'rules' => 'required'),
      array('field' => 'proveedor',
            'label' => '',
            'rules' => ''),

      array('field' => 'solicitoId',
            'label' => 'Solicito',
            'rules' => ''),
      array('field' => 'solicito',
            'label' => '',
            'rules' => ''),

      array('field' => 'departamento',
            'label' => 'Departamento',
            'rules' => 'required'),

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

      array('field' => 'totalLetra',
            'label' => '',
            'rules' => ''),
      array('field' => 'codigo[]',
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
      array('field' => 'retTotal[]',
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
            'label' => 'RET.',
            'rules' => ''),
      array('field' => 'totalOrden',
            'label' => 'Total',
            'rules' => 'greater_than[0]'),
    );

    $this->form_validation->set_rules($rules);
  }

  public function configAddOrdenLigar()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'proveedorId',
            'label' => 'Proveedor',
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
            'rules' => 'greater_than[0]'),
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
            'rules' => ''),
      array('field' => 'totalOrden',
            'label' => 'Total',
            'rules' => 'greater_than[0]'),
    );

    $this->form_validation->set_rules($rules);
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
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}