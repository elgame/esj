<?php

class Cheque extends FPDF {
	var $titulo1 = '';
	var $CI;

	var $hheader = '';

	var $limiteY = 0;

	var $orientation;
	var $unit;
  private $data;

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
	public function generaCheque($id_movimiento, $data=null){
    if ($id_movimiento) {
  		$CI =& get_instance();
      $CI->load->model('banco_cuentas_model');
      $data = $CI->banco_cuentas_model->getInfoOperacion($id_movimiento);
      if(isset($data['info']->id_movimiento))
        $this->{'generaCheque_'.$data['info']->id_banco}($data['info']->anombre_de, $data['info']->monto,
          substr($data['info']->fecha, 0, 10), $data['info']->moneda, $data['info']->abono_cuenta);
      else
        echo "No se obtubo la informacion del cheque";
    } elseif ($data) {
      $this->data = $data;
      $this->{'generaCheque_'.$data['cuenta']->formato_cheque}();
    }
	}

	/**
	 * Banorte 4
	 */
	public function generaCheque_bnort1($opc='I'){
    parent::__construct($this->orientation, $this->unit, array(70, 165));

    $this->AddPage('P', array(70, 165));
    $this->SetFont('Arial','B', 9);

    $this->SetDrawColor(0, 0, 0);
    $this->SetLineWidth(0.1);
    // $this->Rect(0, 0, 70, 165, 'D');

    $this->RotatedText(44, 63, MyString::fechaATexto(substr($this->data['info']->fecha, 0, 10)), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(42, 125, MyString::formatoNumero($this->data['info']->monto, 2, '', false), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(31, 37, $this->data['info']->a_nombre_de, -90);

    $this->RotatedText(23, 10, MyString::num2letras($this->data['info']->monto, $this->data['cuenta']->tipo), -90);

    // if($abono_cuenta == 1){
    //  $this->SetFont('Arial','B', 7);
    //  $this->RotatedText(60, 55, "PARA ABONO EN CUENTA", -90);
    //  $this->RotatedText(56, 55, "DEL BENEFICIARIO", -90);
    // }

    $this->Output('cheque.pdf', $opc);
  }

	/**
	 * Banamex 2
	 */
	public function generaCheque_bmex1($opc='I'){
		parent::__construct($this->orientation, $this->unit, array(70, 165));

		$this->AddPage('P', array(70, 165));
		$this->SetFont('Arial','B', 9);

		$this->SetDrawColor(0, 0, 0);
		$this->SetLineWidth(0.1);
		// $this->Rect(0, 0, 70, 165, 'D');

		$this->RotatedText(58, 112, MyString::fechaATexto(substr($this->data['info']->fecha, 0, 10)), -90);

		$this->SetFont('Arial','B', 9);
		$this->RotatedText(46, 130, MyString::formatoNumero($this->data['info']->monto, 2, '', false), -90);

		$this->SetFont('Arial','B', 9);
		$this->RotatedText(46, 12, $this->data['info']->a_nombre_de, -90);

		$this->RotatedText(37, 12, MyString::num2letras($this->data['info']->monto, $this->data['cuenta']->tipo), -90);

		// if($abono_cuenta == 1){
		// 	$this->SetFont('Arial','B', 7);
		// 	$this->RotatedText(33, 75, "PARA ABONO EN CUENTA", -90);
		// 	$this->RotatedText(29, 75, "DEL BENEFICIARIO", -90);
		// }

		$this->Output('cheque.pdf', $opc);
	}

  public function generaCheque_bmex2($opc='I'){
    parent::__construct($this->orientation, $this->unit, array(84, 216));

    $this->AddPage('P', array(84, 216));
    $this->SetFont('Arial','B', 9);

    $this->SetDrawColor(0, 0, 0);
    $this->SetLineWidth(0.1);
    // $this->Rect(0, 0, 84, 216, 'D');

    $this->RotatedText(64, 125, MyString::fechaATexto(substr($this->data['info']->fecha, 0, 10)), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(48, 173, MyString::formatoNumero($this->data['info']->monto, 2, '', false), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(48, 12, $this->data['info']->a_nombre_de, -90);

    $this->RotatedText(40, 12, MyString::num2letras($this->data['info']->monto, $this->data['cuenta']->tipo), -90);

    // if($abono_cuenta == 1){
    //  $this->SetFont('Arial','B', 7);
    //  $this->RotatedText(32, 85, "PARA ABONO EN CUENTA", -90);
    //  $this->RotatedText(28, 85, "DEL BENEFICIARIO", -90);
    // }

    $this->Output('cheque.pdf', $opc);
  }

	/**
	 * Banbajio 3
	 */
	public function generaCheque_bajio1($opc='I'){
    parent::__construct($this->orientation, $this->unit, array(70, 165));

    $this->AddPage('P', array(70, 165));
    $this->SetFont('Arial','B', 9);

    $this->SetDrawColor(0, 0, 0);
    $this->SetLineWidth(0.1);
    // $this->Rect(0, 0, 70, 165, 'D');

    $this->RotatedText(50, 79, MyString::fechaATexto(substr($this->data['info']->fecha, 0, 10)), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(39, 127, MyString::formatoNumero($this->data['info']->monto, 2, '', false), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(42, 10, $this->data['info']->a_nombre_de, -90);

    $this->RotatedText(33, 10, MyString::num2letras($this->data['info']->monto, $this->data['cuenta']->tipo), -90);

    // if($abono_cuenta == 1){
    //  $this->SetFont('Arial','B', 7);
    //  $this->RotatedText(22, 45, "PARA ABONO EN CUENTA", -90);
    //  $this->RotatedText(18, 45, "DEL BENEFICIARIO", -90);
    // }

    $this->Output('cheque.pdf', $opc);
  }

	/**
	 * Afirme 1
	 */
	public function generaCheque_afirm1($opc='I'){
    parent::__construct($this->orientation, $this->unit, array(70, 165));

    $this->AddPage('P', array(70, 165));
    $this->SetFont('Arial','B', 9);

    $this->SetDrawColor(0, 0, 0);
    $this->SetLineWidth(0.1);
    // $this->Rect(0, 0, 70, 165, 'D');

    $this->RotatedText(53, 105, MyString::fechaATexto(substr($this->data['info']->fecha, 0, 10)), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(40, 122, MyString::formatoNumero($this->data['info']->monto, 2, '', false), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(40, 10, $this->data['info']->a_nombre_de, -90);

    $this->RotatedText(30, 10, MyString::num2letras($this->data['info']->monto, $this->data['cuenta']->tipo), -90);

    // if($abono_cuenta == 1){
    //  $this->SetFont('Arial','B', 7);
    //  $this->RotatedText(66, 126, "PARA ABONO EN CUENTA", -90);
    //  $this->RotatedText(62, 126, "DEL BENEFICIARIO", -90);
    // }

    $this->Output('cheque.pdf', $opc);
  }

  /**
   * Santander 1
   */
  public function generaCheque_santr1($opc='I'){
    parent::__construct($this->orientation, $this->unit, array(70, 165));

    $this->AddPage('P', array(70, 165));
    $this->SetFont('Arial','B', 9);

    $this->SetDrawColor(0, 0, 0);
    $this->SetLineWidth(0.1);
    // $this->Rect(0, 0, 70, 165, 'D');

    $this->RotatedText(59, 95, MyString::fechaATexto(substr($this->data['info']->fecha, 0, 10)), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(42, 123, MyString::formatoNumero($this->data['info']->monto, 2, '', false), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(44, 12, $this->data['info']->a_nombre_de, -90);

    $this->RotatedText(35, 12, MyString::num2letras($this->data['info']->monto, $this->data['cuenta']->tipo), -90);

    // if($abono_cuenta == 1){
    //  $this->SetFont('Arial','B', 7);
    //  $this->RotatedText(30, 47, "PARA ABONO EN CUENTA", -90);
    //  $this->RotatedText(26, 47, "DEL BENEFICIARIO", -90);
    // }

    $this->Output('cheque.pdf', $opc);
  }

  /**
   * Bancomer 1
   */
  public function generaCheque_bcmer1($opc='I'){
    parent::__construct($this->orientation, $this->unit, array(70, 165));

    $this->AddPage('P', array(70, 165));
    $this->SetFont('Arial','B', 9);

    $this->SetDrawColor(0, 0, 0);
    $this->SetLineWidth(0.1);
    // $this->Rect(0, 0, 70, 165, 'D');

    $this->RotatedText(47, 91, MyString::fechaATexto(substr($this->data['info']->fecha, 0, 10)), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(40, 131, MyString::formatoNumero($this->data['info']->monto, 2, '', false), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(38, 15, $this->data['info']->a_nombre_de, -90);

    $this->RotatedText(30, 15, MyString::num2letras($this->data['info']->monto, $this->data['cuenta']->tipo), -90);

    // if($abono_cuenta == 1){
    //  $this->SetFont('Arial','B', 7);
    //  $this->RotatedText(65, 80, "PARA ABONO EN CUENTA", -90);
    //  $this->RotatedText(61, 80, "DEL BENEFICIARIO", -90);
    // }

    $this->Output('cheque.pdf', $opc);
  }

  /**
   * Bancomer 2
   */
  public function generaCheque_bcmer2($opc='I'){
    parent::__construct($this->orientation, $this->unit, array(70, 165));

    $this->AddPage('P', array(70, 165));
    $this->SetFont('Arial','B', 9);

    $this->SetDrawColor(0, 0, 0);
    $this->SetLineWidth(0.1);
    // $this->Rect(0, 0, 70, 165, 'D');

    $this->RotatedText(54, 115, MyString::fechaATexto(substr($this->data['info']->fecha, 0, 10)), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(41, 125, MyString::formatoNumero($this->data['info']->monto, 2, '', false), -90);

    $this->SetFont('Arial','B', 9);
    $this->RotatedText(40, 10, $this->data['info']->a_nombre_de, -90);

    $this->RotatedText(30, 10, MyString::num2letras($this->data['info']->monto, $this->data['cuenta']->tipo), -90);

    // if($abono_cuenta == 1){
    //  $this->SetFont('Arial','B', 7);
    //  $this->RotatedText(65, 60, "PARA ABONO EN CUENTA", -90);
    //  $this->RotatedText(61, 60, "DEL BENEFICIARIO", -90);
    // }

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
