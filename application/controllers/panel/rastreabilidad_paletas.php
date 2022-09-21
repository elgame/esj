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
    $this->load->model('facturacion_model');

    $params['paletas'] = $this->rastreabilidad_paletas_model->getPaletas(true);

    $fstatus = $this->input->get('fstatus');
    unset($_GET['fstatus']);
    $params['areas']   = $this->areas_model->getAreas();
    $params['series']  = $this->facturacion_model->get_series($_GET['did_empresa'], 'r')['data'];
    $_GET['fstatus'] = $fstatus;

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
      array('panel/paletas_salidas.css', 'screen'),
    ));
    $this->carabiner->js(array(
      // array('libs/jquery.uniform.min.js'),
      // array('libs/jquery.chosen.min.js'),
      array('libs/jquery.numeric.js'),
      array('general/keyjump.js'),
      array('panel/rastreabilidad/paletas_agregar.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Papeleta de salida'
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
        array('panel/paletas_salidas.css', 'screen'),
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
        'titulo' => 'Modificar Papeleta de Salida'
      );

      $this->load->model('rastreabilidad_paletas_model');

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

      $params['info'] = $this->rastreabilidad_paletas_model->getInfoPaleta($_GET['id']);
      $params['readonly'] = ($params['info']['paleta']->status==='f'? 'readonly': '');
      $params['disabled'] = ($params['info']['paleta']->status==='f'? 'disabled': '');

      // echo "<pre>";
      //   var_dump($params['info']);
      // echo "</pre>";exit;

      $params['unidades'] = $this->db->select('*')->from('unidades')->where('status', 't')->order_by('nombre')->get()->result();

      if (isset($_GET['msg']))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/rastreabilidad/paletas/modificar', $params);
      $this->load->view('panel/footer');
    }else
      redirect(base_url('panel/rastreabilidad_paletas/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  public function remisionar()
  {
    $this->load->model('rastreabilidad_paletas_model');
    $this->rastreabilidad_paletas_model->remisionarPapeleta($this->input->get('id'));
    redirect(base_url('panel/rastreabilidad_paletas/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
  }

  /**
   * Procesa los datos para mostrar el reporte rcr en pdf
   * @return void
   */
  public function imprimir()
  {
    $this->load->model('rastreabilidad_paletas_model');
    $this->rastreabilidad_paletas_model->paleta_pdf($this->input->get('id'));
  }

  public function cancelar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('rastreabilidad_paletas_model');
      $res_mdl = $this->rastreabilidad_paletas_model->deletePaleta( $this->input->get('id'));
      redirect(base_url('panel/rastreabilidad_paletas/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
    }
    else
      redirect(base_url('panel/rastreabilidad_paletas/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Obtiene la lista de rendimientos de una clasificacion, ajax
   */
  public function ajax_get_pallets(){
    $this->load->model('rastreabilidad_paletas_model');
    $params = $this->rastreabilidad_paletas_model->getRendimientoLibre(
                $this->input->get('id'), $this->input->get('idunidad'),
                $this->input->get('idcalibre'), $this->input->get('idetiqueta'));

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
            'rules' => 'required|callback_chkboleta'),
      array('field' => 'empresaId',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'empresa',
            'label' => 'Empresa',
            'rules' => ''),
      array('field' => 'tipo',
            'label' => 'Tipo',
            'rules' => 'required'),
      array('field' => 'tipoNP',
            'label' => 'TipoNP',
            'rules' => ''),
      array('field' => 'fecha',
            'label' => 'Fecha',
            'rules' => 'required'),

      array('field' => 'empresa_contratante',
            'label' => 'Empresa contratante',
            'rules' => 'required'),
      array('field' => 'cliente_destino',
            'label' => 'Cliente Destino',
            'rules' => 'required'),
      array('field' => 'direccion',
            'label' => 'Dirección',
            'rules' => 'required'),
      array('field' => 'dia_llegada',
            'label' => 'Día de llegada',
            'rules' => 'required'),
      array('field' => 'hr_entrega',
            'label' => 'Hr de entrega',
            'rules' => 'required'),
      array('field' => 'placa_termo',
            'label' => 'Placa termo',
            'rules' => 'required'),
      array('field' => 'temperatura',
            'label' => 'Placa termo',
            'rules' => 'required'),
      array('field' => 'orden_flete',
            'label' => 'Orden de Flete',
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
            'rules' => 'required|is_natural_no_zero|callback_chkexporta'),
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

      array('field' => 'pallets_posicion[]',
            'label' => 'Posición pallet',
            'rules' => ''),
      array('field' => 'pallets_id[]',
            'label' => 'Pallet',
            'rules' => ''),

    );


    $this->form_validation->set_rules($rules);
  }

  public function chkboleta($id){
    // $result = $this->db->query("SELECT Count(id_paleta_salida) AS num FROM otros.paletas_salidas
    //   WHERE id_bascula = {$id} AND status <> 'ca'".(isset($_GET['id'])? " AND id_paleta_salida <> '{$_GET['id']}'": '') )->row();
    // if($result->num > 0){
    //   $this->form_validation->set_message('chkboleta', "La boleta {$_POST['boletasSalidasFolio']} ya esta registrada en otra paleta de salida, intenta con otra.");
    //   return false;
    // }else
      return true;
  }

  public function chkexporta($ids_clasificacion)
  {
    $msgg = '';

    $result = $this->db->query("SELECT id_area FROM bascula WHERE id_bascula = {$_POST['boletasSalidasId']} ")->row();

    // limon, empaque y gubalu
    if (isset($result->id_area) && $result->id_area == 2 &&
      isset($_POST['empresaId']) && ($_POST['empresaId'] == 2 || $_POST['empresaId'] == 15)) {
      if (count($_POST['prod_did_prod']) > 0) {
        if ($_POST['tipo'] == 'lo' || $_POST['tipo'] == 'na') {
          $idss = implode(',', $_POST['prod_did_prod']);
          $classs = $this->db->query("SELECT Upper(nombre) AS nombre FROM clasificaciones WHERE id_clasificacion in({$idss})")->result();
          foreach ($classs as $key => $clas) {
            if (strpos($clas->nombre, 'CONVENCIONAL') === false) {
              $msgg .= "No es una clasificacion CONVENCIONAL -> {$clas->nombre}<br \>";
            }
          }
        } else { // exportacion
          $tipo_val = $this->input->post('tipoNP') == 'si'? 'CONVENCIONAL': 'EXPORTACION';
          foreach ($_POST['pallets_id'] as $key => $value) {
            if ($value > 0) {
              $idss[] = $value;
            }
          }
          $idss = implode(',', $idss);
          $classs = $this->db->query("SELECT rp.folio, Upper(c.nombre) AS nombre
            FROM rastria_pallets rp
              INNER JOIN rastria_pallets_rendimiento rpr ON rp.id_pallet = rpr.id_pallet
              INNER JOIN clasificaciones c ON c.id_clasificacion = rpr.id_clasificacion
            WHERE rp.id_pallet in({$idss})")->result();
          foreach ($classs as $key => $clas) {
            if (strpos($clas->nombre, $tipo_val) === false) {
              $msgg .= "Pallet: {$clas->folio}, No es una clasificacion de {$tipo_val} -> {$clas->nombre}<br \>";
            }
          }
        }
      }
    }

    if($msgg != ''){
      $this->form_validation->set_message('chkexporta', $msgg);
      return false;
    }else
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
        $txt = 'La papeleta de salida se agregó correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'Existe un Pallet de la misma clasificacion pendiente.';
        $icono = 'error';
        break;
      case 5:
        $txt = 'La papeleta de salida se modifico correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'La papeleta de salida se finalizo correctamente (se generaron las remisiones).';
        $icono = 'success';
        break;
      case 7:
        $txt = 'La papeleta de salida se elimino correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = 'La papeleta de salida se encuentra facturado, para eliminarlo primero tiene que cancelar la factura.';
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