<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class inventario extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'inventario/cproveedor_pdf/',
    'inventario/cproveedor_xls/',
    'inventario/cproductos_pdf/',
    'inventario/cproductos_xls/',
    'inventario/cproducto_pdf/',
    'inventario/cunproductos_pdf/',
    'inventario/cunproductos_xls/',
    'inventario/cseguimiento_pdf/',
    'inventario/sproveedor_pdf/',
    'inventario/sproveedor_xls/',

    'inventario/epu_pdf/',
    'inventario/epu_xls/',
    'inventario/epc_pdf/',
    'inventario/epc_xls/',
    'inventario/promedio_pdf/',
    'inventario/ueps_pdf/',
    'inventario/ueps_xls/',
    'inventario/pueps_pdf/',
    'inventario/eclasif_pdf/',
    'inventario/historial_nivelar_pdf/',

    'inventario/ajax_get_familias/',

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
   --------------- Rpt Compras ----------------
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
    $this->load->model('inventario_model');
    $this->inventario_model->getCProveedorXls();
  }

  public function sproveedor()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Servicios por Proveedor');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/compras/sproveedor',$params);
    $this->load->view('panel/footer',$params);
  }
  public function sproveedor_pdf(){
    $this->load->model('inventario_model');
    $this->inventario_model->getSProveedorPdf();

  }
  public function sproveedor_xls(){
    $this->load->model('inventario_model');
    $this->inventario_model->getSProveedorXls();
  }

  public function cproductos()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_compras.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');
    $this->load->model('productos_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Compras por Producto');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    $params['familias'] = $this->productos_model->getFamiliasAjax(['id_empresa' => $params['empresa']->id_empresa]);

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
    $this->load->model('inventario_model');
    $this->inventario_model->getCProductosXls();
  }
  public function cproducto_pdf()
  {
    $this->load->model('inventario_model');
    $this->inventario_model->getCProductoPdf();
  }
  public function cseguimiento_pdf()
  {
    $this->load->model('inventario_model');
    $this->inventario_model->getCSeguimientoPdf();
  }

  /**
   * Compras de un Producto
   * @return [type] [description]
   */
  public function cunproductos()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_segxproducto.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Reporte de seguimientos x Producto');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/compras/cunproducto',$params);
    $this->load->view('panel/footer',$params);
  }
  public function cunproductos_pdf(){
    $this->load->model('inventario_model');
    $this->inventario_model->getCUnProductosPdf();
  }
  public function cunproductos_xls(){
    $this->load->model('inventario_model');
    $this->inventario_model->getCUnProductosXls();
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
    $this->load->model('almacenes_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Existencia por unidades');

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
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
  public function epu_xls(){
    $this->load->model('inventario_model');
    $this->inventario_model->getEPUXls();
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
    $this->load->model('almacenes_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Existencia por costos');

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
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
    $this->load->model('inventario_model');
    $this->inventario_model->getEPCXls();
  }


  public function promedio_pdf(){
    if (isset($_GET['id_producto']{0}))
    {
      $this->load->model('inventario_model');
      $this->inventario_model->getPromediodf();
    }
  }

  /**
   * Reporte de costos
   * @return [type] [description]
   */
  public function ueps()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_inventarios.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('productos_model');
    $this->load->model('almacenes_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Existencia por costos UEPS');

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
    $params['data'] = $this->productos_model->getFamilias(false, 'p');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/inventario/ueps',$params);
    $this->load->view('panel/footer',$params);
  }
  public function ueps_pdf(){
    $this->load->model('inventario_model');
    $this->inventario_model->getEPCPdf();
    // $this->inventario_model->getUEPSPdf();
  }
  public function ueps_xls(){
    $this->load->model('inventario_model');
    $this->inventario_model->getEPCXls();
    // $this->inventario_model->getUEPSXls();
  }
  public function pueps_pdf(){
    if (isset($_GET['id_producto']{0}))
    {
      $this->load->model('inventario_model');
      $this->inventario_model->getPromediodf();
      // $this->inventario_model->getPUEPSPdf();
    }
  }

  /**
   * Reporte de costos
   * @return [type] [description]
   */
  public function historial_nivelar()
  {
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/almacen/rpt_inventarios.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('empresas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Historial de nivelaciones');

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/almacen/inventario/historial_nivelar',$params);
    $this->load->view('panel/footer',$params);
  }
  public function historial_nivelar_pdf(){
    $this->load->model('inventario_model');
    $this->inventario_model->getHistorialNivPdf();

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
    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');

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
        redirect(base_url('panel/inventario/nivelar/?'.MyString::getVarsLink(array('msg')).'&msg='.$res_mdl['msg']));
      }
    }

    $params['almacenes']  = $this->almacenes_model->getAlmacenes(false);
    $params['empresa']   = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['fid_empresa'])?$_GET['fid_empresa']: $params['empresa']->id_empresa);
    $_GET['fempresa'] = isset($_GET['dempresa'])?$_GET['dempresa']:$params['empresa']->nombre_fiscal;

    $_GET['fstatus'] = 'ac';
    $params['familias'] = $this->productos_model->getFamilias(false, 'p');

    $id_familia = isset($_GET['dfamilias'])? $_GET['dfamilias']: (isset($params['familias']['familias'][0])? $params['familias']['familias'][0]->id_familia: 0);
    $id_almacen = isset($_GET['id_almacen'])? $_GET['id_almacen']: 1;
    $params['data'] = $this->inventario_model->getNivelarData($id_familia, null, $id_almacen);

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


  public function ajax_get_familias()
  {
    $this->load->model('productos_model');
    $params = $this->productos_model->getFamilias(false, 'p');
    echo json_encode($params);
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
              'rules'   => 'max_length[18]'),
        array('field'   => 'efisica[]',
              'label'   => 'E. Fisica',
              'rules'   => 'max_length[18]'),
        array('field'   => 'diferencia[]',
              'label'   => 'Diferencia',
              'rules'   => 'max_length[12]'),
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