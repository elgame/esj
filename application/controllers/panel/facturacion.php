<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class facturacion extends MY_Controller {
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
    'facturacion/ajax_get_clientes/',
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
   * Agrega una factura a la bd
   *
   * @return void
   */
  public function agregar()
  {
    $this->carabiner->js(array(
        array('libs/jquery.numeric.js'),
        array('general/keyjump.js'),
        array('general/util.js'),
        array('panel/facturacion/frm_addmod.js'),
    ));

    $params['info_empleado']  = $this->info_empleado['info']; //info empleado
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']            = array('titulo' => 'Agregar factura');
    $params['pagar_ordent']   = false;

    if(isset($_GET['ordent']{0}))
      $this->asignaOrdenTrabajo($_GET['ordent']);

    $this->load->library('cfdi');
    $this->load->model('facturacion_model');

    $this->configAddModFactura();
    if($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $respons = $this->facturacion_model->addFactura();

      if($respons['passes'])
        redirect(base_url('panel/documentos/agregar/?msg=3&id='.$respons['id_factura']));
    }

    $params['series'] = $this->facturacion_model->getSeriesFolios(100);
    $params['fecha']  = str_replace(' ', 'T', date("Y-m-d H:i"));

    if (isset($_GET['id']))
    {
      $params['id'] = $_GET['id'];
    }
    else
    {
      // Obtiene los datos de la empresa predeterminada.
      $params['empresa_default'] = $this->db
        ->select("e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org")
        ->from("empresas AS e")
        ->where("e.predeterminado", "t")
        ->get()
        ->row();

      // Obtiene el numero de certificado de la empresa predeterminada.
      $params['no_certificado'] = $this->cfdi->obtenNoCertificado($params['empresa_default']->cer_org);
    }

    if(isset($_GET['msg']{0}))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/facturacion/agregar', $params);
    $this->load->view('panel/footer');
  }

  /**
   * Paga una factura.
   *
   * @return void
   */
  public function pagar()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('facturacion_model');
      $this->facturacion_model->pagaFactura();

      redirect(base_url('panel/facturacion/?'.String::getVarsLink(array('msg','id')).'&msg=9'));
    }
  }

  /**
   * Cancela una factura.
   *
   * @return void
   */
  public function cancelar()
  {
    if (isset($_GET['id']{0}))
    {
      $this->load->model('facturacion_model');
      $this->facturacion_model->cancelaFactura();

      redirect(base_url('panel/facturacion/?'.String::getVarsLink(array('msg','id')).'&msg=5'));
    }
  }

  /**
   * Configura los metodos de agregar y modificar
   *
   * @return void
   */
  private function configAddModFactura()
  {
    $this->load->library('form_validation');
    $rules = array(

        array('field'   => 'did_empresa',
              'label'   => 'Empresa',
              'rules'   => 'required|max_length[25]'),
        array('field'   => 'did_cliente',
              'label'   => 'Cliente',
              'rules'   => 'required|max_length[25]'),
        array('field'   => 'dserie',
              'label'   => 'Serie',
              'rules'   => 'max_length[25]'),
        array('field'   => 'dfolio',
              'label'   => 'Folio',
              'rules'   => 'required|numeric|callback_seriefolio_check'),
        array('field'   => 'dno_aprobacion',
              'label'   => 'Numero de aprobacion',
              'rules'   => 'required|numeric'),
        array('field'   => 'dano_aprobacion',
              'label'   => 'Fecha de aprobacion',
              'rules'   => 'required|max_length[10]|callback_isValidDate'),

        array('field'   => 'dfecha',
              'label'   => 'Fecha',
              'rules'   => 'required|max_length[25]'), //|callback_isValidDate

        array('field'   => 'total_importe',
              'label'   => 'SubTotal1',
              'rules'   => 'required|numeric'),
        array('field'   => 'total_subtotal',
              'label'   => 'SubTotal',
              'rules'   => 'required|numeric'),

        array('field'   => 'total_descuento',
              'label'   => 'Descuento',
              'rules'   => 'required|numeric'),
        array('field'   => 'total_iva',
              'label'   => 'IVA',
              'rules'   => 'required|numeric'),
        array('field'   => 'total_retiva',
              'label'   => 'Retencion IVA',
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
        array('field'   => 'dmetodo_pago_digitos',
              'label'   => 'Ultimos 4 digitos',
              'rules'   => 'max_length[20]'),
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
        array('field'   => 'prod_ddescuento[]',
              'label'   => 'prod_ddescuento',
              'rules'   => ''),
        array('field'   => 'prod_ddescuento_porcent[]',
              'label'   => 'prod_ddescuento_porcent',
              'rules'   => ''),
        array('field'   => 'prod_dpreciou[]',
              'label'   => 'prod_dpreciou',
              'rules'   => ''),
        array('field'   => 'prod_importe[]',
              'label'   => 'prod_importe',
              'rules'   => ''),
        array('field'   => 'prod_diva_total[]',
              'label'   => 'prod_diva_total',
              'rules'   => ''),
        array('field'   => 'prod_dreten_iva_total[]',
              'label'   => 'prod_dreten_iva_total',
              'rules'   => ''),
        array('field'   => 'prod_dreten_iva_porcent[]',
              'label'   => 'prod_dreten_iva_porcent',
              'rules'   => ''),
        array('field'   => 'prod_diva_porcent[]',
              'label'   => 'prod_diva_porcent',
              'rules'   => ''),
        array('field'   => 'prod_dmedida[]',
              'label'   => 'prod_dmedida',
              'rules'   => ''),

        array('field'   => 'dversion',
              'label'   => '',
              'rules'   => ''),
        array('field'   => 'dcer_caduca',
              'label'   => 'Empresa',
              'rules'   => 'callback_chk_cer_caduca'),

        array('field'   => 'dno_certificado',
              'label'   => 'No. Certificado',
              'rules'   => 'required'),
        array('field'   => 'dtipo_comprobante',
              'label'   => 'Tipo comproante',
              'rules'   => 'required'),
        array('field'   => 'dobservaciones',
              'label'   => 'Observaciones',
              'rules'   => ''),
    );
    $this->form_validation->set_rules($rules);
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


  public function chk_cer_caduca($date)
  {
    $hoy = date('Y-m-d');

    if (strtotime($hoy) > strtotime($date))
    {
      $this->form_validation->set_message('chk_cer_caduca', 'El certificado de la empresa caducó, actualize la información de la misma.');
      return false;
    }

    return true;
  }

  /**
   * Verifica que la serie y folio enviados del form no esten asignados a una
   * factura y tambien que esten vigentes.
   *
   * @param string $str
   * @return boolean
   */
  public function seriefolio_check($str)
  {
    if($str != ''){
      $sql = $ms = '';

      $res = $this->db->select('Count(id_factura) AS num')
        ->from('facturacion')
        ->where("serie = '".$this->input->post('dserie')."' AND folio = ".$str." AND id_empresa = ". $this->input->post('did_empresa'))
        ->get();
      $data = $res->row();
      if($data->num > 0){
        $this->form_validation->set_message('seriefolio_check', 'El serie y folio ya esta utilizado por otra Factura.');
        return false;
      } else {
        $anoLimite = date('Y-m-d',strtotime($this->input->post('dano_aprobacion') . " + 730 day"));

        $hoy = date('Y-m-d');
        // $hoy = '2015-07-19';

        if (strtotime($hoy) > strtotime($anoLimite))
        {
          $this->form_validation->set_message('seriefolio_check', 'El serie y folio ya caducaron, no pueden ser utilizados.');
          return false;
        }
      }
    }
    return true;
  }

  /**
   * Form_validation: Valida su una fecha esta en formato correcto
   */
  public function isValidDate($str)
  {
    if($str != ''){
      if(String::isValidDate($str) == false){
        $this->form_validation->set_message('isValidDate', 'El campo %s no es una fecha valida');
        return false;
      }
    }
    return true;
  }

  public function val_total($str)
  {
    if($str <= 0){
      $this->form_validation->set_message('val_total', 'El Total no puede ser 0, verifica los datos ingresados.');
      return false;
    }
    return true;
  }

   /*
   |-------------------------------------------------------------------------
   |  SERIES Y FOLIOS
   |-------------------------------------------------------------------------
   */

  /**
   * Permite administrar los series y folios para la facturacion.
   *
   * @return void
   */
  public function series_folios()
  {
    // $this->carabiner->css(array(
    //     array('general/forms.css','screen'),
    //     array('general/tables.css','screen')
    // ));

    $this->load->library('pagination');
    $this->load->model('facturacion_model');

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['opcmenu_active'] = 'Ventas'; //activa la opcion del menu
    $params['seo']        = array('titulo' => 'Administrar Series y Folios');

    $params['datos_s'] = $this->facturacion_model->getSeriesFolios();

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/series_folios/admin',$params);
    $this->load->view('panel/footer',$params);
  }

  public function agregar_serie_folio()
  {
    $this->carabiner->js(array(
        array('panel/facturacion/series_folios/frm_addmod.js')
    ));

    $params['info_empleado']  = $this->info_empleado['info'];
    $params['seo']  = array('titulo' => 'Agregar Series y Folios');

    $this->load->model('facturacion_model');

    $this->configAddSerieFolio();
    if($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2,preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $model_resp = $this->facturacion_model->addSerieFolio();

      if($model_resp['passes'])
        redirect(base_url('panel/facturacion/agregar_serie_folio/?'.String::getVarsLink(array('msg')).'&msg=6'));
    }

    if(isset($_GET['msg']{0}))
        $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header',$params);
    $this->load->view('panel/general/menu',$params);
    $this->load->view('panel/facturacion/series_folios/agregar',$params);
    $this->load->view('panel/footer',$params);
  }

  public function modificar_serie_folio()
  {
    if(isset($_GET['id']{0})){
      $this->carabiner->js(array(
          array('panel/facturacion/series_folios/frm_addmod.js')
      ));

      $this->load->model('facturacion_model');
      $this->configAddSerieFolio('edit');

      if($this->form_validation->run() == FALSE)
      {
        $params['frm_errors'] = $this->showMsgs(2,preg_replace("[\n|\r|\n\r]", '', validation_errors()));
      }
      else
      {
        $model_resp = $this->facturacion_model->editSerieFolio($_GET['id']);

        if($model_resp['passes'])
          $params['frm_errors'] = $this->showMsgs(7);
      }

      $params['info_empleado']  = $this->info_empleado['info'];
      $params['opcmenu_active']   = 'Ventas'; //activa la opcion del menu
      $params['seo']['titulo']  = 'Modificar Serie y Folio';

      $params['serie_info'] = $this->facturacion_model->getInfoSerieFolio($_GET['id']);

      if(isset($_GET['msg']{0}))
          $params['frm_errors'] = $this->showMsgs($_GET['msg']);

        $this->load->view('panel/header',$params);
        $this->load->view('panel/general/menu',$params);
        $this->load->view('panel/facturacion/series_folios/modificar',$params);
        $this->load->view('panel/footer',$params);
    }
    else
      redirect(base_url('panel/facturacion/index_serie_folios/').String::getVarsLink(array('msg')).'&msg=1');
  }

  /**
   * obtiene el folio siguiente de la serie seleccionada
   */
  public function get_folio()
  {
    if(isset($_GET['serie']) && isset($_GET['ide']))
    {
      $this->load->model('facturacion_model');
      $res = $this->facturacion_model->getFolioSerie($_GET['serie'], $_GET['ide'], "es_nota_credito = 'f'");

      $param =  $this->showMsgs(2, $res[1]);
      $param['data'] = $res[0];
      echo json_encode($param);
    }
  }

  /**
   * obtiene el folio siguiente de la serie seleccionada
   */
  public function get_series()
  {
    if(isset($_GET['ide']))
    {
      $this->load->model('facturacion_model');
      $res = $this->facturacion_model->getSeriesEmpresa($_GET['ide']);

      $param =  $this->showMsgs(2, $res[1]);
      $param['data'] = $res[0];
      echo json_encode($param);
    }
  }

  private function configAddSerieFolio($tipo='add')
  {
    $this->load->library('form_validation');

    $rules = array(
            array('field' => 'fid_empresa',
                'label' => 'Empresa',
                'rules' => 'required|numeric'),
            array('field' => 'fempresa',
                'label' => 'Empresa',
                'rules' => 'min_length[1]'),

            array('field' => 'fno_aprobacion',
                'label' => 'No Aprobación',
                'rules' => 'required|numeric'),
            array('field' => 'ffolio_inicio',
                'label' => 'Folio Inicio',
                'rules' => 'required|is_natural'),
            array('field' => 'ffolio_fin',
                'label' => 'Folio Fin',
                'rules' => 'required|is_natural'),
            array('field' => 'fano_aprobacion',
                'label' => 'Fecha Aprobación',
                'rules' => 'required|max_length[10]|callback_isValidDate'),
            array('field' => 'fleyenda',
                'label' => 'Leyenda',
                'rules' => ''),
            array('field' => 'fleyenda1',
                'label' => 'Leyenda 1',
                'rules' => ''),
            array('field' => 'fleyenda2',
                'label' => 'Leyenda 2',
                'rules' => ''),
            array('field' => 'fnota_credito',
                'label' => 'Nota de Credito',
                'rules' => ''),
        );

    if($tipo=='add')
    {
      // if(isset($_FILES['durl_img']))
      //   if($_FILES['durl_img']['name']!='')
      //     $_POST['durl_img'] = 'ok';

      $rules[] = array('field' => 'fserie',
                       'label' => 'Serie',
                       'rules' => 'max_lenght[30]|callback_isValidSerie[add]');
      // $rules[] = array('field'  => 'durl_img',
      //     'label' => 'Imagen',
      //     'rules' => 'required');
    }

    if($tipo=='edit'){
      $rules[] = array('field'  => 'fserie',
                        'label' => 'Serie',
                        'rules' => 'max_lenght[30]|callback_isValidSerie[edit]');
    }

    $this->form_validation->set_rules($rules);
  }

  /**
   * Form_validation: Valida si la Serie ya existe
   */
  public function isValidSerie($str, $tipo)
  {
    $str = $str=='' ? '' : $str;

    if ($str != '')
    {
      if($tipo=='add'){
        if($this->facturacion_model->exist('facturacion_series_folios',
            array('serie' => mb_strtoupper($str), 'id_empresa' => $this->input->post('fid_empresa')) )){
          $this->form_validation->set_message('isValidSerie', 'El campo %s ya existe');
          return false;
        }
        return true;
      }
      else{
        $row = $this->facturacion_model->exist('facturacion_series_folios',
          array('serie' => mb_strtoupper($str), 'id_empresa' => $this->input->post('fid_empresa')), true);

        if($row!=FALSE){
          if($row->id_serie_folio == $_GET['id'])
            return true;
          else{
            $this->form_validation->set_message('isValidSerie', 'El campo %s ya existe');
            return false;
          }
        }return true;
      }
    }
  }

  /*
   |-------------------------------------------------------------------------
   |  AJAX
   |-------------------------------------------------------------------------
   */

   /**
    * Obtiene las clasificaciones por ajax.
    *
    * @return JSON
    */
   public function ajax_get_clasificaciones()
   {
      $this->load->model('clasificaciones_model');

      echo json_encode($this->clasificaciones_model->ajaxClasificaciones());
   }

  /**
    * Obtiene listado de empresas por ajax
    */
  public function ajax_get_empresas_fac(){
    $this->load->model('facturacion_model');
    $params = $this->facturacion_model->getFacEmpresasAjax();

    echo json_encode($params);
  }

  /**
    * Obtiene listado de los clientes que tienen RFC por ajax.
    */
  public function ajax_get_clientes(){
    $this->load->model('clientes_model');
    $params = $this->clientes_model->getClientesAjax(" AND rfc != ''");

    echo json_encode($params);
  }

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
        $txt = 'La Factura se modifico correctamente.';
        $icono = 'success';
        break;
      case 4:
        $txt = 'La Factura se agrego correctamente.';
        $icono = 'success';
        break;
      case 5:
        $txt = 'La Factura se cancelo correctamente.';
        $icono = 'success';
        break;
      case 6:
        $txt = 'La Serie y Folio se agregaron correctamente.';
        $icono = 'success';
        break;
      case 7:
        $txt = 'La Serie y Folio se modifico correctamente.';
        $icono = 'success';
        break;
      case 8:
        $txt = $msg;
        $icono = 'success';
        break;
      case 9:
        $txt = 'La Factura se pagó correctamente.';
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