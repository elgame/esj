<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ventas extends MY_Controller {
  /**
   * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
   * @var unknown_type
   */
  private $excepcion_privilegio = array(
    'ventas/get_folio/',

    'facturacion/rvc_pdf/',
    'facturacion/rvp_pdf/',

    'facturacion/ajax_get_clasificaciones/',
    'facturacion/ajax_get_empresas_fac/'
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
      array('panel/ventas_remision/admin.js'),
    ));

    $this->load->library('pagination');
    $this->load->model('ventas_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']        = array('titulo' => 'Ventas de remisión');

    $params['datos_s'] = $this->ventas_model->getVentas();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/ventas_remision/admin',$params);
    $this->load->view('panel/footer',$params);
  }

  /**
   * Agrega una nota de remision a la bd
   *
   * @return void
   */
  public function agregar()
  {
    $this->carabiner->js(array(
        array('libs/jquery.numeric.js'),
        array('general/keyjump.js'),
        array('general/util.js'),
        array('panel/ventas_remision/frm_addmod.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['seo']            = array('titulo' => 'Agregar Nota remisión');
    $params['pagar_ordent']   = false;

    $this->load->model('ventas_model');

    $this->configAddModFactura();
    if($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $respons = $this->ventas_model->addNotaVenta();

      if($respons[0])
        redirect(base_url('panel/ventas/agregar/?msg=4&id='.$respons[2]));
    }

    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    if (isset($_GET['id']))
    {
      $params['id'] = $_GET['id'];
    }
    
    // Obtiene los datos de la empresa predeterminada.
    $params['empresa_default'] = $this->db
      ->select("e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org")
      ->from("empresas AS e")
      ->where("e.predeterminado", "t")
      ->get()
      ->row();

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/ventas_remision/agregar', $params);
    $this->load->view('panel/footer');
  }

  public function pagar()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('ventas_model');
      $this->ventas_model->pagaNotaRemison($_GET['id']);

      redirect(base_url('panel/ventas/?'.String::getVarsLink(array('msg','id')).'&msg=9'));
    }
  }

  public function cancelar()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('ventas_model');
      $this->ventas_model->cancelaNotaRemison($_GET['id']);

      redirect(base_url('panel/ventas/?'.String::getVarsLink(array('msg','id')).'&msg=5'));
    }
  }

  /**
   * Configura los metodos de agregar y modificar
   */
  private function configAddModFactura()
  {
    $this->load->library('form_validation');
    $rules = array(

        array('field'   => 'did_empresa',
              'label'   => 'Empresa',
              'rules'   => 'required|numeric'),
        array('field'   => 'did_cliente',
              'label'   => 'Cliente',
              'rules'   => 'required|numeric'),
        array('field'   => 'dfolio',
              'label'   => 'Folio',
              'rules'   => 'required|numeric|callback_seriefolio_check'),

        array('field'   => 'dfecha',
              'label'   => 'Fecha',
              'rules'   => 'required|max_length[25]'), //|callback_isValidDate

        array('field'   => 'total_importe',
              'label'   => 'SubTotal',
              'rules'   => 'required|numeric'),
        array('field'   => 'total_totfac',
              'label'   => 'Total',
              'rules'   => 'required|numeric|callback_val_total'),

        array('field'   => 'dforma_pago',
              'label'   => 'Forma de pago',
              'rules'   => 'required|max_length[80]'),
        array('field'   => 'dmetodo_pago',
              'label'   => 'Metodo de pago',
              'rules'   => 'required|max_length[40]'),
        array('field'   => 'dcondicion_pago',
              'label'   => 'Condición de pago',
              'rules'   => 'required|max_length[2]'),
        array('field'   => 'dplazo_credito',
              'label'   => 'Plazo de crédito',
              'rules'   => 'numeric'),

        array('field'   => 'dempresa',
              'label'   => 'Empresa',
              'rules'   => ''),
        array('field'   => 'dcliente',
              'label'   => 'Cliente',
              'rules'   => ''),
        array('field'   => 'dcliente_rfc',
              'label'   => 'Cliente',
              'rules'   => ''),
        array('field'   => 'dcliente_domici',
              'label'   => 'Cliente',
              'rules'   => ''),
        array('field'   => 'dcliente_ciudad',
              'label'   => 'Cliente',
              'rules'   => ''),
        array('field'   => 'dttotal_letra',
              'label'   => 'letra',
              'rules'   => ''),
        array('field'   => 'dreten_iva',
              'label'   => 'Retecion IVA',
              'rules'   => ''),

        array('field'   => 'prod_did_prod[]',
              'label'   => 'prod_did_prod',
              'rules'   => ''),
        array('field'   => 'prod_dcantidad[]',
              'label'   => 'prod_dcantidad',
              'rules'   => ''),
        array('field'   => 'prod_ddescripcion[]',
              'label'   => 'prod_ddescripcion',
              'rules'   => ''),
        array('field'   => 'prod_dpreciou[]',
              'label'   => 'prod_dpreciou',
              'rules'   => ''),
        array('field'   => 'prod_importe[]',
              'label'   => 'prod_importe',
              'rules'   => ''),
        array('field'   => 'prod_dmedida[]',
              'label'   => 'prod_dmedida',
              'rules'   => ''),
    );
    $this->form_validation->set_rules($rules);
  }

  /**
   * Verifica que la serie y folio enviados del form no esten asignados a una
   * factura y tambien que esten vigentes.
   *
   * @param string $str
   * @return boolean
   */
  public function seriefolio_check($str){
    if($str != ''){
      $sql = $ms = '';

      $res = $this->db->select('Count(id_venta) AS num')
        ->from('facturacion_ventas_remision')
        ->where("folio = ".$str." AND id_empresa = ". $this->input->post('did_empresa'))
        ->get();
      $data = $res->row();
      if($data->num > 0){
        $this->form_validation->set_message('seriefolio_check', 'El folio ya esta utilizado por otra Nota.');
        return false;
      }
    }
    return true;
  }

  public function val_total($str){
    if($str <= 0){
      $this->form_validation->set_message('val_total', 'El Total no puede ser 0, verifica los datos ingresados.');
      return false;
    }
    return true;
  }

  public function imprimir()
  {
    $this->load->model('facturacion_model');
    if(isset($_GET['id']{0}) && $this->facturacion_model->exist('facturas', 'id_factura = '.$_GET['id'])){
      //factura

      $data = $this->facturacion_model->getInfoFactura($_GET['id']);

      $res = $this->db->select('*')->
                        from('facturas_series_folios')->
                        where("serie = '".$data['info']->serie."' AND id_empresa = ".$data['info']->id_empresa)->get();
      $data_serie = $res->row();
      $res->free_result();


      $res = $this->db->select('*')->
                        from('empresas')->
                        where('id_empresa = '.$data['info']->id_empresa)->get();
      $data_empresa = $res->row();

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->show_head = false;
      $pdf->AddPage();

      $pdf->Image(APPPATH.'images/factura.jpg', .5, 0, 215, 279);
      if ($data_empresa->logo != '')
        $pdf->Image($data_empresa->logo, 11, 12, 40, 0); // Logo de la Empresa

      $y = 40;

      $pdf->SetXY(51, 9);
      $pdf->SetFont('Arial','B', 12);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(118, 4, $data_empresa->nombre_fiscal, 0, 0, 'C');

      $pdf->SetXY(51, 13);
      $pdf->SetFont('Arial','B', 9);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(118, 4, 'R.F.C. '.$data_empresa->rfc, 0, 0, 'C');


      $calle = '';
      if ($data_empresa->calle !== '')
        $calle = $data_empresa->calle;
      if ($data_empresa->no_interior !== '')
        $calle .= ' No. '.$data_empresa->no_interior;
      if ($data_empresa->no_exterior !== '')
        $calle .= ' No. '.$data_empresa->no_exterior;

      $colonia = '';
      if ($data_empresa->colonia !== '')
        $colonia = ' COL. '.$data_empresa->colonia;

      $colmuni = '';
      if($data_empresa->cp !== '')
        $colmuni = ' C.P. '.$data_empresa->cp;
      if($data_empresa->municipio !== '')
        $colmuni .= ' '.$data_empresa->municipio;
      if($data_empresa->estado !== '')
        $colmuni .= ' '.$data_empresa->estado;

      $pdf->SetXY(51, 17);
      $pdf->SetFont('Arial','', 9);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(118, 4, $calle, 0, 0, 'C');

      $pdf->SetXY(51, 21);
      $pdf->SetFont('Arial','', 9);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(118, 4, $colonia, 0, 0, 'C');

      $pdf->SetXY(51, 25);
      $pdf->SetFont('Arial','', 9);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(118, 4, $colmuni, 0, 0, 'C');

      $pdf->SetXY(51, 29);
      $pdf->SetFont('Arial','B', 9);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(118, 4, 'TEL/FAX: '.$data_empresa->telefono.(($data_empresa->celular !== '')?'   CEL:'.$data_empresa->celular:''), 0, 0, 'C');

      $www = '';
      if ($data_empresa->pag_web !== '')
        $www = $data_empresa->pag_web;
      if ($data_empresa->email !== '')
        $www .= '      Email: '.$data_empresa->email;

      $pdf->SetXY(51, 33);
      $pdf->SetFont('Arial','B', 8);
      $pdf->SetTextColor(204, 0, 0);
      $pdf->Cell(118, 4, $www, 0, 0, 'C');

      $pdf->SetXY(170, 15);
      $pdf->SetFont('Arial','', 12);
      $pdf->SetTextColor(204, 0, 0);
      $pdf->Cell(37, 6, ($data['info']->serie!=''? $data['info']->serie: '').$data['info']->folio, 0, 0, 'C');

      $pdf->SetFont('Arial','B', 7);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetXY(170, 26);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(37));
      $pdf->Row(array('EXPEDIDA EN '.$data_empresa->municipio.', '.$data_empresa->estado), false, false);

      $pdf->SetXY(158, 40);
      $pdf->SetFont('Arial','', 10);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(48, 6, $data['info']->fecha, 0, 0, 'C');

      $pdf->SetXY(158, 50);
      $pdf->SetFont('Arial','', 10);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(48, 6, $data['info']->forma_pago, 0, 0, 'C');
      /*$pdf->SetXY(182, 50);
      $pdf->Cell(25, 6, $data['info']->serie, 0, 0, 'C');*/
      $pdf->SetXY(158, 58);
      $pdf->Cell(48, 6, ($data['info']->condicion_pago=='cr'? 'CREDITO': 'CONTADO'), 0, 0, 'C');

      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(128));

      $pdf->SetXY(28, 36);
      // $pdf->Cell(128, 6, $data['info']->cliente->nombre_fiscal, 0, 0, 'L');
      $pdf->Row(array($data['info']->cliente->nombre_fiscal), false, false);
      $pdf->SetFont('Arial','', 9);
      $pdf->SetXY(28, 43);
      // $pdf->Cell(128, 6, $data['info']->domicilio, 0, 0, 'L');
      $pdf->Row(array($data['info']->domicilio), false, false);

      $pdf->SetXY(28, 52);
      $pdf->Cell(128, 6, $data['info']->ciudad, 0, 1, 'L');
      $pdf->SetXY(28, 58);
      $pdf->Cell(128, 6, strtoupper($data['info']->cliente->rfc), 0, 1, 'L');

      $pdf->SetY(70);
      $aligns = array('C', 'C', 'L', 'C', 'C');
      $widths = array(14, 18, 113, 24, 27);
      $header = array('', '', '', '', '');

      foreach($data['productos'] as $key => $item)
      {
        $band_head = false;
        if($pdf->GetY() >= 200 || $key==0){ //salta de pagina si exede el max
          if($key > 0)
            $pdf->AddPage();
        }

        $pdf->SetFont('Arial','',8);
        $pdf->SetTextColor(0,0,0);

        $datos = array($item->cantidad, ($item->unidad !== NULL ? $item->unidad : $item->unidad2), $item->descripcion,
                      String::formatoNumero($item->precio_unitario),
                      String::formatoNumero($item->importe));

        $pdf->SetX(11);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false, false);
      }

      $y = 214;

      $pdf->SetFont('Arial','', 8.5);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFillColor(232,232,232);
      $pdf->SetXY(156, $y);
      $pdf->Cell(24, 4, 'SUB-TOTAL', 1, 0, 'L', 1);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFillColor(255,255,255);
      $pdf->SetXY(180, $y);
      $pdf->Cell(27, 4, string::formatoNumero($data['info']->subtotal), 1, 0, 'L');


      if (floatval($data['info']->descuento) > 0)
      {
        $y += 4;
        $pdf->SetFont('Arial','', 8.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(232,232,232);
        $pdf->SetXY(156, 218);
        $pdf->Cell(24, 4, 'DESC.', 1, 0, 'L', 1);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY(180, 218);
        $pdf->Cell(27, 4, string::formatoNumero($data['info']->descuento), 1, 0, 'L', 1);

        $y += 4;
        $pdf->SetFont('Arial','', 8.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(232,232,232);
        $pdf->SetXY(156, 222);
        $pdf->Cell(24, 4, 'SUB-TOTAL', 1, 0, 'L', 1);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY(180, 222);
        $pdf->Cell(27, 4, string::formatoNumero(floatval($data['info']->subtotal) - floatval($data['info']->descuento)), 1, 0, 'L', 1);
      }

      $y += 4;

      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFillColor(232,232,232);
      $pdf->SetXY(156, $y);
      $pdf->Cell(24, 4, 'I.V.A.', 1, 0, 'L', 1);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFillColor(255,255,255);
      $pdf->SetXY(180, $y);
      $pdf->Cell(27, 4, string::formatoNumero($data['info']->importe_iva), 1, 0, 'L', 1);

      if (floatval($data['info']->retencion_iva) > 0)
      {
        $y += 4;
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(232,232,232);
        $pdf->SetXY(156, $y);
        $pdf->Cell(24, 4, 'Ret. I.V.A.', 1, 0, 'L', 1);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY(180, $y);
        $pdf->Cell(27, 4, string::formatoNumero($data['info']->retencion_iva), 1, 0, 'L', 1);
      }


      $y += 4;
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFillColor(232,232,232);
      $pdf->SetXY(156, $y);
      $pdf->Cell(24, 4, 'TOTAL', 1, 0, 'L', 1);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFillColor(255,255,255);
      $pdf->SetXY(180, $y);
      $pdf->Cell(27, 4, string::formatoNumero($data['info']->total), 1, 0, 'L', 1);

      $pdf->SetXY(51, 214);
      $pdf->Cell(105, 24, '', 1, 0, 'L');

      $pdf->SetXY(51, 217);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(105));
      $pdf->Row(array(strtoupper(string::num2letras($data['info']->total))), false, false);

      $pdf->Image(APPPATH.'images/series_folios/'.$data['info']->img_cbb, 11, 217, 34, 34); // 185

      $pdf->SetFont('Arial','', 8);
      $pdf->SetXY(50, 238);
      $pdf->Cell(155, 6, $data_serie->leyenda1, 0, 0, 'L');
      $pdf->SetXY(50, 241);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(155));
      $pdf->Row(array($data_serie->leyenda2.' '.$data_serie->ano_aprobacion), false, false); // .' '.$data['info']->ano_aprobacion

      // $pdf->SetXY(50, 229);
      // $pdf->Cell(106, 6, $data['info']->forma_pago, 0, 0, 'L');
      $pdf->SetXY(50, 233);
      $pdf->Cell(106, 6, 'Metodo de pago: '.$data['info']->metodo_pago.', '.$data['info']->metodo_pago_digitos, 0, 0, 'L');

      $pdf->SetFont('Arial','', 10);
      $pdf->SetXY(50, 249);
      $pdf->Cell(155, 6, $data_empresa->regimen_fiscal, 0, 0, 'L');

      $pdf->SetXY(10, 252);
      $pdf->Cell(155, 6, "SICOFI ".$data_serie->no_aprobacion, 0, 0, 'L');

      // $pdf->SetXY(170, 258);
      // $pdf->SetFont('Arial','B', 12);
      // $pdf->SetTextColor(204, 0, 0);
      // $pdf->Cell(35, 8, ($data['info']->serie!=''? $data['info']->serie.'-': '').$data['info']->folio, 0, 0, 'C');

      if($data['info']->status == 'ca')
        $pdf->Image(APPPATH.'images/cancelado.png', 3, 9, 215, 270);

      $pdf->Output('Factura.pdf', 'I');
    }else
      redirect(base_url('panel/facturacion'));
  }




  /**
   * obtiene el folio siguiente de la serie seleccionada
   */
  public function get_folio()
  {
    if(isset($_GET['ide']))
    {
      $this->load->model('ventas_model');
      $res = $this->ventas_model->getFolio($_GET['ide']);

      $param =  $this->showMsgs(2, $res[1]);
      $param['data'] = $res[0];
      echo json_encode($param);
    }
  }


  /*
   |-------------------------------------------------------------------------
   |  AJAX
   |-------------------------------------------------------------------------
   */


  /*
   |-------------------------------------------------------------------------
   |  REPORTES
   |-------------------------------------------------------------------------
   */
  public function rvc()
  {
    $this->carabiner->js(array(
      array('panel/facturacion/admin.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte Ventas Cliente');

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/rvc',$params);
    $this->load->view('panel/footer',$params);
  }


  public function rvc_pdf()
  {
    $this->load->model('facturacion_model');
    $this->facturacion_model->rvc_pdf();
  }

  public function rvp()
  {
    $this->carabiner->js(array(
      array('panel/facturacion/admin.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Facturacion'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Reporte Ventas Producto');

    $query = $this->db->query("SELECT id_familia, nombre
                               FROM productos_familias");

    $params['familias'] = $query->result();

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/rvp',$params);
    $this->load->view('panel/footer',$params);
  }

  public function rvp_pdf()
  {
    $this->load->model('facturacion_model');
    $this->facturacion_model->rvp_pdf();
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
        $txt = 'La Nota de remisión se modifico correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'La Nota de remisión se agrego correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La Nota de remisión se cancelo correctamente.';
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
    }

    return array(
        'title' => $title,
        'msg' => $txt,
        'ico' => $icono);
  }

}

?>