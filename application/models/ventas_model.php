<?php
class Ventas_model extends privilegios_model{

	function __construct(){
		parent::__construct();
	}

  /*
   |-------------------------------------------------------------------------
   |  FACTURACION
   |-------------------------------------------------------------------------
   */

	/**
	 * Obtiene el listado de facturas
   *
   * @return
	 */
	public function getVentas($perpage = '40', $sql2='')
  {
		$sql = '';
    //paginacion
    $params = array(
        'result_items_per_page' => $perpage,
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    );
    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql = " AND Date(f.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(f.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(f.fecha) = '".$this->input->get('ffecha2')."'";

    // if($this->input->get('fserie') != '')
    //  $sql .= " AND c.serie = '".$this->input->get('fserie')."'";
    if($this->input->get('ffolio') != '')
      $sql .= " AND f.folio = '".$this->input->get('ffolio')."'";
    if($this->input->get('fstatus') != '')
      $sql .= " AND f.status = '".$this->input->get('fstatus')."'";
    if($this->input->get('fid_cliente') != '')
      $sql .= " AND f.id_cliente = '".$this->input->get('fid_cliente')."'";
    if($this->input->get('did_empresa') != '')
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";

    if($this->input->get('dobserv') != '')
      $sql .= " AND lower(f.Observaciones) LIKE '%".$this->input->get('dobserv')."%'";

    $query = BDUtil::pagination("
        SELECT f.id_factura, Date(f.fecha) AS fecha, f.serie, f.folio, c.nombre_fiscal,
                e.nombre_fiscal as empresa, f.condicion_pago, f.forma_pago, f.status, f.total, f.id_nc,
                f.status_timbrado, f.uuid, f.docs_finalizados, f.observaciones, f.refacturada
        FROM facturacion AS f
        INNER JOIN empresas AS e ON e.id_empresa = f.id_empresa
        INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
        WHERE 1 = 1 AND f.is_factura = 'f' AND f.status != 'b' ".$sql.$sql2."
        ORDER BY (f.fecha, f.folio) DESC
        ", $params, true);
    $res = $this->db->query($query['query']);

    $response = array(
        'fact'           => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['fact'] = $res->result();

    return $response;
	}

	/**
	 * Obtiene la informacion de una factura
	 */
	public function getInfoVenta($id, $info_basic=false)
  {
		$res = $this->db
            ->select("*")
            ->from('facturacion')
            ->where("id_factura = {$id}")
            ->get();

    if($res->num_rows() > 0)
    {
			$response['info'] = $res->row();
      $response['info']->fechaT = str_replace(' ', 'T', substr($response['info']->fecha, 0, 16));
      $response['info']->fecha = substr($response['info']->fecha, 0, 10);

			$res->free_result();

      if($info_basic)
				return $response;

      // Carga la info de la empresa.
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($response['info']->id_empresa);
      $response['info']->empresa = $empresa['info'];

      // Carga la info del cliente.
			$this->load->model('clientes_model');
			$prov = $this->clientes_model->getClienteInfo($response['info']->id_cliente);
			$response['info']->cliente = $prov['info'];

      $res = $this->db
        ->select('fp.id_factura, fp.id_clasificacion, fp.num_row, fp.cantidad, fp.descripcion, fp.precio_unitario,
                  fp.importe, fp.iva, fp.unidad, fp.retencion_iva, fp.porcentaje_iva, fp.porcentaje_retencion, fp.ids_pallets, cl.cuenta_cpi')
        ->from('facturacion_productos as fp')
        ->join('clasificaciones as cl', 'cl.id_clasificacion = fp.id_clasificacion', 'left')
        ->where('fp.id_factura = ' . $id)
        ->get();

      $response['productos'] = $res->result();

			return $response;
		}else
			return false;
	}

	/**
	 * Obtiene el folio de acuerdo a la serie seleccionada
	 */
	public function getFolio($empresa)
  {
		$res = $this->db->select('folio')->
                      from('facturacion')->
                      where("id_empresa = {$empresa}")->
                      where("is_factura = 'f'")->
                      order_by('folio', 'DESC')->
                      limit(1)->get()->row();

    $folio      = (isset($res->folio)? $res->folio: 0)+1;
    $res = new stdClass();
    $res->folio = $folio;
    $msg        = 'ok';

		return array($res, $msg);
	}

	/**
	 * Agrega una nota remison a la bd
	 */
	public function addNotaVenta()
  {
    $this->load->model('clientes_model');

    // Obtiene la forma de pago, si es en parcialidades entonces la forma de
    // pago son las parcialidades "Parcialidad 1 de X".
    $formaPago = ($_POST['dforma_pago'] == 'Pago en parcialidades') ? $this->input->post('dforma_pago_parcialidad') : 'Pago en una sola exhibición';

    $datosFactura = array(
      'id_cliente'          => $this->input->post('did_cliente'),
      'id_empresa'          => $this->input->post('did_empresa'),
      'version'             => $this->input->post('dversion'),
      'serie'               => '',
      'folio'               => $this->input->post('dfolio'),
      'fecha'               => str_replace('T', ' ', $_POST['dfecha']),
      'subtotal'            => floatval($_POST['total_subtotal']) - floatval($_POST['total_iva']),
      'importe_iva'          => $this->input->post('total_iva'),
      'total'               => $this->input->post('total_totfac'),
      'total_letra'         => $this->input->post('dttotal_letra'),
      'no_aprobacion'       => 0,
      'ano_aprobacion'      => '',
      'forma_pago'          => $formaPago,
      'metodo_pago'         => $this->input->post('dmetodo_pago'),
      'no_certificado'      => $this->input->post('dno_certificado'),
      'cadena_original'      => '',
      'sello'                => '',
      'certificado'          => '',
      'condicion_pago'      => $this->input->post('dcondicion_pago'),
      'plazo_credito'       => $_POST['dcondicion_pago'] === 'co' ? 0 : $this->input->post('dplazo_credito'),
      'observaciones'       => $this->input->post('dobservaciones'),
      'status'              => 'p', //$_POST['dcondicion_pago'] === 'co' ? 'pa' : 'p',
      'status_timbrado'     => 'p',
      'sin_costo'           => isset($_POST['dsincosto']) ? 't' : 'f',
      'is_factura'          => 'f'
    );

    $this->db->insert('facturacion', $datosFactura);
    $id_venta = $this->db->insert_id();

    // Obtiene los datos del cliente.
    $cliente = $this->clientes_model->getClienteInfo($this->input->post('did_cliente'), true);
    $dataCliente = array(
      'id_factura'    => $id_venta,
      'nombre'      => $cliente['info']->nombre_fiscal,
      'rfc'         => $cliente['info']->rfc,
      'calle'       => $cliente['info']->calle,
      'no_exterior' => $cliente['info']->no_exterior,
      'no_interior' => $cliente['info']->no_interior,
      'colonia'     => $cliente['info']->colonia,
      'localidad'   => $cliente['info']->localidad,
      'municipio'   => $cliente['info']->municipio,
      'estado'      => $cliente['info']->estado,
      'cp'          => $cliente['info']->cp,
      'pais'        => 'MEXICO',
    );
    $this->db->insert('facturacion_cliente', $dataCliente);

    // Productos
    $productosFactura   = array();
    foreach ($_POST['prod_ddescripcion'] as $key => $descripcion)
    {
      if ($_POST['prod_importe'][$key] != 0)
      {
        $productosFactura[] = array(
          'id_factura'       => $id_venta,
          'id_clasificacion' => $_POST['prod_did_prod'][$key] !== '' ? $_POST['prod_did_prod'][$key] : null,
          'num_row'          => intval($key),
          'cantidad'         => $_POST['prod_dcantidad'][$key],
          'descripcion'      => $descripcion,
          'precio_unitario'  => $_POST['prod_dpreciou'][$key],
          'importe'          => floatval($_POST['prod_importe'][$key]) - floatval($_POST['prod_diva_total'][$key]),
          'iva'              => $_POST['prod_diva_total'][$key],
          'unidad'           => $_POST['prod_dmedida'][$key],
          // 'retencion_iva'    => $_POST['prod_dreten_iva_total'][$key],
          'porcentaje_iva'   => $_POST['prod_diva_porcent'][$key],
          // 'porcentaje_retencion' => $_POST['prod_dreten_iva_porcent'][$key],
          'ids_pallets'       => $_POST['pallets_id'][$key] !== '' ? $_POST['pallets_id'][$key] : null,
        );
      }
    }
    $this->db->insert_batch('facturacion_productos', $productosFactura);

    if (isset($_POST['palletsIds']))
    {
      $pallets = array(); // Ids de los pallets cargados en la factura.
      // Crea el array de los pallets a insertar.
      foreach ($_POST['palletsIds'] as $palletId)
      {
        $pallets[] = array(
          'id_factura' => $id_venta,
          'id_pallet'  => $palletId
        );
      }

      if (count($pallets) > 0)
      {
        $this->db->insert_batch('facturacion_pallets', $pallets);
      }
    }

		return array('passes' => true, 'id_venta' => $id_venta);
	}

	/**
	 * Cancela una nota, la elimina
	 */
	public function cancelaNotaRemison($id_venta){
		$this->db->update('facturacion', array('status' => 'ca'), "id_factura = '{$id_venta}'");
		return array(true, '');
	}

  /**
   * Paga una nota
   */
  public function pagaNotaRemison($id_venta){
    $this->db->update('facturacion', array('status' => 'pa'), "id_factura = '{$id_venta}'");
    return array(true, '');
  }

	public function generaNotaRemisionPdf($id_venta, $path = null)
	{
    // include(APPPATH.'libraries/phpqrcode/qrlib.php');
    $venta = $this->getInfoVenta($id_venta);

    // echo "<pre>";
    //   var_dump($venta);
    // echo "</pre>";exit;

    $this->load->library('mypdf');

    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;

    $pdf->AliasNbPages();
    $pdf->AddPage();

    //////////
    // Logo //
    //////////

    $pdf->SetXY(30, 2);
    $pdf->Image(APPPATH.'images/logo.png');

    //////////////////////////
    // Rfc y Regimen Fiscal //
    //////////////////////////

    // 0, 171, 72 = verde

    $pdf->SetFont('helvetica','B', 18);
    // $pdf->SetFillColor(0, 171, 72);
    $pdf->SetTextColor(255, 255, 255);
    // $pdf->SetXY(0, 0);
    // $pdf->Cell(108, 15, "Factura Electrónica (CFDI)", 0, 0, 'C', 1);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(3, $pdf->GetY());
    $pdf->Cell(108, 14, "RFC: {$venta['info']->empresa->rfc}", 0, 0, 'L', 0);

    $pdf->SetFont('helvetica','B', 13);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(3, $pdf->GetY() + 10);
    $pdf->Cell(216, 8, $venta['info']->empresa->nombre_fiscal, 0, 0, 'L', 0);

    // $pdf->SetFont('helvetica','B', 14);
    // $pdf->SetFillColor(242, 242, 242);
    // $pdf->SetTextColor(0, 171, 72);
    // $pdf->SetXY(0, $pdf->GetY() + 14);
    // $pdf->Cell(108, 8, "Régimen Fiscal:", 0, 0, 'L', 1);

    // $pdf->SetFont('helvetica','', 12);
    // $pdf->SetTextColor(0, 0, 0);
    // $pdf->SetXY(0, $pdf->GetY() + 8);
    // $pdf->MultiCell(108, 6, $venta['info']->empresa->regimen_fiscal, 0, 'C', 0);

    /////////////////////////////////////
    // Folio Fisca, CSD, Lugar y Fecha //
    /////////////////////////////////////

    $pdf->SetFont('helvetica','B', 14);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(109, 0);
    $pdf->Cell(108, 8, "Folio:", 0, 0, 'L', 1);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, 0);
    $pdf->Cell(108, 8, $venta['info']->folio, 0, 0, 'C', 0);

    // $pdf->SetTextColor(0, 171, 72);
    // $pdf->SetXY(109, $pdf->GetY() + 8);
    // $pdf->Cell(108, 8, "No de Serie del Certificado del CSD:", 0, 0, 'R', 1);

    // $pdf->SetTextColor(0, 0, 0);
    // $pdf->SetXY(109, $pdf->GetY() + 8);
    // $pdf->Cell(108, 8, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'C', 0);

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(109, $pdf->GetY() + 9);
    $pdf->Cell(108, 8, "Lugar. fecha y hora de emisión:", 0, 0, 'R', 1);

    $pdf->SetFont('helvetica','', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, $pdf->GetY() + 8);

    $pais   = strtoupper($venta['info']->empresa->pais);
    $estado = strtoupper($venta['info']->empresa->estado);
    $fecha = $venta['info']->fecha;

    $pdf->Cell(108, 8, "{$pais}, {$estado} {$fecha}", 0, 0, 'R', 0);

    //////////////////
    // Rfc Receptor //
    //////////////////

    $pdf->SetFillColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 20);
    $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 13);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 1);
    $pdf->Cell(216, 8, "RFC Receptor: {$venta['info']->cliente->rfc}", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 8);
    $pdf->Cell(216, 8, $venta['info']->cliente->nombre_fiscal, 0, 0, 'L', 1);

