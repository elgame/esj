<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class productos_salidas extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'productos_salidas/rpt_gastos_pdf/',
    'productos_salidas/rpt_gastos_xls/',
    'productos_salidas/imprimirticket/',
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
      'titulo' => 'Administración de Salidas de Productos'
    );

    $this->load->library('pagination');
    $this->load->model('productos_salidas_model');

    $params['salidas'] = $this->productos_salidas_model->getSalidas();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d"));

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/productos_salidas/admin', $params);
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
      array('panel/productos_salidas/agregar.js'),
      array('panel/compras_ordenes/areas_requisicion.js'),
    ));

    $this->load->model('almacenes_model');
    $this->load->model('productos_salidas_model');
    $this->load->model('compras_areas_model');
    $this->load->model('empresas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar salida'
    );

    $this->configAddSalida();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->productos_salidas_model->agregar();
      $this->productos_salidas_model->agregarProductos($res_mdl['id_salida']);

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/productos_salidas/agregar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg'].'&print='.$res_mdl['id_salida'] ));
      }
    }

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
    $params['next_folio'] = $this->productos_salidas_model->folio();
    $params['fecha']      = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['areas'] = $this->compras_areas_model->getTipoAreas();

    //imprimir
    $params['prints'] = isset($_GET['print'])? $_GET['print']: '';

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

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/productos_salidas/agregar', $params);
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
      array('panel/productos_salidas/agregar.js'),
    ));

    $this->load->model('productos_salidas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Ver salida'
    );

    $this->configModSalida();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->productos_salidas_model->modificar($_GET['id']);
      $res_mdl = $this->productos_salidas_model->modificarProductos($_GET['id']);

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/productos_salidas/ver/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    $params['salida'] = $this->productos_salidas_model->info($_GET['id'], true);
    $params['modificar'] = $this->usuarios_model->tienePrivilegioDe('', 'productos_salidas/modificar/');

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/productos_salidas/ver', $params);
    $this->load->view('panel/footer');
  }

  public function cancelar()
  {
    $this->load->model('productos_salidas_model');
    $this->productos_salidas_model->cancelar($_GET['id']);

    redirect(base_url('panel/productos_salidas/?' . MyString::getVarsLink(array('id')).'&msg=4'));
  }

  public function imprimir()
  {
    $this->load->model('productos_salidas_model');

    if (isset($_GET['id']))
    {
      $this->productos_salidas_model->print_orden_compra($_GET['id']);
    }
  }
  public function imprimirticket()
  {
    $this->load->model('productos_salidas_model');

    if (isset($_GET['id']))
    {
      $this->productos_salidas_model->imprimir_salidaticket($_GET['id']);
    }
  }


  /**
   * REPORTES
   * ***********************************
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
    $this->load->view('panel/productos_salidas/rpt_gastos',$params);
    $this->load->view('panel/footer',$params);
  }
  public function rpt_gastos_pdf()
  {
    $this->load->model('productos_salidas_model');
    $this->productos_salidas_model->rpt_gastos_pdf();
  }
  public function rpt_gastos_xls()
  {
    $this->load->model('productos_salidas_model');
    $this->productos_salidas_model->rpt_gastos_xls();
  }



  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddSalida()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'empresaId',
            'label' => 'Empresa',
            'rules' => 'required|callback_productos_existencia'),
      array('field' => 'empresa',
            'label' => '',
            'rules' => ''),

      array('field' => 'id_almacen',
            'label' => 'Almacen',
            'rules' => 'required'),
      array('field' => 'tid_almacen',
            'label' => 'Transferir',
            'rules' => ''),

      array('field' => 'solicito',
            'label' => 'Solicito',
            'rules' => 'required|max_length[130]'),
      array('field' => 'recibio',
            'label' => 'Recibio',
            'rules' => 'required|max_length[130]'),
      array('field' => 'ftrabajador',
            'label' => 'Trabajador',
            'rules' => ''),
      array('field' => 'fid_trabajador',
            'label' => 'Trabajador',
            'rules' => ''),

      array('field' => 'conceptoSalida',
            'label' => 'Concepto',
            'rules' => 'max_length[200]'),

      array('field' => 'fecha',
            'label' => 'Fecha',
            'rules' => 'required'),
      array('field' => 'folio',
            'label' => 'Folio',
            'rules' => 'required'),

      array('field' => 'no_receta',
            'label' => 'No receta',
            'rules' => 'max_length[20]'),
      array('field' => 'etapa',
            'label' => 'Etapa',
            'rules' => 'max_length[30]'),
      array('field' => 'ranchoC',
            'label' => 'Rancho',
            'rules' => 'required'),
      array('field' => 'ranchoC_id',
            'label' => 'Rancho',
            'rules' => 'required|numeric'),
      array('field' => 'centro_costo',
            'label' => 'Centro de costo',
            'rules' => 'required'),
      array('field' => 'centro_costo_id',
            'label' => 'Centro de costo',
            'rules' => 'required|numeric'),
      array('field' => 'hectareas',
            'label' => 'Hectareas',
            'rules' => 'numeric'),
      array('field' => 'grupo',
            'label' => 'Grupo',
            'rules' => 'max_length[20]'),
      array('field' => 'no_secciones',
            'label' => 'No melgas/seccion',
            'rules' => 'max_length[20]'),
      array('field' => 'dias_despues_de',
            'label' => 'Dias despues de',
            'rules' => 'numeric'),
      array('field' => 'metodo_aplicacion',
            'label' => 'Metodo de aplicacion',
            'rules' => 'max_length[30]'),
      array('field' => 'ciclo',
            'label' => 'Ciclo',
            'rules' => 'max_length[20]'),
      array('field' => 'tipo_aplicacion',
            'label' => 'Tipo de aplicacion',
            'rules' => 'max_length[20]'),
      array('field' => 'observaciones',
            'label' => 'Observaciones',
            'rules' => 'max_length[220]'),
      array('field' => 'fecha_aplicacion',
            'label' => 'Fecha de aplicacion',
            'rules' => ''),


      // array('field' => 'codigoArea[]',
      //       'label' => 'Codigo Area',
      //       'rules' => 'required'),
      // array('field' => 'codigoAreaId[]',
      //       'label' => 'Codigo Area',
      //       'rules' => 'required'),
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

    if ($this->input->post('tid_almacen') == '') {
      $rules[] = array('field' => 'areaId',
            'label' => 'Cultivo',
            'rules' => 'required|numeric');
      $rules[] = array('field' => 'area',
            'label' => 'Cultivo',
            'rules' => 'required');
      $rules[] = array('field' => 'ranchoId',
            'label' => 'Rancho',
            'rules' => 'required|numeric');
      $rules[] = array('field' => 'rancho',
            'label' => 'Rancho',
            'rules' => 'required');
      $rules[] = array('field' => 'centroCostoId',
            'label' => 'Centro de costo',
            'rules' => 'required|numeric');
      $rules[] = array('field' => 'centroCosto',
            'label' => 'Centro de costo',
            'rules' => 'required');
      $rules[] = array('field' => 'activoId',
            'label' => 'Activo',
            'rules' => 'numeric');
      $rules[] = array('field' => 'activos',
            'label' => 'Activo',
            'rules' => '');
    }

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
            $productos[] = $item[0]->nombre_producto.' ('.($existencia-$_POST['cantidad'][$key]).')';
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
        $txt = 'La salida se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'La salida se cancelo correctamente.';
        $icono = 'success';
      break;
      case 5:
        $txt = 'La salida se modifico correctamente.';
        $icono = 'success';
      break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}