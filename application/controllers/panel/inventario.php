<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class inventario extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'inventario/cproveedor_pdf/',
    'inventario/cproductos_pdf/',
    'inventario/cproducto_pdf/',

    'inventario/epu_pdf/',
    'inventario/epc_pdf/',
    'inventario/promedio_pdf/',
    'inventario/eclasif_pdf/',

    'cuentas_pagar/saldos_xls/',
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
    # code...
  }

  /*-------------------------------------------
   --------------- Rpt Compras -------------
   -------------------------------------------*/

  public function cproveedor()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Compras por Proveedor');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/compras/cproveedor',$params);
    $this->load->view('panel/footer',$params);
  }
  public function cproveedor_pdf(){
    $this->load->model('inventario_model');
    $this->inventario_model->getCProveedorPdf();

  }
  public function cproveedor_xls(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->cuentasPagarExcel();
  }

  public function cproductos()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Compras por Producto');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/compras/cproducto',$params);
    $this->load->view('panel/footer',$params);
  }
  public function cproductos_pdf(){
    $this->load->model('inventario_model');
    $this->inventario_model->getCProductosPdf();
  }
  public function cproductos_xls(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->cuentasPagarExcel();
  }
  public function cproducto_pdf()
  {
    $this->load->model('inventario_model');
    $this->inventario_model->getCProductoPdf();
  }


  /*-------------------------------------------
   ----------- Rpt Inventario -------------
   -------------------------------------------*/

  public function epu()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_inventarios.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('productos_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Existencia por unidades');

    $params['data'] = $this->productos_model->getFamilias(false, 'p');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/inventario/epu',$params);
    $this->load->view('panel/footer',$params);
  }
  public function epu_pdf(){
    $this->load->model('inventario_model');
    $this->inventario_model->getEPUPdf();

  }
  public function saldos_xls(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->cuentasPagarExcel();
  }

  /**
   * Reporte de costos
   * @return [type] [description]
   */
  public function epc()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_inventarios.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('productos_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Existencia por costos');

    $params['data'] = $this->productos_model->getFamilias(false, 'p');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/inventario/epc',$params);
    $this->load->view('panel/footer',$params);
  }
  public function epc_pdf(){
    $this->load->model('inventario_model');
    $this->inventario_model->getEPCPdf();

  }
  public function epc_xls(){
    $this->load->model('cuentas_pagar_model');
    $this->cuentas_pagar_model->cuentasPagarExcel();
  }


  public function promedio_pdf(){
    if (isset($_GET['id_producto']{0}))
    {
      $this->load->model('inventario_model');
      $this->inventario_model->getPromediodf();
    }
  }


  /**
   * Reporte de existencias de clasificaciones
   * @return [type] [description]
   */
  public function eclasif()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_inventarios.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('productos_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Existencia de Clasificaciones');

    $params['data'] = $this->productos_model->getFamilias(false, 'p');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/inventario/eclasif',$params);
    $this->load->view('panel/footer',$params);
  }
  public function eclasif_pdf(){
    $this->load->model('inventario_model');
    $this->inventario_model->getEClasifPdf();

  }


  /**
   * Nivelar inventario
   * @return [type] [description]
   */
  public function nivelar()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('libs/jquery.numeric.js'),
      array('panel/almacen/nivelar_inventario.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('productos_model');
    $this->load->model('inventario_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Existencia de Clasificaciones');

    $this->configNivelar();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $res_mdl = $this->inventario_model->nivelar();

      if ($res_mdl['passes'])
      {
        redirect(base_url('panel/inventario/nivelar/?'.String::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    $params['familias'] = $this->productos_model->getFamilias(false, 'p');

    $params['empresa']   = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($params['empresa']->id_empresa)? $params['empresa']->id_empresa: '');

    $id_familia = isset($_GET['dfamilias'])? $_GET['dfamilias']: (isset($params['familias']['familias'][0])? $params['familias']['familias'][0]->id_familia: 0);
    $params['data'] = $this->inventario_model->getNivelarData($id_familia);

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/inventario/nivelar',$params);
    $this->load->view('panel/footer',$params);
  }
  public function eclasifd_pdf(){
    $this->load->model('inventario_model');
    $this->inventario_model->getEClasifPdf();

  }


  /**
   * Configura los metodos de agregar y modificar
   */
  private function configNivelar()
  {
    $this->load->library('form_validation');
    $rules = array(

        array('field'   => 'idproducto[]',
              'label'   => 'Producto',
              'rules'   => 'required|numeric'),
        array('field'   => 'precio_producto[]',
              'label'   => 'Precio',
              'rules'   => 'required|numeric'),
        array('field'   => 'esistema[]',
              'label'   => 'E. Sistema',
              'rules'   => 'max_length[10]'),
        array('field'   => 'efisica[]',
              'label'   => 'E. Fisica',
              'rules'   => 'max_length[10]'),
        array('field'   => 'diferencia[]',
              'label'   => 'Diferencia',
              'rules'   => 'max_length[10]'),
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
        $txt = 'Se nivelo el inventario correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El abono se agrego correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'El abono se elimino correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = $msg;
        $icono = 'success';
        break;
      case 9:
        $txt = 'El abono se pagó correctamente.';
        $icono = 'success';
        break;

      case 30:
        $txt = 'No hay saldo suficiente para procesar la operación.';
        $icono = 'error';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

?>