<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_layout_model extends banco_cuentas_model {
	public $row_control = '';

	function __construct()
	{
		parent::__construct();
	}

  public function get($data)
  {
    if (count($data['pagos']) > 0)
    {
      $res_files = $this->db->query("SELECT Count(id_mov_banamex) AS num FROM banco_movimientos_banamex
              WHERE Date(fecha) = '".$data['fecha_pago']."' AND tipo_operacion = '".$data['toperacion']."'")->row();
      $data['conta_dia'] = $res_files->num+1;

      $this->regControl($data);
      $this->regGlobal($data);
      $this->regIndividual($data);
      $this->regTotales($data);

      $this->db->insert('banco_movimientos_banamex', array(
        'fecha'          => $data['fecha_pago'],
        'tipo_operacion' => $data['toperacion'],
        'registros'      => $this->row_control,
        'id_cuenta'      => $data['id_cuenta'],
        ));

      header ("Content-Disposition: attachment; filename=".($data['toperacion']=='ba'? 'banamex': 'interbancaria').".txt ");
      header ("Content-Type: application/octet-stream");
      echo $this->row_control;
    }
  }

  public function regControl($data)
  {
    $this->row_control = '1'; //tipo de registro
    $this->row_control .= $this->numero($data['numero_cliente'], 12); //Número de identificación del cliente
    $this->row_control .= date("dmy", strtotime( $data['fecha_pago'] )); //Fecha de pago
    $this->row_control .= $this->numero($data['conta_dia'], 4); //Secuencial del archivo
    $this->row_control .= $this->string($data['nombre_empresa'], 36); //Nombre del la empresa
    $this->row_control .= $this->string($data['description'], 20); //Descripción del archivo
    $this->row_control .= ($data['toperacion']=='ba'? '06': '12'); //Naturaleza del archivo, 06:Pago a proveedores, 12:Pagos interbancarios CLABE
    $this->row_control .= $this->string('', 40); //Instrucciones para órdenes de pago
    $this->row_control .= 'C'; //Versión del layout
    $this->row_control .= '0'; //Volúmen
    $this->row_control .= "0\r\n"; //Características del archivo
  }

  public function regGlobal($data)
  {
    $this->row_control .= '2'; //tipo de registro
    $this->row_control .= '1'; //Tipo de operación, 1:Si es Cargo
    $this->row_control .= '001'; //Clave de la moneda
    $this->row_control .= $this->numero($data['total_retiro'], 18, true); //Importe a abonar o cargar
    $this->row_control .= '01'; //Tipo de cuenta
    $this->row_control .= $this->numero($data['sucursal'], 4); //Número de sucursal
    $this->row_control .= $this->numero($data['cuenta'], 20); //Número de cuenta
    $this->row_control .= $this->string('', 20)."\r\n"; //Espacio en blanco
  }

  public function regIndividual($data)
  {
    foreach ($data['pagos'] as $key => $value)
    {
      $this->row_control .= '3'; //tipo de registro
      $this->row_control .= '0'; //Tipo de operación, 1:Si es Cargo
      $this->row_control .= '001'; //Clave de la moneda
      $this->row_control .= $this->numero($value['monto'], 18, true); //Importe a abonar o cargar
      $tipo_cuenta = '01';
      if ($data['toperacion']=='ba') //banamex
      {
        if(strlen($value['proveedor_cuenta']) == 16){ //Tarjeta de debito o credito
          $cuenta = $value['proveedor_cuenta'];
          $tipo_cuenta = '03';
        }else
          $cuenta = $this->numero($value['proveedor_sucursal'], 4).$this->numero($value['proveedor_cuenta'], 7);
        $ref_alfanumerica = substr($value['ref_numerica'], 0, 10);
        $instrucciones = $value['ref_alfanumerica'];
        $descripcion = $value['descripcion'];
        $ref_numerica = '';
      }else //interbancaria
      {
        if($value['es_moral'] == 'f')
        {
          $new_nombre = $this->getNombre($value['beneficiario']);
          $value['beneficiario'] = $this->string( $this->cleanStr($new_nombre[0].','.$new_nombre[1].'/'.$new_nombre[2]), 55);
        }else
          $value['beneficiario'] = ','.$this->string( $this->cleanStr($value['beneficiario']), 53, '/');
        $cuenta = $value['proveedor_cuenta'];
        if(strlen($cuenta) == 16) //Tarjeta de debito o credito
          $tipo_cuenta = '03';
        $ref_alfanumerica = $value['ref_alfanumerica'];
        $instrucciones = $descripcion = '';
        $ref_numerica = $value['ref_numerica'];
      }
      $this->row_control .= $tipo_cuenta; //Tipo de cuenta
      $this->row_control .= $this->numero($cuenta, 20); //Número de cuenta
      $this->row_control .= $this->string($ref_alfanumerica, 40); //Referencia  Alfanumérica/Numerica
      $this->row_control .= $value['beneficiario']; //Beneficiario
      $this->row_control .= $this->string($instrucciones, 40); //Instrucciones
      $this->row_control .= $this->string($descripcion, 24); //Descripción TEF
      $this->row_control .= $this->numero($value['clave_banco'], 4); //Clave de Banco
      $this->row_control .= $this->numero( $ref_numerica , 7); //Referencia Numérica
      $this->row_control .= "00\r\n"; //Plazo
    }
  }

  public function regTotales($data)
  {
    $this->row_control .= '4'; //tipo de registro
    $this->row_control .= '001'; //Clave de la moneda
    $this->row_control .= $this->numero($data['num_abonos'], 6); //Número de abonos
    $this->row_control .= $this->numero($data['total_retiro'], 18, true); //Importe total de abonos
    $this->row_control .= $this->numero($data['num_cargos'], 6); //Número de cargos
    $this->row_control .= $this->numero($data['total_retiro'], 18, true)."\r\n"; //Importe total de cargos
  }

/*
	public function get()
	{
		$this->load->model('banco_cuentas_model');
		$this->load->model('proveedores_model');

		$data = $this->banco_cuentas_model->getSaldoCuentaData();
		$data['total_retiro'] = $data['num_cargos'] = 0;
		foreach ($data['movimientos'] as $key => $value)
		{
		  if (isset($value->id_cuenta_proveedor))
		  {
		  	if ($value->id_cuenta_proveedor != NULL)
		  	{
		  		$data_proveedor = $this->proveedores_model->getCuentas($value->id_cli_pro, $value->id_cuenta_proveedor);
		  		if ($this->input->get('toperacion')=='ba')
		  		{
		  			if ($data_proveedor[0]->codigo == '7' ) //es banamex
			  		{
  						$data['movimientos'][$key]->data_prov = $data_proveedor[0];
  						$data['total_retiro']                 += $value->retiro;
  						$data['num_cargos']++;
			  		}else
			  			unset($data['movimientos'][$key]);
		  		}elseif($this->input->get('toperacion')=='in')
		  		{
		  			if ($data_proveedor[0]->codigo != '7' ) //es interbancario
			  		{
  						$data['movimientos'][$key]->data_prov = $data_proveedor[0];
  						$data['total_retiro']                 += $value->retiro;
  						$data['num_cargos']++;
			  		}else
			  			unset($data['movimientos'][$key]);
		  		}else
		  			unset($data['movimientos'][$key]);
		  	}
		  }else
		  	unset($data['movimientos'][$key]);
		}

		if (count($data['movimientos']) > 0)
		{
			$res_files = $this->db->query("SELECT Count(id_mov_banamex) AS num FROM banco_movimientos_banamex
							WHERE Date(fecha) = '".$this->input->get('ffecha2')."' AND tipo_operacion = '".$this->input->get('toperacion')."'")->row();
			$data['conta_dia'] = $res_files->num+1;

			$this->regControl($data);
			$this->regGlobal($data);
			$this->regIndividual($data);
			$this->regTotales($data);

			$this->db->insert('banco_movimientos_banamex', array(
				'fecha'          => $this->input->get('ffecha2'),
				'tipo_operacion' => $this->input->get('toperacion'),
				'registros'      => $this->row_control,
				'id_cuenta'      => $data['cuenta']['info']->id_cuenta,
				));

			header ("Content-Disposition: attachment; filename=".($this->input->get('toperacion')=='ba'? 'banamex': 'interbancaria').".txt ");
			header ("Content-Type: application/octet-stream");
			echo $this->row_control;
		}
	}

	public function regControl($data)
	{
		$this->row_control = '1'; //tipo de registro
		$this->row_control .= $this->numero('123456789', 12); //Número de identificación del cliente
		$this->row_control .= date("dmy", strtotime($this->input->get('ffecha2') )); //Fecha de pago
		$this->row_control .= $this->numero($data['conta_dia'], 4); //Secuencial del archivo
		$this->row_control .= $this->string($data['cuenta']['info']->nombre_fiscal, 36); //Nombre del la empresa
		$this->row_control .= $this->string('Pago a proveedores', 20); //Descripción del archivo
		$this->row_control .= ($this->input->get('toperacion')=='ba'? '06': '12'); //Naturaleza del archivo, 06:Pago a proveedores, 12:Pagos interbancarios CLABE
		$this->row_control .= $this->string('', 40); //Instrucciones para órdenes de pago
		$this->row_control .= 'C'; //Versión del layout
		$this->row_control .= '0'; //Volúmen
		$this->row_control .= "0\n"; //Características del archivo
	}

	public function regGlobal($data)
	{
		$this->row_control .= '2'; //tipo de registro
		$this->row_control .= '1'; //Tipo de operación, 1:Si es Cargo
		$this->row_control .= '001'; //Clave de la moneda
		$this->row_control .= $this->numero($data['total_retiro'], 18, true); //Importe a abonar o cargar
		$this->row_control .= '01'; //Tipo de cuenta
		$this->row_control .= $this->numero($data['cuenta']['info']->sucursal, 4); //Número de sucursal
		$this->row_control .= $this->numero($data['cuenta']['info']->cuenta, 20); //Número de cuenta
		$this->row_control .= $this->string('', 20)."\n"; //Espacio en blanco
	}

	public function regIndividual($data)
	{
		foreach ($data['movimientos'] as $key => $value)
		{
			$this->row_control .= '3'; //tipo de registro
			$this->row_control .= '1'; //Tipo de operación, 1:Si es Cargo
			$this->row_control .= '001'; //Clave de la moneda
			$this->row_control .= $this->numero($value->retiro, 18, true); //Importe a abonar o cargar
      $tipo_cuenta = '01';
      if ($this->input->get('toperacion')=='ba') //banamex
        $cuenta = $this->numero($value->data_prov->sucursal, 4).$this->numero($value->data_prov->cuenta, 7);
      else //interbancaria
      {
        $cuenta = $value->data_prov->cuenta;
        if(strlen($cuenta) == 16) //Tarjeta de debito o credito
          $tipo_cuenta = '03';
      }
			$this->row_control .= $tipo_cuenta; //Tipo de cuenta
			$this->row_control .= $this->numero($cuenta, 20); //Número de cuenta
			$this->row_control .= $this->string(date("dmy", strtotime($this->input->get('ffecha2') )), 40); //Referencia  Alfanumérica
			$this->row_control .= $this->string($value->cli_pro, 55); //Beneficiario
			$this->row_control .= $this->string(date("dmy", strtotime($this->input->get('ffecha2') )), 40); //Instrucciones
			$this->row_control .= $this->string('', 24); //Descripción TEF
			$this->row_control .= $this->numero($value->data_prov->codigo, 4); //Clave de Banco
			$this->row_control .= $this->numero( date("ymd", strtotime($this->input->get('ffecha2') )) , 7); //Referencia Numérica
			$this->row_control .= "00\n"; //Plazo
		}
	}

	public function regTotales($data)
	{
		$this->row_control .= '4'; //tipo de registro
		$this->row_control .= '001'; //Clave de la moneda
		$this->row_control .= $this->numero('0', 6); //Número de abonos
		$this->row_control .= $this->numero('0', 18, true); //Importe total de abonos
		$this->row_control .= $this->numero($data['num_cargos'], 6); //Número de cargos
		$this->row_control .= $this->numero($data['total_retiro'], 18, true)."\n"; //Importe total de cargos
	}
*/

	private function numero($numero, $pos, $decimales=false)
	{
		if ($decimales)
			$numero = str_replace('.', '', number_format($numero, 2, '.', ''));
		$leng  = strlen($numero);
		$datos = $pos-$leng;
		$ceros = '';
		for ($i = 1; $i <= $datos; $i++)
			$ceros .= '0';
		$numero = $ceros.$numero;

		return $numero;
	}
	private function string($str, $pos, $end='')
	{
		$leng = strlen($str);
		$datos = $pos-$leng;
    if($datos > 0){
      $str .= $end;
  		for ($i = 1; $i <= $datos; $i++)
  		{
  			$str .= ' ';
  		}
    }else{
      $str = substr($str, 0, $pos).$end;
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