    ///////////////
    // Productos //
    ///////////////

    $pdf->SetFillColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 8);
    $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

    $pdf->SetXY(0, $pdf->GetY());
    $aligns = array('C', 'C', 'C', 'C','C');
    $widths = array(30, 35, 91, 30, 30);
    $header = array('Cantidad', 'Unidad de Medida', 'Descripcion', 'Precio Unitario', 'Importe');

    // $conceptos = current($xml->Conceptos);

    // for ($i=0; $i < 3; $i++)
    //   $conceptos[] = $conceptos[$i];

    // echo "<pre>";
    //   var_dump($conceptos, is_array($conceptos));
    // echo "</pre>";exit;

    // if (! is_array($conceptos))
    //   $conceptos = array($conceptos);

    $pdf->limiteY = 250;

    $pdf->setY($pdf->GetY() + 1);
    foreach($venta['productos'] as $key => $item)
    {
      $band_head = false;

      if($pdf->GetY() >= $pdf->limiteY || $key === 0) //salta de pagina si exede el max
      {
        if($key > 0) $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetX(0);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      $pdf->SetFont('Arial', '', 10);
      $pdf->SetTextColor(0,0,0);

      $pdf->SetX(0);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
        $item->cantidad,
        $item->unidad,
        $item->descripcion,
        String::formatoNumero($item->precio_unitario, 3),
        String::formatoNumero(floatval($item->importe) + floatval($item->iva), 3),
      ), false);
    }

    /////////////
    // Totales //
    /////////////

    if($pdf->GetY() + 30 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    $pdf->SetFillColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetXY(0, $pdf->GetY() + 1);
    $pdf->Cell(156, 20, "", 1, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(1, $pdf->GetY() + 1);
    $pdf->Cell(154, 8, "Total con letra:", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetXY(0, $pdf->GetY() + 8);
    $pdf->MultiCell(156, 8, $venta['info']->total_letra, 0, 'C', 0);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetXY(1, $pdf->GetY() + 6);
    $pdf->Cell(78, 5, $venta['info']->forma_pago, 0, 0, 'L', 0);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetXY(78, $pdf->GetY());
    $pdf->Cell(78, 5, "Pago en {$venta['info']->metodo_pago}", 0, 0, 'L', 0);

    // $pdf->SetFont('helvetica','B', 10);
    // $pdf->SetXY(156, $pdf->GetY() - 23);
    // $pdf->Cell(30, 6, "Subtotal", 1, 0, 'C', 1);

    // $pdf->SetXY(186, $pdf->GetY());
    // $pdf->Cell(30, 6, String::formatoNumero($venta['info']->subtotal), 1, 0, 'C', 1);

    $pdf->SetXY(156, $pdf->GetY() - 23);
    $pdf->Cell(30, 6, "TOTAL", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 6,String::formatoNumero($venta['info']->total, 2), 1, 0, 'C', 1);

    ///////////////////
    // Observaciones //
    ///////////////////

    $pdf->SetXY(0, $pdf->GetY() + 25);

    $width = (($pdf->GetStringWidth($venta['info']->observaciones) / 216) * 8) + 9;

    if($pdf->GetY() + $width >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    if ( ! empty($venta['info']->observaciones))
    {
        $pdf->SetXY(0, $pdf->GetY() + 3);
        $pdf->SetFont('helvetica','B', 10);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(216));
        $pdf->Row(array('Observaciones'), true);

        $pdf->SetFont('helvetica','', 9);
        $pdf->SetXY(0, $pdf->GetY());
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(216));
        $pdf->Row(array($venta['info']->observaciones), true, 1);
    }

    if ($path)
      $pdf->Output($path.'Factura.pdf', 'F');
    else
      $pdf->Output('Factura', 'I');
	}



  /*
   |-------------------------------------------------------------------------
   |  REPORTES
   |-------------------------------------------------------------------------
   */

  public function getRVP()
  {
    $sql = '';
    //Filtros para buscar
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql = " AND Date(f.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(f.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(f.fecha) = '".$this->input->get('ffecha2')."'";

    if ($this->input->get('dfamilia') != '')
      $sql .= " AND p.id_familia = " . $this->input->get('dfamilia');

    // var_dump($sql);exit;

    $query = $this->db->query("SELECT fp.id_producto, SUM(fp.cantidad) AS total_cantidad, SUM(fp.importe) AS total_importe, p.codigo, p.nombre as producto
                                FROM facturas_productos AS fp
                                INNER JOIN facturas AS f ON f.id_factura = fp.id_factura
                                INNER JOIN productos AS p ON p.id_producto = fp.id_producto
                                WHERE f.status != 'ca' $sql
                                GROUP BY fp.id_producto");

    return $query->result();

  }

   public function rvc_pdf()
   {
      $_GET['ffecha1'] = date("Y-m").'-01';
      $_GET['ffecha2'] = date("Y-m-d");

      $data = $this->getFacturas('10000');

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->show_head = true;
      $pdf->titulo2 = 'Reporte Ventas Cliente';


      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $pdf->titulo3 = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 = "Del ".$_GET['ffecha1'];
      elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 = "Del ".$_GET['ffecha2'];

      $pdf->AliasNbPages();
      // $links = array('', '', '', '');
      $pdf->SetY(30);
      $aligns = array('C', 'C', 'C', 'C','C', 'C', 'C', 'C');
      $widths = array(20, 25, 13, 51, 30, 25, 18, 22);
      $header = array('Fecha', 'Serie', 'Folio', 'Cliente', 'Empresa', 'Forma de pago', 'Estado', 'Total');
      $total = 0;

      foreach($data['fact'] as $key => $item)
      {
        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(255,255,255);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('Arial','',8);
        $pdf->SetTextColor(0,0,0);

        $estado = ($item->status === 'p') ? 'Pendiente' : (($item->status === 'pa') ? 'Pagada' : 'Cancelada');
        $condicion_pago = ($item->condicion_pago === 'co') ? 'Contado' : 'Credito';
        $datos = array($item->fecha, $item->serie, $item->folio, $item->nombre_fiscal, $item->empresa, $condicion_pago, $estado, String::formatoNumero($item->total));
        $total += floatval($item->total);

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }

      $pdf->SetX(6);
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(255,255,255);
      $pdf->Row(array('', '', '', '', '', '', 'Total:', String::formatoNumero($total)), true);

      $pdf->Output('Reporte_Ventas_Cliente.pdf', 'I');
  }

  public function rvp_pdf()
  {
      $data = $this->getRVP();

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->show_head = true;
      $pdf->titulo2 = 'Reporte Ventas Productos';

      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $pdf->titulo3 = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 = "Del ".$_GET['ffecha1'];
      elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 = "Del ".$_GET['ffecha2'];

      $pdf->AliasNbPages();
      // $links = array('', '', '', '');
      $pdf->SetY(30);
      $aligns = array('C', 'C', 'C', 'C','C', 'C', 'C', 'C', 'C');
      $widths = array(20, 120, 20, 44);
      $header = array('Codigo', 'Producto', 'Cantidad', 'Importe');
      $total = 0;

      foreach($data as $key => $item)
      {
        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(255,255,255);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('Arial','',8);
        $pdf->SetTextColor(0,0,0);

        $datos = array($item->codigo, $item->producto, $item->total_cantidad, String::formatoNumero($item->total_importe));

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }

      $pdf->Output('Reporte_Ventas_Productos.pdf', 'I');
  }


}