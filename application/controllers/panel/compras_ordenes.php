<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class compras_ordenes extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'compras_ordenes/ajax_producto_by_codigo/',
    'compras_ordenes/ajax_producto/',

    'bascula/show_view_agregar_empresa/',

    'bascula/rde_pdf/',
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

    $params['next_folio']    = $this->compras_ordenes_model->folio('en');
    $params['fecha']         = str_replace(' ', 'T', date("Y-m-d H:i"));
    $params['departamentos'] = $this->compras_ordenes_model->departamentos();
    $params['unidades']      = $this->compras_ordenes_model->unidades();

    $this->configAddModBascula();
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
      $this->configAddModBascula();
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $response = $this->compras_ordenes_model->actualizar($_GET['id']);

        if ($response['passes'])
        {
          redirect(base_url('panel/compras_ordenes/modificar/?'.String::getVarsLink(array('msg')).'&msg='.$response['msg']));
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

        redirect(base_url('panel/compras_ordenes/modificar/?'.String::getVarsLink(array('m')).'&msg='.$response['msg']));
      }
    }

    $params['orden'] = $this->compras_ordenes_model->info($_GET['id'], true);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/compras_ordenes/modificar', $params);
    $this->load->view('panel/footer');
  }

  public function autorizar()
  {
    redirect(base_url('panel/compras_ordenes/modificar/?id=' . $_GET['id']));
  }

  public function entrada()
  {
    redirect(base_url('panel/compras_ordenes/modificar/?id=' . $_GET['id']));
  }

  public function ver()
  {
    redirect(base_url('panel/compras_ordenes/modificar/?id=' . $_GET['id']));
  }

  public function cancelar()
  {
    $this->load->model('compras_ordenes_model');
    $this->compras_ordenes_model->cancelar($_GET['id']);

    redirect(base_url('panel/compras_ordenes/?msg=8'));
  }

  /*
   |------------------------------------------------------------------------
   | Ajax
   |------------------------------------------------------------------------
   */

  public function ajax_producto_by_codigo()
  {
    $this->load->model('compras_ordenes_model');

    $where = "lower(p.codigo) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%' AND";

    $productos = $this->compras_ordenes_model->getProductoAjax($_GET['ide'], $_GET['tipo'], $where);

    echo json_encode($productos);
  }

  public function ajax_producto()
  {
    $this->load->model('compras_ordenes_model');

    $where = "lower(p.nombre) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%' AND";

    $productos = $this->compras_ordenes_model->getProductoAjax($_GET['ide'], $_GET['tipo'], $where, 'nombre');

    echo json_encode($productos);
  }

  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddModBascula()
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
      array('field' => 'valorUnitario[]',
            'label' => '',
            'rules' => ''),
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
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}