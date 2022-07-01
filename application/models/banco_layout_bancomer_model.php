<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_layout_bancomer_model extends banco_cuentas_model {
	public $row_control = '';

	function __construct()
	{
		parent::__construct();
	}

  public function get($pagos, $cuenta_retiro)
  {
    // echo "<pre>";
    //   var_dump($pagos, $cuenta_retiro);
    // echo "</pre>";exit;
    if (count($pagos) > 0)
    {
      $noFile = isset($_GET['nofile']{0})? $_GET['nofile']: 1;
      $fecha = date("Y-m-d");

      // Escribe encabezado en archivo
      $header = 'H';
      $header .= $cuenta_retiro->no_cliente;
      $header .= $fecha;
      $header .= $this->llena0(2, $noFile);  //Consecutivo de archivo en el día
      $header .= $this->string("PAGOS".str_replace('-', '', $fecha), 30);
      $header .= "00                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                ";
      $header .= "\r\n";

      $pagos_archivo = $this->getPagosGrup($pagos, $cuenta_retiro);

      // Escribe detalle en archivo
      $reg = '';
      $renglon = 1;
      $total = 0;
      foreach ($pagos_archivo as $key => $value) {
        if ($value['monto'] > 0 && strlen($value['proveedor_cuenta']) > 5) {
          $reg .= 'DAP';
          $reg .= $this->string("PAGO{$renglon}", 20);
          $reg .= $this->string($value['beneficiario_clave'], 30);
          $reg .= 'PDA';
          $reg .= $this->llena0(1, $value['tipo_cuenta']);
          $reg .= '00000000000000000000               ';
          $reg .= $this->string(($value['tipo_cuenta']=='2'? '': $value['ref_numerica']), 7);
          $reg .= '                  ';
          $reg .= $this->string(($value['tipo_cuenta']=='2'? '': trim($value['descripcion'])), 30);
          $reg .= '                                                                                    N                                                                                                                                                                                           MXP                    ';
          $reg .= 'FA';
          $reg .= $this->llena0(15, number_format($value['monto'], 2, '', ''));
          $reg .= '0000000000000000';
          $reg .= '0'; // 0:sin confirmacion, 1:confirmar email, 3:confirmar msn
          $reg .= '                                                  N000000000000';
          $reg .= "{$fecha}0001-01-010001-01-010001-01-01{$fecha}N 0001-01-01700                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                0001-01-01";
          $reg .= "\r\n";

          $total += floatval(number_format($value['monto'], 2, '.', ''));
          $renglon++;
        }
      }

      // Escribe sumario en archivo
      $footer = '';
      $footer .= 'T';
      $footer .= $this->llena0(10, $renglon-1);
      $footer .= $this->llena0(15, number_format($total, 2, '', '') );
      $footer .= '000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000                                                                 ';
      $footer .= "\r\n";

      header("Content-Disposition: attachment; filename=SITPagos_".date("d-m-Y").'.txt');
      echo($header.$reg.$footer);
    }
  }

  public function getPagosGrup($pagos, $cuenta_retiro)
  {
    $pagos_archivo = array();
    $total_pagar = $num_abonos = 0;
    foreach ($pagos as $key => $pago)
    {
      $total_proveedor = 0;
      foreach ($pago->pagos as $keyp => $value)
      {
        $total_pagar += $value->new_total; // monto
        $total_proveedor += $value->new_total; //monto
      }
      if ($total_proveedor > 0)
      {
        $num_abonos++;
        $pagos_archivo[] = array(
          'monto'              => $total_proveedor,
          'proveedor_sucursal' => $pago->pagos[0]->sucursal,
          'proveedor_cuenta'   => $pago->pagos[0]->cuenta,
          'ref_alfanumerica'   => $pago->pagos[0]->ref_alfanumerica,
          'beneficiario_clave' => $pago->pagos[0]->cuenta, // $this->cleanStr($pago->pagos[0]->alias, true),
          'beneficiario'       => $pago->nombre_fiscal,
          'es_moral'           => $pago->es_moral,
          'clave_banco'        => $pago->pagos[0]->codigo_bajio,
          'ref_numerica'       => $pago->pagos[0]->referencia,
          'descripcion'        => $pago->pagos[0]->descripcion,
          'alias'              => $pago->pagos[0]->alias,
          'importe_iva'        => '0',
          'tipo_cuenta'        => $this->getTipoCuenta($pago->pagos[0], $cuenta_retiro),
        );
      }
    }
    return $pagos_archivo;
  }

  public function getTipoCuenta($pago, $cuenta_retiro)
  {
    // 1 = Pago en Ventanilla
    // 2 = Pago a cuenta Bancomer
    // 3 = Pago a convenio CIE
    // 4 = Pago a cuenta Internacional vía SWIFT
    // 5 = Pago a cuenta Interbancaria día siguiente
    // 6 = Pago a cuenta Interbancaria mismo día
    $tipo = '6'; // interbancarias
    $leng = strlen($pago->cuenta);
    if ($pago->id_banco == $cuenta_retiro->id_banco) { // cuentas bancomer
      $tipo = '2';
    }
    return $tipo;
  }


  private function llena0($hasta, $str, $char='0', $dir='I'){
    $llenar = '';
    for ($i=1;$i<=($hasta-strlen($str));$i++)
      $llenar .= $char;
    return ($dir=='I'? $llenar.$str: $str.$llenar);
  }

	private function numero($numero, $pos, $decimales=false)
	{
		if ($decimales)
			$numero = str_replace('.', '', number_format($numero, 2, '.', ''));
		$leng  = mb_strlen($numero);
		$datos = $pos-$leng;
		$ceros = '';
		for ($i = 1; $i <= $datos; $i++)
			$ceros .= '0';
		$numero = $ceros.$numero;

		return $numero;
	}
	private function string($str, $pos, $end='')
	{
		$leng = mb_strlen($str);
		$datos = $pos-$leng;
    if($datos > 0){
      $str .= $end;
  		for ($i = 1; $i <= $datos; $i++)
  		{
  			$str .= ' ';
  		}
    }else{
      $str = mb_substr($str, 0, $pos).$end;
    }
		return $str;
	}
  private function cleanStr($string, $upper=false)
  {
    $string = mb_strtolower($string, 'UTF-8');
    $string = str_replace(
      ['ñ','á','é','í','ó','ú','*','#','$','%','=','+','&','^','-','_',',','.',';',':'], //' ',
      ['n','a','e','i','o','u','','','','','','','','','','','','','','',''], $string); //'',
    if ($upper) {
      $string = mb_strtoupper($string, 'UTF-8');
    }
    return trim($string);
  }

  function getNombre($nombre){
    $arreglo = explode(' ', $nombre);
    $size = count($arreglo);

    //si el nombre tiene solo 2 palabras
    if($size==2){
      //el primero es nombre
      $nombre =$arreglo[1];
      //el segundo es apellido
      $apellidop = $arreglo[0];
      $apellidom = "";
    }else{
      //los tokens se utilizan para crear apellidos compuestos
      $tokens = "de la del las los mac mc van von y i san santa ";
      $nombre ="";
      $apellidop = "";
      $apellidom = "";
      $token = 'am';

      // for ($contz=$size-1; $contz>=0; $contz--)
      for ($contz=0; $contz<$size; $contz++)
      {
        if($contz == 0)
          $apellidop = $arreglo[$contz];
        elseif($contz == 1)
          $apellidom = $arreglo[$contz];
        else
          $nombre = $arreglo[$contz].' '.$nombre;
        // if(!$this->buscarCadena($tokens, $arreglo[$contz]))
        //   $token = $token=='am'? 'ap': 'n';

        // if($token == 'am')
        //   $apellidom = $arreglo[$contz].' '.$apellidom;
        // elseif($token == 'ap')
        //   $apellidop = $arreglo[$contz].' '.$apellidop;
        // elseif($token == 'n')
        //   $nombre = $arreglo[$contz].' '.$nombre;
      }
    }

    $nombre2 = trim($nombre);
    $nombre = explode(' ', $nombre2);
    if(count($nombre) > 1)
    {
      $nombre2 = '';
      for ($contz=count($nombre)-1; $contz>=0; $contz--)
        $nombre2 .= $nombre[$contz].' ';
    }

    return array(trim($nombre2), trim($apellidop), trim($apellidom));
  }

  function buscarCadena($cadena, $palabra){
    if(stristr($cadena,$palabra)) return true;
    else return false;
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */