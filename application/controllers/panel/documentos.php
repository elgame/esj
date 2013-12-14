<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class documentos extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'facturacion/get_folio/',
    'facturacion/get_series/',

    'facturacion/rvc_pdf/',
    'facturacion/rvp_pdf/',

    'facturacion/ajax_get_clasificaciones/',
    'facturacion/ajax_get_empresas_fac/',

    'documentos/ajax_get_ticket_info/',
    'documentos/ajax_update_doc/',
    'documentos/ajax_get_snapshot/',
    'documentos/ajax_save_snaptshot/',
    'documentos/ajax_del_snaptshot/',
    'documentos/ajax_check_ctrl/',

    'documentos/imprime_manifiesto_chofer/',
    'documentos/imprime_embarque/',
    'documentos/imprime_certificado_tlc/',
    'documentos/imprime_manifiesto_camion/',

    'documentos/acomodo_embarque/',
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
      array('panel/facturacion/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('facturacion_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Facturas');

    $params['datos_s'] = $this->facturacion_model->getFacturas();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/admin',$params);
    $this->load->view('panel/footer',$params);
  }

  /**
   * Visualiza la vista para realizar la documentacion.
   *
   * @return void
   */
  public function agregar()
  {
    if (isset($_GET['id']{0}) && $_GET['id'] !== '')
    {
      $this->carabiner->js(array(
          array('libs/jquery.numeric.js'),
          array('libs/jquery.dataTables.min.js'),
          array('general/supermodal.js'),
          array('general/keyjump.js'),
          array('general/util.js'),
          array('panel/documentos/agregar.js'),
      ));

      $params['info_empleado']  = $this->info_empleado['info']; //info empleado
      $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
      $params['seo']            = array('titulo' => 'Agregar factura');
      $params['pagar_ordent']   = false;

      $this->load->model('facturacion_model');
      $this->load->model('documentos_model');

      // Obtiene la informacion de la factura.
      $params['factura'] = $this->facturacion_model->getInfoFactura($_GET['id']);

      // Carga la vista de la factura con sus datos.
      $params['facturaView'] = $this->load->view('panel/facturacion/ver', $params, true);

      $is_finalizados = $this->db
        ->select('docs_finalizados')
        ->from('facturacion')
        ->where('id_factura', $_GET['id'])
        ->get()->row()->docs_finalizados;

      if ($is_finalizados === 'f')
      {
        // Obtiene los documentos del cliente.
        $docsCliente = $this->documentos_model->getClienteDocs($_GET['id']);

        $total = 0;
        if ($docsCliente)
        {
          foreach ($docsCliente as $doc)
          {
            if ($doc->status === 't')
              $total++;
          }

          if (count($docsCliente) === $total)
            $params['finalizar'] = true;
        }
      }
      else
        $params['finalizados'] = true;

      // Obtiene la vista de los documentos del cliente.
      $params['documentos'] = $this->generaDocsView($params['factura']['info']->id_factura);

      if(isset($_GET['msg']{0}))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/documentos/agregar', $params);
      $this->load->view('panel/footer');
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
  }

  /**
   * Genera la vista del listado de los documentos.
   *
   * @param  boolean $idFactura
   * @return string
   */
  public function generaDocsView($idFactura = false)
  {
    $this->load->model('documentos_model');
    $this->load->model('areas_model');

    if ( ! isset($this->facturacion_model))
    {
      $this->load->model('facturacion_model');
    }

    $idFactura = $idFactura ? $idFactura : $_GET['id'];

    // Obtiene la informacion de la factura.
    $params['factura'] = $this->facturacion_model->getInfoFactura($idFactura);


    // Obtiene los documentos del cliente.
    $params['documentos'] = $this->documentos_model->getClienteDocs($idFactura);
    // echo "<pre>";
    //   var_dump($params['documentos']);
    // echo "</pre>";exit;

    // Obtiene los documentos de las areas.
    $params['areas'] = $this->areas_model->getAreas();

    $params['is_finalizados'] = $this->db
      ->select('docs_finalizados')
      ->from('facturacion')
      ->where('id_factura', $idFactura)
      ->get()->row()->docs_finalizados;

    // Obtiene los pallets libres.
    $params['pallets'] = $this->db
      ->select('*')
      ->from("embarque_pallets_libres")
      ->get()
      ->result();

    // echo "<pre>";
    //   var_dump($params['pallets']);
    // echo "</pre>";exit;

    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->db
      ->select("id_empresa, nombre_fiscal, rfc, calle, no_exterior, colonia, localidad, municipio, estado")
      ->from("empresas AS e")
      ->where("e.predeterminado", "t")
      ->get()
      ->row();

    // Construye la vista del listado de documentos.
    return $this->load->view('panel/documentos/agregar_listado', $params, true);
  }

  public function finalizar_docs()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('documentos_model');
      $this->documentos_model->finalizar_docs($_GET['id']);

      redirect(base_url('panel/documentos/agregar/?id='.$_GET['id']));
    }
  }

  /*
  |-------------------------------------------------------------------------
  |  CHOFER COPIA DEL IFE
  |-------------------------------------------------------------------------
  */

  public function chofer_copia_ife()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('documentos_model');
      $this->documentos_model->saveChoferCopiaIfe($_POST['embIdFac'], $_POST['embIdDoc']);

      redirect(base_url('panel/documentos/agregar/?id='.$_GET['id'].'&ds='.$_POST['embIdDoc'].'&msg=4'));
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
  }

  /*
   |-------------------------------------------------------------------------
   |  CHOFER COPIA LICENCIA
   |-------------------------------------------------------------------------
   */

   public function chofer_copia_licencia()
   {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('documentos_model');
      $this->documentos_model->saveChoferCopiaLicencia($_POST['embIdFac'], $_POST['embIdDoc']);

      redirect(base_url('panel/documentos/agregar/?id='.$_GET['id'].'&ds='.$_POST['embIdDoc'].'&msg=4'));
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
   }

  /*
   |-------------------------------------------------------------------------
   |  CHOFER COPIA LICENCIA
   |-------------------------------------------------------------------------
   */

  public function seguro_camion()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('documentos_model');
      $this->documentos_model->saveSeguroCamion($_POST['embIdFac'], $_POST['embIdDoc']);

      redirect(base_url('panel/documentos/agregar/?id='.$_GET['id'].'&ds='.$_POST['embIdDoc'].'&msg=4'));
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
  }

  /*
   |-------------------------------------------------------------------------
   |  EMBARQUE
   |-------------------------------------------------------------------------
   */

  public function acomodo_embarque()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('documentos_model');
      $res = $this->documentos_model->storeEmbarque();

      if ($res['passes'])
        redirect(base_url('panel/documentos/agregar/?id='.$_GET['id'].'&ds='.$_POST['embIdDoc'].'&msg=4'));
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
  }

  /*
   |-------------------------------------------------------------------------
   |  CERTIFICADO TLC
   |-------------------------------------------------------------------------
   */

  public function certificado_tlc()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('documentos_model');
      $res = $this->documentos_model->storeCertificadoTlc($_POST['embIdFac'], $_POST['embIdDoc']);

      if ($res['passes'])
        redirect(base_url('panel/documentos/agregar/?id='.$_GET['id'].'&ds='.$_POST['embIdDoc'].'&msg=4'));
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
  }

  /*
   |-------------------------------------------------------------------------
   |  MANIFIESTO CAMION
   |-------------------------------------------------------------------------
   */

  public function manifiesto_camion()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('documentos_model');
      $res = $this->documentos_model->storeManifiestoCamion($_POST['embIdFac'], $_POST['embIdDoc']);

      if ($res['passes'])
        redirect(base_url('panel/documentos/agregar/?id='.$_GET['id'].'&ds='.$_POST['embIdDoc'].'&msg=4'));
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
  }

  /*
   |-------------------------------------------------------------------------
   |  AJAX
   |-------------------------------------------------------------------------
   */

  /**
   * Obtiene la informacion del Chofer y Camion del ticket de Pesada.
   *
   * @return void
   */
  public function ajax_get_ticket_info()
  {
    $this->load->model('documentos_model');

    $data = $this->documentos_model->getChoferCamionInfo($_GET['ida'], $_GET['idt'], $_GET['idf']);

    echo json_encode($data);
  }

  /**
   * Actualiza la informacion de un documento.
   *
   * @return void
   */
  public function ajax_update_doc()
  {
    $this->load->model('documentos_model');

    $resp = $this->documentos_model->ajaxUpdateDocumento($_POST['factura_id'], $_POST['documento_id']);

    if ($resp['passes'])
    {
      $_GET['id'] = $_POST['factura_id'];

      $resp['htmlDocs'] = $this->generaDocsView($_POST['factura_id']);
    }

    echo json_encode($resp);
  }

  /**
   * Obtiene el base64 de la captura.
   *
   * @return void
   */
  public function ajax_get_snapshot(){
    $base64 = UploadFiles::fileToBase64($this->config->item('base_url_cam_salida_snapshot'), 'jpg');

    echo json_encode(array('base64' => $base64));
  }

  /**
   * Guarda el snapshot.
   *
   * @return [type] [description]
   */
  public function ajax_save_snaptshot()
  {
    $this->load->model('facturacion_model');
    $this->load->model('documentos_model');

    $idFactura = $_POST['factura_id'];

    // Obtiene la informacion de la factura.
    $factura = $this->facturacion_model->getInfoFactura($idFactura);

    // Obtiene la ruta donde se guardan los documentos del cliente.
    $path = $this->documentos_model->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio);

    $filename = 'CHOFER FOTO FIRMA MANIFIESTO';

    $base64 = str_replace('[removed]', 'data:image/jpg;base64,', $_POST['base64']);
    unset($_POST['base64']);

    // Guarda el snapshot en disco.
    UploadFiles::base64SaveImg($base64, $filename, 'jpg', $path);

    $_POST['url'] = $path.$filename.'.jpg';

    $this->ajax_update_doc();
  }

  /**
   * elimina el snapshot.
   */
  public function ajax_del_snaptshot()
  {
    $this->load->model('facturacion_model');
    $this->load->model('documentos_model');

    $idFactura = $_POST['factura_id'];

    // Obtiene la informacion de la factura.
    $factura = $this->facturacion_model->getInfoFactura($idFactura);

    // Obtiene la ruta donde se guardan los documentos del cliente.
    $path = $this->documentos_model->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio);

    $filename = 'CHOFER FOTO FIRMA MANIFIESTO';
    unlink($path.$filename.'.jpg');
    echo json_encode($this->documentos_model->updateDocumento($_POST, $_POST['factura_id'], $_POST['documento_id'], 'f'));
  }

  public function ajax_check_ctrl()
  {
    $query = $this->db
      ->select('id_factura, id_documento, id_embarque')
      ->from('facturacion_doc_embarque')
      ->where('ctrl_embarque', $_POST['no_ctrl'])
      ->get();

    if ($query->num_rows() > 0)
    {

      $res = $query->result();

      if ($res[0]->id_factura == $_POST['id_fac'] && $res[0]->id_documento == $_POST['id_doc'])
        echo 0;
      else
        echo 1;
    }
    else
      echo 0;
  }

  /*
   |-------------------------------------------------------------------------
   |  METODOS PARA IMPRIMIR
   |-------------------------------------------------------------------------
   */

   /**
    * Imprime el Documento Manifiesto Chofer.
    *
    * @return void
    */
    public function imprime_manifiesto_chofer()
    {
      if (isset($_GET['idf']{0}) && isset($_GET['idd']{0}))
      {
        $this->load->model('documentos_model');
        $this->documentos_model->generaDoc($_GET['idf'], $_GET['idd']);
      }
      else redirect(base_url('panel/facturacion/?msg=1'));
   }

   /**
    * Imprime el Documento Manifiesto Chofer.
    *
    * @return void
    */
    public function imprime_embarque()
    {
      if (isset($_GET['idf']{0}) && isset($_GET['idd']{0}))
      {
        $this->load->model('documentos_model');
        $this->documentos_model->generaDoc($_GET['idf'], $_GET['idd']);
      }
      else redirect(base_url('panel/facturacion/?msg=1'));
   }

 /**
  * Imprime el Documento Certificado TLC.
  *
  * @return void
  */
  public function imprime_certificado_tlc()
  {
    if (isset($_GET['idf']{0}) && isset($_GET['idd']{0}))
    {
      $this->load->model('documentos_model');
      $this->documentos_model->generaDoc($_GET['idf'], $_GET['idd']);
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
  }

  /**
  * Imprime el Documento Manifiesto Camion.
  *
  * @return void
  */
  public function imprime_manifiesto_camion()
  {
    if (isset($_GET['idf']{0}) && isset($_GET['idd']{0}))
    {
      $this->load->model('documentos_model');
      $this->documentos_model->generaDoc($_GET['idf'], $_GET['idd']);
    }
    else redirect(base_url('panel/facturacion/?msg=1'));
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
        $txt = 'La Factura se agrego correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'El documento se actualizó correctamente!';
        $icono = 'success';
        break;
    }

    return array(
      'title' => $title,
      'msg' => $txt,
      'ico' => $icono
    );
  }

}