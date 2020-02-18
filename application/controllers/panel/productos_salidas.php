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
      array('panel/tags.css', 'screen'),
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

    if (isset($_GET['id'])) {
      $salida = $this->productos_salidas_model->info($_GET['id'], true)['info'][0];
      if (count($salida->productos) > 0) {
        redirect(base_url('panel/productos_salidas/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
      }
    }

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
        $salida = $this->productos_salidas_model->info($_GET['id'], true)['info'][0];
        if (count($salida->productos) > 0) {
          redirect(base_url('panel/productos_salidas/ver/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg'].'&print='.$res_mdl['id_salida'] ));
        } else
          redirect(base_url('panel/productos_salidas/modificar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg'].'&print='.$res_mdl['id_salida'] ));
      }
    }

    $params['salida'] = $this->productos_salidas_model->info($_GET['id'], true)['info'][0];

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
    $params['fecha']      = str_replace(' ', 'T', substr($params['salida']->fecha, 0, 19));

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
    $this->load->view('panel/productos_salidas/modificar', $params);
    $this->load->view('panel/footer');
  }

  public function ver()
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

    //imprimir
    $params['prints'] = isset($_GET['print'])? $_GET['print']: '';

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

    $req1 = $req2 = '';
    if ($this->input->post('guardar') !== false) {
      $req1 = 'required';
      $req2 = 'required|';
    }

    $valEmpAp = false;
    if ($this->input->post('empresaId') == 20) { // id de agro insumos
      $valEmpAp = true;
    }

    $rules = array(
      array('field' => 'empresaId',
            'label' => 'Empresa',
            'rules' => 'required|callback_productos_existencia'),
      array('field' => 'empresa',
            'label' => '',
            'rules' => ''),

      array('field' => 'tipo',
            'label' => 'Tipo',
            'rules' => 'required'),

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


      // array('field' => 'codigoArea[]',
      //       'label' => 'Codigo Area',
      //       'rules' => 'required'),
      // array('field' => 'codigoAreaId[]',
      //       'label' => 'Codigo Area',
      //       'rules' => 'required'),
      array('field' => 'tipoProducto[]',
            'label' => '',
            'rules' => $req1.''),
      array('field' => 'precioUnit[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'codigo[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'concepto[]',
            'label' => 'Productos',
            'rules' => $req1.''),
      array('field' => 'productoId[]',
            'label' => '',
            'rules' => ''),
      array('field' => 'cantidad[]',
            'label' => 'Cantidad',
            'rules' => $req1.'|greater_than[0]')
    );

    if ($this->input->post('tipo') == 'r') { // recetas
      $rules[] = array('field' => 'no_receta',
            'label' => 'No receta',
            'rules' => 'max_length[20]');
      $rules[] = array('field' => 'etapa',
            'label' => 'Etapa',
            'rules' => 'max_length[30]');
      $rules[] = array('field' => 'ranchoC',
            'label' => 'Rancho',
            'rules' => $req1.'');
      $rules[] = array('field' => 'ranchoC_id',
            'label' => 'Rancho',
            'rules' => $req2.'numeric');
      $rules[] = array('field' => 'centro_costo',
            'label' => 'Centro de costo',
            'rules' => $req1.'');
      $rules[] = array('field' => 'centro_costo_id',
            'label' => 'Centro de costo',
            'rules' => $req2.'numeric');
      $rules[] = array('field' => 'hectareas',
            'label' => 'Hectareas',
            'rules' => 'numeric');
      $rules[] = array('field' => 'grupo',
            'label' => 'Grupo',
            'rules' => 'max_length[20]');
      $rules[] = array('field' => 'no_secciones',
            'label' => 'No melgas/seccion',
            'rules' => 'max_length[20]');
      $rules[] = array('field' => 'dias_despues_de',
            'label' => 'Dias despues de',
            'rules' => 'numeric');
      $rules[] = array('field' => 'metodo_aplicacion',
            'label' => 'Metodo de aplicacion',
            'rules' => 'max_length[30]');
      $rules[] = array('field' => 'ciclo',
            'label' => 'Ciclo',
            'rules' => 'max_length[20]');
      $rules[] = array('field' => 'tipo_aplicacion',
            'label' => 'Tipo de aplicacion',
            'rules' => 'max_length[20]');
      $rules[] = array('field' => 'observaciones',
            'label' => 'Observaciones',
            'rules' => 'max_length[220]');
      $rules[] = array('field' => 'fecha_aplicacion',
            'label' => 'Fecha de aplicacion',
            'rules' => '');
    }

    if ($this->input->post('tid_almacen') == '') {
      $rules[] = array('field' => 'empresaApId',
            'label' => 'Empresa aplicacion',
            'rules' => ($valEmpAp? 'required|numeric': ''));
      $rules[] = array('field' => 'empresaAp',
            'label' => 'Empresa aplicacion',
            'rules' => ($valEmpAp? 'required': ''));

      $rules[] = array('field' => 'areaId',
            'label' => 'Cultivo',
            'rules' => $req2.'numeric');
      $rules[] = array('field' => 'area',
            'label' => 'Cultivo',
            'rules' => $req1.'');
      $rules[] = array('field' => 'ranchoId[]',
            'label' => 'Rancho',
            'rules' => $req2.'numeric');
      $rules[] = array('field' => 'ranchoText[]',
            'label' => 'Rancho',
            'rules' => '');
      $rules[] = array('field' => 'rancho',
            'label' => 'Rancho',
            'rules' => '');
      $rules[] = array('field' => 'centroCostoId[]',
            'label' => 'Centro de costo',
            'rules' => $req2.'numeric');
      $rules[] = array('field' => 'centroCostoText[]',
            'label' => 'Centro de costo',
            'rules' => '');
      $rules[] = array('field' => 'centroCosto',
            'label' => 'Centro de costo',
            'rules' => '');
      $rules[] = array('field' => 'activoId',
            'label' => 'Activo',
            'rules' => 'numeric');

      $drequired = '';
      if ($this->input->post('tipo') == 'c')
        $drequired = 'required';
      $rules[] = array('field' => 'activos',
            'label' => 'Activo',
            'rules' => $drequired);
    }

    if ($this->input->post('tipo') == 'c') { // combustible
      $rules[] = array('field' => 'clabor',
            'label' => 'Labor',
            'rules' => 'required');
      $rules[] = array('field' => 'clabor_id',
            'label' => 'Labor',
            'rules' => 'required|numeric');
      $rules[] = array('field' => 'cimplemento',
            'label' => 'Implemento',
            'rules' => 'max_length[80]');
      $rules[] = array('field' => 'chora_carga',
            'label' => 'Hora de carga',
            'rules' => '');
      $rules[] = array('field' => 'clitros',
            'label' => 'Litros',
            'rules' => 'required|numeric');
      $rules[] = array('field' => 'codometro',
            'label' => 'Odometro',
            'rules' => 'required|numeric');
      $rules[] = array('field' => 'cprecio',
            'label' => 'Precio',
            'rules' => 'required|numeric');
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
          $item = $this->inventario_model->getEPUData($value, $this->input->post('id_almacen'), true);
          $existencia = MyString::float( $item[0]->saldo_anterior+$item[0]->entradas-$item[0]->salidas-$item[0]->con_req );
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
      case 6:
        $txt = 'La salida ya sta cerrada no la puedes modificar.';
        $icono = 'error';
      break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }
}