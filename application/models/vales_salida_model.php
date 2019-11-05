<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class vales_salida_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
		$this->load->model('bitacora_model');
	}

	// publi

 	/**
 	 * Agrega un proveedor a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addVale($data)
	{
		$data = array(
          'tipo'        => $data['tipo'],
          'id_remision' => (isset($data['id_remision']) && $data['id_remision']!='')? $data['id_remision']: null,
          'folio'       => $this->getFolio(),
          'id_autoriza' => $this->session->userdata('id_usuario'),
					);

		$this->db->insert('otros.vales_salida', $data);
    $id_vale = $this->db->insert_id('otros.vales_salida_id_vale_salida_seq');

		return $this->getValeInfo($id_vale);
	}

  public function getFolio()
  {
    $result = $this->db->query("SELECT folio FROM otros.vales_salida ORDER BY id_vale_salida DESC LIMIT 1")->row();
    if (isset($result->folio)) {
      $folio = $result->folio+1;
    } else
      $folio = 1;
    return $folio;
  }

 //  public function addSalida($id_control, $data=NULL)
 //  {

 //    if ($data==NULL)
 //    {
 //      $data = array(
 //            'id_usuario_sal' => $this->input->post('id_usuario_sal'),
 //            'id_vale_salida' => ($this->input->post('id_vale_salida')!=''? $this->input->post('id_vale_salida'): null),
 //            'fecha_salida'   => date("Y-m-d H:i:s"),
 //            );
 //    }
 //    $this->db->update('otros.control_acceso', $data, "id_control = {$id_control}");

 //    return array('error' => FALSE);
 //  }

	// /**
	//  * Modificar la informacion de un proveedor
	//  * @param  [type] $id_cliente [description]
	//  * @param  [type] $data       [description]
	//  * @return [type]             [description]
	//  */
	// public function updateRegistro($id_control, $data=NULL)
	// {

	// 	if ($data==NULL)
	// 	{
	// 		$data = array(
 //            'id_usaurio_ent' => $this->input->post('id_usaurio_ent'),
 //            'id_usuario_sal' => $this->input->post('id_usuario_sal'),
 //            'id_vale_salida' => $this->input->post('id_vale_salida'),
 //            'nombre'         => $this->input->post('nombre'),
 //            'asunto'         => $this->input->post('asunto'),
 //            'departamento'   => $this->input->post('departamento'),
 //            'placas'         => $this->input->post('placas'),
	// 					);
	// 	}

	// 	$this->db->update('otros.control_acceso', $data, array('id_control' => $id_control));

	// 	return array('error' => FALSE);
	// }

	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_cliente [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getValeInfo($id_vale_salida, $basic=true)
	{
		$result = $this->db->query("SELECT * FROM otros.vales_salida
          WHERE id_vale_salida = {$id_vale_salida}");
		$data = $result->row();

    if (!$basic) {
      if ($data->tipo == 'venta') {
        $this->load->model('facturacion_model');
        $this->load->model('documentos_model');
        $data->factura = $this->facturacion_model->getInfoFactura($data->id_remision);
        $data->documentos = $this->documentos_model->getClienteDocs($data->id_remision);
      }
    }

		return $data;
	}

	public function imprimir($id_vale, $path = null)
  {
    // include(APPPATH.'libraries/phpqrcode/qrlib.php');

    $vale = $this->getValeInfo($id_vale, false);

    // echo "<pre>";
    //   var_dump($vale);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    $this->load->library('barcode');

    $barcode = 'application/media/temp/code.gif';
    $this->barcode->make($vale->id_vale_salida, 1, $barcode, 100, 40, false);

    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;

    $pdf->AliasNbPages();
    $pdf->AddPage();

    //////////
    // Logo //
    //////////

    $pdf->SetXY(0, 0);
    // $pdf->SetXY(30, 2);
    $logo = 'application/images/logo2.png' ;
    $pdf->Image($logo, 10, null, 0, 21);
    $pdf->Image($barcode, 10, $pdf->GetY()-2, 0, 20);

    // $pdf->SetFont('Arial','B', 70);
    // $pdf->SetTextColor(160,160,160);
    // $pdf->RotatedText(65, 120, ($factura['info']->no_impresiones==0? 'ORIGINAL': 'COPIA #'.$factura['info']->no_impresiones), 45);

     $pdf->SetFont('helvetica','B', 25);
    // $pdf->SetFillColor(0, 171, 72);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, 5);
    $pdf->Cell(108, 15, "VALE DE SALIDA", 0, 0, 'C', 0);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(109, $pdf->GetY()+10);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, "Fecha y hora de impresión:", 0, 0, 'R', 1);

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, MyString::fechaATexto(date("Y-m-d")).' '.date("H:i:s"), 0, 0, 'R', 0);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $y_aux = $pdf->GetY();
    $pdf->Cell(108, 4, "Folio Vale:", 0, 0, 'R', 1);

    if ($vale->tipo == 'venta') {
      $pdf->SetXY(109, $pdf->GetY());
      $pdf->Cell(50, 4, ($vale->factura['info']->is_factura=='t'? 'Folio Factura': 'Folio Remision'), 0, 0, 'L', 1);
    }
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, $vale->folio.'  ' , 0, 0, 'R', 0);
    if ($vale->tipo == 'venta') {
      $pdf->SetXY(109, $pdf->GetY());
      $pdf->Cell(50, 4, $vale->factura['info']->serie.$vale->factura['info']->folio, 0, 0, 'L', 0);
    }


    if ($vale->tipo == 'venta') {
      ///////////////
      // Productos //
      ///////////////

      $pdf->SetFillColor(0, 171, 72);
      $pdf->SetXY(0, $pdf->GetY() + 5);
      $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

      $pdf->SetXY(0, $pdf->GetY());
      $aligns = array('C', 'C', 'C');
      $aligns2 = array('C', 'C', 'C');
      $widths = array(40, 55, 121);
      $header = array('Cantidad', 'Unidad de Medida', 'Descripcion');

      $conceptos = $vale->factura['productos'];

      $traslado11 = 0;
      $traslado16 = 0;

      $pdf->limiteY = 250;

      $pdf->setY($pdf->GetY() + 1);
      $hay_prod_certificados = false;
      $gastos = array();
      $bultoss = 0;
      foreach($conceptos as $key => $item)
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

        $printRow = true;
        if ( GastosProductos::searchGastosProductos($item->id_clasificacion) ){
          if($vale->factura['info']->sin_costo_nover == 'f')
          {
            $printRow = false;
            $gastos[] = $item;
          } else {
            $printRow = false;
          }
        }

        if ($item->certificado === 't')
          $hay_prod_certificados = true;

        if($printRow)
        {
          if ($item->porcentaje_iva == '11')
            $traslado11 += $item->iva;
          elseif ($item->porcentaje_iva == '16')
            $traslado16 += $item->iva;

          $descripcion_ext = '';
          if ( GastosProductos::searchGastosProductos($item->id_clasificacion) ){
            if($item->id_clasificacion == '49' && isset($vale->factura['seguro']))
              $descripcion_ext = " (No {$vale->factura['seguro']->pol_seg})";
            elseif(($item->id_clasificacion == '51' || $item->id_clasificacion == '51') && isset($vale->factura['certificado'.$item->id_clasificacion]))
              $descripcion_ext = " (No {$vale->factura['certificado'.$item->id_clasificacion]->certificado})";
            elseif($item->id_clasificacion == '53' && isset($vale->factura['supcarga']))
              $descripcion_ext = " (No {$vale->factura['supcarga']->certificado})";
          }else
            $bultoss += $item->cantidad;

          $pdf->Row(array(
            $item->cantidad,
            $item->unidad,
            $item->descripcion.$descripcion_ext,
          ), false, true, null, 2, 1);
        }
      }

      $pdf->SetFont('Arial', 'B', 8);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetX(0);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths(array(40));
      $pdf->Row(array($bultoss), true, true, null, 2, 1);
      $pdf->SetY($pdf->GetY()+2);


      $yaux = $pdf->GetY();
      // Documentos //
      ///////////////
      $pdf->SetX(0);
      $pdf->SetAligns(['C']);
      $pdf->SetWidths([60]);
      $pdf->Row(['Documentos'], true, true, null, 2, 1);
      $pdf->SetFont('Arial', '', 7.5);
      foreach ($vale->documentos as $key => $value) {
        $pdf->SetX(0);
        $pdf->Row([$value->nombre], true, false, null, 2, 1);
        if ($value->id_documento == 1)
          $manifiesto = json_decode($value->data);
      }

      if (isset($manifiesto)) {
        $pdf->SetY($yaux);
        // Vehiculo //
        ///////////////
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetX(62);
        $pdf->SetAligns(['C']);
        $pdf->SetWidths([60]);
        $pdf->Row(['Vehiculo'], true, true, null, 2, 1);
        $pdf->SetX(62);
        $pdf->SetAligns(['R', 'L']);
        $pdf->SetWidths([20, 40]);
        $pdf->Row(['Placa:', $manifiesto->camion_placas], true, false, null, 2, 1);
        $pdf->SetX(62);
        $pdf->Row(['Color:', $manifiesto->camion_color], true, false, null, 2, 1);
        $pdf->SetX(62);
        $pdf->Row(['Marca:', $manifiesto->camion_marca], true, false, null, 2, 1);

        $pdf->SetY($yaux);
        // Chofer //
        ///////////////
        $pdf->SetX(124);
        $pdf->SetAligns(['C']);
        $pdf->SetWidths([60]);
        $pdf->Row(['Chofer'], true, true, null, 2, 1);
        $pdf->SetX(124);
        $pdf->Row([$manifiesto->chofer], true, false, null, 2, 1);
        $pdf->SetX(124);
        $pdf->Row([$manifiesto->chofer_ife], true, false, null, 2, 1);
        $pdf->SetX(124);
        $pdf->Row([$manifiesto->chofer_no_licencia], true, false, null, 2, 1);
        $pdf->SetX(124);
        $pdf->Row(['__________________________________'], true, false, null, 2, 1);
        $pdf->SetX(124);
        $pdf->Row(['FIRMA'], true, false, null, 2, 1);
      }
    }

    $pdf->SetXY(124, $pdf->GetY()+4);
    $pdf->Row(['__________________________________'], true, false, null, 2, 1);
    $pdf->SetX(124);
    $pdf->Row(['AUTORIZA'], true, false, null, 2, 1);

    $pdf->Output('vale_salida', 'I');
    // 0, 171, 72 = verde
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */