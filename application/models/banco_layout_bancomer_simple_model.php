<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_layout_bancomer_simple_model extends banco_cuentas_model {
	public $row_control = '';

	function __construct()
	{
		parent::__construct();
	}

  public function get($pagos, $cuenta_retiro, $tipo = 'compras')
  {
    // echo "<pre>";
    //   var_dump($pagos, $cuenta_retiro);
    // echo "</pre>";exit;
    if (count($pagos) > 0)
    {
      $noFile = isset($_GET['nofile']{0})? $_GET['nofile']: 1;
      $fecha = date("Y-m-d");

      if ($tipo == 'compras') {
        $pagos_archivo = $this->getPagosGrup($pagos, $cuenta_retiro);
      } else {
        $pagos_archivo = $this->getPagosGrup($pagos, $cuenta_retiro);
      }
      // echo "<pre>";
      // var_dump($pagos_archivo);
      // echo "</pre>";exit;

      // Escribe detalle en archivo
      $regMb = '';
      $regIn = '';
      $renglon = 1;
      $total = 0;
      foreach ($pagos_archivo as $key => $value) {
        if ($value['monto'] > 0 && strlen($value['proveedor_cuenta']) > 5) {
          if ($value['banco'] == $cuenta_retiro->banco) {
            $regMb .= $this->llena0(18, $value['proveedor_cuenta']);
            $regMb .= $this->llena0(18, $value['cuenta_retiro']);
            $regMb .= "MXP";
            $regMb .= $this->llena0(16, number_format($value['monto'], 2, '.', ''));
            $regMb .= $this->llena0(30, $value['ref_alfanumerica'], ' ', 'D');
            $regMb .= "\r\n";
          } else {
            $regIn .= $this->llena0(18, $value['proveedor_cuenta']);
            $regIn .= $this->llena0(18, $value['cuenta_retiro']);
            $regIn .= "MXP";
            $regIn .= $this->llena0(16, number_format($value['monto'], 2, '.', ''));
            $regIn .= $this->llena0(30, $value['beneficiario'], ' ', 'D');
            $regIn .= $this->llena0(2, $value['tipo_cuenta'], ' ');
            $regIn .= $this->llena0(3, $value['no_banco'], ' ');
            $regIn .= $this->llena0(30, $value['ref_alfanumerica'], ' ', 'D');
            $regIn .= $this->llena0(7, $value['ref_numerica']);
            $regIn .= "H";
            $regIn .= "\r\n";
          }

          $total += floatval(number_format($value['monto'], 2, '.', ''));
          $renglon++;
        }
      }
      // echo "<pre>";
      // var_dump($regIn, $regMb);
      // echo "</pre>";exit;

      $nombre = "pagos_bancomer";
      $zip = new ZipArchive;
      if ($zip->open(APPPATH."media/temp/{$nombre}.zip", ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true)
      {
        if (strlen($regMb) > 0) {
          $zip->addFromString("BBVA_Bancomer-".date('Y-m-d').".txt", $regMb);
        }
        if (strlen($regIn) > 0) {
          $zip->addFromString("BBVA_Interbancarias-".date('Y-m-d').".txt", $regIn);
        }

        $zip->close();
      }
      else
      {
        exit('Error al intentar crear el ZIP.');
      }

      header('Content-Type: application/zip');
      header("Content-disposition: attachment; filename={$nombre}.zip");
      readfile(APPPATH."media/temp/{$nombre}.zip");

      unlink(APPPATH."media/temp/{$nombre}.zip");
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
          'cuenta_retiro'      => $cuenta_retiro->cuenta,
          'monto'              => $total_proveedor,
          'proveedor_sucursal' => $pago->pagos[0]->sucursal,
          'proveedor_cuenta'   => $pago->pagos[0]->cuenta,
          'ref_alfanumerica'   => $pago->pagos[0]->ref_alfanumerica,
          'beneficiario'       => $pago->nombre_fiscal,
          'es_moral'           => $pago->es_moral,
          'clave_banco'        => $pago->pagos[0]->codigo_bajio,
          'ref_numerica'       => $pago->pagos[0]->referencia,
          'descripcion'        => trim($pago->pagos[0]->descripcion),
          'alias'              => $pago->pagos[0]->alias,
          'importe_iva'        => '0',
          'tipo_cuenta'        => $this->getTipoCuenta($pago->pagos[0], $cuenta_retiro),
          'no_banco'           => substr($pago->pagos[0]->cuenta, 0, 3),
          'banco'              => $pago->cuentas_proveedor[0]->banco,
        );
      }
    }
    return $pagos_archivo;
  }

  public function getTipoCuenta($pago, $cuenta_retiro)
  {
    $tipo = '40'; // clabe
    $leng = strlen($pago->cuenta);
    if ($leng < 18) { // tarjeta
      $tipo = '03';
    }
    return $tipo;
  }


  private function llena0($hasta, $str, $char='0', $dir='I'){
    $llenar = '';
    for ($i=1;$i<=($hasta-strlen($str));$i++)
      $llenar .= $char;
    $string = ($dir=='I'? $llenar.$str: $str.$llenar);
    return substr($string, 0, $hasta);
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

  function buscarCadena($cadena, $palabra){
    if(stristr($cadena,$palabra)) return true;
    else return false;
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */