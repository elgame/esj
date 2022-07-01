<?php
class facturacion2_model extends privilegios_model{

	function __construct(){
		parent::__construct();
	}

  /*
   |-------------------------------------------------------------------------
   |  FACTURACION
   |-------------------------------------------------------------------------
   */

    /**
     * Reportes Productos Facturados.
     *
     * @return void
     */
    public function getDataRPF2()
    {
      $sql = '';
      $response = array();

      $dcontenido = true;
      if ( !is_array($this->input->get('ids_productos')) && $this->input->get('dcontiene') == '')
      {
        if ($this->input->get('did_calidad') != '' || $this->input->get('did_tamanio') != '') {
          $_GET['ids_productos'] = array($this->input->get('dcalidad').' '.$this->input->get('dtamanio'));
          $dcontenido = false;
        } else
          exit();
      }

      if ($this->input->get('dcontiene') != '') {
        $_GET['ids_productos'] = array($this->input->get('dcontiene'));
      }

      foreach ($this->input->get('ids_productos') as $key => $prod)
      {
        $sql = "WHERE 1 = 1";
        if (is_numeric($prod)) {
          $sql .= " AND fp.id_clasificacion = {$prod}";
        } elseif ($dcontenido) {
          $prod = mb_strtoupper($prod, 'UTF-8');
          $sql .= " AND UPPER(fp.descripcion) LIKE '%{$prod}%'";
        }

        //Filtro de fecha.
        if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
          $sql .= " AND Date(f.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
        elseif($this->input->get('ffecha1') != '')
          $sql .= " AND Date(f.fecha) = '".$this->input->get('ffecha1')."'";
        elseif($this->input->get('ffecha2') != '')
          $sql .= " AND Date(f.fecha) = '".$this->input->get('ffecha2')."'";

        if ($this->input->get('fid_cliente') != '')
        {
          $sql .= " AND f.id_cliente = " . $this->input->get('fid_cliente');
        }

        if ($this->input->get('did_empresa') != '')
        {
          $sql .= " AND f.id_empresa = " . $this->input->get('did_empresa');
        }

        if ($this->input->get('dtipo') != '')
        {
          $sql .= " AND f.is_factura = '" . $this->input->get('dtipo') . "'";
        }

        if ($this->input->get('did_calidad') != '')
        {
          $sql .= " AND fp.id_calidad = " . $this->input->get('did_calidad');
        }

        if ($this->input->get('did_tamanio') != '')
        {
          $sql .= " AND fp.id_tamanio = " . $this->input->get('did_tamanio');
        }

        // filtra por pagadas
        if (isset($_GET['dpagadas']))
        {
          $sql .= " AND f.status = 'pa'";
        }
        // filtra por las que esten pendientes y pagadas.
        else
        {
          $sql .= " AND f.status != 'ca'";
        }

        $query = $this->db->query(
            "SELECT f.id_factura, DATE(f.fecha) as fecha, f.serie, f.folio, c.nombre_fiscal as cliente,
                    SUM(fp.cantidad) as cantidad, fp.precio_unitario,
                    SUM(fp.importe) as importe, COALESCE(fc.pol_seg, fc.certificado) AS poliza,
                    u.nombre AS unidad, u.codigo AS unidadc, COALESCE(fp.unidad_c, u.cantidad, 1) AS unidad_cantidad,
                    (SUM(fp.cantidad)*COALESCE(fp.unidad_c, u.cantidad, 1)) AS kilos, ca.nombre AS calibre
            FROM facturacion f
            INNER JOIN facturacion_productos fp ON fp.id_factura = f.id_factura
            INNER JOIN clientes c ON c.id_cliente= f.id_cliente
            LEFT JOIN unidades u ON u.id_unidad = fp.id_unidad
            LEFT JOIN calibres ca ON ca.id_calibre = fp.id_calibres
            LEFT JOIN facturacion_seg_cert fc ON f.id_factura = fc.id_factura AND fp.id_clasificacion = fc.id_clasificacion
            {$sql}
            GROUP BY f.id_factura, f.fecha, f.serie, f.folio, c.nombre_fiscal, fp.precio_unitario, fp.unidad_c, fc.pol_seg, fc.certificado, u.id_unidad, ca.id_calibre
            ORDER BY f.fecha ASC");

        if (is_numeric($prod)) {
          $prodcto = $this->db->query(
              "SELECT id_clasificacion, nombre FROM clasificaciones WHERE id_clasificacion = ".$prod)->row();
        } else {
          $prodcto = $this->db->query(
              "SELECT 0 AS id_clasificacion, '{$prod}' AS nombre")->row();
        }

        $response[] = array('producto' => $prodcto, 'listado' => $query->result());
        $query->free_result();
      }

      return $response;
    }
    public function prodfact2_pdf()
    {
      if (isset($_GET['did_producto']))
      {
        $facturas = $this->getDataRPF2();

        $this->load->model('empresas_model');
        $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

        $this->load->library('mypdf');
        // Creación del objeto de la clase heredada
        $pdf = new MYpdf('P', 'mm', 'Letter');
        $pdf->show_head = true;

        if ($empresa['info']->logo !== '')
          $pdf->logo = $empresa['info']->logo;

        $pdf->titulo1 = $empresa['info']->nombre_fiscal;
        $pdf->titulo2 = "Reporte Productos Facturados con Kilos";

        // $pdf->titulo3 = "{$_GET['dproducto']} \n";
        if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
            $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
        elseif (!empty($_GET['ffecha1']))
            $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1']);
        elseif (!empty($_GET['ffecha2']))
            $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha2']);

        $pdf->AliasNbPages();
        // $links = array('', '', '', '');
        $pdf->SetY(30);
        $aligns = array('C', 'C', 'L', 'L', 'L', 'R', 'R', 'R', 'R');
        $widths = array(15, 17, 65, 20, 13, 13, 19, 18, 22);
        $header = array('Fecha', 'Serie/Folio', 'Cliente', 'Poliza', 'Cantidad', 'UM', 'Kgs', 'Precio', 'Importe');

        $cantidad = 0;
        $importe = 0;
        $cantidadt = 0;
        $kilost = 0;
        $importet = 0;
        $promedio = 0;

        foreach($facturas as $key => $product)
        {
          $cantidad = 0;
          $kilos = 0;
          $importe = 0;
          if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
          {
              $pdf->AddPage();

              $pdf->SetFont('Arial','B',7);
              $pdf->SetTextColor(255,255,255);
              $pdf->SetFillColor(160,160,160);
              $pdf->SetX(6);
              $pdf->SetAligns($aligns);
              $pdf->SetWidths($widths);
              $pdf->Row($header, true);
          }
          $pdf->SetFont('Arial','B',7);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetX(6);
          $pdf->SetAligns(array('L'));
          $pdf->SetWidths(array(180));
          $pdf->Row(array($product['producto']->nombre), false, false);

          foreach ($product['listado'] as $key2 => $item)
          {
            $band_head = false;
            if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
            {
                $pdf->AddPage();

                $pdf->SetFont('Arial','B',7);
                $pdf->SetTextColor(255,255,255);
                $pdf->SetFillColor(160,160,160);
                $pdf->SetX(6);
                $pdf->SetAligns($aligns);
                $pdf->SetWidths($widths);
                $pdf->Row($header, true);
            }

            $pdf->SetFont('Arial','',7);
            $pdf->SetTextColor(0,0,0);

            $datos = array(
              MyString::fechaAT($item->fecha),
              $item->serie.'-'.$item->folio,
              $item->cliente,
              $item->poliza,
              $item->cantidad,
              $item->unidadc,
              MyString::formatoNumero($item->kilos, 2, '', false),
              MyString::formatoNumero($item->precio_unitario/($item->unidad_cantidad>0?$item->unidad_cantidad:1), 3, '$', false),
              MyString::formatoNumero($item->importe, 2, '$', false)
            );

            $cantidad += floatval($item->cantidad);
            $kilos    += floatval($item->kilos);
            $importe  += floatval($item->importe);

            $cantidadt += floatval($item->cantidad);
            $kilost    += floatval($item->kilos);
            $importet  += floatval($item->importe);

            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($datos, false);
          }

          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);

          $pdf->SetFont('Arial','B',7);
          $pdf->SetTextColor(255,255,255);
          $pdf->Row(array('', '', '', '',
              $cantidad,
              '',
              MyString::formatoNumero($kilos, 2, '', false),
              $cantidad == 0 ? 0 : MyString::formatoNumero($importe/($kilos>0?$kilos:1), 2, '$', false),
              MyString::formatoNumero($importe, 2, '$', false) ), true);
        }

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);

        $pdf->SetFont('Arial','B',7);
        $pdf->SetTextColor(255,255,255);
        $pdf->Row(array('', '', '', '',
            $cantidadt,
            '',
            MyString::formatoNumero($kilost, 2, '', false),
            $cantidadt == 0 ? 0 : MyString::formatoNumero($importet/($kilost>0?$kilost:1), 2, '$', false),
            MyString::formatoNumero($importet, 2, '$', false) ), true);

        $pdf->Output('Reporte_Productos_Facturados.pdf', 'I');
      }
    }

    public function prodfact2_xls()
    {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=productos_facturados.xls");
      header("Pragma: no-cache");
      header("Expires: 0");

      $facturas = $this->getDataRPF2();

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $titulo1 = $empresa['info']->nombre_fiscal;
      $titulo2 = "Reporte Productos Facturados con Kilos";
      $titulo3 = "";
      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
          $titulo3 .= "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      elseif (!empty($_GET['ffecha1']))
          $titulo3 .= "Del ".$_GET['ffecha1'];
      elseif (!empty($_GET['ffecha2']))
          $titulo3 .= "Del ".$_GET['ffecha2'];

      $html = '<table>
        <tbody>
          <tr>
            <td colspan="10" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
          </tr>
          <tr>
            <td colspan="10" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
          </tr>
          <tr>
            <td colspan="10" style="text-align:center;">'.$titulo3.'</td>
          </tr>
          <tr>
            <td colspan="10"></td>
          </tr>
          <tr style="font-weight:bold">
            <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
            <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Serie/Folio</td>
            <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Cliente</td>
            <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Poliza</td>
            <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
            <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Unidad</td>
            <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Kgs</td>
            <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Precio</td>
            <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Importe</td>
            <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Calibre</td>
          </tr>';
      $total_importe = $total_cantidad = $total_kilos = 0;
      $total_importet = $total_cantidadt = $total_kilost = 0;
      foreach ($facturas as $key => $produc)
      {
        $total_importe = $total_cantidad = $total_kilos = 0;

        $html .= '<tr>
              <td colspan="6" style="font-size:14px;border:1px solid #000;">'.$produc['producto']->nombre.'</td>
            </tr>';
        foreach ($produc['listado'] as $key2 => $value)
        {
          $html .= '<tr>
              <td style="width:150px;border:1px solid #000;">'.$value->fecha.'</td>
              <td style="width:100px;border:1px solid #000;">'.$value->serie.$value->folio.'</td>
              <td style="width:400px;border:1px solid #000;">'.$value->cliente.'</td>
              <td style="width:400px;border:1px solid #000;">'.$value->poliza.'</td>
              <td style="width:100px;border:1px solid #000;">'.$value->cantidad.'</td>
              <td style="width:100px;border:1px solid #000;">'.$value->unidadc.'</td>
              <td style="width:100px;border:1px solid #000;">'.$value->kilos.'</td>
              <td style="width:150px;border:1px solid #000;">'.$value->precio_unitario.'</td>
              <td style="width:150px;border:1px solid #000;">'.$value->importe.'</td>
              <td style="width:150px;border:1px solid #000;">'.$value->calibre.'</td>
            </tr>';
            $total_importe   += $value->importe;
            $total_cantidad  += $value->cantidad;
            $total_kilos     += $value->kilos;
            $total_importet  += $value->importe;
            $total_cantidadt += $value->cantidad;
            $total_kilost    += $value->kilos;
        }
        $html .= '
          <tr style="font-weight:bold">
            <td colspan="4">TOTAL</td>
            <td style="border:1px solid #000;">'.$total_cantidad.'</td>
            <td style="border:1px solid #000;"></td>
            <td style="border:1px solid #000;">'.$total_kilos.'</td>
            <td style="border:1px solid #000;">'.($total_cantidad == 0 ? 0 : $total_importe/$total_cantidad).'</td>
            <td style="border:1px solid #000;">'.$total_importe.'</td>
            <td style="border:1px solid #000;"></td>
          </tr>
          <tr>
            <td colspan="6"></td>
          </tr>
          <tr>
            <td colspan="6"></td>
          </tr>';
      }

      $html .= '
          <tr style="font-weight:bold">
            <td colspan="4">TOTALES</td>
            <td style="border:1px solid #000;">'.$total_cantidadt.'</td>
            <td style="border:1px solid #000;"></td>
            <td style="border:1px solid #000;">'.$total_kilost.'</td>
            <td style="border:1px solid #000;">'.($total_cantidadt == 0 ? 0 : $total_importet/$total_cantidadt).'</td>
            <td style="border:1px solid #000;">'.$total_importet.'</td>
            <td style="border:1px solid #000;"></td>
          </tr>
        </tbody>
      </table>';

      echo $html;
    }


  public function ventasAcumuladoData()
  {
    $response = array();

    $sql = "WHERE 1 = 1";

    $prod = null;
    if ($this->input->get('dcontiene') != '') {
      $prod = mb_strtoupper($prod, 'UTF-8');
      $sql .= " AND UPPER(fp.descripcion) LIKE '%{$prod}%'";
    }

    if (is_array($this->input->get('ids_productos'))) {
      $sql .= " AND fp.id_clasificacion in(".implode(', ', $this->input->get('ids_productos')).")";
    } else {
      $sql .= " AND 1 = 2 ";
    }

    //Filtro de fecha.
    if($this->input->get('ffecha1') == '' && $this->input->get('ffecha2') == '')
      $sql .= " AND Date(f.fecha) BETWEEN '".date("Y-m")."-01' AND '".date("Y-m-d")."'";
    elseif($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql .= " AND Date(f.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql .= " AND Date(f.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql .= " AND Date(f.fecha) = '".$this->input->get('ffecha2')."'";

    if ($this->input->get('fid_cliente') != '')
    {
      $sql .= " AND f.id_cliente = " . $this->input->get('fid_cliente');
    }

    if ($this->input->get('did_empresa') != '')
    {
      $sql .= " AND f.id_empresa = " . $this->input->get('did_empresa');
    }

    if ($this->input->get('dtipo') != '')
    {
      $sql .= " AND f.is_factura = '" . $this->input->get('dtipo') . "'";
    }

    // filtra por pagadas
    if (isset($_GET['dpagadas']))
    {
      $sql .= " AND f.status = 'pa'";
    }
    // filtra por las que esten pendientes y pagadas.
    else
    {
      $sql .= " AND f.status != 'ca'";
    }

    $query = $this->db->query(
      "SELECT c.id_cliente, c.nombre_fiscal as cliente,
          SUM(fp.cantidad) as cantidad, -- fp.precio_unitario,
          SUM(fp.importe) as importe,
          cl.id_clasificacion, cl.nombre AS producto
      FROM facturacion f
        INNER JOIN facturacion_productos fp ON fp.id_factura = f.id_factura
        INNER JOIN clientes c ON c.id_cliente = f.id_cliente
        INNER JOIN clasificaciones cl ON cl.id_clasificacion = fp.id_clasificacion
        {$sql}
      GROUP BY c.id_cliente, cl.id_clasificacion
      ORDER BY cliente ASC, producto ASC");

    $datos = $query->result();
    if (count($datos) > 0) {
      $auxp = 0;
      $prouctos = [];
      foreach ($datos as $key => $row) {
        $prouctos[$row->id_clasificacion] = $row->producto;
      }

      foreach ($datos as $key => $row)
      {
        if (!isset($response[$row->id_cliente])) {
          $response[$row->id_cliente] = [
            'cliente' => $row->cliente,
            'total_piezas' => 0,
            'total_importe' => 0,
            'productos' => []
          ];
          foreach ($prouctos as $key1 => $prod) {
            $response[$row->id_cliente]['productos'][$key1] = [
              'producto' => $prod,
              'piezas' => 0,
              'importe' => 0
            ];
          }
        }

        $response[$row->id_cliente]['total_piezas'] += $row->cantidad;
        $response[$row->id_cliente]['total_importe'] += $row->importe;
        $response[$row->id_cliente]['productos'][$row->id_clasificacion]['piezas'] += $row->cantidad;
        $response[$row->id_cliente]['productos'][$row->id_clasificacion]['importe'] += $row->importe;
      }
    }

    return $response;
  }

  /**
   * Reportes Productos Facturados.
   *
   * @return void
   */
  public function ventasAcumulado_pdf()
  {
    // if (isset($_GET['did_producto']))
    // {
      if (empty($_GET['did_empresa'])) {
        $empresaDef = $this->empresas_model->getDefaultEmpresa();
        $_GET['did_empresa'] = $empresaDef->id_empresa;
      }

      $ventas = $this->ventasAcumuladoData();
      if (count($ventas) == 0) {
        return false;
      }
      // echo "<pre>";
      // var_dump($ventas);
      // echo "</pre>";exit;

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->show_head = true;

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      $pdf->titulo2 = "Reporte Ventas Acumuladas";

      // $pdf->titulo3 = "{$_GET['dproducto']} \n";
      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
          $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
      elseif (!empty($_GET['ffecha1']))
          $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1']);
      elseif (!empty($_GET['ffecha2']))
          $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha2']);

      $tipo = (!empty($_GET['dtipoReporte'])? $_GET['dtipoReporte']: 'piezas');
      $tipoSim = ($tipo=='piezas'? '': '$');

      $pdf->AliasNbPages();
      // $links = array('', '', '', '');
      $pdf->SetY(30);


      $aligns = array('L');
      $widths = array(60);
      $totalesp = ['TOTALES'];
      $header = array('CLIENTE');
      foreach (array_values($ventas)[0]['productos'] as $key => $value) {
        $aligns[] = 'R';
        $widths[] = 25;
        $header[] = $value['producto'];
        $totalesp[$key] = 0;
      }
      $aligns[] = 'R';
      $widths[] = 25;
      $header[] = strtoupper("Total {$tipo}");
      $totalesp['end'] = 0;

      $cantidadt = 0;
      $importet = 0;

      foreach($ventas as $key => $venta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
            $pdf->AddPage();

            $pdf->SetFont('Arial','B',7);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFillColor(200,200,200);
            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, true);
        }
        $pdf->SetFont('Arial','', 7);
        $pdf->SetTextColor(0,0,0);

        $datos = array($venta['cliente']);
        foreach ($venta['productos'] as $key1 => $prod) {
          $datos[] = MyString::formatoNumero($prod[$tipo], 2, $tipoSim, false);
          $totalesp[$key1] += $prod[$tipo];
        }
        $datos[] = MyString::formatoNumero($venta["total_{$tipo}"], 2, $tipoSim, false);

        $totalesp['end'] += floatval($venta["total_{$tipo}"]);

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(0,0,0);
      foreach ($totalesp as $key => $value) {
        if ($key > 0) {
          $totalesp[$key] = MyString::formatoNumero($value, 2, $tipoSim, false);
        }
      }
      $pdf->Row(array_values($totalesp), true);

      $pdf->Output('Reporte_ventas_acumuladas.pdf', 'I');
    // }
  }

  public function ventasAcumulado_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=Reporte_ventas_acumuladas.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $ventas = $this->ventasAcumuladoData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Reporte Ventas Acumuladas";
    $titulo3 = "";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $titulo3 .= "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
    elseif (!empty($_GET['ffecha1']))
        $titulo3 .= "Del ".$_GET['ffecha1'];
    elseif (!empty($_GET['ffecha2']))
        $titulo3 .= "Del ".$_GET['ffecha2'];

    $tipo = (!empty($_GET['dtipoReporte'])? $_GET['dtipoReporte']: 'piezas');
    $tipoSim = ($tipo=='piezas'? '': '$');

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>
        <tr style="font-weight:bold">';

    $totalesp = ['TOTALES'];
    $html .= '<td style="width:150px;border:1px solid #000;background-color: #cccccc;">CLIENTE</td>';
    foreach (array_values($ventas)[0]['productos'] as $key => $value) {
      $html .= '<td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$value['producto'].'</td>';
      $totalesp[$key] = 0;
    }
    $html .= '<td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.strtoupper("Total {$tipo}").'</td>
        </tr>';
    $totalesp['end'] = 0;

    $cantidadt = 0;
    $importet = 0;

    foreach($ventas as $key => $venta)
    {
      $html .= '<tr>';

      $html .= '<td style="width:150px;border:1px solid #000;">'.$venta['cliente'].'</td>';
      foreach ($venta['productos'] as $key1 => $prod) {
        $html .= '<td style="width:150px;border:1px solid #000;">'.$prod[$tipo].'</td>';
        $totalesp[$key1] += $prod[$tipo];
      }
      $html .= '<td style="width:150px;border:1px solid #000;">'.$venta["total_{$tipo}"].'</td>';

      $totalesp['end'] += floatval($venta["total_{$tipo}"]);
    }

    $html .= '
        <tr style="font-weight:bold">';
        foreach ($totalesp as $key => $value) {
          $html .= '<td style="border:1px solid #000;">'.$value.'</td>';
        }
    $html .= '</tr>
      </tbody>
    </table>';

    echo $html;
  }

}