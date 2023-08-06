<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bascula extends MY_Controller {

  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'bascula/import_boletas_intangibles/',

    'bascula/ajax_get_areas/',
    'bascula/ajax_get_empresas/',
    'bascula/ajax_get_proveedores/',
    'bascula/ajax_get_clientes/',
    'bascula/ajax_get_choferes/',
    'bascula/ajax_get_productor/',
    'bascula/ajax_get_camiones/',
    'bascula/ajax_get_calidades/',
    'bascula/ajax_get_precio_calidad/',
    'bascula/ajax_get_kilos/',
    'bascula/ajax_check_limite_proveedor/',
    'bascula/ajax_get_ranchos/',
    'bascula/ajax_get_tablas/',

    'bascula/fotos/',
    'bascula/ajax_pagar_boleta/',
    'bascula/auth_modify/',
    'bascula/puede_modificar/',

    'bascula/show_view_agregar_empresa/',
    'bascula/show_view_agregar_proveedor/',
    'bascula/show_view_agregar_cliente/',
    'bascula/show_view_agregar_chofer/',
    'bascula/show_view_agregar_camion/',

    'bascula/show_view_agregar_lote/',
    'bascula/show_view_ligar_orden/',

    'bascula/rde_pdf/',
    'bascula/rde_xls/',
    'bascula/r_acumulados_pdf/',
    'bascula/r_acumulados_xls/',
	  'bascula/rmc_pdf/',
    'bascula/rbp_pdf/',
    'bascula/rbp_xls/',

    'bascula/rpt_ent_pina_pdf/',
    'bascula/rpt_ent_pina_xls/',
    'bascula/rpt_boletas_salida_pdf/',
    'bascula/rpt_boletas_salida_xls/',
    'bascula/rpt_boletas_porpagar_pdf/',
    'bascula/rpt_boletas_porpagar_xls/',

    'bascula/imprimir_pagadas/',

    'bascula/snapshot/',

    'bascula/ajax_get_next_folio/',
    'bascula/ajax_load_folio/',

    'bascula/imprimir2/',
    'bascula/rmc_pdf2/',

    'bascula/bonificaciones_pdf/',
    'bascula/bonificaciones_xls/',
    'bascula/bitacora_pdf/',

    'bascula/imprimir_recepcion/',
    'bascula/get_boleta/',

    'bascula/get_calidades/',
    'bascula/rdefull_xls/',

    'bascula/rpt_auditorias_pdf/',

    'bascula/imprimir_movimiento/'
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
      array('panel/bascula/admin.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Movimientos'
    );

    $this->load->model('bascula_model');
    $this->load->model('areas_model');

    // Obtiene los datos de la empresa predeterminada.
    $this->load->model('empresas_model');
    $params['empresa_default'] = $this->empresas_model->getDefaultEmpresa();

    $params['basculas'] = $this->bascula_model->getBasculas(true);
    $params['areas'] = $this->areas_model->getAreas();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/admin', $params);
    $this->load->view('panel/footer');
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
      array('general/util.js'),
      array('panel/facturacion/gastos_productos.js'),
      array('panel/bascula/agregar.js'),
      array('panel/bascula/bonificacion.js'),
      array('panel/bascula/clasif.js'),
    ));

    $this->load->model('bascula_model');
    $this->load->model('areas_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Bascula'
    );

    $params['modkbt'] = $this->usuarios_model->tienePrivilegioDe('', 'bascula/modificar_kilosbt/');

    $params['next_folio'] = $this->bascula_model->getSiguienteFolio('en');
    $params['areas']      = $this->areas_model->getAreas();
    $params['unidades'] = $this->db->select('*')->from('unidades')->where('status', 't')->order_by('nombre')->get()->result();

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

      $log = true;
      $authId = false;
      if (isset($_POST['autorizar']))
      {
        $log = true;
        $authId = $_POST['autorizar'];
      }

      $res_mdl = $this->bascula_model->addBascula(null, false, $log, $authId);

      $boletar = $ticket = '';
      // if (isset($_POST['pstatus']))
      //   $ticket = '&p=t';

      if (isset($_GET['p']))
        $ticket = '&p=t';

      if ($this->input->post('ptipo') === 'en') {
        $boletar = $res_mdl['new_boleta']? '&br='.$res_mdl['idb']: '';
      }
      $res_mdl['error'] = isset($res_mdl['error'])? $res_mdl['error']: false;
      if( $res_mdl['error'] == false){
        redirect(base_url('panel/bascula/agregar/?'.MyString::getVarsLink(array('msg', 'fstatus', 'p')).'&msg='.$res_mdl['msg'].$boletar.$ticket));
      }
    }

    $params['accion']      = 'n'; // indica que es nueva entrada
    $params['idb']         = '';
    $params['param_folio'] = '';
    $params['fecha']       = str_replace(' ', 'T', date("Y-m-d H:i"));
    $params['autorizar']   = false;
    $params['fecha_pago']  = '';

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
          $_POST['prancho']       = $info['info'][0]->rancho;
          $_POST['ptabla']        = $info['info'][0]->tabla;
        }
        else
        {
          $this->load->model('clientes_model');
          $cliente = $this->clientes_model->getClienteInfo($info['info'][0]->id_cliente, true);

          $_POST['pcliente']         = $cliente['info']->nombre_fiscal;
          $_POST['pid_cliente']      = $info['info'][0]->id_cliente;
          $_POST['dno_trazabilidad'] = $info['info'][0]->no_trazabilidad;
        }

        if ($info['info'][0]->id_productor != null)
        {
          $_POST['pproductor']    = $info['info'][0]->productor;
          $_POST['pid_productor'] = $info['info'][0]->id_productor;
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

        $_POST['pno_lote'] = $info['info'][0]->no_lote;
        $_POST['pchofer_es_productor'] = $info['info'][0]->chofer_es_productor;

        if (isset($_GET['p']))
        {
          $params['ticket'] = $info['info'][0]->id_bascula;

          if ($info['info'][0]->no_impresiones == 0)
          {
            $info['info'][0]->no_impresiones = 1;
          }
        }

        if (isset($_GET['e']))
          if ($_GET['e'] === 't')
            $params['e'] = true;

        $_POST['ptipo']         = $info['info'][0]->tipo;
        $_POST['parea']         = $info['info'][0]->id_area;
        $_POST['parea_nom']     = $info['info'][0]->area;
        $_POST['pempresa']      = $empresa['info']->nombre_fiscal;
        $_POST['pid_empresa']   = $info['info'][0]->id_empresa;

        $params['next_folio'] = $info['info'][0]->folio;
        if ($info['info'][0]->tipo == 'en') {
          if($info['info'][0]->fecha_tara != '')
            $params['fecha']      =  str_replace(' ', 'T', substr($info['info'][0]->fecha_tara, 0, 16));
          if($info['info'][0]->fecha_bruto != '')
            $params['fecha']      =  substr($info['info'][0]->fecha_bruto, 0, 10).'T'.date("H:i");
          else
            $params['fecha']      =  str_replace(' ', 'T', substr(date("Y-m-d H:i"), 0, 16));
        } else {
          $params['fecha']      =  str_replace(' ', 'T', substr($info['info'][0]->fecha_bruto, 0, 16));
        }

        $params['fecha_pago']   = str_replace(' ', 'T', substr($info['info'][0]->fecha_pago, 0, 16) );

        $_POST['pkilos_brutos']    = $info['info'][0]->kilos_bruto;
        $_POST['pkilos_tara']      = $info['info'][0]->kilos_tara;
        $_POST['pcajas_prestadas'] = $info['info'][0]->cajas_prestadas;
        $_POST['pkilos_neto']      = $info['info'][0]->kilos_neto2 > 0? $info['info'][0]->kilos_neto2 : $info['info'][0]->kilos_neto;
        $_POST['pkilos_neto2']     = $info['info'][0]->kilos_neto2;

        $_POST['pno_lote']         = $info['info'][0]->no_lote;

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
            $_POST['pnum_registro'][] = $c->num_registro;
          }
        }

        if ( isset($info['cajas_clasf']) && count($info['cajas_clasf']) > 0) {
          foreach ($info['cajas_clasf'] as $key => $p) {
            $_POST['prod_did_prod'][$key]           = $p->id_clasificacion;
            $_POST['prod_importe'][$key]            = $p->importe;
            $_POST['prod_ddescripcion'][$key]       = $p->descripcion;
            $_POST['prod_dmedida'][$key]            = $p->unidad;
            $_POST['prod_dcantidad'][$key]          = $p->cantidad;
            $_POST['prod_dpreciou'][$key]           = $p->precio_unitario;
            $_POST['prod_diva_porcent'][$key]       = $p->porcentaje_iva;
            $_POST['prod_diva_total'][$key]         = $p->iva;
            $_POST['prod_dreten_iva_porcent'][$key] = $p->porcentaje_retencion;
            $_POST['prod_dreten_iva_total'][$key]   = $p->retencion_iva;
            $_POST['pkilos'][$key]                  = $p->kilos;
            $_POST['ppromedio'][$key]               = $p->promedio;


            $_POST['isCert'][$key]                  = $p->certificado === 't' ? '1' : '0';
          }
        }

        $_POST['ptotal_cajas']   = $info['info'][0]->total_cajas;
        $_POST['ppesada']        = $info['info'][0]->kilos_neto2;
        $_POST['ptotal']         = $info['info'][0]->importe;
        $_POST['pobcervaciones'] = $info['info'][0]->obcervaciones;

        // Indicara si se necesita autorizacion para modificar.
        $params['autorizar'] = $info['info'][0]->no_impresiones > 0 ? true : false;
        $params['certificado'] = $info['info'][0]->certificado === 't' ? '1' : '0';
        $params['intangible'] = $info['info'][0]->intangible === 't' ? '1' : '0';

        $_POST['pisr'] = $info['info'][0]->ret_isr;
        $_POST['pisrPorcent'] = $info['info'][0]->ret_isr_porcent;

        $params['fotos'] = $info['bascula_fotos'];
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
   * Muestra el formulario para modificar una entrada|salida
   * @return void
   */
  public function modificar()
  {
    if (isset($_GET['folio']{0}) || isset($_GET['idb']{0}))
    {
      if (isset($_GET['folio']{0}))
        redirect(base_url('panel/bascula/agregar/?folio='.$_GET['folio']).'&e=t');

      if (isset($_GET['idb']{0}))
        redirect(base_url('panel/bascula/agregar/?idb='.$_GET['idb']).'&e=t');
    }
  }

  public function import_boletas_intangibles()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('panel/nomina_fiscal/bonos_otros.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Nomina Fiscal'; //activa la opcion del menu
    $params['seo'] = array('titulo' => 'Recetas - Importar Recetas Corona');

    $this->load->model('nomina_fiscal_model');
    $this->load->model('empresas_model');

    if (isset($_FILES['archivo_boletas'])) {
      $this->load->model('bascula_model');
      $res_mdl = $this->bascula_model->importarBoletasIntangibles();
      $_GET['msg'] = $res_mdl['error'];

      if (isset($res_mdl['resumen']) && count($res_mdl['resumen']) > 0) {
        $params['resumen'] = $res_mdl['resumen'];
      }
      if (isset($res_mdl['resumenok']) && count($res_mdl['resumenok']) > 0) {
        $params['resumenok'] = $res_mdl['resumenok'];
      }
      if (isset($res_mdl['print'])) {
        $params['print'] = $res_mdl['print'];
      }
    }

    if(isset($_GET['msg']{0}))
    {
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      if ($_GET['msg'] === '550')
      {
        $params['close'] = true;
      }
    }

    $this->load->view('panel/bascula/importar_boletas_intangibles', $params);
  }

  /**
   * Cancela una bascula entrada|salida
   * @return void
   */
  public function cancelar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('bascula_model');
      $res_mdl = $this->bascula_model->updateBascula($this->input->get('id'), array('status' => 'f'), null, false, false, false);
      if($res_mdl)
        redirect(base_url('panel/bascula/?'.MyString::getVarsLink(array('msg')).'&msg=8'));
    }
    else
      redirect(base_url('panel/bascula/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Activa una bascula entrada|salida
   * @return void
   */
  public function activar()
  {
    if (isset($_GET['id']))
    {
      $this->load->model('bascula_model');
      $res_mdl = $this->bascula_model->updateBascula($this->input->get('id'), array('status' => 't'));
      if($res_mdl)
        redirect(base_url('panel/bascula/?'.MyString::getVarsLink(array('msg')).'&msg=9'));
    }
    else
      redirect(base_url('panel/bascula/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  public function puede_modificar()
  {
    if (isset($_GET['pidb'])) {
      $this->load->model('bascula_model');
      $info = $this->bascula_model->getBasculaInfo($_GET['pidb']);
      echo json_encode(array('puede_modificar' => $info['info'][0]->no_impresiones > 0 ? false : true));
    } else
      echo json_encode(array('puede_modificar' => false));
  }

  /**
   * Muestra el pdf del ticket.
   * @return void
   */
  public function imprimir()
  {
    $this->load->model('bascula_model');
    if($this->input->get('p') == 'true')
      $this->bascula_model->imprimir_ticket($this->input->get('id'));
    else
      $this->load->view('panel/bascula/print_ticket');
  }
  public function imprimir2()
  {
    $this->load->model('bascula_model');

    $params['data'] = $data = $this->bascula_model->getBasculaInfo($this->input->get('id'));
    $this->load->view('panel/bascula/print_ticket2', $params);
  }

  /**
   * Muestra el pdf de BOLETA DE RECEPCION.
   * @return void
   */
  public function imprimir_recepcion()
  {
    $this->load->model('bascula_model');
    if($this->input->get('p') == 'true')
      $this->bascula_model->imprimir_boletaR($this->input->get('id'));
    else
      $this->load->view('panel/bascula/print_boleta_recepcion');
  }

  /**
   * Ver las fotos capturadas
   * @return [type] [description]
   */
  public function fotos()
  {
    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Fotos Bascula'
    );

    $this->load->model('bascula_model');
    $info = $this->bascula_model->getBasculaInfo($_GET['idb']);
    $params['fotos'] = $info['bascula_fotos'];
    $params['noHeader'] = true;

    $this->load->view('panel/header', $params);
    // $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/fotos', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Visualiza el formulario para agregar una bonificacion.
   * @return void
   */
  public function bonificacion()
  {
    if (isset($_GET['idb']))
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
        'titulo' => 'Bonificación'
      );

      // $params['next_folio'] = $this->bascula_model->getSiguienteFolio();
      $params['areas']      = $this->areas_model->getAreas();

      $params['empresa_default'] = $this->db->select("id_empresa, nombre_fiscal")
        ->from("empresas")
        ->where("predeterminado", "t")
        ->get()
        ->row();

      $this->configAddModBascula(true);
      if ($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $this->load->model('bascula_model');
        $res_mdl = $this->bascula_model->addBascula(null, true);

        // $ticket = '';
        // if (isset($_POST['pstatus']))
          $ticket = '&p=t&b='.$res_mdl['idb'];

        $res_mdl['error'] = isset($res_mdl['error'])? $res_mdl['error']: false;
        if( ! $res_mdl['error'])
          redirect(base_url('panel/bascula/bonificacion/?'.MyString::getVarsLink(array('msg', 'fstatus')).'&msg='.$res_mdl['msg'].$ticket));
      }

      // $params['accion'] = 'n'; // indica que es nueva entrada
      $params['idb']    = '';
      $params['param_folio'] = '';
      $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

      $params['e'] = false;

      // if (isset($_GET['id']))
      // {

        $info = $this->bascula_model->getBasculaInfo($_GET['idb']);

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
            $_POST['prancho']       = $info['info'][0]->rancho;
            $_POST['ptabla']        = $info['info'][0]->tabla;
          }
          else
          {
            $this->load->model('clientes_model');
            $cliente = $this->clientes_model->getClienteInfo($info['info'][0]->id_cliente, true);

            $_POST['pcliente']    = $cliente['info']->nombre_fiscal;
            $_POST['pid_cliente'] = $info['info'][0]->id_cliente;
            $_POST['prancho']     = '';
            $_POST['ptabla']      = '';
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

          // $params['param_folio'] = '?id='.$_GET['id'];
          $params['idb']         = $info['info'][0]->id_bascula;
          $params['accion']      = isset($_GET['e']) ? $info['info'][0]->accion : 'n';

          if (isset($_GET['p']))
            $params['ticket'] = $_GET['b']; //$info['info'][0]->id_bascula

          if (isset($_GET['e']))
            if ($_GET['e'] === 't')
              $params['e'] = true;

          $_POST['ptipo']         = $info['info'][0]->tipo;
          $_POST['parea']         = $info['info'][0]->id_area;
          $_POST['pempresa']      = $empresa['info']->nombre_fiscal;
          $_POST['pid_empresa']   = $info['info'][0]->id_empresa;

          $params['next_folio'] = $this->bascula_model->getSiguienteFolio($info['info'][0]->tipo, $info['info'][0]->id_area);
          // $params['next_folio'] = $info['info'][0]->folio;
          // $params['fecha']      =  str_replace(' ', 'T', substr($info['info'][0]->fecha_bruto, 0, 16));

          $_POST['pkilos_brutos']    = $info['info'][0]->kilos_bruto;
          $_POST['pkilos_tara']      = $info['info'][0]->kilos_tara;
          $_POST['pkilos_neto']      = $info['info'][0]->kilos_neto;
          $_POST['pcajas_prestadas'] = $info['info'][0]->cajas_prestadas;

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
              $_POST['pnum_registro'][] = $c->num_registro;
            }
          }

          $_POST['ptotal_cajas']   = $info['info'][0]->total_cajas;
          $_POST['ppesada']        = $info['info'][0]->kilos_neto2;
          $_POST['ptotal']         = isset($_GET['e']) ? $info['info'][0]->importe : 0;
          $_POST['pobcervaciones'] = $info['info'][0]->obcervaciones;

        }
        else
        {
          $_GET['msg'] = '10';
        }

      if (isset($_GET['msg']))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

      $this->load->view('panel/header', $params);
      $this->load->view('panel/general/menu', $params);
      $this->load->view('panel/bascula/bonificacion', $params);
      $this->load->view('panel/footer');
    }
    else
      redirect(base_url('panel/bascula/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
  }

  /**
   * Muestra el formulario para modificar una bonificacion
   * @return void
   */
  public function modificar_bonificacion()
  {
    if (isset($_GET['idb']{0}))
      redirect(base_url('panel/bascula/bonificacion/?idb='.$_GET['idb']).'&e=t');
  }

  public function snapshot(){
    $base64 = UploadFiles::fileToBase64( $this->config->item('base_url_cam_salida_snapshot'), 'jpg');
    UploadFiles::base64SaveImg($base64, 'calando');
    echo $base64;
  }

  /**
   * Muestra la vista para el Reporte "REPORTE DIARIO DE ENTRADAS"
   *
   * @return void
   */
  public function rde()
  {
    $this->carabiner->js(array(
      // array('general/msgbox.js'),
      array('panel/bascula/admin.js'),
      array('panel/bascula/reportes/rde.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Reporte Diario de Entradas'
    );
    $this->load->model('areas_model');

    $params['areas'] = $this->areas_model->getAreas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    // $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/reportes/rde', $params);
    $this->load->view('panel/footer');
  }

 /**
   * Procesa los datos para mostrar el reporte rcr en pdf
   * @return void
   */
  public function rde_pdf()
  {
    $this->load->model('bascula_model');
    if ($this->input->get('ftipo') == 'sa') {
      $this->bascula_model->rdes_pdf();
    } else
      $this->bascula_model->rde_pdf();
  }

  public function rde_xls()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->rde_xls();
  }

  public function rdefull_xls()
  {
    $this->load->model('bascula_model');
    if ($this->input->get('ftipo') == 'sa')
      $this->bascula_model->rdesfull_xls();
    else
      $this->bascula_model->rdefull_xls();
  }

  public function get_calidades()
  {
    $this->load->model('calidades_model');
    $response = $this->calidades_model->get_calidades($this->input->get('id_area'));
    echo json_encode($response);
  }

  /**
   * Muestra la vista para el Reporte "REPORTE BOLETAS PAGADAS"
   *
   * @return void
   */
  public function rbp()
  {
    $this->carabiner->js(array(
      // array('general/msgbox.js'),
      array('panel/bascula/admin.js'),
      array('panel/bascula/reportes/rde.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Reporte Boletas Pagadas'
    );
    $this->load->model('areas_model');

    $params['areas'] = $this->areas_model->getAreas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    // $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/reportes/rbp', $params);
    $this->load->view('panel/footer');
  }

 /**
   * Procesa los datos para mostrar el reporte rcr en pdf
   * @return void
   */
  public function rbp_pdf()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->rbp_pdf();
  }
  public function rbp_xls()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->rbp_xls();
  }

  /**
   * Muestra la vista para el Reporte "REPORTE BOLETAS PAGADAS"
   *
   * @return void
   */
  public function rpt_boletas_salida()
  {
    $this->carabiner->js(array(
      // array('general/msgbox.js'),
      array('panel/bascula/admin.js'),
      array('panel/bascula/reportes/rpt_boletas_salida.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Reporte Salidas de Boletas'
    );
    $this->load->model('areas_model');

    $params['areas'] = $this->areas_model->getAreas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    // $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/reportes/rpt_boletas_salida', $params);
    $this->load->view('panel/footer');
  }
  /**
   * Procesa los datos para mostrar el reporte rcr en pdf
   * @return void
   */
  public function rpt_boletas_salida_pdf()
  {
    $this->load->model('bascula2_model');
    $this->bascula2_model->rpt_boletas_salida_pdf();
  }
  public function rpt_boletas_salida_xls()
  {
    $this->load->model('bascula2_model');
    $this->bascula2_model->rpt_boletas_salida_pdf();
  }

  public function rpt_boletas_porpagar()
  {
    $this->carabiner->js(array(
      // array('general/msgbox.js'),
      array('panel/bascula/admin.js'),
      array('panel/bascula/reportes/rpt_boletas_porpagar.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Reporte Boletas Por Pagar'
    );
    $this->load->model('areas_model');

    $params['areas'] = $this->areas_model->getAreas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    // $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/reportes/rpt_boletas_porpagar', $params);
    $this->load->view('panel/footer');
  }
  public function rpt_boletas_porpagar_pdf()
  {
    $this->load->model('bascula2_model');
    $this->bascula2_model->rpt_boletas_porpagar_pdf();
  }
  public function rpt_boletas_porpagar_xls()
  {
    $this->load->model('bascula2_model');
    $this->bascula2_model->rpt_boletas_porpagar_xls();
  }

  public function rpt_ent_pina()
  {
    $this->carabiner->js(array(
      // array('general/msgbox.js'),
      array('panel/bascula/admin.js'),
      array('panel/bascula/reportes/rpt_ent_pina.js')
    ));
    $this->carabiner->css(array(
      array('panel/tags.css', 'screen'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Reporte Boletas Pagadas'
    );
    $this->load->model('areas_model');

    $params['areas'] = $this->areas_model->getAreas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    // $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/reportes/rpt_ent_pina', $params);
    $this->load->view('panel/footer');
  }
  /**
   * Procesa los datos para mostrar el reporte rcr en pdf
   * @return void
   */
  public function rpt_ent_pina_pdf()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->rpt_entrada_fruta_pdf();
  }
  public function rpt_ent_pina_xls()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->rpt_entrada_fruta_xls();
  }

  /**
   * Procesa los datos para mostrar el reporte rcr en pdf
   * @return void
   */
  public function rmc_pdf2()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->rmc_pdf();
  }
  public function rmc_pdf()
  {
    $this->load->model('bascula_model');
    $this->load->model('empresas_model');

    $params['isXml'] = (isset($_GET['tipoo']) && $_GET['tipoo'] == 'xls');
    $params['data'] = $this->bascula_model->getMovimientos();
    $params['empresa'] = $this->empresas_model->getInfoEmpresa((empty($_GET['fid_empresa'])? 2: $_GET['fid_empresa']), true);
    $params['empresa'] = isset($params['empresa']['info'])? $params['empresa']['info']: null;

    if ($params['isXml']) {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=reporte_movimientos.xls");
      header("Pragma: no-cache");
      header("Expires: 0");

      echo $this->load->view('panel/bascula/reportes/rmc', $params, true);
      exit;
    } else {
      $this->load->view('panel/bascula/reportes/rmc', $params);
    }
  }

  public function rptAuditoria()
  {
    // if (isset($_GET['fid_proveedor']))
    //   if ($_GET['fid_proveedor'] == '')
    //     redirect(base_url('panel/bascula/movimientos/?msg=13'));
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      array('panel/bascula/movimientos_cuenta.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Movimientos de Cuenta'
    );

    $this->load->model('bascula_model');
    $this->load->model('areas_model');

    $params['areas'] = $this->areas_model->getAreas();
    $_GET['farea'] = empty($_GET['farea'])? $params['areas']['areas'][0]->id_area: $_GET['farea'];
    // $params['movimientos'] = $this->bascula_model->getMovimientos();

    // echo "<pre>";
    //   var_dump($params['movimientos']);
    // echo "</pre>";exit;

    if (isset($_GET['p']) && isset($_GET['pe']))
    {
      $params['p'] = true;
      $params['pe'] = $_GET['pe'];
    }

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    // $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/reportes/rpt_auditorias', $params);
    $this->load->view('panel/footer');
  }
  public function rpt_auditorias_pdf()
  {
    $this->load->model('bascula2_model');

    $params['data'] = $this->bascula2_model->getMovimientosAuditoria();
    if($this->input->get('ftipop') == 'sa') {
      $this->load->view('panel/bascula/reportes/rpt_auditorias_sa_pdf', $params);
    }else{
      $this->load->view('panel/bascula/reportes/rpt_auditorias_pdf', $params);
    }
  }

  /**
   * Muestra la vista para realizar movimientos de cuenta.
   * @return void
   */
  public function movimientos()
  {
    // if (isset($_GET['fid_proveedor']))
    //   if ($_GET['fid_proveedor'] == '')
    //     redirect(base_url('panel/bascula/movimientos/?msg=13'));
    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('general/supermodal.js'),
      array('general/util.js'),
      array('panel/bascula/movimientos_cuenta.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Movimientos de Cuenta'
    );

    $this->load->model('bascula_model');
    $this->load->model('areas_model');

    $params['areas'] = $this->areas_model->getAreas();
    $_GET['farea'] = empty($_GET['farea'])? $params['areas']['areas'][0]->id_area: $_GET['farea'];
    $params['movimientos'] = $this->bascula_model->getMovimientos();

    // echo "<pre>";
    //   var_dump($params['movimientos']);
    // echo "</pre>";exit;

    if (isset($_GET['p']) && isset($_GET['pe']))
    {
      $params['p'] = true;
      $params['pe'] = $_GET['pe'];
    }

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    // $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/movimientos_cuenta', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Muestra la vista para realizar movimientos de cuenta.
   * @return void
   */
  public function admin_movimientos()
  {
    $this->carabiner->js(array(
        array('general/msgbox.js'),
        array('panel/bascula/admin.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Pesadas'
    );

    $this->load->model('bascula_model');
    $this->load->model('areas_model');

    $params['basculas'] = $this->bascula_model->getPagos();
    $params['areas'] = $this->areas_model->getAreas();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/admin_movimientos', $params);
    $this->load->view('panel/footer');
  }

  public function cancelar_movimiento()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('bascula_model');

      $res_mdl = $this->bascula_model->cancelar_pago($_GET['id'], true);
      redirect(base_url('panel/bascula/admin_movimientos/?'.MyString::getVarsLink(array('msg', 'p', 'pe')).'&msg=15'));
    }else
      redirect(base_url('panel/bascula/admin_movimientos/?'.MyString::getVarsLink(array('msg', 'p', 'pe')).'&msg=1'));
  }

  public function imprimir_movimiento()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->imprimir_pago($_GET['id']);
  }

  public function pago_basculas()
  {
    $this->load->model('bascula_model');

    $res_mdl = $this->bascula_model->pago_basculas();

    if ($res_mdl['passess'])
    {
      $res_mdl = $this->bascula_model->updateBascula($this->input->get('id'), array('status' => 'f'), null, false, false, false);

      $pesadas = '&pe='.implode(',', $_POST['ppagos']);
      redirect(base_url('panel/bascula/movimientos/?'.MyString::getVarsLink(array('msg', 'p', 'pe')).'&msg=14&p=t'.$pesadas));
    }
  }

  public function imprimir_pagadas()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->rmc_pdf();
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
   * Procesa los datos para mostrar el reporte r_acumulados en pdf
   * @return void
   */
  public function r_acumulados_pdf()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->r_acumulados_pdf();
  }
  public function r_acumulados_xls()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->r_acumulados_xls();
  }

  /**
   * Muestra la vista para el reporte bonificaciones.
   * @return void
   */
  public function bonificaciones()
  {
    if (isset($_GET['fid_proveedor']))
      if ($_GET['fid_proveedor'] == '')
        redirect(base_url('panel/bascula/movimientos/?msg=13'));

    $this->carabiner->js(array(
      // array('general/msgbox.js'),
      array('panel/bascula/reportes/r_bonificaciones.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Reporte de bonificaciones'
    );

    $this->load->model('bascula_model');
    $this->load->model('areas_model');

    $params['movimientos'] = $this->bascula_model->getMovimientos();
    $params['areas'] = $this->areas_model->getAreas();

    // echo "<pre>";
    //   var_dump($params['movimientos']);
    // echo "</pre>";exit;

    if (isset($_GET['p']) && isset($_GET['pe']))
    {
      $params['p'] = true;
      $params['pe'] = $_GET['pe'];
    }

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/bascula/reportes/r_bonificaciones', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Procesa los datos para mostrar el reporte bonificaciones en pdf
   * @return void
   */
  public function bonificaciones_pdf()
  {
    $this->load->model('bascula_rpts_model');
    $this->bascula_rpts_model->bonificaciones_pdf();
  }
  public function bonificaciones_xls()
  {
    $this->load->model('bascula_rpts_model');
    $this->bascula_rpts_model->bonificaciones_xls();
  }


  /*
   |------------------------------------------------------------------------
   | Metodos para mostrar los formulario en el supermodal.
   |------------------------------------------------------------------------
   */

  /**
   * Muestra formulario agregar empresa.
   * @return void
   */
  public function show_view_agregar_empresa()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('panel/clientes/frm_addmod.js'),
      array('panel/bascula/fix_empresas.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Empresa'
    );

    $this->configAddModEmpresa();

    if($this->form_validation->run() == FALSE){
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }else{
      $this->load->model('empresas_model');
      $respons = $this->empresas_model->addEmpresa();

      if($respons[0])
        redirect(base_url('panel/empresas/agregar/?'.MyString::getVarsLink(array('msg')).'&msg='.$respons[2].'&close=1'));
      else
        $params['frm_errors'] = $this->showMsgs(2, $respons[1]);
    }

    $params['closeModal'] = false;
    if (isset($_GET['close']))
      $params['closeModal'] = true;

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    // $this->load->view('panel/header', $params);
    // $this->load->view('panel/general/menu', $params);
    // $this->load->view('panel/empresas/agregar', $params);
    // $this->load->view('panel/footer');

    $params['template'] = $this->load->view('panel/empresas/agregar', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
  }

  /**
   * Muestra formulario agregar area.
   * @return void
   */
  public function show_view_agregar_proveedor()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('general/keyjump.js'),
      array('panel/proveedores/addmod.js'),
      array('panel/bascula/fix_proveedores.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Proveedor'
    );

    $this->configAddModProveedor();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('proveedores_model');
      $res_mdl = $this->proveedores_model->addProveedor();

      if(!$res_mdl['error'])
        redirect(base_url('panel/bascula/show_view_agregar_proveedor/?'.MyString::getVarsLink(array('msg')).'&msg=4&close=1'));
    }

    $params['closeModal'] = false;
    if (isset($_GET['close']))
      $params['closeModal'] = true;

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $params['template'] = $this->load->view('panel/proveedores/agregar', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
  }

  public function show_view_agregar_cliente()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('general/keyjump.js'),
      array('panel/proveedores/addmod.js'),
      array('panel/bascula/fix_clientes.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Cliente'
    );

    $this->configAddModCliente();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('clientes_model');
      $res_mdl = $this->clientes_model->addCliente();

      if(!$res_mdl['error'])
        redirect(base_url('panel/bascula/show_view_agregar_cliente/?'.MyString::getVarsLink(array('msg')).'&msg=11&close=1'));
    }

    $params['closeModal'] = false;
    if (isset($_GET['close']))
      $params['closeModal'] = true;

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->model('empresas_model');
    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();

    $this->load->model('documentos_model');
    $params['documentos'] = $this->documentos_model->getDocumentos();

    $params['template'] = $this->load->view('panel/clientes/agregar', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
  }

  /**
   * Muestra formulario agregar chofer.
   * @return void
   */
  public function show_view_agregar_chofer()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('panel/bascula/fix_choferes.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar chofer'
    );

    $this->configAddModChofer();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('choferes_model');
      $res_mdl = $this->choferes_model->addChofer();

      if(!$res_mdl['error'])
        redirect(base_url('panel/bascula/show_view_agregar_chofer/?'.MyString::getVarsLink(array('msg')).'&msg=5&close=1'));
    }

    $params['closeModal'] = false;
    if (isset($_GET['close']))
      $params['closeModal'] = true;

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $params['template'] = $this->load->view('panel/choferes/agregar', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
  }

  /**
   * Muestra formulario agregar camion.
   * @return void
   */
  public function show_view_agregar_camion()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));
    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('panel/bascula/fix_camiones.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar camion'
    );

    $this->configAddModCamion();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('camiones_model');
      $res_mdl = $this->camiones_model->addCamion();

      if(!$res_mdl['error'])
        redirect(base_url('panel/bascula/show_view_agregar_camion/?'.MyString::getVarsLink(array('msg')).'&msg=6&close=1'));
    }

    $params['closeModal'] = false;
    if (isset($_GET['close']))
      $params['closeModal'] = true;

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $params['template'] = $this->load->view('panel/camiones/agregar', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
  }

  /**
   * Muestra formulario agregar camion.
   * @return void
   */
  public function show_view_agregar_lote()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      // array('panel/bascula/fix_camiones.js'),
    ));

    $this->load->model('bascula_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar camion'
    );

    $this->configAddLote();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('bascula_model');

      $bascula = $this->bascula_model->getBasculaInfo($_GET['idb']);

      // Determina si se hara el registro en la bitacora.
      $regBitacora = $bascula['info'][0]->no_lote === null ? false : true;

      $res_mdl = $this->bascula_model->updateBascula(
        $_GET['idb'],
        array(
          'no_lote' => $this->input->post('pno_lote'),
          'chofer_es_productor' => isset($_POST['pchofer_es_productor']) ? 't' : 'f'
        ),
        null,
        $regBitacora,
        $this->session->userdata['id_usuario'],
        false
      );

      if($res_mdl['passes'])
        redirect(base_url('panel/bascula/show_view_agregar_lote/?'.MyString::getVarsLink(array('msg')).'&msg=15&close=1'));
    }

    $data = $this->bascula_model->getBasculaInfo($_GET['idb']);

    $params['no_lote']     = $data['info'][0]->no_lote;
    $params['chofer_prod'] = $data['info'][0]->chofer_es_productor;

    $params['closeModal'] = false;
    if (isset($_GET['close']))
      $params['closeModal'] = true;

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $params['template'] = $this->load->view('panel/bascula/agregar_lote', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
  }

  /**
   * Muestra formulario para ligar ordenes compra.
   * @return void
   */
  public function show_view_ligar_orden()
  {
    $this->carabiner->css(array(
      array('libs/jquery.uniform.css', 'screen'),
    ));

    $this->carabiner->js(array(
      array('libs/jquery.uniform.min.js'),
      array('libs/jquery.numeric.js'),
      array('panel/compras_ordenes/admin.js'),
      // array('panel/bascula/fix_camiones.js'),
    ));

    $this->load->model('bascula_model');

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar camion'
    );

    $this->configAddLigOrden();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->load->model('bascula_model');

      $res_mdl = $this->bascula_model->ligarOrdenes($_GET['idb'], $_POST);

      if($res_mdl)
        redirect(base_url('panel/bascula/show_view_ligar_orden/?'.MyString::getVarsLink(array('msg')).'&msg=30&close=1'));
    }

    if (isset($_GET['Buscar'])) {
      $this->load->model('compras_ordenes_model');
      $_GET['fstatus'] = 'a';
      $params['ordenes'] = $this->compras_ordenes_model->getOrdenes();
    }

    $data = $this->bascula_model->getOrdenesLigadas($_GET['idb']);

    $params['ordenes_lig'] = $data;
    $params['lig_entrego'] = isset($data[0]->entrego)? $data[0]->entrego: '';
    $params['lig_recibio'] = isset($data[0]->recicio)? $data[0]->recicio: '';

    $params['closeModal'] = false;
    if (isset($_GET['close']))
      $params['closeModal'] = true;

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $params['template'] = $this->load->view('panel/bascula/ligar_orden', $params, true);

    $this->load->view('panel/bascula/supermodal', $params);
  }

  /*
   |------------------------------------------------------------------------
   | Metodos con validaciones de formulario.
   |------------------------------------------------------------------------
   */

  public function configAddModBascula($bonificacion = false)
  {
    $this->load->library('form_validation');

    $rules = array(
      array('field' => 'certificado',
            'label' => 'Certificado',
            'rules' => ''),
      array('field' => 'intangible',
            'label' => 'intangible',
            'rules' => ''),
      array('field' => 'ptipo',
            'label' => 'Tipo',
            'rules' => 'required'),
      array('field' => 'pfolio',
            'label' => 'Folio',
            'rules' => 'required|is_natural_no_zero|callback_chkfolio'),
      array('field' => 'pfecha',
            'label' => 'Fecha',
            'rules' => 'required'),

      array('field' => 'parea',
            'label' => 'Area',
            'rules' => 'required'),
      array('field' => 'pid_empresa',
            'label' => 'Empresa',
            'rules' => 'required'),
      array('field' => 'pempresa',
            'label' => '',
            'rules' => ''),
      array('field' => 'pid_proveedor',
            'label' => 'Proveedor',
            'rules' => ''),
      array('field' => 'pproveedor',
            'label' => '',
            'rules' => ''),
      array('field' => 'pid_cliente',
            'label' => 'Cliente',
            'rules' => ''),
      array('field' => 'pcliente',
            'label' => '',
            'rules' => ''),
      // array('field' => 'dno_trazabilidad',
      //       'label' => '',
      //       'rules' => 'max_length[15]|callback_check_trazabilidad'),
      array('field' => 'pid_chofer',
            'label' => 'Chofer',
            'rules' => ''),
      array('field' => 'pchofer',
            'label' => '',
            'rules' => ''),
      array('field' => 'pid_camion',
            'label' => 'Camión',
            'rules' => ''),
      array('field' => 'pcamion',
            'label' => '',
            'rules' => ''),

      array('field' => 'pkilos_brutos',
            'label' => '',
            'rules' => ''),
      array('field' => 'pkilos_tara',
            'label' => '',
            'rules' => ''),
      array('field' => 'pkilos_neto',
            'label' => '',
            'rules' => ''),

      array('field' => 'ptotal_cajas',
            'label' => '',
            'rules' => ''),
      array('field' => 'ppesada',
            'label' => '',
            'rules' => ''),
      array('field' => 'ptotal',
            'label' => '',
            'rules' => ''),
      array('field' => 'pisr',
            'label' => '',
            'rules' => ''),
      array('field' => 'pisrPorcent',
            'label' => '',
            'rules' => ''),
      array('field' => 'pobcervaciones',
            'label' => 'Observaciones',
            'rules' => 'max_length[254]'),

      array('field' => 'pcajas_prestadas',
            'label' => 'Cajas Prestadas',
            'rules' => ''),



    );

    if (isset($_POST['paccion']))
    {
      if ($_POST['paccion'] == 'n')
      {

        if ($_POST['ptipo'] == 'en')
        {
          $requiredd = 'required';
          if ($bonificacion) {
            $requiredd = '';
          }
          $rules[] = array('field' => 'pkilos_brutos',
                           'label' => 'Kilos Brutos',
                           'rules' => 'required');

          $rules[] = array('field' => 'prancho',
                           'label' => 'Rancho',
                           'rules' => $requiredd);
          $rules[] = array('field' => 'ptabla',
                           'label' => 'Tabla/Lote',
                           'rules' => $requiredd);
        }
        else
        {
          $rules[] = array('field' => 'pkilos_tara',
                           'label' => 'Kilos tara',
                           'rules' => 'required');
          $rules[] = array('field' => 'prancho',
                           'label' => 'Rancho',
                           'rules' => '');
          $rules[] = array('field' => 'ptabla',
                           'label' => 'Tabla/Lote',
                           'rules' => '');
        }
      }
      else if ($_POST['paccion'] == 'en' || $_POST['paccion'] == 'sa')
      {
        $rules[] = array('field' => 'pkilos_brutos',
                         'label' => 'Kilos Brutos',
                         'rules' => 'required');

        $rules[] = array('field' => 'pkilos_tara',
                         'label' => 'Kilos Tara',
                         'rules' => 'required');

        $rules[] = array('field' => 'pkilos_neto',
                         'label' => 'Kilos Neto',
                         'rules' => 'required');

        if ($_POST['ptipo'] === 'en') {
          $rules[] = array('field' => 'pcajas[]',
            'label' => 'Calidad cajas',
            'rules' => 'required');
          $rules[] = array('field' => 'pcalidad[]',
                'label' => 'Calidad calidad',
                'rules' => 'required');
          $rules[] = array('field' => 'pcalidadtext[]',
                'label' => 'Calidad calidadtext',
                'rules' => 'required');
          $rules[] = array('field' => 'pkilos[]',
                'label' => 'Calidad kilos',
                'rules' => '');
          $rules[] = array('field' => 'ppromedio[]',
                'label' => 'Calidad promedio',
                'rules' => '');
          $rules[] = array('field' => 'pprecio[]',
                'label' => 'Calidad precio',
                'rules' => 'required');
          $rules[] = array('field' => 'pimporte[]',
                'label' => 'Calidad importe',
                'rules' => 'required');
        }
      }
    }

    if (isset($_POST['ptipo']))
    {
      if ($_POST['ptipo'] === 'en') {
        $rules[] = array('field'  => 'pid_proveedor',
                          'label' => 'Proveedor',
                          'rules' => 'required');

        if ($_POST['parea'] == 2) {
          $rules[] = array('field'  => 'pid_productor',
                            'label' => 'Productor',
                            'rules' => $bonificacion? '': 'required');
        }
      }
      else
      {
        $rules[] = array('field' => 'pid_cliente',
                         'label' => 'Cliente',
                         'rules' => 'required');

        $rules[] = array('field' => 'dno_trazabilidad',
                         'label' => 'No Trazabilidad',
                         'rules' => 'max_length[15]|callback_check_trazabilidad');

        $rules[] = array('field' => 'pid_chofer',
                         'label' => 'Chofer',
                         'rules' => 'required');

        $rules[] = array('field' => 'pid_camion',
                         'label' => 'Camión',
                         'rules' => 'required');
      }
    }

    $this->form_validation->set_rules($rules);
  }

  public function chkfolio($folio)
  {
    if ( ! isset($_GET['idb']) && ! isset($_GET['e']))
    {
      $result = $this->db->query("SELECT Count(id_bascula) AS num FROM bascula
        WHERE folio = {$folio} AND tipo = '{$this->input->post('ptipo')}'
        AND id_area = {$this->input->post('parea')}")->row();
      // var_dump($result->num);exit();
      if($result->num > 0){
        $this->form_validation->set_message('chkfolio', 'El folio ya existe, intenta con otro.');
        return false;
      }else
        return true;
    }
  }

  public function check_trazabilidad($value)
  {
    if (trim($value) != '') {
      $sql = !empty($_POST['pidb'])? " AND b.id_bascula <> {$_POST['pidb']}": '';
      $error = false;
      $query = $this->db->query("SELECT b.id_bascula, b.no_trazabilidad
                                   FROM bascula b
                                   WHERE b.no_trazabilidad = '{$value}'
                                    AND b.id_empresa = {$this->input->post('pid_empresa')}
                                    AND b.status = 't'
                                    {$sql}");

      if ($query->num_rows() > 0)
      {
        $data = $query->row();

        $this->form_validation->set_message('check_trazabilidad', "El numero de trazabilidad '{$data->no_trazabilidad}' ya esta registrado.");
        return false;
      }
    }

    return true;
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
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configAddModProveedor($accion='agregar')
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fnombre_fiscal',
            'label' => 'Nombre fiscal',
            'rules' => 'required|max_length[140]'),
      array('field' => 'fcalle',
            'label' => 'Calle',
            'rules' => 'max_length[60]'),
      array('field' => 'fno_exterior',
            'label' => 'No. exterior',
            'rules' => 'max_length[7]'),
      array('field' => 'fno_interior',
            'label' => 'No. interior',
            'rules' => 'max_length[7]'),
      array('field' => 'fcolonia',
            'label' => 'Colonia',
            'rules' => 'max_length[60]'),
      array('field' => 'flocalidad',
            'label' => 'Localidad',
            'rules' => 'max_length[45]'),
      array('field' => 'fmunicipio',
            'label' => 'Municipio',
            'rules' => 'max_length[45]'),
      array('field' => 'festado',
            'label' => 'Estado',
            'rules' => 'max_length[45]'),

      array('field' => 'frfc',
            'label' => 'RFC',
            'rules' => 'max_length[13]'),
      array('field' => 'fcurp',
            'label' => 'CURP',
            'rules' => 'max_length[35]'),
      array('field' => 'fcp',
            'label' => 'CP',
            'rules' => 'max_length[10]'),
      array('field' => 'ftelefono',
            'label' => 'Telefono',
            'rules' => 'max_length[15]'),
      array('field' => 'fcelular',
            'label' => 'Celular',
            'rules' => 'max_length[20]'),

      array('field' => 'femail',
            'label' => 'Email',
            'rules' => 'max_length[70]|valid_email'),
      array('field' => 'ftipo_proveedor',
            'label' => 'Tipo de proveedor',
            'rules' => 'required|max_length[2]'),
      array('field' => 'fcuenta_cpi',
            'label' => 'Cuenta ContpaqI',
            'rules' => 'max_length[12]'),
    );

    $this->form_validation->set_rules($rules);
  }

  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configAddModCliente($accion='agregar')
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fnombre_fiscal',
            'label' => 'Nombre fiscal',
            'rules' => 'required|max_length[140]'),
      array('field' => 'fcalle',
            'label' => 'Calle',
            'rules' => 'max_length[60]'),
      array('field' => 'fno_exterior',
            'label' => 'No. exterior',
            'rules' => 'max_length[7]'),
      array('field' => 'fno_interior',
            'label' => 'No. interior',
            'rules' => 'max_length[7]'),
      array('field' => 'fcolonia',
            'label' => 'Colonia',
            'rules' => 'max_length[60]'),
      array('field' => 'flocalidad',
            'label' => 'Localidad',
            'rules' => 'max_length[45]'),
      array('field' => 'fmunicipio',
            'label' => 'Municipio',
            'rules' => 'max_length[45]'),
      array('field' => 'festado',
            'label' => 'Estado',
            'rules' => 'max_length[45]'),

      array('field' => 'frfc',
            'label' => 'RFC',
            'rules' => 'max_length[13]'),
      array('field' => 'fcurp',
            'label' => 'CURP',
            'rules' => 'max_length[35]'),
      array('field' => 'fcp',
            'label' => 'CP',
            'rules' => 'max_length[10]'),
      array('field' => 'ftelefono',
            'label' => 'Telefono',
            'rules' => 'max_length[15]'),
      array('field' => 'fcelular',
            'label' => 'Celular',
            'rules' => 'max_length[20]'),

      array('field' => 'femail',
            'label' => 'Email',
            'rules' => 'max_length[70]|valid_email'),
      array('field' => 'fcuenta_cpi',
            'label' => 'Cuenta ContpaqI',
            'rules' => 'max_length[12]'),
    );

    $this->form_validation->set_rules($rules);
  }

  /*
  | Asigna las reglas para validar un articulo al agregarlo
  */
  public function configAddModChofer($accion='agregar')
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fnombre',
            'label' => 'Nombre',
            'rules' => 'required|max_length[120]|callback_chckNombre'),
    );

    $this->form_validation->set_rules($rules);
  }

  public function chckNombre($nombre)
  {
    $result = $this->db->query("SELECT COUNT(nombre) as total
      FROM choferes
      WHERE lower(nombre) = '".mb_strtolower($nombre)."'")->row();

    if($result->total > 0){
        $this->form_validation->set_message('chckNombre', 'El nombre del chofer ya existe.');
      return false;
    }else
      return true;
  }

  public function configAddModCamion($accion='agregar')
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'fplacas',
            'label' => 'Placas',
            'rules' => 'required|max_length[15]'),
      array('field' => 'fmodelo',
            'label' => 'Modelo',
            'rules' => 'max_length[15]'),
      array('field' => 'fmarca',
            'label' => 'Marca',
            'rules' => 'max_length[15]'),
    );

    $this->form_validation->set_rules($rules);
  }

  public function configAddLote()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'pno_lote',
            'label' => 'No. Lote',
            'rules' => 'required|integer'),
      array('field' => 'pchofer_es_productor',
            'label' => 'Chofer es productor',
            'rules' => ''),
    );

    $this->form_validation->set_rules($rules);
  }

  public function configAddLigOrden()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'lig_entrego',
            'label' => 'Entrego',
            'rules' => 'required'),
      array('field' => 'lig_recibio',
            'label' => 'Recibio',
            'rules' => 'required'),
      array('field' => 'lig_ordenes[]',
            'label' => 'Recibio',
            'rules' => ''),
    );

    $this->form_validation->set_rules($rules);
  }

  /*
   |------------------------------------------------------------------------
   | Metodos para peticiones Ajax.
   |------------------------------------------------------------------------
   */

   /**
    * Obtiene las areas por peticion Ajax.
    * @return void
    */
  public function ajax_get_areas()
  {
    $this->load->model('areas_model');
    echo json_encode($this->areas_model->getEmpresasAjax());
  }

   /**
    * Obtiene las empresas por peticion Ajax.
    * @return void
    */
  public function ajax_get_empresas()
  {
    $this->load->model('empresas_model');
    echo json_encode($this->empresas_model->getEmpresasAjax());
  }

  /**
    * Obtiene los proveedores por peticion Ajax.
    * @return void
    */
  public function ajax_get_proveedores()
  {
    $this->load->model('proveedores_model');
    echo json_encode($this->proveedores_model->getProveedoresAjax());
  }

  /**
    * Obtiene los ranchos por peticion Ajax.
    * @return void
    */
  public function ajax_get_ranchos()
  {
    $this->load->model('proveedores_model');
    echo json_encode($this->proveedores_model->getRanchosAjax());
  }

  /**
    * Obtiene los ranchos por peticion Ajax.
    * @return void
    */
  public function ajax_get_tablas()
  {
    $this->load->model('proveedores_model');
    echo json_encode($this->proveedores_model->getTablasAjax());
  }

  /**
    * Obtiene los proveedores por peticion Ajax.
    * @return void
    */
  public function ajax_get_clientes()
  {
    $this->load->model('clientes_model');
    echo json_encode($this->clientes_model->getClientesAjax());
  }

  /**
    * Obtiene los choferes por peticion Ajax.
    * @return void
    */
  public function ajax_get_choferes()
  {
    $this->load->model('choferes_model');
    echo json_encode($this->choferes_model->getChoferesAjax());
  }

  public function ajax_get_productor()
  {
    $this->load->model('productores_model');
    echo json_encode($this->productores_model->getProductorAjax());
  }

  /**
    * Obtiene los choferes por peticion Ajax.
    * @return void
    */
  public function ajax_get_camiones()
  {
    $this->load->model('camiones_model');
    echo json_encode($this->camiones_model->getCamionesAjax());
  }

  /**
    * Obtiene las calidades de un area por peticion Ajax.
    * @return void
    */
  public function ajax_get_calidades()
  {
    $this->load->model('calidades_model');
    echo json_encode($this->calidades_model->getCalidades($_GET['id'], false));
  }

 /**
  * Obtiene las calidades de un area por peticion Ajax.
  * @return void
  */
  public function ajax_get_precio_calidad()
  {
    $this->load->model('calidades_model');
    echo json_encode($this->calidades_model->getCalidadInfo($_GET['id'], true));
  }

  /**
  * Funcion para simular la peticion ajax al otro servidor.
  * @return void
  */
  public function ajax_get_kilos()
  {
    echo '{"msg":true,"data":{"id":"dRmVAfDOq","fecha":"2013-06-04 15:09:04","peso":"500"}}';
  }

  public function ajax_get_next_folio()
  {
    $this->load->model('bascula_model');
    echo $this->bascula_model->getSiguienteFolio($_GET['tipo'], $_GET['area']);
  }

  public function ajax_load_folio()
  {
    $this->load->model('bascula_model');
    echo $this->bascula_model->getIdfolio($_GET['folio'], $_GET['tipo'], $_GET['area']);
  }

  public function ajax_check_limite_proveedor()
  {
    $this->load->model('bascula_model');
    echo json_encode($this->bascula_model->checkLimiteProveedor($_GET['idp']));
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

      case 30:
        $txt = 'Las ordenes se ligaron correctamente!';
        $icono = 'success';
        break;

      case 20:
        $txt = 'Se modifico correctamente la compra!';
        $icono = 'success';
        break;

      case 500:
        $txt = 'Las boletas se guardaron correctamente.';
        $icono = 'success';
        break;
      case 501:
        $txt = 'Ocurrió un error al subir el archivo de boletas.';
        $icono = 'error';
        break;
      case 502:
        $txt = 'Ocurrió un error al leer el archivo de boletas.';
        $icono = 'error';
        break;
      case 503:
        $txt = 'Algunas boletas no se guardaron, revisar el detalle de errores.';
        $icono = 'error';
        break;
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

  public function ajax_pagar_boleta()
  {
    $this->load->model('bascula_model');

    // $fechaPago = $this->db->query(
    //   "SELECT fecha_pago
    //    FROM bascula
    //    WHERE id_bascula = {$_GET['idb']}"
    // )->row()->fecha_pago;

    $this->bascula_model->pagarBoleta($_GET['idb']);

    echo json_encode(array('passes' => true));
  }

  public function auth_modify()
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'usuario',
        'label'   => 'Usuario',
        'rules'   => 'required'),
      array('field' => 'pass',
        'label'   => 'Contraseña',
        'rules'   => 'required')
    );

    $this->form_validation->set_rules($rules);
    if ($this->form_validation->run() == false)
    {
      $resp = array(
        'passes' => false,
        'title'  => 'Error al Autorizar el Usuario!',
        'msg'    => preg_replace("[\n|\r|\n\r]", '', validation_errors()),
        'ico'    => 'error',
        'fecha'  => date("Y-m-d\TH:i"),
      );
    }
    else
    {
      $data = "usuario = '".$this->input->post('usuario')."' AND password = '".$this->input->post('pass')."' AND status = '1' ";
      $sql = $this->db->get_where('usuarios', $data);

      if ($sql->num_rows() > 0)
      {
        $user = $sql->result();
        // echo "<pre>";
        //   var_dump($user);
        // echo "</pre>";exit;

        $this->load->model('usuarios_model');
        $tienePriv = $this->usuarios_model->tienePrivilegioDe('', 'bascula/modificar-auth/', false, $user[0]->id);

        $fechaPriv = true;
        if ($this->input->post('tipo') === 'fecha') {
          $fechaPriv = $this->usuarios_model->tienePrivilegioDe('', 'bascula/mfecha/', false, $user[0]->id);
        }

        if ($tienePriv && $fechaPriv)
        {
           $resp = array(
            'passes'  => true,
            'title'   => '',
            'msg'     => 'Usuario autenticado!',
            'ico'     => 'successs',
            'user_id' => $user[0]->id
          );
        }
        else
        {
           $resp = array(
            'passes' => false,
            'title'  => 'Error!',
            'msg'    => 'El usuario no cuenta con el privilegio de editar!',
            'ico'    => 'error'
          );
        }
      }
      else
      {
        $resp = array(
          'passes' => false,
          'title' => 'Error al Autorizar el Usuario!',
          'msg'   => 'El usuario y/o contraseña son incorrectos',
          'ico'   => 'error'
        );
      }
    }

    echo json_encode($resp);
  }

  /**
   * Muestra la vista para el Reporte "REPORTE DE ACUMULADOS DE PRODUCTOS"
   *
   * @return void
   */
  public function bitacora()
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
    $this->load->view('panel/bascula/reportes/bitacora', $params);
    $this->load->view('panel/footer');
  }

  public function bitacora_pdf()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->bitacora_pdf();
  }

  /**
   * ***************************************************************
   * ************* FACTURAS *******************
   * @return [type] [description]
   */
  public function facturas()
  {
    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('general/msgbox.js'),
      array('panel/compras_ordenes/admin.js'),
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Administración de Compras'
    );

    $this->load->library('pagination');
    $this->load->model('bascula_model');

    $params['compras'] = $this->bascula_model->getFacturas();

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/admin_facturas', $params);
    $this->load->view('panel/footer');
  }

  public function facturas_agregar()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('general/supermodal.js'),
      array('general/msgbox.js'),
      array('general/util.js'),
      array('panel/bascula/admin_facturas.js'),
    ));
    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Agregar Factura'
    );

    $this->load->model('proveedores_model');
    $this->load->model('bascula_model');
    $this->load->model('compras_ordenes_model');
    $this->load->model('areas_model');
    $this->load->model('empresas_model');

    $this->configUpdateXml();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->bascula_model->addFactura($_POST, $_FILES['xml']);

      $params['frm_errors'] = $this->showMsgs(20);
    }

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));
    $params['areas']  = $this->areas_model->getAreas();

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/bascula/agregar_facturas', $params);
    $this->load->view('panel/footer');
  }

  public function facturas_ver()
  {
    $this->carabiner->js(array(
      array('general/supermodal.js'),
      array('general/msgbox.js'),
      array('panel/bascula/admin_facturas.js'),
    ));

    $this->load->model('proveedores_model');
    $this->load->model('bascula_model');
    $this->load->model('compras_ordenes_model');

    $this->configUpdateXml();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->bascula_model->updateFactura($_GET['id'], $_GET['idp'], (isset($_FILES['xml'])? $_FILES['xml']: null));

      $params['frm_errors'] = $this->showMsgs(20);
    }

    $params['proveedor'] = $this->proveedores_model->getProveedorInfo($_GET['idp'], true);

    $params['compra'] = $this->bascula_model->getInfoFactura($_GET['id'], true);

    $this->load->view('panel/bascula/ver_facturas', $params);
  }

  public function facturas_cancelar()
  {
    $this->load->model('bascula_model');
    $this->bascula_model->cancelarFactura($_GET['id']);

    redirect(base_url('panel/bascula/facturas/?' . MyString::getVarsLink(array('id')).'&msg=3'));
  }

  public function get_boleta()
  {
    $this->load->model('bascula_model');
    $res = $this->bascula_model->getBasculaInfo(false, $_GET['pfolio'], false, array('b.tipo' => $_GET['ptipo'], 'b.id_area' => $_GET['parea']));
    echo json_encode($res);
  }

  public function configUpdateXml()
  {
    $this->load->library('form_validation');

    $rules = array(
      // array('field' => 'xml',
      //       'label' => 'XML',
      //       'rules' => 'callback_xml_check'),
      array('field' => 'aux',
            'label' => '',
            'rules' => ''),
    );

    $this->form_validation->set_rules($rules);
  }

  public function xml_check($file)
  {
    if ($_FILES['xml']['type'] !== '' && $_FILES['xml']['type'] !== 'text/xml')
    {
      $this->form_validation->set_message('xml_check', 'El %s debe ser un archivo XML.');
      return false;
    }
    else
    {
      return true;
    }
  }

}

/* End of file bascula.php */
/* Location: ./application/controllers/panel/bascula.php */