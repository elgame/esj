<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller{
	protected $info_empleado;
	
	function MY_Controller($redirect=true){
		date_default_timezone_set('America/Mexico_City');
		parent::__construct();
		
		$this->limpiaParams();
		$this->updateSessionExp();
		
		$this->load->helper(array('form', 'url'));
		$this->load->library('carabiner');
		$this->carabiner->config(
			array(
			    'base_uri'   => base_url(),
			    'combine'    => false,
			    'dev'        => true
		));
	}
	
	private function limpiaParams(){
		foreach ($_POST as $key => $value)
    		$_POST[$key] = String::limpiarTexto(($value));
		
		foreach ($_GET as $key => $value)
			$_GET[$key] = String::limpiarTexto(($value));
	}

	/*
	|	Verifica si existe la session o cookie con el parametro remember que indica si el usuario 
	| al momento de loguearse marco el campo "no cerrar sesion"
	*/
	public function updateSessionExp()
	{
		if ($this->session->userdata('remember'))
		{
			$this->session->sess_expiration      = 60*60*24*365;
			$this->session->sess_expire_on_close = FALSE;

			$unset_data = array('id_usuario' => '', 
													'username'   => '', 
													'email'      => '',
													'remember'   => '', 
													'acceso'     => '', 
													'idunico'     => '',
													'tipo'       => '');
			
			$user_data  = array('id_usuario'=> $this->session->userdata('id_usuario'),
													'username'  => $this->session->userdata('username'),
													'email'     => $this->session->userdata('email'),
													'remember'	=> TRUE,
													'acceso'    => $this->session->userdata('acceso'), 
													'idunico'   => $this->session->userdata('idunico'),
													'tipo'      => $this->session->userdata('tipo'));
			
			$this->session->unset_userdata($unset_data);
			$this->session->set_userdata($user_data);
		}	
	}
}
?>