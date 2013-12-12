<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_layout_model extends banco_cuentas_model {
	public $row_control = '';

	function __construct()
	{
		parent::__construct();
	}

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
			$this->row_control .= '01'; //Tipo de cuenta
			if ($this->input->get('toperacion')=='ba') //banamex
				$cuenta = $this->numero($value->data_prov->sucursal, 4).$this->numero($value->data_prov->cuenta, 7);
			else //interbancaria
				$cuenta = $value->data_prov->cuenta;
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
		$this->row_control .= $this->numero($data['total_retiro'], 18, true); //Importe total de cargos
	}


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
	private function string($str, $pos)
	{
		$leng = strlen($str);
		$datos = $pos-$leng;
		for ($i = 1; $i <= $datos; $i++)
		{
			$str .= ' ';
		}
		return $str;
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */