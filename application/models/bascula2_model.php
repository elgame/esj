<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bascula2_model extends bascula_model {

  function __construct()
  {
    parent::__construct();
  }


  /*
   |-------------------------------------------------------------------------
   |  REPORTES
   |-------------------------------------------------------------------------
  */

   public function rde_data()
   {
      $sql3 = $sql = $sql2 = '';

      $_GET['ffecha1'] = $this->input->get('ffecha1') != '' ? $_GET['ffecha1'] : date('Y-m-d');
      $_GET['ffecha2'] = $this->input->get('ffecha2') != '' ? $_GET['ffecha2'] : date('Y-m-d');
      $fecha_compara = 'fecha_tara';

      $this->load->model('areas_model');
      $_GET['farea'] = $this->input->get('farea') != '' ? $_GET['farea'] : $this->areas_model->getAreaDefault();
      if ($this->input->get('farea') != '') {
        if ($this->input->get('farea') != 'all') {
          // $sql .= " AND b.id_area = " . $_GET['farea'];
          // $sql2 .= " AND b.id_area = " . $_GET['farea'];
          $sql3 = " AND id_area = " . $_GET['farea'];
        }
      }

      $calidad_val = null;
      if(isset($_GET['fcalidad']{0})) {
        $calidad_val = $_GET['fcalidad'];
      }

      if ($this->input->get('fid_proveedor') != ''){
        if($this->input->get('ftipo') == 'sa'){
          $sql .= " AND b.id_cliente = '".$_GET['fid_proveedor']."'";
          $sql2 .= " AND b.id_cliente = '".$_GET['fid_proveedor']."'";
        }else{
          $sql .= " AND b.id_proveedor = '".$_GET['fid_proveedor']."'";
          $sql2 .= " AND b.id_proveedor = '".$_GET['fid_proveedor']."'";
        }
      }

      if ($this->input->get('fid_empresa') != ''){
        $sql .= " AND b.id_empresa = '".$_GET['fid_empresa']."'";
        $sql2 .= " AND b.id_empresa = '".$_GET['fid_empresa']."'";
      }
      if ($this->input->get('fstatus') != '')
      {
        if ($this->input->get('fstatus') === '1')
          if($this->input->get('fefectivo') == 'si')
          {
            $sql .= " AND b.accion = 'p'";
            $fecha_compara = 'fecha_pago';
          }
          else
            $sql .= " AND (b.accion = 'p' OR b.accion = 'b')";
        else
          $sql .= " AND (b.accion = 'en' OR b.accion = 'sa')";
      }

      $sql .= " AND DATE(b.{$fecha_compara}) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      $sql2 .= " AND DATE(b.{$fecha_compara}) BETWEEN '".$_GET['ffecha1']."'  AND '".$_GET['ffecha2']."' ";

      //Filtros del tipo de pesadas
      if ($this->input->get('ftipo') != '')
        $sql .= " AND b.tipo = '{$_GET['ftipo']}'";
      $campos = "p.nombre_fiscal AS proveedor, p.cuenta_cpi, ";
      $table_ms = 'LEFT JOIN proveedores p ON p.id_proveedor = b.id_proveedor';
      $tipo_rpt = "Entrada";
      if($this->input->get('ftipo') == 'sa') {
        $campos = "c.nombre_fiscal AS proveedor, c.cuenta_cpi, ";
        $table_ms = 'LEFT JOIN clientes c ON c.id_cliente = b.id_cliente';
        $tipo_rpt = "Salida";
      }

      $this->load->model('areas_model');

      $_GET['ftipo'] = 'fr';
      $areas = $this->areas_model->getAreas(false, $sql3);

      $response = [];

      foreach ($areas['areas'] as $keya => $varea) {
        $query = $this->db->query(
          "SELECT bc.id_bascula,
            bc.id_calidad,
            bc.cajas,
            bc.kilos,
            bc.promedio,
            bc.precio,
            bc.importe,
            {$campos}
            b.folio,
            b.accion AS pagado,
            Date(b.{$fecha_compara}) AS fecha
          FROM bascula_compra AS bc
          INNER JOIN bascula AS b ON b.id_bascula = bc.id_bascula
          {$table_ms}
          WHERE b.status = true AND b.id_area = {$varea->id_area}
                {$sql}
          ORDER BY (b.folio, bc.id_calidad) ASC
          "
        );

        // Obtiene la informacion del Area filtrada.
        $area = $this->areas_model->getAreaInfo($varea->id_area);

        $rde = array();
        if ($query->num_rows() > 0)
        {
          // echo "<pre>";
          //   var_dump($area);
          // echo "</pre>";exit;

          foreach ($area['calidades'] as $key => $calidad)
          {
            if ($calidad_val == $calidad->id_calidad || $calidad_val === null) {
              $rde[$key] = array('calidad' => $calidad->nombre, 'cajas' => array());
              foreach ($query->result() as $key2 => $caja)
                if ($caja->id_calidad == $calidad->id_calidad)
                  $rde[$key]['cajas'][] = $caja;
            }
          }

          foreach ($rde as $key => $calidad)
            if (count($calidad['cajas']) === 0)
              unset($rde[$key]);
        }

        $cancelados = $this->db->query(
          "SELECT SUM(b.importe) as cancelado
          FROM bascula AS b
          WHERE b.id_bonificacion is null AND
                b.status = false AND
                b.tipo = 'en'
                {$sql2}
          ")->row()->cancelado;

        $response[] = array('rde' => $rde, 'area' => $area, 'cancelados' => $cancelados, 'tipo' => $tipo_rpt);
      }


      return $response;
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Diario de Entradas.
    *
    * @return void
    */
   public function rde_pdf()
   {

      // Obtiene los datos del reporte.
      $data = $this->rde_data();

      // echo "<pre>";
      //   var_dump($data);
      // echo "</pre>";exit;

      // $rde = $data['rde'];

      // $area = $data['area'];
      // echo "<pre>";
      //   var_dump($area);
      // echo "</pre>";exit;

      $fecha = new DateTime($_GET['ffecha1']);
      $fecha2 = new DateTime($_GET['ffecha2']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');

      if (isset($_GET['fid_empresa']) && $_GET['fid_empresa'] !== '')
      {
        $this->load->model('empresas_model');
        $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('fid_empresa'));

        if ($empresa['info']->logo !== '')
          $pdf->logo = $empresa['info']->logo;
        $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      }

      $pdf->titulo2 = "REPORTE DIARIO DE ENTRADAS <".$data[0]['tipo'].'>';
      $pdf->titulo3 = $fecha->format('d/m/Y')." Al ".$fecha2->format('d/m/Y')." | ".$this->input->get('fproveedor').' | '.$this->input->get('fempresa');

      $pdf->AliasNbPages();
      $pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'C', 'L', 'C', 'C', 'C', 'C', 'C');
      $aligns1 = array('C', 'C', 'C', 'L', 'R', 'R', 'R', 'R', 'R');
      $widths = array(6, 20, 17, 55, 16, 25, 25, 17, 25);
      $header = array('',   'FECHA', 'BOLETA','NOMBRE', 'PROM',
                      'CAJAS', 'KILOS', 'PRECIO','IMPORTE');

      $gtotalPagado    = 0;
      $gtotalNoPagado  = 0;
      $gtotalCancelado = 0;
      $gtotalImporte   = 0;

      foreach($data as $keya => $row)
      {
        $totalPagado    = 0;
        $totalNoPagado  = 0;
        $totalCancelado = 0;

        $pdf->SetFont('helvetica','B', 9);
        $pdf->SetTextColor(0,0,0);

        $pdf->SetY($pdf->GetY());
        $pdf->SetX(6);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(206));
        $pdf->Row(array($row['area']['info']->nombre), false, false);

        foreach($row['rde'] as $key => $calidad)
        {
          if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
          {
            if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

            $pdf->SetFont('helvetica','B', 8);
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFillColor(160,160,160);
            $pdf->SetY($pdf->GetY());
            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, false);
          }

          $pdf->SetFont('helvetica','', 9);
          $pdf->SetTextColor(0,0,0);

          $pdf->SetY($pdf->GetY()-1);
          $pdf->SetX(6);
          $pdf->SetAligns(array('L'));
          $pdf->SetWidths(array(206));
          $pdf->Row(array($calidad['calidad']), false, false);

          $pdf->SetFont('helvetica','',8);
          $pdf->SetTextColor(0,0,0);

          $promedio = 0;
          $cajas    = 0;
          $kilos    = 0;
          $precio   = 0;
          $importe  = 0;

          foreach ($calidad['cajas'] as $caja)
          {
            if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
            {
              $pdf->AddPage();

              $pdf->SetFont('helvetica','B', 8);
              $pdf->SetTextColor(0,0,0);
              $pdf->SetFillColor(160,160,160);
              $pdf->SetY($pdf->GetY()-2);
              $pdf->SetX(6);
              $pdf->SetAligns($aligns);
              $pdf->SetWidths($widths);
              $pdf->Row($header, false);
            }

            $pdf->SetFont('helvetica','',8);
            $pdf->SetTextColor(0,0,0);

            $promedio += $caja->promedio;
            $cajas    += $caja->cajas;
            $kilos    += $caja->kilos;
            $precio   += $caja->precio;
            $importe  += $caja->importe;

            if ($caja->pagado === 'p' || $caja->pagado === 'b')
              $totalPagado += $caja->importe;
            else
              $totalNoPagado += $caja->importe;

            $datos = array(($caja->pagado === 'p' || $caja->pagado === 'b') ? ucfirst($caja->pagado) : '',
                           $caja->fecha,
                           $caja->folio,
                           substr($caja->proveedor, 0, 28),
                           MyString::formatoNumero($caja->promedio, 2, '', false),
                           $caja->cajas,
                           $caja->kilos,
                           MyString::formatoNumero($caja->precio, 2, '$', false),
                           MyString::formatoNumero($caja->importe, 2, '$', false));

            $pdf->SetY($pdf->GetY()-2);
            $pdf->SetX(6);
            $pdf->SetAligns($aligns1);
            $pdf->SetWidths($widths);
            $pdf->Row($datos, false, false);
          }

          $pdf->SetY($pdf->GetY()-1);
          $pdf->SetX(6);
          $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R', 'R'));
          $pdf->SetWidths(array(98, 16, 25, 25, 17, 25));
          $pdf->Row(array(
            'TOTALES',
            MyString::formatoNumero($kilos/$cajas, 2, '', false),
            $cajas,
            $kilos,
            MyString::formatoNumero($importe/$kilos, 2, '$', false),
            MyString::formatoNumero($importe, 2, '$', false)), false, false);

        }

        if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
        {
          $pdf->AddPage();
        }

        $pdf->SetFont('helvetica','B', 8);
        // $pdf->SetX(6);
        $pdf->SetY($pdf->getY() + 6);
        $pdf->SetAligns(array('C', 'C', 'C', 'C'));
        $pdf->SetWidths(array(50, 50, 50, 50));
        $pdf->Row(array(
          'PAGADO',
          'NO PAGADO',
          'CANCELADO',
          'TOTAL IMPORTE'), false);

        $totalImporte = (floatval($totalPagado) + floatval($totalNoPagado)) - floatval($row['cancelados']);

        if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
        {
          $pdf->AddPage();
        }
        $pdf->SetAligns(array('C', 'C', 'C', 'C'));
        $pdf->SetWidths(array(50, 50, 50, 50));
        $pdf->Row(array(
          MyString::formatoNumero($totalPagado, 2, '$', false),
          MyString::formatoNumero($totalNoPagado, 2, '$', false),
          MyString::formatoNumero($row['cancelados'], 2, '$', false),
          MyString::formatoNumero($totalImporte, 2, '$', false)), false);

        $gtotalPagado    += $totalPagado;
        $gtotalNoPagado  += $totalNoPagado;
        $gtotalCancelado += $row['cancelados'];
        $gtotalImporte   += $totalImporte;
      }

      if(count($data) > 1) {
        if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
        {
          $pdf->AddPage();
        }

        $pdf->SetFont('helvetica','B', 8);
        // $pdf->SetX(6);
        $pdf->SetY($pdf->getY() + 6);
        $pdf->SetAligns(array('C', 'C', 'C', 'C'));
        $pdf->SetWidths(array(50, 50, 50, 50));
        $pdf->Row(array(
          'GRAL PAGADO',
          'GRAL NO PAGADO',
          'GRAL CANCELADO',
          'GRAL TOTAL IMPORTE'), false);

        $totalImporte = (floatval($totalPagado) + floatval($totalNoPagado)) - floatval($row['cancelados']);

        if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
        {
          $pdf->AddPage();
        }
        $pdf->SetAligns(array('C', 'C', 'C', 'C'));
        $pdf->SetWidths(array(50, 50, 50, 50));
        $pdf->Row(array(
          MyString::formatoNumero($gtotalPagado, 2, '$', false),
          MyString::formatoNumero($gtotalNoPagado, 2, '$', false),
          MyString::formatoNumero($gtotalCancelado, 2, '$', false),
          MyString::formatoNumero($gtotalImporte, 2, '$', false)), false);
      }

      $pdf->Output('REPORTE_DIARIO_ENTRADAS_'.$data[0]['area']['info']->nombre.'_'.$fecha->format('d/m/Y').'.pdf', 'I');
  }

  public function rdefull_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_diario_entradas.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Obtiene los datos del reporte.
    $data = $this->rde_data();

    // $rde = $data['rde'];

    // $area = $data['area'];

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $titulo1 = '';
    if (isset($_GET['fid_empresa']) && $_GET['fid_empresa'] !== '')
    {
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $titulo1 = $empresa['info']->nombre_fiscal;
    }

    $titulo2 = "REPORTE DIARIO DE ENTRADAS <".$data[0]['tipo'].'>';
    $titulo3 = $fecha->format('d/m/Y')." Al ".$fecha2->format('d/m/Y')." | ".$this->input->get('fproveedor').' | '.$this->input->get('fempresa');

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
        <tr style="font-weight:bold">
          <td style="width:30px;border:1px solid #000;background-color: #cccccc;"></td>
          <td style="width:50px;border:1px solid #000;background-color: #cccccc;">BOLETA</td>
          <td style="width:80px;border:1px solid #000;background-color: #cccccc;">CUENTA</td>
          <td style="width:300px;border:1px solid #000;background-color: #cccccc;">NOMBRE</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">PROM</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">CAJAS</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">KILOS</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">PRECIO</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">IMPORTE</td>
        </tr>';
    $gtotalPagado    = 0;
    $gtotalNoPagado  = 0;
    $gtotalCancelado = 0;
    $gtotalImporte   = 0;

    foreach($data as $keya => $row)
    {
      $totalPagado    = 0;
      $totalNoPagado  = 0;
      $totalCancelado = 0;
      $html .= '<tr>
              <td colspan="9" style="font-size:16px;border:1px solid #000;">'.$row['area']['info']->nombre.'</td>
            </tr>';

      foreach($row['rde'] as $key => $calidad)
      {
        $promedio = 0;
        $cajas    = 0;
        $kilos    = 0;
        $precio   = 0;
        $importe  = 0;

        $html .= '<tr>
              <td colspan="9" style="font-size:14px;border:1px solid #000;">'.$calidad['calidad'].'</td>
            </tr>';
        foreach ($calidad['cajas'] as $caja)
        {
          $promedio += $caja->promedio;
          $cajas    += $caja->cajas;
          $kilos    += $caja->kilos;
          $precio   += $caja->precio;
          $importe  += $caja->importe;

          if ($caja->pagado === 'p' || $caja->pagado === 'b')
            $totalPagado += $caja->importe;
          else
            $totalNoPagado += $caja->importe;

          $html .= '<tr>
              <td style="width:30px;border:1px solid #000;">'.(($caja->pagado === 'p' || $caja->pagado === 'b') ? ucfirst($caja->pagado) : '').'</td>
              <td style="width:50px;border:1px solid #000;">'.$caja->folio.'</td>
              <td style="width:80px;border:1px solid #000;">'.$caja->cuenta_cpi.'</td>
              <td style="width:300px;border:1px solid #000;">'.substr($caja->proveedor, 0, 28).'</td>
              <td style="width:100px;border:1px solid #000;">'.$caja->promedio.'</td>
              <td style="width:100px;border:1px solid #000;">'.$caja->cajas.'</td>
              <td style="width:100px;border:1px solid #000;">'.$caja->kilos.'</td>
              <td style="width:100px;border:1px solid #000;">'.$caja->precio.'</td>
              <td style="width:100px;border:1px solid #000;">'.$caja->importe.'</td>
            </tr>';
        }

        $html .= '
          <tr style="font-weight:bold">
            <td colspan="4">TOTALES</td>
            <td style="border:1px solid #000;">'.($kilos/$cajas).'</td>
            <td style="border:1px solid #000;">'.$cajas.'</td>
            <td style="border:1px solid #000;">'.$kilos.'</td>
            <td style="border:1px solid #000;">'.($importe/$kilos).'</td>
            <td style="border:1px solid #000;">'.$importe.'</td>
          </tr>
          <tr>
            <td colspan="9"></td>
          </tr>
          <tr>
            <td colspan="9"></td>
          </tr>';
      }
      $totalImporte = (floatval($totalPagado) + floatval($totalNoPagado)) - floatval($row['cancelados']);
      $html .= '
          <tr style="font-weight:bold">
            <td colspan="3">PAGADO</td>
            <td colspan="2">NO PAGADO</td>
            <td colspan="2">CANCELADO</td>
            <td colspan="2">TOTAL IMPORTE</td>
          </tr>
          <tr style="font-weight:bold">
            <td colspan="3">'.$totalPagado.'</td>
            <td colspan="2">'.$totalNoPagado.'</td>
            <td colspan="2">'.$row['cancelados'].'</td>
            <td colspan="2">'.$totalImporte.'</td>
          </tr>';

      $gtotalPagado    += $totalPagado;
      $gtotalNoPagado  += $totalNoPagado;
      $gtotalCancelado += $row['cancelados'];
      $gtotalImporte   += $totalImporte;
    }

    if (count($data) > 1)
      $html .= '
          <tr style="font-size:14px;font-weight:bold">
            <td colspan="3">GRAL PAGADO</td>
            <td colspan="2">GRAL NO PAGADO</td>
            <td colspan="2">GRAL CANCELADO</td>
            <td colspan="2">GRAL TOTAL IMPORTE</td>
          </tr>
          <tr style="font-size:14px;font-weight:bold">
            <td colspan="3">'.$gtotalPagado.'</td>
            <td colspan="2">'.$gtotalNoPagado.'</td>
            <td colspan="2">'.$gtotalCancelado.'</td>
            <td colspan="2">'.$gtotalImporte.'</td>
          </tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
  }


  public function rde_xls()
  {
    $res = $this->rde_data();

    $data = array();
    foreach ($res['rde'] as $key => $calidad)
    {
      foreach ($calidad['cajas'] as $key => $caja)
      {
        if (array_key_exists($caja->folio, $data))
          $data[$caja->folio]->importe += $caja->importe;
        else
          $data[$caja->folio] = $caja;
      }
    }

    $this->load->library('myexcel');
    $xls = new myexcel();

    $worksheet =& $xls->workbook->addWorksheet();

    $xls->titulo2 = 'REPORTE DIARIO DE ENTRADAS';
    $xls->titulo3 = "<".$res['area']['info']->nombre."> DEL DIA " . $this->input->get('ffecha1');
    $xls->titulo4 = 'Pagos en efectivo';

    $row=0;
    //Header
    $xls->excelHead($worksheet, $row, 8, array(
        array($xls->titulo2, 'format_title2'),
        array($xls->titulo3, 'format_title3'),
        array($xls->titulo4, 'format_title3')
    ));

    foreach ($data as $key => $value)
    {
      $data[$key]->colnull = '';
    }

    $row +=3;
    $xls->excelContent($worksheet, $row, $data, array(
        'head' => array('BOLETA', 'PRODUCTOR', '', '', 'IMPORTE'),
        'conte' => array(
            array('name' => 'folio', 'format' => 'format4', 'sum' => -1),
            array('name' => 'proveedor', 'format' => 'format4', 'sum' => -1),
            array('name' => 'colnull', 'format' => 'format4', 'sum' => -1),
            array('name' => 'colnull', 'format' => 'format4', 'sum' => -1),
            array('name' => 'importe', 'format' => 'format4', 'sum' => 0),
          )
    ));

    $xls->workbook->send('reporte_diario_entradas.xls');
    $xls->workbook->close();
  }


  public function getDataMovimientosAuditoriaSa(&$data)
  {
    $sql = '';

    $_GET['fechaini'] = $this->input->get('fechaini') != '' ? $_GET['fechaini'] : date('Y-m-01');
    $_GET['fechaend'] = $this->input->get('fechaend') != '' ? $_GET['fechaend'] : date('Y-m-d');
    if ($this->input->get('fechaini') != '' && $this->input->get('fechaend') != '')
    $sql .= " AND DATE(b.fecha_bruto) >= '".$this->input->get('fechaini')."' AND
                  DATE(b.fecha_bruto) <= '".$this->input->get('fechaend')."'";

    $_GET['farea'] = $this->input->get('farea') != '' ? $_GET['farea'] : '1';
    if ($this->input->get('farea') != '')
      $sql .= " AND b.id_area = " . $_GET['farea'];

    if ($this->input->get('fid_proveedor') != ''){
      if($this->input->get('ftipop') == 'sa'){
        $sql .= " AND b.id_cliente = '".$_GET['fid_proveedor']."'";
      }else{
        $sql .= " AND b.id_proveedor = '".$_GET['fid_proveedor']."'";
      }
    }

    if ($this->input->get('fid_empresa') != '') {
      $sql .= " AND b.id_empresa = '".$_GET['fid_empresa']."'";
    }

    if ($this->input->get('prancho') != '') {
      $sql .= " AND Upper(b.rancho) LIKE '".mb_strtoupper($_GET['prancho'], 'UTF-8')."'";
    }

    if ($this->input->get('fstatusp') != '')
      if ($this->input->get('fstatusp') === '1')
        $sql .= " AND b.accion IN ('p', 'b')";
      else
        $sql .= " AND b.accion IN ('en', 'sa')";

    //Filtros del tipo de pesadas
    if ($this->input->get('ftipop') != '')
      $sql .= " AND b.tipo = '{$_GET['ftipop']}'";
    $table_ms = 'LEFT JOIN proveedores p ON p.id_proveedor = b.id_proveedor';
    $tipo_rpt = "Entrada";
    if($this->input->get('ftipop') == 'sa') {
      $table_ms = 'LEFT JOIN clientes c ON c.id_cliente = b.id_cliente';
      $tipo_rpt = "Salida";
    }

    // if ($this->input->get('ftipop') != '')
    //   if ($this->input->get('ftipop') === '1')
    //     $sql .= " AND b.tipo = 'en'";
    //   else
    //     $sql .= " AND b.tipo = 'sa'";

    if (isset($_GET['pe']))
      $sql = " AND b.id_bascula IN (".$_GET['pe'].")";

    $query = $this->db->query(
      "SELECT b.accion as status,
         b.folio,
         DATE(b.fecha_bruto) as fecha,
         COALESCE(cl.nombre) as calidad,
         COALESCE(fp.cantidad) AS cajas,
         COALESCE(null, 0) AS promedio,
         Coalesce(fp.kilos) AS kilos,
         b.tipo,
         b.rancho,
         b.no_trazabilidad,
         (f.serie || f.folio) AS factura,
         (CASE WHEN f.is_factura = 't' THEN 'Factura' ELSE 'Remisión' END) AS tipo_doc
      FROM bascula AS b
        {$table_ms}
        LEFT JOIN facturacion_otrosdatos AS fo ON fo.no_trazabilidad = b.no_trazabilidad
        LEFT JOIN facturacion AS f ON f.id_factura = fo.id_factura
        LEFT JOIN (
          SELECT id_remision, id_factura, status
          FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
        ) fh ON f.id_factura = fh.id_remision
        LEFT JOIN facturacion_productos AS fp ON f.id_factura = fp.id_factura
        LEFT JOIN clasificaciones AS cl ON cl.id_clasificacion = fp.id_clasificacion
      WHERE
            b.status = true AND COALESCE(fh.id_remision, 0) = 0
            {$sql}
      ORDER BY b.folio ASC
    ");

    $movimientos = $query->result();

    foreach ($movimientos as $key => $caja)
    {
      $data['totales']['kilos'] += floatval($caja->kilos);
      $data['totales']['cajas'] += floatval($caja->cajas);

      if ($caja->tipo == 'en')
        $caja->tipo = 'E';
      elseif ($caja->tipo == 'sa')
        $caja->tipo = 'S';
    }

    $data['movimientos'] = $movimientos;
  }

  public function getDataMovimientosAuditoriaEn(&$data)
  {
    $sql = '';

    $_GET['fechaini'] = $this->input->get('fechaini') != '' ? $_GET['fechaini'] : date('Y-m-01');
    $_GET['fechaend'] = $this->input->get('fechaend') != '' ? $_GET['fechaend'] : date('Y-m-d');
    if ($this->input->get('fechaini') != '' && $this->input->get('fechaend') != '')
    $sql .= " AND DATE(b.fecha_bruto) >= '".$this->input->get('fechaini')."' AND
                  DATE(b.fecha_bruto) <= '".$this->input->get('fechaend')."'";

    $_GET['farea'] = $this->input->get('farea') != '' ? $_GET['farea'] : '1';
    if ($this->input->get('farea') != '')
      $sql .= " AND b.id_area = " . $_GET['farea'];

    if ($this->input->get('fid_proveedor') != ''){
      if($this->input->get('ftipop') == 'sa'){
        $sql .= " AND b.id_cliente = '".$_GET['fid_proveedor']."'";
      }else{
        $sql .= " AND b.id_proveedor = '".$_GET['fid_proveedor']."'";
      }
    }

    if ($this->input->get('fid_empresa') != '') {
      $sql .= " AND b.id_empresa = '".$_GET['fid_empresa']."'";
    }

    if ($this->input->get('prancho') != '') {
      $sql .= " AND Upper(b.rancho) LIKE '".mb_strtoupper($_GET['prancho'], 'UTF-8')."'";
    }

    if ($this->input->get('fstatusp') != '')
      if ($this->input->get('fstatusp') === '1')
        $sql .= " AND b.accion IN ('p', 'b')";
      else
        $sql .= " AND b.accion IN ('en', 'sa')";

    //Filtros del tipo de pesadas
    if ($this->input->get('ftipop') != '')
      $sql .= " AND b.tipo = '{$_GET['ftipop']}'";
    $table_ms = 'LEFT JOIN proveedores p ON p.id_proveedor = b.id_proveedor';
    $tipo_rpt = "Entrada";
    if($this->input->get('ftipop') == 'sa') {
      $table_ms = 'LEFT JOIN clientes c ON c.id_cliente = b.id_cliente';
      $tipo_rpt = "Salida";
    }

    // if ($this->input->get('ftipop') != '')
    //   if ($this->input->get('ftipop') === '1')
    //     $sql .= " AND b.tipo = 'en'";
    //   else
    //     $sql .= " AND b.tipo = 'sa'";

    if (isset($_GET['pe']))
      $sql = " AND b.id_bascula IN (".$_GET['pe'].")";

    $query = $this->db->query(
      "SELECT b.id_bascula,
             b.accion as status,
             b.folio,
             DATE(b.fecha_bruto) as fecha,
             COALESCE(ca.nombre, bp.descripcion) as calidad,
             COALESCE(bc.cajas, bp.cantidad) AS cajas,
             COALESCE(bc.promedio, 0) AS promedio,
             Coalesce(bc.kilos, b.kilos_neto) AS kilos,
             -- COALESCE(bc.precio, bp.precio_unitario) AS precio,
             -- COALESCE(bc.importe, bp.importe) AS importe,
             -- b.importe as importe_todas,
             b.tipo,
             -- pagos.tipo_pago,
             -- pagos.concepto,
             b.id_bonificacion,
             b.rancho,
             COALESCE((SELECT id_pago FROM banco_pagos_bascula WHERE status = 'f' AND id_bascula = b.id_bascula), 0) AS en_pago
      FROM bascula AS b
        LEFT JOIN bascula_compra AS bc ON b.id_bascula = bc.id_bascula
        LEFT JOIN bascula_productos AS bp ON b.id_bascula = bp.id_bascula
        {$table_ms}
        LEFT JOIN calidades AS ca ON ca.id_calidad = bc.id_calidad
        LEFT JOIN (SELECT bpb.id_bascula, bp.tipo_pago, bp.concepto
                  FROM bascula_pagos AS bp
                  INNER JOIN bascula_pagos_basculas AS bpb ON bpb.id_pago = bp.id_pago
                  WHERE bp.status = 't') AS pagos
                  ON pagos.id_bascula = b.id_bascula
      WHERE
            b.status = true
            {$sql}
      ORDER BY b.folio, bc.id_calidad ASC
    ");

    $movimientos = $query->result();

    foreach ($movimientos as $key => $caja)
    {
      // $data['totales']['importe']     += floatval($caja->importe);
      // $data['totales']['total']       += floatval($caja->importe);
      if(!is_numeric($caja->id_bonificacion))
      {
        $data['totales']['kilos']       += floatval($caja->kilos);
        $data['totales']['cajas']       += floatval($caja->cajas);
      }else
        $caja->calidad = 'BONIFICACION';
      // $data['precio_prom'] += floatval($caja->promedio);

      if ($caja->status === 'p' || $caja->status === 'b')
      {
        // $data['totales']['pagados'] += floatval($caja->importe);
        if ($caja->status === 'p')
          $caja->tipo_pago = 'EFECTIVO';
      }else{
        // $data['totales']['no_pagados'] += floatval($caja->importe);
      }

      if ($caja->tipo == 'en')
        $caja->tipo = 'E';
      elseif ($caja->tipo == 'sa')
        $caja->tipo = 'S';
    }

    $data['movimientos'] = $movimientos;
  }

  public function getMovimientosAuditoria()
  {
    $data =  array(
      'movimientos' => array(),
      'area'        => array(),
      'proveedor'   => array(),
    );

    $data['totales'] = array(
        'importe'     => 0,
        'pesada'      => 0,
        'total'       => 0,
        'pagados'     => 0,
        'kilos'       => 0,
        'cajas'       => 0,
        'precio_prom' => 0, // importe / kilos
        'no_pagados'  => 0,
      );

      if($this->input->get('ftipop') == 'sa') {
        $this->getDataMovimientosAuditoriaSa($data);
      }else{
        $this->getDataMovimientosAuditoriaEn($data);
      }


      $this->load->model('areas_model');
      $this->load->model('proveedores_model');
      $this->load->model('clientes_model');

      // Obtiene la informacion del Area filtrada.
      $data['area'] = $this->areas_model->getAreaInfo($_GET['farea']);

      // Obtiene la informacion del proveedor filtrado.
      if ($this->input->get('fid_proveedor') > 0) {
        if($this->input->get('ftipop') == 'sa') {
          $data['proveedor'] = $this->clientes_model->getClienteInfo($_GET['fid_proveedor']);
        }else
          $data['proveedor'] = $this->proveedores_model->getProveedorInfo($_GET['fid_proveedor']);
      }
    // }

    return $data;
  }


  public function rpt_boletas_salida_data()
  {
    $sql = $sql2 = '';

    $_GET['ffecha1'] = $this->input->get('ffecha1') != '' ? $_GET['ffecha1'] : date('Y-m-d');
    $_GET['ffecha2'] = $this->input->get('ffecha2') != '' ? $_GET['ffecha2'] : date('Y-m-d');
    $fecha_compara = 'fecha_tara';

    $this->load->model('areas_model');
    $_GET['farea'] = $this->input->get('farea') != '' ? $_GET['farea'] : $this->areas_model->getAreaDefault();
    if ($this->input->get('farea') != '') {
      $sql .= " AND b.id_area = " . $_GET['farea'];
    }

    if ($this->input->get('fid_proveedor') != ''){
      if($this->input->get('ftipo') == 'sa'){
        $sql .= " AND b.id_cliente = '".$_GET['fid_proveedor']."'";
      }else{
        $sql .= " AND b.id_proveedor = '".$_GET['fid_proveedor']."'";
      }
    }

    if ($this->input->get('fid_empresa') != ''){
      $sql .= " AND b.id_empresa = '".$_GET['fid_empresa']."'";
    }

    $sql .= " AND DATE(b.{$fecha_compara}) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";

    //Filtros del tipo de pesadas
    if ($this->input->get('ftipo') != '')
      $sql .= " AND b.tipo = '{$_GET['ftipo']}'";

    $campos = "p.nombre_fiscal AS proveedor, ";
    $table_ms = 'LEFT JOIN proveedores p ON p.id_proveedor = b.id_proveedor';
    $tipo_rpt = "Entrada";
    if($this->input->get('ftipo') == 'sa') {
      $campos = "c.nombre_fiscal AS proveedor, ";
      $table_ms = 'LEFT JOIN clientes c ON c.id_cliente = b.id_cliente';
      $tipo_rpt = "Salida";
    }

    if ($this->input->get('fid_producto') > 0) {
      $sql2 .= " AND id_clasificacion = {$_GET['fid_producto']}";
    }

    $query = $this->db->query(
      "SELECT b.id_bascula,
        b.total_cajas, b.importe, {$campos}
        b.folio, b.accion, Date(b.{$fecha_compara}) AS fecha,
        b.kilos_bruto, b.kilos_tara, b.kilos_neto,
        Coalesce(op.nombre_fiscal, '') AS productor,
        Coalesce(e.nombre_fiscal, '') AS empresa,
        pro.productos
      FROM bascula AS b
        {$table_ms}
        LEFT JOIN otros.productor op ON op.id_productor = b.id_productor
        LEFT JOIN empresas e ON e.id_empresa = b.id_empresa
        ".(empty($sql2)? 'LEFT': 'INNER')." JOIN (
          SELECT id_bascula, String_agg(('Cajas: ' || cantidad || ' | Producto: ' || descripcion), ',&') AS productos
          FROM bascula_productos
          WHERE 1 = 1 {$sql2}
          GROUP BY id_bascula
        ) pro ON pro.id_bascula = b.id_bascula
      WHERE b.status = true
        {$sql}
      ORDER BY b.folio ASC
      "
    );

    $this->load->model('areas_model');

    // Obtiene la informacion del Area filtrada.
    $area = $this->areas_model->getAreaInfo($_GET['farea']);

    $rsb = array();
    if ($query->num_rows() > 0)
    {
      $rsb = $query->result();
    }

    return array('area' => $area, 'data' => $rsb, 'tipo' => $tipo_rpt);
  }
  /**
  * Visualiza/Descarga el PDF para el Reporte boletas pagadas.
  *
  * @return void
  */
  public function rpt_boletas_salida_pdf()
  {
    // Obtiene los datos del reporte.
    $data = $this->rpt_boletas_salida_data();

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $area = $data['area'];
    // echo "<pre>";
    //   var_dump($area);
    // echo "</pre>";exit;

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if (isset($_GET['fid_empresa']) && $_GET['fid_empresa'] !== '')
    {
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('fid_empresa'));

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;
      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    }

    $pdf->titulo2 = "REPORTE SALIDAS DE BOLETAS <".(isset($area['info'])? $area['info']->nombre: '')."> <".(isset($data['tipo'])? $data['tipo']: '').'>';
    $prov_produc = $this->input->get('fproveedor').($this->input->get('fproveedor')!=''? " | ": '').$this->input->get('fproducto');
    $pdf->titulo3 = $fecha->format('d/m/Y')." Al ".$fecha2->format('d/m/Y')." | ".$prov_produc.' | '.$this->input->get('fempresa');

    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);

    $aligns = array('C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C');
    $aligns1 = array('L', 'R', 'R', 'R', 'L', 'L', 'L', 'R', 'R', 'R');
    $widths = array(18, 20, 25, 25, 40, 40, 40);
    $header = array('FECHA', 'BOLETA', 'IMPORTE', 'KG', 'EMPRESA', 'CLIENTE', 'PRODUCTOS');

    $kgt    = 0;
    $importe  = 0;
    foreach ($data['data'] as $key => $caja) {
      if($pdf->GetY()+15 >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      {
        if($key != 0)
          $pdf->AddPage();

        $pdf->SetFont('helvetica','B', 8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, false);
      }

      $pdf->SetFont('helvetica','',8);
      $pdf->SetTextColor(0,0,0);

      $kgt += $caja->kilos_neto;
      $importe += $caja->importe;

      $datos = array(
        MyString::fechaAT($caja->fecha),
        $caja->folio,
        MyString::formatoNumero($caja->importe, 2, '$', false),
        MyString::formatoNumero($caja->kilos_neto, 2, '', false),
        substr($caja->empresa, 0, 28),
        substr($caja->proveedor, 0, 28),
        str_replace(',&', "\n", $caja->productos),
      );

      $pdf->SetY($pdf->GetY());
      $pdf->SetX(6);
      $pdf->SetAligns($aligns1);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);

      if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
      {
        $pdf->AddPage();
      }
    }

    $pdf->SetFont('helvetica','B',8);
    $pdf->SetY($pdf->GetY()-1);
    $pdf->SetX(6);
    $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(38, 25, 25));
    $pdf->Row(array(
      'TOTALES',
      MyString::formatoNumero($importe, 2, '$', false),
      MyString::formatoNumero($kgt, 2, '', false),
    ), false, false);

    $pdf->Output('salidas_boletas.pdf', 'I');
  }

  public function rpt_boletas_salida_xls()
  {
    // Obtiene los datos del reporte.
    $data = $this->rpt_boletas_salida_data();

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $area = $data['area'];

    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=salidas_boletas.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa((isset($_GET['fid_empresa']{0})? $_GET['fid_empresa']: 2));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "REPORTE SALIDAS DE BOLETAS <".$area['info']->nombre."> <".(isset($data['tipo'])? $data['tipo']: '').'>';
    $prov_produc = $this->input->get('fproveedor').($this->input->get('fproveedor')!=''? " | ": '').$this->input->get('fproducto');
    $titulo3 = $fecha->format('d/m/Y')." Al ".$fecha2->format('d/m/Y')." | ".$prov_produc.' | '.$this->input->get('fempresa');


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="7" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="7" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="7" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="7"></td>
        </tr>';
      $html .= '<tr style="font-weight:bold">
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">FECHA</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">BOLETA</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">IMPORTE</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">KG</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">EMPRESA</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">CLIENTE</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">PRODUCTOS</td>
      </tr>';
    $kgt    = 0;
    $importe  = 0;
    foreach ($data['data'] as $key => $caja) {
      $kgt += $caja->kilos_neto;
      $importe += $caja->importe;

      $html .= '<tr>
        <td style="width:150px;border:1px solid #000;">'.$caja->fecha.'</td>
        <td style="width:150px;border:1px solid #000;">'.$caja->folio.'</td>
        <td style="width:400px;border:1px solid #000;">'.$caja->importe.'</td>
        <td style="width:150px;border:1px solid #000;">'.$caja->kilos_neto.'</td>
        <td style="width:150px;border:1px solid #000;">'.$caja->empresa.'</td>
        <td style="width:150px;border:1px solid #000;">'.$caja->proveedor.'</td>
        <td style="width:150px;border:1px solid #000;">'.str_replace(',&', "\n", $caja->productos).'</td>
      </tr>';
    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="2">TOTALES</td>
          <td style="border:1px solid #000;">'.$kgt.'</td>
          <td style="border:1px solid #000;">'.$importe.'</td>
          <td colspan="3"></td>
        </tr>
        <tr>
          <td colspan="7"></td>
        </tr>';

    $html .= '
      </tbody>
    </table>';

    echo $html;
  }

  public function rpt_boletas_porpagar_data()
  {
    $sql = $sql2 = '';

    $_GET['ffecha1'] = $this->input->get('ffecha1') != '' ? $_GET['ffecha1'] : date('Y-m-d');
    $_GET['ffecha2'] = $this->input->get('ffecha2') != '' ? $_GET['ffecha2'] : date('Y-m-d');
    $fecha_compara = 'fecha_tara';

    $this->load->model('areas_model');
    $_GET['farea'] = $this->input->get('farea') != '' ? $_GET['farea'] : $this->areas_model->getAreaDefault();
    if ($this->input->get('farea') != ''){
      $sql .= " AND b.id_area = " . $_GET['farea'];
    }

    if ($this->input->get('fid_empresa') != ''){
      $sql .= " AND b.id_empresa = '".$_GET['fid_empresa']."'";
    }

    if ($this->input->get('fstatus') === 'pb') { // pagados
      $sql .= " AND b.accion In('p', 'b')";
    } else {  // pendientes -> se
      $sql .= " AND b.accion = 'sa'";
    }

    $sql .= " AND DATE(b.{$fecha_compara}) <= '".$_GET['ffecha1']."'";
    $sql .= " AND b.intangible 'f'";


    $query = $this->db->query(
      "SELECT p.id_proveedor, p.nombre_fiscal AS proveedor, e.nombre_fiscal AS empresa, sal.importe
      FROM proveedores p
        INNER JOIN (
          SELECT b.id_proveedor, Sum(b.importe) AS importe
          FROM bascula AS b
          WHERE b.status = true AND b.id_bonificacion IS NULL
            AND b.tipo = 'en' {$sql}
          GROUP BY b.id_proveedor
        ) sal ON p.id_proveedor = sal.id_proveedor
        INNER JOIN empresas e ON e.id_empresa = p.id_empresa
      ORDER BY empresa ASC, proveedor ASC
      ");

    $this->load->model('areas_model');

    // Obtiene la informacion del Area filtrada.
    $area = $this->areas_model->getAreaInfo($_GET['farea']);

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data = $query->result();
    }

    return array('data' => $data, 'area' => $area);
  }
  public function rpt_boletas_porpagar_pdf()
  {
    // Obtiene los datos del reporte.
    $data = $this->rpt_boletas_porpagar_data();

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $area = $data['area'];
    // echo "<pre>";
    //   var_dump($area);
    // echo "</pre>";exit;

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if (isset($_GET['fid_empresa']) && $_GET['fid_empresa'] !== '')
    {
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('fid_empresa'));

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;
      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    }

    $pdf->titulo2 = "REPORTE BOLETAS POR PAGAR <".(isset($area['info'])? $area['info']->nombre: '').">";
    $prov_produc = $this->input->get('fproveedor').($this->input->get('fproveedor')!=''? " | ": '').$this->input->get('fproductor');
    $pdf->titulo3 = "Hasta ".$fecha->format('d/m/Y')." | ".$this->input->get('fempresa');

    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);

    $aligns = array('L', 'L', 'R', 'C', 'C', 'C', 'C', 'C', 'C');
    $widths = array(80, 95, 30);
    $header = array('EMPRESA', 'PROVEEDOR', 'IMPORTE');

    $importe = 0;
    foreach($data['data'] as $key => $caja)
    {
      if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      {
        if($key != 0)
          $pdf->AddPage();

        $pdf->SetFont('helvetica','B', 8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, false);
      }

      $pdf->SetFont('helvetica','',8);
      $pdf->SetTextColor(0,0,0);

      $importe  += $caja->importe;

      $datos = array(
        $caja->empresa,
        $caja->proveedor,
        MyString::formatoNumero($caja->importe, 2, '$', false)
      );

      $pdf->SetY($pdf->GetY());
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);

    }

    if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
    {
      $pdf->AddPage();
    }

    $pdf->SetFont('helvetica','B',8);
    $pdf->SetY($pdf->GetY()-1);
    $pdf->SetX(6);
    $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(175, 30, 30, 25, 17, 25));
    $pdf->Row(array(
      'TOTALES',
      MyString::formatoNumero($importe, 2, '$', false)
    ), false, false);

    $pdf->Output('reporte_boletas_porpagar.pdf', 'I');
  }

  public function rpt_boletas_porpagar_xls()
  {
    // Obtiene los datos del reporte.
    $data = $this->rpt_boletas_porpagar_data();

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;
    $area = $data['area'];

    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_boletas_porpagar.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa((isset($_GET['fid_empresa']{0})? $_GET['fid_empresa']: 2));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "REPORTE BOLETAS POR PAGAR <".$area['info']->nombre.">";
    $titulo3 = "Hasta ".$fecha->format('d/m/Y')." | ".$this->input->get('fempresa');


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="3" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="3" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="3" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="3"></td>
        </tr>';
      $html .= '<tr style="font-weight:bold">
        <td style="width:250px;border:1px solid #000;background-color: #cccccc;">EMPRESA</td>
        <td style="width:250px;border:1px solid #000;background-color: #cccccc;">PROVEEDOR</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">IMPORTE</td>
      </tr>';

    $importe = 0;
    foreach($data['data'] as $key => $caja)
    {
      $importe  += $caja->importe;

      $html .= '<tr>
        <td style="width:250px;border:1px solid #000;">'.$caja->empresa.'</td>
        <td style="width:250px;border:1px solid #000;">'.$caja->proveedor.'</td>
        <td style="width:150px;border:1px solid #000;">'.$caja->importe.'</td>
      </tr>';

    }

    $html .= '
      <tr style="font-weight:bold">
        <td colspan="2">TOTALES</td>
        <td style="border:1px solid #000;">'.$importe.'</td>
      </tr>
      <tr>
        <td colspan="3"></td>
      </tr>';

    $html .= '
      </tbody>
    </table>';

    echo $html;
  }

}
/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */