<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class rastreabilidad_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }


  /*
   |-------------------------------------------------------------------------
   |  REPORTES
   |-------------------------------------------------------------------------
  */
  /**
   * REPORTE DE RASTREABILIDAD DE PRODUCTOS
   * @return [type] [description]
   */
  public function rrp_data()
   {
      $response = array('data' => array(), 'calidad' => '', 'tipo' => 'Entrada');
      $sql = '';

      if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
        $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
        $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
      }
      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
        $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
        $sql .= " AND Date(b.fecha_tara) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      }
      //Filtros de calidad
      if ($this->input->get('fcalidad') != ''){
        $sql .= " AND bc.id_calidad = " . $_GET['fcalidad'];

        // Obtiene la informacion del Area filtrada.
        $this->load->model('calidades_model');
        $response['calidad'] = $this->calidades_model->getCalidadInfo($_GET['fcalidad']);
      }else
        $sql .= " AND bc.id_calidad = 0";

      $query = $this->db->query(
        "SELECT b.id_bascula, 
                b.folio, 
                b.no_lote, 
                b.fecha_tara, 
                b.chofer_es_productor, 
                p.nombre_fiscal, 
                c.nombre, 
                Sum(bc.cajas) AS cajas, 
                Sum(bc.kilos) AS kilos
        FROM bascula AS b
          INNER JOIN bascula_compra AS bc ON bc.id_bascula = b.id_bascula
          INNER JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor
          LEFT JOIN choferes AS c ON c.id_chofer = b.id_chofer
        WHERE b.status = true AND b.tipo = 'en' AND b.accion IN('sa', 'p', 'b')
          {$sql}
        GROUP BY b.id_bascula, b.folio, b.no_lote, b.fecha_tara, b.chofer_es_productor, p.nombre_fiscal, c.nombre
        ORDER BY b.no_lote ASC, b.folio ASC
        "
      );
      if($query->num_rows() > 0)
        $response['data'] = $query->result();


      return $response;
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Rastreabilidad de productos
    *
    * @return void
    */
   public function rrp_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->rrp_data();

      if(isset($data['calidad']['info']->nombre)){
        $calidad_nombre = $data['calidad']['info']->nombre;
      }else
        $calidad_nombre = '';

      $fecha = new DateTime($_GET['ffecha1']);
      $fecha2 = new DateTime($_GET['ffecha2']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "REPORTE RASTREABILIDAD DEL PRODUCTO <{$calidad_nombre}>";
      $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} AL {$fecha2->format('d/m/Y')}\n";
      $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
      $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'L', 'L', 'R', 'R');
      $widths = array(12, 20, 68, 70, 15, 20);
      $header = array('LOTE', 'BOLETA', 'PRODUCTOR','FACTURADOR', 'CAJAS', 'KGS');

      $total_kilos = 0;
      $total_cajas = 0;
      $kilos_lote  = 0;
      $cajas_lote  = 0;
      $num_lote    = -1;

      foreach($data['data'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);

          if($key==0)
            $num_lote = $boleta->no_lote;
        }

        if($num_lote != $boleta->no_lote){
          $pdf->SetFont('helvetica','B',8);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row(array(
              '', '', '', '',
              String::formatoNumero($cajas_lote, 2, ''),
              String::formatoNumero($kilos_lote, 2, ''),
            ), true);
          $cajas_lote = 0;
          $kilos_lote = 0;
          $num_lote = $boleta->no_lote;
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
          $pdf->Row(array(
              $boleta->no_lote,
              $boleta->folio,
              ($boleta->chofer_es_productor=='t'? $boleta->nombre: ''),
              $boleta->nombre_fiscal,
              String::formatoNumero($boleta->cajas, 2, ''),
              String::formatoNumero($boleta->kilos, 2, ''),
            ), false);
        $cajas_lote  += $boleta->cajas;
        $kilos_lote  += $boleta->kilos;
        $total_cajas += $boleta->cajas;
        $total_kilos += $boleta->kilos;
      }
      //Total del ultimo lote
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
          '', '', '', '',
          String::formatoNumero($cajas_lote, 2, ''),
          String::formatoNumero($kilos_lote, 2, ''),
        ), true);

      //total general
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
        $pdf->Row(array(
            '', '', '', 'TOTAL',
            String::formatoNumero($total_cajas, 2, ''),
            String::formatoNumero($total_kilos, 2, ''),
          ), false, false);


      $pdf->Output('reporte_rastreabilidad_'.$fecha->format('d/m/Y').'.pdf', 'I');
   }

   /**
   * REPORTE DE RASTREABILIDAD DE PRODUCTOS
   * @return [type] [description]
   */
  public function ref_data()
   {
      $response = array('data' => array(), 'calidad' => '', 'tipo' => 'Entrada');
      $sql = '';

      if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
        $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
        $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
      }
      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
        $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
        $sql .= " AND Date(b.fecha_tara) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      }
      //Filtros de area
      if ($this->input->get('farea') != ''){
        $sql .= " AND b.id_area = " . $_GET['farea'];
      }else
        $sql .= " AND b.id_area = 0";

      $query = $this->db->query(
        "SELECT b.id_bascula, 
          bc.id_calidad,
          c.nombre,
          b.folio, 
          b.no_lote, 
          b.fecha_tara, 
          p.nombre_fiscal,
          Sum(bc.cajas) AS cajas,
          Sum(bc.kilos) AS kilos
        FROM bascula AS b
          INNER JOIN bascula_compra as bc ON bc.id_bascula = b.id_bascula
          INNER JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor 
          INNER JOIN calidades AS c ON bc.id_calidad = c.id_calidad
        WHERE b.status = true AND b.tipo = 'en' AND b.accion IN('sa', 'p', 'b')
          {$sql}
        GROUP BY b.id_bascula, bc.id_calidad, c.nombre, b.folio, b.no_lote, b.fecha_tara, p.nombre_fiscal, bc.num_registro
        ORDER BY no_lote ASC, folio ASC, num_registro ASC
        "
      );
      if($query->num_rows() > 0){
        $response['data'] = $query->result();
        $query->free_result();
      }


      return $response;
   }

   /**
    * Visualiza/Descarga el PDF para el Reporte Rastreabilidad de productos
    *
    * @return void
    */
   public function ref_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->ref_data();

      if(isset($data['calidad']['info']->nombre)){
        $calidad_nombre = $data['calidad']['info']->nombre;
      }else
        $calidad_nombre = '';

      $fecha = new DateTime($_GET['ffecha1']);
      $fecha2 = new DateTime($_GET['ffecha2']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "REPORTE RASTREABILIDAD DEL PRODUCTO <{$calidad_nombre}>";
      $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} AL {$fecha2->format('d/m/Y')}\n";
      $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
      $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

      $pdf->AliasNbPages();
      //$pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'L', 'L', 'R', 'R');
      $widths = array(12, 20, 68, 70, 15, 20);
      $header = array('LOTE', 'BOLETA', 'PRODUCTOR','CALIDAD', 'CAJAS', 'KGS');

      $total_kilos = 0;
      $total_cajas = array();
      $num_lote    = -1;

      foreach($data['data'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
          $pdf->Row(array(
              ($num_lote != $boleta->no_lote? $boleta->no_lote: ''),
              $boleta->folio,
              $boleta->nombre_fiscal,
              $boleta->nombre,
              String::formatoNumero($boleta->cajas, 2, ''),
              String::formatoNumero($boleta->kilos, 2, ''),
            ), false);

        if($num_lote != $boleta->no_lote){
          $num_lote = $boleta->no_lote;
        }

        if(array_key_exists($boleta->id_calidad, $total_cajas)){
          $total_cajas[$boleta->id_calidad]['cajas'] += $boleta->cajas;
          $total_cajas[$boleta->id_calidad]['kilos'] += $boleta->kilos;
        }else{
          $total_cajas[$boleta->id_calidad] = array('cajas' => $boleta->cajas, 'kilos' => $boleta->kilos, 'nombre' => $boleta->nombre);
        }
      }

      //total general
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetAligns(array('L', 'R', 'R'));
      $pdf->SetWidths(array(40, 20, 20));

      $pdf->SetX(6);
      $pdf->Row(array(
          'CALIDAD', 'CAJAS', 'KILOS',
        ), false, false);
      foreach ($total_cajas as $key => $value) {
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetX(6);
        $pdf->Row(array(
            $value['nombre'], 
            String::formatoNumero($value['cajas'], 2, ''),
            String::formatoNumero($value['kilos'], 2, ''),
          ), false, false);
      }


      $pdf->Output('reporte_rastreabilidad_'.$fecha->format('d/m/Y').'.pdf', 'I');
   }


}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */