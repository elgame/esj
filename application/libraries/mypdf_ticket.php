<?php

class mypdf_ticket extends FPDF {
    var $limiteY = 0;
    var $titulo1 = 'CAFE DIGITAL';

    var $pag_size = array();

    private $header_entrar = true;

	/**
	 * P:Carta Vertical, L:Carta Horizontal, lP:Legal vertical, lL:Legal Horizontal
	 * @param unknown_type $orientation
	 * @param unknown_type $unit
	 * @param unknown_type $size
	 */
	function __construct($orientation='P', $unit='mm', $size=array(63, 180)){
		parent::__construct($orientation, $unit, $size);
		$this->limiteY = 50;
        $this->pag_size = $size;

        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false);
	}

    //Page header
    public function Header() {
        if ($this->header_entrar) {
            // Título
            $this->SetFont('Arial', 'B', 8);
            $this->SetXY(0, 0);
            $this->MultiCell($this->pag_size[0], 10, $this->titulo1, 0, 'C');

            $this->header_entrar = false;
        }
    }

    public function datosTicket($data){
        $this->SetFont('Arial', '', 8);
        $this->MultiCell($this->pag_size[0], 3, 'Cafeteria // Impresion Digital // Internet', 0, 'L');
        $this->MultiCell($this->pag_size[0], 3, 'Av. Conchita 3768-A', 0, 'L');
        $this->MultiCell($this->pag_size[0], 3, 'Loma Bonita', 0, 'L');

        $this->MultiCell($this->pag_size[0], 3, 'TICKET : ' . $data[0]->id_ticket , 0, 'L');

        $this->MultiCell($this->pag_size[0], 3, 'FECHA :' . date('d-m-Y H:i:s'), 0, 'L');

    }

    public function productosTicket($data, $data_info){

        $this->SetY($this->GetY()+3);

        $this->SetFont('Arial', '', 8);
        $this->SetWidths(array(32, 31));
        $this->SetAligns(array('L'));
        $this->Row(array('CODIGO', 'ARTICULO'), false, false);

        $this->SetWidths(array(12, 19, 30));
        $this->SetAligns(array('L'));
        $this->Row(array('CANT.', 'PRECIO/UN.', 'IMPORTE TOTAL'), false, false);

        $this->SetFont('Arial', '', 8);
        $this->CheckPageBreak(4);
        $this->MultiCell($this->pag_size[0], 3, '---------------------------------------------------', 0, 'L');
        if(is_array($data_info)){
            foreach ($data_info as $prod){
              $this->SetFont('Arial', '', 8);
              $this->SetWidths(array(32, 31));
              $this->SetAligns(array('L'));
              $this->Row(array($prod->nombre, $prod->nombre), false, false);

              $this->SetWidths(array(12, 19, 30));
              $this->SetAligns(array('L'));
              $this->Row(array($prod->cantidad, String::formatoNumero($prod->precio_venta,2), String::formatoNumero($prod->importe,2)), false, false);
              $this->SetY($this->GetY() + 3);
            }
        }
        $this->CheckPageBreak(4);
        $this->MultiCell($this->pag_size[0], 3, '---------------------------------------------------', 0, 'L');

        $this->SetWidths(array(31, 30));
        $this->SetAligns(array('L'));
        $this->Row(array( 'TOTAL', String::formatoNumero($data[0]->total)), false, false, 3);

        $this->SetY($this->GetY() + 5);

        $this->Row(array( 'ENTREGADO', String::formatoNumero($data[0]->recibido)), false, false, 3);
        $this->Row(array( 'CAMBIO', String::formatoNumero($data[0]->cambio)), false, false, 3);
    }

    public function pieTicket($data){

      $this->SetY($this->GetY() + 5);

      $this->SetFont('Arial', '', 8);
      $this->SetWidths(array($this->pag_size[0]));
      $this->SetAligns(array('L'));
      $this->Row(array('3634-3430' ), false, false);
      $this->SetY($this->GetY() - 3);
      $this->Row(array('www.cafedigital.mx' ), false, false);
    }

    public function printTicket($data, $data_prod){
        $this->datosTicket($data);
        $this->productosTicket($data, $data_prod);
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




    /*Crear tablas*/
    var $widths;
    var $aligns;
    var $links;

    function SetWidths($w){
        $this->widths=$w;
    }

    function SetAligns($a){
        $this->aligns=$a;
    }

    function SetMyLinks($a){
        $this->links=$a;
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

                if($header && $bordes)
                    $this->Rect($x,$y,$w,$h,'DF');
                elseif($bordes)
                    $this->Rect($x,$y,$w,$h);

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
        $script="print($param);";
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
}


?>