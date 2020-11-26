<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class productos_regreso extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'productos_regreso/imprimir/',
    'productos_regreso/rpt_gastos_pdf/',
    'productos_regreso/rpt_gastos_xls/',
    'productos_regreso/imprimirticket/',
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
      'titulo' => 'Administración de Productos Regresados'
    );

    $this->load->library('pagination');
    $this->load->model('compras_ordenes_model');

    $params['ordenes'] = $this->compras_ordenes_model->getOrdenes(100, true, true);

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d"));

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/productos_regreso/admin', $params);
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
      array('general/keyjump.js'),
      array('panel/productos_regreso/agregar.js'),
      array('panel/compras_ordenes/areas_requisicion.js'),
    ));

    $this->load->model('almacenes_model');
    $this->load->model('productos_regreso_model');
    $this->load->model('compras_areas_model');
    $this->load->model('compras_ordenes_model');
    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Regresar productos'
    );

    $this->configAddRegreso();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->productos_regreso_model->agregar();

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/productos_regreso/agregar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg'].'&print='.$res_mdl['id_orden'] ));
      }
    }

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
    $params['next_folio'] = $this->compras_ordenes_model->folio('p', true);
    $params['fecha']      = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['areas'] = $this->compras_areas_model->getTipoAreas();

    //imprimir
    $params['prints'] = isset($_GET['print'])? $_GET['print']: '';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/productos_regreso/agregar', $params);
    $this->load->view('panel/footer');
  }

  public function ver()
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
      array('general/keyjump.js'),
      array('panel/productos_regreso/agregar.js'),
    ));

    $this->load->model('compras_ordenes_model');
    $this->load->model('almacenes_model');
    $this->load->model('productos_regreso_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Ver productos regresados'
    );

    $this->configModSalida();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->productos_regreso_model->modificarProductos($_GET['id']);

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/productos_regreso/ver/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    $params['orden']     = $this->compras_ordenes_model->info($_GET['id'], true);
    $params['modificar'] = $this->usuarios_model->tienePrivilegioDe('', 'productos_regreso/modificar/');
    $params['almacenes'] = $this->almacenes_model->getAlmacenes(false);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/productos_regreso/ver', $params);
    $this->load->view('panel/footer');
  }

  public function cancelar()
  {
    $this->load->model('productos_regreso_model');
    $this->productos_regreso_model->cancelar($_GET['id']);

    redirect(base_url('panel/productos_regreso/?' . MyString::getVarsLink(array('id')).'&msg=4'));
  }

  public function imprimir()
  {
    redirect(base_url('panel/compras_ordenes/ticket/?' . MyString::getVarsLink([])));
  }


  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddRegreso()
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

      array('field' => 'solicito',
            'label' => 'Regreso',
            'rules' => 'required|max_length[130]'),

      array('field' => 'conceptoSalida',
            'label' => 'Concepto',
            'rules' => 'max_length[200]'),

      array('field' => 'fecha',
            'label' => 'Fecha',
            'rules' => 'required'),
      array('field' => 'folio',
            'label' => 'Folio',
            'rules' => 'required'),


      array('field' => 'tipoProducto[]',
            'label' => '',
            'rules' => 'required'),
      array('field' => 'precioUnit[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'codigo[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'concepto[]',
            'label' => 'Productos',
            'rules' => 'required'),
      array('field' => 'productoId[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'cantidad[]',
            'label' => 'Cantidad',
            'rules' => 'required|greater_than[0]')
    );

    $this->form_validation->set_rules($rules);
  }

  public function productos_existencia($str)
  {
    $this->load->model('inventario_model');
    $productos = array();
    if (isset($_POST['productoId'])) {
      foreach ($_POST['productoId'] as $key => $value) {
        if ($_POST['tipoProducto'][$key] == 'p') {
          // id_almacen
          $item = $this->inventario_model->getEPUData($value, $this->input->post('id_almacen'));
          $existencia = MyString::float( $item[0]->saldo_anterior+$item[0]->entradas-$item[0]->salidas );
          if ( MyString::float($existencia-$_POST['cantidad'][$key]) < 0) {
            $productos[] = str_replace('%', '%%', $item[0]->nombre_producto.' ('.($existencia-$_POST['cantidad'][$key]).')');
          }
        }
      }
    }
    if (count($productos)>0) {
      $this->form_validation->set_message('productos_existencia', 'No hay existencia suficiente en: '.implode(', ', $productos));
      return FALSE;
    }
    return true;
  }

  public function configModSalida()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'cantidad[]',
            'label' => 'Cantidad',
            'rules' => 'required| [0]')
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
        $txt = 'Se regresaron los productos correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'Se cancelo el regreso de productos correctamente.';
        $icono = 'success';
      break;
      case 5:
        $txt = 'Se modifico el regreso de productos correctamente.';
        $icono = 'success';
      break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}