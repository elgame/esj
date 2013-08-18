<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class polizas extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'polizas/genera_poliza/',
    'polizas/descargar_poliza/',
    'polizas/get_folio/'
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
      // array('general/msgbox.js'),
      array('panel/polizas/genera.js'),
      array('panel/bascula/reportes/rde.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Generar Polizas'
    );

    $this->load->model('empresas_model');
    $this->load->model('polizas_model');


    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    $params['folio'] = $this->polizas_model->getFolio('3', 'v');

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/polizas/generar', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Procesa los datos para mostrar el reporte r_acumulados en pdf
   * @return void
   */
  public function genera_poliza()
  {
    $this->load->model('polizas_model');
    $params['poliza'] = $this->polizas_model->generaPoliza();

    $this->load->view('panel/polizas/generar_result', $params);
  }

  public function descargar_poliza()
  {
    header("Content-disposition: attachment; filename={$_GET['poliza_nombre']}");
    header("Content-type: application/octet-stream");
    readfile(APPPATH."media/polizas/{$_GET['poliza_nombre']}");
  }

  public function get_folio(){
    $this->load->model('polizas_model');
    $params['folio'] = $this->polizas_model->getFolio();
    echo json_encode($params);
  }

  /**
   * Visualiza el formulario para agregar o editar una entrada|salida.
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
      array('general/buttons.toggle.js'),
      array('general/keyjump.js'),
      array('panel/bascula/agregar.js'),
      array('panel/bascula/bonificacion.js'),
    ));

    $this->load->model('bascula_model');
    $this->load->model('areas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Bascula'
    );

    $params['next_folio'] = $this->bascula_model->getSiguienteFolio('en');
    $params['areas']      = $this->areas_model->getAreas();

    $params['empresa_default'] = $this->db->select("id_empresa, nombre_fiscal")
      ->from("empresas")
      ->where("predeterminado", "t")
      ->get()
      ->row();

    $this->configAddModBascula();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('bascula_model');
      $res_mdl = $this->bascula_model->addBascula();

      $ticket = '';
      // if (isset($_POST['pstatus']))
      //   $ticket = '&p=t';

      if (isset($_GET['p']))
        $ticket = '&p=t';

      $res_mdl['error'] = isset($res_mdl['error'])? $res_mdl['error']: false;
      if( ! $res_mdl['error'])
        redirect(base_url('panel/bascula/agregar/?'.String::getVarsLink(array('msg', 'fstatus', 'p')).'&msg='.$res_mdl['msg'].$ticket));
    }

    $params['accion']      = 'n'; // indica que es nueva entrada
    $params['idb']         = '';
    $params['param_folio'] = '';
    $params['fecha']       = str_replace(' ', 'T', date("Y-m-d H:i"));

    $params['e'] = false;

    if (isset($_GET['folio']) || isset($_GET['idb']))
    {
      if (isset($_GET['folio']))
        $info = $this->bascula_model->getBasculaInfo(0, $_GET['folio']);

      if (isset($_GET['idb']))
        $info = $this->bascula_model->getBasculaInfo($_GET['idb'], 0);
      // echo "<pre>";
      //   var_dump($info);
      // echo "</pre>";exit;
      if (count($info['info']) > 0)
      {
        $this->load->model('empresas_model');
        $empresa = $this->empresas_model->getInfoEmpresa($info['info'][0]->id_empresa, true);

        if ($info['info'][0]->id_proveedor != null)
        {
          $this->load->model('proveedores_model');
          $proveedor = $this->proveedores_model->getProveedorInfo($info['info'][0]->id_proveedor, true);

          $_POST['pproveedor']    = $proveedor['info']->nombre_fiscal;
          $_POST['pid_proveedor'] = $info['info'][0]->id_proveedor;
        }
        else
        {
          $this->load->model('clientes_model');
          $cliente = $this->clientes_model->getClienteInfo($info['info'][0]->id_cliente, true);

          $_POST['pcliente']    = $cliente['info']->nombre_fiscal;
          $_POST['pid_cliente'] = $info['info'][0]->id_cliente;
        }

        if ($info['info'][0]->id_chofer != null)
        {
          $this->load->model('choferes_model');
          $chofer = $this->choferes_model->getChoferInfo($info['info'][0]->id_chofer, true);

          $_POST['pchofer']    = $chofer['info']->nombre;
          $_POST['pid_chofer'] = $info['info'][0]->id_chofer;
        }

        if ($info['info'][0]->id_camion != null)
        {
          $this->load->model('camiones_model');
          $camion = $this->camiones_model->getCamionInfo($info['info'][0]->id_camion, true);

          $_POST['pcamion']       = $camion['info']->placa;
          $_POST['pid_camion']    = $info['info'][0]->id_camion;
        }

        $params['param_folio'] = '?idb=' . $info['info'][0]->id_bascula; //$_GET['folio'];
        $params['idb']         = $info['info'][0]->id_bascula;
        $params['accion']      = $info['info'][0]->accion;

        if (isset($_GET['p']))
          $params['ticket'] = $info['info'][0]->id_bascula;

        if (isset($_GET['e']))
          if ($_GET['e'] === 't')
            $params['e'] = true;

        $_POST['ptipo']         = $info['info'][0]->tipo;
        $_POST['parea']         = $info['info'][0]->id_area;
        $_POST['pempresa']      = $empresa['info']->nombre_fiscal;
        $_POST['pid_empresa']   = $info['info'][0]->id_empresa;

        $params['next_folio'] = $info['info'][0]->folio;
        if($info['info'][0]->fecha_tara != '')
          $params['fecha']      =  str_replace(' ', 'T', substr($info['info'][0]->fecha_tara, 0, 16));
        else
          $params['fecha']      =  str_replace(' ', 'T', substr(date("Y-m-d H:i:s"), 0, 16));

        $_POST['pkilos_brutos']    = $info['info'][0]->kilos_bruto;
        $_POST['pkilos_tara']      = $info['info'][0]->kilos_tara;
        $_POST['pcajas_prestadas'] = $info['info'][0]->cajas_prestadas;
        $_POST['pkilos_neto']      = $info['info'][0]->kilos_neto;

        if ( ! isset($_POST['pcajas']) )
        {
          foreach ($info['cajas'] as $key => $c)
          {
            $_POST['pcajas'][]       = $c->cajas;
            $_POST['pcalidad'][]     = $c->id_calidad;
            $_POST['pcalidadtext'][] = $c->calidad;
            $_POST['pkilos'][]       = $c->kilos;
            $_POST['ppromedio'][]    = $c->promedio;
            $_POST['pprecio'][]      = $c->precio;
            $_POST['pimporte'][]     = $c->importe;
          }
        }

        $_POST['ptotal_cajas']   = $info['info'][0]->total_cajas;
        $_POST['ppesada']        = $info['info'][0]->kilos_neto2;
        $_POST['ptotal']         = $info['info'][0]->importe;
        $_POST['pobcervaciones'] = $info['info'][0]->obcervaciones;

      }
      else
      {
        $_GET['msg'] = '10';
      }

      // echo "<pre>";
      //   var_dump($info);
      // echo "</pre>";exit;
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/agregar', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Muestra la vista para el Reporte "REPORTE DE ACUMULADOS DE PRODUCTOS"
   *
   * @return void
   */
  public function r_acumulados()
  {
    $this->carabiner->js(array(
      // array('general/msgbox.js'),
      array('panel/bascula/admin.js'),
      array('panel/bascula/reportes/rde.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Reporte de Acumulados de Productos'
    );
    $this->load->model('areas_model');

    $params['areas'] = $this->areas_model->getAreas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    // $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/reportes/r_acumulados', $params);
    $this->load->view('panel/footer');
  }

 


  /**
   * Configura las reglas para agregar empresas
   */
  private function configAddModEmpresa(){
    $this->load->library('form_validation');
    $contacto = false;

      $rules = array(
        array('field' => 'dnombre_fiscal',
            'label' => 'Nombre Fiscal',
            'rules' => 'required|max_length[130]'),
        array('field' => 'drfc',
            'label' => 'RFC',
            'rules' => 'max_length[13]'),
        array('field' => 'dcalle',
            'label' => 'Calle',
            'rules' => 'max_length[60]'),
        array('field' => 'dno_exterior',
            'label' => 'No exterior',
            'rules' => 'max_length[8]'),
        array('field' => 'dno_interior',
            'label' => 'No interior',
            'rules' => 'max_length[8]'),
        array('field' => 'dcolonia',
            'label' => 'Colonia',
            'rules' => 'max_length[60]'),
        array('field' => 'dlocalidad',
            'label' => 'Localidad',
            'rules' => 'max_length[60]'),
        array('field' => 'dmunicipio',
            'label' => 'Municipio',
            'rules' => 'max_length[60]'),
        array('field' => 'destado',
            'label' => 'Estado',
            'rules' => 'max_length[60]'),
        array('field' => 'dcp',
            'label' => 'CP',
            'rules' => 'max_length[12]'),
        array('field' => 'dregimen_fiscal',
            'label' => 'Régimen fiscal',
            'rules' => 'max_length[100]'),
        array('field' => 'dtelefono',
            'label' => 'Teléfono',
            'rules' => 'max_length[15]'),
        array('field' => 'demail',
            'label' => 'Email',
            'rules' => 'valid_email|max_length[70]'),
        array('field' => 'dpag_web',
            'label' => 'Pag Web',
            'rules' => 'max_length[80]')
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
        $txt = 'La empresa se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El proveedor se agregó correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'El chofer se agregó correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'El camión se activó correctamente.';
        $icono = 'success';
        break;

      case 7:
        $txt = 'La entrada se agrego correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = 'La bascula se cancelo correctamente.';
        $icono = 'success';
        break;

      case 9:
        $txt = 'La bascula se activo correctamente.';
        $icono = 'success';
        break;

      case 10:
        $txt = 'No existe el folio especificado.';
        $icono = 'error';
        break;
      case 11:
        $txt = 'El cliente se agregó correctamente.';
        $icono = 'success';
        break;
      case 12:
        $txt = 'La bonificación se agregó correctamente.';
        $icono = 'success';
        break;
      case 13:
        $txt = 'Especifique un Proveedor!';
        $icono = 'error';
        break;
      case 14:
        $txt = 'El pago se realizo correctamente!';
        $icono = 'success';
        break;
      case 15:
        $txt = 'El lote se agrego correctamente!';
        $icono = 'success';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

/* End of file bascula.php */
/* Location: ./application/controllers/panel/bascula.php */