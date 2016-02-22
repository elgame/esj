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
                           String::formatoNumero($caja->promedio, 2, '', false),
                           $caja->cajas,
                           $caja->kilos,
                           String::formatoNumero($caja->precio, 2, '$', false),
                           String::formatoNumero($caja->importe, 2, '$', false));

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
            String::formatoNumero($kilos/$cajas, 2, '', false),
            $cajas,
            $kilos,
            String::formatoNumero($importe/$kilos, 2, '$', false),
            String::formatoNumero($importe, 2, '$', false)), false, false);

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
          String::formatoNumero($totalPagado, 2, '$', false),
          String::formatoNumero($totalNoPagado, 2, '$', false),
          String::formatoNumero($row['cancelados'], 2, '$', false),
          String::formatoNumero($totalImporte, 2, '$', false)), false);

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
          String::formatoNumero($gtotalPagado, 2, '$', false),
          String::formatoNumero($gtotalNoPagado, 2, '$', false),
          String::formatoNumero($gtotalCancelado, 2, '$', false),
          String::formatoNumero($gtotalImporte, 2, '$', false)), false);
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


/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */