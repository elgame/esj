<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_layout_bajio_model extends banco_cuentas_model {
	public $row_control = '';

	function __construct()
	{
		parent::__construct();
	}

  public function get($pagos, $cuenta_retiro)
  {
    if (count($pagos) > 0)
    {
      $noFile = isset($_GET['nofile']{0})? $_GET['nofile']: 1;

      // Escribe encabezado en archivo
      $header = '01';
      $header .= "0000001";
      $header .= date("Ymd");
      $header .= $this->llena0(3, $noFile)."\r\n"; //Consecutivo de archivo en el día

      $pagos_archivo = $this->getPagosGrup($pagos, $cuenta_retiro);

      // Escribe detalle en archivo
      $reg = '';
      $renglon = 2;
      $total = 0;
      foreach ($pagos_archivo as $key => $value) {
        if ($value['monto'] > 0 && strlen($value['proveedor_cuenta']) > 5) {
          $reg .= '02';
          $reg .= $this->llena0(7, $renglon);
          $reg .= '01';
          $reg .= $this->llena0(20, $cuenta_retiro->cuenta); // '53708870201'
          $reg .= "01";
          $reg .= $this->llena0(5, $value['clave_banco']);
          $reg .= $this->llena0(15, number_format($value['monto'], 2, '', '') );
          $reg .= date("Ymd");
          $reg .= $this->llena0(3, $value['tipo_cuenta']=='1'?'BCO':'SPI');
          $reg .= $this->llena0(2, $value['tipo_cuenta']); // 01 es cuenta de cheques con 11 dígitos (mismo banco) 40 es cuenta clabe con 18 digitos (TEF, SPEI, mismo banco) 03 es no. de tarjeta de debito con 16 dígitos (TEF, SPEI, mismo bco)
          $reg .= $this->llena0(20, $value['proveedor_cuenta']);
          $reg .= "000000000";
          $reg .= $this->llena0(15, trim($value['alias']), ' ', 'D');
          $reg .= $this->llena0(15, number_format($value['importe_iva'], 2, '', '') );
          //$reg .= "000000000000000"
          $reg .= $this->llena0(40, trim($value['descripcion']), ' ', 'D')."\r\n";
          $total += floatval(number_format($value['monto'], 2, '.', ''));
          $renglon++;
        }
      }

      // Escribe sumario en archivo
      $footer = '';
      $footer .= '09';
      $footer .= $this->llena0(7, $renglon);
      $footer .= $this->llena0(7, $renglon-2);
      $footer .= $this->llena0(18, number_format($total, 2, '', '') )."\r\n";

      header("Content-Disposition: attachment; filename=pagos_".date("dmY").'.txt');
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
    $tipo = '40'; // interbancarias
    $leng = strlen($pago->cuenta);
    if ($leng == 11 && $pago->id_banco == $cuenta_retiro->id_banco) { // cuentas bajio
      $tipo = '1';
    } elseif ($leng == 16) { // tarjetas
      $tipo = '3';
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
  private function cleanStr($string)
  {
    return str_replace(array('ñ','Ñ','*','#','$','%','=','+'), array('n','N','','','','','',''), $string);
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