<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class rastreabilidad_paletas extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'rastreabilidad_paletas/ajax_get_rendimientos/',
    'rastreabilidad_paletas/dd/',
    'rastreabilidad_paletas/ajax_get_folio/',
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
        array('panel/rastreabilidad/admin.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Paletas de Salida'
    );

    $this->load->model('rastreabilidad_paletas_model');
    $this->load->model('areas_model');

    $params['paletas'] = $this->rastreabilidad_paletas_model->getPaletas(true);
    $params['areas']   = $this->areas_model->getAreas();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/rastreabilidad/paletas/admin', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Muestra el Formulario para agregar un paleta de salida
   * @return [type] [description]
   */
  public function agregar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
      array('libs/jquery.chosen.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.chosen.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/keyjump.js'),
      array('panel/rastreabilidad/paletas_agregar.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Paleta de salida'
    );

    $this->load->model('rastreabilidad_paletas_model');

    $this->configAddModPaleta();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->rastreabilidad_paletas_model->addPaletaSalida();

      redirect(base_url('panel/rastreabilidad_paletas/agregar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
    }

    $params['unidades'] = $this->db->select('*')->from('unidades')->where('status', 't')->order_by('nombre')->get()->result();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/rastreabilidad/paletas/agregar', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Muestra el Formulario para agregar un pallet
   * @return [type] [description]
   */
  public function modificar()
  {
    if (isset($_GET['id']))
    {
      $this->carabiner->css(array(
        array('libs/jquery.uniform.css', 'screen'),
        array('libs/jquery.chosen.css', 'screen'),
        array('panel/general_sanjorge.css', 'screen'),
      ));
      $this->carabiner->js(array(
        array('libs/jquery.uniform.min.js'),
        array('libs/jquery.chosen.min.js'),
        array('libs/jquery.numeric.js'),
        array('general/keyjump.js'),
        array('panel/rastreabilidad/pallets_agregar.js'),
      ));

      $params['info_empleado'] = $this->info_empleado['info']; //info empleado
      $params['seo'] = array(
        'titulo' => 'Agregar Pallet'
      );

      $this->load->model('rastreabilidad_paletas_model');
      $this->load->model('calibres_model');
      $this->load->model('areas_model');

      $this->configAddModPaleta();
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $res_mdl = $this->rastreabilidad_paletas_model->updatePallet($_GET['id']);

        redirect(base_url('panel/rastreabilidad_paletas/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
      }

      $params['info'] = $this->rastreabilidad_paletas_model->getInfoPallet($_GET['id']);

      // $params['calibres'] = $this->calibres_model->getCalibres();


      $params['areas'] = $this->areas_model->getAreas();
      // Obtenemos area predeterminada
      $params['area_default'] = null;
      foreach ($params['areas']['areas'] as $key => $value)
      {
        if($value->predeterminado == 't')
        {
          $params['area_default'] = $value->id_area;
          break;
        }
      }

      if (isset($_GET['msg']))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/rastreabilidad/pallets/modificar', $params);
      $this->load->view('panel/footer');
    }else
      redirect(base_url('panel/rastreabilidad_paletas/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Procesa los datos para mostrar el reporte rcr en pdf
   * @return void
   */
  public function imprimir()
  {
    $this->load->model('rastreabilidad_paletas_model');
    $this->rastreabilidad_paletas_model->palletBig_pdf($this->input->get('id'));
  }

  public function eliminar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('rastreabilidad_paletas_model');
      $delAll = isset($_GET['d']) ? true : false;
      $res_mdl = $this->rastreabilidad_paletas_model->deletePallet( $this->input->get('id'), $delAll );
      redirect(base_url('panel/rastreabilidad_paletas/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
    }
    else
      redirect(base_url('panel/rastreabilidad_paletas/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Obtiene la lista de rendimientos de una clasificacion, ajax
   */
  public function ajax_get_rendimientos(){
    $this->load->model('rastreabilidad_paletas_model');
    $params = $this->rastreabilidad_paletas_model->getRendimientoLibre(
                $this->input->get('id'), $this->input->get('idunidad'),
                $this->input->get('idcalibre'), $this->input->get('idetiqueta'));

    echo json_encode($params);
  }

  public function ajax_get_folio(){
    $params = array('folio' => null);
    if(isset($_GET['darea']{0}))
    {
      $this->load->model('rastreabilidad_paletas_model');
      $params['folio'] = $this->rastreabilidad_paletas_model->getNextFolio($_GET['darea']);
    }

    echo json_encode($params);
  }


  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddModPaleta()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'boletasSalidasFolio',
            'label' => 'Folio',
            'rules' => 'required'),
      array('field' => 'boletasSalidasId',
            'label' => 'Folio',
            'rules' => 'required'),
      array('field' => 'empresaId',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'empresa',
            'label' => 'Empresa',
            'rules' => ''),
      array('field' => 'tipo',
            'label' => 'Tipo',
            'rules' => 'required'),
      array('field' => 'fecha',
            'label' => 'Fecha',
            'rules' => 'required'),

      array('field' => 'prod_cliente[]',
            'label' => 'Cliente',
            'rules' => ''),
      array('field' => 'prod_id_cliente[]',
            'label' => 'Cliente',
            'rules' => 'required|is_natural_no_zero'),
      array('field' => 'prod_ddescripcion[]',
            'label' => 'Clasificacion',
            'rules' => ''),
      array('field' => 'prod_did_prod[]',
            'label' => 'Clasificacion',
            'rules' => 'required|is_natural_no_zero'),
      array('field' => 'prod_dmedida[]',
            'label' => 'Medida',
            'rules' => ''),
      array('field' => 'prod_dmedida_id[]',
            'label' => 'Medida',
            'rules' => ''),
      array('field' => 'prod_dcantidad[]',
            'label' => 'Cantidad',
            'rules' => ''),
      array('field' => 'prod_dmedida_kilos[]',
            'label' => 'Kilos',
            'rules' => ''),

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
        $txt = 'El pallet se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'Existe un Pallet de la misma clasificacion pendiente.';
        $icono = 'error';
        break;
      case 5:
        $txt = 'El pallet se modifico correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'El camión se activó correctamente.';
        $icono = 'success';
        break;
      case 7:
        $txt = 'El pallet se elimino correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = 'El pallet se encuentra facturado, para eliminarlo primero tiene que cancelar la factura.';
        $icono = 'error';
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