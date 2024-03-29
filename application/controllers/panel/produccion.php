<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class produccion extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'produccion/inventario_pdf/',
    'produccion/inventario_xls/',
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
      array('panel/produccion/admin.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Ordenes de produccion'
    );

    $this->load->library('pagination');
    $this->load->model('produccion_model');

    $params['produccion'] = $this->produccion_model->getProduccion();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d"));

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/produccion/admin', $params);
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
      array('panel/produccion/agregar.js'),
      array('panel/compras_ordenes/areas_requisicion.js'),
    ));

    $this->load->model('almacenes_model');
    $this->load->model('produccion_model');
    $this->load->model('compras_areas_model');
    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar orden de produccion'
    );

    $this->configAddRegreso();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->produccion_model->agregar();

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/produccion/agregar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg'].'&print='.$res_mdl['id_salida'] ));
      }
    }

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
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
    $this->load->view('panel/produccion/agregar', $params);
    $this->load->view('panel/footer');
  }

  public function nivelar()
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
      array('panel/produccion/nivelar.js'),
      array('panel/compras_ordenes/areas_requisicion.js'),
    ));

    $this->load->model('produccion_model');
    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Nivelar producción'
    );

    $this->configNivelar();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->produccion_model->nivelar();

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/produccion/nivelar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg'].'&print=' ));
      }
    }

    $params['fecha']      = str_replace(' ', 'T', date("Y-m-d H:i"));

    //imprimir
    $params['prints'] = isset($_GET['print'])? $_GET['print']: '';

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/produccion/nivelar', $params);
    $this->load->view('panel/footer');
  }

  public function cancelar()
  {
    $this->load->model('produccion_model');
    $this->produccion_model->cancelar($_GET['id']);

    redirect(base_url('panel/produccion/?' . MyString::getVarsLink(array('id')).'&msg=4'));
  }


  /**
   * Inventario
   * @return [type] [description]
   */
  public function inventario()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/produccion/rpt_inventarios.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('productos_model');
    $this->load->model('almacenes_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Inventario de produccion');

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
    $params['data'] = $this->productos_model->getFamilias(false, 'p');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/produccion/inventario',$params);
    $this->load->view('panel/footer',$params);
  }
  public function inventario_pdf(){
    $this->load->model('produccion_model');
    $this->produccion_model->getInventarioPdf();
  }
  public function inventario_xls(){
    $this->load->model('produccion_model');
    $this->produccion_model->getInventarioXls();
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
            'rules' => 'required|callback_productos_existencia'),
      array('field' => 'empresa',
            'label' => '',
            'rules' => ''),

      array('field' => 'clasificacion',
            'label' => 'Clasificacion',
            'rules' => 'required'),
      array('field' => 'id_clasificacion',
            'label' => 'Clasificacion',
            'rules' => 'required'),
      array('field' => 'cantidad',
            'label' => 'Cantidad',
            'rules' => 'required'),
      array('field' => 'costo_adicional',
            'label' => 'Costo adicional',
            'rules' => ''),

      // array('field' => 'id_almacen',
      //       'label' => 'Almacen',
      //       'rules' => 'required'),

      array('field' => 'conceptoSalida',
            'label' => 'Concepto',
            'rules' => 'max_length[200]'),

      array('field' => 'fecha_produccion',
            'label' => 'Produccion',
            'rules' => 'required'),
      // array('field' => 'folio',
      //       'label' => 'Folio',
      //       'rules' => 'required'),


      array('field' => 'tipoProducto[]',
            'label' => '',
            'rules' => 'required'),
      array('field' => 'precioUnit[]',
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

  public function configNivelar()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'empresaId',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'empresa',
            'label' => '',
            'rules' => ''),
      array('field' => 'fecha',
            'label' => 'Produccion',
            'rules' => 'required'),

      ['field' => 'concepto[]',
        'label' => '',
        'rules' => 'required'],
      ['field' => 'productoId[]',
        'label' => '',
        'rules' => 'required'],
      ['field' => 'cantidad[]',
        'label' => '',
        'rules' => ''],
      ['field' => 'existencia[]',
        'label' => '',
        'rules' => ''],
      ['field' => 'newexistencia[]',
        'label' => '',
        'rules' => ''],
      ['field' => 'tipoMovimiento[]',
        'label' => '',
        'rules' => ''],
      ['field' => 'precioUnit[]',
        'label' => '',
        'rules' => ''],
      ['field' => 'importe[]',
        'label' => '',
        'rules' => ''],
    );

    $this->form_validation->set_rules($rules);
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
        $txt = 'Se registraron correctamente los productos.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'Se cancelo correctamente.';
        $icono = 'success';
      break;
      case 5:
        $txt = 'Se modifico el regreso de productos correctamente.';
        $icono = 'success';
      break;
      case 6:
        $txt = 'Se nivelaron los productos correctamente.';
        $icono = 'success';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}