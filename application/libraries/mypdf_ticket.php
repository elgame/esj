<?php

class mypdf_ticket extends FPDF {
    var $limiteY = 0;
    var $titulo1 = 'EMPAQUE SAN JORGE S.A DE C.V';
    var $reg_fed = 'REG. ESJ97052763A0620061646';
    var $font_size = 8;

    var $fount_txt = 'helvetica';
    var $fount_num = 'SciFly-Sans'; // SciFly-Sans

    var $pag_size = array();

    public $header_entrar = true;

	/**
	 * P:Carta Vertical, L:Carta Horizontal, lP:Legal vertical, lL:Legal Horizontal
	 * @param unknown_type $orientation
	 * @param unknown_type $unit
	 * @param unknown_type $size
	 */
	function __construct($orientation='P', $unit='mm', $size=array(63, 300)){
		parent::__construct($orientation, $unit, $size);
		$this->limiteY = 50;
    $this->pag_size = $size;

    $this->SetMargins(0, 0, 0);
    $this->SetAutoPageBreak(false);
	}

    //Page header
    public function headerTicket($data) {
        $this->AddFont($this->fount_num, '');

        if ($data->status == 'f') {
          $this->SetFont($this->fount_txt, '', 30);
          $this->SetTextColor(150, 150, 150);
          $this->RotatedText(30, 20, 'CANCELADA', -90);
        }

        $this->SetTextColor(0, 0, 0);
        if ($this->header_entrar) {
            // Título
            $this->SetFont($this->fount_txt, '', 8);
            $this->SetXY(0, $this->GetY());
            $this->MultiCell($this->pag_size[0], 4, $this->titulo1, 0, 'C');

            $this->SetFounts(array($this->fount_txt), array(-1.5));
            $this->SetAligns(array('L'));
            $this->SetWidths(array(38));
            $this->SetXY(0, $this->GetY()-1);
            $this->Row(array(($data->tipo=='en'? 'ENTRADA': 'SALIDA').' '.$data->area ), false, true, 4);
            $this->SetXY(38, $this->GetY()-4);
            $this->SetWidths(array(12));
            $this->SetFounts(array($this->fount_txt), array(-1));
            $this->Row(array('BOLETA'), false, false, 4);
            $this->SetXY(49, $this->GetY()-4);
            $this->SetWidths(array(15));
            $this->SetFounts(array($this->fount_num), array(1));
            $this->Row(array($data->folio), false, true, 4);

            if ($data->intangible == 't') {
              $this->SetTextColor(255, 255, 255);
              $this->Rect($this->GetX(), $this->GetY()+1, 63, 5, 'DF');
            }
            $this->SetFounts(array($this->fount_txt), array(-1));
            $this->SetWidths(array(63));
            $this->SetAligns(array('C'));
            $this->SetXY(0, $this->GetY()+.5);
            $this->Row(array($this->reg_fed), false, false);
            $this->SetTextColor(0, 0, 0);

            $this->header_entrar = false;
        }
    }

    public function datosTicket($data) {
        $this->SetXY(0, $this->GetY()-2);
        $this->MultiCell($this->pag_size[0], 2, '--------------------------------------------------------------------------', 0, 'L');

        $this->SetY($this->GetY()-1);

        $this->SetWidths(array(12, 17, 20, 14));
        $this->SetAligns(array('L', 'R', 'L', 'L'));
        $this->SetFounts(array($this->fount_txt, $this->fount_num, $this->fount_num),
                         array(0, 1.5, 0.5, 0.5));
        $this->Row(array( 'BRUTO:', MyString::formatoNumero($data->kilos_bruto, 2, ''), MyString::fechaATexto(substr($data->fecha_bruto, 0, 10), '/c'), substr($data->fecha_bruto, -11, -3)), false, false, 4);
        $this->SetY($this->GetY()+.5);
        $this->Row(array( 'TARA:', MyString::formatoNumero($data->kilos_tara, 2, ''), MyString::fechaATexto(substr($data->fecha_tara, 0, 10), '/c'), substr($data->fecha_tara, -11, -3)), false, false, 4);
        $this->SetY($this->GetY()+.5);
        $this->Row(array( 'NETO:', MyString::formatoNumero($data->kilos_neto, 2, ''), ''), false, false, 4);
    }

    public function productosTicket($data, $data_info){

        $this->SetY($this->GetY()+1);

        // $this->SetFont($this->fount_txt, '', $this->font_size-1);
        $this->SetWidths(array(9, 11, 11.5, 10, 8.5, 16));
        $this->SetAligns(array('L','L','L','L','R','L'));
        $this->SetFounts(array($this->fount_txt),
                         array(-1,-1,-1,-1,-1,-1));

        // $this->Row(array('CJS', 'PROD', 'KILOS', 'P.P.', '$', 'IMPORTE'), false, true, 5);
        $this->Row(array('Pzas', 'Produc', 'Kilos', 'P.P.', 'Prec', 'Importe'), false, true, 4);

        $this->SetFont($this->fount_txt, '', $this->font_size);
        $this->CheckPageBreak(4);
        $this->MultiCell($this->pag_size[0], 2, '----------------------------------------------------------------', 0, 'L');
        if(is_array($data_info)){
          $this->SetY($this->GetY()+1);
          $this->SetFounts(array($this->fount_num,$this->fount_txt,$this->fount_num,$this->fount_num,$this->fount_num,$this->fount_num),
                     array(.5,-1,.5,.5,.5,.5));
          $total_kilos = 0;
          $total_pzas = 0;
          foreach ($data_info as $prod){
            $this->SetY($this->GetY()-3);
            $this->Row(array($prod->cajas,
                             $prod->calidad,
                             MyString::formatoNumero($prod->kilos, 2, ''),
                             $prod->promedio,
                             MyString::formatoNumero($prod->precio, 2, ''),
                             MyString::formatoNumero($prod->importe, 2, '', false)), false, false);
            $total_kilos += $prod->kilos;
            $total_pzas += $prod->cajas;
          }
        }

        $this->SetFont($this->fount_txt, '', $this->font_size);
        $this->SetY($this->GetY()-2);
        $this->CheckPageBreak(4);
        $this->MultiCell($this->pag_size[0], 2, '----------------------------------------------------------------', 0, 'L');

        $this->SetFont($this->fount_txt, '', $this->font_size+1);
        $this->SetY($this->GetY()-2);
        $this->SetWidths(array(11, 20, 14, 18));
        $this->SetAligns(array('L', 'R', 'R', 'R'));
        $this->SetFounts(array($this->fount_num, $this->fount_num, $this->fount_txt, $this->fount_num), array(1, 1, 0, 1));
        $this->Row(array($total_pzas, $total_kilos, 'SubTotal', MyString::formatoNumero($data->importe+$data->ret_isr, 2, '', false)), false, false, 4);

        $this->SetX(31);
        $this->SetWidths(array(14, 18));
        $this->SetAligns(array('R', 'R'));
        $this->SetFounts(array($this->fount_txt, $this->fount_num), array(0, 1));
        $this->Row(array('IVA', MyString::formatoNumero(0, 2, '', false)), false, false, 4);
        $this->SetX(31);
        $this->Row(array("ISR", MyString::formatoNumero($data->ret_isr, 2, '', false)), false, false, 4);
        $this->SetX(31);
        $this->Row(array('Total', MyString::formatoNumero($data->importe, 2, '', false)), false, false, 4);

        $this->SetXY(0, $this->GetY()-4);
        $this->SetWidths(array(11));
        $this->SetFounts(array($this->fount_txt), array(0));
        $this->Row(array('Lote'), false, false, 4);
        $this->SetXY(11, $this->GetY()-4);
        $this->SetWidths(array(24));
        $this->SetFounts(array($this->fount_num), array(1));
        $this->Row(array($data->no_lote!=''? $data->no_lote: '-'), false, true, 4);

        $this->SetFont($this->fount_txt, '', $this->font_size-1);
        $this->SetY($this->GetY() + 3);

        if ($data->tipo === 'en')
        {
          $cuentaCpi = $data->cpi_proveedor;
          $nombreCpi = $data->proveedor;
        }
        else
        {
          $cuentaCpi = $data->cpi_cliente;
          $nombreCpi = $data->cliente;
        }

        $this->MultiCell($this->pag_size[0], 3, 'CUENTA: ' . strtoupper($cuentaCpi), 0, 'L');
        $this->SetY($this->GetY()-2);
        $this->SetWidths(array($this->pag_size[0]));
        $this->SetFounts(array($this->fount_txt), array(0));
        $this->SetAligns(array('L'));
        $this->Row(array( '              '.strtoupper($nombreCpi) ), false, false);

        $this->SetY($this->GetY()-1);
        $this->MultiCell($this->pag_size[0], 3, 'RANCHO: ' . strtoupper($data->rancho), 0, 'L');
        $this->MultiCell($this->pag_size[0], 3, 'CHOFER: ' . strtoupper($data->chofer), 0, 'L');
        $this->MultiCell($this->pag_size[0], 3, 'CAMION: ' . strtoupper($data->camion), 0, 'L');
        $this->MultiCell($this->pag_size[0], 3, 'PLACAS: ' . strtoupper($data->camion_placas), 0, 'L');
        $this->MultiCell($this->pag_size[0], 3, 'PRODUCTOR: ' . strtoupper($data->productor), 0, 'L');

        if ($data->certificado == 't')
        {
          $this->SetY($this->GetY() + 1);
          $this->MultiCell($this->pag_size[0], 3, 'GGN4052852866927 PRODUCTO CERTIFICADO', 0, 'L');
        }

    }

    public function clasificTicket($data, $data_info){
        $this->SetFont($this->fount_txt, '', $this->font_size);
        $this->SetY($this->GetY()+2);
        $this->MultiCell($this->pag_size[0], 2, '----------------------------------------------------------------', 0, 'L');

        $this->SetY($this->GetY()-1);

        // $this->SetFont($this->fount_txt, '', $this->font_size-1);
        $this->SetWidths(array(9, 16, 16.5, 8.5, 16));
        $this->SetAligns(array('L','L','L','R','L'));
        $this->SetFounts(array($this->fount_txt),
                         array(-1,-1,-1,-1,-1));

        // $this->Row(array('CJS', 'PROD', 'KILOS', 'P.P.', '$', 'IMPORTE'), false, true, 5);
        $this->Row(array('CJS', 'UMedida', 'CLASIF', 'PCIO', 'IMPORTE'), false, true, 5);

        $this->SetFont($this->fount_txt, '', $this->font_size);
        $this->CheckPageBreak(4);
        $this->MultiCell($this->pag_size[0], 2, '----------------------------------------------------------------', 0, 'L');
        $total = $subtotal = $iva = 0;
        if(is_array($data_info)){
            $this->SetY($this->GetY()+1);
              // $this->SetFont($this->fount_txt, '', $this->font_size);
              // $this->SetWidths(array(8, 12, 12, 10, 10, 18));
              // $this->SetAligns(array('L'));
            foreach ($data_info as $prod){
              $this->SetWidths(array(9, 16, 16.5, 8.5, 16));
              $this->SetAligns(array('L','L','L','R','L'));
              $this->SetFounts(array($this->fount_num,$this->fount_txt,$this->fount_txt,$this->fount_num,$this->fount_num),
                         array(.5,-1,-1,.5,.5));
              $this->SetY($this->GetY()-3);
              $this->Row(array($prod->cantidad,
                               $prod->ucodigo,
                               $prod->ccodigo,
                               MyString::formatoNumero($prod->precio_unitario, 2, ''),
                               MyString::formatoNumero($prod->importe, 2, '', false)), false, false);

              $this->SetWidths(array(9, 16, 16.5, 16, 8.5));
              $this->SetAligns(array('R','L','L','L','L'));
              $this->SetFounts(array($this->fount_txt,$this->fount_num,$this->fount_txt,$this->fount_num,$this->fount_num),
                         array(.5,-1,-1,.5,.5));
              $this->SetY($this->GetY()-3);
              $this->Row(array('Kg:',
                                MyString::formatoNumero($prod->kilos, 2, ''),
                                'Promedio:',
                                MyString::formatoNumero($prod->promedio, 2, ''),
                                ''
                              ), false, false);
              $this->SetY($this->GetY()+2);
              $subtotal += $prod->importe;
              $iva += $prod->iva;
            }
        }
        $total = $subtotal+$iva;

        $this->SetFont($this->fount_txt, '', $this->font_size);
        $this->CheckPageBreak(4);
        $this->MultiCell($this->pag_size[0], 2, '----------------------------------------------------------------', 0, 'L');

        $this->SetFont($this->fount_txt, '', $this->font_size+1);
        $this->SetY($this->GetY()-2);
        $this->SetWidths(array(30, 31));
        $this->SetAligns(array('R', 'R'));
        $this->SetFounts(array($this->fount_txt, $this->fount_num),
                         array(0, 1));
        $this->Row(array('SUBTOTAL', MyString::formatoNumero($subtotal, 2, '', false)), false, false, 3);
        $this->Row(array('IVA', MyString::formatoNumero($iva, 2, '', false)), false, false, 3);
        $this->Row(array('TOTAL', MyString::formatoNumero($total, 2, '', false)), false, false, 3);

        $this->SetFont($this->fount_txt, '', $this->font_size);
        $this->SetY($this->GetY() + 3);

        if ($data->tipo === 'en')
        {
          $cuentaCpi = $data->cpi_proveedor;
          $nombreCpi = $data->proveedor;
        }
        else
        {
          $cuentaCpi = $data->cpi_cliente;
          $nombreCpi = $data->cliente;
        }

        $this->MultiCell($this->pag_size[0], 3, 'CUENTA: ' . strtoupper($cuentaCpi), 0, 'L');
        $this->SetY($this->GetY()-2);
        $this->SetWidths(array($this->pag_size[0]));
        $this->SetAligns(array('L'));
        $this->Row(array( strtoupper($nombreCpi) ), false, false);

        $this->SetY($this->GetY()-1);
        $this->MultiCell($this->pag_size[0], 3, 'RANCHO: ' . strtoupper($data->rancho), 0, 'L');
        $this->MultiCell($this->pag_size[0], 3, 'CHOFER: ' . strtoupper($data->chofer), 0, 'L');
        $this->MultiCell($this->pag_size[0], 3, 'CAMION: ' . strtoupper($data->camion), 0, 'L');
        $this->MultiCell($this->pag_size[0], 3, 'PLACAS: ' . strtoupper($data->camion_placas), 0, 'L');

        if ($data->certificado == 't')
        {
          $this->SetY($this->GetY() + 1);
          $this->MultiCell($this->pag_size[0], 3, 'GGN4052852866927 PRODUCTO CERTIFICADO', 0, 'L');
        }

    }

    public function pieTicket($data){

      $this->SetY($this->GetY());

      $this->MultiCell($this->pag_size[0], 2, '----------------------------------------------------------------', 0, 'L');

      $bascula = 'Electrónica Toledo';
      if ($data->kilos_neto2 == $data->kilos_neto && $data->tipo == 'en') {
        $bascula = 'De piso en recepción';
      }
      $this->SetY($this->GetY()-1);
      $this->SetFounts(array($this->fount_txt), array(-1));
      $this->SetWidths(array($this->pag_size[0]));
      $this->SetAligns(array('L'));
      $this->Row(array("BASCULA: {$bascula}" ), false, false, 4);

      $status = 'PENDIENTE';
      $fechaPago = '';
      if ($data->accion == 'p') { // contado
        $fechaPago = MyString::fechaATexto(substr($data->fecha_pago, 0, 10), '/c').' '.substr($data->fecha_pago, -11, -3);
        $status = 'PAGADA';
      } elseif ($data->accion == 'b') { // transferencia o cheque
        $fechaPago = '';
        $status = 'PAGADA';
        if (isset($data->pago->fecha)) {
          $fechaPago = MyString::fechaATexto(substr($data->pago->fecha, 0, 10), '/c').' '.substr($data->pago->fecha, -11, -3);
          $this->SetFounts(array($this->fount_txt), array(-1));
          $this->SetWidths(array($this->pag_size[0]));
          $this->SetAligns(array('L'));
          $this->SetY($this->GetY()-1);
          $this->Row(array("METODO PAGO: {$data->pago->tipo_pago}" ), false, false, 4);
          $this->SetY($this->GetY()-1);
          $this->Row(array("CUENTA: {$data->pago->alias}" ), false, false, 4);
          if ($data->pago->usuario != '') {
            $this->SetY($this->GetY()-1);
            $this->Row(array("REALIZO: {$data->pago->usuario}" ), false, false, 4);
          }
        }
      }
      $this->SetFounts(array($this->fount_txt, $this->fount_num), array(-1, -1));
      $this->SetWidths(array(32, 30));
      $this->SetAligns(array('L', 'R'));
      $this->SetY($this->GetY()-1);
      $this->Row(array("STATUS: {$status}", $fechaPago), false, false, 4);

      $this->SetY($this->GetY()+1);
      $this->MultiCell($this->pag_size[0], 2, '--------------------------------------------------------------------', 0, 'L');

      $txt_impresion = $data->no_impresiones>0? 'COPIA '.$data->no_impresiones: 'ORIGINAL';

      $this->SetFont($this->fount_txt, '', $this->font_size+1);
      $this->SetFounts(array($this->fount_txt), array(-1));
      $this->SetWidths(array(35, 27));
      $this->SetAligns(array('L', 'R'));
      $this->Row(array('CREADO:' ), false, false);
      $this->SetY($this->GetY() - 3.5);
      $this->SetFounts(array($this->fount_num, $this->fount_txt), array(0, 0));
      if ($data->intangible == 't') {
        $this->Row(array(MyString::fechaATexto(substr($data->fecha_bruto, 0, 10), '/c').' '.substr($data->fecha_bruto, -11, -3), 'ORIGINAL'), false, false, 4);
      } else {
        if ($data->no_impresiones == 0) {
          $this->Row(array(MyString::fechaATexto(date("Y-m-d"), '/c').' '.date("H:i:s"), $txt_impresion), false, false, 4);
        } else {
          $this->Row(array(MyString::fechaATexto(substr($data->fecha_imp_orig, 0, 10), '/c').' '.substr($data->fecha_imp_orig, -11, -3), 'ORIGINAL'), false, false, 4);
        }
      }
      $this->SetWidths(array($this->pag_size[0]));
      $this->SetFounts(array($this->fount_txt), array(-1));
      $this->SetAligns(array('L'));
      $this->SetY($this->GetY()-1);
      $this->Row(array($data->creadox ), false, false, 4);
      if (!empty($data->cerradox)) {
        $this->SetFont($this->fount_txt, '', $this->font_size+1);
        $this->SetFounts(array($this->fount_txt), array(-1));
        $this->SetWidths(array(35, 27));
        $this->SetAligns(array('L', 'R'));
        $this->Row(array('CERRADO:' ), false, false);
        $this->SetWidths(array($this->pag_size[0]));
        $this->SetFounts(array($this->fount_txt), array(-1));
        $this->SetAligns(array('L'));
        $this->SetY($this->GetY() - 3.5);
        $this->Row(array(MyString::fechaATexto(substr($data->fecha_imp_orig, 0, 10), '/c').' '.substr($data->fecha_imp_orig, -11, -3)), false, false, 4);
        $this->SetY($this->GetY()-1);
        $this->Row(array($data->cerradox ), false, false, 4);
      }
      if ($data->no_impresiones > 0) {
        $this->SetFounts(array($this->fount_txt), array(-1));
        $this->SetWidths(array(35, 27));
        $this->SetAligns(array('L', 'R'));
        $this->Row(array('RE-IMPRESION:' ), false, false, 4);
        $this->SetY($this->GetY()-1.2);
        $this->SetFounts(array($this->fount_num, $this->fount_txt), array(0, 0));
        $this->Row(array(MyString::fechaATexto(date("Y-m-d"), '/c').' '.date("H:i:s"), $txt_impresion), false, false, 4);
        $this->SetWidths(array($this->pag_size[0]));
        $this->SetFounts(array($this->fount_txt), array(-1));
        $this->SetAligns(array('L'));
        $this->SetY($this->GetY()-1);
        $this->Row(array($data->usuario ), false, false, 4);
      }


      $this->SetAligns(array('C'));
      $this->SetFont($this->fount_txt, '', $this->font_size);
      $this->SetY($this->GetY() + 5);
      $this->Row(array('--------------------------------------------------'), false, false);

      $this->SetY($this->GetY() - 3);
      $this->SetFounts(array($this->fount_txt), array(1));
      $this->Row(array('FIRMA CHOFER'), false, false);

      $this->SetAligns(array('C'));
      $this->SetFont($this->fount_txt, '', $this->font_size);
      $this->SetY($this->GetY() + 5);
      $this->Row(array('--------------------------------------------------'), false, false);

      $this->SetY($this->GetY() - 3);
      $this->SetFounts(array($this->fount_txt), array(1));
      $this->Row(array('FIRMA RECIBIDO'), false, false);


      if (isset($data->bitacora) && count($data->bitacora) > 0 && $data->no_impresiones >= 2) {
        $this->AddPage();
        $this->SetY($this->GetY()+1);
        $this->Row(array('---------------------------------------------------------'), false, false, 4);
        $this->SetAligns(array('C'));
        $this->SetY($this->GetY()-1);
        $this->Row(array('Cambios'), false, false, 4);

        foreach ($data->bitacora as $key => $value) {
          $y = $this->GetY();
          $this->SetFounts(array($this->fount_txt, $this->fount_txt), array(-1, -1));
          $this->SetWidths(array(37, 25));
          $this->SetAligns(array('L', 'R'));
          $this->Row(array($value->campo, substr($value->fecha, 0, 19)), false, false, 4);
          $this->SetWidths(array(31, 31));
          $this->Row(array("Realizo: {$value->usuario_logueado}", "Autorizo: {$value->usuario_auth}"), false, false, 4);
          $this->SetFounts(array($this->fount_txt), array(-1));
          $this->SetWidths(array(62));
          $this->SetAligns(array('L'));
          $h = 8;
          if (strlen($value->antes) > 0) {
            $this->Row(array("Antes: ".str_replace('|', ' | ', $value->antes)), false, false, $this->getHRow(62, str_replace('|', ' | ', $value->antes)));
            $h += $this->getHRow(62, str_replace('|', ' | ', $value->antes));
          }
          if (strlen($value->despues) > 0) {
            $this->Row(array("Después: ".str_replace('|', ' | ', $value->despues)), false, false, $this->getHRow(62, str_replace('|', ' | ', $value->despues)));
            $h += $this->getHRow(62, str_replace('|', ' | ', $value->despues));
          }
          $this->Row(array('------------------------------------------------------------------------'), false, false);
        }
      }

    }

    public function printTicket($data, $data_prod, $cajas_clasf){
      $this->headerTicket($data);
      $this->datosTicket($data);
      if ($data->tipo == 'sa') {
        $this->clasificTicket($data, $cajas_clasf);
      } else {
        $this->productosTicket($data, $data_prod);
      }
      $this->pieTicket($data);
    }


    var $col=0;

    function SetCol($col){
        //Move position to a column
        $this->col=$col;
        $x=10+$col*65;
        $this->SetLeftMargin($x);
        $this->SetX($x);
    }

    function AcceptPageBreak(){
        if($this->col<2){
            //Go to next column
            $this->SetCol($this->col+1);
            $this->SetY(10);
            return false;
        }else{
            //Regrese a la primera columna y emita un salto de página
            $this->SetCol(0);
            return true;
        }
    }

    public function getHRow($width, $text)
    {
      $nb = max(0, $this->NbLines($width, $text));
      $h = ($this->font_size-3)*$nb;
      return $h;
    }


    /*Crear tablas*/
    var $widths;
    var $aligns;
    var $links;
    var $font;
    var $fontz;

    function SetWidths($w){
        $this->widths=$w;
    }

    function SetAligns($a){
        $this->aligns=$a;
    }

    function SetMyLinks($a){
        $this->links=$a;
    }

    function SetFounts($a, $z=array()){
        $this->font=$a;
        $this->fontz=$z;
    }

    function Row($data, $header=false, $bordes=true, $h=NULL){
        $nb=0;
        for($i=0;$i<count($data);$i++)
            $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
            $h= $h==NULL? $this->FontSize*$nb+3: $h;
            if($header)
                $h += 2;
            $this->CheckPageBreak($h);
            for($i=0;$i<count($data);$i++){
                $w=$this->widths[$i];
                $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
                $x=$this->GetX();
                $y=$this->GetY();

                $this->SetFont( (isset($this->font[$i]) ? $this->font[$i] : 'helvetica'), '', ($this->font_size+(isset($this->fontz[$i]) ? $this->fontz[$i] : 0)) );

                if($header && $bordes)
                    $this->Rect($x,$y,$w,$h,'DF');
                elseif($bordes)
                    $this->Rect($x,$y+1,$w,$h);

                if($header)
                    $this->SetXY($x,$y+3);
                else
                    $this->SetXY($x,$y+2);

                if(isset($this->links[$i]{0}) && $header==false){
                    $this->SetTextColor(35, 95, 185);
                    $this->Cell($w, $this->FontSize, $data[$i], 0, strlen($data[$i]), $a, false, $this->links[$i]);
                    $this->SetTextColor(0,0,0);
                }else
                    $this->MultiCell($w,$this->FontSize, $data[$i],0,$a);

                $this->SetXY($x+$w,$y);
            }
            $this->Ln($h);
    }

