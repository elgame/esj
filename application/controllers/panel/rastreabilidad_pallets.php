<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class rastreabilidad_pallets extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'rastreabilidad_pallets/ajax_get_rendimientos/',
    'rastreabilidad_pallets/dd/',
    'rastreabilidad_pallets/ajax_get_folio/',
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
      'titulo' => 'Rastreabilidad'
    );

    $this->load->model('rastreabilidad_pallets_model');
    $this->load->model('areas_model');

    $params['pallets'] = $this->rastreabilidad_pallets_model->getPallets(true);
    $params['areas']   = $this->areas_model->getAreas();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/rastreabilidad/pallets/admin', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Muestra el Formulario para agregar un pallet
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
      array('panel/rastreabilidad/pallets_agregar.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Pallet'
    );

    $this->load->model('rastreabilidad_pallets_model');
    $this->load->model('calibres_model');
    $this->load->model('areas_model');

    $this->configAddModPallet();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->rastreabilidad_pallets_model->addPallet();

      redirect(base_url('panel/rastreabilidad_pallets/agregar/?'.String::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
    }

    $params['areas'] = $this->areas_model->getAreas();
    // Obtenemos area predeterminada
    $params['area_default'] = null;
    if(isset($_POST['parea']{0}))
      $params['area_default'] = $_POST['parea'];
    else{
      foreach ($params['areas']['areas'] as $key => $value)
      {
        if($value->predeterminado == 't')
        {
          $params['area_default'] = $value->id_area;
          break;
        }
      }
    }

    $params['folio'] = $this->rastreabilidad_pallets_model->getNextFolio($params['area_default']);

    // $params['calibres'] = $this->calibres_model->getCalibres();


    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/rastreabilidad/pallets/agregar', $params);
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

      $this->load->model('rastreabilidad_pallets_model');
      $this->load->model('calibres_model');
      $this->load->model('areas_model');

      $this->configAddModPallet();
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $res_mdl = $this->rastreabilidad_pallets_model->updatePallet($_GET['id']);

        redirect(base_url('panel/rastreabilidad_pallets/?'.String::getVarsLink(array('msg')).'&msg=5'));
      }

      $params['info'] = $this->rastreabilidad_pallets_model->getInfoPallet($_GET['id']);

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
      redirect(base_url('panel/rastreabilidad_pallets/?'.String::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Procesa los datos para mostrar el reporte rcr en pdf
   * @return void
   */
  public function imprimir()
  {
    $this->load->model('rastreabilidad_pallets_model');
    $this->rastreabilidad_pallets_model->palletBig_pdf($this->input->get('id'));
  }

  public function eliminar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('rastreabilidad_pallets_model');
      $delAll = isset($_GET['d']) ? true : false;
      $res_mdl = $this->rastreabilidad_pallets_model->deletePallet( $this->input->get('id'), $delAll );
      redirect(base_url('panel/rastreabilidad_pallets/?'.String::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
    }
    else
      redirect(base_url('panel/rastreabilidad_pallets/?'.String::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Obtiene la lista de rendimientos de una clasificacion, ajax
   */
  public function ajax_get_rendimientos(){
    $this->load->model('rastreabilidad_pallets_model');
    $params = $this->rastreabilidad_pallets_model->getRendimientoLibre(
                $this->input->get('id'), $this->input->get('idunidad'),
                $this->input->get('idcalibre'), $this->input->get('idetiqueta'));

    echo json_encode($params);
  }

  public function ajax_get_folio(){
    $params = array('folio' => null);
    if(isset($_GET['darea']{0}))
    {
      $this->load->model('rastreabilidad_pallets_model');
      $params['folio'] = $this->rastreabilidad_pallets_model->getNextFolio($_GET['darea']);
    }

    echo json_encode($params);
  }


  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddModPallet()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'ffolio',
            'label' => 'Folio',
            'rules' => 'required|is_natural_no_zero|callback_chkfolio|callback_productos_existencia'),
      array('field' => 'fid_clasificacion',
            'label' => 'Clasificacion',
            'rules' => ''),
      array('field' => 'fcajas',
            'label' => 'Cajas',
            'rules' => 'required|is_natural_no_zero'),

      array('field' => 'fclasificacion',
            'label' => 'Clasificacion',
            'rules' => ''),
      array('field' => 'rendimientos[]',
            'label' => 'Lista de cajas',
            'rules' => 'is_natural_no_zero'),
      array('field' => 'idrendimientos[]',
            'label' => 'Caja disponible',
            'rules' => 'is_natural_no_zero'),
      array('field' => 'idclasificacion[]',
            'label' => 'Clasificacion',
            'rules' => 'is_natural_no_zero'),

      array('field' => 'idcalibre[]',
            'label' => 'Calibres',
            'rules' => 'required|is_natural_no_zero'),
      array('field' => 'fcliente',
            'label' => 'Cliente',
            'rules' => ''),
      array('field' => 'fid_cliente',
            'label' => 'Cliente',
            'rules' => 'is_natural_no_zero'),
      // array('field' => 'fhojaspapel',
      //       'label' => 'Hojas de papel',
      //       'rules' => 'required|is_natural'),
      array('field' => 'ps[]',
            'label' => 'Producto',
            'rules' => ''),
      array('field' => 'ps_id[]',
            'label' => 'Producto',
            'rules' => ''),
      array('field' => 'ps_num[]',
            'label' => 'Cantidad',
            'rules' => ''),
    );


    $this->form_validation->set_rules($rules);
  }

  public function chkfolio($folio){
    $result = $this->db->query("SELECT Count(id_pallet) AS num FROM rastria_pallets
      WHERE id_area = {$_POST['parea']} AND folio = {$folio}".(isset($_GET['id'])? " AND id_pallet <> '{$_GET['id']}'": '') )->row();
    if($result->num > 0){
      $this->form_validation->set_message('chkfolio', 'El folio ya existe, intenta con otro.');
      return false;
    }else
      return true;
  }

  public function productos_existencia($str)
  {
    $this->load->model('inventario_model');
    $productos = array();
    if (is_array($_POST['ps_id']) && count($_POST['ps_id']) > 0) {
      foreach ($_POST['ps_id'] as $key => $value) {
        if (floatval($value) > 0) {
          $item = $this->inventario_model->getEPUData($value);
          $existencia = String::float( $item[0]->saldo_anterior+$item[0]->entradas-$item[0]->salidas );
          if ( String::float($existencia-$_POST['ps_num'][$key]) < 0) {
            $productos[] = $item[0]->nombre_producto.' ('.($existencia-$_POST['ps_num'][$key]).')';
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