<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_layout_model extends banco_cuentas_model {


	function __construct()
	{
		parent::__construct();
	}

	public function regControl()
	{
		$row_control = '1'; //tipo de registro
		$row_control .= ''; //Número de identificación del cliente
	}


	private function numero($numero, $pos)
	{
		
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */