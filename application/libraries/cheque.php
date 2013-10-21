<?php

class Cheque extends FPDF {
	var $titulo1 = 'Red Fire de Colima';
	var $CI;
	
	var $hheader = '';
	
	var $limiteY = 0;

	var $orientation;
	var $unit;
	
	/**
	 * P:Carta Vertical, L:Carta Horizontal, lP:Legal vertical, lL:Legal Horizontal
	 * @param unknown_type $orientation
	 * @param unknown_type $unit
	 * @param unknown_type $size
	 */
	function __construct($orientation='P', $unit='mm', $size=array(70, 165)){
		$this->orientation = $orientation;
		$this->unit = $unit;
	}
	
	
	/**
	 * Descarga el estado de una cuenta seleccionada en formato pdf
	 */
	public function generaCheque($id_movimiento){
		$CI =& get_instance();
		$CI->load->model('banco_cuentas_model');
		$data = $CI->banco_cuentas_model->getInfoOperacion($id_movimiento);
		if(isset($data['info']->id_movimiento))
			$this->{'generaCheque_'.$data['info']->id_banco}($data['info']->anombre_de, $data['info']->monto, 
				substr($data['info']->fecha, 0, 10), $data['info']->moneda, $data['info']->abono_cuenta);
		else
			echo "No se obtubo la informacion del cheque";
	}

	/**
	 * Banorte 4
	 */
	public function generaCheque_4($nombre, $monto, $fecha=null, $moneda='M.N.', $abono_cuenta=0, $opc='I'){
		parent::__construct($this->orientation, $this->unit, array(70, 165));

		$fecha = $fecha==null? date("Y-m-d"): $fecha;
		$this->AddPage('P', array(70, 165));
		$this->SetFont('Arial','B', 9);
		
		$this->SetDrawColor(0, 0, 0);
		$this->SetLineWidth(0.1);
		// $this->Rect(0, 0, 70, 165, 'D');
		
		$this->RotatedText(44, 64, String::fechaATexto($fecha), -90);
		
		$this->SetFont('Arial','B', 10);
		$this->RotatedText(44, 124, String::formatoNumero($monto, 2, ''), -90);
		
		$this->SetFont('Arial','B', 9);
		$this->RotatedText(38, 35, $nombre, -90);
		
		$this->RotatedText(28, 8, String::num2letras($monto, $moneda), -90);

		if($abono_cuenta == 1){
			$this->SetFont('Arial','B', 8);
			$this->RotatedText(55, 50, 'PARA ABONO EN CUENTA', -90);
		}
		
		$this->Output('cheque.pdf', $opc);
	}

	/**
	 * Banamex 2
	 */
	public function generaCheque_2($nombre, $monto, $fecha=null, $moneda='M.N.', $abono_cuenta=0, $opc='I'){
		parent::__construct($this->orientation, $this->unit, array(70, 165));

		$fecha = $fecha==null? date("Y-m-d"): $fecha;
		$this->AddPage('P', array(70, 165));
		$this->SetFont('Arial','B', 9);
		
		$this->SetDrawColor(0, 0, 0);
		$this->SetLineWidth(0.1);
		// $this->Rect(0, 0, 70, 165, 'D');
		
		$this->RotatedText(58, 112, String::fechaATexto($fecha), -90);
		
		$this->SetFont('Arial','B', 10);
		$this->RotatedText(42, 132, String::formatoNumero($monto, 2, ''), -90);
		
		$this->SetFont('Arial','B', 9);
		$this->RotatedText(46, 6, $nombre, -90);
		
		$this->RotatedText(37, 6, String::num2letras($monto, $moneda), -90);

		if($abono_cuenta == 1){
			$this->SetFont('Arial','B', 7);
			$this->RotatedText(13, 120, "PARA ABONO EN CUENTA", -90);
			$this->RotatedText(9, 120, "DEL BENEFICIARIO", -90);
		}
		
		$this->Output('cheque.pdf', $opc);
	}

	/**
	 * Banbajio 3
	 */
	public function generaCheque_3($nombre, $monto, $fecha=null, $moneda='M.N.', $abono_cuenta=0, $opc='I'){
		parent::__construct($this->orientation, $this->unit, array(70, 165));

		$fecha = $fecha==null? date("Y-m-d"): $fecha;
		$this->AddPage('P', array(70, 165));
		$this->SetFont('Arial','B', 9);
		
		$this->SetDrawColor(0, 0, 0);
		$this->SetLineWidth(0.1);
		// $this->Rect(0, 0, 70, 165, 'D');
		
		$this->RotatedText(51, 79, String::fechaATexto($fecha), -90);
		
		$this->SetFont('Arial','B', 10);
		$this->RotatedText(39, 132, String::formatoNumero($monto, 2, ''), -90);
		
		$this->SetFont('Arial','B', 9);
		$this->RotatedText(44, 6, $nombre, -90);
		
		$this->RotatedText(34, 6, String::num2letras($monto, $moneda), -90);

		if($abono_cuenta == 1){
			$this->SetFont('Arial','B', 7);
			$this->RotatedText(13, 120, "PARA ABONO EN CUENTA", -90);
			$this->RotatedText(9, 120, "DEL BENEFICIARIO", -90);
		}
		
		$this->Output('cheque.pdf', $opc);
	}
	
	/**
	 * Afirme 1
	 */
	public function generaCheque_1($nombre, $monto, $fecha=null, $moneda='M.N.', $abono_cuenta=0, $opc='I'){
		parent::__construct($this->orientation, $this->unit, array(70, 165));

		$fecha = $fecha==null? date("Y-m-d"): $fecha;
		$this->AddPage('P', array(70, 165));
		$this->SetFont('Arial','B', 9);
		
		$this->SetDrawColor(0, 0, 0);
		$this->SetLineWidth(0.1);
		// $this->Rect(0, 0, 70, 165, 'D');
		
		$this->RotatedText(54, 104, String::fechaATexto($fecha), -90);
		
		$this->SetFont('Arial','B', 10);
		$this->RotatedText(40, 123, String::formatoNumero($monto, 2, ''), -90);
		
		$this->SetFont('Arial','B', 9);
		$this->RotatedText(42, 8, $nombre, -90);
		
		$this->RotatedText(32, 8, String::num2letras($monto, $moneda), -90);

		if($abono_cuenta == 1){
			$this->SetFont('Arial','B', 7);
			$this->RotatedText(13, 120, "PARA ABONO EN CUENTA", -90);
			$this->RotatedText(9, 120, "DEL BENEFICIARIO", -90);
		}
		
		$this->Output('cheque.pdf', $opc);
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
