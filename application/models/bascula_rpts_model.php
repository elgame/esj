<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bascula_rpts_model extends Bascula_model {

  function __construct()
  {
    parent::__construct();
  }

  public function getBonificaciones()
  {
    $data =  array(
      'movimientos' => array(),
      'totales'   => array(
        'importe'     => 0,
        'pagados'     => 0,
        'kilos'       => 0,
        'cajas'       => 0,
        'no_pagados'  => 0,
      ),
    );

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
      $sql .= " AND p.id_proveedor = '".$_GET['fid_proveedor']."'";
    }

    if ($this->input->get('fstatusp') != '')
      if ($this->input->get('fstatusp') === '1')
        $sql .= " AND b.accion IN ('p', 'b')";
      else
        $sql .= " AND b.accion IN ('en', 'sa')";

    //Filtros del tipo de pesadas
    if ($this->input->get('ftipop') != '')
      $sql .= " AND b.tipo = 'en'";
    $tipo_rpt = "Entrada";

    $query = $this->db->query(
      "SELECT b.id_bascula,
             b.accion as status,
             b.folio,
             DATE(b.fecha_bruto) as fecha,
             b.total_cajas AS cajas,
             b.kilos_neto AS kilos,
             (b.importe/ (CASE b.kilos_neto WHEN 0 THEN 1 ELSE b.kilos_neto END))::numeric(100,2) AS precio,
             b.importe,
             b.tipo,
             pagos.tipo_pago,
             pagos.concepto,
             p.nombre_fiscal
      FROM bascula AS b
        LEFT JOIN bascula_compra AS bc ON b.id_bascula = bc.id_bascula
        LEFT JOIN proveedores p ON p.id_proveedor = b.id_proveedor
        LEFT JOIN (SELECT bpb.id_bascula, bp.tipo_pago, bp.concepto
                  FROM bascula_pagos AS bp
                  INNER JOIN bascula_pagos_basculas AS bpb ON bpb.id_pago = bp.id_pago) AS pagos
                  ON pagos.id_bascula = b.id_bascula
      WHERE
            b.status = true AND b.id_bonificacion IS NOT NULL
            {$sql}
      ORDER BY (b.folio, b.fecha_bruto) ASC
    ");

    $movimientos = $query->result();

    foreach ($movimientos as $key => $caja)
    {
      $data['totales']['importe']     += floatval($caja->importe);
      $data['totales']['kilos']       += floatval($caja->kilos);
      $data['totales']['cajas']       += floatval($caja->cajas);

      if ($caja->status === 'p' || $caja->status === 'b')
      {
        $data['totales']['pagados'] += floatval($caja->importe);
        if ($caja->status === 'p')
          $caja->tipo_pago = 'EFECTIVO';
      }else
        $data['totales']['no_pagados'] += floatval($caja->importe);

      if ($caja->tipo == 'en')
        $caja->tipo = 'E';
      elseif ($caja->tipo == 'sa')
        $caja->tipo = 'S';
    }


    $this->load->model('areas_model');
    $this->load->model('proveedores_model');
    $this->load->model('clientes_model');

    // Obtiene la informacion del Area filtrada.
    $data['area'] = $this->areas_model->getAreaInfo($_GET['farea']);

    // Obtiene la informacion del proveedor filtrado.
    if ($this->input->get('fid_proveedor') != '')
      $data['proveedor'] = $this->proveedores_model->getProveedorInfo($_GET['fid_proveedor']);

    $data['movimientos'] = $movimientos;

    return $data;
  }

  /**
    * Visualiza/Descarga el PDF para el Reporte Diario de Entradas.
    *
    * @return void
    */
   public function bonificaciones_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->getBonificaciones();

      // echo "<pre>";
      //   var_dump($data['totales']);
      // echo "</pre>";exit;

      $rmc = $data['movimientos'];

      $area = $data['area'];

      $fechaini = new DateTime($_GET['fechaini']);
      $fechaend = new DateTime($_GET['fechaend']);


      $tipo = "ENTRADAS/SALIDAS";
      if ($this->input->get('ftipop') != '')
        if ($this->input->get('ftipop') === '1')
          $tipo = "ENTRADAS";
        else
          $tipo = "SALIDAS";

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "BONIFICACIONES - {$tipo} <".$area['info']->nombre."> DEL DIA " . $fechaini->format('d/m/Y') . " AL " . $fechaend->format('d/m/Y');
      if (isset($data['proveedor']))
        $pdf->titulo3 = strtoupper($data['proveedor']['info']->nombre_fiscal) . " (CTA: " .$data['proveedor']['info']->cuenta_cpi . ")";
      $pdf->titulo3 .= " \n FECHA/HORA DEL REPORTE: " . date('d/m/Y H:i:s');

      $pdf->noShowPages = false;
      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'L', 'R', 'R', 'R', 'R', 'C', 'C');
      $widths = array(14, 17, 47, 13, 15, 13, 20, 30, 35);
      $header = array('BOLETA', 'FECHA','PROVEEDOR',
                      'CAJS', 'KILOS', 'PRECIO', 'IMPORTE', 'TIPO PAGO', 'CONCEPTO');

      foreach($rmc as $key => $caja)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetY($pdf->GetY()-1);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, false);
        }

        $pdf->SetFont('helvetica','',8);
        $pdf->SetTextColor(0,0,0);

        $datos = array(
                       $caja->folio,
                       $caja->fecha,
                       substr($caja->nombre_fiscal, 0, 22),
                       MyString::formatoNumero($caja->cajas, 2, ''),
                       MyString::formatoNumero($caja->kilos, 2, ''),
                       MyString::formatoNumero($caja->precio, 2, '', false),
                       MyString::formatoNumero($caja->importe, 2, '', false),
                       strtoupper($caja->tipo_pago),
                       $caja->concepto,
                      );

        $pdf->SetY($pdf->GetY()-1);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false, false);
      }

      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $prom_total = floatval($data['totales']['kilos'])/(floatval($data['totales']['cajas'])>0? floatval($data['totales']['cajas']): 1);
      $pdf->Row(array('', '', '',
        MyString::formatoNumero($data['totales']['cajas'], 2, ''),
        MyString::formatoNumero($data['totales']['kilos'], 2, ''),
        '',
        MyString::formatoNumero($data['totales']['importe']),
        '',''
      ), false, false);

      if($pdf->GetY()+20 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetY($pdf->GetY() + 6);
      $pdf->SetX(6);
      $pdf->SetAligns(array('C', 'C', 'C'));
      $pdf->SetWidths(array(66, 66, 66));
      $pdf->Row(array(
        'PAGADO',
        'NO PAGADO',
        'TOTAL IMPORTE',), false);

      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(6);
      $pdf->SetAligns(array('C', 'C', 'C'));
      $pdf->SetWidths(array(66, 66, 66));
      $pdf->Row(array(
        MyString::formatoNumero($data['totales']['pagados']),
        MyString::formatoNumero($data['totales']['no_pagados']),
        MyString::formatoNumero($data['totales']['importe'])
      ), false);

      $pdf->Output('reporte_bonificaciones.pdf', 'I');
  }

  public function bonificaciones_xls()
  {
    // Obtiene los datos del reporte.
    $data = $this->getBonificaciones();

    // echo "<pre>";
    //   var_dump($data['totales']);
    // echo "</pre>";exit;

    $rmc = $data['movimientos'];

    $area = $data['area'];

    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=REPORTE_DIARIO_ENTRADAS_{$area['info']->nombre}.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $fechaini = new DateTime($_GET['fechaini']);
    $fechaend = new DateTime($_GET['fechaend']);

    $tipo = "ENTRADAS/SALIDAS";
    if ($this->input->get('ftipop') != '')
      if ($this->input->get('ftipop') === '1')
        $tipo = "ENTRADAS";
      else
        $tipo = "SALIDAS";

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "BONIFICACIONES - {$tipo} <".$area['info']->nombre."> DEL DIA " . $fechaini->format('d/m/Y') . " AL " . $fechaend->format('d/m/Y');
    $titulo3 = '';
    if (isset($data['proveedor']))
      $titulo3 = strtoupper($data['proveedor']['info']->nombre_fiscal) . " (CTA: " .$data['proveedor']['info']->cuenta_cpi . ")";
    $titulo3 .= " \n FECHA/HORA DEL REPORTE: " . date('d/m/Y H:i:s');


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="9" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="9" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="9" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="9"></td>
        </tr>';
      $html .= '<tr style="font-weight:bold">
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">BOLETA</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">FECHA</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">PROVEEDOR</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">CAJS</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">KILOS</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">PRECIO</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">IMPORTE</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">TIPO PAGO</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">CONCEPTO</td>
      </tr>';
    foreach($rmc as $key => $caja)
    {
      $html .= '<tr>
          <td style="width:150px;border:1px solid #000;">'.$caja->folio.'</td>
          <td style="width:150px;border:1px solid #000;">'.$caja->fecha.'</td>
          <td style="width:400px;border:1px solid #000;">'.$caja->nombre_fiscal.'</td>
          <td style="width:150px;border:1px solid #000;">'.$caja->cajas.'</td>
          <td style="width:150px;border:1px solid #000;">'.$caja->kilos.'</td>
          <td style="width:150px;border:1px solid #000;">'.$caja->precio.'</td>
          <td style="width:150px;border:1px solid #000;">'.$caja->importe.'</td>
          <td style="width:150px;border:1px solid #000;">'.$caja->tipo_pago.'</td>
          <td style="width:400px;border:1px solid #000;">'.$caja->concepto.'</td>
        </tr>';

    }

    $prom_total = floatval($data['totales']['kilos'])/(floatval($data['totales']['cajas'])>0? floatval($data['totales']['cajas']): 1);
    $html .= '
      <tr style="font-weight:bold">
        <td colspan="3"></td>
        <td style="border:1px solid #000;">'.$data['totales']['cajas'].'</td>
        <td style="border:1px solid #000;">'.$data['totales']['kilos'].'</td>
        <td></td>
        <td style="border:1px solid #000;">'.$data['totales']['importe'].'</td>
        <td colspan="2"></td>
      </tr>
      <tr>
        <td colspan="9"></td>
      </tr>';

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="2" style="border:1px solid #000;">PAGADO</td>
          <td style="border:1px solid #000;">NO PAGADO</td>
          <td colspan="2" style="border:1px solid #000;">TOTAL IMPORTE</td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="2" style="border:1px solid #000;">'.$data['totales']['pagados'].'</td>
          <td style="border:1px solid #000;">'.$data['totales']['no_pagados'].'</td>
          <td colspan="2" style="border:1px solid #000;">'.$data['totales']['importe'].'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }


}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */