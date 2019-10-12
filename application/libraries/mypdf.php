<?php

class MYpdf extends FPDF {
  var $show_head = true;
  var $titulo1 = "EMPAQUE SAN JORGE S.A. DE C.V.";
  var $titulo2 = '';
  var $titulo3 = '';
  var $reg_fed = 'REG. ESJ97052763A0620061646';
  var $logo = '/images/logo.png';

  var $fount_txt = 'helvetica';
  var $fount_num = 'SciFly-Sans'; // SciFly-Sans
  var $font_size = 8;
  var $pag_size = array();

  var $hheader = '';

  var $limiteY     = 0;
  var $noShowPages = true;
  var $noShowDate = true;
  var $noShowPagesPos = null;

  var $auxy = 0;

  /**
   * P:Carta Vertical, L:Carta Horizontal, lP:Legal vertical, lL:Legal Horizontal
   * @param unknown_type $orientation
   * @param unknown_type $unit
   * @param unknown_type $size
   */
  function __construct($orientation='P', $unit='mm', $size='Letter'){
    parent::__construct($orientation, $unit, $size);

    $this->pag_size = $size;

    if(!is_array($size))
      $this->hheader = 'header'.$size.$orientation;

  }

  //Page header
  public function Header() {
    if($this->show_head){
        // Logo
      if($this->logo != '')
        $this->Image(APPPATH.(str_replace(APPPATH, '', $this->logo)), 6, 5, 20);
      $this->SetFont('Arial','',5);
      //$this->Text(6, 15, 'EXTINTORES Y SISTEMAS CONTRA INCENDIOS');

      $this->{$this->hheader}();

      // Salto de línea
      $this->Ln(20);
    }

    $this->auxy = 0;
  }

  // Page footer
  public function Footer() {
    if($this->noShowPages && $this->noShowPagesPos)
      $this->Text($this->noShowPagesPos, 5, $this->PageNo().'/{nb}');
  }
    /**
     * Carta Vertical
     */
    public function headerLetterP(){
      // Título
      $this->SetFont('Arial','B',14);
      $this->SetXY(46, 6);
      $this->MultiCell(141, 6, $this->titulo1, 0, 'C', false);

      $this->SetFont('Arial','B',11);
      $this->SetX(46);
      $this->MultiCell(141, 6, $this->titulo2, 0, 'C', false);

      if($this->titulo3 != ''){
        $this->SetFont('Arial','B',8);
        $this->SetX(46);
        $this->MultiCell(141, 4, $this->titulo3, 0, 'C', false);
      }

      $this->SetFont('Arial','I',8);
      $this->SetXY(211, 5);
      if($this->noShowPages)
       $this->Cell(3, 5, $this->PageNo().'/{nb}', 0, 0, 'R');
      $this->SetXY(194, 8);
      if($this->noShowDate)
        $this->Cell(16, 5, date("d/m/Y H:i:s"), 0, 0, 'R');

      // $this->Line(6, 26, 210, 26);

      $this->limiteY = 235; //limite de alto
    }
    /**
     * Carta horizontal
     */
    public function headerLetterL(){
      // Título
      $this->SetFont('Arial','B',14);
      $this->SetXY(46, 6);
      $this->Cell(206, 6, $this->titulo1, 0, 0, 'C');

      $this->SetFont('Arial','B',11);
      $this->SetXY(46, 11);
      $this->Cell(206, 6, $this->titulo2, 0, 0, 'C');

      if($this->titulo3 != ''){
        $this->SetFont('Arial','B',9);
        $this->SetXY(46, 17);
        $this->MultiCell(206, 4, $this->titulo3, 0, 'C', false);
      }

      $this->SetFont('Arial','I',8);
      $this->SetXY(276, 5);
      if($this->noShowPages)
       $this->Cell(3, 5, $this->PageNo().'/{nb}', 0, 0, 'R');
      $this->SetXY(259, 8);
      if($this->noShowDate)
        $this->Cell(16, 5, date("d/m/Y H:i:s"), 0, 0, 'R');

      $this->Line(6, 26, 273, 26);

      $this->limiteY = 190; //limite de alto
    }
    /**
     * Legal Vertical
     */
    public function headerLegalP(){
      // Título
      $this->SetFont('Arial','B',14);
      $this->SetXY(46, 6);
      $this->Cell(141, 6, $this->titulo1, 0, 0, 'C');

      $this->SetFont('Arial','B',11);
      $this->SetXY(46, 11);
      $this->Cell(141, 6, $this->titulo2, 0, 0, 'C');

      if($this->titulo3 != ''){
        $this->SetFont('Arial','B',9);
        $this->SetXY(46, 17);
        $this->MultiCell(141, 4, $this->titulo3, 0, 'C', false);
      }


      $this->SetFont('Arial','I',8);
      $this->SetXY(211, 5);
        if($this->noShowPages)
         $this->Cell(3, 5, $this->PageNo().'/{nb}', 0, 0, 'R');
      $this->SetXY(194, 8);
      if($this->noShowDate)
        $this->Cell(16, 5, date("d/m/Y H:i:s"), 0, 0, 'R');

      $this->Line(6, 26, 210, 26);
    }
    /**
     * Legal horizontal
     */
    public function headerLegalL(){
      // Título
      $this->SetFont('Arial','B',14);
      $this->SetXY(46, 6);
      $this->Cell(280, 6, $this->titulo1, 0, 0, 'C');

      $this->SetFont('Arial','B',11);
      $this->SetXY(46, 11);
      $this->Cell(280, 6, $this->titulo2, 0, 0, 'C');

      if($this->titulo3 != ''){
        $this->SetFont('Arial','B',9);
        $this->SetXY(46, 17);
        $this->MultiCell(280, 4, $this->titulo3, 0, 'C', false);
      }


      $this->SetFont('Arial','I',8);
      $this->SetXY(350, 5);
        if($this->noShowPages)
         $this->Cell(3, 5, $this->PageNo().'/{nb}', 0, 0, 'R');
      $this->SetXY(333, 8);
      if($this->noShowDate)
        $this->Cell(16, 5, date("d/m/Y H:i:s"), 0, 0, 'R');

      $this->Line(6, 26, 349, 26);
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
    var $font;
    var $fontz;
    public $fontb;
    var $font_bold = '';

    function SetWidths($w){
      $this->widths=$w;
    }

    function SetAligns($a){
      $this->aligns=$a;
    }

    function SetMyLinks($a){
      $this->links=$a;
    }

    function SetFounts($a, $z=array(), $b=[]){
        $this->font=$a;
        $this->fontz=$z;
        $this->fontb=$b;
    }

    function Row($data, $header=false, $bordes=true, $colortxt=null, $height=3, $positionY=2){
      $nb=0;
      for($i=0;$i<count($data);$i++)
        $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
        $h=$this->FontSize*$nb+$height;
        // if($header)
        //  $h += 2;
        $this->CheckPageBreak($h);
        for($i=0;$i<count($data);$i++){
          $w=$this->widths[$i];
          $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
          $bord=0;
          $x=$this->GetX();
          $y=$this->GetY();

          if($header===true && $bordes===true)
            $this->Rect($x,$y,$w,$h,'DF');
          elseif($bordes===true)
            $this->Rect($x,$y,$w,$h);
          else {
            if ($bordes === 'B') {
              $this->Line($x,$y+$h,$x+$w,$y+$h);
            }
          }

          if($header)
            $this->SetXY($x,$y+$positionY);
          else
            $this->SetXY($x,$y+$positionY);

          if (isset($colortxt[$i])) {
              $this->SetTextColor($colortxt[$i][0], $colortxt[$i][1], $colortxt[$i][2]);
          }

          if(isset($this->links[$i]{0}) && $header==false){
            $this->SetTextColor(35, 95, 185);
            $this->Cell($w, $this->FontSize, $data[$i], $bord, strlen($data[$i]), $a, false, $this->links[$i]);
            $this->SetTextColor(0,0,0);
          }else
            $this->MultiCell($w,$this->FontSize, $data[$i],$bord,$a);

          $this->SetXY($x+$w,$y);
        }
        $this->Ln($h);
    }


    function Row2($data, $header=false, $bordes=true, $h=NULL, $colortxt=null){
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
                $bord=0;
                $x=$this->GetX();
                $y=$this->GetY();

                $this->SetFont( (isset($this->font[$i]) ? $this->font[$i] : 'helvetica'), (isset($this->fontb[$i]) ? $this->fontb[$i] : $this->font_bold), ($this->font_size+(isset($this->fontz[$i]) ? $this->fontz[$i] : 0)) );

                if($header===true && $bordes===true)
                  $this->Rect($x,$y,$w,$h,'DF');
                elseif($bordes===true)
                  $this->Rect($x,$y,$w,$h);
                else {
                  if ($bordes === 'B') {
                    $this->Line($x,$y+$h,$x+$w,$y+$h);
                  }
                }

                if($header)
                    $this->SetXY($x,$y+3);
                else
                    $this->SetXY($x,$y+2);

                if (isset($colortxt[$i])) {
                    $this->SetTextColor($colortxt[$i][0], $colortxt[$i][1], $colortxt[$i][2]);
                }

                if(isset($this->links[$i]{0}) && $header==false){
                    $this->SetTextColor(35, 95, 185);
                    $this->Cell($w, $this->FontSize, $data[$i], $bord, strlen($data[$i]), $a, false, $this->links[$i]);
                    $this->SetTextColor(0,0,0);
                }else
                    $this->MultiCell($w,$this->FontSize, $data[$i],$bord,$a);

                $this->SetXY($x+$w,$y);
            }
            $this->Ln($h);
    }

    function CheckPageBreak($h, $limit=0){
      $limit = $limit==0? $this->PageBreakTrigger: $limit;
      if($this->GetY()+$h>$limit) {
        if (count($this->pages) > $this->page) {
          $this->page++;
          $this->SetXY(111, 10);
        } else
          $this->AddPage($this->CurOrientation);
      }
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

}


?>