    function CheckPageBreak($h, $limit=0){
        $limit = $limit==0? $this->PageBreakTrigger: $limit;
        if($this->GetY()+$h>$limit){
            $this->AddPage($this->CurOrientation);
            return true;
        }
        return false;
    }

    function NbLines($w,$txt){
        $cw=&$this->CurrentFont['cw'];
        if($w==0)
            $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n")
            $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb){
            $c=$s[$i];
            if($c=="\n"){
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ')
                $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax){
                if($sep==-1){
                    if($i==$j)
                        $i++;
                }else
                    $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            }else
                $i++;
        }
        return $nl;
    }



    /**
     * indica si se abre el dialogo de imprecion inmediatamente
     * @param boolean $dialog [description]
     */
    function AutoPrint($dialog=false){
        //Open the print dialog or start printing immediately on the standard printer
        $param=($dialog ? 'true' : 'false');
        $script="print({$param});";
        $this->IncludeJS($script);
    }


    /**
     * SOPORTE PARA INTRODUCIR JAVASCRIPT
     */
    var $javascript;
    var $n_js;

    function IncludeJS($script) {
        $this->javascript=$script;
    }

    function _putjavascript() {
        $this->_newobj();
        $this->n_js=$this->n;
        $this->_out('<<');
        $this->_out('/Names [(EmbeddedJS) '.($this->n+1).' 0 R]');
        $this->_out('>>');
        $this->_out('endobj');
        $this->_newobj();
        $this->_out('<<');
        $this->_out('/S /JavaScript');
        $this->_out('/JS '.$this->_textstring($this->javascript));
        $this->_out('>>');
        $this->_out('endobj');
    }

    function _putresources() {
        parent::_putresources();
        if (!empty($this->javascript)) {
            $this->_putjavascript();
        }
    }

    function _putcatalog() {
        parent::_putcatalog();
        if (!empty($this->javascript)) {
            $this->_out('/Names <</JavaScript '.($this->n_js).' 0 R>>');
        }
    }

  function RotatedText($x, $y, $txt, $angle)
  {
      //Text rotated around its origin
      $this->Rotate($angle, $x, $y);
      $this->Text($x, $y, $txt);
      $this->Rotate(0);
  }


  var $angle=0;

  function Rotate($angle, $x=-1, $y=-1)
  {
      if($x==-1)
          $x=$this->x;
      if($y==-1)
          $y=$this->y;
      if($this->angle!=0)
          $this->_out('Q');
      $this->angle=$angle;
      if($angle!=0)
      {
          $angle*=M_PI/180;
          $c=cos($angle);
          $s=sin($angle);
          $cx=$x*$this->k;
          $cy=($this->h-$y)*$this->k;
          $this->_out(sprintf('q %.5f %.5f %.5f %.5f %.2f %.2f cm 1 0 0 1 %.2f %.2f cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
      }
  }

  function _endpage()
  {
      if($this->angle!=0)
      {
          $this->angle=0;
          $this->_out('Q');
      }
      parent::_endpage();
  }
}


?>