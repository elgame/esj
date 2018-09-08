<?php
class cuentas_cobrar_pago_model extends cuentas_cobrar_model{

  function __construct(){
    parent::__construct();
    // $this->load->model('bitacora_model');
  }


  public function getComPagoData()
  {
    //paginacion
    $params = array(
      'result_items_per_page' => '40',
      'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
      );
    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    $response = array();
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m").'-01';
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getDefaultEmpresa();
    if( ! $this->input->get('did_empresa') != '')
    {
      $_GET['did_empresa'] = $empresa->id_empresa;
      $_GET['dempresa'] = $empresa->nombre_fiscal;
    }
    if ($this->input->get('did_empresa') != '')
      $sql .= " AND e.id_empresa = '".$_GET['did_empresa']."'";

    if($this->input->get('fid_cliente') != '')
      $sql .= " AND c.id_cliente = '".$this->input->get('fid_cliente')."'";

    if($this->input->get('fstatus') != '') {
      $status = '';
      if ($this->input->get('fstatus') == 'ca')
        $status = 'cancelada';
      elseif ($this->input->get('fstatus') == 'p' || $this->input->get('fstatus') == 'pa')
        $status = 'facturada';
      $sql .= " AND f.status = '".$status."'";
    }


    $query = BDUtil::pagination(
      "SELECT
        f.id, f.id_movimiento, f.fecha, f.serie, f.folio, f.uuid, f.cfdi_ext, f.sello, f.cadena_original,
        f.status, f.version, f.id_empresa, f.no_impresiones, bm.monto, c.nombre_fiscal, e.nombre_fiscal AS empresa
      FROM banco_movimientos_com_pagos AS f
        INNER JOIN banco_movimientos AS bm ON bm.id_movimiento = f.id_movimiento
        INNER JOIN clientes AS c ON c.id_cliente = bm.id_cliente
        INNER JOIN empresas AS e ON e.id_empresa = f.id_empresa
      WHERE 1 = 1
      {$sql}
      ORDER BY Date(f.fecha) DESC
      ", $params, true);
    $res = $this->db->query($query['query']);

    $response = array(
      'pagos'         => array(),
      'total_rows'     => $query['total_rows'],
      'items_per_page' => $params['result_items_per_page'],
      'result_page'    => $params['result_page'],
      );

    if($res->num_rows() > 0)
    {
      $response['pagos'] = $res->result();
      $res->free_result();
    }

    // echo "<pre>";
    //   var_dump($response);
    // echo "</pre>";exit;

    return $response;
  }

  public function getInfoComPago($id_compago=false, $id_movimiento=false)
  {
    $sql = $id_compago? "WHERE bcp.id = {$id_compago}" : "WHERE bcp.id_movimiento = {$id_movimiento} AND bcp.status = 'facturada'";
    $factura = $this->db->query(
      "SELECT bcp.id, bcp.id_movimiento, bcp.fecha, bcp.serie, bcp.folio, bcp.xml, bcp.uuid, bcp.cfdi_ext, bcp.sello, bcp.cadena_original,
        bcp.status, bcp.version, bcp.id_empresa, bcp.no_impresiones, e.logo
      FROM banco_movimientos_com_pagos AS bcp
        INNER JOIN empresas AS e ON e.id_empresa = bcp.id_empresa
      {$sql}")->row();

    if (isset($factura->cfdi_ext))
      $factura->cfdi_ext = json_decode($factura->cfdi_ext);

    return $factura;
  }

  public function addComPago($id_movimiento, $id_cuenta_cliente)
  {
    $query = $this->db->query(
          "SELECT *, (select Count(id_movimiento) from banco_movimientos_com_pagos where id_movimiento = {$id_movimiento}) AS num_row
           FROM banco_movimientos_com_pagos
           WHERE id_movimiento = {$id_movimiento} AND status = 'facturada'"
        );

    if ($query->num_rows() == 0) {
      $this->load->library('cfdi');

      $queryMov = $this->db->query(
          "SELECT bm.id_movimiento, bm.fecha, bm.metodo_pago AS forma_pago, bm.concepto,
            bm.monto AS pago, bb.rfc, bc.numero AS num_cuenta, (caf.total - Coalesce(fao.total, 0)) AS pago_factura, v.version, v.serie, v.folio,
            v.id_factura, v.uuid, v.cfdi_ext, Coalesce(par.parcialidades, 1) AS parcialidades, v.id_cliente, v.id_empresa
           FROM banco_movimientos bm
            INNER JOIN banco_cuentas bc ON bc.id_cuenta = bm.id_cuenta
            INNER JOIN banco_bancos bb ON bb.id_banco = bm.id_banco
            INNER JOIN banco_movimientos_facturas bmf ON bm.id_movimiento = bmf.id_movimiento
            INNER JOIN facturacion_abonos caf ON caf.id_abono = bmf.id_abono_factura
            INNER JOIN facturacion v ON v.id_factura = caf.id_factura
            LEFT JOIN (
              SELECT id_factura, Count(*) AS parcialidades FROM facturacion_abonos GROUP BY id_factura
            ) par ON v.id_factura = par.id_factura
            LEFT JOIN (
              SELECT id_factura, id_abono, (CASE WHEN tipo = 's' THEN total ELSE -1*total END) AS total FROM facturacion_abonos_otros
            ) fao ON v.id_factura = fao.id_factura AND caf.id_abono = fao.id_abono
           WHERE bm.id_movimiento = {$id_movimiento} AND v.version::float > 3.2 AND v.is_factura = 't'"
        );

      if ($id_cuenta_cliente > 0) {
        $queryCliente = $this->db->query(
            "SELECT cc.id_cuenta, cc.id_cliente, cc.alias, cc.cuenta, bb.rfc
             FROM clientes_cuentas cc
              INNER JOIN banco_bancos bb ON bb.id_banco = cc.id_banco
             WHERE cc.id_cuenta = {$id_cuenta_cliente}"
          );
      }

      if ($queryMov->num_rows() > 0) {
        $queryMov            = $queryMov->result();
        $queryCliente        = isset($queryCliente)? $queryCliente->row() : null;
        $folio = $this->getFolioSerie('P', $queryMov[0]->id_empresa);
        if ($folio === false) {
          return array("passes" => false, "codigo" => "14");
        }

        $queryMov[0]->num_cuenta = str_replace('-', '', $queryMov[0]->num_cuenta);

        // xml 3.3
        $datosApi = $this->cfdi->obtenDatosCfdi33ComP($queryMov, $queryCliente, $folio);
        // echo "<pre>";
        //   var_dump($datosApi);
        // echo "</pre>";exit;

        log_message('error', "ComPago");
        log_message('error', json_encode($datosApi));
        // Timbrado de la factura.
        $result = $this->timbrar($datosApi, $id_movimiento);
        log_message('error', json_encode($result));

        if ($result['passes'])
        {

          // // $xmlName = explode('/', $archivos['pathXML']);

          // // copy($archivos['pathXML'], $pathDocs.end($xmlName));

          // //Si es otra moneda actualiza al tipo de cambio
          // if($datosFactura['moneda'] !== 'MXN')
          // {
          //   $datosFactura1 = array();
          //   $datosFactura1['total']         = number_format($datosFactura['total']*$datosFactura['tipo_cambio'], 2, '.', '');
          //   $datosFactura1['subtotal']      = number_format($datosFactura['subtotal']*$datosFactura['tipo_cambio'], 2, '.', '');
          //   $datosFactura1['importe_iva']   = number_format($datosFactura['importe_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
          //   $datosFactura1['retencion_iva'] = number_format($datosFactura['retencion_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
          //   $this->db->update('facturacion', $datosFactura1, array('id_factura' => $idFactura));

          //   foreach ($productosFactura as $key => $value)
          //   {
          //     $value['precio_unitario'] = number_format($value['precio_unitario']*$datosFactura['tipo_cambio'], 2, '.', '');
          //     $value['importe']         = number_format($value['importe']*$datosFactura['tipo_cambio'], 2, '.', '');
          //     $value['iva']             = number_format($value['iva']*$datosFactura['tipo_cambio'], 2, '.', '');
          //     $value['retencion_iva']   = number_format($value['retencion_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
          //     $this->db->update('facturacion_productos', $value, "id_factura = {$value['id_factura']} AND num_row = {$value['num_row']}");
          //   }
          // }

          $dataTimbrado = array(
            'id_movimiento'   => $id_movimiento,
            'id_empresa'      => $queryMov[0]->id_empresa,
            'fecha'           => $datosApi['fecha'],
            'serie'           => $datosApi['serie'],
            'folio'           => $datosApi['folio'],
            'xml'             => $result['timbrado']->data->xml,
            'uuid'            => $result['timbrado']->data->uuid,
            'cadena_original' => $result['timbrado']->data->cadenaOriginal,
            'sello'           => $result['timbrado']->data->sello,
            'version'         => $queryMov[0]->version,
            'cfdi_ext'        => json_encode($datosApi),
          );
          $this->db->insert('banco_movimientos_com_pagos', $dataTimbrado);
          $id_compago = $this->db->insert_id();

          foreach ($queryMov as $key => $pago) {
            $this->db->insert('facturacion_com_pagos', [
              'id_movimiento' => $id_movimiento,
              'id_factura'    => $pago->id_factura,
            ]);
          }

          $this->db->query("SELECT refreshallmaterializedviews();");

          $this->load->model('documentos_model');
          $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($datosApi['receptor']['nombreFiscal'], $dataTimbrado['serie'], $dataTimbrado['folio']);

          $this->generaFacturaPdf33($id_compago, $pathDocs);

        }

        // $datosFactura, $cadenaOriginal, $sello, $productosFactura,
        // echo "<pre>";
        //   var_dump($result);
        // echo "</pre>";exit;

        return $result;
      }
      return array("passes" => false, "codigo" => "13");
    }
    return array("passes" => false, "codigo" => "12");
  }

  private function timbrar($dataXml, $id_movimiento)
  {
    $this->load->library('facturartebarato_api');

    // $this->facturartebarato_api->setPathXML($pathXML);

    // Realiza el timbrado usando la libreria.
    $timbrado = $this->facturartebarato_api->timbrar($dataXml);

    $result = array(
      'id_factura' => $id_movimiento,
      'codigo'     => $timbrado->codigo,
      'timbrado'   => $timbrado,
    );

    // Si no hubo errores al momento de realizar el timbrado.
    if ($timbrado->status)
    {
      $result['passes'] = true;
      $result['msg'] = $timbrado->mensaje;
    }
    else
    {
      // Entra si hubo un algun tipo de error de conexion a internet.
      if ($timbrado->codigo === 'ERR_INTERNET_DISCONNECTED')
        $result['msg'] = 'Error Timbrado: Internet Desconectado. Verifique su conexión para realizar el timbrado.';
      elseif ($timbrado->codigo === '500')
        $result['msg'] = 'Error en el servidor del timbrado. Pongase en contacto con el equipo de desarrollo del sistema.';
      else
        $result['msg'] = $timbrado->mensaje;

      $result['passes'] = false;
    }

    // echo "<pre>";
    //   var_dump($timbrado);
    // echo "</pre>";exit;

    return $result;
  }

  public function cancelaFactura($id_compago)
  {
    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('documentos_model');

    // Obtenemos la info de la factura a cancelar.
    $factura = $this->getInfoComPago($id_compago);

    if ($factura->uuid != '')
    {
      $status_uuid = '708';
      // Carga los datos fiscales de la empresa dentro de la lib CFDI.
      $this->cfdi->cargaDatosFiscales($factura->id_empresa);

      // Parametros que necesita el webservice para la cancelacion.
      $params = array(
        'rfc'   => $factura->cfdi_ext->emisor->rfc,
        'uuids' => $factura->uuid,
        'cer'   => $this->cfdi->obtenCer(),
        'key'   => $this->cfdi->obtenKey(),
      );

      // Llama el metodo cancelar para que realiza la peticion al webservice.
      $result = $this->facturartebarato_api->cancelar($params);

      if ($result->data->status_uuid === '201' || $result->data->status_uuid === '202')
      {
        $status_uuid = $result->data->status_uuid;
        $this->db->update('banco_movimientos_com_pagos',
          array('status' => 'cancelada'), "id = {$id_compago}");

        // Regenera el PDF de la factura.
        $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($factura->cfdi_ext->receptor->nombreFiscal, $factura->serie, $factura->folio);
        $this->generaFacturaPdf33($id_compago, $pathDocs);

        $this->db->query("SELECT refreshallmaterializedviews();");

        // $this->enviarEmail($idFactura);

      }
    }else{
      $status_uuid = '201';
    }

    return array('msg' => $status_uuid);
  }

  public function getFolioSerie($serie, $empresa, $sqlX = null)
  {
    $res = $this->db->select('folio')
      ->from('banco_movimientos_com_pagos')
      ->where("serie = '".$serie."' AND id_empresa = ".$empresa."") // AND status != 'b'
      ->order_by('folio', 'DESC')
      ->limit(1)->get()->row();

    $res_serie = $this->db->select('id_serie_folio')
      ->from('facturacion_series_folios')
      ->where("serie = '".$serie."' AND id_empresa = ".$empresa."") // AND status != 'b'
      ->order_by('id_serie_folio', 'DESC')
      ->limit(1)->get()->row();

    if (!isset($res->folio) && !isset($res_serie->id_serie_folio)) {
      return false;
    }

    $folio = (isset($res->folio)? $res->folio: 0)+1;

    return $folio;
  }

  public function generaFacturaPdf33($id_compago, $path = null)
  {
    $factura = $this->getInfoComPago($id_compago);

    $this->load->library('cfdi');
    include_once(APPPATH.'libraries/phpqrcode/qrlib.php');

    $this->load->model('catalogos33_model');
    $metodosPago       = new MetodosPago();
    $formaPago         = new FormaPago();
    $usoCfdi           = new UsoCfdi();
    $tipoDeComprobante = new TipoDeComprobante();
    $regimenFiscal     = $this->catalogos33_model->regimenFiscales($factura->cfdi_ext->emisor->regimenFiscal);

    $xml = simplexml_load_string(str_replace(array('cfdi:', 'tfd:'), '', $factura->xml));
    if($xml === false)
      return false;
    // echo "<pre>";
    //   var_dump($factura, $xml);
    // echo "</pre>";exit;

    $this->load->library('mypdf');

    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;

    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetFont('Arial','B', 70);
    $pdf->SetTextColor(160,160,160);
    $pdf->RotatedText(65, 130, ($factura->no_impresiones==0? 'ORIGINAL': 'COPIA #'.$factura->no_impresiones), 45);

    $pdf->SetXY(0, 0);
    /////////////////////////////////////
    // Folio Fisca, CSD, Lugar y Fecha //
    /////////////////////////////////////

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 2);
    $pdf->Cell(108, 4, "Folio Fiscal:", 0, 0, 'R', 1);

    $titulo_comprobante = '                 Complemento de Pago';

    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Cell(50, 4, $titulo_comprobante.':  '.($factura->serie.$factura->folio) , 0, 0, 'L', 1);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 6);
    $pdf->Cell(108, 4, $xml->Complemento->TimbreFiscalDigital[0]['UUID'], 0, 0, 'C', 0);

    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(48, 4, "Fecha y hora de impresión:", 0, 0, 'L', 1);
    $pdf->SetXY(48, $pdf->GetY());
    $pdf->Cell(60, 4, "No de Serie del Certificado del CSD:", 0, 0, 'R', 1);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica','', 9);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(48, 4, String::fechaATexto(date("Y-m-d")).' '.date("H:i:s"), 0, 0, 'L', 0);
    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(48, $pdf->GetY());
    $pdf->Cell(60, 4, $factura->cfdi_ext->noCertificado, 0, 0, 'R', 0);

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, "Lugar. fecha y hora de emisión:", 0, 0, 'R', 1);

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 4);

    $municipio   = strtoupper($factura->cfdi_ext->emisor->municipio);
    $estado = strtoupper($factura->cfdi_ext->emisor->estado);
    $fecha = explode('T', $factura->cfdi_ext->fecha);
    $fecha = String::fechaATexto($fecha[0]);

    $pdf->Cell(108, 4, "{$municipio}, {$estado} ({$factura->cfdi_ext->emisor->cp}) | {$fecha}", 0, 0, 'R', 0);


    // $pdf->SetXY(30, 2);

    //////////////////////////
    // Rfc y Regimen Fiscal //
    //////////////////////////

    // 0, 171, 72 = verde

    $pdf->SetFont('helvetica','B', 9);
    // $pdf->SetFillColor(0, 171, 72);
    $pdf->SetTextColor(255, 255, 255);


    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, "Emisor:", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 4);

    $pdf->SetX(0);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(19, 93));
    $pdf->Row(array('RFC:', $factura->cfdi_ext->emisor->rfc), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 196));
    $pdf->SetX(0);
    $pdf->Row(array('NOMBRE:', $factura->cfdi_ext->emisor->nombreFiscal), false, false, null, 2, 1);
    $pdf->SetX(0);
    $pdf->Row(array('DOMICILIO:', $factura->cfdi_ext->emisor->calle.' No. '.$factura->cfdi_ext->emisor->noExterior.
          ((isset($factura->cfdi_ext->emisor->noInterior)) ? ' Int. '.$factura->cfdi_ext->emisor->noInterior : '') ), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 83, 19, 83));
    $pdf->SetX(0);
    $pdf->Row(array('COLONIA:', $factura->cfdi_ext->emisor->colonia, 'LOCALIDAD:', $factura->cfdi_ext->emisor->localidad), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 46, 19, 40, 11, 46, 11, 30));
    $pdf->SetX(0);
    $pdf->Row(array('MUNICIPIO:', $factura->cfdi_ext->emisor->municipio, 'ESTADO:', $factura->cfdi_ext->emisor->estado, 'PAIS:', $factura->cfdi_ext->emisor->pais, 'CP:', $factura->cfdi_ext->emisor->cp), false, false, null, 2, 1);

    $end_y = $pdf->GetY();

    //------------ IMAGEN CANDELADO --------------------

    if($factura->status === 'cancelada'){
      $pdf->Image(APPPATH.'/images/cancelado.png', 20, 40, 190, 190, "PNG");
    }

    //////////
    // Logo //
    //////////
    $logo = (file_exists($factura->logo)) ? $factura->logo : '' ;
    if($logo != '')
      $pdf->Image($logo, 115, 2, 0, 21);
    $pdf->SetXY(0, 25);

    $pdf->SetFont('helvetica','b', 9);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, "Régimen Fiscal:", 0, 0, 'R', 1);

    $regimen_fiscal = "{$regimenFiscal->c_RegimenFiscal} - {$regimenFiscal->nombre}";
    $uso_cfdi = $usoCfdi->search($factura->cfdi_ext->usoCfdi);

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $pdf->MultiCell(108, 4, $regimen_fiscal, 0, 'C', 0);
    $pdf->SetXY(119, $pdf->GetY() + 4);
    $pdf->MultiCell(98, 4, "USO CFDI: {$uso_cfdi['key']} - {$uso_cfdi['value']}", 0, 'R', 0);

    //////////////////
    // Datos Receptor //
    //////////////////
    $pdf->setY($end_y);
    $domicilioReceptor = '';
    $domicilioReceptor .= (isset($factura->cfdi_ext->receptor->calle) ? $factura->cfdi_ext->receptor->calle : '');
    $domicilioReceptor .= (isset($factura->cfdi_ext->receptor->noExterior) ? ' #'.$factura->cfdi_ext->receptor->noExterior : '');
    $domicilioReceptor .= (isset($factura->cfdi_ext->receptor->noInterior)) ? ' Int. '.$factura->cfdi_ext->receptor->noInterior : '';
    $domicilioReceptor .= (isset($factura->cfdi_ext->receptor->colonia) ? ', '.$factura->cfdi_ext->receptor->colonia : '');
    $domicilioReceptor .= (isset($factura->cfdi_ext->receptor->localidad) ? ', '.$factura->cfdi_ext->receptor->localidad : '');
    $domicilioReceptor .= (isset($factura->cfdi_ext->receptor->municipio)) ? ', '.$factura->cfdi_ext->receptor->municipio : '';
    $domicilioReceptor .= (isset($factura->cfdi_ext->receptor->estado) ? ', '.$factura->cfdi_ext->receptor->estado : '');

    $pdf->SetFillColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 1);
    $pdf->Cell(216, 4, "Receptor:", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 4);

    $pdf->SetX(0);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(19, 93));
    $pdf->Row(array('RFC:', $factura->cfdi_ext->receptor->rfc), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 196));
    $pdf->SetX(0);
    $pdf->Row(array('NOMBRE:', $factura->cfdi_ext->receptor->nombreFiscal), false, false, null, 2, 1);
    $pdf->SetX(0);
    $pdf->Row(array('DOMICILIO:', (isset($factura->cfdi_ext->receptor->calle) ? $factura->cfdi_ext->receptor->calle : '').
              ' No. '.(isset($factura->cfdi_ext->receptor->noExterior) ? $factura->cfdi_ext->receptor->noExterior : '').
              ((isset($factura->cfdi_ext->receptor->noInterior)) ? ' Int. '.$factura->cfdi_ext->receptor->noInterior : '') ), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 83, 19, 83));
    $pdf->SetX(0);
    $pdf->Row(array('COLONIA:', (isset($factura->cfdi_ext->receptor->colonia) ? $factura->cfdi_ext->receptor->colonia : ''),
              'LOCALIDAD:', (isset($factura->cfdi_ext->receptor->localidad) ? $factura->cfdi_ext->receptor->localidad : '')), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 65, 11, 65, 11, 40));
    $pdf->SetX(0);
    $pdf->Row(array('ESTADO:', (isset($factura->cfdi_ext->receptor->estado) ? $factura->cfdi_ext->receptor->estado : ''),
            'PAIS:', (isset($factura->cfdi_ext->receptor->pais) ? $factura->cfdi_ext->receptor->pais : ''),
            'CP:', (isset($factura->cfdi_ext->receptor->cp) ? $factura->cfdi_ext->receptor->cp : '') ), false, false, null, 2, 1);

    ///////////////
    // Productos //
    ///////////////

    $pdf->SetFillColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 5);
    $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

    $pdf->SetXY(0, $pdf->GetY());
    $aligns = array('C', 'C', 'C', 'C', 'C', 'C');
    $aligns2 = array('C', 'C', 'L', 'C', 'R', 'R');
    $widths = array(30, 35, 16, 75, 30, 30);
    $header = array('Cantidad', 'Unidad de Medida', 'C. Unidad', 'Descripcion', 'Precio Unitario', 'Importe');

    $pdf->limiteY = 250;

    $pdf->setY($pdf->GetY() + 1);
    $hay_prod_certificados = false;
    foreach($factura->cfdi_ext->productos as $key => $item)
    {
      $band_head = false;

      if($pdf->GetY() >= $pdf->limiteY || $key === 0) //salta de pagina si exede el max
      {
        if($key > 0) $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetX(0);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true, true, null, 2, 1);
      }

      $pdf->SetFont('Arial', '', 8);
      $pdf->SetTextColor(0,0,0);

      $pdf->SetX(0);
      $pdf->SetAligns($aligns2);
      $pdf->SetWidths($widths);

      $pdf->Row(array(
        String::formatoNumero($item->cantidad, 2, ''),
        $item->unidad,
        $item->claveUnidad,
        $this->cfdi->replaceSpecialChars($item->claveProdServ.' - '.$item->concepto, true),
        String::formatoNumero( $item->valorUnitario, 2, '$', false),
        String::formatoNumero( $item->importe, 2, '$', false),
      ), false, true, null, 2, 1);
    }

    /////////////
    // Totales //
    /////////////

    if($pdf->GetY() + 30 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    $h = 15;
    $h += 6;

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetXY(0, $pdf->GetY() + 1);
    $pdf->Cell(156, $h, "", 1, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(1, $pdf->GetY() + 1);
    $pdf->Cell(154, 4, "Total con letra:", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->MultiCell(156, 6, String::num2letras($factura->cfdi_ext->total), 0, 'C', 0);

    $pdf->Line(1, $pdf->GetY(), 200, $pdf->GetY());

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(1, $pdf->GetY()+1);
    $frmPago = $formaPago->search($factura->cfdi_ext->formaDePago);
    $pdf->Cell(91, 4, "Forma de Pago: {$frmPago['key']} - {$frmPago['value']}", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(91, $pdf->GetY());
    $text_condicionpago = '';
    $pdf->Cell(65, 4, $text_condicionpago, 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(1, $pdf->GetY()+4.5);
    $metPago = $metodosPago->search($factura->cfdi_ext->metodoDePago);
    $pdf->Cell(148, 4, "Metodo de Pago: {$metPago['key']} - {$metPago['value']}", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetXY(111, $pdf->GetY());
    $pdf->Cell(39, 4, "Tipo de Cambio: ".String::formatoNumero($factura->cfdi_ext->tipoCambio, 4), 0, 0, 'L', 1);


    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetXY(156, $pdf->GetY() - 16);
    $pdf->Cell(30, 5, "Subtotal", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5, String::formatoNumero($factura->cfdi_ext->totalImporte, 2, '$', false), 1, 0, 'R', 1);

    // Pinta traslados, retenciones

    $pdf->SetXY(156, $pdf->GetY() + 5);
    $pdf->Cell(30, 5, "IVA", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5,String::formatoNumero($factura->cfdi_ext->trasladosImporte->iva, 2, '$', false), 1, 0, 'R', 1);

    $pdf->SetXY(156, $pdf->GetY() + 5);
    $pdf->Cell(30, 5, "TOTAL", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5,String::formatoNumero($factura->cfdi_ext->total, 2, '$', false), 1, 0, 'R', 1);

    ///////////////////
    // Complemento de Pagos //
    ///////////////////

    $pdf->SetXY(0, $pdf->GetY() + 10);

    if (isset($factura->cfdi_ext->pagos)) {
      // $tipoRelacion = new TipoRelacion();
      // $tipo_rel = $tipoRelacion->search($factura->cfdi_ext->cfdiRelacionados->tipoRelacion);
      $pdf->SetFillColor(0, 171, 72);
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Cell(216, 4, "Complemento de Recepción de Pagos:", 0, 0, 'L', 1);

      $pdf->SetFont('helvetica','B', 8);

      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(108, 108));
      $pdf->SetXY(0, $pdf->GetY()+4);
      $pdf->Row(array("Pago", "Fecha: {$factura->cfdi_ext->pagos[0]->fechaPago}"), false, true, null, 2, 1);

      $pdf->SetFont('helvetica','', 8);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(216));

      $pdf->SetXY(0, $pdf->GetY());
      $cuentas = "RFC Emisor Cta Ordenante: {$factura->cfdi_ext->pagos[0]->rfcEmisorCtaOrd} | Cta Ordenante: {$factura->cfdi_ext->pagos[0]->cuentaOrd}";
      $pdf->Row(array($cuentas), false, false, null, 2, 1);

      $pdf->SetXY(0, $pdf->GetY());
      $cuentas = "RFC Emisor Cta Beneficiario: {$factura->cfdi_ext->pagos[0]->rfcEmisorCtaBen} | Cta Beneficiario: {$factura->cfdi_ext->pagos[0]->cuentaBen}";
      $pdf->Row(array($cuentas), false, false, null, 2, 1);

      $pdf->SetXY(0, $pdf->GetY());
      $frmPago = $formaPago->search($factura->cfdi_ext->pagos[0]->formaDePago);
      $cuentas = "Forma de Pago: {$frmPago['key']} - {$frmPago['value']} | Num operación: {$factura->cfdi_ext->pagos[0]->numOperacion}";
      $pdf->Row(array($cuentas), false, false, null, 2, 1);

      $pdf->SetXY(0, $pdf->GetY());
      $cuentas = "Moneda: {$factura->cfdi_ext->pagos[0]->moneda} | Tipo cambio: {$factura->cfdi_ext->pagos[0]->tipoCambio} | Monto: {$factura->cfdi_ext->pagos[0]->monto}";
      $pdf->Row(array($cuentas), false, false, null, 2, 1);

      $pdf->SetFont('helvetica','B', 8);

      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row(array("Documentos"), false, true, null, 2, 1);

      $pdf->SetFont('helvetica','', 8);
      foreach ($factura->cfdi_ext->pagos[0]->doctoRelacionado as $key => $value) {
        $pdf->SetXY(0, $pdf->GetY());
        $cuentas = "Id documento: {$value->idDocumento}".($value->serie!=''? " | Serie: {$value->serie}": '')." | Folio: {$value->folio}";
        $pdf->Row(array($cuentas), false, false, null, 2, 1);

        $pdf->SetXY(0, $pdf->GetY());
        $metPago = $metodosPago->search($value->metodoDePago);
        $cuentas = "Moneda: {$value->moneda} | Metodo de Pago: {$metPago['key']} - {$metPago['value']} | No parcialidad: {$value->numParcialidad}";
        $pdf->Row(array($cuentas), false, false, null, 2, 1);

        $pdf->SetXY(0, $pdf->GetY());
        $cuentas = "Saldo anterior: {$value->saldoAnterior} | Pago: {$value->importePagado} | Saldo insoluto: {$value->saldoInsoluto}";
        $pdf->Row(array($cuentas), false, false, null, 2, 1);

        $pdf->Line(0, $pdf->GetY(), 216, $pdf->GetY());
      }
    }

    ////////////////////////
    // CFDI Relacionados //
    ///////////////////////
    if (isset($factura->cfdi_ext->cfdiRelacionados)) {
      $tipoRelacion = new TipoRelacion();
      $tipo_rel = $tipoRelacion->search($factura->cfdi_ext->cfdiRelacionados->tipoRelacion);
      $pdf->SetFillColor(0, 171, 72);
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Cell(216, 4, "CFDI Relacionados:", 0, 0, 'L', 1);

      $pdf->SetFont('helvetica','', 8);

      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(216));

      $pdf->SetXY(0, $pdf->GetY()+4);
      $pdf->Row(array("Tipo de Relacion: {$tipo_rel['key']} - {$tipo_rel['value']}" ), false, true, null, 2, 1);

      foreach ($factura->cfdi_ext->cfdiRelacionados->cfdiRelacionado as $key => $value) {
        $pdf->SetXY(0, $pdf->GetY());
        $pdf->Row(array("UUID: {$value->uuid}"), false, true, null, 2, 1);
      }
    }


    ////////////////////
    // Timbrado Datos //
    ////////////////////

    if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY(3, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(196));
    $pdf->Row(array('Sello Digital del CFDI:'), false, 0);

    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(3, $pdf->GetY() - 3);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(211));
    $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['SelloCFD']), false, false);

    if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY(3, $pdf->GetY() - 2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(196));
    $pdf->Row(array('Sello Digital del SAT:'), false, 0);

    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(3, $pdf->GetY() - 3);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(211));
    $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['SelloSAT']), false, 0);

    /////////////
    // QR CODE //
    /////////////

    // Genera Qr.
    $cad_sello = substr($factura->sello, -8);
    $cadenaOriginalSAT = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?&id={$factura->uuid}&re={$factura->cfdi_ext->emisor->rfc}&rr={$factura->cfdi_ext->receptor->rfc}&tt={$factura->cfdi_ext->total}&fe={$cad_sello}";

    // echo "<pre>";
    //   var_dump($cadenaOriginalSAT, $total, $diff);
    // echo "</pre>";exit;

    QRcode::png($cadenaOriginalSAT, APPPATH.'media/qrtemp.png', 'H', 3);

    if($pdf->GetY() + 50 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Image(APPPATH.'media/qrtemp.png', null, null, 40);

    // Elimina el QR generado temporalmente.
    unlink(APPPATH.'media/qrtemp.png');

    ////////////////////
    // Timbrado Datos //
    ////////////////////

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY(45, $pdf->GetY() - 39);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(160));

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(45, $pdf->GetY() + 1);
    $pdf->Cell(80, 6, "RFC Prov Certif:", 0, 0, 'R', 1);

    $pdf->SetXY(125, $pdf->GetY());
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['RfcProvCertif'], 0, 0, 'C', 0);

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(45, $pdf->GetY() + 10);
    $pdf->Cell(80, 6, "No de Serie del Certificado del SAT:", 0, 0, 'R', 1);

    $pdf->SetXY(125, $pdf->GetY());
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['NoCertificadoSAT'], 0, 0, 'C', 0);

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(45, $pdf->GetY() + 10);
    $pdf->Cell(80, 6, "Fecha y hora de certificación:", 0, 0, 'R', 1);

    $pdf->SetXY(125, $pdf->GetY());
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado'], 0, 0, 'C', 0);

    $pdf->SetXY(0, $pdf->GetY()+13);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(220, 6, 'ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI.', 0, 0, 'C', 0);
    $pdf->SetXY(10, $pdf->GetY()+3);


    $folio = $this->cfdi->acomodarFolio($factura->folio);
    $file = $factura->cfdi_ext->emisor->rfc.'-'.$factura->serie.$folio;
    if ($path) {
      file_put_contents($path."{$file}.xml", $factura->xml);
      $pdf->Output($path."{$file}.pdf", 'F');
    } else {
      // Actualiza el # de impresion
      $this->db->update('banco_movimientos_com_pagos', ['no_impresiones' => $factura->no_impresiones+1], "id = ".$factura->id);

      $pdf->Output($file.'.pdf', 'I');
    }
  }

}