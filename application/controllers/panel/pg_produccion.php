<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class pg_produccion extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'pg_produccion/ajax_get_remisiones/',
    'pg_produccion/ajax_get_repmant/',
    'pg_produccion/ajax_get_proveedores/',
    'pg_produccion/ajax_get_cods/',
    'pg_produccion/ajax_get_gastos_caja/',
    'pg_produccion/rpt_rel_fletes_pdf/',
    'pg_produccion/rpt_rel_fletes_xls/',
    'pg_produccion/rpt_rend_equipo_pdf/',
    'pg_produccion/rpt_rend_equipo_xls/',
    'pg_produccion/rpt_estado_results_pdf/',
    'pg_produccion/rpt_estado_results_xls/',

    'pg_produccion/imprimir/',
  );


  public function _remap($method)
  {
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
      array('general/supermodal.js'),
      array('panel/pg/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('pg_produccion_model');

    $params['info_empleado'] = $this->info_empleado['info'];
    $params['seo'] = array('titulo' => 'Produccion Plasticos');

    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $params['datos'] = $this->pg_produccion_model->getProduccion();

    $params['fecha']  = date("Y-m-d");
    $params['method']  = '';

    $params['desbloquear'] = false;
    if ($this->usuarios_model->tienePrivilegioDe('', 'ventas/desbloquear/')) {
      $params['desbloquear'] = true;
    }

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/pg/admin',$params);
    $this->load->view('panel/footer',$params);
  }

  /**
   * Agrega una venta de remision a la bd
   *
   * @return void
   */
  public function agregar()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('libs/jquery.filtertable.min.js'),
      array('libs/jquery.mask.min.js'),
      array('general/keyjump.js'),
      array('general/util.js'),
      ['panel/pg/agregar_produccion.js'],
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    // $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Agregar Producción Plástico');
    $params['pagar_ordent']   = false;

    $this->load->library('cfdi');
    $this->load->model('facturacion_model');
    $this->load->model('pg_produccion_model');
    $this->load->model('empresas_model');

    $this->configAddModProduccion();
    if($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      if (isset($_GET['idp']))
        $respons = $this->pg_produccion_model->updateProduccion($_GET['idp']);
      else
        $respons = $this->pg_produccion_model->addProduccion();

      if($respons['passes'])
      {
        if (isset($_GET['idp']))
          redirect(base_url('panel/pg_produccion/agregar/?msg=3&idp='.$_GET['idp']));
        else
          redirect(base_url('panel/pg_produccion/?msg='.$respons['msg']));
      } else {
        $params['frm_errors'] = $this->showMsgs($respons['msg']);
      }
    }

    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();
    $idEmpresa = $params['empresa_default']->id_empresa;
    if (isset($_GET['idp']))
    {
      $params['borrador'] = $this->pg_produccion_model->produccionInfo($_GET['idp'], true);
      $idEmpresa = $params['borrador']['info']->empresa->id_empresa;
      // echo "<pre>";
      // var_dump($params['borrador']);
      // echo "</pre>";exit;
    }

    // Parametros por default.
    // Obtiene los datos de la empresa predeterminada.
    $params['fecha']  = date("Y-m-d");
    $params['sucursales'] = $this->empresas_model->getSucursales($idEmpresa);
    $params['maquinas'] = $this->pg_produccion_model->maquinasGet(99999, 't');
    $params['moldes'] = $this->pg_produccion_model->moldesGet(99999, 't');
    $params['grupos'] = $this->pg_produccion_model->gruposGet(99999, 't');



    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/pg/produccionAgregar', $params);
    $this->load->view('panel/footer');
  }

  public function cancelar()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('pg_produccion_model');
      $this->pg_produccion_model->cancelar($_GET['id']);

      redirect(base_url('panel/pg_produccion/?'.MyString::getVarsLink(array('msg','id')).'&msg=5'));
    }
  }

  /**
   * Imprime la producción
   * @return [type] [description]
   */
  public function imprimir()
  {
    if(isset($_GET['id']{0}))
    {
      $this->load->model('pg_produccion_model');
      $this->pg_produccion_model->imprimir($_GET['id']);
    }
    else
      redirect(base_url('panel/pg_produccion/?msg=1'));
  }

  /**
   * Configura los metodos de agregar y modificar
   *
   * @return void
   */
  private function configAddModProduccion($borrador = false)
  {
    $this->load->library('form_validation');
    $rules = array(
        ['field' => 'dempresa', 'label' => 'dempresa', 'rules' => ''] ,
        ['field' => 'did_empresa', 'label' => 'did_empresa', 'rules' => 'required|numeric'],
        ['field' => 'sucursalId', 'label' => 'sucursalId', 'rules' => 'numeric'] ,
        ['field' => 'dmaquina', 'label' => 'dmaquina', 'rules' => 'required|numeric'],
        ['field' => 'dmolde', 'label' => 'dmolde', 'rules' => 'required|numeric'] ,
        ['field' => 'dgrupo', 'label' => 'dgrupo', 'rules' => 'required|numeric'] ,
        ['field' => 'dturno', 'label' => 'dturno', 'rules' => 'required|numeric'] ,
        ['field' => 'dfecha', 'label' => 'dfecha', 'rules' => 'required'] ,
        ['field' => 'djefeTurn', 'label' => 'djefeTurn', 'rules' => ''] ,
        ['field' => 'djefeTurnId', 'label' => 'djefeTurnId', 'rules' => 'required|numeric'] ,

        ['field' => 'prod_id[]', 'label' => 'prod_id', 'rules' => ''],
        ['field' => 'prod_clasificacion[]', 'label' => 'prod_clasificacion', 'rules' => 'required'],
        ['field' => 'prod_id_clasificacion[]', 'label' => 'prod_id_clasificacion', 'rules' => 'required|numeric'],
        ['field' => 'prod_cajas_buenas[]', 'label' => 'prod_cajas_buenas', 'rules' => 'required|numeric'],
        ['field' => 'prod_cajas_merma[]', 'label' => 'prod_cajas_merma', 'rules' => 'required|numeric'],
        ['field' => 'prod_total_cajas[]', 'label' => 'prod_total_cajas', 'rules' => 'required|numeric'],
        ['field' => 'prod_peso_promedio[]', 'label' => 'prod_peso_promedio', 'rules' => 'required|numeric'],
        ['field' => 'prod_plasta[]', 'label' => 'prod_plasta', 'rules' => 'required|numeric'],
        ['field' => 'prod_Kgs_inyectados[]', 'label' => 'prod_Kgs_inyectados', 'rules' => 'required|numeric'],
        ['field' => 'prod_ciclo[]', 'label' => 'prod_ciclo', 'rules' => 'required|numeric'],
        ['field' => 'prod_del[]', 'label' => 'prod_del', 'rules' => ''],

    );

    $this->form_validation->set_rules($rules);
  }




  public function maquinas()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Plásticos Maquinas'
    );

    $this->load->library('pagination');
    $this->load->model('pg_produccion_model');

    $params['conceptos'] = $this->pg_produccion_model->maquinasGet();
    // echo "<pre>";
    //   var_dump($params['categorias']);
    // echo "</pre>";exit;
    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/pg/maquinasAdmin', $params);
    $this->load->view('panel/footer');
  }

  public function maquinasAgregar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/labores.js'),
    ));

    $this->load->model('pg_produccion_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Maquina'
    );

    $this->configAddMaquinas();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->pg_produccion_model->maquinasAgregar($_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/pg_produccion/maquinasAgregar/?'.MyString::getVarsLink(array('msg')).'&msg=4'));
      }
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/pg/maquinasAgregar', $params);
    $this->load->view('panel/footer');
  }

  public function maquinasModificar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/categorias.js'),
    ));

    $this->load->model('pg_produccion_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Modificar Maquina'
    );

    $this->configAddMaquinas();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->pg_produccion_model->maquinasModificar($_GET['id'], $_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/pg_produccion/maquinasModificar/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
      }
    }

    $params['maquina'] = $this->pg_produccion_model->maquinasInfo($_GET['id'], true);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/pg/maquinasModificar', $params);
    $this->load->view('panel/footer');
  }

  public function maquinasEliminar()
  {
    $this->load->model('pg_produccion_model');
    $this->pg_produccion_model->maquinasEliminar($_GET['id']);

    redirect(base_url('panel/pg_produccion/maquinas/?&msg=6'));
  }

  public function configAddMaquinas()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'nombre',
            'label' => 'Nombre',
            'rules' => 'required|max_length[120]'),
    );

    $this->form_validation->set_rules($rules);
  }


  public function moldes()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Plásticos Moldes'
    );

    $this->load->library('pagination');
    $this->load->model('pg_produccion_model');

    $params['conceptos'] = $this->pg_produccion_model->moldesGet();
    // echo "<pre>";
    //   var_dump($params['categorias']);
    // echo "</pre>";exit;
    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/pg/moldesAdmin', $params);
    $this->load->view('panel/footer');
  }

  public function moldesAgregar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/nomina_fiscal/labores.js'),
    ));

    $this->load->model('pg_produccion_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Molde'
    );

    $this->configAddMoldes();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->pg_produccion_model->moldesAgregar($_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/pg_produccion/moldesAgregar/?'.MyString::getVarsLink(array('msg')).'&msg=4'));
      }
    }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/pg/moldesAgregar', $params);
    $this->load->view('panel/footer');
  }

  public function moldesModificar()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/caja_chica/categorias.js'),
    ));

    $this->load->model('pg_produccion_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Modificar Maquina'
    );

    $this->configAddMoldes();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->pg_produccion_model->moldesModificar($_GET['id'], $_POST);

      if ($res_mdl)
      {
        redirect(base_url('panel/pg_produccion/moldesModificar/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
      }
    }

    $params['molde'] = $this->pg_produccion_model->moldesInfo($_GET['id'], true);

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/pg/moldesModificar', $params);
    $this->load->view('panel/footer');
  }

  public function moldesEliminar()
  {
    $this->load->model('pg_produccion_model');
    $this->pg_produccion_model->moldesEliminar($_GET['id']);

    redirect(base_url('panel/pg_produccion/moldes/?&msg=6'));
  }

  public function configAddMoldes()
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'nombre',
            'label' => 'Nombre',
            'rules' => 'required|max_length[120]'),
    );

    $this->form_validation->set_rules($rules);
  }


  /*
   |-------------------------------------------------------------------------
   |  MESAJES ALERTAS
   |-------------------------------------------------------------------------
   */

  /**
   * Muestra mensajes cuando se realiza alguna accion
   * @param unknown_type $tipo
   * @param unknown_type $msg
   * @param unknown_type $title
   */
  private function showMsgs($tipo, $msg='', $title='Facturacion!'){
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
        $txt = 'La Producción se guardo correctamente.';
        $icono = 'success';
        break;
      case 33:
        $txt = 'La Producción se guardo pero algunos gastos no porque que no se selecciono del catalogo.';
        $icono = 'error';
        break;
      case 34:
        $txt = 'La Producción se guardo pero algunos rep/matto no porque que no se selecciono del catalogo.';
        $icono = 'error';
        break;
      case 4:
        $txt = 'La Nota de remisión se agrego correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La Producción se cancelo correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'La maquina se elimino correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = $msg;
        $icono = 'success';
        break;
      case 9:
        $txt = 'La Nota de remisión se pagó correctamente.';
        $icono = 'success';
        break;
      case 10:
        $txt = 'La Nota de credito se agrego correctamente.';
        $icono = 'success';
        break;
      case 11:
        $txt = 'La Remision se agrego correctamente.';
        $icono = 'success';
        break;

      case 16:
        $txt = 'El molde se elimino correctamente.';
        $icono = 'success';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

?>